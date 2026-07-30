<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BotWorkerDebounce extends BaseCommand
{
    protected $group       = 'BotBuilder';
    protected $name        = 'bot:debounce';
    protected $description = 'Fast-Lane 1: Processes message debounce buffer asynchronously.';

    public function run(array $params)
    {
        CLI::write('Starting Fast-Lane Worker 1 (Debounce)...', 'green');

        $db = \Config\Database::connect();

        // Instantiate the legacy controller to reuse the process_buffer logic
        // We use the full namespace to avoid conflicts
        $controller = new \Core\Bot_builder\Controllers\Bot_builder();

        while (true) {
            try {
                // Reconnect to prevent "MySQL server has gone away" errors
                $db->reconnect();
                
                // Directly reuse the robust legacy debounce method
                $controller->process_buffer();
            } catch (\Throwable $e) {
                log_message('error', '[BotWorkerDebounce] Critical Error: ' . $e->getMessage());
            }

            // Proactive memory cleanup for long-running PHP CLI
            gc_collect_cycles();
            
            // Fast-Lane: 1-second cadence
            sleep(1);
        }
    }
}
