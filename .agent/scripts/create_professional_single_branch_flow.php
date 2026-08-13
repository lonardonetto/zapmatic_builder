<?php

declare(strict_types=1);

error_reporting(E_ALL);

date_default_timezone_set('America/Sao_Paulo');

const ACCOUNT_ID = 40;
const TEST_SEND_TO = '5521970402529';
const GRAPH_VERSION = 'v23.0';

function fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function info(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function loadEnv(string $path): array
{
    if (!is_file($path)) {
        fail('.env nao encontrado em ' . $path);
    }

    $data = parse_ini_file($path, false, INI_SCANNER_RAW);
    if (!is_array($data)) {
        fail('Nao foi possivel ler o .env');
    }

    return $data;
}

function db(): mysqli
{
    static $mysqli = null;
    if ($mysqli instanceof mysqli) {
        return $mysqli;
    }

    $env = loadEnv(__DIR__ . '/../../.env');
    $mysqli = @new mysqli(
        (string)($env['database.default.hostname'] ?? '127.0.0.1'),
        (string)($env['database.default.username'] ?? ''),
        (string)($env['database.default.password'] ?? ''),
        (string)($env['database.default.database'] ?? '')
    );

    if ($mysqli->connect_errno) {
        fail('Falha ao conectar no banco: ' . $mysqli->connect_error);
    }

    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function dbRow(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    if (!$stmt) {
        fail('Falha ao preparar SQL: ' . db()->error);
    }

    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        fail('Falha ao executar SQL: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function dbExec(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    if (!$stmt) {
        fail('Falha ao preparar SQL: ' . db()->error);
    }

    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        fail('Falha ao executar SQL: ' . $stmt->error);
    }

    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

function dbInsertId(): int
{
    return (int) db()->insert_id;
}

function ids(int $len = 13): string
{
    return substr(bin2hex(random_bytes(16)), 0, $len);
}

function slugify(string $value, bool $underscore = false): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', $underscore ? '_' : '-', $value) ?? '';
    $value = trim($value, $underscore ? '_' : '-');
    return $value !== '' ? $value : 'campo_' . random_int(100, 999);
}

function alphaSequence(int $index): string
{
    $sequence = '';
    $current = max(0, $index) + 1;

    while ($current > 0) {
        $current--;
        $sequence = chr(65 + ($current % 26)) . $sequence;
        $current = intdiv($current, 26);
    }

    return $sequence !== '' ? $sequence : 'A';
}

function toMetaIdentifier(string $value, string $fallback = 'SCREEN'): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $normalized = strtoupper($value);
    $normalized = preg_replace('/[^A-Z]+/', '_', $normalized) ?? '';
    $normalized = preg_replace('/_+/', '_', $normalized) ?? '';
    $normalized = trim($normalized, '_');

    $resolved = $normalized !== '' ? $normalized : $fallback;
    return substr($resolved, 0, 24);
}

function composeMetaIdentifier(array $parts, array $fallbackParts): string
{
    $primary = array_values(array_filter(array_map(static function ($part) {
        return toMetaIdentifier((string) $part, '');
    }, $parts)));

    $fallback = array_values(array_filter(array_map(static function ($part) {
        return toMetaIdentifier((string) $part, '');
    }, $fallbackParts)));

    return toMetaIdentifier(implode('_', $primary), implode('_', $fallback) ?: 'SCREEN');
}

function wrapTextToWidth(string $text, int $fontSize, string $font, int $maxWidth): array
{
    $words = preg_split('/\s+/', trim($text)) ?: [];
    $lines = [];
    $current = '';

    foreach ($words as $word) {
        $candidate = trim($current . ' ' . $word);
        $bbox = imagettfbbox($fontSize, 0, $font, $candidate);
        $width = abs((int)$bbox[2] - (int)$bbox[0]);

        if ($current !== '' && $width > $maxWidth) {
            $lines[] = $current;
            $current = $word;
        } else {
            $current = $candidate;
        }
    }

    if ($current !== '') {
        $lines[] = $current;
    }

    return $lines;
}

function colorAllocateAlphaHex(GdImage $img, string $hex, int $alpha = 0): int
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
        $hex = '000000';
    }
    return imagecolorallocatealpha(
        $img,
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
        $alpha
    );
}

function imageDataUrl(string $path): string
{
    return 'data:image/png;base64,' . base64_encode((string) file_get_contents($path));
}

