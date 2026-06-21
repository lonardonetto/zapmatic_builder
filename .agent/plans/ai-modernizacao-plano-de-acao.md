# Plano de Ação - Modernização da IA

> Operação: atualização da camada de IA sem quebrar o sistema  
> Estratégia: evolução isolada, por fases, com gate de teste obrigatório ao final de cada etapa  
> Princípio central: o sistema deve permanecer funcional durante toda a operação

---

## 1. Objetivo do plano

Modernizar a camada de IA do sistema para suportar:

- OpenAI atual;
- Gemini;
- OpenRouter;
- leitura de imagens recebidas;
- leitura de áudios recebidos;
- evolução futura de chatbot, autoresponder e bulk sem regressão funcional.

Este plano foi desenhado para:

- reduzir risco de quebra;
- permitir rollback por fase;
- documentar cada avanço;
- garantir teste obrigatório entre fases.

---

## 2. Diretrizes da operação

### 2.1. Regras obrigatórias

- Nenhuma fase avança sem parecer técnico registrado.
- Toda fase deve atualizar o documento de progresso.
- Toda fase deve rodar o checklist mínimo de regressão.
- Nenhuma fase pode quebrar chatbot, autoresponder, bulk, live chat ou OpenAI administrativo.
- Mudanças de banco devem ser pequenas, versionadas e reversíveis sempre que possível.

### 2.2. Estratégia de implantação

- trabalhar em camadas;
- preservar o fluxo atual enquanto o novo fluxo é introduzido;
- evitar big bang migration;
- preferir feature flags e compatibilidade temporária;
- separar infraestrutura, adapter, schema, multimodal e rollout de uso.

---

## 3. Arquitetura-alvo resumida

### 3.1. Camadas desejadas

1. **Camada de entrada**
   - Baileys
   - API Oficial

2. **Camada de normalização**
   - texto
   - imagem
   - vídeo
   - áudio
   - metadata do contato

3. **Camada de decisão**
   - chatbot
   - autoresponder
   - bulk
   - regras de uso de IA

4. **Camada de provider**
   - OpenAI
   - Gemini
   - OpenRouter

5. **Camada de contexto**
   - memória curta em cache
   - persistência opcional em banco

6. **Camada de observabilidade**
   - logs
   - consumo
   - falhas
   - testes

### 3.2. Papel de cada provedor

- **OpenAI**: provedor premium e padrão inicial de produção.
- **Gemini**: alternativa forte para multimodal e escala.
- **OpenRouter**: roteamento e fallback opcional, não como contrato principal do sistema.

---

## 4. Plano por fases

## Fase 0 - Governança, baseline e documentação

### Objetivo

Criar a base operacional da modernização.

### Escopo

- documento de funcionamento atual;
- plano de ação oficial;
- documento de progresso;
- definição do gate de testes.

### Entrega esperada

- operação documentada;
- baseline técnica fechada;
- trilha oficial para registrar mudanças futuras.

### Banco

- sem mudança.

### Risco

- baixo.

### Gate de saída

- documentos criados e revisados;
- sem alteração funcional no sistema.

---

## Fase 1 - Observabilidade e testes de segurança operacional

### Objetivo

Criar base de verificação para não voar no escuro durante a modernização.

### Escopo

- padronizar logs do motor de IA;
- mapear erros por provedor;
- criar checklist de teste repetível;
- validar smoke tests do estado atual.

### Entrega esperada

- logs minimamente confiáveis;
- checklist técnico de regressão;
- baseline funcional documentada.

### Banco

- preferencialmente sem mudança.

### Risco

- baixo.

### Gate de saída

- chatbot atual validado;
- autoresponder atual validado;
- bulk atual validado;
- OpenAI administrativo validado.

---

## Fase 2 - Adapter de provedor sem mudar comportamento externo

### Objetivo

Remover o acoplamento direto de OpenAI do runtime sem quebrar o fluxo atual.

### Escopo

- introduzir camada `provider adapter`;
- manter OpenAI como provedor único inicial por baixo do adapter;
- preservar `use_ai`, `status`, `model`, `max_tokens` e o fluxo do chatbot.

### Entrega esperada

- runtime deixa de depender de OpenAI diretamente na regra de negócio;
- comportamento funcional do chatbot continua igual.

### Banco

- sem mudança obrigatória nesta fase, se possível.

### Risco

- médio.

### Gate de saída

- chatbot por keyword e `nextBot` funcionando;
- modo sem IA funcionando;
- modo default funcionando;
- nenhuma regressão em Baileys e API Oficial.

---

## Fase 3 - Normalização de schema e configuração

### Objetivo

