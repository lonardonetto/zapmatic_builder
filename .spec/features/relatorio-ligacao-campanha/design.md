# Design: Relatório Completo e Confiabilidade das Campanhas de Ligação

> feature: relatorio-ligacao-campanha

## Visão geral

O ciclo de vida de uma chamada passa por 3 camadas hoje desconexas:

```
CallCampaignWorker (PHP)  ──POST /call/start──▶  Gateway Go (handler_call.go)
        ▲                                              │
        └───────── GET /call/status (poll) ────────────┘
                                                       │
                                              meowcaller (fork vendor)
                                              emite CallPreAccept / CallAccept / CallReject / CallTerminate
```

O objetivo é (1) **fechar o ciclo** com ring timeout e reconciliação não
bloqueante, e (2) **persistir cada etapa** numa timeline (`sp_call_events`),
expondo a plataforma (mobile/web) que hoje só aparece no log do meowcaller.

## Decisões de arquitetura (confirmadas com o usuário)

1. **Plataforma via evento `CallAccept` no gateway** — em vez de estender o fork
   meowcaller, o gateway registra um handler de `events.CallAccept` (que já
   carrega `RemotePlatform`) e associa a plataforma à `callEntry` ativa pelo
   `CallID`. **Zero push em repositório externo, zero regeneração de `vendor/`** —
   é o caminho de menor risco e entrega o mesmo resultado.
2. **Timeline em tabela dedicada `sp_call_events`** — uma linha por etapa,
   auditável, em vez de colunas/JSON escondidos no lead.
3. **Gravação de resposta (record_response) fora de escopo** — spec futura.

## Modelo de dados

### Nova tabela `sp_call_events`

