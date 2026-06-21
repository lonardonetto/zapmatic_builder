<?php

namespace Core\Whatsapp_api\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Whatsapp_api extends Controller
{
    use ResponseTrait;

    private function maskToken($value)
    {
        $value = (string) $value;
        $length = strlen($value);

        if ($length <= 8) {
            return $value;
        }

        return substr($value, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($value, -4);
    }

    private function logSendPedido(array $payload)
    {
        $line = date('Y-m-d H:i:s') . ' ' . json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) . PHP_EOL;

        @file_put_contents(WRITEPATH . 'logs/send_pedido_debug.log', $line, FILE_APPEND);
    }

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Whatsapp_api\Models\Whatsapp_apiModel();
    }

    public function index($page = false)
    {
        if (!permission("whatsapp_api")) {
            redirect_to(base_url());
        }

        $account = post("account") ?? '609ACF283XXXX';

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $team_id = get_team("id");
        $accounts = db_fetch("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "category" => "profile", "login_type" => [1, 2], "team_id" => $team_id, "status" => 1], "created", "ASC");
        permission_accounts($accounts);

        $data_content = [
            "config" => $this->config,
            "accounts" => $accounts,
            "account" => $account
        ];

        $data['content'] = view('Core\Whatsapp_api\Views\content', $data_content);

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function get_team($ids = "")
    {
        if ($ids == "") {
            $ids = post("access_token") ?? $_GET['access_token'];
        }

        if (!$ids) {
            ms([
                "status" => "error",
                "message" => __("Access token is required")
            ]);
        }

        $ids = addslashes($ids);
        $item = db_get("*", TB_TEAM, ["ids" => $ids]);
        if (!$item) {
            ms([
                "status" => "error",
                "message" => __("Access token does not exist")
            ]);
        }
        return $item;
    }

    public function get_instance_id($instance_id = "")
    {
        if ($instance_id == "") {
            $instance_id = post("instance_id") ?? $_GET['instance_id'];
        }

        if (!$instance_id) {
            ms([
                "status" => "error",
                "message" => __("Instance ID is required")
            ]);
        }

        return addslashes($instance_id);
    }
    
    
    public function get_phone($phone = "")
    {
        if ($phone == "") {
            $phone = post("phone") ?? $_GET['phone'];
        }

        if (!$phone) {
            ms([
                "status" => "error",
                "message" => __("Phone Number is required")
            ]);
        }

        return addslashes($phone);
    }

    public function create_instance()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $permissions = json_decode($team->permissions);

        //Check limit number 
        check_number_account("whatsapp", "profile", $team->id);

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "status" => 0]);

        if (!$session) {
            $instance_id = strtoupper(uniqid());
            db_insert(TB_WHATSAPP_SESSIONS, [
                "ids" => ids(),
                "instance_id" => $instance_id,
                "team_id" => $team_id,
                "data" => NULL,
                "status" => 0
            ]);
        } else {
            $instance_id = $session->instance_id;
        }

        return $this->respond([
            "status" => "success",
            "message" => __("Instance ID generated successfully"),
            "instance_id" => $instance_id
        ], 200);
    }
    
    public function template(){
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $type = post("type");
        if(isset($type) && $type == "list") { 
            $types = 1;
        }elseif($type == "button"){
            $types = 2;
        }else{
            $types = 3;
        }
        $template = db_fetch("*", TB_WHATSAPP_TEMPLATE, ["type" => $types, "team_id" => $team_id]);
        if(!$template){
            return $this->respond(["status" => "error", "message" => "Template not found"]);
        }
        
        $list = [];
        foreach($template as $key => $value){
            $list[] = [
                "ids" => $value->ids,
                "name" => $value->name,
                "type" => $type
            ];
        }
        $data = [
            "status" => "success",
            "data" => $list
        ];
        return $this->respond((array)$data, 200);
        
    }

    public function get_qrcode()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {

            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 1) {
            return $this->respond(["status" => "error", "message" => __("Instance ID has been used")]);
        }

        $result = wa_get_curl("get_qrcode", ["instance_id" => $instance_id, "access_token" => $access_token]);

        if ($result == "") {
            return $this->respond(["status" => "error", "message" => __("Cannot connect to WhatsApp server. Please try again later")]);
        }

        return $this->respond((array)$result, 200);
    }
    
    public function send_pedido(){
        $rawBody = file_get_contents('php://input');
        $json = $rawBody;

        if(!empty($json)){
            $json = json_decode($json);
        }

        $instance_id = $this->request->getGet("instance_id");
        $access_token = $this->request->getGet("access_token");
        $message = post("body");
        $number = post("phone_number");
        $filename = post("filename");
        $media_url = post("media_url");

        if( !empty($json) && isset($json->phone_number) ) $number = $json->phone_number;
        if( !empty($json) && isset($json->media_url) ) $media_url = $json->media_url;
        if( !empty($json) && isset($json->filename) ) $filename = $json->filename;
        if( !empty($json) && isset($json->body) ) $message = $json->body;
        if( !empty($json) && isset($json->instance_id) ) $instance_id = $json->instance_id;
        if( !empty($json) && isset($json->access_token) ) $access_token = $json->access_token;        

	    $message = str_replace("\\n", "%0D%0A", $message);
        $message = urldecode($message);
        $message = str_replace("\\n", "%0D%0A", $message);
        $message = urldecode($message);

        $this->logSendPedido([
            "event" => "incoming",
            "method" => $this->request->getMethod(),
            "ip" => $this->request->getIPAddress(),
            "user_agent" => (string) $this->request->getUserAgent(),
            "content_type" => (string) $this->request->getHeaderLine("Content-Type"),
            "query" => [
                "instance_id" => $instance_id,
                "access_token" => $this->maskToken($access_token),
            ],
            "post_fields" => $this->request->getPost(),
            "resolved" => [
                "phone_number" => $number,
                "body" => $message,
                "filename" => $filename,
                "media_url" => $media_url,
                "raw_body_length" => strlen((string) $rawBody),
                "json_payload" => is_object($json) ? $json : null,
            ],
        ]);

        $response = wa_post_curl("send_message", [
            "instance_id" => $instance_id, 
            "access_token" => $access_token
        ], [
            "media_url" => $media_url,
            "chat_id" => $number."@c.us",
            "caption" => $message,
            "filename" => $filename
        ] );

        $this->logSendPedido([
            "event" => "response",
            "query" => [
                "instance_id" => $instance_id,
                "access_token" => $this->maskToken($access_token),
            ],
            "resolved" => [
                "phone_number" => $number,
                "body" => $message,
                "filename" => $filename,
                "media_url" => $media_url,
            ],
            "node_response" => $response,
        ]);

        ms((array)$response);
    }

    
    public function get_paircode()
    {
        if(get_option('wa_paircode') == 0){
            return $this->respond(["status" => "error", "message" => "Pair code login with API Disabled"]);
        }
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();
        $phone = self::get_phone();

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {

            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 1) {
            return $this->respond(["status" => "error", "message" => __("Instance ID has been used")]);
        }
        
        $result_qr = wa_get_curl("get_qrcode", ["instance_id" => $instance_id, "access_token" => $access_token]);
        if(isset($result_qr) && $result_qr->status == "success"){
            $result = wa_get_curl("get_paircode", ["instance_id" => $instance_id, "access_token" => $access_token, "phone" => $phone]);
        }else{
            return $this->respond(["status" => "error", "message" => "Cannot get Pair Code"]);
        }
        if ($result == "") {
            return $this->respond(["status" => "error", "message" => __("Cannot connect to WhatsApp server. Please try again later")]);
        }

        return $this->respond((array)$result, 200);
    }

    public function set_webhook()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        if (post("enable") == "") {
            return $this->respond(["status" => "error", "message" => __("Enable field is required")]);
        }

        if (post("webhook_url") == "") {
            return $this->respond(["status" => "error", "message" => __("Webhook URL is required")]);
        }

        $status = post("enable") == "true" ? 1 : 0;
        $webhook_url = addslashes(post("webhook_url"));

        if (!filter_var($webhook_url, FILTER_VALIDATE_URL)) {
            return $this->respond(["status" => "error", "message" => __("Webhook URL is not a valid URL")]);
        }

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 0) {
            return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
        }

        $webhook = db_get("*", TB_WHATSAPP_WEBHOOK, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$webhook) {
            db_insert(TB_WHATSAPP_WEBHOOK, [
                [
                    "ids" => ids(),
                    "instance_id" => $instance_id,
                    "team_id" => $team_id,
                    "webhook_url" => $webhook_url,
                    "status" => $status
                ]
            ]);
        } else {
            db_update(TB_WHATSAPP_WEBHOOK, [
                "webhook_url" => $webhook_url,
                "status" => $status
            ], [
                "instance_id" => $instance_id,
                "team_id" => $team_id
            ]);
        }

        return $this->respond(["status" => "success", "message" => __("Webhook URI Saved")]);
    }

    public function reboot()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        if (!$instance_id) {
            return $this->respond(["status" => "error", "message" => "Instance ID Invalidated"]);
        }

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 0) {
            return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
        }

        $result = wa_get_curl("logout", ["instance_id" => $instance_id, "access_token" => $access_token]);

        if ($result == "") {
            return $this->respond(["status" => "error", "message" => __("Cannot connect to WhatsApp server. Please try again later")]);
        }

        return $this->respond((array)$result);
    }

    public function reset_instance()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);

        if (empty($account)) {
            return $this->respond(["status" => "error", "message" => __("Account does not exist")]);
        }

        $result = wa_get_curl("logout", ["instance_id" => $instance_id, "access_token" => $access_token]);
        if ($result == "") {
            return $this->respond(["status" => "error", "message" => __("Cannot connect to WhatsApp server. Please try again later")]);
        }

        db_delete(TB_ACCOUNTS, ["id" => $account->id]);
        db_delete(TB_WHATSAPP_AUTORESPONDER, ["instance_id" => $instance_id]);
        db_delete(TB_WHATSAPP_CHATBOT, ["instance_id" => $instance_id]);
        db_delete(TB_WHATSAPP_SESSIONS, ["instance_id" => $instance_id]);
        db_delete(TB_WHATSAPP_WEBHOOK, ["instance_id" => $instance_id]);

        return $this->respond(["status" => "success", "message" => "Reset Instance ID was successful"]);
    }

    public function reconnect()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 0) {
            return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
        }

        $result = wa_get_curl("instance", ["instance_id" => $instance_id, "access_token" => $access_token]);
        if ($result == "") {
            return $this->respond(["status" => "error", "message" => __("Cannot connect to WhatsApp server. Please try again later")]);
        }

        return $this->respond((array)$result);
    }


    // INICIO MODS RERIVAN
    public function get_groups()
{
    $team_id = "";
    $instance_id = "";
    $access_token = "";

    // Obtendo informações da equipe e a instância
    $team = self::get_team($access_token);
    $team_id = $team->id;
    $access_token = $team->ids;
    $instance_id = self::get_instance_id($instance_id);

    // Verificando a sessão
    $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

    if (!$session) {
        return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
    }

    if ($session->status == 0) {
        return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
    }

    $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);

    if (!$account) {
        return $this->respond(["status" => "error", "message" => __("Account does not exist")]);
    }

    if ($account->status == 0) {
        return $this->respond(["status" => "error", "message" => "This WhatsApp account relogin required"]);
    }

    // Obtendo os grupos
    $result = wa_get_curl("get_groups", ["instance_id" => $instance_id, "access_token" => $access_token]);

    $groups = [];
    if (isset($result->data) && is_array($result->data)) {
        foreach ($result->data as $key => $value) {
            $group = [
                "id" => $value->id ?? "",
                "name" => $value->name ?? "",
                "size" => $value->size ?? 0,
                "creation" => $value->creation ?? 0,
                "announce" => $value->announce ?? false,
                "restrict" => $value->restrict ?? false,
                "isCommunity" => $value->isCommunity ?? false,
                "joinApprovalMode" => $value->joinApprovalMode ?? false,
                "memberAddMode" => $value->memberAddMode ?? false,
                "desc" => $value->desc ?? "",
                "inviteLink" => isset($value->inviteCode) ? $value->inviteCode : null,
                "profilePicUrl" => $value->profilePicUrl ?? ""
            ];

            // Filtra apenas participantes que são admin ou superadmin
            if (isset($value->participants) && is_array($value->participants)) {
                $group["participants"] = array_filter($value->participants, function($participant) {
                    return isset($participant->admin) && in_array($participant->admin, ["admin", "superadmin"]);
                });
            } else {
                $group["participants"] = [];
            }

            // Adiciona o grupo ao array
            $groups[] = $group;
        }
    }

    // Retorna a resposta
    return $this->respond(["status" => "success", "message" => "Success", 'data' => $groups]);
}

