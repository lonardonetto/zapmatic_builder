<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<div class="container">
    <div class="row justify-content-center mt-5 mb-3">
        <div class="col-xl-10 col-lg-11">
            <div class="central-hero">
                <div>
                    <div class="central-eyebrow"><?php _e('Central de Conexão') ?></div>
                    <h1 class="central-title"><?php _ec($page_title ?? 'Central de Conexão WhatsApp') ?></h1>
                    <p class="central-subtitle mb-0"><?php _ec($page_subtitle ?? 'Gerencie conexões Baileys, Cloud API e Whatsmeow em um único lugar.') ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php $show_embedded_signup = !empty($cloud_api_embedded_signup_enabled); ?>

    <div class="row justify-content-center mt-3">
        <div class="col-xl-10 col-lg-11">
            <div class="wa-connection-switchboard">
                <div class="wa-connection-switchboard-copy">
                    <div class="wa-connection-switchboard-title"><?php _e('Conectar nova conta') ?></div>
                    <div class="wa-connection-switchboard-text"><?php _e('Escolha o tipo de conexão e abra o fluxo em um painel lateral, sem empurrar os perfis para baixo.') ?></div>
                </div>
                <div class="wa-connection-switchboard-actions">
                    <button type="button" class="btn btn-success rounded-pill px-4 js-open-connection-drawer" data-connection-drawer-target="baileys">
                        <i class="fas fa-qrcode me-2"></i><?php _e('Baileys') ?>
                    </button>

                    <a href="<?php _ec(base_url('whatsapp_profiles/oauth?open=whatsmeow')) ?>" class="btn btn-info rounded-pill px-4 js-progress-navigation" data-progress-title="<?php _e('Abrindo Whatsmeow') ?>" data-progress-detail="<?php _e('Nenhuma instância será criada até iniciar a conexão.') ?>" data-progress-duration="800">
                        <i class="fab fa-golang me-2"></i><?php _e('Whatsmeow') ?>
                    </a>

                    <?php if ((int)permission("cloud_api_enabled") == 1): ?>
                        <button type="button" class="btn btn-primary rounded-pill px-4 js-open-connection-drawer" data-connection-drawer-target="cloud">
                            <i class="fas fa-cloud me-2"></i><?php _e('Cloud API') ?>
                        </button>

                        <button type="button" class="btn btn-light-primary rounded-pill px-4 js-open-connection-drawer" data-connection-drawer-target="cloud" data-cloud-manual="1">
                            <i class="fas fa-sliders-h me-2"></i><?php _e('Configuração manual') ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

            <div id="waConnectionDrawer" class="wa-connection-drawer<?php _e(!empty($show_baileys_qr) || !empty($show_whatsmeow_qr) || !empty($open_whatsmeow_drawer) ? ' is-open' : '') ?>" data-default-view="<?php _e(!empty($show_whatsmeow_qr) || !empty($open_whatsmeow_drawer) ? 'whatsmeow' : (!empty($show_baileys_qr) ? 'baileys' : '')) ?>">
        <div class="wa-connection-drawer-backdrop" data-close-connection-drawer></div>
        <div class="wa-connection-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="waConnectionDrawerTitle">
            <div class="wa-connection-drawer-header">
                <div>
                    <div class="wa-connection-drawer-eyebrow"><?php _e('Fluxo de conexão') ?></div>
                    <h2 class="wa-connection-drawer-title" id="waConnectionDrawerTitle"><?php _e('Escolha e conclua a conexão') ?></h2>
                    <p class="wa-connection-drawer-subtitle mb-0"><?php _e('Abra Baileys ou Cloud API em um painel lateral, mantendo a visão dos perfis sempre limpa no topo.') ?></p>
                </div>
                <button type="button" class="btn btn-light-dark btn-sm rounded-pill px-3" data-close-connection-drawer>
                    <i class="fas fa-times me-1"></i><?php _e('Fechar') ?>
                </button>
            </div>

            <div class="wa-connection-drawer-tabs">
                <button type="button" class="wa-connection-drawer-tab js-open-connection-drawer" data-connection-drawer-target="baileys">
                    <i class="fas fa-qrcode me-2"></i><?php _e('Baileys') ?>
                </button>
                <button type="button" class="wa-connection-drawer-tab js-open-connection-drawer" data-connection-drawer-target="whatsmeow">
                    <i class="fab fa-golang me-2"></i><?php _e('Whatsmeow') ?>
                </button>
                <?php if ((int)permission("cloud_api_enabled") == 1): ?>
                    <button type="button" class="wa-connection-drawer-tab js-open-connection-drawer" data-connection-drawer-target="cloud">
                        <i class="fas fa-cloud me-2"></i><?php _e('Cloud API') ?>
                    </button>
                <?php endif; ?>
            </div>

            <div class="wa-connection-drawer-body">
                <div class="wa-connection-drawer-view<?php _e(!empty($show_baileys_qr) ? ' is-active' : '') ?>" data-drawer-view="baileys">

    <form class="actionForm" action="<?php _e( base_url("whatsapp_profiles/" . ( uri('segment', 3)=="unofficial"?"save_unofficial":"save" ) ) )?>" method="POST" data-redirect="<?php _e( base_url("account_manager") )?>">
    <div class="row justify-content-center mt-3">
        <div class="col-xl-10 col-lg-11">
            <div class="card mb-4 mb-xl-10">
                <div class="card-header border-0 pt-0">
                    <h5><i class="fas fa-qrcode me-2 text-success"></i><?php _e("Conexão Baileys")?></h5>
                </div>
                <div class="card-body">
                        <!-- Baileys Content -->
                        <div id="tab_baileys">
                            <?php if (check_number_account("whatsapp", "profile", false, false) || uri("segment", 3) == $instance_id): ?>
                            <div class="py-2 check-wrap-all">
                                <?php if (empty($show_baileys_qr)): ?>
                                <div class="baileys-launcher">
                                    <div class="baileys-launcher-copy">
                                        <div class="baileys-launcher-title"><?php _e('Preparar nova conexão') ?></div>
                                        <div class="baileys-launcher-text"><?php _e('O QR Code e a sessão Baileys só serão preparados quando você iniciar a conexão. Isso evita gerar instâncias desnecessárias ao abrir a Central.') ?></div>
                                        <?php if (!empty($pending_baileys_session) && !empty($pending_baileys_session->instance_id)): ?>
                                        <div class="baileys-pending-chip">
                                            <i class="fas fa-clock me-2"></i><?php _e('Há uma sessão pendente pronta para continuar:') ?> <strong class="ms-1"><?php _ec($pending_baileys_session->instance_id) ?></strong>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="baileys-launcher-actions">
                                        <a href="<?php _ec($baileys_connect_url ?? base_url('whatsapp_profiles/oauth?connect=baileys')) ?>" class="btn btn-success rounded-pill px-4 js-progress-navigation" data-progress-title="<?php _e('Preparando conexão Baileys') ?>" data-progress-detail="<?php _e('Estamos preparando a sessão e abrindo o fluxo de autenticação.') ?>" data-progress-duration="3000">
                                            <i class="fas fa-qrcode me-2"></i><?php _e(!empty($pending_baileys_session) && !empty($pending_baileys_session->instance_id) ? 'Continuar conexão' : 'Iniciar conexão Baileys') ?>
                                        </a>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="border b-r-10 p-20 mb-4">
                                    <div class="fs-16 fw-6 d-flex flex-wrap align-items-center gap-2"><span><i class="fad fa-key"></i> <?php _e("ID da instância:")?> <span class="text-success"><?php _ec($instance_id)?></span></span><a href="<?php _ec( base_url("whatsapp_profiles/generate_instance") )?>" class="btn btn-outline btn-outline-dashed bg-white js-progress-navigation" data-progress-title="<?php _e('Gerando nova instância') ?>" data-progress-detail="<?php _e('Estamos renovando a sessão para criar um novo QR Code.') ?>" data-progress-duration="3000">
                                        <i class="fas fa-random text-success" style="margin-right:5px;"></i> <?php _e("Gerar Nova Instância")?>
                                    </a><a href="<?php _ec(base_url('whatsapp_profiles/oauth')) ?>" class="btn btn-light btn-sm rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i><?php _e('Voltar para a Central') ?></a>
                                    </div>
                                    <?php if(get_option('wa_paircode') == 0) {?>
                                    <div class="text-gray-600"><?php _e("Escaneie o QR Code no seu aplicativo WhatsApp")?></div>
                                    <?php } else { ?>
                                    <div class="text-gray-600"><?php _e("Escaneie o QR Code no seu aplicativo WhatsApp")?> <?php _e('ou use o código abaixo:');?></div>
                                    
                                    <p></p>
                                    <button type="button" class="btn btn-outline btn-outline-dashed bg-white" data-bs-toggle="modal" data-bs-target="#PairingCodeModal"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>"></i> <?php _e("Conecte via código")?></button></i>
                                    <?php } ?>
                                </div>

                                <div class="text-center wa-qr-code" data-instance-id="<?php _ec($instance_id)?>">
                                    <?php if($has_pair == false){ ?>
                                    <div class="wa-code text-center">
                                        
                                        <div class="w-300 h-300 d-flex justify-content-center align-items-center fs-60 m-auto border b-r-10 text-dark">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                                    <?php }else { ?>
                                    <div class="border b-r-10 p-20 text-center">
                                        <?php if($pair_code != "" && $has_error == false){ ?>
                                        <h5><?php _ec($pair_code);?></h5>
                                        <?php } else { ?>
                                        <div class="alert alert-danger">
                                            <?php _e($error_msg);?>
                                        </div>
                                        <?php } ?>
                                        </div>
                                    <?php } ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                                <?php $number_accounts = (int)permission("number_accounts"); ?>
                                <div class="alert alert-danger d-flex align-items-center">
                                    <div class="fs-40 me-3"><i class="fad fa-exclamation-circle"></i></div>
                                    <div>
                                        <div class="fw-bold"><?php _e("Limit number of accounts")?></div>
                                        <?php _e( sprintf(__("You can only add up to %s Whatsapp profiles"), $number_accounts ) )?>
                                    </div>
                                </div>
                            <?php endif ?>
                        </div>
                </div>
            </div>
        </div>
    </div>
    </form>
                </div>

    <!-- Whatsmeow Gateway -->
                <div class="wa-connection-drawer-view<?php _e(!empty($show_whatsmeow_qr) || !empty($open_whatsmeow_drawer) ? ' is-active' : '') ?>" data-drawer-view="whatsmeow">
        <form class="actionForm" action="<?php _e( base_url("whatsapp_profiles/" . ( uri('segment', 3)=="unofficial"?"save_unofficial":"save" ) ) )?>" method="POST" data-redirect="<?php _e( base_url("account_manager") )?>">
        <div class="row justify-content-center mt-3">
            <div class="col-xl-10 col-lg-11">
            <div class="card mb-4 mb-xl-10">
                <div class="card-header border-0 pt-0">
                    <h5><i class="fab fa-golang me-2 text-info"></i><?php _e("Conexão Whatsmeow (Go)")?></h5>
                </div>
                <div class="card-body">
                    <div id="tab_whatsmeow">
                        <?php if (check_number_account("whatsapp", "profile", false, false)): ?>
                        <div class="py-2 check-wrap-all">
                            <?php if (empty($show_whatsmeow_qr)): ?>
                            <div class="whatsmeow-launcher">
                                <div class="whatsmeow-launcher-copy">
                                    <div class="whatsmeow-launcher-title"><?php _e('Preparar nova conexão Whatsmeow') ?></div>
                                    <div class="whatsmeow-launcher-text"><?php _e('O QR Code será gerado quando você iniciar a conexão.') ?></div>
                                </div>
                                <div class="whatsmeow-launcher-actions">
                                    <a href="<?php _ec(base_url('whatsapp_profiles/generate_whatsmeow_instance')) ?>" class="btn btn-info rounded-pill px-4 js-progress-navigation" data-progress-title="<?php _e('Preparando conexão Whatsmeow') ?>" data-progress-detail="<?php _e('Gerando QR via gateway Go.') ?>" data-progress-duration="3000">
                                        <i class="fab fa-golang me-2"></i><?php _e('Iniciar conexão Whatsmeow') ?>
                                    </a>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="border b-r-10 p-20 mb-4">
                                <div class="fs-16 fw-6 d-flex flex-wrap align-items-center gap-2">
                                    <span><i class="fad fa-key"></i> <?php _e("ID da instância:")?> <span class="text-info"><?php _ec($whatsmeow_instance_id)?></span></span>
                                    <a href="<?php _ec(base_url('whatsapp_profiles/generate_whatsmeow_instance')) ?>" class="btn btn-outline btn-outline-dashed bg-white js-progress-navigation" data-progress-title="<?php _e('Gerando nova instância') ?>" data-progress-detail="<?php _e('Preparando nova sessão Whatsmeow.') ?>" data-progress-duration="3000">
                                        <i class="fas fa-random text-info" style="margin-right:5px;"></i> <?php _e("Gerar Nova Instância")?>
                                    </a>
                                    <a href="<?php _ec(base_url('whatsapp_profiles/oauth')) ?>" class="btn btn-light btn-sm rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i><?php _e('Voltar para a Central') ?></a>
                                </div>
                                <div class="text-gray-600"><?php _e("Escaneie o QR Code ou autentique com biometria (passkey) no seu aplicativo WhatsApp")?></div>
                            </div>

                            <div class="text-center wa-qr-code" data-instance-id="<?php _ec($whatsmeow_instance_id)?>">
                                <div class="wa-code text-center">
                                    <div class="w-300 h-300 d-flex justify-content-center align-items-center fs-60 m-auto border b-r-10 text-dark">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <div class="fs-40 me-3"><i class="fad fa-exclamation-circle"></i></div>
                            <div>
                                <div class="fw-bold"><?php _e("Limit number of accounts")?></div>
                                <?php _e( sprintf(__("You can only add up to %s Whatsapp profiles"), $number_accounts ) )?>
                            </div>
                        </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            </div>
        </div>
        </form>
    </div>

    <!-- Embedded Signup - Conexão Automática -->
                <div class="wa-connection-drawer-view" data-drawer-view="cloud">
    <?php if ((int)permission("cloud_api_enabled") == 1): ?>
    <div class="row justify-content-center mt-3">
        <div class="col-xl-10 col-lg-11">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-cloud me-2 text-primary"></i><?php _e("Conexão Cloud API")?></h5>
                </div>
                <div class="card-body">
                    <?php if (check_number_account("whatsapp", "profile", false, false)): ?>
                    
                    <?php if ($show_embedded_signup): ?>
                    <!-- Botão Embedded Signup -->
                    <div class="text-center mb-4">
                        <div class="border b-r-10 p-20 mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e8f4fd 100%);">
                            <div class="mb-3">
                                <i class="fab fa-facebook fs-40 text-primary"></i>
                            </div>
                            <h6 class="fw-bold mb-2"><?php _e("Conexão Automática com a Meta")?></h6>
                            <p class="text-muted fs-13 mb-4"><?php _e("Conecte seu WhatsApp Business com apenas um clique. Nós cuidamos de toda a configuração automaticamente.")?></p>
                            
                            <button type="button" id="btn-embedded-signup" class="btn btn-primary btn-lg" onclick="launchEmbeddedSignup()">
                                <i class="fab fa-whatsapp me-2"></i> <?php _e("Conectar com Meta")?>
                            </button>
                            
                            <div id="embedded-loading" class="d-none mt-3">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="text-muted mt-2 fs-13"><?php _e("Processando conexão... Aguarde.")?></p>
                            </div>
                        </div>

                        <div class="d-flex align-items-center my-3">
                            <hr class="flex-grow-1">
                            <span class="px-3 text-muted fs-12"><?php _e("ou configure manualmente")?></span>
                            <hr class="flex-grow-1">
                        </div>

                        <button type="button" class="btn btn-outline btn-outline-dashed bg-white btn-sm" data-bs-toggle="collapse" data-bs-target="#manualCloudForm">
                            <i class="fas fa-cog me-1"></i> <?php _e("Configuração Manual")?>
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Formulário Manual (colapsável) -->
                    <div<?php if ($show_embedded_signup): ?> class="collapse" id="manualCloudForm"<?php endif; ?>>
                        <form class="actionForm" action="<?php echo str_replace("http:", "https:", base_url("whatsapp_profiles/save_official")); ?>" method="POST" data-redirect="<?php _e( base_url("whatsapp_profiles/oauth") )?>" data-progress-title="<?php _e('Salvando conexão Cloud API') ?>" data-progress-detail="<?php _e('Estamos registrando os dados da sua conexão oficial com a Meta.') ?>" data-progress-duration="3000">
                                <div class="mb-10">
                                    <label class="form-label fw-bold"><?php _e("Nome do Perfil")?></label>
                                    <input type="text" class="form-control form-control-solid" name="name" placeholder="Ex: Meu Negócio" required>
                                </div>
                                <div class="row mb-10">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold"><?php _e("WABA ID")?></label>
                                        <input type="text" class="form-control form-control-solid" name="waba_id" placeholder="Ex: 123456789012345" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold"><?php _e("Phone Number ID")?></label>
                                        <input type="text" class="form-control form-control-solid" name="phone_number_id" placeholder="Ex: 1234567890" required>
                                    </div>
                                </div>
                                <div class="mb-10">
                                    <label class="form-label fw-bold"><?php _e("Access Token (Meta)")?></label>
                                    <textarea class="form-control form-control-solid" name="token" rows="3" placeholder="Insira o Token Permanente da Meta" required></textarea>
                                </div>
                                <div class="mb-10">
                                    <label class="form-label fw-bold"><?php _e("Verify Token")?></label>
                                    <?php 
                                        $temp_token = session()->get('temp_official_verify_token');
                                        if(!$temp_token){
                                            $temp_token = uniqid('zapmatic_');
                                            session()->set('temp_official_verify_token', $temp_token);
                                        }
                                    ?>
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-solid" name="verify_token" value="<?php _ec( $temp_token )?>" id="verify_token_cloud" required>
                                        <button class="btn btn-sm btn-light-primary" type="button" onclick="copyToClipboard('verify_token_cloud')"><i class="fas fa-copy"></i></button>
                                    </div>
                                    <small class="text-muted"><?php _e("Use este token na configuração de Webhook do Facebook Developers.")?></small>
                                </div>

                                <div class="alert alert-warning d-flex align-items-center mb-10">
                                    <div class="fs-24 me-3 text-warning"><i class="fas fa-exclamation-triangle"></i></div>
                                    <div>
                                        <strong><?php _e("IMPORTANTE:")?></strong> <?php _e("Você DEVE clicar no botão 'Salvar Perfil Cloud API' abaixo ANTES de tentar verificar e salvar no Painel de Desenvolvedor da Meta.")?>
                                    </div>
                                </div>
                                
                                <div class="mb-10 bg-light-success p-5 rounded border border-success border-dashed">
                                    <label class="form-label fw-bold text-success"><?php _e("Webhook URL")?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-transparent border-0" value="<?php _ec( base_url('whatsapp_webhook/index') )?>" readonly id="webhook_url_cloud">
                                        <button class="btn btn-sm btn-light-success" type="button" onclick="copyToClipboard('webhook_url_cloud')"><i class="fas fa-copy"></i></button>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <span class="indicator-label"><?php _e("Salvar Perfil Cloud API")?></span>
                                    </button>
                                </div>
                            </form>
                    </div>

                    <?php else: ?>
                        <?php $number_accounts = (int)permission("number_accounts"); ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <div class="fs-40 me-3"><i class="fad fa-exclamation-circle"></i></div>
                            <div>
                                <div class="fw-bold"><?php _e("Limit number of accounts")?></div>
                                <?php _e( sprintf(__("You can only add up to %s Whatsapp profiles"), $number_accounts ) )?>
                            </div>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

            <?php if (!empty($accounts)): ?>
            <?php
                $cloud_accounts_total = 0;
                $baileys_accounts_total = 0;
                $whatsmeow_accounts_total = 0;
                $offline_accounts_total = 0;
                foreach ($accounts as $account_counter) {
                    $lt = (int) ($account_counter->login_type ?? 0);
                    if ($lt === 1) {
                        $cloud_accounts_total++;
                    } elseif ($lt === 3) {
                        $whatsmeow_accounts_total++;
                    } else {
                        $baileys_accounts_total++;
                    }

                    if ((int) ($account_counter->status ?? 0) !== 1) {
                        $offline_accounts_total++;
                    }
                }
            ?>
            <div class="row justify-content-center mt-3">
                <div class="col-xl-10 col-lg-11">
                    <div class="card mb-4">
                        <div class="card-header border-0 pb-0">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                <div class="card-title mb-0"><i class="fad fa-grid-2 me-2" style="color: <?php _e($config['color'])?>"></i> <?php _ec("Contas Conectadas")?></div>
                                <div class="wa-summary-strip">
                                    <span class="wa-summary-pill"><?php _e('Total') ?> <?php _ec((string) count($accounts)) ?></span>
                                    <span class="wa-summary-pill"><?php _e('Cloud') ?> <?php _ec((string) $cloud_accounts_total) ?></span>
                                    <span class="wa-summary-pill"><?php _e('Baileys') ?> <?php _ec((string) $baileys_accounts_total) ?></span>
                                    <span class="wa-summary-pill wa-summary-pill-info"><?php _e('Whatsmeow') ?> <?php _ec((string) $whatsmeow_accounts_total) ?></span>
                                    <span class="wa-summary-pill wa-summary-pill-warning"><?php _e('Exigem atenção') ?> <?php _ec((string) $offline_accounts_total) ?></span>
                                </div>
                            </div>

                            <div class="wa-toolbar">
                                <div class="wa-search-wrap">
                                    <i class="fas fa-search wa-search-icon"></i>
                                    <input type="text" id="waConnectionSearch" class="form-control wa-search-input" placeholder="<?php _e('Buscar por nome ou número...') ?>">
                                </div>
                                <div class="wa-filter-pills" id="waConnectionFilters">
                                    <button type="button" class="wa-filter-pill active" data-filter="all"><?php _e('Todas') ?></button>
                                    <button type="button" class="wa-filter-pill" data-filter="cloud"><?php _e('Cloud API') ?></button>
                                    <button type="button" class="wa-filter-pill" data-filter="baileys"><?php _e('Baileys') ?></button>
                                    <button type="button" class="wa-filter-pill" data-filter="whatsmeow"><?php _e('Whatsmeow') ?></button>
                                    <button type="button" class="wa-filter-pill" data-filter="attention"><?php _e('Com alerta') ?></button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-4">
                            <div class="wa-accounts-grid">
                                <?php foreach ($accounts as $value): ?>
                                    <?php
                                        $login_type_val = (int) ($value->login_type ?? 0);
                                        $is_cloud = $login_type_val === 1;
                                        $is_whatsmeow = $login_type_val === 3;
                                        $is_connected = (int) ($value->status ?? 0) === 1;
                                        $display_name = trim((string) ($value->name ?? ''));
                                        if ($display_name === '') {
                                            $display_name = trim((string) ($value->pid ?? 'Perfil WhatsApp'));
                                        }

                                        $profile_type_label = $is_cloud ? 'Cloud API' : ($is_whatsmeow ? 'Go / Whatsmeow' : 'Baileys');
                                        $profile_type_color = $is_cloud ? 'success' : ($is_whatsmeow ? 'info' : 'primary');
                                        $profile_type_filter = $is_cloud ? 'cloud' : ($is_whatsmeow ? 'whatsmeow' : 'baileys');

                                        $avatar_url = get_file_url($value->avatar);
                                        $data_acc = $is_cloud ? json_decode($value->data) : null;
                                        $waba_id = $data_acc->waba_id ?? '';
                                        $phone_id = $data_acc->phone_number_id ?? '';
                                        $v_token = $data_acc->verify_token ?? '';
                                        $token_meta = $data_acc->token ?? '';
                                        $integration_access_token = $is_cloud ? (string) $token_meta : (string) get_team('ids');
                                        $integration_access_token_label = $is_cloud ? 'Access Token (Meta)' : 'Token de acesso';
                                    ?>
                                    <div class="wa-account-cell" data-account-type="<?php _ec($profile_type_filter) ?>" data-account-state="<?php _ec($is_connected ? 'connected' : 'attention') ?>" data-account-search="<?php _ec(strtolower($display_name . ' ' . $value->pid)) ?>">
                                        <div class="wa-account-tile" data-profile-id="<?php _ec($value->ids) ?>" data-profile-name="<?php _ec($display_name) ?>">
                                            <div class="wa-account-top">
                                                <div class="wa-account-identity">
                                                    <div class="wa-account-avatar">
                                                        <?php if (!empty($value->avatar)): ?>
                                                            <img src="<?php _ec($avatar_url) ?>" alt="<?php _ec($display_name) ?>">
                                                        <?php else: ?>
                                                            <span><i class="fab fa-whatsapp"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="wa-account-copy min-w-0">
                                                        <div class="wa-account-name" title="<?php _ec($display_name) ?>"><?php _ec($display_name) ?></div>
                                                        <div class="wa-account-subline"><?php _ec($value->pid) ?></div>
                                                    </div>
                                                </div>
                                                <span class="badge badge-light-<?php echo $profile_type_color ?> fs-10"><?php echo $profile_type_label ?></span>
                                            </div>

                                            <div class="wa-account-middle">
                                                <div id="status-<?php _ec($value->ids)?>" class="wa-status-slot">
                                                    <?php if (!$is_connected && !$is_cloud): ?>
                                                        <span class="wa-status-pill wa-status-pill-danger"><i class="fas fa-plug-circle-xmark me-1"></i><?php _e('Login necessário') ?></span>
                                                    <?php elseif ($is_connected): ?>
                                                        <span class="wa-status-pill wa-status-pill-success"><i class="fas fa-check-circle me-1"></i><?php _e('Conectado') ?></span>
                                                    <?php else: ?>
                                                        <span class="wa-status-pill wa-status-pill-warning"><i class="fas fa-exclamation-circle me-1"></i><?php _e('Revisar conexão') ?></span>
                                                    <?php endif ?>
                                                </div>

                                                <?php if ($is_cloud): ?>
                                                    <div id="cloud-health-<?php _ec($value->ids)?>" class="cloud-health-inline mt-2" data-cloud-health-id="<?php _ec($value->ids)?>">
                                                        <div class="cloud-health-loading"><?php _e('Consultando Cloud...') ?></div>
                                                    </div>
                                                <?php elseif ($is_whatsmeow): ?>
                                                    <div class="wa-local-note"><?php _e('Conexão via Whatsmeow (Go)') ?></div>
                                                <?php else: ?>
                                                    <div class="wa-local-note"><?php _e('Conexão local via Baileys') ?></div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="wa-account-footer">
                                                <div class="wa-account-primary-actions">
                                                    <?php if (!$is_connected && !$is_cloud): ?>
                                                        <a href="<?php echo base_url('whatsapp_profiles/oauth/' . $value->token); ?>" class="btn btn-success btn-sm rounded-pill px-3 js-progress-navigation" data-progress-title="<?php _e('Retomando conexão') ?>" data-progress-detail="<?php _e('Estamos abrindo a sessão deste perfil para concluir a autenticação.') ?>" data-progress-duration="3000">
                                                            <i class="fas fa-plug me-1"></i><?php _e("Conectar")?>
                                                        </a>
                                                    <?php elseif ($is_cloud): ?>
                                                        <a href="<?php _ec(base_url('whatsapp_profiles/cloud_health/' . $value->ids))?>" class="btn btn-light-warning btn-sm rounded-pill px-3" title="<?php _e("Abrir painel da Cloud")?>">
                                                            <i class="fas fa-chart-line me-1"></i><?php _e('Painel') ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="wa-local-chip"><?php _e('Sessão ativa') ?></span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="dropdown dropup wa-actions-dropdown">
                                                    <button class="btn btn-light-dark btn-sm rounded-pill px-3" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-h me-1"></i><?php _e('Ações') ?>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm wa-actions-menu">
                                                        <?php if (!$is_connected && !$is_cloud): ?>
                                                            <li><a class="dropdown-item js-progress-navigation" href="<?php echo base_url('whatsapp_profiles/oauth/' . $value->token); ?>" data-progress-title="<?php _e('Retomando conexão') ?>" data-progress-detail="<?php _e('Estamos abrindo a sessão deste perfil para concluir a autenticação.') ?>" data-progress-duration="3000"><i class="fas fa-plug text-success me-2"></i><?php _e('Conectar') ?></a></li>
                                                        <?php endif; ?>

                                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="editProfileName('<?php _ec($value->ids) ?>')"><i class="fas fa-pen text-primary me-2"></i><?php _e('Editar nome') ?></a></li>
                                                        <li><a class="dropdown-item js-open-integration-data" href="javascript:void(0);" data-profile-name="<?php _ec($display_name) ?>" data-profile-type="<?php _ec($profile_type_label) ?>" data-record-id="<?php _ec((string) ($value->ids ?? '')) ?>" data-instance-id="<?php _ec((string) ($value->token ?? '')) ?>" data-access-token="<?php _ec($integration_access_token) ?>" data-access-token-label="<?php _ec($integration_access_token_label) ?>" data-profile-pid="<?php _ec((string) ($value->pid ?? '')) ?>" data-phone-number-id="<?php _ec((string) $phone_id) ?>" data-waba-id="<?php _ec((string) $waba_id) ?>" data-verify-token="<?php _ec((string) $v_token) ?>"><i class="fas fa-fingerprint text-dark me-2"></i><?php _e('ID e token da integração') ?></a></li>

                                                        <?php if ($is_cloud): ?>
                                                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="testarConexaoCloud('<?php _ec($value->ids)?>', this)"><i class="fas fa-bolt text-primary me-2"></i><?php _e('Testar conexão') ?></a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sincronizarTemplates('<?php _ec($value->ids)?>', this)"><i class="fas fa-sync-alt text-info me-2"></i><?php _e('Sincronizar templates') ?></a></li>
                                                            <li><a class="dropdown-item" href="<?php _ec(base_url('whatsapp_profiles/cloud_health/' . $value->ids))?>"><i class="fas fa-chart-line text-warning me-2"></i><?php _e('Abrir painel da Cloud') ?></a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="editarPerfilCloud('<?php _ec($value->ids)?>', '<?php _ec($value->name)?>', '<?php _ec($waba_id)?>', '<?php _ec($phone_id)?>', '<?php _ec($token_meta)?>', '<?php _ec($v_token)?>')"><i class="fas fa-edit text-success me-2"></i><?php _e('Editar Cloud API') ?></a></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                        <?php endif; ?>

                                                        <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="desconectarPerfil('<?php echo htmlspecialchars($value->ids, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo base_url('whatsapp_profiles/disconnect'); ?>')"><i class="fas fa-trash-alt me-2"></i><?php _e('Excluir') ?></a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif ?>

            <div class="row justify-content-center mt-3">
                <div class="col-xl-10 col-lg-11">
                    <div class="card">
                        <div class="card-body">
                            <div class="note">
                                <div class="desc m-b-15"><?php _e("Se algum perfil não aparecer acima, tente reconectar, aceitar novamente as permissões e confirmar que você está logado no perfil correto.")?></div>
                                <a href="<?php _ec( base_url("whatsapp_profiles/oauth") )?>" class="btn btn-outline btn-outline-dashed bg-white"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>"></i> <?php _e("Reconectar WhatsApp")?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</div>

