(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
window.BotBuilderModules.inspector = window.BotBuilderModules.inspector || {};
var M = window.BotBuilderModules.inspector;

function ctx() { return M._ctx || {}; }

function collectVariables(currentId) {
    var vars = [];
    var seen = {};
    var sysVars = ['phone','last_message','timestamp'];
    sysVars.forEach(function(v) {
        vars.push({name: v, source: 'System', icon: 'fas fa-cog'});
        seen[v] = true;
    });
    var nodeDefs = ctx().NODE_DEFS || window.BotBuilderNodeDefs || {};
    Object.keys(ctx().BB.nodes).forEach(function(nid) {
        var n = ctx().BB.nodes[nid];
        if(n.id === currentId) return;
        var c = n.config || {};
        var def = nodeDefs[n.type] || {};
        if(c.variable && !seen[c.variable]) {
            seen[c.variable] = true;
            vars.push({
                name: c.variable,
                source: def.label || n.type,
                icon: def.icon ? def.icon.split(' ')[1] || 'fa-cube' : 'fa-cube'
            });
        }
    });
    return vars;
}

function varFieldWithPicker(elId, label, value, nodeId) {
    var vars = collectVariables(nodeId);
    var dropdownItems = '';
    if(vars.length > 0) {
        dropdownItems += '<div class="var-dropdown-header"><i class="fas fa-layer-group"></i> Variáveis disponíveis</div>';
        vars.forEach(function(v) {
            dropdownItems += '<div class="var-dropdown-item" data-var="' + v.name + '" onclick="selectVariable(\'' + elId + '\',\'' + v.name + '\',this)">\n                <span class="var-icon"><i class="fas ' + (v.icon.replace('fad ','').replace('fas ','')) + '"></i></span>\n                <span class="var-name">' + v.name + '</span>\n                <span class="var-source">' + v.source + '</span>\n            </div>';
        });
    } else {
        dropdownItems = '<div class="var-dropdown-empty"><i class="fas fa-inbox"></i><br>Nenhuma variável criada ainda.<br><small>Adicione blocos de entrada para criar variáveis.</small></div>';
    }
    dropdownItems += '<div class="var-dropdown-new" onclick="createNewVariable(\'' + elId + '\')"><i class="fas fa-plus"></i> Criar nova variável</div>';
    return '<div class="form-group var-picker-wrapper">\n        <label>' + label + '</label>\n        <div class="var-picker-input-row">\n            <input class="form-control" id="' + elId + '" value="' + ctx().escHtml(String(value || '')) + '" placeholder="Selecione uma variável" style="font-family:\'SFMono-Regular\',\'Consolas\',monospace;font-size:12px;">\n            <button class="var-picker-btn" onclick="toggleVarDropdown(\'' + elId + '\')" title="Ver variáveis"><i class="fas fa-cog"></i></button>\n        </div>\n        <div class="var-dropdown" id="' + elId + '-dropdown">' + dropdownItems + '</div>\n    </div>';
}

window.toggleVarDropdown = function(elId) {
    var dd = document.getElementById(elId + '-dropdown');
    if(!dd) return;
    document.querySelectorAll('.var-dropdown.show').forEach(function(d) { if(d.id !== elId + '-dropdown') d.classList.remove('show'); });
    dd.classList.toggle('show');
};

window.selectVariable = function(elId, varName, itemEl) {
    var input = document.getElementById(elId);
    if(input) {
        input.value = varName;
        input.dispatchEvent(new Event('input', {bubbles: true}));
    }
    var dd = document.getElementById(elId + '-dropdown');
    if(dd) dd.classList.remove('show');
};

window.createNewVariable = function(elId) {
    var name = prompt('Enter a new variable name:', 'my_variable');
    if(name && name.trim()) {
        var input = document.getElementById(elId);
        if(input) {
            input.value = name.trim().replace(/[^a-zA-Z0-9_]/g, '_');
            input.dispatchEvent(new Event('input', {bubbles: true}));
        }
    }
    var dd = document.getElementById(elId + '-dropdown');
    if(dd) dd.classList.remove('show');
};

window.insertVarIntoField = function(fieldId) {
    var el = document.getElementById(fieldId);
    if(!el) return;
    var currentNodeId = ctx().BB.selectedNode;
    var vars = collectVariables(currentNodeId);
    if(vars.length === 0) {
        var coreIns = M.insertTextAtCursor || function(el2, text2) {
            if(el2.selectionStart !== undefined) {
                var s = el2.selectionStart, e = el2.selectionEnd;
                el2.value = el2.value.substring(0, s) + text2 + el2.value.substring(e);
                el2.selectionStart = el2.selectionEnd = s + text2.length;
            } else { el2.value += text2; }
            el2.dispatchEvent(new Event('input', {bubbles: true}));
            el2.focus();
        };
        coreIns(el, '{{variable}}');
        return;
    }
    var varNames = vars.map(function(v) { return v.name; });
    var choice = prompt('Insert variable:\n\n' + varNames.map(function(v,i) { return (i+1) + '. ' + v; }).join('\n') + '\n\nEnter variable name or number:', varNames[0]);
    if(choice) {
        var varName2 = choice.trim();
        var idx = parseInt(varName2);
        if(!isNaN(idx) && idx >= 1 && idx <= varNames.length) {
            varName2 = varNames[idx - 1];
        }
        var insFn = M.insertTextAtCursor || function(el3, text3) {
            if(el3.selectionStart !== undefined) {
                var s = el3.selectionStart, e = el3.selectionEnd;
                el3.value = el3.value.substring(0, s) + text3 + el3.value.substring(e);
                el3.selectionStart = el3.selectionEnd = s + text3.length;
            } else { el3.value += text3; }
            el3.dispatchEvent(new Event('input', {bubbles: true}));
            el3.focus();
        };
        insFn(el, '{{' + varName2 + '}}');
    }
};

document.addEventListener('click', function(e) {
    if(!e.target.closest('.var-picker-wrapper')) {
        document.querySelectorAll('.var-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
    }
});

function initMediaUpload(nodeId, type) {
    var btn = document.querySelector('.bb-media-upload-btn');
    var input = document.querySelector('.bb-media-file');
    var hidden = document.getElementById('conf-url');
    var current = document.querySelector('.bb-media-current');

    if(!btn || !input || !hidden) return;

    btn.addEventListener('click', function() { input.click(); });
    input.addEventListener('change', async function() {
        if(!input.files || !input.files[0]) return;

        var formData = new FormData();
        formData.append('media', input.files[0]);
        formData.append('type', type);
        formData.append('bot_id', window.bsConfig.bot_id);
        if(window.bsConfig && window.bsConfig.csrf_name && window.bsConfig.csrf_hash) {
            formData.append(window.bsConfig.csrf_name, window.bsConfig.csrf_hash);
        }

        btn.disabled = true;
        var originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        try {
            var response = await fetch(window.bsConfig.upload_media_url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            var text = await response.text();
            var result;
            try { result = JSON.parse(text); } catch(e) {
                throw new Error(text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 300) || 'Resposta inválida do servidor no upload');
            }
            if(!response.ok || result.status !== 'success') throw new Error(result.message || 'Falha ao enviar mídia');
            if(window.bsConfig && result.csrf_hash) window.bsConfig.csrf_hash = result.csrf_hash;

            hidden.value = result.url;
            var node = ctx().BB.nodes[nodeId];
            if(node) {
                node.config = node.config || {};
                node.config.url = result.url;
                node.config.media_name = result.name || '';
            }
            if(current) {
                var thumb = type === 'image' ? '<img src="' + ctx().escHtml(result.url) + '" alt="Prévia">' : '<i class="fas ' + (type === 'video' ? 'fa-video' : (type === 'audio' ? 'fa-microphone' : 'fa-file-alt')) + '"></i>';
                current.classList.remove('muted');
                current.innerHTML = '<div class="bb-media-thumb ' + type + '">' + thumb + '</div><div><strong>Mídia carregada</strong><a href="' + ctx().escHtml(result.url) + '" target="_blank" rel="noopener">Abrir arquivo</a></div>';
            }
            ctx().updateNodePreview(nodeId);
            ctx().drawConnections();
            ctx().saveSnapshot();
            ctx().triggerAutoSave();
        } catch(error) {
            alert(error.message || 'Não foi possível enviar a mídia');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
            input.value = '';
        }
    });
}

window.uploadDynamicMedia = async function(input, selector, syncName) {
    if(!input.files || !input.files[0]) return;
    var row = input.closest('.dyn-item-row');
    var target = row ? row.querySelector(selector) : null;
    var btn = row ? row.querySelector('.dyn-media-upload-btn') : null;
    if(!target) return;

    var formData = new FormData();
    formData.append('media', input.files[0]);
    formData.append('type', 'image');
    formData.append('bot_id', window.bsConfig.bot_id);
    if(window.bsConfig && window.bsConfig.csrf_name && window.bsConfig.csrf_hash) {
        formData.append(window.bsConfig.csrf_name, window.bsConfig.csrf_hash);
    }

    var originalText = btn ? btn.innerHTML : '';
    if(btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...'; }

    try {
        var response = await fetch(window.bsConfig.upload_media_url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        var text = await response.text();
        var result;
        try { result = JSON.parse(text); } catch(e) {
            throw new Error(text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 300) || 'Resposta inválida do servidor no upload');
        }
        if(!response.ok || result.status !== 'success') throw new Error(result.message || 'Falha ao enviar mídia');
        if(window.bsConfig && result.csrf_hash) window.bsConfig.csrf_hash = result.csrf_hash;
        target.value = result.url;
        var status = row ? row.querySelector('.dyn-media-status') : null;
        if(status) status.textContent = 'Imagem carregada';
        var preview = row ? row.querySelector('.dyn-media-preview') : null;
        var previewImg = preview ? preview.querySelector('img') : null;
        if(preview && previewImg) {
            previewImg.src = result.url;
            preview.style.display = '';
        }
        if(syncName === 'pic' && typeof M.dynPicSync === 'function') M.dynPicSync();
        if(syncName === 'card' && typeof M.dynCardSync === 'function') M.dynCardSync();
    } catch (error) {
        alert(error.message || 'Não foi possível enviar a mídia');
    } finally {
        if(btn) { btn.disabled = false; btn.innerHTML = originalText; }
        input.value = '';
    }
};

M.collectVariables = collectVariables;
M.varFieldWithPicker = varFieldWithPicker;
M.initMediaUpload = initMediaUpload;

})();
