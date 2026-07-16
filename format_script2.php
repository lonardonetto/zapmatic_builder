<?php
$content = file_get_contents('inc/core/Whatsapp_api/Views/content.php');

$parts = explode('<h3 class="swagger-section-title"><?php _e("Send Direct Message Api") ?></h3>', $content);
$head = $parts[0];
$tail = $parts[1];

function convertToSwagger($html) {
    // Regex for:
    // <h6...><span...>METHOD</span>TITLE</h6>
    // <label>Resource URL:</label>
    // <div class="alert..."><code...>URL1</code></div>
    // <label>CURL:</label>
    // <div class="alert..."><code...>URL2</code></div>
    // <div class="text-uppercase...">Params</div>
    // <table...>...</table>
    
    $pattern = '/<h6[^>]*><span[^>]*>(.*?)<\/span>\s*(.*?)<\/h6>\s*<label[^>]*>.*?<\/label>\s*<div class="alert alert-dark[^>]*>\s*<code[^>]*>\s*(.*?)\s*<\/code>\s*<\/div>\s*<label[^>]*>.*?<\/label>\s*<div class="alert alert-dark[^>]*>\s*<code[^>]*>\s*(.*?)\s*<\/code>\s*<\/div>\s*<div class="text-uppercase[^>]*>.*?<\/div>\s*<table class="table[^>]*>(.*?)<\/table>/s';
    
    return preg_replace_callback($pattern, function($matches) {
        $method = trim($matches[1]);
        $title = trim($matches[2]);
        $url1 = trim($matches[3]);
        $url2 = trim($matches[4]);
        $tbody = trim($matches[5]);
        
        $methodClass = strtolower($method);
        
        $path = '';
        if (preg_match('/base_url\("api\/([^"?]+)/', $url1, $m)) {
            $path = '/api/' . $m[1];
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
                <div class="swagger-desc"></div>
                <div class="mb-3">
                    <label class="fw-bold mb-2">Resource URL:</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn"><i class="far fa-copy"></i></button>
                        <code>'.$url1.'</code>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="fw-bold mb-2">cURL Exemplo:</label>
                    <div class="swagger-url-box">
                        <button class="copy-btn"><i class="far fa-copy"></i></button>
                        <code>'.$url2.'</code>
                    </div>
                </div>
                <div class="swagger-table-wrapper">
                    <table class="swagger-table">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Valor / Exemplo</th>
                            </tr>
                        </thead>
                        '.$tbody.'
                    </table>
                </div>
            </div>
        </div>';
    }, $html);
}

$tail = convertToSwagger($tail);

$new_content = $head . '<h3 class="swagger-section-title"><?php _e("Send Direct Message Api") ?></h3>' . $tail;

file_put_contents('inc/core/Whatsapp_api/Views/content.php', $new_content);
echo "Formatting applied.\n";
