# Constituição — v1.1.1

<!--
  Princípios inegociáveis do projeto. Não são estilo: são restrições.
  P-xxx = princípio (código de rastreio, como US/AC/T).
  Níveis: [DEVE] obrigatório · [RECOMENDADO] forte · [PODE] permitido/explícito.
  Todo [DEVE] precisa de verificação executável — senão o audit acusa
  "princípio sem verificação" (PRINCIPIO_SEM_VERIFICACAO). Formatos:
    - verificação(gate): satisfeita pelo próprio audit (só p/ princípios "meta")
    - verificação(teste): @principle:P-xxx
    - verificação(proibido): `regex` em `glob`
    - verificação(obrigatório): `regex` em `glob`
-->

## P-001 [DEVE] Todo requisito tem prova executável

Nenhuma feature é declarada pronta sem o audit em modo CI sair limpo (exit 0).
Este princípio é verificado pelo próprio mecanismo do audit (AC_SEM_TESTE,
AC_SEM_PROVA, TASK_CONCLUIDA_SEM_PROVA) — não precisa de teste extra seu.

- verificação(gate): intrínseca ao audit

## P-002 [DEVE] Segredos nunca em código próprio

Chaves de API, senhas e tokens NUNCA hard-coded nos módulos críticos.
Devem vir de variáveis de ambiente (.env) ou config protegido.
vendor/ é código de terceiros — fora do nosso controle.

Módulos sob vigilância:
- Flow Builder (Bot_builder) — o mais crítico
- Call Campaign — lida com áudio e gateway
- Bulk — envio em massa
- Gateway Go (internal/, excluindo vendor/)

- verificação(proibido): `(api[_-]?key|password|senha|token)\s*[:=]\s*['"]\w{12,}` em `inc/core/Bot_builder/**/*.php`
- verificação(proibido): `(api[_-]?key|password|senha|token)\s*[:=]\s*['"]\w{12,}` em `inc/core/Whatsapp_call_campaign/**/*.php`
- verificação(proibido): `(api[_-]?key|password|senha|token)\s*[:=]\s*['"]\w{12,}` em `inc/core/Whatsapp_bulk/**/*.php`
- verificação(proibido): `(api[_-]?key|password|senha|token)\s*[:=]\s*['"]\w{12,}` em `app_zapmatic_whatsmeow_api/internal/**/*.go`
- verificação(obrigatório): `^[A-Za-z_][A-Za-z0-9_]*\s*=` em `.env`

## P-003 [DEVE] Não quebrar APIs públicas sem transição

Rotas, endpoints e contratos de API não mudam sem versão ou compatibilidade retroativa.
Mudanças no gateway Go exigem recompilação + restart coordenado (systemd).
Mudanças no PHP exigem `php -l` limpo antes do deploy.

- verificação(gate): intrínseca ao audit — arquivos alterados sem tag de versão acusam divergência

## P-004 [RECOMENDADO] Backup antes de deploy crítico

Binários Go e arquivos PHP de produção preservados com `.bak` (ou `.bak<N>`, ex.: `.bak2`) antes de qualquer alteração.
Rollback possível com `git revert` ou restauração do backup.
Os scripts de deploy preservam os backups existentes (`--exclude='*.bak*'`).

- verificação(obrigatório): `\.bak` em `deploy_go.sh`

## P-005 [DEVE] Sistema multi-tenant: team_id nas queries do usuário final

Nenhum usuário pode acessar dados de outro time. Queries acionadas
por ação do usuário (editor, delete, sessions, update, save) DEVEM filtrar
por `team_id`. O Bot_builder é o módulo mais exposto.

Métodos corrigidos em 2026-08-10 (Fase S3): get_bot, update, delete,
save_flow (start_block), get_sessions. Métodos internos/globais
(versions, templates, seeds) sem tenant por design.

- verificação(gate): Fase S3 concluída (2026-08-10) — get_bot, update, delete, save_flow, get_sessions corrigidos com where('team_id', ...). Demais queries são internas (versions, templates, seeds, worker) e não expostas ao usuário final.
