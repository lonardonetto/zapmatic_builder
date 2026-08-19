<?php
declare(strict_types=1);

namespace Core\Whatsapp_profiles\Libraries;

/**
 * MetaLoginGate
 *
 * Gate puro para decidir se o FB.login() pode ser chamado com segurança.
 *
 * Motivação: o console reportava "FB.login() called before FB.init()" quando o
 * login disparava antes do SDK estar pronto. Esta classe extrai a decisão para
 * uma função estática testável (sem depender do SDK do Facebook no ambiente de
 * testes).
 */
class MetaLoginGate
{
    /**
     * Retorna true apenas quando o SDK está pronto e o FB foi inicializado.
     *
     * @param bool $fbReady   se window.FB existe e o SDK foi inicializado
     */
    public static function canLogin(bool $fbReady): bool
    {
        return $fbReady === true;
    }
}
