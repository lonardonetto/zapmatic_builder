<?php
$health = $snapshot['health'] ?? ['badge' => 'secondary', 'label' => 'Sem diagnóstico', 'message' => ''];
$quality = $snapshot['quality'] ?? ['label' => 'Sem dado', 'badge' => 'secondary', 'raw' => ''];
$capacity = $snapshot['capacity'] ?? ['throughput_label' => 'Sem dado oficial', 'safe_parallel_cap' => 0, 'official_daily_remaining_label' => 'Não disponível oficialmente'];
$metrics24h = $snapshot['metrics_24h'] ?? ['total' => 0, 'sent' => 0, 'delivered' => 0, 'read' => 0, 'failed' => 0, 'delivery_rate' => 0.0, 'read_rate' => 0.0, 'failure_rate' => 0.0];
$metrics7d = $snapshot['metrics_7d'] ?? ['total' => 0, 'sent' => 0, 'delivered' => 0, 'read' => 0, 'failed' => 0, 'delivery_rate' => 0.0, 'read_rate' => 0.0, 'failure_rate' => 0.0];
$lastError = $snapshot['last_error'] ?? null;
$topErrors = $snapshot['top_errors_7d'] ?? [];
$recentCampaigns = $snapshot['recent_campaigns'] ?? [];
$wabaOverview = $snapshot['waba_overview'] ?? ['waba_id' => '', 'accounts_total' => 0, 'numbers_active' => 0, 'metrics_24h' => ['total' => 0, 'delivery_rate' => 0.0, 'failed' => 0]];
$graph = $snapshot['graph'] ?? ['ok' => false, 'http_code' => 0, 'error' => ''];
$accountData = $snapshot['account'] ?? [];
$cachedAt = (int) ($snapshot['cached_at'] ?? 0);
$formatTime = static function ($timestamp) {
    return $timestamp > 0 ? date('d/m/Y H:i:s', $timestamp) : 'Sem registro';
};
$formatPercent = static function ($value) {
    return number_format((float) $value, 1, ',', '.') . '%';
};
$throughputMap = [
    'STANDARD' => 'Padrão',
    'HIGH' => 'Alta',
];
$throughputLabel = trim((string) ($capacity['throughput_label'] ?? 'Sem dado oficial'));
if (isset($throughputMap[strtoupper($throughputLabel)])) {
    $throughputLabel = $throughputMap[strtoupper($throughputLabel)];
}
?>

