<style>
/* =============================================
   TEMPLATES MARKETPLACE — Premium SaaS UI
   ============================================= */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.tm-wrapper {
    padding: 30px 24px 60px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
}

/* Hero Banner */
.tm-hero {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 40%, #ec4899 100%);
    border-radius: 20px;
    padding: 36px 40px;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 32px;
}

.tm-hero::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -10%;
    width: 340px;
    height: 340px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
}

.tm-hero::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: 20%;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
}

.tm-hero h2 {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 8px;
    letter-spacing: -0.5px;
    position: relative;
    z-index: 2;
}

.tm-hero p {
    font-size: 15px;
    margin: 0;
    opacity: 0.85;
    font-weight: 400;
    position: relative;
    z-index: 2;
}

.tm-hero-icon {
    position: absolute;
    right: 40px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 72px;
    opacity: 0.15;
    z-index: 1;
}

/* Top Bar */
.tm-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}

.tm-search {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    padding: 0 16px;
    flex: 1;
    max-width: 360px;
    transition: all 0.2s;
}

.tm-search:focus-within {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.tm-search i {
    color: #9ca3af;
    font-size: 14px;
}

.tm-search input {
    border: none;
    outline: none;
    padding: 12px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    width: 100%;
    background: transparent;
    color: #111827;
}

.tm-search input::placeholder {
    color: #c2c7d0;
}

.tm-actions {
    display: flex;
    gap: 10px;
}

.tm-btn {
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}

.tm-btn-outline {
    background: #fff;
    color: #374151;
    border: 1.5px solid #e5e7eb;
}

.tm-btn-outline:hover {
    border-color: #4f46e5;
    color: #4f46e5;
    background: #f5f3ff;
}

.tm-btn-primary {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #fff;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
}

.tm-btn-primary:hover {
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
    transform: translateY(-1px);
}

/* Category Pills */
.tm-categories {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.tm-cat-pill {
    padding: 8px 18px;
    border-radius: 100px;
    font-size: 13px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.tm-cat-pill:hover {
    border-color: #c7d2fe;
    color: #4f46e5;
    background: #f5f3ff;
}

.tm-cat-pill.active {
    background: #4f46e5;
    color: #fff;
    border-color: #4f46e5;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.tm-cat-pill .count {
    background: rgba(0,0,0,0.08);
    padding: 1px 7px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 600;
}

.tm-cat-pill.active .count {
    background: rgba(255,255,255,0.25);
}

/* Template Grid */
.tm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

/* Template Card */
.tm-card {
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
}

.tm-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
    border-color: transparent;
}

/* Card Preview Area */
.tm-card-preview {
    height: 150px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.tm-card-preview .tm-card-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    z-index: 2;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.tm-card:hover .tm-card-icon {
    transform: scale(1.1) rotate(-3deg);
}

/* Badge */
.tm-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    z-index: 3;
}

.tm-badge.free {
    background: #ecfdf5;
    color: #059669;
}

.tm-badge.premium {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
}

/* Card Body */
.tm-card-body {
    padding: 18px 20px 20px;
}

.tm-card-category {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #9ca3af;
    margin-bottom: 6px;
}

.tm-card-name {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 6px;
    line-height: 1.3;
}

.tm-card-desc {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 14px;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Card Footer */
.tm-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.tm-card-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    color: #9ca3af;
}

.tm-card-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.tm-card-install-btn {
    padding: 7px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
    background: #f0f0ff;
    color: #4f46e5;
}

.tm-card-install-btn:hover {
    background: #4f46e5;
    color: #fff;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

/* Empty State */
.tm-empty {
    text-align: center;
    padding: 60px 20px;
    grid-column: 1 / -1;
}

.tm-empty i {
    font-size: 56px;
    color: #d1d5db;
    margin-bottom: 16px;
    display: block;
}

.tm-empty h4 {
    font-size: 18px;
    font-weight: 700;
    color: #374151;
    margin: 0 0 6px;
}

.tm-empty p {
    font-size: 14px;
    color: #9ca3af;
    margin: 0;
}

/* ===== PREVIEW MODAL ===== */
.tm-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s;
}

.tm-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.tm-modal {
    background: #fff;
    border-radius: 20px;
    width: 95%;
    max-width: 560px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    transform: scale(0.95) translateY(10px);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.tm-modal-overlay.active .tm-modal {
    transform: scale(1) translateY(0);
}

.tm-modal-header {
    padding: 28px 28px 0;
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.tm-modal-icon {
    width: 52px;
    height: 52px;
    min-width: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
}

.tm-modal-title {
    flex: 1;
}

.tm-modal-title h3 {
    font-size: 22px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 4px;
}

.tm-modal-title .tm-modal-cat {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #9ca3af;
}

.tm-modal-close {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: none;
    background: #f3f4f6;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: all 0.2s;
}

.tm-modal-close:hover {
    background: #e5e7eb;
    color: #111827;
}

.tm-modal-body {
    padding: 24px 28px;
}

.tm-modal-desc {
    font-size: 14px;
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 20px;
}

.tm-modal-stats {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.tm-stat {
    padding: 10px 16px;
    background: #f9fafb;
    border: 1px solid #f0f0f0;
    border-radius: 10px;
    font-size: 12px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 6px;
}

.tm-stat strong {
    color: #374151;
    font-weight: 700;
}

/* Flow Preview */
.tm-flow-preview {
    background: #f9fafb;
    border: 1px solid #f0f0f0;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
}

.tm-flow-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #9ca3af;
    margin-bottom: 10px;
}

.tm-flow-nodes {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.tm-flow-node {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    background: #fff;
    border: 1px solid #e5e7eb;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 4px;
}

.tm-flow-node i {
    font-size: 10px;
}

.tm-modal-footer {
    padding: 0 28px 28px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Spinner */
.tm-btn .spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: tm-spin 0.6s linear infinite;
    display: none;
}
.tm-btn.loading .spinner { display: inline-block; }
.tm-btn.loading .btn-text { display: none; }

@keyframes tm-spin { to { transform: rotate(360deg); } }

/* Toast */
.tm-toast {
    position: fixed;
    top: 24px;
    right: 24px;
    padding: 14px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    z-index: 10001;
    transform: translateX(120%);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}
.tm-toast.show { transform: translateX(0); }
.tm-toast.error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.tm-toast.success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }

/* Responsive */
@media (max-width: 640px) {
    .tm-hero { padding: 24px; }
    .tm-hero h2 { font-size: 22px; }
    .tm-topbar { flex-direction: column; }
    .tm-search { max-width: 100%; }
    .tm-grid { grid-template-columns: 1fr; }
}

/* =============================================
   DARK MODE — Premium Dark Theme
   ============================================= */
[data-theme=dark] .tm-wrapper {
    color: #CDCDDE;
}

/* Search Bar */
[data-theme=dark] .tm-search {
    background: #1b1b29;
    border-color: #2B2B40;
}
[data-theme=dark] .tm-search:focus-within {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}
[data-theme=dark] .tm-search i {
    color: #565674;
}
[data-theme=dark] .tm-search input {
    color: #CDCDDE;
}
[data-theme=dark] .tm-search input::placeholder {
    color: #565674;
}

/* Buttons */
[data-theme=dark] .tm-btn-outline {
    background: rgba(43, 43, 64, 0.6);
    color: #CDCDDE;
    border-color: #323248;
}
[data-theme=dark] .tm-btn-outline:hover {
    border-color: #6366f1;
    color: #a5b4fc;
    background: rgba(99, 102, 241, 0.1);
}

/* Category Pills */
[data-theme=dark] .tm-cat-pill {
    background: rgba(43, 43, 64, 0.5);
    border-color: #323248;
    color: #92929F;
}
[data-theme=dark] .tm-cat-pill:hover {
    border-color: rgba(99, 102, 241, 0.4);
    color: #a5b4fc;
    background: rgba(99, 102, 241, 0.08);
}
[data-theme=dark] .tm-cat-pill.active {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.35);
}
[data-theme=dark] .tm-cat-pill .count {
    background: rgba(255,255,255,0.06);
}
[data-theme=dark] .tm-cat-pill.active .count {
    background: rgba(255,255,255,0.2);
}

/* Template Cards */
[data-theme=dark] .tm-card {
    background: #1e1e2d;
    border-color: #2B2B40;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}
[data-theme=dark] .tm-card:hover {
    border-color: rgba(99, 102, 241, 0.3);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(99, 102, 241, 0.15);
}

/* Card Preview Area */
[data-theme=dark] .tm-card-preview {
    background: rgba(27, 27, 41, 0.5);
}
[data-theme=dark] .tm-card-preview .tm-card-icon {
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.4);
}

/* Dark Badges */
[data-theme=dark] .tm-badge.free {
    background: rgba(5, 150, 105, 0.15);
    color: #34d399;
    border: 1px solid rgba(5, 150, 105, 0.2);
}
[data-theme=dark] .tm-badge.premium {
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.15), rgba(245, 158, 11, 0.2));
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.2);
}