Corrigir o contrato interno da IA e preparar o terreno para múltiplos provedores.

### Escopo

- revisar `sp_whatsapp_ai`;
- adicionar campos neutros de provedor;
- resolver o gap do `main_prompt/system_prompt`;
- preparar estrutura para capacidades multimodais;
- revisar UI de configuração do chatbot.

### Campos candidatos

- `provider`
- `model`
- `system_prompt`
- `provider_options`
- `vision_enabled`
- `audio_enabled`
- `transcribe_provider`
- `transcribe_model`
- `fallback_chain`
- `context_mode`
- `context_window`
- `context_ttl_minutes`

### Entrega esperada

- schema consistente;
- UI coerente com o runtime;
- config preparada para expansão.

### Banco

- sim, com migration controlada.

### Risco

- médio.

### Gate de saída

- salvar configuração continua funcionando;
- chatbot continua respondendo;
- configs antigas continuam compatíveis;
- sem perda das contas já configuradas.

---

## Fase 4 - Persistência opcional de contexto em banco

### Objetivo

Introduzir persistência controlada de contexto para reduzir perda após restart.

### Parecer

**Viável e recomendado**, mas em modelo híbrido.

### Estratégia recomendada

- memória continua sendo cache rápido;
- banco guarda apenas janela curta;
- TTL obrigatório;
- limite de mensagens por conversa;
- possibilidade de desligar por conta/plano no futuro.

### Estrutura sugerida

- `sp_whatsapp_ai_sessions`
- `sp_whatsapp_ai_messages`

### Benefícios

- continuidade de conversa;
- troubleshooting;
- auditoria;
- base para recursos premium.

### Risco

- médio.

### Gate de saída

- reset/restart do Node não destrói toda a continuidade;
- sem crescimento descontrolado de banco;
- sem impacto perceptível de latência.

---

## Fase 5 - Modernização do OpenAI

### Objetivo

Atualizar o provedor OpenAI para modelos e APIs atuais, mantendo compatibilidade.

### Escopo

- runtime migrado para contrato atual do provedor;
- módulo administrativo atualizado;
- retirada gradual de modelos legados;
- atualização de listas de modelos e configurações.

### Entrega esperada

- OpenAI moderno em runtime e admin;
- fim do legado mais crítico;
- base pronta para multimodal OpenAI.

### Banco

- sem mudança grande, salvo ajuste de config.

### Risco

- médio.

### Gate de saída

- chatbot IA com OpenAI funcionando;
- OpenAI administrativo texto funcionando;
- OpenAI administrativo imagem funcionando;
- consumo/erros observáveis.

---

## Fase 6 - Adição do Gemini direto

### Objetivo

Adicionar Gemini como provedor direto, sem depender do OpenRouter.

### Escopo

- adapter Gemini;
- seleção de provedor na conta;
- modelos por perfil de uso;
- tratamento de resposta compatível com o contrato interno.

### Entrega esperada

- chatbot operando com Gemini;
- configuração por instância disponível;
- fallback manual possível.

### Banco

- sem mudança grande, se schema da fase 3 estiver pronto.

### Risco

- médio.

### Gate de saída

- mesma conversa básica funcionando com Gemini;
- sem quebrar OpenAI;
- mesma regra de keyword, `nextBot`, default e disable keyword.

---

## Fase 7 - Adição do OpenRouter opcional

### Objetivo

Adicionar roteamento e fallback controlado via OpenRouter.

### Escopo

- provider OpenRouter;
- configuração opcional por conta;
- fallback chain controlada;
- logs por provedor real utilizado.

### Entrega esperada

- camada opcional de roteamento;
- fallback seguro;
- visibilidade de qual modelo respondeu.

### Banco

- aproveita schema da fase 3.

### Risco

- médio para alto.

### Gate de saída

- OpenRouter responde sem quebrar contratos;
- fallback documentado e testado;
- OpenAI e Gemini continuam operando normalmente.

---

## Fase 8 - Pipeline multimodal: imagem e áudio

### Objetivo

Permitir leitura de mídia recebida sem quebrar o fluxo atual.

### Escopo

- estabilizar extração/download de mídia;
- criar pipeline de visão para imagens;
- criar pipeline de transcrição para áudio;
- padronizar saída para o motor conversacional.

### Estratégia recomendada

- imagem: enviar mídia + legenda + contexto ao provedor;
- áudio: transcrever primeiro, responder depois;
- persistir metadata mínima da mídia processada.

### Entrega esperada

- chatbot entende imagem recebida;
- chatbot entende áudio por transcrição;
- sem regressão para mensagens sem mídia.

### Banco

- possivelmente tabelas auxiliares para cache/transcrição, se necessário.

