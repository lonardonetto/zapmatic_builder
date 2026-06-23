(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
var M = window.BotBuilderModules.canvasCore = {};
var H = null;

M.init = function(ctx) { H = ctx; };
function c() { return H || {}; }

function loadCanvasViewport() {
    var ctx = c();
    try {
        var saved = JSON.parse(localStorage.getItem(ctx.viewportStorageKey) || 'null');
        if(!saved) return false;
        var zoom = Number(saved.zoom);
        var panX = Number(saved.panX);
        var panY = Number(saved.panY);
        if(!Number.isFinite(zoom) || !Number.isFinite(panX) || !Number.isFinite(panY)) return false;
        ctx.BB.zoom = Math.max(0.2, Math.min(2, zoom));
        ctx.BB.panX = panX;
        ctx.BB.panY = panY;
        return true;
    } catch(e) { return false; }
}

function saveCanvasViewport() {
    var ctx = c();
    clearTimeout(ctx.viewportSaveTimer);
    ctx.viewportSaveTimer = setTimeout(function() {
        try {
            localStorage.setItem(ctx.viewportStorageKey, JSON.stringify({ zoom: ctx.BB.zoom, panX: ctx.BB.panX, panY: ctx.BB.panY }));
        } catch(e) {}
    }, 120);
}

function updateTransform() {
    var ctx = c();
    ctx.canvas.style.transform = 'translate(' + ctx.BB.panX + 'px, ' + ctx.BB.panY + 'px) scale(' + ctx.BB.zoom + ')';
    updateZoomDisplay();
    saveCanvasViewport();
}

function updateZoomDisplay() {
    var el = c().$('#zoom-level');
    if(el) el.textContent = Math.round(c().BB.zoom * 100) + '%';
}

function setupCanvasPan() {
    var ctx = c();
    var panning = false, sx, sy;
    ctx.canvasContainer.addEventListener('mousedown', function(e) {
        if(e.button === 1 || (e.button === 0 && (e.target === ctx.canvasContainer || e.target === ctx.canvas || e.target.tagName === 'svg'))) {
            panning = true;
            sx = e.clientX; sy = e.clientY;
            ctx.canvasContainer.style.cursor = 'grabbing';
            e.preventDefault();
        }
    });
    document.addEventListener('mousemove', function(e) {
        if(!panning) return;
        ctx.BB.panX += e.clientX - sx;
        ctx.BB.panY += e.clientY - sy;
        sx = e.clientX; sy = e.clientY;
        updateTransform();
    });
    document.addEventListener('mouseup', function() {
        panning = false;
        ctx.canvasContainer.style.cursor = 'grab';
    });
    ctx.canvasContainer.addEventListener('wheel', function(e) {
        e.preventDefault();
        var delta = e.deltaY > 0 ? -0.05 : 0.05;
        ctx.zoomCanvas(delta);
    }, { passive: false });
}

function setupKeyboard() {
    var ctx = c();
    document.addEventListener('keydown', function(e) {
        if(e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
        var meta = e.metaKey || e.ctrlKey;
        if(meta && e.key === 'z' && !e.shiftKey) { e.preventDefault(); ctx.undo(); }
        if(meta && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) { e.preventDefault(); ctx.redo(); }
        if(meta && e.key === 's') { e.preventDefault(); ctx.saveFlow(false); ctx.showToast('Salvo!', 'success'); }
        if(e.key === 'Delete' || e.key === 'Backspace') {
            if(ctx.BB.selectedNode && ctx.BB.nodes[ctx.BB.selectedNode] && ctx.BB.nodes[ctx.BB.selectedNode].type !== 'start') { ctx.deleteNode(ctx.BB.selectedNode); }
        }
        if(meta && e.key === 'd') { e.preventDefault(); if(ctx.BB.selectedNode) ctx.duplicateNode(ctx.BB.selectedNode); }
        if(e.key === 'Escape') { if(ctx.closeInspector) ctx.closeInspector(); }
    });
}

function setupContextMenu() {
    var ctx = c();
    var menu = document.createElement('div');
    menu.className = 'context-menu';
    menu.id = 'context-menu';
    menu.innerHTML = '<div class="context-menu-item" data-action="duplicate"><i class="fas fa-copy"></i>Duplicar<span class="shortcut">⌘D</span></div><div class="context-menu-item" data-action="inspect"><i class="fas fa-cog"></i>Configurar</div><div class="context-menu-divider"></div><div class="context-menu-item danger" data-action="delete"><i class="fas fa-trash-alt"></i>Excluir<span class="shortcut">Del</span></div>';
    document.body.appendChild(menu);

    ctx.canvasContainer.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        var nodeEl = e.target.closest('.bot-node');
        if(!nodeEl) { menu.classList.remove('show'); return; }
        var id = nodeEl.dataset.id;
        ctx.selectNode(id);
        menu.style.left = e.clientX + 'px';
        menu.style.top = e.clientY + 'px';
        menu.classList.add('show');
        menu.onclick = function(ev) {
            var item = ev.target.closest('.context-menu-item');
            if(!item) return;
            var act = item.dataset.action;
            if(act === 'duplicate') ctx.duplicateNode(id);
            if(act === 'delete') ctx.deleteNode(id);
            if(act === 'inspect') ctx.openInspector(id);
            menu.classList.remove('show');
        };
    });

    document.addEventListener('click', function() { menu.classList.remove('show'); });
}

M.loadCanvasViewport = loadCanvasViewport;
M.saveCanvasViewport = saveCanvasViewport;
M.updateTransform = updateTransform;
M.updateZoomDisplay = updateZoomDisplay;
M.setupCanvasPan = setupCanvasPan;
M.setupKeyboard = setupKeyboard;
M.setupContextMenu = setupContextMenu;

})();
