<?php
namespace Core\Whatsapp_export_participants\Libraries;

/**
 * AccountScope
 *
 * Regra pura de escopo multi-tenant para consultas de conta: toda consulta
 * acionada pelo usuário final DEVE filtrar por team_id (princípio P-005).
 */
class AccountScope
{
    /**
     * Injeta team_id nos filtros de consulta. team_id vazio é rejeitado.
     *
     * @param array $filters
     * @param int   $teamId
     * @return array Filtros com 'team_id' garantido.
     * @throws \InvalidArgumentException se teamId <= 0.
     */
    public static function withTeam(array $filters, int $teamId): array
    {
        if ($teamId <= 0) {
            throw new \InvalidArgumentException('team_id is required');
        }
        $filters['team_id'] = $teamId;
        return $filters;
    }
}
