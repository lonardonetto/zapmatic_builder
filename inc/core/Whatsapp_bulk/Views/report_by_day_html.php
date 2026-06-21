<?php
$team_id = get_team("id");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Campanhas</title>
    <link rel="stylesheet" href="<?php _e( get_theme_url() ) ?>Assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php _e( get_theme_url() ) ?>Assets/plugins/fontawesome/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .campaign-section { 
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        .campaign-header {
            background: #428200;
            color: white;
            padding: 20px;
            margin: 0;
        }
        .stats-card {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin: 10px;
        }
        .stats-number {
            font-size: 24px;
            font-weight: bold;
            color: #428200;
        }
        .success-list { background-color: #dff0d8; }
        .failure-list { background-color: #f2dede; }
        .list-header {
            padding: 10px;
            font-weight: bold;
            color: #333;
        }
        .cost-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-radius: 5px;
        }
        @media print {
            .no-print { display: none !important; }
            .campaign-section { 
                break-inside: avoid;
                margin: 20px 0;
                box-shadow: none;
            }
        }
    </style>
    <script>
        // Desativa a inicialização automática do intlTelInput
        window.intlTelInputGlobals = {
            autoInitialize: false
        };
    </script>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4 no-print">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Relatório de Campanhas</h1>
                    <div>
                        <button onclick="window.print()" class="btn btn-primary me-2">
                            <i class="fas fa-print"></i> Imprimir/PDF
                        </button>
                        <a href="<?php _ec(get_module_url("report_by_day?".http_build_query($_GET)."&format=excel")) ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Baixar Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Agrupa os dados por campanha
        $campanhas = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $campanha_id = $row->ids;
                if (!isset($campanhas[$campanha_id])) {
                    $campanhas[$campanha_id] = [
                        'nome' => $row->name,
                        'type' => (int)($row->type ?? 1),
                        'total_sucesso' => 0,
                        'total_falha' => 0,
                        'total_geral' => 0,
                        'envios_sucesso' => [],
                        'envios_falha' => []
                    ];
                }

                $data = whatsapp_bulk_get_report_items($row);
                if (!empty($data)) {
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
        ?>

        <?php foreach ($campanhas as $campanha_id => $campanha): ?>
        <?php
            $is_call_campaign = (int)($campanha['type'] ?? 1) === 7;
            $campaign_type_label = $is_call_campaign ? 'Campanha de ligação' : 'Campanha de mensagem';
            $success_label = $is_call_campaign ? 'Ligações iniciadas' : 'Envios com sucesso';
            $failed_label = $is_call_campaign ? 'Tentativas de ligação com falha' : 'Envios com falha';
        ?>
        <div class="campaign-section">
            <h2 class="campaign-header">
                <i class="fas fa-bullhorn me-2"></i>
                <?php echo htmlspecialchars($campanha['nome']); ?>
                <span class="badge bg-light text-dark ms-2"><?php echo htmlspecialchars($campaign_type_label); ?></span>
            </h2>

            <div class="container-fluid p-4">
                <!-- Estatísticas -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-number text-success"><?php echo $campanha['total_sucesso']; ?></div>
                            <div><?php echo htmlspecialchars($success_label); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-number text-danger"><?php echo $campanha['total_falha']; ?></div>
                            <div><?php echo htmlspecialchars($failed_label); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $campanha['total_geral']; ?></div>
                            <div><?php _e("Total de tentativas"); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Custos -->
                <?php if (!$is_call_campaign): ?>
                <div class="cost-section">
                    <h4><i class="fas fa-dollar-sign me-2"></i>Informações de Custo</h4>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong>Custo por Disparo:</strong> R$ 0,03</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Custo Total:</strong> R$ <?php echo number_format($campanha['total_geral'] * 0.03, 2, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Lista de Envios -->
                <div class="row mt-4">
                    <?php if (!empty($campanha['envios_sucesso'])): ?>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header success-list">
                                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_label); ?></h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Número</th>
                                                <th>Horário</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($campanha['envios_sucesso'] as $envio): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($envio['numero']); ?></td>
                                                <td><?php echo htmlspecialchars($envio['horario']); ?></td>
                                                <td><?php echo htmlspecialchars($envio['status']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($campanha['envios_falha'])): ?>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header failure-list">
                                <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i><?php echo htmlspecialchars($failed_label); ?></h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Número</th>
                                                <th>Horário</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($campanha['envios_falha'] as $envio): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($envio['numero']); ?></td>
                                                <td><?php echo htmlspecialchars($envio['horario']); ?></td>
                                                <td><?php echo htmlspecialchars($envio['status']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script src="<?php _e( get_theme_url() ) ?>Assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php _e( get_theme_url() ) ?>Assets/plugins/fontawesome/js/all.min.js"></script>
</body>
</html>
