<?php
$data = false;
$sections = false;
$desc = "";
$image = "";
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

    $desc = get_data($data, "name");

}
?>

<form class="actionForm" action="<?php _eC( get_module_url("save/".get_data($result, "ids")) )?>" method="POST" data-redirect="<?php _ec( $wa_return_redirect ) ?>">
	<div class="container py-5">
		<div class="card b-r-6 mb-4">
			<div class="card-header">
				<div class="card-title"><i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e("Poll template")?></div>
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
				
				<div class="mb-3">
                    <label class="form-label"><?php _e("Enable Multiselect")?></label>
                    <select class="form-select form-select-solid" name="multi_select" required>
                        <option value="0" <?php _ec((get_data($data, "selectableCount") == 0) ? "selected" : "") ?> ><?php _ec('Yes')?></option>
                        <option value="1" <?php _ec((get_data($data, "selectableCount") == 1) ? "selected" : "") ?> ><?php _ec('No')?></option>
                    </select>
                </div>

				<label class="form-label"><?php _e("Main description")?></label>
				<?php echo view_cell('\Core\Caption\Controllers\Caption::block', ['name' => 'desc', 'placeholder' => 'Enter main description', 'value' => $desc]) ?>

			</div>
		</div>

		<div class="card b-r-6">
			<div class="card-header">
				<div class="card-title"><?php _e("List Poll")?></div>
			</div>

			<div class="card-body wa-template-option">
				<?php
                $options = [];

                if( !empty($result) ){
                    $data = json_decode($result->data);
                    if( !empty($data) && isset($data->values) && count($data->values) != 0 ){
                        $options = $data->values;
                    }
                }
                ?>

                <?php if(!empty($options)){?>

                    <?php foreach ($options as $key => $value){?>

                    <div class="card border b-r-6 mb-4 wa-template-option-item">
						<div class="card-header">
							<div class="card-title"><?php _e("Poll Option")?> <?php _ec( $key + 1 )?></div>
							<div class="card-toolbar">
								<button type="button" class="btn btn-sm btn-light-danger wa-template-option-remove px-3 b-r-6"><i class="fad fa-trash-alt pe-0 me-0"></i></button>
							</div>
						</div>
						<div class="card-body">
					        <div class="tab-content pt-3" id="nav-tabContent">
					            <div class="mb-3">
				                    <label class="form-label"><?php _e("Display text")?></label> 
				                    <textarea name="btn_msg_display_text[<?php _ec( $key + 1 )?>]" class="form-control form-control-solid btn_msg_display_text_<?php _ec( $key + 1 )?>" placeholder="Enter your caption"><?php _ec( $options[$key] ) ?></textarea>
					            </div>
					            
					        </div>
						</div>
					</div>
                    <?php } ?>

                <?php }else{?>
				<div class="wa-empty">
					<?php _ec( $this->include('Core\Whatsapp\Views\empty'), false);?>
				</div>
                <?php }?>

			</div>

			<div class="card-footer wa-template-wrap-add">
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
			<div class="card-title"><?php _e("Poll")?> {count}</div>
			<div class="card-toolbar">
				<button type="button" class="btn btn-sm btn-light-danger wa-template-option-remove px-3 b-r-6"><i class="fad fa-trash-alt pe-0 me-0"></i></button>
			</div>
		</div>
		<div class="card-body">

	        <div class="tab-content pt-3" id="nav-tabContent">
	            <div class="mb-3">
                    <label class="form-label"><?php _e("Display text")?></label> 
                    <textarea name="btn_msg_display_text[{count}]" class="form-control form-control-solid btn_msg_display_text_{count}" placeholder="Enter your caption"></textarea>
	            </div>
	        </div>

	        <ul class="text-success fs-12 mb-0">
	            <li><?php _e("Random message by Spintax. Ex: {Hi|Hello|Hola}")?></li>
	        </ul>
		</div>
	</div>
</div>
<script type="text/javascript">
$(function(){
	Core.tagsinput();
});
</script>
