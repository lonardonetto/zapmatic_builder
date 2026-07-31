<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BotWorkerAll extends BaseCommand
{
    protected $group       = 'BotBuilder';
    protected $name        = 'bot:all';
    protected $description = 'Consolidated async daemon: debounce, queue, campaigns, sessions.';

    private const INTERVAL_FAST = 1;
    private const INTERVAL_SLOW = 15;

    private $db;
    private $controller;
    private $lastSlowRun = 0;
    private $cycleCount = 0;

    public function run(array $params)
    {
        CLI::write('Starting Consolidated Bot Worker (bot:all)...', 'green');
        CLI::write('  Tasks: Debounce(1s) Queue(1s) Campaigns(1s) Sessions(15s)', 'yellow');

        $this->db = \Config\Database::connect();

        // Disable query cache if available (CI4 version-specific)
        if (method_exists($this->db, 'disableQueryCache')) {
            $this->db->disableQueryCache();
        }

        $this->controller = new \Core\Bot_builder\Controllers\Bot_builder();

        while (true) {
            $cycleStart = microtime(true);
            $this->cycleCount++;

            try {
                $this->db->reconnect();
                $this->runDebounce();
            } catch (\Throwable $e) {
                log_message('error', "[BotWorkerAll] Debounce Error: {$e->getMessage()}");
            }

            try {
                $this->runQueue();
            } catch (\Throwable $e) {
                log_message('error', "[BotWorkerAll] Queue Error: {$e->getMessage()}");
            }

            try {
                $this->runCampaigns();
            } catch (\Throwable $e) {
                log_message('error', "[BotWorkerAll] Campaign Error: {$e->getMessage()}");
            }

            // Processar atualizacoes de sistema
            try {
                $this->runSystemUpdates();
            } catch (\Throwable $e) {
                log_message('error', "[BotWorkerAll] System Update Error: {$e->getMessage()}");
            }


            if (time() - $this->lastSlowRun >= self::INTERVAL_SLOW) {
                $this->lastSlowRun = time();
                try {
                    $this->runSessions();
                } catch (\Throwable $e) {
                    log_message('error', "[BotWorkerAll] Sessions Error: {$e->getMessage()}");
                }
            }

            gc_collect_cycles();

            $elapsed = (microtime(true) - $cycleStart) * 1000000;
            $sleep = max(50000, 1000000 - (int)$elapsed);
            usleep($sleep);
        }
    }

    // ──────────────────────────────────────────────
    // Fast-Lane 1: Debounce (Message Buffering)
    // ──────────────────────────────────────────────
    private function runDebounce(): void
    {
        $this->controller->process_buffer();
    }

    // ──────────────────────────────────────────────
    // Fast-Lane 2: Queue (Async Delays & Resumes)
    // ──────────────────────────────────────────────
    private function runQueue(): void
    {
        $builder = $this->db->table('sp_message_queue');

        $jobs = $builder->where('status', 'pending')
                        ->where('send_at <=', time())
                        ->limit(30)
                        ->get()->getResult();

        if (empty($jobs)) return;

        $bbModel = new \Core\Bot_builder\Models\Bot_builderModel();
        $controller = $this->controller;

        foreach ($jobs as $job) {
            $builder->where('id', $job->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'processing', 'attempts' => $job->attempts + 1]);

            if ($this->db->affectedRows() === 0) continue;

            try {
                $payload = json_decode($job->payload);

                if ($job->action_type === 'delay_resume') {
                    $session = $bbModel->get_session_by_id($job->session_id);
                    if ($session && $session->is_completed == 0 && $session->bot_status === 'active') {
                        $instance_id = $payload->instance_id ?? $session->instance_id;
                        $controller->run_flow($session, '', 'resume', $instance_id, true);
                    }
                    $builder->where('id', $job->id)->update(['status' => 'completed']);
                }

            } catch (\Throwable $e) {
                $error = $e->getMessage();
                if ($job->attempts >= $job->max_attempts) {
                    $builder->where('id', $job->id)->update(['status' => 'failed', 'error_log' => $error]);
                } else {
                    $builder->where('id', $job->id)->update([
                        'status' => 'pending', 'send_at' => time() + 60, 'error_log' => $error
                    ]);
                }
            }
        }
    }

    // ──────────────────────────────────────────────
    // Fast-Lane 3: Campaign Dispatch
    // ──────────────────────────────────────────────
    private function runCampaigns(): void
    {
        $builder = $this->db->table('sp_campaign_queue');

        $jobs = $builder->where('status', 'pending')
                        ->where('send_at <=', time())
                        ->limit(100)
                        ->get()->getResult();

        if (empty($jobs)) return;

        foreach ($jobs as $job) {
            $builder->where('id', $job->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'processing', 'attempts' => $job->attempts + 1]);

            if ($this->db->affectedRows() === 0) continue;

            try {
                $account = $this->db->table('sp_accounts')->where('id', $job->instance_id)->get()->getRow();
                if (!$account || $account->status != 1) {
                    $builder->where('id', $job->id)->update([
                        'status' => 'failed', 'error_log' => 'Account disconnected or not found.'
                    ]);
                    continue;
                }

                $payload = json_decode($job->payload, true);
                $type = $payload['type'] ?? 'text';

                $response = \App\Services\WhatsAppGatewayService::send(
                    $account->token, $job->recipient_phone, $type, $payload
                );

                $httpCode = $response['http_code'] ?? 0;
                if ($httpCode == 0 && isset($response['raw'])) {
                    if (strpos($response['raw'], '429') !== false || strpos($response['raw'], 'Too Many Requests') !== false) {
                        $httpCode = 429;
                    }
                }

                if ($httpCode == 429 || ($response['status'] == 'error' && strpos(json_encode($response), '429') !== false)) {
                    $backoff = min(900, 60 * pow(2, $job->attempts));
                    $builder->where('id', $job->id)->update([
                        'status' => 'rate_limited', 'send_at' => time() + $backoff,
                        'error_log' => "HTTP 429 Rate Limit. Backoff: {$backoff}s"
                    ]);
                    $this->db->query("UPDATE sp_campaign_queue SET send_at = send_at + ? WHERE instance_id = ? AND status = 'pending'", [$backoff, $job->instance_id]);
                    log_message('warning', "[BotWorkerAll] Circuit Breaker instance {$job->instance_id}: paused {$backoff}s");

                } elseif ($response['status'] === 'success') {
                    $builder->where('id', $job->id)->update(['status' => 'sent', 'error_log' => json_encode($response)]);
                    $this->db->query("UPDATE sp_whatsapp_schedules SET sent = sent + 1 WHERE id = ?", [$job->campaign_id]);

                } else {
                    if ($job->attempts >= $job->max_attempts) {
                        $builder->where('id', $job->id)->update(['status' => 'failed', 'error_log' => json_encode($response)]);
                        $this->db->query("UPDATE sp_whatsapp_schedules SET failed = failed + 1 WHERE id = ?", [$job->campaign_id]);
                    } else {
                        $builder->where('id', $job->id)->update([
                            'status' => 'pending', 'send_at' => time() + 60, 'error_log' => json_encode($response)
                        ]);
                    }
                }

            } catch (\Throwable $e) {
                $error = $e->getMessage();
                log_message('error', "[BotWorkerAll] Campaign Dispatch Error Job {$job->id}: {$error}");
                if ($job->attempts >= $job->max_attempts) {
                    $builder->where('id', $job->id)->update(['status' => 'failed', 'error_log' => $error]);
                    $this->db->query("UPDATE sp_whatsapp_schedules SET failed = failed + 1 WHERE id = ?", [$job->campaign_id]);
                } else {
                    $builder->where('id', $job->id)->update(['status' => 'pending', 'send_at' => time() + 60, 'error_log' => $error]);
                }
            }
        }
    }

    // ──────────────────────────────────────────────
    // System Updates (Background Worker)
    // ──────────────────────────────────────────────
    private function runSystemUpdates(): void
    {
        $row = $this->db->table('sp_system_updates')
            ->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->get()->getRow();

        if (!$row) return;

        $update_id = (int)$row->id;
        $target = (string)$row->to_version;
        $channel = (string)$row->channel;
        
        // Optimistic lock
        $this->db->table('sp_system_updates')->where('id', $update_id)->update(['status' => 'processing']);
        if ($this->db->affectedRows() === 0) return;

        $progressFile = WRITEPATH . 'logs/update_progress_' . $update_id . '.json';
        $setProgress = function ($stage, $percent, $message, $done = false) use ($progressFile) {
            @file_put_contents($progressFile, json_encode([
                'stage' => $stage, 'percent' => $percent,
                'message' => $message, 'done' => $done,
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
        };

        try {
            $setProgress('backup', 5, 'Criando backup do sistema... (pode levar alguns segundos)');
            $updater = new \Core\Plugins\Controllers\System_updater();
            $ref = new \ReflectionMethod($updater, 'create_backup');
            $ref->setAccessible(true);
            $backup_file = $ref->invoke($updater, $row->from_version ?? '0.0.0');
            $setProgress('backup_done', 15, 'Backup criado. Baixando atualização...');

            $setProgress('download', 30, 'Baixando atualização do GitHub...');
            $ref = new \ReflectionMethod($updater, 'apply_git_update');
            $ref->setAccessible(true);
            $ref->invoke($updater, $target, $channel);
            $setProgress('download_done', 65, 'Arquivos aplicados. Migrando banco...');

            $setProgress('migrate', 80, 'Aplicando migrações SQL...');
            $ref = new \ReflectionMethod($updater, 'run_pending_migrations');
            $ref->setAccessible(true);
            $migrations = $ref->invoke($updater);
            $setProgress('migrate_done', 88, 'Banco atualizado. Reiniciando...');

            $setProgress('restart', 92, 'Reiniciando serviços...');
            $ref = new \ReflectionMethod($updater, 'restart_processes');
            $ref->setAccessible(true);
            $ref->invoke($updater);

            $setProgress('version', 96, 'Atualizando versão...');
            $ref = new \ReflectionMethod($updater, 'write_version');
            $ref->setAccessible(true);
            $ref->invoke($updater, $target, $channel);

            $this->db->table('sp_system_updates')->where('id', $update_id)->update([
                'status' => 'applied',
                'backup_file' => $backup_file,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);

            $setProgress('done', 100, "Atualização concluída para v{$target}!", true);

        } catch (\Throwable $e) {
            $this->db->table('sp_system_updates')->where('id', $update_id)->update(['status' => 'failed']);
            $setProgress('error', -1, 'Erro: ' . $e->getMessage(), true);
        }
    }

    // ──────────────────────────────────────────────
    // Slow-Lane: Session Timeouts
    // ──────────────────────────────────────────────
    private function runSessions(): void
    {
        $this->controller->check_timeouts();
    }
}
