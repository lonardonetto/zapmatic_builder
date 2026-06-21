(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
window.BotBuilderModules.inspector = window.BotBuilderModules.inspector || {};
const M = window.BotBuilderModules.inspector;

M.init = function(options) {
    M._ctx = options || {};
};

function renderWhatsAppPreview(node) {
    const ctx = M._ctx;
    const config = node.config || {};
    const type = node.type;
    const text = String(config.text || config.question || config.caption || config.prompt || '').trim();
    const mediaUrl = String(config.url || '').trim();
    const title = type === 'buttons' ? 'Botões' : (type === 'list' ? 'Lista' : (type === 'cards' ? 'Carrossel' : 'Mensagem'));
    let body = '';

    if(['image', 'video'].includes(type) && mediaUrl) {
        body += `<div class="wa-preview-media ${type}">${type === 'image' ? `<img src="${ctx.escHtml(mediaUrl)}" alt="Prévia">` : `<video src="${ctx.escHtml(mediaUrl)}" muted playsinline></video>`}</div>`;
    }

    if(type === 'audio' && mediaUrl) {
        body += '<div class="wa-preview-audio"><i class="fas fa-play"></i><span>Áudio anexado</span></div>';
    }

    body += `<div class="wa-preview-bubble-text">${ctx.escHtml(text || config.template_name || 'Configure a mensagem deste bloco...')}</div>`;

    if(type === 'buttons') {
        let labels = [];
        if((config.button_mode || 'quick') === 'native' && config.native_template) {
            labels = M.nativeOptionEntries(config.native_template, 2).map(function(e) { return e.label; }).filter(Boolean).slice(0, 10);
        } else {
            labels = String(config.options || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean).slice(0, 10);
        }
        if(labels.length) body += '<div class="wa-preview-actions">' + labels.map(function(label) { return '<div class="wa-preview-action"><i class="fas fa-reply"></i>' + ctx.escHtml(label) + '</div>'; }).join('') + '</div>';
    }

    if(type === 'list' && config.native_template) {
        var labels = M.nativeOptionEntries(config.native_template, 1).map(function(e) { return e.label; }).filter(Boolean).slice(0, 8);
        if(labels.length) body += '<div class="wa-preview-actions">' + labels.map(function(label) { return '<div class="wa-preview-action list"><i class="fas fa-list"></i>' + ctx.escHtml(label) + '</div>'; }).join('') + '</div>';
    }

    if(type === 'cards' && config.native_template) {
        var labels = M.nativeOptionEntries(config.native_template, 5).map(function(e) { return e.label; }).filter(Boolean).slice(0, 6);
        if(labels.length) body += '<div class="wa-preview-actions">' + labels.map(function(label) { return '<div class="wa-preview-action card"><i class="fas fa-images"></i>' + ctx.escHtml(label) + '</div>'; }).join('') + '</div>';
    }

    return '<div class="wa-inspector-preview" id="wa-inspector-preview"><div class="wa-preview-title"><i class="fab fa-whatsapp"></i><span>Prévia WhatsApp</span><small>' + ctx.escHtml(title) + '</small></div><div class="wa-preview-phone"><div class="wa-preview-bubble">' + body + '</div></div></div>';
}

function refreshWhatsAppPreview(id) {
    const ctx = M._ctx;
    const target = document.getElementById('wa-inspector-preview');
    if(target && ctx.BB.nodes[id]) {
        target.outerHTML = renderWhatsAppPreview(ctx.BB.nodes[id]);
    }
}

function field(tag, elId, label, value, placeholder, rows) {
    const ctx = M._ctx;
    value = value != null ? value : '';
    if(tag === 'textarea') {
        return '<div class="form-group"><label>' + label + '</label><textarea class="form-control" id="' + elId + '" rows="' + (rows||3) + '" placeholder="' + (placeholder||'') + '">' + ctx.escHtml(String(value)) + '</textarea></div>';
    }
    return '<div class="form-group"><label>' + label + '</label><input class="form-control" id="' + elId + '" value="' + ctx.escHtml(String(value)) + '" placeholder="' + (placeholder||'') + '"></div>';
}

function mediaUploadField(type, value) {
    const ctx = M._ctx;
    const labels = { image: 'Imagem', video: 'Vídeo', audio: 'Áudio', document: 'Arquivo' };
    const accept = {
        image: 'image/jpeg,image/png,image/gif,image/webp',
        video: 'video/mp4,video/quicktime,video/webm',
        audio: 'audio/mpeg,audio/ogg,audio/wav,audio/mp4,audio/aac',
        document: '.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt'
    };
    const icon = { image: 'fa-image', video: 'fa-video', audio: 'fa-microphone', document: 'fa-file-alt' }[type] || 'fa-paperclip';
    var preview = value && type === 'image' ? '<img src="' + ctx.escHtml(String(value)) + '" alt="Prévia">' : '<i class="fas ' + icon + '"></i>';
    var current = value ? '<div class="bb-media-current"><div class="bb-media-thumb ' + type + '">' + preview + '</div><div><strong>Mídia carregada</strong><a href="' + ctx.escHtml(String(value)) + '" target="_blank" rel="noopener">Abrir arquivo</a></div></div>' : '<div class="bb-media-current muted"><div class="bb-media-thumb ' + type + '"><i class="fas ' + icon + '"></i></div><div><strong>Nenhuma mídia enviada</strong><span>Escolha um arquivo para anexar ao bloco.</span></div></div>';
    return '<div class="form-group bb-media-upload-wrap">\n        <label>' + (labels[type] || 'Mídia') + '</label>\n        <input type="hidden" id="conf-url" value="' + ctx.escHtml(String(value || '')) + '">\n        <div class="bb-media-upload-box">\n            <input type="file" class="bb-media-file" data-media-type="' + type + '" accept="' + (accept[type] || '') + '">\n            <button type="button" class="btn-add-dynamic bb-media-upload-btn"><i class="fas fa-cloud-upload-alt"></i> Enviar ' + (labels[type] || 'mídia') + '</button>\n        </div>\n        ' + current + '\n        <div class="form-hint">Use upload real. Não é necessário informar URL manualmente.</div>\n    </div>';
}

function select(elId, label, value, options) {
    var opts = options.map(function(o) { return '<option value="' + o + '" ' + (value==o?'selected':'') + '>' + o + '</option>'; }).join('');
    return '<div class="form-group"><label>' + label + '</label><select class="form-control" id="' + elId + '">' + opts + '</select></div>';
}

function selectCustom(elId, label, value, options) {
    var opts = '<option value="">-- Select --</option>' + options.map(function(o) {
        var parts = o.split('|');
        return '<option value="' + parts[0] + '" ' + (value==parts[0]?'selected':'') + '>' + (parts[1]||parts[0]) + '</option>';
    }).join('');
    return '<div class="form-group"><label>' + label + '</label><select class="form-control" id="' + elId + '">' + opts + '</select></div>';
}

function insertTextAtCursor(el, text) {
    if(el.selectionStart !== undefined) {
        var s = el.selectionStart;
        var e = el.selectionEnd;
        el.value = el.value.substring(0, s) + text + el.value.substring(e);
        el.selectionStart = el.selectionEnd = s + text.length;
    } else {
        el.value += text;
    }
    el.dispatchEvent(new Event('input', {bubbles: true}));
    el.focus();
}

function varInsertHint(targetId) {
    return '<div class="form-hint" style="display:flex;align-items:center;gap:6px;">\n        <span>Use <code style="background:#f5f3ff;padding:1px 4px;border-radius:3px;color:#7c3aed;">{{variable}}</code> for dynamic values</span>\n        <button class="form-control-var-btn" style="position:static;transform:none;width:auto;height:auto;padding:2px 6px;border:1px solid #e5e7eb;border-radius:4px;font-size:10px;"\n            onclick="insertVarIntoField(\'' + targetId + '\')">{ }</button>\n    </div>';
}

M.renderWhatsAppPreview = renderWhatsAppPreview;
M.refreshWhatsAppPreview = refreshWhatsAppPreview;
M.field = field;
M.mediaUploadField = mediaUploadField;
M.select = select;
M.selectCustom = selectCustom;
M.insertTextAtCursor = insertTextAtCursor;
M.varInsertHint = varInsertHint;

})();
