<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center mb-3 mt-2">
    <div class="bd-search position-relative me-auto py-3">
        <h2 class="mb-0 py-0"> <i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _e($config['name']) ?></h2>
        <p class="mb-0"><?php _e($config['desc']) ?></p>
    </div>
    <div class="">
        <div class="dropdown me-2">
            <div class="input-group input-group-sm sp-input-group border b-r-4">
                <span class="input-group-text border-0 fs-20 bg-gray-100 text-gray-800" id="sub-menu-search"><i class="fad fa-search"></i></span>
                <input type="text" class="ajax-pages-search ajax-filter form-control form-control-solid ps-15 border-0" name="keyword" value="" placeholder="<?php _e("Buscar") ?>" autocomplete="off">
                <a href="<?php _ec(get_module_url("index/update")) ?>" class="btn btn-light btn-active-light-primary m-r-1 border-end" title="<?php _e("Nova campanha") ?>" data-toggle="tooltip" data-placement="top"><i class="fad fa-plus text-primary"></i></a>
                <a href="<?php _ec(get_module_url('popup_report')) ?>" class="btn btn-light btn-active-light-success actionItem" data-popup="ReportBulkModal" title="<?php _e("Relatório") ?>" data-toggle="tooltip" data-placement="top"><i class="fad fa-file-chart-line text-success"></i></a>
            </div>
        </div>
    </div>
</div>

