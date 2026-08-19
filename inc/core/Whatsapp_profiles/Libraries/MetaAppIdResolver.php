<?php
declare(strict_types=1);

namespace Core\Whatsapp_profiles\Libraries;

/**
 * MetaAppIdResolver
 *
 * Resolve o App ID da Meta usado no onboarding (Embedded Signup / Facebook SDK).
 *
 * Regra (em ordem de prioridade):
 *   1. `meta_app_id`       — se for um App ID numérico válido;
 *   2. `facebook_login_app_id` — se for numérico válido (fallback legado);
 *   3. fallback fixo       — App ID padrão do sistema.
 *
 * Motivação: a opção `meta_app_id` já chegou a ser gravada com valor inválido
 * (ex.: "admind"). Como o código antigo usava `?:`, qualquer string não-vazia
 * vencia o fallback — inclusive lixo — quebrando o FB.init()/FB.login().
 *
 * Isolada de propósito: zero CodeIgniter, zero banco. A leitura das opções
 * continua no controller/view; aqui só entra a validação + priorização pura.
 */
class MetaAppIdResolver
{
    /** App ID padrão do sistema (fallback fixo). */
    public const FALLBACK_APP_ID = '763786439394524';

    /**
     * Resolve o App ID final a partir das duas opções disponíveis.
     *
     * @param string|int|null $metaAppId          valor de `meta_app_id`
     * @param string|int|null $facebookLoginAppId valor de `facebook_login_app_id`
     */
    public static function resolve($metaAppId = null, $facebookLoginAppId = null): string
    {
        $meta = self::normalize($metaAppId);
        if ($meta !== null) {
            return $meta;
        }

        $facebook = self::normalize($facebookLoginAppId);
        if ($facebook !== null) {
            return $facebook;
        }

        return self::FALLBACK_APP_ID;
    }

    /**
     * Resolve o App Secret final (32 hex) a partir das duas opções.
     *
     * Mesmo padrão do App ID: o valor específico `meta_app_secret` só vale se
     * for um secret válido (32 caracteres hex). Caso contrário, cai para o
     * `facebook_login_app_secret`. Retorna string vazia se ambos forem inválidos.
     *
     * @param string|int|null $metaAppSecret          valor de `meta_app_secret`
     * @param string|int|null $facebookLoginAppSecret valor de `facebook_login_app_secret`
     */
    public static function resolveSecret($metaAppSecret = null, $facebookLoginAppSecret = null): string
    {
        $meta = self::normalizeSecret($metaAppSecret);
        if ($meta !== null) {
            return $meta;
        }

        $facebook = self::normalizeSecret($facebookLoginAppSecret);
        if ($facebook !== null) {
            return $facebook;
        }

        return '';
    }

    /**
     * Retorna true se o valor é um App ID numérico válido da Meta.
     *
     * App IDs da Meta são sequências de dígitos (ex.: 763786439394524).
     * Aceitamos qualquer sequência com pelo menos 10 dígitos e sem outros
     * caracteres. Retorna false para vazio, null, "admind" etc.
     *
     * @param mixed $value
     */
    public static function isValid($value): bool
    {
        return self::normalize($value) !== null;
    }

    /**
     * Retorna true se o valor é um App Secret válido (32 hex).
     *
     * @param mixed $value
     */
    public static function isValidSecret($value): bool
    {
        return self::normalizeSecret($value) !== null;
    }

    /**
     * Normaliza um valor para um App ID válido, ou null se inválido.
     *
     * @param mixed $value
     */
    private static function normalize($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Apenas dígitos; pelo menos 10 (App IDs reais têm ~15).
        if (!preg_match('/^[0-9]{10,}$/', $value)) {
            return null;
        }

        return $value;
    }

    /**
     * Normaliza um App Secret (32 caracteres hex), ou null se inválido.
     *
     * @param mixed $value
     */
    private static function normalizeSecret($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (!preg_match('/^[0-9a-fA-F]{32}$/', $value)) {
            return null;
        }

        return strtolower($value);
    }
}
