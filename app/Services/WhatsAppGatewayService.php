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

    public static function capabilities($instanceId): array
    {
        self::ensureTables();
        $gateway = self::gatewayForInstance($instanceId);
        if (!empty($gateway['capabilities_json'])) {
            $capabilities = json_decode($gateway['capabilities_json'], true);
            if (is_array($capabilities)) return $capabilities;
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

        $endpoint = $type === 'text' ? '/send/text' : '/send/media';
        $headers = ['Content-Type: application/json'];
        if (!empty($gateway['api_key'])) $headers[] = 'X-Zapmatic-Gateway-Key: ' . $gateway['api_key'];

        $ch = curl_init($baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'instance_id' => $instanceId,
                'chat_id' => $chatId,
                'type' => $type,
                'payload' => $payload,
            ]),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['status' => 'error', 'provider' => 'whatsmeow', 'message' => $error];
        $decoded = json_decode($response, true);
        return is_array($decoded)
            ? $decoded + ['provider' => 'whatsmeow']
            : ['status' => 'success', 'provider' => 'whatsmeow', 'raw' => $response];
    }
}
