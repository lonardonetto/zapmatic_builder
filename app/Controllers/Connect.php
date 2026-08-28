<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Connect extends Controller
{
    public function show($token = '')
    {
        $db = \Config\Database::connect();
        try {
            $link = $db->table('sp_connection_links')
                ->where('token', $token)
                ->get()->getRow();
        } catch (\Throwable $e) {
            return view('Connect/not_found');
        }

        if (!$link) {
            return view('Connect/not_found');
        }

        if ($link->status === 'used') {
            return view('Connect/success', [
                'phone' => $link->connected_phone,
                'name' => $link->connected_name,
                'avatar' => $link->connected_avatar,
                'client_name' => $link->client_name,
            ]);
        }

        if (strtotime($link->expires_at) < time() || $link->status === 'expired') {
            $db->table('sp_connection_links')->where('id', $link->id)->update(['status' => 'expired']);
            return view('Connect/expired');
        }

        $remaining = max(0, strtotime($link->expires_at) - time());
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;

        $host = parse_url(base_url(), PHP_URL_HOST);

        return view('Connect/connect', [
            'token' => $token,
            'instance_id' => $link->instance_id,
            'client_name' => $link->client_name,
            'expires_minutes' => $minutes,
            'expires_seconds' => $seconds,
            'remaining' => $remaining,
            'host' => $host,
        ]);
    }

    public function qr($token = '')
    {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        $db = \Config\Database::connect();
        try {
            $link = $db->table('sp_connection_links')
                ->where('token', $token)
                ->where('status', 'pending')
                ->get()->getRow();
        } catch (\Throwable $e) {
            echo json_encode(['state' => 'error', 'message' => 'Database error']);
            exit;
        }

        if (!$link || strtotime($link->expires_at) < time()) {
            echo json_encode(['error' => 'expired']);
            exit;
        }

        $goBaseUrl = $this->getGoBaseUrl();
        $ch = curl_init($goBaseUrl . '/qrcode?instance_id=' . urlencode($link->instance_id));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
        $resp = curl_exec($ch);
        curl_close($ch);

        if (!$resp) { echo json_encode(['error' => 'gateway offline']); exit; }

        $data = json_decode($resp);
        if (!$data || empty($data->qrcode)) { echo json_encode(['error' => 'no qr data']); exit; }

        echo json_encode(['qrcode' => $data->qrcode]);
        exit;
    }

    public function poll_status($token = '')
    {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        $db = \Config\Database::connect();
        $link = $db->table('sp_connection_links')
            ->where('token', $token)
            ->where('status', 'pending')
            ->get()->getRow();

        if (!$link) {
            echo json_encode(['state' => 'not_found']);
            exit;
        }

        if (strtotime($link->expires_at) < time()) {
            $db->table('sp_connection_links')->where('id', $link->id)->update(['status' => 'expired']);
            echo json_encode(['state' => 'expired']);
            exit;
        }

        // Check Go API - start instance if needed
        $goBaseUrl = $this->getGoBaseUrl();
        $ch = curl_init($goBaseUrl . '/status?instance_id=' . urlencode($link->instance_id));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
        $resp = curl_exec($ch);
        curl_close($ch);

        $status = $resp ? json_decode($resp) : null;

        // If instance not found or disconnected, auto-refresh QR
        if (!$status || !isset($status->state) || ($status->status ?? '') === 'error' || ($status->state ?? '') === 'disconnected') {
            // Restart instance to get fresh QR
            $chStart = curl_init($goBaseUrl . '/qrcode?instance_id=' . urlencode($link->instance_id));
            curl_setopt_array($chStart, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
            curl_exec($chStart);
            curl_close($chStart);

            // Re-check
            $ch2 = curl_init($goBaseUrl . '/status?instance_id=' . urlencode($link->instance_id));
            curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
            $resp2 = curl_exec($ch2);
            curl_close($ch2);
            $status2 = $resp2 ? json_decode($resp2) : null;

            echo json_encode(['state' => $status2->state ?? 'qr_ready', 'refresh' => true]);
            exit;
        }

        if ($status->state !== 'connected') {
            echo json_encode(['state' => $status->state]);
            exit;
        }

        // CONNECTED! Get profile
        $avatar = '';
        $pushName = $status->push_name ?? '';
        $phone = $status->phone ?? '';
        $jid = $status->jid ?? '';

        $chP = curl_init($goBaseUrl . '/profile?instance_id=' . urlencode($link->instance_id));
        curl_setopt_array($chP, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $profResp = curl_exec($chP);
        curl_close($chP);
        if ($profResp) {
            $prof = json_decode($profResp);
            if ($prof && !empty($prof->avatar_url)) {
                $avatar = $prof->avatar_url;
            }
            if ($prof && !empty($prof->push_name)) {
                $pushName = $prof->push_name;
            }
        }

        // Save account
        $teamId = $link->team_id;
        $instanceId = $link->instance_id;
        // Save account using query builder (safe escaping)
        $existing = $db->table('sp_accounts')->where('token', $instanceId)->get()->getRow();
        $accountData = json_encode(['gateway' => 'whatsmeow', 'jid' => $jid]);
        if ($existing) {
            $db->table('sp_accounts')->where('token', $instanceId)->update([
                'status' => 1, 'pid' => $jid, 'name' => $pushName ?: $phone, 'avatar' => $avatar
            ]);
        } else {
            $db->table('sp_accounts')->insert([
                'ids' => uniqid(), 'team_id' => $teamId, 'token' => $instanceId,
                'pid' => $jid, 'name' => $pushName ?: $phone, 'avatar' => $avatar,
                'social_network' => 'whatsapp', 'category' => 'profile',
                'module' => 'whatsapp_profiles',
                'status' => 1, 'login_type' => 3, 'data' => $accountData
            ]);
        }
        $db->table('sp_whatsapp_sessions')->where('instance_id', $instanceId)->update([
            'status' => 1, 'data' => $accountData
        ]);

        // Mark link as used
        $db->table('sp_connection_links')->where('id', $link->id)->update([
            'status' => 'used',
            'used_at' => date('Y-m-d H:i:s'),
            'connected_phone' => $phone,
            'connected_name' => $pushName,
            'connected_avatar' => $avatar,
        ]);

        echo json_encode([
            'state' => 'connected',
            'name' => $pushName,
            'phone' => $phone,
            'avatar' => $avatar,
        ]);
        exit;
    }

    public function paircode($token = '')
    {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        $phone = $this->request->getPost('phone');
        if (empty($phone)) {
            echo json_encode(['status' => 'error', 'message' => 'phone required']);
            exit;
        }

        $db = \Config\Database::connect();
        try {
            $link = $db->table('sp_connection_links')
                ->where('token', $token)
                ->where('status', 'pending')
                ->get()->getRow();
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
            exit;
        }

        if (!$link || strtotime($link->expires_at) < time()) {
            echo json_encode(['status' => 'error', 'message' => 'Link expired']);
            exit;
        }

        $goBaseUrl = $this->getGoBaseUrl();

        // Start QR (WebSocket)
        $ch1 = curl_init($goBaseUrl . '/qrcode?instance_id=' . urlencode($link->instance_id));
        curl_setopt_array($ch1, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
        $qrResp = curl_exec($ch1);
        curl_close($ch1);

        $qrData = json_decode($qrResp);
        if (!$qrData || ($qrData->status ?? '') !== 'success') {
            echo json_encode(['status' => 'error', 'message' => 'Failed to start connection']);
            exit;
        }

        // Generate pair code
        $ch2 = curl_init($goBaseUrl . '/paircode?instance_id=' . urlencode($link->instance_id) . '&phone=' . urlencode($phone));
        curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
        $pairResp = curl_exec($ch2);
        curl_close($ch2);

        $pairData = json_decode($pairResp);
        if (!$pairData || ($pairData->status ?? '') !== 'success') {
            echo json_encode(['status' => 'error', 'message' => $pairData->message ?? 'Failed to generate code']);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'code' => $pairData->code,
        ]);
        exit;
    }

    private function getGoBaseUrl(): string
    {
        $cfgPaths = [
            ROOTPATH . 'app_zapmatic_whatsmeow_api/config.json',
        ];
        foreach ($cfgPaths as $path) {
            if (file_exists($path)) {
                $cfg = json_decode(file_get_contents($path), true);
                if (!empty($cfg['port'])) {
                    return 'http://127.0.0.1:' . $cfg['port'];
                }
            }
        }
        return 'http://127.0.0.1:8090';
    }
}
