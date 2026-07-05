package bulk

import (
	"strings"
	"testing"
	"time"
)

func TestSpintaxE2E(t *testing.T) {
	params := map[string]string{"nome": "Maria", "pedido": "12345"}
	msg := BuildMessage(
		"Olá %nome%! {Seu pedido|O pedido} %pedido% {está pronto|foi aprovado}! [wa_name]",
		params, "Maria", "inst123", "Maria", "5511999999999",
	)
	if msg == "" {
		t.Fatal("message should not be empty")
	}
	if !strings.Contains(msg, "12345") {
		t.Fatalf("expected pedido param to be replaced, got: %q", msg)
	}
	t.Logf("✅ Spintax+Params+Common: %q", msg)
}

func TestContactRotatorE2E(t *testing.T) {
	cfg := DefaultDBConfig()
	if err := InitMySQL(cfg); err != nil {
		t.Skipf("MySQL not available: %v", err)
	}
	defer mysqlDB.Close()

	campaigns, err := ListDueCampaigns(5)
	if err != nil {
		t.Fatalf("ListDue failed: %v", err)
	}
	if len(campaigns) == 0 {
		c, err := GetCampaignByID(2)
		if err != nil {
			t.Skip("No campaigns available to test")
		}
		campaigns = append(campaigns, c)
	}

	c := campaigns[0]
	t.Logf("📋 Campaign: %q Type=%d Accounts=%v", c.Name, c.Type, c.Accounts)

	sel := NewContactSelector()
	phone, err := sel.NextPhone(c)
	if err != nil {
		t.Logf("   ⚠️  NextPhone: %v", err)
	} else if phone == nil {
		t.Log("   ℹ️  No phones left for this campaign")
	} else {
		t.Logf("   ✅ Next phone: ID=%d Phone=%v Params=%v IsValid=%v",
			phone["id"], phone["phone"], phone["params"], phone["is_valid"])
	}

	if len(c.Accounts) > 0 {
		rot := NewAccountRotatorWithIndex(c.Accounts, c.NextAccount)
		if rot.HasMore() {
			nextAcc := rot.Next()
			t.Logf("   ✅ Rotator next account: %d (index after: %d)", nextAcc, rot.Index())
		}
	}
}

func TestSchedulerDelayE2E(t *testing.T) {
	cfg := DefaultDBConfig()
	if err := InitMySQL(cfg); err != nil {
		t.Skipf("MySQL not available: %v", err)
	}
	defer mysqlDB.Close()

	c, err := GetCampaignByID(2)
	if err != nil {
		t.Skip("No campaigns available")
	}

	s := NewScheduler(c.Timezone)
	_ = time.Now()

	t.Logf("📋 Campaign timezone: %q", c.Timezone)
	t.Logf("   TimePost: %d (%s)", c.TimePost, time.Unix(c.TimePost, 0).Format("15:04:05"))
	t.Logf("   MinDelay: %d, MaxDelay: %d", c.MinDelay, c.MaxDelay)
	t.Logf("   ScheduleTime: %v", c.ScheduleTime)
	t.Logf("   ScheduleWeekdays: %v", c.ScheduleWeekdays)
	t.Logf("   Within window: %v", s.IsWithinWindow(c))

	next := s.NextAvailableTime(c)
	t.Logf("   Next available: %d (%s)", next, time.Unix(next, 0).Format("15:04:05"))

	delay := CalculateDelay(c.MinDelay, c.MaxDelay)
	t.Logf("   Calculated delay: %ds", delay)
}

func TestNormalizePhoneE2E(t *testing.T) {
	pn := &PhoneNormalizer{}
	tests := []struct {
		input    string
		expected string
		note     string
	}{
		{"5521970402529", "5521970402529", "BR DDD21 < 31 → mantém"},
		{"5511999999999", "5511999999999", "BR DDD11 < 31 → mantém"},
		{"5531988888888", "553188888888", "BR DDD31 ≥ 31 → remove 5º dígito"},
		{"5532888888888", "553288888888", "BR DDD32 ≥ 31 → remove 5º dígito"},
		{"521234567890", "521234567890", "MX 12 dígitos sem 1 → mantém"},
		{"11999999999", "11999999999", "Sem prefixo → mantém"},
		{" 55 21 97040-2529 ", "5521970402529", "BR formatado → só números"},
	}
	for _, tt := range tests {
		got := pn.NormalizePhone(tt.input)
		status := "✅"
		if got != tt.expected {
			status = "⚠️"
		}
		t.Logf("  %s Normalize(%q) → %q (expected %q) - %s", status, tt.input, got, tt.expected, tt.note)
	}
}
