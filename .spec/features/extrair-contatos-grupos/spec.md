# Spec: Extrair contatos de grupos

> feature: extrair-contatos-grupos
> status: implementada

## Contexto

O módulo `Whatsapp_export_participants` (extração de contatos de grupos do
WhatsApp) carrega todos os grupos de uma conta de uma só vez e processa a
criação de lista de contatos de forma síncrona. Em contas com muitos grupos,
isso estoura timeout e dispara bloqueio 429 do WhatsApp, além de travar a
página. A extração também não permite excluir o próprio número nem os admins,
e não aproveita nomes de participantes que o gateway já retorne.

## Histórias

### US-001 — Paginação da listagem de grupos

Como usuário do módulo, quero que a lista de grupos seja paginada no gateway
Go e no PHP, para que contas com muitos grupos carreguem sem timeout e sem
bloqueio por excesso de acessos (429).

#### AC-001 — Paginador devolve a fatia correta e o total

- **Dado** uma lista de 10 grupos e `page=2`, `limit=4`
- **Quando** o paginador é chamado
- **Então** devolve os grupos 5 a 8 (índices 4 a 7) e `total=10`

#### AC-002 — Página inválida devolve lista vazia, sem erro

- **Dado** uma lista de 10 grupos e `page=0` (ou negativa, ou além do fim)
- **Quando** o paginador é chamado
- **Então** devolve lista vazia e nunca lança erro

#### AC-003 — Limite inválido usa o padrão de 50

- **Dado** `limit=0` (ou negativo)
- **Quando** o paginador é chamado
- **Então** usa `limit=50` como padrão

#### AC-004 — Gateway Go pagina com page/limit/total

- **Dado** o handler Go de listagem de grupos com `page=1` e `limit=50`
- **Quando** a requisição é feita
- **Então** a resposta contém somente a página pedida e o campo `total` de grupos

#### AC-013 — Sem `page`, o gateway devolve todos os grupos (legado)

- **Dado** o handler Go de listagem de grupos chamado sem o parâmetro `page`
- **Quando** a requisição é feita
- **Então** a resposta devolve todos os grupos (compatibilidade retroativa com os chamadores atuais)

### US-002 — Criação de lista de contatos em fila (background)

Como usuário do módulo, quero que a criação da lista de contatos seja
enfileirada e processada em background, para que a página não trave em grupos
grandes e o progresso seja visível.

#### AC-005 — Job nasce pendente com payload e progresso zero

- **Dado** `team_id`, `account_id` e `group_id` de um pedido
- **Quando** um job é criado
- **Então** ele tem status pendente, `total>0`, `done=0` e payload contendo os três ids

#### AC-006 — Worker recusa job de outro time

- **Dado** um job com `team_id=7` e um worker atuando no `team_id=9`
- **Quando** o worker tenta processar
- **Então** o job é rejeitado (não é processado)

#### AC-007 — Progresso é a razão entre concluídos e total

- **Dado** um job com `total=100` e `done=40`
- **Quando** o progresso é calculado
- **Então** resulta em `0.40` (40%)

### US-003 — Filtros de participantes (próprio número e admins)

Como usuário do módulo, quero excluir opcionalmente o próprio número e os
admins do grupo, para que a lista final contenha apenas os destinatários
desejados.

#### AC-008 — Exclui o número da própria conta

- **Dado** participantes com o próprio número entre eles e `exclude_self=true`
- **Quando** o filtro é aplicado
- **Então** o próprio número é removido do resultado

#### AC-009 — Exclui admins quando a opção está ativa

- **Dado** participantes com admins marcados e `exclude_admins=true`
- **Quando** o filtro é aplicado
- **Então** os admins são removidos do resultado

#### AC-010 — Deduplicação mantém a primeira ocorrência

- **Dado** participantes com números repetidos
- **Quando** a extração é executada
- **Então** cada número aparece uma única vez (a primeira ocorrência é mantida)

### US-004 — Segurança multi-tenant no escopo da conta

Como operador do sistema, quero que toda consulta de conta do módulo seja
escopada por `team_id`, para que nenhum usuário acesse conta de outro time.

#### AC-011 — Escopo de conta sempre injeta team_id válido

- **Dado** um `team_id` válido e filtros de conta
- **Quando** o escopo é montado
- **Então** `team_id` é injetado nos filtros; `team_id` vazio é rejeitado

### US-005 — Aproveitar nome do participante quando existir

Como usuário do módulo, quero que o nome do participante seja aproveitado
quando o gateway já o retornar, para que a lista de contatos não tenha apenas
números.

#### AC-012 — Nome presente é persistido; ausente vira nulo sem erro

- **Dado** um participante com campo `name` e outro sem
- **Quando** a extração é executada
- **Então** o primeiro tem nome registrado e o segundo tem nome nulo, sem erro

## Fora de escopo

- Verificação ativa de número no WhatsApp (o validador em background do sistema
  já faz isso; aqui marcamos pendente/inválido pela estrutura).
- Buscar push-name de participantes no gateway Go (exigiria mudança de
  contrato no handler e recompilação — decidido NÃO fazer nesta feature).
- Paginação no caminho Node/Baileys legado (login_type 1/2); a paginação vale
  para o caminho Go (login_type 3), que é o atual.

## Suposições

| ID | Suposição | Status | Resolução |
|---|---|---|---|
| ASM-001 | O caminho principal de grupos é o Go (login_type=3); o Node legado não precisa de paginação nesta feature | confirmada | usuário escolheu "Go /groups/list + PHP" |
| ASM-002 | A tabela de fila (`sp_export_participants_queue`) é nova e não conflita com `sp_message_queue`/`sp_campaign_queue` | confirmada | migration própria `007_*` |
| ASM-003 | O cron é registrado via `cron` no Config.php (mesmo mecanismo do módulo Payment) e disparado externamente (systemd/crontab do servidor) | confirmada | padrão observado em `inc/plugins/Payment/Config.php` |
| ASM-004 | A execução real da fila exige o banco de produção; os testes provam a lógica pura (fábrica do job, tenant check, progresso) sem depender de MySQL local | confirmada | ambiente não tem MySQL nos testes |

## Perguntas em aberto

| ID | Pergunta | Status | Resposta |
|---|---|---|---|
| Q-001 | O processamento do job em background deve ter limite de lotes (ex.: 200 por execução)? | respondida | Sim, lote de 200 por execução do cron, com `attempts` e `max_attempts=3` |
| Q-002 | O CSV exportado deve sair só com números válidos? | respondida | Não nesta feature — o CSV mantém o comportamento atual (válido+inválido marcados); filtro de self/admins é o único novo filtro |
