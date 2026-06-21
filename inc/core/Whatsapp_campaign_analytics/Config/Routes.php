<?php
$config = include realpath( __DIR__."/../Config.php" );
if (!defined('MODULE_CONFIG')){
    define("MODULE_CONFIG", $config);
}

if( url_is( $config["id"] ) || url_is( $config["id"].'/*' ) ){
    $routes->setDefaultNamespace( ucfirst($config['folder']) . "\\" . ucfirst($config['id']) . "\\Controllers");
}

if (isset($routes)) {
    $routes->get('whatsapp_campaign_analytics', 'Whatsapp_campaign_analytics::index');
    $routes->post('whatsapp_campaign_analytics/ajax_list', 'Whatsapp_campaign_analytics::ajax_list');
    $routes->get('whatsapp_campaign_analytics/details/(:any)', 'Whatsapp_campaign_analytics::details/$1');
    $routes->get('whatsapp_campaign_analytics/export/(:any)', 'Whatsapp_campaign_analytics::export/$1');
}
