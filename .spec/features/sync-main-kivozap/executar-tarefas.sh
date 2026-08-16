#!/usr/bin/env bash
# executar-tarefas.sh — gerado por `onp-spec plano sync-main-kivozap` em 2026-08-16 12:27
# NÃO edite à mão: mudou tasks.md ou a config, regenere o plano.
#
# uso:
#   bash executar-tarefas.sh                  tudo (ondas → sequenciais → gate)
#   bash executar-tarefas.sh --faixa <id>     reexecuta UMA faixa (+ merge + gate)
#   bash executar-tarefas.sh --seq <T-xxx>    reexecuta UMA tarefa sequencial
#   bash executar-tarefas.sh --gate           só o gate (verify + audit)
#   bash executar-tarefas.sh --listar         mostra faixas, tarefas e estados
#   (acrescente --sem-gate para não rodar o gate ao final)
#
# resumo do que está rolando, a qualquer momento: onp-spec resumo sync-main-kivozap
set -u
set -o pipefail

RUN_ID='app_zapmatic_app-sync-main-kivozap-msvs5ixb'
FEATURE='sync-main-kivozap'
BASE_BRANCH='spec/sync-main-kivozap'
ENGINE='/home/ubuntu/.nvm/versions/node/v22.23.1/bin/onp-spec'
CLAUDE_FLAGS=(--permission-mode acceptEdits --allowedTools 'Bash(git add:*),Bash(git commit:*),Bash(git status:*),Bash(git diff:*),Bash(git log:*),Bash(node:*)')
STREAM_FLAGS=(--output-format stream-json --verbose)
FALHAS=""
COM_GATE=1
RESUMO_MODEL='claude-haiku-4-5'
RESUMO_PID=""

verde()    { printf '\033[32m%s\033[0m\n' "$*"; }
vermelho() { printf '\033[31m%s\033[0m\n' "$*"; }
amarelo()  { printf '\033[33m%s\033[0m\n' "$*"; }
info()     { printf '· %s\n' "$*"; }
falhar()   { vermelho "✘ $*"; exit 1; }

# eventos vão para o ledger GLOBAL (~/.onp-spec/painel/ledger.jsonl):
# um arquivo para todos os projetos, é o que o onp-spec resumo lê
evento() { node "$ENGINE" evento --run "$RUN_ID" "$@" >/dev/null 2>&1 || true; }

# ── ambiente (todos os modos passam por aqui) ────────────────────────
preparar_ambiente() {
  command -v git >/dev/null 2>&1 || falhar "git não encontrado"
  command -v node >/dev/null 2>&1 || falhar "node não encontrado"
  command -v claude >/dev/null 2>&1 || falhar "Claude Code CLI (claude) não encontrado — instale-o ou siga o modo manual em plano-execucao.md"
  TOPLEVEL=$(git rev-parse --show-toplevel 2>/dev/null) || falhar "fora de um repositório git"
  cd "$TOPLEVEL" || exit 1
  # artefatos recém-gerados pelo `onp-spec plano` são sujeira esperada:
  # se forem a ÚNICA sujeira, o script mesmo commita; qualquer outra, aborta
  if [ -n "$(git status --porcelain)" ]; then
    if [ -z "$(git status --porcelain | grep -v -e 'plano-execucao\.' -e 'plano\.json' -e 'executar-tarefas\.sh')" ]; then
      git add -A
      git commit -q -m "plano de execução: $FEATURE (artefatos gerados)"
      info "artefatos do plano commitados"
    else
      falhar "árvore suja além dos artefatos do plano — commite ou faça git stash antes (os worktrees partem do último commit)"
    fi
  fi
  git ls-files --error-unmatch -- '.spec/features/sync-main-kivozap/spec.md' >/dev/null 2>&1 || falhar "spec.md não está commitada — os worktrees das faixas precisam dela no git"
  ATUAL=$(git rev-parse --abbrev-ref HEAD)
  [ "$ATUAL" != "HEAD" ] || falhar "HEAD destacado — troque para uma branch"
  if [ "$ATUAL" != "$BASE_BRANCH" ]; then
    if git show-ref --verify --quiet "refs/heads/$BASE_BRANCH"; then
      git checkout -q "$BASE_BRANCH" || falhar "não consegui trocar para $BASE_BRANCH"
    else
      git checkout -q -b "$BASE_BRANCH" || falhar "não consegui criar $BASE_BRANCH"
    fi
    info "branch de trabalho: $BASE_BRANCH (a partir de $ATUAL)"
  fi
  git worktree prune
  LOG_DIR="$(dirname "$TOPLEVEL")/onp-worktrees/app_zapmatic_app-sync-main-kivozap-logs"
  WT_BASE="$(dirname "$TOPLEVEL")/onp-worktrees/app_zapmatic_app-sync-main-kivozap"
  STREAMS_DIR="${ONP_SPEC_HOME:-$HOME/.onp-spec}/painel/streams/$RUN_ID"
  mkdir -p "$LOG_DIR" "$STREAMS_DIR"
}

