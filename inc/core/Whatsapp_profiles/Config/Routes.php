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
$routes->add('whatsapp_profiles/save_official', '\Core\Whatsapp_profiles\Controllers\Whatsapp_profiles::save_official');
$routes->add('whatsapp_profiles/save_embedded', '\Core\Whatsapp_profiles\Controllers\Whatsapp_profiles::save_embedded');
$routes->add('whatsapp_profiles/cloud_health_batch', '\Core\Whatsapp_profiles\Controllers\Whatsapp_profiles::cloud_health_batch');
$routes->add('whatsapp_profiles/cloud_health/(:any)', '\Core\Whatsapp_profiles\Controllers\Whatsapp_profiles::cloud_health/$1');
$routes->add('whatsapp_profiles/generate_instance', '\Core\Whatsapp_profiles\Controllers\Whatsapp_profiles::generate_instance');
$routes->add('whatsapp_profiles/generate_instance/(:any)', '\Core\Whatsapp_profiles\Controllers\Whatsapp_profiles::generate_instance/$1');
$routes->add('whatsapp_profiles/get_qrcode/(:any)', '\Core\Whatsapp_profiles\Controllers\Whatsapp_profiles::get_qrcode/$1');
$routes->add('whatsapp_profiles/check_login/(:any)', '\Core\Whatsapp_profiles\Controllers\Whatsapp_profiles::check_login/$1');

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