function drawGradient(GdImage $img, string $startHex, string $endHex): void
{
    $width = imagesx($img);
    $height = imagesy($img);

    [$sr, $sg, $sb] = [hexdec(substr($startHex, 1, 2)), hexdec(substr($startHex, 3, 2)), hexdec(substr($startHex, 5, 2))];
    [$er, $eg, $eb] = [hexdec(substr($endHex, 1, 2)), hexdec(substr($endHex, 3, 2)), hexdec(substr($endHex, 5, 2))];

    for ($y = 0; $y < $height; $y++) {
        $ratio = $height > 1 ? $y / ($height - 1) : 0;
        $r = (int) round($sr + ($er - $sr) * $ratio);
        $g = (int) round($sg + ($eg - $sg) * $ratio);
        $b = (int) round($sb + ($eb - $sb) * $ratio);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $width, $y, $color);
    }
}

function drawFlowCard(string $path, array $palette, string $eyebrow, string $title, string $subtitle, array $bullets): void
{
    $width = 1200;
    $height = 675;
    $img = imagecreatetruecolor($width, $height);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    drawGradient($img, $palette['start'], $palette['end']);

    $overlay = colorAllocateAlphaHex($img, $palette['accent'], 90);
    imagefilledellipse($img, 990, 120, 360, 360, $overlay);
    imagefilledellipse($img, 1040, 540, 520, 520, colorAllocateAlphaHex($img, '#ffffff', 118));
    imagefilledrectangle($img, 660, 70, 1110, 605, colorAllocateAlphaHex($img, '#ffffff', 102));
    imagefilledrectangle($img, 720, 130, 1050, 520, colorAllocateAlphaHex($img, $palette['dark'], 60));

    $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
    $titleColor = colorAllocateAlphaHex($img, '#ffffff', 0);
    $softColor = colorAllocateAlphaHex($img, '#EAF2FF', 0);
    $chipBg = colorAllocateAlphaHex($img, '#ffffff', 104);
    $chipText = colorAllocateAlphaHex($img, '#0C1D3A', 0);

    imagefilledrectangle($img, 80, 78, 320, 128, $chipBg);
    imagettftext($img, 19, 0, 102, 112, $chipText, $font, strtoupper($eyebrow));

    $titleLines = wrapTextToWidth($title, 38, $font, 520);
    $y = 205;
    foreach ($titleLines as $line) {
        imagettftext($img, 38, 0, 84, $y, $titleColor, $font, $line);
        $y += 58;
    }

    $subtitleLines = wrapTextToWidth($subtitle, 21, $font, 540);
    $y += 10;
    foreach ($subtitleLines as $line) {
        imagettftext($img, 21, 0, 88, $y, $softColor, $font, $line);
        $y += 34;
    }

    $bulletY = 480;
    foreach ($bullets as $bullet) {
        imagefilledellipse($img, 98, $bulletY - 6, 12, 12, colorAllocateAlphaHex($img, '#ffffff', 0));
        imagettftext($img, 18, 0, 118, $bulletY, $softColor, $font, $bullet);
        $bulletY += 48;
    }

    imagettftext($img, 22, 0, 755, 190, $chipText, $font, 'FLOW');
    imagettftext($img, 28, 0, 755, 245, $titleColor, $font, 'Atendimento');
    imagettftext($img, 28, 0, 755, 286, $titleColor, $font, 'guiado');
    imagettftext($img, 18, 0, 755, 360, $softColor, $font, 'Capta dados, organiza a solicitacao');
    imagettftext($img, 18, 0, 755, 392, $softColor, $font, 'e reduz retrabalho no atendimento.');

    imagepng($img, $path, 8);
    imagedestroy($img);
}

function buildChoiceSource(array $options): array
{
    $source = [];
    $index = 1;
    foreach ($options as $option) {
        $option = trim((string) $option);
        if ($option === '') {
            continue;
        }
        $source[] = [
            'id' => (string) $index,
            'title' => $option,
        ];
        $index++;
    }
    return $source;
}

function navigationListItem(array $item, string $nextScreenId, string $fallbackId): array
{
    $listItem = [
        'id' => toMetaIdentifier((string) ($item['id'] ?? $item['title'] ?? ''), $fallbackId),
        'main-content' => [
            'title' => (string) ($item['title'] ?? 'Item'),
        ],
        'on-click-action' => [
            'name' => 'navigate',
            'next' => [
                'name' => $nextScreenId,
                'type' => 'screen',
            ],
            'payload' => new stdClass(),
        ],
    ];

    if (!empty($item['description'])) {
        $listItem['main-content']['description'] = trim((string) $item['description']);
    }

    return $listItem;
}

