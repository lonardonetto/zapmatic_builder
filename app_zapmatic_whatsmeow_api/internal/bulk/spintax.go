package bulk

import (
	"math/rand"
	"regexp"
	"strings"
)

var (
	// spintaxRe captura apenas blocos entre chaves que contenham ao menos um pipe '|' (ex: {a|b}).
	// Blocos sem pipe (ex: {nome}, {var1}) são ignorados pelo Spintax para que ReplaceParams os substitua.
	spintaxRe = regexp.MustCompile(`\{([^{}]*?\|[^{}]*?)\}`)

	// paramRe captura variáveis em chaves {var}, porcentagens %var% e colchetes [var].
	paramRe = regexp.MustCompile(`(?i)(?:\{([a-z0-9_]+)\}|\%([a-z0-9_]+)\%|\[([a-z0-9_]+)\])`)
)

// ExpandSpintax processa {opcao1|opcao2|opcao3} substituindo por um valor aleatório.
// Requer a presença do pipe '|' dentro do bloco para não destruir marcadores como {nome}.
func ExpandSpintax(input string) string {
	if input == "" {
		return ""
	}
	prev := ""
	current := input
	// Itera até não haver mais mudanças (resolve aninhamento: {a|{b|c}})
	for current != prev {
		prev = current
		current = spintaxRe.ReplaceAllStringFunc(current, func(match string) string {
			inner := match[1 : len(match)-1]
			parts := splitSpintaxParts(inner)
			if len(parts) == 0 {
				return ""
			}
			return strings.TrimSpace(parts[rand.Intn(len(parts))])
		})
	}
	return current
}

// splitSpintaxParts separa por "|" respeitando blocos aninhados {}
func splitSpintaxParts(s string) []string {
	var parts []string
	depth := 0
	start := 0
	for i, ch := range s {
		switch ch {
		case '{':
			depth++
		case '}':
			depth--
		case '|':
			if depth == 0 {
				parts = append(parts, s[start:i])
				start = i + 1
			}
		}
	}
	if start <= len(s) {
		parts = append(parts, s[start:])
	}
	return parts
}

// ReplaceParams substitui {variavel}, %variavel% e [variavel] por valores do mapa de parâmetros do contato.
// Suporta busca case-insensitive e alias v1/var1/1.
func ReplaceParams(input string, params map[string]string) string {
	if input == "" || len(params) == 0 {
		return input
	}

	// Normaliza as chaves do mapa para lowercase
	normalized := make(map[string]string, len(params))
	for k, v := range params {
		lk := strings.ToLower(strings.TrimSpace(k))
		normalized[lk] = v
	}

	return paramRe.ReplaceAllStringFunc(input, func(match string) string {
		sub := paramRe.FindStringSubmatch(match)
		var key string
		for _, k := range sub[1:] {
			if k != "" {
				key = strings.ToLower(k)
				break
			}
		}

		if key == "" {
			return match
		}

		// 1. Busca direta por chave exata (ex.: "nome", "var1", "cidade")
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

// lookupParamAlias resolve equivalências comuns entre cabeçalhos de planilha.
func lookupParamAlias(normalized map[string]string, key string) (string, bool) {
	aliases := []string{}

	if strings.HasPrefix(key, "var") && len(key) > 3 {
		num := key[3:]
		aliases = append(aliases, "v"+num, num)
	} else if strings.HasPrefix(key, "v") && len(key) > 1 {
		num := key[1:]
		aliases = append(aliases, "var"+num, num)
	} else if len(key) >= 1 && key[0] >= '0' && key[0] <= '9' {
		aliases = append(aliases, "v"+key, "var"+key)
	}

	for _, alias := range aliases {
		if val, ok := normalized[alias]; ok {
			return val, true
		}
	}
	return "", false
}

// ReplaceCommonData substitui placeholders como [wa_name], [instance_id] etc.
func ReplaceCommonData(input string, waName, instanceID, pushName, phone string) string {
	if input == "" {
		return ""
	}
	result := input
	result = strings.ReplaceAll(result, "[wa_name]", waName)
	result = strings.ReplaceAll(result, "[instance_id]", instanceID)
	result = strings.ReplaceAll(result, "[push_name]", pushName)
	result = strings.ReplaceAll(result, "[phone]", phone)
	result = strings.ReplaceAll(result, "[nome]", waName)
	result = strings.ReplaceAll(result, "[numero]", phone)
	return result
}

// BuildMessage aplica spintax + common data + params na sequência correta.
func BuildMessage(caption string, params map[string]string, waName, instanceID, pushName, phone string) string {
	// 1. Spintax primeiro (apenas blocos com pipe)
	result := ExpandSpintax(caption)
	// 2. Common data placeholders ([wa_name], [phone], etc.)
	result = ReplaceCommonData(result, waName, instanceID, pushName, phone)
	// 3. Parâmetros customizados do contato ({var}, %var%, [var])
	result = ReplaceParams(result, params)
	return result
}
