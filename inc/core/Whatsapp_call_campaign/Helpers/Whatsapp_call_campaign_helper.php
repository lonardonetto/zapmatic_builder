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

if (!function_exists('call_normalize_schedule_hours')) {
    function call_normalize_schedule_hours($schedule_time): array
    {
        if (empty($schedule_time)) return [];
        if (!is_array($schedule_time)) {
            $schedule_time = json_decode($schedule_time, true) ?? [];
        }
        $normalized = [];
        foreach ($schedule_time as $value) {
            $hour = (int) $value;
            if ($hour >= 0 && $hour <= 23) {
                $normalized[(string) $hour] = (string) $hour;
            }
        }
        $normalized = array_values($normalized);
        sort($normalized, SORT_NUMERIC);
        return $normalized;
    }
}

if (!function_exists('call_normalize_schedule_weekdays')) {
    function call_normalize_schedule_weekdays($schedule_weekdays): array
    {
        if (empty($schedule_weekdays)) return [];
        if (!is_array($schedule_weekdays)) {
            $schedule_weekdays = json_decode($schedule_weekdays, true) ?? [];
        }
        $normalized = [];
        foreach ($schedule_weekdays as $value) {
            $weekday = (int) $value;
            if ($weekday >= 1 && $weekday <= 7) {
                $normalized[(string) $weekday] = (string) $weekday;
            }
        }
        $normalized = array_values($normalized);
        sort($normalized, SORT_NUMERIC);
        return $normalized;
    }
}

if (!function_exists('call_is_within_schedule_window')) {
    /**
     * Verifica se estamos dentro da janela de agendamento da campanha.
     * Retorna true se a campanha pode rodar agora.
     */
    function call_is_within_schedule_window(object $campaign): bool
    {
        $tz = !empty($campaign->timezone) ? $campaign->timezone : date_default_timezone_get();
        try {
            $now = new \DateTime('now', new \DateTimeZone($tz));
        } catch (\Throwable $e) {
            $now = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        }

        // Verificar horários permitidos
        $hours = call_normalize_schedule_hours($campaign->schedule_time ?? '');
        if (!empty($hours) && !in_array((string)$now->format('G'), $hours, true)) {
            return false;
        }

        // Verificar dias da semana permitidos
        $weekdays = call_normalize_schedule_weekdays($campaign->schedule_weekdays ?? '');
        if (!empty($weekdays) && !in_array((string)$now->format('N'), $weekdays, true)) {
            return false;
        }

        // Verificar feriados
        if (!empty($campaign->skip_team_holidays) && (int)$campaign->skip_team_holidays === 1) {
            $holidays = call_get_team_holidays((int)($campaign->team_id ?? 0));
            if (in_array($now->format('Y-m-d'), $holidays, true)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('call_get_team_holidays')) {
    function call_get_team_holidays(int $team_id): array
    {
        $db = \Config\Database::connect();
        try {
            $rows = $db->table('sp_team_holidays')
                ->select('holiday_date')
                ->where('team_id', $team_id)
                ->get()->getResult();
            return array_map(fn($r) => $r->holiday_date, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('call_schedule_label')) {
    /**
     * Gera label legível da janela de agendamento.
     */
    function call_schedule_label(object $campaign): string
    {
        $parts = [];

        $weekdays = call_normalize_schedule_weekdays($campaign->schedule_weekdays ?? '');
        if (!empty($weekdays)) {
            $names = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb',7=>'Dom'];
            if (implode(',', $weekdays) === '1,2,3,4,5') {
                $parts[] = 'Seg-Sex';
            } elseif (implode(',', $weekdays) === '1,2,3,4,5,6,7') {
                $parts[] = 'Todos os dias';
            } else {
                $parts[] = implode(', ', array_map(fn($d) => $names[$d] ?? $d, $weekdays));
            }
        }

        $hours = call_normalize_schedule_hours($campaign->schedule_time ?? '');
        if (!empty($hours)) {
            $first = str_pad($hours[0], 2, '0', STR_PAD_LEFT);
            $last = str_pad(end($hours), 2, '0', STR_PAD_LEFT);
            $parts[] = $first . 'h-' . $last . 'h';
        }

        if (!empty($campaign->skip_team_holidays) && (int)$campaign->skip_team_holidays === 1) {
            $parts[] = 'Ignora feriados';
        }

        return implode(' | ', $parts);
    }
}
