<?php
/**
 * Helper: Meta Oficial (Cloud API) - criação/submissão de templates para análise.
 *
 * Objetivo:
 * - Reaproveitar os módulos existentes (botões/lista/carrossel/enquete) e permitir "modo Oficial (Meta)"
 * - Centralizar validações e utilitários (upload handle `h`, sanitização de nomes, etc.)
 *
 * Observação:
 * - Para APROVAÇÃO de template com HEADER de mídia, a Meta exige `example.header_handle` com um `handle (h)`
 *   obtido via Graph Resumable Upload (/APP_ID/uploads + POST bytes).
 * - Para ENVIO (/messages), usamos `media_id` (upload /{phone_number_id}/media) ou `link`.
 */

if (!function_exists('meta_sanitizar_nome_template')) {
  /**
   * Sanitiza nome para template oficial da Meta (letras minúsculas, números e underscore).
   */
  function meta_sanitizar_nome_template(string $nome): string
  {
    $nome = strtolower(trim($nome));
    $nome = preg_replace('/[^a-z0-9_]/', '_', $nome);
    $nome = preg_replace('/_+/', '_', $nome);
    $nome = trim($nome, '_');
    return $nome;
  }
}

if (!function_exists('meta_parsear_idiomas_csv')) {
  /**
   * Parseia idiomas "pt_BR,en_US" em array.
   */
  function meta_parsear_idiomas_csv(string $csv): array
  {
    $csv = trim($csv);
    if ($csv === '') return ['pt_BR'];
    $langs = array_values(array_filter(array_map(function ($l) {
      return trim((string) $l);
    }, explode(',', $csv))));
    return !empty($langs) ? $langs : ['pt_BR'];
  }
}

if (!function_exists('meta_resolver_caminho_disco_por_url_publica')) {
  /**
   * Resolve caminho em disco (WRITEPATH/uploads/...) a partir de uma URL pública para writable/uploads.
   */
  function meta_resolver_caminho_disco_por_url_publica(string $url): ?string
  {
    $path = parse_url($url, PHP_URL_PATH);
    if (!$path) return null;
    $base = basename($path);
    if (!$base) return null;
    $disk = rtrim(WRITEPATH, '/\\') . '/uploads/' . $base;
    return is_file($disk) ? $disk : null;
  }
}

if (!function_exists('meta_guess_mime_por_extensao')) {
  /**
   * Tenta inferir MIME a partir da extensão.
   */
  function meta_guess_mime_por_extensao(string $filePath): string
  {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $map = [
      'png' => 'image/png',
      'jpg' => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'webp' => 'image/webp',
      'gif' => 'image/gif',
      'mp4' => 'video/mp4',
      'pdf' => 'application/pdf',
    ];
    return $map[$ext] ?? 'application/octet-stream';
  }
}

if (!function_exists('meta_obter_app_id_por_token')) {
  /**
   * Usa /debug_token para extrair app_id do token atual.
   */
  function meta_obter_app_id_por_token(string $token): ?string
  {
    $debugUrl = "https://graph.facebook.com/v22.0/debug_token?input_token=" . urlencode($token);
    $ch = curl_init($debugUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http < 200 || $http >= 300) return null;
    $decoded = json_decode((string) $resp, true);
    return $decoded['data']['app_id'] ?? null;
  }
}