/* Card Body */
[data-theme=dark] .tm-card-body {
    border-top: 1px solid #2B2B40;
}
[data-theme=dark] .tm-card-category {
    color: #6D6D80;
}
[data-theme=dark] .tm-card-name {
    color: #FFFFFF;
}
[data-theme=dark] .tm-card-desc {
    color: #92929F;
}

/* Card Footer */
[data-theme=dark] .tm-card-meta {
    color: #565674;
}
[data-theme=dark] .tm-card-install-btn {
    background: rgba(99, 102, 241, 0.12);
    color: #a5b4fc;
    border: 1px solid rgba(99, 102, 241, 0.15);
}
[data-theme=dark] .tm-card-install-btn:hover {
    background: #4f46e5;
    color: #fff;
    border-color: #4f46e5;
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.4);
}

/* Empty State */
[data-theme=dark] .tm-empty i {
    color: #474761;
}
[data-theme=dark] .tm-empty h4 {
    color: #CDCDDE;
}
[data-theme=dark] .tm-empty p {
    color: #6D6D80;
}

/* ===== DARK PREVIEW MODAL ===== */
[data-theme=dark] .tm-modal-overlay {
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(6px);
}
[data-theme=dark] .tm-modal {
    background: #1e1e2d;
    border: 1px solid #2B2B40;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
}
[data-theme=dark] .tm-modal-title h3 {
    color: #FFFFFF;
}
[data-theme=dark] .tm-modal-title .tm-modal-cat {
    color: #6D6D80;
}
[data-theme=dark] .tm-modal-close {
    background: #2B2B40;
    color: #92929F;
}
[data-theme=dark] .tm-modal-close:hover {
    background: #323248;
    color: #FFFFFF;
}
[data-theme=dark] .tm-modal-desc {
    color: #92929F;
}

