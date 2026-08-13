// Runner unificado de testes para o motor onp-spec verify.
//
// Roda:
//   1. PHPUnit (tests/phpunit) com o printer TAP (tests/Zapmatic/Spec/TapPrinter.php)
//   2. `go test -v` no módulo app_zapmatic_whatsmeow_api (offline, -mod=vendor)
//
// Ambos emitem TAP no stdout. Este runner os executa em sequência, renumera
// as linhas "ok/not ok" (a engine exige numeração contígua) e devolve exit 0
// apenas se TODOS os passos passarem.
//
// O nome de cada teste carrega @spec:AC-xxx (PHP via docblock; Go via helpers
// tap_test.go) — é assim que a engine casa teste -> critério de aceite.

import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');

function run(cmd, args, opts = {}) {
  const res = spawnSync(cmd, args, {
    cwd: opts.cwd || root,
    encoding: 'utf-8',
    env: { ...process.env, NO_COLOR: '1', ...(opts.env || {}) },
    maxBuffer: 64 * 1024 * 1024,
    shell: false,
  });
  return { res, out: `${res.stdout || ''}\n${res.stderr || ''}` };
}

const phpUnit = path.join(root, 'vendor', 'bin', 'phpunit');
const steps = [];

// 1. PHPUnit com printer TAP
steps.push(run(phpUnit, [
  '-c', path.join(root, 'tests', 'phpunit.xml'),
  '--colors=never',
  '--no-interaction',
  '--printer', 'Zapmatic\\Spec\\TapPrinter',
]));

// 2. Go test (offline, vendor). -v é necessário para o Go NÃO suprimir o
// stdout dos testes que passam (onde as linhas TAP são impressas).
steps.push(run('go', ['test', '-v', './...'], {
  cwd: path.join(root, 'app_zapmatic_whatsmeow_api'),
  env: { GOFLAGS: '-mod=vendor' },
}));

// Coleta e renumera as linhas TAP.
const tapLines = [];
let rawExitOk = true;

for (const s of steps) {
  for (const line of s.out.split(/\r?\n/)) {
    const m = line.match(/^\s*(not )?ok\s+\d+\s*(?:-\s*)?(.*)$/);
    if (m) {
      tapLines.push({ not: !!m[1], title: m[2].trim() });
    }
  }
  if (s.res.status !== 0) rawExitOk = false;
}

const total = tapLines.length;
let out = `1..${total}\n`;
let i = 0;
for (const t of tapLines) {
  i++;
  out += `${t.not ? 'not ok' : 'ok'} ${i} - ${t.title}\n`;
}
process.stdout.write(out);

process.exit(rawExitOk ? 0 : 1);
