/**
 * Bot Builder v3 — editor visual de fluxos
 * Arrastar, conectar, desfazer/refazer, atalhos, autosave e simulador.
 */

(function() {
'use strict';

// ===================== STATE =====================
window.BotBuilder = {
    nodes: {},
    edges: [],
    selectedNode: null,
    zoom: 1,
    panX: 0,
    panY: 0,
    isDraggingNode: false,
    undoStack: [],
    redoStack: [],
    dirty: false,
    autoSaveTimer: null,
    snapGrid: 20
};

const BB = window.BotBuilder;
const $ = (s, p) => BotBuilderModules.utils.$(s, p);
const $$ = (s, p) => BotBuilderModules.utils.$$(s, p);

const canvasContainer = $('#bot-canvas-container');
const canvas = $('#bot-canvas');
const svg = $('#connections');
const inspectorPanel = $('#inspector');
const inspectorForm = $('#inspector-form');
const viewportStorageKey = 'bot_builder_viewport_' + ((window.bsConfig && window.bsConfig.bot_id) || 'default');
let viewportSaveTimer = null;

function loadCanvasViewport() {
    var m = window.BotBuilderModules && window.BotBuilderModules.canvasCore;
    return m ? m.loadCanvasViewport() : false;
}
function saveCanvasViewport() {
    var m = window.BotBuilderModules && window.BotBuilderModules.canvasCore;
    if(m) m.saveCanvasViewport();
}

// ===================== NODE CONFIGS =====================
const NODE_DEFS = window.BotBuilderNodeDefs || {};
const builderInspectorModule = window.BotBuilderModules && window.BotBuilderModules.inspector;
if(builderInspectorModule) {
    builderInspectorModule.init({
        BB,
        NODE_DEFS,
        escHtml,
        updateNodePreview,
        markDirty,
        triggerAutoSave,
        openInspector,
        drawConnections,
        enableConnections,
        saveSnapshot,
        refreshWhatsAppPreview: refreshInspectorWhatsAppPreview,
        showToast,
        inspectorPanel,
        inspectorForm,
        $
    });
}

// ===================== UUID =====================
window.uuidv4 = () => BotBuilderModules.utils.uuidv4();

// ===================== INIT =====================
document.addEventListener('DOMContentLoaded', () => {
    if(window.initialNodes) {
        window.initialNodes.forEach(n => {
            let data = {};
            try { data = JSON.parse(n.data); } catch(e){}
            data.uid = n.id;
            createNode(n.id, n.type, parseInt(n.pos_x), parseInt(n.pos_y), data, true);
        });
    }
    if(window.initialEdges) {
        window.initialEdges.forEach(e => {
            let h = e.condition_value || 'default';
            if(h==='output_1') h='default';
            BB.edges.push({ from: e.from_block_id, to: e.to_block_id, handle: h });
        });
        drawConnections();
    }
    loadCanvasViewport();
    updateTransform();
    setupCanvasPan();
    setupDragDrop();
    setupKeyboard();
    setupContextMenu();
    saveSnapshot();
});

// ===================== DRAG & DROP FROM SIDEBAR =====================
function setupDragDrop() {
    $$('.block-item').forEach(b => {
        b.addEventListener('dragstart', e => {
            e.dataTransfer.setData('block-type', b.dataset.type);
            e.dataTransfer.effectAllowed = 'copy';
        });
    });
    canvasContainer.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect='copy'; });
    canvasContainer.addEventListener('drop', e => {
        e.preventDefault();
        const type = e.dataTransfer.getData('block-type');
        if(!type) return;
        const rect = canvasContainer.getBoundingClientRect();
        const x = (e.clientX - rect.left - BB.panX) / BB.zoom;
        const y = (e.clientY - rect.top - BB.panY) / BB.zoom;
        const id = uuidv4();
        createNode(id, type, snap(x), snap(y));
        saveSnapshot();
        triggerAutoSave();
    });
}

function snap(v) { return BotBuilderModules.utils.snap(v, BB.snapGrid); }

