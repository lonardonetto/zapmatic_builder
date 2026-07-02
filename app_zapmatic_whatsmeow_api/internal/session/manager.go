package session

import (
	"context"
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"sync"
	"time"

	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/store"
	"go.mau.fi/whatsmeow/store/sqlstore"
	"go.mau.fi/whatsmeow/types"
	"go.mau.fi/whatsmeow/types/events"
	waLog "go.mau.fi/whatsmeow/util/log"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/receiver"
)

type InstanceState int

const (
	StateDisconnected InstanceState = iota
	StateConnecting
	StateQRReady
	StatePasskeyReady
	StatePasskeyCodeReady
	StateConnected
)

type Instance struct {
	ID               string        `json:"id"`
	State            InstanceState `json:"state"`
	JID              string        `json:"jid,omitempty"`
	Phone            string        `json:"phone,omitempty"`
	LastQR           string        `json:"last_qr,omitempty"`
	PushName         string        `json:"push_name,omitempty"`
	LastPasskeyCode  string        `json:"last_passkey_code,omitempty"`
	SkipHandoffUX    bool          `json:"skip_handoff_ux,omitempty"`
	PasskeyChallenge []byte        `json:"-"`
	client           *whatsmeow.Client
	cancel           context.CancelFunc
	connectedAt      time.Time
}

func (inst *Instance) Client() *whatsmeow.Client {
	return inst.client
}

func (inst *Instance) DisplayName() string {
	if inst.PushName != "" {
		return inst.PushName
	}
	if inst.client != nil && inst.client.Store != nil {
		return inst.client.Store.PushName
	}
	return ""
}

type instanceMapping struct {
	InstanceID string `json:"instance_id"`
	JID        string `json:"jid"`
}

type Manager struct {
	mu          sync.RWMutex
	instances   map[string]*Instance
	storeDir    string
	container   *sqlstore.Container
	webhookURL  string
	mappingFile string
	recv        *receiver.Receiver
}

type StatusInfo struct {
	ID       string `json:"id"`
	State    string `json:"state"`
	JID      string `json:"jid,omitempty"`
	Phone    string `json:"phone,omitempty"`
	PushName string `json:"push_name,omitempty"`
	Online   bool   `json:"online"`
	Uptime   string `json:"uptime,omitempty"`
}

// ConnectionResult é o resultado unificado do processo de pareamento.
// O WhatsApp pode enviar QR Code ou desafio Passkey — ambos retornam pelo mesmo endpoint.
type ConnectionResult struct {
	Method    string `json:"method"`               // "qr" | "passkey"
	QRCode    string `json:"qrcode,omitempty"`      // URL/string do QR (method=qr)
	Challenge string `json:"challenge,omitempty"`   // desafio WebAuthn (method=passkey)
	RpID      string `json:"rp_id,omitempty"`       // relying party ID
	Timeout   int    `json:"timeout,omitempty"`     // timeout do challenge
	State     string `json:"state,omitempty"`       // "connected" se já conectou
}

func NewManager(storeDir, webhookURL string) *Manager {
	return &Manager{
		instances:   make(map[string]*Instance),
		storeDir:    storeDir,
		webhookURL:  webhookURL,
		mappingFile: filepath.Join(storeDir, "instance_map.json"),
		recv:        receiver.New(webhookURL),
	}
}

func (m *Manager) Init(ctx context.Context) error {
	os.MkdirAll(m.storeDir, 0755)
	dbPath := filepath.Join(m.storeDir, "whatsmeow.db")
	databaseURL := fmt.Sprintf("file:%s?_foreign_keys=on&_journal_mode=WAL&_busy_timeout=5000", dbPath)

	container, err := sqlstore.New(ctx, "sqlite3", databaseURL, waLog.Zerolog(logging.Log))
	if err != nil {
		return fmt.Errorf("failed to initialize sqlstore: %w", err)
	}
	m.container = container

	mappings := m.loadMappings()
	devices, _ := container.GetAllDevices(ctx)
	deviceJIDs := make(map[string]bool)
	for _, d := range devices { deviceJIDs[d.ID.String()] = true }

	for instanceID, jidStr := range mappings {
		if _, exists := m.instances[instanceID]; exists { continue }
		if !deviceJIDs[jidStr] {
			logging.Log.Warn().Str("instance", instanceID).Str("jid", jidStr).Msg("Device not in DB, removing mapping")
			m.deleteMapping(instanceID)
			continue
		}
		logging.Log.Info().Str("instance", instanceID).Str("jid", jidStr).Msg("Auto-reconnecting")
		m.StartInstance(ctx, instanceID)
	}

	logging.Log.Info().Str("db_path", dbPath).Int("devices", len(devices)).Int("restored", len(mappings)).Msg("Session store initialized")
	return nil
}

