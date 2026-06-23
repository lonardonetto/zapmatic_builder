(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
window.BotBuilderModules.inspector = window.BotBuilderModules.inspector || {};
var M = window.BotBuilderModules.inspector;

function ctx() { return M._ctx || {}; }

function nativeTypeForNode(type) {
    if(type === 'buttons') return 2;
    if(type === 'list') return 1;
    if(type === 'cards') return 5;
    return 0;
}

function nativeLabelForType(type) {
    return type === 1 ? 'lista nativa' : (type === 2 ? 'botões nativos' : 'carrossel nativo');
}

function nativeTemplateInspector(node, d) {
    var type = nativeTypeForNode(node.type);
    var selectedName = d.template_name || (d.template_ids ? 'Template selecionado' : 'Nenhum template selecionado');
    return '<div class="form-section native-template-box" data-native-type="' + type + '">\n        <div class="form-section-title"><i class="fas fa-layer-group"></i> Template nativo global</div>\n        <div class="form-group">\n            <label>Selecionar ' + nativeLabelForType(type) + '</label>\n            <select class="form-control native-template-select" id="conf-template_ids" data-type="' + type + '">\n                <option value="' + ctx().escHtml(d.template_ids || '') + '">' + ctx().escHtml(selectedName) + '</option>\n            </select>\n        </div>\n        <input type="hidden" id="conf-template_type" value="' + type + '">\n        <input type="hidden" id="conf-template_name" value="' + ctx().escHtml(d.template_name || '') + '">\n        <div class="native-template-actions" style="display:flex;gap:8px;margin-bottom:10px;">\n            <button type="button" class="btn-add-dynamic native-template-create" data-type="' + type + '"><i class="fas fa-plus"></i> Criar novo</button>\n            <button type="button" class="btn-add-dynamic native-template-edit" data-type="' + type + '" ' + (d.template_ids ? '' : 'disabled') + '><i class="fas fa-edit"></i> Editar</button>\n            <button type="button" class="btn-add-dynamic native-template-refresh" data-type="' + type + '"><i class="fas fa-sync"></i></button>\n        </div>\n        <div class="native-template-preview" id="native-template-preview">' + renderNativeTemplatePreview(d.native_template || null, type) + '</div>\n    </div>';
}

function renderNativeTemplatePreview(tpl, type) {
    if(!tpl || !tpl.data) return '<div class="form-hint">Selecione um template para carregar o preview e as saídas do node.</div>';
    var data = tpl.data || {};
    var text = data.text || data.caption || data.title || '';
    var compactText = text.length > 110 ? text.substring(0, 110) + '...' : text;
    var title = ctx().escHtml(tpl.name || (type === 5 ? 'Carrossel' : (type === 1 ? 'Lista' : 'Botões')));

    if(type === 2) {
        var buttons = Array.isArray(data.templateButtons) ? data.templateButtons : [];
        var labels = buttons.map(function(btn) {
            return btn.quickReplyButton?.displayText || btn.urlButton?.displayText || btn.callButton?.displayText || 'Botão';
        }).filter(Boolean);
        var rows = labels.slice(0, 6).map(function(label, i) {
            var prefix = 'Botão ' + (i + 1);
            if(buttons[i] && buttons[i].urlButton) prefix = 'URL ' + (i + 1);
            else if(buttons[i] && buttons[i].callButton) prefix = 'Tel. ' + (i + 1);
            else if(buttons[i] && (buttons[i].urlButton?.url || '').indexOf('otp_type=COPY_CODE') !== -1) prefix = 'Cópia ' + (i + 1);
            return '<div class="native-preview-row"><span>' + prefix + '</span><b title="' + ctx().escHtml(label) + '">' + ctx().escHtml(label) + '</b></div>';
        }).join('');
        return '<div class="native-preview-card"><div class="native-preview-head"><b>' + title + '</b><small>Botões · ' + labels.length + ' saída(s)</small></div>' + (compactText ? '<div class="native-preview-text" title="' + ctx().escHtml(text) + '">' + ctx().escHtml(compactText) + '</div>' : '') + '<div class="native-preview-list">' + (rows || '<small>Sem botões detectados</small>') + '</div></div>';
    }
    if(type === 1) {
        var sections = Array.isArray(data.sections) ? data.sections : [];
        var rows = [];
        sections.forEach(function(sec) { (sec.rows || []).forEach(function(row) { rows.push({ section: sec.title || 'Menu', label: row.title || row.rowId || 'Opção' }); }); });
        var rowHtml = rows.slice(0, 8).map(function(row) { return '<div class="native-preview-row"><span>' + ctx().escHtml(row.section) + '</span><b title="' + ctx().escHtml(row.label) + '">' + ctx().escHtml(row.label) + '</b></div>'; }).join('');
        return '<div class="native-preview-card"><div class="native-preview-head"><b>' + title + '</b><small>Lista · ' + rows.length + ' opção(ões)</small></div>' + (compactText ? '<div class="native-preview-text" title="' + ctx().escHtml(text) + '">' + ctx().escHtml(compactText) + '</div>' : '') + '<div class="native-preview-list">' + (rowHtml || '<small>Sem opções detectadas</small>') + '</div></div>';
    }
    var cards = Array.isArray(data.cards) ? data.cards : [];
    var rows = cards.slice(0, 8).map(function(card, i) {
        var label = card.title || card.body || 'Card ' + (i + 1);
        return '<div class="native-preview-row"><span>Card ' + (i + 1) + '</span><b title="' + ctx().escHtml(label) + '">' + ctx().escHtml(label.length > 45 ? label.substring(0, 45) + '...' : label) + '</b></div>';
    }).join('');
    return '<div class="native-preview-card"><div class="native-preview-head"><b>' + title + '</b><small>Carrossel · ' + cards.length + ' card(s)</small></div>' + (compactText ? '<div class="native-preview-text" title="' + ctx().escHtml(text) + '">' + ctx().escHtml(compactText) + '</div>' : '') + '<div class="native-preview-list">' + (rows || '<small>Sem cards detectados</small>') + '</div></div>';
}

function nativeOptionEntries(tpl, type) {
    var entries = [];
    var data = tpl && tpl.data ? tpl.data : null;
    if(!data) return entries;
    if(type === 2) {
        (data.templateButtons || []).forEach(function(btn) {
            var q = btn.quickReplyButton || null;
            if(q) entries.push({ id: q.id || q.displayText, label: q.displayText || q.id });
            var u = btn.urlButton || null;
            if(u) entries.push({ id: u.displayText || 'link', label: u.displayText || 'Link' });
            var c = btn.callButton || null;
            if(c) entries.push({ id: c.displayText || 'call', label: c.displayText || 'Ligar' });
        });
    } else if(type === 1) {
        (data.sections || []).forEach(function(sec) { (sec.rows || []).forEach(function(row) { entries.push({ id: row.rowId || row.title, label: row.title || row.rowId }); }); });
    } else if(type === 5) {
        (data.cards || []).forEach(function(card) { (card.buttons || []).forEach(function(btn) {
            var params = btn.buttonParamsJson || btn.paramsJson || '{}';
            if(typeof params === 'string') { try { params = JSON.parse(params); } catch(e) { params = {}; } }
            entries.push({ id: params.id || params.display_text || btn.id || btn.displayText, label: params.display_text || btn.displayText || params.id || 'Botão' });
        }); });
    }
    return entries.filter(function(e) { return e.id || e.label; });
}

async function loadNativeTemplatesIntoSelect(nodeId, type) {
    var selectEl = document.querySelector('.native-template-select');
    if(!selectEl || !window.bsConfig?.native_templates_url) return;
    var current = (ctx().BB.nodes[nodeId] || {}).config?.template_ids || '';
    selectEl.innerHTML = '<option value="">Carregando...</option>';
    var response = await fetch(window.bsConfig.native_templates_url + '?type=' + type, { headers:{'X-Requested-With':'XMLHttpRequest'} });
    var result = await response.json();
    if(result.status !== 'success') throw new Error(result.message || 'Falha ao carregar templates');
    selectEl.innerHTML = '<option value="">Selecione um template</option>' + (result.data || []).map(function(t) { return '<option value="' + ctx().escHtml(t.ids) + '" ' + (current === t.ids ? 'selected' : '') + '>' + ctx().escHtml(t.name) + '</option>'; }).join('');
    selectEl.dataset.createUrl = result.create_url || '';
}

async function loadNativeTemplate(nodeId, ids) {
    if(!ids || !window.bsConfig?.native_template_url) return null;
    var response = await fetch(window.bsConfig.native_template_url + '/' + encodeURIComponent(ids), { headers:{'X-Requested-With':'XMLHttpRequest'} });
    var result = await response.json();
    if(result.status !== 'success') throw new Error(result.message || 'Falha ao carregar template');
    var node = ctx().BB.nodes[nodeId];
    if(node) {
        node.config.template_ids = result.data.ids;
        node.config.template_type = String(result.data.type);
        node.config.template_name = result.data.name;
        node.config.native_template = result.data;
    }
    return result.data;
}

function initNativeTemplateControls(nodeId) {
    var c = ctx();
    var node = c.BB.nodes[nodeId];
    if(!node) return;
    var type = nativeTypeForNode(node.type);
    var selectEl = document.querySelector('.native-template-select');
    if(!selectEl) return;

    loadNativeTemplatesIntoSelect(nodeId, type).catch(function(err) { console.warn(err); });
    if(node.config.template_ids && !node.config.native_template) {
        loadNativeTemplate(nodeId, node.config.template_ids).then(function(tpl) {
            if(tpl) {
                var previewEl = document.getElementById('native-template-preview');
                if(previewEl) previewEl.innerHTML = renderNativeTemplatePreview(tpl, type);
                c.updateNodePreview(nodeId);
                c.markDirty();
            }
        }).catch(function(err) { console.warn(err); });
    }

    selectEl.addEventListener('change', async function() {
        try {
            var tpl = await loadNativeTemplate(nodeId, selectEl.value);
            if(tpl) {
                document.getElementById('native-template-preview').innerHTML = renderNativeTemplatePreview(tpl, type);
                c.updateNodePreview(nodeId);
                c.markDirty();
                c.triggerAutoSave();
                // Rebuild handles to match new template buttons and remove orphaned edges
                if(typeof M.rebuildButtonHandles === 'function') M.rebuildButtonHandles(nodeId);
            }
        } catch(error) { alert(error.message); }
    });

    var buildReturnUrl = function() { return encodeURIComponent(window.bsConfig.native_return_url + '?node=' + nodeId); };
    var createBtn = document.querySelector('.native-template-create');
    if(createBtn) createBtn.addEventListener('click', function() {
        var base = selectEl.dataset.createUrl || '';
        if(base) window.location.href = base + '?wa_return=' + buildReturnUrl();
    });
    var editBtn = document.querySelector('.native-template-edit');
    if(editBtn) editBtn.addEventListener('click', function() {
        var tpl = node.config.native_template;
        if(!tpl) return;
        var editUrl = tpl.edit_url || '';
        if(tpl.ids && window.bsConfig.base_url) {
            var routes = {
                1: 'whatsapp_list_message_template/index/update/',
                2: 'whatsapp_button_template/index/update/',
                5: 'whatsapp_carousel_template/index/update/'
            };
            if(routes[type]) editUrl = window.bsConfig.base_url + routes[type] + tpl.ids;
        }
        if(editUrl) window.location.href = editUrl + '?wa_return=' + buildReturnUrl();
    });
    var refreshBtn = document.querySelector('.native-template-refresh');
    if(refreshBtn) refreshBtn.addEventListener('click', function() { loadNativeTemplatesIntoSelect(nodeId, type).catch(function(err) { alert(err.message); }); });
}

M.nativeTypeForNode = nativeTypeForNode;
M.nativeLabelForType = nativeLabelForType;
M.nativeTemplateInspector = nativeTemplateInspector;
M.renderNativeTemplatePreview = renderNativeTemplatePreview;
M.nativeOptionEntries = nativeOptionEntries;
M.loadNativeTemplatesIntoSelect = loadNativeTemplatesIntoSelect;
M.loadNativeTemplate = loadNativeTemplate;
M.initNativeTemplateControls = initNativeTemplateControls;

})();
