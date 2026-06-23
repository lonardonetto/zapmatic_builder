<?php
    $current_type = (int)get_data($result, "type");
    if ($current_type === 0) {
        $current_type = 1;
    }
    $is_call_campaign = $current_type === 7;
    $cloud_parallel_enabled = (int)get_data($result, "cloud_parallel_enabled") === 1;
    $cloud_parallel_level = (int)get_data($result, "cloud_parallel_level");
    $cloud_parallel_presets = [10, 20, 25, 30, 40, 50, 60, 70, 80, 90, 100];

    $weekday_options = isset($weekday_options) && is_array($weekday_options)
        ? $weekday_options
        : (function_exists('whatsapp_bulk_schedule_weekday_options')
            ? whatsapp_bulk_schedule_weekday_options()
            : [
                '1' => ['short' => 'Seg', 'label' => 'Segunda-feira'],
                '2' => ['short' => 'Ter', 'label' => 'Terça-feira'],
                '3' => ['short' => 'Qua', 'label' => 'Quarta-feira'],
                '4' => ['short' => 'Qui', 'label' => 'Quinta-feira'],
                '5' => ['short' => 'Sex', 'label' => 'Sexta-feira'],
                '6' => ['short' => 'Sáb', 'label' => 'Sábado'],
                '7' => ['short' => 'Dom', 'label' => 'Domingo'],
            ]);

    $schedule_time = get_data($result, "schedule_time");
    if ($schedule_time != "") {
        $schedule_time = json_decode($schedule_time);
        if (!is_array($schedule_time)) {
            $schedule_time = [];
        }
    } else {
        $schedule_time = [];
    }
    $schedule_time = array_map('strval', $schedule_time);

    $schedule_weekdays = get_data($result, "schedule_weekdays");
    if ($schedule_weekdays != "") {
        $schedule_weekdays = json_decode($schedule_weekdays);
        if (!is_array($schedule_weekdays)) {
            $schedule_weekdays = [];
        }
    } else {
        $schedule_weekdays = [];
    }
    $schedule_weekdays = array_map('strval', $schedule_weekdays);

    $skip_team_holidays = (int)get_data($result, "skip_team_holidays") === 1;
    $schedule_window = function_exists('whatsapp_bulk_schedule_window_meta')
        ? whatsapp_bulk_schedule_window_meta($schedule_time, $schedule_weekdays, $skip_team_holidays, $weekday_options)
        : [
            'has_rules' => false,
            'short' => '',
            'full' => '',
            'empty' => 'Sem restrição adicional. A campanha poderá rodar a qualquer momento.',
        ];
    $schedule_summary_empty = $schedule_window['empty'] ?? 'Sem restrição adicional. A campanha poderá rodar a qualquer momento.';
    $schedule_summary_initial = !empty($schedule_window['has_rules'])
        ? ($schedule_window['short'] ?? '')
        : $schedule_summary_empty;
    $time_post_value = get_data($result, "time_post");
    if ($time_post_value !== "" && $time_post_value !== null) {
        if (!is_numeric($time_post_value)) {
            $time_post_value = strtotime((string) $time_post_value);
        }
        $time_post_value = $time_post_value ? date('d/m/Y H:i', (int) $time_post_value) : '';
    } else {
        $time_post_value = '';
    }

    if ($time_post_value === '') {
        $default_time_post = time() + (10 * 60);
        $default_time_post = (int)(ceil($default_time_post / 300) * 300);
        $time_post_value = date('d/m/Y H:i', $default_time_post);
    }

    $team_holidays = isset($team_holidays) && is_array($team_holidays) ? $team_holidays : [];
