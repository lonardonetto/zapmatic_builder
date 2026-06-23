<?php
namespace Core\Whatsapp_profiles\Controllers;

class Whatsapp_profiles extends \CodeIgniter\Controller
{
    protected $helpers = ['url', 'general'];
    protected $response;

    const TB_ACCOUNTS = 'sp_accounts';
    const TB_WHATSAPP_AUTORESPONDER = 'sp_whatsapp_autoresponder';
    const TB_WHATSAPP_CHATBOT = 'sp_whatsapp_chatbot';
    const TB_WHATSAPP_SESSIONS = 'sp_whatsapp_sessions';
    const TB_WHATSAPP_WEBHOOK = 'sp_whatsapp_webhook';

    public function __construct()
    {
        // Construtor vazio para evitar problemas
    }

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Chama o método pai para inicialização padrão
        parent::initController($request, $response, $logger);

        // Força o tipo de resposta como JSON para todos os métodos
        $this->response = $response;

        // Remove qualquer output buffering
        while (ob_get_level())
            ob_end_clean();

        $reflect = new \ReflectionClass(get_called_class());
        $this->module = strtolower($reflect->getShortName());
        $this->config = include realpath(__DIR__ . "/../Config.php");
        $this->whatsapp_server_url = get_option('whatsapp_server_url', '');

