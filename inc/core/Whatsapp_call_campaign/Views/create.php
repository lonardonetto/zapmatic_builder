<div class="container my-5">
    <a href="<?php _e(base_url('whatsapp_call_campaign')) ?>" class="btn btn-dark btn-sm mb-4">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>

    <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/create')) ?>">
        <div class="card b-r-6">
            <div class="card-header">
                <h3 class="card-title"><i class="<?php _ec($config['icon']) ?> me-2" style="color:<?php _ec($config['color']) ?>"></i> Nova Campanha de Chamada</h3>
            </div>
            <div class="card-body position-relative">

                <!-- 1. Nome da campanha -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Nome da campanha</label>
                    <input type="text" class="form-control" name="name" required placeholder="Ex: Promoção Agosto">
                </div>

                <!-- 2. Seleção de contas -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold mb-0">Selecionar instâncias WhatsApp</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAllInstances(true)"><i class="fad fa-check-double me-1"></i>Selecionar todas</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllInstances(false)"><i class="fad fa-times me-1"></i>Desmarcar todas</button>
                        </div>
                    </div>
                    <div class="border rounded p-2" style="max-height:200px; overflow-y:auto;">
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
                                <input class="form-check-input" type="checkbox" name="parallel_instances[]" value="<?php _ec($a->token) ?>" id="pinst_<?php _ec($a->id) ?>" <?php echo $a->status == 1 ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 3. Seleção de leads -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Grupo de contatos</label>
                    <div class="d-flex gap-3 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="lead_mode" id="leadModeAll" value="all_contacts" onchange="toggleLeadMode()">
                            <label class="form-check-label" for="leadModeAll">Todos (<?php echo count($contacts) ?> contatos)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="lead_mode" id="leadModeSelect" value="selected_contacts" onchange="toggleLeadMode()">
                            <label class="form-check-label" for="leadModeSelect">Selecionar contatos</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="lead_mode" id="leadModeManual" value="manual" checked onchange="toggleLeadMode()">
                            <label class="form-check-label" for="leadModeManual">Colar números</label>
                        </div>
                    </div>

                    <div id="leadModeSelectPanel" class="d-none">
                        <div class="border rounded p-2" style="max-height:200px; overflow-y:auto;">
                            <?php foreach ($contacts as $c): ?>
                            <?php if ($c->phone_count > 0): ?>
                            <div class="form-check py-1">
                                <input class="form-check-input" type="checkbox" name="selected_contacts[]" value="<?php echo (int)$c->id ?>" id="contact_<?php echo (int)$c->id ?>">
                                <label class="form-check-label" for="contact_<?php echo (int)$c->id ?>">
                                    <?php echo htmlspecialchars($c->name ?: '(sem nome)') ?>
                                    <span class="badge bg-light text-muted"><?php echo $c->phone_count ?> tel</span>
                                </label>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div id="leadModeManualPanel">
                        <textarea name="phones" class="form-control" rows="5" placeholder="5511999999999&#10;5521888888888&#10;5586777777777"></textarea>
                        <small class="text-muted">Um número por linha. Nono dígito será normalizado automaticamente.</small>
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
                                            <input class="form-check-input" type="radio" name="call_mode" id="modeFila" value="fila" checked>
                                            <label class="form-check-label" for="modeFila"><i class="fad fa-list-ol me-1"></i>Fila</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="call_mode" id="modeAlternado" value="alternado">
                                            <label class="form-check-label" for="modeAlternado"><i class="fad fa-sync-alt me-1"></i>Alternado</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="call_mode" id="modeSimultaneo" value="simultaneo">
                                            <label class="form-check-label" for="modeSimultaneo"><i class="fad fa-layer-group me-1"></i>Simultâneo</label>
                                        </div>
                                    </div>
                                    <small class="text-muted">Fila: 1 instância sequencial. Alternado: alterna instâncias (1 por vez). Simultâneo: N chamadas ao mesmo tempo.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Áudio para tocar</label>
                                    <div id="audio-preview-area"></div>
                                    <select name="audio_id" id="audioSelectCreate" class="form-select mb-2" onchange="previewAudio(this)">
                                        <option value="">Nenhum</option>
                                        <?php foreach ($audios as $a): ?>
                                        <option value="<?php _ec($a->id) ?>" data-stream="<?php _e(base_url('call_audio_stream.php?id=' . $a->id)) ?>" data-format="<?php _ec($a->format) ?>"><?php _ec($a->name) ?> (<?php _ec($a->duration_seconds) ?>s)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadAudioCreateModal">
                                        <i class="fas fa-upload me-1"></i>Upload novo áudio
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Delay mín (s)</label>
                                    <select class="form-select" name="delay_min">
                                        <?php for ($i = 5; $i <= 120; $i += ($i < 30 ? 5 : ($i < 60 ? 10 : 30))): ?>
                                        <option value="<?php echo $i ?>" <?php echo $i === 10 ? 'selected' : '' ?>><?php echo $i ?>s</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Delay máx (s)</label>
                                    <select class="form-select" name="delay_max">
                                        <?php for ($i = 10; $i <= 300; $i += ($i < 60 ? 10 : 30)): ?>
                                        <option value="<?php echo $i ?>" <?php echo $i === 60 ? 'selected' : '' ?>><?php echo $i ?>s</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Timeout toque (s)</label>
                                    <select class="form-select" name="timeout_ring">
                                        <?php for ($i = 10; $i <= 120; $i += 10): ?>
                                        <option value="<?php echo $i ?>" <?php echo $i === 30 ? 'selected' : '' ?>><?php echo $i ?>s</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Time post -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Agendar início</label>
                    <input type="text" class="form-control datetime" id="call_time_post" name="time_post" autocomplete="off" placeholder="dd/mm/aaaa HH:mm">
                    <div class="fs-12 text-gray-600 mt-1">Deixe vazio para iniciar imediatamente ao clicar em Criar.</div>
                </div>

                <!-- 6. Janela de disparo -->
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
                                    <ul class="d-flex flex-wrap seclect-shedule-time gap-3 mb-3" style="list-style:none;padding:0;">
                                        <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="daytime"><?php _e("Daytime")?></a></li>
                                        <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="nighttime"><?php _e("Nighttime")?></a></li>
                                        <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="odd"><?php _e("Odd")?></a></li>
                                        <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="even"><?php _e("Even")?></a></li>
                                    </ul>
                                    <select class="form-select call-schedule-time mb-2" data-control="select2" data-placeholder="<?php _e("Selecione os horários permitidos")?>" multiple name="schedule_time[]">
                                        <?php for ($i = 0; $i <= 23; $i++): ?>
                                        <option value="<?php echo $i ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                        <?php endfor; ?>
                                    </select>
                                    <p class="fs-12 text-gray-600 mb-1"><?php _e("Escolha os horários em que a campanha poderá ser executada.")?></p>
                                    <p class="fs-12 text-danger mb-0"><?php _e("Se nenhum horário for selecionado, a campanha poderá rodar em qualquer hora do dia.")?></p>
                                </div>

                                <div class="col-xl-6">
                                    <label class="form-label d-block fw-bold"><?php _e("Dias permitidos")?></label>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="1,2,3,4,5,6,7"><?php _e("Todos")?></button>
                                        <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="1,2,3,4,5"><?php _e("Dias úteis")?></button>
                                        <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="6,7"><?php _e("Fim de semana")?></button>
                                        <button type="button" class="btn btn-light btn-sm call-weekday-clear"><?php _e("Limpar")?></button>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2" id="call-weekday-selector">
                                        <?php $weekday_options = call_schedule_weekday_options(); ?>
                                        <?php foreach ($weekday_options as $wv => $wm): ?>
                                        <input type="checkbox" class="btn-check call-weekday-input" name="schedule_weekdays[]" id="create_weekday_<?php echo $wv ?>" value="<?php echo $wv ?>" autocomplete="off">
                                        <label class="btn btn-sm btn-outline btn-outline-primary" for="create_weekday_<?php echo $wv ?>" title="<?php echo $wm['label'] ?>"><?php echo $wm['short'] ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="fs-12 text-gray-600 mb-0 mt-2"><?php _e("Se nenhum dia for marcado, a campanha poderá rodar em qualquer dia.")?></p>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 p-3 rounded border border-primary border-dashed bg-white">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" value="1" id="call_skip_holidays" name="skip_team_holidays">
                                            <label class="form-check-label fw-bold" for="call_skip_holidays"><?php _e("Ignorar feriados da equipe")?></label>
                                        </div>
                                        <div class="text-gray-600 fs-12"><?php _e("Ao ativar, as chamadas serão reagendadas quando a data local estiver marcada como feriado.")?></div>
                                    </div>
                                </div>

                                <div class="col-xl-6">
                                    <label class="form-label fw-bold"><?php _e("Timezone")?></label>
                                    <select name="timezone" class="form-select">
                                        <option value="">Padrão do sistema</option>
                                        <option value="America/Sao_Paulo">America/Sao_Paulo (BRT)</option>
                                        <option value="America/Manaus">America/Manaus (AMT)</option>
                                        <option value="America/Belem">America/Belem (BRT)</option>
                                        <option value="America/Fortaleza">America/Fortaleza (BRT)</option>
                                        <option value="America/Bahia">America/Bahia (BRT)</option>
                                        <option value="America/Recife">America/Recife (BRT)</option>
                                        <option value="America/Noronha">America/Noronha (FNT)</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-light-primary border border-primary border-dashed mb-0" id="call-schedule-summary-wrap">
                                        <div class="fw-bold text-gray-800 mb-1"><?php _e("Resumo da janela")?></div>
                                        <div class="text-gray-700" id="call-schedule-summary"><?php _e("Sem restrição. A campanha poderá rodar a qualquer momento.")?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="<?php _e(base_url('whatsapp_call_campaign')) ?>" class="btn btn-dark"><?php _e("Voltar")?></a>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-success"><i class="fad fa-bolt me-1"></i>Criar Campanha</button>
                        <button type="submit" class="btn btn-success"><i class="fal fa-paper-plane me-1"></i>Agendar</button>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="clear_time_post" id="callClearTimePost" value="0">
    </form>
