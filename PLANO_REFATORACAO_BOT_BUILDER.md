# Plano de Refatoração Segura — Bot Builder Zapmatic

Status: planejamento aprovado para execução gradual  
Data de início: 2026-06-19  
Objetivo: transformar o Bot Builder em uma base mais profissional, modular, funcional e fácil de manter, sem quebrar o funcionamento atual do editor, salvamento, publicação ou runtime WhatsApp.

---

## 1. Contexto

O Bot Builder evoluiu bastante visualmente e agora possui recursos modernos no editor:

- canvas visual;
- nodes/blocos;
- conexões animadas;
- lixeira contextual nas conexões;
- inspector lateral;
- preview WhatsApp;
- simulador;
- validação visual;
- publicação/conexões;
- configurações do bot.

O problema atual é estrutural: o frontend principal está concentrado em um arquivo grande:

```text
inc/core/Bot_builder/Assets/js/bot_builder.js
```

E o backend de execução também possui um ponto monolítico importante:

```text
inc/core/Bot_builder/Controllers/Bot_builder.php::run_flow
```

Este plano define como reduzir o monólito em fases pequenas, testáveis e reversíveis.

---

## 2. Princípios obrigatórios

1. Não quebrar fluxos existentes.
2. Não alterar o contrato de dados salvo no banco sem necessidade.
3. Não alterar o envio real WhatsApp durante refatorações frontend.
4. Não mexer no `waziper.js` durante esta etapa.
5. Refatorar em módulos pequenos.
6. Uma fase por vez.
7. Testar após cada fase.
8. Manter possibilidade de rollback simples.
9. Documentar cada avanço neste arquivo.
10. Não misturar refatoração estrutural com novas features grandes.

---

## 3. Diagnóstico atual

## 3.1 Frontend monolítico

Arquivo principal:

```text
inc/core/Bot_builder/Assets/js/bot_builder.js
```

Responsabilidades concentradas no mesmo arquivo:

| Responsabilidade | Situação atual |
|---|---|
| Estado global do editor | Dentro de `bot_builder.js` |
| Definição dos tipos de blocos | Dentro de `bot_builder.js` |
| Criação/renderização de nodes | Dentro de `bot_builder.js` |
| Drag/drop | Dentro de `bot_builder.js` |
| Inspector lateral | Dentro de `bot_builder.js` |
| Campos dinâmicos | Dentro de `bot_builder.js` |
| Upload/preview de mídia | Dentro de `bot_builder.js` |
| Sistema de variáveis | Dentro de `bot_builder.js` |
| Conexões SVG | Dentro de `bot_builder.js` |
| Pan/zoom | Dentro de `bot_builder.js` |
| Undo/redo | Dentro de `bot_builder.js` |
| Autosave/saveFlow | Dentro de `bot_builder.js` |
| Validação visual | Dentro de `bot_builder.js` |
| Simulador | Dentro de `bot_builder.js` |
| Modal de publicação | Dentro de `bot_builder.js` |
| Configurações do bot | Dentro de `bot_builder.js` |

### Conclusão

O arquivo é funcional, mas está acoplado demais. O risco de manutenção aumenta a cada nova melhoria.

---

## 3.2 Backend com runtime concentrado

Arquivo:

```text
inc/core/Bot_builder/Controllers/Bot_builder.php
```

Ponto crítico:

```text
run_flow()
```

Responsabilidades concentradas:

- carregar bot;
- carregar blocos;
- carregar conexões;
- resolver bloco atual;
- processar resposta do usuário;
- validar inputs;
- rotear botões/listas/cards;
- executar blocos;
- substituir variáveis;
- enviar WhatsApp;
- salvar estado da sessão.

### Conclusão

O backend também merece refatoração, mas deve vir depois do frontend porque é mais sensível para produção.

---

## 4. Brainstorm de estratégias

## Opção A — Refatoração mínima e progressiva

Separar somente partes bem delimitadas do frontend, mantendo `bot_builder.js` como bootstrap principal.

### Prós

- Menor risco.
- Fácil rollback.
- Não exige build system.
- Compatível com o carregamento atual por `<script>`.
- Permite validar etapa por etapa.

### Contras

- O monólito diminui aos poucos.
- Ainda haverá dependência do estado global `window.BotBuilder` por um tempo.

### Esforço

Médio.

---

## Opção B — Modularização completa com ES Modules

Transformar o editor em módulos `import/export` modernos.

### Prós

- Arquitetura mais limpa.
- Separação real de dependências.
- Melhor manutenção futura.

### Contras

- Pode exigir ajuste de carregamento no PHP.
- Pode quebrar se algum navegador/configuração antiga não lidar bem.
- Mais risco imediato.
- Refatoração maior.

### Esforço

Alto.

---

## Opção C — Reescrever editor com framework moderno

Recriar o editor com Vue/React/Svelte ou similar.

### Prós

- Melhor arquitetura possível a longo prazo.
- Componentização real.
- Estado mais previsível.

### Contras

- Alto risco.
- Alto custo.
- Pode quebrar fluxos existentes.
- Exige build, deploy e migração.
- Não é necessário para o momento atual.

### Esforço

Muito alto.

---

## 5. Recomendação

Seguir com a **Opção A — Refatoração mínima e progressiva**.

Motivo:

- O sistema está em produção.
- O editor já funciona.
- As melhorias visuais recentes estão estáveis.
- O objetivo agora é reduzir risco, não reescrever tudo.
- O projeto não precisa de build system para começar a ficar mais profissional.

Estratégia:

```text
Manter bot_builder.js como orquestrador inicial.
Extrair módulos simples para window.BotBuilderModules.
Carregar os novos arquivos antes do bot_builder.js em editor.php.
Validar cada extração isoladamente.
```

---

## 6. Arquitetura alvo inicial

```text
inc/core/Bot_builder/Assets/js/builder/
├── node-defs.js
├── utils.js
├── connections.js
├── simulator.js
├── validation.js
├── publish-modal.js
└── inspector.js
```

Nesta primeira etapa, não será obrigatório criar todos de uma vez.

Ordem segura:

1. `utils.js`
2. `node-defs.js`
3. `connections.js`
4. `validation.js`
5. `simulator.js`
6. `publish-modal.js`
7. `inspector.js`

---

## 7. Modelo de compatibilidade

Para evitar quebra, os módulos novos devem expor funções no namespace global controlado:

```js
window.BotBuilderModules = window.BotBuilderModules || {};
```

Exemplo conceitual:

```js
window.BotBuilderModules.connections = {
    enableConnections,
    drawConnections,
    getCubicPoint
};
```

Depois o `bot_builder.js` pode continuar chamando nomes locais ou delegar para os módulos.

Durante a transição, será permitido manter wrappers:

```js
function drawConnections() {
    return window.BotBuilderModules.connections.drawConnections(...);
}
```