func (m *Manager) loadMappings() map[string]string {
	data, err := os.ReadFile(m.mappingFile)
	if err != nil {
		return make(map[string]string)
	}
	var mappings []instanceMapping
	if err := json.Unmarshal(data, &mappings); err != nil {
		return make(map[string]string)
	}
	result := make(map[string]string, len(mappings))
	for _, m := range mappings {
		result[m.InstanceID] = m.JID
	}
	return result
}

func (m *Manager) saveMapping(instanceID, jid string) {
	mappings := m.loadMappings()
	mappings[instanceID] = jid
	m.writeMappings(mappings)
}

func (m *Manager) deleteMapping(instanceID string) {
	mappings := m.loadMappings()
	delete(mappings, instanceID)
	m.writeMappings(mappings)
}

func (m *Manager) writeMappings(mappings map[string]string) {
	var list []instanceMapping
	for id, jid := range mappings {
		list = append(list, instanceMapping{InstanceID: id, JID: jid})
	}
	data, _ := json.MarshalIndent(list, "", "  ")
	os.WriteFile(m.mappingFile, data, 0644)
}

func (m *Manager) GetInstance(instanceID string) *Instance {
	m.mu.RLock()
	defer m.mu.RUnlock()
	return m.instances[instanceID]
}

func (m *Manager) ListInstances() []StatusInfo {
	m.mu.RLock()
	defer m.mu.RUnlock()

	var list []StatusInfo
	for id, inst := range m.instances {
		info := StatusInfo{
			ID:     id,
			State:  stateToString(inst.State),
			JID:    inst.JID,
			Phone:  inst.Phone,
			PushName: inst.DisplayName(),
			Online: inst.State == StateConnected,
		}
		if !inst.connectedAt.IsZero() {
			info.Uptime = time.Since(inst.connectedAt).Round(time.Second).String()
		}
		list = append(list, info)
	}
	return list
}

func (m *Manager) GetStatus(instanceID string) *StatusInfo {
	inst := m.GetInstance(instanceID)
	if inst == nil {
		return nil
	}
	info := &StatusInfo{
		ID:       inst.ID,
		State:    stateToString(inst.State),
		JID:      inst.JID,
		Phone:    inst.Phone,
		PushName: inst.DisplayName(),
		Online:   inst.State == StateConnected,
	}
	if !inst.connectedAt.IsZero() {
		info.Uptime = time.Since(inst.connectedAt).Round(time.Second).String()
	}
	return info
}

