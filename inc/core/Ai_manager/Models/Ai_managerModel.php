<?php
namespace Core\Ai_manager\Models;

use CodeIgniter\Model;

class Ai_managerModel extends Model
{
    protected $table = 'sp_ai_settings';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'team_id', 'openrouter_key', 'openai_key', 'anthropic_key', 'gemini_key', 'mistral_key',
        'groq_key', 'deepseek_key', 'perplexity_key', 'together_key', 'default_provider',
        'default_model', 'status', 'created', 'changed'
    ];

    public $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
    }

    public function get_settings($teamId)
    {
        \App\Services\AIService::ensureTables();
        return $this->where('team_id', $teamId)->first();
    }

    public function block_settings($path = "")
    {
        return [
            'position' => 9250,
            'menu' => view('Core\Ai_manager\Views\settings\menu', ['config' => $this->config]),
            'content' => '<div class="container my-5"><div class="alert alert-primary">Abra a <a href="' . base_url('ai_manager') . '" class="fw-bold">Central de IA</a> para configurar as APIs globais.</div></div>'
        ];
    }
}
