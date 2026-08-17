# Plano Vivo — Modernização Visual do Flow Builder Zapmatic

Status: em planejamento
Data de início: 2026-06-17
Objetivo: transformar o Flow Builder em uma experiência visual mais profissional, moderna e próxima de ferramentas como Typebot/BotConversa, sem quebrar o backend atual nem o envio WhatsApp.

---

## 1. Princípios do projeto

1. Evoluir em fases pequenas e testáveis.
2. Separar design/frontend do runtime/backend sempre que possível.
3. Evitar mexer no `waziper.js` durante melhorias visuais.
4. Não trocar toda a arquitetura de uma vez.
5. Manter compatibilidade com fluxos já criados.
6. Registrar cada avanço neste documento.
7. Priorizar usabilidade real: criar, editar, testar e publicar fluxos com menos confusão.

---

## 2. Arquivos principais do módulo

### Frontend/editor visual

```text
inc/core/Bot_builder/Assets/js/bot_builder.js
```

Responsável por:

- canvas visual;
- nodes/blocos;
- inspector lateral;
- conexões;
- preview dos blocos;
- editor de configurações;
- salvamento do fluxo.

### Backend/runtime

```text
inc/core/Bot_builder/Controllers/Bot_builder.php
```

Responsável por:

- salvar bot;
- executar fluxo;
- processar sessão;
- enviar mensagens;
- resolver templates nativos;
- integrar com WhatsApp.

### Documentos relacionados

```text
FLOW_BUILDER_TYPEBOT_ROADMAP.md
RELATORIO_FLOW_BUILDER.md
SUMARIO_EXECUTIVO_FLOW_BUILDER.md
GUIA_PRATICO_TESTE_FLOW_BUILDER.md
TESTE_FLOW_BUILDER_56NODES.md
```

---

## 3. Meta visual desejada

O Flow Builder deve parecer uma ferramenta SaaS moderna:

- canvas limpo;
- blocos com visual mais profissional;
- ícones consistentes;
- cores por categoria;
- inspector lateral mais organizado;
- navegação fácil;
- preview claro do que vai chegar no WhatsApp;
- estados visuais: salvo, publicando, erro, aguardando teste;
- experiência parecida com Typebot, BotConversa, Manychat e Make.

---

## 4. Fases propostas

## Fase 1 — Base visual e organização sem risco

Objetivo: melhorar aparência sem alterar lógica do fluxo.

Tarefas:

- [ ] Mapear estrutura atual do `bot_builder.js`.
- [ ] Identificar onde ficam estilos inline e HTML gerado via JS.
- [ ] Criar padrão visual de cards/nodes.
- [ ] Melhorar sidebar esquerda de blocos.
- [ ] Melhorar inspector lateral direito.
- [ ] Ajustar espaçamentos, bordas, sombras e tipografia.
- [ ] Padronizar cores por categoria.
- [ ] Melhorar estado visual de node selecionado.

Risco: baixo.

Arquivos prováveis:

```text
inc/core/Bot_builder/Assets/js/bot_builder.js
```

---

## Fase 2 — Experiência de edição dos blocos

Objetivo: deixar edição mais fácil, bonita e menos confusa.

Tarefas:

- [ ] Reorganizar campos do inspector por seções.
- [ ] Melhorar bloco Texto.
- [ ] Melhorar bloco Imagem/Vídeo/Áudio.
- [ ] Melhorar bloco Botões.
- [ ] Melhorar bloco Lista.
- [ ] Melhorar bloco Carrossel.
- [ ] Melhorar visual de variáveis disponíveis.
- [ ] Criar mensagens de ajuda pequenas abaixo dos campos.

Risco: baixo/médio.

---

## Fase 3 — Preview WhatsApp mais realista

Objetivo: o usuário ver melhor como a mensagem chegará no WhatsApp.

Tarefas:

