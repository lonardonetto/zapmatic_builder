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
