<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BotWorkerQueue extends BaseCommand
{
    protected $group       = 'BotBuilder';
    protected $name        = 'bot:queue';
    protected $description = 'Fast-Lane 2: Consumes asynchronous delays and resumes flows.';

    public function run(array $params)
    {
        CLI::write('Starting Fast-Lane Worker 2 (Queue)...', 'green');

        $db = \Config\Database::connect();

        while (true) {
            try {
                $db->reconnect();
                
                // Invoke specific queue processing logic
                $this->processQueue($db);
            } catch (\Throwable $e) {
                log_message('error', '[BotWorkerQueue] Critical Error: ' . $e->getMessage());
            }

            gc_collect_cycles();
            
            // Fast-Lane: 1-second cadence
            sleep(1);
        }
    }

    private function processQueue($db)
    {
        $builder = $db->table('sp_message_queue');
        
        $jobs = $builder->where('status', 'pending')
                        ->where('send_at <=', time())
                        ->limit(30)
                        ->get()->getResult();

        if (empty($jobs)) {
            return;
        }

        $controller = new \Core\Bot_builder\Controllers\Bot_builder();
        $bbModel = new \Core\Bot_builder\Models\Bot_builderModel();

        foreach ($jobs as $job) {
            // Optimistic Lock
            $builder->where('id', $job->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'processing', 'attempts' => $job->attempts + 1]);

            if ($db->affectedRows() === 0) {
                continue; // Job was taken by another worker
            }

            try {
                $payload = json_decode($job->payload);

                if ($job->action_type === 'delay_resume') {
                    // Fetch the session using the legacy model
                    $session = $bbModel->get_session_by_id($job->session_id);

                    // Ensure session is valid, not completed, and not handed off to human
                    if ($session && $session->is_completed == 0 && $session->bot_status === 'active') {
                        $instance_id = $payload->instance_id ?? $session->instance_id;
                        
                        // Execute flow from the suspended block
                        // is_start = true means it won't ask for user input validation on resumption
                        $controller->run_flow($session, '', 'resume', $instance_id, true);
                    }
                    
                    $builder->where('id', $job->id)->update(['status' => 'completed']);
                }
                
                // Note: Other action_types (api_call, send_message) will go here in the future
                
            } catch (\Throwable $e) {
                $error = $e->getMessage();
                if ($job->attempts >= $job->max_attempts) {
                    $builder->where('id', $job->id)->update(['status' => 'failed', 'error_log' => $error]);
                } else {
                    // Exponential backoff or static 1 minute retry
                    $builder->where('id', $job->id)->update(['status' => 'pending', 'send_at' => time() + 60, 'error_log' => $error]);
                }
            }
        }
    }
}
