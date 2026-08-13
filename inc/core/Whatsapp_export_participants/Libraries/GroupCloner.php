<?php
namespace Core\Whatsapp_export_participants\Libraries;

use Core\Whatsapp_export_participants\Libraries\PhoneNormalizer;

/**
 * GroupCloner
 *
 * Lógica pura do clone de grupos: montagem do nome destino, filtro de
 * participantes (sem admins, sem o próprio número, sem duplicados),
 * fatiamento em lotes, cálculo de progresso e payload do job.
 *
 * Isolado de propósito: zero banco, zero acoplamento com CodeIgniter.
 * A persistência e as chamadas ao gateway Go ficam no controller.
 */
class GroupCloner
{
    public const MAX_NAME_LENGTH = 25;
    public const ADD_BATCH_SIZE = 50;
    public const DEFAULT_MAX_ATTEMPTS = 3;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Monta o nome do grupo clonado: "<origem> - cópia", truncado a
     * MAX_NAME_LENGTH caracteres (limite do WhatsApp).
     */
    public static function buildTargetName(string $sourceName): string
    {
        $base = trim($sourceName);
        if ($base === '') {
            $base = 'Grupo';
        }
        $name = $base . ' - cópia';
        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            return mb_substr($name, 0, self::MAX_NAME_LENGTH);
        }
        return $name;
    }

    /**
     * Verifica se uma conta suporta clone (apenas Go, login_type=3).
     */
    public static function supportsClone($loginType): bool
    {
        return (int)$loginType === 3;
    }

    /**
     * Filtra participantes para o clone: remove APENAS o próprio número (a
     * conta conectada, que vira admin do grupo novo como criadora) e deduplica.
     * Os demais participantes — incluindo admins do grupo de origem — permanecem,
     * para que qualquer grupo possa ser clonado.
     *
     * @param array       $participants Array de objetos com ->id (jid) e ->admin (bool).
     * @param string|null $selfJid      JID do próprio número (ex.: "5511...@s.whatsapp.net").
     * @return string[] Números limpos (apenas dígitos).
     */
    public static function filterClone(array $participants, ?string $selfJid = null): array
    {
        $self = ($selfJid !== null && $selfJid !== '')
            ? PhoneNormalizer::fromJid($selfJid)
            : null;

        $out = [];
        $seen = [];

        foreach ($participants as $participant) {
            $jid = is_object($participant) ? ($participant->id ?? '') : ($participant['id'] ?? '');

            $clean = PhoneNormalizer::fromJid((string)$jid);
            if ($clean === '') {
                continue;
            }

            if ($self !== null && $clean === $self) {
                continue; // o próprio número (a conta conectada) não entra no clone
            }

            if (isset($seen[$clean])) {
                continue; // deduplicação: mantém a primeira ocorrência
            }
            $seen[$clean] = true;

            $out[] = $clean;
        }

        return $out;
    }

    /**
     * Divide uma lista em lotes de no máximo $batch itens.
     *
     * @return array Lista de lotes (arrays).
     */
    public static function chunkParticipants(array $items, int $batch = self::ADD_BATCH_SIZE): array
    {
        if ($batch <= 0) {
            $batch = self::ADD_BATCH_SIZE;
        }
        return array_chunk($items, $batch);
    }

    /**
     * Progresso (0..1) como razão entre concluídos e total. Nunca passa de 1.
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

    /**
     * Monta o payload de um job de clone novo.
     *
     * @return array{team_id:int, account_id:string, group_id:string, target_name:string, status:string, total:int, done:int, attempts:int, max_attempts:int, progress:float}
     */
    public static function createJob(int $teamId, string $accountId, string $groupId, string $targetName, int $total): array
    {
        return [
            'team_id'      => $teamId,
            'account_id'   => $accountId,
            'group_id'     => $groupId,
            'target_name'  => $targetName,
            'status'       => self::STATUS_PENDING,
            'total'        => $total,
            'done'         => 0,
            'attempts'     => 0,
            'max_attempts' => self::DEFAULT_MAX_ATTEMPTS,
            'progress'     => 0.0,
        ];
    }
}
