<div class="container my-5 mw-800">
    <div class="mb-5 d-flex align-items-center">
        <a href="<?php _ec( get_module_url() ) ?>" class="btn btn-light btn-sm me-3"><i class="fas fa-arrow-left"></i> <?php _e("Voltar") ?></a>
        <h2 class="mb-0"> <i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _e("Configurar Mineração") ?></h2>
    </div>

    <form class="actionForm" action="<?php _ec( get_module_url("save") ) ?>" method="POST" data-redirect="<?php _ec( get_module_url() ) ?>">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                
                <input type="hidden" name="ids" value="<?php _ec( $job->ids ?? '' ) ?>">

                <div class="mb-4">
                    <label class="form-label fw-bold"><?php _e("Nome da Campanha (Opcional)") ?></label>
                    <input type="text" class="form-control" name="name" value="<?php _ec( $job->name ?? '' ) ?>" placeholder="Ex: Dentistas Zona Sul RJ">
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e("Palavra-Chave") ?></label>
                        <input type="text" class="form-control" name="keyword" value="<?php _ec( $job->keyword ?? '' ) ?>" placeholder="Ex: Pizzaria, Pet Shop, Advogado..." required>
                        <div class="form-text">O que você quer procurar no Google Maps.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e("Localização") ?></label>
                        <input type="text" class="form-control" name="location" value="<?php _ec( $job->location ?? '' ) ?>" placeholder="Ex: São Paulo, Centro RJ..." required>
                        <div class="form-text">A região onde será feita a busca.</div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e("Lista de Contatos de Destino (Phonebook)") ?></label>
                        <select name="target_phonebook" class="form-select" required>
                            <option value=""><?php _e("Selecione uma lista") ?></option>
                            <?php if(!empty($phonebooks)): ?>
                                <?php foreach($phonebooks as $pb): ?>
                                    <option value="<?php _ec($pb->ids) ?>" <?php echo (isset($job->target_phonebook) && $job->target_phonebook == $pb->ids) ? 'selected' : ''; ?>><?php _ec($pb->name) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">Os números serão injetados aqui.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e("DDI (Código do País)") ?></label>
                        <input type="text" class="form-control" name="ddi" value="<?php _ec( $job->ddi ?? '55' ) ?>" required>
                        <div class="form-text">Será fixado antes do número (Ex: 55 para o Brasil).</div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e("Limite de Contatos") ?></label>
                        <input type="number" class="form-control" name="limit_leads" value="<?php _ec( $job->limit_leads ?? 100 ) ?>" min="1" max="1000" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php _e("Delay de Segurança (Segundos)") ?></label>
                        <input type="number" class="form-control" name="delay_seconds" value="<?php _ec( $job->delay_seconds ?? 30 ) ?>" min="15" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold"><?php _e("Rotação de Proxies (Opcional)") ?></label>
                    <textarea class="form-control" name="proxy" rows="3" placeholder="http://user:pass@host:port&#10;http://user:pass@host2:port"><?php _ec( $job->proxy ?? '' ) ?></textarea>
                    <div class="form-text">Insira um proxy por linha. O sistema irá rotacionar os IPs se o Google bloquear algum. Deixe em branco para usar IP local.</div>
                </div>

                <?php if(isset($job->status) && $job->status == 3 && !empty($job->error_msg)): ?>
                    <div class="alert alert-danger">
                        <strong>Erro Anterior:</strong> <?php _ec($job->error_msg) ?>
                    </div>
                <?php endif; ?>

            </div>
            <div class="card-footer bg-light text-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php _e("Salvar Tarefa") ?></button>
            </div>
        </div>
    </form>
</div>
