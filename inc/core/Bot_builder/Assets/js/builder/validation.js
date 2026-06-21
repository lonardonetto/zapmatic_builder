/*
 * Bot Builder — validação visual do fluxo.
 * Extraído de bot_builder.js para isolar regras de diagnóstico do canvas.
 */
(function() {
'use strict';

window.BotBuilderModules = window.BotBuilderModules || {};

let ctx = null;

function renderFlowValidation() {
    if(!ctx) return;

    const body = ctx.$('#flow-validation-body');
    if(!body) return;

    const nodes = Object.values(ctx.BB.nodes || {});
    const edges = ctx.BB.edges || [];
    const issues = [];
    const startNodes = nodes.filter(n => n.type === 'start');
    const endNodes = nodes.filter(n => n.type === 'end');

    if(!nodes.length) issues.push({type:'error', icon:'fa-exclamation-circle', text:'O fluxo ainda não possui blocos.'});
    if(!startNodes.length) issues.push({type:'error', icon:'fa-play-circle', text:'Adicione um bloco Início para definir a entrada do fluxo.'});
    if(startNodes.length > 1) issues.push({type:'warn', icon:'fa-code-branch', text:'Existe mais de um bloco Início. Revise qual deve iniciar o fluxo.'});
    if(!endNodes.length) issues.push({type:'warn', icon:'fa-flag-checkered', text:'Nenhum bloco Fim encontrado. O fluxo pode terminar sem encerramento visual.'});

    nodes.forEach(node => {
        const outgoing = edges.filter(e => e.from === node.id);
        const incoming = edges.filter(e => e.to === node.id);
        const label = (ctx.NODE_DEFS[node.type]?.label || node.type || 'Bloco');
        if(node.type !== 'end' && !outgoing.length) issues.push({type:'warn', icon:'fa-unlink', text:`${label} está sem conexão de saída.`});
        if(node.type !== 'start' && !incoming.length) issues.push({type:'warn', icon:'fa-link-slash', text:`${label} não recebe conexão de nenhum bloco.`});
        if(['text','buttons','list','cards','image','video','audio'].includes(node.type)) {
            const c = node.config || {};
            const hasContent = String(c.text || c.caption || c.question || c.url || c.template_name || '').trim();
            if(!hasContent) issues.push({type:'warn', icon:'fa-align-left', text:`${label} parece estar sem conteúdo configurado.`});
        }
    });

    const okItems = issues.length ? [] : [{type:'ok', icon:'fa-check-circle', text:'Nenhum problema visual encontrado. O fluxo parece pronto para teste.'}];
    const items = [...okItems, ...issues].slice(0, 30).map(item => `<div class="flow-validation-item ${item.type}"><i class="fas ${item.icon}"></i><span>${ctx.escHtml(item.text)}</span></div>`).join('');

    body.innerHTML = `<div class="flow-validation-summary">
        <div class="flow-validation-stat"><b>${nodes.length}</b><span>Blocos</span></div>
        <div class="flow-validation-stat"><b>${edges.length}</b><span>Conexões</span></div>
        <div class="flow-validation-stat"><b>${issues.length}</b><span>Avisos</span></div>
    </div><div class="flow-validation-list">${items}</div>`;
}

function toggleValidationPanel() {
    if(!ctx) return;

    const panel = ctx.$('#flow-validation-panel');
    if(!panel) return;

    const opening = panel.classList.contains('hidden');
    panel.classList.toggle('hidden');
    if(opening) renderFlowValidation();
}

function init(context) {
    ctx = context;
}

window.BotBuilderModules.validation = {
    init,
    toggleValidationPanel,
    renderFlowValidation
};

})();
