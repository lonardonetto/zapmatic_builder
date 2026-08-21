<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Core\Whatsapp_bulk\Libraries\BulkDuplicator;

final class BulkDuplicateTest extends TestCase
{
    /** @spec:AC-083 */
    public function test_duplicar_campanha_preserva_sent_e_failed_para_continuar(): void
    {
        $original = (object)[
            'id' => 45,
            'name' => 'Campanha Vendas',
            'sent' => 150,
            'failed' => 25,
            'contact_id' => 10,
            'team_id' => 2,
        ];

        $data = BulkDuplicator::prepareDuplicateData($original, 'Campanha Vendas Copy 1', 'new_ids_123');

        $this->assertSame('Campanha Vendas Copy 1', $data['name']);
        $this->assertSame('new_ids_123', $data['ids']);
        $this->assertSame(150, $data['sent'], 'sent deve ser preservado da original');
        $this->assertSame(25, $data['failed'], 'failed deve ser preservado da original');
        $this->assertSame(1, $data['status']);
        $this->assertArrayNotHasKey('id', $data);
    }

    /** @spec:AC-084 */
    public function test_duplicar_campanha_grupo_copia_registros_de_grupo(): void
    {
        $groups = [
            (object)['id' => 1, 'schedule_id' => 45, 'account_id' => 12, 'group_jid' => '120363001@g.us', 'position' => 0],
            (object)['id' => 2, 'schedule_id' => 45, 'account_id' => 12, 'group_jid' => '120363002@g.us', 'position' => 1],
        ];

        $newRecords = BulkDuplicator::prepareGroupRecords($groups, 99);

        $this->assertCount(2, $newRecords);
        $this->assertSame(99, $newRecords[0]['schedule_id']);
        $this->assertSame('120363001@g.us', $newRecords[0]['group_jid']);
        $this->assertArrayNotHasKey('id', $newRecords[0]);
    }
}