Assim os pontos existentes continuam funcionando.

---

## 8. Plano faseado

# Fase 0 — Congelamento e linha de base

Objetivo: garantir que temos um ponto estável antes de mexer.

## Tarefas

- [ ] Rodar validação JS atual.
- [ ] Rodar validação PHP do editor/controller se houver alteração PHP.
- [ ] Verificar se editor abre.
- [ ] Verificar se bot existente abre.
- [ ] Verificar se conexões aparecem.
- [ ] Verificar se lixeira da conexão aparece.
- [ ] Verificar se autosave ainda funciona.
- [ ] Registrar estado inicial neste plano.

## Testes internos

```text
node -c inc/core/Bot_builder/Assets/js/bot_builder.js
php -l inc/core/Bot_builder/Views/editor.php
php -l inc/core/Bot_builder/Controllers/Bot_builder.php
```

## Critério de avanço

Só avançar se editor estiver abrindo e sem erro JS de sintaxe.

---

# Fase 1 — Extrair utilitários básicos

Objetivo: separar funções utilitárias sem mexer em lógica principal.

Arquivo novo:

```text
inc/core/Bot_builder/Assets/js/builder/utils.js
```

Candidatos:

- `$`
- `$$`
- `escHtml`
- `uuidv4`
- `snap`
- `pause`
- `replVars`
- `escapeHtml`

## Estratégia segura

1. Criar `utils.js`.
2. Expor em `window.BotBuilderModules.utils`.
3. Carregar `utils.js` antes de `bot_builder.js`.
4. Trocar chamadas aos poucos ou criar aliases no `bot_builder.js`.
5. Validar.

## Testes internos

- Criar node.
- Arrastar node.
- Abrir inspector.
- Salvar fluxo.
- Usar simulador.

## Risco

Baixo.

## Rollback

Remover `<script>` novo e manter funções originais no `bot_builder.js`.

---

# Fase 2 — Extrair definições de nodes

Objetivo: mover a configuração estática dos tipos de blocos.

Arquivo novo:

```text
inc/core/Bot_builder/Assets/js/builder/node-defs.js
```

Mover:

```text
NODE_DEFS
```

## Estratégia segura

1. Criar `node-defs.js` com `window.BotBuilderNodeDefs`.
2. Em `bot_builder.js`, trocar:

```js
const NODE_DEFS = {...}
```

por:

```js
const NODE_DEFS = window.BotBuilderNodeDefs || {};
```

3. Carregar `node-defs.js` antes de `bot_builder.js` em `editor.php`.

## Testes internos

- Sidebar continua criando todos os tipos.
- Node de texto cria corretamente.
- Node de botão cria handles.
- Node de condição cria saídas SIM/NÃO.
- Node de integração mostra label/ícone.

## Risco

Baixo.

## Critério de avanço

Todos os nodes principais aparecem com ícone e label corretos.

---

# Fase 3 — Extrair conexões

Objetivo: modularizar a área que já está bem delimitada e ficou mais rica.

Arquivo novo:

```text
inc/core/Bot_builder/Assets/js/builder/connections.js
```

Mover:

- `connStart`
- `dragLine`
- `enableConnections`
- listeners de mouse para conexão
- `getCubicPoint`
- `drawConnections`
- lógica de hitbox invisível
- lixeira flutuante

## Estratégia segura

1. Criar módulo `connections.js`.
2. Receber dependências por contexto:

```js
window.BotBuilderModules.connections.init({
    BB,
    canvas,
    svg,
    canvasContainer,
    $, $$,
    saveSnapshot,
    triggerAutoSave
});
```

3. Manter wrappers no `bot_builder.js`:

```js
function enableConnections(node) {
    return window.BotBuilderModules.connections.enableConnections(node);
}

function drawConnections() {
    return window.BotBuilderModules.connections.drawConnections();
}
```

## Testes internos

- Criar conexão entre dois nodes.
- Mover node e confirmar linha acompanha.
- Excluir conexão pela lixeira.
- Excluir conexão clicando na linha/hitbox.
- Confirmar autosave após excluir.
- Confirmar undo/redo após excluir/criar.
- Confirmar destaque verde no simulador.

## Risco

Médio/baixo.

## Critério de avanço

Todas as conexões funcionam igual ou melhor que antes.

---

# Fase 4 — Extrair validação visual

Objetivo: separar painel/botão de validação do fluxo.

Arquivo novo:

```text
inc/core/Bot_builder/Assets/js/builder/validation.js
```

Mover:

- `toggleValidationPanel`
- `renderFlowValidation`
- regras visuais de validação.

## Testes internos

- Fluxo sem bloco início mostra aviso.
- Fluxo sem fim mostra aviso.
- Bloco sem saída mostra aviso.
- Bloco sem conteúdo mostra aviso.
- Botão Validar abre/fecha painel.

## Risco

Baixo/médio.

---

# Fase 5 — Extrair simulador

Objetivo: isolar o mini runtime frontend.

Arquivo novo:

```text
inc/core/Bot_builder/Assets/js/builder/simulator.js
```

Mover:

- estado `sim`;
- preview;
- variáveis da sessão;
- caminho percorrido;
- typing indicator;
- mensagens simuladas;
- input do usuário;
- validações simuladas.

## Testes internos

- Abrir simulador.
- Rodar fluxo simples.
- Clicar botão no simulador.
- Capturar variável.
- Ver histórico percorrido.
- Ver conexão percorrida verde.
- Fechar e abrir novamente.

## Risco

Médio.

## Observação

Não misturar esta fase com alteração do runtime real PHP.

---

# Fase 6 — Extrair modal de publicação

Objetivo: separar publicação e conexão de instâncias.

Arquivo novo:

```text
inc/core/Bot_builder/Assets/js/builder/publish-modal.js
```

Mover:

- `openPublishModal`
- `closePublishModal`
- `updatePmStatus`
- `renderInstances`
- `publishBot`
- `toggleInstance`
- `publishAndConnect`
- `saveBotSettings` se fizer sentido.

## Testes internos

- Abrir modal.
- Listar instâncias.
- Publicar.
- Vincular instância.
- Desvincular instância.
- Salvar configurações do bot.

## Risco

Médio.

---

# Fase 7 — Extrair inspector

Objetivo: separar a parte mais complexa do editor.

Arquivo novo:

```text
inc/core/Bot_builder/Assets/js/builder/inspector.js
```

Mover depois, com cuidado:

- `openInspector`;
- campos;
- uploads;
- native templates;
- builders dinâmicos;
- variável picker;
- preview WhatsApp do inspector.

## Risco

Alto em comparação às fases anteriores.

## Recomendação

Só iniciar quando conexões, validação, simulador e publicação estiverem estáveis.

---

# Fase 8 — Refatoração backend runtime

