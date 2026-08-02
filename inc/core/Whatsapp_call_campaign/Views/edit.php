<div class="row">



    <div class="col-12">
        <a href="<?php _e(base_url('whatsapp_call_campaign')) ?>" class="btn btn-light btn-sm mb-3">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
        <div class="card border-0 shadow-sm rounded-12">
            <div class="card-header border-0">
                <h5 class="mb-0"><i class="fad fa-edit me-2"></i>Editar: <?php echo htmlspecialchars($campaign->name) ?></h5>
            </div>
            <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/update')) ?>">
                <input type="hidden" name="campaign_id" value="<?php echo (int)$campaign->id ?>">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nome</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($campaign->name) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Instância</label>
                            <select name="instance_id" class="form-select form-select-solid call-instance-select" data-control="select2" data-hide-search="true" data-placeholder="Selecione...">
                                <?php foreach ($accounts as $a): ?>
                                <option value="<?php _ec($a->token) ?>" data-img="<?php _ec(get_file_url($a->avatar)) ?>" <?php echo $a->token == $campaign->instance_id ? 'selected' : '' ?>><?php _ec($a->name ?: $a->token) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Áudio</label>
                            <?php
                            $currentAudio = null;
                            if (!empty($campaign->audio_id)) {
                                foreach ($audios as $a) {
                                    if ((int)$a->id === (int)$campaign->audio_id) { $currentAudio = $a; break; }
                                }
                            }
                            ?>
                            <div id="current-audio-section">
                                <?php if ($currentAudio): ?>
                                <div class="border rounded p-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fad fa-file-audio text-primary"></i>
                                        <div class="flex-grow-1">
                                            <strong><?php echo htmlspecialchars($currentAudio->name) ?></strong>
                                            <small class="text-muted d-block"><?php echo strtoupper($currentAudio->format) ?> · <?php echo gmdate("i:s", $currentAudio->duration_seconds) ?></small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAudio()" title="Remover áudio"><i class="fas fa-times"></i></button>
                                    </div>
                                    <div class="mt-2">
                                        <audio controls preload="none" style="width:100%;height:32px;">
                                            <source src="<?php _e(base_url('call_audio_stream.php?id=' . $currentAudio->id)) ?>" type="audio/<?php echo $currentAudio->format === 'mp3' ? 'mpeg' : $currentAudio->format ?>">
                                        </audio>
                                    </div>
                                </div>
                                <input type="hidden" name="audio_id" id="currentAudioId" value="<?php echo (int)$campaign->audio_id ?>">
                                <?php else: ?>
                                <p class="text-muted small mb-2" id="no-audio-msg">Nenhum áudio selecionado.</p>
                                <input type="hidden" name="audio_id" id="currentAudioId" value="">
                                <?php endif; ?>
                            </div>
                            <div id="change-audio-section">
                                <select id="audioSelect" class="form-select" onchange="changeAudio()">
                                    <option value="">Selecionar outro áudio...</option>
                                    <?php foreach ($audios as $a): ?>
                                    <?php if ((int)$a->id !== (int)($campaign->audio_id ?? 0)): ?>
                                    <option value="<?php _ec($a->id) ?>"><?php _ec($a->name) ?> (<?php _ec(gmdate('i:s', $a->duration_seconds)) ?>)</option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadAudioEditModal">
                                        <i class="fas fa-upload me-1"></i>Upload novo áudio
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Delay (s)</label>
                            <input type="number" name="delay_between_calls" class="form-control" value="<?php echo (int)$campaign->delay_between_calls ?>" min="5">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Timeout (s)</label>
                            <input type="number" name="timeout_ring" class="form-control" value="<?php echo (int)$campaign->timeout_ring ?>" min="10">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Leads (<span id="leads-count"><?php echo count($leads) ?></span>)</label>

                            <!-- Lista atual de leads (editável) -->
                            <div id="leads-list" class="border rounded p-2 mb-3" style="min-height:50px; max-height:200px; overflow-y:auto;">
                                <?php foreach ($leads as $lead): ?>
                                <span class="badge bg-light text-dark me-1 mb-1 lead-badge" data-phone="<?php echo htmlspecialchars($lead->phone) ?>">
                                    <?php echo htmlspecialchars($lead->phone) ?> <?php echo htmlspecialchars($lead->name) ?>
                                    <a href="javascript:void(0);" onclick="removeLead(this)" class="text-danger ms-1" title="Remover">×</a>
                                    <input type="hidden" name="keep_phones[]" value="<?php echo htmlspecialchars($lead->phone) ?>">
                                </span>
                                <?php endforeach; ?>
                                <?php if (empty($leads)): ?>
                                <p class="text-muted small mb-0" id="leads-empty">Nenhum lead adicionado.</p>
                                <?php endif; ?>
                            </div>

                            <!-- Adicionar leads -->
                            <div class="border rounded p-3">
                                <h6 class="fw-bold mb-3"><i class="fad fa-user-plus me-2 text-success"></i>Adicionar leads</h6>
                                <div class="d-flex gap-3 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="add_mode" id="addModeContacts" value="contacts" checked onchange="toggleAddMode()">
                                        <label class="form-check-label" for="addModeContacts">Contatos do sistema (<?php echo count($contacts) ?>)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="add_mode" id="addModeManual" value="manual" onchange="toggleAddMode()">
                                        <label class="form-check-label" for="addModeManual">Colar números</label>
                                    </div>
                                </div>

                                <div id="addContactsPanel">
                                    <div class="border rounded p-2 mb-2" style="max-height:200px; overflow-y:auto;">
                                        <?php if (!empty($contacts)): ?>
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
                                        <?php else: ?>
                                        <p class="text-muted small mb-0">Nenhum contato encontrado.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div id="addManualPanel" class="d-none">
                                    <textarea name="add_phones" class="form-control" rows="4" placeholder="5511999999999&#10;5521888888888"></textarea>
                                    <small class="text-muted">Um número por linha. Nono dígito será normalizado.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-4 rounded border border-primary border-dashed bg-white">
                                <h4 class="mb-3"><i class="fad fa-calendar-alt me-2 text-primary"></i>Janela de Execução</h4>
                                <div class="row g-4">
                                    <div class="col-xl-6">
                                        <label class="form-label d-block fw-bold">Agendar início</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fad fa-calendar-alt"></i></span>
                                            <input type="text" class="form-control datetime" id="call_time_post_edit" name="time_post" autocomplete="off" placeholder="dd/mm/aaaa HH:mm" value="<?php echo !empty($campaign->schedule_start) ? date('d/m/Y H:i', strtotime($campaign->schedule_start)) : '' ?>">
                                        </div>
                                        <p class="fs-12 text-gray-600 mb-0 mt-1">Deixe vazio para iniciar imediatamente.</p>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label fw-bold">Timezone</label>
                                        <select name="timezone" class="form-select">
                                            <option value="">Padrão</option>
                                            <?php foreach (['America/Sao_Paulo','America/Manaus','America/Belem','America/Fortaleza','America/Bahia','America/Recife','America/Noronha'] as $tz): ?>
                                            <option value="<?php echo $tz ?>" <?php echo ($campaign->timezone ?? '') === $tz ? 'selected' : '' ?>><?php echo $tz ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label d-block fw-bold">Horários permitidos</label>
                                        <?php $schedHours = json_decode($campaign->schedule_time ?? '[]', true) ?? []; ?>
                                        <select class="form-select call-schedule-time mb-2" data-control="select2" data-placeholder="Selecione os horários permitidos" multiple name="schedule_time[]">
                                            <?php for ($i = 0; $i <= 23; $i++): ?>
                                            <option value="<?php echo $i ?>" <?php echo in_array((string)$i, $schedHours) ? 'selected' : '' ?>><?php echo str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label d-block fw-bold">Dias permitidos</label>
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="1,2,3,4,5,6,7">Todos</button>
                                            <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="1,2,3,4,5">Dias úteis</button>
                                            <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="6,7">Fim de semana</button>
                                            <button type="button" class="btn btn-light btn-sm call-weekday-clear">Limpar</button>
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
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="skip_team_holidays" value="1" <?php echo !empty($campaign->skip_team_holidays) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Ignorar feriados da equipe</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success"><i class="fad fa-save me-1"></i>Salvar alterações</button>
                    <a href="<?php _e(base_url('whatsapp_call_campaign')) ?>" class="btn btn-light">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Remove lead from list
    window.removeLead = function(el) {
        el.closest('.lead-badge').remove();
        updateLeadsCount();
    };

    // Audio management
    window.removeAudio = function() {
        document.getElementById('currentAudioId').value = '';
        var section = document.getElementById('current-audio-section');
        section.innerHTML = '<p class="text-muted small mb-2">Nenhum áudio selecionado.</p><input type="hidden" name="audio_id" id="currentAudioId" value="">';
    };

    window.changeAudio = function() {
        var sel = document.getElementById('audioSelect');
        if (sel.value) {
            document.getElementById('currentAudioId').value = sel.value;
            // Visual feedback
            var section = document.getElementById('current-audio-section');
            section.innerHTML = '<div class="d-flex align-items-center gap-2 border rounded p-2 mb-2 bg-light"><i class="fad fa-file-audio text-primary"></i><div class="flex-grow-1"><strong>' + sel.options[sel.selectedIndex].text + '</strong><small class="text-muted d-block">Selecionado</small></div><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAudio()" title="Remover"><i class="fas fa-times"></i></button></div><input type="hidden" name="audio_id" id="currentAudioId" value="' + sel.value + '">';
            sel.value = '';
        }
    };

    function updateLeadsCount() {
        var count = document.querySelectorAll('.lead-badge').length;
        var el = document.getElementById('leads-count');
        if (el) el.textContent = count;
        var empty = document.getElementById('leads-empty');
        if (empty) empty.style.display = count > 0 ? 'none' : '';
    }

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
    // Select2 - schedule hours (theme auto-inits instance select via data-control)
    var $st = $('.call-schedule-time');
    if ($st.length && typeof $st.select2 === 'function') {
        $st.select2({ placeholder: 'Selecione os horários permitidos', allowClear: true, theme: 'bootstrap5', selectionCssClass: ':all:', width: 'resolve' });
    }
});
</script>

<!-- Upload Audio Modal (edit) -->
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
