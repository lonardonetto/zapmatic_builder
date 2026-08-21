<?php
declare(strict_types=1);

namespace Core\Whatsapp_bulk\Libraries;

/**
 * BulkDuplicator — Lógica pura de duplicação de campanhas de disparo em massa.
 *
 * Garante que a campanha duplicada preserve os contadores da original (sent e failed)
 * para que o motor Go inicie no offset correto (sent+failed) e continue exatamente
 * de onde parou.
 */
final class BulkDuplicator
{
    /**
     * Prepara os dados do novo registro de campanha a partir do objeto original.
     *
     * @param object|array $original
     * @param string $newName
     * @param string $newIds
     * @return array
     */
    public static function prepareDuplicateData($original, string $newName, string $newIds): array
    {
        $data = (array)$original;

        unset($data['id']);
        $data['ids'] = $newIds;
        $data['name'] = $newName;
        $data['status'] = 1;

        // Preserva sent e failed da campanha original para continuar de onde parou.
        // O offset persistente no Go e sent + failed.
        $data['sent'] = (int)($original->sent ?? $data['sent'] ?? 0);
        $data['failed'] = (int)($original->failed ?? $data['failed'] ?? 0);

        $data['result'] = '';
        $data['next_account'] = null;
        $data['run'] = 0;
        $data['changed'] = time();
        $data['created'] = time();

        return $data;
    }

    /**
     * Prepara os registros de alvos de grupo (sp_whatsapp_schedule_groups) para o novo schedule_id.
     *
     * @param array $originalGroups Lista de registros da tabela sp_whatsapp_schedule_groups
     * @param int $newScheduleId ID do novo registro de campanha inserido
     * @return array
     */
    public static function prepareGroupRecords(array $originalGroups, int $newScheduleId): array
    {
        $newRecords = [];
        foreach ($originalGroups as $g) {
            $row = (array)$g;
            unset($row['id']);
            $row['schedule_id'] = $newScheduleId;
            $newRecords[] = $row;
        }
        return $newRecords;
    }
}
