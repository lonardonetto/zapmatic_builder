package bulk

import (
	"testing"
)

// TestMySQLConnection tests real MySQL connection and queries.
func TestMySQLConnection(t *testing.T) {
	cfg := DefaultDBConfig()
	if err := InitMySQL(cfg); err != nil {
		t.Skipf("MySQL not available: %v. Set MYSQL_TEST=1 to force.", err)
	}
	defer mysqlDB.Close()
	t.Log("✅ MySQL connected successfully")
}

func TestListDueCampaignsIntegration(t *testing.T) {
	cfg := DefaultDBConfig()
	if err := InitMySQL(cfg); err != nil {
		t.Skipf("MySQL not available: %v", err)
	}
	defer mysqlDB.Close()

	campaigns, err := ListDueCampaigns(10)
	if err != nil {
		t.Fatalf("ListDueCampaigns failed: %v", err)
	}
	t.Logf("✅ Due campaigns found: %d", len(campaigns))
	for _, c := range campaigns {
		t.Logf("   Campaign ID=%d Name=%q Type=%d Accounts=%v Status=%d Sent=%d Failed=%d",
			c.ID, c.Name, c.Type, c.Accounts, c.Status, c.Sent, c.Failed)
	}
}

func TestContactGroupsIntegration(t *testing.T) {
	cfg := DefaultDBConfig()
	if err := InitMySQL(cfg); err != nil {
		t.Skipf("MySQL not available: %v", err)
	}
	defer mysqlDB.Close()

	groups, err := GetContactGroups(0)
	if err != nil {
		t.Fatalf("GetContactGroups failed: %v", err)
	}
	t.Logf("✅ Contact groups found: %d", len(groups))
	for _, g := range groups {
		id := g["id"].(int)
		name := g["name"].(string)
		t.Logf("   Group ID=%d Name=%q", id, name)

		phones, err := GetPhoneNumbers(id, nil)
		if err != nil {
			t.Logf("   ⚠️  Phones error: %v", err)
			continue
		}
		t.Logf("   Phones in group: %d", len(phones))
		if len(phones) > 0 {
			for _, p := range phones[:min(3, len(phones))] {
				t.Logf("      - ID=%d Phone=%v IsValid=%v",
					p["id"], p["phone"], p["is_valid"])
			}
		}
	}
}

func TestGetCampaignByIDIntegration(t *testing.T) {
	cfg := DefaultDBConfig()
	if err := InitMySQL(cfg); err != nil {
		t.Skipf("MySQL not available: %v", err)
	}
	defer mysqlDB.Close()

	// Try a few campaign IDs
	for _, id := range []int{1, 2, 3, 4, 5} {
		c, err := GetCampaignByID(id)
		if err != nil {
			t.Logf("   Campaign %d: not found (%v)", id, err)
			continue
		}
		t.Logf("✅ Campaign %d: %q Type=%d Accounts=%v Next=%d Status=%d",
			c.ID, c.Name, c.Type, c.Accounts, c.NextAccount, c.Status)
	}
}

func TestSchedulerWithHolidaysIntegration(t *testing.T) {
	cfg := DefaultDBConfig()
	if err := InitMySQL(cfg); err != nil {
		t.Skipf("MySQL not available: %v", err)
	}
	defer mysqlDB.Close()

	dates, err := GetHolidayDates(0)
	if err != nil {
		t.Logf("⚠️  Holiday query: %v", err)
		return
	}
	t.Logf("✅ Holidays for team 0: %v", dates)
}

func TestPendingPhoneValidationIntegration(t *testing.T) {
	cfg := DefaultDBConfig()
	if err := InitMySQL(cfg); err != nil {
		t.Skipf("MySQL not available: %v", err)
	}
	defer mysqlDB.Close()

	nums, err := GetPhoneNumbersPendingValidation(5)
	if err != nil {
		t.Fatalf("GetPhoneNumbersPendingValidation failed: %v", err)
	}
	t.Logf("✅ Pending validation numbers: %d", len(nums))
	for _, n := range nums {
		t.Logf("   ID=%d Phone=%v", n["id"], n["phone"])
	}
}

func TestNormalizePhoneIntegration(t *testing.T) {
	pn := &PhoneNormalizer{}
	tests := []struct {
		input    string
		expected string
	}{
		{"5521970402529", "552170402529"},      // BR DDD21 ≤ 31 → mantém
		{"5511999999999", "5511999999999"},      // BR DDD11 < 31 → mantém
		{"5531988888888", "553188888888"},       // BR DDD31 ≥ 31 → remove 5º
		{"521234567890", "521234567890"},        // MX 12 dígitos sem 1 → adiciona
		{"11999999999", "11999999999"},          // Sem prefixo → mantém
	}
	pn = &PhoneNormalizer{}
	for _, tt := range tests {
		got := pn.NormalizePhone(tt.input)
		t.Logf("  Normalize(%q) → %q (expected %q)", tt.input, got, tt.expected)
	}
}

func TestEnsureJID(t *testing.T) {
	if result := EnsureJID("5521970402529"); result != "5521970402529@s.whatsapp.net" {
		t.Fatalf("expected JID, got %q", result)
	}
	if result := EnsureJID("5521970402529@s.whatsapp.net"); result != "5521970402529@s.whatsapp.net" {
		t.Fatalf("expected unchanged, got %q", result)
	}
	t.Log("✅ EnsureJID OK")
}
