# Spec: Carrossel correcao

> feature: carrossel-correcao
> status: rascunho

## Contexto

O modulo de template de carrossel (Whatsapp_carousel_template, type=5 no banco)
apresenta dois defeitos visuais no painel administrativo apos a criacao de um
template: (1) o template salvo nao aparece na listagem da aba "Carousel" na
mesma pagina e (2) o template aparece dentro da aba de "Buttons" como se fosse
um template de botoes. Cada tipo de template (botoes type=2, lista type=1,
carrossel type=5) deve permanecer embarcado em sua propria aba e funcionar
de forma independente. O problema foi descrito no PRD
docs/adrs/001-carrossel-correcao-v1.md.

O sistema usa a tabela TB_WHATSAPP_TEMPLATE com coluna type para distinguir os
tipos. O modulo de carrossel (Config id=whatsapp_carousel_template, parent
id=template) e registrado no sidebar junto com botoes (type=2) e lista (type=1).
O Bot Builder usa o endpoint bot-builder/native-templates?type=5 e o JS
inspector-templates.js (nativeTypeForNode) para carregar templates por tipo.

## Historias

### US-036 — Carrossel salva e lista na aba correta

Como administrador do sistema, quero que apos criar um template de carrossel
ele apareca imediatamente na aba "Carousel" do sidebar de templates, para que
eu consiga visualizar e gerenciar meus templates de carrossel sem confusao.

#### AC-095 — Carrossel criado aparece na listagem apos salvar

- **Dado** que estou logado como administrador com a permissao whatsapp_carousel_template habilitada e acesso a pagina de criacao de template de carrossel (/whatsapp_carousel_template/index/update)
- **Quando** preencho o nome "Meu Carrossel", adiciono 2 cards com titulo e corpo, e clico em Submit
- **Entao** sou redirecionado para a listagem de templates de carrossel e o template "Meu Carrossel" aparece na aba "Carousel" com a contagem de cards exibida

#### AC-096 — Carrossel nao aparece na aba de Botoes

- **Dado** que criei um template de carrossel chamado "Meu Carrossel" e ele aparece na aba Carousel
- **Quando** navego para a aba "Buttons" do sidebar de templates
- **Entao** o template "Meu Carrossel" NAO aparece na listagem de botoes

#### AC-097 — Aba correta fica ativa apos salvar carrossel

- **Dado** que estou na pagina de criacao de template de carrossel
- **Quando** submeto o formulario com sucesso
- **Entao** a aba "Carousel" do sidebar fica ativa e o template recem-criado e visivel na listagem

### US-037 — Carrossel edita corretamente pelo formulario proprio

Como administrador, quero que ao editar um template de carrossel o formulario
de carrossel seja aberto (e nao o de botoes), para que eu possa editar cards,
botoes e midias de cada card.

#### AC-098 — Edicao abre formulario de carrossel com cards pre-preenchidos

- **Dado** que existe um template de carrossel com 3 cards no banco de dados
- **Quando** clico no botao de editar deste template na listagem
- **Entao** o formulario de carrossel e aberto com os 3 cards pre-preenchidos (titulo, corpo, footer, midia) e os botoes de cada card com tipo e texto corretos

#### AC-099 — Edicao preserva botoes dos cards

- **Dado** que um template de carrossel tem 2 cards, onde o card 1 tem 2 botoes (quick_reply "Ver mais" e cta_url "Site")
- **Quando** abro o formulario de edicao deste template
- **Entao** o card 1 mostra os 2 botoes com seus tipos, textos e URLs corretamente preenchidos nos campos do formulario

### US-038 — Listagem AJAX do carrossel e independente

Como administrador, quero que a listagem paginada de templates de carrossel
via AJAX funcione independentemente da listagem de botoes.

#### AC-100 — Endpoint ajax_list do carrossel retorna somente type=5

