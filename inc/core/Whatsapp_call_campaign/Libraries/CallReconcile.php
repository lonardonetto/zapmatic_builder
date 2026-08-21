<?php
declare(strict_types=1);

namespace Core\Whatsapp_call_campaign\Libraries;

/**
 * CallReconcile — lógica pura de reconciliação de leads de campanha de chamada.
 *
 * Sem acoplamento com CodeIgniter, banco ou gateway. Devolve decisões puras que o
 * worker usa: ação por status do gateway, instância alternada, contagem de leads
 * pendentes/resolvidos e o desfecho de timeout.
 */
final class CallReconcile
{
    /**
     * Ação a tomar para um lead dado o status retornado pelo /call/status.
     * "answered"/"active" => answered; "ended" => ended; demais (ex.: ringing) => noop.
     */
    public static function resolveAction(string $status): string
    {
        if ($status === 'active' || $status === 'answered') {
            return 'answered';
        }
        if ($status === 'ended') {
            return 'ended';
        }
        return 'noop';
    }

    /**
     * Agrupa a lista plana de eventos por lead_id.
     *
     * @param array $events lista de objetos/arrays com campo lead_id
     */
    public static function eventsForLead(array $events, int $leadId): array
    {
        $out = [];
        foreach ($events as $ev) {
            $evLead = is_array($ev) ? ($ev['lead_id'] ?? null) : ($ev->lead_id ?? null);
            if ((int)$evLead === $leadId) {
                $out[] = $ev;
            }
        }
        return $out;
    }

    /**
     * Seleciona a instância do próximo disparo no modo alternado (rodízio).
     *
     * @param string[] $instanceIds
     */
    public static function nextInstance(array $instanceIds, int $doneCount): ?string
    {
        if (empty($instanceIds)) {
            return null;
        }
        $idx = $doneCount % count($instanceIds);
        return $instanceIds[$idx];
    }

    /**
     * Conta quantos leads ainda estão pendentes (não resolvidos).
     *
     * @param string[] $statuses status de cada lead da campanha
     */
    public static function pendingCount(array $statuses): int
    {
        $n = 0;
        foreach ($statuses as $s) {
            if ($s === 'pending' || $s === 'ringing') {
                $n++;
            }
        }
        return $n;
    }

    /**
     * Indica se todos os leads da campanha já foram resolvidos (nenhum pending/ringing).
     *
     * @param string[] $statuses
     */
    public static function allResolved(array $statuses): bool
    {
        return self::pendingCount($statuses) === 0;
    }

    /**
     * Desfecho aplicado quando o worker estoura o timeout de espera.
     * Sempre cancela antes de falhar — o motivo fica explícito para o relatório.
     */
    public static function timeoutOutcome(): array
    {
        return [
            'status' => 'failed',
            'hangup_source' => 'worker',
            'error_message' => 'Timeout waiting for call result',
        ];
    }
}
