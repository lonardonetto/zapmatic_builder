<?php
$config = include realpath(__DIR__ . "/../Config.php");
if (!defined('MODULE_CONFIG')) {
    define("MODULE_CONFIG", $config);
}

if (
    isset($config['menu']) &&
    isset($config['menu']['sub_menu']) &&
    isset($config['menu']['sub_menu']["id"]) &&
    (url_is($config['menu']['sub_menu']["id"]) || url_is($config['menu']['sub_menu']["id"] . '/*'))
) {
    $routes->setDefaultNamespace(ucfirst($config['folder']) . "/" . ucfirst($config['menu']['sub_menu']["id"]) . "/Controllers");
} else if (url_is($config["id"]) || url_is($config["id"] . '/*')) {
    $routes->setDefaultNamespace(ucfirst($config['folder']) . "/" . ucfirst($config['id']) . "/Controllers");
}

if (isset($routes)) {
    $controller = '\Core\Whatsapp_flow\Controllers\Whatsapp_flow::';
    $routes->match(['get', 'post'], 'whatsapp_flow', $controller . 'index');
    $routes->match(['get', 'post'], 'whatsapp_flow/index', $controller . 'index');
    $routes->match(['get', 'post'], 'whatsapp_flow/index/(:any)', $controller . 'index/$1');
    $routes->match(['get', 'post'], 'whatsapp_flow/index/(:any)/(:any)', $controller . 'index/$1/$2');
    $routes->post('whatsapp_flow/ajax_list', $controller . 'ajax_list');
    $routes->match(['get', 'post'], 'whatsapp_flow/save', $controller . 'save');
    $routes->match(['get', 'post'], 'whatsapp_flow/save/(:any)', $controller . 'save/$1');
    $routes->match(['get', 'post'], 'whatsapp_flow/delete', $controller . 'delete');
    $routes->match(['get', 'post'], 'whatsapp_flow/meta_push_draft', $controller . 'meta_push_draft');
    $routes->match(['get', 'post'], 'whatsapp_flow/meta_push_draft/(:any)', $controller . 'meta_push_draft/$1');
    $routes->match(['get', 'post'], 'whatsapp_flow/meta_publish', $controller . 'meta_publish');
    $routes->match(['get', 'post'], 'whatsapp_flow/meta_publish/(:any)', $controller . 'meta_publish/$1');
    $routes->match(['get', 'post'], 'whatsapp_flow/meta_sync', $controller . 'meta_sync');
    $routes->match(['get', 'post'], 'whatsapp_flow/meta_sync/(:any)', $controller . 'meta_sync/$1');
    $routes->match(['get', 'post'], 'whatsapp_flow/meta_pull_account', $controller . 'meta_pull_account');
    $routes->match(['get', 'post'], 'whatsapp_flow/meta_pull_account/(:any)', $controller . 'meta_pull_account/$1');
    $routes->match(['get', 'post'], 'whatsapp_flow/endpoint_sync', $controller . 'endpoint_sync');
    $routes->match(['get', 'post'], 'whatsapp_flow/endpoint_sync/(:any)', $controller . 'endpoint_sync/$1');
    $routes->match(['get', 'post'], 'whatsapp_flow/endpoint_refresh', $controller . 'endpoint_refresh');
    $routes->match(['get', 'post'], 'whatsapp_flow/endpoint_refresh/(:any)', $controller . 'endpoint_refresh/$1');
}

if (file_exists(realpath(__DIR__ . "/../Helpers"))) {
    $helperPath = realpath(__DIR__ . "/../Helpers/") . "/";
    $helpers = scandir($helperPath);
    foreach ($helpers as $helper) {
        if ($helper === '.' || $helper === '..' || stripos($helper, "_helper.php") === false) {
            continue;
        }

        if (file_exists($helperPath . $helper)) {
            require_once($helperPath . $helper);
        }
    }
}
