<?php
namespace App\Services;

class WhatsAppGatewayService
{
    public static function send($instanceId, string $chatId, string $type, array $payload): array
    {
        self::ensureTables();
        $gateway = self::gatewayForInstance($instanceId);

        if (($gateway['provider'] ?? 'baileys') === 'whatsmeow') {
            return self::sendViaWhatsmeow($gateway, $instanceId, $chatId, $type, $payload);
        }

        return self::sendViaBaileys($instanceId, $chatId, $type, $payload);
    }

    public static function register($instanceId, string $baseUrl, ?string $apiKey = null, ?int $teamId = null): array
    {
        self::ensureTables();
        $db = \Config\Database::connect();

        $existing = $db->table('sp_whatsapp_gateways')
            ->where('instance_id', $instanceId)
            ->get()
            ->getRowArray();

        $data = [
            'instance_id' => $instanceId,
            'provider' => 'whatsmeow',
            'base_url' => $baseUrl,
            'api_key' => $apiKey ?? '',
            'status' => 1,
            'capabilities_json' => null,
            'changed' => time(),
        ];

        if ($teamId) {
            $data['team_id'] = $teamId;
        }

        if (empty($data['created'])) {
            $data['created'] = time();
        }

        if ($existing) {
            $db->table('sp_whatsapp_gateways')
                ->where('id', $existing['id'])
                ->update($data);
            return ['status' => 'success', 'message' => 'Gateway atualizado para whatsmeow'];
        }

        $data['created'] = time();
        $db->table('sp_whatsapp_gateways')->insert($data);
        return ['status' => 'success', 'message' => 'Gateway whatsmeow registrado'];
    }

    public static function qr($instanceId): array
    {
        $gateway = self::gatewayForInstance($instanceId);
        if ($gateway['provider'] !== 'whatsmeow') {
            return ['status' => 'error', 'message' => 'Instancia nao usa whatsmeow'];
        }

        $baseUrl = rtrim($gateway['base_url'] ?? '', '/');
        $url = $baseUrl . '/qrcode?instance_id=' . urlencode($instanceId);
        if (!empty($gateway['api_key'])) {
            $url .= '&api_key=' . urlencode($gateway['api_key']);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['status' => 'error', 'message' => $error];
        return json_decode($response, true) ?: ['status' => 'error', 'message' => 'Resposta invalida do gateway'];
    }

    public static function status($instanceId): array
    {
        $gateway = self::gatewayForInstance($instanceId);
        if ($gateway['provider'] !== 'whatsmeow') {
            return ['status' => 'error', 'message' => 'Instancia nao usa whatsmeow'];
        }

        $baseUrl = rtrim($gateway['base_url'] ?? '', '/');
        $url = $baseUrl . '/status?instance_id=' . urlencode($instanceId);
        if (!empty($gateway['api_key'])) {
            $url .= '&api_key=' . urlencode($gateway['api_key']);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['status' => 'error', 'message' => $error];
        return json_decode($response, true) ?: ['status' => 'error', 'message' => 'Resposta invalida do gateway'];
    }

    public static function logout($instanceId): array
    {
        $gateway = self::gatewayForInstance($instanceId);
        if ($gateway['provider'] !== 'whatsmeow') {
            return ['status' => 'error', 'message' => 'Instancia nao usa whatsmeow'];
        }

        $baseUrl = rtrim($gateway['base_url'] ?? '', '/');
        $url = $baseUrl . '/logout';
        $headers = ['Content-Type: application/json'];
        if (!empty($gateway['api_key'])) {
            $headers[] = 'X-Zapmatic-Gateway-Key: ' . $gateway['api_key'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['instance_id' => $instanceId]),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['status' => 'error', 'message' => $error];

        self::unregisterGateway($instanceId);

        return json_decode($response, true) ?: ['status' => 'success'];
    }

    public static function unregisterGateway($instanceId): void
    {
        $db = \Config\Database::connect();
        $db->table('sp_whatsapp_gateways')
            ->where('instance_id', $instanceId)
            ->delete();
    }

    public static function capabilities($instanceId): array
    {
        self::ensureTables();
        $gateway = self::gatewayForInstance($instanceId);

        if ($gateway['provider'] === 'whatsmeow' && !empty($gateway['base_url'])) {
            $baseUrl = rtrim($gateway['base_url'], '/');
            $ch = curl_init($baseUrl . '/capabilities');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            if ($response) {
                $caps = json_decode($response, true);
                if (is_array($caps)) return $caps;
            }
        }

        return [
            'text' => true,
            'image' => true,
            'audio' => true,
            'document' => true,
            'buttons' => true,
            'list' => true,
            'carousel' => true,
            'presence' => true,
            'groups' => true,
        ];
    }

    public static function ensureTables(): void
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('sp_whatsapp_gateways')) return;

        $forge = \Config\Database::forge();
        $forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'team_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'instance_id' => ['type' => 'VARCHAR', 'constraint' => 100],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'baileys'],
            'base_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'api_key' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'INT', 'constraint' => 1, 'default' => 1],
            'capabilities_json' => ['type' => 'TEXT', 'null' => true],
            'created' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'changed' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);
        $forge->addPrimaryKey('id');
        $forge->addKey('instance_id');
        $forge->createTable('sp_whatsapp_gateways', true);
    }

