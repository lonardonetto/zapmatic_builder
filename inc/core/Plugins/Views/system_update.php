<?php
_e($this->extend('Backend\Stackmin\Views\index'), false);
?>

<?php echo $this->section('content') ?>

<div class="main-wrapper flex-grow-1 n-scroll">
    <div class="container my-5">
        <div class="w-100 m-r-0 d-flex align-items-center justify-content-between mb-4">
            <h3 class="fw-bolder m-b-0 text-gray-800"><i class="fad fa-sync-alt text-primary"></i> Atualização do Sistema</h3>
            <div class="d-flex align-items-center gap-2">
                <select id="su-channel" class="form-select form-select-sm d-inline-block w-auto me-2" style="min-width:140px;">
                    <option value="stable" <?php echo ($channel ?? 'stable') === 'stable' ? 'selected' : '' ?>>Canal Stable</option>
                    <option value="test" <?php echo ($channel ?? 'stable') === 'test' ? 'selected' : '' ?>>Canal Teste</option>
                </select>
                <button class="btn btn-light btn-active-light-primary b-r-10" onclick="suCheck()">
                    <i class="fad fa-search"></i> Verificar
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <!-- Versão Atual -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-12">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">Versão Atual</div>
                        <h3 class="mb-1 text-gray-800">v<?php echo htmlspecialchars($current['version'] ?? '0.0.0') ?></h3>
                        <span class="badge bg-<?php echo ($channel ?? 'stable') === 'test' ? 'warning' : 'success' ?> text-uppercase"><?php echo htmlspecialchars($channel ?? 'stable') ?></span>
                    </div>
                </div>
            </div>
            <!-- Disponível -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-12">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">Disponível (Stable)</div>
                        <h3 class="mb-1 text-success">v<?php echo htmlspecialchars($latest_stable ?? $current['version'] ?? '0.0.0') ?></h3>
                        <small class="text-muted">Teste: v<?php echo htmlspecialchars($latest_test ?? '—') ?></small>
                    </div>
                </div>
            </div>
            <!-- Migrações -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-12">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">Migrações SQL</div>
                        <h3 class="mb-1 text-info"><?php echo (int)($migrations_pending ?? 0) ?> pendentes</h3>
                        <small class="text-muted">aplicadas automaticamente</small>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($update_available)): ?>
        <div class="alert alert-success d-flex justify-content-between align-items-center rounded-12">
            <div>
                <strong><i class="fad fa-download"></i> Atualização disponível: v<?php echo htmlspecialchars($latest_stable ?? '') ?></strong>
                <div class="small text-muted">Um backup é criado automaticamente antes de atualizar.</div>
            </div>
            <button class="btn btn-success b-r-10" onclick="suApply('<?php echo htmlspecialchars($latest_stable ?? '') ?>')">
                <i class="fad fa-rocket"></i> Atualizar agora
            </button>
        </div>
        <?php else: ?>
        <div class="alert alert-light border rounded-12">
            <i class="fad fa-check-circle text-success"></i> Seu sistema está na versão mais recente deste canal.
        </div>
        <?php endif; ?>

        <?php if (!empty($history)): ?>
        <h6 class="mt-4 mb-3 text-gray-800"><i class="fad fa-history text-muted"></i> Histórico de Atualizações</h6>
        <div class="card border-0 shadow-sm rounded-12">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">De</th>
                                <th>Para</th>
                                <th>Canal</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th class="pe-3 text-end">Rollback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td class="ps-3">v<?php echo htmlspecialchars($h['from_version'] ?? '?') ?></td>
                                <td>v<?php echo htmlspecialchars($h['to_version'] ?? '?') ?></td>
                                <td><span class="badge bg-<?php echo ($h['channel'] ?? 'stable') === 'test' ? 'warning' : 'success' ?> text-uppercase"><?php echo htmlspecialchars($h['channel'] ?? 'stable') ?></span></td>
                                <td>
                                    <?php
                                    $badges = ['pending' => 'secondary', 'applied' => 'success', 'failed' => 'danger', 'rolled_back' => 'dark'];
                                    $badge = $badges[$h['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge ?> text-uppercase"><?php echo htmlspecialchars($h['status']) ?></span>
                                </td>
                                <td class="text-muted small"><?php echo htmlspecialchars($h['created_at'] ?? '') ?></td>
                                <td class="pe-3 text-end">
                                    <?php if ($h['status'] === 'applied' && !empty($h['backup_file'])): ?>
                                    <button class="btn btn-sm btn-outline-danger b-r-8" onclick="suRollback(<?php echo (int)$h['id'] ?>)">
                                        <i class="fad fa-undo"></i> Reverter
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php echo $this->endSection() ?>

<script>
function suCheck() {
    var channel = document.getElementById('su-channel').value;
    $.post('<?php _e(base_url('plugins/system_updater_check')) ?>', { channel: channel }, function(resp) {
        if (resp.status === 'success') {
            if (resp.data && resp.data.update_available) {
                toastr.success(resp.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                toastr.info(resp.message);
            }
        } else {
            toastr.error(resp.message || 'Erro ao verificar');
        }
    }, 'json');
}

function suApply(version) {
    if (!confirm('Atualizar o sistema para v' + version + '?\n\nUm backup será criado automaticamente antes da atualização.\nO sistema pode ficar indisponível por alguns segundos.')) return;

    var channel = document.getElementById('su-channel').value;
    $.post('<?php _e(base_url('plugins/system_updater_apply')) ?>', {
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

    $.post('<?php _e(base_url('plugins/system_updater_rollback')) ?>', { update_id: id }, function(resp) {
        if (resp.status === 'success') {
            toastr.success(resp.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            toastr.error(resp.message || 'Erro no rollback');
        }
    }, 'json');
}
</script>
