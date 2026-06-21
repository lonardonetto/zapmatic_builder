<?php if ( !empty($result) ){ ?>
	
	<?php foreach ($result as $key => $value): ?>
		
		<div class="item col-md-6 col-sm-12 mb-4">
            <div class="card b-r-10">
                <div class="card-body position-relative p-r-50">
                    <i class="fad fa-comment-alt-lines fs-90 position-absolute text-success opacity-25 r-30"></i>
                    <div class="mb-3">
                        <h3 class="text-dark mb-1"><?php _e($value->name)?></h3>

                        <?php
                            // Badge de status Meta (type=66) vinculado ao template interno (type=2)
                            $metaBadge = null;
                            $metaStatus = null;
                            if (isset($meta_status_by_template_ids) && is_array($meta_status_by_template_ids) && isset($meta_status_by_template_ids[$value->ids])) {
                                $metaStatus = $meta_status_by_template_ids[$value->ids];
                                $st = strtoupper((string)($metaStatus['status'] ?? ''));
                                $cat = (string)($metaStatus['category'] ?? '');
                                $prev = (string)($metaStatus['previous_category'] ?? '');

                                $class = 'badge-light';
                                if ($st === 'APPROVED') $class = 'badge-light-success';
                                elseif ($st === 'PENDING' || $st === 'PAUSED') $class = 'badge-light-warning';
                                elseif ($st === 'REJECTED' || $st === 'ERROR') $class = 'badge-light-danger';

                                $label = $st !== '' ? $st : 'META';
                                $metaBadge = '<span class="badge ' . $class . ' me-2">META: ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';

                                if ($cat !== '') {
                                    $metaBadge .= '<span class="badge badge-light-info me-2">' . htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') . '</span>';
                                }
                                if ($prev !== '' && $prev !== $cat) {
                                    $metaBadge .= '<span class="badge badge-light">prev: ' . htmlspecialchars($prev, ENT_QUOTES, 'UTF-8') . '</span>';
                                }
                            } else {
                                // Se o template tiver modo meta_official habilitado mas ainda sem status, mostra "Não submetido"
                                $dataTmp = $value->data ? json_decode($value->data, true) : [];
                                $enabled = (int)($dataTmp['meta_official']['enabled'] ?? 0) === 1;
                                if ($enabled) {
                                    $metaBadge = '<span class="badge badge-light me-2">META: Não submetido</span>';
                                }
                            }
                        ?>

                        <?php if (!empty($metaBadge)): ?>
                            <div class="mb-2"><?php echo $metaBadge; ?></div>
                        <?php endif; ?>

                        <?php
                        	$count_btn = 0;
                        	if($value->data != ""){
                        		$data = json_decode($value->data);

                        		if(isset($data->templateButtons)){
                        			$count_btn = count($data->templateButtons);
                        		}
                        	}
                        ?>
                        <div><?php _ec(  sprintf( __('%d buttons'), $count_btn ) )?></div>
                    </div>
                    <div class="d-flex">
                        <a href="<?php _e( get_module_url("index/update/".$value->ids) )?>" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative"><i class="position-absolute l-11 fs-14 fal fa-edit"></i></a>
                        <a href="<?php _e( get_module_url("delete/".$value->ids) )?>" data-id="<?php _ec( $value->ids )?>" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative actionItem" data-confirm="<?php _e('Are you sure to delete this items?')?>" data-call-success="Core.ajax_pages();"><i class="position-absolute l-11 fs-14 fal fa-trash-alt"></i></a>
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