// ===================== CREATE NODE =====================
function createNode(id, type, x, y, config = {}, isInit = false) {
    const def = NODE_DEFS[type] || { icon:'fad fa-cube', label:type };
    if(Object.keys(config).length === 0) {
        config = Object.assign({ uid: id }, def.defaults || {});
    }
    config.uid = id;

    const node = document.createElement('div');
    node.className = `bot-node type-${type}`;
    node.style.left = x + 'px';
    node.style.top = y + 'px';
    node.dataset.id = id;
    node.dataset.type = type;

    // Output handles
    let outHTML = '';
    const btnLikeTypes = ['buttons','list','pic_choice','cards'];
    if(type === 'condition') {
        outHTML = `
            <div class="handle out" data-handle="true" style="top:25px;background:#10b981;border-color:#10b981;" title="Verdadeiro"></div>
            <div class="handle-label" style="top:25px;right:18px;color:#10b981;">SIM</div>
            <div class="handle out" data-handle="false" style="top:65px;background:#ef4444;border-color:#ef4444;" title="Falso"></div>
            <div class="handle-label" style="top:65px;right:18px;color:#ef4444;">NÃO</div>`;
    } else if(type === 'ab_test') {
        outHTML = `
            <div class="handle out" data-handle="variant_a" style="top:25px;background:#6366f1;border-color:#6366f1;" title="Variante A"></div>
            <div class="handle-label" style="top:25px;right:18px;color:#6366f1;">A</div>
            <div class="handle out" data-handle="variant_b" style="top:65px;background:#f59e0b;border-color:#f59e0b;" title="Variante B"></div>
            <div class="handle-label" style="top:65px;right:18px;color:#f59e0b;">B</div>`;
    } else if(btnLikeTypes.includes(type)) {
        // Generate per-button output handles
        outHTML = builderInspectorModule._buildBtnHandlesHTML(type, config);
    } else if(type !== 'end') {
        outHTML = '<div class="handle out" data-handle="default" style="top:50%;"></div>';
    }
    const noInputTypes = ['start','command','reply','invalid'];
    let inHTML = !noInputTypes.includes(type) ? '<div class="handle in" style="top:50%;"></div>' : '';

    node.innerHTML = `
        <div class="node-header">
            <div class="node-icon"><i class="${def.icon}"></i></div>
            <span class="node-type-label">${def.label}</span>
            ${config.label ? `<span class="node-custom-label">${escHtml(config.label)}</span>` : ''}
        </div>
        <div class="node-body">${renderNodeBodyHTML(type, config, def)}</div>
        <div class="handles-container">${inHTML}${outHTML}</div>`;

    canvas.appendChild(node);
    BB.nodes[id] = { id, type, x, y, config };

    enableNodeDrag(node);
    enableNodeClick(node);
    enableConnections(node);

    if(!isInit) markDirty();
}

function escHtml(s) { return BotBuilderModules.utils.escHtml(s); }

function renderInspectorWhatsAppPreview(node) {
    return builderInspectorModule ? builderInspectorModule.renderWhatsAppPreview(node) : '';
}

function refreshInspectorWhatsAppPreview(id) {
    if(builderInspectorModule) builderInspectorModule.refreshWhatsAppPreview(id);
}