// StartInstance unificado: conecta o websocket. O WhatsApp decide se envia QR ou passkey.
func (m *Manager) StartInstance(ctx context.Context, instanceID string) error {
	m.mu.Lock()

	if existing, ok := m.instances[instanceID]; ok {
		m.mu.Unlock()
		switch existing.State {
		case StateConnected:
			return fmt.Errorf("instance already connected")
		case StateConnecting, StateQRReady, StatePasskeyReady, StatePasskeyCodeReady:
			// Já está em um fluxo de pareamento, pode reutilizar
			return nil
		default:
		}
	}

	var deviceStore *store.Device
	mappings := m.loadMappings()
	if jidStr, ok := mappings[instanceID]; ok {
		jid, err := types.ParseJID(jidStr)
		if err == nil {
			deviceStore, err = m.container.GetDevice(ctx, jid)
			if err != nil {
				logging.Log.Warn().Err(err).Str("instance", instanceID).Str("jid", jidStr).Msg("Failed to get device from store")
			}
		}
	}

	isNew := deviceStore == nil
	if isNew {
		deviceStore = m.container.NewDevice()
	}

	client := whatsmeow.NewClient(deviceStore, waLog.Zerolog(logging.Log))

	clientCtx, clientCancel := context.WithCancel(ctx)
	inst := &Instance{
		ID:     instanceID,
		State:  StateConnecting,
		client: client,
		cancel: clientCancel,
	}
	m.instances[instanceID] = inst
	m.mu.Unlock()

	client.AddEventHandler(func(evt interface{}) {
		m.handleEvent(instanceID, evt)
	})

	go func() {
		defer func() {
			if r := recover(); r != nil {
				logging.Log.Error().Str("instance", instanceID).Interface("panic", r).Msg("Session goroutine panicked")
			}
		}()

		if isNew {
			qrItemChan, err := client.GetQRChannel(clientCtx)
			if err != nil {
				logging.Log.Error().Err(err).Str("instance", instanceID).Msg("Failed to get QR channel")
				m.mu.Lock()
				inst.State = StateDisconnected
				m.mu.Unlock()
				return
			}

			go func() {
				for item := range qrItemChan {
					switch item.Event {
					case "code":
						m.mu.Lock()
						inst.LastQR = item.Code
						inst.State = StateQRReady
						m.mu.Unlock()
						logging.Log.Debug().Str("instance", instanceID).Msg("QR code received")
					case "success":
						m.mu.Lock()
						inst.State = StateConnected
						if client.Store != nil && client.Store.ID != nil {
							inst.JID = client.Store.ID.String()
							m.saveMapping(instanceID, inst.JID)
						}
						m.mu.Unlock()
					case "error":
						logging.Log.Error().Str("instance", instanceID).Err(item.Error).Msg("QR pairing error")
						m.mu.Lock()
						inst.State = StateDisconnected
						m.mu.Unlock()
					default:
						if item.Event == "timeout" || item.Event == "err-unexpected-state" || item.Event == "err-client-outdated" || item.Event == "err-scanned-without-multidevice" {
							m.mu.Lock()
							inst.State = StateDisconnected
							m.mu.Unlock()
						}
					}
				}
			}()

			if err := client.Connect(); err != nil {
				logging.Log.Error().Err(err).Str("instance", instanceID).Msg("Failed to connect")
				m.mu.Lock()
				inst.State = StateDisconnected
				m.mu.Unlock()
			}
		} else {
			if err := client.Connect(); err != nil {
				logging.Log.Error().Err(err).Str("instance", instanceID).Msg("Failed to reconnect")
				m.mu.Lock()
				inst.State = StateDisconnected
				m.mu.Unlock()
				return
			}

			m.mu.Lock()
			if client.IsConnected() {
				inst.State = StateConnected
				inst.connectedAt = time.Now()
				if client.Store != nil && client.Store.ID != nil {
					inst.JID = client.Store.ID.String()
				}
			}
			m.mu.Unlock()

			logging.Log.Info().Str("instance", instanceID).Str("jid", inst.JID).Msg("Already authenticated, reconnected")
		}
	}()

	return nil
}

// WaitConnection aguarda o resultado do pareamento (QR ou passkey) e retorna o que vier primeiro.
// O WhatsApp decide automaticamente qual método usar.
func (m *Manager) WaitConnection(instanceID string, timeout time.Duration) (*ConnectionResult, error) {
	deadline := time.After(timeout)
	for {
		m.mu.RLock()
		inst, ok := m.instances[instanceID]
		if !ok {
			m.mu.RUnlock()
			return nil, fmt.Errorf("instance not found")
		}
		state := inst.State
		qr := inst.LastQR
		challenge := inst.PasskeyChallenge
		m.mu.RUnlock()

		switch state {
		case StateQRReady:
			if qr != "" {
				return &ConnectionResult{
					Method: "qr",
					QRCode: qr,
					State:  "qr_ready",
				}, nil
			}
		case StatePasskeyReady:
			if challenge != nil && len(challenge) > 0 {
				var pubKey types.WebAuthnPublicKey
				if err := json.Unmarshal(challenge, &pubKey); err == nil {
					return &ConnectionResult{
						Method:    "passkey",
						Challenge: string(pubKey.Challenge),
						RpID:      pubKey.RelyingPartID,
						Timeout:   pubKey.Timeout,
						State:     "passkey_ready",
					}, nil
				}
			}
		case StatePasskeyCodeReady:
			m.mu.RLock()
			code := inst.LastPasskeyCode
			skipUX := inst.SkipHandoffUX
			m.mu.RUnlock()
			if code != "" {
				return &ConnectionResult{
					Method: "passkey",
					State:  "passkey_code_ready",
					Challenge: code, // reusa campo para transportar o código
					RpID:  fmt.Sprintf("%t", skipUX),
				}, nil
			}
		case StateConnected:
			return &ConnectionResult{
				State: "connected",
			}, nil
		case StateDisconnected:
			return nil, fmt.Errorf("connection failed")
		}

		select {
		case <-time.After(500 * time.Millisecond):
		case <-deadline:
			return nil, fmt.Errorf("timeout waiting for connection method (QR or passkey)")
		}
	}
}

