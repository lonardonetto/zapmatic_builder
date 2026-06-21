<div class="tab-pane fade show <?php _ec( (get_data($result, "type") == 6)?" active":"" ) ?>" id="wa_official_template_tab">
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <label class="form-label mb-0"><?php _e("Selecionar Template Meta")?></label>
            <?php /* Página meta_templates desativada (fluxo movido para módulos de templates) */ ?>
        </div>
        <select class="form-select form-select-solid mt-2" name="official_template" data-control="select2" data-placeholder="<?php _e("Escolha um template aprovado")?>">
            <option value="0"><?php _e("Selecione um template...")?></option>
            <?php if (!empty($official_templates)): ?>
                <?php foreach ($official_templates as $key => $value): ?>
                    <?php $t_data = json_decode($value->data); ?>
                    <option value="<?php _ec($value->id)?>" <?php _ec( get_data($result, "official_template", "select", $value->id) )?>>
                        <?php _ec($value->name)?> (<?php _ec($t_data->language ?? 'pt_BR')?>)
                    </option>
                <?php endforeach ?>
            <?php endif ?>
        </select>
        <div class="mt-2">
            <small class="text-info"><?php _e("Templates sincronizados da Meta garantem entrega mesmo fora da janela de 24 horas.")?></small>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label"><?php _e("Variáveis do BODY (opcional)")?></label>
        <textarea name="meta_body_vars" class="form-control form-control-solid" rows="3" placeholder="<?php _e("Uma variável por linha. Ex:\nJoão\nPedido #123")?>"></textarea>
        <div class="form-text">
            <?php _e("Use quando o template tiver {{1}}, {{2}}... no BODY. A ordem das linhas deve bater com os índices.")?>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label"><?php _e("HEADER media_id (opcional)")?></label>
            <input type="text" name="meta_header_media_id" class="form-control form-control-solid" placeholder="<?php _e("Ex: 123456789012345")?>">
            <div class="form-text"><?php _e("Preferencial: enviar por media_id (mídia já enviada à Meta).")?></div>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?php _e("HEADER link (opcional)")?></label>
            <input type="text" name="meta_header_media_link" class="form-control form-control-solid" placeholder="<?php _e("https://.../imagem.png")?>">
            <div class="form-text"><?php _e("Alternativa: URL pública do arquivo (imagem/vídeo/documento).")?></div>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label"><?php _e("Components JSON (avançado, opcional)")?></label>
        <textarea name="meta_components_json" class="form-control form-control-solid" rows="5" placeholder='[{"type":"body","parameters":[{"type":"text","text":"João"}]}]'></textarea>
        <div class="form-text">
            <?php _e("Se preencher, este JSON será enviado como template.components (para botões dinâmicos, carrossel, etc).")?>
        </div>
    </div>
</div>
