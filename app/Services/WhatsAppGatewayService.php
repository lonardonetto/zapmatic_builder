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

    private static function gatewayForInstance($instanceId): array
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

        $messageType = $type === 'text' ? 'text' : $type;
        $body = [
            'chat_id' => $chatId,
            'message_type' => $messageType,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $params = ['instance_id' => $instanceId];
        if ($access_token) {
            $params['access_token'] = $access_token;
        }

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

        $endpoint = '/send/text';
        if (in_array($type, ['image', 'audio', 'video', 'document'])) {
            $endpoint = '/send/media';
        }

        $headers = ['Content-Type: application/json'];
        if (!empty($gateway['api_key'])) $headers[] = 'X-Zapmatic-Gateway-Key: ' . $gateway['api_key'];

        $body = [
            'instance_id' => $instanceId,
            'chat_id' => $chatId,
            'type' => $type,
            'payload' => $payload,
        ];

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
