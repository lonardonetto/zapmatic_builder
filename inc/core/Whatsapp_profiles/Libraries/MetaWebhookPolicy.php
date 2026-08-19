<?php
declare(strict_types=1);

namespace Core\Whatsapp_profiles\Libraries;

/**
 * MetaWebhookPolicy
 *
 * Regras puras do endpoint de webhook Cloud API:
 *   1. decidir se um evento é processado localmente ou apenas logado;
 *   2. validar o hub.verify_token contra os tokens salvos nas contas locais.
 *
 * Isolada de propósito: zero banco, zero cURL, zero CodeIgniter. O controller
 * `Whatsapp_webhook.php` consulta o banco e usa esta classe para a DECISÃO —
 * mantendo o comportamento observável testável.
 */
class MetaWebhookPolicy
{
    public const ACTION_PROCESS = 'process';
    public const ACTION_LOG_DISABLED = 'log_disabled';

    /**
     * Decide a ação para um evento de webhook dado se o número foi encontrado
     * localmente (login_type = 1 no banco do próprio sistema).
     *
     * - Encontrado localmente  -> PROCESS (encaminha ao Bot Builder)
     * - Não encontrado          -> LOG_DISABLED ("Forwarding DISABLED", sem loop)
     */
    public static function decideAction(bool $foundLocally): string
    {
        return $foundLocally ? self::ACTION_PROCESS : self::ACTION_LOG_DISABLED;
    }

    /**
     * Valida o hub.verify_token recebido na verificação GET contra a lista de
     * verify_tokens salvos nas contas Cloud API locais.
     *
     * @param array<string> $accountVerifyTokens tokens do campo data.verify_token das contas locais
     * @param string $incomingToken hub.verify_token recebido
     */
    public static function verifyTokenMatches(array $accountVerifyTokens, string $incomingToken): bool
    {
        if ($incomingToken === '') {
            return false;
        }
        foreach ($accountVerifyTokens as $token) {
            if (hash_equals((string) $token, $incomingToken)) {
                return true;
            }
        }
        return false;
    }
}
