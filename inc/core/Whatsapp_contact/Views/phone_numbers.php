<form>
    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between py-4">
            <div class="d-flex align-items-center">
                <a href="<?php _ec(get_module_url()) ?>" class="btn btn-light me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="fs-2 fw-bold mb-0">
                        <?php _e(get_data($contact, "name")) ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <?php _e("Gerenciamento de números de telefone") ?>
                    </p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php _ec(get_module_url("popup_import_contact/" . get_data($contact, "ids"))) ?>" 
                   class="btn btn-primary actionItem" 
                   data-popup="ImportContactModal">
                    <i class="fas fa-file-import me-2"></i>
                    <?php _e("Importar") ?>
                </a>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" 
                                   class="form-control border-0 bg-light ajax-pages-search ajax-filter" 
                                   name="keyword" 
                                   placeholder="<?php _e("Buscar números...") ?>" 
                                   aria-label="Search">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="d-flex justify-content-md-end gap-2">
                            <a href="<?php _e(get_module_url("delete_phone")) ?>" 
                               class="btn btn-light actionMultiItem" 
                               data-confirm="<?php _e('Tem certeza que deseja excluir estes itens?') ?>" 
                               data-call-success="Core.ajax_pages();">
                                <i class="fas fa-trash-alt text-danger me-2"></i>
                                <?php _e("Excluir Selecionados") ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="ajax-pages" 
                     data-url="<?php _ec(get_module_url("ajax_list_phone_numbers/" . get_data($contact, "id"))) ?>" 
                     data-response=".ajax-result" 
                     data-per-page="<?php _ec(get_data($datatable, "per_page")) ?>"
                     data-current-page="<?php _ec(get_data($datatable, "current_page")) ?>"
                     data-total-items="<?php _ec(get_data($datatable, "total_items")) ?>">

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 rounded-start ps-4" width="40">
                                        <div class="form-check">
                                            <input class="form-check-input checkbox-all" type="checkbox">
                                        </div>
                                    </th>
                                    <th class="border-0" width="60"><?php _e("No.") ?></th>
                                    <th class="border-0" width="200"><?php _e("Número") ?></th>
                                    <th class="border-0" width="100"><?php _e("Status") ?></th>
                                    <th class="border-0 rounded-end"><?php _e("Parâmetros") ?></th>
                                </tr>
                            </thead>
                            <tbody class="ajax-result">
                                <tr>
                                    <td colspan="5">
                                        <div class="d-flex align-items-center justify-content-center py-5">
                                            <div class="text-center">
                                                <img src="<?php _e(get_theme_url()) ?>Assets/img/empty2.png" 
                                                     class="mw-100 mb-3" style="max-height: 150px;" 
                                                     alt="No numbers">
                                                <p class="text-muted mb-0">
                                                    <?php _e("Nenhum número encontrado") ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        <nav class="ajax-pagination"></nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}
.table > tbody > tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}
.input-group-text {
    border-right: 0;
}
.form-control:focus {
    box-shadow: none;
    border-color: #dee2e6;
}
.card {
    border-radius: 0.5rem;
}
.rounded-start {
    border-top-left-radius: 0.5rem !important;
    border-bottom-left-radius: 0.5rem !important;
}
.rounded-end {
    border-top-right-radius: 0.5rem !important;
    border-bottom-right-radius: 0.5rem !important;
}
</style>

<script type="text/javascript">
$(function(){
    Core.ajax_pages();
});
</script>

