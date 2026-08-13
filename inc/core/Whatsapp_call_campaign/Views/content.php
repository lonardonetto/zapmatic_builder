<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-12 mb-4">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="<?php _ec($config['icon']) ?>" style="color:<?php _ec($config['color']) ?>"></i> Campanhas de Chamada</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#uploadAudioModal">
                        <i class="fad fa-music me-1"></i> Upload Áudio
                    </button>
                    <a href="<?php _e(base_url('whatsapp_call_campaign/create')) ?>" class="btn btn-sm btn-success rounded-pill">
                        <i class="fad fa-plus me-1"></i> Nova Campanha
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($campaigns)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fad fa-phone-volume fa-3x mb-3 d-block opacity-50"></i>
                    <p>Nenhuma campanha de chamada criada ainda.</p>
                    <p class="small">Crie uma campanha para ligar automaticamente para seus leads e tocar uma mensagem de áudio.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Campanha</th>
                                <th>Status</th>
                                <th>Progresso</th>
                                <th>Leads</th>
                                <th>Agendamento</th>
                                <th>Criado</th>
                                <th class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $c): ?>
                            <?php
                                $answered = (int)$c->calls_answered;
                                $failed_total = (int)$c->calls_no_answer + (int)$c->calls_busy + (int)$c->calls_failed;
                                $made = (int)$c->calls_made;
                                $total = (int)$c->total_leads;
                                $pct = $total > 0 ? round(($made / $total) * 100) : 0;
                                $schedule_label = '';
                                if (!empty($c->schedule_time) || !empty($c->schedule_weekdays) || !empty($c->skip_team_holidays)) {
                                    include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Helpers/Whatsapp_call_campaign_helper.php';
                                    $schedule_label = call_schedule_label($c);
                                }
                            ?>
                            <tr data-campaign-id="<?php echo (int)$c->id ?>" data-status="<?php echo htmlspecialchars($c->status) ?>">
                                <td class="ps-3">
                                    <strong><?php echo htmlspecialchars($c->name) ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($c->instance_id) ?></small>
                                </td>
                                <td>
                                    <?php
                                    $badgeMap = ['draft'=>'secondary','scheduled'=>'info','running'=>'success','paused'=>'warning','completed'=>'primary','failed'=>'danger'];
                                    $badge = $badgeMap[$c->status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge ?>"><?php echo htmlspecialchars($c->status) ?></span>
                                </td>
                                <td>
                                    <?php if ($c->status === 'running'): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:6px;width:80px;">
                                            <div class="progress-bar bg-success" style="width:<?php echo $pct ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $made ?>/<?php echo $total ?></small>
                                        <div class="spinner-border spinner-border-sm text-success" role="status" style="width:12px;height:12px;"></div>
                                    </div>
                                    <?php elseif ($c->status === 'completed'): ?>
                                    <span class="text-success fw-bold"><?php echo $answered ?></span> / <span class="text-danger"><?php echo $failed_total ?></span>
                                    <small class="text-muted d-block"><?php echo $pct ?>% concluído</small>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark"><?php echo $total ?></span>
                                    <?php if (!empty($schedule_label)): ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($schedule_label)): ?>
                                    <small class="text-primary"><i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($schedule_label) ?></small>
                                    <?php else: ?>
                                    <small class="text-muted">Sem restrição</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?php echo htmlspecialchars($c->created_at) ?></td>
                                <td class="text-end pe-3">
                                    <?php if ($c->status === 'draft' || $c->status === 'paused'): ?>
                                    <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/start')) ?>" style="display:inline">
                                        <input type="hidden" name="campaign_id" value="<?php echo (int)$c->id ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Iniciar"><i class="fad fa-play"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($c->status === 'running'): ?>
                                    <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/pause')) ?>" style="display:inline">
                                        <input type="hidden" name="campaign_id" value="<?php echo (int)$c->id ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Pausar"><i class="fad fa-pause"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($c->status === 'completed' || $c->status === 'paused'): ?>
                                    <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/repeat')) ?>" style="display:inline" onsubmit="return confirm('Repetir esta campanha? Todos os leads serão resetados.')">
                                        <input type="hidden" name="campaign_id" value="<?php echo (int)$c->id ?>">
                                        <button type="submit" class="btn btn-sm btn-info" title="Repetir campanha"><i class="fad fa-redo"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($c->status === 'draft'): ?>
                                    <a href="<?php _e(base_url('whatsapp_call_campaign/edit/' . $c->id)) ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="fad fa-edit"></i></a>
                                    <?php endif; ?>
                                    <a href="<?php _e(base_url('whatsapp_call_campaign/results/' . $c->id)) ?>" class="btn btn-sm btn-outline-primary" title="Resultados"><i class="fad fa-chart-bar"></i></a>
                                    <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/delete')) ?>" style="display:inline" onsubmit="return confirm('Excluir esta campanha?')">
                                        <input type="hidden" name="campaign_id" value="<?php echo (int)$c->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="fad fa-trash"></i></button>
                                    </form>
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

