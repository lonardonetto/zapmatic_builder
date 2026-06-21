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
        while (ob_get_level()) ob_end_clean();
        
        $reflect = new \ReflectionClass(get_called_class());
        $this->module = strtolower( $reflect->getShortName() );
        $this->config = include realpath( __DIR__."/../Config.php" );
        $this->whatsapp_server_url = get_option('whatsapp_server_url', '');

        if($this->whatsapp_server_url == ""){
            redirect_to( base_url("social_network_settings/index/".$this->config['parent']['id']) ); 
        }
    }
    
    protected function jsonResponse($data, $statusCode = 200) {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON($data)
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
    
    public function index() {
        redirect_to( get_module_url("oauth") );
    }

    public function oauth($instance_id = false){
        $team_id = get_team("id");
        $content_data = [ "config" => $this->config ];

        // Busca a conta específica se um instance_id foi fornecido
        $account = db_get("*", self::TB_ACCOUNTS, [
            "social_network" => "whatsapp", 
            "category" => "profile", 
            "token" => $instance_id, 
            "team_id" => $team_id
        ]);

        // Busca apenas contas ativas
        $accounts = db_fetch("*", self::TB_ACCOUNTS, [
            "social_network" => "whatsapp",
            "category" => "profile",
            "team_id" => $team_id,
            "status" => 0
        ]);

        $content_data['accounts'] = $accounts;

        if(empty($account)){
            $session = db_get("*", self::TB_WHATSAPP_SESSIONS,["status" => 0, "team_id" => $team_id]);
            if(empty($session)){
                $instance_id = strtoupper(uniqid());
                db_delete(self::TB_WHATSAPP_SESSIONS, ["status" => 0, "team_id" => $team_id]);
                db_insert( self::TB_WHATSAPP_SESSIONS, [
                    "ids" => ids(),
                    "instance_id" => $instance_id,
                    "team_id" => $team_id,
                    "data" => NULL,
                    "status" => 0
                ] );

                $content_data['instance_id'] = $instance_id;
            }else{
                $content_data['instance_id'] = $session->instance_id;
            }
        }else{
            db_update(self::TB_WHATSAPP_SESSIONS, [ 'status' => 0], [ 'instance_id' => $account->token ]);
            $content_data['instance_id'] = $instance_id;
        }
        
        $content_data["has_pair"] = false;
        $content_data["pair_code"] = "";
        $content_data["error_msg"] = "";
        $content_data["has_error"] = false;
        
        if(isset($_POST['phone'])){
            $account = db_get("*", self::TB_ACCOUNTS, ["social_network" => "whatsapp", "category" => "profile", "token" => $instance_id, "team_id" => $team_id]);
            $access_token = get_team("ids");
            if($account){
                $session = db_get("*", self::TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "status" => 0]);
                if($session){
                    if($session->instance_id != $instance_id){
                        db_update( self::TB_WHATSAPP_SESSIONS, [
                            "instance_id" => $instance_id,
                            "status" => 0
                        ], [ 'id' => $session->id ] );
                    }else{
                        db_insert( self::TB_WHATSAPP_SESSIONS, [
                            "ids" => ids(),
                            "instance_id" => $instance_id,
                            "team_id" => $team_id,
                            "data" => NULL,
                            "status" => 0] );
                    }
                }
            }else{
                if(!check_number_account("whatsapp", "profile", false, false)){
                    return false;
                    $content_data["has_pair"] = false;
                }
            }
            
            $results = wa_get_curl("get_qrcode", [ "instance_id" => $instance_id, "access_token" => $access_token ]);
            $result = wa_get_curl("get_paircode", [ "instance_id" => $_POST['instance_id'], "access_token" => $access_token, "phone" => $_POST['phone'] ]);
            
            if(isset($results) && isset($result) && $result->status == "success"){
                
                $content_data["has_pair"] = true;
                $content_data["pair_code"] = $result->code;
                $content_data["has_error"] = false;
            }else if(isset($result) && $result->status == "error"){
                $content_data["error_msg"] = $result->message;
                
                $content_data["has_error"] = true;
                $content_data["has_pair"] = true;
            }else{
                $content_data["has_error"] = true;
                $content_data["has_pair"] = false;
                $content_data["error_msg"] = __("Cannot connect to WhatsApp server. Please make sure the WhatsApp server running."). "</br>" . __("You can follow by documentation at <a href='#' target='_blank'>here</a>");
            }
        }

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "config" => $this->config,
            "content" => view('Core\Whatsapp_profiles\Views\oauth', $content_data)
        ];

        return view('Core\Whatsapp_profiles\Views\index', $data);
    }
    
     public function generate_instance($instance_id = false) {
    $team_id = get_team("id");
    $content_data = ["config" => $this->config];

    $account = db_get("*", self::TB_ACCOUNTS, [
        "social_network" => "whatsapp", 
        "category" => "profile", 
        "token" => $instance_id, 
        "team_id" => $team_id
    ]);

    // Busca apenas contas ativas
    $accounts = db_fetch("*", self::TB_ACCOUNTS, [
        "social_network" => "whatsapp",
        "category" => "profile",
        "team_id" => $team_id,
        "status" => 0
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

    } else {
        // Atualiza o status da instância existente e recupera o PIN
        db_update(self::TB_WHATSAPP_SESSIONS, ["status" => 0], ["instance_id" => $instance_id]);

        $session_data = json_decode($account['data'], true);
        $content_data['instance_id'] = $instance_id;
        $content_data['pin_code'] = $session_data['pin'] ?? null;
        $content_data['has_pair'] = false;
    }

    // Prepara os dados para renderização na view
    $data = [
        "title" => $this->config['name'],
        "desc" => $this->config['desc'],
        "config" => $this->config,
        "content" => view('Core\Whatsapp_profiles\Views\oauth', $content_data)
    ];

    return view('Core\Whatsapp_profiles\Views\index', $data);
}

    public function get_qrcode($instance_id = false){
        $team_id = get_team("id");
        $access_token = get_team("ids");

        $account = db_get("*", self::TB_ACCOUNTS, ["social_network" => "whatsapp", "category" => "profile", "token" => $instance_id, "team_id" => $team_id]);
        if($account){
            $session = db_get("*", self::TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "status" => 0]);
            if($session){
                if($session->instance_id != $instance_id){
                    db_update( self::TB_WHATSAPP_SESSIONS, [
                        "instance_id" => $instance_id,
                        "status" => 0
                    ], [ 'id' => $session->id ] );
                }
            }else{
                db_insert( self::TB_WHATSAPP_SESSIONS, [
                    "ids" => ids(),
                    "instance_id" => $instance_id,
                    "team_id" => $team_id,
                    "data" => NULL,
                    "status" => 0
                ] );
            }
        }else{
            if(!check_number_account("whatsapp", "profile", false, false)){
                return false;
            }
        }

        $result = wa_get_curl("get_qrcode", [ "instance_id" => $instance_id, "access_token" => $access_token ]);
        if($result == ""){
            echo json_encode([
                "status" => "error",
                "message" => __("Cannot connect to WhatsApp server. Please make sure the WhatsApp server running."). "</br>" . __("You can follow by documentation at <a href='#' target='_blank'>here</a>")
            ]);
            exit;
        }

        if( $result->status == "error" ){
            echo json_encode([
                "status" => "error",
                "message" => __( $result->message )
            ]);
            exit;
        }else{
            echo json_encode($result);
            exit;
        }
    }

    public function check_login($instance_id = ""){
        $team_id = get_team("id");
        $whatsapp_session = db_get("*", self::TB_WHATSAPP_SESSIONS, ["status" => 1, "team_id" => $team_id, "instance_id" => $instance_id]);
        
        if($whatsapp_session){

            $profile = false;
            if($whatsapp_session->data != ""){
                $profile = json_decode($whatsapp_session->data);
            }

            $account = db_get("*", self::TB_ACCOUNTS, ["token" => $instance_id, "team_id" => $team_id]);

            if(!$account){
                $account = db_get("*", self::TB_ACCOUNTS, ["pid" => $profile->id, "team_id" => $team_id]);
            }

            if($account){
                $avatar = save_img( $account->avatar, WRITEPATH.'avatar/' );
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

    public function delete(){
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
            try { db_delete(self::TB_WHATSAPP_AUTORESPONDER, ["instance_id" => $account->token]); } catch (\Exception $e) {}
            try { db_delete(self::TB_WHATSAPP_CHATBOT, ["instance_id" => $account->token]); } catch (\Exception $e) {}
            try { db_delete(self::TB_WHATSAPP_SESSIONS, ["instance_id" => $account->token]); } catch (\Exception $e) {}
            try { db_delete(self::TB_WHATSAPP_WEBHOOK, ["instance_id" => $account->token]); } catch (\Exception $e) {}

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

    public function create_profile() {
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

            if(empty($name)) {
                throw new \Exception('O nome do perfil é obrigatório');
            }

            if(!check_number_account("whatsapp", "profile")) {
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

    public function test_endpoint() {
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

    public function disconnect(){
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
}