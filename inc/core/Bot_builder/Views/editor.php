<?php
$csrf = csrf_token();
$hash = csrf_hash();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de bot - <?php echo esc($bot->name) ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo base_url('inc/core/Bot_builder/Assets/css/bot_builder.css') ?>?v=<?php echo time() ?>">
    <style>
        #content { padding: 0 !important; margin: 0 !important; }
        .wrapper { overflow: hidden !important; }
    </style>
</head>
<body>

<div class="bot-builder-wrapper">
    <!-- LEFT SIDEBAR -->
    <div class="sidebar-blocks">
        <div class="sidebar-header">
            <h3><i class="fad fa-robot"></i> <span>Blocos</span></h3>
        </div>
        <div class="sidebar-search">
            <input type="text" id="block-search" placeholder="Buscar blocos..." oninput="filterBlocks()">
        </div>
        <div class="blocks-list" id="blocks-list">
            <!-- Bubbles -->
            <div class="block-category">Mensagens</div>
            <div class="block-item" draggable="true" data-type="text">
                <div class="block-icon bi-blue"><i class="fas fa-comment-alt"></i></div><span>Texto</span>
            </div>
            <div class="block-item" draggable="true" data-type="image">
                <div class="block-icon bi-indigo"><i class="fas fa-image"></i></div><span>Imagem</span>
            </div>
            <div class="block-item" draggable="true" data-type="video">
                <div class="block-icon bi-pink"><i class="fas fa-video"></i></div><span>Video</span>
            </div>
            <div class="block-item" draggable="true" data-type="embed">
                <div class="block-icon bi-gray"><i class="fas fa-laptop-code"></i></div><span>Embed</span>
            </div>
            <div class="block-item" draggable="true" data-type="audio">
                <div class="block-icon bi-cyan"><i class="fas fa-headphones"></i></div><span>Audio</span>
            </div>

            <!-- Inputs (2-column grid like Typebot) -->
            <div class="block-category">Entradas</div>
            <div class="inputs-grid">
            <div class="block-item" draggable="true" data-type="input_text">
                <div class="block-icon bi-orange"><i class="fas fa-font"></i></div><span>Texto</span>
            </div>
            <div class="block-item" draggable="true" data-type="input_number">
                <div class="block-icon bi-orange"><i class="fas fa-hashtag"></i></div><span>Número</span>
            </div>
            <div class="block-item" draggable="true" data-type="input_email">
                <div class="block-icon bi-orange"><i class="fas fa-envelope"></i></div><span>Email</span>
            </div>
            <div class="block-item" draggable="true" data-type="input_website">
                <div class="block-icon bi-orange"><i class="fas fa-link"></i></div><span>Site</span>
            </div>
            <div class="block-item" draggable="true" data-type="input_date">
                <div class="block-icon bi-orange"><i class="fas fa-calendar-alt"></i></div><span>Data</span>
            </div>
            <div class="block-item" draggable="true" data-type="input_time">
                <div class="block-icon bi-orange"><i class="fas fa-clock"></i></div><span>Hora</span>
            </div>
            <div class="block-item" draggable="true" data-type="input_phone">
                <div class="block-icon bi-orange"><i class="fas fa-phone-alt"></i></div><span>Telefone</span>
            </div>
            <div class="block-item" draggable="true" data-type="buttons">
                <div class="block-icon bi-orange"><i class="fas fa-check-square"></i></div><span>Botões</span>
            </div>
            <div class="block-item" draggable="true" data-type="pic_choice">
                <div class="block-icon bi-orange"><i class="fas fa-images"></i></div><span>Escolha com imagem</span>
            </div>
            <div class="block-item" draggable="true" data-type="payment">
                <div class="block-icon bi-orange"><i class="fas fa-credit-card"></i></div><span>Pagamento</span>
            </div>
            <div class="block-item" draggable="true" data-type="rating">
                <div class="block-icon bi-orange"><i class="fas fa-star"></i></div><span>Avaliação</span>
            </div>
            <div class="block-item" draggable="true" data-type="file_upload">
                <div class="block-icon bi-orange"><i class="fas fa-cloud-upload-alt"></i></div><span>Arquivo <small style="background:#fde68a;color:#b45309;padding:0 3px;border-radius:3px;font-size:8px;font-weight:700;margin-left:1px;">🔒</small></span>
            </div>
            <div class="block-item" draggable="true" data-type="cards">
                <div class="block-icon bi-orange"><i class="fas fa-id-card"></i></div><span>Cards</span>
            </div>
            </div>

            <!-- Logic -->
            <div class="block-category">Lógica</div>
            <div class="block-item" draggable="true" data-type="set_variable">
                <div class="block-icon bi-teal"><i class="fas fa-pen-fancy"></i></div><span>Definir variável</span>
            </div>
            <div class="block-item" draggable="true" data-type="condition">
                <div class="block-icon bi-gray"><i class="fas fa-filter"></i></div><span>Condição</span>
            </div>
            <div class="block-item" draggable="true" data-type="redirect">
                <div class="block-icon bi-purple"><i class="fas fa-external-link-alt"></i></div><span>Redirecionar</span>
            </div>
            <div class="block-item" draggable="true" data-type="script">
                <div class="block-icon bi-indigo"><i class="fas fa-code"></i></div><span>Script</span>
            </div>
            <div class="block-item" draggable="true" data-type="typebot">
                <div class="block-icon bi-blue"><i class="fas fa-robot"></i></div><span>Typebot</span>
            </div>
            <div class="block-item" draggable="true" data-type="delay">
                <div class="block-icon bi-orange"><i class="fas fa-hourglass-half"></i></div><span>Esperar</span>
            </div>
            <div class="block-item" draggable="true" data-type="ab_test">
                <div class="block-icon bi-pink"><i class="fas fa-random"></i></div><span>AB Test</span>
            </div>
            <div class="block-item" draggable="true" data-type="webhook">
                <div class="block-icon bi-teal"><i class="fas fa-globe"></i></div><span>Webhook</span>
            </div>
            <div class="block-item" draggable="true" data-type="jump">
                <div class="block-icon bi-purple"><i class="fas fa-share"></i></div><span>Pular</span>
            </div>
            <div class="block-item" draggable="true" data-type="return">
                <div class="block-icon bi-gray"><i class="fas fa-undo-alt"></i></div><span>Retornar</span>
            </div>

            <!-- Events -->
            <div class="block-category">Eventos</div>
            <div class="block-item" draggable="true" data-type="start">
                <div class="block-icon bi-green"><i class="fas fa-flag"></i></div><span>Início</span>
            </div>
            <div class="block-item" draggable="true" data-type="command">
                <div class="block-icon bi-indigo"><i class="fas fa-terminal"></i></div><span>Comando</span>
            </div>
            <div class="block-item" draggable="true" data-type="reply">
                <div class="block-icon bi-blue"><i class="fas fa-reply"></i></div><span>Resposta</span>
            </div>
            <div class="block-item" draggable="true" data-type="invalid">
                <div class="block-icon bi-red"><i class="fas fa-times"></i></div><span>Inválido</span>
            </div>
            <div class="block-item" draggable="true" data-type="end">
                <div class="block-icon bi-red"><i class="fas fa-stop"></i></div><span>Fim</span>
            </div>

            <!-- AI & Integrations -->
            <div class="block-category">IA e integrações</div>
            <div class="block-item" draggable="true" data-type="ai_reply">
                <div class="block-icon bi-purple"><i class="fas fa-sparkles"></i></div><span>Resposta com IA</span>
            </div>
            <div class="block-item" draggable="true" data-type="list">
                <div class="block-icon bi-emerald"><i class="fas fa-list"></i></div><span>Menu de lista</span>
            </div>

            <!-- Integrations (2-column grid) -->
            <div class="block-category">Integrações</div>
            <div class="integrations-grid">
                <div class="block-item intg-item" draggable="true" data-type="intg_sheets">
                    <div class="block-icon-intg" style="background:#34a853;"><i class="fas fa-table"></i></div><span>Sheets</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_analytics">
                    <div class="block-icon-intg" style="background:#e37400;"><i class="fas fa-chart-bar"></i></div><span>Analytics</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_http">
                    <div class="block-icon-intg" style="background:#6b7280;"><i class="fas fa-bolt"></i></div><span>HTTP request</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_email">
                    <div class="block-icon-intg" style="background:#0ea5e9;"><i class="fas fa-paper-plane"></i></div><span>Email</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_zapier">
                    <div class="block-icon-intg" style="background:#ff4a00;"><i class="fas fa-zap"></i></div><span>Zapier</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_make">
                    <div class="block-icon-intg" style="background:#6d28d9;"><i class="fas fa-cogs"></i></div><span>Make.com</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_pabbly">
                    <div class="block-icon-intg" style="background:#ff6d2e;"><i class="fas fa-link"></i></div><span>Pabbly</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_chatwoot">
                    <div class="block-icon-intg" style="background:#1f93ff;"><i class="fas fa-comments"></i></div><span>Chatwoot</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_pixel">
                    <div class="block-icon-intg" style="background:#1877f2;"><i class="fas fa-bullseye"></i></div><span>Pixel</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_openai">
                    <div class="block-icon-intg" style="background:#10a37f;"><i class="fas fa-brain"></i></div><span>OpenAI</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_calcom">
                    <div class="block-icon-intg" style="background:#292929;"><i class="fas fa-calendar-check"></i></div><span>Cal.com</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_chatnode">
                    <div class="block-icon-intg" style="background:#4f46e5;"><i class="fas fa-robot"></i></div><span>ChatNode</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_qrcode">
                    <div class="block-icon-intg" style="background:#374151;"><i class="fas fa-qrcode"></i></div><span>QR code</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_dify">
                    <div class="block-icon-intg" style="background:#7c3aed;"><i class="fas fa-magic"></i></div><span>Dify.AI</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_mistral">
                    <div class="block-icon-intg" style="background:#f97316;"><i class="fas fa-wind"></i></div><span>Mistral</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_elevenlabs">
                    <div class="block-icon-intg" style="background:#000000;"><i class="fas fa-volume-up"></i></div><span>ElevenLabs</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_anthropic">
                    <div class="block-icon-intg" style="background:#d97706;"><i class="fas fa-feather-alt"></i></div><span>Anthropic</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_together">
                    <div class="block-icon-intg" style="background:#6366f1;"><i class="fas fa-users"></i></div><span>Together</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_openrouter">
                    <div class="block-icon-intg" style="background:#0ea5e9;"><i class="fas fa-route"></i></div><span>OpenRouter</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_nocodb">
                    <div class="block-icon-intg" style="background:#4f46e5;"><i class="fas fa-database"></i></div><span>NocoDB</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_segment">
                    <div class="block-icon-intg" style="background:#52bd94;"><i class="fas fa-chart-pie"></i></div><span>Segment</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_groq">
                    <div class="block-icon-intg" style="background:#f97316;"><i class="fas fa-microchip"></i></div><span>Groq</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_zendesk">
                    <div class="block-icon-intg" style="background:#03363d;"><i class="fas fa-headset"></i></div><span>Zendesk</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_posthog">
                    <div class="block-icon-intg" style="background:#f9a825;"><i class="fas fa-chart-line"></i></div><span>Posthog</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_perplexity">
                    <div class="block-icon-intg" style="background:#20808d;"><i class="fas fa-search"></i></div><span>Perplexity</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_deepseek">
                    <div class="block-icon-intg" style="background:#4f46e5;"><i class="fas fa-atom"></i></div><span>DeepSeek</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_blink">
                    <div class="block-icon-intg" style="background:#22c55e;"><i class="fas fa-bolt"></i></div><span>Blink</span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_gmail">
                    <div class="block-icon-intg" style="background:#ea4335;"><i class="fas fa-envelope-open"></i></div><span>Gmail <small class="intg-beta">Beta</small></span>
                </div>
                <div class="block-item intg-item" draggable="true" data-type="intg_woocommerce">
                    <div class="block-icon-intg" style="background:#96588a;"><i class="fab fa-wordpress"></i></div><span>WooCommerce</span>
                </div>
            </div>
        </div>
    </div>

    <!-- CENTER CANVAS -->
    <div class="canvas-wrapper">
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="top-bar-left">
                <a href="<?php echo base_url('bot-builder') ?>" title="Voltar"><i class="fas fa-arrow-left"></i></a>
                <div class="bot-info">
                    <span class="bot-name"><?php echo esc($bot->name) ?></span>
                    <span class="bot-status <?php echo $bot->status ? 'published' : 'draft' ?>">
                        <?php echo $bot->status ? 'Publicado' : 'Rascunho' ?>
                    </span>
                </div>
            </div>

            <div class="top-bar-center">
                <button class="tb-tool-btn" id="btn-undo" onclick="undo()" title="Desfazer (⌘Z)" disabled><i class="fas fa-undo"></i></button>
                <button class="tb-tool-btn" id="btn-redo" onclick="redo()" title="Refazer (⌘⇧Z)" disabled><i class="fas fa-redo"></i></button>
            </div>

            <div class="top-bar-right">
                <div id="save-status" class="saved"><i class="fas fa-check-circle"></i> Salvo</div>
                <button class="tb-btn" onclick="exportFlow()" title="Exportar JSON"><i class="fas fa-download"></i></button>
                <button class="tb-btn" onclick="toggleValidationPanel()"><i class="fas fa-check-double"></i> Validar</button>
                <button class="tb-btn" onclick="togglePreview()"><i class="fas fa-play"></i> Prévia</button>
                <button class="tb-btn tb-btn-success" onclick="openPublishModal()"><i class="fas fa-rocket"></i> Publicar</button>
            </div>
        </div>

        <!-- Canvas -->
        <div id="bot-canvas-container">
            <div id="bot-canvas">
                <svg id="connections" width="100%" height="100%"></svg>
            </div>
        </div>

        <!-- Zoom Controls -->
        <div class="canvas-controls">
            <button class="control-btn" onclick="zoomCanvas(-0.1)" title="Diminuir zoom"><i class="fas fa-minus"></i></button>
            <span class="zoom-level" id="zoom-level">100%</span>
            <button class="control-btn" onclick="resetCanvas()" title="Redefinir"><i class="fas fa-expand"></i></button>
            <button class="control-btn" onclick="zoomCanvas(0.1)" title="Aumentar zoom"><i class="fas fa-plus"></i></button>
        </div>

        <!-- Validation Panel -->
        <div class="flow-validation-panel hidden" id="flow-validation-panel">
            <div class="flow-validation-header">
                <span><i class="fas fa-check-double"></i> Validação do fluxo</span>
                <button onclick="toggleValidationPanel()"><i class="fas fa-times"></i></button>
            </div>
            <div class="flow-validation-body" id="flow-validation-body">
                <div class="flow-validation-empty">
                    <i class="fas fa-route"></i>
                    <strong>Clique em Validar</strong>
                    <span>Vamos analisar blocos, conexões e pontos que merecem atenção.</span>
                </div>
            </div>
        </div>

        <!-- Preview Panel -->
        <div class="preview-panel hidden" id="preview-panel">
            <div class="preview-header">
                <span><i class="fab fa-whatsapp"></i> Simulador WhatsApp</span>
                <button onclick="togglePreview()"><i class="fas fa-times"></i></button>
            </div>
            <div class="preview-body" id="preview-chat">
                <div class="chat-date">Hoje</div>
            </div>
            <div class="sim-vars-panel" id="sim-vars-panel">
                <div class="sim-vars-header">
                    <span><i class="fas fa-database"></i> Variáveis da sessão</span>
                    <small id="sim-vars-count">0</small>
                </div>
                <div class="sim-vars-list" id="sim-vars-list">
                    <div class="sim-vars-empty">Nenhuma variável capturada ainda.</div>
                </div>
                <div class="sim-history-header">
                    <span><i class="fas fa-shoe-prints"></i> Caminho percorrido</span>
                    <small id="sim-history-count">0</small>
                </div>
                <div class="sim-history-list" id="sim-history-list">
                    <div class="sim-vars-empty">Nenhum bloco percorrido ainda.</div>
                </div>
            </div>
            <div class="preview-footer">
                <input type="text" id="preview-input" placeholder="Digite uma mensagem..." onkeypress="handlePreviewInput(event)">
                <button onclick="sendPreviewMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <!-- RIGHT INSPECTOR -->
    <div class="sidebar-inspector hidden" id="inspector">
        <div class="inspector-header">
            <h4 id="inspector-title">Configuração</h4>
            <div class="close-inspector" onclick="closeInspector()"><i class="fas fa-times"></i></div>
        </div>
        <div class="inspector-content" id="inspector-form"></div>
    </div>
