<?php
namespace Core\Whatsapp_profiles\Controllers;

/**
 * Cloud API Webhook Handler
 * 
 * Recebe webhooks da Meta Cloud API (verificação + mensagens)
 * e os transforma para o formato interno do Bot_builder.
 * 
 * GET  /cloud_webhook  → Verificação do webhook (hub_challenge)
 * POST /cloud_webhook  → Mensagens recebidas
 */
class Cloud_webhook extends \CodeIgniter\Controller
{
    /**
     * GET - Meta Cloud API webhook verification
     * A Meta envia GET com hub_mode=subscribe, hub_verify_token, hub_challenge
     * Precisa retornar o hub_challenge se o token bater
     */
    public function index()
    {
        $logFile = WRITEPATH . 'cloud_webhook.log';

        $mode      = $this->request->getGet('hub_mode');
        $token     = $this->request->getGet('hub_verify_token');
        $challenge = $this->request->getGet('hub_challenge');

        file_put_contents($logFile, date('Y-m-d:H:i:s') . " | VERIFY | mode={$mode} token={$token} challenge={$challenge}\n", FILE_APPEND);

        if ($mode !== 'subscribe') {
            return $this->response->setStatusCode(403)->setBody('Invalid mode');
        }

        // Procurar verify_token em todas as contas Cloud API
        $db = \Config\Database::connect();
        $account = $db->table('sp_accounts')
            ->where('login_type', 1)
            ->where('status', 1)
            ->get()
            ->getResult();

        foreach ($account as $acc) {
            $data = json_decode($acc->data, true) ?: [];
            $verify_token = $data['verify_token'] ?? '';
            if ($verify_token !== '' && $token === $verify_token) {
                file_put_contents($logFile, date('Y-m-d:H:i:s') . " | VERIFY OK | matched account={$acc->token}\n", FILE_APPEND);
                return $this->response->setBody($challenge);
            }
        }

        file_put_contents($logFile, date('Y-m-d:H:i:s') . " | VERIFY FAILED | token not matched\n", FILE_APPEND);
        return $this->response->setStatusCode(403)->setBody('Token mismatch');
    }

    /**
     * POST - Receber mensagens da Meta Cloud API
     * Transforma o formato Meta para o formato interno e reprocessa
     */
    public function receive()
    {
        $logFile = WRITEPATH . 'cloud_webhook.log';
        $raw = file_get_contents('php://input');

        file_put_contents($logFile, date('Y-m-d:H:i:s') . " | RECEIVE | " . substr($raw, 0, 500) . "\n", FILE_APPEND);

        $payload = json_decode($raw, true);
        if (!$payload) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid JSON']);
        }

        // Meta envia: { object: "whatsapp_business_account", entry: [{ changes: [{ value: { messages: [...] } }] }] }
        $entry = $payload['entry'][0] ?? null;
        if (!$entry) {
            return $this->response->setJSON(['status' => 'ok', 'message' => 'No entry']);
        }

        $changes = $entry['changes'][0] ?? null;
        if (!$changes) {
            return $this->response->setJSON(['status' => 'ok', 'message' => 'No changes']);
        }

        $value = $changes['value'] ?? [];
        $metadata = $value['metadata'] ?? [];
        $phone_number_id = $metadata['phone_number_id'] ?? '';

        if (empty($phone_number_id)) {
            return $this->response->setJSON(['status' => 'ok', 'message' => 'No phone_number_id']);
        }

        // Resolver phone_number_id para instance_id (token)
        $db = \Config\Database::connect();
        $cloud_config = $db->table('sp_whatsapp_cloud_api_config')
            ->where('phone_number_id', $phone_number_id)
            ->get()
            ->getRowArray();

        if (!$cloud_config) {
            file_put_contents($logFile, date('Y-m-d:H:i:s') . " | NO CONFIG for phone_number_id={$phone_number_id}\n", FILE_APPEND);
            return $this->response->setJSON(['status' => 'error', 'message' => 'Cloud API config not found']);
        }

        $instance_id = $cloud_config['instance_id'];

        // Processar status updates (entregue, lido, etc)
        $statuses = $value['statuses'] ?? [];
        if (!empty($statuses)) {
            $this->processStatuses($statuses, $instance_id);
            return $this->response->setJSON(['status' => 'ok', 'message' => 'Status processed']);
        }

        // Processar mensagens recebidas
        $meta_messages = $value['messages'] ?? [];
        if (empty($meta_messages)) {
            return $this->response->setJSON(['status' => 'ok', 'message' => 'No messages in payload']);
        }

        // Transformar formato Meta → formato interno
        $contacts = $value['contacts'] ?? [];
        $transformed = $this->transformToInternal($meta_messages, $contacts, $instance_id, $metadata);

