# Plano de execução — sync-main-kivozap

> gerado por `onp-spec plano` em 2026-08-16 12:27 — NÃO edite à mão;
> mudou tasks.md ou a config? Regenere: `onp-spec plano sync-main-kivozap`

## Resumo — o que vai acontecer

- **19 tarefa(s) pendente(s)**: 19 em 14 faixa(s) paralela(s) + 0 sequencial(is)
- **1 faixa = 1 worktree + 1 branch + 1 janela de contexto limpa** — faixas não compartilham nenhum arquivo entre si
- prefere outra seleção ou uma após a outra? Regenere com `onp-spec plano sync-main-kivozap --paralelizar T-xxx,T-yyy` ou `--sequencial`
- tudo acontece na branch de trabalho `spec/sync-main-kivozap`; levar para a main é decisão sua

## Faixas e ondas

### Onda 1 — faixa-1 ∥ faixa-2 ∥ faixa-3

#### faixa-1 — branch `spec/sync-main-kivozap-faixa-1` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-1`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-001 | Backup completo do kivozap antes da sincronização | `claude-sonnet-5` | medium | `(execução remota via SSH)` |
| T-011 | Verificar isolamento: processos independentes | `claude-sonnet-5` | medium | `(execução remota via SSH)` |
| T-012 | Reiniciar serviços do kivozap | `claude-sonnet-5` | medium | `(execução remota via SSH)` |
| T-018 | Teste: Cron jobs independentes | `claude-sonnet-5` | medium | `(execução remota via SSH)` |

#### faixa-2 — branch `spec/sync-main-kivozap-faixa-2` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-2`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-002 | Remover tabelas legadas do kivozap | `claude-sonnet-5` | medium | `(execução remota via SSH — MySQL)` |
| T-003 | Criar tabelas faltantes no kivozap | `claude-sonnet-5` | medium | `(execução remota via SSH — MySQL)` |
| T-004 | Alinhar tipos de colunas em sp_bb_sessions | `claude-sonnet-5` | medium | `(execução remota via SSH — MySQL)` |

#### faixa-3 — branch `spec/sync-main-kivozap-faixa-3` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-3`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-005 | Sincronizar código: Bot_builder.php (correção já aplicada) | `claude-sonnet-5` | medium | `inc/core/Bot_builder/Controllers/Bot_builder.php` |

### Onda 2 — faixa-4 ∥ faixa-5 ∥ faixa-6

#### faixa-4 — branch `spec/sync-main-kivozap-faixa-4` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-4`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-006 | Sincronizar código: Whatsapp_bulk | `claude-sonnet-5` | medium | `inc/core/Whatsapp_bulk/Controllers/Whatsapp_bulk.php` |

#### faixa-5 — branch `spec/sync-main-kivozap-faixa-5` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-5`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-007 | Sincronizar código: Whatsapp_export_participants | `claude-sonnet-5` | medium | `inc/core/Whatsapp_export_participants/Controllers/Whatsapp_export_participants.php` |

#### faixa-6 — branch `spec/sync-main-kivozap-faixa-6` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-6`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-008 | Sincronizar código: Todos os controllers restantes | `claude-sonnet-5` | medium | `inc/core/*/Controllers/*.php` |

### Onda 3 — faixa-7 ∥ faixa-8 ∥ faixa-9

#### faixa-7 — branch `spec/sync-main-kivozap-faixa-7` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-7`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-009 | Verificar isolamento: sem reencaminhamento entre plataformas | `claude-sonnet-5` | medium | `inc/core/Whatsapp_webhook/Controllers/Whatsapp_webhook.php` |

#### faixa-8 — branch `spec/sync-main-kivozap-faixa-8` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-8`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-010 | Verificar isolamento: credenciais próprias preservadas | `claude-sonnet-5` | medium | `.env` |

#### faixa-9 — branch `spec/sync-main-kivozap-faixa-9` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-9`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-013 | Teste: Flow builder funcional | `claude-sonnet-5` | medium | `inc/core/Bot_builder/` |

### Onda 4 — faixa-10 ∥ faixa-11 ∥ faixa-12

#### faixa-10 — branch `spec/sync-main-kivozap-faixa-10` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-10`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-014 | Teste: Webhook funcional | `claude-sonnet-5` | medium | `inc/core/Whatsapp_webhook/` |

#### faixa-11 — branch `spec/sync-main-kivozap-faixa-11` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-11`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-015 | Teste: Bulk/mass messaging | `claude-sonnet-5` | medium | `inc/core/Whatsapp_bulk/` |

#### faixa-12 — branch `spec/sync-main-kivozap-faixa-12` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-12`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-016 | Teste: Exportação de participantes | `claude-sonnet-5` | medium | `inc/core/Whatsapp_export_participants/` |

### Onda 5 — faixa-13 ∥ faixa-14

#### faixa-13 — branch `spec/sync-main-kivozap-faixa-13` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-13`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-017 | Teste: Logs sem erros PHP | `claude-sonnet-5` | medium | `writable/logs/` |

#### faixa-14 — branch `spec/sync-main-kivozap-faixa-14` — worktree `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-faixa-14`

| tarefa | título | modelo | esforço | arquivos |
|---|---|---|---|---|
| T-019 | Auditoria final: onp-spec audit | `claude-sonnet-5` | medium | `.spec/verification/sync-main-kivozap.json` |

## Gestão de branches e commits

1. branch de trabalho `spec/sync-main-kivozap` criada do ponto atual (se ainda não existir)
2. cada faixa nasce dela como branch própria e roda no seu worktree — **1 tarefa = 1 commit** (`T-xxx feature: título`)
3. terminou a onda → merge `--no-ff` de cada faixa de volta, na ordem; conflito interrompe a faixa e pede resolução humana
4. faixa mesclada → worktree removido, branch apagada, tarefa marcada `[concluida]` no tasks.md
5. gate final na branch de trabalho: `onp-spec verify sync-main-kivozap` + `onp-spec audit --ci` — **exit 0 ou não está pronto**

## Como executar

### ▶ Execução — Claude Code headless

```bash
bash .spec/features/sync-main-kivozap/executar-tarefas.sh
```

Cada faixa roda `claude -p` com **janela de contexto limpa**, no seu worktree, com
`--model` e `--effort` já definidos por tarefa e permissões `acceptEdits`. Os prompts exatos estão
embutidos no script — quer rodar uma faixa na mão, é só copiá-los de lá.
Logs: `../onp-worktrees/app_zapmatic_app-sync-main-kivozap-logs/`.

### 📣 Acompanhamento — tabela + resumo no chat (a cada 1 min)

O script roda em **background**: o agente AVISA o usuário antes de iniciar e,
enquanto roda, posta no chat a cada ~1 minuto a **tabela de andamento** (qual
tarefa está rodando, qual não está, o que concluiu/falhou) junto com o
**resumo geral de andamento** (escrito por IA; sem IA, o motor resume). Ao
final, o usuário recebe o resumo completo da execução. A qualquer momento:

```bash
onp-spec resumo sync-main-kivozap --tabela   # a tabela de andamento
onp-spec resumo sync-main-kivozap            # o resumo em texto
```

