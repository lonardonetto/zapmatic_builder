# Roadmap Completo — Flow Builder Zapmatic rumo a Typebot WhatsApp-native

Status: planejamento
Data: 2026-06-17

## 1. Contexto histórico

O Flow Builder do Zapmatic evoluiu bastante nas últimas etapas. As decisões mais importantes já consolidadas são:

1. O Builder deve ser o motor visual principal do sistema.
2. O chatbot antigo deve ser gradualmente ocultado/desativado, sem quebrar rotas existentes.
3. Não devemos transformar `Bot_builder.php` nem `bot_builder.js` em novos arquivos monolíticos como o `waziper.js`.
4. O `waziper.js` deve ser tratado como camada sensível de transporte WhatsApp/Baileys/Cloud API. Novas features do Builder devem evitar mexer nele.
5. Para templates nativos WhatsApp, o Builder deve reutilizar o caminho já estável do sistema.

## 2. Decisões técnicas já validadas

### 2.1 Carrossel nativo no Flow Builder

Problema resolvido:

- O carrossel enviado pelo Flow Builder chegava no WhatsApp Web, mas não chegava no mobile/iOS.
- A causa foi o envio via `bot_builder_send` com payload manual de cards.
- A solução correta foi usar o mesmo caminho do Single/Bulk/Autoresponder: `direct_send_message` com `type=5` e `template` ID.

Regra definitiva:

```text
Flow Builder + template nativo de carrossel = direct_send_message(type=5, template=ID)
```

Não usar novamente:

- `relayMessage`
- `generateWAMessageFromContent`
- `viewOnceMessage`
- payload manual de carrossel dentro do Flow Builder

Documento relacionado:

```text
debug-flow-carousel-mobile.md
```

### 2.2 Templates nativos globais

O Builder passou a usar templates nativos globais para:

- Botões — type 2
- Lista/Menu — type 1
- Cards/Carrossel — type 5

Isso evita duplicar UI/CRUD de botões/listas/cards dentro do Builder.

### 2.3 Criar novo / Editar templates no Flow

Foi validado o fluxo:

```text
Flow Builder → Criar novo/Editar template → Salvar → Voltar ao Flow Builder
```

Rotas corretas:

```text
whatsapp_list_message_template/index/update
whatsapp_button_template/index/update
whatsapp_carousel_template/index/update
```

Com retorno via:

```text
wa_return
```

### 2.4 Organização visual já implementada

Já foi implementado:

- Nome interno do bloco (`node.config.label`)
- Exibição do nome no card do node
- Handles/saídas mais legíveis para botões/lista/cards
- Prévia compacta dos templates nativos no inspector

Essas melhorias deixam o Builder mais próximo de ferramentas como Typebot e BotConversa.

## 3. Estado atual do Flow Builder

### 3.1 Nodes básicos existentes

Já existem nodes para:

- Início
- Fim
- Texto
- Imagem
- Vídeo
- Áudio
- Incorporar/link/embed
- Delay/esperar
- Condição
- Definir variável
- Jump/pular
- Script
- Teste A/B
- Retornar
- Redirecionar
- Comando
- Resposta específica
- Inválido/fallback

### 3.2 Inputs existentes

Já existem inputs estruturados:

- Entrada de texto
- Entrada numérica
- Entrada de e-mail
- Entrada de site/URL
- Entrada de data
- Entrada de hora
- Entrada de telefone
- Upload de arquivo
- Avaliação/rating
- Escolha com imagem

Esses recursos já cobrem parte importante do que Typebot oferece.

### 3.3 WhatsApp-native existente

Já existe suporte ou base para:

- Texto
- Imagem
- Vídeo
- Áudio
- Botões nativos
- Lista/Menu nativo
- Carrossel nativo
- Templates globais
- Fluxo usando Baileys/Cloud via backend existente

Esse é um diferencial importante em relação a builders genéricos.

### 3.4 Integrações existentes

O frontend já lista muitas integrações:

- Requisição HTTP
- E-mail
- Zapier
- Make
- Pabbly
- Google Sheets
- Google Analytics
- Meta Pixel
- OpenAI
- Anthropic
- Mistral
- Groq
- DeepSeek
- Perplexity
- Together
- OpenRouter
- Dify
- ChatNode
- ElevenLabs
- Cal.com
- QR Code
- NocoDB
- Segment
- Zendesk
- PostHog
- Blink
- Gmail
- WooCommerce

