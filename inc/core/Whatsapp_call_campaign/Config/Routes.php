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

$routes->group('', ['namespace' => 'Core\Whatsapp_call_campaign\Controllers'], static function ($routes) {
    $routes->get('whatsapp_call_campaign', 'Whatsapp_call_campaign::index');
    $routes->post('whatsapp_call_campaign/create', 'Whatsapp_call_campaign::create');
    $routes->post('whatsapp_call_campaign/start', 'Whatsapp_call_campaign::start');
    $routes->post('whatsapp_call_campaign/pause', 'Whatsapp_call_campaign::pause');
    $routes->post('whatsapp_call_campaign/delete', 'Whatsapp_call_campaign::delete');
    $routes->get('whatsapp_call_campaign/status/(:num)', 'Whatsapp_call_campaign::status/$1');
    $routes->get('whatsapp_call_campaign/results/(:num)', 'Whatsapp_call_campaign::results/$1');
    $routes->post('whatsapp_call_campaign/upload_audio', 'Whatsapp_call_campaign::upload_audio');
    $routes->post('whatsapp_call_campaign/delete_audio', 'Whatsapp_call_campaign::delete_audio');
    $routes->get('whatsapp_call_campaign/audios', 'Whatsapp_call_campaign::audios');
});