function renderNodeBodyHTML(type, config, def = {}) {
    const mediaUrl = (config.url || '').trim();
    const text = config.text || config.question || config.prompt || config.options || config.variable || def.label || 'Configure...';
    const safeText = escHtml(String(text).substring(0, 55) + (String(text).length > 55 ? '...' : ''));

    if(type === 'image' && mediaUrl) {
        return `<div class="bb-node-media"><img src="${escHtml(mediaUrl)}" alt="Prévia da imagem" loading="lazy"></div>${safeText ? `<div class="bb-node-caption">${safeText}</div>` : ''}`;
    }

    if(type === 'video' && mediaUrl) {
        return `<div class="bb-node-media"><video src="${escHtml(mediaUrl)}" muted playsinline preload="metadata"></video><span class="bb-node-media-badge">Vídeo</span></div>${safeText ? `<div class="bb-node-caption">${safeText}</div>` : ''}`;
    }

    if(type === 'audio' && mediaUrl) {
        return `<div class="bb-node-audio"><i class="fad fa-volume-up"></i><span>Áudio anexado</span></div>${safeText ? `<div class="bb-node-caption">${safeText}</div>` : ''}`;
    }

    if(type === 'pic_choice') {
        const choices = (config.choices || '').split(',').map(s => s.trim()).filter(Boolean);
        const firstWithImage = choices.map(c => c.split('|').map(p => (p || '').trim())).find(p => p[1]);
        const labels = choices.map(c => c.split('|')[0].trim()).filter(Boolean).slice(0, 3);
        let html = '';
        if(firstWithImage) html += `<div class="bb-node-media"><img src="${escHtml(firstWithImage[1])}" alt="Prévia da escolha" loading="lazy"></div>`;
        html += `<div class="bb-node-caption">${labels.length ? escHtml(labels.join(', ')) : 'Escolhas com imagem'}</div>`;
        return html;
    }

    if(type === 'buttons' && config.button_mode === 'native') {
        var nt = config.native_template || {};
        var ntData = nt.data || {};
        if(typeof ntData === 'string') { try { ntData = JSON.parse(ntData); } catch(e) { ntData = {}; } }
        var imgUrl = (ntData.image && ntData.image.url) ? ntData.image.url : '';
        var btns = ntData.templateButtons || [];
        var labels = btns.map(function(b) {
            return (b.quickReplyButton && b.quickReplyButton.displayText) ||
                   (b.button && b.button.displayText) || '';
        }).filter(Boolean).slice(0, 3);
        var tplText = ntData.text || ntData.caption || '';
        let html = imgUrl ? `<div class="bb-node-media"><img src="${escHtml(imgUrl)}" alt="Prévia da imagem" loading="lazy"></div>` : '';
        html += `<div class="bb-node-caption"><strong>Botões nativos</strong>${config.template_name ? ': ' + escHtml(config.template_name) : ': selecione um template'}</div>`;
        if(labels.length) html += `<div class="bb-node-options-preview">${labels.map(function(l) { return '<span class="bb-node-option-tag">' + escHtml(l) + '</span>'; }).join('')}</div>`;
        return html;
    }

    if(type === 'buttons' && config.button_mode === 'quick') {
        const imgUrl = (config.image || '').trim();
        const labels = (config.options || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean).slice(0, 3);
        var displayLabels = labels.map(function(l) { return l.split('|')[0]; }); // Extract label from pipe-delimited format
        let html = imgUrl ? `<div class="bb-node-media"><img src="${escHtml(imgUrl)}" alt="Prévia da imagem" loading="lazy"></div>` : '';
        html += `<div class="bb-node-caption">${safeText}</div>`;
        if(displayLabels.length) html += `<div class="bb-node-options-preview">${displayLabels.map(function(l) { return '<span class="bb-node-option-tag">' + escHtml(l) + '</span>'; }).join('')}</div>`;
        return html;
    }

    if(type === 'list' && config.template_mode === 'native') {
        return `<div class="bb-node-caption"><strong>Lista nativa</strong>${config.template_name ? ': ' + escHtml(config.template_name) : ': selecione um template'}</div>`;
    }

    if(type === 'cards' && config.template_mode === 'native') {
        return `<div class="bb-node-caption"><strong>Carrossel nativo</strong>${config.template_name ? ': ' + escHtml(config.template_name) : ': selecione um template'}</div>`;
    }

    if(type === 'cards') {
        const cards = (config.cards_data || '').split('\n').map(line => line.split('|').map(p => (p || '').trim())).filter(p => p[0] || p[1] || p[2]);
        if(cards.length) {
            const firstImage = cards.find(p => p[2]);
            const titles = cards.slice(0, 3).map(p => p[0] || p[3]).filter(Boolean);
            let html = '';
            if(firstImage) html += `<div class="bb-node-media"><img src="${escHtml(firstImage[2])}" alt="Prévia do card" loading="lazy"></div>`;
            html += `<div class="bb-node-caption"><strong>${cards.length} card${cards.length > 1 ? 's' : ''}</strong>${titles.length ? ': ' + escHtml(titles.join(', ')) : ''}</div>`;
            return html;
        }
    }

    return safeText;
}

