<div class="row">
    <div class="col-12 mb-4">
        <div class="card bg-gradient-primary border-0 shadow-lg">
            <div class="card-body p-4 text-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="font-weight-bold mb-2">Biblioteca de modelos</h2>
                        <p class="mb-0 opacity-80">Encontre modelos prontos para lançar automações de WhatsApp com mais rapidez.</p>
                    </div>
                    <div class="d-none d-md-block">
                        <i class="fad fa-store fa-4x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php if(!empty($templates)): ?>
        <?php foreach($templates as $tpl): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100 shadow-sm hover-shadow transition-3d-hover border-radius-xl">
                    <div class="position-relative">
                        <div style="height: 160px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 12px 12px 0 0;">
                            <i class="fad fa-robot fa-3x text-primary opacity-50"></i>
                        </div>
                        <?php if($tpl->is_premium): ?>
                            <span class="badge bg-warning position-absolute top-0 end-0 m-3 shadow-sm">PREMIUM</span>
                        <?php else: ?>
                            <span class="badge bg-success position-absolute top-0 end-0 m-3 shadow-sm">GRÁTIS</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                             <span class="badge bg-light text-dark mb-2"><?php echo $tpl->category ?></span>
                        </div>
                        <h5 class="card-title font-weight-bold"><?php echo esc($tpl->name) ?></h5>
                        <p class="card-text text-muted small mb-4" style="min-height: 40px;">
                            <?php echo esc($tpl->description) ?>
                        </p>
                        
                        <div class="d-grid gap-2">
                            <a href="<?php echo base_url('bot-builder/install_template/'.$tpl->id) ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fad fa-download me-2"></i> Usar modelo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <i class="fad fa-box-open fa-4x text-muted mb-3"></i>
            <h4>Nenhum modelo encontrado</h4>
            <p>Volte depois para conferir novos modelos.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.hover-shadow:hover { 
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
}
.transition-3d-hover {
    transition: all 0.2s ease-in-out;
}
.border-radius-xl {
    border-radius: 1rem;
}
</style>