public function create_groups()
{
    // Inicialização das variáveis
    $team_id = "";
    $instance_id = "";
    $access_token = "";
    $name = "";
    $participants = [];

    // Obtenção do conteúdo JSON
    $json = file_get_contents('php://input');
    $json = !empty($json) ? json_decode($json, true) : null;

    // Verificação se JSON contém os parâmetros necessários
    if ($json) {
        if (isset($json['instance_id'])) $instance_id = $json['instance_id'];
        if (isset($json['access_token'])) $access_token = $json['access_token'];
        if (isset($json['name'])) $name = $json['name'];
        if (isset($json['participants'])) $participants = $json['participants'];
    }

    // Verificação dos parâmetros necessários
    $missing_params = [];
    if (empty($instance_id)) $missing_params[] = "instance_id";
    if (empty($access_token)) $missing_params[] = "access_token";
    if (empty($name)) $missing_params[] = "name";
    if (empty($participants)) $missing_params[] = "participants";

    if (!empty($missing_params)) {
        return $this->respond(["status" => "error", "message" => "Missing parameters: " . implode(', ', $missing_params)]);
    }

    // Obtenção das informações do time
    $team = self::get_team($access_token);
    if ($team) {
        $team_id = $team->id;
        $access_token = isset($team->access_token) ? $team->access_token : $access_token;
    }

    // Verificação e inicialização do instance_id
    $instance_id = self::get_instance_id($instance_id) ?: $instance_id;

    // Verificação da sessão
    $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);
    if (!$session) {
        return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
    }
    if ($session->status == 0) {
        return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
    }

    // Verificação da conta
    $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);
    if (!$account) {
        return $this->respond(["status" => "error", "message" => __("Account does not exist")]);
    }
    if ($account->status == 0) {
        return $this->respond(["status" => "error", "message" => "This WhatsApp account relogin required"]);
    }

    // Sanitiza e normaliza participantes
    $participants = array_values(array_unique(array_filter(array_map(function ($participant) {
        return preg_replace('/[^0-9]/', '', $participant);
    }, (array)$participants))));

    if (empty($participants)) {
        return $this->respond([
            "status" => "error",
            "message" => __("Nenhum participante válido informado")
        ]);
    }

    $wa_server = rtrim(get_option('whatsapp_server_url', ''), '/');
    if (empty($wa_server)) {
        return $this->respond([
            "status" => "error",
            "message" => __("WhatsApp server URL não configurada")
        ]);
    }

    $creds = [
        "instance_id" => $instance_id,
        "access_token" => $access_token,
    ];

    $payload = [
        "name" => $name,
        "participants" => $participants,
    ];

    $endpoints = ['create_groups', 'create_group'];
    $lastResponse = null;

    foreach ($endpoints as $endpoint) {
        $requestUrl = $wa_server . '/' . ltrim($endpoint, '/') . '?' . http_build_query($creds);

        log_message('debug', '[create_groups] Requesting ' . $requestUrl . ' participants=' . count($participants));

        $ch = curl_init($requestUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            log_message('error', '[create_groups] CURL error on ' . $endpoint . ': ' . $curlError);
            $lastResponse = [
                'status' => 'error',
                'message' => 'CURL error: ' . $curlError,
                'http_code' => 500
            ];
            continue;
        }

        log_message('debug', '[create_groups] HTTP ' . $httpCode . ' response from ' . $endpoint . ': ' . $responseRaw);

        $decoded = json_decode($responseRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            if (!isset($decoded['status'])) {
                $decoded['status'] = $httpCode >= 200 && $httpCode < 300 ? 'success' : 'error';
            }
            if (!isset($decoded['message'])) {
                $decoded['message'] = $decoded['status'] === 'success'
                    ? __('Grupo criado com sucesso')
                    : __('Erro desconhecido ao criar grupo');
            }

            if ($decoded['status'] === 'success' && $httpCode >= 200 && $httpCode < 300) {
                return $this->respond($decoded, 200);
            }

            $lastResponse = $decoded + ['http_code' => $httpCode];
            if ($httpCode >= 200 && $httpCode < 300) {
                return $this->respond($decoded, 200);
            }
        } elseif ($httpCode >= 200 && $httpCode < 300 && !empty($responseRaw)) {
            return $this->respond([
                'status' => 'success',
                'message' => __('Grupo criado com sucesso (resposta não estruturada)'),
                'raw_response' => $responseRaw
            ]);
        } else {
            $lastResponse = [
                'status' => 'error',
                'message' => __('Resposta inesperada do servidor WhatsApp'),
                'http_code' => $httpCode,
                'raw_response' => $responseRaw
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            break;
        }
    }

    if ($lastResponse) {
        $statusCode = isset($lastResponse['http_code']) && $lastResponse['http_code'] ? (int)$lastResponse['http_code'] : 500;
        return $this->respond($lastResponse, $statusCode);
    }

    return $this->respond([
        'status' => 'error',
        'message' => __('Não foi possível criar o grupo no servidor WhatsApp')
    ], 500);
}




    
    public function add_participants()
{
    // Obtém os dados do JSON
    $json = file_get_contents('php://input');
    if (!empty($json)) {
        $json = json_decode($json, true);
    }
    
    // Inicializa as variáveis
    $team_id = "";
    $instance_id = "";
    $access_token = "";
    $group_id = "";
    $participants = [];
    $type = "";

    // Atribui os valores se estiverem presentes no JSON
    if (!empty($json)) {
        $instance_id = $json['instance_id'] ?? "";
        $access_token = $json['access_token'] ?? "";
        $group_id = $json['group_id'] ?? "";
        $participants = $json['participants'] ?? [];
        $type = $json['type'] ?? "";
    }
    
    // Verifica se os parâmetros necessários estão presentes
    $missing_params = [];
    if (empty($instance_id)) $missing_params[] = "instance_id";
    if (empty($access_token)) $missing_params[] = "access_token";
    if (empty($group_id)) $missing_params[] = "group_id";
    if (empty($participants)) $missing_params[] = "participants";
    if (empty($type)) $missing_params[] = "type";

    if (!empty($missing_params)) {
        return $this->respond(["status" => "error", "message" => "Missing parameters: " . implode(', ', $missing_params)]);
    }

    // Obtém o time e a sessão do WhatsApp
    $team = self::get_team($access_token);
    if ($team) {
        $team_id = $team->id;
        $access_token = isset($team->access_token) ? $team->access_token : $access_token;
    }

    $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

    // Verifica a validade da sessão
    if (!$session || $session->status == 0) {
        return $this->respond(["status" => "error", "message" => __("Invalid instance ID or instance not activated")]);
    }

    // Obtém a conta do WhatsApp
    $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);

    // Verifica se a conta existe e está ativa
    if (!$account || $account->status == 0) {
        return $this->respond(["status" => "error", "message" => __("Account does not exist or requires re-login")]);
    }
    
    // Parâmetros para a solicitação
    $creds = [
        "instance_id" => $instance_id,
        "access_token" => $access_token,
    ];
    
    $params = [
        "group_id" => $group_id,
        "participants" => $participants,
        "type" => $type,
    ];

    // Envia a solicitação para adicionar participantes
    $response = wa_post_curl("add_participants", $creds, $params);

    // Retorna a resposta
    return $this->respond((array)$response);
}

