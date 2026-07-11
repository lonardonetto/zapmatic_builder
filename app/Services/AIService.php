<?php
namespace App\Services;

class AIService
{
    private const ENDPOINTS = [
        'openai' => 'https://api.openai.com/v1/chat/completions',
        'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
        'mistral' => 'https://api.mistral.ai/v1/chat/completions',
        'groq' => 'https://api.groq.com/openai/v1/chat/completions',
        'deepseek' => 'https://api.deepseek.com/v1/chat/completions',
        'perplexity' => 'https://api.perplexity.ai/chat/completions',
        'together' => 'https://api.together.xyz/v1/chat/completions',
    ];

    private const DEFAULT_MODELS = [
        'openrouter' => 'openai/gpt-oss-20b:free',
        'openai' => 'gpt-4o-mini',
        'anthropic' => 'claude-3-5-sonnet-20241022',
        'mistral' => 'mistral-large-latest',
        'groq' => 'llama-3.1-70b-versatile',
        'deepseek' => 'deepseek-chat',
        'perplexity' => 'llama-3.1-sonar-small-128k-online',
        'together' => 'meta-llama/Llama-3-70b-chat-hf',
    ];

    public static function getSettings($teamId): array
    {
        self::ensureTables();
        $row = \Config\Database::connect()->table('sp_ai_settings')->where('team_id', $teamId)->get()->getRowArray();
        return $row ?: [];
    }

    public static function reply(string $provider, array $config, string $userMessage, $teamId): string
    {
        self::ensureTables();
        $settings = self::getSettings($teamId);
        $provider = self::resolveProvider($provider, $settings);

        if (!$provider) {
            return 'Nenhuma chave de IA configurada. Configure em Settings → Central de IA.';
        }

        $config['model'] = $config['model'] ?: ($settings['default_model'] ?? self::DEFAULT_MODELS[$provider] ?? '');
        $config['system_prompt'] = $config['system_prompt'] ?? 'Você é um assistente útil.';

        try {
            $started = microtime(true);
            $result = $provider === 'anthropic'
                ? self::anthropic($config, $userMessage, $settings)
                : self::openAiCompatible($provider, $config, $userMessage, $settings);
            self::logCall($teamId, $provider, $config['model'], $userMessage, $result, (int)round((microtime(true) - $started) * 1000));
            return $result;
        } catch (\Throwable $e) {
            log_message('error', 'AIService: ' . $e->getMessage());
            return 'Erro ao processar IA: ' . $e->getMessage();
        }
    }

    public static function testConnection(string $provider, string $apiKey): array
    {
        if (!$apiKey) {
            return ['status' => 'error', 'message' => 'Informe a chave da API.'];
        }

        if ($provider === 'openrouter') {
            return self::testGet('https://openrouter.ai/api/v1/models', ['Authorization: Bearer ' . $apiKey]);
        }

        $config = ['model' => self::DEFAULT_MODELS[$provider] ?? '', 'system_prompt' => 'Teste', 'temperature' => 0.1, 'max_tokens' => 16];
        $settings = [$provider . '_key' => $apiKey];
        $response = $provider === 'anthropic'
            ? self::anthropic($config, 'Responda apenas OK.', $settings)
            : self::openAiCompatible($provider, $config, 'Responda apenas OK.', $settings);

        return stripos($response, 'error') === false && stripos($response, 'missing') === false
            ? ['status' => 'success', 'message' => 'Conexão realizada com sucesso.']
            : ['status' => 'error', 'message' => $response];
    }

    public static function listModels($teamId): array
    {
        $settings = self::getSettings($teamId);
        $apiKey = $settings['openrouter_key'] ?? '';
        if (!$apiKey) return [];

        $ch = curl_init('https://openrouter.ai/api/v1/models');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $body = json_decode($response, true);
        return $body['data'] ?? [];
    }

    public static function ensureTables(): void
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();
        $fields = [
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'team_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'openrouter_key' => ['type' => 'TEXT', 'null' => true],
            'openai_key' => ['type' => 'TEXT', 'null' => true],
            'anthropic_key' => ['type' => 'TEXT', 'null' => true],
            'gemini_key' => ['type' => 'TEXT', 'null' => true],
            'mistral_key' => ['type' => 'TEXT', 'null' => true],
            'groq_key' => ['type' => 'TEXT', 'null' => true],
            'deepseek_key' => ['type' => 'TEXT', 'null' => true],
            'perplexity_key' => ['type' => 'TEXT', 'null' => true],
            'together_key' => ['type' => 'TEXT', 'null' => true],
            'default_provider' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'default_model' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status' => ['type' => 'INT', 'constraint' => 1, 'null' => true],
            'created' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'changed' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ];

