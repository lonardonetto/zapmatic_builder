package bulk

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"strconv"
	"strings"
	"time"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

type CampaignType int

const (
	CampaignText     CampaignType = 1
	CampaignButton   CampaignType = 2
	CampaignList     CampaignType = 3
	CampaignPoll     CampaignType = 4
	CampaignCarousel CampaignType = 5
)

type CampaignStatus int

const (
	StatusPaused    CampaignStatus = 0
	StatusRunning   CampaignStatus = 1
	StatusCompleted CampaignStatus = 2
)

type Campaign struct {
	ID               int            `json:"id"`
	IDs              string         `json:"ids"`
	TeamID           int            `json:"team_id"`
	Accounts         []int          `json:"accounts"`
	NextAccount      int            `json:"next_account"`
	ContactID        int            `json:"contact_id"`
	Type             CampaignType   `json:"type"`
	Template         int            `json:"template"`
	TimePost         int64          `json:"time_post"`
	MinDelay         int            `json:"min_delay"`
	MaxDelay         int            `json:"max_delay"`
	ScheduleTime     []int          `json:"schedule_time"`
	ScheduleWeekdays []int          `json:"schedule_weekdays"`
	Timezone         string         `json:"timezone"`
	Name             string         `json:"name"`
	Caption          string         `json:"caption"`
	Media            string         `json:"media"`
	Sent             int            `json:"sent"`
	Failed           int            `json:"failed"`
	Run              int64          `json:"run"`
	Status           CampaignStatus `json:"status"`
	CloudParallel    bool           `json:"cloud_parallel_enabled"`
	CloudParallelLvl int            `json:"cloud_parallel_level"`
	SkipHolidays     bool           `json:"skip_team_holidays"`
}

type CampaignResult struct {
	Status      int    `json:"status"`
	Type        int    `json:"type"`
	PhoneNumber string `json:"phone_number"`
	MessageID   string `json:"message_id,omitempty"`
	Error       string `json:"error,omitempty"`
	Timestamp   int64  `json:"timestamp"`
	AccountID   int    `json:"account_id"`
}

func scanCampaign(s scanner) (*Campaign, error) {
	var (
		c                  Campaign
		accountsJSON       string
		scheduleTimeJSON   sql.NullString
		scheduleWeekdaySQL sql.NullString
		timezoneSQL        sql.NullString
		mediaSQL           sql.NullString
		cloudParal         sql.NullInt64
		cloudParalLvl      sql.NullInt64
		skipHoliday        sql.NullInt64
	)
	err := s.Scan(
		&c.ID, &c.IDs, &c.TeamID, &accountsJSON,
		&c.NextAccount, &c.ContactID, (*int)(&c.Type),
		&c.Template, &c.TimePost, &c.MinDelay, &c.MaxDelay,
		&scheduleTimeJSON, &scheduleWeekdaySQL, &timezoneSQL,
		&c.Name, &c.Caption, &mediaSQL,
		&c.Sent, &c.Failed, &c.Run, (*int)(&c.Status),
		&cloudParal, &cloudParalLvl, &skipHoliday,
	)
	if err != nil {
		return nil, err
	}
	json.Unmarshal([]byte(accountsJSON), &c.Accounts)
	if scheduleTimeJSON.Valid {
		json.Unmarshal([]byte(scheduleTimeJSON.String), &c.ScheduleTime)
	}
	if scheduleWeekdaySQL.Valid {
		json.Unmarshal([]byte(scheduleWeekdaySQL.String), &c.ScheduleWeekdays)
	}
	if timezoneSQL.Valid {
		c.Timezone = timezoneSQL.String
	}
	if mediaSQL.Valid {
		c.Media = mediaSQL.String
	}
	if cloudParal.Valid {
		c.CloudParallel = cloudParal.Int64 == 1
	}
	if cloudParalLvl.Valid {
		c.CloudParallelLvl = int(cloudParalLvl.Int64)
	}
	if skipHoliday.Valid {
		c.SkipHolidays = skipHoliday.Int64 == 1
	}
	return &c, nil
}

type scanner interface {
	Scan(dest ...interface{}) error
}

const campaignCols = `id, ids, team_id, accounts, next_account, contact_id,
 type, template, time_post, min_delay, max_delay,
 schedule_time, schedule_weekdays, timezone,
 name, caption, media,
 sent, failed, run, status,
 cloud_parallel_enabled, cloud_parallel_level, skip_team_holidays`

