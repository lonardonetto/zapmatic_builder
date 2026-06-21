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
                            <option value="<?php _ec($value->token) ?>" <?php _ec($account == $value->token ? 'selected' : '')  ?> data-img="<?php _ec(get_file_url($value->avatar)) ?>"><?php _ec($value->name) ?></option>
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

            <h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase"><?php _e("Instance Api") ?></h5>
            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="create-instance"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Create Instance") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/create_instance?access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text">
                <?php _e("Create a new Instance ID") ?>
            </div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>
            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="get-qr-code"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Send Pedido") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send_pedido?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text"><?php _e("Envie notificações de <b>status de pedido<b>")?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params")?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec( get_team("ids") )?></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="get-qr-code"><span class="text-success fw-6 m-r-5"><?php _e("GET") ?></span> <?php _e("Get QR Code") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/get_qrcode?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text"><?php _e("Display QR code to login to Whatsapp web. You can get the results returned via Webhook") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>
            
            <?php if(get_option('wa_paircode') == 1):?>
            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="get-qr-code"><span class="text-success fw-6 m-r-5"><?php _e("GET") ?></span> <?php _e("Get Pairing Code") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/get_paircode?instance_id=".  $account ."&access_token=" . get_team("ids")."&phone=62815xxxxxxxx")) ?>
                </code>
            </div>

            <div class="text"><?php _e("Get pairing code to login to Whatsapp web.") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">phone</td>
                        <td>62815xxxxxxxx</td>
                    </tr>
                </tbody>
            </table>
            <?php endif ?>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="set-receving-webhook"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Set Receving Webhook") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/set_webhook?webhook_url=https://webhook.site/1b25464d6833784f96eef4xxxxxxxxxx&enable=true&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text"><?php _e("Get all return values from Whatsapp. Like connection status, Incoming message, Outgoing message, Disconnected, Change Battery,...") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">webhook_url</td>
                        <td>https://webhook.site/1b25464d6833784f96eef4xxxxxxxxxx</td>
                    </tr>
                    <tr>
                        <td class="fw-6">enable</td>
                        <td>true</td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="reboot-instance"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Reboot Instance") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/reboot?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text">
                <?php _e("Logout Whatsapp web and do a fresh scan") ?>
            </div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="reset-instance"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Reset Instance") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/reset_instance?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text">
                <?php _e("This will logout Whatsapp web, Change Instance ID, Delete all old instance data") ?>
            </div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="reconnect"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Reconnect") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/reconnect?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text">
                <?php _e("Re-initiate connection from app to Whatsapp web when lost connection") ?>
            </div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row p-t-25 p-b-25">
        <div class="col-12">
            <h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase"><?php _e("Send Direct Message Api") ?></h5>
            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="send-text"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Send Text") ?></h6>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send?number=84933313xxx&type=text&message=test%20message&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "{int}",</span><br>
                    <span class="ms-4">"type": "text",</span><br>
                    <span class="ms-4">"message": "{string}",</span><br>
                    <span class="ms-4">"instance_id": "<?php _e($account) ?>",</span><br>
                    <span class="ms-4">"access_token": "<?php _ec(get_team("ids")) ?>"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send a text message to a phone number through the app") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">number</td>
                        <td>84933313xxx</td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>text</td>
                    </tr>
                    <tr>
                        <td class="fw-6">message</td>
                        <td><?php _ec("test message") ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>
            
            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="send-text"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Send Poll, Button, List") ?></h6>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send?number=84933313xxx&type=poll&template=templateids&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "{int}",</span><br>
                    <span class="ms-4">"type": "poll" , // button, list</span><br>
                    <span class="ms-4">"template": "template ids",</span><br>
                    <span class="ms-4">"instance_id": "<?php _e($account) ?>",</span><br>
                    <span class="ms-4">"access_token": "<?php _ec(get_team("ids")) ?>"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send a Template message to a phone number through the app") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">number</td>
                        <td>84933313xxx</td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>button/poll/list</td>
                    </tr>
                    <tr>
                        <td class="fw-6">template</td>
                        <td><?php _ec("template ids") ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="send-media"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Send Media & File") ?></h6>

            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send?number=84933313xxx&type=media&message=test%20message&media_url=https://i.pravatar.cc&filename=file_test.jpg&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "{int}",</span><br>
                    <span class="ms-4">"type": "media",</span><br>
                    <span class="ms-4">"message": "{string}",</span><br>
                    <span class="ms-4">"media_url": "{string}",</span><br>
                    <span class="ms-4">"instance_id": "<?php _e($account) ?>",</span><br>
                    <span class="ms-4">"access_token": "<?php _ec(get_team("ids")) ?>"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send a media or file with message to a phone number through the app") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">number</td>
                        <td>84933313xxx</td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>media</td>
                    </tr>
                    <tr>
                        <td class="fw-6">message</td>
                        <td><?php _ec("test message") ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">media_url</td>
                        <td>https://i.pravatar.cc</td>
                    </tr>
                    <tr>
                        <td class="fw-6">filename <span class="text-danger small">(<?php _e("Just use for send document") ?>)</span></td>
                        <td>file_test.pdf</td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row p-t-25 p-b-25">
        <div class="col-12">


            <h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase"><?php _e("Group Api") ?></h5>


            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="send-text-message-group"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Get Groups from Instance") ?></h6>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/get_groups?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>



            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="send-text-message-group"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Send Text Message Group") ?></h6>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send_group?group_id=84987694574-1618740914@g.us&type=text&message=test%20message&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send_group")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"group_id": "8498761xxxxxxxx@g.us",</span><br>
                    <span class="ms-4">"type": "text",</span><br>
                    <span class="ms-4">"message": "{string}",</span><br>
                    <span class="ms-4">"instance_id": "<?php _e($account) ?>",</span><br>
                    <span class="ms-4">"access_token": "<?php _ec(get_team("ids")) ?>"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send a text message to a group through the app") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">group_id</td>
                        <td>84987694574-1618740914@g.us</td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>text</td>
                    </tr>
                    <tr>
                        <td class="fw-6">message</td>
                        <td><?php _ec("test message") ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>
            
            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="send-text-message-group"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Create new Group") ?></h6>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/create_groups")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"instance_id": "<?php _e($account) ?>",</span><br>
                    <span class="ms-4">"access_token": "<?php _ec(get_team("ids")) ?>",</span><br>
                    <span class="ms-4">"name": "Group Name",</span><br>
                    <span class="ms-4">"participants": ["5596xxxxxxxx@s.whatsapp.net", "5596xxxxxxxx@s.whatsapp.net"]</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Creating new group through the app") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Query Params") ?></div>

            <table class="table table-striped table-borderless">
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">name</td>
                        <td>Group Name</td>
                    </tr>
                    <tr>
                        <td class="fw-6">participants</td>
                        <td>559684040268@s.whatsapp.net</td>
                    </tr>
                </tbody>
            </table>
            
            
            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="send-text-message-group"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Add Participants") ?></h6>
            
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/add_participants")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"instance_id": "<?php _e($account) ?>",</span><br>
                    <span class="ms-4">"access_token": "<?php _ec(get_team("ids")) ?>",</span><br>
                    <span class="ms-4">"group_id": "xyz@g.us",</span><br>
                    <span class="ms-4">"type": "add",</span><br>
                    <span class="ms-4">"participants": [
                        "55968100xxxx@s.whatsapp.net",
                        "55968401xxxx@s.whatsapp.net"
                    ]</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Add new participants") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Query Params") ?></div>

            <table class="table table-striped table-borderless">
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">group_id</td>
                        <td>xyz@g.us</td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>add</td>
                    </tr>
                    <tr>
                        <td class="fw-6">participants</td>
                        <td>1234@s.whatsapp.net</td>
                    </tr>
                </tbody>
            </table>
            
            
            
            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="send-text-message-group"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Remove Participants") ?></h6>
            
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/remove_participants")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"instance_id": "<?php _e($account) ?>",</span><br>
                    <span class="ms-4">"access_token": "<?php _ec(get_team("ids")) ?>",</span><br>
                    <span class="ms-4">"group_id": "xyz@g.us",</span><br>
                    <span class="ms-4">"type": "remove",</span><br>
                    <span class="ms-4">"participants": [
                        "55968100xxxx@s.whatsapp.net",
                        "55968401xxxx@s.whatsapp.net"
                    ]</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Remove participants") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Query Params") ?></div>

            <table class="table table-striped table-borderless">
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">group_id</td>
                        <td>xyz@g.us</td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>remove</td>
                    </tr>
                    <tr>
                        <td class="fw-6">participants</td>
                        <td>1234@s.whatsapp.net</td>
                    </tr>
                </tbody>
            </table>
            
            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="send-text"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Send Poll, Button, List") ?></h6>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send_group?group_id=84987694574-1618740914@g.us&type=poll&template=templateids&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send_group")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"group_id": "{string}",</span><br>
                    <span class="ms-4">"type": "poll" , // button, list</span><br>
                    <span class="ms-4">"template": "template ids",</span><br>
                    <span class="ms-4">"instance_id": "<?php _e($account) ?>",</span><br>
                    <span class="ms-4">"access_token": "<?php _ec(get_team("ids")) ?>"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send a Template message to a phone number through the app") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">group_id</td>
                        <td>84987694574-1618740914@g.us</td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>button/poll/list</td>
                    </tr>
                    <tr>
                        <td class="fw-6">template</td>
                        <td><?php _ec("template ids") ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="send-media-message-group"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Send Media & File Message Group") ?></h6>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send_group?group_id=84987694574-1618740914@g.us&type=media&message=test%20message&media_url=https://i.pravatar.cc&filename=file_test.jpg&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send_group")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"group_id": "8498761xxxxxxxx@g.us",</span><br>
                    <span class="ms-4">"type": "media",</span><br>
                    <span class="ms-4">"message": "{string}",</span><br>
                    <span class="ms-4">"media_url": "{string}",</span><br>
                    <span class="ms-4">"instance_id": "<?php _e($account) ?>",</span><br>
                    <span class="ms-4">"access_token": "<?php _ec(get_team("ids")) ?>"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send a media or file with message to a group through the app") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">group_id</td>
                        <td>8498761xxxxxxxx@g.us</td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>media</td>
                    </tr>
                    <tr>
                        <td class="fw-6">message</td>
                        <td><?php _ec("test message") ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">media_url</td>
                        <td>https://i.pravatar.cc</td>
                    </tr>
                    <tr>
                        <td class="fw-6">filename <span class="text-danger small">(<?php _e("Just use for send document") ?>)</span></td>
                        <td>file_test.pdf</td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>