        if (!$db->tableExists('sp_ai_settings')) {
            $forge->addField($fields);
            $forge->addPrimaryKey('id');
            $forge->addKey('team_id');
            $forge->createTable('sp_ai_settings', true);
            return;
        }

        foreach ($fields as $field => $attr) {
            if ($field !== 'id' && !$db->fieldExists($field, 'sp_ai_settings')) {
                $forge->addColumn('sp_ai_settings', [$field => $attr]);
            }
        }
    }

    private static function resolveProvider(string $provider, array $settings): string
    {
        if ($provider && $provider !== 'auto') {
            return !empty($settings[$provider . '_key']) ? $provider : '';
        }

        $preferred = $settings['default_provider'] ?? 'openrouter';
        foreach (array_unique([$preferred, 'openrouter', 'openai', 'anthropic', 'mistral', 'groq', 'deepseek', 'perplexity', 'together']) as $candidate) {
            if (!empty($settings[$candidate . '_key'])) return $candidate;
        }
        return '';
    }

    private static function openAiCompatible(string $provider, array $config, string $userMessage, array $settings): string
    {
        $apiKey = $settings[$provider . '_key'] ?? '';
        if (!$apiKey) return ucfirst($provider) . ' API key missing.';

        $endpoint = self::ENDPOINTS[$provider] ?? '';
        if (!$endpoint) return 'Provider não suportado: ' . $provider;

        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey];
        if ($provider === 'openrouter') {
            $headers[] = 'HTTP-Referer: ' . base_url();
            $headers[] = 'X-Title: Zapmatic';
        }

        return self::postJson($endpoint, [
            'model' => $config['model'] ?? self::DEFAULT_MODELS[$provider],
            'messages' => array_values(array_filter([
                !empty($config['system_prompt']) ? ['role' => 'system', 'content' => $config['system_prompt']] : null,
                ['role' => 'user', 'content' => $userMessage],
            ])),
            'temperature' => (float)($config['temperature'] ?? 0.7),
            'max_tokens' => (int)($config['max_tokens'] ?? 500),
        ], $headers, ['choices', 0, 'message', 'content']);
    }

    private static function anthropic(array $config, string $userMessage, array $settings): string
    {
        $apiKey = $settings['anthropic_key'] ?? '';
        if (!$apiKey) return 'Anthropic API key missing.';

        return self::postJson('https://api.anthropic.com/v1/messages', [
            'model' => $config['model'] ?? self::DEFAULT_MODELS['anthropic'],
            'system' => $config['system_prompt'] ?? 'Você é um assistente útil.',
            'messages' => [['role' => 'user', 'content' => $userMessage]],
            'temperature' => (float)($config['temperature'] ?? 0.7),
            'max_tokens' => (int)($config['max_tokens'] ?? 1024),
        ], ['Content-Type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01'], ['content', 0, 'text']);
    }

    private static function postJson(string $url, array $payload, array $headers, array $path): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return 'Erro de conexão: ' . $error;
        $body = json_decode($response, true);
        if ($httpCode >= 400 || isset($body['error'])) {
            $message = $body['error']['message'] ?? $body['error'] ?? ('HTTP ' . $httpCode);
            return 'AI Error: ' . (is_string($message) ? $message : json_encode($message));
        }

        $value = $body;
        foreach ($path as $key) {
            if (!isset($value[$key])) return 'No response';
            $value = $value[$key];
        }
        return is_string($value) ? $value : json_encode($value);
    }

    private static function testGet(string $url, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode === 200
            ? ['status' => 'success', 'message' => 'Conexão realizada com sucesso.']
            : ['status' => 'error', 'message' => 'Falha na conexão HTTP ' . $httpCode];
    }

    private static function logCall($teamId, string $provider, string $model, string $prompt, string $response, int $timeMs): void
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('sp_ai_logs')) return;
            $db->table('sp_ai_logs')->insert([
                'team_id' => $teamId,
                'provider' => $provider,
                'model' => $model,
                'prompt' => mb_substr($prompt, 0, 2000),
                'response' => mb_substr($response, 0, 5000),
                'execution_time_ms' => $timeMs,
                'created' => time(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
