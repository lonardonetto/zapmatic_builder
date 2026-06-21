<div class="card card-flush m-b-25">
    <div class="card-header">
        <div class="card-title flex-column">
            <h3 class="fw-bolder"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e('WhatsApp API Configuration')?></h3>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <label for="wa_menu_type" class="form-label"><?php _ec("Show all feautures on sidebar menu")?></label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="wa_menu_type" id="wa_menu_type_disable" <?php _e( get_option('wa_menu_type', 0)  == 0?"checked":"" )?> value="0">
                    <label class="form-check-label" for="wa_menu_type_disable"><?php _e("Hide")?></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="wa_menu_type" id="wa_menu_type_enable" <?php _e( get_option('wa_menu_type', 0)  == 1?"checked":"" )?> value="1">
                    <label class="form-check-label" for="facebook_profile_status_enable"><?php _e("Show")?></label>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="wa_paircode" class="form-label"><?php _ec("Enable Login With Phone/Pairing Code")?></label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="wa_paircode" id="wa_paircode_disable" <?php _e( get_option('wa_paircode', 0)  == 0?"checked":"" )?> value="0">
                    <label class="form-check-label" for="wa_paircode_disable"><?php _e("Disable")?></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="wa_paircode" id="wa_paircode_enable" <?php _e( get_option('wa_paircode', 0)  == 1?"checked":"" )?> value="1">
                    <label class="form-check-label" for="wa_paircode_enable"><?php _e("Enable")?></label>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="whatsapp_server_url" class="form-label"><?php _e('WhatsApp Server URL')?></label>
            <input type="text" class="form-control form-control-solid" id="whatsapp_server_url" name="whatsapp_server_url" value="<?php _e( get_option("whatsapp_server_url", "") )?>" placeholder="https://example.com/">
        </div>
    </div>
</div>

<div class="card card-flush m-b-25">
    <div class="card-header">
        <div class="card-title flex-column">
            <h3 class="fw-bolder"><i class="fab fa-facebook" style="color:#1877F2;"></i> <?php _e('Configuração Global da Meta')?></h3>
            <span class="text-muted fs-12"><?php _e('Usada pela API Cloud do WhatsApp, Onboarding da Meta, Facebook, Instagram, Webhooks e Fluxos da Meta.')?></span>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex flex-column flex-sm-row w-100 p-20 mb-7">
            <span class="fs-30 me-4 mb-5 mb-sm-0 text-primary"><i class="fad fa-info-circle"></i></span>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1"><?php _e('Configure uma vez e reutilize em todo o sistema')?></h5>
                <span><?php _e('Se estes campos ficarem vazios, o sistema continuará usando as configurações antigas do Login do Facebook e os valores atuais de fallback, mantendo as conexões existentes funcionando.')?></span>
            </div>
        </div>

        <div class="mb-4">
            <label for="meta_app_id" class="form-label"><?php _e('Meta App ID')?></label>
            <input type="text" class="form-control form-control-solid" id="meta_app_id" name="meta_app_id" value="<?php _ec( get_option('meta_app_id', '') )?>" placeholder="<?php _e('Your Meta/Facebook App ID')?>">
        </div>

        <div class="mb-4">
            <label for="meta_app_secret" class="form-label"><?php _e('Meta App Secret')?></label>
            <input type="password" class="form-control form-control-solid" id="meta_app_secret" name="meta_app_secret" value="<?php _ec( get_option('meta_app_secret', '') )?>" placeholder="<?php _e('Your Meta/Facebook App Secret')?>">
        </div>

        <div class="mb-4">
            <label for="meta_embedded_signup_config_id" class="form-label"><?php _e('Embedded Signup Configuration ID')?></label>
            <input type="text" class="form-control form-control-solid" id="meta_embedded_signup_config_id" name="meta_embedded_signup_config_id" value="<?php _ec( get_option('meta_embedded_signup_config_id', '') )?>" placeholder="1115307890606209">
        </div>

        <div class="mb-4">
            <label for="meta_graph_version" class="form-label"><?php _e('Versão da Graph API')?></label>
            <input type="text" class="form-control form-control-solid" id="meta_graph_version" name="meta_graph_version" value="<?php _ec( get_option('meta_graph_version', '') )?>" placeholder="v22.0">
        </div>

        <div class="mb-4">
            <label for="meta_webhook_verify_token" class="form-label"><?php _e('Meta Webhook Verify Token')?></label>
            <input type="text" class="form-control form-control-solid" id="meta_webhook_verify_token" name="meta_webhook_verify_token" value="<?php _ec( get_option('meta_webhook_verify_token', '') )?>" placeholder="<?php _e('Create a strong verification token')?>">
        </div>

        <div class="mb-0">
            <label class="form-label"><?php _e('URLs de Webhook')?></label>
            <input type="text" readonly class="form-control form-control-solid mb-2" value="<?php _ec( base_url('whatsapp/webhook') )?>">
            <input type="text" readonly class="form-control form-control-solid" value="<?php _ec( base_url('meta-api/webhook') )?>">
        </div>
    </div>
</div>
