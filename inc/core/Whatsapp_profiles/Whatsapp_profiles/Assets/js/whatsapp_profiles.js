$(document).ready(function() {
    console.log('Whatsapp Profiles JS Loaded');

    function showProfileConfirmDialog(options) {
        if (typeof Core !== 'undefined' && typeof Core.showConfirmDialog === 'function') {
            Core.showConfirmDialog(options);
            return;
        }

        if (window.confirm(options.message || 'Tem certeza que deseja continuar?') && typeof options.onConfirm === 'function') {
            options.onConfirm();
        }
    }

    // Configuração global de AJAX para interceptar erros
    $.ajaxSetup({
        error: function(xhr, status, error) {
            console.group('Global AJAX Error Handler');
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('Response Text:', xhr.responseText);
            console.error('Response Headers:', xhr.getAllResponseHeaders());
            console.error('Status Code:', xhr.status);
            console.error('Status Text:', xhr.statusText);
            console.groupEnd();

            // Tenta parsear qualquer resposta JSON
            try {
                var errorResponse = JSON.parse(xhr.responseText);
                toastr.error(errorResponse.message || 'Erro desconhecido');
            } catch(e) {
                // Se não for JSON, mostra mensagem genérica
                toastr.error('Erro de comunicação com o servidor');
            }
        }
    });

    // Função para tentar parsear JSON de forma segura
    function safeParseJSON(jsonString) {
        try {
            // Remove qualquer conteúdo HTML ou caracteres antes do JSON
            var jsonStart = jsonString.indexOf('{');
            var jsonEnd = jsonString.lastIndexOf('}') + 1;
            
            if (jsonStart !== -1 && jsonEnd !== -1) {
                jsonString = jsonString.substring(jsonStart, jsonEnd);
            }
            
            return JSON.parse(jsonString);
        } catch (e) {
            console.error('JSON Parse Error:', e);
            console.error('Original response:', jsonString);
            return null;
        }
    }

    // Função de log detalhado de erros
    function logDetailedError(xhr, status, error) {
        console.group('Detailed AJAX Error');
        console.error('Status:', status);
        console.error('Error:', error);
        console.error('Response Text:', xhr.responseText);
        console.error('Response Headers:', xhr.getAllResponseHeaders());
        console.error('Status Code:', xhr.status);
        console.error('Status Text:', xhr.statusText);
        console.groupEnd();
    }

    // Função para exclusão de perfil
    function deleteProfile(profileId) {
        console.log('Deleting profile:', profileId);

        showProfileConfirmDialog({
            title: 'Excluir perfil',
            message: 'Tem certeza que deseja excluir este perfil? Esta ação não pode ser desfeita.',
            confirmText: 'Excluir perfil',
            readyHint: 'Se estiver tudo certo, confirme para excluir este perfil definitivamente.',
            onConfirm: function() {
                // Desabilita botão durante a exclusão
                var $deleteButton = $('[data-profile-id="' + profileId + '"]');
                $deleteButton.prop('disabled', true).addClass('disabled');

                $.ajax({
                    url: '<?php echo base_url("whatsapp_profiles/delete"); ?>',
                    method: 'POST',
                    data: { id: profileId },
                    dataType: 'json',
                    timeout: 10000, // 10 segundos de timeout
                    success: function(response) {
                        console.log('Delete response:', response);

                        if (response.status === 'success') {
                            // Remove o card correspondente
                            $('[data-profile-id="' + profileId + '"]').closest('.card, .col').fadeOut(300, function() {
                                $(this).remove();
                            });

                            // Mostra toast de sucesso
                            toastr.success('Perfil excluído com sucesso!');
                        } else {
                            console.error('Delete failed:', response);
                            toastr.error(response.message || 'Erro ao excluir perfil');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Log detalhado do erro
                        logDetailedError(xhr, status, error);

                        // Tenta parsear resposta como JSON de forma segura
                        var errorResponse = safeParseJSON(xhr.responseText);

                        if (errorResponse && errorResponse.message) {
                            toastr.error(errorResponse.message);
                        } else {
                            toastr.error('Erro de comunicação com o servidor');
                        }
                    },
                    complete: function() {
                        // Reabilita botão após tentativa
                        $deleteButton.prop('disabled', false).removeClass('disabled');
                    }
                });
            }
        });
    }

    // Captura o evento de exclusão individual
    $(document).on('click', '.delete-profile-btn', function(e) {
        e.preventDefault();
        
        console.log('Delete button clicked');
        
        var profileId = $(this).data('profile-id');
        console.log('Profile ID:', profileId);
        
        if (!profileId) {
            console.error('No profile ID found');
            toastr.error('Erro: ID do perfil não encontrado');
            return;
        }

        deleteProfile(profileId);
    });

    // Função para exclusão em lote
    $(document).on('click', '#delete-selected-profiles', function(e) {
        e.preventDefault();
        
        var selectedIds = [];
        $('.profile-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        console.log('Deleting multiple profiles:', selectedIds);

        if (selectedIds.length === 0) {
            toastr.warning('Nenhum perfil selecionado');
            return;
        }

        showProfileConfirmDialog({
            title: 'Excluir perfis selecionados',
            message: 'Tem certeza que deseja excluir os perfis selecionados? Esta ação não pode ser desfeita.',
            confirmText: 'Excluir perfis',
            readyHint: 'Se estiver tudo certo, confirme para excluir os perfis selecionados.',
            onConfirm: function() {
                $.ajax({
                    url: '<?php echo base_url("whatsapp_profiles/delete"); ?>',
                    method: 'POST',
                    data: { id: selectedIds },
                    dataType: 'json',
                    timeout: 10000, // 10 segundos de timeout
                    success: function(response) {
                        console.log('Delete response:', response);

                        if (response.status === 'success' && response.deletedIds) {
                            // Remove os cards correspondentes
                            response.deletedIds.forEach(function(id) {
                                $('[data-profile-id="' + id + '"]').closest('.card, .col').fadeOut(300, function() {
                                    $(this).remove();
                                });
                            });
                            toastr.success('Perfis excluídos com sucesso!');
                        } else {
                            console.error('Delete failed:', response);
                            toastr.error(response.message || 'Erro ao excluir perfis');
                        }
                    }
                });
            }
        });
    });
});
