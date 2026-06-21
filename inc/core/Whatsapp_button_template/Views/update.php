<?php
$data = false;
$sections = false;
$desc = "";
$image = "";
$available_flows = isset($available_flows) && is_array($available_flows) ? $available_flows : [];
$request = \Config\Services::request();
$wa_default_redirect = get_module_url();
$wa_return_url = trim((string) $request->getGet('wa_return'));
$wa_return_redirect = $wa_default_redirect;
if ($wa_return_url !== '') {
    $return_host = parse_url($wa_return_url, PHP_URL_HOST);
    $base_host = parse_url(base_url(), PHP_URL_HOST);
    if ((strpos($wa_return_url, '/') === 0 && strpos($wa_return_url, '//') !== 0) || ($return_host && $base_host && strcasecmp($return_host, $base_host) === 0)) {
        $wa_return_redirect = $wa_return_url;
    }
}
$wa_has_return = $wa_return_url !== '' && $wa_return_redirect !== $wa_default_redirect;
if( !empty($result) ){
    $data = json_decode($result->data);

    if( !empty($data) && isset($data->sections) && count($data->sections) != 0 ){
        $sections = $data->sections;
    }

    if(get_data($data, "caption")){
    	$desc = get_data($data, "caption");
    }else{
    	$desc = get_data($data, "text");
    }

    if( isset($data->image) && isset($data->image->url) ){
    	$image = remove_file_path($data->image->url);
    }

}
?>

