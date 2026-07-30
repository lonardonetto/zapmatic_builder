<?php
$content = file_get_contents('inc/core/Whatsapp_api/Views/content.php');

$style_script = <<<'STYLE'
<style>
    /* Styling adjustments for Swagger-like UI */
    .swagger-card { border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); background: #fff; }
    .swagger-header { padding: 12px 16px; display: flex; align-items: center; background: #f8fafc; cursor: pointer; transition: all 0.2s; border-bottom: 1px solid transparent; }
    .swagger-card.is-open .swagger-header { border-bottom-color: #e2e8f0; background: #f1f5f9; }
    .swagger-header:hover { background: #f1f5f9; }
    .swagger-method { font-weight: 700; font-size: 13px; padding: 4px 10px; border-radius: 4px; margin-right: 15px; color: #fff; min-width: 65px; text-align: center; text-transform: uppercase; }
    .swagger-method.post { background: #10b981; }
    .swagger-method.get { background: #3b82f6; }
    .swagger-method.put { background: #f59e0b; }
    .swagger-method.delete { background: #ef4444; }
    .swagger-path { font-family: monospace; font-size: 14px; color: #334155; font-weight: 600; flex-grow: 1; }
    .swagger-title { font-size: 14px; color: #64748b; margin-left: 15px; }
    
    .swagger-body { padding: 0; display: none; }
    .swagger-card.is-open .swagger-body { display: block; }
    
    .swagger-section-title { font-size: 18px; font-weight: 700; color: #1e293b; margin: 40px 0 20px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }
    
    .swagger-info-block { padding: 20px; border-bottom: 1px solid #e2e8f0; }
    .swagger-desc { color: #475569; font-size: 14px; margin-bottom: 15px; }
    
    .swagger-url-box { background: #1e293b; color: #e2e8f0; border-radius: 6px; padding: 12px 15px; font-family: monospace; word-break: break-all; border: none; font-size: 13px; position: relative; margin-top: 5px; }
    .swagger-url-box .copy-btn { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.1); border: none; color: #fff; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 12px; transition: background 0.2s; }
    .swagger-url-box .copy-btn:hover { background: rgba(255,255,255,0.25); }
    
    .swagger-params-block { padding: 20px; background: #fcfcfc; }
    .swagger-params-title { font-size: 14px; font-weight: 600; color: #334155; text-transform: uppercase; margin-bottom: 15px; }
    
    .swagger-table { width: 100%; border-collapse: collapse; }
    .swagger-table th { text-align: left; padding: 10px 15px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600; font-size: 13px; background: #f8fafc; }
    .swagger-table td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; font-size: 14px; }
    .swagger-table tr:last-child td { border-bottom: none; }
    .swagger-table td.param-name { font-weight: 600; color: #0f172a; font-family: monospace; width: 30%; }
    
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
</script>
STYLE;

// Split logic
$parts = explode('<h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase"><?php _e("Instance Api") ?></h5>', $content);
$head = $parts[0] . $style_script . '<div class="swagger-wrapper"><h3 class="swagger-section-title"><?php _e("Instance Api") ?></h3>';
$tail = $parts[1];

// Substitui os títulos de seção
$tail = str_replace('<h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase">', '<h3 class="swagger-section-title">', $tail);
$tail = str_replace('</h5>', '</h3>', $tail);

// Função para processar blocos simples (sem separação URL / cURL)
function processSimpleBlocks($text) {
    $pattern = '/<h6[^>]*><span[^>]*>(.*?)<\/span>\s*(.*?)<\/h6>\s*<div class="alert alert-dark[^>]*>\s*<code[^>]*>\s*(.*?)\s*<\/code>\s*<\/div>\s*<div class="text">(.*?)<\/div>\s*<div class="text-uppercase[^>]*>.*?<\/div>\s*<table class="table[^>]*>(.*?)<\/table>/s';
    
    return preg_replace_callback($pattern, function($m) {
        $method = trim(strip_tags($m[1]));
        $title = trim(strip_tags($m[2]));
        $url = trim($m[3]);
        $desc = trim($m[4]);
        $tbody = trim($m[5]);
        
        $methodClass = strtolower($method);
        $path = '';
        if (preg_match('/base_url\("api\/([^"?]+)/', $url, $pathMatch)) {
            $path = '/api/' . $pathMatch[1];
        } else {
            $path = '/api/...';
        }
        
        return '
        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method '.$methodClass.'">'.$method.'</div>
                <div class="swagger-path">'.$path.'</div>
                <div class="swagger-title">'.$title.'</div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="swagger-desc">'.$desc.'</div>
                    <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Endpoint URL</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                        <code>'.$url.'</code>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        '.$tbody.'
                    </table>
                </div>
            </div>
        </div>';
    }, $text);
}

// Função para processar blocos compostos (com Resource URL e cURL)
function processComplexBlocks($text) {
    $pattern = '/<h6[^>]*><span[^>]*>(.*?)<\/span>\s*(.*?)<\/h6>\s*<label>.*?<\/label>\s*<div class="alert alert-dark[^>]*>\s*<code[^>]*>\s*(.*?)\s*<\/code>\s*<\/div>\s*<label>.*?<\/label>\s*<div class="alert alert-dark[^>]*>\s*<code[^>]*>\s*(.*?)\s*<\/code>\s*<\/div>\s*<div class="text-uppercase[^>]*>.*?<\/div>\s*<table class="table[^>]*>(.*?)<\/table>/s';
    
    return preg_replace_callback($pattern, function($m) {
        $method = trim(strip_tags($m[1]));
        $title = trim(strip_tags($m[2]));
        $url1 = trim($m[3]);
        $url2 = trim($m[4]);
        $tbody = trim($m[5]);
        
        $methodClass = strtolower($method);
        $path = '';
        if (preg_match('/base_url\("api\/([^"?]+)/', $url1, $pathMatch)) {
            $path = '/api/' . $pathMatch[1];
        } else {
            $path = '/api/...';
        }
        
        return '
        <div class="swagger-card">
            <div class="swagger-header">
                <div class="swagger-method '.$methodClass.'">'.$method.'</div>
                <div class="swagger-path">'.$path.'</div>
                <div class="swagger-title">'.$title.'</div>
                <div class="ms-auto"><i class="fas fa-chevron-down toggle-icon"></i></div>
            </div>
            <div class="swagger-body">
                <div class="swagger-info-block">
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Resource URL</label>
                            <div class="swagger-url-box">
                                <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                                <code>'.$url1.'</code>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold fs-12 text-uppercase text-muted mb-2 d-block">Exemplo cURL</label>
                            <div class="swagger-url-box" style="background:#282a36;">
                                <button class="copy-btn" title="Copiar"><i class="far fa-copy"></i></button>
                                <code>'.$url2.'</code>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swagger-params-block">
                    <div class="swagger-params-title">Parâmetros</div>
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Nome do Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        '.$tbody.'
                    </table>
                </div>
            </div>
        </div>';
    }, $text);
}

// Add fw-6 to param names
function fixTableClasses($text) {
    return preg_replace('/<tr>\s*<td>(.*?)<\/td>/', '<tr><td class="param-name">$1</td>', $text);
}

$tail = processSimpleBlocks($tail);
$tail = processComplexBlocks($tail);
$tail = fixTableClasses($tail);

$new_content = $head . $tail . '</div>';

file_put_contents('inc/core/Whatsapp_api/Views/content.php', $new_content);
echo "Styling completed!\n";
