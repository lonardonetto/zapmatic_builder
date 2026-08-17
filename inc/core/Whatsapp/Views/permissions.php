<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Features")?></label>
    <div class="mb-3">
        <label for="whatsapp_profile" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_profile]" id="whatsapp_profile" value="1" <?php _e( plan_permission('checkbox', "whatsapp_profile") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_profile"><?php _e("Profile")?></label>
            </div>
        </label>

        <label for="whatsapp_bulk" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_bulk]" id="whatsapp_bulk" value="1" <?php _e( plan_permission('checkbox', "whatsapp_bulk") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_bulk"><?php _e("Bulk messaging")?></label>
            </div>
        </label>

        <label for="whatsapp_autoresponder" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_autoresponder]" id="whatsapp_autoresponder" value="1" <?php _e( plan_permission('checkbox', "whatsapp_autoresponder") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_autoresponder"><?php _e("Autoresponder")?></label>
            </div>
        </label>

        <label for="whatsapp_callresponder" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_callresponder]" id="whatsapp_callresponder" value="1" <?php _e( plan_permission('checkbox', "whatsapp_callresponder") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_callresponder"><?php _e("Call Responder")?></label>
            </div>
        </label>

        <label for="whatsapp_history" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_history]" id="whatsapp_history" value="1" <?php _e( plan_permission('checkbox', "whatsapp_history") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_history"><?php _e("Send Message History")?></label>
            </div>
        </label>

        <label for="whatsapp_chatbot" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_chatbot]" id="whatsapp_chatbot" value="1" <?php _e( plan_permission('checkbox', "whatsapp_chatbot") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_chatbot"><?php _e("Chatbot")?></label>
            </div>
        </label>

        <?php if (find_modules("bot_builder")): ?>
        <label for="bot_builder" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[bot_builder]" id="bot_builder" value="1" <?php _e( plan_permission('checkbox', "bot_builder") == 1?"checked":"" )?>>
                <label class="form-check-label" for="bot_builder"><?php _e("Construtor de Bots")?></label>
            </div>
        </label>
        <?php endif ?>

        <?php if (find_modules("whatsapp_call_campaign")): ?>
        <label for="whatsapp_call_campaign" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_call_campaign]" id="whatsapp_call_campaign" value="1" <?php _e( plan_permission('checkbox', "whatsapp_call_campaign") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_call_campaign"><?php _e("Campanhas de Chamada WhatsApp")?></label>
            </div>
        </label>
        <?php endif ?>

        <?php if (find_modules("whatsapp_export_participants")): ?>
        <label for="whatsapp_export_participants" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_export_participants]" id="whatsapp_export_participants" value="1" <?php _e( plan_permission('checkbox', "whatsapp_export_participants") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_export_participants"><?php _e("Exportar Participantes e Clone de Grupos")?></label>
            </div>
        </label>
        <?php endif ?>

        <label for="whatsapp_contact" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_contact]" id="whatsapp_contact" value="1" <?php _e( plan_permission('checkbox', "whatsapp_contact") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_contact"><?php _e("Contacts")?></label>
            </div>
        </label>
        <?php if (find_modules("whatsapp_leads")): ?>
        <label for="whatsapp_leads" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_leads]" id="whatsapp_leads" value="1" <?php _e( plan_permission('checkbox', "whatsapp_leads") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_leads"><?php _e("WhatsApp Leads")?></label>
            </div>
        </label>
        <?php endif ?>
        <?php if (find_modules("gm_scraper")): ?>
        <label for="gm_scraper" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[gm_scraper]" id="gm_scraper" value="1" <?php _e( plan_permission('checkbox', "gm_scraper") == 1?"checked":"" )?>>
                <label class="form-check-label" for="gm_scraper"><?php _e("Extrator de Leads Google Maps")?></label>
            </div>
        </label>
        <?php endif ?>
        <label for="criptografia_copy" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[criptografia_copy]" id="criptografia_copy" value="1" <?php _e( plan_permission('checkbox', "criptografia_copy") == 1?"checked":"" )?>>
                <label class="form-check-label" for="criptografia_copy"><?php _e("Criptografia de textos")?></label>
            </div>
        </label>
         <label for="whatsapp_evo_profile" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_evo_profile]" id="whatsapp_evo_profile" value="1" <?php _e( plan_permission('checkbox', "whatsapp_evo_profile") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_evo_profile"><?php _e("Evolution API")?></label>
            </div>
        </label>
        <?php if (find_modules("whatsapp_api")): ?>
        <label for="whatsapp_api" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_api]" id="whatsapp_api" value="1" <?php _e( plan_permission('checkbox', "whatsapp_api") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_api"><?php _e("API")?></label>
            </div>
        </label>
        <?php endif ?>
        <?php if (find_modules("whatsapp_send_message")): ?>
        <label for="whatsapp_send_message" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_send_message]" id="whatsapp_send_message" value="1" <?php _e( plan_permission('checkbox', "whatsapp_send_message") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_send_message"><?php _e("Single Send Message")?></label>
            </div>
        </label>
        <?php endif ?>
    </div>
