(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
window.BotBuilderModules.simulator = window.BotBuilderModules.simulator || {};
var M = window.BotBuilderModules.simulator;

function ctx() { return M._ctx || {}; }
function getSim() { return ctx().getSim ? ctx().getSim() : null; }

function init(context) {
    M._ctx = context;
}

function nextNode(id, handle) {
    if(handle && handle !== 'default') {
        var edge = ctx().BB.edges.find(function(e) { return e.from === id && e.handle === handle; });
        if(edge) M.markEdge(edge);
        return edge ? edge.to : null;
    }
    var defaultEdge = ctx().BB.edges.find(function(e) { return e.from === id && (!e.handle || e.handle === 'default'); });
    if(defaultEdge) { M.markEdge(defaultEdge); return defaultEdge.to; }
    var anyEdge = ctx().BB.edges.find(function(e) { return e.from === id; });
    if(anyEdge) M.markEdge(anyEdge);
    return anyEdge ? anyEdge.to : null;
}

function resolveChoice(nodeId, text, options) {
    if(!options) options = {};
    var nx = nextNode(nodeId, text);
    if(!nx) {
        var edge = ctx().BB.edges.find(function(e) { return e.from === nodeId && e.handle && e.handle.toLowerCase().trim() === text.toLowerCase().trim(); });
        if(edge) { M.markEdge(edge); nx = edge.to; }
    }
    if(!nx && /^\d+$/.test(text.trim())) {
        var idx = parseInt(text.trim()) - 1;
        var btnEdges = ctx().BB.edges.filter(function(e) { return e.from === nodeId && e.handle && e.handle !== 'default'; });
        if(idx >= 0 && idx < btnEdges.length) { M.markEdge(btnEdges[idx]); nx = btnEdges[idx].to; }
    }
    if(!nx && options.partial !== false) {
        var tl = text.toLowerCase().trim();
        var edge2 = ctx().BB.edges.find(function(e) {
            if(e.from !== nodeId || !e.handle || e.handle === 'default') return false;
            var h = e.handle.toLowerCase().trim();
            return h.indexOf(tl) !== -1 || tl.indexOf(h) !== -1;
        });
        if(edge2) { M.markEdge(edge2); nx = edge2.to; }
    }
    if(!nx) nx = nextNode(nodeId);
    return nx;
}

function startSimulation() {
    var chat = ctx().$('#preview-chat');
    if(chat) chat.innerHTML = '<div class="chat-date">Hoje</div>';
    M.clearActiveNode();
    var nextSimState = { currentNode: null, context: {}, waiting: false, history: [], traversedEdges: [] };
    if(typeof ctx().setSim === 'function') ctx().setSim(nextSimState);
    M.updateVarsPanel();
    M.updateHistoryPanel();
    var start = Object.values(ctx().BB.nodes || {}).find(function(n) { return n.type === 'start'; });
    if(!start) { M.msg('⚠️ Nenhum bloco de início encontrado.'); return; }
    if(typeof ctx().runSim === 'function') ctx().runSim(start.id);
}

function processResponse(text) {
    var sim = getSim();
    if(!sim || !sim.waiting) return;
    var n = sim.currentNode;
    var inputTypes = ['input','input_text','input_number','input_email','input_website','input_date','input_time','input_phone','rating','file_upload'];
    var btnLikeTypes = ['buttons','list','pic_choice','cards'];

    if(sim.waiting === 'text' && inputTypes.indexOf(n.type) !== -1) {
        var validationResult = M.validateInput(n, text);
        if(!validationResult.valid) {
            var retryMsg2 = n.config.retry_message || validationResult.message || 'Invalid input. Please try again.';
            M.msg(ctx().replVars(retryMsg2, sim.context));
            return;
        }
        sim.context[n.config.variable || 'input'] = text;
    } else if(sim.waiting === 'button' && btnLikeTypes.indexOf(n.type) !== -1) {
        sim.context.last_selection = text;
        if(n.config.variable) sim.context[n.config.variable] = text;
    } else if(sim.waiting === 'button' && n.type === 'payment') {
        sim.context[n.config.variable || 'payment_status'] = 'paid';
    } else {
        sim.context.last_selection = text;
    }
    M.updateVarsPanel();
    sim.waiting = false;
    var nx = btnLikeTypes.indexOf(n.type) !== -1 ? M.resolveChoice(n.id, text) : nextNode(n.id);
    if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
}

async function executeSimpleBlock(nodeId) {
    var n = ctx().BB.nodes[nodeId];
    if(!n) return true;

    var simpleTypes = ['start','text','end','delay','image','video','audio','embed','input','input_text','input_email','input_website','input_phone','input_number','input_date','input_time','rating','file_upload','buttons','pic_choice','cards','list','payment','condition','ai_reply','set_variable','webhook','jump','script','ab_test','intg_sheets','intg_analytics','intg_http','intg_email','intg_zapier','intg_make','intg_pabbly','intg_chatwoot','intg_pixel','intg_segment','intg_posthog','intg_openai','intg_chatnode','intg_dify','intg_mistral','intg_anthropic','intg_together','intg_openrouter','intg_groq','intg_perplexity','intg_deepseek','intg_calcom','intg_qrcode','intg_elevenlabs','intg_nocodb','intg_zendesk','intg_blink','intg_gmail','intg_woocommerce'];
    if(simpleTypes.indexOf(n.type) === -1) return false;

    var sim = getSim();
    sim.currentNode = n;
    M.setActiveNode(nodeId);
    M.recordHistory(n);
    M.updateVarsPanel();
    if(n.type !== 'start') M.showTyping();
    await ctx().pause(500);

    if(n.type === 'start') {
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'text') {
        M.msg(ctx().replVars(n.config.text || '', sim.context));
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'image') {
        M.msg('📷 ' + (n.config.caption || n.config.url || 'Imagem'));
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'video') {
        M.msg('🎬 ' + (n.config.caption || n.config.url || 'Vídeo'));
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'audio') {
        M.msg('🎵 Mensagem de áudio');
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'embed') {
        M.msg('🔗 [Conteúdo incorporado: ' + (n.config.title || n.config.url || 'Link') + ']');
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'input' || n.type === 'input_text' || n.type === 'input_email' || n.type === 'input_website' || n.type === 'input_phone') {
        M.msg(ctx().replVars(n.config.question || '', sim.context));
        sim.waiting = 'text';
        return true;
    }
    if(n.type === 'input_number') {
        M.msg(ctx().replVars(n.config.question || '', sim.context) + (n.config.min || n.config.max ? '\n📊 Faixa: ' + (n.config.min || '∞') + ' – ' + (n.config.max || '∞') : ''));
        sim.waiting = 'text';
        return true;
    }
    if(n.type === 'input_date') {
        M.msg(ctx().replVars(n.config.question || '', sim.context) + '\n📅 Formato: ' + (n.config.format || 'YYYY-MM-DD'));
        sim.waiting = 'text';
        return true;
    }
    if(n.type === 'input_time') {
        M.msg(ctx().replVars(n.config.question || '', sim.context) + '\n🕐 Formato: ' + (n.config.format || 'HH:mm'));
        sim.waiting = 'text';
        return true;
    }
    if(n.type === 'rating') {
        var max = parseInt(n.config.max_stars) || 5;
        M.msg(ctx().replVars(n.config.question || '', sim.context) + '\n⭐ Avalie de 1 a ' + max);
        sim.waiting = 'text';
        return true;
    }
    if(n.type === 'file_upload') {
        M.msg(ctx().replVars(n.config.question || '', sim.context) + '\n📎 Permitidos: ' + (n.config.allowed_types || 'qualquer') + ' (Máx: ' + (n.config.max_size || '10') + 'MB)');
        sim.waiting = 'text';
        return true;
    }
    if(n.type === 'buttons') {
        var btns = (n.config.options || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
        M.buttons(ctx().replVars(n.config.text || '', sim.context), btns);
        sim.waiting = 'button';
        return true;
    }
    if(n.type === 'pic_choice') {
        var choices = (n.config.choices || '').split(',').map(function(s) { return s.trim().split('|')[0]; }).filter(Boolean);
        M.buttons(ctx().replVars(n.config.question || '', sim.context) + '\n🖼️ [Escolhas com imagem]', choices);
        sim.waiting = 'button';
        return true;
    }
    if(n.type === 'cards') {
        var cardLines = (n.config.cards_data || '').split('\n').filter(Boolean);
        var btns2 = cardLines.map(function(c) { var parts = c.split('|').map(function(s) { return (s || '').trim(); }); return parts[3] || parts[0] || ''; }).filter(Boolean);
        M.buttons('🃏 [Cards]', btns2.length ? btns2 : ['Card 1']);
        sim.waiting = 'button';
        return true;
    }
    if(n.type === 'list') {
        var opts = [];
        (n.config.sections || '').split('\n').forEach(function(line) { var parts = line.split('|'); if(parts[1]) parts[1].split(',').forEach(function(o) { if(o.trim()) opts.push(o.trim()); }); });
        if(opts.length > 0) { M.buttons(ctx().replVars(n.config.text || '', sim.context) + '\n📋 Selecione uma opção:', opts); }
        else { M.msg(ctx().replVars(n.config.text || '', sim.context) + '\n📋 [Menu de lista]'); }
        sim.waiting = 'button';
        return true;
    }
    if(n.type === 'payment') {
        M.msg('💳 Solicitação de pagamento: ' + (n.config.currency || 'BRL') + ' ' + (n.config.amount || '0') + '\n' + (n.config.description || ''));
        sim.waiting = 'button';
        return true;
    }
    if(n.type === 'condition') {
        var v = sim.context[n.config.variable] || '';
        var exp = n.config.expected || '';
        var op = n.config.operator || '==';
        var r = false;
        if(op === '==') r = v == exp;
        else if(op === '!=') r = v != exp;
        else if(op === 'contains') r = v.indexOf(exp) !== -1;
        else if(op === 'starts_with') r = v.indexOf(exp) === 0;
        else if(op === 'ends_with') r = v.lastIndexOf(exp) === v.length - exp.length;
        else if(op === '>') r = parseFloat(v) > parseFloat(exp);
        else if(op === '<') r = parseFloat(v) < parseFloat(exp);
        else if(op === 'is_empty') r = !v;
        else if(op === 'not_empty') r = !!v;
        var nx = nextNode(nodeId, r ? 'true' : 'false');
        if(!nx) nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'ai_reply') {
        M.msg('🤖 [Resposta de IA para: ' + ctx().replVars(n.config.prompt || '', sim.context).substring(0, 50) + '...]');
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'set_variable') {
        sim.context[n.config.variable] = ctx().replVars(n.config.value || '', sim.context);
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'webhook') {
        M.msg('🌐 [Webhook: ' + (n.config.method || 'POST') + ' ' + (n.config.url || '').substring(0, 30) + ']');
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'jump') {
        if(n.config.target_node && ctx().BB.nodes[n.config.target_node] && typeof ctx().runSim === 'function') ctx().runSim(n.config.target_node);
        return true;
    }
    if(n.type === 'script') {
        try {
            var res = 'executed';
            if(n.config.language === 'javascript') {
                var fn = new Function('context', n.config.code || 'return "done"');
                res = fn(sim.context) || 'executed';
            } else { res = '[Script PHP - executado no servidor]'; }
            sim.context[n.config.variable || 'script_result'] = String(res);
            M.updateVarsPanel();
            M.msg('📜 Script: ' + String(res).substring(0, 50));
        } catch(err) { M.msg('❌ Erro no script: ' + err.message); }
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'ab_test') {
        var pct = parseInt(n.config.variant_a_pct) || 50;
        var isA = Math.random() * 100 < pct;
        M.msg('🔀 AB Test → ' + (isA ? (n.config.variant_a_label || 'A') : (n.config.variant_b_label || 'B')));
        var nx = nextNode(nodeId, isA ? 'variant_a' : 'variant_b');
        if(!nx) nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_sheets') {
        M.msg('📊 Sheets: ' + (n.config.action || 'append_row') + ' → Sheet "' + (n.config.sheet_name || 'Sheet1') + '"');
        sim.context[n.config.variable || 'sheets_result'] = 'success';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_analytics') {
        M.msg('📈 Analytics: Event "' + (n.config.event_name || 'bot_event') + '" tracked');
        sim.context[n.config.variable || 'analytics_result'] = 'tracked';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_http') {
        M.msg('🌐 HTTP ' + (n.config.method || 'POST') + ' → ' + ctx().replVars(n.config.url || '', sim.context).substring(0, 40) + '...');
        sim.context[n.config.variable || 'http_response'] = '{"status":200,"data":"simulated"}';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_email') {
        M.msg('✉️ Email → ' + ctx().replVars(n.config.to || '', sim.context));
        sim.context[n.config.variable || 'email_status'] = 'sent';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_zapier') {
        M.msg('⚡ Zapier webhook triggered');
        sim.context[n.config.variable || 'zapier_result'] = 'triggered';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_make') {
        M.msg('⚙️ Make.com scenario triggered');
        sim.context[n.config.variable || 'make_result'] = 'triggered';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_pabbly') {
        M.msg('🔗 Pabbly workflow triggered');
        sim.context[n.config.variable || 'pabbly_result'] = 'triggered';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_chatwoot') {
        M.msg('💬 Chatwoot: ' + (n.config.action || 'create_contact'));
        sim.context[n.config.variable || 'chatwoot_result'] = 'success';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_pixel') {
        M.msg('🎯 Meta Pixel: Event "' + (n.config.event_name || 'Lead') + '" fired');
        sim.context[n.config.variable || 'pixel_result'] = 'fired';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_segment') {
        M.msg('📊 Segment: Event "' + (n.config.event_name || 'Bot Interaction') + '" tracked');
        sim.context[n.config.variable || 'segment_result'] = 'tracked';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_posthog') {
        M.msg('📊 Posthog: Event "' + (n.config.event_name || 'bot_event') + '" captured');
        sim.context[n.config.variable || 'posthog_result'] = 'captured';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_openai') {
        var p = ctx().replVars(n.config.prompt || '', sim.context);
        M.msg('🧠 OpenAI (' + (n.config.model || 'gpt-4o-mini') + '): "' + p.substring(0, 40) + '..."');
        sim.context[n.config.variable || 'openai_reply'] = '[Simulated OpenAI response for: ' + p.substring(0, 30) + ']';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_chatnode') {
        M.msg('🤖 ChatNode query sent');
        sim.context[n.config.variable || 'chatnode_reply'] = '[Simulated ChatNode reply]';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_dify') {
        M.msg('🪄 Dify.AI query sent');
        sim.context[n.config.variable || 'dify_reply'] = '[Simulated Dify response]';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_mistral') {
        M.msg('🌬️ Mistral (' + (n.config.model || 'mistral-medium') + ') query sent');
        sim.context[n.config.variable || 'mistral_reply'] = '[Simulated Mistral response]';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_anthropic') {
        M.msg('🪶 Claude (' + (n.config.model || 'claude-3.5-sonnet') + ') query sent');
        sim.context[n.config.variable || 'claude_reply'] = '[Simulated Claude response]';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_together') {
        M.msg('👥 Together AI (' + (n.config.model || 'Llama-3').split('/').pop() + ') query sent');
        sim.context[n.config.variable || 'together_reply'] = '[Simulated Together response]';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_openrouter') {
        M.msg('🚀 OpenRouter (' + (n.config.model || 'gpt-4o-mini') + ') query sent');
        sim.context[n.config.variable || 'openrouter_reply'] = '[Simulated OpenRouter response]';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_groq') {
        M.msg('⚡ Groq (' + (n.config.model || 'llama-3.1-70b') + ') ultra-fast inference');
        sim.context[n.config.variable || 'groq_reply'] = '[Simulated Groq response]';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_perplexity') {
        M.msg('🔍 Perplexity (' + (n.config.model || 'sonar-small') + ') search query sent');
        sim.context[n.config.variable || 'perplexity_reply'] = '[Simulated Perplexity response with web results]';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_deepseek') {
        M.msg('⚛️ DeepSeek (' + (n.config.model || 'deepseek-chat') + ') query sent');
        sim.context[n.config.variable || 'deepseek_reply'] = '[Simulated DeepSeek response]';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_calcom') {
        M.msg('📅 Cal.com: ' + (n.config.action || 'get_availability'));
        sim.context[n.config.variable || 'calcom_result'] = '{"available":true}';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_qrcode') {
        var data = ctx().replVars(n.config.data || '', sim.context);
        var url = 'https://api.qrserver.com/v1/create-qr-code/?size=' + (n.config.size || '300') + 'x' + (n.config.size || '300') + '&data=' + encodeURIComponent(data);
        M.msg('📱 QR Code generated for: ' + data.substring(0, 30));
        sim.context[n.config.variable || 'qr_url'] = url;
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_elevenlabs') {
        M.msg('🔊 ElevenLabs TTS: generating audio...');
        sim.context[n.config.variable || 'audio_url'] = 'https://example.com/simulated_audio.mp3';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_nocodb') {
        M.msg('🗄️ NocoDB: ' + (n.config.action || 'list') + ' on table');
        sim.context[n.config.variable || 'nocodb_result'] = '{"success":true}';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_zendesk') {
        M.msg('🎫 Zendesk: ' + (n.config.action || 'create_ticket'));
        sim.context[n.config.variable || 'zendesk_result'] = '{"ticket_id":12345}';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_blink') {
        M.msg('💡 Blink: ' + (n.config.action || 'send_notification'));
        sim.context[n.config.variable || 'blink_result'] = 'success';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_gmail') {
        M.msg('📧 Gmail → ' + ctx().replVars(n.config.to || '', sim.context));
        sim.context[n.config.variable || 'gmail_status'] = 'sent';
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'intg_woocommerce') {
        var wooAction = n.config.woo_action || 'get_order';
        var varName = n.config.variable || 'woo_result';
        if(wooAction === 'get_order') {
            var oid = ctx().replVars(n.config.order_id || '', sim.context);
            M.msg('🛒 WooCommerce: Looking up order #' + oid);
            sim.context[varName] = '🛒 *Order #' + oid + '*\n\n✅ *Status:* Processing\n👤 *Customer:* John Doe\n💵 *Total:* USD 99.99';
            sim.context[varName + '_status'] = 'processing';
            sim.context[varName + '_total'] = 'USD 99.99';
            sim.context[varName + '_error'] = 'false';
        } else if(wooAction === 'search_products') {
            M.msg('🔍 WooCommerce: Searching "' + ctx().replVars(n.config.search_query || '', sim.context) + '"');
            sim.context[varName] = '1. *Sample Product* — 29.99\n2. *Another Product* — 49.99';
            sim.context[varName + '_count'] = '2';
        } else {
            M.msg('📂 WooCommerce: Getting categories');
            sim.context[varName] = '1. Electronics (12)\n2. Clothing (8)\n3. Home (5)';
        }
        M.updateVarsPanel();
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    if(n.type === 'end') {
        M.msg('✅ Fim do fluxo.');
        sim.waiting = false;
        M.clearActiveNode();
        return true;
    }
    if(n.type === 'delay') {
        M.msg('⏳ Aguardando ' + (n.config.seconds||3) + 's...');
        await ctx().pause((n.config.seconds||3)*1000);
        var nx = nextNode(nodeId);
        if(nx && typeof ctx().runSim === 'function') ctx().runSim(nx);
        return true;
    }
    return false;
}

function validateInput(node, text) {
    var type = node.type;
    var d = node.config || {};
    if((d.required||'true') === 'true' && (!text || !text.trim())) {
        return {valid: false, message: d.retry_message || 'This field is required.'};
    }
    if(type === 'input_text') {
        if(d.min_length && text.length < parseInt(d.min_length)) return {valid: false, message: 'Please enter at least ' + d.min_length + ' characters.'};
        if(d.max_length && text.length > parseInt(d.max_length)) return {valid: false, message: 'Please enter at most ' + d.max_length + ' characters.'};
        if(d.regex) { try { if(!new RegExp(d.regex).test(text)) return {valid: false, message: d.regex_error || 'Input does not match the expected format.'}; } catch(e){} }
    }
    if(type === 'input_email') {
        if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(text)) return {valid: false, message: 'This email doesn\'t seem to be valid. Can you check it?'};
    }
    if(type === 'input_website') {
        if(!/^https?:\/\/.+/i.test(text)) return {valid: false, message: 'Please enter a valid URL starting with https://.'};
    }
    if(type === 'input_number') {
        var num = parseFloat(text);
        if(isNaN(num)) return {valid: false, message: 'Please enter a valid number.'};
        if(d.min !== '' && d.min !== undefined && num < parseFloat(d.min)) return {valid: false, message: 'Number must be at least ' + d.min + '.'};
        if(d.max !== '' && d.max !== undefined && num > parseFloat(d.max)) return {valid: false, message: 'Number must be at most ' + d.max + '.'};
    }
    if(type === 'input_phone') {
        var cleaned = text.replace(/[\s\-\(\)]/g, '');
        if(!/^[\+]?[0-9]{7,15}$/.test(cleaned)) return {valid: false, message: 'Please enter a valid phone number.'};
    }
    if(type === 'input_date') {
        var fmt = d.format || 'YYYY-MM-DD';
        if(fmt === 'YYYY-MM-DD' && !/^\d{4}-\d{2}-\d{2}$/.test(text)) return {valid: false, message: 'Please enter a date in YYYY-MM-DD format.'};
        if(fmt === 'DD/MM/YYYY' && !/^\d{2}\/\d{2}\/\d{4}$/.test(text)) return {valid: false, message: 'Please enter a date in DD/MM/YYYY format.'};
        if(fmt === 'MM/DD/YYYY' && !/^\d{2}\/\d{2}\/\d{4}$/.test(text)) return {valid: false, message: 'Please enter a date in MM/DD/YYYY format.'};
        if(fmt === 'DD-MM-YYYY' && !/^\d{2}-\d{2}-\d{4}$/.test(text)) return {valid: false, message: 'Please enter a date in DD-MM-YYYY format.'};
    }
    if(type === 'input_time') {
        if(!/^\d{1,2}:\d{2}/.test(text)) return {valid: false, message: 'Please enter a valid time (e.g. 14:30).'};
    }
    if(type === 'rating') {
        var num2 = parseInt(text);
        var max2 = parseInt(d.max_stars) || 5;
        if(isNaN(num2) || num2 < 1 || num2 > max2) return {valid: false, message: 'Please enter a rating between 1 and ' + max2 + '.'};
    }
    return {valid: true};
}

M.init = init;
M.nextNode = nextNode;
M.resolveChoice = resolveChoice;
M.startSimulation = startSimulation;
M.processResponse = processResponse;
M.executeSimpleBlock = executeSimpleBlock;
M.validateInput = validateInput;

})();
