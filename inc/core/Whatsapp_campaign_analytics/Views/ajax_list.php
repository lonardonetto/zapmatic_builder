<?php if (!empty($result)) {
    foreach ($result as $row) {
        $c_name = !empty($row->campaign_name) ? $row->campaign_name : "Sem Nome (ID: {$row->schedule_id})";
        ?>
        <tr class="item border-bottom border-gray-100">
            <td class="py-2 px-3">
                <div class="d-flex flex-column">
                    <span class="campaign-title-forced"><?php echo $c_name; ?></span>
                    <span class="text-muted fw-bold" style="font-size: 14px !important;">ID: <?php echo $row->schedule_id; ?></span>
                </div>
            </td>
            <td>
                <span class="text-gray-800 fw-bold" style="font-size: 18px !important;"><?php echo datetime_show($row->start_date); ?></span>
            </td>
            <td class="text-center">
                <span class="badge badge-dark fw-boldest" style="font-size: 18px !important; padding: 6px 14px !important;"><?php echo $row->total_messages; ?></span>
            </td>
            <td class="text-center">
                <span class="text-primary counter-number-forced"><?php echo (int)$row->sent_count; ?></span>
            </td>
            <td class="text-center">
                <span class="text-info counter-number-forced"><?php echo (int)$row->delivered_count; ?></span>
            </td>
            <td class="text-center">
                <span class="text-success counter-number-forced"><?php echo (int)$row->read_count; ?></span>
            </td>
            <td class="text-center">
                <span class="text-danger counter-number-forced"><?php echo (int)$row->failed_count; ?></span>
            </td>
            <td class="text-end pe-0">
                <div class="d-flex justify-content-end gap-1">
                    <a href="<?php echo base_url('whatsapp_campaign_analytics/details/' . $row->schedule_id); ?>" class="btn btn-sm btn-light-primary btn-active-primary fw-bold" title="Ver Detalhes">
                        Detalhes
                    </a>
                    <a href="<?php echo base_url('whatsapp_campaign_analytics/export/' . $row->schedule_id); ?>" class="btn btn-sm btn-light-success btn-active-success fw-bold" title="Exportar XLSX">
                        Exportar
                    </a>
                    <a href="<?php echo base_url('whatsapp_campaign_analytics/delete/' . $row->schedule_id); ?>" class="btn btn-sm btn-light-danger btn-active-danger fw-bold actionItem" data-confirm="Tem certeza que deseja excluir permanentemente o relatório desta campanha?" data-call-success="Core.ajax_pages();" title="Excluir Relatório">
                        <i class="fad fa-trash-alt"></i>
                    </a>
                </div>
            </td>
        </tr>
    <?php }
} else { ?>
    <tr>
        <td colspan="8" class="text-center p-5">
            <span class="text-muted"><i class="fad fa-history fs-40 mb-3 d-block"></i> Nenhum relatório encontrado</span>
        </td>
    </tr>
<?php } ?>