// SendPasskeyResponse envia a resposta WebAuthn do navegador para o WhatsApp.
func (m *Manager) SendPasskeyResponse(instanceID string, responseJSON []byte) error {
	m.mu.RLock()
	inst, ok := m.instances[instanceID]
	m.mu.RUnlock()
	if !ok {
		return fmt.Errorf("instance not found")
	}
	if inst.client == nil {
		return fmt.Errorf("client not available")
	}

	var webauthnResp types.WebAuthnResponse
	if err := json.Unmarshal(responseJSON, &webauthnResp); err != nil {
		return fmt.Errorf("failed to parse WebAuthn response: %w", err)
	}

	return inst.client.SendPasskeyResponse(context.Background(), &webauthnResp)
}

// SendPasskeyConfirm envia a confirmação final após o usuário verificar o código.
func (m *Manager) SendPasskeyConfirm(instanceID string) error {
	m.mu.RLock()
	inst, ok := m.instances[instanceID]
	m.mu.RUnlock()
	if !ok {
		return fmt.Errorf("instance not found")
	}
	if inst.client == nil {
		return fmt.Errorf("client not available")
	}
	return inst.client.SendPasskeyConfirmation(context.Background())
}

// WaitPasskeyCode aguarda o código de confirmação do passkey.
func (m *Manager) WaitPasskeyCode(instanceID string, timeout time.Duration) (string, bool, error) {
	deadline := time.After(timeout)
	for {
		m.mu.RLock()
		inst, ok := m.instances[instanceID]
		if !ok {
			m.mu.RUnlock()
			return "", false, fmt.Errorf("instance not found")
		}
		state := inst.State
		code := inst.LastPasskeyCode
		skipUX := inst.SkipHandoffUX
		m.mu.RUnlock()

		switch state {
		case StatePasskeyCodeReady:
			if code != "" {
				return code, skipUX, nil
			}
		case StateConnected:
			return "", true, nil
		case StateDisconnected:
			return "", false, fmt.Errorf("connection failed")
		}

		select {
		case <-time.After(500 * time.Millisecond):
		case <-deadline:
			return "", false, fmt.Errorf("timeout waiting for passkey code")
		}
	}
}

func (m *Manager) Disconnect(instanceID string) error {
	m.mu.Lock()
	inst, ok := m.instances[instanceID]
	if !ok {
		m.mu.Unlock()
		return fmt.Errorf("instance not found")
	}
	if inst.cancel != nil {
		inst.cancel()
	}
	if inst.client != nil {
		inst.client.Disconnect()
	}
	inst.State = StateDisconnected
	delete(m.instances, instanceID)
	m.mu.Unlock()

	logging.Log.Info().Str("instance", instanceID).Msg("Instance disconnected")
	return nil
}

func (m *Manager) Logout(instanceID string) error {
	m.mu.Lock()
	inst, ok := m.instances[instanceID]
	if !ok {
		m.mu.Unlock()
		return fmt.Errorf("instance not found")
	}
	client := inst.client
	if inst.cancel != nil {
		inst.cancel()
	}
	m.mu.Unlock()

	if client != nil {
		_ = client.Logout(context.Background())
		client.Disconnect()
	}

	m.mu.Lock()
	delete(m.instances, instanceID)
	m.mu.Unlock()
	m.deleteMapping(instanceID)

	logging.Log.Info().Str("instance", instanceID).Msg("Instance logged out")
	return nil
}