- [ ] Criar preview estilo bolha do WhatsApp no inspector.
- [ ] Preview para texto.
- [ ] Preview para mídia.
- [ ] Preview para botões.
- [ ] Preview para lista.
- [ ] Preview para carrossel.
- [ ] Mostrar aviso quando item depende de template nativo.

Risco: médio, mas só frontend.

---

## Fase 4 — Debug e teste visual

Objetivo: ajudar o usuário a entender por onde o contato passou no fluxo.

Tarefas:

- [ ] Criar painel de teste/simulador básico.
- [ ] Destacar caminho percorrido no canvas.
- [ ] Mostrar último bloco executado.
- [ ] Mostrar variáveis da sessão.
- [ ] Mostrar erros de validação.
- [ ] Adicionar botão “Testar este fluxo”.

Risco: médio.

Pode exigir backend depois.

---

## Fase 5 — Organização profissional e produtividade

Objetivo: escalar para fluxos grandes.

Tarefas:

- [ ] Mini mapa do canvas.
- [ ] Busca por bloco.
- [ ] Agrupar blocos por categoria.
- [ ] Duplicar bloco.
- [ ] Copiar/colar bloco.
- [ ] Auto-organizar fluxo.
- [ ] Comentários/notas nos blocos.
- [ ] Histórico visual de versões.

Risco: médio/alto.

---

## Fase 6 — Polimento final SaaS

Objetivo: aparência premium.

Tarefas:

- [ ] Empty states bonitos.
- [ ] Skeleton loading.
- [ ] Toasts padronizados.
- [ ] Confirmações elegantes.
- [ ] Modal de publicação.
- [ ] Tela de templates pronta para venda/uso rápido.
- [ ] Onboarding do Flow Builder.

Risco: baixo/médio.

---

## 5. Plano recomendado de início

Começar pela Fase 1, porque é a mais segura:

1. Ler o `bot_builder.js`.
2. Mapear funções principais do frontend.
3. Separar mentalmente:
   - renderização do canvas;
   - renderização dos nodes;
   - inspector;
   - sidebar;
   - estilos.
4. Fazer melhorias pequenas em uma tela por vez.
5. Testar criar/salvar/publicar fluxo após cada alteração.

---

## 6. Checklist de progresso

### Planejamento

- [x] Criar documento vivo de modernização.
- [x] Mapear estrutura real do frontend atual.
- [x] Definir identidade visual base.
- [x] Definir primeira tela/bloco a modernizar.

### Implementação

- [x] Melhorar sidebar. Primeira camada aplicada em 2026-06-17: largura refinada, fundo em gradiente suave, busca premium, categorias com marcador visual e itens de bloco em cards modernos.
- [x] Melhorar visual dos nodes. Primeira camada aplicada em 2026-06-17: cards mais modernos, sombra premium, header refinado, ícone maior, body com preview mais limpo e handles mais elegantes.
- [x] Melhorar inspector. Primeira camada aplicada em 2026-06-17: painel mais largo, fundo premium, header refinado, botão fechar moderno, campos mais elegantes e seções com card visual.
- [x] Melhorar preview. Primeira camada aplicada em 2026-06-17: preview estilo WhatsApp dentro do inspector, com bolha, mídia, áudio e ações para botões/lista/carrossel.
- [x] Melhorar estados de salvamento/publicação. Primeira camada aplicada em 2026-06-18: topbar, badges de status, botões principais, simulador e modal de publicação/conexões.

### Validação

- [ ] Criar fluxo novo.
- [ ] Editar fluxo existente.
- [ ] Publicar fluxo.
- [ ] Testar envio WhatsApp.
- [ ] Confirmar que flows antigos seguem abrindo.

---

## 7. Registro de avanços

### 2026-06-17