        if ($this->whatsapp_server_url == "") {
            redirect_to(base_url("social_network_settings/index/" . $this->config['parent']['id']));
        }
    }

    protected function jsonResponse($data, $statusCode = 200)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON($data)
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    protected function parseCloudRuntimeData($account): array
    {
        $raw = $account->data ?? $account->tmp ?? '';
        if (empty($raw)) {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $parsed = json_decode((string) $raw, true);
        return is_array($parsed) ? $parsed : [];
    }

    protected function getCloudHealthCache()
    {
        try {
            return \Config\Services::cache();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function getCloudSafeParallelCap(string $throughputLevel = ''): int
    {
        return strtoupper(trim($throughputLevel)) === 'STANDARD' || $throughputLevel === '' ? 80 : 100;
    }

    protected function fetchCloudGraphProfile(string $phoneNumberId, string $accessToken): array
    {
        if ($phoneNumberId === '' || $accessToken === '') {
            return [
                'ok' => false,
                'http_code' => 0,
                'payload' => [],
                'error' => 'missing_phone_or_token',
            ];
        }

        $url = 'https://graph.facebook.com/v22.0/' . rawurlencode($phoneNumberId) . '?fields=id,display_phone_number,verified_name,quality_rating,throughput';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $payload = json_decode((string) $body, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        return [
            'ok' => $httpCode >= 200 && $httpCode < 300 && empty($payload['error']),
            'http_code' => $httpCode,
            'payload' => $payload,
            'error' => $payload['error']['message'] ?? $error,
        ];
    }

    protected function mapQualityBadge(string $qualityRating = ''): array
    {
        $qualityRating = strtoupper(trim($qualityRating));

        switch ($qualityRating) {
            case 'GREEN':
                return ['label' => 'Verde', 'badge' => 'success'];

            case 'YELLOW':
                return ['label' => 'Amarelo', 'badge' => 'warning'];

            case 'RED':
                return ['label' => 'Vermelho', 'badge' => 'danger'];

            default:
                return ['label' => 'Sem dado', 'badge' => 'secondary'];
        }
    }

    protected function buildCloudMetricsSummary(int $teamId, array $accountIds, int $since): array
    {
        $accountIds = array_values(array_unique(array_filter(array_map('intval', $accountIds))));
        $summary = [
            'total' => 0,
            'sent' => 0,
            'delivered' => 0,
            'read' => 0,
            'failed' => 0,
            'delivery_rate' => 0.0,
            'read_rate' => 0.0,
            'failure_rate' => 0.0,
        ];

        if ($teamId <= 0 || empty($accountIds)) {
            return $summary;
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_MESSAGE_STATUS);
        $builder->select('status, COUNT(*) AS total');
        $builder->where('team_id', $teamId);
        $builder->where('created >=', $since);
        $builder->whereIn('account_id', $accountIds);
        $builder->groupBy('status');
        $query = $builder->get();
        $rows = $query->getResult();
        $query->freeResult();

        foreach ($rows ?: [] as $row) {
            $status = strtolower((string) ($row->status ?? 'sent'));
            $total = (int) ($row->total ?? 0);
            if (!isset($summary[$status])) {
                continue;
            }

            $summary[$status] = $total;
            $summary['total'] += $total;
        }

        if ($summary['total'] > 0) {
            $summary['delivery_rate'] = round((($summary['delivered'] + $summary['read']) / $summary['total']) * 100, 1);
            $summary['read_rate'] = round(($summary['read'] / $summary['total']) * 100, 1);
            $summary['failure_rate'] = round(($summary['failed'] / $summary['total']) * 100, 1);
        }

        return $summary;
    }

    protected function getCloudLastError(int $teamId, int $accountId): ?array
    {
        if ($teamId <= 0 || $accountId <= 0) {
            return null;
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_MESSAGE_STATUS);
        $builder->select('schedule_id, campaign_name, to_number, meta_error_code, meta_error_title, meta_error_details, last_status_at');
        $builder->where('team_id', $teamId);
        $builder->where('account_id', $accountId);
        $builder->where('meta_error_code IS NOT NULL', null, false);
        $builder->orderBy('last_status_at', 'DESC');
        $builder->limit(1);
        $row = $builder->get()->getRow();

        if (!$row) {
            return null;
        }

        $display = '[' . (int) $row->meta_error_code . '] ' . trim((string) ($row->meta_error_title ?? ''));
        if (!empty($row->meta_error_details)) {
            $display .= ' - ' . trim((string) $row->meta_error_details);
        }

        return [
            'schedule_id' => (int) ($row->schedule_id ?? 0),
            'campaign_name' => (string) ($row->campaign_name ?? ''),
            'to_number' => (string) ($row->to_number ?? ''),
            'code' => (int) ($row->meta_error_code ?? 0),
            'title' => (string) ($row->meta_error_title ?? ''),
            'details' => (string) ($row->meta_error_details ?? ''),
            'last_status_at' => (int) ($row->last_status_at ?? 0),
            'display' => trim($display),
        ];
    }

    protected function getCloudTopErrors(int $teamId, int $accountId, int $since): array
    {
        if ($teamId <= 0 || $accountId <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_MESSAGE_STATUS);
        $builder->select('meta_error_code, meta_error_title, COUNT(*) AS total, MAX(last_status_at) AS last_status_at');
        $builder->where('team_id', $teamId);
        $builder->where('account_id', $accountId);
        $builder->where('created >=', $since);
        $builder->where('meta_error_code IS NOT NULL', null, false);
        $builder->groupBy('meta_error_code, meta_error_title');
        $builder->orderBy('total', 'DESC');
        $builder->orderBy('last_status_at', 'DESC');
        $builder->limit(5);
        $query = $builder->get();
        $rows = $query->getResult();
        $query->freeResult();

        $items = [];
        foreach ($rows ?: [] as $row) {
            $items[] = [
                'code' => (int) ($row->meta_error_code ?? 0),
                'title' => (string) ($row->meta_error_title ?? ''),
                'total' => (int) ($row->total ?? 0),
                'last_status_at' => (int) ($row->last_status_at ?? 0),
            ];
        }

        return $items;
    }

    protected function getCloudRecentCampaigns(int $teamId, int $accountId): array
    {
        if ($teamId <= 0 || $accountId <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $sql = "
            SELECT 
                schedule_id,
                MAX(campaign_name) AS campaign_name,
                MAX(last_status_at) AS last_status_at,
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent_total,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_total,
                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) AS read_total,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_total
            FROM " . TB_WHATSAPP_MESSAGE_STATUS . "
            WHERE team_id = ? AND account_id = ? AND schedule_id > 0
            GROUP BY schedule_id
            ORDER BY last_status_at DESC
            LIMIT 5
        ";
        $query = $db->query($sql, [$teamId, $accountId]);
        $rows = $query->getResult();

        $items = [];
        foreach ($rows ?: [] as $row) {
            $items[] = [
                'schedule_id' => (int) ($row->schedule_id ?? 0),
                'campaign_name' => (string) ($row->campaign_name ?? ('Campanha #' . (int) ($row->schedule_id ?? 0))),
                'last_status_at' => (int) ($row->last_status_at ?? 0),
                'total' => (int) ($row->total ?? 0),
                'sent' => (int) ($row->sent_total ?? 0),
                'delivered' => (int) ($row->delivered_total ?? 0),
                'read' => (int) ($row->read_total ?? 0),
                'failed' => (int) ($row->failed_total ?? 0),
            ];
        }

        return $items;
    }

    protected function buildCloudWabaOverview(int $teamId, string $wabaId, int $currentAccountId): array
    {
        $overview = [
            'waba_id' => $wabaId,
            'accounts_total' => 0,
            'numbers_active' => 0,
            'metrics_24h' => [
                'total' => 0,
                'sent' => 0,
                'delivered' => 0,
                'read' => 0,
                'failed' => 0,
                'delivery_rate' => 0.0,
                'read_rate' => 0.0,
                'failure_rate' => 0.0,
            ],
        ];

        if ($teamId <= 0 || $wabaId === '') {
            return $overview;
        }

        $accounts = db_fetch('*', TB_ACCOUNTS, [
            'team_id' => $teamId,
            'social_network' => 'whatsapp',
            'category' => 'profile',
            'login_type' => 1,
        ], 'id', 'ASC');

        $accountIds = [];
        foreach ($accounts ?: [] as $account) {
            $runtime = $this->parseCloudRuntimeData($account);
            $accountWabaId = trim((string) ($runtime['waba_id'] ?? ''));
            if ($accountWabaId !== $wabaId) {
                continue;
            }

            $overview['accounts_total']++;
            if ((int) ($account->status ?? 0) === 1) {
                $overview['numbers_active']++;
            }
            $accountIds[] = (int) $account->id;
        }

        if (!empty($accountIds)) {
            $overview['metrics_24h'] = $this->buildCloudMetricsSummary($teamId, $accountIds, time() - 86400);
        }

        return $overview;
    }

    protected function buildCloudHealthState($account, array $profile, array $metrics24h, ?array $lastError): array
    {
        $qualityRaw = strtoupper(trim((string) ($profile['payload']['quality_rating'] ?? '')));

        if ((int) ($account->status ?? 0) !== 1) {
            return [
                'state' => 'critical',
                'label' => 'Desconectado',
                'badge' => 'danger',
                'message' => 'O perfil Cloud está desconectado ou precisa de atenção manual.',
            ];
        }

        if (!empty($lastError['code']) && (int) $lastError['code'] === 131042) {
            return [
                'state' => 'critical',
                'label' => 'Bloqueado por pagamento',
                'badge' => 'danger',
                'message' => 'A Meta recusou envios recentes por pendência de cobrança na conta WhatsApp Business.',
            ];
        }

        if ($qualityRaw === 'RED') {
            return [
                'state' => 'critical',
                'label' => 'Qualidade crítica',
                'badge' => 'danger',
                'message' => 'A qualidade oficial do número está em nível crítico na Meta.',
            ];
        }

        if ($qualityRaw === 'YELLOW' || ($metrics24h['total'] >= 5 && $metrics24h['failure_rate'] >= 20)) {
            return [
                'state' => 'warning',
                'label' => 'Atenção',
                'badge' => 'warning',
                'message' => 'Há sinais de atenção na qualidade ou na taxa de falha recente deste número.',
            ];
        }

        if (!empty($profile['ok']) || $qualityRaw === 'GREEN') {
            return [
                'state' => 'good',
                'label' => 'Saudável',
                'badge' => 'success',
                'message' => 'Sem alertas críticos recentes. O número aparenta operar normalmente.',
            ];
        }

        return [
            'state' => 'unknown',
            'label' => 'Sem diagnóstico completo',
            'badge' => 'secondary',
            'message' => 'Não foi possível atualizar os sinais oficiais da Meta agora.',
        ];
    }

    protected function buildCloudHealthSnapshot($account, bool $forceRefresh = false): array
    {
        $teamId = (int) ($account->team_id ?? 0);
        $accountId = (int) ($account->id ?? 0);
        $cacheKey = 'wa_cloud_health_snapshot_' . $teamId . '_' . $accountId;
        $cache = $this->getCloudHealthCache();

        if (!$forceRefresh && $cache) {
            try {
                $cached = $cache->get($cacheKey);
                if (is_array($cached) && !empty($cached)) {
                    return $cached;
                }
            } catch (\Throwable $e) {
            }
        }

        $runtime = $this->parseCloudRuntimeData($account);
        $wabaId = trim((string) ($runtime['waba_id'] ?? ''));
        $phoneNumberId = trim((string) ($runtime['phone_number_id'] ?? $account->pid ?? ''));
        $accessToken = trim((string) ($runtime['token'] ?? $runtime['access_token'] ?? ''));
        $graphProfile = $this->fetchCloudGraphProfile($phoneNumberId, $accessToken);
        $graphPayload = $graphProfile['payload'] ?? [];

        $qualityMeta = $this->mapQualityBadge((string) ($graphPayload['quality_rating'] ?? ''));
        $throughputLevel = trim((string) ($graphPayload['throughput']['level'] ?? ''));
        $safeParallelCap = $this->getCloudSafeParallelCap($throughputLevel);

        $metrics24h = $this->buildCloudMetricsSummary($teamId, [$accountId], time() - 86400);
        $metrics7d = $this->buildCloudMetricsSummary($teamId, [$accountId], time() - 604800);
        $lastError = $this->getCloudLastError($teamId, $accountId);
        $topErrors = $this->getCloudTopErrors($teamId, $accountId, time() - 604800);
        $recentCampaigns = $this->getCloudRecentCampaigns($teamId, $accountId);
        $wabaOverview = $this->buildCloudWabaOverview($teamId, $wabaId, $accountId);
        $health = $this->buildCloudHealthState($account, $graphProfile, $metrics24h, $lastError);

        $operationalHint = 'A Meta não expõe um saldo diário oficial restante por número neste endpoint. Exibimos uso recente e capacidade simultânea segura.';
        if (!empty($lastError['code']) && (int) $lastError['code'] === 131042) {
            $operationalHint = 'Resolva primeiro a pendência de cobrança da WABA na Meta. Enquanto o erro 131042 persistir, novos disparos podem ser aceitos inicialmente, mas falhar no status final.';
        } elseif ($health['state'] === 'warning') {
            $operationalHint = 'Recomendação operacional: reduzir volume até a qualidade estabilizar ou a taxa de falha voltar ao normal.';
        } elseif ($health['state'] === 'good') {
            $operationalHint = 'Capacidade oficial consultada com sucesso. Use o limite simultâneo seguro como referência de operação deste número.';
        }

        $snapshot = [
            'account' => [
                'id' => $accountId,
                'ids' => (string) ($account->ids ?? ''),
                'name' => (string) ($account->name ?? ''),
                'status' => (int) ($account->status ?? 0),
                'display_phone_number' => (string) ($graphPayload['display_phone_number'] ?? ($runtime['display_phone_number'] ?? $account->pid ?? '')),
                'verified_name' => (string) ($graphPayload['verified_name'] ?? ($runtime['verified_name'] ?? '')),
                'phone_number_id' => $phoneNumberId,
                'waba_id' => $wabaId,
            ],
            'health' => $health,
            'quality' => [
                'raw' => strtoupper(trim((string) ($graphPayload['quality_rating'] ?? ''))),
                'label' => $qualityMeta['label'],
                'badge' => $qualityMeta['badge'],
            ],
            'capacity' => [
                'throughput_level' => $throughputLevel,
                'throughput_label' => $throughputLevel !== '' ? $throughputLevel : 'Sem dado oficial',
                'safe_parallel_cap' => $safeParallelCap,
                'official_daily_remaining_available' => false,
                'official_daily_remaining_label' => 'Não disponível oficialmente',
            ],
            'metrics_24h' => $metrics24h,
            'metrics_7d' => $metrics7d,
            'last_error' => $lastError,
            'top_errors_7d' => $topErrors,
            'recent_campaigns' => $recentCampaigns,
            'waba_overview' => $wabaOverview,
            'graph' => [
                'ok' => (bool) ($graphProfile['ok'] ?? false),
                'http_code' => (int) ($graphProfile['http_code'] ?? 0),
                'error' => (string) ($graphProfile['error'] ?? ''),
            ],
            'operational_hint' => $operationalHint,
            'cached_at' => time(),
        ];

        if ($cache) {
            try {
                $cache->save($cacheKey, $snapshot, 300);
            } catch (\Throwable $e) {
            }
        }

        return $snapshot;
    }

    protected function normalizeCloudHealthIds($rawIds): array
    {
        if (is_array($rawIds)) {
            $items = $rawIds;
        } else {
            $items = explode(',', (string) $rawIds);
        }

        $ids = [];
        foreach ($items as $item) {
            $item = preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $item));
            if ($item === '') {
                continue;
            }

            $ids[] = $item;
        }

        return array_values(array_unique($ids));
    }

    protected function getCloudAccountsForHealth(int $teamId, array $idsFilter = []): array
    {
        if ($teamId <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table(self::TB_ACCOUNTS);
        $builder->where('team_id', $teamId);
        $builder->where('social_network', 'whatsapp');
        $builder->where('category', 'profile');
        $builder->where('login_type', 1);

        if (!empty($idsFilter)) {
            $builder->whereIn('ids', $idsFilter);
        }

        $builder->orderBy('id', 'DESC');
        $query = $builder->get();
        $rows = $query->getResult();
        $query->freeResult();

        return $rows ?: [];
    }

    protected function getCloudAccountForHealth(int $teamId, string $ids)
    {
        if ($teamId <= 0 || $ids === '') {
            return null;
        }

        return db_get('*', self::TB_ACCOUNTS, [
            'ids' => $ids,
            'team_id' => $teamId,
            'social_network' => 'whatsapp',
            'category' => 'profile',
            'login_type' => 1,
        ]);
    }

    protected function canUseCloudEmbeddedSignup($team_id = 0)
    {
        $team_id = $team_id ?: get_team("id");

        if ((int) permission("cloud_api_enabled", $team_id) !== 1) {
            return false;
        }

        $team = db_get("*", TB_TEAM, "id = '{$team_id}'");
        if (empty($team)) {
            return false;
        }

        $team_permissions = [];
        if (!empty($team->permissions)) {
            $decoded_permissions = json_decode($team->permissions, true);
            if (is_array($decoded_permissions)) {
                $team_permissions = $decoded_permissions;
            }
        }

        $team_default = true;
        if (array_key_exists("cloud_api_embedded_signup", $team_permissions)) {
            $team_default = (int) $team_permissions["cloud_api_embedded_signup"] === 1;
        }

        $current_user_id = get_user("id");
        if (!$current_user_id || (int) $team->owner === (int) $current_user_id) {
            return $team_default;
        }

        $team_member = db_get("*", TB_TEAM_MEMBER, "team_id = '{$team->id}' AND uid = '{$current_user_id}'");
        if (!empty($team_member) && !empty($team_member->permissions)) {
            $member_permissions = json_decode($team_member->permissions, true);
            if (is_array($member_permissions) && array_key_exists("cloud_api_embedded_signup", $member_permissions)) {
                return (int) $member_permissions["cloud_api_embedded_signup"] === 1;
            }
        }

        return $team_default;
    }

    public function index()
    {
        redirect_to(get_module_url("oauth"));
    }

    public function oauth($instance_id = false)
    {
        $team_id = get_team("id");
        $connect_mode = trim((string) $this->request->getGet('connect'));
        $open_tab = trim((string) $this->request->getGet('open'));
        $phone_post_requested = isset($_POST['phone']);
        $pending_session = db_get("*", self::TB_WHATSAPP_SESSIONS, ["status" => 0, "team_id" => $team_id]);
        $should_prepare_baileys_session = (!empty($instance_id) || $connect_mode === 'baileys' || $phone_post_requested);

        $content_data = [
            "config" => $this->config,
            "cloud_api_embedded_signup_enabled" => $this->canUseCloudEmbeddedSignup($team_id),
            "page_title" => "Central de Conexão WhatsApp",
            "page_subtitle" => "Gerencie conexões Baileys, Cloud API e Whatsmeow em um único lugar.",
            "show_baileys_qr" => false,
            "show_whatsmeow_qr" => false,
            "open_whatsmeow_drawer" => $open_tab === 'whatsmeow',
            "pending_baileys_session" => $pending_session,
            "baileys_connect_url" => base_url("whatsapp_profiles/generate_instance")
        ];

        // Busca a conta específica se um instance_id foi fornecido (apenas Baileys)
        $account = db_get("*", self::TB_ACCOUNTS, [
            "social_network" => "whatsapp",
            "category" => "profile",
            "login_type" => 2,  // Apenas Baileys
            "token" => $instance_id,
            "team_id" => $team_id
        ]);

        // Busca TODOS os perfis de WhatsApp (Baileys e Cloud API) para o time
        $accounts = db_fetch("*", self::TB_ACCOUNTS, [
            "social_network" => "whatsapp",
            "category" => "profile",
            "team_id" => $team_id
        ]);

        $content_data['accounts'] = $accounts;

        if ($should_prepare_baileys_session) {
            if (empty($account)) {
                $session = db_get("*", self::TB_WHATSAPP_SESSIONS, ["status" => 0, "team_id" => $team_id]);
                if (empty($session)) {
                    $instance_id = strtoupper(uniqid());
                    db_delete(self::TB_WHATSAPP_SESSIONS, ["status" => 0, "team_id" => $team_id]);
                    db_insert(self::TB_WHATSAPP_SESSIONS, [
                        "ids" => ids(),
                        "instance_id" => $instance_id,
                        "team_id" => $team_id,
                        "data" => NULL,
                        "status" => 0
                    ]);

                    $content_data['instance_id'] = $instance_id;
                    $content_data['pending_baileys_session'] = (object) [
                        'instance_id' => $instance_id,
                    ];
                } else {
                    $content_data['instance_id'] = $session->instance_id;
                    $content_data['pending_baileys_session'] = $session;
                }
            } else {
                db_update(self::TB_WHATSAPP_SESSIONS, ['status' => 0], ['instance_id' => $account->token]);
                $content_data['instance_id'] = $account->token;
                $content_data['pending_baileys_session'] = (object) [
                    'instance_id' => $account->token,
                ];
            }

            $content_data['show_baileys_qr'] = !empty($content_data['instance_id']);
        } else {
            $content_data['instance_id'] = '';
        }

        $content_data["has_pair"] = false;
        $content_data["pair_code"] = "";
        $content_data["error_msg"] = "";
        $content_data["has_error"] = false;

        if ($phone_post_requested && !empty($content_data['instance_id'])) {
            $instance_id = $content_data['instance_id'];
            $account = db_get("*", self::TB_ACCOUNTS, ["social_network" => "whatsapp", "category" => "profile", "token" => $instance_id, "team_id" => $team_id]);
            $access_token = get_team("ids");
            if ($account) {
                $session = db_get("*", self::TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "status" => 0]);
                if ($session) {
                    if ($session->instance_id != $instance_id) {
                        db_update(self::TB_WHATSAPP_SESSIONS, [
                            "instance_id" => $instance_id,
                            "status" => 0
                        ], ['id' => $session->id]);
                    } else {
                        db_insert(self::TB_WHATSAPP_SESSIONS, [
                            "ids" => ids(),
                            "instance_id" => $instance_id,
                            "team_id" => $team_id,
                            "data" => NULL,
                            "status" => 0
                        ]);
                    }
                }
            } else {
                if (!check_number_account("whatsapp", "profile", false, false)) {
                    return false;
                    $content_data["has_pair"] = false;
                }
            }

            $results = wa_get_curl("get_qrcode", ["instance_id" => $instance_id, "access_token" => $access_token]);
            $result = wa_get_curl("get_paircode", ["instance_id" => $_POST['instance_id'], "access_token" => $access_token, "phone" => $_POST['phone']]);

            if (isset($results) && isset($result) && $result->status == "success") {

                $content_data["has_pair"] = true;
                $content_data["pair_code"] = isset($result->pairing_code) ? $result->pairing_code : (isset($result->code) ? $result->code : "");
                $content_data["has_error"] = false;
            } else if (isset($result) && $result->status == "error") {
                $content_data["error_msg"] = $result->message;

                $content_data["has_error"] = true;
                $content_data["has_pair"] = true;
            } else {
                $content_data["has_error"] = true;
                $content_data["has_pair"] = false;
                $content_data["error_msg"] = __("Cannot connect to WhatsApp server. Please make sure the WhatsApp server running.") . "</br>" . __("You can follow by documentation at <a href='#' target='_blank'>here</a>");
            }
        }

        $data = [
            "title" => "Central de Conexão WhatsApp",
            "desc" => "Gerencie conexões Baileys, Cloud API e perfis em um único painel.",
            "config" => $this->config,
            "content" => view('Core\Whatsapp_profiles\Views\oauth', $content_data)
        ];

        return view('Core\Whatsapp_profiles\Views\index', $data);
    }

    public function cloud_health_batch()
    {
        try {
            $teamId = (int) get_team('id');
            if ($teamId <= 0) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Time não identificado para consultar a saúde Cloud.',
                ], 403);
            }

            $rawIds = $this->request->getPost('ids');
            if ($rawIds === null) {
                $rawIds = $this->request->getGet('ids');
            }

            $idsFilter = $this->normalizeCloudHealthIds($rawIds);
            $forceRefresh = (int) ($this->request->getPost('refresh') ?? $this->request->getGet('refresh') ?? 0) === 1;
            $accounts = $this->getCloudAccountsForHealth($teamId, $idsFilter);

            $items = [];
            foreach ($accounts as $account) {
                $snapshot = $this->buildCloudHealthSnapshot($account, $forceRefresh);
                $items[(string) $account->ids] = [
                    'html' => view('Core\Whatsapp_profiles\Views\cloud_health_inline', [
                        'account' => $account,
                        'snapshot' => $snapshot,
                    ]),
                    'snapshot' => $snapshot,
                ];
            }

            return $this->jsonResponse([
                'status' => 'success',
                'items' => $items,
                'count' => count($items),
                'refresh' => $forceRefresh,
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Não foi possível carregar o resumo Cloud agora.',
            ], 500);
        }
    }

    public function cloud_health($ids = '')
    {
        $teamId = (int) get_team('id');
        $ids = trim((string) $ids);
        $account = $this->getCloudAccountForHealth($teamId, $ids);

        if (empty($account)) {
            return redirect()->to(base_url('whatsapp_profiles/oauth'));
        }

        $forceRefresh = (int) $this->request->getGet('refresh') === 1;
        $snapshot = $this->buildCloudHealthSnapshot($account, $forceRefresh);

        $content = view('Core\Whatsapp_profiles\Views\cloud_health', [
            'config' => $this->config,
            'account' => $account,
            'snapshot' => $snapshot,
            'force_refresh' => $forceRefresh,
        ]);

        $data = [
            'title' => 'Saúde Cloud API',
            'desc' => $this->config['desc'],
            'config' => $this->config,
            'content' => $content,
        ];

        return view('Core\Whatsapp_profiles\Views\index', $data);
    }

    /**
     * Página de gestão de templates oficiais (Meta) por conta Cloud API.
     *
     * Reutiliza `sp_whatsapp_template`:
     * - type=WA_TEMPLATE_TYPE_META_DRAFT: rascunhos (blueprints) do Zapmatic
     * - type=WA_TEMPLATE_TYPE_META_STATUS: espelho de status por WABA/idioma
     * - type=WA_TEMPLATE_TYPE_META_APPROVED: aprovados prontos para envio
     */
    public function meta_templates($ids = "")
    {
        // Página desativada: o fluxo Oficial (Meta) foi movido para os módulos de templates (ex.: Modelo de botão).
        return redirect()->to(base_url('whatsapp_profiles'));

        $team_id = get_team("id");
        $account = db_get("*", TB_ACCOUNTS, ["ids" => $ids, "team_id" => $team_id, "login_type" => 1]);
        if (empty($account)) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Conta Cloud API não encontrada.'], 404);
        }

        $drafts = db_fetch("*", TB_WHATSAPP_TEMPLATE, ["team_id" => $team_id, "type" => WA_TEMPLATE_TYPE_META_DRAFT], "id", "DESC");

        $db = \Config\Database::connect();
        $statuses = $db->query(
            "SELECT * FROM " . TB_WHATSAPP_TEMPLATE . "
             WHERE team_id = ? AND type = ?
               AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.account_ids')) = ?
             ORDER BY changed DESC",
            [$team_id, WA_TEMPLATE_TYPE_META_STATUS, $ids]
        )->getResult();

        $content = view('Core\Whatsapp_profiles\Views\meta_templates', [
            'account' => $account,
            'drafts' => $drafts,
            'statuses' => $statuses,
        ]);

        // Renderiza dentro do layout padrão (Stackmin) para manter o mesmo design do sistema
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "config" => $this->config,
            "content" => $content,
        ];

        return view('Core\Whatsapp_profiles\Views\index', $data);
    }

    /**
     * Salva rascunho (blueprint) de template oficial Meta.
     *
     * Campos principais:
     * - template_name, category, languages (csv)
     * - header_format, header_text, header_media (arquivo)
     * - body_text, body_example (quando houver {{n}})
     * - footer_text
     * - btn1_text, btn2_text (quick replies), btn3_url (TEXTO|URL)
     */
    public function meta_draft_save($account_ids = "")
    {
        return $this->jsonResponse(['status' => 'error', 'message' => 'Funcionalidade desativada. Use o módulo de Modelo de botão para submissão Meta.'], 410);

        if (!$this->request->isAJAX() && $this->request->getMethod() !== 'post') {
            // permitimos POST normal do actionForm também
        }

        try {
            $team_id = get_team("id");
            $template_name = trim((string) post('template_name'));
            $category = strtoupper(trim((string) post('category')));
            $languages_raw = trim((string) post('languages'));
            $header_format = strtoupper(trim((string) post('header_format')));
            $header_text = (string) post('header_text');
            $body_text = (string) post('body_text');
            $body_example = (string) post('body_example');
            $footer_text = (string) post('footer_text');

            validate('null', __('Nome do template'), $template_name);
            validate('null', __('Categoria'), $category);
            validate('null', __('Idiomas'), $languages_raw);
            validate('null', __('Body'), $body_text);

            if (!in_array($category, ['MARKETING', 'UTILITY'], true)) {
                throw new \Exception('Categoria inválida. Use MARKETING ou UTILITY.');
            }

            // Linguagens
            $languages = array_values(array_filter(array_map(function ($l) {
                return trim($l);
            }, explode(',', $languages_raw))));
            if (empty($languages)) {
                throw new \Exception('Informe ao menos um idioma (ex: pt_BR).');
            }

            // Header
            if ($header_format === '') $header_format = 'NONE';
            if (!in_array($header_format, ['NONE', 'TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                throw new \Exception('Header inválido.');
            }

            // Upload de mídia do header (se aplicável)
            $header_media_file = $this->request->getFile('header_media');
            $header_media = null;
            if (in_array($header_format, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                if (!$header_media_file || !$header_media_file->isValid()) {
                    throw new \Exception('Arquivo de header mídia é obrigatório para IMAGE/VIDEO/DOCUMENT.');
                }
                $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $header_media_file->getExtension();
                $header_media_file->move(WRITEPATH . 'uploads', $newName, true);
                $diskPath = WRITEPATH . 'uploads/' . $newName;
                $publicUrl = base_url('writable/uploads/' . $newName);
                $header_media = [
                    'filename' => $newName,
                    'disk_path' => $diskPath,
                    'public_url' => $publicUrl,
                    'mime' => $header_media_file->getMimeType(),
                ];
            }

            // Placeholder example
            $has_placeholders = preg_match('/\{\{\d+\}\}/', $body_text) === 1;
            $example_values = [];
            if ($has_placeholders) {
                // Regra comum da Meta: variável não pode estar no início/fim do template
                if (preg_match('/^\s*\{\{\d+\}\}/', $body_text) || preg_match('/\{\{\d+\}\}\s*$/', $body_text)) {
                    throw new \Exception('A Meta não permite variáveis no início ou no fim do body. Ajuste o texto (adicione palavras antes/depois).');
                }
                if (trim($body_example) === '') {
                    throw new \Exception('Body example é obrigatório quando houver variáveis {{n}}.');
                }
                $example_values = array_values(array_map('trim', explode('|', $body_example)));
            }

            // Botões (simplificado)
            $buttons = [];
            $btn1 = trim((string) post('btn1_text'));
            $btn2 = trim((string) post('btn2_text'));
            $btn3 = trim((string) post('btn3_url'));
            if ($btn1 !== '') $buttons[] = ['type' => 'QUICK_REPLY', 'text' => mb_substr($btn1, 0, 25)];
            if ($btn2 !== '') $buttons[] = ['type' => 'QUICK_REPLY', 'text' => mb_substr($btn2, 0, 25)];
            if ($btn3 !== '') {
                $parts = array_map('trim', explode('|', $btn3, 2));
                if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                    throw new \Exception('Botão URL inválido. Use o formato: TEXTO|https://exemplo.com');
                }
                $buttons[] = ['type' => 'URL', 'text' => mb_substr($parts[0], 0, 25), 'url' => $parts[1]];
            }

            $draft_data = [
                'category' => $category,
                'languages' => $languages,
                'header' => [
                    'format' => $header_format,
                    'text' => $header_text,
                    'media' => $header_media,
                ],
                'body' => [
                    'text' => $body_text,
                    'example' => $example_values,
                ],
                'footer' => [
                    'text' => $footer_text,
                ],
                'buttons' => $buttons,
                'created_at' => time(),
            ];

            // Upsert por team + type + name (mantém 1 rascunho por nome)
            $existing = db_get("*", TB_WHATSAPP_TEMPLATE, ["team_id" => $team_id, "type" => WA_TEMPLATE_TYPE_META_DRAFT, "name" => $template_name]);
            if (empty($existing)) {
                db_insert(TB_WHATSAPP_TEMPLATE, [
                    'ids' => ids(),
                    'team_id' => $team_id,
                    'type' => WA_TEMPLATE_TYPE_META_DRAFT,
                    'name' => $template_name,
                    'data' => json_encode($draft_data),
                    'created' => time(),
                    'changed' => time(),
                ]);
            } else {
                db_update(TB_WHATSAPP_TEMPLATE, [
                    'data' => json_encode($draft_data),
                    'changed' => time(),
                ], ['id' => $existing->id]);
            }

            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Rascunho salvo com sucesso. Agora clique em Submeter.',
                'redirect' => base_url('whatsapp_profiles/meta_templates/' . $account_ids),
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Submete um rascunho para aprovação na Meta (por conta/WABA e por idioma).
     * Executa upload de mídia (se header for mídia) e cria o template via Business Management API.
     */
    public function meta_draft_submit($account_ids = "", $draft_id = 0)
    {
        return $this->jsonResponse(['status' => 'error', 'message' => 'Funcionalidade desativada. Use o módulo de Modelo de botão para submissão Meta.'], 410);

        try {
            $team_id = get_team("id");
            $account = db_get("*", TB_ACCOUNTS, ["ids" => $account_ids, "team_id" => $team_id, "login_type" => 1]);
            if (empty($account)) {
                throw new \Exception('Conta Cloud API não encontrada.');
            }

            $draft = db_get("*", TB_WHATSAPP_TEMPLATE, ["id" => (int)$draft_id, "team_id" => $team_id, "type" => WA_TEMPLATE_TYPE_META_DRAFT]);
            if (empty($draft)) {
                throw new \Exception('Rascunho não encontrado.');
            }

            $draftData = json_decode($draft->data, true) ?: [];
            $accData = json_decode($account->data, true) ?: [];
            $waba_id = $accData['waba_id'] ?? null;
            $phone_number_id = $accData['phone_number_id'] ?? null;
            $token = $accData['token'] ?? null;

            if (!$waba_id || !$phone_number_id || !$token) {
                throw new \Exception('Credenciais Cloud API incompletas (waba_id/phone_number_id/token).');
            }

            $templateName = $draft->name;
            $category = $draftData['category'] ?? 'MARKETING';
            $languages = $draftData['languages'] ?? ['pt_BR'];

            // Upload de mídia (se houver)
            $default_header_media = null; // media_id para ENVIO
            $approval_header_handle = null; // handle (h) para APROVAÇÃO do template
            $header = $draftData['header'] ?? [];
            $headerFormat = strtoupper((string)($header['format'] ?? 'NONE'));
            if (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                $media = $header['media'] ?? null;
                $diskPath = is_array($media) ? ($media['disk_path'] ?? null) : null;
                $mime = is_array($media) ? ($media['mime'] ?? null) : null;
                if (!$diskPath || !is_file($diskPath)) {
                    throw new \Exception('Arquivo de mídia do header não encontrado no servidor. Salve o rascunho novamente.');
                }

                /**
                 * 1) Upload para ENVIO (Cloud API /media) -> retorna media_id (usado depois em /messages)
                 */
                $mediaUploadUrl = "https://graph.facebook.com/v22.0/{$phone_number_id}/media";
                $ch = curl_init($mediaUploadUrl);
                $postFields = [
                    'messaging_product' => 'whatsapp',
                    'file' => new \CURLFile($diskPath, $mime ?: 'application/octet-stream', basename($diskPath)),
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $resp = curl_exec($ch);
                $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $decoded = json_decode((string)$resp, true);
                if ($http < 200 || $http >= 300 || empty($decoded['id'])) {
                    $msg = $decoded['error']['message'] ?? ('Falha no upload de mídia para envio (HTTP ' . $http . ')');
                    throw new \Exception($msg);
                }
                $default_header_media = ['id' => $decoded['id']];

                /**
                 * 2) Upload para APROVAÇÃO (Graph Resumable Upload /{app_id}/uploads) -> retorna handle `h`
                 * Usado em components[].example.header_handle no endpoint /{waba_id}/message_templates.
                 */
                $debugUrl = "https://graph.facebook.com/v22.0/debug_token?input_token=" . urlencode($token);
                $ch = curl_init($debugUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $debugResp = curl_exec($ch);
                $debugHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $debug = json_decode((string)$debugResp, true);
                $app_id = $debug['data']['app_id'] ?? null;
                if ($debugHttp < 200 || $debugHttp >= 300 || !$app_id) {
                    throw new \Exception('Não foi possível identificar o app_id do token (debug_token).');
                }

                $fileSize = filesize($diskPath);
                $fileName = basename($diskPath);
                $fileType = $mime ?: 'application/octet-stream';

                // cria sessão de upload com assinatura (retorna uploadId com ?sig=...)
                $createUploadUrl = "https://graph.facebook.com/v22.0/{$app_id}/uploads"
                    . "?file_length={$fileSize}&file_type=" . urlencode($fileType) . "&file_name=" . urlencode($fileName);
                $ch = curl_init($createUploadUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $createResp = curl_exec($ch);
                $createHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $create = json_decode((string)$createResp, true);
                $uploadId = $create['id'] ?? null;
                if ($createHttp < 200 || $createHttp >= 300 || !$uploadId) {
                    $msg = $create['error']['message'] ?? 'Falha ao criar sessão de upload (uploads).';
                    throw new \Exception($msg);
                }

                // envia bytes para URL composta pelo uploadId completo (sem url-encode, pois contém query sig)
                $uploadBytesUrl = "https://graph.facebook.com/v22.0/{$uploadId}";
                $bytes = file_get_contents($diskPath);
                $ch = curl_init($uploadBytesUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'file_offset: 0',
                    'Content-Type: application/octet-stream'
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $bytes);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                $upResp = curl_exec($ch);
                $upHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $up = json_decode((string)$upResp, true);
                $approval_header_handle = $up['h'] ?? null;
                if ($upHttp < 200 || $upHttp >= 300 || !$approval_header_handle) {
                    $msg = $up['error']['message'] ?? 'Falha ao enviar bytes da mídia (upload session).';
                    throw new \Exception($msg);
                }
            }

            // Monta components para criação do template
            $body = $draftData['body'] ?? [];
            $bodyText = (string)($body['text'] ?? '');
            $bodyExample = $body['example'] ?? [];
            $footerText = (string)(($draftData['footer']['text'] ?? '') ?: '');
            $buttons = $draftData['buttons'] ?? [];

            $created = 0;
            $errors = 0;

            foreach ((array)$languages as $lang) {
                $lang = trim((string)$lang);
                if ($lang === '') continue;

                $components = [];
                if ($headerFormat === 'TEXT' && trim((string)($header['text'] ?? '')) !== '') {
                    $components[] = [
                        'type' => 'HEADER',
                        'format' => 'TEXT',
                        'text' => (string)$header['text'],
                    ];
                } elseif (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                    $headerComp = [
                        'type' => 'HEADER',
                        'format' => $headerFormat,
                    ];
                    if ($approval_header_handle) {
                        // Conforme doc Meta: usa example.header_handle para aprovação de header mídia
                        $headerComp['example'] = ['header_handle' => [$approval_header_handle]];
                    }
                    $components[] = $headerComp;
                }

                $bodyComp = [
                    'type' => 'BODY',
                    'text' => $bodyText,
                ];
                if (preg_match('/\{\{\d+\}\}/', $bodyText) && !empty($bodyExample)) {
                    $bodyComp['example'] = ['body_text' => [array_values($bodyExample)]];
                }
                $components[] = $bodyComp;

                if ($footerText !== '') {
                    $components[] = ['type' => 'FOOTER', 'text' => $footerText];
                }

                if (!empty($buttons)) {
                    $components[] = ['type' => 'BUTTONS', 'buttons' => $buttons];
                }

                $payload = [
                    'name' => $templateName,
                    'language' => $lang,
                    'category' => $category,
                    'components' => $components,
                ];

                $createUrl = "https://graph.facebook.com/v22.0/{$waba_id}/message_templates";
                $ch = curl_init($createUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $resp = curl_exec($ch);
                $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $decoded = json_decode((string)$resp, true);
                $metaId = $decoded['id'] ?? ($decoded['message_template_id'] ?? null);
                $status = $decoded['status'] ?? (($http >= 200 && $http < 300) ? 'PENDING' : 'ERROR');
                $lastError = ($http >= 200 && $http < 300) ? null : ($decoded['error']['message'] ?? $resp);

                $status_data = [
                    "meta_id" => $metaId ?: '',
                    "name" => $templateName,
                    "language" => $lang,
                    "category" => $category,
                    "components" => $components,
                    "account_ids" => $account_ids,
                    "waba_id" => $waba_id,
                    "status" => $status,
                    "default_header_media" => $default_header_media,
                    "last_error" => $lastError,
                    "draft_id" => (int)$draft_id,
                ];

                $db = \Config\Database::connect();
                $existing_status = $db->query(
                    "SELECT * FROM " . TB_WHATSAPP_TEMPLATE . "
                     WHERE team_id = ? AND type = ? AND name = ?
                       AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.account_ids')) = ?
                       AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.language')) = ?
                     LIMIT 1",
                    [$team_id, WA_TEMPLATE_TYPE_META_STATUS, $templateName, $account_ids, $lang]
                )->getRow();

                if (empty($existing_status)) {
                    db_insert(TB_WHATSAPP_TEMPLATE, [
                        "ids" => ids(),
                        "team_id" => $team_id,
                        "type" => WA_TEMPLATE_TYPE_META_STATUS,
                        "name" => $templateName,
                        "data" => json_encode($status_data),
                        "created" => time(),
                        "changed" => time()
                    ]);
                } else {
                    db_update(TB_WHATSAPP_TEMPLATE, [
                        "data" => json_encode($status_data),
                        "changed" => time()
                    ], ["id" => $existing_status->id]);
                }

                if ($http >= 200 && $http < 300) {
                    $created++;
                } else {
                    $errors++;
                }
            }

            return $this->jsonResponse([
                'status' => $errors > 0 ? 'warning' : 'success',
                'message' => "Submissão concluída. Criados: {$created}. Erros: {$errors}. Agora clique em 'Sincronizar status'.",
                'redirect' => base_url('whatsapp_profiles/meta_templates/' . $account_ids),
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function generate_instance($instance_id = false)
    {
        $team_id = get_team("id");
        $content_data = [
            "config" => $this->config,
            "cloud_api_embedded_signup_enabled" => $this->canUseCloudEmbeddedSignup($team_id),
            "page_title" => "Central de Conexão WhatsApp",
            "page_subtitle" => "Gerencie conexões Baileys e Cloud API em um único lugar.",
            "show_baileys_qr" => true,
            "pending_baileys_session" => null,
            "baileys_connect_url" => base_url("whatsapp_profiles/oauth?connect=baileys")
        ];

        $account = false;
        if (!empty($instance_id)) {
            $account = db_get("*", self::TB_ACCOUNTS, [
                "social_network" => "whatsapp",
                "category" => "profile",
                "login_type" => 2,  // Apenas contas Baileys
                "token" => $instance_id,
                "team_id" => $team_id
            ]);
        }

        // Mantém a Central completa: cards de Cloud e Baileys continuam visíveis
        $accounts = db_fetch("*", self::TB_ACCOUNTS, [
            "social_network" => "whatsapp",
            "category" => "profile",
            "team_id" => $team_id
        ]);
        $content_data['accounts'] = $accounts;

        // Exclui sessões anteriores inativas do mesmo time e gera uma nova instância se necessário
        if (empty($account)) {
            db_delete(self::TB_WHATSAPP_SESSIONS, ["status" => 0, "team_id" => $team_id]);

            // Gera um novo ID de instância e PIN
            $instance_id = strtoupper(uniqid());
            $pin_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Insere a nova sessão no banco de dados com o PIN
            db_insert(self::TB_WHATSAPP_SESSIONS, [
                "ids" => ids(),
                "instance_id" => $instance_id,
                "team_id" => $team_id,
                "data" => json_encode(["pin" => $pin_code]), // Armazena o PIN na coluna 'data'
                "status" => 0
            ]);

            $content_data['instance_id'] = $instance_id;
            $content_data['pin_code'] = $pin_code;
            $content_data['has_pair'] = false;
            $content_data['pending_baileys_session'] = (object) [
                'instance_id' => $instance_id,
            ];

        } else {
            // Atualiza o status da instância existente e recupera o PIN
            $instance_id = $account->token;
            db_update(self::TB_WHATSAPP_SESSIONS, ["status" => 0], ["instance_id" => $instance_id]);

            $session_data = [];
            if (!empty($account->data)) {
                $decoded_account_data = json_decode($account->data, true);
                $session_data = is_array($decoded_account_data) ? $decoded_account_data : [];
            }

            if (empty($session_data['pin'])) {
                $session = db_get("data", self::TB_WHATSAPP_SESSIONS, ["instance_id" => $instance_id, "team_id" => $team_id]);
                if (!empty($session->data)) {
                    $decoded_session_data = json_decode($session->data, true);
                    if (is_array($decoded_session_data)) {
                        $session_data = array_merge($session_data, $decoded_session_data);
                    }
                }
            }

            $content_data['instance_id'] = $instance_id;
            $content_data['pin_code'] = $session_data['pin'] ?? null;
            $content_data['has_pair'] = false;
            $content_data['pending_baileys_session'] = (object) [
                'instance_id' => $instance_id,
            ];
        }

        // Prepara os dados para renderização na view
        $data = [
            "title" => "Central de Conexão WhatsApp",
            "desc" => "Gerencie conexões Baileys, Cloud API e perfis em um único painel.",
            "config" => $this->config,
            "content" => view('Core\Whatsapp_profiles\Views\oauth', $content_data)
        ];

        return view('Core\Whatsapp_profiles\Views\index', $data);
    }

    public function generate_whatsmeow_instance()
    {
        $team_id = get_team("id");
        $instance_id = 'WMEOW_' . strtoupper(uniqid());

        // Registra gateway whatsmeow no banco
        try {
            \App\Services\WhatsAppGatewayService::register(
                $instance_id,
                'http://127.0.0.1:8090',
                '',
                $team_id
            );
        } catch (\Throwable $e) {
            // Falha silenciosa, continua
        }

        // Cria sessão pendente (status=0) igual ao Baileys
        db_delete(self::TB_WHATSAPP_SESSIONS, ["status" => 0, "team_id" => $team_id]);
        db_insert(self::TB_WHATSAPP_SESSIONS, [
            "ids" => ids(),
            "instance_id" => $instance_id,
            "team_id" => $team_id,
            "data" => NULL,
            "status" => 0
        ]);

        $accounts = db_fetch("*", self::TB_ACCOUNTS, [
            "social_network" => "whatsapp",
            "category" => "profile",
            "team_id" => $team_id
        ]);

        $content_data = [
            "config" => $this->config,
            "cloud_api_embedded_signup_enabled" => $this->canUseCloudEmbeddedSignup($team_id),
            "page_title" => "Central de Conexão WhatsApp",
            "page_subtitle" => "Gerencie conexões Baileys, Cloud API e Whatsmeow em um único lugar.",
            "show_baileys_qr" => false,
            "show_whatsmeow_qr" => true,
            "pending_baileys_session" => null,
            "baileys_connect_url" => base_url("whatsapp_profiles/generate_instance"),
            "accounts" => $accounts,
            "instance_id" => $instance_id,
            "whatsmeow_instance_id" => $instance_id,
        ];

        $data = [
            "title" => "Central de Conexão WhatsApp",
            "desc" => "Gerencie conexões Baileys, Cloud API e Whatsmeow.",
            "config" => $this->config,
            "content" => view('Core\Whatsapp_profiles\Views\oauth', $content_data)
        ];

        return view('Core\Whatsapp_profiles\Views\index', $data);
    }

    public function get_qrcode($instance_id = false)
    {
        $team_id = get_team("id");

        // Detecta se é instância Whatsmeow
        $gateway = \App\Services\WhatsAppGatewayService::gatewayForInstance($instance_id);
        if (($gateway['provider'] ?? 'baileys') === 'whatsmeow') {
            $this->get_whatsmeow_qrcode($instance_id);
            return;
        }

        $access_token = get_team("ids");

        $account = db_get("*", self::TB_ACCOUNTS, ["social_network" => "whatsapp", "category" => "profile", "token" => $instance_id, "team_id" => $team_id]);
        if ($account) {
            $session = db_get("*", self::TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "status" => 0]);
            if ($session) {
                if ($session->instance_id != $instance_id) {
                    db_update(self::TB_WHATSAPP_SESSIONS, [
                        "instance_id" => $instance_id,
                        "status" => 0
                    ], ['id' => $session->id]);
                }
            } else {
                db_insert(self::TB_WHATSAPP_SESSIONS, [
                    "ids" => ids(),
                    "instance_id" => $instance_id,
                    "team_id" => $team_id,
                    "data" => NULL,
                    "status" => 0
                ]);
            }
        } else {
            if (!check_number_account("whatsapp", "profile", false, false)) {
                return false;
            }
        }

        $result = wa_get_curl("get_qrcode", ["instance_id" => $instance_id, "access_token" => $access_token]);
        if ($result == "") {
            echo json_encode([
                "status" => "error",
                "message" => __("Cannot connect to WhatsApp server. Please make sure the WhatsApp server running.") . "</br>" . __("You can follow by documentation at <a href='#' target='_blank'>here</a>")
            ]);
            exit;
        }

        if ($result->status == "error") {
            echo json_encode([
                "status" => "error",
                "message" => __($result->message)
            ]);
            exit;
        } else {
            echo json_encode($result);
            exit;
        }
    }

    public function get_whatsmeow_qrcode($instance_id)
    {
        $gateway = \App\Services\WhatsAppGatewayService::gatewayForInstance($instance_id);
        $baseUrl = rtrim($gateway['base_url'] ?? 'http://127.0.0.1:8090', '/');

        $ch = curl_init($baseUrl . '/qrcode?instance_id=' . urlencode($instance_id));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            echo json_encode([
                "status" => "error",
                "message" => "Gateway Whatsmeow offline: $error"
            ]);
            exit;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['qrcode'])) {
            echo json_encode([
                "status" => "error",
                "message" => "Resposta inválida do gateway"
            ]);
            exit;
        }

        $qrRaw = $decoded['qrcode'];
        $qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' . urlencode($qrRaw);

        echo json_encode([
            "status" => "success",
            "base64" => $qrImgUrl,
            "instance_id" => $instance_id,
        ]);
        exit;
    }

    public function check_whatsmeow_login($instance_id)
    {
        $gateway = \App\Services\WhatsAppGatewayService::gatewayForInstance($instance_id);
        if (($gateway['provider'] ?? 'baileys') !== 'whatsmeow') {
            echo json_encode(["status" => "error", "message" => "Not a whatsmeow instance"]);
            exit;
        }

        $baseUrl = rtrim($gateway['base_url'] ?? 'http://127.0.0.1:8090', '/');

        $ch = curl_init($baseUrl . '/status?instance_id=' . urlencode($instance_id));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            echo json_encode(["status" => "error", "message" => "Gateway offline"]);
            exit;
        }

        $status = json_decode($response);
        if ($status && !empty($status->state) && $status->state === 'connected') {
            $team_id = get_team("id");

            // Verifica se conta já existe
            $account = db_get("*", self::TB_ACCOUNTS, [
                "token" => $instance_id,
                "team_id" => $team_id
            ]);

            if (!$account) {
                // Cria conta automaticamente
                db_insert(self::TB_ACCOUNTS, [
                    "ids" => ids(),
                    "module" => "whatsapp_profiles",
                    "social_network" => "whatsapp",
                    "category" => "profile",
                    "login_type" => 3,
                    "team_id" => $team_id,
                    "pid" => $status->jid ?? $instance_id,
                    "name" => "Whatsmeow - " . substr($instance_id, 0, 12),
                    "token" => $instance_id,
                    "status" => 1,
                    "created" => time(),
                    "changed" => time(),
                ]);
            } else {
                db_update(self::TB_ACCOUNTS, ["status" => 1, "changed" => time()], ["token" => $instance_id]);
            }

            // Marca sessão como concluída
            $session = db_get("*", self::TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);
            if (!$session) {
                db_insert(self::TB_WHATSAPP_SESSIONS, [
                    "ids" => ids(),
                    "instance_id" => $instance_id,
                    "team_id" => $team_id,
                    "data" => json_encode(["gateway" => "whatsmeow", "jid" => $status->jid ?? ""]),
                    "status" => 1,
                ]);
            } else {
                db_update(self::TB_WHATSAPP_SESSIONS, ["status" => 1], ["instance_id" => $instance_id]);
            }

            echo json_encode(["status" => "success", "message" => "Whatsmeow connected"]);
            exit;
        }

        echo json_encode(["status" => "error", "message" => "Aguardando scan do QR"]);
        exit;
    }

    public function check_login($instance_id = "")
    {
        // Roteia instâncias Whatsmeow para o gateway Go
        if (strpos($instance_id, 'WMEOW_') === 0) {
            $this->check_whatsmeow_login($instance_id);
            return;
        }

        $team_id = get_team("id");
        $whatsapp_session = db_get("*", self::TB_WHATSAPP_SESSIONS, ["status" => 1, "team_id" => $team_id, "instance_id" => $instance_id]);

        if ($whatsapp_session) {

            $profile = false;
            if ($whatsapp_session->data != "") {
                $profile = json_decode($whatsapp_session->data);
            }

            $account = db_get("*", self::TB_ACCOUNTS, ["token" => $instance_id, "team_id" => $team_id]);

            if (!$account) {
                $account = db_get("*", self::TB_ACCOUNTS, ["pid" => $profile->id, "team_id" => $team_id]);
            }

            if ($account) {
                $avatar = save_img($account->avatar, WRITEPATH . 'avatar/');
                db_update(self::TB_ACCOUNTS, ["avatar" => $avatar], ['id' => $account->id]);

                echo json_encode([
                    "status" => "success",
                    "message" => __("Success")
                ]);
                exit;
            }
        }

        echo json_encode([
            "status" => "error",
            "message" => __("Unsuccess")
        ]);
        exit;
    }

    public function delete()
    {
        try {
            // Limpa qualquer output anterior
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');

            $team_id = get_team('id');
            $access_token = get_team('ids');
            $ids = post('ids');

            if (empty($ids)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Nenhum perfil selecionado"
                ]);
                exit;
            }

            $account = db_get("*", self::TB_ACCOUNTS, [
                "ids" => $ids,
                "team_id" => $team_id,
                "social_network" => "whatsapp",
                "category" => "profile"
            ]);

            if (empty($account)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Perfil não encontrado"
                ]);
                exit;
            }

            // Realiza logout na API
            try {
                $logoutResponse = wa_get_curl("logout", [
                    "instance_id" => $account->token,
                    "access_token" => $access_token
                ]);
            } catch (\Exception $e) {
                // Ignora erro no logout da API
            }

            // Finaliza a sessão
            try {
                db_update(self::TB_WHATSAPP_SESSIONS, [
                    "status" => 2,
                    "data" => json_encode([
                        "disconnected_at" => date("Y-m-d H:i:s"),
                        "disconnected_by" => "user_delete"
                    ])
                ], ["instance_id" => $account->token]);
            } catch (\Exception $e) {
                // Ignora erro na atualização da sessão
            }

            // Remove avatar se existir
            if (!empty($account->avatar)) {
                $avatar_path = WRITEPATH . 'avatar/' . $account->avatar;
                if (file_exists($avatar_path)) {
                    @unlink($avatar_path);
                }
            }

            // Exclui registros relacionados
            db_delete(self::TB_ACCOUNTS, ["ids" => $ids]);

            // Tenta excluir registros relacionados, ignorando erros
            try {
                db_delete(self::TB_WHATSAPP_AUTORESPONDER, ["instance_id" => $account->token]);
            } catch (\Exception $e) {
            }
            try {
                db_delete(self::TB_WHATSAPP_CHATBOT, ["instance_id" => $account->token]);
            } catch (\Exception $e) {
            }
            try {
                db_delete(self::TB_WHATSAPP_SESSIONS, ["instance_id" => $account->token]);
            } catch (\Exception $e) {
            }
            try {
                db_delete(self::TB_WHATSAPP_WEBHOOK, ["instance_id" => $account->token]);
            } catch (\Exception $e) {
            }

            // Limpa cache da sessão se existir
            if (function_exists('delete_session_by_token')) {
                try {
                    delete_session_by_token($account->token);
                } catch (\Exception $e) {
                    // Ignora erro na limpeza do cache
                }
            }

            echo json_encode([
                "status" => "success",
                "message" => "Perfil excluído com sucesso"
            ]);
            exit;

        } catch (\Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Erro ao excluir: " . $e->getMessage()
            ]);
            exit;
        }
    }

    public function create_profile()
    {
        // Verifica se é uma requisição AJAX
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Only AJAX requests are allowed'
            ], 400);
        }

        try {
            $team_id = get_team("id");
            if (!$team_id) {
                throw new \Exception('Team ID não encontrado');
            }

            $name = post('name');
            $description = post('description');

            if (empty($name)) {
                throw new \Exception('O nome do perfil é obrigatório');
            }

            if (!check_number_account("whatsapp", "profile")) {
                throw new \Exception('Você atingiu o limite de perfis permitidos');
            }

            // Gera um novo instance_id
            $instance_id = strtoupper(uniqid());

            // Remove qualquer sessão antiga inativa
            db_delete(self::TB_WHATSAPP_SESSIONS, ["status" => 0, "team_id" => $team_id]);

            // Cria o perfil
            $profile_data = [
                "ids" => ids(),
                "team_id" => $team_id,
                "social_network" => "whatsapp",
                "category" => "profile",
                "name" => $name,
                "description" => $description,
                "status" => 0,
                "token" => $instance_id,
                "changed" => date('Y-m-d H:i:s'),
                "created" => date('Y-m-d H:i:s')
            ];

            $profile_insert = db_insert(self::TB_ACCOUNTS, $profile_data);
            if (!$profile_insert) {
                throw new \Exception('Erro ao criar perfil no banco de dados');
            }

            // Cria a sessão do WhatsApp
            $session_data = [
                "ids" => ids(),
                "instance_id" => $instance_id,
                "team_id" => $team_id,
                "data" => NULL,
                "status" => 0
            ];

            $session_insert = db_insert(self::TB_WHATSAPP_SESSIONS, $session_data);
            if (!$session_insert) {
                // Se falhar ao criar a sessão, remove o perfil
                db_delete(self::TB_ACCOUNTS, ["token" => $instance_id]);
                throw new \Exception('Erro ao criar sessão do WhatsApp');
            }

            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Perfil criado com sucesso',
                'instance_id' => $instance_id
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function test_endpoint()
    {
        // Verifica se é uma requisição AJAX
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Only AJAX requests are allowed'
            ], 400);
        }

        return $this->jsonResponse([
            'status' => 'success',
            'message' => 'Test endpoint working',
            'post_data' => $this->request->getPost(),
            'get_data' => $this->request->getGet(),
            'time' => date('Y-m-d H:i:s'),
            'is_ajax' => $this->request->isAJAX()
        ]);
    }

    public function disconnect()
    {
        try {
            // Limpa qualquer output anterior
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');

            $team_id = get_team('id');
            $access_token = get_team('ids');
            $ids = post('ids');

            if (empty($ids)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Nenhum perfil selecionado"
                ]);
                exit;
            }

            $account = db_get("*", self::TB_ACCOUNTS, [
                "ids" => $ids,
                "team_id" => $team_id,
                "social_network" => "whatsapp",
                "category" => "profile"
            ]);

            if (empty($account)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Perfil não encontrado"
                ]);
                exit;
            }

            // Realiza logout na API
            try {
                $logoutResponse = wa_get_curl("logout", [
                    "instance_id" => $account->token,
                    "access_token" => $access_token
                ]);
            } catch (\Exception $e) {
                // Ignora erro no logout da API
            }

            // Atualiza status da sessão
            try {
                db_update(self::TB_WHATSAPP_SESSIONS, [
                    "status" => 2, // 2 = Desconectado
                    "data" => json_encode([
                        "disconnected_at" => date("Y-m-d H:i:s"),
                        "disconnected_by" => "user"
                    ])
                ], ["instance_id" => $account->token]);
            } catch (\Exception $e) {
                // Ignora erro na atualização da sessão
            }

            // Atualiza status da conta
            db_update(self::TB_ACCOUNTS, [
                "status" => 0
            ], ["ids" => $ids]);

            // Limpa cache da sessão se existir
            if (function_exists('delete_session_by_token')) {
                try {
                    delete_session_by_token($account->token);
                } catch (\Exception $e) {
                    // Ignora erro na limpeza do cache
                }
            }

            echo json_encode([
                "status" => "success",
                "message" => "Perfil desconectado com sucesso"
            ]);
            exit;

        } catch (\Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Erro ao desconectar: " . $e->getMessage()
            ]);
            exit;
        }
    }

    public function update_name()
    {
        try {
            // Limpa output buffer
            while (ob_get_level())
                ob_end_clean();

            // Headers básicos
            header('Content-Type: application/json');

            // Pega os dados necessários
            $ids = post('ids');
            $name = post('name');
            $team_id = get_team("id");

            // Log para debug
            error_log("[UPDATE] Iniciando atualização forçada - ID: $ids, Nome: $name");

            // Validações básicas
            if (empty($ids) || empty($name) || empty($team_id)) {
                ms([
                    "status" => "error",
                    "message" => __("Dados inválidos para atualização")
                ]);
            }

            // Conecta ao banco
            $db = \Config\Database::connect();

            // Busca a conta para verificar se existe
            $account = db_get("*", TB_ACCOUNTS, [
                "ids" => $ids,
                "team_id" => $team_id,
                "social_network" => "whatsapp",
                "category" => "profile"
            ]);

            if (!$account) {
                error_log("[UPDATE] Perfil não encontrado");
                ms([
                    "status" => "error",
                    "message" => __("Perfil não encontrado")
                ]);
            }

            // Força a atualização do nome usando query direta
            $sql = "UPDATE " . TB_ACCOUNTS . " SET name = ?, changed = ? WHERE ids = ? AND team_id = ?";
            $db->query($sql, [$name, date("Y-m-d H:i:s"), $ids, $team_id]);

            // Força atualização da sessão do WhatsApp
            try {
                $current_session = db_get("*", TB_WHATSAPP_SESSIONS, [
                    "instance_id" => $account->token,
                    "team_id" => $team_id
                ]);

                if ($current_session) {
                    $session_data = $current_session->data ? json_decode($current_session->data, true) : [];

                    // Atualiza todos os campos de nome possíveis
                    $session_data['name'] = $name;
                    $session_data['pushname'] = $name;
                    $session_data['displayName'] = $name;

                    // Força atualização da sessão usando query direta
                    $sql = "UPDATE " . TB_WHATSAPP_SESSIONS . " SET data = ? WHERE instance_id = ? AND team_id = ?";
                    $db->query($sql, [json_encode($session_data), $account->token, $team_id]);
                }
            } catch (\Exception $e) {
                error_log("[UPDATE] Erro ao atualizar sessão (não crítico): " . $e->getMessage());
            }

            // Limpa apenas os caches específicos
            try {
                if (function_exists('delete_session_by_token')) {
                    delete_session_by_token($account->token);
                }

                // Remove apenas os dados específicos da sessão
                $session = session();
                $session->remove('ACCOUNTS_DATA_' . $team_id);
                $session->remove('WHATSAPP_DATA_' . $team_id);
                $session->remove('INSTANCE_DATA_' . $account->token);

                // Limpa apenas o cache relacionado ao WhatsApp
                if (function_exists('cache')) {
                    cache()->deleteMatching('whatsapp_*');
                    cache()->deleteMatching('account_*' . $team_id);
                }

                if (function_exists('opcache_reset')) {
                    opcache_reset();
                }
            } catch (\Exception $e) {
                error_log("[UPDATE] Erro ao limpar cache (não crítico): " . $e->getMessage());
            }

            // Verifica se a atualização foi bem sucedida usando query direta
            $sql = "SELECT name FROM " . TB_ACCOUNTS . " WHERE ids = ? AND team_id = ?";
            $query = $db->query($sql, [$ids, $team_id]);
            $result = $query->getRow();

            if (!$result || $result->name !== $name) {
                error_log("[UPDATE] Verificação falhou - Nome não foi atualizado");
                throw new \Exception("Falha na verificação da atualização");
            }

            error_log("[UPDATE] Nome atualizado com sucesso para: " . $name);

            ms([
                "status" => "success",
                "message" => __("Nome atualizado com sucesso"),
                "reload" => true,
                "debug_info" => [
                    "old_name" => $account->name,
                    "new_name" => $name,
                    "verification" => $result->name,
                    "team_id" => $team_id,
                    "profile_id" => $ids,
                    "is_connected" => (bool) $account->status,
                    "final_status" => $account->status,
                    "force_update" => true
                ]
            ]);

        } catch (\Exception $e) {
            error_log("[UPDATE] Erro: " . $e->getMessage());
            ms([
                "status" => "error",
                "message" => __("Erro ao atualizar: ") . $e->getMessage()
            ]);
        }
    }

    protected function validate_meta_credentials($phone_number_id, $access_token)
    {
        $url = "https://graph.facebook.com/v22.0/{$phone_number_id}";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        $this->cloud_log("validate_meta_credentials: phone_number_id={$phone_number_id}, HTTP={$http_code}");
        if ($curl_error) {
            $this->cloud_log("validate_meta_credentials CURL ERROR: {$curl_error}");
        }

        if ($http_code != 200) {
            $error = $result['error']['message'] ?? 'Erro desconhecido na Meta API';
            $this->cloud_log("validate_meta_credentials FALHA: {$error} (HTTP {$http_code})");
            throw new \Exception("Falha na validação com a Meta: {$error} (HTTP {$http_code})");
        }

        return true;
    }


    protected function cloud_log($msg)
    {
        $logFile = WRITEPATH . 'logs/debug_cloud.txt';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
    }

    public function save_official()
    {
        $this->cloud_log("STARTED");

        if (!$this->request->isAJAX()) {
            $this->cloud_log("REJEITADO: Não é AJAX");
            ms([
                'status' => 'error',
                'message' => 'Only AJAX requests are allowed'
            ]);
        }

        try {
            $team_id = get_team("id");
            $name = post('name');
            $waba_id = post('waba_id');
            $phone_number_id = post('phone_number_id');
            $token = post('token');
            $verify_token = post('verify_token');

            $this->cloud_log("Received: Name=$name, WABA=$waba_id, PhoneID=$phone_number_id, Team=$team_id");

            if (empty($name) || empty($waba_id) || empty($phone_number_id) || empty($token)) {
                $this->cloud_log("FALHA: Campos obrigatórios faltando - name=" . (!empty($name)?'OK':'VAZIO') . ", waba=" . (!empty($waba_id)?'OK':'VAZIO') . ", phone=" . (!empty($phone_number_id)?'OK':'VAZIO') . ", token=" . (!empty($token)?'OK':'VAZIO'));
                throw new \Exception('Todos os campos são obrigatórios.');
            }

            // Verificar se Cloud API está habilitado no plano
            $cloud_api_enabled = (int) permission("cloud_api_enabled", $team_id);
            if ($cloud_api_enabled != 1) {
                throw new \Exception('Cloud API não está habilitado no seu plano.');
            }

            // Verificar limite de conexões Cloud API
            $cloud_api_limit = (int) permission("cloud_api_accounts", $team_id);
            if ($cloud_api_limit != -1 && $cloud_api_limit > 0) {
                $cloud_api_count = db_get("count(*) as count", TB_ACCOUNTS, ["social_network" => "whatsapp", "login_type" => 1, "team_id" => $team_id])->count;
                if ($cloud_api_count >= $cloud_api_limit) {
                    throw new \Exception(sprintf('Você atingiu o limite de %d conexões Cloud API.', $cloud_api_limit));
                }
            }

            // O instance_id para Cloud API deve ser único
            $instance_id = strtoupper(uniqid('CLD'));
            $this->cloud_log("Generated Instance ID: $instance_id");

            $data_json = json_encode([
                "waba_id" => $waba_id,
                "phone_number_id" => $phone_number_id,
                "verify_token" => $verify_token,
                "token" => $token
            ]);

            // Validação obrigatória antes de salvar
            $this->validate_meta_credentials($phone_number_id, $token);

            // Tentar obter a foto de perfil oficial da Cloud API
            $avatar_url = null;
            try {
                $pic_url = "https://graph.facebook.com/v22.0/{$phone_number_id}/whatsapp_business_profile?fields=profile_picture_url&access_token={$token}";
                $ch_pic = curl_init($pic_url);
                curl_setopt($ch_pic, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_pic, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch_pic, CURLOPT_TIMEOUT, 10);
                $pic_res = curl_exec($ch_pic);
                curl_close($ch_pic);
                $pic_data = json_decode($pic_res, true);
                if (!empty($pic_data['data'][0]['profile_picture_url'])) {
                    $avatar_url = $pic_data['data'][0]['profile_picture_url'];
                }
            } catch (\Exception $e) {}

            if (empty($avatar_url)) {
                // Fallback para avatar gerado
                $avatar_colors = ["E74645", "FB7756", "FACD60", "12492F", "F7A400", "58B368"];
                $avatar_color = $avatar_colors[array_rand($avatar_colors)];
                $avatar_name = preg_replace('/[&="\'~\s]/', '', $name);
                $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($avatar_name) . "&background=" . $avatar_color . "&color=fff&font-size=0.5&rounded=false&format=png";
            }

            $profile_data = [
                "ids" => ids(),
                "module" => "whatsapp_profiles",
                "team_id" => $team_id,
                "social_network" => "whatsapp",
                "category" => "profile",
                "login_type" => 1, // 1 = Cloud API Official
                "name" => $name,
                "token" => $instance_id,
                "pid" => $phone_number_id,
                "data" => $data_json,
                "avatar" => $avatar_url,
                "status" => 1,
                "changed" => time(),
                "created" => time()
            ];

            $this->cloud_log("Attempting insert into sp_accounts...");
            $insert = db_insert(self::TB_ACCOUNTS, $profile_data);
            $this->cloud_log("Insert result: " . json_encode($insert));

            if (!$insert) {
                $this->cloud_log("Insert returned false/0");
                throw new \Exception('Erro ao salvar perfil no banco de dados. Verifique o log de erros.');
            }

            $this->cloud_log("Attempting insert into sp_whatsapp_sessions...");
            db_insert(self::TB_WHATSAPP_SESSIONS, [
                "ids" => ids(),
                "instance_id" => $instance_id,
                "team_id" => $team_id,
                "data" => $data_json,
                "status" => 1
            ]);
            $this->cloud_log("Session inserted.");

            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Perfil Cloud API configurado com sucesso!',
                'redirect' => base_url('whatsapp_profiles/oauth')
            ]);

        } catch (\Exception $e) {
            $this->cloud_log("ERRO EXCEPTION: " . $e->getMessage());
            return $this->jsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function update_official()
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Only AJAX requests are allowed'], 400);
        }

        try {
            $team_id = get_team("id");
            $ids = post('ids');
            $name = post('name');
            $waba_id = post('waba_id');
            $phone_number_id = post('phone_number_id');
            $token = post('token');
            $verify_token = post('verify_token');

            if (empty($ids) || empty($name) || empty($waba_id) || empty($phone_number_id) || empty($token)) {
                throw new \Exception('Todos os campos são obrigatórios.');
            }

            $account = db_get("*", self::TB_ACCOUNTS, ["ids" => $ids, "team_id" => $team_id, "login_type" => 1]);
            if (empty($account)) {
                throw new \Exception('Conta não encontrada ou você não tem permissão.');
            }

            $data_json = json_encode([
                "waba_id" => $waba_id,
                "phone_number_id" => $phone_number_id,
                "verify_token" => $verify_token,
                "token" => $token
            ]);

            // Validação obrigatória antes de atualizar
            $this->validate_meta_credentials($phone_number_id, $token);

            db_update(self::TB_ACCOUNTS, [
                "name" => $name,
                "pid" => $phone_number_id,
                "data" => $data_json,
                "changed" => time()
            ], ["ids" => $ids]);

            db_update(self::TB_WHATSAPP_SESSIONS, [
                "data" => $data_json
            ], ["instance_id" => $account->token]);

            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Perfil Cloud API atualizado com sucesso!',
                'redirect' => base_url('whatsapp_profiles/oauth')
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Salva um único perfil Embedded Signup no banco de dados
     */
    protected function saveOneEmbeddedProfile($team_id, $access_token, $waba_id, $phone_number_id, $display_phone = '')
    {
        $graph_version = get_option('meta_graph_version', '') ?: 'v22.0';
        $this->embedded_log("saveOneEmbeddedProfile: WABA={$waba_id}, PhoneID={$phone_number_id}, Display={$display_phone}");

        // Buscar info do phone na API se não temos display
        $verified_name = '';
        if (empty($display_phone)) {
            $phone_url = "https://graph.facebook.com/v22.0/{$phone_number_id}?access_token={$access_token}";
            $ch = curl_init($phone_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $phone_resp = curl_exec($ch);
            curl_close($ch);
            $phone_data = json_decode($phone_resp, true);
            $display_phone = $phone_data['display_phone_number'] ?? '';
            $verified_name = $phone_data['verified_name'] ?? '';
        } else {
            // Buscar verified_name
            $phones_url = "https://graph.facebook.com/v22.0/{$waba_id}/phone_numbers?access_token={$access_token}";
            $ch = curl_init($phones_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $phones_resp = curl_exec($ch);
            curl_close($ch);
            $phones_data = json_decode($phones_resp, true);
            if (!empty($phones_data['data'])) {
                foreach ($phones_data['data'] as $p) {
                    if (($p['id'] ?? '') === $phone_number_id) {
                        $verified_name = $p['verified_name'] ?? '';
                        break;
                    }
                }
            }
        }

        // Verificar se já existe
        $existing = db_get("*", self::TB_ACCOUNTS, [
            "pid" => $phone_number_id,
            "login_type" => 1,
            "social_network" => "whatsapp"
        ]);

        if (!empty($existing) && $existing->team_id != $team_id) {
            throw new \Exception("Número {$display_phone} já conectado a outra equipe.");
        }

        // Registrar na Cloud API
        $register_url = "https://graph.facebook.com/{$graph_version}/{$phone_number_id}/register";
        $ch = curl_init($register_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['messaging_product' => 'whatsapp', 'pin' => '123456']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_exec($ch);
        curl_close($ch);

        // Inscrever webhooks
        $subscribe_url = "https://graph.facebook.com/v22.0/{$waba_id}/subscribed_apps";
        $ch = curl_init($subscribe_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_exec($ch);
        curl_close($ch);

        // Preparar dados
        $instance_id = strtoupper(uniqid('EMB'));
        $verify_token = uniqid('zapmatic_');
        $profile_name = !empty($verified_name) ? $verified_name : (!empty($display_phone) ? "WhatsApp {$display_phone}" : "WhatsApp Cloud API");

        $data_json = json_encode([
            "waba_id" => $waba_id,
            "phone_number_id" => $phone_number_id,
            "verify_token" => $verify_token,
            "token" => $access_token,
            "display_phone" => $display_phone,
            "verified_name" => $verified_name,
            "connected_via" => "embedded_signup"
        ]);

        // Avatar
        $avatar_colors = ["E74645", "FB7756", "FACD60", "12492F", "F7A400", "58B368"];
        $avatar_color = $avatar_colors[array_rand($avatar_colors)];
        $avatar_name = preg_replace('/[&="\'~\s]/', '', $profile_name);
        $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($avatar_name) . "&background=" . $avatar_color . "&color=fff&font-size=0.5&rounded=false&format=png";

        if (!empty($existing)) {
            // Atualizar existente
            db_update(self::TB_ACCOUNTS, [
                "name" => $profile_name,
                "token" => $instance_id,
                "data" => $data_json,
                "avatar" => $avatar_url,
                "status" => 1,
                "changed" => time()
            ], ["id" => $existing->id]);
            $this->embedded_log("saveOneEmbeddedProfile: Atualizado phone {$display_phone} (id={$existing->id})");
        } else {
            // Inserir novo
            $insert = db_insert(self::TB_ACCOUNTS, [
                "ids" => ids(),
                "module" => "whatsapp_profiles",
                "team_id" => $team_id,
                "social_network" => "whatsapp",
                "category" => "profile",
                "login_type" => 1,
                "name" => $profile_name,
                "token" => $instance_id,
                "pid" => $phone_number_id,
                "data" => $data_json,
                "avatar" => $avatar_url,
                "status" => 1,
                "changed" => time(),
                "created" => time()
            ]);

            if (!$insert) {
                throw new \Exception("Erro ao inserir phone {$display_phone} no banco.");
            }

            db_insert(self::TB_WHATSAPP_SESSIONS, [
                "ids" => ids(),
                "instance_id" => $instance_id,
                "team_id" => $team_id,
                "data" => $data_json,
                "status" => 1
            ]);
            $this->embedded_log("saveOneEmbeddedProfile: Inserido phone {$display_phone} com sucesso!");
        }
    }

    /**
     * Embedded Signup - Recebe o code do Facebook SDK e configura tudo automaticamente
     */
    protected function embedded_log($msg)
    {
        $logFile = WRITEPATH . 'logs/embedded_signup.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
        error_log($msg);
    }

    public function save_embedded()
    {
        $this->embedded_log("=== EMBEDDED SIGNUP: save_embedded() CHAMADO ===");
        $this->embedded_log("REQUEST_METHOD=" . $_SERVER['REQUEST_METHOD'] . ", isAJAX=" . ($this->request->isAJAX() ? 'SIM' : 'NAO'));
        $this->embedded_log("POST data=" . json_encode($_POST));
        
        if (!$this->request->isAJAX()) {
            $this->embedded_log("REJEITADO: Não é AJAX");
            return $this->jsonResponse(['status' => 'error', 'message' => 'Only AJAX requests are allowed'], 400);
        }

        try {
            $team_id = get_team("id");
            $code = post('code');

            if (empty($code)) {
                throw new \Exception('Código de autorização não recebido. Tente novamente.');
            }

            // Verificar se a conexão automática com a Meta está habilitada no plano
            if (!$this->canUseCloudEmbeddedSignup($team_id)) {
                throw new \Exception('A conexão automática com a Meta não está habilitada para o seu plano.');
            }

            // Verificar limite de conexões Cloud API
            $cloud_api_limit = (int) permission("cloud_api_accounts", $team_id);
            if ($cloud_api_limit != -1 && $cloud_api_limit > 0) {
                $cloud_api_count = db_get("count(*) as count", TB_ACCOUNTS, ["social_network" => "whatsapp", "login_type" => 1, "team_id" => $team_id])->count;
                if ($cloud_api_count >= $cloud_api_limit) {
                    throw new \Exception(sprintf('Você atingiu o limite de %d conexões Cloud API.', $cloud_api_limit));
                }
            }

            // Step 1: Trocar o code por um access token de negócio
            $app_id = get_option('meta_app_id', '') ?: get_option('facebook_login_app_id', '');
            $app_secret = get_option('meta_app_secret', '') ?: get_option('facebook_login_app_secret', '');
            $graph_version = get_option('meta_graph_version', '') ?: 'v22.0';

            // Fallback do App ID (mesmo usado no SDK JS do frontend)
            if (empty($app_id)) {
                $app_id = '763786439394524';
                $this->embedded_log("Embedded Signup: Usando App ID fallback: {$app_id}");
            }

            if (empty($app_secret)) {
                $this->embedded_log("Embedded Signup ERRO: App Secret está vazio nas configurações globais Meta e no fallback de Login Social.");
                throw new \Exception('ATENÇÃO: O Meta App Secret não está configurado. Vá em Configurações > WhatsApp API > Global Meta Configuration e preencha o App Secret do seu aplicativo na Meta. Sem isso, a conexão automática não pode ser concluída.');
            }

            $this->embedded_log("Embedded Signup Step 1: Trocando code por token. App ID: {$app_id}, Graph: {$graph_version}, Code: " . substr($code, 0, 20) . "...");

            $token_url = "https://graph.facebook.com/{$graph_version}/oauth/access_token";
            $token_params = http_build_query([
                'client_id' => $app_id,
                'client_secret' => $app_secret,
                'code' => $code
            ]);

            $ch = curl_init($token_url . '?' . $token_params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $token_response = curl_exec($ch);
            $token_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            $this->embedded_log("Embedded Signup Step 1 Response: HTTP {$token_http_code}, Body: " . substr($token_response, 0, 500));
            if ($curl_error) {
                $this->embedded_log("Embedded Signup Step 1 CURL Error: {$curl_error}");
            }

            $token_data = json_decode($token_response, true);

            if ($token_http_code != 200 || empty($token_data['access_token'])) {
                $error_msg = $token_data['error']['message'] ?? 'Erro desconhecido ao trocar código por token';
                $this->embedded_log("Embedded Signup FALHA Step 1: {$error_msg}");
                throw new \Exception("Erro ao obter token da Meta: {$error_msg}");
            }

            $access_token = $token_data['access_token'];
            $this->embedded_log("Embedded Signup Step 1 OK: Token obtido com sucesso.");

            // Step 2: Obter WABA ID e Phone Number ID
            // PRIORIDADE 1: Dados enviados diretamente pelo frontend (evento FINISH do Embedded Signup)
            $waba_id = trim((string) post('waba_id'));
            $phone_number_id_from_js = trim((string) post('phone_number_id'));
            $candidate_waba_ids = [];
            
            $this->embedded_log("Embedded Signup Step 2: Dados do JS - waba_id={$waba_id}, phone_number_id={$phone_number_id_from_js}");

            // PRIORIDADE 2: Se não veio do JS, tentar via debug_token
            if (empty($waba_id)) {
                $this->embedded_log("Embedded Signup Step 2: waba_id não veio do JS, tentando via debug_token...");
                $debug_url = "https://graph.facebook.com/{$graph_version}/debug_token?input_token={$access_token}&access_token={$app_id}|{$app_secret}";
                $ch = curl_init($debug_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $debug_response = curl_exec($ch);
                curl_close($ch);
                $debug_data = json_decode($debug_response, true);
                $this->embedded_log("Embedded Signup Step 2: debug_token response: " . substr($debug_response, 0, 1000));

                // Extrair WABA ID do debug_token (granular_scopes) - com tratamento seguro
                if (!empty($debug_data['data']['granular_scopes'])) {
                    foreach ($debug_data['data']['granular_scopes'] as $scope) {
                        $perm = $scope['scope'] ?? ($scope['permission'] ?? '');
                        if ($perm === 'whatsapp_business_management' && !empty($scope['target_ids']) && is_array($scope['target_ids'])) {
                            foreach ($scope['target_ids'] as $target_id) {
                                if (!in_array($target_id, $candidate_waba_ids, true)) {
                                    $candidate_waba_ids[] = $target_id;
                                }
                            }
                        }
                    }
                }

                if (empty($waba_id) && !empty($candidate_waba_ids) && !empty($phone_number_id_from_js)) {
                    foreach ($candidate_waba_ids as $candidate_waba_id) {
                        $candidate_phones_url = "https://graph.facebook.com/v22.0/{$candidate_waba_id}/phone_numbers?access_token={$access_token}";
                        $ch = curl_init($candidate_phones_url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                        $candidate_phones_response = curl_exec($ch);
                        curl_close($ch);
                        $candidate_phones_data = json_decode($candidate_phones_response, true);

                        if (!empty($candidate_phones_data['data'])) {
                            foreach ($candidate_phones_data['data'] as $candidate_phone) {
                                if (($candidate_phone['id'] ?? '') === $phone_number_id_from_js) {
                                    $waba_id = $candidate_waba_id;
                                    break 2;
                                }
                            }
                        }
                    }
                }

                // Fallback seguro: se houver apenas uma WABA possível, usar ela
                if (empty($waba_id) && count($candidate_waba_ids) === 1) {
                    $waba_id = $candidate_waba_ids[0];
                }

                // Último fallback: buscar WABAs via business_id quando o escopo trouxer businesses
                if (empty($waba_id) && !empty($debug_data['data']['granular_scopes'])) {
                    foreach ($debug_data['data']['granular_scopes'] as $scope) {
                        $perm = $scope['scope'] ?? ($scope['permission'] ?? '');
                        if ($perm !== 'business_management' || empty($scope['target_ids']) || !is_array($scope['target_ids'])) {
                            continue;
                        }

                        foreach ($scope['target_ids'] as $business_id) {
                            $waba_url = "https://graph.facebook.com/v22.0/{$business_id}/owned_whatsapp_business_accounts?access_token={$access_token}";
                            $ch = curl_init($waba_url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                            $waba_response = curl_exec($ch);
                            curl_close($ch);
                            $waba_data = json_decode($waba_response, true);

                            if (!empty($waba_data['data']) && is_array($waba_data['data'])) {
                                foreach ($waba_data['data'] as $candidate_waba) {
                                    $candidate_id = $candidate_waba['id'] ?? '';
                                    if (!$candidate_id) {
                                        continue;
                                    }

                                    if (!empty($phone_number_id_from_js)) {
                                        $candidate_phones_url = "https://graph.facebook.com/v22.0/{$candidate_id}/phone_numbers?access_token={$access_token}";
                                        $ch_phone = curl_init($candidate_phones_url);
                                        curl_setopt($ch_phone, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch_phone, CURLOPT_SSL_VERIFYPEER, false);
                                        curl_setopt($ch_phone, CURLOPT_TIMEOUT, 15);
                                        $candidate_phones_response = curl_exec($ch_phone);
                                        curl_close($ch_phone);
                                        $candidate_phones_data = json_decode($candidate_phones_response, true);

                                        if (!empty($candidate_phones_data['data'])) {
                                            foreach ($candidate_phones_data['data'] as $candidate_phone) {
                                                if (($candidate_phone['id'] ?? '') === $phone_number_id_from_js) {
                                                    $waba_id = $candidate_id;
                                                    break 4;
                                                }
                                            }
                                        }
                                    } else {
                                        $waba_id = $candidate_id;
                                        break 3;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (empty($waba_id) && count($candidate_waba_ids) > 1) {
                $this->embedded_log("Step 2: Múltiplas WABAs encontradas (" . count($candidate_waba_ids) . "). Buscando phones de cada uma para encontrar o novo número...");
                
                $all_phones_found = [];
                foreach ($candidate_waba_ids as $cand_waba) {
                    $cand_url = "https://graph.facebook.com/{$graph_version}/{$cand_waba}/phone_numbers?access_token={$access_token}";
                    $ch_cand = curl_init($cand_url);
                    curl_setopt($ch_cand, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch_cand, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch_cand, CURLOPT_TIMEOUT, 15);
                    $cand_resp = curl_exec($ch_cand);
                    curl_close($ch_cand);
                    $cand_data = json_decode($cand_resp, true);
                    
                    if (!empty($cand_data['data'])) {
                        foreach ($cand_data['data'] as $cand_phone) {
                            $cand_phone_id = $cand_phone['id'] ?? '';
                            if (empty($cand_phone_id)) continue;
                            
                            // Verificar se este phone_number_id já existe no banco
                            $existing_phone = db_get("id", self::TB_ACCOUNTS, [
                                "pid" => $cand_phone_id,
                                "login_type" => 1,
                                "social_network" => "whatsapp"
                            ]);
                            
                            $all_phones_found[] = [
                                'waba_id' => $cand_waba,
                                'phone_id' => $cand_phone_id,
                                'display' => $cand_phone['display_phone_number'] ?? '',
                                'exists' => !empty($existing_phone)
                            ];
                        }
                    }
                }
                
                $this->embedded_log("Step 2: Todos phones encontrados: " . json_encode($all_phones_found));
                
                // Filtrar os phones que NÃO existem no banco (são novos)
                $new_phones = array_filter($all_phones_found, function($p) { return !$p['exists']; });
                
                if (count($new_phones) === 1) {
                    $new_phone = array_values($new_phones)[0];
                    $waba_id = $new_phone['waba_id'];
                    $phone_number_id_from_js = $new_phone['phone_id'];
                    $this->embedded_log("Step 2: Novo phone identificado automaticamente! WABA={$waba_id}, PhoneID={$phone_number_id_from_js}, Display={$new_phone['display']}");
                } elseif (count($new_phones) > 1) {
                    // Múltiplos novos - salvar TODOS eles
                    $this->embedded_log("Step 2: Múltiplos phones novos encontrados (" . count($new_phones) . "). Salvando TODOS...");
                    
                    $saved_phones = [];
                    foreach (array_values($new_phones) as $np) {
                        try {
                            $this->saveOneEmbeddedProfile($team_id, $access_token, $np['waba_id'], $np['phone_id'], $np['display']);
                            $saved_phones[] = $np['display'];
                        } catch (\Exception $e) {
                            $this->embedded_log("Step 2: Erro ao salvar phone {$np['phone_id']}: " . $e->getMessage());
                        }
                    }
                    
                    if (empty($saved_phones)) {
                        throw new \Exception('Erro ao salvar os números encontrados. Tente novamente.');
                    }
                    
                    $phones_list = implode(', ', $saved_phones);
                    return $this->jsonResponse([
                        'status' => 'success',
                        'message' => "WhatsApp conectado com sucesso! Números: {$phones_list}",
                        'redirect' => base_url('whatsapp_profiles/oauth')
                    ]);
                } else {
                    // Todos já existem - pode ser reconexão, usar o primeiro
                    $first = $all_phones_found[0] ?? null;
                    if ($first) {
                        $waba_id = $first['waba_id'];
                        $phone_number_id_from_js = $first['phone_id'];
                        $this->embedded_log("Step 2: Todos phones já existem no banco. Atualizando o primeiro: WABA={$waba_id}, PhoneID={$phone_number_id_from_js}");
                    } else {
                        $this->embedded_log("Step 2 FALHA: Nenhum phone encontrado em nenhuma WABA. WABAs=" . json_encode($candidate_waba_ids));
                        throw new \Exception('Nenhum número de telefone encontrado nas WABAs associadas. Tente novamente.');
                    }
                }
            }

            if (empty($waba_id)) {
                $this->embedded_log("Embedded Signup FALHA Step 2: WABA ID não encontrado por nenhum método.");
                throw new \Exception('Não foi possível encontrar a WABA associada. Verifique se você selecionou uma conta WhatsApp Business durante o cadastro.');
            }
            $this->embedded_log("Embedded Signup Step 2 OK: WABA ID encontrado: {$waba_id}");

            // Step 3: Buscar os números de telefone da WABA
            // Se já temos o phone_number_id do JS, usar ele mas ainda buscar info adicional
            $phones_url = "https://graph.facebook.com/v22.0/{$waba_id}/phone_numbers?access_token={$access_token}";
            $ch = curl_init($phones_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $phones_response = curl_exec($ch);
            curl_close($ch);
            $phones_data = json_decode($phones_response, true);

            $this->embedded_log("Embedded Signup Step 3: phones response: " . substr($phones_response, 0, 500));
            
            $phone_info = null;
            
            // Tentar encontrar o phone específico enviado pelo JS
            if (!empty($phone_number_id_from_js) && !empty($phones_data['data'])) {
                foreach ($phones_data['data'] as $p) {
                    if ($p['id'] === $phone_number_id_from_js) {
                        $phone_info = $p;
                        break;
                    }
                }
            }
            
            // Se não encontrou pelo ID, usar o primeiro
            if (empty($phone_info) && !empty($phones_data['data'][0])) {
                $phone_info = $phones_data['data'][0];
            }
            
            // Se não encontrou nenhum phone na API mas temos o ID do JS, criar info mínima
            if (empty($phone_info) && !empty($phone_number_id_from_js)) {
                $this->embedded_log("Embedded Signup Step 3: Usando phone_number_id do JS como fallback: {$phone_number_id_from_js}");
                $phone_info = ['id' => $phone_number_id_from_js, 'display_phone_number' => '', 'verified_name' => ''];
            }
            
            if (empty($phone_info)) {
                $this->embedded_log("Embedded Signup FALHA Step 3: Nenhum phone encontrado. Resp: " . $phones_response);
                throw new \Exception('Nenhum número de telefone encontrado na WABA. Tente novamente e registre um número durante o cadastro.');
            }

            $phone_number_id = $phone_info['id'];
            $display_phone = $phone_info['display_phone_number'] ?? '';
            $verified_name = $phone_info['verified_name'] ?? '';
            $this->embedded_log("Embedded Signup Step 3 OK: phone_number_id={$phone_number_id}, display={$display_phone}, name={$verified_name}");

            $is_update = false;
            // Verificar se este phone_number_id já está cadastrado
            $existing = db_get("*", self::TB_ACCOUNTS, [
                "pid" => $phone_number_id,
                "login_type" => 1,
                "social_network" => "whatsapp"
            ]);
            
            if (!empty($existing)) {
                if ($existing->team_id != $team_id) {
                    throw new \Exception("Este número ({$display_phone}) já está conectado a outra equipe.");
                }
                // Se já existe e for da mesma equipe, podemos apenas atualizar os dados
                $is_update = true;
                $inserted_id = $existing->id;
                $instance_id = $existing->token; // Mantém o token atual
                $unique_id = $existing->ids;
            }

            // Step 4: Registrar o número para Cloud API (register)
            $register_url = "https://graph.facebook.com/v22.0/{$phone_number_id}/register";
            $ch = curl_init($register_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'messaging_product' => 'whatsapp',
                'pin' => '123456' // PIN padrão para registro
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $register_response = curl_exec($ch);
            $register_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Registro pode falhar se já registrado - isso é OK
            if ($register_code != 200) {
                $register_result = json_decode($register_response, true);
                $reg_error = $register_result['error']['message'] ?? '';
                // Se já está registrado, continuar normalmente
                if (strpos(strtolower($reg_error), 'already') === false && 
                    strpos(strtolower($reg_error), 'registered') === false) {
                    $this->embedded_log("Embedded Signup: Register warning - {$reg_error}");
                }
            }

            // Step 5: Inscrever o app nos webhooks da WABA
            $subscribe_url = "https://graph.facebook.com/v22.0/{$waba_id}/subscribed_apps";
            $ch = curl_init($subscribe_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_exec($ch);
            curl_close($ch);

            // Step 6: Salvar tudo no banco de dados
            $instance_id = strtoupper(uniqid('EMB'));
            $verify_token = uniqid('zapmatic_');
            $profile_name = !empty($verified_name) ? $verified_name : (!empty($display_phone) ? "WhatsApp {$display_phone}" : "WhatsApp Cloud API");

            $data_json = json_encode([
                "waba_id" => $waba_id,
                "phone_number_id" => $phone_number_id,
                "verify_token" => $verify_token,
                "token" => $access_token,
                "display_phone" => $display_phone,
                "verified_name" => $verified_name,
                "connected_via" => "embedded_signup"
            ]);

            // Tentar obter a foto de perfil oficial da Cloud API
            $avatar_url = null;
            try {
                $pic_url = "https://graph.facebook.com/{$graph_version}/{$phone_number_id}/whatsapp_business_profile?fields=profile_picture_url&access_token={$access_token}";
                $ch_pic = curl_init($pic_url);
                curl_setopt($ch_pic, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_pic, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch_pic, CURLOPT_TIMEOUT, 10);
                $pic_res = curl_exec($ch_pic);
                curl_close($ch_pic);
                $pic_data = json_decode($pic_res, true);
                if (!empty($pic_data['data'][0]['profile_picture_url'])) {
                    $avatar_url = $pic_data['data'][0]['profile_picture_url'];
                }
            } catch (\Exception $e) {}

            if (empty($avatar_url)) {
                // Fallback para avatar gerado
                $avatar_colors = ["E74645", "FB7756", "FACD60", "12492F", "F7A400", "58B368"];
                $avatar_color = $avatar_colors[array_rand($avatar_colors)];
                $avatar_name = preg_replace('/[&="\'~\s]/', '', $profile_name);
                $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($avatar_name) . "&background=" . $avatar_color . "&color=fff&font-size=0.5&rounded=false&format=png";
            }

            if ($is_update) {
                $profile_data = [
                    "name" => $profile_name,
                    "token" => $instance_id,
                    "data" => $data_json,
                    "avatar" => $avatar_url,
                    "status" => 1,
                    "changed" => time()
                ];

                $this->embedded_log("Embedded Signup Step 6: Atualizando no banco. Phone: {$display_phone}, WABA: {$waba_id}, PhoneID: {$phone_number_id}");
                $update = db_update(self::TB_ACCOUNTS, $profile_data, ["id" => $inserted_id]);
                if (!$update) {
                    $this->embedded_log("Embedded Signup FALHA Step 6: db_update retornou false.");
                    throw new \Exception('Erro ao atualizar perfil no banco de dados.');
                }
                
                // Update session or create if missing
                $session = db_get("*", self::TB_WHATSAPP_SESSIONS, ["instance_id" => $instance_id]);
                if ($session) {
                    db_update(self::TB_WHATSAPP_SESSIONS, [
                        "data" => $data_json,
                        "status" => 1
                    ], ["id" => $session->id]);
                } else {
                    db_insert(self::TB_WHATSAPP_SESSIONS, [
                        "ids" => ids(),
                        "instance_id" => $instance_id,
                        "team_id" => $team_id,
                        "data" => $data_json,
                        "status" => 1
                    ]);
                }
            } else {
                $profile_data = [
                    "ids" => ids(),
                    "module" => "whatsapp_profiles",
                    "team_id" => $team_id,
                    "social_network" => "whatsapp",
                    "category" => "profile",
                    "login_type" => 1,
                    "name" => $profile_name,
                    "token" => $instance_id,
                    "pid" => $phone_number_id,
                    "data" => $data_json,
                    "avatar" => $avatar_url,
                    "status" => 1,
                    "changed" => time(),
                    "created" => time()
                ];

                $this->embedded_log("Embedded Signup Step 6: Inserindo no banco. Phone: {$display_phone}, WABA: {$waba_id}, PhoneID: {$phone_number_id}");
                $insert = db_insert(self::TB_ACCOUNTS, $profile_data);
                if (!$insert) {
                    $this->embedded_log("Embedded Signup FALHA Step 6: db_insert retornou false.");
                    throw new \Exception('Erro ao salvar perfil no banco de dados.');
                }

                db_insert(self::TB_WHATSAPP_SESSIONS, [
                    "ids" => ids(),
                    "instance_id" => $instance_id,
                    "team_id" => $team_id,
                    "data" => $data_json,
                    "status" => 1
                ]);
            }

            $this->embedded_log("Embedded Signup Step 6 OK: Perfil salvo com sucesso! Phone: {$display_phone}");

            return $this->jsonResponse([
                'status' => 'success',
                'message' => "WhatsApp conectado com sucesso! Número: {$display_phone}",
                'redirect' => base_url('whatsapp_profiles/oauth')
            ]);

        } catch (\Exception $e) {
            $this->embedded_log("ERRO EXCEPTION: " . $e->getMessage() . " | File: " . $e->getFile() . ":" . $e->getLine());
            return $this->jsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function test_official($ids = "")
    {
        try {
            $team_id = get_team("id");
            $account = db_get("*", self::TB_ACCOUNTS, ["ids" => $ids, "team_id" => $team_id, "login_type" => 1]);
            
            if (empty($account)) {
                throw new \Exception('Conta não encontrada.');
            }

            $data = json_decode($account->data, true);
            $phone_number_id = $data['phone_number_id'] ?? null;
            $access_token = $data['token'] ?? null;

            if (empty($phone_number_id) || empty($access_token)) {
                throw new \Exception('Credenciais incompletas.');
            }

            // Usar o endpoint do Phone Number que exige menos permissões (apenas whatsapp_business_messaging)
            $url = "https://graph.facebook.com/v18.0/{$phone_number_id}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($http_code == 200) {
                return $this->jsonResponse(['status' => 'success', 'message' => 'Conexão ativa! Token válido.']);
            } else {
                $error = $result['error']['message'] ?? 'Erro desconhecido na Meta API';
                return $this->jsonResponse(['status' => 'error', 'message' => "Falha na conexão: {$error} (HTTP {$http_code})"]);
            }

        } catch (\Exception $e) {
            return $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Sincroniza templates oficiais da Meta para a tabela sp_whatsapp_template
     * @param string $ids - IDs da conta Cloud API
     */
        /**
     * Sincroniza templates oficiais da Meta para a tabela sp_whatsapp_template
     * @param string $ids - IDs da conta Cloud API
     */
    public function sync_templates($ids = "")
    {
        try {
            $team_id = get_team("id");
            $account = db_get("*", TB_ACCOUNTS, ["ids" => $ids, "team_id" => $team_id, "login_type" => 1]);

            if (empty($account)) {
                throw new \Exception('Conta Cloud API não encontrada.');
            }

            $data = json_decode($account->data, true);
            $waba_id = $data['waba_id'] ?? null;
            $phone_id = $data['phone_number_id'] ?? null;
            $access_token = $data['token'] ?? null;

            if (empty($access_token)) {
                throw new \Exception('Access Token não configurado.');
            }

            // Tentar descobrir o WABA ID real se o fornecido falhar ou estiver vazio
            $ch = curl_init("https://graph.facebook.com/v22.0/debug_token?input_token={$access_token}");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $debug_resp = curl_exec($ch);
            curl_close($ch);
            
            // Tentar buscar templates
            $fetch_templates = function($id, $token) {
                $url = "https://graph.facebook.com/v22.0/{$id}/message_templates?limit=500";
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $resp = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                return ['code' => $code, 'resp' => json_decode($resp, true)];
            };

            $result_data = $fetch_templates($waba_id, $access_token);

            // Se falhar com #200 ou #100, tentar descobrir o WABA ID correto via debug_token ou me/accounts
            if ($result_data['code'] != 200) {
                $error_msg = $result_data['resp']['error']['message'] ?? 'Erro desconhecido';
                $error_code = $result_data['resp']['error']['code'] ?? 0;

                if ($error_code == 200) {
                    throw new \Exception("Erro de Permissão (#200): Seu Token não tem a permissão 'whatsapp_business_management'. Por favor, gere um novo Token no Facebook Developers marcando todas as permissões de WhatsApp.");
                }
                
                throw new \Exception("Meta API Error: {$error_msg}");
            }

            $templates = $result_data['resp']['data'] ?? [];

            if (empty($templates)) {
                return $this->jsonResponse(['status' => 'warning', 'message' => 'Nenhum template encontrado na sua conta Meta.']);
            }

            $synced_approved = 0;
            $synced_status = 0;
            $count_pending = 0;
            $count_rejected = 0;

            foreach ($templates as $template) {
                $template_name = $template['name'] ?? '';
                $language = $template['language'] ?? 'pt_BR';
                $status = $template['status'] ?? 'UNKNOWN';

                if ($status === 'PENDING' || $status === 'PAUSED') $count_pending++;
                if ($status === 'REJECTED') $count_rejected++;

                $template_data = [
                    "meta_id" => $template['id'] ?? '',
                    "name" => $template_name,
                    "language" => $language,
                    "category" => $template['category'] ?? '',
                    // A Meta pode reclassificar automaticamente a categoria após revisão (ex.: UTILITY -> MARKETING)
                    "previous_category" => $template['previous_category'] ?? null,
                    "components" => $template['components'] ?? [],
                    "account_ids" => $ids,
                    "waba_id" => $waba_id,
                    "status" => $status
                ];

                // Evita sobrescrever templates entre WABAs/contas:
                // uniqueness: team_id + type + name + language + account_ids (armazenado no JSON data)
                $db = \Config\Database::connect();

                // 1) Espelho de status (PENDING/REJECTED/APPROVED)
                $existing_status = $db->query(
                    "SELECT * FROM " . TB_WHATSAPP_TEMPLATE . "
                     WHERE team_id = ? AND type = ? AND name = ?
                       AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.account_ids')) = ?
                       AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.language')) = ?
                     LIMIT 1",
                    [$team_id, WA_TEMPLATE_TYPE_META_STATUS, $template_name, $ids, $language]
                )->getRow();

                // Se já existir status salvo pelo sistema (ex.: vindo do módulo de Botões),
                // preserva campos internos de vínculo para não “perder” o relacionamento após sincronizar.
                // Isso evita o badge voltar para "Não submetido".
                $existing_status_json = [];
                if (!empty($existing_status) && !empty($existing_status->data)) {
                    $tmp = json_decode($existing_status->data, true);
                    $existing_status_json = is_array($tmp) ? $tmp : [];
                }
                foreach ([
                    'source_template_type',
                    'source_template_ids',
                    'draft_id',
                    'default_header_media',
                    'last_error',
                    'previous_category',
                ] as $k) {
                    if (!array_key_exists($k, $template_data) && array_key_exists($k, $existing_status_json)) {
                        $template_data[$k] = $existing_status_json[$k];
                    }
                }

                if (empty($existing_status)) {
                    db_insert(TB_WHATSAPP_TEMPLATE, [
                        "ids" => ids(),
                        "team_id" => $team_id,
                        "type" => WA_TEMPLATE_TYPE_META_STATUS,
                        "name" => $template_name,
                        "data" => json_encode($template_data),
                        "created" => time(),
                        "changed" => time()
                    ]);
                } else {
                    db_update(TB_WHATSAPP_TEMPLATE, [
                        "data" => json_encode($template_data),
                        "changed" => time()
                    ], ["id" => $existing_status->id]);
                }
                $synced_status++;

                // 2) Pool de envio (somente aprovados)
                if ($status === 'APPROVED') {
                    // Se tivermos mídia padrão cadastrada na tabela de status, propagamos para o registro aprovado
                    // (isso permite envio consistente do HEADER mídia via media_id/link).
                    if (isset($existing_status) && !empty($existing_status)) {
                        $statusJson = json_decode($existing_status->data ?? '', true);
                        if (is_array($statusJson) && !empty($statusJson['default_header_media']) && empty($template_data['default_header_media'])) {
                            $template_data['default_header_media'] = $statusJson['default_header_media'];
                        }
                    }

                    // IMPORT/CONVERSÃO PARA MODELO DE BOTÃO (TYPE 2) NO ZAPMATIC
                    if (!function_exists("meta_sync_to_button_template")) {
                        $metaHelper = realpath(ROOTPATH . 'inc/core/Whatsapp/Helpers/meta_official_helper.php');
                        if ($metaHelper) require_once $metaHelper;
                    }

                    if (function_exists('meta_sync_to_button_template')) {
                        meta_sync_to_button_template((int)$team_id, (string)$ids, $template_data);
                    }

                    $existing_approved = $db->query(
                        "SELECT * FROM " . TB_WHATSAPP_TEMPLATE . "
                         WHERE team_id = ? AND type = ? AND name = ?
                           AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.account_ids')) = ?
                           AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.language')) = ?
                         LIMIT 1",
                        [$team_id, WA_TEMPLATE_TYPE_META_APPROVED, $template_name, $ids, $language]
                    )->getRow();

                    if (empty($existing_approved)) {
                        db_insert(TB_WHATSAPP_TEMPLATE, [
                            "ids" => ids(),
                            "team_id" => $team_id,
                            "type" => WA_TEMPLATE_TYPE_META_APPROVED,
                            "name" => $template_name,
                            "data" => json_encode($template_data),
                            "created" => time(),
                            "changed" => time()
                        ]);
                    } else {
                        db_update(TB_WHATSAPP_TEMPLATE, [
                            "data" => json_encode($template_data),
                            "changed" => time()
                        ], ["id" => $existing_approved->id]);
                    }
                    $synced_approved++;
                }
            }

            return $this->jsonResponse([
                'status' => 'success',
                'message' => "Sincronização concluída! Aprovados: {$synced_approved}. Status atualizados: {$synced_status}. Pendentes: {$count_pending}. Rejeitados: {$count_rejected}."
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function widget_menu( $params = [] ){
        if ( (int)permission("cloud_api_enabled") != 1 ) return "";
        return view('Core\Whatsapp_profiles\Views\widget\menu', $params);
    }

    public function widget_content( $params = [] ){
        if ( (int)permission("cloud_api_enabled") != 1 ) return "";
        $team_id = get_team("id");
        // Mantém apenas aprovados no widget (type=6)
        $meta_templates = db_fetch("*", TB_WHATSAPP_TEMPLATE, ["type" => WA_TEMPLATE_TYPE_META_APPROVED, "team_id" => $team_id]);
        return view('Core\Whatsapp_profiles\Views\widget\content', ["result" => $params["result"], "meta_templates" => $meta_templates]);
    }

}
