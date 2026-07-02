<?php

if (!function_exists('whatsapp_bulk_schedule_weekday_options')) {
    function whatsapp_bulk_schedule_weekday_options(): array
    {
        return [
            '1' => ['short' => 'Seg', 'label' => 'Segunda-feira'],
            '2' => ['short' => 'Ter', 'label' => 'Terça-feira'],
            '3' => ['short' => 'Qua', 'label' => 'Quarta-feira'],
            '4' => ['short' => 'Qui', 'label' => 'Quinta-feira'],
            '5' => ['short' => 'Sex', 'label' => 'Sexta-feira'],
            '6' => ['short' => 'Sáb', 'label' => 'Sábado'],
            '7' => ['short' => 'Dom', 'label' => 'Domingo'],
        ];
    }
}

if (!function_exists('whatsapp_bulk_decode_schedule_values')) {
    function whatsapp_bulk_decode_schedule_values($values): array
    {
        if (is_string($values)) {
            $values = trim($values);
            if ($values === '') {
                return [];
            }

            $decoded = json_decode($values, true);
            if (is_array($decoded)) {
                $values = $decoded;
            } else {
                $values = array_filter(array_map('trim', explode(',', $values)), static function ($value) {
                    return $value !== '';
                });
            }
        }

        if (is_object($values)) {
            $values = (array) $values;
        }

        if (!is_array($values)) {
            return [];
        }

        return array_values($values);
    }
}

if (!function_exists('whatsapp_bulk_normalize_schedule_hours')) {
    function whatsapp_bulk_normalize_schedule_hours($schedule_time): array
    {
        $normalized = [];

        foreach (whatsapp_bulk_decode_schedule_values($schedule_time) as $value) {
            $hour = (int) $value;
            if ($hour < 0 || $hour > 23) {
                continue;
            }

            $normalized[(string) $hour] = (string) $hour;
        }

        $normalized = array_values($normalized);
        sort($normalized, SORT_NUMERIC);

        return $normalized;
    }
}

if (!function_exists('whatsapp_bulk_normalize_schedule_weekdays')) {
    function whatsapp_bulk_normalize_schedule_weekdays($schedule_weekdays): array
    {
        $normalized = [];

        foreach (whatsapp_bulk_decode_schedule_values($schedule_weekdays) as $value) {
            $weekday = (int) $value;
            if ($weekday < 1 || $weekday > 7) {
                continue;
            }

            $normalized[(string) $weekday] = (string) $weekday;
        }

        $normalized = array_values($normalized);
        sort($normalized, SORT_NUMERIC);

        return $normalized;
    }
}

if (!function_exists('whatsapp_bulk_describe_schedule_weekdays')) {
    function whatsapp_bulk_describe_schedule_weekdays(array $weekdays, array $weekday_options = []): string
    {
        $weekday_options = !empty($weekday_options) ? $weekday_options : whatsapp_bulk_schedule_weekday_options();
        $weekdays = array_values(array_unique(array_map('strval', $weekdays)));

        if (empty($weekdays) || implode(',', $weekdays) === '1,2,3,4,5,6,7') {
            return 'Todos os dias';
        }

        $joined = implode(',', $weekdays);
        if ($joined === '1,2,3,4,5') {
            return 'Seg-Sex';
        }

        if ($joined === '6,7') {
            return 'Sáb-Dom';
        }

        $labels = [];
        foreach ($weekdays as $weekday) {
            $labels[] = $weekday_options[$weekday]['short'] ?? $weekday;
        }

        return implode(', ', $labels);
    }
}

if (!function_exists('whatsapp_bulk_describe_schedule_hours')) {
    function whatsapp_bulk_describe_schedule_hours(array $hours): string
    {
        $hours = array_values(array_unique(array_map('strval', $hours)));
        if (empty($hours)) {
            return 'Qualquer horário';
        }

        return implode(',', array_map(static function ($hour) {
            return str_pad((string) ((int) $hour), 2, '0', STR_PAD_LEFT);
        }, $hours));
    }
}