function buildFinalScreen(string $formScreenId, array $menuItem, array $subitem, array $finalFormState, string $flowName, string $flowSlug): array
{
    $layoutChildren = [];

    $heading = str_replace(['{{categoria}}', '{{opcao}}'], [(string) $menuItem['title'], (string) $subitem['title']], (string) ($finalFormState['heading'] ?? ''));
    $caption = str_replace(['{{categoria}}', '{{opcao}}'], [(string) $menuItem['title'], (string) $subitem['title']], (string) ($finalFormState['caption'] ?? ''));
    $note = str_replace(['{{categoria}}', '{{opcao}}'], [(string) $menuItem['title'], (string) $subitem['title']], (string) ($finalFormState['body_text'] ?? ''));

    if (trim($heading) !== '') {
        $layoutChildren[] = ['type' => 'TextHeading', 'text' => trim($heading)];
    }

    if (trim($caption) !== '') {
        $layoutChildren[] = ['type' => 'TextCaption', 'text' => trim($caption)];
    }

    $formChildren = [];
    foreach ($finalFormState['fields'] as $field) {
        $safeName = slugify((string) ($field['name'] ?? $field['label'] ?? 'campo'), true);
        $type = (string) ($field['type'] ?? 'text');

        if ($type === 'text') {
            $formChildren[] = [
                'type' => 'TextInput',
                'required' => !empty($field['required']),
                'label' => (string) ($field['label'] ?? $safeName),
                'name' => $safeName,
            ];
            continue;
        }

        if ($type === 'textarea') {
            $formChildren[] = [
                'type' => 'TextArea',
                'required' => !empty($field['required']),
                'label' => (string) ($field['label'] ?? $safeName),
                'name' => $safeName,
            ];
            continue;
        }

        $options = buildChoiceSource($field['options'] ?? []);

        if ($type === 'radio') {
            if (trim((string) ($field['label'] ?? '')) !== '') {
                $formChildren[] = ['type' => 'TextSubheading', 'text' => trim((string) $field['label'])];
            }
            $formChildren[] = [
                'type' => 'RadioButtonsGroup',
                'required' => !empty($field['required']),
                'label' => (string) ($field['label'] ?? $safeName),
                'name' => $safeName,
                'data-source' => $options,
            ];
            continue;
        }

        if ($type === 'checkbox') {
            $formChildren[] = [
                'type' => 'CheckboxGroup',
                'required' => !empty($field['required']),
                'label' => (string) ($field['label'] ?? $safeName),
                'name' => $safeName,
                'data-source' => $options,
            ];
            continue;
        }

        if ($type === 'dropdown') {
            $formChildren[] = [
                'type' => 'Dropdown',
                'required' => !empty($field['required']),
                'label' => (string) ($field['label'] ?? $safeName),
                'name' => $safeName,
                'data-source' => $options,
            ];
        }
    }

    if (trim($note) !== '') {
        $formChildren[] = ['type' => 'TextBody', 'text' => trim($note)];
    }

    $payload = [
        'flow_name' => $flowName,
        'flow_slug' => $flowSlug,
        'category_id' => slugify((string) ($menuItem['id'] ?? $menuItem['title'] ?? ''), true),
        'category_title' => (string) ($menuItem['title'] ?? ''),
        'option_id' => slugify((string) ($subitem['id'] ?? $subitem['title'] ?? ''), true),
        'option_title' => (string) ($subitem['title'] ?? ''),
    ];

    foreach ($finalFormState['fields'] as $field) {
        $safeName = slugify((string) ($field['name'] ?? $field['label'] ?? 'campo'), true);
        $payload[$safeName] = '${form.' . $safeName . '}';
    }

    $formChildren[] = [
        'type' => 'Footer',
        'label' => (string) ($finalFormState['submit_label'] ?? 'Enviar'),
        'on-click-action' => [
            'name' => 'complete',
            'payload' => $payload,
        ],
    ];

    $layoutChildren[] = [
        'type' => 'Form',
        'name' => slugify((string) ($menuItem['title'] ?? 'menu') . '_' . (string) ($subitem['title'] ?? 'opcao') . '_form', true),
        'children' => $formChildren,
    ];

    return [
        'id' => $formScreenId,
        'title' => (string) ($subitem['title'] ?? $menuItem['title'] ?? 'Detalhes'),
        'terminal' => true,
        'data' => new stdClass(),
        'layout' => [
            'type' => 'SingleColumnLayout',
            'children' => $layoutChildren,
        ],
    ];
}

