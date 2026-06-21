<form class="" action="<?php _ec( get_module_url("delete") )?>">
<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
    <div class="bd-search position-relative me-auto">
        <h2 class="mb-0 py-4"> <i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e("Assign proxy")?></h2>
    </div>
    <div class="">
        <div class="me-2">
            <div class="input-group input-group-sm sp-input-group border b-r-4">
                <span class="input-group-text border-0 fs-20 bg-gray-100 text-gray-800" id="sub-menu-search"><i class="fad fa-search"></i></span>
                <input type="text" class="ajax-pages-search ajax-filter form-control form-control-solid ps-15 border-0" name="keyword" value="" placeholder="<?php _e("Search")?>" autocomplete="off">
                
                <!-- Seleção de Proxy Visível -->
                <select class="form-select form-select-sm border-start" data-control="select2" name="proxy" style="min-width: 320px;">
                    <option value=""><?php _e("Select proxy")?></option>
                    <?php if (!empty($proxies)): ?>
                        <?php foreach ($proxies as $key => $value): ?>
                            <option value="<?php _ec($value->id)?>">
                                <?php _ec( "[".list_countries($value->location)."] ".$value->proxy )?>
                                <?php if((int)$value->team_id === (int)get_team('id')): ?> (meu time) <?php else: ?> (global) <?php endif; ?>
                            </option>
                        <?php endforeach ?>
                    <?php endif ?>
                </select>

                <!-- Botão de Atribuir Visível -->
                <a href="<?php _ec( get_module_url("do_assign") )?>" class="btn btn-light btn-active-light-success actionMultiItem" title="<?php _e("Assign proxy")?>" data-toggle="tooltip" data-placement="top" data-redirect="<?php _ec( get_module_url("index/assign") )?>">
                    <i class="fad fa-user-plus text-success"></i> <?php _e("Assign")?>
                </a>

                <!-- Botões de Ação Restantes -->
                <a href="<?php _e( get_module_url('remove_assign') )?>" class="btn btn-light btn-active-light-danger actionMultiItem" title="<?php _e("Remove assign")?>" data-toggle="tooltip" data-placement="top" data-confirm="<?php _e('Are you sure to remove assign this accounts?')?>" data-redirect="<?php _ec( get_module_url("index/assign") )?>" >
                    <i class="fad fa-user-times text-danger"></i>
	            </a>
            	<a href="<?php _ec( get_module_url() )?>" class="btn btn-light btn-active-light-dark" title="<?php _e("Back")?>" data-toggle="tooltip" data-placement="top" >
                    <i class="fad fa-chevron-left text-dark"></i>
                </a>
            </div>
        </div>
    </div>
</div>
	
<div class="container my-3">
    <div class="card card-flush">
        <div class="card-body p-0">

            <?php if ( isset($datatable) ): ?>
            <div class="alert alert-info m-4" role="alert" style="display:flex;align-items:center;gap:12px;">
                <div>
                    <b>Verificar localização via API:</b>
                    <span id="probe-helper" class="text-muted">Selecione uma conta e clique em \"Checar localização\" para ver o resultado sem sair da página.</span>
                </div>
                <div class="ms-auto" style="display:flex;gap:8px;">
                    <button type="button" id="btn-probe-open" class="btn btn-sm btn-primary">Checar localização</button>
                    <button type="button" id="btn-open-config" class="btn btn-sm btn-secondary">Ir para configuração de proxies</button>
                </div>
            </div>

                <div class="<?php _e( get_data($datatable, "responsive")? "table-responsive":"" )?>">

                    <?php if ( is_array( get_data($datatable, "columns") ) ): ?>

                        <table 
                            class="ajax-pages table table align-middle table-row-dashed fs-13 gy-5" 
                            data-url="<?php _ec( get_module_url("ajax_list_assigned") )?>" 
                            data-response=".ajax-result" 
                            data-per-page="<?php _ec( get_data($datatable, "per_page") )?>"
                            data-current-page="<?php _ec( get_data($datatable, "current_page") )?>"
                            data-total-items="<?php _ec( get_data($datatable, "total_items") )?>"
                        >
                            <thead>
                                <tr class="text-start text-muted fw-bolder text-uppercase gs-0">
                                    <th scope="col" class="w-20 border-bottom py-4 ps-4">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                            <input class="form-check-input checkbox-all" type="checkbox">
                                        </div>
                                    </th>
                                    <th scope="col" class="border-bottom fw-4 fs-12 text-nowrap px-3 py-4"><?php _e("Account info")?></th>
                                    <th scope="col" class="border-bottom fw-4 fs-12 text-nowrap px-3 py-4"><?php _e("Proxy assigned")?></th>
                                    <th scope="col" class="border-bottom fw-4 fs-12 text-nowrap px-3 py-4"><?php _e("Proxy location")?></th>
                                </tr>
                            </thead>
                            <tbody class="ajax-result"></tbody>
                        </table>

                    <?php endif ?>

                </div>
                
            <?php endif ?>

            <?php if (get_data($datatable, "total_items") != 0): ?>
            <nav class="m-t-50 ajax-pagination m-auto text-center mb-4"> </nav>
            <?php endif ?>

        </div>
    </div>
</div>
</form>

<!-- Modal de resultado da localização -->
<div class="modal fade" id="modalProbe" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Localização detectada</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <pre id="probe-json" class="bg-light p-3" style="white-space:pre-wrap;word-break:break-word;min-height:120px;">Carregando...</pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
  </div>

<script type="text/javascript">
    $(function(){
        Core.ajax_pages();
    });

    function getCheckedIds(){
        const ids=[];
        // Coleta apenas checkboxes de linha (evita pegar o checkbox-all sem valor)
        $('.ajax-result .checkbox-item:checked').each(function(){
            const v = $(this).val();
            if(v){ ids.push(v); }
        });
        return ids;
    }

    $('#btn-probe-open').on('click', function(){
        const ids = getCheckedIds();
        if(ids.length===0){ alert('Selecione uma conta.'); return; }
        const selected = ids[0];
        const url = '<?php _ec( get_module_url("probe_location_ajax") )?>?id=' + encodeURIComponent(selected);
        const $modal = new bootstrap.Modal(document.getElementById('modalProbe'));
        $('#probe-json').text('Carregando...');
        $modal.show();
        fetch(url)
          .then(r=>r.json())
          .then(j=>{ $('#probe-json').text(JSON.stringify(j,null,2)); })
          .catch(()=>{ $('#probe-json').text('Erro ao consultar localização.'); });
    });

    $('#btn-open-config').on('click', function(){
        window.location.href = '<?php _ec( get_module_url("index") )?>';
    });
</script>