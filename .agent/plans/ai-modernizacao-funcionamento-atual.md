# Documento de Funcionamento Atual

> Sistema: Zapmatic  
> Escopo: motor de IA, chatbot, autoresponder, bulk, entrada de mídia e módulo administrativo de OpenAI  
> Objetivo: registrar como o sistema funciona hoje antes da modernização da camada de IA

---

## 1. Objetivo deste documento

Este documento descreve a lógica atual do sistema para servir como:

- linha de base técnica antes da atualização da IA;
- referência de regressão durante a implementação;
- apoio para auditoria de cada etapa do plano;
- base do documento de progresso da operação.

Este documento não propõe mudanças. Ele registra o estado atual.

---

## 2. Visão geral do motor atual

Hoje o sistema possui duas camadas distintas de IA:

1. **IA de atendimento via WhatsApp**
   - Executa no Node.js.
   - Está concentrada principalmente em [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:967) e [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2076).
   - Usa OpenAI de forma direta.

2. **IA administrativa/manual**
   - Executa no PHP.
   - Está concentrada em [Openai_helper.php](/www/wwwroot/app_zapmatic_app/inc/core/Openai/Helpers/Openai_helper.php:3) e [Openai.php](/www/wwwroot/app_zapmatic_app/inc/core/Openai/Controllers/Openai.php:26).
   - Também é OpenAI-only.

Conclusão do estado atual:

- O sistema ainda não possui camada neutra de provedor.
- O motor de atendimento ainda não suporta Gemini nem OpenRouter.
- O tratamento multimodal ainda é parcial.

---

## 3. Entrada de mensagens

### 3.1. Entrada via Baileys

Mensagens recebidas pelo canal Baileys passam pelo seguinte fluxo:

- ao receber mensagem de terceiros, o sistema chama `WAZIPER.chatbot(...)` em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:901);
- após um `sleep`, chama `WAZIPER.autoresponder(...)` em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:903).

Resumo:

- chatbot roda primeiro;
- autoresponder roda depois;
- ambos recebem a mesma mensagem base.

### 3.2. Entrada via API Oficial

Mensagens recebidas pela API Oficial passam por:

- normalização em [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:1265);
- processamento de live chat em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2536);
- depois `WAZIPER.chatbot(...)` em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2541);
- e `WAZIPER.autoresponder(...)` em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2543).

Resumo:

- Baileys e API Oficial convergem para o mesmo motor de chatbot/autoresponder;
- a diferença principal está na normalização da mensagem e da mídia.

---

## 4. Chatbot atual

### 4.1. Leitura do conteúdo recebido

O chatbot extrai o conteúdo do inbound em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2161).

Hoje ele trata:

- resposta de botão;
- resposta de template;
- resposta de lista;
- texto simples;
- imagem com legenda;
- sticker com legenda;
- vídeo com legenda;
- áudio com legenda;
- conversa simples.

Quando não há legenda/texto, ele converte para placeholders:

- imagem sem legenda: `📷`
- vídeo sem legenda: `📹`
- áudio sem legenda: `🎧`
- mensagem vazia: `👋`

Implicação atual:

- o motor consegue reagir a mídia apenas se houver legenda útil;
- áudio não é transcrito hoje;
- imagem não passa por visão computacional hoje.

### 4.2. Seleção de regra do bot

Depois de montar o `content`, o motor busca os bots ativos em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2246).

A seleção da melhor regra acontece em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2094).

Regras atuais:

- `type_search = 2`: match exato;
- outros tipos: match parcial por `contains`;
- prioridade maior para match exato;
- se empatar, vence a keyword mais longa;
- se empatar novamente, vence o menor `id`.

Depois do match:

- o item é enviado por `WAZIPER.auto_send(...)` em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2269);
- se existir `nextBot` e `save_data != 2`, o sistema encadeia o próximo passo em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2270).

Se não houver match:

- o sistema procura o bot default em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2282);
- se existir, envia por `auto_send(...)`.

### 4.3. Uso de IA no chatbot

O uso de IA só entra no caminho `chatbot` quando:

- o item do bot tem `use_ai = 1`;
- o tipo do envio recebido por `auto_send()` é `chatbot`.

Isso está travado em [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:970).

Conclusão operacional:

- hoje apenas o chatbot usa IA;
- autoresponder e bulk não usam IA como primeiro-class citizen;
- qualquer expansão para IA nesses fluxos exigirá mudança de contrato interno.

---

## 5. Motor de IA de atendimento atual

### 5.1. Origem da configuração

O runtime carrega a configuração da IA em `sp_whatsapp_ai`:

- leitura em [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:971);
- tabela definida por [Constants.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Config/Constants.php:4).

Campos atuais da tabela `sp_whatsapp_ai`:

- `team_id`
- `instance_id`
- `status`
- `apikey`
- `temperature`
- `model`
- `key_disable`
- `key_enable`
- `max_tokens`
- `api_status`

Base atual observada:

- `7` configurações em `sp_whatsapp_ai`;
- modelos em uso: `gpt-4-turbo` e `gpt-3.5-turbo`.

### 5.2. Execução do provedor

Hoje o motor:

- instancia `new OpenAI(...)` em [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:980);
- monta `messages` com histórico em memória;
- chama `openai.chat.completions.create(...)` em [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:1018).

Características atuais:

- provedor único: OpenAI;
- endpoint atual: `chat.completions`;
- não existe adapter;
- não existe fallback entre provedores.

### 5.3. Histórico de contexto

O histórico da conversa fica em memória:

- `OpenAi_History_Chat`
- `OpenAi_Chats_Ids`

O reset manual ocorre em [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:957).

Estado atual:

- o contexto morre quando o processo Node reinicia;
- não existe persistência em banco;
- não existe TTL formal no banco para contexto;
- não existe auditoria centralizada de prompts e respostas.

### 5.4. Inconsistência identificada

O runtime usa `ai_item.main_prompt` em [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:985), mas:

- a tabela `sp_whatsapp_ai` não tem essa coluna hoje;
- o controller de save não persiste prompt de sistema em [Whatsapp_chatbot.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_chatbot/Controllers/Whatsapp_chatbot.php:165);
- a view atual também não expõe esse campo em [ai_settings.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_chatbot/Views/ai_settings.php:15).

Essa divergência precisa ser corrigida antes da modernização da IA.

---

## 6. Autoresponder atual

O autoresponder atual está em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:1753).

Lógica atual:

- dedupe por mensagem recebida;
- valida `send_to`;
- valida lista `except`;
- respeita janela de `delay`;
- ao final chama `WAZIPER.auto_send(..., "autoresponder", ...)`.

Ponto importante:

- ele usa `auto_send()`;
- porém o processamento de IA dentro de `Extend.process_message()` só roda quando `type == 'chatbot'`.

Conclusão:

- autoresponder não é IA nativo hoje;
- ele envia resposta automática normal, sem adapter de IA.

---

## 7. Bulk atual

O fluxo de campanhas em massa vive em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:1680).

Lógica observada:

- atualiza contadores de `sent` e `failed`;
- agenda próximo disparo com `min_delay` e `max_delay`;
- atualiza `sp_whatsapp_schedules`;
- emite eventos para o painel.

No envio em si, o bulk usa `auto_send()` em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:3411).

Limitação atual:

- bulk passa por `auto_send()`, mas o uso de IA continua bloqueado pelo critério `type == 'chatbot'`;
- portanto, bulk hoje não deve ser tratado como fluxo IA-first.

---

## 8. Mídia e live chat

### 8.1. Normalização da API Oficial

Mensagens oficiais são convertidas em estrutura interna em [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:1265).

Tipos tratados:

- interactive
- image
- video
- audio
- sticker
- text

### 8.2. Download e persistência da mídia

O download e a validação da mídia acontecem em:

- [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:1765)

O fluxo atual:

- baixa a mídia;
- gera nome de arquivo se necessário;
- grava em `app_zapmatic_api/files` quando permitido;
- salva metadata da mensagem.

Risco já identificado:

- o trecho de download de mídia já apresentou falhas em log histórico;
- isso torna a etapa multimodal uma das áreas mais sensíveis da operação.

---

## 9. OpenAI administrativo atual

O módulo administrativo de OpenAI está no PHP.

Principais pontos:

- geração de texto manual em [Openai.php](/www/wwwroot/app_zapmatic_app/inc/core/Openai/Controllers/Openai.php:26);
- geração de imagem em [Openai.php](/www/wwwroot/app_zapmatic_app/inc/core/Openai/Controllers/Openai.php:93);
- helper central em [Openai_helper.php](/www/wwwroot/app_zapmatic_app/inc/core/Openai/Helpers/Openai_helper.php:3);
- tela de configuração em [content.php](/www/wwwroot/app_zapmatic_app/inc/core/Openai/Views/settings/content.php:1).

Estado atual:

- ainda usa `completions` com `text-davinci-003` em [Openai.php](/www/wwwroot/app_zapmatic_app/inc/core/Openai/Controllers/Openai.php:42);
- helper também mantém parte do legado em [Openai_helper.php](/www/wwwroot/app_zapmatic_app/inc/core/Openai/Helpers/Openai_helper.php:50);
- a lista de modelos em [Openai_helper.php](/www/wwwroot/app_zapmatic_app/inc/core/Openai/Helpers/Openai_helper.php:210) está defasada;
- a interface é 100% OpenAI e não é multi-provider.

---

## 10. UI de configuração do chatbot com IA

O item de chatbot liga IA por `use_ai` em [update.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_chatbot/Views/update.php:115).

A configuração por conta fica em [ai_settings.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_chatbot/Views/ai_settings.php:15).

Hoje a tela permite:

- status da IA;
- API key;
- temperatura;
- modelo;
- max tokens;
- keyword para habilitar;
- keyword para desabilitar.

Limitações atuais da UI:

- não existe campo de provedor;
- não existe prompt de sistema persistente funcional;
- não existe toggle de visão;
- não existe toggle de transcrição de áudio;
- não existe fallback chain;
- não existe controle de retenção/contexto;
- o texto da UI ainda fala em OpenAI diretamente.

---

## 11. Estado atual do banco para IA de atendimento

Tabela principal:

- `sp_whatsapp_ai`

Outras tabelas relevantes:

- `sp_whatsapp_chatbot`
- `sp_whatsapp_autoresponder`
- `sp_whatsapp_schedules`
- `sp_whatsapp_messages`
- `sp_whatsapp_subscriber`

Hoje não existem tabelas próprias para:

- contexto persistente da IA;
- trilha de prompts/respostas;
- uso por provedor/modelo;
- fallback por tentativa;
- transcrição de áudio;
- cache de visão de imagem.

---

## 12. Limitações e riscos atuais

### 12.1. Limitações funcionais

- IA de atendimento só funciona no `chatbot`;
- autoresponder e bulk não estão integrados como fluxos de IA;
- não existe multi-provider;
- não existe transcrição de áudio;
- não existe visão para imagem;
- contexto não é persistido.

### 12.2. Limitações de arquitetura

- runtime e UI possuem descompasso de schema;
- OpenAI está acoplado diretamente ao runtime;
- histórico em memória aumenta risco de inconsistência após restart;
- parte do módulo administrativo ainda usa endpoints/modelos legados.

### 12.3. Limitações operacionais

- não existe documento oficial interno da arquitetura atual;
- não existe documento contínuo de avanço por etapa;
- não existe gate formal de teste antes de avançar de fase.

---

## 13. Parecer técnico sobre persistir contexto em banco

Guardar contexto em banco é **viável** e faz sentido, mas não deve ser a primeira mudança do projeto.

Recomendação:

- adotar modelo híbrido;
- manter cache em memória para baixa latência;
- persistir no banco apenas uma janela curta de contexto;
- aplicar TTL e limite de turnos por chat;
- registrar apenas o necessário para continuidade e auditoria.

Motivos para fazer:

- continuidade após restart do Node;
- observabilidade;
- possibilidade de troubleshooting;
- suporte a atendimento mais consistente.

Motivos para não fazer logo no início:

- aumenta custo de projeto;
- exige desenho de retenção e privacidade;
- se feito antes do adapter, corre o risco de cristalizar um schema ruim.

Parecer:

- **sim, é viável**;
- **deve entrar no plano**;
- **não deve ser a fase inicial**.

---

## 14. Conclusão do estado atual

O sistema atual é funcional, mas a camada de IA está em um estágio de transição:

- funciona bem para o caso básico de chatbot com OpenAI;
- ainda não está pronta para uma expansão segura de provedores e multimodal;
- precisa primeiro de normalização de contrato, schema e estratégia de testes.

Este documento passa a ser a base oficial da operação de modernização.
