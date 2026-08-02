<div class="container my-5">
    <a href="<?php _e(base_url('whatsapp_call_campaign')) ?>" class="btn btn-dark btn-sm mb-4">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>

    <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/update')) ?>">
        <input type="hidden" name="campaign_id" value="<?php echo (int)$campaign->id ?>">
        <div class="card b-r-6">
            <div class="card-header">
                <h3 class="card-title"><i class="fad fa-edit me-2"></i> Editar: <?php echo htmlspecialchars($campaign->name) ?></h3>
            </div>
            <div class="card-body position-relative">

                <!-- 1. Seleção de contas -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Instâncias WhatsApp</label>
                    <div class="border rounded p-2" style="max-height:200px; overflow-y:auto;">
                        <?php
                        $currentIds = [];
                        if (!empty($campaign->instance_ids)) {
                            $currentIds = json_decode($campaign->instance_ids, true) ?: [];
                        } elseif (!empty($campaign->instance_id)) {
                            $currentIds = [$campaign->instance_id];
                        }
                        ?>
                        <?php foreach ($accounts as $a): ?>
                        <div class="d-flex align-items-center py-2 px-2 border-bottom">
                            <?php $avatar = get_file_url($a->avatar); ?>
                            <?php if (!empty($avatar)): ?>
                            <img src="<?php _ec($avatar) ?>" style="width:32px;height:32px;border-radius:50%;margin-right:12px;object-fit:cover;">
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?php echo htmlspecialchars($a->name ?: $a->token) ?></div>
                                <small class="text-muted"><?php echo $a->status == 1 ? '🟢 Online' : '🔴 Offline' ?></small>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="parallel_instances[]" value="<?php _ec($a->token) ?>" id="epinst_<?php _ec($a->id) ?>" <?php echo (in_array($a->token, $currentIds) || $a->token == $campaign->instance_id) ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. Nome -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Nome da campanha</label>
                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($campaign->name) ?>">
                </div>

                <!-- 3. Leads -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Leads (<span id="leads-count"><?php echo count($leads) ?></span>)</label>

                    <div id="leads-list" class="border rounded p-2 mb-3" style="min-height:50px; max-height:200px; overflow-y:auto;">
                        <?php foreach ($leads as $lead): ?>
                        <span class="badge bg-light text-dark me-1 mb-1 lead-badge" data-phone="<?php echo htmlspecialchars($lead->phone) ?>">
                            <?php echo htmlspecialchars($lead->phone) ?> <?php echo htmlspecialchars($lead->name) ?>
                            <a href="javascript:void(0);" onclick="removeLead(this)" class="text-danger ms-1" title="Remover">×</a>
                            <input type="hidden" name="keep_phones[]" value="<?php echo htmlspecialchars($lead->phone) ?>">
                        </span>
                        <?php endforeach; ?>
                        <?php if (empty($leads)): ?>
                        <p class="text-muted small mb-0" id="leads-empty">Nenhum lead.</p>
                        <?php endif; ?>
                    </div>

                    <div class="border rounded p-3">
                        <h6 class="fw-bold mb-3"><i class="fad fa-user-plus me-2 text-success"></i>Adicionar leads</h6>
                        <div class="d-flex gap-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="add_mode" id="addModeContacts" value="contacts" checked onchange="toggleAddMode()">
                                <label class="form-check-label" for="addModeContacts">Contatos (<?php echo count($contacts) ?>)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="add_mode" id="addModeManual" value="manual" onchange="toggleAddMode()">
                                <label class="form-check-label" for="addModeManual">Colar números</label>
                            </div>
                        </div>
                        <div id="addContactsPanel">
                            <div class="border rounded p-2" style="max-height:200px; overflow-y:auto;">
                                <?php foreach ($contacts as $c): ?>
                                <?php if ($c->phone_count > 0): ?>
                                <div class="form-check py-1">
                                    <input class="form-check-input" type="checkbox" name="add_contacts[]" value="<?php echo (int)$c->id ?>" id="edit_contact_<?php echo (int)$c->id ?>">
                                    <label class="form-check-label" for="edit_contact_<?php echo (int)$c->id ?>">
                                        <?php echo htmlspecialchars($c->name ?: '(sem nome)') ?>
                                        <span class="badge bg-light text-muted"><?php echo $c->phone_count ?> tel</span>
                                    </label>
                                </div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div id="addManualPanel" class="d-none">
                            <textarea name="add_phones" class="form-control" rows="4" placeholder="5511999999999&#10;5521888888888"></textarea>
                        </div>
                    </div>
                </div>

                <!-- 4. Modo de chamada + Delay -->
                <div class="mb-3">
                    <div class="card border b-r-6">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Modo de chamada</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="call_mode" id="emodeRotation" value="rotation" <?php echo ($campaign->call_mode ?? 'rotation') === 'rotation' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="emodeRotation"><i class="fad fa-sync-alt me-1"></i>Rotação</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="call_mode" id="emodeParallel" value="parallel" <?php echo ($campaign->call_mode ?? '') === 'parallel' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="emodeParallel"><i class="fad fa-layer-group me-1"></i>Paralelo</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Áudio</label>
                                    <div id="current-audio-section">
                                        <?php $currentAudio = null; if (!empty($campaign->audio_id)) { foreach ($audios as $a) { if ((int)$a->id === (int)$campaign->audio_id) { $currentAudio = $a; break; } } } ?>
                                        <?php if ($currentAudio): ?>
                                        <div class="border rounded p-2 mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fad fa-file-audio text-primary"></i>
                                                <div class="flex-grow-1">
                                                    <strong><?php echo htmlspecialchars($currentAudio->name) ?></strong>
                                                    <small class="text-muted d-block"><?php echo strtoupper($currentAudio->format) ?> · <?php echo gmdate("i:s", $currentAudio->duration_seconds) ?></small>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAudio()" title="Remover"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="mt-2">
                                                <audio controls preload="none" style="width:100%;height:32px;">
                                                    <source src="<?php _e(base_url('call_audio_stream.php?id=' . $currentAudio->id)) ?>" type="audio/<?php echo $currentAudio->format === 'mp3' ? 'mpeg' : $currentAudio->format ?>">
                                                </audio>
                                            </div>
                                        </div>
                                        <input type="hidden" name="audio_id" id="currentAudioId" value="<?php echo (int)$campaign->audio_id ?>">
                                        <?php else: ?>
                                        <p class="text-muted small mb-2">Nenhum áudio.</p>
                                        <input type="hidden" name="audio_id" id="currentAudioId" value="">
                                        <?php endif; ?>
                                    </div>
                                    <select id="audioSelect" class="form-select mb-2" onchange="changeAudio()">
                                        <option value="">Trocar áudio...</option>
                                        <?php foreach ($audios as $a): ?>
                                        <?php if ((int)$a->id !== (int)($campaign->audio_id ?? 0)): ?>
                                        <option value="<?php _ec($a->id) ?>"><?php _ec($a->name) ?> (<?php _ec(gmdate('i:s', $a->duration_seconds)) ?>)</option>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadAudioEditModal">
                                        <i class="fas fa-upload me-1"></i>Upload novo áudio
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Delay mín (s)</label>
                                    <select class="form-select" name="delay_min">
                                        <?php for ($i = 5; $i <= 120; $i += ($i < 30 ? 5 : ($i < 60 ? 10 : 30))): ?>
                                        <option value="<?php echo $i ?>" <?php echo $i == ($campaign->delay_min ?? 10) ? 'selected' : '' ?>><?php echo $i ?>s</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Delay máx (s)</label>
                                    <select class="form-select" name="delay_max">
                                        <?php for ($i = 10; $i <= 300; $i += ($i < 60 ? 10 : 30)): ?>
                                        <option value="<?php echo $i ?>" <?php echo $i == ($campaign->delay_max ?? 60) ? 'selected' : '' ?>><?php echo $i ?>s</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Timeout toque (s)</label>
                                    <select class="form-select" name="timeout_ring">
                                        <?php for ($i = 10; $i <= 120; $i += 10): ?>
                                        <option value="<?php echo $i ?>" <?php echo $i == ($campaign->timeout_ring ?? 30) ? 'selected' : '' ?>><?php echo $i ?>s</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Agendar início -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Agendar início</label>
                    <input type="text" class="form-control datetime" id="call_time_post_edit" name="time_post" autocomplete="off" placeholder="dd/mm/aaaa HH:mm" value="<?php echo !empty($campaign->schedule_start) ? date('d/m/Y H:i', strtotime($campaign->schedule_start)) : '' ?>">
                    <div class="fs-12 text-gray-600 mt-1">Deixe vazio para iniciar imediatamente.</div>
                </div>

                <!-- 6. Janela de execução -->
                <div class="mb-3">
                    <div class="card border b-r-6 bg-light-info">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        <h4 class="mb-0"><?php _e("Janela de execução")?></h4>
                                        <span class="badge badge-light-info"><?php _e("Dias, horários e feriados")?></span>
                                    </div>
                                    <p class="text-gray-700 mb-0"><?php _e("Defina quando a campanha pode rodar.")?></p>
                                </div>
                            </div>
                            <div class="row g-4">
                                <div class="col-xl-6">
                                    <label class="form-label d-block fw-bold"><?php _e("Horários permitidos")?></label>
                                    <?php $schedHours = json_decode($campaign->schedule_time ?? '[]', true) ?? []; ?>
                                    <ul class="d-flex flex-wrap seclect-shedule-time gap-3 mb-3" style="list-style:none;padding:0;">
                                        <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="daytime"><?php _e("Daytime")?></a></li>
                                        <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="nighttime"><?php _e("Nighttime")?></a></li>
                                        <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="odd"><?php _e("Odd")?></a></li>
                                        <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="even"><?php _e("Even")?></a></li>
                                    </ul>
                                    <select class="form-select call-schedule-time mb-2" data-control="select2" data-placeholder="<?php _e("Selecione os horários permitidos")?>" multiple name="schedule_time[]">
                                        <?php for ($i = 0; $i <= 23; $i++): ?>
                                        <option value="<?php echo $i ?>" <?php echo in_array((string)$i, $schedHours) ? 'selected' : '' ?>><?php echo str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-xl-6">
                                    <label class="form-label d-block fw-bold"><?php _e("Dias permitidos")?></label>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="1,2,3,4,5,6,7"><?php _e("Todos")?></button>
                                        <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="1,2,3,4,5"><?php _e("Dias úteis")?></button>
                                        <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="6,7"><?php _e("Fim de semana")?></button>
                                        <button type="button" class="btn btn-light btn-sm call-weekday-clear"><?php _e("Limpar")?></button>
                                    </div>
                                    <?php $schedDays = json_decode($campaign->schedule_weekdays ?? '[]', true) ?? []; ?>
                                    <?php $weekday_options = call_schedule_weekday_options(); ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($weekday_options as $wv => $wm): ?>
                                        <input type="checkbox" class="btn-check call-weekday-input" name="schedule_weekdays[]" id="edit_weekday_<?php echo $wv ?>" value="<?php echo $wv ?>" autocomplete="off" <?php echo in_array((string)$wv, $schedDays) ? 'checked' : '' ?>>
                                        <label class="btn btn-sm btn-outline btn-outline-primary" for="edit_weekday_<?php echo $wv ?>"><?php echo $wm['short'] ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 p-3 rounded border border-primary border-dashed bg-white">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" name="skip_team_holidays" value="1" <?php echo !empty($campaign->skip_team_holidays) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold"><?php _e("Ignorar feriados da equipe")?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <label class="form-label fw-bold"><?php _e("Timezone")?></label>
                                    <select name="timezone" class="form-select">
                                        <option value="">Padrão</option>
                                        <?php foreach (['America/Sao_Paulo','America/Manaus','America/Belem','America/Fortaleza','America/Bahia','America/Recife','America/Noronha'] as $tz): ?>
                                        <option value="<?php echo $tz ?>" <?php echo ($campaign->timezone ?? '') === $tz ? 'selected' : '' ?>><?php echo $tz ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-light-primary border border-primary border-dashed mb-0">
                                        <div class="fw-bold text-gray-800 mb-1"><?php _e("Resumo da janela")?></div>
                                        <div class="text-gray-700" id="call-schedule-summary"><?php _e("Sem restrição.")?></div>
                                    </div>
                                </div>
                            </ Upload Audio Modal (edit) -->
