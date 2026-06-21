<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.bb-analytics-page {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 80vh;
}

/* Hero */
.bb-an-hero {
    background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
    border-radius: 20px;
    padding: 32px 36px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}

.bb-an-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(139,92,246,0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.bb-an-hero-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    z-index: 2;
}

.bb-an-hero-left { display: flex; align-items: center; gap: 16px; }

.bb-an-hero-icon {
    width: 52px; height: 52px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #fff;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.bb-an-hero-text h2 {
    margin: 0; font-size: 22px; font-weight: 800; color: #fff; letter-spacing: -0.4px;
}

.bb-an-hero-text p {
    margin: 3px 0 0; font-size: 14px; color: rgba(255,255,255,0.9); font-weight: 500;
}

.bb-an-hero-actions { display: flex; gap: 10px; }

.bb-an-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 20px;
    border-radius: 11px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.25s;
    white-space: nowrap;
}

.bb-an-btn i { font-size: 13px; }

.bb-an-btn-back {
    background: rgba(255,255,255,0.2);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.25);
    backdrop-filter: blur(5px);
}
.bb-an-btn-back:hover { background: rgba(255,255,255,0.3); color: #fff; text-decoration: none; }

.bb-an-btn-sessions {
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    color: #fff;
    box-shadow: 0 4px 14px rgba(59,130,246,0.3);
}
.bb-an-btn-sessions:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59,130,246,0.4); color: #fff; text-decoration: none; }

/* Stats */
.bb-an-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}

.bb-an-stat {
    background: #fff;
    border: 1px solid #eef0f4;
    border-radius: 16px;
    padding: 22px 24px;
    transition: all 0.25s;
    position: relative;
    overflow: hidden;
}

.bb-an-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.06);
}