// ===================== NODE DRAG =====================
function enableNodeDrag(node) {
    let dragging = false, didMove = false, sx, sy, drawQueued = false;
    const header = node.querySelector('.node-header');

    header.addEventListener('mousedown', e => {
        if(e.button !== 0) return;
        dragging = true;
        didMove = false;
        sx = e.clientX; sy = e.clientY;
        BB.isDraggingNode = true;
        node.classList.add('dragging');
        selectNode(node.dataset.id);
        e.preventDefault();
        e.stopPropagation();
    });

    const onMove = e => {
        if(!dragging) return;
        if(Math.abs(e.clientX - sx) > 3 || Math.abs(e.clientY - sy) > 3) didMove = true;
        const dx = (e.clientX - sx) / BB.zoom;
        const dy = (e.clientY - sy) / BB.zoom;
        const n = BB.nodes[node.dataset.id];
        if(!n) return;
        n.x += dx;
        n.y += dy;
        node.style.left = n.x + 'px';
        node.style.top = n.y + 'px';
        if(!drawQueued) {
            drawQueued = true;
            requestAnimationFrame(() => {
                drawQueued = false;
                if(dragging) drawConnections();
            });
        }
        sx = e.clientX; sy = e.clientY;
    };

    const onUp = () => {
        if(!dragging) return;
        dragging = false;
        BB.isDraggingNode = false;
        node.classList.remove('dragging');
        const n = BB.nodes[node.dataset.id];
        n.x = snap(n.x); n.y = snap(n.y);
        node.style.left = n.x + 'px';
        node.style.top = n.y + 'px';
        drawConnections();
        // If user clicked header without dragging, open the inspector
        if(!didMove) {
            openInspector(node.dataset.id);
        }
        triggerAutoSave();
    };

    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
}

// ===================== NODE CLICK / INSPECTOR =====================
function enableNodeClick(node) {
    node.addEventListener('mousedown', e => {
        if(e.target.classList.contains('handle')) return;
        if(e.target.closest('.node-header')) return;
        selectNode(node.dataset.id);
        openInspector(node.dataset.id);
    });
}

function selectNode(id) {
    if(BB.selectedNode) {
        const prev = $(`[data-id="${BB.selectedNode}"]`);
        if(prev) prev.classList.remove('selected');
    }
    BB.selectedNode = id;
    const curr = $(`[data-id="${id}"]`);
    if(curr) curr.classList.add('selected');
}
















// ===================== VARIABLE SYSTEM =====================






// ===================== DYNAMIC INPUT BUILDERS =====================


function updateNodePreview(id) {
    const n = BB.nodes[id];
    if(!n) return;
    const nodeEl = $(`[data-id="${id}"]`);
    const el = nodeEl ? nodeEl.querySelector('.node-body') : null;
    if(!el) return;
    const def = NODE_DEFS[n.type] || { label: n.type };
    const header = nodeEl.querySelector('.node-header');
    if(header) {
        header.innerHTML = `<div class="node-icon"><i class="${def.icon || 'fad fa-cube'}"></i></div><span class="node-type-label">${def.label || n.type}</span>${n.config.label ? `<span class="node-custom-label">${escHtml(n.config.label)}</span>` : ''}`;
    }
    el.innerHTML = renderNodeBodyHTML(n.type, n.config, def);
    // Rebuild button handles if this is a button-like node
    const btnLikeTypes = ['buttons','list','pic_choice','cards'];
    if(btnLikeTypes.includes(n.type)) {
        rebuildButtonHandles(id);
    }
}

function openInspector(id) {
    if(builderInspectorModule && builderInspectorModule.openInspector) builderInspectorModule.openInspector(id);
}

function rebuildButtonHandles(id) {
    const m = window.BotBuilderModules && window.BotBuilderModules.inspector;
    if(m && m.rebuildButtonHandles) m.rebuildButtonHandles(id);
}

window.closeInspector = function() { inspectorPanel.classList.add('hidden'); };

// ===================== DELETE / DUPLICATE =====================
window.deleteNode = function(id) {
    if(!confirm('Excluir este bloco?')) return;
    saveSnapshot();
    const el = $(`[data-id="${id}"]`);
    if(el) el.remove();
    delete BB.nodes[id];
    BB.edges = BB.edges.filter(e => e.from !== id && e.to !== id);
    drawConnections();
    inspectorPanel.classList.add('hidden');
    if(BB.selectedNode === id) BB.selectedNode = null;
    triggerAutoSave();
};

window.duplicateNode = function(id) {
    const orig = BB.nodes[id];
    if(!orig) return;
    saveSnapshot();
    const newId = uuidv4();
    const newConfig = JSON.parse(JSON.stringify(orig.config));
    newConfig.uid = newId;
    createNode(newId, orig.type, orig.x + 40, orig.y + 40, newConfig);
    triggerAutoSave();
    showToast('Bloco duplicado', 'success');
};