<!-- Import Chatbot Modal -->

<div class="modal fade" id="PairingCodeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php _ec("Conecte usando código 🤖") ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="PairingCodeModal" action="<?php _ec(base_url("whatsapp_profiles/oauth")) ?>" method="POST" data-redirect="" data-progress-title="<?php _e('Gerando código de pareamento') ?>" data-progress-detail="<?php _e('Estamos solicitando o código para continuar a conexão Baileys.') ?>" data-progress-duration="3000">
                    <div class="tab-pane fade show active p-50" id="PairingCodeModal_form">
                        <div class="col mb-3">
                            <input type="hidden" id="instance_id" name="instance_id" value="<?php _ec($instance_id)?>">
                            <label for="phone" class="form-label"><?php _e("📱 Número do WhatsApp") ?></label>
                            <input id="phone" type="text" class="form-control" name="phone"  required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block w-100">
                        <?php _e("Gerar código") ?>
                    </button>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Cloud API Modal -->
<div class="modal fade" id="EditCloudModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php _ec("Editar Perfil Cloud API ☁️") ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="actionForm" action="<?php echo base_url("whatsapp_profiles/update_official"); ?>" method="POST" data-redirect="<?php _e( base_url("whatsapp_profiles/oauth") )?>" data-progress-title="<?php _e('Atualizando conexão Cloud API') ?>" data-progress-detail="<?php _e('Estamos salvando as alterações deste perfil oficial.') ?>" data-progress-duration="3000">
                    <input type="hidden" name="ids" id="edit_cloud_ids">
                    <div class="mb-5">
                        <label class="form-label fw-bold"><?php _e("Nome do Perfil")?></label>
                        <input type="text" class="form-control" name="name" id="edit_cloud_name" required>
                    </div>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?php _e("WABA ID")?></label>
                            <input type="text" class="form-control" name="waba_id" id="edit_cloud_waba_id" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?php _e("Phone Number ID")?></label>
                            <input type="text" class="form-control" name="phone_number_id" id="edit_cloud_phone_id" required>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="form-label fw-bold"><?php _e("Access Token (Meta)")?></label>
                        <textarea class="form-control" name="token" id="edit_cloud_token" rows="3" required></textarea>
                    </div>
                    <div class="mb-5">
                        <label class="form-label fw-bold"><?php _e("Verify Token")?></label>
                        <input type="text" class="form-control" name="verify_token" id="edit_cloud_verify_token" required>
                    </div>
                    <div class="text-center mt-6">
                        <button type="submit" class="btn btn-primary w-100">
                            <?php _e("Salvar Alterações") ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="IntegrationDataModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php _e('ID e token da integração') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light-primary border border-primary border-dashed fs-13 mb-4">
                    <?php _e('Use estes dados para integrações internas. O ID interno do perfil é do sistema, o ID da instância é o identificador operacional da conexão e o token abaixo é o token real de acesso da integração.') ?>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e('Nome do perfil') ?></label>
                        <input type="text" class="form-control" id="integration_profile_name" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e('Tipo da conexão') ?></label>
                        <input type="text" class="form-control" id="integration_profile_type" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e('ID interno do perfil') ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="integration_record_id" readonly>
                            <button class="btn btn-light-primary" type="button" onclick="copyToClipboard('integration_record_id')"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e('ID da instância') ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="integration_instance_id" readonly>
                            <button class="btn btn-light-primary" type="button" onclick="copyToClipboard('integration_instance_id')"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold" id="integration_access_token_label"><?php _e('Token de acesso') ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="integration_access_token" readonly>
                            <button class="btn btn-light-primary" type="button" onclick="copyToClipboard('integration_access_token')"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e('Identificador / número') ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="integration_profile_pid" readonly>
                            <button class="btn btn-light-primary" type="button" onclick="copyToClipboard('integration_profile_pid')"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                </div>

                <div id="integration_cloud_block" class="mt-4 d-none">
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?php _e('Phone Number ID') ?></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="integration_phone_number_id" readonly>
                                <button class="btn btn-light-primary" type="button" onclick="copyToClipboard('integration_phone_number_id')"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?php _e('WABA ID') ?></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="integration_waba_id" readonly>
                                <button class="btn btn-light-primary" type="button" onclick="copyToClipboard('integration_waba_id')"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?php _e('Verify Token') ?></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="integration_verify_token" readonly>
                                <button class="btn btn-light-primary" type="button" onclick="copyToClipboard('integration_verify_token')"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="waActionToastStack" class="wa-action-toast-stack" aria-live="polite" aria-atomic="true"></div>

