<div class="container py-4">
    <form id="leads-filter" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label"><?php _e('Buscar por nome ou telefone') ?></label>
                <input type="text" name="keyword" class="form-control form-control-solid ajax-filter" placeholder="Maria, +551199...">
            </div>
            <div class="col-md-3">
                <label class="form-label"><?php _e('Conta de origem') ?></label>
                <select name="instance" class="form-select form-select-solid ajax-filter">
                    <option value=""><?php _e('Todas as contas') ?></option>
                    <?php if(!empty($instances)): foreach ($instances as $instance): ?>
                        <option value="<?php _ec($instance->token) ?>"><?php _ec($instance->name) ?> (<?php _ec(preg_replace('/@.*/', '', $instance->token)) ?>)</option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label"><?php _e('Primeiro contato de') ?></label>
                <input type="date" name="date_from" class="form-control form-control-solid ajax-filter">
            </div>
            <div class="col-md-2">
                <label class="form-label"><?php _e('Primeiro contato até') ?></label>
                <input type="date" name="date_to" class="form-control form-control-solid ajax-filter">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><?php _e('Filtrar') ?></button>
                <button type="button" id="btn-reset" class="btn btn-light w-100"><?php _e('Limpar') ?></button>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><?php _e('Leads captados via WhatsApp') ?></h5>
                <small class="text-muted"><?php _e('Mostra todos os contatos que já interagiram com seus números.') ?></small>
            </div>
            <div class="d-flex gap-2">
                <button id="btn-export" class="btn btn-success">
                    <i class="fad fa-file-export me-1"></i> <?php _e('Exportar CSV') ?>
                </button>
                <button id="btn-delete" class="btn btn-danger">
                    <i class="fad fa-trash-alt me-1"></i> <?php _e('Excluir selecionados') ?>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="ajax-pages" 
                 data-url="<?php _ec(get_module_url('ajax_list')) ?>"
                 data-response=".ajax-result"
                 data-per-page="30"
                 data-current-page="1"
                 data-total-items="0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width:40px">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="check-all">
                                    </div>
                                </th>
                                <th><?php _e('Nome') ?></th>
                                <th><?php _e('Telefone') ?></th>
                                <th><?php _e('Conta de origem') ?></th>
                                <th><?php _e('Primeiro contato') ?></th>
                                <th><?php _e('Último contato') ?></th>
                                <th class="text-center"><?php _e('Mensagens não lidas') ?></th>
                            </tr>
                        </thead>
                        <tbody class="ajax-result">
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <?php _e('Use os filtros acima e clique em "Filtrar" para carregar os leads.') ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <nav class="m-3 ajax-pagination"></nav>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const $form = $('#leads-filter');
    const $container = $('.ajax-pages');

    function reload(){
        $container.attr('data-current-page', 1);
        Core.ajax_pages();
    }

    $form.on('submit', function(e){
        e.preventDefault();
        reload();
    });

    $('#btn-reset').on('click', function(){
        $form[0].reset();
        reload();
    });

    $('#btn-export').on('click', function(){
        const params = $form.serialize();
        window.location = '<?php _ec(get_module_url("export")) ?>' + '?' + params;
    });

    $('#btn-delete').on('click', function(){
        const $btn = $(this);
        const selected = $('.ajax-result input.checkbox-item:checked').map(function(){
            return $(this).val();
        }).get();

        if(selected.length === 0){
            if (typeof Core !== 'undefined' && typeof Core.notify === 'function') {
                Core.notify('<?php _e('Selecione pelo menos um lead') ?>', 'error');
            }
            return;
        }

        const runDelete = function(){
            const payload = { csrf: csrf, ids: selected };

            Core.ajax_post($btn, '<?php _ec(get_module_url("delete")) ?>', payload, function(){
                reload();
            });
        };

        if (typeof Core !== 'undefined' && typeof Core.showConfirmDialog === 'function') {
            Core.showConfirmDialog({
                title: '<?php _e('Excluir leads selecionados') ?>',
                message: '<?php _e('Tem certeza que deseja excluir os leads selecionados?') ?>',
                confirmText: '<?php _e('Excluir leads') ?>',
                readyHint: '<?php _e('Se estiver tudo certo, confirme para excluir os leads selecionados.') ?>',
                onConfirm: runDelete
            });
            return;
        }

        if (window.confirm('<?php _e('Tem certeza que deseja excluir os leads selecionados?') ?>')) {
            runDelete();
        }
    });

    $(document).on('change', '#check-all', function(){
        $('.ajax-result input.checkbox-item').prop('checked', $(this).prop('checked'));
    });

    $(document).on('change', '.ajax-result input.checkbox-item', function(){
        if(!$(this).prop('checked')){
            $('#check-all').prop('checked', false);
        }
    });

    const ensureCoreAndReload = () => {
        if (typeof Core !== 'undefined' && typeof Core.ajax_pages === 'function') {
            reload();
            return true;
        }
        return false;
    };

    if (!ensureCoreAndReload()) {
        const intervalId = setInterval(() => {
            if (ensureCoreAndReload()) {
                clearInterval(intervalId);
            }
        }, 100);
        setTimeout(() => {
            if (ensureCoreAndReload()) {
                clearInterval(intervalId);
            }
        }, 500);
    }
})();
</script>
