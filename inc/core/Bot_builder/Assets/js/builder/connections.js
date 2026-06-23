/*
 * Bot Builder — conexões do canvas.
 * Extraído de bot_builder.js para isolar desenho, criação, hitbox e exclusão de ligações.
 */
(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};

let ctx = null;
let connStart = null;
let dragLine = null;
let listenersBound = false;

function getCubicPoint(x1, y1, cx1, cy1, cx2, cy2, x2, y2, t) {
    const mt = 1 - t;
    return {
        x: (mt ** 3) * x1 + 3 * (mt ** 2) * t * cx1 + 3 * mt * (t ** 2) * cx2 + (t ** 3) * x2,
        y: (mt ** 3) * y1 + 3 * (mt ** 2) * t * cy1 + 3 * mt * (t ** 2) * cy2 + (t ** 3) * y2
    };
}

function enableConnections(node) {
    if(!ctx || !node) return;

    var container = node.querySelector('.handles-container');
    var contL = container ? container.offsetLeft : 0;
    var contT = container ? container.offsetTop : 0;

    node.querySelectorAll('.handle.out').forEach(h => {
        h.addEventListener('mousedown', e => {
            e.stopPropagation();
            connStart = { nodeId: node.dataset.id, handleId: h.dataset.handle };
            h.classList.add('connecting');

            dragLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            dragLine.setAttribute('class', 'drag-connection-line');

            const n = ctx.BB.nodes[node.dataset.id];
            // Start from center of the handle dot
            const hx = n.x + contL + h.offsetLeft + h.offsetWidth / 2;
            const hy = n.y + contT + h.offsetTop + h.offsetHeight / 2;
            dragLine.setAttribute('x1', hx);
            dragLine.setAttribute('y1', hy);
            dragLine.setAttribute('x2', hx);
            dragLine.setAttribute('y2', hy);
            ctx.svg.appendChild(dragLine);
        });
    });
}

function onMouseMove(e) {
    if(!ctx || !connStart || !dragLine) return;
    const rect = ctx.canvasContainer.getBoundingClientRect();
    const mx = (e.clientX - rect.left - ctx.BB.panX) / ctx.BB.zoom;
    const my = (e.clientY - rect.top - ctx.BB.panY) / ctx.BB.zoom;
    dragLine.setAttribute('x2', mx);
    dragLine.setAttribute('y2', my);
}

function onMouseUp(e) {
    if(!ctx || !connStart) return;

    if(dragLine) {
        dragLine.remove();
        dragLine = null;
    }

    ctx.$$('.handle.connecting').forEach(h => h.classList.remove('connecting'));

    if(e.target.classList.contains('handle') && e.target.classList.contains('in')) {
        const targetEl = e.target.closest('.bot-node');
        if(targetEl && targetEl.dataset.id !== connStart.nodeId) {
            ctx.saveSnapshot();
            ctx.BB.edges = ctx.BB.edges.filter(ed => !(ed.from === connStart.nodeId && ed.handle === connStart.handleId));
            ctx.BB.edges.push({ from: connStart.nodeId, to: targetEl.dataset.id, handle: connStart.handleId });
            drawConnections();
            ctx.triggerAutoSave();
        }
    }

    connStart = null;
}

function bindListeners() {
    if(listenersBound) return;
    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
    listenersBound = true;
}

function _buildNodeHandleCache() {
    // Cache container offsets per node once per draw cycle
    var cache = {};
    ctx.BB.edges.forEach(function(edge) {
        if(!cache[edge.from]) {
            var el = ctx.$('[data-id="' + edge.from + '"]');
            if(el) {
                var c = el.querySelector('.handles-container');
                cache[edge.from] = c ? { l: c.offsetLeft, t: c.offsetTop } : { l: 0, t: 0 };
            }
        }
        if(!cache[edge.to]) {
            var el = ctx.$('[data-id="' + edge.to + '"]');
            if(el) {
                var c = el.querySelector('.handles-container');
                cache[edge.to] = c ? { l: c.offsetLeft, t: c.offsetTop } : { l: 0, t: 0 };
            }
        }
    });
    return cache;
}

