<?php
namespace Core\Whatsapp_bulk\Libraries;

/**
 * GroupTarget
 *
 * Biblioteca pura para destinos de grupo do disparo em massa. Não depende de
 * banco nem de CodeIgniter — apenas monta, normaliza e valida a lista de
 * grupos que uma campanha vai atingir (envio DENTRO do grupo, via @g.us).
 */
class GroupTarget
{
    /**
     * Normaliza uma lista de destinos de grupo, deduplicando por par
     * account_id + group_jid e descartando group_jid vazio.
     *
     * @param array $targets Lista de ['account_id' => mixed, 'group_jid' => mixed]
     * @return array Lista de ['account_id' => string, 'group_jid' => string] sem repetição.
     */
    public static function normalizeTargets(array $targets): array
    {
        $out = [];
        $seen = [];

        foreach ($targets as $target) {
            if (!is_array($target)) {
                continue;
            }

            $accountId = trim((string)($target['account_id'] ?? ''));
            $groupJid  = trim((string)($target['group_jid'] ?? ''));

            if ($groupJid === '') {
                continue; // descarta JID vazio
            }

            $groupJid = self::ensureGroupJid($groupJid);
            $key = $accountId . '|' . $groupJid;

            if (isset($seen[$key])) {
                continue; // deduplica
            }
            $seen[$key] = true;

            $out[] = [
                'account_id' => $accountId,
                'group_jid'  => $groupJid,
            ];
        }

        return $out;
    }

    /**
     * Monta o JID de grupo com o sufixo @g.us. Se já contiver "@", preserva.
     *
     * @param string $groupId Id do grupo, com ou sem sufixo.
     * @return string JID de grupo (ex.: "123456789@g.us").
     */
    public static function ensureGroupJid(string $groupId): string
    {
        $groupId = trim($groupId);
        if ($groupId === '') {
            return '';
        }

        if (strpos($groupId, '@') !== false) {
            return $groupId;
        }

        return $groupId . '@g.us';
    }

    /**
     * Resolve o chat de destino de um envio de grupo: sempre o JID do grupo.
     * Nunca cai em @s.whatsapp.net (que é JID de número individual).
     *
     * @param string $groupId Id do grupo, com ou sem sufixo.
     * @return string JID de grupo (@g.us).
     */
    public static function resolveChat(string $groupId): string
    {
        $jid = self::ensureGroupJid($groupId);
        if (strpos($jid, '@g.us') !== false) {
            return $jid;
        }

        // Se veio um sufixo que não é de grupo (ex.: @s.whatsapp.net), força @g.us.
        $base = preg_replace('/@.*$/', '', $jid);
        return $base . '@g.us';
    }

    /**
     * Valida se uma conta é elegível para envio em grupo (Go/Whatsmeow,
     * login_type=3). Contas legadas (1/2) não enviam em grupo neste ciclo.
     *
     * @param int $loginType
     * @return bool
     */
    public static function supportsGroupSend($loginType): bool
    {
        return (int)$loginType === 3;
    }

    /**
     * Injeta team_id em cada destino de grupo. team_id vazio é rejeitado.
     *
     * @param array $targets Lista já normalizada de ['account_id', 'group_jid'].
     * @param int   $teamId
     * @return array Destinos com 'team_id' garantido.
     * @throws \InvalidArgumentException se teamId <= 0.
     */
    public static function scopeTargets(array $targets, int $teamId): array
    {
        if ($teamId <= 0) {
            throw new \InvalidArgumentException('team_id is required');
        }

        $out = [];
        foreach ($targets as $target) {
            $target['team_id'] = $teamId;
            $out[] = $target;
        }

        return $out;
    }
}
