<?php
$installation = false;
if($installation){
    header("Location: ./install/index.php");
    exit;
}

// Path to the front controller (this file)
define('DEMO', FALSE);
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('COREPATH', __DIR__ . DIRECTORY_SEPARATOR."inc/");

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestPath = rtrim((string) parse_url($requestUri, PHP_URL_PATH), '/');

if ($requestPath === '/api/send_pedido' || $requestPath === '/index.php/api/send_pedido') {
    $maskValue = static function ($value) {
        $value = (string) $value;
        $length = strlen($value);

        if ($length <= 8) {
            return $value;
        }

        return substr($value, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($value, -4);
    };

    $maskArray = static function (array $data) use ($maskValue) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $value;
                continue;
            }

            if (in_array((string) $key, ['access_token', 'authorization'], true)) {
                $data[$key] = $maskValue($value);
            }
        }

        return $data;
    };

    $bootstrapLog = [
        'time' => date('Y-m-d H:i:s'),
        'event' => 'bootstrap',
        'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'host' => $_SERVER['HTTP_HOST'] ?? null,
        'uri' => $requestUri,
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? null,
        'get' => $maskArray($_GET),
        'post' => $maskArray($_POST),
    ];

    @file_put_contents(
        __DIR__ . '/writable/logs/send_pedido_bootstrap.log',
        json_encode($bootstrapLog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND
    );
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// Ensure the current directory is pointing to the front controller's directory
chdir(__DIR__);

// Load our paths config file
// This is the line that might need to be changed, depending on your folder structure.
$pathsConfig = FCPATH . 'app/Config/Paths.php';
// ^^^ Change this if you move your application folder
require realpath($pathsConfig) ?: $pathsConfig;

$paths = new Config\Paths();

// Location of the framework bootstrap file.
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app       = require realpath($bootstrap) ?: $bootstrap;

/*
 *---------------------------------------------------------------
 * LAUNCH THE APPLICATION
 *---------------------------------------------------------------
 * Now that everything is setup, it's time to actually fire
 * up the engines and make this app do its thang.
 */
$app->run();
