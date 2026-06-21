<style>
/* =============================================
   BOT BUILDER — CREATE PAGE (Premium Dark)
   ============================================= */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ---- Wrapper ---- */
.tb-create-wrapper {
    min-height: 82vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    position: relative;
    overflow: hidden;
    background: linear-gradient(145deg, #0f0f1a 0%, #131328 40%, #0d1117 100%);
    border-radius: 18px;
    margin: 8px;
}

/* Animated mesh gradient background */
.tb-create-wrapper::before {
    content: '';
    position: absolute;
    top: -60%;
    left: -60%;
    width: 220%;
    height: 220%;
    background:
        radial-gradient(circle at 25% 35%, rgba(99, 102, 241, 0.08) 0%, transparent 45%),
        radial-gradient(circle at 75% 25%, rgba(139, 92, 246, 0.06) 0%, transparent 45%),
        radial-gradient(circle at 50% 75%, rgba(14, 165, 233, 0.05) 0%, transparent 45%),
        radial-gradient(circle at 80% 80%, rgba(236, 72, 153, 0.04) 0%, transparent 40%);
    animation: tb-mesh-drift 25s ease-in-out infinite alternate;
    pointer-events: none;
}

/* Floating particles */
.tb-create-wrapper::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(1.5px 1.5px at 10% 20%, rgba(139,92,246,0.25) 50%, transparent 50%),
        radial-gradient(1px 1px at 30% 60%, rgba(99,102,241,0.2) 50%, transparent 50%),
        radial-gradient(1.5px 1.5px at 60% 15%, rgba(14,165,233,0.2) 50%, transparent 50%),
        radial-gradient(1px 1px at 80% 50%, rgba(236,72,153,0.15) 50%, transparent 50%),
        radial-gradient(1px 1px at 50% 85%, rgba(99,102,241,0.15) 50%, transparent 50%),
        radial-gradient(1.5px 1.5px at 90% 90%, rgba(139,92,246,0.2) 50%, transparent 50%);
    animation: tb-particles-float 30s ease-in-out infinite alternate;
    pointer-events: none;
    opacity: 0.7;
}

@keyframes tb-mesh-drift {
    0%   { transform: translate(0, 0) rotate(0deg) scale(1); }
    50%  { transform: translate(-20px, -15px) rotate(1deg) scale(1.02); }
    100% { transform: translate(-40px, -25px) rotate(2deg) scale(1); }
}

@keyframes tb-particles-float {
    0%   { transform: translateY(0); }
    100% { transform: translateY(-15px); }
}

.tb-create-container {
    width: 100%;
    max-width: 640px;
    position: relative;
    z-index: 2;
}

/* ---- Header ---- */
.tb-create-header {
    text-align: center;
    margin-bottom: 42px;
}

.tb-create-header .tb-icon-wrap {
    width: 72px;
    height: 72px;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 22px;
    box-shadow:
        0 8px 32px rgba(79, 70, 229, 0.35),
        0 0 60px rgba(124, 58, 237, 0.15),
        inset 0 1px 0 rgba(255,255,255,0.15);
    animation: tb-icon-pulse 3s ease-in-out infinite;
    position: relative;
}

.tb-create-header .tb-icon-wrap::after {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 22px;
    background: linear-gradient(135deg, rgba(79,70,229,0.4), rgba(124,58,237,0.2), rgba(236,72,153,0.3));
    z-index: -1;
    filter: blur(8px);
    opacity: 0.6;
    animation: tb-icon-glow 3s ease-in-out infinite alternate;
}

@keyframes tb-icon-pulse {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-7px) rotate(-2deg); }
}

@keyframes tb-icon-glow {
    0% { opacity: 0.4; }
    100% { opacity: 0.8; }
}