        if (!$transformed) {
            return $this->response->setJSON(['status' => 'ok', 'message' => 'No messages to process']);
        }

        // Enviar formato transformado para o Bot_builder webhook via POST interno
        $webhook_url = base_url('bot-builder/webhook');
        $ch = curl_init($webhook_url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($transformed),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $webhook_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        file_put_contents($logFile, date('Y-m-d:H:i:s') . " | BOT_BUILDER webhook response: HTTP {$http_code} | " . substr($webhook_response, 0, 200) . "\n", FILE_APPEND);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Cloud API message forwarded to Bot_builder',
            'http_code' => $http_code,
        ]);
    }

    /**
     * Transformar formato Meta Cloud API → formato interno
     */
    private function transformToInternal(array $messages, array $contacts, string $instance_id, array $metadata): ?array
    {
        $internal_messages = [];

        foreach ($messages as $msg) {
            $from = $msg['from'] ?? '';
            $msg_id = $msg['id'] ?? '';
            $timestamp = (int)($msg['timestamp'] ?? time());
            $type = $msg['type'] ?? 'text';
            $push_name = '';

            // Buscar nome do contato
            foreach ($contacts as $c) {
                if (($c['wa_id'] ?? '') === $from) {
                    $push_name = $c['profile']['name'] ?? '';
                    break;
                }
            }

            // Transformar conteúdo baseado no tipo
            $message_body = [];
            switch ($type) {
                case 'text':
                    $message_body = ['conversation' => $msg['text']['body'] ?? ''];
                    break;
                case 'image':
                    $message_body = ['imageMessage' => ['url' => $msg['image']['link'] ?? '']];
                    break;
                case 'video':
                    $message_body = ['videoMessage' => ['url' => $msg['video']['link'] ?? '']];
                    break;
                case 'audio':
                    $message_body = ['audioMessage' => ['url' => $msg['audio']['link'] ?? '']];
                    break;
                case 'document':
                    $message_body = ['documentMessage' => [
                        'url' => $msg['document']['link'] ?? '',
                        'fileName' => $msg['document']['caption'] ?? 'file'
                    ]];
                    break;
                case 'sticker':
                    $message_body = ['stickerMessage' => ['url' => $msg['sticker']['link'] ?? '']];
                    break;
                case 'interactive':
                    // Botões e listas
                    $interactive = $msg['interactive'] ?? [];
                    if ($interactive['type'] === 'button_reply') {
                        $message_body = ['buttonsResponseMessage' => [
                            'selectedButtonId' => $interactive['button_reply']['id'] ?? ''
                        ]];
                    } elseif ($interactive['type'] === 'list_reply') {
                        $message_body = ['listResponseMessage' => [
                            'singleSelectReply' => ['selectedRowId' => $interactive['list_reply']['id'] ?? '']
                        ]];
                    }
                    break;
                case 'location':
                    $message_body = ['locationMessage' => [
                        'degreesLatitude' => $msg['location']['latitude'] ?? 0,
                        'degreesLongitude' => $msg['location']['longitude'] ?? 0,
                    ]];
                    break;
                case 'reaction':
                    // Reações - skip por enquanto
                    continue 2;
                case 'contacts':
                    $message_body = ['contactMessage' => ['displayName' => $msg['contacts'][0]['name']['formatted_name'] ?? '']];
                    break;
                default:
                    $message_body = ['conversation' => ''];
                    break;
            }

            // Montar no formato interno que o Bot_builder espera
            $jid = $from . '@s.whatsapp.net';

            $internal_messages[] = [
                'key' => [
                    'remoteJid' => $jid,
                    'fromMe' => false,
                    'id' => $msg_id,
                ],
                'pushName' => $push_name,
                'messageTimestamp' => $timestamp,
                'message' => $message_body,
                'official_api' => true,
                '_wa_id' => $from,
            ];
        }

        if (empty($internal_messages)) {
            return null;
        }

        return [
            'instance_id' => $instance_id,
            'data' => [
                'messages' => $internal_messages,
            ],
        ];
    }

    /**
     * Processar updates de status (entregue, lido, etc)
     */
    private function processStatuses(array $statuses, string $instance_id)
    {
        $logFile = WRITEPATH . 'cloud_webhook.log';

        foreach ($statuses as $status) {
            $msg_id = $status['id'] ?? '';
            $status_type = $status['status'] ?? '';
            $timestamp = $status['timestamp'] ?? time();

            if (empty($msg_id)) continue;

            file_put_contents($logFile, date('Y-m-d:H:i:s') . " | STATUS | id={$msg_id} status={$status_type}\n", FILE_APPEND);

            $db = \Config\Database::connect();
            $db->table('sp_whatsapp_messages')
                ->where('meta_message_id', $msg_id)
                ->update(['status' => $status_type, 'changed' => $timestamp]);
        }
    }
}
