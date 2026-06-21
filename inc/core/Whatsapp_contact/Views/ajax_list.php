<?php if (!empty($result)) { ?>

    <?php foreach ($result as $key => $value) : ?>
        <tr>
            <td class="ps-4">
                <div class="form-check">
                    <input class="form-check-input checkbox-item" type="checkbox" name="ids[]" value="<?php _e($value->ids) ?>">
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="mb-1">
                            <a href="<?php _e(get_module_url("index/update/" . $value->ids)) ?>" 
                               class="text-dark text-decoration-none">
                                <?php _ec($value->name) ?>
                            </a>
                        </h6>
                        <span class="text-muted small">ID: <?php _e($value->id) ?></span>
                    </div>
                </div>
            </td>
            <td class="text-center contact-group-count-<?php _e($value->id) ?>">
                <span class="badge bg-light text-dark">
                    <?php _ec(number_format($value->count ?? 0)) ?>
                </span>
            </td>
            <td class="text-center contact-group-valid-<?php _e($value->id) ?>">
                <span class="badge bg-success-soft">
                    <?php _ec(number_format($value->count_valid ?? 0)) ?>
                </span>
            </td>
            <td class="text-center">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <span class="badge bg-danger-soft contact-group-invalid-<?php _e($value->id) ?>">
                        <?php _ec(number_format($value->count_invalid ?? 0)) ?>
                    </span>
                    <?php if ($value->count_invalid > 0): ?>
                    <a href="<?php _e(get_module_url("delete_invalid/" . $value->ids)) ?>" 
                       class="btn btn-sm btn-link text-danger p-0 actionItem" 
                       data-confirm="<?php _e('Tem certeza que deseja excluir os números inválidos?') ?>" 
                       data-call-after="Core.ajax_pages();">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </td>
            <td class="text-center contact-group-process-<?php _e($value->id) ?>">
                <span class="badge bg-info-soft">
                    <?php _ec(number_format($value->count_process ?? 0)) ?>
                </span>
            </td>
            <td class="text-center">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <span class="badge bg-warning-soft contact-group-duplicate-<?php _e($value->id) ?>">
                        <?php _ec(number_format($value->count - $value->count_non_repeated ?? 0)) ?>
                    </span>
                    <?php if (($value->count - $value->count_non_repeated) > 0): ?>
                    <a href="<?php _e(get_module_url("delete_duplicate/" . $value->ids)) ?>" 
                       class="btn btn-sm btn-link text-danger p-0 actionItem" 
                       data-confirm="<?php _e('Tem certeza que deseja excluir os números duplicados?') ?>" 
                       data-call-after="Core.ajax_pages();">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </td>
            <td class="text-center">
                <?php if($value->status): ?>
                    <span class="badge bg-success">Ativo</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Inativo</span>
                <?php endif; ?>
            </td>
            <td class="text-end pe-4">
                <div class="btn-group dropup">
                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="dropdownMenuButton_<?php _e($value->ids) ?>" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download text-primary"></i>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton_<?php _e($value->ids) ?>">
                        <li><a class="dropdown-item" href="<?php _e(get_module_url("exportar_csv/" . $value->ids . "/comma")) ?>"><?php _e("Exportar CSV (vírgula)") ?></a></li>
                        <li><a class="dropdown-item" href="<?php _e(get_module_url("exportar_csv/" . $value->ids . "/semicolon")) ?>"><?php _e("Exportar CSV (ponto e vírgula)") ?></a></li>
                    </ul>
                    <a href="<?php _e(get_module_url("index/update/" . $value->ids)) ?>" 
                       class="btn btn-sm btn-light" 
                       title="<?php _e('Editar') ?>">
                        <i class="fas fa-edit text-info"></i>
                    </a>
                    <a href="<?php _e(get_module_url("index/phone_numbers/" . $value->ids)) ?>" 
                       class="btn btn-sm btn-light" 
                       title="<?php _e('Lista de números') ?>">
                        <i class="fas fa-list text-warning"></i>
                    </a>
                    <a href="<?php _e(get_module_url("delete/" . $value->ids)) ?>" 
                       class="btn btn-sm btn-light actionItem" 
                       data-confirm="<?php _e('Tem certeza que deseja excluir este grupo?') ?>" 
                       data-call-after="Core.ajax_pages();" 
                       title="<?php _e('Excluir') ?>">
                        <i class="fas fa-trash-alt text-danger"></i>
                    </a>
                </div>
            </td>
        </tr>
    <?php endforeach ?>

<?php } else { ?>
    <tr>
        <td colspan="9">
            <div class="d-flex align-items-center justify-content-center py-5">
                <div class="text-center">
                    <img src="<?php _e(get_theme_url()) ?>Assets/img/empty.png" 
                         class="mw-100 mb-3" style="max-height: 150px;" 
                         alt="No contacts">
                    <p class="text-muted mb-0"><?php _e("Nenhum grupo encontrado") ?></p>
                </div>
            </div>
        </td>
    </tr>
<?php } ?>

<style>
.bg-success-soft { background-color: rgba(25, 135, 84, 0.1) !important; color: #198754 !important; }
.bg-danger-soft { background-color: rgba(220, 53, 69, 0.1) !important; color: #dc3545 !important; }
.bg-warning-soft { background-color: rgba(255, 193, 7, 0.1) !important; color: #ffc107 !important; }
.bg-info-soft { background-color: rgba(13, 202, 240, 0.1) !important; color: #0dcaf0 !important; }
.btn-group > .btn { border-radius: 0.25rem !important; margin: 0 1px; }
.badge { font-weight: 500; padding: 0.5em 0.75em; }
</style>