</div>

<!-- PUBLISH & INTEGRATION MODAL -->
<div class="publish-modal-overlay" id="publish-modal" style="display:none;">
<div class="publish-modal">
    <div class="pm-header">
        <div class="pm-header-left">
            <div class="pm-header-icon"><i class="fas fa-rocket"></i></div>
            <div>
                <h3>Publicar e conectar WhatsApp</h3>
                <p>Salve, publique e vincule este bot aos seus números WhatsApp</p>
            </div>
        </div>
        <button class="pm-close" onclick="closePublishModal()"><i class="fas fa-times"></i></button>
    </div>

    <div class="pm-body">
        <!-- Status Section -->
        <div class="pm-section">
            <div class="pm-status-bar">
                <div class="pm-status-item">
                    <i class="fas fa-circle-check" style="color:#10b981;"></i>
                    <span>Fluxo criado</span>
                </div>
                <div class="pm-status-divider"></div>
                <div class="pm-status-item" id="pm-save-status">
                    <i class="fas fa-circle" style="color:#94a3b8;"></i>
                    <span>Salvar e publicar</span>
                </div>
                <div class="pm-status-divider"></div>
                <div class="pm-status-item" id="pm-connect-status">
                    <i class="fas fa-circle" style="color:#94a3b8;"></i>
                    <span>Conectar WhatsApp</span>
                </div>
            </div>
        </div>

        <!-- Quick Publish -->
        <div class="pm-section">
            <div class="pm-card pm-card-publish">
                <div class="pm-card-icon" style="background:linear-gradient(135deg,#4f46e5,#6366f1);">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="pm-card-content">
                    <h4>Publicar bot</h4>
                    <p>Salva o fluxo atual e coloca o bot no ar. Ele responderá pelas palavras-chave configuradas.</p>
                </div>
                <button class="pm-btn pm-btn-primary" id="pm-publish-btn" onclick="publishBot()">
                    <i class="fas fa-rocket"></i> Publicar agora
                </button>
            </div>
        </div>

        <!-- Bot Settings: Enable/Disable & Keywords -->
        <div class="pm-section">
            <div class="pm-section-header">
                <h4><i class="fas fa-sliders-h" style="color:#6366f1;"></i> Configurações do bot</h4>
            </div>

            <!-- Bot Enabled Toggle -->
            <div class="pm-setting-row">
                <div class="pm-setting-info">
                    <div class="pm-setting-label">Bot ativado</div>
                    <div class="pm-setting-desc">Quando estiver desligado, o bot não responderá mensagens mesmo publicado.</div>
                </div>
                <label class="pm-toggle-switch">
                    <input type="checkbox" id="pm-bot-enabled" <?php echo (!isset($bot->bot_enabled) || $bot->bot_enabled) ? 'checked' : '' ?>>
                    <span class="pm-toggle-slider"></span>
                </label>
            </div>

            <!-- Trigger Keyword -->
            <div class="pm-setting-row">
                <div class="pm-setting-info" style="flex:1;">
                    <div class="pm-setting-label"><i class="fas fa-play-circle" style="color:#10b981;margin-right:4px;"></i> Palavra-chave de ativação</div>
                    <div class="pm-setting-desc">Palavras que iniciam este bot quando o contato enviar uma mensagem. Separe várias palavras com vírgula.</div>
                </div>
            </div>
            <div class="pm-input-row">
                <input type="text" class="pm-input" id="pm-enable-keyword" placeholder="ex: oi, menu, começar" value="<?php echo esc($bot->enable_keyword ?? $bot->trigger_keywords ?? '') ?>">
            </div>

            <!-- Stop Keyword -->
            <div class="pm-setting-row" style="margin-top:10px;">
                <div class="pm-setting-info" style="flex:1;">
                    <div class="pm-setting-label"><i class="fas fa-stop-circle" style="color:#ef4444;margin-right:4px;"></i> Palavra-chave para parar</div>
                    <div class="pm-setting-desc">Palavras que encerram imediatamente a sessão do bot. O contato poderá iniciar novamente usando a palavra-chave de ativação.</div>
                </div>
            </div>
            <div class="pm-input-row">
                <input type="text" class="pm-input" id="pm-stop-keyword" placeholder="ex: parar, sair, cancelar" value="<?php echo esc($bot->stop_keyword ?? '') ?>">
            </div>

            <!-- Keyword Match Type -->
            <div class="pm-setting-row" style="margin-top:14px;">
                <div class="pm-setting-info" style="flex:1;">
                    <div class="pm-setting-label"><i class="fas fa-crosshairs" style="color:#8b5cf6;margin-right:4px;"></i> Tipo de correspondência</div>
                    <div class="pm-setting-desc">Escolha como a palavra-chave deve ser comparada com a mensagem recebida.</div>
                </div>
            </div>
            <div class="pm-input-row">
                <select class="pm-input" id="pm-keyword-match-type" style="cursor:pointer;">
                    <option value="contains" <?php echo (($bot->keyword_match_type ?? 'contains') === 'contains') ? 'selected' : '' ?>>Contém a palavra em qualquer parte</option>
                    <option value="exact" <?php echo (($bot->keyword_match_type ?? 'contains') === 'exact') ? 'selected' : '' ?>>Mensagem exatamente igual</option>
                </select>
            </div>

            <!-- Chat Type -->
            <div class="pm-setting-row" style="margin-top:14px;">
                <div class="pm-setting-info" style="flex:1;">
                    <div class="pm-setting-label"><i class="fas fa-comments" style="color:#0ea5e9;margin-right:4px;"></i> Responder em</div>
                    <div class="pm-setting-desc">Escolha em quais tipos de conversa este bot pode responder.</div>
                </div>
            </div>
            <div class="pm-input-row">
                <div class="pm-chat-type-group" id="pm-chat-type-group">
                    <?php $chatType = $bot->chat_type ?? 'all'; ?>
                    <label class="pm-chat-type-pill <?php echo $chatType === 'all' ? 'active' : '' ?>">
                        <input type="radio" name="pm-chat-type" value="all" <?php echo $chatType === 'all' ? 'checked' : '' ?> style="display:none;">
                        <i class="fas fa-globe"></i> Todos
                    </label>
                    <label class="pm-chat-type-pill <?php echo $chatType === 'individual' ? 'active' : '' ?>">
                        <input type="radio" name="pm-chat-type" value="individual" <?php echo $chatType === 'individual' ? 'checked' : '' ?> style="display:none;">
                        <i class="fas fa-user"></i> Apenas individuais
                    </label>
                    <label class="pm-chat-type-pill <?php echo $chatType === 'groups' ? 'active' : '' ?>">
                        <input type="radio" name="pm-chat-type" value="groups" <?php echo $chatType === 'groups' ? 'checked' : '' ?> style="display:none;">
                        <i class="fas fa-users"></i> Apenas grupos
                    </label>
                </div>
            </div>

            <!-- Save Settings Button -->
            <div style="margin-top:12px;text-align:right;">
                <button class="pm-btn pm-btn-outline" id="pm-save-settings-btn" onclick="saveBotSettings()">
                    <i class="fas fa-save"></i> Salvar configurações
                </button>
            </div>
        </div>

        <!-- WhatsApp Instances -->
        <div class="pm-section">
            <div class="pm-section-header">
                <h4><i class="fab fa-whatsapp" style="color:#25d366;"></i> Conexões WhatsApp</h4>
                <span class="pm-badge" id="pm-instance-count">0 conectadas</span>
            </div>
            <p class="pm-section-desc">Selecione quais números WhatsApp usarão este bot. O fluxo responderá automaticamente nas conexões vinculadas.</p>
            
            <div class="pm-instances-list" id="pm-instances-list">
                <div class="pm-loading"><i class="fas fa-circle-notch fa-spin"></i> Carregando conexões...</div>
            </div>

            <div class="pm-empty-state" id="pm-no-instances" style="display:none;">
                <i class="fab fa-whatsapp" style="font-size:36px;color:#94a3b8;"></i>
                <h4>Nenhuma conexão WhatsApp</h4>
                <p>Você ainda não conectou contas WhatsApp. Vá até a Central de Conexão para adicionar uma.</p>
                <a href="<?php echo base_url('account-manager') ?>" class="pm-btn pm-btn-outline">
                    <i class="fas fa-plus"></i> Adicionar WhatsApp
                </a>
            </div>
        </div>

        <!-- Linked Instances Summary -->
        <div class="pm-section" id="pm-linked-summary" style="display:none;">
            <div class="pm-alert pm-alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Bot ativo!</strong>
                    <span id="pm-linked-summary-text">Este bot está vinculado a 0 números WhatsApp.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="pm-footer">
        <button class="pm-btn pm-btn-ghost" onclick="closePublishModal()">Fechar</button>
        <button class="pm-btn pm-btn-primary" onclick="publishAndConnect()">
            <i class="fas fa-check-double"></i> Salvar e concluir
        </button>
    </div>
