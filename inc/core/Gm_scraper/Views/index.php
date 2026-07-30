<div class="container my-5">
    <div class="row">
        <div class="col-md-8">
            <h2> <i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _e($config['name']) ?></h2>
            <p><?php _e($config['desc']) ?></p>
        </div>
        <div class="col-md-4 text-end">
            <a class="btn btn-primary" href="<?php _ec( get_module_url('update') ) ?>"><i class="fas fa-plus"></i> <?php _e("Nova Mineração") ?></a>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><?php _e("Palavra-Chave / Local") ?></th>
                            <th><?php _e("Destino (Lista)") ?></th>
                            <th><?php _e("Limite / Delay") ?></th>
                            <th><?php _e("Status") ?></th>
                            <th><?php _e("Progresso") ?></th>
                            <th class="text-end"><?php _e("Ações") ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($jobs)): ?>
                            <?php foreach($jobs as $job): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary"><?php _ec($job->name ?: ($job->keyword . ' - ' . $job->location)) ?></div>
                                        <div class="fw-bold"><?php _ec($job->keyword) ?></div>
                                        <div class="text-muted small"><i class="fas fa-map-marker-alt"></i> <?php _ec($job->location) ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                            $pb = db_get("name", "sp_whatsapp_contacts", ["ids" => $job->target_phonebook]);
                                            if($pb) echo "<span class='badge bg-secondary'>{$pb->name}</span>";
                                            else echo "-";
                                        ?>
                                        <div class="text-muted small mt-1">DDI: +<?php _ec($job->ddi ?: '55') ?></div>
                                    </td>
                                    <td>
                                        <div class="small">Max: <?php _ec($job->limit_leads) ?> leads</div>
                                        <div class="small text-muted"><i class="fas fa-clock"></i> <?php _ec($job->delay_seconds) ?>s delay</div>
                                    </td>
                                    <td>
                                        <?php 
                                        switch($job->status){
                                            case 0: echo '<span class="badge bg-warning">Pendente</span>'; break;
                                            case 1: echo '<span class="badge bg-primary">Extraindo...</span>'; break;
                                            case 2: echo '<span class="badge bg-success">Concluído</span>'; break;
                                            case 3: echo '<span class="badge bg-danger">Erro</span>'; break;
                                            case 4: echo '<span class="badge bg-secondary">Pausado</span>'; break;
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-success"><?php _ec($job->current_count) ?> / <?php _ec($job->limit_leads) ?></div>
                                    </td>
                                    <td class="text-end">
                                        <?php if($job->status == 1 || $job->status == 0): ?>
                                            <a href="<?php _ec( get_module_url('status_action/pause/'.$job->ids) ) ?>" class="btn btn-sm btn-light-warning btn-icon actionItem" data-popup="false" title="Pausar"><i class="fas fa-pause"></i></a>
                                        <?php elseif($job->status == 4 || $job->status == 3): ?>
                                            <a href="<?php _ec( get_module_url('status_action/resume/'.$job->ids) ) ?>" class="btn btn-sm btn-light-success btn-icon actionItem" data-popup="false" title="Continuar"><i class="fas fa-play"></i></a>
                                        <?php endif; ?>
                                        <a href="<?php _ec( get_module_url('export_csv/'.$job->ids) ) ?>" class="btn btn-sm btn-light-info btn-icon" data-popup="false" title="Exportar CSV Completo" target="_blank"><i class="fas fa-file-csv"></i></a>
                                        <a href="<?php _ec( get_module_url('update/'.$job->ids) ) ?>" class="btn btn-sm btn-light-primary btn-icon" title="Editar"><i class="fas fa-edit"></i></a>
                                        <a href="<?php _ec( get_module_url('delete') ) ?>" data-id="<?php _ec($job->ids) ?>" data-redirect="<?php _ec( get_module_url() ) ?>" class="btn btn-sm btn-light-danger btn-icon actionItem" title="Excluir"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted mb-3"><i class="fad fa-map-marked-alt fa-3x"></i></div>
                                    <h5>Nenhuma mineração encontrada</h5>
                                    <p>Clique em "Nova Mineração" para começar a extrair contatos do Google Maps.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Motor de atualização em tempo real (Live Reload)
    $(document).ready(function() {
        if(window.gmScraperInterval) clearInterval(window.gmScraperInterval);
        
        window.gmScraperInterval = setInterval(function() {
            // Se o usuário mudou de tela (ou foi para a tela de Update) paramos o intervalo
            if($("table tbody").length === 0 || window.location.href.indexOf('gm_scraper') === -1) {
                clearInterval(window.gmScraperInterval);
                return;
            }

            $.ajax({
                url: "<?php _ec( get_module_url() ) ?>",
                type: "GET",
                dataType: "html",
                success: function(response) {
                    var newTbody = $(response).find("table tbody").html();
                    if(newTbody) {
                        $("table tbody").html(newTbody);
                    }
                }
            });
        }, 3000); // Atualiza de 3 em 3 segundos
    });
</script>
