package normalizer

import (
	"time"

	"go.mau.fi/whatsmeow/types/events"
)

type AutomationContext struct {
	CanonicalID  string `json:"canonicalId"`
	CanonicalJid string `json:"canonicalJid"`
	ReplyJid     string `json:"replyJid"`
	Gateway      string `json:"gateway"`
}

type Key struct {
	RemoteJid string `json:"remoteJid"`
	FromMe    bool   `json:"fromMe"`
	ID        string `json:"id"`
}

type NormalizedMessage struct {
	Key              Key                `json:"key"`
	Message          interface{}        `json:"message"`
	MessageTimestamp int64              `json:"messageTimestamp"`
	PushName         string             `json:"pushName,omitempty"`
	WaID             string             `json:"_wa_id,omitempty"`
	AutomationCtx    AutomationContext  `json:"_automation_context,omitempty"`
}

type NormalizedPayload struct {
	InstanceID string            `json:"instance_id"`
	Gateway    string            `json:"gateway"`
	Data       NormalizedMessages `json:"data"`
}

type NormalizedMessages struct {
	Messages []NormalizedMessage `json:"messages"`
}

func NormalizeMessage(instanceID string, msg *events.Message) NormalizedPayload {
	chat := msg.Info.Chat.String()
	sender := msg.Info.Sender.User
	conversationText := msg.Message.GetConversation()

	var messagePayload interface{}
	if conversationText != "" {
			messagePayload = map[string]interface{}{
				"conversation": conversationText,
			}
		} else if extended := msg.Message.GetExtendedTextMessage(); extended != nil {
			messagePayload = map[string]interface{}{
				"extendedTextMessage": map[string]interface{}{
					"text": extended.GetText(),
				},
			}
		} else if btnResp := msg.Message.GetButtonsResponseMessage(); btnResp != nil {
			messagePayload = map[string]interface{}{
				"buttonsResponseMessage": map[string]interface{}{
					"selectedDisplayText": btnResp.GetSelectedDisplayText(),
					"selectedButtonId":    btnResp.GetSelectedButtonID(),
				},
			}
		} else if listResp := msg.Message.GetListResponseMessage(); listResp != nil {
			title := listResp.GetTitle()
			rowID := ""
			if sr := listResp.GetSingleSelectReply(); sr != nil {
				rowID = sr.GetSelectedRowID()
			}
			messagePayload = map[string]interface{}{
				"listResponseMessage": map[string]interface{}{
					"title": title,
					"singleSelectReply": map[string]interface{}{
						"selectedRowId": rowID,
					},
				},
			}
		} else if btnReply := msg.Message.GetTemplateButtonReplyMessage(); btnReply != nil {
			messagePayload = map[string]interface{}{
				"templateButtonReplyMessage": map[string]interface{}{
					"selectedDisplayText": btnReply.GetSelectedDisplayText(),
					"selectedIndex":       btnReply.GetSelectedIndex(),
					"selectedId":          btnReply.GetSelectedID(),
				},
			}
		} else {
			messagePayload = map[string]interface{}{
				"conversation": "",
			}
		}

	return NormalizedPayload{
		InstanceID: instanceID,
		Gateway:    "whatsmeow",
		Data: NormalizedMessages{
			Messages: []NormalizedMessage{
				{
					Key: Key{
						RemoteJid: chat,
						FromMe:    msg.Info.IsFromMe,
						ID:        msg.Info.ID,
					},
					Message:          messagePayload,
					MessageTimestamp: msg.Info.Timestamp.Unix(),
					PushName:         msg.Info.PushName,
					WaID:             sender,
					AutomationCtx: AutomationContext{
						CanonicalID:  sender,
						CanonicalJid: chat,
						ReplyJid:     chat,
						Gateway:      "whatsmeow",
					},
				},
			},
		},
	}
}

func BuildEventPayload(instanceID string, eventType string, data interface{}) map[string]interface{} {
	return map[string]interface{}{
		"instance_id": instanceID,
		"gateway":     "whatsmeow",
		"event":       eventType,
		"timestamp":   time.Now().Unix(),
		"data":        data,
	}
}
