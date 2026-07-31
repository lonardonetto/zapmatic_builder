<?php
$config = include realpath( __DIR__."/../Config.php" );
if (!defined('MODULE_CONFIG')){
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

if ( file_exists( realpath(  __DIR__."/../Helpers" ) ) ) {
    $helperPath = realpath(  __DIR__."/../Helpers/" )."/";
    $helpers = scandir($helperPath);
    foreach ($helpers as $helper) {
        if ($helper === '.' || $helper === '..' || stripos( $helper , "_helper.php") === false) continue;
        if (  file_exists( $helperPath.$helper ) ) {
        	require_once( $helperPath.$helper );
        }
    }
}
// Rotas do System Updater
$routes->group('plugins', ['namespace' => 'Core\Plugins\Controllers'], function ($routes) {
    $routes->get('system-updater', 'System_updater::index');
    $routes->post('system-updater/check', 'System_updater::check');
    $routes->post('system-updater/apply', 'System_updater::apply');
    $routes->post('system-updater/rollback', 'System_updater::rollback');
});
