<?php
namespace Core\Whatsapp_profile\Controllers;

class Whatsapp_profile extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Whatsapp_profile\Models\Whatsapp_profileModel();
    }
    
    public function index() {
        return redirect()->to(base_url('whatsapp_profiles/oauth'));
    }

    public function info(){
        $team_id = get_team("id");
        $access_token = get_team("ids");
        $ids = post("account");
        $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "login_type" => [1, 2], "ids" => $ids, "team_id" => $team_id]);

        if(!empty($account)){
            $data = [
                "status" => "success",
                "account" => $account,
                "access_token" => $access_token,
            ];
        }else{
            $data = [
                "status" => "error",
                "message" => "WhatsApp account does not exist. Please try again or re-login your WhatsApp account"
            ];

        }

        return view('Core\Whatsapp_profile\Views\info', $data);
    }

    public function logout($ids = false){
        $team_id = get_team("id");
        $access_token = get_team("ids");
        $ids = post("account");
        $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "login_type" => [1, 2], "ids" => $ids, "team_id" => $team_id]);

        if(!empty($account)){
            $data = [
                "status" => "success",
                "account" => $account,
                "access_token" => $access_token,
            ];
        }else{
            $data = [
                "status" => "error",
                "message" => "WhatsApp account does not exist. Please try again or re-login your WhatsApp account"
            ];

        }

        return view('Core\Whatsapp_profile\Views\info', $data);
    }

}
