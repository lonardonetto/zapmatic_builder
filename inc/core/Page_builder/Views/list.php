<?php 
$pages = $pages ?? [];
$team_id = get_team("id");
?>
<div class="main-wrapper flex-grow-1 n-scroll">
    <div class="container my-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1"><i class="fas fa-file-alt me-2" style="color:#6C5CE7"></i>Construtor de Páginas</h3>
                <p class="text-muted mb-0">Crie e edite landing pages com blocos visuais</p>
            </div>
            <button class="btn btn-primary" onclick="newPage()">
                <i class="fas fa-plus me-1"></i> Nova Página
            </button>
        </div>

        <?php if (empty($pages)): ?>
        <div class="text-center py-5">
            <div class="mb-3" style="font-size:48px">📄</div>
            <h5>Nenhuma página criada</h5>
            <p class="text-muted">Clique em "Nova Página" para começar a construir sua landing page</p>
            <button class="btn btn-primary" onclick="newPage()">Criar Primeira Página</button>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($pages as $page): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">
                                <?php if ($page->is_home): ?>🏠 <?php endif; ?>
                                <?php _e($page->title) ?>
                            </h6>
                            <span class="badge bg-<?php _e($page->is_published ? 'success' : 'secondary') ?>">
                                <?php _e($page->is_published ? 'Publicada' : 'Rascunho') ?>
                            </span>
                        </div>
                        <p class="text-muted small mb-2">
                            /<?php _e($page->page_type == 'home' ? '' : $page->slug) ?>
                        </p>
                        <p class="text-muted small mb-3">
                            <i class="fas fa-layer-group me-1"></i> <?php _e($page->section_count) ?> seções
                        </p>
                        <div class="d-flex gap-1">
                            <a href="<?php _e(get_module_url("editor/" . $page->id)) ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <?php if ($page->is_published): ?>
                            <a href="<?php _e($page->page_type == 'home' ? base_url() : base_url('pagina/' . $page->slug)) ?>" 
                               target="_blank" class="btn btn-sm btn-outline-success" title="Ver página">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger" onclick="deletePage(<?php _e($page->id) ?>, '<?php _e($page->title) ?>')" title="Deletar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nova/Editar Página -->
<div class="modal fade" id="pageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Página</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="pageForm">
                    <input type="hidden" name="id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" class="form-control" name="title" required placeholder="Ex: Página Inicial">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control" name="slug" placeholder="Ex: minha-landing (deixe vazio para auto-gerar)">
                        <small class="text-muted">URL: /pagina/<b>seu-slug</b></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="page_type">
                            <option value="home">Home (substitui página principal)</option>
                            <option value="pricing">Preços</option>
                            <option value="features">Funcionalidades</option>
                            <option value="faqs">FAQ</option>
                            <option value="custom">Página Customizada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_home" value="1" id="isHome">
                            <label class="form-check-label" for="isHome">Usar como página principal do site</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_published" value="1" checked id="isPublished">
                            <label class="form-check-label" for="isPublished">Publicar página</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="savePage()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
function newPage() {
    document.getElementById('pageForm').reset();
    document.querySelector('[name="id"]').value = 0;
    new bootstrap.Modal(document.getElementById('pageModal')).show();
}

function savePage() {
    var form = document.getElementById('pageForm');
    var data = {};
    $(form).serializeArray().forEach(function(item) {
        if (item.name === 'is_home' || item.name === 'is_published') {
            data[item.name] = item.value || '0';
        } else {
            data[item.name] = item.value;
        }
    });
    $.post('<?php _e(get_module_url("save_page")) ?>', data, function(res) {
        if (res.status === 'success') {
            location.href = res.redirect;
        } else {
            alert(res.message);
        }
    }, 'json');
}

function deletePage(id, title) {
    if (confirm('Tem certeza que deseja deletar "' + title + '"? Todas as seções serão removidas.')) {
        $.post('<?php _e(get_module_url("delete_page")) ?>', {id: id}, function(res) {
            location.reload();
        }, 'json');
    }
}
</script>
