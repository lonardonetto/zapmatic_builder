<?php
_e($this->extend('Backend\Stackmin\Views\index'), false);
?>

<?php echo $this->section('content') ?>

<div class="main-wrapper flex-grow-1 n-scroll">
    <!-- Overlay de progresso -->
    <div id="su-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); z-index:99999; align-items:center; justify-content:center; flex-direction:column; color:#fff; padding:20px;">
        <div class="text-center" style="max-width:500px; width:100%;">
            <div class="fw-bold" style="font-size:1.2rem; margin-bottom:20px;" id="su-progress-title">
                <i class="fad fa-cog fa-spin"></i> Atualizando o sistema...
            </div>
            <div class="progress" style="height:25px; background:rgba(255,255,255,0.15); border-radius:12px; overflow:hidden;">
                <div id="su-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%; background:linear-gradient(90deg,#10b981,#3b82f6); transition:width 0.5s ease;">
                    <span id="su-progress-pct" style="font-weight:bold;">0%</span>
                </div>
            </div>
            <div id="su-progress-msg" class="text-light small mt-3" style="opacity:0.9;">Iniciando...</div>
            <div id="su-overlay-note" class="text-light small mt-2" style="opacity:0.6;">NÃO feche esta página</div>
        </div>
    </div>

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
            <button class="btn btn-success b-r-10 su-update-btn" onclick="suApply('<?php echo htmlspecialchars($latest_stable ?? '') ?>')">
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

<script>
function suCheck() {
    var channel = document.getElementById('su-channel').value;
    $.post('<?php _e(base_url('plugins/system_updater_check')) ?>', {
        channel: channel,
        '<?php echo csrf_token() ?>': '<?php echo csrf_hash() ?>'
    }, function(resp) {
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

var suPollTimer = null;
var suUpdateId = 0;

var suPollTimer = null;
var suUpdateId = 0;

function suApply(version) {
    if (!confirm('Atualizar o sistema para v' + version + '?\n\nUm backup será criado automaticamente antes da atualização.\nO sistema pode ficar indisponível por alguns segundos.')) return;

    var channel = document.getElementById('su-channel').value;

    showOverlay();
    setProgressUI(5, 'Iniciando atualização...');
    var title = document.getElementById('su-progress-title');
    if (title) title.innerHTML = '<i class="fad fa-cog fa-spin"></i> Atualizando o sistema...';

    var btn = document.querySelector('.su-update-btn');
    var btnHtml = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fad fa-spinner-third fa-spin"></i> Atualizando...'; }

    $.post('<?php _e(base_url("plugins/system_updater_apply")) ?>', {
        target_version: version,
        channel: channel,
        '<?php echo csrf_token() ?>': '<?php echo csrf_hash() ?>'
    }, function(resp) {
        if (resp.status === 'success') {
            suUpdateId = resp.update_id || 0;
            // Polling muito agressivo: a cada 1 segundo
            suPollTimer = setInterval(suPoll, 1000);
            suPoll(); 
        } else {
            toastr.error(resp.message || 'Erro ao iniciar');
            hideOverlay();
            if (btn) { btn.disabled = false; btn.innerHTML = btnHtml; }
        }
    }).fail(function() {
        toastr.error('Falha de rede ao iniciar');
        hideOverlay();
        if (btn) { btn.disabled = false; btn.innerHTML = btnHtml; }
    });
}

function suPoll() {
    if (!suUpdateId) return;

    $.post('<?php _e(base_url("plugins/system_updater_progress")) ?>', {
        update_id: suUpdateId,
        '<?php echo csrf_token() ?>': '<?php echo csrf_hash() ?>'
    }, function(resp) {
        if (resp.status !== 'success' || !resp.progress) return;
        var p = resp.progress;
        
        setProgressUI(p.percent, p.message || '');
        var bar = document.getElementById('su-progress-bar');
        if (bar) bar.classList.add('progress-bar-animated');

        if (p.done) {
            clearInterval(suPollTimer);
            var title = document.getElementById('su-progress-title');
            var note = document.getElementById('su-overlay-note');
            
            if (p.percent < 0 || p.stage === 'error') {
                if (title) title.innerHTML = '<i class="fad fa-exclamation-triangle text-warning"></i> Falha na atualização';
                if (note) note.textContent = 'Você pode fechar esta página';
                toastr.error(p.message || 'Erro crítico');
                setTimeout(hideOverlay, 4000);
            } else {
                if (title) title.innerHTML = '<i class="fad fa-check-circle text-success"></i> Atualização concluída!';
                if (note) note.textContent = 'Redirecionando automaticamente...';
                toastr.success(p.message || 'Sistema atualizado!');
                setTimeout(function(){ location.reload(); }, 2500);
            }
        }
    }).fail(function() {
        // Durante o rsync os arquivos PHP sao substituidos, o poll pode falhar.
        // Nao faz nada: o setInterval continua tentando e recupera sozinho.
    });
}
function showOverlay() {
    var overlay = document.getElementById('su-overlay');
    if (overlay) overlay.style.display = 'flex';
}

function hideOverlay() {
    var overlay = document.getElementById('su-overlay');
    if (overlay) overlay.style.display = 'none';
}

function setProgressUI(percent, message) {
    percent = Math.max(0, parseInt(percent) || 0);
    var bar = document.getElementById('su-progress-bar');
    var pct = document.getElementById('su-progress-pct');
    var msg = document.getElementById('su-progress-msg');
    if (bar) bar.style.width = percent + '%';
    if (pct) pct.textContent = percent + '%';
    if (msg) msg.textContent = message || '';
}

function suRollback(id) {
    if (!confirm('Reverter para a versão anterior?\n\nO backup desta atualização será restaurado.')) return;

    $.post('<?php _e(base_url('plugins/system_updater_rollback')) ?>', {
        update_id: id,
        '<?php echo csrf_token() ?>': '<?php echo csrf_hash() ?>'
    }, function(resp) {
        if (resp.status === 'success') {
            toastr.success(resp.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            toastr.error(resp.message || 'Erro no rollback');
        }
    }, 'json');
}
</script>

<?php echo $this->endSection() ?>

