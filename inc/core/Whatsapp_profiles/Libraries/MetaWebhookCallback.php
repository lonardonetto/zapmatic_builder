<?php
declare(strict_types=1);

namespace Core\Whatsapp_profiles\Libraries;

/**
 * MetaWebhookCallback
 *
 * Monta a configuração de callback por WABA (override_callback_uri) para que
 * cada sistema processe os webhooks dos seus próprios números no seu próprio
 * domínio, sem depender do main (app único ELITEZAP).
 *
 * Isolada de propósito: zero cURL, zero banco, zero CodeIgniter. Apenas calcula
 * URLs e parâmetros. O controller (cURL) e o script retroativo consomem esta
 * biblioteca — regra única, testável.
 *
 * Referência: Graph API `POST /{waba_id}/subscribed_apps`
 *   ?override_callback_uri=... &verify_token=...
 */
class MetaWebhookCallback
{
    /** Sufixo do endpoint local de webhook (rota existente em todos os sistemas). */
    public const WEBHOOK_PATH = '/whatsapp_webhook';

    /** Constroi a URL local de callback a partir do domínio base do sistema. */
    public static function buildLocalCallbackUrl(string $baseUrl): string
    {
        return rtrim(trim($baseUrl), '/') . self::WEBHOOK_PATH;
    }

    /** Constroi a URL de subscribe (com override) para uma WABA. */
    public static function buildOverrideUrl(string $graphVersion, string $wabaId): string
    {
        $version = trim($graphVersion) !== '' ? trim($graphVersion) : 'v22.0';
        return "https://graph.facebook.com/{$version}/{$wabaId}/subscribed_apps";
    }

    /**
     * Constroi o corpo (application/x-www-form-urlencoded) do subscribe.
     *
     * @return array{override_callback_uri:string, verify_token:string}
     */
    public static function buildOverrideParams(string $localCallback, string $verifyToken): array
    {
        return [
            'override_callback_uri' => $localCallback,
            'verify_token' => $verifyToken,
        ];
    }

    /**
     * Verifica se a configuração de webhook de um número já aponta para o
     * domínio local (campo `whatsapp_business_account`).
     *
     * @param array|string|null $webhookConfiguration JSON do campo `webhook_configuration`
     * @param string $localCallback URL local esperada (sem trailing slash)
     */
    public static function isCorrect($webhookConfiguration, string $localCallback): bool
    {
        if (is_string($webhookConfiguration)) {
            $decoded = json_decode($webhookConfiguration, true);
            if (!is_array($decoded)) {
                return false;
            }
            $webhookConfiguration = $decoded;
        }

        if (!is_array($webhookConfiguration)) {
            return false;
        }

        $configured = $webhookConfiguration['whatsapp_business_account'] ?? null;
        if (!is_string($configured) || $configured === '') {
            return false;
        }

        // Normaliza para comparar ignorando trailing slash e index.php no caminho.
        $normalized = rtrim($configured, '/');
        $expected = rtrim($localCallback, '/');

        if ($normalized === $expected) {
            return true;
        }

        // Alguns servidores entregam "/index.php/whatsapp_webhook".
        $expectedAlt = str_replace('/whatsapp_webhook', '/index.php/whatsapp_webhook', $expected);
        return $normalized === $expectedAlt;
    }
}
