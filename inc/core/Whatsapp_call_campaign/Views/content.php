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
                                <td class="ps-3"><i class="fad fa-file-audio me-2 text-muted"></i><?php echo htmlspecialchars($a->name) ?></td>
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

                            <!-- Contatos selecionados -->
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

                            <!-- Manual -->
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
                                    <!-- Data/hora início -->
                                    <div class="col-xl-6">
                                        <label class="form-label d-block fw-bold">Agendar início</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fad fa-calendar-alt"></i></span>
                                            <input type="text" class="form-control datetime" id="call_time_post" name="time_post" autocomplete="off" placeholder="dd/mm/aaaa HH:mm">
                                        </div>
                                        <p class="fs-12 text-gray-600 mb-0 mt-1">Deixe vazio para iniciar imediatamente ao clicar em Iniciar.</p>
                                    </div>

                                    <!-- Timezone -->
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

                                    <!-- Horários permitidos -->
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

                                    <!-- Dias permitidos -->
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
                                            <input type="checkbox" class="btn-check call-weekday-input" name="schedule_weekdays[]" id="call_weekday_<?php echo $wv ?>" value="<?php echo $wv ?>" autocomplete="off">
                                            <label class="btn btn-sm btn-outline btn-outline-primary" for="call_weekday_<?php echo $wv ?>" title="<?php echo $wm['label'] ?>"><?php echo $wm['short'] ?></label>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="fs-12 text-gray-600 mb-0 mt-2">Se nenhum dia for marcado, a campanha poderá rodar em qualquer dia.</p>
                                    </div>

                                    <!-- Ignorar feriados -->
                                    <div class="col-12">
                                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 p-3 rounded border border-primary border-dashed bg-white">
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" value="1" id="call_skip_holidays" name="skip_team_holidays">
                                                <label class="form-check-label fw-600 text-gray-700 ms-2" for="call_skip_holidays">Ignorar feriados da equipe</label>
                                            </div>
                                            <div class="text-gray-600 fs-12">Ao ativar, as chamadas serão automaticamente reagendadas quando a data local estiver marcada como feriado.</div>
                                        </div>
                                    </div>

                                    <!-- Resumo -->
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
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-success" onclick="document.getElementById('callClearTimePost').value='1'"><i class="fad fa-bolt me-1"></i>Criar Campanha</button>
                        <button type="submit" class="btn btn-success"><i class="fad fa-calendar-check me-1"></i>Agendar</button>
                    </div>
                </div>
                <input type="hidden" name="clear_time_post" id="callClearTimePost" value="0">
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
</script>
