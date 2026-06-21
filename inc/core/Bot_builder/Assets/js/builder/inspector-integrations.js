(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
window.BotBuilderModules.inspector = window.BotBuilderModules.inspector || {};
var M = window.BotBuilderModules.inspector;

function ctx() { return M._ctx || {}; }

function renderIntegrationFields(node, id, d) {
    var h = '';
    var type = node.type;

    if(type === 'intg_sheets') {
        h += M.select('conf-action', 'Ação', d.action, ['append_row','read_row','update_row','delete_row']);
        h += M.field('input', 'conf-spreadsheet_id', 'ID da planilha', d.spreadsheet_id, 'Informe o ID do Google Sheets');
        h += M.field('input', 'conf-sheet_name', 'Nome da aba', d.sheet_name, 'Planilha1');
        h += M.field('textarea', 'conf-values', 'Valores da linha (separados por vírgula)', d.values, '{{name}},{{email}},{{phone}}', 2);
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'sheets_result');
        h += '<div class="form-hint">Use {{variavel}} para valores dinâmicos. Vírgula separa colunas.</div>';
    }
    if(type === 'intg_analytics') {
        h += M.field('input', 'conf-tracking_id', 'ID de acompanhamento', d.tracking_id, 'G-XXXXXXXXXX');
        h += M.field('input', 'conf-event_name', 'Nome do evento', d.event_name, 'bot_event');
        h += M.field('textarea', 'conf-event_params', 'Parâmetros do evento (JSON)', d.event_params, '{"categoria":"bot"}', 3);
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'analytics_result');
    }
    if(type === 'intg_http') {
        h += M.field('input', 'conf-url', 'URL', d.url, 'https://api.example.com/endpoint');
        h += M.select('conf-method', 'Método', d.method, ['GET','POST','PUT','PATCH','DELETE']);
        h += M.field('textarea', 'conf-headers', 'Cabeçalhos (JSON)', d.headers, '{"Authorization":"Bearer ..."}', 3);
        h += M.field('textarea', 'conf-body', 'Corpo (JSON)', d.body, '{}', 4);
        h += M.field('input', 'conf-timeout', 'Tempo limite (segundos)', d.timeout, '30');
        h += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'http_response');
        h += '<div class="form-hint">Requisição HTTP completa. Use {{variavel}} na URL, cabeçalhos e corpo.</div>';
    }
    if(type === 'intg_email') {
        h += M.field('input', 'conf-to', 'E-mail de destino', d.to, 'cliente@exemplo.com');
        h += M.field('input', 'conf-subject', 'Assunto', d.subject, 'Novo envio do bot');
        h += M.field('textarea', 'conf-body_text', 'Corpo do e-mail', d.body_text, 'Nome: {{name}}\nE-mail: {{email}}', 4);
        h += M.field('input', 'conf-from_name', 'Nome do remetente', d.from_name, 'Bot');
        h += M.field('input', 'conf-smtp_host', 'Servidor SMTP', d.smtp_host, 'smtp.gmail.com');
        h += M.field('input', 'conf-smtp_port', 'Porta SMTP', d.smtp_port, '587');
        h += M.field('input', 'conf-smtp_user', 'Usuário SMTP', d.smtp_user, '');
        h += M.field('input', 'conf-smtp_pass', 'Senha SMTP', d.smtp_pass, '');
        h += M.field('input', 'conf-variable', 'Salvar status em', d.variable, 'email_status');
    }
    if(type === 'intg_zapier') {
        h += M.field('input', 'conf-webhook_url', 'Zapier Webhook URL', d.webhook_url, 'https://hooks.zapier.com/...');
        h += M.field('textarea', 'conf-payload', 'Payload (JSON)', d.payload, '{"name":"{{name}}"}', 4);
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'zapier_result');
        h += '<div class="form-hint">Dispara um Zap do Zapier por webhook.</div>';
    }
    if(type === 'intg_make') {
        h += M.field('input', 'conf-webhook_url', 'Make Webhook URL', d.webhook_url, 'https://hook.eu1.make.com/...');
        h += M.field('textarea', 'conf-payload', 'Payload (JSON)', d.payload, '{"name":"{{name}}"}', 4);
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'make_result');
        h += '<div class="form-hint">Dispara um cenário do Make.com por webhook.</div>';
    }
    if(type === 'intg_pabbly') {
        h += M.field('input', 'conf-webhook_url', 'Pabbly Webhook URL', d.webhook_url, 'https://connect.pabbly.com/...');
        h += M.field('textarea', 'conf-payload', 'Payload (JSON)', d.payload, '{"name":"{{name}}"}', 4);
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'pabbly_result');
        h += '<div class="form-hint">Dispara um fluxo do Pabbly Connect.</div>';
    }
    if(type === 'intg_chatwoot') {
        h += M.field('input', 'conf-api_url', 'Chatwoot API URL', d.api_url, 'https://app.chatwoot.com/api/v1');
        h += M.field('input', 'conf-api_token', 'Token da API', d.api_token, '');
        h += M.field('input', 'conf-account_id', 'ID da conta', d.account_id, '');
        h += M.select('conf-action', 'Ação', d.action, ['create_contact','create_conversation','send_message','assign_agent']);
        h += M.field('textarea', 'conf-payload', 'Payload (JSON)', d.payload, '{}', 3);
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'chatwoot_result');
    }
    if(type === 'intg_pixel') {
        h += M.field('input', 'conf-pixel_id', 'Facebook Pixel ID', d.pixel_id, '');
        h += M.field('input', 'conf-event_name', 'Nome do evento', d.event_name, 'Lead');
        h += M.field('textarea', 'conf-event_params', 'Parâmetros do evento (JSON)', d.event_params, '{"value":"1","currency":"BRL"}', 3);
        h += M.field('input', 'conf-access_token', 'Token de acesso', d.access_token, '');
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'pixel_result');
        h += '<div class="form-hint">Dispara uma conversão do Meta Pixel pela API do servidor.</div>';
    }
    if(type === 'intg_openai') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, 'sk-...');
        h += M.select('conf-model', 'Modelo', d.model, ['gpt-4o','gpt-4o-mini','gpt-4-turbo','gpt-3.5-turbo','o1-mini']);
        h += M.field('textarea', 'conf-system_prompt', 'Prompt do sistema', d.system_prompt, 'Você é um assistente útil.', 3);
        h += M.field('textarea', 'conf-prompt', 'Prompt / mensagem do usuário', d.prompt, '{{last_message}}', 3);
        h += M.field('input', 'conf-temperature', 'Temperatura', d.temperature, '0.7');
        h += M.field('input', 'conf-max_tokens', 'Máximo de tokens', d.max_tokens, '500');
        h += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'openai_reply');
    }
    if(type === 'intg_calcom') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, 'cal_live_...');
        h += M.field('input', 'conf-event_type_id', 'ID do tipo de evento', d.event_type_id, '');
        h += M.select('conf-action', 'Ação', d.action, ['get_availability','create_booking','cancel_booking']);
        h += M.field('input', 'conf-date', 'Data para consultar disponibilidade', d.date, '{{date}}');
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'calcom_result');
    }
    if(type === 'intg_chatnode') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, '');
        h += M.field('input', 'conf-bot_id', 'ChatNode Bot ID', d.bot_id, '');
        h += M.field('textarea', 'conf-query', 'Consulta', d.query, '{{last_message}}', 2);
        h += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'chatnode_reply');
    }
    if(type === 'intg_qrcode') {
        h += M.field('input', 'conf-data', 'Dados do QR Code', d.data, '{{website}}');
        h += M.field('input', 'conf-size', 'Tamanho (px)', d.size, '300');
        h += M.select('conf-format', 'Formato', d.format, ['png','svg','jpg']);
        h += M.field('input', 'conf-variable', 'Salvar URL em', d.variable, 'qr_url');
        h += '<div class="form-hint">Gera uma URL de imagem de QR Code a partir dos dados informados.</div>';
    }
    if(type === 'intg_dify') {
        h += M.field('input', 'conf-api_url', 'Dify API URL', d.api_url, 'https://api.dify.ai/v1');
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, 'app-...');
        h += M.field('textarea', 'conf-query', 'Consulta', d.query, '{{last_message}}', 2);
        h += M.field('input', 'conf-conversation_id', 'ID da conversa (opcional)', d.conversation_id, '');
        h += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'dify_reply');
    }
    if(type === 'intg_mistral') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, '');
        h += M.select('conf-model', 'Modelo', d.model, ['mistral-large-latest','mistral-medium','mistral-small-latest','open-mistral-nemo']);
        h += M.field('textarea', 'conf-system_prompt', 'Prompt do sistema', d.system_prompt, '', 2);
        h += M.field('textarea', 'conf-prompt', 'Prompt', d.prompt, '{{last_message}}', 3);
        h += M.field('input', 'conf-temperature', 'Temperatura', d.temperature, '0.7');
        h += M.field('input', 'conf-max_tokens', 'Máximo de tokens', d.max_tokens, '500');
        h += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'mistral_reply');
    }
    if(type === 'intg_elevenlabs') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, '');
        h += M.field('input', 'conf-voice_id', 'ID da voz', d.voice_id, '');
        h += M.field('textarea', 'conf-text', 'Texto para transformar em áudio', d.text, '{{ai_reply}}', 3);
        h += M.select('conf-model_id', 'Modelo', d.model_id, ['eleven_multilingual_v2','eleven_turbo_v2_5','eleven_monolingual_v1']);
        h += M.field('input', 'conf-variable', 'Salvar URL do áudio em', d.variable, 'audio_url');
        h += '<div class="form-hint">Converte texto em fala e salva a URL do áudio na variável.</div>';
    }
    if(type === 'intg_anthropic') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, 'sk-ant-...');
        h += M.select('conf-model', 'Modelo', d.model, ['claude-3-5-sonnet-20241022','claude-3-5-haiku-20241022','claude-3-opus-20240229']);
        h += M.field('textarea', 'conf-system_prompt', 'Prompt do sistema', d.system_prompt, '', 2);
        h += M.field('textarea', 'conf-prompt', 'Prompt', d.prompt, '{{last_message}}', 3);
        h += M.field('input', 'conf-temperature', 'Temperatura', d.temperature, '0.7');
        h += M.field('input', 'conf-max_tokens', 'Máximo de tokens', d.max_tokens, '1024');
        h += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'claude_reply');
    }
    if(type === 'intg_together') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, '');
        h += M.select('conf-model', 'Modelo', d.model, ['meta-llama/Llama-3-70b-chat-hf','meta-llama/Llama-3-8b-chat-hf','mistralai/Mixtral-8x7B-v0.1','Qwen/Qwen2-72B-Instruct']);
        h += M.field('textarea', 'conf-system_prompt', 'Prompt do sistema', d.system_prompt, '', 2);
        h += M.field('textarea', 'conf-prompt', 'Prompt', d.prompt, '{{last_message}}', 3);
        h += M.field('input', 'conf-temperature', 'Temperatura', d.temperature, '0.7');
        h += M.field('input', 'conf-max_tokens', 'Máximo de tokens', d.max_tokens, '500');
        h += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'together_reply');
    }
    if(type === 'intg_openrouter') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, '');
        h += M.select('conf-model', 'Modelo', d.model, ['openai/gpt-4o-mini','openai/gpt-4o','anthropic/claude-3.5-sonnet','google/gemini-pro','meta-llama/llama-3.1-70b-instruct']);
        h += M.field('textarea', 'conf-system_prompt', 'Prompt do sistema', d.system_prompt, '', 2);
        h += M.field('textarea', 'conf-prompt', 'Prompt', d.prompt, '{{last_message}}', 3);
        h += M.field('input', 'conf-temperature', 'Temperatura', d.temperature, '0.7');
        h += M.field('input', 'conf-max_tokens', 'Máximo de tokens', d.max_tokens, '500');
        h += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'openrouter_reply');
        h += '<div class="form-hint">Roteia para mais de 100 modelos de IA pela API da OpenRouter.</div>';
    }
    if(type === 'intg_nocodb') {
        h += M.field('input', 'conf-api_url', 'NocoDB URL', d.api_url, 'https://app.nocodb.com/api/v1');
        h += M.field('input', 'conf-api_token', 'Token da API', d.api_token, '');
        h += M.field('input', 'conf-table_id', 'ID da tabela', d.table_id, '');
        h += M.select('conf-action', 'Ação', d.action, ['list','read','create','update','delete']);
        h += M.field('textarea', 'conf-data', 'Dados (JSON)', d.data, '{}', 3);
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'nocodb_result');
    }
    if(type === 'intg_segment') {
        h += M.field('input', 'conf-write_key', 'Chave de escrita', d.write_key, '');
        h += M.field('input', 'conf-event_name', 'Nome do evento', d.event_name, 'Interação do bot');
        h += M.field('input', 'conf-user_id', 'ID do usuário', d.user_id, '{{phone}}');
        h += M.field('textarea', 'conf-properties', 'Propriedades (JSON)', d.properties, '{"origem":"bot"}', 3);
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'segment_result');
    }
    if(type === 'intg_groq') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, 'gsk_...');
        h += M.select('conf-model', 'Modelo', d.model, ['llama-3.1-70b-versatile','llama-3.1-8b-instant','mixtral-8x7b-32768','gemma2-9b-it']);
        h += M.field('textarea', 'conf-system_prompt', 'Prompt do sistema', d.system_prompt, '', 2);
        h += M.field('textarea', 'conf-prompt', 'Prompt', d.prompt, '{{last_message}}', 3);
        h += M.field('input', 'conf-temperature', 'Temperatura', d.temperature, '0.7');
        h += M.field('input', 'conf-max_tokens', 'Máximo de tokens', d.max_tokens, '500');
        h += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'groq_reply');
        h += '<div class="form-hint">Inferência de IA de baixa latência pela Groq.</div>';
    }
    if(type === 'intg_zendesk') {
        h += M.field('input', 'conf-subdomain', 'Subdomínio', d.subdomain, 'suaempresa');
        h += M.field('input', 'conf-email', 'E-mail do admin', d.email, 'admin@exemplo.com');
        h += M.field('input', 'conf-api_token', 'Token da API', d.api_token, '');
        h += M.select('conf-action', 'Ação', d.action, ['create_ticket','update_ticket','add_comment','search_tickets']);
        h += M.field('input', 'conf-subject', 'Assunto', d.subject, 'Suporte: {{name}}');
        h += M.field('textarea', 'conf-body_text', 'Corpo / descrição', d.body_text, '{{last_message}}', 3);
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'zendesk_result');
    }
    if(type === 'intg_posthog') {
        h += M.field('input', 'conf-api_key', 'Chave da API do projeto', d.api_key, 'phc_...');
        h += M.field('input', 'conf-host', 'Host', d.host, 'https://app.posthog.com');
        h += M.field('input', 'conf-event_name', 'Nome do evento', d.event_name, 'bot_event');
        h += M.field('input', 'conf-distinct_id', 'ID distinto', d.distinct_id, '{{phone}}');
        h += M.field('textarea', 'conf-properties', 'Propriedades (JSON)', d.properties, '{}', 3);
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'posthog_result');
    }
    if(type === 'intg_perplexity') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, 'pplx-...');
        h += M.select('conf-model', 'Modelo', d.model, ['llama-3.1-sonar-small-128k-online','llama-3.1-sonar-large-128k-online','llama-3.1-sonar-huge-128k-online']);
        h += M.field('textarea', 'conf-system_prompt', 'Prompt do sistema', d.system_prompt, '', 2);
        h += M.field('textarea', 'conf-prompt', 'Prompt', d.prompt, '{{last_message}}', 3);
        h += M.field('input', 'conf-temperature', 'Temperatura', d.temperature, '0.7');
        h += M.field('input', 'conf-max_tokens', 'Máximo de tokens', d.max_tokens, '500');
        h += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'perplexity_reply');
        h += '<div class="form-hint">Busca com IA conectada à internet e dados em tempo real.</div>';
    }
    if(type === 'intg_deepseek') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, '');
        h += M.select('conf-model', 'Modelo', d.model, ['deepseek-chat','deepseek-reasoner']);
        h += M.field('textarea', 'conf-system_prompt', 'Prompt do sistema', d.system_prompt, '', 2);
        h += M.field('textarea', 'conf-prompt', 'Prompt', d.prompt, '{{last_message}}', 3);
        h += M.field('input', 'conf-temperature', 'Temperatura', d.temperature, '0.7');
        h += M.field('input', 'conf-max_tokens', 'Máximo de tokens', d.max_tokens, '500');
        h += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'deepseek_reply');
    }
    if(type === 'intg_blink') {
        h += M.field('input', 'conf-api_key', 'Chave da API', d.api_key, '');
        h += M.select('conf-action', 'Ação', d.action, ['send_notification','create_task','update_status']);
        h += M.field('textarea', 'conf-payload', 'Payload (JSON)', d.payload, '{}', 3);
        h += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'blink_result');
    }
    if(type === 'intg_gmail') {
        h += M.field('input', 'conf-to', 'E-mail de destino', d.to, 'cliente@gmail.com');
        h += M.field('input', 'conf-subject', 'Assunto', d.subject, 'Notificação do bot');
        h += M.field('textarea', 'conf-body_text', 'Corpo do e-mail', d.body_text, 'Olá {{name}},\n\nSeus dados...', 4);
        h += M.field('input', 'conf-from_name', 'Nome do remetente', d.from_name, 'Bot');
        h += M.field('input', 'conf-oauth_token', 'OAuth Token', d.oauth_token, '');
        h += M.field('input', 'conf-variable', 'Salvar status em', d.variable, 'gmail_status');
        h += '<div class="form-hint"><span class="intg-beta" style="font-size:9px;">Beta</span> A integração Gmail usa OAuth2. Configure nas configurações.</div>';
    }

    return h;
}

M.renderIntegrationFields = renderIntegrationFields;

})();
