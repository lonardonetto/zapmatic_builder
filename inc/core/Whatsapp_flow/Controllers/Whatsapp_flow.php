<?php
namespace Core\Whatsapp_flow\Controllers;

class Whatsapp_flow extends \CodeIgniter\Controller
{
    protected $config;
    protected $model;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Whatsapp_flow\Models\Whatsapp_flowModel();
    }

    public function index($page = false, $ids = false)
    {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        switch ($page) {
            case 'update':
                $team_id = get_team("id");
                $item = false;
                if ($ids) {
                    $item = db_get("*", TB_WHATSAPP_FLOWS, ["ids" => $ids, "team_id" => $team_id]);
                }

                $cloud_accounts = $this->get_cloud_accounts($team_id);
                $endpoints = $this->get_endpoints($team_id);
                $active_endpoint = $this->resolve_active_endpoint($item, $endpoints);
                $flow_assets_total = 0;

                if (!empty($item)) {
                    $db = \Config\Database::connect();
                    $builder = $db->table(TB_WHATSAPP_FLOW_ASSETS);
                    $builder->where("team_id", $team_id);
                    $builder->where("flow_id", $item->id);
                    $flow_assets_total = (int) $builder->countAllResults();
                }

                $data['content'] = view('Core\Whatsapp_flow\Views\update', [
                    "result" => $item,
                    "config" => $this->config,
                    "cloud_accounts" => $cloud_accounts,
                    "endpoints" => $endpoints,
                    "status_local_options" => $this->get_status_local_options(),
                    "flow_categories" => $this->get_flow_categories(),
                    "flow_assets_total" => $flow_assets_total,
                    "active_endpoint" => $active_endpoint,
                    "flow_server_url" => $this->get_flow_server_base_url(),
                ]);
                break;

            default:
                $total = $this->model->get_list(false);

                $datatable = [
                    "total_items" => $total,
                    "per_page" => 30,
                    "current_page" => 1,
                ];

                $data['content'] = view('Core\Whatsapp_flow\Views\content', [
                    'total' => $total,
                    'datatable' => $datatable,
                    'config' => $this->config,
                ]);
                break;
        }

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function ajax_list()
    {
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);

        ms([
            "total_items" => $total_items,
            "data" => view('Core\Whatsapp_flow\Views\ajax_list', [
                "result" => $result,
                "config" => $this->config,
            ])
        ]);
    }

    public function widget_menu($params = [])
    {
        if (!permission("whatsapp_flow")) {
            return "";
        }

        $account = $params["account"] ?? null;
        if (empty($account) || (int) get_data($account, "login_type") !== 1) {
            return "";
        }

        $result = $params["result"] ?? false;
        return view('Core\Whatsapp_flow\Views\widget\menu', [
            "result" => $result,
        ]);
    }

    public function widget_content($params = [])
    {
        if (!permission("whatsapp_flow")) {
            return "";
        }

        $account = $params["account"] ?? null;
        if (empty($account) || (int) get_data($account, "login_type") !== 1) {
            return "";
        }

        $team_id = get_team("id");
        $flows = $this->get_sendable_flows($team_id, $account);

        return view('Core\Whatsapp_flow\Views\widget\content', [
            "result" => $params["result"] ?? false,
            "account" => $account,
            "flows" => $flows,
        ]);
    }

    public function save($ids = false)
    {
        try {
            $this->assert_flow_permission();
            $this->persist_flow_from_request($ids);

            ms([
                "status" => "success",
                "message" => __('Success')
            ]);
        } catch (\Throwable $e) {
            ms([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function meta_push_draft($ids = false)
    {
        try {
            $this->assert_flow_permission();
            $context = $this->resolve_flow_context($ids, !empty($_POST));
            $flow = $this->sync_meta_draft($context);

            ms([
                "status" => "success",
                "message" => __('Flow draft synchronized with Meta')
            ]);
        } catch (\Throwable $e) {
            ms([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function meta_publish($ids = false)
    {
        try {
            $this->assert_flow_permission();
            $context = $this->resolve_flow_context($ids, !empty($_POST));
            $flow = $this->sync_meta_draft($context);
            $account = $context["account"];
            $meta_flow_id = trim((string) get_data($flow, "meta_flow_id", "text"));

            if ($meta_flow_id === "") {
                throw new \Exception(__('Meta Flow ID is missing after draft synchronization'));
            }

            if (strtoupper((string) get_data($flow, "status_meta", "text")) === "PUBLISHED") {
                ms([
                    "status" => "success",
                    "message" => __('This Flow is already published on Meta')
                ]);
            }

            $publish = $this->meta_graph_request($account, $meta_flow_id . "/publish", "POST");
            $this->log_meta_event($flow, $account, "meta_publish", [
                "flow_id" => $meta_flow_id,
            ], $publish);

            if ($publish["status"] !== "success") {
                throw new \Exception($publish["message"]);
            }

            $flow = $this->sync_flow_from_meta($flow, $account);

            ms([
                "status" => "success",
                "message" => __('Flow published on Meta')
            ]);
        } catch (\Throwable $e) {
            ms([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function meta_sync($ids = false)
    {
        try {
            $this->assert_flow_permission();
            $context = $this->resolve_flow_context($ids, !empty($_POST));
            $flow = $context["flow"];
            $account = $context["account"];

            if (trim((string) get_data($flow, "meta_flow_id", "text")) === "") {
                throw new \Exception(__('Create or sync the Meta draft first before refreshing its status'));
            }

            $flow = $this->sync_flow_from_meta($flow, $account);

            ms([
                "status" => "success",
                "message" => __('Meta status refreshed successfully')
            ]);
        } catch (\Throwable $e) {
            ms([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function meta_pull_account($ids = false)
    {
        try {
            $this->assert_flow_permission();
            $context = $this->resolve_flow_context($ids, false);
            $summary = $this->pull_meta_flows_for_account($context["account"]);

            if ((int) $summary["synced"] === 0) {
                ms([
                    "status" => "success",
                    "message" => __('Meta did not return any Flow for this account')
                ]);
            }

            ms([
                "status" => "success",
                "message" => sprintf(
                    __('Meta synchronization finished: %d Flow(s), %d new, %d updated'),
                    (int) $summary["synced"],
                    (int) $summary["created"],
                    (int) $summary["updated"]
                )
            ]);
        } catch (\Throwable $e) {
            ms([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function endpoint_sync($ids = false)
    {
        try {
            $this->assert_flow_permission();
            $context = $this->resolve_flow_context($ids, false);
            $endpoint = $this->ensure_endpoint_record($context["account"], $context["flow"]);
            $keys = $this->ensure_endpoint_keypair($endpoint);

            $upload = $this->sync_endpoint_public_key_on_meta($endpoint, $context["account"], $keys["public_key"]);
            if ($upload["status"] !== "success") {
                throw new \Exception($upload["message"]);
            }

            $endpoint = $this->refresh_endpoint_status_on_meta($endpoint, $context["account"], $keys["public_key"]);

            ms([
                "status" => "success",
                "message" => __('Flow endpoint prepared and public key synchronized with Meta')
            ]);
        } catch (\Throwable $e) {
            ms([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function endpoint_refresh($ids = false)
    {
        try {
            $this->assert_flow_permission();
            $context = $this->resolve_flow_context($ids, false);
            $endpoint = $this->ensure_endpoint_record($context["account"], $context["flow"]);
            $public_key = null;

            $private_key_path = trim((string) get_data($endpoint, "private_key_path", "text"));
            if ($private_key_path !== "" && file_exists($private_key_path)) {
                $public_key = $this->derive_public_key_from_private((string) file_get_contents($private_key_path));
            }

            $this->refresh_endpoint_status_on_meta($endpoint, $context["account"], $public_key);

            ms([
                "status" => "success",
                "message" => __('Flow endpoint status refreshed successfully')
            ]);
        } catch (\Throwable $e) {
            ms([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function delete()
    {
        $team_id = get_team("id");
        $ids = post('id');

        if (empty($ids)) {
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if (is_array($ids)) {
            foreach ($ids as $id) {
                db_delete(TB_WHATSAPP_FLOWS, ['ids' => $id, "team_id" => $team_id]);
            }
        } elseif (is_string($ids)) {
            db_delete(TB_WHATSAPP_FLOWS, ['ids' => $ids, "team_id" => $team_id]);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    protected function get_cloud_accounts($team_id)
    {
        return db_fetch("*", TB_ACCOUNTS, [
            "social_network" => "whatsapp",
            "category" => "profile",
            "login_type" => 1,
            "team_id" => $team_id,
            "status" => 1
        ], "id", "DESC");
    }

    protected function get_endpoints($team_id)
    {
        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_FLOW_ENDPOINTS . " as e");
        $builder->select("e.*, a.name as account_name");
        $builder->join(TB_ACCOUNTS . " as a", "a.id = e.account_id", "left");
        $builder->where("e.team_id", $team_id);
        $builder->orderBy("e.created", "DESC");
        $query = $builder->get();

        return $query ? $query->getResult() : [];
    }

    protected function resolve_active_endpoint($flow, $endpoints)
    {
        if (empty($flow) || empty($endpoints)) {
            return null;
        }

        $selected_endpoint_id = (int) get_data($flow, "endpoint_id");
        if ($selected_endpoint_id > 0) {
            foreach ($endpoints as $endpoint) {
                if ((int) get_data($endpoint, "id") === $selected_endpoint_id) {
                    return $endpoint;
                }
            }
        }

        $account_id = (int) get_data($flow, "account_id");
        if ($account_id > 0) {
            foreach ($endpoints as $endpoint) {
                if ((int) get_data($endpoint, "account_id") === $account_id) {
                    return $endpoint;
                }
            }
        }

        return null;
    }

    protected function get_flow_server_base_url()
    {
        $server_url = trim((string) get_option("whatsapp_server_url", ""));
        if ($server_url === "") {
            $server_url = base_url();
        }

        return rtrim($server_url, "/");
    }

    protected function build_flow_endpoint_uri($endpoint_ids)
    {
        return $this->get_flow_server_base_url() . "/flow_endpoint/" . rawurlencode((string) $endpoint_ids);
    }

    protected function get_sendable_flows($team_id, $account)
    {
        $account_id = (int) get_data($account, "id");
        $account_ids = trim((string) get_data($account, "ids", "text"));

        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_FLOWS . " as f");
        $builder->select("f.*");
        $builder->where("f.team_id", $team_id);
        $builder->where("f.channel", "cloud_api");
        $builder->where("f.status_local !=", WA_FLOW_STATUS_LOCAL_ARCHIVED);

        $builder->groupStart();
        $builder->where("f.account_id", $account_id);
        if ($account_ids !== "") {
            $builder->orWhere("f.account_ids", $account_ids);
        }
        $builder->groupEnd();

        $builder->where("(TRIM(COALESCE(f.flow_json, '')) != '' OR TRIM(COALESCE(f.meta_flow_id, '')) != '')", null, false);
        $builder->orderBy("CASE WHEN UPPER(COALESCE(f.status_meta, '')) = 'PUBLISHED' THEN 0 ELSE 1 END", "ASC", false);
        $builder->orderBy("f.changed", "DESC");

        $query = $builder->get();
        return $query ? $query->getResult() : [];
    }

    protected function get_status_local_options()
    {
        return [
            WA_FLOW_STATUS_LOCAL_DRAFT => __("Draft"),
            WA_FLOW_STATUS_LOCAL_READY => __("Ready"),
            WA_FLOW_STATUS_LOCAL_ARCHIVED => __("Archived"),
        ];
    }

    protected function get_flow_categories()
    {
        return [
            "SIGN_UP" => __("Sign up"),
            "SIGN_IN" => __("Sign in"),
            "APPOINTMENT_BOOKING" => __("Appointment booking"),
            "LEAD_GENERATION" => __("Lead generation"),
            "CONTACT_US" => __("Contact us"),
            "CUSTOMER_SUPPORT" => __("Customer support"),
            "SURVEY" => __("Survey"),
            "OTHER" => __("Other"),
        ];
    }

    protected function assert_flow_permission()
    {
        if (!permission("whatsapp_flow")) {
            throw new \Exception(__('You do not have permission to manage WhatsApp Flows'));
        }

        if ((int) permission("cloud_api_enabled") !== 1) {
            throw new \Exception(__('Cloud API is not enabled for this plan'));
        }
    }

    protected function persist_flow_from_request($ids = false)
    {
        $team_id = get_team("id");

        $name = trim((string) post("name"));
        $slug = trim((string) post("slug"));
        $account_ids = trim((string) post("account_ids"));
        $endpoint_id = (int) post("endpoint_id");
        $status_local = trim((string) post("status_local"));
        $json_version = trim((string) post("json_version"));
        $data_api_version = trim((string) post("data_api_version"));
        $flow_json = $this->normalize_json_text((string) post("flow_json"));
        $preview_data = $this->normalize_json_text((string) post("preview_data"));
        $builder_state = $this->normalize_json_text((string) post("builder_state"));

        $item = $ids ? db_get("*", TB_WHATSAPP_FLOWS, ["ids" => $ids, "team_id" => $team_id]) : false;
        $categories = $this->normalize_categories(post("categories"));
        if (empty($categories) && !empty($item) && $this->table_has_column(TB_WHATSAPP_FLOWS, "categories_json")) {
            $categories = $this->decode_categories(get_data($item, "categories_json", "text"));
        }

        if (empty($categories)) {
            $categories = ["OTHER"];
        }

        if ($name === "") {
            throw new \Exception(__('Flow name is required'));
        }

        if ($account_ids === "") {
            throw new \Exception(__('Cloud account is required'));
        }

        $allowed_statuses = array_keys($this->get_status_local_options());
        if (!in_array($status_local, $allowed_statuses, true)) {
            $status_local = WA_FLOW_STATUS_LOCAL_DRAFT;
        }

        if ($flow_json !== null && !$this->is_valid_json($flow_json)) {
            throw new \Exception(__('Flow JSON must be valid JSON'));
        }

        if ($preview_data !== null && !$this->is_valid_json($preview_data)) {
            throw new \Exception(__('Preview data must be valid JSON'));
        }

        if ($builder_state !== null && !$this->is_valid_json($builder_state)) {
            throw new \Exception(__('Builder state must be valid JSON'));
        }

        $account = db_get("*", TB_ACCOUNTS, [
            "ids" => $account_ids,
            "team_id" => $team_id,
            "social_network" => "whatsapp",
            "category" => "profile",
            "login_type" => 1,
            "status" => 1,
        ]);

        if (empty($account)) {
            throw new \Exception(__('Selected Cloud account was not found'));
        }

        $account_data = json_decode($account->data ?? "", true);
        if (!is_array($account_data)) {
            $account_data = [];
        }

        $resolved_endpoint = null;
        if ($endpoint_id > 0) {
            $resolved_endpoint = db_get("*", TB_WHATSAPP_FLOW_ENDPOINTS, [
                "id" => $endpoint_id,
                "team_id" => $team_id,
            ]);

            if (empty($resolved_endpoint)) {
                throw new \Exception(__('Selected endpoint was not found'));
            }

            if (!empty($resolved_endpoint->account_id) && (int) $resolved_endpoint->account_id !== (int) $account->id) {
                throw new \Exception(__('Selected endpoint does not belong to the chosen account'));
            }
        } else {
            $resolved_endpoint = db_get("*", TB_WHATSAPP_FLOW_ENDPOINTS, [
                "team_id" => $team_id,
                "account_id" => $account->id,
            ]);
        }

        $slug = $this->slugify($slug !== "" ? $slug : $name);
        if ($slug === "") {
            $slug = ids();
        }

        $data = [
            "team_id" => $team_id,
            "account_id" => $account->id,
            "account_ids" => $account->ids,
            "waba_id" => $account_data["waba_id"] ?? null,
            "phone_number_id" => $account_data["phone_number_id"] ?? null,
            "endpoint_id" => !empty($resolved_endpoint) ? $resolved_endpoint->id : null,
            "name" => $name,
            "slug" => $slug,
            "channel" => "cloud_api",
            "status_local" => $status_local,
            "json_version" => $json_version !== "" ? $json_version : null,
            "data_api_version" => $data_api_version !== "" ? $data_api_version : null,
            "flow_json" => $flow_json,
            "preview_data" => $preview_data,
            "changed" => time(),
        ];

        if ($this->table_has_column(TB_WHATSAPP_FLOWS, "builder_state")) {
            $data["builder_state"] = $builder_state;
        }

        if ($this->table_has_column(TB_WHATSAPP_FLOWS, "categories_json")) {
            $data["categories_json"] = json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (empty($item)) {
            $data["ids"] = ids();
            $data["created"] = time();
            db_insert(TB_WHATSAPP_FLOWS, $data);
            $saved_ids = $data["ids"];
        } else {
            db_update(TB_WHATSAPP_FLOWS, $data, ["ids" => $ids, "team_id" => $team_id]);
            $saved_ids = $item->ids;
        }

        $flow = db_get("*", TB_WHATSAPP_FLOWS, ["ids" => $saved_ids, "team_id" => $team_id]);
        if (empty($flow)) {
            throw new \Exception(__('Unable to save the Flow locally'));
        }

        return [
            "flow" => $flow,
            "account" => $account,
            "endpoint" => $resolved_endpoint,
            "categories" => $categories,
        ];
    }

    protected function resolve_flow_context($ids = false, $persist_from_post = false)
    {
        if ($persist_from_post) {
            return $this->persist_flow_from_request($ids);
        }

        $team_id = get_team("id");
        $flow = $ids ? db_get("*", TB_WHATSAPP_FLOWS, ["ids" => $ids, "team_id" => $team_id]) : false;
        if (empty($flow)) {
            throw new \Exception(__('Save the Flow locally before using Meta actions'));
        }

        $account = db_get("*", TB_ACCOUNTS, [
            "id" => (int) $flow->account_id,
            "team_id" => $team_id,
            "social_network" => "whatsapp",
            "category" => "profile",
            "login_type" => 1,
            "status" => 1,
        ]);

        if (empty($account)) {
            throw new \Exception(__('The Cloud API account linked to this Flow is no longer available'));
        }

        $endpoint = null;
        if (!empty($flow->endpoint_id)) {
            $endpoint = db_get("*", TB_WHATSAPP_FLOW_ENDPOINTS, [
                "id" => (int) $flow->endpoint_id,
                "team_id" => $team_id,
            ]);
        }

        if (empty($endpoint)) {
            $endpoint = db_get("*", TB_WHATSAPP_FLOW_ENDPOINTS, [
                "team_id" => $team_id,
                "account_id" => (int) $account->id,
            ]);
        }

        $categories = [];
        if ($this->table_has_column(TB_WHATSAPP_FLOWS, "categories_json")) {
            $categories = $this->decode_categories(get_data($flow, "categories_json", "text"));
        }

        if (empty($categories)) {
            $categories = ["OTHER"];
        }

        return [
            "flow" => $flow,
            "account" => $account,
            "endpoint" => $endpoint,
            "categories" => $categories,
        ];
    }

    protected function ensure_endpoint_record($account, $flow = null)
    {
        $team_id = get_team("id");
        $account_data = json_decode($account->data ?? "", true);
        if (!is_array($account_data)) {
            $account_data = [];
        }

        $endpoint = db_get("*", TB_WHATSAPP_FLOW_ENDPOINTS, [
            "team_id" => $team_id,
            "account_id" => (int) $account->id,
        ]);

        $app_secret = trim((string) get_option("facebook_login_app_secret", ""));
        $data = [
            "team_id" => $team_id,
            "account_id" => (int) $account->id,
            "account_ids" => (string) get_data($account, "ids", "text"),
            "waba_id" => $account_data["waba_id"] ?? null,
            "phone_number_id" => $account_data["phone_number_id"] ?? null,
            "endpoint_uri" => $endpoint ? (string) get_data($endpoint, "endpoint_uri", "text") : null,
            "app_secret_verified" => $app_secret !== "" ? 1 : 0,
            "changed" => time(),
        ];

        if (trim((string) $data["endpoint_uri"]) === "") {
            $seed_ids = $endpoint ? (string) get_data($endpoint, "ids", "text") : ids();
            $data["endpoint_uri"] = $this->build_flow_endpoint_uri($seed_ids);
        }

        if (empty($endpoint)) {
            $data["ids"] = ids();
            $data["endpoint_uri"] = $this->build_flow_endpoint_uri($data["ids"]);
            $data["endpoint_status"] = "not_configured";
            $data["created"] = time();
            db_insert(TB_WHATSAPP_FLOW_ENDPOINTS, $data);

            $endpoint = db_get("*", TB_WHATSAPP_FLOW_ENDPOINTS, [
                "team_id" => $team_id,
                "account_id" => (int) $account->id,
            ]);
        } else {
            db_update(TB_WHATSAPP_FLOW_ENDPOINTS, $data, [
                "id" => (int) $endpoint->id,
                "team_id" => $team_id,
            ]);

            $endpoint = db_get("*", TB_WHATSAPP_FLOW_ENDPOINTS, [
                "id" => (int) $endpoint->id,
                "team_id" => $team_id,
            ]);
        }

        if (empty($endpoint)) {
            throw new \Exception(__('Unable to create the Flow endpoint record locally'));
        }

        if (!empty($flow) && (int) get_data($flow, "endpoint_id") !== (int) $endpoint->id) {
            db_update(TB_WHATSAPP_FLOWS, [
                "endpoint_id" => (int) $endpoint->id,
                "changed" => time(),
            ], [
                "id" => (int) get_data($flow, "id"),
                "team_id" => $team_id,
            ]);
        }

        return $endpoint;
    }

    protected function ensure_endpoint_keypair($endpoint)
    {
        $endpoint_ids = trim((string) get_data($endpoint, "ids", "text"));
        if ($endpoint_ids === "") {
            throw new \Exception(__('Endpoint IDs are missing'));
        }

        $base_dir = rtrim(WRITEPATH, "/\\") . DIRECTORY_SEPARATOR . "flow_endpoints" . DIRECTORY_SEPARATOR . $endpoint_ids;
        if (!is_dir($base_dir) && !@mkdir($base_dir, 0775, true) && !is_dir($base_dir)) {
            throw new \Exception(__('Unable to create the Flow endpoint directory in writable storage'));
        }

        $private_key_path = trim((string) get_data($endpoint, "private_key_path", "text"));
        $public_key_path = $base_dir . DIRECTORY_SEPARATOR . "public.pem";
        if ($private_key_path === "") {
            $private_key_path = $base_dir . DIRECTORY_SEPARATOR . "private.pem";
        }

        if (!file_exists($private_key_path)) {
            $key_resource = openssl_pkey_new([
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ]);

            if ($key_resource === false) {
                throw new \Exception(__('Unable to generate the RSA keypair for the Flow endpoint'));
            }

            $private_key = "";
            if (!openssl_pkey_export($key_resource, $private_key)) {
                throw new \Exception(__('Unable to export the Flow endpoint private key'));
            }

            $details = openssl_pkey_get_details($key_resource);
            $public_key = $details["key"] ?? null;
            if (!$public_key) {
                throw new \Exception(__('Unable to derive the Flow endpoint public key'));
            }

            file_put_contents($private_key_path, $private_key);
            @chmod($private_key_path, 0600);
            file_put_contents($public_key_path, $public_key);
            @chmod($public_key_path, 0644);
        } else {
            $private_key = (string) file_get_contents($private_key_path);
            $public_key = $this->derive_public_key_from_private($private_key);

            if (!file_exists($public_key_path) || trim((string) file_get_contents($public_key_path)) !== trim($public_key)) {
                file_put_contents($public_key_path, $public_key);
                @chmod($public_key_path, 0644);
            }
        }

        $fingerprint = $this->compute_public_key_fingerprint($public_key);
        db_update(TB_WHATSAPP_FLOW_ENDPOINTS, [
            "private_key_path" => $private_key_path,
            "public_key_fingerprint" => $fingerprint,
            "endpoint_status" => "local_ready",
            "last_meta_error" => null,
            "changed" => time(),
        ], [
            "id" => (int) get_data($endpoint, "id"),
            "team_id" => get_team("id"),
        ]);

        return [
            "private_key_path" => $private_key_path,
            "public_key_path" => $public_key_path,
            "private_key" => $private_key,
            "public_key" => $public_key,
            "fingerprint" => $fingerprint,
        ];
    }

    protected function sync_endpoint_public_key_on_meta($endpoint, $account, $public_key)
    {
        $phone_number_id = $this->get_account_phone_number_id($account);
        $response = $this->meta_graph_request($account, $phone_number_id . "/whatsapp_business_encryption", "POST", [
            "form_params" => [
                "business_public_key" => trim((string) $public_key),
            ],
        ]);

        $this->log_meta_account_event($account, "meta_upload_business_public_key", [
            "endpoint_id" => (int) get_data($endpoint, "id"),
            "phone_number_id" => $phone_number_id,
            "endpoint_uri" => (string) get_data($endpoint, "endpoint_uri", "text"),
            "public_key_fingerprint" => $this->compute_public_key_fingerprint($public_key),
        ], $response);

        $update = [
            "public_key_uploaded" => $response["status"] === "success" ? 1 : 0,
            "last_meta_error" => $response["status"] === "success" ? null : (string) $response["message"],
            "last_sync_at" => time(),
            "endpoint_status" => $response["status"] === "success" ? "public_key_uploaded" : "upload_failed",
            "changed" => time(),
        ];

        db_update(TB_WHATSAPP_FLOW_ENDPOINTS, $update, [
            "id" => (int) get_data($endpoint, "id"),
            "team_id" => get_team("id"),
        ]);

        return $response;
    }

    protected function refresh_endpoint_status_on_meta($endpoint, $account, $local_public_key = null)
    {
        $phone_number_id = $this->get_account_phone_number_id($account);
        $response = $this->meta_graph_request($account, $phone_number_id . "/whatsapp_business_encryption", "GET");

        $this->log_meta_account_event($account, "meta_get_business_public_key", [
            "endpoint_id" => (int) get_data($endpoint, "id"),
            "phone_number_id" => $phone_number_id,
            "endpoint_uri" => (string) get_data($endpoint, "endpoint_uri", "text"),
        ], $response);

        if ($response["status"] !== "success") {
            db_update(TB_WHATSAPP_FLOW_ENDPOINTS, [
                "last_meta_error" => (string) $response["message"],
                "last_sync_at" => time(),
                "changed" => time(),
            ], [
                "id" => (int) get_data($endpoint, "id"),
                "team_id" => get_team("id"),
            ]);

            throw new \Exception($response["message"]);
        }

        $remote_public_key = trim((string) ($response["data"]["business_public_key"] ?? ""));
        $signature_status = strtoupper(trim((string) ($response["data"]["business_public_key_signature_status"] ?? "")));
        $has_local_key = $local_public_key !== null && trim((string) $local_public_key) !== "";
        $keys_match = false;

        if ($has_local_key && $remote_public_key !== "") {
            $keys_match = $this->normalize_public_key_for_compare($local_public_key) === $this->normalize_public_key_for_compare($remote_public_key);
        }

        $endpoint_status = $this->derive_endpoint_status($has_local_key, $remote_public_key !== "", $keys_match, $signature_status);
        $last_meta_error = null;

        if ($signature_status !== "" && !in_array($signature_status, ["VALID", "MATCHED"], true)) {
            $last_meta_error = "Meta signature status: " . $signature_status;
        } elseif ($remote_public_key !== "" && $has_local_key && !$keys_match) {
            $last_meta_error = __('The public key stored on Meta does not match the local endpoint keypair');
        }

        db_update(TB_WHATSAPP_FLOW_ENDPOINTS, [
            "public_key_uploaded" => $remote_public_key !== "" ? 1 : 0,
            "app_secret_verified" => trim((string) get_option("facebook_login_app_secret", "")) !== "" ? 1 : 0,
            "last_meta_error" => $last_meta_error,
            "last_sync_at" => time(),
            "endpoint_status" => $endpoint_status,
            "changed" => time(),
        ], [
            "id" => (int) get_data($endpoint, "id"),
            "team_id" => get_team("id"),
        ]);

        return db_get("*", TB_WHATSAPP_FLOW_ENDPOINTS, [
            "id" => (int) get_data($endpoint, "id"),
            "team_id" => get_team("id"),
        ]);
    }

    protected function sync_meta_draft($context)
    {
        $flow = $context["flow"];
        $account = $context["account"];
        $endpoint = $context["endpoint"] ?? null;
        $categories = $context["categories"] ?? ["OTHER"];

        if (trim((string) get_data($flow, "flow_json", "text")) === "") {
            throw new \Exception(__('Flow JSON is required before sending the draft to Meta'));
        }

        if (strtoupper((string) get_data($flow, "status_meta", "text")) === "PUBLISHED") {
            throw new \Exception(__('This Flow is already published on Meta. Create a new local Flow if you need another version.'));
        }

        $meta_flow_id = trim((string) get_data($flow, "meta_flow_id", "text"));
        if ($meta_flow_id === "") {
            $create_response = $this->meta_graph_request($account, $this->get_account_waba_id($account) . "/flows", "POST", [
                "form_params" => $this->build_meta_flow_form_payload($flow, $categories, $endpoint),
            ]);

            $this->log_meta_event($flow, $account, "meta_create_flow", [
                "name" => get_data($flow, "name", "text"),
                "categories" => $categories,
                "endpoint_uri" => $this->get_endpoint_uri($endpoint),
            ], $create_response);

            if ($create_response["status"] !== "success" || empty($create_response["data"]["id"])) {
                throw new \Exception($create_response["message"]);
            }

            $meta_flow_id = (string) $create_response["data"]["id"];
            db_update(TB_WHATSAPP_FLOWS, [
                "meta_flow_id" => $meta_flow_id,
                "status_meta" => "DRAFT",
                "last_meta_error" => null,
                "last_sync_at" => time(),
                "changed" => time(),
            ], [
                "id" => (int) $flow->id,
                "team_id" => get_team("id"),
            ]);

            $flow = db_get("*", TB_WHATSAPP_FLOWS, ["id" => (int) $flow->id, "team_id" => get_team("id")]);
        }

        $update_metadata = $this->meta_graph_request($account, $meta_flow_id, "POST", [
            "form_params" => $this->build_meta_flow_form_payload($flow, $categories, $endpoint),
        ]);

        $this->log_meta_event($flow, $account, "meta_update_flow_metadata", [
            "flow_id" => $meta_flow_id,
            "name" => get_data($flow, "name", "text"),
            "categories" => $categories,
            "endpoint_uri" => $this->get_endpoint_uri($endpoint),
        ], $update_metadata);

        if ($update_metadata["status"] !== "success") {
            throw new \Exception($update_metadata["message"]);
        }

        $upload = $this->upload_meta_flow_json($flow, $account);
        $validation_errors = $upload["data"]["validation_errors"] ?? [];
        $flow = $this->sync_flow_from_meta($flow, $account);

        if (!empty($validation_errors)) {
            throw new \Exception(__('Meta accepted the draft, but found validation errors in the Flow JSON. Review "Last Meta error" before publishing.'));
        }

        return $flow;
    }

    protected function upload_meta_flow_json($flow, $account)
    {
        $meta_flow_id = trim((string) get_data($flow, "meta_flow_id", "text"));
        if ($meta_flow_id === "") {
            throw new \Exception(__('Meta Flow ID is missing for JSON upload'));
        }

        $flow_json = trim((string) get_data($flow, "flow_json", "text"));
        if ($flow_json === "") {
            throw new \Exception(__('Flow JSON is empty'));
        }

        $sanitized_flow = $this->sanitize_flow_json_for_meta($flow_json);
        $flow_json = $sanitized_flow["json"];
        if (!empty($sanitized_flow["changed"])) {
            $this->update_flow_meta_state((int) $flow->id, [
                "flow_json" => $flow_json,
                "changed" => time(),
            ]);
            $flow = db_get("*", TB_WHATSAPP_FLOWS, ["id" => (int) $flow->id, "team_id" => get_team("id")]) ?: $flow;
        }

        $temp_file = tempnam(sys_get_temp_dir(), "wa_flow_");
        if ($temp_file === false) {
            throw new \Exception(__('Unable to prepare the temporary Flow JSON file'));
        }

        file_put_contents($temp_file, $flow_json);

        try {
            $response = $this->meta_graph_request($account, $meta_flow_id . "/assets", "POST", [
                "form_params" => $this->build_meta_flow_asset_payload($temp_file),
            ]);
        } finally {
            @unlink($temp_file);
        }

        $this->log_meta_event($flow, $account, "meta_upload_flow_json", [
            "flow_id" => $meta_flow_id,
            "asset_type" => "FLOW_JSON",
            "name" => "flow.json",
            "sanitized" => !empty($sanitized_flow["changed"]),
            "screen_map" => $sanitized_flow["screen_map"] ?? [],
        ], $response);

        if ($response["status"] !== "success") {
            $this->update_flow_meta_state((int) $flow->id, [
                "last_meta_error" => $response["message"],
                "last_sync_at" => time(),
                "changed" => time(),
            ]);
            throw new \Exception($response["message"]);
        }

        $validation_errors = $response["data"]["validation_errors"] ?? [];
        $this->update_flow_meta_state((int) $flow->id, [
            "last_meta_error" => !empty($validation_errors) ? $this->format_meta_errors($validation_errors) : null,
            "last_sync_at" => time(),
            "changed" => time(),
        ]);

        return $response;
    }

    protected function sync_flow_from_meta($flow, $account)
    {
        $meta_flow_id = trim((string) get_data($flow, "meta_flow_id", "text"));
        if ($meta_flow_id === "") {
            throw new \Exception(__('Meta Flow ID is missing'));
        }

        $response = $this->meta_graph_request($account, $meta_flow_id, "GET", [
            "query" => [
                "fields" => "id,name,categories,preview,status,validation_errors,json_version,data_api_version,data_channel_uri,health_status",
            ],
        ]);

        $this->log_meta_event($flow, $account, "meta_get_flow", [
            "flow_id" => $meta_flow_id,
        ], $response);

        if ($response["status"] !== "success") {
            throw new \Exception($response["message"]);
        }

        $data = $response["data"];
        $validation_errors = $data["validation_errors"] ?? [];
        $preview = $data["preview"] ?? [];
        $status_meta = (string) ($data["status"] ?? get_data($flow, "status_meta", "text"));

        $update = [
            "meta_flow_id" => (string) ($data["id"] ?? $meta_flow_id),
            "status_meta" => $status_meta !== "" ? $status_meta : null,
            "json_version" => !empty($data["json_version"]) ? (string) $data["json_version"] : get_data($flow, "json_version", "text"),
            "data_api_version" => !empty($data["data_api_version"]) ? (string) $data["data_api_version"] : get_data($flow, "data_api_version", "text"),
            "health_status" => !empty($data["health_status"]) ? json_encode($data["health_status"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            "last_meta_error" => !empty($validation_errors) ? $this->format_meta_errors($validation_errors) : null,
            "last_sync_at" => time(),
            "changed" => time(),
        ];

        if ($this->table_has_column(TB_WHATSAPP_FLOWS, "categories_json") && !empty($data["categories"]) && is_array($data["categories"])) {
            $update["categories_json"] = json_encode($this->normalize_categories($data["categories"]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($this->table_has_column(TB_WHATSAPP_FLOWS, "data_channel_uri")) {
            $update["data_channel_uri"] = !empty($data["data_channel_uri"]) ? (string) $data["data_channel_uri"] : null;
        }

        if ($this->table_has_column(TB_WHATSAPP_FLOWS, "preview_url")) {
            $update["preview_url"] = !empty($preview["preview_url"]) ? (string) $preview["preview_url"] : null;
        }

        if ($this->table_has_column(TB_WHATSAPP_FLOWS, "preview_expires_at")) {
            $update["preview_expires_at"] = !empty($preview["expires_at"]) ? strtotime((string) $preview["expires_at"]) : null;
        }

        if ($status_meta === "PUBLISHED" && empty($flow->published_at)) {
            $update["published_at"] = time();
        }

        $this->update_flow_meta_state((int) $flow->id, $update);
        $this->sync_flow_assets_from_meta($flow, $account);

        $refreshed = db_get("*", TB_WHATSAPP_FLOWS, ["id" => (int) $flow->id, "team_id" => get_team("id")]);
        return $refreshed ?: $flow;
    }

    protected function pull_meta_flows_for_account($account)
    {
        $path = $this->get_account_waba_id($account) . "/flows";
        $query = [
            "fields" => "id,name,status,categories",
        ];

        $meta_items = [];
        $seen_ids = [];
        $page_count = 0;

        while ($path !== null && $page_count < 50) {
            $response = $this->meta_graph_request($account, $path, "GET", [
                "query" => $query,
            ]);

            $this->log_meta_account_event($account, "meta_list_flows", [
                "path" => $path,
                "query" => $query,
                "page" => $page_count + 1,
            ], $response);

            if ($response["status"] !== "success") {
                throw new \Exception($response["message"]);
            }

            $response_data = $response["data"]["data"] ?? [];
            if (is_array($response_data)) {
                foreach ($response_data as $meta_flow) {
                    $meta_flow_id = trim((string) ($meta_flow["id"] ?? ""));
                    if ($meta_flow_id === "" || isset($seen_ids[$meta_flow_id])) {
                        continue;
                    }

                    $seen_ids[$meta_flow_id] = true;
                    $meta_items[] = $meta_flow;
                }
            }

            $next = trim((string) ($response["data"]["paging"]["next"] ?? ""));
            $path = $next !== "" ? $next : null;
            $query = [];
            $page_count++;
        }

        $created = 0;
        $updated = 0;
        $synced = 0;

        foreach ($meta_items as $meta_flow) {
            $upsert = $this->upsert_local_flow_from_meta($account, $meta_flow);
            $this->sync_flow_from_meta($upsert["flow"], $account);
            $synced++;

            if (!empty($upsert["created"])) {
                $created++;
            } else {
                $updated++;
            }
        }

        return [
            "synced" => $synced,
            "created" => $created,
            "updated" => $updated,
            "pages" => $page_count,
        ];
    }

    protected function upsert_local_flow_from_meta($account, $meta_flow)
    {
        $team_id = get_team("id");
        $meta_flow_id = trim((string) ($meta_flow["id"] ?? ""));
        if ($meta_flow_id === "") {
            throw new \Exception(__('Meta returned a Flow without ID'));
        }

        $account_data = json_decode($account->data ?? "", true);
        if (!is_array($account_data)) {
            $account_data = [];
        }

        $existing = db_get("*", TB_WHATSAPP_FLOWS, [
            "team_id" => $team_id,
            "account_id" => (int) $account->id,
            "meta_flow_id" => $meta_flow_id,
        ]);

        $default_endpoint = db_get("*", TB_WHATSAPP_FLOW_ENDPOINTS, [
            "team_id" => $team_id,
            "account_id" => (int) $account->id,
        ]);

        $status_meta = trim((string) ($meta_flow["status"] ?? ""));
        $meta_name = trim((string) ($meta_flow["name"] ?? ""));
        $categories = $this->normalize_categories($meta_flow["categories"] ?? []);
        $slug_seed = $meta_name !== "" ? $meta_name : ("meta-flow-" . substr($meta_flow_id, -6));
        $slug = $this->slugify($slug_seed);

        if ($slug === "") {
            $slug = "meta-flow-" . strtolower(substr($meta_flow_id, -6));
        }

        $data = [
            "team_id" => $team_id,
            "account_id" => (int) $account->id,
            "account_ids" => (string) get_data($account, "ids", "text"),
            "waba_id" => $account_data["waba_id"] ?? null,
            "phone_number_id" => $account_data["phone_number_id"] ?? null,
            "meta_flow_id" => $meta_flow_id,
            "channel" => "cloud_api",
            "status_local" => $existing ? (string) get_data($existing, "status_local", "text") : $this->derive_local_status_from_meta($status_meta),
            "status_meta" => $status_meta !== "" ? $status_meta : null,
            "changed" => time(),
        ];

        if ($existing) {
            if ((int) get_data($existing, "endpoint_id") <= 0 && !empty($default_endpoint)) {
                $data["endpoint_id"] = (int) $default_endpoint->id;
            }

            if (trim((string) get_data($existing, "name", "text")) === "" || trim((string) get_data($existing, "flow_json", "text")) === "") {
                $data["name"] = $meta_name !== "" ? $meta_name : (string) get_data($existing, "name", "text");
            }

            if (trim((string) get_data($existing, "slug", "text")) === "") {
                $data["slug"] = $slug;
            }
        } else {
            $data["ids"] = ids();
            $data["endpoint_id"] = !empty($default_endpoint) ? (int) $default_endpoint->id : null;
            $data["name"] = $meta_name !== "" ? $meta_name : ("Meta Flow " . substr($meta_flow_id, -6));
            $data["slug"] = $slug;
            $data["created"] = time();
        }

        if ($this->table_has_column(TB_WHATSAPP_FLOWS, "categories_json") && !empty($categories)) {
            $data["categories_json"] = json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($existing) {
            db_update(TB_WHATSAPP_FLOWS, $data, [
                "id" => (int) $existing->id,
                "team_id" => $team_id,
            ]);
        } else {
            db_insert(TB_WHATSAPP_FLOWS, $data);
        }

        $flow = db_get("*", TB_WHATSAPP_FLOWS, [
            "team_id" => $team_id,
            "account_id" => (int) $account->id,
            "meta_flow_id" => $meta_flow_id,
        ]);

        if (empty($flow)) {
            throw new \Exception(__('Unable to persist the Flow imported from Meta'));
        }

        return [
            "flow" => $flow,
            "created" => empty($existing),
        ];
    }

    protected function sync_flow_assets_from_meta($flow, $account)
    {
        $meta_flow_id = trim((string) get_data($flow, "meta_flow_id", "text"));
        if ($meta_flow_id === "") {
            return;
        }

        $assets = $this->meta_graph_request($account, $meta_flow_id . "/assets", "GET");
        $this->log_meta_event($flow, $account, "meta_list_flow_assets", [
            "flow_id" => $meta_flow_id,
        ], $assets);

        if ($assets["status"] !== "success" || empty($assets["data"]["data"]) || !is_array($assets["data"]["data"])) {
            return;
        }

        foreach ($assets["data"]["data"] as $asset) {
            $name = trim((string) ($asset["name"] ?? "flow.json"));
            $asset_type = trim((string) ($asset["asset_type"] ?? "FLOW_JSON"));
            $download_url = trim((string) ($asset["download_url"] ?? ""));

            $data = [
                "team_id" => get_team("id"),
                "flow_id" => (int) get_data($flow, "id"),
                "meta_flow_id" => $meta_flow_id,
                "name" => $name !== "" ? $name : "flow.json",
                "asset_type" => $asset_type !== "" ? $asset_type : "FLOW_JSON",
                "public_url" => $download_url !== "" ? $download_url : null,
                "status" => "meta_synced",
                "checksum" => $download_url !== "" ? sha1($download_url) : null,
                "changed" => time(),
            ];

            $existing = db_get("*", TB_WHATSAPP_FLOW_ASSETS, [
                "team_id" => get_team("id"),
                "flow_id" => (int) get_data($flow, "id"),
                "name" => $data["name"],
                "asset_type" => $data["asset_type"],
            ]);

            if (empty($existing)) {
                $data["ids"] = ids();
                $data["created"] = time();
                db_insert(TB_WHATSAPP_FLOW_ASSETS, $data);
            } else {
                db_update(TB_WHATSAPP_FLOW_ASSETS, $data, [
                    "id" => (int) $existing->id,
                    "team_id" => get_team("id"),
                ]);
            }
        }
    }

    protected function meta_graph_request($account, $path, $method = "GET", $options = [])
    {
        $account_data = json_decode($account->data ?? "", true);
        if (!is_array($account_data)) {
            $account_data = [];
        }

        $access_token = trim((string) ($account_data["token"] ?? ""));
        if ($access_token === "") {
            return [
                "status" => "error",
                "message" => __('Cloud API access token is missing'),
                "http_code" => 0,
                "data" => null,
            ];
        }

        $url = "https://graph.facebook.com/v22.0/" . ltrim($path, "/");
        if (!empty($options["query"]) && is_array($options["query"])) {
            $url .= (strpos($url, "?") === false ? "?" : "&") . http_build_query($options["query"]);
        }

        $ch = curl_init($url);
        $headers = [
            "Authorization: Bearer " . $access_token,
        ];

        $method = strtoupper($method);
        switch ($method) {
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                break;

            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;

            default:
                break;
        }

        if (array_key_exists("json", $options)) {
            $json_payload = json_encode($options["json"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headers[] = "Content-Type: application/json";
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
        } elseif (!empty($options["form_params"]) && is_array($options["form_params"])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options["form_params"]);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response_raw = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode((string) $response_raw, true);
        if (!is_array($decoded)) {
            $decoded = [
                "raw" => (string) $response_raw,
            ];
        }

        $log_payload = [
            "path" => ltrim($path, "/"),
            "method" => $method,
            "query" => $options["query"] ?? null,
            "form_params" => $this->sanitize_meta_payload_for_log($options["form_params"] ?? null),
            "json" => $options["json"] ?? null,
            "http_code" => $http_code,
            "curl_error" => $curl_error,
            "response" => $decoded,
        ];

        @file_put_contents(
            rtrim(WRITEPATH, "/\\") . "/logs/whatsapp_flow_meta.log",
            date("Y-m-d H:i:s") . " " . json_encode($log_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );

        if ($curl_error) {
            return [
                "status" => "error",
                "message" => "Curl error: " . $curl_error,
                "http_code" => $http_code,
                "data" => $decoded,
            ];
        }

        if ($http_code >= 200 && $http_code < 300 && empty($decoded["error"])) {
            return [
                "status" => "success",
                "message" => __("Success"),
                "http_code" => $http_code,
                "data" => $decoded,
            ];
        }

        return [
            "status" => "error",
            "message" => (string) ($decoded["error"]["message"] ?? ("Meta request failed with HTTP " . $http_code)),
            "http_code" => $http_code,
            "data" => $decoded,
        ];
    }

    protected function get_account_waba_id($account)
    {
        $account_data = json_decode($account->data ?? "", true);
        if (!is_array($account_data)) {
            $account_data = [];
        }

        $waba_id = trim((string) ($account_data["waba_id"] ?? ""));
        if ($waba_id === "") {
            throw new \Exception(__('The selected Cloud API account does not have a WABA ID'));
        }

        return $waba_id;
    }

    protected function get_account_phone_number_id($account)
    {
        $account_data = json_decode($account->data ?? "", true);
        if (!is_array($account_data)) {
            $account_data = [];
        }

        $phone_number_id = trim((string) ($account_data["phone_number_id"] ?? ""));
        if ($phone_number_id === "") {
            throw new \Exception(__('The selected Cloud API account does not have a phone number ID'));
        }

        return $phone_number_id;
    }

    protected function get_endpoint_uri($endpoint)
    {
        return !empty($endpoint) ? trim((string) get_data($endpoint, "endpoint_uri", "text")) : null;
    }

    protected function build_meta_flow_form_payload($flow, $categories, $endpoint = null)
    {
        return array_filter([
            "name" => trim((string) get_data($flow, "name", "text")),
            "categories" => json_encode($this->normalize_categories($categories), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            "endpoint_uri" => $this->get_endpoint_uri($endpoint),
            "data_api_version" => trim((string) get_data($flow, "data_api_version", "text")),
        ], function ($value) {
            return $value !== null && $value !== "";
        });
    }

    protected function build_meta_flow_asset_payload($file_path)
    {
        return [
            "file" => new \CURLFile($file_path, "application/json", "flow.json"),
            "name" => "flow.json",
            "asset_type" => "FLOW_JSON",
        ];
    }

    protected function sanitize_flow_json_for_meta($flow_json)
    {
        $decoded = json_decode($flow_json, true);
        if (!is_array($decoded) || empty($decoded["screens"]) || !is_array($decoded["screens"])) {
            return [
                "json" => $flow_json,
                "changed" => false,
                "screen_map" => [],
            ];
        }

        $used_ids = [];
        $screen_map = [];
        foreach ($decoded["screens"] as $index => $screen) {
            $old_id = trim((string) ($screen["id"] ?? ""));
            $fallback = "SCREEN_" . $this->alpha_sequence($index);
            $new_id = $this->unique_meta_identifier($old_id !== "" ? $old_id : $fallback, $fallback, $used_ids);

            if ($old_id !== "" && $old_id !== $new_id) {
                $screen_map[$old_id] = $new_id;
            }

            $decoded["screens"][$index]["id"] = $new_id;
        }

        if (!empty($decoded["routing_model"]) && is_array($decoded["routing_model"])) {
            $new_routing_model = [];
            foreach ($decoded["routing_model"] as $screen_id => $routes) {
                $mapped_screen_id = $screen_map[$screen_id] ?? $this->sanitize_meta_identifier($screen_id, "SCREEN");
                $mapped_routes = [];

                if (is_array($routes)) {
                    foreach ($routes as $route) {
                        $mapped_routes[] = $screen_map[$route] ?? $this->sanitize_meta_identifier($route, "SCREEN");
                    }
                }

                $new_routing_model[$mapped_screen_id] = $mapped_routes;
            }

            $decoded["routing_model"] = $new_routing_model;
        }

        if (!empty($screen_map)) {
            $decoded["screens"] = $this->replace_flow_screen_references($decoded["screens"], $screen_map);
        }

        $decoded = $this->restore_flow_empty_objects($decoded);
        $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return [
                "json" => $flow_json,
                "changed" => false,
                "screen_map" => [],
            ];
        }

        return [
            "json" => $encoded,
            "changed" => trim($encoded) !== trim($flow_json),
            "screen_map" => $screen_map,
        ];
    }

    protected function restore_flow_empty_objects($value, $key = null)
    {
        if (is_array($value)) {
            foreach ($value as $child_key => $child_value) {
                $value[$child_key] = $this->restore_flow_empty_objects($child_value, is_string($child_key) ? $child_key : null);
            }

            if (empty($value) && in_array($key, ["data", "payload"], true)) {
                return (object) [];
            }
        }

        return $value;
    }

    protected function replace_flow_screen_references($value, $screen_map)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->replace_flow_screen_references($item, $screen_map);
            }

            return $value;
        }

        if (is_string($value) && array_key_exists($value, $screen_map)) {
            return $screen_map[$value];
        }

        return $value;
    }

    protected function sanitize_meta_identifier($value, $fallback = "SCREEN")
    {
        $value = trim((string) $value);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false) {
            $value = $ascii;
        }

        $value = strtoupper($value);
        $value = preg_replace('/[^A-Z]+/', '_', $value);
        $value = preg_replace('/_+/', '_', (string) $value);
        $value = trim((string) $value, '_');

        if ($value === '') {
            $value = trim((string) preg_replace('/[^A-Z]+/', '_', strtoupper((string) $fallback)), '_');
        }

        if ($value === '') {
            $value = 'SCREEN';
        }

        return substr($value, 0, 24);
    }

    protected function derive_public_key_from_private($private_key_pem)
    {
        $resource = openssl_pkey_get_private($private_key_pem);
        if ($resource === false) {
            throw new \Exception(__('Unable to read the Flow endpoint private key'));
        }

        $details = openssl_pkey_get_details($resource);
        if (empty($details["key"])) {
            throw new \Exception(__('Unable to derive the Flow endpoint public key'));
        }

        return (string) $details["key"];
    }

    protected function normalize_public_key_for_compare($public_key)
    {
        return preg_replace('/\s+/', '', str_replace([
            "-----BEGIN PUBLIC KEY-----",
            "-----END PUBLIC KEY-----",
        ], "", trim((string) $public_key)));
    }

    protected function compute_public_key_fingerprint($public_key)
    {
        return hash("sha256", $this->normalize_public_key_for_compare($public_key));
    }

    protected function derive_endpoint_status($has_local_key, $has_remote_key, $keys_match, $signature_status)
    {
        if (in_array($signature_status, ["VALID", "MATCHED"], true)) {
            return "verified";
        }

        if ($has_remote_key && !$has_local_key) {
            return "public_key_uploaded";
        }

        if ($has_remote_key && $keys_match) {
            return "public_key_uploaded";
        }

        if ($has_remote_key && !$keys_match && $has_local_key) {
            return "remote_key_mismatch";
        }

        if ($has_local_key) {
            return "local_ready";
        }

        return "not_configured";
    }

    protected function unique_meta_identifier($value, $fallback, array &$used_ids)
    {
        $base = $this->sanitize_meta_identifier($value, $fallback);
        $candidate = $base;
        $counter = 0;

        while (isset($used_ids[$candidate])) {
            $suffix = '_' . $this->alpha_sequence($counter);
            $trimmed = rtrim(substr($base, 0, max(1, 24 - strlen($suffix))), '_');
            $candidate = ($trimmed !== '' ? $trimmed : 'SCREEN') . $suffix;
            $counter++;
        }

        $used_ids[$candidate] = true;
        return $candidate;
    }

    protected function alpha_sequence($index)
    {
        $index = (int) $index;
        if ($index < 0) {
            $index = 0;
        }

        $index++;
        $sequence = '';

        while ($index > 0) {
            $index--;
            $sequence = chr(65 + ($index % 26)) . $sequence;
            $index = (int) floor($index / 26);
        }

        return $sequence !== '' ? $sequence : 'A';
    }

    protected function normalize_categories($categories)
    {
        $allowed = array_keys($this->get_flow_categories());

        if (is_string($categories)) {
            $categories = trim($categories);
            if ($categories === "") {
                $categories = [];
            } else {
                $decoded = json_decode($categories, true);
                if (is_array($decoded)) {
                    $categories = $decoded;
                } else {
                    $categories = array_map("trim", explode(",", $categories));
                }
            }
        }

        if (!is_array($categories)) {
            return [];
        }

        $normalized = [];
        foreach ($categories as $category) {
            $category = strtoupper(trim((string) $category));
            if ($category !== "" && in_array($category, $allowed, true)) {
                $normalized[] = $category;
            }
        }

        $normalized = array_values(array_unique($normalized));
        return !empty($normalized) ? $normalized : [];
    }

    protected function derive_local_status_from_meta($status_meta)
    {
        $status_meta = strtoupper(trim((string) $status_meta));

        switch ($status_meta) {
            case "PUBLISHED":
                return WA_FLOW_STATUS_LOCAL_READY;

            case "DEPRECATED":
                return WA_FLOW_STATUS_LOCAL_ARCHIVED;

            default:
                return WA_FLOW_STATUS_LOCAL_DRAFT;
        }
    }

    protected function decode_categories($raw)
    {
        if ($raw === null || $raw === "") {
            return [];
        }

        return $this->normalize_categories($raw);
    }

    protected function sanitize_meta_payload_for_log($payload)
    {
        if (!is_array($payload)) {
            return $payload;
        }

        $sanitized = [];
        foreach ($payload as $key => $value) {
            if ($value instanceof \CURLFile) {
                $sanitized[$key] = [
                    "file" => $value->getPostFilename(),
                    "mime" => $value->getMimeType(),
                ];
                continue;
            }

            if (is_string($value) && strlen($value) > 1000) {
                $sanitized[$key] = substr($value, 0, 1000) . "...";
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    protected function log_meta_event($flow, $account, $event_type, $payload, $result)
    {
        if (!defined("TB_WHATSAPP_FLOW_EVENTS")) {
            return;
        }

        db_insert(TB_WHATSAPP_FLOW_EVENTS, [
            "team_id" => get_team("id"),
            "flow_id" => (int) get_data($flow, "id"),
            "account_id" => (int) get_data($account, "id"),
            "account_ids" => (string) get_data($account, "ids", "text"),
            "instance_id" => (string) get_data($account, "token", "text"),
            "event_type" => $event_type,
            "direction" => "system",
            "flow_token" => "",
            "status" => (string) ($result["status"] ?? "unknown"),
            "payload" => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            "response" => json_encode($result["data"] ?? $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            "error_message" => (string) ($result["message"] ?? ""),
            "created" => time(),
        ]);
    }

    protected function log_meta_account_event($account, $event_type, $payload, $result)
    {
        if (!defined("TB_WHATSAPP_FLOW_EVENTS")) {
            return;
        }

        db_insert(TB_WHATSAPP_FLOW_EVENTS, [
            "team_id" => get_team("id"),
            "flow_id" => null,
            "account_id" => (int) get_data($account, "id"),
            "account_ids" => (string) get_data($account, "ids", "text"),
            "instance_id" => (string) get_data($account, "token", "text"),
            "event_type" => $event_type,
            "direction" => "system",
            "flow_token" => "",
            "status" => (string) ($result["status"] ?? "unknown"),
            "payload" => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            "response" => json_encode($result["data"] ?? $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            "error_message" => (string) ($result["message"] ?? ""),
            "created" => time(),
        ]);
    }

    protected function format_meta_errors($errors)
    {
        if (empty($errors)) {
            return null;
        }

        return json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function update_flow_meta_state($flow_id, $data)
    {
        db_update(TB_WHATSAPP_FLOWS, $data, [
            "id" => $flow_id,
            "team_id" => get_team("id"),
        ]);
    }

    protected function normalize_json_text($value)
    {
        $value = trim($value);
        return $value !== "" ? $value : null;
    }

    protected function is_valid_json($value)
    {
        json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function slugify($value)
    {
        $value = trim($value);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false) {
            $value = $ascii;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim((string) $value, '-');
        return $value;
    }

    protected function table_has_column($table, $column)
    {
        static $cache = [];

        $cache_key = $table . ":" . $column;
        if (array_key_exists($cache_key, $cache)) {
            return $cache[$cache_key];
        }

        try {
            $db = \Config\Database::connect();
            $cache[$cache_key] = $db->fieldExists($column, $table);
        } catch (\Throwable $e) {
            $cache[$cache_key] = false;
        }

        return $cache[$cache_key];
    }
}
