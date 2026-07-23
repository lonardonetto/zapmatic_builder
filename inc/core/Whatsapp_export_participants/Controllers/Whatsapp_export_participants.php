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

        $team_id = get_team("id");
        $accounts = db_fetch("*", TB_ACCOUNTS, [ "social_network" => "whatsapp", "category" => "profile", "login_type" => [1, 2, 3], "team_id" => $team_id, "status" => 1], "created", "ASC");
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
        $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "login_type" => [1, 2, 3], "ids" => $ids, "team_id" => $team_id]);

        if(!empty($account)){
            // Fallback para contas Go onde username fica vazio e o numero real está no pid
            if (empty($account->username) && !empty($account->pid)) {
                $account->username = str_replace('@s.whatsapp.net', '', $account->pid);
            }

            $result = $this->fetch_groups($account, $access_token);
            if($result->status == "error"){
                echo '<div class="alert alert-danger">Erro: ' . htmlspecialchars($result->message) . '</div>';
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
        $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "login_type" => [1, 2, 3], "ids" => $account_id, "team_id" => $team_id]);
    
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
                        $participants = $value->participants;
                        $data = [];
                        foreach ($participants as $participant) {
                            
                            $data[] = [
                                'phone' => $participant->id,
                                
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
}