# Spec: Sincronizar Main → Kivozap (estrutura idêntica, dados independentes)

> feature: sync-main-kivozap
> status: rascunho

## Contexto

O sistema **main** (zapmatic — 10.0.0.14, `app_zapmatic_app`, banco `db_zapmatic_sql`)
é o sistema mãe de onde derivamos os demais. O sistema **kivozap** (144.22.167.45,
`app_abner_app`, banco `db_abner_sql`) ficou defasado: tem código corrompido
(Bot_builder.php com parse error), tabelas legadas, módulos ausentes e colunas
com tipos diferentes.

O objetivo é tornar os dois sistemas **100% idênticos em estrutura** (código,
tabelas, colunas, módulos) mantendo cada um com suas **próprias credenciais,
domínio, banco de dados, portas e processos** — sem cruzamento de dados nem
requisições entre sistemas.

## Histórias

### US-016 — Estrutura de banco idêntica

Como administrador, quero que o banco do kivozap tenha as mesmas tabelas e
colunas do main (com os mesmos tipos), para que todos os módulos funcionem
sem divergências estruturais.

#### AC-038 — Tabelas do main presentes no kivozap

- **Dado** que o main tem 76 tabelas (incluindo `sp_clone_group_queue`,
  `sp_export_participants_queue`, `sp_whatsapp_schedule_groups`)
- **Quando** comparo `SHOW TABLES` de ambos os bancos
- **Então** o kivozap contém todas as tabelas do main (exceto as legadas
  que serão removidas)

#### AC-039 — Tabelas legadas removidas do kivozap

- **Dado** que o kivozap tem tabelas legadas (`sp_whatsapp_autoresponder`,
  `sp_whatsapp_chatbot`) que não existem no main
- **Quando** executo a limpeza
- **Então** essas tabelas são dropadas e seus dados legados são descartados

#### AC-040 — Colunas com tipos alinhados

- **Dado** que `sp_bb_sessions` no kivozap tem `reply_phone varchar(100)`,
  `timeout_instance_id varchar(100)`, `timeout_at bigint(20)`,
  `timeout_retry_msg longtext`, `timeout_exit_msg longtext`
- **Quando** comparo com o main (`varchar(255)`, `int(11)`, `text`)
- **Então** os tipos do kivozap são alterados para coincidir com o main
  (sem perda de dados existentes)

### US-017 — Código idêntico ao main

Como administrador, quero que todos os arquivos de código do kivozap sejam
idênticos aos do main, para que features novas e correções funcionem em ambos.

#### AC-041 — Bot_builder.php sem erros de sintaxe

- **Dado** que o `Bot_builder.php` do kivozap tinha parse error (já corrigido
  na auditoria anterior)
- **Quando** rodo `php -l` no arquivo do kivozap
- **Então** não há erros de sintaxe

#### AC-042 — Módulos com diferenças sincronizados

- **Dado** que `Whatsapp_bulk` (151 linhas de diff) e
  `Whatsapp_export_participants` (515 linhas de diff) têm código ausente
  no kivozap
- **Quando** copio os arquivos do main para o kivozap
- **Então** os diffs zeram (0 diferenças)

#### AC-043 — Todos os controllers idênticos

- **Dado** que comparo todos os controllers de todos os módulos
- **Quando** rodo diff entre main e kivozap para cada
  `inc/core/*/Controllers/*.php`
- **Então** não há diferenças (exceto configurações de ambiente — ver AC-046)

### US-018 — Isolamento total entre sistemas

Como administrador, quero que cada sistema funcione de forma independente,
sem cruzar dados, requisições ou processos com o outro.

#### AC-044 — Sem reencaminhamento entre plataformas

- **Dado** que ambos os sistemas têm `Whatsapp_webhook.php` com
  `Forwarding DISABLED`
- **Quando** um webhook chega com `phone_number_id` não local
- **Então** o sistema apenas loga e não reencaminha para o outro sistema

#### AC-045 — Credenciais próprias preservadas

- **Dado** que o kivozap tem `.env` com `db_abner_sql`, senha, domínio
  `kivozap.com.br`
- **Quando** a sincronização é concluída
- **Então** o `.env` do kivozap mantém suas credenciais originais (não herda
  dados do main)

#### AC-046 — Processos independentes