/* Dark Modal Stats */
[data-theme=dark] .tm-stat {
    background: #1b1b29;
    border-color: #2B2B40;
    color: #92929F;
}
[data-theme=dark] .tm-stat strong {
    color: #CDCDDE;
}

/* Dark Flow Preview */
[data-theme=dark] .tm-flow-preview {
    background: #1b1b29;
    border-color: #2B2B40;
}
[data-theme=dark] .tm-flow-title {
    color: #6D6D80;
}
[data-theme=dark] .tm-flow-node {
    background: #2B2B40;
    border-color: #323248;
    color: #CDCDDE;
}

/* Dark Modal Footer */
[data-theme=dark] .tm-modal-footer .tm-btn-outline {
    background: #2B2B40;
    border-color: #323248;
    color: #CDCDDE;
}
[data-theme=dark] .tm-modal-footer .tm-btn-outline:hover {
    background: #323248;
    border-color: #474761;
    color: #FFFFFF;
}

/* Dark Toasts */
[data-theme=dark] .tm-toast.error {
    background: rgba(185, 28, 28, 0.15);
    color: #fca5a5;
    border-color: rgba(185, 28, 28, 0.25);
}
[data-theme=dark] .tm-toast.success {
    background: rgba(4, 120, 87, 0.15);
    color: #6ee7b7;
    border-color: rgba(4, 120, 87, 0.25);
}
</style>