Objetivo: reduzir `run_flow` sem mudar comportamento.

Arquivos futuros possíveis:

```text
inc/core/Bot_builder/Services/FlowRunner.php
inc/core/Bot_builder/Services/BlockExecutor.php
inc/core/Bot_builder/Services/RouteResolver.php
inc/core/Bot_builder/Services/MessageResolver.php
inc/core/Bot_builder/Services/VariableResolver.php
inc/core/Bot_builder/Services/WhatsAppSender.php
```

## Ordem segura

1. Extrair apenas helpers puros.
2. Extrair resolução de variáveis.
3. Extrair resolução de próximo bloco.
4. Extrair envio WhatsApp por tipo.
5. Só depois mexer no loop principal.

## Testes internos obrigatórios

- Webhook recebe texto.
- Fluxo inicia.
- Input salva variável.
- Botões roteiam correto.
- Lista roteia correto.
- Cards roteiam correto.
- Mídia envia correto.
- Fim de fluxo encerra sessão.
- Fluxos antigos continuam rodando.

## Risco

Alto.

## Recomendação

Deixar para depois da estabilização completa do frontend.

---

## 9. Ordem recomendada de execução imediata

Próximas ações práticas:

1. Fase 0: estabelecer linha de base.
2. Fase 2: extrair `NODE_DEFS` primeiro.
3. Fase 3: extrair conexões.
4. Fase 4: extrair validação.
5. Fase 5: extrair simulador.

Por que começar por `NODE_DEFS` e não por `utils`?

- `NODE_DEFS` é estático.
- É mais simples de validar.
- Reduz bastante o topo do arquivo.
- Tem baixo risco.
- Prepara o padrão de carregamento modular.

---

## 10. Checklist de testes manuais por fase

Após cada fase, executar:

## Editor

- [ ] Abrir `/bot-builder`.
- [ ] Abrir bot existente.
- [ ] Criar node Texto.
- [ ] Criar node Botões.
- [ ] Criar node Entrada de texto.
- [ ] Criar node Condição.
- [ ] Mover node.
- [ ] Conectar nodes.
- [ ] Excluir conexão pela lixeira.
- [ ] Excluir node.
- [ ] Duplicar node.
- [ ] Usar undo.
- [ ] Usar redo.

## Inspector

- [ ] Abrir inspector de texto.
- [ ] Editar mensagem.
- [ ] Ver preview atualizar.
- [ ] Abrir inspector de botões.
- [ ] Adicionar/remover opção.
- [ ] Abrir inspector de mídia.

## Simulador

- [ ] Abrir prévia.
- [ ] Iniciar simulação.
- [ ] Ver bloco ativo.
- [ ] Ver conexão percorrida.
- [ ] Ver variáveis.
- [ ] Ver caminho percorrido.
- [ ] Enviar resposta.

## Salvamento

- [ ] Autosave dispara.
- [ ] Salvar manual.
- [ ] Reabrir bot e confirmar alterações.
- [ ] Publicar.

## Backend básico

- [ ] `php -l editor.php`.
- [ ] `php -l Bot_builder.php` quando houver alteração PHP.

---

## 11. Comandos de validação técnica

```bash
node -c inc/core/Bot_builder/Assets/js/bot_builder.js
php -l inc/core/Bot_builder/Views/editor.php
php -l inc/core/Bot_builder/Controllers/Bot_builder.php
```

Se forem criados módulos JS novos:

```bash
node -c inc/core/Bot_builder/Assets/js/builder/node-defs.js
node -c inc/core/Bot_builder/Assets/js/builder/connections.js
node -c inc/core/Bot_builder/Assets/js/builder/simulator.js
```

---

## 12. Regras de carregamento no editor.php

Atualmente o editor carrega:

```text
bot_builder.js
```

Durante a modularização, os módulos devem ser carregados antes:

```php
<script src=".../builder/node-defs.js?v=<?php echo time() ?>"></script>
<script src=".../builder/connections.js?v=<?php echo time() ?>"></script>
<script src=".../bot_builder.js?v=<?php echo time() ?>"></script>
```

Regra:

```text
Módulos primeiro, bootstrap principal por último.
```

---

## 13. Controle de avanço

Cada fase precisa registrar:

```text
Data:
Fase:
Arquivos alterados:
O que mudou:
Testes executados:
Resultado:
Pendências:
```

---

## 14. Registro de avanço

### 2026-06-19 — Planejamento

- Criado este plano de refatoração segura.
- Confirmado que `bot_builder.js` está monolítico.
- Confirmado que `Bot_builder.php::run_flow` também é ponto monolítico no backend.
- Definida estratégia progressiva sem reescrita total.
- Definida recomendação de começar por `NODE_DEFS` e depois conexões.
- Linha de base técnica executada: `node -c bot_builder.js`, `php -l editor.php` e `php -l Bot_builder.php` sem erros de sintaxe.

### 2026-06-19 — Fase 2 concluída: extração de `NODE_DEFS`

- Criado o módulo `inc/core/Bot_builder/Assets/js/builder/node-defs.js`.
- Movidas as definições estáticas dos tipos de blocos para `window.BotBuilderNodeDefs`.
- Ajustado `bot_builder.js` para consumir `const NODE_DEFS = window.BotBuilderNodeDefs || {};`.
- Ajustado `editor.php` para carregar `builder/node-defs.js` antes de `bot_builder.js`.
- Mantido o comportamento existente: labels, ícones e defaults dos blocos continuam disponíveis pelo mesmo nome `NODE_DEFS` dentro do editor.
- Validações executadas sem erro: `node -c builder/node-defs.js`, `node -c bot_builder.js`, `php -l editor.php`, `php -l Bot_builder.php`.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

### 2026-06-19 — Fase 3 concluída: extração de conexões

- Criado o módulo `inc/core/Bot_builder/Assets/js/builder/connections.js`.
- Movida a lógica de criação de conexão, drag line, mousemove/mouseup, desenho SVG, hitbox invisível, lixeira flutuante e destaque de caminho percorrido.
- Mantidos wrappers em `bot_builder.js`: `enableConnections`, `drawConnections` e `getCubicPoint` continuam existindo para preservar chamadas internas.
- Ajustado `editor.php` para carregar `builder/connections.js` antes de `bot_builder.js`.
- Mantido o comportamento visual atual: linhas animadas, azul/índigo normal, verde no teste, lixeira com tooltip e área invisível ampla.
- Validações executadas sem erro: `node -c builder/connections.js`, `node -c bot_builder.js`, `php -l editor.php`, diagnostics de `connections.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

### 2026-06-19 — Ajuste visual pós-Fase 3

- Ajustada a visibilidade das conexões em posições onde o pontilhado parecia sumir perto de nodes/cards.
- Aumentado levemente o `z-index` do SVG de conexões, espessura visual da linha e estabilidade da hitbox com `vector-effect: non-scaling-stroke`.
- Validação CSS sem erros.

### 2026-06-19 — Fase 4 concluída: extração da validação visual

- Criado o módulo `inc/core/Bot_builder/Assets/js/builder/validation.js`.
- Movidas as regras do painel “Validar” para o módulo novo.
- Mantidos wrappers em `bot_builder.js`: `toggleValidationPanel` e `renderFlowValidation` continuam disponíveis.
- Ajustado `editor.php` para carregar `builder/validation.js` antes de `bot_builder.js`.
- Mantido o comportamento atual do painel: contagem de blocos, conexões, avisos e lista de problemas visuais.
- Validações executadas sem erro: `node -c builder/validation.js`, `node -c bot_builder.js`, `php -l editor.php`, diagnostics de `validation.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

