<?php
namespace Core\Ai_manager\Controllers;

class Ai_manager extends \CodeIgniter\Controller
{
    public $config;
    public $model;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Ai_manager\Models\Ai_managerModel();
    }

    public function index()
    {
        $teamId = get_team('id');
        $settings = $this->model->get_settings($teamId) ?: [];
        $models = !empty($settings['openrouter_key']) ? \App\Services\AIService::listModels($teamId) : [];

        $data = [
            'title' => $this->config['name'],
            'desc' => $this->config['desc'],
            'content' => view('Core\Ai_manager\Views\settings', [
                'config' => $this->config,
                'settings' => $settings,
                'models' => $models,
            ])
        ];

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function save()
    {
        \App\Services\AIService::ensureTables();
        $teamId = get_team('id');
        $settings = $this->model->get_settings($teamId);
        $data = [
            'team_id' => $teamId,
            'openrouter_key' => $this->request->getPost('openrouter_key'),
            'openai_key' => $this->request->getPost('openai_key'),
            'anthropic_key' => $this->request->getPost('anthropic_key'),
            'gemini_key' => $this->request->getPost('gemini_key'),
            'mistral_key' => $this->request->getPost('mistral_key'),
            'groq_key' => $this->request->getPost('groq_key'),
            'deepseek_key' => $this->request->getPost('deepseek_key'),
            'perplexity_key' => $this->request->getPost('perplexity_key'),
            'together_key' => $this->request->getPost('together_key'),
            'default_provider' => $this->request->getPost('default_provider') ?: 'openrouter',
            'default_model' => $this->request->getPost('default_model') ?: 'openai/gpt-4o-mini',
            'status' => 1,
            'changed' => time(),
        ];

        $ok = $settings
            ? $this->model->update($settings['id'], $data)
            : $this->model->insert($data + ['created' => time()]);

        ms($ok ? ['status' => 'success', 'message' => 'Configuração de IA salva.'] : ['status' => 'error', 'message' => 'Falha ao salvar configuração de IA.']);
    }

    public function test_connection()
    {
        $provider = $this->request->getPost('provider') ?: 'openrouter';
        $key = $this->request->getPost('api_key') ?: '';
        ms(\App\Services\AIService::testConnection($provider, $key));
    }

    public function models()
    {
        ms(['status' => 'success', 'data' => \App\Services\AIService::listModels(get_team('id'))]);
    }
}
