package bulk

import "testing"

func TestExpandSpintaxSimple(t *testing.T) {
	result := ExpandSpintax("Hello {world|there|friend}")
	if result == "" {
		t.Fatal("expected non-empty result")
	}
	valid := result == "Hello world" || result == "Hello there" || result == "Hello friend"
	if !valid {
		t.Fatalf("unexpected result: %q", result)
	}
}

// @spec:AC-085
func TestExpandSpintaxOnlyWithPipe(t *testing.T) {
	// Variáveis entre chaves sem pipe não devem ser tocadas pelo Spintax
	input := "Olá {nome}, seu código é {codigo} e {opcao1|opcao2}"
	result := ExpandSpintax(input)
	if result != "Olá {nome}, seu código é {codigo} e opcao1" && result != "Olá {nome}, seu código é {codigo} e opcao2" {
		bulkTapNotOk(t, "TestExpandSpintaxOnlyWithPipe", "AC-085", "Spintax destruiu variáveis sem pipe: "+result)
		return
	}
	bulkTapOk(t, "TestExpandSpintaxOnlyWithPipe", "AC-085")
}

func TestExpandSpintaxNoBraces(t *testing.T) {
	result := ExpandSpintax("Hello world")
	if result != "Hello world" {
		t.Fatalf("expected no change, got %q", result)
	}
}

func TestExpandSpintaxNested(t *testing.T) {
	result := ExpandSpintax("{a|{b|c}}")
	if result == "" {
		t.Fatal("expected non-empty")
	}
}

func TestExpandSpintaxMultiple(t *testing.T) {
	result := ExpandSpintax("{Hi|Hey} {there|friend}")
	valid := result == "Hi there" || result == "Hi friend" || result == "Hey there" || result == "Hey friend"
	if !valid {
		t.Fatalf("unexpected: %q", result)
	}
}

// @spec:AC-086
func TestReplaceParamsFormats(t *testing.T) {
	params := map[string]string{
		"Nome":   "Maria",
		"var1":   "12345",
		"Cidade": "São Paulo",
	}

	// 1. Chaves {var}
	r1 := ReplaceParams("Olá {Nome}, ref {var1} de {cidade}", params)
	if r1 != "Olá Maria, ref 12345 de São Paulo" {
		bulkTapNotOk(t, "TestReplaceParamsFormats", "AC-086", "Falha em chaves {var}: "+r1)
		return
	}

	// 2. Porcentagens %var%
	r2 := ReplaceParams("Olá %nome%, ref %VAR1% de %cidade%", params)
	if r2 != "Olá Maria, ref 12345 de São Paulo" {
		bulkTapNotOk(t, "TestReplaceParamsFormats", "AC-086", "Falha em porcentagens %var%: "+r2)
		return
	}

	// 3. Colchetes [var]
	r3 := ReplaceParams("Olá [nome], ref [var1]", params)
	if r3 != "Olá Maria, ref 12345" {
		bulkTapNotOk(t, "TestReplaceParamsFormats", "AC-086", "Falha em colchetes [var]: "+r3)
		return
	}

	bulkTapOk(t, "TestReplaceParamsFormats", "AC-086")
}

// @spec:AC-086
func TestReplaceParamsAlias(t *testing.T) {
	params := map[string]string{
		"var1": "100",
		"v2":   "200",
	}

	// {v1} mapeia para var1; {var2} mapeia para v2
	r := ReplaceParams("Valores: {v1} e {var2}", params)
	if r != "Valores: 100 e 200" {
		bulkTapNotOk(t, "TestReplaceParamsAlias", "AC-086", "Falha em aliases v1/var1: "+r)
		return
	}
	bulkTapOk(t, "TestReplaceParamsAlias", "AC-086")
}

// @spec:AC-087
func TestReplaceParamsMissingClean(t *testing.T) {
	params := map[string]string{"nome": "João"}
	r := ReplaceParams("Olá {nome}, seu saldo é {saldo}", params)
	if r != "Olá João, seu saldo é {saldo}" {
		bulkTapNotOk(t, "TestReplaceParamsMissingClean", "AC-087", "Variável ausente não mantida intacta: "+r)
		return
	}
	bulkTapOk(t, "TestReplaceParamsMissingClean", "AC-087")
}

// @spec:AC-085 @spec:AC-086
func TestBuildMessageFull(t *testing.T) {
	params := map[string]string{"nome": "Ana", "var1": "VIP"}
	caption := "Olá {nome}! {Bom dia|Boa tarde}, status {var1} para [phone]."
	result := BuildMessage(caption, params, "Ana", "inst1", "Ana", "5511999999999")

	expected1 := "Olá Ana! Bom dia, status VIP para 5511999999999."
	expected2 := "Olá Ana! Boa tarde, status VIP para 5511999999999."

	if result != expected1 && result != expected2 {
		bulkTapNotOk(t, "TestBuildMessageFull", "AC-085", "BuildMessage falhou: "+result)
		return
	}
	bulkTapOk(t, "TestBuildMessageFull", "AC-085")
}
