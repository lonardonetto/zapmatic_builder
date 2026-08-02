<?php

if (!function_exists('call_normalize_phone')) {
    /**
     * Normaliza telefone brasileiro para formato WhatsApp JID.
     * Aplica regra do 9º dígito:
     *   DDD >= 31: remove o 9 (ex: 558694482065 → 558694482065)
     *   DDD <= 30: mantém o 9 (ex: 5521999999999 → 5521999999999)
     */
    function call_normalize_phone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // Adiciona 55 se não tiver
        if (strlen($clean) >= 10 && strlen($clean) <= 11 && substr($clean, 0, 2) !== '55') {
            $clean = '55' . $clean;
        }

        // Formato: 55 + DDD (2 dígitos) + número
        if (strlen($clean) >= 12 && substr($clean, 0, 2) === '55') {
            $ddd = intval(substr($clean, 2, 2));
            // DDD >= 31: WhatsApp usa SEM nono dígito
            if ($ddd >= 31 && strlen($clean) === 13 && $clean[4] === '9') {
                $clean = substr($clean, 0, 4) . substr($clean, 5);
            }
        }

        return $clean;
    }
}

if (!function_exists('call_get_contacts_with_phones')) {
    /**
     * Busca contatos do team com seus telefones válidos.
     */
    function call_get_contacts_with_phones(int $team_id): array
    {
        $db = \Config\Database::connect();

        $contacts = $db->table('sp_whatsapp_contacts')
            ->where('team_id', $team_id)
            ->where('status', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResult();

        foreach ($contacts as &$contact) {
            $phones = $db->table('sp_whatsapp_phone_numbers')
                ->select('phone, is_valid')
                ->where('pid', $contact->id)
                ->get()->getResult();

            $contact->phones = [];
            $contact->valid_phones = [];
            foreach ($phones as $p) {
                $normalized = call_normalize_phone($p->phone);
                $contact->phones[] = $normalized;
                if ($p->is_valid == 1 && strlen($normalized) >= 12) {
                    $contact->valid_phones[] = $normalized;
                }
            }
            $contact->phone_count = count($contact->valid_phones);
        }

        return $contacts;
    }
}
