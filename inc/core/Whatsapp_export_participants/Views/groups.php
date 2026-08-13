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
});
</script>
