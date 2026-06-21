<?php
namespace Core\Whatsapp_webhook\Controllers;

class Whatsapp_webhook extends \CodeIgniter\Controller
{
    protected function getStatusPriority(string $status): int
    {
        $map = [
            'sent' => 1,
            'delivered' => 2,
            'read' => 3,
            'failed' => 4,
            'deleted' => 4,
        ];

        $status = strtolower(trim($status));
        return $map[$status] ?? 0;
    }

    protected function extractMetaErrorPayload(array $statusItem): array
    {
        $error = $statusItem['errors'][0] ?? [];

        $code = isset($error['code']) ? (int) $error['code'] : null;
        $title = trim((string) ($error['title'] ?? $error['message'] ?? ''));
        $details = trim((string) ($error['details'] ?? ($error['error_data']['details'] ?? '')));

        return [
            'code' => $code,
            'title' => $title,
            'details' => $details,
        ];
    }

    protected function syncCloudDispatchFromMetaStatus($db, $messageRow, string $statusText, int $timestamp, array $errorPayload): void
    {
        if (empty($messageRow) || empty($messageRow->schedule_id) || empty($messageRow->wa_message_id)) {
            return;
        }

        $dispatchBuilder = $db->table(TB_WHATSAPP_CLOUD_DISPATCHES);
        $dispatch = $dispatchBuilder
            ->where('schedule_id', (int) $messageRow->schedule_id)
            ->where('wa_message_id', (string) $messageRow->wa_message_id)
            ->get()
            ->getRow();

        if (!$dispatch) {
            return;
        }

        $currentDispatchStatus = strtolower((string) ($dispatch->status ?? 'queued'));
        $metaStatus = strtolower(trim($statusText));
        $update = [
            'updated' => $timestamp,
        ];

        if (in_array($metaStatus, ['failed', 'deleted'], true)) {
            $errorMessage = trim(($errorPayload['code'] ? '[' . $errorPayload['code'] . '] ' : '') . ($errorPayload['title'] ?? ''));
            if (!empty($errorPayload['details'])) {
                $errorMessage .= ($errorMessage !== '' ? ' - ' : '') . $errorPayload['details'];
            }

            $update['status'] = 'failed';
            $update['error_code'] = $errorPayload['code'];
            $update['error_message'] = $errorMessage !== '' ? $errorMessage : 'Falha retornada pela Meta';
            $update['last_attempt_at'] = $timestamp;
            $update['next_attempt_at'] = null;
        } elseif ($currentDispatchStatus !== 'failed') {
            $update['status'] = 'sent';
            $update['last_attempt_at'] = $timestamp;
            $update['next_attempt_at'] = null;
            $update['error_code'] = null;
            $update['error_message'] = null;
        }

        $dispatchBuilder->where('id', (int) $dispatch->id)->update($update);
    }

    protected function reconcileCloudParallelScheduleCounters($db, int $scheduleId): void
    {
        if ($scheduleId <= 0) {
            return;
        }

        $dispatchRows = $db->table(TB_WHATSAPP_CLOUD_DISPATCHES)
            ->select('status, COUNT(*) as total')
            ->where('schedule_id', $scheduleId)
            ->groupBy('status')
            ->get()
            ->getResult();

        if (empty($dispatchRows)) {
            return;
        }

        $sent = 0;
        $failed = 0;

        foreach ($dispatchRows as $row) {
            $status = strtolower((string) ($row->status ?? 'queued'));
            $total = (int) ($row->total ?? 0);

            if ($status === 'sent') {
                $sent += $total;
            } elseif ($status === 'failed') {
                $failed += $total;
            }
        }

        $db->table(TB_WHATSAPP_SCHEDULES)
            ->where('id', $scheduleId)
            ->update([
                'sent' => $sent,
                'failed' => $failed,
            ]);
    }

