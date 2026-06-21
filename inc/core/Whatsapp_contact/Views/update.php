<form class="actionForm" action="<?php _eC(get_module_url("save/" . get_data($result, "ids"))) ?>" method="POST" data-redirect="<?php _ec(get_module_url()) ?>">
    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between py-4">
            <div class="d-flex align-items-center">
                <a href="<?php _ec(get_module_url()) ?>" class="btn btn-light me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="fs-2 fw-bold mb-0">
                        <?php _e(get_data($result, "ids") ? "Editar Grupo" : "Novo Grupo") ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <?php _e(get_data($result, "ids") ? "Atualize as informações do grupo" : "Crie um novo grupo de contatos") ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-12 col-lg-8">
                        <!-- Nome do Grupo -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <?php _e("Nome do Grupo") ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="name" 
                                   value="<?php _ec(get_data($result, "name")) ?>" 
                                   placeholder="<?php _e("Digite o nome do grupo") ?>"
                                   required>
                            <div class="form-text">
                                <?php _e("Este nome será usado para identificar o grupo de contatos") ?>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold d-block mb-3">
                                <?php _e("Status do Grupo") ?>
                            </label>
                            <div class="d-flex gap-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="status" 
                                           id="status_enable" 
                                           value="1" 
                                           <?php _e(get_data($result, "status") == 1 || get_data($result, "status") == "" ? "checked='true'" : "") ?>>
                                    <label class="form-check-label" for="status_enable">
                                        <span class="badge bg-success-soft">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <?php _e('Ativo') ?>
                                        </span>
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="status" 
                                           id="status_disable" 
                                           value="0" 
                                           <?php _e(get_data($result, "status", "radio", 0)) ?>>
                                    <label class="form-check-label" for="status_disable">
                                        <span class="badge bg-secondary-soft">
                                            <i class="fas fa-times-circle me-1"></i>
                                            <?php _e('Inativo') ?>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="form-text">
                                <?php _e("Grupos inativos não serão considerados nas operações automáticas") ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <?php _e("Informações") ?>
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    <?php if (get_data($result, "changed")): ?>
                                    <li class="mb-2">
                                        <small class="text-muted d-block">
                                            <?php _e("Última atualização") ?>
                                        </small>
                                        <span>
                                            <?php _ec(time_elapsed_string(get_data($result, "changed"))) ?>
                                        </span>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (get_data($result, "created")): ?>
                                    <li>
                                        <small class="text-muted d-block">
                                            <?php _e("Data de criação") ?>
                                        </small>
                                        <span>
                                            <?php _ec(datetime_show(get_data($result, "created"))) ?>
                                        </span>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light border-0 p-4">
                <div class="d-flex justify-content-between">
                    <a href="<?php _ec(get_module_url()) ?>" class="btn btn-light">
                        <i class="fas fa-times me-1"></i>
                        <?php _e("Cancelar") ?>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        <?php _e(get_data($result, "ids") ? "Atualizar" : "Criar Grupo") ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.bg-secondary-soft { background-color: rgba(108, 117, 125, 0.1) !important; color: #6c757d !important; }
.bg-success-soft { background-color: rgba(25, 135, 84, 0.1) !important; color: #198754 !important; }
.form-switch .form-check-input { margin-top: 0.25rem; }
.badge { font-weight: 500; padding: 0.5em 0.75em; }
</style>

<script type="text/javascript">
$(function(){
    Core.tagsinput();
});
</script>