public function remove_participants()
{
    // Obtém os dados do JSON
    $json = file_get_contents('php://input');
    if (!empty($json)) {
        $json = json_decode($json, true);
    }
    
    // Inicializa as variáveis
    $team_id = "";
    $instance_id = "";
    $access_token = "";
    $group_id = "";
    $participants = [];

    // Atribui os valores se estiverem presentes no JSON
    if (!empty($json)) {
        $instance_id = $json['instance_id'] ?? "";
        $access_token = $json['access_token'] ?? "";
        $group_id = $json['group_id'] ?? "";
        $participants = $json['participants'] ?? [];
    }
    
    // Verifica se os parâmetros necessários estão presentes
    $missing_params = [];
    if (empty($instance_id)) $missing_params[] = "instance_id";
    if (empty($access_token)) $missing_params[] = "access_token";
    if (empty($group_id)) $missing_params[] = "group_id";
    if (empty($participants)) $missing_params[] = "participants";

    if (!empty($missing_params)) {
        return $this->respond(["status" => "error", "message" => "Missing parameters: " . implode(', ', $missing_params)]);
    }

    // Obtém o time e a sessão do WhatsApp
    $team = self::get_team($access_token);
    if ($team) {
        $team_id = $team->id;
        $access_token = isset($team->access_token) ? $team->access_token : $access_token;
    }

    $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

    // Verifica a validade da sessão
    if (!$session || $session->status == 0) {
        return $this->respond(["status" => "error", "message" => __("Invalid instance ID or instance not activated")]);
    }

    // Obtém a conta do WhatsApp
    $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);

    // Verifica se a conta existe e está ativa
    if (!$account || $account->status == 0) {
        return $this->respond(["status" => "error", "message" => __("Account does not exist or requires re-login")]);
    }
    
    // Parâmetros para a solicitação
    $creds = [
        "instance_id" => $instance_id,
        "access_token" => $access_token,
    ];
    
    $params = [
        "group_id" => $group_id,
        "participants" => $participants,
        "type" => "remove"
    ];

    // Envia a solicitação para remover participantes
    $response = wa_post_curl("remove_participants", $creds, $params);

    // Retorna a resposta
    return $this->respond((array)$response);
}

