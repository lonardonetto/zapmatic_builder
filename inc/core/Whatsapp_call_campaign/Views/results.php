<div class="row mb-4">
    <div class="col-12">
        <a href="<?php _e(base_url('whatsapp_call_campaign')) ?>" class="btn btn-light btn-sm mb-3">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
        <h4 class="fw-bold"><?php echo htmlspecialchars($campaign->name) ?></h4>
        <span class="badge bg-<?php echo ['draft'=>'secondary','running'=>'success','paused'=>'warning','completed'=>'primary','failed'=>'danger'][$campaign->status] ?? 'secondary' ?>">
            <?php echo htmlspecialchars($campaign->status) ?>
        </span>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">Total</div>
            <h3 class="mb-0"><?php echo (int)$campaign->total_leads ?></h3>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">Ligadas</div>
            <h3 class="mb-0 text-primary"><?php echo (int)$campaign->calls_made ?></h3>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">Atenderam</div>
            <h3 class="mb-0 text-success"><?php echo (int)$campaign->calls_answered ?></h3>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">Não atenderam</div>
            <h3 class="mb-0 text-warning"><?php echo (int)$campaign->calls_no_answer ?></h3>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">Ocupado</div>
            <h3 class="mb-0 text-info"><?php echo (int)$campaign->calls_busy ?></h3>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="text-muted small">Erro</div>
            <h3 class="mb-0 text-danger"><?php echo (int)$campaign->calls_failed ?></h3>
        </div>
    </div>
</div>

<!-- Leads Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header border-0">
        <h6 class="mb-0">Leads</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Telefone</th>
                        <th>Nome</th>
                        <th>Status</th>
                        <th>Duração</th>
                        <th>Erro</th>
                        <th class="pe-3">Horário</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $i => $lead): ?>
                    <tr>
                        <td class="ps-3"><?php echo $i + 1 ?></td>
                        <td><?php echo htmlspecialchars($lead->phone) ?></td>
                        <td><?php echo htmlspecialchars($lead->name) ?></td>
                        <td>
                            <?php
                            $sBadge = ['pending'=>'secondary','ringing'=>'info','answered'=>'success','no_answer'=>'warning','busy'=>'info','failed'=>'danger','cancelled'=>'dark'];
                            ?>
                            <span class="badge bg-<?php echo $sBadge[$lead->status] ?? 'secondary' ?>"><?php echo htmlspecialchars($lead->status) ?></span>
                        </td>
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
