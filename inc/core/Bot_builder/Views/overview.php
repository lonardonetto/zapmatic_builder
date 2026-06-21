<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.bb-overview-page {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 80vh;
}

.bb-ov-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    border-radius: 20px;
    padding: 32px 36px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}

.bb-ov-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(16,185,129,0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.bb-ov-hero-inner {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 16px; position: relative; z-index: 2;
}

.bb-ov-hero-left { display: flex; align-items: center; gap: 16px; }

.bb-ov-hero-icon {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, #10b981, #34d399);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff;
    box-shadow: 0 6px 20px rgba(16,185,129,0.3);
}

.bb-ov-hero-text h2 { margin: 0; font-size: 22px; font-weight: 800; color: #fff; }
.bb-ov-hero-text p { margin: 3px 0 0; font-size: 13px; color: rgba(255,255,255,0.5); }

.bb-ov-actions { display: flex; gap: 10px; }

.bb-ov-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 20px; border-radius: 11px;
    font-size: 13px; font-weight: 600;
    font-family: 'Inter', sans-serif;
    text-decoration: none; border: none; cursor: pointer;
    transition: all 0.25s; white-space: nowrap;
}

.bb-ov-btn-back {
    background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.8);
    border: 1px solid rgba(255,255,255,0.1);
}
.bb-ov-btn-back:hover { background: rgba(255,255,255,0.14); color: #fff; text-decoration: none; }

.bb-ov-btn-edit {
    background: linear-gradient(135deg, #6366f1, #818cf8); color: #fff;
    box-shadow: 0 4px 14px rgba(99,102,241,0.3);
}
.bb-ov-btn-edit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); color: #fff; text-decoration: none; }

/* Stats */
.bb-ov-stats {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px; margin-bottom: 24px;
}

.bb-ov-stat {
    background: #fff; border: 1px solid #eef0f4; border-radius: 14px;
    padding: 20px 22px; display: flex; align-items: center; gap: 14px;
    transition: all 0.25s;
}

.bb-ov-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.05); }

.bb-ov-stat-icon {
    width: 44px; height: 44px; min-width: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}

