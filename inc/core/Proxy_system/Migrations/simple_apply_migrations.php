<?php
/**
 * Script simplificado para aplicar migrations do sistema de proxy avançado
 * Este script executa as SQLs diretamente
 */

echo "=== SISTEMA DE PROXY AVANÇADO - APLICAÇÃO DE MIGRATIONS ===\n";
echo "Iniciando aplicação das migrations...\n\n";

// Função para executar SQL diretamente no MySQL
function execute_sql_file($sql_file, $description) {
    echo "Aplicando: $description...\n";
    
    if (!file_exists($sql_file)) {
        echo "❌ ERRO: Arquivo não encontrado: $sql_file\n";
        return false;
    }
    
    // Ler arquivo SQL
    $sql_content = file_get_contents($sql_file);
    
    // Dividir comandos SQL
    $sql_commands = array_filter(array_map('trim', explode(';', $sql_content)));
    
    $success = true;
    foreach ($sql_commands as $sql_command) {
        if (!empty($sql_command) && !str_starts_with(trim($sql_command), '--')) {
            // Executar comando SQL via mysqli diretamente
            $result = execute_sql_command($sql_command);
            if (!$result) {
                echo "❌ ERRO ao executar: " . substr($sql_command, 0, 100) . "...\n";
                $success = false;
            }
        }
    }
    
    if ($success) {
        echo "✅ Sucesso: $description aplicada!\n\n";
    }
    
    return $success;
}

// Função para executar comando SQL individual
function execute_sql_command($sql) {
    // Conectar ao banco usando configuração padrão
    $connection = new mysqli('localhost', 'root', '', 'zapmatic');
    
    if ($connection->connect_error) {
        echo "❌ Erro de conexão: " . $connection->connect_error . "\n";
        return false;
    }
    
    $result = $connection->query($sql);
    $connection->close();
    
    return $result;
}

// Verificar se tabela existe
function table_exists($table_name) {
    $connection = new mysqli('localhost', 'root', '', 'zapmatic');
    
    if ($connection->connect_error) {
        return false;
    }
    
    $result = $connection->query("SHOW TABLES LIKE '$table_name'");
    $exists = $result && $result->num_rows > 0;
    $connection->close();
    
    return $exists;
}

// Lista de migrations
$migrations = [
    'extend_tb_proxies_migration.sql' => 'Extensão da tabela tb_proxies',
    'proxy_health_migration.sql' => 'Criação da tabela tb_proxy_health',
    'proxy_rotation_log_migration.sql' => 'Criação da tabela tb_proxy_rotation_log'
];

$success_count = 0;
$error_count = 0;

// Aplicar migrations
foreach ($migrations as $file => $description) {
    $migration_path = __DIR__ . '/' . $file;
    
    if (execute_sql_file($migration_path, $description)) {
        $success_count++;
    } else {
        $error_count++;
    }
}

// Validação final
echo "=== VALIDAÇÃO DAS MIGRATIONS ===\n";

$tables_to_check = [
    'tb_proxies' => 'Tabela principal de proxies',
    'tb_proxy_health' => 'Tabela de monitoramento de saúde',
    'tb_proxy_rotation_log' => 'Tabela de log de rotações'
];

foreach ($tables_to_check as $table => $description) {
    if (table_exists($table)) {
        echo "✅ $description: OK\n";
    } else {
        echo "❌ $description: TABELA NÃO ENCONTRADA!\n";
    }
}

// Resumo final
echo "\n=== RESUMO FINAL ===\n";
echo "Migrations aplicadas com sucesso: $success_count\n";
echo "Erros encontrados: $error_count\n";

if ($error_count === 0) {
    echo "\n🎉 TODAS AS MIGRATIONS FORAM APLICADAS COM SUCESSO!\n";
    echo "O sistema de proxy avançado está pronto para uso.\n";
} else {
    echo "\n⚠️ ALGUMAS MIGRATIONS APRESENTARAM ERROS.\n";
    echo "Verifique os erros acima e tente novamente.\n";
}

// Função auxiliar para PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }
}
?> 