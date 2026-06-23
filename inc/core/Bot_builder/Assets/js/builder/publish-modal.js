(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};

let ctx = null;
let pmLinkedIds = new Set();
let pmPublished = false;

function init(options) {
    ctx = options || {};
    pmLinkedIds = new Set((window.linkedInstanceIds || []).map(Number));
    pmPublished = false;

    window.openPublishModal = openPublishModal;
    window.closePublishModal = closePublishModal;
    window.publishBot = publishBot;
    window.toggleInstance = toggleInstance;
    window.publishAndConnect = publishAndConnect;
    window.saveBotSettings = saveBotSettings;

    document.addEventListener('click', e => {
        if(e.target && e.target.id === 'publish-modal') closePublishModal();
    });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

function getFlowPayload() {
    const BB = ctx.BB;
    const blocks = Object.values(BB.nodes).map(n => ({
        id: n.id,
        type: n.type,
        pos_x: Math.round(n.x),
        pos_y: Math.round(n.y),
        data: n.config
    }));
    const edges = BB.edges.map(e => ({
        id: 'edge_' + e.from.substring(0, 8) + '_' + e.to.substring(0, 8),
        source: e.from,
        target: e.to,
        data: {
            condition_type: e.handle === 'true' || e.handle === 'false' ? 'condition' : null,
            condition_value: e.handle
        }
    }));
    return { blocks, edges };
}

function appendBotSettings(fd, includeMatchAndChat) {
    const enableKw = document.getElementById('pm-enable-keyword');
    const stopKw = document.getElementById('pm-stop-keyword');
    const botEnabled = document.getElementById('pm-bot-enabled');

    if(enableKw) {
        fd.append('enable_keyword', enableKw.value);
        fd.append('trigger_keywords', enableKw.value);
    }
    if(stopKw) fd.append('stop_keyword', stopKw.value);
    if(botEnabled) fd.append('bot_enabled', botEnabled.checked ? '1' : '0');

    if(includeMatchAndChat) {
        const matchType = document.getElementById('pm-keyword-match-type');
        if(matchType) fd.append('keyword_match_type', matchType.value);
        const chatTypeRadio = document.querySelector('input[name="pm-chat-type"]:checked');
        if(chatTypeRadio) fd.append('chat_type', chatTypeRadio.value);
    }
}

function openPublishModal() {
    const modal = document.getElementById('publish-modal');
    if(!modal) return;
    modal.style.display = 'flex';
    pmPublished = false;
    updateStatus();
    renderInstances();
}

function closePublishModal() {
    const modal = document.getElementById('publish-modal');
    if(modal) modal.style.display = 'none';
}

function updateStatus() {
    const saveEl = document.getElementById('pm-save-status');
    const connEl = document.getElementById('pm-connect-status');

    if(saveEl) {
        const icon = saveEl.querySelector('i');
        if(pmPublished) {
            icon.className = 'fas fa-circle-check';
            icon.style.color = '#10b981';
            saveEl.classList.add('done');
        } else {
            icon.className = 'fas fa-circle';
            icon.style.color = '#94a3b8';
            saveEl.classList.remove('done');
        }
    }

    if(connEl) {
        const icon = connEl.querySelector('i');
        if(pmLinkedIds.size > 0) {
            icon.className = 'fas fa-circle-check';
            icon.style.color = '#10b981';
            connEl.classList.add('done');
        } else {
            icon.className = 'fas fa-circle';
            icon.style.color = '#94a3b8';
            connEl.classList.remove('done');
        }
    }

    const summary = document.getElementById('pm-linked-summary');
    const summaryText = document.getElementById('pm-linked-summary-text');
    if(summary && summaryText) {
        if(pmLinkedIds.size > 0 && pmPublished) {
            summary.style.display = 'block';
            summaryText.textContent = `Este bot está vinculado a ${pmLinkedIds.size} número${pmLinkedIds.size > 1 ? 's' : ''} WhatsApp e está respondendo mensagens.`;
        } else {
            summary.style.display = 'none';
        }
    }

    const countEl = document.getElementById('pm-instance-count');
    if(countEl) {
        countEl.textContent = pmLinkedIds.size + (pmLinkedIds.size === 1 ? ' conectada' : ' conectadas');
        countEl.style.background = pmLinkedIds.size > 0 ? '#dcfce7' : '#f1f5f9';
        countEl.style.color = pmLinkedIds.size > 0 ? '#15803d' : '#64748b';
    }
}

function renderInstances() {
    const listEl = document.getElementById('pm-instances-list');
    const emptyEl = document.getElementById('pm-no-instances');
    const instances = window.waInstances || [];

    if(!instances.length) {
        listEl.style.display = 'none';
        emptyEl.style.display = 'block';
        return;
    }

    emptyEl.style.display = 'none';
    listEl.style.display = 'flex';

    let html = '';
    instances.forEach(inst => {
        const id = parseInt(inst.id);
        const isLinked = pmLinkedIds.has(id);
        const name = inst.name || inst.pid || 'Conexão WhatsApp';
        const detail = inst.pid || inst.ids || 'ID: ' + inst.id;
        const avatar = inst.avatar ? `<img src="${inst.avatar}" alt="${name}">` : '<i class="fab fa-whatsapp"></i>';

        html += `
        <div class="pm-instance-card ${isLinked ? 'linked' : ''}" id="pm-inst-${id}">
            <div class="pm-instance-avatar">${avatar}</div>
            <div class="pm-instance-info">
                <div class="pm-instance-name">${escapeHtml(name)}</div>
                <div class="pm-instance-detail">${escapeHtml(detail)}</div>
            </div>
            <div class="pm-instance-actions">
                ${isLinked
                    ? `<span class="pm-instance-status connected"><i class="fas fa-check"></i> Vinculado</span>
                       <button class="pm-btn pm-btn-danger" style="padding:6px 10px;font-size:11px;" onclick="toggleInstance(${id}, false)">
                           <i class="fas fa-unlink"></i> Desvincular
                       </button>`
                    : `<button class="pm-btn pm-btn-success" style="padding:6px 12px;font-size:11px;" onclick="toggleInstance(${id}, true)">
                           <i class="fas fa-link"></i> Vincular
                       </button>`
                }
            </div>
        </div>`;
    });

    listEl.innerHTML = html;
    updateStatus();
}

function buildPublishFormData() {
    const payload = getFlowPayload();
    const fd = new FormData();
    fd.append('bot_id', window.bsConfig.bot_id);
    fd.append('blocks', JSON.stringify(payload.blocks));
    fd.append('edges', JSON.stringify(payload.edges));
    fd.append('publish', '1');
    fd.append(window.bsConfig.csrf_name, window.bsConfig.csrf_hash);
    appendBotSettings(fd, true);
    return fd;
}

function publishBot() {
    const btn = document.getElementById('pm-publish-btn');
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Publicando...';
    }

    fetch(window.bsConfig.save_url, { method: 'POST', body: buildPublishFormData(), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(() => {
        pmPublished = true;
        ctx.BB.dirty = false;
        const st = document.getElementById('save-status');
        if(st) { st.innerHTML = '<i class="fas fa-check-circle"></i> Publicado'; st.className = 'saved'; }
        if(btn) {
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Publicado!';
            btn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-rocket"></i> Publicar agora';
                btn.style.background = '';
            }, 2000);
        }
        updateStatus();
        ctx.showToast('Bot publicado com sucesso!', 'success');
    })
    .catch(err => {
        console.error('Erro ao publicar:', err);
        if(btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Erro';
            setTimeout(() => { btn.innerHTML = '<i class="fas fa-rocket"></i> Publicar agora'; }, 2000);
        }
        ctx.showToast('Falha ao publicar o bot', 'error');
    });
}

