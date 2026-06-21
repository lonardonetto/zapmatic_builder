<!-- Modern Header Section -->
<div class="container-fluid px-4 py-4">
    <div class="row g-4 align-items-center justify-content-between">
        <div class="col-12 col-md-6">
            <div class="d-flex align-items-center">
                <div class="position-relative">
                    <i class="<?php _ec($config['icon']) ?> fs-2 me-2" style="color: <?php _ec($config['color']) ?>;"></i>
                </div>
                <div>
                    <h1 class="fs-2 fw-bold mb-0"><?php _e($config['name']) ?></h1>
                    <p class="text-muted mb-0"><?php _e("Gerencie seus grupos de contatos do WhatsApp") ?></p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="d-flex gap-3 justify-content-md-end">
                <!-- Search Box -->
                <div class="flex-grow-1 flex-md-grow-0" style="max-width: 300px;">
                    <div class="input-group">
                        <span class="input-group-text border-0 bg-light">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-0 bg-light ajax-pages-search ajax-filter" 
                               name="keyword" placeholder="<?php _e("Buscar grupos...") ?>" 
                               aria-label="Search">
                    </div>
                </div>
                <!-- Delete Selected Button -->
                <div class="delete-selected d-none">
                    <form class="actionForm" 
                          action="<?php _e(get_module_url("delete")) ?>" 
                          method="POST" 
                          data-redirect="<?php _e(get_module_url()) ?>"
                          data-call-after="clearSelection();">
                        <div id="selected-ids-container"></div>
                        <button type="submit" 
                                class="btn btn-danger actionBtn" 
                                data-confirm="<?php _e('Tem certeza que deseja excluir todos os grupos selecionados?') ?>">
                            <i class="fas fa-trash-alt me-2"></i>
                            <span class="button-text"><?php _e("Excluir Selecionados") ?></span>
                            <div class="spinner-border spinner-border-sm d-none" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </button>
                    </form>
                </div>
                <a href="<?php _ec(get_module_url("index/update")) ?>" 
                   class="btn btn-primary d-flex align-items-center">
                    <i class="fas fa-plus me-2"></i>
                    <?php _e("Novo Grupo") ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (get_data($datatable, "total_items") != 0) : ?>
<div class="container-fluid px-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 ajax-pages" 
                       data-url="<?php _ec(get_module_url("ajax_list")) ?>" 
                       data-response=".ajax-result" 
                       data-per-page="<?php _ec(get_data($datatable, "per_page")) ?>" 
                       data-current-page="<?php _ec(get_data($datatable, "current_page")) ?>" 
                       data-total-items="<?php _ec(get_data($datatable, "total_items")) ?>">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 rounded-start ps-4">
                                <div class="form-check">
                                    <input class="form-check-input checkbox-all" type="checkbox" id="checkAll">
                                </div>
                            </th>
                            <th class="border-0"><?php _e('Nome do Grupo') ?></th>
                            <th class="border-0 text-center">
                                <i class="fas fa-users text-primary me-1"></i> <?php _e("Total") ?>
                            </th>
                            <th class="border-0 text-center">
                                <i class="fas fa-check-circle text-success me-1"></i> <?php _e("Válidos") ?>
                            </th>
                            <th class="border-0 text-center">
                                <i class="fas fa-times-circle text-danger me-1"></i> <?php _e('Inválidos') ?>
                            </th>
                            <th class="border-0 text-center">
                                <i class="fas fa-sync text-info me-1"></i> <?php _e('Validando') ?>
                            </th>
                            <th class="border-0 text-center">
                                <i class="fas fa-clone text-warning me-1"></i> <?php _e("Duplicados") ?>
                            </th>
                            <th class="border-0 text-center">
                                <i class="fas fa-circle text-primary me-1"></i> <?php _e("Status") ?>
                            </th>
                            <th class="border-0 rounded-end text-end pe-4">
                                <i class="fas fa-cog text-muted"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="ajax-result">
                        <!-- Dynamic content will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        <nav class="ajax-pagination"></nav>
    </div>
</div>

<script>
    setTimeout(function() {
        var wa_server = '<?php echo get_option('whatsapp_server_url', '') ?>';
        if (!contact_socket && wa_server != '') {
            var contact_socket = io(wa_server, {
                transports: ['polling']
            });
            contact_socket.on('check_phone_update_<?php echo get_team("id"); ?>', (args) => {
                Core.ajax_pages();
            });
        }
    }, 2000);

    $(function() {
        Core.ajax_pages();

        // Show/Hide Delete Selected button and update hidden inputs
        function toggleDeleteButton() {
            var checkedBoxes = $('.checkbox-item:checked');
            var deleteBtn = $('.delete-selected');
            var container = $('#selected-ids-container');
            
            if (checkedBoxes.length > 0) {
                container.empty(); // Limpa os inputs anteriores
                checkedBoxes.each(function() {
                    container.append(
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'ids[]',
                            value: $(this).val()
                        })
                    );
                });
                deleteBtn.removeClass('d-none');
            } else {
                container.empty();
                deleteBtn.addClass('d-none');
            }
        }

        // Handle checkbox events
        $(document).on('change', '.checkbox-item, .checkbox-all', function() {
            toggleDeleteButton();
        });

        // Handle "Select All" checkbox
        $(document).on('change', '#checkAll', function() {
            $('.checkbox-item').prop('checked', $(this).prop('checked'));
            toggleDeleteButton();
        });

        // Função para mostrar o loading no botão
        function toggleButtonLoading(button, show) {
            var spinner = button.find('.spinner-border');
            var text = button.find('.button-text');
            
            if (show) {
                text.addClass('d-none');
                spinner.removeClass('d-none');
                button.prop('disabled', true);
            } else {
                spinner.addClass('d-none');
                text.removeClass('d-none');
                button.prop('disabled', false);
            }
        }

        // Função para limpar seleção após exclusão
        window.clearSelection = function() {
            $('.checkbox-item, #checkAll').prop('checked', false);
            $('.delete-selected').addClass('d-none');
            $('#selected-ids-container').empty();
            Core.ajax_pages();
        };

        // Interceptar submit apenas para validação (apenas para formulários de exclusão)
        $(document).on('submit', '.actionForm[action*="delete"]', function(e) {
            var form = $(this);
            var ids = [];
            
            $('.checkbox-item:checked').each(function() {
                ids.push($(this).val());
            });

            if (ids.length === 0) {
                e.preventDefault();
                Core.notify("Selecione pelo menos um item para excluir", "error");
                return false;
            }
            
            // Se chegou aqui, tem itens selecionados - deixar o sistema padrão tratar
            return true;
        });
    });
</script>

<?php else : ?>
<div class="container-fluid px-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="text-center py-5">
                <img src="<?php _e(get_theme_url()) ?>Assets/img/empty2.png" 
                     class="mw-100 mb-4" style="max-height: 300px;" 
                     alt="No contacts">
                <h3 class="fw-normal text-muted mb-3"><?php _e("Nenhum grupo de contatos encontrado") ?></h3>
                <a href="<?php _ec(get_module_url("index/update")) ?>" 
                   class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    <?php _e("Criar Novo Grupo") ?>
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif ?>

<!-- Custom Styles -->
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
.btn-group > .btn {
    padding: 0.375rem 0.75rem;
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
.delete-selected {
    transition: all 0.3s ease;
}
.actionBtn {
    position: relative;
    min-width: 180px;
}
.actionBtn .spinner-border {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
}
</style>