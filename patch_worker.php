<?php
$f = '/www/wwwroot/app_zapmatic_app/app/Commands/BotWorkerAll.php';
$c = file_get_contents($f);

// Injetar chamada no loop principal
$search1 = "            try {
                \$this->runCampaigns();
            } catch (\Throwable \$e) {
                log_message('error', \"[BotWorkerAll] Campaign Error: {\$e->getMessage()}\");
            }";
$replace1 = $search1 . "

            // Processar atualizacoes de sistema
            try {
                \$this->runSystemUpdates();
            } catch (\Throwable \$e) {
                log_message('error', \"[BotWorkerAll] System Update Error: {\$e->getMessage()}\");
            }";
if (strpos($c, "runSystemUpdates();") === false) {
    $c = str_replace($search1, $replace1, $c);
}

// Injetar metodo runSystemUpdates
$search2 = "    // ──────────────────────────────────────────────
    // Slow-Lane: Session Timeouts";
$replace2 = "    // ──────────────────────────────────────────────
    // System Updates (Background Worker)
    // ──────────────────────────────────────────────
    private function runSystemUpdates(): void
    {
        \$row = \$this->db->table('sp_system_updates')
            ->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->get()->getRow();

        if (!\$row) return;

        \$update_id = (int)\$row->id;
        \$target = (string)\$row->to_version;
        \$channel = (string)\$row->channel;
        
        // Optimistic lock
        \$this->db->table('sp_system_updates')->where('id', \$update_id)->update(['status' => 'processing']);
        if (\$this->db->affectedRows() === 0) return;

        \$progressFile = WRITEPATH . 'logs/update_progress_' . \$update_id . '.json';
        \$setProgress = function (\$stage, \$percent, \$message, \$done = false) use (\$progressFile) {
            @file_put_contents(\$progressFile, json_encode([
                'stage' => \$stage, 'percent' => \$percent,
                'message' => \$message, 'done' => \$done,
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
        };

        try {
            \$setProgress('backup', 5, 'Criando backup do sistema...');
            \$updater = new \Core\Plugins\Controllers\System_updater();
            \$ref = new \ReflectionMethod(\$updater, 'create_backup');
            \$ref->setAccessible(true);
            \$backup_file = \$ref->invoke(\$updater, \$row->from_version ?? '0.0.0');

            \$setProgress('download', 30, 'Baixando atualização...');
            \$ref = new \ReflectionMethod(\$updater, 'apply_git_update');
            \$ref->setAccessible(true);
            \$ref->invoke(\$updater, \$target, \$channel);

            \$setProgress('migrate', 80, 'Aplicando banco de dados...');
            \$ref = new \ReflectionMethod(\$updater, 'run_pending_migrations');
            \$ref->setAccessible(true);
            \$migrations = \$ref->invoke(\$updater);

            \$setProgress('restart', 92, 'Reiniciando serviços...');
            \$ref = new \ReflectionMethod(\$updater, 'restart_processes');
            \$ref->setAccessible(true);
            \$ref->invoke(\$updater);

            \$setProgress('version', 96, 'Atualizando versão...');
            \$ref = new \ReflectionMethod(\$updater, 'write_version');
            \$ref->setAccessible(true);
            \$ref->invoke(\$updater, \$target, \$channel);

            \$this->db->table('sp_system_updates')->where('id', \$update_id)->update([
                'status' => 'applied',
                'backup_file' => \$backup_file,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);

            \$setProgress('done', 100, \"Atualização concluída para v{\$target}!\", true);

        } catch (\Throwable \$e) {
            \$this->db->table('sp_system_updates')->where('id', \$update_id)->update(['status' => 'failed']);
            \$setProgress('error', -1, 'Erro: ' . \$e->getMessage(), true);
        }
    }

" . $search2;

if (strpos($c, "private function runSystemUpdates") === false) {
    $c = str_replace($search2, $replace2, $c);
}

file_put_contents($f, $c);
echo "Worker modificado.\n";
?>
