<?php
namespace Core\Whatsapp_webhook\Controllers;

class Whatsapp_webhook extends \CodeIgniter\Controller
{
    /**
     * Read the Baileys Node.js port from config.js automatically.
     * Cached in a static var so the file is read only once per request.
     */
    protected function getBaileysPort(): int
    {
        static $port = null;
        if ($port !== null) {
            return $port;
        }

        // Try to read from config.json (sibling directory)
        $paths = [
            ROOTPATH . '../app_zapmatic_api/config.js',
            dirname(ROOTPATH) . '/app_zapmatic_api/config.js',
        ];

        foreach ($paths as $configPath) {
            try {
                if (@is_file($configPath)) {
                    $content = @file_get_contents($configPath);
                    if ($content && preg_match('/\bport\s*:\s*(\d+)/', $content, $m)) {
                        $port = (int) $m[1];
                        return $port;
                    }
                }
            } catch (\Throwable $e) {
                // open_basedir restriction - skip
            }
        }

        // Fallback
        $port = 9000;
        return $port;
    }

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
        return;
    }
    protected function old_reconcileCloudParallelScheduleCounters($db, int $scheduleId): void
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

        $hub_mode = $this->request->getGet('hub_mode') ?? $this->request->getGet('hub.mode') ?? $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? null;
        $hub_challenge = $this->request->getGet('hub_challenge') ?? $this->request->getGet('hub.challenge') ?? $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? null;
        $hub_verify_token = $this->request->getGet('hub_verify_token') ?? $this->request->getGet('hub.verify_token') ?? $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? null;

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
            // NAO usar fastcgi_finish_request() antes do Bot_builder —
            // causa "ini_set(): Session ini settings cannot be changed after headers sent"
            // que impede o envio de respostas do autoresponder.
            // Em vez disso, sinalizamos que ja respondemos ao Meta.
            $headers_sent_to_meta = false;
            foreach($data['entry'] as $entryItem) {
                foreach($entryItem['changes'] as $changeItem) {
                    $value = $changeItem['value'] ?? [];

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

            // 2) Encaminhar mensagens Cloud API para Bot_builder (keywords + autorespond)
            if (isset($value['metadata']['phone_number_id'])) {
                $phone_number_id = (string)$value['metadata']['phone_number_id'];

                $db = \Config\Database::connect();
                $sql = "SELECT token FROM sp_accounts WHERE social_network = 'whatsapp' AND login_type = 1 AND (pid = ? OR JSON_UNQUOTE(JSON_EXTRACT(data, '$.phone_number_id')) = ?)";
                $query = $db->query($sql, [$phone_number_id, $phone_number_id]);
                $row = $query->getRow();

                if ($row) {
                    $token = $row->token;

                    // Buscar conta completa para uso em flow events
                    $account = $db->query(
                        "SELECT * FROM sp_accounts WHERE social_network = 'whatsapp' AND login_type = 1 AND token = ?",
                        [$token]
                    )->getRow();

                    // Forward to Bot_builder webhook (keywords + autorespond)
                    if (!empty($value['messages'])) {
                        $bot_payload = [
                            'instance_id' => $token,
                            'data' => ['messages' => []],
                        ];
                        foreach ($value['messages'] as $msg) {
                            $from = $msg['from'] ?? '';
                            $type = $msg['type'] ?? 'text';
                            $text_body = $msg['text']['body'] ?? '';
                            $push_name = '';
                            if (!empty($value['contacts'])) {
                                $push_name = $value['contacts'][0]['profile']['name'] ?? '';
                            }
                            $message_body = [];
                            if ($type === 'text') {
                                $message_body = ['conversation' => $text_body];
                            } elseif ($type === 'button') {
                                // Novo formato Meta: template buttons vêm como "type":"button" com "button.text" e "button.payload"
                                $button = $msg['button'] ?? [];
                                $message_body = [
                                    'buttonsResponseMessage' => [
                                        'selectedButtonId' => $button['payload'] ?? $button['text'] ?? '',
                                        'selectedDisplayText' => $button['text'] ?? $button['payload'] ?? '',
                                    ]
                                ];
                            } elseif ($type === 'interactive') {
                                // Button/list reply
                                $interactive = $msg['interactive'] ?? [];
                                $i_type = $interactive['type'] ?? '';
                                if ($i_type === 'button_reply') {
                                    $reply = $interactive['button_reply'] ?? [];
                                    $message_body = [
                                        'buttonsResponseMessage' => [
                                            'selectedButtonId' => $reply['id'] ?? '',
                                            'selectedDisplayText' => $reply['title'] ?? $reply['id'] ?? '',
                                        ]
                                    ];
                                } elseif ($i_type === 'list_reply') {
                                    $reply = $interactive['list_reply'] ?? [];
                                    $message_body = [
                                        'listResponseMessage' => [
                                            'title' => $reply['title'] ?? '',
                                            'singleSelectReply' => ['selectedRowId' => $reply['id'] ?? ''],
                                        ]
                                    ];
                                } elseif ($i_type === 'nfm_reply') {
                                    // Cloud API Flow response — converter para formato Bot Builder
                                    $nfm = $interactive['nfm_reply'] ?? [];
                                    $response_json = $nfm['response_json'] ?? '{}';
                                    $nfm_body = $nfm['body'] ?? 'Sent';
                                    $nfm_name = $nfm['name'] ?? 'flow';
                                    $message_body = [
                                        'interactiveResponseMessage' => [
                                            'body' => ['text' => $nfm_body],
                                            'nativeFlowResponseMessage' => [
                                                'paramsJson' => $response_json,
                                                'name' => $nfm_name,
                                            ]
                                        ]
                                    ];
                                    // Logar evento de flow inbound
                                    if (!empty($account)) {
                                        $this->log_flow_response_event($msg, $account, $from, $nfm);
                                    }
                                } else {
                                    // Outro tipo interativo — passar como texto
                                    $text_body = $interactive['body']['text'] ?? json_encode($interactive);
                                    $message_body = ['conversation' => $text_body];
                                }
                            } elseif ($type === 'image') {
                                $message_body = ['imageMessage' => [
                                    'url' => $msg['image']['id'] ?? '',
                                    'caption' => $msg['image']['caption'] ?? '',
                                    'mimetype' => $msg['image']['mime_type'] ?? 'image/jpeg',
                                ]];
                            } elseif ($type === 'video') {
                                $message_body = ['videoMessage' => [
                                    'url' => $msg['video']['id'] ?? '',
                                    'caption' => $msg['video']['caption'] ?? '',
                                    'mimetype' => $msg['video']['mime_type'] ?? 'video/mp4',
                                ]];
                            } elseif ($type === 'audio') {
                                $message_body = ['audioMessage' => [
                                    'url' => $msg['audio']['id'] ?? '',
                                    'mimetype' => $msg['audio']['mime_type'] ?? 'audio/ogg',
                                ]];
                            } elseif ($type === 'document') {
                                $message_body = ['documentMessage' => [
                                    'url' => $msg['document']['id'] ?? '',
                                    'title' => $msg['document']['filename'] ?? '',
                                    'mimetype' => $msg['document']['mime_type'] ?? '',
                                ]];
                            } elseif ($type === 'sticker') {
                                $message_body = ['stickerMessage' => [
                                    'url' => $msg['sticker']['id'] ?? '',
                                    'mimetype' => $msg['sticker']['mime_type'] ?? 'image/webp',
                                ]];
                            }
                            $bot_payload['data']['messages'][] = [
                                'key' => [
                                    'remoteJid' => $from . '@s.whatsapp.net',
                                    'fromMe' => false,
                                    'id' => $msg['id'] ?? '',
                                ],
                                'pushName' => $push_name,
                                'messageTimestamp' => (int)($msg['timestamp'] ?? time()),
                                'message' => $message_body,
                                'official_api' => true,
                                '_wa_id' => $from,
                            ];
                        }
                        // Chamada direta ao Bot_builder — sem cURL, sem deadlock PHP-FPM
                        try {
                            $bot_builder = new \Core\Bot_builder\Controllers\Bot_builder();
                            $bot_builder->process_webhook($bot_payload);
                            $log_entry .= "Bot_builder: process_webhook called directly (OK)\n";
                        } catch (\Throwable $e) {
                            $log_entry .= "Bot_builder: process_webhook ERROR: " . $e->getMessage() . "\n";
                        }
                    }
                } else {
                    // Reencaminhamento para plataformas filhas DESATIVADO.
                    // Motivo: causava loop infinito (zapmatic <-> astros <-> elite) que
                    // saturava o PHP-FPM. Cada servidor processa apenas seus próprios números.
                    $log_entry .= "No account found matching phone_number_id: $phone_number_id locally. Forwarding DISABLED (loop prevention).\n";
                }
            }

                            }
            }
            file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

            // Responder ao Meta DEPOIS de processar tudo (incluindo Bot_builder)
            // Nao usar fastcgi_finish_request() antes — causa erro ini_set do CI4
            if (function_exists('fastcgi_finish_request')) { echo 'OK'; fastcgi_finish_request(); }
            else echo 'OK';
            exit;
        }

        $log_entry .= "No verification or POST data found.\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        echo "Whatsapp Webhook Endpoint Active.";
        exit;
    }

    private function log_flow_response_event($msg, $account, $from, $nfm)
    {
        if (!defined('TB_WHATSAPP_FLOW_EVENTS') || !defined('TB_WHATSAPP_FLOWS')) {
            return;
        }

        try {
            $db = \Config\Database::connect();
            $team_id = (int) ($account->team_id ?? 0);
            $account_id = (int) ($account->id ?? 0);
            $flow_token = $nfm['response_json'] ?? '{}';
            $decoded = json_decode($flow_token, true);
            $token_from_response = $decoded['flow_token'] ?? '';

            $flow = null;
            if ($token_from_response !== '') {
                $flow = $db->table(TB_WHATSAPP_FLOWS)
                    ->where('team_id', $team_id)
                    ->where('account_id', $account_id)
                    ->where('channel', 'cloud_api')
                    ->orderBy('id', 'DESC')
                    ->get()->getRow();
            }

            if (empty($flow)) {
                $flow = $db->table(TB_WHATSAPP_FLOWS)
                    ->where('team_id', $team_id)
                    ->where('account_id', $account_id)
                    ->where('channel', 'cloud_api')
                    ->where('status_local !=', 'archived')
                    ->orderBy('id', 'DESC')
                    ->get()->getRow();
            }

            $db->table(TB_WHATSAPP_FLOW_EVENTS)->insert([
                'team_id' => $team_id,
                'flow_id' => !empty($flow) ? (int) $flow->id : null,
                'account_id' => $account_id,
                'account_ids' => (string) ($account->ids ?? ''),
                'instance_id' => (string) ($account->token ?? ''),
                'event_type' => 'flow_response',
                'direction' => 'inbound',
                'contact_id' => preg_replace('/[^0-9]/', '', (string) $from),
                'chat_id' => preg_replace('/[^0-9]/', '', (string) $from),
                'flow_token' => $token_from_response,
                'message_id' => $msg['id'] ?? '',
                'status' => 'received',
                'payload' => json_encode($nfm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'response' => $nfm['response_json'] ?? '{}',
                'created' => time(),
            ]);
        } catch (\Throwable $e) {
            @file_put_contents(WRITEPATH . 'logs/flow_response_error.log',
                date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n",
                FILE_APPEND
            );
        }
    }
}
