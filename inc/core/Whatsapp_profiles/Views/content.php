<?php 
// ##CHECKPOINT: Otimização da Interface de Perfis do WhatsApp - Versão Final
// Modificações realizadas:
// 1. Ajuste no posicionamento da foto do perfil (direita do card)
// 2. Redução do tamanho da fonte do "WhatsApp" para 0.8rem
// 3. Coloração do texto "WhatsApp" em verde
// 4. Remoção do "@s.whatsapp.net" do número de telefone
// 5. Redução das bordas arredondadas dos cards (rounded-2)
// 6. Ajuste do layout para 4 cards por linha em telas grandes
// 7. Remoção da data de vencimento
// 8. Formatação correta da data de criação do perfil
// 9. Adição de card informativo compacto com fundo verde água
// 10. Remoção do título "Perfis do WhatsApp"
?>

<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ( !empty($accounts) ): ?>
<div class="container py-4">
    <?php 
    // Calcula estatísticas de perfis
    $totalProfiles = count($accounts);
    $connectedProfiles = $totalProfiles;
    $disconnectedProfiles = 0;
    ?>
    
    <div class="d-flex justify-content-end mb-4">
        <a href="<?php echo base_url('whatsapp_profiles/oauth'); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i><?php _e("Adicionar perfil")?>
        </a>
    </div>

    <div class="card bg-soft-teal text-dark mb-4 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-info-circle fa-2x text-teal"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1">Gerenciamento de Perfis WhatsApp</h5>
                        <p class="card-text small mb-0">
                            Visualize e gerencie seus perfis do WhatsApp.
                        </p>
                    </div>
                </div>
                <div class="text-end">
                    <div class="d-flex align-items-center">
                        <div class="me-3 text-center">
                            <h6 class="mb-0 text-primary fw-bold"><?php _e($totalProfiles); ?></h6>
                            <small class="text-muted">Total de Perfis</small>
                        </div>
                        <div class="me-3 text-center">
                            <h6 class="mb-0 text-success fw-bold"><?php _e($connectedProfiles); ?></h6>
                            <small class="text-muted">Conectados</small>
                        </div>
                        <div class="text-center">
                            <h6 class="mb-0 text-danger fw-bold"><?php _e($disconnectedProfiles); ?></h6>
                            <small class="text-muted">Desconectados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .bg-soft-teal {
        background-color: rgba(32, 201, 151, 0.15);
    }
    .text-teal {
        color: #20c997;
    }
    </style>

    <!-- Search Bar -->
    <div class="mb-4">
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" class="form-control border-start-0" id="searchProfiles" placeholder="<?php _e("Procurar perfis...")?>" aria-label="Search">
        </div>
    </div>

    <!-- Cards Grid -->
    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4" id="profileCards">
        <?php foreach ($accounts as $account): ?>
        <div class="col profile-card">
            <div class="card rounded-2 mb-3 position-relative h-100 border-0 shadow-sm hover-shadow" 
                 data-profile-id="<?php _e($account->ids); ?>"
                 data-profile-name="<?php _e($account->name); ?>">
                <?php // #chcknbox: Checkbox do card implementado com sucesso
                // Posicionamento: 10px das bordas, z-index garantido ?>
                <input type="checkbox" class="form-check-input position-absolute" 
                       style="top: 10px; right: 10px; z-index: 10;"
                       id="profile-checkbox-<?php _e($account->id); ?>"
                       name="selected_profiles[]" 
                       value="<?php _e($account->id); ?>"
                >
                <?php // ##CHECKPOINT: Perfil de Whatsapp atualizado com sucesso
                // - Checkbox adicionado
                // - Nome do perfil posicionado
                // - Status de conexão adicionado
                // - Número do WhatsApp formatado
                // - Data de criação ajustada
                // - Botão de configurações posicionado ?>
                <div class="card-body">
                    <div class="d-flex align-items-start mb-2 position-relative">
                        <?php if (!empty($account->avatar)): ?>
                            <img src="<?php _e( get_file_url($account->avatar) )?>" class="profile-pic rounded-circle position-absolute" style="top: -8px; right: 15px;" alt="Profile" width="50" height="50">
                        <?php else: ?>
                            <div class="profile-pic-placeholder rounded-circle position-absolute" style="top: -8px; right: 15px; width: 50px; height: 50px; background-color: #e0e0e0;"></div>
                        <?php endif; ?>
                        <div style="margin-left: 0; width: 100%;">
                            <?php 
                            // Função para truncar nome (declarada apenas se não existir)
                            if (!function_exists('truncateName')) {
                                function truncateName($name, $maxLength = 20) {
                                    if (strlen($name) > $maxLength) {
                                        return substr($name, 0, $maxLength - 3) . '...';
                                    }
                                    return $name;
                                }
                            }

                            // Lógica para nome do perfil
                            $displayName = !empty($account->name) 
                                ? $account->name 
                                : str_replace('@s.whatsapp.net', '', $account->pid);
                            
                            // Aplica truncamento
                            $displayName = truncateName($displayName);
                            ?>
                            <h5 class="card-title mb-0 position-absolute" style="top: -8px; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php _e($displayName); ?></h5>
                            <span class="badge <?php _e( $account->status ? 'bg-success' : 'bg-danger' )?> me-2 mb-1 d-inline-block position-absolute" style="top: 20px; margin-top: -5px;">
                                <?php _e( $account->status ? 'Conectado' : 'Desconectado' )?>
                            </span>
                        </div>
                    </div>
                    <div style="margin-top: 50px;" class="text-start">
                        <h6 class="mb-1 text-muted" style="font-size: 0.8rem;">
                            <span class="text-success">WhatsApp</span>: <?php _e(str_replace('@s.whatsapp.net', '', $account->pid)); ?>
                        </h6>
                        <small class="text-muted">Perfil criado em: <?php _e(date('d/m/Y H:i', $account->created)); ?></small>
                    </div>

                    <div class="card-footer bg-transparent border-0 position-absolute bottom-0 end-0 p-3">
                        <div class="dropup">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cog me-2"></i> Configurações
                            </button>
                            <ul class="dropdown-menu">
                                <?php if ($account->status !== '1'): ?>
                                    <li><a class="dropdown-item" href="<?php echo base_url('whatsapp_profiles/oauth/' . $account->token); ?>">
                                        <i class="fas fa-plug text-success me-2"></i> <?php _e("Conectar")?>
                                    </a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="#" onclick="disconnectProfile('<?php _e($account->ids); ?>')">
                                        <i class="fas fa-times-circle text-danger me-2"></i> <?php _e("Desconectar")?>
                                    </a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="#" onclick="editProfileName('<?php _e($account->ids); ?>')">
                                    <i class="fas fa-edit text-primary me-2"></i> <?php _e("Editar Nome")?>
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="deleteProfile('<?php _e($account->ids); ?>')">
                                    <i class="fas fa-trash text-danger me-2"></i> <?php _e("Excluir")?>
                                </a></li>
                                <?php if (isset($account->login_type) && $account->login_type == 1): ?>
                                    <?php 
                                        $data_acc = json_decode($account->data);
                                        $waba_id = $data_acc->waba_id ?? '';
                                        $phone_id = $data_acc->phone_number_id ?? '';
                                        $v_token = $data_acc->verify_token ?? '';
                                        $token_meta = $data_acc->token ?? '';
                                    ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-primary" href="#" onclick="testarConexaoCloud('<?php _e($account->ids); ?>')">
                                        <i class="fas fa-bolt me-2"></i> <?php _e("Testar Conexão")?>
                                    </a></li>
                                    <li><a class="dropdown-item text-info" href="#" onclick="sincronizarTemplates('<?php _e($account->ids); ?>')">
                                        <i class="fas fa-sync-alt me-2"></i> <?php _e("Sincronizar Templates")?>
                                    </a></li>
                                    <?php /* Página meta_templates desativada (fluxo movido para módulos de templates) */ ?>
                                    <li><a class="dropdown-item text-success" href="#" onclick="editarPerfilCloud('<?php _e($account->ids); ?>', '<?php _e($account->name); ?>', '<?php _e($waba_id); ?>', '<?php _e($phone_id); ?>', '<?php _e($token_meta); ?>', '<?php _e($v_token); ?>')">
                                        <i class="fas fa-edit me-2"></i> <?php _e("Editar Cloud API")?>
                                    </a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Removido código de botão de teste
    // #RETIRARBOTAO: Botões de teste e adicionar perfil foram removidos em 11/12/2024
});