# worktree limpo mesmo depois de uma tentativa que falhou
preparar_worktree() { # $1=faixa $2=branch $3=worktree
  git worktree prune
  if [ -e "$3" ]; then git worktree remove --force "$3" >/dev/null 2>&1; rm -rf "$3"; fi
  if git show-ref --verify --quiet "refs/heads/$2"; then git branch -D "$2" >/dev/null 2>&1; fi
  git worktree add "$3" -b "$2" >/dev/null 2>&1 || { vermelho "✘ não consegui criar o worktree de $1 em $3"; return 1; }
}

tentativa() { # $1=faixa — conta reexecuções (vai para o ledger)
  local arq="$LOG_DIR/.tentativa-$1"
  local n=1
  [ -f "$arq" ] && n=$(( $(cat "$arq") + 1 ))
  printf "%s" "$n" > "$arq"
  printf "%s" "$n"
}

# uma tarefa = uma sessão claude headless com contexto limpo.
# o JSONL da sessão vira o stream da tarefa no ledger
rodar_tarefa() { # $1=escopo(faixa|seq) $2=T-xxx $3=prompt $4=modelo $5=esforço
  local chave="$1--$2"
  local stream="$STREAMS_DIR/$chave.jsonl"
  evento --tipo tarefa --tarefa "$2" --faixa "$1" --estado executando --stream "$chave"
  info "$2 — claude -p ($4 · $5) · stream: $chave"
  if claude -p "$3" --model "$4" --effort "$5" "${STREAM_FLAGS[@]}" "${CLAUDE_FLAGS[@]}" > "$stream" 2>>"$LOG_DIR/$1.log"; then
    evento --tipo tarefa --tarefa "$2" --faixa "$1" --estado concluida --stream "$chave"
    node "$ENGINE" stream-resumo "$RUN_ID" "$chave" 2>/dev/null || true
    return 0
  fi
  evento --tipo tarefa --tarefa "$2" --faixa "$1" --estado falhou --stream "$chave"
  node "$ENGINE" stream-resumo "$RUN_ID" "$chave" 2>/dev/null || true
  return 1
}

mesclar_faixa() { # $1=faixa $2=branch $3=worktree $4=exit-da-faixa
  if [ "$4" -ne 0 ]; then
    evento --tipo faixa --faixa "$1" --estado falhou
    vermelho "✘ $1 falhou (log: $LOG_DIR/$1.log) — worktree mantido para inspeção: $3"
    amarelo "  reexecute só ela: bash .spec/features/sync-main-kivozap/executar-tarefas.sh --faixa $1"
    FALHAS="$FALHAS $1"; return 1
  fi
  evento --tipo faixa --faixa "$1" --estado mesclando
  if git merge --no-ff "$2" -m "merge $1 ($FEATURE)"; then
    git worktree remove --force "$3" >/dev/null 2>&1
    git branch -d "$2" >/dev/null 2>&1
    evento --tipo faixa --faixa "$1" --estado mesclada
    verde "✔ $1 mesclada em $BASE_BRANCH"
  else
    git merge --abort >/dev/null 2>&1
    evento --tipo faixa --faixa "$1" --estado conflito
    vermelho "✘ conflito ao mesclar $1 — resolva na mão: git merge $2 (worktree mantido: $3)"
    FALHAS="$FALHAS $1"; return 1
  fi
}

marcar_concluidas() { # $@=T-xxx
  for t in "$@"; do node "$ENGINE" tarefa "$FEATURE" "$t" concluida >/dev/null || true; done
}

