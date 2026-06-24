<?php
namespace Core\Bot_builder\Controllers;

class Bot_builder extends \CodeIgniter\Controller
{
    public $config;
    public $model;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Bot_builder\Models\Bot_builderModel();
    }

    // ===================== PAGES =====================

    public function index()
    {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "content" => view('Core\Bot_builder\Views\index', [
                "config" => $this->config,
                "bots" => $this->model->get_list()
            ])
        ];
        return view('Core\Whatsapp\Views\index', $data);
    }

    public function create()
    {
        $data = [
            "title" => "Criar Bot",
            "desc" => $this->config['desc'],
            "content" => view('Core\Bot_builder\Views\create', [
                "config" => $this->config,
                "recent_bots" => $this->model->get_list()
            ])
        ];
        return view('Core\Whatsapp\Views\index', $data);
    }

    public function editor($id = false)
    {
        $bot = $this->model->get_bot($id);
        if(!$bot) return redirect()->to(base_url('bot-builder'));

        // Get available WhatsApp instances and current integrations
        $instances = $this->model->get_available_instances();
        $integrations = $this->model->get_integrations($id);
        $linked_ids = array_map(function($i){ return (int)$i->instance_id; }, $integrations);

        return view('Core\Bot_builder\Views\editor', [
            "bot" => $bot,
            "blocks" => $this->model->get_blocks($id),
            "edges" => $this->model->get_edges($id),
            "config" => $this->config,
            "instances" => $instances,
            "integrations" => $integrations,
            "linked_instance_ids" => $linked_ids
        ]);
    }

    public function native_templates()
    {
        $type = (int) $this->request->getGet('type');
        if(!in_array($type, [1, 2, 5], true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tipo de template inválido']);
        }

        $team_id = get_team('id');
        $items = db_fetch('id, ids, name, type, data, changed, created', TB_WHATSAPP_TEMPLATE, [
            'team_id' => $team_id,
            'type' => $type
        ], 'id', 'DESC');

        $data = [];
        if(!empty($items)) {
            foreach($items as $item) {
                $data[] = $this->format_native_template_for_builder($item);
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $data,
            'create_url' => $this->native_template_create_url($type)
        ]);
    }

    public function native_template($ids = false)
    {
        $team_id = get_team('id');
        $item = db_get('id, ids, name, type, data, changed, created', TB_WHATSAPP_TEMPLATE, [
            'team_id' => $team_id,
            'ids' => $ids
        ]);

        if(empty($item)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Template não encontrado']);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $this->format_native_template_for_builder($item)
        ]);
    }

    private function format_native_template_for_builder($item)
    {
        $payload = json_decode($item->data ?? '{}');
        if(!is_object($payload) && !is_array($payload)) $payload = new \stdClass();
        $type = (int)($item->type ?? 0);

        return [
            'id' => (int)($item->id ?? 0),
            'ids' => $item->ids ?? '',
            'name' => $item->name ?? '',
            'type' => $type,
            'data' => $payload,
            'edit_url' => $this->native_template_edit_url($type, $item->ids ?? ''),
            'create_url' => $this->native_template_create_url($type),
        ];
    }

    private function native_template_create_url($type)
    {
        switch((int)$type) {
            case 1: return base_url('whatsapp_list_message_template/index/update');
            case 2: return base_url('whatsapp_button_template/index/update');
            case 5: return base_url('whatsapp_carousel_template/index/update');
        }
        return '#';
    }

    private function native_template_edit_url($type, $ids)
    {
        switch((int)$type) {
            case 1: return base_url('whatsapp_list_message_template/index/update/' . $ids);
            case 2: return base_url('whatsapp_button_template/index/update/' . $ids);
            case 5: return base_url('whatsapp_carousel_template/index/update/' . $ids);
        }
        return '#';
    }

    public function sessions($id = false)
    {
        $bot = $this->model->get_bot($id);
        if(!$bot) return redirect()->to(base_url('bot-builder'));

        $sessions = $this->model->get_sessions($id);
        $total = count($sessions);
        $active = 0;
        $completed = 0;
        foreach($sessions as $s) {
            if(!empty($s->is_completed)) $completed++;
            else $active++;
        }

        $data = [
            "title" => $bot->name . " - Atendimentos",
            "desc" => "Sessões de atendimento",
            "content" => view('Core\Bot_builder\Views\sessions', [
                "bot" => $bot,
                "sessions" => $sessions,
                "total_sessions" => $total,
                "active_sessions" => $active,
                "completed_sessions" => $completed,
                "config" => $this->config
            ])
        ];
        return view('Core\Whatsapp\Views\index', $data);
    }

    public function analytics($id = false)
    {
        $bot = $this->model->get_bot($id);
        if(!$bot) return redirect()->to(base_url('bot-builder'));

        $sessions = $this->model->get_sessions($id);
        $blocks = $this->model->get_blocks($id);
        $edges = $this->model->get_edges($id);

        $total_sessions = count($sessions);
        $completed_sessions = 0;
        $active_sessions = 0;
        $unique_phones = [];
        $daily_sessions = [];
        $hourly_dist = array_fill(0, 24, 0);

        foreach($sessions as $s) {
            if(!empty($s->is_completed)) $completed_sessions++;
            else $active_sessions++;

            if(!empty($s->phone) && !in_array($s->phone, $unique_phones)) {
                $unique_phones[] = $s->phone;
            }

            // Daily trend (last 30 days)
            if(!empty($s->created_at)) {
                $day = date('Y-m-d', strtotime($s->created_at));
                if(!isset($daily_sessions[$day])) $daily_sessions[$day] = 0;
                $daily_sessions[$day]++;

                $hour = (int)date('G', strtotime($s->created_at));
                $hourly_dist[$hour]++;
            }
        }

        $completion_rate = $total_sessions > 0 ? round(($completed_sessions / $total_sessions) * 100, 1) : 0;

        // Build last 14 days data
        $trend_labels = [];
        $trend_data = [];
        for($i = 13; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $trend_labels[] = date('M j', strtotime($day));
            $trend_data[] = $daily_sessions[$day] ?? 0;
        }

        // Block type distribution
        $block_types = [];
        foreach($blocks as $b) {
            $t = $b->type ?? 'unknown';
            if(!isset($block_types[$t])) $block_types[$t] = 0;
            $block_types[$t]++;
        }

        $data = [
            "title" => $bot->name . " - Métricas",
            "desc" => "Métricas do bot",
            "content" => view('Core\Bot_builder\Views\analytics', [
                "bot" => $bot,
                "config" => $this->config,
                "total_sessions" => $total_sessions,
                "completed_sessions" => $completed_sessions,
                "active_sessions" => $active_sessions,
                "unique_users" => count($unique_phones),
                "completion_rate" => $completion_rate,
                "trend_labels" => json_encode($trend_labels),
                "trend_data" => json_encode($trend_data),
                "block_types" => $block_types,
                "total_blocks" => count($blocks),
                "total_edges" => count($edges),
                "hourly_dist" => json_encode(array_values($hourly_dist))
            ])
        ];
        return view('Core\Whatsapp\Views\index', $data);
    }

    public function overview($id = false)
    {
        $bot = $this->model->get_bot($id);
        if(!$bot) return redirect()->to(base_url('bot-builder'));

        $sessions = $this->model->get_sessions($id);
        $blocks = $this->model->get_blocks($id);
        $edges = $this->model->get_edges($id);

        $total_sessions = count($sessions);
        $completed = 0;
        $active = 0;
        foreach($sessions as $s) {
            if(!empty($s->is_completed)) $completed++;
            else $active++;
        }

        $data = [
            "title" => $bot->name . " - Visão geral",
            "desc" => "Visão geral do bot",
            "content" => view('Core\Bot_builder\Views\overview', [
                "bot" => $bot,
                "config" => $this->config,
                "total_sessions" => $total_sessions,
                "completed_sessions" => $completed,
                "active_sessions" => $active,
                "total_blocks" => count($blocks),
                "total_edges" => count($edges),
                "blocks" => $blocks
            ])
        ];
        return view('Core\Whatsapp\Views\index', $data);
    }

    // ===================== SAVE =====================

    public function save()
    {
        $name = post('name');
        if ($name) {
            $id = post('id');
            $data = [
                'name' => $name,
                'description' => post('description'),
                'trigger_keywords' => post('trigger_keywords'),
                'status' => post('status') ? 1 : 0,
            ];

            if($id) {
                $this->model->update($id, $data);
            } else {
                $data['team_id'] = get_team("id");
                $data['created_by'] = get_user("id");
                $id = $this->model->insert($data);
            }
            return redirect()->to(base_url('bot-builder/'.$id.'/editor'));
        }

        // AJAX Save Flow
        $bot_id = post('bot_id');
        if ($bot_id) {
            try {
                $blocks = json_decode((string) post('blocks'), true);
                $edges = json_decode((string) post('edges'), true);
                $publish = post('publish');

                if (!is_array($blocks)) $blocks = [];
                if (!is_array($edges)) $edges = [];

                $this->model->save_flow($bot_id, $blocks, $edges);

                // Create version snapshot
                $this->model->create_version($bot_id, $blocks, $edges);

            // Update keywords and bot_enabled if provided
            $update_data = [];
            if($this->request->getPost('trigger_keywords') !== null) {
                $update_data['trigger_keywords'] = $this->request->getPost('trigger_keywords');
            }
            if($this->request->getPost('enable_keyword') !== null) {
                $update_data['enable_keyword'] = $this->request->getPost('enable_keyword');
            }
            if($this->request->getPost('stop_keyword') !== null) {
                $update_data['stop_keyword'] = $this->request->getPost('stop_keyword');
            }
            if($this->request->getPost('bot_enabled') !== null) {
                $update_data['bot_enabled'] = $this->request->getPost('bot_enabled') ? 1 : 0;
            }
            if($this->request->getPost('keyword_match_type') !== null) {
                $update_data['keyword_match_type'] = $this->request->getPost('keyword_match_type');
            }
            if($this->request->getPost('chat_type') !== null) {
                $update_data['chat_type'] = $this->request->getPost('chat_type');
            }

            if($publish) {
                $update_data['status'] = 1;
            }

            if(!empty($update_data)) {
                $this->model->db->table('sp_bot_builders')->where('id', $bot_id)->update($update_data);
            }

                return $this->response->setJSON(['status' => 'success', 'message' => 'Flow saved', 'csrf_hash' => csrf_hash()]);
            } catch (\Throwable $e) {
                log_message('error', 'Bot Builder save error: ' . $e->getMessage());
                return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    public function upload_media()
    {
        $bot_id = (int) post('bot_id');
        $type = strtolower((string) post('type'));
        $file = $this->request->getFile('media');

        if (!$bot_id || !$this->model->get_bot($bot_id)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bot inválido']);
        }

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Arquivo inválido']);
        }

        $allowed = [
            'image' => ['jpg','jpeg','png','gif','webp'],
            'video' => ['mp4','mov','m4v','webm'],
            'audio' => ['mp3','ogg','wav','m4a','aac'],
            'document' => ['pdf','doc','docx','xls','xlsx','csv','txt']
        ];
        $extensions = $allowed[$type] ?? array_merge(...array_values($allowed));
        $ext = strtolower($file->getClientExtension());

        if (!in_array($ext, $extensions)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tipo de arquivo não permitido para este bloco']);
        }

        if ($file->getSizeByUnit('mb') > 25) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Arquivo maior que 25MB']);
        }

        $newName = 'bb_' . get_team('id') . '_' . $bot_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $file->move(WRITEPATH . 'uploads', $newName, true);

        if (!$file->hasMoved()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Não foi possível salvar o arquivo']);
        }

        $url = base_url('writable/uploads/' . $newName);
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Mídia enviada com sucesso',
            'url' => $url,
            'name' => $file->getClientName(),
            'type' => $type,
            'extension' => $ext,
            'size' => $file->getSize(),
            'csrf_hash' => csrf_hash()
        ]);
    }

    public function delete()
    {
        $id = post('id');
        $this->model->delete($id);
        return $this->response->setJSON(['status' => 'success', 'message' => 'Bot excluído']);
    }

    // ===================== INTEGRATION ENDPOINTS =====================

    public function get_instances()
    {
        $instances = $this->model->get_available_instances();
        return $this->response->setJSON(['status' => 'success', 'instances' => $instances]);
    }

    public function get_bot_integrations($bot_id = false)
    {
        if(!$bot_id) return $this->response->setJSON(['status' => 'error', 'message' => 'ID do bot obrigatório']);
        $integrations = $this->model->get_integrations($bot_id);
        return $this->response->setJSON(['status' => 'success', 'integrations' => $integrations]);
    }

    public function link_instance()
    {
        $bot_id = post('bot_id');
        $instance_id = post('instance_id');

        if(!$bot_id || !$instance_id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Informe o bot e a conexão WhatsApp']);
        }

        // Save flow first, then publish
        $this->model->db->table('sp_bot_builders')->where('id', $bot_id)->update(['status' => 1]);

        $id = $this->model->link_instance($bot_id, $instance_id);
        return $this->response->setJSON([
            'status' => 'success', 
            'message' => 'Bot vinculado à conexão WhatsApp com sucesso',
            'integration_id' => $id
        ]);
    }

    public function unlink_instance()
    {
        $bot_id = post('bot_id');
        $instance_id = post('instance_id');

        if(!$bot_id || !$instance_id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Informe o bot e a conexão WhatsApp']);
        }

        $this->model->unlink_instance($bot_id, $instance_id);
        return $this->response->setJSON(['status' => 'success', 'message' => 'Bot desvinculado da conexão']);
    }

    // ===================== BOT SETTINGS (Keywords & Toggle) =====================

    public function save_bot_settings()
    {
        $bot_id = post('bot_id');
        if(!$bot_id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID do bot obrigatório']);
        }

        $update = [];
        if($this->request->getPost('trigger_keywords') !== null) {
            $update['trigger_keywords'] = $this->request->getPost('trigger_keywords');
        }
        if($this->request->getPost('enable_keyword') !== null) {
            $update['enable_keyword'] = $this->request->getPost('enable_keyword');
        }
        if($this->request->getPost('stop_keyword') !== null) {
            $update['stop_keyword'] = $this->request->getPost('stop_keyword');
        }
        if($this->request->getPost('bot_enabled') !== null) {
            $update['bot_enabled'] = $this->request->getPost('bot_enabled') ? 1 : 0;
        }
        if($this->request->getPost('keyword_match_type') !== null) {
            $update['keyword_match_type'] = $this->request->getPost('keyword_match_type');
        }
        if($this->request->getPost('chat_type') !== null) {
            $update['chat_type'] = $this->request->getPost('chat_type');
        }

        if(!empty($update)) {
            $this->model->db->table('sp_bot_builders')->where('id', $bot_id)->update($update);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Configurações do bot salvas']);
    }

    public function get_bot_settings($bot_id = false)
    {
        if(!$bot_id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID do bot obrigatório']);
        }

        $bot = $this->model->get_bot($bot_id);
        if(!$bot) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bot não encontrado']);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'trigger_keywords' => $bot->trigger_keywords ?? '',
                'enable_keyword' => $bot->enable_keyword ?? '',
                'stop_keyword' => $bot->stop_keyword ?? '',
                'bot_enabled' => isset($bot->bot_enabled) ? (int)$bot->bot_enabled : 1,
                'keyword_match_type' => $bot->keyword_match_type ?? 'contains',
                'chat_type' => $bot->chat_type ?? 'all',
            ]
        ]);
    }

    // ===================== CREATE & IMPORT =====================

    public function start_scratch()
    {
        $name = $this->request->getPost('bot_name');
        if(empty($name)) $name = 'Bot sem título';
        $keywords = $this->request->getPost('trigger_keywords') ?? '';

        $bot_id = $this->model->create_empty_bot(get_user('id'), get_team('id'), $name);

        if($bot_id && !empty($keywords)) {
            $this->model->db->table('sp_bot_builders')->where('id', $bot_id)->update([
                'trigger_keywords' => $keywords
            ]);
        }

        if($bot_id) {
            return redirect()->to(base_url('bot-builder/'.$bot_id.'/editor'));
        }
        return redirect()->back()->with('error', 'Não foi possível criar o bot.');
    }

    public function import_file()
    {
        $file = $this->request->getFile('file');
        if(!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Arquivo inválido.');
        }

        $json = file_get_contents($file->getTempName());
        $data = json_decode($json, true);

        if(!$data || !isset($data['blocks'])) {
            return redirect()->back()->with('error', 'Formato JSON inválido.');
        }

        $bot_id = $this->model->import_bot($data, get_user('id'), get_team('id'));
        if($bot_id) {
            return redirect()->to(base_url('bot-builder/'.$bot_id.'/editor'))->with('success', 'Importado com sucesso!');
        }
        return redirect()->back()->with('error', 'Falha ao importar.');
    }

    // ===================== TEMPLATES =====================

    public function templates($category = null)
    {
        $data = [
            "title" => "Biblioteca de Modelos",
            "desc" => "Escolha modelos prontos de bots",
            "content" => view('Core\Bot_builder\Views\templates_marketplace', [
                "config" => $this->config,
                "templates" => $this->model->get_templates($category),
                "categories" => $this->model->get_template_categories(),
                "active_category" => $category
            ])
        ];
        return view('Core\Whatsapp\Views\index', $data);
    }

    public function template_preview($id)
    {
        $template = $this->model->get_template($id);
        if(!$template) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Modelo não encontrado']);
        }

        $flow = json_decode($template->schema_json, true);
        $blocks = $flow['blocks'] ?? [];
        $block_types = [];
        foreach($blocks as $b) {
            $type = $b['type'] ?? 'unknown';
            if(!in_array($type, ['start','end'])) $block_types[] = $type;
        }

        return $this->response->setJSON([
            'status' => 'success',
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'category' => $template->category,
                'icon' => $template->icon,
                'icon_color' => $template->icon_color,
                'is_premium' => $template->is_premium,
                'price' => $template->price,
                'use_count' => $template->use_count,
                'node_count' => count($blocks),
                'block_types' => array_values(array_unique($block_types)),
                'blocks' => $blocks
            ]
        ]);
    }

    public function use_template($id)
    {
        try {
            $bot_id = $this->model->install_template($id, get_user('id'), get_team('id'));
        } catch(\Exception $e) {
            log_message('error', 'install_template error: ' . $e->getMessage());
            if($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
            }
            return redirect()->back()->with('error', 'Error installing template.');
        }

        if($bot_id) {
            try { $this->model->track_template_usage($id, get_user('id'), $bot_id); } catch(\Exception $e) {}

            if($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'redirect' => base_url('bot-builder/'.$bot_id.'/editor')
                ]);
            }
            return redirect()->to(base_url('bot-builder/'.$bot_id.'/editor'));
        }

        if($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Falha ao usar o modelo']);
        }
        return redirect()->back()->with('error', 'Falha ao usar o modelo.');
    }

    public function import_template_json()
    {
        $file = $this->request->getFile('file');
        if(!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Arquivo inválido.');
        }

        $json = file_get_contents($file->getTempName());
        $data = json_decode($json, true);

        if(!$data || !isset($data['blocks'])) {
            return redirect()->back()->with('error', 'JSON inválido.');
        }

        $bot_id = $this->model->import_bot($data, get_user('id'), get_team('id'));
        if($bot_id) {
            return redirect()->to(base_url('bot-builder/'.$bot_id.'/editor'));
        }
        return redirect()->back()->with('error', 'Falha ao importar.');
    }

    public function install_template($id) { return $this->use_template($id); }

    // ===================== EXPORT / IMPORT =====================

    public function export($id = false)
    {
        $bot = $this->model->get_bot($id);
        if(!$bot) return redirect()->to(base_url('bot-builder'));

        $blocks = $this->model->get_blocks($id);
        $edges = $this->model->get_edges($id);

        $data = [
            'meta' => [
                'name' => $bot->name,
                'description' => $bot->description,
                'keywords' => $bot->trigger_keywords,
                'version' => '3.0',
                'exported_at' => date('Y-m-d H:i:s')
            ],
            'blocks' => $blocks,
            'edges' => $edges
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $bot->name) . '_flow.json';

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length', strlen($json))
            ->setBody($json);
    }

    public function import()
    {
        $json = file_get_contents('php://input');
        if(!$json) return $this->response->setJSON(['status' => 'error', 'message' => 'Nenhum dado recebido']);

        $data = json_decode($json, true);
        if(!$data || !isset($data['blocks'])) return $this->response->setJSON(['status' => 'error', 'message' => 'JSON inválido']);

        $bot_data = [
            'name' => ($data['meta']['name'] ?? 'Bot importado') . ' (importado)',
            'description' => $data['meta']['description'] ?? '',
            'trigger_keywords' => $data['meta']['keywords'] ?? '',
            'status' => 0,
            'team_id' => get_team("id"),
            'created_by' => get_user("id")
        ];

        $bot_id = $this->model->insert($bot_data);
        $this->model->save_flow($bot_id, $data['blocks'], $data['edges']);

        return $this->response->setJSON(['status' => 'success', 'redirect' => base_url('bot-builder/'.$bot_id.'/editor')]);
    }

    /**
     * Promove um bloco Botões (modo quick) para Template Nativo reutilizável.
     */
    public function promote_to_native()
    {
        $blockId = (string) $this->request->getPost('block_id');
        $text = (string) $this->request->getPost('text');
        $title = (string) $this->request->getPost('title');
        $image = (string) $this->request->getPost('image');
        $optionsRaw = (string) $this->request->getPost('options');
        $existingIds = (string) $this->request->getPost('ids');

        if (!$blockId || !$optionsRaw) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Parâmetros obrigatórios: block_id, options']);
        }

        $teamId = get_team('id');

        $labels = array_map('trim', explode(',', $optionsRaw));
        $templateButtons = [];
        foreach ($labels as $idx => $label) {
            if ($label === '') continue;
            $templateButtons[] = [
                'index' => $idx,
                'quickReplyButton' => [
                    'displayText' => $label,
                    'id' => 'opt_' . $idx
                ]
            ];
        }

        if (empty($templateButtons)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Nenhum botão válido encontrado']);
        }

        $payload = [
            'templateButtons' => $templateButtons,
            'footer' => '',
            'title' => $title,
            'text' => $text,
            'caption' => $text,
            'image' => $image ? ['url' => $image] : null,
            'local_variables' => [],
            'meta_official' => [
                'enabled' => false,
                'base_name' => '',
                'category' => 'MARKETING',
                'languages' => '',
                'header_format' => 'TEXT',
                'body_example' => ''
            ]
        ];

        $name = substr(preg_replace('/[^a-zA-Z0-9_À-ÿ ]/', '', trim($text)), 0, 40) ?: 'Botao Builder';
        $name = 'BB_' . $name;

        $ids = $existingIds ?: ('bb_promoted_' . uniqid());

        // Upsert: update only if existing ids were provided (meaning user chose to update)
        $existing = null;
        if ($existingIds) {
            $existing = db_get('id, ids, name', TB_WHATSAPP_TEMPLATE, ['ids' => $ids, 'team_id' => $teamId]);
        }
        if ($existing) {
            db_update(TB_WHATSAPP_TEMPLATE, [
                'data' => json_encode($payload),
                'name' => $existing->name,
                'changed' => time()
            ], ['id' => $existing->id]);
            $name = $existing->name;
        } else {
            // Check name conflict and disambiguate
            $nameConflict = db_get('id', TB_WHATSAPP_TEMPLATE, ['team_id' => $teamId, 'name' => $name, 'type' => 2]);
            if ($nameConflict) {
                $name .= '_' . substr($blockId, 0, 6);
            }
            $data = [
                'ids' => $ids,
                'team_id' => $teamId,
                'type' => 2,
                'name' => $name,
                'data' => json_encode($payload),
                'changed' => time(),
                'created' => time(),
            ];
            db_insert(TB_WHATSAPP_TEMPLATE, $data);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'ids' => $ids,
            'name' => $name,
            'message' => $existing ? 'Template nativo atualizado com sucesso!' : 'Template nativo criado! Agora pode ser usado em outros fluxos.'
        ]);
    }

    // ===================== RUNTIME EXECUTOR =====================

    public function webhook()
    {
        helper('whatsapp');

        $json = file_get_contents('php://input');
        
        // Debug log
        $logFile = WRITEPATH . 'bot_builder_webhook.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " | Webhook received | Raw: " . substr($json, 0, 500) . "\n", FILE_APPEND);
        
        if(!$json) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Payload não informado']);
        }

        $data = json_decode($json, true);
        if(!isset($data['instance_id']) || !isset($data['data']['messages'])) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " | Invalid payload structure\n", FILE_APPEND);
            return $this->response->setJSON(['status' => 'error', 'message' => 'Payload inválido']);
        }

        file_put_contents($logFile, date('Y-m-d H:i:s') . " | Processing instance: " . $data['instance_id'] . " | Messages: " . count($data['data']['messages']) . "\n", FILE_APPEND);

        $handled_count = $this->process_webhook($data);
        
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Webhook processado',
            'handled' => $handled_count > 0,
            'handled_count' => $handled_count,
        ]);
    }

    private function process_webhook($data)
    {
        $logFile = WRITEPATH . 'bot_builder_webhook.log';
        $wa_instance_id = $data['instance_id']; // This is sp_accounts.token (waserver uses this)
        $messages = $data['data']['messages'];

        // Resolve token to account id (sp_bb_integrations uses sp_accounts.id)
        $account = $this->model->db->table('sp_accounts')
            ->where('token', $wa_instance_id)
            ->get()->getRow();
        $account_id = $account ? $account->id : null;

        file_put_contents($logFile, date('Y-m-d H:i:s') . " | Token: {$wa_instance_id} → Account ID: " . ($account_id ?: 'NOT FOUND') . "\n", FILE_APPEND);

        // Use token for sending WhatsApp messages, account_id for DB lookups
        $instance_id_for_send = $wa_instance_id;
        $instance_id_for_lookup = $account_id;

        if(!$account_id) {
            // Fallback: try using the raw instance_id as-is
            $instance_id_for_lookup = $wa_instance_id;
        }

        $handled_count = 0;

        foreach ($messages as $message) {
            if (isset($message['key']['fromMe']) && $message['key']['fromMe']) continue;

            $identity = $this->resolve_message_identity($message);
            $phone = $identity['session_phone'];
            $reply_phone = $identity['reply_phone'];
            $text = $this->extract_text($message);
            $type = $this->get_message_type($message);

            // ★ Also extract the raw button ID for fallback matching
            $button_id = $this->extract_button_id($message);

            file_put_contents($logFile, date('Y-m-d H:i:s') . " | Phone: {$phone} | Reply: {$reply_phone} | Text: {$text} | Type: {$type}" . ($button_id ? " | ButtonId: {$button_id}" : '') . "\n", FILE_APPEND);

            // ==================== WOOCOMMERCE SHOP BOT HOOK ====================
            if ($account && isset($account->team_id)) {
                try {
                    // Check if message is for WooCommerce Shop Bot
                    $handled = \Core\Whatsapp_woocommerce\Controllers\Whatsapp_woocommerce::handle_incoming_message(
                        $phone,
                        $text,
                        $instance_id_for_send,
                        $account->team_id
                    );
                    if ($handled) {
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " | 🛍️ Handled by WooCommerce Bot\n", FILE_APPEND);
                        $handled_count++;
                        continue; // Skip normal bot flow
                    }
                } catch (\Throwable $e) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " | ❌ WooBot Error: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
            // ===================================================================

            $session = $this->model->get_session($phone, $instance_id_for_lookup);
            if(!$session && $reply_phone !== $phone) {
                $session = $this->model->get_session($reply_phone, $instance_id_for_lookup);
                if($session) {
                    $this->model->update_session($session->id, ['phone' => $phone]);
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " | Sessão legada promovida para identidade canônica: {$phone}\n", FILE_APPEND);
                }
            }

            if ($session && empty($session->current_block_id)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " | ⚠️ Invalid active session #{$session->id} with empty current_block_id. Closing and trying keyword again.\n", FILE_APPEND);
                $this->model->end_sessions_for_phone($phone, $session->bot_id);
                $session = null;
            }

            if ($session) {
                $session->reply_phone = $reply_phone;
                // ★ Check stop keyword BEFORE processing the flow
                $stop_matched = $this->model->check_stop_keyword($text, $session->bot_id);
                if($stop_matched) {
                    // End the session and notify user
                    $bot = $this->model->get_bot($session->bot_id);
                    $this->model->end_sessions_for_phone($phone, $session->bot_id);
                    $stop_msg = 'Bot interrompido. Você pode iniciar novamente enviando a palavra-chave de ativação.';
                    if($bot && !empty($bot->enable_keyword)) {
                        $stop_msg = 'Bot interrompido. Envie "' . trim(explode(',', $bot->enable_keyword)[0]) . '" para iniciar novamente.';
                    } elseif($bot && !empty($bot->trigger_keywords)) {
                        $stop_msg = 'Bot interrompido. Envie "' . trim(explode(',', $bot->trigger_keywords)[0]) . '" para iniciar novamente.';
                    }
                    $this->send_whatsapp($instance_id_for_send, $reply_phone, 'text', ['text' => $stop_msg]);
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " | ⛔ Stop keyword matched for bot #{$session->bot_id} – session ended\n", FILE_APPEND);
                    $handled_count++;
                    continue;
                }
                file_put_contents($logFile, date('Y-m-d H:i:s') . " | Found active session #{$session->id} for bot #{$session->bot_id} | Block: {$session->current_block_id}\n", FILE_APPEND);
                $session->canonical_phone = $phone;
                $session->phone = $reply_phone;
                $this->run_flow($session, $text, $type, $instance_id_for_send, false, $button_id);
                $handled_count++;
            } else {
                // 1. Try keyword trigger first
                $bot = $this->model->find_bot_by_trigger($text, $instance_id_for_lookup, $phone);
                if ($bot) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " | ✅ Keyword matched! Bot #{$bot->id} ({$bot->name}) | Start: {$bot->start_block_id}\n", FILE_APPEND);
                    $session_id = $this->model->create_session($bot->id, $phone, $instance_id_for_lookup);
                    $session = (object)[
                        'id' => $session_id,
                        'bot_id' => $bot->id,
                        'phone' => $reply_phone,
                        'reply_phone' => $reply_phone,
                        'canonical_phone' => $phone,
                        'context' => '{}',
                        'current_block_id' => $bot->start_block_id
                    ];
                    $this->run_flow($session, $text, $type, $instance_id_for_send, true);
                    $handled_count++;
                    continue;
                }

                file_put_contents($logFile, date('Y-m-d H:i:s') . " | No keyword match for '{$text}' with lookup ID: {$instance_id_for_lookup}\n", FILE_APPEND);

                // 2. Try Command triggers (blocks of type 'command' across all bots)
                $command_match = $this->find_command_trigger($text, $instance_id_for_lookup);
                if ($command_match) {
                    $session_id = $this->model->create_session($command_match['bot_id'], $phone, $instance_id_for_lookup);
                    $session = (object)[
                        'id' => $session_id,
                        'bot_id' => $command_match['bot_id'],
                        'phone' => $reply_phone,
                        'reply_phone' => $reply_phone,
                        'context' => '{}',
                        'current_block_id' => $command_match['block_id']
                    ];
                    $this->run_flow($session, $text, $type, $instance_id_for_send, true);
                    $handled_count++;
                    continue;
                }

                // 3. Try Reply triggers (blocks of type 'reply' across all bots)
                $reply_match = $this->find_reply_trigger($text, $instance_id_for_lookup);
                if ($reply_match) {
                    $session_id = $this->model->create_session($reply_match['bot_id'], $phone, $instance_id_for_lookup);
                    $this->model->update_session($session_id, ['current_block_id' => $reply_match['block_id']]);
                    $session = (object)[
                        'id' => $session_id,
                        'bot_id' => $reply_match['bot_id'],
                        'phone' => $reply_phone,
                        'reply_phone' => $reply_phone,
                        'context' => '{}',
                        'current_block_id' => $reply_match['block_id']
                    ];
                    $this->run_flow($session, $text, $type, $instance_id_for_send, true);
                    $handled_count++;
                }
            }
        }

        return $handled_count;
    }

    private function resolve_message_identity($message)
    {
        $remote = $message['key']['remoteJid'] ?? '';
        $context = $message['_automation_context'] ?? [];
        if(is_string($context)) {
            $decoded = json_decode($context, true);
            $context = is_array($decoded) ? $decoded : [];
        }

        $number_to_jid = function($value) {
            $value = trim((string)$value);
            if($value === '') return '';
            if(strpos($value, '@') !== false) return $value;
            return preg_match('/^[0-9]+$/', $value) ? $value . '@s.whatsapp.net' : $value;
        };

        $canonical = $context['canonicalJid'] ?? '';
        if($canonical === '') $canonical = $number_to_jid($context['canonicalId'] ?? '');
        if($canonical === '') $canonical = $number_to_jid($context['canonicalNumber'] ?? '');
        if($canonical === '' && !empty($message['_wa_id'])) $canonical = $number_to_jid($message['_wa_id']);
        if($canonical === '') $canonical = $remote;

        $reply = $context['replyJid'] ?? ($context['transportJid'] ?? $remote);
        if(!empty($message['official_api'])) {
            $reply = $context['cloudTo'] ?? ($message['_wa_id'] ?? $canonical);
        }

        $reply = trim((string)$reply);
        if($reply === '') $reply = $canonical;

        return [
            'session_phone' => trim((string)$canonical),
            'reply_phone' => $reply,
        ];
    }

    private function run_flow($session, $input, $input_type, $instance_id, $is_start = false, $button_id = null)
    {
        $bot_id = $session->bot_id;
        $bot = $this->model->get_bot($bot_id);
        $team_id = $bot->team_id ?? get_team('id');
        $context = json_decode($session->context ?? '{}', true);
        $blocks = $this->model->get_blocks($bot_id);
        $edges = $this->model->get_edges($bot_id);

        $findBlock = function($uid) use ($blocks) {
            foreach($blocks as $b) if($b->id === $uid) return $b;
            return null;
        };

        $current_block_id = $session->current_block_id;

        if ($is_start && !$current_block_id) {
            foreach($blocks as $b) {
                if($b->type === 'start') { $current_block_id = $b->id; break; }
            }
        }

        $current_block = $findBlock($current_block_id);
        if(!$current_block) return;

        // Handle input from user
        if (!$is_start) {
            $prev_type = $current_block->type;
            $data = json_decode($current_block->data);
            $next_id = null;

            // All input types that capture user response into a variable
            $input_types = ['input','input_text','input_number','input_email','input_website','input_date','input_time','input_phone','rating','file_upload'];
            if (in_array($prev_type, $input_types)) {
                // ——— Validation ———
                $validation = $this->validate_input($prev_type, $data, $input);
                if(!$validation['valid']) {
                    $retry_msg = !empty($data->retry_message) ? $this->replace_vars($data->retry_message, $context) : $validation['message'];
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $retry_msg]);
                    // Stay on the same block — don't advance
                    return;
                }
                $varName = $data->variable ?? 'input';
                $context[$varName] = $input;
                $context['last_message'] = $input;
                $next_id = $this->find_next_node($edges, $current_block_id);
            } elseif ($prev_type === 'ai_reply' && (($data->mode ?? 'once') === 'continuous')) {
                $context['last_message'] = $input;
                $prompt = $this->replace_vars($data->prompt ?? '{{last_message}}', $context);
                $reply = $this->call_ai_service($prompt, $context, $data, $data->provider ?? 'auto', $team_id);
                $context[$data->variable ?? 'ai_reply'] = $reply;
                $context['ai_history'] = array_slice(array_merge($context['ai_history'] ?? [], [['user' => $input, 'assistant' => $reply]]), -6);
                $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $reply]);
                $this->save_state($session->id, $current_block_id, $context);
                return;
            } elseif (in_array($prev_type, ['buttons', 'list', 'pic_choice', 'cards'])) {
                $context['last_selection'] = $input;
                if(!empty($data->variable)) $context[$data->variable] = $input;

                // ★ BUTTON ROUTING: Multi-strategy edge matching
                // Build a map of button labels for this node (for reverse-lookup)
                $btn_labels = [];
                $btn_ids = [];
                $card_title_by_reply = [];
                if($prev_type === 'buttons') {
                    $btn_labels = array_map('trim', explode(',', $data->options ?? ''));
                } elseif($prev_type === 'pic_choice') {
                    foreach(explode(',', $data->choices ?? '') as $c) {
                        $parts = explode('|', trim($c));
                        $btn_labels[] = trim($parts[0]);
                    }
                } elseif($prev_type === 'cards') {
                    foreach(explode("\n", $data->cards_data ?? '') as $idx => $line) {
                        $parts = array_map('trim', explode('|', trim($line)));
                        $title = $parts[0] ?? '';
                        $label = $parts[3] ?? $title;
                        if(!empty($label)) {
                            $btn_labels[] = $label;
                            $btn_ids[] = 'bb_card_' . ($idx + 1);
                            if($title) {
                                $card_title_by_reply[strtolower($label)] = $title;
                                $card_title_by_reply[strtolower('bb_card_' . ($idx + 1))] = $title;
                            }
                        }
                    }
                } elseif($prev_type === 'list') {
                    foreach(explode("\n", $data->sections ?? '') as $line) {
                        $parts = explode('|', $line);
                        if(isset($parts[1])) {
                            foreach(explode(',', $parts[1]) as $opt) {
                                if(trim($opt)) $btn_labels[] = trim($opt);
                            }
                        }
                    }
                }
                $btn_labels = array_filter($btn_labels);

                // Collect all edges from this button node
                $btn_edges = [];
                $default_edge_id = null;
                foreach($edges as $e) {
                    if($e->from_block_id == $current_block_id) {
                        if(!empty($e->condition_value) && $e->condition_value !== 'default') {
                            $btn_edges[] = $e;
                        } else {
                            $default_edge_id = $e->to_block_id; // fallback/default edge
                        }
                    }
                }

                $input_lower = strtolower(trim($input));
                $btn_id_lower = $button_id ? strtolower(trim($button_id)) : '';
                $mapped_card_title = $card_title_by_reply[$input_lower] ?? ($btn_id_lower ? ($card_title_by_reply[$btn_id_lower] ?? '') : '');
                $mapped_card_title_lower = $mapped_card_title ? strtolower(trim($mapped_card_title)) : '';

                // Strategy 1: Exact match on condition_value vs display text
                foreach($btn_edges as $e) {
                    $cv = strtolower(trim($e->condition_value));
                    if($cv === $input_lower || ($mapped_card_title_lower && $cv === $mapped_card_title_lower)) {
                        $next_id = $e->to_block_id;
                        break;
                    }
                }

                // Strategy 2: Match button ID to label, then label to edge
                if(!$next_id && $btn_id_lower) {
                    // button_id might be the label slug (e.g., 'pricing' or '💰 Pricing')
                    foreach($btn_edges as $e) {
                        $cv = strtolower(trim($e->condition_value));
                        if($cv === $btn_id_lower) {
                            $next_id = $e->to_block_id;
                            break;
                        }
                    }
                }

                // Strategy 3: Carousel/internal button ID matching (e.g., bb_card_1)
                if(!$next_id && (!empty($btn_ids))) {
                    $reply_key = $btn_id_lower ?: $input_lower;
                    foreach($btn_ids as $idx => $internal_id) {
                        if(strtolower($internal_id) === $reply_key && isset($btn_edges[$idx])) {
                            $next_id = $btn_edges[$idx]->to_block_id;
                            break;
                        }
                    }
                }

                // Strategy 4: Numeric index matching (user replied "1", "2", etc.)
                if(!$next_id && is_numeric(trim($input))) {
                    $idx = intval(trim($input)) - 1;
                    if($idx >= 0 && $idx < count($btn_labels) && isset($btn_edges[$idx])) {
                        $next_id = $btn_edges[$idx]->to_block_id;
                    }
                }

                // Strategy 4: Partial/contains match on condition_value
                if(!$next_id) {
                    foreach($btn_edges as $e) {
                        $cv = strtolower(trim($e->condition_value));
                        if($cv && (strpos($input_lower, $cv) !== false || strpos($cv, $input_lower) !== false)) {
                            $next_id = $e->to_block_id;
                            break;
                        }
                    }
                }

                // Strategy 5: Fallback to default/first edge
                if(!$next_id) {
                    $next_id = $default_edge_id ?: $this->find_next_node($edges, $current_block_id);
                }
            } elseif ($prev_type == 'payment') {
                $isPaid = in_array(strtolower(trim($input)), ['paid', 'yes', 'done', 'confirmed', 'completed', 'pago', 'sim', 'feito', 'confirmado', 'concluido', 'concluído']);
                $context[$data->variable ?? 'payment_status'] = $isPaid ? 'paid' : 'failed';
                if($isPaid) {
                    $successMsg = $this->replace_vars($data->success_message ?? 'Pagamento recebido! Obrigado.', $context);
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => "✅ $successMsg"]);
                } else {
                    $failMsg = $this->replace_vars($data->failure_message ?? 'Pagamento não confirmado.', $context);
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => "❌ $failMsg"]);
                }
                $next_id = $this->find_next_node($edges, $current_block_id);
            }

            if($next_id) {
                $current_block_id = $next_id;
                $current_block = $findBlock($current_block_id);
            } else {
                return;
            }
        }

        // Execute chain
        $steps = 0;
        while($current_block && $steps < 50) {
            $steps++;
            $bType = $current_block->type;
            $bData = json_decode($current_block->data);
            $next_id = null;

            switch($bType) {
                case 'start':
                case 'command':
                case 'reply':
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'text':
                    $msg = $this->replace_vars($bData->text ?? '', $context);
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $msg]);
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'image':
                    $url = $this->replace_vars($bData->url ?? '', $context);
                    $caption = $this->replace_vars($bData->caption ?? '', $context);
                    $this->send_whatsapp($instance_id, $session->phone, 'image', ['url' => $url, 'caption' => $caption]);
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'video':
                    $url = $this->replace_vars($bData->url ?? '', $context);
                    $caption = $this->replace_vars($bData->caption ?? '', $context);
                    $this->send_whatsapp($instance_id, $session->phone, 'video', ['url' => $url, 'caption' => $caption]);
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'audio':
                    $url = $this->replace_vars($bData->url ?? '', $context);
                    $this->send_whatsapp($instance_id, $session->phone, 'audio', ['url' => $url]);
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'embed':
                    $url = $this->replace_vars($bData->url ?? '', $context);
                    $title = $this->replace_vars($bData->title ?? '', $context);
                    $desc = $this->replace_vars($bData->description ?? '', $context);
                    $msg = ($title ? "*$title*\n" : '') . ($desc ? "$desc\n" : '') . $url;
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $msg]);
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'buttons':
                    if(($bData->button_mode ?? '') === 'native' && !empty($bData->template_ids)) {
                        $payload = $this->get_native_template_payload($team_id, $bData->template_ids, 2);
                        if(!empty($payload) && !empty($payload['_template_id'])) {
                            $this->send_whatsapp($instance_id, $session->phone, 'buttons', [
                                '_template_id' => (int)$payload['_template_id']
                            ]);
                            $this->save_state($session->id, $current_block->id, $context);
                            return;
                        }
                    }
                    $msg = $this->replace_vars($bData->text ?? '', $context);
                    $btns = $this->parse_buttons($bData->options ?? '');
                    $templateButtons = $this->build_template_buttons_from_parsed($btns);
                    $btnTitle = $bData->title ?? '';
                    $btnImage = $bData->image ?? '';
                    $runtime_template_id = $this->save_quick_buttons_runtime_template($team_id, $current_block->id, $msg, $templateButtons, $btnTitle, $btnImage);
                    if($runtime_template_id) {
                        $this->send_whatsapp($instance_id, $session->phone, 'buttons', [
                            '_template_id' => (int)$runtime_template_id
                        ]);
                    } else {
                        $this->send_whatsapp($instance_id, $session->phone, 'buttons', [
                            'text' => $msg,
                            'title' => '',
                            'footer' => '',
                            'buttons' => $btns,
                            'templateButtons' => $templateButtons
                        ]);
                    }
                    $this->save_state($session->id, $current_block->id, $context);
                    return;

                case 'list':
                    if(!empty($bData->template_ids)) {
                        $payload = $this->get_native_template_payload($team_id, $bData->template_ids, 1);
                        if(!empty($payload)) {
                            $payload = $this->replace_vars_recursive($this->normalize_native_list_payload($payload), $context);
                            $this->send_whatsapp($instance_id, $session->phone, 'list', $payload);
                            $this->save_state($session->id, $current_block->id, $context);
                            return;
                        }
                    }
                    $msg = $this->replace_vars($bData->text ?? '', $context);
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $msg . "\n📋 Responda com a opção desejada"]);
                    $this->save_state($session->id, $current_block->id, $context);
                    return;

                case 'input':
                case 'input_text':
                case 'input_email':
                case 'input_website':
                case 'input_phone':
                    $msg = $this->replace_vars($bData->question ?? '', $context);
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $msg]);
                    $this->save_state($session->id, $current_block->id, $context);
                    return;

                case 'input_number':
                    $msg = $this->replace_vars($bData->question ?? '', $context);
                    $range_hint = '';
                    if(!empty($bData->min) || !empty($bData->max)) {
                        $range_hint = "\n📊 Intervalo: " . ($bData->min ?: '∞') . ' – ' . ($bData->max ?: '∞');
                    }
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $msg . $range_hint]);
                    $this->save_state($session->id, $current_block->id, $context);
                    return;

                case 'input_date':
                    $msg = $this->replace_vars($bData->question ?? '', $context);
                    $format = $bData->format ?? 'YYYY-MM-DD';
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $msg . "\n📅 Formato: $format"]);
                    $this->save_state($session->id, $current_block->id, $context);
                    return;

                case 'input_time':
                    $msg = $this->replace_vars($bData->question ?? '', $context);
                    $format = $bData->format ?? 'HH:mm';
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $msg . "\n🕐 Formato: $format"]);
                    $this->save_state($session->id, $current_block->id, $context);
                    return;

                case 'pic_choice':
                    $msg = $this->replace_vars($bData->question ?? '', $context);
                    $choices = explode(',', $bData->choices ?? '');
                    $choice_text = '';
                    foreach($choices as $i => $c) {
                        $parts = explode('|', trim($c));
                        $label = $parts[0] ?? 'Opção ' . ($i+1);
                        $choice_text .= "\n" . ($i+1) . ". " . trim($label);
                    }
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $msg . $choice_text . "\n\n🖼️ Responda com a sua escolha"]);
                    $this->save_state($session->id, $current_block->id, $context);
                    return;

                case 'payment':
                    $amount = $bData->amount ?? '0';
                    $currency = $bData->currency ?? 'BRL';
                    $description = $this->replace_vars($bData->description ?? '', $context);
                    $payLink = $this->replace_vars($bData->payment_link ?? '', $context);
                    $msg = "💳 Solicitação de pagamento\n\n*Valor:* $currency $amount\n*Descrição:* $description";
                    if(!empty($payLink)) {
                        $msg .= "\n\n🔗 Pague aqui: $payLink";
                    }
                    $msg .= "\n\nResponda 'pago' para confirmar.";
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $msg]);
                    $this->save_state($session->id, $current_block->id, $context);
                    return;

                case 'rating':
                    $msg = $this->replace_vars($bData->question ?? '', $context);
                    $max = intval($bData->max_stars ?? 5);
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $msg . "\n⭐ Responda com um número de 1 a $max"]);
                    $this->save_state($session->id, $current_block->id, $context);
                    return;

                case 'file_upload':
                    $msg = $this->replace_vars($bData->question ?? '', $context);
                    $allowed = $bData->allowed_types ?? 'any';
                    $maxSize = $bData->max_size ?? '10';
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $msg . "\n📎 Permitido: $allowed (máx.: {$maxSize}MB)"]);
                    $this->save_state($session->id, $current_block->id, $context);
                    return;

                case 'cards':
                    if(!empty($bData->template_ids)) {
                        $payload = $this->get_native_template_payload($team_id, $bData->template_ids, 5);
                        if(!empty($payload)) {
                            $payload = $this->replace_vars_recursive($this->normalize_native_carousel_payload($payload), $context);
                            file_put_contents(WRITEPATH . 'bot_builder_carousel_trace.log', date('Y-m-d H:i:s') . ' | NATIVE_CAROUSEL_SEND | ' . json_encode([
                                'session_id' => $session->id ?? null,
                                'bot_id' => $session->bot_id ?? null,
                                'instance_id' => $instance_id,
                                'session_phone' => $session->phone ?? null,
                                'canonical_phone' => $session->canonical_phone ?? null,
                                'send_to' => $session->phone,
                                'cards_count' => count($payload['cards'] ?? []),
                                'first_card' => $payload['cards'][0] ?? null
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
                            $this->send_whatsapp($instance_id, $session->phone, 'carousel', $payload);
                            $this->save_state($session->id, $current_block->id, $context);
                            return;
                        }
                    }
                    $cards_raw = $bData->cards_data ?? '';
                    $lines = explode("\n", $cards_raw);
                    $cards = [];
                    foreach($lines as $i => $line) {
                        $parts = array_map('trim', explode('|', trim($line)));
                        if(empty($parts[0]) && empty($parts[1]) && empty($parts[2])) continue;
                        $title = $parts[0] ?? 'Card ' . ($i+1);
                        $desc = $parts[1] ?? '';
                        $image = $parts[2] ?? '';
                        $button = $parts[3] ?? $title;
                        $buttonId = 'bb_card_' . ($i + 1);
                        $card = [
                            'title' => $title,
                            'body' => $desc ?: ' ',
                            'buttons' => [[
                                'name' => 'quick_reply',
                                'buttonParamsJson' => json_encode([
                                    'id' => $buttonId,
                                    'display_text' => mb_substr($button, 0, 20),
                                    'disabled' => false
                                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                            ]]
                        ];
                        if($image) {
                            $card['image'] = ['url' => $image];
                            $card['media'] = $image;
                        }
                        $cards[] = $card;
                    }
                    if(!empty($cards)) {
                        $manual_payload = [
                            'text' => 'Escolha uma opção:',
                            'cards' => $cards
                        ];
                        file_put_contents(WRITEPATH . 'bot_builder_carousel_trace.log', date('Y-m-d H:i:s') . ' | MANUAL_CAROUSEL_SEND | ' . json_encode([
                            'session_id' => $session->id ?? null,
                            'bot_id' => $session->bot_id ?? null,
                            'instance_id' => $instance_id,
                            'session_phone' => $session->phone ?? null,
                            'canonical_phone' => $session->canonical_phone ?? null,
                            'send_to' => $session->phone,
                            'cards_count' => count($manual_payload['cards'] ?? []),
                            'first_card' => $manual_payload['cards'][0] ?? null
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
                        $this->send_whatsapp($instance_id, $session->phone, 'carousel', $manual_payload);
                    } else {
                        $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => 'Nenhum card configurado.']);
                    }
                    $this->save_state($session->id, $current_block->id, $context);
                    return;

                case 'condition':
                    $var = $bData->variable ?? 'input';
                    $val = $context[$var] ?? '';
                    $op = $bData->operator ?? '==';
                    $expected = $this->replace_vars($bData->expected ?? '', $context);

                    $result = $this->evaluate_condition($val, $op, $expected);
                    $handle = $result ? 'true' : 'false';
                    $next_id = $this->find_next_node($edges, $current_block->id, $handle);
                    if(!$next_id) $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'delay':
                    $seconds = intval($bData->seconds ?? 3);
                    if($seconds > 0 && $seconds <= 30) sleep($seconds);
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'ai_reply':
                    $prompt = $this->replace_vars($bData->prompt ?? '{{last_message}}', $context);
                    $reply = $this->call_ai_service($prompt, $context, $bData, $bData->provider ?? 'auto', $team_id);
                    $context[$bData->variable ?? 'ai_reply'] = $reply;
                    if(($bData->mode ?? 'once') === 'continuous') {
                        $context['ai_history'] = array_slice(array_merge($context['ai_history'] ?? [], [['user' => $prompt, 'assistant' => $reply]]), -6);
                    }
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $reply]);
                    if(($bData->mode ?? 'once') === 'continuous') {
                        $this->save_state($session->id, $current_block->id, $context);
                        return;
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'webhook':
                    $url = $this->replace_vars($bData->url ?? '', $context);
                    $method = $bData->method ?? 'POST';
                    $headers = json_decode($this->replace_vars($bData->headers ?? '{}', $context), true) ?: [];
                    $body = $this->replace_vars($bData->body ?? '{}', $context);
                    $varName = $bData->variable ?? 'webhook_response';

                    $response = $this->execute_webhook($url, $method, $headers, $body);
                    $context[$varName] = $response;
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'set_variable':
                    $varName = $bData->variable ?? '';
                    $value = $this->replace_vars($bData->value ?? '', $context);
                    if(!empty($varName)) $context[$varName] = $value;
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'jump':
                    $target = $bData->target_node ?? '';
                    if(!empty($target) && $findBlock($target)) {
                        $next_id = $target;
                    }
                    break;

                case 'script':
                    // Server-side PHP script execution (sandboxed)
                    $code = $bData->code ?? '';
                    $varName = $bData->variable ?? 'script_result';
                    $result = '';
                    if(!empty($code) && ($bData->language ?? 'javascript') === 'php') {
                        try {
                            // Extract simple variable assignments from the code
                            ob_start();
                            $ctx = $context;
                            // Safe eval with context
                            $fn = function($context) use ($code) {
                                extract($context);
                                return eval($code);
                            };
                            $result = $fn($context) ?? ob_get_clean();
                        } catch(\Throwable $e) {
                            $result = 'Script error: ' . $e->getMessage();
                        }
                    } else {
                        $result = 'JavaScript runs client-side only';
                    }
                    $context[$varName] = (string)$result;
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'ab_test':
                    $pct = intval($bData->variant_a_pct ?? 50);
                    $isA = (mt_rand(1, 100) <= $pct);
                    $handle = $isA ? 'variant_a' : 'variant_b';
                    $context['ab_test_variant'] = $handle;
                    $next_id = $this->find_next_node($edges, $current_block->id, $handle);
                    if(!$next_id) $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'typebot':
                    // Link to another bot — create a child session
                    $target_bot_id = $bData->bot_id ?? '';
                    if(!empty($target_bot_id)) {
                        $target_bot = $this->model->get_bot($target_bot_id);
                        if($target_bot) {
                            // Store parent session info for return
                            $context['_parent_session'] = $session->id;
                            $context['_parent_block'] = $current_block->id;
                            // Create child session
                            $child_session_id = $this->model->create_session($target_bot_id, $session->phone);
                            $child_session = (object)[
                                'id' => $child_session_id,
                                'bot_id' => $target_bot_id,
                                'phone' => $session->phone,
                                'context' => json_encode($context),
                                'current_block_id' => null
                            ];
                            $this->run_flow($child_session, '', 'text', $instance_id, true);
                            return;
                        }
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'redirect':
                    $url = $this->replace_vars($bData->url ?? '', $context);
                    $message = $this->replace_vars($bData->message ?? '', $context);
                    if(!empty($message)) {
                        $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => $message]);
                    }
                    if(($bData->send_url ?? 'true') === 'true' && !empty($url)) {
                        $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => "🔗 $url"]);
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'return':
                    // Return to parent bot if this was a typebot call
                    $return_val = $this->replace_vars($bData->return_value ?? '', $context);
                    $context['return_value'] = $return_val;
                    $parent_session_id = $context['_parent_session'] ?? null;
                    if($parent_session_id) {
                        // Mark this child session as done
                        $this->model->update_session($session->id, ['is_completed' => 1]);
                        // Resume parent
                        $parent_session = $this->model->get_session_by_id($parent_session_id);
                        if($parent_session) {
                            $parent_ctx = json_decode($parent_session->context ?? '{}', true);
                            $parent_ctx['typebot_return'] = $return_val;
                            $parent_block = $parent_ctx['_parent_block'] ?? $parent_session->current_block_id;
                            // Find next from the typebot block
                            $parent_edges = $this->model->get_edges($parent_session->bot_id);
                            $resume_id = $this->find_next_node($parent_edges, $parent_block);
                            if($resume_id) {
                                $parent_session->context = json_encode($parent_ctx);
                                $parent_session->current_block_id = $resume_id;
                                $this->save_state($parent_session->id, $resume_id, $parent_ctx);
                                $this->run_flow($parent_session, '', 'text', $instance_id, false);
                            }
                        }
                        return;
                    }
                    // If no parent, just end
                    $this->model->update_session($session->id, ['is_completed' => 1]);
                    return;

                case 'invalid':
                    $msg = $this->replace_vars($bData->message ?? 'Resposta inválida', $context);
                    $this->send_whatsapp($instance_id, $session->phone, 'text', ['text' => "⚠️ $msg"]);
                    if(($bData->retry ?? 'true') === 'true') {
                        // Stay on the same block — don't advance
                        $next_id = $this->find_next_node($edges, $current_block->id);
                        if(!$next_id) return; // dead end if no next
                    } else {
                        $next_id = $this->find_next_node($edges, $current_block->id);
                    }
                    break;

                // ==================== INTEGRATIONS ====================

                case 'intg_http':
                    $url     = $this->replace_vars($bData->url ?? '', $context);
                    $method  = strtoupper($bData->method ?? 'POST');
                    $headers = json_decode($this->replace_vars($bData->headers ?? '{}', $context), true) ?: [];
                    $body    = $this->replace_vars($bData->body ?? '{}', $context);
                    $timeout = intval($bData->timeout ?? 30);
                    $varName = $bData->variable ?? 'http_response';
                    try {
                        $ch = curl_init($url);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT        => $timeout,
                            CURLOPT_CUSTOMREQUEST  => $method,
                        ]);
                        $hArr = [];
                        foreach($headers as $k => $v) $hArr[] = "$k: $v";
                        if(!empty($hArr)) curl_setopt($ch, CURLOPT_HTTPHEADER, $hArr);
                        if(in_array($method, ['POST','PUT','PATCH'])) {
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                        }
                        $resp = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        $context[$varName] = $resp ?: '';
                        $context[$varName.'_code'] = (string)$httpCode;
                    } catch(\Throwable $e) {
                        $context[$varName] = 'Error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_email':
                    $to       = $this->replace_vars($bData->to ?? '', $context);
                    $subject  = $this->replace_vars($bData->subject ?? '', $context);
                    $bodyText = $this->replace_vars($bData->body_text ?? '', $context);
                    $fromName = $bData->from_name ?? 'Bot';
                    $varName  = $bData->variable ?? 'email_status';
                    try {
                        $mailHeaders = "From: $fromName <" . ($bData->smtp_user ?? 'noreply@bot.com') . ">\r\n";
                        $mailHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
                        $sent = @mail($to, $subject, $bodyText, $mailHeaders);
                        $context[$varName] = $sent ? 'sent' : 'failed';
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_zapier':
                case 'intg_make':
                case 'intg_pabbly':
                    $webhookUrl = $this->replace_vars($bData->webhook_url ?? '', $context);
                    $payload    = $this->replace_vars($bData->payload ?? '{}', $context);
                    $varName    = $bData->variable ?? ($current_block->type . '_result');
                    try {
                        $ch = curl_init($webhookUrl);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => $payload,
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                            CURLOPT_TIMEOUT        => 15,
                        ]);
                        $resp = curl_exec($ch);
                        curl_close($ch);
                        $context[$varName] = $resp ?: 'triggered';
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_openai':
                case 'intg_anthropic':
                case 'intg_mistral':
                case 'intg_groq':
                case 'intg_deepseek':
                case 'intg_perplexity':
                case 'intg_together':
                case 'intg_openrouter':
                    $provider = str_replace('intg_', '', $current_block->type);
                    $prompt = $this->replace_vars($bData->prompt ?? '{{last_message}}', $context);
                    $varName = $bData->variable ?? ($provider . '_reply');
                    $context[$varName] = $this->call_ai_service($prompt, $context, $bData, $provider, $team_id);
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_sheets':
                    $action  = $bData->action ?? 'append_row';
                    $sheetId = $bData->spreadsheet_id ?? '';
                    $sheet   = $bData->sheet_name ?? 'Sheet1';
                    $values  = $this->replace_vars($bData->values ?? '', $context);
                    $varName = $bData->variable ?? 'sheets_result';
                    // Google Sheets via Apps Script webhook or direct API
                    try {
                        $payload = json_encode([
                            'action'         => $action,
                            'spreadsheet_id' => $sheetId,
                            'sheet_name'     => $sheet,
                            'values'         => explode(',', $values)
                        ]);
                        // Use configured webhook URL or default storage
                        $webhookUrl = $context['sheets_webhook_url'] ?? '';
                        if(!empty($webhookUrl)) {
                            $ch = curl_init($webhookUrl);
                            curl_setopt_array($ch, [
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_POST           => true,
                                CURLOPT_POSTFIELDS     => $payload,
                                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                                CURLOPT_TIMEOUT        => 15,
                            ]);
                            $resp = curl_exec($ch);
                            curl_close($ch);
                            $context[$varName] = $resp ?: 'success';
                        } else {
                            $context[$varName] = 'success'; // no webhook configured, just log
                        }
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_analytics':
                    $trackingId  = $bData->tracking_id ?? '';
                    $eventName   = $this->replace_vars($bData->event_name ?? 'bot_event', $context);
                    $eventParams = json_decode($this->replace_vars($bData->event_params ?? '{}', $context), true) ?: [];
                    $varName     = $bData->variable ?? 'analytics_result';
                    try {
                        // GA4 Measurement Protocol
                        $payload = json_encode([
                            'client_id' => md5($session->phone),
                            'events'    => [['name' => $eventName, 'params' => $eventParams]]
                        ]);
                        $gaUrl = "https://www.google-analytics.com/mp/collect?measurement_id=$trackingId&api_secret=" . ($context['ga_api_secret'] ?? '');
                        $ch = curl_init($gaUrl);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => $payload,
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                            CURLOPT_TIMEOUT        => 10,
                        ]);
                        curl_exec($ch);
                        curl_close($ch);
                        $context[$varName] = 'tracked';
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_pixel':
                    $pixelId     = $bData->pixel_id ?? '';
                    $eventName   = $bData->event_name ?? 'Lead';
                    $eventParams = json_decode($this->replace_vars($bData->event_params ?? '{}', $context), true) ?: [];
                    $accessToken = $bData->access_token ?? '';
                    $varName     = $bData->variable ?? 'pixel_result';
                    try {
                        $payload = json_encode([
                            'data' => [[
                                'event_name'    => $eventName,
                                'event_time'    => time(),
                                'user_data'     => ['ph' => [hash('sha256', $session->phone)]],
                                'custom_data'   => $eventParams,
                                'action_source' => 'system_generated'
                            ]]
                        ]);
                        $ch = curl_init("https://graph.facebook.com/v18.0/$pixelId/events?access_token=$accessToken");
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => $payload,
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                            CURLOPT_TIMEOUT        => 10,
                        ]);
                        $resp = curl_exec($ch);
                        curl_close($ch);
                        $context[$varName] = $resp ?: 'fired';
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_chatwoot':
                    $apiUrl   = rtrim($bData->api_url ?? 'https://app.chatwoot.com/api/v1', '/');
                    $apiToken = $bData->api_token ?? '';
                    $accId    = $bData->account_id ?? '';
                    $action   = $bData->action ?? 'create_contact';
                    $payload  = $this->replace_vars($bData->payload ?? '{}', $context);
                    $varName  = $bData->variable ?? 'chatwoot_result';
                    try {
                        $endpoints = [
                            'create_contact'       => "/accounts/$accId/contacts",
                            'create_conversation'  => "/accounts/$accId/conversations",
                            'send_message'         => "/accounts/$accId/conversations",
                            'assign_agent'         => "/accounts/$accId/conversations"
                        ];
                        $ep = $apiUrl . ($endpoints[$action] ?? "/accounts/$accId/contacts");
                        $ch = curl_init($ep);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => $payload,
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', "api_access_token: $apiToken"],
                            CURLOPT_TIMEOUT        => 15,
                        ]);
                        $resp = curl_exec($ch);
                        curl_close($ch);
                        $context[$varName] = $resp ?: 'success';
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_elevenlabs':
                    $apiKey  = $bData->api_key ?? '';
                    $voiceId = $bData->voice_id ?? '';
                    $text    = $this->replace_vars($bData->text ?? '', $context);
                    $modelId = $bData->model_id ?? 'eleven_multilingual_v2';
                    $varName = $bData->variable ?? 'audio_url';
                    try {
                        $payload = json_encode(['text' => $text, 'model_id' => $modelId]);
                        $ch = curl_init("https://api.elevenlabs.io/v1/text-to-speech/$voiceId");
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => $payload,
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', "xi-api-key: $apiKey"],
                            CURLOPT_TIMEOUT        => 30,
                        ]);
                        $audioData = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        if($httpCode == 200 && $audioData) {
                            $fileName = 'audio_' . time() . '_' . mt_rand(1000,9999) . '.mp3';
                            $audioPath = FCPATH . 'uploads/audio/' . $fileName;
                            @mkdir(dirname($audioPath), 0777, true);
                            file_put_contents($audioPath, $audioData);
                            $context[$varName] = base_url('uploads/audio/' . $fileName);
                            // Optionally send as audio message
                            $this->send_whatsapp($instance_id, $session->phone, 'audio', ['url' => $context[$varName]]);
                        } else {
                            $context[$varName] = 'error: HTTP ' . $httpCode;
                        }
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_calcom':
                    $apiKey      = $bData->api_key ?? '';
                    $eventTypeId = $bData->event_type_id ?? '';
                    $action      = $bData->action ?? 'get_availability';
                    $date        = $this->replace_vars($bData->date ?? '', $context);
                    $varName     = $bData->variable ?? 'calcom_result';
                    try {
                        $baseUrl = 'https://api.cal.com/v1';
                        if($action === 'get_availability') {
                            $ep = "$baseUrl/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&dateFrom=$date";
                            $ch = curl_init($ep);
                            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
                        } else {
                            $ep = "$baseUrl/bookings?apiKey=$apiKey";
                            $ch = curl_init($ep);
                            curl_setopt_array($ch, [
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_POST           => true,
                                CURLOPT_POSTFIELDS     => json_encode(['eventTypeId' => (int)$eventTypeId]),
                                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                                CURLOPT_TIMEOUT        => 15,
                            ]);
                        }
                        $resp = curl_exec($ch);
                        curl_close($ch);
                        $context[$varName] = $resp ?: '{}';
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_chatnode':
                    $apiKey  = $bData->api_key ?? '';
                    $botId   = $bData->bot_id ?? '';
                    $query   = $this->replace_vars($bData->query ?? '', $context);
                    $varName = $bData->variable ?? 'chatnode_reply';
                    try {
                        $payload = json_encode(['query' => $query, 'bot_id' => $botId]);
                        $ch = curl_init('https://api.chatnode.ai/v1/chat');
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => $payload,
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', "Authorization: Bearer $apiKey"],
                            CURLOPT_TIMEOUT        => 30,
                        ]);
                        $resp = json_decode(curl_exec($ch), true);
                        curl_close($ch);
                        $context[$varName] = $resp['answer'] ?? $resp['response'] ?? json_encode($resp);
                    } catch(\Throwable $e) {
                        $context[$varName] = 'Error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_qrcode':
                    $data    = $this->replace_vars($bData->data ?? '', $context);
                    $size    = $bData->size ?? '300';
                    $varName = $bData->variable ?? 'qr_url';
                    $qrUrl   = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
                    $context[$varName] = $qrUrl;
                    // Send QR as image
                    $this->send_whatsapp($instance_id, $session->phone, 'image', ['url' => $qrUrl, 'caption' => 'QR Code']);
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_dify':
                    $apiUrl = rtrim($bData->api_url ?? 'https://api.dify.ai/v1', '/');
                    $apiKey = $bData->api_key ?? '';
                    $query  = $this->replace_vars($bData->query ?? '', $context);
                    $convId = $bData->conversation_id ?? '';
                    $varName = $bData->variable ?? 'dify_reply';
                    try {
                        $body = ['inputs' => [], 'query' => $query, 'response_mode' => 'blocking', 'user' => md5($session->phone)];
                        if(!empty($convId)) $body['conversation_id'] = $convId;
                        $ch = curl_init("$apiUrl/chat-messages");
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => json_encode($body),
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', "Authorization: Bearer $apiKey"],
                            CURLOPT_TIMEOUT        => 60,
                        ]);
                        $resp = json_decode(curl_exec($ch), true);
                        curl_close($ch);
                        $context[$varName] = $resp['answer'] ?? json_encode($resp);
                    } catch(\Throwable $e) {
                        $context[$varName] = 'Error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_nocodb':
                    $apiUrl   = rtrim($bData->api_url ?? '', '/');
                    $apiToken = $bData->api_token ?? '';
                    $tableId  = $bData->table_id ?? '';
                    $action   = $bData->action ?? 'list';
                    $data     = $this->replace_vars($bData->data ?? '{}', $context);
                    $varName  = $bData->variable ?? 'nocodb_result';
                    try {
                        $method = 'GET';
                        $ep = "$apiUrl/db/data/v1/$tableId";
                        if(in_array($action, ['create'])) $method = 'POST';
                        if(in_array($action, ['update'])) $method = 'PATCH';
                        if(in_array($action, ['delete'])) $method = 'DELETE';
                        $ch = curl_init($ep);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_CUSTOMREQUEST  => $method,
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', "xc-token: $apiToken"],
                            CURLOPT_TIMEOUT        => 15,
                        ]);
                        if($method !== 'GET') curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                        $resp = curl_exec($ch);
                        curl_close($ch);
                        $context[$varName] = $resp ?: '{}';
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_segment':
                    $writeKey   = $bData->write_key ?? '';
                    $eventName  = $this->replace_vars($bData->event_name ?? 'Bot Interaction', $context);
                    $userId     = $this->replace_vars($bData->user_id ?? '', $context);
                    $properties = json_decode($this->replace_vars($bData->properties ?? '{}', $context), true) ?: [];
                    $varName    = $bData->variable ?? 'segment_result';
                    try {
                        $payload = json_encode([
                            'userId'     => $userId,
                            'event'      => $eventName,
                            'properties' => $properties,
                            'timestamp'  => date('c')
                        ]);
                        $ch = curl_init('https://api.segment.io/v1/track');
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => $payload,
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                            CURLOPT_USERPWD        => "$writeKey:",
                            CURLOPT_TIMEOUT        => 10,
                        ]);
                        curl_exec($ch);
                        curl_close($ch);
                        $context[$varName] = 'tracked';
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_zendesk':
                    $subdomain = $bData->subdomain ?? '';
                    $email     = $bData->email ?? '';
                    $apiToken  = $bData->api_token ?? '';
                    $action    = $bData->action ?? 'create_ticket';
                    $subject   = $this->replace_vars($bData->subject ?? '', $context);
                    $bodyText  = $this->replace_vars($bData->body_text ?? '', $context);
                    $varName   = $bData->variable ?? 'zendesk_result';
                    try {
                        $payload = json_encode(['ticket' => ['subject' => $subject, 'description' => $bodyText]]);
                        $ch = curl_init("https://$subdomain.zendesk.com/api/v2/tickets.json");
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => $payload,
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                            CURLOPT_USERPWD        => "$email/token:$apiToken",
                            CURLOPT_TIMEOUT        => 15,
                        ]);
                        $resp = curl_exec($ch);
                        curl_close($ch);
                        $context[$varName] = $resp ?: '{}';
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_posthog':
                    $apiKey     = $bData->api_key ?? '';
                    $host       = rtrim($bData->host ?? 'https://app.posthog.com', '/');
                    $eventName  = $this->replace_vars($bData->event_name ?? 'bot_event', $context);
                    $distinctId = $this->replace_vars($bData->distinct_id ?? '', $context);
                    $properties = json_decode($this->replace_vars($bData->properties ?? '{}', $context), true) ?: [];
                    $varName    = $bData->variable ?? 'posthog_result';
                    try {
                        $payload = json_encode([
                            'api_key'     => $apiKey,
                            'event'       => $eventName,
                            'distinct_id' => $distinctId,
                            'properties'  => $properties,
                            'timestamp'   => date('c')
                        ]);
                        $ch = curl_init("$host/capture/");
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => $payload,
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                            CURLOPT_TIMEOUT        => 10,
                        ]);
                        curl_exec($ch);
                        curl_close($ch);
                        $context[$varName] = 'captured';
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_blink':
                    $apiKey  = $bData->api_key ?? '';
                    $action  = $bData->action ?? 'send_notification';
                    $payload = $this->replace_vars($bData->payload ?? '{}', $context);
                    $varName = $bData->variable ?? 'blink_result';
                    try {
                        $ch = curl_init('https://api.joinblink.com/v1/' . str_replace('_', '-', $action));
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => $payload,
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', "Authorization: Bearer $apiKey"],
                            CURLOPT_TIMEOUT        => 15,
                        ]);
                        $resp = curl_exec($ch);
                        curl_close($ch);
                        $context[$varName] = $resp ?: 'success';
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_gmail':
                    $to       = $this->replace_vars($bData->to ?? '', $context);
                    $subject  = $this->replace_vars($bData->subject ?? '', $context);
                    $bodyText = $this->replace_vars($bData->body_text ?? '', $context);
                    $fromName = $bData->from_name ?? 'Bot';
                    $varName  = $bData->variable ?? 'gmail_status';
                    // Gmail via OAuth2
                    $oauthToken = $bData->oauth_token ?? '';
                    try {
                        if(!empty($oauthToken)) {
                            $raw = "From: $fromName\r\nTo: $to\r\nSubject: $subject\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n$bodyText";
                            $encoded = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
                            $ch = curl_init('https://gmail.googleapis.com/gmail/v1/users/me/messages/send');
                            curl_setopt_array($ch, [
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_POST           => true,
                                CURLOPT_POSTFIELDS     => json_encode(['raw' => $encoded]),
                                CURLOPT_HTTPHEADER     => ['Content-Type: application/json', "Authorization: Bearer $oauthToken"],
                                CURLOPT_TIMEOUT        => 15,
                            ]);
                            $resp = curl_exec($ch);
                            curl_close($ch);
                            $context[$varName] = 'sent';
                        } else {
                            // Fallback to mail()
                            $headers = "From: $fromName <noreply@bot.com>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
                            @mail($to, $subject, $bodyText, $headers);
                            $context[$varName] = 'sent_fallback';
                        }
                    } catch(\Throwable $e) {
                        $context[$varName] = 'error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'intg_woocommerce':
                    $wooAction = $bData->woo_action ?? 'get_order';
                    $varName   = $bData->variable ?? 'woo_result';
                    try {
                        $wooModel = new \Core\Whatsapp_woocommerce\Models\Whatsapp_woocommerceModel();
                        $wooSettings = $wooModel->get_settings($this->model->get_team_id_for_session($session));
                        if (!$wooSettings || empty($wooSettings->woo_url)) {
                            $context[$varName] = 'Error: WooCommerce not configured';
                        } else {
                            switch ($wooAction) {
                                case 'get_order':
                                    $orderId = $this->replace_vars($bData->order_id ?? '', $context);
                                    $orderId = intval(trim($orderId));
                                    if ($orderId <= 0) {
                                        $context[$varName] = 'Erro: ID do pedido inválido';
                                        $context[$varName.'_error'] = 'true';
                                    } else {
                                        $result = $wooModel->get_order($wooSettings, $orderId);
                                        if ($result['error']) {
                                            $context[$varName] = 'Error: ' . $result['message'];
                                            $context[$varName.'_error'] = 'true';
                                        } else {
                                            $context[$varName] = $wooModel->format_order_details($result['data']);
                                            $context[$varName.'_status'] = $result['data']['status'] ?? '';
                                            $context[$varName.'_total'] = ($result['data']['currency'] ?? '') . ' ' . ($result['data']['total'] ?? '0');
                                            $context[$varName.'_error'] = 'false';
                                        }
                                    }
                                    break;
                                case 'search_products':
                                    $query = $this->replace_vars($bData->search_query ?? '', $context);
                                    $result = $wooModel->search_products($wooSettings, $query, 5);
                                    if ($result['error']) {
                                        $context[$varName] = 'Error: ' . $result['message'];
                                    } else {
                                        $products = $result['data'];
                                        $msg = '';
                                        foreach ($products as $i => $p) {
                                            $msg .= ($i+1) . '. *' . ($p['name'] ?? 'Product') . '* — ' . ($p['price'] ?? '0') . "\n";
                                        }
                                        $context[$varName] = $msg ?: 'No products found';
                                        $context[$varName.'_count'] = (string)count($products);
                                    }
                                    break;
                                case 'get_categories':
                                    $result = $wooModel->get_categories($wooSettings);
                                    if ($result['error']) {
                                        $context[$varName] = 'Error: ' . $result['message'];
                                    } else {
                                        $cats = $result['data'];
                                        $msg = '';
                                        foreach ($cats as $i => $c) {
                                            $msg .= ($i+1) . '. ' . ($c['name'] ?? 'Category') . ' (' . ($c['count'] ?? 0) . ")\n";
                                        }
                                        $context[$varName] = $msg ?: 'No categories found';
                                    }
                                    break;
                                default:
                                    $context[$varName] = 'Unknown WooCommerce action';
                            }
                        }
                    } catch (\Throwable $e) {
                        $context[$varName] = 'Error: ' . $e->getMessage();
                    }
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;

                case 'end':
                    $this->model->update_session($session->id, ['is_completed' => 1]);
                    return;

                default:
                    $next_id = $this->find_next_node($edges, $current_block->id);
                    break;
            }

            if ($next_id) {
                $current_block_id = $next_id;
                $current_block = $findBlock($current_block_id);
            } else {
                $current_block = null;
            }
        }

        $this->save_state($session->id, $current_block_id, $context);
    }

    // ===================== HELPERS =====================

    /**
 * Find a Command block trigger across all published bots linked to this instance
 * Command blocks act as entry points when user sends a specific command (e.g., /help)
 */