Mas nem todas estão completas no backend.

## 4. Comparação com Typebot

## 4.1 Recursos que já temos ou temos parcialmente

| Área | Typebot | Zapmatic atual | Status |
|---|---|---|---|
| Builder visual | Sim | Sim | Parcial/bom |
| Nodes de texto/mídia | Sim | Sim | Bom |
| Inputs estruturados | Sim | Sim | Bom |
| Variáveis | Sim | Sim via contexto | Parcial |
| Condições | Sim | Sim | Parcial |
| Webhook/HTTP | Sim | Sim | Bom |
| Integrações de IA | Sim/parcial | Muitas | Parcial/bom |
| Templates reutilizáveis | Sim | Templates WhatsApp nativos | Bom no WhatsApp |
| Simulador | Sim | Não completo | Falta |
| Debug visual | Sim | Não completo | Falta |
| Analytics por node | Sim | Não completo | Falta |
| Versionamento | Sim | Parcial/publicado | Falta amadurecer |
| Organização visual | Sim | Em evolução | Parcial/bom |
| WhatsApp nativo | Limitado | Forte | Diferencial Zapmatic |

## 4.2 Recursos em que Zapmatic pode superar Typebot

O Zapmatic pode ser melhor que Typebot em WhatsApp se focar em:

1. Templates nativos WhatsApp
2. Botões/listas/carrossel reais
3. Baileys + Cloud API
4. Integração com campanhas/bulk/single/autoresponder
5. Controle de janela 24h
6. Handoff para atendimento humano
7. Métricas específicas de WhatsApp
8. Respostas nativas de botão/lista/carrossel

## 5. Lacunas principais

## 5.1 Variáveis

Já existe contexto e `set_variable`, mas falta uma experiência profissional.

Faltam:

- Painel global de variáveis
- Lista de variáveis disponíveis
- Detecção de variáveis usadas
- Validação de variável inexistente
- Transformações de variável
- Tipagem básica: texto, número, booleano, data, JSON
- Preview de mensagens com variáveis preenchidas

## 5.2 Condições avançadas

Existe node de condição, mas pode evoluir.

Faltam operadores:

- contém
- não contém
- começa com
- termina com
- maior/menor
- vazio/não vazio
- regex
- data antes/depois
- número entre

Falta também:

- múltiplas regras AND/OR
- switch node
- rota default obrigatória

## 5.3 Simulador e debug

Esse é um dos pontos mais importantes para ficar nível Typebot.

Faltam:

- Simulador de conversa no editor
- Execução passo a passo
- Highlight do node atual
- Histórico de execução
- Visualização de variáveis em tempo real
- Logs por conversa
- Debug de integração

## 5.4 Analytics

Faltam métricas de fluxo:

- Execuções por node
- Cliques por botão
- Conversão por caminho
- Abandono/drop-off
- Tempo médio até conversão
- Exportação de leads/variáveis

## 5.5 Versionamento/publicação

Existe ideia de publicado, mas precisa amadurecer.

Faltam:

- Rascunho vs publicado real
- Histórico de versões
- Restaurar versão
- Comparar versões
- Checklist antes de publicar

## 5.6 Integrações incompletas

Algumas integrações aparecem completas na UI, mas o backend ainda é parcial.

Prioridade para amadurecer:

1. SMTP/E-mail real
2. Cal.com completo
3. Google Agenda
4. Google Sheets real
5. Chatwoot
6. Zendesk
7. Google Analytics
8. Meta Pixel

## 6. Regra arquitetural obrigatória

## 6.1 Não mexer no waziper para features do Builder

Regra:

```text
Novas features do Flow Builder devem ser implementadas no PHP/Builder ou em services próprios.
O waziper.js só deve ser alterado quando o problema for transporte WhatsApp real.
```

Motivo:

- `waziper.js` é grande e sensível.
- Já houve instabilidade ao tentar forçar carrossel manual por Baileys.
- O caminho correto é reutilizar endpoints nativos existentes (`direct_send_message`) quando possível.

## 6.2 Não criar novos monólitos

Não concentrar tudo em:

```text
Bot_builder.php
bot_builder.js
```

Regra prática:

