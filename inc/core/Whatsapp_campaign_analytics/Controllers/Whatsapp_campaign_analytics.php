<?php
namespace Core\Whatsapp_campaign_analytics\Controllers;

class Whatsapp_campaign_analytics extends \CodeIgniter\Controller
{
    public $config;
    public $model;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Whatsapp_campaign_analytics\Models\Whatsapp_campaign_analyticsModel();
    }

    protected function canAccess()
    {
        return permission("whatsapp_campaign_analytics") || (int) permission("cloud_api_enabled") === 1;
    }

    protected function denyAccess($ajax = false)
    {
        if ($ajax) {
            header('Content-Type: application/json');
            ms([
                "status" => "error",
                "message" => __("Cloud API report access is not enabled for this account.")
            ]);
        }

        return redirect()->to(base_url('whatsapp'));
    }

    public function index()
    {
        if (!$this->canAccess()) {
            return $this->denyAccess();
        }

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

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

        $data['content'] = view('Core\Whatsapp_campaign_analytics\Views\content', $data_content);
        return view('Core\Whatsapp\Views\index', $data);
    }

    public function ajax_list()
    {
        if (!$this->canAccess()) {
            return $this->denyAccess(true);
        }

        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);
        $data = [
            "result" => $result,
            "config" => $this->config
        ];
        header('Content-Type: application/json');
        ms([
            "total_items" => $total_items,
            "data" => view('Core\Whatsapp_campaign_analytics\Views\ajax_list', $data)
        ]);
    }

    public function details($schedule_id = "")
    {
        if (!$this->canAccess()) {
            return $this->denyAccess();
        }

        if (empty($schedule_id)) {
            return redirect()->to(base_url('whatsapp_campaign_analytics'));
        }

        $team_id = get_team("id");
        $results = $this->model->get_details($schedule_id);
        
        // Obter nome da campanha (fallback para sp_whatsapp_schedules)
        $campaign_name = "Campanha " . $schedule_id;
        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_MESSAGE_STATUS);
        $builder->select('campaign_name');
        $builder->where('schedule_id', $schedule_id);
        $builder->where('team_id', $team_id);
        $builder->where('campaign_name IS NOT NULL');
        $builder->limit(1);
        $row = $builder->get()->getRow();
        if ($row && !empty($row->campaign_name)) {
            $campaign_name = $row->campaign_name;
        } else {
            // Fallback: buscar nome da tabela de schedules
            $schedule = db_get('name', TB_WHATSAPP_SCHEDULES, ['id' => $schedule_id, 'team_id' => $team_id]);
            if ($schedule && !empty($schedule->name)) {
                $campaign_name = $schedule->name;
            }
        }

        $data = [
            "title" => "Detalhes da Campanha",
            "desc" => $campaign_name,
        ];

        $data_content = [
            'results' => $results,
            'campaign_name' => $campaign_name,
            'schedule_id' => $schedule_id,
            'config'  => $this->config,
        ];

        $data['content'] = view('Core\Whatsapp_campaign_analytics\Views\details', $data_content);
        return view('Core\Whatsapp\Views\index', $data);
    }

    public function export($schedule_id = "")
    {
        if (!$this->canAccess()) {
            return $this->denyAccess();
        }

        if (empty($schedule_id)) {
            return false;
        }

        $results = $this->model->get_details($schedule_id);
        if (empty($results)) {
            return false;
        }

        $team_id = get_team("id");
        $campaign_name = "Campanha_" . $schedule_id;
        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_MESSAGE_STATUS);
        $builder->select('campaign_name');
        $builder->where('schedule_id', $schedule_id);
        $builder->where('team_id', $team_id);
        $builder->where('campaign_name IS NOT NULL');
        $builder->limit(1);
        $row = $builder->get()->getRow();
        if ($row && !empty($row->campaign_name)) {
            $campaign_name = $row->campaign_name;
        } else {
            // Fallback: buscar nome da tabela de schedules
            $schedule = db_get('name', TB_WHATSAPP_SCHEDULES, ['id' => $schedule_id, 'team_id' => $team_id]);
            if ($schedule && !empty($schedule->name)) {
                $campaign_name = $schedule->name;
            }
        }

        $file = "relatorio_" . slugify($campaign_name) . ".xls";
        
        $report = view('Core\Whatsapp_campaign_analytics\Views\export', ['results' => $results, 'campaign_name' => $campaign_name]);
        
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        echo $report;
    }

    public function delete($schedule_id = "")
    {
        if (!$this->canAccess()) {
            return $this->denyAccess(true);
        }

        $team_id = get_team("id");

        if (empty($schedule_id) || empty($team_id)) {
            ms([
                "status"  => "error",
                "message" => __("Campos obrigatórios não preenchidos")
            ]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_MESSAGE_STATUS);
        $builder->where('schedule_id', $schedule_id);
        $builder->where('team_id', $team_id);
        $builder->delete();

        ms([
            "status"  => "success",
            "message" => __("Relatório excluído com sucesso")
        ]);
    }
}
