<?php return array (
  'id' => 'whatsapp_export_participants',
  'folder' => 'core',
  'name' => 'Export participants',
  'author' => 'Stackcode',
  'author_uri' => 'sp',
  'desc' => 'Export participant list',
  'icon' => 'fad fa-file-export',
  'color' => '#004cff',
  'login_required' => false,
  'show_plan' => true,
  'parent' => 
  array (
    'id' => 'features',
    'name' => 'Features',
  ),
  'cron' => 
  array (
    'name' => 'Process export participants queue',
    'uri' => 'whatsapp_export_participants/cron',
    'style' => '* * * * *',
  ),
);