.bb-an-stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.bb-an-stat-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.bb-an-stat-icon.indigo { background: #eef2ff; color: #6366f1; }
.bb-an-stat-icon.green { background: #ecfdf5; color: #10b981; }
.bb-an-stat-icon.blue { background: #eff6ff; color: #3b82f6; }
.bb-an-stat-icon.amber { background: #fffbeb; color: #f59e0b; }
.bb-an-stat-icon.purple { background: #faf5ff; color: #8b5cf6; }
.bb-an-stat-icon.pink { background: #fdf2f8; color: #ec4899; }

.bb-an-stat-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.bb-an-stat h3 {
    margin: 0; font-size: 26px; font-weight: 800; color: #111827; letter-spacing: -0.5px;
}

.bb-an-stat span.label {
    font-size: 12px; font-weight: 500; color: #9ca3af; margin-top: 2px; display: block;
}

/* Charts Grid */
.bb-an-charts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    margin-bottom: 24px;
}

.bb-an-chart-card {
    background: #fff;
    border: 1px solid #eef0f4;
    border-radius: 18px;
    overflow: hidden;
}

.bb-an-chart-card.full-width {
    grid-column: 1 / -1;
}

.bb-an-chart-header {
    padding: 20px 24px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.bb-an-chart-title {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bb-an-chart-title i { font-size: 15px; }

.bb-an-chart-body {
    padding: 20px 24px;
}

/* Canvas chart container */
.bb-an-canvas-wrap {
    position: relative;
    width: 100%;
    height: 260px;
}

.bb-an-canvas-wrap canvas {
    width: 100% !important;
    height: 100% !important;
}

/* Completion Ring */
.bb-an-ring-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 30px;
    padding: 20px 0;
}

.bb-an-ring {
    position: relative;
    width: 140px;
    height: 140px;
}

.bb-an-ring svg {
    width: 140px;
    height: 140px;
    transform: rotate(-90deg);
}

.bb-an-ring-bg {
    fill: none;
    stroke: #f3f4f6;
    stroke-width: 10;
}

.bb-an-ring-progress {
    fill: none;
    stroke-width: 10;
    stroke-linecap: round;
    transition: stroke-dashoffset 1.5s ease-out;
}

.bb-an-ring-text {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.bb-an-ring-text .pct {
    font-size: 28px;
    font-weight: 800;
    color: #111827;
    display: block;
    line-height: 1;
}

.bb-an-ring-text .pct-label {
    font-size: 11px;
    color: #9ca3af;
    font-weight: 500;
}

.bb-an-ring-legend {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.bb-an-ring-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #374151;
    font-weight: 500;
}

.bb-an-ring-legend-dot {
    width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0;
}

.bb-an-ring-legend-val {
    font-weight: 700;
    color: #111827;
    margin-left: auto;
}

/* Block Type Bars */
.bb-an-block-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.bb-an-block-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.bb-an-block-icon {
    width: 30px; height: 30px; min-width: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #fff;
}

.bb-an-block-name {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    min-width: 90px;
    text-transform: capitalize;
}

.bb-an-block-bar-wrap {
    flex: 1;
    height: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    overflow: hidden;
}

.bb-an-block-bar {
    height: 100%;
    border-radius: 4px;
    transition: width 1s ease-out;
}

.bb-an-block-count {
    font-size: 12px;
    font-weight: 700;
    color: #6b7280;
    min-width: 24px;
    text-align: right;
}

/* Hourly Heatmap */
.bb-an-heatmap {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 4px;
}

.bb-an-heatmap-cell {
    aspect-ratio: 1;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    font-weight: 600;
    color: #9ca3af;
    transition: all 0.2s;
    position: relative;
}

.bb-an-heatmap-cell:hover {
    transform: scale(1.15);
    z-index: 2;
}

.bb-an-heatmap-cell .hm-tooltip {
    position: absolute;
    bottom: calc(100% + 4px);
    left: 50%;
    transform: translateX(-50%) scale(0.9);
    padding: 4px 8px;
    background: #1e293b;
    color: #fff;
    font-size: 10px;
    border-radius: 5px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s;
    pointer-events: none;
}

.bb-an-heatmap-cell:hover .hm-tooltip {
    opacity: 1; visibility: visible; transform: translateX(-50%) scale(1);
}

.bb-an-heatmap-labels {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 4px;
    margin-top: 4px;
}

.bb-an-heatmap-labels span {
    text-align: center;
    font-size: 9px;
    color: #c4c9d4;
    font-weight: 500;
}

/* Bot Info Card */
.bb-an-info-card {
    background: #fff;
    border: 1px solid #eef0f4;
    border-radius: 18px;
    overflow: hidden;
    margin-bottom: 24px;
}

.bb-an-info-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
}

.bb-an-info-title {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bb-an-info-title i { color: #8b5cf6; }

.bb-an-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0;
}

.bb-an-info-item {
    padding: 18px 24px;
    border-right: 1px solid #f3f4f6;
    border-bottom: 1px solid #f3f4f6;
}

.bb-an-info-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #9ca3af;
    margin-bottom: 4px;
}

.bb-an-info-value {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    word-break: break-all;
}

@media (max-width: 768px) {
    .bb-an-hero { padding: 20px; }
    .bb-an-hero-inner { flex-direction: column; align-items: flex-start; }
    .bb-an-stats { grid-template-columns: repeat(2, 1fr); }
    .bb-an-charts { grid-template-columns: 1fr; }
    .bb-an-ring-wrap { flex-direction: column; }
}

@media (max-width: 480px) {
    .bb-an-stats { grid-template-columns: 1fr; }
}
</style>

<?php
// Block type color mapping
$type_colors = [
    'start' => '#10b981', 'end' => '#ef4444', 'text' => '#3b82f6',
    'image' => '#6366f1', 'video' => '#ec4899', 'audio' => '#06b6d4',
    'buttons' => '#f59e0b', 'list' => '#10b981', 'input' => '#f43f5e',
    'condition' => '#64748b', 'delay' => '#f97316', 'ai_reply' => '#8b5cf6',
    'webhook' => '#0d9488', 'set_variable' => '#ca8a04', 'jump' => '#a855f7'
];

$type_icons = [
    'start' => 'fad fa-play-circle', 'end' => 'fad fa-stop-circle', 'text' => 'fad fa-comment',
    'image' => 'fad fa-image', 'video' => 'fad fa-video', 'audio' => 'fad fa-headphones',
    'buttons' => 'fad fa-hand-pointer', 'list' => 'fad fa-list', 'input' => 'fad fa-keyboard',
    'condition' => 'fad fa-code-branch', 'delay' => 'fad fa-clock', 'ai_reply' => 'fad fa-sparkles',
    'webhook' => 'fad fa-plug', 'set_variable' => 'fad fa-equals', 'jump' => 'fad fa-share'
];

$max_block = !empty($block_types) ? max($block_types) : 1;
$hourly_arr = json_decode($hourly_dist, true);
$max_hourly = !empty($hourly_arr) ? max(max($hourly_arr), 1) : 1;
?>

<div class="bb-analytics-page">

    <!-- HERO -->
    <div class="bb-an-hero">
        <div class="bb-an-hero-inner">
            <div class="bb-an-hero-left">
                <div class="bb-an-hero-icon"><i class="fad fa-chart-pie"></i></div>
                <div class="bb-an-hero-text">
                    <h2><?php echo esc($bot->name) ?> — Métricas</h2>
                    <p>Indicadores, tendências e desempenho do seu bot</p>
                </div>
            </div>
            <div class="bb-an-hero-actions">
                <a href="<?php echo base_url('bot-builder') ?>" class="bb-an-btn bb-an-btn-back">
                    <i class="fad fa-arrow-left"></i> Voltar
                </a>
                <a href="<?php echo base_url('bot-builder/'.$bot->id.'/sessions') ?>" class="bb-an-btn bb-an-btn-sessions">
                    <i class="fad fa-users"></i> Ver atendimentos
                </a>
            </div>
        </div>
    </div>

    <!-- STATS -->
    <div class="bb-an-stats">
        <div class="bb-an-stat">
            <div class="bb-an-stat-top">
                <div class="bb-an-stat-icon indigo"><i class="fad fa-comments-alt"></i></div>
            </div>
            <h3><?php echo $total_sessions ?></h3>
            <span class="label">Total de atendimentos</span>
        </div>
        <div class="bb-an-stat">
            <div class="bb-an-stat-top">
                <div class="bb-an-stat-icon green"><i class="fad fa-check-double"></i></div>
            </div>
            <h3><?php echo $completed_sessions ?></h3>
            <span class="label">Finalizados</span>
        </div>
        <div class="bb-an-stat">
            <div class="bb-an-stat-top">
                <div class="bb-an-stat-icon blue"><i class="fad fa-user-friends"></i></div>
            </div>
            <h3><?php echo $unique_users ?></h3>
            <span class="label">Usuários únicos</span>
        </div>
        <div class="bb-an-stat">
            <div class="bb-an-stat-top">
                <div class="bb-an-stat-icon amber"><i class="fad fa-percentage"></i></div>
            </div>
            <h3><?php echo $completion_rate ?>%</h3>
            <span class="label">Taxa de conclusão</span>
        </div>
        <div class="bb-an-stat">
            <div class="bb-an-stat-top">
                <div class="bb-an-stat-icon purple"><i class="fad fa-puzzle-piece"></i></div>
            </div>
            <h3><?php echo $total_blocks ?></h3>
            <span class="label">Blocos do fluxo</span>
        </div>
        <div class="bb-an-stat">
            <div class="bb-an-stat-top">
                <div class="bb-an-stat-icon pink"><i class="fad fa-project-diagram"></i></div>
            </div>
            <h3><?php echo $total_edges ?></h3>
            <span class="label">Conexões</span>
        </div>
    </div>

    <!-- CHARTS ROW -->
    <div class="bb-an-charts">

        <!-- Sessions Trend Chart -->
        <div class="bb-an-chart-card full-width">
            <div class="bb-an-chart-header">
                <div class="bb-an-chart-title">
                    <i class="fad fa-chart-line" style="color:#6366f1;"></i>
                    Tendência de atendimentos (últimos 14 dias)
                </div>
            </div>
            <div class="bb-an-chart-body">
                <div class="bb-an-canvas-wrap">
                    <canvas id="bbTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Completion Ring -->
        <div class="bb-an-chart-card">
            <div class="bb-an-chart-header">
                <div class="bb-an-chart-title">
                    <i class="fad fa-bullseye-arrow" style="color:#10b981;"></i>
                    Taxa de conclusão
                </div>
            </div>
            <div class="bb-an-chart-body">
                <div class="bb-an-ring-wrap">
                    <div class="bb-an-ring">
                        <svg viewBox="0 0 140 140">
                            <circle class="bb-an-ring-bg" cx="70" cy="70" r="60" />
                            <circle class="bb-an-ring-progress" cx="70" cy="70" r="60"
                                stroke="<?php echo $completion_rate >= 50 ? '#10b981' : '#f59e0b' ?>"
                                stroke-dasharray="<?php echo 2 * M_PI * 60 ?>"
                                stroke-dashoffset="<?php echo 2 * M_PI * 60 * (1 - $completion_rate / 100) ?>"
                            />
                        </svg>
                        <div class="bb-an-ring-text">
                            <span class="pct"><?php echo $completion_rate ?>%</span>
                            <span class="pct-label">Concluído</span>
                        </div>
                    </div>
                    <div class="bb-an-ring-legend">
                        <div class="bb-an-ring-legend-item">
                            <div class="bb-an-ring-legend-dot" style="background:#10b981;"></div>
                            Finalizados
                            <span class="bb-an-ring-legend-val"><?php echo $completed_sessions ?></span>
                        </div>
                        <div class="bb-an-ring-legend-item">
                            <div class="bb-an-ring-legend-dot" style="background:#3b82f6;"></div>
                            Ativos
                            <span class="bb-an-ring-legend-val"><?php echo $active_sessions ?></span>
                        </div>
                        <div class="bb-an-ring-legend-item">
                            <div class="bb-an-ring-legend-dot" style="background:#e5e7eb;"></div>
                            Total
                            <span class="bb-an-ring-legend-val"><?php echo $total_sessions ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Block Type Distribution -->
        <div class="bb-an-chart-card">
            <div class="bb-an-chart-header">
                <div class="bb-an-chart-title">
                    <i class="fad fa-puzzle-piece" style="color:#8b5cf6;"></i>
                    Tipos de bloco
                </div>
            </div>
            <div class="bb-an-chart-body">
                <?php if(!empty($block_types)): ?>
                <div class="bb-an-block-list">
                    <?php foreach($block_types as $type => $count): ?>
                    <?php $color = $type_colors[$type] ?? '#6b7280'; $icon = $type_icons[$type] ?? 'fad fa-cube'; ?>
                    <div class="bb-an-block-item">
                        <div class="bb-an-block-icon" style="background:<?php echo $color ?>;">
                            <i class="<?php echo $icon ?>"></i>
                        </div>
                        <span class="bb-an-block-name"><?php echo str_replace('_', ' ', esc($type)) ?></span>
                        <div class="bb-an-block-bar-wrap">
                            <div class="bb-an-block-bar" style="width:<?php echo round(($count / $max_block) * 100) ?>%; background:<?php echo $color ?>;"></div>
                        </div>
                        <span class="bb-an-block-count"><?php echo $count ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color:#9ca3af; text-align:center; font-size:13px;">Este bot ainda não possui blocos</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Hourly Activity Heatmap -->
        <div class="bb-an-chart-card full-width">
            <div class="bb-an-chart-header">
                <div class="bb-an-chart-title">
                    <i class="fad fa-fire" style="color:#f97316;"></i>
                    Mapa de atividade por hora
                </div>
            </div>
            <div class="bb-an-chart-body">
                <div class="bb-an-heatmap">
                    <?php for($h = 0; $h < 24; $h++): ?>
                    <?php
                        $val = $hourly_arr[$h] ?? 0;
                        $intensity = $max_hourly > 0 ? ($val / $max_hourly) : 0;
                        if($val == 0) $bg = '#f8f9fa';
                        elseif($intensity < 0.25) $bg = '#dbeafe';
                        elseif($intensity < 0.5) $bg = '#93c5fd';
                        elseif($intensity < 0.75) $bg = '#3b82f6';
                        else $bg = '#1d4ed8';
                        $textColor = $intensity >= 0.5 ? '#fff' : '#6b7280';
                        $hour_label = str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':00';
                    ?>
                    <div class="bb-an-heatmap-cell" style="background:<?php echo $bg ?>; color:<?php echo $textColor ?>;">
                        <?php echo $val ?>
                        <span class="hm-tooltip"><?php echo $hour_label ?>: <?php echo $val ?> atendimentos</span>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="bb-an-heatmap-labels">
                    <?php for($h = 0; $h < 24; $h += 2): ?>
                    <?php $hl = str_pad((string)$h, 2, '0', STR_PAD_LEFT) . 'h'; ?>
                    <span><?php echo $hl ?></span><span></span>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- BOT INFO -->
    <div class="bb-an-info-card">
        <div class="bb-an-info-header">
            <div class="bb-an-info-title">
                <i class="fad fa-info-circle"></i>
                Detalhes do bot
            </div>
        </div>
        <div class="bb-an-info-grid">
            <div class="bb-an-info-item">
                <div class="bb-an-info-label">Nome do bot</div>
                <div class="bb-an-info-value"><?php echo esc($bot->name) ?></div>
            </div>
            <div class="bb-an-info-item">
                <div class="bb-an-info-label">Status</div>
                <div class="bb-an-info-value" style="color:<?php echo $bot->status ? '#059669' : '#d97706' ?>;">
                    <?php echo $bot->status ? '● Publicado' : '● Rascunho' ?>
                </div>
            </div>
            <div class="bb-an-info-item">
                <div class="bb-an-info-label">Palavras-chave</div>
                <div class="bb-an-info-value"><?php echo !empty($bot->trigger_keywords) ? esc($bot->trigger_keywords) : '—' ?></div>
            </div>
            <div class="bb-an-info-item">
                <div class="bb-an-info-label">Criado em</div>
                <div class="bb-an-info-value"><?php echo !empty($bot->created_at) ? date('d/m/Y H:i', strtotime($bot->created_at)) : '—' ?></div>
            </div>
            <div class="bb-an-info-item">
                <div class="bb-an-info-label">Descrição</div>
                <div class="bb-an-info-value"><?php echo !empty($bot->description) ? esc($bot->description) : '—' ?></div>
            </div>
            <div class="bb-an-info-item">
                <div class="bb-an-info-label">Bot ID</div>
                <div class="bb-an-info-value" style="font-family:'SF Mono','Consolas',monospace; font-size:12px;">#<?php echo $bot->id ?></div>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sessions Trend Chart
    var trendCtx = document.getElementById('bbTrendChart');
    if(trendCtx) {
        var labels = <?php echo $trend_labels ?>;
        var data = <?php echo $trend_data ?>;

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Atendimentos',
                    data: data,
                    borderColor: '#6366f1',
                    backgroundColor: function(ctx) {
                        var gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 250);
                        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.15)');
                        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.01)');
                        return gradient;
                    },
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Inter', size: 12, weight: '600' },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Inter', size: 11, weight: '500' },
                            color: '#9ca3af'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f3f4f6' },
                        ticks: {
                            font: { family: 'Inter', size: 11, weight: '500' },
                            color: '#9ca3af',
                            stepSize: 1
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
});
</script>
