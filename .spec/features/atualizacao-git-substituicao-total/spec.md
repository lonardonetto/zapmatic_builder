# Atualização Completa via Git (Substituição Total de Arquivos)

> **feature:** atualizacao-git-substituicao-total
> **status:** pronto para execução
> **data:** 2026-08-18
> **autor:** Kilo
> **dono do processo:** Leo Netto
> **versão de referência:** v8.5.16 (main)

## Contexto

O main (`zapmatic.tec.br`, `/www/wwwroot/app_zapmatic_app`, banco `db_zapmatic_sql`)
é o laboratório de desenvolvimento. Os demais sistemas são réplicas que devem ser
**100% idênticas ao main** em código, estrutura de banco e comportamento — cada um
com credenciais, domínio, banco, portas e processos próprios.

O método anterior (`sync-*` via rsync) copiava apenas "mudanças" e tinha risco de
faltar arquivo. **A partir desta spec, a atualização é por SUBSTITUIÇÃO TOTAL de
arquivos via git** (`git fetch` + `git reset --hard` + `git checkout`), garantindo
que cada sistema fique byte a byte idêntico ao main naquilo que é versionado.

> **Regra de ouro:** nunca atualizar todos de uma vez. Atualizar **um sistema por
> vez**, acompanhado pelo Leo Netto, testando cada etapa antes de seguir para o
> próximo.

---

## 1. Inventário do main (linha de base)

> Levantado em 2026-08-18, main na tag `v8.5.16`.

| Item | Valor |
|---|---|
| Banco de dados | `db_zapmatic_sql` |
| Tabelas (total) | **76** |
| Colunas (total) | **918** |
| Módulos em `inc/core/` | **57** pastas de módulo |
| Arquivos em `inc/core/` (todos) | 6749 (6348 `.php`) |
| Arquivos `inc/core/` versionados no git | 859 (687 `.php` + assets) |
| Módulos em `inc/plugins/` | 14 (Payment gateways) |
| Repositório git | `github.com/lonardonetto/zapmatic_builder` |
| Branch | `main` |

### 1.1 O que o git versiona (vai para os sistemas)

| Caminho | Versionado? | Observação |
|---|---|---|
| `inc/core/` (fora de `*/Libraries/vendor/`) | ✅ | **todo** arquivo `.php`, `.js`, `.css`, `.json`, views, assets |
| `inc/plugins/` (fora de `*/vendor/`) | ✅ | todo código de pagamento |
| `inc/themes/` | ✅ | 3003 arquivos |
| `app/` | ✅ | exceto `app/Config/google-service-account.json` (gitignored) |
| `app_zapmatic_whatsmeow_api/` (código Go) | ✅ | exceto `config.json`, binary, `storage/sessions`, `logs` |
| `app_zapmatic_scraper/` | ✅ | exceto `node_modules/` |
| `assets/`, `migrations/`, `sql/`, `docs/`, `_bmad/`, `.spec/` | ✅ | |
| Root files (`spark`, `composer.json`, `index.php`, etc.) | ✅ | |

### 1.2 O que o git **NÃO** versiona (preservar no destino)

| Caminho | Motivo |
|---|---|
| `.env` | credenciais do tenant (gitignored) |
| `vendor/` (raiz) | dependências Composer (292M, gitignored) |
| `*/Libraries/vendor/`, `*/vendor/` (módulos/plugins) | dependências (78M File_manager, etc.) |
| `writable/` | logs, áudios, cache, sessions (gitignored) |
| `app_zapmatic_whatsmeow_api/config.json` | credenciais/porta do Go (gitignored) |
| `app_zapmatic_whatsmeow_api/zapmatic-whatsmeow` | binary compilado |
| `app_zapmatic_whatsmeow_api/storage/`, `logs/` | runtime |
| `app_zapmatic_scraper/node_modules/` | dependências Node |
| `app/Config/google-service-account.json` | secret (gitignored) |

---

## 2. Sistemas e variáveis (preencher 1 por vez)

| Variavel | astros | elias | paulo | renovo | (proximos) |
|---|---|---|---|---|---|
| `{PATH}` | `/www/wwwroot/app.astroscomunicacaodigital.com` | `/www/wwwroot/app_elias_app` | `/www/wwwroot/app_paulo_app` | `/www/wwwroot/renovo_app` | — |
| `{DOMAIN}` | app.astroscomunicacaodigital.com | multiconnecta.com.br | atualizaleads.app.br | renovo.app | — |
| `{DB_NAME}` | sql_eudezio_db | sql_elias_db | sql_paulo_db | db_renovo_sql | — |
| `{DB_USER}` | sql_eudezio_db | sql_elias_db | sql_paulo_db | db_renovo_sql | — |
| `{GO_PORT}` | 8094 | 8092 | 8091 | 8093 | — |
| `{TENANT}` | astros | elias | paulo | renovo | — |
| git no destino? | **NÃO** | **NÃO** | **NÃO** | SIM (sem commits) | — |
| **Status** | pendente | pendente | pendente | pendente | — |

