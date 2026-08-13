<?php
namespace Core\Whatsapp_export_participants\Controllers;

class Whatsapp_export_participants extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Whatsapp_export_participants\Models\Whatsapp_export_participantsModel();
    }
    
    public function index( $page = false ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $team_id = (int)get_team("id");
        $accounts = db_fetch("*", TB_ACCOUNTS, \Core\Whatsapp_export_participants\Libraries\AccountScope::withTeam([ "social_network" => "whatsapp", "category" => "profile", "login_type" => [1, 2, 3], "status" => 1], $team_id), "created", "ASC");
        permission_accounts($accounts);

        $data_content = [
            "config" => $this->config,
            "accounts" => $accounts
        ];

        $data['content'] = view('Core\Whatsapp_export_participants\Views\content', $data_content );

        return view('Core\Whatsapp\Views\index', $data);
    }

    private function fetch_groups($account, $access_token)
    {
        $login_type = (int)($account->login_type ?? 2);
        if ($login_type === 3) {
            return $this->fetch_groups_via_go($account->token);
        }
        return wa_get_curl("get_groups", [
            "instance_id" => $account->token,
            "access_token" => $access_token
        ]);
    }

    /**
     * Lê uma flag booleana da requisição com valor padrão.
     */
    private function flag(string $name, bool $default = false): bool
    {
        $value = post($name);
        if ($value === null || $value === '') {
            return $default;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    private function fetch_groups_via_go($instance_id)
    {
        // Implementa cache de 15 minutos (900 segundos) para evitar bloqueio 429 do WhatsApp
        $cache = \Config\Services::cache();
        $cacheKey = 'groups_wmeow_' . md5($instance_id);
        
        if ($cachedData = $cache->get($cacheKey)) {
            return $cachedData;
        }

        $gateway = \App\Services\WhatsAppGatewayService::gatewayForInstance($instance_id);
        $baseUrl = rtrim($gateway['base_url'] ?? \App\Services\WhatsAppGatewayService::getGoBaseUrl(), '/');
        $url = $baseUrl . '/groups/list?instance_id=' . urlencode($instance_id);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result);
        if (empty($data) || !isset($data->groups)) {
            $msg = 'Failed to fetch groups from Go API';
            if ($data && isset($data->message)) {
                $msg .= ': ' . $data->message;
            }
            return (object)['status' => 'error', 'message' => $msg];
        }

        $totalGroups = count($data->groups);
        $totalCommunities = 0;
        $totalAnnouncements = 0;
        $totalWithPhotos = 0;
        
        foreach ($data->groups as $group) {
            if (!empty($group->isCommunity)) $totalCommunities++;
            if (!empty($group->announce)) $totalAnnouncements++;
            if (!empty($group->profilePicUrl)) $totalWithPhotos++;
        }

        // Convert Go API format to Node.js format (compatible with the view)
        $output = (object)[
            'status' => 'success',
            'data' => [],
            'statistics' => (object)[
                'totalGroups' => $totalGroups,
                'totalCommunities' => $totalCommunities,
                'totalAnnouncements' => $totalAnnouncements,
                'totalWithPhotos' => $totalWithPhotos,
                'totalWithInviteLinks' => 0, // Go API doesn't fetch invite links synchronously
            ]
        ];

        // Se for zero, o PHP na view vai dar erro de divisão por zero. Ajuste preventivo:
        if ($totalGroups === 0) {
            $output->statistics->totalGroups = 1; // Evita division by zero na view
        }

        foreach ($data->groups as $group) {
            $participants = [];
            foreach ($group->participants as $p) {
                $participant = (object)['id' => $p->id . '@s.whatsapp.net'];
                if (!empty($p->admin)) {
                    $participant->admin = true;
                }
                if (!empty($p->name)) {
                    $participant->name = $p->name;
                }
                $participants[] = $participant;
            }
            $output->data[] = (object)[
                'id' => $group->jid,
                'name' => $group->name,
                'participants' => $participants,
                'size' => count($participants),
                'creation' => $group->creation ?? time(),
                'isCommunity' => $group->isCommunity ?? false,
                'announce' => $group->announce ?? false,
                'profilePicUrl' => $group->profilePicUrl ?? '',
                'inviteCode' => null,
                'owner' => ($group->owner ?? '') . '@s.whatsapp.net',
            ];
        }

        // Salva no cache por 15 minutos
        if ($output->status === 'success') {
            $cache->save($cacheKey, $output, 900);
        }

        return $output;
    }

    public function groups() {
        $team_id = get_team("id");
        $access_token = get_team("ids");
        $ids = post("account");
        $account = db_get("*", TB_ACCOUNTS, \Core\Whatsapp_export_participants\Libraries\AccountScope::withTeam(["social_network" => "whatsapp", "login_type" => [1, 2, 3], "ids" => $ids], (int)$team_id));

        if(!empty($account)){
            // Fallback para contas Go onde username fica vazio e o numero real está no pid
            if (empty($account->username) && !empty($account->pid)) {
                $account->username = str_replace('@s.whatsapp.net', '', $account->pid);
            }

            $result = $this->fetch_groups($account, $access_token);
            if($result->status == "error"){
                $friendly_msg = $result->message;
                
                // Mapeamento de mensagens técnicas para amigáveis
                $errLower = strtolower($result->message);
                if (strpos($errLower, 'client not found') !== false || strpos($errLower, 'instance not found') !== false) {
                    $friendly_msg = "Sua conta do WhatsApp está desconectada do servidor. Por favor, reconecte escaneando o QR Code novamente.";
                } elseif (strpos($errLower, 'not connected') !== false) {
                    $friendly_msg = "A conexão com o WhatsApp foi perdida ou o aparelho está offline. Verifique o celular e reconecte se necessário.";
                } elseif (strpos($errLower, 'rate-overlimit') !== false || strpos($errLower, 'status 429') !== false) {
                    $friendly_msg = "O WhatsApp bloqueou temporariamente a leitura de grupos para este número por excesso de acessos. Por favor, aguarde cerca de 1 a 2 horas e tente novamente.";
                } elseif (strpos($errLower, 'timeout') !== false) {
                    $friendly_msg = "O WhatsApp demorou muito para responder com a lista de grupos. Tente novamente.";
                }

                echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> ' . htmlspecialchars($friendly_msg) . '</div>';
                return;
            } else {
                $data = [
                    "status" => "success",
                    "result" => $result,
                    "account" => $account,
                    "access_token" => $access_token,
                ];
            }

        }else{
            echo '<div class="alert alert-danger">WhatsApp account does not exist. Please try again or re-login your WhatsApp account</div>';
            return;
        }

        return view('Core\Whatsapp_export_participants\Views\groups', $data);
    }
    
    
    
    public function export_group($account_id = false, $group_id = false){
        $team_id = get_team("id");
        $access_token = get_team("ids");
        $account = db_get("*", TB_ACCOUNTS, \Core\Whatsapp_export_participants\Libraries\AccountScope::withTeam(["social_network" => "whatsapp", "login_type" => [1, 2, 3], "ids" => $account_id], (int)$team_id));
    
        if(!empty($account)){
            $result = $this->fetch_groups($account, $access_token);

            if($result == ""){
                redirect_to( get_module_url() );
            }

            if($result->status == "error"){
                redirect_to( get_module_url() );
            }

            if(!empty( $result->data )){

                foreach ($result->data as $key => $value) {
                    if($value->id == $group_id){
                        // Aplica os filtros (self/admins) antes de extrair
                        $participants = $value->participants;
                        $selfJid = !empty($account->username) ? $account->username . '@s.whatsapp.net' : (!empty($account->pid) ? $account->pid : null);
                        $filtered = \Core\Whatsapp_export_participants\Libraries\ParticipantFilter::apply(
                            $participants,
                            $selfJid,
                            $this->flag("exclude_self", true),
                            $this->flag("exclude_admins", false)
                        );

                        // Usa as bibliotecas isoladas para normalizar (9º dígito) e validar
                        $rows = \Core\Whatsapp_export_participants\Libraries\LeadExtractor::extract($filtered, (string)$value->id);

                        $data = [];
                        foreach ($rows as $row) {
                            $status = ($row['is_valid'] === 2) ? 'invalido' : 'valido';
                            $data[] = [
                                'nome'     => $row['name'] ?? '',
                                'telefone' => $row['phone'],
                                'ddd'      => $row['ddd'],
                                'status'   => $status,
                                'group_id' => $row['group_id'],
                            ];
                        }

                        download_send_headers($value->name." integrantes " . date("d-m-Y") . ".csv");
                        echo array2csv($data);
                    }
                }
            }else{
                redirect_to( get_module_url() );
            }
        }
    }

    /**
     * Enfileira a criação de uma lista de contatos a partir dos participantes
     * de um grupo. O processamento real acontece em background via cron.
     */
    public function create_contact_list($account_id = false, $group_id = false)
    {
        $team_id = (int)get_team("id");
        $access_token = get_team("ids");
        $account = db_get("*", TB_ACCOUNTS, \Core\Whatsapp_export_participants\Libraries\AccountScope::withTeam(["social_network" => "whatsapp", "login_type" => [1, 2, 3], "ids" => $account_id], (int)$team_id));

        if (empty($account)) {
            ms([
                "status" => "error",
                "message" => __("WhatsApp account does not exist")
            ]);
        }

        $result = $this->fetch_groups($account, $access_token);

        if (empty($result) || $result->status == "error") {
            ms([
                "status" => "error",
                "message" => __("Failed to fetch groups")
            ]);
        }

        $participants = null;
        $group_name = "Group";
        if (!empty($result->data)) {
            foreach ($result->data as $value) {
                if ($value->id == $group_id) {
                    $participants = $value->participants;
                    $group_name = $value->name;
                    break;
                }
            }
        }

        if ($participants === null) {
            ms([
                "status" => "error",
                "message" => __("Group not found")
            ]);
        }

        // Filtros opcionais antes de enfileirar
        $selfJid = !empty($account->username) ? $account->username . '@s.whatsapp.net' : (!empty($account->pid) ? $account->pid : null);
        $filtered = \Core\Whatsapp_export_participants\Libraries\ParticipantFilter::apply(
            $participants,
            $selfJid,
            $this->flag("exclude_self", true),
            $this->flag("exclude_admins", false)
        );

        if (empty($filtered)) {
            ms([
                "status" => "error",
                "message" => __("No valid participants found in this group")
            ]);
        }

        $db = \Config\Database::connect();
        $job = \Core\Whatsapp_export_participants\Libraries\ExportQueue::createJob(
            $team_id,
            (string)$account->ids,
            (string)$group_id,
            count($filtered)
        );

        $db->table('sp_export_participants_queue')->insert([
            'ids'          => ids(),
            'team_id'      => $job['team_id'],
            'account_id'   => $job['account_id'],
            'group_id'     => $job['group_id'],
            'group_name'   => $group_name,
            'status'       => $job['status'],
            'total'        => $job['total'],
            'done'         => $job['done'],
            'attempts'     => $job['attempts'],
            'max_attempts' => $job['max_attempts'],
            'error_log'    => null,
            'created'      => time(),
            'changed'      => time(),
        ]);

        ms([
            "status" => "success",
            "message" => sprintf(__("Contact list queued with %s participants. It will be processed in background."), $job['total'])
        ]);
    }

    /**
     * Worker do cron: processa jobs pendentes do time em lotes.
     */
    public function cron()
    {
        $team_id = (int)get_team("id");

        // Sem time (chamada fora de contexto de usuário), processa todos os pendentes.
        if ($team_id <= 0) {
            $team_id = null;
        }

        $db = \Config\Database::connect();
        $builder = $db->table('sp_export_participants_queue');
        $builder->where('status', 'pending');
        $builder->where('attempts < max_attempts');
        if ($team_id !== null) {
            $builder->where('team_id', $team_id);
        }
        $builder->orderBy('id', 'ASC');
        $builder->limit(\Core\Whatsapp_export_participants\Libraries\ExportQueue::BATCH_SIZE);
        $jobs = $builder->get()->getResult();

        foreach ($jobs as $job) {
            $this->processJob($job);
        }

        return "ok";
    }

    /**
     * Busca o access_token do time diretamente no banco — o cron roda fora
     * de sessão de usuário, então get_team() não funciona aqui.
     */
    private function team_access_token(int $teamId): string
    {
        $team = db_get("ids", TB_TEAM, ["id" => $teamId]);
        return $team ? (string)$team->ids : '';
    }

    /**
     * Processa um job da fila.
     */
    private function processJob($job)
    {
        $db = \Config\Database::connect();
        $teamId = (int)$job->team_id;

        $account = db_get("*", TB_ACCOUNTS, \Core\Whatsapp_export_participants\Libraries\AccountScope::withTeam(["ids" => $job->account_id], $teamId));
        if (empty($account)) {
            $db->table('sp_export_participants_queue')->where('id', $job->id)->update([
                'status'    => 'failed',
                'error_log' => 'account not found',
                'attempts'  => (int)$job->attempts + 1,
                'changed'   => time(),
            ]);
            return;
        }

        $result = $this->fetch_groups($account, $this->team_access_token($teamId));
        if (empty($result) || $result->status == "error") {
            $db->table('sp_export_participants_queue')->where('id', $job->id)->update([
                'status'    => 'failed',
                'error_log' => isset($result->message) ? $result->message : 'failed to fetch groups',
                'attempts'  => (int)$job->attempts + 1,
                'changed'   => time(),
            ]);
            return;
        }

        $participants = null;
        if (!empty($result->data)) {
            foreach ($result->data as $value) {
                if ($value->id == $job->group_id) {
                    $participants = $value->participants;
                    break;
                }
            }
        }

        if ($participants === null) {
            $db->table('sp_export_participants_queue')->where('id', $job->id)->update([
                'status'    => 'failed',
                'error_log' => 'group not found',
                'attempts'  => (int)$job->attempts + 1,
                'changed'   => time(),
            ]);
            return;
        }

        $rows = \Core\Whatsapp_export_participants\Libraries\LeadExtractor::extract($participants, (string)$job->group_id);
        if (empty($rows)) {
            $db->table('sp_export_participants_queue')->where('id', $job->id)->update([
                'status'    => 'failed',
                'error_log' => 'no valid participants',
                'attempts'  => (int)$job->attempts + 1,
                'changed'   => time(),
            ]);
            return;
        }

        $contact_name = sprintf("%s - %s", $job->group_name ?: "Group", date("d/m/Y"));
        $saved = \Core\Whatsapp_export_participants\Libraries\LeadExtractor::saveAsContactList($teamId, $contact_name, $rows);

        $total = (int)$job->total > 0 ? (int)$job->total : count($rows);
        $done  = count($rows);

        $db->table('sp_export_participants_queue')->where('id', $job->id)->update([
            'status'    => 'completed',
            'done'      => $done,
            'total'     => $total,
            'error_log' => null,
            'changed'   => time(),
        ]);
    }
}