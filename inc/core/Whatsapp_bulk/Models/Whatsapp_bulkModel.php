<?php
namespace Core\Whatsapp_bulk\Models;
use CodeIgniter\Model;

class Whatsapp_bulkModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function get_legacy_schedule_result_summary($result): array
    {
        $items = is_string($result) && trim($result) !== '' ? json_decode($result, true) : [];
        if (!is_array($items)) {
            $items = [];
        }

        $summary = [
            'success' => 0,
            'failed' => 0,
            'total' => 0,
        ];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $summary['total']++;
            if (!empty($item['status'])) {
                $summary['success']++;
            } else {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    protected function empty_failure_summary(): array
    {
        return [
            'has_error' => false,
            'code' => '',
            'title' => '',
            'message' => '',
            'tooltip' => '',
            'failed_count' => 0,
        ];
    }

    protected function friendly_cloud_failure($code, $title = '', $message = ''): array
    {
        $code = trim((string) $code);
        $raw_title = trim((string) $title);
        $raw_message = trim((string) $message);
        $haystack = strtolower($code . ' ' . $raw_title . ' ' . $raw_message);

        if ($code === '131042' || strpos($haystack, 'payment') !== false || strpos($haystack, 'unsettled') !== false) {
            return [
                'code' => $code !== '' ? $code : '131042',
                'title' => 'Problema de pagamento na conta WhatsApp Business',
                'message' => 'A Meta recusou os envios porque existem pagamentos pendentes na conta. Regularize a cobrança no Billing Hub da Meta e tente novamente.',
                'raw_title' => $raw_title,
                'raw_message' => $raw_message,
            ];
        }

        if ($code === '131056') {
            return [
                'code' => $code,
                'title' => 'Limite temporário de envio para este destinatário',
                'message' => 'A Meta bloqueou temporariamente novos envios para o mesmo usuário. Aguarde a janela de segurança e tente novamente.',
                'raw_title' => $raw_title,
                'raw_message' => $raw_message,
            ];
        }

        if ($code === '130429') {
            return [
                'code' => $code,
                'title' => 'Limite de volume da Cloud API atingido',
                'message' => 'A Meta recusou parte dos envios por limite de throughput. Reduza o nível simultâneo ou aguarde a janela de liberação.',
                'raw_title' => $raw_title,
                'raw_message' => $raw_message,
            ];
        }

        return [
            'code' => $code,
            'title' => $raw_title !== '' ? 'Falha no envio pela Cloud API' : 'Falhas registradas na campanha',
            'message' => $raw_message !== '' ? $raw_message : 'Alguns envios falharam. Abra o relatório da campanha para ver o detalhe por número.',
            'raw_title' => $raw_title,
            'raw_message' => $raw_message,
        ];
    }

    protected function register_failure_candidate(array &$summaries, int $schedule_id, $code, $title = '', $message = '', int $count = 1): void
    {
        if ($schedule_id <= 0 || empty($summaries[$schedule_id]) || $count <= 0) {
            return;
        }

        $friendly = $this->friendly_cloud_failure($code, $title, $message);
        $signature = implode('|', [
            $friendly['code'] ?? '',
            $friendly['title'] ?? '',
            $friendly['message'] ?? '',
        ]);

        if (!isset($summaries[$schedule_id]['error_candidates'][$signature])) {
            $summaries[$schedule_id]['error_candidates'][$signature] = [
                'count' => 0,
                'code' => $friendly['code'] ?? '',
                'title' => $friendly['title'] ?? '',
                'message' => $friendly['message'] ?? '',
                'raw_title' => $friendly['raw_title'] ?? '',
                'raw_message' => $friendly['raw_message'] ?? '',
            ];
        }

        $summaries[$schedule_id]['error_candidates'][$signature]['count'] += $count;
    }

    protected function finalize_failure_summaries(array &$summaries): void
    {
        foreach ($summaries as $schedule_id => $summary) {
            $candidates = $summary['error_candidates'] ?? [];
            $best = null;

            foreach ($candidates as $candidate) {
                if ($best === null || (int) ($candidate['count'] ?? 0) > (int) ($best['count'] ?? 0)) {
                    $best = $candidate;
                }
            }

            if ($best !== null) {
                $code = trim((string) ($best['code'] ?? ''));
                $title = trim((string) ($best['title'] ?? 'Falhas registradas na campanha'));
                $message = trim((string) ($best['message'] ?? 'Abra o relatório da campanha para ver o detalhe por número.'));
                $raw_title = trim((string) ($best['raw_title'] ?? ''));
                $raw_message = trim((string) ($best['raw_message'] ?? ''));
                $tooltip_parts = [];

                if ($code !== '') {
                    $tooltip_parts[] = '[' . $code . ']';
                }
                $tooltip_parts[] = $title;
                $tooltip_parts[] = $message;
                if ($raw_title !== '' && $raw_title !== $title) {
                    $tooltip_parts[] = 'Meta: ' . $raw_title;
                }

                $summaries[$schedule_id]['error_summary'] = [
                    'has_error' => true,
                    'code' => $code,
                    'title' => $title,
                    'message' => $message,
                    'tooltip' => implode(' - ', array_filter($tooltip_parts)),
                    'failed_count' => (int) ($summary['failed'] ?? 0),
                ];
            } else {
                $summaries[$schedule_id]['error_summary'] = $this->empty_failure_summary();
            }

            unset($summaries[$schedule_id]['error_candidates']);
        }
    }

    public function get_cloud_parallel_schedule_summaries(array $schedule_ids = [])
    {
        $schedule_ids = array_values(array_unique(array_filter(array_map('intval', $schedule_ids))));
        if (empty($schedule_ids)) {
            return [];
        }

        $db = \Config\Database::connect();

        $message_builder = $db->table(TB_WHATSAPP_MESSAGE_STATUS);
        $message_builder->select('schedule_id, wa_message_id, status, meta_error_code, meta_error_title, meta_error_details, last_status_at');
        $message_builder->whereIn('schedule_id', $schedule_ids);
        $message_builder->orderBy('last_status_at', 'DESC');
        $message_query = $message_builder->get();
        $message_rows = $message_query->getResult();
        $message_query->freeResult();

        $message_map = [];
        foreach ($message_rows ?: [] as $row) {
            $schedule_id = (int) ($row->schedule_id ?? 0);
            $wa_message_id = (string) ($row->wa_message_id ?? '');
            if ($schedule_id <= 0 || $wa_message_id === '') {
                continue;
            }

            if (!isset($message_map[$schedule_id])) {
                $message_map[$schedule_id] = [];
            }

            if (!isset($message_map[$schedule_id][$wa_message_id])) {
                $message_map[$schedule_id][$wa_message_id] = [
                    'status' => strtolower((string) ($row->status ?? 'sent')),
                    'error_code' => (string) ($row->meta_error_code ?? ''),
                    'error_title' => (string) ($row->meta_error_title ?? ''),
                    'error_message' => (string) ($row->meta_error_details ?? ''),
                ];
            }
        }

        $dispatch_builder = $db->table(TB_WHATSAPP_CLOUD_DISPATCHES);
        $dispatch_builder->select('schedule_id, status, wa_message_id, error_code, error_message');
        $dispatch_builder->whereIn('schedule_id', $schedule_ids);
        $dispatch_query = $dispatch_builder->get();
        $dispatch_rows = $dispatch_query->getResult();
        $dispatch_query->freeResult();

        $summaries = [];
        foreach ($schedule_ids as $schedule_id) {
            $summaries[$schedule_id] = [
                'success' => 0,
                'failed' => 0,
                'pending' => 0,
                'total' => 0,
                'error_summary' => $this->empty_failure_summary(),
                'error_candidates' => [],
            ];
        }

        foreach ($dispatch_rows ?: [] as $row) {
            $schedule_id = (int) ($row->schedule_id ?? 0);
            if ($schedule_id <= 0 || !isset($summaries[$schedule_id])) {
                continue;
            }

            $summaries[$schedule_id]['total']++;

            $wa_message_id = (string) ($row->wa_message_id ?? '');
            $message_status = ($wa_message_id !== '' && isset($message_map[$schedule_id][$wa_message_id]))
                ? $message_map[$schedule_id][$wa_message_id]
                : null;
            $final_status = $message_status ? (string) ($message_status['status'] ?? '') : '';

            if ($final_status !== '') {
                if (in_array($final_status, ['failed', 'deleted'], true)) {
                    $summaries[$schedule_id]['failed']++;
                    $this->register_failure_candidate(
                        $summaries,
                        $schedule_id,
                        $message_status['error_code'] ?? '',
                        $message_status['error_title'] ?? '',
                        $message_status['error_message'] ?? ''
                    );
                } else {
                    $summaries[$schedule_id]['success']++;
                }
                continue;
            }

            $dispatch_status = strtolower((string) ($row->status ?? 'queued'));
            if ($dispatch_status === 'failed') {
                $summaries[$schedule_id]['failed']++;
                $this->register_failure_candidate(
                    $summaries,
                    $schedule_id,
                    $row->error_code ?? '',
                    '',
                    $row->error_message ?? ''
                );
            } elseif ($dispatch_status === 'sent') {
                $summaries[$schedule_id]['success']++;
            } else {
                $summaries[$schedule_id]['pending']++;
            }
        }

        $this->finalize_failure_summaries($summaries);

        return $summaries;
    }

    public function get_account_items($list = []){
        $team_id = get_team("id");
        if (empty($list) || !is_array($list)) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_ACCOUNTS);
        $builder->select("*");
        $builder->where('team_id', $team_id);
        $builder->whereIn("ids", $list);
        $builder->where('status', 1);
        $query = $builder->get();
        $result = $query->getResult();
        $query->freeResult();

        return $result ?: [];
    }

    public function get_team_holidays(int $team_id): array
    {
        if ($team_id <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_TEAM_HOLIDAYS);
        $builder->select('id, holiday_date, name, created, changed');
        $builder->where('team_id', $team_id);
        $builder->orderBy('holiday_date', 'ASC');
        $query = $builder->get();
        $result = $query->getResult();
        $query->freeResult();

        return $result ?: [];
    }

    public function get_team_holiday(int $team_id, int $id)
    {
        if ($team_id <= 0 || $id <= 0) {
            return false;
        }

        return db_get('*', TB_WHATSAPP_TEAM_HOLIDAYS, [
            'team_id' => $team_id,
            'id' => $id,
        ]);
    }

    public function get_team_holiday_by_date(int $team_id, string $holiday_date)
    {
        if ($team_id <= 0 || $holiday_date === '') {
            return false;
        }

        return db_get('*', TB_WHATSAPP_TEAM_HOLIDAYS, [
            'team_id' => $team_id,
            'holiday_date' => $holiday_date,
        ]);
    }

    public function save_team_holiday(int $team_id, string $holiday_date, string $name, int $id = 0)
    {
        if ($team_id <= 0 || $holiday_date === '' || $name === '') {
            return false;
        }

        $now = time();
        $existingByDate = $this->get_team_holiday_by_date($team_id, $holiday_date);

        if ($id > 0) {
            $current = $this->get_team_holiday($team_id, $id);
            if (!$current) {
                return false;
            }

            if ($existingByDate && (int)$existingByDate->id !== $id) {
                db_update(TB_WHATSAPP_TEAM_HOLIDAYS, [
                    'name' => $name,
                    'changed' => $now,
                ], [
                    'id' => $existingByDate->id,
                    'team_id' => $team_id,
                ]);

                db_delete(TB_WHATSAPP_TEAM_HOLIDAYS, [
                    'id' => $id,
                    'team_id' => $team_id,
                ]);

                return $this->get_team_holiday($team_id, (int)$existingByDate->id);
            }

            db_update(TB_WHATSAPP_TEAM_HOLIDAYS, [
                'holiday_date' => $holiday_date,
                'name' => $name,
                'changed' => $now,
            ], [
                'id' => $id,
                'team_id' => $team_id,
            ]);

            return $this->get_team_holiday($team_id, $id);
        }

        if ($existingByDate) {
            db_update(TB_WHATSAPP_TEAM_HOLIDAYS, [
                'name' => $name,
                'changed' => $now,
            ], [
                'id' => $existingByDate->id,
                'team_id' => $team_id,
            ]);

            return $this->get_team_holiday($team_id, (int)$existingByDate->id);
        }

        $inserted = db_insert(TB_WHATSAPP_TEAM_HOLIDAYS, [
            'team_id' => $team_id,
            'holiday_date' => $holiday_date,
            'name' => $name,
            'created' => $now,
            'changed' => $now,
        ]);

        if (!$inserted) {
            return false;
        }

        return $this->get_team_holiday_by_date($team_id, $holiday_date);
    }

    public function delete_team_holiday(int $team_id, int $id): bool
    {
        if ($team_id <= 0 || $id <= 0) {
            return false;
        }

        return (bool)db_delete(TB_WHATSAPP_TEAM_HOLIDAYS, [
            'team_id' => $team_id,
            'id' => $id,
        ]);
    }

    public function block_quicks($path = ""){
        return [
            "position" => 1200
        ];
    }

    public function block_plans(){
        return [
            "tab" => 15,
            "position" => 100,
            "label" => __("Whatsapp tool"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => $this->config['name'],
                ],
            ]
        ];
    }

    public function block_whatsapp(){
        $data = [
            "config" => $this->config
        ];

        return array(
            "position" => 4000,
            "config" => $this->config
        );
    }

    public function get_list( $return_data = true )
    {
        $team_id = get_team("id");
        $current_page = (int)(post("current_page") - 1);
        $per_page = post("per_page");
        $total_items = post("total_items");
        $keyword = post("keyword");

        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_SCHEDULES);
        $builder->select('*');
        $builder->where('team_id', $team_id);

        if($keyword){
            $builder->groupStart()
                    ->like('name', $keyword)
                    ->orLike('caption', $keyword)
                    ->groupEnd();
        }
        
        if(!$return_data)
        {
            $result = $builder->countAllResults();
        }
        else
        {
            $builder->limit($per_page, $per_page*$current_page);
            $builder->orderBy("created", "DESC");
            $query = $builder->get();
            $result = $query->getResult();
            $query->freeResult();

            if(!empty($result)){
                $weekday_options = function_exists('whatsapp_bulk_schedule_weekday_options')
                    ? whatsapp_bulk_schedule_weekday_options()
                    : [];
                $cloud_parallel_schedule_ids = [];
                foreach ($result as $value) {
                    if ((int) ($value->cloud_parallel_enabled ?? 0) === 1) {
                        $cloud_parallel_schedule_ids[] = (int) $value->id;
                    }
                }

                $cloud_parallel_summaries = $this->get_cloud_parallel_schedule_summaries($cloud_parallel_schedule_ids);

                foreach ($result as $key => $value) {
                    $count_phone = db_get("count(id) as count", TB_WHATSAPP_PHONE_NUMBERS, ["pid" => $value->contact_id]);
                    $result[$key]->total_phone_number = $count_phone ? $count_phone->count : 0;
                    $result[$key]->time_post = $value->time_post ?? time();

                    $schedule_window = function_exists('whatsapp_bulk_schedule_window_meta')
                        ? whatsapp_bulk_schedule_window_meta(
                            $value->schedule_time ?? '',
                            $value->schedule_weekdays ?? '',
                            $value->skip_team_holidays ?? 0,
                            $weekday_options
                        )
                        : [
                            'has_rules' => false,
                            'short' => '',
                            'full' => '',
                        ];

                    $result[$key]->schedule_window_has_rules = !empty($schedule_window['has_rules']);
                    $result[$key]->schedule_window_short = $schedule_window['short'] ?? '';
                    $result[$key]->schedule_window_full = $schedule_window['full'] ?? '';

                    if ((int) ($value->cloud_parallel_enabled ?? 0) === 1) {
                        $summary = $cloud_parallel_summaries[(int) $value->id] ?? null;
                        if ($summary) {
                            $result[$key]->sent = (int) ($summary['success'] ?? 0);
                            $result[$key]->failed = (int) ($summary['failed'] ?? 0);
                            $result[$key]->failure_summary = $summary['error_summary'] ?? $this->empty_failure_summary();
                        }
                    } else {
                        $legacy_summary = $this->get_legacy_schedule_result_summary($value->result ?? '');
                        $legacy_total = (int) ($legacy_summary['total'] ?? 0);
                        $stored_total = (int) ($value->sent ?? 0) + (int) ($value->failed ?? 0);
                        if ($legacy_total > 0 && $legacy_total >= $stored_total) {
                            $result[$key]->sent = (int) ($legacy_summary['success'] ?? 0);
                            $result[$key]->failed = (int) ($legacy_summary['failed'] ?? 0);
                        }
                    }

                    if ((int) ($value->cloud_parallel_enabled ?? 0) !== 1 && (int) ($result[$key]->failed ?? 0) > 0) {
                        $fallback_message = function_exists('whatsapp_bulk_baileys_failure_message')
                            ? whatsapp_bulk_baileys_failure_message((int)($value->type ?? 1) === 7)
                            : 'Falha no envio. Verifique se o número possui WhatsApp ativo, se o número conectado está banido/desconectado, ou se o número informado não existe.';
                        $result[$key]->failure_summary = [
                            'has_error' => true,
                            'code' => '',
                            'title' => 'Falhas registradas',
                            'message' => $fallback_message,
                            'tooltip' => 'Falhas registradas - ' . $fallback_message,
                            'failed_count' => (int) ($result[$key]->failed ?? 0),
                        ];
                    } elseif (empty($result[$key]->failure_summary)) {
                        $result[$key]->failure_summary = $this->empty_failure_summary();
                    }
                }
            }
        }
        
        return $result;
    }

    public function get_accounts($list = []){
        $result = $this->get_account_items($list);

        $result_array = [];
        if(!empty($result)){
            foreach ($result as $key => $value) {
                $result_array[] = $value->id;
            }
        }

        if(!empty($result_array)){
            return json_encode($result_array);
        }else{
            return false;
        }
        
    }

    public function get_report($ids = ""){
        $team_id = get_team("id");
        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_SCHEDULES." as a");
        $builder->select("a.*, c.name as contact_name");
        $builder->join(TB_WHATSAPP_CONTACTS." as c", "a.contact_id = c.id");
        $builder->where("a.ids",$ids);
        $builder->where("a.team_id", $team_id);
        $query = $builder->get();
        $result = $query->getRow();
        $query->freeResult();
        return $result;
    }

    public function get_report_by_day(){
        $daterange = post("daterange");
        if( $daterange != "" ){
            $daterange = explode(",", $daterange);
        }else{
            $daterange = [];
        }

        if(count($daterange) != 2){
            return false;
        }

        $date_since = timestamp_sql( $daterange[0]." 00:00:00" );
        $date_until = timestamp_sql( $daterange[1]." 23:59:59" );

        $team_id = get_team("id");
        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_SCHEDULES." as a");
        $builder->select("a.*, c.name as contact_name");
        $builder->join(TB_WHATSAPP_CONTACTS." as c", "a.contact_id = c.id");
        $builder->where("a.team_id", $team_id);
        $builder->where("a.created BETWEEN '$date_since' AND '$date_until'");
        $query = $builder->get();
        $result = $query->getResult();
        
        if(!empty($result)){
            foreach ($result as $key => $value) {
                $count_phone = db_get("count(id) as count", TB_WHATSAPP_PHONE_NUMBERS, ["pid" => $value->contact_id]);
                $result[$key]->total_phone_number = $count_phone ? $count_phone->count : 0;
            }
        }
        
        return $result;
    }
}
