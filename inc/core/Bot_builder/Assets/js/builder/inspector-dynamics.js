(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
window.BotBuilderModules.inspector = window.BotBuilderModules.inspector || {};
var M = window.BotBuilderModules.inspector;

function ctx() { return M._ctx || {}; }

var _dynNodeId = null;

// ===================== BUTTONS Dynamic Builder =====================

function dynBtnInit(nodeId) {
    _dynNodeId = nodeId;
    var raw = (ctx().BB.nodes[nodeId].config || {}).options || '';
    var items = raw.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    var list = document.getElementById('dyn-btn-list');
    if(!list) return;
    list.innerHTML = '';
    items.forEach(function(item, i) { _dynBtnRow(list, item, i); });
    if(items.length === 0) { dynBtnAdd(); }
}

function _dynBtnRow(container, label, idx) {
    var row = document.createElement('div');
    row.className = 'dyn-item-row dyn-btn-row';
    row.innerHTML = '<div class="dyn-item-order"><i class="fas fa-grip-vertical"></i><span>' + (idx+1) + '</span></div>\n        <div class="dyn-item-main"><label>Botão ' + (idx+1) + '</label>\n            <input class="form-control dyn-btn-inp" value="' + ctx().escHtml(label) + '" placeholder="Texto do botão">\n        </div>\n        <button type="button" class="dyn-item-del" onclick="this.closest(\'.dyn-item-row\').remove();dynBtnSync()" title="Remover"><i class="fas fa-times"></i></button>';
    row.querySelector('.dyn-btn-inp').addEventListener('input', function() { dynBtnSync(); });
    container.appendChild(row);
}

window.dynBtnAdd = function() {
    var list = document.getElementById('dyn-btn-list');
    if(!list) return;
    var count = list.querySelectorAll('.dyn-item-row').length;
    if(count >= 10) { ctx().showToast('O Zapmatic permite no máximo 10 botões via Native Flow', 'error'); return; }
    _dynBtnRow(list, '', count);
    list.lastElementChild.querySelector('input').focus();
    dynBtnSync();
};

function dynBtnSync() {
    if(!_dynNodeId) return;
    var inputs = document.querySelectorAll('#dyn-btn-list .dyn-btn-inp');
    var vals = Array.from(inputs).map(function(i) { return i.value.trim(); }).filter(Boolean);
    var optsEl = document.getElementById('conf-options');
    var str = vals.join(', ');
    if(optsEl) optsEl.value = str;
    ctx().BB.nodes[_dynNodeId].config.options = str;
    ctx().updateNodePreview(_dynNodeId);
    var refFn = M.refreshWhatsAppPreview || (ctx().refreshWhatsAppPreview ? function(id2) { ctx().refreshWhatsAppPreview(id2); } : null);
    if(refFn) refFn(_dynNodeId);
    ctx().markDirty();
    ctx().triggerAutoSave();
    document.querySelectorAll('#dyn-btn-list .dyn-item-row').forEach(function(r, i) {
        var order = r.querySelector('.dyn-item-order span');
        var label2 = r.querySelector('.dyn-item-main label');
        if(order) order.textContent = i+1;
        if(label2) label2.textContent = 'Botão '+(i+1);
    });
    if(typeof M.rebuildButtonHandles === 'function') M.rebuildButtonHandles(_dynNodeId);
}

// ===================== PIC CHOICE Dynamic Builder =====================

function dynPicInit(nodeId) {
    _dynNodeId = nodeId;
    var raw = (ctx().BB.nodes[nodeId].config || {}).choices || '';
    var items = raw.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    var list = document.getElementById('dyn-pic-list');
    if(!list) return;
    list.innerHTML = '';
    items.forEach(function(item, i) {
        var parts = item.split('|').map(function(s) { return (s||'').trim(); });
        _dynPicRow(list, parts[0]||'', parts[1]||'', i);
    });
    if(items.length === 0) { dynPicAdd(); }
}

function _dynPicRow(container, label, img, idx) {
    var row = document.createElement('div');
    row.className = 'dyn-item-row';
    row.style.cssText = 'display:flex;gap:6px;align-items:center;margin-bottom:8px;padding:8px;background:rgba(148,163,184,0.08);border-radius:8px;';
    row.innerHTML = '<div style="flex:1;display:flex;flex-direction:column;gap:4px;">\n        <input class="form-control dyn-pic-label" value="' + ctx().escHtml(label||'') + '" placeholder="Texto da opção" style="font-size:12px;">\n        <input type="hidden" class="dyn-pic-img" value="' + ctx().escHtml(img||'') + '">\n        <div style="display:flex;gap:6px;align-items:center;">\n            <button type="button" class="btn-add-dynamic dyn-media-upload-btn" onclick="this.nextElementSibling.click()"><i class="fas fa-upload"></i> Enviar imagem</button>\n            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="uploadDynamicMedia(this,\'.dyn-pic-img\',\'pic\')">\n            <span class="dyn-media-status" style="font-size:11px;color:#64748b;">' + (img ? 'Imagem carregada' : 'Sem imagem') + '</span>\n        </div>\n    </div>\n    <button type="button" class="dyn-item-del" onclick="this.parentElement.remove();dynPicSync()" title="Remover" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:14px;padding:2px 4px;"><i class="fas fa-times"></i></button>';
    row.querySelectorAll('input').forEach(function(inp) { inp.addEventListener('input', function() { dynPicSync(); }); });
    container.appendChild(row);
}

window.dynPicAdd = function() {
    var list = document.getElementById('dyn-pic-list');
    if(!list) return;
    _dynPicRow(list, '', '', list.querySelectorAll('.dyn-item-row').length);
    list.lastElementChild.querySelector('input').focus();
    dynPicSync();
};

function dynPicSync() {
    if(!_dynNodeId) return;
    var rows = document.querySelectorAll('#dyn-pic-list .dyn-item-row');
    var vals = Array.from(rows).map(function(r) {
        var label = r.querySelector('.dyn-pic-label').value.trim();
        var img = r.querySelector('.dyn-pic-img').value.trim();
        return label ? (label + (img ? '|' + img : '')) : '';
    }).filter(Boolean);
    var str = vals.join(',');
    var el = document.getElementById('conf-choices');
    if(el) el.value = str;
    ctx().BB.nodes[_dynNodeId].config.choices = str;
    ctx().updateNodePreview(_dynNodeId);
    ctx().markDirty();
    ctx().triggerAutoSave();
}

// ===================== CARDS Dynamic Builder =====================

function dynCardInit(nodeId) {
    _dynNodeId = nodeId;
    var raw = (ctx().BB.nodes[nodeId].config || {}).cards_data || '';
    var lines = raw.split('\n').filter(Boolean);
    var list = document.getElementById('dyn-cards-list');
    if(!list) return;
    list.innerHTML = '';
    lines.forEach(function(line, i) {
        var parts = line.split('|').map(function(s) { return (s||'').trim(); });
        _dynCardRow(list, parts[0]||'', parts[1]||'', parts[2]||'', parts[3]||'', i);
    });
    if(lines.length === 0) { dynCardAdd(); }
}

function _dynCardRow(container, title, desc, img, btn, idx) {
    var row = document.createElement('div');
    row.className = 'dyn-item-row dyn-card-row';
    row.innerHTML = '<div class="dyn-card-head">\n        <div class="dyn-card-index"><i class="fas fa-images"></i><span>Card ' + (idx+1) + '</span></div>\n        <button type="button" class="dyn-item-del" onclick="this.closest(\'.dyn-item-row\').remove();dynCardSync()" title="Remover"><i class="fas fa-times"></i></button>\n    </div>\n    <div class="dyn-card-grid">\n        <input class="form-control dyn-card-title" value="' + ctx().escHtml(title) + '" placeholder="Título">\n        <input class="form-control dyn-card-desc" value="' + ctx().escHtml(desc) + '" placeholder="Descrição">\n    </div>\n    <input type="hidden" class="dyn-card-img" value="' + ctx().escHtml(img) + '">\n    <div class="dyn-media-preview" style="' + (img ? '' : 'display:none;') + '">\n        <img src="' + ctx().escHtml(img) + '" alt="Prévia">\n    </div>\n    <div class="dyn-card-media-row">\n        <button type="button" class="btn-add-dynamic dyn-media-upload-btn" onclick="this.nextElementSibling.click()"><i class="fas fa-cloud-upload-alt"></i> Enviar imagem</button>\n        <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="uploadDynamicMedia(this,\'.dyn-card-img\',\'card\')">\n        <span class="dyn-media-status">' + (img ? 'Imagem carregada' : 'Sem imagem') + '</span>\n    </div>\n    <input class="form-control dyn-card-btn" value="' + ctx().escHtml(btn) + '" placeholder="Texto do botão">';
    row.querySelectorAll('input').forEach(function(inp) { inp.addEventListener('input', function() { dynCardSync(); }); });
    container.appendChild(row);
}

window.dynCardAdd = function() {
    var list = document.getElementById('dyn-cards-list');
    if(!list) return;
    _dynCardRow(list, '', '', '', '', list.querySelectorAll('.dyn-item-row').length);
    list.lastElementChild.querySelector('.dyn-card-title').focus();
    dynCardSync();
};

function dynCardSync() {
    if(!_dynNodeId) return;
    var rows = document.querySelectorAll('#dyn-cards-list .dyn-item-row');
    var lines = Array.from(rows).map(function(r) {
        var t = r.querySelector('.dyn-card-title').value.trim();
        var d = r.querySelector('.dyn-card-desc').value.trim();
        var img = r.querySelector('.dyn-card-img').value.trim();
        var btn = r.querySelector('.dyn-card-btn').value.trim();
        return t ? [t, d, img, btn].join('|') : '';
    }).filter(Boolean);
    var str = lines.join('\n');
    var el = document.getElementById('conf-cards_data');
    if(el) el.value = str;
    ctx().BB.nodes[_dynNodeId].config.cards_data = str;
    ctx().updateNodePreview(_dynNodeId);
    var refFn = M.refreshWhatsAppPreview || (ctx().refreshWhatsAppPreview ? function(id2) { ctx().refreshWhatsAppPreview(id2); } : null);
    if(refFn) refFn(_dynNodeId);
    ctx().markDirty();
    ctx().triggerAutoSave();
    document.querySelectorAll('#dyn-cards-list .dyn-item-row').forEach(function(r, i) {
        var label = r.querySelector('.dyn-card-index span');
        if(label) label.textContent = 'Card '+(i+1);
    });
}

// ===================== RATING Preview =====================

function dynRatingPreview(nodeId) {
    _dynNodeId = nodeId;
    var wrap = document.getElementById('rating-preview-wrap');
    if(!wrap) return;
    var d = ctx().BB.nodes[nodeId].config;
    var max = parseInt(d.max_stars) || 5;
    var style = d.style || 'stars';
    wrap.innerHTML = '';
    for(var i = 1; i <= max; i++) {
        (function(idx) {
            var span = document.createElement('span');
            span.style.cssText = 'cursor:pointer;font-size:22px;opacity:0.4;transition:opacity 0.15s;';
            span.textContent = style === 'numbers' ? idx : (style === 'emojis' ? '😃' : (style === 'thumbs' ? '👍' : '★'));
            span.onmouseenter = function() {
                wrap.querySelectorAll('span').forEach(function(s, j) { s.style.opacity = j <= (idx-1) ? '1' : '0.4'; });
            };
            span.onmouseleave = function() {
                wrap.querySelectorAll('span').forEach(function(s) { s.style.opacity = '0.4'; });
            };
            wrap.appendChild(span);
        })(i);
    }
    var maxEl = document.getElementById('conf-max_stars');
    var styleEl = document.getElementById('conf-style');
    if(maxEl) {
        var handler = function() { setTimeout(function() { dynRatingPreview(nodeId); }, 50); };
        maxEl.removeEventListener('input', handler);
        maxEl.addEventListener('input', handler);
    }
    if(styleEl) {
        var handler2 = function() { setTimeout(function() { dynRatingPreview(nodeId); }, 50); };
        styleEl.removeEventListener('change', handler2);
        styleEl.addEventListener('change', handler2);
    }
}

// ===================== FILE TYPE Toggle Sync =====================

function dynFileTypeInit(nodeId) {
    _dynNodeId = nodeId;
    document.querySelectorAll('.file-type-chk').forEach(function(chk) {
        chk.addEventListener('change', function() {
            var checked = Array.from(document.querySelectorAll('.file-type-chk:checked')).map(function(c) { return c.dataset.ft; });
            var str = checked.join(',');
            var el = document.getElementById('conf-allowed_types');
            if(el) el.value = str;
            ctx().BB.nodes[nodeId].config.allowed_types = str;
            ctx().updateNodePreview(nodeId);
            ctx().markDirty();
            ctx().triggerAutoSave();
        });
    });
}

// ===================== BUTTON HANDLES (visual handles on canvas) =====================

var _btnColors = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316'];

function _getBtnOptionEntries(type, config) {
    var nativeType = M.nativeTypeForNode(type);
    if(nativeType && (config.button_mode === 'native' || config.template_mode === 'native') && config.native_template) {
        return M.nativeOptionEntries(config.native_template, nativeType).map(function(e, i) { return {
            id: e.id || e.label || 'opcao_' + (i + 1),
            label: e.label || e.id || 'Opção ' + (i + 1),
            prefix: nativeType === 5 ? 'Card' : (nativeType === 1 ? 'Menu' : 'Botão')
        }; }).filter(function(e) { return e.id || e.label; });
    }
    if(type === 'buttons') {
        return (config.options || '').split(',').map(function(s) { return { id: s.trim(), label: s.trim(), prefix: 'Botão' }; }).filter(function(e) { return e.id; });
    } else if(type === 'pic_choice') {
        return (config.choices || '').split(',').map(function(s) {
            var label = s.trim().split('|')[0];
            return { id: label, label: label, prefix: 'Imagem' };
        }).filter(function(e) { return e.id; });
    } else if(type === 'cards') {
        return (config.cards_data || '').split('\n').map(function(c, i) {
            var parts = c.split('|').map(function(s) { return (s || '').trim(); });
            var label = parts[3] || parts[0] || 'Card ' + (i + 1);
            return { id: label, label: label, prefix: 'Card ' + (i + 1) };
        }).filter(function(e) { return e.id; });
    } else if(type === 'list') {
        var opts = [];
        (config.sections || '').split('\n').forEach(function(line) {
            var parts = line.split('|');
            var section = (parts[0] || 'Menu').trim();
            if(parts[1]) parts[1].split(',').forEach(function(o) {
                var label2 = o.trim();
                if(label2) opts.push({ id: label2, label: label2, prefix: section });
            });
        });
        return opts.length ? opts : [{ id: 'default', label: 'Padrão', prefix: 'Menu' }];
    }
    return [];
}

function _getBtnOptions(type, config) {
    return _getBtnOptionEntries(type, config).map(function(e) { return e.id; }).filter(Boolean);
}

function _formatHandleLabel(entry) {
    var prefix = entry.prefix ? entry.prefix + ': ' : '';
    var full = prefix + (entry.label || entry.id || '');
    return {
        full: full,
        short: full.length > 22 ? full.substring(0, 22) + '..' : full
    };
}

function _buildBtnHandlesHTML(type, config) {
    var entries = _getBtnOptionEntries(type, config);
    if(entries.length === 0) {
        return '<div class="handle out" data-handle="default" style="top:50%;"></div>';
    }
    var html = '';
    var spacing = Math.max(30, 80 / entries.length);
    entries.forEach(function(entry, i) {
        var topPx = 20 + i * spacing;
        var color = _btnColors[i % _btnColors.length];
        var handleId = entry.id || entry.label;
        var label = _formatHandleLabel(entry);
        html += '<div class="handle out" data-handle="' + ctx().escHtml(handleId) + '" style="top:' + topPx + 'px;background:' + color + ';border-color:' + color + ';" title="' + ctx().escHtml(label.full) + '"></div>';
        html += '<div class="handle-label" style="top:' + topPx + 'px;right:18px;color:' + color + ';font-size:9px;max-width:135px;text-align:right;" title="' + ctx().escHtml(label.full) + '">' + ctx().escHtml(label.short) + '</div>';
    });
    return html;
}

function rebuildButtonHandles(nodeId) {
    var c = ctx();
    var n = c.BB.nodes[nodeId];
    if(!n) return;
    var btnLikeTypes = ['buttons','list','pic_choice','cards'];
    if(btnLikeTypes.indexOf(n.type) === -1) return;

    var el = document.querySelector('[data-id="' + nodeId + '"]');
    if(!el) return;

    var container = el.querySelector('.handles-container');
    if(!container) return;

    var inHandle = container.querySelector('.handle.in');
    var inHTML = inHandle ? inHandle.outerHTML : '';

    var outHTML = _buildBtnHandlesHTML(n.type, n.config);

    container.innerHTML = inHTML + outHTML;

    if(typeof c.enableConnections === 'function') c.enableConnections(el);

    var options = _getBtnOptions(n.type, n.config);
    if(options.length > 2) {
        el.style.minHeight = (40 + options.length * 30) + 'px';
    } else {
        el.style.minHeight = '';
    }

    c.drawConnections();
}

M.dynBtnInit = dynBtnInit;
M.dynBtnSync = dynBtnSync;
M._dynBtnRow = _dynBtnRow;
M.dynPicInit = dynPicInit;
M.dynPicSync = dynPicSync;
M._dynPicRow = _dynPicRow;
M.dynCardInit = dynCardInit;
M.dynCardSync = dynCardSync;
M._dynCardRow = _dynCardRow;
M.dynRatingPreview = dynRatingPreview;
M.dynFileTypeInit = dynFileTypeInit;
M.rebuildButtonHandles = rebuildButtonHandles;
M._buildBtnHandlesHTML = _buildBtnHandlesHTML;

})();