<div class="tm-wrapper">

    <!-- Hero Banner -->
    <div class="tm-hero">
        <h2>Biblioteca de modelos</h2>
        <p>Escolha fluxos prontos para publicar automações de WhatsApp mais rápido</p>
        <i class="fad fa-store tm-hero-icon"></i>
    </div>

    <!-- Top Bar -->
    <div class="tm-topbar">
        <div class="tm-search">
            <i class="fas fa-search"></i>
            <input type="text" id="tm-search-input" placeholder="Buscar modelos..." oninput="tmFilterTemplates()">
        </div>
        <div class="tm-actions">
            <a href="<?php echo base_url('bot-builder/create') ?>" class="tm-btn tm-btn-outline">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <label for="tm-import-input" class="tm-btn tm-btn-outline" style="cursor:pointer;">
                <i class="fad fa-file-import"></i> Importar JSON
            </label>
            <form id="tm-import-form" action="<?php echo base_url('bot-builder/templates/import') ?>" method="post" enctype="multipart/form-data" style="display:none;">
                <?php echo csrf_field() ?>
                <input type="file" id="tm-import-input" name="file" accept=".json" onchange="document.getElementById('tm-import-form').submit();">
            </form>
        </div>
    </div>

    <!-- Category Pills -->
    <div class="tm-categories">
        <a href="<?php echo base_url('bot-builder/templates') ?>" class="tm-cat-pill <?php echo empty($active_category) ? 'active' : '' ?>">
            <i class="fad fa-th-large"></i> Todos
        </a>
        <?php if(!empty($categories)): ?>
            <?php foreach($categories as $cat): ?>
                <a href="<?php echo base_url('bot-builder/templates/category/'.urlencode($cat->category)) ?>" 
                   class="tm-cat-pill <?php echo ($active_category === $cat->category) ? 'active' : '' ?>">
                    <?php echo esc($cat->category) ?>
                    <span class="count"><?php echo $cat->count ?></span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Template Grid -->
    <div class="tm-grid" id="tm-grid">
        <?php if(!empty($templates)): ?>
            <?php foreach($templates as $tpl): ?>
                <?php 
                    $icon = $tpl->icon ?? 'fad fa-robot';
                    $icon_color = $tpl->icon_color ?? '#4f46e5';
                ?>
                <div class="tm-card" data-id="<?php echo $tpl->id ?>" data-name="<?php echo esc($tpl->name) ?>" onclick="tmPreview(<?php echo $tpl->id ?>)">
                    
                    <!-- Preview Area -->
                    <div class="tm-card-preview" style="background: linear-gradient(135deg, <?php echo esc($icon_color) ?>08, <?php echo esc($icon_color) ?>18);">
                        <div class="tm-card-icon" style="background: <?php echo esc($icon_color) ?>;">
                            <i class="<?php echo esc($icon) ?>"></i>
                        </div>
                        <?php if($tpl->is_premium): ?>
                            <span class="tm-badge premium"><i class="fas fa-crown"></i> Premium</span>
                        <?php else: ?>
                            <span class="tm-badge free">Grátis</span>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div class="tm-card-body">
                        <div class="tm-card-category"><?php echo esc($tpl->category) ?></div>
                        <h4 class="tm-card-name"><?php echo esc($tpl->name) ?></h4>
                        <p class="tm-card-desc"><?php echo esc($tpl->description) ?></p>
                        <div class="tm-card-footer">
                            <div class="tm-card-meta">
                                <span><i class="fad fa-users"></i> <?php echo $tpl->use_count ?? 0 ?></span>
                            </div>
                            <button class="tm-card-install-btn" onclick="event.stopPropagation(); tmInstall(<?php echo $tpl->id ?>, this)">
                                <i class="fad fa-download"></i> Usar
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="tm-empty">
                <i class="fad fa-box-open"></i>
                <h4>Nenhum modelo encontrado</h4>
                <p>Volte depois para ver novidades ou crie um bot do zero.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Preview Modal -->
