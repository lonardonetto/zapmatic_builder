<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-12 mb-4">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="<?php _ec($config['icon']) ?>" style="color:<?php _ec($config['color']) ?>"></i> Campanhas de Chamada</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#uploadAudioModal">
                        <i class="fad fa-music me-1"></i> Upload Áudio
                    </button>
                    <button class="btn btn-sm btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#createCampaignModal">
                        <i class="fad fa-plus me-1"></i> Nova Campanha
                    </button>
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
                                <th>Leads</th>
                                <th>Atenderam</th>
                                <th>Não atenderam</th>
                                <th>Criado</th>
                                <th class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $c): ?>
                            <tr>
                                <td class="ps-3">
                                    <strong><?php echo htmlspecialchars($c->name) ?></strong>
                                    <br><small class="text-muted">Instância: <?php echo htmlspecialchars($c->instance_id) ?></small>
                                </td>
                                <td>
                                    <?php
                                    $badgeMap = ['draft'=>'secondary','scheduled'=>'info','running'=>'success','paused'=>'warning','completed'=>'primary','failed'=>'danger'];
                                    $badge = $badgeMap[$c->status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge ?>"><?php echo htmlspecialchars($c->status) ?></span>
                                </td>
                                <td><?php echo (int)$c->total_leads ?></td>
                                <td><span class="text-success fw-bold"><?php echo (int)$c->calls_answered ?></span></td>
                                <td><span class="text-danger"><?php echo (int)$c->calls_no_answer + (int)$c->calls_busy + (int)$c->calls_failed ?></span></td>
                                <td class="text-muted small"><?php echo htmlspecialchars($c->created_at) ?></td>
                                <td class="text-end pe-3">
                                    <?php if ($c->status === 'draft' || $c->status === 'paused'): ?>
                                    <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/start')) ?>" style="display:inline">
                                        <input type="hidden" name="campaign_id" value="<?php echo (int)$c->id ?>">
                                        <button type="submit" class="btn btn-sm btn-success"><i class="fad fa-play"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($c->status === 'running'): ?>
                                    <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/pause')) ?>" style="display:inline">
                                        <input type="hidden" name="campaign_id" value="<?php echo (int)$c->id ?>">
                                        <button type="submit" class="btn btn-sm btn-warning"><i class="fad fa-pause"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <a href="<?php _e(base_url('whatsapp_call_campaign/results/' . $c->id)) ?>" class="btn btn-sm btn-outline-primary"><i class="fad fa-chart-bar"></i></a>
                                    <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/delete')) ?>" style="display:inline" onsubmit="return confirm('Excluir esta campanha?')">
                                        <input type="hidden" name="campaign_id" value="<?php echo (int)$c->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fad fa-trash"></i></button>
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

<!-- Modal: Criar Campanha -->
<div class="modal fade" id="createCampaignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fad fa-phone-volume me-2"></i>Nova Campanha de Chamada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php _e(base_url('whatsapp_call_campaign/create')) ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nome da campanha</label>
                            <input type="text" name="name" class="form-control" required placeholder="Ex: Promoção Agosto">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Instância WhatsApp</label>
                            <select name="instance_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($accounts as $a): ?>
                                <option value="<?php _ec($a->token) ?>"><?php _ec($a->name ?: $a->token) ?> (<?php _ec($a->status == 1 ? 'Online' : 'Offline') ?>)</option>
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
                            <input type="number" name="delay_between_calls" class="form-control" value="30" min="5" max="3600">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Timeout toque (s)</label>
                            <input type="number" name="timeout_ring" class="form-control" value="30" min="10" max="120">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Telefones (um por linha)</label>
                            <textarea name="phones" class="form-control" rows="8" required placeholder="5511999999999&#10;5521888888888&#10;5586777777777"></textarea>
                            <small class="text-muted">Um número por linha. Inclua DDD + número (com ou sem +55).</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fad fa-plus me-1"></i>Criar Campanha</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                        <input type="file" name="audio_file" class="form-control" accept=".mp3,.wav,.opus" required>
                        <small class="text-muted">Máximo 10MB. O áudio será tocado para o lead quando atender a chamada.</small>
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
