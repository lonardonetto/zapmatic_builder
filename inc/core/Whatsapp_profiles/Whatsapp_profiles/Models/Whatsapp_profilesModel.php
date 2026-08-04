<?php
namespace Core\Whatsapp_profiles\Models;
use CodeIgniter\Model;

class Whatsapp_profilesModel extends Model
{
	public function __construct(){
        $this->config = include realpath( __DIR__."/../Config.php" );
    }
    
	public function block_accounts($path = ""){
        $team_id = get_team("id");
        $accounts = db_fetch("*", TB_ACCOUNTS, "social_network = 'whatsapp' AND category = 'profile' AND team_id = '{$team_id}'");

        // Sync real-time status from Go API for whatsmeow accounts (login_type=3)
        $db = \Config\Database::connect();
        $goBaseUrl = \App\Services\WhatsAppGatewayService::getGoBaseUrl();
        $goStatus = null;
        try {
            $ch = curl_init($goBaseUrl . '/status');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $goStatus = json_decode($response, true);
        } catch (\Throwable $e) {}

        if ($goStatus && ($goStatus['status'] ?? '') === 'success') {
            $connectedIds = [];
            foreach ($goStatus['instances'] ?? [] as $inst) {
                $connectedIds[$inst['id']] = true;
            }

            foreach ($accounts as $account) {
                if ((int)($account->login_type ?? 0) !== 3) continue;
                $token = $account->token ?? '';
                $shouldBeConnected = isset($connectedIds[$token]);
                $currentStatus = (int)($account->status ?? 0);

                if ($shouldBeConnected && $currentStatus !== 1) {
                    $db->table(TB_ACCOUNTS)->where('id', $account->id)->update(['status' => 1]);
                    $account->status = 1;
                } elseif (!$shouldBeConnected && $currentStatus !== 0) {
                    $db->table(TB_ACCOUNTS)->where('id', $account->id)->update(['status' => 0]);
                    $account->status = 0;
                }
            }
        }

        return [
        	"button" => view( 'Core\Whatsapp_profiles\Views\button', [ 'config' => $this->config ] ),
        	"content" => view( 'Core\Whatsapp_profiles\Views\content', [ 'config' => $this->config, 'accounts' => $accounts ] )
        ];
    }

    public function block_social_settings($path = ""){
        return [
            "menu" => view( 'Core\Whatsapp_profiles\Views\settings\menu', [ 'config' => $this->config ] ),
            "content" => view( 'Core\Whatsapp_profiles\Views\settings\content', [ 'config' => $this->config ] )
        ];
    }
}