- **Dado** que cada sistema tem seus próprios processos (Apache, PHP-FPM,
  cron, webhooks, Go extractor)
- **Quando** verifico os processos ativos em cada servidor
- **Então** não há requisições cruzadas entre os sistemas (sem conexões
  CLOSE-WAIT ou ESTABLISHED para o outro servidor)

### US-019 — Teste completo pós-sincronização

Como administrador, quero que todos os módulos sejam testados no kivozap
após a sincronização, para garantir que tudo funciona como no main.

#### AC-047 — Flow builder abre e edita

- **Dado** que o kivozap foi sincronizado
- **Quando** acesso `kivozap.com.br/bot-builder` e abro o editor de um bot
- **Então** a área de edição carrega sem erros (HTTP 200, sem parse errors)

#### AC-048 — Webhook responde corretamente

- **Dado** que o webhook está configurado no kivozap
- **Quando** envio POST para `/whatsapp_webhook` com payload válido
- **Então** responde HTTP 200 com "OK" em menos de 5 segundos

#### AC-049 — Bulk/mass messaging funcional

- **Dado** que `Whatsapp_bulk` foi sincronizado do main
- **Quando** acesso a página de disparo em massa no kivozap
- **Então** a página carrega (HTTP 200) e as funcionalidades de contatos
  e grupos estão disponíveis

#### AC-050 — Exportação de participantes funcional

- **Dado** que `Whatsapp_export_participants` foi sincronizado
- **Quando** acesso a página de exportação no kivozap
- **Então** a página carrega e os filtros (self, admins) estão disponíveis

#### AC-051 — Nenhum erro PHP nos logs

- **Dado** que a sincronização foi concluída e o PHP-FPM reiniciado
- **Quando** verifico os logs de erro do PHP (`writable/logs/`)
- **Então** não há erros de sintaxe, fatal errors nem warnings relacionados
  aos módulos sincronizados

#### AC-052 — Cron jobs independentes

- **Dado** que cada sistema tem seus próprios cron jobs
- **Quando** verifico a crontab de cada servidor
- **Então** os cron jobs apontam apenas para o próprio sistema (sem
  referências cruzadas)

## Fora de escopo

- Sincronização de dados de usuários, mensagens ou contatos entre sistemas
- Alteração de domínios, DNS ou certificados SSL
- Migração de servidor (kivozap permanece em 144.22.167.45)
- Alteração de portas ou configurações de rede
- Sincronização de outros sistemas (Elite, PlusZap, MetaSenderPro, etc.)
- Alteração do sistema main (zapmatic)

## Suposições

| ID | Suposição | Status | Resolução |
|---|---|---|---|
| ASM-018 | O Bot_builder.php já foi corrigido na auditoria anterior (parse error removido) | confirmada | php -l passou sem erros |
| ASM-019 | As tabelas legadas do kivozap (`sp_whatsapp_autoresponder`, `sp_whatsapp_chatbot`) não são usadas por nenhum módulo ativo | aberta | — |
| ASM-020 | O `.env` do kivozap não deve ser alterado (mantém credenciais próprias) | confirmada | usuário solicitou explicitamente |
| ASM-021 | Os processos Go (extractor, cron) do kivozap são independentes dos do main | aberta | — |
| ASM-022 | Os dados existentes nas tabelas do kivozap (mensagens, contatos, sessões) devem ser preservados | confirmada | usuário solicitou: "não dropar dados existentes" |
| ASM-023 | A cópia de código deve ser feita via SCP/SSH, não via git (kivozap não é repo git) | confirmada | auditoria mostrou que app_abner_app não tem .git |
| ASM-024 | O PHP-FPM e Apache do kivozap devem ser reiniciados após a cópia de código | confirmada | necessário para carregar novos arquivos |

## Perguntas em aberto

| ID | Pergunta | Status | Resposta |
|---|---|---|---|
| Q-014 | As tabelas legadas (`sp_whatsapp_autoresponder`, `sp_whatsapp_chatbot`) têm dados importantes que devem ser preservados? | aberta | — |
| Q-015 | O kivozap usa o mesmo extractor Go que o main, ou tem configuração diferente? | aberta | — |
| Q-016 | Há algum módulo específico do kivozap que NÃO deve ser sobrescrito (customização local)? | aberta | — |
