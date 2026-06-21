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

    node.querySelectorAll('.handle.out').forEach(h => {
        h.addEventListener('mousedown', e => {
            e.stopPropagation();
            connStart = { nodeId: node.dataset.id, handleId: h.dataset.handle };
            h.classList.add('connecting');

            dragLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            dragLine.setAttribute('class', 'drag-connection-line');

            const n = ctx.BB.nodes[node.dataset.id];
            const hx = n.x + h.offsetLeft + 6;
            const hy = n.y + h.offsetTop + 6;
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

function drawConnections() {
    if(!ctx) return;

    ctx.svg.innerHTML = '';
    const sim = typeof ctx.getSim === 'function' ? ctx.getSim() : null;

    ctx.BB.edges.forEach(edge => {
        const fn = ctx.BB.nodes[edge.from], tn = ctx.BB.nodes[edge.to];
        if(!fn || !tn) return;
        const fromEl = ctx.$(`[data-id="${edge.from}"]`);
        const toEl = ctx.$(`[data-id="${edge.to}"]`);
        if(!fromEl || !toEl) return;

        let hEl = fromEl.querySelector(`.handle.out[data-handle="${edge.handle}"]`);
        if(!hEl) hEl = fromEl.querySelector('.handle.out');
        if(!hEl) return;

        const x1 = fn.x + fromEl.offsetWidth + 6;
        const y1 = fn.y + hEl.offsetTop + 6;
        const x2 = tn.x - 6;
        const y2 = tn.y + (toEl.offsetHeight / 2);

        const dx = Math.abs(x2 - x1) * 0.5;
        const d = `M ${x1} ${y1} C ${x1+dx} ${y1}, ${x2-dx} ${y2}, ${x2} ${y2}`;
        let cls = 'connection-line';
        const edgeKey = `${edge.from}->${edge.to}:${edge.handle || 'default'}`;
        if(edge.handle === 'true') cls += ' true-edge';
        if(edge.handle === 'false') cls += ' false-edge';
        if(sim && sim.traversedEdges && sim.traversedEdges.includes(edgeKey)) cls += ' sim-traversed-edge';

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

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', d);
        path.setAttribute('class', cls);
        path.dataset.edgeKey = edgeKey;

        path.addEventListener('click', () => {
            if(confirm('Excluir esta conexão?')) deleteEdge();
        });
        ctx.svg.appendChild(path);

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