```sql
CREATE TABLE IF NOT EXISTS sp_call_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT NOT NULL,
  lead_id INT NOT NULL,
  call_id VARCHAR(100) NOT NULL,
  event VARCHAR(40) NOT NULL,        -- placed|preaccepted|accepted|answered|audio_started|audio_finished|hangup_scheduled|ended|failed
  platform VARCHAR(16) DEFAULT NULL, -- mobile|web (no accepted)
  reason VARCHAR(255) DEFAULT NULL,  -- hangup|ring_timeout|server:463|rejected|peer_disconnect|timeout|...
  detail TEXT DEFAULT NULL,          -- payload extra legível (opcional)
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lead (lead_id),
  INDEX idx_call (call_id),
  INDEX idx_campaign (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Novas colunas em `sp_call_leads`

| Coluna | Tipo | Significado |
|---|---|---|
| `platform` | varchar(16) NULL | `mobile`/`web` do atendimento |
| `heard_full_audio` | tinyint(1) 0 | 1 se OnFinish disparou |
| `hangup_source` | varchar(24) NULL | `auto`/`peer`/`server`/`ring_timeout`/`worker` |
| `ring_duration_seconds` | int 0 | tempo entre placed e answered (ou ring_timeout) |
| `last_error` | varchar(255) NULL | último erro/motivo reportado (redundância legível do `error_message`) |

## Fluxo no gateway (handler_call.go)

### `callEntry` enriquecida

A `callEntry` ganha:
- `Timeline []callEvent` (mutex protegido) — cada etapa com `Event`, `Platform`,
  `Reason`, `At`.
- `RingTimeout time.Duration` — herdado do payload `ring_timeout` (o worker
  envia `timeout_ring` da campanha).
- Um `time.Timer` de ring timeout, armado no `handleCallStart`.

### Eventos mapeados

| Etapa | Origem | Gravado quando |
|---|---|---|
| `placed` | após `Call()` retornar | imediato |
| `preaccepted` | `events.CallPreAccept` (handler no gateway) | ao chegar |
| `accepted` | `events.CallAccept` (handler no gateway) + `RemotePlatform` | ao chegar |
| `answered` | `OnReady` (media flowing) | callback atual |
| `audio_started` | logo antes de `call.Play` | callback atual |
| `audio_finished` | `player.OnFinish` | callback atual |
| `hangup_scheduled` | quando agenda o hangup de 2s | callback atual |
| `ended` | `OnEnd(reason)` | callback atual |
| `failed` | erro no `Call()`/load de áudio | no `handleCallStart` |

> **Como o gateway enxerga os eventos do peer:** o `meowcaller.Client` recebe o
> `*whatsmeow.Client` e registra seus próprios handlers internamente; o gateway
> também tem acesso ao `*whatsmeow.Client` via `inst.Client()`. Registramos
> handlers de `events.CallPreAccept`/`events.CallAccept`/`events.CallReject`/
> `events.CallTerminate` **no gateway** (em `manager.go` ou no próprio handler),
> correlacionando por `CallID`. Isso captura plataforma e reject/terminate sem
> tocar no fork. O `RemotePlatform` é normalizado: `sm*` → `mobile`, senão `web`.

### Ring timeout

- Armado no `handleCallStart` com `time.Duration(ringTimeout) * time.Second`.
- Ao disparar: se `entry.Status` ainda for `ringing` (não houve `answered` nem
  `ended`), chama `call.Hangup()` e grava `ended` com `reason=ring_timeout`.
- Cancela o timer no `OnReady` e no `OnEnd`.

### `GET /call/status` retrocompatível

Mantém os campos atuais e **adiciona**:
- `timeline`: array de eventos.
- `platform`, `heard_full_audio`, `hangup_source`.

## Fluxo no worker (CallCampaignWorker.php)

### Mudança central: reconciliação não bloqueante

Substituir o `pollCallResult` bloqueante por um método `reconcileRinging($db)`
que, a cada ciclo do `while(true)` (antes de disparar novos leads), varre **todos
os leads `ringing` de todas as campanhas ativas** e consulta `/call/status` de
cada um (em lote via `curl_multi`, quando aplicável), gravando:

1. eventos da timeline (via um novo `POST /call/events` **ou** lendo
   `timeline` do `/call/status` e persistindo no MySQL);
2. status final do lead e contadores da campanha.

### Modos

| Modo | Comportamento corrigido |
|---|---|
| `fila` | dispara 1 (instância única), depois reconcilia via ciclo (não trava no poll) |
| `alternado` | dispara 1 por vez alternando instância (mantém), reconcilia via ciclo |
| `simultaneo` | dispara N (1 por instância) via `goApiMultiPost` (mantém), **e passa a reconciliar** os `ringing` no ciclo |

### Persistência da timeline (decisão de escrita)

Opção preferida: o **worker** é o único escritor do MySQL (o gateway não escreve
no banco de chamadas hoje). O gateway apenas **acumula a timeline em memória** e
a devolve no `/call/status`; o worker persiste em `sp_call_events` e atualiza
`sp_call_leads`. Isso:
- mantém o gateway sem acoplamento a schema de negócio;
- usa o MySQL já conectado no worker;
- torna o relatório fonte única no banco.

> Alternativa (gateway escreve direto no MySQL via `bulk.DB()`) fica registrada
> como plano B se a latência do polling se mostrar ruim — ver ASM-030.

## Fluxo do fork meowcaller

> **Não é mais necessário.** A decisão 1 mudou para captura pelo gateway (evento
> `CallAccept`). O fork `lonardonetto/meowcaller` permanece **intocado** nesta
> feature — preserva o fix de call-id da seção 4.17.5 sem risco de regressão.

## Testes

- **Go** (`handler_call_test.go`): ring timeout (timer dispara hangup), timeline
  (ordenação e conteúdo), retrocompatibilidade do `/call/status` (campos antigos
  presentes), mapeamento de platform → mobile/web.
- **PHP** (`tests/phpunit/`): reconciliação de leads `ringing`, classificação do
  desfecho (ring_timeout × peer_disconnect × hangup), persistência de eventos.

## Riscos

- `RemotePlatform` pode ter valores inesperados (ASM-029) — normalização
  defensiva: `sm*` → mobile, senão `web`.
- Timer de ring timeout deve ser cancelado em todas as saídas para não encerrar
  chamada já atendida (paridade com AC-034 do auto-hangup).
- O handler de `CallAccept` no gateway roda concorrentemente ao `OnReady` do
  meowcaller — a associação plataforma→call deve ser thread-safe (mutex no
  `callStore`).
