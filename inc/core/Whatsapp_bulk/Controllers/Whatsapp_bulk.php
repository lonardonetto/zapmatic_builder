<?php

namespace Core\Whatsapp_bulk\Controllers;

class Whatsapp_bulk extends \CodeIgniter\Controller
{
    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Whatsapp_bulk\Models\Whatsapp_bulkModel();
        helper('Core\Whatsapp_bulk\Helpers\Whatsapp_bulk_helper');
    }

    protected function is_call_campaign($type)
    {
        return (int)$type === 7;
    }

    protected function normalize_selected_account_ids($accounts)
    {
        if (!is_array($accounts)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('strval', $accounts))));
    }

    protected function cloud_parallel_presets(): array
    {
        return [10, 20, 25, 30, 40, 50, 60, 70, 80, 90, 100];
    }

    protected function parse_account_runtime_data($account_item): array
    {
        $raw = $account_item->data ?? $account_item->tmp ?? '';
        if (empty($raw)) {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $parsed = json_decode($raw, true);
        return is_array($parsed) ? $parsed : [];
    }

    protected function get_cloud_account_safe_cap(string $throughput_level = ''): int
    {
        return strtoupper(trim($throughput_level)) === 'STANDARD' || $throughput_level === '' ? 80 : 100;
    }

    protected function fetch_cloud_graph_profile(string $phone_number_id, string $access_token): array
    {
        if ($phone_number_id === '' || $access_token === '') {
            return [
                'http_code' => 0,
                'payload' => [],
                'error' => 'missing_phone_or_token',
            ];
        }

        $url = 'https://graph.facebook.com/v22.0/' . rawurlencode($phone_number_id) . '?fields=id,display_phone_number,verified_name,quality_rating,throughput';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $access_token,
            ],
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $payload = json_decode((string) $body, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        return [
            'http_code' => $http_code,
            'payload' => $payload,
            'error' => $error,
        ];
    }

    protected function build_cloud_parallel_capabilities(array $selected_account_ids, array $account_items = []): array
    {
        if (empty($account_items)) {
            $account_items = $this->model->get_account_items($selected_account_ids);
        }

        $accounts = [];
        $all_cloud = !empty($account_items);
        $has_baileys = false;

        foreach ($account_items as $account_item) {
            $is_cloud = (int) ($account_item->login_type ?? 0) === 1;
            if (!$is_cloud) {
                $all_cloud = false;
                $has_baileys = true;
                $accounts[] = [
                    'ids' => $account_item->ids,
                    'id' => (int) $account_item->id,
                    'name' => $account_item->name,
                    'login_type' => (int) $account_item->login_type,
                    'is_cloud' => false,
                    'cap' => 0,
                    'supports_parallel' => false,
                    'throughput_level' => '',
                    'quality_rating' => '',
                    'display_phone_number' => '',
                ];
                continue;
            }

            $runtime = $this->parse_account_runtime_data($account_item);
            $phone_number_id = (string) ($runtime['phone_number_id'] ?? $account_item->pid ?? '');
            $access_token = (string) ($runtime['token'] ?? $runtime['access_token'] ?? '');
            $profile = $this->fetch_cloud_graph_profile($phone_number_id, $access_token);
            $payload = $profile['payload'];
            $throughput_level = (string) ($payload['throughput']['level'] ?? '');
            $safe_cap = $this->get_cloud_account_safe_cap($throughput_level);

            $accounts[] = [
                'ids' => $account_item->ids,
                'id' => (int) $account_item->id,
                'name' => $account_item->name,
                'login_type' => (int) $account_item->login_type,
                'is_cloud' => true,
                'cap' => $safe_cap,
                'supports_parallel' => true,
                'throughput_level' => $throughput_level,
                'quality_rating' => (string) ($payload['quality_rating'] ?? ''),
                'display_phone_number' => (string) ($payload['display_phone_number'] ?? ''),
                'phone_number_id' => $phone_number_id,
                'throughput_known' => $throughput_level !== '',
                'cap_source' => $throughput_level !== '' ? 'live_throughput' : 'safe_fallback',
                'http_code' => (int) ($profile['http_code'] ?? 0),
                'error' => (string) ($profile['error'] ?? ''),
            ];
        }

        $aggregate_cap = 0;
        if ($all_cloud) {
            $aggregate_cap = min(100, array_sum(array_map(static function ($account) {
                return (int) ($account['cap'] ?? 0);
            }, $accounts)));
        }

        $allowed_levels = array_values(array_filter($this->cloud_parallel_presets(), static function ($level) use ($aggregate_cap) {
            return $level <= $aggregate_cap;
        }));

        return [
            'all_cloud' => $all_cloud,
            'has_baileys' => $has_baileys,
            'accounts' => $accounts,
            'aggregate_cap' => $aggregate_cap,
            'allowed_levels' => $allowed_levels,
            'fallback_legacy' => !$all_cloud,
        ];
    }

    protected function purge_schedule_runtime_state(int $schedule_id): void
    {
        if ($schedule_id <= 0) {
            return;
        }

        db_delete(TB_WHATSAPP_CLOUD_DISPATCHES, ['schedule_id' => $schedule_id]);
        db_delete(TB_WHATSAPP_MESSAGE_STATUS, ['schedule_id' => $schedule_id]);
    }

    protected function schedule_weekday_options(): array
    {
        if (function_exists('whatsapp_bulk_schedule_weekday_options')) {
            return whatsapp_bulk_schedule_weekday_options();
        }

        return [
            '1' => ['short' => 'Seg', 'label' => 'Segunda-feira'],
            '2' => ['short' => 'Ter', 'label' => 'Terça-feira'],
            '3' => ['short' => 'Qua', 'label' => 'Quarta-feira'],
            '4' => ['short' => 'Qui', 'label' => 'Quinta-feira'],
            '5' => ['short' => 'Sex', 'label' => 'Sexta-feira'],
            '6' => ['short' => 'Sáb', 'label' => 'Sábado'],
            '7' => ['short' => 'Dom', 'label' => 'Domingo'],
        ];
    }

    protected function normalize_schedule_time($schedule_time): array
    {
        if (!is_array($schedule_time)) {
            return [];
        }

        $normalized = [];
        foreach ($schedule_time as $value) {
            $hour = (int)$value;
            if ($hour < 0 || $hour > 23) {
                continue;
            }

            $normalized[(string)$hour] = (string)$hour;
        }

        $normalized = array_values($normalized);
        sort($normalized, SORT_NUMERIC);
        return $normalized;
    }

    protected function normalize_schedule_weekdays($schedule_weekdays): array
    {
        if (!is_array($schedule_weekdays)) {
            return [];
        }

        $normalized = [];
        foreach ($schedule_weekdays as $value) {
            $weekday = (int)$value;
            if ($weekday < 1 || $weekday > 7) {
                continue;
            }

            $normalized[(string)$weekday] = (string)$weekday;
        }

        $normalized = array_values($normalized);
        sort($normalized, SORT_NUMERIC);
        return $normalized;
    }

    protected function resolve_time_post_timezone(): \DateTimeZone
    {
        $timezone = get_user("timezone");
        if (empty($timezone)) {
            $timezone = date_default_timezone_get();
        }

        try {
            return new \DateTimeZone($timezone);
        } catch (\Throwable $e) {
            return new \DateTimeZone(date_default_timezone_get());
        }
    }

    protected function parse_time_post_input($value)
    {
        if (is_numeric($value)) {
            $timestamp = (int)$value;
            return $timestamp > 0 ? $timestamp : false;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return false;
        }

        $timezone = $this->resolve_time_post_timezone();
        $formats = array_values(array_unique(array_filter([
            get_option('format_datetime', 'd/m/Y g:i A'),
            'd/m/Y H:i',
            'd/m/Y G:i',
            'd/m/Y g:i A',
            'd/m/Y h:i A',
            'Y-m-d H:i',
            'Y-m-d G:i',
            'Y-m-d g:i A',
            'Y-m-d h:i A',
            'm/d/Y H:i',
            'm/d/Y G:i',
            'm/d/Y g:i A',
            'm/d/Y h:i A',
        ])));

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat('!' . $format, $value, $timezone);
            $errors = \DateTime::getLastErrors();
            $has_errors = is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0);

            if ($date instanceof \DateTime && !$has_errors) {
                return $date->getTimestamp();
            }
        }

        if (strpos($value, '/') === false) {
            $timestamp = strtotime($value);
            return $timestamp !== false && $timestamp > 0 ? $timestamp : false;
        }

        return false;
    }

    protected function is_valid_holiday_date(string $holiday_date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $holiday_date)) {
            return false;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $holiday_date);
        return $date && $date->format('Y-m-d') === $holiday_date;
    }

    protected function get_team_holidays_payload(int $team_id): array
    {
        $holidays = $this->model->get_team_holidays($team_id);
        if (empty($holidays)) {
            return [];
        }

        return array_map(static function ($holiday) {
            return [
                'id' => (int)($holiday->id ?? 0),
                'holiday_date' => (string)($holiday->holiday_date ?? ''),
                'name' => (string)($holiday->name ?? ''),
                'label' => !empty($holiday->holiday_date)
                    ? date('d/m/Y', strtotime((string)$holiday->holiday_date))
                    : '',
            ];
        }, $holidays);
    }

    public function cloud_capabilities()
    {
        $selected_account_ids = $this->normalize_selected_account_ids(post('accounts'));

        if (empty($selected_account_ids)) {
            ms([
                'status' => 'success',
                'data' => [
                    'all_cloud' => false,
                    'has_baileys' => false,
                    'accounts' => [],
                    'aggregate_cap' => 0,
                    'allowed_levels' => [],
                    'fallback_legacy' => true,
                ],
            ]);
        }

        $account_items = $this->model->get_account_items($selected_account_ids);
        $valid_account_ids = array_map(static function ($account_item) {
            return (string) ($account_item->ids ?? '');
        }, $account_items);

        if (count($account_items) !== count($selected_account_ids) || array_diff($selected_account_ids, $valid_account_ids)) {
            ms([
                'status' => 'error',
                'message' => __('You need to log in again to access your selected WhatsApp accounts.'),
            ]);
        }

        ms([
            'status' => 'success',
            'data' => $this->build_cloud_parallel_capabilities($selected_account_ids, $account_items),
        ]);
    }

    public function team_holidays()
    {
        $team_id = (int)get_team('id');

        ms([
            'status' => 'success',
            'data' => [
                'holidays' => $this->get_team_holidays_payload($team_id),
            ],
        ]);
    }

    public function save_team_holiday()
    {
        $team_id = (int)get_team('id');
        $id = (int)post('id');
        $holiday_date = trim((string)post('holiday_date'));
        $name = trim((string)post('name'));

        if (!$this->is_valid_holiday_date($holiday_date)) {
            ms([
                'status' => 'error',
                'message' => __('Informe uma data de feriado válida.'),
            ]);
        }

        validate('null', __('Nome do feriado'), $name);
        validate('max_length', __('Nome do feriado'), $name, 191);

        $saved = $this->model->save_team_holiday($team_id, $holiday_date, $name, $id);
        if (!$saved) {
            ms([
                'status' => 'error',
                'message' => __('Não foi possível salvar o feriado da equipe.'),
            ]);
        }

        ms([
            'status' => 'success',
            'message' => __('Feriado da equipe salvo com sucesso.'),
            'data' => [
                'holidays' => $this->get_team_holidays_payload($team_id),
            ],
        ]);
    }

    public function delete_team_holiday()
    {
        $team_id = (int)get_team('id');
        $id = (int)post('id');

        if ($id <= 0) {
            ms([
                'status' => 'error',
                'message' => __('Feriado inválido.'),
            ]);
        }

        $deleted = $this->model->delete_team_holiday($team_id, $id);
        if (!$deleted) {
            ms([
                'status' => 'error',
                'message' => __('Não foi possível remover o feriado da equipe.'),
            ]);
        }

        ms([
            'status' => 'success',
            'message' => __('Feriado da equipe removido com sucesso.'),
            'data' => [
                'holidays' => $this->get_team_holidays_payload($team_id),
            ],
        ]);
    }

    public function index($page = false, $ids = false)
    {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        switch ($page) {
            case 'update':
                $team_id = get_team("id");
                $item = false;
                $status_summary = [];

                if ($ids) {
                    $item = db_get("*", TB_WHATSAPP_SCHEDULES, ["ids" => $ids, "team_id" => $team_id]);

                    // Resumo de status Cloud API (se a campanha existir)
                    if (!empty($item) && isset($item->id)) {
                        $db = \Config\Database::connect();
                        $builder = $db->table(TB_WHATSAPP_MESSAGE_STATUS);
                        $builder->select('status, COUNT(*) as total');
                        $builder->where('team_id', $team_id);
                        $builder->where('schedule_id', $item->id);
                        $builder->groupBy('status');
                        $query = $builder->get();
                        foreach ($query->getResult() as $row) {
                            $status_summary[$row->status] = (int) $row->total;
                        }
                    }
                }

                $contacts = db_fetch("*", TB_WHATSAPP_CONTACTS, ["team_id" => $team_id, "status" => 1], "id", "DESC");

                $data['content'] = view('Core\Whatsapp_bulk\Views\update', [
                    "result" => $item,
                    "contacts" => $contacts,
                    "config" => $this->config,
                    "status_summary" => $status_summary,
                    "weekday_options" => $this->schedule_weekday_options(),
                    "team_holidays" => $this->get_team_holidays_payload($team_id),
                ]);
                break;

            default:
                $total = $this->model->get_list(false);

                $datatable = [
                    "total_items" => $total,
                    "per_page" => 30,
                    "current_page" => 1,

                ];

                $data_content = [
                    'total' => $total,
                    'datatable'  => $datatable,
                    'config'  => $this->config,
                ];

                $data['content'] = view('Core\Whatsapp_bulk\Views\content', $data_content);
                break;
        }

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function ajax_list()
    {
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);
        $data = [
            "result" => $result,
            "config" => $this->config
        ];
        ms([
            "total_items" => $total_items,
            "data" => view('Core\Whatsapp_bulk\Views\ajax_list', $data)
        ]);
    }

    public function live_status()
    {
        $team_id = get_team("id");
        $ids = post("ids");

        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : explode(',', $ids);
        }

        $ids = array_values(array_unique(array_filter(array_map('strval', (array) $ids))));
        if (empty($ids)) {
            ms([
                "status" => "success",
                "data" => []
            ]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_WHATSAPP_SCHEDULES);
        $builder->select('*');
        $builder->where('team_id', $team_id);
        $builder->whereIn('ids', $ids);
        $query = $builder->get();
        $items = $query->getResult();
        $query->freeResult();

        if (empty($items)) {
            ms([
                "status" => "success",
                "data" => []
            ]);
        }

        $contact_ids = [];
        $cloud_parallel_schedule_ids = [];
        foreach ($items as $item) {
            $contact_ids[] = (int) ($item->contact_id ?? 0);
            if ((int) ($item->cloud_parallel_enabled ?? 0) === 1) {
                $cloud_parallel_schedule_ids[] = (int) ($item->id ?? 0);
            }
        }

        $contact_ids = array_values(array_unique(array_filter($contact_ids)));
        $phone_counts = [];
        if (!empty($contact_ids)) {
            $phone_builder = $db->table(TB_WHATSAPP_PHONE_NUMBERS);
            $phone_builder->select('pid, COUNT(id) as total');
            $phone_builder->whereIn('pid', $contact_ids);
            $phone_builder->groupBy('pid');
            $phone_query = $phone_builder->get();
            foreach ($phone_query->getResult() ?: [] as $row) {
                $phone_counts[(int) ($row->pid ?? 0)] = (int) ($row->total ?? 0);
            }
            $phone_query->freeResult();
        }

        $cloud_parallel_summaries = $this->model->get_cloud_parallel_schedule_summaries($cloud_parallel_schedule_ids);

        $empty_error_summary = static function (): array {
            return [
                'has_error' => false,
                'code' => '',
                'title' => '',
                'message' => '',
                'tooltip' => '',
                'failed_count' => 0,
            ];
        };

        $generic_error_summary = static function (int $failed, bool $is_call_campaign = false) use ($empty_error_summary): array {
            if ($failed <= 0) {
                return $empty_error_summary();
            }

            $fallback_message = function_exists('whatsapp_bulk_baileys_failure_message')
                ? whatsapp_bulk_baileys_failure_message($is_call_campaign)
                : 'Falha no envio pelo Baileys. Verifique se o número conectado foi banido ou desconectado, se o número receptor possui WhatsApp ativo ou se o número informado não existe.';

            return [
                'has_error' => true,
                'code' => '',
                'title' => 'Falhas registradas no Baileys',
                'message' => $fallback_message,
                'tooltip' => 'Falhas registradas no Baileys - ' . $fallback_message,
                'failed_count' => $failed,
            ];
        };

        $status_html = function ($item, int $sent, int $failed, int $pending, bool $is_waiting_first_dispatch): string {
            switch ((int) ($item->status ?? 0)) {
                case 0:
                    return '<i class="fs-18 fas fa-pause-circle text-warning"></i>';

                case 1:
                    if ($is_waiting_first_dispatch) {
                        return '<div class="status-waiting"><i class="fs-18 fas fa-hourglass-half text-info"></i></div>';
                    }
                    return '<div class="status-running"><i class="fs-18 fas fa-signal text-primary"></i></div>';

                default:
                    if ($failed > 0 && $sent === 0 && $pending === 0) {
                        return '<i class="fs-18 fas fa-times-circle text-danger"></i>';
                    }
                    if ($failed > 0) {
                        return '<i class="fs-18 fas fa-exclamation-circle text-warning"></i>';
                    }
                    return '<i class="fs-18 fas fa-check-circle text-success"></i>';
            }
        };

        $format_time = function ($timestamp): string {
            $timestamp = (int) $timestamp;
            if ($timestamp <= 0) {
                return '-';
            }
            return function_exists('datetime_show') ? datetime_show($timestamp) : date('d/m/Y H:i', $timestamp);
        };

        $data = [];
        foreach ($items as $item) {
            $total = (int) ($phone_counts[(int) ($item->contact_id ?? 0)] ?? 0);
            $sent = (int) ($item->sent ?? 0);
            $failed = (int) ($item->failed ?? 0);
            $error_summary = $empty_error_summary();

            if ((int) ($item->cloud_parallel_enabled ?? 0) === 1) {
                $summary = $cloud_parallel_summaries[(int) ($item->id ?? 0)] ?? null;
                if ($summary) {
                    $sent = (int) ($summary['success'] ?? 0);
                    $failed = (int) ($summary['failed'] ?? 0);
                    $error_summary = $summary['error_summary'] ?? $empty_error_summary();
                }
            } else {
                $legacy_summary = $this->model->get_legacy_schedule_result_summary($item->result ?? '');
                $legacy_total = (int) ($legacy_summary['total'] ?? 0);
                $stored_total = $sent + $failed;
                if ($legacy_total > 0 && $legacy_total >= $stored_total) {
                    $sent = (int) ($legacy_summary['success'] ?? 0);
                    $failed = (int) ($legacy_summary['failed'] ?? 0);
                }
            }

            if ($failed > 0 && empty($error_summary['has_error'])) {
                $error_summary = $generic_error_summary($failed, (int)($item->type ?? 1) === 7);
            }

            if (!empty($error_summary['has_error'])) {
                $error_summary['failed_count'] = $failed;
            }

            $pending = max(0, $total - $sent - $failed);
            $has_dispatch_activity = ($sent + $failed) > 0;
            $time_post = (int) ($item->time_post ?? 0);
            $is_waiting_first_dispatch = (int) ($item->status ?? 0) === 1 && !$has_dispatch_activity && $pending > 0 && $time_post > time();
            $first_dispatch_label = $time_post > 0 ? date('d/m/Y H:i', $time_post) : '';
            $next_action = '-';
            if ((int) ($item->status ?? 0) !== 2 && $pending >= 0) {
                $next_action = $is_waiting_first_dispatch && $first_dispatch_label !== '' ? $first_dispatch_label : $format_time($time_post);
            }

            $min_delay = (int) ($item->min_delay ?? 0);
            $max_delay = (int) ($item->max_delay ?? 0);
            $interval_label = $min_delay === $max_delay ? sprintf('%ds', $min_delay) : sprintf('%ds - %ds', $min_delay, $max_delay);
            $sent_percent = $total > 0 ? round(($sent / $total) * 100, 1) : 0;
            $failed_percent = $total > 0 ? round(($failed / $total) * 100, 1) : 0;
            $pending_percent = $total > 0 ? round(($pending / $total) * 100, 1) : 0;

            $data[$item->ids] = [
                'ids' => $item->ids,
                'status' => (int) ($item->status ?? 0),
                'total' => $total,
                'sent' => $sent,
                'failed' => $failed,
                'pending' => $pending,
                'progress_percent' => $total > 0 ? (int) round((($sent + $failed) / $total) * 100) : 0,
                'sent_percent' => $sent_percent,
                'failed_percent' => $failed_percent,
                'pending_percent' => $pending_percent,
                'progress_tooltip' => sprintf('%s / %s / %s', number_format($sent), number_format($failed), number_format($pending)),
                'status_html' => $status_html($item, $sent, $failed, $pending, $is_waiting_first_dispatch),
                'next_action' => $next_action,
                'interval' => $interval_label,
                'error_summary' => $error_summary,
                'is_waiting_first_dispatch' => $is_waiting_first_dispatch,
                'first_dispatch_label' => $first_dispatch_label,
            ];
        }

        ms([
            "status" => "success",
            "data" => $data,
            "server_time" => time(),
        ]);
    }

    public function report($ids = "")
    {
        $result = $this->model->get_report($ids);
        if (empty($result)) {
            return false;
        }
        $file = $result->name . ".xls";
        $report = view('Core\Whatsapp_bulk\Views\report', ['result' => $result]);
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        echo $report;
    }

    public function report_by_day()
    {
        $result = $this->model->get_report_by_day();
        $format = strtolower((string) get('format'));

        if ($format === 'html') {
            echo view('Core\Whatsapp_bulk\Views\report_by_day_html', ['result' => $result]);
            return;
        }

        $file = "campaign_report.xls";
        $view = $format === 'excel'
            ? 'Core\Whatsapp_bulk\Views\report_by_day_excel'
            : 'Core\Whatsapp_bulk\Views\report_by_day';
        $report = view($view, ['result' => $result]);
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        echo $report;
    }

    public function popup_report()
    {
        $team_id = get_team("id");
        $data = [
            'config'  => $this->config,
        ];
        return view('Core\Whatsapp_bulk\Views\popup_report', $data);
    }

    public function restart($ids = false)
    {
        $team_id = get_team("id");
        $item = db_get("*", TB_WHATSAPP_SCHEDULES, ["ids" => $ids, "team_id" => $team_id]);

        if (!empty($item)) {
            $this->purge_schedule_runtime_state((int) $item->id);
            $data = [
                "run" => 0,
                "status" => 1,
                "result" => '',
                "sent" => 0,
                "failed" => 0,
                "next_account" => null,
                "changed" => time()
            ];

            $result = db_update(TB_WHATSAPP_SCHEDULES, $data, ["id" => $item->id]);
        }

        ms([
            "status" => "success",
            "message" => __("Success")
        ]);
    }

    public function save($ids = false)
    {
        try {
            $team_id = get_team("id");
            $update_name_only = post("update_name_only");
            
            // Se for apenas atualização do nome
            if($update_name_only){
                $name = post("name");
                
                if($name == ""){
                    ms([
                        "status" => "error",
                        "message" => __('Please provide the campaign name')
                    ]);
                }

                if(!$ids){
                    ms([
                        "status" => "error",
                        "message" => __('Campaign ID not provided')
                    ]);
                }

                $item = db_get("*", TB_WHATSAPP_SCHEDULES, ["ids" => $ids, "team_id" => $team_id]);
                
                if(empty($item)){
                    ms([
                        "status" => "error",
                        "message" => __('Campaign not found')
                    ]);
                }

                $update_data = [
                    "name" => $name,
                    "changed" => time()
                ];
                
                $result = db_update(TB_WHATSAPP_SCHEDULES, $update_data, ["ids" => $ids]);
                
                if(!$result){
                    ms([
                        "status" => "error",
                        "message" => __('Failed to update campaign name')
                    ]);
                }

                ms([
                    "status" => "success",
                    "message" => __('Campaign name updated successfully')
                ]);
                
                return;
            }

            $type = (int)post("type");
            $name = post("name");
            $group = post("group");
            $medias = post("medias");
            $caption = post("caption");
            $advance_options = post("advance_options");
            $template = 0;
            $btn_msg = (int)post("btn_msg");
            $list_msg = (int)post("list_msg");
            $carousel_msg = (int)post("carousel_msg");
            $min_interval_per_post = (int)post("min_interval_per_post");
            $max_interval_per_post = (int)post("max_interval_per_post");
            $cloud_parallel_enabled = (int)(post('cloud_parallel_enabled') ? 1 : 0);
            $cloud_parallel_level = (int)post('cloud_parallel_level');
            $schedule_time = post("schedule_time");
            $schedule_weekdays = post('schedule_weekdays');
            $skip_team_holidays = (int)(post('skip_team_holidays') ? 1 : 0);
            $accounts = post("accounts");
            $raw_time_post = post("time_post");
            $time_post = $this->parse_time_post_input($raw_time_post);
            $item = db_get("*", TB_WHATSAPP_SCHEDULES, ["ids" => $ids, "team_id" => $team_id]);

            $schedule_time = $this->normalize_schedule_time($schedule_time);
            $schedule_time = !empty($schedule_time) ? json_encode($schedule_time) : "";

            $schedule_weekdays = $this->normalize_schedule_weekdays($schedule_weekdays);
            $schedule_weekdays = !empty($schedule_weekdays) ? json_encode($schedule_weekdays) : "";

            validate('null', __('Campaign name'), $name);
            validate("max_length", "Campaign name", $name, 100);
            validate('null', __('Contact group'), $group);

            switch ($type) {
                case 1:
                    if (permission("whatsapp_send_media")) {
                        if (!is_array($medias) && $caption == "") {
                            ms([
                                "status" => "error",
                                "message" => __('Please enter a caption or add a media')
                            ]);
                        }
                    } else {
                        validate('null', __('Caption'), $caption);
                    }
                    break;

                case 2:
                    if ($btn_msg == 0) {
                        ms([
                            "status" => "error",
                            "message" => __('Please select a button message option')
                        ]);
                    }
                    $template = $btn_msg;
                    break;

                case 3:
                    if ($list_msg == 0) {
                        ms([
                            "status" => "error",
                            "message" => __('Please select a list message option')
                        ]);
                    }

                    $template = $list_msg;
                    break;

                case 4:
                    if ($btn_msg == 0) {
                        ms([
                            "status" => "error",
                            "message" => __('Please select a poll message option')
                        ]);
                    }
                    $template = $btn_msg;
                    break;

                case 5:
                    if ($carousel_msg == 0) {
                        ms([
                            "status" => "error",
                            "message" => __('Please select a carousel message option')
                        ]);
                    }
                    $template = $carousel_msg;
                    break;

                case 7:
                    $template = 0;
                    break;
                
                case 6:
                    ms([
                        "status" => "error",
                        "message" => __("Templates Oficiais (Meta) foram removidos do Bulk Message. Use o módulo 'Modelo de botão' para criar/submeter e sincronize no Perfil Cloud API.")
                    ]);

                default:
                    if ($btn_msg == 0 && $list_msg == 0 && $carousel_msg == 0) {
                        ms([
                            "status" => "error",
                            "message" => __('Invalid input data')
                        ]);
                    }
                    break;
            }

            validate("min_number", __("Min interval"), $min_interval_per_post, 1);
            validate("min_number", __("Max interval"), $max_interval_per_post, 1);

            if ($min_interval_per_post > $max_interval_per_post) {
                ms([
                    "status" => "error",
                    "message" => __('Max interval must be greater than or equal to min interval')
                ]);
            }

            if ($time_post === false || $time_post <= 0) {
                ms([
                    "status" => "error",
                    "message" => __('Informe uma data e hora inicial válidas no formato dd/mm/aaaa hh:mm.')
                ]);
            }

            if (empty($item) && $time_post <= time()) {
                ms([
                    "status" => "error",
                    "message" => __('Para novas campanhas, escolha uma data e hora inicial no futuro para evitar disparo imediato.')
                ]);
            }

            $group = db_get("*", TB_WHATSAPP_CONTACTS, ["id" => $group, "team_id" => $team_id]);

            validate('empty', __('Please select at least a profile'), $accounts);
            validate('empty', __('Please select a contact group'), $group);

            $selected_account_ids = $this->normalize_selected_account_ids($accounts);
            $account_items = $this->model->get_account_items($selected_account_ids);
            if (count($account_items) !== count($selected_account_ids)) {
                ms([
                    "status" => "error",
                    "message" => __("You need to log in again to access your selected WhatsApp accounts.")
                ]);
            }

            if ($this->is_call_campaign($type)) {
                foreach ($account_items as $account_item) {
                    if (
                        $account_item->social_network !== "whatsapp" ||
                        (int)$account_item->status !== 1 ||
                        (int)$account_item->login_type !== 2
                    ) {
                        ms([
                            "status" => "error",
                            "message" => __("Please select only Baileys accounts for call campaigns.")
                        ]);
                    }
                }
            }

            $cloud_capabilities = $this->build_cloud_parallel_capabilities($selected_account_ids, $account_items);
            if ($cloud_parallel_enabled === 1 && !$this->is_call_campaign($type) && $cloud_capabilities['all_cloud']) {
                if (!in_array($cloud_parallel_level, $this->cloud_parallel_presets(), true)) {
                    ms([
                        "status" => "error",
                        "message" => __('Selecione um nível paralelo válido para a Cloud API.')
                    ]);
                }

                if (!in_array($cloud_parallel_level, $cloud_capabilities['allowed_levels'], true)) {
                    ms([
                        "status" => "error",
                        "message" => sprintf(__('O nível paralelo da Cloud API selecionado excede a capacidade segura atual (%s).'), (int)$cloud_capabilities['aggregate_cap'])
                    ]);
                }
            } else {
                $cloud_parallel_enabled = 0;
                $cloud_parallel_level = 0;
            }

            $accounts = $this->model->get_accounts($selected_account_ids);

            if (!$accounts) {
                ms([
                    "status" => "error",
                    "message" => __("You need to log in again to access your selected WhatsApp accounts.")
                ]);
            }

            if ($this->is_call_campaign($type)) {
                $caption = null;
                $media = null;
            } elseif (!empty($medias) && permission("whatsapp_send_media")) {
                foreach ($medias as $key => $value) {
                    $medias[$key] = get_file_url($value);
                }

                $media = $medias[0];
            } else {
                $media = NULL;
            }

            if (!$this->is_call_campaign($type) && !empty($advance_options) && isset($advance_options['shortlink'])) {
                $shortlink_by = shortlink_by(['advance_options' => ['shortlink' => $advance_options['shortlink']]]);;
                $caption = shortlink($caption, $shortlink_by);
            }

            if (!empty($item)) {
                $data = [
                    "team_id" => $team_id,
                    "type" => $type,
                    "template" => $template,
                    "accounts" => $accounts,
                    "contact_id" => $group->id,
                    "time_post" => $time_post,
                    "min_delay" => $min_interval_per_post,
                    "max_delay" => $max_interval_per_post,
                    "cloud_parallel_enabled" => $cloud_parallel_enabled,
                    "cloud_parallel_level" => $cloud_parallel_level,
                    "schedule_time" => $schedule_time,
                    "schedule_weekdays" => $schedule_weekdays,
                    "skip_team_holidays" => $skip_team_holidays,
                    "timezone" => get_user("timezone"),
                    "name" => $name,
                    "caption" => $caption,
                    "media" => $media,
                    "run" => 0,
                    "changed" => time()
                ];

                $result = db_update(TB_WHATSAPP_SCHEDULES, $data, ["id" => $item->id]);
            } else {
                $campaign_running = db_get("count(*) as count", TB_WHATSAPP_SCHEDULES, ["status" => 1, "team_id" => $team_id])->count;
                if ($campaign_running >= (int)permission("whatsapp_bulk_max_run")) {
                    $status = 0;
                } else {
                    $status = 1;
                }

                $data = [
                    "ids" => ids(),
                    "team_id" => $team_id,
                    "type" => $type,
                    "template" => $template,
                    "accounts" => $accounts,
                    "contact_id" => $group->id,
                    "time_post" => $time_post,
                    "min_delay" => $min_interval_per_post,
                    "max_delay" => $max_interval_per_post,
                    "cloud_parallel_enabled" => $cloud_parallel_enabled,
                    "cloud_parallel_level" => $cloud_parallel_level,
                    "schedule_time" => $schedule_time,
                    "schedule_weekdays" => $schedule_weekdays,
                    "skip_team_holidays" => $skip_team_holidays,
                    "timezone" => get_user("timezone"),
                    "name" => $name,
                    "caption" => $caption,
                    "media" => $media,
                    "run" => 0,
                    "status" => $status,
                    "changed" => time(),
                    "created" => time()
                ];

                $result = db_insert(TB_WHATSAPP_SCHEDULES, $data);
            }

            ms([
                "status" => "success",
                "message" => __("Success")
            ]);
        } catch (Exception $e) {
            ms([
                "status" => "error",
                "message" => __('An unexpected error occurred')
            ]);
        }
    }

    public function status($ids = false)
    {
        $team_id = get_team('id');
        $item = db_get("*", TB_WHATSAPP_SCHEDULES, ["ids" => $ids, "team_id" => $team_id]);

        if (!$item) {
            ms([
                "status" => "error",
                "message" => __('The bulk campaign was not found')
            ]);
        }

        if (!empty($item)) {

            if ($item->status == 2) {
                ms([
                    "status" => "error",
                    "message" => __('The campaign has been completed.')
                ]);
            }

            if ($item->status == 1) {
                db_update(TB_WHATSAPP_SCHEDULES, ['status' => 0, 'run' => 0], ['ids' => $ids]);
            } else {
                if (!$this->is_call_campaign($item->type)) {
                    $stats = db_get("wa_total_sent_by_month", TB_WHATSAPP_STATS, ["team_id" => $team_id]);
                    $permissions = (int)permission("whatsapp_message_per_month");
                    if ($stats && $stats->wa_total_sent_by_month >= $permissions) {
                        ms([
                            "status" => "error",
                            "message" => __('You have exceeded the maximum number of messages you can send per month.')
                        ]);
                    }
                }

                $campaign_running = db_get("count(*) as count", TB_WHATSAPP_SCHEDULES, ["status" => 1, "team_id" => $team_id])->count;
                if ($campaign_running >= (int)permission("whatsapp_bulk_max_run")) {
                    ms([
                        "status" => "error",
                        "message" => sprintf(__('You can only run a maximum of %s campaigns at the same time.'), (int)permission("whatsapp_bulk_max_run"))
                    ]);
                }

                db_update(TB_WHATSAPP_SCHEDULES, ['status' => 1, 'run' => 0], ['ids' => $ids]);
            }
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    public function delete($ids = "")
    {
        $team_id = get_team("id");
        if($ids == "") return false;

        if( empty($ids) ){
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if( is_array($ids) ){
            foreach ($ids as $id) {
                $item = db_get('*', TB_WHATSAPP_SCHEDULES, ['ids' => $id, 'team_id' => $team_id]);
                if (!empty($item)) {
                    $this->purge_schedule_runtime_state((int) $item->id);
                }
                db_delete(TB_WHATSAPP_SCHEDULES, ['ids' => $id, 'team_id' => $team_id]);
            }
        } elseif( is_string($ids) ) {
            $item = db_get('*', TB_WHATSAPP_SCHEDULES, ['ids' => $ids, 'team_id' => $team_id]);
            if (!empty($item)) {
                $this->purge_schedule_runtime_state((int) $item->id);
            }
            db_delete(TB_WHATSAPP_SCHEDULES, ['ids' => $ids, 'team_id' => $team_id]);
        }

        ms([
            "status" => "success",
            "message" => __('Selected items have been deleted successfully')
        ]);
    }

    public function delete_bulk()
    {
        $team_id = get_team("id");
        $ids = post("ids");
        
        if (empty($ids) || !is_array($ids)) {
            ms([
                "status" => "error",
                "message" => __('Please select items to delete')
            ]);
        }

        foreach ($ids as $id) {
            $item = db_get('*', TB_WHATSAPP_SCHEDULES, ['ids' => $id, 'team_id' => $team_id]);
            if (!empty($item)) {
                $this->purge_schedule_runtime_state((int) $item->id);
            }
            db_delete(TB_WHATSAPP_SCHEDULES, ['ids' => $id, "team_id" => $team_id]);
        }

        ms([
            "status" => "success",
            "message" => __('Selected items have been deleted successfully')
        ]);
    }

    public function diagnostic_status($ids = "")
    {
        $team_id = get_team("id");
        if (empty($ids)) {
            ms([
                "status" => "error",
                "message" => __('Campaign ID required')
            ]);
        }
        
        $item = db_get("*", TB_WHATSAPP_SCHEDULES, ["ids" => $ids, "team_id" => $team_id]);
        if (empty($item)) {
            ms([
                "status" => "error",
                "message" => __('Campaign not found')
            ]);
        }
        
        require_once APPPATH . '../inc/core/Whatsapp/Helpers/status_diagnostic.php';
        $diagnostic = diagnostic_status_table($item->id, $item);
        
        ms([
            "status" => "success",
            "data" => $diagnostic
        ]);
    }

    public function duplicate($ids = ""){
        try {
            $team_id = get_team("id");
            if($ids == "") return false;
            
            $item = db_get("*", TB_WHATSAPP_SCHEDULES, ["ids" => $ids, "team_id" => $team_id]);
            
            if(!empty($item)){
                // Get the original name
                $originalName = $item->name;
                
                // Find existing copies
                $allCampaigns = db_fetch("*", TB_WHATSAPP_SCHEDULES, ["team_id" => $team_id]);
                
                $maxNumber = 0;
                if(!empty($allCampaigns)){
                    foreach($allCampaigns as $campaign){
                        if(strpos($campaign->name, $originalName . ' Copy ') === 0){
                            if(preg_match('/ Copy (\d+)$/', $campaign->name, $matches)){
                                $maxNumber = max($maxNumber, (int)$matches[1]);
                            }
                        }
                    }
                }
                
                // Create new name with next number
                $newName = $originalName . ' Copy ' . ($maxNumber + 1);
                
                $item->ids = ids();
                $item->name = $newName;
                $item->team_id = $team_id;
                $item->status = 1;
                $item->sent = 0;
                $item->failed = 0;
                $item->result = '';
                $item->next_account = null;
                $item->run = 0;
                $item->changed = time();
                $item->created = time();
                unset($item->id);

                $result = db_insert(TB_WHATSAPP_SCHEDULES, (array)$item);
                
                if(!$result){
                    ms([
                        "status" => "error",
                        "message" => __('Failed to duplicate campaign')
                    ]);
                }
            } else {
                ms([
                    "status" => "error",
                    "message" => __('Campaign not found')
                ]);
            }

            ms([
                "status" => "success",
                "message" => __('Campaign duplicated successfully')
            ]);
            
        } catch (Exception $e) {
            ms([
                "status" => "error",
                "message" => __('An error occurred while duplicating the campaign')
            ]);
        }
    }
}
