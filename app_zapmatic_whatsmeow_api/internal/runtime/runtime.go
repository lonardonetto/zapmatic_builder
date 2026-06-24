package runtime

import (
	"context"
	"encoding/json"
	"sync"
	"time"

	"go.mau.fi/whatsmeow/types/events"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/normalizer"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/receiver"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/session"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/storage"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/webhook"
)

type Runtime struct {
	mu          sync.RWMutex
	sm          *session.Manager
	recv        *receiver.Receiver
	store       *storage.Manager
	webhookURL  string
	startedAt   time.Time
}

type InstanceInfo struct {
	ID        string `json:"id"`
	State     string `json:"state"`
	JID       string `json:"jid,omitempty"`
	Phone     string `json:"phone,omitempty"`
	PushName  string `json:"push_name,omitempty"`
	Online    bool   `json:"online"`
	Uptime    string `json:"uptime,omitempty"`
	AvatarURL string `json:"avatar_url,omitempty"`
}

type Stats struct {
	StartedAt        string `json:"started_at"`
	Uptime           string `json:"uptime"`
	TotalInstances   int    `json:"total_instances"`
	ConnectedInstances int  `json:"connected_instances"`
	DisconnectedInstances int `json:"disconnected_instances"`
}

func New(storeDir, webhookURL string) *Runtime {
	rt := &Runtime{
		sm:         session.NewManager(storeDir, webhookURL),
		recv:       receiver.New(webhookURL),
		store:      storage.New(storeDir),
		webhookURL: webhookURL,
		startedAt:  time.Now(),
	}
	return rt
}

func (rt *Runtime) Init(ctx context.Context) error {
	return rt.sm.Init(ctx)
}

func (rt *Runtime) Shutdown() {
	rt.sm.Shutdown()
}

func (rt *Runtime) Session() *session.Manager {
	return rt.sm
}

func (rt *Runtime) Storage() *storage.Manager {
	return rt.store
}

func (rt *Runtime) Receiver() *receiver.Receiver {
	return rt.recv
}

func (rt *Runtime) Stats() Stats {
	instances := rt.sm.ListInstances()
	var total, connected, disconnected int
	for _, inst := range instances {
		total++
		if inst.Online {
			connected++
		} else {
			disconnected++
		}
	}
	return Stats{
		StartedAt:            rt.startedAt.Format(time.RFC3339),
		Uptime:               time.Since(rt.startedAt).Round(time.Second).String(),
		TotalInstances:       total,
		ConnectedInstances:   connected,
		DisconnectedInstances: disconnected,
	}
}

func (rt *Runtime) ListInstances() []InstanceInfo {
	instances := rt.sm.ListInstances()
	infos := make([]InstanceInfo, 0, len(instances))
	for _, inst := range instances {
		info := InstanceInfo{
			ID:       inst.ID,
			State:    inst.State,
			JID:      inst.JID,
			Phone:    inst.Phone,
			PushName: inst.PushName,
			Online:   inst.Online,
			Uptime:   inst.Uptime,
		}
		infos = append(infos, info)
	}
	return infos
}

func (rt *Runtime) HandleEvent(instanceID string, evt interface{}) {
	switch v := evt.(type) {
	case *events.Message:
		if !v.Info.IsFromMe {
			payload := normalizer.NormalizeMessage(instanceID, v)
			body, err := json.Marshal(payload)
			if err != nil {
				logging.Log.Error().Err(err).Str("instance", instanceID).Msg("Failed to marshal message")
				return
			}
			logging.Log.Info().
				Str("instance", instanceID).
				Str("from", v.Info.Sender.User).
				Str("chat", v.Info.Chat.String()).
				Str("type", v.Info.Type).
				Msg("Incoming message, forwarding to webhook")
			webhook.Send(rt.webhookURL, body)
		}

	case *events.Connected:
		logging.Log.Info().Str("instance", instanceID).Msg("Device connected")

	case *events.Disconnected:
		logging.Log.Warn().Str("instance", instanceID).Msg("Device disconnected")

	case *events.LoggedOut:
		logging.Log.Warn().Str("instance", instanceID).Msg("Device logged out")

	case *events.PairSuccess:
		logging.Log.Info().Str("instance", instanceID).Str("jid", v.ID.String()).Msg("Device paired")

	case *events.HistorySync:
		logging.Log.Debug().Str("instance", instanceID).Msg("History sync received")

	case *events.Presence:
	case *events.CallOffer:
		logging.Log.Info().Str("instance", instanceID).Str("from", v.CallCreator.User).Msg("Incoming call")

	default:
		logging.Log.Debug().Str("instance", instanceID).Msg("Unhandled event type")
	}
}
