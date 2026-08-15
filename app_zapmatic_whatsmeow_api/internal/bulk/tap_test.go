package bulk

import (
	"fmt"
	"sync/atomic"
	"testing"
)

var bulkTapCounter int64

func bulkTapOk(t *testing.T, name, tag string) {
	t.Helper()
	n := atomic.AddInt64(&bulkTapCounter, 1)
	fmt.Printf("ok %d - %s @spec:%s\n", n, name, tag)
}

func bulkTapNotOk(t *testing.T, name, tag, reason string) {
	t.Helper()
	n := atomic.AddInt64(&bulkTapCounter, 1)
	fmt.Printf("not ok %d - %s @spec:%s\n", n, name, tag)
	t.Errorf("%s", reason)
}