.tb-create-header .tb-icon-wrap i {
    font-size: 30px;
    color: #fff;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}

.tb-create-header h2 {
    font-size: 30px;
    font-weight: 800;
    color: #f1f5f9;
    margin: 0 0 10px 0;
    letter-spacing: -0.5px;
    background: linear-gradient(135deg, #f1f5f9, #c7d2fe);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.tb-create-header p {
    font-size: 15px;
    color: #64748b;
    margin: 0;
    font-weight: 400;
    line-height: 1.5;
}

/* ---- Cards Grid ---- */
.tb-cards-grid {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* ---- Card Base ---- */
.tb-create-card {
    display: flex;
    align-items: center;
    gap: 18px;
    width: 100%;
    padding: 22px 26px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    color: inherit;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.tb-create-card::before {
    content: '';
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 0.35s;
    pointer-events: none;
    border-radius: 16px;
}

.tb-create-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.25);
    border-color: transparent;
}

.tb-create-card:active {
    transform: translateY(-1px);
}

/* Card Variants */
.tb-create-card.tb-scratch {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.06) 0%, rgba(255,255,255,0.02) 100%);
}
.tb-create-card.tb-scratch:hover {
    border-color: rgba(99, 102, 241, 0.4);
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(99, 102, 241, 0.03) 100%);
    box-shadow: 0 16px 48px rgba(79, 70, 229, 0.15), 0 0 0 1px rgba(99, 102, 241, 0.2);
}
.tb-create-card.tb-scratch .tb-card-icon {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    box-shadow: 0 4px 18px rgba(79, 70, 229, 0.4);
}

.tb-create-card.tb-template {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.04) 0%, rgba(255,255,255,0.02) 100%);
}
.tb-create-card.tb-template:hover {
    border-color: rgba(245, 158, 11, 0.4);
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(251, 191, 36, 0.03) 100%);
    box-shadow: 0 16px 48px rgba(245, 158, 11, 0.12), 0 0 0 1px rgba(245, 158, 11, 0.2);
}
.tb-create-card.tb-template .tb-card-icon {
    background: linear-gradient(135deg, #f59e0b, #f97316);
    box-shadow: 0 4px 18px rgba(245, 158, 11, 0.4);
}

.tb-create-card.tb-import {
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.04) 0%, rgba(255,255,255,0.02) 100%);
}
.tb-create-card.tb-import:hover {
    border-color: rgba(139, 92, 246, 0.4);
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.08) 0%, rgba(139, 92, 246, 0.03) 100%);
    box-shadow: 0 16px 48px rgba(124, 58, 237, 0.12), 0 0 0 1px rgba(139, 92, 246, 0.2);
}
.tb-create-card.tb-import .tb-card-icon {
    background: linear-gradient(135deg, #7c3aed, #a855f7);
    box-shadow: 0 4px 18px rgba(124, 58, 237, 0.4);
}

/* Card Icon */
.tb-card-icon {
    width: 50px;
    height: 50px;
    min-width: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    position: relative;
}

.tb-create-card:hover .tb-card-icon {
    transform: scale(1.1) rotate(-4deg);
}

.tb-card-icon i {
    font-size: 21px;
    color: #fff;
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.15));
}

/* Card Text */
.tb-card-text {
    flex: 1;
}

.tb-card-text h4 {
    font-size: 16px;
    font-weight: 650;
    color: #e2e8f0;
    margin: 0 0 4px 0;
}

.tb-card-text p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
    font-weight: 400;
    line-height: 1.45;
}

/* Card Arrow */
.tb-card-arrow {
    width: 34px;
    height: 34px;
    min-width: 34px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.06);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    transition: all 0.3s;
    font-size: 13px;
}

.tb-create-card:hover .tb-card-arrow {
    background: rgba(99, 102, 241, 0.15);
    border-color: rgba(99, 102, 241, 0.3);
    color: #818cf8;
    transform: translateX(4px);
}

.tb-create-card.tb-template:hover .tb-card-arrow {
    background: rgba(245, 158, 11, 0.12);
    border-color: rgba(245, 158, 11, 0.3);
    color: #fbbf24;
}

.tb-create-card.tb-import:hover .tb-card-arrow {
    background: rgba(139, 92, 246, 0.12);
    border-color: rgba(139, 92, 246, 0.3);
    color: #a78bfa;
}

/* ---- Divider ---- */
.tb-divider {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 10px 0;
}

.tb-divider::before,
.tb-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
}

