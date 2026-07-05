package bulk

import "testing"

func TestAccountRotator(t *testing.T) {
	accounts := []int{1, 2, 3}
	r := NewAccountRotator(accounts)

	if r.Len() != 3 {
		t.Fatalf("expected 3, got %d", r.Len())
	}

	// Round-robin
	for i, expected := range []int{1, 2, 3, 1, 2} {
		got := r.Next()
		if got != expected {
			t.Fatalf("iteration %d: expected %d, got %d", i, expected, got)
		}
	}
}

func TestAccountRotatorWithIndex(t *testing.T) {
	accounts := []int{10, 20, 30}
	r := NewAccountRotatorWithIndex(accounts, 1)

	got := r.Next()
	if got != 20 {
		t.Fatalf("expected 20 at index 1, got %d", got)
	}
}

func TestAccountRotatorEmpty(t *testing.T) {
	r := NewAccountRotator(nil)
	if r.Len() != 0 {
		t.Fatal("expected 0 accounts")
	}
	if r.Next() != 0 {
		t.Fatal("expected 0 for empty")
	}
	if r.HasMore() {
		t.Fatal("expected HasMore=false")
	}
}

func TestAccountRotatorCurrent(t *testing.T) {
	r := NewAccountRotator([]int{5, 10})
	r.SetIndex(1)
	if r.Current() != 10 {
		t.Fatalf("expected 10, got %d", r.Current())
	}
}

func TestAccountRotatorSetIndex(t *testing.T) {
	r := NewAccountRotatorWithIndex([]int{100, 200}, 5)
	// index % len = 5 % 2 = 1
	if r.Current() != 200 {
		t.Fatalf("expected 200 after mod, got %d", r.Current())
	}
}
