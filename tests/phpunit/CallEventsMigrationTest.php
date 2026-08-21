<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CallEventsMigrationTest extends TestCase
{
    /** @spec:AC-082 */
    public function test_migracao_cria_tabela_e_colunas(): void
    {
        $sql = (string) file_get_contents(dirname(__DIR__, 2) . '/migrations/2026_08_21_001_call_events.sql');

        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS sp_call_events', $sql);
        $this->assertStringContainsString('UNIQUE KEY uq_call_event (call_id, event)', $sql);

        // Colunas aditivas em sp_call_leads (ALTER ADD COLUMN, não-destrutivo).
        foreach (['platform', 'heard_full_audio', 'hangup_source', 'ring_duration_seconds', 'last_error'] as $col) {
            $this->assertStringContainsString("ADD COLUMN {$col}", $sql, "coluna {$col} ausente na migração");
        }
    }
}