<!-- Audio Library -->
<?php if (!empty($audios)): ?>
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-12 mb-4">
            <div class="card-header border-0">
                <h6 class="mb-0"><i class="fad fa-music me-2 text-primary"></i> Biblioteca de Áudios</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Nome</th>
                                <th>Formato</th>
                                <th>Duração</th>
                                <th>Tamanho</th>
                                <th>Criado</th>
                                <th class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audios as $a): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle p-0" style="width:28px;height:28px;" onclick="playAudio(this, '<?php _e(base_url('call_audio_stream.php?id=' . $a->id)) ?>')" title="Ouvir"><i class="fas fa-play" style="font-size:10px;"></i></button>
                                        <div>
                                            <div><?php echo htmlspecialchars($a->name) ?></div>
                                            <div id="audio-player-<?php echo (int)$a->id ?>" class="d-none mt-1">
                                                <audio controls preload="none" style="width:200px;height:28px;">
                                                    <source src="<?php _e(base_url('call_audio_stream.php?id=' . $a->id)) ?>" type="audio/<?php echo $a->format === 'mp3' ? 'mpeg' : $a->format ?>">
                                                </audio>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark"><?php echo strtoupper(htmlspecialchars($a->format)) ?></span></td>
                                <td><?php echo $a->duration_seconds > 0 ? gmdate("i:s", $a->duration_seconds) : '—' ?></td>
                                <td><?php echo $a->file_size_bytes > 0 ? round($a->file_size_bytes / 1024 / 1024, 1) . ' MB' : '—' ?></td>
                                <td class="text-muted small"><?php echo htmlspecialchars($a->created_at) ?></td>
                                <td class="text-end pe-3">
                                    <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/delete_audio')) ?>" style="display:inline" onsubmit="return confirm('Excluir este áudio?')">
                                        <input type="hidden" name="audio_id" value="<?php echo (int)$a->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Upload Áudio -->
<div class="modal fade" id="uploadAudioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fad fa-music me-2"></i>Upload de Áudio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/upload_audio')) ?>" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do áudio</label>
                        <input type="text" name="audio_name" class="form-control" required placeholder="Ex: Promoção Agosto">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Arquivo (MP3, WAV, Opus)</label>
                        <input type="file" name="audio_file" class="form-control" accept=".mp3,.wav,.opus,.ogg,.oga,.flac,.aac,.m4a" required>
                        <small class="text-muted">MP3, WAV, Opus, OGG, FLAC, AAC, M4A. Máximo 10MB. Formatos não-MP3 serão convertidos automaticamente.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fad fa-upload me-1"></i>Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
$(document).ready(function() {
    // Toggle lead mode
    $('input[name="lead_mode"]').on('change', function() {
        var mode = $(this).val();
        $('#leadModeSelectPanel').toggleClass('d-none', mode !== 'selected_contacts');
        $('#leadModeManualPanel').toggleClass('d-none', mode !== 'manual');
    });

    // DateTimePicker for start time
    var $timePost = $('#call_time_post');
    if ($timePost.length && typeof $timePost.datetimepicker === 'function') {
        $timePost.datetimepicker({
            controlType: 'select',
            oneLine: true,
            dateFormat: 'dd/mm/yy',
            timeFormat: 'HH:mm',
            closeText: 'Fechar',
            prevText: 'Anterior',
            nextText: 'Próximo',
            currentText: 'Hoje',
            monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
            monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
            dayNames: ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'],
            dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'],
            dayNamesMin: ['D','S','T','Q','Q','S','S'],
            weekHeader: 'Sm',
            firstDay: 0,
            isRTL: false,
            showMonthAfterYear: false,
            yearSuffix: '',
            timeOnlyTitle: 'Escolha o horário',
            timeText: 'Horário',
            hourText: 'Hora',
            minuteText: 'Minuto',
            secondText: 'Segundo'
        });
    }

    // Select2 for schedule_time
    var $scheduleTime = $('.call-schedule-time');
    if ($scheduleTime.length && typeof $scheduleTime.select2 === 'function') {
        $scheduleTime.select2({
            placeholder: 'Selecione os horários permitidos',
            allowClear: true
        });
    }

    // Schedule presets (daytime/nighttime/odd/even)
    $(document).on('click', '.call-schedule-preset', function() {
        var type = $(this).data('time');
        var sel = document.querySelector('.call-schedule-time');
        if (!sel) return;
        for (var i = 0; i < sel.options.length; i++) {
            var val = parseInt(sel.options[i].value);
            var match = false;
            if (type === 'daytime') match = (val >= 8 && val <= 20);
            else if (type === 'nighttime') match = (val < 8 || val > 20);
            else if (type === 'odd') match = (val % 2 === 1);
            else if (type === 'even') match = (val % 2 === 0);
            sel.options[i].selected = match;
        }
        if ($scheduleTime.length && typeof $scheduleTime.select2 === 'function') {
            $scheduleTime.trigger('change.select2');
        }
        updateCallScheduleSummary();
    });

    // Weekday presets
    $(document).on('click', '.call-weekday-preset', function() {
        var vals = String($(this).data('weekdays') || '').split(',');
        $('.call-weekday-input').each(function() {
            this.checked = vals.includes(this.value);
        });
        updateCallScheduleSummary();
    });
    $(document).on('click', '.call-weekday-clear', function() {
        $('.call-weekday-input').prop('checked', false);
        updateCallScheduleSummary();
    });

    // Update summary on any schedule change
    $(document).on('change', '.call-weekday-input, .call-schedule-time, #call_skip_holidays', function() {
        updateCallScheduleSummary();
    });
    if ($scheduleTime.length) {
        $scheduleTime.on('change', updateCallScheduleSummary);
    }
});

