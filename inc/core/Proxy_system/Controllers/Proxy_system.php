<?php
namespace Core\Proxy_system\Controllers;

class Proxy_system extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Proxy_system\Models\Proxy_systemModel();
    }
    
    public function index( $page = false ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc']
        ];

        switch ( $page ) {
            case 'update':
                $item = false;
                $ids = uri('segment', 4);
                if( $ids ){
                    $item = db_get("*", TB_PROXIES, [ "ids" => $ids, "is_system" => 1 ]);
                }

                $plans = db_fetch("*", TB_PLANS, "", "id", "ASC");

                $data['content'] = view('Core\Proxy_system\Views\update', ["result" => $item, 'plans' => $plans]);
                break;

            case 'assign':
                $start = 0;
                $limit = 1;

                $pager = \Config\Services::pager();
                $total = $this->model->get_list_assigned(false);
                // Exibir proxies globais (team_id=0) e do time atual
                $proxies_global = db_fetch("*", TB_PROXIES, ["status" => 1, "team_id" => 0], "id", "DESC");
                $proxies_team = db_fetch("*", TB_PROXIES, ["status" => 1, "team_id" => get_team("id")], "id", "DESC");
                $proxies = array_merge($proxies_global ?: [], $proxies_team ?: []);

               $datatable = [
                    "responsive" => true,
                    "columns" => [
                        "id" => __("ID"),
                        "account_info" =>  __("Account info"),
                        "user_info" =>  __("User info"),
                        "system_proxy" =>  __("System Proxy"),
                        "proxy_assigned" => __("Proxy assigned"),
                        "location" => __("Proxy location")
                    ],
                    "total_items" => $total,
                    "per_page" => 50,
                    "current_page" => 1,
                ];

                $data_content = [
                    'start' => $start,
                    'limit' => $limit,
                    'total' => $total,
                    'pager' => $pager,
                    'datatable'  => $datatable,
                    'proxies'  => $proxies,
                    'config' => $this->config
                ];

                $data['content'] = view('Core\Proxy_system\Views\list_assigned', $data_content);
                break;

            case 'import':
                $plans = db_fetch("*", TB_PLANS, "", "id", "ASC");
                $data['content'] = view('Core\Proxy_system\Views\import', [
                    'plans' => $plans
                ]);
                break;
            
            default:
                $start = 0;
                $limit = 1;

                $pager = \Config\Services::pager();
                $total = $this->model->get_list(false);

                $datatable = [
                    "responsive" => true,
                    "columns" => [
                        "id" => __("ID"),
                        "Proxy" =>  __("Proxy"),
                        "Location" =>  __("Location"),
                        "Status" => __("Status"),
                        "created" => __("Created"),
                    ],
                    "total_items" => $total,
                    "per_page" => 50,
                    "current_page" => 1,
                ];

                $data_content = [
                    'start' => $start,
                    'limit' => $limit,
                    'total' => $total,
                    'pager' => $pager,
                    'datatable'  => $datatable,
                    'config' => $this->config
                ];

                $data['content'] = view('Core\Proxy_system\Views\list', $data_content);
                break;
        }

        return view('Core\Proxy_system\Views\index', $data);
    }

    public function ajax_list(){
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);
        $data = [
            "result" => $result
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Proxy_system\Views\ajax_list', $data)
        ] );
    }

    public function ajax_list_assigned(){
        $total_items = $this->model->get_list_assigned(false);
        $result = $this->model->get_list_assigned(true);
        $data = [
            "result" => $result
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Proxy_system\Views\ajax_list_assigned', $data)
        ] );
    }

    public function save($ids = "")
    {
        // Log de início da operação
        log_message('info', 'Iniciando save de proxy para ids=' . $ids . ' por usuário=' . (get_user('id') ?? 'anon'));

        // Verificação de rate limit
        if (check_rate_limit()) {
            log_message('warning', 'Rate limit excedido para usuário=' . (get_user('id') ?? 'anon'));
            return ms(['status' => 'error', 'message' => 'Rate limit excedido (10 operações por minuto).']);
        }

        $status = post('status');
        $proxy = post('proxy');
        $limit = (int)post('limit');
        $plans = post('plans');

        if(!$plans)  $plans = [];

        // Sanitização básica
        $proxy = trim(strip_tags($proxy));
        $location = proxy_location($proxy);
        if(!$location){
            log_message('error', 'Proxy inválido informado: ' . $proxy);
            ms([
                "status" => "error",
                "message" => __('Proxy format is incorrect')
            ]);
        }

        $item = db_get("*", TB_PROXIES, ["ids" => $ids, "is_system" => 1]);
        if(!$item){
            $item = db_get("*", TB_PROXIES, ["proxy" => $proxy, "is_system" => 1]);
            validate('not_empty', __('This proxy already exists'), $item);

            db_insert(TB_PROXIES , [
                "ids" => ids(),
                "team_id" => 0,
                "is_system" => 1,
                "proxy" => $proxy,
                "location" => $location,
                "limit" => $limit,
                "plans" => json_encode($plans),
                "status" => $status,
                "changed" => time(),
                "created" => time()
            ]);
            log_message('info', 'Proxy inserido: ' . $proxy);
        }else{
            $item = db_get("*", TB_PROXIES, ["ids !=" => $ids, "proxy" => $proxy, "is_system" => 1]);
            validate('not_empty', __('This proxy already exists'), $item);

            db_update(
                TB_PROXIES, 
                [
                    "proxy" => $proxy,
                    "location" => $location,
                    "limit" => $limit,
                    "plans" => json_encode($plans),
                    "status" => $status,
                    "changed" => time()
                ], 
                ["ids" => $ids]
            );
            log_message('info', 'Proxy atualizado: ' . $proxy);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    public function do_assign($ids = ""){
        log_message('info', 'Iniciando do_assign de proxy. Usuário=' . (get_user('id') ?? 'anon'));
        $proxy = post("proxy");
        $ids = post('ids');

        // Validação reforçada
        if( !$proxy ){
            log_message('error', 'Proxy não informado em do_assign.');
            ms([
                "status" => "error",
                "message" => __('Please select a proxy to can assign proxy')
            ]);
        }
        if( empty($ids) || !is_array($ids) ){
            log_message('error', 'IDs de contas não informados ou inválidos em do_assign.');
            ms([
                "status" => "error",
                "message" => __('Please select an account to can assign proxy')
            ]);
        }
  
        foreach ($ids as $id) 
        {
            $account = db_get("*", TB_ACCOUNTS, ["ids" => $id]);
            validate('empty', __('Cannot find account to assign proxy'), $account);

            // Permitir atribuir proxies globais ou do time (sem exigir is_system=1)
            $proxy_item = db_get("*", TB_PROXIES, ["id" => $proxy]);
            validate('empty', __('This proxy does not exist'), $proxy_item);

            db_update(
                TB_ACCOUNTS, 
                ["proxy" => $proxy_item->id], 
                [ "id" => $account->id ]
            );
            log_message('info', 'Proxy atribuído: proxy_id=' . $proxy_item->id . ' para account_id=' . $account->id);

            // Sinaliza a API Node para recarregar o agent dessa instância
            if (!empty($account->token)) {
                $this->touch_instance_proxy($account->token);
            }
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    public function remove_assign($ids = ""){
        log_message('info', 'Iniciando remoção de proxy de contas. Usuário=' . (get_user('id') ?? 'anon'));
        $ids = post('ids');

        if( empty($ids) || !is_array($ids) ){
            log_message('error', 'IDs de contas não informados ou inválidos em remove_assign.');
            ms([
                "status" => "error",
                "message" => __('Please select an account to can assign proxy')
            ]);
        }

        foreach ($ids as $id) 
        {
            $account = db_get("*", TB_ACCOUNTS, ["ids" => $id]);
            db_update(
                TB_ACCOUNTS, 
                ["proxy" => ""], 
                [ "ids" => $id ]
            );
            log_message('info', 'Proxy removido da account_id=' . $id);

            if ($account && !empty($account->token)) {
                $this->touch_instance_proxy($account->token);
            }
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    private function touch_instance_proxy($instance_id){
        helper('Core\Proxy_system\Helpers\Proxy_system_helper');
        $url = proxy_probe_base_url() . '?instance_id=' . urlencode($instance_id);
        try {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 4,
                    'ignore_errors' => true,
                ]
            ]);
            @file_get_contents($url, false, $ctx);
        } catch (\Throwable $e) {
            log_message('warning', 'Falha ao sinalizar refresh de proxy para instância '.$instance_id.': '.$e->getMessage());
        }
    }

    // Proxy server-side para consultar a localização da instância sem CORS/mixed-content
    public function probe_location_ajax(){
        try {
            $id = $this->request->getGet('id') ?? post('id');
            if(!$id){
                return $this->response->setJSON(['status'=>'error','message'=>'ID da conta não informado']);
            }
            $account = db_get("*", TB_ACCOUNTS, ["ids" => $id]);
            if(!$account || empty($account->token)){
                return $this->response->setJSON(['status'=>'error','message'=>'Conta não encontrada ou sem token']);
            }
            helper('Core\Proxy_system\Helpers\Proxy_system_helper');
            $nodeUrl = proxy_probe_base_url() . '?instance_id=' . urlencode($account->token) . '&force=1';
            $ch = curl_init($nodeUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
            $resp = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if($resp === false || $http < 200 || $http >= 300){
                return $this->response->setJSON(['status'=>'error','message'=>'Falha ao consultar localização', 'httpCode'=>$http, 'error'=>$err]);
            }
            // Retornar o JSON do Node diretamente
            return $this->response->setHeader('Content-Type', 'application/json')->setBody($resp);
        } catch(\Throwable $e){
            return $this->response->setJSON(['status'=>'error','message'=>$e->getMessage()]);
        }
    }

    public function download_example_upload_csv(){
        $filename = get_module_dir(__DIR__, 'Assets/csv_template.csv');
        if(file_exists($filename)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: 0");
            header('Content-Disposition: attachment; filename="'.basename($filename).'"');
            header('Content-Length: ' . filesize($filename));
            header('Pragma: public');
            flush();
            readfile($filename);
            redirect_to( get_module_url() );
        }else{
            redirect_to( get_module_url() );
        }
    }

    public function do_import_proxy(){
        log_message('info', 'Iniciando importação de proxies via CSV. Usuário=' . (get_user('id') ?? 'anon'));
        $plans = post('plans');
        $team_id = get_team("id");
        $max_size = 5*1024;
        $file_path = "";

        validate('empty', __('Please select at least one plan'), $plans);

        if(!empty($_FILES) && is_array($_FILES['files']['name'])){
            if(empty( $this->request->getFiles() )){
                log_message('error', 'Nenhum arquivo enviado para importação.');
                ms([
                    "status" => "error",
                    "message" => __('Cannot found files csv to upload')
                ]);
            }

            $check_mime = $this->validate([
                'files' => [
                    'uploaded[files]',
                    'ext_in[files,csv]'
                ],
            ]);

            if(!$check_mime){
                log_message('error', 'Tipo de arquivo inválido na importação CSV.');
                ms([
                    "status" => "error",
                    "message" => "The filetype you are attempting to upload is not allowed"
                ]);
            }

            $check_size = $this->validate([
                'files' => [
                    'uploaded[files]',
                    'max_size[files,'.$max_size.']'
                ],
            ]);

            if(!$check_size){
                log_message('error', 'Arquivo CSV excede o tamanho permitido.');
                ms([
                    "status" => "error",
                    "message" => __( sprintf("Unable to upload a file larger than %sMB", $maxsize) )
                ]);
            }

            if ($file = $this->request->getFiles()) {
                if( isset( $file['files'] ) ){
                    foreach($file['files'] as $img) {
                        if ($img->isValid() && ! $img->hasMoved()) {
                            $newName = $img->getRandomName();
                            $img->move(WRITEPATH.'uploads', $newName);
                            $file_path = WRITEPATH.'uploads/'.$newName;
                        }
                    }
                }
            }
        }

        if($file_path == ""){
            log_message('error', 'Falha ao salvar arquivo CSV.');
            ms([
                "status" => "error",
                "message" => __("Upload csv file failed.")
            ]);
        }

        $csvReader = new \yidas\csv\Reader($file_path);
        $csvFile = $csvReader->readRows();
        $headers = [];
        $proxies = [];
        foreach($csvFile as $key => $row) {
            if( $key != 0 ){
                if(is_array($row )){
                    $proxy = trim(strip_tags($row[0]));
                    $limit = (int)$row[1];
                }
                // Validação linha a linha
                if(!$proxy || !$limit || !proxy_location($proxy)){
                    log_message('error', 'Linha inválida no CSV: proxy=' . ($proxy ?? 'null') . ', limit=' . ($limit ?? 'null'));
                    continue;
                }
                if( $location = proxy_location($proxy) ){
                    $proxies[] = [
                        "ids" => ids(),
                        "team_id" => 0,
                        "is_system" => 1,
                        "proxy" => $proxy,
                        "location" => $location,
                        "limit" => $limit,
                        "plans" => json_encode($plans),
                        "status" => 1,
                        "changed" => time(),
                        "created" => time()
                    ];
                    log_message('info', 'Proxy importado do CSV: ' . $proxy);
                }
            }else{
                if(!empty($row)){
                    foreach ($row as $pos => $value) {
                        if($pos != 0){
                            $headers[] = $value;
                        }
                    }
                }
            }
        }

        if(!empty($proxies)){
            db_insert( TB_PROXIES, $proxies );
        }

        unlink($file_path);

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    public function delete( $ids = '' ){
        log_message('info', 'Iniciando exclusão de proxy(s). Usuário=' . (get_user('id') ?? 'anon'));
        if($ids == ''){
            $ids = post('ids');
        }

        if( empty($ids) ){
            log_message('error', 'IDs não informados para exclusão de proxy(s).');
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if( is_array($ids) )
        {
            foreach ($ids as $id) 
            {
                db_delete(TB_PROXIES, ['ids' => $id]);
                log_message('info', 'Proxy excluído: ids=' . $id);
            }
        }
        elseif( is_string($ids) )
        {
            db_delete(TB_PROXIES, ['ids' => $ids]);
            log_message('info', 'Proxy excluído: ids=' . $ids);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    /**
     * Endpoint para validar se o proxy atribuído ao perfil está realmente mudando a localização.
     * Exemplo de uso: /proxy_system/testar_localizacao/{account_id}
     */
    public function testar_localizacao($account_id = null) {
        if (!$account_id) {
            return ms(['status' => 'error', 'message' => 'ID do perfil não informado.']);
        }
        $account = db_get("*", TB_ACCOUNTS, ["id" => $account_id]);
        if (!$account || empty($account->proxy)) {
            return ms(['status' => 'error', 'message' => 'Perfil não encontrado ou sem proxy atribuído.']);
        }
        $proxy_item = db_get("*", TB_PROXIES, ["id" => $account->proxy]);
        if (!$proxy_item) {
            return ms(['status' => 'error', 'message' => 'Proxy não encontrado.']);
        }
        $proxy = $proxy_item->proxy;
        $proxy_auth = null;
        if (strpos($proxy, '@') !== false) {
            list($auth, $host) = explode('@', $proxy, 2);
            $proxy = $host;
            $proxy_auth = $auth;
        }
        $url = 'http://ipinfo.io/json';
        $tipos = [CURLPROXY_HTTP, CURLPROXY_SOCKS5];
        $erro = '';
        foreach ($tipos as $tipo) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
            curl_setopt($ch, CURLOPT_PROXYTYPE, $tipo);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            if ($proxy_auth) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_auth);
            }
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                $erro = curl_error($ch);
                curl_close($ch);
                continue;
            }
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($http_code >= 200 && $http_code < 300 && $result) {
                $info = json_decode($result, true);
                // Atualiza a localização do proxy no banco para refletir a localização real
                if (!empty($info['country'])) {
                    db_update(TB_PROXIES, ['location' => $info['country']], ['id' => $proxy_item->id]);
                }
                return ms([
                    'status' => 'success',
                    'proxy_ip' => $info['ip'] ?? null,
                    'proxy_country' => $info['country'] ?? null,
                    'proxy_city' => $info['city'] ?? null,
                    'proxy_org' => $info['org'] ?? null,
                    'proxy_type' => $tipo == CURLPROXY_HTTP ? 'HTTP' : 'SOCKS5',
                    'mensagem' => 'Localização detectada via proxy.'
                ]);
            } else {
                $erro = 'HTTP code ' . $http_code . ' - ' . $result;
            }
        }
        return ms(['status' => 'error', 'message' => 'Erro ao testar proxy: ' . $erro]);
    }
}