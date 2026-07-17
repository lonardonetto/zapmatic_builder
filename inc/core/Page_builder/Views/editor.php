<?php
$page = $page ?? [];
$sections = $sections ?? [];
$blocks = $blocks ?? [];
$page_id = $page->id ?? 0;
$module_url = get_module_url();
?>
<div class="main-wrapper flex-grow-1 n-scroll">
    <div class="container-fluid my-3">
        
        <!-- Top Bar -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="<?php _e($module_url) ?>" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <strong>Editando: <?php _e($page->title) ?></strong>
                <span class="badge bg-<?php _e($page->is_published ? 'success' : 'secondary') ?> ms-2">
                    <?php _e($page->is_published ? 'Publicada' : 'Rascunho') ?>
                </span>
            </div>
            <div>
                <?php if ($page->is_published): ?>
                <a href="<?php _e($page->page_type == 'home' ? base_url() : base_url('pagina/' . $page->slug)) ?>" 
                   target="_blank" class="btn btn-sm btn-outline-success me-1">
                    <i class="fas fa-eye me-1"></i> Ver Página
                </a>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-warning me-1" onclick="togglePublish(<?php _e($page_id) ?>)">
                    <?php _e($page->is_published ? 'Despublicar' : 'Publicar') ?>
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Coluna esquerda: Blocos disponíveis -->
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <strong>Blocos Disponíveis</strong>
                    </div>
                    <div class="card-body p-2">
                        <?php foreach ($blocks as $type => $block): ?>
                        <button class="btn btn-outline-secondary btn-sm w-100 text-start mb-1 add-block-btn" 
                                data-type="<?php _e($type) ?>" 
                                title="<?php _e($block['desc']) ?>">
                            <i class="<?php _e($block['icon']) ?> me-2" style="width:20px"></i>
                            <?php _e($block['name']) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Coluna direita: Seções -->
            <div class="col-md-9">
                <div id="sections-container" class="sortable">
                    <?php if (empty($sections)): ?>
                    <div class="text-center py-5" id="empty-state">
                        <div style="font-size:48px">🧱</div>
                        <h5>Nenhuma seção adicionada</h5>
                        <p class="text-muted">Clique em um bloco ao lado para adicionar a primeira seção</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($sections as $section): 
                            $data = json_decode($section->data, true) ?: [];
                        ?>
                        <div class="card shadow-sm mb-3 section-card" data-id="<?php _e($section->id) ?>">
                            <div class="card-header d-flex justify-content-between align-items-center" style="cursor:move">
                                <div>
                                    <span class="drag-handle me-2">☰</span>
                                    <strong><?php _e($blocks[$section->block_type]['name'] ?? $section->block_type) ?></strong>
                                    <small class="text-muted ms-2"><?php _e($section->title) ?></small>
                                </div>
                                <div>
                                    <span class="badge bg-<?php _e($section->is_active ? 'success' : 'secondary') ?> me-2">
                                        <?php _e($section->is_active ? 'Ativo' : 'Inativo') ?>
                                    </span>
                                    <button class="btn btn-sm btn-outline-danger delete-section" data-id="<?php _e($section->id) ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <form class="section-form" data-section-id="<?php _e($section->id) ?>" data-block-type="<?php _e($section->block_type) ?>">
                                    <input type="hidden" name="page_id" value="<?php _e($page_id) ?>">
                                    <input type="hidden" name="section_id" value="<?php _e($section->id) ?>">
                                    <input type="hidden" name="block_type" value="<?php _e($section->block_type) ?>">
                                    
                                    <!-- Conteúdo do bloco carregado via JS -->
                                    <div class="block-content" data-type="<?php _e($section->block_type) ?>">
                                        <?php 
                                        // Use controller method to render block form
                                        echo call_user_func([new \Core\Page_builder\Controllers\Page_builder(), 'loadBlockForm'], $section->block_type, $data); 
                                        ?>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-primary btn-sm save-section-btn">
                                            <i class="fas fa-save me-1"></i> Salvar Seção
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template para nova seção (oculto) -->
<template id="section-template">
    <div class="card shadow-sm mb-3 section-card">
        <div class="card-header d-flex justify-content-between align-items-center" style="cursor:move">
            <div>
                <span class="drag-handle me-2">☰</span>
                <strong class="section-type-name"></strong>
                <small class="text-muted ms-2 section-internal-title"></small>
            </div>
            <div>
                <span class="badge bg-success me-2">Ativo</span>
                <button class="btn btn-sm btn-outline-danger delete-section">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form class="section-form">
                <input type="hidden" name="page_id" value="<?php _e($page_id) ?>">
                <input type="hidden" name="section_id" value="0">
                <input type="hidden" name="block_type" value="">
                <div class="block-content"></div>
                <div class="mt-2">
                    <button type="button" class="btn btn-primary btn-sm save-section-btn">
                        <i class="fas fa-save me-1"></i> Salvar Seção
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
var moduleUrl = '<?php _e($module_url) ?>';
var pageId = <?php _e($page_id) ?>;
var existingSections = <?php echo json_encode($sections, JSON_UNESCAPED_UNICODE); ?>;

