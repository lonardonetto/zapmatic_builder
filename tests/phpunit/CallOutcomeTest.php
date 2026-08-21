<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Core\Whatsapp_call_campaign\Libraries\CallOutcome;

final class CallOutcomeTest extends TestCase
{
    /** @spec:AC-071 */
    public function test_normaliza_plataforma(): void
    {
        $this->assertSame('mobile', CallOutcome::normalizePlatform('smbi'));
        $this->assertSame('mobile', CallOutcome::normalizePlatform('smba'));
        $this->assertSame('web', CallOutcome::normalizePlatform('web'));
        $this->assertSame('mobile', CallOutcome::normalizePlatform('SMBA'));
        $this->assertNull(CallOutcome::normalizePlatform(''));
        $this->assertNull(CallOutcome::normalizePlatform(null));
    }

    /** @spec:AC-073 */
    public function test_ouviu_ate_o_final(): void
    {
        $timeline = [
            ['event' => 'audio_finished', 'reason' => ''],
            ['event' => 'hangup_scheduled', 'reason' => ''],
        ];
        $out = CallOutcome::classify('ended', 'hangup', 54, $timeline);
        $this->assertSame(1, $out['heard_full_audio']);
        $this->assertSame('auto', $out['hangup_source']);
        $this->assertSame('answered', $out['status']);
    }

    /** @spec:AC-072 */
    public function test_tocou_ate_desligar_ring_timeout(): void
    {
        $out = CallOutcome::classify('ended', 'ring_timeout', 0, []);
        $this->assertSame('no_answer', $out['status']);
        $this->assertSame('ring_timeout', $out['hangup_source']);
        $this->assertSame(0, $out['heard_full_audio']);
    }

    /** @spec:AC-072 */
    public function test_foi_desligado_pelo_peer_durante_audio(): void
    {
        // Atendeu mas o peer desligou cedo (durou 2s, sem OnFinish, sem auto-hangup).
        $out = CallOutcome::classify('ended', '', 2, []);
        $this->assertSame('answered', $out['status']);
        $this->assertSame('peer', $out['hangup_source']);
        $this->assertSame(0, $out['heard_full_audio']);
    }

    /** @spec:AC-074 */
    public function test_erro_reportado_pelo_servidor(): void
    {
        $out = CallOutcome::classify('ended', 'server:463', 0, []);
        $this->assertSame('failed', $out['status']);
        $this->assertSame('server', $out['hangup_source']);
    }

    /** @spec:AC-074 */
    public function test_busy(): void
    {
        $out = CallOutcome::classify('ended', 'busy', 0, []);
        $this->assertSame('busy', $out['status']);
    }
}
