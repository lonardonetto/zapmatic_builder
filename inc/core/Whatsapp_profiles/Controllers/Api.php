<?php
namespace Core\Whatsapp_profiles\Controllers;

class Api extends \CodeIgniter\Controller
{
    protected $helpers = ['url', 'general'];
    protected $response;

    public function __construct() {
        parent::__construct();
        
        // Força o tipo de resposta como JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        
        // Remove qualquer output buffering
        while (ob_get_level()) ob_end_clean();
        
        // Verifica se é uma requisição AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Only AJAX requests are allowed'
            ]);
            exit;
        }
    }

    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    public function test() {
        $this->json([
            'status' => 'success',
            'message' => 'API endpoint working',
            'post_data' => $_POST,
            'get_data' => $_GET,
            'time' => date('Y-m-d H:i:s'),
            'is_ajax' => true
        ]);
    }

    public function create_profile() {
        try {
            $team_id = get_team("id");
            if (!$team_id) {
                throw new \Exception('Team ID não encontrado');
            }

            $name = post('name');
            $description = post('description');

            if(empty($name)) {
                throw new \Exception('O nome do perfil é obrigatório');
            }

            if(!check_number_account("whatsapp", "profile")) {
                throw new \Exception('Você atingiu o limite de perfis permitidos');
            }

            // Gera um novo instance_id
            $instance_id = strtoupper(uniqid());
            
            // Remove qualquer sessão antiga inativa
            db_delete(TB_WHATSAPP_SESSIONS, ["status" => 0, "team_id" => $team_id]);

            // Cria o perfil
            $profile_data = [
                "ids" => ids(),
                "team_id" => $team_id,
                "social_network" => "whatsapp",
                "category" => "profile",
                "name" => $name,
                "description" => $description,
                "status" => 0,
                "token" => $instance_id,
                "changed" => date('Y-m-d H:i:s'),
                "created" => date('Y-m-d H:i:s')
            ];

            $profile_insert = db_insert(TB_ACCOUNTS, $profile_data);
            if (!$profile_insert) {
                throw new \Exception('Erro ao criar perfil no banco de dados');
            }

            // Cria a sessão do WhatsApp
            $session_data = [
                "ids" => ids(),
                "instance_id" => $instance_id,
                "team_id" => $team_id,
                "data" => NULL,
                "status" => 0
            ];

            $session_insert = db_insert(TB_WHATSAPP_SESSIONS, $session_data);
            if (!$session_insert) {
                // Se falhar ao criar a sessão, remove o perfil
                db_delete(TB_ACCOUNTS, ["token" => $instance_id]);
                throw new \Exception('Erro ao criar sessão do WhatsApp');
            }

            $this->json([
                'status' => 'success',
                'message' => 'Perfil criado com sucesso',
                'instance_id' => $instance_id
            ]);

        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
