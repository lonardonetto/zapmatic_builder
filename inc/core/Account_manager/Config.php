<?php
return [
    'id' => 'account_manager',
    'folder' => 'core',
    'name' => 'Account manager',
    'author' => 'Stackcode',
    'author_uri' => 'https://stackposts.com',
    'desc' => 'Customize system interface',
    'icon' => 'fad fa-share-alt',
    'color' => '#002bff',
    // 'menu' => removido — perfis agora são gerenciados pela Central de Conexão (whatsapp_profiles/oauth)
    // O widget ainda é usado em outras páginas (campanhas, etc.)
    'hidden_menu' => true,
    'css' => [
        "Assets/css/account_manager.css"
    ],
    'js' => [
        "Assets/js/account_manager.js"
    ],
];