<div class="modal fade" id="uploadAudioEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fad fa-music me-2"></i>Upload de Áudio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/upload_audio')) ?>" enctype="multipart/form-data">
                <input type="hidden" name="redirect_back" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do áudio</label>
                        <input type="text" name="audio_name" class="form-control" required placeholder="Ex: Promoção Agosto">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Arquivo</label>
                        <input type="file" name="audio_file" class="form-control" accept=".mp3,.wav,.opus,.ogg,.oga,.flac,.aac,.m4a" required>
                        <small class="text-muted">MP3, WAV, Opus, OGG, FLAC, AAC, M4A. Máx 10MB.</small>
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
    // Remove lead
    window.removeLead = function(el) {
        el.closest('.lead-badge').remove();
        updateLeadsCount();
    };
    function updateLeadsCount() {
        var count = document.querySelectorAll('.lead-badge').length;
        var el = document.getElementById('leads-count');
        if (el) el.textContent = count;
        var empty = document.getElementById('leads-empty');
        if (empty) empty.style.display = count > 0 ? 'none' : '';
    }

    // Audio management
    window.removeAudio = function() {
        document.getElementById('currentAudioId').value = '';
        var section = document.getElementById('current-audio-section');
        section.innerHTML = '<p class="text-muted small mb-2">Nenhum áudio.</p><input type="hidden" name="audio_id" id="currentAudioId" value="">';
    };
    window.changeAudio = function() {
        var sel = document.getElementById('audioSelect');
        if (sel.value) {
            document.getElementById('currentAudioId').value = sel.value;
            var section = document.getElementById('current-audio-section');
            section.innerHTML = '<div class="d-flex align-items-center gap-2 border rounded p-2 mb-2 bg-light"><i class="fad fa-file-audio text-primary"></i><div class="flex-grow-1"><strong>' + sel.options[sel.selectedIndex].text + '</strong></div><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAudio()"><i class="fas fa-times"></i></button></div><input type="hidden" name="audio_id" id="currentAudioId" value="' + sel.value + '">';
            sel.value = '';
        }
    };

    // Toggle add mode
    function toggleAddMode() {
        var mode = $('input[name="add_mode"]:checked').val();
        $('#addContactsPanel').toggleClass('d-none', mode !== 'contacts');
        $('#addManualPanel').toggleClass('d-none', mode !== 'manual');
    }
    window.toggleAddMode = toggleAddMode;
    $('input[name="add_mode"]').on('change', toggleAddMode);

    // DateTimePicker
    var $tp = $('#call_time_post_edit');
    if ($tp.length && typeof $tp.datetimepicker === 'function') {
        $tp.datetimepicker({
            controlType: 'select', oneLine: true, dateFormat: 'dd/mm/yy', timeFormat: 'HH:mm',
            closeText: 'Fechar', prevText: 'Anterior', nextText: 'Próximo', currentText: 'Hoje',
            monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
            dayNames: ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'],
            dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'],
            dayNamesMin: ['D','S','T','Q','Q','S','S']
        });
    }

    // Schedule presets
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
        var $st = $('.call-schedule-time');
        if ($st.length) $st.trigger('change.select2');
        updateScheduleSummary();
    });
    $(document).on('click', '.call-weekday-preset', function() {
        var vals = String($(this).data('weekdays') || '').split(',');
        $('.call-weekday-input').each(function() { this.checked = vals.includes(this.value); });
        updateScheduleSummary();
    });
    $(document).on('click', '.call-weekday-clear', function() {
        $('.call-weekday-input').prop('checked', false);
        updateScheduleSummary();
    });
    $(document).on('change', '.call-weekday-input, .call-schedule-time, input[name="skip_team_holidays"]', updateScheduleSummary);
    updateScheduleSummary();
});

