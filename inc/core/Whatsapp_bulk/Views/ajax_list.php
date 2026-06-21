<?php
if (!function_exists('whatsapp_bulk_campaign_meta')) {
    function whatsapp_bulk_campaign_meta($type)
    {
        if ((int)$type === 7) {
            return [
                'label' => __('Campanha de ligação'),
                'badge' => 'danger',
                'progress_label' => __('Ligações'),
                'next_label' => __('Próxima ligação'),
            ];
        }

        return [
            'label' => __('Campanha de mensagem'),
            'badge' => 'primary',
            'progress_label' => __('Enviados'),
            'next_label' => __('Próxima ação'),
        ];
    }
}
?>
<?php if (!empty($result)) { ?>
    <div class="spam-warning-container" data-bulk-spam-warning>
        <div class="alert alert-warning d-flex align-items-center warning-animation" role="alert">
            <div class="alert-icon me-3">
                <i class="fas fa-exclamation-triangle fa-lg fa-bounce"></i>
            </div>
            <div class="alert-content">
                <h5 class="alert-heading mb-1">Atenção ao spam</h5>
                <p class="mb-0">
                    Use com moderação para evitar banimento do WhatsApp. Recomendamos:
                    <span class="d-block mt-1">
                        <i class="fas fa-clock text-warning"></i> Intervalos entre mensagens
                        <i class="fas fa-users text-warning ms-3"></i> Grupos pequenos
                        <i class="fas fa-shield-alt text-warning ms-3"></i> Evite conteúdo repetitivo
                    </span>
                </p>
            </div>
            <button type="button" class="spam-warning-close" data-dismiss-spam-warning aria-label="<?php _e('Fechar aviso') ?>">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="row g-4">
        <?php foreach ($result as $key => $value) : ?>
            <?php $campaign_meta = whatsapp_bulk_campaign_meta($value->type ?? 1); ?>
            <?php
                $total = (int)($value->total_phone_number ?? 0);
                $sent = (int)($value->sent ?? 0);
                $failed = (int)($value->failed ?? 0);
                $pending = max(0, $total - $sent - $failed);
                $progress_tooltip = sprintf("%s / %s / %s", number_format($sent), number_format($failed), number_format($pending));
                $processed_percent = ($total > 0) ? round((($sent + $failed) / $total) * 100) : 0;
                $has_dispatch_activity = ($sent + $failed) > 0;
                $campaign_time_post = (int)($value->time_post ?? 0);
                $is_waiting_first_dispatch = (int)($value->status ?? 0) === 1 && !$has_dispatch_activity && $pending > 0 && $campaign_time_post > time();
                $first_dispatch_label = $campaign_time_post > 0 ? date('d/m/Y H:i', $campaign_time_post) : '';
                $failure_summary = is_array($value->failure_summary ?? null) ? $value->failure_summary : [];
                $has_failure_summary = !empty($failure_summary['has_error']) || $failed > 0;
                $failure_code = trim((string)($failure_summary['code'] ?? ''));
                $failure_title = trim((string)($failure_summary['title'] ?? 'Falhas registradas na campanha'));
                $failure_message = trim((string)($failure_summary['message'] ?? 'Abra o relatório da campanha para ver o detalhe por número.'));
                $failure_prefix = $total > 0 && $failed >= $total
                    ? sprintf('Todos os %s envios falharam. ', number_format($failed))
                    : sprintf('%s envio(s) com falha. ', number_format($failed));
                $failure_display_title = $failure_code !== '' ? '[' . $failure_code . '] ' . $failure_title : $failure_title;
                $failure_display_message = $has_failure_summary ? $failure_prefix . $failure_message : '';
                $failure_tooltip = trim((string)($failure_summary['tooltip'] ?? ''));
                if ($failure_tooltip === '' && $has_failure_summary) {
                    $failure_tooltip = trim($failure_display_title . ' - ' . $failure_display_message);
                }
            ?>
            <div class="col-md-3">
                <div class="card card-flush bulk-campaign-card" data-bulk-live-card data-id="<?php _e($value->ids) ?>">
                    <div class="card-header py-2">
                        <div class="d-flex align-items-center w-100">
                            <div class="form-check form-check-sm form-check-custom form-check-solid me-1">
                                <input class="form-check-input checkbox-item" type="checkbox" name="ids[]" value="<?php _e($value->ids) ?>">
                            </div>
                            <div class="campaign-title-wrapper flex-grow-1 d-flex align-items-start overflow-hidden">
                                <div class="campaign-title-content">
                                    <h3 class="card-title text-truncate campaign-title mb-0" 
                                        id="title-<?php _e($value->ids)?>" 
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="top" 
                                        title="<?php _e($value->name)?>"
                                    >
                                        <?php _e($value->name)?>
                                    </h3>
                                    <div class="campaign-type-badge-row">
                                        <span class="badge badge-light-<?php _ec($campaign_meta['badge']) ?>"><?php _e($campaign_meta['label']) ?></span>
                                    </div>
                                </div>
                                <div class="campaign-title-edit d-none" id="edit-<?php _e($value->ids)?>">
                                    <div class="edit-title-container">
                                        <input type="text" 
                                               class="form-control form-control-lg" 
                                               value="<?php _e($value->name)?>" 
                                               id="input-<?php _e($value->ids)?>"
                                               style="width: 100%; font-size: 16px; padding: 10px; margin-bottom: 10px;">
                                        <div class="preview-text mb-2 text-muted">
                                            Preview: <span class="preview-content"><?php _e($value->name)?></span>
                                        </div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-success save-title" data-id="<?php _e($value->ids)?>">
                                                <i class="fas fa-check me-1"></i> Salvar
                                            </button>
                                            <button type="button" class="btn btn-light cancel-edit" data-id="<?php _e($value->ids)?>">
                                                <i class="fas fa-times me-1"></i> Cancelar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-light edit-title-btn ms-1" data-id="<?php _e($value->ids)?>">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            <?php if (!empty($value->schedule_window_has_rules) && !empty($value->schedule_window_short)) : ?>
                                <div class="campaign-schedule-ribbon" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php _e($value->schedule_window_full) ?>">
                                    <i class="fas fa-calendar-alt text-info"></i>
                                    <span class="campaign-schedule-ribbon-text"><?php _e($value->schedule_window_short) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="campaign-awaiting-ribbon <?php _ec($is_waiting_first_dispatch ? '' : 'd-none') ?>" data-live-awaiting>
                                    <i class="fas fa-hourglass-start text-info"></i>
                                    <span><?php _e('Aguardando primeiro disparo') ?></span>
                                    <strong data-live-awaiting-time><?php _ec($first_dispatch_label) ?></strong>
                                </div>

                            <div class="campaign-error-ribbon <?php _ec($has_failure_summary ? '' : 'd-none') ?>" data-live-error-box data-bs-toggle="tooltip" data-bs-placement="top" title="<?php _e($failure_tooltip) ?>">
                                <div class="campaign-error-icon"><i class="fas fa-exclamation-triangle"></i></div>
                                <div class="campaign-error-content">
                                    <strong data-live-error-title><?php _e($failure_display_title) ?></strong>
                                    <span data-live-error-message><?php _e($failure_display_message) ?></span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-paper-plane text-primary"></i> <?php _e($campaign_meta['progress_label']) ?></span>
                                <div class="progress flex-grow-1 ms-3" style="height: 24px;" 
                                     data-bs-toggle="tooltip" 
                                     data-bs-placement="top" 
                                     data-live-progress-tooltip
                                     title="<?php _e($progress_tooltip)?>">
                                    <div class="progress-stacked position-relative w-100">
                                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" 
                                             data-live-sent-bar
                                             style="width: <?php _ec(number_format(($total > 0) ? ($sent/$total)*100 : 0, 1))?>%">
                                        </div>
                                        <div class="progress-bar bg-danger progress-bar-striped progress-bar-animated" role="progressbar" 
                                             data-live-failed-bar
                                             style="width: <?php _ec(number_format(($total > 0) ? ($failed/$total)*100 : 0, 1))?>%">
                                        </div>
                                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" 
                                             data-live-pending-bar
                                             style="width: <?php _ec(number_format(($total > 0) ? ($pending/$total)*100 : 0, 1))?>%">
                                        </div>
                                        <div class="progress-text" data-live-progress-text>
                                            <?php _ec($processed_percent) ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-check text-success"></i> <?php _e("Sucesso") ?></span>
                                <span data-live-sent><?php _e($sent)?></span>
                            </div>

                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-times text-danger"></i> <?php _e("Falhas") ?></span>
                                <span data-live-failed><?php _e($failed)?></span>
                            </div>

                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-clock text-warning"></i> <?php _e("Pendentes") ?></span>
                                <span data-live-pending><?php _e($pending)?></span>
                            </div>

                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-signal"></i> <?php _e("Status") ?></span>
                                <span data-live-status>
                                <?php
                                    switch ($value->status) {
                                        case 0:
                                            $type = '<i class="fs-18 fas fa-pause-circle text-warning"></i>';
                                            break;

                                        case 1:
                                            if ($is_waiting_first_dispatch) {
                                                $type = '<div class="status-waiting"><i class="fs-18 fas fa-hourglass-half text-info"></i></div>';
                                            } else {
                                                $type = '<div class="status-running"><i class="fs-18 fas fa-signal text-primary"></i></div>';
                                            }
                                            break;

                                        default:
                                            if ($failed > 0 && $sent === 0 && $pending === 0) {
                                                $type = '<i class="fs-18 fas fa-times-circle text-danger"></i>';
                                            } elseif ($failed > 0) {
                                                $type = '<i class="fs-18 fas fa-exclamation-circle text-warning"></i>';
                                            } else {
                                                $type = '<i class="fs-18 fas fa-check-circle text-success"></i>';
                                            }
                                            break;
                                    }
                                    _ec($type);
                                    ?>
                                </span>
                            </div>

                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-stopwatch"></i> <?php _e($campaign_meta['next_label']) ?></span>
                                <span data-live-next><?php 
                                    if ($is_waiting_first_dispatch && $first_dispatch_label !== '') {
                                        _ec($first_dispatch_label);
                                    } else {
                                        _e(($pending >= 0 && isset($value->status) && $value->status != 2) ? 
                                            (isset($value->time_post) ? datetime_show($value->time_post) : '-') : '-');
                                    }
                                ?></span>
                            </div>

                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-clock"></i> <?php _e('Intervalo') ?></span>
                                <span data-live-interval><?php 
                                    $min_delay = isset($value->min_delay) ? $value->min_delay : 0;
                                    $max_delay = isset($value->max_delay) ? $value->max_delay : 0;
                                    if($min_delay == $max_delay) {
                                        _e(sprintf("%ds", $min_delay));
                                    } else {
                                        _e(sprintf("%ds - %ds", $min_delay, $max_delay));
                                    }
                                ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bulk-campaign-card-footer">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?php _e( get_module_url('index/update/'.$value->ids) )?>" class="btn btn-sm btn-light btn-active-light-primary bulk-card-action-btn" aria-label="<?php _e('Configurações da campanha') ?>"><i class="fad fa-cog"></i></a>
                            <div class="dropdown bulk-card-actions">
                                <button class="btn btn-sm btn-light btn-active-light-primary dropdown-toggle campaign-actions-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?php _e('Mais ações da campanha') ?>"><i class="fad fa-ellipsis-v"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end bulk-card-actions-menu">
                                    <li><a class="dropdown-item" href="<?php _e( get_module_url('report/'.$value->ids) )?>" target="_blank"><i class="fad fa-file-chart-line"></i> <?php _e("Relatório")?></a></li>
                                    <li><a class="dropdown-item actionItem" href="<?php _e( get_module_url('duplicate/'.$value->ids) )?>" data-confirm="<?php _e('Tem certeza que deseja duplicar esta campanha?')?>" data-redirect=""><i class="fad fa-copy"></i> <?php _e("Duplicar")?></a></li>
                                    <li><a class="dropdown-item actionItem" href="<?php _e( get_module_url('restart/'.$value->ids) )?>" data-confirm="<?php _e('Tem certeza que deseja reiniciar esta campanha?')?>" data-call-success="Core.ajax_pages();"><i class="fad fa-redo"></i> <?php _e("Reiniciar")?></a></li>
                                    <li><a class="dropdown-item actionItem" href="<?php _e( get_module_url('status/'.$value->ids) )?>" data-call-success="Core.ajax_pages();"><i class="fad fa-toggle-on"></i> <?php _e("Ativar/Desativar")?></a></li>
                                    <li><a class="dropdown-item actionItem" href="<?php _e( get_module_url('delete/'.$value->ids) )?>" data-confirm="<?php _e('Tem certeza que deseja excluir esta campanha?')?>" data-call-success="Core.ajax_pages();"><i class="fad fa-trash-alt"></i> <?php _e("Excluir")?></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <div class="text-center py-2 mt-3 bulk-card-page-credit" style="color: #6c757d; font-size: 13px;">
        Desenvolvido por <span style="color: #28a745; font-weight: 500;">Zapmatic Tecnologic</span>
    </div>

    <style>
    .spam-warning-container {
        width: 100%;
        margin: 0 0 14px;
    }
    .spam-warning-container.is-hidden {
        display: none !important;
    }
    .spam-warning-container .alert {
        position: relative;
        min-height: 0;
        margin: 0 !important;
        padding: 12px 46px 12px 14px;
        border-radius: 10px;
        border-color: rgba(245, 185, 20, 0.34);
        background: linear-gradient(90deg, rgba(255, 244, 196, 0.95), rgba(255, 250, 230, 0.95));
        color: #6b4b00;
    }
    .spam-warning-container .alert-icon {
        width: 26px;
        display: inline-flex;
        justify-content: center;
        color: #9b7600;
    }
    .spam-warning-container .alert-heading {
        color: #5f4300;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.2;
    }
    .spam-warning-container .alert-content {
        min-width: 0;
        font-size: 13px;
        line-height: 1.35;
    }
    .spam-warning-container .alert-content span {
        display: flex !important;
        flex-wrap: wrap;
        gap: 8px 16px;
        margin-top: 4px !important;
    }
    .spam-warning-container .alert-content span i {
        margin-right: 4px;
    }
    .spam-warning-container .alert-content span i.ms-3 {
        margin-left: 0 !important;
    }
    .spam-warning-close {
        position: absolute;
        top: 10px;
        right: 12px;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 9px;
        background: rgba(95, 67, 0, 0.08);
        color: #6b4b00;
        transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
    }
    .spam-warning-close:hover,
    .spam-warning-close:focus {
        background: rgba(95, 67, 0, 0.16);
        color: #3f2d00;
        transform: scale(1.04);
    }
    .campaign-title-wrapper {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        min-width: 0;
    }
    .campaign-title-content {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-width: 0;
    }
    .campaign-title {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 0;
        cursor: help;
        min-width: 0;
    }
    .campaign-type-badge-row {
        display: flex;
        margin-top: 4px;
    }
    .campaign-type-badge-row .badge {
        max-width: 100%;
        white-space: nowrap;
    }
    .campaign-schedule-ribbon {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        padding: 7px 10px;
        border-radius: 10px;
        background: linear-gradient(135deg, rgba(13, 202, 240, 0.12), rgba(13, 110, 253, 0.08));
        border: 1px solid rgba(13, 110, 253, 0.12);
        color: #3f4254;
        font-size: 12px;
        line-height: 1.4;
        cursor: help;
    }
    .campaign-schedule-ribbon-text {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .campaign-awaiting-ribbon {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        border-radius: 10px;
        background: linear-gradient(135deg, rgba(13, 202, 240, 0.10), rgba(13, 110, 253, 0.04));
        border: 1px dashed rgba(13, 110, 253, 0.18);
        color: #3f4254;
        font-size: 12px;
        line-height: 1.4;
        flex-wrap: wrap;
    }
    .campaign-awaiting-ribbon strong {
        font-weight: 600;
        color: #0d6efd;
    }
    .campaign-error-ribbon {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        min-width: 0;
        padding: 8px 10px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.12), rgba(255, 193, 7, 0.08));
        border: 1px solid rgba(220, 53, 69, 0.18);
        color: #5c1b25;
        box-shadow: 0 10px 24px rgba(220, 53, 69, 0.08);
        cursor: help;
    }
    .campaign-error-icon {
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 26px;
        border-radius: 9px;
        background: rgba(220, 53, 69, 0.12);
        color: #dc3545;
    }
    .campaign-error-content {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .campaign-error-content strong {
        display: block;
        color: #842029;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .campaign-error-content span {
        display: -webkit-box;
        color: #6d2a31;
        font-size: 11px;
        line-height: 1.35;
        overflow: hidden;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
    }
    .bulk-campaign-card {
        height: auto !important;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: visible;
    }
    .bulk-campaign-card .card-header {
        min-height: 0;
        padding-top: 9px !important;
        padding-bottom: 7px !important;
    }
    .bulk-campaign-card .card-body {
        flex: 0 0 auto;
        padding: 11px 14px 7px !important;
    }
    .bulk-campaign-card .card-body > .d-flex.flex-column {
        gap: 8px !important;
    }
    .bulk-campaign-card .d-flex.justify-content-between {
        align-items: center;
        min-height: 21px;
        font-size: 12px;
        line-height: 1.25;
    }
    .bulk-campaign-card .progress {
        height: 20px !important;
    }
    .bulk-campaign-card-footer {
        position: relative;
        overflow: visible;
        margin-top: 2px;
        padding: 5px 14px 11px !important;
        border-top: 0;
    }
    .bulk-card-action-btn,
    .campaign-actions-toggle {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        border: 1px solid rgba(63, 66, 84, 0.08);
        background: #f8f9fb;
        box-shadow: none;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
    }
    .campaign-actions-toggle::after {
        display: none;
    }
    .bulk-card-action-btn:hover,
    .bulk-card-action-btn:focus,
    .campaign-actions-toggle:hover,
    .campaign-actions-toggle:focus,
    .campaign-actions-toggle.show {
        background: #ffffff !important;
        border-color: rgba(13, 110, 253, 0.18);
        color: var(--bs-primary);
        box-shadow: 0 8px 18px rgba(13, 110, 253, 0.10);
        opacity: 1;
    }
    .bulk-card-actions {
        position: relative;
    }
    .bulk-card-actions-menu {
        position: absolute !important;
        right: 0 !important;
        left: auto !important;
        top: auto !important;
        bottom: calc(100% + 12px) !important;
        transform: none !important;
        inset: auto 0 calc(100% + 12px) auto !important;
        z-index: 1085;
        min-width: 220px;
        padding: 8px;
        border: 1px solid rgba(63, 66, 84, 0.08);
        border-radius: 16px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
        margin: 0 !important;
        background: #ffffff;
        list-style: none;
    }
    .bulk-card-actions-menu.show {
        display: block !important;
        animation: none;
        transform-origin: bottom right;
    }
    .bulk-card-actions-menu li {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .bulk-card-actions-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 12px;
        padding: 10px 12px;
        color: #3f4254;
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .bulk-card-actions-menu .dropdown-item i {
        width: 16px;
        text-align: center;
    }
    .bulk-card-actions-menu .dropdown-item:hover,
    .bulk-card-actions-menu .dropdown-item:focus {
        background: rgba(13, 110, 253, 0.08);
        color: var(--bs-primary);
    }
    .edit-title-btn {
        flex: 0 0 auto;
    }
    .edit-title-container {
        position: fixed;
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 1060;
        width: 90%;
        max-width: 400px;
    }

    .edit-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1050;
    }

    .preview-text {
        background: #f8f9fa;
        padding: 8px;
        border-radius: 4px;
        font-size: 14px;
    }

    .preview-content {
        font-weight: 500;
        color: #2c3338;
    }

    .progress {
        background-color: #f0f0f0;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
    }

    .progress-stacked {
        height: 100%;
        display: flex;
        position: relative;
    }

    .progress-bar {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 600;
        color: #fff;
        text-shadow: 1px 1px 1px rgba(0,0,0,0.2);
        transition: none;
    }

    .progress-bar.bg-success {
        background-color: #28a745;
    }

    .progress-bar.bg-danger {
        background-color: #dc3545;
    }

    .progress-bar.bg-primary {
        background-color: #4f8bff;
    }

    .progress-value {
        opacity: 0;
        transition: opacity 0.3s;
    }

    .progress-bar[style*="width: 0"] .progress-value {
        opacity: 0;
    }

    .progress-bar:not([style*="width: 0"]) .progress-value {
        opacity: 1;
    }

    .progress-total {
        color: #000;
        font-size: 13px;
        font-weight: 600;
        text-shadow: 1px 1px 1px rgba(255,255,255,0.5);
        z-index: 2;
    }

    .status-running i {
        animation: pulse 1.5s infinite;
    }

    .status-waiting i {
        animation: pulse 2.4s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
        }
        50% {
            opacity: 0.4;
        }
        100% {
            opacity: 1;
        }
    }

    .warning-animation i {
        animation: bounce 1s infinite;
    }

    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-3px);
        }
    }

    .progress-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .progress {
        flex: 1;
        background-color: #f0f0f0;
        border-radius: 4px;
        overflow: hidden;
        margin: 0;
    }

    .progress-percentage {
        font-size: 12px;
        font-weight: 500;
        color: var(--bs-gray-600);
        min-width: 35px;
        text-align: right;
    }

    .progress-bar {
        transition: width 0.3s ease;
    }

    .progress-bar.bg-success {
        background-color: var(--bs-success);
    }

    .progress-bar.bg-danger {
        background-color: var(--bs-danger);
    }

    .progress-bar.bg-light {
        background-color: var(--bs-gray-200);
    }

    .progress-text {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 12px;
        font-weight: 500;
        text-shadow: 0 1px 1px rgba(0,0,0,0.2);
        z-index: 1;
    }

    /* Estilos responsivos para mobile */
    @media (max-width: 768px) {
        .edit-title-container {
            position: fixed;
            left: 50% !important;
            top: 50% !important;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 320px;
            margin: 0 auto;
        }

        .edit-title-container input {
            width: 100%;
            padding: 10px;
            font-size: 16px; /* Previne zoom no iOS */
            margin-bottom: 10px;
        }

        .edit-title-container .btn {
            padding: 10px 15px;
            font-size: 14px;
            width: 100%;
            margin-bottom: 5px;
        }

        /* Ajuste para botões em linha */
        .edit-title-container .btn-group {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .edit-title-container .btn-group .btn {
            flex: 1;
        }
    }

    /* Ajustes gerais */
    .card {
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Reduz espaço entre aviso e cards */
    .alert {
        margin-bottom: 15px !important;
    }

    /* Ajustes para mobile */
    @media (max-width: 768px) {
        /* Container principal */
        .container-fluid {
            padding: 10px !important;
        }

        /* Reduz ainda mais o espaço entre alerta e cards no mobile */
        .alert {
            margin-bottom: 10px !important;
            padding: 10px 15px !important;
        }

        .spam-warning-container .alert {
            padding-right: 44px !important;
        }

        /* Centraliza e ajusta cards no mobile */
        .card {
            margin-left: auto !important;
            margin-right: auto !important;
            width: 100% !important;
            max-width: 500px !important;
            margin-bottom: 15px !important;
        }

        /* Ajusta o grid no mobile */
        .row {
            margin: 0 !important;
        }

        .col-lg-4, .col-md-4, .col-sm-6 {
            padding: 0 5px !important;
        }

        /* Ajusta o padding interno dos cards */
        .card-body {
            padding: 15px !important;
        }
    }

    /* Ajustes específicos para telas muito pequenas */
    @media (max-width: 576px) {
        .container-fluid {
            padding: 5px !important;
        }

        .card {
            margin-bottom: 10px !important;
        }

        .card-body {
            padding: 12px !important;
        }
    }

    /* Ajuste do overlay */
    #edit-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1050;
        display: none; /* Começa oculto por padrão */
    }
    </style>
    <script>
    $(document).ready(function(){
        var spamWarningStorageKey = 'zapmatic_bulk_spam_warning_closed';

        function getSpamWarningClosed() {
            try {
                return window.localStorage && localStorage.getItem(spamWarningStorageKey) === '1';
            } catch (error) {
                return false;
            }
        }

        function setSpamWarningClosed() {
            try {
                if (window.localStorage) {
                    localStorage.setItem(spamWarningStorageKey, '1');
                }
            } catch (error) {}
        }

        function syncSpamWarningState() {
            if (getSpamWarningClosed()) {
                $('[data-bulk-spam-warning]').addClass('is-hidden');
            }
        }

        syncSpamWarningState();

        $(document).off('click', '[data-dismiss-spam-warning]').on('click', '[data-dismiss-spam-warning]', function(e) {
            e.preventDefault();
            setSpamWarningClosed();
            $(this).closest('[data-bulk-spam-warning]').slideUp(160, function() {
                $(this).addClass('is-hidden').css('display', '');
            });
        });

        // Função para reinicializar tooltips
        function initTooltips() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
        initTooltips();

        // Remove qualquer overlay existente ao carregar a página
        $('#edit-overlay').remove();
        
        // Função para garantir que não existam overlays duplicados
        function cleanupOverlays() {
            $('.edit-overlay, #edit-overlay').remove();
        }
        
        // Limpa overlays ao iniciar
        cleanupOverlays();
        
        // Ao clicar no botão de editar
        $(document).on('click', '.edit-title-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Remove overlays antigos antes de criar um novo
            cleanupOverlays();
            
            var $button = $(this);
            var $editor = $('#edit-' + $button.data('id'));
            
            // Cria e adiciona o overlay
            $('body').append('<div id="edit-overlay"></div>');
            $('#edit-overlay').fadeIn(200);
            
            $editor.removeClass('d-none');
            positionEditor($button, $editor);
            $editor.find('input').focus();
        });

        // Garante que o overlay seja removido ao cancelar
        $(document).on('click', '.cancel-edit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var id = $(this).data('id');
            $('#edit-' + id).addClass('d-none');
            cleanupOverlays();
        });

        // Ajusta o comportamento do save para limpar corretamente
        $(document).on('click', '.save-title', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $btn = $(this);
            if($btn.hasClass('disabled')) return false;
            
            var id = $btn.data('id');
            var $editor = $('#edit-' + id);
            var newTitle = $editor.find('input').val().trim();
            
            if (!newTitle) {
                Core.notify('<?php _e("Campaign name cannot be empty") ?>', 'error');
                return false;
            }

            $btn.addClass('disabled').html('<i class="fas fa-spinner fa-spin"></i>');
            
            var formData = new FormData();
            formData.append('update_name_only', 'true');
            formData.append('ids', id);
            formData.append('name', newTitle);

            $.ajax({
                url: '<?php _e(get_module_url("save")) ?>/' + id,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(result) {
                    $('#title-' + id).text(newTitle);
                    
                    setTimeout(function() {
                        $editor.addClass('d-none');
                        cleanupOverlays();
                        $btn.removeClass('disabled').html('Salvar');
                        Core.notify('<?php _e("Campaign name updated successfully") ?>', 'success');
                    }, 3000);
                },
                error: function(xhr, status, error) {
                    console.error('Ajax Error:', error);
                    $btn.removeClass('disabled').html('Salvar');
                    cleanupOverlays();
                    Core.notify('<?php _e("Network error occurred") ?>', 'error');
                }
            });
            
            return false;
        });

        // Função para posicionar o editor
        function positionEditor($button, $editor) {
            if (window.innerWidth <= 768) {
                // Em dispositivos móveis, centraliza na tela
                $editor.find('.edit-title-container').css({
                    'position': 'fixed',
                    'left': '50%',
                    'top': '50%',
                    'transform': 'translate(-50%, -50%)'
                });
            } else {
                // Em desktop, posiciona relativo ao botão
                var buttonPos = $button.offset();
                var windowWidth = $(window).width();
                var editorWidth = 400;
                
                var left = buttonPos.left - (editorWidth / 2);
                if (left < 20) left = 20;
                if (left + editorWidth > windowWidth - 20) left = windowWidth - editorWidth - 20;
                
                $editor.find('.edit-title-container').css({
                    'position': 'fixed',
                    'top': buttonPos.top + $button.outerHeight() + 10,
                    'left': left,
                    'transform': 'none'
                });
            }
        }

        // Atualiza posição ao redimensionar
        $(window).on('resize', function() {
            var $visibleEditor = $('.campaign-title-edit:not(.d-none)');
            if ($visibleEditor.length) {
                var $button = $('.edit-title-btn[data-id="' + $visibleEditor.data('id') + '"]');
                positionEditor($button, $visibleEditor);
            }
        });

        // Previne que o overlay feche o toggle
        $(document).on('click', '#edit-overlay', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });

        // Previne que cliques dentro do editor fechem o toggle
        $(document).on('click', '.edit-title-container', function(e) {
            e.stopPropagation();
        });

        // Função para limpar todos os tooltips
        function clearAllTooltips() {
            $('.tooltip').remove();
            $('.live-tooltip').each(function() {
                var tooltip = bootstrap.Tooltip.getInstance(this);
                if (tooltip) {
                    tooltip.dispose();
                }
            });
        }

        // Tooltip dinâmico ao editar título
        $(document).on('input', '.live-tooltip', function() {
            var $input = $(this);
            var currentText = $input.val();
            
            // Limpa tooltips existentes
            clearAllTooltips();
            
            // Se o campo estiver vazio, não mostra tooltip
            if (!currentText.trim()) {
                return;
            }
            
            // Cria novo tooltip com o texto atualizado
            var newTooltip = new bootstrap.Tooltip($input[0], {
                title: currentText,
                placement: 'bottom',
                trigger: 'manual',
                template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner bg-dark" style="max-width: none; padding: 8px 12px;"></div></div>'
            });
            
            // Mostra o tooltip
            newTooltip.show();
        });

        // Esconde o tooltip quando sair do modo de edição
        $(document).on('click', '.cancel-edit, .save-title', function() {
            clearAllTooltips();
        });

        // Mostra o tooltip inicial quando entrar no modo de edição
        $(document).on('click', '.edit-title-btn', function() {
            var $input = $('#input-' + $(this).data('id'));
            var currentText = $input.val();

            // Limpa tooltips existentes
            clearAllTooltips();

            if (currentText.trim()) {
                setTimeout(function() {
                    var newTooltip = new bootstrap.Tooltip($input[0], {
                        title: currentText,
                        placement: 'bottom',
                        trigger: 'manual',
                        template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner bg-dark" style="max-width: none; padding: 8px 12px;"></div></div>'
                    });
                    newTooltip.show();
                }, 100);
            }
        });

        // Limpa tooltips ao clicar fora
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.campaign-title-edit').length) {
                clearAllTooltips();
            }
        });

        // Função para animar as barras de progresso
        function animateProgressBars() {
            $('.animate-progress').each(function() {
                var $bar = $(this);
                var targetWidth = $bar.data('target');
                
                // Reseta a barra
                $bar.css({
                    'width': '0%',
                    'transition': 'none'
                });
                
                // Força um reflow
                $bar[0].offsetHeight;
                
                // Anima para o valor final
                $bar.css({
                    'transition': 'width 1.5s ease-in-out',
                    'width': targetWidth + '%'
                });
            });
        }

        // Executa a animação quando a página carrega
        setTimeout(animateProgressBars, 500);

        // Re-executa a animação após atualizações AJAX
        $(document).ajaxComplete(function() {
            setTimeout(animateProgressBars, 500);
        });

        // Enter key to save
        $(document).off('keypress', '.campaign-title-edit input').on('keypress', '.campaign-title-edit input', function(e){
            if(e.which == 13) {
                e.preventDefault();
                e.stopPropagation();
                $(this).closest('.campaign-title-edit').find('.save-title').click();
            }
        });

        // Prevent edit field from closing when clicked
        $(document).off('click', '.campaign-title-edit').on('click', '.campaign-title-edit', function(e) {
            e.stopPropagation();
        });
    });

    $(window).on('load', function() {
        // Inicializa os tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
    </script>
<?php } else { ?>
    <div class="mw-400 container d-flex align-items-center align-self-center h-100 py-5">
        <div>
            <div class="text-center px-4">
                <img class="mw-100 mh-300px" alt="" src="<?php _e(get_theme_url()) ?>Assets/img/empty2.png">
                <h3 class="mt-4"><?php _e('No campaigns found') ?></h3>
            </div>
        </div>
    </div>
<?php } ?>