> Os sistemas remotos (MetaSenderPro, Kivozap, Chatbut, AgenciaMCW, IaClicks, Elite,
> PlusZap) seguem o mesmo processo; preencher as variáveis na hora de cada um.

---

## 3. Processo de atualização (por sistema)

### Etapa 0 — Pré-requisitos e aprovação

- [ ] Leo Netto confirma qual sistema será atualizado (somente 1).
- [ ] Backup completo do destino criado (Etapa 1).
- [ ] Sem campanhas em execução no destino no momento (ou pausadas).

### Etapa 1 — Backup completo do destino

```bash
TS=$(date +%Y%m%d_%H%M%S)
BK=/www/backup_zapmatic_update/${TS}
mkdir -p "$BK"

# 1.1 Dump do banco de dados (schema + dados)
mysqldump -u {DB_USER} -p'{DB_PASS}' --single-transaction --routines --triggers \
  {DB_NAME} > "$BK/{DB_NAME}.sql"

# 1.2 Backup dos arquivos tenant-specific
cp -a {PATH}/.env "$BK/.env"
cp -a {PATH}/app_zapmatic_whatsmeow_api/config.json "$BK/config.json"
cp -a {PATH}/app/Config/google-service-account.json "$BK/google-service-account.json" 2>/dev/null || true
cp -a {PATH}/ecosystem.config.js "$BK/ecosystem.config.js"

# 1.3 Backup do writable (logs/audios/sessions) — opcional mas recomendado
tar czf "$BK/writable.tgz" -C {PATH} writable 2>/dev/null || true
```

> **Critério:** `$BK` contém `{DB_NAME}.sql` + `.env` + `config.json` + `ecosystem.config.js`.

### Etapa 2 — Comparação de banco (tabelas + colunas)

**Objetivo:** identificar exatamente o que falta no destino para ficar igual ao main.

```bash
# 2.1 Dump de schema do main (referência)
mysqldump -u db_zapmatic_sql -pinTwk7z37PnhWcY5 --no-data --single-transaction \
  db_zapmatic_sql > /tmp/main_schema.sql

# 2.2 Dump de schema do destino
mysqldump -u {DB_USER} -p'{DB_PASS}' --no-data --single-transaction \
  {DB_NAME} > /tmp/{TENANT}_schema.sql
```

**2.3 Comparar tabelas:**