- Criado este documento de planejamento.
- Decidido começar pelo frontend/design do Flow Builder.
- Regra importante: melhorias visuais devem evitar alteração no `waziper.js`.
- Próxima ação sugerida: mapear `bot_builder.js` e escolher primeira área visual para modernizar.
- Mapeado o frontend principal: `bot_builder.js` renderiza o canvas/nodes e `bot_builder.css` concentra os estilos visuais do editor.
- Aplicada primeira modernização segura em `inc/core/Bot_builder/Assets/css/bot_builder.css`, focada apenas em CSS dos nodes/cards.
- Aplicada primeira modernização visual da sidebar esquerda: header, busca, categorias, cards dos blocos e ícones.
- Aplicada primeira modernização visual do inspector lateral direito: painel, header, botão fechar, campos e seções.
- Criado preview estilo WhatsApp dentro do inspector, renderizado no frontend para texto, mídia, áudio e opções de botões/lista/carrossel.
- Segunda rodada visual iniciada e aplicada: editor de botões rápidos em mini-cards, upload/preview de mídia mais profissional e editor de cards/carrossel com layout visual refinado.
- Modernizados componentes de produtividade do inspector: seletor de variáveis, dropdown de variáveis, toggles, dicas (`form-hint`) e alertas (`insp-alert`).
- Modernizada a barra superior do editor: topbar, botão voltar, nome/status do bot, grupo de ferramentas, botões principais e estados de salvamento/publicação.
- Modernizados painel de preview/simulador WhatsApp e modal de publicação/conexões, com refinamento visual em cards, botões, estados, footer e overlay.
- Modernizada a tela externa/listagem do Bot Builder em `inc/core/Bot_builder/Views/index.php`, com paleta mais profissional, menos colorida, cards claros, ações refinadas, tags sóbrias e hero mais elegante.
- Ajustada a tela externa para não estourar tanto nas laterais: `max-width`, centralização, espaçamento lateral, grid mais controlado e responsividade.
- Adicionados overrides separados para tema claro (`body.dl-light`) e escuro explícito (`body.dl-dark`, `body.dark`, `body[data-theme="dark"]`), evitando aplicar tema escuro indevidamente em fundo claro.
- Corrigido desalinhamento dos ícones/avatares dos cards externos e padronizados os chips de palavras-chave para tamanho fixo/consistente, evitando “balões” irregulares.
- Corrigido alinhamento do título “Seus bots”: ícone, texto e badge contador ficam próximos, visíveis e alinhados.
- Iniciada Fase 4 com painel de validação visual do fluxo no editor: botão “Validar”, resumo de blocos/conexões/avisos e checagens frontend de início, fim, conexões e conteúdo básico.
- Adicionado destaque visual do bloco atual durante o simulador/preview, com classe `sim-active`, borda verde e etiqueta “Testando agora”.
- Adicionado painel “Variáveis da sessão” no simulador, exibindo `sim.context` em tempo real para entradas, seleções, pagamentos e integrações simuladas.
- Adicionado histórico visual “Caminho percorrido” no simulador, registrando cada bloco visitado com ordem, nome e tipo.
- Ajustado tamanho/layout do simulador para priorizar o chat: painel maior, chat com altura mínima e áreas de variáveis/histórico compactadas.
- Adicionado destaque das conexões percorridas no canvas durante a simulação (`sim-traversed-edge`).
- Adicionado indicador de presença “digitando...” antes das respostas simuladas do bot.
- Ajustadas conexões do canvas para manter movimento contínuo em todas as ligações existentes; no teste, o caminho percorrido mantém o movimento e muda apenas para destaque verde.
- Refinada a paleta das conexões normais para azul/índigo suave, deixando o fluxo mais moderno e chamativo sem poluir o canvas.
- Adicionada lixeira flutuante com tooltip “Excluir conexão” no meio das ligações, usando a lógica existente de remoção e facilitando a edição visual.
- Não houve alteração no backend/runtime nem no `waziper.js` nesta etapa visual.

---

## 8. Próxima decisão pendente

Escolher qual área vamos modernizar primeiro:

1. Sidebar esquerda de blocos.
2. Cards/nodes no canvas.
3. Inspector lateral direito.
4. Preview WhatsApp no inspector.

Recomendação: começar por **cards/nodes no canvas + sidebar**, porque dá impacto visual imediato e baixo risco.
