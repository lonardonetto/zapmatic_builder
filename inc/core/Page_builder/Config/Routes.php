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

// Admin routes
$routes->group('page_builder', ['namespace' => 'Core\Page_builder\Controllers'], function($routes) {
    $routes->get('/', 'Page_builder::index');
    $routes->post('save_page', 'Page_builder::save_page');
    $routes->post('delete_page', 'Page_builder::delete_page');
    $routes->get('editor/(:num)', 'Page_builder::editor/$1');
    $routes->post('save_section', 'Page_builder::save_section');
    $routes->post('delete_section', 'Page_builder::delete_section');
    $routes->post('reorder_sections', 'Page_builder::reorder_sections');
    $routes->post('toggle_page', 'Page_builder::toggle_page');
});

// Frontend render routes (without admin namespace)
$routes->group('pagina', function($routes) {
    $routes->get('(:any)', '\Core\Page_builder\Controllers\Render::page/$1');
});