.tb-divider span {
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* ---- Footer ---- */
.tb-create-footer {
    text-align: center;
    margin-top: 34px;
}

.tb-create-footer a {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    text-decoration: none;
    transition: all 0.25s;
    padding: 9px 18px;
    border-radius: 10px;
    border: 1px solid transparent;
}

.tb-create-footer a:hover {
    color: #a78bfa;
    background: rgba(139, 92, 246, 0.06);
    border-color: rgba(139, 92, 246, 0.12);
}

/* ---- Recent Bots ---- */
.tb-recent {
    margin-top: 36px;
    padding-top: 28px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.tb-recent-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #475569;
    margin-bottom: 14px;
}

.tb-recent-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.tb-recent-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.25s;
}

.tb-recent-item:hover {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.tb-recent-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.tb-recent-dot.draft { background: #f59e0b; box-shadow: 0 0 8px rgba(245, 158, 11, 0.4); }
.tb-recent-dot.published { background: #10b981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.4); }

.tb-recent-name {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    color: #cbd5e1;
}

.tb-recent-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 8px;
}

.tb-recent-badge.draft {
    background: rgba(245, 158, 11, 0.1);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.15);
}

.tb-recent-badge.published {
    background: rgba(16, 185, 129, 0.1);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.15);
}

/* ===== MODAL ===== */
.tb-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}

.tb-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.tb-modal {
    background: linear-gradient(160deg, #1a1a2e 0%, #16162a 50%, #131328 100%);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 24px;
    width: 95%;
    max-width: 480px;
    padding: 0;
    box-shadow:
        0 32px 64px -12px rgba(0, 0, 0, 0.5),
        0 0 80px rgba(99, 102, 241, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.04);
    transform: scale(0.92) translateY(20px);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    overflow: hidden;
}

.tb-modal-overlay.active .tb-modal {
    transform: scale(1) translateY(0);
}

/* Modal Header */
.tb-modal-header {
    padding: 32px 32px 0;
    text-align: center;
    position: relative;
}

.tb-modal-header-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3);
    position: relative;
}

.tb-modal-header-icon::after {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(79,70,229,0.4), rgba(124,58,237,0.2));
    z-index: -1;
    filter: blur(6px);
    opacity: 0.5;
}

.tb-modal-header-icon i {
    font-size: 24px;
    color: #fff;
}

.tb-modal h3 {
    font-size: 22px;
    font-weight: 700;
    color: #f1f5f9;
    margin: 0 0 6px 0;
    letter-spacing: -0.3px;
}

.tb-modal .tb-modal-desc {
    font-size: 14px;
    color: #64748b;
    margin: 0 0 0 0;
    line-height: 1.5;
}

/* Modal Body */
.tb-modal-body {
    padding: 28px 32px 8px;
}

.tb-modal .tb-form-group {
    margin-bottom: 22px;
}

.tb-modal .tb-form-group label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #94a3b8;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tb-modal .tb-input {
    width: 100%;
    padding: 13px 16px;
    border: 1.5px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    font-size: 14.5px;
    font-family: 'Inter', sans-serif;
    color: #e2e8f0;
    background: rgba(255, 255, 255, 0.03);
    transition: all 0.25s;
    outline: none;
    box-sizing: border-box;
}

.tb-modal .tb-input:focus {
    border-color: rgba(99, 102, 241, 0.5);
    background: rgba(99, 102, 241, 0.04);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.08), 0 0 20px rgba(99, 102, 241, 0.06);
}

.tb-modal .tb-input::placeholder {
    color: #475569;
}

/* Helper text under input */
.tb-input-hint {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 6px;
    font-size: 11.5px;
    color: #475569;
}

.tb-input-hint i {
    font-size: 11px;
    color: #6366f1;
}

/* Modal Actions */
.tb-modal-footer {
    padding: 16px 32px 28px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.tb-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 8px;
}

.tb-btn {
    padding: 11px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    border: none;
    cursor: pointer;
    transition: all 0.25s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    position: relative;
    overflow: hidden;
}

.tb-btn-cancel {
    background: rgba(255, 255, 255, 0.04);
    color: #94a3b8;
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.tb-btn-cancel:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #cbd5e1;
    border-color: rgba(255, 255, 255, 0.1);
}

