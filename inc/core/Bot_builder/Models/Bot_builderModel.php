<?php
namespace Core\Bot_builder\Models;

class Bot_builderModel
{
    public $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->auto_migrate();
    }

    public function block_whatsapp(){
        $config = include realpath( __DIR__."/../Config.php" );
        return array(
            "position" => 10000,
            "config" => $config
        );
    }

    // ===================== AUTO MIGRATE =====================

    public function auto_migrate()
    {
        $forge = \Config\Database::forge();
        // Main bots table
        if(!$this->db->tableExists('sp_bot_builders')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `sp_bot_builders` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `ids` VARCHAR(255) DEFAULT NULL,
                `team_id` INT(11) DEFAULT NULL,
                `created_by` INT(11) DEFAULT NULL,
                `name` VARCHAR(255) DEFAULT NULL,
                `description` TEXT DEFAULT NULL,
                `trigger_keywords` TEXT DEFAULT NULL,
                `enable_keyword` TEXT DEFAULT NULL,
                `stop_keyword` TEXT DEFAULT NULL,
                `bot_enabled` TINYINT(1) DEFAULT 1,
                `start_block_id` VARCHAR(255) DEFAULT NULL,
                `status` TINYINT(1) DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_team` (`team_id`),
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
        // Blocks
        if(!$this->db->tableExists('sp_bb_blocks')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `sp_bb_blocks` (
                `id` VARCHAR(255) NOT NULL,
                `bot_id` INT(11) DEFAULT NULL,
                `type` VARCHAR(50) DEFAULT NULL,
                `data` LONGTEXT DEFAULT NULL,
                `pos_x` INT(11) DEFAULT 0,
                `pos_y` INT(11) DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_bot` (`bot_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
        // Edges
        if(!$this->db->tableExists('sp_bb_edges')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `sp_bb_edges` (
                `id` VARCHAR(255) NOT NULL,
                `bot_id` INT(11) DEFAULT NULL,
                `from_block_id` VARCHAR(255) DEFAULT NULL,
                `to_block_id` VARCHAR(255) DEFAULT NULL,
                `condition_type` VARCHAR(50) DEFAULT NULL,
                `condition_value` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id`),
                INDEX `idx_bot` (`bot_id`),
                INDEX `idx_from` (`from_block_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
        // Sessions
        if(!$this->db->tableExists('sp_bb_sessions')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `sp_bb_sessions` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `bot_id` INT(11) DEFAULT NULL,
                `phone` VARCHAR(255) DEFAULT NULL,
                `current_block_id` VARCHAR(255) DEFAULT NULL,
                `context` LONGTEXT DEFAULT NULL,
                `is_completed` TINYINT(1) DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_phone` (`phone`),
                INDEX `idx_bot` (`bot_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
        // Versions
        if(!$this->db->tableExists('sp_bb_versions')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `sp_bb_versions` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `bot_id` INT(11) DEFAULT NULL,
                `version` INT(11) DEFAULT 1,
                `snapshot` LONGTEXT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_bot` (`bot_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
        // Templates
        if(!$this->db->tableExists('sp_bb_templates')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `sp_bb_templates` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) DEFAULT NULL,
                `description` TEXT DEFAULT NULL,
                `category` VARCHAR(100) DEFAULT NULL,
                `icon` VARCHAR(100) DEFAULT 'fad fa-robot',
                `icon_color` VARCHAR(30) DEFAULT '#4f46e5',
                `schema_json` LONGTEXT DEFAULT NULL,
                `is_premium` TINYINT(1) DEFAULT 0,
                `price` DECIMAL(10,2) DEFAULT 0,
                `use_count` INT(11) DEFAULT 0,
                `status` TINYINT(1) DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

            $this->seed_templates();
        }
        // Template usage
        if(!$this->db->tableExists('sp_bb_template_usage')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `sp_bb_template_usage` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `template_id` INT(11) DEFAULT NULL,
                `user_id` INT(11) DEFAULT NULL,
                `bot_id` INT(11) DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        // ===== PATCH: Add missing columns to existing tables =====
        $this->safe_add_column('sp_bot_builders', 'ids', "VARCHAR(255) DEFAULT NULL AFTER `id`");
        $this->safe_add_column('sp_bb_templates', 'status', "TINYINT(1) DEFAULT 1 AFTER `use_count`");
        $this->safe_add_column('sp_bb_templates', 'icon', "VARCHAR(100) DEFAULT 'fad fa-robot' AFTER `category`");
        $this->safe_add_column('sp_bb_templates', 'icon_color', "VARCHAR(30) DEFAULT '#4f46e5' AFTER `icon`");
        $this->safe_add_column('sp_bb_templates', 'is_premium', "TINYINT(1) DEFAULT 0 AFTER `schema_json`");
        $this->safe_add_column('sp_bb_templates', 'price', "DECIMAL(10,2) DEFAULT 0 AFTER `is_premium`");
        $this->safe_add_column('sp_bb_templates', 'use_count', "INT(11) DEFAULT 0 AFTER `price`");
        $this->safe_add_column('sp_bb_sessions', 'is_completed', "TINYINT(1) DEFAULT 0 AFTER `context`");
        $this->safe_add_column('sp_bot_builders', 'start_block_id', "VARCHAR(255) DEFAULT NULL AFTER `trigger_keywords`");
        $this->safe_add_column('sp_bot_builders', 'description', "TEXT DEFAULT NULL AFTER `name`");
        $this->safe_add_column('sp_bot_builders', 'created_by', "INT(11) DEFAULT NULL AFTER `team_id`");
        $this->safe_add_column('sp_bot_builders', 'enable_keyword', "TEXT DEFAULT NULL AFTER `trigger_keywords`");
        $this->safe_add_column('sp_bot_builders', 'stop_keyword', "TEXT DEFAULT NULL AFTER `enable_keyword`");
        $this->safe_add_column('sp_bot_builders', 'bot_enabled', "TINYINT(1) DEFAULT 1 AFTER `stop_keyword`");
        $this->safe_add_column('sp_bot_builders', 'keyword_match_type', "VARCHAR(20) DEFAULT 'contains' AFTER `bot_enabled`");
        $this->safe_add_column('sp_bot_builders', 'chat_type', "VARCHAR(20) DEFAULT 'all' AFTER `keyword_match_type`");
        $this->safe_add_column('sp_bb_sessions', 'instance_id', "VARCHAR(255) DEFAULT NULL AFTER `bot_id`");

        // Bot-Instance Integrations (link bots to WhatsApp instances)
        if(!$this->db->tableExists('sp_bb_integrations')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `sp_bb_integrations` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `bot_id` INT(11) NOT NULL,
                `instance_id` INT(11) NOT NULL,
                `account_ids` VARCHAR(255) DEFAULT NULL,
                `status` TINYINT(1) DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_bot_instance` (`bot_id`, `instance_id`),
                INDEX `idx_bot` (`bot_id`),
                INDEX `idx_instance` (`instance_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        // Re-seed templates with production-ready flows if needed
        $this->reseed_if_needed();
    }

    /**
     * Safely add a column — silently ignores if column already exists
     */
    private function safe_add_column($table, $column, $definition) {
        try {
            if($this->db->tableExists($table) && !$this->db->fieldExists($column, $table)) {
                $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        } catch(\Exception $e) {
            // Column likely already exists — safe to ignore
        }
    }

    // ===================== CRUD =====================

    public function get_list() {
        return $this->db->table('sp_bot_builders')
            ->where('team_id', get_team('id'))
            ->orderBy('id', 'DESC')
            ->get()->getResult();
    }

    public function get_bot($id) {
        return $this->db->table('sp_bot_builders')
            ->where('id', $id)
            ->get()->getRow();
    }

    public function insert($data) {
        $this->db->table('sp_bot_builders')->insert($data);
        return $this->db->insertID();
    }

    public function update($id, $data) {
        return $this->db->table('sp_bot_builders')->where('id', $id)->update($data);
    }

    public function delete($id) {
        $this->db->table('sp_bb_blocks')->where('bot_id', $id)->delete();
        $this->db->table('sp_bb_edges')->where('bot_id', $id)->delete();
        $this->db->table('sp_bb_sessions')->where('bot_id', $id)->delete();
        $this->db->table('sp_bb_versions')->where('bot_id', $id)->delete();
        $this->db->table('sp_bot_builders')->where('id', $id)->delete();
    }

    // ===================== BLOCKS / EDGES =====================

    public function get_blocks($bot_id) {
        return $this->db->table('sp_bb_blocks')
            ->where('bot_id', $bot_id)
            ->get()->getResult();
    }

    public function get_edges($bot_id) {
        return $this->db->table('sp_bb_edges')
            ->where('bot_id', $bot_id)
            ->get()->getResult();
    }

    public function save_flow($bot_id, $blocks, $edges) {
        $this->db->transStart();
        $this->db->table('sp_bb_blocks')->where('bot_id', $bot_id)->delete();
        $this->db->table('sp_bb_edges')->where('bot_id', $bot_id)->delete();

        if(!empty($blocks)) {
            $batch = [];
            foreach($blocks as $block) {
                $batch[] = [
                    'id' => $block['id'],
                    'bot_id' => $bot_id,
                    'type' => $block['type'],
                    'data' => json_encode($block['data'] ?? []),
                    'pos_x' => $block['pos_x'] ?? 0,
                    'pos_y' => $block['pos_y'] ?? 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                if($block['type'] === 'start') {
                    $this->db->table('sp_bot_builders')->where('id', $bot_id)->update(['start_block_id' => $block['id']]);
                }
            }
            if(!empty($batch)) $this->db->table('sp_bb_blocks')->insertBatch($batch);
        }

        if(!empty($edges)) {
            $batch = [];
            $seen = [];
            foreach($edges as $edge) {
                $source = $edge['source'] ?? '';
                $target = $edge['target'] ?? '';
                $condition_type = $edge['data']['condition_type'] ?? null;
                $condition_value = $edge['data']['condition_value'] ?? 'default';

                if (!$source || !$target) continue;

                $unique = $source . '|' . $target . '|' . ($condition_type ?? '') . '|' . ($condition_value ?? '');
                if (isset($seen[$unique])) continue;
                $seen[$unique] = true;

                $batch[] = [
                    'id' => md5($bot_id . '|' . $unique),
                    'bot_id' => $bot_id,
                    'from_block_id' => $source,
                    'to_block_id' => $target,
                    'condition_type' => $condition_type,
                    'condition_value' => $condition_value
                ];
            }
            if(!empty($batch)) $this->db->table('sp_bb_edges')->insertBatch($batch);
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    // ===================== VERSIONING =====================

    public function create_version($bot_id, $blocks, $edges) {
        $last = $this->db->table('sp_bb_versions')
            ->where('bot_id', $bot_id)
            ->orderBy('version', 'DESC')
            ->limit(1)
            ->get()->getRow();
        $nextVer = $last ? ($last->version + 1) : 1;

        $this->db->table('sp_bb_versions')->insert([
            'bot_id' => $bot_id,
            'version' => $nextVer,
            'snapshot' => json_encode(['blocks' => $blocks, 'edges' => $edges]),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Keep max 20 versions
        $old = $this->db->table('sp_bb_versions')
            ->where('bot_id', $bot_id)
            ->orderBy('version', 'DESC')
            ->get()->getResult();
        if(count($old) > 20) {
            $toDelete = array_slice($old, 20);
            foreach($toDelete as $v) {
                $this->db->table('sp_bb_versions')->where('id', $v->id)->delete();
            }
        }
    }

    // ===================== SESSIONS =====================

    public function get_session($phone, $instance_id = null) {
        $q = $this->db->table('sp_bb_sessions')
            ->where('phone', $phone)
            ->where('is_completed', 0);
        if($instance_id) {
            $q->where('instance_id', $instance_id);
        }
        return $q->orderBy('updated_at', 'DESC')->get()->getRow();
    }

    public function get_sessions($bot_id) {
        return $this->db->table('sp_bb_sessions')
            ->where('bot_id', $bot_id)
            ->orderBy('id', 'DESC')
            ->get()->getResult();
    }

    public function get_session_by_id($session_id) {
        return $this->db->table('sp_bb_sessions')
            ->where('id', $session_id)
            ->get()->getRow();
    }

    public function get_team_id_for_session($session) {
        if(!$session || empty($session->bot_id)) return null;

        $bot = $this->db->table('sp_bot_builders')
            ->select('team_id')
            ->where('id', $session->bot_id)
            ->get()->getRow();

        return $bot ? $bot->team_id : null;
    }

    public function create_session($bot_id, $phone, $instance_id = null) {
        // End existing sessions for this phone on this instance
        $q = $this->db->table('sp_bb_sessions')
            ->where('phone', $phone)
            ->where('is_completed', 0);
        if($instance_id) $q->where('instance_id', $instance_id);
        $q->update(['is_completed' => 1]);

        $this->db->table('sp_bb_sessions')->insert([
            'bot_id' => $bot_id,
            'phone' => $phone,
            'instance_id' => $instance_id,
            'context' => '{}',
            'is_completed' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $this->db->insertID();
    }

    public function update_session($id, $data) {
        $this->db->table('sp_bb_sessions')->where('id', $id)->update($data);
    }

    // ===================== TRIGGERS =====================

    public function find_bot_by_trigger($text, $instance_id = null, $chat_id = null) {
        $text = strtolower(trim($text));

        // If instance_id provided, first check bots linked to this instance
        if($instance_id) {
            $linked = $this->db->table('sp_bot_builders as b')
                ->select('b.*, b.id as id')
                ->join('sp_bb_integrations as i', 'i.bot_id = b.id')
                ->where('b.status', 1)
                ->where('i.instance_id', $instance_id)
                ->where('i.status', 1)
                ->get()->getResult();

            foreach($linked as $bot) {
                if(isset($bot->bot_enabled) && $bot->bot_enabled == 0) continue;

                // Check chat_type filter
                if(!$this->chat_type_matches($bot, $chat_id)) continue;

                // Check keywords using match type
                if($this->keyword_matches($text, $bot)) return $bot;
            }
        }

        if($instance_id) {
            return null;
        }

        // Fallback sem instância: usado somente em rotinas manuais/legadas.
        $bots = $this->db->table('sp_bot_builders')
            ->where('status', 1)
            ->get()->getResult();

        foreach($bots as $bot) {
            if(isset($bot->bot_enabled) && $bot->bot_enabled == 0) continue;
            if(!$this->chat_type_matches($bot, $chat_id)) continue;
            if($this->keyword_matches($text, $bot)) return $bot;
        }
        return null;
    }

    /**
     * Check if a keyword matches based on the bot's keyword_match_type setting
     * 'exact' = entire message must match keyword exactly
     * 'contains' (default) = keyword just needs to appear in message
     */
    private function keyword_matches($text, $bot) {
        $match_type = $bot->keyword_match_type ?? 'contains';

        // Check enable_keyword first
        if(!empty($bot->enable_keyword)) {
            $keywords = array_map('trim', explode(',', strtolower($bot->enable_keyword)));
            foreach($keywords as $kw) {
                if(empty($kw)) continue;
                if($match_type === 'exact') {
                    if($kw === $text) return true;
                } else {
                    if($kw === $text || strpos($text, $kw) !== false) return true;
                }
            }
        }

        // Fallback to trigger_keywords
        if(!empty($bot->trigger_keywords)) {
            $keywords = array_map('trim', explode(',', strtolower($bot->trigger_keywords)));
            foreach($keywords as $kw) {
                if(empty($kw)) continue;
                if($match_type === 'exact') {
                    if($kw === $text) return true;
                } else {
                    if($kw === $text || strpos($text, $kw) !== false) return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the chat type matches the bot's chat_type setting
     * 'all' (default) = respond in both individual and group chats
     * 'individual' = only respond in private/individual chats
     * 'groups' = only respond in group chats
     */
    private function chat_type_matches($bot, $chat_id) {
        if(!$chat_id) return true; // No chat_id provided, allow all
        $chat_type = $bot->chat_type ?? 'all';
        if($chat_type === 'all') return true;

        $is_group = (strpos($chat_id, '@g.us') !== false);
        if($chat_type === 'individual' && $is_group) return false;
        if($chat_type === 'groups' && !$is_group) return false;
        return true;
    }

    /**
     * Check if text matches a stop keyword for any active bot session
     * Returns the bot if stop keyword matches, null otherwise
     */
    public function check_stop_keyword($text, $bot_id) {
        $text = strtolower(trim($text));
        $bot = $this->get_bot($bot_id);
        if(!$bot || empty($bot->stop_keyword)) return false;

        $stop_keywords = array_map('trim', explode(',', strtolower($bot->stop_keyword)));
        foreach($stop_keywords as $sk) {
            if(!empty($sk) && ($sk === $text || strpos($text, $sk) !== false)) {
                return true;
            }
        }
        return false;
    }

    /**
     * End all active sessions for a phone number and bot
     */
    public function end_sessions_for_phone($phone, $bot_id = null) {
        $q = $this->db->table('sp_bb_sessions')
            ->where('phone', $phone)
            ->where('is_completed', 0);
        if($bot_id) $q->where('bot_id', $bot_id);
        return $q->update(['is_completed' => 1]);
    }

    // ===================== INTEGRATIONS =====================

    public function get_integrations($bot_id) {
        return $this->db->table('sp_bb_integrations')
            ->where('bot_id', $bot_id)
            ->get()->getResult();
    }

    public function link_instance($bot_id, $instance_id, $account_ids = null) {
        // Check if already linked
        $existing = $this->db->table('sp_bb_integrations')
            ->where('bot_id', $bot_id)
            ->where('instance_id', $instance_id)
            ->get()->getRow();

        if($existing) {
            // Re-enable if disabled
            $this->db->table('sp_bb_integrations')
                ->where('id', $existing->id)
                ->update(['status' => 1, 'account_ids' => $account_ids]);
            return $existing->id;
        }

        $this->db->table('sp_bb_integrations')->insert([
            'bot_id' => $bot_id,
            'instance_id' => $instance_id,
            'account_ids' => $account_ids,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $this->db->insertID();
    }

    public function unlink_instance($bot_id, $instance_id) {
        return $this->db->table('sp_bb_integrations')
            ->where('bot_id', $bot_id)
            ->where('instance_id', $instance_id)
            ->delete();
    }

    public function get_instances_for_bot($bot_id) {
        return $this->db->table('sp_bb_integrations')
            ->where('bot_id', $bot_id)
            ->where('status', 1)
            ->get()->getResult();
    }

    public function get_bots_for_instance($instance_id) {
        return $this->db->table('sp_bb_integrations as i')
            ->join('sp_bot_builders as b', 'b.id = i.bot_id')
            ->where('i.instance_id', $instance_id)
            ->where('i.status', 1)
            ->where('b.status', 1)
            ->select('b.*')
            ->get()->getResult();
    }

    public function get_available_instances() {
        $team_id = get_team('id');
        return $this->db->table('sp_accounts')
            ->where('team_id', $team_id)
            ->where('status', 1)
            ->where('social_network', 'whatsapp')
            ->select('id, ids, name, avatar, pid, category')
            ->get()->getResult();
    }

    // ===================== IMPORT =====================

    public function import_bot($data, $user_id, $team_id) {
        $name = ($data['meta']['name'] ?? $data['name'] ?? 'Bot importado');
        $bot_data = [
            'name' => $name,
            'description' => $data['meta']['description'] ?? $data['description'] ?? '',
            'trigger_keywords' => $data['meta']['keywords'] ?? $data['trigger_keywords'] ?? '',
            'status' => 0,
            'team_id' => $team_id,
            'created_by' => $user_id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->table('sp_bot_builders')->insert($bot_data);
        $bot_id = $this->db->insertID();

        $blocks = $data['blocks'] ?? [];
        $edges = $data['edges'] ?? [];

        // Remap IDs - use random_bytes for guaranteed uniqueness
        $idMap = [];
        $counter = 0;
        foreach($blocks as &$b) {
            $old = is_object($b) ? $b->id : ($b['id'] ?? '');
            $new = 'blk_' . bin2hex(random_bytes(8)) . '_' . $counter;
            $counter++;
            $idMap[$old] = $new;
            if(is_object($b)) $b->id = $new; else $b['id'] = $new;
        }
        unset($b); // CRITICAL: break dangling reference to prevent array corruption

        $formattedBlocks = [];
        foreach($blocks as $b) {
            $b = (array)$b;
            $d = $b['data'] ?? [];
            if(is_string($d)) $d = json_decode($d, true);
            $formattedBlocks[] = [
                'id' => $b['id'],
                'type' => $b['type'] ?? $b['node_type'] ?? 'text',
                'pos_x' => $b['pos_x'] ?? $b['position_x'] ?? 0,
                'pos_y' => $b['pos_y'] ?? $b['position_y'] ?? 0,
                'data' => $d
            ];
        }

        $formattedEdges = [];
        foreach($edges as $e) {
            $e = (array)$e;
            $eData = $e['data'] ?? [];
            if(is_string($eData)) $eData = json_decode($eData, true) ?: [];
            $src = $e['source'] ?? $e['from_block_id'] ?? $e['source_id'] ?? '';
            $tgt = $e['target'] ?? $e['to_block_id'] ?? $e['target_id'] ?? '';
            $formattedEdges[] = [
                'id' => 'edge_' . bin2hex(random_bytes(8)) . '_' . $counter,
                'source' => $idMap[$src] ?? $src,
                'target' => $idMap[$tgt] ?? $tgt,
                'data' => [
                    'condition_type' => $eData['condition_type'] ?? $e['condition_type'] ?? null,
                    'condition_value' => $eData['condition_value'] ?? $e['condition_value'] ?? $e['handle_id'] ?? null
                ]
            ];
            $counter++;
        }

        $this->save_flow($bot_id, $formattedBlocks, $formattedEdges);
        return $bot_id;
    }

    public function create_empty_bot($user_id, $team_id, $name) {
        $this->db->table('sp_bot_builders')->insert([
            'name' => $name,
            'team_id' => $team_id,
            'created_by' => $user_id,
            'status' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $id = $this->db->insertID();

        // Add Start node
        $startId = uniqid('start_');
        $this->db->table('sp_bb_blocks')->insert([
            'id' => $startId,
            'bot_id' => $id,
            'type' => 'start',
            'data' => json_encode(['uid' => $startId]),
            'pos_x' => 200,
            'pos_y' => 200,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $this->db->table('sp_bot_builders')->where('id', $id)->update(['start_block_id' => $startId]);

        return $id;
    }

    // ===================== TEMPLATES =====================

    public function get_templates($category = null) {
        $q = $this->db->table('sp_bb_templates')->where('status', 1);
        if($category) $q->where('category', $category);
        return $q->orderBy('use_count', 'DESC')->get()->getResult();
    }

    public function get_template($id) {
        return $this->db->table('sp_bb_templates')->where('id', $id)->get()->getRow();
    }

    public function get_template_categories() {
        return $this->db->table('sp_bb_templates')
            ->select('category, COUNT(*) as count')
            ->where('status', 1)
            ->groupBy('category')
            ->get()->getResult();
    }

    public function install_template($template_id, $user_id, $team_id) {
        $tpl = $this->get_template($template_id);
        if(!$tpl) throw new \Exception('Modelo não encontrado');

        $flow = json_decode($tpl->schema_json, true);
        if(!$flow || !isset($flow['blocks'])) throw new \Exception('Modelo inválido');

        $data = [
            'name' => $tpl->name,
            'description' => $tpl->description,
            'blocks' => $flow['blocks'],
            'edges' => $flow['edges'] ?? [],
            'meta' => ['name' => $tpl->name, 'description' => $tpl->description]
        ];

        $bot_id = $this->import_bot($data, $user_id, $team_id);
        $this->db->table('sp_bb_templates')->where('id', $template_id)->set('use_count', 'use_count + 1', false)->update();

        return $bot_id;
    }

    public function track_template_usage($template_id, $user_id, $bot_id) {
        $this->db->table('sp_bb_template_usage')->insert([
            'template_id' => $template_id,
            'user_id' => $user_id,
            'bot_id' => $bot_id,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    // ===================== SEED =====================

    /**
     * Re-seed templates if they haven't been updated to v2.
     * Called from auto_migrate so existing installs get the fix.
     */
    public function reseed_if_needed() {
        $this->safe_add_column('sp_bb_templates', 'seed_version', "INT(11) DEFAULT 0 AFTER `status`");

        $check = $this->db->table('sp_bb_templates')
            ->where('seed_version >=', 3)
            ->countAllResults();

        if($check == 0) {
            // Drop old seeds and re-create with production flows
            $this->db->table('sp_bb_templates')->truncate();
            $this->seed_templates();
        }
    }

    private function seed_templates() {
        $templates = [
            // ══════ MARKETING (6) ══════
            ['name'=>'Funil de captura de leads','description'=>'Capture nome, e-mail e telefone em um fluxo simples de qualificação.','category'=>'Marketing','icon'=>'fad fa-funnel-dollar','icon_color'=>'#10b981','schema_json'=>json_encode($this->build_lead_gen_flow())],
            ['name'=>'Opt-in para promoções','description'=>'Permita que contatos entrem ou saiam de listas promocionais.','category'=>'Marketing','icon'=>'fad fa-bullhorn','icon_color'=>'#f59e0b','schema_json'=>json_encode($this->build_promo_optin_flow())],
            ['name'=>'Inscrição para webinar','description'=>'Colete inscrições para eventos com data, nome e e-mail.','category'=>'Marketing','icon'=>'fad fa-video','icon_color'=>'#6366f1','schema_json'=>json_encode($this->build_webinar_flow())],
            ['name'=>'Distribuição de cupons','description'=>'Entregue cupons de desconto depois de coletar os dados do contato.','category'=>'Marketing','icon'=>'fad fa-tags','icon_color'=>'#ec4899','schema_json'=>json_encode($this->build_coupon_flow())],
            ['name'=>'Lembrete de evento','description'=>'Envie lembretes e confirme presença usando botões.','category'=>'Marketing','icon'=>'fad fa-calendar-star','icon_color'=>'#8b5cf6','schema_json'=>json_encode($this->build_event_reminder_flow())],
            ['name'=>'Campanha de indicação','description'=>'Crie campanhas de indicação com dados do indicador e códigos de recompensa.','category'=>'Marketing','icon'=>'fad fa-share-alt','icon_color'=>'#14b8a6','is_premium'=>1,'schema_json'=>json_encode($this->build_referral_flow())],
            // ══════ SALES (5) ══════
            ['name'=>'Consulta de produto','description'=>'Receba dúvidas sobre produtos e encaminhe contatos ao time comercial.','category'=>'Vendas','icon'=>'fad fa-shopping-bag','icon_color'=>'#f97316','schema_json'=>json_encode($this->build_product_inquiry_flow())],
            ['name'=>'Qualificação de vendas','description'=>'Qualifique leads com perguntas sobre orçamento, prazo e decisão.','category'=>'Vendas','icon'=>'fad fa-filter','icon_color'=>'#10b981','is_premium'=>1,'schema_json'=>json_encode($this->build_sales_qual_flow())],
            ['name'=>'Solicitação de orçamento','description'=>'Colete requisitos e entregue uma estimativa comercial personalizada.','category'=>'Vendas','icon'=>'fad fa-dollar-sign','icon_color'=>'#eab308','schema_json'=>json_encode($this->build_pricing_request_flow())],
            ['name'=>'Agendamento de demonstração','description'=>'Agende demonstrações coletando nome, e-mail e melhor horário.','category'=>'Vendas','icon'=>'fad fa-desktop','icon_color'=>'#6366f1','schema_json'=>json_encode($this->build_demo_booking_flow())],
            ['name'=>'Oferta de upsell','description'=>'Apresente ofertas de upgrade para clientes existentes.','category'=>'Vendas','icon'=>'fad fa-chart-line','icon_color'=>'#a855f7','is_premium'=>1,'schema_json'=>json_encode($this->build_upsell_flow())],
            // ══════ SUPPORT (4) ══════
            ['name'=>'FAQ de atendimento','description'=>'Responda dúvidas frequentes de forma automática e reduza tickets repetidos.','category'=>'Atendimento','icon'=>'fad fa-headset','icon_color'=>'#4f46e5','schema_json'=>json_encode($this->build_support_flow())],
            ['name'=>'Criação de ticket','description'=>'Abra tickets coletando descrição do problema e prioridade.','category'=>'Atendimento','icon'=>'fad fa-ticket-alt','icon_color'=>'#ef4444','schema_json'=>json_encode($this->build_ticket_flow())],
            ['name'=>'Problema com pedido','description'=>'Trate reclamações de item errado, avariado ou ausente.','category'=>'Atendimento','icon'=>'fad fa-exclamation-triangle','icon_color'=>'#f59e0b','schema_json'=>json_encode($this->build_order_issue_flow())],
            ['name'=>'Agendador de atendimento','description'=>'Permita que clientes escolham data e horário para agendar.','category'=>'Atendimento','icon'=>'fad fa-calendar-check','icon_color'=>'#06b6d4','schema_json'=>json_encode($this->build_appointment_flow())],
            // ══════ E-COMMERCE (5) ══════
            ['name'=>'Status do pedido','description'=>'Permita que clientes consultem pedidos informando o ID.','category'=>'E-commerce','icon'=>'fad fa-shipping-fast','icon_color'=>'#10b981','schema_json'=>json_encode($this->build_order_status_flow())],
            ['name'=>'Recuperação de carrinho','description'=>'Recupere carrinhos abandonados com lembretes e ofertas.','category'=>'E-commerce','icon'=>'fad fa-cart-arrow-down','icon_color'=>'#ef4444','is_premium'=>1,'schema_json'=>json_encode($this->build_abandoned_cart_flow())],
            ['name'=>'Recomendação de produto','description'=>'Recomende produtos conforme preferências do cliente.','category'=>'E-commerce','icon'=>'fad fa-shopping-cart','icon_color'=>'#f59e0b','schema_json'=>json_encode($this->build_product_flow())],
            ['name'=>'Confirmação de pagamento na entrega','description'=>'Confirme pedidos de pagamento na entrega e colete endereço.','category'=>'E-commerce','icon'=>'fad fa-money-bill-wave','icon_color'=>'#22c55e','schema_json'=>json_encode($this->build_cod_flow())],
            ['name'=>'Link de pagamento','description'=>'Gere e envie links de pagamento para checkout rápido.','category'=>'E-commerce','icon'=>'fad fa-credit-card','icon_color'=>'#8b5cf6','is_premium'=>1,'schema_json'=>json_encode($this->build_payment_link_flow())],
            // ══════ AI BOTS (10) ══════
            ['name'=>'IA para atendimento','description'=>'Atendimento com IA usando Gemini/GPT para conversas naturais.','category'=>'IA','icon'=>'fad fa-sparkles','icon_color'=>'#a855f7','schema_json'=>json_encode($this->build_ai_flow_prompt('Você é um agente de atendimento educado e útil. Responda com clareza e ofereça encaminhar para um humano quando necessário.'))],
            ['name'=>'IA assistente de vendas','description'=>'Assistente comercial que qualifica leads e responde dúvidas de produto.','category'=>'IA','icon'=>'fad fa-robot','icon_color'=>'#6366f1','schema_json'=>json_encode($this->build_ai_flow_prompt('Você é um assistente de vendas amigável. Ajude o cliente a encontrar o produto certo, responda dúvidas de preço e conduza para a compra.'))],
            ['name'=>'IA consultora de produto','description'=>'Recomenda produtos com base nas necessidades do cliente.','category'=>'IA','icon'=>'fad fa-lightbulb','icon_color'=>'#f59e0b','schema_json'=>json_encode($this->build_ai_flow_prompt('Você é um consultor de produtos. Pergunte sobre necessidades, orçamento e preferências, depois recomende as melhores opções com prós e contras.'))],
            ['name'=>'IA para agendamentos','description'=>'Ajuda usuários a marcar horários e consultar disponibilidade.','category'=>'IA','icon'=>'fad fa-calendar-alt','icon_color'=>'#06b6d4','schema_json'=>json_encode($this->build_ai_flow_prompt('Você é um assistente de agendamento. Ajude usuários a marcar atendimentos perguntando data, horário e tipo de serviço. Confirme tudo com educação.'))],
            ['name'=>'IA para FAQ','description'=>'Responde perguntas frequentes usando a base de conhecimento.','category'=>'IA','icon'=>'fad fa-question-circle','icon_color'=>'#10b981','schema_json'=>json_encode($this->build_ai_flow_prompt('Você é um bot de FAQ. Responda dúvidas comuns sobre entrega, troca, preços e conta de forma objetiva e útil.'))],
            ['name'=>'IA qualificadora de leads','description'=>'Qualifica leads com perguntas inteligentes de seguimento.','category'=>'IA','icon'=>'fad fa-user-check','icon_color'=>'#ec4899','is_premium'=>1,'schema_json'=>json_encode($this->build_ai_flow_prompt('Você é uma IA de qualificação de leads. Pergunte sobre tamanho da empresa, orçamento, prazo e dores. Classifique o lead como quente, morno ou frio.'))],
            ['name'=>'IA para imóveis','description'=>'Assistente para consultas de imóveis, visitas e valores.','category'=>'IA','icon'=>'fad fa-home','icon_color'=>'#f97316','is_premium'=>1,'schema_json'=>json_encode($this->build_ai_flow_prompt('Você é um assistente imobiliário. Ajude usuários a encontrar imóveis por localização, orçamento e quantidade de quartos. Ofereça agendar visitas.'))],
            ['name'=>'IA para triagem de RH','description'=>'Conduz uma triagem inicial de candidatos.','category'=>'IA','icon'=>'fad fa-user-tie','icon_color'=>'#6366f1','is_premium'=>1,'schema_json'=>json_encode($this->build_ai_flow_prompt('Você é um bot de triagem de RH. Pergunte sobre experiência, habilidades, disponibilidade e pretensão salarial com tom profissional e acolhedor.'))],
            ['name'=>'IA auxiliar de e-commerce','description'=>'Ajuda clientes a buscar produtos, tamanhos, pedidos e trocas.','category'=>'IA','icon'=>'fad fa-store','icon_color'=>'#22c55e','schema_json'=>json_encode($this->build_ai_flow_prompt('Você é um assistente de e-commerce. Ajude clientes a encontrar produtos, conferir tamanhos, rastrear pedidos e lidar com trocas de forma amigável.'))],
            ['name'=>'IA conversa geral','description'=>'Chatbot de IA para conversas e dúvidas gerais.','category'=>'IA','icon'=>'fad fa-comments','icon_color'=>'#8b5cf6','schema_json'=>json_encode($this->build_ai_flow_prompt('Você é um assistente de IA amigável e útil. Responda perguntas com precisão, clareza e tom conversacional.'))],
        ];

        foreach($templates as $t) {
            $t['status'] = 1;
            $t['seed_version'] = 3;
            $t['is_premium'] = $t['is_premium'] ?? 0;
            $t['use_count'] = rand(10, 500);
            $t['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('sp_bb_templates')->insert($t);
        }
    }

    // ─────────────────────────────────────────────────
    //  FLOW BUILDERS — Production-ready templates
    //  All use node types from editor's NODE_DEFS
    // ─────────────────────────────────────────────────

    private function uid() { return bin2hex(random_bytes(8)); }

    private function build_support_flow() {
        $s  = 'start_'.$this->uid();
        $t1 = 'text_'.$this->uid();
        $b1 = 'btn_'.$this->uid();
        $t2 = 'text_'.$this->uid();
        $t3 = 'text_'.$this->uid();
        $t4 = 'text_'.$this->uid();
        $inp = 'inp_'.$this->uid();
        $t5 = 'text_'.$this->uid();
        $e  = 'end_'.$this->uid();

        return [
            'blocks' => [
                ['id'=>$s,   'type'=>'start',      'data'=>['uid'=>$s], 'pos_x'=>100, 'pos_y'=>250],
                ['id'=>$t1,  'type'=>'text',       'data'=>['uid'=>$t1, 'text'=>'👋 Hello! Welcome to our support center. How can I help you today?'], 'pos_x'=>400, 'pos_y'=>250],
                ['id'=>$b1,  'type'=>'buttons',    'data'=>['uid'=>$b1, 'text'=>'Choose a topic:', 'options'=>'💰 Pricing,🔧 Technical Issue,📦 Order Status', 'variable'=>'topic'], 'pos_x'=>750, 'pos_y'=>250],
                ['id'=>$t2,  'type'=>'text',       'data'=>['uid'=>$t2, 'text'=>"💰 *Pricing Plans*\n\n🟢 Starter — $9/mo\n🔵 Pro — $29/mo\n🟣 Enterprise — $99/mo\n\nVisit our pricing page for full details!"], 'pos_x'=>1100, 'pos_y'=>50],
                ['id'=>$t3,  'type'=>'text',       'data'=>['uid'=>$t3, 'text'=>'🔧 For technical issues, please describe your problem and our team will get back to you within 1 hour.'], 'pos_x'=>1100, 'pos_y'=>250],
                ['id'=>$inp, 'type'=>'input_text',  'data'=>['uid'=>$inp, 'question'=>'📋 Please enter your order number:', 'variable'=>'order_number', 'placeholder'=>'e.g. ORD-12345', 'required'=>'true', 'button_label'=>'Submit'], 'pos_x'=>1100, 'pos_y'=>450],
                ['id'=>$t4,  'type'=>'text',       'data'=>['uid'=>$t4, 'text'=>'📦 Checking order #{{order_number}}... Your order is being processed and should arrive in 3-5 business days!'], 'pos_x'=>1450, 'pos_y'=>450],
                ['id'=>$t5,  'type'=>'text',       'data'=>['uid'=>$t5, 'text'=>'Thank you for contacting us! Is there anything else I can help with? 😊'], 'pos_x'=>1450, 'pos_y'=>250],
                ['id'=>$e,   'type'=>'end',        'data'=>['uid'=>$e], 'pos_x'=>1800, 'pos_y'=>250],
            ],
            'edges' => [
                ['source'=>$s,   'target'=>$t1,  'data'=>['condition_value'=>'default']],
                ['source'=>$t1,  'target'=>$b1,  'data'=>['condition_value'=>'default']],
                ['source'=>$b1,  'target'=>$t2,  'data'=>['condition_value'=>'Pricing']],
                ['source'=>$b1,  'target'=>$t3,  'data'=>['condition_value'=>'Technical Issue']],
                ['source'=>$b1,  'target'=>$inp, 'data'=>['condition_value'=>'Order Status']],
                ['source'=>$inp, 'target'=>$t4,  'data'=>['condition_value'=>'default']],
                ['source'=>$t2,  'target'=>$t5,  'data'=>['condition_value'=>'default']],
                ['source'=>$t3,  'target'=>$t5,  'data'=>['condition_value'=>'default']],
                ['source'=>$t4,  'target'=>$t5,  'data'=>['condition_value'=>'default']],
                ['source'=>$t5,  'target'=>$e,   'data'=>['condition_value'=>'default']],
            ]
        ];
    }

    private function build_lead_gen_flow() {
        $s  = 'start_'.$this->uid();
        $t1 = 'text_'.$this->uid();
        $n  = 'name_'.$this->uid();
        $em = 'email_'.$this->uid();
        $ph = 'phone_'.$this->uid();
        $b1 = 'btn_'.$this->uid();
        $ty = 'thanks_'.$this->uid();
        $tn = 'nothanks_'.$this->uid();
        $e  = 'end_'.$this->uid();

        return [
            'blocks' => [
                ['id'=>$s,  'type'=>'start',       'data'=>['uid'=>$s], 'pos_x'=>100, 'pos_y'=>250],
                ['id'=>$t1, 'type'=>'text',        'data'=>['uid'=>$t1, 'text'=>"🎯 Hi there! I'd love to learn more about you so we can help you better. Can I ask a few quick questions?"], 'pos_x'=>400, 'pos_y'=>250],
                ['id'=>$n,  'type'=>'input_text',   'data'=>['uid'=>$n,  'question'=>'👤 What\'s your name?', 'variable'=>'name', 'placeholder'=>'Your full name', 'required'=>'true', 'button_label'=>'Next →'], 'pos_x'=>750, 'pos_y'=>250],
                ['id'=>$em, 'type'=>'input_email',  'data'=>['uid'=>$em, 'question'=>'📧 Great, {{name}}! What\'s your email address?', 'variable'=>'email', 'placeholder'=>'you@company.com', 'required'=>'true', 'button_label'=>'Next →'], 'pos_x'=>1100, 'pos_y'=>250],
                ['id'=>$ph, 'type'=>'input_phone',  'data'=>['uid'=>$ph, 'question'=>'📱 And your phone number?', 'variable'=>'phone_number', 'placeholder'=>'+1 234 567 8900', 'required'=>'false', 'button_label'=>'Next →'], 'pos_x'=>1450, 'pos_y'=>250],
                ['id'=>$b1, 'type'=>'buttons',     'data'=>['uid'=>$b1, 'text'=>'Are you interested in a free consultation?', 'options'=>'✅ Yes, I\'m interested!,❌ Not right now', 'variable'=>'interest'], 'pos_x'=>1800, 'pos_y'=>250],
                ['id'=>$ty, 'type'=>'text',        'data'=>['uid'=>$ty, 'text'=>"🎉 Awesome, {{name}}! Our team will reach out to you at {{email}} within 24 hours.\n\nThank you for your interest! 🙌"], 'pos_x'=>2150, 'pos_y'=>150],
                ['id'=>$tn, 'type'=>'text',        'data'=>['uid'=>$tn, 'text'=>'No problem, {{name}}! Feel free to reach out anytime you\'re ready. Have a great day! 👋'], 'pos_x'=>2150, 'pos_y'=>400],
                ['id'=>$e,  'type'=>'end',         'data'=>['uid'=>$e], 'pos_x'=>2500, 'pos_y'=>250],
            ],
            'edges' => [
                ['source'=>$s,  'target'=>$t1, 'data'=>['condition_value'=>'default']],
                ['source'=>$t1, 'target'=>$n,  'data'=>['condition_value'=>'default']],
                ['source'=>$n,  'target'=>$em, 'data'=>['condition_value'=>'default']],
                ['source'=>$em, 'target'=>$ph, 'data'=>['condition_value'=>'default']],
                ['source'=>$ph, 'target'=>$b1, 'data'=>['condition_value'=>'default']],
                ['source'=>$b1, 'target'=>$ty, 'data'=>['condition_value'=>'Yes']],
                ['source'=>$b1, 'target'=>$tn, 'data'=>['condition_value'=>'Not right now']],
                ['source'=>$ty, 'target'=>$e,  'data'=>['condition_value'=>'default']],
                ['source'=>$tn, 'target'=>$e,  'data'=>['condition_value'=>'default']],
            ]
        ];
    }

    private function build_product_flow() {
        $s  = 'start_'.$this->uid();
        $t1 = 'text_'.$this->uid();
        $b1 = 'btn_'.$this->uid();
        $p1 = 'prod1_'.$this->uid();
        $p2 = 'prod2_'.$this->uid();
        $p3 = 'prod3_'.$this->uid();
        $b2 = 'btn2_'.$this->uid();
        $ty = 'thanks_'.$this->uid();
        $e  = 'end_'.$this->uid();

        return [
            'blocks' => [
                ['id'=>$s,  'type'=>'start',   'data'=>['uid'=>$s], 'pos_x'=>100, 'pos_y'=>250],
                ['id'=>$t1, 'type'=>'text',    'data'=>['uid'=>$t1, 'text'=>"🛍️ Welcome to our shop! Browse our latest products below 👇"], 'pos_x'=>400, 'pos_y'=>250],
                ['id'=>$b1, 'type'=>'buttons', 'data'=>['uid'=>$b1, 'text'=>'What are you looking for?', 'options'=>'📱 Electronics,👕 Fashion,🏠 Home & Garden', 'variable'=>'category'], 'pos_x'=>750, 'pos_y'=>250],
                ['id'=>$p1, 'type'=>'text',    'data'=>['uid'=>$p1, 'text'=>"📱 *Electronics*\n\n🔸 Smart Watch — \$199\n🔸 Wireless Earbuds — \$79\n🔸 Portable Charger — \$39\n\n✨ Free shipping on orders over \$100!"], 'pos_x'=>1100, 'pos_y'=>50],
                ['id'=>$p2, 'type'=>'text',    'data'=>['uid'=>$p2, 'text'=>"👕 *Fashion*\n\n🔸 Summer Collection — From \$29\n🔸 Premium T-Shirts — \$49\n🔸 Designer Jackets — \$129\n\n🏷️ Use code WHATSAPP10 for 10% off!"], 'pos_x'=>1100, 'pos_y'=>250],
                ['id'=>$p3, 'type'=>'text',    'data'=>['uid'=>$p3, 'text'=>"🏠 *Home & Garden*\n\n🔸 Smart LED Lights — \$59\n🔸 Plant Collection — From \$19\n🔸 Cozy Throw Blankets — \$45\n\n🌿 Free plant care guide with every purchase!"], 'pos_x'=>1100, 'pos_y'=>450],
                ['id'=>$b2, 'type'=>'buttons', 'data'=>['uid'=>$b2, 'text'=>'Would you like to place an order?', 'options'=>'🛒 Order Now,💬 Talk to Sales', 'variable'=>'action'], 'pos_x'=>1450, 'pos_y'=>250],
                ['id'=>$ty, 'type'=>'text',    'data'=>['uid'=>$ty, 'text'=>'🎉 Great choice! Our sales team will reach out shortly to finalize your order. Thank you for shopping with us! 💛'], 'pos_x'=>1800, 'pos_y'=>250],
                ['id'=>$e,  'type'=>'end',     'data'=>['uid'=>$e], 'pos_x'=>2150, 'pos_y'=>250],
            ],
            'edges' => [
                ['source'=>$s,  'target'=>$t1, 'data'=>['condition_value'=>'default']],
                ['source'=>$t1, 'target'=>$b1, 'data'=>['condition_value'=>'default']],
                ['source'=>$b1, 'target'=>$p1, 'data'=>['condition_value'=>'Electronics']],
                ['source'=>$b1, 'target'=>$p2, 'data'=>['condition_value'=>'Fashion']],
                ['source'=>$b1, 'target'=>$p3, 'data'=>['condition_value'=>'Home & Garden']],
                ['source'=>$p1, 'target'=>$b2, 'data'=>['condition_value'=>'default']],
                ['source'=>$p2, 'target'=>$b2, 'data'=>['condition_value'=>'default']],
                ['source'=>$p3, 'target'=>$b2, 'data'=>['condition_value'=>'default']],
                ['source'=>$b2, 'target'=>$ty, 'data'=>['condition_value'=>'default']],
                ['source'=>$ty, 'target'=>$e,  'data'=>['condition_value'=>'default']],
            ]
        ];
    }

    private function build_appointment_flow() {
        $s  = 'start_'.$this->uid();
        $t1 = 'text_'.$this->uid();
        $n  = 'name_'.$this->uid();
        $d  = 'date_'.$this->uid();
        $tm = 'time_'.$this->uid();
        $ph = 'phone_'.$this->uid();
        $t2 = 'text_'.$this->uid();
        $e  = 'end_'.$this->uid();

        return [
            'blocks' => [
                ['id'=>$s,  'type'=>'start',       'data'=>['uid'=>$s], 'pos_x'=>100, 'pos_y'=>250],
                ['id'=>$t1, 'type'=>'text',        'data'=>['uid'=>$t1, 'text'=>'📅 Let\'s book your appointment! I\'ll need a few details.'], 'pos_x'=>400, 'pos_y'=>250],
                ['id'=>$n,  'type'=>'input_text',   'data'=>['uid'=>$n,  'question'=>'👤 What\'s your full name?', 'variable'=>'client_name', 'required'=>'true', 'button_label'=>'Next'], 'pos_x'=>750, 'pos_y'=>250],
                ['id'=>$d,  'type'=>'input_date',   'data'=>['uid'=>$d,  'question'=>'📅 Pick your preferred date:', 'variable'=>'appt_date', 'required'=>'true', 'button_label'=>'Next'], 'pos_x'=>1100, 'pos_y'=>250],
                ['id'=>$tm, 'type'=>'input_time',   'data'=>['uid'=>$tm, 'question'=>'⏰ What time works best for you?', 'variable'=>'appt_time', 'required'=>'true', 'button_label'=>'Next'], 'pos_x'=>1450, 'pos_y'=>250],
                ['id'=>$ph, 'type'=>'input_phone',  'data'=>['uid'=>$ph, 'question'=>'📱 Contact phone number:', 'variable'=>'contact_phone', 'required'=>'true', 'button_label'=>'Confirm'], 'pos_x'=>1800, 'pos_y'=>250],
                ['id'=>$t2, 'type'=>'text',        'data'=>['uid'=>$t2, 'text'=>"✅ *Appointment Confirmed!*\n\n👤 Name: {{client_name}}\n📅 Date: {{appt_date}}\n⏰ Time: {{appt_time}}\n📱 Phone: {{contact_phone}}\n\nWe'll send you a reminder 24 hours before. See you then! 🎉"], 'pos_x'=>2150, 'pos_y'=>250],
                ['id'=>$e,  'type'=>'end',         'data'=>['uid'=>$e], 'pos_x'=>2500, 'pos_y'=>250],
            ],
            'edges' => [
                ['source'=>$s,  'target'=>$t1, 'data'=>['condition_value'=>'default']],
                ['source'=>$t1, 'target'=>$n,  'data'=>['condition_value'=>'default']],
                ['source'=>$n,  'target'=>$d,  'data'=>['condition_value'=>'default']],
                ['source'=>$d,  'target'=>$tm, 'data'=>['condition_value'=>'default']],
                ['source'=>$tm, 'target'=>$ph, 'data'=>['condition_value'=>'default']],
                ['source'=>$ph, 'target'=>$t2, 'data'=>['condition_value'=>'default']],
                ['source'=>$t2, 'target'=>$e,  'data'=>['condition_value'=>'default']],
            ]
        ];
    }

    private function build_buttons_demo_flow() {
        $s  = 'start_'.$this->uid();
        $t1 = 'text_'.$this->uid();
        $b1 = 'btn_'.$this->uid();
        $q1 = 'q1_'.$this->uid();
        $b2 = 'btn2_'.$this->uid();
        $r1 = 'r1_'.$this->uid();
        $r2 = 'r2_'.$this->uid();
        $e  = 'end_'.$this->uid();

        return [
            'blocks' => [
                ['id'=>$s,  'type'=>'start',   'data'=>['uid'=>$s], 'pos_x'=>100, 'pos_y'=>250],
                ['id'=>$t1, 'type'=>'text',    'data'=>['uid'=>$t1, 'text'=>"🎯 *Lead Scoring Quiz*\n\nAnswer 2 quick questions and we'll personalize your experience!"], 'pos_x'=>400, 'pos_y'=>250],
                ['id'=>$b1, 'type'=>'buttons', 'data'=>['uid'=>$b1, 'text'=>'What\'s your company size?', 'options'=>'1-10 employees,11-50 employees,50+ employees', 'variable'=>'company_size'], 'pos_x'=>750, 'pos_y'=>250],
                ['id'=>$q1, 'type'=>'input_text', 'data'=>['uid'=>$q1, 'question'=>'🏢 What industry are you in?', 'variable'=>'industry', 'placeholder'=>'e.g. SaaS, Healthcare, E-commerce', 'required'=>'true', 'button_label'=>'Next →'], 'pos_x'=>1100, 'pos_y'=>250],
                ['id'=>$b2, 'type'=>'buttons', 'data'=>['uid'=>$b2, 'text'=>'What\'s your biggest challenge right now?', 'options'=>'📈 Growth,💰 Revenue,🤝 Customer Retention', 'variable'=>'challenge'], 'pos_x'=>1450, 'pos_y'=>250],
                ['id'=>$r1, 'type'=>'text',    'data'=>['uid'=>$r1, 'text'=>"🏆 *Your Profile*\n\n🏢 Company size: {{company_size}}\n🏭 Industry: {{industry}}\n🎯 Challenge: {{challenge}}\n\n✅ Based on your answers, you're a great fit for our *Growth Plan*! A specialist will reach out shortly."], 'pos_x'=>1800, 'pos_y'=>150],
                ['id'=>$r2, 'type'=>'text',    'data'=>['uid'=>$r2, 'text'=>'Thanks for taking our quiz! 📋 We\'ll review your profile and get back to you with personalized recommendations.'], 'pos_x'=>1800, 'pos_y'=>400],
                ['id'=>$e,  'type'=>'end',     'data'=>['uid'=>$e], 'pos_x'=>2150, 'pos_y'=>250],
            ],
            'edges' => [
                ['source'=>$s,  'target'=>$t1, 'data'=>['condition_value'=>'default']],
                ['source'=>$t1, 'target'=>$b1, 'data'=>['condition_value'=>'default']],
                ['source'=>$b1, 'target'=>$q1, 'data'=>['condition_value'=>'default']],
                ['source'=>$q1, 'target'=>$b2, 'data'=>['condition_value'=>'default']],
                ['source'=>$b2, 'target'=>$r1, 'data'=>['condition_value'=>'Growth']],
                ['source'=>$b2, 'target'=>$r2, 'data'=>['condition_value'=>'default']],
                ['source'=>$r1, 'target'=>$e,  'data'=>['condition_value'=>'default']],
                ['source'=>$r2, 'target'=>$e,  'data'=>['condition_value'=>'default']],
            ]
        ];
    }

    private function build_ai_flow() {
        $s  = 'start_'.$this->uid();
        $t1 = 'text_'.$this->uid();
        $ai = 'ai_'.$this->uid();
        $t2 = 'text_'.$this->uid();
        $e  = 'end_'.$this->uid();

        return [
            'blocks' => [
                ['id'=>$s,  'type'=>'start',    'data'=>['uid'=>$s], 'pos_x'=>100, 'pos_y'=>250],
                ['id'=>$t1, 'type'=>'text',     'data'=>['uid'=>$t1, 'text'=>'🤖 Hi! I\'m your AI assistant. Ask me anything and I\'ll do my best to help!'], 'pos_x'=>400, 'pos_y'=>250],
                ['id'=>$ai, 'type'=>'ai_reply', 'data'=>['uid'=>$ai, 'prompt'=>"You are a helpful, friendly customer support agent for a SaaS company. Be concise, professional, and always try to solve the customer's issue. If you can't help, offer to escalate to a human agent.", 'model'=>'gemini', 'temperature'=>'0.7', 'max_tokens'=>'500'], 'pos_x'=>750, 'pos_y'=>250],
                ['id'=>$t2, 'type'=>'text',     'data'=>['uid'=>$t2, 'text'=>'Thank you for chatting with me! If you need more help, just send another message. 😊'], 'pos_x'=>1100, 'pos_y'=>250],
                ['id'=>$e,  'type'=>'end',      'data'=>['uid'=>$e], 'pos_x'=>1450, 'pos_y'=>250],
            ],
            'edges' => [
                ['source'=>$s,  'target'=>$t1, 'data'=>['condition_value'=>'default']],
                ['source'=>$t1, 'target'=>$ai, 'data'=>['condition_value'=>'default']],
                ['source'=>$ai, 'target'=>$t2, 'data'=>['condition_value'=>'default']],
                ['source'=>$t2, 'target'=>$e,  'data'=>['condition_value'=>'default']],
            ]
        ];
    }

    private function build_reengagement_flow() {
        $s  = 'start_'.$this->uid();
        $t1 = 'text_'.$this->uid();
        $b1 = 'btn_'.$this->uid();
        $t2 = 'text_'.$this->uid();
        $t3 = 'text_'.$this->uid();
        $em = 'email_'.$this->uid();
        $t4 = 'text_'.$this->uid();
        $e  = 'end_'.$this->uid();

        return [
            'blocks' => [
                ['id'=>$s,  'type'=>'start',       'data'=>['uid'=>$s], 'pos_x'=>100, 'pos_y'=>250],
                ['id'=>$t1, 'type'=>'text',        'data'=>['uid'=>$t1, 'text'=>"👋 Hey there! We noticed you haven't visited us in a while.\n\n🎁 Here's an exclusive *20% OFF* code just for you: **COMEBACK20**"], 'pos_x'=>400, 'pos_y'=>250],
                ['id'=>$b1, 'type'=>'buttons',     'data'=>['uid'=>$b1, 'text'=>'Would you like to claim your discount?', 'options'=>'🎁 Yes! Claim it,📋 Tell me more,🚫 No thanks', 'variable'=>'response'], 'pos_x'=>750, 'pos_y'=>250],
                ['id'=>$t2, 'type'=>'text',        'data'=>['uid'=>$t2, 'text'=>"🎉 Awesome! Use code **COMEBACK20** at checkout.\n\nValid for 48 hours. Happy shopping! 🛍️"], 'pos_x'=>1100, 'pos_y'=>50],
                ['id'=>$t3, 'type'=>'text',        'data'=>['uid'=>$t3, 'text'=>"📋 *What's new:*\n\n✨ Redesigned product line\n🚀 Faster shipping (now 2-day!)\n💰 New loyalty rewards program\n🎁 Your 20% off code: COMEBACK20"], 'pos_x'=>1100, 'pos_y'=>250],
                ['id'=>$em, 'type'=>'input_email',  'data'=>['uid'=>$em, 'question'=>'📧 Enter your email and we\'ll send you the coupon:', 'variable'=>'email', 'required'=>'false', 'button_label'=>'Send'], 'pos_x'=>1100, 'pos_y'=>450],
                ['id'=>$t4, 'type'=>'text',        'data'=>['uid'=>$t4, 'text'=>'Thank you! We hope to see you again soon. 💛 Have a wonderful day!'], 'pos_x'=>1450, 'pos_y'=>250],
                ['id'=>$e,  'type'=>'end',         'data'=>['uid'=>$e], 'pos_x'=>1800, 'pos_y'=>250],
            ],
            'edges' => [
                ['source'=>$s,  'target'=>$t1, 'data'=>['condition_value'=>'default']],
                ['source'=>$t1, 'target'=>$b1, 'data'=>['condition_value'=>'default']],
                ['source'=>$b1, 'target'=>$t2, 'data'=>['condition_value'=>'Yes']],
                ['source'=>$b1, 'target'=>$t3, 'data'=>['condition_value'=>'Tell me more']],
                ['source'=>$b1, 'target'=>$em, 'data'=>['condition_value'=>'No thanks']],
                ['source'=>$t2, 'target'=>$t4, 'data'=>['condition_value'=>'default']],
                ['source'=>$t3, 'target'=>$t4, 'data'=>['condition_value'=>'default']],
                ['source'=>$em, 'target'=>$t4, 'data'=>['condition_value'=>'default']],
                ['source'=>$t4, 'target'=>$e,  'data'=>['condition_value'=>'default']],
            ]
        ];
    }

    private function build_hr_flow() {
        $s  = 'start_'.$this->uid();
        $t1 = 'text_'.$this->uid();
        $n  = 'name_'.$this->uid();
        $em = 'email_'.$this->uid();
        $d  = 'date_'.$this->uid();
        $b1 = 'btn_'.$this->uid();
        $t2 = 'text_'.$this->uid();
        $t3 = 'text_'.$this->uid();
        $e  = 'end_'.$this->uid();

        return [
            'blocks' => [
                ['id'=>$s,  'type'=>'start',       'data'=>['uid'=>$s], 'pos_x'=>100, 'pos_y'=>250],
                ['id'=>$t1, 'type'=>'text',        'data'=>['uid'=>$t1, 'text'=>"🏢 *Welcome to the Team!*\n\nCongratulations on joining us! Let's get you set up. I'll guide you through the onboarding process step by step. 🚀"], 'pos_x'=>400, 'pos_y'=>250],
                ['id'=>$n,  'type'=>'input_text',   'data'=>['uid'=>$n,  'question'=>'👤 What\'s your full legal name (for HR records)?', 'variable'=>'employee_name', 'required'=>'true', 'button_label'=>'Next'], 'pos_x'=>750, 'pos_y'=>250],
                ['id'=>$em, 'type'=>'input_email',  'data'=>['uid'=>$em, 'question'=>'📧 Your personal email (for backup communications):', 'variable'=>'personal_email', 'required'=>'true', 'button_label'=>'Next'], 'pos_x'=>1100, 'pos_y'=>250],
                ['id'=>$d,  'type'=>'input_date',   'data'=>['uid'=>$d,  'question'=>'📅 When is your start date?', 'variable'=>'start_date', 'required'=>'true', 'button_label'=>'Next'], 'pos_x'=>1450, 'pos_y'=>250],
                ['id'=>$b1, 'type'=>'buttons',     'data'=>['uid'=>$b1, 'text'=>'Have you received your work equipment?', 'options'=>'✅ Yes,❌ Not yet', 'variable'=>'has_equipment'], 'pos_x'=>1800, 'pos_y'=>250],
                ['id'=>$t2, 'type'=>'text',        'data'=>['uid'=>$t2, 'text'=>"📋 *Onboarding Summary for {{employee_name}}*\n\n📧 Email: {{personal_email}}\n📅 Start date: {{start_date}}\n💻 Equipment: {{has_equipment}}\n\nYour onboarding packet has been sent! Check your email for next steps. Welcome aboard! 🎉"], 'pos_x'=>2150, 'pos_y'=>150],
                ['id'=>$t3, 'type'=>'text',        'data'=>['uid'=>$t3, 'text'=>'📦 No worries! Our IT team will ship your equipment ASAP. You should receive it 2 days before {{start_date}}. We\'ll notify you with a tracking number.'], 'pos_x'=>2150, 'pos_y'=>400],
                ['id'=>$e,  'type'=>'end',         'data'=>['uid'=>$e], 'pos_x'=>2500, 'pos_y'=>250],
            ],
            'edges' => [
                ['source'=>$s,  'target'=>$t1, 'data'=>['condition_value'=>'default']],
                ['source'=>$t1, 'target'=>$n,  'data'=>['condition_value'=>'default']],
                ['source'=>$n,  'target'=>$em, 'data'=>['condition_value'=>'default']],
                ['source'=>$em, 'target'=>$d,  'data'=>['condition_value'=>'default']],
                ['source'=>$d,  'target'=>$b1, 'data'=>['condition_value'=>'default']],
                ['source'=>$b1, 'target'=>$t2, 'data'=>['condition_value'=>'Yes']],
                ['source'=>$b1, 'target'=>$t3, 'data'=>['condition_value'=>'Not yet']],
                ['source'=>$t2, 'target'=>$e,  'data'=>['condition_value'=>'default']],
                ['source'=>$t3, 'target'=>$e,  'data'=>['condition_value'=>'default']],
            ]
        ];
    }

    // ══════ NEW: MARKETING FLOWS ══════
    private function build_promo_optin_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $b1='b_'.$this->uid();
        $y='y_'.$this->uid(); $n='n_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"🔥 *Weekly Deals Alert!*\n\nGet exclusive discounts delivered to your WhatsApp every week."],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$b1,'type'=>'buttons','data'=>['uid'=>$b1,'text'=>'Subscribe to weekly deals?','options'=>'✅ Yes, subscribe!,❌ No thanks','variable'=>'optin'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$y,'type'=>'text','data'=>['uid'=>$y,'text'=>"🎯 *You're subscribed!* You'll receive exclusive deals every Friday. 🛍️"],'pos_x'=>1100,'pos_y'=>150],
            ['id'=>$n,'type'=>'text','data'=>['uid'=>$n,'text'=>'No worries! You can subscribe anytime by messaging us again. 😊'],'pos_x'=>1100,'pos_y'=>400],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1450,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$b1,'data'=>['condition_value'=>'default']],
            ['source'=>$b1,'target'=>$y,'data'=>['condition_value'=>'Yes, subscribe!']],
            ['source'=>$b1,'target'=>$n,'data'=>['condition_value'=>'No thanks']],
            ['source'=>$y,'target'=>$e,'data'=>['condition_value'=>'default']],
            ['source'=>$n,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_webinar_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $n='n_'.$this->uid();
        $em='em_'.$this->uid(); $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"📹 *Free Webinar: Grow Your Business*\n\n📅 This Saturday at 3 PM\n⏱️ 45 minutes\n\nRegister now!"],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$n,'type'=>'input_text','data'=>['uid'=>$n,'question'=>'👤 Your full name:','variable'=>'name','required'=>'true','button_label'=>'Next →'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$em,'type'=>'input_email','data'=>['uid'=>$em,'question'=>'📧 Email for the link:','variable'=>'email','required'=>'true','button_label'=>'Register'],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"✅ *Registered, {{name}}!* Check {{email}} for the link. See you Saturday! 🎉"],'pos_x'=>1450,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1800,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$n,'data'=>['condition_value'=>'default']],
            ['source'=>$n,'target'=>$em,'data'=>['condition_value'=>'default']],
            ['source'=>$em,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_coupon_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $n='n_'.$this->uid();
        $ph='ph_'.$this->uid(); $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"🎁 *Exclusive Coupon!*\n\nGet 25% OFF — just share your details."],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$n,'type'=>'input_text','data'=>['uid'=>$n,'question'=>'👤 Your name:','variable'=>'name','required'=>'true','button_label'=>'Next'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$ph,'type'=>'input_phone','data'=>['uid'=>$ph,'question'=>'📱 WhatsApp number:','variable'=>'phone','required'=>'true','button_label'=>'Get Coupon'],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"🎉 Here you go, {{name}}!\n\n🏷️ Code: *SAVE25*\n💰 25% OFF — valid 7 days\n\nHappy shopping! 🛍️"],'pos_x'=>1450,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1800,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$n,'data'=>['condition_value'=>'default']],
            ['source'=>$n,'target'=>$ph,'data'=>['condition_value'=>'default']],
            ['source'=>$ph,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_event_reminder_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $b1='b_'.$this->uid();
        $y='y_'.$this->uid(); $n='n_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"📅 *Event Reminder*\n\n🎤 Annual Tech Conference 2026\n📍 Convention Center\n🕐 March 15, 10 AM"],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$b1,'type'=>'buttons','data'=>['uid'=>$b1,'text'=>'RSVP:','options'=>'✅ I\'ll be there,❌ Can\'t make it','variable'=>'rsvp'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$y,'type'=>'text','data'=>['uid'=>$y,'text'=>"🎉 Confirmed! We'll remind you 1 day before. See you! 🚀"],'pos_x'=>1100,'pos_y'=>150],
            ['id'=>$n,'type'=>'text','data'=>['uid'=>$n,'text'=>"No problem! We'll share the recording afterwards. 📹"],'pos_x'=>1100,'pos_y'=>400],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1450,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$b1,'data'=>['condition_value'=>'default']],
            ['source'=>$b1,'target'=>$y,'data'=>['condition_value'=>'I\'ll be there']],
            ['source'=>$b1,'target'=>$n,'data'=>['condition_value'=>'Can\'t make it']],
            ['source'=>$y,'target'=>$e,'data'=>['condition_value'=>'default']],
            ['source'=>$n,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_referral_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $n='n_'.$this->uid();
        $em='em_'.$this->uid(); $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"🤝 *Refer & Earn!*\n\nRefer a friend — both get \$10 credit!"],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$n,'type'=>'input_text','data'=>['uid'=>$n,'question'=>'👤 Your name:','variable'=>'name','required'=>'true','button_label'=>'Next'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$em,'type'=>'input_email','data'=>['uid'=>$em,'question'=>'📧 Your email:','variable'=>'email','required'=>'true','button_label'=>'Get Code'],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"🎉 Your referral code: *REF-{{name}}*\n\n💰 Share with friends\n📧 Rewards → {{email}}\n\nStart sharing! 🚀"],'pos_x'=>1450,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1800,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$n,'data'=>['condition_value'=>'default']],
            ['source'=>$n,'target'=>$em,'data'=>['condition_value'=>'default']],
            ['source'=>$em,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    // ══════ NEW: SALES FLOWS ══════
    private function build_product_inquiry_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $inp='i_'.$this->uid();
        $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>'👋 Which product are you interested in?'],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$inp,'type'=>'input_text','data'=>['uid'=>$inp,'question'=>'🛍️ Product name:','variable'=>'product','required'=>'true','button_label'=>'Submit'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"✅ Our team will contact you about *{{product}}* within 30 minutes. 🕐"],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1450,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$inp,'data'=>['condition_value'=>'default']],
            ['source'=>$inp,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_sales_qual_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $b1='b_'.$this->uid();
        $b2='b2_'.$this->uid(); $inp='i_'.$this->uid(); $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"🎯 *Sales Qualification*\n\nA few quick questions to find the best solution."],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$b1,'type'=>'buttons','data'=>['uid'=>$b1,'text'=>'Monthly budget?','options'=>'Under $500,$500-$2000,$2000+','variable'=>'budget'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$b2,'type'=>'buttons','data'=>['uid'=>$b2,'text'=>'Timeline?','options'=>'This week,This month,Exploring','variable'=>'timeline'],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$inp,'type'=>'input_text','data'=>['uid'=>$inp,'question'=>'🏢 Company name:','variable'=>'company','required'=>'true','button_label'=>'Submit'],'pos_x'=>1450,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"📋 *Profile*\n🏢 {{company}}\n💰 {{budget}}\n⏱️ {{timeline}}\n\nA rep will call within 1 hour! 🚀"],'pos_x'=>1800,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>2150,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$b1,'data'=>['condition_value'=>'default']],
            ['source'=>$b1,'target'=>$b2,'data'=>['condition_value'=>'default']],
            ['source'=>$b2,'target'=>$inp,'data'=>['condition_value'=>'default']],
            ['source'=>$inp,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_pricing_request_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $inp='i_'.$this->uid();
        $em='em_'.$this->uid(); $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"💰 *Custom Pricing*\n\nTell us your needs and we'll quote you."],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$inp,'type'=>'input_text','data'=>['uid'=>$inp,'question'=>'📋 Requirements:','variable'=>'requirements','required'=>'true','button_label'=>'Next'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$em,'type'=>'input_email','data'=>['uid'=>$em,'question'=>'📧 Email for quote:','variable'=>'email','required'=>'true','button_label'=>'Submit'],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"✅ Quote requested!\n📋 {{requirements}}\n📧 → {{email}}\n\nExpect it within 24h. 🙏"],'pos_x'=>1450,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1800,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$inp,'data'=>['condition_value'=>'default']],
            ['source'=>$inp,'target'=>$em,'data'=>['condition_value'=>'default']],
            ['source'=>$em,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_demo_booking_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $n='n_'.$this->uid();
        $em='em_'.$this->uid(); $d='d_'.$this->uid(); $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"🖥️ *Book a Demo*\n\nSee our platform in action!"],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$n,'type'=>'input_text','data'=>['uid'=>$n,'question'=>'👤 Name:','variable'=>'name','required'=>'true','button_label'=>'Next'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$em,'type'=>'input_email','data'=>['uid'=>$em,'question'=>'📧 Work email:','variable'=>'email','required'=>'true','button_label'=>'Next'],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$d,'type'=>'input_date','data'=>['uid'=>$d,'question'=>'📅 Preferred date:','variable'=>'demo_date','required'=>'true','button_label'=>'Book'],'pos_x'=>1450,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"✅ Booked, {{name}}!\n📅 {{demo_date}}\n📧 Confirmation → {{email}} 🚀"],'pos_x'=>1800,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>2150,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$n,'data'=>['condition_value'=>'default']],
            ['source'=>$n,'target'=>$em,'data'=>['condition_value'=>'default']],
            ['source'=>$em,'target'=>$d,'data'=>['condition_value'=>'default']],
            ['source'=>$d,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_upsell_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $b1='b_'.$this->uid();
        $y='y_'.$this->uid(); $n='n_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"⭐ *Upgrade to Pro!*\n\n✅ Unlimited messages\n✅ AI chatbot\n✅ Priority support\n\n🏷️ *40% OFF* for you!"],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$b1,'type'=>'buttons','data'=>['uid'=>$b1,'text'=>'Upgrade now?','options'=>'🚀 Yes!,❌ Not now','variable'=>'upgrade'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$y,'type'=>'text','data'=>['uid'=>$y,'text'=>"🎉 Click here to upgrade: [Link]\n40% discount auto-applied. Welcome to Pro! 🚀"],'pos_x'=>1100,'pos_y'=>150],
            ['id'=>$n,'type'=>'text','data'=>['uid'=>$n,'text'=>'No problem! Offer valid 48h. Upgrade anytime. 😊'],'pos_x'=>1100,'pos_y'=>400],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1450,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$b1,'data'=>['condition_value'=>'default']],
            ['source'=>$b1,'target'=>$y,'data'=>['condition_value'=>'Yes!']],
            ['source'=>$b1,'target'=>$n,'data'=>['condition_value'=>'Not now']],
            ['source'=>$y,'target'=>$e,'data'=>['condition_value'=>'default']],
            ['source'=>$n,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    // ══════ NEW: SUPPORT FLOWS ══════
    private function build_ticket_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $inp='i_'.$this->uid();
        $b1='b_'.$this->uid(); $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"🎫 *Create Support Ticket*\n\nOur team responds within 2 hours."],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$inp,'type'=>'input_text','data'=>['uid'=>$inp,'question'=>'📝 Describe your issue:','variable'=>'issue','required'=>'true','button_label'=>'Next'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$b1,'type'=>'buttons','data'=>['uid'=>$b1,'text'=>'Priority:','options'=>'🔴 Urgent,🟡 Medium,🟢 Low','variable'=>'priority'],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"✅ *Ticket created!*\n🎫 {{issue}}\n🔖 {{priority}}\n\nResponse within 2h. 🙏"],'pos_x'=>1450,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1800,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$inp,'data'=>['condition_value'=>'default']],
            ['source'=>$inp,'target'=>$b1,'data'=>['condition_value'=>'default']],
            ['source'=>$b1,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_order_issue_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $b1='b_'.$this->uid();
        $w='w_'.$this->uid(); $d='d_'.$this->uid(); $m='m_'.$this->uid();
        $inp='i_'.$this->uid(); $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"📦 *Order Issue*\n\nSorry! Let's fix it."],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$b1,'type'=>'buttons','data'=>['uid'=>$b1,'text'=>'What happened?','options'=>'📦 Wrong item,💔 Damaged,❓ Missing','variable'=>'issue_type'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$w,'type'=>'text','data'=>['uid'=>$w,'text'=>'📦 Correct item shipping ASAP. Keep the wrong one!'],'pos_x'=>1100,'pos_y'=>80],
            ['id'=>$d,'type'=>'text','data'=>['uid'=>$d,'text'=>'💔 Replacement ships today — express, free!'],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$m,'type'=>'text','data'=>['uid'=>$m,'text'=>'❓ Investigating now. Update within 1 hour.'],'pos_x'=>1100,'pos_y'=>420],
            ['id'=>$inp,'type'=>'input_text','data'=>['uid'=>$inp,'question'=>'📋 Order number:','variable'=>'order_id','required'=>'true','button_label'=>'Submit'],'pos_x'=>1450,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"✅ Case filed for #{{order_id}}.\nIssue: {{issue_type}}\nResolution within 24h. 🙏"],'pos_x'=>1800,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>2150,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$b1,'data'=>['condition_value'=>'default']],
            ['source'=>$b1,'target'=>$w,'data'=>['condition_value'=>'Wrong item']],
            ['source'=>$b1,'target'=>$d,'data'=>['condition_value'=>'Damaged']],
            ['source'=>$b1,'target'=>$m,'data'=>['condition_value'=>'Missing']],
            ['source'=>$w,'target'=>$inp,'data'=>['condition_value'=>'default']],
            ['source'=>$d,'target'=>$inp,'data'=>['condition_value'=>'default']],
            ['source'=>$m,'target'=>$inp,'data'=>['condition_value'=>'default']],
            ['source'=>$inp,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    // ══════ NEW: E-COMMERCE FLOWS ══════
    private function build_order_status_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $inp='i_'.$this->uid();
        $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>'📦 *Order Status*\n\nEnter your order ID for real-time tracking.'],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$inp,'type'=>'input_text','data'=>['uid'=>$inp,'question'=>'🔍 Order ID:','variable'=>'order_id','required'=>'true','button_label'=>'Check'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"📦 *#{{order_id}}*\n✅ Being processed\n🚚 Delivery: 3-5 days\n\nTracking info coming soon!"],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1450,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$inp,'data'=>['condition_value'=>'default']],
            ['source'=>$inp,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_abandoned_cart_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $b1='b_'.$this->uid();
        $y='y_'.$this->uid(); $n='n_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"🛒 *Cart Reminder!*\n\nComplete now & get *10% OFF*: BACK10"],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$b1,'type'=>'buttons','data'=>['uid'=>$b1,'text'=>'Complete purchase?','options'=>'🛒 Complete,❌ Remove Cart','variable'=>'action'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$y,'type'=>'text','data'=>['uid'=>$y,'text'=>"🎉 Checkout: [Link]\n🏷️ Code *BACK10* = 10% off!\n⏰ Expires 24h"],'pos_x'=>1100,'pos_y'=>150],
            ['id'=>$n,'type'=>'text','data'=>['uid'=>$n,'text'=>'Cart removed. Save BACK10 for next time! 💛'],'pos_x'=>1100,'pos_y'=>400],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1450,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$b1,'data'=>['condition_value'=>'default']],
            ['source'=>$b1,'target'=>$y,'data'=>['condition_value'=>'Complete']],
            ['source'=>$b1,'target'=>$n,'data'=>['condition_value'=>'Remove Cart']],
            ['source'=>$y,'target'=>$e,'data'=>['condition_value'=>'default']],
            ['source'=>$n,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_cod_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $n='n_'.$this->uid();
        $inp='i_'.$this->uid(); $b1='b_'.$this->uid(); $t2='t2_'.$this->uid();
        $t3='t3_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"💵 *COD Confirmation*\n\nConfirm your order details."],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$n,'type'=>'input_text','data'=>['uid'=>$n,'question'=>'👤 Name:','variable'=>'name','required'=>'true','button_label'=>'Next'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$inp,'type'=>'input_text','data'=>['uid'=>$inp,'question'=>'📍 Full address:','variable'=>'address','required'=>'true','button_label'=>'Next'],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$b1,'type'=>'buttons','data'=>['uid'=>$b1,'text'=>'Confirm COD?','options'=>'✅ Confirm,❌ Cancel','variable'=>'confirm'],'pos_x'=>1450,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"✅ *Confirmed!*\n👤 {{name}}\n📍 {{address}}\n💵 COD\n\nDelivery 2-3 days 🚚"],'pos_x'=>1800,'pos_y'=>150],
            ['id'=>$t3,'type'=>'text','data'=>['uid'=>$t3,'text'=>'Cancelled. Order again anytime! 👋'],'pos_x'=>1800,'pos_y'=>400],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>2150,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$n,'data'=>['condition_value'=>'default']],
            ['source'=>$n,'target'=>$inp,'data'=>['condition_value'=>'default']],
            ['source'=>$inp,'target'=>$b1,'data'=>['condition_value'=>'default']],
            ['source'=>$b1,'target'=>$t2,'data'=>['condition_value'=>'Confirm']],
            ['source'=>$b1,'target'=>$t3,'data'=>['condition_value'=>'Cancel']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
            ['source'=>$t3,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    private function build_payment_link_flow() {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid(); $inp='i_'.$this->uid();
        $em='em_'.$this->uid(); $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>"💳 *Quick Payment*\n\nI'll generate a secure link."],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$inp,'type'=>'input_text','data'=>['uid'=>$inp,'question'=>'💰 Amount:','variable'=>'amount','required'=>'true','button_label'=>'Next'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$em,'type'=>'input_email','data'=>['uid'=>$em,'question'=>'📧 Receipt email:','variable'=>'email','required'=>'true','button_label'=>'Generate'],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>"💳 *Payment Link*\n💰 \${{amount}}\n📧 {{email}}\n🔗 [Pay Now]\n\n🔒 Secure. Expires 24h."],'pos_x'=>1450,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1800,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$inp,'data'=>['condition_value'=>'default']],
            ['source'=>$inp,'target'=>$em,'data'=>['condition_value'=>'default']],
            ['source'=>$em,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }

    // ══════ GENERIC AI FLOW (parameterized prompt) ══════
    private function build_ai_flow_prompt($prompt) {
        $s='start_'.$this->uid(); $t1='t_'.$this->uid();
        $ai='ai_'.$this->uid(); $t2='t2_'.$this->uid(); $e='e_'.$this->uid();
        return ['blocks'=>[
            ['id'=>$s,'type'=>'start','data'=>['uid'=>$s],'pos_x'=>100,'pos_y'=>250],
            ['id'=>$t1,'type'=>'text','data'=>['uid'=>$t1,'text'=>'🤖 Hi! I\'m your AI assistant. Ask me anything!'],'pos_x'=>400,'pos_y'=>250],
            ['id'=>$ai,'type'=>'ai_reply','data'=>['uid'=>$ai,'prompt'=>$prompt,'model'=>'gemini','temperature'=>'0.7','max_tokens'=>'500'],'pos_x'=>750,'pos_y'=>250],
            ['id'=>$t2,'type'=>'text','data'=>['uid'=>$t2,'text'=>'Thanks for chatting! Send another message anytime. 😊'],'pos_x'=>1100,'pos_y'=>250],
            ['id'=>$e,'type'=>'end','data'=>['uid'=>$e],'pos_x'=>1450,'pos_y'=>250],
        ],'edges'=>[
            ['source'=>$s,'target'=>$t1,'data'=>['condition_value'=>'default']],
            ['source'=>$t1,'target'=>$ai,'data'=>['condition_value'=>'default']],
            ['source'=>$ai,'target'=>$t2,'data'=>['condition_value'=>'default']],
            ['source'=>$t2,'target'=>$e,'data'=>['condition_value'=>'default']],
        ]];
    }
}
