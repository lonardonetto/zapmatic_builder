(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
var M = window.BotBuilderModules.history = {};
var H = null; // Will hold init context

M.init = function(ctx) {
    H = ctx;
};

function ctx() { return H || {}; }

function saveSnapshot() {
    var c = ctx();
    c.BB.undoStack.push(JSON.stringify({ nodes: c.BB.nodes, edges: c.BB.edges }));
    if(c.BB.undoStack.length > 50) c.BB.undoStack.shift();
    c.BB.redoStack = [];
    updateUndoBtn();
}

function restoreSnapshot(snap) {
    var c = ctx();
    // Clear canvas
    c.$$('.bot-node').forEach(function(n) { n.remove(); });
    c.BB.nodes = {};
    c.BB.edges = [];
    // Recreate
    Object.values(snap.nodes).forEach(function(n) {
        c.createNode(n.id, n.type, n.x, n.y, n.config, true);
    });
    snap.edges.forEach(function(e) { c.BB.edges.push(e); });
    c.drawConnections();
    c.inspectorPanel.classList.add('hidden');
}

function updateUndoBtn() {
    var c = ctx();
    var u = c.$('#btn-undo'), r = c.$('#btn-redo');
    if(u) u.disabled = c.BB.undoStack.length < 2;
    if(r) r.disabled = !c.BB.redoStack.length;
}

function undo() {
    var c = ctx();
    if(c.BB.undoStack.length < 2) return;
    c.BB.redoStack.push(c.BB.undoStack.pop());
    var snap = JSON.parse(c.BB.undoStack[c.BB.undoStack.length - 1]);
    restoreSnapshot(snap);
    updateUndoBtn();
    c.triggerAutoSave();
}

function redo() {
    var c = ctx();
    if(!c.BB.redoStack.length) return;
    var snap = JSON.parse(c.BB.redoStack.pop());
    c.BB.undoStack.push(JSON.stringify(snap));
    restoreSnapshot(snap);
    updateUndoBtn();
    c.triggerAutoSave();
}

M.saveSnapshot = saveSnapshot;
M.undo = undo;
M.redo = redo;
M.restoreSnapshot = restoreSnapshot;
M.updateUndoBtn = updateUndoBtn;

})();
