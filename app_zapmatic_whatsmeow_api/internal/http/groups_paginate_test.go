package http

// @spec:AC-004 @spec:AC-013 — paginação do gateway Go (page/limit/total) e
// comportamento legado (sem page retorna todos).
import (
	"testing"
)

func mkGroups(n int) []GroupInfo {
	groups := make([]GroupInfo, n)
	for i := 0; i < n; i++ {
		groups[i] = GroupInfo{JID: string(rune('a' + i))}
	}
	return groups
}

func TestPaginateGroupsSliceAndTotal(t *testing.T) {
	groups := mkGroups(10)
	page, total := paginateGroups(groups, 2, 4)
	if total != 10 {
		tapNotOk(t, "TestPaginateGroupsSliceAndTotal", "AC-004", "total inesperado")
		return
	}
	if len(page) != 4 {
		tapNotOk(t, "TestPaginateGroupsSliceAndTotal", "AC-004", "tamanho da página inesperado")
		return
	}
	tapOk(t, "TestPaginateGroupsSliceAndTotal", "AC-004")
}

func TestPaginateGroupsInvalidPageEmpty(t *testing.T) {
	groups := mkGroups(10)
	page0, _ := paginateGroups(groups, 0, 4)
	pageNeg, _ := paginateGroups(groups, -1, 4)
	pageFar, _ := paginateGroups(groups, 99, 4)
	if len(page0) != 0 || len(pageNeg) != 0 || len(pageFar) != 0 {
		tapNotOk(t, "TestPaginateGroupsInvalidPageEmpty", "AC-004", "página inválida deveria ser vazia")
		return
	}
	tapOk(t, "TestPaginateGroupsInvalidPageEmpty", "AC-004")
}

func TestPaginateGroupsInvalidLimitDefault(t *testing.T) {
	groups := mkGroups(3)
	page, _ := paginateGroups(groups, 1, 0)
	if len(page) != 3 {
		tapNotOk(t, "TestPaginateGroupsInvalidLimitDefault", "AC-004", "limite inválido deveria cair no padrão")
		return
	}
	tapOk(t, "TestPaginateGroupsInvalidLimitDefault", "AC-004")
}

func TestSelectGroupsNoPageReturnsAll(t *testing.T) {
	groups := mkGroups(10)
	out, total, page := selectGroups(groups, "", 0)
	if len(out) != 10 || total != 10 || page != 0 {
		tapNotOk(t, "TestSelectGroupsNoPageReturnsAll", "AC-013", "sem page deveria retornar todos")
		return
	}
	tapOk(t, "TestSelectGroupsNoPageReturnsAll", "AC-013")
}

func TestSelectGroupsWithPagePaginates(t *testing.T) {
	groups := mkGroups(10)
	out, total, page := selectGroups(groups, "2", 4)
	if len(out) != 4 || total != 10 || page != 2 {
		tapNotOk(t, "TestSelectGroupsWithPagePaginates", "AC-004", "com page deveria paginar")
		return
	}
	tapOk(t, "TestSelectGroupsWithPagePaginates", "AC-004")
}
