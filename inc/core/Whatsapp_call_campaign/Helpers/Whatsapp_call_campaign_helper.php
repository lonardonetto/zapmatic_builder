<?php

if (!function_exists('call_label_event')) {
    /**
     * Traduz o nome do evento da timeline para português amigável.
     */
    function call_label_event(string $event): string
    {
        $map = [
            'placed'          => 'Ligação realizada',
            'preaccepted'     => 'Aparelho do contato tocou',
            'accepted'        => 'Atendeu',
            'answered'        => 'Atendeu (áudio em fluxo)',
            'audio_started'   => 'Começou a tocar o áudio',
            'audio_finished'  => 'Áudio concluído',
            'hangup_scheduled'=> 'Desligamento automático agendado',
            'ended'           => 'Chamada encerrada',
            'failed'          => 'Falha na ligação',
            'rejected'        => 'Chamada recusada',
            'terminated'      => 'Chamada encerrada',
            'ring_timeout'    => 'Não atendeu (tempo esgotado)',
        ];
        return $map[$event] ?? $event;
    }
}

if (!function_exists('call_label_status')) {
    /**
     * Traduz o status do lead para português amigável.
     */
    function call_label_status(string $status): string
    {
        $map = [
            'pending'   => 'Pendente',
            'ringing'   => 'Chamando',
            'answered'  => 'Atendeu',
            'no_answer' => 'Não atendeu',
            'busy'      => 'Ocupado',
            'failed'    => 'Falhou',
            'cancelled' => 'Cancelada',
        ];
        return $map[$status] ?? $status;
    }
}

if (!function_exists('call_label_platform')) {
    /**
     * Traduz a plataforma do atendimento para português amigável.
     */
    function call_label_platform(?string $platform): string
    {
        $map = [
            'mobile' => '📱 Celular',
            'web'    => '💻 WhatsApp Web',
        ];
        return $map[$platform] ?? '—';
    }
}

if (!function_exists('call_label_reason')) {
    /**
     * Traduz o motivo do encerramento da chamada para português amigável.
     */
    function call_label_reason(?string $reason): string
    {
        if (empty($reason)) return '';
        $lower = strtolower(trim($reason));
        $map = [
            'ring_timeout' => 'não atendeu (tempo esgotado)',
            'hangup'       => 'desligada pelo sistema',
            'rejected'     => 'recusada pelo contato',
            'busy'         => 'linha ocupada',
            'timeout'      => 'tempo esgotado',
        ];
        if (isset($map[$lower])) return $map[$lower];
        // Motivos de servidor: "server:463" -> "erro do servidor (463)"
        if (strpos($lower, 'server:') === 0) {
            return 'erro do servidor (' . substr($reason, 7) . ')';
        }
        if (stripos($lower, 'no devices') !== false || stripos($lower, 'unreachable') !== false) {
            return 'sem WhatsApp / inalcançável';
        }
        return $reason;
    }
}

if (!function_exists('call_label_hangup_source')) {
    /**
     * Traduz a origem do desligamento para português amigável.
     */
    function call_label_hangup_source(?string $source): string
    {
        $map = [
            'auto'         => 'Automático',
            'peer'         => 'Desligado pelo contato',
            'server'       => 'Desligado pelo servidor',
            'ring_timeout' => 'Tempo de toque esgotado',
            'worker'       => 'Cancelado pelo sistema',
        ];
        return $map[$source] ?? ($source ?: '');
    }
}

