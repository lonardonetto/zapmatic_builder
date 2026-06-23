(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
window.BotBuilderModules.simulator = window.BotBuilderModules.simulator || {};
var M = window.BotBuilderModules.simulator;

function ctx() { return M._ctx || {}; }
function getSim() { return ctx().getSim ? ctx().getSim() : null; }

function clearActiveNode() {
    document.querySelectorAll('.bot-node.sim-active').forEach(function(el) { el.classList.remove('sim-active'); });
}

function setActiveNode(nodeId) {
    clearActiveNode();
    var el = document.querySelector('.bot-node[data-id="' + nodeId + '"]');
    if(el) el.classList.add('sim-active');
}

function updateVarsPanel() {
    var c = ctx();
    var sim = getSim();
    var list = c.$('#sim-vars-list');
    var count = c.$('#sim-vars-count');
    if(!list || !count || !sim) return;
    var entries = Object.entries(sim.context || {});
    count.textContent = entries.length;
    if(!entries.length) {
        list.innerHTML = '<div class="sim-vars-empty">Nenhuma variável capturada ainda.</div>';
        return;
    }
    list.innerHTML = entries.map(function(e) { return '<div class="sim-var-row"><span class="sim-var-key">' + c.escHtml(e[0]) + '</span><span class="sim-var-value" title="' + c.escHtml(String(e[1])) + '">' + c.escHtml(String(e[1])) + '</span></div>'; }).join('');
}

function updateHistoryPanel() {
    var c = ctx();
    var sim = getSim();
    var list = c.$('#sim-history-list');
    var count = c.$('#sim-history-count');
    if(!list || !count || !sim) return;
    var history = sim.history || [];
    count.textContent = history.length;
    if(!history.length) {
        list.innerHTML = '<div class="sim-vars-empty">Nenhum bloco percorrido ainda.</div>';
        return;
    }
    var visible = history.slice(-20);
    list.innerHTML = visible.map(function(item, idx) { return '<div class="sim-history-row"><span class="sim-history-index">' + (history.length - visible.length + idx + 1) + '</span><span class="sim-history-name" title="' + c.escHtml(item.label) + '">' + c.escHtml(item.label) + '</span><span class="sim-history-type">' + c.escHtml(item.type) + '</span></div>'; }).join('');
    list.scrollTop = list.scrollHeight;
}

function recordHistory(node) {
    var sim = getSim();
    if(!sim || !node) return;
    sim.history = sim.history || [];
    var def = ctx().NODE_DEFS[node.type] || {};
    sim.history.push({ id: node.id, type: node.type, label: def.label || node.type || 'Bloco' });
    updateHistoryPanel();
}

function markEdge(edge) {
    var sim = getSim();
    if(!sim || !edge) return;
    sim.traversedEdges = sim.traversedEdges || [];
    var key = edge.from + '->' + edge.to + ':' + (edge.handle || 'default');
    if(sim.traversedEdges.indexOf(key) === -1) sim.traversedEdges.push(key);
    ctx().drawConnections();
}

function scrollChat() {
    var c = ctx().$('#preview-chat');
    if(c) c.scrollTop = c.scrollHeight;
}

function showTyping() {
    var c = ctx();
    var chat = c.$('#preview-chat');
    if(!chat || chat.querySelector('.chat-typing')) return;
    var d = document.createElement('div');
    d.className = 'chat-msg bot chat-typing';
    d.innerHTML = '<span></span><span></span><span></span><small>digitando...</small>';
    chat.appendChild(d);
    scrollChat();
}

function hideTyping() {
    var t = ctx().$('#preview-chat');
    if(t) { t = t.querySelector('.chat-typing'); if(t) t.remove(); }
}

function msg(text) {
    hideTyping();
    var d = document.createElement('div');
    d.className = 'chat-msg bot';
    d.textContent = text;
    ctx().$('#preview-chat').appendChild(d);
    scrollChat();
}

function buttons(text, btns) {
    var c = ctx();
    hideTyping();
    var d = document.createElement('div');
    d.className = 'chat-msg bot';
    d.innerHTML = '<div>' + c.escHtml(text) + '</div><div class="chat-btn-group">' + btns.map(function(b) { return '<div class="chat-btn" onclick="simBtnClick(\'' + c.escHtml(b) + '\')">' + c.escHtml(b) + '</div>'; }).join('') + '</div>';
    c.$('#preview-chat').appendChild(d);
    scrollChat();
}

function userMsg(text) {
    var d = document.createElement('div');
    d.className = 'chat-msg user';
    d.textContent = text;
    ctx().$('#preview-chat').appendChild(d);
    scrollChat();
}

M.clearActiveNode = clearActiveNode;
M.setActiveNode = setActiveNode;
M.updateVarsPanel = updateVarsPanel;
M.updateHistoryPanel = updateHistoryPanel;
M.recordHistory = recordHistory;
M.markEdge = markEdge;
M.showTyping = showTyping;
M.hideTyping = hideTyping;
M.msg = msg;
M.buttons = buttons;
M.userMsg = userMsg;
M.scrollChat = scrollChat;

})();
