<?php if ( !empty($result) ){ ?>
	
	<?php foreach ($result as $key => $value): ?>
		
		<div class="item col-md-6 col-sm-12 mb-4">
            <div class="card b-r-10">
                <div class="card-body position-relative p-r-50">
                    <i class="fad fa-comment-alt-lines fs-90 position-absolute text-success opacity-25 r-30"></i>
                    <div class="mb-3">
                        <h3 class="text-dark"><?php _e($value->name)?></h3>

                        <?php
                        	$count_sections = 0;
                        	if($value->data != ""){
                        		$data = json_decode($value->data);

                        		if(isset($data->sections)){
                        			$count_sections = count($data->sections);
                        		}
                        	}
                        ?>
                        <div><?php _ec(  sprintf( __('%d sections'), $count_sections ) )?></div>
                    </div>
                    <div class="d-flex">
                        <a href="<?php _e( get_module_url("index/update/".$value->ids) )?>" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative" title="<?php _e('Edit')?>"><i class="position-absolute l-11 fs-14 fal fa-edit"></i></a>
                        <a href="#" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative" onclick="duplicateTemplate('<?php _ec($value->ids)?>', '<?php _e(get_module_url('duplicate'))?>')" title="<?php _e('Duplicate')?>"><i class="position-absolute l-11 fs-14 fal fa-copy"></i></a>
                        <a href="<?php _e( get_module_url("delete/".$value->ids) )?>" data-id="<?php _ec( $value->ids )?>" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative actionItem" data-confirm="<?php _e('Are you sure to delete this items?')?>" data-call-success="Core.ajax_pages();" title="<?php _e('Delete')?>"><i class="position-absolute l-11 fs-14 fal fa-trash-alt"></i></a>
                    </div>
                </div>
            </div>
        </div>

	<?php endforeach ?>

<?php }else{ ?>
	<div class="mw-400 container d-flex align-items-center align-self-center h-100 py-5">
	    <div>
	        <div class="text-center px-4">
	            <img class="mw-100 mh-300px" alt="" src="<?php _e( get_theme_url() ) ?>Assets/img/empty2.png">
	        </div>
	    </div>
	</div> 
<?php }?>

<script type="text/javascript">
function duplicateTemplate(id, url) {
    var proceed = function() {
        var actionDialog = null;
        if (typeof Core !== 'undefined' && typeof Core.showActionDialog === 'function') {
            actionDialog = Core.showActionDialog({
                type: 'duplicate',
                icon: 'fad fa-copy',
                title: '<?php _e("Duplicando template") ?>',
                message: '<?php _e("Estamos criando uma cópia do template selecionado.") ?>'
            });
        } else {
            Core.overplay();
        }

        // Prepara os dados
        var data = {
            'id': id
        };

        // Faz a requisição
        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(result) {
                // Remove loading
                Core.overplay(false);

                if (actionDialog && typeof Core.finishActionDialog === 'function') {
                    Core.finishActionDialog(result.status || 'success', result.message || '<?php _e("Template duplicado com sucesso.") ?>', actionDialog);
                }

                if (result.status == 'success') {
                    // Mostra mensagem de sucesso
                    Core.notify(result.message, 'success');

                    // Recarrega a página após 1 segundo
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Mostra mensagem de erro
                    Core.notify(result.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                // Remove loading
                Core.overplay(false);

                if (actionDialog && typeof Core.finishActionDialog === 'function') {
                    Core.finishActionDialog('error', '<?php _e("Ocorreu um erro ao duplicar o template")?>: ' + error, actionDialog);
                }

                // Mostra mensagem de erro
                Core.notify('<?php _e("Ocorreu um erro ao duplicar o template")?>: ' + error, 'error');
            }
        });
    };

    if (typeof Core !== 'undefined' && typeof Core.showConfirmDialog === 'function') {
        Core.showConfirmDialog({
            title: '<?php _e("Duplicar template") ?>',
            message: '<?php _e("Tem certeza que deseja duplicar este template?") ?>',
            confirmText: '<?php _e("Duplicar") ?>',
            readyHint: '<?php _e("Se estiver tudo certo, confirme para criar uma cópia deste template.") ?>',
            onConfirm: proceed
        });
        return;
    }

    if (window.confirm('<?php _e("Tem certeza que deseja duplicar este template?")?>')) {
        proceed();
    }
}
</script>
