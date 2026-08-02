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
                            <select name="instance_id" class="form-select call-instance-select" data-placeholder="Selecione...">
                                <?php foreach ($accounts as $a): ?>
                                <option value="<?php _ec($a->token) ?>" data-avatar="<?php _ec(get_file_url($a->avatar)) ?>" data-name="<?php _ec($a->name ?: $a->token) ?>" <?php echo $a->token == $campaign->instance_id ? 'selected' : '' ?>><?php _ec($a->name ?: $a->token) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Áudio</label>
                            <select name="audio_id" class="form-select">
                                <option value="">Nenhum</option>
                                <?php foreach ($audios as $a): ?>
                                <option value="<?php _ec($a->id) ?>" <?php echo ((int)$a->id === (int)($campaign->audio_id ?? 0)) ? 'selected' : '' ?>><?php _ec($a->name) ?> (<?php _ec($a->duration_seconds) ?>s)</option>
                                <?php endforeach; ?>
                            </select>
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
                            <label class="form-label fw-bold">Leads atuais (<?php echo count($leads) ?>)</label>
                            <div class="border rounded p-2 mb-2" style="max-height:150px; overflow-y:auto;">
                                <?php foreach ($leads as $lead): ?>
                                <span class="badge bg-light text-dark me-1 mb-1"><?php echo htmlspecialchars($lead->phone) ?> <?php echo htmlspecialchars($lead->name) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <label class="form-label fw-bold">Adicionar números (um por linha, será normalizado)</label>
                            <textarea name="phones" class="form-control" rows="4" placeholder="5511999999999&#10;5521888888888"></textarea>
                            <small class="text-muted">Novos números serão adicionados. Leads já existentes mantidos.</small>
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
});
</script>
