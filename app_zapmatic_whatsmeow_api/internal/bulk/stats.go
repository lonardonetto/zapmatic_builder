package bulk

import (
	"database/sql"
	"fmt"
	"math"
	"time"
)

// StatsManager handles monthly sending limits and counters.
type StatsManager struct{}

// TeamStats represents the sp_whatsapp_stats for a team.
type TeamStats struct {
	ID           int
	TeamID       int
	TotalSent    int
	BulkSent     int
	BulkFailed   int
	BulkTotal    int
	TimeReset    int64
	MonthlyLimit int
}

// GetOrCreateStats fetches or creates stats for a team.
func (sm *StatsManager) GetOrCreateStats(teamID int) (*TeamStats, error) {
	if mysqlDB == nil {
		return nil, fmt.Errorf("MySQL not initialized")
	}
	stats := &TeamStats{TeamID: teamID}

	err := mysqlDB.QueryRow(
		`SELECT id, team_id, wa_total_sent, wa_bulk_sent_count, wa_bulk_failed_count, wa_bulk_total_count, wa_time_reset
		 FROM sp_whatsapp_stats WHERE team_id = ?`, teamID,
	).Scan(&stats.ID, &stats.TeamID, &stats.TotalSent, &stats.BulkSent, &stats.BulkFailed, &stats.BulkTotal, &stats.TimeReset)

	if err == sql.ErrNoRows {
		// Create new
		now := time.Now().Unix()
		_, insertErr := mysqlDB.Exec(
			`INSERT INTO sp_whatsapp_stats (team_id, wa_time_reset, wa_total_sent, wa_bulk_sent_count, wa_bulk_failed_count, wa_bulk_total_count, next_update)
			 VALUES (?, ?, 0, 0, 0, 0, ?)`,
			teamID, now, now+86400,
		)
		if insertErr != nil {
			return nil, fmt.Errorf("create stats: %w", insertErr)
		}
		return sm.GetOrCreateStats(teamID)
	}
	if err != nil {
		return nil, fmt.Errorf("get stats: %w", err)
	}

	// Get monthly limit from team permissions
	var monthlyLimit sql.NullInt64
	mysqlDB.QueryRow(
		`SELECT MAX(CAST(p.permission_value AS UNSIGNED))
		 FROM sp_team_permissions p
		 JOIN sp_team_member m ON m.team_id = p.team_id
		 WHERE p.team_id = ? AND p.permission = 'whatsapp_bulk_limit'`, teamID,
	).Scan(&monthlyLimit)
	if monthlyLimit.Valid {
		stats.MonthlyLimit = int(monthlyLimit.Int64)
	}
	if stats.MonthlyLimit <= 0 {
		stats.MonthlyLimit = 10000
	}

	return stats, nil
}

// CheckLimit verifies if the team can send more messages this month.
func (sm *StatsManager) CheckLimit(teamID int) (bool, int, error) {
	stats, err := sm.GetOrCreateStats(teamID)
	if err != nil {
		return false, 0, err
	}
	remaining := stats.MonthlyLimit - stats.BulkTotal
	return remaining > 0, remaining, nil
}

// IncrementSent increments the sent counter for a team.
func (sm *StatsManager) IncrementSent(teamID int) error {
	if mysqlDB == nil {
		return fmt.Errorf("MySQL not initialized")
	}
	_, err := mysqlDB.Exec(
		`UPDATE sp_whatsapp_stats
		 SET wa_total_sent = wa_total_sent + 1,
		     wa_bulk_sent_count = wa_bulk_sent_count + 1,
		     wa_bulk_total_count = wa_bulk_total_count + 1
		 WHERE team_id = ?`, teamID,
	)
	return err
}

// IncrementFailed increments the failed counter for a team.
func (sm *StatsManager) IncrementFailed(teamID int) error {
	if mysqlDB == nil {
		return fmt.Errorf("MySQL not initialized")
	}
	_, err := mysqlDB.Exec(
		`UPDATE sp_whatsapp_stats
		 SET wa_bulk_failed_count = wa_bulk_failed_count + 1,
		     wa_bulk_total_count = wa_bulk_total_count + 1
		 WHERE team_id = ?`, teamID,
	)
	return err
}

// ResetMonthly resets the monthly counters (called when wa_time_reset passes).
func (sm *StatsManager) ResetMonthly(teamID int) error {
	if mysqlDB == nil {
		return fmt.Errorf("MySQL not initialized")
	}
	now := time.Now().Unix()
	_, err := mysqlDB.Exec(
		`UPDATE sp_whatsapp_stats
		 SET wa_total_sent = 0, wa_bulk_sent_count = 0, wa_bulk_failed_count = 0,
		     wa_bulk_total_count = 0, wa_time_reset = ?
		 WHERE team_id = ?`, now+86400*30, teamID,
	)
	return err
}

// GetBulkMaxConcurrent returns the maximum number of concurrent campaigns allowed.
func GetBulkMaxConcurrent() int {
	return 5
}

// GetMonthlyLimit returns the monthly limit for a team.
func GetMonthlyLimit(teamID int) int {
	sm := &StatsManager{}
	stats, err := sm.GetOrCreateStats(teamID)
	if err != nil {
		return 10000
	}
	return int(math.Max(float64(stats.MonthlyLimit), 100))
}

// MaxMessageLength returns the maximum message length for WhatsApp.
func MaxMessageLength() int {
	return 4096
}
