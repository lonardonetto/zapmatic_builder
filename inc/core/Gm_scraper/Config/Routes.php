<?php
$config = include realpath( __DIR__."/../Config.php" );
if (!defined('MODULE_CONFIG')){
    define("MODULE_CONFIG", $config);
}

if( url_is( $config["id"] ) || url_is( $config["id"].'/*' ) ){
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
$routes->get('export_csv/all', 'Gm_scraper::export_csv/all');
$routes->get('export_csv/(:any)', 'Gm_scraper::export_csv/$1');
