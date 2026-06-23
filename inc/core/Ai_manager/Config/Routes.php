<?php
$config = include realpath(__DIR__ . "/../Config.php");
if (!defined('MODULE_CONFIG')) {
    define("MODULE_CONFIG", $config);
}

if (url_is($config['id']) || url_is($config['id'] . '/*')) {
    $routes->setDefaultNamespace(ucfirst($config['folder']) . "/" . ucfirst($config['id']) . "/Controllers");
}

$routes->group('ai_manager', ['namespace' => 'Core\Ai_manager\Controllers'], function($routes) {
    $routes->get('/', 'Ai_manager::index');
    $routes->post('save', 'Ai_manager::save');
    $routes->post('test_connection', 'Ai_manager::test_connection');
    $routes->get('models', 'Ai_manager::models');
});