<div class="tm-modal-overlay" id="tm-preview-modal">
    <div class="tm-modal">
        <div class="tm-modal-header">
            <div class="tm-modal-icon" id="tm-modal-icon"><i class="fad fa-robot"></i></div>
            <div class="tm-modal-title">
                <h3 id="tm-modal-name">Modelo</h3>
                <div class="tm-modal-cat" id="tm-modal-category">Categoria</div>
            </div>
            <button class="tm-modal-close" onclick="tmClosePreview()"><i class="fas fa-times"></i></button>
        </div>
        <div class="tm-modal-body">
            <p class="tm-modal-desc" id="tm-modal-desc">Descrição</p>
            <div class="tm-modal-stats" id="tm-modal-stats"></div>
            <div class="tm-flow-preview" id="tm-flow-section">
                <div class="tm-flow-title">Blocos do fluxo</div>
                <div class="tm-flow-nodes" id="tm-flow-nodes"></div>
            </div>
        </div>
        <div class="tm-modal-footer">
            <button class="tm-btn tm-btn-outline" onclick="tmClosePreview()">Cancelar</button>
            <button class="tm-btn tm-btn-primary" id="tm-use-btn" onclick="tmInstallFromModal()">
                <span class="btn-text"><i class="fad fa-download"></i> Usar este modelo</span>
                <div class="spinner"></div>
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="tm-toast" id="tm-toast"></div>

<script>
var currentPreviewId = null;

// === SEARCH / FILTER ===
function tmFilterTemplates() {
    var query = document.getElementById('tm-search-input').value.toLowerCase();
    var cards = document.querySelectorAll('.tm-card');
    cards.forEach(function(card) {
        var name = (card.dataset.name || '').toLowerCase();
        var desc = card.querySelector('.tm-card-desc');
        var text = name + ' ' + (desc ? desc.textContent.toLowerCase() : '');
        card.style.display = text.includes(query) ? '' : 'none';
    });
}

