<?php
namespace Core\Whatsapp_flow\Models;
use CodeIgniter\Model;

class Whatsapp_flowModel extends Model
{
    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
    }

    public function block_plans()
    {
        return [
            "tab" => 15,
            "position" => 340,
            "label" => __("Whatsapp tool"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => __("WhatsApp Flows"),
                ],
            ]
        ];
    }

    public function block_whatsapp()
    {
        return [
            "position" => 7050,
            "config" => $this->config
        ];
    }

    public function get_list($return_data = true)
    {
        $team_id = get_team("id");
        $current_page = max((int) post("current_page") - 1, 0);
        $per_page = (int) post("per_page");
        $keyword = trim((string) post("keyword"));

        if ($per_page <= 0) {
            $per_page = 30;
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_FLOWS . " as f");
        $builder->select("f.*, a.name as account_name, e.endpoint_status");
        $builder->join(TB_ACCOUNTS . " as a", "a.id = f.account_id", "left");
        $builder->join(TB_WHATSAPP_FLOW_ENDPOINTS . " as e", "e.id = f.endpoint_id", "left");
        $builder->where("f.team_id", $team_id);

        if ($keyword !== "") {
            $builder->groupStart();
            $builder->like("f.name", $keyword);
            $builder->orLike("f.slug", $keyword);
            $builder->orLike("f.meta_flow_id", $keyword);
            $builder->orLike("a.name", $keyword);
            $builder->groupEnd();
        }

        if (!$return_data) {
            return $builder->countAllResults();
        }

        $builder->limit($per_page, $per_page * $current_page);
        $builder->orderBy("f.created", "DESC");

        $query = $builder->get();
        $result = $query ? $query->getResult() : [];
        if ($query) {
            $query->freeResult();
        }

        return $result;
    }
}
