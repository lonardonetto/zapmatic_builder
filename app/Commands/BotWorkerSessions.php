<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BotWorkerSessions extends BaseCommand
{
    protected $group       = 'BotBuilder';
    protected $name        = 'bot:sessions';
    protected $description = 'Slow-Lane 3: Manages session timeouts and retries.';

    public function run(array $params)
    {
        CLI::write('Starting Slow-Lane Worker 3 (Sessions)...', 'green');

        $db = \Config\Database::connect();

        // Instantiate the legacy controller to reuse the timeout check logic
        $controller = new \Core\Bot_builder\Controllers\Bot_builder();

        while (true) {
            try {
                $db->reconnect();
                
                // Directly reuse the robust legacy session timeout method
                $controller->check_timeouts();
            } catch (\Throwable $e) {
                log_message('error', '[BotWorkerSessions] Critical Error: ' . $e->getMessage());
            }

            gc_collect_cycles();
            
            // Slow-Lane: 15-second cadence
            sleep(15);
        }
    }
}
