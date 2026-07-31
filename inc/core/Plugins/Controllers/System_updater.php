<?php
namespace Core\Plugins\Controllers;

use CodeIgniter\Controller;

class System_updater extends Controller
{
    private const GITHUB_API = 'https://api.github.com/repos/lonardonetto/zapmatic_builder';
    private const GITHUB_RAW = 'https://raw.githubusercontent.com/lonardonetto/zapmatic_builder';
    private const BACKUP_DIR = 'writable/backups';

    public $config;
    public $model;

    public function __construct()
    {
        // Garantir helpers em ambiente CLI
        if (!function_exists('parse_config')) {
            require_once FCPATH . 'app/Helpers/Common_helper.php';
        }
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Plugins\Models\PluginsModel();
    }

    // ──────────────────────────────────────────────
    // Interface principal (aba no Admin Mods)
    // ──────────────────────────────────────────────
    public function index()
    {
        try {
            $current = $this->get_current_version();
            $channel = $current['channel'] ?? 'stable';
            $check = $this->check_remote($channel);

            $data = [
                'title' => 'Atualização do Sistema',
                'config' => $this->config,
                'current' => $current,
                'channel' => $channel,
                'latest_stable' => $check['latest_stable'] ?? null,
                'latest_test' => $check['latest_test'] ?? null,
                'update_available' => $check['update_available'] ?? false,
                'history' => $this->get_update_history(),
                'migrations_pending' => $this->count_pending_migrations(),
            ];

            return view('Core\Plugins\Views\system_update', $data);
        } catch (\Throwable $e) {
            @file_put_contents(WRITEPATH . 'logs/system_updater_error.log', date('Y-m-d H:i:s') . " | " . $e->getMessage() . " @ " . $e->getFile() . ":" . $e->getLine() . "\n", FILE_APPEND);
            throw $e;
        }
    }

    // ──────────────────────────────────────────────
    // Verificar atualização (AJAX)
    // ──────────────────────────────────────────────
    public function check()
    {
        $channel = post("channel") ?? "stable";
        $result = $this->check_remote($channel);

        if ($result['update_available']) {
            ms([
                'status' => 'success',
                'message' => "Atualização disponível: v{$result['latest_version']}",
                'data' => $result
            ]);
        } else {
            ms([
                'status' => 'success',
                'message' => "Você está na versão mais recente (v{$result['current_version']})",
                'data' => $result
            ]);
        }
    }

