<?php
namespace Core\Whatsapp_export_participants\Libraries;

use Core\Whatsapp_export_participants\Libraries\PhoneNormalizer;

/**
 * ParticipantFilter
 *
 * Filtros puros sobre a lista de participantes: exclusão opcional do próprio
 * número, exclusão opcional de admins e deduplicação por número normalizado.
 */
class ParticipantFilter
{
    /**
     * Aplica os filtros a uma lista de participantes.
     *
     * @param array       $participants  Array de objetos com ->id (jid) e ->admin (bool|string).
     * @param string|null $selfJid       JID do próprio número (ex.: "5511...@s.whatsapp.net").
     * @param bool        $excludeSelf   Remove o próprio número.
     * @param bool        $excludeAdmins Remove participantes admin/superadmin.
     * @return array Lista filtrada e deduplicada de objetos.
     */
    public static function apply(array $participants, ?string $selfJid = null, bool $excludeSelf = false, bool $excludeAdmins = false): array
    {
        $self = $selfJid !== null && $selfJid !== ''
            ? PhoneNormalizer::fromJid($selfJid)
            : null;

        $out = [];
        $seen = [];

        foreach ($participants as $participant) {
            $jid = is_object($participant) ? ($participant->id ?? '') : ($participant['id'] ?? '');
            $isAdmin = is_object($participant)
                ? (!empty($participant->admin))
                : (!empty($participant['admin']));

            if ($excludeAdmins && $isAdmin) {
                continue;
            }

            $clean = PhoneNormalizer::fromJid((string)$jid);
            if ($clean === '') {
                continue;
            }

            if ($excludeSelf && $self !== null && $clean === $self) {
                continue;
            }

            if (isset($seen[$clean])) {
                continue; // deduplicação: mantém a primeira ocorrência
            }
            $seen[$clean] = true;

            $out[] = $participant;
        }

        return $out;
    }
}
