<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
    <div class="bd-search position-relative me-auto mt-5">
        <div class="mb-5">
            <h2><i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _ec($config['name']) ?></h2>
            <p><?php _e($config['desc']) ?></p>
        </div>
    </div>
</div>
<div class="container">
    <form method="POST">
        <div class="card b-r-10 mb-5">
            <div class="card-body p-10">

                <select name="account" data-control="select2" data-hide-search="true" class="form-select form-select-sm bg-body fw-bold border-0 miw-130 auto-submit">
                    <option value="609ACF283XXXX" data-icon="fab fa-whatsapp" data-icon-color="#25d366"><span><?php _e("Select WhatsApp account") ?></span></option>
                    <?php if (!empty($accounts)) : ?>

                        <?php foreach ($accounts as $key => $value) : ?>
                            <?php $lt=(int)($value->login_type??2); $gc=$lt===1?'#0d6efd':($lt===3?'#0dcaf0':'#ffc107'); $gl=$lt===1?'Cloud API':($lt===3?'Go':'Local'); ?>
                            <option value="<?php _ec($value->token) ?>" <?php _ec($account == $value->token ? 'selected' : '')  ?> data-img="<?php _ec(get_file_url($value->avatar)) ?>" data-gw-label="<?php _ec($gl) ?>" data-gw-color="<?php _ec($gc) ?>"><?php _ec($value->name) ?></option>
                        <?php endforeach ?>

                    <?php else : ?>

                    <?php endif ?>
                </select>

            </div>
        </div>
    </form>
</div>

<div class="container mb-5 card p-25 b-r-10 text-gray-700">
    <div class="row">
        <div class="col-12">
            <div class="alert alert-success p-20 m-b-30" role="alert">
                <?php _e("Your Access Token:") ?> <strong><?php _ec(get_team("ids")) ?></strong>
            </div>

            