function drawConnections() {
    if(!ctx) return;

    ctx.svg.innerHTML = '';
    var sim = typeof ctx.getSim === 'function' ? ctx.getSim() : null;
    var handleCache = _buildNodeHandleCache();

    ctx.BB.edges.forEach(function(edge) {
        var fn = ctx.BB.nodes[edge.from], tn = ctx.BB.nodes[edge.to];
        if(!fn || !tn) return;
        var fromEl = ctx.$('[data-id="' + edge.from + '"]');
        var toEl = ctx.$('[data-id="' + edge.to + '"]');
        if(!fromEl || !toEl) return;

        var hEl = fromEl.querySelector('[data-handle="' + edge.handle + '"].handle.out');
        if(!hEl) hEl = fromEl.querySelector('.handle.out');
        if(!hEl) return;

        var inEl = toEl.querySelector('.handle.in');
        if(!inEl) return;

        var fc = handleCache[edge.from] || { l: 0, t: 0 };
        var tc = handleCache[edge.to] || { l: 0, t: 0 };
        var x1 = fn.x + fc.l + hEl.offsetLeft + hEl.offsetWidth / 2;
        var y1 = fn.y + fc.t + hEl.offsetTop + hEl.offsetHeight / 2;
        var x2 = tn.x + tc.l + inEl.offsetLeft + inEl.offsetWidth / 2;
        var y2 = tn.y + tc.t + inEl.offsetTop + inEl.offsetHeight / 2;

        const dx = Math.abs(x2 - x1) * 0.5;
        const d = `M ${x1} ${y1} C ${x1+dx} ${y1}, ${x2-dx} ${y2}, ${x2} ${y2}`;
        let cls = 'connection-line';
        const handleColor = getComputedStyle(hEl).backgroundColor || '#6478ff';
        const edgeKey = `${edge.from}->${edge.to}:${edge.handle || 'default'}`;
        if(edge.handle === 'true') cls += ' true-edge';
        if(edge.handle === 'false') cls += ' false-edge';
        const isTraversed = sim && sim.traversedEdges && sim.traversedEdges.includes(edgeKey);
        if(isTraversed) cls += ' sim-traversed-edge';
        const lineColor = isTraversed ? '#10b981' : handleColor;

        const deleteEdge = () => {
            ctx.saveSnapshot();
            ctx.BB.edges = ctx.BB.edges.filter(ed => ed !== edge);
            drawConnections();
            ctx.triggerAutoSave();
        };

        const hitbox = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        hitbox.setAttribute('d', d);
        hitbox.setAttribute('class', 'connection-hitbox');
        hitbox.dataset.edgeKey = edgeKey;
        hitbox.addEventListener('click', () => {
            if(confirm('Excluir esta conexão?')) deleteEdge();
        });
        ctx.svg.appendChild(hitbox);

        const shadowPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        shadowPath.setAttribute('d', d);
        shadowPath.setAttribute('class', cls + ' connection-line-shadow');
        shadowPath.dataset.edgeKey = edgeKey;
        shadowPath.style.stroke = lineColor;
        ctx.svg.appendChild(shadowPath);

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', d);
        path.setAttribute('class', cls + ' connection-line-core');
        path.dataset.edgeKey = edgeKey;
        path.style.stroke = lineColor;

        path.addEventListener('click', () => {
            if(confirm('Excluir esta conexão?')) deleteEdge();
        });
        ctx.svg.appendChild(path);

        const shinePath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        shinePath.setAttribute('d', d);
        shinePath.setAttribute('class', cls + ' connection-line-shine');
        shinePath.dataset.edgeKey = edgeKey;
        ctx.svg.appendChild(shinePath);

        const mid = getCubicPoint(x1, y1, x1 + dx, y1, x2 - dx, y2, x2, y2, 0.5);
        const action = document.createElementNS('http://www.w3.org/2000/svg', 'foreignObject');
        action.setAttribute('x', mid.x - 15);
        action.setAttribute('y', mid.y - 15);
        action.setAttribute('width', 30);
        action.setAttribute('height', 30);
        action.setAttribute('class', 'connection-delete-wrap');
        action.innerHTML = '<button type="button" class="connection-delete-btn" title="Excluir conexão" aria-label="Excluir conexão"><i class="fa fa-trash"></i><span>Excluir conexão</span></button>';
        const btn = action.querySelector('button');
        btn.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            deleteEdge();
        });
        ctx.svg.appendChild(action);
    });
}

function init(context) {
    ctx = context;
    bindListeners();
}

window.BotBuilderModules.connections = {
    init,
    enableConnections,
    drawConnections,
    getCubicPoint
};

})();
