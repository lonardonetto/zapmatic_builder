<?php
namespace Core\Whatsapp_carousel_template\Controllers;

class Whatsapp_carousel_template extends \CodeIgniter\Controller
{
    protected $config;
    protected $model;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . '/../Config.php'));
        $this->model = new \Core\Whatsapp_carousel_template\Models\Whatsapp_carousel_templateModel();
    }

    public function index($page = false, $ids = false)
    {
        $data = [
            'title' => $this->config['name'],
            'desc'  => $this->config['desc'],
        ];

        switch ($page) {
            case 'update':
                $item = false;
                if ($ids) {
                    $team_id = get_team('id');
                    $item = db_get('*', TB_WHATSAPP_TEMPLATE, [
                        'type'    => 5,
                        'ids'     => $ids,
                        'team_id' => $team_id,
                    ]);
                }

                $data['content'] = view('Core\Whatsapp_carousel_template\Views\update', [
                    'result' => $item,
                    'config' => $this->config,
                ]);
                break;

            default:
                $total = $this->model->get_list(false);

                $datatable = [
                    'total_items'  => $total,
                    'per_page'     => 30,
                    'current_page' => 1,
                ];

                $data_content = [
                    'total'    => $total,
                    'datatable'=> $datatable,
                    'config'   => $this->config,
                ];

                $data['content'] = view('Core\Whatsapp_carousel_template\Views\content', $data_content);
                break;
        }

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function widget_menu($params = [])
    {
        if (!permission('whatsapp_carousel_template')) {
            return '';
        }

        $result = $params['result'];
        return view('Core\Whatsapp_carousel_template\Views\widget\menu', ['result' => $result]);
    }

    public function widget_content($params = [])
    {
        if (!permission('whatsapp_carousel_template')) {
            return '';
        }

        $team_id = get_team('id');
        $templates = db_fetch('*', TB_WHATSAPP_TEMPLATE, [
            'type'    => 5,
            'team_id' => $team_id,
        ]);

        return view('Core\Whatsapp_carousel_template\Views\widget\content', [
            'result'    => $params['result'],
            'templates' => $templates,
        ]);
    }

    public function ajax_list()
    {
        $total_items = $this->model->get_list(false);
        $result      = $this->model->get_list(true);
        $data        = [
            'result' => $result,
            'config' => $this->config,
        ];

        ms([
            'total_items' => $total_items,
            'data'        => view('Core\Whatsapp_carousel_template\Views\ajax_list', $data),
        ]);
    }

    public function save($ids = false)
    {
        $name           = post('name');
        $message_title  = post('message_title');
        $message_body   = post('message_body');
        $message_footer = post('message_footer');
        $advance_options = post('advance_options');

        $card_titles   = post('card_title');
        $card_bodies   = post('card_body');
        $card_footers  = post('card_footer');
        $card_media    = post('card_media');

        $card_btn_type    = post('card_btn_type');
        $card_btn_text    = post('card_btn_display_text');
        $card_btn_url     = post('card_btn_url');
        $card_btn_phone   = post('card_btn_phone');
        $card_btn_copy    = post('card_btn_copy');
        $card_btn_catalog_phone = post('card_btn_catalog_phone');
        $card_btn_catalog_product = post('card_btn_catalog_product');
        $card_btn_pix_merchant = post('card_btn_pix_merchant');
        $card_btn_pix_key = post('card_btn_pix_key');
        $card_btn_pix_key_type = post('card_btn_pix_key_type');
        $card_btn_pay_currency = post('card_btn_pay_currency');
        $card_btn_pay_amount = post('card_btn_pay_amount');
        $card_btn_pay_reference = post('card_btn_pay_reference');
        $card_btn_pay_item_name = post('card_btn_pay_item_name');
        $card_btn_pay_note = post('card_btn_pay_note');

        $card_titles   = is_array($card_titles) ? array_values($card_titles) : [];
        $card_bodies   = is_array($card_bodies) ? array_values($card_bodies) : [];
        $card_footers  = is_array($card_footers) ? array_values($card_footers) : [];
        $card_media    = is_array($card_media) ? array_values($card_media) : [];

        $card_btn_type    = is_array($card_btn_type) ? array_values($card_btn_type) : [];
        $card_btn_text    = is_array($card_btn_text) ? array_values($card_btn_text) : [];
        $card_btn_url     = is_array($card_btn_url) ? array_values($card_btn_url) : [];
        $card_btn_phone   = is_array($card_btn_phone) ? array_values($card_btn_phone) : [];
        $card_btn_copy    = is_array($card_btn_copy) ? array_values($card_btn_copy) : [];
        $card_btn_catalog_phone = is_array($card_btn_catalog_phone) ? array_values($card_btn_catalog_phone) : [];
        $card_btn_catalog_product = is_array($card_btn_catalog_product) ? array_values($card_btn_catalog_product) : [];
        $card_btn_pix_merchant = is_array($card_btn_pix_merchant) ? array_values($card_btn_pix_merchant) : [];
        $card_btn_pix_key = is_array($card_btn_pix_key) ? array_values($card_btn_pix_key) : [];
        $card_btn_pix_key_type = is_array($card_btn_pix_key_type) ? array_values($card_btn_pix_key_type) : [];
        $card_btn_pay_currency = is_array($card_btn_pay_currency) ? array_values($card_btn_pay_currency) : [];
        $card_btn_pay_amount = is_array($card_btn_pay_amount) ? array_values($card_btn_pay_amount) : [];
        $card_btn_pay_reference = is_array($card_btn_pay_reference) ? array_values($card_btn_pay_reference) : [];
        $card_btn_pay_item_name = is_array($card_btn_pay_item_name) ? array_values($card_btn_pay_item_name) : [];
        $card_btn_pay_note = is_array($card_btn_pay_note) ? array_values($card_btn_pay_note) : [];

        $team_id = get_team('id');

        validate('null', __('Carousel name'), $name);

        if (empty($card_titles) || !is_array($card_titles)) {
            ms([
                'status'  => 'error',
                'message' => __('Add at least one card'),
            ]);
        }

        if (count($card_titles) > 10) {
            ms([
                'status'  => 'error',
                'message' => __('Only up to 10 cards allowed'),
            ]);
        }

        $shortlink_by = false;
        if (!empty($advance_options) && isset($advance_options['shortlink'])) {
            $shortlink_by = shortlink_by(['advance_options' => ['shortlink' => $advance_options['shortlink']]]);
        }

        $message_title  = shortlink($message_title, $shortlink_by);
        $message_body   = shortlink($message_body, $shortlink_by);
        $message_footer = shortlink($message_footer, $shortlink_by);

        $cards = [];

        foreach ($card_titles as $i => $title) {
            $cardPosition = $i + 1;
            $title = trim($title);
            if ($title === '') {
                ms([
                    'status'  => 'error',
                    'message' => sprintf(__('Card %s: Title is required'), $cardPosition),
                ]);
            }

            $bodyRaw = isset($card_bodies[$i]) ? $card_bodies[$i] : '';
            $body = shortlink(trim($bodyRaw), $shortlink_by);
            if ($body === '') {
                ms([
                    'status'  => 'error',
                    'message' => sprintf(__('Card %s: Body is required'), $cardPosition),
                ]);
            }

            $footerRaw = isset($card_footers[$i]) ? $card_footers[$i] : '';
            $footer = shortlink(trim($footerRaw), $shortlink_by);
            $media_url = isset($card_media[$i]) ? trim($card_media[$i]) : '';
            if ($media_url !== '') {
                $media_url = shortlink($media_url, $shortlink_by);
            }

            $buttons = [];
            $cardBtnType = isset($card_btn_type[$i]) && is_array($card_btn_type[$i]) ? array_values($card_btn_type[$i]) : [];
            $cardBtnText = isset($card_btn_text[$i]) && is_array($card_btn_text[$i]) ? array_values($card_btn_text[$i]) : [];
            $cardBtnUrl = isset($card_btn_url[$i]) && is_array($card_btn_url[$i]) ? array_values($card_btn_url[$i]) : [];
            $cardBtnPhone = isset($card_btn_phone[$i]) && is_array($card_btn_phone[$i]) ? array_values($card_btn_phone[$i]) : [];
            $cardBtnCopy = isset($card_btn_copy[$i]) && is_array($card_btn_copy[$i]) ? array_values($card_btn_copy[$i]) : [];
            $cardBtnCatalogPhone = isset($card_btn_catalog_phone[$i]) && is_array($card_btn_catalog_phone[$i]) ? array_values($card_btn_catalog_phone[$i]) : [];
            $cardBtnCatalogProduct = isset($card_btn_catalog_product[$i]) && is_array($card_btn_catalog_product[$i]) ? array_values($card_btn_catalog_product[$i]) : [];
            $cardBtnPixMerchant = isset($card_btn_pix_merchant[$i]) && is_array($card_btn_pix_merchant[$i]) ? array_values($card_btn_pix_merchant[$i]) : [];
            $cardBtnPixKey = isset($card_btn_pix_key[$i]) && is_array($card_btn_pix_key[$i]) ? array_values($card_btn_pix_key[$i]) : [];
            $cardBtnPixKeyType = isset($card_btn_pix_key_type[$i]) && is_array($card_btn_pix_key_type[$i]) ? array_values($card_btn_pix_key_type[$i]) : [];
            $cardBtnPayCurrency = isset($card_btn_pay_currency[$i]) && is_array($card_btn_pay_currency[$i]) ? array_values($card_btn_pay_currency[$i]) : [];
            $cardBtnPayAmount = isset($card_btn_pay_amount[$i]) && is_array($card_btn_pay_amount[$i]) ? array_values($card_btn_pay_amount[$i]) : [];
            $cardBtnPayReference = isset($card_btn_pay_reference[$i]) && is_array($card_btn_pay_reference[$i]) ? array_values($card_btn_pay_reference[$i]) : [];
            $cardBtnPayItemName = isset($card_btn_pay_item_name[$i]) && is_array($card_btn_pay_item_name[$i]) ? array_values($card_btn_pay_item_name[$i]) : [];
            $cardBtnPayNote = isset($card_btn_pay_note[$i]) && is_array($card_btn_pay_note[$i]) ? array_values($card_btn_pay_note[$i]) : [];

            if (!empty($cardBtnType)) {
                if (count($cardBtnType) > 3) {
                    ms([
                        'status'  => 'error',
                        'message' => sprintf(__('Card %s: Only up to 3 buttons allowed'), $cardPosition),
                    ]);
                }

                foreach ($cardBtnType as $btnIdx => $btn_type) {
                    $buttonPosition = $btnIdx + 1;
                    $btn_type = trim($btn_type);
                    $display_text = isset($cardBtnText[$btnIdx]) ? trim($cardBtnText[$btnIdx]) : '';

                    if ($display_text === '') {
                        ms([
                            'status'  => 'error',
                            'message' => sprintf(__('Card %s Button %s: Display text is required'), $cardPosition, $buttonPosition),
                        ]);
                    }

                    switch ($btn_type) {
                        case 'quick_reply':
                            $buttons[] = [
                                'name' => 'quick_reply',
                                'buttonParamsJson' => json_encode([
                                    'display_text' => $display_text,
                                    'id'           => uniqid('card_' . $cardPosition . '_'),
                                ]),
                            ];
                            break;

                        case 'cta_url':
                            $url = isset($cardBtnUrl[$btnIdx]) ? trim($cardBtnUrl[$btnIdx]) : '';
                            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                                ms([
                                    'status'  => 'error',
                                    'message' => sprintf(__('Card %s Button %s: Invalid URL'), $cardPosition, $buttonPosition),
                                ]);
                            }

                            $buttons[] = [
                                'name' => 'cta_url',
                                'buttonParamsJson' => json_encode([
                                    'display_text' => $display_text,
                                    'url'          => $url,
                                    'merchant_url' => $url,
                                ]),
                            ];
                            break;

                        case 'cta_call':
                            $phone = isset($cardBtnPhone[$btnIdx]) ? trim($cardBtnPhone[$btnIdx]) : '';
                            if (!isValidTelephoneNumber($phone)) {
                                ms([
                                    'status'  => 'error',
                                    'message' => sprintf(__('Card %s Button %s: Invalid phone number'), $cardPosition, $buttonPosition),
                                ]);
                            }

                            $buttons[] = [
                                'name' => 'cta_call',
                                'buttonParamsJson' => json_encode([
                                    'display_text'  => $display_text,
                                    'phone_number'  => $phone,
                                ]),
                            ];
                            break;

                        case 'cta_copy':
                            $copy = isset($cardBtnCopy[$btnIdx]) ? trim($cardBtnCopy[$btnIdx]) : '';
                            if ($copy === '') {
                                ms([
                                    'status'  => 'error',
                                    'message' => sprintf(__('Card %s Button %s: Copy code is required'), $cardPosition, $buttonPosition),
                                ]);
                            }

                            $buttons[] = [
                                'name' => 'cta_copy',
                                'buttonParamsJson' => json_encode([
                                    'display_text' => $display_text,
                                    'copy_code'    => $copy,
                                ]),
                            ];
                            break;

                        case 'cta_catalog':
                            $catalog_phone = isset($cardBtnCatalogPhone[$btnIdx]) ? trim($cardBtnCatalogPhone[$btnIdx]) : '';
                            $catalog_product = isset($cardBtnCatalogProduct[$btnIdx]) ? trim($cardBtnCatalogProduct[$btnIdx]) : '';

                            if (!isValidTelephoneNumber($catalog_phone)) {
                                ms([
                                    'status'  => 'error',
                                    'message' => sprintf(__('Card %s Button %s: Invalid catalog phone number'), $cardPosition, $buttonPosition),
                                ]);
                            }

                            if ($catalog_product === '') {
                                ms([
                                    'status'  => 'error',
                                    'message' => sprintf(__('Card %s Button %s: Catalog product ID is required'), $cardPosition, $buttonPosition),
                                ]);
                            }

                            $buttons[] = [
                                'name' => 'cta_catalog',
                                'buttonParamsJson' => json_encode([
                                    'display_text'          => $display_text,
                                    'business_phone_number' => preg_replace('/[^0-9]/', '', $catalog_phone),
                                    'catalog_product_id'    => $catalog_product,
                                ]),
                            ];
                            break;

                        case 'payment_info':
                            $merchant = isset($cardBtnPixMerchant[$btnIdx]) ? trim($cardBtnPixMerchant[$btnIdx]) : '';
                            $pix_key = isset($cardBtnPixKey[$btnIdx]) ? trim($cardBtnPixKey[$btnIdx]) : '';
                            $pix_type = isset($cardBtnPixKeyType[$btnIdx]) ? strtoupper(trim($cardBtnPixKeyType[$btnIdx])) : 'CPF';

                            if ($merchant === '' || $pix_key === '') {
                                ms([
                                    'status'  => 'error',
                                    'message' => sprintf(__('Card %s Button %s: PIX merchant name and key are required'), $cardPosition, $buttonPosition),
                                ]);
                            }

                            $allowedPixTypes = ['CPF', 'CNPJ', 'EMAIL', 'PHONE', 'EVP'];
                            if (!in_array($pix_type, $allowedPixTypes, true)) {
                                $pix_type = 'CPF';
                            }

                            $buttons[] = [
                                'name' => 'payment_info',
                                'buttonParamsJson' => json_encode([
                                    'display_text' => $display_text,
                                    'payment_settings' => [[
                                        'type' => 'pix_static_code',
                                        'pix_static_code' => [
                                            'merchant_name' => $merchant,
                                            'key' => $pix_key,
                                            'key_type' => $pix_type,
                                        ],
                                    ]],
                                ]),
                            ];
                            break;

                        case 'review_and_pay':
                            $currency = isset($cardBtnPayCurrency[$btnIdx]) ? strtoupper(trim($cardBtnPayCurrency[$btnIdx])) : 'BRL';
                            if (strlen($currency) !== 3) {
                                ms([
                                    'status'  => 'error',
                                    'message' => sprintf(__('Card %s Button %s: Currency must be a 3-letter ISO code'), $cardPosition, $buttonPosition),
                                ]);
                            }

                            $amount = isset($cardBtnPayAmount[$btnIdx]) ? trim($cardBtnPayAmount[$btnIdx]) : '';
                            if ($amount === '' || !is_numeric($amount) || $amount <= 0) {
                                ms([
                                    'status'  => 'error',
                                    'message' => sprintf(__('Card %s Button %s: Amount must be greater than zero'), $cardPosition, $buttonPosition),
                                ]);
                            }

                            $reference = isset($cardBtnPayReference[$btnIdx]) ? trim($cardBtnPayReference[$btnIdx]) : '';
                            if ($reference === '') {
                                $reference = uniqid('pay_');
                            }

                            $item_name = isset($cardBtnPayItemName[$btnIdx]) ? trim($cardBtnPayItemName[$btnIdx]) : __('Item');
                            $note = isset($cardBtnPayNote[$btnIdx]) ? trim($cardBtnPayNote[$btnIdx]) : '';

                            $amountValue = (int)round($amount * 100);

                            $buttons[] = [
                                'name' => 'review_and_pay',
                                'buttonParamsJson' => json_encode([
                                    'display_text' => $display_text,
                                    'currency' => $currency,
                                    'total_amount' => [
                                        'value' => (string)$amountValue,
                                        'offset' => '100',
                                    ],
                                    'reference_id' => $reference,
                                    'payment_status' => 'pending',
                                    'payment_type' => 'pix',
                                    'payment_method' => 'PIX',
                                    'payment_timestamp' => time(),
                                    'order' => [
                                        'status' => 'open',
                                        'order_type' => 'PAYMENT_REQUEST',
                                        'description' => $item_name,
                                        'items' => [[
                                            'retailer_id' => $reference,
                                            'name' => $item_name,
                                            'amount' => [
                                                'value' => (string)$amountValue,
                                                'offset' => '100',
                                            ],
                                            'quantity' => '1',
                                        ]],
                                        'subtotal' => [
                                            'value' => (string)$amountValue,
                                            'offset' => '100',
                                        ],
                                    ],
                                    'additional_note' => $note,
                                    'native_payment_methods' => [],
                                    'share_payment_status' => false,
                                ]),
                            ];
                            break;

                        default:
                            ms([
                                'status'  => 'error',
                                'message' => sprintf(__('Card %s Button %s: Invalid button type'), $cardPosition, $buttonPosition),
                            ]);
                            break;
                    }
                }
            }

            $cards[] = [
                'title'   => $title,
                'body'    => $body,
                'footer'  => $footer,
                'media'   => $media_url,
                'buttons' => $buttons,
            ];
        }

        $payload = [
            'title'  => $message_title,
            'text'   => $message_body,
            'footer' => $message_footer,
            'cards'  => $cards,
        ];

        $item = db_get('*', TB_WHATSAPP_TEMPLATE, ['ids' => $ids, 'team_id' => $team_id]);

        if (empty($item)) {
            $data = [
                'ids'     => ids(),
                'team_id' => $team_id,
                'type'    => 5,
                'name'    => $name,
                'data'    => json_encode($payload),
                'changed' => time(),
                'created' => time(),
            ];

            db_insert(TB_WHATSAPP_TEMPLATE, $data);
        } else {
            $data = [
                'name'    => $name,
                'data'    => json_encode($payload),
                'changed' => time(),
            ];

            db_update(TB_WHATSAPP_TEMPLATE, $data, ['ids' => $ids]);
        }

        ms([
            'status'  => 'success',
            'message' => __('Success'),
        ]);
    }

    public function delete()
    {
        $team_id = get_team('id');
        $ids = post('id');

        if (empty($ids)) {
            ms([
                'status'  => 'error',
                'message' => __('Please select an item to delete'),
            ]);
        }

        if (is_array($ids)) {
            foreach ($ids as $id) {
                db_delete(TB_WHATSAPP_TEMPLATE, ['ids' => $id, 'team_id' => $team_id]);
            }
        } elseif (is_string($ids)) {
            db_delete(TB_WHATSAPP_TEMPLATE, ['ids' => $ids, 'team_id' => $team_id]);
        }

        ms([
            'status'  => 'success',
            'message' => __('Success'),
        ]);
    }
}
