<?php
while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json');

$env = parse_ini_file(__DIR__ . '/.env');
$db = new mysqli(
    $env['database.default.hostname'] ?? 'localhost',
    $env['database.default.username'] ?? '',
    $env['database.default.password'] ?? '',
    $env['database.default.database'] ?? ''
);

// Detect Go API port from local config.json
$goPort = '8090';
$cfgPaths = [
    __DIR__ . '/app_zapmatic_whatsmeow_api/config.json',
    __DIR__ . '/../app_zapmatic_whatsmeow_api/config.json',
];
foreach ($cfgPaths as $cfgPath) {
    if (file_exists($cfgPath)) {
        $cfg = json_decode(file_get_contents($cfgPath), true);
        if (!empty($cfg['port'])) {
            $goPort = (string)$cfg['port'];
            break;
        }
    }
}
$goBaseUrl = 'http://127.0.0.1:' . $goPort;

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!preg_match('#/pair_status\.php/(WMEOW_[A-F0-9]+)#', $path, $m)) {
    echo '{"state":"error","message":"invalid"}';
    exit;
}
$instanceId = $m[1];

// Check Go API status
$ch = curl_init($goBaseUrl . '/status?instance_id=' . urlencode($instanceId));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$resp = curl_exec($ch);
curl_close($ch);
if (!$resp) { echo '{"state":"error","message":"gateway offline"}'; exit; }

$status = json_decode($resp);
if (!$status) { echo '{"state":"error"}'; exit; }

if ($status->state !== 'connected') {
    echo json_encode(['state' => $status->state]);
    exit;
}

// CONNECTED! Get profile with avatar
$avatar = '';
$chP = curl_init($goBaseUrl . '/profile?instance_id=' . urlencode($instanceId));
curl_setopt_array($chP, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
$profResp = curl_exec($chP);
curl_close($chP);
if ($profResp) {
    $prof = json_decode($profResp);
    if ($prof && !empty($prof->avatar_url)) {
        $avatar = $prof->avatar_url;
    }
}

$jid = $status->jid ?? '';
$phone = $status->phone ?? '';
$pushName = $status->push_name ?? '';

// Get team_id from pending session
$teamId = 0;
$sessRes = $db->query("SELECT team_id FROM sp_whatsapp_sessions WHERE instance_id = '" . $db->real_escape_string($instanceId) . "' ORDER BY id DESC LIMIT 1");
if ($sessRow = $sessRes->fetch_assoc()) {
    $teamId = (int)$sessRow['team_id'];
}
if (!$teamId) {
    $sessRes2 = $db->query("SELECT team_id FROM sp_whatsapp_sessions WHERE instance_id LIKE 'WMEOW_%' ORDER BY id DESC LIMIT 1");
    if ($sessRow2 = $sessRes2->fetch_assoc()) {
        $teamId = (int)$sessRow2['team_id'];
    }
}

// Check if account already exists
$accRes = $db->query("SELECT id FROM sp_accounts WHERE token = '" . $db->real_escape_string($instanceId) . "'");
if ($accRes->num_rows > 0) {
    $db->query("UPDATE sp_accounts SET status = 1, pid = '" . $db->real_escape_string($jid) . "', name = '" . $db->real_escape_string($pushName ?: $phone) . "', avatar = '" . $db->real_escape_string($avatar) . "' WHERE token = '" . $db->real_escape_string($instanceId) . "'");
    echo json_encode(['state' => 'connected', 'saved' => true, 'instance_id' => $instanceId, 'team_id' => $teamId, 'name' => $pushName, 'avatar' => $avatar]);
    exit;
}

// Create the account
if ($teamId) {
    $name = $db->real_escape_string($pushName ?: $phone ?: $jid);
    $pid = $db->real_escape_string($jid);
    $token = $db->real_escape_string($instanceId);
    $avatarEsc = $db->real_escape_string($avatar);

    $db->query("INSERT INTO sp_accounts (ids, team_id, token, pid, name, avatar, social_network, category, status, login_type, data) VALUES (UUID(), $teamId, '$token', '$pid', '$name', '$avatarEsc', 'whatsapp', 'profile', 1, 3, '{\"gateway\":\"whatsmeow\",\"jid\":\"$pid\"}')");
    $db->query("UPDATE sp_whatsapp_sessions SET status = 1, data = '{\"gateway\":\"whatsmeow\",\"jid\":\"$pid\"}' WHERE instance_id = '$token'");

    echo json_encode(['state' => 'connected', 'saved' => true, 'instance_id' => $instanceId, 'team_id' => $teamId, 'name' => $pushName, 'avatar' => $avatar]);
} else {
    echo json_encode(['state' => 'connected', 'saved' => false, 'error' => 'no team_id found']);
}
