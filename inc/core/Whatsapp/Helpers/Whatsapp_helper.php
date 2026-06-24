<?php 
if(!function_exists('wa_get_phone')){
    function wa_get_phone( $jid = "" )
    {
        return $jid = "+".explode("@", $jid)[0];
    }
}

if(!function_exists('array2csv')){
    function array2csv(array &$array)
    {
        if (count($array) == 0) return null;
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys(reset($array)));
        foreach ($array as $row) {
            fputcsv($df, $row);
        }
        fclose($df);
        return ob_get_clean();
    }
}

if(!function_exists('download_send_headers')){
    function download_send_headers($filename) {
        // disable caching
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");

        // force download  
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }
}

if(!function_exists("wa_get_curl")){
    function wa_get_curl($endpoint, $params){

    	$api_path =  get_option('whatsapp_server_url', '');
    	$url = $api_path . $endpoint . '?' . http_build_query($params);
        $user_agent='Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420.1 (KHTML, like Gecko) Version/3.0 Mobile/3B48b Safari/419.3';

        $headers = array
        (
            'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,fr;q=0.8;q=0.6,en;q=0.4,ar;q=0.2',
            'Accept-Encoding: gzip,deflate',
            'Accept-Charset: utf-8;q=0.7,*;q=0.7',
            'cookie:datr=; locale=en_US; sb=; pl=n; lu=gA; c_user=; xs=; act=; presence='
        ); 

        $ch = curl_init( $url );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST , "GET");
        curl_setopt($ch, CURLOPT_POST, false);     
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_REFERER, base_url());
        $result = curl_exec( $ch );
        curl_close( $ch );

        return json_decode($result);
    }
}

if(!function_exists('wa_post_curl')){
	function wa_post_curl($endpoint, $params, $data)
	{
        $api_path =  get_option('whatsapp_server_url', '');
        $url = $api_path . $endpoint . '?' . http_build_query($params);

	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($ch,CURLOPT_URL, $url);
	    curl_setopt($ch,CURLOPT_POST, count($params));
	    curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($data));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	    $result = curl_exec($ch);
	    curl_close($ch);

	    return json_decode($result);
	}
}

if(!function_exists('wa_send_via_whatsmeow')){
	function wa_send_via_whatsmeow($instance_id, $chat_id, $text, $presence_type = 'composing', $presence_time = 2)
	{
		$gateway = \App\Services\WhatsAppGatewayService::gatewayForInstance($instance_id);
		if (($gateway['provider'] ?? 'baileys') !== 'whatsmeow') {
			return ['status' => 'error', 'message' => 'Not a whatsmeow instance'];
		}
		$baseUrl = rtrim($gateway['base_url'] ?? 'http://127.0.0.1:8090', '/');

		// Envia presença digitando
		if ($presence_time > 0) {
			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL => $baseUrl . '/send/presence',
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => json_encode([
					'instance_id' => $instance_id,
					'chat_id' => $chat_id,
					'presence' => $presence_type,
					'duration' => (int)$presence_time,
				]),
				CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 5,
			]);
			curl_exec($ch);
			curl_close($ch);
		}

		// Envia texto
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $baseUrl . '/send/text',
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode([
				'instance_id' => $instance_id,
				'chat_id' => $chat_id,
				'type' => 'text',
				'payload' => ['text' => $text],
			]),
			CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
		]);
		$resp = curl_exec($ch);
		$err = curl_error($ch);
		curl_close($ch);

		if ($err) return ['status' => 'error', 'message' => 'Gateway Go offline: ' . $err];
		$data = json_decode($resp, true);
		return $data ?: ['status' => 'error', 'message' => 'Resposta inválida do gateway'];
	}
}

if(!function_exists('wa_meta_normalize_recipient')){
    function wa_meta_normalize_recipient($to)
    {
        $recipient = trim((string)$to);
        if ($recipient === '') {
            return '';
        }

        if (strpos($recipient, '@') !== false) {
            $recipient = explode('@', $recipient)[0];
        }

        if (preg_match('/[A-Za-z]/', $recipient)) {
            return $recipient;
        }

        return preg_replace('/[^0-9]/', '', $recipient);
    }
}

if(!function_exists('wa_keyword_trim')){
    function wa_keyword_trim( $data = "" )
    {
        if($data == "") return $data;

        $data = explode(",", $data);

        $tmp = [];
        foreach ($data as $value) {
            $tmp[] = trim($value);
        }

        return implode(",", $tmp);
    }
}