<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h3 class="mb-1"><?php _e('Painel da Saúde Cloud') ?></h3>
            <div class="text-muted fs-13"><?php _ec($accountData['name'] ?? ($account->name ?? 'Conta Cloud')) ?> • <?php _ec($accountData['display_phone_number'] ?? ($account->pid ?? '')) ?></div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php _ec(base_url('whatsapp_profiles/oauth')) ?>" class="btn btn-light-secondary">
                <i class="fas fa-arrow-left me-1"></i><?php _e('Voltar para conexões') ?>
            </a>
            <a href="<?php _ec(base_url('whatsapp_profiles/cloud_health/' . ($account->ids ?? '') . '?refresh=1')) ?>" class="btn btn-primary">
                <i class="fas fa-sync-alt me-1"></i><?php _e('Atualizar agora') ?>
            </a>
        </div>
    </div>

    <div class="alert alert-light-<?php _ec($health['badge'] ?? 'secondary') ?> border border-<?php _ec($health['badge'] ?? 'secondary') ?> border-dashed d-flex align-items-start mb-4">
        <div class="me-3 fs-24 text-<?php _ec($health['badge'] ?? 'secondary') ?>">
            <i class="fas fa-heartbeat"></i>
        </div>
        <div>
            <div class="fw-bold mb-1"><?php _ec($health['label'] ?? 'Sem diagnóstico') ?></div>
            <div class="text-muted mb-2"><?php _ec($health['message'] ?? 'Sem mensagem de saúde disponível.') ?></div>
            <div class="fs-12 text-muted"><?php _e('Última leitura do snapshot:') ?> <?php _ec($formatTime($cachedAt)) ?></div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fs-12 text-uppercase mb-2"><?php _e('Qualidade oficial') ?></div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="fw-bold fs-2x"><?php _ec($quality['label'] ?? 'Sem dado') ?></div>
                        <span class="badge badge-light-<?php _ec($quality['badge'] ?? 'secondary') ?>"><?php _ec($quality['raw'] ?: 'N/A') ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fs-12 text-uppercase mb-2"><?php _e('Nível oficial da Meta') ?></div>
                    <div class="fw-bold fs-2x"><?php _ec($throughputLabel) ?></div>
                    <div class="text-muted fs-12 mt-2"><?php _e('Consulta em tempo real do endpoint da Meta para este número.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fs-12 text-uppercase mb-2"><?php _e('Lote seguro') ?></div>
                    <div class="fw-bold fs-2x"><?php _ec((string) ($capacity['safe_parallel_cap'] ?? 0)) ?></div>
                    <div class="text-muted fs-12 mt-2"><?php _e('Referência operacional para disparos paralelos deste número.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fs-12 text-uppercase mb-2"><?php _e('Saldo diário oficial') ?></div>
                    <div class="fw-bold fs-3"><?php _ec($capacity['official_daily_remaining_label'] ?? 'Não disponível oficialmente') ?></div>
                    <div class="text-muted fs-12 mt-2"><?php _e('A Meta não expõe um saldo diário restante neste endpoint.') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title"><?php _e('Identidade do número') ?></h5>
                </div>
                <div class="card-body pt-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted fs-12"><?php _e('Nome verificado') ?></div>
                            <div class="fw-bold fs-14"><?php _ec($accountData['verified_name'] ?: 'Sem dado oficial') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12"><?php _e('Número exibido') ?></div>
                            <div class="fw-bold fs-14"><?php _ec($accountData['display_phone_number'] ?: ($account->pid ?? 'Sem dado')) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12"><?php _e('Phone Number ID') ?></div>
                            <div class="fw-semibold fs-13 text-break"><?php _ec($accountData['phone_number_id'] ?: 'Sem dado') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12"><?php _e('WABA ID') ?></div>
                            <div class="fw-semibold fs-13 text-break"><?php _ec($accountData['waba_id'] ?: 'Sem dado') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12"><?php _e('Conta interna') ?></div>
                            <div class="fw-semibold fs-13 text-break"><?php _ec($accountData['ids'] ?: ($account->ids ?? 'Sem dado')) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-12"><?php _e('HTTP Meta') ?></div>
                            <div class="fw-semibold fs-13"><?php _ec((string) ($graph['http_code'] ?? 0)) ?><?php if (!empty($graph['error'])): ?> - <?php _ec($graph['error']) ?><?php endif; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title"><?php _e('Uso recente e entregabilidade') ?></h5>
                </div>
                <div class="card-body pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="text-muted fs-12"><?php _e('Total 24h') ?></div>
                            <div class="fw-bold fs-3"><?php _ec((string) ($metrics24h['total'] ?? 0)) ?></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted fs-12"><?php _e('Entregues 24h') ?></div>
                            <div class="fw-bold fs-3"><?php _ec((string) (($metrics24h['delivered'] ?? 0) + ($metrics24h['read'] ?? 0))) ?></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted fs-12"><?php _e('Falhas 24h') ?></div>
                            <div class="fw-bold fs-3"><?php _ec((string) ($metrics24h['failed'] ?? 0)) ?></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted fs-12"><?php _e('Leitura 24h') ?></div>
                            <div class="fw-bold fs-3"><?php _ec($formatPercent($metrics24h['read_rate'] ?? 0)) ?></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle gy-3 mb-0">
                            <thead>
                                <tr class="text-muted fw-semibold fs-12 text-uppercase">
                                    <th><?php _e('Janela') ?></th>
                                    <th><?php _e('Entrega') ?></th>
                                    <th><?php _e('Leitura') ?></th>
                                    <th><?php _e('Falha') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php _e('Últimas 24h') ?></td>
                                    <td><?php _ec($formatPercent($metrics24h['delivery_rate'] ?? 0)) ?></td>
                                    <td><?php _ec($formatPercent($metrics24h['read_rate'] ?? 0)) ?></td>
                                    <td><?php _ec($formatPercent($metrics24h['failure_rate'] ?? 0)) ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('Últimos 7 dias') ?></td>
                                    <td><?php _ec($formatPercent($metrics7d['delivery_rate'] ?? 0)) ?></td>
                                    <td><?php _ec($formatPercent($metrics7d['read_rate'] ?? 0)) ?></td>
                                    <td><?php _ec($formatPercent($metrics7d['failure_rate'] ?? 0)) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title"><?php _e('Último erro e códigos recorrentes') ?></h5>
                </div>
                <div class="card-body pt-4">
                    <?php if (!empty($lastError)): ?>
                        <div class="alert alert-light-danger border border-danger border-dashed mb-4">
                            <div class="fw-bold mb-1"><?php _ec($lastError['display'] ?? '') ?></div>
                            <div class="text-muted fs-12 mb-1"><?php _e('Destino:') ?> <?php _ec($lastError['to_number'] ?? 'Sem dado') ?></div>
                            <div class="text-muted fs-12 mb-1"><?php _e('Campanha:') ?> <?php _ec($lastError['campaign_name'] ?? ('#' . (int) ($lastError['schedule_id'] ?? 0))) ?></div>
                            <div class="text-muted fs-12"><?php _e('Última ocorrência:') ?> <?php _ec($formatTime((int) ($lastError['last_status_at'] ?? 0))) ?></div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light-success border border-success border-dashed mb-4">
                            <?php _e('Nenhum erro com código Meta foi registrado recentemente para esta conta.') ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle gy-3 mb-0">
                            <thead>
                                <tr class="text-muted fw-semibold fs-12 text-uppercase">
                                    <th><?php _e('Código') ?></th>
                                    <th><?php _e('Título') ?></th>
                                    <th><?php _e('Ocorrências 7d') ?></th>
                                    <th><?php _e('Última vez') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topErrors)): ?>
                                    <?php foreach ($topErrors as $item): ?>
                                        <tr>
                                            <td class="fw-bold"><?php _ec((string) ($item['code'] ?? 0)) ?></td>
                                            <td><?php _ec($item['title'] ?? 'Sem título') ?></td>
                                            <td><?php _ec((string) ($item['total'] ?? 0)) ?></td>
                                            <td><?php _ec($formatTime((int) ($item['last_status_at'] ?? 0))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-muted"><?php _e('Sem códigos de erro relevantes nos últimos 7 dias.') ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title"><?php _e('Campanhas recentes deste número') ?></h5>
                </div>
                <div class="card-body pt-4">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle gy-3 mb-0">
                            <thead>
                                <tr class="text-muted fw-semibold fs-12 text-uppercase">
                                    <th><?php _e('Campanha') ?></th>
                                    <th><?php _e('Total') ?></th>
                                    <th><?php _e('Entregues') ?></th>
                                    <th><?php _e('Falhas') ?></th>
                                    <th><?php _e('Última atividade') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentCampaigns)): ?>
                                    <?php foreach ($recentCampaigns as $campaign): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php _ec($campaign['campaign_name'] ?? ('Campanha #' . (int) ($campaign['schedule_id'] ?? 0))) ?></div>
                                                <div class="text-muted fs-12">#<?php _ec((string) ($campaign['schedule_id'] ?? 0)) ?></div>
                                            </td>
                                            <td><?php _ec((string) ($campaign['total'] ?? 0)) ?></td>
                                            <td><?php _ec((string) (($campaign['delivered'] ?? 0) + ($campaign['read'] ?? 0))) ?></td>
                                            <td><?php _ec((string) ($campaign['failed'] ?? 0)) ?></td>
                                            <td><?php _ec($formatTime((int) ($campaign['last_status_at'] ?? 0))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-muted"><?php _e('Ainda não há campanhas recentes registradas para este número.') ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title"><?php _e('Visão da WABA relacionada') ?></h5>
                </div>
                <div class="card-body pt-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted fs-12"><?php _e('WABA ID') ?></div>
                            <div class="fw-semibold fs-13 text-break"><?php _ec($wabaOverview['waba_id'] ?: 'Sem dado') ?></div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-muted fs-12"><?php _e('Contas Cloud') ?></div>
                            <div class="fw-bold fs-2x"><?php _ec((string) ($wabaOverview['accounts_total'] ?? 0)) ?></div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-muted fs-12"><?php _e('Números ativos') ?></div>
                            <div class="fw-bold fs-2x"><?php _ec((string) ($wabaOverview['numbers_active'] ?? 0)) ?></div>
                        </div>
                    </div>

                    <div class="separator my-4"></div>

                    <div class="row g-3">
                        <div class="col-md-4 col-6">
                            <div class="text-muted fs-12"><?php _e('Volume 24h') ?></div>
                            <div class="fw-bold fs-3"><?php _ec((string) (($wabaOverview['metrics_24h']['total'] ?? 0))) ?></div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="text-muted fs-12"><?php _e('Entrega 24h') ?></div>
                            <div class="fw-bold fs-3"><?php _ec($formatPercent($wabaOverview['metrics_24h']['delivery_rate'] ?? 0)) ?></div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="text-muted fs-12"><?php _e('Falhas 24h') ?></div>
                            <div class="fw-bold fs-3"><?php _ec((string) (($wabaOverview['metrics_24h']['failed'] ?? 0))) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title"><?php _e('Leitura operacional') ?></h5>
                </div>
                <div class="card-body pt-4">
                    <div class="alert alert-light-primary border border-primary border-dashed mb-4">
                        <?php _ec($snapshot['operational_hint'] ?? 'Sem recomendação operacional no momento.') ?>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted fs-12 text-uppercase mb-1"><?php _e('Disponibilidade diária oficial') ?></div>
                        <div class="fw-semibold fs-13"><?php _e('A Meta não expõe um saldo diário restante por número neste endpoint. Aqui mostramos uso recente, erros e capacidade segura.') ?></div>
                    </div>

                    <div>
                        <div class="text-muted fs-12 text-uppercase mb-1"><?php _e('Quando agir') ?></div>
                        <div class="fw-semibold fs-13"><?php _e('Se a qualidade cair para amarelo/vermelho ou se surgir erro 131042, ajuste volume e revise cobrança antes de continuar novos disparos.') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
