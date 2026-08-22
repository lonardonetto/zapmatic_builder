# Spec: Duplicação de Campanhas de Bulk Continuada e Substituição de Variáveis de Planilha

> feature: bulk-duplicar-e-variaveis
> status: concluido

## Contexto

Ao duplicar uma campanha de disparo em massa (`Whatsapp_bulk::duplicate`), o sistema zerava os contadores `sent = 0` e `failed = 0`. Como o motor de disparo Go (`app_zapmatic_whatsmeow_api/internal/bulk/`) utiliza o **offset persistente** (`offset = sent + failed`) para determinar a posição atual da lista de contatos/grupos, a campanha duplicada nascia com `offset = 0` e recomeçava do primeiro contato (do zero).

Além disso, mensagens com variáveis/palavras-chave vindas de planilhas Excel/CSV (ex.: `{nome}`, `{var1}`, `{v1}`, `{cidade}`) não estavam sendo substituídas corretamente pelas seguintes razões no motor Go (`spintax.go`):
1. A expressão regular do Spintax (`spintaxRe = regexp.MustCompile(\`\{([^{}]*?)\}\`)`) capturava qualquer bloco entre chaves `{...}`, mesmo sem o separador `|` de opções Spintax, destruindo marcadores como `{nome}` ao remover as chaves antes da substituição de parâmetros.
2. A substituição de parâmetros (`ReplaceParams`) só buscava a sintaxe `%variavel%`, ignorando as sintaxes de chaves `{variavel}` e colchetes `[variavel]`.
3. Mapeamentos de chaves com maiúsculas/minúsculas no JSON de parâmetros falhavam por busca estrita de case.
4. Para campanhas de grupo (`target_type = 'groups'`), a duplicação não copiava os registros da tabela `sp_whatsapp_schedule_groups`, fazendo a campanha duplicada nascer sem alvos.

Esta especificação corrige a duplicação para **continuar de onde parou** (preservando o offset), copia os grupos selecionados e garante a substituição completa de variáveis da planilha em todos os formatos suportados (`{var}`, `%var%`, `[var]`).

## Histórias

### US-031 — Duplicação de campanha continuando de onde parou

Como operador de disparo em massa, quero duplicar uma campanha interrompida ou concluída mantendo o progresso dos contadores (`sent` e `failed`), para que a nova campanha continue exatamente do ponto onde a anterior parou, sem re-enviar para quem já recebeu.

#### AC-083 — Preservação dos contadores ao duplicar

- **Dado** uma campanha original com `sent = 150` e `failed = 50`
- **Quando** o operador clica em "Duplicar"
- **Então** a nova campanha duplicada é criada com `sent = 150` e `failed = 50` (preservando os contadores originais)
- **E** quando o motor Go inicia a nova campanha, calcula `offset = 150 + 50 = 200` e dispara a partir do contato #200 da lista

#### AC-084 — Copiar alvos de grupo ao duplicar campanha de grupos

- **Dado** uma campanha de grupo (`target_type = 'groups'`) com registros na tabela `sp_whatsapp_schedule_groups`
- **Quando** a campanha é duplicada
- **Então** os registros de grupos da campanha original são copiados para a nova campanha com o novo `schedule_id`

### US-032 — Substituição de variáveis da planilha na mensagem

Como operador de disparo em massa, quero usar variáveis da planilha (como `{nome}`, `{var1}`, `{v1}`, `{cidade}`, `%nome%`, `[nome]`) na mensagem da campanha, para que cada contato receba o texto personalizado com seus dados.

#### AC-085 — Preservação de variáveis em chaves sem Spintax

- **Dado** um texto de mensagem contendo variáveis em chaves sem pipe, como `"Olá {nome}, seu pedido é {pedido}"`
- **Quando** o motor processa o Spintax (`ExpandSpintax`)
- **Então** o Spintax ignora blocos sem `|` (mantendo `{nome}` e `{pedido}` intactos para a substituição de parâmetros)
- **E** blocos Spintax reais com pipe (ex.: `{Olá|Oi|Bom dia}`) continuam sendo sorteados normalmente

#### AC-086 — Substituição de parâmetros em múltiplos formatos

- **Dado** um contato com o parâmetro `{"nome": "Maria", "var1": "1234"}`
- **Quando** o motor substitui os parâmetros na mensagem
- **Então** aceita as sintaxes `{nome}`, `%nome%`, `[nome]`, `{var1}`, `%var1%`, `[var1]`, `{v1}`, `%v1%`, `[v1]` de forma insensível a maiúsculas/minúsculas (`{Nome}`, `{NOME}`)

#### AC-087 — Fallback limpo para variáveis não preenchidas

- **Dado** um contato cujo parâmetro não existe no mapa
- **Quando** a mensagem é montada
- **Então** se o parâmetro não for encontrado e não houver valor, a variável é removida limpadamente ou mantida intacta sem corromper o texto

## Fora de escopo

- Alteração da sintaxe de agrupamento ou paginação no envio de mídia.
- Mudança na regra de delay ou limite de envio por conta.
- Alteração nos relatórios de disparo já existentes.

## Suposições

| ID | Suposição | Status | Resolução |
|---|---|---|---|
| ASM-033 | Ao duplicar uma campanha, o comportamento padrão desejado pelo usuário é **continuar de onde parou** (preservando `sent` e `failed`), pois para recomeçar do zero o usuário cria uma nova campanha | aberta | — |
| ASM-034 | O Spintax legítimo sempre contém pelo menos um caractere pipe de opção dentro das chaves (exemplo: a ou b entre chaves) | aberta | — |
| ASM-035 | As chaves dos parâmetros no JSON `params` podem vir em maiúsculas ou minúsculas dependendo do cabeçalho da planilha CSV/Excel importada | aberta | — |

## Perguntas em aberto

| ID | Pergunta | Status | Resposta |
|---|---|---|---|
| Q-021 | Deve existir uma opção explícita na UI de "Duplicar (Zerar Progresso)" além de "Duplicar (Continuar)", ou a ação atual de "Duplicar" deve ser ajustada para continuar? | aberta | — |
