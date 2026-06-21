<div class="tab-pane fade <?php _ec( (get_data($result, "type") == 6)?"show active":"" ) ?>" id="wa_meta_template">
    <div class="mb-4">
        <label class="form-label"><?php _e("Selecionar Template Meta")?></label>
        <select name="meta_template" class="form-select form-select-solid" data-control="select2" data-placeholder="<?php _e("Escolha um template aprovado")?>">
            <option value="0"><?php _e("Selecione um template...")?></option>
            <?php if(!empty($meta_templates)): ?>
                <?php foreach($meta_templates as $template): ?>
                    <?php 
                        $t_data = json_decode($template->data);
                    ?>
                    <option value="<?php _ec($template->id)?>" <?php _ec( (get_data($result, "meta_template") == $template->id)?"selected":"" ) ?>>
                        <?php _ec($template->name)?> (<?php _ec($t_data->language ?? 'pt_BR')?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <div class="alert alert-info">
        <div class="d-flex">
            <div class="me-3"><i class="fad fa-info-circle fs-2"></i></div>
            <div>
                <?php _e("Templates sincronizados da Meta garantem entrega mesmo fora da janela de 24 horas.")?>
                <br>
                <small><?php _e("Caso não encontre o que aprovou, clique em 'Sincronizar' na tela de Perfis WhatsApp.")?></small>
            </div>
        </div>
    </div>
</div>