<form class="actionForm" action="<?php _eC( get_module_url("save/".get_data($result, "ids")) )?>" method="POST" data-redirect="<?php _ec( $wa_return_redirect ) ?>">
	<div class="container py-5">
		<div class="card b-r-6 mb-4">
			<div class="card-header">
				<div class="card-title"><i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e("Button template")?></div>
				<?php if ($wa_has_return): ?>
					<div class="card-toolbar">
						<a href="<?php _ec($wa_return_redirect) ?>" class="btn btn-sm btn-light-primary b-r-30"><i class="fad fa-arrow-left me-1"></i><?php _e('Voltar para a tela anterior') ?></a>
					</div>
				<?php endif; ?>
			</div>

			<div class="card-body">
				<div class="mb-4">
					<label class="form-label"><?php _e("Name")?></label>
					<input type="text" name="name" class="form-control form-control-solid" placeholder="<?php _e("Enter template name")?>" value="<?php _ec( get_data($result, "name") )?>">
				</div>

				<div class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex flex-column flex-sm-row w-100 p-5 mb-10">
					<span class="fs-2hx text-primary me-4 mb-5 mb-sm-0">
						<i class="fad fa-info-circle text-primary fs-1"></i>
					</span>
					<div class="d-flex flex-column pe-0 pe-sm-10">
						<h5 class="mb-2 text-primary"><?php _e("Guia Completo - Regras Cloud API (Meta)")?></h5>
						<span class="fs-12 text-gray-800">
							<ul class="mb-0">
								<li><strong><?php _e("Títulos Únicos:")?></strong> <span class="text-danger"><?php _e("Cada botão DEVE ter um texto diferente.")?></span> <?php _e("Títulos repetidos causam falha no envio.")?></li>
								<li><strong><?php _e("Botão de Link (URL):")?></strong> <?php _e("A Cloud API permite link real APENAS se houver 1 único botão na mensagem. Se houver mais de um, todos serão convertidos para texto para garantir a entrega.")?></li>
								<li><strong><?php _e("Botão de Chamada:")?></strong> <?php _e("Não é suportado em mensagens dinâmicas e será convertido para texto.")?></li>
								<li><strong><?php _e("Por que converter para Texto?")?></strong> 
									<ol class="mt-1">
										<li><?php _e("Permite que o Chatbot detecte o clique e continue o atendimento automático.")?></li>
										<li><?php _e("Permite usar até 10 botões de texto na mesma mensagem via Baileys Native Flow. Para submissão Cloud API/Meta oficial, o limite oficial continua separado.")?></li>
									</ol>
								</li>
							</ul>
						</span>
					</div>
				</div>
				
				<div class="mb-4">
					<label class="form-label"><?php _e("Title Button")?></label>
					<input type="text" name="title" class="form-control form-control-solid" placeholder="<?php _e("Enter Title Button")?>" value="<?php _ec( get_data($data, "title") )?>">
				</div>
				
				<?php if ( permission("whatsapp_send_media") ): ?>
				<label class="form-label"><?php _e("Main image")?></label>
				<?php echo view_cell('\Core\File_manager\Controllers\File_manager::mini', ["type" => "image", "select_multi" => 0]) ?>

				<script type="text/javascript">
					$(function(){
						File_manager.loadSelectedFiles(["<?php _ec( remove_file_path(  $image ) )?>"]);
					});
				</script>

				<?php endif ?>

				<label class="form-label"><?php _e("Main description")?></label>
				<?php echo view_cell('\Core\Caption\Controllers\Caption::block', ['name' => 'desc', 'placeholder' => 'Enter main description', 'value' => $desc]) ?>

				<div class="mb-4">
					<label class="form-label"><?php _e("Footer")?></label>
					<input type="text" name="footer" class="form-control form-control-solid" placeholder="<?php _e("Enter footer content")?>" value="<?php _ec( get_data($data, "footer") )?>">
				</div>

				<?php if ( (int)permission("cloud_api_enabled") == 1 ): ?>
				<?php
					$meta_cfg = isset($data->meta_official) ? $data->meta_official : (object)[];
					$meta_enabled = (int) get_data($meta_cfg, "enabled") === 1;
				?>
				<div class="card border border-dashed b-r-6 mt-5">
					<div class="card-header">
						<div class="card-title"><?php _e("Oficial (Meta) - Enviar para análise")?></div>
					</div>
					<div class="card-body">
						<div class="form-check form-switch mb-4">
							<input class="form-check-input" type="checkbox" role="switch" id="meta_enabled" name="meta_enabled" value="1" <?php _ec($meta_enabled ? 'checked' : '')?>>
							<label class="form-check-label" for="meta_enabled"><?php _e("Ativar modo Oficial (Meta)")?></label>
						</div>

						<div class="meta-official-fields <?php _ec($meta_enabled ? '' : 'd-none')?> ">
							<div class="row g-3">
								<div class="col-md-4">
									<label class="form-label"><?php _e("Nome base (Meta)")?></label>
									<input type="text" class="form-control form-control-solid" name="meta_base_name" placeholder="ex: desbanimento" value="<?php _ec( get_data($meta_cfg, "base_name") )?>">
									<div class="form-text"><?php _e("A cada envio para análise, será criada uma nova versão com sufixo de data/hora.")?></div>
								</div>
								<div class="col-md-4">
									<label class="form-label"><?php _e("Categoria")?></label>
									<select class="form-select form-select-solid" name="meta_category">
										<?php $cat = strtoupper((string) get_data($meta_cfg, "category", "MARKETING")); ?>
										<option value="MARKETING" <?php _ec($cat === 'MARKETING' ? 'selected' : '')?>>MARKETING</option>
										<option value="UTILITY" <?php _ec($cat === 'UTILITY' ? 'selected' : '')?>>UTILITY</option>
									</select>
								</div>
								<div class="col-md-4">
									<label class="form-label"><?php _e("Idiomas")?></label>
									<input type="text" class="form-control form-control-solid" name="meta_languages" placeholder="pt_BR,en_US" value="<?php _ec( get_data($meta_cfg, "languages", "pt_BR") )?>">
								</div>
							</div>

							<div class="row g-3 mt-1">
								<div class="col-md-4">
									<label class="form-label"><?php _e("Header")?></label>
									<?php $hf = strtoupper((string) get_data($meta_cfg, "header_format", "TEXT")); ?>
									<select class="form-select form-select-solid" name="meta_header_format">
										<option value="NONE" <?php _ec($hf === 'NONE' ? 'selected' : '')?>><?php _e("Sem header")?></option>
										<option value="TEXT" <?php _ec($hf === 'TEXT' ? 'selected' : '')?>><?php _e("Texto (usa Title Button)")?></option>
										<option value="IMAGE" <?php _ec($hf === 'IMAGE' ? 'selected' : '')?>><?php _e("Imagem (usa Main image)")?></option>
									</select>
								</div>
								<div class="col-md-8">
									<label class="form-label"><?php _e("Variáveis do body (obrigatório se houver {{n}})")?></label>
								<input type="text" class="form-control form-control-solid" name="meta_body_example" placeholder="ex: %nome%|%pedido% ou João|12345" value="<?php _ec( get_data($meta_cfg, "body_example") )?>">
								<div class="form-text"><?php _e("Informe valores ou variáveis da planilha separados por |. Ex: %nome%|%email% — a ordem deve corresponder a {{1}}, {{2}}, etc.")?></div>
								</div>
							</div>

							<div class="alert alert-warning mt-4 mb-0">
								<strong><?php _e("Importante:")?></strong>
								<?php _e("No modo Oficial (Meta), os botões aceitos são Quick Reply, URL, Telefone e Flow. Copy/Catálogo serão bloqueados na submissão.")?>
							</div>
						</div>
					</div>
					<div class="card-footer">
						<div class="row g-2 align-items-end">
							<div class="col-md-8">
								<label class="form-label"><?php _e("Conta Cloud API para submissão")?></label>
								<select class="form-select form-select-solid" name="account_ids">
									<option value=""><?php _e("Selecione...")?></option>
									<?php if (!empty($cloud_accounts)): ?>
										<?php $last_acc = get_data($meta_cfg, "last_account_ids"); ?>
										<?php foreach ($cloud_accounts as $acc): ?>
											<option value="<?php _ec($acc->ids)?>" <?php _ec(($last_acc && $last_acc == $acc->ids) ? 'selected' : '')?>>
												<?php _ec($acc->name)?> (<?php _ec($acc->pid)?>)
											</option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>
							<div class="col-md-4 d-grid">
								<a id="btn-meta-submit" class="btn btn-info actionMultiItem" href="<?php _ec( get_module_url("meta_submit/" . get_data($result, "ids")) )?>" data-params="">
									<i class="fas fa-paper-plane me-2"></i><?php _e("Enviar para análise (Meta)")?>
								</a>
							</div>
						</div>
							<div class="form-check mt-3">
								<input class="form-check-input" type="checkbox" value="1" id="meta_force" name="meta_force">
								<label class="form-check-label" for="meta_force">
									<?php _e("Forçar nova versão mesmo se houver PENDING recente")?>
								</label>
							</div>
						<?php if (!empty($meta_statuses)): ?>
						<div class="mt-4">
							<div class="d-flex justify-content-between align-items-center mb-2">
								<div class="fw-bold"><?php _e("Status do template na Meta")?></div>
								<?php if (!empty($last_acc)): ?>
									<button type="button" class="btn btn-sm btn-light" onclick="atualizarStatusMeta('<?php _ec($last_acc)?>')">
										<i class="fas fa-sync-alt me-1"></i><?php _e("Atualizar status")?>
									</button>
								<?php endif; ?>
							</div>
							<div class="table-responsive">
								<table class="table table-sm table-striped align-middle mb-0">
									<thead>
										<tr>
											<th><?php _e("Nome (versão)")?></th>
											<th><?php _e("Idioma")?></th>
											<th><?php _e("Status")?></th>
											<th><?php _e("Categoria")?></th>
											<th><?php _e("Meta ID")?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($meta_statuses as $st): ?>
											<?php $sd = json_decode($st->data, true) ?: []; ?>
											<tr>
												<td class="text-over-all"><strong><?php _ec($st->name)?></strong></td>
												<td><?php _ec($sd['language'] ?? '-')?></td>
												<td><?php _ec($sd['status'] ?? '-')?></td>
												<td>
													<?php
														$cat = $sd['category'] ?? '-';
														$prev = $sd['previous_category'] ?? null;
														_ec($cat);
														if ($prev && $prev !== $cat) {
															echo ' <small class="text-muted">(prev: ' . htmlspecialchars((string)$prev, ENT_QUOTES, 'UTF-8') . ')</small>';
														}
													?>
												</td>
												<td><small><?php _ec($sd['meta_id'] ?? '-')?></small></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
						<?php else: ?>
							<div class="form-text mt-3">
								<?php _e("Após submeter, o status aparecerá aqui. Para importar templates aprovados da Meta para o sistema, use 'Sincronizar Templates' no perfil Cloud API.")?>
							</div>
						<?php endif; ?>
						<div class="form-text mt-2">
							<?php _e("Dica: salve o template antes de submeter, e depois clique em 'Sincronizar Templates' no perfil Cloud para atualizar status.")?>
						</div>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<?php
		/**
		 * Painel de Dados Dinâmicos (Variáveis)
		 * Cada variável {{n}} no corpo da mensagem se mapeia para um item aqui.
		 * source=local  → valor fixo guardado no template
		 * source=sheet  → coluna da planilha ex: %nome%
		 */
		$_lv_json = '[]';
		if( !empty($result) ){
		    $d2 = json_decode($result->data);
		    if( !empty($d2) && isset($d2->local_variables) ){
		        $_lv_json = json_encode($d2->local_variables);
		    }
		}
		?>
		<div class="card b-r-6 mt-4" id="card-local-variables">
			<div class="card-header">
				<div class="card-title">
					<i class="fad fa-database me-2 text-warning"></i>
					<?php _e("Dados Dinâmicos (Variáveis)")?>
				</div>
				<div class="card-toolbar">
					<button type="button" class="btn btn-sm btn-light-warning" id="btn-add-local-var">
						<i class="fad fa-plus me-1"></i><?php _e("Criar variável")?>
					</button>
				</div>
			</div>
			<div class="card-body">
				<div class="alert alert-light-primary d-flex align-items-center mb-4 p-3">
					<i class="fad fa-info-circle text-primary fs-4 me-3 flex-shrink-0"></i>
					<div class="fs-13">
						<?php _e("Crie variáveis para usar no corpo como")?>
						<code>{{1}}</code>, <code>{{2}}</code>...
						&nbsp;<strong><?php _e("Local")?></strong> = <?php _e("valor fixo")?>.
						&nbsp;<strong><?php _e("Planilha")?></strong> = <?php _e("coluna ex: %nome%")?>.
					</div>
				</div>

				<div id="local-variables-list"></div>

				<div id="local-vars-empty" class="text-center text-muted py-3 fs-13">
					<i class="fad fa-layer-group me-1"></i>
					<?php _e("Nenhuma variável. Clique em 'Criar variável' para começar.")?>
				</div>

				<input type="hidden" name="local_variables" id="local-variables-json"
				       value="<?php echo htmlspecialchars($_lv_json, ENT_QUOTES, 'UTF-8') ?>">
			</div>
		</div>

				<div class="card b-r-6">
			<div class="card-header">
				<div class="card-title"><?php _e("List button")?></div>
			</div>

			<div class="card-body wa-template-option">
				<?php
                $options = [];

                if( !empty($result) ){
                    $data = json_decode($result->data);
                    if( !empty($data) && isset($data->templateButtons) && count($data->templateButtons) != 0 ){
                        $options = $data->templateButtons;
                    }
                }
                ?>

                <?php if(!empty($options)){?>

                    <?php foreach ($options as $key => $value): 
                        $displayText = "";
                        if( isset( $value->quickReplyButton ) ){
                            $displayText = $value->quickReplyButton->displayText;
                        }else if( isset( $value->urlButton ) ){
                            $displayText = $value->urlButton->displayText;
                        }else if( isset( $value->callButton ) ){
                            $displayText = $value->callButton->displayText;
                        }else if( isset( $value->flowButton ) ){
                            $displayText = $value->flowButton->displayText;
                        }else if( isset( $value->catalogButton ) ){
                            $displayText = $value->catalogButton->displayText;
                        }

                        $catalogPhone = isset($value->catalogButton) ? get_data($value->catalogButton, "businessPhoneNumber") : "";
                        $catalogProduct = isset($value->catalogButton) ? get_data($value->catalogButton, "catalogProductId") : "";
                        $flowSelected = isset($value->flowButton) ? get_data($value->flowButton, "flowIds") : "";
                        $flowActionData = isset($value->flowButton) ? get_data($value->flowButton, "flowActionData") : "";
                        $extracted_code = "";
                    ?>
                    
                   

                    <div class="card border b-r-6 mb-4 wa-template-option-item">
						<div class="card-header">
							<div class="card-title"><?php _e("Button")?> <?php _ec( $key + 1 )?></div>
							<div class="card-toolbar">
								<button type="button" class="btn btn-sm btn-light-danger wa-template-option-remove px-3 b-r-6"><i class="fad fa-trash-alt pe-0 me-0"></i></button>
							</div>
						</div>
						<div class="card-body">
							<ul class="nav nav-pills mb-3 bg-light-dark rounded border" id="pills-tab">
						        <li class="nav-item">
						            <label for="btn_type_text_<?php _ec( $key + 1 )?>" class="nav-link bg-active-white text-gray-700 px-4 py-3 text-active-successy <?php _ec( get_data($value, "quickReplyButton") != false?"active":"" ) ?>" data-bs-toggle="pill" data-bs-target="#nav_btn_type_text_<?php _ec( $key + 1 )?>" type="button" role="tab"><?php _e("Text Button")?></label>
						            <input class="d-none" type="radio" name="btn_msg_type[<?php _ec( $key + 1 )?>]" id="btn_type_text_<?php _ec( $key + 1 )?>" <?php _ec( get_data($value, "quickReplyButton") != false?'checked="true"':"" ) ?> value="1">
						        </li>
						        <li class="nav-item">
						            <label for="btn_type_link_<?php _ec( $key + 1 )?>" class="nav-link bg-active-white text-gray-700 px-4 py-3 text-active-success  <?php _ec( get_data($value, "urlButton") != false?"active":"" ) ?>" data-bs-toggle="pill" data-bs-target="#nav_btn_type_link_<?php _ec( $key + 1 )?>" type="button" role="tab"><?php _e("Link Button")?></label>
				                    <input class="d-none" type="radio" name="btn_msg_type[<?php _ec( $key + 1 )?>]" id="btn_type_link_<?php _ec( $key + 1 )?>" <?php _ec( get_data($value, "urlButton") != false?'checked="true"':"" ) ?> value="2">
						        </li>
						        <li class="nav-item">
						            <label for="btn_type_call_<?php _ec( $key + 1 )?>" class="nav-link bg-active-white text-gray-700 px-4 py-3 text-active-success <?php _ec( get_data($value, "callButton") != false?"active":"" ) ?>" data-bs-toggle="pill" data-bs-target="#nav_btn_type_call_<?php _ec( $key + 1 )?>" type="button" role="tab"><?php _e("Call Action Button")?></label>
				                    <input class="d-none" type="radio" name="btn_msg_type[<?php _ec( $key + 1 )?>]" id="btn_type_call_<?php _ec( $key + 1 )?>" <?php _ec( get_data($value, "callButton") != false?'checked="true"':"" ) ?> value="3">
						        </li>
						        <li class="nav-item">
						            <label for="btn_type_copy_<?php _ec( $key + 1 )?>" class="nav-link bg-active-white text-gray-700 px-4 py-3 text-active-success  <?php _ec( get_data($value, "copyButton") != false?"active":"" ) ?>" data-bs-toggle="pill" data-bs-target="#nav_btn_type_copy_<?php _ec( $key + 1 )?>" type="button" role="tab"><?php _e("Copy Buttom")?></label>
				                    <input class="d-none" type="radio" name="btn_msg_type[<?php _ec( $key + 1 )?>]" id="btn_type_copy_<?php _ec( $key + 1 )?>" <?php _ec( get_data($value, "copyButton") != false?'checked="true"':"" ) ?> value="4">
						        </li>
						        <li class="nav-item">
						            <label for="btn_type_catalog_<?php _ec( $key + 1 )?>" class="nav-link bg-active-white text-gray-700 px-4 py-3 text-active-success  <?php _ec( get_data($value, "catalogButton") != false?"active":"" ) ?>" data-bs-toggle="pill" data-bs-target="#nav_btn_type_catalog_<?php _ec( $key + 1 )?>" type="button" role="tab"><?php _e("Catalog Button")?></label>
				                    <input class="d-none" type="radio" name="btn_msg_type[<?php _ec( $key + 1 )?>]" id="btn_type_catalog_<?php _ec( $key + 1 )?>" <?php _ec( get_data($value, "catalogButton") != false?'checked="true"':"" ) ?> value="5">
						        </li>
						        <li class="nav-item">
						            <label for="btn_type_flow_<?php _ec( $key + 1 )?>" class="nav-link bg-active-white text-gray-700 px-4 py-3 text-active-success <?php _ec( get_data($value, "flowButton") != false?"active":"" ) ?>" data-bs-toggle="pill" data-bs-target="#nav_btn_type_flow_<?php _ec( $key + 1 )?>" type="button" role="tab"><?php _e("Flow Button")?></label>
				                    <input class="d-none" type="radio" name="btn_msg_type[<?php _ec( $key + 1 )?>]" id="btn_type_flow_<?php _ec( $key + 1 )?>" <?php _ec( get_data($value, "flowButton") != false?'checked="true"':"" ) ?> value="6">
						        </li>
						    </ul>

					        <div class="tab-content pt-3" id="nav-tabContent">
					            <div class="mb-3">
				                    <label class="form-label"><?php _e("Display text")?></label> 
				                    <textarea name="btn_msg_display_text[<?php _ec( $key + 1 )?>]" class="form-control form-control-solid btn_msg_display_text_<?php _ec( $key + 1 )?>" placeholder="Enter your caption"><?php _ec( $displayText ) ?></textarea>
					            </div>
					            <div class="tab-pane fade <?php _ec( get_data($value, "quickReplyButton") != false?"show active":"" ) ?>" id="nav_btn_type_text_<?php _ec( $key + 1 )?>" role="tabpanel"></div>
					            <div class="tab-pane fade mb-3 <?php _ec( get_data($value, "urlButton") != false?"show active":"" ) ?>" id="nav_btn_type_link_<?php _ec( $key + 1 )?>" role="tabpanel">
				                    <label class="form-label"><?php _e("Link")?></label> 
				                    <input class="form-control form-control-solid" name="btn_msg_link[<?php _ec( $key + 1 )?>]" placeholder="<?php _e("Enter your url")?>" value="<?php _ec( get_data($value, "urlButton") != false?get_data($value->urlButton, "url"):"" ) ?>">
					            </div>
					            <div class="tab-pane fade mb-3 <?php _ec( get_data($value, "callButton") != false?"show active":"" ) ?>" id="nav_btn_type_call_<?php _ec( $key + 1 )?>" role="tabpanel">
				                    <label class="form-label"><?php _e("Phone number")?></label> 
				                    <input class="form-control form-control-solid" name="btn_msg_call[<?php _ec( $key + 1 )?>]" placeholder="<?php _e("Ex: +1 (234) 5678-901")?>" value="<?php _ec( get_data($value, "callButton") != false?get_data($value->callButton, "phoneNumber"):"" ) ?>">
					            </div>
					            <div class="tab-pane fade mb-3 <?php _ec(get_data($value, "copyButton") != false ? "show active" : "") ?>" id="nav_btn_type_copy_<?php _ec($key + 1) ?>" role="tabpanel">
                                    <label class="form-label"><?php _e("Copy Button") ?></label> 
                                    <input class="form-control form-control-solid" id="btn_msg_copy_<?php _ec($key + 1) ?>" name="btn_msg_copy[<?php _ec($key + 1) ?>]" placeholder="<?php _e("Enter Your Code or Text") ?>" value="<?php _ec($extracted_code) ?>">
                                </div>
                                <div class="tab-pane fade mb-3 <?php _ec(get_data($value, "catalogButton") != false ? "show active" : "") ?>" id="nav_btn_type_catalog_<?php _ec($key + 1) ?>" role="tabpanel">
                                    <label class="form-label"><?php _e("Business phone number") ?></label>
                                    <input class="form-control form-control-solid mb-3" name="btn_msg_catalog_phone[<?php _ec($key + 1) ?>]" placeholder="<?php _e("Ex: +55 21970402529") ?>" value="<?php _ec($catalogPhone) ?>">
                                    <label class="form-label"><?php _e("Catalog product ID") ?></label>
                                    <input class="form-control form-control-solid" name="btn_msg_catalog_product[<?php _ec($key + 1) ?>]" placeholder="<?php _e("Enter catalog product id") ?>" value="<?php _ec($catalogProduct) ?>">
                                </div>
                                <div class="tab-pane fade mb-3 <?php _ec(get_data($value, "flowButton") != false ? "show active" : "") ?>" id="nav_btn_type_flow_<?php _ec($key + 1) ?>" role="tabpanel">
                                    <label class="form-label"><?php _e("Selecionar Flow publicado") ?></label>
                                    <select class="form-select form-select-solid mb-3" name="btn_msg_flow[<?php _ec($key + 1) ?>]">
                                        <option value=""><?php _e("Selecione um Flow")?></option>
                                        <?php foreach ($available_flows as $flowOption): ?>
                                            <?php $flowMetaId = get_data($flowOption, "meta_flow_id", "text"); ?>
                                            <option value="<?php _ec(get_data($flowOption, "ids"))?>" <?php _ec($flowSelected == get_data($flowOption, "ids") ? 'selected' : '')?>>
                                                <?php _ec(get_data($flowOption, "name"))?><?php _e($flowMetaId ? " | Meta ID: {$flowMetaId}" : " | Local only")?><?php _e(get_data($flowOption, "account_name") ? " | " . get_data($flowOption, "account_name") : "")?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label class="form-label"><?php _e("Initial Flow JSON (opcional)") ?></label>
                                    <textarea class="form-control form-control-solid" rows="4" name="btn_msg_flow_action_data[<?php _ec($key + 1) ?>]" placeholder='{"origem":"template_oficial","categoria":"financeiro"}'><?php _ec($flowActionData)?></textarea>
                                    <div class="form-text"><?php _e("Use um objeto JSON. Você pode guardar valores fixos ou placeholders como %nome%, %telefone% e [wa_name] para o envio oficial.")?></div>
                                </div>
					        </div>

					        <ul class="text-success fs-12 mb-0">
					            <li><?php _e("Random message by Spintax. Ex: {Hi|Hello|Hola}")?></li>
					            <li><?php _e("CallButton: Enter Phone number for the button")?></li>
					            <li><?php _e("UrlButton: Enter URL for the button")?></li>
					            <li><?php _e("quickReplyButton: Enter a message to quick reply for the button")?></li>
					            <li><?php _e("FlowButton: Selecione um Flow publicado da mesma conta Cloud usada na submissão")?></li>
					            <li><?php _e("CatalogButton: Enter business phone number and product id")?></li>
					            <li><?php _e("[Bulk messaging] - Add custom variables: %name%, %param1%, %param2%,...")?></li>
					        </ul>
						</div>
					</div>
                    <?php endforeach ?>

                <?php }else{?>
				<div class="wa-empty">
					<?php _ec( $this->include('Core\Whatsapp\Views\empty'), false);?>
				</div>
                <?php }?>

			</div>

			<div class="card-footer wa-template-wrap-add <?php _ec( count($options)>= 10?"d-none":"" )?>">
				<a href="javascript:void(0);" class="btn btn-dark px-3 btn-wa-add-option"><?php _e("Add new button")?></a>
			</div>
		</div>

		<div class="mt-5 d-flex justify-content-end">
			<button type="submit" class="btn btn-primary w-100"><?php _e("Submit")?></button>
		</div>
	</div>
