<?php
namespace Core\Gm_scraper\Controllers;

class Gm_scraper extends \CodeIgniter\Controller
{
    public function __construct()
    {
        $this->config = parse_config( include realpath(__DIR__."/../Config.php") );
        $this->model = new \Core\Gm_scraper\Models\Gm_scraperModel();
    }

    public function index( $page = false ) {
        $team_id = get_team("id");
        
        $jobs = db_fetch("*", "sp_gmscraper_jobs", ["team_id" => $team_id], "id", "DESC");
        
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "content" => view('Core\Gm_scraper\Views\index', [
                "config" => $this->config,
                "jobs" => $jobs
            ])
        ];

        return view('Core\Whatsapp\Views\index', $data);
    }
    
    public function update($ids = "")
    {
        $team_id = get_team("id");
        $job = db_get("*", "sp_gmscraper_jobs", ["ids" => $ids, "team_id" => $team_id]);
        
        $phonebooks = db_fetch("*", "sp_whatsapp_contacts", ["team_id" => $team_id], "name", "ASC");

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "content" => view('Core\Gm_scraper\Views\update', [
                "config" => $this->config,
                "job" => $job,
                "phonebooks" => $phonebooks
            ])
        ];

        return view('Core\Whatsapp\Views\index', $data);
    }
    
    public function save()
    {
        $team_id = get_team("id");
        $name = post('name');
        $keyword = post('keyword');
        $location = post('location');
        $target_phonebook = post('target_phonebook');
        $limit_leads = (int)post('limit_leads');
        $delay_seconds = (int)post('delay_seconds');
        $ddi = post('ddi') ?: '55';
        $proxy = post('proxy');
        $ids = post('ids');

        if(empty($name)){
            $name = $keyword . ' - ' . $location;
        }

        if(empty($keyword)){
            ms([
                "status" => "error",
                "message" => __('Palavra-chave é obrigatória')
            ]);
        }
        
        if(empty($location)){
            ms([
                "status" => "error",
                "message" => __('Localização é obrigatória')
            ]);
        }
        
        if(empty($target_phonebook)){
            ms([
                "status" => "error",
                "message" => __('Selecione uma lista de contatos para salvar os leads')
            ]);
        }

        $data = [
            "team_id" => $team_id,
            "name" => $name,
            "keyword" => $keyword,
            "location" => $location,
            "target_phonebook" => $target_phonebook,
            "limit_leads" => $limit_leads > 0 ? $limit_leads : 100,
            "delay_seconds" => $delay_seconds > 0 ? $delay_seconds : 30,
            "ddi" => preg_replace('/\D/', '', $ddi),
            "proxy" => $proxy,
            "changed" => time()
        ];

        if(empty($ids)){
            $data["ids"] = ids();
            $data["status"] = 0; // pending
            $data["current_count"] = 0;
            $data["created"] = time();
            
            db_insert("sp_gmscraper_jobs", $data);
        }else{
            db_update("sp_gmscraper_jobs", $data, ["ids" => $ids, "team_id" => $team_id]);
        }

        ms([
            "status" => "success",
            "message" => __('Tarefa salva com sucesso')
        ]);
    }
    
    public function status_action($action, $ids){
        $team_id = get_team("id");
        if( empty($ids) ) return false;

        $new_status = 0;
        if($action === 'pause'){
            $new_status = 4; // paused
        } elseif($action === 'resume'){
            $new_status = 0; // set to pending so daemon picks it up
        }

        db_update("sp_gmscraper_jobs", ["status" => $new_status, "changed" => time()], ["ids" => $ids, "team_id" => $team_id]);

        ms([
            "status" => "success",
            "message" => __('Status atualizado com sucesso')
        ]);
    }
    
    public function export_csv($job_id)
    {
        $team_id = get_team("id");
        $job = db_get("*", "sp_gmscraper_jobs", ["ids" => $job_id, "team_id" => $team_id]);
        if(!$job){
            return redirect()->to( get_module_url() );
        }

        $leads = db_fetch("*", "sp_gmscraper_leads", ["job_id" => $job->id, "team_id" => $team_id], "id", "ASC");

        $filename = "gmscraper_leads_" . $job->keyword . "_" . date("Ymd_His") . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM
        fputs($output, "\xEF\xBB\xBF");
        
        fputcsv($output, ['Nome', 'Telefone', 'Nota', 'Avaliações', 'Endereço', 'Site']);

        if(!empty($leads)){
            foreach($leads as $lead){
                fputcsv($output, [
                    $lead->name,
                    $lead->phone,
                    $lead->rating,
                    $lead->reviews,
                    $lead->address,
                    $lead->website
                ]);
            }
        }
        fclose($output);
        exit;
    }
    
    public function delete(){
        $team_id = get_team("id");
        $ids = post('id'); // ajax action form envia 'id' ou array

        if( empty($ids) ){
            ms([
                "status" => "error",
                "message" => __('Selecione um item para deletar')
            ]);
        }

        if( is_array($ids) ){
            foreach ($ids as $id) {
                db_delete("sp_gmscraper_jobs", ['ids' => $id, "team_id" => $team_id]);
            }
        } elseif( is_string($ids) ) {
            db_delete("sp_gmscraper_jobs", ['ids' => $ids, "team_id" => $team_id]);
        }

        ms([
            "status" => "success",
            "message" => __('Sucesso')
        ]);
    }
}
