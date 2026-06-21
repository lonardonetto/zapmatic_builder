<?php
// Garante que não há saída antes dos headers
if (ob_get_length()) ob_clean();

// Define os headers do Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="relatorio_por_dia.xls"');
header('Cache-Control: no-cache');
header('Pragma: no-cache');

// Função auxiliar para criar cabeçalho da aba
function printHeader() {
    echo '<tr>';
    echo '<th colspan="4" style="height: 40px; background-color: #428200; color: #fff; text-transform: uppercase;">';
    echo utf8_decode("Informações da Campanha");
    echo '</th>';
    echo '</tr>';
}

// Função auxiliar para imprimir seção de custos
function printCostSection($total_geral) {
    echo '<tr>';
    echo '<th colspan="4" style="height: 40px; background-color: #428200; color: #fff; text-transform: uppercase; margin-top: 20px;">';
    echo utf8_decode("Informações de Custo");
    echo '</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th class="subheader" colspan="2">' . utf8_decode("Custo por Disparo") . '</th>';
    echo '<td class="total" colspan="2" style="text-align: center;">R$ 0,03</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th class="subheader" colspan="2">' . utf8_decode("Custo Total") . '</th>';
    echo '<td class="total" colspan="2" style="text-align: center;">R$ ' . number_format($total_geral * 0.03, 2, ',', '.') . '</td>';
    echo '</tr>';
}

// Agrupa os dados por campanha
$campanhas = [];
if (!empty($result)) {
    foreach ($result as $row) {
        $data = whatsapp_bulk_get_report_items($row);
        if (!empty($data)) {
            $campanha_id = $row->id;
            if (!isset($campanhas[$campanha_id])) {
                $campanhas[$campanha_id] = [
                    'nome' => $row->name,
                    'total_sucesso' => 0,
                    'total_falha' => 0,
                    'total_geral' => 0,
                    'envios_sucesso' => [],
                    'envios_falha' => []
                ];
            }

            foreach ($data as $value) {
                if (is_object($value) && isset($value->phone_number)) {
                    $campanhas[$campanha_id]['total_geral']++;
                    $dispatch_state = (string)($value->dispatch_state ?? '');
                    $status_ok = $dispatch_state !== '' ? $dispatch_state === 'sent' : !empty($value->status);

                    $info = [
                        'numero' => $value->phone_number,
                        'horario' => whatsapp_bulk_format_report_timestamp($value->sent_at ?? null),
                        'status' => isset($value->message) ? $value->message : ($status_ok ? 'Enviado com sucesso' : 'Falha no envio')
                    ];

                    if ($status_ok) {
                        $campanhas[$campanha_id]['total_sucesso']++;
                        $campanhas[$campanha_id]['envios_sucesso'][] = $info;
                    } else {
                        $campanhas[$campanha_id]['total_falha']++;
                        $campanhas[$campanha_id]['envios_falha'][] = $info;
                    }
                }
            }
        }
    }
}

// Gera uma aba para cada campanha
foreach ($campanhas as $campanha_id => $campanha) {
    // Início da nova aba
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<style>';
    echo 'td, th { border: 1px solid black; padding: 5px; }';
    echo '.header { background-color: #428200; color: white; }';
    echo '.subheader { background-color: #f5f5f5; }';
    echo '.success { background-color: #dff0d8; }';
    echo '.danger { background-color: #f2dede; }';
    echo '.total { font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Nome da aba (limitado a 31 caracteres conforme limitação do Excel)
    echo '<table x:str border=0 cellpadding=0 cellspacing=0 width=100% style="border-collapse: collapse;">';
    echo '<tr><td colspan=4></td></tr>';
    echo '<worksheet name="' . substr(preg_replace('/[\[\]\*\/\\\?]/', '', $campanha['nome']), 0, 31) . '">';
    
    // Cabeçalho da campanha
    echo '<tr>';
    echo '<th colspan="4" class="header" style="height: 50px;">' . utf8_decode($campanha['nome']) . '</th>';
    echo '</tr>';
    
    // Informações gerais
    printHeader();
    
    // Estatísticas
    echo '<tr>';
    echo '<th class="subheader" colspan="2">' . utf8_decode("Total de Envios com Sucesso") . '</th>';
    echo '<td class="total" colspan="2" style="text-align: center;">' . $campanha['total_sucesso'] . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th class="subheader" colspan="2">' . utf8_decode("Total de Envios com Falha") . '</th>';
    echo '<td class="total" colspan="2" style="text-align: center;">' . $campanha['total_falha'] . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th class="subheader" colspan="2">' . utf8_decode("Total Geral de Tentativas") . '</th>';
    echo '<td class="total" colspan="2" style="text-align: center;">' . $campanha['total_geral'] . '</td>';
    echo '</tr>';
    
    // Seção de custos
    printCostSection($campanha['total_geral']);
    
    // Lista de envios com sucesso
    if (!empty($campanha['envios_sucesso'])) {
        echo '<tr>';
        echo '<th colspan="4" class="success" style="height: 40px; text-transform: uppercase; margin-top: 20px; text-align: center;">';
        echo utf8_decode("Lista de Envios com Sucesso");
        echo '</th>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>' . utf8_decode("Número") . '</th>';
        echo '<th>' . utf8_decode("Horário") . '</th>';
        echo '<th colspan="2">' . utf8_decode("Status") . '</th>';
        echo '</tr>';
        
        foreach ($campanha['envios_sucesso'] as $envio) {
            echo '<tr>';
            echo '<td>' . $envio['numero'] . '</td>';
            echo '<td>' . $envio['horario'] . '</td>';
            echo '<td colspan="2">' . utf8_decode($envio['status']) . '</td>';
            echo '</tr>';
        }
    }
    
    // Lista de envios com falha
    if (!empty($campanha['envios_falha'])) {
        echo '<tr>';
        echo '<th colspan="4" class="danger" style="height: 40px; text-transform: uppercase; margin-top: 20px; text-align: center;">';
        echo utf8_decode("Lista de Envios com Falha");
        echo '</th>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>' . utf8_decode("Número") . '</th>';
        echo '<th>' . utf8_decode("Horário") . '</th>';
        echo '<th colspan="2">' . utf8_decode("Status") . '</th>';
        echo '</tr>';
        
        foreach ($campanha['envios_falha'] as $envio) {
            echo '<tr>';
            echo '<td>' . $envio['numero'] . '</td>';
            echo '<td>' . $envio['horario'] . '</td>';
            echo '<td colspan="2">' . utf8_decode($envio['status']) . '</td>';
            echo '</tr>';
        }
    }
    
    echo '</worksheet>';
    echo '</table>';
    echo '</body>';
    echo '</html>';
}