function toggleInstance(instanceId, link) {
    const card = document.getElementById('pm-inst-' + instanceId);
    if(card) {
        const actionsEl = card.querySelector('.pm-instance-actions');
        if(actionsEl) actionsEl.innerHTML = '<i class="fas fa-circle-notch fa-spin" style="color:#94a3b8;"></i>';
    }

    const url = link ? window.bsConfig.link_url : window.bsConfig.unlink_url;
    const fd = new FormData();
    fd.append('bot_id', window.bsConfig.bot_id);
    fd.append('instance_id', instanceId);
    fd.append(window.bsConfig.csrf_name, window.bsConfig.csrf_hash);

    fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            if(link) {
                pmLinkedIds.add(instanceId);
                pmPublished = true;
                ctx.showToast('Bot vinculado à conexão WhatsApp!', 'success');
            } else {
                pmLinkedIds.delete(instanceId);
                ctx.showToast('Bot desvinculado da conexão', 'success');
            }
            renderInstances();
        } else {
            ctx.showToast(d.message || 'Falha na operação', 'error');
            renderInstances();
        }
    })
    .catch(err => {
        console.error('Erro ao alterar conexão:', err);
        ctx.showToast('Erro de conexão', 'error');
        renderInstances();
    });
}

function publishAndConnect() {
    if(!pmPublished) {
        fetch(window.bsConfig.save_url, { method: 'POST', body: buildPublishFormData(), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(() => {
            pmPublished = true;
            ctx.BB.dirty = false;
            const st = document.getElementById('save-status');
            if(st) { st.innerHTML = '<i class="fas fa-check-circle"></i> Publicado'; st.className = 'saved'; }
            updateStatus();
            ctx.showToast('Bot salvo e publicado!', 'success');
            closePublishModal();
        })
        .catch(() => { ctx.showToast('Falha ao salvar', 'error'); });
    } else {
        ctx.showToast('Todas as alterações foram salvas!', 'success');
        closePublishModal();
    }
}

function saveBotSettings() {
    const btn = document.getElementById('pm-save-settings-btn');
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Salvando...';
    }

    const fd = new FormData();
    fd.append('bot_id', window.bsConfig.bot_id);
    fd.append(window.bsConfig.csrf_name, window.bsConfig.csrf_hash);
    appendBotSettings(fd, true);

    fetch(window.bsConfig.settings_url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            ctx.showToast('Configurações do bot salvas!', 'success');
            if(btn) {
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Salvo!';
                btn.style.borderColor = '#10b981';
                btn.style.color = '#10b981';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Salvar configurações';
                    btn.style.borderColor = '';
                    btn.style.color = '';
                }, 2000);
            }
        } else {
            ctx.showToast(d.message || 'Falha ao salvar configurações', 'error');
            if(btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Salvar configurações';
            }
        }
    })
    .catch(err => {
        console.error('Erro ao salvar configurações:', err);
        ctx.showToast('Falha ao salvar configurações', 'error');
        if(btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Salvar configurações';
        }
    });
}

window.BotBuilderModules.publishModal = {
    init,
    openPublishModal,
    closePublishModal,
    updateStatus,
    renderInstances,
    publishBot,
    toggleInstance,
    publishAndConnect,
    saveBotSettings
};
})();