    public static function gatewayForInstance($instanceId): array
    {
        $row = \Config\Database::connect()
            ->table('sp_whatsapp_gateways')
            ->where('instance_id', (string)$instanceId)
            ->where('status', 1)
            ->get()
            ->getRowArray();

        return $row ?: ['provider' => 'baileys'];
    }

    private static function sendViaBaileys($instanceId, string $chatId, string $type, array $payload): array
    {
        $access_token = self::resolveAccessToken($instanceId);
        if (!$access_token) {
            return ['status' => 'error', 'provider' => 'baileys', 'message' => 'Access token not found'];
        }

        $params = [
            'instance_id' => $instanceId,
            'access_token' => $access_token,
        ];

        // Botões, lista, carrossel, poll usam direct_send_message com type numérico
        $typeMap = ['buttons' => 2, 'list' => 3, 'carousel' => 5, 'poll' => 6];
        if (!empty($payload['_template_id']) && isset($typeMap[$type])) {
            $params['type'] = $typeMap[$type];
            $body = [
                'chat_id' => $chatId,
                'type' => $typeMap[$type],
                'template' => (int)$payload['_template_id'],
            ];
            $response = wa_post_curl('direct_send_message', $params, $body);
            $decoded = is_string($response) ? json_decode($response, true) : json_decode(json_encode($response), true);
            return is_array($decoded)
                ? $decoded + ['provider' => 'baileys']
                : ['status' => 'success', 'provider' => 'baileys', 'raw' => $response];
        }

        // Áudio usa direct_send_message type=1
        if ($type === 'audio' && !empty($payload['url'])) {
            $params['type'] = 1;
            $body = [
                'chat_id' => $chatId,
                'type' => 1,
                'caption' => $payload['caption'] ?? '',
                'media_url' => $payload['url'],
            ];
            $response = wa_post_curl('direct_send_message', $params, $body);
            $decoded = is_string($response) ? json_decode($response, true) : json_decode(json_encode($response), true);
            return is_array($decoded)
                ? $decoded + ['provider' => 'baileys']
                : ['status' => 'success', 'provider' => 'baileys', 'raw' => $response];
        }

        // Texto e outros usam bot_builder_send
        $messageType = $type === 'text' ? 'text' : $type;
        $body = [
            'chat_id' => $chatId,
            'message_type' => $messageType,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        $response = wa_post_curl('bot_builder_send', $params, $body);
        $decoded = is_string($response) ? json_decode($response, true) : json_decode(json_encode($response), true);

        return is_array($decoded)
            ? $decoded + ['provider' => 'baileys']
            : ['status' => 'success', 'provider' => 'baileys', 'raw' => $response];
    }

    private static function resolveAccessToken($instanceId): ?string
    {
        $db = \Config\Database::connect();
        $account = $db->table('sp_accounts')->where('token', $instanceId)->get()->getRow();
        if (!$account) return null;
        $team = $db->table('sp_team')->where('id', $account->team_id)->get()->getRow();
        return $team ? $team->ids : null;
    }

    private static function sendViaWhatsmeow(array $gateway, $instanceId, string $chatId, string $type, array $payload): array
    {
        $baseUrl = rtrim($gateway['base_url'] ?? '', '/');
        if ($baseUrl === '') {
            return ['status' => 'error', 'provider' => 'whatsmeow', 'message' => 'Gateway Whatsmeow sem base_url.'];
        }

        $headers = ['Content-Type: application/json'];
        if (!empty($gateway['api_key'])) $headers[] = 'X-Zapmatic-Gateway-Key: ' . $gateway['api_key'];

        // Presença digitando (composing) antes de enviar
        $presenceTime = isset($payload['presenceTime']) ? (int)$payload['presenceTime'] : 2;
        $presenceType = isset($payload['presenceType']) ? $payload['presenceType'] : 'composing';
        if ($presenceTime > 0) {
            $presenceBody = [
                'instance_id' => $instanceId,
                'chat_id' => $chatId,
                'presence' => $presenceType,
                'duration' => $presenceTime,
            ];
            $chP = curl_init($baseUrl . '/send/presence');
            curl_setopt_array($chP, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($presenceBody),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 5,
            ]);
            curl_exec($chP);
            curl_close($chP);
        }

        // Identifica endpoint base
        $endpoint = '/send/text';
        $body = [
            'instance_id' => $instanceId,
            'chat_id' => $chatId,
            'type' => $type,
        ];

        if (in_array($type, ['image', 'audio', 'video', 'document'])) {
            $endpoint = '/send/media';
            $body['payload'] = $payload;
        } elseif (in_array($type, ['buttons', 'list', 'poll', 'carousel'])) {
            // Consulta o template no banco
            $templateId = $payload['_template_id'] ?? $payload['template'] ?? 0;
            if (!$templateId) {
                return ['status' => 'error', 'message' => 'ID do template ausente'];
            }
            $db = \Config\Database::connect();
            $template = $db->table('sp_whatsapp_template')->where('id', $templateId)->get()->getRow();
            if (!$template) {
                return ['status' => 'error', 'message' => 'Template não encontrado no banco'];
            }

            $tData = json_decode($template->data, true) ?: [];
            $body['body'] = $tData['text'] ?? $tData['caption'] ?? $payload['text'] ?? 'Escolha uma opção';
            $body['title'] = $tData['title'] ?? '';
            $body['footer'] = $tData['footer'] ?? '';

            if ($type === 'buttons' || $type === 'carousel') {
                $endpoint = '/send/buttons';
                $buttons = [];
                $sourceBtns = $tData['interactiveButtons'] ?? $tData['templateButtons'] ?? $tData['buttons'] ?? [];
                foreach ($sourceBtns as $i => $btn) {
                    $bInfo = is_array($btn) && isset($btn['button']) ? $btn['button'] : $btn;
                    $name = $bInfo['name'] ?? 'quick_reply';
                    $btnParams = is_string($bInfo['buttonParamsJson'] ?? '') ? json_decode($bInfo['buttonParamsJson'], true) : ($bInfo['buttonParamsJson'] ?? []);
                    
                    $btnData = [
                        'id' => $btnParams['id'] ?? $btnParams['buttonId'] ?? "btn_" . ($i+1),
                        'text' => $btnParams['display_text'] ?? $btnParams['displayText'] ?? "Opção " . ($i+1),
                        'type' => $name == 'cta_url' ? 'url' : ($name == 'cta_call' ? 'call' : 'reply')
                    ];
                    if ($btnData['type'] === 'url') $btnData['url'] = $btnParams['url'] ?? $btnParams['merchant_url'] ?? '';
                    if ($btnData['type'] === 'call') $btnData['phone_number'] = $btnParams['phone_number'] ?? '';
                    $buttons[] = $btnData;
                }
                $body['buttons'] = $buttons;
            } elseif ($type === 'list') {
                $endpoint = '/send/list';
                $body['button_text'] = $tData['buttonText'] ?? 'Opções';
                $sections = [];
                foreach ($tData['sections'] ?? [] as $sec) {
                    $rows = [];
                    foreach ($sec['rows'] ?? [] as $r) {
                        $rows[] = [
                            'id' => $r['rowId'] ?? $r['id'] ?? uniqid(),
                            'title' => $r['title'] ?? '',
                            'description' => $r['description'] ?? ''
                        ];
                    }
                    $sections[] = ['title' => $sec['title'] ?? '', 'rows' => $rows];
                }
                $body['sections'] = $sections;
            } elseif ($type === 'poll') {
                $endpoint = '/send/poll';
                $options = [];
                foreach ($tData['options'] ?? [] as $opt) {
                    $options[] = ['name' => $opt['optionName'] ?? $opt['name'] ?? ''];
                }
                $body['options'] = $options;
            }
        } else {
            // Text normal
            $body['payload'] = $payload;
        }

        $ch = curl_init($baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) return ['status' => 'error', 'provider' => 'whatsmeow', 'message' => $error];

        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            return $decoded + ['provider' => 'whatsmeow'];
        }

        return [
            'status' => $httpCode === 200 ? 'success' : 'error',
            'provider' => 'whatsmeow',
            'raw' => $response,
        ];
    }
}
