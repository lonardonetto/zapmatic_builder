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
                            <select name="instance_id" class="form-select">
                                <?php foreach ($accounts as $a): ?>
                                <option value="<?php _ec($a->token) ?>" <?php echo $a->token == $campaign->instance_id ? 'selected' : '' ?>><?php _ec($a->name ?: $a->token) ?></option>
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
                            <div class="border rounded p-3">
                                <h6 class="fw-bold mb-3"><i class="fad fa-calendar-alt me-2 text-primary"></i>Janela de Execução</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Horários permitidos</label>
                                        <?php $schedHours = json_decode($campaign->schedule_time ?? '[]', true) ?? []; ?>
                                        <select name="schedule_time[]" class="form-select" multiple size="4">
                                            <?php for ($h = 0; $h <= 23; $h++): ?>
                                            <option value="<?php echo $h ?>" <?php echo in_array((string)$h, $schedHours) ? 'selected' : '' ?>><?php echo str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Dias permitidos</label>
                                        <?php $schedDays = json_decode($campaign->schedule_weekdays ?? '[]', true) ?? []; ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php
                                            $dayNames = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb',7=>'Dom'];
                                            foreach ($dayNames as $val => $label):
                                            ?>
                                            <label class="btn btn-sm <?php echo in_array((string)$val, $schedDays) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                                <input type="checkbox" name="schedule_weekdays[]" value="<?php echo $val ?>" class="d-none" <?php echo in_array((string)$val, $schedDays) ? 'checked' : '' ?>> <?php echo $label ?>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="skip_team_holidays" value="1" <?php echo !empty($campaign->skip_team_holidays) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Ignorar feriados</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">Timezone</label>
                                        <select name="timezone" class="form-select">
                                            <option value="">Padrão</option>
                                            <?php foreach (['America/Sao_Paulo','America/Manaus','America/Belem','America/Fortaleza','America/Bahia','America/Recife','America/Noronha'] as $tz): ?>
                                            <option value="<?php echo $tz ?>" <?php echo ($campaign->timezone ?? '') === $tz ? 'selected' : '' ?>><?php echo $tz ?></option>
                                            <?php endforeach; ?>
                                        </select>
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
