<!DOCTYPE html>
<html lang="en" dir="<?php _ec( request_service("language")->dir )?>" data-theme="<?php _ec( get_option("theme_color", "light") )?>">
    <head><base href="">
        <meta charset="utf-8" />
        <title><?php _e($title)?></title>
        <meta name="description" content="<?php _e( get_option("website_description", "") )?>" />
        <meta name="keywords" content="<?php _e( get_option("website_description", "") )?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="shortcut icon" href="<?php _ec( get_option("website_favicon", base_url("assets/img/favicon.svg")) )?>" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/fonts/fontawesome/css/all.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/fonts/icomoon/icomoon.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/fonts/flags/flag-icon.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/pagination/pagination.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/izitoast/izitoast.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/webui-popover/webui-popover.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ui/jquery-ui.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/datetimepicker/timepicker-addon.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/emojionearea/emojionearea.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/tagsinput/bootstrap-tagsinput.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/owlcarousel/owl.carousel.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/owlcarousel/owl.theme.default.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/fancybox/jquery.fancybox.min.css" rel="stylesheet" type="text/css"></link>
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/minicolors/jquery.minicolors.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/select2/css/select2.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/monthly/monthly.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/css/animate.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/css/style.css" rel="stylesheet" type="text/css" />
        <style>
            .sp-confirm-modal .modal-dialog {
                max-width: 430px;
                transform: translateY(18px) scale(0.96);
                transition: transform 0.28s ease, opacity 0.28s ease;
            }
            .sp-confirm-modal.show .modal-dialog {
                transform: translateY(0) scale(1);
            }
            .sp-confirm-modal .modal-content {
                border: 0;
                border-radius: 24px;
                overflow: hidden;
                box-shadow: 0 24px 80px rgba(15, 23, 42, 0.22);
                background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            }
            .sp-confirm-modal .modal-body {
                padding: 28px;
            }
            .sp-confirm-hero {
                display: flex;
                align-items: center;
                gap: 16px;
                margin-bottom: 18px;
            }
            .sp-confirm-icon {
                width: 58px;
                height: 58px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 18px;
                color: #0d6efd;
                background: linear-gradient(135deg, rgba(13, 110, 253, 0.14), rgba(13, 202, 240, 0.18));
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
                font-size: 22px;
            }
            .sp-confirm-title {
                margin: 0;
                font-size: 1.2rem;
                font-weight: 700;
                color: #1f2937;
            }
            .sp-confirm-subtitle {
                margin: 4px 0 0;
                font-size: 0.92rem;
                color: #64748b;
            }
            .sp-confirm-message {
                margin: 0;
                font-size: 0.98rem;
                line-height: 1.65;
                color: #334155;
            }
            .sp-confirm-progress {
                height: 7px;
                margin: 22px 0 10px;
                border-radius: 999px;
                overflow: hidden;
                background: rgba(148, 163, 184, 0.16);
            }
            .sp-confirm-progress-bar {
                width: 0%;
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
                transition-property: width;
                transition-timing-function: linear;
                box-shadow: 0 0 20px rgba(13, 202, 240, 0.32);
            }
            .sp-confirm-hint {
                margin: 0;
                font-size: 0.85rem;
                color: #64748b;
            }
            .sp-confirm-actions {
                display: flex;
                gap: 12px;
                margin-top: 24px;
            }
            .sp-confirm-actions .btn {
                flex: 1 1 0;
                min-height: 46px;
                border-radius: 14px;
                font-weight: 600;
            }
            .sp-confirm-actions .btn-light {
                background: #eef2f7;
                border-color: #e2e8f0;
                color: #475569;
            }
            .sp-confirm-approve.is-waiting {
                background: linear-gradient(135deg, #93c5fd, #60a5fa);
                border-color: transparent;
                color: #ffffff;
                opacity: 0.92;
            }
            .sp-confirm-approve.is-ready {
                background: linear-gradient(135deg, #0d6efd, #0b57d0);
                border-color: transparent;
                color: #ffffff;
                box-shadow: 0 14px 34px rgba(13, 110, 253, 0.22);
            }
            .sp-confirm-modal .modal-backdrop.show {
                opacity: 0.56;
            }
            .sp-action-modal .modal-dialog {
                max-width: 390px;
                transform: translateY(16px) scale(0.96);
                transition: transform 0.28s ease, opacity 0.28s ease;
            }
            .sp-action-modal.show .modal-dialog {
                transform: translateY(0) scale(1);
            }
            .sp-action-modal .modal-content {
                border: 0;
                border-radius: 24px;
                overflow: hidden;
                box-shadow: 0 24px 80px rgba(15, 23, 42, 0.22);
                background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            }
            .sp-action-modal .modal-body {
                padding: 30px 28px;
                text-align: center;
            }
            .sp-action-icon-wrap {
                position: relative;
                width: 76px;
                height: 76px;
                margin: 0 auto 18px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .sp-action-ring,
            .sp-action-ring::after {
                position: absolute;
                inset: 0;
                border-radius: 50%;
                content: '';
            }
            .sp-action-ring {
                border: 3px solid rgba(13, 110, 253, 0.12);
            }
            .sp-action-ring::after {
                border: 3px solid transparent;
                border-top-color: var(--sp-action-color, #0d6efd);
                border-right-color: var(--sp-action-color, #0d6efd);
                animation: sp-action-spin 0.9s linear infinite;
            }
            .sp-action-icon {
                position: relative;
                z-index: 2;
                width: 54px;
                height: 54px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 18px;
                color: #ffffff;
                background: var(--sp-action-color, #0d6efd);
                box-shadow: 0 14px 34px rgba(15, 23, 42, 0.16);
                font-size: 21px;
                transform: scale(1);
                transition: transform 0.22s ease, background 0.22s ease;
            }
            .sp-action-modal.is-success .sp-action-ring::after,
            .sp-action-modal.is-error .sp-action-ring::after {
                animation: none;
                border-color: var(--sp-action-color, #0d6efd);
            }
            .sp-action-modal.is-success .sp-action-icon,
            .sp-action-modal.is-error .sp-action-icon {
                transform: scale(1.06);
            }
            .sp-action-modal.is-delete { --sp-action-color: #f1416c; }
            .sp-action-modal.is-duplicate { --sp-action-color: #0dcaf0; }
            .sp-action-modal.is-restart { --sp-action-color: #f59e0b; }
            .sp-action-modal.is-status { --sp-action-color: #22c55e; }
            .sp-action-modal.is-assign { --sp-action-color: #6366f1; }
            .sp-action-modal.is-save { --sp-action-color: #14b8a6; }
            .sp-action-modal.is-default { --sp-action-color: #0d6efd; }
            .sp-action-modal.is-success { --sp-action-color: #22c55e; }
            .sp-action-modal.is-error { --sp-action-color: #f1416c; }
            .sp-action-title {
                margin: 0;
                font-size: 1.18rem;
                font-weight: 750;
                color: #1f2937;
            }
            .sp-action-message {
                margin: 8px 0 0;
                font-size: 0.94rem;
                line-height: 1.6;
                color: #64748b;
            }
            .sp-action-progress {
                height: 7px;
                margin: 22px 0 0;
                border-radius: 999px;
                overflow: hidden;
                background: rgba(148, 163, 184, 0.16);
            }
            .sp-action-progress-bar {
                width: 38%;
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, var(--sp-action-color, #0d6efd), rgba(13, 202, 240, 0.9));
                animation: sp-action-progress 1.15s ease-in-out infinite;
            }
            .sp-action-modal.is-success .sp-action-progress-bar,
            .sp-action-modal.is-error .sp-action-progress-bar {
                width: 100%;
                animation: none;
            }
            .sp-action-close {
                display: none;
                margin-top: 22px;
                min-height: 42px;
                border-radius: 14px;
                font-weight: 600;
            }
            .sp-action-modal.is-error .sp-action-close {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            @keyframes sp-action-spin {
                to { transform: rotate(360deg); }
            }
            @keyframes sp-action-progress {
                0% { transform: translateX(-115%); }
                55% { transform: translateX(45%); }
                100% { transform: translateX(220%); }
            }
        </style>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery/jquery.min.js"></script>
        <?php _ec( load_files("css") );?>
        <?php _ec( add_script_to_header() )?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@15.0.1/build/css/intlTelInput.css">
        
    </head>
    <body class="<?php _ec( get_option("sidebar_type", "sidebar-small") )?> <?php _ec( get_option("theme_color", "light") )?>">
        <div class="loading">
            <div class="loading-icon">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <?php _ec( $this->include('Backend\Stackmin\Views\header'), false )?>

        <div class="d-flex h-100">
            <?php _ec( $this->include('Backend\Stackmin\Views\sidebar'), false )?>
            <?php _ec( $this->renderSection('content'), false )?>
        </div>

        <div class="sidebar-popover"></div>

        <div class="modal fade sp-confirm-modal" id="sp-confirm-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="sp-confirm-hero">
                            <div class="sp-confirm-icon">
                                <i class="fad fa-shield-check"></i>
                            </div>
                            <div>
                                <h5 class="sp-confirm-title" data-confirm-modal-title>Confirmar ação</h5>
                                <p class="sp-confirm-subtitle">Revisamos os próximos passos antes de continuar.</p>
                            </div>
                        </div>

                        <p class="sp-confirm-message" data-confirm-modal-message>Tem certeza de que deseja continuar com esta ação?</p>

                        <div class="sp-confirm-progress">
                            <div class="sp-confirm-progress-bar" data-confirm-modal-progress></div>
                        </div>

                        <p class="sp-confirm-hint" data-confirm-modal-hint>A confirmação será liberada em 2 segundos para evitar cliques acidentais.</p>

                        <div class="sp-confirm-actions">
                            <button type="button" class="btn btn-light" data-confirm-modal-cancel>Cancelar</button>
                            <button type="button" class="btn sp-confirm-approve is-waiting" data-confirm-modal-approve disabled>
                                <i class="fad fa-hourglass-half me-2"></i>Aguarde 2s
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade sp-action-modal is-default" id="sp-action-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="sp-action-icon-wrap">
                            <span class="sp-action-ring"></span>
                            <span class="sp-action-icon" data-action-modal-icon-wrap>
                                <i class="fad fa-bolt" data-action-modal-icon></i>
                            </span>
                        </div>

                        <h5 class="sp-action-title" data-action-modal-title>Executando solicitação</h5>
                        <p class="sp-action-message" data-action-modal-message>Estamos processando sua ação agora.</p>

                        <div class="sp-action-progress">
                            <div class="sp-action-progress-bar" data-action-modal-progress></div>
                        </div>

                        <button type="button" class="btn btn-light sp-action-close" data-action-modal-close>Fechar</button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php _ec( add_script_to_footer() )?>
        <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@15.0.2/build/js/intlTelInput.js"></script>
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script src="https://code.highcharts.com/maps/modules/map.js"></script>
        <script src="https://code.highcharts.com/mapdata/custom/world.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/tinymce/tinymce.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/nicescroll/nicescroll.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/izitoast/izitoast.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/pagination/pagination.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/webui-popover/webui-popover.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ui/jquery-ui.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/datetimepicker/timepicker-addon.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/emojionearea/emojionearea.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/tagsinput/bootstrap-tagsinput.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/owlcarousel/owl.carousel.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/daterangepicker/moment.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/daterangepicker/daterangepicker.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/fancybox/jquery.fancybox.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/minicolors/jquery.minicolors.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/select2/js/select2.full.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery.md5/jquery.md5.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/monthly/monthly.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ace/ace/ace.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ace/jquery-ace.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ace/ace/mode-php.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ace/ace/theme-monokai.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/js/layout.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/js/core.js"></script>
        <?php _ec( load_files("js") );?>

    </body>
</html>