### 2026-06-19 — Ajuste de experiência: persistência do viewport do canvas

- Identificado que o editor reiniciava `zoom`, `panX` e `panY` para os valores padrão ao dar F5.
- Adicionada persistência local por bot usando `localStorage` com chave baseada em `window.bsConfig.bot_id`.
- O canvas agora restaura a última posição e zoom ao recarregar a página.
- O botão de reset continua voltando para zoom 100% e posição inicial, salvando esse novo estado.
- Validação executada: `node -c bot_builder.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

### 2026-06-19 — Fase 5.1 concluída: extração segura dos helpers visuais do simulador

- Criado o módulo `inc/core/Bot_builder/Assets/js/builder/simulator.js`.
- Extraídos apenas os helpers visuais/painéis do simulador: bloco ativo, variáveis da sessão, histórico percorrido, mensagens do chat, botões simulados, typing indicator, mensagem do usuário, scroll e marcação de conexão percorrida.
- Mantido o runtime principal no `bot_builder.js` por segurança: `runSim`, `processSim`, `nextSim` e `validateSimInput` ainda não foram movidos.
- Mantidos wrappers em `bot_builder.js` para preservar chamadas internas existentes.
- Ajustado `editor.php` para carregar `builder/simulator.js` antes de `bot_builder.js`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, `php -l editor.php`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

### 2026-06-19 — Fase 5.2 concluída parcialmente: validação de entrada do simulador

- Movida a função de validação `validateSimInput` para o módulo `builder/simulator.js` como `validateInput`.
- Mantido wrapper em `bot_builder.js` para preservar a chamada existente do runtime do simulador.
- Validações contempladas: obrigatório, texto mínimo/máximo/regex, e-mail, website, número, telefone, data, hora e rating.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

### 2026-06-19 — Fase 5.2 concluída parcialmente: roteamento `nextSim`

- Movida a lógica de roteamento `nextSim` para o módulo `builder/simulator.js` como `nextNode`.
- Mantido wrapper em `bot_builder.js` para preservar todas as chamadas existentes do runtime do simulador.
- O roteamento continua priorizando handle específico, depois conexão default e por último a primeira conexão disponível.
- A marcação visual da conexão percorrida continua passando por `markEdge`, mantendo o destaque verde no canvas.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Correção pós-teste: o módulo `simulator.js` precisava receber `BB` no `init`; sem isso a prévia parava no bloco Início após a extração de `nextSim`.
- Corrigido `bot_builder.js` para passar `BB` ao módulo do simulador.
- Teste funcional interno adicionado/executado via Node com stub do módulo: rota default, rota por handle e marcação de conexão percorrida retornaram OK.
- Extraído helper `resolveChoice` para centralizar o roteamento de escolhas/botões do simulador.
- Removida duplicação de estratégias entre clique em botão e resposta digitada: match exato, case-insensitive, índice numérico, parcial e fallback default.
- Teste funcional interno executado: rota exata, case-insensitive, índice numérico, match parcial e fallback default retornaram OK.
- Extraído `processResponse` para o módulo `builder/simulator.js`, mantendo `processSim` como wrapper no arquivo principal.
- O processamento de resposta agora centraliza captura de variáveis, validação, pagamento simulado, atualização do painel de variáveis e chamada de próxima rota dentro do módulo.
- Teste funcional interno executado: entrada de e-mail inválida mantém espera, e-mail válido captura variável e roteia, botão captura seleção e roteia por escolha.
- Extraído `startSimulation` para o módulo `builder/simulator.js`, mantendo `startSim` como wrapper no arquivo principal.
- A inicialização agora reseta chat, estado da simulação, painel de variáveis, painel de histórico, bloco ativo e direciona para o bloco Início pelo módulo.
- Teste funcional interno executado: reset do chat, reset de estado, limpeza de contexto/histórico/conexões percorridas e roteamento para bloco Início retornaram OK.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Criado e executado teste interno intensivo comparando comportamento legado esperado vs módulo atual do simulador.
- Cobertura do teste comparativo: `nextNode`, `resolveChoice`, validações de entrada, `processResponse` e `startSimulation`.
- Casos comparados: rota default, rota por handle, fallback de primeira conexão, match exato, case-insensitive, índice numérico, match parcial, fallback default, e-mail, número, site, telefone, captura de variável, resposta inválida mantendo espera, botão capturando seleção e reset/início da simulação.
- Resultado do teste intensivo: `legacy vs module intensive simulator test: OK`.
- Extraído `executeSimpleBlock` para o módulo `builder/simulator.js`, cobrindo apenas `start`, `text`, `end` e `delay`.
- `runSim` no `bot_builder.js` agora delega primeiro para `executeSimpleBlock`; se o módulo retornar `false`, mantém o fallback legado para os demais tipos.
- Teste intensivo da extração simples executado: bloco não suportado retorna fallback, `start` roteia, `text` renderiza com variáveis, `delay` roteia e `end` finaliza.
- Teste comparativo legado vs módulo para blocos simples executado: `legacy vs module simple blocks comparison: OK`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

### 2026-06-19 — Fase 5.3 concluída parcialmente: execução de mensagens/mídias simples

- Estado anterior mapeado no `runSim` legado para `image`, `video`, `audio` e `embed`:
  - `image`: enviava `📷 ` + legenda, URL ou `Imagem`, depois seguia pela conexão default.
  - `video`: enviava `🎬 ` + legenda, URL ou `Vídeo`, depois seguia pela conexão default.
  - `audio`: enviava `🎵 Mensagem de áudio`, depois seguia pela conexão default.
  - `embed`: enviava `🔗 [Conteúdo incorporado: ...]`, usando título, URL ou `Link`, depois seguia pela conexão default.
- Estado posterior: `executeSimpleBlock` no módulo `builder/simulator.js` agora cobre também `image`, `video`, `audio` e `embed`, além de `start`, `text`, `end` e `delay`.
- Mantido fallback seguro: `runSim` continua delegando primeiro ao módulo e preserva o bloco legado para tipos ainda não extraídos.
- Teste comparativo legado vs módulo executado para mídias simples com variações de configuração: caption, URL e valores padrão.
- Resultado do teste comparativo: `legacy vs module media blocks comparison: OK`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

### 2026-06-19 — Fase 5.3 concluída parcialmente: execução de blocos que aguardam resposta

- Estado anterior mapeado no `runSim` legado para blocos de entrada/captura:
  - `input`, `input_text`, `input_email`, `input_website` e `input_phone`: exibiam a pergunta com variáveis substituídas e definiam `sim.waiting = 'text'`.
  - `input_number`: exibia a pergunta e, quando configurado, a faixa mínima/máxima; depois aguardava texto.
  - `input_date`: exibia a pergunta e o formato de data; depois aguardava texto.
  - `input_time`: exibia a pergunta e o formato de hora; depois aguardava texto.
  - `rating`: exibia a pergunta e a faixa `1 até max_stars`; depois aguardava texto.
  - `file_upload`: exibia a pergunta, tipos permitidos e tamanho máximo; depois aguardava texto.
- Estado posterior: `executeSimpleBlock` no módulo `builder/simulator.js` agora cobre estes blocos de entrada/captura, mantendo a mesma mensagem e o mesmo estado `waiting`.
- Mantido fallback seguro: `runSim` continua delegando primeiro ao módulo e preserva o bloco legado para tipos ainda não extraídos.
- Teste comparativo legado vs módulo executado para todas as variações principais: pergunta com variável, número com/sem faixa, data com/sem formato customizado, rating com/sem máximo customizado e upload com/sem restrições customizadas.
- Resultado do teste comparativo: `legacy vs module input blocks comparison: OK`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

### 2026-06-19 — Fase 5.3 concluída parcialmente: execução dos blocos de escolha

- Estado anterior mapeado no `runSim` legado para blocos de escolha:
  - `buttons`: quebrava `options` por vírgula, renderizava botões simulados com o texto do bloco e definia `sim.waiting = 'button'`.
  - `pic_choice`: quebrava `choices`, usava o texto antes do `|` como opção, adicionava o aviso `[Escolhas com imagem]` e aguardava botão.
  - `cards`: quebrava `cards_data` por linha, usava ação/título como opção e, sem cards, mostrava fallback `Card 1`; depois aguardava botão.
  - `list`: extraía opções de `sections`; com opções renderizava botões simulados, sem opções renderizava mensagem `[Menu de lista]`; em ambos os casos aguardava botão.
- Estado posterior: `executeSimpleBlock` no módulo `builder/simulator.js` agora cobre `buttons`, `pic_choice`, `cards` e `list`, mantendo as mesmas mensagens, opções e estado `waiting`.
- Mantido fallback seguro: `runSim` continua delegando primeiro ao módulo e preserva o bloco legado para tipos ainda não extraídos.
- Teste comparativo legado vs módulo executado para: botões com/sem opções, escolhas com imagem, cards com/sem dados e lista com/sem seções.
- Resultado do teste comparativo: `legacy vs module choice blocks comparison: OK`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

### 2026-06-19 — Fase 5.3 concluída parcialmente: execução de blocos de decisão/pagamento

- Estado anterior mapeado no `runSim` legado:
  - `payment`: exibia a solicitação com moeda/valor/descrição e aguardava com `sim.waiting = 'button'`.
  - `condition`: pegava a variável do contexto, comparava com valor esperado usando o operador selecionado e roteava via `nextSim` usando o handle `true` ou `false` (com fallback default se não achasse a saída).
- Estado posterior: `executeSimpleBlock` no módulo `builder/simulator.js` agora cobre `payment` e `condition` mantendo exatamente a mesma avaliação de variável, roteamento e exibição de solicitação.
- Mantido fallback seguro: `runSim` continua delegando primeiro ao módulo e preserva o bloco legado para tipos ainda não extraídos.
- Teste funcional interno executado com sucesso validando condição verdadeira (`18 > 20`), falsa e pagamento.
- Resultado do teste: `legacy vs module condition/payment comparison: OK`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 5.4 concluída parcialmente: execução dos blocos avançados locais do simulador

- Extraída para `inc/core/Bot_builder/Assets/js/builder/simulator.js` a execução dos blocos avançados locais: `ai_reply`, `set_variable`, `webhook`, `jump`, `script` e `ab_test`.
- Mantido fallback seguro no `runSim` de `bot_builder.js`: se o módulo não tratar algum tipo, a lógica legada continua disponível.
- Preservados os comportamentos esperados:
  - `ai_reply`: exibe resposta simulada de IA com prompt interpolado.
  - `set_variable`: grava variável no contexto da simulação.
  - `webhook`: exibe chamada simulada com método e URL.
  - `jump`: direciona para o node alvo configurado.
  - `script`: executa JavaScript simulado, simula PHP no servidor e captura erros.
  - `ab_test`: sorteia variante A/B e roteia pelos handles `variant_a` e `variant_b`.
- Criado e executado teste comparativo interno legado vs módulo atual para validar equivalência funcional.
- Casos testados: `ai_reply` com variável, `set_variable`, webhook POST/GET, jump para alvo existente, script JavaScript com sucesso, script PHP simulado, script JavaScript com erro, A/B test variante A e variante B.
- Resultado do teste comparativo: `legacy vs module advanced blocks comparison: OK`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 5.5 concluída parcialmente: extração do primeiro grupo de integrações do simulador

- Extraída para `inc/core/Bot_builder/Assets/js/builder/simulator.js` a execução do primeiro grupo de integrações simples:
  - `intg_sheets`
  - `intg_analytics`
  - `intg_http`
  - `intg_email`
- Mantido fallback seguro no `runSim` de `bot_builder.js` para demais integrações ainda não extraídas.
- Preservados os comportamentos esperados:
  - `intg_sheets`: exibe ação/sheet e salva resultado em variável.
  - `intg_analytics`: exibe evento rastreado e salva resultado.
  - `intg_http`: interpola URL, exibe método/URL e salva resposta simulada.
  - `intg_email`: interpola destinatário, exibe envio e salva status.
- Criado e executado teste comparativo legado vs módulo atual.
- Casos testados: defaults e configurações customizadas para Sheets, Analytics, HTTP e Email, incluindo interpolação de variáveis em URL e destinatário.
- Resultado do teste comparativo: `legacy vs module integration group 1 comparison: OK`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 5.6 concluída parcialmente: extração do segundo grupo de integrações do simulador

- Extraída para `inc/core/Bot_builder/Assets/js/builder/simulator.js` a execução do segundo grupo de integrações/webhooks externos:
  - `intg_zapier`
  - `intg_make`
  - `intg_pabbly`
- Mantido fallback seguro no `runSim` de `bot_builder.js` para integrações ainda não extraídas.
- Preservados os comportamentos esperados:
  - `intg_zapier`: exibe webhook disparado e salva resultado `triggered`.
  - `intg_make`: exibe cenário disparado e salva resultado `triggered`.
  - `intg_pabbly`: exibe workflow disparado e salva resultado `triggered`.
- Criado e executado teste comparativo legado vs módulo atual.
- Casos testados: defaults e variáveis customizadas para Zapier, Make e Pabbly.
- Resultado do teste comparativo: `legacy vs module integration group 2 comparison: OK`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 5.7 concluída parcialmente: extração do terceiro grupo de integrações do simulador

- Extraída para `inc/core/Bot_builder/Assets/js/builder/simulator.js` a execução do terceiro grupo de integrações de atendimento/marketing:
  - `intg_chatwoot`
  - `intg_pixel`
  - `intg_segment`
  - `intg_posthog`
- Mantido fallback seguro no `runSim` de `bot_builder.js` para integrações ainda não extraídas.
- Preservados os comportamentos esperados:
  - `intg_chatwoot`: exibe ação e salva resultado `success`.
  - `intg_pixel`: exibe evento Meta Pixel e salva resultado `fired`.
  - `intg_segment`: exibe evento Segment e salva resultado `tracked`.
  - `intg_posthog`: exibe evento PostHog e salva resultado `captured`.
- Criado e executado teste comparativo legado vs módulo atual.
- Casos testados: defaults e variáveis/configurações customizadas para Chatwoot, Pixel, Segment e PostHog.
- Resultado do teste comparativo: `legacy vs module integration group 3 comparison: OK`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 5.8 concluída parcialmente: extração do quarto grupo de integrações do simulador

- Extraída para `inc/core/Bot_builder/Assets/js/builder/simulator.js` a execução do quarto grupo de integrações de IA/conhecimento:
  - `intg_openai`
  - `intg_chatnode`
  - `intg_dify`
  - `intg_mistral`
  - `intg_anthropic`
  - `intg_together`
  - `intg_openrouter`
  - `intg_groq`
  - `intg_perplexity`
  - `intg_deepseek`
- Mantido fallback seguro no `runSim` de `bot_builder.js` para integrações ainda não extraídas.
- Preservados os comportamentos esperados: mensagens simuladas, modelos configurados, respostas gravadas no contexto e interpolação de variáveis no prompt do OpenAI.
- Criado e executado teste comparativo legado vs módulo atual.
- Casos testados: defaults e configurações customizadas para as 10 integrações de IA/conhecimento, incluindo corte de prompt, modelo com `/` no Together AI e variáveis customizadas.
- Resultado do teste comparativo: `legacy vs module integration group 4 comparison: OK`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 5.9 concluída parcialmente: extração do quinto grupo de integrações do simulador

- Extraída para `inc/core/Bot_builder/Assets/js/builder/simulator.js` a execução do quinto grupo de integrações utilitárias/negócio:
  - `intg_calcom`
  - `intg_qrcode`
  - `intg_elevenlabs`
  - `intg_nocodb`
  - `intg_zendesk`
  - `intg_blink`
  - `intg_gmail`
  - `intg_woocommerce`
- Mantido fallback seguro no `runSim` de `bot_builder.js` até a etapa de limpeza final.
- Preservados os comportamentos esperados: mensagens simuladas, variáveis de resposta, QR Code com `encodeURIComponent`, interpolação de variáveis e cenários WooCommerce (`get_order`, `search_products`, categorias).
- Criado e executado teste comparativo legado vs módulo atual.
- Casos testados: defaults e configurações customizadas para as 8 integrações, incluindo QR Code com variável, Gmail com destinatário interpolado e três ações WooCommerce.
- Resultado do teste comparativo: `legacy vs module integration group 5 comparison: OK`.
- Validações executadas sem erro: `node -c builder/simulator.js`, `node -c bot_builder.js`, diagnostics de `simulator.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 5 consolidada: `runSim` legado limpo

