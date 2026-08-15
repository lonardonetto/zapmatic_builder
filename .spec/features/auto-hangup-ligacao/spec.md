# Spec: Encerramento Automático de Chamada (Auto-Hangup)

> feature: auto-hangup-ligacao
> status: implementada

## Contexto

Nas campanhas de chamadas de voz automatizadas pelo WhatsApp, ao atender a ligação, o destinatário (lead) deve escutar o áudio configurado na campanha e, exatamente 2 segundos após a finalização da reprodução do áudio, a chamada deve ser encerrada automaticamente pelo gateway.

## Histórias

### US-013 — Encerramento via Callback OnFinish de Fim de Áudio

Como operador de campanhas de voz, quero que a chamada desligue automaticamente 2 segundos após o áudio terminar para não prender a linha nem gerar custos desnecessários.

#### AC-033 — OnFinish agenda hangup com delay de 2 segundos

- **Dado** uma chamada atendida com áudio em reprodução
- **Quando** o player de áudio do gateway (`meowcaller.Player`) atinge o final do arquivo (`OnFinish`)
- **Então** uma rotina é disparada para aguardar 2 segundos e invocar `call.Hangup()`

#### AC-034 — Chamada já encerrada não causa erro no hangup

- **Dado** uma chamada que já foi finalizada antes dos 2 segundos
- **Quando** a rotina de auto-hangup executa
- **Então** nenhuma ação conflitante é executada e o sistema permanece estável

### US-014 — Cálculo de Duração e Timer de Segurança

Como operador do sistema, quero que o gateway conheça a duração do áudio mesmo se o valor não for fornecido pelo cliente ou se duration_seconds for zero.

#### AC-035 — Timer de segurança com duração informada

- **Dado** uma chamada iniciada com `audio_duration` positivo
- **Quando** a chamada se torna ativa
- **Então** um timer de segurança de fallback é configurado para `audio_duration + 3s`

#### AC-036 — Estimativa de duração para áudio sem duração no payload

- **Dado** uma chamada com `audio_duration` zerado ou ausente
- **Quando** o gateway carrega o arquivo de áudio no disco
- **Então** o gateway lê os metadados do arquivo (WAV/MP3/Opus) e calcula a duração real para configurar o fallback

## Suposições

- O atraso intencional após a conclusão do áudio é de exatamente 2 segundos para permitir a percepção natural do fim da mensagem antes do desligamento.
- Taxa média de 128 kbps (16.000 B/s) é utilizada como fallback caso o cabeçalho MPEG não seja decodificado com precisão.

## Perguntas em aberto

Nenhuma.
