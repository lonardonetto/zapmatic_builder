<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.wep-page { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }

/* ===== HERO ===== */
.wep-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #0f172a 100%);
    border-radius: 18px;
    padding: 26px 30px;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
}
.wep-hero::before {
    content: '';
    position: absolute;
    top: -70px; right: -70px;
    width: 240px; height: 240px;
    background: radial-gradient(circle, rgba(0,158,247,0.18) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.wep-hero::after {
    content: '';
    position: absolute;
    bottom: -90px; left: 30%;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(80,205,137,0.12) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.wep-hero-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; position: relative; z-index: 2; }
.wep-hero-left { display: flex; align-items: center; gap: 16px; min-width: 0; }
.wep-hero-icon {
    width: 54px; height: 54px; flex-shrink: 0;
    background: linear-gradient(135deg, #009ef7, #4cc9f0);
    border-radius: 15px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff;
    box-shadow: 0 8px 24px rgba(0,158,247,0.35);
}
.wep-hero-text h2 { margin: 0; font-size: 20px; font-weight: 800; color: #fff; letter-spacing: -0.01em; }
.wep-hero-text p { margin: 4px 0 0; font-size: 13px; color: rgba(255,255,255,0.55); }
.wep-hero-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.wep-account-select {
    min-width: 240px;
    border-radius: 11px;
    border: 1px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.08);
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    padding: 10px 14px;
    outline: none;
    cursor: pointer;
}
.wep-account-select option { color: #181C32; background: #fff; }
.wep-account-select:focus { border-color: rgba(0,158,247,0.7); box-shadow: 0 0 0 3px rgba(0,158,247,0.2); }

.wep-help-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 16px; border-radius: 11px;
    font-size: 13px; font-weight: 600;
    color: rgba(255,255,255,0.85);
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    text-decoration: none; cursor: pointer; transition: all 0.2s;
}
.wep-help-btn:hover { background: rgba(255,255,255,0.14); color: #fff; text-decoration: none; }
</style>

<div class="wep-page">
    <form class="actionForm formExportGroup" action="<?php _e(get_module_url("groups")) ?>" method="POST" data-result="html" data-content="ajax-result" data-redirect="false" data-loading="false">

        <div class="wep-hero">
            <div class="wep-hero-inner">
                <div class="wep-hero-left">
                    <div class="wep-hero-icon"><i class="<?php _ec($config['icon']) ?>"></i></div>
                    <div class="wep-hero-text">
                        <h2><?php _ec($config['name']) ?></h2>
                        <p><?php _e($config['desc']) ?></p>
                    </div>
                </div>
                <div class="wep-hero-right">
                    <select name="account" data-control="select2" data-hide-search="true" class="wep-account-select wa_account form-select form-select-sm auto-submit">
                        <option value="0" data-icon="fab fa-whatsapp" data-icon-color="#25d366" selected><?php _e("Select WhatsApp account") ?></option>
                        <?php if (!empty($accounts)) : ?>
                            <?php foreach ($accounts as $key => $value) : ?>
                                <?php
                                $numero = $value->name;
                                if (preg_match('/^(\d+)@s\.whatsapp\.net$/', $value->name, $m)) {
                                    $n = $m[1];
                                    $numero = substr($n,0,2)." (".substr($n,2,2).") ".substr($n,4);
                                }
                                ?>
                                <option value="<?php _ec($value->ids) ?>" data-img="<?php _ec(get_file_url($value->avatar)) ?>">
                                    <?php _ec($numero) ?>
                                </option>
                            <?php endforeach ?>
                        <?php endif ?>
                    </select>
                    <button type="button" class="wep-help-btn" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        <i class="fas fa-circle-question"></i> <?php _e("Help") ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal ajuda -->
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"><?php _e("How to use?") ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ol class="mb-0" style="padding-left: 18px; line-height: 2;">
                            <li><?php _e("1. Send a message to group you want export participants") ?></li>
                            <li><?php _e("2. Select account you want export participants") ?></li>
                            <li><?php _e("3. Click Download or Create Contact List of group you want") ?></li>
                        </ol>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e("Close") ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="ajax-result">
            <?php _ec($this->include('Core\Whatsapp\Views\empty'), false); ?>
        </div>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>
<script type="text/javascript">
    $(function() {
        var clipboardGroupId = new ClipboardJS('.btn-copy-id');
        var clipboardInvite = new ClipboardJS('.btn-copy-invite');

        function setupClipboardEvents(clipboard, successMessage) {
            clipboard.on('success', function(e) {
                Core.notify(successMessage, 'success');
                e.clearSelection();
            });
        }

        setupClipboardEvents(clipboardGroupId, '<?php _e('Group Id was copied to clipboard') ?>');
        setupClipboardEvents(clipboardInvite, '<?php _e('Invite Link was copied to Clipboard') ?>');
    });
</script>