?>
<form class="actionForm" action="<?php _eC( get_module_url("save/".get_data($result, "ids")) )?>" method="POST" data-redirect="<?php _ec( get_module_url() )?>">
    <div class="container my-5">
        <div class="bd-search position-relative me-auto">
            <h2 class="mb-0 py-4"> <i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e( $config['name'] )?></h2>
        </div>

        <div class="card b-r-6 h-100 post-schedule wrap-caption">
            <div class="card-header">
                <h3 class="card-title"><?php _e("Update campaign")?></h3>
                <div class="card-toolbar"></div>
            </div>
            <div class="card-body position-relative">
                <div class="mb-3">
                    <label class="form-label"><?php _e("Select WhatsApp accounts")?></label>
                    <?php echo view_cell('\Core\Account_manager\Controllers\Account_manager::widget', [ "whereIn" => ["id" => json_decode( get_data($result, "accounts") ) ] ,"wheres" => ["social_network" => "whatsapp", "login_type" => [1, 2, 3], "status" => 1, "team_id" => get_team("id")] ]) ?>
                </div>
                <div class="alert alert-warning d-none mb-3" id="call-campaign-account-hint">
                    <div class="fw-600"><?php _e("Baileys accounts only")?></div>
                    <small class="text-gray-700"><?php _e("Only Baileys accounts can be selected for call campaigns.")?></small>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php _e("Campaign name")?></label>
                    <input type="text" class="form-control form-control-solid" name="name" value="<?php _ec( get_data($result, "name") )?>" required>
                </div>
                <input type="hidden" name="carousel_msg" value="<?php _ec( get_data($result, "template", "hidden", get_data($result, "template")) )?>">
                <div class="mb-3">
                    <label class="form-label"><?php _e("Contact group")?></label>
                    <select class="form-select form-select-solid" name="group" required>
                        <option value=""><?php _e("Select contact group")?></option>
                        <?php if (!empty($contacts)): ?>
                            <?php foreach ($contacts as $key => $value): ?>
                                <option value="<?php _ec($value->id)?>" <?php _ec( get_data($result, "contact_id", "select", $value->id) )?> ><?php _ec($value->name)?></option>
                            <?php endforeach ?>
                        <?php endif ?>
                    </select>
                </div>

                <?php 
                    // Resumo de status Cloud API para esta campanha (se fornecido pelo controller)
                    $status_summary = isset($status_summary) && is_array($status_summary) ? $status_summary : [];
                    $total_logged = array_sum($status_summary);
                    $delivered = $status_summary['delivered'] ?? 0;
                    $read = $status_summary['read'] ?? 0;
                    $failed = $status_summary['failed'] ?? 0;
                    $sent = $status_summary['sent'] ?? 0;
                    
                    // Verificar se a campanha usa Cloud API (contas login_type=1)
                    $uses_cloud = false;
                    if (!empty($result) && !empty($result->accounts)) {
                        $account_ids = json_decode($result->accounts, true);
                        if (is_array($account_ids) && !empty($account_ids)) {
                            $team_id = get_team("id");
                            $cloud_accounts = db_fetch("*", TB_ACCOUNTS, [
                                "id" => $account_ids,
                                "social_network" => "whatsapp",
                                "login_type" => 1,
                                "team_id" => $team_id
                            ]);
                            $uses_cloud = !empty($cloud_accounts);
                        }
                    }
                ?>
                
                <?php if ($uses_cloud): ?>
                    <?php if ($total_logged > 0): ?>
                    <div class="mb-3">
                        <div class="card border b-r-6">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0"><?php _e("Status dos envios (Cloud API)")?></h3>
                                <span class="badge badge-light"><?php _ec($total_logged)?> <?php _e("mensagens acompanhadas")?></span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="fw-600 text-gray-700"><?php _e("Enviadas (logadas)")?></div>
                                        <div class="fs-3 fw-bold text-primary"><?php _ec($total_logged)?></div>
                                        <small class="text-gray-500">
                                            <?php if ($sent > 0): ?>
                                                <?php _e("Aguardando confirmação")?>: <?php _ec($sent)?>
                                            <?php elseif ($total_logged > 0): ?>
                                                <?php _e("Todas com status atualizado")?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="fw-600 text-gray-700"><?php _e("Entregues")?></div>
                                        <div class="fs-3 fw-bold text-success">
                                            <?php _ec($delivered)?>
                                            <span class="fs-7 text-gray-500">
                                                (<?php _ec($total_logged ? round(($delivered / $total_logged) * 100) : 0)?>%)
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="fw-600 text-gray-700"><?php _e("Lidas")?></div>
                                        <div class="fs-3 fw-bold text-info">
                                            <?php _ec($read)?>
                                            <span class="fs-7 text-gray-500">
                                                (<?php _ec($total_logged ? round(($read / $total_logged) * 100) : 0)?>%)
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="fw-600 text-gray-700"><?php _e("Falhas")?></div>
                                        <div class="fs-3 fw-bold text-danger">
                                            <?php _ec($failed)?>
                                            <span class="fs-7 text-gray-500">
                                                (<?php _ec($total_logged ? round(($failed / $total_logged) * 100) : 0)?>%)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <p class="fs-12 text-gray-500 mb-0">
                                    <?php _e("Os números acima consideram apenas envios realizados pela Cloud API que retornaram um message_id da Meta. Baileys mantém seu próprio relatório separado.")?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <div class="d-flex">
                                    <div class="me-3"><i class="fad fa-info-circle fs-2"></i></div>
                                    <div>
                                        <div class="fw-600"><?php _e("Aguardando dados de status")?></div>
                                        <small class="text-gray-600">
                                            <?php _e("O tracking de status só funciona com contas Cloud API (Oficial). Se a campanha usa Baileys, os dados não serão gravados.")?>
                                            <br>
                                            <?php _e("Se você usa Cloud API e já disparou:")?>
                                            <br>
                                            • <?php _e("Verifique se a tabela 'sp_whatsapp_message_status' existe")?>
                                            <br>
                                            • <?php _e("Confira o diagnóstico para ver o tipo das contas selecionadas")?>
                                            <br>
                                            <a href="<?php _ec(base_url('whatsapp_bulk/diagnostic_status/' . get_data($result, "ids")))?>" target="_blank" class="btn btn-sm btn-light mt-2">
                                                <?php _e("Ver diagnóstico")?>
                                            </a>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <ul class="nav nav-pills mb-3 bg-white rounded fs-14 nx-scroll overflow-x-auto d-flex text-over b-r-6 border" id="pills-tab">
                    <li class="nav-item me-0">
                         <label for="type_text_media" class="nav-link bg-active-primary text-gray-700 px-4 py-3 b-r-6 text-active-white <?php _ec( $current_type == 1?"active":"" ) ?>" data-bs-toggle="pill" data-bs-target="#wa_text_and_media" type="button" role="tab"><?php _e("Text & Media")?></label>
                         <input class="d-none" type="radio" name="type" id="type_text_media" <?php _ec( $current_type == 1?"checked='true'":"" ) ?> value="1">
                    </li>
                    <?php echo view_cell('\\Core\\Whatsapp_button_template\\Controllers\\Whatsapp_button_template::widget_menu', ["result" => $result]) ?>
                    <?php echo view_cell('\\Core\\Whatsapp_list_message_template\\Controllers\\Whatsapp_list_message_template::widget_menu', ["result" => $result]) ?>
                    <?php echo view_cell('\\Core\\Whatsapp_poll_template\\Controllers\\Whatsapp_poll_template::widget_menu', ["result" => $result]) ?>
                    <?php echo view_cell('\\Core\\Whatsapp_carousel_template\\Controllers\\Whatsapp_carousel_template::widget_menu', ["result" => $result]) ?>
                    <li class="nav-item me-0">
                         <label for="type_call_campaign" class="nav-link bg-active-primary text-gray-700 px-4 py-3 b-r-6 text-active-white <?php _ec( $is_call_campaign?"active":"" ) ?>" data-bs-toggle="pill" data-bs-target="#wa_call_campaign" type="button" role="tab"><?php _e("Campanha de ligação (Baileys)")?></label>
                         <input class="d-none" type="radio" name="type" id="type_call_campaign" <?php _ec( $is_call_campaign?"checked='true'":"" ) ?> value="7">
                    </li>
                </ul>

                <?php if (get_data($result, "type") == 6): ?>
                    <!-- Mantém o valor do tipo para que o backend retorne mensagem clara ao salvar (fluxo desativado) -->
                    <input class="d-none" type="radio" name="type" id="type_meta_template" checked="true" value="6">
                    <div class="alert alert-warning">
                        <div class="d-flex">
                            <div class="me-3"><i class="fad fa-exclamation-triangle fs-2"></i></div>
                            <div>
                                <div class="fw-600"><?php _e("Templates Oficiais (Meta) foram removidos do Bulk Message.")?></div>
                                <small class="text-gray-600">
                                    <?php _e("Use o módulo 'Modelo de botão' para criar/submeter/versões e sincronize no Perfil Cloud API. Aqui, selecione o template nos módulos (botão/lista/carrossel/enquete).")?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php echo view_cell('\\Core\\Whatsapp\\Controllers\\Whatsapp::widget_template_visual_selector', ["result" => $result, "context" => "bulk_message"]) ?>

                <div class="tab-content mb-3" id="pills-tabContent">
                    <div class="tab-pane fade show <?php _ec( $current_type == 1?" active":"" ) ?>" id="wa_text_and_media">
                        <?php echo view_cell('\Core\Whatsapp\Controllers\Whatsapp::widget_content', ["result" => $result]) ?>
                        <label class="form-label"><?php _e("Caption")?></label>
                        <?php echo view_cell('\Core\Caption\Controllers\Caption::block', ['name' => 'caption', 'value' => get_data($result, "caption")]) ?>

                        <ul class="text-gray-400 fs-12">
                            <li><?php _e("Random message by Spintax")?></li>
                            <li><?php _e("Ex: {Hi|Hello|Hola}")?></li>
                            <li><?php _e("Add custom variables: %name%, %param1%, %param2%,...")?></li>
                        </ul>
                    </div>
                    <?php echo view_cell('\\Core\\Whatsapp_button_template\\Controllers\\Whatsapp_button_template::widget_content', ["result" => $result]) ?>
                    <?php echo view_cell('\\Core\\Whatsapp_list_message_template\\Controllers\\Whatsapp_list_message_template::widget_content', ["result" => $result]) ?>
                    <?php echo view_cell('\\Core\\Whatsapp_poll_template\\Controllers\\Whatsapp_poll_template::widget_content', ["result" => $result]) ?>
                    <?php echo view_cell('\\Core\\Whatsapp_carousel_template\\Controllers\\Whatsapp_carousel_template::widget_content', ["result" => $result]) ?>
                    <div class="tab-pane fade<?php _ec( $is_call_campaign?" show active":"" ) ?>" id="wa_call_campaign">
                        <div class="card border b-r-6">
                            <div class="card-body">
                                <div class="d-flex flex-column gap-3">
                                    <div>
                                        <span class="badge badge-light-danger mb-2"><?php _e("Baileys accounts only")?></span>
                                        <h4 class="mb-1"><?php _e("Voice call only")?></h4>
                                        <p class="text-gray-700 mb-0"><?php _e("This campaign initiates a WhatsApp voice call using the safe Baileys protocol path available in this server.")?></p>
                                    </div>
                                    <div class="alert alert-primary mb-0">
                                        <div class="fw-600 mb-1"><?php _e("Call ending depends on the current WhatsApp/Baileys protocol behavior. This mode does not force a low-level hangup.")?></div>
                                        <small class="text-gray-700"><?php _e("Only one-to-one contacts are supported. Group JIDs will be recorded as controlled failures.")?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="card border b-r-6">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-12 mb-3">
                                    <label class="form-label"><?php _e("Time post")?></label>
                                    <input type="text" class="form-control form-control-solid datetime datetime bulk-time-post" id="bulk_time_post" autocomplete="off" name="time_post" value="<?php _e($time_post_value)?>" placeholder="dd/mm/aaaa 00:00">
                                    <div class="fs-12 text-gray-600 mt-2"><?php _e("Defina quando a campanha pode começar. Para evitar disparo imediato sem querer, o padrão já abre alguns minutos à frente.")?></div>
                                </div>
                                <div class="col-12 mb-3" id="cloud-parallel-card">
                                    <div class="card border b-r-6 bg-light-primary">
                                        <div class="card-body">
                                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <h4 class="mb-0"><?php _e("Cloud API simultâneo")?></h4>
                                                        <span class="badge badge-light-primary"><?php _e("Somente Cloud API")?></span>
                                                    </div>
                                                    <p class="text-gray-700 mb-0"><?php _e("Ative ondas paralelas isoladas apenas para campanhas que usam contas 100% Cloud API. Campanhas mistas continuam no fluxo serial original.")?></p>
                                                </div>
                                                <div class="form-check form-switch form-check-custom form-check-solid">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        value="1"
                                                        id="cloud_parallel_enabled"
                                                        name="cloud_parallel_enabled"
                                                        <?php _ec($cloud_parallel_enabled ? 'checked="checked"' : '') ?>
                                                    >
                                                    <label class="form-check-label fw-600 text-gray-700 ms-2" for="cloud_parallel_enabled"><?php _e("Ativar")?></label>
                                                </div>
                                            </div>

                                            <div class="alert alert-warning d-none mb-3" id="cloud-parallel-unavailable"></div>
                                            <div class="alert alert-info d-none mb-3" id="cloud-parallel-summary"></div>

                                            <div class="row g-3 align-items-end" id="cloud-parallel-controls">
                                                <div class="col-md-4">
                                                    <label class="form-label"><?php _e("Nível simultâneo")?></label>
                                                    <select class="form-select form-select-solid" name="cloud_parallel_level" id="cloud_parallel_level" data-saved-level="<?php _ec($cloud_parallel_level) ?>">
                                                        <option value=""><?php _e("Selecione o nível simultâneo")?></option>
                                                        <?php foreach ($cloud_parallel_presets as $preset): ?>
                                                            <option value="<?php _ec($preset) ?>" <?php _ec($cloud_parallel_level === $preset ? 'selected="selected"' : '') ?>><?php _ec($preset) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="fs-12 text-gray-700 mb-1"><?php _e("A disponibilidade dos níveis é calculada a partir das contas Cloud selecionadas. Os níveis 90 e 100 só são liberados quando a capacidade segura agregada permitir.")?></div>
                                                    <div class="fs-12 text-gray-600" id="cloud-parallel-level-hint"><?php _e("Quando este modo estiver ativo, o delay mínimo/máximo passa a ser o intervalo entre as ondas paralelas.")?></div>
                                                </div>
                                            </div>

                                            <div class="mt-3 d-none" id="cloud-parallel-account-list-wrap">
                                                <div class="fs-12 fw-600 text-gray-700 mb-2"><?php _e("Contas Cloud detectadas")?></div>
                                                <div class="fs-12 text-gray-600" id="cloud-parallel-account-list"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label"><?php _e("Random message interval by minimum (second)")?></label>
                                    <select class="form-select form-select-solid" name="min_interval_per_post" required>
                                        <option value=""><?php _e("Select min second")?></option>
                                        <?php for($i = 1; $i <= 3600; $i++):?>
                                            <?php if ($i  <= 100): ?>
                                                <option value="<?php _ec( $i )?>" <?php _ec( get_data($result, "min_delay", "select", $i) )?> ><?php _ec($i === 1 ? '1 segundo' : sprintf('%s segundos', $i)) ?></option>
                                            <?php elseif($i%5==0): ?>
                                                <option value="<?php _ec( $i )?>" <?php _ec( get_data($result, "min_delay", "select", $i) )?> ><?php _ec(sprintf('%s segundos', $i)) ?></option>
                                            <?php endif ?>
                                        <?php endfor ?>
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label"><?php _e("Random message interval by maximum (second)")?></label>
                                    <select class="form-select form-select-solid" name="max_interval_per_post" required>
                                        <option value=""><?php _e("Select max second")?></option>
                                        <?php for($i = 1; $i <= 3600; $i++):?>
                                            <?php if ($i  <= 100): ?>
                                                <option value="<?php _ec( $i )?>" <?php _ec( get_data($result, "max_delay", "select", $i) )?>><?php _ec($i === 1 ? '1 segundo' : sprintf('%s segundos', $i)) ?></option>
                                            <?php elseif($i%5==0): ?>
                                                <option value="<?php _ec( $i )?>" <?php _ec( get_data($result, "max_delay", "select", $i) )?>><?php _ec(sprintf('%s segundos', $i)) ?></option>
                                            <?php endif ?>
                                        <?php endfor ?>
                                    </select>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="card border b-r-6 bg-light-info">
                                        <div class="card-body">
                                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                        <h4 class="mb-0"><?php _e("Janela de disparo")?></h4>
                                                        <span class="badge badge-light-info"><?php _e("Dias, horários e feriados")?></span>
                                                    </div>
                                                    <p class="text-gray-700 mb-0"><?php _e("Defina quando a campanha pode rodar. O intervalo mínimo e máximo continua controlando o espaçamento entre os envios.")?></p>
                                                </div>
                                                <div>
                                                    <button type="button" class="btn btn-light-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#bulkTeamHolidaysOffcanvas" aria-controls="bulkTeamHolidaysOffcanvas">
                                                        <i class="fad fa-calendar-alt me-2"></i><?php _e("Gerenciar feriados da equipe")?>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="row g-4">
                                                <div class="col-xl-6">
                                                    <label class="form-label d-block"><?php _e("Horários permitidos")?></label>

                                                    <ul class="d-flex flex-wrap seclect-shedule-time gap-3 mb-3">
                                                        <li><a href="javascript:void(0);" data-time="daytime"><?php _e("Daytime")?></a></li>
                                                        <li><a href="javascript:void(0);" data-time="nighttime"><?php _e("Nighttime")?></a></li>
                                                        <li><a href="javascript:void(0);" data-time="odd"><?php _e("Odd")?></a></li>
                                                        <li><a href="javascript:void(0);" data-time="even"><?php _e("Even")?></a></li>
                                                    </ul>

                                                    <select class="form-select form-select-solid schedule_time mb-2" data-control="select2" data-placeholder="<?php _e("Selecione os horários permitidos")?>" multiple name="schedule_time[]">
                                                        <?php for($i = 0; $i <= 23; $i++):?>
                                                            <option value="<?php _ec( $i )?>" <?php _ec( in_array((string)$i, $schedule_time, true)?"selected":"" )?> ><?php _ec( $i )?></option>
                                                        <?php endfor ?>
                                                    </select>
                                                    <p class="fs-12 text-gray-600 mb-1"><?php _e("Escolha os horários em que a campanha poderá ser executada.")?></p>
                                                    <p class="fs-12 text-danger mb-0"><?php _e("Se nenhum horário for selecionado, a campanha poderá rodar em qualquer hora do dia.")?></p>
                                                </div>

                                                <div class="col-xl-6">
                                                    <label class="form-label d-block"><?php _e("Dias permitidos")?></label>
                                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                                        <button type="button" class="btn btn-light-primary btn-sm bulk-weekday-preset" data-weekdays="1,2,3,4,5,6,7"><?php _e("Todos")?></button>
                                                        <button type="button" class="btn btn-light-primary btn-sm bulk-weekday-preset" data-weekdays="1,2,3,4,5"><?php _e("Dias úteis")?></button>
                                                        <button type="button" class="btn btn-light-primary btn-sm bulk-weekday-preset" data-weekdays="6,7"><?php _e("Fim de semana")?></button>
                                                        <button type="button" class="btn btn-light btn-sm bulk-weekday-clear"><?php _e("Limpar")?></button>
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-2" id="bulk-weekday-selector">
                                                        <?php foreach ($weekday_options as $weekday_value => $weekday_meta): ?>
                                                            <input
                                                                type="checkbox"
                                                                class="btn-check bulk-weekday-input"
                                                                name="schedule_weekdays[]"
                                                                id="bulk_schedule_weekday_<?php _ec($weekday_value) ?>"
                                                                value="<?php _ec($weekday_value) ?>"
                                                                autocomplete="off"
                                                                <?php _ec(in_array((string)$weekday_value, $schedule_weekdays, true) ? 'checked="checked"' : '') ?>
                                                            >
                                                            <label
                                                                class="btn btn-sm btn-outline btn-outline-primary"
                                                                for="bulk_schedule_weekday_<?php _ec($weekday_value) ?>"
                                                                title="<?php _ec($weekday_meta['label']) ?>"
                                                            >
                                                                <?php _ec($weekday_meta['short']) ?>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <p class="fs-12 text-gray-600 mb-0 mt-2"><?php _e("Se nenhum dia for marcado, a campanha poderá rodar em qualquer dia válido do calendário.")?></p>
                                                </div>

                                                <div class="col-12">
                                                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 p-4 rounded border border-primary border-dashed bg-white">
                                                        <div class="form-check form-switch form-check-custom form-check-solid m-0">
                                                            <input
                                                                class="form-check-input"
                                                                type="checkbox"
                                                                value="1"
                                                                id="skip_team_holidays"
                                                                name="skip_team_holidays"
                                                                <?php _ec($skip_team_holidays ? 'checked="checked"' : '') ?>
                                                            >
                                                            <label class="form-check-label fw-600 text-gray-700 ms-2" for="skip_team_holidays"><?php _e("Ignorar feriados da equipe")?></label>
                                                        </div>
                                                        <div class="text-gray-600 fs-12"><?php _e("Ao ativar, os disparos serão automaticamente reagendados quando a data local estiver marcada no calendário da equipe.")?></div>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="alert alert-light-primary border border-primary border-dashed mb-0" id="bulk-schedule-summary-wrap">
                                                        <div class="fw-600 text-gray-800 mb-1"><?php _e("Resumo da janela")?></div>
                                                        <div class="text-gray-700" id="bulk-schedule-summary"><?php _e($schedule_summary_initial)?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
            
                        </div>
                    </div>
                </div> 
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="<?php _ec( get_module_url() )?>" class="btn btn-dark btn-hover-scale">
                        <?php _e("Back")?>
                    </a>
                    <button type="submit" class="btn btn-primary btn-hover-scale">
                        <i class="fal fa-paper-plane"></i> <?php _e("Schedule")?>
                    </button>
                </div>
            </div>
        </div>
     
    </div>
