<form class="actionForm formExportGroup" action="<?php _e(get_module_url("groups")) ?>" method="POST" data-result="html" data-content="ajax-result" date-redirect="false" data-loading="false">

    <div class="container my-5 mw-1000">
        <div class="mb-5">
            <h2> <i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _ec($config['name']) ?></h2>
            <p><?php _e($config['desc']) ?></p>
        </div>

        <div class="card b-r-10 mb-4">
    <div class="card-body p-10 d-flex align-items-center">
        <select name="account" data-control="select2" data-hide-search="true" class="wa_account form-select form-select-sm bg-body fw-bold border-0 miw-130 auto-submit">
            <option value="0" data-icon="fab fa-whatsapp" data-icon-color="#25d366" selected>
                <span><?php _e("Select WhatsApp account") ?></span>
            </option>
            <?php if (!empty($accounts)) : ?>
                <?php foreach ($accounts as $key => $value) : ?>
                    <?php
                    // Verifica se o nome está no formato numero@s.whatsapp.net
                    if (preg_match('/^(\d+)@s\.whatsapp\.net$/', $value->name, $matches)) {
                        // Captura o número e o formata
                        $numero = $matches[1]; // O número completo
                        $ddi = substr($numero, 0, 2); // DDI (2 primeiros dígitos)
                        $ddd = substr($numero, 2, 2); // DDD (2 dígitos seguintes)
                        $telefone = substr($numero, 4); // Restante do número
                        $formattedNumber = $ddi . " (" . $ddd . ") " . $telefone; // Formato final
                    } else {
                        // Se não for um número, exibe o nome normalmente
                        $formattedNumber = $value->name;
                    }
                    ?>
                    <option value="<?php _ec($value->ids) ?>" data-img="<?php _ec(get_file_url($value->avatar)) ?>">
                        <?php _ec($formattedNumber) ?>
                    </option>
                <?php endforeach ?>
            <?php else : ?>
                <!-- Optionally handle the case where there are no accounts -->
            <?php endif ?>
        </select>
        <button type="button" class="btn btn-primary btn-sm ms-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <?php _e("Help")?>
        </button>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?php _e("Help")?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card b-r-10 mb-4">
            <div class="card-header px-4">
                <div class="card-title"><?php _e("How to use?") ?></div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush b-r-10">
                    <li class="list-group-item px-4 py-4"><?php _e("1. Send a message to group you want export participants") ?></li>
                    <li class="list-group-item px-4 py-4"><?php _e("2. Select account you want export participants") ?></li>
                    <li class="list-group-item px-4 py-4"><?php _e("3. Click Download button of group you want export on list") ?></li>
                </ul>
            </div>
        </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e("Close")?></button>
            </div>
        </div>
    </div>
</div>


        

        <div class="ajax-result">
            <?php _ec($this->include('Core\Whatsapp\Views\empty'), false); ?>
        </div>

    </div>

</form>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>

<script type="text/javascript">
    $(function() {
        // Cria um novo ClipboardJS para os botões de cópia
        var clipboardGroupId = new ClipboardJS('.btn-copy-id');
        var clipboardInvite = new ClipboardJS('.btn-copy-invite');

        // Função para configurar eventos de sucesso
        function setupClipboardEvents(clipboard, successMessage) {
            clipboard.on('success', function(e) {
                // console.info('Action:', e.action);
                // console.info('Text:', e.text);
                // console.info('Trigger:', e.trigger);
                Core.notify(successMessage, 'success');
                e.clearSelection();
            });
        }

        // Configura eventos de sucesso para ambos os Clipboards
        setupClipboardEvents(clipboardGroupId, '<?php _e('Group Id was copied to clipboard') ?>');
        setupClipboardEvents(clipboardInvite, '<?php _e('Invite Link was copied to Clipboard') ?>');

        // Função para verificar a condição e submeter o formulário
        function checkAndSubmit() {
            if ($(".wa_account").val() != 0) {
                $(".formExportGroup").submit();
            }
        }

        // Configura o intervalo para verificar e submeter o formulário
        setInterval(function() {
            checkAndSubmit();
        }, 900000);
    });
</script>