</div>
</div>

<style>
/* ======= PUBLISH MODAL STYLES ======= */
.publish-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(6px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pmFadeIn 0.2s ease;
}
@keyframes pmFadeIn { from{opacity:0} to{opacity:1} }
@keyframes pmSlideUp { from{transform:translateY(30px);opacity:0} to{transform:translateY(0);opacity:1} }

.publish-modal {
    background: #fff;
    border-radius: 20px;
    width: 640px;
    max-width: 95vw;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25), 0 0 0 1px rgba(0,0,0,0.05);
    animation: pmSlideUp 0.3s ease;
}

.pm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}
.pm-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.pm-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
}
.pm-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}
.pm-header p {
    margin: 2px 0 0;
    font-size: 12px;
    color: #64748b;
}
.pm-close {
    background: none;
    border: none;
    font-size: 18px;
    color: #94a3b8;
    cursor: pointer;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}
.pm-close:hover {
    background: #f1f5f9;
    color: #475569;
}

.pm-body {
    padding: 20px 24px;
    overflow-y: auto;
    flex: 1;
}

.pm-section {
    margin-bottom: 20px;
}
.pm-section:last-child { margin-bottom: 0; }

.pm-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.pm-section-header h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.pm-section-desc {
    margin: 0 0 14px;
    font-size: 12px;
    color: #64748b;
    line-height: 1.5;
}