function buildGuidedFlowJson(array $state, string $flowName, string $flowSlug): array
{
    $version = (string) ($state['version'] ?? '7.3');
    $introId = toMetaIdentifier((string) ($state['intro']['screen_id'] ?? 'WELCOME'), 'WELCOME');
    $menuId = toMetaIdentifier((string) ($state['menu']['screen_id'] ?? 'MAIN_MENU'), 'MAIN_MENU');
    $introChildren = [];
    $menuItems = [];
    $menuRoutes = [];
    $submenuScreens = [];
    $finalScreens = [];
    $routingModel = [];

    if (!empty($state['intro']['image_data_url'])) {
        $introImage = [
            'type' => 'Image',
            'src' => explode(',', (string) $state['intro']['image_data_url'], 2)[1] ?? (string) $state['intro']['image_data_url'],
            'scale-type' => (string) ($state['intro']['image_scale_type'] ?? 'cover'),
        ];
        if (!empty($state['intro']['image_alt'])) {
            $introImage['alt-text'] = trim((string) $state['intro']['image_alt']);
        }
        if (!empty($state['intro']['image_width'])) {
            $introImage['width'] = (int) $state['intro']['image_width'];
        }
        if (!empty($state['intro']['image_height'])) {
            $introImage['height'] = (int) $state['intro']['image_height'];
        }
        if (!empty($state['intro']['image_aspect_ratio'])) {
            $introImage['aspect-ratio'] = (float) $state['intro']['image_aspect_ratio'];
        }
        $introChildren[] = $introImage;
    }

    foreach (['heading' => 'TextHeading', 'caption' => 'TextCaption', 'body_text' => 'TextBody'] as $field => $component) {
        $value = trim((string) ($state['intro'][$field] ?? ''));
        if ($value !== '') {
            $introChildren[] = ['type' => $component, 'text' => $value];
        }
    }

    $introChildren[] = [
        'type' => 'Footer',
        'label' => (string) ($state['intro']['button_label'] ?? 'Iniciar'),
        'on-click-action' => [
            'name' => 'navigate',
            'next' => ['name' => $menuId, 'type' => 'screen'],
            'payload' => new stdClass(),
        ],
    ];

    foreach (($state['items'] ?? []) as $menuIndex => $item) {
        $menuSuffix = alphaSequence($menuIndex);
        $submenuScreenId = composeMetaIdentifier(
            [(string) ($item['title'] ?? 'SECTION'), $menuSuffix],
            ['SECTION', $menuSuffix]
        );
        $submenuRoutes = [];
        $submenuItems = [];

        $menuRoutes[] = $submenuScreenId;
        $menuItems[] = navigationListItem($item, $submenuScreenId, $submenuScreenId);

        foreach (($item['subitems'] ?? []) as $subIndex => $subitem) {
            $optionSuffix = alphaSequence($subIndex);
            $finalScreenId = composeMetaIdentifier(
                [(string) ($item['title'] ?? 'SECTION'), (string) ($subitem['title'] ?? 'OPTION'), $menuSuffix, $optionSuffix],
                ['FINAL', $menuSuffix, $optionSuffix]
            );
            $submenuRoutes[] = $finalScreenId;
            $submenuItems[] = navigationListItem([
                'id' => $subitem['id'] ?? '',
                'title' => $subitem['title'] ?? '',
                'description' => $subitem['description'] ?? '',
                'metadata' => $subitem['metadata'] ?? '',
                'image_data_url' => $subitem['image_data_url'] ?? '',
                'image_alt' => $subitem['image_alt'] ?? '',
            ], $finalScreenId, $finalScreenId);
            $finalScreens[] = buildFinalScreen($finalScreenId, $item, $subitem, $state['final_form'], $flowName, $flowSlug);
            $routingModel[$finalScreenId] = [];
        }

        $submenuScreens[] = [
            'id' => $submenuScreenId,
            'title' => (string) ($item['title'] ?? ('Section ' . ($menuIndex + 1))),
            'terminal' => false,
            'data' => new stdClass(),
            'layout' => [
                'type' => 'SingleColumnLayout',
                'children' => [[
                    'type' => 'NavigationList',
                    'name' => slugify((string) ($item['title'] ?? 'section') . '_submenu', true),
                    'label' => (string) ($item['title'] ?? ('Section ' . ($menuIndex + 1))),
                    'description' => (string) ($item['description'] ?? ''),
                    'list-items' => $submenuItems,
                ]],
            ],
        ];

        $routingModel[$submenuScreenId] = $submenuRoutes;
    }

    $screens = [[
        'id' => $introId,
        'title' => (string) ($state['intro']['screen_title'] ?? 'Boas-vindas'),
        'terminal' => false,
        'data' => new stdClass(),
        'layout' => ['type' => 'SingleColumnLayout', 'children' => $introChildren],
    ], [
        'id' => $menuId,
        'title' => (string) ($state['menu']['screen_title'] ?? 'Menu'),
        'terminal' => false,
        'data' => new stdClass(),
        'layout' => [
            'type' => 'SingleColumnLayout',
            'children' => [[
                'type' => 'NavigationList',
                'name' => 'main_menu',
                'label' => (string) ($state['menu']['label'] ?? 'Escolha uma categoria'),
                'description' => (string) ($state['menu']['description'] ?? ''),
                'list-items' => $menuItems,
            ]],
        ],
    ]];

    $screens = array_merge($screens, $submenuScreens, $finalScreens);
    $routingModel[$introId] = [$menuId];
    $routingModel[$menuId] = $menuRoutes;

    return [
        'version' => $version,
        'routing_model' => $routingModel,
        'screens' => $screens,
    ];
}

