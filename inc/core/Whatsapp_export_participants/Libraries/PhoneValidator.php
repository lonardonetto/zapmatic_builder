<?php
namespace Core\Whatsapp_export_participants\Libraries;

use Core\Whatsapp_export_participants\Libraries\PhoneNormalizer;

/**
 * PhoneValidator
 *
 * Valida se um número normalizado é plausível para uso em listas de
 * contatos (ligações / broadcast).
 *
 * Isolado de propósito: não acopla regras de negócio do módulo.
 */
class PhoneValidator
{
    /**
     * Valida o comprimento e a estrutura de um número já normalizado.
     *
     * @return bool
     */
    public static function isValid(string $normalizedPhone): bool
    {
        if ($normalizedPhone === '' || !preg_match('/^[0-9]+$/', $normalizedPhone)) {
            return false;
        }

        $len = strlen($normalizedPhone);

        // Aceita números nacionais (10-11) ou internacionais (12-15)
        if ($len < 10 || $len > 15) {
            return false;
        }

        // Número brasileiro (DDI 55) deve ter 12 ou 13 dígitos após normalização
        if (substr($normalizedPhone, 0, 2) === '55') {
            return ($len === 12 || $len === 13);
        }

        return true;
    }

    /**
     * Classifica um número normalizado em um dos status usados pelo sistema:
     *  1 = válido, 2 = inválido, 4 = pendente (aguardando verificação).
     *
     * Como não realizamos verificação ativa contra o WhatsApp neste fluxo,
     * marcamos números plausíveis como pendentes (4) para que o validador
     * em background do sistema confirme, e inválidos como 2.
     */
    public static function classify(string $normalizedPhone): int
    {
        return self::isValid($normalizedPhone) ? 4 : 2;
    }
}