.pm-badge {
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 20px;
    background: #f1f5f9;
    color: #64748b;
    font-weight: 600;
}

/* Status Bar */
.pm-status-bar {
    display: flex;
    align-items: center;
    gap: 0;
    padding: 12px 16px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
.pm-status-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    white-space: nowrap;
}
.pm-status-divider {
    flex: 1;
    height: 2px;
    background: #e2e8f0;
    margin: 0 12px;
    min-width: 20px;
}
.pm-status-item.active i { color: #4f46e5 !important; }
.pm-status-item.done i { color: #10b981 !important; }

/* Card */
.pm-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #fff;
    transition: all 0.2s;
}
.pm-card:hover {
    border-color: #c7d2fe;
    box-shadow: 0 4px 12px rgba(79,70,229,0.08);
}
.pm-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    flex-shrink: 0;
}
.pm-card-content {
    flex: 1;
    min-width: 0;
}
.pm-card-content h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
}
.pm-card-content p {
    margin: 4px 0 0;
    font-size: 11px;
    color: #64748b;
    line-height: 1.4;
}

/* Buttons */
.pm-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}
.pm-btn-primary {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #fff;
    box-shadow: 0 2px 8px rgba(79,70,229,0.3);
}
.pm-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(79,70,229,0.4);
}
.pm-btn-primary:disabled {
    opacity: 0.6;
    transform: none;
    cursor: not-allowed;
}
.pm-btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    box-shadow: 0 2px 8px rgba(16,185,129,0.3);
}
.pm-btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16,185,129,0.4);
}
.pm-btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
}
.pm-btn-danger:hover { transform: translateY(-1px); }
.pm-btn-outline {
    background: #fff;
    color: #4f46e5;
    border: 1px solid #c7d2fe;
}
.pm-btn-outline:hover {
    background: #f5f3ff;
}
.pm-btn-ghost {
    background: transparent;
    color: #64748b;
}
.pm-btn-ghost:hover {
    background: #f1f5f9;
    color: #1e293b;
}

