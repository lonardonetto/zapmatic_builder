<?php
/**
 * Script de diagnóstico para verificar se a tabela de status existe e se há dados sendo gravados.
 *
 * O tracking de status só grava quando o envio é feito via Cloud API (login_type=1).
 * Campanhas com Baileys (login_type=2) não geram registros.
 */

if (!function_exists('diagnostic_status_table')) {
    function diagnostic_status_table($schedule_id = null, $schedule_item = null) {
        $db = \Config\Database::connect();
        
        // 1. Verificar se a tabela existe
        $table_exists = false;
        try {
            $query = $db->query("SHOW TABLES LIKE 'sp_whatsapp_message_status'");
            $table_exists = $query->getNumRows() > 0;
        } catch (\Exception $e) {
            return [
                'error' => 'Erro ao verificar tabela: ' . $e->getMessage(),
                'table_exists' => false
            ];
        }
        
        $result = [
            'table_exists' => $table_exists,
            'schedule_id' => $schedule_id,
        ];
        
        if (!$table_exists) {
            $result['message'] = 'Tabela sp_whatsapp_message_status NÃO existe. Execute o SQL de migração primeiro.';
            return $result;
        }
        
        // 2. Verificar estrutura da tabela
        try {
            $query = $db->query("DESCRIBE sp_whatsapp_message_status");
            $result['table_structure'] = $query->getResultArray();
        } catch (\Exception $e) {
            $result['error_structure'] = $e->getMessage();
        }
        
        // 3. Contar total de registros
        try {
            $query = $db->query("SELECT COUNT(*) as total FROM sp_whatsapp_message_status");
            $row = $query->getRow();
            $result['total_records'] = (int)($row->total ?? 0);
        } catch (\Exception $e) {
            $result['error_count'] = $e->getMessage();
        }
        
        // 4. Se schedule_id fornecido, verificar registros dessa campanha
        if ($schedule_id) {
            try {
                $query = $db->query(
                    "SELECT status, COUNT(*) as total 
                     FROM sp_whatsapp_message_status 
                     WHERE schedule_id = ? 
                     GROUP BY status",
                    [(int)$schedule_id]
                );
                $result['by_schedule'] = [];
                foreach ($query->getResult() as $row) {
                    $result['by_schedule'][$row->status] = (int)$row->total;
                }
                
                // Últimos 5 registros dessa campanha
                $query = $db->query(
                    "SELECT * FROM sp_whatsapp_message_status 
                     WHERE schedule_id = ? 
                     ORDER BY created DESC 
                     LIMIT 5",
                    [(int)$schedule_id]
                );
                $result['last_5_records'] = $query->getResultArray();
            } catch (\Exception $e) {
                $result['error_schedule'] = $e->getMessage();
            }
        }
        
        // 5. Últimos 10 registros gerais (para debug)
        try {
            $query = $db->query(
                "SELECT schedule_id, to_number, status, wa_message_id, last_status_at, created 
                 FROM sp_whatsapp_message_status 
                 ORDER BY created DESC 
                 LIMIT 10"
            );
            $result['last_10_records'] = $query->getResultArray();
        } catch (\Exception $e) {
            $result['error_last'] = $e->getMessage();
        }

        // 6. Contas da campanha e tipo (Cloud vs Baileys)
        $result['campaign_accounts'] = [];
        $result['uses_cloud'] = false;
        if ($schedule_item && !empty($schedule_item->accounts)) {
            $account_ids = json_decode($schedule_item->accounts, true);
            if (is_array($account_ids) && !empty($account_ids)) {
                $builder = $db->table('sp_accounts');
                $builder->select('id, name, login_type, token');
                $builder->whereIn('id', $account_ids);
                $builder->where('social_network', 'whatsapp');
                $accounts = $builder->get()->getResultArray();
                foreach ($accounts as $acc) {
                    $result['campaign_accounts'][] = [
                        'id' => $acc['id'],
                        'name' => $acc['name'],
                        'login_type' => (int)($acc['login_type'] ?? 0),
                        'tipo' => ($acc['login_type'] == 1) ? 'Cloud API (grava status)' : (($acc['login_type'] == 3) ? 'Whatsmeow/Go (não grava status)' : 'Baileys (não grava status)'),
                    ];
                    if (($acc['login_type'] ?? 0) == 1) {
                        $result['uses_cloud'] = true;
                    }
                }
            }
        }
        $result['diagnostico'] = $result['uses_cloud']
            ? 'A campanha usa Cloud API. Se total_records=0, o Node.js pode estar falhando ao gravar ou o disparo ainda não passou por process_send_message.'
            : 'A campanha usa apenas Baileys. O tracking de status só funciona para contas Cloud API (login_type=1). Selecione uma conta Cloud na campanha para ver os status.';

        return $result;
    }
}
