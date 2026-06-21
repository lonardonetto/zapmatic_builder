<?php
if (!function_exists('wa_flow_builder_is_assoc')) {
    function wa_flow_builder_is_assoc($array)
    {
        if (!is_array($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}

if (!function_exists('wa_flow_builder_merge')) {
    function wa_flow_builder_merge($defaults, $incoming)
    {
        if (!is_array($defaults) || !is_array($incoming)) {
            return $incoming;
        }

        foreach ($incoming as $key => $value) {
            if (
                array_key_exists($key, $defaults) &&
                is_array($defaults[$key]) &&
                is_array($value) &&
                wa_flow_builder_is_assoc($defaults[$key]) &&
                wa_flow_builder_is_assoc($value)
            ) {
                $defaults[$key] = wa_flow_builder_merge($defaults[$key], $value);
                continue;
            }

            $defaults[$key] = $value;
        }

        return $defaults;
    }
}

$flow_json = get_data($result, "flow_json");
$preview_data = get_data($result, "preview_data");
$builder_state_raw = get_data($result, "builder_state");
$last_meta_error = get_data($result, "last_meta_error");
$published_at = get_data($result, "published_at");
$last_sync_at = get_data($result, "last_sync_at");
$preview_url = get_data($result, "preview_url");
$preview_expires_at = get_data($result, "preview_expires_at");
$data_channel_uri = get_data($result, "data_channel_uri");
$selected_account_ids = get_data($result, "account_ids");
$selected_endpoint_id = (int) get_data($result, "endpoint_id");
$status_local_value = get_data($result, "status_local");
$meta_flow_id = get_data($result, "meta_flow_id");
$status_meta = get_data($result, "status_meta");
$endpoint_status = get_data($active_endpoint, "endpoint_status");
$endpoint_uri = get_data($active_endpoint, "endpoint_uri");
$endpoint_last_error = get_data($active_endpoint, "last_meta_error");
$endpoint_private_key_path = get_data($active_endpoint, "private_key_path");
$endpoint_public_key_fingerprint = get_data($active_endpoint, "public_key_fingerprint");
$endpoint_public_key_uploaded = (int) get_data($active_endpoint, "public_key_uploaded");
$endpoint_app_secret_verified = (int) get_data($active_endpoint, "app_secret_verified");
$endpoint_last_sync_at = get_data($active_endpoint, "last_sync_at");
$selected_categories = ["OTHER"];
$categories_json = get_data($result, "categories_json");
$flow_categories = isset($flow_categories) && is_array($flow_categories) ? $flow_categories : [
    "SIGN_UP" => __("Sign up"),
    "SIGN_IN" => __("Sign in"),
    "APPOINTMENT_BOOKING" => __("Appointment booking"),
    "LEAD_GENERATION" => __("Lead generation"),
    "CONTACT_US" => __("Contact us"),
    "CUSTOMER_SUPPORT" => __("Customer support"),
    "SURVEY" => __("Survey"),
    "OTHER" => __("Other"),
];

if ($categories_json) {
    $decoded_categories = json_decode($categories_json, true);
    if (is_array($decoded_categories) && !empty($decoded_categories)) {
        $selected_categories = array_values(array_unique(array_map('strtoupper', array_map('trim', $decoded_categories))));
    }
}
$json_version_value = get_data($result, "json_version") ? get_data($result, "json_version") : "7.3";
$data_api_version_value = get_data($result, "data_api_version") ? get_data($result, "data_api_version") : "3.0";

if (!$status_local_value) {
    $status_local_value = WA_FLOW_STATUS_LOCAL_DRAFT;
}

$simple_defaults = [
    "version" => $json_version_value,
    "screen_id" => "SCREEN_1",
    "screen_title" => get_data($result, "name") ? get_data($result, "name") : "Novo Flow",
    "form_name" => "form",
    "heading" => "",
    "caption" => "",
    "body_text" => "",
    "submit_label" => "Enviar",
    "fields" => [],
];

$guided_defaults = [
    "version" => $json_version_value,
    "intro" => [
        "screen_id" => "WELCOME",
        "screen_title" => get_data($result, "name") ? get_data($result, "name") : "Boas-vindas",
        "heading" => get_data($result, "name") ? get_data($result, "name") : "Boas-vindas",
        "caption" => "Explique rapidamente o que o cliente vai encontrar neste atendimento.",
        "body_text" => "Use este Flow para organizar o menu inicial, conduzir o cliente por categorias e capturar os dados finais.",
        "button_label" => "Iniciar",
        "image_data_url" => "",
        "image_alt" => "Capa do Flow",
        "image_scale_type" => "cover",
        "image_width" => "",
        "image_height" => "",
        "image_aspect_ratio" => "1.6",
    ],
    "menu" => [
        "screen_id" => "MAIN_MENU",
        "screen_title" => "Menu",
        "label" => "Escolha uma categoria",
        "description" => "Selecione abaixo a frente do atendimento que deseja seguir.",
        "media_size" => "regular",
    ],
    "items" => [
        [
            "id" => "menu_1",
            "title" => "Financeiro",
            "description" => "Faturas e negociação",
            "metadata" => "2a via, débitos, negociação e vencimento",
            "badge" => "",
            "tags" => [],
            "side_title" => "",
            "side_description" => "",
            "image_data_url" => "",
            "image_alt" => "Ilustração da categoria",
            "subitems" => [
                [
                    "id" => "option_1",
                    "title" => "Emitir fatura",
                    "description" => "Solicite a segunda via",
                    "metadata" => "Tenha em mãos seus dados de cadastro",
                    "detail_text" => "Preencha os campos abaixo para receber o encaminhamento correto.",
                    "image_data_url" => "",
                    "image_alt" => "Ilustração da opção",
                ]
            ],
        ]
    ],
    "final_form" => [
        "heading" => "Finalize sua solicitação",
        "caption" => "Complete o cadastro para prosseguir com {{opcao}}.",
        "body_text" => "",
        "submit_label" => "Enviar",
        "fields" => [
            [
                "type" => "text",
                "label" => "Nome completo",
                "name" => "nome_completo",
                "required" => true,
                "options" => [],
            ],
            [
                "type" => "text",
                "label" => "Telefone",
                "name" => "telefone",
                "required" => true,
                "options" => [],
            ],
            [
                "type" => "textarea",
                "label" => "Detalhes adicionais",
                "name" => "detalhes_adicionais",
                "required" => false,
                "options" => [],
            ],
        ],
    ],
];

$visual_defaults = [
    "builder_type" => "guided_menu",
    "simple_form" => $simple_defaults,
    "guided_menu" => $guided_defaults,
];

$visual_builder = $visual_defaults;
$builder_supported = true;
$builder_state_loaded = false;

if ($builder_state_raw) {
    $decoded_builder = json_decode($builder_state_raw, true);
    if (is_array($decoded_builder) && isset($decoded_builder["builder_type"])) {
        $visual_builder = wa_flow_builder_merge($visual_defaults, $decoded_builder);
        $builder_state_loaded = true;
    }
}

if (!$builder_state_loaded && $flow_json) {
    $flow_data = json_decode($flow_json, true);

    if (
        !is_array($flow_data) ||
        empty($flow_data["screens"]) ||
        !isset($flow_data["screens"][0]["layout"]["children"][0]) ||
        ($flow_data["screens"][0]["layout"]["children"][0]["type"] ?? "") !== "Form"
    ) {
        $builder_supported = false;
    } else {
        $screen = $flow_data["screens"][0];
        $form = $screen["layout"]["children"][0];
        $children = $form["children"] ?? [];
        $builder_data = $simple_defaults;

        $builder_data["version"] = $flow_data["version"] ?? $simple_defaults["version"];
        $builder_data["screen_id"] = $screen["id"] ?? $simple_defaults["screen_id"];
        $builder_data["screen_title"] = $screen["title"] ?? $simple_defaults["screen_title"];
        $builder_data["form_name"] = $form["name"] ?? $simple_defaults["form_name"];

        $pending_subheading = "";
        foreach ($children as $child) {
            $type = $child["type"] ?? "";

            switch ($type) {
                case 'TextHeading':
                    if ($builder_data["heading"] === "") {
                        $builder_data["heading"] = $child["text"] ?? "";
                    }
                    break;

                case 'TextCaption':
                    if ($builder_data["caption"] === "") {
                        $builder_data["caption"] = $child["text"] ?? "";
                    }
                    break;

                case 'TextBody':
                    if ($builder_data["body_text"] === "") {
                        $builder_data["body_text"] = $child["text"] ?? "";
                    }
                    break;

                case 'TextSubheading':
                    $pending_subheading = $child["text"] ?? "";
                    break;

                case 'TextInput':
                    $builder_data["fields"][] = [
                        "type" => "text",
                        "label" => $child["label"] ?? $pending_subheading,
                        "name" => $child["name"] ?? "",
                        "required" => !empty($child["required"]),
                        "options" => [],
                    ];
                    $pending_subheading = "";
                    break;

                case 'TextArea':
                    $builder_data["fields"][] = [
                        "type" => "textarea",
                        "label" => $child["label"] ?? $pending_subheading,
                        "name" => $child["name"] ?? "",
                        "required" => !empty($child["required"]),
                        "options" => [],
                    ];
                    $pending_subheading = "";
                    break;

                case 'RadioButtonsGroup':
                    $builder_data["fields"][] = [
                        "type" => "radio",
                        "label" => $pending_subheading !== "" ? $pending_subheading : ($child["label"] ?? ""),
                        "name" => $child["name"] ?? "",
                        "required" => !empty($child["required"]),
                        "options" => array_values(array_filter(array_map(function ($option) {
                            return $option["title"] ?? "";
                        }, $child["data-source"] ?? []))),
                    ];
                    $pending_subheading = "";
                    break;

                case 'CheckboxGroup':
                    $builder_data["fields"][] = [
                        "type" => "checkbox",
                        "label" => $child["label"] ?? $pending_subheading,
                        "name" => $child["name"] ?? "",
                        "required" => !empty($child["required"]),
                        "options" => array_values(array_filter(array_map(function ($option) {
                            return $option["title"] ?? "";
                        }, $child["data-source"] ?? []))),
                    ];
                    $pending_subheading = "";
                    break;

                case 'Dropdown':
                    $builder_data["fields"][] = [
                        "type" => "dropdown",
                        "label" => $child["label"] ?? $pending_subheading,
                        "name" => $child["name"] ?? "",
                        "required" => !empty($child["required"]),
                        "options" => array_values(array_filter(array_map(function ($option) {
                            return $option["title"] ?? "";
                        }, $child["data-source"] ?? []))),
                    ];
                    $pending_subheading = "";
                    break;

                case 'Footer':
                    $builder_data["submit_label"] = $child["label"] ?? $simple_defaults["submit_label"];
                    $pending_subheading = "";
                    break;

                default:
                    $builder_supported = false;
                    break 2;
            }
        }

        if ($builder_supported) {
            $visual_builder["builder_type"] = "simple_form";
            $visual_builder["simple_form"] = $builder_data;
        }
    }
}

$builder_mode = ($builder_supported || $builder_state_loaded || !$flow_json) ? "visual" : "advanced";
$visual_builder_json = json_encode(
    $visual_builder,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>

<form class="actionForm" action="<?php _ec(get_module_url("save/" . get_data($result, "ids")))?>" method="POST" data-redirect="<?php _e(get_module_url())?>" id="wa-flow-form">
    <textarea class="d-none" name="flow_json" id="flow_json_hidden"><?php _e($flow_json)?></textarea>
    <textarea class="d-none" name="builder_state" id="builder_state_hidden"><?php _e($builder_state_raw)?></textarea>

    <div class="container py-5">
        <div class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex flex-column flex-sm-row w-100 p-5 mb-10">
            <span class="fs-2hx text-primary me-4 mb-5 mb-sm-0">
                <i class="fad fa-sitemap text-primary fs-1"></i>
            </span>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1 text-primary"><?php _e("Complete visual Flow builder")?></h5>
                <span class="fs-12"><?php _e("Choose between a simple form or a guided Flow with cover image, main menu, submenu routing and final data capture. The system keeps the advanced JSON mode untouched for custom cases.")?></span>
            </div>
        </div>

        <?php if (!$builder_supported && $flow_json && !$builder_state_loaded): ?>
        <div class="alert alert-dismissible bg-light-warning border border-warning border-dashed d-flex flex-column flex-sm-row w-100 p-5 mb-10">
            <span class="fs-2hx text-warning me-4 mb-5 mb-sm-0">
                <i class="fad fa-exclamation-triangle text-warning fs-1"></i>
            </span>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1 text-warning"><?php _e("Advanced Flow detected")?></h5>
                <span class="fs-12"><?php _e("This Flow uses a structure the visual builder still cannot reconstruct from JSON alone. Nothing was altered and the editor opened directly in Advanced JSON mode.")?></span>
            </div>
        </div>
        <?php endif ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card b-r-6 mb-4">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="<?php _ec($config['icon'])?> me-2" style="color: <?php _ec($config['color'])?>;"></i>
                            <?php _e("Flow editor")?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label"><?php _e("Name")?></label>
                            <input type="text" name="name" class="form-control form-control-solid" value="<?php _ec(get_data($result, "name"))?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><?php _e("Slug")?></label>
                            <input type="text" name="slug" class="form-control form-control-solid" value="<?php _ec(get_data($result, "slug"))?>">
                            <div class="fs-12 text-gray-600 mt-2"><?php _e("If left blank, a slug is generated automatically from the Flow name.")?></div>
                        </div>

                        <ul class="nav nav-tabs mb-4 border-0" role="tablist">
                            <li class="nav-item me-2" role="presentation">
                                <button class="nav-link <?php _e($builder_mode === 'visual' ? 'active' : '')?>" id="flow-visual-tab" data-bs-toggle="tab" data-bs-target="#flow-visual-pane" type="button" role="tab"><?php _e("Visual builder")?></button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php _e($builder_mode === 'advanced' ? 'active' : '')?>" id="flow-advanced-tab" data-bs-toggle="tab" data-bs-target="#flow-advanced-pane" type="button" role="tab"><?php _e("Advanced JSON")?></button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade <?php _e($builder_mode === 'visual' ? 'show active' : '')?>" id="flow-visual-pane" role="tabpanel">
                                <div class="card bg-light-primary mb-4">
                                    <div class="card-body">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label class="form-label mb-3"><?php _e("Builder experience")?></label>
                                                <div class="d-flex flex-wrap gap-3">
                                                    <label class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="radio" name="visual_builder_type" value="guided_menu">
                                                        <span class="form-check-label"><?php _e("Guided Flow")?></span>
                                                    </label>
                                                    <label class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="radio" name="visual_builder_type" value="simple_form">
                                                        <span class="form-check-label"><?php _e("Simple form")?></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-7">
                                                <div class="fs-12 text-gray-700">
                                                    <?php _e("Guided Flow creates a cover screen, main menu, submenu journey and final capture screens. Simple form keeps the classic one-screen builder for direct registrations and surveys.")?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="builder-guided-panel">
                                    <div class="card border mb-4">
                                        <div class="card-header">
                                            <div class="card-title"><?php _e("1. Welcome screen")?></div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label"><?php _e("Screen ID")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_intro_screen_id">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label"><?php _e("Top title")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_intro_screen_title">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label"><?php _e("CTA button")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_intro_button_label">
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <label class="form-label"><?php _e("Headline")?></label>
                                                <input type="text" class="form-control form-control-solid" id="guided_intro_heading">
                                            </div>

                                            <div class="mt-4">
                                                <label class="form-label"><?php _e("Caption")?></label>
                                                <textarea class="form-control form-control-solid" rows="3" id="guided_intro_caption"></textarea>
                                            </div>

                                            <div class="mt-4">
                                                <label class="form-label"><?php _e("Body text")?></label>
                                                <textarea class="form-control form-control-solid" rows="4" id="guided_intro_body_text"></textarea>
                                            </div>

                                            <div class="row g-3 mt-1">
                                                <div class="col-md-3">
                                                    <label class="form-label"><?php _e("Scale type")?></label>
                                                    <select class="form-select form-select-solid" id="guided_intro_image_scale_type">
                                                        <option value="cover"><?php _e("Cover")?></option>
                                                        <option value="contain"><?php _e("Contain")?></option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label"><?php _e("Width")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_intro_image_width" placeholder="ex: 400">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label"><?php _e("Height")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_intro_image_height" placeholder="ex: 280">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label"><?php _e("Aspect ratio")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_intro_image_aspect_ratio" placeholder="ex: 1.6">
                                                </div>
                                            </div>

                                            <div class="row g-3 mt-1">
                                                <div class="col-md-8">
                                                    <label class="form-label"><?php _e("Image")?></label>
                                                    <input type="file" class="form-control" id="guided_intro_image_file" accept="image/png,image/jpeg,image/webp">
                                                    <input type="hidden" id="guided_intro_image_data">
                                                    <div class="fs-12 text-gray-600 mt-2"><?php _e("The image is embedded as base64 in the generated Flow JSON, following the Meta component model.")?></div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label"><?php _e("Alt text")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_intro_image_alt">
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <div id="guided_intro_image_preview" class="border rounded bg-light min-h-150px d-flex align-items-center justify-content-center p-4 text-gray-600">
                                                    <?php _e("No cover image selected")?>
                                                </div>
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-light-danger btn-sm" id="guided_intro_remove_image"><?php _e("Remove image")?></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card border mb-4">
                                        <div class="card-header">
                                            <div class="card-title"><?php _e("2. Main menu")?></div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label class="form-label"><?php _e("Screen ID")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_menu_screen_id">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label"><?php _e("Top title")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_menu_screen_title">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label"><?php _e("List label")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_menu_label">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label"><?php _e("Media size")?></label>
                                                    <select class="form-select form-select-solid" id="guided_menu_media_size">
                                                        <option value="regular"><?php _e("Regular")?></option>
                                                        <option value="large"><?php _e("Large")?></option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <label class="form-label"><?php _e("Menu description")?></label>
                                                <textarea class="form-control form-control-solid" rows="3" id="guided_menu_description"></textarea>
                                            </div>

                                            <div class="mt-5 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h4 class="mb-1"><?php _e("Menu sections")?></h4>
                                                    <div class="fs-12 text-gray-600"><?php _e("Each section becomes an item in the main navigation list and opens its own submenu screen.")?></div>
                                                </div>
                                                <button type="button" class="btn btn-dark" id="add_guided_menu_item"><?php _e("Add section")?></button>
                                            </div>

                                            <div id="guided_menu_items" class="mt-4"></div>
                                        </div>
                                    </div>

                                    <div class="card border mb-4">
                                        <div class="card-header">
                                            <div class="card-title"><?php _e("3. Final capture")?></div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label"><?php _e("Heading")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_form_heading">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><?php _e("Submit button")?></label>
                                                    <input type="text" class="form-control form-control-solid" id="guided_form_submit_label">
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <label class="form-label"><?php _e("Caption")?></label>
                                                <textarea class="form-control form-control-solid" rows="3" id="guided_form_caption"></textarea>
                                                <div class="fs-12 text-gray-600 mt-2"><?php _e("You can use {{categoria}} and {{opcao}} to personalize the final screen text.")?></div>
                                            </div>

                                            <div class="mt-4">
                                                <label class="form-label"><?php _e("Extra note")?></label>
                                                <textarea class="form-control form-control-solid" rows="3" id="guided_form_body_text"></textarea>
                                            </div>

                                            <div class="mt-5 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h4 class="mb-1"><?php _e("Shared form fields")?></h4>
                                                    <div class="fs-12 text-gray-600"><?php _e("These fields are reused in each final capture screen generated from the submenu options.")?></div>
                                                </div>
                                                <button type="button" class="btn btn-dark" id="add_guided_form_field"><?php _e("Add field")?></button>
                                            </div>

                                            <div id="guided_form_fields" class="mt-4"></div>

                                            <div class="mt-4">
                                                <label class="form-label"><?php _e("Generated JSON preview")?></label>
                                                <textarea class="form-control form-control-solid" rows="20" id="guided_json_preview" style="font-family: monospace;" readonly></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="builder-simple-panel">
                                    <div class="card bg-light-primary mb-4">
                                        <div class="card-body">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-8">
                                                    <label class="form-label"><?php _e("Start with a ready model")?></label>
                                                    <select class="form-select form-select-solid" id="flow_builder_preset">
                                                        <option value=""><?php _e("Choose a model")?></option>
                                                        <option value="lead_capture"><?php _e("Lead capture / registration")?></option>
                                                        <option value="contact_form"><?php _e("Contact request")?></option>
                                                        <option value="survey"><?php _e("Customer survey")?></option>
                                                        <option value="service_qualification"><?php _e("Service qualification")?></option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <button type="button" class="btn btn-primary w-100" id="apply_flow_preset"><?php _e("Apply model")?></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label"><?php _e("Screen ID")?></label>
                                            <input type="text" class="form-control form-control-solid" id="builder_screen_id">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label"><?php _e("Screen title")?></label>
                                            <input type="text" class="form-control form-control-solid" id="builder_screen_title">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label"><?php _e("Submit button")?></label>
                                            <input type="text" class="form-control form-control-solid" id="builder_submit_label">
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label class="form-label"><?php _e("Heading")?></label>
                                        <input type="text" class="form-control form-control-solid" id="builder_heading">
                                    </div>

                                    <div class="mt-4">
                                        <label class="form-label"><?php _e("Caption")?></label>
                                        <textarea class="form-control form-control-solid" rows="3" id="builder_caption"></textarea>
                                    </div>

                                    <div class="mt-4">
                                        <label class="form-label"><?php _e("Closing note")?></label>
                                        <textarea class="form-control form-control-solid" rows="3" id="builder_body_text"></textarea>
                                    </div>

                                    <div class="mt-5 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="mb-1"><?php _e("Form fields")?></h4>
                                            <div class="fs-12 text-gray-600"><?php _e("Use this builder when you only need one final screen without menus or intermediate routing.")?></div>
                                        </div>
                                        <button type="button" class="btn btn-dark" id="add_builder_field"><?php _e("Add field")?></button>
                                    </div>

                                    <div id="builder_fields" class="mt-4"></div>

                                    <div class="mt-4">
                                        <label class="form-label"><?php _e("Generated JSON preview")?></label>
                                        <textarea class="form-control form-control-solid" rows="16" id="builder_json_preview" style="font-family: monospace;" readonly></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade <?php _e($builder_mode === 'advanced' ? 'show active' : '')?>" id="flow-advanced-pane" role="tabpanel">
                                <div class="mb-4">
                                    <label class="form-label"><?php _e("Flow JSON")?></label>
                                    <textarea class="form-control form-control-solid" rows="24" id="flow_json_editor" style="font-family: monospace;"><?php _e($flow_json)?></textarea>
                                    <div class="fs-12 text-gray-600 mt-2"><?php _e("Use valid JSON only. This mode is recommended for unsupported structures or direct advanced editing.")?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card b-r-6 mb-4">
                    <div class="card-header">
                        <div class="card-title"><?php _e("Cloud binding")?></div>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label"><?php _e("Cloud API account")?></label>
                            <select name="account_ids" class="form-select form-select-solid">
                                <option value=""><?php _e("Select account")?></option>
                                <?php foreach ($cloud_accounts as $account): ?>
                                    <?php
                                    $account_data = json_decode($account->data ?? "", true);
                                    if (!is_array($account_data)) {
                                        $account_data = [];
                                    }
                                    $phone_number_id = $account_data["phone_number_id"] ?? "-";
                                    ?>
                                    <option value="<?php _ec($account->ids)?>" <?php _e($selected_account_ids == $account->ids ? "selected" : "")?>>
                                        <?php _e($account->name)?> | PNID: <?php _e($phone_number_id)?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                            <div class="fs-12 text-gray-600 mt-2"><?php _e("The Flow publishing step will only target Cloud API accounts in the next phases.")?></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><?php _e("Endpoint record")?></label>
                            <select name="endpoint_id" class="form-select form-select-solid">
                                <option value="0"><?php _e("Auto-detect by account when available")?></option>
                                <?php foreach ($endpoints as $endpoint): ?>
                                    <option value="<?php _ec($endpoint->id)?>" <?php _e($selected_endpoint_id === (int) $endpoint->id ? "selected" : "")?>>
                                        <?php _e(($endpoint->account_name ? $endpoint->account_name : __("Unknown account")) . " | " . $endpoint->endpoint_status)?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><?php _e("Local status")?></label>
                                <select name="status_local" class="form-select form-select-solid">
                                    <?php foreach ($status_local_options as $status_key => $status_label): ?>
                                        <option value="<?php _ec($status_key)?>" <?php _e($status_local_value == $status_key ? "selected" : "")?>><?php _e($status_label)?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><?php _e("JSON version")?></label>
                                <input type="text" name="json_version" class="form-control form-control-solid" value="<?php _ec($json_version_value)?>" placeholder="ex: 7.3">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><?php _e("Data API version")?></label>
                            <input type="text" name="data_api_version" class="form-control form-control-solid" value="<?php _ec($data_api_version_value)?>" placeholder="ex: 3.0">
                            <div class="fs-12 text-gray-600 mt-2"><?php _e("Keep this aligned with the Meta Flow endpoint contract when the endpoint phase starts.")?></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><?php _e("Meta categories")?></label>
                            <div class="row g-2">
                                <?php foreach ($flow_categories as $category_key => $category_label): ?>
                                    <div class="col-md-6">
                                        <label class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="categories[]" value="<?php _ec($category_key)?>" <?php _e(in_array($category_key, $selected_categories, true) ? "checked" : "")?>>
                                            <span class="form-check-label"><?php _e($category_label)?></span>
                                        </label>
                                    </div>
                                <?php endforeach ?>
                            </div>
                            <div class="fs-12 text-gray-600 mt-2"><?php _e("These categories are sent to Meta when the draft is created or refreshed.")?></div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label"><?php _e("Preview data")?></label>
                            <textarea name="preview_data" class="form-control form-control-solid" rows="8" style="font-family: monospace;"><?php _e($preview_data)?></textarea>
                            <div class="fs-12 text-gray-600 mt-2"><?php _e("Optional local payload used later for testing and data previews.")?></div>
                        </div>
                    </div>
                </div>

                <div class="card b-r-6 mb-4">
                    <div class="card-header">
                        <div class="card-title"><?php _e("Flow endpoint")?></div>
                    </div>
                    <div class="card-body fs-13">
                        <div class="alert alert-dismissible bg-light-warning border border-warning border-dashed d-flex flex-column w-100 p-4 mb-4">
                            <div class="fw-bold text-warning mb-1"><?php _e("Encrypted endpoint")?></div>
                            <div class="fs-12 text-gray-700"><?php _e("This endpoint is used when the Flow needs encrypted data exchange with your server. The URI is generated from the WhatsApp server URL option and linked to the selected Cloud account.")?></div>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-700"><?php _e("Endpoint status")?></span>
                            <span class="fw-bold"><?php _e($endpoint_status ? $endpoint_status : "not_configured")?></span>
                        </div>
                        <div class="mb-3">
                            <div class="text-gray-700 mb-1"><?php _e("Public endpoint URI")?></div>
                            <div class="fw-bold text-break"><?php _e($endpoint_uri ? $endpoint_uri : ($flow_server_url . "/flow_endpoint/{endpoint_id}"))?></div>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-700"><?php _e("Private key")?></span>
                            <span class="fw-bold"><?php _e($endpoint_private_key_path ? __("Generated") : __("Not generated"))?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-700"><?php _e("Public key on Meta")?></span>
                            <span class="fw-bold"><?php _e($endpoint_public_key_uploaded ? __("Uploaded") : __("Not uploaded"))?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-700"><?php _e("App secret for signature check")?></span>
                            <span class="fw-bold"><?php _e($endpoint_app_secret_verified ? __("Configured") : __("Missing"))?></span>
                        </div>
                        <div class="mb-3">
                            <div class="text-gray-700 mb-1"><?php _e("Public key fingerprint")?></div>
                            <div class="fw-bold text-break"><?php _e($endpoint_public_key_fingerprint ? $endpoint_public_key_fingerprint : "-")?></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-gray-700"><?php _e("Endpoint last sync")?></span>
                            <span class="fw-bold"><?php _e($endpoint_last_sync_at ? datetime_show($endpoint_last_sync_at) : "-")?></span>
                        </div>

                        <?php if ($endpoint_last_error): ?>
                            <div class="mt-4 border border-danger rounded p-3 bg-light-danger">
                                <div class="fw-bold text-danger mb-2"><?php _e("Last endpoint error")?></div>
                                <pre class="mb-0 text-danger" style="white-space: pre-wrap;"><?php _e($endpoint_last_error)?></pre>
                            </div>
                        <?php endif ?>
                    </div>
                </div>

                <div class="card b-r-6 mb-4">
                    <div class="card-header">
                        <div class="card-title"><?php _e("Runtime status")?></div>
                    </div>
                    <div class="card-body fs-13">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-700"><?php _e("Meta Flow ID")?></span>
                            <span class="fw-bold"><?php _e($meta_flow_id ? $meta_flow_id : "-")?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-700"><?php _e("Meta status")?></span>
                            <span class="fw-bold"><?php _e($status_meta ? $status_meta : "-")?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-700"><?php _e("Meta categories")?></span>
                            <span class="fw-bold"><?php _e(!empty($selected_categories) ? implode(", ", $selected_categories) : "-")?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-700"><?php _e("Published at")?></span>
                            <span class="fw-bold"><?php _e($published_at ? datetime_show($published_at) : "-")?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-700"><?php _e("Last sync")?></span>
                            <span class="fw-bold"><?php _e($last_sync_at ? datetime_show($last_sync_at) : "-")?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-700"><?php _e("Preview link")?></span>
                            <span class="fw-bold">
                                <?php if ($preview_url): ?>
                                    <a href="<?php _e($preview_url)?>" target="_blank" class="text-primary"><?php _e("Open preview")?></a>
                                <?php else: ?>
                                    <?php _e("-")?>
                                <?php endif ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-gray-700"><?php _e("Preview expires")?></span>
                            <span class="fw-bold"><?php _e($preview_expires_at ? datetime_show($preview_expires_at) : "-")?></span>
                        </div>
                        <div class="mb-3">
                            <div class="text-gray-700 mb-1"><?php _e("Meta data channel URI")?></div>
                            <div class="fw-bold text-break"><?php _e($data_channel_uri ? $data_channel_uri : "-")?></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-gray-700"><?php _e("Local assets")?></span>
                            <span class="fw-bold"><?php _e((int) $flow_assets_total)?></span>
                        </div>
                    </div>
                </div>

                <div class="card b-r-6 mb-4">
                    <div class="card-header">
                        <div class="card-title"><?php _e("Meta operations")?></div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-dismissible bg-light-info border border-info border-dashed d-flex flex-column w-100 p-4 mb-4">
                            <div class="fw-bold text-info mb-1"><?php _e("Cloud-first workflow")?></div>
                            <div class="fs-12 text-gray-700"><?php _e("Use the buttons below to save the local builder, create or refresh the Meta draft, publish the Flow, and then refresh the official status without leaving this screen.")?></div>
                        </div>

                        <div class="d-grid gap-3">
                            <a href="<?php _ec(get_module_url("endpoint_sync/" . get_data($result, "ids")))?>" class="btn btn-warning actionMultiItem" data-confirm="<?php _e("Generate the local endpoint keypair, upload the public key to Meta, and sync the encryption status now?")?>" data-redirect="<?php _ec(current_url())?>">
                                <i class="fad fa-key me-2"></i><?php _e("Prepare endpoint + upload key to Meta")?>
                            </a>

                            <a href="<?php _ec(get_module_url("endpoint_refresh/" . get_data($result, "ids")))?>" class="btn btn-light-warning actionMultiItem" data-redirect="<?php _ec(current_url())?>">
                                <i class="fad fa-shield-check me-2"></i><?php _e("Refresh endpoint status")?>
                            </a>

                            <a href="<?php _ec(get_module_url("meta_push_draft/" . get_data($result, "ids")))?>" class="btn btn-info actionMultiItem" data-redirect="<?php _ec(current_url())?>">
                                <i class="fad fa-cloud-upload me-2"></i><?php _e("Save + sync draft on Meta")?>
                            </a>

                            <a href="<?php _ec(get_module_url("meta_publish/" . get_data($result, "ids")))?>" class="btn btn-primary actionMultiItem" data-confirm="<?php _e("Publish this Flow on Meta? After publishing, the draft becomes immutable there.")?>" data-redirect="<?php _ec(current_url())?>">
                                <i class="fad fa-paper-plane me-2"></i><?php _e("Save + publish on Meta")?>
                            </a>

                            <a href="<?php _ec(get_module_url("meta_sync/" . get_data($result, "ids")))?>" class="btn btn-light actionMultiItem" data-redirect="<?php _ec(current_url())?>">
                                <i class="fad fa-sync me-2"></i><?php _e("Refresh Meta status")?>
                            </a>

                            <a href="<?php _ec(get_module_url("meta_pull_account/" . get_data($result, "ids")))?>" class="btn btn-light-primary actionMultiItem" data-confirm="<?php _e("Pull all Flows from this Cloud account and sync them locally now?")?>" data-redirect="<?php _ec(current_url())?>">
                                <i class="fad fa-download me-2"></i><?php _e("Pull all Flows from Meta")?>
                            </a>
                        </div>

                        <div class="fs-12 text-gray-600 mt-4">
                            <?php _e("Inside the 24-hour window, Single Message can test the Flow as interactive content. You can also pull all Flows already available on this WABA, including categories and official runtime status, before moving to template + Flow button for outbound.")?>
                        </div>
                    </div>
                </div>

                <?php if ($last_meta_error): ?>
                <div class="card b-r-6 border border-danger mb-4">
                    <div class="card-header">
                        <div class="card-title text-danger"><?php _e("Last Meta error")?></div>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0 text-danger" style="white-space: pre-wrap;"><?php _e($last_meta_error)?></pre>
                    </div>
                </div>
                <?php endif ?>
            </div>
        </div>

        <div class="mt-5 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-6"><?php _e("Save")?></button>
        </div>
    </div>
</form>

<script type="text/javascript">
(function($){
    const initialVisualBuilder = <?php echo $visual_builder_json ? $visual_builder_json : '{}'; ?>;
    const i18n = {
        addField: <?php echo json_encode(__("Add field")); ?>,
        noSimpleField: <?php echo json_encode(__("No field added yet. Use \"Add field\" or apply a ready model.")); ?>,
        noGuidedField: <?php echo json_encode(__("No shared field added yet. You can still create informational screens only, or add fields for the final capture.")); ?>,
        noMenuItem: <?php echo json_encode(__("No section added yet. Create the first main menu item to start the guided Flow.")); ?>,
        noSubitem: <?php echo json_encode(__("No submenu option added yet. Each section needs at least one submenu option.")); ?>,
        selectPreset: <?php echo json_encode(__("Select a model first")); ?>,
        modelApplied: <?php echo json_encode(__("Model applied")); ?>,
        simpleFieldNeedsOptions: <?php echo json_encode(__("Choice fields need at least one option")); ?>,
        guidedNeedsSection: <?php echo json_encode(__("Add at least one menu section in the guided builder")); ?>,
        guidedNeedsSubitem: <?php echo json_encode(__("Each menu section needs at least one submenu option")); ?>,
        invalidGuidedName: <?php echo json_encode(__("Every section and submenu option needs a title")); ?>,
        invalidGuidedScreen: <?php echo json_encode(__("The welcome and menu screen identifiers are required")); ?>,
        invalidJson: <?php echo json_encode(__("Flow JSON must be valid before saving")); ?>,
        removeImage: <?php echo json_encode(__("Remove image")); ?>,
        noCoverImage: <?php echo json_encode(__("No cover image selected")); ?>,
        noItemImage: <?php echo json_encode(__("No image selected for this menu item")); ?>,
        noSubitemImage: <?php echo json_encode(__("No image selected for this submenu option")); ?>,
        sectionLabel: <?php echo json_encode(__("Section")); ?>,
        submenuLabel: <?php echo json_encode(__("Submenu")); ?>,
        fieldLabel: <?php echo json_encode(__("Field")); ?>,
        shortText: <?php echo json_encode(__("Short text")); ?>,
        longText: <?php echo json_encode(__("Long text")); ?>,
        singleChoice: <?php echo json_encode(__("Single choice")); ?>,
        multipleChoice: <?php echo json_encode(__("Multiple choice")); ?>,
        dropdownChoice: <?php echo json_encode(__("Dropdown")); ?>,
        labelQuestion: <?php echo json_encode(__("Label / question")); ?>,
        variableName: <?php echo json_encode(__("Variable name")); ?>,
        fieldType: <?php echo json_encode(__("Type")); ?>,
        required: <?php echo json_encode(__("Required")); ?>,
        options: <?php echo json_encode(__("Options")); ?>,
        oneOptionPerLine: <?php echo json_encode(__("One option per line")); ?>,
        menuTitle: <?php echo json_encode(__("Menu title")); ?>,
        menuDescription: <?php echo json_encode(__("Short description")); ?>,
        menuMetadata: <?php echo json_encode(__("Metadata / helper")); ?>,
        badge: <?php echo json_encode(__("Badge")); ?>,
        tags: <?php echo json_encode(__("Tags")); ?>,
        commaSeparated: <?php echo json_encode(__("Comma separated")); ?>,
        sideTitle: <?php echo json_encode(__("Side title")); ?>,
        sideDescription: <?php echo json_encode(__("Side description")); ?>,
        itemImageAlt: <?php echo json_encode(__("Image alt text")); ?>,
        submenuDetail: <?php echo json_encode(__("Detail text")); ?>,
        menuImage: <?php echo json_encode(__("Menu item image")); ?>,
        submenuImage: <?php echo json_encode(__("Submenu image")); ?>,
        menuOptions: <?php echo json_encode(__("Submenu options")); ?>,
        addSubitem: <?php echo json_encode(__("Add submenu option")); ?>,
        addSection: <?php echo json_encode(__("Add section")); ?>,
        addSimpleField: <?php echo json_encode(__("Add field")); ?>,
        coverImageNote: <?php echo json_encode(__("The cover image is embedded directly in the Flow JSON.")); ?>,
        guidedMode: <?php echo json_encode(__("Guided Flow")); ?>,
        simpleMode: <?php echo json_encode(__("Simple form")); ?>,
    };

    let simpleFields = [];
    let guidedMenuItems = [];
    let guidedFormFields = [];

    const presets = {
        lead_capture: {
            version: '7.3',
            screen_id: 'LEAD_CAPTURE',
            screen_title: 'Cadastro',
            heading: 'Cadastro rápido',
            caption: 'Preencha seus dados para continuar o atendimento.',
            body_text: 'Nossa equipe entrará em contato em breve.',
            submit_label: 'Enviar cadastro',
            fields: [
                { type: 'text', label: 'Nome completo', name: 'nome_completo', required: true, options: [] },
                { type: 'text', label: 'Telefone', name: 'telefone', required: true, options: [] },
                { type: 'text', label: 'E-mail', name: 'email', required: false, options: [] },
                { type: 'radio', label: 'Qual assunto você deseja tratar?', name: 'assunto', required: true, options: ['Comercial', 'Suporte', 'Financeiro'] }
            ]
        },
        contact_form: {
            version: '7.3',
            screen_id: 'CONTACT_US',
            screen_title: 'Fale conosco',
            heading: 'Solicitar contato',
            caption: 'Conte para nós o que você precisa e retornaremos.',
            body_text: 'Resposta em até 2 dias úteis.',
            submit_label: 'Enviar',
            fields: [
                { type: 'text', label: 'Nome', name: 'nome', required: true, options: [] },
                { type: 'text', label: 'Sobrenome', name: 'sobrenome', required: false, options: [] },
                { type: 'textarea', label: 'Mensagem', name: 'mensagem', required: true, options: [] }
            ]
        },
        survey: {
            version: '7.3',
            screen_id: 'SURVEY_SCREEN',
            screen_title: 'Pesquisa',
            heading: 'Pesquisa de satisfação',
            caption: 'Sua opinião ajuda a melhorar nosso atendimento.',
            body_text: 'Obrigado por responder.',
            submit_label: 'Enviar respostas',
            fields: [
                { type: 'radio', label: 'Como você avalia o atendimento?', name: 'atendimento', required: true, options: ['Excelente', 'Bom', 'Regular', 'Ruim'] },
                { type: 'radio', label: 'Você nos indicaria para outra pessoa?', name: 'indicacao', required: true, options: ['Sim', 'Talvez', 'Não'] },
                { type: 'textarea', label: 'Comentário adicional', name: 'comentario', required: false, options: [] }
            ]
        },
        service_qualification: {
            version: '7.3',
            screen_id: 'SERVICE_QUALIFICATION',
            screen_title: 'Qualificação',
            heading: 'Entenda sua necessidade',
            caption: 'Selecione os serviços de interesse para direcionarmos o atendimento.',
            body_text: 'Um especialista analisará sua solicitação.',
            submit_label: 'Continuar',
            fields: [
                { type: 'text', label: 'Nome completo', name: 'nome_completo', required: true, options: [] },
                { type: 'checkbox', label: 'Serviços de interesse', name: 'servicos', required: true, options: ['Implantação', 'Suporte', 'Treinamento', 'Consultoria'] },
                { type: 'textarea', label: 'Detalhes adicionais', name: 'detalhes', required: false, options: [] }
            ]
        }
    };

    function slugify(value, useUnderscore) {
        value = (value || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        value = value.toLowerCase().replace(/[^a-z0-9]+/g, useUnderscore ? '_' : '-').replace(/^[_-]+|[_-]+$/g, '');
        return value || 'campo_' + Math.floor(Math.random() * 1000);
    }

    function alphaSequence(index) {
        let sequence = '';
        let current = parseInt(index, 10);

        if (isNaN(current) || current < 0) {
            current = 0;
        }

        current += 1;
        while (current > 0) {
            current -= 1;
            sequence = String.fromCharCode(65 + (current % 26)) + sequence;
            current = Math.floor(current / 26);
        }

        return sequence || 'A';
    }

    function toMetaIdentifier(value, fallback) {
        const normalized = (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toUpperCase()
            .replace(/[^A-Z]+/g, '_')
            .replace(/_+/g, '_')
            .replace(/^_+|_+$/g, '');

        const resolved = normalized || (fallback || 'SCREEN');
        return resolved.substring(0, 24);
    }

    function composeMetaIdentifier(parts, fallbackParts) {
        const primary = (parts || []).map(function(part){
            return toMetaIdentifier(part, '');
        }).filter(Boolean).join('_');

        const fallback = (fallbackParts || []).map(function(part){
            return toMetaIdentifier(part, '');
        }).filter(Boolean).join('_');

        return toMetaIdentifier(primary, fallback || 'SCREEN');
    }

    function toScreenId(value, fallback) {
        return toMetaIdentifier(value, fallback || 'SCREEN');
    }

    function escapeHtml(value) {
        return $('<div/>').text(value || '').html();
    }

    function normalizeField(field) {
        return {
            id: field && field.id ? field.id : ('f_' + Date.now() + '_' + Math.floor(Math.random() * 100000)),
            type: field && field.type ? field.type : 'text',
            label: field && field.label ? field.label : '',
            name: field && field.name ? field.name : slugify((field && field.label) || 'campo', true),
            required: !!(field && field.required),
            options: Array.isArray(field && field.options) ? field.options : []
        };
    }

    function normalizeSubitem(item) {
        return {
            id: item && item.id ? item.id : ('sub_' + Date.now() + '_' + Math.floor(Math.random() * 100000)),
            title: item && item.title ? item.title : '',
            description: item && item.description ? item.description : '',
            metadata: item && item.metadata ? item.metadata : '',
            detail_text: item && item.detail_text ? item.detail_text : '',
            image_data_url: item && item.image_data_url ? item.image_data_url : '',
            image_alt: item && item.image_alt ? item.image_alt : ''
        };
    }

    function normalizeMenuItem(item) {
        return {
            id: item && item.id ? item.id : ('menu_' + Date.now() + '_' + Math.floor(Math.random() * 100000)),
            title: item && item.title ? item.title : '',
            description: item && item.description ? item.description : '',
            metadata: item && item.metadata ? item.metadata : '',
            badge: item && item.badge ? item.badge : '',
            tags: Array.isArray(item && item.tags) ? item.tags : [],
            side_title: item && item.side_title ? item.side_title : '',
            side_description: item && item.side_description ? item.side_description : '',
            image_data_url: item && item.image_data_url ? item.image_data_url : '',
            image_alt: item && item.image_alt ? item.image_alt : '',
            subitems: Array.isArray(item && item.subitems) ? item.subitems.map(normalizeSubitem) : []
        };
    }

    function ensureGuidedStarter() {
        if (!guidedMenuItems.length) {
            guidedMenuItems = [normalizeMenuItem({
                title: 'Financeiro',
                description: 'Faturas e negociação',
                metadata: '2a via, débitos e negociação',
                subitems: [
                    normalizeSubitem({
                        title: 'Emitir fatura',
                        description: 'Solicite a segunda via',
                        metadata: 'Tenha em mãos seus dados de cadastro',
                        detail_text: 'Explique rapidamente o que o cliente precisa informar.'
                    })
                ]
            })];
        }

        if (!guidedFormFields.length) {
            guidedFormFields = [
                normalizeField({ type: 'text', label: 'Nome completo', name: 'nome_completo', required: true, options: [] }),
                normalizeField({ type: 'text', label: 'Telefone', name: 'telefone', required: true, options: [] }),
                normalizeField({ type: 'textarea', label: 'Detalhes adicionais', name: 'detalhes_adicionais', required: false, options: [] })
            ];
        }
    }

    function getBuilderType() {
        return $('input[name="visual_builder_type"]:checked').val() || 'guided_menu';
    }

    function splitOptions(value) {
        return (value || '').split(/\r?\n/).map(function(option){
            return option.trim();
        }).filter(Boolean);
    }

    function serializeTags(value) {
        return (value || '').split(',').map(function(tag){
            return tag.trim();
        }).filter(Boolean);
    }

    function dataUrlToBase64(dataUrl) {
        if (!dataUrl) {
            return '';
        }

        if (dataUrl.indexOf(',') !== -1) {
            return dataUrl.split(',').pop();
        }

        return dataUrl;
    }

    function buildChoiceSource(options) {
        return (options || []).filter(function(option){
            return option && option.trim() !== '';
        }).map(function(option, index){
            return {
                id: String(index + 1),
                title: option.trim()
            };
        });
    }

    function renderFieldCollection($wrap, fields, emptyText) {
        $wrap.html('');

        if (!fields.length) {
            $wrap.html('<div class="border rounded p-4 text-center text-gray-600 bg-light">' + escapeHtml(emptyText) + '</div>');
            return;
        }

        fields.forEach(function(field, index){
            const normalized = normalizeField(field);
            fields[index] = normalized;
            const optionsText = (normalized.options || []).join('\n');
            const optionsVisible = (normalized.type === 'radio' || normalized.type === 'checkbox' || normalized.type === 'dropdown') ? '' : 'd-none';

            $wrap.append(
                '<div class="card border mb-4 field-item" data-id="' + normalized.id + '">' +
                    '<div class="card-header">' +
                        '<div class="card-title">' + escapeHtml(i18n.fieldLabel) + ' ' + (index + 1) + '</div>' +
                        '<div class="card-toolbar">' +
                            '<button type="button" class="btn btn-sm btn-light-danger remove-field-item"><i class="fad fa-trash-alt pe-0"></i></button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="card-body">' +
                        '<div class="row g-3">' +
                            '<div class="col-md-5">' +
                                '<label class="form-label">' + escapeHtml(i18n.labelQuestion) + '</label>' +
                                '<input type="text" class="form-control form-control-solid field-label" value="' + escapeHtml(normalized.label) + '">' +
                            '</div>' +
                            '<div class="col-md-3">' +
                                '<label class="form-label">' + escapeHtml(i18n.variableName) + '</label>' +
                                '<input type="text" class="form-control form-control-solid field-name" value="' + escapeHtml(normalized.name) + '">' +
                            '</div>' +
                            '<div class="col-md-2">' +
                                '<label class="form-label">' + escapeHtml(i18n.fieldType) + '</label>' +
                                '<select class="form-select form-select-solid field-type">' +
                                    '<option value="text"' + (normalized.type === 'text' ? ' selected' : '') + '>' + escapeHtml(i18n.shortText) + '</option>' +
                                    '<option value="textarea"' + (normalized.type === 'textarea' ? ' selected' : '') + '>' + escapeHtml(i18n.longText) + '</option>' +
                                    '<option value="radio"' + (normalized.type === 'radio' ? ' selected' : '') + '>' + escapeHtml(i18n.singleChoice) + '</option>' +
                                    '<option value="checkbox"' + (normalized.type === 'checkbox' ? ' selected' : '') + '>' + escapeHtml(i18n.multipleChoice) + '</option>' +
                                    '<option value="dropdown"' + (normalized.type === 'dropdown' ? ' selected' : '') + '>' + escapeHtml(i18n.dropdownChoice) + '</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="col-md-2">' +
                                '<label class="form-label">' + escapeHtml(i18n.required) + '</label>' +
                                '<div class="form-check form-switch mt-2">' +
                                    '<input class="form-check-input field-required" type="checkbox"' + (normalized.required ? ' checked' : '') + '>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="mt-3 field-options ' + optionsVisible + '">' +
                            '<label class="form-label">' + escapeHtml(i18n.options) + '</label>' +
                            '<textarea class="form-control form-control-solid field-options-text" rows="4" placeholder="' + escapeHtml(i18n.oneOptionPerLine) + '">' + escapeHtml(optionsText) + '</textarea>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
        });
    }

    function syncFieldCollection($wrap) {
        return $wrap.find('.field-item').map(function(){
            const $item = $(this);
            const type = $item.find('.field-type').val();
            const label = $item.find('.field-label').val();
            const nameInput = $item.find('.field-name').val();
            const options = splitOptions($item.find('.field-options-text').val());

            return normalizeField({
                id: $item.data('id'),
                type: type,
                label: label,
                name: nameInput || slugify(label, true),
                required: $item.find('.field-required').is(':checked'),
                options: options
            });
        }).get();
    }

    function getSimpleState() {
        return {
            version: $('input[name="json_version"]').val() || '7.3',
            screen_id: $('#builder_screen_id').val() || 'SCREEN_1',
            screen_title: $('#builder_screen_title').val() || 'Novo Flow',
            form_name: 'form',
            heading: $('#builder_heading').val() || '',
            caption: $('#builder_caption').val() || '',
            body_text: $('#builder_body_text').val() || '',
            submit_label: $('#builder_submit_label').val() || 'Enviar',
            fields: simpleFields.map(normalizeField)
        };
    }

    function buildSimpleFlowJson(state) {
        const children = [];

        if (state.heading.trim() !== '') {
            children.push({ type: 'TextHeading', text: state.heading.trim() });
        }

        if (state.caption.trim() !== '') {
            children.push({ type: 'TextCaption', text: state.caption.trim() });
        }

        state.fields.forEach(function(field){
            const safeName = slugify(field.name || field.label, true);
            const type = field.type || 'text';

            if (type === 'text') {
                children.push({
                    type: 'TextInput',
                    required: !!field.required,
                    label: field.label || safeName,
                    name: safeName
                });
                return;
            }

            if (type === 'textarea') {
                children.push({
                    type: 'TextArea',
                    required: !!field.required,
                    label: field.label || safeName,
                    name: safeName
                });
                return;
            }

            const options = buildChoiceSource(field.options || []);

            if (type === 'radio') {
                if ((field.label || '').trim() !== '') {
                    children.push({ type: 'TextSubheading', text: field.label.trim() });
                }

                children.push({
                    type: 'RadioButtonsGroup',
                    required: !!field.required,
                    label: field.label || safeName,
                    name: safeName,
                    'data-source': options
                });
                return;
            }

            if (type === 'checkbox') {
                children.push({
                    type: 'CheckboxGroup',
                    required: !!field.required,
                    label: field.label || safeName,
                    name: safeName,
                    'data-source': options
                });
                return;
            }

            if (type === 'dropdown') {
                children.push({
                    type: 'Dropdown',
                    required: !!field.required,
                    label: field.label || safeName,
                    name: safeName,
                    'data-source': options
                });
            }
        });

        if (state.body_text.trim() !== '') {
            children.push({ type: 'TextBody', text: state.body_text.trim() });
        }

        const payload = {
            flow_slug: $('input[name="slug"]').val() || '',
            flow_name: $('input[name="name"]').val() || '',
            screen_id: state.screen_id
        };

        state.fields.forEach(function(field){
            const safeName = slugify(field.name || field.label, true);
            payload[safeName] = '${form.' + safeName + '}';
        });

        children.push({
            type: 'Footer',
            label: state.submit_label,
            'on-click-action': {
                name: 'complete',
                payload: payload
            }
        });

        return JSON.stringify({
            version: state.version,
            screens: [
                {
                    id: toScreenId(state.screen_id || 'SCREEN_1', 'SCREEN_1'),
                    title: state.screen_title,
                    terminal: true,
                    data: {},
                    layout: {
                        type: 'SingleColumnLayout',
                        children: [
                            {
                                type: 'Form',
                                name: state.form_name || 'form',
                                children: children
                            }
                        ]
                    }
                }
            ]
        }, null, 2);
    }

    function replaceTemplateTokens(value, menuItem, subitem) {
        let text = (value || '').toString();
        text = text.replace(/\{\{\s*categoria\s*\}\}/gi, menuItem && menuItem.title ? menuItem.title : '');
        text = text.replace(/\{\{\s*opcao\s*\}\}/gi, subitem && subitem.title ? subitem.title : '');
        return text;
    }

    function buildNavigationListItem(item, nextScreenId, fallbackId) {
        const listItem = {
            id: toMetaIdentifier(item.id || item.title, fallbackId || nextScreenId || 'ITEM'),
            'main-content': {
                title: item.title || 'Item'
            },
            'on-click-action': {
                name: 'navigate',
                next: {
                    name: nextScreenId,
                    type: 'screen'
                },
                payload: {}
            }
        };

        if ((item.description || '').trim() !== '') {
            listItem['main-content'].description = item.description.trim();
        }

        return listItem;
    }

    function buildFinalScreen(formScreenId, menuItem, subitem, finalFormState) {
        const layoutChildren = [];
        const heading = replaceTemplateTokens(finalFormState.heading || subitem.title || menuItem.title || 'Detalhes', menuItem, subitem);
        const caption = replaceTemplateTokens(finalFormState.caption || subitem.description || '', menuItem, subitem);
        const note = replaceTemplateTokens(finalFormState.body_text || subitem.detail_text || '', menuItem, subitem);

        if ((heading || '').trim() !== '') {
            layoutChildren.push({ type: 'TextHeading', text: heading.trim() });
        }

        if ((caption || '').trim() !== '') {
            layoutChildren.push({ type: 'TextCaption', text: caption.trim() });
        }

        const formChildren = [];
        (finalFormState.fields || []).forEach(function(field){
            const safeName = slugify(field.name || field.label, true);
            const type = field.type || 'text';

            if (type === 'text') {
                formChildren.push({
                    type: 'TextInput',
                    required: !!field.required,
                    label: field.label || safeName,
                    name: safeName
                });
                return;
            }

            if (type === 'textarea') {
                formChildren.push({
                    type: 'TextArea',
                    required: !!field.required,
                    label: field.label || safeName,
                    name: safeName
                });
                return;
            }

            const options = buildChoiceSource(field.options || []);

            if (type === 'radio') {
                if ((field.label || '').trim() !== '') {
                    formChildren.push({ type: 'TextSubheading', text: field.label.trim() });
                }

                formChildren.push({
                    type: 'RadioButtonsGroup',
                    required: !!field.required,
                    label: field.label || safeName,
                    name: safeName,
                    'data-source': options
                });
                return;
            }

            if (type === 'checkbox') {
                formChildren.push({
                    type: 'CheckboxGroup',
                    required: !!field.required,
                    label: field.label || safeName,
                    name: safeName,
                    'data-source': options
                });
                return;
            }

            if (type === 'dropdown') {
                formChildren.push({
                    type: 'Dropdown',
                    required: !!field.required,
                    label: field.label || safeName,
                    name: safeName,
                    'data-source': options
                });
            }
        });

        if ((note || '').trim() !== '') {
            formChildren.push({ type: 'TextBody', text: note.trim() });
        }

        const payload = {
            flow_name: $('input[name="name"]').val() || '',
            flow_slug: $('input[name="slug"]').val() || '',
            category_id: slugify(menuItem.id || menuItem.title, true),
            category_title: menuItem.title || '',
            option_id: slugify(subitem.id || subitem.title, true),
            option_title: subitem.title || ''
        };

        (finalFormState.fields || []).forEach(function(field){
            const safeName = slugify(field.name || field.label, true);
            payload[safeName] = '${form.' + safeName + '}';
        });

        formChildren.push({
            type: 'Footer',
            label: finalFormState.submit_label || 'Enviar',
            'on-click-action': {
                name: 'complete',
                payload: payload
            }
        });

        layoutChildren.push({
            type: 'Form',
            name: slugify(menuItem.title + '_' + subitem.title + '_form', true),
            children: formChildren
        });

        return {
            id: formScreenId,
            title: subitem.title || menuItem.title || 'Detalhes',
            terminal: true,
            data: {},
            layout: {
                type: 'SingleColumnLayout',
                children: layoutChildren
            }
        };
    }

    function buildGuidedFlowJson(state) {
        const version = state.version || '7.3';
        const introId = toScreenId(state.intro.screen_id || 'WELCOME', 'WELCOME');
        const menuId = toScreenId(state.menu.screen_id || 'MAIN_MENU', 'MAIN_MENU');
        const introChildren = [];
        const menuItems = [];
        const menuRoutes = [];
        const submenuScreens = [];
        const finalScreens = [];
        const routingModel = {};

        if (state.intro.image_data_url) {
            const introImage = {
                type: 'Image',
                src: dataUrlToBase64(state.intro.image_data_url),
                'scale-type': state.intro.image_scale_type || 'cover'
            };

            if ((state.intro.image_alt || '').trim() !== '') {
                introImage['alt-text'] = state.intro.image_alt.trim();
            }

            if ((state.intro.image_width || '').trim() !== '') {
                introImage.width = parseInt(state.intro.image_width, 10) || undefined;
            }

            if ((state.intro.image_height || '').trim() !== '') {
                introImage.height = parseInt(state.intro.image_height, 10) || undefined;
            }

            if ((state.intro.image_aspect_ratio || '').trim() !== '') {
                introImage['aspect-ratio'] = parseFloat(state.intro.image_aspect_ratio) || undefined;
            }

            introChildren.push(introImage);
        }

        if ((state.intro.heading || '').trim() !== '') {
            introChildren.push({ type: 'TextHeading', text: state.intro.heading.trim() });
        }

        if ((state.intro.caption || '').trim() !== '') {
            introChildren.push({ type: 'TextCaption', text: state.intro.caption.trim() });
        }

        if ((state.intro.body_text || '').trim() !== '') {
            introChildren.push({ type: 'TextBody', text: state.intro.body_text.trim() });
        }

        introChildren.push({
            type: 'Footer',
            label: state.intro.button_label || 'Iniciar',
            'on-click-action': {
                name: 'navigate',
                next: {
                    name: menuId,
                    type: 'screen'
                },
                payload: {}
            }
        });

        (state.items || []).forEach(function(menuItem, menuIndex){
            const item = normalizeMenuItem(menuItem);
            const menuSuffix = alphaSequence(menuIndex);
            const submenuScreenId = composeMetaIdentifier(
                [item.title || 'SECTION', menuSuffix],
                ['SECTION', menuSuffix]
            );
            const submenuRoutes = [];
            const submenuItems = [];

            menuRoutes.push(submenuScreenId);
            menuItems.push(buildNavigationListItem(item, submenuScreenId, submenuScreenId));

            (item.subitems || []).forEach(function(subitem, subIndex){
                const option = normalizeSubitem(subitem);
                const optionSuffix = alphaSequence(subIndex);
                const finalScreenId = composeMetaIdentifier(
                    [item.title || 'SECTION', option.title || 'OPTION', menuSuffix, optionSuffix],
                    ['FINAL', menuSuffix, optionSuffix]
                );

                submenuRoutes.push(finalScreenId);
                submenuItems.push(buildNavigationListItem({
                    id: option.id,
                    title: option.title,
                    description: option.description,
                    metadata: option.metadata,
                    badge: '',
                    tags: [],
                    side_title: '',
                    side_description: '',
                    image_data_url: option.image_data_url,
                    image_alt: option.image_alt
                }, finalScreenId, finalScreenId));
                finalScreens.push(buildFinalScreen(finalScreenId, item, option, state.final_form));
                routingModel[finalScreenId] = [];
            });

            submenuScreens.push({
                id: submenuScreenId,
                title: item.title || ('Section ' + (menuIndex + 1)),
                terminal: false,
                data: {},
                layout: {
                    type: 'SingleColumnLayout',
                    children: [
                        {
                            type: 'NavigationList',
                            name: slugify((item.title || 'section') + '_submenu', true),
                            label: item.title || ('Section ' + (menuIndex + 1)),
                            description: item.description || '',
                            'list-items': submenuItems
                        }
                    ]
                }
            });

            routingModel[submenuScreenId] = submenuRoutes;
        });

        const screens = [
            {
                id: introId,
                title: state.intro.screen_title || 'Boas-vindas',
                terminal: false,
                data: {},
                layout: {
                    type: 'SingleColumnLayout',
                    children: introChildren
                }
            },
            {
                id: menuId,
                title: state.menu.screen_title || 'Menu',
                terminal: false,
                data: {},
                layout: {
                    type: 'SingleColumnLayout',
                    children: [
                        {
                            type: 'NavigationList',
                            name: 'main_menu',
                            label: state.menu.label || 'Escolha uma categoria',
                            description: state.menu.description || '',
                            'list-items': menuItems
                        }
                    ]
                }
            }
        ].concat(submenuScreens, finalScreens);

        routingModel[introId] = [menuId];
        routingModel[menuId] = menuRoutes;

        return JSON.stringify({
            version: version,
            routing_model: routingModel,
            screens: screens
        }, null, 2);
    }

    function getGuidedState() {
        return {
            version: $('input[name="json_version"]').val() || '7.3',
            intro: {
                screen_id: $('#guided_intro_screen_id').val() || 'WELCOME',
                screen_title: $('#guided_intro_screen_title').val() || 'Boas-vindas',
                heading: $('#guided_intro_heading').val() || '',
                caption: $('#guided_intro_caption').val() || '',
                body_text: $('#guided_intro_body_text').val() || '',
                button_label: $('#guided_intro_button_label').val() || 'Iniciar',
                image_data_url: $('#guided_intro_image_data').val() || '',
                image_alt: $('#guided_intro_image_alt').val() || '',
                image_scale_type: $('#guided_intro_image_scale_type').val() || 'cover',
                image_width: $('#guided_intro_image_width').val() || '',
                image_height: $('#guided_intro_image_height').val() || '',
                image_aspect_ratio: $('#guided_intro_image_aspect_ratio').val() || ''
            },
            menu: {
                screen_id: $('#guided_menu_screen_id').val() || 'MAIN_MENU',
                screen_title: $('#guided_menu_screen_title').val() || 'Menu',
                label: $('#guided_menu_label').val() || '',
                description: $('#guided_menu_description').val() || '',
                media_size: $('#guided_menu_media_size').val() || 'regular'
            },
            items: guidedMenuItems.map(normalizeMenuItem),
            final_form: {
                heading: $('#guided_form_heading').val() || '',
                caption: $('#guided_form_caption').val() || '',
                body_text: $('#guided_form_body_text').val() || '',
                submit_label: $('#guided_form_submit_label').val() || 'Enviar',
                fields: guidedFormFields.map(normalizeField)
            }
        };
    }

    function renderSimpleFields() {
        renderFieldCollection($('#builder_fields'), simpleFields, i18n.noSimpleField);
        updateJsonPreview();
    }

    function renderGuidedFormFields() {
        renderFieldCollection($('#guided_form_fields'), guidedFormFields, i18n.noGuidedField);
        updateGuidedPreview();
    }

    function renderPreviewImage($target, dataUrl, emptyText) {
        if (!dataUrl) {
            $target.html('<div class="text-gray-600">' + escapeHtml(emptyText) + '</div>');
            return;
        }

        $target.html('<img src="' + escapeHtml(dataUrl) + '" alt="" class="img-fluid rounded" style="max-height: 260px;">');
    }

    function syncGuidedMenuItemsFromDom() {
        guidedMenuItems = $('#guided_menu_items .guided-menu-item').map(function(){
            const $item = $(this);
            const subitems = $item.find('.guided-subitem-item').map(function(){
                const $sub = $(this);
                return normalizeSubitem({
                    id: $sub.data('id'),
                    title: $sub.find('.guided-subitem-title').val(),
                    description: $sub.find('.guided-subitem-description').val(),
                    metadata: $sub.find('.guided-subitem-metadata').val(),
                    detail_text: $sub.find('.guided-subitem-detail-text').val(),
                    image_data_url: $sub.find('.guided-subitem-image-data').val(),
                    image_alt: $sub.find('.guided-subitem-image-alt').val()
                });
            }).get();

            return normalizeMenuItem({
                id: $item.data('id'),
                title: $item.find('.guided-menu-title').val(),
                description: $item.find('.guided-menu-description').val(),
                metadata: $item.find('.guided-menu-metadata').val(),
                badge: $item.find('.guided-menu-badge').val(),
                tags: serializeTags($item.find('.guided-menu-tags').val()),
                side_title: $item.find('.guided-menu-side-title').val(),
                side_description: $item.find('.guided-menu-side-description').val(),
                image_data_url: $item.find('.guided-menu-image-data').val(),
                image_alt: $item.find('.guided-menu-image-alt').val(),
                subitems: subitems
            });
        }).get();
    }

    function renderGuidedMenuItems() {
        const $wrap = $('#guided_menu_items');
        $wrap.html('');

        if (!guidedMenuItems.length) {
            $wrap.html('<div class="border rounded p-4 text-center text-gray-600 bg-light">' + escapeHtml(i18n.noMenuItem) + '</div>');
            updateGuidedPreview();
            return;
        }

        guidedMenuItems.forEach(function(item, itemIndex){
            const normalizedItem = normalizeMenuItem(item);
            guidedMenuItems[itemIndex] = normalizedItem;

            let subitemsHtml = '';

            if (!normalizedItem.subitems.length) {
                subitemsHtml = '<div class="border rounded p-4 text-center text-gray-600 bg-light">' + escapeHtml(i18n.noSubitem) + '</div>';
            } else {
                normalizedItem.subitems.forEach(function(subitem, subIndex){
                    const normalizedSubitem = normalizeSubitem(subitem);
                    guidedMenuItems[itemIndex].subitems[subIndex] = normalizedSubitem;
                    subitemsHtml +=
                        '<div class="card border mb-4 guided-subitem-item" data-id="' + normalizedSubitem.id + '">' +
                            '<div class="card-header">' +
                                '<div class="card-title">' + escapeHtml(i18n.submenuLabel) + ' ' + (subIndex + 1) + '</div>' +
                                '<div class="card-toolbar">' +
                                    '<button type="button" class="btn btn-sm btn-light-danger remove-guided-subitem"><i class="fad fa-trash-alt pe-0"></i></button>' +
                                '</div>' +
                            '</div>' +
                            '<div class="card-body">' +
                                '<div class="row g-3">' +
                                    '<div class="col-md-4">' +
                                        '<label class="form-label">' + escapeHtml(i18n.menuTitle) + '</label>' +
                                        '<input type="text" class="form-control form-control-solid guided-subitem-title" value="' + escapeHtml(normalizedSubitem.title) + '">' +
                                    '</div>' +
                                    '<div class="col-md-4">' +
                                        '<label class="form-label">' + escapeHtml(i18n.menuDescription) + '</label>' +
                                        '<input type="text" class="form-control form-control-solid guided-subitem-description" value="' + escapeHtml(normalizedSubitem.description) + '">' +
                                    '</div>' +
                                    '<div class="col-md-4">' +
                                        '<label class="form-label">' + escapeHtml(i18n.menuMetadata) + '</label>' +
                                        '<input type="text" class="form-control form-control-solid guided-subitem-metadata" value="' + escapeHtml(normalizedSubitem.metadata) + '">' +
                                    '</div>' +
                                '</div>' +
                                '<div class="mt-3">' +
                                    '<label class="form-label">' + escapeHtml(i18n.submenuDetail) + '</label>' +
                                    '<textarea class="form-control form-control-solid guided-subitem-detail-text" rows="3">' + escapeHtml(normalizedSubitem.detail_text) + '</textarea>' +
                                '</div>' +
                                '<div class="row g-3 mt-1">' +
                                    '<div class="col-md-8">' +
                                        '<label class="form-label">' + escapeHtml(i18n.submenuImage) + '</label>' +
                                        '<input type="file" class="form-control guided-subitem-image-file" accept="image/png,image/jpeg,image/webp">' +
                                        '<input type="hidden" class="guided-subitem-image-data" value="' + escapeHtml(normalizedSubitem.image_data_url) + '">' +
                                    '</div>' +
                                    '<div class="col-md-4">' +
                                        '<label class="form-label">' + escapeHtml(i18n.itemImageAlt) + '</label>' +
                                        '<input type="text" class="form-control form-control-solid guided-subitem-image-alt" value="' + escapeHtml(normalizedSubitem.image_alt) + '">' +
                                    '</div>' +
                                '</div>' +
                                '<div class="mt-3">' +
                                    '<div class="border rounded bg-light min-h-125px d-flex align-items-center justify-content-center p-4 text-gray-600 guided-subitem-image-preview">' +
                                        (normalizedSubitem.image_data_url ? '<img src="' + escapeHtml(normalizedSubitem.image_data_url) + '" alt="" class="img-fluid rounded" style="max-height: 180px;">' : escapeHtml(i18n.noSubitemImage)) +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                });
            }

            $wrap.append(
                '<div class="card border mb-5 guided-menu-item" data-id="' + normalizedItem.id + '">' +
                    '<div class="card-header">' +
                        '<div class="card-title">' + escapeHtml(i18n.sectionLabel) + ' ' + (itemIndex + 1) + '</div>' +
                        '<div class="card-toolbar">' +
                            '<button type="button" class="btn btn-sm btn-light-danger remove-guided-menu-item"><i class="fad fa-trash-alt pe-0"></i></button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="card-body">' +
                        '<div class="row g-3">' +
                            '<div class="col-md-4">' +
                                '<label class="form-label">' + escapeHtml(i18n.menuTitle) + '</label>' +
                                '<input type="text" class="form-control form-control-solid guided-menu-title" value="' + escapeHtml(normalizedItem.title) + '">' +
                            '</div>' +
                            '<div class="col-md-4">' +
                                '<label class="form-label">' + escapeHtml(i18n.menuDescription) + '</label>' +
                                '<input type="text" class="form-control form-control-solid guided-menu-description" value="' + escapeHtml(normalizedItem.description) + '">' +
                            '</div>' +
                            '<div class="col-md-4">' +
                                '<label class="form-label">' + escapeHtml(i18n.menuMetadata) + '</label>' +
                                '<input type="text" class="form-control form-control-solid guided-menu-metadata" value="' + escapeHtml(normalizedItem.metadata) + '">' +
                            '</div>' +
                        '</div>' +
                        '<div class="row g-3 mt-1">' +
                            '<div class="col-md-3">' +
                                '<label class="form-label">' + escapeHtml(i18n.badge) + '</label>' +
                                '<input type="text" class="form-control form-control-solid guided-menu-badge" value="' + escapeHtml(normalizedItem.badge) + '">' +
                            '</div>' +
                            '<div class="col-md-3">' +
                                '<label class="form-label">' + escapeHtml(i18n.tags) + '</label>' +
                                '<input type="text" class="form-control form-control-solid guided-menu-tags" value="' + escapeHtml((normalizedItem.tags || []).join(', ')) + '" placeholder="' + escapeHtml(i18n.commaSeparated) + '">' +
                            '</div>' +
                            '<div class="col-md-3">' +
                                '<label class="form-label">' + escapeHtml(i18n.sideTitle) + '</label>' +
                                '<input type="text" class="form-control form-control-solid guided-menu-side-title" value="' + escapeHtml(normalizedItem.side_title) + '">' +
                            '</div>' +
                            '<div class="col-md-3">' +
                                '<label class="form-label">' + escapeHtml(i18n.sideDescription) + '</label>' +
                                '<input type="text" class="form-control form-control-solid guided-menu-side-description" value="' + escapeHtml(normalizedItem.side_description) + '">' +
                            '</div>' +
                        '</div>' +
                        '<div class="row g-3 mt-1">' +
                            '<div class="col-md-8">' +
                                '<label class="form-label">' + escapeHtml(i18n.menuImage) + '</label>' +
                                '<input type="file" class="form-control guided-menu-image-file" accept="image/png,image/jpeg,image/webp">' +
                                '<input type="hidden" class="guided-menu-image-data" value="' + escapeHtml(normalizedItem.image_data_url) + '">' +
                            '</div>' +
                            '<div class="col-md-4">' +
                                '<label class="form-label">' + escapeHtml(i18n.itemImageAlt) + '</label>' +
                                '<input type="text" class="form-control form-control-solid guided-menu-image-alt" value="' + escapeHtml(normalizedItem.image_alt) + '">' +
                            '</div>' +
                        '</div>' +
                        '<div class="mt-3">' +
                            '<div class="border rounded bg-light min-h-125px d-flex align-items-center justify-content-center p-4 text-gray-600 guided-menu-image-preview">' +
                                (normalizedItem.image_data_url ? '<img src="' + escapeHtml(normalizedItem.image_data_url) + '" alt="" class="img-fluid rounded" style="max-height: 180px;">' : escapeHtml(i18n.noItemImage)) +
                            '</div>' +
                        '</div>' +
                        '<div class="mt-5 d-flex justify-content-between align-items-center">' +
                            '<div>' +
                                '<h5 class="mb-1">' + escapeHtml(i18n.menuOptions) + '</h5>' +
                                '<div class="fs-12 text-gray-600">' + escapeHtml('Cada opção gera uma tela final própria com o formulário compartilhado.') + '</div>' +
                            '</div>' +
                            '<button type="button" class="btn btn-light-primary add-guided-subitem">' + escapeHtml(i18n.addSubitem) + '</button>' +
                        '</div>' +
                        '<div class="mt-4 guided-subitems-wrap">' + subitemsHtml + '</div>' +
                    '</div>' +
                '</div>'
            );
        });

        updateGuidedPreview();
    }

    function fillSimpleBuilder(builder) {
        $('#builder_screen_id').val(builder.screen_id || 'SCREEN_1');
        $('#builder_screen_title').val(builder.screen_title || 'Novo Flow');
        $('#builder_heading').val(builder.heading || '');
        $('#builder_caption').val(builder.caption || '');
        $('#builder_body_text').val(builder.body_text || '');
        $('#builder_submit_label').val(builder.submit_label || 'Enviar');
        simpleFields = Array.isArray(builder.fields) ? builder.fields.map(normalizeField) : [];
        renderSimpleFields();
    }

    function fillGuidedBuilder(builder) {
        const intro = builder.intro || {};
        const menu = builder.menu || {};
        const finalForm = builder.final_form || {};

        $('#guided_intro_screen_id').val(intro.screen_id || 'WELCOME');
        $('#guided_intro_screen_title').val(intro.screen_title || 'Boas-vindas');
        $('#guided_intro_heading').val(intro.heading || '');
        $('#guided_intro_caption').val(intro.caption || '');
        $('#guided_intro_body_text').val(intro.body_text || '');
        $('#guided_intro_button_label').val(intro.button_label || 'Iniciar');
        $('#guided_intro_image_data').val(intro.image_data_url || '');
        $('#guided_intro_image_alt').val(intro.image_alt || '');
        $('#guided_intro_image_scale_type').val(intro.image_scale_type || 'cover');
        $('#guided_intro_image_width').val(intro.image_width || '');
        $('#guided_intro_image_height').val(intro.image_height || '');
        $('#guided_intro_image_aspect_ratio').val(intro.image_aspect_ratio || '');

        $('#guided_menu_screen_id').val(menu.screen_id || 'MAIN_MENU');
        $('#guided_menu_screen_title').val(menu.screen_title || 'Menu');
        $('#guided_menu_label').val(menu.label || '');
        $('#guided_menu_description').val(menu.description || '');
        $('#guided_menu_media_size').val(menu.media_size || 'regular');

        $('#guided_form_heading').val(finalForm.heading || '');
        $('#guided_form_caption').val(finalForm.caption || '');
        $('#guided_form_body_text').val(finalForm.body_text || '');
        $('#guided_form_submit_label').val(finalForm.submit_label || 'Enviar');

        guidedMenuItems = Array.isArray(builder.items) ? builder.items.map(normalizeMenuItem) : [];
        guidedFormFields = Array.isArray(finalForm.fields) ? finalForm.fields.map(normalizeField) : [];

        ensureGuidedStarter();
        renderPreviewImage($('#guided_intro_image_preview'), intro.image_data_url || '', i18n.noCoverImage);
        renderGuidedMenuItems();
        renderGuidedFormFields();
    }

    function updateGuidedPreview() {
        const state = getGuidedState();
        const json = buildGuidedFlowJson(state);
        $('#guided_json_preview').val(json);
        if (getBuilderType() === 'guided_menu') {
            $('#flow_json_hidden').val(json);
            $('#builder_state_hidden').val(JSON.stringify(getVisualBuilderState()));
        }
    }

    function updateJsonPreview() {
        const state = getSimpleState();
        const json = buildSimpleFlowJson(state);
        $('#builder_json_preview').val(json);
        if (getBuilderType() === 'simple_form') {
            $('#flow_json_hidden').val(json);
            $('#builder_state_hidden').val(JSON.stringify(getVisualBuilderState()));
        }
    }

    function getVisualBuilderState() {
        return {
            builder_type: getBuilderType(),
            simple_form: getSimpleState(),
            guided_menu: getGuidedState()
        };
    }

    function applyVisualBuilderType() {
        const type = getBuilderType();
        $('#builder-guided-panel').toggle(type === 'guided_menu');
        $('#builder-simple-panel').toggle(type === 'simple_form');

        if (type === 'guided_menu') {
            updateGuidedPreview();
        } else {
            updateJsonPreview();
        }
    }

    function openVisualTab() {
        const tab = new bootstrap.Tab(document.getElementById('flow-visual-tab'));
        tab.show();
    }

    function syncAndRefreshSimple() {
        simpleFields = syncFieldCollection($('#builder_fields'));
        updateJsonPreview();
    }

    function syncAndRefreshGuidedForm() {
        guidedFormFields = syncFieldCollection($('#guided_form_fields'));
        updateGuidedPreview();
    }

    $(document).on('click', '#add_builder_field', function(){
        simpleFields.push(normalizeField({
            type: 'text',
            label: '',
            name: '',
            required: false,
            options: []
        }));
        renderSimpleFields();
    });

    $(document).on('click', '#add_guided_form_field', function(){
        guidedFormFields.push(normalizeField({
            type: 'text',
            label: '',
            name: '',
            required: false,
            options: []
        }));
        renderGuidedFormFields();
    });

    $(document).on('click', '#add_guided_menu_item', function(){
        guidedMenuItems.push(normalizeMenuItem({
            title: '',
            description: '',
            metadata: '',
            badge: '',
            tags: [],
            side_title: '',
            side_description: '',
            image_data_url: '',
            image_alt: '',
            subitems: [normalizeSubitem({
                title: '',
                description: '',
                metadata: '',
                detail_text: '',
                image_data_url: '',
                image_alt: ''
            })]
        }));
        renderGuidedMenuItems();
    });

    $(document).on('click', '.remove-field-item', function(){
        const $wrap = $(this).closest('.card-body').find('.field-item').closest('#builder_fields, #guided_form_fields');
        const $container = $(this).closest('#builder_fields, #guided_form_fields');
        const id = $(this).closest('.field-item').data('id');

        if ($container.attr('id') === 'guided_form_fields') {
            guidedFormFields = guidedFormFields.filter(function(field){ return field.id !== id; });
            renderGuidedFormFields();
            return;
        }

        simpleFields = simpleFields.filter(function(field){ return field.id !== id; });
        renderSimpleFields();
    });

    $(document).on('click', '.remove-guided-menu-item', function(){
        const id = $(this).closest('.guided-menu-item').data('id');
        guidedMenuItems = guidedMenuItems.filter(function(item){ return item.id !== id; });
        renderGuidedMenuItems();
    });

    $(document).on('click', '.add-guided-subitem', function(){
        const id = $(this).closest('.guided-menu-item').data('id');
        guidedMenuItems = guidedMenuItems.map(function(item){
            if (item.id === id) {
                item.subitems = item.subitems || [];
                item.subitems.push(normalizeSubitem({
                    title: '',
                    description: '',
                    metadata: '',
                    detail_text: '',
                    image_data_url: '',
                    image_alt: ''
                }));
            }
            return item;
        });
        renderGuidedMenuItems();
    });

    $(document).on('click', '.remove-guided-subitem', function(){
        const itemId = $(this).closest('.guided-menu-item').data('id');
        const subId = $(this).closest('.guided-subitem-item').data('id');

        guidedMenuItems = guidedMenuItems.map(function(item){
            if (item.id === itemId) {
                item.subitems = (item.subitems || []).filter(function(subitem){
                    return subitem.id !== subId;
                });
            }
            return item;
        });
        renderGuidedMenuItems();
    });

    $(document).on('input change', '#builder_screen_id, #builder_screen_title, #builder_heading, #builder_caption, #builder_body_text, #builder_submit_label, input[name="name"], input[name="slug"], input[name="json_version"]', function(){
        updateJsonPreview();
    });

    $(document).on('input change', '#guided_intro_screen_id, #guided_intro_screen_title, #guided_intro_heading, #guided_intro_caption, #guided_intro_body_text, #guided_intro_button_label, #guided_intro_image_alt, #guided_intro_image_scale_type, #guided_intro_image_width, #guided_intro_image_height, #guided_intro_image_aspect_ratio, #guided_menu_screen_id, #guided_menu_screen_title, #guided_menu_label, #guided_menu_description, #guided_menu_media_size, #guided_form_heading, #guided_form_caption, #guided_form_body_text, #guided_form_submit_label, input[name="name"], input[name="slug"], input[name="json_version"]', function(){
        updateGuidedPreview();
    });

    $(document).on('input change', '#builder_fields .field-label, #builder_fields .field-name, #builder_fields .field-type, #builder_fields .field-required, #builder_fields .field-options-text', function(){
        const $item = $(this).closest('.field-item');
        if ($(this).hasClass('field-type')) {
            const type = $(this).val();
            $item.find('.field-options').toggleClass('d-none', !(type === 'radio' || type === 'checkbox' || type === 'dropdown'));
        }

        if ($(this).hasClass('field-label')) {
            const $name = $item.find('.field-name');
            if ($name.val().trim() === '') {
                $name.val(slugify($(this).val(), true));
            }
        }

        syncAndRefreshSimple();
    });

    $(document).on('input change', '#guided_form_fields .field-label, #guided_form_fields .field-name, #guided_form_fields .field-type, #guided_form_fields .field-required, #guided_form_fields .field-options-text', function(){
        const $item = $(this).closest('.field-item');
        if ($(this).hasClass('field-type')) {
            const type = $(this).val();
            $item.find('.field-options').toggleClass('d-none', !(type === 'radio' || type === 'checkbox' || type === 'dropdown'));
        }

        if ($(this).hasClass('field-label')) {
            const $name = $item.find('.field-name');
            if ($name.val().trim() === '') {
                $name.val(slugify($(this).val(), true));
            }
        }

        syncAndRefreshGuidedForm();
    });

    $(document).on('input change', '.guided-menu-title, .guided-menu-description, .guided-menu-metadata, .guided-menu-badge, .guided-menu-tags, .guided-menu-side-title, .guided-menu-side-description, .guided-menu-image-alt, .guided-subitem-title, .guided-subitem-description, .guided-subitem-metadata, .guided-subitem-detail-text, .guided-subitem-image-alt', function(){
        syncGuidedMenuItemsFromDom();
        updateGuidedPreview();
    });

    function readImageInput(file, callback) {
        if (!file) {
            callback('');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            callback(e.target.result || '');
        };
        reader.readAsDataURL(file);
    }

    $(document).on('change', '#guided_intro_image_file', function(){
        const file = this.files && this.files[0] ? this.files[0] : null;
        readImageInput(file, function(dataUrl){
            $('#guided_intro_image_data').val(dataUrl);
            renderPreviewImage($('#guided_intro_image_preview'), dataUrl, i18n.noCoverImage);
            updateGuidedPreview();
        });
    });

    $(document).on('click', '#guided_intro_remove_image', function(){
        $('#guided_intro_image_data').val('');
        $('#guided_intro_image_file').val('');
        renderPreviewImage($('#guided_intro_image_preview'), '', i18n.noCoverImage);
        updateGuidedPreview();
    });

    $(document).on('change', '.guided-menu-image-file', function(){
        const $card = $(this).closest('.guided-menu-item');
        const file = this.files && this.files[0] ? this.files[0] : null;
        readImageInput(file, function(dataUrl){
            $card.find('.guided-menu-image-data').val(dataUrl);
            syncGuidedMenuItemsFromDom();
            renderGuidedMenuItems();
        });
    });

    $(document).on('change', '.guided-subitem-image-file', function(){
        const $card = $(this).closest('.guided-subitem-item');
        const file = this.files && this.files[0] ? this.files[0] : null;
        readImageInput(file, function(dataUrl){
            $card.find('.guided-subitem-image-data').val(dataUrl);
            syncGuidedMenuItemsFromDom();
            renderGuidedMenuItems();
        });
    });

    $(document).on('change', 'input[name="visual_builder_type"]', function(){
        applyVisualBuilderType();
        $('#builder_state_hidden').val(JSON.stringify(getVisualBuilderState()));
    });

    $(document).on('click', '#apply_flow_preset', function(){
        const presetKey = $('#flow_builder_preset').val();
        if (!presetKey || !presets[presetKey]) {
            Core.notify(i18n.selectPreset, 'warning');
            return;
        }

        fillSimpleBuilder(presets[presetKey]);
        $('input[name="visual_builder_type"][value="simple_form"]').prop('checked', true);
        applyVisualBuilderType();
        openVisualTab();
        Core.notify(i18n.modelApplied, 'success');
    });

    function validateSimpleBuilder() {
        const invalidChoiceField = simpleFields.find(function(field){
            return (field.type === 'radio' || field.type === 'checkbox' || field.type === 'dropdown') && (!field.options || !field.options.length);
        });

        if (invalidChoiceField) {
            Core.notify(i18n.simpleFieldNeedsOptions, 'error');
            return false;
        }

        return true;
    }

    function validateGuidedBuilder() {
        if (!guidedMenuItems.length) {
            Core.notify(i18n.guidedNeedsSection, 'error');
            return false;
        }

        if (!$('#guided_intro_screen_id').val().trim() || !$('#guided_menu_screen_id').val().trim()) {
            Core.notify(i18n.invalidGuidedScreen, 'error');
            return false;
        }

        const invalidChoiceField = guidedFormFields.find(function(field){
            return (field.type === 'radio' || field.type === 'checkbox' || field.type === 'dropdown') && (!field.options || !field.options.length);
        });

        if (invalidChoiceField) {
            Core.notify(i18n.simpleFieldNeedsOptions, 'error');
            return false;
        }

        const missingTitle = guidedMenuItems.find(function(menuItem){
            if (!(menuItem.title || '').trim()) {
                return true;
            }

            if (!menuItem.subitems || !menuItem.subitems.length) {
                return true;
            }

            return menuItem.subitems.some(function(subitem){
                return !(subitem.title || '').trim();
            });
        });

        if (missingTitle) {
            Core.notify(i18n.invalidGuidedName, 'error');
            return false;
        }

        return true;
    }

    $('#wa-flow-form').on('submit', function(e){
        const advancedActive = $('#flow-advanced-pane').hasClass('active') || $('#flow-advanced-pane').hasClass('show');

        if (advancedActive) {
            const rawJson = $('#flow_json_editor').val();
            try {
                JSON.parse(rawJson);
            } catch (err) {
                e.preventDefault();
                Core.notify(i18n.invalidJson, 'error');
                return false;
            }

            $('#flow_json_hidden').val(rawJson);
            $('#builder_state_hidden').val(JSON.stringify(getVisualBuilderState()));
            return true;
        }

        const builderType = getBuilderType();

        if (builderType === 'simple_form') {
            simpleFields = syncFieldCollection($('#builder_fields'));

            if (!validateSimpleBuilder()) {
                e.preventDefault();
                return false;
            }

            updateJsonPreview();
            $('#builder_state_hidden').val(JSON.stringify(getVisualBuilderState()));
            return true;
        }

        guidedMenuItems = [];
        syncGuidedMenuItemsFromDom();
        guidedFormFields = syncFieldCollection($('#guided_form_fields'));

        if (!validateGuidedBuilder()) {
            e.preventDefault();
            return false;
        }

        updateGuidedPreview();
        $('#builder_state_hidden').val(JSON.stringify(getVisualBuilderState()));
        return true;
    });

    function fillVisualBuilder(builderState) {
        const builderType = builderState && builderState.builder_type ? builderState.builder_type : 'guided_menu';
        const simpleState = builderState && builderState.simple_form ? builderState.simple_form : {};
        const guidedState = builderState && builderState.guided_menu ? builderState.guided_menu : {};

        $('input[name="visual_builder_type"][value="' + builderType + '"]').prop('checked', true);
        fillSimpleBuilder(simpleState);
        fillGuidedBuilder(guidedState);
        applyVisualBuilderType();
    }

    $(function(){
        fillVisualBuilder(initialVisualBuilder);

        if ($('#flow_json_editor').val().trim() !== '' && $('#flow_json_hidden').val().trim() === '') {
            $('#flow_json_hidden').val($('#flow_json_editor').val());
        }
    });
})(jQuery);
</script>
