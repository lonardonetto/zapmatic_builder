<?php
$payload = false;
$cards = [];
$request = \Config\Services::request();
$wa_default_redirect = get_module_url();
$wa_return_url = trim((string) $request->getGet('wa_return'));
$wa_return_redirect = $wa_default_redirect;
if ($wa_return_url !== '') {
    $return_host = parse_url($wa_return_url, PHP_URL_HOST);
    $base_host = parse_url(base_url(), PHP_URL_HOST);
    if ((strpos($wa_return_url, '/') === 0 && strpos($wa_return_url, '//') !== 0) || ($return_host && $base_host && strcasecmp($return_host, $base_host) === 0)) {
        $wa_return_redirect = $wa_return_url;
    }
}
$wa_has_return = $wa_return_url !== '' && $wa_return_redirect !== $wa_default_redirect;
if (!empty($result)) {
    $payload = json_decode($result->data);
    if (!empty($payload) && isset($payload->cards) && is_array($payload->cards)) {
        $cards = $payload->cards;
    }
}
?>

<form class="actionForm" action="<?php _eC( get_module_url('save/' . get_data($result, 'ids')) )?>" method="POST" data-redirect="<?php _ec( $wa_return_redirect ) ?>">
    <div class="container py-5">
        <div class="card b-r-6 mb-4">
            <div class="card-header">
                <div class="card-title"><i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e('Carousel template')?></div>
                <?php if ($wa_has_return): ?>
                    <div class="card-toolbar">
                        <a href="<?php _ec($wa_return_redirect) ?>" class="btn btn-sm btn-light-primary b-r-30"><i class="fad fa-arrow-left me-1"></i><?php _e('Voltar para a tela anterior') ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label"><?php _e('Name')?></label>
                    <input type="text" name="name" class="form-control form-control-solid" placeholder="<?php _e('Enter template name')?>" value="<?php _ec( get_data($result, 'name') )?>">
                </div>

                <div class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex flex-column flex-sm-row w-100 p-5 mb-10">
					<span class="fs-2hx text-primary me-4 mb-5 mb-sm-0">
						<i class="fad fa-info-circle text-primary fs-1"></i>
					</span>
					<div class="d-flex flex-column pe-0 pe-sm-10">
						<h5 class="mb-2 text-primary"><?php _e("Guia Completo - Regras Cloud API (Meta)")?></h5>
						<span class="fs-12 text-gray-800">
							<ul class="mb-0">
								<li><strong><?php _e("Estrutura:")?></strong> <?php _e("Máximo de 10 cards e 2 botões por card.")?></li>
								<li><strong><?php _e("Títulos Únicos:")?></strong> <span class="text-danger"><?php _e("Cada botão dentro de um card DEVE ter um título exclusivo.")?></span></li>
								<li><strong><?php _e("Tipos de Botões:")?></strong> <?php _e("Neste formato de carrossel dinâmico, a Meta só aceita botões do tipo Texto (Reply). Botões de Link/Chamada serão convertidos para texto.")?></li>
								<li><strong><?php _e("Vantagem da Conversão:")?></strong> <?php _e("Permite que o Chatbot continue o atendimento quando o cliente clica na opção do carrossel.")?></li>
							</ul>
						</span>
					</div>
				</div>

                <div class="mb-4">
                    <label class="form-label"><?php _e('Carousel title (optional)')?></label>
                    <input type="text" name="message_title" class="form-control form-control-solid" placeholder="<?php _e('Enter carousel title')?>" value="<?php _ec( get_data($payload, 'title') )?>">
                </div>

                <div class="mb-4">
                    <label class="form-label"><?php _e('Carousel body')?></label>
                    <?php echo view_cell('\Core\Caption\Controllers\Caption::block', ['name' => 'message_body', 'placeholder' => __('Enter carousel description'), 'value' => get_data($payload, 'text')]) ?>
                </div>

                <div class="mb-4">
                    <label class="form-label"><?php _e('Carousel footer (optional)')?></label>
                    <input type="text" name="message_footer" class="form-control form-control-solid" placeholder="<?php _e('Enter footer text')?>" value="<?php _ec( get_data($payload, 'footer') )?>">
                </div>
            </div>
        </div>

        <div class="wa-carousel-cards">
            <?php if (!empty($cards)): ?>
                <?php foreach ($cards as $index => $card): ?>
                    <?php $position = $index + 1; ?>
                    <div class="card b-r-6 mb-4 wa-carousel-card" data-card="<?php _ec( $position ) ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="card-title mb-0"><?php _e('Card')?> <?php _ec($position)?></div>
                            <button type="button" class="btn btn-sm btn-light-danger px-3 b-r-6 wa-carousel-remove-card"><i class="fad fa-trash-alt pe-0 me-0"></i></button>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><?php _e('Title')?></label>
                                <input type="text" name="card_title[<?php _ec($position)?>]" class="form-control form-control-solid" value="<?php _ec( get_data($card, 'title') )?>" placeholder="<?php _e('Enter card title')?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?php _e('Body')?></label>
                                <textarea class="form-control form-control-solid" name="card_body[<?php _ec($position)?>]" rows="4" placeholder="<?php _e('Enter card body')?>"><?php _ec( get_data($card, 'body') )?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?php _e('Footer (optional)')?></label>
                                <input type="text" name="card_footer[<?php _ec($position)?>]" class="form-control form-control-solid" value="<?php _ec( get_data($card, 'footer') )?>" placeholder="<?php _e('Enter card footer')?>">
                            </div>
                            <?php
                                $card_media_value = get_data($card, 'media');
                                if (is_object($card_media_value) && isset($card_media_value->url)) {
                                    $card_media_value = $card_media_value->url;
                                }
                            ?>
                            <div class="mb-3 wa-carousel-media" data-card="<?php _ec($position)?>">
                                <label class="form-label"><?php _e('Media (optional)')?></label>
                                <div class="input-group">
                                    <input type="text" name="card_media[<?php _ec($position)?>]" class="form-control form-control-solid wa-carousel-media-url" value="<?php _ec( $card_media_value )?>" placeholder="<?php _e('https://example.com/image.jpg')?>">
                                    <button type="button" class="btn btn-light-primary wa-carousel-upload-btn" data-card="<?php _ec($position)?>"><i class="fad fa-upload me-1"></i><?php _e('Upload')?></button>
                                </div>
                                <input type="file" class="d-none wa-carousel-file-input" accept="image/*,video/mp4" data-card="<?php _ec($position)?>">
                                <small class="text-muted d-block mt-1"><?php _e('Upload an image/video or use a direct public URL. Uploaded files are stored in File Manager.')?></small>
                                <div class="wa-carousel-media-preview mt-2 <?php _ec( empty($card_media_value) ? 'd-none' : '' )?>">
                                    <img src="<?php _ec( $card_media_value )?>" class="rounded border" style="max-width:160px;max-height:100px;object-fit:cover;">
                                </div>
                            </div>

                            <div class="wa-carousel-buttons" data-card="<?php _ec($position)?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0"><?php _e('Buttons (optional, up to 3)')?></label>
                                    <button type="button" class="btn btn-sm btn-light-primary px-3 b-r-6 wa-carousel-add-button" data-card="<?php _ec($position)?>"><i class="fad fa-plus me-1"></i><?php _e('Add button')?></button>
                                </div>

                                <?php
                                    $card_buttons = isset($card->buttons) && is_array($card->buttons) ? $card->buttons : [];
                                    foreach ($card_buttons as $btn_index => $button):
                                        $btn_position = $btn_index + 1;
                                        $params = [];
                                        if (isset($button->buttonParamsJson)) {
                                            $params = json_decode($button->buttonParamsJson, true) ?: [];
                                        }
                                        $button_name = isset($button->name) ? $button->name : '';

                                        $pix_settings = [
                                            'merchant_name' => '',
                                            'key' => '',
                                            'key_type' => 'CPF',
                                        ];

                                        if ($button_name === 'payment_info' && isset($params['payment_settings']) && is_array($params['payment_settings'])) {
                                            $pix_info = $params['payment_settings'][0] ?? [];
                                            if (isset($pix_info['pix_static_code'])) {
                                                $pix_settings['merchant_name'] = get_data($pix_info['pix_static_code'], 'merchant_name');
                                                $pix_settings['key'] = get_data($pix_info['pix_static_code'], 'key');
                                                $pix_settings['key_type'] = strtoupper(get_data($pix_info['pix_static_code'], 'key_type')) ?: 'CPF';
                                            }
                                        }

                                        $payment_settings = [
                                            'currency' => 'BRL',
                                            'amount' => '',
                                            'reference' => '',
                                            'item_name' => '',
                                            'note' => '',
                                        ];

                                        if ($button_name === 'review_and_pay') {
                                            if (isset($params['currency'])) {
                                                $payment_settings['currency'] = strtoupper($params['currency']);
                                            }
                                            if (isset($params['total_amount']['value']) && isset($params['total_amount']['offset']) && (int)$params['total_amount']['offset'] > 0) {
                                                $payment_settings['amount'] = number_format(((int)$params['total_amount']['value']) / (int)$params['total_amount']['offset'], 2, '.', '');
                                            }
                                            if (isset($params['reference_id'])) {
                                                $payment_settings['reference'] = $params['reference_id'];
                                            }
                                            if (isset($params['order']['items'][0]['name'])) {
                                                $payment_settings['item_name'] = $params['order']['items'][0]['name'];
                                            }
                                            if (isset($params['additional_note'])) {
                                                $payment_settings['note'] = $params['additional_note'];
                                            }
                                        }
                                ?>
                                    <div class="card border b-r-6 mb-3 wa-carousel-button" data-card="<?php _ec($position)?>" data-index="<?php _ec($btn_position)?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div class="text-muted"><?php _e('Button')?> <?php _ec($btn_position)?></div>
                                                <button type="button" class="btn btn-sm btn-light-danger px-3 b-r-6 wa-carousel-remove-button"><i class="fad fa-trash-alt pe-0 me-0"></i></button>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e('Type')?></label>
                                                <select class="form-select form-select-solid wa-carousel-button-type" name="card_btn_type[<?php _ec($position)?>][]">
                                                    <option value="quick_reply" <?php _ec($button_name == 'quick_reply' ? 'selected' : '')?>><?php _e('Quick reply')?></option>
                                                    <option value="cta_url" <?php _ec($button_name == 'cta_url' ? 'selected' : '')?>><?php _e('Call to action URL')?></option>
                                                    <option value="cta_call" <?php _ec($button_name == 'cta_call' ? 'selected' : '')?>><?php _e('Call phone number')?></option>
                                                    <option value="cta_copy" <?php _ec($button_name == 'cta_copy' ? 'selected' : '')?>><?php _e('Copy code')?></option>
                                                    <option value="cta_catalog" <?php _ec($button_name == 'cta_catalog' ? 'selected' : '')?>><?php _e('Open catalog item')?></option>
                                                    <option value="payment_info" <?php _ec($button_name == 'payment_info' ? 'selected' : '')?>><?php _e('PIX payment')?></option>
                                                    <option value="review_and_pay" <?php _ec($button_name == 'review_and_pay' ? 'selected' : '')?>><?php _e('Payment request')?></option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e('Display text')?></label>
                                                <input type="text" class="form-control form-control-solid" name="card_btn_display_text[<?php _ec($position)?>][]" value="<?php _ec( get_data($params, 'display_text') )?>" placeholder="<?php _e('Enter button text')?>">
                                            </div>
                                            <div class="mb-3 wa-carousel-field wa-carousel-field-url" <?php _ec($button_name == 'cta_url' ? '' : 'style="display:none"')?> >
                                                <label class="form-label"><?php _e('URL')?></label>
                                                <input type="text" class="form-control form-control-solid" name="card_btn_url[<?php _ec($position)?>][]" value="<?php _ec( get_data($params, 'url') )?>" placeholder="https://...">
                                            </div>
                                            <div class="mb-3 wa-carousel-field wa-carousel-field-phone" <?php _ec($button_name == 'cta_call' ? '' : 'style="display:none"')?> >
                                                <label class="form-label"><?php _e('Phone number')?></label>
                                                <input type="text" class="form-control form-control-solid" name="card_btn_phone[<?php _ec($position)?>][]" value="<?php _ec( get_data($params, 'phone_number') )?>" placeholder="<?php _e('Ex: +55 21970402529')?>">
                                            </div>
                                            <div class="mb-3 wa-carousel-field wa-carousel-field-copy" <?php _ec($button_name == 'cta_copy' ? '' : 'style="display:none"')?> >
                                                <label class="form-label"><?php _e('Copy code/text')?></label>
                                                <input type="text" class="form-control form-control-solid" name="card_btn_copy[<?php _ec($position)?>][]" value="<?php _ec( get_data($params, 'copy_code') )?>" placeholder="<?php _e('Text to copy')?>">
                                            </div>
                                            <div class="mb-3 wa-carousel-field wa-carousel-field-catalog" <?php _ec($button_name == 'cta_catalog' ? '' : 'style="display:none"')?> >
                                                <label class="form-label"><?php _e('Business phone number')?></label>
                                                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_catalog_phone[<?php _ec($position)?>][]" value="<?php _ec( get_data($params, 'business_phone_number') )?>" placeholder="<?php _e('Ex: +55 21970402529')?>">
                                                <label class="form-label"><?php _e('Catalog product ID')?></label>
                                                <input type="text" class="form-control form-control-solid" name="card_btn_catalog_product[<?php _ec($position)?>][]" value="<?php _ec( get_data($params, 'catalog_product_id') )?>" placeholder="1234567890">
                                            </div>
                                            <div class="mb-3 wa-carousel-field wa-carousel-field-pix" <?php _ec($button_name == 'payment_info' ? '' : 'style="display:none"')?> >
                                                <label class="form-label"><?php _e('Merchant name')?></label>
                                                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_pix_merchant[<?php _ec($position)?>][]" value="<?php _ec( $pix_settings['merchant_name'] )?>" placeholder="<?php _e('Business name')?>">
                                                <label class="form-label"><?php _e('PIX key')?></label>
                                                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_pix_key[<?php _ec($position)?>][]" value="<?php _ec( $pix_settings['key'] )?>" placeholder="<?php _e('Enter PIX key')?>">
                                                <label class="form-label"><?php _e('PIX key type')?></label>
                                                <select class="form-select form-select-solid" name="card_btn_pix_key_type[<?php _ec($position)?>][]">
                                                    <?php $pix_types = ['CPF', 'CNPJ', 'EMAIL', 'PHONE', 'EVP']; ?>
                                                    <?php foreach ($pix_types as $pix_type): ?>
                                                        <option value="<?php _ec($pix_type)?>" <?php _ec($pix_settings['key_type'] === $pix_type ? 'selected' : '')?>><?php _ec($pix_type)?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3 wa-carousel-field wa-carousel-field-payment" <?php _ec($button_name == 'review_and_pay' ? '' : 'style="display:none"')?> >
                                                <label class="form-label"><?php _e('Currency (ISO)')?></label>
                                                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_pay_currency[<?php _ec($position)?>][]" value="<?php _ec( $payment_settings['currency'] )?>" placeholder="BRL">
                                                <label class="form-label"><?php _e('Amount')?></label>
                                                <input type="number" step="0.01" class="form-control form-control-solid mb-3" name="card_btn_pay_amount[<?php _ec($position)?>][]" value="<?php _ec( $payment_settings['amount'] )?>" placeholder="100.00">
                                                <label class="form-label"><?php _e('Reference ID')?></label>
                                                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_pay_reference[<?php _ec($position)?>][]" value="<?php _ec( $payment_settings['reference'] )?>" placeholder="REF123">
                                                <label class="form-label"><?php _e('Item name')?></label>
                                                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_pay_item_name[<?php _ec($position)?>][]" value="<?php _ec( $payment_settings['item_name'] )?>" placeholder="<?php _e('Product or service')?>">
                                                <label class="form-label"><?php _e('Additional note (optional)')?></label>
                                                <textarea class="form-control form-control-solid" name="card_btn_pay_note[<?php _ec($position)?>][]" rows="3" placeholder="<?php _e('Notes for the payment')?>"><?php _ec( $payment_settings['note'] )?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="mt-4">
            <button type="button" class="btn btn-dark w-100 wa-carousel-add-card"><i class="fad fa-plus me-2"></i><?php _e('Add new card')?></button>
            <small class="text-muted d-block mt-2"><?php _e('You can add up to 10 cards and each card can contain up to 3 buttons.')?></small>
        </div>

        <div class="mt-5 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary w-100"><?php _e('Submit')?></button>
        </div>
    </div>
