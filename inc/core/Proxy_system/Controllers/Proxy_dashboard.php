<?php
namespace Core\Proxy_system\Controllers;

class Proxy_dashboard extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Proxy_system\Models\Proxy_systemModel();
        $query = "SELECT * FROM sp_proxies WHERE status = 'online'";  // Ajuste baseado no erro
        log_message('info', 'Acessando dashboard de proxies para usuário ID: ' . session()->get('user_id'));
    }

    /**
     * Dashboard principal com métricas em tempo real
     */
    public function index() {
        $data = [
            "title" => "Dashboard de Proxies",
            "desc" => "Monitoramento em tempo real dos proxies"
        ];

        // Estatísticas gerais
        $stats = $this->get_proxy_statistics();
        
        // Proxies com problemas
        $problematic_proxies = $this->get_problematic_proxies();
        
        // Dados para gráficos
        $performance_data = $this->get_performance_chart_data();
        
        $data_content = [
            'stats' => $stats,
            'problematic_proxies' => $problematic_proxies,
            'performance_data' => $performance_data,
            'config' => $this->config
        ];

        $data['content'] = view('Core\Proxy_system\Views\dashboard', $data_content);
        return view('Core\Proxy_system\Views\index', $data);
    }

    /**
     * API AJAX para atualizar dashboard em tempo real
     */
    public function ajax_realtime_stats() {
        $stats = $this->get_proxy_statistics();
        $recent_logs = $this->get_recent_proxy_logs();
        $alerts = $this->get_active_alerts();

        ms([
            'status' => 'success',
            'data' => [
                'stats' => $stats,
                'recent_logs' => $recent_logs,
                'alerts' => $alerts,
                'last_update' => date('H:i:s')
            ]
        ]);
    }

    /**
     * API para dados do mapa geográfico
     */
    public function ajax_map_data() {
        $proxies = db_fetch("*", "sp_proxies", ["status" => 1]);
        $map_data = [];
        
        foreach ($proxies as $proxy) {
            $location_info = $this->get_detailed_location($proxy->proxy);
            if ($location_info) {
                $usage_count = $this->get_proxy_usage_count($proxy->id);
                $status = $this->check_proxy_health($proxy->id);
                
                $map_data[] = [
                    'lat' => $location_info['lat'] ?? 0,
                    'lng' => $location_info['lng'] ?? 0,
                    'country' => $location_info['country'] ?? 'Unknown',
                    'city' => $location_info['city'] ?? 'Unknown',
                    'proxy_id' => $proxy->id,
                    'proxy_address' => $this->mask_proxy_address($proxy->proxy),
                    'usage_count' => $usage_count,
                    'status' => $status['status'], // online, slow, offline
                    'latency' => $status['latency'] ?? 0,
                    'last_check' => $status['last_check'] ?? null
                ];
            }
        }

        ms([
            'status' => 'success',
            'data' => $map_data
        ]);
    }

    /**
     * Teste manual de conectividade de um proxy
     */
    public function ajax_test_proxy($proxy_id = null) {
        if (!$proxy_id) {
            $proxy_id = post('proxy_id');
        }

        $proxy = db_get("*", "sp_proxies", ["id" => $proxy_id]);
        if (!$proxy) {
            ms(['status' => 'error', 'message' => 'Proxy não encontrado']);
        }

        $test_result = $this->perform_comprehensive_proxy_test($proxy->proxy);
        
        // Salvar resultado do teste no log
        $this->log_proxy_test($proxy_id, $test_result);

        ms([
            'status' => 'success',
            'data' => $test_result
        ]);
    }

    /**
     * Relatório de performance por período
     */
    public function ajax_performance_report() {
        $period = post('period', '24h'); // 1h, 24h, 7d, 30d
        $proxy_id = post('proxy_id', null);

        $report_data = $this->generate_performance_report($period, $proxy_id);

        ms([
            'status' => 'success',
            'data' => $report_data
        ]);
    }

    /**
     * Busca estatísticas gerais dos proxies
     */
    private function get_proxy_statistics() {
        $db = \Config\Database::connect();
        
        // Total de proxies
        $total_proxies = db_get("count(*) as count", "sp_proxies", ["status" => 1])->count;
        
        // Proxies online (testados nas últimas 5 minutos)
        $online_proxies = $this->count_online_proxies();
        
        // Proxies em uso
        $in_use_proxies = $db->query("
            SELECT COUNT(DISTINCT proxy) as count 
            FROM sp_accounts 
            WHERE proxy IS NOT NULL AND proxy != '' AND status = 1
        ")->getRow()->count;
        
        // Latência média nas últimas 24h
        $avg_latency = $this->calculate_avg_latency();

        // Taxa de sucesso nas últimas 24h
        $success_rate = $this->calculate_success_rate();

        return [
            'total_proxies' => $total_proxies,
            'online_proxies' => $online_proxies,
            'offline_proxies' => $total_proxies - $online_proxies,
            'in_use_proxies' => $in_use_proxies,
            'avg_latency' => round($avg_latency, 2),
            'success_rate' => round($success_rate, 2)
        ];
    }

    /**
     * Conta proxies online baseado nos últimos testes
     */
    private function count_online_proxies() {
        $db = \Config\Database::connect();
        
        // Verifica se a tabela existe
        if (!$this->table_exists('sp_proxy_health')) {
            return 0; // Retorna 0 se a tabela não existir ainda
        }

        $result = $db->query("
            SELECT COUNT(DISTINCT proxy_id) as count 
            FROM sp_proxy_health ph1
            WHERE ph1.created = (
                SELECT MAX(ph2.created) 
                FROM sp_proxy_health ph2 
                WHERE ph2.proxy_id = ph1.proxy_id
            )
            AND ph1.status = 'online' 
            AND ph1.created > " . (time() - 300)
        )->getRow();
        
        return $result->count ?? 0;
    }

    /**
     * Calcula latência média das últimas 24h
     */
    private function calculate_avg_latency() {
        $db = \Config\Database::connect();
        
        if (!$this->table_exists('sp_proxy_health')) {
            return 0;
        }

        $result = $db->query("
            SELECT AVG(latency) as avg_latency 
            FROM sp_proxy_health 
            WHERE created > " . (time() - 86400) . " 
            AND latency > 0
        )->getRow();
        
        return $result->avg_latency ?? 0;
    }

    /**
     * Calcula taxa de sucesso das últimas 24h
     */
    private function calculate_success_rate() {
        $db = \Config\Database::connect();
        
        if (!$this->table_exists('sp_proxy_health')) {
            return 100; // Assume 100% se não há dados ainda
        }

        $result = $db->query("
            SELECT 
                (SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as rate
            FROM sp_proxy_health 
            WHERE created > " . (time() - 86400)
        )->getRow();
        
        return $result->rate ?? 100;
    }

    /**
     * Busca proxies com problemas recentes
     */
    private function get_problematic_proxies() {
        $db = \Config\Database::connect();
        
        if (!$this->table_exists('sp_proxy_health')) {
            return [];
        }

        $query = "
            SELECT 
                p.*, 
                ph.status, 
                ph.latency, 
                ph.error_message, 
                ph.last_check,
                IFNULL(ac.accounts_using, 0) as accounts_using
            FROM sp_proxies p
            LEFT JOIN sp_proxy_health ph ON p.id = ph.proxy_id
            LEFT JOIN (
                SELECT proxy, COUNT(*) as accounts_using 
                FROM sp_accounts 
                WHERE proxy IS NOT NULL 
                GROUP BY proxy
            ) ac ON p.id = ac.proxy
            WHERE p.status = 1 
            AND (
                ph.status IS NULL OR
                ph.status != 'online' OR 
                ph.latency > 2000 OR 
                ph.last_check < " . (time() - 600) . "
            )
            ORDER BY ph.last_check DESC
            LIMIT 10
        ";
        
        return $db->query($query)->getResult();
    }

    /**
     * Teste abrangente de um proxy
     */
    private function perform_comprehensive_proxy_test($proxy_address) {
        $start_time = microtime(true);
        $tests = [];
        
        // Teste 1: Conectividade básica
        $connectivity_test = $this->test_proxy_connectivity($proxy_address);
        $tests['connectivity'] = $connectivity_test;
        
        // Teste 2: Verificação de IP/localização
        $location_test = $this->test_proxy_location($proxy_address);
        $tests['location'] = $location_test;
        
        // Teste 3: Teste de velocidade
        $speed_test = $this->test_proxy_speed($proxy_address);
        $tests['speed'] = $speed_test;
        
        $total_time = round((microtime(true) - $start_time) * 1000, 2);
        
        // Determinar status geral
        $overall_status = 'online';
        if (!$connectivity_test['success'] || !$location_test['success']) {
            $overall_status = 'offline';
        } elseif ($speed_test['latency'] > 2000) {
            $overall_status = 'slow';
        } elseif ($speed_test['latency'] > 1000) {
            $overall_status = 'problematic';
        }
        
        return [
            'overall_status' => $overall_status,
            'total_test_time' => $total_time,
            'tests' => $tests,
            'recommendations' => $this->get_proxy_recommendations($tests)
        ];
    }

    /**
     * Testa conectividade básica do proxy
     */
    private function test_proxy_connectivity($proxy_address) {
        $proxy_auth = null;
        if (strpos($proxy_address, '@') !== false) {
            list($auth, $host) = explode('@', $proxy_address, 2);
            $proxy_address = $host;
            $proxy_auth = $auth;
        }

        $test_url = 'http://httpbin.org/ip';
        $start_time = microtime(true);
        
        $ch = curl_init($test_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROXY, $proxy_address);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        if ($proxy_auth) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_auth);
        }
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $latency = round((microtime(true) - $start_time) * 1000, 2);
        curl_close($ch);
        
        $success = ($result !== false && $http_code == 200 && empty($error));
        
        return [
            'success' => $success,
            'latency' => $latency,
            'http_code' => $http_code,
            'error' => $error,
            'response_size' => strlen($result ?? ''),
            'proxy_ip' => $success ? json_decode($result)->origin ?? 'unknown' : null
        ];
    }

    /**
     * Teste de localização do proxy
     */
    private function test_proxy_location($proxy_address) {
        // Implementar teste de localização
        return [
            'success' => true,
            'location' => 'US',
            'city' => 'New York'
        ];
    }

    /**
     * Teste de velocidade do proxy
     */
    private function test_proxy_speed($proxy_address) {
        // Reusar o teste de conectividade para velocidade
        $connectivity = $this->test_proxy_connectivity($proxy_address);
        return [
            'latency' => $connectivity['latency'],
            'success' => $connectivity['success']
        ];
    }

    /**
     * Gera dados para gráficos de performance
     */
    private function get_performance_chart_data() {
        $db = \Config\Database::connect();
        
        if (!$this->table_exists('sp_proxy_health')) {
            // Retorna dados vazios se a tabela não existir
            return [
                'labels' => [],
                'latency' => [],
                'success_rate' => []
            ];
        }
        
        // Dados das últimas 24 horas, agrupados por hora
        $hourly_data = $db->query("
            SELECT 
                HOUR(FROM_UNIXTIME(created)) as hour,
                AVG(latency) as avg_latency,
                COUNT(*) as total_tests,
                SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as successful_tests
            FROM sp_proxy_health
            WHERE created > " . (time() - 86400) . "
            GROUP BY HOUR(FROM_UNIXTIME(created))
            ORDER BY hour
        ")->getResult();

        $chart_data = [
            'labels' => [],
            'latency' => [],
            'success_rate' => []
        ];

        foreach ($hourly_data as $data) {
            $chart_data['labels'][] = str_pad($data->hour, 2, '0', STR_PAD_LEFT) . ':00';
            $chart_data['latency'][] = round($data->avg_latency, 2);
            $chart_data['success_rate'][] = round(($data->successful_tests / $data->total_tests) * 100, 2);
        }

        return $chart_data;
    }

    /**
     * Mascara endereço do proxy para segurança
     */
    private function mask_proxy_address($proxy) {
        // Remove credenciais se houver
        if (strpos($proxy, '@') !== false) {
            list($auth, $host) = explode('@', $proxy, 2);
            return "***:***@{$host}";
        }
        return $proxy;
    }

    /**
     * Gera recomendações baseadas nos testes
     */
    private function get_proxy_recommendations($tests) {
        $recommendations = [];
        
        if (!$tests['connectivity']['success']) {
            $recommendations[] = 'Proxy não está respondendo. Verifique se está online.';
        }
        
        if (isset($tests['speed']['latency']) && $tests['speed']['latency'] > 2000) {
            $recommendations[] = 'Latência muito alta (' . $tests['speed']['latency'] . 'ms). Considere trocar de proxy.';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Proxy funcionando corretamente.';
        }
        
        return $recommendations;
    }

    /**
     * Verifica se uma tabela existe no banco
     */
    private function table_exists($table_name) {
        $db = \Config\Database::connect();
        return $db->tableExists($table_name);
    }

    /**
     * Funções auxiliares
     */
    private function get_recent_proxy_logs() {
        // Implementar busca de logs recentes
        return [];
    }

    private function get_active_alerts() {
        // Implementar busca de alertas ativos
        return [];
    }

    private function get_detailed_location($proxy) {
        // Implementar detecção de localização detalhada
        return [
            'lat' => 40.7128,
            'lng' => -74.0060,
            'country' => 'US',
            'city' => 'New York'
        ];
    }

    private function get_proxy_usage_count($proxy_id) {
        return db_get("count(*) as count", "sp_accounts", ["proxy" => $proxy_id, "status" => 1])->count;
    }

    private function check_proxy_health($proxy_id) {
        $db = \Config\Database::connect();
        
        if (!$this->table_exists('sp_proxy_health')) {
            return ['status' => 'unknown', 'latency' => 0, 'last_check' => null];
        }

        $health = $db->query("
            SELECT * FROM sp_proxy_health 
            WHERE proxy_id = ? 
            ORDER BY created DESC 
            LIMIT 1
        ", [$proxy_id])->getRow();

        if ($health) {
            return [
                'status' => $health->status,
                'latency' => $health->latency,
                'last_check' => $health->last_check
            ];
        }

        return ['status' => 'unknown', 'latency' => 0, 'last_check' => null];
    }

    private function log_proxy_test($proxy_id, $test_result) {
        if (!$this->table_exists('sp_proxy_health')) {
            return; // Não salva se a tabela não existir
        }

        db_insert('sp_proxy_health', [
            'proxy_id' => $proxy_id,
            'status' => $test_result['overall_status'],
            'latency' => $test_result['tests']['connectivity']['latency'] ?? 0,
            'error_message' => $test_result['tests']['connectivity']['error'] ?? '',
            'last_check' => time(),
            'created' => time()
        ]);
    }

    private function generate_performance_report($period, $proxy_id) {
        // Implementar geração de relatório de performance
        return [
            'period' => $period,
            'proxy_id' => $proxy_id,
            'data' => []
        ];
    }
} 