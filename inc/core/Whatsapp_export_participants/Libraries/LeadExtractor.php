<?php
namespace Core\Whatsapp_export_participants\Libraries;

use Core\Whatsapp_export_participants\Libraries\PhoneNormalizer;
use Core\Whatsapp_export_participants\Libraries\PhoneValidator;

/**
 * LeadExtractor
 *
 * Extrai, normaliza e valida participantes de um grupo do WhatsApp,
 * produzindo uma lista pronta para uso (contatos / broadcast).
 *
 * Também é responsável por persistir a lista como um grupo de contatos
 * no módulo de contatos do sistema (sp_whatsapp_contacts + sp_whatsapp_phone_numbers).
 */
class LeadExtractor
{
    /**
     * Processa a lista bruta de participantes e retorna registros normalizados.
     *
     * @param array $participants Array de objetos com ->id (jid) e ->admin (bool)
     * @param string $groupId      ID do grupo (para rastreio)
     * @return array Lista de ['phone' => ..., 'is_valid' => ..., 'ddd' => ...]
     */
    public static function extract(array $participants, string $groupId = ''): array
    {
        $rows = [];
        $seen = [];

        foreach ($participants as $participant) {
            $jid = is_object($participant) ? ($participant->id ?? '') : ($participant['id'] ?? '');
            $normalized = PhoneNormalizer::normalize((string)$jid);

            if ($normalized === '' || isset($seen[$normalized])) {
                continue; // ignora vazios e duplicados
            }
            $seen[$normalized] = true;

            // Aproveita o nome quando o gateway já o retornar; ausente vira nulo.
            $name = is_object($participant)
                ? ($participant->name ?? $participant->pushName ?? null)
                : ($participant['name'] ?? $participant['pushName'] ?? null);

            $rows[] = [
                'phone'    => $normalized,
                'is_valid' => PhoneValidator::classify($normalized),
                'ddd'      => PhoneNormalizer::extractDdd($normalized),
                'group_id' => $groupId,
                'name'     => $name !== null && $name !== '' ? (string)$name : null,
            ];
        }

        return $rows;
    }

    /**
     * Cria um grupo de contatos no módulo de contatos do sistema e insere
     * os números normalizados.
     *
     * @param int    $teamId
     * @param string $contactName Nome do grupo de contatos a criar
     * @param array  $rows        Resultado de self::extract()
     * @return array ['contact_id' => int, 'inserted' => int, 'valid' => int, 'invalid' => int]
     */
    public static function saveAsContactList(int $teamId, string $contactName, array $rows): array
    {
        if (empty($rows)) {
            return ['contact_id' => null, 'inserted' => 0, 'valid' => 0, 'invalid' => 0];
        }

        $db = \Config\Database::connect();

        $db->table(TB_WHATSAPP_CONTACTS)->insert([
            'ids'     => ids(),
            'team_id' => $teamId,
            'name'    => $contactName,
            'status'  => 1,
            'changed' => time(),
            'created' => time(),
        ]);

        $contactId = $db->insertID();

        $batch = [];
        $valid = 0;
        $invalid = 0;

        foreach ($rows as $row) {
            $batch[] = [
                'ids'      => ids(),
                'team_id'  => $teamId,
                'pid'      => $contactId,
                'phone'    => $row['phone'],
                'params'   => !empty($row['name']) ? json_encode(['name' => $row['name']]) : null,
                'is_valid' => $row['is_valid'],
            ];

            if ($row['is_valid'] === 2) {
                $invalid++;
            } else {
                $valid++;
            }
        }

        if (!empty($batch)) {
            $db->table(TB_WHATSAPP_PHONE_NUMBERS)->insertBatch($batch);
        }

        return [
            'contact_id' => $contactId,
            'inserted'   => count($batch),
            'valid'      => $valid,
            'invalid'    => $invalid,
        ];
    }
}
