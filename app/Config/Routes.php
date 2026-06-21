<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('Core\Home\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');

// NUCLEAR OPTION: Force route for Whatsapp Profiles
$routes->add('whatsapp_profiles/save_official', '\Core\Whatsapp_profiles\Controllers\Whatsapp_profiles::save_official');
$routes->add('whatsapp_profiles/save_embedded', '\Core\Whatsapp_profiles\Controllers\Whatsapp_profiles::save_embedded');

// NUCLEAR OPTION: Campaign Analytics
$routes->get('whatsapp_campaign_analytics', '\Core\Whatsapp_campaign_analytics\Controllers\Whatsapp_campaign_analytics::index');
$routes->post('whatsapp_campaign_analytics/ajax_list', '\Core\Whatsapp_campaign_analytics\Controllers\Whatsapp_campaign_analytics::ajax_list');
$routes->get('whatsapp_campaign_analytics/details/(:any)', '\Core\Whatsapp_campaign_analytics\Controllers\Whatsapp_campaign_analytics::details/$1');
$routes->get('whatsapp_campaign_analytics/export/(:any)', '\Core\Whatsapp_campaign_analytics\Controllers\Whatsapp_campaign_analytics::export/$1');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}

/**
 * --------------------------------------------------------------------
 * Include Modules Routes Files
 * --------------------------------------------------------------------
 */
if (file_exists(ROOTPATH . 'inc/plugins')) {
    $modulesPath = ROOTPATH . 'inc/plugins/';
    $modules = scandir($modulesPath);

    foreach ($modules as $module) {
        if ($module === '.' || $module === '..')
            continue;
        if (is_dir($modulesPath) . '/' . $module) {
            $routesPath = $modulesPath . $module . '/Config/Routes.php';
            if (file_exists($routesPath)) {
                require($routesPath);
            } else {
                continue;
            }
        }
    }
}

if (file_exists(ROOTPATH . 'inc/core')) {
    $modulesPath = ROOTPATH . 'inc/core/';
    $modules = scandir($modulesPath);

    foreach ($modules as $module) {
        if ($module === '.' || $module === '..')
            continue;
        if (is_dir($modulesPath) . '/' . $module) {
            $routesPath = $modulesPath . $module . '/Config/Routes.php';
            if (file_exists($routesPath)) {
                require($routesPath);
            } else {
                continue;
            }
        }
    }
}

if (file_exists(ROOTPATH . 'inc/themes/backend')) {
    $modulesPath = ROOTPATH . 'inc/themes/backend/';
    $modules = scandir($modulesPath);

    foreach ($modules as $module) {
        if ($module === '.' || $module === '..')
            continue;
        if (is_dir($modulesPath) . '/' . $module) {
            $routesPath = $modulesPath . $module . '/Config/Routes.php';
            if (file_exists($routesPath)) {
                require($routesPath);
            } else {
                continue;
            }
        }
    }
}

if (file_exists(ROOTPATH . 'inc/themes/frontend')) {
    $modulesPath = ROOTPATH . 'inc/themes/frontend/';
    $modules = scandir($modulesPath);

    foreach ($modules as $module) {
        if ($module === '.' || $module === '..')
            continue;
        if (is_dir($modulesPath) . '/' . $module) {
            $routesPath = $modulesPath . $module . '/Config/Routes.php';
            if (file_exists($routesPath)) {
                require($routesPath);
            } else {
                continue;
            }
        }
    }
}

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
