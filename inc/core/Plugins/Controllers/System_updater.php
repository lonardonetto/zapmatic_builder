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
        $current = $this->get_current_version();
        $channel = $current['channel'] ?? 'stable';
        $check = $this->check_remote($channel);

        $data = [
            'config' => $this->config,
            'current' => $current,
            'channel' => $channel,
            'latest_stable' => $check['stable'] ?? null,
            'latest_test' => $check['test'] ?? null,
            'update_available' => $check['update_available'] ?? false,
            'history' => $this->get_update_history(),
            'migrations_pending' => $this->count_pending_migrations(),
        ];

        return view('Core\Plugins\Views\system_update', $data);
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

        // 2. Backup do estado atual (arquivos críticos)
        $backup_file = $this->create_backup($from_version);

        // 3. Aplicar via git (se for repo) ou download de ZIP
        try {
            $this->apply_git_update($target, $channel);
        } catch (\Throwable $e) {
            $db->table('sp_system_updates')->where('id', $update_id)->update([
                'status' => 'failed',
                'backup_file' => $backup_file,
            ]);
            ms(['status' => 'error', 'message' => 'Falha na atualização: ' . $e->getMessage()]);
        }

        // 4. Rodar migrações SQL pendentes
        $migrations_run = $this->run_pending_migrations();

        // 5. Atualizar version.json
        $this->write_version($target, $channel);

        // 6. Marcar como aplicado
        $db->table('sp_system_updates')->where('id', $update_id)->update([
            'status' => 'applied',
            'backup_file' => $backup_file,
            'applied_at' => date('Y-m-d H:i:s'),
        ]);

        ms([
            'status' => 'success',
            'message' => "Sistema atualizado para v{$target}!" . ($migrations_run > 0 ? " ({$migrations_run} migrações aplicadas)" : ""),
            'backup' => $backup_file
        ]);
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

        $tags = $this->fetch_github_tags();
        $stable = $this->pick_latest($tags, 'stable');
        $test = $this->pick_latest($tags, 'test');

        $target = ($channel === 'test' && $test) ? $test : $stable;
        $update_available = $target && version_compare($target['version'], $current_version, '>');

        return [
            'current_version' => $current_version,
            'latest_stable' => $stable['version'] ?? null,
            'latest_test' => $test['version'] ?? null,
            'latest_version' => $target['version'] ?? $current_version,
            'update_available' => (bool) $update_available,
            'channel' => $channel,
        ];
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
        file_put_contents(FCPATH . 'version.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    // ──────────────────────────────────────────────
    // Aplicar atualização via git
    // ──────────────────────────────────────────────
    private function apply_git_update(string $target, string $channel): void
    {
        $repo_dir = FCPATH;

        // Se não for repo git, inicializa
        if (!is_dir($repo_dir . '.git')) {
            $this->run_shell("cd {$repo_dir} && git init && git remote add origin https://github.com/lonardonetto/zapmatic_builder.git");
        }

        // Buscar tags
        $this->run_shell("cd {$repo_dir} && git fetch --tags origin 2>&1");

        // Determinar branch/tag alvo
        if ($channel === 'test') {
            $ref = 'beta';
            $this->run_shell("cd {$repo_dir} && git fetch origin beta:beta 2>&1 || true");
            $this->run_shell("cd {$repo_dir} && git checkout beta 2>&1 && git reset --hard origin/beta 2>&1");
        } else {
            $tag = "v{$target}";
            $this->run_shell("cd {$repo_dir} && git checkout {$tag} 2>&1 && git reset --hard {$tag} 2>&1");
        }

        // Corrigir permissões
        $this->run_shell("chmod -R 777 {$repo_dir}/app {$repo_dir}/inc 2>/dev/null || true");
    }

    // ──────────────────────────────────────────────
    // Rollback via git
    // ──────────────────────────────────────────────
    private function restore_backup(string $backup_path): void
    {
        // Se temos o backup em tar.gz, restaurar
        $tmp = FCPATH . 'writable/tmp_restore';
        if (is_dir($tmp)) $this->run_shell("rm -rf {$tmp}");

        mkdir($tmp, 0777, true);
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

        $tmp = FCPATH . 'writable/tmp_backup';
        if (is_dir($tmp)) $this->run_shell("rm -rf {$tmp}");
        mkdir($tmp, 0777, true);

        $this->run_shell("cp -a " . FCPATH . "inc {$tmp}/inc 2>/dev/null");
        $this->run_shell("cp -a " . FCPATH . "app {$tmp}/app 2>/dev/null");
        if (file_exists(FCPATH . 'version.json')) {
            copy(FCPATH . 'version.json', $tmp . '/version.json');
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
