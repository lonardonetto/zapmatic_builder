<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.bb-sessions-page {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 80vh;
}

/* Hero */
.bb-sess-hero {
    background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
    border-radius: 20px;
    padding: 32px 36px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}

.bb-sess-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.bb-sess-hero-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    z-index: 2;
}

.bb-sess-hero-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.bb-sess-hero-icon {
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

.bb-sess-hero-text h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.4px;
}

.bb-sess-hero-text p {
    margin: 3px 0 0;
    font-size: 14px;
    color: rgba(255,255,255,0.9);
    font-weight: 500;
}

.bb-sess-hero-actions {
    display: flex;
    gap: 10px;
}

.bb-sess-btn {
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

.bb-sess-btn i { font-size: 13px; }

.bb-sess-btn-back {
    background: rgba(255,255,255,0.2);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.25);
    backdrop-filter: blur(5px);
}

.bb-sess-btn-back:hover {
    background: rgba(255,255,255,0.14);
    color: #fff;
    text-decoration: none;
}

.bb-sess-btn-editor {
    background: linear-gradient(135deg, #6366f1, #818cf8);
    color: #fff;
    box-shadow: 0 4px 14px rgba(99,102,241,0.3);
}

.bb-sess-btn-editor:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99,102,241,0.4);
    color: #fff;
    text-decoration: none;
}

/* Stats Row */
.bb-sess-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}

.bb-sess-stat {
    background: #fff;
    border: 1px solid #eef0f4;
    border-radius: 14px;
    padding: 20px 22px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.25s;
}

.bb-sess-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.05);
}

