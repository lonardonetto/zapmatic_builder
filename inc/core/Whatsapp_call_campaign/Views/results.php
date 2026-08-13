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
                    <th class="ps-3">#</th><th>Telefone</th><th>Nome</th><th>Status</th><th>Duração</th><th>Erro</th><th class="pe-3">Horário</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($leads as $i => $lead): ?>
                    <tr>
                        <td class="ps-3"><?php echo $i + 1 ?></td>
                        <td><?php echo htmlspecialchars($lead->phone) ?></td>
                        <td><?php echo htmlspecialchars($lead->name) ?></td>
                        <td><?php $sBadge = ['pending'=>'secondary','ringing'=>'info','answered'=>'success','no_answer'=>'warning','busy'=>'info','failed'=>'danger','cancelled'=>'dark']; ?><span class="badge bg-<?php echo $sBadge[$lead->status] ?? 'secondary' ?>"><?php echo htmlspecialchars($lead->status) ?></span></td>
                        <td><?php echo $lead->duration_seconds > 0 ? $lead->duration_seconds . 's' : '—' ?></td>
                        <td class="text-danger small"><?php echo htmlspecialchars($lead->error_message ?? '') ?></td>
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
    var pollTimer = null;
    var indicator = document.getElementById("poll-indicator");
    var badge = document.getElementById("campaign-badge");
    var STATUS_CLASSES = {draft:"secondary",scheduled:"info",running:"success",paused:"warning",completed:"primary","failed":"danger"};
    var BADGE_CLASSES = {pending:"secondary",ringing:"info",answered:"success",no_answer:"warning",busy:"info",failed:"danger",cancelled:"dark"};

    function esc(s) { var d=document.createElement("div"); d.textContent=s||""; return d.innerHTML; }

    function updateStats(c) {
        document.getElementById("s-total").textContent=c.total_leads;
        document.getElementById("s-made").textContent=c.calls_made;
        document.getElementById("s-answered").textContent=c.calls_answered;
        document.getElementById("s-noanswer").textContent=c.calls_no_answer;
        document.getElementById("s-busy").textContent=c.calls_busy;
        document.getElementById("s-failed").textContent=c.calls_failed;
        var sc=STATUS_CLASSES[c.status]||"secondary";
        badge.className="badge bg-"+sc;
        badge.textContent=c.status;
        document.getElementById("last-update").textContent="Atualizado: "+new Date().toLocaleTimeString();
    }

    function updateLeads(leads) {
        var tbody=document.querySelector("#leads-table tbody");
        if(!tbody)return;
        var h="";
        for(var i=0;i<leads.length;i++){
            var l=leads[i];
            h+="<tr><td class=ps-3>"+(i+1)+"</td>"+
                "<td>"+esc(l.phone)+"</td>"+
                "<td>"+esc(l.name)+"</td>"+
                "<td><span class="badge bg-"+(BADGE_CLASSES[l.status]||"secondary")+"">"+esc(l.status)+"</span></td>"+
                "<td>"+(l.duration_seconds>0?l.duration_seconds+"s":"—")+"</td>"+
                "<td class="text-danger small">"+esc(l.error_message||"")+"</td>"+
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
                if(d.leads)updateLeads(d.leads);
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