function graphRequest(string $method, string $path, string $token, array $options = []): array
{
    $url = 'https://graph.facebook.com/' . GRAPH_VERSION . '/' . ltrim($path, '/');
    if (!empty($options['query'])) {
        $url .= '?' . http_build_query($options['query']);
    }

    $ch = curl_init($url);
    $headers = ['Authorization: Bearer ' . $token];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_TIMEOUT => 120,
    ]);

    if (!empty($options['form'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options['form']);
    } elseif (array_key_exists('json', $options)) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        fail('Falha CURL: ' . $curlError);
    }

    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        $decoded = ['raw' => $raw];
    }

    return ['http' => $http, 'data' => $decoded, 'raw' => (string) $raw];
}

function insertEvent(int $teamId, int $flowId, int $endpointId, int $accountId, string $accountIds, string $eventType, string $status, array $payload = [], array $response = [], string $error = ''): void
{
    dbExec(
        'INSERT INTO sp_whatsapp_flow_events (team_id, flow_id, endpoint_id, account_id, account_ids, event_type, status, payload, response, error_message, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            (string) $teamId,
            (string) $flowId,
            (string) $endpointId,
            (string) $accountId,
            $accountIds,
            $eventType,
            $status,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $error,
            (string) time(),
        ]
    );
}

function upsertAsset(int $teamId, int $flowId, string $metaFlowId, string $name, string $assetType, string $mimeType, string $storagePath, ?string $publicUrl, string $checksum, ?string $metaAssetId = null, ?string $metaAssetHandle = null): void
{
    dbExec(
        'INSERT INTO sp_whatsapp_flow_assets (ids, team_id, flow_id, meta_flow_id, meta_asset_id, meta_asset_handle, name, asset_type, mime_type, storage_path, public_url, checksum, status, sort_order, changed, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)',
        [
            ids(),
            (string) $teamId,
            (string) $flowId,
            $metaFlowId,
            (string) $metaAssetId,
            (string) $metaAssetHandle,
            $name,
            $assetType,
            $mimeType,
            $storagePath,
            (string) $publicUrl,
            $checksum,
            'meta_synced',
            (string) time(),
            (string) time(),
        ]
    );
}

$account = dbRow('SELECT * FROM sp_accounts WHERE id = ?', [(string) ACCOUNT_ID]);
if (!$account) {
    fail('Conta Cloud ' . ACCOUNT_ID . ' nao encontrada.');
}

$endpoint = dbRow('SELECT * FROM sp_whatsapp_flow_endpoints WHERE account_id = ? ORDER BY id DESC LIMIT 1', [(string) ACCOUNT_ID]);
if (!$endpoint) {
    fail('Endpoint de Flow da conta nao encontrado.');
}

$accountData = json_decode((string) ($account['data'] ?? ''), true);
if (!is_array($accountData) || empty($accountData['token'])) {
    fail('Token da Cloud API nao encontrado na conta.');
}

$graphToken = (string) $accountData['token'];
$teamId = (int) $account['team_id'];
$accountIds = (string) ($account['ids'] ?? '');
$phoneNumberId = (string) ($endpoint['phone_number_id'] ?? ($accountData['phone_number_id'] ?? ''));
$wabaId = (string) ($endpoint['waba_id'] ?? '');
$endpointId = (int) $endpoint['id'];
$endpointUri = (string) ($endpoint['endpoint_uri'] ?? '');

if ($wabaId === '' || $endpointUri === '') {
    fail('WABA ID ou endpoint URI ausentes para a conta.');
}

$timestamp = date('Ymd_His');
$flowName = 'COLETA_PROFISSIONAL_RAMO_UNICO_' . $timestamp;
$flowSlug = 'coleta_profissional_ramo_unico_' . strtolower($timestamp);
$baseDir = __DIR__ . '/../../writable/uploads/flow_assets/' . $flowSlug;
if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
    fail('Nao foi possivel criar diretorio de assets em ' . $baseDir);
}