<style>
    .swagger-wrapper { max-width: 1200px; margin: 0 auto; }
    .swagger-card { border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); background: #fff; }
    .swagger-header { padding: 10px 16px; display: flex; align-items: center; background: #f8fafc; cursor: pointer; transition: all 0.2s; border-bottom: 1px solid transparent; }
    .swagger-card.is-open .swagger-header { border-bottom-color: #e2e8f0; background: #f1f5f9; }
    .swagger-header:hover { background: #f1f5f9; }
    .swagger-method { font-weight: 700; font-size: 13px; padding: 5px 12px; border-radius: 4px; margin-right: 15px; color: #fff; min-width: 80px; text-align: center; text-transform: uppercase; }
    .swagger-method.post { background: #10b981; }
    .swagger-method.get { background: #3b82f6; }
    .swagger-path { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 14px; color: #0f172a; font-weight: 600; flex-grow: 1; }
    .swagger-title { font-size: 13px; color: #64748b; margin-left: 15px; }
    
    .swagger-body { padding: 0; display: none; }
    .swagger-card.is-open .swagger-body { display: block; }
    
    .swagger-section-title { font-size: 22px; font-weight: 700; color: #1e293b; margin: 40px 0 20px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }
    
    .swagger-info-block { padding: 20px; border-bottom: 1px solid #e2e8f0; background: #fff; }
    .swagger-desc { color: #475569; font-size: 14px; margin-bottom: 15px; line-height: 1.5; }
    
    .swagger-url-box { background: #1e293b; color: #e2e8f0; border-radius: 6px; padding: 12px 45px 12px 15px; font-family: 'SFMono-Regular', Consolas, monospace; word-break: break-all; border: none; font-size: 13px; position: relative; margin-top: 5px; }
    .swagger-url-box code { color: #e2e8f0; background: transparent; padding: 0; }
    .swagger-url-box .copy-btn { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.1); border: none; color: #94a3b8; border-radius: 4px; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; transition: all 0.2s; }
    .swagger-url-box .copy-btn:hover { background: rgba(255,255,255,0.2); color: #fff; }
    
    .swagger-params-block { padding: 20px; background: #f8fafc; }
    .swagger-params-title { font-size: 14px; font-weight: 600; color: #334155; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 0.5px; }
    
    .swagger-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 6px; overflow: hidden; border: 1px solid #e2e8f0; }
    .swagger-table th { text-align: left; padding: 12px 15px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600; font-size: 12px; background: #f1f5f9; text-transform: uppercase; letter-spacing: 0.5px; }
    .swagger-table td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; font-size: 14px; color: #334155; }
    .swagger-table tr:last-child td { border-bottom: none; }
    .swagger-table td.param-name { font-weight: 600; color: #0f172a; font-family: 'SFMono-Regular', Consolas, monospace; width: 30%; font-size: 13px; }
    .swagger-table td.param-name .fw-6 { font-weight: 600; }
    
    .toggle-icon { transition: transform 0.3s ease; color: #94a3b8; font-size: 12px; }
    .swagger-card.is-open .toggle-icon { transform: rotate(180deg); color: #475569; }

    .swagger-pre-box { background: #1e293b; color: #e2e8f0; border-radius: 6px; padding: 15px 45px 15px 15px; font-family: 'SFMono-Regular', Consolas, monospace; border: none; font-size: 13px; position: relative; margin-top: 5px; overflow-x: auto; }
    .swagger-pre-box pre { margin: 0; padding: 0; background: transparent; color: inherit; white-space: pre-wrap; word-break: break-all; }
    .swagger-pre-box code { color: #e2e8f0; background: transparent; padding: 0; font-size: 13px; }
    .swagger-pre-box .copy-btn { position: absolute; right: 8px; top: 12px; background: rgba(255,255,255,0.1); border: none; color: #94a3b8; border-radius: 4px; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; transition: all 0.2s; }
    .swagger-pre-box .copy-btn:hover { background: rgba(255,255,255,0.2); color: #fff; }
    .swagger-block-label { font-weight: 700; font-size: 12px; text-transform: uppercase; color: #64748b; margin-bottom: 8px; display: block; letter-spacing: 0.5px; }
    .swagger-block-label .badge-method { background: #10b981; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-right: 5px; }

</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.swagger-header').forEach(function(header) {
            header.addEventListener('click', function() {
                this.closest('.swagger-card').classList.toggle('is-open');
            });
        });
        
        document.querySelectorAll('.swagger-url-box .copy-btn, .swagger-pre-box .copy-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var box = this.closest('.swagger-url-box') || this.closest('.swagger-pre-box');
                var codeEl = box.querySelector('code');
                var text = codeEl.textContent.trim();
                navigator.clipboard.writeText(text).then(() => {
                    var icon = this.querySelector('i');
                    icon.className = 'fas fa-check text-success';
                    setTimeout(() => { icon.className = 'far fa-copy'; }, 2000);
                });
            });
        });
    });
</script>

<h3 class="swagger-section-title"><?php _e("Instance Api") ?></h3><div class="swagger-wrapper">
            
        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/create_instance</div>
                <div class="swagger-title"><?php _e("Create Instance") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="swagger-desc"><?php _e("Create a new Instance ID") ?></div>
                    <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Endpoint URL</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/create_instance?access_token=" . get_team("ids"))) ?></code>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/send_pedido</div>
                <div class="swagger-title"><?php _e("Send Pedido") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="swagger-desc"><?php _e("Envie notificações de <b>status de pedido<b>")?></div>
                    <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Endpoint URL</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/send_pedido?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec( get_team("ids") )?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method get"><?php _e("GET") ?></div>
                <div class="swagger-path">/api/get_qrcode</div>
                <div class="swagger-title"><?php _e("Get QR Code") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="swagger-desc"><?php _e("Display QR code to login to Whatsapp web. You can get the results returned via Webhook") ?></div>
                    <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Endpoint URL</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/get_qrcode?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        
<?php if(get_option('wa_paircode') == 1):?>
        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method get"><?php _e("GET") ?></div>
                <div class="swagger-path">/api/get_paircode</div>
                <div class="swagger-title"><?php _e("Get Pairing Code") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="swagger-desc"><?php _e("Get pairing code to login to Whatsapp web.") ?></div>
                    <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Endpoint URL</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/get_paircode?instance_id=".  $account ."&access_token=" . get_team("ids")."&phone=62815xxxxxxxx")) ?></code>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                    <tr><td class="param-name">phone</td>
                        <td>62815xxxxxxxx</td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        
<?php endif ?>
        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/set_webhook</div>
                <div class="swagger-title"><?php _e("Set Receving Webhook") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="swagger-desc"><?php _e("Get all return values from Whatsapp. Like connection status, Incoming message, Outgoing message, Disconnected, Change Battery,...") ?></div>
                    <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Endpoint URL</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/set_webhook?webhook_url=https://webhook.site/1b25464d6833784f96eef4xxxxxxxxxx&enable=true&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">webhook_url</td>
                        <td>https://webhook.site/1b25464d6833784f96eef4xxxxxxxxxx</td>
                    </tr>
                    <tr><td class="param-name">enable</td>
                        <td>true</td>
                    </tr>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/reboot</div>
                <div class="swagger-title"><?php _e("Reboot Instance") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="swagger-desc"><?php _e("Logout Whatsapp web and do a fresh scan") ?></div>
                    <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Endpoint URL</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/reboot?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/reset_instance</div>
                <div class="swagger-title"><?php _e("Reset Instance") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="swagger-desc"><?php _e("This will logout Whatsapp web, Change Instance ID, Delete all old instance data") ?></div>
                    <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Endpoint URL</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/reset_instance?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/reconnect</div>
                <div class="swagger-title"><?php _e("Reconnect") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="swagger-desc"><?php _e("Re-initiate connection from app to Whatsapp web when lost connection") ?></div>
                    <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Endpoint URL</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/reconnect?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        
<h3 class="swagger-section-title"><?php _e("Send Direct Message Api") ?></h3>
        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/send</div>
                <div class="swagger-title"><?php _e("Send Text") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <span class="swagger-block-label"><span class="badge-method">POST</span> Resource URL (cole no Postman)</span>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/send?number=84933313xxx&type=text&message=test%20message&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">Body (JSON) — copie e cole no Postman</span>
                        <div class="swagger-pre-box">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>{
    "number": "5521970402529",
    "type": "text",
    "message": "Olá! Esta é uma mensagem de teste via API.",
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>"
}</code></pre>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">cURL — rode no terminal</span>
                        <div class="swagger-pre-box" style="background:#282a36;">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>curl -X POST "<?php _ec(base_url("api/send")) ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "number": "5521970402529",
    "type": "text",
    "message": "Olá! Esta é uma mensagem de teste via API.",
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>"
}' </code></pre>
                        </div>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">number</td>
                        <td>84933313xxx</td>
                    </tr>
                    <tr><td class="param-name">type</td>
                        <td>text</td>
                    </tr>
                    <tr><td class="param-name">message</td>
                        <td><?php _ec("test message") ?></td>
                    </tr>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card" style="border-color:#3b82f6;">
            <div class="swagger-header" style="background:#eff6ff;">
                <div class="swagger-method get" style="background:#3b82f6;">GET</div>
                <div class="swagger-path" style="color:#1d4ed8;">/api/get_templates</div>
                <div class="swagger-title" style="color:#1e40af;">Lista de Templates (copie o ID para usar no campo "template")</div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="swagger-desc">Estes são os templates interativos criados no seu Bot Builder. Copie o <strong>ID (hash)</strong> da coluna "ID do Template" e cole no parâmetro <code>"template"</code> do endpoint <code>/api/send</code>.</div>
                    <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Endpoint URL</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/get_templates?type=1&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Templates Disponíveis</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th style="width:25%">ID do Template (copie este)</th>
                                <th>Nome</th>
                                <th style="width:15%">Tipo</th>
                                <th style="width:80px">Copiar</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $team_id = get_team("id");
                        $templates = db_fetch("*", TB_WHATSAPP_TEMPLATE, ["team_id" => $team_id]);
                        $type_names = [1 => "List", 2 => "Button", 3 => "Poll", 5 => "Carousel", 6 => "Mídia", 66 => "Texto"];
                        $type_colors = [1 => "#3b82f6", 2 => "#10b981", 3 => "#f59e0b", 5 => "#8b5cf6", 6 => "#ec4899", 66 => "#6b7280"];
                        if (!empty($templates)) {
                            foreach ($templates as $tpl) {
                                $tpl_type = $type_names[$tpl->type] ?? "Tipo {$tpl->type}";
                                $tpl_color = $type_colors[$tpl->type] ?? "#6b7280";
                                echo '<tr>';
                                echo '<td class="param-name" style="cursor:pointer;" onclick="copyTemplateId(this)" data-id="'.$tpl->ids.'">'.$tpl->ids.'</td>';
                                echo '<td>'.htmlspecialchars($tpl->name).'</td>';
                                echo '<td><span style="background:'.$tpl_color.';color:#fff;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;">'.$tpl_type.'</span></td>';
                                echo '<td><button class="btn btn-sm btn-outline-primary" onclick="copyTemplateIdById(\''.$tpl->ids.'\')"><i class="far fa-copy"></i></button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:30px;">Nenhum template encontrado. Crie templates no Bot Builder primeiro.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
        function copyTemplateId(td) {
            var id = td.getAttribute('data-id');
            navigator.clipboard.writeText(id).then(function() {
                var icon = td.querySelector('i') || td;
                td.style.color = '#10b981';
                setTimeout(function() { td.style.color = '#0f172a'; }, 1500);
            });
        }
        function copyTemplateIdById(id) {
            navigator.clipboard.writeText(id).then(function() {
                alert('ID copiado: ' + id);
            });
        }
        </script>
        
        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/send</div>
                <div class="swagger-title"><?php _e("Send Poll, Button, List, Carousel") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <span class="swagger-block-label"><span class="badge-method">POST</span> Resource URL (cole no Postman)</span>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/send?number=84933313xxx&type=poll&template=templateids&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">Body (JSON) — copie e cole no Postman</span>
                        <div class="swagger-pre-box">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>{
    "number": "5521970402529",
    "type": "poll",
    "template": "SEU_TEMPLATE_ID",
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>"
}</code></pre>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">cURL — rode no terminal</span>
                        <div class="swagger-pre-box" style="background:#282a36;">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>curl -X POST "<?php _ec(base_url("api/send")) ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "number": "5521970402529",
    "type": "carousel",
    "template": "SEU_TEMPLATE_ID",
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>"
}' </code></pre>
                        </div>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">number</td>
                        <td>84933313xxx</td>
                    </tr>
                    <tr><td class="param-name">type</td>
                        <td>button/poll/list/carousel</td>
                    </tr>
                    <tr><td class="param-name">template</td>
                        <td><?php _ec("template ids") ?></td>
                    </tr>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/send</div>
                <div class="swagger-title"><?php _e("Send Media & File") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <span class="swagger-block-label"><span class="badge-method">POST</span> Resource URL (cole no Postman)</span>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/send?number=84933313xxx&type=media&message=test%20message&media_url=https://i.pravatar.cc&filename=file_test.jpg&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">Body (JSON) — copie e cole no Postman</span>
                        <div class="swagger-pre-box">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>{
    "number": "5521970402529",
    "type": "media",
    "message": "Veja esta imagem",
    "media_url": "https://i.pravatar.cc",
    "filename": "file_test.jpg",
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>"
}</code></pre>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">cURL — rode no terminal</span>
                        <div class="swagger-pre-box" style="background:#282a36;">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>curl -X POST "<?php _ec(base_url("api/send")) ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "number": "5521970402529",
    "type": "media",
    "message": "Veja esta imagem",
    "media_url": "https://i.pravatar.cc",
    "filename": "file_test.jpg",
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>"
}' </code></pre>
                        </div>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">number</td>
                        <td>84933313xxx</td>
                    </tr>
                    <tr><td class="param-name">type</td>
                        <td>media</td>
                    </tr>
                    <tr><td class="param-name">message</td>
                        <td><?php _ec("test message") ?></td>
                    </tr>
                    <tr><td class="param-name">media_url</td>
                        <td>https://i.pravatar.cc</td>
                    </tr>
                    <tr><td class="param-name">filename <span class="text-danger small">(<?php _e("Just use for send document") ?>)</span></td>
                        <td>file_test.pdf</td>
                    </tr>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        
<h3 class="swagger-section-title"><?php _e("Group Api") ?></h3>
        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/get_groups</div>
                <div class="swagger-title"><?php _e("Get Groups from Instance") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="swagger-desc"></div>
                    <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Endpoint URL</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/get_groups?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/send_group</div>
                <div class="swagger-title"><?php _e("Send Text Message Group") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <span class="swagger-block-label"><span class="badge-method">POST</span> Resource URL (cole no Postman)</span>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/send_group?group_id=84987694574-1618740914@g.us&type=text&message=test%20message&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">Body (JSON) — copie e cole no Postman</span>
                        <div class="swagger-pre-box">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>{
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>",
    "name": "Nome do Grupo",
    "participants": [
        "559684040268@s.whatsapp.net",
        "55968100xxxx@s.whatsapp.net"
    ]
}</code></pre>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">cURL — rode no terminal</span>
                        <div class="swagger-pre-box" style="background:#282a36;">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>curl -X POST "<?php _ec(base_url("api/create_groups")) ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>",
    "name": "Nome do Grupo",
    "participants": [
        "559684040268@s.whatsapp.net",
        "55968100xxxx@s.whatsapp.net"
    ]
}' </code></pre>
                        </div>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">group_id</td>
                        <td>84987694574-1618740914@g.us</td>
                    </tr>
                    <tr><td class="param-name">type</td>
                        <td>text</td>
                    </tr>
                    <tr><td class="param-name">message</td>
                        <td><?php _ec("test message") ?></td>
                    </tr>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/create_groups</div>
                <div class="swagger-title"><?php _e("Create new Group") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <span class="swagger-block-label"><span class="badge-method">POST</span> Resource URL (cole no Postman)</span>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/add_participants")) ?></code>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">Body (JSON) — copie e cole no Postman</span>
                        <div class="swagger-pre-box">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>{
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>",
    "group_id": "xyz@g.us",
    "type": "add",
    "participants": [
        "55968100xxxx@s.whatsapp.net",
        "55968401xxxx@s.whatsapp.net"
    ]
}</code></pre>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">cURL — rode no terminal</span>
                        <div class="swagger-pre-box" style="background:#282a36;">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>curl -X POST "<?php _ec(base_url("api/add_participants")) ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>",
    "group_id": "xyz@g.us",
    "type": "add",
    "participants": [
        "55968100xxxx@s.whatsapp.net",
        "55968401xxxx@s.whatsapp.net"
    ]
}' </code></pre>
                        </div>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                    <tr><td class="param-name">name</td>
                        <td>Group Name</td>
                    </tr>
                    <tr><td class="param-name">participants</td>
                        <td>559684040268@s.whatsapp.net</td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/add_participants</div>
                <div class="swagger-title"><?php _e("Add Participants") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <span class="swagger-block-label"><span class="badge-method">POST</span> Resource URL (cole no Postman)</span>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/remove_participants")) ?></code>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">Body (JSON) — copie e cole no Postman</span>
                        <div class="swagger-pre-box">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>{
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>",
    "group_id": "xyz@g.us",
    "type": "remove",
    "participants": [
        "55968100xxxx@s.whatsapp.net",
        "55968401xxxx@s.whatsapp.net"
    ]
}</code></pre>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">cURL — rode no terminal</span>
                        <div class="swagger-pre-box" style="background:#282a36;">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>curl -X POST "<?php _ec(base_url("api/remove_participants")) ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>",
    "group_id": "xyz@g.us",
    "type": "remove",
    "participants": [
        "55968100xxxx@s.whatsapp.net",
        "55968401xxxx@s.whatsapp.net"
    ]
}' </code></pre>
                        </div>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                    <tr><td class="param-name">group_id</td>
                        <td>xyz@g.us</td>
                    </tr>
                    <tr><td class="param-name">type</td>
                        <td>add</td>
                    </tr>
                    <tr><td class="param-name">participants</td>
                        <td>1234@s.whatsapp.net</td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/remove_participants</div>
                <div class="swagger-title"><?php _e("Remove Participants") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Resource URL</label>
                            <div class="swagger-url-box">
                                <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                                <code><?php _ec(base_url("api/remove_participants")) ?></code>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Body (JSON)</label>
                            <div class="swagger-url-box" style="background:#282a36;">
                                <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                                <code>{<br>
                    <span class="ms-4">"instance_id": "<?php _e($account) ?>",</span><br>
                    <span class="ms-4">"access_token": "<?php _ec(get_team("ids")) ?>",</span><br>
                    <span class="ms-4">"group_id": "xyz@g.us",</span><br>
                    <span class="ms-4">"type": "remove",</span><br>
                    <span class="ms-4">"participants": [
                        "55968100xxxx@s.whatsapp.net",
                        "55968401xxxx@s.whatsapp.net"
                    ]</span><br>
                    }</code>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                    <tr><td class="param-name">group_id</td>
                        <td>xyz@g.us</td>
                    </tr>
                    <tr><td class="param-name">type</td>
                        <td>remove</td>
                    </tr>
                    <tr><td class="param-name">participants</td>
                        <td>1234@s.whatsapp.net</td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="alert alert-info" style="border-radius:8px;padding:12px 20px;margin-bottom:15px;">
            <i class="fas fa-info-circle me-2"></i> <strong>Atenção:</strong> Para enviar Poll, Button, List ou Carousel para grupos, você precisa do <strong>ID do Template</strong>. Role a página até o bloco azul <strong>"Lista de Templates"</strong> no topo para copiar o ID.
        </div>
        
        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/send_group</div>
                <div class="swagger-title"><?php _e("Send Poll, Button, List, Carousel") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <span class="swagger-block-label"><span class="badge-method">POST</span> Resource URL (cole no Postman)</span>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/send_group?group_id=84987694574-1618740914@g.us&type=poll&template=templateids&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">Body (JSON) — copie e cole no Postman</span>
                        <div class="swagger-pre-box">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>{
    "group_id": "84987694574-1618740914@g.us",
    "type": "carousel",
    "template": "SEU_TEMPLATE_ID",
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>"
}</code></pre>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">cURL — rode no terminal</span>
                        <div class="swagger-pre-box" style="background:#282a36;">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>curl -X POST "<?php _ec(base_url("api/send_group")) ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "group_id": "84987694574-1618740914@g.us",
    "type": "carousel",
    "template": "SEU_TEMPLATE_ID",
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>"
}' </code></pre>
                        </div>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">group_id</td>
                        <td>84987694574-1618740914@g.us</td>
                    </tr>
                    <tr><td class="param-name">type</td>
                        <td>button/poll/list/carousel</td>
                    </tr>
                    <tr><td class="param-name">template</td>
                        <td><?php _ec("template ids") ?></td>
                    </tr>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method post"><?php _e("POST") ?></div>
                <div class="swagger-path">/api/send_group</div>
                <div class="swagger-title"><?php _e("Send Media & File Message Group") ?></div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <span class="swagger-block-label"><span class="badge-method">POST</span> Resource URL (cole no Postman)</span>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code><?php _ec(base_url("api/send_group?group_id=84987694574-1618740914@g.us&type=media&message=test%20message&media_url=https://i.pravatar.cc&filename=file_test.jpg&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?></code>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">Body (JSON) — copie e cole no Postman</span>
                        <div class="swagger-pre-box">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>{
    "group_id": "84987694574-1618740914@g.us",
    "type": "media",
    "message": "Veja esta imagem no grupo",
    "media_url": "https://i.pravatar.cc",
    "filename": "file_test.jpg",
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>"
}</code></pre>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="swagger-block-label">cURL — rode no terminal</span>
                        <div class="swagger-pre-box" style="background:#282a36;">
                            <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                            <pre><code>curl -X POST "<?php _ec(base_url("api/send_group")) ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "group_id": "84987694574-1618740914@g.us",
    "type": "media",
    "message": "Veja esta imagem no grupo",
    "media_url": "https://i.pravatar.cc",
    "filename": "file_test.jpg",
    "instance_id": "<?php _e($account) ?>",
    "access_token": "<?php _ec(get_team("ids")) ?>"
}' </code></pre>
                        </div>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <tr><td class="param-name">group_id</td>
                        <td>8498761xxxxxxxx@g.us</td>
                    </tr>
                    <tr><td class="param-name">type</td>
                        <td>media</td>
                    </tr>
                    <tr><td class="param-name">message</td>
                        <td><?php _ec("test message") ?></td>
                    </tr>
                    <tr><td class="param-name">media_url</td>
                        <td>https://i.pravatar.cc</td>
                    </tr>
                    <tr><td class="param-name">filename <span class="text-danger small">(<?php _e("Just use for send document") ?>)</span></td>
                        <td>file_test.pdf</td>
                    </tr>
                    <tr><td class="param-name">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr><td class="param-name">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </div>
        

</div></div>
</div>
</div>
