package http

import (
	"net/http"
	"strings"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

func (r *Router) handleFiles(w http.ResponseWriter, req *http.Request) {
	path := strings.TrimPrefix(req.URL.Path, "/files/")
	if path == "" || req.URL.Path == "/files/" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "file path required"})
		return
	}
	logging.Log.Debug().Str("path", path).Str("method", req.Method).Msg("File request")
}