$assetSpecs = [
    'cover' => [
        'eyebrow' => 'Flow completo',
        'title' => 'Coleta de dados com atendimento guiado',
        'subtitle' => 'Um fluxo profissional para captar informacoes, direcionar a solicitacao e agilizar o retorno da equipe.',
        'bullets' => ['Menu com categorias', 'Submenus detalhados', 'Formulario final completo'],
        'palette' => ['start' => '#0B1F4A', 'end' => '#2D7FF9', 'accent' => '#7FE0FF', 'dark' => '#142B60'],
    ],
    'cadastro' => [
        'eyebrow' => 'Cadastro',
        'title' => 'Atualizacao e validacao de dados',
        'subtitle' => 'Ideal para cadastro inicial, correcao de email, telefone, cidade e dados de contato.',
        'bullets' => ['Dados essenciais', 'Contato validado', 'Retorno organizado'],
        'palette' => ['start' => '#103A2C', 'end' => '#14A86D', 'accent' => '#86FFD1', 'dark' => '#0E4C3A'],
    ],
    'comercial' => [
        'eyebrow' => 'Comercial',
        'title' => 'Lead qualificado para proposta ou demo',
        'subtitle' => 'Capta interesse, urgencia e canal preferido para acelerar a conversao comercial.',
        'bullets' => ['Solicitar proposta', 'Agendar demonstracao', 'Qualificacao rapida'],
        'palette' => ['start' => '#3A1C67', 'end' => '#7A4DFF', 'accent' => '#D9C8FF', 'dark' => '#4B2A7F'],
    ],
    'suporte' => [
        'eyebrow' => 'Suporte',
        'title' => 'Triagem inteligente para atendimento tecnico',
        'subtitle' => 'Recebe contexto inicial, nivel de urgencia e detalhes do caso antes do retorno da equipe.',
        'bullets' => ['Abrir chamado', 'Priorizar retorno', 'Diminuir retrabalho'],
        'palette' => ['start' => '#5A1B1B', 'end' => '#E15353', 'accent' => '#FFD0D0', 'dark' => '#7A2525'],
    ],
];

$imageDataUrls = [];
foreach ($assetSpecs as $key => $spec) {
    $path = $baseDir . '/' . $key . '.png';
    drawFlowCard($path, $spec['palette'], $spec['eyebrow'], $spec['title'], $spec['subtitle'], $spec['bullets']);
    $imageDataUrls[$key] = imageDataUrl($path);
}

$builderState = [
    'builder_type' => 'guided_menu',
    'simple_form' => [
        'version' => '7.3',
        'screen_id' => 'SCREEN_1',
        'screen_title' => 'Novo Flow',
        'form_name' => 'form',
        'heading' => '',
        'caption' => '',
        'body_text' => '',
        'submit_label' => 'Enviar',
        'fields' => [],
    ],
    'guided_menu' => [
        'version' => '7.3',
        'intro' => [
            'screen_id' => 'WELCOME',
            'screen_title' => 'Boas-vindas',
            'heading' => 'Atendimento guiado',
            'caption' => 'Escolha a area correta e preencha os dados uma unica vez.',
            'body_text' => 'Este Flow foi montado para captar informacoes com mais qualidade, direcionar a solicitacao e reduzir retrabalho no atendimento.',
            'button_label' => 'Iniciar atendimento',
            'image_data_url' => $imageDataUrls['cover'],
            'image_alt' => 'Capa do flow de coleta de dados',
            'image_scale_type' => 'cover',
            'image_width' => '400',
            'image_height' => '260',
            'image_aspect_ratio' => '1.6',
        ],
        'menu' => [
            'screen_id' => 'MAIN_MENU',
            'screen_title' => 'Menu',
            'label' => 'Escolha uma categoria para continuar',
            'description' => 'Cada caminho leva a um formulario final mais completo.',
            'media_size' => 'regular',
        ],
        'items' => [
            [
                'id' => 'atendimento_guiado',
                'title' => 'Atendimento guiado',
                'description' => 'Triagem inicial com coleta de dados',
                'metadata' => 'Versao profissional com navegacao controlada',
                'badge' => 'Teste',
                'tags' => ['triagem', 'dados'],
                'side_title' => '1 opcao',
                'side_description' => 'Ramo unico',
                'image_data_url' => $imageDataUrls['cover'],
                'image_alt' => 'Categoria atendimento guiado',
                'subitems' => [
                    [
                        'id' => 'abrir_atendimento',
                        'title' => 'Abrir atendimento',
                        'description' => 'Preencha um formulario curto e siga com a equipe',
                        'metadata' => 'Mesmo desenho estrutural do flow base',
                        'detail_text' => 'Preencha os dados abaixo para registrarmos sua solicitacao e retornarmos com contexto.',
                        'image_data_url' => $imageDataUrls['cover'],
                        'image_alt' => 'Abertura de atendimento',
                    ],
                ],
            ],
        ],
        'final_form' => [
            'heading' => 'Finalize sua solicitacao',
            'caption' => '{{categoria}} > {{opcao}}',
            'body_text' => 'Preencha os dados abaixo e nossa equipe retornara com o contexto completo da sua solicitacao.',
            'submit_label' => 'Enviar dados',
            'fields' => [
                ['type' => 'text', 'label' => 'Nome completo', 'name' => 'nome_completo', 'required' => true, 'options' => []],
                ['type' => 'text', 'label' => 'Telefone', 'name' => 'telefone', 'required' => true, 'options' => []],
                ['type' => 'text', 'label' => 'E-mail', 'name' => 'email', 'required' => false, 'options' => []],
                ['type' => 'textarea', 'label' => 'Detalhes adicionais', 'name' => 'detalhes_adicionais', 'required' => true, 'options' => []],
            ],
        ],
    ],
];

