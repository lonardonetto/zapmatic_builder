<?php

namespace Core\Whatsapp_leads\Models;

use CodeIgniter\Model;

class Whatsapp_leadsModel extends Model
{
    protected $config;
    protected $accountsMap = [];
    protected $filters = [];
    protected $filteredRows = null;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
    }

    public function block_plans()
    {
        return [
            "tab" => 15,
            "position" => 350,
            "label" => __("Whatsapp tool"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => $this->config['name'],
                ],
            ]
        ];
    }

    public function block_whatsapp()
    {
        return [
            "position" => 6500,
            "config" => $this->config
        ];
    }

    protected function base_query()
    {
        $team_id = get_team('id');
        $db = \Config\Database::connect();
        $builder = $db->table('sp_whatsapp_subscriber as s');
        $builder->select([
            's.id',
            's.team_id',
            's.instance_id',
            's.chatid',
            's.tags',
            's.unreadMessages as unread_messages',
            's.lastMessageTime',
            's.lastMessage',
            's.contact_data',
            's.data',
            'a.name as account_name',
            'a.token as account_instance',
        ]);
        $builder->join('sp_accounts as a', 'a.token = s.instance_id AND a.team_id = s.team_id', 'left');
        $builder->where('s.team_id', $team_id);
        return $builder;
    }

    protected function collectInput($key)
    {
        $value = post($key);
        if ($value === null) {
            $value = get($key);
        }
        return $value;
    }

    protected function gatherFilters()
    {
        if (!empty($this->filters)) {
            return $this->filters;
        }

        $this->filters = [
            'keyword' => trim((string)($this->collectInput('keyword') ?? '')),
            'instance' => $this->collectInput('instance'),
            'date_from' => $this->collectInput('date_from'),
            'date_to' => $this->collectInput('date_to'),
            'per_page' => (int)($this->collectInput('per_page') ?? 30),
            'current_page' => (int)($this->collectInput('current_page') ?? 1),
        ];

        if ($this->filters['per_page'] <= 0) {
            $this->filters['per_page'] = 30;
        }
        if ($this->filters['current_page'] <= 0) {
            $this->filters['current_page'] = 1;
        }

        return $this->filters;
    }

    protected function loadAccountsMap()
    {
        if (!empty($this->accountsMap)) {
            return;
        }

        $team_id = get_team('id');
        $db = \Config\Database::connect();
        $builder = $db->table('sp_accounts');
        $builder->select('token, name');
        $builder->where(['team_id' => $team_id, 'social_network' => 'whatsapp']);
        $builder->orderBy('name', 'ASC');
        $result = $builder->get()->getResult();
        if (!empty($result)) {
            foreach ($result as $item) {
                $this->accountsMap[$item->token] = $item->name;
            }
        }
    }

    protected function fetchFilteredRows()
    {
        if ($this->filteredRows !== null) {
            return $this->filteredRows;
        }

        $filters = $this->gatherFilters();
        $builder = $this->base_query();

        if (!empty($filters['instance'])) {
            $builder->where('s.instance_id', $filters['instance']);
        }

        $builder->orderBy('s.id', 'DESC');
        $rows = $builder->get()->getResult();

        $this->loadAccountsMap();

        $keyword = strtolower($filters['keyword']);
        $dateFromTs = $filters['date_from'] ? strtotime($filters['date_from'] . ' 00:00:00') : null;
        $dateToTs = $filters['date_to'] ? strtotime($filters['date_to'] . ' 23:59:59') : null;

        $filtered = [];
        $seenTriplets = [];

        foreach ($rows as $row) {
            $contact_data = json_decode($row->contact_data ?? '{}');
            $data_blob = json_decode($row->data ?? '{}');

            $jid = strtolower($row->chatid ?? '');
            $isGroup = false;
            if (isset($contact_data->isGroup)) {
                $value = $contact_data->isGroup;
                $isGroup = $value === true || $value === 'true' || $value === 1 || $value === '1';
            }

            $isPersonChat = false;
            if ($jid) {
                if (str_ends_with($jid, '@s.whatsapp.net') || str_ends_with($jid, '@c.us')) {
                    $isPersonChat = true;
                }

                $groupPatterns = ['@g.us', '@newsletter', '@broadcast', '@lid', '@temp'];
                foreach ($groupPatterns as $pattern) {
                    if (strpos($jid, $pattern) !== false) {
                        $isGroup = true;
                        break;
                    }
                }
            }

            if ($isGroup || (!$isPersonChat && $jid)) {
                continue;
            }

            $row->name = $contact_data->name ?? '';
            $row->phone_number = $contact_data->number ?? '';
            if (!$row->phone_number) {
                $row->phone_number = $contact_data->bsuid ?? ($contact_data->identity_key ?? '');
            }
            if (!$row->phone_number) {
                $row->phone_number = $row->chatid ?? '';
            }
            if (!$row->phone_number && !empty($row->chatid)) {
                $row->phone_number = preg_replace('/@.*/', '', $row->chatid);
            }
            $row->account_phone = $row->account_instance ? preg_replace('/@.*/', '', $row->account_instance) : '';
            if (!empty($row->account_instance) && isset($this->accountsMap[$row->account_instance])) {
                $row->account_name = $this->accountsMap[$row->account_instance];
            }

            $createdTs = null;
            if (isset($data_blob->created)) {
                $createdTs = strtotime($data_blob->created);
            }
            $row->created_at = $createdTs ? date('Y-m-d H:i:s', $createdTs) : '';

            $lastMessageTime = is_numeric($row->lastMessageTime ?? null) ? (int)$row->lastMessageTime : null;
            if ($lastMessageTime) {
                $row->last_message_at = date('Y-m-d H:i:s', $lastMessageTime);
            } else {
                $row->last_message_at = '';
            }

            // Keyword filter
            if ($keyword) {
                $haystack = strtolower(
                    implode(' ', [
                        $row->name ?? '',
                        $row->phone_number ?? '',
                        $row->chatid ?? '',
                        $row->account_name ?? ''
                    ])
                );
                if (strpos($haystack, $keyword) === false) {
                    continue;
                }
            }

            // Date filters using created timestamp
            if ($dateFromTs && (!$createdTs || $createdTs < $dateFromTs)) {
                continue;
            }
            if ($dateToTs && (!$createdTs || $createdTs > $dateToTs)) {
                continue;
            }

            $tripletKey = implode(':', [
                (string)($row->team_id ?? ''),
                (string)($row->instance_id ?? ''),
                (string)($row->chatid ?? ''),
            ]);

            // Keep only the newest record for the same lead inside the same connection.
            if (isset($seenTriplets[$tripletKey])) {
                continue;
            }

            $seenTriplets[$tripletKey] = true;
            $filtered[] = $row;
        }

        $this->filteredRows = $filtered;
        return $this->filteredRows;
    }

    public function get_instances()
    {
        $this->loadAccountsMap();
        $result = [];
        foreach ($this->accountsMap as $token => $name) {
            $obj = new \stdClass();
            $obj->token = $token;
            $obj->name = $name;
            $result[] = $obj;
        }
        return $result;
    }

    public function get_list($return_data = true)
    {
        $this->filteredRows = null;
        $filters = $this->gatherFilters();
        $rows = $this->fetchFilteredRows();

        if (!$return_data) {
            return count($rows);
        }

        $offset = ($filters['current_page'] - 1) * $filters['per_page'];
        if ($offset < 0) {
            $offset = 0;
        }

        return array_slice($rows, $offset, $filters['per_page']);
    }

    public function get_all_for_export()
    {
        $this->filteredRows = null;
        $this->filters = [];
        $rows = $this->fetchFilteredRows();
        return $rows;
    }

    public function delete_leads($ids = [])
    {
        if (empty($ids)) {
            return;
        }
        $team_id = get_team('id');
        $db = \Config\Database::connect();
        $builder = $db->table('sp_whatsapp_subscriber');
        $builder->where('team_id', $team_id);
        $builder->whereIn('id', $ids);
        $builder->delete();
    }
}
