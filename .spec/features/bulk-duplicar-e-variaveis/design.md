# Design: Duplicação de Campanhas de Bulk Continuada e Substituição de Variáveis de Planilha

> feature: bulk-duplicar-e-variaveis

## Visão geral

O envio de mensagens em massa (Bulk) é executado pelo motor Go (`app_zapmatic_whatsmeow_api/internal/bulk/`) utilizando a fórmula de **offset persistente**:

$$\text{offset} = \text{sent} + \text{failed}$$

Esta arquitetura apresenta duas lacunas de comportamento identificadas:

1. **Zeramento no `duplicate()`:** O controller PHP `Whatsapp_bulk.php` forçava `sent = 0` e `failed = 0` ao duplicar uma campanha, resetando o offset para zero em vez de manter a posição da campanha original.
2. **Conflito no parser de mensagem Go (`spintax.go`):** O regex de Spintax interceptava qualquer texto entre chaves `{...}` — como `{nome}` ou `{var1}` —, consumindo as chaves antes da substituição de variáveis da planilha.

---

## 1. Ajuste da Duplicação no Controller PHP (`Whatsapp_bulk.php`)

### Comportamento no `duplicate()`

Ao duplicar um registro de `TB_WHATSAPP_SCHEDULES`:

```php
// ANTES (resetava tudo):
$item->sent = 0;
$item->failed = 0;

// DEPOIS (preserva o progresso para continuar de onde parou):
$item->sent = (int)($originalItem->sent ?? 0);
$item->failed = (int)($originalItem->failed ?? 0);
```

### Duplicação de Grupos (`sp_whatsapp_schedule_groups`)

Para campanhas onde `target_type === 'groups'`:

```php
if ($item->target_type === 'groups') {
    $groups = db_fetch("*", "sp_whatsapp_schedule_groups", ["schedule_id" => $originalId]);
    if (!empty($groups)) {
        foreach ($groups as $g) {
            unset($g->id);
            $g->schedule_id = $newScheduleId;
            db_insert("sp_whatsapp_schedule_groups", (array)$g);
        }
    }
}
```

Isso garante que campanhas de grupos duplicadas mantenham os grupos selecionados e o offset correto.

---

## 2. Ajuste do Parser de Mensagens no Motor Go (`spintax.go`)

### 2.1 Spintax Apenas com Pipe (`|`)

Alteração do regex de Spintax em `spintax.go`:

```go
// ANTES (capturava qualquer {...}):
spintaxRe = regexp.MustCompile(`\{([^{}]*?)\}`)

// DEPOIS (exige a presença do pipe | dentro do bloco):
spintaxRe = regexp.MustCompile(`\{([^{}]*?\|[^{}]*?)\}`)
```

Com isso:
- `{Olá|Oi|Bom dia}` é processado pelo Spintax (sorteia uma das opções).
- `{nome}`, `{pedido}`, `{var1}`, `{v1}` são ignorados pelo Spintax e chegam intactos ao `ReplaceParams`.

### 2.2 Substituição Universal de Variáveis (`ReplaceParams`)

Ajuste em `ReplaceParams` para aceitar múltiplos formatos:

1. `{variavel}`, `{v1}`, `{1}`
2. `%variavel%`, `%v1%`, `%1%`
3. `[variavel]`, `[v1]`, `[1]`

Exemplo de implementação em Go:

```go
func ReplaceParams(input string, params map[string]string) string {
	if input == "" || len(params) == 0 {
		return input
	}

	// Normaliza as chaves do mapa para lowercase + alias v1/var1
	normalized := make(map[string]string)
	for k, v := range params {
		lk := strings.ToLower(strings.TrimSpace(k))
		normalized[lk] = v
	}

	// Regex universal para {var}, %var% e [var]
	varRe := regexp.MustCompile(`(?i)(?:\{([a-z0-9_]+)\}|\%([a-z0-9_]+)\%|\[([a-z0-9_]+)\])`)

	return varRe.ReplaceAllStringFunc(input, func(match string) string {
		sub := varRe.FindStringSubmatch(match)
		var key string
		for _, k := range sub[1:] {
			if k != "" {
				key = strings.ToLower(k)
				break
			}
		}
		
		// 1. Busca por chave exata (ex.: "nome", "var1", "cidade")
		if val, ok := normalized[key]; ok {
			return val
		}
		
		// 2. Busca por alias alternativo: v1 <-> var1 <-> 1
		if val, ok := lookupParamAlias(normalized, key); ok {
			return val
		}

		return match
	})
}
```

---

## Matriz de Cobertura de Testes

| Teste | Função | Escopo |
|---|---|---|
| `TestExpandSpintaxOnlyWithPipe` | `ExpandSpintax` | Valida que `{nome}` não é alterado pelo Spintax |
| `TestReplaceParamsFormats` | `ReplaceParams` | Valida `{var}`, `%var%`, `[var]` com maiúsculas/minúsculas |
| `TestReplaceParamsAlias` | `ReplaceParams` | Valida `{v1}` mapeando para chave `var1` ou `1` |
| `TestDuplicateCampaignOffset` | `Whatsapp_bulk::duplicate` | Valida preservação de `sent` e `failed` no PHP |
| `TestDuplicateScheduleGroups` | `Whatsapp_bulk::duplicate` | Valida cópia de registros da tabela `sp_whatsapp_schedule_groups` |