/* Instance Cards */
.pm-instances-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.pm-instance-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    transition: all 0.2s;
}
.pm-instance-card:hover {
    border-color: #c7d2fe;
    background: #fafafe;
}
.pm-instance-card.linked {
    border-color: #86efac;
    background: #f0fdf4;
}
.pm-instance-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.pm-instance-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.pm-instance-avatar i {
    font-size: 18px;
    color: #25d366;
}
.pm-instance-info {
    flex: 1;
    min-width: 0;
}
.pm-instance-name {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}
.pm-instance-detail {
    font-size: 11px;
    color: #64748b;
    margin: 2px 0 0;
}
.pm-instance-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}
.pm-instance-status {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 20px;
    font-weight: 600;
}
.pm-instance-status.connected {
    background: #dcfce7;
    color: #15803d;
}
.pm-instance-status.disconnected {
    background: #fee2e2;
    color: #b91c1c;
}

/* Loading */
.pm-loading {
    text-align: center;
    padding: 30px;
    color: #94a3b8;
    font-size: 13px;
}
.pm-loading i {
    margin-right: 6px;
}

/* Empty State */
.pm-empty-state {
    text-align: center;
    padding: 30px 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px dashed #e2e8f0;
}
.pm-empty-state h4 {
    margin: 12px 0 4px;
    font-size: 14px;
    color: #475569;
}
.pm-empty-state p {
    margin: 0 0 14px;
    font-size: 12px;
    color: #94a3b8;
}