- Arquivos novos de frontend: preferencialmente até 300–600 linhas.
- Services PHP: preferencialmente até 300–700 linhas.
- Se uma feature passar de 150–200 linhas novas, considerar arquivo próprio.

## 7. Arquitetura modular recomendada

## 7.1 Backend PHP

Criar gradualmente:

```text
inc/core/Bot_builder/Services/
├── FlowRunner.php
├── FlowSessionService.php
├── FlowVariableService.php
├── FlowConditionService.php
├── FlowSenderService.php
├── NativeTemplateService.php
├── IntegrationRunner.php
├── IntegrationHttpService.php
├── IntegrationAiService.php
├── IntegrationCalendarService.php
├── FlowAnalyticsService.php
├── FlowVersionService.php
└── FlowDebugService.php
```

### Responsabilidades

#### FlowRunner.php
Executa o fluxo e decide próximo node.

#### FlowVariableService.php
Gerencia variáveis, transformação e validação.

#### FlowConditionService.php
Avalia condições avançadas.

#### FlowSenderService.php
Centraliza envio via caminhos nativos existentes.

#### NativeTemplateService.php
Cuida de templates type 1, 2 e 5.

#### IntegrationRunner.php
Despacha integrações para services específicos.

#### FlowDebugService.php
Registra execução para simulador/debug.

#### FlowAnalyticsService.php
Registra views, cliques, conversões e drop-off.

## 7.2 Frontend JS

Dividir gradualmente:

```text
inc/core/Bot_builder/Assets/js/builder/
├── app.js
├── state.js
├── canvas.js
├── nodes.js
├── node-renderer.js
├── inspector.js
├── native-templates.js
├── handles.js
├── variables.js
├── conditions.js
├── simulator.js
├── analytics.js
├── integrations.js
├── autosave.js
└── utils.js
```

O `bot_builder.js` atual pode continuar por enquanto, mas novas features maiores devem nascer em módulos.

## 8. Plano de implementação por fases

## Fase 1 — Organização e UX do Builder

Status: parcialmente iniciado.

Já feito:

- Nome do bloco
- Handles legíveis
- Prévia compacta de template
- Criar/editar templates nativos com retorno ao Flow

Próximos itens:

1. Busca no fluxo
2. Comentários/notas internas
3. Grupos visuais
4. Cores por grupo/node
5. Mini-mapa
6. Auto-organizar fluxo

### Arquivos sugeridos

Frontend:

```text
Assets/js/builder/search.js
Assets/js/builder/groups.js
Assets/js/builder/notes.js
Assets/js/builder/minimap.js
```

Backend: somente se precisar persistência extra.

## Fase 2 — Variáveis profissionais

Objetivo:

Transformar variáveis em recurso central do Builder.

Itens:

1. Painel de variáveis
2. Autocomplete de `{{variavel}}`
3. Node Set Variable avançado
4. Transformações:
   - uppercase
   - lowercase
   - trim
   - number
   - date format
   - JSON path
5. Validador de variáveis inexistentes
6. Preview com dados fake

### Arquivos sugeridos

```text
Services/FlowVariableService.php
Assets/js/builder/variables.js
```

## Fase 3 — Condições avançadas

Itens:

1. Operadores avançados
2. AND/OR
3. Switch node
4. Condição por data/número/texto
5. Saída default obrigatória
6. Validação visual de condição incompleta

### Arquivos sugeridos

```text
Services/FlowConditionService.php
Assets/js/builder/conditions.js
```

## Fase 4 — Simulador e debug

Itens:

1. Painel “Testar fluxo”
2. Simulação de conversa
3. Highlight do node atual
4. Histórico de execução
5. Variáveis em tempo real
6. Debug de integração
7. Reexecutar step

### Arquivos sugeridos

```text
Services/FlowDebugService.php
Assets/js/builder/simulator.js
```

Banco sugerido:

```text
sp_bot_builder_run_logs
```

Campos:

- id
- bot_id
- session_id
- contact
- node_id
- node_type
- input
- output
- context_json
- status
- error
- created_at

## Fase 5 — Integrações maduras

Prioridade:

1. HTTP avançado
2. SMTP real
3. Cal.com completo
4. Google Agenda
5. Google Sheets real
6. Chatwoot/Zendesk

### HTTP avançado

Adicionar:

