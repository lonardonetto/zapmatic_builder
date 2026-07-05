<style>
.ai-provider-card{border:1px solid #eef2f7;border-radius:18px;padding:16px;background:linear-gradient(180deg,#fff,#fafbff);box-shadow:0 10px 30px rgba(15,23,42,.05);height:100%;transition:.18s ease}
.ai-provider-card:hover{transform:translateY(-1px);box-shadow:0 16px 38px rgba(15,23,42,.08);border-color:#e0e7ff}
.ai-provider-head{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.ai-provider-logo{width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:15px;box-shadow:inset 0 1px 0 rgba(255,255,255,.25),0 10px 22px rgba(15,23,42,.12)}
.ai-provider-logo i{font-size:18px;color:#fff}
.ai-provider-name{font-weight:800;color:#111827;line-height:1.1}
.ai-provider-desc{font-size:12px;color:#64748b;margin-top:3px}
.ai-provider-card .input-group .form-control{border-radius:12px 0 0 12px;background:#f8fafc;border-color:#e5e7eb}
.ai-provider-card .input-group .btn{border-radius:0 12px 12px 0}
</style>

<div class="container py-4">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:#ede9fe;color:#7c3aed;">
                    <i class="fad fa-brain fs-22"></i>
                </div>
                <div>
                    <h3 class="mb-1 fw-bold">Central de IA</h3>
                    <div class="text-muted">Configuração global usada pelo Flow Builder, futuro pipeline e multiatendimento.</div>
                </div>
            </div>
        </div>
        <form class="actionForm" action="<?php _ec(base_url('ai_manager/save')) ?>" method="POST">
            <div class="card-body pt-0">
                <div class="alert alert-primary border-0 rounded-3">
                    As chaves ficam globais por equipe. Os nodes de IA herdam essa configuração automaticamente.
                </div>

                <div class="row g-4">
                    <?php
                    $providers = [
                        'openrouter' => ['name' => 'OpenRouter', 'icon' => 'fas fa-route', 'logo' => 'OR', 'color' => '#0ea5e9', 'desc' => 'Gateway para centenas de modelos'],
                        'openai' => ['name' => 'OpenAI', 'icon' => 'fas fa-brain', 'logo' => 'AI', 'color' => '#10a37f', 'desc' => 'GPT e modelos OpenAI diretos'],
                        'anthropic' => ['name' => 'Anthropic', 'icon' => 'fas fa-feather-alt', 'logo' => 'A', 'color' => '#d97706', 'desc' => 'Claude e modelos Anthropic'],
                        'gemini' => ['name' => 'Gemini', 'icon' => 'fas fa-gem', 'logo' => 'G', 'color' => '#4285f4', 'desc' => 'Modelos Google Gemini'],
                        'mistral' => ['name' => 'Mistral', 'icon' => 'fas fa-wind', 'logo' => 'M', 'color' => '#f97316', 'desc' => 'Modelos Mistral AI'],
                        'groq' => ['name' => 'Groq', 'icon' => 'fas fa-microchip', 'logo' => 'GQ', 'color' => '#ef4444', 'desc' => 'Inferência rápida via Groq'],
                        'deepseek' => ['name' => 'DeepSeek', 'icon' => 'fas fa-atom', 'logo' => 'DS', 'color' => '#4f46e5', 'desc' => 'DeepSeek Chat/Reasoner'],
                        'perplexity' => ['name' => 'Perplexity', 'icon' => 'fas fa-search', 'logo' => 'P', 'color' => '#14b8a6', 'desc' => 'IA com busca e contexto web'],
                        'together' => ['name' => 'Together', 'icon' => 'fas fa-users', 'logo' => 'T', 'color' => '#8b5cf6', 'desc' => 'Modelos open-source via Together'],
                    ];
                    foreach ($providers as $key => $provider):
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="ai-provider-card">
                            <div class="ai-provider-head">
                                <div class="ai-provider-logo" style="background:<?php _ec($provider['color']) ?>;">
                                    <i class="<?php _ec($provider['icon']) ?>"></i>
                                </div>
                                <div>
                                    <div class="ai-provider-name"><?php _ec($provider['name']) ?></div>
                                    <div class="ai-provider-desc"><?php _ec($provider['desc']) ?></div>
                                </div>
                            </div>
                            <label class="form-label fw-semibold mb-2">API Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="<?php _ec($key) ?>_key" id="<?php _ec($key) ?>_key" value="<?php _ec($settings[$key . '_key'] ?? '') ?>" placeholder="Cole a chave da API">
                                <button type="button" class="btn btn-light border" onclick="testAiProvider('<?php _ec($key) ?>')">Testar</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Provider padrão</label>
                        <select class="form-select" name="default_provider">
                            <?php foreach ($providers as $key => $provider): ?>
                                <option value="<?php _ec($key) ?>" <?php _ec(($settings['default_provider'] ?? 'openrouter') === $key ? 'selected' : '') ?>><?php _ec($provider['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Modelo padrão</label>
                        <input class="form-control" name="default_model" value="<?php _ec($settings['default_model'] ?? 'openai/gpt-oss-20b:free') ?>" placeholder="openai/gpt-oss-20b:free">
                        <?php if (!empty($models)): ?>
                            <small class="text-muted">OpenRouter conectado: <?php _ec(count($models)) ?> modelos disponíveis.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0 py-4 d-flex justify-content-end">
                <button class="btn btn-primary"><i class="fad fa-save me-2"></i>Salvar Central de IA</button>
            </div>
        </form>
    </div>
</div>

<script>
function testAiProvider(provider) {
    var key = document.getElementById(provider + '_key').value;
    $.post('<?php _ec(base_url('ai_manager/test_connection')) ?>', {provider: provider, api_key: key}, function(res) {
        if (typeof res === 'string') {
            try { res = JSON.parse(res); } catch(e) {}
        }
        if (window.Main && Main.notify) {
            Main.notify(res.status || 'info', res.message || 'Teste finalizado');
        } else {
            alert(res.message || 'Teste finalizado');
        }
    });
}
</script>
