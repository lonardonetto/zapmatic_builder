package bulk

import (
	"database/sql"
	"math/rand"
	"sort"
	"time"
)

// Scheduler handles campaign time windows, delays, and holidays.
type Scheduler struct {
	location *time.Location
}

// NewScheduler creates a scheduler for the given timezone.
func NewScheduler(tz string) *Scheduler {
	loc := time.Local
	if tz != "" {
		if l, err := time.LoadLocation(tz); err == nil {
			loc = l
		}
	}
	return &Scheduler{location: loc}
}

// IsWithinWindow checks if the current time (in the campaign's timezone)
// falls within the allowed hours and weekdays.
func (s *Scheduler) IsWithinWindow(campaign *Campaign) bool {
	now := time.Now().In(s.location)
	hour := now.Hour()
	weekday := int(now.Weekday())
	if weekday == 0 {
		weekday = 7 // Waziper uses 1=Mon..7=Sun
	}

	// Check weekdays
	if len(campaign.ScheduleWeekdays) > 0 {
		found := false
		for _, d := range campaign.ScheduleWeekdays {
			if d == weekday {
				found = true
				break
			}
		}
		if !found {
			return false
		}
	}

	// Check hours
	if len(campaign.ScheduleTime) > 0 {
		found := false
		for _, h := range campaign.ScheduleTime {
			if h == hour {
				found = true
				break
			}
		}
		if !found {
			return false
		}
	}

	return true
}

// IsHoliday checks if today is a holiday for the team.
func (s *Scheduler) IsHoliday(teamID int) bool {
	if mysqlDB == nil {
		return false
	}
	today := time.Now().In(s.location).Format("2006-01-02")
	var count int
	err := mysqlDB.QueryRow(
		"SELECT COUNT(*) FROM sp_whatsapp_team_holidays WHERE team_id = ? AND holiday_date = ?",
		teamID, today,
	).Scan(&count)
	if err != nil {
		return false
	}
	return count > 0
}

// NextAvailableTime calculates the next send time with delay.
// Returns Unix timestamp for the next send.
func (s *Scheduler) NextAvailableTime(campaign *Campaign) int64 {
	if campaign.TimePost == 0 {
		campaign.TimePost = time.Now().Unix()
	}

	// Add random delay
	minDelay := campaign.MinDelay
	if minDelay <= 0 {
		minDelay = 60
	}
	maxDelay := campaign.MaxDelay
	if maxDelay < minDelay {
		maxDelay = minDelay
	}
	delay := minDelay
	if maxDelay > minDelay {
		delay = minDelay + rand.Intn(maxDelay-minDelay+1)
	}

	nextTime := campaign.TimePost + int64(delay)

	// Se a campanha tiver janela de horário mas estiver fora do horário,
	// avança para o próximo horário permitido
	if !s.IsWithinWindow(campaign) {
		nextTime = s.findNextSlot(campaign, nextTime)
	}

	return nextTime
}

// findNextSlot encontra o próximo horário permitido para envio.
func (s *Scheduler) findNextSlot(campaign *Campaign, from int64) int64 {
	lookAheadDays := 14
	t := time.Unix(from, 0).In(s.location)

	// Avança 1 hora por vez
	for day := 0; day < lookAheadDays; day++ {
		for hour := 0; hour < 24; hour++ {
			check := time.Date(t.Year(), t.Month(), t.Day()+day, hour, 0, 0, 0, s.location)
			wd := int(check.Weekday())
			if wd == 0 {
				wd = 7
			}

			// Check weekday
			if len(campaign.ScheduleWeekdays) > 0 {
				found := false
				for _, d := range campaign.ScheduleWeekdays {
					if d == wd {
						found = true
						break
					}
				}
				if !found {
					continue
				}
			}

			// Check hour
			if len(campaign.ScheduleTime) > 0 {
				found := false
				for _, h := range campaign.ScheduleTime {
					if h == hour {
						found = true
						break
					}
				}
				if !found {
					continue
				}
			}

			return check.Unix()
		}
	}
	return from + 86400 // fallback: +24h
}

// CalculateDelay retorna um delay aleatório entre min e max segundos.
func CalculateDelay(minDelay, maxDelay int) int {
	if minDelay <= 0 {
		minDelay = 60
	}
	if maxDelay < minDelay {
		maxDelay = minDelay
	}
	if maxDelay > minDelay {
		return minDelay + rand.Intn(maxDelay-minDelay+1)
	}
	return minDelay
}

// GetHolidayDates retorna todas as datas de feriado para um time.
func GetHolidayDates(teamID int) ([]string, error) {
	if mysqlDB == nil {
		return nil, sql.ErrConnDone
	}
	rows, err := mysqlDB.Query(
		"SELECT holiday_date FROM sp_whatsapp_team_holidays WHERE team_id = ?",
		teamID,
	)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var dates []string
	for rows.Next() {
		var d string
		if err := rows.Scan(&d); err == nil {
			dates = append(dates, d)
		}
	}
	sort.Strings(dates)
	return dates, nil
}