</form>

<div class="wa-template-data-option d-none">
    <div class="card border b-r-6 mb-4 wa-template-option-item">
		<div class="card-header">
			<div class="card-title"><?php _e("Button")?> {count}</div>
			<div class="card-toolbar">
				<button type="button" class="btn btn-sm btn-light-danger wa-template-option-remove px-3 b-r-6"><i class="fad fa-trash-alt pe-0 me-0"></i></button>
			</div>
		</div>
		<div class="card-body">
			<ul class="nav nav-pills mb-3 bg-light-dark rounded border" id="pills-tab">
		        <li class="nav-item">
		            <label for="btn_type_text_{count}" class="nav-link bg-active-white text-gray-700 px-4 py-3 active text-active-success" data-bs-toggle="pill" data-bs-target="#nav_btn_type_text_{count}" type="button" role="tab"><?php _e("Text Button")?></label>
		            <input class="d-none" type="radio" name="btn_msg_type[{count}]" id="btn_type_text_{count}" checked="true" value="1">
		        </li>
		        <li class="nav-item">
		            <label for="btn_type_link_{count}" class="nav-link bg-active-white text-gray-700 px-4 py-3 text-active-success" data-bs-toggle="pill" data-bs-target="#nav_btn_type_link_{count}" type="button" role="tab"><?php _e("Link Button")?></label>
                    <input class="d-none" type="radio" name="btn_msg_type[{count}]" id="btn_type_link_{count}" value="2">
		        </li>
		        <li class="nav-item">
		            <label for="btn_type_call_{count}" class="nav-link bg-active-white text-gray-700 px-4 py-3 text-active-success" data-bs-toggle="pill" data-bs-target="#nav_btn_type_call_{count}" type="button" role="tab"><?php _e("Call Action Button")?></label>
                    <input class="d-none" type="radio" name="btn_msg_type[{count}]" id="btn_type_call_{count}" value="3">
		        </li>
		        <li class="nav-item">
		            <label for="btn_type_copy_{count}" class="nav-link bg-active-white text-gray-700 px-4 py-3 text-active-success" data-bs-toggle="pill" data-bs-target="#nav_btn_type_copy_{count}" type="button" role="tab"><?php _e("Copy Buttom")?></label>
                    <input class="d-none" type="radio" name="btn_msg_type[{count}]" id="btn_type_copy_{count}" value="4">
		        </li>
		        <li class="nav-item">
		            <label for="btn_type_catalog_{count}" class="nav-link bg-active-white text-gray-700 px-4 py-3 text-active-success" data-bs-toggle="pill" data-bs-target="#nav_btn_type_catalog_{count}" type="button" role="tab"><?php _e("Catalog Button")?></label>
                    <input class="d-none" type="radio" name="btn_msg_type[{count}]" id="btn_type_catalog_{count}" value="5">
		        </li>
		        <li class="nav-item">
		            <label for="btn_type_flow_{count}" class="nav-link bg-active-white text-gray-700 px-4 py-3 text-active-success" data-bs-toggle="pill" data-bs-target="#nav_btn_type_flow_{count}" type="button" role="tab"><?php _e("Flow Button")?></label>
                    <input class="d-none" type="radio" name="btn_msg_type[{count}]" id="btn_type_flow_{count}" value="6">
		        </li>
		    </ul>

	        <div class="tab-content pt-3" id="nav-tabContent">
	            <div class="mb-3">
                    <label class="form-label"><?php _e("Display text")?></label> 
                    <textarea name="btn_msg_display_text[{count}]" class="form-control form-control-solid btn_msg_display_text_{count}" placeholder="Enter your caption"></textarea>
	            </div>
	            <div class="tab-pane fade" id="nav_btn_type_text_{count}" role="tabpanel"></div>
	            <div class="tab-pane fade mb-3" id="nav_btn_type_link_{count}" role="tabpanel">
                    <label class="form-label"><?php _e("Link")?></label> 
                    <input class="form-control form-control-solid" name="btn_msg_link[{count}]" placeholder="<?php _e("Enter your url")?>">
	            </div>
	            <div class="tab-pane fade mb-3" id="nav_btn_type_call_{count}" role="tabpanel">
                    <label class="form-label"><?php _e("Phone number")?></label> 
                    <input class="form-control form-control-solid" name="btn_msg_call[{count}]" placeholder="<?php _e("Ex: +1 (234) 5678-901")?>">
	            </div>
	            <div class="tab-pane fade mb-3" id="nav_btn_type_copy_{count}" role="tabpanel">
                    <label class="form-label"><?php _e("Copy Buttom")?></label> 
                    <input class="form-control form-control-solid" name="btn_msg_copy[{count}]" placeholder="<?php _e("Enter Your Code or Text")?>">
                </div>
                <div class="tab-pane fade mb-3" id="nav_btn_type_catalog_{count}" role="tabpanel">
                    <label class="form-label"><?php _e("Business phone number")?></label> 
                    <input class="form-control form-control-solid mb-3" name="btn_msg_catalog_phone[{count}]" placeholder="<?php _e("Ex: +55 21970402529")?>">
                    <label class="form-label"><?php _e("Catalog product ID")?></label> 
                    <input class="form-control form-control-solid" name="btn_msg_catalog_product[{count}]" placeholder="<?php _e("Enter catalog product id")?>">
                </div>
                <div class="tab-pane fade mb-3" id="nav_btn_type_flow_{count}" role="tabpanel">
                    <label class="form-label"><?php _e("Selecionar Flow publicado")?></label>
                    <select class="form-select form-select-solid mb-3" name="btn_msg_flow[{count}]">
                        <option value=""><?php _e("Selecione um Flow")?></option>
                        <?php foreach ($available_flows as $flowOption): ?>
                            <?php $flowMetaId = get_data($flowOption, "meta_flow_id", "text"); ?>
                            <option value="<?php _ec(get_data($flowOption, "ids"))?>">
                                <?php _ec(get_data($flowOption, "name"))?><?php _e($flowMetaId ? " | Meta ID: {$flowMetaId}" : " | Local only")?><?php _e(get_data($flowOption, "account_name") ? " | " . get_data($flowOption, "account_name") : "")?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label"><?php _e("Initial Flow JSON (opcional)")?></label>
                    <textarea class="form-control form-control-solid" rows="4" name="btn_msg_flow_action_data[{count}]" placeholder='{"origem":"template_oficial"}'></textarea>
                    <div class="form-text"><?php _e("Use um objeto JSON. Valores como %nome% e [wa_name] serão resolvidos no envio oficial.")?></div>
                </div>
	        </div>

	        <ul class="text-success fs-12 mb-0">
	            <li><?php _e("Random message by Spintax. Ex: {Hi|Hello|Hola}")?></li>
	            <li><?php _e("CallButton: Enter Phone number for the button")?></li>
	            <li><?php _e("UrlButton: Enter URL for the button")?></li>
	            <li><?php _e("quickReplyButton: Enter a message to quick reply for the button")?></li>
	            <li><?php _e("FlowButton: selecione um Flow publicado para anexar ao template oficial")?></li>
	            <li><?php _e("CatalogButton: Enter business phone number and product id")?></li>
	            <li><?php _e("[Bulk messaging] - Add custom variables: %name%, %param1%, %param2%,...")?></li>
	        </ul>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function(){
	$('#meta_enabled').on('change', function(){
		const enabled = $(this).is(':checked');
		$('.meta-official-fields').toggleClass('d-none', !enabled);
	});

	// Impede clique sem escolher a conta Cloud (evita o erro "missing_account_ids")
	$(document).on('click', '#btn-meta-submit', function(e){
		const enabled = $('#meta_enabled').is(':checked');
		if(!enabled) return;
		const acc = $('select[name=\"account_ids\"]').val();
		if(!acc){
			e.preventDefault();
			e.stopImmediatePropagation();
			if(typeof showNotification === 'function'){
				showNotification('Selecione a Conta Cloud API para submissão.', 'error');
			}else{
				alert('Selecione a Conta Cloud API para submissão.');
			}
			return false;
		}
	});
});

