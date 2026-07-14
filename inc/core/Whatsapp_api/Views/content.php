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
                            <?php $lt=(int)($value->login_type??2); $gc=$lt===1?'#0d6efd':($lt===3?'#0dcaf0':'#ffc107'); $gl=$lt===1?'Cloud API':($lt===3?'Go':'Baileys'); ?>
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
    .swagger-method.put { background: #f59e0b; }
    .swagger-method.delete { background: #ef4444; }
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
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.swagger-header').forEach(function(header) {
            header.addEventListener('click', function() {
                this.closest('.swagger-card').classList.toggle('is-open');
            });
        });
        
        document.querySelectorAll('.swagger-url-box .copy-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var box = this.closest('.swagger-url-box');
                var text = box.querySelector('code').innerText.trim();
                navigator.clipboard.writeText(text).then(() => {
                    var icon = this.querySelector('i');
                    icon.className = 'fas fa-check text-success';
                    setTimeout(() => { icon.className = 'far fa-copy'; }, 2000);
                });
            });
        });
    });
</script><div class="swagger-wrapper"><h3 class="swagger-section-title"><?php _e("Instance Api") ?></h3>

</div>
</div>
</div>