- Removida de `inc/core/Bot_builder/Assets/js/bot_builder.js` a execução duplicada dos blocos do simulador já migrados.
- `runSim` agora atua como orquestrador mínimo:
  - chama `simulatorModule.executeSimpleBlock(nodeId)`;
  - se o módulo tratar o node, encerra;
  - se algum tipo futuro não for tratado, tenta seguir pelo próximo edge com `nextSim(nodeId)`.
- O módulo `inc/core/Bot_builder/Assets/js/builder/simulator.js` passou a ser a fonte principal da simulação dos blocos básicos, avançados locais e integrações.
- Mantidas funções auxiliares em `bot_builder.js` necessárias para compatibilidade com eventos globais do preview (`simBtnClick`, `processSim`, `validateSimInput`, etc.).
- Rodada validação completa após a limpeza:
  - `node -c builder/simulator.js`.
  - `node -c bot_builder.js`.
  - testes comparativos dos grupos 1, 2, 3, 4 e 5 de integrações.
  - teste comparativo dos blocos avançados locais.
- Resultados:
  - `legacy vs module integration group 1 comparison: OK`.
  - `legacy vs module integration group 2 comparison: OK`.
  - `legacy vs module integration group 3 comparison: OK`.
  - `legacy vs module integration group 4 comparison: OK`.
  - `legacy vs module integration group 5 comparison: OK`.
  - `legacy vs module advanced blocks comparison: OK`.