async function atualizarStatusMeta(accountIds){
	try{
		if(!accountIds){
			alert('Conta inválida.');
			return;
		}
		if (typeof Swal !== "undefined") {
			Swal.fire({ title: "Sincronizando...", didOpen: () => Swal.showLoading() });
		}
		const url = "<?php _ec( base_url('whatsapp_profiles/sync_templates') )?>/" + accountIds;
		const resp = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } });
		const data = await resp.json();
		if (typeof Swal !== "undefined") Swal.close();
		const msg = data && data.message ? data.message : "Sincronização concluída.";
		if(typeof showNotification === 'function'){
			showNotification(msg, data.status || 'success');
		}else{
			alert(msg);
		}
		window.location.reload();
	}catch(e){
		if (typeof Swal !== "undefined") Swal.close();
		alert("Erro ao sincronizar: " + (e && e.message ? e.message : "desconhecido"));
	}
}
</script>
<script type="text/javascript">
$(function(){
	Core.tagsinput();
});
</script>
<script type="text/javascript">
var localVarsI18n = {
    label:            "<?php _e('Nome / rótulo') ?>",
    local:            "<?php _e('Local (valor fixo)') ?>",
    sheet:            "<?php _e('Planilha (coluna)') ?>",
    valuePlaceholder: "<?php _e('Valor padrão') ?>",
    sheetPlaceholder: "%nome%"
};
</script>
<script type="text/javascript">
(function($){
    var _vars    = [];
    var _counter = 0;

    function _load(){
        var raw = $('#local-variables-json').val() || '[]';
        try { _vars = JSON.parse(raw); } catch(e){ _vars = []; }
        _counter = _vars.length > 0
            ? Math.max.apply(null, _vars.map(function(v){ return v.id || 0; }))
            : 0;
    }

    function _save(){
        $('#local-variables-json').val(JSON.stringify(_vars));
    }

    function _esc(s){
        return (s || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
    }

    function _row(v){
        var isSheet  = (v.source === 'sheet');
        var valLocal = !isSheet ? (v.value || '') : '';
        var valSheet =  isSheet ? (v.value || '') : '';
        return [
            '<div class="row g-2 align-items-center mb-2 local-var-row" data-id="' + v.id + '">',
                '<div class="col-auto">',
                    '<span class="badge badge-light-warning fw-bold">{{' + v.id + '}}</span>',
                '</div>',
                '<div class="col-md-3">',
                    '<input type="text" class="form-control form-control-sm form-control-solid lv-label"',
                    ' placeholder="' + _esc(localVarsI18n.label) + '"',
                    ' value="' + _esc(v.label) + '">',
                '</div>',
                '<div class="col-md-2">',
                    '<select class="form-select form-select-sm form-select-solid lv-source">',
                        '<option value="local"' + (!isSheet ? ' selected' : '') + '>' + _esc(localVarsI18n.local) + '</option>',
                        '<option value="sheet"' + ( isSheet ? ' selected' : '') + '>' + _esc(localVarsI18n.sheet) + '</option>',
                    '</select>',
                '</div>',
                '<div class="col lv-wrap-local"' + ( isSheet ? ' style="display:none"' : '') + '>',
                    '<input type="text" class="form-control form-control-sm form-control-solid lv-val-local"',
                    ' placeholder="' + _esc(localVarsI18n.valuePlaceholder) + '"',
                    ' value="' + _esc(valLocal) + '">',
                '</div>',
                '<div class="col lv-wrap-sheet"' + (!isSheet ? ' style="display:none"' : '') + '>',
                    '<input type="text" class="form-control form-control-sm form-control-solid lv-val-sheet"',
                    ' placeholder="' + _esc(localVarsI18n.sheetPlaceholder) + '"',
                    ' value="' + _esc(valSheet) + '">',
                '</div>',
                '<div class="col-auto">',
                    '<button type="button" class="btn btn-sm btn-light-danger lv-remove px-2">',
                        '<i class="fad fa-trash-alt"></i>',
                    '</button>',
                '</div>',
            '</div>'
        ].join('');
    }

    function _render(){
        var $list = $('#local-variables-list');
        $list.empty();
        if( _vars.length === 0 ){
            $('#local-vars-empty').show();
        } else {
            $('#local-vars-empty').hide();
            $.each(_vars, function(i, v){ $list.append(_row(v)); });
        }
        _save();
    }

    function _collect(){
        _vars = [];
        $('#local-variables-list .local-var-row').each(function(){
            var $r = $(this);
            var src = $r.find('.lv-source').val();
            _vars.push({
                id:     parseInt($r.data('id'), 10),
                label:  $r.find('.lv-label').val(),
                source: src,
                value:  src === 'sheet'
                            ? $r.find('.lv-val-sheet').val()
                            : $r.find('.lv-val-local').val()
            });
        });
        _save();
    }

    /* ── events ─────────────────────────── */
    $(document).on('click', '#btn-add-local-var', function(){
        _counter++;
        _vars.push({ id: _counter, label: '', source: 'local', value: '' });
        _render();
    });

    $(document).on('click', '.lv-remove', function(){
        var id = parseInt($(this).closest('.local-var-row').data('id'), 10);
        _vars = _vars.filter(function(v){ return v.id !== id; });
        _render();
    });

    $(document).on('change', '.lv-source', function(){
        var $r = $(this).closest('.local-var-row');
        var isSheet = $(this).val() === 'sheet';
        $r.find('.lv-wrap-local').toggle(!isSheet);
        $r.find('.lv-wrap-sheet').toggle( isSheet);
        _collect();
    });

    $(document).on('input', '.lv-label,.lv-val-local,.lv-val-sheet', function(){
        _collect();
    });

    /* collect before any form submit – use capture phase so it fires BEFORE core.js */
    document.addEventListener('submit', function(e){
        if(e.target && $(e.target).hasClass('actionForm')){
            _collect();
        }
    }, true); // capture=true → runs before jQuery delegated handlers

    /* init */
    $(function(){ _load(); _render(); });

}(jQuery));
</script>
