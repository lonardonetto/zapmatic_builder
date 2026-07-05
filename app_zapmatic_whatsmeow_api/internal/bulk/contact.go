package bulk

import (
	"encoding/json"
	"fmt"
	"regexp"
	"strings"
)

type PhoneNormalizer struct{}

func (pn *PhoneNormalizer) NormalizePhone(phone string) string {
	phone = strings.TrimSpace(phone)
	phone = regexp.MustCompile(`[^\d]`).ReplaceAllString(phone, "")
	if strings.HasPrefix(phone, "55") && len(phone) >= 13 {
		ddd := phone[2:4]
		dddInt := 0
		fmt.Sscanf(ddd, "%d", &dddInt)
		if dddInt >= 31 {
			phone = phone[:4] + phone[5:]
		}
	} else if strings.HasPrefix(phone, "52") && len(phone) == 12 && phone[2:3] != "1" {
		phone = phone[:2] + "1" + phone[2:]
	}
	return phone
}

func EnsureJID(chatID string) string {
	if strings.Contains(chatID, "@") {
		return chatID
	}
	return chatID + "@s.whatsapp.net"
}

func ExtractPhoneFromJID(jid string) string {
	jid = strings.TrimSpace(jid)
	at := strings.Index(jid, "@")
	if at > 0 {
		jid = jid[:at]
	}
	return regexp.MustCompile(`[^\d]`).ReplaceAllString(jid, "")
}

type ContactSelector struct {
	processedIDs map[int]bool
}

func NewContactSelector() *ContactSelector {
	return &ContactSelector{processedIDs: make(map[int]bool)}
}

func (cs *ContactSelector) NextPhone(campaign *Campaign) (map[string]interface{}, error) {
	excludeList := make([]int, 0, len(cs.processedIDs))
	for id := range cs.processedIDs {
		excludeList = append(excludeList, id)
	}
	phones, err := GetPhoneNumbers(campaign.ContactID, excludeList)
	if err != nil {
		return nil, fmt.Errorf("get phones: %w", err)
	}
	if len(phones) == 0 {
		return nil, nil
	}
	phone := phones[0]
	id, _ := phone["id"].(int)
	cs.processedIDs[id] = true
	params := make(map[string]string)
	if pStr, ok := phone["params"].(string); ok && pStr != "" {
		json.Unmarshal([]byte(pStr), &params)
	}
	result := map[string]interface{}{
		"id":        id,
		"phone":     phone["phone"],
		"params":    params,
		"is_valid":  phone["is_valid"],
	}
	return result, nil
}

func (cs *ContactSelector) Reset() {
	cs.processedIDs = make(map[int]bool)
}