if (!function_exists('meta_upload_handle_aprovacao_header')) {
  /**
   * Faz upload resumable para obter `handle (h)` usado em `example.header_handle` na criação do template.
   *
   * @return array {status: success|error, h?: string, message?: string}
   */
  function meta_upload_handle_aprovacao_header(string $token, string $diskPath, string $mime): array
  {
    if (!is_file($diskPath)) {
      return ['status' => 'error', 'message' => 'Arquivo não encontrado para upload de aprovação.'];
    }

    $appId = meta_obter_app_id_por_token($token);
    if (!$appId) {
      return ['status' => 'error', 'message' => 'Não foi possível identificar o app_id do token (debug_token).'];
    }

    $fileSize = filesize($diskPath);
    $fileName = basename($diskPath);

    $createUploadUrl = "https://graph.facebook.com/v22.0/{$appId}/uploads"
      . "?file_length={$fileSize}&file_type=" . urlencode($mime) . "&file_name=" . urlencode($fileName);

    $ch = curl_init($createUploadUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $createResp = curl_exec($ch);
    $createHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $create = json_decode((string) $createResp, true);
    $uploadId = $create['id'] ?? null;
    if ($createHttp < 200 || $createHttp >= 300 || !$uploadId) {
      return ['status' => 'error', 'message' => $create['error']['message'] ?? 'Falha ao criar sessão de upload (uploads).'];
    }

    $bytes = file_get_contents($diskPath);
    $uploadBytesUrl = "https://graph.facebook.com/v22.0/{$uploadId}";
    $ch = curl_init($uploadBytesUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: Bearer ' . $token,
      'file_offset: 0',
      'Content-Type: application/octet-stream',
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bytes);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $upResp = curl_exec($ch);
    $upHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $up = json_decode((string) $upResp, true);
    $h = $up['h'] ?? null;
    if ($upHttp < 200 || $upHttp >= 300 || !$h) {
      return ['status' => 'error', 'message' => $up['error']['message'] ?? 'Falha ao enviar bytes da mídia (upload session).'];
    }

    return ['status' => 'success', 'h' => $h];
  }
}

if (!function_exists('meta_upsert_status_template')) {
  /**
   * Upsert no espelho de status (type=WA_TEMPLATE_TYPE_META_STATUS) por (team_id, name, language, account_ids).
   */
  function meta_upsert_status_template(int $teamId, string $templateName, string $language, string $accountIds, array $statusData): void
  {
    $db = \Config\Database::connect();
    $idsFn = function (): string {
      if (function_exists('ids')) return (string) ids();
      return uniqid();
    };
    $existing = $db->query(
      "SELECT * FROM " . TB_WHATSAPP_TEMPLATE . "
       WHERE team_id = ? AND type = ? AND name = ?
         AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.account_ids')) = ?
         AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.language')) = ?
       LIMIT 1",
      [$teamId, WA_TEMPLATE_TYPE_META_STATUS, $templateName, $accountIds, $language]
    )->getRow();

    if (empty($existing)) {
      $row = [
        "ids" => $idsFn(),
        "team_id" => $teamId,
        "type" => WA_TEMPLATE_TYPE_META_STATUS,
        "name" => $templateName,
        "data" => json_encode($statusData),
        "created" => time(),
        "changed" => time(),
      ];
      if (function_exists('db_insert')) {
        db_insert(TB_WHATSAPP_TEMPLATE, $row);
      } else {
        $db->table(TB_WHATSAPP_TEMPLATE)->insert($row);
      }
      return;
    }

    $upd = [
      "data" => json_encode($statusData),
      "changed" => time(),
    ];
    if (function_exists('db_update')) {
      db_update(TB_WHATSAPP_TEMPLATE, $upd, ["id" => $existing->id]);
    } else {
      $db->table(TB_WHATSAPP_TEMPLATE)->where("id", (int) $existing->id)->update($upd);
    }
  }
}

if (!function_exists('meta_upload_media_para_envio')) {
  /**
   * Faz upload de mídia para ENVIO (Cloud API /{phone_number_id}/media) e retorna media_id.
   *
   * @param object $account Registro de `sp_accounts` (login_type=1) com JSON em `$account->data`
   * @return array {status: success|error, media_id?: string, message?: string, data?: array}
   */
  function meta_upload_media_para_envio($account, string $diskPath, string $mime): array
  {
    $accData = json_decode($account->data ?? '{}', true) ?: [];
    $phoneId = $accData['phone_number_id'] ?? null;
    $token = $accData['token'] ?? null;
    if (!$phoneId || !$token) {
      return ['status' => 'error', 'message' => 'Credenciais Cloud API ausentes (phone_number_id/token).'];
    }
    if (!is_file($diskPath)) {
      return ['status' => 'error', 'message' => 'Arquivo não encontrado para upload de mídia (envio).'];
    }

    $url = "https://graph.facebook.com/v22.0/{$phoneId}/media";
    $ch = curl_init($url);
    $postFields = [
      'messaging_product' => 'whatsapp',
      'file' => new \CURLFile($diskPath, $mime ?: 'application/octet-stream', basename($diskPath)),
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode((string) $resp, true);
    if ($http >= 200 && $http < 300 && !empty($decoded['id'])) {
      return ['status' => 'success', 'media_id' => (string) $decoded['id'], 'data' => $decoded];
    }
    return ['status' => 'error', 'message' => $decoded['error']['message'] ?? ('Falha no upload de mídia (HTTP ' . $http . ')'), 'data' => $decoded];
  }
}

if (!function_exists('meta_criar_template_na_meta')) {
  /**
   * Cria um Message Template no WABA via Business Management API.
   *
   * @param object $account Registro de `sp_accounts` (login_type=1) com JSON em `$account->data`
   * @param array $payload Payload compatível com /{waba_id}/message_templates
   * @return array {status: success|error, meta_template_id?: string, message?: string, data?: array}
   */
  function meta_criar_template_na_meta($account, array $payload): array
  {
    $accData = json_decode($account->data ?? '{}', true) ?: [];
    $wabaId = $accData['waba_id'] ?? null;
    $token = $accData['token'] ?? null;
    if (!$wabaId || !$token) {
      return ['status' => 'error', 'message' => 'Credenciais Cloud API ausentes (waba_id/token).'];
    }

    $url = "https://graph.facebook.com/v22.0/{$wabaId}/message_templates";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode((string) $resp, true);
    $metaId = $decoded['id'] ?? ($decoded['message_template_id'] ?? null);
    if ($http >= 200 && $http < 300 && $metaId) {
      return ['status' => 'success', 'meta_template_id' => (string) $metaId, 'data' => $decoded];
    }
    return ['status' => 'error', 'message' => $decoded['error']['message'] ?? ('Falha ao criar template (HTTP ' . $http . ')'), 'data' => $decoded];
  }
}

if (!function_exists('meta_sync_to_button_template')) {
  /**
   * Converte/Sincroniza um template APPROVED da Meta para o formato `type=2` (Modelo de Botão)
   * no banco de dados do Zapmatic, para que seja listado na página de envio e de edição.
   */
  function meta_sync_to_button_template(int $teamId, string $accountIds, array $templateDataFromMeta): void
  {
    $db = \Config\Database::connect();
    
    $sourceTemplateIds = $templateDataFromMeta['source_template_ids'] ?? null;
    $zapmetaId = (string)$sourceTemplateIds;
    
    // Se não tiver source_template_ids (foi criado direto no business manager), criamos um novo ids()
    if ($zapmetaId === '') {
      $zapmetaId = function_exists('ids') ? ids() : uniqid();
      $templateDataFromMeta['source_template_ids'] = $zapmetaId;
      $templateDataFromMeta['source_template_type'] = 2; // type=2 é button template
      
      // Atualiza o espelho de status (type=WA_TEMPLATE_TYPE_META_STATUS)
      meta_upsert_status_template($teamId, $templateDataFromMeta['name'], $templateDataFromMeta['language'], $accountIds, $templateDataFromMeta);
    }
    
    // Converte components da Meta para o formato do Zapmatic (Button Template)
    $btnTemplate = [
      "templateButtons" => [],
      "footer" => "",
      "title" => "",
      "text" => "",
      "caption" => "",
      "meta_official" => [
        "enabled" => true,
        "base_name" => $templateDataFromMeta['name'],
        "category" => $templateDataFromMeta['category'] ?? "MARKETING",
        "languages" => $templateDataFromMeta['language'] ?? "pt_BR",
        "header_format" => "TEXT",
        "body_example" => "",
      ]
    ];
    
    $components = $templateDataFromMeta['components'] ?? [];
    $btnIndex = 1;
    
    foreach ((array)$components as $comp) {
        $type = strtoupper((string)($comp['type'] ?? ''));
        if ($type === 'HEADER') {
            $btnTemplate['meta_official']['header_format'] = strtoupper((string)($comp['format'] ?? 'TEXT'));
            if ($btnTemplate['meta_official']['header_format'] === 'TEXT') {
                $btnTemplate['title'] = (string)($comp['text'] ?? '');
            } else if (in_array($btnTemplate['meta_official']['header_format'], ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                // Se header for de midia, o usuario deverá usar uma imagem no Zapmatic ou usar o fallback que construímos no send_message!
                $btnTemplate['title'] = "Template c/ Mídia"; 
            }
        } elseif ($type === 'BODY') {
            $btnTemplate['caption'] = (string)($comp['text'] ?? '');
            $btnTemplate['text'] = (string)($comp['text'] ?? '');
            
            if (isset($comp['example']['body_text'][0]) && is_array($comp['example']['body_text'][0])) {
                $btnTemplate['meta_official']['body_example'] = implode('|', $comp['example']['body_text'][0]);
            }
        } elseif ($type === 'FOOTER') {
            $btnTemplate['footer'] = (string)($comp['text'] ?? '');
        } elseif ($type === 'BUTTONS') {
            $buttons = $comp['buttons'] ?? [];
            foreach ((array)$buttons as $metaBtn) {
                $btnType = strtoupper((string)($metaBtn['type'] ?? ''));
                $btnText = (string)($metaBtn['text'] ?? '');
                
                $zapBtn = ["index" => $btnIndex];
                
                if ($btnType === 'QUICK_REPLY') {
                    $zapBtn['quickReplyButton'] = [
                        "displayText" => mb_substr($btnText, 0, 25),
                        "id" => uniqid()
                    ];
                } elseif ($btnType === 'URL') {
                    $zapBtn['urlButton'] = [
                        "displayText" => mb_substr($btnText, 0, 25),
                        "url" => $metaBtn['url'] ?? ''
                    ];
                } elseif ($btnType === 'PHONE_NUMBER') {
                    $zapBtn['callButton'] = [
                        "displayText" => mb_substr($btnText, 0, 25),
                        "phoneNumber" => preg_replace('/[^0-9+]/', '', $metaBtn['phone_number'] ?? '')
                    ];
                } else {
                    // Fallback
                    $zapBtn['quickReplyButton'] = [
                        "displayText" => mb_substr($btnText, 0, 25),
                        "id" => uniqid()
                    ];
                }
                
                $btnTemplate['templateButtons'][] = $zapBtn;
                $btnIndex++;
            }
        }
    }
    
    // Verifica se já existe o type=2
    $existingBtn = $db->query(
        "SELECT * FROM " . TB_WHATSAPP_TEMPLATE . " WHERE ids = ? AND team_id = ? AND type = 2 LIMIT 1",
        [$zapmetaId, $teamId]
    )->getRow();
    
    if (empty($existingBtn)) {
        // Insere novo template type=2
        $row = [
            "ids" => $zapmetaId,
            "team_id" => $teamId,
            "type" => 2,
            "name" => $templateDataFromMeta['name'] . ($templateDataFromMeta['language'] !== 'pt_BR' ? " ({$templateDataFromMeta['language']})" : ""),
            "data" => json_encode($btnTemplate),
            "created" => time(),
            "changed" => time(),
        ];
        
        if (function_exists('db_insert')) {
            db_insert(TB_WHATSAPP_TEMPLATE, $row);
        } else {
            $db->table(TB_WHATSAPP_TEMPLATE)->insert($row);
        }
    } else {
        // Atualiza o existente type=2 preservando media caso exista
        $existingData = json_decode($existingBtn->data ?? '', true) ?: [];
        
        if (isset($existingData['image'])) $btnTemplate['image'] = $existingData['image'];
        if (isset($existingData['media'])) $btnTemplate['media'] = $existingData['media'];
        
        $upd = [
            // Mantem o nome original em caso de alteração no sistema local
            "data" => json_encode($btnTemplate),
            "changed" => time(),
        ];
        
        if (function_exists('db_update')) {
            db_update(TB_WHATSAPP_TEMPLATE, $upd, ["id" => $existingBtn->id]);
        } else {
            $db->table(TB_WHATSAPP_TEMPLATE)->where("id", (int) $existingBtn->id)->update($upd);
        }
    }
  }
}

