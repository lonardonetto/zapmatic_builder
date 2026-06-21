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

$bot_builder_routes = function($routes) {
    $routes->get('/', 'Bot_builder::index');
    $routes->post('/', 'Bot_builder::index');
    $routes->get('create', 'Bot_builder::create');
    $routes->post('create', 'Bot_builder::create');
    
    // Templates Marketplace
    $routes->get('templates', 'Bot_builder::templates');
    $routes->get('templates/category/(:any)', 'Bot_builder::templates/$1');
    $routes->get('templates/preview/(:num)', 'Bot_builder::template_preview/$1');
    $routes->post('templates/use/(:num)', 'Bot_builder::use_template/$1');
    $routes->get('templates/use/(:num)', 'Bot_builder::use_template/$1');
    $routes->post('templates/import', 'Bot_builder::import_template_json');
    
    // Legacy marketplace route (redirect)
    $routes->get('marketplace', 'Bot_builder::templates');
    $routes->get('install_template/(:num)', 'Bot_builder::install_template/$1');
    $routes->post('install_template/(:num)', 'Bot_builder::install_template/$1');
    
    // Bot Editor & Management
    $routes->get('(:num)/editor', 'Bot_builder::editor/$1');
    $routes->get('(:num)/sessions', 'Bot_builder::sessions/$1');
    $routes->get('(:num)/analytics', 'Bot_builder::analytics/$1');
    $routes->get('(:num)/export', 'Bot_builder::export/$1');
    $routes->get('(:num)/overview', 'Bot_builder::overview/$1');
    $routes->post('save', 'Bot_builder::save');
    $routes->post('upload-media', 'Bot_builder::upload_media');
    $routes->get('native-templates', 'Bot_builder::native_templates');
    $routes->get('native-template/(:any)', 'Bot_builder::native_template/$1');
    $routes->post('delete', 'Bot_builder::delete');
    $routes->post('webhook', 'Bot_builder::webhook');
    $routes->get('export/(:num)', 'Bot_builder::export/$1');
    $routes->post('import', 'Bot_builder::import');
    
    // Start from scratch
    $routes->post('start-scratch', 'Bot_builder::start_scratch');
    $routes->post('import-file', 'Bot_builder::import_file');
    
    // WhatsApp Instance Integrations
    $routes->get('instances', 'Bot_builder::get_instances');
    $routes->get('integrations/(:num)', 'Bot_builder::get_bot_integrations/$1');
    $routes->post('link-instance', 'Bot_builder::link_instance');
    $routes->post('unlink-instance', 'Bot_builder::unlink_instance');
    
    // Bot Settings (keywords, enable/disable)
    $routes->post('save-bot-settings', 'Bot_builder::save_bot_settings');
    $routes->get('get-bot-settings/(:num)', 'Bot_builder::get_bot_settings/$1');
};

$routes->group('bot_builder', ['namespace' => 'Core\Bot_builder\Controllers'], $bot_builder_routes);
$routes->group('bot-builder', ['namespace' => 'Core\Bot_builder\Controllers'], $bot_builder_routes);

if (file_exists(realpath(__DIR__ . "/../Helpers"))) {
    $helperPath = realpath(__DIR__ . "/../Helpers/") . "/";
    $helpers = scandir($helperPath);
    foreach ($helpers as $helper) {
        if ($helper === '.' || $helper === '..' || stripos($helper, "_helper.php") === false)
            continue;
        if (file_exists($helperPath . $helper)) {
            require_once($helperPath . $helper);
        }
    }
}
