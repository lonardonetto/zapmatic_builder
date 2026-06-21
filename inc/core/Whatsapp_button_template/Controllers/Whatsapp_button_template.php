<?php
namespace Core\Whatsapp_button_template\Controllers;

class Whatsapp_button_template extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Whatsapp_button_template\Models\Whatsapp_button_templateModel();
    }
    
    public function index( $page = false, $ids = false ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        switch ( $page ) {
            case 'update':
                $item = false;
                if( $ids ){
                    $team_id = get_team("id");
                    $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["type" => 2, "ids" => $ids, "team_id" => $team_id]);
                }

                $cloud_accounts = [];
                $available_flows = [];
                if ((int) permission("cloud_api_enabled") === 1) {
                    $team_id = get_team("id");
                    $cloud_accounts = db_fetch("*", TB_ACCOUNTS, [
                        "social_network" => "whatsapp",
                        "category" => "profile",
                        "login_type" => 1,
                        "team_id" => $team_id,
                        "status" => 1
                    ], "created", "ASC");

                    $db = \Config\Database::connect();
                    $available_flows = $db->query(
                        "SELECT f.*, a.name as account_name
                         FROM " . TB_WHATSAPP_FLOWS . " as f
                         LEFT JOIN " . TB_ACCOUNTS . " as a ON a.id = f.account_id
                         WHERE f.team_id = ? AND f.channel = 'cloud_api'
                           AND f.meta_flow_id IS NOT NULL AND f.meta_flow_id <> ''
                         ORDER BY f.changed DESC",
                        [$team_id]
                    )->getResult();
                }

                // Status de submissões Meta (espelho type=66) vinculadas a este template interno (type=2)
                $meta_statuses = [];
                if (!empty($item)) {
                    try {
                        $db = \Config\Database::connect();
                        $meta_statuses = $db->query(
                            "SELECT * FROM " . TB_WHATSAPP_TEMPLATE . "
                             WHERE team_id = ? AND type = ?
                               AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_type')) = '2'
                               AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_ids')) = ?
                             ORDER BY changed DESC
                             LIMIT 25",
                            [$team_id, WA_TEMPLATE_TYPE_META_STATUS, $item->ids]
                        )->getResult();
                    } catch (\Throwable $e) {
                        $meta_statuses = [];
                    }
                }

                $data['content'] = view('Core\Whatsapp_button_template\Views\update', [
                    "result" => $item,
                    "config" => $this->config,
                    "cloud_accounts" => $cloud_accounts,
                    "available_flows" => $available_flows,
                    "meta_statuses" => $meta_statuses,
                ]);
                break;

            default:
                $total = $this->model->get_list(false);

                $datatable = [
                    "total_items" => $total,
                    "per_page" => 30,
                    "current_page" => 1,

                ];

                $data_content = [
                    'total' => $total,
                    'datatable'  => $datatable,
                    'config'  => $this->config,
                ];

                $data['content'] = view('Core\Whatsapp_button_template\Views\content', $data_content );
                break;
        }

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function widget_menu( $params = [] ){
        if ( !permission("whatsapp_button_template") ) return "";
        $result = $params['result'];
        return view('Core\Whatsapp_button_template\Views\widget\menu', ["result" => $result]);
    }

    public function widget_content( $params = [] ){
        if ( !permission("whatsapp_button_template") ) return "";
        $team_id = get_team("id");
        $btn_templates = db_fetch("*", TB_WHATSAPP_TEMPLATE, ["type" => 2, "team_id" => $team_id]);
        return view('Core\Whatsapp_button_template\Views\widget\content', ["result" => $params["result"], "btn_templates" => $btn_templates]);
    }

    public function ajax_list(){
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);
        // Busca (em lote) o último status Meta (type=66) para cada template interno exibido
        $meta_status_by_template_ids = [];
        if (!empty($result)) {
            try {
                $team_id = get_team("id");
                $tplIds = [];
                foreach ($result as $row) {
                    if (!empty($row->ids)) $tplIds[] = (string) $row->ids;
                }
                $tplIds = array_values(array_unique(array_filter($tplIds)));

                if (!empty($tplIds)) {
                    $db = \Config\Database::connect();
                    $placeholders = implode(',', array_fill(0, count($tplIds), '?'));
                    $sql = "SELECT id, changed, data FROM " . TB_WHATSAPP_TEMPLATE . "
                            WHERE team_id = ? AND type = ?
                              AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_type')) = '2'
                              AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_ids')) IN ({$placeholders})
                            ORDER BY changed DESC";
                    $params = array_merge([$team_id, WA_TEMPLATE_TYPE_META_STATUS], $tplIds);
                    $rows = $db->query($sql, $params)->getResultArray();

                    foreach ($rows as $r) {
                        $sd = json_decode($r['data'] ?? '', true) ?: [];
                        $src = (string) ($sd['source_template_ids'] ?? '');
                        if ($src === '') continue;
                        // mantém o mais recente por source_template_ids
                        if (!isset($meta_status_by_template_ids[$src])) {
                            $meta_status_by_template_ids[$src] = $sd;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $meta_status_by_template_ids = [];
            }
        }

        $data = [
            "result" => $result,
            "config" => $this->config,
            "meta_status_by_template_ids" => $meta_status_by_template_ids,
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Whatsapp_button_template\Views\ajax_list', $data)
        ] );
    }

    public function save( $ids = false ){
        $name = post("name");
        $footer = post("footer");
        $title = post("title");
        $medias = post("medias");
        $desc = post("desc");
        $type = post("desc");
        $advance_options = post("advance_options");
        $btn_msg_type = post("btn_msg_type");
        $btn_msg_display_text = post("btn_msg_display_text");
        $btn_msg_link = post("btn_msg_link");
        $btn_msg_copy = post("btn_msg_copy");
        $btn_msg_call = post("btn_msg_call");
        $btn_msg_catalog_phone = post("btn_msg_catalog_phone");
        $btn_msg_catalog_product = post("btn_msg_catalog_product");
        $btn_msg_flow = post("btn_msg_flow");
        $btn_msg_flow_action_data = post("btn_msg_flow_action_data");
        // Meta Oficial (campos extras) - ficam no mesmo JSON para reaproveitar o criador
        $meta_enabled = (int) post("meta_enabled") === 1;
        $meta_base_name = (string) post("meta_base_name");
        $meta_category = strtoupper(trim((string) post("meta_category")));
        $meta_languages = (string) post("meta_languages");
        $meta_header_format = strtoupper(trim((string) post("meta_header_format")));
        $meta_body_example = (string) post("meta_body_example");
        // Variáveis Dinâmicas Locais (novo feature)
        // Tenta via helper post(), fallback para $_POST direto
        $local_variables_raw = (string) post("local_variables");
        if ($local_variables_raw === '' && isset($_POST['local_variables'])) {
            $local_variables_raw = (string) $_POST['local_variables'];
        }
        $local_variables = [];
        if ($local_variables_raw !== '') {
            $parsed = json_decode($local_variables_raw, true);
            if (is_array($parsed)) {
                foreach ($parsed as $lv) {
                    if (!isset($lv['id'])) continue;
                    $local_variables[] = [
                        'id'     => (int)   ($lv['id']     ?? 0),
                        'label'  => (string)($lv['label']  ?? ''),
                        'source' => in_array($lv['source'] ?? 'local', ['local','sheet'], true)
                                        ? $lv['source'] : 'local',
                        'value'  => (string)($lv['value']  ?? ''),
                    ];
                }
            }
        }
        $team_id = get_team("id");


        $shortlink_by = false;
        if(!empty($advance_options) && isset($advance_options['shortlink'])){
            $shortlink_by = shortlink_by(['advance_options' => [ 'shortlink' => $advance_options['shortlink'] ]]);
        }

        validate('null', __('Button template name'), $name);

        if($desc==""){
            ms([
                "status" => "error",
                "message" => __('Main description is required')
            ]);
        }

        if( empty($btn_msg_type) ){
            ms([
                "status" => "error",
                "message" => __('Add at least one button item')
            ]);
        }

        if(count($btn_msg_type) > 10){
            ms([
                "status" => "error",
                "message" => __('Only up to 10 button items allowed')
            ]);
        }

        $btn_template = [];
        $item_button_message = [];

        foreach ($btn_msg_type as $key => $value) {
            $value = trim($value);


            switch ($value) {
                case 1:
                    if( !isset($btn_msg_display_text[$key]) || $btn_msg_display_text[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __("Button %s: Please enter display text") , $key )
                        ]);
                    }

                    $item_button_message[] = [
                        "index" => $key,
                        "quickReplyButton" => [
                            "displayText" => $btn_msg_display_text[$key],
                            "id" => uniqid()
                        ]
                    ];
                    break;

                case 2:
                    if( !isset($btn_msg_display_text[$key]) || $btn_msg_display_text[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __("Button %s: Please enter display text"), $key )
                        ]);
                    }

                    if (!isset($btn_msg_link[$key]) || filter_var($btn_msg_link[$key], FILTER_VALIDATE_URL) === FALSE) {
                        ms([
                            "status" => "error",
                            "message" => sprintf( __( "Button %s: Invalid URL"), $key )
                        ]);
                    }

                    $item_button_message[] = [
                        "index" => $key,
                        "urlButton" => [
                            "displayText" => $btn_msg_display_text[$key],
                            "url" => $btn_msg_link[$key],
                        ]
                    ];
                    break;

                case 3:
                    if( !isset($btn_msg_display_text[$key]) || $btn_msg_display_text[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __( "Button %s: Please enter display text"), $key )
                        ]);
                    }

                    if ( !isset($btn_msg_call[$key]) || !isValidTelephoneNumber($btn_msg_call[$key]) ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __( "Button %s: Invalid phone number") , $key )
                        ]);
                    }

                    if ( $btn_msg_call[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __( "Button %s: Phone number is required") , $key )
                        ]);
                    }

                    $item_button_message[] = [
                        "index" => $key,
                        "callButton" => [
                            "displayText" => $btn_msg_display_text[$key],
                            "phoneNumber" => $btn_msg_call[$key],
                        ]
                    ];
                    break;
                    
                case 4:
                    if( !isset($btn_msg_display_text[$key]) || $btn_msg_display_text[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __("Button %s: Please enter display text"), $key )
                        ]);
                    }
                
                    $item_button_message[] = [
                        "index" => $key,
                        "urlButton" => [
                            "displayText" => $btn_msg_display_text[$key],
                            "url" => "https://www.whatsapp.com/otp/code/?otp_type=COPY_CODE&code=" . $btn_msg_copy[$key],
                            "disabled" => false
                        ]
                    ];
                    break;

                case 5:
                    if( !isset($btn_msg_display_text[$key]) || $btn_msg_display_text[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __("Button %s: Please enter display text"), $key )
                        ]);
                    }

                    if ( !isset($btn_msg_catalog_phone[$key]) || !isValidTelephoneNumber($btn_msg_catalog_phone[$key]) ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __("Button %s: Invalid phone number"), $key )
                        ]);
                    }

                    if ( !isset($btn_msg_catalog_product[$key]) || trim($btn_msg_catalog_product[$key]) == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __("Button %s: Catalog product ID is required"), $key )
                        ]);
                    }

                    $item_button_message[] = [
                        "index" => $key,
                        "catalogButton" => [
                            "displayText" => $btn_msg_display_text[$key],
                            "businessPhoneNumber" => $btn_msg_catalog_phone[$key],
                            "catalogProductId" => trim($btn_msg_catalog_product[$key])
                        ]
                    ];
                    break;

                case 6:
                    if (!$meta_enabled) {
                        ms([
                            "status" => "error",
                            "message" => __("Botão Flow está disponível apenas no modo Oficial (Meta). Ative o modo Oficial para continuar.")
                        ]);
                    }

                    if (!isset($btn_msg_display_text[$key]) || trim($btn_msg_display_text[$key]) == "") {
                        ms([
                            "status" => "error",
                            "message" => sprintf(__("Button %s: Please enter display text"), $key)
                        ]);
                    }

                    $selected_flow_ids = trim((string) ($btn_msg_flow[$key] ?? ""));
                    if ($selected_flow_ids === "") {
                        ms([
                            "status" => "error",
                            "message" => sprintf(__("Button %s: Select a Flow"), $key)
                        ]);
                    }

                    $selected_flow = $this->get_cloud_flow_by_ids($selected_flow_ids, $team_id);
                    if (empty($selected_flow)) {
                        ms([
                            "status" => "error",
                            "message" => sprintf(__("Button %s: The selected Flow was not found"), $key)
                        ]);
                    }

                    $flow_action_data = trim((string) ($btn_msg_flow_action_data[$key] ?? ""));
                    if ($flow_action_data !== '' && !$this->is_valid_json_object($flow_action_data)) {
                        ms([
                            "status" => "error",
                            "message" => sprintf(__("Button %s: Initial Flow JSON must be a valid object"), $key)
                        ]);
                    }

                    $item_button_message[] = [
                        "index" => $key,
                        "flowButton" => [
                            "displayText" => $btn_msg_display_text[$key],
                            "flowIds" => $selected_flow_ids,
                            "flowName" => (string) (($selected_flow->slug ?? '') !== '' ? $selected_flow->slug : ($selected_flow->name ?? '')),
                            "metaFlowId" => (string) ($selected_flow->meta_flow_id ?? ''),
                            "flowActionData" => $flow_action_data,
                        ]
                    ];
                    break;

                
                default:
                    ms([
                        "status" => "error",
                        "message" => __('The type button item incorrect')
                    ]);
                    break;
            }

            if($value == ""){
                ms([
                    "status" => "error",
                    "message" => __('The option name is required')
                ]);
            }
        }

        $btn_template = [
            "templateButtons" => $item_button_message
        ];

        $desc = shortlink($desc, $shortlink_by);
        $footer = shortlink($footer, $shortlink_by);
        $title = shortlink($title, $shortlink_by);

        if($footer != ""){
            $btn_template["footer"] = $footer;
            //$btn_template["viewOnce"] = true;
        }
        
        if($title != "Botão"){
            $btn_template["title"] = $title;
            //$btn_template["viewOnce"] = true;
        }

        if(!empty($medias) && permission("whatsapp_send_media")){
            $btn_template["caption"] = $desc;
            $btn_template["image"] = [
                "url" => get_file_url($medias[0])
            ];
        }else{
            $btn_template["text"] = $desc;
        }

        // Variáveis Dinâmicas Locais — sempre persiste (mesmo vazio)
        $btn_template["local_variables"] = $local_variables;

        // Auto-generate meta_body_example from local_variables if not filled by user
        if ($meta_body_example === '' && !empty($local_variables)) {
            $example_parts = [];
            foreach ($local_variables as $lv) {
                if ($lv['source'] === 'sheet' && $lv['value'] !== '') {
                    $example_parts[] = $lv['value']; // keep sheet placeholder as example
                } elseif ($lv['value'] !== '') {
                    $example_parts[] = $lv['value']; // local value as example
                } else {
                    $example_parts[] = 'exemplo' . $lv['id'];
                }
            }
            $meta_body_example = implode('|', $example_parts);
        }

        // Persistência de configurações do modo Oficial (Meta) no mesmo template
        $btn_template["meta_official"] = [
            "enabled" => $meta_enabled,
            "base_name" => $meta_base_name,
            "category" => in_array($meta_category, ["MARKETING", "UTILITY"], true) ? $meta_category : "MARKETING",
            "languages" => $meta_languages,
            "header_format" => in_array($meta_header_format, ["NONE", "TEXT", "IMAGE"], true) ? $meta_header_format : "TEXT",
            "body_example" => $meta_body_example,
        ];

        $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["ids" => $ids, "team_id" => $team_id]);
        if( empty($item) ){
            $data = [
                "ids" => ids(),
                "team_id" => $team_id,
                "type" => 2,
                "name" => $name,
                "data" => json_encode($btn_template),
                "changed" => time(),
                "created" => time(),
            ];
            
            db_insert( TB_WHATSAPP_TEMPLATE, $data );
        }else{
            $data = [
                "name" => $name,
                "data" => json_encode($btn_template),
                "changed" => time(),
            ];
            


            db_update( TB_WHATSAPP_TEMPLATE, $data, ["ids" => $ids] );

        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    /**
     * Submete um template de botões (type=2) para análise como Template Oficial (Meta).
     * - Reaproveita o conteúdo do criador atual.
     * - Se o template não estiver conforme (botões/tipos/variáveis/exemplos), bloqueia envio.
     */
    public function meta_submit($ids = false)
    {
        $team_id = get_team("id");
        $logFile = rtrim(WRITEPATH, '/\\') . '/logs/meta_submit.log';
        $log = function (array $data) use ($logFile) {
            @file_put_contents(
                $logFile,
                date('Y-m-d H:i:s') . ' ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
                FILE_APPEND
            );
        };

        /**
         * Importante:
         * Este módulo não carrega automaticamente helpers de outros módulos.
         * Como o fluxo "Oficial (Meta)" depende de helpers em `inc/core/Whatsapp/Helpers`,
         * garantimos o require_once aqui para evitar erros fatais silenciosos.
         */
        try {
            if (!function_exists("meta_sanitizar_nome_template") || !function_exists("meta_upload_handle_aprovacao_header")) {
                $metaHelper = realpath(ROOTPATH . 'inc/core/Whatsapp/Helpers/meta_official_helper.php');
                if ($metaHelper) require_once $metaHelper;
            }
            // Whatsapp_helper.php não é obrigatório aqui, pois o fluxo oficial usa helpers do meta_official_helper.php
        } catch (\Throwable $e) {
            $log(["event" => "fatal", "reason" => "helper_load_failed", "message" => $e->getMessage()]);
            ms(["status" => "error", "message" => __("Falha ao carregar helpers do WhatsApp. Tente novamente.")]);
        }
        $log([
            "event" => "meta_submit_start",
            "template_ids" => $ids,
            "post_keys" => array_keys((array) $_POST),
            "func_exists" => [
                "meta_sanitizar_nome_template" => function_exists("meta_sanitizar_nome_template"),
                "meta_parsear_idiomas_csv" => function_exists("meta_parsear_idiomas_csv"),
                "meta_upload_handle_aprovacao_header" => function_exists("meta_upload_handle_aprovacao_header"),
                "meta_upload_media_para_envio" => function_exists("meta_upload_media_para_envio"),
                "meta_criar_template_na_meta" => function_exists("meta_criar_template_na_meta"),
            ],
        ]);

        if ((int) permission("cloud_api_enabled") !== 1) {
            $log(["event" => "blocked", "reason" => "cloud_api_disabled"]);
            ms(["status" => "error", "message" => __("Cloud API não habilitada para este plano.")]);
        }

        $account_ids = trim((string) post("account_ids"));
        if ($account_ids === "") {
            $log(["event" => "blocked", "reason" => "missing_account_ids"]);
            ms(["status" => "error", "message" => __("Selecione uma conta Cloud API para submeter o template.")]);
        }
        $force_new_version = (int) post("meta_force") === 1;

        $account = db_get("*", TB_ACCOUNTS, ["ids" => $account_ids, "team_id" => $team_id, "login_type" => 1, "status" => 1]);
        if (empty($account)) {
            $log(["event" => "blocked", "reason" => "account_not_found", "account_ids" => $account_ids]);
            ms(["status" => "error", "message" => __("Conta Cloud API não encontrada ou desconectada.")]);
        }

        $tpl = db_get("*", TB_WHATSAPP_TEMPLATE, ["type" => 2, "ids" => $ids, "team_id" => $team_id]);
        if (empty($tpl)) {
            $log(["event" => "blocked", "reason" => "template_not_found", "template_ids" => $ids]);
            ms(["status" => "error", "message" => __("Template de botões não encontrado.")]);
        }

        $tplData = json_decode($tpl->data, true) ?: [];
        $metaCfg = is_array($tplData["meta_official"] ?? null) ? $tplData["meta_official"] : [];
        $enabled = (int) post("meta_enabled") === 1 ? true : (bool) ($metaCfg["enabled"] ?? false);
        if (!$enabled) {
            $log(["event" => "blocked", "reason" => "meta_disabled"]);
            ms(["status" => "error", "message" => __("Ative o modo 'Oficial (Meta)' e salve antes de submeter.")]);
        }

        $baseName = trim((string) post("meta_base_name"));
        if ($baseName === "") $baseName = (string) ($metaCfg["base_name"] ?? "");
        if ($baseName === "") $baseName = (string) $tpl->name;
        $baseName = meta_sanitizar_nome_template($baseName);
        if ($baseName === "") {
            $log(["event" => "blocked", "reason" => "invalid_base_name", "base_name" => $baseName]);
            ms(["status" => "error", "message" => __("Nome base do template (Meta) inválido.")]);
        }

        // Evita duplicação acidental: se já existe versão PENDING recente para este template+conta, bloqueia (a não ser que force)
        if (!$force_new_version) {
            try {
                $db = \Config\Database::connect();
                $recent = $db->query(
                    "SELECT name, changed, data FROM " . TB_WHATSAPP_TEMPLATE . "
                     WHERE team_id = ? AND type = ?
                       AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.account_ids')) = ?
                       AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_type')) = '2'
                       AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_ids')) = ?
                       AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.status')) IN ('PENDING','PAUSED')
                       AND changed >= ?
                     ORDER BY changed DESC
                     LIMIT 1",
                    [$team_id, WA_TEMPLATE_TYPE_META_STATUS, $account_ids, $tpl->ids, (time() - 1800)]
                )->getRow();

                if (!empty($recent)) {
                    $log([
                        "event" => "blocked",
                        "reason" => "pending_exists",
                        "pending_name" => $recent->name ?? null,
                        "changed" => $recent->changed ?? null,
                    ]);
                    ms([
                        "status" => "warning",
                        "message" => __("Já existe uma versão em análise na Meta (PENDING). Aguarde aprovação ou marque 'Forçar nova versão'."),
                    ]);
                }
            } catch (\Throwable $e) {
                // se falhar a checagem, não bloqueia
            }
        }

        $category = strtoupper(trim((string) post("meta_category")));
        if ($category === "") $category = (string) ($metaCfg["category"] ?? "MARKETING");
        if (!in_array($category, ["MARKETING", "UTILITY"], true)) {
            $log(["event" => "blocked", "reason" => "invalid_category", "category" => $category]);
            ms(["status" => "error", "message" => __("Categoria inválida. Use MARKETING ou UTILITY.")]);
        }

        $languagesCsv = trim((string) post("meta_languages"));
        if ($languagesCsv === "") $languagesCsv = (string) ($metaCfg["languages"] ?? "pt_BR");
        $languages = meta_parsear_idiomas_csv($languagesCsv);

        $headerFormat = strtoupper(trim((string) post("meta_header_format")));
        if ($headerFormat === "") $headerFormat = (string) ($metaCfg["header_format"] ?? "TEXT");
        if (!in_array($headerFormat, ["NONE", "TEXT", "IMAGE"], true)) $headerFormat = "TEXT";

        // BODY text
        $bodyText = (string) ($tplData["caption"] ?? $tplData["text"] ?? "");
        $bodyText = trim($bodyText);
        if ($bodyText === "") {
            $log(["event" => "blocked", "reason" => "missing_body_text"]);
            ms(["status" => "error", "message" => __("Descrição principal (Body) é obrigatória para submeter à Meta.")]);
        }
        // Regra da Meta: variável não pode estar no início/fim
        if (preg_match('/^\s*\{\{\d+\}\}/', $bodyText) || preg_match('/\{\{\d+\}\}\s*$/', $bodyText)) {
            $log(["event" => "blocked", "reason" => "placeholder_at_edges", "body" => $bodyText]);
            ms(["status" => "error", "message" => __("A Meta não permite variáveis no início ou no fim do body. Ajuste o texto.")]);
        }

        // Example de variáveis
        $exampleRaw = trim((string) post("meta_body_example"));
        if ($exampleRaw === "") $exampleRaw = (string) ($metaCfg["body_example"] ?? "");
        
        $hasPlaceholders = preg_match('/\{\{\d+\}\}/', $bodyText) === 1;
        $exampleValues = [];
        if ($hasPlaceholders) {
            // AUTO-GENERATE example from local_variables if exampleRaw empty
            if ($exampleRaw === "" && isset($tplData["local_variables"]) && is_array($tplData["local_variables"])) {
                $lv_vals = [];
                foreach ($tplData["local_variables"] as $lv) {
                    if (isset($lv["value"])) {
                        $val = trim($lv["value"]);
                        if (preg_match('/^%[^%]+%$/', $val)) {
                            $lv_vals[] = "Exemplo";
                        } else {
                            $lv_vals[] = $val;
                        }
                    }
                }
                if (!empty($lv_vals)) {
                    $exampleRaw = implode("|", $lv_vals);
                }
            }

            if ($exampleRaw === "") {
                $log(["event" => "blocked", "reason" => "missing_body_example"]);
                ms(["status" => "error", "message" => __("Informe o exemplo de variáveis (ex: João|123) para submeter à Meta.")]);
            }
            $exampleValues = array_values(array_filter(array_map("trim", explode("|", $exampleRaw))));
            if (empty($exampleValues)) {
                ms(["status" => "error", "message" => __("Exemplo de variáveis inválido. Use 'valor1|valor2|...'.")]);
            }
        }

        // HEADER: TEXT usa o "Title Button" do módulo; IMAGE usa a "Main image" do módulo
        $headerText = trim((string) ($tplData["title"] ?? ""));
        $headerHandle = null;
        $defaultHeaderMedia = null; // media_id para envio

        if ($headerFormat === "IMAGE") {
            $imgUrl = (string) (($tplData["image"]["url"] ?? "") ?: "");
            if ($imgUrl === "") {
                $log(["event" => "blocked", "reason" => "missing_header_image_url"]);
                ms(["status" => "error", "message" => __("Para HEADER=IMAGE, selecione uma imagem principal no template e salve.")]);
            }

            $diskPath = meta_resolver_caminho_disco_por_url_publica($imgUrl);
            if (!$diskPath) {
                $log(["event" => "blocked", "reason" => "header_image_not_found_on_disk", "img_url" => $imgUrl]);
                ms(["status" => "error", "message" => __("Não encontrei o arquivo da imagem no servidor (writable/uploads). Salve novamente a imagem.")]);
            }

            $accData = json_decode($account->data, true) ?: [];
            $token = (string) ($accData["token"] ?? "");
            if ($token === "") {
                $log(["event" => "blocked", "reason" => "missing_cloud_token"]);
                ms(["status" => "error", "message" => __("Token da conta Cloud API ausente.")]);
            }

            $mime = meta_guess_mime_por_extensao($diskPath);
            // Upload para envio (media_id)
            $upSend = meta_upload_media_para_envio($account, $diskPath, $mime);
            if (($upSend["status"] ?? "") !== "success") {
                $log(["event" => "blocked", "reason" => "upload_media_to_meta_failed", "message" => $upSend["message"] ?? null]);
                ms(["status" => "error", "message" => __("Falha no upload da mídia (envio): ") . ($upSend["message"] ?? "erro")]);
            }
            $defaultHeaderMedia = ["id" => $upSend["media_id"]];

            // Upload para aprovação (handle h)
            $upApproval = meta_upload_handle_aprovacao_header($token, $diskPath, $mime);
            if (($upApproval["status"] ?? "") !== "success") {
                $log(["event" => "blocked", "reason" => "upload_approval_handle_failed", "message" => $upApproval["message"] ?? null]);
                ms(["status" => "error", "message" => __("Falha no upload da mídia (aprovação): ") . ($upApproval["message"] ?? "erro")]);
            }
            $headerHandle = $upApproval["h"] ?? null;
            if (!$headerHandle) {
                ms(["status" => "error", "message" => __("Handle de aprovação não retornado pela Meta.")]);
            }
        }

        // Buttons: permitir QUICK_REPLY, URL, PHONE_NUMBER e FLOW
        $buttonsInternal = $tplData["templateButtons"] ?? [];
        if (empty($buttonsInternal) || !is_array($buttonsInternal)) {
            $log(["event" => "blocked", "reason" => "missing_buttons"]);
            ms(["status" => "error", "message" => __("Adicione ao menos 1 botão para submeter como template oficial.")]);
        }
        if (count($buttonsInternal) > 3) {
            $log(["event" => "blocked", "reason" => "too_many_buttons", "count" => count($buttonsInternal)]);
            ms(["status" => "error", "message" => __("A Meta exige limite de botões. Ajuste para até 3 botões antes de submeter.")]);
        }

        $metaButtons = [];
        foreach ($buttonsInternal as $btn) {
            if (!is_array($btn)) continue;
            if (isset($btn["quickReplyButton"])) {
                $txt = trim((string) ($btn["quickReplyButton"]["displayText"] ?? ""));
                if ($txt === "") ms(["status" => "error", "message" => __("Botão quick reply sem texto.")]);
                $metaButtons[] = ["type" => "QUICK_REPLY", "text" => mb_substr($txt, 0, 20)];
                continue;
            }
            if (isset($btn["urlButton"])) {
                $txt = trim((string) ($btn["urlButton"]["displayText"] ?? ""));
                $url = trim((string) ($btn["urlButton"]["url"] ?? ""));
                if ($txt === "" || $url === "") ms(["status" => "error", "message" => __("Botão URL inválido (texto/URL).")]);
                $metaButtons[] = ["type" => "URL", "text" => mb_substr($txt, 0, 20), "url" => $url];
                continue;
            }
            if (isset($btn["callButton"])) {
                $txt = trim((string) ($btn["callButton"]["displayText"] ?? ""));
                $phone = trim((string) ($btn["callButton"]["phoneNumber"] ?? ""));
                if ($txt === "" || $phone === "") ms(["status" => "error", "message" => __("Botão de chamada inválido (texto/telefone).")]);
                $metaButtons[] = ["type" => "PHONE_NUMBER", "text" => mb_substr($txt, 0, 20), "phone_number" => $phone];
                continue;
            }
            if (isset($btn["flowButton"])) {
                $flowButton = is_array($btn["flowButton"]) ? $btn["flowButton"] : [];
                $txt = trim((string) ($flowButton["displayText"] ?? ""));
                if ($txt === "") {
                    ms(["status" => "error", "message" => __("Botão Flow sem texto.")]);
                }

                $selectedFlow = null;
                $selectedFlowIds = trim((string) ($flowButton["flowIds"] ?? ""));
                if ($selectedFlowIds !== "") {
                    $selectedFlow = $this->get_cloud_flow_by_ids($selectedFlowIds, $team_id);
                }

                $metaFlowId = trim((string) ($flowButton["metaFlowId"] ?? ""));
                if ($metaFlowId === "" && !empty($selectedFlow)) {
                    $metaFlowId = trim((string) get_data($selectedFlow, "meta_flow_id", "text"));
                }

                if ($metaFlowId === "") {
                    ms(["status" => "error", "message" => __("Publique o Flow na Meta antes de submeter o template com botão Flow.")]);
                }

                $flowAccountIds = trim((string) ($selectedFlow->account_ids ?? ''));
                if ($flowAccountIds !== '' && trim((string) $account_ids) !== '' && $flowAccountIds !== trim((string) $account_ids)) {
                    ms(["status" => "error", "message" => __("O Flow selecionado pertence a outra conta Cloud API. Escolha um Flow da mesma conta usada na submissão.")]);
                }

                $metaButtons[] = [
                    "type" => "FLOW",
                    "text" => mb_substr($txt, 0, 20),
                    "flow_id" => $metaFlowId,
                ];
                continue;
            }

            ms(["status" => "error", "message" => __("Este tipo de botão não é suportado em Template Oficial (Meta). Remova/ajuste e tente novamente.")]);
        }

        // Nome versionado (sempre novo)
        $versionedName = meta_sanitizar_nome_template($baseName . "_" . date("ymd_His"));

        $created = 0;
        $errors = 0;
        foreach ($languages as $lang) {
            $lang = trim((string) $lang);
            if ($lang === "") continue;

            $components = [];
            if ($headerFormat === "TEXT" && $headerText !== "") {
                $components[] = ["type" => "HEADER", "format" => "TEXT", "text" => $headerText];
            } elseif ($headerFormat === "IMAGE") {
                $comp = ["type" => "HEADER", "format" => "IMAGE"];
                $comp["example"] = ["header_handle" => [$headerHandle]];
                $components[] = $comp;
            }

            $bodyComp = ["type" => "BODY", "text" => $bodyText];
            if ($hasPlaceholders && !empty($exampleValues)) {
                $bodyComp["example"] = ["body_text" => [array_values($exampleValues)]];
            }
            $components[] = $bodyComp;

            $footerText = trim((string) ($tplData["footer"] ?? ""));
            if ($footerText !== "") {
                $components[] = ["type" => "FOOTER", "text" => $footerText];
            }

            $components[] = ["type" => "BUTTONS", "buttons" => $metaButtons];

            $payload = [
                "name" => $versionedName,
                "language" => $lang,
                "category" => $category,
                "components" => $components,
            ];

            $res = meta_criar_template_na_meta($account, $payload);
            $log([
                "event" => "meta_create_template",
                "name" => $versionedName,
                "lang" => $lang,
                "category" => $category,
                "meta_result_status" => $res["status"] ?? null,
                "meta_result_message" => $res["message"] ?? null,
                "meta_result_id" => $res["meta_template_id"] ?? ($res["data"]["id"] ?? null),
            ]);
            if (($res["status"] ?? "") === "success") {
                $created++;
                $metaId = $res["meta_template_id"] ?? ($res["data"]["id"] ?? "");
                meta_upsert_status_template($team_id, $versionedName, $lang, $account_ids, [
                    "meta_id" => $metaId ?: "",
                    "name" => $versionedName,
                    "language" => $lang,
                    "category" => $category,
                    "components" => $components,
                    "account_ids" => $account_ids,
                    "waba_id" => (json_decode($account->data, true)["waba_id"] ?? null),
                    "status" => "PENDING",
                    "default_header_media" => $defaultHeaderMedia,
                    "source_template_type" => 2,
                    "source_template_ids" => $tpl->ids,
                ]);
            } else {
                $errors++;
                meta_upsert_status_template($team_id, $versionedName, $lang, $account_ids, [
                    "meta_id" => "",
                    "name" => $versionedName,
                    "language" => $lang,
                    "category" => $category,
                    "components" => $components,
                    "account_ids" => $account_ids,
                    "waba_id" => (json_decode($account->data, true)["waba_id"] ?? null),
                    "status" => "ERROR",
                    "last_error" => $res["message"] ?? "Erro ao criar template na Meta",
                    "default_header_media" => $defaultHeaderMedia,
                    "source_template_type" => 2,
                    "source_template_ids" => $tpl->ids,
                ]);
            }
        }

        // Atualiza o próprio template com o último submit (histórico simples)
        $metaCfg["enabled"] = true;
        $metaCfg["base_name"] = $baseName;
        $metaCfg["category"] = $category;
        $metaCfg["languages"] = $languagesCsv;
        $metaCfg["header_format"] = $headerFormat;
        $metaCfg["body_example"] = $exampleRaw;
        $metaCfg["last_account_ids"] = $account_ids;
        $metaCfg["last_submitted_name"] = $versionedName;
        $metaCfg["last_submitted_at"] = time();
        $tplData["meta_official"] = $metaCfg;
        db_update(TB_WHATSAPP_TEMPLATE, ["data" => json_encode($tplData), "changed" => time()], ["id" => $tpl->id]);

        if ($created > 0 && $errors === 0) {
            $log(["event" => "meta_submit_done", "created" => $created, "errors" => $errors, "name" => $versionedName]);
            ms(["status" => "success", "message" => __("Submetido para análise na Meta. Templates criados: ") . $created]);
        }
        if ($created > 0 && $errors > 0) {
            $log(["event" => "meta_submit_done", "created" => $created, "errors" => $errors, "name" => $versionedName]);
            ms(["status" => "warning", "message" => __("Submetido parcialmente. Criados: ") . $created . __(" | Erros: ") . $errors]);
        }

        $log(["event" => "meta_submit_done", "created" => $created, "errors" => $errors, "name" => $versionedName]);
        ms(["status" => "error", "message" => __("Falha ao submeter na Meta. Verifique os dados e tente novamente.")]);
    }

    protected function get_cloud_flow_by_ids($flow_ids, $team_id)
    {
        $flow_ids = trim((string) $flow_ids);
        if ($flow_ids === '') {
            return null;
        }

        return db_get("*", TB_WHATSAPP_FLOWS, [
            "ids" => $flow_ids,
            "team_id" => $team_id,
            "channel" => "cloud_api",
        ]);
    }

    protected function is_valid_json_object($json)
    {
        $json = trim((string) $json);
        if ($json === '') {
            return true;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return false;
        }

        return array_keys($decoded) !== range(0, count($decoded) - 1);
    }

    public function delete(){
        $team_id = get_team("id");
        $ids = post('id');

        if( empty($ids) ){
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if( is_array($ids) ){
            foreach ($ids as $id) {
                db_delete(TB_WHATSAPP_TEMPLATE, ['ids' => $id, "team_id" => $team_id]);
            }
        }
        elseif( is_string($ids) )
        {
            db_delete(TB_WHATSAPP_TEMPLATE, ['ids' => $ids, "team_id" => $team_id]);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }
}
