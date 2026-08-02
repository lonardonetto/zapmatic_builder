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
            "login_type" => [1, 3],
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
            "login_type" => [1, 3], "team_id" => $team_id, "status" => 1
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
        $instance_id = $this->request->getPost('instance_id');
        $audio_id = $this->request->getPost('audio_id');
        $lead_mode = $this->request->getPost('lead_mode') ?: 'manual';
        $selected_contacts = $this->request->getPost('selected_contacts') ?: [];
        $phones_raw = $this->request->getPost('phones') ?: '';
        $delay = (int) ($this->request->getPost('delay_between_calls') ?? 30);
        $timeout = (int) ($this->request->getPost('timeout_ring') ?? 30);

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
            return redirect('whatsapp_call_campaign');
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
            return redirect('whatsapp_call_campaign');
        }

        $phones = array_values(array_unique($phones));

        $this->db->table(self::TB_CAMPAIGNS)->insert([
            'team_id' => $team_id,
            'instance_id' => $instance_id,
            'audio_id' => !empty($audio_id) ? (int)$audio_id : null,
            'name' => $name,
            'status' => 'draft',
            'max_concurrent' => 1,
            'delay_between_calls' => $delay,
            'timeout_ring' => $timeout,
            'total_leads' => count($phones),
            'schedule_time' => !empty($schedule_time) ? json_encode(call_normalize_schedule_hours($schedule_time)) : null,
            'schedule_weekdays' => !empty($schedule_weekdays) ? json_encode(call_normalize_schedule_weekdays($schedule_weekdays)) : null,
            'skip_team_holidays' => $skip_holidays,
            'timezone' => !empty($timezone) ? $timezone : null,
            'schedule_start' => $schedule_start,
            'status' => !empty($schedule_start) ? 'scheduled' : 'draft',
        ]);

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

        return redirect('whatsapp_call_campaign');
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

        return redirect('whatsapp_call_campaign');
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

        return redirect('whatsapp_call_campaign');
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
            return redirect('whatsapp_call_campaign');
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

        return redirect('whatsapp_call_campaign');
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
            return redirect('whatsapp_call_campaign');
        }

        $leads = $this->db->table(self::TB_LEADS)
            ->where('campaign_id', $campaign_id)
            ->orderBy('id', 'ASC')
            ->get()->getResult();

        $accounts = db_fetch("*", TB_ACCOUNTS, [
            "social_network" => "whatsapp", "category" => "profile",
            "login_type" => [1, 3], "team_id" => $team_id, "status" => 1
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
        $instance_id = $this->request->getPost('instance_id');
        $audio_id = $this->request->getPost('audio_id');
        $delay = (int) ($this->request->getPost('delay_between_calls') ?? 30);
        $timeout = (int) ($this->request->getPost('timeout_ring') ?? 30);
        $phones_raw = $this->request->getPost('phones') ?: '';
        $lead_mode = $this->request->getPost('lead_mode') ?: 'keep';
        $selected_contacts = $this->request->getPost('selected_contacts') ?: [];

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
            return redirect('whatsapp_call_campaign');
        }

        include_once APPPATH . '../inc/core/Whatsapp_call_campaign/Helpers/Whatsapp_call_campaign_helper.php';

        // Atualizar dados da campanha
        $updateData = [];
        if (!empty($name)) $updateData['name'] = $name;
        if (!empty($instance_id)) $updateData['instance_id'] = $instance_id;
        if ($audio_id !== null) $updateData['audio_id'] = !empty($audio_id) ? (int)$audio_id : null;
        $updateData['delay_between_calls'] = $delay;
        $updateData['timeout_ring'] = $timeout;
        $updateData['schedule_time'] = !empty($schedule_time) ? json_encode(call_normalize_schedule_hours($schedule_time)) : null;
        $updateData['schedule_weekdays'] = !empty($schedule_weekdays) ? json_encode(call_normalize_schedule_weekdays($schedule_weekdays)) : null;
        $updateData['skip_team_holidays'] = $skip_holidays;
        $updateData['timezone'] = !empty($timezone) ? $timezone : null;
        if ($schedule_start !== null) $updateData['schedule_start'] = $schedule_start;

        $this->db->table(self::TB_CAMPAIGNS)->where('id', $campaign_id)->update($updateData);

        // Atualizar leads conforme modo selecionado
        if ($lead_mode !== 'keep') {
            $newPhones = [];
            $phoneNames = [];

            if ($lead_mode === 'all_contacts') {
                $contacts = call_get_contacts_with_phones($team_id);
                foreach ($contacts as $c) {
                    foreach ($c->valid_phones as $p) {
                        $newPhones[] = $p;
                        $phoneNames[$p] = $c->name;
                    }
                }
            } elseif ($lead_mode === 'selected_contacts' && !empty($selected_contacts)) {
                $ids = is_array($selected_contacts) ? $selected_contacts : explode(',', $selected_contacts);
                $contacts = call_get_contacts_with_phones($team_id);
                foreach ($contacts as $c) {
                    if (in_array((string)$c->id, $ids)) {
                        foreach ($c->valid_phones as $p) {
                            $newPhones[] = $p;
                            $phoneNames[$p] = $c->name;
                        }
                    }
                }
            } else {
                // Manual
                $newPhones = array_filter(array_map('trim', preg_split('/[\n,]+/', $phones_raw)));
                $newPhones = array_map('call_normalize_phone', $newPhones);
                $newPhones = array_filter($newPhones, function($p) { return strlen($p) >= 12; });
            }

            $newPhones = array_values(array_unique($newPhones));

            if (!empty($newPhones)) {
                // Deletar todos os leads pending atuais
                $this->db->table(self::TB_LEADS)
                    ->where('campaign_id', $campaign_id)
                    ->where('status', 'pending')
                    ->delete();

                // Inserir novos leads
                $builder = $this->db->table(self::TB_LEADS);
                foreach ($newPhones as $phone) {
                    $builder->insert([
                        'campaign_id' => $campaign_id,
                        'phone' => $phone,
                        'name' => $phoneNames[$phone] ?? '',
                        'status' => 'pending',
                    ]);
                }

                // Atualizar total
                $total = $this->db->table(self::TB_LEADS)
                    ->where('campaign_id', $campaign_id)
                    ->countAllResults();
                $this->db->table(self::TB_CAMPAIGNS)
                    ->where('id', $campaign_id)
                    ->update(['total_leads' => $total]);
            }
        }

        return redirect('whatsapp_call_campaign');
    }

    public function delete($campaign_id = 0)
    {
        $team_id = get_team("id");
        $campaign_id = (int) ($this->request->getPost('campaign_id') ?: $campaign_id);

        $this->db->table(self::TB_LEADS)->where('campaign_id', $campaign_id)->delete();
        $this->db->table(self::TB_CAMPAIGNS)->where('id', $campaign_id)->where('team_id', $team_id)->delete();

        return redirect('whatsapp_call_campaign');
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

        echo json_encode(['status' => 'success', 'campaign' => $campaign, 'leads' => $leads]);
        exit;
    }

    public function results($campaign_id)
    {
        $team_id = get_team("id");
        $campaign = $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->where('team_id', $team_id)
            ->get()->getRow();

        if (!$campaign) {
            return redirect('whatsapp_call_campaign');
        }

        $leads = $this->db->table(self::TB_LEADS)
            ->where('campaign_id', $campaign_id)
            ->orderBy('id', 'ASC')
            ->get()->getResult();

        $data = [
            "title" => $this->config['name'] . ' - Resultados',
            "desc" => $campaign->name,
            "config" => $this->config,
            "content" => view('Core\Whatsapp_call_campaign\Views\results', [
                "config" => $this->config,
                "campaign" => $campaign,
                "leads" => $leads,
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
            return redirect('whatsapp_call_campaign');
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

        // Get duration with FFmpeg
        $ffprobe = shell_exec("ffprobe -v quiet -show_entries format=duration -of csv=p=0 " . escapeshellarg($filePath) . " 2>/dev/null");
        if ($ffprobe && is_numeric(trim($ffprobe))) {
            $duration = (int) round((float) trim($ffprobe));
        }

        // Convert OGG/FLAC/AAC to MP3 for meowcaller compatibility
        if (in_array($ext, ['ogg', 'oga', 'flac', 'aac', 'm4a', 'wma'])) {
            $mp3Path = preg_replace('/\.[^.]+$/', '.mp3', $filePath);
            $cmd = sprintf(
                'ffmpeg -y -i %s -codec:a libmp3lame -q:a 2 -ar 16000 -ac 1 %s 2>&1',
                escapeshellarg($filePath),
                escapeshellarg($mp3Path)
            );
            shell_exec($cmd);

            if (file_exists($mp3Path) && filesize($mp3Path) > 0) {
                @unlink($filePath);
                $filePath = $mp3Path;
                $newName = basename($mp3Path);
                $ext = 'mp3';

                // Re-read duration after conversion
                $ffprobe = shell_exec("ffprobe -v quiet -show_entries format=duration -of csv=p=0 " . escapeshellarg($filePath) . " 2>/dev/null");
                if ($ffprobe && is_numeric(trim($ffprobe))) {
                    $duration = (int) round((float) trim($ffprobe));
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

        return redirect('whatsapp_call_campaign');
    }

    public function delete_audio()
    {
        $team_id = get_team("id");
        $audio_id = (int) $this->request->getPost('audio_id');

        if (!$audio_id) {
            return redirect('whatsapp_call_campaign');
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

        return redirect('whatsapp_call_campaign');
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
}
