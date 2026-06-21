<?php 
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="relatorio.xls"');
header('Cache-Control: max-age=0');
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Relatório</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
</head>
<body>
<?php
    $is_call_campaign = (int)($result->type ?? 1) === 7;
    $is_cloud_parallel = whatsapp_bulk_is_cloud_parallel($result);
    $report_items = whatsapp_bulk_get_report_items($result);
    $summary = whatsapp_bulk_count_report_items($report_items);
    $campaign_type_label = $is_call_campaign ? 'Campanha de ligação' : 'Campanha de mensagem';
    $total_label = $is_call_campaign ? 'Total de tentativas' : 'Total de mensagens';
    $success_label = $is_call_campaign ? 'Ligações iniciadas' : 'Mensagens enviadas';
    $failed_label = $is_call_campaign ? 'Tentativas de ligação com falha' : 'Mensagens não enviadas';
    $pending_label = 'Pendente / Processando';
    $success_status_label = $is_call_campaign ? 'Ligação iniciada' : 'Envios bem-sucedidos';
    $failed_status_label = $is_call_campaign ? 'Ligação falhou' : 'Envios frustrados';
?>
<table border="1">
    <tr>
        <th colspan="6" style="background-color: #428200; font-size: 30px; color: #fff; text-align: center;">
            <?php echo mb_convert_encoding($result->name, 'HTML-ENTITIES', 'UTF-8'); ?>
        </th>
    </tr>
    <tr>
        <th style="background: #bada99;"><?php echo mb_convert_encoding("Lista de contatos", 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <th colspan="5"><?php echo mb_convert_encoding($result->contact_name, 'HTML-ENTITIES', 'UTF-8'); ?></th>
    </tr>
    <tr>
        <th style="background: #bada99;"><?php echo mb_convert_encoding('Tipo de campanha', 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <th colspan="5"><?php echo mb_convert_encoding($campaign_type_label, 'HTML-ENTITIES', 'UTF-8'); ?></th>
    </tr>
    <?php if ($is_cloud_parallel): ?>
    <tr>
        <th style="background: #bada99;"><?php echo mb_convert_encoding('Modo de disparo', 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <th colspan="2"><?php echo mb_convert_encoding('Cloud API simultâneo', 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <th style="background: #bada99;"><?php echo mb_convert_encoding('Nível salvo', 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <th colspan="2"><?php echo (int)($result->cloud_parallel_level ?? 0); ?></th>
    </tr>
    <?php endif; ?>
    <tr>
        <th style="background: #bada99;"><?php echo mb_convert_encoding("Delay mínimo usado", 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <th colspan="2"><?php echo $result->min_delay; ?></th>
        <th style="background: #bada99;"><?php echo mb_convert_encoding("Delay máximo usado", 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <th colspan="2"><?php echo $result->max_delay; ?></th>
    </tr>
    <tr>
        <th style="background: #bada99;"><?php echo mb_convert_encoding("Iniciada", 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <th colspan="2"><?php echo date("d/m/Y H:i", $result->created); ?></th>
        <th style="background: #bada99;"><?php echo mb_convert_encoding("Finalizada", 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <th colspan="2"><?php echo date("d/m/Y H:i", $result->time_post); ?></th>
    </tr>
    <?php
    $sucessos = (int)($summary['success'] ?? 0);
    $falhas = (int)($summary['failed'] ?? 0);
    $pendentes = (int)($summary['pending'] ?? 0);
    $total = (int)($summary['total'] ?? 0);
    $taxa_sucesso = $total > 0 ? round(($sucessos / $total) * 100, 2) : 0;
    ?>
    <tr>
        <th colspan="6">&nbsp;</th>
    </tr>
    <tr>
        <th colspan="2" style="background: #bada99;"><?php echo mb_convert_encoding($total_label, 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <td colspan="4" style="text-align: center;"><?php echo $total; ?></td>
    </tr>
    <tr>
        <th colspan="2" style="background: #bada99;"><?php echo mb_convert_encoding($success_label, 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <td colspan="4" style="text-align: center; color: #009f19;"><?php echo $sucessos; ?></td>
    </tr>
    <tr>
        <th colspan="2" style="background: #bada99;"><?php echo mb_convert_encoding($failed_label, 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <td colspan="4" style="text-align: center; color: #f00;"><?php echo $falhas; ?></td>
    </tr>
    <?php if ($is_cloud_parallel): ?>
    <tr>
        <th colspan="2" style="background: #bada99;"><?php echo mb_convert_encoding($pending_label, 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <td colspan="4" style="text-align: center; color: #0d6efd;"><?php echo $pendentes; ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <th colspan="2" style="background: #bada99;"><?php echo mb_convert_encoding("Taxa de Sucesso", 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <td colspan="4" style="text-align: center;"><?php echo $taxa_sucesso; ?>%</td>
    </tr>
    <tr>
        <th colspan="6">&nbsp;</th>
    </tr>
    <tr>
        <th colspan="3" style="background: #bada99;"><?php echo mb_convert_encoding("Números", 'HTML-ENTITIES', 'UTF-8'); ?></th>
        <th colspan="3" style="background: #bada99;"><?php echo mb_convert_encoding("Status", 'HTML-ENTITIES', 'UTF-8'); ?></th>
    </tr>
    <?php if (!empty($report_items)): ?>
        <?php foreach ($report_items as $value): ?>
            <?php if (is_object($value)): ?>
                <tr>
                    <td colspan="3" style="mso-number-format:'@';"><?php echo mb_convert_encoding((string)$value->phone_number, 'HTML-ENTITIES', 'UTF-8'); ?></td>
                    <?php
                        $dispatch_state = (string)($value->dispatch_state ?? '');
                        $status_color = '#f00';
                        if ($dispatch_state === 'sent' || (!empty($value->status) && $dispatch_state === '')) {
                            $status_color = '#009f19';
                        } elseif ($dispatch_state !== '' && !in_array($dispatch_state, ['failed'], true)) {
                            $status_color = '#0d6efd';
                        }
                    ?>
                    <td colspan="3" style="color: <?php echo $status_color; ?>; text-align: center;">
                        <?php
                            $status_text = $value->message ?? (!empty($value->status) ? $success_status_label : $failed_status_label);
                            echo mb_convert_encoding($status_text, 'HTML-ENTITIES', 'UTF-8');
                        ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</table>
</body>
</html>
