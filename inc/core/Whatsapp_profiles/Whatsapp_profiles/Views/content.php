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
            <div class="card rounded-2 mb-3 position-relative h-100 border-0 shadow-sm hover-shadow">
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
                        <div class="dropdown">
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
                                <li><a class="dropdown-item" href="#" onclick="editProfileName(<?php _e($account->id); ?>)">
                                    <i class="fas fa-edit text-primary me-2"></i> <?php _e("Editar Nome")?>
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="deleteProfile('<?php _e($account->ids); ?>')">
                                    <i class="fas fa-trash text-danger me-2"></i> <?php _e("Excluir")?>
                                </a></li>
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
    // Abre modal de edição do nome do perfil
    window.location.href = `/app_zapmatic_app/whatsapp_profiles/edit_name/${profileId}`;
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
