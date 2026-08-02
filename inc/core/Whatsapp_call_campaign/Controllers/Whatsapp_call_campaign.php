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
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        $team_id = get_team("id");
        $name = $this->request->getPost('name');
        $instance_id = $this->request->getPost('instance_id');
        $audio_id = $this->request->getPost('audio_id');
        $phones_raw = $this->request->getPost('phones');
        $delay = (int) ($this->request->getPost('delay_between_calls') ?? 30);
        $timeout = (int) ($this->request->getPost('timeout_ring') ?? 30);

        if (empty($name) || empty($instance_id) || empty($phones_raw)) {
            echo json_encode(['status' => 'error', 'message' => 'name, instance_id and phones are required']);
            exit;
        }

        // Parse phones (one per line or comma-separated)
        $phones = array_filter(array_map('trim', preg_split('/[\n,]+/', $phones_raw)));
        if (empty($phones)) {
            echo json_encode(['status' => 'error', 'message' => 'No valid phone numbers']);
            exit;
        }

        // Create campaign
        $campaign_id = $this->db->table(self::TB_CAMPAIGNS)->insert([
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

        // Insert leads
        $batch = [];
        foreach ($phones as $phone) {
            $batch[] = [
                'campaign_id' => (int)$this->db->insertID(),
                'phone' => $phone,
                'name' => '',
                'status' => 'pending',
            ];
        }

        // Batch insert
        $builder = $this->db->table(self::TB_LEADS);
        foreach ($batch as $row) {
            $builder->insert($row);
        }

        echo json_encode(['status' => 'success', 'campaign_id' => (int)$this->db->insertID(), 'leads' => count($phones)]);
        exit;
    }

    public function start($campaign_id = 0)
    {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        $team_id = get_team("id");
        $campaign_id = (int) ($this->request->getPost('campaign_id') ?: $campaign_id);

        $campaign = $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->where('team_id', $team_id)
            ->get()->getRow();

        if (!$campaign) {
            echo json_encode(['status' => 'error', 'message' => 'Campaign not found']);
            exit;
        }

        if (!in_array($campaign->status, ['draft', 'paused'])) {
            echo json_encode(['status' => 'error', 'message' => 'Campaign cannot be started (status: ' . $campaign->status . ')']);
            exit;
        }

        $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->update(['status' => 'running']);

        echo json_encode(['status' => 'success', 'message' => 'Campaign started']);
        exit;
    }

    public function pause($campaign_id = 0)
    {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        $team_id = get_team("id");
        $campaign_id = (int) ($this->request->getPost('campaign_id') ?: $campaign_id);

        $this->db->table(self::TB_CAMPAIGNS)
            ->where('id', $campaign_id)
            ->where('team_id', $team_id)
            ->where('status', 'running')
            ->update(['status' => 'paused']);

        echo json_encode(['status' => 'success', 'message' => 'Campaign paused']);
        exit;
    }

    public function delete($campaign_id = 0)
    {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        $team_id = get_team("id");
        $campaign_id = (int) ($this->request->getPost('campaign_id') ?: $campaign_id);

        $this->db->table(self::TB_LEADS)->where('campaign_id', $campaign_id)->delete();
        $this->db->table(self::TB_CAMPAIGNS)->where('id', $campaign_id)->where('team_id', $team_id)->delete();

        echo json_encode(['status' => 'success', 'message' => 'Campaign deleted']);
        exit;
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
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        $team_id = get_team("id");
        $file = $this->request->getFile('audio_file');
        $name = $this->request->getPost('audio_name') ?: 'Audio';

        if (!$file || !$file->isValid()) {
            echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
            exit;
        }

        // Save to writable/call_audio/
        $dir = WRITEPATH . 'call_audio/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($dir, $newName);

        $filePath = $dir . $newName;
        $duration = 0;

        // Try to get duration with FFmpeg if available
        $ffprobe = shell_exec("ffprobe -v quiet -show_entries format=duration -of csv=p=0 " . escapeshellarg($filePath) . " 2>/dev/null");
        if ($ffprobe && is_numeric(trim($ffprobe))) {
            $duration = (int) round((float) trim($ffprobe));
        }

        $audio_id = $this->db->table(self::TB_AUDIOS)->insert([
            'team_id' => $team_id,
            'name' => $name,
            'original_name' => $file->getClientName(),
            'file_path' => $filePath,
            'duration_seconds' => $duration,
            'format' => $file->getClientExtension(),
            'file_size_bytes' => $file->getSize(),
        ]);

        echo json_encode([
            'status' => 'success',
            'audio_id' => (int) $this->db->insertID(),
            'name' => $name,
            'duration' => $duration,
        ]);
        exit;
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
