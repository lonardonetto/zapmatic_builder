<div class="container-fluid">
    <div class="row py-4 align-items-center">
        <div class="col-md-8">
            <a href="<?php echo base_url('whatsapp_campaign_analytics'); ?>" class="btn btn-sm btn-light text-muted mb-2"><i class="fad fa-arrow-left"></i> Voltar</a>
            <h3 class="mb-0">Detalhes: <span class="text-primary"><?php echo _e($campaign_name); ?></span></h3>
            <p class="text-muted mb-0">Exibindo o status de cada disparo realizado via API Meta</p>
        </div>
        <div class="col-md-4 text-md-end text-right">
            <a href="<?php echo base_url('whatsapp_campaign_analytics/export/' . $schedule_id); ?>" class="btn btn-success">
                <i class="fad fa-file-excel"></i> Exportar Excel Completo
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-bottom-0">Telefone</th>
                                    <th class="border-bottom-0">Message ID (Meta)</th>
                                    <th class="border-bottom-0 text-center">Status Final</th>
                                    <th class="border-bottom-0">Última Atualização</th>
                                    <th class="border-bottom-0">Motivo Falha (se erro)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($results)): ?>
                                    <?php foreach ($results as $r): ?>
                                        <tr>
                                            <td><strong>+<?php echo $r->to_number; ?></strong></td>
                                            <td><small class="text-muted"><?php echo substr($r->wa_message_id, 0, 4) . '...' . substr($r->wa_message_id, -4); ?></small></td>
                                            <td class="text-center">
                                                <?php 
                                                    if ($r->status == 'read') echo '<span class="badge badge-success text-uppercase">Lido</span>';
                                                    else if ($r->status == 'delivered') echo '<span class="badge badge-info text-uppercase">Entregue</span>';
                                                    else if ($r->status == 'sent') echo '<span class="badge badge-primary text-uppercase">Enviado</span>';
                                                    else if ($r->status == 'failed') echo '<span class="badge badge-danger text-uppercase">Falha</span>';
                                                    else echo('<span class="badge badge-secondary text-uppercase">' . $r->status . '</span>');
                                                ?>
                                            </td>
                                            <td><?php echo datetime_show($r->last_status_at); ?></td>
                                            <td>
                                                <?php if($r->meta_error_code): ?>
                                                    <span class="text-danger">
                                                        [<?php echo $r->meta_error_code; ?>] <?php echo _e($r->meta_error_title); ?>
                                                        <?php if($r->meta_error_details): ?>
                                                            <br><small><?php echo _e($r->meta_error_details); ?></small>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center p-5 text-muted">Nenhum detalhe disponível para esta campanha.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
