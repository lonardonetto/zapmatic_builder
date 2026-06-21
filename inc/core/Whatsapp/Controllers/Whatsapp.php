<?php
namespace Core\Whatsapp\Controllers;

class Whatsapp extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Whatsapp\Models\WhatsappModel();
    }
    
    public function index( $page = false ) {
        $report = $this->model->block_dashboard();
        $data['content'] = view('Core\Whatsapp\Views\content', ['content' => $report['html']] );

        $module = [
            __("WA Profiles"),
            __("WA Autoresponder"),
            __("WA Chatbot"),
            __("WA Bulk messaging"),
            __("WA Rest api"),
            __("WA Export participants"),
            __("WA Evolution API"),
            __("WA List message template"),
            __("WA Poll message template"),
            __("WA Button template"),
            __("WA Criptografia de textos"),
            __("API WhatApp REST"),
            __("WA Create Group"),
            __("WA Contact")
        ];

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function sidebar(){
        $modules = $this->model->get_modules();
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "modules" => $modules,
        ];
        return view('Core\Whatsapp\Views\sidebar', $data);
    }

    public function widget_content( $params = [] ){
        if ( !permission("whatsapp_send_media") ) return "";
        return view('Core\Whatsapp\Views\widget\content', ["result" => $params["result"]]);
    }

    public function widget_template_visual_selector( $params = [] ){
        $team_id = get_team("id");
        if (!$team_id) return "";

        $allowed_types = [];
        if (permission("whatsapp_button_template")) $allowed_types[] = 2;
        if (permission("whatsapp_list_message_template")) $allowed_types[] = 1;
        if (permission("whatsapp_poll_template")) $allowed_types[] = 3;
        if (permission("whatsapp_carousel_template")) $allowed_types[] = 5;

        if (empty($allowed_types)) return "";

        $templates = $this->get_visual_template_items($team_id, $allowed_types);

        return view('Core\Whatsapp\Views\widget\template_visual_selector', [
            "result" => $params["result"] ?? false,
            "templates" => $templates,
            "context" => $params["context"] ?? "whatsapp",
        ]);
    }

    private function get_visual_template_items($team_id, $allowed_types = [])
    {
        $db = \Config\Database::connect();
        $placeholders = implode(',', array_fill(0, count($allowed_types), '?'));
        $rows = $db->query(
            "SELECT id, ids, team_id, type, name, data, changed, created FROM " . TB_WHATSAPP_TEMPLATE . "
             WHERE team_id = ? AND type IN ({$placeholders})
             ORDER BY changed DESC, id DESC",
            array_merge([$team_id], $allowed_types)
        )->getResult();

        if (empty($rows)) return [];

        $meta_status_by_source = $this->get_visual_template_meta_statuses($team_id, $rows);
        $items = [];

        foreach ($rows as $row) {
            $payload = json_decode($row->data ?? '', true);
            if (!is_array($payload)) $payload = [];

            $db_type = (int) $row->type;
            $meta_status = ($db_type === 2 && !empty($row->ids) && isset($meta_status_by_source[(string) $row->ids]))
                ? $meta_status_by_source[(string) $row->ids]
                : [];

            $items[] = [
                "id" => (int) $row->id,
                "ids" => (string) ($row->ids ?? ''),
                "db_type" => $db_type,
                "type" => $this->visual_template_form_type($db_type),
                "input_name" => $this->visual_template_input_name($db_type),
                "type_label" => $this->visual_template_type_label($db_type),
                "category" => $this->visual_template_category($db_type),
                "icon" => $this->visual_template_icon($db_type),
                "name" => (string) ($row->name ?? ''),
                "preview" => $this->visual_template_preview($db_type, $payload),
                "details" => $this->visual_template_details($db_type, $payload),
                "edit_url" => $this->visual_template_edit_url($db_type, (string) ($row->ids ?? '')),
                "meta_status" => $meta_status["status"] ?? "",
                "meta_status_label" => $this->visual_template_meta_status_label($meta_status["status"] ?? ""),
                "changed_label" => !empty($row->changed) ? date("d/m/Y H:i", (int) $row->changed) : "",
            ];
        }

        return $items;
    }

    private function get_visual_template_meta_statuses($team_id, $rows)
    {
        $source_ids = [];
        foreach ($rows as $row) {
            if ((int) ($row->type ?? 0) === 2 && !empty($row->ids)) {
                $source_ids[] = (string) $row->ids;
            }
        }

        $source_ids = array_values(array_unique($source_ids));
        if (empty($source_ids) || !defined('WA_TEMPLATE_TYPE_META_STATUS')) return [];

        try {
            $db = \Config\Database::connect();
            $placeholders = implode(',', array_fill(0, count($source_ids), '?'));
            $query = $db->query(
                "SELECT changed, data FROM " . TB_WHATSAPP_TEMPLATE . "
                 WHERE team_id = ? AND type = ?
                   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_type')) = '2'
                   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_ids')) IN ({$placeholders})
                 ORDER BY changed DESC",
                array_merge([$team_id, WA_TEMPLATE_TYPE_META_STATUS], $source_ids)
            );

            $map = [];
            foreach ($query->getResult() as $row) {
                $data = json_decode($row->data ?? '', true);
                if (!is_array($data)) continue;
                $source = (string) ($data['source_template_ids'] ?? '');
                if ($source === '' || isset($map[$source])) continue;
                $map[$source] = $data;
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function visual_template_form_type($db_type)
    {
        switch ((int) $db_type) {
            case 1: return 3;
            case 2: return 2;
            case 3: return 4;
            case 5: return 5;
            default: return 0;
        }
    }

    private function visual_template_input_name($db_type)
    {
        switch ((int) $db_type) {
            case 2:
            case 3:
                return "btn_msg";
            case 1:
                return "list_msg";
            case 5:
                return "carousel_msg";
            default:
                return "";
        }
    }

    private function visual_template_type_label($db_type)
    {
        switch ((int) $db_type) {
            case 2: return "Botões";
            case 1: return "Lista";
            case 3: return "Enquete";
            case 5: return "Carrossel";
            default: return "Modelo";
        }
    }

    private function visual_template_category($db_type)
    {
        switch ((int) $db_type) {
            case 2: return "buttons";
            case 1: return "list";
            case 3: return "poll";
            case 5: return "carousel";
            default: return "other";
        }
    }

    private function visual_template_icon($db_type)
    {
        switch ((int) $db_type) {
            case 2: return "fad fa-keyboard";
            case 1: return "fad fa-list-alt";
            case 3: return "fad fa-poll-h";
            case 5: return "fad fa-images";
            default: return "fad fa-layer-group";
        }
    }

    private function visual_template_preview($db_type, $payload)
    {
        $text = "";

        switch ((int) $db_type) {
            case 2:
                $text = $payload['text'] ?? $payload['caption'] ?? $payload['title'] ?? $payload['footer'] ?? "";
                break;
            case 1:
                $text = $payload['text'] ?? $payload['title'] ?? $payload['buttonText'] ?? "";
                break;
            case 3:
                $text = $payload['name'] ?? "";
                break;
            case 5:
                $text = $payload['text'] ?? $payload['title'] ?? $payload['footer'] ?? "";
                break;
        }

        $text = trim(strip_tags((string) $text));
        if ($text === '') return "Sem prévia textual. Abra o modelo para revisar o conteúdo completo.";

        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($text, 0, 180, "...");
        }

        return strlen($text) > 180 ? substr($text, 0, 177) . "..." : $text;
    }

    private function visual_template_details($db_type, $payload)
    {
        switch ((int) $db_type) {
            case 2:
                $buttons = is_array($payload['templateButtons'] ?? null) ? count($payload['templateButtons']) : 0;
                return $buttons > 0 ? sprintf("%d botão(ões) configurado(s)", $buttons) : "Mensagem com botões";
            case 1:
                $sections = is_array($payload['sections'] ?? null) ? count($payload['sections']) : 0;
                return $sections > 0 ? sprintf("%d seção(ões) na lista", $sections) : "Mensagem de lista";
            case 3:
                $options = is_array($payload['values'] ?? null) ? count($payload['values']) : 0;
                return $options > 0 ? sprintf("%d opção(ões) de enquete", $options) : "Mensagem de enquete";
            case 5:
                $cards = is_array($payload['cards'] ?? null) ? count($payload['cards']) : 0;
                return $cards > 0 ? sprintf("%d card(s) no carrossel", $cards) : "Mensagem de carrossel";
            default:
                return "Modelo de WhatsApp";
        }
    }

    private function visual_template_edit_url($db_type, $ids)
    {
        $ids = trim((string) $ids);
        if ($ids === '') return '';

        switch ((int) $db_type) {
            case 1:
                return base_url('whatsapp_list_message_template/index/update/' . rawurlencode($ids));
            case 2:
                return base_url('whatsapp_button_template/index/update/' . rawurlencode($ids));
            case 3:
                return base_url('whatsapp_poll_template/index/update/' . rawurlencode($ids));
            case 5:
                return base_url('whatsapp_carousel_template/index/update/' . rawurlencode($ids));
            default:
                return '';
        }
    }

    private function visual_template_meta_status_label($status)
    {
        $status = strtoupper(trim((string) $status));
        switch ($status) {
            case "APPROVED": return "Aprovado na Meta";
            case "PENDING": return "Em análise na Meta";
            case "REJECTED": return "Rejeitado na Meta";
            case "PAUSED": return "Pausado na Meta";
            default: return "";
        }
    }

    public function logout($ids = false){
        $team_id = get_team("id");
        $access_token = get_team("ids");
        $account = db_get("*", TB_ACCOUNTS, ["ids" => $ids, "team_id" => $team_id]);

        if(!$account){
            ms([
                "status" => "error",
                "message" => __("Account does not exist")
            ]);
        }

        $result = wa_get_curl("logout", [ "instance_id" => $account->token, "access_token" => $access_token ]);
        if($result == ""){
            ms([
                "status" => "error",
                "message" => __("Cannot connect WhatsApp server")
            ]);
        }

        ms([
            "status" => $result->status,
            "message" => $result->message
        ]);
    }

    public function reset_plan($user_id = 0){
        if( get_user("is_admin") ){

            $team = db_get("id", TB_TEAM, ["owner" => $user_id]);
            if(!$team){
                ms([
                    "status" => "error",
                    "message" => __("User does not exist")
                ]);
            }

            $stats = db_get("*", TB_WHATSAPP_STATS, ["team_id" => $team->id]);
            if(!$stats){
                ms([
                    "status" => "error",
                    "message" => __("Account does not exist")
                ]);
            }

            db_update(TB_WHATSAPP_STATS, [
                "wa_total_sent_by_month" => 0,
                "wa_time_reset" => 0,
                "next_update" => 0
            ], [ "team_id" => $team->id ]);

            ms([
                "status" => 'success',
                "message" => _("Success")
            ]);
        }

        ms([
            "status" => 'success',
            "message" => _("You don't have permission to access to it")
        ]);
    }
}
