<?php
$team_id = get_team("id");
$proxies = db_fetch("*", TB_PROXIES, ["status" => 1, "team_id" => $team_id], "id", "DESC");
?>

<div class="card mb-4">
    <div class="card-header">
        <div class="card-title">
            <span class="me-2"><i class="fad fa-user-shield text-primary"></i></span>
            <span><?php _e("Configurações de Proxy")?></span>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label"><?php _e("Selecionar Proxy")?></label>
            <select name="proxy_id" class="form-control">
                <option value=""><?php _e("Sem Proxy")?></option>
                <?php foreach($proxies as $proxy):?>
                <option value="<?php _e($proxy->id)?>" <?php _e( $proxy->id == $instance->proxy_id ? "selected" : "" )?>><?php _e($proxy->proxy . " (" . $proxy->location . ")")?></option>
                <?php endforeach?>
            </select>
            <div class="form-text"><?php _e("Selecione um proxy para alterar a localização deste perfil WhatsApp")?></div>
        </div>
    </div>
</div>