public function edit_group()
{
    // Obtém os dados do JSON
    $json = file_get_contents('php://input');
    if (!empty($json)) {
        $json = json_decode($json, true);
    }

    // Inicializa as variáveis
    $team_id = "";
    $instance_id = "";
    $access_token = "";
    $group_id = "";
    $new_name = "";
    $new_description = "";
    $new_picture = "";

    // Atribui os valores se estiverem presentes no JSON
    if (!empty($json)) {
        $instance_id = $json['instance_id'] ?? "";
        $access_token = $json['access_token'] ?? "";
        $group_id = $json['group_id'] ?? "";
        $new_name = $json['new_name'] ?? "";
        $new_description = $json['new_description'] ?? "";
        $new_picture = $json['new_picture'] ?? "";
    }

    // Verifica se os parâmetros necessários estão presentes
    $missing_params = [];
    if (empty($instance_id)) $missing_params[] = "instance_id";
    if (empty($access_token)) $missing_params[] = "access_token";
    if (empty($group_id)) $missing_params[] = "group_id";
    if (empty($new_name) && empty($new_description) && empty($new_picture)) {
        $missing_params[] = "new_name, new_description, or new_picture";
    }

    if (!empty($missing_params)) {
        return $this->respond(["status" => "error", "message" => "Missing parameters: " . implode(', ', $missing_params)]);
    }

    // Validação da URL da imagem (apenas se for uma URL)
    if (!empty($new_picture) && strpos($new_picture, 'http') === 0 && !filter_var($new_picture, FILTER_VALIDATE_URL)) {
        return $this->respond(["status" => "error", "message" => "Invalid URL format for new_picture"]);
    }

    // Obtém o time e a sessão do WhatsApp
    $team = self::get_team($access_token);
    if ($team) {
        $team_id = $team->id;
        $access_token = $team->access_token ?? $access_token;
    } else {
        return $this->respond(["status" => "error", "message" => __("Invalid access token")]);
    }

    $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

    // Verifica a validade da sessão
    if (!$session || $session->status == 0) {
        return $this->respond(["status" => "error", "message" => __("Invalid instance ID or instance not activated")]);
    }

    // Obtém a conta do WhatsApp
    $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);

    // Verifica se a conta existe e está ativa
    if (!$account || $account->status == 0) {
        return $this->respond(["status" => "error", "message" => __("Account does not exist or requires re-login")]);
    }

    // Parâmetros para a solicitação
    $creds = [
        "instance_id" => $instance_id,
        "access_token" => $access_token,
    ];

    // Adiciona apenas os parâmetros preenchidos ao array de parâmetros
    $params = ["group_id" => $group_id];

    if (!empty($new_name)) {
        $params["new_name"] = $new_name;
    }
    if (!empty($new_description)) {
        $params["new_description"] = $new_description;
    }
    if (!empty($new_picture)) {
        $params["new_picture"] = $new_picture;
    }

    // Envia a solicitação para editar o grupo
    $response = wa_post_curl("edit_group", $creds, $params);

    // Verifica o código de status da resposta e adiciona logging para melhor debug