```bash
grep "CREATE TABLE" /tmp/main_schema.sql | sed 's/.*`\([^`]*\)`.*/\1/' | sort > /tmp/main_tables.txt
grep "CREATE TABLE" /tmp/{TENANT}_schema.sql | sed 's/.*`\([^`]*\)`.*/\1/' | sort > /tmp/{TENANT}_tables.txt

echo "=== FALTAM no destino (adicionar) ==="
comm -23 /tmp/main_tables.txt /tmp/{TENANT}_tables.txt

echo "=== LEGADO no destino (dropar após confirmação do Leo) ==="
comm -13 /tmp/main_tables.txt /tmp/{TENANT}_tables.txt

echo "=== Total main ==="; wc -l < /tmp/main_tables.txt   # esperado: 76
echo "=== Total destino ==="; wc -l < /tmp/{TENANT}_tables.txt
```

**2.4 Comparar colunas (arquivo por tabela):** usar o script Python da seção 6.

```bash
python3 /tmp/compare_columns.py /tmp/main_schema.sql /tmp/{TENANT}_schema.sql
```

**Regras:**
- FALTAM no destino → `ALTER TABLE ... ADD COLUMN` / `CREATE TABLE`
- Existe no destino mas NÃO no main (legado) → `DROP` **somente com aprovação do Leo**
- NUNCA dropar dados — apenas estrutura
- Gerar `{TENANT}_migration.sql` com as diferenças (idempotente)

### Etapa 3 — Aplicar migration no destino

```bash
mysql -u {DB_USER} -p'{DB_PASS}' {DB_NAME} < /tmp/{TENANT}_migration.sql

# Validar contagem final
mysql -u {DB_USER} -p'{DB_PASS}' {DB_NAME} -e \
  "SELECT COUNT(*) tables_count FROM information_schema.tables WHERE table_schema='{DB_NAME}'"
# esperado: 76
mysql -u {DB_USER} -p'{DB_PASS}' {DB_NAME} -e \
  "SELECT COUNT(*) columns_count FROM information_schema.columns WHERE table_schema='{DB_NAME}'"
# esperado: 918
```

> **Critério:** tabelas = 76 e colunas = 918 no destino.

### Etapa 4 — Substituição total de arquivos via git

**4.1 Se o destino já é um repositório git (ex.: renovo):**

```bash
cd {PATH}
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/lonardonetto/zapmatic_builder.git
git fetch origin main
git checkout -B main origin/main      # força a branch local para o main remoto
git reset --hard origin/main
```

> Após o `reset --hard`, o destino fica **byte a byte idêntico** ao main em tudo que
> é versionado. Arquivos gitignored (`.env`, `writable`, `vendor`, `config.json`) são
> **intocados** pelo git.

**4.2 Se o destino NÃO é repositório git (ex.: astros, elias, paulo):**

```bash
cd {PATH}
git init
git remote add origin https://github.com/lonardonetto/zapmatic_builder.git
git fetch origin main
git checkout -B main origin/main
git reset --hard origin/main
```

> Isso transforma o destino em repo git **sem destruir os arquivos gitignored**:
> `.env`, `writable/`, `vendor/`, `app_zapmatic_whatsmeow_api/config.json`,
> `app/Config/google-service-account.json`, `node_modules/` permanecem como estão
> porque estão listados no `.gitignore` do main e o git não os sobrescreve.

**4.3 Confirmação de preservação (obrigatório):**

```bash
# Estes devem permanecer com os valores do DESTINO (não do main):
grep -E "^(database.default.hostname|database.default.database|database.default.username)" {PATH}/.env
cat {PATH}/app_zapmatic_whatsmeow_api/config.json | grep -E '"port"|"webhook_url"|"name"'
```

> **Critério:** `webhook_url` aponta para `{DOMAIN}`, e o banco é `{DB_NAME}`.
> Nenhum valor `db_zapmatic_sql` / `zapmatic.tec.br` presente.

### Etapa 5 — Dependências (Composer + Node)

```bash
# Composer (raiz)
cd {PATH} && php /usr/local/bin/composer install --no-dev --optimize-autoloader 2>/dev/null \
  || composer install --no-dev --optimize-autoloader

# Módulos com vendor próprio (se necessário, ver Etapa 7.2)
# File_manager/Libraries/vendor e plugins/*/vendor NÃO vêm pelo git → só atualizar
# se o diff de composer.json deles tiver mudado.

# Node (scraper)
cd {PATH}/app_zapmatic_scraper && npm install --production
```

> **Nota:** `vendor/` (raiz) e os `vendor/` de módulos/plugins são gitignored. O git
> **não** os traz nem os remove. Se o main adicionou/removeu dependências, rodar
> `composer install` atualiza; senão, o vendor existente continua válido.

### Etapa 6 — Compilar o binary Go

```bash
export PATH=/usr/local/go/bin:$PATH
export CGO_ENABLED=1          # obrigatório para sqlite3/whatsmeow
cd {PATH}/app_zapmatic_whatsmeow_api
go build -o zapmatic-whatsmeow ./cmd/server/
chmod +x zapmatic-whatsmeow
```

> NUNCA cross-compile (`CGO_ENABLED=0`). Compilar **no próprio servidor destino**.

### Etapa 7 — Ajustes de ambiente do destino

**7.1 `ecosystem.config.js` (tenant-specific — NÃO vem pelo git corretamente):**

> O `ecosystem.config.js` do main está trackeado no git e, por isso, o `reset --hard`
> vai trazer a versão do MAIN (com `cwd: /www/wwwroot/app_zapmatic_app` e prefixo
> `zapmatic-`). **É obrigatório reescrever** com os valores do destino após o reset.

```javascript
module.exports = {
  apps: [
    { name: "{TENANT}-bot-worker-all", script: "spark", args: "bot:all", interpreter: "php",
      cwd: "{PATH}", autorestart: true, max_memory_restart: "256M",
      error_file: "writable/logs/pm2-all-error.log", out_file: "writable/logs/pm2-all-out.log" },
    { name: "{TENANT}-call-worker", script: "spark", args: "call:campaigns", interpreter: "php",
      cwd: "{PATH}", autorestart: true, max_memory_restart: "128M",
      error_file: "writable/logs/pm2-call-error.log", out_file: "writable/logs/pm2-call-out.log" },
    { name: "{TENANT}-cloud-campaign-worker", script: "spark", args: "cloud:campaigns", interpreter: "php",
      cwd: "{PATH}", autorestart: true, max_memory_restart: "128M",
      error_file: "writable/logs/pm2-cloud-campaign-error.log", out_file: "writable/logs/pm2-cloud-campaign-out.log" },
    { name: "{TENANT}-gmscraper", script: "index.js", interpreter: "node",
      cwd: "{PATH}/app_zapmatic_scraper", autorestart: true, max_memory_restart: "256M" },
  ]
};
```

**7.2 `app_zapmatic_whatsmeow_api/config.json`** — já preservado (gitignored). Validar:

```json
{
  "port": "{GO_PORT}",
  "webhook_url": "https://{DOMAIN}/index.php/bot-builder/webhook",
  "database": { "name": "{DB_NAME}", "user": "{DB_USER}", "password": "{DB_PASS}" }
}
```

**7.3 systemd service** — manter; validar que aponta para `{PATH}` e `{GO_PORT}`.

### Etapa 8 — Reiniciar processos

```bash
# Go gateway
sudo systemctl restart zapmatic-whatsmeow-{TENANT}

# PHP-FPM (para limpar opcache)
sudo /etc/init.d/php-fpm-81 reload || sudo kill -USR2 $(pgrep -f "php-fpm: master" | head -1)

# PM2
cd {PATH}
sudo pm2 delete all 2>/dev/null || true
sudo pm2 start ecosystem.config.js
sudo pm2 save
```

---

## 4. Testes de validação (executar e registrar)

> Cada teste tem critério de sucesso. Registrar resultado real em um arquivo
> `.spec/verification/atualizacao-{TENANT}.json`.

| # | Teste | Comando | Critério |
|---|---|---|---|
| T1 | Web interface | `curl -s -o /dev/null -w "%{http_code}" https://{DOMAIN}/` | `200` |
| T2 | Go health | `curl -s http://localhost:{GO_PORT}/health` | `status ok` |
| T3 | Tabelas | `SELECT COUNT(*) FROM information_schema.tables` | `76` |
| T4 | Colunas | `SELECT COUNT(*) FROM information_schema.columns` | `918` |
| T5 | Identidade `inc/core` | `git status --short inc/` | sem modificações (apenas untracked de vendor) |
| T6 | Credenciais | `grep -c "db_zapmatic_sql\|zapmatic.tec.br" .env config.json` | `0` |
| T7 | PM2 | `pm2 list` | 4 processos `{TENANT}-*` online |
| T8 | Systemd | `systemctl is-active zapmatic-whatsmeow-{TENANT}` | `active` |
| T9 | Bot Builder | abrir `{DOMAIN}/bot-builder` no navegador | página carrega, HTTP 200 |
| T10 | Autoresponder | enviar msg para número conectado do sistema | resposta automática chega |
| T11 | Bulk Cloud API | criar/env de campanha Cloud API de teste | mensagens entregues (`delivered`) |
| T12 | Call API | `GET /call/list` | `status success` |
| T13 | Botões de URL | fluxo com botão URL | botão chega como `cta_url` (redireciona) |
| T14 | Webhook Cloud API | `POST /whatsapp_webhook` payload válido | `200 OK` |
| T15 | Sem erro PHP | verificar `writable/logs/log-*.log` | sem fatal/parse errors |

---

## 5. Comparação arquivo a arquivo do `inc/core`

> Requisito do Leo: analisar **pasta por pasta e arquivo por arquivo** se são
> idênticos ao main. O `git reset --hard` já garante identidade byte a byte para
> tudo que é versionado. Para **provar**, usar hash:

### 5.1 Comparação por hash (SHA-256) — arquivos versionados

```bash
# Gerar manifest de hashes do main (referência)
cd /www/wwwroot/app_zapmatic_app
git ls-files inc/core inc/plugins inc/themes app | while read f; do
  sha256sum "$f"
done | sort > /tmp/main_hashes.txt

# Gerar manifest do destino
cd {PATH}
git ls-files inc/core inc/plugins inc/themes app | while read f; do
  sha256sum "$f"
done | sort > /tmp/{TENANT}_hashes.txt

# Comparar
diff /tmp/main_hashes.txt /tmp/{TENANT}_hashes.txt
```

> **Critério:** `diff` sem saída (0 diferenças) para todos os arquivos versionados.

### 5.2 Auditoria pasta a pasta (relatório)

```bash
# Lista de módulos (57 no inc/core) com estado de identidade
for d in inc/core/*/; do
  mod=$(basename "$d")
  main_n=$(find /www/wwwroot/app_zapmatic_app/$d -name "*.php" -not -path "*/vendor/*" | wc -l)
  dst_n=$(find {PATH}/$d -name "*.php" -not -path "*/vendor/*" | wc -l)
  diff_out=$(diff -rq /www/wwwroot/app_zapmatic_app/$d {PATH}/$d \
    --exclude=vendor --exclude=node_modules 2>/dev/null | wc -l)
  echo "$mod | main_php=$main_n dst_php=$dst_n diffs=$diff_out"
done
```

> **Critério:** todos os módulos com `diffs=0` (exceto diretórios gitignored).

---

## 6. Script auxiliar — comparação de colunas

```python
# /tmp/compare_columns.py
import re, sys

def extract_columns(path):
    tables, current = {}, None
    with open(path) as f:
        for line in f:
            m = re.match(r'CREATE TABLE `(\w+)`', line)
            if m:
                current = m.group(1); tables[current] = []
            elif current and line.strip().startswith('`'):
                cm = re.match(r'`(\w+)`', line.strip())
                if cm: tables[current].append(cm.group(1))
            elif current and line.strip().startswith(')'):
                current = None
    return tables

main = extract_columns(sys.argv[1])
dst = extract_columns(sys.argv[2])

for t in sorted(set(main) | set(dst)):
    if t not in dst:
        print(f"[FALTA TABELA] {t} ({len(main[t])} cols)")
        continue
    if t not in main:
        print(f"[LEGADO TABELA] {t}")
        continue
    miss = set(main[t]) - set(dst[t])
    extra = set(dst[t]) - set(main[t])
    if miss or extra:
        print(f"--- {t} ---")
        if miss: print(f"  FALTAM: {sorted(miss)}")
        if extra: print(f"  LEGADO: {sorted(extra)}")
```

---

## 7. Checklist de segurança

- [ ] Nunca substituir `.env` do destino
- [ ] Nunca substituir `writable/` do destino
- [ ] Nunca substituir `app_zapmatic_whatsmeow_api/config.json` do destino
- [ ] Nunca substituir `app/Config/google-service-account.json` do destino
- [ ] Nunca dropar tabela/coluna que existe no main
- [ ] Nunca dropar dados — apenas estrutura legado (com aprovação do Leo)
- [ ] Nunca usar credenciais do main no destino
- [ ] Compilar Go com `CGO_ENABLED=1` no próprio servidor
- [ ] Reescrever `ecosystem.config.js` com valores do destino após o reset
- [ ] Verificar que `webhook_url` aponta para `{DOMAIN}`
- [ ] Verificar que `.env` não contém `db_zapmatic_sql` nem `zapmatic.tec.br`
- [ ] Rodar a **etapa de teste completa (seção 4)** antes de marcar concluído

---

## 8. Fora de escopo

- Sincronização de dados (usuários, mensagens, contatos) entre sistemas.
- Alteração de DNS, domínio ou SSL do destino.
- Migração de servidor físico.
- Atualização de mais de um sistema por vez.

## 9. Suposições e decisões

| ID | Suposição | Status |
|---|---|---|
| S1 | `ecosystem.config.js` e `config.json` são tenant-specific, mas só `config.json` é gitignored | confirmada — reescrever ecosystem após reset |
| S2 | Os `vendor/` de módulos/plugins não são versionados e não precisam ser atualizados a cada release (só quando composer.json mudar) | aberta |
| S3 | `app/Config/google-service-account.json` é segredo e não deve ir via git | confirmada (gitignored) |
| S4 | Os destinos `astros`/`elias`/`paulo` não são repos git; o `git init` + fetch é seguro pois não apaga gitignored | aberta — validar na 1ª execução |
| S5 | `renovo_app` tem `.git` vazio (sem commits); tratar como "não é repo" (git init) | confirmada |

## 10. Perguntas em aberto

| ID | Pergunta | Resposta necessária |
|---|---|---|
| P1 | Qual é o **primeiro** sistema a atualizar? | Leo Netto define (um por vez) |
| P2 | Os `vendor/` de módulos (File_manager 78M, plugins) devem ser revalidados a cada release ou mantidos? | Leo Netto decide |
| P3 | Legados de banco (tabelas/colunas extras no destino) devem ser dropados ou mantidos? | Leo Netto decide caso a caso |
| P4 | Backup do `writable/` completo é obrigatório em todo sistema? | Leo Netto decide (recomendado) |
