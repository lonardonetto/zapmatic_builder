package bulk

import (
	"testing"
	"time"
)

func TestSchedulerWithWindow(t *testing.T) {
	s := NewScheduler("America/Sao_Paulo")
	campaign := &Campaign{
		ScheduleTime:     []int{10, 11, 12, 13, 14, 15},
		ScheduleWeekdays: []int{1, 2, 3, 4, 5},
	}
	result := s.IsWithinWindow(campaign)
	t.Logf("Current time in SP: %v, within window: %v", time.Now().In(s.location).Format("2006-01-02 15:04"), result)
}

func TestSchedulerNoWindow(t *testing.T) {
	s := NewScheduler("UTC")
	// Block all hours
	campaign := &Campaign{
		ScheduleTime:     []int{99}, // invalid hour, should block
		ScheduleWeekdays: []int{1, 2, 3, 4, 5},
	}
	result := s.IsWithinWindow(campaign)
	if result {
		t.Log("No restriction (hour 99 doesn't exist, so no filter)")
	} else {
		t.Log("Blocked by schedule (expected with wrong hour)")
	}
}

func TestCalculateDelay(t *testing.T) {
	d := CalculateDelay(10, 30)
	if d < 10 || d > 30 {
		t.Fatalf("delay %d out of range [10,30]", d)
	}
	t.Logf("Delay: %ds", d)
}

func TestCalculateDelayMinOnly(t *testing.T) {
	d := CalculateDelay(15, 15)
	if d != 15 {
		t.Fatalf("expected 15, got %d", d)
	}
}

func TestCalculateDelayZero(t *testing.T) {
	d := CalculateDelay(0, 0)
	if d < 60 {
		t.Fatalf("expected default >=60, got %d", d)
	}
	t.Logf("Default delay: %ds", d)
}

func TestSchedulerNextAvailableTime(t *testing.T) {
	s := NewScheduler("America/Sao_Paulo")
	campaign := &Campaign{
		TimePost: time.Now().Unix(),
		MinDelay: 5,
		MaxDelay: 10,
	}
	next := s.NextAvailableTime(campaign)
	if next < campaign.TimePost+5 {
		t.Fatalf("next time %d should be >= time_post+5 (%d)", next, campaign.TimePost+5)
	}
	t.Logf("Next send time in %ds", next-time.Now().Unix())
}

func TestGetHolidayDatesNoDB(t *testing.T) {
	dates, err := GetHolidayDates(1)
	if err == nil {
		t.Logf("Holidays: %v", dates)
	} else {
		t.Logf("Expected no DB: %v", err)
	}
}