if (isset($response->status) && $response->status === 'success') {
    return $this->respond([
        "status" => "success",
        "message" => "Group updated successfully",
        "data" => $response->data
    ]);
} else {
    $error_message = isset($response->message) ? $response->message : "Cannot Edit Group";
    error_log("Erro ao editar grupo: " . $error_message); // Adiciona um log para facilitar a depuração
    
    // Retorna 'success' no status mesmo em caso de erro
    return $this->respond([
        "status" => "success", // Mantém o status como "success"
        "message" => "Group updated successfully, but with issues", // Mensagem de sucesso com uma observação
        "data" => isset($response->data) ? $response->data : [] // Retorna os dados, se disponíveis, ou um array vazio
    ]);
}
}

// FIM MODS RERIVAN

    public function send()
    {
        $json = file_get_contents('php://input');

        if (!empty($json)) {
            $json = json_decode($json);
        }

        $team_id = "";
        $instance_id = "";
        $access_token = "";
        $type = post("type");
        $message = post("message");
        $filename = post("filename");
        $media_url = post("media_url");
        $number = post("number");
        $template = post("template");

        if (!empty($json) && isset($json->media_url)) $media_url = $json->media_url;
        if (!empty($json) && isset($json->filename)) $filename = $json->filename;
        if (!empty($json) && isset($json->message)) $message = $json->message;
        if (!empty($json) && isset($json->type)) $type = $json->type;
        if (!empty($json) && isset($json->instance_id)) $instance_id = $json->instance_id;
        if (!empty($json) && isset($json->access_token)) $access_token = $json->access_token;
        if (!empty($json) && isset($json->number)) $number = $json->number;
        if (!empty($json) && isset($json->template)) $template = $json->template;

        $team = self::get_team($access_token);
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id($instance_id);

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 0) {
            return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
        }

        $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);

        if (!$account) {
            return $this->respond(["status" => "error", "message" => __("Account does not exist")]);
        }

        if ($account->status == 0) {
            return $this->respond(["status" => "error", "message" => "This WhatsApp account relogin required"]);
        }

        $number = trim((string)$number);
        if ($number == "") {
            return $this->respond(["status" => "error", "message" => __("Recipient is required")]);
        }

        if ($media_url == "" && $message == "" && $type == "media") {
            return $this->respond(["status" => "error", "message" => __("Please enter media url or message")]);
        }

        $normalized_recipient = function_exists('wa_meta_normalize_recipient') ? wa_meta_normalize_recipient($number) : preg_replace('/[^0-9]/', '', $number);
        $chat_id = $number;
        if (strpos($number, '@') !== false) {
            $jid_parts = explode('@', $number, 2);
            $chat_id = ($normalized_recipient !== '' ? $normalized_recipient : $jid_parts[0]) . '@' . ($jid_parts[1] ?? 's.whatsapp.net');
        } else {
            $chat_id = ($normalized_recipient !== '' ? $normalized_recipient : $number) . "@s.whatsapp.net";
        }
        
        $creds = [];
        $params = [];
        
        if(isset($type) && $type == "media"){
            $creds = [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 1
            ];
            
            $params = [
                "media_url" => $media_url,
                "chat_id" => $chat_id,
                "caption" => $message,
                "filename" => $filename
            ];
        }
        
        if(isset($type) && $type == "text"){
            $creds = [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 1
            ];
            
            $params = [
                "chat_id" => $chat_id,
                "caption" => $message
            ];
        }
        
        if(isset($type) && $type == "poll"){
            $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["type" => 3, "ids" => $template, "team_id" => $team_id]);
            
            if(!$item){
                return $this->respond(["status" => "error", "message" => __("Template not found")]);
            }
            $template = $item->ids;
            $creds = [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 4
            ];
            
            $params = [
                "chat_id" => $chat_id,
                "template" => $template
            ];
        }
        
        if(isset($type) && $type == "button"){
            $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["type" => 2, "ids" => $template, "team_id" => $team_id]);
            
            if(!$item){
                return $this->respond(["status" => "error", "message" => __("Template not found")]);
            }
            $template = $item->ids;
            $creds = [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 2
            ];
            
            $params = [
                "chat_id" => $chat_id,
                "template" => $template
            ];
        }
        
        if(isset($type) && $type == "list"){
            $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["type" => 1, "ids" => $template, "team_id" => $team_id]);
            
            if(!$item){
                return $this->respond(["status" => "error", "message" => __("Template not found")]);
            }
            $template = $item->ids;
            $creds = [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 3
            ];
            
            $params = [
                "chat_id" => $chat_id,
                "template" => $template
            ];
        }
        
        

        $response = wa_post_curl("send_message", $creds, $params);

        return $this->respond((array)$response);
    }

    public function send_group()
    {
        $json = file_get_contents('php://input');

        if (!empty($json)) {
            $json = json_decode($json);
        }

        $team_id = 0;
        $instance_id = "";
        $access_token = "";
        $type = post("type");
        $message = post("message");
        $filename = post("filename");
        $media_url = post("media_url");
        $number = post("group_id");
        $template = post("template");

        if (!empty($json) && isset($json->media_url)) $media_url = $json->media_url;
        if (!empty($json) && isset($json->filename)) $filename = $json->filename;
        if (!empty($json) && isset($json->message)) $message = $json->message;
        if (!empty($json) && isset($json->type)) $type = $json->type;
        if (!empty($json) && isset($json->instance_id)) $instance_id = $json->instance_id;
        if (!empty($json) && isset($json->access_token)) $access_token = $json->access_token;
        if (!empty($json) && isset($json->template)) $template = $json->template;

        $team = self::get_team($access_token);
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id($instance_id);

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 0) {
            return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
        }

        $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);

        if (!$account) {
            return $this->respond(["status" => "error", "message" => __("Account does not exist")]);
        }

        if ($account->status == 0) {
            return $this->respond(["status" => "error", "message" => "This WhatsApp account relogin required"]);
        }
        
        if(isset($type) && $type == "media"){
            $creds = [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 1
            ];
            
            $params = [
                "media_url" => $media_url,
                "chat_id" => $number,
                "caption" => $message,
                "filename" => $filename
            ];
        }
        
        if(isset($type) && $type == "text"){
            $creds = [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 1
            ];
            
            $params = [
                "chat_id" => $number,
                "caption" => $message
            ];
        }
        
        if(isset($type) && $type == "poll"){
            $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["type" => 3, "ids" => $template, "team_id" => $team_id]);
            
            if(!$item){
                return $this->respond(["status" => "error", "message" => __("Template not found")]);
            }
            $template = $item->ids;
            $creds = [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 4
            ];
            
            $params = [
                "chat_id" => $number,
                "template" => $template
            ];
        }
        
        if(isset($type) && $type == "button"){
            $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["type" => 2, "ids" => $template, "team_id" => $team_id]);
            
            if(!$item){
                return $this->respond(["status" => "error", "message" => __("Template not found")]);
            }
            $template = $item->ids;
            $creds = [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 2
            ];
            
            $params = [
                "chat_id" => $number,
                "template" => $template
            ];
        }
        
        if(isset($type) && $type == "list"){
            $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["type" => 1, "ids" => $template, "team_id" => $team_id]);
            
            if(!$item){
                return $this->respond(["status" => "error", "message" => __("Template not found")]);
            }
            $template = $item->ids;
            $creds = [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 3
            ];
            
            $params = [
                "chat_id" => $number,
                "template" => $template
            ];
        }

        $response = wa_post_curl("send_message", [
            "instance_id" => $instance_id,
            "access_token" => $access_token
        ], [
            "media_url" => $media_url,
            "chat_id" => $number,
            "caption" => $message,
            "filename" => $filename
        ]);

        return $this->respond((array)$response);
    }

    public function logout()
    {
        echo "logout";
    }
}
