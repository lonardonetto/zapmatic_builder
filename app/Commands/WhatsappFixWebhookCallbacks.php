<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * WhatsappFixWebhookCallbacks
 * ---------------------------
 * Correção retroativa das callbacks de webhook Cloud API.
 *
 * Itera as contas Cloud API (`sp_accounts.login_type = 1`) ativas e chama
 * `POST /{waba_id}/subscribed_apps` com `override_callback_uri` apontando para
 * o domínio LOCAL do sistema, para que cada número receba seus webhooks no
 * próprio endpoint (independência de sistema).
 *
 * Idempotente: re-subscribe é seguro (não gera efeito colateral).
 */
class WhatsappFixWebhookCallbacks extends BaseCommand
{
    protected $group       = 'WhatsApp';
    protected $name        = 'whatsapp:fix-webhook-callbacks';
    protected $description = 'Corrige a callback de webhook por WABA para o domínio local de todas as contas Cloud API.';

    public function run(array $params)
    {
        if (!function_exists('db_fetch')) {
            $common = FCPATH . 'app/Helpers/Common_helper.php';
            if (is_file($common)) {
                require_once $common;
            }
        }

        $db = \Config\Database::connect();

        $rows = $db->table('sp_accounts')
            ->where('social_network', 'whatsapp')
            ->where('login_type', 1)
            ->where('status', 1)
            ->get()->getResult();

        if (empty($rows)) {
            CLI::write('Nenhuma conta Cloud API ativa encontrada.', 'yellow');
            return;
        }

        $local_callback = \Core\Whatsapp_profiles\Libraries\MetaWebhookCallback::buildLocalCallbackUrl(base_url());
        CLI::write("Callback local: {$local_callback}", 'green');
        CLI::write("Contas a corrigir: " . count($rows), 'green');

        $ok = 0;
        $fail = 0;

        foreach ($rows as $account) {
            $data = json_decode($account->data ?? '{}', true) ?: [];
            $wabaId = $data['waba_id'] ?? null;
            $verifyToken = $data['verify_token'] ?? null;
            $token = $data['token'] ?? null;

            if (empty($wabaId) || empty($token)) {
                CLI::write("  #{$account->id} {$account->name}: sem waba_id/token no data — ignorado", 'yellow');
                $fail++;
                continue;
            }

            $verifyToken = $verifyToken !== null && $verifyToken !== '' ? $verifyToken : uniqid('zapmatic_');

            $url = \Core\Whatsapp_profiles\Libraries\MetaWebhookCallback::buildOverrideUrl('v22.0', $wabaId);
            $params = \Core\Whatsapp_profiles\Libraries\MetaWebhookCallback::buildOverrideParams($local_callback, $verifyToken);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $resp = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $decoded = json_decode((string) $resp, true);
            $success = $http >= 200 && $http < 300;

            if ($success) {
                CLI::write("  #{$account->id} {$account->name}: OK (HTTP {$http})", 'green');
                $ok++;
            } else {
                $err = $decoded['error']['message'] ?? 'erro desconhecido';
                CLI::write("  #{$account->id} {$account->name}: FALHA (HTTP {$http}) — {$err}", 'red');
                $fail++;
            }
        }

        CLI::write("Concluído: {$ok} OK, {$fail} falha(s).", $ok > 0 ? 'green' : 'yellow');
    }
}
