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
    
/* wa-account cards (same style as connection page) */
.wa-accounts-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:18px}
.wa-account-cell{min-width:0;position:relative}
.wa-account-tile{height:100%;padding:18px;border-radius:20px;border:1px solid #edf1f7;background:linear-gradient(180deg,#fff 0%,#fbfdff 100%);box-shadow:0 18px 45px rgba(15,23,42,.06);display:flex;flex-direction:column;gap:14px;transition:transform .2s,box-shadow .2s,border-color .2s;position:relative;overflow:visible;z-index:1}
.wa-account-tile:hover{transform:translateY(-3px);box-shadow:0 22px 48px rgba(15,23,42,.1);border-color:#dce6f2}
.wa-account-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.wa-account-identity{display:flex;align-items:center;gap:12px;min-width:0;flex:1}
.wa-account-avatar{width:52px;height:52px;border-radius:16px;overflow:hidden;background:linear-gradient(135deg,#eff6ff 0%,#f8fafc 100%);border:1px solid #dbe5f1;display:flex;align-items:center;justify-content:center;color:#25d366;flex-shrink:0}
.wa-account-avatar img{width:100%;height:100%;object-fit:cover}
.wa-account-copy{min-width:0}
.wa-account-name{font-size:15px;font-weight:700;color:#0f172a;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wa-account-subline{margin-top:4px;color:#64748b;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wa-account-middle{display:flex;flex-direction:column;gap:10px}
.wa-status-slot{min-height:24px}
.wa-status-pill{display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;font-size:12px;font-weight:700}
.wa-status-pill-success{background:#ecfdf3;color:#15803d}
.wa-status-pill-danger{background:#fef2f2;color:#b91c1c}
.wa-local-note{color:#64748b;font-size:12px;display:inline-flex;align-items:center;padding:7px 12px;border-radius:999px;background:#f8fafc}
.wa-account-footer{margin-top:auto;display:flex;align-items:center;justify-content:space-between;gap:12px}
.wa-account-primary-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.wa-local-chip{display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:600}
.wa-actions-dropdown{position:relative}
.wa-actions-menu{min-width:220px;border-radius:16px;border:1px solid #e8edf5;position:absolute;z-index:9999}
.wa-actions-wrap{position:relative}
.wa-actions-btn:focus{outline:none;box-shadow:0 0 0 2px rgba(0,0,0,.1)}
.wa-actions-menu-custom{
    display:none;position:absolute;bottom:calc(100% + 6px);right:0;min-width:220px;
    background:#fff;border-radius:16px;border:1px solid #e8edf5;
    box-shadow:0 12px 32px rgba(15,23,42,.12);padding:8px;z-index:9999;
}
.wa-actions-menu-custom a{
    display:flex;align-items:center;padding:8px 12px;border-radius:10px;
    color:#334155;font-size:0.88rem;text-decoration:none;transition:background .15s;
}
.wa-actions-menu-custom a:hover{background:#f8fafc}
.wa-actions-menu-custom a i{width:20px;text-align:center;margin-right:8px}
.wa-actions-menu-custom hr{margin:4px 0;border-color:#edf1f7}

.wa-account-cell .dropdown-menu{z-index:9999 !important}
.wa-account-cell.wa-menu-open,.wa-account-cell.wa-menu-open .wa-account-tile{z-index:50}
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
    <div class="wa-accounts-grid" id="profileCards">
        <?php foreach ($accounts as $account): ?>
            <?php
                $lt = (int)($account->login_type ?? 2);
                $is_cloud = $lt === 1;
                $is_whatsmeow = $lt === 3;
                $is_connected = (int)($account->status ?? 0) === 1;
                $profile_type_label = $is_cloud ? 'Cloud API' : ($is_whatsmeow ? 'Go / Whatsmeow' : 'Baileys');
                $profile_type_color = $is_cloud ? 'success' : ($is_whatsmeow ? 'info' : 'primary');
                $profile_type_filter = $is_cloud ? 'cloud' : ($is_whatsmeow ? 'whatsmeow' : 'baileys');
                $displayName = !empty($account->name) ? $account->name : str_replace('@s.whatsapp.net', '', $account->pid);
                if (strlen($displayName) > 22) $displayName = substr($displayName, 0, 19) . '...';
            ?>
            <div class="wa-account-cell profile-card" data-account-type="<?php _ec($profile_type_filter) ?>">
                <div class="wa-account-tile" data-profile-id="<?php _ec($account->ids) ?>" data-profile-name="<?php _ec($account->name) ?>">
                    <input type="checkbox" class="form-check-input position-absolute"
                           style="top:12px;right:12px;z-index:10;border-radius:4px;width:18px;height:18px;"
                           id="profile-checkbox-<?php _ec($account->id) ?>"
                           name="selected_profiles[]"
                           value="<?php _ec($account->id) ?>">

                    <div class="wa-account-top">
                        <div class="wa-account-identity">
                            <div class="wa-account-avatar">
                                <?php if (!empty($account->avatar)): ?>
                                    <img src="<?php _ec(get_file_url($account->avatar)) ?>" alt="">
                                <?php else: ?>
                                    <span><i class="fab fa-whatsapp"></i></span>
                                <?php endif; ?>
                            </div>
                            <div class="wa-account-copy min-w-0">
                                <div class="wa-account-name" title="<?php _ec($account->name) ?>"><?php _ec($displayName) ?></div>
                                <div class="wa-account-subline"><?php _ec(str_replace('@s.whatsapp.net', '', $account->pid)) ?></div>
                            </div>
                        </div>
                        <span class="badge badge-light-<?php _ec($profile_type_color) ?> fs-10"><?php _ec($profile_type_label) ?></span>
                    </div>

                    <div class="wa-account-middle">
                        <div class="wa-status-slot">
                            <?php if ($is_connected): ?>
                                <span class="wa-status-pill wa-status-pill-success"><i class="fas fa-check-circle me-1"></i><?php _e('Conectado') ?></span>
                            <?php else: ?>
                                <span class="wa-status-pill wa-status-pill-danger"><i class="fas fa-plug-circle-xmark me-1"></i><?php _e('Desconectado') ?></span>
                            <?php endif ?>
                        </div>
                        <?php if ($is_cloud): ?>
                            <div class="wa-local-note"><i class="fas fa-cloud me-1"></i><?php _e('Cloud API (Meta)') ?></div>
                        <?php elseif ($is_whatsmeow): ?>
                            <div class="wa-local-note"><i class="fas fa-server me-1"></i><?php _e('Conexão via Whatsmeow (Go)') ?></div>
                        <?php else: ?>
                            <div class="wa-local-note"><i class="fas fa-qrcode me-1"></i><?php _e('Conexão local via Baileys') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="wa-account-footer">
                        <div class="wa-account-primary-actions">
                            <?php if (!$is_connected && !$is_cloud): ?>
                                <a href="<?php _ec(base_url('whatsapp_profiles/oauth/' . $account->token)) ?>" class="btn btn-success btn-sm rounded-pill px-3">
                                    <i class="fas fa-plug me-1"></i><?php _e("Conectar")?>
                                </a>
                            <?php elseif ($is_connected && !$is_cloud): ?>
                                <span class="wa-local-chip"><?php _e('Sessão ativa') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="wa-actions-wrap">
                            <button class="btn btn-light-dark btn-sm rounded-pill px-3 wa-actions-btn" type="button" onclick="var m=this.nextElementSibling;m.style.display=m.style.display==='block'?'none':'block'">
                                <i class="fas fa-ellipsis-h me-1"></i><?php _e('Ações') ?>
                            </button>
                            <div class="wa-actions-menu-custom">
                                <?php if (!$is_connected && !$is_cloud): ?>
                                    <li><a class="dropdown-item" href="<?php _ec(base_url('whatsapp_profiles/oauth/' . $account->token)) ?>"><i class="fas fa-plug text-success me-2"></i><?php _e('Conectar') ?></a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="#" onclick="disconnectProfile('<?php _ec($account->ids) ?>')"><i class="fas fa-times-circle text-danger me-2"></i><?php _e('Desconectar') ?></a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="#" onclick="editProfileName('<?php _ec($account->ids) ?>')"><i class="fas fa-edit text-primary me-2"></i><?php _e('Editar Nome') ?></a></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteProfile('<?php _ec($account->ids) ?>')"><i class="fas fa-trash me-2"></i><?php _e('Excluir') ?></a></li>
                                <?php if ($is_cloud): ?>
                                    <?php
                                        $data_acc = json_decode($account->data);
                                        $waba_id = $data_acc->waba_id ?? '';
                                        $phone_id = $data_acc->phone_number_id ?? '';
                                        $v_token = $data_acc->verify_token ?? '';
                                        $token_meta = $data_acc->token ?? '';
                                    ?>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><a class="dropdown-item" href="#" onclick="testarConexaoCloud('<?php _ec($account->ids) ?>')"><i class="fas fa-bolt text-warning me-2"></i><?php _e('Testar Conexão') ?></a></li>
                                    <li><a class="dropdown-item" href="#" onclick="sincronizarTemplates('<?php _ec($account->ids) ?>')"><i class="fas fa-sync-alt text-info me-2"></i><?php _e('Sincronizar Templates') ?></a></li>
                                    <li><a class="dropdown-item" href="#" onclick="editarPerfilCloud('<?php _ec($account->ids) ?>', '<?php _ec($account->name) ?>', '<?php _ec($waba_id) ?>', '<?php _ec($phone_id) ?>', '<?php _ec($token_meta) ?>', '<?php _ec($v_token) ?>')"><i class="fas fa-edit text-success me-2"></i><?php _e('Editar Cloud API') ?></a></li>
                                <?php endif; ?>
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

/* wa-account cards (same style as connection page) */

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
