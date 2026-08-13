package http

import (
	"fmt"
	"sync/atomic"
	"testing"
)

var tapCounter int64

func tapOk(t *testing.T, name, tag string) {
	t.Helper()
	n := atomic.AddInt64(&tapCounter, 1)
	fmt.Printf("ok %d - %s @spec:%s\n", n, name, tag)
}

func tapNotOk(t *testing.T, name, tag, reason string) {
	t.Helper()
	n := atomic.AddInt64(&tapCounter, 1)
	fmt.Printf("not ok %d - %s @spec:%s\n", n, name, tag)
	t.Errorf("%s", reason)
}

func tapSkip(t *testing.T, name, tag, reason string) {
	t.Helper()
	n := atomic.AddInt64(&tapCounter, 1)
	fmt.Printf("ok %d - %s @spec:%s # SKIP\n", n, name, tag)
	t.Skip(reason)
}
