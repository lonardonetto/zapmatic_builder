<?php
namespace Core\Whatsapp_send_message\Controllers;

class Whatsapp_send_message extends \CodeIgniter\Controller
{
    public $config;
    public $model;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Whatsapp_send_message\Models\Whatsapp_send_messageModel();
    }

    public function index($page = false)
    {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $team_id = get_team("id");
        $accounts = db_fetch("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "category" => "profile", "login_type" => [1, 2, 3], "team_id" => $team_id, "status" => 1], "created", "ASC");
        permission_accounts($accounts);

        $data_content = [
            "config" => $this->config,
            "accounts" => $accounts
        ];

        $data['content'] = view('Core\Whatsapp_send_message\Views\content', $data_content);

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function info()
    {
        $team_id = get_team("id");
        $access_token = get_team("ids");
        $ids = post("account");
        $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "login_type" => [1, 2, 3], "ids" => $ids, "team_id" => $team_id]);

        if (!empty($account) || $ids == "all") {
            $result = false;
            if (!empty($account)) {
                $result = db_get("*", TB_WHATSAPP_AUTORESPONDER, ["instance_id" => $account->token, "team_id" => $team_id]);
            }

            $data = [
                "status" => "success",
                "result" => false,
                "account" => $account,
                "access_token" => $access_token,
            ];

        } else {
            $data = [
                "status" => "error",
                "message" => "WhatsApp account does not exist. Please try again or re-login your WhatsApp account"
            ];

        }

        return view('Core\Whatsapp_send_message\Views\info', $data);
    }

    public function send()
    {
        $team_id = get_team("id");

        $medias = post("medias");

        $caption = post('caption');
        $instance_id = post('instance_id');

        $send_to = post('send_to');
        $type = (int) post("type");
        $template = 0;
        $btn_msg = (int) post("btn_msg");
        $list_msg = (int) post("list_msg");
        $carousel_msg = (int) post("carousel_msg");
        $flow_msg = trim((string) post("flow_msg"));
        $flow_body = trim((string) post("flow_body"));
        $flow_header = trim((string) post("flow_header"));
        $flow_footer = trim((string) post("flow_footer"));
        $flow_cta = trim((string) post("flow_cta"));
        $flow_mode = trim((string) post("flow_mode"));
        $flow_launch_action = trim((string) post("flow_launch_action"));
        $flow_screen = trim((string) post("flow_screen"));
        $flow_action_data = trim((string) post("flow_action_data"));
        $account = false;
        $access_token = get_team("ids");



        if ($instance_id != "") {
            $account = db_get("*", TB_ACCOUNTS, ["token" => $instance_id, "team_id" => $team_id]);

            if (empty($account)) {
                ms([
                    "status" => "error",
                    "message" => __('Profile does not exist')
                ]);
            }
        }

        switch ($type) {
            case 1:
                if (permission("whatsapp_send_media")) {
                    if (!is_array($medias) && $caption == "") {
                        ms([
                            "status" => "error",
                            "message" => __('Please enter a caption or add a media')
                        ]);
                    }
                } else {
                    validate('null', __('Caption'), $caption);
                }
                break;

            case 2:
                if ($btn_msg == 0) {
                    ms([
                        "status" => "error",
                        "message" => __('Please select a button message option')
                    ]);
                }
                $template = $btn_msg;
                break;

            case 3:
                if ($list_msg == 0) {
                    ms([
                        "status" => "error",
                        "message" => __('Please select a list message option')
                    ]);
                }

                $template = $list_msg;
                break;

            case 4:
                if ($btn_msg == 0) {
                    ms([
                        "status" => "error",
                        "message" => __('Please select a poll message option')
                    ]);
                }
                $template = $btn_msg;
                break;

            case 5:
                if ($carousel_msg == 0) {
                    ms([
                        "status" => "error",
                        "message" => __('Please select a carousel message option')
                    ]);
                }
                $template = $carousel_msg;
                break;

            case 6:
                if ($flow_msg === "") {
                    ms([
                        "status" => "error",
                        "message" => __('Please select a flow')
                    ]);
                }

                if ($flow_body === "") {
                    ms([
                        "status" => "error",
                        "message" => __('Flow body message is required')
                    ]);
                }

                if ($flow_cta === "") {
                    ms([
                        "status" => "error",
                        "message" => __('Flow CTA button text is required')
                    ]);
                }
                break;

            default:
                if ($btn_msg == 0 && $list_msg == 0 && $carousel_msg == 0 && $flow_msg === "") {
                    ms([
                        "status" => "error",
                        "message" => __('Invalid input data')
                    ]);
                }
                break;
        }

        if (!empty($medias) && permission("whatsapp_send_media")) {
            foreach ($medias as $key => $value) {
                $medias[$key] = get_file_url($value);
            }

            $media = $medias[0];
        } else {
            $media = NULL;
        }


        if (!empty($advance_options) && isset($advance_options['shortlink'])) {
            $shortlink_by = shortlink_by(['advance_options' => ['shortlink' => $advance_options['shortlink']]]);
            $caption = shortlink($caption, $shortlink_by);
        }

        if (!empty($account)) {
            // Verifica se é Cloud API (login_type = 1) ou Baileys (login_type = 2)
            if ($account->login_type == 1) {
                // Cloud API - Usa a API do Meta
                if ($type === 6) {
                    $flow = db_get("*", TB_WHATSAPP_FLOWS, [
                        "ids" => $flow_msg,
                        "team_id" => $team_id,
                        "account_id" => $account->id,
                        "channel" => "cloud_api",
                    ]);

                    if (empty($flow)) {
                        ms([
                            "status" => "error",
                            "message" => __('Selected flow was not found for this Cloud account')
                        ]);
                    }

                    $normalized_flow_data = $this->normalize_flow_action_data($flow_action_data);
                    if ($normalized_flow_data === false) {
                        ms([
                            "status" => "error",
                            "message" => __('Initial data JSON must be a valid JSON object')
                        ]);
                    }

                    $interactive_data = $this->build_cloud_flow_interactive_payload($flow, [
                        "body" => $flow_body,
                        "header" => $flow_header,
                        "footer" => $flow_footer,
                        "cta" => $flow_cta,
                        "mode" => $flow_mode,
                        "launch_action" => $flow_launch_action,
                        "screen" => $this->resolve_single_flow_screen($flow, $flow_launch_action, $flow_screen),
                        "data" => $normalized_flow_data,
                        "flow_token" => $this->generate_flow_token($flow),
                    ]);

                    $result = send_cloud_interactive($account, $send_to, 'flow', $interactive_data);
                    $this->record_flow_event($flow, $account, $send_to, $interactive_data, $result);
                } elseif ($template != 0) {
                    $template_data = db_get("*", TB_WHATSAPP_TEMPLATE, ["id" => $template]);
                    if (!empty($template_data)) {
                        $payload = json_decode($template_data->data);
                        $caption = spintax($caption);

                        $metaOfficialEnabled = false;
                        if (isset($payload->meta_official) && is_object($payload->meta_official)) {
                            $metaOfficialEnabled = (int)($payload->meta_official->enabled ?? 0) === 1;
                        }

                        if ($metaOfficialEnabled) {
                            try {
                                $db = \Config\Database::connect();
                                $accIds = (string)($account->ids ?? '');
                                $srcTplIds = (string)($template_data->ids ?? '');

                                $sourceTemplateType = (string)($template_data->type ?? '');
                                if ($sourceTemplateType === '') {
                                    $sourceTemplateType = '2';
                                }

                                $approvedRow = null;
                                if ($accIds !== '' && $srcTplIds !== '') {
                                    $approvedRow = $db->query(
                                        "SELECT data FROM " . TB_WHATSAPP_TEMPLATE . "\n" .
                                        " WHERE team_id = ? AND type = ?\n" .
                                        "   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.account_ids')) = ?\n" .
                                        "   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_type')) = ?\n" .
                                        "   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.source_template_ids')) = ?\n" .
                                        "   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.status')) = 'APPROVED'\n" .
                                        " ORDER BY changed DESC\n" .
                                        " LIMIT 1",
                                        [$team_id, WA_TEMPLATE_TYPE_META_STATUS, $accIds, $sourceTemplateType, $srcTplIds]
                                    )->getRowArray();
                                }

                                $approvedData = !empty($approvedRow['data']) ? (json_decode($approvedRow['data'], true) ?: []) : [];
                                $approvedName = (string)($approvedData['name'] ?? '');
                                $fallbackLang = (string)($payload->meta_official->languages ?? 'pt_BR');
                                if (strpos($fallbackLang, ',') !== false) {
                                    $parts = array_values(array_filter(array_map('trim', explode(',', $fallbackLang))));
                                    $fallbackLang = $parts[0] ?? 'pt_BR';
                                }
                                $approvedLang = (string)($approvedData['language'] ?? $fallbackLang);
                                $defaultHeaderMedia = $approvedData['default_header_media'] ?? null;
                                $approvedComponents = $approvedData['components'] ?? [];
                                $bodyExampleRaw = (string)($payload->meta_official->body_example ?? '');
                                if ($bodyExampleRaw === '') {
                                    $bodyExampleRaw = (string)($approvedData['body_example'] ?? '');
                                }

                                if ($approvedName === '') {
                                    ms([
                                        "status" => "error",
                                        "message" => "Template Oficial (Meta) ainda não aprovado ou não sincronizado. Fora da janela (24h) só é permitido enviar templates aprovados."
                                    ]);
                                }

                                if (empty($media)) {
                                    $media = $payload->image->url ?? $payload->media ?? null;
                                    if ($media) {
                                        $media = get_file_url($media);
                                    }
                                }

                                if (!empty($media)) {
                                    $defaultHeaderMedia = ['link' => $media];
                                }


                                $result = send_cloud_template($account, $send_to, [
                                    'name' => $approvedName,
                                    'language' => $approvedLang,
                                    'default_header_media' => $defaultHeaderMedia,
                                    'components' => $approvedComponents,
                                    'body_example_values' => $bodyExampleRaw,
                                    'flow_button_defaults' => $this->extract_template_flow_button_defaults($payload),
                                ]);
                            } catch (\Throwable $e) {
                                $result = [
                                    'status' => 'error',
                                    'message' => 'Falha ao resolver template oficial aprovado para envio: ' . $e->getMessage(),
                                ];
                            }
                        }

                        if (!$metaOfficialEnabled) {

                        // Se for Carrossel (Type 5)
                        if ($template_data->type == 5) {
                            $cards = [];
                            if (isset($payload->cards) && is_array($payload->cards)) {
                                foreach ($payload->cards as $idx => $card) {
                                    $card_body = spintax($card->body ?? ' ');
                                    if (!empty($card->title)) {
                                        $card_body = "*" . spintax($card->title) . "*\n" . $card_body;
                                    }

                                    $meta_card = [
                                        'card_index' => $idx,
                                        'type' => 'BUTTON',
                                        'body' => ['text' => substr($card_body, 0, 1024)],
                                    ];

                                    $media_url = null;
                                    if (isset($card->media)) {
                                        $media_url = is_object($card->media) && isset($card->media->url) ? $card->media->url : $card->media;
                                    }
                                    if (empty($media_url) && isset($card->image)) {
                                        $media_url = is_object($card->image) && isset($card->image->url) ? $card->image->url : $card->image;
                                    }
                                    if (!empty($media_url) && is_string($media_url)) {
                                        $media_url = stripslashes(trim($media_url));
                                        $media_url = str_replace(' ', '%20', $media_url);
                                        $meta_card['header'] = [
                                            'type' => 'image',
                                            'image' => ['link' => $media_url]
                                        ];
                                    }

                                    $buttons = [];
                                    if (isset($card->buttons) && is_array($card->buttons)) {
                                        foreach ($card->buttons as $btn) {
                                            $btn_type = $btn->name ?? $btn->type ?? '';
                                            $params = is_string($btn->buttonParamsJson ?? null) ? json_decode($btn->buttonParamsJson) : (object) ($btn->quick_reply ?? $btn->reply ?? $btn->cta_url ?? []);

                                            if ($btn_type == 'quick_reply' || $btn_type == 'reply' || $btn_type == 'cta_url') {
                                                $buttons[] = [
                                                    'type' => 'quick_reply',
                                                    'quick_reply' => [
                                                        'id' => $params->id ?? uniqid(),
                                                        'title' => substr(spintax($params->display_text ?? $params->title ?? 'Button'), 0, 20)
                                                    ]
                                                ];
                                            }
                                        }
                                    }

                                    if (!empty($buttons)) {
                                        $meta_card['action'] = [
                                            'buttons' => array_slice($buttons, 0, 2)
                                        ];
                                    } else {
                                        // CAROUSEL CARDS MUST HAVE AT LEAST ONE BUTTON
                                        $meta_card['type'] = 'BUTTON';
                                        $meta_card['action'] = [
                                            'buttons' => [
                                                [
                                                    'type' => 'quick_reply',
                                                    'quick_reply' => ['id' => uniqid(), 'title' => 'OK']
                                                ]
                                            ]
                                        ];
                                    }

                                    $cards[] = $meta_card;
                                }
                            }

                            if (empty($cards)) {
                                $result = send_cloud_message($account, $send_to, $caption, $media);
                            } else {
                                $interactive_data = [
                                    'type' => 'carousel',
                                    'body' => ['text' => substr(spintax($payload->text ?? ' '), 0, 1024)],
                                    'action' => [
                                        'cards' => array_slice($cards, 0, 10)
                                    ]
                                ];

                                if (!empty($payload->title)) {
                                    $interactive_data['body']['text'] = "*" . spintax($payload->title) . "*\n" . $interactive_data['body']['text'];
                                }

                                // Carousel does not support interactive footer
                                /* if (!empty($payload->footer)) {
                                    $interactive_data['footer'] = ['text' => substr(spintax($payload->footer), 0, 60)];
                                } */

                                $result = send_cloud_interactive($account, $send_to, 'carousel', $interactive_data);
                            }
                        } elseif ($template_data->type == 2) { // Botões
                            $buttons = [];
                            if (isset($payload->templateButtons) && is_array($payload->templateButtons)) {
                                foreach ($payload->templateButtons as $btn) {
                                    if (isset($btn->quickReplyButton)) {
                                        $buttons[] = [
                                            'type' => 'reply',
                                            'reply' => [
                                                'id' => $btn->quickReplyButton->id ?? uniqid(),
                                                'title' => substr(spintax($btn->quickReplyButton->displayText ?? 'Button'), 0, 20)
                                            ]
                                        ];
                                    } elseif (isset($btn->urlButton)) {
                                        // URL buttons converted to reply for Cloud API
                                        $buttons[] = [
                                            'type' => 'reply',
                                            'reply' => [
                                                'id' => 'url_' . uniqid(),
                                                'title' => substr(spintax($btn->urlButton->displayText ?? 'Link'), 0, 20)
                                            ]
                                        ];
                                    } elseif (isset($btn->callButton)) {
                                        // Call buttons converted to reply for Cloud API
                                        $buttons[] = [
                                            'type' => 'reply',
                                            'reply' => [
                                                'id' => 'call_' . uniqid(),
                                                'title' => substr(spintax($btn->callButton->displayText ?? 'Ligar'), 0, 20)
                                            ]
                                        ];
                                    } elseif (isset($btn->catalogButton)) {
                                        // Catalog buttons converted to reply for Cloud API
                                        $buttons[] = [
                                            'type' => 'reply',
                                            'reply' => [
                                                'id' => 'catalog_' . uniqid(),
                                                'title' => substr(spintax($btn->catalogButton->displayText ?? 'Catálogo'), 0, 20)
                                            ]
                                        ];
                                    }
                                }
                            }

                            if (empty($buttons)) {
                                $result = send_cloud_message($account, $send_to, substr(spintax($payload->caption ?? $payload->text ?? ' '), 0, 1024), $media);
                            } else {
                                // Check if there's only ONE button and it's a URL - use native cta_url
                                $urlButtons = array_filter($payload->templateButtons ?? [], function($btn) {
                                    return isset($btn->urlButton);
                                });
                                
                                if (count($payload->templateButtons ?? []) === 1 && count($urlButtons) === 1) {
                                    // Single URL button - use native cta_url interactive type
                                    $urlBtn = reset($urlButtons);
                                    $interactive_data = [
                                        'type' => 'cta_url',
                                        'body' => ['text' => substr(spintax($payload->caption ?? $payload->text ?? ' '), 0, 1024)],
                                        'action' => [
                                            'name' => 'cta_url',
                                            'parameters' => [
                                                'display_text' => substr(spintax($urlBtn->urlButton->displayText ?? 'Link'), 0, 20),
                                                'url' => $urlBtn->urlButton->url
                                            ]
                                        ]
                                    ];
                                    
                                    // Add image header if available
                                    $image_url = $payload->image->url ?? $payload->media ?? null;
                                    if (!empty($image_url)) {
                                        $image_url = stripslashes(trim($image_url));
                                        $image_url = str_replace(' ', '%20', $image_url);
                                        $interactive_data['header'] = [
                                            'type' => 'image',
                                            'image' => ['link' => $image_url]
                                        ];
                                    }
                                    
                                    if (!empty($payload->footer)) {
                                        $interactive_data['footer'] = ['text' => substr(spintax($payload->footer), 0, 60)];
                                    }
                                    
                                    $result = send_cloud_interactive($account, $send_to, 'cta_url', $interactive_data);
                                } else {
                                    // Check if there's only ONE button and it's a Call - use native phone_number
                                    $callButtons = array_filter($payload->templateButtons ?? [], function($btn) {
                                        return isset($btn->callButton);
                                    });
                                    
                                    if (count($payload->templateButtons ?? []) === 1 && count($callButtons) === 1) {
                                        // Single Call button - use native phone_number interactive type
                                        $callBtn = reset($callButtons);
                                        $interactive_data = [
                                            'type' => 'cta_url',
                                            'body' => ['text' => substr(spintax($payload->caption ?? $payload->text ?? ' '), 0, 1024)],
                                            'action' => [
                                                'name' => 'cta_url',
                                                'parameters' => [
                                                    'display_text' => substr(spintax($callBtn->callButton->displayText ?? 'Ligar'), 0, 20),
                                                    'url' => 'tel:' . preg_replace('/[^0-9+]/', '', $callBtn->callButton->phoneNumber)
                                                ]
                                            ]
                                        ];
                                        
                                        // Add image header if available
                                        $image_url = $payload->image->url ?? $payload->media ?? null;
                                        if (!empty($image_url)) {
                                            $image_url = stripslashes(trim($image_url));
                                            $image_url = str_replace(' ', '%20', $image_url);
                                            $interactive_data['header'] = [
                                                'type' => 'image',
                                                'image' => ['link' => $image_url]
                                            ];
                                        }
                                        
                                        if (!empty($payload->footer)) {
                                            $interactive_data['footer'] = ['text' => substr(spintax($payload->footer), 0, 60)];
                                        }
                                        
                                        $result = send_cloud_interactive($account, $send_to, 'cta_url', $interactive_data);
                                    } else {
                                    // Multiple buttons or non-URL buttons - use standard reply buttons
                                    $interactive_data = [
                                        'type' => 'button',
                                        'body' => ['text' => substr(spintax($payload->caption ?? $payload->text ?? ' '), 0, 1024)],
                                        'action' => ['buttons' => array_slice($buttons, 0, 3)]
                                    ];

                                    if (!empty($payload->title)) {
                                        $interactive_data['header'] = ['type' => 'text', 'text' => substr(spintax($payload->title), 0, 60)];
                                    }

                                    $image_url = $payload->image->url ?? $payload->media ?? null;
                                    if (!empty($image_url)) {
                                        $image_url = stripslashes(trim($image_url));
                                        $image_url = str_replace(' ', '%20', $image_url);
                                        $interactive_data['header'] = [
                                            'type' => 'image',
                                            'image' => ['link' => $image_url]
                                        ];
                                    }

                                    if (!empty($payload->footer)) {
                                        $interactive_data['footer'] = ['text' => substr(spintax($payload->footer), 0, 60)];
                                    }

                                    $result = send_cloud_interactive($account, $send_to, 'button', $interactive_data);
                                    }
                                }
                            }
                        } elseif ($template_data->type == 1) { // Listas
                            $sections = [];
                            if (isset($payload->sections) && is_array($payload->sections)) {
                                foreach ($payload->sections as $sec) {
                                    $rows = [];
                                    if (isset($sec->rows) && is_array($sec->rows)) {
                                        foreach ($sec->rows as $row) {
                                            $rows[] = [
                                                'id' => $row->rowId ?? uniqid(),
                                                'title' => substr(spintax($row->title ?? 'Item'), 0, 24),
                                                'description' => substr(spintax($row->description ?? ''), 0, 72)
                                            ];
                                        }
                                    }
                                    $sections[] = [
                                        'title' => substr(spintax($sec->title ?? 'Menu'), 0, 24),
                                        'rows' => $rows
                                    ];
                                }
                            }

                            $interactive_data = [
                                'type' => 'list',
                                'body' => ['text' => substr(spintax($payload->text ?? ' '), 0, 1024)],
                                'action' => [
                                    'button' => substr(spintax($payload->buttonText ?? 'Select'), 0, 20),
                                    'sections' => array_slice($sections, 0, 10)
                                ]
                            ];

                            if (!empty($payload->title)) {
                                $interactive_data['header'] = ['type' => 'text', 'text' => substr(spintax($payload->title), 0, 60)];
                            }

                            if (!empty($payload->footer)) {
                                $interactive_data['footer'] = ['text' => substr(spintax($payload->footer), 0, 60)];
                            }

                            $result = send_cloud_interactive($account, $send_to, 'list', $interactive_data);
                        } else {
                            $result = send_cloud_template($account, $send_to, $template_data->name);
                        }

                        }
                    } else {
                        $result = send_cloud_template($account, $send_to, 'hello_world', 'en_US');
                    }
                } else {
                    $result = send_cloud_message($account, $send_to, spintax($caption), $media);
                }

                if ($result['status'] == 'success') {
                    ms(["status" => "success", "message" => "Message sent via Cloud API"]);
                } else {
                    ms(["status" => "error", "message" => $result['message'] ?? "Cannot send Message via Cloud API"]);
                }
            } else {
                // Baileys - Usa wa_post_curl
                if ($type === 6) {
                    ms([
                        "status" => "error",
                        "message" => __('Flow sending is currently available only for Cloud API accounts')
                    ]);
                }
                if ($type === 7) {
                    ms([
                        "status" => "error",
                        "message" => __('Official Meta templates are currently available only for Cloud API accounts')
                    ]);
                }

                if (isset($media) && $media != NULL && $template == 0) {
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' . $_SERVER['HTTP_HOST'] : 'http://' . $_SERVER['HTTP_HOST'];
                    $path = str_replace($protocol . '/writeable/', '', $media);
                    $info = db_get("*", TB_FILES, ["file" => $path]);
                    if (isset($info) && $info->detect == "pdf" && $info->detect == "doc" && $info->detect == "csv" && $info->detect == "other") {
                        $params = [
                            "chat_id" => $send_to . "@s.whatsapp.net",
                            "type" => 1,
                            "caption" => $caption,
                            "media_url" => $media,
                            "filename" => $info->name
                        ];
                    } else {
                        $params = [
                            "chat_id" => $send_to . "@s.whatsapp.net",
                            "type" => $type,
                            "caption" => $caption,
                            "media_url" => $media
                        ];
                    }
                } else {
                    if ($template == 0) {
                        $params = [
                            "chat_id" => $send_to . "@s.whatsapp.net",
                            "caption" => $caption
                        ];
                    } else {
                        $params = [
                            "chat_id" => $send_to . "@s.whatsapp.net",
                            "template" => $template
                        ];
                    }
                }

                $result = wa_post_curl("direct_send_message", ["instance_id" => $instance_id, "access_token" => $access_token, "type" => $type], $params);

                if (isset($result) && $result->status == "success") {
                    ms(["status" => "success", "message" => "Success"]);
                } else {
                    ms(["status" => "error", "message" => "Cannot send Message"]);
                }
            }
        } else {
            ms([
                "status" => "error",
                "message" => __('Relogin required')
            ]);
        }
    }

    protected function build_cloud_flow_interactive_payload($flow, $options = [])
    {
        $body = trim((string) ($options["body"] ?? ""));
        $header = trim((string) ($options["header"] ?? ""));
        $footer = trim((string) ($options["footer"] ?? ""));
        $cta = trim((string) ($options["cta"] ?? "Abrir fluxo"));
        $mode = trim((string) ($options["mode"] ?? "draft"));
        $launch_action = trim((string) ($options["launch_action"] ?? "navigate"));
        $screen = trim((string) ($options["screen"] ?? ""));
        $data = $options["data"] ?? null;
        $flow_token = trim((string) ($options["flow_token"] ?? ""));

        if (!in_array($mode, ["draft", "published"], true)) {
            $mode = "draft";
        }

        if (!in_array($launch_action, ["navigate", "data_exchange"], true)) {
            $launch_action = "navigate";
        }

        if ($flow_token === "") {
            $flow_token = $this->generate_flow_token($flow);
        }

        $parameters = [
            "flow_message_version" => "3",
            "flow_token" => $flow_token,
            "flow_cta" => substr(spintax($cta), 0, 30),
            "flow_action" => $launch_action,
            "mode" => $mode,
        ];

        $meta_flow_id = trim((string) get_data($flow, "meta_flow_id", "text"));
        if ($meta_flow_id !== "") {
            $parameters["flow_id"] = $meta_flow_id;
        } else {
            $flow_name = trim((string) get_data($flow, "slug", "text"));
            if ($flow_name === "") {
                $flow_name = trim((string) get_data($flow, "name", "text"));
            }
            $parameters["flow_name"] = $flow_name;
        }

        if ($launch_action === "navigate") {
            $action_payload = [];
            if ($screen === "") {
                $screen = $this->extract_first_flow_screen_id(get_data($flow, "flow_json", "text")) ?: "FIRST_ENTRY_SCREEN";
            }

            if ($screen !== "") {
                $action_payload["screen"] = $screen;
            }

            if ($data !== null) {
                $action_payload["data"] = $data;
            }

            if (!empty($action_payload)) {
                $parameters["flow_action_payload"] = $action_payload;
            }
        }

        $interactive_data = [
            "type" => "flow",
            "body" => [
                "text" => substr(spintax($body), 0, 1024)
            ],
            "action" => [
                "name" => "flow",
                "parameters" => $parameters
            ]
        ];

        if ($header !== "") {
            $interactive_data["header"] = [
                "type" => "text",
                "text" => substr(spintax($header), 0, 60)
            ];
        }

        if ($footer !== "") {
            $interactive_data["footer"] = [
                "text" => substr(spintax($footer), 0, 60)
            ];
        }

        return $interactive_data;
    }

    protected function resolve_single_flow_screen($flow, $launch_action, $requested_screen = "")
    {
        $launch_action = trim((string) $launch_action);
        $requested_screen = trim((string) $requested_screen);

        if ($launch_action !== "navigate") {
            return $requested_screen;
        }

        $default_screen = $this->extract_first_flow_screen_id(get_data($flow, "flow_json", "text"));
        if ($default_screen === null || $default_screen === "") {
            return $requested_screen;
        }

        if ($requested_screen === "") {
            return $default_screen;
        }

        if (strcasecmp($requested_screen, $default_screen) !== 0) {
            return $default_screen;
        }

        return $requested_screen;
    }

    protected function normalize_flow_action_data($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === "") {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || $decoded === [] || array_keys($decoded) === range(0, count($decoded) - 1)) {
            return false;
        }

        return $decoded;
    }

    protected function extract_first_flow_screen_id($flow_json)
    {
        $flow_json = trim((string) $flow_json);
        if ($flow_json === "") {
            return null;
        }

        $decoded = json_decode($flow_json, true);
        if (!is_array($decoded) || empty($decoded["screens"]) || empty($decoded["screens"][0]["id"])) {
            return null;
        }

        return (string) $decoded["screens"][0]["id"];
    }

    protected function generate_flow_token($flow)
    {
        $team_id = (int) get_team("id");
        $flow_id = (int) get_data($flow, "id");
        return "wa_flow_" . $team_id . "_" . $flow_id . "_" . ids();
    }

    protected function extract_template_flow_button_defaults($payload)
    {
        $defaults = [];
        $buttons = [];

        if (is_object($payload) && isset($payload->templateButtons) && is_array($payload->templateButtons)) {
            $buttons = $payload->templateButtons;
        } elseif (is_array($payload) && isset($payload['templateButtons']) && is_array($payload['templateButtons'])) {
            $buttons = $payload['templateButtons'];
        }

        foreach ($buttons as $index => $button) {
            $flowButton = null;
            if (is_object($button) && isset($button->flowButton) && is_object($button->flowButton)) {
                $flowButton = (array) $button->flowButton;
            } elseif (is_array($button) && isset($button['flowButton']) && is_array($button['flowButton'])) {
                $flowButton = $button['flowButton'];
            }

            if (empty($flowButton)) {
                continue;
            }

            $entry = [
                'index' => (string) $index,
                'flow_token' => $this->generate_template_flow_token(
                    (string) ($flowButton['flowName'] ?? $flowButton['displayText'] ?? 'flow'),
                    (string) ($flowButton['metaFlowId'] ?? $flowButton['flowIds'] ?? ''),
                    (string) $index
                ),
            ];

            $actionData = $this->normalize_template_flow_action_data($flowButton['flowActionData'] ?? '');
            if (!empty($actionData)) {
                $entry['flow_action_data'] = $actionData;
            }

            $defaults[] = $entry;
        }

        return $defaults;
    }

    protected function normalize_template_flow_action_data($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || array_keys($decoded) === range(0, count($decoded) - 1)) {
            return [];
        }

        return $decoded;
    }

    protected function generate_template_flow_token($seed, $recipient, $index)
    {
        $seed = trim((string) $seed);
        if ($seed === '') {
            $seed = 'flow-template';
        }

        return substr(
            preg_replace('/[^a-z0-9_]+/i', '_', strtolower($seed)) . '_' . $index . '_' . substr(md5($recipient . microtime(true)), 0, 12),
            0,
            64
        );
    }

    protected function record_flow_event($flow, $account, $send_to, $interactive_data, $result)
    {
        if (!defined("TB_WHATSAPP_FLOW_EVENTS")) {
            return;
        }

        $parameters = $interactive_data["action"]["parameters"] ?? [];
        $response_data = $result["data"] ?? $result;

        db_insert(TB_WHATSAPP_FLOW_EVENTS, [
            "team_id" => get_team("id"),
            "flow_id" => (int) get_data($flow, "id"),
            "account_id" => (int) get_data($account, "id"),
            "account_ids" => (string) get_data($account, "ids", "text"),
            "instance_id" => (string) get_data($account, "token", "text"),
            "event_type" => "flow_single_send",
            "direction" => "outbound",
            "contact_id" => preg_replace('/[^0-9]/', '', (string) $send_to),
            "chat_id" => preg_replace('/[^0-9]/', '', (string) $send_to),
            "flow_token" => (string) ($parameters["flow_token"] ?? ""),
            "status" => (string) ($result["status"] ?? "unknown"),
            "payload" => json_encode($interactive_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            "response" => json_encode($response_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            "error_message" => (string) ($result["message"] ?? ""),
            "created" => time(),
        ]);
    }
}
