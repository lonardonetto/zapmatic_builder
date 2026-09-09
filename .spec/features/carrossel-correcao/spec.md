# Spec: Carrossel correcao

> feature: carrossel-correcao
> status: implementada

## Contexto

O modulo de template de carrossel (`Whatsapp_carousel_template`, type=5) tinha
dois defeitos: (1) templates de carrossel nao apareciam na aba correta apos
salvamento e (2) apareciam dentro da aba de Botoes. A **causa raiz** era
dupla:

1. **Auto-routing do CodeIgniter nao resolvia o controller do carrossel** —
   o `setDefaultNamespace()` nas Routes.php de cada modulo e um setting global
   que podia ser sobrescrito por modulos carregados depois em ordem alfabetica.
   Quando o formulario de carrossel fazia POST para
   `/whatsapp_carousel_template/save/xxx`, o framework chamava o `save()` do
   controller de Botoes (type=2) em vez do controller de Carrossel (type=5).

2. **Widget do carrossel ausente em 3 paginas de envio** — o `widget_menu` e
   `widget_content` do carrossel nao eram chamados em `Whatsapp_callresponder`,
   `Whatsapp_api` e `Criptografia_copy`, fazendo o Bootstrap nao encontrar o
   tab-pane `#wa_carousel` e o conteudo "vazar" para `#wa_button`.

As correcoes aplicadas foram: (a) rotas explicitas em `app/Config/Routes.php`
(NUCLEAR OPTION) e (b) adicao dos widgets nas 3 paginas faltantes.

## Arquitetura (pos-correcao)

| Componente | Caminho | O que muda |
|---|---|---|
| Rotas explicitas | `app/Config/Routes.php` L48-54 | 6 rotas `$routes->add()` para carousel |
| Widget callresponder | `inc/core/Whatsapp_callresponder/Views/info.php` L64,82 | +carousel menu e content |
| Widget api | `inc/core/Whatsapp_api/Views/info.php` L54-55,71-72 | +poll +carousel menu e content |
| Widget criptografia | `inc/core/Criptografia_copy/Views/info.php` L54-55,71-72 | +poll +carousel menu e content |

## Historias

### US-036 — Carrossel salva com type=5 e aparece na aba correta

Como administrador do sistema, quero que templates de carrossel sejam salvos
com type=5 e aparecam exclusivamente na aba "Carousel".

#### AC-095 — Template de carrossel aparece na listagem apos salvar

- **Dado** que estou logado como administrador e acesso `/whatsapp_carousel_template/index/update`
- **Quando** preencho nome "Teste Auto-Rota", adiciono 2 cards com titulo e corpo, e submeto
- **Entao** o template e salvo com `type=5` no banco de dados e aparece na listagem de carrossel

#### AC-096 — Template de carrossel NAO aparece na aba de Botoes

- **Dado** que criei um template de carrossel chamado "Teste Auto-Rota"
- **Quando** acesso `/whatsapp_button_template`
- **Entao** o template "Teste Auto-Rota" NAO aparece na listagem de botoes (type=2)

#### AC-097 — Rota explicita direciona save para controller correto

- **Dado** que o formulario de carrossel gera action URL `/whatsapp_carousel_template/save/`
- **Quando** o POST e enviado
- **Entao** o framework executa `Whatsapp_carousel_template::save()` e nao `Whatsapp_button_template::save()`

#### AC-105 — Template existente com dados de carrossel mas type=2 e corrigido

- **Dado** que existe um template com `"cards":` no JSON mas `type=2` no banco
- **Quando** o script de migracao roda
- **Entao** o type e corrigido para 5 e o template aparece na aba de carrossel

### US-037 — Carrossel edita corretamente pelo formulario proprio

#### AC-098 — Edicao abre formulario de carrossel com cards pre-preenchidos

- **Dado** que existe um template de carrossel com 3 cards no banco
- **Quando** clico em editar
- **Entao** o formulario de carrossel abre com os 3 cards preenchidos