// Notification fallback mechanism
function showNotification(message, type = 'info') {
    if (typeof Core !== 'undefined' && typeof Core.notify === 'function') {
        Core.notify(message, type);
    } else if (typeof toastr !== 'undefined' && typeof toastr[type] === 'function') {
        toastr[type](message);
    } else {
        console.log(message);
    }
}

function showProfileConfirmDialog(options) {
    if (typeof Core !== 'undefined' && typeof Core.showConfirmDialog === 'function') {
        Core.showConfirmDialog(options);
        return;
    }

    if (window.confirm(options.message || 'Tem certeza que deseja continuar?') && typeof options.onConfirm === 'function') {
        options.onConfirm();
    }
}

function executeDisconnectProfile(profileId) {
    const actionDialog = (typeof Core !== 'undefined' && typeof Core.showActionDialog === 'function')
        ? Core.showActionDialog({
            type: 'status',
            icon: 'fad fa-unlink',
            title: 'Desconectando perfil',
            message: 'Estamos encerrando a conexão deste perfil.'
        })
        : null;

    const formData = new FormData();
    formData.append('ids', profileId);
    if (typeof csrf_token !== 'undefined') {
        formData.append('csrf', csrf_token);
    }

    fetch(PATH + '/whatsapp_profiles/disconnect', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Erro ao parsear resposta:', text);
            throw new Error('Resposta não é JSON válido');
        }

        if (data.status === 'success') {
            if (actionDialog && typeof Core.finishActionDialog === 'function') {
                Core.finishActionDialog('success', data.message || 'Perfil desconectado com sucesso!', actionDialog);
            }
            showNotification('Perfil desconectado com sucesso!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            if (actionDialog && typeof Core.finishActionDialog === 'function') {
                Core.finishActionDialog('error', data.message || 'Erro ao desconectar perfil', actionDialog);
            }
            showNotification(data.message || 'Erro ao desconectar perfil', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        if (actionDialog && typeof Core.finishActionDialog === 'function') {
            Core.finishActionDialog('error', 'Erro ao desconectar perfil: ' + error.message, actionDialog);
        }
        showNotification('Erro ao desconectar perfil: ' + error.message, 'error');
    });
}

