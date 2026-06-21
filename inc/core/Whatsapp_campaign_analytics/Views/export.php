<?php
    $total = count($results);
    $sent = 0;
    $delivered = 0;
    $read = 0;
    $failed = 0;
    $min_date = null;
    $max_date = null;

    foreach ($results as $r) {
        if ($min_date === null || $r->created < $min_date) {
            $min_date = $r->created;
        }
        if ($max_date === null || $r->last_status_at > $max_date) {
            $max_date = $r->last_status_at;
        }
        if (in_array($r->status, ['sent', 'delivered', 'read'])) {
            $sent++;
        }
        if (in_array($r->status, ['delivered', 'read'])) {
            $delivered++;
        }
        if ($r->status == 'read') {
            $read++;
        }
        if ($r->status == 'failed') {
            $failed++;
        }
    }

    $success_rate = $total > 0 ? round(($sent / $total) * 100, 2) . '%' : '0%';

    echo '<table class="table table-bordered">';
    echo '<thead>';
    
    // Resume Header
    echo '<tr><th colspan="6" style="text-align:center; font-weight:bold; font-size:16px;">ZAPTECH-ZAPMATIC</th></tr>';
    echo '<tr>';
    echo '<th>Lista/Campanha</th><td colspan="5"><strong>'.$campaign_name.'</strong></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th>Iniciada</th><td>'.($min_date ? date("d/m/Y H:i:s", $min_date) : '-').'</td>';
    echo '<td></td><td></td>';
    echo '<th>Finalizada</th><td>'.($max_date ? date("d/m/Y H:i:s", $max_date) : '-').'</td>';
    echo '</tr>';

    echo '<tr><td colspan="6"></td></tr>';

    echo '<tr><th>Total de Mensagens</th><td>'.$total.'</td><td colspan="4"></td></tr>';
    echo '<tr><th>Mensagens Enviadas</th><td>'.$sent.'</td><td colspan="4"></td></tr>';
    echo '<tr><th>Mensagens Entregues</th><td>'.$delivered.'</td><td colspan="4"></td></tr>';
    echo '<tr><th>Mensagens Lidas</th><td>'.$read.'</td><td colspan="4"></td></tr>';
    echo '<tr><th>Mensagens que Falharam</th><td>'.$failed.'</td><td colspan="4"></td></tr>';
    echo '<tr><th>Taxa de Sucesso (Enviadas)</th><td>'.$success_rate.'</td><td colspan="4"></td></tr>';

    echo '<tr><td colspan="6"></td></tr>';

    // Details Header
    echo '<tr>';
    echo "<th>Data da Criação (UTC)</th>";
    echo "<th>Telefone Destino</th>";
    echo "<th>Status Final</th>";
    echo "<th>Data Status (UTC)</th>";
    echo "<th>Message ID (Cloud API)</th>";
    echo "<th>Descrição de Erro</th>";
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($results as $r) {
        $created_date = date("d/m/Y H:i:s", $r->created);
        $last_status_date = date("d/m/Y H:i:s", $r->last_status_at);
        
        $error_desc = "";
        if ($r->meta_error_code) {
            $error_desc = "[" . $r->meta_error_code . "] " . $r->meta_error_title;
            if ($r->meta_error_details) {
                $error_desc .= " - " . $r->meta_error_details;
            }
        }
        
        echo "<tr>";
            echo "<td>".$created_date."</td>";
            echo "<td>'".$r->to_number."</td>";
            echo "<td>".ucfirst($r->status)."</td>";
            echo "<td>".$last_status_date."</td>";
            echo "<td>".$r->wa_message_id."</td>";
            echo "<td>".$error_desc."</td>";
        echo "</tr>";
    }

    echo '</tbody>';
    echo '</table>';
?>
