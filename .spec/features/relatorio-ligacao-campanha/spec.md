# Spec: Relatório Completo e Confiabilidade das Campanhas de Ligação

> feature: relatorio-ligacao-campanha
> status: rascunho

## Contexto

As campanhas de chamada de voz (WhatsApp via meowcaller) registram hoje apenas
um **status final** por lead (`answered`, `no_answer`, `busy`, `failed`) e
contadores agregados. O cliente não consegue distinguir o que de fato aconteceu
em cada tentativa: se o número **atendeu pelo mobile** ou **pelo WhatsApp Web**,
se **tocou até desligar** (sem atender), se **foi desligado** pelo próprio
destinatário durante o áudio, se **ouviu o áudio até o final**, ou se houve um
**erro reportado pelo servidor** (ex.: `463` misdial/bloqueado, `403`
forbidden).

Além disso, os três modos de disparo — **fila**, **simultâneo** e **alternado** —
não funcionam plenamente:

- o modo **simultâneo** dispara em paralelo mas **nunca reconcilia** o resultado
  (não chama `pollCallResult`), deixando leads presos em `ringing` e contadores
  que nunca fecham;
- o modo **fila/alternado** usa um polling **bloqueante**, travando o worker em
  uma única chamada até o timeout;
- **não há ring timeout no gateway**: uma chamada que não recebe *nem*
  `preaccept` *nem* `accept/reject/terminate` fica **presa em `ringing` para
  sempre** no `callStore` (caso real `232A55B1…`, MetaSenderPro 2026-08-21);
- o worker, ao estourar o timeout, marca `failed` **sem cancelar** a chamada no
  gateway, deixando um "fantasma" ativo.

Esta feature corrige o ciclo de vida da chamada ponta a ponta e grava **cada
etapa** numa timeline auditável, alimentando um relatório completo por campanha
e por lead.

## Histórias

### US-026 — Timeline completa por tentativa de ligação

Como operador de campanhas de voz, quero ver **cada etapa** de cada tentativa
(disparou, tocou, atendeu [mobile|web], começou o áudio, terminou o áudio,
desligou, encerrou com motivo), para saber exatamente o que aconteceu com cada
número.

#### AC-069 — Timeline registrada por lead

- **Dado** uma campanha em execução com leads
- **Quando** uma chamada percorre seu ciclo (placed → preaccepted → accepted →
  answered → audio_started → audio_finished → hangup_scheduled → ended)
- **Então** cada transição é gravada como um evento ordenado e datado,
  vinculado ao lead e ao call_id (tabela `sp_call_events`)

#### AC-070 — Relatório exibe a timeline

- **Dado** um lead que já passou por chamada
- **Quando** o operador abre a tela de resultados da campanha
- **Então** ele vê, para esse lead, a sequência de etapas com horário, plataforma
  e motivo (quando houver)

### US-027 — Classificação rica do desfecho da chamada

Como operador, quero que cada desfecho tenha uma **classificação precisa** —
atendeu (mobile), atendeu (web), não atendeu (tocou até desligar), foi desligado
durante o áudio, ouviu até o final, erro do servidor — para decidir a próxima
ação sem adivinhar.

#### AC-071 — Plataforma do atendimento capturada

- **Dado** uma chamada atendida
- **Quando** o destinatário atende
- **Então** a plataforma (`mobile` ou `web`, via `RemotePlatform` do WhatsApp) é
  registrada no lead e no evento `accepted`

#### AC-072 — Distinção entre "tocou até desligar" e "foi desligado"

- **Dado** uma chamada que tocou mas não completou o áudio
- **Quando** ela encerra
- **Então** o sistema grava se o desligamento foi por **timeout de toque** (não
  atendeu), por **desligamento do destinatário** durante o áudio, ou por
  **hangup automático** após o fim do áudio

#### AC-073 — "Ouviu até o final" registrado

- **Dado** uma chamada atendida com áudio
- **Quando** o player atinge o fim do arquivo (OnFinish)
- **Então** o lead é marcado como tendo ouvido o áudio completo

#### AC-074 — Erros reportados pelo servidor registrados

- **Dado** uma chamada rejeitada pelo servidor (ex.: `463`, `403`) ou que falhou
  no estabelecimento
- **Quando** o gateway recebe o erro/reject/timeout
- **Então** o motivo é gravado no lead e no evento `ended` com o texto do erro

### US-028 — Ring timeout no gateway

Como operador, quero que uma chamada que fica tocando sem resposta seja
encerrada automaticamente, para não prender a fila nem deixar chamadas
fantasmas.