// Templates de formulários por tipo de bloco
var blockTemplates = {
    hero: `
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Título</label>
                <input type="text" class="form-control" name="hero_title" value="{hero_title}" placeholder="#1 Plataforma de Marketing">
            </div>
            <div class="col-md-6">
                <label class="form-label">Cor do Título</label>
                <input type="color" class="form-control form-control-color" name="text_color" value="{text_color}">
            </div>
            <div class="col-12">
                <label class="form-label">Subtítulo</label>
                <textarea class="form-control" name="hero_subtitle" rows="2" placeholder="Automatize seu atendimento no WhatsApp">{hero_subtitle}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Texto do Botão</label>
                <input type="text" class="form-control" name="button_text" value="{button_text}" placeholder="Começar Grátis">
            </div>
            <div class="col-md-4">
                <label class="form-label">URL do Botão</label>
                <input type="text" class="form-control" name="button_url" value="{button_url}" placeholder="/signup">
            </div>
            <div class="col-md-4">
                <label class="form-label">Cor do Botão</label>
                <input type="color" class="form-control form-control-color" name="button_color" value="{button_color}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipo de Fundo</label>
                <select class="form-select" name="background_type">
                    <option value="color" {bg_color_selected}>Cor Sólida</option>
                    <option value="image" {bg_image_selected}>Imagem</option>
                    <option value="gradient" {bg_gradient_selected}>Gradiente</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cor do Fundo</label>
                <input type="color" class="form-control form-control-color" name="background_color" value="{background_color}">
            </div>
            <div class="col-md-4">
                <label class="form-label">URL da Imagem de Fundo</label>
                <input type="text" class="form-control" name="background_image" value="{background_image}" placeholder="/Assets/img/bg.jpg">
            </div>
            <div class="col-md-4">
                <label class="form-label">Imagem Ilustrativa</label>
                <input type="text" class="form-control" name="image_right" value="{image_right}" placeholder="/Assets/img/hero.png">
            </div>
        </div>`,
    
    features: `
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Título da Seção</label>
                <input type="text" class="form-control" name="section_title" value="{section_title}" placeholder="Funcionalidades">
            </div>
            <div class="col-12">
                <label class="form-label">Subtítulo</label>
                <textarea class="form-control" name="section_subtitle" rows="2" placeholder="Tudo que você precisa">{section_subtitle}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Layout</label>
                <select class="form-select" name="layout">
                    <option value="3" {layout_3}>3 Colunas</option>
                    <option value="4" {layout_4}>4 Colunas</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Cards (JSON)</label>
                <textarea class="form-control" name="cards" rows="6" placeholder='[{"icon":"🚀","title":"Automação","description":"Descrição..."}]'>{cards}</textarea>
                <small class="text-muted">Formato JSON: [{"icon":"ícone","title":"título","description":"descrição"}]</small>
            </div>
        </div>`,

    pricing: `
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Título da Seção</label>
                <input type="text" class="form-control" name="section_title" value="{section_title}" placeholder="Nossos Planos">
            </div>
            <div class="col-12">
                <label class="form-label">Subtítulo</label>
                <textarea class="form-control" name="section_subtitle" rows="2" placeholder="Escolha o melhor plano">{section_subtitle}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Origem dos Preços</label>
                <select class="form-select" name="pricing_origin">
                    <option value="auto" {auto_selected}>Automático (do banco de dados - sp_plans)</option>
                    <option value="manual" {manual_selected}>Manual (definir aqui)</option>
                </select>
            </div>
        </div>`,

    faq: `
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Título da Seção</label>
                <input type="text" class="form-control" name="section_title" value="{section_title}" placeholder="Perguntas Frequentes">
            </div>
            <div class="col-12">
                <label class="form-label">Origem</label>
                <select class="form-select" name="faq_origin">
                    <option value="auto" {auto_selected}>Automático (do banco de dados)</option>
                    <option value="manual" {manual_selected}>Manual (JSON)</option>
                </select>
            </div>
            <div class="col-12 manual-faq" style="display:none">
                <label class="form-label">Perguntas (JSON)</label>
                <textarea class="form-control" name="faqs" rows="6" placeholder='[{"q":"Pergunta","a":"Resposta"}]'>{faqs}</textarea>
            </div>
        </div>`,

    cta: `
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Texto Principal</label>
                <input type="text" class="form-control" name="cta_text" value="{cta_text}" placeholder="Pronto para começar?">
            </div>
            <div class="col-12">
                <label class="form-label">Subtexto</label>
                <textarea class="form-control" name="cta_subtext" rows="2" placeholder="Crie sua conta gratuita">{cta_subtext}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Texto do Botão</label>
                <input type="text" class="form-control" name="cta_button_text" value="{cta_button_text}" placeholder="Começar">
            </div>
            <div class="col-md-4">
                <label class="form-label">URL do Botão</label>
                <input type="text" class="form-control" name="cta_button_url" value="{cta_button_url}" placeholder="/signup">
            </div>
            <div class="col-md-4">
                <label class="form-label">Cor do Fundo</label>
                <input type="color" class="form-control form-control-color" name="cta_bg_color" value="{cta_bg_color}">
            </div>
        </div>`,

    testimonials: `
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Título</label>
                <input type="text" class="form-control" name="section_title" value="{section_title}" placeholder="O que dizem nossos clientes">
            </div>
            <div class="col-12">
                <label class="form-label">Depoimentos (JSON)</label>
                <textarea class="form-control" name="testimonials" rows="6" placeholder='[{"name":"Nome","role":"Cargo","text":"Depoimento","photo":"/url/foto.jpg","stars":5}]'>{testimonials}</textarea>
            </div>
        </div>`,

    footer: `
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Logo (URL)</label>
                <input type="text" class="form-control" name="footer_logo" value="{footer_logo}" placeholder="/Assets/img/logo.png">
            </div>
            <div class="col-12">
                <label class="form-label">Descrição</label>
                <textarea class="form-control" name="footer_description" rows="2" placeholder="Sobre a empresa">{footer_description}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Links do Rodapé (JSON)</label>
                <textarea class="form-control" name="footer_links" rows="4" placeholder='[{"title":"Termos","url":"/terms"}]'>{footer_links}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Redes Sociais (JSON)</label>
                <textarea class="form-control" name="social_links" rows="4" placeholder='[{"name":"Facebook","url":"...","icon":"fab fa-facebook"}]'>{social_links}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Copyright</label>
                <input type="text" class="form-control" name="copyright" value="{copyright}" placeholder="© 2024 Sua Empresa">
            </div>
        </div>`,

    custom_html: `
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Título Interno (para organização)</label>
                <input type="text" class="form-control" name="section_title" value="{section_title}" placeholder="Bloco personalizado">
            </div>
            <div class="col-12">
                <label class="form-label">HTML</label>
                <textarea class="form-control" name="custom_html" rows="8" placeholder="<div>Seu HTML aqui</div>">{custom_html}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">CSS Adicional</label>
                <textarea class="form-control" name="custom_css" rows="4" placeholder=".meu-bloco { background: #fff; }">{custom_css}</textarea>
            </div>
        </div>`
};

