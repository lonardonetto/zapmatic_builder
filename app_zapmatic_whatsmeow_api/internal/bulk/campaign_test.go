package bulk

import (
	"testing"
)

func TestDefaultDBConfig(t *testing.T) {
	cfg := DefaultDBConfig()
	if cfg.Host == "" {
		t.Fatal("expected host to be non-empty")
	}
	if cfg.User == "" {
		t.Fatal("expected user to be non-empty")
	}
	t.Logf("DB config: %s@%s/%s", cfg.User, cfg.Host, cfg.Name)
}

func TestCampaignTypes(t *testing.T) {
	if CampaignText != 1 {
		t.Fatalf("expected CampaignText=1, got %d", CampaignText)
	}
	if CampaignButton != 2 {
		t.Fatalf("expected CampaignButton=2, got %d", CampaignButton)
	}
	if CampaignList != 3 {
		t.Fatalf("expected CampaignList=3, got %d", CampaignList)
	}
	if StatusRunning != 1 {
		t.Fatalf("expected StatusRunning=1, got %d", StatusRunning)
	}
	t.Log("Campaign types and status OK")
}

func TestNowUnix(t *testing.T) {
	ts := NowUnix()
	if ts < 1700000000 {
		t.Fatalf("timestamp looks invalid: %d", ts)
	}
	t.Logf("Current timestamp: %d", ts)
}

func TestListDueCampaignsNoDB(t *testing.T) {
	// Without InitMySQL, it should return an error
	_, err := ListDueCampaigns(5)
	if err == nil {
		t.Fatal("expected error when MySQL not initialized")
	}
	t.Logf("Got expected error: %v", err)
}

func TestUpdateCampaignField(t *testing.T) {
	// Without InitMySQL, it should return an error
	err := updateCampaignField(1, "test", "value")
	if err == nil {
		t.Fatal("expected error when MySQL not initialized")
	}
	t.Logf("Got expected error: %v", err)
}
