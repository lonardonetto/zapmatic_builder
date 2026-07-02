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
	t.Logf("Simple: %q", result)
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
	t.Logf("Nested: %q", result)
}

func TestExpandSpintaxMultiple(t *testing.T) {
	result := ExpandSpintax("{Hi|Hey} {there|friend}")
	valid := result == "Hi there" || result == "Hi friend" || result == "Hey there" || result == "Hey friend"
	if !valid {
		t.Fatalf("unexpected: %q", result)
	}
	t.Logf("Multiple: %q", result)
}

func TestReplaceParams(t *testing.T) {
	params := map[string]string{"nome": "João", "cidade": "São Paulo"}
	result := ReplaceParams("Olá %nome% de %cidade%", params)
	if result != "Olá João de São Paulo" {
		t.Fatalf("expected param replacement, got %q", result)
	}
}

func TestReplaceCommonData(t *testing.T) {
	result := ReplaceCommonData("[wa_name] - [push_name]", "Maria", "inst123", "Maria App", "5511999999999")
	if result != "Maria - Maria App" {
		t.Fatalf("expected common data replacement, got %q", result)
	}
}

func TestBuildMessage(t *testing.T) {
	params := map[string]string{"nome": "João"}
	result := BuildMessage("Olá %nome%! {Tudo bem|Como vai}?", params, "João", "inst1", "João", "5511999999999")
	if result == "" {
		t.Fatal("expected non-empty")
	}
	t.Logf("BuildMessage: %q", result)
}

func TestSplitSpintaxParts(t *testing.T) {
	parts := splitSpintaxParts("a|b|c")
	if len(parts) != 3 {
		t.Fatalf("expected 3 parts, got %d: %v", len(parts), parts)
	}
	parts = splitSpintaxParts("{a|b}|c")
	if len(parts) != 2 {
		t.Fatalf("expected 2 parts with nesting, got %d: %v", len(parts), parts)
	}
}