</div>

<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Message Type")?></label>
    <div class="mb-3">

        <label for="whatsapp_button_template" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_button_template]" id="whatsapp_button_template" value="1" <?php _e( plan_permission('checkbox', "whatsapp_button_template") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_button_template"><?php _e("Send button message")?></label>
            </div>
        </label>

        <label for="whatsapp_list_message_template" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_list_message_template]" id="whatsapp_list_message_template" value="1" <?php _e( plan_permission('checkbox', "whatsapp_list_message_template") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_list_message_template"><?php _e("Send list message")?></label>
            </div>
        </label>

        <label for="whatsapp_poll_template" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_poll_template]" id="whatsapp_poll_template" value="1" <?php _e( plan_permission('checkbox', "whatsapp_poll_template") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_poll_template"><?php _e("Send Poll message")?></label>
            </div>
        </label>

        <label for="whatsapp_carousel_template" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_carousel_template]" id="whatsapp_carousel_template" value="1" <?php _e( plan_permission('checkbox', "whatsapp_carousel_template") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_carousel_template"><?php _e("Send carousel message")?></label>
            </div>
        </label>

        <label for="whatsapp_flow" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_flow]" id="whatsapp_flow" value="1" <?php _e( plan_permission('checkbox', "whatsapp_flow") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_flow"><?php _e("WhatsApp Flows")?></label>
            </div>
        </label>

        <?php if (find_modules("whatsapp_official_template")): ?>
        <label for="whatsapp_official_template" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_official_template]" id="whatsapp_official_template" value="1" <?php _e( plan_permission('checkbox', "whatsapp_official_template") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_official_template"><?php _e("Templates Oficiais WhatsApp")?></label>
            </div>
        </label>
        <?php endif ?>

        <label for="whatsapp_send_media" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_send_media]" id="whatsapp_send_media" value="1" <?php _e( plan_permission('checkbox', "whatsapp_send_media") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_send_media"><?php _e("Send media message")?></label>
            </div>
        </label>

        <label for="group_manager" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[group_manager]" id="group_manager" value="1" <?php _e( plan_permission('checkbox', "group_manager") == 1?"checked":"" )?>>
                <label class="form-check-label" for="group_manager"><?php _e("Gerenciamento de Grupos")?></label>
            </div>
        </label>

        <label for="caption" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[caption]" id="caption" value="1" <?php _e( plan_permission('checkbox', "caption") == 1?"checked":"" )?>>
                <label class="form-check-label" for="caption"><?php _e("Templates de Texto (Caption)")?></label>
            </div>
        </label>
    </div>
</div>


<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Autoresponder")?></label>
    <div class="mb-3">
        <label class="form-label" for="whatsapp_autoresponser_delay"><?php _e("Minimum number of minutes to choose autoresponder delay")?></label>
        <input type="number" class="form-control" id="whatsapp_autoresponser_delay" name="permissions[whatsapp_autoresponser_delay]" value="<?php _ec( (int)plan_permission('text', "whatsapp_autoresponser_delay") )?>">
    </div>
</div>

<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Chatbot")?></label>
    <div class="mb-3">
        <label class="form-label" for="whatsapp_chatbot_item_limit"><?php _e("Item limit for chatbots on each account")?></label>
        <input type="number" class="form-control" id="whatsapp_chatbot_item_limit" name="permissions[whatsapp_chatbot_item_limit]" value="<?php _ec( (int)plan_permission('text', "whatsapp_chatbot_item_limit") )?>">
    </div>
</div>

<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Bulk messaging")?></label>
    <div class="mb-3">
        <label for="whatsapp_bulk_schedule_by_times" class="form-label"> 
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_bulk_schedule_by_times]" id="whatsapp_bulk_schedule_by_times" value="1" <?php _e( plan_permission('checkbox', "whatsapp_bulk_schedule_by_times") == 1?"checked":"" )?>>
                <label class="form-check-label" for="whatsapp_bulk_schedule_by_times"><?php _e("Schedule by times")?></label>
            </div>
        </label>
    </div>
    <div class="mb-3">
        <label class="form-label" for="whatsapp_bulk_max_run"><?php _e("The maximum number of bulk messaging campaign can run at the same time")?></label>
        <input type="number" class="form-control" id="whatsapp_bulk_max_run" name="permissions[whatsapp_bulk_max_run]" value="<?php _ec( (int)plan_permission('text', "whatsapp_bulk_max_run") )?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="whatsapp_bulk_max_contact_group"><?php _e("The maximum number of contact groups")?></label>
        <input type="number" class="form-control" id="whatsapp_bulk_max_contact_group" name="permissions[whatsapp_bulk_max_contact_group]" value="<?php _ec( (int)plan_permission('text', "whatsapp_bulk_max_contact_group") )?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="whatsapp_bulk_max_phone_numbers"><?php _e("The maximum number of numbers that can be added to the contact group")?></label>
        <input type="number" class="form-control" id="whatsapp_bulk_max_phone_numbers" name="permissions[whatsapp_bulk_max_phone_numbers]" value="<?php _ec( (int)plan_permission('text', "whatsapp_bulk_max_phone_numbers") )?>">
    </div>