if(!function_exists('send_cloud_message')){
    function send_cloud_message($account, $to, $message, $media = null) {
        $acc_data = json_decode($account->data);
        $phone_id = $acc_data->phone_number_id ?? null;
        $token = $acc_data->token ?? null;

        if (!$phone_id || !$token) return ['status' => 'error', 'message' => 'Credenciais Cloud API ausentes'];

        $url = "https://graph.facebook.com/v22.0/{$phone_id}/messages";
        $to = wa_meta_normalize_recipient($to);
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
        ];

        if ($media) {
            $media_path = parse_url((string)$media, PHP_URL_PATH);
            if (empty($media_path)) {
                $media_path = (string)$media;
            }
            $ext = strtolower(pathinfo($media_path, PATHINFO_EXTENSION));

            $audio_exts = ['ogg', 'opus', 'mp3', 'm4a', 'aac', 'amr', 'wav', 'weba'];
            $video_exts = ['mp4', '3gp', 'mov', 'webm', 'mkv'];
            $image_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $document_exts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar'];

            $media_type = 'document';
            if (in_array($ext, $audio_exts, true)) {
                $media_type = 'audio';
            } elseif (in_array($ext, $video_exts, true)) {
                $media_type = 'video';
            } elseif (in_array($ext, $image_exts, true)) {
                $media_type = 'image';
            } elseif (in_array($ext, $document_exts, true)) {
                $media_type = 'document';
            }

            $payload['type'] = $media_type;
            if ($media_type === 'audio') {
                $payload['audio'] = ['link' => $media];
            } elseif ($media_type === 'document') {
                $filename = basename($media_path);
                if ($filename === '' || $filename === false) {
                    $filename = 'arquivo';
                }
                $payload['document'] = ['link' => $media, 'caption' => $message, 'filename' => $filename];
            } elseif ($media_type === 'video') {
                $payload['video'] = ['link' => $media, 'caption' => $message];
            } else {
                $payload['image'] = ['link' => $media, 'caption' => $message];
            }
        } else {
            $payload['type'] = 'text';
            $payload['text'] = ['preview_url' => true, 'body' => $message];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        @file_put_contents(
            '/www/wwwroot/app_zapmatic_app/writable/logs/cloud_send.log',
            date('Y-m-d H:i:s') . " - helper=inc to={$to} type=" . ($payload['type'] ?? 'unknown') . " media=" . ($media ?? '') . " http={$code} resp={$resp}\n",
            FILE_APPEND
        );

        $result = json_decode($resp, true);
        if ($code == 200) return ['status' => 'success', 'data' => $result];
        return ['status' => 'error', 'message' => $result['error']['message'] ?? 'Erro Meta API'];
    }
}

