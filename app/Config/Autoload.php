<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
	public $psr4 = [
		'App'         => APPPATH,
		APP_NAMESPACE => APPPATH,
		'Config'      => APPPATH . 'Config',
		'Plugins'     => ROOTPATH . 'inc/plugins',
		'Core'        => ROOTPATH . 'inc/core',
		'Backend'     => ROOTPATH . 'inc/themes/backend',
		'Frontend'    => ROOTPATH . 'inc/themes/frontend',
	];

	public $classmap = [];

	public $files = [
		ROOTPATH . 'inc/core/Users/Helpers/Users_helper.php',
	];
}