func (m *Manager) handleEvent(instanceID string, evt interface{}) {
	m.mu.Lock()
	inst, ok := m.instances[instanceID]
	m.mu.Unlock()
	if !ok {
		return
	}

	switch v := evt.(type) {
	case *events.Connected:
		m.mu.Lock()
		inst.State = StateConnected
		inst.connectedAt = time.Now()
		if inst.client != nil && inst.client.Store != nil && inst.client.Store.ID != nil {
			inst.JID = inst.client.Store.ID.String()
		}
		m.mu.Unlock()
		logging.Log.Info().Str("instance", instanceID).Str("jid", inst.JID).Msg("Connected")

		go func(instID string, client *whatsmeow.Client) {
			for i := 0; i < 120; i++ {
				time.Sleep(1000 * time.Millisecond)
				m.mu.Lock()
				instPtr, ok := m.instances[instID]
				if !ok || instPtr.State != StateConnected {
					m.mu.Unlock()
					return
				}
				pn := ""
				if client != nil && client.Store != nil {
					pn = client.Store.PushName
				}
				if pn != "" && pn != instPtr.PushName {
					instPtr.PushName = pn
					m.mu.Unlock()
					logging.Log.Info().Str("instance", instID).Str("push_name", pn).Int("waited_s", i+1).Msg("Push name captured in background")
					return
				}
				m.mu.Unlock()
			}
			logging.Log.Warn().Str("instance", instID).Msg("Push name not available after 120s background poll")
		}(instanceID, inst.client)

	case *events.Disconnected:
		m.mu.Lock()
		inst.State = StateDisconnected
		m.mu.Unlock()
		logging.Log.Warn().Str("instance", instanceID).Msg("Disconnected")

	case *events.LoggedOut:
		m.mu.Lock()
		inst.State = StateDisconnected
		m.mu.Unlock()
		logging.Log.Warn().Str("instance", instanceID).Msg("Logged out")
		m.deleteMapping(instanceID)

	case *events.PairSuccess:
		m.mu.Lock()
		inst.JID = v.ID.String()
		if inst.client != nil && inst.client.Store != nil {
			inst.Phone = inst.client.Store.ID.User
		}
		m.mu.Unlock()
		logging.Log.Info().Str("instance", instanceID).Str("jid", inst.JID).Msg("Pair success")
		m.saveMapping(instanceID, inst.JID)

	// Passkey: o WhatsApp envia um desafio WebAuthn no lugar do QR
	case *events.PairPasskeyRequest:
		m.mu.Lock()
		inst.State = StatePasskeyReady
		if v.PublicKey != nil {
			challengeJSON, err := json.Marshal(v.PublicKey)
			if err == nil {
				inst.PasskeyChallenge = challengeJSON
			}
		}
		m.mu.Unlock()
		logging.Log.Info().Str("instance", instanceID).Msg("Passkey challenge received (instead of QR)")

	// Passkey: código de confirmação gerado
	case *events.PairPasskeyConfirmation:
		m.mu.Lock()
		inst.State = StatePasskeyCodeReady
		inst.LastPasskeyCode = v.Code
		inst.SkipHandoffUX = v.SkipHandoffUX
		m.mu.Unlock()
		logging.Log.Info().Str("instance", instanceID).Str("code", v.Code).Bool("skip_ux", v.SkipHandoffUX).Msg("Passkey confirmation code received")

	// Passkey: erro
	case *events.PairPasskeyError:
		m.mu.Lock()
		inst.State = StateDisconnected
		m.mu.Unlock()
		logging.Log.Error().Err(v.Error).Bool("continuation", v.Continuation).Str("instance", instanceID).Msg("Passkey error")
	}

	m.recv.HandleEvent(instanceID, evt)
}

func (m *Manager) Shutdown() {
	m.mu.Lock()
	defer m.mu.Unlock()

	for id, inst := range m.instances {
		logging.Log.Info().Str("instance", id).Msg("Shutting down instance")
		if inst.cancel != nil {
			inst.cancel()
		}
		if inst.client != nil {
			inst.client.Disconnect()
		}
	}
	m.instances = make(map[string]*Instance)
}

func stateToString(s InstanceState) string {
	switch s {
	case StateDisconnected:
		return "disconnected"
	case StateConnecting:
		return "connecting"
	case StateQRReady:
		return "qr_ready"
	case StatePasskeyReady:
		return "passkey_ready"
	case StatePasskeyCodeReady:
		return "passkey_code_ready"
	case StateConnected:
		return "connected"
	default:
		return "unknown"
	}
}
