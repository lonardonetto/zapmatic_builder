<?php
namespace Core\Whatsapp_export_participants\Libraries;

/**
 * PhoneNormalizer
 *
 * Responsável por limpar e normalizar números de telefone extraídos de
 * grupos do WhatsApp, aplicando a regra do 9º dígito brasileiro.
 *
 * Regra aplicada (Anatel):
 *  - Celulares brasileiros possuem 9 dígitos (55 + DDD + 9xxxxxxxx).
 *  - Se o número já é brasileiro (DDI 55) e tem 8 dígitos na parte local,
 *    e inicia com 6,7,8 ou 9, adicionamos o dígito '9' após o DDD.
 *  - Números de outros países são apenas limpos e preservados.
 *
 * Isolado de propósito: zero acoplamento com o restante do módulo.
 */
class PhoneNormalizer
{
    /**
     * Remove todos os caracteres não numéricos.
     */
    public static function clean(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', (string)$phone);
    }

    /**
     * Remove o sufixo @s.whatsapp.net e qualquer ruído, retornando apenas dígitos.
     */
    public static function fromJid(string $jid): string
    {
        $jid = (string)$jid;
        $jid = preg_replace('/@s\.whatsapp\.net$/', '', $jid);
        $jid = preg_replace('/@g\.us$/', '', $jid);
        return self::clean($jid);
    }

    /**
     * Verifica se o número (já limpo) é um celular brasileiro com 8 dígitos locais
     * que precisa receber o 9º dígito.
     *
     * Retorna true quando o 9 deve ser adicionado.
     */
    public static function needsNinthDigit(string $cleanPhone): bool
    {
        // Só aplica a números brasileiros com DDI 55
        if (substr($cleanPhone, 0, 2) !== '55') {
            return false;
        }

        $local = substr($cleanPhone, 2); // DDD + número local

        // DDD (2 dígitos) + 8 dígitos locais = 10 -> total 12 com DDI
        if (strlen($cleanPhone) !== 12) {
            return false;
        }

        // O número local sem DDD é a parte final de 8 dígitos
        $localNumber = substr($local, 2, 8);

        // Celulares brasileiros começam com 6, 7, 8 ou 9
        return in_array(substr($localNumber, 0, 1), ['6', '7', '8', '9'], true);
    }

    /**
     * Aplica a regra do 9º dígito.
     *
     * @param string $phone Número em qualquer formato (com ou sem @s.whatsapp.net)
     * @return string Número limpo e normalizado (apenas dígitos)
     */
    public static function normalize(string $phone): string
    {
        $clean = self::fromJid($phone);

        if ($clean === '') {
            return '';
        }

        // Se for número brasileiro sem DDI (10 ou 11 dígitos), adiciona o 55
        if (strlen($clean) >= 10 && strlen($clean) <= 11 && substr($clean, 0, 2) !== '55') {
            $clean = '55' . $clean;
        }

        // Aplica o 9º dígito quando necessário
        if (self::needsNinthDigit($clean)) {
            // 55 + DDD (2) + '9' + 8 dígitos locais
            $clean = substr($clean, 0, 4) . '9' . substr($clean, 4);
        }

        return $clean;
    }

    /**
     * Normaliza e retorna o DDD (2 dígitos) quando aplicável (Brasil).
     * Retorna string vazia para números não brasileiros.
     */
    public static function extractDdd(string $phone): string
    {
        $clean = self::normalize($phone);
        if (substr($clean, 0, 2) === '55' && strlen($clean) >= 12) {
            return substr($clean, 2, 2);
        }
        return '';
    }
}