func ListDueCampaigns(limit int) ([]*Campaign, error) {
	if mysqlDB == nil {
		return nil, fmt.Errorf("MySQL not initialized")
	}
	now := time.Now().Unix()
	rows, err := mysqlDB.Query(
		fmt.Sprintf(`SELECT %s FROM sp_whatsapp_schedules
		 WHERE status = 1 AND run <= ? AND accounts != '' AND time_post <= ?
		 ORDER BY time_post ASC LIMIT ?`, campaignCols),
		now, now, limit,
	)
	if err != nil {
		return nil, fmt.Errorf("query due campaigns: %w", err)
	}
	defer rows.Close()

	var campaigns []*Campaign
	for rows.Next() {
		c, err := scanCampaign(rows)
		if err != nil {
			logging.Log.Error().Err(err).Msg("scan campaign row")
			continue
		}
		campaigns = append(campaigns, c)
	}
	return campaigns, nil
}

func GetCampaignByID(id int) (*Campaign, error) {
	if mysqlDB == nil {
		return nil, fmt.Errorf("MySQL not initialized")
	}
	row := mysqlDB.QueryRow(fmt.Sprintf(`SELECT %s FROM sp_whatsapp_schedules WHERE id = ?`, campaignCols), id)
	return scanCampaign(row)
}

func LockCampaign(id int) error {
	if mysqlDB == nil {
		return fmt.Errorf("MySQL not initialized")
	}
	until := time.Now().Unix() + 300
	result, err := mysqlDB.Exec(
		`UPDATE sp_whatsapp_schedules SET run = ? WHERE id = ? AND run <= ?`,
		until, id, time.Now().Unix(),
	)
	if err != nil {
		return fmt.Errorf("lock campaign: %w", err)
	}
	n, _ := result.RowsAffected()
	if n == 0 {
		return fmt.Errorf("campaign %d already locked", id)
	}
	return nil
}

func UnlockCampaign(id int) error {
	return updateCampaignField(id, "run", "0")
}

func UpdateCampaignResult(id int, success bool, nextTime int64, nextAccount int) error {
	if mysqlDB == nil {
		return fmt.Errorf("MySQL not initialized")
	}
	field := "sent"
	if !success {
		field = "failed"
	}
	_, err := mysqlDB.Exec(
		fmt.Sprintf(
			`UPDATE sp_whatsapp_schedules
			 SET %s = %s + 1, time_post = ?, next_account = ?, run = 0
			 WHERE id = ?`, field, field,
		),
		nextTime, nextAccount, id,
	)
	return err
}

func SetCampaignStatus(id int, status CampaignStatus) error {
	return updateCampaignField(id, "status", strconv.Itoa(int(status)))
}

func SetCampaignCompleted(id int) error {
	return SetCampaignStatus(id, StatusCompleted)
}

func updateCampaignField(id int, field, value string) error {
	if mysqlDB == nil {
		return fmt.Errorf("MySQL not initialized")
	}
	_, err := mysqlDB.Exec(fmt.Sprintf("UPDATE sp_whatsapp_schedules SET %s = ? WHERE id = ?", field), value, id)
	return err
}

// GetNextPhone usa sent+failed como offset para buscar o próximo contato.
// Isso é persistente entre restarts e não precisa de estado em memória.
func GetNextPhone(campaign *Campaign) (map[string]interface{}, error) {
	if mysqlDB == nil {
		return nil, fmt.Errorf("MySQL not initialized")
	}
	offset := campaign.Sent + campaign.Failed

	// Primeiro conta total de telefones
	var total int
	err := mysqlDB.QueryRow(
		"SELECT COUNT(*) FROM sp_whatsapp_phone_numbers WHERE pid = ?",
		campaign.ContactID,
	).Scan(&total)
	if err != nil {
		return nil, fmt.Errorf("count phones: %w", err)
	}
	if offset >= total || total == 0 {
		return nil, nil // no more contacts
	}

	// Busca o telefone no offset
	var id int
	var phone, ids string
	var isValid sql.NullInt64
	var params sql.NullString

	err = mysqlDB.QueryRow(
		`SELECT id, ids, phone, params, is_valid FROM sp_whatsapp_phone_numbers
		 WHERE pid = ? ORDER BY id ASC LIMIT 1 OFFSET ?`,
		campaign.ContactID, offset,
	).Scan(&id, &ids, &phone, &params, &isValid)
	if err != nil {
		return nil, fmt.Errorf("get phone at offset %d: %w", offset, err)
	}

	// Parse params
	parsedParams := make(map[string]string)
	if params.Valid {
		json.Unmarshal([]byte(params.String), &parsedParams)
	}

	result := map[string]interface{}{
		"id":        id,
		"phone":     phone,
		"params":    parsedParams,
		"is_valid":  nil,
	}
	if isValid.Valid {
		result["is_valid"] = int(isValid.Int64)
	}
	return result, nil
}

