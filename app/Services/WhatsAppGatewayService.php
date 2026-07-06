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

        $params = ['instance_id' => $instanceId];
        if ($access_token) $params['access_token'] = $access_token;

        $body = [
            'chat_id' => $chatId,
            'message_type' => $type === 'text' ? 'text' : $type,
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

        $presenceTime = isset($payload['presenceTime']) ? (int)$payload['presenceTime'] : 2;
        $presenceType = isset($payload['presenceType']) ? $payload['presenceType'] : 'composing';
        if ($presenceTime > 0 && $type === 'text') {
            $presenceBody = [
                'instance_id' => $instanceId,
                'chat_id' => $chatId,
                'presence' => $presenceType,
                'duration' => $presenceTime,
            ];
            $chP = curl_init($baseUrl . '/send/presence');
            curl_setopt_array($chP, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($presenceBody),
                CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 5,
            ]);
            curl_exec($chP);
            curl_close($chP);
        }

        // Roteia conforme tipo
        $endpoint = '/send/text';
        $body = [
            'instance_id' => $instanceId,
            'chat_id' => $chatId,
            'type' => $type,
        ];

        if (in_array($type, ['image', 'audio', 'video', 'document'])) {
            $endpoint = '/send/media';
            $body['payload'] = $payload;
        } elseif ($type === 'carousel') {
            $templateId = $payload['_template_id'] ?? $payload['template'] ?? 0;
            if (!$templateId && empty($payload['cards'])) {
                return ['status' => 'error', 'provider' => 'whatsmeow', 'message' => 'ID do template ou cards ausente'];
            }
            $endpoint = '/send/carousel';
            if (!empty($payload['cards'])) {
                $body['body'] = $payload['body'] ?? $payload['text'] ?? 'Escolha';
                $body['title'] = $payload['title'] ?? '';
                $body['footer'] = $payload['footer'] ?? '';
                $sourceCards = $payload['cards'];
            } else {
                $db = \Config\Database::connect();
                $tpl = $db->table('sp_whatsapp_template')->where('id', $templateId)->get()->getRowArray();
                if (!$tpl) {
                    return ['status' => 'error', 'provider' => 'whatsmeow', 'message' => 'Template não encontrado'];
                }
                $tData = json_decode($tpl['data'], true) ?: [];
                $body['body'] = $tData['text'] ?? $tData['caption'] ?? 'Escolha';
                $body['title'] = $tData['title'] ?? '';
                $body['footer'] = $tData['footer'] ?? '';
                $sourceCards = $tData['cards'] ?? [];
            }
            $cards = [];
            foreach ($sourceCards as $i => $card) {
                $imgUrl = '';
                if (is_string($card['media'] ?? null)) $imgUrl = $card['media'];
                elseif (isset($card['media']['url'])) $imgUrl = $card['media']['url'];
                elseif (is_string($card['image'] ?? null)) $imgUrl = $card['image'];
                elseif (isset($card['image']['url'])) $imgUrl = $card['image']['url'];

                $btns = [];
                foreach ($card['buttons'] ?? [] as $j => $btn) {
                    $b = is_array($btn) && isset($btn['button']) ? $btn['button'] : $btn;
                    $qr = $b['quickReplyButton'] ?? [];
                    $id = $qr['id'] ?? $b['id'] ?? "btn_{$i}_{$j}";
                    $text = $qr['displayText'] ?? $qr['display_text'] ?? $b['display_text'] ?? $b['text'] ?? "Opção";
                    $btns[] = ['id' => $id, 'text' => $text, 'type' => 'reply'];
                }

                $cards[] = [
                    'title' => substr($card['title'] ?? "Card " . ($i+1), 0, 60),
                    'body' => substr($card['body'] ?? $card['description'] ?? ' ', 0, 1024),
                    'footer' => substr($card['footer'] ?? ' ', 0, 60),
                    'image' => $imgUrl ? ['url' => $imgUrl] : null,
                    'buttons' => array_slice($btns, 0, 3)
                ];
            }
            $body['cards'] = array_slice($cards, 0, 10);
        } elseif ($type === 'buttons') {
            $templateId = $payload['_template_id'] ?? $payload['template'] ?? 0;
            $isInline = !empty($payload['buttons']);
            if (!$templateId && !$isInline) {
                return ['status' => 'error', 'provider' => 'whatsmeow', 'message' => 'ID do template ausente'];
            }
            $endpoint = '/send/buttons';
            if ($isInline) {
                $body['body'] = $payload['body'] ?? $payload['text'] ?? 'Escolha';
                $body['title'] = $payload['title'] ?? '';
                $body['footer'] = $payload['footer'] ?? '';
            } else {
                $db = \Config\Database::connect();
                $tpl = $db->table('sp_whatsapp_template')->where('id', $templateId)->get()->getRowArray();
                if (!$tpl) {
                    return ['status' => 'error', 'provider' => 'whatsmeow', 'message' => 'Template não encontrado'];
                }
                $tData = json_decode($tpl['data'], true) ?: [];
                $body['body'] = $tData['text'] ?? $tData['caption'] ?? 'Escolha';
                $body['title'] = $tData['title'] ?? '';
                $body['footer'] = $tData['footer'] ?? '';
            }
            $buttons = [];
            if ($isInline) {
                $source = $payload['buttons'] ?? [];
            } else {
                $source = $tData['interactiveButtons'] ?? $tData['templateButtons'] ?? $tData['buttons'] ?? [];
            }
            foreach ($source as $i => $btn) {
                $b = is_array($btn) && isset($btn['button']) ? $btn['button'] : $btn;
                $qr = $b['quickReplyButton'] ?? [];
                $id = $qr['id'] ?? $b['id'] ?? "btn_$i";
                $text = $qr['displayText'] ?? $qr['display_text'] ?? $b['display_text'] ?? $b['text'] ?? "Opção " . ($i+1);
                $buttons[] = ['id' => $id, 'text' => $text, 'type' => 'reply'];
            }
            $body['buttons'] = $buttons;

        } elseif ($type === 'list') {
            $templateId = $payload['_template_id'] ?? $payload['template'] ?? 0;
            $isInline = !empty($payload['sections']);
            if (!$templateId && !$isInline) {
                return ['status' => 'error', 'provider' => 'whatsmeow', 'message' => 'ID do template ausente'];
            }
            $endpoint = '/send/list';
            
            if ($isInline) {
                $body['body'] = $payload['body'] ?? $payload['text'] ?? 'Selecione';
                $body['title'] = $payload['title'] ?? '';
                $body['footer'] = $payload['footer'] ?? '';
                $body['button_text'] = $payload['buttonText'] ?? $payload['button_text'] ?? 'Opções';
                $sourceSections = $payload['sections'];
            } else {
                $db = \Config\Database::connect();
                $tpl = $db->table('sp_whatsapp_template')->where('id', $templateId)->get()->getRowArray();
                if (!$tpl) {
                    return ['status' => 'error', 'provider' => 'whatsmeow', 'message' => 'Template não encontrado'];
                }
                $tData = json_decode($tpl['data'], true) ?: [];
                $body['body'] = $tData['text'] ?? 'Selecione';
                $body['title'] = $tData['title'] ?? '';
                $body['footer'] = $tData['footer'] ?? '';
                $body['button_text'] = $tData['buttonText'] ?? 'Opções';
                $sourceSections = $tData['sections'] ?? [];
            }
            
            $sections = [];
            foreach ($sourceSections as $sec) {
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
        } else {
            // Texto normal
            $body['payload'] = $payload;
        }

        $finalUrl = $baseUrl . $endpoint;
        $payload2 = json_encode($body);
        file_put_contents('/tmp/single_button_debug.log', date('Y-m-d H:i:s') . " URL=$finalUrl body=$payload2\n", FILE_APPEND);
        $ch = curl_init($finalUrl);
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

        file_put_contents('/tmp/single_button_debug.log', date('Y-m-d H:i:s') . " RESP http=$httpCode resp=" . substr($response ?: '', 0, 300) . " error=" . ($error ?: '') . "\n", FILE_APPEND);
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