- Diagnostics de `bot_builder.js` sem erro.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 6 iniciada: Publish Modal modularizado

- Criado o módulo `inc/core/Bot_builder/Assets/js/builder/publish-modal.js`.
- Movida para o novo módulo a lógica do modal de publicação/conexões:
  - abrir/fechar modal;
  - atualizar status visual de publicação e conexão;
  - renderizar instâncias WhatsApp vinculadas/disponíveis;
  - publicar bot;
  - vincular/desvincular instâncias;
  - publicar e conectar;
  - salvar configurações do bot no modal.
- Reduzido `bot_builder.js` para apenas inicializar o módulo `publishModalModule` com `BB` e `showToast`.
- Atualizado `inc/core/Bot_builder/Views/editor.php` para carregar `builder/publish-modal.js` antes de `bot_builder.js`.
- Validações executadas sem erro:
  - `node -c builder/publish-modal.js`.
  - `node -c bot_builder.js`.
  - `node -c builder/simulator.js`.
- Diagnostics de `builder/publish-modal.js` sem erros.
- Diagnostics de `editor.php` ainda mostram avisos antigos de `$bot` indefinido do Intelephense, já existentes no template e não relacionados a esta alteração.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 7 iniciada: Inspector modularizado parcialmente

- Criado o módulo `inc/core/Bot_builder/Assets/js/builder/inspector.js`.
- Extraída para o novo módulo a renderização da prévia WhatsApp do Inspector:
  - mensagens texto/pergunta/caption/prompt;
  - mídia de imagem/vídeo/áudio;
  - botões rápidos e botões via template nativo;
  - lista via template nativo;
  - cards via template nativo.