<style type="text/css">
.intl-tel-input{
    display: block;
}

.central-hero{
    padding: 6px 4px 0;
}

.central-eyebrow{
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #25d366;
    margin-bottom: 8px;
}

.central-title{
    font-size: 30px;
    line-height: 1.15;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}

.central-subtitle{
    font-size: 15px;
    color: #64748b;
    max-width: 760px;
}

.wa-connection-switchboard{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 22px;
    border: 1px solid #e7edf5;
    border-radius: 24px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.05);
}

.wa-connection-switchboard-copy{
    min-width: 0;
}

.wa-connection-switchboard-title{
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}

.wa-connection-switchboard-text{
    font-size: 14px;
    line-height: 1.6;
    color: #64748b;
    max-width: 680px;
}

.wa-connection-switchboard-actions{
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    flex-shrink: 0;
}

.wa-connection-drawer{
    position: fixed;
    inset: 0;
    z-index: 12000;
    pointer-events: none;
}

.wa-connection-drawer.is-open{
    pointer-events: auto;
}

.wa-connection-drawer-backdrop{
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.48);
    opacity: 0;
    transition: opacity 0.28s ease;
}

.wa-connection-drawer.is-open .wa-connection-drawer-backdrop{
    opacity: 1;
}

.wa-connection-drawer-panel{
    position: absolute;
    top: 0;
    right: 0;
    width: min(780px, 100vw);
    height: 100vh;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    box-shadow: -28px 0 60px rgba(15, 23, 42, 0.18);
    transform: translateX(100%);
    transition: transform 0.32s ease;
    display: flex;
    flex-direction: column;
}

