<?php
$f = '/www/wwwroot/app_zapmatic_app/inc/core/Plugins/Views/system_update.php';
$c = file_get_contents($f);

$start = strpos($c, "function suApply");
$end = strpos($c, "function showOverlay");
if ($start !== false && $end !== false) {
    $js = <<<'JS'
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
    });
}

JS;
    $c = substr($c, 0, $start) . $js . substr($c, $end);
    file_put_contents($f, $c);
    echo "JS Atualizado.\n";
}
?>
