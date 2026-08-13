<style>
/* Estilos complementares do fragmento (stats + cards). O hero/select já está no content.php */
.wep-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}
.wep-stat {
    background: var(--sp-white, #fff);
    border: 1px solid var(--sp-gray-200, #eff2f5);
    border-radius: 14px;
    padding: 18px 20px;
    display: flex; align-items: center; gap: 14px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.wep-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(24,28,50,0.06); }
.wep-stat-icon {
    width: 44px; height: 44px; flex-shrink: 0;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
}
.wep-stat-icon.primary { background: var(--sp-primary-light, #f1faff); color: var(--sp-primary, #009ef7); }
.wep-stat-icon.success { background: var(--sp-success-light, #e8fff3); color: var(--sp-success, #50cd89); }
.wep-stat-icon.info   { background: var(--sp-info-light, #f8f5ff);   color: var(--sp-info, #7239ea); }
.wep-stat-icon.warn   { background: var(--sp-warning-light, #fff8dd); color: var(--sp-warning, #ffc700); }
.wep-stat-icon.danger { background: var(--sp-danger-light, #fff5f8);  color: var(--sp-danger, #f1416c); }
.wep-stat-meta { display: flex; flex-direction: column; min-width: 0; }
.wep-stat-value { font-size: 22px; font-weight: 800; line-height: 1.1; color: var(--sp-gray-900, #181C32); }
.wep-stat-label { font-size: 12px; font-weight: 500; color: var(--sp-gray-600, #7E8299); white-space: nowrap; }

.wep-groups { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 16px; }
.wep-group {
    background: var(--sp-white, #fff);
    border: 1px solid var(--sp-gray-200, #eff2f5);
    border-radius: 16px;
    padding: 18px;
    display: flex; flex-direction: column; gap: 14px;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}
.wep-group:hover { transform: translateY(-3px); box-shadow: 0 10px 26px rgba(24,28,50,0.08); border-color: var(--sp-gray-300, #E4E6EF); }

.wep-group-head { display: flex; align-items: flex-start; gap: 12px; min-width: 0; }
.wep-group-avatar {
    width: 48px; height: 48px; flex-shrink: 0;
    border-radius: 13px; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, var(--sp-gray-600, #7E8299), var(--sp-gray-800, #3F4254));
    color: #fff; font-size: 15px; font-weight: 700;
    text-transform: uppercase;
}
.wep-group-avatar img { width: 100%; height: 100%; object-fit: cover; }
.wep-group-info { min-width: 0; flex: 1; }
.wep-group-name { margin: 0; font-size: 15px; font-weight: 700; color: var(--sp-gray-900, #181C32); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.wep-group-id {
    display: inline-flex; align-items: center; gap: 6px;
    margin-top: 5px; font-size: 11px; font-weight: 500;
    color: var(--sp-gray-600, #7E8299);
    background: var(--sp-gray-100, #f5f8fa);
    padding: 3px 9px; border-radius: 7px;
    max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

.wep-badges { display: flex; flex-wrap: wrap; gap: 6px; }
.wep-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 600;
    padding: 4px 10px; border-radius: 8px;
}
.wep-badge.grupo      { background: var(--sp-secondary-light, #f5f8fa); color: var(--sp-gray-700, #5E6278); }
.wep-badge.comunidade { background: var(--sp-info-light, #f8f5ff); color: var(--sp-info, #7239ea); }
.wep-badge.anuncios   { background: var(--sp-warning-light, #fff8dd); color: #c99400; }
.wep-badge.admin      { background: var(--sp-success-light, #e8fff3); color: var(--sp-success, #50cd89); }
.wep-badge.particip   { background: var(--sp-gray-100, #f5f8fa); color: var(--sp-gray-700, #5E6278); }

.wep-group-meta { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.wep-group-actions { display: flex; gap: 8px; flex-wrap: wrap; padding-top: 4px; }
.wep-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 14px; border-radius: 10px;
    font-size: 12px; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none;
    transition: all 0.2s; white-space: nowrap;
}
.wep-btn-export {
    background: var(--sp-white, #fff);
    color: var(--sp-gray-800, #3F4254);
    border: 1px solid var(--sp-gray-300, #E4E6EF);
}
.wep-btn-export:hover { border-color: var(--sp-primary, #009ef7); color: var(--sp-primary, #009ef7); text-decoration: none; }
.wep-btn-create {
    background: linear-gradient(135deg, #009ef7, #4cc9f0);
    color: #fff; box-shadow: 0 4px 14px rgba(0,158,247,0.28);
}
.wep-btn-create:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0,158,247,0.38); color: #fff; text-decoration: none; }
.wep-btn-copy {
    background: var(--sp-gray-100, #f5f8fa);
    color: var(--sp-gray-700, #5E6278);
    border: 1px solid var(--sp-gray-200, #eff2f5);
}
.wep-btn-copy:hover { color: var(--sp-gray-900, #181C32); border-color: var(--sp-gray-400, #B5B5C3); text-decoration: none; }
.wep-btn-clone {
    background: var(--sp-gray-100, #f5f8fa);
    color: var(--sp-gray-800, #3F4254);
    border: 1px solid var(--sp-gray-300, #E4E6EF);
}
.wep-btn-clone:hover { border-color: var(--sp-success, #50cd89); color: var(--sp-success, #50cd89); }

.wep-empty {
    background: var(--sp-white, #fff);
    border: 1px dashed var(--sp-gray-300, #E4E6EF);
    border-radius: 16px;
    padding: 52px 24px; text-align: center;
}
.wep-empty-icon {
    width: 62px; height: 62px; margin: 0 auto 16px;
    border-radius: 18px;
    background: var(--sp-gray-100, #f5f8fa);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; color: var(--sp-gray-500, #A1A5B7);
}
.wep-empty h4 { font-size: 16px; font-weight: 700; color: var(--sp-gray-900, #181C32); margin: 0 0 6px; }
.wep-empty p { font-size: 13px; color: var(--sp-gray-600, #7E8299); margin: 0; }

/* ===== SEARCH ===== */
.wep-search {
    display: flex; align-items: center; gap: 10px;
    background: var(--sp-white, #fff);
    border: 1px solid var(--sp-gray-200, #eff2f5);
    border-radius: 13px;
    padding: 10px 16px;
    margin-bottom: 18px;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.wep-search:focus-within { border-color: var(--sp-primary, #009ef7); box-shadow: 0 0 0 3px rgba(0,158,247,0.12); }
.wep-search .wep-search-icon { font-size: 15px; color: var(--sp-gray-500, #A1A5B7); }
.wep-search input {
    flex: 1; border: none; outline: none;
    font-size: 13px; font-weight: 500;
    color: var(--sp-gray-900, #181C32);
    background: transparent;
}
.wep-search input::placeholder { color: var(--sp-gray-500, #A1A5B7); }
.wep-search-clear {
    display: none; align-items: center; justify-content: center;
    width: 22px; height: 22px; border-radius: 50%;
    border: none; cursor: pointer;
    background: var(--sp-gray-200, #eff2f5);
    color: var(--sp-gray-600, #7E8299);
    font-size: 11px;
}
.wep-search-clear:hover { background: var(--sp-gray-300, #E4E6EF); color: var(--sp-gray-900, #181C32); }

.wep-no-results {
    grid-column: 1 / -1;
    background: var(--sp-white, #fff);
    border: 1px dashed var(--sp-gray-300, #E4E6EF);
    border-radius: 16px;
    padding: 42px 24px; text-align: center;
}
.wep-no-results i { font-size: 26px; color: var(--sp-gray-400, #B5B5C3); margin-bottom: 10px; display: block; }
.wep-no-results p { margin: 0; font-size: 13px; color: var(--sp-gray-600, #7E8299); }

.wep-group.hidden { display: none; }
</style>

<?php if ($status == "success" && $result->status == "success" && !empty($result->data)) : ?>

    <div class="wep-stats">
        <div class="wep-stat">
            <div class="wep-stat-icon primary"><i class="fas fa-users"></i></div>
            <div class="wep-stat-meta">
                <span class="wep-stat-value"><?php _ec($result->statistics->totalGroups) ?></span>
                <span class="wep-stat-label"><?php _e("Grupos") ?></span>
            </div>
        </div>
        <div class="wep-stat">
            <div class="wep-stat-icon info"><i class="fas fa-network-wired"></i></div>
            <div class="wep-stat-meta">
                <span class="wep-stat-value"><?php _ec($result->statistics->totalCommunities) ?></span>
                <span class="wep-stat-label"><?php _e("Comunidades") ?></span>
            </div>
        </div>
        <div class="wep-stat">
            <div class="wep-stat-icon warn"><i class="fas fa-bullhorn"></i></div>
            <div class="wep-stat-meta">
                <span class="wep-stat-value"><?php _ec($result->statistics->totalAnnouncements) ?></span>
                <span class="wep-stat-label"><?php _e("Anúncios") ?></span>
            </div>
        </div>
        <div class="wep-stat">
            <div class="wep-stat-icon success"><i class="fas fa-camera"></i></div>
            <div class="wep-stat-meta">
                <span class="wep-stat-value"><?php _ec($result->statistics->totalWithPhotos) ?></span>
                <span class="wep-stat-label"><?php _e("Com foto") ?></span>
            </div>
        </div>
        <div class="wep-stat">
            <div class="wep-stat-icon danger"><i class="fas fa-link"></i></div>
            <div class="wep-stat-meta">
                <span class="wep-stat-value"><?php _ec($result->statistics->totalWithInviteLinks) ?></span>
                <span class="wep-stat-label"><?php _e("Com link") ?></span>
            </div>
        </div>
    </div>

    <div class="wep-search">
        <i class="fas fa-magnifying-glass wep-search-icon"></i>
        <input type="text" id="wep-group-search" placeholder="<?php _e('Pesquisar grupo por nome ou ID...') ?>" autocomplete="off">
        <button type="button" class="wep-search-clear" id="wep-search-clear" title="<?php _e('Limpar') ?>"><i class="fas fa-xmark"></i></button>
    </div>

    <div class="wep-groups" id="wep-groups-grid">
        <?php foreach ($result->data as $key => $value) : ?>
            <?php
            $filtered_arr = array_filter($value->participants, function ($obj) use ($account) {
                return isset($obj->admin) && str_replace('@s.whatsapp.net', '', $obj->id) == $account->username;
            });

            $profilePicUrl = isset($value->profilePicUrl) ? $value->profilePicUrl : '';
            $isValidUrl = filter_var($profilePicUrl, FILTER_VALIDATE_URL);
            $initials = '';
            $nameParts = preg_split('/\s+/', trim($value->name));
            if (!empty($nameParts)) {
                $initials = strtoupper(mb_substr($nameParts[0], 0, 1));
                if (isset($nameParts[1])) {
                    $initials .= strtoupper(mb_substr($nameParts[1], 0, 1));
                }
            }

            // Badges de tipo
            if ($value->isCommunity && $value->announce)      { $typeBadge = 'anuncios';    $typeLabel = 'Comunidade · Anúncios'; }
            elseif ($value->isCommunity)                      { $typeBadge = 'comunidade';  $typeLabel = 'Comunidade'; }
            elseif ($value->announce)                         { $typeBadge = 'anuncios';    $typeLabel = 'Anúncios'; }
            else                                              { $typeBadge = 'grupo';       $typeLabel = 'Grupo'; }
            ?>
            <div class="wep-group" data-search="<?php echo strtolower(htmlspecialchars($value->name . ' ' . $value->id, ENT_QUOTES)); ?>">
                <div class="wep-group-head">
                    <div class="wep-group-avatar">
                        <?php if ($isValidUrl && !empty($profilePicUrl)) : ?>
                            <img src="<?php echo htmlspecialchars($profilePicUrl); ?>" alt="<?php _e($value->name) ?>">
                        <?php else : ?>
                            <?php _ec($initials) ?>
                        <?php endif; ?>
                    </div>
                    <div class="wep-group-info">
                        <div class="wep-group-name" title="<?php _e($value->name) ?>"><?php _e($value->name) ?></div>
                        <div class="wep-group-id btn-copy-id" data-clipboard-text="<?php _e($value->id) ?>" title="<?php _e("Copiar ID") ?>">
                            <i class="fas fa-fingerprint"></i> <?php _e($value->id) ?>
                        </div>
                    </div>
                </div>

                <div class="wep-badges">
                    <span class="wep-badge <?php echo $typeBadge; ?>">
                        <i class="fas <?php echo ($typeBadge === 'grupo') ? 'fa-users' : (($typeBadge === 'comunidade') ? 'fa-network-wired' : 'fa-bullhorn'); ?>"></i>
                        <?php _e($typeLabel) ?>
                    </span>
                    <span class="wep-badge particip">
                        <i class="fas fa-user-group"></i> <?php _e(sprintf(__("%s participantes"), $value->size)) ?>
                    </span>
                    <?php if (count($filtered_arr) > 0) : ?>
                        <span class="wep-badge admin"><i class="fas fa-shield-halved"></i> <?php _e("Admin") ?></span>
                    <?php endif ?>
                </div>

                <div class="wep-group-meta">
                    <span class="wep-badge particip"><i class="fas fa-calendar-day"></i> <?php _e("Criado") ?> <?php echo date('d/m/Y', $value->creation); ?></span>
                    <?php if (isset($value->inviteCode)) : ?>
                        <a href="<?php _e($value->inviteCode) ?>" target="_blank" class="wep-badge comunidade btn-copy-invite" data-clipboard-text="<?php _e($value->inviteCode) ?>">
                            <i class="fas fa-link"></i> <?php _e("Copiar convite") ?>
                        </a>
                    <?php endif ?>
                </div>

                <div class="wep-group-actions">
                    <a href="<?php _e(get_module_url("export_group/{$account->ids}/{$value->id}")) ?>" class="wep-btn wep-btn-export">
                        <i class="fas fa-download"></i> <?php _e("Exportar CSV") ?>
                    </a>
                    <a href="<?php _e(get_module_url("create_contact_list/{$account->ids}/{$value->id}")) ?>" class="wep-btn wep-btn-create actionItem"
                       data-confirm="<?php _e('Criar lista de contatos com os participantes deste grupo? Os números serão normalizados (9º dígito) e validados.') ?>">
                        <i class="fas fa-address-book"></i> <?php _e("Criar Lista de Contatos") ?>
                    </a>
                    <?php if (isset($account->login_type) && (int)$account->login_type === 3) : ?>
                        <button type="button" class="wep-btn wep-btn-clone"
                                data-group-id="<?php _e($value->id) ?>"
                                data-group-name="<?php echo htmlspecialchars($value->name, ENT_QUOTES); ?>"
                                data-account-id="<?php _e($account->ids) ?>"
                                data-target-name="<?php echo htmlspecialchars(\Core\Whatsapp_export_participants\Libraries\GroupCloner::buildTargetName($value->name), ENT_QUOTES); ?>">
                            <i class="fas fa-clone"></i> <?php _e("Clonar grupo") ?>
                        </button>
                    <?php endif ?>
                </div>
            </div>
        <?php endforeach ?>
        <div class="wep-no-results" id="wep-no-results" style="display:none;">
            <i class="fas fa-magnifying-glass"></i>
            <p><?php _e('Nenhum grupo encontrado para esta pesquisa.') ?></p>
        </div>
    </div>

<?php else : ?>
    <div class="wep-empty">
        <div class="wep-empty-icon"><i class="fas fa-users-slash"></i></div>
        <h4><?php _e("Nenhum grupo encontrado") ?></h4>
        <p><?php _e("Selecione uma conta WhatsApp para carregar os grupos.") ?></p>
    </div>
<?php endif ?>

<!-- Modal de clonagem de grupo (estilo moderno do tema) -->
<style>
    .wep-clone-modal .modal-dialog {
        max-width: 430px;
        transform: translateY(18px) scale(0.96);
        transition: transform 0.28s ease, opacity 0.28s ease;
    }
    .wep-clone-modal.show .modal-dialog {
        transform: translateY(0) scale(1);
    }
    .wep-clone-modal .modal-content {
        border: 0;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 24px 80px rgba(15, 23, 42, 0.22);
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }
    .wep-clone-modal .modal-body { padding: 28px; }
    .wep-clone-hero {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 18px;
    }
    .wep-clone-icon {
        width: 58px;
        height: 58px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        color: #0d6efd;
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.14), rgba(13, 202, 240, 0.18));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
        font-size: 22px;
    }
    .wep-clone-title {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 700;
        color: #1f2937;
    }
    .wep-clone-subtitle {
        margin: 4px 0 0;
        font-size: 0.92rem;
        color: #64748b;
    }
    .wep-clone-message {
        margin: 0 0 18px;
        font-size: 0.98rem;
        line-height: 1.65;
        color: #334155;
    }
    .wep-clone-label {
        display: block;
        margin-bottom: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
    }
    .wep-clone-input {
        height: 48px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        font-size: 0.98rem;
        padding: 0 16px;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .wep-clone-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.12);
    }
    .wep-clone-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }
    .wep-clone-actions .btn {
        flex: 1 1 0;
        min-height: 46px;
        border-radius: 14px;
        font-weight: 600;
    }
    .wep-clone-actions .btn-light {
        background: #eef2f7;
        border-color: #e2e8f0;
        color: #475569;
    }
    .wep-clone-confirm {
        background: linear-gradient(135deg, #0d6efd, #0b57d0);
        border-color: transparent;
        color: #ffffff;
        box-shadow: 0 14px 34px rgba(13, 110, 253, 0.22);
    }
    .wep-clone-confirm:hover { color: #ffffff; filter: brightness(1.05); }
</style>

<div class="modal fade wep-clone-modal" id="wep-clone-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="wep-clone-hero">
                    <div class="wep-clone-icon">
                        <i class="fad fa-clone"></i>
                    </div>
                    <div>
                        <h5 class="wep-clone-title"><?php _e("Clonar grupo") ?></h5>
                        <p class="wep-clone-subtitle"><?php _e("Crie um grupo novo com os mesmos participantes.") ?></p>
                    </div>
                </div>

                <p class="wep-clone-message"><?php _e("Será criado um grupo novo com os mesmos participantes. O seu número vira administrador e não entra na lista.") ?></p>

                <label class="wep-clone-label" for="wep-clone-name"><?php _e("Nome do novo grupo") ?></label>
                <input type="text" id="wep-clone-name" class="form-control wep-clone-input" maxlength="25" autocomplete="off">

                <div class="wep-clone-actions">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php _e("Cancelar") ?></button>
                    <button type="button" class="btn wep-clone-confirm" id="wep-clone-confirm">
                        <i class="fad fa-clone me-2"></i> <?php _e("Clonar grupo") ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function() {
    var $search = $('#wep-group-search');
    var $clear = $('#wep-search-clear');
    var $grid = $('#wep-groups-grid');
    var $noResults = $('#wep-no-results');

    function filterGroups() {
        var term = ($search.val() || '').trim().toLowerCase();
        var visible = 0;

        $grid.find('.wep-group').each(function() {
            var haystack = ($(this).data('search') || '').toLowerCase();
            var match = !term || haystack.indexOf(term) !== -1;
            $(this).toggleClass('hidden', !match);
            if (match) visible++;
        });

        if ($noResults.length) {
            $noResults.toggle(term !== '' && visible === 0);
        }

        if ($clear.length) {
            $clear.toggle(term !== '');
        }
    }

    if ($search.length) {
        $search.on('input', filterGroups);
        $clear.on('click', function() {
            $search.val('').trigger('input').trigger('focus');
        });
    }

    // ===== Clonar grupo =====
    var $cloneModal = $('#wep-clone-modal');
    var cloneAccountId = null;
    var cloneGroupId = null;
    var cloneBaseUrl = '<?php _e(get_module_url("clone_group")) ?>';

    $grid.on('click', '.wep-btn-clone', function() {
        var $btn = $(this);
        cloneAccountId = $btn.data('account-id');
        cloneGroupId = $btn.data('group-id');
        $('#wep-clone-name').val($btn.data('target-name') || '');
        $cloneModal.modal('show');
    });

    $('#wep-clone-confirm').on('click', function() {
        if (!cloneAccountId || !cloneGroupId) {
            return;
        }
        var name = ($('#wep-clone-name').val() || '').trim();
        if (!name) {
            Core.notify('<?php _e('Informe o nome do novo grupo.') ?>', 'error');
            return;
        }

        $cloneModal.modal('hide');

        // Contagem regressiva nativa + confirma\u00e7\u00e3o (igual aos outros bot\u00f5es)
        Core.showConfirmDialog({
            title: '<?php _e('Confirmar clonagem') ?>',
            message: '<?php _e('Criar o grupo') ?> "' + name + '" <?php _e('com os mesmos participantes do grupo original?') ?>',
            hint: '<?php _e('O seu número vira administrador do novo grupo e não entra na lista de participantes.') ?>',
            confirmText: '<?php _e('Clonar grupo') ?>',
            releaseDelay: 2000,
            onConfirm: function() {
                runClone(name);
            }
        });
    });

    function runClone(name) {
        // Anima\u00e7\u00e3o de cria\u00e7\u00e3o nativa do tema (anel girando + barra de progresso)
        var actionDialog = Core.showActionDialog({
            type: 'duplicate',
            icon: 'fad fa-clone',
            title: '<?php _e('Clonando grupo') ?>',
            message: '<?php _e('Estamos criando o grupo novo e adicionando os participantes. Isso pode levar alguns segundos.') ?>'
        });

        // Delay m\u00ednimo para a anima\u00e7\u00e3o ser percept\u00edvel
        var startedAt = Date.now();
        var MIN_DELAY = 1800;

        $.post(
            cloneBaseUrl + '/' + cloneAccountId + '/' + encodeURIComponent(cloneGroupId),
            $.param({ csrf: csrf, target_name: name }),
            function(result) {
                try {
                    if (typeof result !== 'object') { result = $.parseJSON(result); }
                } catch (e) { result = null; }

                var remaining = Math.max(0, MIN_DELAY - (Date.now() - startedAt));
                setTimeout(function() {
                    if (result && result.status === 'success') {
                        Core.finishActionDialog('success', result.message || '<?php _e('Grupo enfileirado para clonagem.') ?>', actionDialog);
                    } else {
                        Core.finishActionDialog('error', (result && result.message) || '<?php _e('Não foi possível clonar o grupo.') ?>', actionDialog);
                    }
                }, remaining);
            }
        ).fail(function() {
            var remaining = Math.max(0, MIN_DELAY - (Date.now() - startedAt));
            setTimeout(function() {
                Core.finishActionDialog('error', '<?php _e('Não foi possível comunicar com o servidor. Tente novamente.') ?>', actionDialog);
            }, remaining);
        });
    }
});
</script>
