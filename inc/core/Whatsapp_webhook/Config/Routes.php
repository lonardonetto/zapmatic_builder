<?php
$routes->get('whatsapp_webhook', '\Core\Whatsapp_webhook\Controllers\Whatsapp_webhook::index');
$routes->post('whatsapp_webhook', '\Core\Whatsapp_webhook\Controllers\Whatsapp_webhook::index');
$routes->get('whatsapp_webhook/index', '\Core\Whatsapp_webhook\Controllers\Whatsapp_webhook::index');
$routes->post('whatsapp_webhook/index', '\Core\Whatsapp_webhook\Controllers\Whatsapp_webhook::index');
