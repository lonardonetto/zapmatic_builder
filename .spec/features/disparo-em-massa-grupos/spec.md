# Spec: Disparo em massa para grupos

> feature: disparo-em-massa-grupos
> status: em-implementacao

## Contexto

Hoje o disparo em massa (`Whatsapp_bulk`) só envia **mensagens 1 a 1** para
números de uma lista de contatos (`sp_whatsapp_phone_numbers`). O motor Go
(`app_zapmatic_whatsmeow_api/internal/bulk/processor.go`) lê o próximo número
via `GetNextPhone` (offset persistente `sent+failed`) e envia com
`sender.SendText`, que já aceita **qualquer JID** — inclusive `@g.us`.

O usuário quer **disparar dentro dos grupos** (mensagem publicada no grupo,
visível a todos os membros), selecionando os grupos das contas conectadas —
sem precisar coletar números e montar lista de contatos manualmente. Isso é
diferente de "coletar membros": aqui o **destino é o grupo**, não cada número.

A infraestrutura de delay, agendamento, feriados, timezone, spintax e rotador
de contas já existe e deve ser **reaproveitada** (mesmo `CalculateDelay`, mesmo
`scheduler`, mesmo `resolveBestInstance`).

## Histórias

### US-010 — Selecionar grupos como destino da campanha

Como usuário do módulo de disparo em massa, quero selecionar grupos do WhatsApp
(de uma ou mais contas) como destino da campanha, para que a mensagem seja
publicada dentro dos grupos sem eu coletar números manualmente. O destino
(grupos vs lista de contatos) é independente do tipo de mensagem (texto/mídia,
botão, lista, carrossel, enquete).

#### AC-024 — Destinos de grupo são normalizados e deduplicados

- **Dado** uma lista de destinos `{account_id, group_jid}` com repetições e JIDs vazios
- **Quando** os destinos de grupo são normalizados
- **Então** cada par `account_id + group_jid` aparece uma única vez
- **E** entradas com `group_jid` vazio são descartadas

#### AC-025 — JID de grupo é montado com sufixo `@g.us`

- **Dado** um id de grupo sem sufixo (`123456789`) e outro já com `@`
- **Quando** o JID de grupo é montado
- **Então** o primeiro vira `123456789@g.us` e o segundo é preservado como está

#### AC-026 — Envio em grupo resolve o destino como grupo, não como número

- **Dado** um destino de grupo com `group_jid` válido
- **Quando** o chat de destino é resolvido para envio
- **Então** o chat é o JID do grupo (`@g.us`), nunca `@s.whatsapp.net`

#### AC-032 — Destino é independente do tipo de mensagem

- **Dado** uma campanha com `target_type` `groups` e `type` 1 (texto)
- **Quando** a campanha é classificada como "de grupo"
- **Então** ela é grupo porque `target_type = groups`, independentemente do `type` (texto/botão/lista/carrossel/enquete)
- **E** uma campanha com `target_type` `contacts` continua sendo de contatos

### US-011 — Validação e isolamento multi-tenant dos grupos

Como operador do sistema, quero que o disparo em grupo seja restrito a contas
Go e escopado por `team_id`, para que nenhum usuário dispare em grupo de outro
time e o caminho legado continue intacto.

#### AC-027 — Envio em grupo é recusado para conta que não é Go (login_type != 3)

- **Dado** uma conta com `login_type` 1 ou 2 (legado/Cloud/Baileys)
- **Quando** a campanha de grupo é validada
- **Então** a campanha é recusada com aviso (não envia em grupo)

#### AC-028 — Destinos de grupo são escopados por team_id

- **Dado** um `team_id` válido e uma lista de destinos de grupo
- **Quando** o escopo dos destinos é montado
- **Então** `team_id` é injetado; `team_id` vazio é rejeitado

### US-012 — Reuso do motor de disparo (delay, agendamento)

Como usuário do módulo, quero que o disparo em grupo respeite delay,
agendamento e feriados exatamente como o disparo 1 a 1, para que o
comportamento seja consistente e não haja lógica duplicada.

#### AC-029 — Delay entre envios de grupo usa o mesmo cálculo do disparo 1 a 1

- **Dado** `min_delay` e `max_delay` de uma campanha
- **Quando** o próximo envio de grupo é agendado
- **Então** o intervalo é calculado pela mesma função de delay (`CalculateDelay`)

#### AC-030 — Offset persistente avança pelos grupos com `sent+failed`

- **Dado** uma campanha de grupo com `sent+failed = N` e uma lista de destinos
- **Quando** o próximo grupo é buscado
- **Então** é retornado o grupo na posição `N`; se `N` esgotou a lista, retorna vazio

#### AC-031 — Grupo é enviado pela conta que é membro dele

- **Dado** um destino de grupo com `account_id` da conta dona
- **Quando** a instância de envio é resolvida
- **Então** é usada a conta dona do grupo (`account_id`), nunca o rotador cego da campanha

## Fora de escopo

- Enviar dentro de grupos via Cloud API ou Baileys (primeiro ciclo só Go/Whatsmeow,
  `login_type=3` — a Cloud API usa `group_id` nativo e exigiria contrato próprio).
- Coletar membros e enviar individualmente (é o fluxo atual de lista de contatos,
  que continua existindo).
- Grupos de anúncio (`announce`) e comunidades (`isCommunity`): a conta pode não
  ter permissão de postar; o envio falhará e será registrado como falha controlada.
- Programar envio recorrente por grupo (fora do escopo inicial).

## Suposições

| ID | Suposição | Status | Resolução |
|---|---|---|---|
| ASM-009 | O envio dentro de grupo funciona no whatsmeow com o mesmo `SendText`/`SendMedia` de 1 a 1, apenas trocando o JID para `@g.us` | aberta | confirmar com teste manual de envio em grupo (produção) |
| ASM-010 | Os destinos de grupo são persistidos em uma tabela dedicada (`sp_whatsapp_schedule_groups`) para não alterar o fluxo de lista de contatos | confirmada | migration própria `009_*` + `save_schedule_groups()` |
| ASM-011 | O rotador de contas e o offset persistente servem para grupos sem mudança de contrato (mesmo padrão `sent+failed`) | confirmada | `NextGroupByOffset`/`ListScheduleGroups` no Go, espelhando `GetNextPhone` |

## Perguntas em aberto

| ID | Pergunta | Status | Resposta |
|---|---|---|---|
| Q-005 | Deve ser possível misturar grupos e números (lista de contatos) na mesma campanha? | respondida | Não — cada campanha é OU grupos OU lista de contatos |
| Q-006 | Deve haver limite de grupos por campanha (ex.: 50)? | respondida | Sem limite fixo |
| Q-007 | Grupos de anúncio/comunidade devem ser escondidos do seletor ou apenas falhar no envio? | respondida | Falhar com aviso (mostra no seletor; envio vira falha controlada no relatório) |
