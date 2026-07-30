<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CampaignWorker extends BaseCommand
{
    protected $group       = 'Campaigns';
    protected $name        = 'campaign:dispatch';
    protected $description = 'Processes mass broadcast queues with batching and circuit breaking.';

    public function run(array $params)
    {
        CLI::write('Starting Campaign Broadcast Worker...', 'green');
        
        $db = \Config\Database::connect();

        while (true) {
            $startTime = microtime(true);

            try {
                // Prevent MySQL wait_timeout disconnects during low traffic periods
                $db->reconnect();
                
                // Process a safe batch of messages
                $this->processBatch($db);
            } catch (\Throwable $e) {
                log_message('error', '[CampaignWorker] Critical Error: ' . $e->getMessage());
            }

            // Proactive memory cleanup for long-running CLI process
            gc_collect_cycles();

            // Cadence control (1-second loop check)
            $executionTime = microtime(true) - $startTime;
            $sleepTime = 1000000 - ($executionTime * 1000000);
            if ($sleepTime > 0) {
                usleep((int) $sleepTime);
            }
        }
    }

    private function processBatch($db)
    {
        $builder = $db->table('sp_campaign_queue');
        
        // 1. Fetch pending batch
        $jobs = $builder->where('status', 'pending')
                        ->where('send_at <=', time())
                        ->limit(100)
                        ->get()->getResult();

        if (empty($jobs)) {
            return;
        }

        foreach ($jobs as $job) {
            // Optimistic lock
            $builder->where('id', $job->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'processing', 'attempts' => $job->attempts + 1]);

            if ($db->affectedRows() === 0) continue;

            try {
                // Get account details
                $account = $db->table('sp_accounts')->where('id', $job->instance_id)->get()->getRow();
                
                if (!$account || $account->status != 1) {
                    $builder->where('id', $job->id)->update([
                        'status' => 'failed',
                        'error_log' => 'Account disconnected or not found.'
                    ]);
                    continue;
                }

                $payload = json_decode($job->payload, true);
                $type = $payload['type'] ?? 'text';
                
                // 2. Dispatch using WhatsAppGatewayService
                $response = \App\Services\WhatsAppGatewayService::send($account->token, $job->recipient_phone, $type, $payload);

                // 3. Handle Responses & Circuit Breaker (HTTP 429)
                $httpCode = $response['http_code'] ?? 0;
                if ($httpCode == 0 && isset($response['raw'])) {
                    // Try to guess from Whatsmeow error
                    if (strpos($response['raw'], '429') !== false || strpos($response['raw'], 'Too Many Requests') !== false) {
                        $httpCode = 429;
                    }
                }

                if ($httpCode == 429 || ($response['status'] == 'error' && strpos(json_encode($response), '429') !== false)) {
                    // CIRCUIT BREAKER TRIGGERED
                    $backoff_seconds = 60 * pow(2, $job->attempts);
                    if ($backoff_seconds > 900) $backoff_seconds = 900; // Cap at 15 mins

                    // Requeue this job
                    $builder->where('id', $job->id)->update([
                        'status' => 'rate_limited',
                        'send_at' => time() + $backoff_seconds,
                        'error_log' => 'HTTP 429 Rate Limit. Backoff applied.'
                    ]);

                    // Shift ALL pending messages for this instance
                    $db->query("UPDATE sp_campaign_queue SET send_at = send_at + ? WHERE instance_id = ? AND status = 'pending'", [$backoff_seconds, $job->instance_id]);
                    
                    log_message('warning', "[CampaignWorker] Circuit Breaker activated for instance {$job->instance_id}. Pausing for {$backoff_seconds}s.");
                    
                } elseif ($response['status'] === 'success') {
                    // Success
                    $builder->where('id', $job->id)->update([
                        'status' => 'sent',
                        'error_log' => json_encode($response)
                    ]);
                    
                    // Update campaign stats
                    $db->query("UPDATE sp_whatsapp_schedules SET sent = sent + 1 WHERE id = ?", [$job->campaign_id]);
                } else {
                    // Other failures
                    if ($job->attempts >= $job->max_attempts) {
                        $builder->where('id', $job->id)->update([
                            'status' => 'failed',
                            'error_log' => json_encode($response)
                        ]);
                        $db->query("UPDATE sp_whatsapp_schedules SET failed = failed + 1 WHERE id = ?", [$job->campaign_id]);
                    } else {
                        // Retry later
                        $builder->where('id', $job->id)->update([
                            'status' => 'pending',
                            'send_at' => time() + 60, // 1 min retry
                            'error_log' => json_encode($response)
                        ]);
                    }
                }

            } catch (\Throwable $e) {
                $error = $e->getMessage();
                log_message('error', "[CampaignWorker] Dispatch Error Job {$job->id}: {$error}");
                
                if ($job->attempts >= $job->max_attempts) {
                    $builder->where('id', $job->id)->update(['status' => 'failed', 'error_log' => $error]);
                    $db->query("UPDATE sp_whatsapp_schedules SET failed = failed + 1 WHERE id = ?", [$job->campaign_id]);
                } else {
                    $builder->where('id', $job->id)->update(['status' => 'pending', 'send_at' => time() + 60, 'error_log' => $error]);
                }
            }
        }
    }
}
