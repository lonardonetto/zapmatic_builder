# 🗺️ Mapa da Refatoração — Flow Builder + Módulo de Ligação

**Documento de estado vivo** — atualizado a cada parte concluída.
Última atualização: 2026-08-06

---

## 1. Escopo

| Área | Módulo | Arquivos principais | Linhas |
|---|---|---|---|
| Flow Builder | `Bot_builder` | Controller 3.999 · Model 1.430 · JS 4.625 · Views 7.239 | ~20.200 |
| Módulo de Ligação | `Whatsapp_call_campaign` + Go gateway | Controller 729 · Worker 438 · handler_call.go 320 | ~1.500 |

## 2. Princípios (vigentes)

1. Não quebrar fluxos em produção · contrato de dados intacto
2. Não mexer no `waziper.js` (Baileys já removido do sistema)
3. Não quebrar a API Go (gateway whatsmeow)
4. Uma fase por vez · sistema 100% funcional ao fim de cada · rollback = backup/git revert
5. Testar ANTES e DEPOIS de cada implementação
6. **NUNCA commitar sem autorização explícita do usuário**

---

## 3. Fases do Plano

### Fase S — Segurança (pendente)
- [ ] S1 · eliminar `eval()` do bloco script (auditoria `sp_bb_blocks type='script'` antes)
- [ ] S2 · autenticar webhook + crons (sem quebrar API Go — alinhar com gateway)
- [ ] S3 · IDOR multi-tenant (filtro `team_id` no Model)
- [ ] S4 · XSS nas views (json_encode flags, esc)
- [ ] S5 · SSRF/ReDoS (bb_is_safe_url)

### Fase 8 — Backend runtime (pendente)
- [ ] 8.1 VariableResolver · 8.2 InputValidator · 8.3 WhatsAppSender · 8.4 SessionStore
- [ ] 8.5 Fixtures de replay (rede de segurança)
- [ ] 8.6 RouteMatcher · 8.7 TriggerResolver · 8.8 Handlers intg_* · 8.9 Handlers conteúdo
- [ ] 8.10 WebhookIngress · 8.11 FlowExecutor

### Fase 9 — Model/banco (pendente)
9.1 auto-migrate→spark · 9.2 templates JSON · 9.3 use_count · 9.4 delete transação · 9.5 versions N+1 · 9.6 cripto API keys · 9.7 FKs

### Fase 10 — Frontend (pendente)
10.1 listas derivadas node-defs · 10.2 getFlowPayload · 10.3 getCubicPoint · 10.4 leaks · 10.5 SimHandlers · 10.6 InspectorFields · 10.7 sandbox simulador

### Fase 11 — Views/assets (pendente)
11.1 CSS compartilhado · 11.2 ?v=time() · 11.3 Google Fonts · 11.4 FontAwesome · 11.5 SRI · 11.6 deletar legado · 11.7 analytics SQL

### Fase 12 — Split controller (pendente)

---

## 4. Registro de Mudanças Executadas

| Data | Fase/Item | Arquivo(s) | O que foi feito | Teste antes → depois |
|---|---|---|---|---|
| 2026-08-06 | **Módulo Ligação · Paulo** | `app/Commands/CallCampaignWorker.php` | `getAudioPath()` → `getAudioInfo()` (envia `audio_path` + `audio_duration`) — paridade total com Zapmatic/IaClicks | **Antes:** enviava só path; chamadas atendidas nunca encerravam · **Depois:** `audio_duration` enviado; "Call answered!" registrado |
| 2026-08-06 | **Módulo Ligação · Paulo** | `app_zapmatic_whatsmeow_api/internal/http/handler_call.go` | Adicionado `AudioDuration` no struct + bloco auto-hangup (encerra após áudio+2s) — copiado do Zapmatic | **Antes:** sem auto-hangup; 146 chamadas presas · **Depois:** auto-hangup ativo (confirmado via strings e logs) |
| 2026-08-06 | **Módulo Ligação · Paulo** | binário `zapmatic-whatsmeow` (porta 8091) | Recompilado com auto-hangup (`go build ./cmd/server`) | **Antes:** 0 ocorrências "Auto-hangup" · **Depois:** 2 ocorrências |
| 2026-08-06 | **Módulo Ligação · Paulo** | serviço systemd `zapmatic-whatsmeow-paulo` | Restart (limpeza callStore) | **Antes:** 146 chamadas presas, CPU 206% · **Depois:** 0 presas, CPU ~7% |
| 2026-08-06 | **Módulo Ligação · Paulo** | pm2 `paulo-call-worker` | Restart com código novo | **Antes:** 2D rodando código antigo · **Depois:** código novo ativo |
| 2026-08-06 | **Módulo Ligação · Doc** | `docs/migration_call_module_v8.5.1.md` | Paulo adicionado à lista de servidores aplicados + histórico da correção | — |

**Backups criados (rollback disponível):**
- `app_paulo_app/app/Commands/CallCampaignWorker.php.bak_20260806_114550`
- `app_paulo_app/app_zapmatic_whatsmeow_api/internal/http/handler_call.go.bak_20260806_114550`
- `app_paulo_app/app_zapmatic_whatsmeow_api/zapmatic-whatsmeow.bak_20260806_114550`

---

## 5. Checklist de Teste — Módulo de Ligação (Paulo)

- [x] Auto-hangup presente no binário novo (strings)
- [x] `Playing audio to peer` → `Auto-hangup scheduled` → `Auto-hangup: audio finished, ending call` (gateway.log, call 01BB088BFAA5 11:50)
- [x] `/call/list` sem acumulação (0 presas; entradas "ended" removidas em 5 min)
- [x] CPU do gateway normalizado (206% → ~7%)
- [x] Worker novo: `Call answered!` registrado
- [x] Áudios válidos (MP3 mono 16kHz/44.1kHz)
- [ ] **Pendente:** validação do usuário em teste de ligação real (áudio audível + chamada encerra sozinha)

---

## 6. Próximos passos (após validação do Paulo)

1. Validar teste de ligação real do Paulo
2. Iniciar **Fase S3 (IDOR)** no Flow Builder — primeiro corte do monólito
3. Testes antes/depois em cada fase (registrar nesta seção)

---

| 2026-08-10 | **Fase S: LID→número** | `handler_groups.go` (main + paulo) | Fallback `GetPNForLID` adicionado ao endpoint `/groups/participant`. Agora resolve LID mesmo sem grupo, via banco local do whatsmeow (`client.Store.LIDs.GetPNForLID`). PHP inalterado (zero mudança no Bot_builder.php). | **Antes:** LIDs de chat privado não resolviam → `wa_phone` = LID bruto na planilha. **Depois:** `268542897311803 → 556282501519`, `120796072644628 → 5521970402529`. Ambos gateways (main=8090, paulo=8091) atualizados e testados. |

| 2026-08-10 | **Astros** | handler_groups.go + handler_call.go + CallCampaignWorker.php + binário Go | Sincronização completa a partir do main. Arquivos copiados (MD5 idênticos), binário recompilado (Auto-hangup=2, GetPNForLID=6), reiniciado. Worker bot já lê o novo CallCampaignWorker. | **Antes:** 48% alinhado. **Depois:** handler_groups=LID→PN, handler_call=auto-hangup, CallCampaignWorker=getAudioInfo. LID testado: 71249279586343→558399647615. Health 17/21. Backups salvos. |
