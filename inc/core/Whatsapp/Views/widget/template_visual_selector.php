<?php
$templates = isset($templates) && is_array($templates) ? $templates : [];
$context = preg_replace('/[^a-z0-9_\-]/i', '', (string)($context ?? 'whatsapp'));
$selector_id = 'wa-template-visual-' . $context . '-' . substr(md5(uniqid('', true)), 0, 8);
$modal_id = $selector_id . '-modal';
$current_type = (int) get_data($result, 'type');
$current_template = (int) get_data($result, 'template');
$categories = [
    'all' => ['label' => 'Todos', 'count' => count($templates)],
    'buttons' => ['label' => 'Botões', 'count' => 0],
    'list' => ['label' => 'Listas', 'count' => 0],
    'poll' => ['label' => 'Enquetes', 'count' => 0],
    'carousel' => ['label' => 'Carrossel', 'count' => 0],
];
$selected_template = null;

foreach ($templates as $template) {
    $category = $template['category'] ?? 'other';
    if (isset($categories[$category])) {
        $categories[$category]['count']++;
    }

    if ((int)($template['type'] ?? 0) === $current_type && (int)($template['id'] ?? 0) === $current_template) {
        $selected_template = $template;
    }
}

$selected_name = $selected_template['name'] ?? 'Nenhum modelo escolhido';
$selected_type = $selected_template['type_label'] ?? 'Escolha um modelo visualmente';
$selected_preview = $selected_template['preview'] ?? 'Abra a galeria para ver a prévia e selecionar o modelo correto.';
?>

<div class="wa-visual-template-selector" id="<?php _ec($selector_id) ?>" data-modal-id="<?php _ec($modal_id) ?>" data-default-name="Nenhum modelo escolhido" data-default-type="Escolha um modelo visualmente" data-default-preview="Abra a galeria para ver a prévia e selecionar o modelo correto.">
    <div class="wa-template-selector-card <?php _ec($selected_template ? 'has-selection' : '') ?>">
        <div class="wa-template-selector-main">
            <div class="wa-template-selector-orb">
                <i class="fad fa-layer-group"></i>
            </div>
            <div class="wa-template-selector-copy">
                <div class="wa-template-eyebrow"><?php _e('Galeria de modelos') ?></div>
                <div class="wa-template-selected-line">
                    <strong data-wa-selected-name><?php _ec($selected_name) ?></strong>
                    <span data-wa-selected-type><?php _ec($selected_type) ?></span>
                </div>
                <p data-wa-selected-preview><?php _ec($selected_preview) ?></p>
            </div>
        </div>
        <div class="wa-template-selector-actions">
            <button type="button" class="btn wa-template-open-gallery" data-bs-toggle="modal" data-bs-target="#<?php _ec($modal_id) ?>" data-toggle="modal" data-target="#<?php _ec($modal_id) ?>">
                <i class="fad fa-search me-2"></i><?php _e('Escolher modelo') ?>
            </button>
            <small><?php _e('A seleção marca automaticamente o tipo correto da mensagem.') ?></small>
        </div>
    </div>
</div>

