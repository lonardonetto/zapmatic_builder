<?php
// Força o tipo de resposta como JSON
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept');
header('X-Content-Type-Options: nosniff');

// Desativa a exibição de erros
ini_set('display_errors', 0);
error_reporting(0);

// Remove qualquer output buffering
while (ob_get_level()) ob_end_clean();

// Log para debug
$input = file_get_contents('php://input');
$headers = getallheaders();

// Verifica se é uma requisição AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only AJAX requests are allowed',
        'debug' => [
            'input' => $input,
            'headers' => $headers,
            'method' => $_SERVER['REQUEST_METHOD'],
            'request' => $_REQUEST
        ]
    ]);
    exit;
}

// Carrega os helpers necessários
require_once __DIR__ . '/../../../../system/helpers/general_helper.php';

// Determina a ação baseado no parâmetro
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'test':
        echo json_encode([
            'status' => 'success',
            'message' => 'API endpoint working',
            'post_data' => $_POST,
            'get_data' => $_GET,
            'time' => date('Y-m-d H:i:s'),
            'is_ajax' => true
        ]);
        break;

    case 'create_profile':
        try {
            $team_id = get_team("id");
            if (!$team_id) {
                throw new Exception('Team ID não encontrado');
            }

            $name = post('name');
            $description = post('description');

            if(empty($name)) {
                throw new Exception('O nome do perfil é obrigatório');
            }

            if(!check_number_account("whatsapp", "profile")) {
                throw new Exception('Você atingiu o limite de perfis permitidos');
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
                throw new Exception('Erro ao criar perfil no banco de dados');
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
                throw new Exception('Erro ao criar sessão do WhatsApp');
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Perfil criado com sucesso',
                'instance_id' => $instance_id
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Action not found'
        ]);
        break;
}

exit;