.tb-btn-primary {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #fff;
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.35);
    border: 1px solid rgba(129, 140, 248, 0.2);
}

.tb-btn-primary::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%);
    opacity: 0;
    transition: opacity 0.25s;
}

.tb-btn-primary:hover {
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.45);
    transform: translateY(-1px);
}

.tb-btn-primary:hover::before {
    opacity: 1;
}

.tb-btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

/* Loading spinner */
.tb-btn .spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: tb-spin 0.6s linear infinite;
    display: none;
}

.tb-btn.loading .spinner { display: inline-block; }
.tb-btn.loading span { display: none; }

@keyframes tb-spin {
    to { transform: rotate(360deg); }
}

/* ---- Toast ---- */
.tb-toast {
    position: fixed;
    top: 24px;
    right: 24px;
    padding: 14px 24px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    z-index: 10000;
    transform: translateX(120%);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    backdrop-filter: blur(12px);
}

.tb-toast.show { transform: translateX(0); }
.tb-toast.error {
    background: rgba(239, 68, 68, 0.12);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.2);
    box-shadow: 0 8px 30px rgba(239, 68, 68, 0.15);
}
.tb-toast.success {
    background: rgba(16, 185, 129, 0.12);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.2);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.15);
}

/* ---- Import Zone ---- */
.tb-import-zone {
    display: none;
    margin-top: 14px;
    padding: 28px;
    border: 2px dashed rgba(139, 92, 246, 0.2);
    border-radius: 16px;
    text-align: center;
    transition: all 0.3s;
    background: rgba(124, 58, 237, 0.03);
}

.tb-import-zone.show { display: block; }

.tb-import-zone.dragover {
    border-color: rgba(139, 92, 246, 0.5);
    background: rgba(124, 58, 237, 0.08);
    box-shadow: 0 0 30px rgba(139, 92, 246, 0.08);
}

.tb-import-zone p {
    font-size: 13px;
    color: #64748b;
    margin: 8px 0 0;
}

/* Responsive */
@media (max-width: 480px) {
    .tb-create-card {
        padding: 16px 18px;
    }
    .tb-create-header h2 {
        font-size: 24px;
    }
    .tb-modal {
        margin: 16px;
    }
    .tb-modal-header, .tb-modal-body, .tb-modal-footer {
        padding-left: 24px;
        padding-right: 24px;
    }
}

/* ===== LIGHT MODE OVERRIDES ===== */
body.dl-light .tb-create-wrapper {
    background: linear-gradient(145deg, #f8fafc 0%, #eef2ff 40%, #f0fdf4 100%);
}
body.dl-light .tb-create-wrapper::before {
    background:
        radial-gradient(circle at 25% 35%, rgba(99, 102, 241, 0.06) 0%, transparent 45%),
        radial-gradient(circle at 75% 25%, rgba(139, 92, 246, 0.04) 0%, transparent 45%),
        radial-gradient(circle at 50% 75%, rgba(14, 165, 233, 0.04) 0%, transparent 45%),
        radial-gradient(circle at 80% 80%, rgba(236, 72, 153, 0.03) 0%, transparent 40%);
}
body.dl-light .tb-create-wrapper::after {
    opacity: 0; /* Hide floating particles in light mode */
}

/* Header Text */
body.dl-light .tb-create-header h2 {
    background: linear-gradient(135deg, #1e293b, #4f46e5);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
body.dl-light .tb-create-header p {
    color: #64748b;
}

/* Cards */
body.dl-light .tb-create-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    backdrop-filter: none;
}
body.dl-light .tb-create-card:hover {
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
}

body.dl-light .tb-create-card.tb-scratch {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.04) 0%, #ffffff 100%);
}
body.dl-light .tb-create-card.tb-scratch:hover {
    border-color: #6366f1;
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, #ffffff 100%);
    box-shadow: 0 12px 32px rgba(79, 70, 229, 0.12);
}

body.dl-light .tb-create-card.tb-template {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.04) 0%, #ffffff 100%);
}
body.dl-light .tb-create-card.tb-template:hover {
    border-color: #f59e0b;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, #ffffff 100%);
    box-shadow: 0 12px 32px rgba(245, 158, 11, 0.12);
}

