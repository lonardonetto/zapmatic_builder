<?php
// Ativa exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Função para log
function writeLog($message) {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Log inicial
writeLog("Request received");

// Pega o input JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

writeLog("Input raw: " . $input);
writeLog("Input decoded: " . print_r($data, true));

// Verifica se tem os campos necessários
if (empty($data['name'])) {
    $response = ['status' => 'error', 'message' => 'Nome é obrigatório'];
    writeLog("Erro: Nome não fornecido");
} else {
    // Simula sucesso
    $response = [
        'status' => 'success',
        'message' => 'Perfil criado com sucesso',
        'data' => [
            'name' => $data['name'],
            'description' => $data['description'] ?? ''
        ]
    ];
    writeLog("Sucesso: Perfil criado para " . $data['name']);
}

// Log da resposta
writeLog("Resposta: " . print_r($response, true));

// Envia resposta
header('Content-Type: application/json');
echo json_encode($response);
