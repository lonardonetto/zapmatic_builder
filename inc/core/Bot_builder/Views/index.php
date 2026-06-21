<style>
/* =============================================
   BOT BUILDER DASHBOARD — Premium Dark Theme UI
   SaaS-grade card-based dashboard with glassmorphism
   ============================================= */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.bb-dashboard {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    padding: 0;
    position: relative;
    min-height: 80vh;
    color: #e2e8f0;
}

/* ===== HERO HEADER ===== */
.bb-hero {
    background: linear-gradient(135deg, #1a1a40 0%, #2d1b69 40%, #4c1d95 70%, #7c3aed 100%);
    border-radius: 20px;
    padding: 36px 40px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(124, 58, 237, 0.25), 0 0 0 1px rgba(124, 58, 237, 0.15);
}

.bb-hero::before {
    content: '';
    position: absolute;
    top: -80px;
    right: -80px;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(139, 92, 246, 0.3) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    animation: bb-hero-orb1 8s ease-in-out infinite;
}

@keyframes bb-hero-orb1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(-30px, 20px) scale(1.1); }
}

.bb-hero::after {
    content: '';
    position: absolute;
    bottom: -60px;
    left: -40px;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(236, 72, 153, 0.2) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    animation: bb-hero-orb2 10s ease-in-out infinite;
}

@keyframes bb-hero-orb2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(20px, -15px) scale(1.15); }
}

.bb-hero-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    z-index: 2;
    flex-wrap: wrap;
    gap: 20px;
}

.bb-hero-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.bb-hero-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #818cf8, #a78bfa, #f472b6);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
    box-shadow: 0 8px 30px rgba(139, 92, 246, 0.45), inset 0 1px 0 rgba(255,255,255,0.2);
    animation: bb-icon-float 3s ease-in-out infinite;
    flex-shrink: 0;
}

@keyframes bb-icon-float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-5px) rotate(2deg); }
}

.bb-hero-text h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.5px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.bb-hero-text p {
    margin: 6px 0 0;
    font-size: 15px;
    color: rgba(255,255,255,0.8);
    font-weight: 500;
}

.bb-hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.bb-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 22px;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}

.bb-btn i {
    font-size: 14px;
}

.bb-btn-primary {
    background: linear-gradient(135deg, #818cf8, #a78bfa);
    color: #fff;
    box-shadow: 0 4px 18px rgba(139, 92, 246, 0.4), inset 0 1px 0 rgba(255,255,255,0.15);
}

.bb-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(139, 92, 246, 0.55);
    background: linear-gradient(135deg, #6366f1, #818cf8);
    color: #fff;
    text-decoration: none;
}

.bb-btn-glass {
    background: rgba(255,255,255,0.1);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(12px);
}

.bb-btn-glass:hover {
    background: rgba(255,255,255,0.18);
    color: #fff;
    transform: translateY(-2px);
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* ===== STATS ROW ===== */
.bb-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}

.bb-stat-card {
    background: rgba(30, 35, 55, 0.7);
    border: 1px solid rgba(99, 102, 241, 0.12);
    border-radius: 16px;
    padding: 22px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.bb-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    opacity: 0;
    transition: opacity 0.3s;
}

.bb-stat-card::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 16px;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
}

.bb-stat-card:hover {
    transform: translateY(-4px);
    border-color: rgba(99, 102, 241, 0.3);
}

.bb-stat-card:hover::before {
    opacity: 1;
}