function executeDeleteProfile(profileId) {
    const actionDialog = (typeof Core !== 'undefined' && typeof Core.showActionDialog === 'function')
        ? Core.showActionDialog({
            type: 'delete',
            icon: 'fad fa-trash-alt',
            title: 'Excluindo perfil',
            message: 'Estamos removendo o perfil selecionado.'
        })
        : null;

    const formData = new FormData();
    formData.append('ids', profileId);
    if (typeof csrf_token !== 'undefined') {
        formData.append('csrf', csrf_token);
    }

    fetch(PATH + '/whatsapp_profiles/delete', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Erro ao parsear resposta:', text);
            throw new Error('Resposta não é JSON válido');
        }

        if (data.status === 'success') {
            if (actionDialog && typeof Core.finishActionDialog === 'function') {
                Core.finishActionDialog('success', data.message || 'Perfil excluído com sucesso!', actionDialog);
            }
            showNotification('Perfil excluído com sucesso!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            if (actionDialog && typeof Core.finishActionDialog === 'function') {
                Core.finishActionDialog('error', data.message || 'Erro ao excluir perfil', actionDialog);
            }
            showNotification(data.message || 'Erro ao excluir perfil', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        if (actionDialog && typeof Core.finishActionDialog === 'function') {
            Core.finishActionDialog('error', 'Erro ao excluir perfil: ' + error.message, actionDialog);
        }
        showNotification('Erro ao excluir perfil: ' + error.message, 'error');
    });
}

function disconnectProfile(profileId) {
    showProfileConfirmDialog({
        title: 'Desconectar perfil',
        message: 'Tem certeza que deseja desconectar este perfil?',
        confirmText: 'Desconectar',
        readyHint: 'Se estiver tudo certo, confirme para desconectar este perfil.',
        onConfirm: function() {
            executeDisconnectProfile(profileId);
        }
    });
}

function deleteProfile(profileId) {
    showProfileConfirmDialog({
        title: 'Excluir perfil',
        message: 'Tem certeza que deseja excluir este perfil?',
        confirmText: 'Excluir perfil',
        readyHint: 'Se estiver tudo certo, confirme para excluir este perfil.',
        onConfirm: function() {
            executeDeleteProfile(profileId);
        }
    });
}

