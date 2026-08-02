<div class="row">

<style>
/* Select2 Bootstrap match */
.call-instance-select + .select2-container { width: 100% !important; }
.call-instance-select + .select2-container .select2-selection--single {
    height: 38px !important; border: 1px solid #dee2e6 !important; border-radius: 0.375rem !important;
    padding: 0 12px !important; font-size: 14px !important; background: #fff !important;
}
.call-instance-select + .select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important; padding-left: 0 !important; color: #333 !important;
}
.call-instance-select + .select2-container .select2-selection--single .select2-selection__arrow {
    height: 36px !important; right: 8px !important;
}
.call-instance-select + .select2-container--open .select2-selection--single,
.call-instance-select + .select2-container--focus .select2-selection--single {
    border-color: #25D366 !important; box-shadow: 0 0 0 0.2rem rgba(37,211,102,.15) !important;
}
.select2-results__option { padding: 8px 12px !important; font-size: 14px !important; }
.select2-results__option--highlighted { background: #25D366 !important; color: #fff !important; }
.select2-dropdown { border: 1px solid #dee2e6 !important; border-radius: 0.375rem !important; box-shadow: 0 4px 12px rgba(0,0,0,.1) !important; }
</style>

    <div class="col-12">
        <a href="<?php _e(base_url('whatsapp_call_campaign')) ?>" class="btn btn-light btn-sm mb-3">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
        <div class="card border-0 shadow-sm rounded-12">
            <div class="card-header border-0">
                <h5 class="mb-0"><i class="fad fa-phone-volume me-2 text-success"></i>Nova Campanha de Chamada</h5>
            </div>
            <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/create')) ?>">
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Dados básicos -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nome da campanha</label>
                            <input type="text" name="name" class="form-control" required placeholder="Ex: Promoção Agosto">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Instância WhatsApp</label>
                            <select name="instance_id" class="form-select call-instance-select" required data-placeholder="Selecione...">
                                <option value="">Selecione...</option>
                                <?php foreach ($accounts as $a): ?>
                                <option value="<?php _ec($a->token) ?>" data-avatar="<?php _ec(get_file_url($a->avatar)) ?>" data-name="<?php _ec($a->name ?: $a->token) ?>"><?php _ec($a->name ?: $a->token) ?> (<?php _ec($a->status == 1 ? 'Online' : 'Offline') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Áudio para tocar</label>
                            <select name="audio_id" class="form-select">
                                <option value="">Nenhum (chamada sem áudio)</option>
                                <?php foreach ($audios as $a): ?>
                                <option value="<?php _ec($a->id) ?>"><?php _ec($a->name) ?> (<?php _ec($a->duration_seconds) ?>s)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Delay entre chamadas (s)</label>
                            <input type="number" name="delay_between_calls" class="form-control" value="30" min="5">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Timeout toque (s)</label>
                            <input type="number" name="timeout_ring" class="form-control" value="30" min="10">
                        </div>

                        <!-- Leads -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Leads</label>
                            <div class="d-flex gap-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="lead_mode" id="leadModeAll" value="all_contacts" onchange="toggleLeadMode()">
                                    <label class="form-check-label" for="leadModeAll">Todos os contatos (<?php echo count($contacts) ?>)</label>
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

                            <div id="leadModeSelectPanel" class="d-none mb-3">
                                <div class="border rounded p-2" style="max-height:200px; overflow-y:auto;">
                                    <?php if (!empty($contacts)): ?>
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
                                    <?php else: ?>
                                    <p class="text-muted small mb-0">Nenhum contato encontrado.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div id="leadModeManualPanel">
                                <textarea name="phones" class="form-control" rows="6" placeholder="5511999999999&#10;5521888888888&#10;5586777777777"></textarea>
                                <small class="text-muted">Um número por linha. Nono dígito será normalizado automaticamente.</small>
                            </div>
                        </div>

                        <!-- Agendamento -->
                        <div class="col-12">
                            <div class="p-4 rounded border border-primary border-dashed bg-white">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                                    <div>
                                        <h4 class="mb-0"><i class="fad fa-calendar-alt me-2 text-primary"></i>Janela de Execução</h4>
                                        <span class="badge badge-light-info">Dias, horários e feriados</span>
                                    </div>
                                    <p class="text-gray-700 mb-0 fs-12">Defina quando a campanha pode rodar. O delay entre chamadas controla o espaçamento.</p>
                                </div>
                                <div class="row g-4">
                                    <div class="col-xl-6">
                                        <label class="form-label d-block fw-bold">Agendar início</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fad fa-calendar-alt"></i></span>
                                            <input type="text" class="form-control datetime" id="call_time_post" name="time_post" autocomplete="off" placeholder="dd/mm/aaaa HH:mm">
                                        </div>
                                        <p class="fs-12 text-gray-600 mb-0 mt-1">Deixe vazio para iniciar imediatamente ao clicar em Iniciar.</p>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label fw-bold">Timezone</label>
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
                                    <div class="col-xl-6">
                                        <label class="form-label d-block fw-bold">Horários permitidos</label>
                                        <ul class="d-flex flex-wrap seclect-shedule-time gap-3 mb-3" style="list-style:none;padding:0;">
                                            <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="daytime">Daytime</a></li>
                                            <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="nighttime">Nighttime</a></li>
                                            <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="odd">Odd</a></li>
                                            <li><a href="javascript:void(0);" class="call-schedule-preset" data-time="even">Even</a></li>
                                        </ul>
                                        <select class="form-select call-schedule-time mb-2" data-control="select2" data-placeholder="Selecione os horários permitidos" multiple name="schedule_time[]">
                                            <?php for ($i = 0; $i <= 23; $i++): ?>
                                            <option value="<?php echo $i ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                            <?php endfor; ?>
                                        </select>
                                        <p class="fs-12 text-gray-600 mb-1">Escolha os horários em que a campanha poderá ser executada.</p>
                                        <p class="fs-12 text-danger mb-0">Se nenhum horário for selecionado, a campanha poderá rodar em qualquer hora do dia.</p>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label d-block fw-bold">Dias permitidos</label>
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="1,2,3,4,5,6,7">Todos</button>
                                            <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="1,2,3,4,5">Dias úteis</button>
                                            <button type="button" class="btn btn-light-primary btn-sm call-weekday-preset" data-weekdays="6,7">Fim de semana</button>
                                            <button type="button" class="btn btn-light btn-sm call-weekday-clear">Limpar</button>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2" id="call-weekday-selector">
                                            <?php
                                            $weekday_options = call_schedule_weekday_options();
                                            foreach ($weekday_options as $wv => $wm):
                                            ?>
                                            <input type="checkbox" class="btn-check call-weekday-input" name="schedule_weekdays[]" id="create_weekday_<?php echo $wv ?>" value="<?php echo $wv ?>" autocomplete="off">
                                            <label class="btn btn-sm btn-outline btn-outline-primary" for="create_weekday_<?php echo $wv ?>" title="<?php echo $wm['label'] ?>"><?php echo $wm['short'] ?></label>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="fs-12 text-gray-600 mb-0 mt-2">Se nenhum dia for marcado, a campanha poderá rodar em qualquer dia.</p>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 p-3 rounded border border-primary border-dashed bg-white">
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" value="1" id="call_skip_holidays" name="skip_team_holidays">
                                                <label class="form-check-label fw-600 text-gray-700 ms-2" for="call_skip_holidays">Ignorar feriados da equipe</label>
                                            </div>
                                            <div class="text-gray-600 fs-12">Ao ativar, as chamadas serão automaticamente reagendadas quando a data local estiver marcada como feriado.</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-light-primary border border-primary border-dashed mb-0">
                                            <div class="fw-600 text-gray-800 mb-1">Resumo da janela</div>
                                            <div class="text-gray-700" id="call-schedule-summary">Sem restrição. A campanha poderá rodar a qualquer momento.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer justify-content-between d-flex">
                    <a href="<?php _e(base_url('whatsapp_call_campaign')) ?>" class="btn btn-light">Cancelar</a>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-success"><i class="fad fa-bolt me-1"></i>Criar Campanha</button>
                        <button type="submit" class="btn btn-success"><i class="fad fa-calendar-check me-1"></i>Agendar</button>
                    </div>
                </div>
                <input type="hidden" name="clear_time_post" id="callClearTimePost" value="0">
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
    // Select2 - instance with avatar
    var $inst = $('.call-instance-select');
    if ($inst.length && typeof $inst.select2 === 'function') {
        $inst.select2({
            placeholder: 'Selecione...',
            templateResult: function(opt) {
                if (!opt.id) return opt.text;
                var avatar = $(opt.element).data('avatar');
                var name = $(opt.element).data('name') || opt.text;
                if (!avatar) return $('<span>').text(name);
                return $('<span><img src="' + avatar + '" style="width:24px;height:24px;border-radius:50%;margin-right:8px;vertical-align:middle;object-fit:cover;">' + name + '</span>');
            },
            templateSelection: function(opt) {
                if (!opt.id) return opt.text;
                var avatar = $(opt.element).data('avatar');
                var name = $(opt.element).data('name') || opt.text;
                if (!avatar) return $('<span>').text(name);
                return $('<span><img src="' + avatar + '" style="width:20px;height:20px;border-radius:50%;margin-right:6px;vertical-align:middle;object-fit:cover;">' + name + '</span>');
            }
        });
    }
    // Select2 - schedule hours
    var $st = $('.call-schedule-time');
    if ($st.length && typeof $st.select2 === 'function') {
        $st.select2({ placeholder: 'Selecione os horários permitidos', allowClear: true });
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
        if ($st.length && typeof $st.select2 === 'function') $st.trigger('change.select2');
        updateCallScheduleSummary();
    });
    // Weekday presets
    $(document).on('click', '.call-weekday-preset', function() {
        var vals = String($(this).data('weekdays') || '').split(',');
        $('.call-weekday-input').each(function() { this.checked = vals.includes(this.value); });
        updateCallScheduleSummary();
    });
    $(document).on('click', '.call-weekday-clear', function() {
        $('.call-weekday-input').prop('checked', false);
        updateCallScheduleSummary();
    });
    $(document).on('change', '.call-weekday-input, .call-schedule-time, #call_skip_holidays', updateCallScheduleSummary);
    if ($st.length) $st.on('change', updateCallScheduleSummary);
});

function updateCallScheduleSummary() {
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

// "Criar Campanha" clears time_post, "Agendar" keeps it
document.querySelectorAll('button[type="submit"]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var hidden = document.getElementById('callClearTimePost');
        if (hidden) hidden.value = this.textContent.includes('Criar') ? '1' : '0';
    });
});
</script>