# ── resumo geral de andamento: 1/min enquanto a execução roda ─────────
# escrito por IA (claude -p, sem ferramentas) com fallback do motor; vai
# para o terminal e para o ledger — o agente repassa o texto no chat.
gerar_resumo() {
  local ctx ia
  ctx=$(node "$ENGINE" resumo "$FEATURE" --contexto 2>/dev/null) || ctx=""
  [ -n "$ctx" ] || return 0
  ia=$(claude -p "Você narra, para o dono do produto, uma execução de tarefas de código em andamento. Estado mecânico:

$ctx

Escreva o RESUMO GERAL DE ANDAMENTO: um parágrafo único de 2 a 4 frases, em português simples, dizendo o que está acontecendo agora, o que já terminou, o que falhou e se o usuário precisa agir. Sem markdown, sem listas." --model "$RESUMO_MODEL" 2>/dev/null)
  if [ -n "$ia" ]; then
    node "$ENGINE" resumo "$FEATURE" --gravar --origem ia --texto "$ia" >/dev/null 2>&1 || true
    printf '\n📣 resumo (IA): %s\n' "$ia"
  else
    node "$ENGINE" resumo "$FEATURE" --gravar >/dev/null 2>&1 || true
    printf '\n📣 resumo: %s\n' "$(node "$ENGINE" resumo "$FEATURE" 2>/dev/null)"
  fi
}

# mata o loop E o sleep filho — senão o sleep herda o stdout e quem chamou
# o script via pipe fica esperando EOF por até 60s depois do exit
parar_resumos() {
  [ -n "$RESUMO_PID" ] || return 0
  command -v pkill >/dev/null 2>&1 && pkill -P "$RESUMO_PID" 2>/dev/null
  kill "$RESUMO_PID" 2>/dev/null
  RESUMO_PID=""
}

iniciar_resumos() {
  ( while :; do sleep 60; gerar_resumo; done ) &
  RESUMO_PID=$!
  # ao sair: para o loop e grava um último resumo (o estado final, do motor)
  trap 'parar_resumos; node "$ENGINE" resumo "$FEATURE" --gravar >/dev/null 2>&1 || true' EXIT
}

# ── faixa-1: T-001 T-011 T-012 T-018 ──
executar_faixa_1() {
  local WT="$WT_BASE-faixa-1"
  preparar_worktree 'faixa-1' 'spec/sync-main-kivozap-faixa-1' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-1' --estado executando --tentativa "$(tentativa 'faixa-1')"
  : > "$LOG_DIR/faixa-1.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-1' 'T-001' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-001 — "Backup completo do kivozap antes da sincronização"
  critérios/refs: US-016, US-017
  arquivos permitidos (e seus testes): (execução remota via SSH)
  mensagem de commit: "T-001 sync-main-kivozap: Backup completo do kivozap antes da sincronização"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium &&
    rodar_tarefa 'faixa-1' 'T-011' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-011 — "Verificar isolamento: processos independentes"
  critérios/refs: AC-046 (Processos independentes)
  arquivos permitidos (e seus testes): (execução remota via SSH)
  mensagem de commit: "T-011 sync-main-kivozap: Verificar isolamento: processos independentes"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium &&
    rodar_tarefa 'faixa-1' 'T-012' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-012 — "Reiniciar serviços do kivozap"
  critérios/refs: AC-041 (Bot_builder.php sem erros de sintaxe)
  arquivos permitidos (e seus testes): (execução remota via SSH)
  mensagem de commit: "T-012 sync-main-kivozap: Reiniciar serviços do kivozap"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium &&
    rodar_tarefa 'faixa-1' 'T-018' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-018 — "Teste: Cron jobs independentes"
  critérios/refs: AC-052 (Cron jobs independentes)
  arquivos permitidos (e seus testes): (execução remota via SSH)
  mensagem de commit: "T-018 sync-main-kivozap: Teste: Cron jobs independentes"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-1.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-1' 'spec/sync-main-kivozap-faixa-1' "$WT" "$st" || return 1
  marcar_concluidas T-001 T-011 T-012 T-018
  return 0
}

# ── faixa-2: T-002 T-003 T-004 ──
executar_faixa_2() {
  local WT="$WT_BASE-faixa-2"
  preparar_worktree 'faixa-2' 'spec/sync-main-kivozap-faixa-2' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-2' --estado executando --tentativa "$(tentativa 'faixa-2')"
  : > "$LOG_DIR/faixa-2.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-2' 'T-002' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-002 — "Remover tabelas legadas do kivozap"
  critérios/refs: AC-039 (Tabelas legadas removidas do kivozap)
  arquivos permitidos (e seus testes): (execução remota via SSH — MySQL)
  mensagem de commit: "T-002 sync-main-kivozap: Remover tabelas legadas do kivozap"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium &&
    rodar_tarefa 'faixa-2' 'T-003' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-003 — "Criar tabelas faltantes no kivozap"
  critérios/refs: AC-038 (Tabelas do main presentes no kivozap)
  arquivos permitidos (e seus testes): (execução remota via SSH — MySQL)
  mensagem de commit: "T-003 sync-main-kivozap: Criar tabelas faltantes no kivozap"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium &&
    rodar_tarefa 'faixa-2' 'T-004' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-004 — "Alinhar tipos de colunas em sp_bb_sessions"
  critérios/refs: AC-040 (Colunas com tipos alinhados)
  arquivos permitidos (e seus testes): (execução remota via SSH — MySQL)
  mensagem de commit: "T-004 sync-main-kivozap: Alinhar tipos de colunas em sp_bb_sessions"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-2.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-2' 'spec/sync-main-kivozap-faixa-2' "$WT" "$st" || return 1
  marcar_concluidas T-002 T-003 T-004
  return 0
}

# ── faixa-3: T-005 ──
executar_faixa_3() {
  local WT="$WT_BASE-faixa-3"
  preparar_worktree 'faixa-3' 'spec/sync-main-kivozap-faixa-3' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-3' --estado executando --tentativa "$(tentativa 'faixa-3')"
  : > "$LOG_DIR/faixa-3.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-3' 'T-005' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-005 — "Sincronizar código: Bot_builder.php (correção já aplicada)"
  critérios/refs: AC-041 (Bot_builder.php sem erros de sintaxe)
  arquivos permitidos (e seus testes): inc/core/Bot_builder/Controllers/Bot_builder.php
  mensagem de commit: "T-005 sync-main-kivozap: Sincronizar código: Bot_builder.php (correção já aplicada)"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-3.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-3' 'spec/sync-main-kivozap-faixa-3' "$WT" "$st" || return 1
  marcar_concluidas T-005
  return 0
}

# ── faixa-4: T-006 ──
executar_faixa_4() {
  local WT="$WT_BASE-faixa-4"
  preparar_worktree 'faixa-4' 'spec/sync-main-kivozap-faixa-4' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-4' --estado executando --tentativa "$(tentativa 'faixa-4')"
  : > "$LOG_DIR/faixa-4.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-4' 'T-006' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-006 — "Sincronizar código: Whatsapp_bulk"
  critérios/refs: AC-042 (Módulos com diferenças sincronizados)
  arquivos permitidos (e seus testes): inc/core/Whatsapp_bulk/Controllers/Whatsapp_bulk.php
  mensagem de commit: "T-006 sync-main-kivozap: Sincronizar código: Whatsapp_bulk"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-4.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-4' 'spec/sync-main-kivozap-faixa-4' "$WT" "$st" || return 1
  marcar_concluidas T-006
  return 0
}

# ── faixa-5: T-007 ──
executar_faixa_5() {
  local WT="$WT_BASE-faixa-5"
  preparar_worktree 'faixa-5' 'spec/sync-main-kivozap-faixa-5' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-5' --estado executando --tentativa "$(tentativa 'faixa-5')"
  : > "$LOG_DIR/faixa-5.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-5' 'T-007' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-007 — "Sincronizar código: Whatsapp_export_participants"
  critérios/refs: AC-042 (Módulos com diferenças sincronizados)
  arquivos permitidos (e seus testes): inc/core/Whatsapp_export_participants/Controllers/Whatsapp_export_participants.php
  mensagem de commit: "T-007 sync-main-kivozap: Sincronizar código: Whatsapp_export_participants"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-5.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-5' 'spec/sync-main-kivozap-faixa-5' "$WT" "$st" || return 1
  marcar_concluidas T-007
  return 0
}

# ── faixa-6: T-008 ──
executar_faixa_6() {
  local WT="$WT_BASE-faixa-6"
  preparar_worktree 'faixa-6' 'spec/sync-main-kivozap-faixa-6' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-6' --estado executando --tentativa "$(tentativa 'faixa-6')"
  : > "$LOG_DIR/faixa-6.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-6' 'T-008' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-008 — "Sincronizar código: Todos os controllers restantes"
  critérios/refs: AC-043 (Todos os controllers idênticos)
  arquivos permitidos (e seus testes): inc/core/*/Controllers/*.php
  mensagem de commit: "T-008 sync-main-kivozap: Sincronizar código: Todos os controllers restantes"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-6.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-6' 'spec/sync-main-kivozap-faixa-6' "$WT" "$st" || return 1
  marcar_concluidas T-008
  return 0
}

# ── faixa-7: T-009 ──
executar_faixa_7() {
  local WT="$WT_BASE-faixa-7"
  preparar_worktree 'faixa-7' 'spec/sync-main-kivozap-faixa-7' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-7' --estado executando --tentativa "$(tentativa 'faixa-7')"
  : > "$LOG_DIR/faixa-7.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-7' 'T-009' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-009 — "Verificar isolamento: sem reencaminhamento entre plataformas"
  critérios/refs: AC-044 (Sem reencaminhamento entre plataformas)
  arquivos permitidos (e seus testes): inc/core/Whatsapp_webhook/Controllers/Whatsapp_webhook.php
  mensagem de commit: "T-009 sync-main-kivozap: Verificar isolamento: sem reencaminhamento entre plataformas"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-7.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-7' 'spec/sync-main-kivozap-faixa-7' "$WT" "$st" || return 1
  marcar_concluidas T-009
  return 0
}

# ── faixa-8: T-010 ──
executar_faixa_8() {
  local WT="$WT_BASE-faixa-8"
  preparar_worktree 'faixa-8' 'spec/sync-main-kivozap-faixa-8' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-8' --estado executando --tentativa "$(tentativa 'faixa-8')"
  : > "$LOG_DIR/faixa-8.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-8' 'T-010' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-010 — "Verificar isolamento: credenciais próprias preservadas"
  critérios/refs: AC-045 (Credenciais próprias preservadas)
  arquivos permitidos (e seus testes): .env
  mensagem de commit: "T-010 sync-main-kivozap: Verificar isolamento: credenciais próprias preservadas"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-8.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-8' 'spec/sync-main-kivozap-faixa-8' "$WT" "$st" || return 1
  marcar_concluidas T-010
  return 0
}

# ── faixa-9: T-013 ──
executar_faixa_9() {
  local WT="$WT_BASE-faixa-9"
  preparar_worktree 'faixa-9' 'spec/sync-main-kivozap-faixa-9' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-9' --estado executando --tentativa "$(tentativa 'faixa-9')"
  : > "$LOG_DIR/faixa-9.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-9' 'T-013' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-013 — "Teste: Flow builder funcional"
  critérios/refs: AC-047 (Flow builder abre e edita)
  arquivos permitidos (e seus testes): inc/core/Bot_builder/
  mensagem de commit: "T-013 sync-main-kivozap: Teste: Flow builder funcional"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-9.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-9' 'spec/sync-main-kivozap-faixa-9' "$WT" "$st" || return 1
  marcar_concluidas T-013
  return 0
}

# ── faixa-10: T-014 ──
executar_faixa_10() {
  local WT="$WT_BASE-faixa-10"
  preparar_worktree 'faixa-10' 'spec/sync-main-kivozap-faixa-10' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-10' --estado executando --tentativa "$(tentativa 'faixa-10')"
  : > "$LOG_DIR/faixa-10.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-10' 'T-014' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-014 — "Teste: Webhook funcional"
  critérios/refs: AC-048 (Webhook responde corretamente)
  arquivos permitidos (e seus testes): inc/core/Whatsapp_webhook/
  mensagem de commit: "T-014 sync-main-kivozap: Teste: Webhook funcional"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-10.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-10' 'spec/sync-main-kivozap-faixa-10' "$WT" "$st" || return 1
  marcar_concluidas T-014
  return 0
}

# ── faixa-11: T-015 ──
executar_faixa_11() {
  local WT="$WT_BASE-faixa-11"
  preparar_worktree 'faixa-11' 'spec/sync-main-kivozap-faixa-11' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-11' --estado executando --tentativa "$(tentativa 'faixa-11')"
  : > "$LOG_DIR/faixa-11.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-11' 'T-015' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-015 — "Teste: Bulk/mass messaging"
  critérios/refs: AC-049 (Bulk/mass messaging funcional)
  arquivos permitidos (e seus testes): inc/core/Whatsapp_bulk/
  mensagem de commit: "T-015 sync-main-kivozap: Teste: Bulk/mass messaging"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-11.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-11' 'spec/sync-main-kivozap-faixa-11' "$WT" "$st" || return 1
  marcar_concluidas T-015
  return 0
}

# ── faixa-12: T-016 ──
executar_faixa_12() {
  local WT="$WT_BASE-faixa-12"
  preparar_worktree 'faixa-12' 'spec/sync-main-kivozap-faixa-12' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-12' --estado executando --tentativa "$(tentativa 'faixa-12')"
  : > "$LOG_DIR/faixa-12.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-12' 'T-016' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-016 — "Teste: Exportação de participantes"
  critérios/refs: AC-050 (Exportação de participantes funcional)
  arquivos permitidos (e seus testes): inc/core/Whatsapp_export_participants/
  mensagem de commit: "T-016 sync-main-kivozap: Teste: Exportação de participantes"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-12.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-12' 'spec/sync-main-kivozap-faixa-12' "$WT" "$st" || return 1
  marcar_concluidas T-016
  return 0
}

# ── faixa-13: T-017 ──
executar_faixa_13() {
  local WT="$WT_BASE-faixa-13"
  preparar_worktree 'faixa-13' 'spec/sync-main-kivozap-faixa-13' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-13' --estado executando --tentativa "$(tentativa 'faixa-13')"
  : > "$LOG_DIR/faixa-13.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-13' 'T-017' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-017 — "Teste: Logs sem erros PHP"
  critérios/refs: AC-051 (Nenhum erro PHP nos logs)
  arquivos permitidos (e seus testes): writable/logs/
  mensagem de commit: "T-017 sync-main-kivozap: Teste: Logs sem erros PHP"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-13.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-13' 'spec/sync-main-kivozap-faixa-13' "$WT" "$st" || return 1
  marcar_concluidas T-017
  return 0
}

# ── faixa-14: T-019 ──
executar_faixa_14() {
  local WT="$WT_BASE-faixa-14"
  preparar_worktree 'faixa-14' 'spec/sync-main-kivozap-faixa-14' "$WT" || return 1
  evento --tipo faixa --faixa 'faixa-14' --estado executando --tentativa "$(tentativa 'faixa-14')"
  : > "$LOG_DIR/faixa-14.log"
  (
    cd "$WT" || exit 9
    rodar_tarefa 'faixa-14' 'T-019' 'Você executa UMA tarefa da feature "sync-main-kivozap" (fluxo onp-spec, spec-anchored).
Leia primeiro: .spec/features/sync-main-kivozap/spec.md, .spec/features/sync-main-kivozap/tasks.md e .spec/constituicao.md.

Sua tarefa (somente ela):
T-019 — "Auditoria final: onp-spec audit"
  critérios/refs: US-016, US-017, US-018, US-019
  arquivos permitidos (e seus testes): .spec/verification/sync-main-kivozap.json
  mensagem de commit: "T-019 sync-main-kivozap: Auditoria final: onp-spec audit"

Regras inegociáveis:
- Todo critério de aceite referenciado vira teste com @spec:AC-xxx no título.
- NUNCA enfraqueça, pule (skip/todo) ou apague um teste para passar — teste pulado não é prova e o audit acusa.
- Rode os testes localmente com `node tests/run_all.mjs` até passarem.
- NÃO edite tasks.md, NÃO rode onp-spec verify/audit e NÃO toque em outras tarefas — o orquestrador cuida disso.
- Ao final de CADA tarefa: `git add` só no que você tocou e um commit próprio.' 'claude-sonnet-5' medium
  ) >> "$LOG_DIR/faixa-14.log" 2>&1
  local st=$?
  mesclar_faixa 'faixa-14' 'spec/sync-main-kivozap-faixa-14' "$WT" "$st" || return 1
  marcar_concluidas T-019
  return 0
}

# ── gate: quem decide é a máquina ────────────────────────────────────
rodar_gate() {
  echo
  info "gate: verify + audit --ci"
  evento --tipo gate --etapa inicio
  node "$ENGINE" verify "$FEATURE"
  local v=$?
  evento --tipo gate --etapa verify --exit "$v"
  node "$ENGINE" audit --ci
  AUDIT=$?
  evento --tipo gate --etapa audit --exit "$AUDIT"
  # fecha a contabilidade: status das tarefas + prova do verify no git
  if [ -n "$(git status --porcelain -- '.spec')" ]; then
    git add -A -- '.spec'
    git commit -q -m "$FEATURE: status das tarefas + prova do verify (plano)"
    info "status das tarefas e prova do verify commitados"
  fi
  return "$AUDIT"
}

encerrar() { # $1=escopo
  echo
  if [ -n "$FALHAS" ]; then vermelho "faixas/tarefas com falha:$FALHAS"; fi
  # sem gate não existe veredito: NUNCA anunciar alinhamento sem o audit
  if [ "$COM_GATE" -eq 0 ]; then
    evento --tipo fim --exit 1 --escopo "$1"
    if [ -z "$FALHAS" ]; then
      amarelo "○ trabalho de '$1' terminou SEM o gate (--sem-gate) — isto NÃO é prova de nada"
      amarelo "  para o veredito: bash .spec/features/sync-main-kivozap/executar-tarefas.sh --gate"
      exit 0
    fi
    vermelho "e ainda há falhas — conserte e rode o gate"
    exit 1
  fi
  rodar_gate
  local audit=$?
  if [ "$audit" -eq 0 ] && [ -z "$FALHAS" ]; then
    evento --tipo fim --exit 0 --escopo "$1"
    verde "✔ plano concluído — especificação e código alinhados (audit exit 0) na branch $BASE_BRANCH"
    info "próximo passo: revise e leve para a main quando quiser (git merge $BASE_BRANCH)"
    exit 0
  fi
  evento --tipo fim --exit 1 --escopo "$1"
  vermelho "plano terminou com pendências — leia a saída do audit acima e os logs em $LOG_DIR"
  amarelo "dica: reexecute só o que falhou (--faixa <id> / --seq <T-xxx>)"
  exit 1
}

executar_tudo() {
  evento --tipo inicio --escopo tudo
  iniciar_resumos
  info "logs em: $LOG_DIR"
  info "resumo geral de andamento: a cada 1 min aqui no terminal (e via: onp-spec resumo)"
  # onda 1: faixa-1 ∥ faixa-2 ∥ faixa-3
  info "onda 1: faixa-1 ∥ faixa-2 ∥ faixa-3 — janelas limpas em paralelo"
  executar_faixa_1 & PID_FAIXA_1=$!
  executar_faixa_2 & PID_FAIXA_2=$!
  executar_faixa_3 & PID_FAIXA_3=$!
  wait "$PID_FAIXA_1" || true
  wait "$PID_FAIXA_2" || true
  wait "$PID_FAIXA_3" || true
  # onda 2: faixa-4 ∥ faixa-5 ∥ faixa-6
  info "onda 2: faixa-4 ∥ faixa-5 ∥ faixa-6 — janelas limpas em paralelo"
  executar_faixa_4 & PID_FAIXA_4=$!
  executar_faixa_5 & PID_FAIXA_5=$!
  executar_faixa_6 & PID_FAIXA_6=$!
  wait "$PID_FAIXA_4" || true
  wait "$PID_FAIXA_5" || true
  wait "$PID_FAIXA_6" || true
  # onda 3: faixa-7 ∥ faixa-8 ∥ faixa-9
  info "onda 3: faixa-7 ∥ faixa-8 ∥ faixa-9 — janelas limpas em paralelo"
  executar_faixa_7 & PID_FAIXA_7=$!
  executar_faixa_8 & PID_FAIXA_8=$!
  executar_faixa_9 & PID_FAIXA_9=$!
  wait "$PID_FAIXA_7" || true
  wait "$PID_FAIXA_8" || true
  wait "$PID_FAIXA_9" || true
  # onda 4: faixa-10 ∥ faixa-11 ∥ faixa-12
  info "onda 4: faixa-10 ∥ faixa-11 ∥ faixa-12 — janelas limpas em paralelo"
  executar_faixa_10 & PID_FAIXA_10=$!
  executar_faixa_11 & PID_FAIXA_11=$!
  executar_faixa_12 & PID_FAIXA_12=$!
  wait "$PID_FAIXA_10" || true
  wait "$PID_FAIXA_11" || true
  wait "$PID_FAIXA_12" || true
  # onda 5: faixa-13 ∥ faixa-14
  info "onda 5: faixa-13 ∥ faixa-14 — janelas limpas em paralelo"
  executar_faixa_13 & PID_FAIXA_13=$!
  executar_faixa_14 & PID_FAIXA_14=$!
  wait "$PID_FAIXA_13" || true
  wait "$PID_FAIXA_14" || true
  encerrar tudo
}

listar() {
  echo "execução: $RUN_ID (feature $FEATURE, branch $BASE_BRANCH)"
  echo "  faixa-1  onda 1  T-001, T-011, T-012, T-018"
  echo "  faixa-2  onda 1  T-002, T-003, T-004"
  echo "  faixa-3  onda 1  T-005"
  echo "  faixa-4  onda 2  T-006"
  echo "  faixa-5  onda 2  T-007"
  echo "  faixa-6  onda 2  T-008"
  echo "  faixa-7  onda 3  T-009"
  echo "  faixa-8  onda 3  T-010"
  echo "  faixa-9  onda 3  T-013"
  echo "  faixa-10  onda 4  T-014"
  echo "  faixa-11  onda 4  T-015"
  echo "  faixa-12  onda 4  T-016"
  echo "  faixa-13  onda 5  T-017"
  echo "  faixa-14  onda 5  T-019"
  echo
  echo "reexecutar uma faixa:    --faixa <id>"
  echo "reexecutar sequencial:   --seq <T-xxx>"
  echo "só o gate:               --gate"
}

MODO="tudo"
ALVO=""
while [ $# -gt 0 ]; do
  case "$1" in
    --listar) MODO="listar" ;;
    --gate) MODO="gate" ;;
    --sem-gate) COM_GATE=0 ;;
    --faixa) MODO="faixa"; ALVO="${2:-}"; shift ;;
    --seq) MODO="seq"; ALVO="${2:-}"; shift ;;
    -h|--help) sed -n "2,14p" "$0"; exit 0 ;;
    *) vermelho "argumento desconhecido: $1"; sed -n "2,14p" "$0"; exit 2 ;;
  esac
  shift
done

if [ "$MODO" = "listar" ]; then listar; exit 0; fi

preparar_ambiente

case "$MODO" in
  tudo) executar_tudo ;;
  gate) COM_GATE=1; iniciar_resumos; encerrar gate ;;
  faixa)
    case "$ALVO" in
      faixa-1) evento --tipo inicio --escopo "faixa:faixa-1"; iniciar_resumos; executar_faixa_1 || true; encerrar "faixa:faixa-1" ;;
      faixa-2) evento --tipo inicio --escopo "faixa:faixa-2"; iniciar_resumos; executar_faixa_2 || true; encerrar "faixa:faixa-2" ;;
      faixa-3) evento --tipo inicio --escopo "faixa:faixa-3"; iniciar_resumos; executar_faixa_3 || true; encerrar "faixa:faixa-3" ;;
      faixa-4) evento --tipo inicio --escopo "faixa:faixa-4"; iniciar_resumos; executar_faixa_4 || true; encerrar "faixa:faixa-4" ;;
      faixa-5) evento --tipo inicio --escopo "faixa:faixa-5"; iniciar_resumos; executar_faixa_5 || true; encerrar "faixa:faixa-5" ;;
      faixa-6) evento --tipo inicio --escopo "faixa:faixa-6"; iniciar_resumos; executar_faixa_6 || true; encerrar "faixa:faixa-6" ;;
      faixa-7) evento --tipo inicio --escopo "faixa:faixa-7"; iniciar_resumos; executar_faixa_7 || true; encerrar "faixa:faixa-7" ;;
      faixa-8) evento --tipo inicio --escopo "faixa:faixa-8"; iniciar_resumos; executar_faixa_8 || true; encerrar "faixa:faixa-8" ;;
      faixa-9) evento --tipo inicio --escopo "faixa:faixa-9"; iniciar_resumos; executar_faixa_9 || true; encerrar "faixa:faixa-9" ;;
      faixa-10) evento --tipo inicio --escopo "faixa:faixa-10"; iniciar_resumos; executar_faixa_10 || true; encerrar "faixa:faixa-10" ;;
      faixa-11) evento --tipo inicio --escopo "faixa:faixa-11"; iniciar_resumos; executar_faixa_11 || true; encerrar "faixa:faixa-11" ;;
      faixa-12) evento --tipo inicio --escopo "faixa:faixa-12"; iniciar_resumos; executar_faixa_12 || true; encerrar "faixa:faixa-12" ;;
      faixa-13) evento --tipo inicio --escopo "faixa:faixa-13"; iniciar_resumos; executar_faixa_13 || true; encerrar "faixa:faixa-13" ;;
      faixa-14) evento --tipo inicio --escopo "faixa:faixa-14"; iniciar_resumos; executar_faixa_14 || true; encerrar "faixa:faixa-14" ;;
      *) falhar "faixa desconhecida: '$ALVO' — veja as disponíveis com --listar" ;;
    esac ;;
  seq)
    case "$ALVO" in
      *) falhar "tarefa sequencial desconhecida: '$ALVO' — veja as disponíveis com --listar" ;;
    esac ;;
esac
