# Spec: Clonar grupo

> feature: clonar-grupo
> status: implementada

## Contexto

O usuário participa de vários grupos do WhatsApp. No módulo de extração de
contatos (`Whatsapp_export_participants`) ele já consegue extrair os leads de um
grupo. Agora ele quer **clonar um grupo**: criar um grupo NOVO no WhatsApp com os
MESMOS leads do grupo de origem, de uma vez, sem trabalho manual.

Regras do usuário (visão confirmada):
- O nome do grupo novo usa o mesmo nome do original + " - cópia", mas é
  **editável na hora**.
- O **próprio número** da conta conectada fica de fora do clone (ele vira admin
  do grupo novo por ser o criador). Os demais participantes — incluindo os
  admins do grupo de origem — são mantidos, para que qualquer grupo seja clonável.
- O clone roda em **fila (background) com progresso**, para grupos grandes não
  travarem nem tomarem bloqueio do WhatsApp.
- Vale **só para contas Go** (login_type=3), que é o caminho atual. Contas
  legadas (Node/Baileys, login_type 1/2) não mostram o clone.
- Tudo de forma **isolada**, sem quebrar a extração de contatos que já existe.

## Histórias

### US-006 — Clonar um grupo a partir de um grupo existente

Como usuário do módulo, quero clicar em "Clonar grupo" e criar um grupo novo com
os mesmos leads do grupo de origem (menos o meu próprio número, que vira admin
do grupo novo), para que eu replique um grupo sem adicionar pessoas uma a uma.

#### AC-014 — Nome do clone é "X - cópia" e é truncado a 25 caracteres

- **Dado** um grupo de origem chamado "Vendas" e o nome destino em branco
- **Quando** o nome do clone é montado
- **Então** o nome sugerido é "Vendas - cópia"
- **E** nomes com mais de 25 caracteres são truncados para 25 (limite do WhatsApp)

#### AC-015 — Admins do grupo de origem são mantidos no clone

- **Dado** participantes com membros marcados como admin
- **Quando** o clone é montado removendo apenas o próprio número
- **Então** os admins do grupo de origem permanecem na lista de participantes do clone

#### AC-016 — O próprio número fica de fora e não é duplicado

- **Dado** participantes que incluem o número da conta conectada e números repetidos
- **Quando** o clone é montado excluindo o próprio número
- **Então** o próprio número não aparece na lista do clone
- **E** cada número aparece uma única vez (sem duplicados)

### US-007 — Clone em fila com progresso

Como usuário do módulo, quero que o clone seja enfileirado e processado em
background com progresso visível, para que grupos grandes sejam clonados sem
travar a página e sem bloqueio por excesso de acessos.

#### AC-017 — Job nasce pendente com payload completo e progresso zero

- **Dado** `team_id`, `account_id`, `group_id` e `target_name` de um clone
- **Quando** um job de clone é criado
- **Então** ele tem status pendente, `total>0`, `done=0`, `progress=0` e payload
  com o nome destino e os ids

#### AC-018 — Participantes são divididos em lotes de no máximo 50

- **Dado** 120 participantes a adicionar e lote máximo de 50
- **Quando** os participantes são fatiados em lotes
- **Então** resultam 3 lotes (50 + 50 + 20) e nenhum lote passa de 50

#### AC-019 — Progresso é a razão entre concluídos e total

- **Dado** um job com `total=100` e `done=40`
- **Quando** o progresso é calculado
- **Então** resulta em `0.40` (40%) e nunca passa de `1.0`

### US-008 — Isolamento e segurança multi-tenant

Como operador do sistema, quero que o clone funcione só para contas Go e sempre
escopado por `team_id`, para que nenhum usuário clone conta de outro time e o
caminho legado continue intacto.

#### AC-020 — Clone é recusado para conta que não é Go (login_type != 3)

- **Dado** uma conta com `login_type` 1 ou 2 (legado)
- **Quando** o clone é solicitado
- **Então** o clone é recusado com aviso (não cria job nem grupo)

#### AC-021 — Escopo de conta sempre injeta team_id válido

- **Dado** um `team_id` válido e filtros de conta
- **Quando** o escopo é montado
- **Então** `team_id` é injetado nos filtros; `team_id` vazio é rejeitado

### US-009 — Rota Go para criar grupo e adicionar participantes

Como integração, o gateway Go precisa expor endpoints para criar um grupo com um
nome e adicionar participantes em lotes, para que o PHP consiga clonar sem
acessar o WhatsApp diretamente.

#### AC-022 — Criação de grupo trunca o nome a 25 caracteres

- **Dado** um nome com mais de 25 caracteres enviado ao gateway
- **Quando** o gateway monta a criação do grupo
- **Então** o nome enviado ao WhatsApp tem no máximo 25 caracteres

#### AC-023 — Adição de participantes é fatiada em lotes

- **Dado** uma lista de JIDs maior que o lote máximo
- **Quando** o gateway prepara a adição de participantes
- **Então** a lista é dividida em lotes e nenhum lote excede o máximo

## Fora de escopo

- Criar lista de contatos junto com o clone (a criação de lista já existe como
  ação separada; o clone só cria o grupo no WhatsApp).
- Enviar mensagem de boas-vindas automática dentro do grupo novo.
- Clonar comunidades (isCommunity) ou grupos de anúncio — só grupos normais.
- Suporte a contas Node/Baileys legadas (login_type 1/2).
- Remover/adicionar participantes após o clone (isso é o WhatsApp nativo).

## Suposições

| ID | Suposição | Status | Resolução |
|---|---|---|---|
| ASM-005 | A conta conectada vira admin do grupo novo automaticamente por ser a criadora (o `CreateGroup` do WhatsApp adiciona o criador como admin implícito) | confirmada | comportamento do WhatsApp/whatsmeow |
| ASM-006 | O limite seguro de adição é 50 participantes por chamada ao WhatsApp (lotes maiores arriscam bloqueio/rate limit) | confirmada | constante configurável no Go e no PHP |
| ASM-007 | O clone usa uma tabela de fila própria (`sp_clone_group_queue`) para não alterar a fila de contatos existente | confirmada | migration própria `008_*` |
| ASM-008 | O botão "Clonar grupo" aparece apenas para contas Go; contas legadas ocultam o botão | confirmada | filtro por login_type=3 na view |

## Perguntas em aberto

| ID | Pergunta | Status | Resposta |
|---|---|---|---|
| Q-003 | Qual o tamanho máximo de cada lote de adição de participantes? | respondida | 50 por chamada (constante, ajustável) |
| Q-004 | O grupo novo deve receber alguma mensagem automática de boas-vindas após o clone? | respondida | Não nesta feature (fora de escopo) |