if(!function_exists('send_cloud_template')){
    if (!function_exists('wa_generate_flow_template_token')) {
        function wa_generate_flow_template_token($template_name, $to, $button_index = 0)
        {
            $seed = strtolower(trim((string) $template_name));
            if ($seed === '') {
                $seed = 'flow-template';
            }

            return substr(
                preg_replace('/[^a-z0-9_]+/', '_', $seed) . '_' . $button_index . '_' . substr(md5((string) $to . microtime(true)), 0, 12),
                0,
                64
            );
        }
    }

    if (!function_exists('wa_normalize_template_flow_action_data')) {
        function wa_normalize_template_flow_action_data($raw)
        {
            if ($raw === null || $raw === '') {
                return [];
            }

            if (is_object($raw)) {
                $raw = (array) $raw;
            }

            if (is_array($raw)) {
                return array_values($raw) === $raw ? [] : $raw;
            }

            if (!is_string($raw)) {
                return [];
            }

            $raw = trim($raw);
            if ($raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return [];
            }

            return array_values($decoded) === $decoded ? [] : $decoded;
        }
    }

    if (!function_exists('wa_normalize_flow_button_defaults')) {
        function wa_normalize_flow_button_defaults($defaults)
        {
            $normalized = [];
            if (is_object($defaults)) {
                $defaults = (array) $defaults;
            }

            if (!is_array($defaults)) {
                return $normalized;
            }

            foreach ($defaults as $entry) {
                if (is_object($entry)) {
                    $entry = (array) $entry;
                }

                if (!is_array($entry)) {
                    continue;
                }

                $index = isset($entry['index']) ? (string) $entry['index'] : null;
                if ($index === null || $index === '') {
                    continue;
                }

                $normalized[$index] = $entry;
            }

            return $normalized;
        }
    }

    if (!function_exists('wa_build_cloud_template_payload')) {
        function wa_build_cloud_template_payload($account, $to, $template_name, $language = 'pt_BR', $components = [], $body_example_values = [], $templateData = [])
        {
            $to = wa_meta_normalize_recipient($to);
            if ($template_name === '') {
                return ['status' => 'error', 'message' => 'Nome do template oficial não informado'];
            }

            $sending_components = [];
            $structural_components = is_array($components) ? $components : [];
            $templateData = is_array($templateData) ? $templateData : [];
            $flow_button_defaults = wa_normalize_flow_button_defaults($templateData['flow_button_defaults'] ?? []);

            if (is_string($body_example_values)) {
                $parts = array_values(array_filter(array_map('trim', explode('|', $body_example_values)), function ($v) {
                    return $v !== '';
                }));
                $body_example_values = $parts;
            }
            if (!is_array($body_example_values)) {
                $body_example_values = [];
            }

            if (!empty($structural_components)) {
                $first = $structural_components[0] ?? null;
                if (is_array($first) && array_key_exists('parameters', $first)) {
                    $sending_components = $structural_components;
                    $structural_components = [];
                }
            }

            if (empty($sending_components) && !empty($structural_components)) {
                $headerFormat = null;
                $headerExampleHandle = null;
                $hasBodyPlaceholders = false;
                $maxBodyPlaceholderIndex = 0;

                foreach ($structural_components as $c) {
                    if (!is_array($c)) continue;
                    $type = strtoupper((string)($c['type'] ?? ''));

                    if ($type === 'BODY') {
                        $text = (string)($c['text'] ?? '');
                        if ($text !== '' && preg_match('/\{\{\d+\}\}/', $text)) {
                            $hasBodyPlaceholders = true;
                            if (preg_match_all('/\{\{(\d+)\}\}/', $text, $m)) {
                                foreach (($m[1] ?? []) as $n) {
                                    $idx = (int) $n;
                                    if ($idx > $maxBodyPlaceholderIndex) $maxBodyPlaceholderIndex = $idx;
                                }
                            }
                        }
                    }

                    if ($type === 'HEADER') {
                        $headerFormat = strtoupper((string)($c['format'] ?? ''));
                        $example = $c['example'] ?? null;
                        if (is_array($example)) {
                            $handles = $example['header_handle'] ?? null;
                            if (is_array($handles) && !empty($handles)) {
                                $headerExampleHandle = (string)$handles[0];
                            }
                        }
                    }
                }

                if ($hasBodyPlaceholders) {
                    if (!empty($body_example_values) && $maxBodyPlaceholderIndex > 0) {
                        $params = [];
                        for ($i = 1; $i <= $maxBodyPlaceholderIndex; $i++) {
                            $val = (string)($body_example_values[$i - 1] ?? '');
                            $params[] = ['type' => 'text', 'text' => $val];
                        }
                        $sending_components[] = [
                            'type' => 'body',
                            'parameters' => $params,
                        ];
                    } else {
                        return [
                            'status' => 'error',
                            'message' => 'Este template possui variáveis ({{1}} etc). Para enviar via Cloud API é necessário informar parameters do BODY.'
                        ];
                    }
                }

                if (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                    $mediaType = strtolower($headerFormat);
                    $defaultHeaderMedia = $templateData['default_header_media'] ?? null;

                    $param = null;
                    if (is_array($defaultHeaderMedia)) {
                        $id = $defaultHeaderMedia['id'] ?? null;
                        $link = $defaultHeaderMedia['link'] ?? null;
                        if (is_string($id) && $id !== '') {
                            $param = ['type' => $mediaType, $mediaType => ['id' => $id]];
                        } elseif (is_string($link) && $link !== '') {
                            $param = ['type' => $mediaType, $mediaType => ['link' => $link]];
                        }
                    }

                    if ($param === null && is_string($headerExampleHandle) && $headerExampleHandle !== '') {
                        $param = ['type' => $mediaType, $mediaType => ['id' => $headerExampleHandle]];
                    }

                    if ($param === null) {
                        return [
                            'status' => 'error',
                            'message' => "Este template exige HEADER {$headerFormat}. Configure uma mídia padrão (media_id ou link) para enviar."
                        ];
                    }

                    $sending_components[] = [
                        'type' => 'header',
                        'parameters' => [$param]
                    ];
                }

                foreach ($structural_components as $componentIndex => $c) {
                    if (!is_array($c)) {
                        continue;
                    }

                    $type = strtoupper((string)($c['type'] ?? ''));
                    if ($type !== 'BUTTONS') {
                        continue;
                    }

                    $buttons = isset($c['buttons']) && is_array($c['buttons']) ? $c['buttons'] : [];
                    foreach ($buttons as $buttonIndex => $button) {
                        if (!is_array($button)) {
                            continue;
                        }

                        $buttonType = strtoupper((string)($button['type'] ?? ''));
                        if ($buttonType !== 'FLOW') {
                            continue;
                        }

                        $resolvedIndex = isset($button['index']) ? (string) $button['index'] : (string) $buttonIndex;
                        $defaults = $flow_button_defaults[$resolvedIndex] ?? [];
                        $action = [
                            'flow_token' => (string)($defaults['flow_token'] ?? wa_generate_flow_template_token($template_name, $to, $resolvedIndex)),
                        ];

                        $actionData = wa_normalize_template_flow_action_data(
                            $defaults['flow_action_data']
                            ?? $defaults['flowActionData']
                            ?? null
                        );

                        if (!empty($actionData)) {
                            $action['flow_action_data'] = $actionData;
                        }

                        $sending_components[] = [
                            'type' => 'button',
                            'sub_type' => 'flow',
                            'index' => $resolvedIndex,
                            'parameters' => [
                                [
                                    'type' => 'action',
                                    'action' => $action,
                                ]
                            ],
                        ];
                    }
                }
            }

            return [
                'status' => 'success',
                'payload' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => $template_name,
                        'language' => ['code' => $language],
                        'components' => $sending_components
                    ]
                ]
            ];
        }
    }

    /**
     * Envia template oficial (Cloud API).
     *
     * Modos suportados:
     * - Legado: send_cloud_template($account, $to, $template_name, $language, $componentsEnvio)
     * - Recomendado: send_cloud_template($account, $to, $templateDataArrayOuObject)
     *   Onde templateData contém: name, language, components (estrutura do template da Meta) e opcionalmente:
     *   - default_header_media: ['id' => '...', 'link' => '...']
     *
     * Observação (Meta):
     * - No envio, `template.components` deve conter apenas `parameters` (e metadados de botões).
     * - Para HEADER de mídia (IMAGE/VIDEO/DOCUMENT), é obrigatório enviar `id` ou `link`.
     */
    function send_cloud_template($account, $to, $template_name, $language = 'pt_BR', $components = [], $body_example_values = []) {
        $acc_data = json_decode($account->data);
        $phone_id = $acc_data->phone_number_id ?? null;
        $token = $acc_data->token ?? null;

        if (!$phone_id || !$token) return ['status' => 'error', 'message' => 'Credenciais Cloud API ausentes'];

        $to = wa_meta_normalize_recipient($to);

        $templateData = null;
        // Modo recomendado: $template_name é um array/obj com os dados do template
        if (is_array($template_name) || is_object($template_name)) {
            $templateData = (array) $template_name;
            $template_name = (string)($templateData['name'] ?? '');
            $language = (string)($templateData['language'] ?? $language);
            if (empty($components) && isset($templateData['components'])) {
                $components = $templateData['components'];
            }
            if (empty($body_example_values) && isset($templateData['body_example_values'])) {
                $body_example_values = $templateData['body_example_values'];
            }
        }

        $url = "https://graph.facebook.com/v22.0/{$phone_id}/messages";
        $prepared = wa_build_cloud_template_payload(
            $account,
            $to,
            $template_name,
            $language,
            $components,
            $body_example_values,
            is_array($templateData) ? $templateData : []
        );
        if (($prepared['status'] ?? 'error') !== 'success') {
            return $prepared;
        }

        $payload = $prepared['payload'];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        @file_put_contents(
            '/www/wwwroot/app_zapmatic_app/writable/logs/cloud_send.log',
            date('Y-m-d H:i:s')
                . ' - helper=template'
                . ' to=' . $to
                . ' name=' . $template_name
                . ' lang=' . $language
                . ' http=' . $code
                . ' payload=' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . ' resp=' . (string) $resp
                . "\n",
            FILE_APPEND
        );

        $result = json_decode($resp, true);
        if ($code == 200) return ['status' => 'success', 'data' => $result];
        return ['status' => 'error', 'message' => $result['error']['message'] ?? 'Erro Meta API'];
    }
}

if(!function_exists('send_cloud_interactive')){
    function send_cloud_interactive($account, $to, $type, $content) {
        $acc_data = json_decode($account->data);
        $phone_id = $acc_data->phone_number_id ?? null;
        $token = $acc_data->token ?? null;

        if (!$phone_id || !$token) return ['status' => 'error', 'message' => 'Credenciais Cloud API ausentes'];

        $url = "https://graph.facebook.com/v22.0/{$phone_id}/messages";
        $to = wa_meta_normalize_recipient($to);
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => $content
        ];

        // Ajuste para cta_url que usa o type button interno mas é interativo
        if($type == 'cta_url') {
            $payload['interactive']['type'] = 'button';
        } else {
            $payload['interactive']['type'] = $type;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($resp, true);
        if ($code == 200) return ['status' => 'success', 'data' => $result];
        return ['status' => 'error', 'message' => $result['error']['message'] ?? 'Erro Meta API'];
    }
}