.bb-sess-stat-icon {
    width: 44px; height: 44px; min-width: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.bb-sess-stat-icon.blue { background: #eff6ff; color: #3b82f6; }
.bb-sess-stat-icon.green { background: #ecfdf5; color: #10b981; }
.bb-sess-stat-icon.amber { background: #fffbeb; color: #f59e0b; }

.bb-sess-stat-info h4 {
    margin: 0; font-size: 20px; font-weight: 800; color: #111827; line-height: 1;
}
.bb-sess-stat-info span {
    font-size: 11.5px; font-weight: 500; color: #9ca3af; margin-top: 3px; display: block;
}

/* Table Card */
.bb-sess-table-card {
    background: #fff;
    border: 1px solid #eef0f4;
    border-radius: 18px;
    overflow: hidden;
}

.bb-sess-table-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.bb-sess-table-title {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bb-sess-table-title i { color: #3b82f6; font-size: 16px; }

.bb-sess-search {
    position: relative;
    max-width: 240px;
}

.bb-sess-search input {
    width: 100%;
    padding: 8px 12px 8px 34px;
    border: 1.5px solid #e5e7eb;
    border-radius: 9px;
    font-size: 12.5px;
    font-family: 'Inter', sans-serif;
    color: #111827;
    outline: none;
    transition: all 0.2s;
}

.bb-sess-search input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.08);
}

.bb-sess-search input::placeholder { color: #c2c7d0; }

.bb-sess-search i {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #9ca3af;
}

/* Table */
.bb-sess-table {
    width: 100%;
    border-collapse: collapse;
}

.bb-sess-table thead th {
    padding: 12px 20px;
    font-size: 11px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    text-align: left;
    background: #fafbfc;
    border-bottom: 1px solid #f0f0f4;
}

.bb-sess-table tbody tr {
    transition: background 0.15s;
    border-bottom: 1px solid #f8f9fa;
}

.bb-sess-table tbody tr:hover {
    background: #f8fafc;
}

.bb-sess-table tbody tr:last-child {
    border-bottom: none;
}

.bb-sess-table tbody td {
    padding: 14px 20px;
    font-size: 13.5px;
    color: #374151;
    vertical-align: middle;
}

.bb-sess-phone {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #111827;
}

.bb-sess-phone-icon {
    width: 32px; height: 32px;
    background: #ecfdf5;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #10b981;
}

.bb-sess-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.bb-sess-status.active-status { background: #eff6ff; color: #2563eb; }
.bb-sess-status.completed-status { background: #ecfdf5; color: #059669; }
.bb-sess-status i { font-size: 7px; }

.bb-sess-block-id {
    font-family: 'SF Mono', 'Consolas', monospace;
    font-size: 11.5px;
    color: #6b7280;
    background: #f3f4f6;
    padding: 3px 8px;
    border-radius: 5px;
    display: inline-block;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.bb-sess-date {
    font-size: 12.5px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 5px;
}

.bb-sess-date i { font-size: 11px; color: #c4c9d4; }

.bb-sess-context-btn {
    width: 30px; height: 30px;
    border: 1px solid #eef0f4;
    background: #fff;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.bb-sess-context-btn:hover {
    background: #eef2ff;
    border-color: #c7d2fe;
    color: #6366f1;
}

/* Empty */
.bb-sess-empty {
    text-align: center;
    padding: 50px 30px;
}

.bb-sess-empty-icon {
    width: 64px; height: 64px;
    background: #eff6ff;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #3b82f6;
    margin-bottom: 16px;
}

.bb-sess-empty h4 {
    font-size: 17px; font-weight: 700; color: #111827; margin: 0 0 6px;
}

.bb-sess-empty p {
    font-size: 13px; color: #9ca3af; margin: 0;
}

/* Context Modal */
.bb-ctx-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0; visibility: hidden;
    transition: all 0.25s;
}

.bb-ctx-modal-overlay.active { opacity: 1; visibility: visible; }

.bb-ctx-modal {
    background: #fff;
    border-radius: 18px;
    width: 95%; max-width: 520px;
    max-height: 80vh;
    box-shadow: 0 25px 60px -15px rgba(0,0,0,0.3);
    transform: scale(0.95) translateY(10px);
    transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
    display: flex;
    flex-direction: column;
}

.bb-ctx-modal-overlay.active .bb-ctx-modal {
    transform: scale(1) translateY(0);
}

.bb-ctx-modal-head {
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f4;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.bb-ctx-modal-head h3 {
    margin: 0; font-size: 16px; font-weight: 700; color: #111827;
    display: flex; align-items: center; gap: 8px;
}

.bb-ctx-modal-head h3 i { color: #6366f1; }

.bb-ctx-modal-close {
    width: 32px; height: 32px;
    border: none; background: #f3f4f6;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: #6b7280; font-size: 14px; cursor: pointer;
    transition: all 0.2s;
}

.bb-ctx-modal-close:hover { background: #e5e7eb; color: #111827; }

.bb-ctx-modal-body {
    padding: 20px 24px;
    overflow-y: auto;
    flex: 1;
}

.bb-ctx-modal-body pre {
    background: #f8fafc;
    border: 1px solid #eef0f4;
    border-radius: 10px;
    padding: 16px;
    font-size: 12px;
    font-family: 'SF Mono', 'Consolas', monospace;
    color: #374151;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-all;
    margin: 0;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .bb-sess-hero { padding: 20px; border-radius: 14px; }
    .bb-sess-hero-inner { flex-direction: column; align-items: flex-start; }
    .bb-sess-stats { grid-template-columns: 1fr; }
    .bb-sess-table-header { flex-direction: column; align-items: flex-start; }
    .bb-sess-search { max-width: 100%; width: 100%; }
}
</style>

<div class="bb-sessions-page">

    <!-- HERO -->
    <div class="bb-sess-hero">
        <div class="bb-sess-hero-inner">
            <div class="bb-sess-hero-left">
                <div class="bb-sess-hero-icon">
                    <i class="fad fa-users"></i>
                </div>
                <div class="bb-sess-hero-text">
                    <h2><?php echo esc($bot->name) ?> — Atendimentos</h2>
                    <p>Acompanhe conversas dos contatos e interações do bot</p>
                </div>
            </div>
            <div class="bb-sess-hero-actions">
                <a href="<?php echo base_url('bot-builder') ?>" class="bb-sess-btn bb-sess-btn-back">
                    <i class="fad fa-arrow-left"></i> Voltar
                </a>
                <a href="<?php echo base_url('bot-builder/'.$bot->id.'/editor') ?>" class="bb-sess-btn bb-sess-btn-editor">
                    <i class="fad fa-edit"></i> Abrir editor
                </a>
            </div>
        </div>
    </div>

    <!-- STATS -->
    <div class="bb-sess-stats">
        <div class="bb-sess-stat">
            <div class="bb-sess-stat-icon blue"><i class="fad fa-comments"></i></div>
            <div class="bb-sess-stat-info">
                <h4><?php echo $total_sessions ?></h4>
                <span>Total de atendimentos</span>
            </div>
        </div>
        <div class="bb-sess-stat">
            <div class="bb-sess-stat-icon green"><i class="fad fa-check-circle"></i></div>
            <div class="bb-sess-stat-info">
                <h4><?php echo $completed_sessions ?></h4>
                <span>Finalizados</span>
            </div>
        </div>
        <div class="bb-sess-stat">
            <div class="bb-sess-stat-icon amber"><i class="fad fa-spinner-third"></i></div>
            <div class="bb-sess-stat-info">
                <h4><?php echo $active_sessions ?></h4>
                <span>Ativos / em andamento</span>
            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="bb-sess-table-card">
        <div class="bb-sess-table-header">
            <div class="bb-sess-table-title">
                <i class="fad fa-list-ul"></i>
                Histórico de atendimentos
            </div>
            <div class="bb-sess-search">
                <i class="fad fa-search"></i>
                <input type="text" placeholder="Buscar por telefone..." oninput="bbFilterSessions(this.value)">
            </div>
        </div>

        <?php if(!empty($sessions)): ?>
        <table class="bb-sess-table" id="bb-sessions-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Telefone</th>
                    <th>Status</th>
                    <th>Bloco atual</th>
                    <th>Iniciado em</th>
                    <th>Última atualização</th>
                    <th>Contexto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sessions as $idx => $s): ?>
                <tr data-phone="<?php echo strtolower($s->phone ?? '') ?>">
                    <td style="color: #c4c9d4; font-weight: 600; font-size: 12px;"><?php echo $idx + 1 ?></td>
                    <td>
                        <div class="bb-sess-phone">
                            <div class="bb-sess-phone-icon"><i class="fad fa-phone"></i></div>
                            <?php echo esc($s->phone ?? 'Desconhecido') ?>
                        </div>
                    </td>
                    <td>
                        <?php if(!empty($s->is_completed)): ?>
                            <span class="bb-sess-status completed-status"><i class="fas fa-circle"></i> Finalizado</span>
                        <?php else: ?>
                            <span class="bb-sess-status active-status"><i class="fas fa-circle"></i> Ativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if(!empty($s->current_block_id)): ?>
                            <span class="bb-sess-block-id" title="<?php echo esc($s->current_block_id) ?>"><?php echo esc($s->current_block_id) ?></span>
                        <?php else: ?>
                            <span style="color: #d1d5db;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="bb-sess-date">
                            <i class="fad fa-calendar"></i>
                            <?php echo !empty($s->created_at) ? date('d/m/Y H:i', strtotime($s->created_at)) : '—' ?>
                        </div>
                    </td>
                    <td>
                        <div class="bb-sess-date">
                            <i class="fad fa-clock"></i>
                            <?php echo !empty($s->updated_at) ? date('d/m/Y H:i', strtotime($s->updated_at)) : '—' ?>
                        </div>
                    </td>
                    <td>
                        <button class="bb-sess-context-btn" onclick="bbShowContext(<?php echo htmlspecialchars(json_encode($s->context ?? '{}'), ENT_QUOTES) ?>, '<?php echo esc($s->phone ?? '') ?>')" title="Ver contexto">
                            <i class="fad fa-code"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="bb-sess-empty">
            <div class="bb-sess-empty-icon"><i class="fad fa-inbox"></i></div>
            <h4>Nenhum atendimento ainda</h4>
            <p>Os atendimentos aparecerão aqui quando os contatos interagirem com o bot</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- CONTEXT MODAL -->
<div class="bb-ctx-modal-overlay" id="bbCtxModal">
    <div class="bb-ctx-modal">
        <div class="bb-ctx-modal-head">
            <h3><i class="fad fa-brackets-curly"></i> Contexto do atendimento <span id="bbCtxPhone" style="font-weight:400; color:#9ca3af; font-size:13px;"></span></h3>
            <button class="bb-ctx-modal-close" onclick="bbCloseCtxModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="bb-ctx-modal-body">
            <pre id="bbCtxContent"></pre>
        </div>
    </div>
</div>

<script>
function bbShowContext(ctx, phone) {
    try {
        var parsed = typeof ctx === 'string' ? JSON.parse(ctx) : ctx;
        document.getElementById('bbCtxContent').textContent = JSON.stringify(parsed, null, 2);
    } catch(e) {
        document.getElementById('bbCtxContent').textContent = ctx || '{}';
    }
    document.getElementById('bbCtxPhone').textContent = phone ? '— ' + phone : '';
    document.getElementById('bbCtxModal').classList.add('active');
}

function bbCloseCtxModal() {
    document.getElementById('bbCtxModal').classList.remove('active');
}

document.getElementById('bbCtxModal').addEventListener('click', function(e) {
    if(e.target === this) bbCloseCtxModal();
});

document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') bbCloseCtxModal();
});

function bbFilterSessions(query) {
    query = query.toLowerCase().trim();
    var rows = document.querySelectorAll('#bb-sessions-table tbody tr');
    rows.forEach(function(row) {
        var phone = row.getAttribute('data-phone') || '';
        row.style.display = phone.indexOf(query) !== -1 ? '' : 'none';
    });
}
</script>