// GetPhoneNumbers busca telefones com offset para compatibilidade.
func GetPhoneNumbers(contactID int, excludeIDs []int) ([]map[string]interface{}, error) {
	if mysqlDB == nil {
		return nil, fmt.Errorf("MySQL not initialized")
	}
	query := `SELECT id, phone, params, is_valid FROM sp_whatsapp_phone_numbers WHERE pid = ?`
	args := []interface{}{contactID}
	if len(excludeIDs) > 0 {
		placeholders := make([]string, len(excludeIDs))
		for i, id := range excludeIDs {
			placeholders[i] = "?"
			args = append(args, id)
		}
		query += " AND id NOT IN (" + strings.Join(placeholders, ",") + ")"
	}
	query += " ORDER BY id ASC LIMIT 1"

	rows, err := mysqlDB.Query(query, args...)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var result []map[string]interface{}
	for rows.Next() {
		var id int
		var phone string
		var params, isValid sql.NullString
		var isValidInt sql.NullInt64
		if err := rows.Scan(&id, &phone, &params, &isValidInt); err != nil {
			continue
		}
		if isValidInt.Valid {
			isValid = sql.NullString{String: strconv.Itoa(int(isValidInt.Int64)), Valid: true}
		}
		row := map[string]interface{}{
			"id": id, "phone": phone, "params": params,
		}
		if isValid.Valid {
			row["is_valid"] = isValid.String
		}
		result = append(result, row)
	}
	return result, nil
}

func UpdatePhoneValidity(phoneID int, isValid int) error {
	if mysqlDB == nil {
		return fmt.Errorf("MySQL not initialized")
	}
	_, err := mysqlDB.Exec(
		"UPDATE sp_whatsapp_phone_numbers SET is_valid = ? WHERE id = ?",
		isValid, phoneID,
	)
	return err
}

func GetPhoneNumbersPendingValidation(limit int) ([]map[string]interface{}, error) {
	if mysqlDB == nil {
		return nil, fmt.Errorf("MySQL not initialized")
	}
	rows, err := mysqlDB.Query(
		`SELECT id, phone FROM sp_whatsapp_phone_numbers
		 WHERE is_valid IS NULL OR is_valid = 4
		 LIMIT ?`, limit,
	)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var nums []map[string]interface{}
	for rows.Next() {
		var id int
		var phone string
		if err := rows.Scan(&id, &phone); err != nil {
			continue
		}
		nums = append(nums, map[string]interface{}{"id": id, "phone": phone})
	}
	return nums, nil
}

// GetContactPhonesCount retorna o total de telefones em um grupo de contato.
func GetContactPhonesCount(contactID int) (int, error) {
	if mysqlDB == nil {
		return 0, fmt.Errorf("MySQL not initialized")
	}
	var total int
	err := mysqlDB.QueryRow(
		"SELECT COUNT(*) FROM sp_whatsapp_phone_numbers WHERE pid = ?", contactID,
	).Scan(&total)
	return total, err
}

// GetContactGroups returns all contact groups for a team.
func GetContactGroups(teamID int) ([]map[string]interface{}, error) {
	if mysqlDB == nil {
		return nil, fmt.Errorf("MySQL not initialized")
	}
	rows, err := mysqlDB.Query(
		`SELECT id, ids, name FROM sp_whatsapp_contacts WHERE team_id = ? AND status = 1`, teamID,
	)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var groups []map[string]interface{}
	for rows.Next() {
		var id int; var ids, name string
		if err := rows.Scan(&id, &ids, &name); err != nil { continue }
		groups = append(groups, map[string]interface{}{"id": id, "ids": ids, "name": name})
	}
	return groups, nil
}
