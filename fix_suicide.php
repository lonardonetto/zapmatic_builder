<?php
$f = '/www/wwwroot/app_zapmatic_app/inc/core/Plugins/Controllers/System_updater.php';
$c = file_get_contents($f);

$search = <<<'PHP'
    private function restart_processes(): void
    {
        // Reiniciar workers PM2 (tenta vários nomes possíveis por sistema)
        $this->run_shell("pm2 restart bot-worker-all 2>/dev/null || true");
        $this->run_shell("pm2 restart renovo-bot-worker-all 2>/dev/null || true");
        $this->run_shell("pm2 restart bot-worker-debounce bot-worker-queue bot-worker-sessions campaign-worker-dispatch 2>/dev/null || true");

        // Reiniciar Go gateway (tenta nomes de serviço possíveis)
        $this->run_shell("sudo systemctl restart zapmatic-whatsmeow 2>/dev/null || true");
        $this->run_shell("sudo systemctl restart zapmatic-whatsmeow-renovo 2>/dev/null || true");
        $this->run_shell("sudo systemctl restart zapmatic-whatsmeow-main 2>/dev/null || true");

        log_message('info', "[SystemUpdater] Processos reiniciados após atualização");
    }
PHP;

$replace = <<<'PHP'
    private function restart_processes(): void
    {
        // Reiniciar Go gateway imediatamente
        $this->run_shell("sudo systemctl restart zapmatic-whatsmeow 2>/dev/null || true");
        $this->run_shell("sudo systemctl restart zapmatic-whatsmeow-renovo 2>/dev/null || true");
        $this->run_shell("sudo systemctl restart zapmatic-whatsmeow-main 2>/dev/null || true");

        // Reiniciar workers PM2 com DELAY em background para nao matar o proprio script de update
        $cmd = "nohup bash -c 'sleep 5 && pm2 restart bot-worker-all renovo-bot-worker-all paulo-bot-worker-all elias-bot-worker-all astros-bot-worker-all > /dev/null 2>&1' > /dev/null 2>&1 &";
        @exec($cmd);

        log_message('info', "[SystemUpdater] PM2 agendado para reiniciar em 5s. Go Gateway reiniciado.");
    }
PHP;

$c = str_replace($search, $replace, $c);
file_put_contents($f, $c);
echo "Updater corrigido.\n";