function editProfileName(profileId) {
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 não está disponível');
        showNotification('Erro ao carregar o componente de edição. Por favor, recarregue a página.', 'error');
        return;
    }

    const account = document.querySelector(`[data-profile-id="${profileId}"]`);
    const currentName = account ? account.getAttribute('data-profile-name') : '';
    
    Swal.fire({
        title: '<?php _e("Editar Nome do Perfil") ?>',
        html: `
            <div class="form-group">
                <input type="text" id="profile_name" class="form-control" value="${currentName}" placeholder="<?php _e("Digite o novo nome") ?>">
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '<?php _e("Salvar") ?>',
        cancelButtonText: '<?php _e("Cancelar") ?>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        preConfirm: () => {
            const name = document.getElementById('profile_name').value;
            if (!name.trim()) {
                Swal.showValidationMessage('Por favor, insira um nome para o perfil');
                return false;
            }
            return name;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const newName = result.value;
            
            const formData = new FormData();
            formData.append('ids', profileId);
            formData.append('name', newName);
            
            // Mostrar loading
            Swal.fire({
                title: 'Atualizando...',
                text: 'Por favor, aguarde',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Adiciona timestamp para evitar cache
            const timestamp = new Date().getTime();
            const url = `${PATH}/whatsapp_profiles/update_name?_=${timestamp}`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                },
                body: formData,
                cache: 'no-store'
            })
            .then(response => {
                console.log('Status da resposta:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Resposta do servidor:', text);
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Erro ao parsear resposta:', text);
                    throw new Error('Resposta não é JSON válido');
                }
                
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Força um hard reload completo
                        window.location.href = window.location.pathname + '?t=' + new Date().getTime();
                        window.location.reload(true);
                    });
                } else {
                    throw new Error(data.message || 'Erro ao atualizar nome do perfil');
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message || 'Erro ao atualizar nome do perfil'
                });
            });
        }
    });
}
</script>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
.card {
    border-radius: 15px;
    background: #fff;
}
.profile-pic {
    object-fit: cover;
    border: 2px solid #25d366;
    width: 50px;
    height: 50px;
}
.profile-icon {
    border: 2px solid #25d366;
    
}
.badge {
    padding: 0.5em 1em;
    border-radius: 50px;
}
.modal-content {
    border-radius: 15px;
}
.dropdown-menu {
    z-index: 9999 !important;
    position: absolute !important;
    margin-bottom: 5px;
}
.dropup .dropdown-menu {
    bottom: 100%;
    top: auto !important;
}
</style>

<?php 
// Método estático para truncar nome
if (!function_exists('truncateName')) {
    function truncateName($name, $maxLength = 20) {
        if (strlen($name) > $maxLength) {
            return substr($name, 0, $maxLength - 3) . '...';
        }
        return $name;
    }
}
?>

<?php else: ?>
<div class="container py-5">
    <div class="text-center">
        <i class="fab fa-whatsapp fa-4x text-success mb-4"></i>
        <h4><?php _e("Nenhum perfil encontrado")?></h4>
        <p class="text-muted"><?php _e("Adicione seu primeiro perfil do WhatsApp para começar")?></p>
        <a href="<?php echo base_url('whatsapp_profiles/oauth'); ?>" class="btn btn-primary mt-3">
            <i class="fas fa-plus"></i> <?php _e("Adicionar perfil")?>
        </a>
    </div>
</div>
<?php endif ?>
<!-- Edit Cloud API - SweetAlert based (avoids nested form issue in Account Manager) -->
<script>
function editarPerfilCloud(ids, name, waba_id, phone_id, token, verify_token) {
    if (typeof Swal === 'undefined') {
        showNotification('Erro ao carregar o editor do perfil Cloud API. Recarregue a página.', 'error');
        return;
    }

    Swal.fire({
        title: '<?php _ec("Editar Perfil Cloud API ☁️") ?>',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label fw-bold"><?php _e("Nome do Perfil")?></label>
                    <input type="text" class="form-control" id="swal_cloud_name" value="${name || ''}" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e("WABA ID")?></label>
                        <input type="text" class="form-control" id="swal_cloud_waba_id" value="${waba_id || ''}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e("Phone Number ID")?></label>
                        <input type="text" class="form-control" id="swal_cloud_phone_id" value="${phone_id || ''}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold"><?php _e("Access Token (Meta)")?></label>
                    <textarea class="form-control" id="swal_cloud_token" rows="3">${token || ''}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold"><?php _e("Verify Token")?></label>
                    <input type="text" class="form-control" id="swal_cloud_verify_token" value="${verify_token || ''}">
                </div>
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<?php _e("Salvar Alterações") ?>',
        cancelButtonText: '<?php _e("Cancelar") ?>',
        allowOutsideClick: false,
        preConfirm: () => {
            const formName = document.getElementById('swal_cloud_name').value;
            const formWaba = document.getElementById('swal_cloud_waba_id').value;
            const formPhone = document.getElementById('swal_cloud_phone_id').value;
            const formToken = document.getElementById('swal_cloud_token').value;
            const formVerify = document.getElementById('swal_cloud_verify_token').value;

            if (!formName.trim() || !formWaba.trim() || !formPhone.trim() || !formToken.trim()) {
                Swal.showValidationMessage('Preencha todos os campos obrigatórios');
                return false;
            }
            return { name: formName, waba_id: formWaba, phone_number_id: formPhone, token: formToken, verify_token: formVerify };
        }
    }).then((result) => {
        if (!result.isConfirmed) return;

        const vals = result.value;
        const formData = new FormData();
        formData.append('ids', ids);
        formData.append('name', vals.name);
        formData.append('waba_id', vals.waba_id);
        formData.append('phone_number_id', vals.phone_number_id);
        formData.append('token', vals.token);
        formData.append('verify_token', vals.verify_token);
        if (typeof csrf !== 'undefined') formData.append('csrf', csrf);

        Swal.fire({
            title: 'Salvando...',
            text: 'Por favor, aguarde',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('<?php echo base_url("whatsapp_profiles/update_official"); ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => r.text())
        .then(text => {
            let data;
            try { data = JSON.parse(text); } catch(e) { throw new Error('Resposta inválida do servidor'); }

            if (data.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Sucesso!', text: data.message, showConfirmButton: false, timer: 1500 })
                .then(() => { window.location.reload(); });
            } else {
                throw new Error(data.message || 'Erro ao salvar');
            }
        })
        .catch(error => {
            Swal.fire({ icon: 'error', title: 'Erro', text: error.message });
        });
    });
}

function testarConexaoCloud(ids) {
    var originalHtml = '';
    var btn = null;
    if (event && event.currentTarget) {
        btn = $(event.currentTarget);
        originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
    }
    
    $.get('<?php echo base_url("whatsapp_profiles/test_official"); ?>/' + ids, function(data) {
        var msg = (data && data.message) ? data.message : "Resposta inesperada do servidor";
        var status = (data && data.status) ? data.status : "error";
        
        if(typeof Core != "undefined" && typeof Core.notify == "function") {
            Core.notify(msg, status);
        } else if(typeof showNotification == "function") {
            showNotification(msg, status);
        }
        
        if (status === 'success') {
            setTimeout(function() { window.location.reload(); }, 2000);
        }
    }, 'json').fail(function() {
        if(typeof Core != "undefined" && typeof Core.notify == "function") {
            Core.notify("Erro ao testar a conexão.", "error");
        } else if(typeof showNotification == "function") {
            showNotification("Erro ao testar a conexão.", "error");
        }
    }).always(function() {
        if (btn) btn.html(originalHtml).prop('disabled', false);
    });
}

function sincronizarTemplates(ids) {
    var originalHtml = '';
    var btn = null;
    if (event && event.currentTarget) {
        btn = $(event.currentTarget);
        originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
    }

    $.get('<?php echo base_url("whatsapp_profiles/sync_templates"); ?>/' + ids, function(data) {
        var msg = (data && data.message) ? data.message : "Resposta inesperada do servidor";
        var status = (data && data.status) ? data.status : "error";
        
        if(typeof Core != "undefined" && typeof Core.notify == "function") {
            Core.notify(msg, status);
        } else if(typeof showNotification == "function") {
            showNotification(msg, status);
        }
        
        if (status === 'success') {
            setTimeout(function() { window.location.reload(); }, 2000);
        }
    }, 'json').fail(function(jqxhr) {
        var errorMsg = "Erro ao sincronizar templates.";
        try { var resp = JSON.parse(jqxhr.responseText); if (resp.message) errorMsg = resp.message; } catch(e) {}
        
        if(typeof Core != "undefined" && typeof Core.notify == "function") {
            Core.notify(errorMsg, "error");
        } else if(typeof showNotification == "function") {
            showNotification(errorMsg, "error");
        }
    }).always(function() {
        if (btn) btn.html(originalHtml).prop('disabled', false);
    });
}
</script>