### Risco

- alto.

### Gate de saída

- imagem com legenda: ok;
- imagem sem legenda: comportamento controlado;
- áudio: transcrição ok;
- API Oficial e Baileys com o mesmo contrato funcional.

---

## Fase 9 - IA no autoresponder

### Objetivo

Expandir IA para autoresponder de forma segura.

### Escopo

- definir quando autoresponder pode usar IA;
- manter `delay`, `dedupe`, `send_to` e `except`;
- impedir loops e respostas excessivas.

### Entrega esperada

- autoresponder com IA opcional;
- sem flood;
- sem quebrar o modo não-IA.

### Risco

- alto.

### Gate de saída

- autoresponder clássico continua funcionando;
- autoresponder com IA responde dentro das regras;
- sem duplicidade e sem respostas fora da janela.

---

## Fase 10 - IA no bulk

### Objetivo

Expandir IA para bulk apenas depois do motor estar estável.

### Escopo

- IA opcional para personalização controlada;
- política de custo/limite por campanha;
- proteção contra respostas incoerentes em escala;
- logs por envio.

### Entrega esperada

- bulk com IA sob controle;
- limite de risco operacional;
- visibilidade de custo e falha.

### Risco

- muito alto.

### Gate de saída

- campanha pequena validada;
- template clássico continua funcionando;
- IA não quebra fila, agendamento ou estatísticas.

---

## 5. Ordem recomendada de execução

Ordem oficial recomendada:

1. Fase 0
2. Fase 1
3. Fase 2
4. Fase 3
5. Fase 4
6. Fase 5
7. Fase 6
8. Fase 7
9. Fase 8
10. Fase 9
11. Fase 10

Justificativa:

- primeiro controlar risco;
- depois desacoplar;
- depois corrigir schema;
- depois modernizar provedor principal;
- depois expandir;
- só por último tocar em autoresponder e bulk com IA.

---

## 6. Gate de testes obrigatórios por etapa

Ao final de cada fase com alteração de código, rodar no mínimo:

### 6.1. Chatbot

- keyword exata;
- keyword parcial;
- default bot;
- `nextBot`;
- item com `use_ai = 0`;
- item com `use_ai = 1`.

### 6.2. Entrada de canais

- Baileys com texto;
- API Oficial com texto;
- quando a fase envolver mídia:
  - imagem com legenda;
  - imagem sem legenda;
  - áudio;
  - vídeo com legenda.

### 6.3. Autoresponder

- resposta simples;
- dedupe por mensagem;
- delay;
- `except`;
- `send_to`.

### 6.4. Bulk

- campanha simples;
- próxima execução;
- contadores `sent/failed`;
- fila não travada.

### 6.5. OpenAI administrativo

- geração de texto;
- geração de imagem;
- gravação e leitura de configuração.

### 6.6. Regressão estrutural

- logs sem erro crítico novo;
- sem perda de sessão/conexão;
- sem quebra de telas de configuração;
- sintaxe do código alterado validada.

---

## 7. Documento de avanço obrigatório

A cada fase concluída, atualizar:

- status da fase;
- objetivo;
- arquivos alterados;
- banco alterado ou não;
- testes executados;
- resultado dos testes;
- parecer técnico;
- decisão de avançar ou não.

Documento oficial:

- [ai-modernizacao-progresso.md](/www/wwwroot/app_zapmatic_app/.agent/plans/ai-modernizacao-progresso.md)

---

## 8. Critério de avanço entre fases

Uma fase só pode avançar quando:

- os testes mínimos passarem;
- o parecer técnico for favorável;
- o documento de progresso for atualizado;
- não houver regressão crítica em chatbot, autoresponder, bulk ou OpenAI admin.

Se falhar:

- registrar o problema;
- corrigir dentro da mesma fase;
- repetir testes;
- só então avaliar avanço.

---

## 9. Decisão sobre contexto em banco

Parecer oficial:

- **sim, vamos considerar contexto em banco**;
- **modelo recomendado: híbrido**;
- **fase recomendada: Fase 4**.

Não fazer isso antes da Fase 3, porque:

- o schema atual ainda está desalinhado;
- o adapter ainda não estará estabilizado;
- seria prematuro persistir um contrato interno ainda não consolidado.

---

## 10. Conclusão operacional

Este plano foi desenhado para modernizar a IA com segurança.

Ele evita:

- troca brusca de provedor;
- acoplamento novo em cima de base antiga;
- expansão de IA para autoresponder e bulk antes da hora.

Ele prioriza:

- estabilidade do sistema;
- documentação contínua;
- testes obrigatórios;
- avanço controlado por fases.
