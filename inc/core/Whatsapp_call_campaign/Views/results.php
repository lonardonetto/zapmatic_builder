<div class="row mb-4">
    <div class="col-12">
        <a href="<?php _e(base_url('whatsapp_call_campaign')) ?>" class="btn btn-light btn-sm mb-3"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
        <h4 class="fw-bold"><?php echo htmlspecialchars($campaign->name) ?></h4>
        <span id="campaign-badge" class="badge bg-<?php echo ['draft'=>'secondary','running'=>'success','paused'=>'warning','completed'=>'primary','failed'=>'danger'][$campaign->status] ?? 'secondary' ?>"><?php echo htmlspecialchars($campaign->status) ?></span>
        <small class="text-muted ms-2" id="last-update"></small>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><div class="text-muted small">Total</div><h3 class="mb-0" id="s-total"><?php echo (int)$campaign->total_leads ?></h3></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><div class="text-muted small">Ligadas</div><h3 class="mb-0 text-primary" id="s-made"><?php echo (int)$campaign->calls_made ?></h3></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><div class="text-muted small">Atenderam</div><h3 class="mb-0 text-success" id="s-answered"><?php echo (int)$campaign->calls_answered ?></h3></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><div class="text-muted small">Não atenderam</div><h3 class="mb-0 text-warning" id="s-noanswer"><?php echo (int)$campaign->calls_no_answer ?></h3></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><div class="text-muted small">Ocupado</div><h3 class="mb-0 text-info" id="s-busy"><?php echo (int)$campaign->calls_busy ?></h3></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><div class="text-muted small">Erro</div><h3 class="mb-0 text-danger" id="s-failed"><?php echo (int)$campaign->calls_failed ?></h3></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header border-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Leads</h6>
        <div id="poll-indicator" class="d-none"><span class="spinner-border spinner-border-sm text-success me-2" role="status" style="width:14px;height:14px;"></span><small class="text-muted">Atualizando...</small></div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="leads-table">
                <thead class="table-light"><tr>
                    <th class="ps-3">#</th><th>Telefone</th><th>Nome</th><th>Status</th><th>Plataforma</th><th>Duração</th><th>Ouviu</th><th>Etapas</th><th>Erro</th><th class="pe-3">Horário</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($leads as $i => $lead): ?>
                    <?php $leadEvents = $eventsByLead[$lead->id] ?? []; ?>
                    <tr>
                        <td class="ps-3"><?php echo $i + 1 ?></td>
                        <td><?php echo htmlspecialchars($lead->phone) ?></td>
                        <td><?php echo htmlspecialchars($lead->name) ?></td>
                        <td><?php $sBadge = ['pending'=>'secondary','ringing'=>'info','answered'=>'success','no_answer'=>'warning','busy'=>'info','failed'=>'danger','cancelled'=>'dark']; ?><span class="badge bg-<?php echo $sBadge[$lead->status] ?? 'secondary' ?>"><?php echo htmlspecialchars(call_label_status($lead->status)) ?></span></td>
                        <td><?php echo htmlspecialchars(call_label_platform($lead->platform ?? '')) ?></td>
                        <td><?php echo $lead->duration_seconds > 0 ? $lead->duration_seconds . 's' : '—' ?></td>
                        <td><?php echo !empty($lead->heard_full_audio) ? '✅' : '—' ?></td>
                        <td class="small">
                            <?php if (!empty($leadEvents)): ?>
                                <?php foreach ($leadEvents as $ev): ?>
                                <span class="d-block text-muted" title="<?php echo htmlspecialchars($ev->reason ?? '') ?>">
                                    <?php echo htmlspecialchars(call_label_event($ev->event)) ?><?php echo $ev->platform ? ' · ' . htmlspecialchars(call_label_platform($ev->platform)) : '' ?><?php $rLabel = call_label_reason($ev->reason ?? ''); echo $rLabel ? ' (' . htmlspecialchars($rLabel) . ')' : '' ?>
                                </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-danger small"><?php $err = call_label_reason($lead->error_message ?? ''); echo htmlspecialchars($err ?: ($lead->error_message ?? '')) ?></td>
                        <td class="text-muted small"><?php echo htmlspecialchars($lead->started_at ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    var CAMPAIGN_ID = <?php echo (int)$campaign->id ?>;
    var T = <?php echo call_translations_json(); ?>;
    var pollTimer = null;
    var indicator = document.getElementById("poll-indicator");
    var badge = document.getElementById("campaign-badge");
    var STATUS_CLASSES = {draft:"secondary",scheduled:"info",running:"success",paused:"warning",completed:"primary","failed":"danger"};
    var BADGE_CLASSES = {pending:"secondary",ringing:"info",answered:"success",no_answer:"warning",busy:"info",failed:"danger",cancelled:"dark"};

    function esc(s) { var d=document.createElement("div"); d.textContent=s||""; return d.innerHTML; }
    function tStatus(s){ return (T.status&&T.status[s])||s; }
    function tEvent(e){ return (T.events&&T.events[e])||e; }
    function tPlatform(p){ return (T.platform&&T.platform[p])||"—"; }
    function tReason(r){
        if(!r)return "";
        var lower=(r||"").toLowerCase().trim();
        if(T.reasons&&T.reasons[lower])return T.reasons[lower];
        if(lower.indexOf("server:")===0)return "erro do servidor ("+r.substring(7)+")";
        if(lower.indexOf("no devices")!==-1||lower.indexOf("unreachable")!==-1)return "sem WhatsApp / inalcançável";
        return r;
    }

    function updateStats(c) {
        document.getElementById("s-total").textContent=c.total_leads;
        document.getElementById("s-made").textContent=c.calls_made;
        document.getElementById("s-answered").textContent=c.calls_answered;
        document.getElementById("s-noanswer").textContent=c.calls_no_answer;
        document.getElementById("s-busy").textContent=c.calls_busy;
        document.getElementById("s-failed").textContent=c.calls_failed;
        var sc=STATUS_CLASSES[c.status]||"secondary";
        badge.className="badge bg-"+sc;
        badge.textContent=tStatus(c.status);
        document.getElementById("last-update").textContent="Atualizado: "+new Date().toLocaleTimeString();
    }

    function updateLeads(leads, events) {
        var tbody=document.querySelector("#leads-table tbody");
        if(!tbody)return;
        var h="";
        for(var i=0;i<leads.length;i++){
            var l=leads[i];
            var evs=events&&events[l.id]?events[l.id]:[];
            var evHtml="<span class=text-muted>—</span>";
            if(evs.length){
                evHtml="";
                for(var j=0;j<evs.length;j++){
                    var e=evs[j];
                    var rl=tReason(e.reason);
                    evHtml+="<span class='d-block text-muted'>"+esc(tEvent(e.event))+(e.platform?" · "+esc(tPlatform(e.platform)):"")+(rl?" ("+esc(rl)+")":"")+"</span>";
                }
            }
            var err=tReason(l.error_message);
            h+="<tr><td class=ps-3>"+(i+1)+"</td>"+
                "<td>"+esc(l.phone)+"</td>"+
                "<td>"+esc(l.name)+"</td>"+
                "<td><span class="badge bg-"+(BADGE_CLASSES[l.status]||"secondary")+"">"+esc(tStatus(l.status))+"</span></td>"+
                "<td>"+esc(tPlatform(l.platform))+"</td>"+
                "<td>"+(l.duration_seconds>0?l.duration_seconds+"s":"—")+"</td>"+
                "<td>"+(l.heard_full_audio?"✅":"—")+"</td>"+
                "<td class=small>"+evHtml+"</td>"+
                "<td class="text-danger small">"+esc(err||"")+"</td>"+
                "<td class="text-muted small">"+esc(l.started_at||"—")+"</td></tr>";
        }
        tbody.innerHTML=h;
    }

    function poll() {
        fetch("<?php echo base_url('whatsapp_call_campaign/status/' . $campaign->id) ?>",{headers:{"X-Requested-With":"XMLHttpRequest"}})
        .then(function(r){return r.json();})
        .then(function(d){
            indicator.classList.add("d-none");
            if(d.status==="success"&&d.campaign){
                updateStats(d.campaign);
                if(d.leads)updateLeads(d.leads, d.events);
                if(d.campaign.status==="completed"||d.campaign.status==="failed"){
                    clearInterval(pollTimer);pollTimer=null;
                    indicator.innerHTML='<small class="text-success"><i class="fas fa-check me-1"></i>Concluído</small>';
                    indicator.classList.remove("d-none");
                }
            }
        }).catch(function(){indicator.classList.add("d-none");});
    }

    var s="<?php echo $campaign->status ?>";
    if(s==="running"||s==="scheduled"){
        pollTimer=setInterval(function(){indicator.classList.remove("d-none");poll();},3000);
    }
})();
</script>