    // ──────────────────────────────────────────────
    // Aplicar atualização
    // ──────────────────────────────────────────────
    public function apply()
    {
        $target = trim(post("target_version") ?? "");
        $channel = post("channel") ?? "stable";

        if ($target === "") {
            ms(['status' => 'error', 'message' => 'Versão alvo não informada']);
        }

        set_time_limit(300);

        // Desabilitar TODOS os buffers (real-time streaming)
        @ini_set('output_buffering', '0');
        @ini_set('zlib.output_compression', 'Off');
        @ini_set('implicit_flush', '1');
        @ob_implicit_flush(true);
        while (ob_get_level() > 0) { @ob_end_clean(); }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        // Padding inicial (força o servidor a liberar os headers)
        echo str_repeat(' ', 4096) . "\n\n";
        @ob_flush();
        @flush();

        // Funcao de stream de progresso
        $stream = function (int $percent, string $message, bool $done = false) {
            $chunk = json_encode(['percent' => $percent, 'message' => $message, 'done' => $done]);
            echo "data: {$chunk}\n\n";
            @ob_flush();
            @flush();
            if ($done) {
                usleep(50000);
                @ob_flush();
                @flush();
            }
        };

        $current = $this->get_current_version();
        $from_version = $current['version'] ?? '0.0.0';

        // 1. Registrar update como pending
        $db = db_connect();
        $db->table('sp_system_updates')->insert([
            'from_version' => $from_version,
            'to_version' => $target,
            'channel' => $channel,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $update_id = $db->insertID();

        try {
            $stream(5, 'Criando backup do sistema...');
            $ref = new \ReflectionMethod($this, 'create_backup');
            $ref->setAccessible(true);
            $backup_file = $ref->invoke($this, $from_version);

            $stream(30, 'Baixando atualização do GitHub...');
            $ref = new \ReflectionMethod($this, 'apply_git_update');
            $ref->setAccessible(true);
            $ref->invoke($this, $target, $channel);

            $stream(80, 'Aplicando migrações SQL...');
            $ref = new \ReflectionMethod($this, 'run_pending_migrations');
            $ref->setAccessible(true);
            $migrations = $ref->invoke($this);

            $stream(92, 'Reiniciando processos...');
            $ref = new \ReflectionMethod($this, 'restart_processes');
            $ref->setAccessible(true);
            $ref->invoke($this);

            $stream(96, 'Atualizando versão...');
            $ref = new \ReflectionMethod($this, 'write_version');
            $ref->setAccessible(true);
            $ref->invoke($this, $target, $channel);

            // Marcar aplicado
            $db->table('sp_system_updates')->where('id', $update_id)->update([
                'status' => 'applied',
                'backup_file' => $backup_file,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);

            $stream(100, "Atualização concluída para v{$target}!" . ($migrations > 0 ? " ({$migrations} migrações)" : ""), true);

        } catch (\Throwable $e) {
            $db->table('sp_system_updates')->where('id', $update_id)->update(['status' => 'failed']);
            $stream(-1, 'Erro: ' . $e->getMessage(), true);
        }
    }

    // ──────────────────────────────────────────────
    // Progresso da atualização (AJAX polling)
    // ──────────────────────────────────────────────
    public function progress()
    {
        $update_id = (int) post("update_id");
        $progress_file = WRITEPATH . 'logs/update_progress_' . $update_id . '.json';

        if (file_exists($progress_file)) {
            $data = json_decode(file_get_contents($progress_file), true);
            ms(['status' => 'success', 'progress' => $data]);
        }

        // Sem arquivo: verificar status no banco
        $db = db_connect();
        $row = $db->table('sp_system_updates')->where('id', $update_id)->get()->getRow();
        if ($row && $row->status === 'applied') {
            ms(['status' => 'success', 'progress' => ['percent' => 100, 'stage' => 'done', 'message' => 'Atualização concluída', 'done' => true]]);
        }
        if ($row && $row->status === 'failed') {
            ms(['status' => 'success', 'progress' => ['percent' => -1, 'stage' => 'error', 'message' => 'Falha na atualização', 'done' => true]]);
        }

        ms(['status' => 'success', 'progress' => ['percent' => 0, 'stage' => 'starting', 'message' => 'Iniciando...', 'done' => false]]);
    }

    // ──────────────────────────────────────────────
    // Rollback para versão anterior
    // ──────────────────────────────────────────────
    public function rollback()
    {
        $update_id = (int) post("update_id");

        $db = db_connect();
        $update = $db->table('sp_system_updates')->where('id', $update_id)->get()->getRow();

        if (!$update || empty($update->backup_file)) {
            ms(['status' => 'error', 'message' => 'Nenhum backup encontrado para esta atualização']);
        }

        $backup_path = WRITEPATH . 'backups/' . basename($update->backup_file);

        if (!file_exists($backup_path)) {
            ms(['status' => 'error', 'message' => "Backup não encontrado: {$backup_path}"]);
        }

        try {
            // Restaurar backup
            $this->restore_backup($backup_path);

            // Registrar rollback
            $db->table('sp_system_updates')->where('id', $update_id)->update([
                'status' => 'rolled_back',
            ]);

            ms(['status' => 'success', 'message' => 'Sistema revertido para a versão anterior com sucesso!']);
        } catch (\Throwable $e) {
            ms(['status' => 'error', 'message' => 'Falha no rollback: ' . $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────
    // Reiniciar processos após atualização
    // ──────────────────────────────────────────────
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

    // ──────────────────────────────────────────────
    // Rodar migrações pendentes
    // ──────────────────────────────────────────────
    private function run_pending_migrations(): int
    {
        $migrations_dir = FCPATH . 'migrations';
        if (!is_dir($migrations_dir)) return 0;

        $db = db_connect();
        $applied = $db->table('sp_system_migrations')->select('filename')->get()->getResultArray();
        $applied_files = array_column($applied, 'filename');

        $files = glob($migrations_dir . '/*.sql');
        sort($files);

        $count = 0;
        foreach ($files as $file) {
            $filename = basename($file);
            if (in_array($filename, $applied_files)) continue;

            $sql = file_get_contents($file);
            if (trim($sql) === '') continue;

            try {
                // Executar cada statement separado
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $stmt) {
                    if ($stmt !== '') {
                        $db->query($stmt . ';');
                    }
                }

                $db->table('sp_system_migrations')->insert([
                    'filename' => $filename,
                    'version' => $this->get_current_version()['version'] ?? null,
                ]);
                $count++;
            } catch (\Throwable $e) {
                log_message('error', "[SystemUpdater] Migration {$filename} failed: " . $e->getMessage());
            }
        }

        return $count;
    }

    // ──────────────────────────────────────────────
    // Contar migrações pendentes
    // ──────────────────────────────────────────────
    private function count_pending_migrations(): int
    {
        $migrations_dir = FCPATH . 'migrations';
        if (!is_dir($migrations_dir)) return 0;

        $db = db_connect();
        $applied = $db->table('sp_system_migrations')->select('filename')->get()->getResultArray();
        $applied_files = array_column($applied, 'filename');

        $files = glob($migrations_dir . '/*.sql');
        return count(array_filter($files, fn($f) => !in_array(basename($f), $applied_files)));
    }

    // ──────────────────────────────────────────────
    // Verificar versão no GitHub
    // ──────────────────────────────────────────────
    private function check_remote(string $channel): array
    {
        $current = $this->get_current_version();
        $current_version = $current['version'] ?? '0.0.0';

        // Buscar versões direto do raw.githubusercontent.com (SEM rate limit da API)
        $stable = $this->fetch_raw_version('main');
        $test = $this->fetch_raw_version('beta');

        $target = ($channel === 'test' && $test) ? $test : $stable;
        $update_available = $target && version_compare($target, $current_version, '>');

        return [
            'current_version' => $current_version,
            'latest_stable' => $stable,
            'latest_test' => $test,
            'latest_version' => $target ?? $current_version,
            'update_available' => (bool) $update_available,
            'channel' => $channel,
        ];
    }

    // ──────────────────────────────────────────────
    // Buscar versão do version.json na branch (raw, sem rate limit)
    // ──────────────────────────────────────────────
    private function fetch_raw_version(string $branch): ?string
    {
        $url = 'https://raw.githubusercontent.com/lonardonetto/zapmatic_builder/' . $branch . '/version.json';

        $ctx = stream_context_create(['http' => [
            'header' => "User-Agent: Zapmatic-Updater\r\n",
            'timeout' => 15,
        ]]);

        $result = @file_get_contents($url, false, $ctx);
        if (!$result) return null;

        $data = json_decode($result, true);
        if (!is_array($data) || empty($data['version'])) return null;

        return (string)$data['version'];
    }

    // ──────────────────────────────────────────────
    // Buscar tags do GitHub
    // ──────────────────────────────────────────────
    private function fetch_github_tags(): array
    {
        $ctx = stream_context_create(['http' => [
            'header' => "User-Agent: Zapmatic-Updater\r\n",
            'timeout' => 15,
        ]]);

        $result = @file_get_contents(self::GITHUB_API . '/tags?per_page=50', false, $ctx);
        if (!$result) return [];

        $tags = json_decode($result, true);
        if (!is_array($tags)) return [];

        $out = [];
        foreach ($tags as $tag) {
            $name = $tag['name'] ?? '';
            if (!preg_match('/^v?(\d+\.\d+\.\d+)(.*)$/', $name, $m)) continue;

            $channel = str_contains($m[2], 'test') ? 'test' : 'stable';
            $out[] = [
                'tag' => $name,
                'version' => $m[1],
                'channel' => $channel,
            ];
        }

        return $out;
    }

    // ──────────────────────────────────────────────
    // Resolver o nome real da tag a partir da versão
    // ──────────────────────────────────────────────
    private function resolve_tag_name(string $version): string
    {
        // Tags sao criadas como vX.Y.Z (sem sufixo)
        // Se vier com prefixo v, usa como esta
        if (str_starts_with($version, 'v')) {
            return $version;
        }
        return "v{$version}";
    }

    // ──────────────────────────────────────────────
    // Escolher versão mais recente por canal
    // ──────────────────────────────────────────────
    private function pick_latest(array $tags, string $channel): ?array
    {
        $filtered = array_filter($tags, fn($t) => $t['channel'] === $channel);
        if (empty($filtered)) return null;

        usort($filtered, fn($a, $b) => version_compare($b['version'], $a['version']));
        return $filtered[0];
    }

    // ──────────────────────────────────────────────
    // Ler versão atual
    // ──────────────────────────────────────────────
    private function get_current_version(): array
    {
        $version_file = FCPATH . 'version.json';
        if (file_exists($version_file)) {
            $data = json_decode(file_get_contents($version_file), true);
            if (is_array($data)) return $data;
        }
        return ['version' => '0.0.0', 'channel' => 'stable'];
    }

    // ──────────────────────────────────────────────
    // Escrever versão
    // ──────────────────────────────────────────────
    private function write_version(string $version, string $channel): void
    {
        $data = [
            'version' => $version,
            'channel' => $channel,
            'build_date' => date('Y-m-d H:i:s'),
            'git_commit' => $this->get_git_commit(),
        ];
        $file = FCPATH . 'version.json';

        // Garantir permissão de escrita (o rsync pode ter trocado o dono)
        @chmod($file, 0666);

        $result = @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if ($result === false) {
            log_message('error', "[SystemUpdater] Falha ao escrever version.json em {$file}");
        }
    }

    // ──────────────────────────────────────────────
    // Aplicar atualização via download ZIP da tag + rsync
    // ──────────────────────────────────────────────
    private function apply_git_update(string $target, string $channel): void
    {
        $repo_dir = rtrim(FCPATH, '/');

        // Resolver o NOME REAL da tag (ex: v8.3.0-updater, v8.2.0-cleanup)
        $tag_name = $this->resolve_tag_name($target);
        $ref = $tag_name;

        // Baixar ZIP da tag/branch do GitHub (repo público, sem auth)
        $zip_url = 'https://github.com/lonardonetto/zapmatic_builder/archive/refs/tags/' . $ref . '.zip';
        if ($channel === 'test') {
            $zip_url = 'https://github.com/lonardonetto/zapmatic_builder/archive/refs/heads/' . $ref . '.zip';
        }

        $tmp = FCPATH . 'writable/tmp_update_' . uniqid();
        if (!is_dir($tmp)) {
            mkdir($tmp, 0777, true);
        }

        $zip_file = $tmp . '/update.zip';

        // Download com User-Agent
        $ctx = stream_context_create(['http' => [
            'header' => "User-Agent: Zapmatic-Updater\r\n",
            'timeout' => 60,
            'follow_location' => 1,
        ]]);
        $data = @file_get_contents($zip_url, false, $ctx);
        if (!$data || strlen($data) < 1000) {
            $this->run_shell("rm -rf {$tmp}");
            throw new \RuntimeException("Falha ao baixar atualização de {$zip_url}");
        }
        file_put_contents($zip_file, $data);

        // Extrair ZIP
        $zip = new \ZipArchive;
        if ($zip->open($zip_file) !== true) {
            $this->run_shell("rm -rf {$tmp}");
            throw new \RuntimeException("ZIP inválido");
        }
        $zip->extractTo($tmp);
        $zip->close();

        // Encontrar pasta raiz do projeto extraído
        $extracted = glob($tmp . '/zapmatic_builder-*');
        if (empty($extracted) || !is_dir($extracted[0])) {
            $this->run_shell("rm -rf {$tmp}");
            throw new \RuntimeException("Estrutura do ZIP inesperada");
        }
        $src = $extracted[0];

        // Proteções que NÃO podem ser sobrescritas ou deletadas
        $excludes = [];
        $protected = [
            '.env', '.git', 'vendor', 'writable', 'storage',
            'ecosystem.config.js',
            'version.json',
            'app_zapmatic_whatsmeow_api/config.json',
            'app_zapmatic_whatsmeow_api/storage',
            'app_zapmatic_whatsmeow_api/logs',
            'app_zapmatic_api/sessions',
            'app_zapmatic_api/config.js',
            'app_zapmatic_api/store',
            'app_zapmatic_api/files',
            'app_zapmatic_api/node_modules',
        ];
        foreach ($protected as $p) {
            $excludes[] = "--exclude={$p}";
        }

        // Backup antes (feito pelo caller, mas garantia dupla)
        $backup_file = $this->create_backup($target);

        // Rsync do código extraído sobre o sistema
        // SEM --delete: nunca remove arquivos existentes (evita perda de dados
        // se a extração falhar ou o processo morrer no meio)
        $cmd = "rsync -avz --no-times " . implode(' ', $excludes) . " {$src}/ {$repo_dir}/ 2>&1";
        $this->run_shell($cmd);

        // Garantir permissões
        $this->run_shell("chmod -R 777 {$repo_dir}/app {$repo_dir}/inc 2>/dev/null || true");

        // Limpar temp
        $this->run_shell("rm -rf {$tmp}");
    }

    // ──────────────────────────────────────────────
    // Rollback via git
    // ──────────────────────────────────────────────
    private function restore_backup(string $backup_path): void
    {
        // Se temos o backup em tar.gz, restaurar
        $tmp = FCPATH . 'writable/tmp_restore';
        if (is_dir($tmp)) $this->run_shell("rm -rf {$tmp}");
        if (!is_dir($tmp)) {
            mkdir($tmp, 0777, true);
        }
        $this->run_shell("tar -xzf {$backup_path} -C {$tmp} 2>&1");

        if (is_dir($tmp . '/inc')) {
            $this->run_shell("rm -rf " . FCPATH . "inc && cp -a {$tmp}/inc " . FCPATH . "inc");
        }
        if (is_dir($tmp . '/app')) {
            $this->run_shell("rm -rf " . FCPATH . "app && cp -a {$tmp}/app " . FCPATH . "app");
        }
        if (file_exists($tmp . '/version.json')) {
            copy($tmp . '/version.json', FCPATH . 'version.json');
        }

        $this->run_shell("rm -rf {$tmp}");
    }

    // ──────────────────────────────────────────────
    // Criar backup (tar.gz de inc/ + app/ + version.json)
    // ──────────────────────────────────────────────
    private function create_backup(string $from_version): string
    {
        $backup_dir = FCPATH . self::BACKUP_DIR;
        if (!is_dir($backup_dir)) mkdir($backup_dir, 0777, true);

        $filename = 'backup_v' . $from_version . '_' . date('Ymd_His') . '.tar.gz';
        $backup_path = $backup_dir . '/' . $filename;

        // Pasta temporaria UNICA (evita conflito de permissao com processos anteriores)
        $tmp = FCPATH . 'writable/tmp_backup_' . uniqid();
        if (!is_dir($tmp)) {
            mkdir($tmp, 0777, true);
        }

        $this->run_shell("cp -a " . FCPATH . "inc {$tmp}/inc 2>/dev/null");
        $this->run_shell("cp -a " . FCPATH . "app {$tmp}/app 2>/dev/null");
        if (file_exists(FCPATH . 'version.json')) {
            @copy(FCPATH . 'version.json', $tmp . '/version.json');
        }

        $this->run_shell("cd {$tmp} && tar -czf {$backup_path} inc app version.json 2>&1");
        $this->run_shell("rm -rf {$tmp}");

        return $filename;
    }

    // ──────────────────────────────────────────────
    // Histórico de atualizações
    // ──────────────────────────────────────────────
    private function get_update_history(): array
    {
        $db = db_connect();
        return $db->table('sp_system_updates')
            ->orderBy('id', 'DESC')
            ->limit(20)
            ->get()->getResultArray();
    }

    // ──────────────────────────────────────────────
    // Executar comando shell
    // ──────────────────────────────────────────────
    private function run_shell(string $cmd): void
    {
        exec($cmd . ' 2>&1', $output, $code);
        if ($code !== 0) {
            log_message('error', "[SystemUpdater] Shell: {$cmd} | " . implode("\n", $output));
        }
    }

    // ──────────────────────────────────────────────
    // Commit git atual
    // ──────────────────────────────────────────────
    private function get_git_commit(): string
    {
        $out = [];
        exec('cd ' . FCPATH . ' && git rev-parse --short HEAD 2>/dev/null', $out);
        return $out[0] ?? '';
    }
}
