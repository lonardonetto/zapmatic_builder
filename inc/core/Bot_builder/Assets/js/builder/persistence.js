(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
var M = window.BotBuilderModules.persistence = {};
var H = null;

M.init = function(ctx) { H = ctx; };
function c() { return H || {}; }

function markDirty() { c().BB.dirty = true; }

function triggerAutoSave() {
    var ctx = c();
    clearTimeout(ctx.BB.autoSaveTimer);
    var st = ctx.$('#save-status');
    if(st) { st.innerHTML = '<i class="fas fa-circle"></i> Alterações pendentes'; st.className = 'saving'; }
    ctx.BB.autoSaveTimer = setTimeout(function() { window.saveFlow(false); }, 2000);
}

window.saveFlow = function(publish) {
    var ctx = c();
    var BB = ctx.BB;
    var blocks = Object.values(BB.nodes).map(function(n) { return {
        id: n.id, type: n.type, pos_x: Math.round(n.x), pos_y: Math.round(n.y), data: n.config
    }; });
    var edges = BB.edges.map(function(e) {
        var fromNode = BB.nodes[e.from];
        var isCondition = e.handle === 'true' || e.handle === 'false';
        var isABTest = e.handle === 'variant_a' || e.handle === 'variant_b';
        var isDefault = !e.handle || e.handle === 'default';
        var btnLikeTypes = ['buttons','list','pic_choice','cards'];
        var isButtonHandle = fromNode && btnLikeTypes.includes(fromNode.type) && !isDefault;
        var condType = null;
        var condVal = e.handle;
        if(isCondition) condType = 'condition';
        else if(isABTest) condType = 'ab_test';
        else if(isButtonHandle) condType = 'button';
        else if(isDefault) condVal = 'default';
        return {
            id: 'edge_' + e.from.substring(0,8) + '_' + e.to.substring(0,8),
            source: e.from, target: e.to,
            data: { condition_type: condType, condition_value: condVal }
        };
    });

    var fd = new FormData();
    fd.append('bot_id', window.bsConfig.bot_id);
    fd.append('blocks', JSON.stringify(blocks));
    fd.append('edges', JSON.stringify(edges));
    if(publish) fd.append('publish', '1');
    fd.append(window.bsConfig.csrf_name, window.bsConfig.csrf_hash);

    var st = ctx.$('#save-status');
    if(st) { st.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Salvando...'; st.className = 'saving'; }

    fetch(window.bsConfig.save_url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r) { return r.text(); })
    .then(function(text) {
        var data;
        try { data = JSON.parse(text); } catch(e) {
            throw new Error(text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 300) || 'Resposta inválida do servidor');
        }
        if(data.status === 'error') throw new Error(data.message || 'Erro ao salvar fluxo');
        BB.dirty = false;
        if(window.bsConfig && data.csrf_hash) window.bsConfig.csrf_hash = data.csrf_hash;
        if(st) { st.innerHTML = '<i class="fas fa-check-circle"></i> Salvo'; st.className = 'saved'; }
        if(publish) window.showToast('Bot publicado!', 'success');
    })
    .catch(function(err) {
        console.error('Erro ao salvar:', err);
        if(st) { st.innerHTML = '<i class="fas fa-exclamation-circle"></i> Erro'; st.className = 'error'; }
        alert(err.message || 'Não foi possível salvar o fluxo');
    });
};

window.exportFlow = function() { window.location.href = window.bsConfig.export_url; };

window.filterBlocks = function() {
    var input = document.getElementById('block-search');
    if(!input) return;
    var q = input.value.toLowerCase();
    document.querySelectorAll('.block-item').forEach(function(item) {
        item.style.display = item.textContent.toLowerCase().includes(q) ? 'flex' : 'none';
    });
    document.querySelectorAll('.block-category').forEach(function(cat) {
        var next = cat.nextElementSibling, visible = false;
        while(next && !next.classList.contains('block-category')) {
            if(next.style.display !== 'none') visible = true;
            next = next.nextElementSibling;
        }
        cat.style.display = visible ? 'block' : 'none';
    });
};

M.markDirty = markDirty;
M.triggerAutoSave = triggerAutoSave;
M.saveFlow = window.saveFlow;

})();
