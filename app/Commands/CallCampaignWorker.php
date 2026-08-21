<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CallCampaignWorker extends BaseCommand
{
    protected $group = 'Custom';
    protected $name = 'call:campaigns';
    protected $description = 'Process call campaigns - place WhatsApp voice calls to leads';
    protected $usage = 'spark call:campaigns';
    protected $arguments = [];
    protected $options = [];

    const TB_CAMPAIGNS = 'sp_call_campaigns';
    const TB_LEADS = 'sp_call_leads';
    const TB_AUDIOS = 'sp_call_audios';

    public function run(array $params)
    {
        CLI::write('[CallCampaignWorker] Starting...', 'green');
        $db = \Config\Database::connect();

        while (true) {
            try {
                // Reconcilia leads em `ringing` de TODAS as campanhas ativas
                // (não-bloqueante). Cobre o modo simultâneo (que não faz poll) e
                // qualquer lead órfão que tenha ficado para trás.
                $this->reconcileRingingLeads($db);

                // Find running campaigns
                $campaigns = $db->table(self::TB_CAMPAIGNS)
                    ->whereIn('status', ['running', 'scheduled'])
                    ->get()->getResult();

                foreach ($campaigns as $campaign) {
                    $this->processCampaign($db, $campaign);
                }
            } catch (\Throwable $e) {
                CLI::write('[CallCampaignWorker] Error: ' . $e->getMessage(), 'red');
                @file_put_contents(WRITEPATH . 'logs/call_campaign_worker.log', 
                    date('Y-m-d H:i:s') . ' | ERROR | ' . $e->getMessage() . "\n", FILE_APPEND);
            }

            sleep(3);
        }
    }

    private function processCampaign($db, $campaign)
    {
        // Verificar agendamento inicial (schedule_start)
        if ($campaign->status === 'scheduled' && !empty($campaign->schedule_start)) {
            $start = strtotime($campaign->schedule_start);
            if ($start > time()) {
                return; // Ainda não é hora
            }
            $db->table(self::TB_CAMPAIGNS)->where('id', $campaign->id)->update(['status' => 'running']);
            $campaign->status = 'running';
        }

        // Verificar janela de agendamento (dias/horários)
        if (!$this->isWithinScheduleWindow($campaign)) {
            return;
        }

        // Check if all leads are done
        $pending = $db->table(self::TB_LEADS)
            ->where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->countAllResults();

        if ($pending == 0) {
            $ringing = $db->table(self::TB_LEADS)
                ->where('campaign_id', $campaign->id)
                ->where('status', 'ringing')
                ->countAllResults();
            if ($ringing == 0) {
                $db->table(self::TB_CAMPAIGNS)->where('id', $campaign->id)->update(['status' => 'completed']);
                CLI::write("[CallCampaignWorker] Campaign {$campaign->id} completed", 'green');
            }
            return;
        }

        // Check max concurrent
        $ringing = $db->table(self::TB_LEADS)
            ->where('campaign_id', $campaign->id)
            ->where('status', 'ringing')
            ->countAllResults();

        $mode = $campaign->call_mode ?? 'fila';
        $instanceIds = !empty($campaign->instance_ids) ? json_decode($campaign->instance_ids, true) : [$campaign->instance_id];
        if (empty($instanceIds)) $instanceIds = [$campaign->instance_id];

        if ($mode === 'simultaneo') {
            // SIMULTÂNEO: N chamadas ao mesmo tempo (1 por instância)
            $availableSlots = count($instanceIds) - $ringing;
            if ($availableSlots <= 0) return;

            $leads = $db->table(self::TB_LEADS)
                ->where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->orderBy('RAND()')
                ->limit($availableSlots)
                ->get()->getResult();

            if (empty($leads)) return;

            // Build batch
            $batch = [];
            $goBaseUrl = $this->getGoBaseUrl();
            $audioInfo = $this->getAudioInfo($db, $campaign);

            foreach ($leads as $i => $lead) {
                $targetInstance = $instanceIds[$i % count($instanceIds)];
                $db->table(self::TB_LEADS)->where('id', $lead->id)->update([
                    'status' => 'ringing', 'started_at' => date('Y-m-d H:i:s'),
                ]);
                $payload = ['instance_id' => $targetInstance, 'phone' => $lead->phone];
                if ($audioInfo) { $payload['audio_path'] = $audioInfo['path']; $payload['audio_duration'] = $audioInfo['duration']; }
                if (!empty($campaign->timeout_ring)) { $payload['ring_timeout'] = (int)$campaign->timeout_ring; }
                $batch[] = [
                    'lead_id' => $lead->id,
                    'campaign_id' => $campaign->id,
                    'url' => $goBaseUrl . '/call/start',
                    'payload' => $payload,
                ];
            }

            CLI::write("[CallCampaignWorker] SIMULTÂNEO: " . count($batch) . " chamadas simultâneas", 'magenta');
            $results = $this->goApiMultiPost($batch);

            foreach ($results as $r) {
                if ($r['success']) {
                    $db->table(self::TB_LEADS)->where('id', $r['lead_id'])->update(['call_id' => $r['call_id']]);
                    $db->table(self::TB_CAMPAIGNS)->where('id', $r['campaign_id'])->set('calls_made', 'calls_made + 1', false)->update();
                } else {
                    $db->table(self::TB_LEADS)->where('id', $r['lead_id'])->update([
                        'status' => 'failed', 'error_message' => $r['error'], 'ended_at' => date('Y-m-d H:i:s'),
                    ]);
                    $db->table(self::TB_CAMPAIGNS)->where('id', $r['campaign_id'])->set('calls_failed', 'calls_failed + 1', false)->update();
                }
            }

            // Delay antes do próximo lote
            $delayMin = max(5, (int)($campaign->delay_min ?? 10));
            $delayMax = max($delayMin, (int)($campaign->delay_max ?? 60));
            $delay = rand($delayMin, $delayMax);
            CLI::write("[CallCampaignWorker] Waiting {$delay}s before next batch...", 'cyan');
            sleep($delay);

        } elseif ($mode === 'alternado') {
            // ALTERNADO: 1 chamada por vez, alterna instância
            if ($ringing >= 1) return;

            $lead = $db->table(self::TB_LEADS)
                ->where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->orderBy('RAND()')->limit(1)->get()->getRow();
            if (!$lead) return;

            // Count already processed leads to pick instance
            $done = $db->table(self::TB_LEADS)
                ->where('campaign_id', $campaign->id)
                ->whereIn('status', ['answered', 'no_answer', 'busy', 'failed'])
                ->countAllResults();
            $targetInstance = $instanceIds[$done % count($instanceIds)];

            $this->placeCall($db, $campaign, $lead, $targetInstance);

        } else {
            // FILA: 1 chamada por vez, 1ª instância
            if ($ringing >= 1) return;

            $lead = $db->table(self::TB_LEADS)
                ->where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->orderBy('RAND()')->limit(1)->get()->getRow();
            if (!$lead) return;

            $this->placeCall($db, $campaign, $lead, $campaign->instance_id);
        }
    }

    private function placeCall($db, $campaign, $lead, $targetInstanceId)
    {
        $db->table(self::TB_LEADS)->where('id', $lead->id)->update([
            'status' => 'ringing', 'started_at' => date('Y-m-d H:i:s'),
        ]);

        $goBaseUrl = $this->getGoBaseUrl();
        $payload = ['instance_id' => $targetInstanceId, 'phone' => $lead->phone];

        $audioInfo = $this->getAudioInfo($db, $campaign);

        $payload = ['instance_id' => $targetInstanceId, 'phone' => $lead->phone];
        if ($audioInfo) { $payload['audio_path'] = $audioInfo['path']; $payload['audio_duration'] = $audioInfo['duration']; }
        if (!empty($campaign->timeout_ring)) { $payload['ring_timeout'] = (int)$campaign->timeout_ring; }

        CLI::write("[CallCampaignWorker] Calling {$lead->phone} (campaign {$campaign->id})", 'yellow');
        $result = $this->goApiPost($goBaseUrl . '/call/start', $payload);

        if (!$result || ($result->status ?? '') !== 'success') {
            $errMsg = $result->message ?? 'Go API error';
            $db->table(self::TB_LEADS)->where('id', $lead->id)->update([
                'status' => 'failed',
                'error_message' => $errMsg,
                'last_error' => $errMsg,
                'ended_at' => date('Y-m-d H:i:s'),
            ]);
            $db->table(self::TB_CAMPAIGNS)->where('id', $campaign->id)->set('calls_failed', 'calls_failed + 1', false)->update();
            // Registra o evento de falha na timeline (se a tabela existir).
            $this->persistEvent($db, $campaign->id, $lead->id, '', 'failed', null, $errMsg);
            CLI::write("[CallCampaignWorker] Call failed: {$errMsg}", 'red');
            return;
        }

        $callId = $result->call_id ?? '';
        $db->table(self::TB_LEADS)->where('id', $lead->id)->update(['call_id' => $callId]);
        $db->table(self::TB_CAMPAIGNS)->where('id', $campaign->id)->set('calls_made', 'calls_made + 1', false)->update();

        // Registra o evento "placed" (ligação realizada) imediatamente.
        $this->persistEvent($db, $campaign->id, $lead->id, $callId, 'placed', null, null);

        $this->pollCallResult($db, $campaign->id, $lead->id, $callId, $campaign->timeout_ring + 60);

        $delayMin = max(5, (int)($campaign->delay_min ?? 10));
        $delayMax = max($delayMin, (int)($campaign->delay_max ?? 60));
        $delay = rand($delayMin, $delayMax);
        CLI::write("[CallCampaignWorker] Waiting {$delay}s ({$delayMin}-{$delayMax}s) before next call...", 'cyan');
        sleep($delay);
    }

    private function getAudioInfo($db, $campaign)
    {
        if (empty($campaign->audio_id)) return null;
        $audio = $db->table(self::TB_AUDIOS)->where('id', $campaign->audio_id)->get()->getRow();
        if ($audio && file_exists($audio->file_path)) {
            $duration = (int)($audio->duration_seconds ?? 0);
            if ($duration <= 0) {
                include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Helpers/Whatsapp_call_campaign_helper.php';
                if (function_exists('call_get_audio_file_duration')) {
                    $duration = call_get_audio_file_duration($audio->file_path);
                    if ($duration > 0) {
                        try {
                            $db->table(self::TB_AUDIOS)->where('id', $audio->id)->update(['duration_seconds' => $duration]);
                        } catch (\Throwable $e) {}
                    }
                }
            }
            return ['path' => $audio->file_path, 'duration' => $duration];
        }
        return null;
    }

    private function goApiMultiPost(array $batch): array
    {
        $results = [];
        $multi = curl_multi_init();
        $chs = [];

        foreach ($batch as $item) {
            $ch = curl_init($item['url']);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($item['payload']),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 30,
            ]);
            curl_multi_add_handle($multi, $ch);
            $chs[] = ['ch' => $ch, 'lead_id' => $item['lead_id'], 'campaign_id' => $item['campaign_id']];
        }

        // Execute all simultaneously
        $running = null;
        do {
            curl_multi_exec($multi, $running);
            curl_multi_select($multi);
        } while ($running > 0);

        foreach ($chs as $item) {
            $body = curl_multi_getcontent($item['ch']);
            $data = $body ? json_decode($body) : null;
            $ok = $data && ($data->status ?? '') === 'success';
            $results[] = [
                'lead_id' => $item['lead_id'],
                'campaign_id' => $item['campaign_id'],
                'success' => $ok,
                'call_id' => $data->call_id ?? '',
                'error' => $data->message ?? 'Go API error',
            ];
            curl_multi_remove_handle($multi, $item['ch']);
            curl_close($item['ch']);
        }

        curl_multi_close($multi);
        return $results;
    }

    private function pollCallResult($db, $campaignId, $leadId, $callId, $timeout)
    {
        $start = time();
        while ((time() - $start) < $timeout) {
            sleep(3);

            $goBaseUrl = $this->getGoBaseUrl();
            $result = $this->goApiGet($goBaseUrl . '/call/status?call_id=' . urlencode($callId));

            if (!$result) continue;

            $status = $result->status ?? '';

            if ($status === 'active' || $status === 'answered') {
                $db->table(self::TB_LEADS)
                    ->where('id', $leadId)
                    ->update([
                        'status' => 'answered',
                        'answered_at' => date('Y-m-d H:i:s'),
                        'platform' => $this->normalizePlatform($result->platform ?? ''),
                    ]);
                $db->table(self::TB_CAMPAIGNS)
                    ->where('id', $campaignId)
                    ->set('calls_answered', 'calls_answered + 1', false)
                    ->update();
                $this->persistTimeline($db, $campaignId, $leadId, $callId, $result);
                CLI::write("[CallCampaignWorker] Call answered!", 'green');
                return;
            }

            if ($status === 'ended') {
                $reason = $result->reason ?? '';
                $duration = 0;
                if (!empty($result->answered_at) && !empty($result->ended_at)) {
                    $duration = strtotime($result->ended_at) - strtotime($result->answered_at);
                }

                $this->persistTimeline($db, $campaignId, $leadId, $callId, $result);
                $outcome = $this->classifyOutcome($status, $reason, $duration, $result);
                $finalStatus = $outcome['status'];

                $db->table(self::TB_LEADS)
                    ->where('id', $leadId)
                    ->update([
                        'status' => $finalStatus,
                        'ended_at' => date('Y-m-d H:i:s'),
                        'duration_seconds' => $duration,
                        'error_message' => $reason ?: null,
                        'hangup_source' => $outcome['hangup_source'],
                        'heard_full_audio' => !empty($result->heard_full_audio) ? 1 : $outcome['heard_full_audio'],
                        'ring_duration_seconds' => (int)($result->ring_duration_seconds ?? 0),
                        'last_error' => $reason ?: null,
                        'platform' => $this->normalizePlatform($result->platform ?? ''),
                    ]);

                if ($finalStatus === 'answered') {
                    $db->table(self::TB_CAMPAIGNS)->where('id', $campaignId)
                        ->set('calls_answered', 'calls_answered + 1', false)->update();
                } elseif ($finalStatus === 'busy') {
                    $db->table(self::TB_CAMPAIGNS)->where('id', $campaignId)
                        ->set('calls_busy', 'calls_busy + 1', false)->update();
                } elseif ($finalStatus === 'failed') {
                    $db->table(self::TB_CAMPAIGNS)->where('id', $campaignId)
                        ->set('calls_failed', 'calls_failed + 1', false)->update();
                } else {
                    $db->table(self::TB_CAMPAIGNS)->where('id', $campaignId)
                        ->set('calls_no_answer', 'calls_no_answer + 1', false)->update();
                }

                CLI::write("[CallCampaignWorker] Call ended: {$finalStatus} ({$duration}s)", 'yellow');
                return;
            }
        }

        // Timeout - cancela no gateway e marca como failed (evita chamada fantasma)
        $this->goApiPost($this->getGoBaseUrl() . '/call/cancel', ['call_id' => $callId]);

        $db->table(self::TB_LEADS)
            ->where('id', $leadId)
            ->update([
                'status' => 'failed',
                'error_message' => 'Timeout waiting for call result',
                'ended_at' => date('Y-m-d H:i:s'),
                'hangup_source' => 'worker',
                'last_error' => 'Timeout waiting for call result',
            ]);
        $db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaignId)
            ->set('calls_failed', 'calls_failed + 1', false)
            ->update();
    }

    /**
     * Reconcilia, de forma não-bloqueante, todos os leads em `ringing` das
     * campanhas ativas. É o que garante o fechamento no modo simultâneo e a
     * recuperação de leads órfãos. Lê a timeline do /call/status e persiste.
     */
    private function reconcileRingingLeads($db)
    {
        try {
            $campaigns = $db->table(self::TB_CAMPAIGNS)
                ->whereIn('status', ['running', 'scheduled'])
                ->get()->getResult();
            if (empty($campaigns)) return;

            $campaignIds = array_map(fn($c) => $c->id, $campaigns);
            $leads = $db->table(self::TB_LEADS)
                ->whereIn('campaign_id', $campaignIds)
                ->where('status', 'ringing')
                ->where('call_id !=', '')
                ->get()->getResult();
            if (empty($leads)) return;

            $goBaseUrl = $this->getGoBaseUrl();
            foreach ($leads as $lead) {
                $result = $this->goApiGet($goBaseUrl . '/call/status?call_id=' . urlencode($lead->call_id));
                if (!$result) continue;
                $status = $result->status ?? '';
                if ($status === 'ringing') continue; // ainda tocando

                if ($status === 'active' || $status === 'answered') {
                    $db->table(self::TB_LEADS)->where('id', $lead->id)->update([
                        'status' => 'answered',
                        'answered_at' => date('Y-m-d H:i:s'),
                        'platform' => $this->normalizePlatform($result->platform ?? ''),
                    ]);
                    $db->table(self::TB_CAMPAIGNS)->where('id', $lead->campaign_id)
                        ->set('calls_answered', 'calls_answered + 1', false)->update();
                    $this->persistTimeline($db, $lead->campaign_id, $lead->id, $lead->call_id, $result);
                    continue;
                }

                if ($status === 'ended') {
                    $reason = $result->reason ?? '';
                    $duration = 0;
                    if (!empty($result->answered_at) && !empty($result->ended_at)) {
                        $duration = strtotime($result->ended_at) - strtotime($result->answered_at);
                    }
                    $this->persistTimeline($db, $lead->campaign_id, $lead->id, $lead->call_id, $result);
                    $outcome = $this->classifyOutcome($status, $reason, $duration, $result);
                    $finalStatus = $outcome['status'];

                    $db->table(self::TB_LEADS)->where('id', $lead->id)->update([
                        'status' => $finalStatus,
                        'ended_at' => date('Y-m-d H:i:s'),
                        'duration_seconds' => $duration,
                        'error_message' => $reason ?: null,
                        'hangup_source' => $outcome['hangup_source'],
                        'heard_full_audio' => !empty($result->heard_full_audio) ? 1 : $outcome['heard_full_audio'],
                        'ring_duration_seconds' => (int)($result->ring_duration_seconds ?? 0),
                        'last_error' => $reason ?: null,
                        'platform' => $this->normalizePlatform($result->platform ?? ''),
                    ]);

                    if ($finalStatus === 'answered') {
                        $db->table(self::TB_CAMPAIGNS)->where('id', $lead->campaign_id)
                            ->set('calls_answered', 'calls_answered + 1', false)->update();
                    } elseif ($finalStatus === 'busy') {
                        $db->table(self::TB_CAMPAIGNS)->where('id', $lead->campaign_id)
                            ->set('calls_busy', 'calls_busy + 1', false)->update();
                    } elseif ($finalStatus === 'failed') {
                        $db->table(self::TB_CAMPAIGNS)->where('id', $lead->campaign_id)
                            ->set('calls_failed', 'calls_failed + 1', false)->update();
                    } else {
                        $db->table(self::TB_CAMPAIGNS)->where('id', $lead->campaign_id)
                            ->set('calls_no_answer', 'calls_no_answer + 1', false)->update();
                    }
                }
            }
        } catch (\Throwable $e) {
            // Reconciliação nunca deve derrubar o worker.
            CLI::write('[CallCampaignWorker] Reconcile error: ' . $e->getMessage(), 'yellow');
        }
    }

    /**
     * Classifica o desfecho da chamada a partir do status, reason e timeline.
     * Deleta a lógica para a Library pura CallOutcome (testável sem gateway).
     */
    private function classifyOutcome(string $status, string $reason, int $duration, $result): array
    {
        $timeline = $result->timeline ?? null;
        $events = [];
        if (is_array($timeline)) {
            foreach ($timeline as $ev) {
                $events[] = [
                    'event' => $ev->event ?? '',
                    'platform' => $ev->platform ?? '',
                    'reason' => $ev->reason ?? '',
                ];
            }
        }

        include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Libraries/CallOutcome.php';
        if (class_exists('Core\Whatsapp_call_campaign\Libraries\CallOutcome')) {
            return \Core\Whatsapp_call_campaign\Libraries\CallOutcome::classify($status, $reason, $duration, $events);
        }

        // Fallback inline (nunca deve quebrar se a Library não carregar).
        return [
            'status' => 'no_answer',
            'hangup_source' => null,
            'heard_full_audio' => 0,
        ];
    }

    /**
     * Persiste a timeline do /call/status na tabela sp_call_events, se existir.
     * É aditiva e protegida: se a migração ainda não rodou, falha silenciosamente.
     */
    private function persistTimeline($db, $campaignId, $leadId, $callId, $result)
    {
        try {
            $timeline = $result->timeline ?? null;
            if (!is_array($timeline)) return;

            $existing = $db->table('sp_call_events')
                ->where('call_id', $callId)
                ->get()->getResult();
            $existingEvents = array_column($existing, 'event');

            foreach ($timeline as $ev) {
                $evt = $ev->event ?? '';
                if ($evt === '' || in_array($evt, $existingEvents, true)) continue;

                $this->persistEvent(
                    $db, $campaignId, $leadId, $callId, $evt,
                    $ev->platform ?? null, $ev->reason ?? null,
                    $ev->at ?? null
                );
            }
        } catch (\Throwable $e) {
            // sp_call_events pode não existir ainda (migração pendente).
        }
    }

    /**
     * Insere um único evento na timeline (sp_call_events), protegido contra
     * duplicidade (UNIQUE KEY call_id+event) e contra tabela ausente.
     */
    private function persistEvent($db, $campaignId, $leadId, $callId, $event, $platform, $reason, $at = null)
    {
        try {
            $createdAt = $at ? date('Y-m-d H:i:s', strtotime($at)) : date('Y-m-d H:i:s');
            $db->table('sp_call_events')->insert([
                'campaign_id' => $campaignId,
                'lead_id' => $leadId,
                'call_id' => $callId ?: 'legacy-' . $leadId . '-' . $event,
                'event' => $event,
                'platform' => $this->normalizePlatform($platform),
                'reason' => $reason ?: null,
                'created_at' => $createdAt,
            ]);
        } catch (\Throwable $e) {
            // Duplicidade (UNIQUE KEY) ou tabela ausente — ignora silenciosamente.
        }
    }

    private function normalizePlatform(?string $platform): ?string
    {
        include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Libraries/CallOutcome.php';
        if (class_exists('Core\Whatsapp_call_campaign\Libraries\CallOutcome')) {
            return \Core\Whatsapp_call_campaign\Libraries\CallOutcome::normalizePlatform($platform);
        }
        if (empty($platform)) return null;
        $p = strtolower(trim($platform));
        if (strncmp($p, 'sm', 2) === 0) return 'mobile';
        return 'web';
    }

    private function isWithinScheduleWindow($campaign): bool
    {
        // Se não tem agendamento configurado, sempre pode rodar
        if (empty($campaign->schedule_time) && empty($campaign->schedule_weekdays) && empty($campaign->skip_team_holidays)) {
            return true;
        }

        $tz = !empty($campaign->timezone) ? $campaign->timezone : date_default_timezone_get();
        try {
            $now = new \DateTime('now', new \DateTimeZone($tz));
        } catch (\Throwable $e) {
            $now = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        }

        // Verificar horários permitidos
        $hours = json_decode($campaign->schedule_time ?? '[]', true) ?? [];
        if (!empty($hours) && !in_array((string)$now->format('G'), $hours, true)) {
            return false;
        }

        // Verificar dias da semana
        $weekdays = json_decode($campaign->schedule_weekdays ?? '[]', true) ?? [];
        if (!empty($weekdays) && !in_array((string)$now->format('N'), $weekdays, true)) {
            return false;
        }

        // Verificar feriados
        if (!empty($campaign->skip_team_holidays) && (int)$campaign->skip_team_holidays === 1) {
            try {
                $db = \Config\Database::connect();
                $holidays = $db->table('sp_team_holidays')
                    ->select('holiday_date')
                    ->where('team_id', $campaign->team_id)
                    ->get()->getResult();
                $holidayDates = array_map(fn($r) => $r->holiday_date, $holidays);
                if (in_array($now->format('Y-m-d'), $holidayDates, true)) {
                    return false;
                }
            } catch (\Throwable $e) {}
        }

        return true;
    }

    private function getGoBaseUrl(): string
    {
        $cfgPaths = [
            ROOTPATH . 'app_zapmatic_whatsmeow_api/config.json',
        ];
        foreach ($cfgPaths as $path) {
            if (file_exists($path)) {
                $cfg = json_decode(file_get_contents($path), true);
                if (!empty($cfg['port'])) {
                    return 'http://127.0.0.1:' . $cfg['port'];
                }
            }
        }
        return 'http://127.0.0.1:8090';
    }

    private function goApiPost(string $url, array $body)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ? json_decode($result) : null;
    }

    private function goApiGet(string $url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ? json_decode($result) : null;
    }
}