.bb-stat-card:nth-child(1)::before { background: linear-gradient(90deg, #6366f1, #818cf8); }
.bb-stat-card:nth-child(1):hover { box-shadow: 0 8px 30px rgba(99, 102, 241, 0.2); }
.bb-stat-card:nth-child(2)::before { background: linear-gradient(90deg, #10b981, #34d399); }
.bb-stat-card:nth-child(2):hover { box-shadow: 0 8px 30px rgba(16, 185, 129, 0.2); }
.bb-stat-card:nth-child(3)::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.bb-stat-card:nth-child(3):hover { box-shadow: 0 8px 30px rgba(245, 158, 11, 0.2); }
.bb-stat-card:nth-child(4)::before { background: linear-gradient(90deg, #ec4899, #f472b6); }
.bb-stat-card:nth-child(4):hover { box-shadow: 0 8px 30px rgba(236, 72, 153, 0.2); }

.bb-stat-icon {
    width: 50px;
    height: 50px;
    min-width: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    transition: all 0.3s;
}

.bb-stat-card:hover .bb-stat-icon {
    transform: scale(1.1);
}

.bb-stat-icon.indigo { background: rgba(99, 102, 241, 0.15); color: #818cf8; box-shadow: 0 0 20px rgba(99, 102, 241, 0.1); }
.bb-stat-icon.emerald { background: rgba(16, 185, 129, 0.15); color: #34d399; box-shadow: 0 0 20px rgba(16, 185, 129, 0.1); }
.bb-stat-icon.amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; box-shadow: 0 0 20px rgba(245, 158, 11, 0.1); }
.bb-stat-icon.pink { background: rgba(236, 72, 153, 0.15); color: #f472b6; box-shadow: 0 0 20px rgba(236, 72, 153, 0.1); }

.bb-stat-info h4 {
    margin: 0;
    font-size: 24px;
    font-weight: 800;
    color: #f1f5f9;
    letter-spacing: -0.5px;
    line-height: 1;
}

.bb-stat-info span {
    font-size: 12px;
    font-weight: 500;
    color: #94a3b8;
    margin-top: 4px;
    display: block;
}

/* ===== SECTION HEADER ===== */
.bb-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 12px;
}

.bb-section-title {
    font-size: 17px;
    font-weight: 700;
    color: #e2e8f0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.bb-section-title i {
    font-size: 18px;
    color: #818cf8;
}

.bb-section-title .bb-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    background: rgba(99, 102, 241, 0.15);
    color: #818cf8;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid rgba(99, 102, 241, 0.2);
}

.bb-view-toggle {
    display: flex;
    background: rgba(30, 35, 55, 0.6);
    border-radius: 10px;
    padding: 3px;
    gap: 2px;
    border: 1px solid rgba(99, 102, 241, 0.1);
}

.bb-view-btn {
    width: 34px;
    height: 34px;
    border: none;
    background: transparent;
    border-radius: 8px;
    color: #64748b;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.bb-view-btn.active {
    background: rgba(99, 102, 241, 0.2);
    color: #818cf8;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}

.bb-view-btn:hover:not(.active) {
    color: #94a3b8;
}

/* ===== BOT CARDS GRID ===== */
.bb-bots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 18px;
}

.bb-bot-card {
    background: rgba(25, 30, 48, 0.8);
    border: 1px solid rgba(99, 102, 241, 0.1);
    border-radius: 18px;
    padding: 0;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(10px);
}

.bb-bot-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.25), 0 0 0 1px rgba(99, 102, 241, 0.2);
    border-color: rgba(99, 102, 241, 0.25);
}

.bb-bot-card-top {
    height: 4px;
    background: linear-gradient(90deg, rgba(100, 116, 139, 0.3), rgba(100, 116, 139, 0.15));
    transition: all 0.3s;
}

.bb-bot-card.published .bb-bot-card-top {
    background: linear-gradient(90deg, #10b981, #34d399, #6ee7b7);
    box-shadow: 0 2px 10px rgba(16, 185, 129, 0.3);
}

.bb-bot-card.draft .bb-bot-card-top {
    background: linear-gradient(90deg, #f59e0b, #fbbf24, #fde68a);
    box-shadow: 0 2px 10px rgba(245, 158, 11, 0.3);
}

.bb-bot-card:hover .bb-bot-card-top {
    height: 5px;
}

.bb-bot-card-body {
    padding: 22px 24px 18px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.bb-bot-card-header {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 16px;
}

.bb-bot-avatar {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    color: #fff;
    position: relative;
    transition: all 0.3s;
}

.bb-bot-card:hover .bb-bot-avatar {
    transform: scale(1.05);
}

.bb-bot-avatar.gradient-1 { background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 6px 18px rgba(99,102,241,0.35); }
.bb-bot-avatar.gradient-2 { background: linear-gradient(135deg, #ec4899, #f472b6); box-shadow: 0 6px 18px rgba(236,72,153,0.35); }
.bb-bot-avatar.gradient-3 { background: linear-gradient(135deg, #06b6d4, #22d3ee); box-shadow: 0 6px 18px rgba(6,182,212,0.35); }
.bb-bot-avatar.gradient-4 { background: linear-gradient(135deg, #f97316, #fb923c); box-shadow: 0 6px 18px rgba(249,115,22,0.35); }
.bb-bot-avatar.gradient-5 { background: linear-gradient(135deg, #10b981, #34d399); box-shadow: 0 6px 18px rgba(16,185,129,0.35); }
.bb-bot-avatar.gradient-6 { background: linear-gradient(135deg, #3b82f6, #60a5fa); box-shadow: 0 6px 18px rgba(59,130,246,0.35); }

.bb-bot-avatar .pulse-dot {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2.5px solid rgba(25, 30, 48, 0.9);
}

.bb-bot-avatar .pulse-dot.active {
    background: #10b981;
    animation: bb-pulse 2s ease-in-out infinite;
}

.bb-bot-avatar .pulse-dot.inactive {
    background: #f59e0b;
}

@keyframes bb-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5); }
    50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
}

.bb-bot-meta {
    flex: 1;
    min-width: 0;
}

.bb-bot-name {
    font-size: 16px;
    font-weight: 700;
    color: #f1f5f9;
    margin: 0 0 6px;
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.2;
}

.bb-bot-name a {
    color: inherit;
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color 0.2s;
}

.bb-bot-name a:hover {
    color: #a78bfa;
}

.bb-bot-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    flex-shrink: 0;
}

.bb-bot-status.published {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.bb-bot-status.draft {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.bb-bot-status i {
    font-size: 7px;
}

/* Keywords Section */
.bb-bot-keywords {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 18px;
    flex: 1;
}

.bb-keyword-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: rgba(99, 102, 241, 0.08);
    border: 1px solid rgba(99, 102, 241, 0.12);
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 500;
    color: #a5b4fc;
    transition: all 0.2s;
}

.bb-keyword-tag i {
    font-size: 9px;
    color: #6366f1;
}

.bb-keyword-tag:hover {
    background: rgba(99, 102, 241, 0.18);
    border-color: rgba(99, 102, 241, 0.3);
    color: #c7d2fe;
}

.bb-no-keywords {
    font-size: 12px;
    color: #475569;
    font-style: italic;
}

/* Card Footer */
.bb-bot-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 24px;
    border-top: 1px solid rgba(99, 102, 241, 0.08);
    background: rgba(15, 18, 30, 0.4);
}

.bb-bot-card-footer .bb-bot-date {
    font-size: 11.5px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 5px;
}

.bb-bot-card-footer .bb-bot-date i {
    font-size: 11px;
    color: #475569;
}

.bb-card-actions {
    display: flex;
    gap: 6px;
}

.bb-action-btn {
    width: 34px;
    height: 34px;
    border: 1px solid rgba(99, 102, 241, 0.1);
    background: rgba(30, 35, 55, 0.6);
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.25s;
    text-decoration: none;
}

.bb-action-btn:hover {
    transform: translateY(-2px);
}

.bb-action-btn.edit:hover {
    background: rgba(99, 102, 241, 0.2);
    border-color: rgba(99, 102, 241, 0.3);
    color: #818cf8;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
}

.bb-action-btn.analytics:hover {
    background: rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.3);
    color: #34d399;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
}

.bb-action-btn.sessions:hover {
    background: rgba(59, 130, 246, 0.2);
    border-color: rgba(59, 130, 246, 0.3);
    color: #60a5fa;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2);
}

.bb-action-btn.export:hover {
    background: rgba(139, 92, 246, 0.2);
    border-color: rgba(139, 92, 246, 0.3);
    color: #a78bfa;
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.2);
}

.bb-action-btn.delete:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.3);
    color: #f87171;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);
}

/* ===== EMPTY STATE ===== */
.bb-empty-state {
    text-align: center;
    padding: 60px 30px;
    background: rgba(25, 30, 48, 0.6);
    border: 2px dashed rgba(99, 102, 241, 0.2);
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

.bb-empty-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2));
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: #818cf8;
    margin-bottom: 20px;
    animation: bb-icon-float 3s ease-in-out infinite;
    box-shadow: 0 0 30px rgba(99, 102, 241, 0.15);
}

.bb-empty-state h3 {
    font-size: 20px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0 0 8px;
}

.bb-empty-state p {
    font-size: 14px;
    color: #64748b;
    margin: 0 0 24px;
    max-width: 380px;
    margin-left: auto;
    margin-right: auto;
}

/* ===== DELETE MODAL ===== */
.bb-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s;
}

.bb-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.bb-modal {
    background: linear-gradient(135deg, #1e2235, #252a3e);
    border: 1px solid rgba(99, 102, 241, 0.15);
    border-radius: 20px;
    width: 95%;
    max-width: 420px;
    padding: 32px;
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(99, 102, 241, 0.1);
    transform: scale(0.95) translateY(10px);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    text-align: center;
}

.bb-modal-overlay.active .bb-modal {
    transform: scale(1) translateY(0);
}

.bb-modal-icon {
    width: 64px;
    height: 64px;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #f87171;
    margin-bottom: 18px;
}

.bb-modal h3 {
    font-size: 20px;
    font-weight: 700;
    color: #f1f5f9;
    margin: 0 0 8px;
}

.bb-modal p {
    font-size: 14px;
    color: #94a3b8;
    margin: 0 0 24px;
    line-height: 1.5;
}

.bb-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.bb-modal-btn {
    padding: 11px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.bb-modal-btn.cancel {
    background: rgba(100, 116, 139, 0.15);
    color: #94a3b8;
    border: 1px solid rgba(100, 116, 139, 0.2);
}

.bb-modal-btn.cancel:hover {
    background: rgba(100, 116, 139, 0.25);
    color: #cbd5e1;
}

.bb-modal-btn.danger {
    background: linear-gradient(135deg, #ef4444, #f87171);
    color: #fff;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
}

.bb-modal-btn.danger:hover {
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
    transform: translateY(-1px);
}

/* ===== TOOLTIP ===== */
.bb-action-btn[data-tooltip] {
    position: relative;
}

.bb-action-btn[data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%) scale(0.9);
    padding: 5px 10px;
    background: #0f1219;
    color: #e2e8f0;
    font-size: 11px;
    font-weight: 500;
    border-radius: 6px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s;
    pointer-events: none;
    z-index: 100;
    border: 1px solid rgba(99, 102, 241, 0.15);
}

.bb-action-btn[data-tooltip]:hover::after {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) scale(1);
}

/* ===== SEARCH BAR ===== */
.bb-search-wrapper {
    position: relative;
    max-width: 280px;
}

.bb-search-wrapper input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    border: 1.5px solid rgba(99, 102, 241, 0.15);
    border-radius: 10px;
    font-size: 13px;
    font-family: 'Inter', sans-serif;
    color: #e2e8f0;
    background: rgba(30, 35, 55, 0.6);
    outline: none;
    transition: all 0.2s;
    backdrop-filter: blur(10px);
}

.bb-search-wrapper input:focus {
    border-color: rgba(99, 102, 241, 0.4);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    background: rgba(30, 35, 55, 0.8);
}

.bb-search-wrapper input::placeholder {
    color: #475569;
}

.bb-search-wrapper i {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 13px;
    color: #64748b;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .bb-hero {
        padding: 24px;
        border-radius: 16px;
    }
    .bb-hero-inner {
        flex-direction: column;
        align-items: flex-start;
    }
    .bb-hero-text h2 {
        font-size: 20px;
    }
    .bb-bots-grid {
        grid-template-columns: 1fr;
    }
    .bb-stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    .bb-section-header {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .bb-stats-row {
        grid-template-columns: 1fr;
    }
}
/* ===== LIGHT MODE OVERRIDES ===== */
body.dl-light .bb-dashboard {
    color: #1e293b;
    /* Premium ambient background with subtle color tints */
    background: linear-gradient(160deg, #f8fafc 0%, #eef2ff 35%, #faf5ff 65%, #f0fdf4 100%);
    border-radius: 16px;
    padding: 24px;
    position: relative;
}
/* Decorative ambient orbs */
body.dl-light .bb-dashboard::before {
    content: '';
    position: absolute;
    top: -100px;
    right: -100px;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.07) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
}
body.dl-light .bb-dashboard::after {
    content: '';
    position: absolute;
    bottom: -80px;
    left: -80px;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(236, 72, 153, 0.05) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
}
/* Ensure all children sit above the orbs */
body.dl-light .bb-dashboard > * {
    position: relative;
    z-index: 1;
}

/* Hero Light Mode - Fresh, vibrant gradient */
body.dl-light .bb-hero {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 40%, #818cf8 70%, #a78bfa 100%) !important;
    box-shadow: 0 8px 24px -4px rgba(99, 102, 241, 0.35) !important;
    border: none !important;
}
body.dl-light .bb-hero::before {
    background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 60%) !important;
    animation: none !important;
}
body.dl-light .bb-hero::after {
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 60%) !important;
    animation: none !important;
}
body.dl-light .bb-hero-text h2 {
    color: #ffffff !important;
    text-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
}
body.dl-light .bb-hero-text p {
    color: rgba(255,255,255,0.9) !important;
}
body.dl-light .bb-hero-icon {
    box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
}
body.dl-light .bb-btn-glass {
    background: rgba(255,255,255,0.2) !important;
    border-color: rgba(255,255,255,0.3) !important;
    color: #fff !important;
}
body.dl-light .bb-btn-glass:hover {
    background: rgba(255,255,255,0.3) !important;
}
body.dl-light .bb-btn-primary {
    background: #ffffff !important;
    color: #4f46e5 !important;
    box-shadow: 0 4px 14px rgba(0,0,0,0.15) !important;
}
body.dl-light .bb-btn-primary:hover {
    background: #f8fafc !important;
    color: #4338ca !important;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2) !important;
}

/* Stat Cards Light */
body.dl-light .bb-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    backdrop-filter: none;
}
body.dl-light .bb-stat-info h4 { color: #0f172a; }
body.dl-light .bb-stat-info span { color: #64748b; }

/* Bot Cards Light */
body.dl-light .bb-bot-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    backdrop-filter: none;
}
body.dl-light .bb-bot-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border-color: #6366f1;
}

body.dl-light .bb-bot-name { color: #0f172a; }
body.dl-light .bb-bot-name a:hover { color: #6366f1; }

body.dl-light .bb-keyword-tag {
    background: #f1f5f9;
    border-color: #e2e8f0;
    color: #475569;
}
body.dl-light .bb-keyword-tag i { color: #6366f1; }

body.dl-light .bb-bot-card-footer {
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
}

/* Action Buttons Light */
body.dl-light .bb-action-btn {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #64748b;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
body.dl-light .bb-action-btn:hover {
    background: #f8fafc;
}

/* Search Input Light */
body.dl-light .bb-search-wrapper input {
    background: #ffffff;
    border-color: #e2e8f0;
    color: #1e293b;
}
body.dl-light .bb-search-wrapper input::placeholder {
    color: #94a3b8;
}
body.dl-light .bb-search-wrapper input:focus {
    border-color: #6366f1;
    background: #ffffff;
}

/* Section Headers */
body.dl-light .bb-section-title {
    color: #1e293b;
}
body.dl-light .bb-count-badge {
    background: #eff6ff;
    color: #6366f1;
}

/* Empty State Light */
body.dl-light .bb-empty-state {
    background: #ffffff;
    border-color: #e2e8f0;
}
body.dl-light .bb-empty-state h3 { color: #0f172a; }

/* ===== PROFESSIONAL EXTERNAL DASHBOARD REFINEMENT ===== */
.bb-dashboard {
    color: #0f172a;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 18px 32px;
    box-sizing: border-box;
}

@media (max-width: 1400px) {
    .bb-dashboard {
        max-width: 1180px;
    }
}

@media (max-width: 991px) {
    .bb-dashboard {
        padding: 0 12px 24px;
    }
}

.bb-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 58%, #334155 100%);
    border-radius: 22px;
    padding: 30px 34px;
    margin-bottom: 22px;
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.16), inset 0 1px 0 rgba(255,255,255,0.08);
}

.bb-hero::before {
    background: radial-gradient(circle, rgba(148, 163, 184, 0.22) 0%, transparent 70%);
}

.bb-hero::after {
    background: radial-gradient(circle, rgba(99, 102, 241, 0.14) 0%, transparent 70%);
}

.bb-hero-icon {
    background: linear-gradient(135deg, #475569, #0f172a);
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.35), inset 0 1px 0 rgba(255,255,255,0.14);
}

.bb-btn-primary {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.26);
}

.bb-btn-primary:hover {
    background: linear-gradient(135deg, #1d4ed8, #4338ca);
    box-shadow: 0 16px 34px rgba(37, 99, 235, 0.32);
}

.bb-stat-card,
.bb-bot-card {
    background: rgba(255,255,255,0.94);
    border: 1px solid rgba(226, 232, 240, 0.92);
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.07);
    backdrop-filter: blur(12px);
}

.bb-stat-card:hover,
.bb-bot-card:hover {
    border-color: rgba(37, 99, 235, 0.24);
    box-shadow: 0 22px 48px rgba(15, 23, 42, 0.12);
}

.bb-stat-info h4,
.bb-bot-name {
    color: #0f172a;
}

.bb-stat-info span,
.bb-bot-card-footer .bb-bot-date,
.bb-no-keywords {
    color: #64748b;
}

.bb-bot-card {
    border-radius: 22px;
    overflow: hidden;
}

.bb-bot-card-top {
    height: 5px;
    background: linear-gradient(90deg, #cbd5e1, #e2e8f0);
}

.bb-bot-card.published .bb-bot-card-top {
    background: linear-gradient(90deg, #0f766e, #14b8a6);
    box-shadow: 0 2px 12px rgba(20, 184, 166, 0.20);
}

.bb-bot-card.draft .bb-bot-card-top {
    background: linear-gradient(90deg, #b45309, #f59e0b);
    box-shadow: 0 2px 12px rgba(245, 158, 11, 0.18);
}

.bb-bot-card-body {
    padding: 24px 24px 18px;
}

.bb-bot-avatar {
    border-radius: 16px;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.15) !important;
}

.bb-bot-avatar.gradient-1,
.bb-bot-avatar.gradient-2,
.bb-bot-avatar.gradient-3,
.bb-bot-avatar.gradient-4,
.bb-bot-avatar.gradient-5,
.bb-bot-avatar.gradient-6 {
    background: linear-gradient(135deg, #334155, #0f172a);
}

.bb-bot-avatar .pulse-dot {
    border-color: #fff;
}

.bb-bot-name a:hover {
    color: #2563eb;
}

.bb-bot-status {
    border-radius: 999px;
    font-weight: 800;
}

.bb-bot-status.published {
    background: #ecfdf5;
    color: #047857;
    border-color: rgba(16, 185, 129, 0.18);
}

.bb-bot-status.draft {
    background: #fffbeb;
    color: #b45309;
    border-color: rgba(245, 158, 11, 0.20);
}

.bb-keyword-tag {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #475569;
    border-radius: 999px;
    font-weight: 700;
}

.bb-keyword-tag i {
    color: #64748b;
}

.bb-bot-card-footer {
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    border-top: 1px solid rgba(226, 232, 240, 0.8);
}

.bb-action-btn {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #64748b;
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.045);
}

.bb-action-btn.edit:hover,
.bb-action-btn.sessions:hover,
.bb-action-btn.analytics:hover,
.bb-action-btn.export:hover {
    background: #eff6ff;
    border-color: rgba(37, 99, 235, 0.22);
    color: #2563eb;
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.12);
}

.bb-action-btn.delete:hover {
    background: #fef2f2;
    border-color: rgba(239, 68, 68, 0.22);
    color: #dc2626;
    box-shadow: 0 10px 24px rgba(239, 68, 68, 0.10);
}

.bb-search-wrapper input {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #0f172a;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.bb-section-title {
    color: #0f172a;
}

.bb-count-badge {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid rgba(37, 99, 235, 0.12);
}

.bb-stats-row,
.bb-bots-grid {
    gap: 18px;
}

.bb-bots-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

.bb-section-header {
    margin: 24px 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.bb-section-title {
    display: inline-flex !important;
    align-items: center !important;
    gap: 7px !important;
    color: #0f172a;
    line-height: 1;
}

.bb-section-title i {
    font-size: 17px !important;
    line-height: 1 !important;
    color: #64748b !important;
}

.bb-section-title .bb-count-badge {
    margin-left: 2px !important;
    min-width: 22px !important;
    height: 22px !important;
    padding: 0 7px !important;
    border-radius: 9px !important;
    font-size: 11px !important;
    line-height: 22px !important;
}

body.dl-light .bb-dashboard {
    color: #0f172a;
}

body.dl-light .bb-stat-card,
body.dl-light .bb-bot-card {
    background: rgba(255,255,255,0.94);
    border-color: rgba(226, 232, 240, 0.92);
}

body.dl-dark .bb-dashboard,
body.dark .bb-dashboard,
body[data-theme="dark"] .bb-dashboard {
    color: #e2e8f0;
}

body.dl-dark .bb-stat-card,
body.dark .bb-stat-card,
body[data-theme="dark"] .bb-stat-card,
body.dl-dark .bb-bot-card,
body.dark .bb-bot-card,
body[data-theme="dark"] .bb-bot-card {
    background: rgba(15, 23, 42, 0.88);
    border-color: rgba(71, 85, 105, 0.72);
    box-shadow: 0 18px 42px rgba(0,0,0,0.22);
}

body.dl-dark .bb-stat-info h4,
body.dark .bb-stat-info h4,
body[data-theme="dark"] .bb-stat-info h4,
body.dl-dark .bb-bot-name,
body.dark .bb-bot-name,
body[data-theme="dark"] .bb-bot-name,
body.dl-dark .bb-bot-name a,
body.dark .bb-bot-name a,
body[data-theme="dark"] .bb-bot-name a,
body.dl-dark .bb-section-title,
body.dark .bb-section-title,
body[data-theme="dark"] .bb-section-title {
    color: #f8fafc;
}

body.dl-dark .bb-stat-info span,
body.dark .bb-stat-info span,
body[data-theme="dark"] .bb-stat-info span,
body.dl-dark .bb-bot-card-footer .bb-bot-date,
body.dark .bb-bot-card-footer .bb-bot-date,
body[data-theme="dark"] .bb-bot-card-footer .bb-bot-date,
body.dl-dark .bb-no-keywords,
body.dark .bb-no-keywords,
body[data-theme="dark"] .bb-no-keywords {
    color: #94a3b8;
}

body.dl-dark .bb-bot-card-footer,
body.dark .bb-bot-card-footer,
body[data-theme="dark"] .bb-bot-card-footer {
    background: linear-gradient(180deg, rgba(15,23,42,0.92), rgba(15,23,42,0.98));
    border-top-color: rgba(71, 85, 105, 0.65);
}

body.dl-dark .bb-keyword-tag,
body.dark .bb-keyword-tag,
body[data-theme="dark"] .bb-keyword-tag,
body.dl-dark .bb-action-btn,
body.dark .bb-action-btn,
body[data-theme="dark"] .bb-action-btn,
body.dl-dark .bb-search-wrapper input,
body.dark .bb-search-wrapper input,
body[data-theme="dark"] .bb-search-wrapper input {
    background: rgba(30, 41, 59, 0.88);
    border-color: rgba(71, 85, 105, 0.72);
    color: #cbd5e1;
}

.bb-bot-card-header {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    gap: 14px;
    align-items: start;
    margin-bottom: 14px;
}

.bb-bot-avatar {
    width: 46px !important;
    height: 46px !important;
    min-width: 46px !important;
    border-radius: 14px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 18px !important;
    margin: 0 !important;
    position: relative !important;
}

.bb-bot-avatar i {
    line-height: 1 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.bb-bot-avatar .pulse-dot {
    width: 11px !important;
    height: 11px !important;
    right: -2px !important;
    bottom: 2px !important;
}

.bb-bot-info {
    min-width: 0;
    padding-top: 2px;
}

.bb-bot-name {
    margin: 0 0 7px !important;
    line-height: 1.25 !important;
    font-size: 15px !important;
}

.bb-bot-meta {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
}

.bb-keywords-list {
    display: flex !important;
    align-items: center !important;
    flex-wrap: wrap !important;
    gap: 6px !important;
    margin-top: 10px !important;
    min-height: 28px;
}

.bb-keyword-tag {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px !important;
    height: 24px !important;
    max-width: 118px !important;
    padding: 0 9px !important;
    border-radius: 999px !important;
    font-size: 10.5px !important;
    line-height: 1 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

.bb-keyword-tag i {
    font-size: 9px !important;
    line-height: 1 !important;
    flex: 0 0 auto !important;
}

.bb-keyword-more {
    height: 24px !important;
    padding: 0 9px !important;
    border-radius: 999px !important;
    font-size: 10.5px !important;
    line-height: 24px !important;
    white-space: nowrap !important;
}

.bb-bot-card-body {
    min-height: 150px;
}

@media (max-width: 1200px) {
    .bb-bots-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 767px) {
    .bb-dashboard {
        max-width: 100%;
        padding-left: 10px;
        padding-right: 10px;
    }
    .bb-hero {
        padding: 24px 22px;
    }
    .bb-bots-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Calculate stats
$total_bots = !empty($bots) ? count($bots) : 0;
$published_bots = 0;
$draft_bots = 0;
$total_keywords = 0;
if(!empty($bots)) {
    foreach($bots as $bot) {
        if($bot->status) $published_bots++;
        else $draft_bots++;
        if(!empty($bot->trigger_keywords)) {
            $keywords = array_filter(array_map('trim', explode(',', $bot->trigger_keywords)));
            $total_keywords += count($keywords);
        }
    }
}
$gradient_classes = ['gradient-1','gradient-2','gradient-3','gradient-4','gradient-5','gradient-6'];
$bot_icons = ['fad fa-robot','fad fa-comment-dots','fad fa-bolt','fad fa-cogs','fad fa-paper-plane','fad fa-brain'];
?>

<div class="bb-dashboard">

    <!-- HERO HEADER -->
    <div class="bb-hero">
        <div class="bb-hero-inner">
            <div class="bb-hero-left">
                <div class="bb-hero-icon">
                    <i class="fad fa-robot"></i>
                </div>
                <div class="bb-hero-text">
                    <h2>Construtor de Bots</h2>
                    <p>Crie, gerencie e publique automações visuais para WhatsApp</p>
                </div>
            </div>
            <div class="bb-hero-actions">
                <a href="<?php echo base_url('bot-builder/templates') ?>" class="bb-btn bb-btn-glass">
                    <i class="fad fa-store"></i> Modelos
                </a>
                <a href="<?php echo base_url('bot-builder/create') ?>" class="bb-btn bb-btn-primary">
                    <i class="fad fa-plus"></i> Criar bot
                </a>
            </div>
        </div>
    </div>

    <!-- STATS ROW -->
    <div class="bb-stats-row">
        <div class="bb-stat-card">
            <div class="bb-stat-icon indigo">
                <i class="fad fa-robot"></i>
            </div>
            <div class="bb-stat-info">
                <h4><?php echo $total_bots ?></h4>
                <span>Total de bots</span>
            </div>
        </div>
        <div class="bb-stat-card">
            <div class="bb-stat-icon emerald">
                <i class="fad fa-check-circle"></i>
            </div>
            <div class="bb-stat-info">
                <h4><?php echo $published_bots ?></h4>
                <span>Publicados</span>
            </div>
        </div>
        <div class="bb-stat-card">
            <div class="bb-stat-icon amber">
                <i class="fad fa-pencil-ruler"></i>
            </div>
            <div class="bb-stat-info">
                <h4><?php echo $draft_bots ?></h4>
                <span>Rascunhos</span>
            </div>
        </div>
        <div class="bb-stat-card">
            <div class="bb-stat-icon pink">
                <i class="fad fa-tags"></i>
            </div>
            <div class="bb-stat-info">
                <h4><?php echo $total_keywords ?></h4>
                <span>Palavras-chave</span>
            </div>
        </div>
    </div>

    <!-- SECTION HEADER -->
    <div class="bb-section-header">
        <div class="bb-section-title">
            <i class="fad fa-layer-group"></i>
            Seus bots
            <span class="bb-count-badge"><?php echo $total_bots ?></span>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="bb-search-wrapper">
                <i class="fad fa-search"></i>
                <input type="text" id="bb-search-input" placeholder="Buscar bots..." oninput="bbFilterBots(this.value)">
            </div>
        </div>
    </div>

    <!-- BOTS GRID -->
    <?php if(!empty($bots)): ?>
    <div class="bb-bots-grid" id="bb-bots-grid">
        <?php foreach($bots as $index => $bot): ?>
        <?php
            $gradientClass = $gradient_classes[$index % count($gradient_classes)];
            $iconClass = $bot_icons[$index % count($bot_icons)];
            $statusClass = $bot->status ? 'published' : 'draft';
            $statusLabel = $bot->status ? 'Publicado' : 'Rascunho';
            $keywords = !empty($bot->trigger_keywords) ? array_filter(array_map('trim', explode(',', $bot->trigger_keywords))) : [];
            $createdDate = isset($bot->created_at) ? date('d/m/Y', strtotime($bot->created_at)) : '';
        ?>
        <div class="bb-bot-card <?php echo $statusClass ?>" data-name="<?php echo strtolower(esc($bot->name)) ?>" data-keywords="<?php echo strtolower(esc($bot->trigger_keywords ?? '')) ?>">
            <div class="bb-bot-card-top"></div>
            <div class="bb-bot-card-body">
                <div class="bb-bot-card-header">
                    <div class="bb-bot-avatar <?php echo $gradientClass ?>">
                        <i class="<?php echo $iconClass ?>"></i>
                        <div class="pulse-dot <?php echo $bot->status ? 'active' : 'inactive' ?>"></div>
                    </div>
                    <div class="bb-bot-meta">
                        <div class="bb-bot-name">
                            <a href="<?php echo base_url('bot-builder/'.$bot->id.'/editor') ?>"><?php _e(esc($bot->name)) ?></a>
                        </div>
                        <span class="bb-bot-status <?php echo $statusClass ?>">
                            <i class="fas fa-circle"></i>
                            <?php echo $statusLabel ?>
                        </span>
                    </div>
                </div>
                <div class="bb-bot-keywords">
                    <?php if(!empty($keywords)): ?>
                        <?php foreach(array_slice($keywords, 0, 4) as $kw): ?>
                            <span class="bb-keyword-tag"><i class="fad fa-hashtag"></i> <?php echo esc(trim($kw)) ?></span>
                        <?php endforeach; ?>
                        <?php if(count($keywords) > 4): ?>
                            <span class="bb-keyword-tag" style="background:#eef2ff; color:#6366f1; font-weight:600;">+<?php echo count($keywords) - 4 ?> mais</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="bb-no-keywords">Nenhuma palavra-chave configurada</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bb-bot-card-footer">
                <div class="bb-bot-date">
                    <i class="fad fa-calendar-alt"></i>
                    <?php echo $createdDate ?: 'Recente' ?>
                </div>
                <div class="bb-card-actions">
                    <a href="<?php echo base_url('bot-builder/'.$bot->id.'/editor') ?>" class="bb-action-btn edit" data-tooltip="Editar fluxo">
                        <i class="fad fa-edit"></i>
                    </a>
                    <a href="<?php echo base_url('bot-builder/'.$bot->id.'/sessions') ?>" class="bb-action-btn sessions" data-tooltip="Atendimentos">
                        <i class="fad fa-users"></i>
                    </a>
                    <a href="<?php echo base_url('bot-builder/'.$bot->id.'/analytics') ?>" class="bb-action-btn analytics" data-tooltip="Métricas">
                        <i class="fad fa-chart-bar"></i>
                    </a>
                    <a href="<?php echo base_url('bot-builder/'.$bot->id.'/export') ?>" class="bb-action-btn export" data-tooltip="Exportar JSON">
                        <i class="fad fa-download"></i>
                    </a>
                    <button class="bb-action-btn delete" data-tooltip="Excluir" onclick="bbConfirmDelete('<?php echo base_url('bot-builder/delete') ?>', <?php echo $bot->id ?>, '<?php echo esc($bot->name) ?>')">
                        <i class="fad fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- EMPTY STATE -->
    <div class="bb-empty-state">
        <div class="bb-empty-icon">
            <i class="fad fa-robot"></i>
        </div>
        <h3>Nenhum bot criado ainda</h3>
        <p>Monte seu primeiro fluxo de automação para WhatsApp em poucos minutos. Use um modelo pronto ou comece do zero.</p>
        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo base_url('bot-builder/create') ?>" class="bb-btn bb-btn-primary">
                <i class="fad fa-plus"></i> Criar primeiro bot
            </a>
            <a href="<?php echo base_url('bot-builder/templates') ?>" class="bb-btn" style="background: #f3f4f6; color: #374151;">
                <i class="fad fa-store"></i> Ver modelos
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- DELETE CONFIRMATION MODAL -->
<div class="bb-modal-overlay" id="bb-delete-modal">
    <div class="bb-modal">
        <div class="bb-modal-icon">
            <i class="fad fa-trash-alt"></i>
        </div>
        <h3>Excluir bot?</h3>
        <p>Tem certeza que deseja excluir <strong id="bb-delete-name"></strong>? Esta ação não pode ser desfeita e todos os dados do fluxo serão removidos.</p>
        <div class="bb-modal-actions">
            <button class="bb-modal-btn cancel" onclick="bbCloseDeleteModal()">Cancelar</button>
            <button class="bb-modal-btn danger" id="bb-delete-confirm" onclick="bbExecuteDelete()">
                <i class="fad fa-trash-alt"></i> Excluir bot
            </button>
        </div>
    </div>
</div>

<script>
// ===== EXCLUSÃO =====
var bbDeleteUrl = '';
var bbDeleteId = 0;

function bbConfirmDelete(url, id, name) {
    bbDeleteUrl = url;
    bbDeleteId = id;
    document.getElementById('bb-delete-name').textContent = name;
    document.getElementById('bb-delete-modal').classList.add('active');
}

function bbCloseDeleteModal() {
    document.getElementById('bb-delete-modal').classList.remove('active');
}

function bbExecuteDelete() {
    var btn = document.getElementById('bb-delete-confirm');
    btn.innerHTML = '<i class="fad fa-spinner-third fa-spin"></i> Excluindo...';
    btn.disabled = true;

    $.post(bbDeleteUrl, {
        id: bbDeleteId,
        <?php echo csrf_token() ?>: '<?php echo csrf_hash() ?>'
    }, function(result) {
        location.reload();
    }).fail(function() {
        btn.innerHTML = '<i class="fad fa-trash-alt"></i> Excluir bot';
        btn.disabled = false;
        alert('Não foi possível excluir. Tente novamente.');
    });
}

// Close modal on overlay click
document.getElementById('bb-delete-modal').addEventListener('click', function(e) {
    if(e.target === this) bbCloseDeleteModal();
});

// Close on ESC
document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') bbCloseDeleteModal();
});

// ===== SEARCH / FILTER =====
function bbFilterBots(query) {
    query = query.toLowerCase().trim();
    var cards = document.querySelectorAll('.bb-bot-card');
    cards.forEach(function(card) {
        var name = card.getAttribute('data-name') || '';
        var keywords = card.getAttribute('data-keywords') || '';
        if(name.indexOf(query) !== -1 || keywords.indexOf(query) !== -1) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
