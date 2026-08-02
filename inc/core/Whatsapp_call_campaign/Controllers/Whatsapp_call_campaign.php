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

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "config" => $this->config,
            "content" => view('Core\Whatsapp_call_campaign\Views\content', [
                "config" => $this->config,
                "campaigns" => $campaigns,
                "accounts" => $accounts,
                "audios" => $audios,
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
        $phones_raw = $this->request->getPost('phones');
        $delay = (int) ($this->request->getPost('delay_between_calls') ?? 30);
        $timeout = (int) ($this->request->getPost('timeout_ring') ?? 30);

        if (empty($name) || empty($instance_id) || empty($phones_raw)) {
            return redirect('whatsapp_call_campaign');
        }

        // Parse phones (one per line or comma-separated)
        $phones = array_filter(array_map('trim', preg_split('/[\n,]+/', $phones_raw)));
        if (empty($phones)) {
            return redirect('whatsapp_call_campaign');
        }

        // Create campaign
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
        ]);

        $campaign_id = (int) $this->db->insertID();

        // Insert leads
        $builder = $this->db->table(self::TB_LEADS);
        foreach ($phones as $phone) {
            $builder->insert([
                'campaign_id' => $campaign_id,
                'phone' => trim($phone),
                'name' => '',
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
