<?php

namespace Core\Whatsapp_call_campaign\Controllers;

use CodeIgniter\Controller;

class Whatsapp_call_campaign extends Controller
{
    protected $config;
    protected $db;

    const TB_CAMPAIGNS = 'sp_call_campaigns';
    const TB_LEADS = 'sp_call_leads';
    const TB_AUDIOS = 'sp_call_audios';

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->db = \Config\Database::connect();

        if (!function_exists('permission') || (!permission('whatsapp_call_campaign') && !is_admin())) {
            if (function_exists('redirect')) {
                return redirect()->to('/whatsapp');
            }
        }
    }

    private function go_api(string $endpoint, array $params = [], string $method = 'GET')
    {
        $baseUrl = \App\Services\WhatsAppGatewayService::getGoBaseUrl();
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
        if (!empty($params) && $method === 'GET') {
            $url .= '?' . http_build_query($params);
        }
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            if (!empty($params)) {
                $opts[CURLOPT_POSTFIELDS] = json_encode($params);
                $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
            }
        }
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ? json_decode($result) : null;
    }

    public function index()
    {
        $team_id = get_team("id");

        $campaigns = $this->db->table(self::TB_CAMPAIGNS)
            ->where('team_id', $team_id)
            ->orderBy('id', 'DESC')
            ->get()->getResult();

        $accounts = db_fetch("*", TB_ACCOUNTS, [
            "social_network" => "whatsapp",
            "category" => "profile",
            "login_type" => [3],
            "team_id" => $team_id,
            "status" => 1
        ], "created", "ASC");

        $audios = $this->db->table(self::TB_AUDIOS)
            ->where('team_id', $team_id)
            ->orderBy('id', 'DESC')
            ->get()->getResult();

        // Carregar contatos com telefones
        include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Helpers/Whatsapp_call_campaign_helper.php';
        $contacts = call_get_contacts_with_phones($team_id);

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "config" => $this->config,
            "content" => view('Core\Whatsapp_call_campaign\Views\content', [
                "config" => $this->config,
                "campaigns" => $campaigns,
                "accounts" => $accounts,
                "audios" => $audios,
                "contacts" => $contacts,
            ])
        ];

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function create_page()
    {
        $team_id = get_team("id");

        $accounts = db_fetch("*", TB_ACCOUNTS, [
            "social_network" => "whatsapp", "category" => "profile",
            "login_type" => [3], "team_id" => $team_id, "status" => 1
        ], "created", "ASC");

        $audios = $this->db->table(self::TB_AUDIOS)
            ->where('team_id', $team_id)
            ->orderBy('id', 'DESC')
            ->get()->getResult();

        include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Helpers/Whatsapp_call_campaign_helper.php';
        $contacts = call_get_contacts_with_phones($team_id);

        $data = [
            "title" => $this->config['name'] . ' - Nova',
            "desc" => 'Criar nova campanha de chamada',
            "config" => $this->config,
            "content" => view('Core\Whatsapp_call_campaign\Views\create', [
                "config" => $this->config,
                "accounts" => $accounts,
                "audios" => $audios,
                "contacts" => $contacts,
            ])
        ];

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function create()
    {
        $team_id = get_team("id");
        $name = $this->request->getPost('name');
        $call_mode = $this->request->getPost('call_mode') ?: 'rotation';
        $parallel_instances = $this->request->getPost('parallel_instances') ?: [];
        $audio_id = $this->request->getPost('audio_id');
        $lead_mode = $this->request->getPost('lead_mode') ?: 'manual';
        $selected_contacts = $this->request->getPost('selected_contacts') ?: [];
        $phones_raw = $this->request->getPost('phones') ?: '';
        $delay = (int) ($this->request->getPost('delay_between_calls') ?? 30);
        $delay_min = max(5, (int) ($this->request->getPost('delay_min') ?? 10));
        $delay_max = max($delay_min, (int) ($this->request->getPost('delay_max') ?? 60));
        $timeout = (int) ($this->request->getPost('timeout_ring') ?? 30);

        // Resolve instâncias
        $instance_ids = is_array($parallel_instances) ? $parallel_instances : explode(',', $parallel_instances);
        $instance_ids = array_filter($instance_ids);
        if (empty($instance_ids)) {
            header('Location: ' . base_url('whatsapp_call_campaign')); exit;
        }
        $instance_id = $instance_ids[0]; // fallback

        // Agendamento
        $schedule_time = $this->request->getPost('schedule_time') ?: [];
        $schedule_weekdays = $this->request->getPost('schedule_weekdays') ?: [];
        $skip_holidays = (int) ($this->request->getPost('skip_team_holidays') ?: 0);
        $timezone = $this->request->getPost('timezone') ?: '';
        $time_post_raw = trim($this->request->getPost('time_post') ?: '');
        $clear_time_post = (int) ($this->request->getPost('clear_time_post') ?: 0);
        $schedule_start = null;
        // Se clicou "Criar Campanha" (sem agendamento), ignora time_post
        if ($clear_time_post === 1) {
            $time_post_raw = '';
        }
        if (!empty($time_post_raw)) {
            $tz = !empty($timezone) ? $timezone : date_default_timezone_get();
            try {
                $dt = \DateTime::createFromFormat('d/m/Y H:i', $time_post_raw, new \DateTimeZone($tz));
                if ($dt) $schedule_start = $dt->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {}
        }

        if (empty($name) || empty($instance_id)) {
            header('Location: ' . base_url('whatsapp_call_campaign')); exit;
        }

        include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Helpers/Whatsapp_call_campaign_helper.php';

        $phones = [];
        $phone_names = [];

        if ($lead_mode === 'all_contacts') {
            $contacts = call_get_contacts_with_phones($team_id);
            foreach ($contacts as $c) {
                foreach ($c->valid_phones as $p) {
                    $phones[] = $p;
                    $phone_names[$p] = $c->name;
                }
            }
        } elseif ($lead_mode === 'selected_contacts' && !empty($selected_contacts)) {
            $ids = is_array($selected_contacts) ? $selected_contacts : explode(',', $selected_contacts);
            $contacts = call_get_contacts_with_phones($team_id);
            foreach ($contacts as $c) {
                if (in_array((string)$c->id, $ids)) {
                    foreach ($c->valid_phones as $p) {
                        $phones[] = $p;
                        $phone_names[$p] = $c->name;
                    }
                }
            }
        } else {
            $phones = array_filter(array_map('trim', preg_split('/[\n,]+/', $phones_raw)));
            $phones = array_map('call_normalize_phone', $phones);
            $phones = array_filter($phones, function($p) { return strlen($p) >= 12; });
        }

        if (empty($phones)) {
            header('Location: ' . base_url('whatsapp_call_campaign')); exit;
        }

        $phones = array_values(array_unique($phones));

        // Detect available columns for backward compatibility
        $availCols = [];
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM " . self::TB_CAMPAIGNS)->getResultArray();
            $availCols = array_column($cols, 'Field');
        } catch (\Throwable $e) {}

        $insertData = [
            'team_id' => $team_id,
            'instance_id' => $instance_id,
            'audio_id' => !empty($audio_id) ? (int)$audio_id : null,
            'name' => $name,
            'status' => !empty($schedule_start) ? 'scheduled' : 'draft',
            'max_concurrent' => 1,
            'delay_between_calls' => $delay,
            'timeout_ring' => $timeout,
            'total_leads' => count($phones),
        ];
        if (in_array('call_mode', $availCols)) $insertData['call_mode'] = $call_mode;
        if (in_array('instance_ids', $availCols)) $insertData['instance_ids'] = !empty($instance_ids) ? json_encode($instance_ids) : null;
        if (in_array('delay_min', $availCols)) $insertData['delay_min'] = $delay_min;
        if (in_array('delay_max', $availCols)) $insertData['delay_max'] = $delay_max;
        if (in_array('schedule_time', $availCols)) $insertData['schedule_time'] = !empty($schedule_time) ? json_encode(call_normalize_schedule_hours($schedule_time)) : null;
        if (in_array('schedule_weekdays', $availCols)) $insertData['schedule_weekdays'] = !empty($schedule_weekdays) ? json_encode(call_normalize_schedule_weekdays($schedule_weekdays)) : null;
        if (in_array('skip_team_holidays', $availCols)) $insertData['skip_team_holidays'] = $skip_holidays;
        if (in_array('timezone', $availCols)) $insertData['timezone'] = !empty($timezone) ? $timezone : null;
        if (in_array('schedule_start', $availCols)) $insertData['schedule_start'] = $schedule_start;

        $this->db->table(self::TB_CAMPAIGNS)->insert($insertData);
        $campaign_id = (int) $this->db->insertID();

        $builder = $this->db->table(self::TB_LEADS);
        foreach ($phones as $phone) {
            $builder->insert([
                'campaign_id' => $campaign_id,
                'phone' => trim($phone),
                'name' => $phone_names[$phone] ?? '',
                'status' => 'pending',
            ]);
        }

        header('Location: ' . base_url('whatsapp_call_campaign')); exit;
    }

    public function start($campaign_id = 0)
    {
        $team_id = get_team("id");
        $campaign_id = (int) ($this->request->getPost('campaign_id') ?: $campaign_id);

        $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->where('team_id', $team_id)
            ->whereIn('status', ['draft', 'paused'])
            ->update(['status' => 'running']);

        header('Location: ' . base_url('whatsapp_call_campaign')); exit;
    }

    public function pause($campaign_id = 0)
    {
        $team_id = get_team("id");
        $campaign_id = (int) ($this->request->getPost('campaign_id') ?: $campaign_id);

        $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->where('team_id', $team_id)
            ->where('status', 'running')
            ->update(['status' => 'paused']);

        header('Location: ' . base_url('whatsapp_call_campaign')); exit;
    }

    public function repeat($campaign_id = 0)
    {
        $team_id = get_team("id");
        $campaign_id = (int) ($this->request->getPost('campaign_id') ?: $campaign_id);

        // Verificar se a campanha pertence ao team
        $campaign = $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->where('team_id', $team_id)
            ->get()->getRow();

        if (!$campaign) {
            header('Location: ' . base_url('whatsapp_call_campaign')); exit;
        }

        // Resetar campanha
        $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->update([
                'status' => 'draft',
                'calls_made' => 0,
                'calls_answered' => 0,
                'calls_no_answer' => 0,
                'calls_busy' => 0,
                'calls_failed' => 0,
            ]);

        // Resetar todos os leads
        $this->db->table(self::TB_LEADS)
            ->where('campaign_id', $campaign_id)
            ->update([
                'status' => 'pending',
                'call_id' => null,
                'started_at' => null,
                'answered_at' => null,
                'ended_at' => null,
                'duration_seconds' => 0,
                'response_audio' => null,
                'error_message' => null,
                'retry_count' => 0,
            ]);

        header('Location: ' . base_url('whatsapp_call_campaign')); exit;
    }

    public function edit($campaign_id = 0)
    {
        $team_id = get_team("id");
        $campaign_id = (int) ($this->request->getPost('campaign_id') ?: $this->request->getGet('campaign_id') ?: $campaign_id);

        $campaign = $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->where('team_id', $team_id)
            ->get()->getRow();

        if (!$campaign) {
            header('Location: ' . base_url('whatsapp_call_campaign')); exit;
        }

        $leads = $this->db->table(self::TB_LEADS)
            ->where('campaign_id', $campaign_id)
            ->orderBy('id', 'ASC')
            ->get()->getResult();

        $accounts = db_fetch("*", TB_ACCOUNTS, [
            "social_network" => "whatsapp", "category" => "profile",
            "login_type" => [3], "team_id" => $team_id, "status" => 1
        ], "created", "ASC");

        $audios = $this->db->table(self::TB_AUDIOS)
            ->where('team_id', $team_id)
            ->orderBy('id', 'DESC')
            ->get()->getResult();

        include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Helpers/Whatsapp_call_campaign_helper.php';
        $contacts = call_get_contacts_with_phones($team_id);

        $data = [
            "title" => $this->config['name'] . ' - Editar',
            "desc" => $campaign->name,
            "config" => $this->config,
            "content" => view('Core\Whatsapp_call_campaign\Views\edit', [
                "config" => $this->config,
                "campaign" => $campaign,
                "leads" => $leads,
                "accounts" => $accounts,
                "audios" => $audios,
                "contacts" => $contacts,
            ])
        ];

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function update($campaign_id = 0)
    {
        $team_id = get_team("id");
        $campaign_id = (int) ($this->request->getPost('campaign_id') ?: $campaign_id);
        $name = $this->request->getPost('name');
        $parallel_instances = $this->request->getPost('parallel_instances') ?: [];
        $audio_id = $this->request->getPost('audio_id');
        $call_mode = $this->request->getPost('call_mode') ?: 'fila';
        $delay_min = max(5, (int) ($this->request->getPost('delay_min') ?? 10));
        $delay_max = max($delay_min, (int) ($this->request->getPost('delay_max') ?? 60));
        $timeout = (int) ($this->request->getPost('timeout_ring') ?? 30);

        // Resolve instâncias
        $instance_ids = is_array($parallel_instances) ? $parallel_instances : explode(',', $parallel_instances);
        $instance_ids = array_filter($instance_ids);

        // Leads: keep_phones[] (mantidos) + add_contacts[] + add_phones
        $keep_phones = $this->request->getPost('keep_phones') ?: [];
        $add_contacts = $this->request->getPost('add_contacts') ?: [];
        $add_phones_raw = $this->request->getPost('add_phones') ?: '';
        $add_mode = $this->request->getPost('add_mode') ?: 'contacts';

        // Agendamento
        $schedule_time = $this->request->getPost('schedule_time') ?: [];
        $schedule_weekdays = $this->request->getPost('schedule_weekdays') ?: [];
        $skip_holidays = (int) ($this->request->getPost('skip_team_holidays') ?: 0);
        $timezone = $this->request->getPost('timezone') ?: '';
        $time_post_raw = trim($this->request->getPost('time_post') ?: '');
        $clear_time_post = (int) ($this->request->getPost('clear_time_post') ?: 0);
        $schedule_start = null;
        if ($clear_time_post === 1) {
            $time_post_raw = '';
        }
        if (!empty($time_post_raw)) {
            $tz = !empty($timezone) ? $timezone : date_default_timezone_get();
            try {
                $dt = \DateTime::createFromFormat('d/m/Y H:i', $time_post_raw, new \DateTimeZone($tz));
                if ($dt) $schedule_start = $dt->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {}
        }

        $campaign = $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->where('team_id', $team_id)
            ->get()->getRow();

        if (!$campaign) {
            header('Location: ' . base_url('whatsapp_call_campaign')); exit;
        }

        include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Helpers/Whatsapp_call_campaign_helper.php';

        // Atualizar dados da campanha
        $updateData = [];
        if (!empty($name)) $updateData['name'] = $name;
        if (!empty($instance_ids)) {
            $updateData['instance_id'] = $instance_ids[0];
            $updateData['instance_ids'] = json_encode(array_values($instance_ids));
        }
        if ($audio_id !== null) $updateData['audio_id'] = !empty($audio_id) ? (int)$audio_id : null;
        $updateData['call_mode'] = $call_mode;
        $updateData['delay_min'] = $delay_min;
        $updateData['delay_max'] = $delay_max;
        $updateData['timeout_ring'] = $timeout;
        $updateData['schedule_time'] = !empty($schedule_time) ? json_encode(call_normalize_schedule_hours($schedule_time)) : null;
        $updateData['schedule_weekdays'] = !empty($schedule_weekdays) ? json_encode(call_normalize_schedule_weekdays($schedule_weekdays)) : null;
        $updateData['skip_team_holidays'] = $skip_holidays;
        $updateData['timezone'] = !empty($timezone) ? $timezone : null;
        if ($schedule_start !== null) $updateData['schedule_start'] = $schedule_start;

        $this->db->table(self::TB_CAMPAIGNS)->where('id', $campaign_id)->update($updateData);

        // Atualizar leads: manter os que estão no formulário + adicionar novos
        include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Helpers/Whatsapp_call_campaign_helper.php';

        // Phones que o usuário manteve (não clicou ×)
        $keepPhones = array_filter(array_map('trim', $keep_phones));

        // Phones para adicionar dos contatos selecionados
        $addFromContacts = [];
        $phoneNames = [];
        if (!empty($add_contacts)) {
            $ids = is_array($add_contacts) ? $add_contacts : explode(',', $add_contacts);
            $contacts = call_get_contacts_with_phones($team_id);
            foreach ($contacts as $c) {
                if (in_array((string)$c->id, $ids)) {
                    foreach ($c->valid_phones as $p) {
                        $addFromContacts[] = $p;
                        $phoneNames[$p] = $c->name;
                    }
                }
            }
        }

        // Phones para adicionar manualmente
        $addManual = [];
        if (!empty($add_phones_raw)) {
            $addManual = array_filter(array_map('trim', preg_split('/[\n,]+/', $add_phones_raw)));
            $addManual = array_map('call_normalize_phone', $addManual);
            $addManual = array_filter($addManual, function($p) { return strlen($p) >= 12; });
        }

        // Combinar: keep + add
        $allPhones = array_merge($keepPhones, $addFromContacts, $addManual);
        $allPhones = array_values(array_unique($allPhones));

        if (!empty($allPhones)) {
            // Deletar leads pending que não estão na nova lista
            $this->db->table(self::TB_LEADS)
                ->where('campaign_id', $campaign_id)
                ->where('status', 'pending')
                ->whereNotIn('phone', $allPhones)
                ->delete();

            // Adicionar leads novos que não existem
            $existing = $this->db->table(self::TB_LEADS)
                ->select('phone')
                ->where('campaign_id', $campaign_id)
                ->get()->getResult();
            $existingPhones = array_column($existing, 'phone');

            $builder = $this->db->table(self::TB_LEADS);
            foreach ($allPhones as $phone) {
                if (!in_array($phone, $existingPhones)) {
                    $builder->insert([
                        'campaign_id' => $campaign_id,
                        'phone' => $phone,
                        'name' => $phoneNames[$phone] ?? '',
                        'status' => 'pending',
                    ]);
                }
            }

            // Atualizar total
            $total = $this->db->table(self::TB_LEADS)
                ->where('campaign_id', $campaign_id)
                ->countAllResults();
            $this->db->table(self::TB_CAMPAIGNS)
                ->where('id', $campaign_id)
                ->update(['total_leads' => $total]);
        }

        header('Location: ' . base_url('whatsapp_call_campaign')); exit;
    }

    public function delete($campaign_id = 0)
    {
        $team_id = get_team("id");
        $campaign_id = (int) ($this->request->getPost('campaign_id') ?: $campaign_id);

        $this->db->table(self::TB_LEADS)->where('campaign_id', $campaign_id)->delete();
        $this->db->table(self::TB_CAMPAIGNS)->where('id', $campaign_id)->where('team_id', $team_id)->delete();

        header('Location: ' . base_url('whatsapp_call_campaign')); exit;
    }

    public function status($campaign_id)
    {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        $team_id = get_team("id");
        $campaign = $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->where('team_id', $team_id)
            ->get()->getRow();

        if (!$campaign) {
            echo json_encode(['status' => 'error', 'message' => 'Campaign not found']);
            exit;
        }

        $leads = $this->db->table(self::TB_LEADS)
            ->where('campaign_id', $campaign_id)
            ->orderBy('id', 'ASC')
            ->get()->getResult();

        // Carrega a timeline de eventos por lead (se a tabela existir).
        $eventsByLead = [];
        try {
            $events = $this->db->table('sp_call_events')
                ->where('campaign_id', $campaign_id)
                ->orderBy('id', 'ASC')
                ->get()->getResult();
            foreach ($events as $ev) {
                $eventsByLead[$ev->lead_id][] = $ev;
            }
        } catch (\Throwable $e) {}

        echo json_encode(['status' => 'success', 'campaign' => $campaign, 'leads' => $leads, 'events' => $eventsByLead]);
        exit;
    }

    public function results($campaign_id)
    {
        $team_id = get_team("id");
        include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Helpers/Whatsapp_call_campaign_helper.php';
        $campaign = $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->where('team_id', $team_id)
            ->get()->getRow();

        if (!$campaign) {
            header('Location: ' . base_url('whatsapp_call_campaign')); exit;
        }

        $leads = $this->db->table(self::TB_LEADS)
            ->where('campaign_id', $campaign_id)
            ->orderBy('id', 'ASC')
            ->get()->getResult();

        // Carrega a timeline de eventos por lead (se a tabela existir).
        $eventsByLead = [];
        try {
            $events = $this->db->table('sp_call_events')
                ->where('campaign_id', $campaign_id)
                ->orderBy('id', 'ASC')
                ->get()->getResult();
            foreach ($events as $ev) {
                $eventsByLead[$ev->lead_id][] = $ev;
            }
        } catch (\Throwable $e) {}

        $data = [
            "title" => $this->config['name'] . ' - Resultados',
            "desc" => $campaign->name,
            "config" => $this->config,
            "content" => view('Core\Whatsapp_call_campaign\Views\results', [
                "config" => $this->config,
                "campaign" => $campaign,
                "leads" => $leads,
                "eventsByLead" => $eventsByLead,
            ])
        ];

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function upload_audio()
    {
        $team_id = get_team("id");
        $file = $this->request->getFile('audio_file');
        $name = $this->request->getPost('audio_name') ?: 'Audio';

        if (!$file || !$file->isValid()) {
            header('Location: ' . base_url('whatsapp_call_campaign')); exit;
        }

        // Save to writable/call_audio/
        $dir = WRITEPATH . 'call_audio/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($dir, $newName);

        $filePath = $dir . $newName;
        $ext = strtolower($file->getClientExtension());
        $duration = 0;

        // Include helper for pure PHP duration fallback
        include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Helpers/Whatsapp_call_campaign_helper.php';

        // Resolve full paths for ffmpeg/ffprobe (www user may not have /usr/bin in PATH)
        $ffmpeg = trim((string) $this->safeShell("which ffmpeg 2>/dev/null") ?: '');
        if (empty($ffmpeg)) $ffmpeg = '/usr/bin/ffmpeg';
        $ffprobeBin = trim((string) $this->safeShell("which ffprobe 2>/dev/null") ?: '');
        if (empty($ffprobeBin)) $ffprobeBin = '/usr/bin/ffprobe';

        // Get duration with FFmpeg if available
        $ffprobe = $this->safeShell($ffprobeBin . " -v quiet -show_entries format=duration -of csv=p=0 " . escapeshellarg($filePath) . " 2>/dev/null");
        if ($ffprobe && is_numeric(trim($ffprobe))) {
            $duration = (int) round((float) trim($ffprobe));
        }
        if ($duration <= 0 && function_exists('call_get_audio_file_duration')) {
            $duration = call_get_audio_file_duration($filePath);
        }

        // Convert OGG/FLAC/AAC to MP3 for meowcaller compatibility
        if (in_array($ext, ['ogg', 'oga', 'flac', 'aac', 'm4a', 'wma'])) {
            $mp3Path = preg_replace('/\.[^.]+$/', '.mp3', $filePath);
            $cmd = sprintf(
                '%s -y -i %s -codec:a libmp3lame -q:a 2 -ar 16000 -ac 1 %s 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($filePath),
                escapeshellarg($mp3Path)
            );
            $this->safeShell($cmd);

            if (file_exists($mp3Path) && filesize($mp3Path) > 0) {
                @unlink($filePath);
                $filePath = $mp3Path;
                $newName = basename($mp3Path);
                $ext = 'mp3';

                // Re-read duration after conversion
                $ffprobe = $this->safeShell($ffprobeBin . " -v quiet -show_entries format=duration -of csv=p=0 " . escapeshellarg($filePath) . " 2>/dev/null");
                if ($ffprobe && is_numeric(trim($ffprobe))) {
                    $duration = (int) round((float) trim($ffprobe));
                }
                if ($duration <= 0 && function_exists('call_get_audio_file_duration')) {
                    $duration = call_get_audio_file_duration($filePath);
                }
            }
        }

        $this->db->table(self::TB_AUDIOS)->insert([
            'team_id' => $team_id,
            'name' => $name,
            'original_name' => $file->getClientName(),
            'file_path' => $filePath,
            'duration_seconds' => $duration,
            'format' => $ext,
            'file_size_bytes' => filesize($filePath),
        ]);

        // Redirect back if came from edit page
        $redirect_back = $this->request->getPost('redirect_back');
        if ($redirect_back) {
            $referer = $this->request->getServer('HTTP_REFERER');
            if ($referer) { header('Location: ' . $referer); exit; }
        }

        header('Location: ' . base_url('whatsapp_call_campaign')); exit;
    }

    public function delete_audio()
    {
        $team_id = get_team("id");
        $audio_id = (int) $this->request->getPost('audio_id');

        if (!$audio_id) {
            header('Location: ' . base_url('whatsapp_call_campaign')); exit;
        }

        // Get audio file path before deleting
        $audio = $this->db->table(self::TB_AUDIOS)
            ->where('id', $audio_id)
            ->where('team_id', $team_id)
            ->get()->getRow();

        if ($audio) {
            // Delete file from disk
            if (!empty($audio->file_path) && file_exists($audio->file_path)) {
                @unlink($audio->file_path);
            }
            // Delete from DB
            $this->db->table(self::TB_AUDIOS)->where('id', $audio_id)->where('team_id', $team_id)->delete();
        }

        header('Location: ' . base_url('whatsapp_call_campaign')); exit;
    }

    public function audios()
    {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        $team_id = get_team("id");
        $audios = $this->db->table(self::TB_AUDIOS)
            ->where('team_id', $team_id)
            ->orderBy('id', 'DESC')
            ->get()->getResult();

        echo json_encode(['status' => 'success', 'audios' => $audios]);
        exit;
    }

    /**
     * Safe wrapper for shell_exec that handles disabled functions.
     */
    private function safeShell(string $cmd): ?string
    {
        try {
            if (function_exists('shell_exec')) {
                return shell_exec($cmd);
            }
            if (function_exists('exec')) {
                $output = [];
                exec($cmd, $output);
                return implode("
", $output);
            }
            if (function_exists('passthru')) {
                ob_start();
                passthru($cmd);
                return ob_get_clean();
            }
        } catch (\Throwable $e) {}
        return null;
    }
}