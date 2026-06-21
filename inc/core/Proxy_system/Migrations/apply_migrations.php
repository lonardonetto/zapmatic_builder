<?php
/**
 * Script para aplicar migrations do sistema de proxy avançado
 * 
 * Executa as seguintes migrations:
 * 1. Extensão da tabela tb_proxies
 * 2. Criação da tabela tb_proxy_health
 * 3. Criação da tabela tb_proxy_rotation_log
 * 
 * Uso: 
 * - Via CLI: php apply_migrations.php
 * - Via Web: acesse apply_migrations.php no navegador
 */

// Definir se está rodando via CLI ou Web
$is_cli = php_sapi_name() === 'cli';

if ($is_cli) {
    echo "=== SISTEMA DE PROXY AVANÇADO - APLICAÇÃO DE MIGRATIONS ===\n";
    echo "Iniciando aplicação das migrations...\n\n";
} else {
    echo "<h2>Sistema de Proxy Avançado - Aplicação de Migrations</h2>";
    echo "<p>Iniciando aplicação das migrations...</p>";
    echo "<pre>";
}

// Carregar configuração do banco de dados 
// Definir constante do FCPATH para funcionar em qualquer ambiente
if (!defined('FCPATH')) {
    define('FCPATH', realpath(dirname(__FILE__) . '/../../../../') . DIRECTORY_SEPARATOR);
}

// Carregar CodeIgniter para usar suas configurações
require_once FCPATH . 'app/Config/Boot/production.php';
require_once FCPATH . 'app/Config/Boot/development.php';

// Definir ambiente se não estiver definido
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

// Configurações básicas do CodeIgniter
$systemDirectory = FCPATH . 'system';
$applicationDirectory = FCPATH . 'app';

if (!defined('BASEPATH')) {
    define('BASEPATH', $systemDirectory . DIRECTORY_SEPARATOR);
}

if (!defined('APPPATH')) {
    define('APPPATH', $applicationDirectory . DIRECTORY_SEPARATOR);
}

// Carregar autoloader e configurações básicas
if (file_exists(FCPATH . 'vendor/autoload.php')) {
    require_once FCPATH . 'vendor/autoload.php';
}

// Carregar arquivo de função db_get
require_once FCPATH . 'app/Helpers/Common_helper.php';

// Função para conectar ao banco usando a conexão do CodeIgniter
function get_database_connection() {
    // Usar a mesma configuração que as funções db_get usam
    $db = \Config\Database::connect();
    return $db;
}

try {
    // Conectar ao banco de dados
    $db = get_database_connection();
    
    // Lista de migrations na ordem correta
    $migrations = [
        'extend_tb_proxies_migration.sql' => 'Extensão da tabela tb_proxies',
        'proxy_health_migration.sql' => 'Criação da tabela tb_proxy_health',
        'proxy_rotation_log_migration.sql' => 'Criação da tabela tb_proxy_rotation_log'
    ];
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($migrations as $file => $description) {
        $output = $is_cli ? "Aplicando: $description...\n" : "Aplicando: $description...<br>";
        echo $output;
        
        $migration_path = __DIR__ . '/' . $file;
        
        if (!file_exists($migration_path)) {
            $error = "❌ ERRO: Arquivo de migration não encontrado: $file\n";
            echo $is_cli ? $error : str_replace("\n", "<br>", $error);
            $error_count++;
            continue;
        }
        
        try {
            // Ler e executar o arquivo SQL
            $sql_content = file_get_contents($migration_path);
            
            // Dividir por comandos SQL (separados por ';')
            $sql_commands = array_filter(array_map('trim', explode(';', $sql_content)));
            
            foreach ($sql_commands as $sql_command) {
                if (!empty($sql_command) && !str_starts_with(trim($sql_command), '--')) {
                    $db->query($sql_command);
                }
            }
            
            $success = "✅ Sucesso: $description aplicada com sucesso!\n";
            echo $is_cli ? $success : str_replace("\n", "<br>", $success);
            $success_count++;
            
        } catch (Exception $e) {
            $error = "❌ ERRO ao aplicar $description: " . $e->getMessage() . "\n";
            echo $is_cli ? $error : str_replace("\n", "<br>", $error);
            $error_count++;
        }
        
        echo $is_cli ? "\n" : "<br>";
    }
    
    // Validar se as tabelas foram criadas corretamente
    echo $is_cli ? "=== VALIDAÇÃO DAS MIGRATIONS ===\n" : "<h3>Validação das Migrations</h3>";
    
    $tables_to_check = [
        'tb_proxies' => 'Tabela principal de proxies',
        'tb_proxy_health' => 'Tabela de monitoramento de saúde',
        'tb_proxy_rotation_log' => 'Tabela de log de rotações'
    ];
    
    foreach ($tables_to_check as $table => $description) {
        if ($db->tableExists($table)) {
            $count = $db->table($table)->countAll();
            $msg = "✅ $description: OK ($count registros)\n";
            echo $is_cli ? $msg : str_replace("\n", "<br>", $msg);
        } else {
            $msg = "❌ $description: TABELA NÃO ENCONTRADA!\n";
            echo $is_cli ? $msg : str_replace("\n", "<br>", $msg);
        }
    }
    
    // Verificar novos campos em tb_proxies
    echo $is_cli ? "\n=== VERIFICAÇÃO DE NOVOS CAMPOS ===\n" : "<h3>Verificação de Novos Campos</h3>";
    
    $new_fields = ['timeout', 'retry_count', 'max_connections', 'health_score'];
    
    // Obter campos da tabela tb_proxies
    $fields = $db->getFieldNames('tb_proxies');
    
    foreach ($new_fields as $field) {
        if (in_array($field, $fields)) {
            $msg = "✅ Campo '$field' adicionado com sucesso\n";
            echo $is_cli ? $msg : str_replace("\n", "<br>", $msg);
        } else {
            $msg = "❌ Campo '$field' não foi encontrado\n";
            echo $is_cli ? $msg : str_replace("\n", "<br>", $msg);
        }
    }
    
    // Resumo final
    echo $is_cli ? "\n=== RESUMO FINAL ===\n" : "<h3>Resumo Final</h3>";
    echo $is_cli ? "Migrations aplicadas com sucesso: $success_count\n" : "Migrations aplicadas com sucesso: $success_count<br>";
    echo $is_cli ? "Erros encontrados: $error_count\n" : "Erros encontrados: $error_count<br>";
    
    if ($error_count === 0) {
        $msg = "\n🎉 TODAS AS MIGRATIONS FORAM APLICADAS COM SUCESSO!\n";
        $msg .= "O sistema de proxy avançado está pronto para uso.\n";
        echo $is_cli ? $msg : str_replace("\n", "<br>", $msg);
    } else {
        $msg = "\n⚠️ ALGUMAS MIGRATIONS APRESENTARAM ERROS.\n";
        $msg .= "Verifique os erros acima e tente novamente.\n";
        echo $is_cli ? $msg : str_replace("\n", "<br>", $msg);
    }
    
} catch (Exception $e) {
    $error = "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo $is_cli ? $error : str_replace("\n", "<br>", $error);
    
    if ($is_cli) {
        exit(1);
    }
}

if (!$is_cli) {
    echo "</pre>";
    echo "<p><a href='javascript:history.back()'>← Voltar</a></p>";
}

// Função auxiliar para PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }
}
?> 