.wa-connection-drawer.is-open .wa-connection-drawer-panel{
    transform: translateX(0);
}

.wa-connection-drawer-header{
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 22px 24px 18px;
    border-bottom: 1px solid #e7edf5;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(10px);
}

.wa-connection-drawer-eyebrow{
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #25d366;
    margin-bottom: 7px;
}

.wa-connection-drawer-title{
    font-size: 24px;
    line-height: 1.15;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}

.wa-connection-drawer-subtitle{
    font-size: 13px;
    line-height: 1.55;
    color: #64748b;
    max-width: 520px;
}

.wa-connection-drawer-tabs{
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 24px;
    border-bottom: 1px solid #edf1f7;
    background: rgba(255, 255, 255, 0.82);
    backdrop-filter: blur(10px);
}

.wa-connection-drawer-tab{
    border: 0;
    border-radius: 999px;
    padding: 10px 15px;
    background: #edf3fb;
    color: #475569;
    font-size: 13px;
    font-weight: 700;
}

.wa-connection-drawer-tab.is-active{
    background: #0f172a;
    color: #ffffff;
}

.wa-connection-drawer-body{
    flex: 1;
    overflow-y: auto;
    padding: 18px 18px 32px;
}

.wa-connection-drawer-view{
    display: none;
}

.wa-connection-drawer-view.is-active{
    display: block;
}

.wa-connection-drawer .row.justify-content-center{
    margin-left: 0;
    margin-right: 0;
}

.wa-connection-drawer .col-xl-10,
.wa-connection-drawer .col-lg-11,
.wa-connection-drawer .col-md-7{
    width: 100%;
    max-width: none;
    flex: 0 0 100%;
    padding-left: 0;
    padding-right: 0;
}

.wa-connection-drawer .card{
    margin-bottom: 0;
    border-radius: 24px;
    border: 1px solid #e7edf5;
    box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
}

body.wa-drawer-open{
    overflow: hidden;
}

.baileys-launcher{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 22px;
    border: 1px solid #e7edf5;
    border-radius: 20px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
}

.baileys-launcher-copy{
    min-width: 0;
}

.baileys-launcher-title{
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}

.baileys-launcher-text{
    font-size: 14px;
    line-height: 1.6;
    color: #64748b;
}

.baileys-pending-chip{
    margin-top: 14px;
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
    padding: 9px 14px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 600;
}

.baileys-launcher-actions{
    flex-shrink: 0;
}

