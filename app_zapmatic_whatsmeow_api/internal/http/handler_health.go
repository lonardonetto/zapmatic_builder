package http

import (
	"net/http"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/capabilities"
)

func (r *Router) handleHealth(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodGet {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}
	instances := r.rt.Session().ListInstances()
	connected := 0
	for _, inst := range instances {
		if inst.Online { connected++ }
	}
	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status": "ok", "provider": "whatsmeow", "version": "0.1.0",
		"total_instances": len(instances), "connected": connected,
	})
}

func (r *Router) handleCapabilities(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodGet {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}
	r.writeJSON(w, http.StatusOK, capabilities.Get())
}