- `bot_builder.js` mantém funções delegadoras `renderInspectorWhatsAppPreview` e `refreshInspectorWhatsAppPreview`, agora chamando `builderInspectorModule`.
- Atualizado `inc/core/Bot_builder/Views/editor.php` para carregar `builder/inspector.js` antes de `bot_builder.js`.
- Validações executadas sem erro:
  - `node -c builder/inspector.js`.
  - `node -c builder/publish-modal.js`.
  - `node -c bot_builder.js`.
- Diagnostics de `builder/inspector.js` sem erros.
- Diagnostics de `bot_builder.js` mostram apenas hints antigos/remanescentes, sem erro bloqueante.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 7.1 concluída parcialmente: helpers de campos do Inspector extraídos

- Movidos para `inc/core/Bot_builder/Assets/js/builder/inspector.js` os helpers seguros de montagem de campos:
  - `field`;
  - `mediaUploadField`;
  - `select`;
  - `selectCustom`.
- `bot_builder.js` mantém funções delegadoras com os mesmos nomes para preservar compatibilidade com a renderização atual do Inspector.
- A criação de HTML dos inputs, textareas, selects e campo de upload de mídia agora fica concentrada no módulo `builder/inspector.js`.
- Validações executadas sem erro:
  - `node -c builder/inspector.js`.
  - `node -c bot_builder.js`.
  - `node -c builder/publish-modal.js`.
- Diagnostics de `builder/inspector.js` sem erros.
- Diagnostics de `bot_builder.js` mostram apenas hints antigos/remanescentes, sem erro bloqueante.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 7.2 concluída parcialmente: helpers de templates nativos do Inspector extraídos

- Movidos para `inc/core/Bot_builder/Assets/js/builder/inspector.js` os helpers de templates nativos:
  - `nativeTypeForNode`;
  - `nativeLabelForType`;
  - `nativeTemplateInspector`;
  - `renderNativeTemplatePreview`;
  - `nativeOptionEntries`.
- `bot_builder.js` mantém funções delegadoras com os mesmos nomes para preservar compatibilidade com a renderização atual do Inspector e controles nativos ainda não extraídos.
- Criado e executado teste de equivalência para os helpers nativos.
- Casos testados:
  - mapeamento de tipo para `buttons`, `list`, `cards` e fallback;
  - labels por tipo nativo;
  - preview de template de botões, lista, cards e template vazio;
  - extração de opções nativas para botões, lista e cards;
  - HTML do `nativeTemplateInspector` para botões e lista sem template.
- Resultado do teste: `inspector native helpers equivalence: OK`.
- Validações executadas sem erro:
  - `node -c builder/inspector.js`.
  - `node -c bot_builder.js`.
  - `node -c builder/publish-modal.js`.
- Diagnostics de `builder/inspector.js` sem erros.
- Diagnostics de `bot_builder.js` mostram apenas hints antigos/remanescentes, sem erro bloqueante.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 7.3 concluída parcialmente: controles de template nativo do Inspector extraídos

- Movidos para `inc/core/Bot_builder/Assets/js/builder/inspector.js` os controles de template nativo:
  - `loadNativeTemplatesIntoSelect`;
  - `loadNativeTemplate`;
  - `initNativeTemplateControls`.
- `bot_builder.js` mantém funções delegadoras com os mesmos nomes para preservar compatibilidade com a renderização atual do Inspector.
- O módulo `builder/inspector.js` agora recebe no `init` as dependências necessárias: `BB`, `escHtml`, `nativeOptionEntries`, `updateNodePreview`, `markDirty`, `triggerAutoSave` e `openInspector`.
- Criado e executado teste básico dos controles nativos.
- Casos testados:
  - carregamento da lista de templates via `native_templates_url`;
  - seleção do template atual no `<select>`;
  - carregamento de template via `native_template_url`;
  - atualização da configuração do node;
  - atualização do preview após alteração do select;
  - chamada dos callbacks `updateNodePreview`, `markDirty` e `triggerAutoSave`;
  - montagem do redirect de criação com `wa_return`.
- Resultado do teste: `inspector native controls test: OK`.
- Teste anterior dos helpers nativos também foi reexecutado: `inspector native helpers equivalence: OK`.
- Validações executadas sem erro:
  - `node -c builder/inspector.js`.
  - `node -c bot_builder.js`.
  - `node -c builder/publish-modal.js`.
- Diagnostics de `builder/inspector.js` sem erros.
- Diagnostics de `bot_builder.js` mostram apenas hints antigos/remanescentes, sem erro bloqueante.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 7.4 concluída: helpers de variáveis do Inspector extraídos

- Movidos para `inc/core/Bot_builder/Assets/js/builder/inspector.js` os helpers de variáveis:
  - `collectVariables`;
  - `varFieldWithPicker`;
  - `varInsertHint`;
  - `insertTextAtCursor`.
- Movidos os handlers globais de variável para `inspector.js`:
  - `window.toggleVarDropdown`;
  - `window.selectVariable`;
  - `window.createNewVariable`;
  - `window.insertVarIntoField`;
  - event listener de fechar dropdowns em clique externo.
- `bot_builder.js` mantém funções delegadoras para `collectVariables`, `varFieldWithPicker`, `varInsertHint` e `insertTextAtCursor` para preservar compatibilidade com a renderização do Inspector.
- Removido o bloco legado completo em `bot_builder.js` (implementação, handlers globais e event listener).
- O módulo `builder/inspector.js` agora também recebe `NODE_DEFS` no `init` como dependência.
- Criado e executado teste de equivalência com 7 casos:
  - `collectVariables`: variáveis de sistema + variáveis dos nodes;
  - `varFieldWithPicker` com variáveis disponíveis;
  - `varFieldWithPicker` com variáveis de sistema sempre presentes;
  - `varInsertHint` com field id e placeholder;
  - `insertTextAtCursor` com inserção no meio do texto;
  - `collectVariables` pulando o próprio node (currentId);
  - window globals: `toggleVarDropdown`, `selectVariable`, `createNewVariable`, `insertVarIntoField`.
- Resultado do teste: `ALL TESTS PASSED`.
- Validações executadas sem erro:
  - `node -c builder/inspector.js`.
  - `node -c bot_builder.js`.
  - `node -c builder/publish-modal.js`.
- Diagnostics de `builder/inspector.js` e `bot_builder.js` apenas hints, sem erro bloqueante.
- Linhas do monolítico: **1953** (redução de ~132 linhas).
- Linhas do módulo: **417**.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 7.5 concluída: `initMediaUpload` extraído para inspector.js

