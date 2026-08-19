# Tasks: Atualização Completa via Git (Substituição Total)

> feature: atualizacao-git-substituicao-total

## T-001 — Definir ordem de atualização dos sistemas [pendente]
- Refs: Contexto
- Notas: Leo Netto define qual sistema atualizar primeiro. Um por vez.
  Registrar o `{TENANT}` escolhido antes de iniciar a Etapa 0.

## T-002 — Executar backup completo do destino [pendente]
- Refs: Etapa 1
- Notas: dump do banco + `.env` + `config.json` + `google-service-account.json`
  + `ecosystem.config.js` + `writable.tgz` em `/www/backup_zapmatic_update/{TS}`.

## T-003 — Comparar banco (tabelas + colunas) main vs destino [pendente]
- Refs: Etapa 2, seção 6
- Notas: gerar `/tmp/{TENANT}_migration.sql` idempotente com ADD COLUMN /
  CREATE TABLE para o que faltar; listar legados para aprovação do Leo.

## T-004 — Aplicar migration no destino e validar 76 tabelas / 918 colunas [pendente]
- Refs: Etapa 3
- Notas: rodar `{TENANT}_migration.sql`; confirmar contagens.

## T-005 — Substituição total de arquivos via git (reset --hard origin/main) [pendente]
- Refs: Etapa 4
- Notas: `git init`/fetch + `checkout -B main` + `reset --hard`. Confirmar que
  `.env`, `writable/`, `vendor/`, `config.json` continuam intactos.

## T-006 — Instalar dependências (composer + npm) [pendente]
- Refs: Etapa 5
- Notas: `composer install --no-dev --optimize-autoloader` e
  `npm install --production` no scraper.

## T-007 — Compilar binary Go (CGO_ENABLED=1) no destino [pendente]
- Refs: Etapa 6
- Notas: `go build -o zapmatic-whatsmeow ./cmd/server/`.

## T-008 — Reescrever ecosystem.config.js com valores do destino [pendente]
- Refs: Etapa 7.1, S1
- Notas: prefixo `{TENANT}-`, cwd `{PATH}`, incluir o worker cloud-campaign.

## T-009 — Reiniciar processos (Go, PHP-FPM, PM2) [pendente]
- Refs: Etapa 8
- Notas: systemctl restart, php-fpm reload, pm2 delete all + start + save.

## T-010 — Executar testes de validação (T1..T15) e registrar [pendente]
- Refs: seção 4
- Notas: registrar resultados em `.spec/verification/atualizacao-{TENANT}.json`.

## T-011 — Auditoria arquivo a arquivo do inc/core (hashes SHA-256) [pendente]
- Refs: seção 5
- Notas: diff de `/tmp/main_hashes.txt` vs `/tmp/{TENANT}_hashes.txt` = 0
  diferenças para arquivos versionados.
