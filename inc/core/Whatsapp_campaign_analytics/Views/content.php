<style>
    .ajax-result td span { font-size: inherit !important; }
    .table thead th { font-size: 16px !important; font-weight: 900 !important; color: #111 !important; text-transform: uppercase !important; }
    .campaign-title-forced { font-size: 24px !important; font-weight: 900 !important; color: #181c32 !important; text-transform: capitalize !important; display: block !important; }
    .counter-number-forced { font-size: 32px !important; font-weight: 900 !important; line-height: 1 !important; }
</style>

<div class="row">
    <div class="col-12 py-4">
        <h3 class="mb-4">Relatórios de Campanhas <span class="text-success"><i class="fad fa-chart-line"></i> Cloud API</span></h3>
        <p class="text-muted">Aqui você vê o histórico detalhado de todas as campanhas baseadas nos status recebidos pela Meta API. Campanhas excluídas ficam gravadas nesta tabela, protegendo os dados históricos já enviados.</p>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="ajax-pages" data-url="<?php echo base_url('whatsapp_campaign_analytics/ajax_list'); ?>" data-response=".ajax-result" data-per-page="<?php echo $datatable['per_page']; ?>" data-current-page="<?php echo $datatable['current_page']; ?>" data-total-items="<?php echo $total; ?>">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-row-dashed gy-3">
                            <thead>
                                <tr class="text-start text-gray-800 fw-boldest text-uppercase gs-0 border-bottom border-gray-300" style="background: #f8f9fa;">
                                    <th class="min-w-250px py-2 px-3" style="font-size: 16px !important; font-weight: 800 !important; color: #181c32 !important;">Campanha</th>
                                    <th class="min-w-120px py-2" style="font-size: 16px !important; font-weight: 800 !important; color: #181c32 !important;">Início</th>
                                    <th class="text-center py-2" style="font-size: 16px !important; font-weight: 800 !important; color: #181c32 !important;">Total</th>
                                    <th class="text-center py-2" style="font-size: 16px !important; font-weight: 800 !important; color: #181c32 !important;">Enviados</th>
                                    <th class="text-center py-2" style="font-size: 16px !important; font-weight: 800 !important; color: #181c32 !important;">Entregues</th>
                                    <th class="text-center py-2" style="font-size: 16px !important; font-weight: 800 !important; color: #181c32 !important;">Lidos</th>
                                    <th class="text-center py-2" style="font-size: 16px !important; font-weight: 800 !important; color: #181c32 !important;">Falhas</th>
                                    <th class="text-end pe-3 py-2" style="font-size: 16px !important; font-weight: 800 !important; color: #181c32 !important;">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="ajax-result"></tbody>
                        </table>
                    </div>
                </div>

                <script type="text/javascript">
                    $(function() {
                        Core.ajax_pages();
                    });
                </script>
            </div>
        </div>
        <div class="mt-4">
            <nav class="m-t-50 m-b-50 ajax-pagination m-auto text-center"></nav>
        </div>
    </div>
</div>