body.dl-light .tb-create-card.tb-import {
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.04) 0%, #ffffff 100%);
}
body.dl-light .tb-create-card.tb-import:hover {
    border-color: #7c3aed;
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.08) 0%, #ffffff 100%);
    box-shadow: 0 12px 32px rgba(124, 58, 237, 0.12);
}

/* Card Text */
body.dl-light .tb-card-text h4 {
    color: #0f172a;
}
body.dl-light .tb-card-text p {
    color: #64748b;
}

/* Card Arrow */
body.dl-light .tb-card-arrow {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #94a3b8;
}

/* Divider */
body.dl-light .tb-divider::before,
body.dl-light .tb-divider::after {
    background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
}
body.dl-light .tb-divider span {
    color: #94a3b8;
}

/* Footer */
body.dl-light .tb-create-footer a {
    color: #64748b;
}
body.dl-light .tb-create-footer a:hover {
    color: #6366f1;
    background: rgba(99, 102, 241, 0.06);
    border-color: rgba(99, 102, 241, 0.15);
}

/* Recent Bots */
body.dl-light .tb-recent {
    border-top-color: #e2e8f0;
}
body.dl-light .tb-recent-title {
    color: #64748b;
}
body.dl-light .tb-recent-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
}
body.dl-light .tb-recent-item:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
body.dl-light .tb-recent-name {
    color: #1e293b;
}

