<div class="row justify-content-center mt-5">
    <div class="col-lg-8">
        <div class="text-center mb-5">
            <h2 class="fw-bolder">Criar novo bot</h2>
            <p class="text-muted">Escolha como deseja começar sua automação de WhatsApp</p>
        </div>
        
        <div class="row g-4">
            <!-- Option 1: Start from Scratch -->
            <div class="col-md-4">
                <form action="<?php echo base_url('bot-builder/templates/start-scratch') ?>" method="post">
                    <input type="hidden" name="<?php echo $config['csrf_token_name'] ?>" value="<?php echo $config['csrf_hash'] ?>">
                    <button type="submit" class="card h-100 w-100 text-start border shadow-sm hover-shadow transition-3d-hover btn p-0 text-reset">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="symbol symbol-50px me-3">
                                    <span class="symbol-label bg-light-primary text-primary">
                                        <i class="fad fa-plus fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                            <h4 class="card-title fw-bold">Começar do zero</h4>
                            <p class="card-text text-muted small">Abra um canvas vazio e construa o fluxo passo a passo.</p>
                        </div>
                    </button>
                </form>
            </div>

            <!-- Option 2: Start from Template -->
            <div class="col-md-4">
                <a href="<?php echo base_url('bot-builder/templates/library') ?>" class="card h-100 border shadow-sm hover-shadow transition-3d-hover text-decoration-none text-reset">
                    <div class="card-body p-4">
                         <div class="d-flex align-items-center mb-3">
                            <div class="symbol symbol-50px me-3">
                                <span class="symbol-label bg-light-success text-success">
                                    <i class="fad fa-th-large fa-2x"></i>
                                </span>
                            </div>
                        </div>
                        <h4 class="card-title fw-bold">Começar por modelo</h4>
                        <p class="card-text text-muted small">Use modelos prontos para acelerar a criação do bot.</p>
                    </div>
                </a>
            </div>

            <!-- Option 3: Import -->
            <div class="col-md-4">
                <div class="card h-100 border shadow-sm hover-shadow transition-3d-hover cursor-pointer" onclick="document.getElementById('importFile').click()">
                    <div class="card-body p-4">
                         <div class="d-flex align-items-center mb-3">
                            <div class="symbol symbol-50px me-3">
                                <span class="symbol-label bg-light-info text-info">
                                    <i class="fad fa-file-import fa-2x"></i>
                                </span>
                            </div>
                        </div>
                        <h4 class="card-title fw-bold">Importar arquivo</h4>
                        <p class="card-text text-muted small">Envie um arquivo .json exportado de outra instância do <?php _ec(get_option("brand_name", "DelyntroBot")) ?>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Import Form -->
<form id="importForm" action="<?php echo base_url('bot-builder/templates/import') ?>" method="post" enctype="multipart/form-data" style="display:none">
    <input type="hidden" name="<?php echo $config['csrf_token_name'] ?>" value="<?php echo $config['csrf_hash'] ?>">
    <input type="file" id="importFile" name="file" accept=".json" onchange="document.getElementById('importForm').submit()">
</form>

<style>
.hover-shadow:hover { 
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
    border-color: var(--bs-primary) !important;
}
.transition-3d-hover {
    transition: all 0.2s ease-in-out;
}
.symbol-label {
    width: 50px; height: 50px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 10px;
}
.bg-light-primary { background: #eff6ff; }
.bg-light-success { background: #f0fdf4; }
.bg-light-info { background: #f0f9ff; }
</style>