#### AC-099 — Edicao preserva botoes dos cards

- **Dado** que um template tem 2 cards com botoes variados (quick_reply, cta_url)
- **Quando** abro edicao
- **Entao** os botoes estao com tipo, texto e URL corretos

### US-038 — Listagem AJAX do carrossel e independente

#### AC-100 — Endpoint ajax_list retorna somente type=5

- **Dado** que existem templates de carrossel (type=5) e botoes (type=2) no banco
- **Quando** faco GET para `/whatsapp_carousel_template/ajax_list`
- **Entao** a resposta contem apenas templates type=5

#### AC-101 — Paginacao do carrossel nao interfere na de botoes

- **Dado** que existem 35+ templates de cada tipo
- **Quando** navego pagina 2 do carrossel
- **Entao** botoes permanecem na pagina 1

### US-039 — Bot Builder funciona para carrossel

#### AC-102 — native_templates retorna type=5 para nodes cards

- **Dado** que existem templates de carrossel e um node "cards" no Bot Builder
- **Quando** o seletor carrega
- **Entao** apenas templates type=5 aparecem no dropdown

#### AC-103 — Criar novo pelo Bot Builder abre formulario correto

- **Dado** que estou no Bot Builder com node "cards"
- **Quando** clico "Criar novo"
- **Entao** sou redirecionado para `/whatsapp_carousel_template/index/update`

#### AC-104 — Preview mostra cards e nao botoes

- **Dado** que um template de carrossel esta selecionado no Bot Builder
- **Quando** o renderNativeTemplatePreview(type=5) executa
- **Entao** mostra titulo, contagem de cards e linhas de cada card

### US-040 — Widgets de carrossel presentes em todas as paginas de envio

#### AC-106 — Callresponder tem aba de carrossel

- **Dado** que estou na pagina de criacao de chatbot (callresponder)
- **Quando** a pagina carrega
- **Entao** a aba "Carousel" aparece com `widget_menu` e `widget_content` do carrossel

#### AC-107 — API REST tem aba de carrossel

- **Dado** que estou na pagina de configuracao de API REST
- **Quando** a pagina carrega
- **Entao** as abas "Enquete" e "Carousel" aparecem com seus respectivos widgets

#### AC-108 — Criptografia tem aba de carrossel

- **Dado** que estou na pagina de criptografia de textos
- **Quando** a pagina carrega
- **Entao** as abas "Enquete" e "Carousel" aparecem com seus respectivos widgets

## Fora de escopo

- Correcao dos modulos de lista (type=1) e enquete (type=3)
- Alteracoes no gateway Go ou envio de mensagens
- Templates oficiais Meta (type=66)
- Alteracoes de layout/CSS

## Suposicoes

| ID | Suposicao | Status | Resolucao |
|---|---|---|---|
| ASM-201 | O auto-routing do CodeIgniter nao resolvia o namespace corretamente para modulos com `menu.sub_menu` | confirmada | Rotas explicitas adicionadas |
| ASM-202 | O `setDefaultNamespace()` e um setting global sobrescrito por modulos seguintes | confirmada | Documentado na RFC |
| ASM-203 | Template id=19 "ELITEZAP" no Astros tinha dados de carrossel mas type=2 | confirmada | Corrigido manualmente no banco |
| ASM-204 | O widget do carrossel funcionava em Whatsapp_bulk e Whatsapp_send_message mas nao nas outras paginas | confirmada | Widgets adicionados em 3 paginas |

## Perguntas em aberto

| ID | Pergunta | Status | Resposta |
|---|---|---|---|
| Q-201 | Existe commit antigo onde funcionava? | respondida | SIM — o codigo do carrossel NUNCA mudou; o bug era no routing |
| Q-202 | Bug na pagina standalone ou Bot Builder? | respondida | Na pagina standalone de criacao |
| Q-203 | Permissao habilitada? | respondida | SIM, permissao completa concedida |