    public function index()
    {
        // Debug Log
        $log_file = WRITEPATH . 'logs/webhook_debug.txt';
        $log_entry = "--- " . date('Y-m-d H:i:s') . " ---\n";
        $log_entry .= "Method: " . $this->request->getMethod() . "\n";
        $log_entry .= "URL: " . (string) current_url() . "\n";
        $log_entry .= "GET: " . json_encode($this->request->getGet()) . "\n";
        $log_entry .= "HEADERS: " . json_encode($this->request->getHeaders()) . "\n";

        // Clean any accidental output/warnings/whitespace
        while (ob_get_level())
            ob_end_clean();

        $hub_mode = $this->request->getGet('hub_mode');
        $hub_challenge = $this->request->getGet('hub_challenge');
        $hub_verify_token = $this->request->getGet('hub_verify_token');

        // Handling Verification Request
        if ($hub_mode == 'subscribe' && !empty($hub_verify_token)) {

            $db = \Config\Database::connect();

            // Use JSON_UNQUOTE and JSON_EXTRACT for precise matching
            $sql = "SELECT id FROM sp_accounts WHERE social_network = 'whatsapp' AND login_type = 1 AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.verify_token')) = ?";
            $query = $db->query($sql, [$hub_verify_token]);
            $row = $query->getRow();

            if ($row) {
                $log_entry .= "Validation SUCCESS for token: $hub_verify_token. Responding with challenge: $hub_challenge\n";
                file_put_contents($log_file, $log_entry, FILE_APPEND);

                header('Content-Type: text/plain');
                echo $hub_challenge;
                exit;
            } else {
                $log_entry .= "Validation FAILED for token: $hub_verify_token. Token not found in database for any Cloud API account (login_type = 1).\n";
                $log_entry .= "Ensue that you clicked 'Save Profile' in Zapmatic BEFORE trying to verify in Meta Dashboard.\n";
                file_put_contents($log_file, $log_entry, FILE_APPEND);

                header('HTTP/1.1 403 Forbidden');
                echo 'Invalid Verify Token or Account not saved yet.';
                exit;
            }
        }

        // Handling Incoming Messages / Statuses (POST)
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $log_entry .= "POST Body: " . $input . "\n";

            $data = json_decode($input, true);
            $value = $data['entry'][0]['changes'][0]['value'] ?? [];

            // 1) Processar STATUS da Cloud API diretamente no PHP (atualiza sp_whatsapp_message_status)
            if (!empty($value['statuses']) && is_array($value['statuses'])) {
                $db = \Config\Database::connect();
                foreach ($value['statuses'] as $statusItem) {
                    $waMessageId = $statusItem['id'] ?? null;
                    if (!$waMessageId) {
                        continue;
                    }

                    $statusText = $statusItem['status'] ?? null;
                    $ts = isset($statusItem['timestamp']) ? (int) $statusItem['timestamp'] : time();
                    $errorPayload = $this->extractMetaErrorPayload($statusItem);

                    $messageBuilder = $db->table(TB_WHATSAPP_MESSAGE_STATUS);
                    $currentRow = $messageBuilder->where('wa_message_id', $waMessageId)->get()->getRow();
                    if (!$currentRow) {
                        continue;
                    }

                    $currentTs = (int) ($currentRow->last_status_at ?? 0);
                    $currentPriority = $this->getStatusPriority((string) ($currentRow->status ?? ''));
                    $incomingPriority = $this->getStatusPriority((string) $statusText);

                    if ($currentTs > $ts || ($currentTs === $ts && $currentPriority > $incomingPriority)) {
                        continue;
                    }

                    $set = [
                        "status" => $statusText ?: 'sent',
                        "last_status_at" => $ts,
                    ];

                    if ($errorPayload['code'] !== null) {
                        $set["meta_error_code"] = $errorPayload['code'];
                    }
                    if ($errorPayload['title'] !== '') {
                        $set["meta_error_title"] = $errorPayload['title'];
                    }
                    if ($errorPayload['details'] !== '') {
                        $set["meta_error_details"] = $errorPayload['details'];
                    }

                    try {
                        $messageBuilder->where('wa_message_id', $waMessageId)->update($set);

                        $this->syncCloudDispatchFromMetaStatus($db, $currentRow, (string) ($statusText ?: 'sent'), $ts, $errorPayload);
                        $this->reconcileCloudParallelScheduleCounters($db, (int) $currentRow->schedule_id);
                    } catch (\Throwable $e) {
                        $log_entry .= "Status update error for wa_message_id {$waMessageId}: " . $e->getMessage() . "\n";
                    }
                }
            }

            // 2) Continuar encaminhando MENSAGENS para o Node.js (mantém comportamento atual)
            if (isset($value['metadata']['phone_number_id'])) {
                $phone_number_id = $value['metadata']['phone_number_id'];

                $db = \Config\Database::connect();
                // Find account token by phone_number_id in data JSON
                $sql = "SELECT token FROM sp_accounts WHERE social_network = 'whatsapp' AND login_type = 1 AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.phone_number_id')) = ?";
                $query = $db->query($sql, [$phone_number_id]);
                $row = $query->getRow();

                if ($row) {
                    $token = $row->token;
                    $log_entry .= "Forwarding payload for phone_number_id: $phone_number_id to Node API instance: $token\n";

                    // Forward to Node.js API
                    $ch = curl_init("http://localhost:9000/webhook/$token");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    $response = curl_exec($ch);
                    $curl_info = curl_getinfo($ch);
                    curl_close($ch);

                    $log_entry .= "Node API Response status: " . $curl_info['http_code'] . "\n";
                } else {
                    $log_entry .= "No account found matching phone_number_id: $phone_number_id\n";
                }
            }

            file_put_contents($log_file, $log_entry, FILE_APPEND);

            echo 'OK';
            exit;
        }

        $log_entry .= "No verification or POST data found.\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        echo "Whatsapp Webhook Endpoint Active.";
        exit;
    }
}