.wa-summary-strip{
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.wa-toolbar{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}

.wa-search-wrap{
    position: relative;
    min-width: 280px;
    flex: 1;
}

.wa-search-icon{
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.wa-search-input{
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    padding-left: 40px;
    min-height: 44px;
}

.wa-filter-pills{
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.wa-filter-pill{
    border: 0;
    border-radius: 999px;
    padding: 9px 14px;
    background: #eef2f7;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
}

.wa-filter-pill.active{
    background: #0f172a;
    color: #ffffff;
}

.wa-summary-pill{
    display: inline-flex;
    align-items: center;
    padding: 7px 12px;
    border-radius: 999px;
    background: #f5f7fb;
    color: #334155;
    font-size: 12px;
    font-weight: 600;
}

.wa-summary-pill-warning{
    background: #fff7ed;
    color: #c2410c;
}

.wa-accounts-grid{
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 18px;
}

.wa-account-cell{
    min-width: 0;
    position: relative;
}

.wa-account-tile{
    height: 100%;
    padding: 18px;
    border-radius: 20px;
    border: 1px solid #edf1f7;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
    display: flex;
    flex-direction: column;
    gap: 14px;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    position: relative;
    overflow: visible;
    z-index: 1;
}

.wa-account-tile:hover{
    transform: translateY(-3px);
    box-shadow: 0 22px 48px rgba(15, 23, 42, 0.1);
    border-color: #dce6f2;
}

.wa-account-cell.wa-menu-open,
.wa-account-cell.wa-menu-open .wa-account-tile{
    z-index: 50;
}

.wa-account-top{
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.wa-account-identity{
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
}

.wa-account-avatar{
    width: 52px;
    height: 52px;
    border-radius: 16px;
    overflow: hidden;
    background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
    border: 1px solid #dbe5f1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #25d366;
    flex-shrink: 0;
}

.wa-account-avatar img{
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.wa-account-copy{
    min-width: 0;
}

.wa-account-name{
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.wa-account-subline{
    margin-top: 4px;
    color: #64748b;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.wa-account-middle{
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.wa-status-slot{
    min-height: 24px;
}

.wa-status-pill{
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}

.wa-status-pill-success{
    background: #ecfdf3;
    color: #15803d;
}

.wa-status-pill-warning{
    background: #fff7ed;
    color: #b45309;
}

.wa-status-pill-danger{
    background: #fef2f2;
    color: #b91c1c;
}

.cloud-health-loading,
.wa-local-note{
    color: #64748b;
    font-size: 12px;
}

.wa-local-note{
    display: inline-flex;
    align-items: center;
    padding: 7px 12px;
    border-radius: 999px;
    background: #f8fafc;
}

.cloud-health-mini{
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.cloud-health-mini-pill{
    display: inline-flex;
    align-items: center;
    padding: 7px 11px;
    border-radius: 999px;
    background: #f4f7fb;
    color: #334155;
    font-size: 12px;
    line-height: 1.1;
    white-space: nowrap;
}

.cloud-health-mini-pill-success{
    background: #ecfdf3;
    color: #15803d;
}

.cloud-health-mini-pill-warning{
    background: #fff7ed;
    color: #b45309;
}

.cloud-health-mini-pill-danger{
    background: #fef2f2;
    color: #b91c1c;
}

.cloud-health-mini-pill-secondary{
    background: #eef2f7;
    color: #475569;
}

.wa-account-footer{
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.wa-account-primary-actions{
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.wa-local-chip{
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 600;
}

.dropdown-menu{
    z-index: 9999 !important;
}

.wa-actions-dropdown{
    position: relative;
}

.wa-actions-menu{
    min-width: 220px;
    border-radius: 16px;
    border: 1px solid #e8edf5;
    padding-top: 8px;
    padding-bottom: 8px;
    box-shadow: 0 22px 50px rgba(15, 23, 42, 0.18) !important;
}

.wa-action-toast-stack{
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    width: min(420px, calc(100vw - 24px));
    z-index: 20000;
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: none;
}

.wa-action-toast{
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    border: 1px solid rgba(13, 110, 253, 0.14);
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 22px 55px rgba(15, 23, 42, 0.18);
    backdrop-filter: blur(12px);
    opacity: 0;
    transform: translateY(-12px) scale(0.96);
    transition: opacity 0.28s ease, transform 0.28s ease;
}

.wa-action-toast.is-visible{
    opacity: 1;
    transform: translateY(0) scale(1);
}

.wa-action-toast.is-leaving{
    opacity: 0;
    transform: translateY(-10px) scale(0.98);
}

.wa-action-toast-head{
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 18px 14px;
}

.wa-action-toast-icon{
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 42px;
    font-size: 16px;
    color: #0d6efd;
    background: rgba(13, 110, 253, 0.12);
    box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.08);
}

.wa-action-toast-copy{
    min-width: 0;
}

.wa-action-toast-title{
    font-size: 14px;
    font-weight: 700;
    color: #14213d;
    margin-bottom: 4px;
}

.wa-action-toast-detail{
    font-size: 12px;
    line-height: 1.45;
    color: #60708f;
}

.wa-action-toast-progress{
    height: 4px;
    background: rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

.wa-action-toast-progress span{
    display: block;
    width: 38%;
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #0d6efd 0%, #25d366 100%);
    animation: waActionToastPulse 1.15s linear infinite;
}

.wa-action-toast.is-success{
    border-color: rgba(25, 135, 84, 0.16);
}

.wa-action-toast.is-success .wa-action-toast-icon{
    color: #198754;
    background: rgba(25, 135, 84, 0.12);
    box-shadow: inset 0 0 0 1px rgba(25, 135, 84, 0.08);
}

.wa-action-toast.is-success .wa-action-toast-progress span{
    width: 100%;
    animation: none;
    background: linear-gradient(90deg, #198754 0%, #25d366 100%);
}

.wa-action-toast.is-error{
    border-color: rgba(220, 53, 69, 0.16);
}

.wa-action-toast.is-error .wa-action-toast-icon{
    color: #dc3545;
    background: rgba(220, 53, 69, 0.12);
    box-shadow: inset 0 0 0 1px rgba(220, 53, 69, 0.08);
}

.wa-action-toast.is-error .wa-action-toast-progress span{
    width: 100%;
    animation: none;
    background: linear-gradient(90deg, #dc3545 0%, #ff7a7a 100%);
}

@keyframes waActionToastPulse{
    0%{
        transform: translateX(-120%);
    }

    100%{
        transform: translateX(360%);
    }
}

@media (max-width: 767px){
    .wa-connection-switchboard,
    .baileys-launcher,
    .wa-toolbar,
    .wa-account-top,
    .wa-account-footer{
        flex-direction: column;
        align-items: stretch;
    }

    .wa-connection-switchboard-actions{
        width: 100%;
        justify-content: stretch;
    }

    .wa-connection-switchboard-actions .btn,
    .wa-connection-drawer-tab{
        width: 100%;
        justify-content: center;
    }

    .wa-connection-drawer-panel{
        width: 100vw;
    }

    .wa-connection-drawer-header,
    .wa-connection-drawer-tabs,
    .wa-connection-drawer-body{
        padding-left: 16px;
        padding-right: 16px;
    }

    .wa-connection-drawer-header{
        padding-top: 18px;
        padding-bottom: 16px;
    }

    .wa-connection-drawer-tabs{
        flex-direction: column;
        align-items: stretch;
    }

    .wa-search-wrap{
        min-width: 100%;
    }

    .wa-account-footer .dropdown,
    .wa-account-footer .dropdown .btn,
    .wa-account-primary-actions,
    .wa-account-primary-actions .btn{
        width: 100%;
    }

    .wa-account-primary-actions .btn,
    .wa-account-footer .dropdown .btn{
        justify-content: center;
    }

    .wa-action-toast-stack{
        top: 12px;
    }

    .wa-action-toast-head{
        padding: 14px 15px 12px;
    }
}
</style>

<!--End Import Chatbot Modal -->

<script>
function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices
    navigator.clipboard.writeText(copyText.value).then(function() {
        showNotification('Copiado para a área de transferência!', 'success');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}

function showNotification(message, type = 'info') {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        console.log("Notification [" + type + "]: " + message);
        // Tenta encontrar o toastr no elemento pai (caso seja um iframe ou carregado dinamicamente)
        if (typeof parent.toastr !== 'undefined') {
            parent.toastr[type](message);
        }
    }
}

var waActionToastStorageKey = 'wa_connection_action_toast';
var waActionToastCounter = 0;

function escapeActionToastText(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
}

function getActionToastStack() {
    var stack = $('#waActionToastStack');
    if (!stack.length) {
        $('body').append('<div id="waActionToastStack" class="wa-action-toast-stack" aria-live="polite" aria-atomic="true"></div>');
        stack = $('#waActionToastStack');
    }
    return stack;
}

function buildActionToastHtml(id, title, detail) {
    return ''
        + '<div class="wa-action-toast is-pending" id="' + id + '">'
        + '    <div class="wa-action-toast-head">'
        + '        <div class="wa-action-toast-icon"><i class="fas fa-spinner fa-spin"></i></div>'
        + '        <div class="wa-action-toast-copy">'
        + '            <div class="wa-action-toast-title">' + escapeActionToastText(title) + '</div>'
        + '            <div class="wa-action-toast-detail">' + escapeActionToastText(detail) + '</div>'
        + '        </div>'
        + '    </div>'
        + '    <div class="wa-action-toast-progress"><span></span></div>'
        + '</div>';
}

function persistActionToast(payload) {
    try {
        sessionStorage.setItem(waActionToastStorageKey, JSON.stringify(payload));
    } catch (e) {}
}

function clearPersistedActionToast() {
    try {
        sessionStorage.removeItem(waActionToastStorageKey);
    } catch (e) {}
}

function restorePersistedActionToast() {
    var raw = null;
    try {
        raw = sessionStorage.getItem(waActionToastStorageKey);
    } catch (e) {
        return;
    }

    if (!raw) {
        return;
    }

    clearPersistedActionToast();

    try {
        var payload = JSON.parse(raw);
        if (!payload || !payload.title) {
            return;
        }

        var minDuration = parseInt(payload.minDuration, 10);
        if (isNaN(minDuration) || minDuration < 0) {
            minDuration = 3000;
        }

        var startedAt = parseInt(payload.startedAt, 10) || Date.now();
        var elapsed = Date.now() - startedAt;
        var remaining = minDuration - elapsed;
        if (remaining <= 150) {
            return;
        }

        showActionToast(payload.title, payload.detail, {
            minDuration: remaining,
            autoDismiss: true
        });
    } catch (e) {}
}

function showActionToast(title, detail, options) {
    options = options || {};

    var noticeId = 'waActionToast_' + (++waActionToastCounter);
    var minDuration = parseInt(options.minDuration, 10);
    if (isNaN(minDuration) || minDuration < 0) {
        minDuration = 3000;
    }

    var autoDismiss = !!options.autoDismiss;
    var startedAt = Date.now();
    var closeTimer = null;
    var removed = false;
    var stack = getActionToastStack();

    stack.prepend(buildActionToastHtml(
        noticeId,
        title || 'Solicitação em andamento',
        detail || 'Estamos processando sua ação agora.'
    ));

    var notice = $('#' + noticeId);
    setTimeout(function() {
        notice.addClass('is-visible');
    }, 10);

    function updateState(state, nextTitle, nextDetail) {
        if (!notice.length) {
            return;
        }

        notice.removeClass('is-pending is-success is-error').addClass('is-' + state);

        if (nextTitle) {
            notice.find('.wa-action-toast-title').text(nextTitle);
        }

        if (nextDetail) {
            notice.find('.wa-action-toast-detail').text(nextDetail);
        }

        var iconHtml = '<i class="fas fa-spinner fa-spin"></i>';
        if (state === 'success') {
            iconHtml = '<i class="fas fa-check"></i>';
        } else if (state === 'error') {
            iconHtml = '<i class="fas fa-exclamation"></i>';
        }

        notice.find('.wa-action-toast-icon').html(iconHtml);
    }

    function dismissAfter(delay) {
        clearTimeout(closeTimer);
        closeTimer = setTimeout(function() {
            if (removed) {
                return;
            }

            removed = true;
            notice.removeClass('is-visible').addClass('is-leaving');
            setTimeout(function() {
                notice.remove();
            }, 280);
        }, Math.max(0, delay));
    }

    if (autoDismiss) {
        dismissAfter(minDuration);
    }

    return {
        complete: function(nextTitle, nextDetail) {
            var elapsed = Date.now() - startedAt;
            updateState('success', nextTitle || 'Solicitação concluída', nextDetail || 'A ação foi finalizada com sucesso.');
            dismissAfter((minDuration - elapsed) + 900);
        },
        error: function(nextTitle, nextDetail) {
            var elapsed = Date.now() - startedAt;
            updateState('error', nextTitle || 'Não foi possível concluir', nextDetail || 'Revise os dados e tente novamente em instantes.');
            dismissAfter((minDuration - elapsed) + 1200);
        },
        dismiss: function() {
            var elapsed = Date.now() - startedAt;
            dismissAfter(minDuration - elapsed);
        },
        update: function(nextTitle, nextDetail) {
            updateState('pending', nextTitle, nextDetail);
        }
    };
}

function queueActionToastAcrossNavigation(title, detail, minDuration) {
    var duration = parseInt(minDuration, 10);
    if (isNaN(duration) || duration < 0) {
        duration = 3000;
    }

    persistActionToast({
        title: title || 'Solicitação em andamento',
        detail: detail || 'Estamos processando sua ação agora.',
        minDuration: duration,
        startedAt: Date.now()
    });

    return showActionToast(title, detail, {
        minDuration: duration,
        autoDismiss: true
    });
}

function getConnectionDrawer() {
    return $('#waConnectionDrawer');
}

function setConnectionDrawerView(view) {
    var drawer = getConnectionDrawer();
    if (!drawer.length || !view) {
        return;
    }

    drawer.find('.wa-connection-drawer-view').removeClass('is-active');
    drawer.find('.wa-connection-drawer-view[data-drawer-view="' + view + '"]').addClass('is-active');
    drawer.find('.wa-connection-drawer-tab').removeClass('is-active');
    drawer.find('.wa-connection-drawer-tab[data-connection-drawer-target="' + view + '"]').addClass('is-active');
    drawer.attr('data-active-view', view);
}

function syncManualCloudState(shouldExpand) {
    var manualCloudForm = $('#manualCloudForm');
    if (!manualCloudForm.length) {
        return;
    }

    if (typeof manualCloudForm.collapse === 'function') {
        manualCloudForm.collapse(shouldExpand ? 'show' : 'hide');
        return;
    }

    manualCloudForm.toggleClass('show', !!shouldExpand);
}

function openConnectionDrawer(view, options) {
    var drawer = getConnectionDrawer();
    if (!drawer.length) {
        return;
    }

    options = options || {};
    var targetView = view || drawer.attr('data-default-view') || 'baileys';
    setConnectionDrawerView(targetView);
    drawer.addClass('is-open');
    $('body').addClass('wa-drawer-open');

    if (targetView === 'cloud') {
        syncManualCloudState(!!options.expandManualCloud);
    }
}

function closeConnectionDrawer() {
    var drawer = getConnectionDrawer();
    if (!drawer.length) {
        return;
    }

    drawer.removeClass('is-open');
    $('body').removeClass('wa-drawer-open');
}

function cloudHealthFallbackHtml(message) {
    return '<div class="cloud-health-loading"><i class="fas fa-exclamation-circle me-1"></i>' + message + '</div>';
}

function carregarResumosCloud(idsList, forceRefresh) {
    if (!idsList || !idsList.length) {
        return;
    }

    $.ajax({
        url: '<?php echo base_url("whatsapp_profiles/cloud_health_batch"); ?>',
        type: 'GET',
        dataType: 'json',
        data: {
            ids: idsList.join(','),
            refresh: forceRefresh ? 1 : 0
        }
    }).done(function(response) {
        if (!response || response.status !== 'success' || !response.items) {
            idsList.forEach(function(id) {
                $('#cloud-health-' + id).html(cloudHealthFallbackHtml('Não foi possível carregar o resumo Cloud agora.'));
            });
            return;
        }

        idsList.forEach(function(id) {
            if (response.items[id] && response.items[id].html) {
                $('#cloud-health-' + id).html(response.items[id].html);
            } else {
                $('#cloud-health-' + id).html(cloudHealthFallbackHtml('Resumo Cloud indisponível para esta conta.'));
            }
        });
    }).fail(function() {
        idsList.forEach(function(id) {
            $('#cloud-health-' + id).html(cloudHealthFallbackHtml('Erro ao consultar a saúde Cloud.'));
        });
    });
}

function iniciarConexao(token) {
    // Primeiro, vamos verificar se o token é válido
    if (!token) {
        console.error('Token não fornecido');
        return;
    }

    // Faz a requisição para gerar uma nova instância
    fetch('<?php echo get_module_url('generate_instance'); ?>/' + token, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            // Redireciona para a página de autenticação com a nova instância
            window.location.href = '<?php echo get_module_url('oauth'); ?>/' + token;
        } else {
            showNotification(data.message || 'Erro ao gerar instância', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao iniciar conexão: ' + error.message, 'error');
    });
}

function editarPerfilCloud(ids, name, waba_id, phone_id, token, verify_token) {
    $('#edit_cloud_ids').val(ids);
    $('#edit_cloud_name').val(name);
    $('#edit_cloud_waba_id').val(waba_id);
    $('#edit_cloud_phone_id').val(phone_id);
    $('#edit_cloud_token').val(token);
    $('#edit_cloud_verify_token').val(verify_token);
    $('#EditCloudModal').modal('show');
}

function abrirDadosIntegracao(payload) {
    payload = payload || {};

    $('#integration_profile_name').val(payload.name || '');
    $('#integration_profile_type').val(payload.type || '');
    $('#integration_record_id').val(payload.record_id || '');
    $('#integration_instance_id').val(payload.instance_id || '');
    $('#integration_access_token').val(payload.access_token || '');
    $('#integration_access_token_label').text(payload.access_token_label || 'Token de acesso');
    $('#integration_profile_pid').val(payload.profile_pid || '');

    var isCloud = (payload.type || '').toLowerCase() === 'cloud api';
    $('#integration_phone_number_id').val(payload.phone_number_id || '');
    $('#integration_waba_id').val(payload.waba_id || '');
    $('#integration_verify_token').val(payload.verify_token || '');
    $('#integration_cloud_block').toggleClass('d-none', !isCloud);

    $('#IntegrationDataModal').modal('show');
}

function testarConexaoCloud(ids, triggerEl) {
    var btn = $(triggerEl || (window.event ? window.event.currentTarget : null));
    var originalHtml = btn.html();
    var actionToast = showActionToast(
        'Testando conexão Cloud API',
        'Estamos validando a comunicação deste perfil com a Meta.',
        { minDuration: 3000 }
    );

    btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

    $.get('<?php echo base_url("whatsapp_profiles/test_official"); ?>/' + ids, function(data) {
        if (data && data.status) {
            var isSuccess = data.status === 'success' || data.status === true;
            if (isSuccess) {
                actionToast.complete('Teste concluído', data.message || 'A validação desta conexão foi finalizada.');
            } else {
                actionToast.error('Teste concluído com alerta', data.message || 'A conexão respondeu com erro durante a validação.');
            }

            if(typeof Core != "undefined" && typeof Core.notify == "function") {
                Core.notify(data.message, data.status);
            } else if(typeof showNotification == "function") {
                showNotification(data.message, data.status);
            }
            
            var statusHtml = '';
            if (data.status === 'success') {
                statusHtml = '<span class="wa-status-pill wa-status-pill-success"><i class="fas fa-check-circle me-1"></i><?php _e( "Conectado" )?></span>';
            } else {
                statusHtml = '<span class="wa-status-pill wa-status-pill-danger"><i class="fas fa-times-circle me-1"></i><?php _e( "Erro na conexão" )?></span>';
            }
            $('#status-' + ids).html(statusHtml);
        } else {
            actionToast.error('Resposta inválida', 'O servidor não retornou um resultado válido para este teste.');
            if(typeof Core != "undefined" && typeof Core.notify == "function") Core.notify("Resposta inválida do servidor.", "error");
            else if(typeof showNotification == "function") showNotification("Resposta inválida do servidor.", "error");
        }
    }, 'json').fail(function() {
        actionToast.error('Falha no teste da conexão', 'Não conseguimos concluir a validação desta conta agora.');
        if(typeof Core != "undefined" && typeof Core.notify == "function") Core.notify("Erro ao testar a conexão.", "error");
        else if(typeof showNotification == "function") showNotification("Erro ao testar a conexão.", "error");
    }).always(function() {
        btn.html(originalHtml).prop('disabled', false);
        carregarResumosCloud([ids], true);
    });
}

function sincronizarTemplates(ids, triggerEl) {
    var btn = $(triggerEl || (window.event ? window.event.currentTarget : null));
    var originalHtml = btn.html();
    var actionToast = showActionToast(
        'Sincronizando templates',
        'Estamos consultando os templates aprovados desta conexão Cloud API.',
        { minDuration: 3000 }
    );

    btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

    $.get('<?php echo base_url("whatsapp_profiles/sync_templates"); ?>/' + ids, function(data) {
        if (data && data.status) {
            var isSuccess = data.status === 'success' || data.status === true;
            if (isSuccess) {
                actionToast.complete('Sincronização concluída', data.message || 'Os templates desta conta foram atualizados.');
            } else {
                actionToast.error('Sincronização concluída com alerta', data.message || 'A sincronização retornou um erro para esta conta.');
            }

            if(typeof Core != "undefined" && typeof Core.notify == "function") {
                Core.notify(data.message, data.status);
            } else if(typeof showNotification == "function") {
                showNotification(data.message, data.status);
            }
        } else {
            actionToast.error('Resposta inválida', 'O servidor não retornou o resultado esperado para a sincronização.');
            if(typeof Core != "undefined" && typeof Core.notify == "function") Core.notify("Resposta inválida do servidor.", "error");
            else if(typeof showNotification == "function") showNotification("Resposta inválida do servidor.", "error");
        }
    }, 'json').fail(function(jqxhr) {
        var errorMsg = "Erro ao sincronizar templates.";
        try {
            var resp = JSON.parse(jqxhr.responseText);
            if (resp.message) errorMsg = resp.message;
        } catch(e) {}
        actionToast.error('Falha na sincronização', errorMsg);
        if(typeof Core != "undefined" && typeof Core.notify == "function") Core.notify(errorMsg, "error");
        else if(typeof showNotification == "function") showNotification(errorMsg, "error");
    }).always(function() {
        btn.html(originalHtml).prop('disabled', false);
    });
}

function updateProfileNameRequest(profileId, newName) {
    const formData = new FormData();
    formData.append('ids', profileId);
    formData.append('name', newName);

    const timestamp = new Date().getTime();
    const url = `${PATH}/whatsapp_profiles/update_name?_=${timestamp}`;

    return fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache',
            'Expires': '0'
        },
        body: formData,
        cache: 'no-store'
    }).then(response => response.text()).then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Resposta inválida do servidor.');
        }

        if (data.status !== 'success') {
            throw new Error(data.message || 'Erro ao atualizar o nome do perfil.');
        }

        return data;
    });
}

function editProfileName(profileId) {
    const card = document.querySelector(`.wa-account-tile[data-profile-id="${profileId}"]`);
    const currentName = card ? (card.getAttribute('data-profile-name') || '') : '';

    const doUpdate = function(newName) {
        if (!newName || !newName.trim()) {
            showNotification('Informe um nome válido para o perfil.', 'error');
            return;
        }

        var actionToast = showActionToast(
            'Salvando novo nome do perfil',
            'Estamos atualizando a identificação desta conexão.',
            { minDuration: 3000 }
        );

        updateProfileNameRequest(profileId, newName.trim())
            .then(function(data) {
                actionToast.complete('Nome atualizado', data.message || 'O nome do perfil foi salvo com sucesso.');
                showNotification(data.message || 'Nome atualizado com sucesso.', 'success');
                window.location.reload();
            })
            .catch(function(error) {
                actionToast.error('Falha ao salvar o nome', error.message || 'Não foi possível atualizar o nome deste perfil.');
                showNotification(error.message || 'Erro ao atualizar o nome do perfil.', 'error');
            });
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '<?php _e("Editar nome do perfil") ?>',
            input: 'text',
            inputValue: currentName,
            inputPlaceholder: '<?php _e("Digite o novo nome") ?>',
            showCancelButton: true,
            confirmButtonText: '<?php _e("Salvar") ?>',
            cancelButtonText: '<?php _e("Cancelar") ?>',
            preConfirm: function(value) {
                if (!value || !value.trim()) {
                    Swal.showValidationMessage('<?php _e("Informe um nome válido para o perfil") ?>');
                    return false;
                }
                return value;
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                doUpdate(result.value);
            }
        });
        return;
    }

    const fallbackName = window.prompt('Digite o novo nome do perfil:', currentName);
    if (fallbackName !== null) {
        doUpdate(fallbackName);
    }
}

$(function() {
    restorePersistedActionToast();

    var initialDrawerView = getConnectionDrawer().attr('data-default-view') || '';
    if (initialDrawerView) {
        openConnectionDrawer(initialDrawerView);
    }

    var cloudIds = [];
    $('.cloud-health-inline[data-cloud-health-id]').each(function() {
        var accountId = $(this).data('cloud-health-id');
        if (accountId) {
            cloudIds.push(accountId.toString());
        }
    });

    if (cloudIds.length) {
        carregarResumosCloud(cloudIds, false);
    }

    var activeAccountFilter = 'all';
    var searchInput = $('#waConnectionSearch');
    var accountCards = $('.wa-account-cell');

    function applyConnectionFilters() {
        var searchTerm = (searchInput.val() || '').toString().trim().toLowerCase();

        accountCards.each(function() {
            var card = $(this);
            var accountType = card.data('account-type');
            var accountState = card.data('account-state');
            var searchData = (card.data('account-search') || '').toString().toLowerCase();

            var matchesFilter = activeAccountFilter === 'all'
                || (activeAccountFilter === 'cloud' && accountType === 'cloud')
                || (activeAccountFilter === 'baileys' && accountType === 'baileys')
                || (activeAccountFilter === 'whatsmeow' && accountType === 'whatsmeow')
                || (activeAccountFilter === 'attention' && accountState === 'attention');

            var matchesSearch = searchTerm === '' || searchData.indexOf(searchTerm) !== -1;

            card.toggle(matchesFilter && matchesSearch);
        });
    }

    $('#waConnectionFilters').on('click', '.wa-filter-pill', function() {
        activeAccountFilter = $(this).data('filter') || 'all';
        $('#waConnectionFilters .wa-filter-pill').removeClass('active');
        $(this).addClass('active');
        applyConnectionFilters();
    });

    searchInput.on('input', applyConnectionFilters);
    applyConnectionFilters();

    $(document)
        .on('show.bs.dropdown', '.wa-actions-dropdown', function() {
            $(this).closest('.wa-account-cell').addClass('wa-menu-open');
        })
        .on('hide.bs.dropdown', '.wa-actions-dropdown', function() {
            $(this).closest('.wa-account-cell').removeClass('wa-menu-open');
        })
        .on('click', '.js-open-connection-drawer', function() {
            var item = $(this);
            var targetView = item.attr('data-connection-drawer-target') || 'baileys';
            openConnectionDrawer(targetView, {
                expandManualCloud: item.attr('data-cloud-manual') === '1'
            });
        })
        .on('click', '[data-close-connection-drawer]', function() {
            closeConnectionDrawer();
        })
        .on('click', '.js-progress-navigation', function() {
            var item = $(this);
            queueActionToastAcrossNavigation(
                item.data('progress-title') || 'Solicitação em andamento',
                item.data('progress-detail') || 'Estamos preparando sua próxima etapa agora.',
                item.data('progress-duration') || 3000
            );
        })
        .on('click', '.js-open-integration-data', function() {
            var item = $(this);
            abrirDadosIntegracao({
                name: item.attr('data-profile-name') || '',
                type: item.attr('data-profile-type') || '',
                record_id: item.attr('data-record-id') || '',
                instance_id: item.attr('data-instance-id') || '',
                access_token: item.attr('data-access-token') || '',
                access_token_label: item.attr('data-access-token-label') || '',
                profile_pid: item.attr('data-profile-pid') || '',
                phone_number_id: item.attr('data-phone-number-id') || '',
                waba_id: item.attr('data-waba-id') || '',
                verify_token: item.attr('data-verify-token') || ''
            });
        })
        .on('submit', 'form[data-progress-title]', function() {
            var form = $(this);
            queueActionToastAcrossNavigation(
                form.data('progress-title') || 'Solicitação em andamento',
                form.data('progress-detail') || 'Estamos processando sua solicitação agora.',
                form.data('progress-duration') || 3000
            );
        });

    $(document).on('keydown', function(event) {
        if (event.key === 'Escape') {
            closeConnectionDrawer();
        }
    });
});

$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    if (settings.url.includes("save_official")) {
        var detail = "Erro " + jqxhr.status + " - " + thrownError;
        var responsePreview = jqxhr.responseText ? jqxhr.responseText.substring(0, 200) : "Sem resposta do servidor.";
        console.error("Erro ao salvar perfil oficial:", detail, responsePreview);
        $(".loading").hide();

        if (typeof showNotification === "function") {
            showNotification("Erro ao salvar perfil oficial. Verifique os dados e tente novamente.", "error");
        } else if (typeof Core !== "undefined" && typeof Core.notify === "function") {
            Core.notify("Erro ao salvar perfil oficial. Verifique os dados e tente novamente.", "error");
        }
    }
});

// ========================================
// EMBEDDED SIGNUP - Facebook SDK
// ========================================

<?php if (!empty($cloud_api_embedded_signup_enabled)): ?>
var _fbReady = false;
var _embeddedSignupToast = null;
<?php 
    $fb_app_id = get_option('meta_app_id', '') ?: get_option('facebook_login_app_id', '');
    if (empty($fb_app_id)) {
        $fb_app_id = '763786439394524'; // ELITEZAP App ID fallback
    }
    $meta_graph_version = get_option('meta_graph_version', '') ?: 'v22.0';
    $meta_config_id = get_option('meta_embedded_signup_config_id', '') ?: '1115307890606209';
?>
var FB_APP_ID = '<?php echo addslashes($fb_app_id); ?>';
var META_GRAPH_VERSION = '<?php echo addslashes($meta_graph_version); ?>';
var META_EMBEDDED_SIGNUP_CONFIG_ID = '<?php echo addslashes($meta_config_id); ?>';
console.log('🔧 FB App ID:', FB_APP_ID);

// 1. Definir fbAsyncInit ANTES de carregar o SDK
window.fbAsyncInit = function() {
    FB.init({
        appId: FB_APP_ID,
        cookie: true,
        xfbml: true,
        version: META_GRAPH_VERSION
    });
    _fbReady = true;
    console.log('✅ Facebook SDK inicializado com App ID:', FB_APP_ID);
};

// 2. Injetar o SDK programaticamente (método oficial Meta)
(function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) { return; }
    js = d.createElement(s);
    js.id = id;
    js.src = 'https://connect.facebook.net/pt_BR/sdk.js';
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

// 3. Função principal do Embedded Signup
function launchEmbeddedSignup() {
    _embeddedSignupToast = showActionToast(
        'Preparando conexão com a Meta',
        'Estamos iniciando o fluxo oficial da Cloud API para este perfil.',
        { minDuration: 3000 }
    );

    if (!_fbReady) {
        // Aguarda os recursos oficiais antes de abrir o popup de autenticação.
        _embeddedSignupToast.update('Aguardando recursos da Meta', 'Estamos carregando os recursos necessários para abrir a autenticação oficial.');
        $('#btn-embedded-signup').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Carregando...');
        var attempts = 0;
        var waitForSDK = setInterval(function() {
            attempts++;
            if (_fbReady) {
                clearInterval(waitForSDK);
                $('#btn-embedded-signup').prop('disabled', false).html('<i class="fab fa-whatsapp me-2"></i> Conectar com Meta');
                doEmbeddedLogin();
            } else if (attempts > 20) { // ~10 segundos
                clearInterval(waitForSDK);
                resetEmbeddedButton();
                if (_embeddedSignupToast) {
                    _embeddedSignupToast.error('Recursos da Meta indisponíveis', 'A autenticação oficial não pôde ser iniciada neste momento.');
                }
                if(typeof showNotification == "function") showNotification('Os recursos da Meta não carregaram. Recarregue a página e tente novamente.', 'error');
                else console.error('Os recursos da Meta não carregaram. Recarregue a página e tente novamente.');
            }
        }, 500);
        return;
    }
    doEmbeddedLogin();
}

// Variável global para armazenar dados do Embedded Signup FINISH event
var _embeddedSignupData = null;
var _embeddedSignupStorageKey = 'wa_embedded_signup_finish';

function clearEmbeddedSignupData() {
    _embeddedSignupData = null;
    try {
        sessionStorage.removeItem(_embeddedSignupStorageKey);
    } catch (e) {}
}

function storeEmbeddedSignupData(data) {
    if (!data || typeof data !== 'object') return;

    _embeddedSignupData = data;

    try {
        sessionStorage.setItem(_embeddedSignupStorageKey, JSON.stringify(data));
    } catch (e) {}
}

function getEmbeddedSignupData() {
    if (_embeddedSignupData && typeof _embeddedSignupData === 'object') {
        return _embeddedSignupData;
    }

    try {
        var stored = sessionStorage.getItem(_embeddedSignupStorageKey);
        if (stored) {
            _embeddedSignupData = JSON.parse(stored);
            return _embeddedSignupData;
        }
    } catch (e) {}

    return null;
}

function normalizeEmbeddedSignupEventPayload(payload) {
    if (!payload) return null;

    if (typeof payload === 'string') {
        try {
            payload = JSON.parse(payload);
        } catch (e) {
            return null;
        }
    }

    if (typeof payload !== 'object') {
        return null;
    }

    if (payload.type === 'WA_EMBEDDED_SIGNUP') {
        return payload;
    }

    if (payload.data && typeof payload.data === 'object' && payload.data.type === 'WA_EMBEDDED_SIGNUP') {
        return payload.data;
    }

    return null;
}

function submitEmbeddedSignup(code, saveUrl) {
    var finishData = getEmbeddedSignupData();

    console.log('📡 Enviando AJAX para:', saveUrl);
    console.log('📡 Dados do FINISH event:', JSON.stringify(finishData));

    var postData = { code: code };
    if (finishData) {
        if (finishData.waba_id) postData.waba_id = finishData.waba_id;
        if (finishData.phone_number_id) postData.phone_number_id = finishData.phone_number_id;
    }

    console.log('📡 POST data:', JSON.stringify(postData));

    $.ajax({
        url: saveUrl,
        method: 'POST',
        data: postData,
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: function(data) {
            console.log('✅ AJAX Success:', JSON.stringify(data));
            if (data.status === 'success') {
                clearEmbeddedSignupData();
                if (_embeddedSignupToast) {
                    _embeddedSignupToast.complete('Conexão Cloud recebida', data.message || 'Estamos finalizando o cadastro do perfil oficial.');
                }
                if(typeof showNotification == "function") showNotification(data.message, 'success');
                setTimeout(function() {
                    window.location.href = data.redirect || '<?php echo base_url("whatsapp_profiles/oauth"); ?>';
                }, 1500);
            } else {
                var msg = data.message || 'Erro desconhecido';
                console.error('❌ Backend retornou erro:', msg);
                if (_embeddedSignupToast) {
                    _embeddedSignupToast.error('Falha ao salvar conexão', msg);
                }
                if(typeof showNotification == "function") showNotification(msg, 'error');
                else console.error('Erro ao salvar perfil: ' + msg);
                resetEmbeddedButton();
            }
        },
        error: function(jqxhr, textStatus, errorThrown) {
            console.error('❌ AJAX Error:', textStatus, errorThrown);
            console.error('❌ AJAX Response:', jqxhr.status, jqxhr.responseText);
            var errorMsg = 'Erro ao conectar. Tente novamente.';
            try {
                var resp = JSON.parse(jqxhr.responseText);
                if (resp.message) errorMsg = resp.message;
            } catch(e) {}
            if (_embeddedSignupToast) {
                _embeddedSignupToast.error('Falha na conexão oficial', errorMsg);
            }
            if(typeof showNotification == "function") showNotification(errorMsg, 'error');
            else console.error('Erro de sistema: ' + errorMsg);
            resetEmbeddedButton();
        }
    });
}

function waitForEmbeddedSignupFinish(code, saveUrl) {
    var attempts = 0;
    var maxAttempts = 50; // 5 segundos

    var timer = setInterval(function() {
        attempts++;

        var finishData = getEmbeddedSignupData();
        if (finishData && (finishData.waba_id || finishData.phone_number_id)) {
            clearInterval(timer);
            submitEmbeddedSignup(code, saveUrl);
            return;
        }

        if (attempts >= maxAttempts) {
            clearInterval(timer);
            submitEmbeddedSignup(code, saveUrl);
        }
    }, 100);
}

function doEmbeddedLogin() {
    console.log('🚀 doEmbeddedLogin() chamado - iniciando FB.login...');
    clearEmbeddedSignupData();
    if (_embeddedSignupToast) {
        _embeddedSignupToast.update('Abrindo autenticação da Meta', 'Conclua as etapas no popup para terminar a conexão oficial.');
    }
    FB.login(function(response) {
        console.log('📦 FB.login response COMPLETA:', JSON.stringify(response));
        if (response.authResponse) {
            var code = response.authResponse.code;
            console.log('✅ authResponse.code recebido:', code ? code.substring(0, 30) + '...' : 'VAZIO/NULL');

            if (!code) {
                console.error('Erro: authResponse existe mas code está vazio.', response.authResponse);
                if (_embeddedSignupToast) {
                    _embeddedSignupToast.error('Autorização incompleta', 'O código de autorização veio vazio. Tente conectar novamente.');
                }
                if (typeof showNotification === 'function') {
                    showNotification('Código de autorização vazio. Tente novamente.', 'error');
                }
                resetEmbeddedButton();
                return;
            }

            // Mostrar loading
            if (_embeddedSignupToast) {
                _embeddedSignupToast.update('Processando autorização oficial', 'Estamos confirmando os dados enviados pela Meta para cadastrar o perfil.');
            }
            $('#btn-embedded-signup').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Processando...');
            $('#embedded-loading').removeClass('d-none');

            var saveUrl = '<?php echo str_replace("http:", "https:", base_url("whatsapp_profiles/save_embedded")); ?>';

            waitForEmbeddedSignupFinish(code, saveUrl);
        } else {
            console.log('⚠️ Embedded Signup: Usuário cancelou ou não autorizou. Response:', JSON.stringify(response));
            if (_embeddedSignupToast) {
                _embeddedSignupToast.error('Conexão cancelada', 'A autenticação oficial foi interrompida antes da conclusão.');
            }
        }
    }, {
        config_id: META_EMBEDDED_SIGNUP_CONFIG_ID,
        scope: 'whatsapp_business_management,whatsapp_business_messaging,whatsapp_business_manage_events,business_management',
        response_type: 'code',
        override_default_response_type: true,
        extras: {
            setup: {},
            featureType: 'coexistence',
            sessionInfoVersion: '3'
        }
    });
}

function resetEmbeddedButton() {
    $('#btn-embedded-signup').prop('disabled', false).html('<i class="fab fa-whatsapp me-2"></i> Conectar com Meta');
    $('#embedded-loading').addClass('d-none');
    clearEmbeddedSignupData();
}

// Listener para eventos do Embedded Signup popup
window.addEventListener('message', function(event) {
    var allowedOrigins = [
        "https://www.facebook.com",
        "https://web.facebook.com",
        "https://business.facebook.com"
    ];
    if (allowedOrigins.indexOf(event.origin) === -1) return;
    try {
        var data = normalizeEmbeddedSignupEventPayload(event.data);
        if (!data) return;

        if (data.type === 'WA_EMBEDDED_SIGNUP') {
            if (data.event === 'FINISH') {
                console.log('🎉 Embedded Signup FINISH:', data.data);
                // Armazenar dados do FINISH para uso no AJAX
                storeEmbeddedSignupData(data.data || {});
            } else if (data.event === 'CANCEL') {
                console.log('Embedded Signup cancelado');
                if (_embeddedSignupToast) {
                    _embeddedSignupToast.error('Conexão cancelada', 'O fluxo oficial foi cancelado antes da confirmação final.');
                }
                resetEmbeddedButton();
            } else if (data.event === 'ERROR') {
                console.log('Embedded Signup erro:', data.data);
                if (_embeddedSignupToast) {
                    _embeddedSignupToast.error('Erro no cadastro oficial', 'A Meta retornou um erro ao processar esta conexão.');
                }
                if(typeof showNotification == "function") showNotification('Erro no cadastro. Tente novamente.', 'error');
                resetEmbeddedButton();
            }
        }
    } catch(e) {}
});
<?php endif; ?>
</script>
