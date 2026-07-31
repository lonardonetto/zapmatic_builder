<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SystemUpdater extends BaseCommand
{
    protected $group       = 'System';
    protected $name        = 'system:update';
    protected $description = 'Executa atualizacao do sistema em background com progresso.';

    private $progressFile = '';
    private $db;

    public function run(array $params)
    {
        // Params: --id=, --version=, --channel=
        $id = 0;
        $version = '';
        $channel = 'stable';
        foreach ($params as $p) {
            $p = ltrim($p, '-'); // remove prefixo -- ou -
            if (str_starts_with($p, 'id=')) $id = (int)substr($p, 3);
            if (str_starts_with($p, 'version=')) $version = substr($p, 8);
            if (str_starts_with($p, 'channel=')) $channel = substr($p, 8);
        }

        $this->progressFile = WRITEPATH . 'logs/update_progress_' . $id . '.json';
        $this->db = \Config\Database::connect();

        try {
            $this->setProgress('backup', 10, 'Criando backup do sistema...');
            $updater = new \Core\Plugins\Controllers\System_updater();
            $refBackup = new \ReflectionMethod($updater, 'create_backup');
            $refBackup->setAccessible(true);
            $backup_file = $refBackup->invoke($updater, '0.0.0');

            $this->setProgress('download', 30, 'Baixando atualização do GitHub...');
            $refApply = new \ReflectionMethod($updater, 'apply_git_update');
            $refApply->setAccessible(true);
            $refApply->invoke($updater, $version, $channel);

            $this->setProgress('migrate', 80, 'Aplicando migrações SQL...');
            $refMigrate = new \ReflectionMethod($updater, 'run_pending_migrations');
            $refMigrate->setAccessible(true);
            $migrations = $refMigrate->invoke($updater);

            $this->setProgress('restart', 92, 'Reiniciando processos...');
            $refRestart = new \ReflectionMethod($updater, 'restart_processes');
            $refRestart->setAccessible(true);
            $refRestart->invoke($updater);

            $this->setProgress('version', 96, 'Atualizando versão...');
            $refWrite = new \ReflectionMethod($updater, 'write_version');
            $refWrite->setAccessible(true);
            $refWrite->invoke($updater, $version, $channel);

            // Marcar como aplicado
            if ($id > 0) {
                $this->db->table('sp_system_updates')->where('id', $id)->update([
                    'status' => 'applied',
                    'backup_file' => $backup_file,
                    'applied_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->setProgress('done', 100, "Atualização concluída para v{$version}" . ($migrations > 0 ? " ({$migrations} migrações)" : ""), true);

        } catch (\Throwable $e) {
            if ($id > 0) {
                $this->db->table('sp_system_updates')->where('id', $id)->update(['status' => 'failed']);
            }
            $this->setProgress('error', -1, 'Erro: ' . $e->getMessage());
        }
    }

    private function setProgress(string $stage, int $percent, string $message, bool $done = false): void
    {
        $data = [
            'stage' => $stage,
            'percent' => $percent,
            'message' => $message,
            'done' => $done,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        @file_put_contents($this->progressFile, json_encode($data));
    }
}