</div>

<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Total number of messages/month")?></label>
    <div class="mb-3">
        <input type="number" class="form-control" id="whatsapp_message_per_month" name="permissions[whatsapp_message_per_month]" value="<?php _ec( (int)plan_permission('text', "whatsapp_message_per_month") )?>">
        <span class="fs-12 text-primary"><?php _e("Include the total number of messages sent by Bulk messaging, Autoresponser, Chatbot")?></span>
    </div>
</div>

<?php if (find_modules("bot_builder")): ?>
<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Construtor de Bots — Limites")?></label>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de fluxos/bots criados")?></label>
        <input type="number" class="form-control" name="permissions[bot_builder_max_flows]" value="<?php _ec( (int)plan_permission('text', "bot_builder_max_flows") )?>">
    </div>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de nodes por fluxo")?></label>
        <input type="number" class="form-control" name="permissions[bot_builder_max_nodes]" value="<?php _ec( (int)plan_permission('text', "bot_builder_max_nodes") )?>">
    </div>
</div>
<?php endif ?>

<?php if (find_modules("whatsapp_call_campaign")): ?>
<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Campanhas de Chamada WhatsApp — Limites")?></label>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de ligacoes por mes")?></label>
        <input type="number" class="form-control" name="permissions[whatsapp_call_campaign_max_calls]" value="<?php _ec( (int)plan_permission('text', "whatsapp_call_campaign_max_calls") )?>">
    </div>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de ligacoes simultaneas")?></label>
        <input type="number" class="form-control" name="permissions[whatsapp_call_campaign_max_concurrent]" value="<?php _ec( (int)plan_permission('text', "whatsapp_call_campaign_max_concurrent") )?>">
    </div>
    <div class="mb-3">
        <label class="form-label"><?php _e("Duracao maxima do audio (segundos)")?></label>
        <input type="number" class="form-control" name="permissions[whatsapp_call_campaign_max_audio_duration]" value="<?php _ec( (int)plan_permission('text', "whatsapp_call_campaign_max_audio_duration") )?>">
    </div>
</div>
<?php endif ?>

<?php if (find_modules("gm_scraper")): ?>
<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Extrator de Leads Google Maps — Limites")?></label>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de buscas por mes")?></label>
        <input type="number" class="form-control" name="permissions[gm_scraper_max_jobs]" value="<?php _ec( (int)plan_permission('text', "gm_scraper_max_jobs") )?>">
    </div>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de leads extraidos por mes")?></label>
        <input type="number" class="form-control" name="permissions[gm_scraper_max_leads]" value="<?php _ec( (int)plan_permission('text', "gm_scraper_max_leads") )?>">
    </div>
</div>
<?php endif ?>

<?php if (find_modules("whatsapp_export_participants")): ?>
<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Exportar Participantes e Clone de Grupos — Limites")?></label>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de exportacoes por mes")?></label>
        <input type="number" class="form-control" name="permissions[whatsapp_export_max_exports]" value="<?php _ec( (int)plan_permission('text', "whatsapp_export_max_exports") )?>">
    </div>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de clonagens por mes")?></label>
        <input type="number" class="form-control" name="permissions[whatsapp_export_max_clones]" value="<?php _ec( (int)plan_permission('text', "whatsapp_export_max_clones") )?>">
    </div>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de participantes por exportacao")?></label>
        <input type="number" class="form-control" name="permissions[whatsapp_export_max_participants]" value="<?php _ec( (int)plan_permission('text', "whatsapp_export_max_participants") )?>">
    </div>
</div>
<?php endif ?>

<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Gerenciamento de Grupos — Limites")?></label>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de grupos gerenciados")?></label>
        <input type="number" class="form-control" name="permissions[group_manager_max_groups]" value="<?php _ec( (int)plan_permission('text', "group_manager_max_groups") )?>">
    </div>
</div>

<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Templates de Texto (Caption) — Limites")?></label>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de templates criados")?></label>
        <input type="number" class="form-control" name="permissions[caption_max_templates]" value="<?php _ec( (int)plan_permission('text', "caption_max_templates") )?>">
    </div>
</div>

<?php if (find_modules("whatsapp_official_template")): ?>
<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Templates Oficiais WhatsApp — Limites")?></label>
    <div class="mb-3">
        <label class="form-label"><?php _e("Maximo de templates oficiais")?></label>
        <input type="number" class="form-control" name="permissions[whatsapp_official_template_max]" value="<?php _ec( (int)plan_permission('text', "whatsapp_official_template_max") )?>">
    </div>
</div>
<?php endif ?>
