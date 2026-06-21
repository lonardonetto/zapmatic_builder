<?php
$config = include realpath(__DIR__ . "/../Config.php");
if (!defined('MODULE_CONFIG')) {
    define("MODULE_CONFIG", $config);
}

if(
    isset($config['menu']) && 
    isset($config['menu']['sub_menu']) && 
    isset($config['menu']['sub_menu']["id"]) && 
    (url_is( $config['menu']['sub_menu']["id"] ) || url_is( $config['menu']['sub_menu']["id"].'/*' )) 
){
    $routes->setDefaultNamespace( ucfirst($config['folder']) . "/" . ucfirst($config['menu']['sub_menu']["id"]) . "/Controllers");
}else if( url_is( $config["id"] ) || url_is( $config["id"].'/*' ) ){
    $routes->setDefaultNamespace( ucfirst($config['folder']) . "/" . ucfirst($config['id']) . "/Controllers");
}


$routes->group('', ['namespace' => 'Core\Admin_API\Controllers'], static function ($routes) {
    $routes->get('admin_api/', 'Admin_API::index');
    $routes->get('admin_api/users', 'Admin_API::get_users');
    $routes->post('admin_api/users', 'Admin_API::create_user');
    $routes->put('admin_api/users', 'Admin_API::update_user');
    $routes->delete('admin_api/users', 'Admin_API::delete_user');

    $routes->get('admin_api/get_autologin', 'Admin_API::get_autologin');
    $routes->get('admin_api/check_token', 'Admin_API::check_token');
    $routes->get('admin_api/migrate_users', 'Admin_API::migrate_users');
});

if (file_exists(realpath(__DIR__ . "/../Helpers"))) {
    $helperPath = realpath(__DIR__ . "/../Helpers/") . "/";
    $helpers = scandir($helperPath);
    foreach ($helpers as $helper) {
        if ($helper === '.' || $helper === '..' || stripos($helper, "_helper.php") === false) continue;
        if (file_exists($helperPath . $helper)) {
            require_once($helperPath . $helper);
        }
    }
}
