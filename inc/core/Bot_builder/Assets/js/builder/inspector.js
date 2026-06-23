(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
window.BotBuilderModules.inspector = window.BotBuilderModules.inspector || {};
var M = window.BotBuilderModules.inspector;

function ctx() { return M._ctx || {}; }

// ===================== BUILD INPUT INSPECTOR =====================

function buildInputInspector(node, id, d) {
    var h = '';

    // Required Toggle
    h += '<div class="tb-toggle-row">\n        <span class="tb-toggle-label"><i class="fas fa-asterisk" style="color:#ef4444"></i> Required</span>\n        <label class="tb-toggle"><input type="checkbox" id="conf-required" data-bool="true" ' + ((d.required||'true')==='true'?'checked':'') + '><span class="tb-toggle-track"></span></label>\n    </div>';

    if(node.type === 'input_text') {
        h += '<div class="tb-toggle-row">\n            <span class="tb-toggle-label"><i class="fas fa-align-left"></i> Texto longo</span>\n            <label class="tb-toggle"><input type="checkbox" id="conf-long_text" data-bool="true" ' + (d.long_text==='true'?'checked':'') + '><span class="tb-toggle-track"></span></label>\n        </div>';
    }

    if(['input_text','input_number','input_email','input_website','input_phone'].includes(node.type)) {
        h += '<div class="form-group">\n            <label>Placeholder:</label>\n            <div class="form-control-with-var">\n                <input class="form-control" id="conf-placeholder" value="' + ctx().escHtml(d.placeholder||'Digite sua resposta...') + '" placeholder="Digite sua resposta...">\n                <button class="form-control-var-btn" onclick="insertVarIntoField(\'conf-placeholder\')" title="Inserir variável"><i class="fas fa-code"></i></button>\n            </div>\n        </div>';
    }

    h += '<div class="form-group">\n        <label>Texto do botão:</label>\n        <div class="form-control-with-var">\n            <input class="form-control" id="conf-button_label" value="' + ctx().escHtml(d.button_label||'Enviar') + '" placeholder="Enviar">\n            <button class="form-control-var-btn" onclick="insertVarIntoField(\'conf-button_label\')" title="Inserir variável"><i class="fas fa-code"></i></button>\n        </div>\n    </div>';

    if(['input_text','input_number','input_email','input_website','input_phone'].includes(node.type)) {
        var modes = node.type === 'input_number' ? ['numeric','decimal','text'] :
                      node.type === 'input_email' ? ['email','text'] :
                      node.type === 'input_website' ? ['url','text'] :
                      node.type === 'input_phone' ? ['tel','numeric','text'] :
                      ['text','numeric','email','url','tel'];
        h += M.select('conf-input_mode', 'Modo de entrada', d.input_mode || modes[0], modes);
    }

    if(node.type === 'input_text') {
        h += '<hr class="insp-sep">';
        h += '<div class="form-section-title" style="font-size:11px;color:#64748b;margin:8px 0 4px;"><i class="fas fa-shield-alt"></i> Regras de validação</div>';
        h += '<div class="form-row">';
        h += M.field('input', 'conf-min_length', 'Tamanho mínimo', d.min_length, '');
        h += M.field('input', 'conf-max_length', 'Tamanho máximo', d.max_length, '');
        h += '</div>';
        h += M.field('input', 'conf-regex', 'Padrão regex', d.regex, '^[A-Za-z ]+$');
        h += M.field('input', 'conf-regex_error', 'Mensagem de erro regex', d.regex_error || 'A resposta não está no formato esperado.', 'Erro personalizado...');
    }

    if(node.type === 'input_number') {
        h += '<div class="form-row">';
        h += M.field('input', 'conf-min', 'Valor mínimo', d.min, '');
        h += M.field('input', 'conf-max', 'Valor máximo', d.max, '');
        h += '</div>';
        h += M.field('input', 'conf-step', 'Intervalo', d.step, '1');
    }

    if(node.type === 'input_date') {
        h += M.select('conf-format', 'Formato da data', d.format || 'YYYY-MM-DD', ['YYYY-MM-DD','DD/MM/YYYY','MM/DD/YYYY','DD-MM-YYYY']);
        h += '<div class="form-row">';
        h += M.field('input', 'conf-min_date', 'Data mínima', d.min_date, 'YYYY-MM-DD');
        h += M.field('input', 'conf-max_date', 'Data máxima', d.max_date, 'YYYY-MM-DD');
        h += '</div>';
    }
    if(node.type === 'input_time') {
        h += M.select('conf-format', 'Formato da hora', d.format || 'HH:mm', ['HH:mm','hh:mm A','HH:mm:ss']);
        h += '<div class="form-row">';
        h += M.field('input', 'conf-min_time', 'Hora mínima', d.min_time, '09:00');
        h += M.field('input', 'conf-max_time', 'Hora máxima', d.max_time, '18:00');
        h += '</div>';
    }
    if(node.type === 'input_phone') {
        h += M.field('input', 'conf-country_code', 'DDI padrão', d.country_code, '+55');
    }

    h += '<hr class="insp-sep">';
    h += '<div class="tb-toggle-row">\n        <span class="tb-toggle-label"><i class="fas fa-microphone"></i> Permitir áudio</span>\n        <label class="tb-toggle"><input type="checkbox" id="conf-allow_audio" data-bool="true" ' + (d.allow_audio==='true'?'checked':'') + '><span class="tb-toggle-track"></span></label>\n    </div>';
    h += '<div class="tb-toggle-row">\n        <span class="tb-toggle-label"><i class="fas fa-paperclip"></i> Permitir anexos</span>\n        <label class="tb-toggle"><input type="checkbox" id="conf-allow_attachments" data-bool="true" ' + (d.allow_attachments==='true'?'checked':'') + '><span class="tb-toggle-track"></span></label>\n    </div>';

    h += '<hr class="insp-sep">';
    h += M.varFieldWithPicker('conf-variable', 'Salvar resposta em uma variável:', d.variable || '', id);

    h += '<hr class="insp-sep">';
    h += M.field('textarea', 'conf-retry_message', 'Mensagem de nova tentativa', d.retry_message || '', 'Envie uma resposta válida.', 2);
    h += '<div class="form-hint"><i class="fas fa-info-circle"></i> Enviada quando a resposta do contato não passa na validação.</div>';

    return h;
}

// ===================== OPEN INSPECTOR =====================

function openInspector(id) {
    var node = ctx().BB.nodes[id];
    if(!node) return;
    var panel = ctx().inspectorPanel;
    panel.classList.remove('hidden');
    var def = ctx().NODE_DEFS[node.type] || {};
    ctx().$('#inspector-title').innerHTML = '<i class="' + (def.icon || 'fad fa-cube') + '" style="font-size:14px;"></i> ' + (def.label || node.type);

    var d = node.config;
    var html = '<div class="form-group"><label>ID do bloco</label><input class="form-control" value="' + id + '" disabled style="font-family:monospace;font-size:10px;opacity:0.6;"></div>';
    html += M.field('input', 'conf-label', 'Nome do bloco', d.label || '', 'Ex: Áudio boas-vindas');
    html += '<div class="form-hint">Nome interno para organização. Não é enviado ao contato.</div>';

    if(node.type === 'text') {
        html += M.field('textarea', 'conf-text', 'Mensagem', d.text, 'Digite sua mensagem...', 4);
        html += '<div class="form-hint">Use {{variavel}} para valores dinâmicos</div>';
    }
    if(node.type === 'image') {
        html += M.mediaUploadField('image', d.url);
        html += M.field('input', 'conf-caption', 'Legenda', d.caption, 'Legenda opcional');
    }
    if(node.type === 'video') {
        html += M.mediaUploadField('video', d.url);
        html += M.field('input', 'conf-caption', 'Legenda', d.caption, 'Legenda opcional');
    }
    if(node.type === 'audio') {
        html += M.mediaUploadField('audio', d.url);
    }
    if(node.type === 'embed') {
        html += M.field('input', 'conf-url', 'URL', d.url, 'https://example.com');
        html += M.select('conf-embed_type', 'Tipo de incorporação', d.embed_type, ['link','iframe','map','video']);
        html += M.field('input', 'conf-title', 'Título', d.title, 'Título da prévia do link');
        html += M.field('input', 'conf-description', 'Descrição', d.description, 'Descrição da prévia do link');
        html += '<div class="form-hint">Envia uma prévia de link ou conteúdo incorporado</div>';
    }
    var inputTypes = ['input_text','input_number','input_email','input_website','input_date','input_time','input_phone'];
    if(inputTypes.indexOf(node.type) !== -1) {
        html += M.field('textarea', 'conf-question', 'Mensagem:', d.question || '', 'Digite sua pergunta...', 3);
        html += M.varInsertHint('conf-question');
        html += buildInputInspector(node, id, d);
    }
    if(node.type === 'buttons') {
        html += M.selectCustom('conf-button_mode', 'Modo de botões', d.button_mode || 'quick', ['quick|Botões rápidos do Builder','native|Template nativo global']);
        if((d.button_mode || 'quick') === 'native') {
            html += M.nativeTemplateInspector(node, d);
        } else {
            html += M.field('textarea', 'conf-text', 'Mensagem', d.text, 'Escolha uma opção:', 3);
            html += M.varInsertHint('conf-text');
            html += M.field('input', 'conf-title', 'Título (opcional)', d.title||'', 'Ex: Confirmação');
            html += '<div class="form-group bb-media-upload-wrap"><label>Imagem</label><input type="hidden" id="conf-image" value="' + ctx().escHtml(d.image||'') + '"><div class="bb-media-upload-box"><input type="file" class="bb-media-file" data-media-type="image" accept="image/jpeg,image/png,image/gif,image/webp"><button type="button" class="btn-add-dynamic bb-media-upload-btn"><i class="fas fa-cloud-upload-alt"></i> Enviar Imagem</button></div><div id="conf-image-preview" class="bb-media-current' + (d.image?'':' muted') + '">' + (d.image?'<div class="bb-media-thumb image"><img src="' + ctx().escHtml(d.image) + '" alt="Prévia"></div><div><strong>Mídia carregada</strong><a href="' + ctx().escHtml(d.image) + '" target="_blank" rel="noopener">Abrir arquivo</a></div>':'<div class="bb-media-thumb image"><i class="fas fa-image"></i></div><div><strong>Nenhuma imagem</strong><span>Escolha uma imagem para o cabeçalho dos botões.</span></div>') + '</div></div>';
            html += '<div class="form-section"><div class="form-section-title"><i class="fas fa-check-square"></i> Itens <span style="color:#94a3b8;font-weight:400;font-size:10px;">(máx. 10 via Native Flow)</span></div>';
            html += '<div id="dyn-btn-list"></div>';
            html += '<button type="button" class="btn-add-dynamic" onclick="dynBtnAdd()"><i class="fas fa-plus"></i> Adicionar item</button>';
            html += '<input type="hidden" id="conf-options" value="' + ctx().escHtml(d.options||'') + '">';
            html += '</div>';
            var alreadyPromoted = (d.template_ids && d.template_ids.indexOf('bb_promoted_') === 0);
            html += '<button type="button" class="btn-duplicate-node" onclick="promoteToNative(\'' + id + '\')"><i class="fas ' + (alreadyPromoted ? 'fa-sync-alt' : 'fa-rocket') + '"></i> ' + (alreadyPromoted ? 'Atualizar template nativo' : 'Promover para template nativo') + '</button>';
        }
        html += M.varFieldWithPicker('conf-variable', 'Salvar seleção na variável', d.variable || 'selection', id);
        html += '<div class="tb-toggle-row">\n            <span class="tb-toggle-label"><i class="fas fa-asterisk" style="color:#ef4444"></i> Obrigatório</span>\n            <label class="tb-toggle"><input type="checkbox" id="conf-required" data-bool="true" ' + ((d.required||'true')==='true'?'checked':'') + '><span class="tb-toggle-track"></span></label>\n        </div>';
    }
    if(node.type === 'pic_choice') {
        html += M.field('textarea', 'conf-question', 'Pergunta', d.question, 'Escolha uma opção:', 3);
        html += M.varInsertHint('conf-question');
        html += '<div class="form-section"><div class="form-section-title"><i class="fas fa-images"></i> Opções</div>';
        html += '<div id="dyn-pic-list"></div>';
        html += '<button type="button" class="btn-add-dynamic" onclick="dynPicAdd()"><i class="fas fa-plus"></i> Adicionar opção</button>';
        html += '<input type="hidden" id="conf-choices" value="' + ctx().escHtml(d.choices||'') + '">';
        html += '</div>';
        html += M.varFieldWithPicker('conf-variable', 'Salvar escolha na variável', d.variable || 'choice', id);
        html += '<div class="tb-toggle-row">\n            <span class="tb-toggle-label"><i class="fas fa-layer-group"></i> Permitir múltiplas escolhas</span>\n            <label class="tb-toggle"><input type="checkbox" id="conf-multiple" data-bool="true" ' + (d.multiple==='true'?'checked':'') + '><span class="tb-toggle-track"></span></label>\n        </div>';
    }
    if(node.type === 'payment') {
        html += '<div class="form-section"><div class="form-section-title"><i class="fas fa-credit-card"></i> Configurações de pagamento</div>';
        html += '<div class="form-row">';
        html += M.field('input', 'conf-amount', 'Valor', d.amount, '9.99');
        html += M.select('conf-currency', 'Moeda', d.currency, ['BRL','USD','EUR','GBP','INR','AED','SAR']);
        html += '</div>';
        html += M.field('input', 'conf-description', 'Descrição', d.description, 'Pagamento do serviço');
        html += M.select('conf-provider', 'Provedor', d.provider, ['stripe','razorpay','paypal','manual']);
        html += M.field('input', 'conf-payment_link', 'URL do link de pagamento', d.payment_link || '', 'https://pay.stripe.com/...');
        html += '<div class="form-hint"><i class="fas fa-info-circle"></i> O contato recebe este link. O fluxo continua quando ele responder "pago".</div>';
        html += '</div>';
        html += '<div class="form-section"><div class="form-section-title"><i class="fas fa-comment-dots"></i> Mensagens</div>';
        html += M.field('input', 'conf-success_message', 'Mensagem de sucesso', d.success_message || 'Pagamento recebido! Obrigado.', 'Pagamento recebido!');
        html += M.field('input', 'conf-failure_message', 'Mensagem de falha', d.failure_message || 'Pagamento não confirmado. Tente novamente.', 'Pagamento não confirmado.');
        html += '</div>';
        html += M.varFieldWithPicker('conf-variable', 'Salvar status do pagamento em', d.variable || 'payment_status', id);
    }
    if(node.type === 'rating') {
        html += M.field('textarea', 'conf-question', 'Pergunta', d.question, 'Avalie sua experiência', 3);
        html += M.varInsertHint('conf-question');
        html += '<div class="form-group"><label>Prévia:</label><div id="rating-preview-wrap" style="display:flex;gap:4px;padding:8px 0;"></div></div>';
        html += '<div class="form-row">';
        html += M.field('input', 'conf-max_stars', 'Avaliação máxima', d.max_stars, '5');
        html += M.select('conf-style', 'Estilo', d.style, ['stars','numbers','emojis','thumbs']);
        html += '</div>';
        html += '<div class="tb-toggle-row">\n            <span class="tb-toggle-label"><i class="fas fa-mouse-pointer"></i> Enviar com um clique</span>\n            <label class="tb-toggle"><input type="checkbox" id="conf-one_click" data-bool="true" ' + ((d.one_click||'true')==='true'?'checked':'') + '><span class="tb-toggle-track"></span></label>\n        </div>';
        html += M.varFieldWithPicker('conf-variable', 'Salvar avaliação em', d.variable || 'rating', id);
    }
    if(node.type === 'file_upload') {
        html += M.field('textarea', 'conf-question', 'Pergunta', d.question, 'Envie um arquivo', 3);
        html += M.varInsertHint('conf-question');
        html += '<div class="form-section"><div class="form-section-title"><i class="fas fa-file-alt"></i> Tipos de arquivo permitidos</div>';
        var ftIcons = {image:'fa-image',video:'fa-video',audio:'fa-music',document:'fa-file-alt',pdf:'fa-file-pdf',spreadsheet:'fa-file-excel'};
        var ftLabels = {image:'Imagem',video:'Vídeo',audio:'Áudio',document:'Documento',pdf:'PDF',spreadsheet:'Planilha'};
        var allowedArr = (d.allowed_types||'image,document,pdf').split(',').map(function(s){return s.trim().toLowerCase();});
        ['image','video','audio','document','pdf','spreadsheet'].forEach(function(ft) {
            html += '<div class="tb-toggle-row">\n                <span class="tb-toggle-label"><i class="fas ' + (ftIcons[ft]||'fa-file') + '"></i> ' + (ftLabels[ft] || ft) + '</span>\n                <label class="tb-toggle"><input type="checkbox" class="file-type-chk" data-ft="' + ft + '" ' + (allowedArr.indexOf(ft)!==-1?'checked':'') + '><span class="tb-toggle-track"></span></label>\n            </div>';
        });
        html += '<input type="hidden" id="conf-allowed_types" value="' + ctx().escHtml(d.allowed_types||'image,document,pdf') + '">';
        html += '</div>';
        html += M.field('input', 'conf-max_size', 'Tamanho máximo (MB)', d.max_size, '10');
        html += '<div class="tb-toggle-row">\n            <span class="tb-toggle-label"><i class="fas fa-asterisk" style="color:#ef4444"></i> Obrigatório</span>\n            <label class="tb-toggle"><input type="checkbox" id="conf-required" data-bool="true" ' + ((d.required||'true')==='true'?'checked':'') + '><span class="tb-toggle-track"></span></label>\n        </div>';
        html += M.varFieldWithPicker('conf-variable', 'Salvar URL do arquivo em', d.variable || 'file_url', id);
    }
    if(node.type === 'cards') {
        html += M.nativeTemplateInspector(node, d);
        html += M.varFieldWithPicker('conf-variable', 'Salvar escolha em', d.variable || 'card_choice', id);
        html += '<div class="tb-toggle-row">\n            <span class="tb-toggle-label"><i class="fas fa-asterisk" style="color:#ef4444"></i> Obrigatório</span>\n            <label class="tb-toggle"><input type="checkbox" id="conf-required" data-bool="true" ' + ((d.required||'true')==='true'?'checked':'') + '><span class="tb-toggle-track"></span></label>\n        </div>';
    }
    if(node.type === 'list') {
        html += M.nativeTemplateInspector(node, d);
        html += M.varFieldWithPicker('conf-variable', 'Salvar seleção na variável', d.variable || 'list_choice', id);
    }
    if(node.type === 'input') {
        html += M.field('textarea', 'conf-question', 'Pergunta', d.question, 'Pergunte algo...', 3);
        html += M.field('input', 'conf-variable', 'Salvar na variável', d.variable, 'user_name');
        html += M.select('conf-input_type', 'Tipo de entrada', d.input_type, ['text','number','email','phone','date','url']);
    }
    if(node.type === 'condition') {
        html += '<div class="insp-alert info"><i class="fas fa-info-circle"></i>Conecte as saídas <b>VERDADEIRO</b> (verde) e <b>FALSO</b> (vermelho) em caminhos diferentes.</div>';
        html += M.field('input', 'conf-variable', 'Variável', d.variable, 'user_email');
        html += M.select('conf-operator', 'Operador', d.operator, ['==','!=','contains','starts_with','ends_with','>','<','>=','<=','is_empty','not_empty']);
        html += M.field('input', 'conf-expected', 'Valor esperado', d.expected, 'sim');
    }
    if(node.type === 'delay') {
        html += M.field('input', 'conf-seconds', 'Espera (segundos)', d.seconds, '3');
    }
    if(node.type === 'ai_reply') {
        html += '<div class="insp-alert info"><i class="fas fa-brain"></i>Este bloco usa a Central de IA global. Configure as chaves em <b>Central de IA</b>.</div>';
        html += M.select('conf-provider', 'Provider', d.provider, ['auto','openrouter','openai','anthropic','mistral','groq','deepseek','perplexity','together']);
        html += M.select('conf-mode', 'Modo de resposta', d.mode, ['once','continuous']);
        html += '<div class="form-hint"><b>once</b>: responde uma vez e segue o fluxo. <b>continuous</b>: mantém a conversa neste node.</div>';
        html += M.field('input', 'conf-model', 'Modelo', d.model, 'vazio = modelo padrão da central');
        html += M.field('textarea', 'conf-system_prompt', 'Prompt de comportamento', d.system_prompt, 'Você é um assistente útil.', 3);
        html += M.field('textarea', 'conf-knowledge_base', 'Base de conhecimento', d.knowledge_base, 'Cole aqui informações da empresa, produtos, regras, preços, políticas e perguntas frequentes.', 6);
        var knowledgeFiles = [];
        try { knowledgeFiles = JSON.parse(d.knowledge_files || '[]'); } catch(e) { knowledgeFiles = []; }
        html += '<div class="form-group bb-knowledge-upload-wrap"><label>Anexos da base de conhecimento</label><input type="hidden" id="conf-knowledge_files" value="' + M._ctx.escHtml(d.knowledge_files || '[]') + '"><div class="bb-media-upload-box"><input type="file" class="bb-knowledge-file" accept=".pdf,.xls,.xlsx,.csv,.txt" multiple><button type="button" class="btn-add-dynamic bb-knowledge-upload-btn"><i class="fas fa-paperclip"></i> Enviar anexos</button></div><div class="form-hint">Até 5 arquivos: PDF, XLS, XLSX, CSV ou TXT. O texto extraído entra no contexto da IA.</div><div class="bb-knowledge-files">' + knowledgeFiles.map(function(f, idx){ return '<div class="bb-media-current"><div class="bb-media-thumb document"><i class="fas fa-file-alt"></i></div><div><strong>' + M._ctx.escHtml(f.name || ('Arquivo ' + (idx+1))) + '</strong><a href="' + M._ctx.escHtml(f.url || '#') + '" target="_blank" rel="noopener">Abrir arquivo</a></div><button type="button" class="btn btn-sm btn-light border bb-knowledge-remove" data-index="' + idx + '">Remover</button></div>'; }).join('') + '</div></div>';
        html += M.field('textarea', 'conf-prompt', 'Prompt do usuário', d.prompt, '{{last_message}}', 4);
        html += M.field('input', 'conf-temperature', 'Temperatura', d.temperature, '0.7');
        html += M.field('input', 'conf-max_tokens', 'Máximo de tokens', d.max_tokens, '500');
        html += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'ai_reply');
        html += '<div class="form-hint">Use {{variavel}} para inserir contexto da sessão.</div>';
    }
    if(node.type === 'webhook') {
        html += M.field('input', 'conf-url', 'Webhook URL', d.url, 'https://api.example.com/hook');
        html += M.select('conf-method', 'Método', d.method, ['GET','POST','PUT','PATCH','DELETE']);
        html += M.field('textarea', 'conf-headers', 'Cabeçalhos (JSON)', d.headers, '{"Authorization":"Bearer ..."}', 2);
        html += M.field('textarea', 'conf-body', 'Corpo (JSON)', d.body, '{"key":"{{variable}}"}', 3);
        html += M.field('input', 'conf-variable', 'Salvar resposta em', d.variable, 'webhook_response');
    }
    if(node.type === 'set_variable') {
        html += M.field('input', 'conf-variable', 'Nome da variável', d.variable, 'score');
        html += M.field('input', 'conf-value', 'Valor', d.value, '100');
        html += '<div class="form-hint">Use {{outra_variavel}} para referenciar outras variáveis</div>';
    }
    if(node.type === 'jump') {
        var nodeOpts = Object.keys(ctx().BB.nodes).filter(function(nid){return nid!==id;}).map(function(nid){return nid + '|' + ((ctx().NODE_DEFS[ctx().BB.nodes[nid].type]||{}).label||ctx().BB.nodes[nid].type) + ': ' + ((ctx().BB.nodes[nid].config.text||ctx().BB.nodes[nid].config.question||'').substring(0,20));});
        html += M.selectCustom('conf-target_node', 'Pular para bloco', d.target_node, nodeOpts);
    }
    if(node.type === 'script') {
        html += M.select('conf-language', 'Linguagem', d.language, ['javascript','php']);
        html += M.field('textarea', 'conf-code', 'Código', d.code, '// Escreva o código aqui...', 8);
        html += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'script_result');
        html += '<div class="form-hint">JavaScript roda no simulador. PHP roda no servidor.</div>';
    }
    if(node.type === 'ab_test') {
        html += '<div class="insp-alert info"><i class="fas fa-info-circle"></i>O tráfego é dividido entre as variantes A e B conforme a porcentagem.</div>';
        html += M.field('input', 'conf-variant_a_pct', 'Porcentagem da variante A', d.variant_a_pct, '50');
        html += M.field('input', 'conf-variant_a_label', 'Nome da variante A', d.variant_a_label, 'Variante A');
        html += M.field('input', 'conf-variant_b_label', 'Nome da variante B', d.variant_b_label, 'Variante B');
        html += '<div class="form-hint">A variante B recebe automaticamente o restante da porcentagem.</div>';
    }
    if(node.type === 'redirect') {
        html += M.field('input', 'conf-url', 'URL de redirecionamento', d.url, 'https://example.com');
        html += M.field('textarea', 'conf-message', 'Mensagem', d.message, 'Clique no link abaixo:', 2);
        html += M.select('conf-send_url', 'Enviar URL ao contato', d.send_url, ['true','false']);
        html += '<div class="form-hint">Envia uma mensagem com URL para redirecionamento.</div>';
    }
    if(node.type === 'return') {
        html += M.field('input', 'conf-return_value', 'Valor de retorno', d.return_value, 'done');
        html += '<div class="form-hint">Retorna ao fluxo principal quando chamado por um bloco Typebot.</div>';
    }
    if(node.type === 'command') {
        html += M.field('input', 'conf-command_name', 'Nome do comando', d.command_name, '/ajuda');
        html += M.field('input', 'conf-description', 'Descrição', d.description, 'Dispara quando o usuário envia um comando');
        html += '<div class="form-hint">Funciona como ponto de entrada quando o contato envia o comando especificado.</div>';
    }
    if(node.type === 'reply') {
        html += M.field('input', 'conf-match_text', 'Texto esperado', d.match_text, 'olá');
        html += M.select('conf-match_type', 'Tipo de comparação', d.match_type, ['exact','contains','starts_with','regex']);
        html += M.field('input', 'conf-description', 'Descrição', d.description, 'Dispara em uma resposta específica');
        html += '<div class="form-hint">Ponto de entrada acionado quando a mensagem bate com o padrão.</div>';
    }
    if(node.type === 'invalid') {
        html += M.field('textarea', 'conf-message', 'Mensagem de erro', d.message, 'Desculpe, não entendi.', 3);
        html += M.select('conf-retry', 'Repetir etapa anterior', d.retry, ['true','false']);
        html += '<div class="form-hint">Mostrado quando a resposta não combina com nenhuma opção esperada. Conecte a um caminho alternativo.</div>';
    }

    // Integrações
    var fullIntgTypes = ['intg_sheets','intg_analytics','intg_http','intg_email','intg_zapier','intg_make','intg_pabbly','intg_chatwoot','intg_pixel','intg_segment','intg_posthog','intg_openai','intg_chatnode','intg_dify','intg_mistral','intg_anthropic','intg_together','intg_openrouter','intg_groq','intg_perplexity','intg_deepseek','intg_calcom','intg_qrcode','intg_elevenlabs','intg_nocodb','intg_zendesk','intg_blink','intg_gmail'];

    if(fullIntgTypes.indexOf(node.type) !== -1) {
        html += M.renderIntegrationFields(node, id, d);
    }

    if(node.type === 'intg_woocommerce') {
        html += '<div class="insp-alert info"><i class="fab fa-wordpress" style="color:#96588a;"></i> Conecta à sua loja WooCommerce. Configure as chaves em <b>Integração WooCommerce</b>.</div>';
        html += M.selectCustom('conf-woo_action', 'Ação', d.woo_action, ['get_order|Obter pedido','search_products|Buscar produtos','get_categories|Obter categorias']);
        html += '<div id="woo-order-fields"' + (d.woo_action !== 'get_order' && d.woo_action ? ' style="display:none;"' : '') + '>';
        html += M.field('input', 'conf-order_id', 'ID do pedido', d.order_id, '{{order_id}}');
        html += '</div>';
        html += '<div id="woo-search-fields"' + (d.woo_action !== 'search_products' ? ' style="display:none;"' : '') + '>';
        html += M.field('input', 'conf-search_query', 'Termo de busca', d.search_query, '{{search_query}}');
        html += '</div>';
        html += M.field('input', 'conf-variable', 'Salvar resultado em', d.variable, 'woo_result');
        html += '<div class="form-hint"><b>Consulta de pedido</b> salva: woo_result, woo_result_status, woo_result_total, woo_result_error<br><b>Busca</b> salva: woo_result, woo_result_count</div>';
    }
    if(node.type === 'typebot') {
        html += M.field('input', 'conf-bot_id', 'ID do bot de destino', d.bot_id, 'Informe o ID do bot');
        html += M.select('conf-return_data', 'Retornar dados ao bot principal', d.return_data, ['true','false']);
        html += '<div class="form-hint">Vincula outro bot. O contato entra nesse fluxo e pode retornar depois.</div>';
    }

    html += M.renderWhatsAppPreview(node);

    html += '<hr class="form-divider">';
    html += '<button class="btn-duplicate-node" onclick="duplicateNode(\'' + id + '\')"><i class="fas fa-copy"></i> Duplicar</button>';
    if(node.type !== 'start') {
        html += '<button class="btn-delete-node" onclick="deleteNode(\'' + id + '\')"><i class="fas fa-trash-alt"></i> Excluir bloco</button>';
    }

    ctx().inspectorForm.innerHTML = html;

    ctx().inspectorForm.querySelectorAll('[id^="conf-"]').forEach(function(el) {
        var key = el.id.replace('conf-', '');
        if(el.type === 'checkbox' && el.dataset.bool) {
            el.addEventListener('change', function() {
                ctx().BB.nodes[id].config[key] = el.checked ? 'true' : 'false';
                ctx().updateNodePreview(id);
                M.refreshWhatsAppPreview(id);
                ctx().markDirty();
                ctx().triggerAutoSave();
            });
        } else {
            el.addEventListener('input', function() {
                ctx().BB.nodes[id].config[key] = el.value;
                ctx().updateNodePreview(id);
                M.refreshWhatsAppPreview(id);
                ctx().markDirty();
                ctx().triggerAutoSave();
            });
        }
    });

    var modeSelect = document.getElementById('conf-button_mode');
    if(modeSelect) {
        modeSelect.addEventListener('change', function() {
            var bm = modeSelect.value;
            var node = ctx().BB.nodes[id];
            node.config.button_mode = bm;
            if(bm === 'native') {
                node.config.template_mode = 'native';
            } else {
                // Switching to quick: full reset — template data stays in native_template
                node.config.text = '';
                node.config.title = '';
                node.config.image = '';
                node.config.options = '';
                delete node.config.template_ids;
                delete node.config.template_name;
                delete node.config.template_type;
                delete node.config.native_template;
            }
            ctx().updateNodePreview(id);
            ctx().markDirty();
            ctx().triggerAutoSave();
            M.openInspector(id);
        });
    }

    setTimeout(function() {
        if(node.type === 'buttons' && (ctx().BB.nodes[id].config.button_mode || 'quick') !== 'native') { M.dynBtnInit(id); }
        if(['buttons','list','cards'].indexOf(node.type) !== -1) { M.initNativeTemplateControls(id); }
        if(node.type === 'pic_choice') { M.dynPicInit(id); }
        if(node.type === 'rating') { M.dynRatingPreview(id); }
        if(node.type === 'buttons') { M.initMediaUpload(id, 'image', 'image'); }
        if(['image','video','audio'].indexOf(node.type) !== -1) { M.initMediaUpload(id, node.type); }
        if(node.type === 'file_upload') { M.dynFileTypeInit(id); }
        if(node.type === 'ai_reply') { M.initKnowledgeUpload(id); }
        if(node.type === 'intg_woocommerce') {
            var actionSel = document.getElementById('conf-woo_action');
            if(actionSel) {
                actionSel.addEventListener('change', function() {
                    var orderFields = document.getElementById('woo-order-fields');
                    var searchFields = document.getElementById('woo-search-fields');
                    if(orderFields) orderFields.style.display = this.value === 'get_order' ? '' : 'none';
                    if(searchFields) searchFields.style.display = this.value === 'search_products' ? '' : 'none';
                });
            }
        }
        ctx().updateNodePreview(id);
    }, 0);
}