// ===================== CONNECTIONS =====================
const connectionModule = window.BotBuilderModules && window.BotBuilderModules.connections;

if(connectionModule) {
    connectionModule.init({
        BB,
        canvas,
        svg,
        canvasContainer,
        $,
        $$,
        saveSnapshot,
        triggerAutoSave,
        getSim: () => sim
    });
}

// ===================== HISTORY (UNDO/REDO) =====================
const historyModule = window.BotBuilderModules && window.BotBuilderModules.history;
if(historyModule) {
    historyModule.init({
        BB,
        $, $$,
        createNode,
        drawConnections,
        triggerAutoSave,
        inspectorPanel
    });
}

window.BotBuilder = window.BotBuilder || {};
window.BotBuilder.BB = BB;

const persistenceModule = window.BotBuilderModules && window.BotBuilderModules.persistence;
if(persistenceModule) {
    persistenceModule.init({
        BB,
        $
    });
}

function enableConnections(node) {
    if(connectionModule) connectionModule.enableConnections(node);
}

function getCubicPoint(x1, y1, cx1, cy1, cx2, cy2, x2, y2, t) {
    return connectionModule.getCubicPoint(x1, y1, cx1, cy1, cx2, cy2, x2, y2, t);
}

function drawConnections() {
    if(connectionModule) connectionModule.drawConnections();
}

// ===================== PAN & ZOOM =====================
function setupCanvasPan() { if(canvasCoreModule) canvasCoreModule.setupCanvasPan(); }

function updateTransform() { if(canvasCoreModule) canvasCoreModule.updateTransform(); }

window.zoomCanvas = function(d) {
    BB.zoom = Math.max(0.2, Math.min(2, BB.zoom + d));
    updateTransform();
};

window.resetCanvas = function() {
    BB.zoom = 1; BB.panX = 0; BB.panY = 0;
    updateTransform();
};

function updateZoomDisplay() { if(canvasCoreModule) canvasCoreModule.updateZoomDisplay(); }

// ===================== UNDO / REDO =====================
function saveSnapshot() { return historyModule ? historyModule.saveSnapshot() : null; }

window.undo = function() { if(historyModule) historyModule.undo(); };

window.redo = function() { if(historyModule) historyModule.redo(); };

function restoreSnapshot(snap) { if(historyModule) historyModule.restoreSnapshot(snap); }

function updateUndoBtn() { if(historyModule) historyModule.updateUndoBtn(); }

// ===================== AUTO-SAVE =====================
function markDirty() { var p = BotBuilderModules.persistence; if(p) p.markDirty(); }
function triggerAutoSave() { var p = BotBuilderModules.persistence; if(p) p.triggerAutoSave(); }

window.saveFlow = function(publish) {
    var p = BotBuilderModules.persistence;
    if(p) p.saveFlow(publish);
};

window.exportFlow = function() { window.location.href = window.bsConfig.export_url; };

// ===================== KEYBOARD SHORTCUTS =====================
function setupKeyboard() { if(canvasCoreModule) canvasCoreModule.setupKeyboard(); }

// ===================== CONTEXT MENU =====================
function setupContextMenu() { if(canvasCoreModule) canvasCoreModule.setupContextMenu(); }

// ===================== SEARCH BLOCKS =====================
window.filterBlocks = function() {
    var p = BotBuilderModules.persistence;
    if(p) p.filterBlocks();
};

window.showToast = function(msg, type) { BotBuilderModules.utils.showToast(msg, type); };
function showToast(msg, type) { BotBuilderModules.utils.showToast(msg, type); }

// ===================== FLOW VALIDATION =====================
const validationModule = window.BotBuilderModules && window.BotBuilderModules.validation;

if(validationModule) {
    validationModule.init({
        BB,
        NODE_DEFS,
        $,
        escHtml
    });
}

window.toggleValidationPanel = function() {
    if(validationModule) validationModule.toggleValidationPanel();
};

function renderFlowValidation() {
    if(validationModule) validationModule.renderFlowValidation();
}

// ===================== SIMULATOR =====================
let sim = { currentNode: null, context: {}, waiting: false };
const simulatorModule = window.BotBuilderModules && window.BotBuilderModules.simulator;