<?php if (get_data($datatable, "total_items") != 0) : ?>
    <div class="container">
        <div class="ajax-pages" data-bulk-live-root data-url="<?php _ec(get_module_url("ajax_list")) ?>" data-response=".ajax-result" data-per-page="<?php _ec(get_data($datatable, "per_page")) ?>" data-current-page="<?php _ec(get_data($datatable, "current_page")) ?>" data-total-items="<?php _ec(get_data($datatable, "total_items")) ?>" data-call-after="if (window.refreshBulkCampaignCards) window.refreshBulkCampaignCards();">
            <div class="ajax-result bulk-ajax-result mt-2"></div>
            <nav class="m-t-50 m-b-50 ajax-pagination m-auto text-center"></nav>
        </div>
    </div>

    <script type="text/javascript">
        $(function() {
            if (window.bulkCampaignLiveTimer) {
                clearInterval(window.bulkCampaignLiveTimer);
            }

            function canRefreshBulkCampaignCards() {
                if (document.hidden) {
                    return false;
                }

                if ($('.campaign-title-edit:not(.d-none), #edit-overlay, .bulk-card-actions-menu.show').length > 0) {
                    return false;
                }

                return $('[data-bulk-live-card]').length > 0;
            }

            function setBulkTooltip($element, title) {
                if (!$element.length) {
                    return;
                }

                title = title || '';

                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    var instance = bootstrap.Tooltip.getInstance($element[0]);

                    if (title === '') {
                        if (instance) {
                            instance.dispose();
                        }
                        $element.removeAttr('title data-bs-original-title data-live-tooltip-active data-live-tooltip-title');
                        return;
                    }

                    if (instance && $element.attr('data-live-tooltip-title') === title) {
                        $element.attr('data-live-tooltip-active', '1');
                        return;
                    }

                    if (instance) {
                        instance.dispose();
                    }

                    $element.attr('title', title);
                    $element.attr('data-bs-original-title', title);
                    $element.attr('data-live-tooltip-title', title);
                    $element.attr('data-live-tooltip-active', '1');
                    new bootstrap.Tooltip($element[0], {
                        trigger: 'hover focus',
                        container: 'body'
                    });
                    return;
                }

                $element.attr('title', title);
            }

            function formatBulkNumber(value) {
                value = parseInt(value || 0, 10);
                try {
                    return value.toLocaleString('pt-BR');
                } catch (e) {
                    return String(value);
                }
            }

            function resolveBulkErrorSummary(data) {
                var error = data.error_summary || {};
                var failed = parseInt(data.failed || 0, 10);
                var total = parseInt(data.total || 0, 10);

                if (!error.has_error && failed <= 0) {
                    return null;
                }

                var title = $.trim(error.title || 'Falhas registradas na campanha');
                var code = $.trim(String(error.code || ''));
                var message = $.trim(error.message || 'Abra o relatório da campanha para ver o detalhe por número.');
                var prefix = total > 0 && failed >= total
                    ? 'Todos os ' + formatBulkNumber(failed) + ' envios falharam. '
                    : formatBulkNumber(failed) + ' envio(s) com falha. ';
                var displayTitle = code !== '' ? '[' + code + '] ' + title : title;
                var displayMessage = prefix + message;
                var tooltip = $.trim(error.tooltip || (displayTitle + ' - ' + displayMessage));

                return {
                    title: displayTitle,
                    message: displayMessage,
                    tooltip: tooltip
                };
            }

            function updateBulkCampaignCard(card, data) {
                var $card = $(card);
                var percent = parseInt(data.progress_percent || 0, 10);

                $card.find('[data-live-sent]').text(data.sent || 0);
                $card.find('[data-live-failed]').text(data.failed || 0);
                $card.find('[data-live-pending]').text(data.pending || 0);
                $card.find('[data-live-next]').text(data.next_action || '-');
                $card.find('[data-live-interval]').text(data.interval || '-');
                $card.find('[data-live-status]').html(data.status_html || '-');
                $card.find('[data-live-progress-text]').text(percent + '%');
                $card.find('[data-live-sent-bar]').css('width', (data.sent_percent || 0) + '%');
                $card.find('[data-live-failed-bar]').css('width', (data.failed_percent || 0) + '%');
                $card.find('[data-live-pending-bar]').css('width', (data.pending_percent || 0) + '%');
                setBulkTooltip($card.find('[data-live-progress-tooltip]'), data.progress_tooltip || '');

                var $awaiting = $card.find('[data-live-awaiting]');
                if (data.is_waiting_first_dispatch) {
                    $awaiting.removeClass('d-none');
                    $awaiting.find('[data-live-awaiting-time]').text(data.first_dispatch_label || '');
                } else {
                    $awaiting.addClass('d-none');
                    $awaiting.find('[data-live-awaiting-time]').text('');
                }

                var $errorBox = $card.find('[data-live-error-box]');
                var errorSummary = resolveBulkErrorSummary(data);
                if ($errorBox.length) {
                    if (errorSummary) {
                        $errorBox.removeClass('d-none');
                        $errorBox.find('[data-live-error-title]').text(errorSummary.title);
                        $errorBox.find('[data-live-error-message]').text(errorSummary.message);
                        setBulkTooltip($errorBox, errorSummary.tooltip);
                    } else {
                        $errorBox.addClass('d-none');
                        $errorBox.find('[data-live-error-title]').text('');
                        $errorBox.find('[data-live-error-message]').text('');
                        setBulkTooltip($errorBox, '');
                    }
                }
            }

            function refreshBulkCampaignCards() {
                if (!$('[data-bulk-live-root]').length) {
                    clearInterval(window.bulkCampaignLiveTimer);
                    window.bulkCampaignLiveTimer = null;
                    return;
                }

                if (!canRefreshBulkCampaignCards()) {
                    return;
                }

                var ids = $('[data-bulk-live-card]').map(function() {
                    return $(this).data('id');
                }).get().filter(Boolean);

                if (!ids.length) {
                    return;
                }

                $.ajax({
                    url: '<?php _ec(get_module_url("live_status")) ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        csrf: csrf,
                        ids: ids
                    }
                }).done(function(response) {
                    var rows = response && response.data ? response.data : {};
                    $('[data-bulk-live-card]').each(function() {
                        var id = $(this).data('id');
                        if (rows[id]) {
                            updateBulkCampaignCard(this, rows[id]);
                        }
                    });
                });
            }

            window.refreshBulkCampaignCards = refreshBulkCampaignCards;

            Core.ajax_pages();

            window.bulkCampaignLiveTimer = setInterval(refreshBulkCampaignCards, 5000);
            setTimeout(refreshBulkCampaignCards, 500);
            setTimeout(refreshBulkCampaignCards, 1600);
            setTimeout(refreshBulkCampaignCards, 3500);
        });
    </script>
<?php else : ?>
    <div class="container">
        <div class="mw-400 container d-flex align-items-center align-self-center h-100 py-5">
            <div>
                <div class="text-center px-4">
                    <img class="mw-100 mh-300px" alt="" src="<?php _e(get_theme_url()) ?>Assets/img/empty2.png">
                    <h3 class="mt-4"><?php _e('No campaigns found') ?></h3>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>
