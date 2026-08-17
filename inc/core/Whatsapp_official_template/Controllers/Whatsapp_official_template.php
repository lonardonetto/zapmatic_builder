<?php
namespace Core\Whatsapp_official_template\Controllers;

class Whatsapp_official_template extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    private function has_permission(){
        if (!function_exists('permission')) return true;
        if (function_exists('is_admin') && is_admin()) return true;
        return permission('whatsapp_official_template');
    }

    public function widget_menu( $params = [] ){
        if (!$this->has_permission()) return '';
        return view('Core\Whatsapp_official_template\Views\widget\menu', ["result" => $params["result"]]);
    }

    public function widget_content( $params = [] ){
        if (!$this->has_permission()) return '';
        $team_id = get_team("id");
        $account = $params["account"] ?? null;
        $account_ids = is_object($account) ? ($account->ids ?? null) : (is_array($account) ? ($account["ids"] ?? null) : null);

        // Filtra por conta Cloud API (evita misturar WABAs/clients)
        $official_templates = [];
        if (!empty($account_ids)) {
            $db = \Config\Database::connect();
            $sql = "SELECT * FROM " . TB_WHATSAPP_TEMPLATE . "
                    WHERE team_id = ? AND type = 6
                      AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.account_ids')) = ?
                    ORDER BY name ASC";
            $official_templates = $db->query($sql, [$team_id, $account_ids])->getResult();
        }

        return view('Core\Whatsapp_official_template\Views\widget\content', [
            "result" => $params["result"],
            "official_templates" => $official_templates,
            "account_ids" => $account_ids,
        ]);
    }
}
