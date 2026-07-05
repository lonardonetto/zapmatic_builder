package bulk

import (
	"context"
	"strings"
	"time"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/types"
)

type PhoneValidator struct{}

type ValidationResult int

const (
	Valid     ValidationResult = 1
	Invalid   ValidationResult = 2
	Checking  ValidationResult = 3
	Retry     ValidationResult = 4
	NoAccount ValidationResult = 0
)

// CheckPhone verifica se o número existe no WhatsApp via IsOnWhatsApp.
func (pv *PhoneValidator) CheckPhone(client *whatsmeow.Client, phone string) bool {
	if client == nil || !client.IsConnected() {
		return false
	}
	cleanPhone := strings.TrimSuffix(phone, "@s.whatsapp.net")
	cleanPhone = strings.TrimSuffix(cleanPhone, "@lid")

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	exists, err := client.IsOnWhatsApp(ctx, []string{cleanPhone})
	if err != nil {
		logging.Log.Warn().Err(err).Str("phone", cleanPhone).Msg("IsOnWhatsApp failed")
		return false
	}
	if len(exists) > 0 {
		return exists[0].IsIn
	}
	return false
}

// CheckPhoneBatch verifica múltiplos números de uma vez.
func (pv *PhoneValidator) CheckPhoneBatch(client *whatsmeow.Client, phones []string) []types.IsOnWhatsAppResponse {
	if client == nil || !client.IsConnected() || len(phones) == 0 {
		return nil
	}
	cleanPhones := make([]string, len(phones))
	for i, p := range phones {
		p = strings.TrimSuffix(p, "@s.whatsapp.net")
		p = strings.TrimSuffix(p, "@lid")
		cleanPhones[i] = p
	}

	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	results, err := client.IsOnWhatsApp(ctx, cleanPhones)
	if err != nil {
		logging.Log.Warn().Err(err).Int("count", len(phones)).Msg("Batch IsOnWhatsApp failed")
		return nil
	}
	return results
}

// ValidatePendingNumbers processa números pendentes de validação.
func ValidatePendingNumbers(getClientFn func() *whatsmeow.Client, limit int) {
	nums, err := GetPhoneNumbersPendingValidation(limit)
	if err != nil || len(nums) == 0 {
		return
	}

	pv := &PhoneValidator{}
	client := getClientFn()
	if client == nil || !client.IsConnected() {
		for _, n := range nums {
			id, _ := n["id"].(int)
			UpdatePhoneValidity(id, int(NoAccount))
		}
		return
	}

	for _, n := range nums {
		id, _ := n["id"].(int)
		phone, _ := n["phone"].(string)

		UpdatePhoneValidity(id, int(Checking))

		if pv.CheckPhone(client, phone) {
			UpdatePhoneValidity(id, int(Valid))
			logging.Log.Debug().Int("id", id).Str("phone", phone).Msg("Phone validated as valid")
		} else {
			UpdatePhoneValidity(id, int(Invalid))
			logging.Log.Debug().Int("id", id).Str("phone", phone).Msg("Phone validated as invalid")
		}
	}
}

// IsValidPhone retorna true se o número for válido (is_valid = 1).
func IsValidPhone(isValidRaw interface{}) bool {
	switch v := isValidRaw.(type) {
	case string:
		return v == "1"
	case int:
		return v == int(Valid)
	case int64:
		return v == int64(Valid)
	}
	return false
}

// ShouldSkipPhone retorna true se o número deve ser pulado (is_valid = 2 ou 0).
func ShouldSkipPhone(isValidRaw interface{}) bool {
	switch v := isValidRaw.(type) {
	case string:
		return v == "2" || v == "0"
	case int:
		return v == int(Invalid) || v == int(NoAccount)
	case int64:
		return v == int64(Invalid) || v == int64(NoAccount)
	}
	return false
}