function updateCallScheduleSummary() {
    var hours = [];
    var sel = document.querySelector('.call-schedule-time');
    if (sel) {
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].selected) hours.push(sel.options[i].value);
        }
    }
    var weekdays = [];
    document.querySelectorAll('.call-weekday-input:checked').forEach(function(cb) {
        weekdays.push(cb.value);
    });
    var skip = document.getElementById('call_skip_holidays');
    var skipHolidays = skip ? skip.checked : false;

    var weekdayLabels = {1:'Seg',2:'Ter',3:'Qua',4:'Qui',5:'Sex',6:'Sáb',7:'Dom'};
    var parts = [];
    if (weekdays.length === 7 || weekdays.length === 0) parts.push('Todos os dias');
    else if (weekdays.join(',') === '1,2,3,4,5') parts.push('Seg-Sex');
    else if (weekdays.join(',') === '6,7') parts.push('Sáb-Dom');
    else parts.push(weekdays.map(function(w) { return weekdayLabels[w] || w; }).join(', '));

    if (hours.length > 0) {
        parts.push(hours.map(function(h) { return h.toString().padStart(2, '0') + ':00'; }).join(', '));
    }
    if (skipHolidays) parts.push('Ignora feriados');

    var summary = document.getElementById('call-schedule-summary');
    if (summary) {
        summary.textContent = parts.length > 0 ? parts.join(' | ') : 'Sem restrição. A campanha poderá rodar a qualquer momento.';
    }
}

// Auto-refresh running campaigns
$(document).ready(function() {
    var pollTimer = null;
    function hasRunning() {
        return document.querySelector('tr[data-status="running"]') !== null;
    }
    function refreshStatus() {
        if (!hasRunning()) { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } return; }
        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                doc.querySelectorAll('tbody tr[data-campaign-id]').forEach(function(newRow) {
                    var id = newRow.getAttribute('data-campaign-id');
                    var currentRow = document.querySelector('tr[data-campaign-id="'+id+'"]');
                    if (currentRow) {
                        var ns = newRow.getAttribute('data-status');
                        var os = currentRow.getAttribute('data-status');
                        if (ns !== os) currentRow.replaceWith(newRow);
                        else if (ns === 'running') {
                            var np = newRow.querySelector('.progress-bar');
                            var op = currentRow.querySelector('.progress-bar');
                            if (np && op) op.style.width = np.style.width;
                        }
                    }
                });
                if (!hasRunning() && pollTimer) { clearInterval(pollTimer); pollTimer = null; }
            }).catch(function() {});
    }
    if (hasRunning()) pollTimer = setInterval(refreshStatus, 5000);
    var observer = new MutationObserver(function() {
        if (hasRunning() && !pollTimer) pollTimer = setInterval(refreshStatus, 5000);
    });
    var tbody = document.querySelector('tbody');
    if (tbody) observer.observe(tbody, { childList: true, subtree: true });
});

function playAudio(btn, url) {
    // Find or create audio element
    var row = btn.closest('tr');
    var playerId = btn.closest('td').querySelector('[id^="audio-player-"]');
    if (playerId) {
        playerId.classList.toggle('d-none');
        var audio = playerId.querySelector('audio');
        if (audio && !playerId.classList.contains('d-none')) {
            audio.play().catch(function() {});
            btn.innerHTML = '<i class="fas fa-pause" style="font-size:10px;"></i>';
            audio.onended = function() { btn.innerHTML = '<i class="fas fa-play" style="font-size:10px;"></i>'; };
        } else if (audio) {
            audio.pause();
            btn.innerHTML = '<i class="fas fa-play" style="font-size:10px;"></i>';
        }
    }
}
</script>
