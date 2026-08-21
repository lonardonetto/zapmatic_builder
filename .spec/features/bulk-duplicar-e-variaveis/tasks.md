# Tasks: Duplicação de Campanhas de Bulk Continuada e Substituição de Variáveis de Planilha

> feature: bulk-duplicar-e-variaveis

## T-044 — PHP: Preservar contadores sent/failed no duplicate() [concluida]
- Refs: US-031, AC-083
- Arquivos: inc/core/Whatsapp_bulk/Controllers/Whatsapp_bulk.php
- Notas: No método `duplicate()`, manter os valores de `$item->sent` e `$item->failed` da campanha original em vez de zerá-los, permitindo que o motor Go inicie no offset correto (`original_sent + original_failed`) e continue de onde parou.

## T-045 — PHP: Copiar grupos em sp_whatsapp_schedule_groups no duplicate() [concluida]
- Refs: US-031, AC-084
- Arquivos: inc/core/Whatsapp_bulk/Controllers/Whatsapp_bulk.php
- Notas: Ao duplicar campanha onde `target_type === 'groups'`, copiar todos os registros de `sp_whatsapp_schedule_groups` da campanha original associando ao novo `schedule_id`.

## T-046 — Go: Ajustar regex de Spintax para exigir pipe (|) [concluida]
- Refs: US-032, AC-085
- Arquivos: app_zapmatic_whatsmeow_api/internal/bulk/spintax.go
- Notas: Alterar a regex `spintaxRe` para exigir ao menos um `|` dentro do bloco (`\{([^{}]*?\|[^{}]*?)\}`), garantindo que variáveis como `{nome}`, `{var1}`, `{v1}` não sejam consumidas pelo Spintax.

## T-047 — Go: Substituição universal de parâmetros ({var}, %var%, [var]) e aliases [concluida]
- Refs: US-032, AC-086, AC-087
- Arquivos: app_zapmatic_whatsmeow_api/internal/bulk/spintax.go
- Notas: Atualizar `ReplaceParams` para aceitar sintaxes em chaves `{var}`, porcentagem `%var%` e colchetes `[var]`, com busca case-insensitive e suporte a aliases (`v1` <-> `var1` <-> `1`).

## T-048 — Testes Go e PHP para duplicação e substituição de parâmetros [concluida]
- Refs: AC-083, AC-084, AC-085, AC-086, AC-087
- Arquivos: app_zapmatic_whatsmeow_api/internal/bulk/spintax_test.go, tests/phpunit/BulkDuplicateTest.php
- Notas: Adicionar testes automatizados anotados com `@spec:AC-xxx` cobrindo a preservação de Spintax, substituição de parâmetros em múltiplos formatos e a lógica de duplicação.
