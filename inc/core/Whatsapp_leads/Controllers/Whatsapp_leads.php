<?php

namespace Core\Whatsapp_leads\Controllers;

class Whatsapp_leads extends \CodeIgniter\Controller
{
    protected $config;
    protected $model;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Whatsapp_leads\Models\Whatsapp_leadsModel();
    }

    protected function ensureAccess($ajax = false)
    {
        if (permission('whatsapp_leads')) {
            return true;
        }

        if ($ajax || service('request')->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => __('You do not have permission to access this feature')
            ]);
        }

        redirect_to(base_url('dashboard'));
        return false;
    }

    public function index($page = false)
    {
        $access = $this->ensureAccess();
        if ($access !== true) {
            return $access;
        }

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $data_content = [
            'config' => $this->config,
            'instances' => $this->model->get_instances(),
        ];

        $data['content'] = view('Core\\Whatsapp_leads\\Views\\content', $data_content);

        return view('Core\\Whatsapp\\Views\\index', $data);
    }

    public function ajax_list()
    {
        $access = $this->ensureAccess(true);
        if ($access !== true) {
            return $access;
        }

        try {
            $total_items = $this->model->get_list(false);
            $result = $this->model->get_list(true);

            $data = [
                "result" => $result,
                "config" => $this->config
            ];

            return $this->response->setJSON([
                "total_items" => $total_items,
                "data" => view('Core\\Whatsapp_leads\\Views\\ajax_list', $data)
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[whatsapp_leads.ajax_list] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function export()
    {
        $access = $this->ensureAccess();
        if ($access !== true) {
            return $access;
        }

        $filename = 'whatsapp_leads';
        $rows = $this->model->get_all_for_export();

        header("Content-Description: File Transfer");
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=" . $filename . '_' . date('Ymd_His') . ".csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Telefone',
            'Nome'
        ]);

        if (!empty($rows)) {
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row->phone_number,
                    $row->name,
                ]);
            }
        }

        fclose($output);
        exit;
    }

    public function delete()
    {
        $access = $this->ensureAccess(true);
        if ($access !== true) {
            return $access;
        }

        $ids = post('ids');
        if (empty($ids) || !is_array($ids)) {
            ms([
                "status" => "error",
                "message" => __('Selecione pelo menos um lead para excluir')
            ]);
        }

        $this->model->delete_leads($ids);

        ms([
            "status" => "success",
            "message" => __('Leads removidos com sucesso')
        ]);
    }
}