.bb-ov-stat-icon.green { background: #ecfdf5; color: #10b981; }
.bb-ov-stat-icon.blue { background: #eff6ff; color: #3b82f6; }
.bb-ov-stat-icon.purple { background: #faf5ff; color: #8b5cf6; }
.bb-ov-stat-icon.amber { background: #fffbeb; color: #f59e0b; }

.bb-ov-stat-info h4 { margin: 0; font-size: 20px; font-weight: 800; color: #111827; line-height: 1; }
.bb-ov-stat-info span { font-size: 11.5px; font-weight: 500; color: #9ca3af; margin-top: 3px; display: block; }

/* Info Card */
.bb-ov-info-card {
    background: #fff; border: 1px solid #eef0f4; border-radius: 18px;
    padding: 28px; margin-bottom: 24px;
}

.bb-ov-info-title {
    font-size: 16px; font-weight: 700; color: #111827; margin: 0 0 20px;
    display: flex; align-items: center; gap: 8px;
}

.bb-ov-info-title i { color: #10b981; }

.bb-ov-info-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 18px;
}

.bb-ov-info-item { display: flex; flex-direction: column; gap: 4px; }

.bb-ov-info-label {
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af;
}

.bb-ov-info-value {
    font-size: 14px; font-weight: 600; color: #111827;
}

/* Quick Actions */
.bb-ov-quick {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
}

.bb-ov-quick-card {
    background: #fff; border: 1px solid #eef0f4; border-radius: 14px;
    padding: 22px 24px;
    display: flex; align-items: center; gap: 14px;
    text-decoration: none; color: inherit;
    transition: all 0.25s;
}

.bb-ov-quick-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    border-color: transparent;
    text-decoration: none;
}

.bb-ov-quick-icon {
    width: 44px; height: 44px; min-width: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff;
}

.bb-ov-quick-text h4 { margin: 0; font-size: 14px; font-weight: 600; color: #111827; }
.bb-ov-quick-text p { margin: 3px 0 0; font-size: 12px; color: #9ca3af; }

@media (max-width: 768px) {
    .bb-ov-hero { padding: 20px; }
    .bb-ov-hero-inner { flex-direction: column; align-items: flex-start; }
    .bb-ov-stats { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="bb-overview-page">

    <div class="bb-ov-hero">
        <div class="bb-ov-hero-inner">
            <div class="bb-ov-hero-left">
                <div class="bb-ov-hero-icon"><i class="fad fa-robot"></i></div>
                <div class="bb-ov-hero-text">
                    <h2><?php echo esc($bot->name) ?></h2>
                    <p><?php echo !empty($bot->description) ? esc($bot->description) : 'Visão geral do bot e ações rápidas' ?></p>
                </div>
            </div>
            <div class="bb-ov-actions">
                <a href="<?php echo base_url('bot-builder') ?>" class="bb-ov-btn bb-ov-btn-back">
                    <i class="fad fa-arrow-left"></i> Voltar
                </a>
                <a href="<?php echo base_url('bot-builder/'.$bot->id.'/editor') ?>" class="bb-ov-btn bb-ov-btn-edit">
                    <i class="fad fa-edit"></i> Abrir editor
                </a>
            </div>
        </div>
    </div>

    <div class="bb-ov-stats">
        <div class="bb-ov-stat">
            <div class="bb-ov-stat-icon green"><i class="fad fa-puzzle-piece"></i></div>
            <div class="bb-ov-stat-info"><h4><?php echo $total_blocks ?></h4><span>Blocos do fluxo</span></div>
        </div>
        <div class="bb-ov-stat">
            <div class="bb-ov-stat-icon blue"><i class="fad fa-project-diagram"></i></div>
            <div class="bb-ov-stat-info"><h4><?php echo $total_edges ?></h4><span>Conexões</span></div>
        </div>
        <div class="bb-ov-stat">
            <div class="bb-ov-stat-icon purple"><i class="fad fa-comments"></i></div>
            <div class="bb-ov-stat-info"><h4><?php echo $total_sessions ?></h4><span>Total de atendimentos</span></div>
        </div>
        <div class="bb-ov-stat">
            <div class="bb-ov-stat-icon amber"><i class="fad fa-check-circle"></i></div>
            <div class="bb-ov-stat-info"><h4><?php echo $completed_sessions ?></h4><span>Finalizados</span></div>
        </div>
    </div>

    <div class="bb-ov-info-card">
        <h3 class="bb-ov-info-title"><i class="fad fa-info-circle"></i> Detalhes do bot</h3>
        <div class="bb-ov-info-grid">
            <div class="bb-ov-info-item">
                <span class="bb-ov-info-label">Nome do bot</span>
                <span class="bb-ov-info-value"><?php echo esc($bot->name) ?></span>
            </div>
            <div class="bb-ov-info-item">
                <span class="bb-ov-info-label">Status</span>
                <span class="bb-ov-info-value" style="color:<?php echo $bot->status ? '#059669' : '#d97706' ?>;">
                    <?php echo $bot->status ? '● Publicado' : '● Rascunho' ?>
                </span>
            </div>
            <div class="bb-ov-info-item">
                <span class="bb-ov-info-label">Trigger Keywords</span>
                <span class="bb-ov-info-value"><?php echo !empty($bot->trigger_keywords) ? esc($bot->trigger_keywords) : '—' ?></span>
            </div>
            <div class="bb-ov-info-item">
                <span class="bb-ov-info-label">Criado em</span>
                <span class="bb-ov-info-value"><?php echo !empty($bot->created_at) ? date('M j, Y g:i A', strtotime($bot->created_at)) : '—' ?></span>
            </div>
        </div>
    </div>

    <div class="bb-ov-quick">
        <a href="<?php echo base_url('bot-builder/'.$bot->id.'/editor') ?>" class="bb-ov-quick-card">
            <div class="bb-ov-quick-icon" style="background:linear-gradient(135deg,#6366f1,#818cf8);box-shadow:0 4px 12px rgba(99,102,241,0.25);">
                <i class="fad fa-edit"></i>
            </div>
            <div class="bb-ov-quick-text">
                <h4>Editar fluxo</h4>
                <p>Open the visual bot editor</p>
            </div>
        </a>
        <a href="<?php echo base_url('bot-builder/'.$bot->id.'/sessions') ?>" class="bb-ov-quick-card">
            <div class="bb-ov-quick-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);box-shadow:0 4px 12px rgba(59,130,246,0.25);">
                <i class="fad fa-users"></i>
            </div>
            <div class="bb-ov-quick-text">
                <h4>Ver atendimentos</h4>
                <p>See user conversations</p>
            </div>
        </a>
        <a href="<?php echo base_url('bot-builder/'.$bot->id.'/analytics') ?>" class="bb-ov-quick-card">
            <div class="bb-ov-quick-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);box-shadow:0 4px 12px rgba(139,92,246,0.25);">
                <i class="fad fa-chart-pie"></i>
            </div>
            <div class="bb-ov-quick-text">
                <h4>Métricas</h4>
                <p>Performance metrics & trends</p>
            </div>
        </a>
        <a href="<?php echo base_url('bot-builder/'.$bot->id.'/export') ?>" class="bb-ov-quick-card">
            <div class="bb-ov-quick-icon" style="background:linear-gradient(135deg,#f97316,#fb923c);box-shadow:0 4px 12px rgba(249,115,22,0.25);">
                <i class="fad fa-download"></i>
            </div>
            <div class="bb-ov-quick-text">
                <h4>Exportar JSON</h4>
                <p>Download bot flow as JSON</p>
            </div>
        </a>
    </div>
</div>
