<?php
$health = $snapshot['health'] ?? ['badge' => 'secondary', 'label' => 'Sem diagnóstico', 'message' => ''];
$quality = $snapshot['quality'] ?? ['label' => 'Sem dado', 'badge' => 'secondary'];
$capacity = $snapshot['capacity'] ?? ['throughput_label' => 'Sem dado oficial', 'safe_parallel_cap' => 0];
$metrics24h = $snapshot['metrics_24h'] ?? ['delivery_rate' => 0.0];
$lastError = $snapshot['last_error'] ?? null;
$cachedAt = (int) ($snapshot['cached_at'] ?? 0);

$throughputMap = [
    'STANDARD' => 'Padrão',
    'HIGH' => 'Alta',
];

$throughputLabel = trim((string) ($capacity['throughput_label'] ?? 'Sem dado oficial'));
if (isset($throughputMap[strtoupper($throughputLabel)])) {
    $throughputLabel = $throughputMap[strtoupper($throughputLabel)];
}

$tooltipParts = [];
$message = trim((string) ($health['message'] ?? ''));
if ($message !== '') {
    $tooltipParts[] = $message;
}
if ($cachedAt > 0) {
    $tooltipParts[] = 'Atualizado em ' . date('d/m/Y H:i', $cachedAt);
}
if (!empty($lastError['display'])) {
    $tooltipParts[] = $lastError['display'];
}
$tooltip = implode(' | ', $tooltipParts);
?>

<div class="cloud-health-mini"<?php if ($tooltip !== ''): ?> title="<?php _ec($tooltip) ?>"<?php endif; ?>>
    <span class="cloud-health-mini-pill cloud-health-mini-pill-<?php _ec($health['badge'] ?? 'secondary') ?>">
        <?php _ec($health['label'] ?? 'Sem diagnóstico') ?>
    </span>
    <span class="cloud-health-mini-pill">
        <?php _e('Qualidade') ?> <?php _ec($quality['label'] ?? 'Sem dado') ?>
    </span>
    <span class="cloud-health-mini-pill">
        <?php _e('Meta') ?> <?php _ec($throughputLabel) ?>
    </span>
    <span class="cloud-health-mini-pill">
        <?php _e('Lote') ?> <?php _ec((string) ($capacity['safe_parallel_cap'] ?? 0)) ?>
    </span>
    <span class="cloud-health-mini-pill">
        <?php _e('Entrega 24h') ?> <?php _ec(number_format((float) ($metrics24h['delivery_rate'] ?? 0), 1, ',', '.')) ?>%
    </span>
    <?php if (!empty($lastError['code'])): ?>
    <span class="cloud-health-mini-pill cloud-health-mini-pill-danger">
        <?php _e('Erro') ?> <?php _ec((string) $lastError['code']) ?>
    </span>
    <?php endif; ?>
</div>