if (!function_exists('whatsapp_bulk_schedule_window_meta')) {
    function whatsapp_bulk_schedule_window_meta($schedule_time, $schedule_weekdays, $skip_team_holidays = 0, array $weekday_options = []): array
    {
        $weekday_options = !empty($weekday_options) ? $weekday_options : whatsapp_bulk_schedule_weekday_options();
        $hours = whatsapp_bulk_normalize_schedule_hours($schedule_time);
        $weekdays = whatsapp_bulk_normalize_schedule_weekdays($schedule_weekdays);
        $skip_team_holidays = (int) $skip_team_holidays === 1;
        $has_rules = !empty($hours) || !empty($weekdays) || $skip_team_holidays;

        $days_label = whatsapp_bulk_describe_schedule_weekdays($weekdays, $weekday_options);
        $hours_label = whatsapp_bulk_describe_schedule_hours($hours);
        $holiday_short_label = 'Feriados ON';
        $holiday_full_label = $skip_team_holidays ? 'Feriados: ON' : 'Feriados: OFF';
        $empty_label = 'Sem restrição adicional. A campanha poderá rodar a qualquer momento.';

        $short_parts = [$days_label, $hours_label];
        if ($skip_team_holidays) {
            $short_parts[] = $holiday_short_label;
        }

        return [
            'hours' => $hours,
            'weekdays' => $weekdays,
            'skip_team_holidays' => $skip_team_holidays,
            'has_rules' => $has_rules,
            'days_label' => $days_label,
            'hours_label' => $hours_label,
            'holiday_short_label' => $holiday_short_label,
            'holiday_full_label' => $holiday_full_label,
            'empty' => $empty_label,
            'short' => $has_rules ? implode(' | ', $short_parts) : '',
            'full' => $has_rules
                ? implode(' | ', [
                    'Dias: ' . $days_label,
                    'Horários: ' . $hours_label,
                    $holiday_full_label,
                ])
                : $empty_label,
        ];
    }
}

if (!function_exists('whatsapp_bulk_is_cloud_parallel')) {
    function whatsapp_bulk_is_cloud_parallel($schedule): bool
    {
        return (int) (is_object($schedule) ? ($schedule->cloud_parallel_enabled ?? 0) : ($schedule['cloud_parallel_enabled'] ?? 0)) === 1;
    }
}

if (!function_exists('whatsapp_bulk_fetch_cloud_dispatches')) {
    function whatsapp_bulk_fetch_cloud_dispatches(int $schedule_id): array
    {
        if ($schedule_id <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_CLOUD_DISPATCHES);
        $builder->select('*');
        $builder->where('schedule_id', $schedule_id);
        $builder->orderBy('id', 'ASC');
        $query = $builder->get();
        $rows = $query->getResult();
        $query->freeResult();

        return $rows ?: [];
    }
}

if (!function_exists('whatsapp_bulk_fetch_cloud_message_statuses')) {
    function whatsapp_bulk_fetch_cloud_message_statuses(int $schedule_id): array
    {
        if ($schedule_id <= 0) {
            return [];
        }

	        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_MESSAGE_STATUS);
        $builder->select('wa_message_id, status, meta_error_code, meta_error_title, meta_error_details, last_status_at');
        $builder->where('schedule_id', $schedule_id);
        $builder->orderBy('last_status_at', 'DESC');
        $query = $builder->get();
        $rows = $query->getResult();
        $query->freeResult();

        $statuses = [];
        foreach ($rows ?: [] as $row) {
            $wa_message_id = (string) ($row->wa_message_id ?? '');
            if ($wa_message_id === '' || isset($statuses[$wa_message_id])) {
                continue;
            }

            $statuses[$wa_message_id] = $row;
        }

        return $statuses;
    }
}

if (!function_exists('whatsapp_bulk_baileys_failure_message')) {
    function whatsapp_bulk_baileys_failure_message(bool $is_call_campaign = false): string
    {
        if ($is_call_campaign) {
            return 'Falha na ligação. Verifique se o número possui WhatsApp ativo, se o número conectado está banido/desconectado, ou se o número informado não existe.';
        }

        return 'Falha no envio. Verifique se o número possui WhatsApp ativo, se o número conectado está banido/desconectado, ou se o número informado não existe.';
    }
}

if (!function_exists('whatsapp_bulk_normalize_legacy_report_message')) {
    function whatsapp_bulk_normalize_legacy_report_message($item, bool $is_call_campaign = false): string
    {
        $status_ok = !empty($item->status);
        $message = trim((string)($item->message ?? ''));

        if ($message === '[object Object]') {
            $message = '';
        }

        if ($status_ok) {
            $success_map = [
                'success' => $is_call_campaign ? 'Ligação iniciada' : 'Enviado com sucesso',
                'sent' => $is_call_campaign ? 'Ligação iniciada' : 'Enviado com sucesso',
                'delivered' => 'Entregue',
                'read' => 'Lido',
            ];

            $key = strtolower($message);
            return $success_map[$key] ?? ($message !== '' ? $message : ($is_call_campaign ? 'Ligação iniciada' : 'Enviado com sucesso'));
        }

        $generic_failures = [
            '',
            '0',
            'false',
            'fail',
            'failed',
            'failure',
            'error',
            'erro',
            'falha',
            'falhou',
            'não enviado',
            'nao enviado',
        ];

        if (in_array(strtolower($message), $generic_failures, true)) {
            return whatsapp_bulk_baileys_failure_message($is_call_campaign);
        }

        return $message;
    }
}

