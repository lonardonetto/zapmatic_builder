package bulk

import (
	"math/rand"
	"regexp"
	"strings"
)

var (
	spintaxRe = regexp.MustCompile(`\{([^{}]*?)\}`)
	paramRe   = regexp.MustCompile(`(?i)\%([a-z0-9_]+)\%`)
)

// ExpandSpintax processa {opcao1|opcao2|opcao3} substituindo por um valor aleatório.
// Também suporta aninhamento: {a|{b|c}}.
func ExpandSpintax(input string) string {
	if input == "" {
		return ""
	}
	prev := ""
	current := input
	// Itera até não haver mais mudanças (resolve aninhamento)
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

// ReplaceParams substitui %variavel% por valores do mapa de parâmetros do contato.
func ReplaceParams(input string, params map[string]string) string {
	if input == "" || len(params) == 0 {
		return input
	}
	return paramRe.ReplaceAllStringFunc(input, func(match string) string {
		key := strings.ToLower(match[1 : len(match)-1])
		if val, ok := params[key]; ok {
			return val
		}
		return match
	})
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
	// 1. Spintax primeiro
	result := ExpandSpintax(caption)
	// 2. Common data placeholders
	result = ReplaceCommonData(result, waName, instanceID, pushName, phone)
	// 3. Parâmetros customizados do contato (%var%)
	result = ReplaceParams(result, params)
	return result
}