<div class="modal fade wa-template-gallery-modal" id="<?php _ec($modal_id) ?>" tabindex="-1" aria-hidden="true" data-owner="#<?php _ec($selector_id) ?>">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content wa-template-gallery-shell">
            <div class="wa-template-gallery-header">
                <div>
                    <span class="wa-template-gallery-kicker"><?php _e('Escolha com segurança') ?></span>
                    <h4><?php _e('Galeria visual de modelos') ?></h4>
                    <p><?php _e('Veja a prévia, filtre por categoria e clique em Usar modelo. O envio continua usando a mesma configuração técnica de antes.') ?></p>
                </div>
                <button type="button" class="wa-template-gallery-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="<?php _e('Fechar') ?>">
                    <i class="fad fa-times"></i>
                </button>
            </div>

            <div class="wa-template-gallery-guide">
                <div><strong>1</strong><span><?php _e('Filtre pela categoria desejada.') ?></span></div>
                <div><strong>2</strong><span><?php _e('Confira a prévia do conteúdo.') ?></span></div>
                <div><strong>3</strong><span><?php _e('Clique em Usar modelo para aplicar no formulário.') ?></span></div>
            </div>

            <div class="wa-template-gallery-toolbar">
                <div class="wa-template-category-tabs">
                    <?php foreach ($categories as $category_key => $category): ?>
                        <button type="button" class="wa-template-category-tab <?php _ec($category_key === 'all' ? 'active' : '') ?>" data-wa-template-filter="<?php _ec($category_key) ?>">
                            <?php _ec($category['label']) ?>
                            <span><?php _ec((int)$category['count']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="wa-template-search-box">
                    <i class="fad fa-search"></i>
                    <input type="text" data-wa-template-search placeholder="<?php _e('Buscar por nome, tipo ou conteúdo...') ?>">
                </div>
            </div>

            <div class="wa-template-gallery-body">
                <?php if (!empty($templates)): ?>
                    <div class="wa-template-grid" data-wa-template-grid>
                        <?php foreach ($templates as $template): ?>
                            <?php
                                $is_selected = ((int)($template['type'] ?? 0) === $current_type && (int)($template['id'] ?? 0) === $current_template);
                                $meta_status = strtolower((string)($template['meta_status'] ?? ''));
                                $search_text = trim(($template['name'] ?? '') . ' ' . ($template['type_label'] ?? '') . ' ' . ($template['preview'] ?? '') . ' ' . ($template['details'] ?? ''));
                                $search_text = function_exists('mb_strtolower') ? mb_strtolower($search_text, 'UTF-8') : strtolower($search_text);
                            ?>
                            <article class="wa-template-card <?php _ec($is_selected ? 'is-selected' : '') ?>" data-template-card data-category="<?php _ec($template['category'] ?? 'other') ?>" data-template-id="<?php _ec((int)($template['id'] ?? 0)) ?>" data-template-type="<?php _ec((int)($template['type'] ?? 0)) ?>" data-input-name="<?php _ec($template['input_name'] ?? '') ?>" data-template-name="<?php _ec($template['name'] ?? '') ?>" data-template-label="<?php _ec($template['type_label'] ?? '') ?>" data-template-preview="<?php _ec($template['preview'] ?? '') ?>" data-search="<?php _ec($search_text) ?>">
                                <div class="wa-template-card-top">
                                    <div class="wa-template-card-icon"><i class="<?php _ec($template['icon'] ?? 'fad fa-layer-group') ?>"></i></div>
                                    <div class="wa-template-card-title">
                                        <span><?php _ec($template['type_label'] ?? 'Modelo') ?></span>
                                        <h5><?php _ec($template['name'] ?? 'Modelo sem nome') ?></h5>
                                    </div>
                                    <div class="wa-template-selected-check"><i class="fad fa-check"></i></div>
                                </div>
                                <div class="wa-template-whatsapp-preview">
                                    <div class="wa-template-bubble">
                                        <?php _ec($template['preview'] ?? '') ?>
                                        <div class="wa-template-bubble-time"><?php _ec(date('H:i')) ?> <i class="fad fa-check-double"></i></div>
                                    </div>
                                </div>
                                <div class="wa-template-card-meta">
                                    <span><i class="fad fa-info-circle"></i><?php _ec($template['details'] ?? 'Modelo de WhatsApp') ?></span>
                                    <?php if (!empty($template['changed_label'])): ?>
                                        <span><i class="fad fa-clock"></i><?php _ec($template['changed_label']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($template['meta_status_label'])): ?>
                                    <div class="wa-template-meta-status status-<?php _ec($meta_status) ?>">
                                        <i class="fad fa-cloud-check"></i><?php _ec($template['meta_status_label']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="wa-template-card-actions">
                                    <button type="button" class="wa-template-use-btn" data-wa-template-use>
                                        <i class="fad fa-check-circle"></i><?php _e('Usar modelo') ?>
                                    </button>
                                    <?php if (!empty($template['edit_url'])): ?>
                                        <a href="<?php _ec($template['edit_url']) ?>" class="wa-template-edit-btn" data-wa-template-edit>
                                            <i class="fad fa-edit"></i><?php _e('Editar') ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="wa-template-empty-filter d-none" data-wa-template-empty-filter>
                        <i class="fad fa-search"></i>
                        <strong><?php _e('Nenhum modelo encontrado') ?></strong>
                        <span><?php _e('Tente limpar a busca ou mudar a categoria.') ?></span>
                    </div>
                <?php else: ?>
                    <div class="wa-template-empty-state">
                        <i class="fad fa-layer-group"></i>
                        <strong><?php _e('Nenhum modelo disponível ainda') ?></strong>
                        <span><?php _e('Crie modelos de botões, listas, enquetes ou carrossel para aparecerem nesta galeria.') ?></span>
                        <div class="wa-template-empty-actions">
                            <a href="<?php _ec(base_url('whatsapp_button_template/index/update')) ?>" class="btn btn-primary btn-sm"><?php _e('Criar botão') ?></a>
                            <a href="<?php _ec(base_url('whatsapp_list_message_template/index/update')) ?>" class="btn btn-light btn-sm"><?php _e('Criar lista') ?></a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.wa-visual-template-enabled #wa_button,
.wa-visual-template-enabled #wa_list_message,
.wa-visual-template-enabled #wa_poll,
.wa-visual-template-enabled #wa_carousel {
    display: none !important;
}
.wa-visual-template-selector {
    margin: 0 0 18px;
}
.wa-template-selector-card {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: center;
    padding: 16px;
    border-radius: 18px;
    border: 1px solid rgba(28, 171, 108, 0.16);
    background:
        radial-gradient(circle at 8% 20%, rgba(28, 171, 108, 0.16), transparent 34%),
        linear-gradient(135deg, #ffffff 0%, #f7fbf8 58%, #eefaf3 100%);
    box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
    animation: waTemplateRise 0.38s ease both;
}
.wa-template-selector-main {
    display: flex;
    align-items: center;
    min-width: 0;
    gap: 14px;
}
.wa-template-selector-orb {
    width: 54px;
    height: 54px;
    flex: 0 0 54px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 18px;
    color: #0f8f59;
    background: linear-gradient(145deg, rgba(37, 211, 102, 0.16), rgba(13, 110, 253, 0.10));
    box-shadow: inset 0 0 0 1px rgba(28, 171, 108, 0.18);
    font-size: 22px;
}
.wa-template-selector-copy {
    min-width: 0;
}
.wa-template-eyebrow {
    color: #0f8f59;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.wa-template-selected-line {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.wa-template-selected-line strong {
    color: #13201a;
    font-size: 15px;
    max-width: 430px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.wa-template-selected-line span {
    padding: 4px 9px;
    border-radius: 999px;
    background: rgba(28, 171, 108, 0.10);
    color: #0f8f59;
    font-size: 11px;
    font-weight: 700;
}
.wa-template-selector-copy p {
    margin: 5px 0 0;
    color: #65736d;
    font-size: 12px;
    line-height: 1.45;
    max-width: 680px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.wa-template-selector-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    flex: 0 0 auto;
}
.wa-template-open-gallery {
    border: 0;
    color: #ffffff;
    font-weight: 800;
    border-radius: 999px;
    padding: 11px 18px;
    background: linear-gradient(135deg, #0f8f59, #0d6efd);
    box-shadow: 0 14px 28px rgba(15, 143, 89, 0.20);
}
.wa-template-open-gallery:hover,
.wa-template-open-gallery:focus {
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 18px 34px rgba(15, 143, 89, 0.26);
}
.wa-template-selector-actions small {
    color: #6f7f79;
    font-size: 11px;
}
.wa-template-gallery-modal {
    z-index: 1080 !important;
}
.wa-template-gallery-modal .modal-dialog {
    height: calc(100vh - 28px);
    margin-top: 14px;
    margin-bottom: 14px;
}
.wa-template-gallery-shell {
    border: 0;
    border-radius: 24px;
    overflow: hidden;
    background: #f6faf8;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.22);
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 28px);
}
.wa-template-gallery-header {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 24px 26px;
    color: #ffffff;
    background:
        radial-gradient(circle at 12% 20%, rgba(255,255,255,.22), transparent 28%),
        linear-gradient(135deg, #0f8f59 0%, #128f7a 48%, #0d6efd 100%);
    flex: 0 0 auto;
}
.wa-template-gallery-kicker {
    display: inline-flex;
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.16);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: 9px;
}
.wa-template-gallery-header h4 {
    color: #ffffff;
    margin: 0;
    font-size: 24px;
    font-weight: 850;
}
.wa-template-gallery-header p {
    color: rgba(255,255,255,.82);
    margin: 7px 0 0;
    max-width: 760px;
    font-size: 13px;
}
.wa-template-gallery-close {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 14px;
    color: #ffffff;
    background: rgba(255,255,255,.16);
    transition: transform .2s ease, background .2s ease;
}
.wa-template-gallery-close:hover {
    transform: rotate(90deg);
    background: rgba(255,255,255,.24);
}
.wa-template-gallery-guide {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    padding: 14px 18px 0;
    flex: 0 0 auto;
}
.wa-template-gallery-guide div {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 14px;
    background: #ffffff;
    color: #5f6b66;
    font-size: 12px;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
}
.wa-template-gallery-guide strong {
    width: 24px;
    height: 24px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    background: rgba(28, 171, 108, 0.12);
    color: #0f8f59;
}
.wa-template-gallery-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 18px;
    flex: 0 0 auto;
}
.wa-template-category-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.wa-template-category-tab {
    border: 1px solid rgba(15, 143, 89, 0.12);
    background: #ffffff;
    color: #45524d;
    border-radius: 999px;
    padding: 9px 12px;
    font-size: 12px;
    font-weight: 800;
    transition: all .2s ease;
}
.wa-template-category-tab span {
    display: inline-flex;
    min-width: 20px;
    justify-content: center;
    margin-left: 6px;
    padding: 2px 6px;
    border-radius: 999px;
    background: rgba(15, 143, 89, .10);
    color: #0f8f59;
}
.wa-template-category-tab.active,
.wa-template-category-tab:hover {
    background: #0f8f59;
    border-color: #0f8f59;
    color: #ffffff;
    box-shadow: 0 12px 24px rgba(15,143,89,.18);
}
.wa-template-category-tab.active span,
.wa-template-category-tab:hover span {
    background: rgba(255,255,255,.18);
    color: #ffffff;
}
.wa-template-search-box {
    min-width: 300px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 13px;
    border-radius: 16px;
    background: #ffffff;
    border: 1px solid rgba(15, 143, 89, 0.12);
    box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
}
.wa-template-search-box input {
    width: 100%;
    height: 42px;
    border: 0;
    outline: 0;
    background: transparent;
    font-size: 13px;
    color: #1f2d27;
}
.wa-template-gallery-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 0 18px 20px;
    scrollbar-width: thin;
    scrollbar-color: rgba(15, 143, 89, .38) rgba(15, 143, 89, .08);
}
.wa-template-gallery-body::-webkit-scrollbar {
    width: 10px;
}
.wa-template-gallery-body::-webkit-scrollbar-track {
    background: rgba(15, 143, 89, .08);
    border-radius: 999px;
}
.wa-template-gallery-body::-webkit-scrollbar-thumb {
    background: rgba(15, 143, 89, .38);
    border: 2px solid #f6faf8;
    border-radius: 999px;
}
.wa-template-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}
.wa-template-card {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px;
    border-radius: 18px;
    background: #ffffff;
    border: 1px solid rgba(16, 24, 40, .08);
    box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    animation: waTemplateCardIn .32s ease both;
}
.wa-template-card:hover,
.wa-template-card.is-selected {
    transform: translateY(-2px);
    border-color: rgba(15, 143, 89, .35);
    box-shadow: 0 18px 36px rgba(15, 143, 89, .12);
}
.wa-template-card-top {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.wa-template-card-icon {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 42px;
    border-radius: 14px;
    color: #0f8f59;
    background: linear-gradient(145deg, rgba(37, 211, 102, .14), rgba(13, 110, 253, .08));
    font-size: 18px;
}
.wa-template-card-title {
    flex: 1 1 auto;
    min-width: 0;
}
.wa-template-card-title span {
    color: #0f8f59;
    font-size: 11px;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.wa-template-card-title h5 {
    margin: 2px 0 0;
    color: #18231f;
    font-size: 14px;
    font-weight: 850;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.wa-template-selected-check {
    width: 25px;
    height: 25px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: rgba(15,143,89,.10);
    color: #0f8f59;
    opacity: 0;
    transform: scale(.82);
    transition: all .2s ease;
}
.wa-template-card.is-selected .wa-template-selected-check {
    opacity: 1;
    transform: scale(1);
}
.wa-template-whatsapp-preview {
    min-height: 118px;
    padding: 12px;
    border-radius: 14px;
    background-color: #efe7dd;
    background-image: radial-gradient(rgba(16,24,40,.055) 1px, transparent 1px);
    background-size: 12px 12px;
}
.wa-template-bubble {
    position: relative;
    max-width: 96%;
    min-height: 74px;
    padding: 10px 10px 18px;
    border-radius: 12px 12px 12px 3px;
    background: #ffffff;
    color: #26332e;
    font-size: 12px;
    line-height: 1.45;
    white-space: pre-line;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
}
.wa-template-bubble-time {
    position: absolute;
    right: 9px;
    bottom: 5px;
    color: #8a9992;
    font-size: 10px;
}
.wa-template-bubble-time i {
    color: #36a6e2;
}
.wa-template-card-meta {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
}
.wa-template-card-meta span,
.wa-template-meta-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 8px;
    border-radius: 999px;
    background: #f5f8f6;
    color: #62716b;
    font-size: 11px;
    font-weight: 700;
}
.wa-template-meta-status.status-approved {
    background: rgba(25, 135, 84, .10);
    color: #198754;
}
.wa-template-meta-status.status-pending,
.wa-template-meta-status.status-paused {
    background: rgba(255, 193, 7, .16);
    color: #9a6a00;
}
.wa-template-meta-status.status-rejected {
    background: rgba(220, 53, 69, .12);
    color: #dc3545;
}
.wa-template-card-actions {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 8px;
    align-items: center;
}
.wa-template-use-btn,
.wa-template-edit-btn {
    width: 100%;
    border: 0;
    border-radius: 13px;
    padding: 10px 12px;
    font-weight: 850;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: all .2s ease;
    text-decoration: none;
}
.wa-template-use-btn {
    color: #0f8f59;
    background: rgba(15,143,89,.10);
}
.wa-template-edit-btn {
    width: auto;
    min-width: 92px;
    color: #42526b;
    background: #f3f6f8;
    border: 1px solid rgba(16, 24, 40, .06);
}
.wa-template-use-btn:hover,
.wa-template-card.is-selected .wa-template-use-btn {
    color: #ffffff;
    background: linear-gradient(135deg, #0f8f59, #0d6efd);
    box-shadow: 0 12px 24px rgba(15,143,89,.18);
}
.wa-template-edit-btn:hover,
.wa-template-edit-btn:focus {
    color: #0d6efd;
    background: #ffffff;
    border-color: rgba(13, 110, 253, .18);
    box-shadow: 0 10px 20px rgba(13, 110, 253, .10);
}
.wa-template-empty-filter,
.wa-template-empty-state {
    min-height: 260px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 28px;
    border-radius: 18px;
    background: #ffffff;
    color: #66756f;
    text-align: center;
}
.wa-template-empty-filter i,
.wa-template-empty-state i {
    font-size: 42px;
    color: rgba(15,143,89,.34);
}
.wa-template-empty-filter strong,
.wa-template-empty-state strong {
    color: #18231f;
    font-size: 16px;
}
.wa-template-empty-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}
@keyframes waTemplateRise {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes waTemplateCardIn {
    from { opacity: 0; transform: translateY(8px) scale(.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@media (max-width: 991px) {
    .wa-template-gallery-modal .modal-dialog {
        width: calc(100% - 16px);
        max-width: none;
        height: calc(100vh - 16px);
        margin: 8px auto;
    }
    .wa-template-gallery-shell {
        max-height: calc(100vh - 16px);
        border-radius: 18px;
    }
    .wa-template-gallery-header {
        padding: 18px;
    }
    .wa-template-gallery-header h4 {
        font-size: 20px;
    }
    .wa-template-gallery-header p {
        font-size: 12px;
    }
    .wa-template-gallery-guide {
        padding: 10px 12px 0;
    }
    .wa-template-gallery-toolbar {
        padding: 12px;
    }
    .wa-template-gallery-body {
        padding: 0 12px 14px;
    }
    .wa-template-card-actions {
        grid-template-columns: 1fr;
    }
    .wa-template-edit-btn {
        width: 100%;
    }
    .wa-template-selector-card,
    .wa-template-gallery-toolbar {
        align-items: stretch;
        flex-direction: column;
    }
    .wa-template-selector-actions {
        align-items: stretch;
    }
    .wa-template-search-box {
        min-width: 0;
        width: 100%;
    }
    .wa-template-grid,
    .wa-template-gallery-guide {
        grid-template-columns: 1fr;
    }
}
@media (min-width: 992px) and (max-width: 1300px) {
    .wa-template-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>

<script>
(function() {
    function hideModal($modal) {
        var modalElement = $modal.get(0);
        if (window.bootstrap && bootstrap.Modal && modalElement) {
            var instance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
            instance.hide();
            return;
        }

        if (typeof $modal.modal === 'function') {
            $modal.modal('hide');
        }
    }

    function initVisualTemplateSelector() {
        $('.wa-visual-template-selector').each(function() {
            var $root = $(this);
            if ($root.data('visualTemplateReady')) return;
            $root.data('visualTemplateReady', true);

            var modalId = $root.data('modal-id');
            var $modal = $('#' + modalId);
            var $scope = $root.closest('.card-body');
            if ($scope.length) $scope.addClass('wa-visual-template-enabled');

            if ($modal.length && !$modal.parent().is('body')) {
                $modal.appendTo('body');
            }

            function normalize(value) {
                return String(value || '').toLowerCase();
            }

            function refreshFilter() {
                var activeCategory = $modal.find('.wa-template-category-tab.active').data('wa-template-filter') || 'all';
                var search = normalize($modal.find('[data-wa-template-search]').val());
                var visible = 0;

                $modal.find('[data-template-card]').each(function() {
                    var $card = $(this);
                    var category = $card.data('category');
                    var haystack = normalize($card.data('search'));
                    var categoryOk = activeCategory === 'all' || category === activeCategory;
                    var searchOk = search === '' || haystack.indexOf(search) !== -1;
                    var show = categoryOk && searchOk;
                    $card.toggleClass('d-none', !show);
                    if (show) visible++;
                });

                $modal.find('[data-wa-template-empty-filter]').toggleClass('d-none', visible > 0);
            }

            function applyTemplate($card) {
                var templateId = String($card.data('template-id') || '');
                var templateType = String($card.data('template-type') || '');
                var inputName = String($card.data('input-name') || '');
                var tabMap = {
                    '2': 'type_button',
                    '3': 'type_template',
                    '4': 'type_poll',
                    '5': 'type_carousel'
                };
                var tabInputId = tabMap[templateType];

                if (tabInputId) {
                    var $typeInput = $('#' + tabInputId);
                    var $typeLabel = $('label[for="' + tabInputId + '"]');
                    $typeInput.prop('checked', true).trigger('change');
                    if ($typeLabel.length) {
                        $typeLabel.trigger('click');
                    }
                }

                if (inputName && templateId) {
                    $('input[name="' + inputName + '"][value="' + templateId + '"]').prop('checked', true).trigger('change');
                }

                $root.find('[data-wa-selected-name]').text($card.data('template-name') || 'Modelo selecionado');
                $root.find('[data-wa-selected-type]').text($card.data('template-label') || 'Modelo');
                $root.find('[data-wa-selected-preview]').text($card.data('template-preview') || 'Modelo aplicado no formulário.');
                $root.find('.wa-template-selector-card').addClass('has-selection');
                $modal.find('[data-template-card]').removeClass('is-selected');
                $card.addClass('is-selected');

                var $button = $card.find('[data-wa-template-use]');
                var originalHtml = $button.html();
                $button.html('<i class="fas fa-check"></i> Modelo aplicado');
                setTimeout(function() {
                    $button.html(originalHtml);
                    hideModal($modal);
                }, 450);
            }

            function resetSummaryIfNativeMessage() {
                var currentType = String($('input[name="type"]:checked').val() || '');
                if (currentType !== '1' && currentType !== '7' && currentType !== '6') return;

                $root.find('[data-wa-selected-name]').text($root.data('default-name'));
                $root.find('[data-wa-selected-type]').text($root.data('default-type'));
                $root.find('[data-wa-selected-preview]').text($root.data('default-preview'));
                $root.find('.wa-template-selector-card').removeClass('has-selection');
                $modal.find('[data-template-card]').removeClass('is-selected');
            }

            $modal.on('click', '.wa-template-category-tab', function() {
                $modal.find('.wa-template-category-tab').removeClass('active');
                $(this).addClass('active');
                refreshFilter();
            });

            $modal.on('input', '[data-wa-template-search]', refreshFilter);

            $modal.on('click', '[data-wa-template-use]', function(e) {
                e.preventDefault();
                applyTemplate($(this).closest('[data-template-card]'));
            });

            $modal.on('click', '[data-wa-template-edit]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var editUrl = $(this).attr('href');
                if (!editUrl) return;
                var joiner = editUrl.indexOf('?') === -1 ? '?' : '&';
                window.location.href = editUrl + joiner + 'wa_return=' + encodeURIComponent(window.location.href);
            });

            $modal.on('click', '[data-template-card]', function(e) {
                if ($(e.target).closest('[data-wa-template-use], [data-wa-template-edit]').length) return;
                applyTemplate($(this));
            });

            $(document).on('change.' + modalId, 'input[name="type"]', resetSummaryIfNativeMessage);
        });
    }

    $(document).ready(initVisualTemplateSelector);
    $(document).ajaxComplete(function() {
        setTimeout(initVisualTemplateSelector, 60);
    });
})();
</script>