</form>

<div class="wa-carousel-card-template d-none">
    <div class="card b-r-6 mb-4 wa-carousel-card" data-card="__CARD__">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0"><?php _e('Card')?> __CARD__</div>
            <button type="button" class="btn btn-sm btn-light-danger px-3 b-r-6 wa-carousel-remove-card"><i class="fad fa-trash-alt pe-0 me-0"></i></button>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label"><?php _e('Title')?></label>
                <input type="text" name="card_title[__CARD__]" class="form-control form-control-solid" placeholder="<?php _e('Enter card title')?>">
            </div>
            <div class="mb-3">
                <label class="form-label"><?php _e('Body')?></label>
                <textarea class="form-control form-control-solid" name="card_body[__CARD__]" rows="4" placeholder="<?php _e('Enter card body')?>"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label"><?php _e('Footer (optional)')?></label>
                <input type="text" name="card_footer[__CARD__]" class="form-control form-control-solid" placeholder="<?php _e('Enter card footer')?>">
            </div>
            <div class="mb-3 wa-carousel-media" data-card="__CARD__">
                <label class="form-label"><?php _e('Media (optional)')?></label>
                <div class="input-group">
                    <input type="text" name="card_media[__CARD__]" class="form-control form-control-solid wa-carousel-media-url" placeholder="<?php _e('https://example.com/image.jpg')?>">
                    <button type="button" class="btn btn-light-primary wa-carousel-upload-btn" data-card="__CARD__"><i class="fad fa-upload me-1"></i><?php _e('Upload')?></button>
                </div>
                <input type="file" class="d-none wa-carousel-file-input" accept="image/*,video/mp4" data-card="__CARD__">
                <small class="text-muted d-block mt-1"><?php _e('Upload an image/video or use a direct public URL. Uploaded files are stored in File Manager.')?></small>
                <div class="wa-carousel-media-preview mt-2 d-none">
                    <img src="" class="rounded border" style="max-width:160px;max-height:100px;object-fit:cover;">
                </div>
            </div>
            <div class="wa-carousel-buttons" data-card="__CARD__">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0"><?php _e('Buttons (optional, up to 3)')?></label>
                    <button type="button" class="btn btn-sm btn-light-primary px-3 b-r-6 wa-carousel-add-button" data-card="__CARD__"><i class="fad fa-plus me-1"></i><?php _e('Add button')?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="wa-carousel-button-template d-none">
    <div class="card border b-r-6 mb-3 wa-carousel-button" data-card="__CARD__" data-index="__BTN__">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-muted"><?php _e('Button')?> __BTN__</div>
                <button type="button" class="btn btn-sm btn-light-danger px-3 b-r-6 wa-carousel-remove-button"><i class="fad fa-trash-alt pe-0 me-0"></i></button>
            </div>
            <div class="mb-3">
                <label class="form-label"><?php _e('Type')?></label>
                <select class="form-select form-select-solid wa-carousel-button-type" name="card_btn_type[__CARD__][]">
                    <option value="quick_reply"><?php _e('Quick reply')?></option>
                    <option value="cta_url"><?php _e('Call to action URL')?></option>
                    <option value="cta_call"><?php _e('Call phone number')?></option>
                    <option value="cta_copy"><?php _e('Copy code')?></option>
                    <option value="cta_catalog"><?php _e('Open catalog item')?></option>
                    <option value="payment_info"><?php _e('PIX payment')?></option>
                    <option value="review_and_pay"><?php _e('Payment request')?></option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label"><?php _e('Display text')?></label>
                <input type="text" class="form-control form-control-solid" name="card_btn_display_text[__CARD__][]" placeholder="<?php _e('Enter button text')?>">
            </div>
            <div class="mb-3 wa-carousel-field wa-carousel-field-url" style="display:none">
                <label class="form-label"><?php _e('URL')?></label>
                <input type="text" class="form-control form-control-solid" name="card_btn_url[__CARD__][]" placeholder="https://...">
            </div>
            <div class="mb-3 wa-carousel-field wa-carousel-field-phone" style="display:none">
                <label class="form-label"><?php _e('Phone number')?></label>
                <input type="text" class="form-control form-control-solid" name="card_btn_phone[__CARD__][]" placeholder="<?php _e('Ex: +55 21970402529')?>">
            </div>
            <div class="mb-3 wa-carousel-field wa-carousel-field-copy" style="display:none">
                <label class="form-label"><?php _e('Copy code/text')?></label>
                <input type="text" class="form-control form-control-solid" name="card_btn_copy[__CARD__][]" placeholder="<?php _e('Text to copy')?>">
            </div>
            <div class="mb-3 wa-carousel-field wa-carousel-field-catalog" style="display:none">
                <label class="form-label"><?php _e('Business phone number')?></label>
                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_catalog_phone[__CARD__][]" placeholder="<?php _e('Ex: +55 21970402529')?>">
                <label class="form-label"><?php _e('Catalog product ID')?></label>
                <input type="text" class="form-control form-control-solid" name="card_btn_catalog_product[__CARD__][]" placeholder="1234567890">
            </div>
            <div class="mb-3 wa-carousel-field wa-carousel-field-pix" style="display:none">
                <label class="form-label"><?php _e('Merchant name')?></label>
                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_pix_merchant[__CARD__][]" placeholder="<?php _e('Business name')?>">
                <label class="form-label"><?php _e('PIX key')?></label>
                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_pix_key[__CARD__][]" placeholder="<?php _e('Enter PIX key')?>">
                <label class="form-label"><?php _e('PIX key type')?></label>
                <select class="form-select form-select-solid" name="card_btn_pix_key_type[__CARD__][]">
                    <option value="CPF">CPF</option>
                    <option value="CNPJ">CNPJ</option>
                    <option value="EMAIL">EMAIL</option>
                    <option value="PHONE">PHONE</option>
                    <option value="EVP">EVP</option>
                </select>
            </div>
            <div class="mb-3 wa-carousel-field wa-carousel-field-payment" style="display:none">
                <label class="form-label"><?php _e('Currency (ISO)')?></label>
                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_pay_currency[__CARD__][]" placeholder="BRL">
                <label class="form-label"><?php _e('Amount')?></label>
                <input type="number" step="0.01" class="form-control form-control-solid mb-3" name="card_btn_pay_amount[__CARD__][]" placeholder="100.00">
                <label class="form-label"><?php _e('Reference ID')?></label>
                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_pay_reference[__CARD__][]" placeholder="REF123">
                <label class="form-label"><?php _e('Item name')?></label>
                <input type="text" class="form-control form-control-solid mb-3" name="card_btn_pay_item_name[__CARD__][]" placeholder="<?php _e('Product or service')?>">
                <label class="form-label"><?php _e('Additional note (optional)')?></label>
                <textarea class="form-control form-control-solid" name="card_btn_pay_note[__CARD__][]" rows="3" placeholder="<?php _e('Notes for the payment')?>"></textarea>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function($){
    const maxCards = 10;
    const maxButtons = 3;

    function renumberCards() {
        $('.wa-carousel-card').each(function(idx){
            const cardIndex = idx + 1;
            $(this).attr('data-card', cardIndex);
            $(this).find('.card-title').first().text('<?php _e('Card')?> ' + cardIndex);

            // Update input names
            $(this).find('input[name^="card_title"]').attr('name', 'card_title[' + cardIndex + ']');
            $(this).find('textarea[name^="card_body"]').attr('name', 'card_body[' + cardIndex + ']');
            $(this).find('input[name^="card_footer"]').attr('name', 'card_footer[' + cardIndex + ']');
            $(this).find('input[name^="card_media"]').attr('name', 'card_media[' + cardIndex + ']');
            $(this).find('.wa-carousel-media').attr('data-card', cardIndex);
            $(this).find('.wa-carousel-upload-btn').attr('data-card', cardIndex);
            $(this).find('.wa-carousel-file-input').attr('data-card', cardIndex);

            $(this).find('.wa-carousel-buttons').attr('data-card', cardIndex);
            $(this).find('.wa-carousel-add-button').attr('data-card', cardIndex);

            $(this).find('.wa-carousel-button').each(function(btnIdx){
                const btnIndex = btnIdx + 1;
                $(this).attr('data-card', cardIndex).attr('data-index', btnIndex);
                $(this).find('.text-muted').first().text('<?php _e('Button')?> ' + btnIndex);
                $(this).find('select[name^="card_btn_type"]').attr('name', 'card_btn_type[' + cardIndex + '][]');
                $(this).find('input[name^="card_btn_display_text"]').attr('name', 'card_btn_display_text[' + cardIndex + '][]');
                $(this).find('input[name^="card_btn_url"]').attr('name', 'card_btn_url[' + cardIndex + '][]');
                $(this).find('input[name^="card_btn_phone"]').attr('name', 'card_btn_phone[' + cardIndex + '][]');
                $(this).find('input[name^="card_btn_copy"]').attr('name', 'card_btn_copy[' + cardIndex + '][]');
                $(this).find('input[name^="card_btn_catalog_phone"]').attr('name', 'card_btn_catalog_phone[' + cardIndex + '][]');
                $(this).find('input[name^="card_btn_catalog_product"]').attr('name', 'card_btn_catalog_product[' + cardIndex + '][]');
            });
        });
    }

    function toggleButtonFields($button) {
        const type = $button.find('.wa-carousel-button-type').val();
        $button.find('.wa-carousel-field').hide();
        if (type === 'cta_url') {
            $button.find('.wa-carousel-field-url').show();
        } else if (type === 'cta_call') {
            $button.find('.wa-carousel-field-phone').show();
        } else if (type === 'cta_copy') {
            $button.find('.wa-carousel-field-copy').show();
        } else if (type === 'cta_catalog') {
            $button.find('.wa-carousel-field-catalog').show();
        } else if (type === 'payment_info') {
            $button.find('.wa-carousel-field-pix').show();
        } else if (type === 'review_and_pay') {
            $button.find('.wa-carousel-field-payment').show();
        }
    }

    $(document)
        .on('click', '.wa-carousel-add-card', function(){
            const cardCount = $('.wa-carousel-card').length;
            if (cardCount >= maxCards) {
                Core.notify('<?php _e('Maximum number of cards reached')?>', 'error');
                return;
            }

            const newIndex = cardCount + 1;
            const template = $('.wa-carousel-card-template').html().replace(/__CARD__/g, newIndex);
            $('.wa-carousel-cards').append(template);
            Core.tagsinput();
        })
        .on('click', '.wa-carousel-remove-card', function(){
            $(this).closest('.wa-carousel-card').remove();
            renumberCards();
        })
        .on('click', '.wa-carousel-upload-btn', function(){
            $(this).closest('.wa-carousel-media').find('.wa-carousel-file-input').trigger('click');
        })
        .on('change', '.wa-carousel-file-input', function(){
            const input = this;
            const file = input.files && input.files[0] ? input.files[0] : null;
            if (!file) return;

            const $media = $(input).closest('.wa-carousel-media');
            const $button = $media.find('.wa-carousel-upload-btn');
            const formData = new FormData();
            formData.append('csrf', csrf);
            formData.append('folder', 0);
            formData.append('files[]', file);

            $button.prop('disabled', true).addClass('disabled');
            $.ajax({
                url: PATH + 'file_manager/upload_files',
                type: 'post',
                data: formData,
                dataType: 'json',
                contentType: false,
                processData: false,
                success: function(result){
                    $button.prop('disabled', false).removeClass('disabled');
                    input.value = '';

                    if (!result || result.status !== 'success' || !result.file) {
                        Core.notify(result && result.message ? result.message : '<?php _e('Unable to upload media')?>', 'error');
                        return;
                    }

                    const mediaUrl = (PATH || '/') + 'writable/' + result.file.replace(/^\/+/, '');
                    $media.find('.wa-carousel-media-url').val(mediaUrl);
                    $media.find('.wa-carousel-media-preview img').attr('src', mediaUrl);
                    $media.find('.wa-carousel-media-preview').removeClass('d-none');
                    Core.notify('<?php _e('Media uploaded successfully')?>', 'success');
                },
                error: function(){
                    $button.prop('disabled', false).removeClass('disabled');
                    input.value = '';
                    Core.notify('<?php _e('Unable to upload media')?>', 'error');
                }
            });
        })
        .on('click', '.wa-carousel-add-button', function(){
            const cardIndex = $(this).data('card');
            const container = $('.wa-carousel-buttons[data-card="' + cardIndex + '"]');
            const currentButtons = container.find('.wa-carousel-button').length;
            if (currentButtons >= maxButtons) {
                Core.notify('<?php _e('Maximum number of buttons per card reached')?>', 'error');
                return;
            }

            const newBtnIndex = currentButtons + 1;
            const template = $('.wa-carousel-button-template').html()
                .replace(/__CARD__/g, cardIndex)
                .replace(/__BTN__/g, newBtnIndex);
            container.append(template);
        })
        .on('click', '.wa-carousel-remove-button', function(){
            const $card = $(this).closest('.wa-carousel-card');
            $(this).closest('.wa-carousel-button').remove();
            renumberCards();
        })
        .on('change', '.wa-carousel-button-type', function(){
            const $button = $(this).closest('.wa-carousel-button');
            toggleButtonFields($button);
        });

    $(document).ready(function(){
        $('.wa-carousel-button').each(function(){ toggleButtonFields($(this)); });
        renumberCards();
    });
})(jQuery);
</script>
