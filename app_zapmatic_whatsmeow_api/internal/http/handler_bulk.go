package http

import (
	"net/http"
	"strconv"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/bulk"
)

var bulkProcessor *bulk.Processor

func SetBulkProcessor(p *bulk.Processor) {
	bulkProcessor = p
}

func (r *Router) handleBulkListCampaigns(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodGet {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"error": "method not allowed"})
		return
	}
	limit := 10
	if l, err := strconv.Atoi(req.URL.Query().Get("limit")); err == nil && l > 0 {
		limit = l
	}
	campaigns, err := bulk.ListDueCampaigns(limit)
	if err != nil {
		r.writeJSON(w, http.StatusInternalServerError, map[string]string{"error": err.Error()})
		return
	}
	r.writeJSON(w, http.StatusOK, map[string]interface{}{"status": "success", "campaigns": campaigns})
}

func (r *Router) handleBulkCampaignAction(w http.ResponseWriter, req *http.Request) {
	idStr := req.URL.Query().Get("id")
	if idStr == "" {
		// Extract from path /bulk/campaign/123
		path := req.URL.Path
		prefix := "/bulk/campaign/"
		if len(path) > len(prefix) {
			idStr = path[len(prefix):]
		}
	}
	id, err := strconv.Atoi(idStr)
	if err != nil || id == 0 {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"error": "invalid campaign id"})
		return
	}

	switch req.Method {
	case http.MethodGet:
		c, err := bulk.GetCampaignByID(id)
		if err != nil {
			r.writeJSON(w, http.StatusNotFound, map[string]string{"error": "not found"})
			return
		}
		r.writeJSON(w, http.StatusOK, map[string]interface{}{"status": "success", "campaign": c})

	case http.MethodPost:
		action := req.URL.Query().Get("action")
		switch action {
		case "pause":
			bulk.SetCampaignStatus(id, bulk.StatusPaused)
			r.writeJSON(w, http.StatusOK, map[string]string{"status": "success", "message": "paused"})
		case "resume":
			bulk.SetCampaignStatus(id, bulk.StatusRunning)
			r.writeJSON(w, http.StatusOK, map[string]string{"status": "success", "message": "resumed"})
		case "restart":
			bulk.SetCampaignStatus(id, bulk.StatusRunning)
			r.writeJSON(w, http.StatusOK, map[string]string{"status": "success", "message": "restarted"})
		default:
			r.writeJSON(w, http.StatusBadRequest, map[string]string{"error": "unknown action"})
		}

	case http.MethodDelete:
		bulk.SetCampaignStatus(id, bulk.StatusPaused)
		r.writeJSON(w, http.StatusOK, map[string]string{"status": "success", "message": "deleted"})

	default:
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"error": "method not allowed"})
	}
}

func (r *Router) handleBulkStart(w http.ResponseWriter, req *http.Request) {
	r.writeJSON(w, http.StatusOK, map[string]interface{}{"status": "success", "running": true})
}

func (r *Router) handleBulkStop(w http.ResponseWriter, req *http.Request) {
	if bulkProcessor != nil {
		bulkProcessor.Stop()
	}
	r.writeJSON(w, http.StatusOK, map[string]interface{}{"status": "success", "running": false})
}

func (r *Router) handleBulkStatus(w http.ResponseWriter, req *http.Request) {
	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status": "success", "processor_running": true,
	})
}

func (r *Router) handleBulkValidate(w http.ResponseWriter, req *http.Request) {
	if bulkProcessor != nil {
		go bulkProcessor.ValidateNow()
	}
	r.writeJSON(w, http.StatusOK, map[string]string{"status": "success", "message": "validation triggered"})
}
