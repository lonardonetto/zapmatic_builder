<div class="modal fade" id="ImportContactModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i>
                    <?php _e("Importar Contatos") ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="actionForm" action="<?php _eC(get_module_url("add_contact/" . get_data($result, "ids"))) ?>" method="POST" data-redirect="">
                    <!-- Import Type Selector -->
                    <ul class="nav nav-pills nav-fill bg-light rounded-3 p-2 mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <input type="radio" class="btn-check" name="type" id="type_import_csv" value="1" checked>
                            <label class="nav-link active rounded-3" for="type_import_csv" data-bs-toggle="tab" data-bs-target="#import_csv" role="tab">
                                <i class="fas fa-file-csv me-2"></i>
                                <?php _e("Upload CSV") ?>
                            </label>
                        </li>
                        <li class="nav-item" role="presentation">
                            <input type="radio" class="btn-check" name="type" id="type_import_form" value="2">
                            <label class="nav-link rounded-3" for="type_import_form" data-bs-toggle="tab" data-bs-target="#import_form" role="tab">
                                <i class="fas fa-keyboard me-2"></i>
                                <?php _e("Via Formulário") ?>
                            </label>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- CSV Import -->
                        <div class="tab-pane fade show active" id="import_csv" role="tabpanel">
                            <div class="text-center mb-4">
                                <img src="<?php _e(get_theme_url()) ?>Assets/img/file-upload.png" 
                                     class="mw-100 mb-4" style="max-height: 120px;" 
                                     alt="Upload CSV">
                                <h6 class="fw-bold mb-1"><?php _e("Importe seus contatos via CSV") ?></h6>
                                <p class="text-muted mb-0"><?php _e("Faça upload de um arquivo CSV com seus contatos") ?></p>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="<?php _e(get_module_url("download_example_upload_csv")) ?>" 
                                   class="btn btn-light">
                                    <i class="fas fa-download me-2"></i>
                                    <?php _e("Baixar Modelo") ?>
                                </a>
                                <div class="position-relative">
                                    <button type="button" class="btn btn-primary w-100">
                                        <i class="fas fa-upload me-2"></i>
                                        <?php _e("Selecionar Arquivo") ?>
                                    </button>
                                    <input id="import_whatsapp_contact" 
                                           type="file" 
                                           name="files[]"
                                           class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer"
                                           multiple="" 
                                           data-action="<?php _ec(get_module_url("do_import_contact/" . get_data($result, "ids"))) ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Form Import -->
                        <div class="tab-pane fade" id="import_form" role="tabpanel">
                            <div class="mb-4">
                                <label class="form-label fw-medium">
                                    <?php _e('Adicionar múltiplos números') ?>
                                </label>
                                <div class="form-text mb-2">
                                    <?php _e("Digite um número por linha. Formatos aceitos:") ?>
                                </div>
                                <textarea class="form-control bg-light border-0" 
                                          name="phone_numbers" 
                                          rows="10" 
                                          placeholder="841234567890
840123456789
+840123456798
84123456789-1618177713@g.us"></textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    <?php _e("Cancelar") ?>
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?php _e("Importar") ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.modal-content {
    border-radius: 1rem;
}
.nav-pills .nav-link {
    color: var(--bs-body-color);
    font-weight: 500;
}
.nav-pills .nav-link.active {
    background-color: var(--bs-primary);
    color: #fff;
}
.form-control:focus {
    box-shadow: none;
    border-color: #dee2e6;
}
.cursor-pointer {
    cursor: pointer;
}
textarea {
    resize: none;
}
</style>

<script type="text/javascript">
$(function(){
    Whatsapp.import_contact();
    
    // Ensure proper tab switching
    $('#ImportContactModal input[name="type"]').on('change', function() {
        const target = $(this).siblings('label').data('bs-target');
        $(target).tab('show');
    });
});
</script>