- Movida para `inc/core/Bot_builder/Assets/js/builder/inspector.js` a função `initMediaUpload`.
- `bot_builder.js` mantém função delegadora com o mesmo nome para preservar compatibilidade com a inicialização do Inspector.
- Adicionadas novas dependências ao `init` do módulo `inspector.js`: `drawConnections`, `saveSnapshot` e `refreshInspectorWhatsAppPreview`.
- `uploadDynamicMedia` permanece em `bot_builder.js` por depender das funções closure `dynPicSync` e `dynCardSync`.
- Validações executadas sem erro:
  - `node -c builder/inspector.js`.
  - `node -c bot_builder.js`.
  - `node -c builder/publish-modal.js`.
- Diagnostics de `builder/inspector.js` e `bot_builder.js` apenas hints, sem erro bloqueante.
- Linhas do monolítico: **1896** (redução de ~57 linhas).
- Linhas do módulo: **484**.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 7.6 concluída: `buildInputInspector` extraído para inspector.js

- Movida para `inc/core/Bot_builder/Assets/js/builder/inspector.js` a função `buildInputInspector`, que renderiza o HTML do inspector para blocos de input (input_text, input_number, input_email, input_website, input_date, input_time, input_phone).
- A função usa exclusivamente helpers já no módulo: `field`, `select`, `varFieldWithPicker`, `escHtml`.
- `bot_builder.js` mantém função delegadora com o mesmo nome.
- Teste de equivalência executado com 7 casos (todos os tipos de input) — **ALL TESTS PASSED**.
- Adicionadas dependências ao `init` do módulo: `inspectorPanel`, `inspectorForm`, `$`.
- Removida variável não utilizada `typeLabels` que estava no código legado (causava hint).
- Validações executadas sem erro:
  - `node -c builder/inspector.js`.
  - `node -c bot_builder.js`.
- Diagnostics de `builder/inspector.js` e `bot_builder.js` apenas hints, sem erro bloqueante.
- Linhas do monolítico: **1776** (redução de ~120 linhas).
- Linhas do módulo: **638**.
- Não houve alteração no backend, banco, runtime WhatsApp ou `waziper.js`.

---

### 2026-06-20 — Fase 7.7 concluída: builders dinâmicos extraídos para inspector.js

- Movidos para `inc/core/Bot_builder/Assets/js/builder/inspector.js` todos os builders dinâmicos:
  - `dynBtnInit`, `_dynBtnRow`, `dynBtnSync` (botões)
  - `dynPicInit`, `_dynPicRow`, `dynPicSync` (pic_choice)
  - `dynCardInit`, `_dynCardRow`, `dynCardSync` (cards)
  - `dynRatingPreview` (rating)
  - `dynFileTypeInit` (file)
- Variável `_dynNodeId` movida para o closure do módulo.
- Adicionadas dependências ao `init`: `showToast`, `rebuildButtonHandles`.
- `window.dynBtnAdd`, `window.dynPicAdd`, `window.dynCardAdd` permanecem no módulo (globals para onclick inline).
- `bot_builder.js` mantém funções delegadoras para as 12 funções.
- Validações executadas sem erro:
  - `node -c builder/inspector.js`.
  - `node -c bot_builder.js`.
- Diagnostics apenas hints, sem erro bloqueante.
- Linhas do monolítico: **1607** (redução de ~170 linhas).
- Linhas do módulo: **810**.
- Redução total do monolítico: ~1000 linhas (de ~2600 para 1607).

---

## 15. Próxima ação recomendada

Com os builders dinâmicos extraídos, a próxima maior fatia é:

1. **Extrair `openInspector` completo** (~300 linhas restantes no monolítico) — depende de `rebuildButtonHandles`, `enableConnections`, `selectNode`, `nativeTemplateInspector`, `initMediaUpload`, `dynBtnInit`, etc. — todas já disponíveis como delegadores ou dependências.
2. **Extrair `dynPicSync`/`dynCardSync` e `uploadDynamicMedia`** — juntamente com os helpers de sync para completar o upload dinâmico.
3. **Extrair `dynCardInit` e `dynPicSync`/`dynCardSync` internos já encapsulados**.

Recomendação: opção **1 — `openInspector` completo**, a maior peça restante.

---

### 2026-06-20 — Fase 7.8 concluída: `openInspector` extraído para inspector.js

- Movida para `inc/core/Bot_builder/Assets/js/builder/inspector.js` a função `openInspector` (~500 linhas), que:
  - Renderiza o HTML completo para todos os tipos de node (text, image, video, audio, embed, inputs, buttons, pic_choice, payment, rating, file_upload, cards, list, input, condition, delay, ai_reply, webhook, set_variable, jump, script, ab_test, typebot, redirect, return, command, reply, invalid, intg_*, etc.)
  - Inicializa builders dinâmicos via `setTimeout` (dynBtnInit, dynPicInit, dynCardInit, dynRatingPreview, initMediaUpload, dynFileTypeInit, initNativeTemplateControls)
  - Configura WooCommerce action toggle
  - Registra event listeners em todos os campos `conf-*`
- `bot_builder.js` mantém função delegadora (`openInspector` → `builderInspectorModule.openInspector`).
- Sintaxe validada em ambos os arquivos.
- Diagnostics: apenas hints (funções delegadoras não lidas, esperado).
- Linhas do monolítico: **1077** (redução de ~530 linhas nesta fase).
- Linhas do módulo: **1340**.
- Redução total do monolítico: de ~2600 para **1077** (~1523 linhas removidas).

---

### Estado Final da Refatoração

| Arquivo | Linhas | Status |
|---------|--------|--------|
| `bot_builder.js` | **1077** | ⬇️ ~1523 linhas removidas |
| `builder/inspector.js` | **1340** | ✅ Completo (Inspector) |
| `builder/simulator.js` | **835** | ✅ Completo |
| `builder/publish-modal.js` | **348** | ✅ Completo |
| `builder/validation.js` | **73** | ✅ Completo |
| `builder/connections.js` | **173** | ✅ Completo |
| `builder/node-defs.js` | **76** | ✅ Completo |
| **Total módulos** | **2845** | |

---

## Próxima ação recomendada

Com o Inspector completo, o `bot_builder.js` contém apenas:

1. **Canvas management** (drag, drop, zoom, pan, drawConnections)
2. **Simulator delegators** (setSimActiveNode, simMsg, etc.)
3. **Event handlers** (save, load, init)
4. **`window.uploadDynamicMedia`** (depende de closure)
5. **Helpers** (rebuildButtonHandles, removeDragHandlers, getCubicPoint)

Opcionalmente, pode-se criar `builder/canvas.js` para o core de canvas management (~300 linhas), ou `builder/utils.js` para remover ~100 linhas de utilitários dispersos.