// === PREVIEW MODAL ===
function tmPreview(id) {
    currentPreviewId = id;
    document.getElementById('tm-preview-modal').classList.add('active');
    
    // Fetch template details
    fetch('<?php echo base_url("bot-builder/templates/preview") ?>' + '/' + id, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if(data.status === 'success') {
            var t = data.template;
            document.getElementById('tm-modal-name').textContent = t.name;
            document.getElementById('tm-modal-category').textContent = t.category;
            document.getElementById('tm-modal-desc').textContent = t.description || 'Sem descrição.';
            
            // Icon
            var iconEl = document.getElementById('tm-modal-icon');
            iconEl.style.background = t.icon_color || '#4f46e5';
            iconEl.innerHTML = '<i class="' + (t.icon || 'fad fa-robot') + '"></i>';
            
            // Stats
            var statsHtml = '';
            statsHtml += '<div class="tm-stat"><i class="fad fa-cubes"></i> <strong>' + t.node_count + '</strong> blocos</div>';
            statsHtml += '<div class="tm-stat"><i class="fad fa-users"></i> <strong>' + (t.use_count || 0) + '</strong> usos</div>';
            if(t.is_premium) {
                statsHtml += '<div class="tm-stat"><i class="fas fa-crown" style="color:#f59e0b;"></i> Premium</div>';
            } else {
                statsHtml += '<div class="tm-stat"><i class="fas fa-check-circle" style="color:#10b981;"></i> Grátis</div>';
            }
            document.getElementById('tm-modal-stats').innerHTML = statsHtml;
            
            // Block types
            var blockTypeIcons = {
                'text': 'fad fa-comment-alt',
                'buttons': 'fad fa-grip-horizontal',
                'list': 'fad fa-list',
                'input': 'fad fa-keyboard',
                'input_text': 'fad fa-font',
                'input_email': 'fad fa-envelope',
                'input_phone': 'fad fa-phone',
                'input_number': 'fad fa-hashtag',
                'input_date': 'fad fa-calendar',
                'input_time': 'fad fa-clock',
                'input_url': 'fad fa-link',
                'input_rating': 'fad fa-star',
                'input_file': 'fad fa-file-upload',
                'input_payment': 'fad fa-credit-card',
                'input_pic_choice': 'fad fa-images',
                'input_cards': 'fad fa-id-card',
                'condition': 'fad fa-code-branch',
                'ai_reply': 'fad fa-sparkles',
                'delay': 'fad fa-hourglass-half',
                'webhook': 'fad fa-globe',
                'set_variable': 'fad fa-pen',
                'image': 'fad fa-image',
                'video': 'fad fa-video',
                'audio': 'fad fa-headphones',
                'embed': 'fad fa-code'
            };
            var nodesHtml = '';
            if(t.block_types && t.block_types.length) {
                t.block_types.forEach(function(bt) {
                    var icon = blockTypeIcons[bt] || 'fad fa-cube';
                    nodesHtml += '<div class="tm-flow-node"><i class="' + icon + '"></i> ' + bt.replace('_',' ') + '</div>';
                });
            }
            document.getElementById('tm-flow-nodes').innerHTML = nodesHtml || '<span style="color:#9ca3af; font-size:12px;">Sem blocos</span>';
        }
    })
    .catch(function() {
        tmShowToast('Falha ao carregar a prévia do modelo', 'error');
    });
}

function tmClosePreview() {
    document.getElementById('tm-preview-modal').classList.remove('active');
    currentPreviewId = null;
}

// Close modal on overlay click
document.getElementById('tm-preview-modal').addEventListener('click', function(e) {
    if(e.target === this) tmClosePreview();
});

document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') tmClosePreview();
});

// === INSTALL ===
function tmInstall(id, btn) {
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fad fa-spinner-third fa-spin"></i>';
    }
    
    // Use a regular form POST (avoids AJAX session issues with auth filter)
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo rtrim(base_url("bot-builder/templates/use"), "/") ?>/' + id;
    form.style.display = 'none';
    
    // Add CSRF token
    var csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '<?php echo csrf_token() ?>';
    csrfInput.value = '<?php echo csrf_hash() ?>';
    form.appendChild(csrfInput);
    
    document.body.appendChild(form);
    form.submit();
}

function tmInstallFromModal() {
    if(!currentPreviewId) return;
    var btn = document.getElementById('tm-use-btn');
    btn.classList.add('loading');
    tmInstall(currentPreviewId);
}

// === TOAST ===
function tmShowToast(msg, type) {
    var toast = document.getElementById('tm-toast');
    toast.className = 'tm-toast ' + type;
    toast.textContent = msg;
    setTimeout(function() { toast.classList.add('show'); }, 10);
    setTimeout(function() { toast.classList.remove('show'); }, 3500);
}

// Flash messages
<?php if(session()->getFlashdata('error')): ?>
    tmShowToast('<?php echo addslashes(session()->getFlashdata('error')) ?>', 'error');
<?php endif; ?>
<?php if(session()->getFlashdata('success')): ?>
    tmShowToast('<?php echo addslashes(session()->getFlashdata('success')) ?>', 'success');
<?php endif; ?>
</script>