// ===================== PROMOTE TO NATIVE =====================
window.promoteToNative = function(id) {
    var BB = window.BotBuilder ? window.BotBuilder.BB : null;
    if(!BB) return;
    var node = BB.nodes[id];
    if(!node || node.type !== 'buttons') return;

    var d = node.config || {};
    var options = d.options || '';
    var labels = options.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    if(labels.length < 1) {
        alert('Adicione pelo menos um item nos botões antes de promover.');
        return;
    }

    if(!confirm((d.template_ids && d.template_ids.indexOf('bb_promoted_') === 0 ? 'Atualizar' : 'Criar') + ' um template nativo reutilizável com este bloco?\n\nO template poderá ser usado em outros fluxos.')) return;

    var fd = new FormData();
    fd.append('block_id', id);
    fd.append('text', d.text || '');
    fd.append('title', d.title || '');
    fd.append('image', d.image || '');
    fd.append('options', options);
    if(d.template_ids) fd.append('ids', d.template_ids);
    fd.append(window.bsConfig.csrf_name, window.bsConfig.csrf_hash);

    fetch(window.bsConfig.base_url + 'bot-builder/promote_to_native', {
        method: 'POST', body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if(res.status === 'error') { alert(res.message); return; }
        // Preserve preview data from quick config, then switch to native
        node.config.template_ids = res.ids;
        node.config.template_name = res.name;
        node.config.template_type = '2';
        node.config.button_mode = 'native';
        window.showToast(res.message, 'success');
        var pers = window.BotBuilderModules && window.BotBuilderModules.persistence;
        if(pers && pers.triggerAutoSave) pers.triggerAutoSave();
        if(window.BotBuilderModules && window.BotBuilderModules.inspector && window.BotBuilderModules.inspector.openInspector) {
            window.BotBuilderModules.inspector.openInspector(id);
        }
    })
    .catch(function(err) {
        console.error('Promote error:', err);
        alert('Erro ao promover template: ' + err.message);
    });
};

M.buildInputInspector = buildInputInspector;
M.openInspector = openInspector;

})();
