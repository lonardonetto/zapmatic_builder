(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};
var M = window.BotBuilderModules.utils = {};

function $(s, p) { return (p || document).querySelector(s); }
function $$(s, p) { return (p || document).querySelectorAll(s); }

function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function uuidv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random()*16|0; return (c==='x'?r:(r&0x3|0x8)).toString(16);
    });
}

function snap(v, grid) {
    var g = grid || 20;
    return Math.round(v / g) * g;
}

function pause(ms) {
    return new Promise(function(r) { setTimeout(r, ms); });
}

function replVars(text, ctx) {
    return (text||'').replace(/\{\{(.*?)\}\}/g, function(m, k) { return ctx[k.trim()] || m; });
}

function showToast(msg, type) {
    var t = document.getElementById('bb-toast');
    if(!t) {
        t = document.createElement('div');
        t.id = 'bb-toast';
        t.className = 'bb-toast';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.className = 'bb-toast ' + (type || 'info');
    requestAnimationFrame(function() { t.classList.add('show'); });
    setTimeout(function() { t.classList.remove('show'); }, 2500);
}
window.showToast = showToast;

function getCubicPoint(x1, y1, cx1, cy1, cx2, cy2, x2, y2, t) {
    var u = 1 - t;
    return {
        x: u*u*u*x1 + 3*u*u*t*cx1 + 3*u*t*t*cx2 + t*t*t*x2,
        y: u*u*u*y1 + 3*u*u*t*cy1 + 3*u*t*t*cy2 + t*t*t*y2
    };
}

M.$ = $;
M.$$ = $$;
M.escHtml = escHtml;
M.uuidv4 = uuidv4;
M.snap = snap;
M.pause = pause;
M.replVars = replVars;
M.showToast = showToast;
M.getCubicPoint = getCubicPoint;

})();
