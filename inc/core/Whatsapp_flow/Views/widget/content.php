<div class="tab-pane fade show <?php _ec( get_data($result, "type") == 6 ? "active" : "" ) ?>" id="wa_flow">
    <label class="form-label"><?php _e("Cloud API flows")?></label>
    <div class="card border mb-4">
        <div class="card-body">
            <?php if (!empty($flows)): ?>
                <div class="mb-4">
                    <label class="form-label"><?php _e("Select flow")?></label>
                    <select class="form-select form-select-solid" name="flow_msg" id="flow_msg">
                        <option value=""><?php _e("Choose a flow")?></option>
                        <?php foreach ($flows as $value): ?>
                            <?php
                            $flow_name = get_data($value, "name");
                            $status_local = get_data($value, "status_local", "text");
                            $meta_flow_id = get_data($value, "meta_flow_id", "text");
                            $entry_screen = "FIRST_ENTRY_SCREEN";
                            if (!empty($value->flow_json)) {
                                $decoded = json_decode($value->flow_json, true);
                                if (is_array($decoded) && !empty($decoded["screens"][0]["id"])) {
                                    $entry_screen = (string) $decoded["screens"][0]["id"];
                                }
                            }
                            ?>
                            <option
                                value="<?php _ec( get_data($value, "ids") )?>"
                                data-flow-name="<?php _ec( $flow_name )?>"
                                data-meta-flow-id="<?php _ec( $meta_flow_id )?>"
                                data-entry-screen="<?php _ec( $entry_screen )?>"
                            >
                                <?php _ec( $flow_name )?><?php _e( $meta_flow_id ? " | Meta ID: {$meta_flow_id}" : " | Local only" )?><?php _e( $status_local ? " | " . ucfirst($status_local) : "" )?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <div class="text-gray-500 fs-12 mt-2">
                        <?php _e("Dentro da janela você pode testar o envio como interactive flow. Se o Flow ainda não existir na Meta, o payload será formado, mas o envio real poderá falhar.")?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><?php _e("CTA button text")?></label>
                        <input type="text" class="form-control form-control-solid" name="flow_cta" maxlength="30" value="Abrir fluxo">
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><?php _e("Flow mode")?></label>
                        <select class="form-select form-select-solid" name="flow_mode">
                            <option value="draft"><?php _e("Draft")?></option>
                            <option value="published"><?php _e("Published")?></option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label"><?php _e("Flow launch strategy")?></label>
                    <select class="form-select form-select-solid" name="flow_launch_action" id="flow_launch_action">
                        <option value="navigate"><?php _e("Navigate directly to a screen")?></option>
                        <option value="data_exchange" disabled><?php _e("Open via encrypted endpoint (data exchange - in validation)")?></option>
                    </select>
                    <div class="text-gray-500 fs-12 mt-2" id="flow_launch_action_help">
                        <?php _e("Use navigate to start from a fixed screen. The encrypted endpoint mode is still under validation with Meta in the Single Message screen.")?>
                    </div>
                    <div class="text-warning fs-12 mt-2">
                        <?php _e("Recommended for production right now: Published + Navigate directly to a screen.")?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label"><?php _e("Message body")?></label>
                    <textarea class="form-control form-control-solid" name="flow_body" rows="3" placeholder="<?php _e("Ex: Escolha uma opção para continuar no atendimento.")?>"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><?php _e("Header text")?></label>
                        <input type="text" class="form-control form-control-solid" name="flow_header" maxlength="60" placeholder="<?php _e("Optional")?>">
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><?php _e("Footer text")?></label>
                        <input type="text" class="form-control form-control-solid" name="flow_footer" maxlength="60" placeholder="<?php _e("Optional")?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4" id="flow_screen_wrapper">
                        <label class="form-label"><?php _e("Entry screen")?></label>
                        <input type="text" class="form-control form-control-solid" name="flow_screen" id="flow_screen" placeholder="FIRST_ENTRY_SCREEN" readonly>
                        <div class="text-gray-500 fs-12 mt-2">
                            <?php _e("A tela inicial do Flow e preenchida automaticamente para evitar erro de parametro invalido.")?>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4" id="flow_action_data_wrapper">
                        <label class="form-label"><?php _e("Initial data JSON")?></label>
                        <input type="text" class="form-control form-control-solid" name="flow_action_data" placeholder='{"origem":"single_message"}'>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex align-items-center align-self-center h-100 py-5">
                    <div class="w-100">
                        <div class="text-center px-4">
                            <img class="mh-190 mb-4" alt="" src="<?php _e( get_theme_url() ) ?>Assets/img/empty2.png">
                            <div class="text-gray-600 fs-13 mb-3">
                                <?php _e("No Flow available for the selected Cloud account")?>:
                                <strong><?php _e(get_data($account, "name") ? get_data($account, "name") : "-")?></strong>
                            </div>
                            <div>
                                <a class="btn btn-primary btn-sm b-r-30" href="<?php _e( base_url("whatsapp_flow/index/update") )?>" >
                                    <i class="fad fa-plus"></i> <?php _e("Add flow")?>
                                </a>
                            </div>
                            <div class="text-gray-500 fs-12 mt-3">
                                <?php _e("If the Flow already exists in Meta, use \"Pull all Flows from Meta\" inside the Flow module to import and sync it locally for this account.")?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function(){
    const syncSelectedFlowFields = function() {
        const entryScreen = $('#flow_msg').find('option:selected').data('entry-screen') || '';
        $('#flow_screen').val(entryScreen);
    };

    const applyFlowLaunchMode = function() {
        const launchMode = $('#flow_launch_action').val() || 'navigate';
        const isEndpointMode = launchMode === 'data_exchange';

        $('#flow_screen_wrapper').toggleClass('opacity-50', isEndpointMode);
        $('#flow_action_data_wrapper').toggleClass('opacity-50', isEndpointMode);
        $('#flow_screen').prop('disabled', isEndpointMode);
        $('input[name="flow_action_data"]').prop('disabled', isEndpointMode);

        if (isEndpointMode) {
            $('#flow_launch_action_help').text('<?php _e("Data exchange opens the Flow through the encrypted endpoint. Entry screen and initial JSON are ignored because the endpoint decides the first screen.")?>');
        } else {
            $('#flow_launch_action_help').text('<?php _e("Navigate starts the Flow from a fixed screen and can pass an initial JSON object to Meta.")?>');
        }
    };

    $(document).on('change', '#flow_msg', function(){
        syncSelectedFlowFields();
    });

    $(document).on('change', '#flow_launch_action', applyFlowLaunchMode);
    syncSelectedFlowFields();
    applyFlowLaunchMode();
});
</script>