if(simulatorModule) {
    simulatorModule.init({
        BB,
        NODE_DEFS,
        $,
        escHtml,
        replVars,
        pause,
        drawConnections,
        runSim,
        getSim: () => sim,
        setSim: nextSimState => { sim = nextSimState; }
    });
}

window.togglePreview = function() {
    const p = $('#preview-panel');
    if(p.classList.contains('hidden')) { p.classList.remove('hidden'); startSim(); }
    else { p.classList.add('hidden'); clearSimActiveNode(); }
};

function clearSimActiveNode() { if(simulatorModule) simulatorModule.clearActiveNode(); }
function setSimActiveNode(nodeId) { if(simulatorModule) simulatorModule.setActiveNode(nodeId); }
function updateSimVarsPanel() { if(simulatorModule) simulatorModule.updateVarsPanel(); }
function updateSimHistoryPanel() { if(simulatorModule) simulatorModule.updateHistoryPanel(); }
function recordSimHistory(node) { if(simulatorModule) simulatorModule.recordHistory(node); }
function markSimEdge(edge) { if(simulatorModule) simulatorModule.markEdge(edge); }

function startSim() {
    if(simulatorModule) simulatorModule.startSimulation();
}
window.startSimulation = startSim;

async function runSim(nodeId) {
    if(simulatorModule && await simulatorModule.executeSimpleBlock(nodeId)) return;

    const nx = nextSim(nodeId);
    if(nx) runSim(nx);
}
function nextSim(id, handle) {
    return simulatorModule ? simulatorModule.nextNode(id, handle) : null;
}

function resolveSimChoice(nodeId, text, options) {
    return simulatorModule ? simulatorModule.resolveChoice(nodeId, text, options) : null;
}

function pause(ms) { return BotBuilderModules.utils.pause(ms); }
function replVars(text, ctx) { return BotBuilderModules.utils.replVars(text, ctx); }

function showSimTyping() { if(simulatorModule) simulatorModule.showTyping(); }
function hideSimTyping() { if(simulatorModule) simulatorModule.hideTyping(); }
function simMsg(t) { if(simulatorModule) simulatorModule.msg(t); }
function simBtns(text, btns) { if(simulatorModule) simulatorModule.buttons(text, btns); }

window.simBtnClick = function(t) {
    simUserMsg(t);
    // Route through the button edge system
    if(sim.currentNode) {
        const n = sim.currentNode;
        const btnLikeTypes = ['buttons','list','pic_choice','cards'];
        if(btnLikeTypes.includes(n.type)) {
            // Store selection in context
            sim.context['last_selection'] = t;
            if(n.config.variable) sim.context[n.config.variable] = t;
            updateSimVarsPanel();
            sim.waiting = false;

            const nx = resolveSimChoice(n.id, t, { partial: false });

            if(nx) runSim(nx);
            return;
        }
    }
    processSim(t);
};

window.handlePreviewInput = function(e) { if(e.key==='Enter') sendPreviewMessage(); };

window.sendPreviewMessage = function() {
    const inp = $('#preview-input');
    const t = inp.value.trim(); if(!t) return;
    simUserMsg(t); inp.value = ''; processSim(t);
};

function simUserMsg(t) { if(simulatorModule) simulatorModule.userMsg(t); }

function processSim(text) {
    if(simulatorModule) simulatorModule.processResponse(text);
}

function validateSimInput(node, text) {
    return simulatorModule ? simulatorModule.validateInput(node, text) : {valid: true};
}

function scrollChat() { if(simulatorModule) simulatorModule.scrollChat(); }

// ===================== PUBLISH & INTEGRATION MODAL =====================
const canvasCoreModule = window.BotBuilderModules && window.BotBuilderModules.canvasCore;
if(canvasCoreModule) {
    canvasCoreModule.init({
        BB,
        canvas,
        canvasContainer,
        $,
        zoomCanvas: window.zoomCanvas,
        undo: window.undo,
        redo: window.redo,
        saveFlow: window.saveFlow,
        showToast,
        deleteNode: window.deleteNode,
        duplicateNode: window.duplicateNode,
        selectNode,
        openInspector,
        closeInspector: window.closeInspector,
        viewportStorageKey,
        viewportSaveTimer
    });
}

const publishModalModule = window.BotBuilderModules && window.BotBuilderModules.publishModal;
if(publishModalModule) {
    publishModalModule.init({
        BB,
        showToast
    });
}

})();