/* Alert */
.pm-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 12px;
}
.pm-alert i {
    font-size: 16px;
    flex-shrink: 0;
    margin-top: 1px;
}
.pm-alert strong {
    display: block;
    margin-bottom: 2px;
}
.pm-alert-success {
    background: #f0fdf4;
    border: 1px solid #86efac;
    color: #166534;
}
.pm-alert-success i { color: #10b981; }

/* Footer */
.pm-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding: 16px 24px;
    border-top: 1px solid #f1f5f9;
    background: #f8fafc;
}

/* Bot Settings */
.pm-setting-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    margin-bottom: 6px;
    transition: all 0.2s;
}
.pm-setting-row:hover {
    border-color: #c7d2fe;
    background: #fafafe;
}
.pm-setting-info {
    flex: 1;
    min-width: 0;
}
.pm-setting-label {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
}
.pm-setting-desc {
    font-size: 11px;
    color: #64748b;
    line-height: 1.4;
    margin-top: 2px;
}

/* Toggle Switch */
.pm-toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}
.pm-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.pm-toggle-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #cbd5e1;
    border-radius: 24px;
    transition: all 0.3s;
}
.pm-toggle-slider:before {
    content: '';
    position: absolute;
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: all 0.3s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.pm-toggle-switch input:checked + .pm-toggle-slider {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
}
.pm-toggle-switch input:checked + .pm-toggle-slider:before {
    transform: translateX(20px);
}
.pm-toggle-switch input:focus + .pm-toggle-slider {
    box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
}

/* Input fields in settings */
.pm-input-row {
    margin-bottom: 4px;
}
.pm-input {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    font-size: 13px;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    background: #fff;
    transition: all 0.2s;
    outline: none;
    box-sizing: border-box;
}
.pm-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.pm-input::placeholder {
    color: #94a3b8;
}

/* Chat Type Pill Buttons */
.pm-chat-type-group {
    display: flex;
    gap: 6px;
    width: 100%;
}
.pm-chat-type-pill {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 10px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    font-size: 12.5px;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}
.pm-chat-type-pill:hover {
    border-color: #c7d2fe;
    background: #f8fafc;
}
.pm-chat-type-pill.active {
    border-color: #6366f1;
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    color: #4338ca;
    font-weight: 600;
    box-shadow: 0 0 0 2px rgba(99,102,241,0.12);
}
.pm-chat-type-pill i {
    font-size: 13px;
}

/* Select dropdown styling */
.pm-input select, select.pm-input {
    appearance: auto;
    -webkit-appearance: auto;
}

/* Premium publish modal refinement */
.publish-modal-overlay {
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(10px);
}
.publish-modal {
    width: 720px;
    border-radius: 26px;
    border: 1px solid rgba(226, 232, 240, 0.82);
    box-shadow: 0 34px 90px rgba(15, 23, 42, 0.35);
}
.pm-header {
    padding: 22px 26px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 52%, #eef2ff 100%);
}
.pm-header-icon,
.pm-card-icon {
    border-radius: 16px;
    box-shadow: 0 12px 26px rgba(79, 70, 229, 0.20);
}
.pm-header h3 {
    font-size: 17px;
    font-weight: 850;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.pm-close {
    border: 1px solid rgba(226, 232, 240, 0.86);
    background: rgba(255,255,255,0.76);
}
.pm-body {
    padding: 22px 26px;
    background: linear-gradient(180deg, #fff, #f8fafc);
}
.pm-section {
    margin-bottom: 22px;
}
.pm-status-bar,
.pm-card,
.pm-setting-row,
.pm-empty-state,
.pm-alert {
    border-radius: 18px;
    border-color: rgba(226, 232, 240, 0.86);
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
}
.pm-card:hover,
.pm-setting-row:hover,
.pm-instance-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
}
.pm-btn {
    border-radius: 13px;
    font-weight: 800;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
}
.pm-btn-primary {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    box-shadow: 0 12px 26px rgba(79, 70, 229, 0.24);
}
.pm-instance-card {
    border-radius: 16px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
}
.pm-input,
.pm-chat-type-pill {
    border-radius: 14px;
    border-color: rgba(203, 213, 225, 0.94);
}
.pm-footer {
    padding: 18px 26px;
    background: rgba(248, 250, 252, 0.95);
}
</style>

<!-- Config -->
<script>
    window.bsConfig = {
        bot_id: '<?php echo $bot->id ?>',
        csrf_name: '<?php echo $csrf ?>',
        csrf_hash: '<?php echo $hash ?>',
        save_url: '<?php echo base_url("bot-builder/save") ?>',
        export_url: '<?php echo base_url("bot-builder/export/".$bot->id) ?>',
        import_url: '<?php echo base_url("bot-builder/import") ?>',
        link_url: '<?php echo base_url("bot-builder/link-instance") ?>',
        unlink_url: '<?php echo base_url("bot-builder/unlink-instance") ?>',
        instances_url: '<?php echo base_url("bot-builder/instances") ?>',
        integrations_url: '<?php echo base_url("bot-builder/integrations/".$bot->id) ?>',
        settings_url: '<?php echo base_url("bot-builder/save-bot-settings") ?>',
        get_settings_url: '<?php echo base_url("bot-builder/get-bot-settings/".$bot->id) ?>',
        upload_media_url: '<?php echo base_url("bot-builder/upload-media") ?>',
        native_templates_url: '<?php echo base_url("bot-builder/native-templates") ?>',
        native_template_url: '<?php echo base_url("bot-builder/native-template") ?>',
        base_url: '<?php echo rtrim(base_url(), '/') . '/' ?>',
        native_return_url: '<?php echo current_url() ?>'
    };
    window.initialBotSettings = {
        trigger_keywords: '<?php echo addslashes($bot->trigger_keywords ?? '') ?>',
        enable_keyword: '<?php echo addslashes($bot->enable_keyword ?? '') ?>',
        stop_keyword: '<?php echo addslashes($bot->stop_keyword ?? '') ?>',
        bot_enabled: <?php echo (isset($bot->bot_enabled) ? (int)$bot->bot_enabled : 1) ?>,
        keyword_match_type: '<?php echo addslashes($bot->keyword_match_type ?? 'contains') ?>',
        chat_type: '<?php echo addslashes($bot->chat_type ?? 'all') ?>'
    };
    window.initialNodes = <?php echo !empty($blocks) ? json_encode($blocks) : '[]' ?>;
    window.initialEdges = <?php echo !empty($edges) ? json_encode($edges) : '[]' ?>;
    window.waInstances = <?php echo !empty($instances) ? json_encode($instances) : '[]' ?>;
    window.linkedInstanceIds = <?php echo !empty($linked_instance_ids) ? json_encode($linked_instance_ids) : '[]' ?>;
</script>

<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/utils.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/history.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/canvas-core.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/persistence.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/node-defs.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/connections.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/validation.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/simulator-ui.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/simulator.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/publish-modal.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/inspector-core.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/inspector-templates.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/inspector-variables.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/inspector-dynamics.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/inspector-integrations.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/builder/inspector.js') ?>?v=<?php echo time() ?>"></script>
<script src="<?php echo base_url('inc/core/Bot_builder/Assets/js/bot_builder.js') ?>?v=<?php echo time() ?>"></script>

<script>
// Chat type pill toggle handler
document.addEventListener('DOMContentLoaded', function() {
    const group = document.getElementById('pm-chat-type-group');
    if(group) {
        group.querySelectorAll('.pm-chat-type-pill').forEach(pill => {
            pill.addEventListener('click', function() {
                group.querySelectorAll('.pm-chat-type-pill').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                this.querySelector('input[type=radio]').checked = true;
            });
        });
    }
});
</script>

</body>
</html>
