<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * CloudCampaignWorker
 * ------------------
 * Dispatcher sequencial para campanhas em massa (sp_whatsapp_schedules) cujas
 * contas selecionadas sejam Cloud API (sp_accounts.login_type = 1).
 *
 * Por que existe:
 *   - O Go processor (internal/bulk/processor.go) deliberadamente PULA campanhas
 *     cujo provider nao e "whatsmeow" (linha 164: "Skip campaigns that don't use
 *     whatsmeow — let Node.js/PHP handle them").
 *   - O Node.js que cuidava de Cloud API (app_zapmatic_api/) foi removido na
 *     migracao assincrona (commit 7ffefe8d).
 *   - A tabela sp_campaign_queue nunca e alimentada por nenhum produtor.
 * Resultado: campanha Cloud API ficava com status=1 e nunca enviava.
 *
 * Este worker fecha essa lacuna APENAS para Cloud API, usando o mesmo modelo de
 * lock otimista (campo `run`) e o mesmo offset persistente (sent+failed) do Go,
 * portanto NAO disputa com o Go: campanhas Whatsmeow continuam sendo processadas
 * exclusivamente pelo Go.
 */
class CloudCampaignWorker extends BaseCommand
{
    protected $group       = 'Campaigns';
    protected $name        = 'cloud:campaigns';
    protected $description = 'Processa campanhas em massa Cloud API (login_type=1) que o Go propositalmente pula.';

    private const TB_SCHEDULES     = 'sp_whatsapp_schedules';
    private const TB_ACCOUNTS      = 'sp_accounts';
    private const TB_PHONE_NUMBERS = 'sp_whatsapp_phone_numbers';
    private const TB_TEMPLATE      = 'sp_whatsapp_template';
    private const TB_MSG_STATUS    = 'sp_whatsapp_message_status';
    private const TB_DISPATCHES    = 'sp_whatsapp_cloud_dispatches';

    private const LOCK_SECONDS = 300;

    private $db;

    public function run(array $params)
    {
        // Helpers globais NAO sao carregados automaticamente no contexto CLI (spark).
        $this->bootstrapHelpers();

        $this->db = \Config\Database::connect();
        if (method_exists($this->db, 'disableQueryCache')) {
            $this->db->disableQueryCache();
        }

        CLI::write('[CloudCampaignWorker] Starting (Cloud API dispatcher)...', 'green');

        while (true) {
            $start = microtime(true);
            try {
                $this->db->reconnect();
                $this->processDue();
            } catch (\Throwable $e) {
                $this->log('Erro no ciclo: ' . $e->getMessage());
            }

            gc_collect_cycles();
            $elapsed = (microtime(true) - $start) * 1000000;
            usleep(max(100000, 3000000 - (int) $elapsed));
        }
    }

    private function bootstrapHelpers(): void
    {
        $common = FCPATH . 'app/Helpers/Common_helper.php';
        if (is_file($common) && !function_exists('db_get')) {
            require_once $common;
        }

        $waHelper = ROOTPATH . 'inc/core/Whatsapp/Helpers/Whatsapp_helper.php';
        if (is_file($waHelper) && !function_exists('send_cloud_message')) {
            require_once $waHelper;
        }

        $constants = ROOTPATH . 'inc/core/Whatsapp/Config/Constants.php';
        if (is_file($constants) && !defined('TB_WHATSAPP_SCHEDULES')) {
            require_once $constants;
        }
    }

    private function processDue(): void
    {
        $now = time();

        $rows = $this->db->table(self::TB_SCHEDULES)
            ->select('*')
            ->where('status', 1)
            ->where('run <=', $now)
            ->where('accounts !=', '')
            ->where('time_post <=', $now)
            ->orderBy('time_post', 'ASC')
            ->limit(50)
            ->get()->getResult();

        if (empty($rows)) {
            return;
        }

        foreach ($rows as $item) {
            try {
                $this->processCampaign($item, $now);
            } catch (\Throwable $e) {
                $this->log("Campanha #{$item->id} erro: " . $e->getMessage());
                // Garante que nao fique presa em run>0 em caso de excecao.
                $this->db->table(self::TB_SCHEDULES)->where('id', (int) $item->id)->update(['run' => 0]);
            }
        }
    }

    private function processCampaign($item, int $now): void
    {
        $accounts = $this->decodeAccounts($item->accounts ?? '');
        if (empty($accounts)) {
            return;
        }

        // So processamos aqui campanhas 100% Cloud API. Se houver qualquer conta
        // Whatsmeow/Baileys, deixamos o Go seguir cuidando (comportamento atual).
        if (!$this->isAllCloud($accounts)) {
            return;
        }

        // Lock otimista identico ao Go (LockCampaign).
        $builder = $this->db->table(self::TB_SCHEDULES);
        $builder->where('id', (int) $item->id)
            ->where('status', 1)
            ->where('run <=', $now)
            ->update(['run' => $now + self::LOCK_SECONDS]);
        if ($this->db->affectedRows() === 0) {
            return; // ja bloqueada por outro worker
        }

        // Janela de agendamento (dias/horarios/feriados)
        if (!$this->isWithinWindow($item, $now)) {
            // Reagenda para nao entrar em loop apertado (Go calcula o proximo slot;
            // aqui usamos uma janela segura de 60s).
            $this->db->table(self::TB_SCHEDULES)->where('id', (int) $item->id)
                ->update(['run' => 0, 'time_post' => $now + 60]);
            return;
        }

        $offset = (int) ($item->sent ?? 0) + (int) ($item->failed ?? 0);
        $phone = $this->getPhoneAtOffset((int) $item->contact_id, $offset);
        if ($phone === null) {
            // Sem mais contatos -> concluir.
            $this->db->table(self::TB_SCHEDULES)->where('id', (int) $item->id)
                ->update(['status' => 2, 'run' => 0]);
            CLI::write("[CloudCampaignWorker] Campanha #{$item->id} concluida (sent={$item->sent} failed={$item->failed})", 'green');
            return;
        }

        $account = $this->resolveAccount($accounts, (int) ($item->next_account ?? 0));
        if ($account === null) {
            // Nenhuma conta Cloud conectada agora -> tenta de novo em 30s.
            $this->db->table(self::TB_SCHEDULES)->where('id', (int) $item->id)
                ->update(['run' => 0, 'time_post' => $now + 30]);
            return;
        }

        $to = (string) ($phone->phone ?? '');
        $result = $this->sendMessage($item, $account, $to, $phone);

        $success = ($result['status'] === 'success');
        $message = $result['message'] ?? ($success ? 'Enviado' : 'Falha');
        $errorCode = $result['error_code'] ?? null;
        $waMessageId = $result['wa_message_id'] ?? null;

        // Atualiza result (relatorio .xls), contadores, time_post, next_account e run.
        $this->applyResult($item, $account, $phone, $to, $success, $message, $errorCode, $waMessageId, $now);

        $delay = $this->nextDelay($item);
        $nextTime = $now + $delay;

        $this->db->table(self::TB_SCHEDULES)->where('id', (int) $item->id)->update([
            'sent'         => (int) ($item->sent ?? 0) + ($success ? 1 : 0),
            'failed'       => (int) ($item->failed ?? 0) + ($success ? 0 : 1),
            'time_post'    => $nextTime,
            'next_account' => ((int) ($item->next_account ?? 0) + 1) % count($accounts),
            'run'          => 0,
        ]);

        CLI::write("[CloudCampaignWorker] #{$item->id} -> {$to} " . ($success ? 'OK' : 'FALHA: ' . $message), $success ? 'green' : 'yellow');
    }

    private function applyResult($item, $account, $phone, string $to, bool $success, string $message, $errorCode, $waMessageId, int $now): void
    {
        $entry = [
            'phone_number'  => $to,
            'status'        => $success,
            'message'       => $message,
            'wa_message_id' => $waMessageId,
            'sent_at'       => $now,
        ];
        if ($errorCode !== null) {
            $entry['error_code'] = $errorCode;
        }
        if (!empty($phone->id)) {
            $entry['phone_number_id'] = (int) $phone->id;
        }

        // 1) result (caminho legado usado pelo relatorio quando cloud_parallel_enabled=0)
        $existing = [];
        $raw = (string) ($item->result ?? '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }
        $existing[] = $entry;
        $this->db->table(self::TB_SCHEDULES)->where('id', (int) $item->id)
            ->update(['result' => json_encode($existing)]);

        // 2) sp_whatsapp_message_status (painel "Saude Cloud" + ultimos erros)
        try {
            $this->db->table(self::TB_MSG_STATUS)->insert([
                'team_id'        => (int) ($item->team_id ?? 0),
                'campaign_name'  => (string) ($item->name ?? ''),
                'schedule_id'    => (int) $item->id,
                'account_id'     => (int) $account->id,
                'to_number'      => $to,
                'wa_message_id'  => $waMessageId ?: ('local_' . $now . '_' . mt_rand(1000, 9999)),
                'status'         => $success ? 'sent' : 'failed',
                'last_status_at' => $now,
                'meta_error_code' => $errorCode !== null ? (int) $errorCode : null,
                'meta_error_title' => $success ? null : substr($message, 0, 255),
                'meta_error_details' => $success ? null : $message,
                'created'        => $now,
            ]);
        } catch (\Throwable $e) {
            $this->log("Falha ao gravar message_status: " . $e->getMessage());
        }

        // 3) sp_whatsapp_cloud_dispatches (caminho usado quando cloud_parallel_enabled=1)
        try {
            $this->db->table(self::TB_DISPATCHES)->insert([
                'schedule_id'      => (int) $item->id,
                'team_id'          => (int) ($item->team_id ?? 0),
                'account_id'       => (int) $account->id,
                'phone_number_id'  => (string) ($account->token ?? ''),
                'contact_phone_id' => (int) ($phone->id ?? 0),
                'raw_phone'        => $to,
                'normalized_phone' => $to,
                'status'           => $success ? 'sent' : 'failed',
                'wa_message_id'    => $waMessageId,
                'attempt_count'    => 1,
                'error_code'       => $errorCode !== null ? (int) $errorCode : null,
                'error_message'    => $success ? null : $message,
                'last_attempt_at'  => $now,
                'created'          => $now,
                'updated'          => $now,
            ]);
        } catch (\Throwable $e) {
            $this->log("Falha ao gravar cloud_dispatches: " . $e->getMessage());
        }
    }

    private function sendMessage($item, $account, string $to, $phone): array
    {
        $type = (int) ($item->type ?? 1);
        $caption = (string) ($item->caption ?? '');
        $media = (string) ($item->media ?? '');

        switch ($type) {
            case 2: // Botao
                return $this->sendButton($item, $account, $to);

            case 3: // Lista
                return $this->sendList($item, $account, $to);

            case 6: // Template oficial Meta
                return $this->sendOfficialTemplate($item, $account, $to);

            default: // Texto / Midia
                $text = $caption !== '' ? $caption : ' ';
                if (function_exists('spintax')) {
                    $text = spintax($text);
                }
                $res = send_cloud_message($account, $to, $text, $media !== '' ? $media : null);
                return $this->normalizeResult($res);
        }
    }

    private function sendButton($item, $account, string $to): array
    {
        $template = $this->db->table(self::TB_TEMPLATE)->where('id', (int) $item->template)->get()->getRow();
        if (!$template) {
            return ['status' => 'error', 'message' => 'Template nao encontrado: ' . $item->template];
        }

        $tplData = json_decode((string) $template->data, true);
        if (!is_array($tplData)) {
            $tplData = [];
        }

        // Meta official (marketing/utilidade) -> envia via template aprovado (type=66)
        $metaEnabled = !empty($tplData['meta_official']['enabled']);
        if ($metaEnabled) {
            $official = $this->sendOfficialTemplate($item, $account, $to, $template, $tplData);
            if ($official['status'] === 'success' || empty($tplData['templateButtons'])) {
                return $official;
            }
            // Se falhou e ha botoes locais, cai para interactive button abaixo.
        }

        // Interactive button (fallback / nao-official)
        $buttons = $tplData['templateButtons'] ?? $tplData['interactiveButtons'] ?? [];
        $body = (string) ($tplData['text'] ?? $tplData['caption'] ?? ' ');
        if (function_exists('spintax')) {
            $body = spintax($body);
        }
        $footer = (string) ($tplData['footer'] ?? '');
        $imageUrl = $tplData['image']['url'] ?? null;

        // Caso de botao unico do tipo URL -> cta_url (melhor UX na Cloud API)
        if (count($buttons) === 1) {
            $btn = $buttons[0];
            $url = null;
            $displayText = '';
            $tel = null;
            if (!empty($btn['urlButton'])) {
                $url = $btn['urlButton']['url'] ?? '';
                $displayText = $btn['urlButton']['displayText'] ?? 'Link';
            } elseif (!empty($btn['callButton'])) {
                $tel = $btn['callButton']['phoneNumber'] ?? '';
                $displayText = $btn['callButton']['displayText'] ?? 'Ligar';
            }
            if ($url !== null || $tel !== null) {
                $interactive = [
                    'type' => 'cta_url',
                    'body' => ['text' => substr($body, 0, 1024)],
                    'action' => [
                        'name' => 'cta_url',
                        'parameters' => [
                            'display_text' => substr($displayText !== '' ? $displayText : 'Link', 0, 20),
                            'url' => $tel !== null ? 'tel:' . preg_replace('/[^0-9+]/', '', $tel) : $url,
                        ],
                    ],
                ];
                if ($footer !== '') {
                    $interactive['footer'] = ['text' => substr($footer, 0, 60)];
                }
                if ($imageUrl) {
                    $interactive['header'] = ['type' => 'image', 'image' => ['link' => $imageUrl]];
                }
                $res = send_cloud_interactive($account, $to, 'cta_url', $interactive);
                return $this->normalizeResult($res);
            }
        }

        // Multiplos botoes / reply buttons
        $actionButtons = [];
        foreach (array_slice($buttons, 0, 3) as $btn) {
            $real = $btn['quickReplyButton'] ?? $btn;
            $actionButtons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => (string) ($real['id'] ?? uniqid()),
                    'title' => substr((string) ($real['displayText'] ?? $real['text'] ?? 'Opcao'), 0, 20),
                ],
            ];
        }

        if (empty($actionButtons)) {
            // Sem botoes validos -> texto simples
            $res = send_cloud_message($account, $to, $body);
            return $this->normalizeResult($res);
        }

        $interactive = [
            'type' => 'button',
            'body' => ['text' => substr($body, 0, 1024)],
            'action' => ['buttons' => $actionButtons],
        ];
        if ($footer !== '') {
            $interactive['footer'] = ['text' => substr($footer, 0, 60)];
        }
        if ($imageUrl) {
            $interactive['header'] = ['type' => 'image', 'image' => ['link' => $imageUrl]];
        }

        $res = send_cloud_interactive($account, $to, 'button', $interactive);
        return $this->normalizeResult($res);
    }

    private function sendList($item, $account, string $to): array
    {
        $template = $this->db->table(self::TB_TEMPLATE)->where('id', (int) $item->template)->get()->getRow();
        if (!$template) {
            return ['status' => 'error', 'message' => 'Template nao encontrado: ' . $item->template];
        }
        $tplData = json_decode((string) $template->data, true) ?: [];

        $sections = [];
        foreach ($tplData['sections'] ?? [] as $sec) {
            $rows = [];
            foreach ($sec['rows'] ?? [] as $r) {
                $rows[] = [
                    'id' => (string) ($r['rowId'] ?? $r['id'] ?? uniqid()),
                    'title' => substr((string) ($r['title'] ?? ''), 0, 24),
                    'description' => substr((string) ($r['description'] ?? ''), 0, 72),
                ];
            }
            $sections[] = ['title' => substr((string) ($sec['title'] ?? ''), 0, 24), 'rows' => array_slice($rows, 0, 10)];
        }

        $interactive = [
            'type' => 'list',
            'header' => ['type' => 'text', 'text' => substr((string) ($tplData['title'] ?? 'Menu'), 0, 60)],
            'body' => ['text' => substr((string) ($tplData['text'] ?? ' '), 0, 1024)],
            'footer' => ['text' => substr((string) ($tplData['footer'] ?? ''), 0, 60)],
            'action' => [
                'button' => substr((string) ($tplData['buttonText'] ?? 'Opcoes'), 0, 20),
                'sections' => array_slice($sections, 0, 10),
            ],
        ];

        $res = send_cloud_interactive($account, $to, 'list', $interactive);
        return $this->normalizeResult($res);
    }

    /**
     * Envia template oficial (type=6) ou template meta_official de um botao (type=2).
     * Localiza a linha aprovada (type=66) e monta o payload com header de midia + body params.
     */
    private function sendOfficialTemplate($item, $account, string $to, $sourceTemplate = null, $sourceTplData = null): array
    {
        if ($sourceTemplate === null) {
            $sourceTemplate = $this->db->table(self::TB_TEMPLATE)->where('id', (int) $item->template)->get()->getRow();
        }
        if (!$sourceTemplate) {
            return ['status' => 'error', 'message' => 'Template nao encontrado: ' . $item->template];
        }

        if ($sourceTplData === null) {
            $sourceTplData = json_decode((string) $sourceTemplate->data, true) ?: [];
        }

        // type=6: o proprio template ja e o oficial aprovado.
        if ((int) ($sourceTemplate->type ?? 0) === 6) {
            $tplData = [
                'name'       => (string) ($sourceTplData['name'] ?? ''),
                'language'   => (string) ($sourceTplData['language'] ?? 'pt_BR'),
                'components' => $sourceTplData['components'] ?? [],
                'body_example_values' => $sourceTplData['body_example'] ?? ($sourceTplData['body_example_values'] ?? ''),
                'default_header_media' => $sourceTplData['default_header_media'] ?? null,
            ];
            $res = send_cloud_template($account, $to, $tplData);
            return $this->normalizeResult($res);
        }

        // type=2 com meta_official: achar a linha aprovada (type=66) vinculada.
        $accountIds = (string) ($account->ids ?? '');
        $sourceType = (string) ($item->type ?? '');
        $sourceIds = (string) ($sourceTemplate->ids ?? '');

        $approved = null;
        if ($accountIds !== '' && $sourceType !== '' && $sourceIds !== '') {
            $approved = $this->db->query(
                "SELECT * FROM " . self::TB_TEMPLATE . "
                 WHERE type = 66
                   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.account_ids')) = ?
                   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_type')) = ?
                   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_ids')) = ?
                   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.status')) = 'APPROVED'
                 ORDER BY changed DESC
                 LIMIT 1",
                [$accountIds, $sourceType, $sourceIds]
            )->getRow();
        }

        if (!$approved) {
            return ['status' => 'error', 'message' => 'Template oficial aprovado nao encontrado (source ' . $sourceIds . ')'];
        }

        $approvedData = json_decode((string) $approved->data, true) ?: [];
        $bodyExample = $sourceTplData['meta_official']['body_example'] ?? '';

        $tplData = [
            'name'       => (string) ($approvedData['name'] ?? ''),
            'language'   => (string) ($approvedData['language'] ?? ($sourceTplData['meta_official']['languages'] ?? 'pt_BR')),
            'components' => $approvedData['components'] ?? [],
            'body_example_values' => $bodyExample,
            'default_header_media' => $approvedData['default_header_media'] ?? null,
        ];

        $res = send_cloud_template($account, $to, $tplData);
        return $this->normalizeResult($res);
    }

    private function normalizeResult(array $res): array
    {
        if (($res['status'] ?? 'error') === 'success') {
            $msgId = null;
            if (isset($res['data']['messages'][0]['id'])) {
                $msgId = $res['data']['messages'][0]['id'];
            }
            return ['status' => 'success', 'message' => 'Enviado', 'wa_message_id' => $msgId];
        }

        return [
            'status'  => 'error',
            'message' => (string) ($res['message'] ?? 'Erro Cloud API'),
        ];
    }

    private function decodeAccounts($accounts): array
    {
        if (is_string($accounts)) {
            $accounts = json_decode($accounts, true);
        }
        if (!is_array($accounts)) {
            return [];
        }
        return array_values(array_filter(array_map('intval', $accounts)));
    }

    private function isAllCloud(array $accountIds): bool
    {
        if (empty($accountIds)) {
            return false;
        }
        $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
        $rows = $this->db->query(
            "SELECT id, login_type FROM " . self::TB_ACCOUNTS . " WHERE id IN ({$placeholders}) AND social_network = 'whatsapp'",
            $accountIds
        )->getResult();

        if (count($rows) === 0) {
            return false;
        }

        foreach ($rows as $row) {
            if ((int) $row->login_type !== 1) {
                return false;
            }
        }
        return true;
    }

    private function resolveAccount(array $accountIds, int $startIndex)
    {
        $count = count($accountIds);
        for ($i = 0; $i < $count; $i++) {
            $index = ($startIndex + $i) % $count;
            $account = $this->db->table(self::TB_ACCOUNTS)
                ->where('id', $accountIds[$index])
                ->where('status', 1)
                ->where('login_type', 1)
                ->where('social_network', 'whatsapp')
                ->get()->getRow();
            if ($account) {
                return $account;
            }
        }
        return null;
    }

    private function getPhoneAtOffset(int $contactId, int $offset)
    {
        $total = $this->db->table(self::TB_PHONE_NUMBERS)->where('pid', $contactId)->countAllResults();
        if ($offset >= $total || $total === 0) {
            return null;
        }

        return $this->db->table(self::TB_PHONE_NUMBERS)
            ->select('id, phone, params, is_valid')
            ->where('pid', $contactId)
            ->orderBy('id', 'ASC')
            ->limit(1, $offset)
            ->get()->getRow();
    }

    private function nextDelay($item): int
    {
        $min = (int) ($item->min_delay ?? 0);
        $max = (int) ($item->max_delay ?? 0);
        if ($min <= 0) {
            $min = 60;
        }
        if ($max < $min) {
            $max = $min;
        }
        return $max > $min ? mt_rand($min, $max) : $min;
    }

    private function isWithinWindow($item, int $now): bool
    {
        $hours = json_decode((string) ($item->schedule_time ?? '[]'), true) ?: [];
        $weekdays = json_decode((string) ($item->schedule_weekdays ?? '[]'), true) ?: [];
        $skipHolidays = (int) ($item->skip_team_holidays ?? 0) === 1;

        if (empty($hours) && empty($weekdays) && !$skipHolidays) {
            return true;
        }

        $tz = (string) ($item->timezone ?? '');
        try {
            $dt = new \DateTime('now', new \DateTimeZone($tz !== '' ? $tz : 'America/Sao_Paulo'));
        } catch (\Throwable $e) {
            $dt = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        }

        if (!empty($hours) && !in_array((string) $dt->format('G'), array_map('strval', $hours), true)) {
            return false;
        }
        if (!empty($weekdays) && !in_array((string) $dt->format('N'), array_map('strval', $weekdays), true)) {
            return false;
        }
        if ($skipHolidays) {
            try {
                $today = $dt->format('Y-m-d');
                $count = $this->db->table('sp_whatsapp_team_holidays')
                    ->where('team_id', (int) ($item->team_id ?? 0))
                    ->where('holiday_date', $today)
                    ->countAllResults();
                if ($count > 0) {
                    return false;
                }
            } catch (\Throwable $e) {
            }
        }

        return true;
    }

    private function release(int $scheduleId): void
    {
        $this->db->table(self::TB_SCHEDULES)->where('id', $scheduleId)->update(['run' => 0]);
    }

    private function log(string $message): void
    {
        @file_put_contents(
            WRITEPATH . 'logs/cloud_campaign_worker.log',
            date('Y-m-d H:i:s') . ' | ' . $message . "\n",
            FILE_APPEND
        );
    }
}