- Testar endpoint no editor
- Mapear JSON response para variável
- Exibir status code
- Exibir erro

### SMTP real

Substituir `mail()` por biblioteca real.

Sugestão:

- PHPMailer
- Symfony Mailer

### Cal.com completo

Adicionar campos:

- start time
- timezone
- attendee name
- attendee email
- attendee phone
- booking uid
- cancel reason

### Google Agenda

Criar service separado:

```text
Services/IntegrationCalendarService.php
```

Ações:

- conectar conta
- listar calendários
- consultar disponibilidade
- criar evento
- cancelar evento
- atualizar evento

## Fase 6 — Analytics e conversão

Itens:

1. Contagem por node
2. Cliques por saída
3. Drop-off
4. Conversão por objetivo
5. Exportação CSV
6. Métricas no canvas

### Arquivos sugeridos

```text
Services/FlowAnalyticsService.php
Assets/js/builder/analytics.js
```

Banco sugerido:

```text
sp_bot_builder_events
```

Campos:

- id
- bot_id
- session_id
- node_id
- event_type
- handle
- metadata_json
- created_at

## Fase 7 — Versionamento/publicação

Itens:

1. Rascunho separado da versão publicada
2. Publicar versão
3. Histórico
4. Restaurar versão
5. Comparar versão
6. Checklist de publicação

Banco sugerido:

```text
sp_bot_builder_versions
```

Campos:

- id
- bot_id
- version
- nodes_json
- edges_json
- status
- created_by
- created_at
- published_at

## Fase 8 — Handoff e atendimento humano

Itens:

1. Node “Transferir para humano”
2. Pausar automação
3. Retomar automação
4. Atribuir atendente
5. Adicionar tag
6. Integrar Chatwoot/inbox

## 9. Revisão das integrações atuais

## 9.1 Boas para uso básico

- Requisição HTTP
- Zapier
- Make
- Pabbly
- OpenAI
- Anthropic
- Mistral/Groq/DeepSeek/Perplexity/Together/OpenRouter com ressalvas
- QR Code
- WooCommerce, se configurado
- PostHog básico

## 9.2 Parciais

- Google Sheets
- Google Analytics
- Meta Pixel
- Chatwoot
- ElevenLabs
- Cal.com
- ChatNode
- Dify
- NocoDB
- Segment
- Zendesk
- Gmail
- Blink

## 9.3 Incompleta/enganosa

- E-mail SMTP: a UI pede SMTP, mas o backend usa `mail()`.

Ação recomendada:

- Marcar integrações parciais como “Beta”.
- Priorizar completar poucas integrações com qualidade.

## 10. Ordem recomendada de execução

## Curto prazo

1. Busca no fluxo
2. Comentários/notas
3. Painel de variáveis
4. Validador básico de fluxo
5. Marcar integrações Beta

## Médio prazo

1. Condições avançadas
2. Simulador interno
3. HTTP com teste e mapeamento JSON
4. SMTP real
5. Cal.com completo

## Longo prazo

1. Google Agenda
2. Google Sheets real
3. Analytics por node
4. Versionamento completo
5. Handoff humano
6. Subflows/componentes reutilizáveis

## 11. Checklist obrigatório para cada nova feature

Antes de implementar:

- A feature exige mexer no `waziper.js`?
  - Se sim, reavaliar.
- Pode ser implementada em service PHP?
- Pode ser implementada em módulo JS separado?
- Vai aumentar muito `Bot_builder.php` ou `bot_builder.js`?
- Tem fallback para fluxos antigos?
- Tem validação de sintaxe?
- Tem impacto no envio WhatsApp?

Regra:

```text
Não criar novos monólitos.
Não mexer no waziper para features visuais/lógicas do Builder.
Preferir services e módulos pequenos.
```

## 12. Conclusão

O Zapmatic já possui uma base forte e diferenciada por ser WhatsApp-native. Para chegar ao nível Typebot, o foco deve ser:

1. Melhorar organização visual.
2. Profissionalizar variáveis.
3. Amadurecer condições.
4. Criar simulador/debug.
5. Completar poucas integrações essenciais com qualidade.
6. Adicionar analytics e versionamento.

O caminho correto é evoluir incrementalmente, mantendo o `waziper.js` estável e quebrando o Builder em services/módulos menores conforme novas features forem criadas.