#### AC-075 — Encerramento por timeout de toque

- **Dado** uma chamada em `ringing` (sem `accepted`/`ended`) por mais de
  `timeout_ring` segundos
- **Quando** o tempo limite expira
- **Então** o gateway encerra a chamada com motivo `ring_timeout`, dispara o
  evento `ended` e a remove do registro de chamadas ativas

#### AC-076 — Cancelamento explícito no timeout do worker

- **Dado** o worker que aguarda o resultado de uma chamada
- **Quando** o tempo limite de espera expira
- **Então** o worker chama `POST /call/cancel` antes de marcar o lead como
  falha, garantindo que não reste chamada ativa no gateway

### US-029 — Modos de disparo funcionando plenamente

Como operador, quero que **fila**, **simultâneo** e **alternado** disparem e
reconciliem corretamente, para que nenhum lead fique preso e os contadores
sempre fechem.

#### AC-077 — Reconciliação assíncrona uniforme

- **Dado** uma campanha em qualquer modo (fila, simultâneo, alternado)
- **Quando** há leads em `ringing`
- **Então** a cada ciclo o worker consulta o status de cada chamada ativa
  (não-bloqueante) e grava o desfecho no lead e nos contadores da campanha

#### AC-078 — Modo simultâneo fecha os contadores

- **Dado** uma campanha `simultaneo` com N chamadas em paralelo
- **Quando** todas as chamadas encerram
- **Então** todos os leads deixam o estado `ringing` e os contadores
  (answered/no_answer/busy/failed) refletem o total de leads

#### AC-079 — Modo alternado alterna as instâncias

- **Dado** uma campanha `alternado` com 2+ instâncias
- **Quando** as chamadas são disparadas sequencialmente
- **Então** cada chamada usa uma instância diferente, em rodízio

### US-030 — Persistência e recompilação do gateway

Como engenheiro, quero que a nova capacidade (timeline, ring timeout, plataforma)
seja **versionada e recompilável** no gateway, respeitando o fluxo de deploy
(P-003/P-004) sem quebrar a API existente.

#### AC-080 — API de status retrocompatível

- **Dado** o endpoint `GET /call/status` já existente
- **Quando** a resposta passa a incluir a timeline e campos novos
- **Então** os campos antigos (`status`, `reason`, `started_at`, etc.) continuam
  presentes, sem quebrar consumidores atuais

#### AC-081 — Plataforma capturada pelo gateway

- **Dado** o client whatsmeow conectado à instância
- **Quando** o evento `CallAccept` chega (com `RemotePlatform`)
- **Então** o gateway associa a plataforma do peer à chamada ativa, sem
  depender de modificação no fork meowcaller (captura via handler no gateway)

#### AC-082 — Migração cria a tabela de eventos

- **Dado** um banco sem `sp_call_events`
- **Quando** a migração da feature roda
- **Então** a tabela `sp_call_events` e as novas colunas de `sp_call_leads`
  passam a existir

## Fora de escopo

- Gravação do áudio de **resposta** do lead (`record_response`) — spec separada.
- Reconexão/revalidação de instâncias WhatsApp.
- Alteração dos modos de agendamento (janela/horários/feriados).
- Regras de retry automático de leads que falharam.

## Suposições

| ID | Suposição | Status | Resolução |
|---|---|---|---|
| ASM-029 | `RemotePlatform` segue os valores `smbi`/`smba` (mobile) e `web` (WhatsApp Web/Desktop) — a normalização será `mobile` para qualquer valor `sm*` e `web` para o restante | aberta | — |
| ASM-030 | O gateway **não** escreve no MySQL de negócio: a timeline é acumulada em memória e devolvida no `/call/status`; o worker PHP persiste em `sp_call_events`. (Plano B: gateway escreve via `bulk.DB()`.) | aberta | — |
| ASM-031 | O ring timeout usa `timeout_ring` da campanha; chamadas sem campanha associada usam um default de 30s | aberta | — |
| ASM-032 | A reconciliação assíncrona roda no próprio worker (PHP), que já persiste via MySQL — não é preciso um novo processo | aberta | — |

## Perguntas em aberto

| ID | Pergunta | Status | Resposta |
|---|---|---|---|
| Q-019 | O relatório deve permitir exportar a timeline (CSV/PDF) ou apenas exibir na tela? | aberta | — |
| Q-020 | Chamadas que falharam por "número sem WhatsApp" devem ser automaticamente marcadas como inválidas e removidas de novas tentativas? | aberta | — |
