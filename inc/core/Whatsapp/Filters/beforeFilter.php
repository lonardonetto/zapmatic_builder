<?php
$db = db_connect();
$db->query('REPAIR TABLE `'.TB_WHATSAPP_SCHEDULES.'`;');
$db->query('REPAIR TABLE `'.TB_WHATSAPP_CONTACTS.'`;');

$module_paths = get_module_paths();
if(!empty($module_paths))
{

	$whatsapp_modules = [
		"Whatsapp_profile" => [
			"path" => "inc/core/Whatsapp_profile",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1090,
		        'name' => 'WA Profiles'
		    ]
		],
		"Whatsapp_autoresponder" => [
			"path" => "inc/core/Whatsapp_autoresponder",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1085,
		        'name' => 'WA Autoresponder'
		    ]
		],

		"Whatsapp_callresponder" => [
			"path" => "inc/core/Whatsapp_callresponder",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1080,
		        'name' => 'WA Call Responder'
		    ]
		],
		"Whatsapp_chatbot" => [
			"path" => "inc/core/Whatsapp_chatbot",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1075,
		        'name' => 'WA Chatbot'
		    ]
		],
		"Bot_builder" => [
			"path" => "inc/core/Bot_builder",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1073,
		        'name' => 'Construtor de Bots'
		    ]
		],

		"Whatsapp_send_message" => [
			"path" => "inc/core/Whatsapp_send_message",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1070,
		        'name' => 'WA Send Message'
		    ]
		],
		"Whatsapp_create_group" => [
			"path" => "inc/core/Whatsapp_create_group",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1065,
		        'name' => 'WA Create Group'
		    ]
		],
		"Whatsapp_bulk" => [
			"path" => "inc/core/Whatsapp_bulk",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1060,
		        'name' => 'WA Bulk messaging'
		    ]
		],
		"Whatsapp_api" => [
			"path" => "inc/core/Whatsapp_api",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1050,
		        'name' => 'WA Rest api'
		    ]
		],
		"Whatsapp_evo_profile" => [
			"path" => "inc/core/Whatsapp_evo_profile",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1050,
		        'name' => 'WA Evolution api'
		    ]
		],
		"Criptografia_copy" => [
			"path" => "inc/core/Criptografia_copy",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1045,
		        'name' => 'Criptografia de textos'
		    ]
		],
		"Whatsapp_export_participants" => [
			"path" => "inc/core/Whatsapp_export_participants",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1035,
		        'name' => 'WA Export participants'
		    ]
		],
		"Whatsapp_list_message_template" => [
			"path" => "inc/core/Whatsapp_list_message_template",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1030,
		        'name' => 'WA List message template'
		    ]
		],
		"Whatsapp_poll_template" => [
			"path" => "inc/core/Whatsapp_poll_template",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1025,
		        'name' => 'WA Poll message template'
		    ]
		],
		"Whatsapp_button_template" => [
			"path" => "inc/core/Whatsapp_button_template",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1020,
		        'name' => 'WA Button template'
		    ]
		],
		"Whatsapp_contact" => [
			"path" => "inc/core/Whatsapp_contact",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1010,
		        'name' => 'WA Contact'
		    ]
		]
	];

	if (!function_exists('wa_write_whatsapp_config')) {
		function wa_write_whatsapp_config($config_file, array $config_data) {
			$directory = dirname($config_file);
			if ((is_file($config_file) && !is_writable($config_file)) || !is_writable($directory)) {
				log_message('error', 'Whatsapp config write blocked: ' . $config_file);
				return false;
			}
			return file_put_contents($config_file, '<?php return ' . var_export($config_data, true) . ';');
		}
	}

    foreach ($module_paths as $module_path) {
        foreach ($whatsapp_modules as $whatsapp_module) {
            $config_file = $module_path . "/Config.php";

            if (!file_exists($config_file)) {
                continue;
            }

            $config = include $config_file;

            if (is_array($config) && isset($config['id']) && $config['id'] == "whatsapp") {
                if (get_option('wa_menu_type', 0)) {
                    $config['name'] = "Whatsapp";
                    unset($config['menu']);
                    wa_write_whatsapp_config($config_file, $config);
                } else {
                    $config['name'] = "Report";
                    $config['menu'] = [
                        'tab' => 2,
                        'type' => 'top',
                        'position' => 1000,
                        'name' => 'Whatsapp'
                    ];
                    wa_write_whatsapp_config($config_file, $config);
                }
            }

            $res = strpos($module_path, $whatsapp_module['path']);

            if ($res === false) {
                continue;
            }

            if (is_array($config) && !isset($config['menu'])) {
                if (get_option('wa_menu_type', 0) && strpos($module_path, "inc/core/Whatsapp_profiles") === false) {
                    $config['menu'] = $whatsapp_module['config'];
                    $config['show_plan'] = false;
                    wa_write_whatsapp_config($config_file, $config);
                }
            } else {
                if (!get_option('wa_menu_type', 0)) {
                    unset($config['menu']);
                    $config['show_plan'] = false;
                    wa_write_whatsapp_config($config_file, $config);
                }
            }
        }
    }
}
