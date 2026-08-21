<?php
declare(strict_types=1);

namespace Core\Whatsapp_call_campaign\Libraries;

/**
 * CallOutcome — lógica pura de classificação do desfecho de uma chamada.
 *
 * Sem acoplamento com CodeIgniter, banco ou gateway: recebe o status/reason/
 * duração/timeline do /call/status e devolve a classificação de negócio
 * (status final, hangup_source, heard_full_audio) e a normalização de plataforma.
 */
final class CallOutcome
{
    /**
     * Normaliza a plataforma do WhatsApp (RemotePlatform) para um rótulo estável.
     * "smbi"/"smba"/qualquer "sm*" => mobile; qualquer outro valor => web; vazio => null.
     */
    public static function normalizePlatform(?string $platform): ?string
    {
        if ($platform === null || trim($platform) === '') {
            return null;
        }
        $p = strtolower(trim($platform));
        if (strncmp($p, 'sm', 2) === 0) {
            return 'mobile';
        }
        return 'web';
    }

    /**
     * Classifica o desfecho a partir do status final, reason, duração e timeline.
     *
     * @param string $status   status final do gateway ("ended", "active", ...)
     * @param string $reason   motivo reportado (ex.: "hangup", "ring_timeout", "server:463")
     * @param int    $duration duração entre answered_at e ended_at
     * @param array  $timeline lista de eventos [{event, platform, reason, at}]
     *
     * @return array{status:string, hangup_source:?string, heard_full_audio:int}
     */
    public static function classify(string $status, string $reason, int $duration, array $timeline = []): array
    {
        $hangupSource = null;
        $heardFull = 0;

        foreach ($timeline as $ev) {
            $event = is_array($ev) ? ($ev['event'] ?? '') : ($ev->event ?? '');
            if ($event === 'audio_finished') {
                $heardFull = 1;
            }
            if ($event === 'hangup_scheduled') {
                $hangupSource = 'auto';
            }
        }

        $reasonLower = strtolower($reason);
        $finalStatus = 'no_answer';

        if ($duration > 0) {
            $finalStatus = 'answered';
            // Se o lead desligou durante/antes do fim e não houve auto-hangup, foi o peer.
            if ($hangupSource === null) {
                $hangupSource = 'peer';
            }
        } elseif (strpos($reasonLower, 'busy') !== false) {
            $finalStatus = 'busy';
        } elseif (strpos($reasonLower, 'ring_timeout') !== false) {
            $finalStatus = 'no_answer';
            $hangupSource = 'ring_timeout';
        } elseif (strpos($reasonLower, 'server:') !== false) {
            $finalStatus = 'failed';
            $hangupSource = 'server';
        } elseif (strpos($reasonLower, 'reject') !== false) {
            $finalStatus = 'no_answer';
        }

        return [
            'status' => $finalStatus,
            'hangup_source' => $hangupSource,
            'heard_full_audio' => $heardFull,
        ];
    }
}