private function find_command_trigger($text, $instance_id) {
    $text = trim($text);
    if(empty($text)) return null;

    // Get all published bots linked to this instance
    $bots = $this->model->db->table('sp_bot_builders as b')
        ->select('b.*')
        ->join('sp_bb_integrations as i', 'i.bot_id = b.id', 'left')
        ->where('b.status', 1)
        ->groupStart()
            ->where('i.instance_id', $instance_id)
            ->where('i.status', 1)
        ->groupEnd()
        ->get()->getResult();

    foreach($bots as $bot) {
        if(isset($bot->bot_enabled) && $bot->bot_enabled == 0) continue;
        $blocks = $this->model->get_blocks($bot->id);
        foreach($blocks as $block) {
            if($block->type !== 'command') continue;
            $data = json_decode($block->data);
            $cmd = trim($data->command_name ?? '');
            if(!empty($cmd) && strtolower($text) === strtolower($cmd)) {
                return ['bot_id' => $bot->id, 'block_id' => $block->id];
            }
        }
    }
    return null;
}

    /**
 * Find a Reply block trigger across all published bots linked to this instance
 * Reply blocks act as entry points when user message matches a pattern
 */
private function find_reply_trigger($text, $instance_id) {
    $text = trim($text);
    if(empty($text)) return null;

    // Get all published bots linked to this instance
    $bots = $this->model->db->table('sp_bot_builders as b')
        ->select('b.*')
        ->join('sp_bb_integrations as i', 'i.bot_id = b.id', 'left')
        ->where('b.status', 1)
        ->groupStart()
            ->where('i.instance_id', $instance_id)
            ->where('i.status', 1)
        ->groupEnd()
        ->get()->getResult();

    foreach($bots as $bot) {
        if(isset($bot->bot_enabled) && $bot->bot_enabled == 0) continue;
        $blocks = $this->model->get_blocks($bot->id);
        foreach($blocks as $block) {
            if($block->type !== 'reply') continue;
            $data = json_decode($block->data);
            $match_text = trim($data->match_text ?? '');
            $match_type = $data->match_type ?? 'exact';
            if(empty($match_text)) continue;

            $matched = false;
            switch($match_type) {
                case 'exact':
                    $matched = (strtolower($text) === strtolower($match_text));
                    break;
                case 'contains':
                    $matched = (stripos($text, $match_text) !== false);
                    break;
                case 'starts_with':
                    $matched = (stripos($text, $match_text) === 0);
                    break;
                case 'regex':
                    $matched = (@preg_match('/' . $match_text . '/i', $text) === 1);
                    break;
            }
            if($matched) {
                return ['bot_id' => $bot->id, 'block_id' => $block->id];
            }
        }
    }
    return null;
}

    private function find_next_node($edges, $from_id, $handle = null) {
        $default_target = null;
        foreach($edges as $e) {
            if($e->from_block_id == $from_id) {
                if($handle) {
                    // Exact handle match (for conditions: 'true'/'false', for buttons: label text)
                    if($e->condition_value == $handle) return $e->to_block_id;
                } else {
                    // No handle specified: prefer edges with no condition (default edges)
                    if(empty($e->condition_value) || $e->condition_value === 'default') {
                        return $e->to_block_id;
                    }
                    // Remember first edge as fallback
                    if(!$default_target) $default_target = $e->to_block_id;
                }
            }
        }
        return $default_target;
    }

    private function save_state($id, $block_id, $context) {
        $this->model->update_session($id, [
            'current_block_id' => $block_id,
            'context' => json_encode($context)
        ]);
    }

    /**
     * Validate user input based on block type
     */
    private function validate_input($type, $data, $input) {
        // Required check
        if(isset($data->required) && $data->required === 'true' && trim($input) === '') {
            return ['valid' => false, 'message' => 'Este campo é obrigatório. Envie uma resposta para continuar.'];
        }

        switch($type) {
            case 'input_text':
                if(!empty($data->min_length) && mb_strlen($input) < intval($data->min_length)) {
                    return ['valid' => false, 'message' => "Envie pelo menos {$data->min_length} caracteres."];
                }
                if(!empty($data->max_length) && mb_strlen($input) > intval($data->max_length)) {
                    return ['valid' => false, 'message' => "Envie no máximo {$data->max_length} caracteres."];
                }
                if(!empty($data->regex)) {
                    $pattern = '/' . str_replace('/', '\/', $data->regex) . '/u';
                    if(@preg_match($pattern, $input) === 0) {
                        $msg = $data->regex_error ?? 'Input does not match the expected format.';
                        return ['valid' => false, 'message' => $msg];
                    }
                }
                break;

            case 'input_email':
                if(!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    return ['valid' => false, 'message' => 'Este e-mail não parece válido. Confira e envie novamente.'];
                }
                break;

            case 'input_website':
                if(!preg_match('/^https?:\/\/.+/i', $input)) {
                    return ['valid' => false, 'message' => 'Envie uma URL válida começando com https://.'];
                }
                break;

            case 'input_number':
                if(!is_numeric($input)) {
                    return ['valid' => false, 'message' => 'Envie um número válido.'];
                }
                $num = floatval($input);
                if(!empty($data->min) && $num < floatval($data->min)) {
                    return ['valid' => false, 'message' => "O número precisa ser no mínimo {$data->min}."];
                }
                if(!empty($data->max) && $num > floatval($data->max)) {
                    return ['valid' => false, 'message' => "O número precisa ser no máximo {$data->max}."];
                }
                if(!empty($data->step)) {
                    $step = floatval($data->step);
                    $base = !empty($data->min) ? floatval($data->min) : 0;
                    if($step > 0 && fmod($num - $base, $step) != 0) {
                        return ['valid' => false, 'message' => "O número precisa respeitar intervalos de {$data->step}."];
                    }
                }
                break;

            case 'input_phone':
                $cleaned = preg_replace('/[\s\-\(\)]/', '', $input);
                if(!preg_match('/^\+?[0-9]{7,15}$/', $cleaned)) {
                    return ['valid' => false, 'message' => 'Envie um telefone válido.'];
                }
                break;

            case 'input_date':
                $fmt = $data->format ?? 'YYYY-MM-DD';
                if($fmt === 'YYYY-MM-DD' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
                    return ['valid' => false, 'message' => 'Envie uma data no formato AAAA-MM-DD.'];
                }
                if($fmt === 'DD/MM/YYYY' && !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $input)) {
                    return ['valid' => false, 'message' => 'Envie uma data no formato DD/MM/AAAA.'];
                }
                if($fmt === 'MM/DD/YYYY' && !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $input)) {
                    return ['valid' => false, 'message' => 'Envie uma data no formato MM/DD/AAAA.'];
                }
                if($fmt === 'DD-MM-YYYY' && !preg_match('/^\d{2}-\d{2}-\d{4}$/', $input)) {
                    return ['valid' => false, 'message' => 'Envie uma data no formato DD-MM-AAAA.'];
                }
                // Range validation
                if(!empty($data->min_date) || !empty($data->max_date)) {
                    try {
                        $dateVal = new \DateTime($input);
                        if(!empty($data->min_date)) {
                            $minDate = new \DateTime($data->min_date);
                            if($dateVal < $minDate) return ['valid' => false, 'message' => "A data precisa ser em ou após {$data->min_date}."];
                        }
                        if(!empty($data->max_date)) {
                            $maxDate = new \DateTime($data->max_date);
                            if($dateVal > $maxDate) return ['valid' => false, 'message' => "A data precisa ser em ou antes de {$data->max_date}."];
                        }
                    } catch(\Exception $e) {}
                }
                break;

            case 'input_time':
                if(!preg_match('/^\d{1,2}:\d{2}/', $input)) {
                    return ['valid' => false, 'message' => 'Envie um horário válido, por exemplo 14:30.'];
                }
                // Range validation
                if(!empty($data->min_time) && strcmp($input, $data->min_time) < 0) {
                    return ['valid' => false, 'message' => "O horário precisa ser em ou após {$data->min_time}."];
                }
                if(!empty($data->max_time) && strcmp($input, $data->max_time) > 0) {
                    return ['valid' => false, 'message' => "O horário precisa ser em ou antes de {$data->max_time}."];
                }
                break;

            case 'rating':
                $num = intval($input);
                $max = intval($data->max_stars ?? 5);
                if($num < 1 || $num > $max) {
                    return ['valid' => false, 'message' => "Envie uma avaliação entre 1 e $max."];
                }
                break;

            case 'file_upload':
                // In WhatsApp context, we receive the file URL or media info
                // Basic validation: ensure something was provided
                if(trim($input) === '') {
                    return ['valid' => false, 'message' => 'Envie um arquivo para continuar.'];
                }
                break;
        }
        return ['valid' => true];
    }

    private function replace_vars($text, $context) {
        foreach($context as $k => $v) {
            if(is_string($v) || is_numeric($v)) {
                $text = str_replace("{{".$k."}}", $v, $text);
            }
        }
        return preg_replace('/{{\s*[^}]+\s*}}/', '', $text);
    }

    private function get_native_template_payload($team_id, $ids, $type) {
        if(empty($ids)) return null;
        $template = db_get('id, ids, name, type, data', TB_WHATSAPP_TEMPLATE, [
            'team_id' => $team_id,
            'ids' => $ids,
            'type' => (int)$type
        ]);
        if(empty($template) || empty($template->data)) return null;

        $payload = json_decode($template->data, true);
        if(!is_array($payload)) return null;
        $payload['_template_id'] = $template->id;
        $payload['_template_ids'] = $template->ids;
        $payload['_template_name'] = $template->name;
        $payload['_template_type'] = (int)$template->type;
        return $payload;
    }

    private function replace_vars_recursive($value, $context) {
        if(is_string($value)) return $this->replace_vars($value, $context);
        if(is_array($value)) {
            foreach($value as $k => $v) {
                $value[$k] = $this->replace_vars_recursive($v, $context);
            }
        }
        return $value;
    }

    private function save_quick_buttons_runtime_template($team_id, $block_id, $text, $templateButtons, $title = '', $image = '') {
        if(empty($templateButtons) || !is_array($templateButtons)) return false;

        $ids = 'bb_quick_buttons_' . $block_id;
        $now = time();
        $imageUrl = $image ?: '';
        $data = [
            'templateButtons' => $templateButtons,
            'footer' => '',
            'title' => $title,
            'text' => $text,
            'caption' => $text,
            'image' => $imageUrl ? ['url' => $imageUrl] : null,
            'local_variables' => [],
            'meta_official' => [
                'enabled' => false,
                'base_name' => '',
                'category' => 'MARKETING',
                'languages' => '',
                'header_format' => 'TEXT',
                'body_example' => ''
            ]
        ];

        $table = $this->model->db->table(TB_WHATSAPP_TEMPLATE);
        $existing = $table->where('team_id', $team_id)->where('ids', $ids)->where('type', 2)->get()->getRow();

        $row = [
            'team_id' => $team_id,
            'ids' => $ids,
            'name' => 'BB_QUICK_BUTTONS_' . $block_id,
            'type' => 2,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'changed' => $now
        ];

        if(!empty($existing)) {
            $this->model->db->table(TB_WHATSAPP_TEMPLATE)->where('id', $existing->id)->update($row);
            return (int)$existing->id;
        }

        $row['created'] = $now;
        $this->model->db->table(TB_WHATSAPP_TEMPLATE)->insert($row);
        return (int)$this->model->db->insertID();
    }

    private function normalize_native_buttons_payload($payload) {
        $buttons = [];
        foreach(($payload['templateButtons'] ?? []) as $index => $btn) {
            if(isset($btn['quickReplyButton'])) {
                $q = $btn['quickReplyButton'];
                $buttons[] = [
                    'id' => $q['id'] ?? ($q['displayText'] ?? 'bb_btn_' . ($index + 1)),
                    'label' => $q['displayText'] ?? ($q['id'] ?? 'Opção ' . ($index + 1)),
                    'value' => $q['id'] ?? ($q['displayText'] ?? 'bb_btn_' . ($index + 1))
                ];
            } elseif(isset($btn['urlButton'])) {
                $u = $btn['urlButton'];
                $label = $u['displayText'] ?? 'Link ' . ($index + 1);
                $buttons[] = [
                    'id' => 'btn_url_' . $index,
                    'label' => $label,
                    'value' => $label
                ];
            } elseif(isset($btn['callButton'])) {
                $c = $btn['callButton'];
                $label = $c['displayText'] ?? 'Ligar ' . ($index + 1);
                $buttons[] = [
                    'id' => 'btn_phone_' . $index,
                    'label' => $label,
                    'value' => $label
                ];
            }
        }

        return [
            'text' => $payload['text'] ?? $payload['caption'] ?? $payload['title'] ?? 'Escolha uma opção:',
            'caption' => $payload['caption'] ?? $payload['text'] ?? '',
            'title' => $payload['title'] ?? '',
            'footer' => $payload['footer'] ?? '',
            'media' => $payload['media'] ?? ($payload['image']['url'] ?? ''),
            'image' => $payload['image'] ?? null,
            'buttons' => $buttons,
            'templateButtons' => $payload['templateButtons'] ?? []
        ];
    }

    private function normalize_native_list_payload($payload) {
        return [
            'title' => $payload['title'] ?? 'Menu',
            'text' => $payload['text'] ?? $payload['caption'] ?? 'Selecione uma opção:',
            'footer' => $payload['footer'] ?? '',
            'buttonText' => $payload['buttonText'] ?? $payload['button_text'] ?? 'Selecionar',
            'sections' => $payload['sections'] ?? []
        ];
    }

    private function normalize_native_carousel_payload($payload) {
        $cards = [];
        foreach(($payload['cards'] ?? []) as $index => $card) {
            if(!is_array($card)) $card = (array)$card;

            $normalized = [
                'buttons' => []
            ];

            if(!empty($card['title'])) {
                $normalized['title'] = function_exists('spintax') ? spintax($card['title']) : $card['title'];
            }

            $body = $card['body'] ?? ($card['description'] ?? ' ');
            if(!empty($body)) {
                $normalized['body'] = function_exists('spintax') ? spintax($body) : $body;
            }

            if(!empty($card['footer'])) {
                $normalized['footer'] = function_exists('spintax') ? spintax($card['footer']) : $card['footer'];
            }

            $media = $card['media'] ?? ($card['image'] ?? null);
            if(is_string($media) && trim($media) !== '') {
                $normalized['image'] = ['url' => trim($media)];
            } elseif(is_array($media)) {
                if(!empty($media['url'])) {
                    $normalized['image'] = ['url' => $media['url']];
                } else {
                    $normalized['image'] = $media;
                }
            }

            foreach(($card['buttons'] ?? []) as $buttonIndex => $button) {
                if(!is_array($button)) $button = (array)$button;

                $params = $button['buttonParamsJson'] ?? [];
                if(is_string($params)) {
                    $decoded = json_decode($params, true);
                    $params = is_array($decoded) ? $decoded : [];
                }
                if(!is_array($params)) $params = [];

                $name = $button['name'] ?? 'quick_reply';
                $label = $params['display_text'] ?? ($params['displayText'] ?? ($button['label'] ?? ('Opção ' . ($buttonIndex + 1))));
                $label = function_exists('spintax') ? spintax((string)$label) : (string)$label;
                $label = mb_substr($label, 0, 20);

                if($name === 'cta_url' && !empty($params['url'])) {
                    $normalized['buttons'][] = [
                        'name' => 'cta_url',
                        'buttonParamsJson' => json_encode([
                            'display_text' => $label,
                            'url' => $params['url'],
                            'merchant_url' => $params['merchant_url'] ?? $params['url']
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    ];
                } elseif($name === 'cta_copy' && !empty($params['copy_code'])) {
                    $normalized['buttons'][] = [
                        'name' => 'cta_copy',
                        'buttonParamsJson' => json_encode([
                            'display_text' => $label,
                            'copy_code' => $params['copy_code']
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    ];
                } else {
                    $normalized['buttons'][] = [
                        'name' => 'quick_reply',
                        'buttonParamsJson' => json_encode([
                            'display_text' => $label,
                            'id' => mb_substr((string)($params['id'] ?? $label), 0, 64)
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    ];
                }
            }

            if(!empty($normalized['title']) || !empty($normalized['body']) || !empty($normalized['image']) || !empty($normalized['buttons'])) {
                $cards[] = $normalized;
            }
        }

        return [
            'text' => function_exists('spintax') ? spintax($payload['text'] ?? $payload['caption'] ?? 'Escolha uma opção:') : ($payload['text'] ?? $payload['caption'] ?? 'Escolha uma opção:'),
            'title' => function_exists('spintax') ? spintax($payload['title'] ?? '') : ($payload['title'] ?? ''),
            'subtitle' => function_exists('spintax') ? spintax($payload['subtitle'] ?? '') : ($payload['subtitle'] ?? ''),
            'footer' => function_exists('spintax') ? spintax($payload['footer'] ?? '') : ($payload['footer'] ?? ''),
            'cards' => $cards,
            '_template_id' => $payload['_template_id'] ?? null,
            '_template_ids' => $payload['_template_ids'] ?? null,
            '_template_type' => $payload['_template_type'] ?? 5
        ];
    }

    private function parse_buttons($str) {
        $arr = explode(',', $str);
        $btns = [];
        foreach($arr as $idx => $txt) {
            $txt = trim($txt);
            if(!$txt) continue;

            // Support extended format: label|type|url|phone|copy_code
            // type can be: text, url, phone, copy
            // Legacy plain text is treated as type=text
            $parts = explode('|', $txt);
            $parts = array_map('trim', $parts);
            $label = $parts[0] ?? $txt;
            $type = $parts[1] ?? 'text';
            $url = $parts[2] ?? '';
            $phone = $parts[3] ?? '';
            $copy_code = $parts[4] ?? '';

            if (!in_array($type, ['text','url','phone','copy'], true)) {
                $type = 'text';
            }

            // For text buttons, use label as ID for routing
            $id = ($type === 'text') ? $label : ('btn_' . $type . '_' . $idx);

            $btns[] = (object)[
                'id' => $id,
                'label' => $label,
                'value' => $label,
                'type' => $type,
                'url' => $url,
                'phone' => $phone,
                'copy_code' => $copy_code,
            ];
        }
        return $btns;
    }

    private function build_template_buttons_from_parsed($btns) {
        $templateButtons = [];
        foreach($btns as $idx => $btn) {
            switch($btn->type) {
                case 'url':
                    $templateButtons[] = [
                        'index' => $idx,
                        'urlButton' => [
                            'displayText' => $btn->label,
                            'url' => $btn->url ?: 'https://example.com',
                        ]
                    ];
                    break;
                case 'phone':
                    $templateButtons[] = [
                        'index' => $idx,
                        'callButton' => [
                            'displayText' => $btn->label,
                            'phoneNumber' => $btn->phone ?: '+5511999999999',
                        ]
                    ];
                    break;
                case 'copy':
                    $copyUrl = 'https://www.whatsapp.com/otp/code/?otp_type=COPY_CODE&code=' . urlencode($btn->copy_code ?: $btn->label);
                    $templateButtons[] = [
                        'index' => $idx,
                        'urlButton' => [
                            'displayText' => $btn->label,
                            'url' => $copyUrl,
                            'disabled' => false
                        ]
                    ];
                    break;
                default: // text
                    $templateButtons[] = [
                        'index' => $idx,
                        'quickReplyButton' => [
                            'displayText' => $btn->label,
                            'id' => $btn->id
                        ]
                    ];
                    break;
            }
        }
        return $templateButtons;
    }

    private function evaluate_condition($val, $op, $expected) {
        switch($op) {
            case '==': return $val == $expected;
            case '!=': return $val != $expected;
            case 'contains': return strpos(strtolower($val), strtolower($expected)) !== false;
            case 'starts_with': return strpos($val, $expected) === 0;
            case 'ends_with': return substr($val, -strlen($expected)) === $expected;
            case '>': return floatval($val) > floatval($expected);
            case '<': return floatval($val) < floatval($expected);
            case '>=': return floatval($val) >= floatval($expected);
            case '<=': return floatval($val) <= floatval($expected);
            case 'is_empty': return empty($val);
            case 'not_empty': return !empty($val);
            default: return false;
        }
    }

    private function extract_knowledge_files($files): string {
        $files = is_string($files) ? json_decode($files, true) : $files;
        if(!is_array($files)) return '';

        $chunks = [];
        foreach(array_slice($files, 0, 5) as $file) {
            $url = $file['url'] ?? '';
            $name = $file['name'] ?? basename(parse_url($url, PHP_URL_PATH) ?: 'arquivo');
            $path = $this->knowledge_file_path($url);
            if(!$path || !is_file($path)) continue;
            $text = $this->extract_knowledge_file_text($path);
            if($text !== '') {
                $chunks[] = "Arquivo: {$name}\n" . mb_substr($text, 0, 4000);
            }
        }
        return mb_substr(implode("\n\n---\n\n", $chunks), 0, 12000);
    }

    private function knowledge_file_path(string $url): string {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $needle = '/writable/uploads/';
        $pos = strpos($path, $needle);
        if($pos === false) return '';
        $file = basename(substr($path, $pos + strlen($needle)));
        return WRITEPATH . 'uploads/' . $file;
    }

    private function extract_knowledge_file_text(string $path): string {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if(in_array($ext, ['txt', 'csv'])) {
            return trim((string)@file_get_contents($path));
        }
        if($ext === 'xlsx') return $this->extract_xlsx_text($path);
        if($ext === 'xls') return 'Planilha XLS enviada. Converta para XLSX ou CSV para leitura automática.';
        if($ext === 'pdf') return $this->extract_pdf_text($path);
        return '';
    }

    private function extract_xlsx_text(string $path): string {
        if(!class_exists('ZipArchive')) return '';
        $zip = new \ZipArchive();
        if($zip->open($path) !== true) return '';
        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if($sharedXml !== false) {
            $xml = @simplexml_load_string($sharedXml);
            if($xml) foreach($xml->si as $si) $shared[] = trim((string)$si->t);
        }
        $out = [];
        for($i = 1; $i <= 10; $i++) {
            $sheet = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
            if($sheet === false) continue;
            $xml = @simplexml_load_string($sheet);
            if(!$xml) continue;
            foreach($xml->sheetData->row as $row) {
                $cells = [];
                foreach($row->c as $cell) {
                    $v = trim((string)$cell->v);
                    if((string)$cell['t'] === 's') $v = $shared[(int)$v] ?? $v;
                    if($v !== '') $cells[] = $v;
                }
                if($cells) $out[] = implode(' | ', $cells);
            }
        }
        $zip->close();
        return trim(implode("\n", $out));
    }

    private function extract_pdf_text(string $path): string {
        $raw = (string)@file_get_contents($path);
        if($raw === '') return '';
        preg_match_all('/\(([^()]{3,})\)/', $raw, $matches);
        $text = implode(' ', $matches[1] ?? []);
        $text = stripcslashes($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text) ?: 'PDF enviado, mas o texto não pôde ser extraído automaticamente.';
    }

    private function call_ai_service($prompt, $context, $config = null, $provider = 'auto', $team_id = null) {
        $team_id = $team_id ?: (get_team('id') ?? null);
        if(!$team_id) return "Serviço de IA indisponível.";

        $config = is_object($config) ? (array)$config : (array)$config;
        $config['system_prompt'] = $this->replace_vars($config['system_prompt'] ?? 'Você é um assistente útil.', $context);
        if(!empty($config['knowledge_base'])) {
            $config['system_prompt'] .= "\n\nBase de conhecimento da empresa:\n" . $this->replace_vars($config['knowledge_base'], $context);
        }
        $fileKnowledge = $this->extract_knowledge_files($config['knowledge_files'] ?? '[]');
        if($fileKnowledge !== '') {
            $config['system_prompt'] .= "\n\nConteúdo dos anexos da base de conhecimento:\n" . $fileKnowledge;
        }
        $visibleContext = array_diff_key($context, array_flip(['ai_history']));
        if(!empty($visibleContext)) {
            $config['system_prompt'] .= "\n\nVariáveis atuais do fluxo:\n" . json_encode($visibleContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if(!empty($context['ai_history']) && is_array($context['ai_history'])) {
            $history = array_map(fn($item) => 'Cliente: ' . ($item['user'] ?? '') . "\nAssistente: " . ($item['assistant'] ?? ''), $context['ai_history']);
            $config['system_prompt'] .= "\n\nHistórico recente da conversa:\n" . implode("\n---\n", $history);
        }
        $config['model'] = $config['model'] ?? '';
        $config['temperature'] = $config['temperature'] ?? 0.7;
        $config['max_tokens'] = $config['max_tokens'] ?? 500;

        return \App\Services\AIService::reply($provider ?: ($config['provider'] ?? 'auto'), $config, $prompt, $team_id);
    }

    private function call_gemini($api_key, $prompt, $temperature, $max_tokens) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $api_key;
        $data = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $max_tokens
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['candidates'][0]['content']['parts'][0]['text'] ?? 'AI could not generate a response.';
    }

    private function call_openai($api_key, $prompt, $model, $temperature, $max_tokens) {
        $url = "https://api.openai.com/v1/chat/completions";
        $data = [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['choices'][0]['message']['content'] ?? 'AI could not generate a response.';
    }

    private function execute_webhook($url, $method, $headers, $body) {
        $ch = curl_init($url);
        $curl_headers = ['Content-Type: application/json'];
        foreach($headers as $k => $v) $curl_headers[] = "$k: $v";

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $curl_headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: '';
    }

    /**
     * Recursively unwrap ephemeral/view-once messages to get the real content
     */
    private function unwrap_message($m) {
        if(isset($m['message']['ephemeralMessage']['message'])) {
            $m['message'] = $m['message']['ephemeralMessage']['message'];
            return $this->unwrap_message($m);
        }
        if(isset($m['message']['viewOnceMessage']['message'])) {
            $m['message'] = $m['message']['viewOnceMessage']['message'];
            return $this->unwrap_message($m);
        }
        if(isset($m['message']['viewOnceMessageV2']['message'])) {
            $m['message'] = $m['message']['viewOnceMessageV2']['message'];
            return $this->unwrap_message($m);
        }
        if(isset($m['message']['documentWithCaptionMessage']['message'])) {
            $m['message'] = $m['message']['documentWithCaptionMessage']['message'];
            return $this->unwrap_message($m);
        }
        return $m;
    }

    private function get_message_type($m) {
        $m = $this->unwrap_message($m);
        
        if(isset($m['message']['buttonsResponseMessage'])) return 'button_reply';
        if(isset($m['message']['listResponseMessage'])) return 'list_reply';
        if(isset($m['message']['interactiveResponseMessage'])) return 'button_reply';
        if(isset($m['message']['templateButtonReplyMessage'])) return 'button_reply';
        if(isset($m['message']['imageMessage'])) return 'image';
        if(isset($m['message']['videoMessage'])) return 'video';
        if(isset($m['message']['audioMessage'])) return 'audio';
        if(isset($m['message']['documentMessage'])) return 'document';
        
        return 'text';
    }

    /**
     * Extract the primary text from a WhatsApp message.
     * For button replies, returns the display text (label) first, falls back to button ID.
     */
    private function extract_text($m) {
        $m = $this->unwrap_message($m);

        // Button reply: prefer display text (matches edge condition_value)
        if(isset($m['message']['buttonsResponseMessage'])) {
            $br = $m['message']['buttonsResponseMessage'];
            // selectedDisplayText = the visible button label
            if(!empty($br['selectedDisplayText'])) return $br['selectedDisplayText'];
            // selectedButtonId = our button ID (which is now also the label)
            if(!empty($br['selectedButtonId'])) return $br['selectedButtonId'];
        }
        // List reply: prefer title (display text), fall back to row ID
        if(isset($m['message']['listResponseMessage'])) {
            $lr = $m['message']['listResponseMessage'];
            if(!empty($lr['title'])) return $lr['title'];
            if(!empty($lr['singleSelectReply']['selectedRowId'])) return $lr['singleSelectReply']['selectedRowId'];
        }
        // Template button reply
        if(isset($m['message']['templateButtonReplyMessage']['selectedDisplayText'])) {
            return $m['message']['templateButtonReplyMessage']['selectedDisplayText'];
        }
        // Interactive response (native flow buttons)
	        if(isset($m['message']['interactiveResponseMessage'])) {
	            $interactive = $m['message']['interactiveResponseMessage'];
	            $paramsJson = $interactive['nativeFlowResponseMessage']['paramsJson'] ?? '{}';
	            $body = json_decode($paramsJson, true) ?: [];
	            if(!empty($body['display_text'])) return $body['display_text'];
	            if(!empty($body['displayText'])) return $body['displayText'];
	            if(!empty($interactive['body']['text'])) return $interactive['body']['text'];
	            if(!empty($body['id'])) return $body['id'];
	            if(!empty($body['selectedId'])) return $body['selectedId'];
	        }
        // Regular text messages
        if(isset($m['message']['conversation'])) return $m['message']['conversation'];
        if(isset($m['message']['extendedTextMessage']['text'])) return $m['message']['extendedTextMessage']['text'];
        // Media captions
        if(isset($m['message']['imageMessage']['caption'])) return $m['message']['imageMessage']['caption'];
        if(isset($m['message']['videoMessage']['caption'])) return $m['message']['videoMessage']['caption'];
        if(isset($m['message']['documentMessage']['caption'])) return $m['message']['documentMessage']['caption'];
        
        return "";
    }

    /**
     * Extract the raw button ID from a button reply message.
     * This is used as a secondary matching key when display text doesn't match.
     */
    private function extract_button_id($m) {
        $m = $this->unwrap_message($m);
        
        if(isset($m['message']['buttonsResponseMessage']['selectedButtonId'])) {
            return $m['message']['buttonsResponseMessage']['selectedButtonId'];
        }
        if(isset($m['message']['listResponseMessage']['singleSelectReply']['selectedRowId'])) {
            return $m['message']['listResponseMessage']['singleSelectReply']['selectedRowId'];
        }
	        if(isset($m['message']['interactiveResponseMessage'])) {
	            $interactive = $m['message']['interactiveResponseMessage'];
	            $paramsJson = $interactive['nativeFlowResponseMessage']['paramsJson'] ?? '{}';
	            $body = json_decode($paramsJson, true) ?: [];
	            return $body['id'] ?? ($body['selectedId'] ?? null);
	        }
        return null;
    }

    private function send_whatsapp($instance_id, $phone, $type, $content) {
        $access_token = $this->get_access_token($instance_id);
        if(!$access_token) {
            log_message('error', 'Bot Builder: token de equipe não encontrado para a instância ' . $instance_id);
            return false;
        }

        // Roteia tudo via WhatsAppGatewayService — ele resolve provider e gateway
        $result = \App\Services\WhatsAppGatewayService::send($instance_id, $phone, $type, $content);

        try {
            file_put_contents(
                WRITEPATH . 'bot_builder_send.log',
                date('Y-m-d H:i:s') . ' | instance=' . $instance_id . ' | chat=' . $phone . ' | type=' . $type . ' | result=' . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n",
                FILE_APPEND
            );
        } catch(\Throwable $e) {}

        return $result;
    }

    private function get_access_token($instance_id) {
        $account = $this->model->db->table('sp_accounts')->where('token', $instance_id)->get()->getRow();
        if(!$account) return null;
        $team = $this->model->db->table('sp_team')->where('id', $account->team_id)->get()->getRow();
        return $team ? $team->ids : null;
    }
}
