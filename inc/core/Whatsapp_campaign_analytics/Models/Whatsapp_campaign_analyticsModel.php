<?php
namespace Core\Whatsapp_campaign_analytics\Models;

class Whatsapp_campaign_analyticsModel
{
    protected $db;

    public function __construct(){
        $this->db = \Config\Database::connect();
    }

    public function get_list($return_data = true)
    {
        $team_id = get_team("id");
        $statusTable = TB_WHATSAPP_MESSAGE_STATUS;
        $scheduleTable = TB_WHATSAPP_SCHEDULES;

        $sql_base = "
            SELECT 
                ms.schedule_id,
                COALESCE(MAX(ms.campaign_name), s.name) as campaign_name,
                MIN(ms.created) as start_date,
                COUNT(*) as total_messages,
                SUM(CASE WHEN ms.status IN ('sent', 'delivered', 'read') THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN ms.status IN ('delivered', 'read') THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN ms.status='read' THEN 1 ELSE 0 END) as read_count,
                SUM(CASE WHEN ms.status='failed' THEN 1 ELSE 0 END) as failed_count
            FROM {$statusTable} ms
            LEFT JOIN {$scheduleTable} s ON s.id = ms.schedule_id
            WHERE ms.team_id = ?
            GROUP BY ms.schedule_id, s.name
            ORDER BY start_date DESC
        ";

        if (!$return_data) {
            $query = $this->db->query("SELECT COUNT(*) as total FROM ({$sql_base}) as t", [$team_id]);
            $result = $query->getRow();
            return $result ? $result->total : 0;
        }

        $page = (int)post("current_page");
        if ($page == 0) $page = 1;
        $per_page = 30;
        $offset = ($page - 1) * $per_page;

        $sql_paginated = $sql_base . " LIMIT {$per_page} OFFSET {$offset}";
        $query = $this->db->query($sql_paginated, [$team_id]);

        return $query->getResult();
    }

    public function block_plans(){
        return [
            "tab" => 15,
            "position" => 1000,
            "label" => __("Whatsapp tool"),
            "items" => [
                [
                    "id" => "whatsapp_campaign_analytics",
                    "name" => 'Relatórios <span style="color: #25d366;">Cloud API</span>',
                ],
            ]
        ];
    }

    public function block_whatsapp(){
        return array(
            "position" => 1000,
            "config" => parse_config( include realpath( __DIR__."/../Config.php" ) )
        );
    }

    public function get_details($schedule_id)
    {
        $team_id = get_team("id");
        $builder = $this->db->table(TB_WHATSAPP_MESSAGE_STATUS);
        $builder->select('*');
        $builder->where('team_id', $team_id);
        $builder->where('schedule_id', $schedule_id);
        $builder->orderBy('created', 'DESC');
        $query = $builder->get();
        return $query->getResult();
    }
}
