<?php
namespace Core\Whatsapp_export_participants\Libraries;

/**
 * ExportQueue
 *
 * Lógica pura da fila de criação de listas de contatos em background.
 * Sem acesso a banco aqui: apenas fábrica de payload, regra de tenant e
 * cálculo de progresso. A persistência fica no controller/model.
 */
class ExportQueue
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const DEFAULT_MAX_ATTEMPTS = 3;
    public const BATCH_SIZE = 200;

    /**
     * Monta o payload de um job novo.
     *
     * @param int    $teamId
     * @param string $accountId
     * @param string $groupId
     * @param int    $total     Total de participantes a processar.
     * @return array{team_id:int, account_id:string, group_id:string, status:string, total:int, done:int, attempts:int, max_attempts:int, progress:float}
     */
    public static function createJob(int $teamId, string $accountId, string $groupId, int $total): array
    {
        return [
            'team_id'      => $teamId,
            'account_id'   => $accountId,
            'group_id'     => $groupId,
            'status'       => self::STATUS_PENDING,
            'total'        => $total,
            'done'         => 0,
            'attempts'     => 0,
            'max_attempts' => self::DEFAULT_MAX_ATTEMPTS,
            'progress'     => 0.0,
        ];
    }

    /**
     * Verifica se um job pertence ao time informado.
     *
     * @param array $job    Payload do job (deve ter 'team_id').
     * @param int   $teamId Time do worker.
     */
    public static function tenantOwns(array $job, int $teamId): bool
    {
        return (int)($job['team_id'] ?? null) === $teamId;
    }

    /**
     * Progresso (0..1) como razão entre concluídos e total.
     * Total zero retorna 0 (não divide por zero).
     *
     * @param int $done
     * @param int $total
     */
    public static function calcProgress(int $done, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }
        $progress = $done / $total;
        if ($progress < 0.0) {
            return 0.0;
        }
        if ($progress > 1.0) {
            return 1.0;
        }
        return $progress;
    }
}