</div>

<script>
$(document).ready(function() {
    // Toggle all instances
    window.toggleAllInstances = function(state) {
        document.querySelectorAll('input[name="parallel_instances[]"]').forEach(function(cb) {
            cb.checked = state;
        });
    };

    // Toggle lead mode
    $('input[name="lead_mode"]').on('change', function() {
        var mode = $(this).val();
        $('#leadModeSelectPanel').toggleClass('d-none', mode !== 'selected_contacts');
        $('#leadModeManualPanel').toggleClass('d-none', mode !== 'manual');
    });

    // DateTimePicker
    var $tp = $('#call_time_post');
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

    // Weekday presets
    $(document).on('click', '.call-weekday-preset', function() {
        var vals = String($(this).data('weekdays') || '').split(',');
        $('.call-weekday-input').each(function() { this.checked = vals.includes(this.value); });
        updateScheduleSummary();
    });
    $(document).on('click', '.call-weekday-clear', function() {
        $('.call-weekday-input').prop('checked', false);
        updateScheduleSummary();
    });
    $(document).on('change', '.call-weekday-input, .call-schedule-time, #call_skip_holidays', updateScheduleSummary);
});

function updateScheduleSummary() {
    var hours = [];
    var sel = document.querySelector('.call-schedule-time');
    if (sel) { for (var i = 0; i < sel.options.length; i++) { if (sel.options[i].selected) hours.push(sel.options[i].value); } }
    var weekdays = [];
    document.querySelectorAll('.call-weekday-input:checked').forEach(function(cb) { weekdays.push(cb.value); });
    var skip = document.getElementById('call_skip_holidays');
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
    if (summary) summary.textContent = parts.length > 0 ? parts.join(' | ') : 'Sem restrição. A campanha poderá rodar a qualquer momento.';
}

// Criar Campanha clears time_post, Agendar keeps it
document.querySelectorAll('button[type="submit"]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var hidden = document.getElementById('callClearTimePost');
        if (hidden) hidden.value = this.textContent.includes('Criar') ? '1' : '0';
    });
});

function previewAudio(sel) {
    var area = document.getElementById('audio-preview-area');
    if (!sel.value) { area.innerHTML = ''; return; }
    var opt = sel.options[sel.selectedIndex];
    var stream = opt.getAttribute('data-stream');
    var format = opt.getAttribute('data-format') || 'mpeg';
    area.innerHTML = '<div class="mb-2"><audio controls preload="none" style="width:100%;height:32px;"><source src="' + stream + '" type="audio/' + format + '"></audio></div>';
}
</script>

<!-- Upload Audio Modal (create) -->
<div class="modal fade" id="uploadAudioCreateModal" tabindex="-1">
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