</form>

<div class="offcanvas offcanvas-end p-20" tabindex="-1" id="bulkTeamHolidaysOffcanvas" aria-labelledby="bulkTeamHolidaysLabel">
    <div class="offcanvas-header">
        <h4 id="bulkTeamHolidaysLabel" class="text-primary"><?php _e("Feriados da equipe")?></h4>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body n-scroll">
        <div class="alert d-none" id="bulk-team-holidays-feedback"></div>

        <div class="card border b-r-6 mb-4">
            <div class="card-body">
                <div class="fw-600 text-gray-800 mb-3"><?php _e("Cadastrar ou editar feriado")?></div>
                <form id="bulk-team-holiday-form">
                    <input type="hidden" name="id" id="bulk-team-holiday-id" value="0">
                    <div class="mb-3">
                        <label class="form-label"><?php _e("Data do feriado")?></label>
                        <input type="date" class="form-control form-control-solid" name="holiday_date" id="bulk-team-holiday-date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php _e("Nome do feriado")?></label>
                        <input type="text" class="form-control form-control-solid" name="name" id="bulk-team-holiday-name" maxlength="191" placeholder="<?php _e("Ex.: Corpus Christi")?>" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm" id="bulk-team-holiday-submit"><?php _e("Salvar feriado")?></button>
                        <button type="button" class="btn btn-light btn-sm d-none" id="bulk-team-holiday-cancel"><?php _e("Cancelar edição")?></button>
                    </div>
                </form>
            </div>
        </div>

        <div>
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <div class="fw-600 text-gray-800"><?php _e("Calendário da equipe")?></div>
                <button type="button" class="btn btn-light btn-sm" id="bulk-team-holidays-refresh"><?php _e("Atualizar")?></button>
            </div>
            <div class="text-gray-600 fs-12 mb-3"><?php _e("Os feriados cadastrados aqui poderão ser reutilizados por todas as campanhas deste time que ativarem a opção de ignorar feriados.")?></div>
            <div id="bulk-team-holidays-empty" class="alert alert-light-info d-none mb-3"><?php _e("Nenhum feriado cadastrado para esta equipe até o momento.")?></div>
            <div id="bulk-team-holidays-list"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function(){
    Core.tagsinput();

    var cloudParallelUrl = '<?php _e(get_module_url("cloud_capabilities")) ?>';
    var teamHolidaysUrl = '<?php _e(get_module_url("team_holidays")) ?>';
    var saveTeamHolidayUrl = '<?php _e(get_module_url("save_team_holiday")) ?>';
    var deleteTeamHolidayUrl = '<?php _e(get_module_url("delete_team_holiday")) ?>';
    var cloudParallelPresets = <?php _ec(json_encode($cloud_parallel_presets)) ?>;
    var weekdayLabels = <?php _ec(json_encode($weekday_options)) ?>;
    var teamHolidaysState = <?php _ec(json_encode($team_holidays)) ?>;
    var scheduleSummaryEmptyText = <?php _ec(json_encode($schedule_summary_empty)) ?>;
    var cloudParallelRequest = null;
    var teamHolidaysRequest = null;
    var $cloudCard = $("#cloud-parallel-card");
    var $cloudSwitch = $("#cloud_parallel_enabled");
    var $cloudLevel = $("#cloud_parallel_level");
    var $cloudUnavailable = $("#cloud-parallel-unavailable");
    var $cloudSummary = $("#cloud-parallel-summary");
    var $cloudAccountWrap = $("#cloud-parallel-account-list-wrap");
    var $cloudAccountList = $("#cloud-parallel-account-list");
    var $bulkTimePost = $("#bulk_time_post");
    var $weekdayInputs = $(".bulk-weekday-input");
    var $scheduleTime = $(".schedule_time");
    var $skipTeamHolidays = $("#skip_team_holidays");
    var $scheduleSummary = $("#bulk-schedule-summary");
    var $holidayFeedback = $("#bulk-team-holidays-feedback");
    var $holidayList = $("#bulk-team-holidays-list");
    var $holidayEmpty = $("#bulk-team-holidays-empty");
    var $holidayForm = $("#bulk-team-holiday-form");
    var $holidayId = $("#bulk-team-holiday-id");
    var $holidayDate = $("#bulk-team-holiday-date");
    var $holidayName = $("#bulk-team-holiday-name");
    var $holidaySubmit = $("#bulk-team-holiday-submit");
    var $holidayCancel = $("#bulk-team-holiday-cancel");

    var parseAccountData = function(item){
        var accountData = item.data("account");
        if (typeof accountData === "string") {
            try {
                accountData = JSON.parse(accountData);
            } catch (e) {
                accountData = {};
            }
        }
        return accountData || {};
    };

    var removeSelectedAccountChip = function(accountIds){
        $(".am-selected-list .am-selected-item[data-id='"+accountIds+"']").remove();
        if ($(".am-selected-list .am-selected-item").length === 0) {
            $(".am-selected-empty").show();
        }
    };

    var escapeHtml = function(value){
        return $("<div>").text(value == null ? "" : String(value)).html();
    };

    var normalizeNumericList = function(values){
        return (values || []).map(function(value){
            return String(value);
        }).filter(function(value){
            return value !== '';
        }).sort(function(a, b){
            return parseInt(a, 10) - parseInt(b, 10);
        });
    };

    var formatHour = function(value){
        var number = parseInt(value, 10);
        if (isNaN(number)) {
            return String(value);
        }

        return String(number).padStart(2, '0');
    };

    var bulkDateTimeLocale = {
        closeText: 'Feito',
        prevText: 'Anterior',
        nextText: 'Próximo',
        currentText: 'Agora',
        monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
        monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        dayNames: ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'],
        dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
        dayNamesMin: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
        weekHeader: 'Sem',
        dateFormat: 'dd/mm/yy',
        firstDay: 0,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: '',
        amNames: ['AM', 'A'],
        pmNames: ['PM', 'P'],
        timeOnlyTitle: 'Escolha o horário',
        timeText: 'Hora',
        hourText: 'Hora',
        minuteText: 'Minuto',
        secondText: 'Segundo',
        millisecText: 'Milissegundo',
        microsecText: 'Microssegundo',
        timezoneText: 'Fuso horário'
    };

    var initBulkTimePostPicker = function(){
        if (!$bulkTimePost.length || typeof $bulkTimePost.datetimepicker !== 'function') {
            return;
        }

        try {
            $bulkTimePost.datetimepicker('destroy');
        } catch (error) {
            // ignore previous init errors
        }

        $bulkTimePost.datetimepicker({
            controlType: 'select',
            oneLine: true,
            dateFormat: bulkDateTimeLocale.dateFormat,
            timeFormat: 'HH:mm',
            closeText: bulkDateTimeLocale.closeText,
            prevText: bulkDateTimeLocale.prevText,
            nextText: bulkDateTimeLocale.nextText,
            currentText: bulkDateTimeLocale.currentText,
            monthNames: bulkDateTimeLocale.monthNames,
            monthNamesShort: bulkDateTimeLocale.monthNamesShort,
            dayNames: bulkDateTimeLocale.dayNames,
            dayNamesShort: bulkDateTimeLocale.dayNamesShort,
            dayNamesMin: bulkDateTimeLocale.dayNamesMin,
            weekHeader: bulkDateTimeLocale.weekHeader,
            firstDay: bulkDateTimeLocale.firstDay,
            isRTL: bulkDateTimeLocale.isRTL,
            showMonthAfterYear: bulkDateTimeLocale.showMonthAfterYear,
            yearSuffix: bulkDateTimeLocale.yearSuffix,
            timeOnlyTitle: bulkDateTimeLocale.timeOnlyTitle,
            timeText: bulkDateTimeLocale.timeText,
            hourText: bulkDateTimeLocale.hourText,
            minuteText: bulkDateTimeLocale.minuteText,
            secondText: bulkDateTimeLocale.secondText,
            millisecText: bulkDateTimeLocale.millisecText,
            microsecText: bulkDateTimeLocale.microsecText,
            timezoneText: bulkDateTimeLocale.timezoneText,
            beforeShow: function () {
                $('.ui-datepicker-wrap').addClass('active');
            },
            onClose: function () {
                $('.ui-datepicker-wrap').removeClass('active');
            }
        });

        if (!$bulkTimePost.val()) {
            var defaultStart = new Date();
            defaultStart.setMinutes(defaultStart.getMinutes() + 10);
            defaultStart.setSeconds(0);
            defaultStart.setMilliseconds(0);

            var roundedMinutes = Math.ceil(defaultStart.getMinutes() / 5) * 5;
            if (roundedMinutes === 60) {
                defaultStart.setHours(defaultStart.getHours() + 1);
                defaultStart.setMinutes(0);
            } else {
                defaultStart.setMinutes(roundedMinutes);
            }

            $bulkTimePost.datetimepicker('setDate', defaultStart);
        }
    };

    var describeWeekdays = function(values){
        var normalized = normalizeNumericList(values);
        if (!normalized.length) {
            return 'Todos os dias';
        }

        if (normalized.join(',') === '1,2,3,4,5') {
            return 'Seg-Sex';
        }

        if (normalized.join(',') === '6,7') {
            return 'Sáb-Dom';
        }

        if (normalized.join(',') === '1,2,3,4,5,6,7') {
            return 'Todos os dias';
        }

        return normalized.map(function(value){
            return weekdayLabels[value] ? weekdayLabels[value].short : value;
        }).join(', ');
    };

    var describeHours = function(values){
        var normalized = normalizeNumericList(values);
        if (!normalized.length) {
            return 'Qualquer horário';
        }

        return normalized.map(formatHour).join(',');
    };

    var buildBulkScheduleSummary = function(weekdays, hours, skipHolidays){
        if (!weekdays.length && !hours.length && !skipHolidays) {
            return scheduleSummaryEmptyText;
        }

        var parts = [
            describeWeekdays(weekdays),
            describeHours(hours)
        ];

        if (skipHolidays) {
            parts.push('Feriados ON');
        }

        return parts.join(' | ');
    };

    var updateBulkScheduleSummary = function(){
        var weekdays = $weekdayInputs.filter(':checked').map(function(){
            return $(this).val();
        }).get();
        var hours = $scheduleTime.val() || [];
        var skipHolidays = $skipTeamHolidays.is(':checked');

        $scheduleSummary.text(buildBulkScheduleSummary(weekdays, hours, skipHolidays));
    };

    var resetHolidayForm = function(){
        $holidayId.val('0');
        $holidayDate.val('');
        $holidayName.val('');
        $holidaySubmit.text('<?php _e("Salvar feriado") ?>');
        $holidayCancel.addClass('d-none');
    };

    var showHolidayFeedback = function(type, message){
        var cssClass = type === 'success' ? 'alert-success' : 'alert-danger';
        $holidayFeedback
            .removeClass('d-none alert-success alert-danger')
            .addClass(cssClass)
            .text(message || '');
    };

    var clearHolidayFeedback = function(){
        $holidayFeedback.removeClass('alert-success alert-danger').addClass('d-none').text('');
    };

    var sortTeamHolidays = function(holidays){
        return (holidays || []).slice().sort(function(a, b){
            return String(a.holiday_date || '').localeCompare(String(b.holiday_date || ''));
        });
    };

    var renderTeamHolidays = function(holidays){
        teamHolidaysState = sortTeamHolidays(holidays);

        if (!teamHolidaysState.length) {
            $holidayList.empty();
            $holidayEmpty.removeClass('d-none');
            return;
        }

        $holidayEmpty.addClass('d-none');

        var html = teamHolidaysState.map(function(holiday){
            var displayDate = holiday.label || holiday.holiday_date || '';
            return [
                '<div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-start gap-3">',
                    '<div>',
                        '<div class="fw-600 text-gray-800">' + escapeHtml(displayDate) + '</div>',
                        '<div class="text-gray-700 fs-13">' + escapeHtml(holiday.name || '') + '</div>',
                    '</div>',
                    '<div class="d-flex gap-2">',
                        '<button type="button" class="btn btn-light-primary btn-sm team-holiday-edit" data-id="' + escapeHtml(holiday.id) + '" data-date="' + escapeHtml(holiday.holiday_date || '') + '" data-name="' + escapeHtml(holiday.name || '') + '"><?php _e("Editar") ?></button>',
                        '<button type="button" class="btn btn-light-danger btn-sm team-holiday-delete" data-id="' + escapeHtml(holiday.id) + '"><?php _e("Excluir") ?></button>',
                    '</div>',
                '</div>'
            ].join('');
        }).join('');

        $holidayList.html(html);
    };

    var fetchTeamHolidays = function(options){
        options = options || {};

        if (teamHolidaysRequest && typeof teamHolidaysRequest.abort === 'function') {
            teamHolidaysRequest.abort();
        }

        if (options.silent !== true) {
            clearHolidayFeedback();
        }

        teamHolidaysRequest = $.ajax({
            url: teamHolidaysUrl,
            type: 'GET',
            dataType: 'json'
        }).done(function(response){
            var holidays = response && response.data && $.isArray(response.data.holidays) ? response.data.holidays : [];
            renderTeamHolidays(holidays);
        }).fail(function(xhr, status){
            if (status === 'abort') {
                return;
            }

            showHolidayFeedback('error', '<?php _e("Não foi possível carregar o calendário de feriados da equipe.") ?>');
        });
    };

    var applyWeekdaySelection = function(values){
        var selection = normalizeNumericList(values);
        $weekdayInputs.each(function(){
            $(this).prop('checked', selection.indexOf($(this).val()) !== -1);
        });
        updateBulkScheduleSummary();
    };

    var getSelectedAccountIds = function(){
        return $(".am-choice-body .check-item:checked").map(function(){
            return $(this).val();
        }).get();
    };

    var setCloudParallelUnavailable = function(message){
        $cloudUnavailable.text(message || '').toggleClass('d-none', !message);
    };

    var setCloudParallelSummary = function(message){
        $cloudSummary.html(message || '').toggleClass('d-none', !message);
    };

    var renderCloudParallelAccountList = function(accounts){
        if (!accounts || !accounts.length) {
            $cloudAccountWrap.addClass('d-none');
            $cloudAccountList.empty();
            return;
        }

        var html = accounts.map(function(account){
            var parts = [];
            parts.push('<strong>' + escapeHtml(account.name || 'Cloud API') + '</strong>');
            if (account.display_phone_number) {
                parts.push(escapeHtml(account.display_phone_number));
            }
            if (account.throughput_level) {
                parts.push('capacidade ' + escapeHtml(account.throughput_level));
            } else {
                parts.push('capacidade fallback');
            }
            parts.push('limite ' + escapeHtml(account.cap || 0));
            return '<div class="mb-1">' + parts.join(' <span class="text-gray-500">|</span> ') + '</div>';
        }).join('');

        $cloudAccountList.html(html);
        $cloudAccountWrap.removeClass('d-none');
    };

    var syncCloudLevelOptions = function(allowedLevels){
        allowedLevels = $.isArray(allowedLevels) ? allowedLevels.map(function(level){
            return parseInt(level, 10);
        }) : [];

        $cloudLevel.find('option').each(function(){
            var $option = $(this);
            var value = parseInt($option.val(), 10);
            if (!value) {
                $option.prop('disabled', false).removeClass('d-none');
                return;
            }

            var enabled = allowedLevels.indexOf(value) !== -1;
            $option.prop('disabled', !enabled);
            if (value >= 90) {
                $option.toggleClass('d-none', !enabled);
            } else {
                $option.removeClass('d-none');
            }
        });

        var currentValue = parseInt($cloudLevel.val(), 10);
        if (currentValue && allowedLevels.indexOf(currentValue) === -1) {
            $cloudLevel.val('');
        }

        if (!$cloudLevel.val() && $cloudSwitch.is(':checked') && allowedLevels.length) {
            $cloudLevel.val(String(allowedLevels[0]));
        }
    };

    var setCloudParallelControlsEnabled = function(enabled){
        $cloudSwitch.prop('disabled', !enabled);
        $cloudLevel.prop('disabled', !enabled || !$cloudSwitch.is(':checked'));
    };

    var resetCloudParallelUi = function(){
        setCloudParallelUnavailable('');
        setCloudParallelSummary('');
        renderCloudParallelAccountList([]);
        syncCloudLevelOptions([]);
    };

    var fetchCloudCapabilities = function(){
        var isCallCampaign = $("input[name='type']:checked").val() === "7";
        var selectedAccounts = getSelectedAccountIds();

        if (cloudParallelRequest && typeof cloudParallelRequest.abort === 'function') {
            cloudParallelRequest.abort();
        }

        if (isCallCampaign) {
            $cloudCard.addClass('d-none');
            resetCloudParallelUi();
            $cloudSwitch.prop('checked', false);
            setCloudParallelControlsEnabled(false);
            return;
        }

        $cloudCard.removeClass('d-none');

        if (!selectedAccounts.length) {
            resetCloudParallelUi();
            $cloudSwitch.prop('checked', false);
            setCloudParallelUnavailable('<?php _e("Selecione pelo menos uma conta do WhatsApp para avaliar a capacidade paralela da Cloud API.") ?>');
            setCloudParallelControlsEnabled(false);
            return;
        }

        setCloudParallelControlsEnabled(false);
        setCloudParallelUnavailable('');
        setCloudParallelSummary('<?php _e("Consultando a capacidade da Cloud API para as contas selecionadas...") ?>');

        cloudParallelRequest = $.ajax({
            url: cloudParallelUrl,
            type: 'POST',
            dataType: 'json',
            data: { accounts: selectedAccounts }
        }).done(function(response){
            var data = response && response.data ? response.data : {};
            var allowedLevels = $.isArray(data.allowed_levels) ? data.allowed_levels : [];
            var aggregateCap = parseInt(data.aggregate_cap || 0, 10);

            if (!data.all_cloud) {
                resetCloudParallelUi();
                $cloudSwitch.prop('checked', false);
                setCloudParallelUnavailable('<?php _e("O modo simultâneo da Cloud API só fica disponível quando todas as contas selecionadas são Cloud API. Campanhas mistas voltam automaticamente para o fluxo serial original.") ?>');
                setCloudParallelControlsEnabled(false);
                return;
            }

            syncCloudLevelOptions(allowedLevels);
            renderCloudParallelAccountList(data.accounts || []);
            setCloudParallelSummary('<?php _e("Capacidade segura agregada") ?>: <strong>' + aggregateCap + '</strong> <?php _e("envios simultâneos por onda para esta campanha.") ?>');

            if (!allowedLevels.length) {
                $cloudSwitch.prop('checked', false);
                setCloudParallelUnavailable('<?php _e("Nenhum nível paralelo seguro da Cloud API está disponível no momento para as contas selecionadas.") ?>');
                setCloudParallelControlsEnabled(false);
                return;
            }

            setCloudParallelControlsEnabled(true);
        }).fail(function(xhr, status){
            if (status === 'abort') {
                return;
            }

            resetCloudParallelUi();
            $cloudSwitch.prop('checked', false);
            setCloudParallelUnavailable('<?php _e("Falha ao consultar a capacidade da Cloud API. A campanha continuará no fluxo serial original até que as contas selecionadas possam ser validadas novamente.") ?>');
            setCloudParallelControlsEnabled(false);
        });
    };

    var syncCallCampaignAccounts = function(){
        var isCallCampaign = $("input[name='type']:checked").val() === "7";
        $("#call-campaign-account-hint").toggleClass("d-none", !isCallCampaign);

        $(".am-choice-body .search-accounts").each(function(){
            var wrapper = $(this);
            var choiceItem = wrapper.find(".am-choice-item");
            var input = choiceItem.find("input.check-item");
            var accountData = parseAccountData(choiceItem);
            var isBaileys = parseInt(accountData.login_type || 0, 10) === 2 || parseInt(accountData.login_type || 0, 10) === 3;

            if (isCallCampaign && !isBaileys) {
                if (input.is(":checked")) {
                    input.prop("checked", false);
                    removeSelectedAccountChip(input.val());
                }
                input.prop("disabled", true);
                wrapper.addClass("d-none");
            } else {
                input.prop("disabled", false);
                wrapper.removeClass("d-none");
            }
        });

        fetchCloudCapabilities();
    };

    <?php if ( get_data($result, "accounts") != ""): ?>
        var accounts = <?php _ec( get_data($result, "accounts") )?>;
        for (var i = 0; i < accounts.length; i++) {
            Account_manager.CheckAndSelect(  $('input#am_'+accounts[i]).parents(".am-choice-item") );
        }
    <?php endif ?>

    renderTeamHolidays(teamHolidaysState);
    initBulkTimePostPicker();
    updateBulkScheduleSummary();
    syncCallCampaignAccounts();
    setCloudParallelControlsEnabled(false);

    $(document).on("change", "input[name='type']", function(){
        syncCallCampaignAccounts();
    });

    $(document).on("change", ".am-choice-body .check-item, .am-list-account .check-box-all", function(){
        setTimeout(syncCallCampaignAccounts, 0);
    });

    $(document).on('change', '#cloud_parallel_enabled', function(){
        $cloudLevel.prop('disabled', !$(this).is(':checked') || $(this).is(':disabled'));
        if ($(this).is(':checked') && !$cloudLevel.val()) {
            var firstEnabled = $cloudLevel.find('option:not([disabled])[value!=""]').first().val();
            if (firstEnabled) {
                $cloudLevel.val(firstEnabled);
            }
        }
    });

    $(document).on('change', '.bulk-weekday-input, .schedule_time, #skip_team_holidays', function(){
        updateBulkScheduleSummary();
    });

    $(document).on('click', '.bulk-weekday-preset', function(){
        applyWeekdaySelection(String($(this).data('weekdays') || '').split(','));
    });

    $(document).on('click', '.bulk-weekday-clear', function(){
        applyWeekdaySelection([]);
    });

    $(document).on('show.bs.offcanvas', '#bulkTeamHolidaysOffcanvas', function(){
        fetchTeamHolidays({ silent: true });
    });

    $(document).on('click', '#bulk-team-holidays-refresh', function(){
        fetchTeamHolidays();
    });

    $(document).on('click', '#bulk-team-holiday-cancel', function(){
        clearHolidayFeedback();
        resetHolidayForm();
    });

    $(document).on('submit', '#bulk-team-holiday-form', function(e){
        e.preventDefault();
        clearHolidayFeedback();

        $.ajax({
            url: saveTeamHolidayUrl,
            type: 'POST',
            dataType: 'json',
            data: $holidayForm.serialize()
        }).done(function(response){
            var holidays = response && response.data && $.isArray(response.data.holidays) ? response.data.holidays : [];
            renderTeamHolidays(holidays);
            showHolidayFeedback('success', response.message || '<?php _e("Feriado salvo com sucesso.") ?>');
            resetHolidayForm();
        }).fail(function(xhr){
            var response = xhr && xhr.responseJSON ? xhr.responseJSON : {};
            showHolidayFeedback('error', response.message || '<?php _e("Não foi possível salvar o feriado da equipe.") ?>');
        });
    });

    $(document).on('click', '.team-holiday-edit', function(){
        clearHolidayFeedback();
        $holidayId.val(String($(this).data('id') || '0'));
        $holidayDate.val(String($(this).data('date') || ''));
        $holidayName.val(String($(this).data('name') || ''));
        $holidaySubmit.text('<?php _e("Atualizar feriado") ?>');
        $holidayCancel.removeClass('d-none');
        $holidayDate.trigger('focus');
    });

    function deleteTeamHoliday(id) {
        var actionDialog = null;
        if (typeof Core !== 'undefined' && typeof Core.showActionDialog === 'function') {
            actionDialog = Core.showActionDialog({
                type: 'delete',
                icon: 'fad fa-calendar-times',
                title: '<?php _e("Excluindo feriado") ?>',
                message: '<?php _e("Estamos removendo o feriado do calendário da equipe.") ?>'
            });
        }

        clearHolidayFeedback();

        $.ajax({
            url: deleteTeamHolidayUrl,
            type: 'POST',
            dataType: 'json',
            data: { id: id }
        }).done(function(response){
            var holidays = response && response.data && $.isArray(response.data.holidays) ? response.data.holidays : [];
            renderTeamHolidays(holidays);
            showHolidayFeedback('success', response.message || '<?php _e("Feriado removido com sucesso.") ?>');
            if (actionDialog && typeof Core.finishActionDialog === 'function') {
                Core.finishActionDialog('success', response.message || '<?php _e("Feriado removido com sucesso.") ?>', actionDialog);
            }
            if (String($holidayId.val()) === String(id)) {
                resetHolidayForm();
            }
        }).fail(function(xhr){
            var response = xhr && xhr.responseJSON ? xhr.responseJSON : {};
            showHolidayFeedback('error', response.message || '<?php _e("Não foi possível remover o feriado da equipe.") ?>');
            if (actionDialog && typeof Core.finishActionDialog === 'function') {
                Core.finishActionDialog('error', response.message || '<?php _e("Não foi possível remover o feriado da equipe.") ?>', actionDialog);
            }
        });
    }

    function showBulkConfirmDialog(options) {
        if (typeof Core !== 'undefined' && typeof Core.showConfirmDialog === 'function') {
            Core.showConfirmDialog(options);
            return;
        }

        if (window.confirm(options.message || '<?php _e("Tem certeza que deseja continuar?") ?>') && typeof options.onConfirm === 'function') {
            options.onConfirm();
        }
    }

    $(document).on('click', '.team-holiday-delete', function(){
        var id = parseInt($(this).data('id') || 0, 10);
        if (!id) {
            return;
        }

        showBulkConfirmDialog({
            title: '<?php _e("Excluir feriado da equipe") ?>',
            message: '<?php _e("Tem certeza que deseja excluir este feriado da equipe?") ?>',
            confirmText: '<?php _e("Excluir feriado") ?>',
            readyHint: '<?php _e("Se estiver tudo certo, confirme para remover este feriado da equipe.") ?>',
            onConfirm: function(){
                deleteTeamHoliday(id);
            }
        });
    });
});
</script> 