// Função para preencher template com valores
function fillTemplate(template, data) {
    var html = template;
    for (var key in data) {
        html = html.replace(new RegExp('{' + key + '}', 'g'), data[key] || '');
    }
    // Limpar placeholders não usados
    html = html.replace(/\{[a-z_]+\}/g, '');
    return html;
}

// Adicionar nova seção
document.querySelectorAll('.add-block-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var type = this.dataset.type;
        var template = blockTemplates[type];
        if (!template) return;
        
        var tpl = document.getElementById('section-template');
        var clone = tpl.content.cloneNode(true);
        
        clone.querySelector('input[name="block_type"]').value = type;
        clone.querySelector('.section-type-name').textContent = this.textContent.trim();
        
        var content = fillTemplate(template, {});
        clone.querySelector('.block-content').innerHTML = content;
        
        document.getElementById('empty-state')?.remove();
        document.getElementById('sections-container').appendChild(clone);
        
        bindSectionEvents();
    });
});

// Event delegation para salvar/deletar (funciona para elementos dinâmicos)
document.querySelector('#sections-container').addEventListener('click', function(e) {
    var target = e.target;
    
    // Delete button
    if (target.closest('.delete-section')) {
        e.preventDefault();
        var btn = target.closest('.delete-section');
        var card = btn.closest('.section-card');
        if (!card) return;
        
        var sectionId = btn.getAttribute('data-id') || card.getAttribute('data-id') || '0';
        
        if (!sectionId || sectionId === '0') {
            card.remove();
            checkEmpty();
            return;
        }
        
        if (!confirm('Remover esta seção?')) return;
        
        $.ajax({
            url: moduleUrl + 'delete_section',
            type: 'POST',
            data: {id: sectionId},
            dataType: 'text',
            success: function(raw) {
                try {
                    var res = JSON.parse(raw);
                    if (res.status === 'success') {
                        card.remove();
                        checkEmpty();
                    } else {
                        alert('Erro: ' + (res.message || 'desconhecido'));
                    }
                } catch(e) {
                    alert('Erro ao processar resposta do servidor');
                }
            },
            error: function() {
                alert('Erro de conexão ao remover seção');
            }
        });
    }
    
    // Save button
    if (target.closest('.save-section-btn')) {
        e.preventDefault();
        var btn = target.closest('.save-section-btn');
        var form = btn.closest('.section-form');
        if (!form) return;
        
        var data = {};
        $(form).find('input, select, textarea').each(function() {
            if (this.name) data[this.name] = this.value;
        });
        
        var origText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>';
        btn.disabled = true;
        
        $.ajax({
            url: moduleUrl + 'save_section',
            type: 'POST',
            data: data,
            dataType: 'text',
            success: function(raw) {
                btn.disabled = false;
                try {
                    var res = JSON.parse(raw);
                    if (res.status === 'success') {
                        if (res.section_id) {
                            form.querySelector('input[name="section_id"]').value = res.section_id;
                            var card = form.closest('.section-card');
                            if (card) card.setAttribute('data-id', res.section_id);
                        }
                        btn.innerHTML = '<i class="fas fa-check me-1"></i> Salvo!';
                        btn.classList.add('btn-success');
                        btn.classList.remove('btn-primary');
                        setTimeout(function() {
                            btn.innerHTML = '<i class="fas fa-save me-1"></i> Salvar';
                            btn.classList.remove('btn-success');
                            btn.classList.add('btn-primary');
                        }, 1500);
                    } else {
                        btn.innerHTML = origText;
                        alert(res.message || 'Erro ao salvar');
                    }
                } catch(e) {
                    btn.innerHTML = origText;
                    alert('Erro ao processar resposta');
                }
            },
            error: function() {
                btn.disabled = false;
                btn.innerHTML = origText;
                alert('Erro de conexão ao salvar');
            }
        });
    }
});

function checkEmpty() {
    var cards = document.querySelectorAll('#sections-container .section-card');
    if (cards.length === 0) {
        document.getElementById('sections-container').innerHTML = 
            '<div class="text-center py-5" id="empty-state"><div style="font-size:48px">🧱</div><h5>Nenhuma seção adicionada</h5><p class="text-muted">Clique em um bloco ao lado para adicionar</p></div>';
    }
}

function togglePublish(pageId) {
    $.post(moduleUrl + 'toggle_page', {id: pageId}, function() {
        location.reload();
    }, 'json');
}

// SortableJS - reordenar seções
if (typeof Sortable !== 'undefined') {
    new Sortable(document.getElementById('sections-container'), {
        handle: '.card-header',
        animation: 150,
        onEnd: function() {
            var order = [];
            document.querySelectorAll('.section-card').forEach(function(card) {
                order.push(card.dataset.id || 0);
            });
            $.post(moduleUrl + 'reorder_sections', {order: JSON.stringify(order)});
        }
    });
}

</script>