if (!function_exists('whatsapp_bulk_get_report_items')) {
    function whatsapp_bulk_get_report_items($schedule): array
    {
        if (empty($schedule)) {
            return [];
        }

        if (whatsapp_bulk_is_cloud_parallel($schedule)) {
            $dispatches = whatsapp_bulk_fetch_cloud_dispatches((int) ($schedule->id ?? 0));
            $message_statuses = whatsapp_bulk_fetch_cloud_message_statuses((int) ($schedule->id ?? 0));
            $items = [];

            foreach ($dispatches as $dispatch) {
                $state = (string) ($dispatch->status ?? 'queued');
                $timestamp = (int) ($dispatch->last_attempt_at ?: $dispatch->updated ?: $dispatch->created ?: 0);

                $wa_message_id = (string) ($dispatch->wa_message_id ?? '');
                $message_status = ($wa_message_id !== '' && isset($message_statuses[$wa_message_id]))
                    ? $message_statuses[$wa_message_id]
                    : null;

                if ($message_status) {
                    $meta_status = strtolower((string) ($message_status->status ?? 'sent'));
                    $timestamp = max($timestamp, (int) ($message_status->last_status_at ?? 0));

                    if (in_array($meta_status, ['failed', 'deleted'], true)) {
                        $state = 'failed';
                        $status = false;

                        $error_prefix = '';
                        if (!empty($message_status->meta_error_code)) {
                            $error_prefix = '[' . $message_status->meta_error_code . '] ';
                        }

                        $error_title = trim((string) ($message_status->meta_error_title ?? ''));
                        $error_details = trim((string) ($message_status->meta_error_details ?? ''));
                        $message = trim($error_prefix . $error_title);
                        if ($error_details !== '') {
                            $message .= ($message !== '' ? ' - ' : '') . $error_details;
                        }
                        if ($message === '') {
                            $message = 'Falha';
                        }
                    } else {
                        $state = 'sent';
                        $status = true;

                        switch ($meta_status) {
                            case 'read':
                                $message = 'Lido';
                                break;

                            case 'delivered':
                                $message = 'Entregue';
                                break;

                            default:
                                $message = 'Enviado';
                                break;
                        }
                    }

                    $items[] = (object) [
                        'phone_number' => (string) ($dispatch->normalized_phone ?: $dispatch->raw_phone ?: ''),
                        'status' => $status,
                        'message' => $message,
                        'dispatch_state' => $state,
                        'sent_at' => $timestamp,
                        'error_code' => $message_status->meta_error_code ?? null,
                        'wa_message_id' => $dispatch->wa_message_id ?? null,
                    ];

                    continue;
                }

                switch ($state) {
                    case 'sent':
                        $message = 'Enviado';
                        $status = true;
                        break;

                    case 'failed':
                        $message = $dispatch->error_message ?: 'Falha';
                        $status = false;
                        break;

                    case 'retry_wait':
                        $message = $dispatch->error_message ?: 'Retentativa agendada';
                        $status = false;
                        break;

                    case 'processing':
                        $message = 'Processando';
                        $status = false;
                        break;

                    default:
                        $message = 'Na fila';
                        $status = false;
                        break;
                }

                $items[] = (object) [
                    'phone_number' => (string) ($dispatch->normalized_phone ?: $dispatch->raw_phone ?: ''),
                    'status' => $status,
                    'message' => $message,
                    'dispatch_state' => $state,
                    'sent_at' => $timestamp,
                    'error_code' => $dispatch->error_code ?? null,
                    'wa_message_id' => $dispatch->wa_message_id ?? null,
                ];
            }

            return $items;
        }

        if (empty($schedule->result)) {
            return [];
        }

        $items = json_decode($schedule->result, false);
        if (!is_array($items)) {
            return [];
        }

        $is_call_campaign = (int)($schedule->type ?? 1) === 7;
        foreach ($items as $item) {
            if (!is_object($item)) {
                continue;
            }

            $item->message = whatsapp_bulk_normalize_legacy_report_message($item, $is_call_campaign);
        }

        return $items;
    }
}

if (!function_exists('whatsapp_bulk_count_report_items')) {
    function whatsapp_bulk_count_report_items(array $items): array
    {
        $summary = [
            'success' => 0,
            'failed' => 0,
            'pending' => 0,
            'total' => 0,
        ];

        foreach ($items as $item) {
            if (!is_object($item)) {
                continue;
            }

            $summary['total']++;
            $state = (string) ($item->dispatch_state ?? '');

            if ($state !== '') {
                if ($state === 'sent') {
                    $summary['success']++;
                } elseif ($state === 'failed') {
                    $summary['failed']++;
                } else {
                    $summary['pending']++;
                }
                continue;
            }

            if (!empty($item->status)) {
                $summary['success']++;
            } else {
                $summary['failed']++;
            }
        }

        return $summary;
    }
}

if (!function_exists('whatsapp_bulk_format_report_timestamp')) {
    function whatsapp_bulk_format_report_timestamp($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_numeric($value)) {
            $timestamp = (int) $value;
            return $timestamp > 0 ? date('d/m/Y H:i:s', $timestamp) : '-';
        }

        $timestamp = strtotime((string) $value);
        return $timestamp ? date('d/m/Y H:i:s', $timestamp) : '-';
    }
}