function updateScheduleSummary() {
    var hours = [];
    var sel = document.querySelector('.call-schedule-time');
    if (sel) { for (var i = 0; i < sel.options.length; i++) { if (sel.options[i].selected) hours.push(sel.options[i].value); } }
    var weekdays = [];
    document.querySelectorAll('.call-weekday-input:checked').forEach(function(cb) { weekdays.push(cb.value); });
    var skip = document.querySelector('input[name="skip_team_holidays"]');
    var skipH = skip ? skip.checked : false;
    var wl = {1:'Seg',2:'Ter',3:'Qua',4:'Qui',5:'Sex',6:'Sáb',7:'Dom'};
    var parts = [];
    if (weekdays.length === 7 || weekdays.length === 0) parts.push('Todos os dias');
    else if (weekdays.join(',') === '1,2,3,4,5') parts.push('Seg-Sex');
    else if (weekdays.join(',') === '6,7') parts.push('Sáb-Dom');
    else parts.push(weekdays.map(function(w) { return wl[w] || w; }).join(', '));
    if (hours.length > 0) parts.push(hours.map(function(h) { return h.padStart(2,'0')+':00'; }).join(', '));
    if (skipH) parts.push('Ignora feriados');
    var summary = document.getElementById('call-schedule-summary');
    if (summary) summary.textContent = parts.length > 0 ? parts.join(' | ') : 'Sem restrição.';
}
</script>
