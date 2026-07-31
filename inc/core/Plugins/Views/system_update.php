<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-sync-alt text-primary"></i> Atualização do Sistema</h5>
                    <div>
                        <select id="su-channel" class="form-select form-select-sm d-inline-block w-auto me-2">
                            <option value="stable" <?php echo $channel === 'stable' ? 'selected' : '' ?>>Canal Stable</option>
                            <option value="test" <?php echo $channel === 'test' ? 'selected' : '' ?>>Canal Teste</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" onclick="suCheck()">
                            <i class="fas fa-search"></i> Verificar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Versão Atual -->
                        <div class="col-md-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Versão Atual</div>
                                    <h3 class="mb-1">v<?php echo htmlspecialchars($current['version'] ?? '0.0.0') ?></h3>
                                    <span class="badge bg-<?php echo ($channel === 'test') ? 'warning' : 'success' ?>"><?php echo strtoupper($channel) ?></span>
                                </div>
                            </div>
                        </div>
                        <!-- Atualização Disponível -->
                        <div class="col-md-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Disponível (Stable)</div>
                                    <h3 class="mb-1 text-success">v<?php echo htmlspecialchars($latest_stable ?? $current['version'] ?? '0.0.0') ?></h3>
                                    <small class="text-muted">Teste: v<?php echo htmlspecialchars($latest_test ?? '—') ?></small>
                                </div>
                            </div>
                        </div>
                        <!-- Migrações Pendentes -->
                        <div class="col-md-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Migrações SQL</div>
                                    <h3 class="mb-1 text-info"><?php echo (int)($migrations_pending ?? 0) ?> pendentes</h3>
                                    <small class="text-muted">aplicadas automaticamente</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($update_available)): ?>
                    <div class="alert alert-success mt-3 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Atualização disponível: v<?php echo htmlspecialchars($latest_stable ?? '') ?></strong>
                            <div class="small text-muted">Recomendado: fazer backup antes de atualizar.</div>
                        </div>
                        <button class="btn btn-success" onclick="suApply('<?php echo htmlspecialchars($latest_stable ?? '') ?>')">
                            <i class="fas fa-download"></i> Atualizar agora
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-secondary mt-3">
                        <i class="fas fa-check-circle text-success"></i> Seu sistema está na versão mais recente deste canal.
                    </div>
                    <?php endif; ?>

                    <!-- Histórico de Atualizações -->
                    <?php if (!empty($history)): ?>
                    <h6 class="mt-4 mb-3"><i class="fas fa-history text-muted"></i> Histórico de Atualizações</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>De</th>
                                    <th>Para</th>
                                    <th>Canal</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Rollback</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): ?>
                                <tr>
                                    <td>v<?php echo htmlspecialchars($h['from_version'] ?? '?') ?></td>
                                    <td>v<?php echo htmlspecialchars($h['to_version'] ?? '?') ?></td>
                                    <td><span class="badge bg-<?php echo $h['channel'] === 'test' ? 'warning' : 'success' ?>"><?php echo strtoupper($h['channel']) ?></span></td>
                                    <td>
                                        <?php
                                        $badges = ['pending' => 'secondary', 'applied' => 'success', 'failed' => 'danger', 'rolled_back' => 'dark'];
                                        $badge = $badges[$h['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $badge ?>"><?php echo strtoupper($h['status']) ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($h['created_at'] ?? '') ?></td>
                                    <td>
                                        <?php if ($h['status'] === 'applied' && !empty($h['backup_file'])): ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="suRollback(<?php echo (int)$h['id'] ?>)">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card { border-radius: 12px; }
.badge { font-size: 11px; }
</style>

<script>
function suCheck() {
    var channel = document.getElementById('su-channel').value;
    $.post('<?php echo base_url('plugins/system-updater/check') ?>', { channel: channel }, function(resp) {
        if (resp.status === 'success') {
            if (resp.data.update_available) {
                toastr.success(resp.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                toastr.info(resp.message);
            }
        }
    }, 'json');
}

function suApply(version) {
    if (!confirm('Atualizar o sistema para v' + version + '?\n\nUm backup será criado automaticamente antes da atualização.\nO sistema pode ficar indisponível por alguns segundos.')) return;

    var channel = document.getElementById('su-channel').value;
    $.post('<?php echo base_url('plugins/system-updater/apply') ?>', {
        target_version: version,
        channel: channel
    }, function(resp) {
        if (resp.status === 'success') {
            toastr.success(resp.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            toastr.error(resp.message || 'Erro ao atualizar');
        }
    }, 'json');
}

function suRollback(id) {
    if (!confirm('Reverter para a versão anterior?\n\nO backup desta atualização será restaurado.')) return;

    $.post('<?php echo base_url('plugins/system-updater/rollback') ?>', { update_id: id }, function(resp) {
        if (resp.status === 'success') {
            toastr.success(resp.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            toastr.error(resp.message || 'Erro no rollback');
        }
    }, 'json');
}
</script>