$flowJsonArray = buildGuidedFlowJson($builderState['guided_menu'], $flowName, $flowSlug);
$flowJson = json_encode($flowJsonArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$builderStateJson = json_encode($builderState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$previewDataJson = json_encode([
    'nome_completo' => 'Ana Oliveira',
    'telefone' => '5511999999999',
    'email' => 'ana@empresa.com',
    'detalhes_adicionais' => 'Preciso de retorno com orientacao inicial.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$jsonPath = $baseDir . '/flow.json';
file_put_contents($jsonPath, $flowJson);

dbExec(
    'INSERT INTO sp_whatsapp_flows (ids, team_id, account_id, account_ids, waba_id, phone_number_id, endpoint_id, name, slug, channel, status_local, json_version, data_api_version, categories_json, flow_json, preview_data, builder_state, changed, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [
        ids(),
        (string) $teamId,
        (string) ACCOUNT_ID,
        $accountIds,
        $wabaId,
        $phoneNumberId,
        (string) $endpointId,
        $flowName,
        $flowSlug,
        'cloud_api',
        'draft',
        '7.3',
        '3.0',
        json_encode(['LEAD_GENERATION', 'CONTACT_US', 'CUSTOMER_SUPPORT'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $flowJson,
        $previewDataJson,
        $builderStateJson,
        (string) time(),
        (string) time(),
    ]
);
$flowId = dbInsertId();

info('Flow local criado: ID ' . $flowId . ' / ' . $flowName);

$createResponse = graphRequest('POST', $wabaId . '/flows', $graphToken, [
    'form' => [
        'name' => $flowName,
        'categories' => json_encode(['LEAD_GENERATION', 'CONTACT_US', 'CUSTOMER_SUPPORT'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'endpoint_uri' => $endpointUri,
        'data_api_version' => '3.0',
    ],
]);

insertEvent($teamId, $flowId, $endpointId, ACCOUNT_ID, $accountIds, 'meta_create', $createResponse['http'] === 200 ? 'success' : 'error', [
    'name' => $flowName,
    'categories' => ['LEAD_GENERATION', 'CONTACT_US', 'CUSTOMER_SUPPORT'],
    'endpoint_uri' => $endpointUri,
    'data_api_version' => '3.0',
], $createResponse['data'], $createResponse['http'] === 200 ? '' : $createResponse['raw']);

if ($createResponse['http'] !== 200 || empty($createResponse['data']['id'])) {
    dbExec('UPDATE sp_whatsapp_flows SET last_meta_error = ?, changed = ? WHERE id = ?', [$createResponse['raw'], (string) time(), (string) $flowId]);
    fail('Meta recusou a criacao do draft: ' . $createResponse['raw']);
}

$metaFlowId = (string) $createResponse['data']['id'];
dbExec('UPDATE sp_whatsapp_flows SET meta_flow_id = ?, status_meta = ?, changed = ? WHERE id = ?', [$metaFlowId, 'DRAFT', (string) time(), (string) $flowId]);
info('Draft criado na Meta: ' . $metaFlowId);

$uploadResponse = graphRequest('POST', $metaFlowId . '/assets', $graphToken, [
    'form' => [
        'name' => 'flow.json',
        'asset_type' => 'FLOW_JSON',
        'file' => curl_file_create($jsonPath, 'application/json', 'flow.json'),
    ],
]);

insertEvent($teamId, $flowId, $endpointId, ACCOUNT_ID, $accountIds, 'meta_upload_json', $uploadResponse['http'] === 200 ? 'success' : 'error', [
    'path' => $jsonPath,
    'asset_type' => 'FLOW_JSON',
], $uploadResponse['data'], $uploadResponse['http'] === 200 ? '' : $uploadResponse['raw']);

if ($uploadResponse['http'] !== 200) {
    dbExec('UPDATE sp_whatsapp_flows SET last_meta_error = ?, changed = ? WHERE id = ?', [$uploadResponse['raw'], (string) time(), (string) $flowId]);
    fail('Meta recusou o upload do flow.json: ' . $uploadResponse['raw']);
}

$metaAssetId = (string) ($uploadResponse['data']['id'] ?? '');
$metaAssetHandle = (string) ($uploadResponse['data']['asset_handle'] ?? '');
$metaAssetUrl = (string) ($uploadResponse['data']['url'] ?? '');
upsertAsset(
    $teamId,
    $flowId,
    $metaFlowId,
    'flow.json',
    'FLOW_JSON',
    'application/json',
    $jsonPath,
    $metaAssetUrl !== '' ? $metaAssetUrl : null,
    sha1_file($jsonPath) ?: sha1($flowJson),
    $metaAssetId !== '' ? $metaAssetId : null,
    $metaAssetHandle !== '' ? $metaAssetHandle : null
);

$publishResponse = graphRequest('POST', $metaFlowId . '/publish', $graphToken, [
    'form' => [],
]);

insertEvent($teamId, $flowId, $endpointId, ACCOUNT_ID, $accountIds, 'meta_publish', $publishResponse['http'] === 200 ? 'success' : 'error', [], $publishResponse['data'], $publishResponse['http'] === 200 ? '' : $publishResponse['raw']);

if ($publishResponse['http'] !== 200) {
    dbExec('UPDATE sp_whatsapp_flows SET last_meta_error = ?, changed = ? WHERE id = ?', [$publishResponse['raw'], (string) time(), (string) $flowId]);
    fail('Meta recusou a publicacao: ' . $publishResponse['raw']);
}

$detailsResponse = graphRequest('GET', $metaFlowId, $graphToken, [
    'query' => [
        'fields' => 'id,name,categories,preview,status,validation_errors,json_version,data_api_version,data_channel_uri,health_status',
    ],
]);

insertEvent($teamId, $flowId, $endpointId, ACCOUNT_ID, $accountIds, 'meta_sync', $detailsResponse['http'] === 200 ? 'success' : 'error', [], $detailsResponse['data'], $detailsResponse['http'] === 200 ? '' : $detailsResponse['raw']);

$statusMeta = 'PUBLISHED';
$previewUrl = '';
$dataChannelUri = '';
$healthStatus = '';
$lastMetaError = null;

if ($detailsResponse['http'] === 200) {
    $statusMeta = (string) ($detailsResponse['data']['status'] ?? 'PUBLISHED');
    $previewRaw = $detailsResponse['data']['preview'] ?? '';
    $previewUrl = is_string($previewRaw) ? $previewRaw : json_encode($previewRaw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $dataChannelUri = (string) ($detailsResponse['data']['data_channel_uri'] ?? '');
    $healthStatus = json_encode($detailsResponse['data']['health_status'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $validationErrors = $detailsResponse['data']['validation_errors'] ?? [];
    if (!empty($validationErrors)) {
        $lastMetaError = json_encode($validationErrors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} else {
    $lastMetaError = $detailsResponse['raw'];
}

dbExec(
    'UPDATE sp_whatsapp_flows SET meta_flow_id = ?, status_local = ?, status_meta = ?, preview_url = ?, data_channel_uri = ?, health_status = ?, last_meta_error = ?, last_sync_at = ?, published_at = ?, changed = ? WHERE id = ?',
    [
        $metaFlowId,
        'ready',
        $statusMeta,
        $previewUrl,
        $dataChannelUri,
        $healthStatus,
        (string) $lastMetaError,
        (string) time(),
        (string) time(),
        (string) time(),
        (string) $flowId,
    ]
);

info('Flow publicado com sucesso na Meta.');
info('Meta Flow ID: ' . $metaFlowId);

$sendResponse = graphRequest('POST', $phoneNumberId . '/messages', $graphToken, [
    'json' => [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => TEST_SEND_TO,
        'type' => 'interactive',
        'interactive' => [
            'type' => 'flow',
            'body' => ['text' => 'Teste do novo flow profissional de coleta de dados.'],
            'action' => [
                'name' => 'flow',
                'parameters' => [
                    'flow_message_version' => '3',
                    'flow_token' => 'flow_test_' . ids(10),
                    'flow_cta' => 'Abrir atendimento',
                    'flow_action' => 'navigate',
                    'mode' => 'published',
                    'flow_id' => $metaFlowId,
                    'flow_action_payload' => [
                        'screen' => 'WELCOME',
                        'data' => [
                            'origem' => 'script_professional_flow',
                        ],
                    ],
                ],
            ],
        ],
    ],
]);

insertEvent(
    $teamId,
    $flowId,
    $endpointId,
    ACCOUNT_ID,
    $accountIds,
    'flow_single_send',
    $sendResponse['http'] === 200 ? 'success' : 'error',
    ['to' => TEST_SEND_TO, 'screen' => 'WELCOME'],
    $sendResponse['data'],
    $sendResponse['http'] === 200 ? '' : $sendResponse['raw']
);

if ($sendResponse['http'] === 200 && !empty($sendResponse['data']['messages'][0]['id'])) {
    info('Teste de envio aceito pela Meta. WAMID: ' . $sendResponse['data']['messages'][0]['id']);
} else {
    info('Flow criado, mas o teste de envio nao foi aceito agora: ' . $sendResponse['raw']);
}

info('Diretorio dos assets: ' . $baseDir);
info('Arquivo do Flow JSON: ' . $jsonPath);