/* Modal */
body.dl-light .tb-modal {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.15);
}
body.dl-light .tb-modal h3 { color: #0f172a; }
body.dl-light .tb-modal .tb-modal-desc { color: #64748b; }
body.dl-light .tb-modal .tb-form-group label { color: #475569; }
body.dl-light .tb-modal .tb-input {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #1e293b;
}
body.dl-light .tb-modal .tb-input:focus {
    border-color: #6366f1;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}
body.dl-light .tb-modal .tb-input::placeholder { color: #94a3b8; }
body.dl-light .tb-input-hint { color: #94a3b8; }

body.dl-light .tb-btn-cancel {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}
body.dl-light .tb-btn-cancel:hover {
    background: #e2e8f0;
    color: #475569;
}

/* Import Zone */
body.dl-light .tb-import-zone {
    border-color: #e2e8f0;
    background: #faf5ff;
}
body.dl-light .tb-import-zone.dragover {
    border-color: #7c3aed;
    background: rgba(124, 58, 237, 0.06);
}
body.dl-light .tb-import-zone p {
    color: #64748b;
}

/* Toast */
body.dl-light .tb-toast.error {
    background: #fef2f2;
    color: #ef4444;
    border-color: #fecaca;
}
body.dl-light .tb-toast.success {
    background: #f0fdf4;
    color: #16a34a;
    border-color: #bbf7d0;
}
</style>

<div class="tb-create-wrapper">
    <div class="tb-create-container">

        <!-- Header -->
        <div class="tb-create-header">
            <div class="tb-icon-wrap">
                <i class="fad fa-robot"></i>
            </div>
            <h2>Criar novo bot</h2>
            <p>Escolha como deseja começar sua automação para WhatsApp</p>
        </div>

        <!-- Cards -->
        <div class="tb-cards-grid">

            <!-- 1. Start from Scratch -->
            <div class="tb-create-card tb-scratch" id="btn-start-scratch" onclick="tbOpenScratchModal()">
                <div class="tb-card-icon">
                    <i class="fad fa-plus"></i>
                </div>
                <div class="tb-card-text">
                    <h4>Começar do zero</h4>
                    <p>Abra uma tela em branco e monte o fluxo passo a passo</p>
                </div>
                <div class="tb-card-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>

            <div class="tb-divider"><span>ou</span></div>

            <!-- 2. Start from Template -->
            <a href="<?php echo base_url('bot-builder/templates') ?>" class="tb-create-card tb-template">
                <div class="tb-card-icon">
                    <i class="fad fa-th-large"></i>
                </div>
                <div class="tb-card-text">
                    <h4>Usar modelo pronto</h4>
                    <p>Escolha um fluxo pré-montado e adapte para sua operação</p>
                </div>
                <div class="tb-card-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <!-- 3. Import a File -->
            <div class="tb-create-card tb-import" id="btn-import-file" onclick="tbToggleImport()">
                <div class="tb-card-icon">
                    <i class="fad fa-file-import"></i>
                </div>
                <div class="tb-card-text">
                    <h4>Importar arquivo</h4>
                    <p>Envie um arquivo .json exportado de outro bot</p>
                </div>
                <div class="tb-card-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>

            <!-- Import Drop Zone (hidden until clicked) -->
            <div class="tb-import-zone" id="import-zone">
                <form id="importForm" action="<?php echo base_url('bot-builder/import-file') ?>" method="post" enctype="multipart/form-data">
                    <?php echo csrf_field() ?>
                    <div class="tb-card-icon" style="background: linear-gradient(135deg, #7c3aed, #a855f7); margin: 0 auto 10px; box-shadow: 0 4px 18px rgba(124, 58, 237, 0.4);">
                        <i class="fad fa-cloud-upload" style="font-size: 22px; color: #fff;"></i>
                    </div>
                    <label for="importFileInput" class="tb-btn tb-btn-primary" style="cursor: pointer; margin-top: 6px;">
                        <i class="fad fa-folder-open"></i>
                        <span>Escolher arquivo JSON</span>
                    </label>
                    <input type="file" id="importFileInput" name="file" accept=".json" style="display:none" onchange="tbHandleFileSelect(this)">
                    <p>ou arraste o arquivo .json para cá</p>
                    <div id="import-file-name" style="display:none; margin-top:10px; padding:8px 14px; background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.15); border-radius:10px; color:#34d399; font-weight:500; font-size:13px;">
                        <i class="fad fa-check-circle"></i> <span></span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="tb-create-footer">
            <a href="<?php echo base_url('bot-builder') ?>">
                <i class="fas fa-arrow-left"></i>
                Voltar para bots
            </a>
        </div>

        <!-- Recent Bots (if any) -->
        <?php if(!empty($recent_bots)): ?>
        <div class="tb-recent">
            <div class="tb-recent-title">Bots recentes</div>
            <div class="tb-recent-list">
                <?php foreach(array_slice($recent_bots, 0, 3) as $bot): ?>
                <a href="<?php echo base_url('bot-builder/'.$bot->id.'/editor') ?>" class="tb-recent-item">
                    <div class="tb-recent-dot <?php echo $bot->status ? 'published' : 'draft' ?>"></div>
                    <span class="tb-recent-name"><?php echo esc($bot->name) ?></span>
                    <span class="tb-recent-badge <?php echo $bot->status ? 'published' : 'draft' ?>">
                        <?php echo $bot->status ? 'Publicado' : 'Rascunho' ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL: Name Your Bot -->
<div class="tb-modal-overlay" id="scratchModal">
    <div class="tb-modal">
        <div class="tb-modal-header">
            <div class="tb-modal-header-icon">
                <i class="fad fa-sparkles"></i>
            </div>
            <h3>Nome do bot</h3>
            <p class="tb-modal-desc">Dê um nome para identificar o fluxo. Você pode alterar depois.</p>
        </div>
        <form id="scratchForm" action="<?php echo base_url('bot-builder/start-scratch') ?>" method="post">
            <?php echo csrf_field() ?>
            <div class="tb-modal-body">
                <div class="tb-form-group">
                    <label for="bot-name-input">Nome do bot</label>
                    <input type="text" class="tb-input" id="bot-name-input" name="bot_name" placeholder="Meu bot de WhatsApp" autocomplete="off" autofocus>
                    <div class="tb-input-hint">
                        <i class="fas fa-info-circle"></i>
                        Esse nome aparece apenas para sua equipe
                    </div>
                </div>
                <div class="tb-form-group">
                    <label for="bot-keywords-input">Palavras-chave de ativação <span style="color:#475569; font-weight:400; text-transform:none; letter-spacing:0;">(separadas por vírgula)</span></label>
                    <input type="text" class="tb-input" id="bot-keywords-input" name="trigger_keywords" placeholder="oi, menu, começar">
                    <div class="tb-input-hint">
                        <i class="fas fa-info-circle"></i>
                        O bot inicia quando o contato enviar uma dessas palavras
                    </div>
                </div>
            </div>
            <div class="tb-modal-footer">
                <button type="button" class="tb-btn tb-btn-cancel" onclick="tbCloseScratchModal()">Cancelar</button>
                <button type="submit" class="tb-btn tb-btn-primary" id="btn-create-bot">
                    <i class="fas fa-rocket"></i>
                    <span>Criar bot</span>
                    <div class="spinner"></div>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
<div class="tb-toast" id="tb-toast"></div>

<script>
// ===== SCRATCH MODAL =====
function tbOpenScratchModal() {
    document.getElementById('scratchModal').classList.add('active');
    setTimeout(() => {
        document.getElementById('bot-name-input').focus();
    }, 350);
}

function tbCloseScratchModal() {
    document.getElementById('scratchModal').classList.remove('active');
}

// Close modal on overlay click
document.getElementById('scratchModal').addEventListener('click', function(e) {
    if(e.target === this) tbCloseScratchModal();
});

// Close on ESC
document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') {
        tbCloseScratchModal();
        document.getElementById('import-zone').classList.remove('show');
    }
});

// Form Submit with Loading
document.getElementById('scratchForm').addEventListener('submit', function(e) {
    var nameVal = document.getElementById('bot-name-input').value.trim();
    if(!nameVal) {
        e.preventDefault();
        document.getElementById('bot-name-input').style.borderColor = 'rgba(239, 68, 68, 0.5)';
        document.getElementById('bot-name-input').style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1), 0 0 20px rgba(239, 68, 68, 0.06)';
        tbShowToast('Informe um nome para o bot', 'error');
        // Reset error styling after 2s
        setTimeout(function() {
            document.getElementById('bot-name-input').style.borderColor = '';
            document.getElementById('bot-name-input').style.boxShadow = '';
        }, 2000);
        return;
    }
    document.getElementById('btn-create-bot').classList.add('loading');
});

// ===== IMPORT =====
function tbToggleImport() {
    var zone = document.getElementById('import-zone');
    zone.classList.toggle('show');
    if(zone.classList.contains('show')) {
        zone.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function tbHandleFileSelect(input) {
    if(input.files && input.files[0]) {
        var file = input.files[0];
        if(!file.name.endsWith('.json')) {
            tbShowToast('Selecione um arquivo .json', 'error');
            input.value = '';
            return;
        }
        // Show filename
        var nameEl = document.getElementById('import-file-name');
        nameEl.querySelector('span').textContent = file.name;
        nameEl.style.display = 'block';
        // Auto-submit after brief delay
        setTimeout(function() {
            document.getElementById('importForm').submit();
        }, 600);
    }
}

// Drag & Drop
var importZone = document.getElementById('import-zone');

importZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('dragover');
});

importZone.addEventListener('dragleave', function() {
    this.classList.remove('dragover');
});

importZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    var files = e.dataTransfer.files;
    if(files.length > 0) {
        var fileInput = document.getElementById('importFileInput');
        fileInput.files = files;
        tbHandleFileSelect(fileInput);
    }
});

// ===== TOAST =====
function tbShowToast(msg, type) {
    var toast = document.getElementById('tb-toast');
    toast.className = 'tb-toast ' + type;
    toast.textContent = msg;
    setTimeout(function() { toast.classList.add('show'); }, 10);
    setTimeout(function() { toast.classList.remove('show'); }, 3500);
}

// Show flash messages if any
<?php if(session()->getFlashdata('error')): ?>
    tbShowToast('<?php echo addslashes(session()->getFlashdata('error')) ?>', 'error');
<?php endif; ?>
<?php if(session()->getFlashdata('success')): ?>
    tbShowToast('<?php echo addslashes(session()->getFlashdata('success')) ?>', 'success');
<?php endif; ?>
</script>
