<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Core\Whatsapp_call_campaign\Libraries\CallReconcile;
use Core\Whatsapp_call_campaign\Libraries\CallOutcome;

final class CallReconcileTest extends TestCase
{
    /** @spec:AC-077 */
    public function test_reconciliacao_nao_bloqueante_classifica_status(): void
    {
        $this->assertSame('answered', CallReconcile::resolveAction('active'));
        $this->assertSame('answered', CallReconcile::resolveAction('answered'));
        $this->assertSame('ended', CallReconcile::resolveAction('ended'));
        $this->assertSame('noop', CallReconcile::resolveAction('ringing'));
    }

    /** @spec:AC-078 */
    public function test_modo_simultaneo_fecha_todos_os_contadores(): void
    {
        // Campanha com 5 leads; nenhum pendente/ringing => todos resolvidos.
        $statuses = ['answered', 'answered', 'no_answer', 'busy', 'failed'];
        $this->assertTrue(CallReconcile::allResolved($statuses));
        $this->assertSame(0, CallReconcile::pendingCount($statuses));
    }

    /** @spec:AC-078 */
    public function test_modo_simultaneo_com_ringing_nao_fecha(): void
    {
        $statuses = ['answered', 'ringing', 'pending', 'no_answer'];
        $this->assertFalse(CallReconcile::allResolved($statuses));
        $this->assertSame(2, CallReconcile::pendingCount($statuses));
    }

    /** @spec:AC-079 */
    public function test_modo_alternado_alterna_instancias(): void
    {
        $instances = ['A', 'B', 'C'];
        $this->assertSame('A', CallReconcile::nextInstance($instances, 0));
        $this->assertSame('B', CallReconcile::nextInstance($instances, 1));
        $this->assertSame('C', CallReconcile::nextInstance($instances, 2));
        $this->assertSame('A', CallReconcile::nextInstance($instances, 3));
    }

    /** @spec:AC-076 */
    public function test_timeout_cancela_e_marca_failed(): void
    {
        $out = CallReconcile::timeoutOutcome();
        $this->assertSame('failed', $out['status']);
        $this->assertSame('worker', $out['hangup_source']);
        $this->assertStringContainsString('Timeout', $out['error_message']);
    }

    /** @spec:AC-070 */
    public function test_eventos_agrupados_por_lead(): void
    {
        $events = [
            (object)['lead_id' => 1, 'event' => 'placed'],
            (object)['lead_id' => 1, 'event' => 'ended'],
            (object)['lead_id' => 2, 'event' => 'placed'],
        ];
        $lead1 = CallReconcile::eventsForLead($events, 1);
        $this->assertCount(2, $lead1);
        $this->assertSame('placed', $lead1[0]->event);
        $this->assertSame('ended', $lead1[1]->event);
    }

    /** @spec:AC-074 */
    public function test_motivo_reject_sem_servidor_vira_no_answer(): void
    {
        $out = CallOutcome::classify('ended', 'rejected', 0, []);
        $this->assertSame('no_answer', $out['status']);
    }
}