- **Dado** que existem 5 templates de carrossel (type=5) e 10 de botoes (type=2) no banco para o mesmo team_id
- **Quando** faco uma requisicao AJAX para o endpoint ajax_list do modulo de carrossel
- **Entao** a resposta contem apenas os 5 templates de carrossel com a contagem de cards de cada um

#### AC-101 — Paginacao do carrossel nao interfere na de botoes

- **Dado** que existem 35 templates de carrossel e 40 de botoes
- **Quando** navego para a pagina 2 da listagem AJAX de carrossel
- **Entao** a listagem de botoes permanece na pagina 1 e os dados de paginacao de cada modulo sao independentes

### US-039 — Bot Builder seletor nativo de templates funciona para carrossel

Como administrador, quero que o seletor de templates nativos no Bot Builder
carregue e exiba templates de carrossel (type=5) na secao correta do node
"cards", para que eu possa vincular um template de carrossel a um bloco do
fluxo.

#### AC-102 — native_templates retorna type=5 para nodes cards

- **Dado** que existem templates de carrossel no banco e o Bot Builder esta aberto com um node do tipo "cards"
- **Quando** o seletor de templates nativos carrega (loadNativeTemplatesIntoSelect com type=5)
- **Entao** a requisicao GET para bot-builder/native-templates?type=5 retorna apenas templates de carrossel e o dropdown exibe os nomes corretos

#### AC-103 — Criar novo carrossel pelo Bot Builder abre formulario correto

- **Dado** que estou no Bot Builder com um node "cards" selecionado e o seletor de templates nativos esta aberto
- **Quando** clico no botao "Criar novo"
- **Entao** sou redirecionado para /whatsapp_carousel_template/index/update e apos salvar o template de carrossel, o redirect (parametro wa_return) me traz de volta ao Bot Builder

#### AC-104 — preview do carrossel mostra cards e nao botoes

- **Dado** que um template de carrossel esta selecionado no seletor nativo do Bot Builder
- **Quando** o renderNativeTemplatePreview e executado com type=5
- **Entao** o preview mostra o titulo do carrossel, a contagem de cards e as linhas de cada card (titulo truncado em 45 caracteres)

## Fora de escopo

- Correcao dos modulos de lista (type=1) e enquete (type=3/poll)
- Alteracoes no envio de mensagens via gateway Go (send_whatsapp)
- Templates oficiais Meta (type=66) e submissao Meta
- Alteracoes de layout/CSS alem do necessario para as abas funcionarem
- Correcao de bugs no whatsapp_button_template ou whatsapp_list_message_template

## Suposicoes

| ID | Suposicao | Status | Resolucao |
|---|---|---|---|
| ASM-201 | A tabela TB_WHATSAPP_TEMPLATE armazena corretamente type=5 para templates de carrossel — o INSERT do controller save() esta correto, o bug e na leitura/renderizacao | aberta | — |
| ASM-202 | O Bootstrap 5 tabs/pills esta funcionando no layout — o problema provavelmente e na estrutura HTML dos tab-panes ou no resultado do redirect | aberta | — |
| ASM-203 | A permissao whatsapp_carousel_template esta habilitada para o usuario de teste — se desabilitada, widget_content retorna vazio | aberta | — |
| ASM-204 | O redirect apos save() do carousel aponta para a URL correta do modulo (get_module_url()) e nao para a de botoes | aberta | — |
| ASM-205 | Nao ha conflito de namespace ou autoloading entre Whatsapp_carousel_template e Whatsapp_button_template — ambos tem Config id unico | aberta | — |

## Perguntas em aberto

| ID | Pergunta | Status | Resposta |
|---|---|---|---|
| Q-201 | Existe algum commit antigo onde o carrossel funcionava corretamente? Se sim, qual o SHA para comparar o diff? | aberta | — |
| Q-202 | O bug acontece apenas no Bot Builder (seletor visual de templates) ou tambem na pagina standalone /whatsapp_carousel_template? | aberta | — |
| Q-203 | A permissao whatsapp_carousel_template esta habilitada no plano do usuario afetado? | aberta | — |
