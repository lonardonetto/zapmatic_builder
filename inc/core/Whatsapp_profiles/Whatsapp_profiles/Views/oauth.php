<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<div class="container">
    <form class="actionForm" action="<?php _e( get_module_url( ( uri('segment', 3)=="unofficial"?"save_unofficial":"save" ) ) )?>" method="POST" data-redirect="<?php _e( base_url("account_manager") )?>">
    <div class="row justify-content-center mt-5">
        <div class="col-md-7">
            <div class="card mb-4 mb-xl-10">
                <div class="card-header border-0 pt-0">
                    <h5><i class="fas fa-qrcode me-2 text-success"></i><?php _e("WhatsApp Web (Baileys)")?></h5>
                </div>
                <div class="card-body">
                        <!-- Baileys Content -->
                        <div id="tab_baileys">
                            <?php if (check_number_account("whatsapp", "profile", false, false) || uri("segment", 3) == $instance_id): ?>
                            <div class="py-2 check-wrap-all">
                                <div class="border b-r-10 p-20 mb-4">
                                    <div class="fs-16 fw-6"><i class="fad fa-key"></i> <?php _e("Instance ID:")?> <span class="text-success"><?php _ec($instance_id)?></span><a style="margin-left:10px;" href="<?php _ec( get_module_url("generate_instance") )?>" class="btn btn-outline btn-outline-dashed bg-white">
                                        <i class="fas fa-random text-success" style="margin-right:5px;"></i> <?php _e("Gerar Nova Instância")?>
                                    </a>
                                    </div>
                                    <?php if(get_option('wa_paircode') == 0) {?>
                                    <div class="text-gray-600"><?php _e("Scan the QR Code on your Whatsapp app")?></div>
                                    <?php } else { ?>
                                    <div class="text-gray-600"><?php _e("Scan the QR Code on your Whatsapp app")?> <?php _e('ou 👇:');?></div>
                                    
                                    <p></p>
                                    <button type="button" class="btn btn-outline btn-outline-dashed bg-white" data-bs-toggle="modal" data-bs-target="#PairingCodeModal"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>"></i> <?php _e("Conecte via código")?></button></i>
                                    <?php } ?>
                                </div>

                                <div class="text-center wa-qr-code" data-instance-id="<?php _ec($instance_id)?>">
                                    <?php if($has_pair == false){ ?>
                                    <div class="wa-code text-center">
                                        
                                        <div class="w-300 h-300 d-flex justify-content-center align-items-center fs-60 m-auto border b-r-10 text-dark">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                                    <?php }else { ?>
                                    <div class="border b-r-10 p-20 text-center">
                                        <?php if($pair_code != "" && $has_error == false){ ?>
                                        <h5><?php _ec($pair_code);?></h5>
                                        <?php } else { ?>
                                        <div class="alert alert-danger">
                                            <?php _e($error_msg);?>
                                        </div>
                                        <?php } ?>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php else: ?>
                                <?php $number_accounts = (int)permission("number_accounts"); ?>
                                <div class="alert alert-danger d-flex align-items-center">
                                    <div class="fs-40 me-3"><i class="fad fa-exclamation-circle"></i></div>
                                    <div>
                                        <div class="fw-bold"><?php _e("Limit number of accounts")?></div>
                                        <?php _e( sprintf(__("You can only add up to %s Whatsapp profiles"), $number_accounts ) )?>
                                    </div>
                                </div>
                            <?php endif ?>
                        </div>
                </div>
            </div>
        </div>
    </div>
    </form>
    
    <!-- Cloud API Tab - FORM SEPARADO -->
    <?php if ((int)permission("cloud_api_enabled") == 1): ?>
    <div class="row justify-content-center mt-3">
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-cloud me-2"></i><?php _e("WhatsApp Official (Cloud API)")?></h5>
                </div>
                <div class="card-body">
                    <form class="actionForm" action="<?php echo str_replace("http:", "https:", get_module_url("save_official")); ?>" method="POST" data-redirect="<?php _e( base_url("whatsapp_profiles/oauth") )?>">
                                <div class="mb-10">
                                    <label class="form-label fw-bold"><?php _e("Nome do Perfil")?></label>
                                    <input type="text" class="form-control form-control-solid" name="name" placeholder="Ex: Meu Negócio" required>
                                </div>
                                <div class="row mb-10">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold"><?php _e("WABA ID")?></label>
                                        <input type="text" class="form-control form-control-solid" name="waba_id" placeholder="Ex: 123456789012345" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold"><?php _e("Phone Number ID")?></label>
                                        <input type="text" class="form-control form-control-solid" name="phone_number_id" placeholder="Ex: 1234567890" required>
                                    </div>
                                </div>
                                <div class="mb-10">
                                    <label class="form-label fw-bold"><?php _e("Access Token (Meta)")?></label>
                                    <textarea class="form-control form-control-solid" name="token" rows="3" placeholder="Insira o Token Permanente da Meta" required></textarea>
                                </div>
                                <div class="mb-10">
                                    <label class="form-label fw-bold"><?php _e("Verify Token")?></label>
                                    <?php 
                                        $temp_token = session()->get('temp_official_verify_token');
                                        if(!$temp_token){
                                            $temp_token = uniqid('zapmatic_');
                                            session()->set('temp_official_verify_token', $temp_token);
                                        }
                                    ?>
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-solid" name="verify_token" value="<?php _ec( $temp_token )?>" id="verify_token_cloud" required>
                                        <button class="btn btn-sm btn-light-primary" type="button" onclick="copyToClipboard('verify_token_cloud')"><i class="fas fa-copy"></i></button>
                                    </div>
                                    <small class="text-muted"><?php _e("Use este token na configuração de Webhook do Facebook Developers.")?></small>
                                </div>

                                <div class="alert alert-warning d-flex align-items-center mb-10">
                                    <div class="fs-24 me-3 text-warning"><i class="fas fa-exclamation-triangle"></i></div>
                                    <div>
                                        <strong><?php _e("IMPORTANTE:")?></strong> <?php _e("Você DEVE clicar no botão 'Salvar Perfil Cloud API' abaixo ANTES de tentar verificar e salvar no Painel de Desenvolvedor da Meta.")?>
                                    </div>
                                </div>
                                
                                <div class="mb-10 bg-light-success p-5 rounded border border-success border-dashed">
                                    <label class="form-label fw-bold text-success"><?php _e("Webhook URL")?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-transparent border-0" value="<?php _ec( base_url('whatsapp_webhook/index') )?>" readonly id="webhook_url_cloud">
                                        <button class="btn btn-sm btn-light-success" type="button" onclick="copyToClipboard('webhook_url_cloud')"><i class="fas fa-copy"></i></button>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <span class="indicator-label"><?php _e("Salvar Perfil Cloud API")?></span>
                                    </button>
                                </div>
                            </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif ?>

            <?php if (!empty($accounts)): ?>
            <div class="row justify-content-center mt-3">
                <div class="col-md-7">
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="card-title"><i class="fad fa-list me-2" style="color: <?php _e($config['color'])?>"></i> <?php _ec("Gerenciar Contas Conectadas")?></div>
                        </div>
DEBUG-PROFILE-VIEW
                        <div class="card-body">
                            <?php foreach ($accounts as $key => $value): ?>
                                <div class="d-flex flex-stack">
                                    <div class="symbol symbol-45px me-3">
                                        <img src="<?php _ec( get_file_url($value->avatar) )?>" class="align-self-center" alt="">
                                    </div>
                                    <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                                        <div class="flex-grow-1 me-2 text-over-all">
                                            <a href="<?php _ec( $value->url)?>" class="text-gray-800 text-hover-primary fs-14 fw-bold"><?php _e( $value->name )?></a>
                                            <span class="text-muted fw-semibold d-block fs-12">
                                                <?php _e( $value->pid )?> 
                                                <span class="badge badge-light-<?php echo ($value->login_type == 1)?'success':'primary'?> fs-10 ml-2">
                                                    <?php echo ($value->login_type == 1)?'Cloud API':'Baileys'?>
                                                </span>
                                            </span>
                                            <div id="status-<?php _ec($value->ids)?>">
                                                <?php if ($value->login_type == 2 && $value->status == 0): ?>
                                                <a href="javascript:void(0);" onclick="iniciarConexao('<?php echo htmlspecialchars($value->token, ENT_QUOTES, 'UTF-8'); ?>')" class="text-danger fw-semibold d-block fs-12"><?php _e( "Re-login required" )?></a>
                                                <?php elseif($value->status == 1): ?>
                                                <span class="text-success fw-semibold d-block fs-12"><i class="fas fa-check-circle fs-10 me-1"></i><?php _e( "Conectado" )?></span>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <?php if ($value->login_type == 2 && $value->status == 0): ?>
                                            <a href="<?php echo base_url('whatsapp_profiles/oauth/' . $value->token); ?>" class="btn btn-success btn-sm me-2">
                                                <i class="fas fa-plug"></i> <?php _e("Conectar")?>
                                            </a>
                                        <?php else: ?>
                                            <?php if ($value->login_type == 1): ?>
                                                <?php 
                                                    $data_acc = json_decode($value->data);
                                                    $waba_id = $data_acc->waba_id ?? '';
                                                    $phone_id = $data_acc->phone_number_id ?? '';
                                                    $v_token = $data_acc->verify_token ?? '';
                                                    $token_meta = $data_acc->token ?? '';
                                                ?>
                                                <button type="button" class="btn btn-light-primary btn-sm me-2" 
                                                    onclick="testarConexaoCloud('<?php _ec($value->ids)?>')" 
                                                    title="<?php _e("Testar Conexão")?>">
                                                    <i class="fas fa-bolt"></i>
                                                </button>
                                                <button type="button" class="btn btn-info btn-sm me-2" 
                                                    onclick="sincronizarTemplates('<?php _ec($value->ids)?>')" 
                                                    title="<?php _e("Sincronizar Templates da Meta")?>">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                                <button type="button" class="btn btn-light-success btn-sm me-2" 
                                                    onclick="editarPerfilCloud('<?php _ec($value->ids)?>', '<?php _ec($value->name)?>', '<?php _ec($waba_id)?>', '<?php _ec($phone_id)?>', '<?php _ec($token_meta)?>', '<?php _ec($v_token)?>')" 
                                                    title="<?php _e("Editar")?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>

                                            <button type="button" 
                                                class="btn btn-danger btn-sm"
                                                onclick="desconectarPerfil('<?php echo htmlspecialchars($value->ids, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo get_module_url('disconnect'); ?>')">
                                                <i class="fas fa-trash-alt"></i> <?php _e("Excluir")?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if($key + 1 != count($accounts)){?>
                                <div class="separator separator-dashed my-4"></div>
                                <?php }?>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif ?>

            <div class="row justify-content-center mt-3">
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-body">
                            <div class="note">
                                <div class="desc m-b-15"><?php _e("If you don't see your profiles above, you might try to reconnect, re-accept all permissions, and ensure that you're logged in to the correct profile.")?></div>
                                <a href="<?php _ec( get_module_url("oauth") )?>" class="btn btn-outline btn-outline-dashed bg-white"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>"></i> <?php _e("Re-connect with Whatsapp")?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</div>

<!-- Import Chatbot Modal -->

<div class="modal fade" id="PairingCodeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php _ec("Conecte usando código 🤖") ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="PairingCodeModal" action="<?php _ec(get_module_url("oauth")) ?>" method="POST" data-redirect="">
                    <div class="tab-pane fade show active p-50" id="PairingCodeModal_form">
                        <div class="col mb-3">
                            <input type="hidden" id="instance_id" name="instance_id" value="<?php _ec($instance_id)?>">
                            <label for="phone" class="form-label"><?php _e("📱 Número do WhatsApp") ?></label>
                            <input id="phone" type="text" class="form-control" name="phone"  required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block w-100">
                        <?php _e("Gerar código") ?>
                    </button>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Cloud API Modal -->
<div class="modal fade" id="EditCloudModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php _ec("Editar Perfil Cloud API ☁️") ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="actionForm" action="<?php echo get_module_url("update_official"); ?>" method="POST" data-redirect="<?php _e( base_url("whatsapp_profiles/oauth") )?>">
                    <input type="hidden" name="ids" id="edit_cloud_ids">
                    <div class="mb-5">
                        <label class="form-label fw-bold"><?php _e("Nome do Perfil")?></label>
                        <input type="text" class="form-control" name="name" id="edit_cloud_name" required>
                    </div>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?php _e("WABA ID")?></label>
                            <input type="text" class="form-control" name="waba_id" id="edit_cloud_waba_id" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?php _e("Phone Number ID")?></label>
                            <input type="text" class="form-control" name="phone_number_id" id="edit_cloud_phone_id" required>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="form-label fw-bold"><?php _e("Access Token (Meta)")?></label>
                        <textarea class="form-control" name="token" id="edit_cloud_token" rows="3" required></textarea>
                    </div>
                    <div class="mb-5">
                        <label class="form-label fw-bold"><?php _e("Verify Token")?></label>
                        <input type="text" class="form-control" name="verify_token" id="edit_cloud_verify_token" required>
                    </div>
                    <div class="text-center mt-6">
                        <button type="submit" class="btn btn-primary w-100">
                            <?php _e("Salvar Alterações") ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
.intl-tel-input{
    display: block;
}
</style>

<!--End Import Chatbot Modal -->

<script>
function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices
    navigator.clipboard.writeText(copyText.value).then(function() {
        showNotification('Copiado para a área de transferência!', 'success');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}

function showNotification(message, type = 'info') {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        console.log("Notification [" + type + "]: " + message);
        // Tenta encontrar o toastr no elemento pai (caso seja um iframe ou carregado dinamicamente)
        if (typeof parent.toastr !== 'undefined') {
            parent.toastr[type](message);
        }
    }
}

function iniciarConexao(token) {
    // Primeiro, vamos verificar se o token é válido
    if (!token) {
        console.error('Token não fornecido');
        return;
    }

    // Faz a requisição para gerar uma nova instância
    fetch('<?php echo get_module_url('generate_instance'); ?>/' + token, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            // Redireciona para a página de autenticação com a nova instância
            window.location.href = '<?php echo get_module_url('oauth'); ?>/' + token;
        } else {
            showNotification(data.message || 'Erro ao gerar instância', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao iniciar conexão: ' + error.message, 'error');
    });
}

function editarPerfilCloud(ids, name, waba_id, phone_id, token, verify_token) {
    $('#edit_cloud_ids').val(ids);
    $('#edit_cloud_name').val(name);
    $('#edit_cloud_waba_id').val(waba_id);
    $('#edit_cloud_phone_id').val(phone_id);
    $('#edit_cloud_token').val(token);
    $('#edit_cloud_verify_token').val(verify_token);
    $('#EditCloudModal').modal('show');
}

function testarConexaoCloud(ids) {
    var btn = $(event.currentTarget);
    var originalHtml = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

    $.get('<?php echo get_module_url("test_official"); ?>/' + ids, function(data) {
        if (data && data.status) {
            showNotification(data.message, data.status);
            
            var statusHtml = '';
            if (data.status === 'success') {
                statusHtml = '<span class="text-success fw-semibold d-block fs-12"><i class="fas fa-check-circle fs-10 me-1"></i><?php _e( "Conectado" )?></span>';
            } else {
                statusHtml = '<span class="text-danger fw-semibold d-block fs-12"><i class="fas fa-times-circle fs-10 me-1"></i><?php _e( "Erro na conexão" )?></span>';
            }
            $('#status-' + ids).html(statusHtml);
        } else {
            showNotification("Resposta inválida do servidor.", "error");
        }
    }, 'json').fail(function() {
        showNotification("Erro ao testar conexão.", "error");
    }).always(function() {
        btn.html(originalHtml).prop('disabled', false);
    });
}

function sincronizarTemplates(ids) {
    var btn = $(event.currentTarget);
    var originalHtml = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

    $.get('<?php echo get_module_url("sync_templates"); ?>/' + ids, function(data) {
        if (data && data.status) {
            showNotification(data.message, data.status);
        } else {
            showNotification("Resposta inválida do servidor.", "error");
        }
    }, 'json').fail(function(jqxhr) {
        var errorMsg = "Erro ao sincronizar templates.";
        try {
            var resp = JSON.parse(jqxhr.responseText);
            if (resp.message) errorMsg = resp.message;
        } catch(e) {}
        showNotification(errorMsg, "error");
    }).always(function() {
        btn.html(originalHtml).prop('disabled', false);
    });
}
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    if (settings.url.includes("save_official")) {
        var detail = "Erro " + jqxhr.status + " - " + thrownError;
        var responsePreview = jqxhr.responseText ? jqxhr.responseText.substring(0, 200) : "Sem resposta do servidor.";
        console.error("Erro ao salvar perfil oficial:", detail, responsePreview);
        $(".loading").hide();

        if (typeof showNotification === "function") {
            showNotification("Erro ao salvar perfil oficial. Verifique os dados e tente novamente.", "error");
        } else if (typeof Core !== "undefined" && typeof Core.notify === "function") {
            Core.notify("Erro ao salvar perfil oficial. Verifique os dados e tente novamente.", "error");
        }
    }
});
</script>