if (!function_exists('call_translations_json')) {
    /**
     * Devolve o mapa de traduções como JSON para uso no JavaScript da view.
     */
    function call_translations_json(): string
    {
        $data = [
            'events' => [
                'placed' => 'Ligação realizada',
                'preaccepted' => 'Aparelho do contato tocou',
                'accepted' => 'Atendeu',
                'answered' => 'Atendeu (áudio em fluxo)',
                'audio_started' => 'Começou a tocar o áudio',
                'audio_finished' => 'Áudio concluído',
                'hangup_scheduled' => 'Desligamento automático agendado',
                'ended' => 'Chamada encerrada',
                'failed' => 'Falha na ligação',
                'rejected' => 'Chamada recusada',
                'terminated' => 'Chamada encerrada',
                'ring_timeout' => 'Não atendeu (tempo esgotado)',
            ],
            'status' => [
                'pending' => 'Pendente',
                'ringing' => 'Chamando',
                'answered' => 'Atendeu',
                'no_answer' => 'Não atendeu',
                'busy' => 'Ocupado',
                'failed' => 'Falhou',
                'cancelled' => 'Cancelada',
            ],
            'platform' => [
                'mobile' => '📱 Celular',
                'web' => '💻 WhatsApp Web',
            ],
            'reasons' => [
                'ring_timeout' => 'não atendeu (tempo esgotado)',
                'hangup' => 'desligada pelo sistema',
                'rejected' => 'recusada pelo contato',
                'busy' => 'linha ocupada',
                'timeout' => 'tempo esgotado',
            ],
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('call_normalize_phone')) {
    /**
     * Normaliza telefone brasileiro para formato WhatsApp JID.
     *
     * Regra do 9º dígito (Anatel, correta):
     *   - Celulares brasileiros têm 9 dígitos (55 + DDD + 9xxxxxxxx).
     *   - Se o número brasileiro (DDI 55) tem 8 dígitos locais iniciando com
     *     6/7/8/9, ADICIONA o '9' após o DDD.
     *   - NUNCA remove o 9º dígito: removê-lo transforma um celular em número
     *     de fixo (sem WhatsApp) e faz a chamada falhar com "usync no LID".
     */
    function call_normalize_phone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // Adiciona 55 se não tiver (número local com DDD + 8/9 dígitos)
        if (strlen($clean) >= 10 && strlen($clean) <= 11 && substr($clean, 0, 2) !== '55') {
            $clean = '55' . $clean;
        }

        // Adiciona o 9º dígito quando é celular brasileiro com 8 dígitos locais
        if (substr($clean, 0, 2) === '55' && strlen($clean) === 12) {
            $localNumber = substr($clean, 6); // últimos 8 dígitos (após DDI + DDD)
            if (strlen($localNumber) === 8 && in_array(substr($localNumber, 0, 1), ['6', '7', '8', '9'], true)) {
                $clean = substr($clean, 0, 4) . '9' . substr($clean, 4);
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

if (!function_exists('call_schedule_weekday_options')) {
    function call_schedule_weekday_options(): array
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

if (!function_exists('call_decode_schedule_values')) {
    function call_decode_schedule_values($values): array
    {
        if (is_string($values)) {
            $values = trim($values);
            if ($values === '') return [];
            $decoded = json_decode($values, true);
            if (is_array($decoded)) $values = $decoded;
            else $values = explode(',', $values);
        }
        if (!is_array($values)) return [];
        return array_values(array_filter(array_map('strval', $values), fn($v) => $v !== ''));
    }
}

if (!function_exists('call_describe_schedule_weekdays')) {
    function call_describe_schedule_weekdays(array $weekdays): string
    {
        $options = call_schedule_weekday_options();
        $weekdays = array_values(array_unique(array_map('strval', $weekdays)));
        if (empty($weekdays) || implode(',', $weekdays) === '1,2,3,4,5,6,7') return 'Todos os dias';
        if (implode(',', $weekdays) === '1,2,3,4,5') return 'Seg-Sex';
        if (implode(',', $weekdays) === '6,7') return 'Sáb-Dom';
        $labels = [];
        foreach ($weekdays as $d) $labels[] = $options[$d]['short'] ?? $d;
        return implode(', ', $labels);
    }
}

if (!function_exists('call_describe_schedule_hours')) {
    function call_describe_schedule_hours(array $hours): string
    {
        if (empty($hours)) return 'Qualquer horário';
        return implode(', ', array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', $hours));
    }
}

if (!function_exists('call_get_audio_file_duration')) {
    /**
     * Calcula a duração em segundos de um arquivo de áudio (MP3, WAV, OGG, Opus)
     * em PHP puro, sem depender de shell_exec/ffprobe.
     */
    function call_get_audio_file_duration(string $filePath): int
    {
        if (empty($filePath) || !file_exists($filePath)) {
            return 0;
        }

        $fileSize = @filesize($filePath);
        if (!$fileSize || $fileSize <= 0) {
            return 0;
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // 1. WAV
        if ($ext === 'wav') {
            $fp = @fopen($filePath, 'rb');
            if ($fp) {
                $header = fread($fp, 44);
                fclose($fp);
                if (strlen($header) >= 44 && substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WAVE') {
                    $byteRate = ord($header[28]) | (ord($header[29]) << 8) | (ord($header[30]) << 16) | (ord($header[31]) << 24);
                    if ($byteRate > 0) {
                        $dataSize = $fileSize - 44;
                        return max(1, (int) round($dataSize / $byteRate));
                    }
                }
            }
        }

        // 2. MP3
        if ($ext === 'mp3') {
            $fp = @fopen($filePath, 'rb');
            if ($fp) {
                $offset = 0;
                $header = fread($fp, 10);
                // Verifica ID3v2 header
                if (strlen($header) >= 10 && substr($header, 0, 3) === 'ID3') {
                    $id3Size = ((ord($header[6]) & 0x7F) << 21)
                             | ((ord($header[7]) & 0x7F) << 14)
                             | ((ord($header[8]) & 0x7F) << 7)
                             | (ord($header[9]) & 0x7F);
                    $offset = 10 + $id3Size;
                }

                fseek($fp, $offset);
                $audioData = fread($fp, 8192);
                fclose($fp);

                // Procura frame sync (0xFF 0xEx)
                $len = strlen($audioData);
                for ($i = 0; $i < $len - 4; $i++) {
                    if (ord($audioData[$i]) === 0xFF && (ord($audioData[$i + 1]) & 0xE0) === 0xE0) {
                        $b1 = ord($audioData[$i + 1]);
                        $b2 = ord($audioData[$i + 2]);

                        $versionBits = ($b1 >> 3) & 0x03; // 3 = MPEG 1, 2 = MPEG 2, 0 = MPEG 2.5
                        $layerBits   = ($b1 >> 1) & 0x03; // 1 = Layer III, 2 = Layer II, 3 = Layer I
                        $bitrateIdx  = ($b2 >> 4) & 0x0F;

                        // Bitrates para MPEG 1 Layer III (kbps)
                        $bitratesMpeg1Layer3 = [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 0];
                        // Bitrates para MPEG 2/2.5 Layer III (kbps)
                        $bitratesMpeg2Layer3 = [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, 0];

                        $bitrate = 0;
                        if ($versionBits === 3 && $layerBits === 1 && isset($bitratesMpeg1Layer3[$bitrateIdx])) {
                            $bitrate = $bitratesMpeg1Layer3[$bitrateIdx];
                        } elseif ($layerBits === 1 && isset($bitratesMpeg2Layer3[$bitrateIdx])) {
                            $bitrate = $bitratesMpeg2Layer3[$bitrateIdx];
                        }

                        if ($bitrate > 0) {
                            $audioBytes = max(1, $fileSize - $offset);
                            return max(1, (int) round(($audioBytes * 8) / ($bitrate * 1000)));
                        }
                    }
                }
            }
        }

        // 3. Fallback genérico baseado em taxa de 128kbps (16.000 bytes/segundo)
        return max(1, (int) round($fileSize / 16000));
    }
}
