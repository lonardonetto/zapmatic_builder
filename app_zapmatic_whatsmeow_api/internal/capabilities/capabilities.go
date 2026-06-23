package capabilities

type Capabilities struct {
	Provider  string                   `json:"provider"`
	Version   string                   `json:"version"`
	Features  map[string]FeatureStatus `json:"features"`
}

type FeatureStatus struct {
	Supported bool   `json:"supported"`
	Notes     string `json:"notes,omitempty"`
}

func Get() Capabilities {
	return Capabilities{
		Provider: "whatsmeow",
		Version:  "0.1.0",
		Features: map[string]FeatureStatus{
			"text":              {Supported: true},
			"image":             {Supported: true},
			"audio":             {Supported: true},
			"video":             {Supported: true},
			"document":          {Supported: true},
			"buttons":           {Supported: false, Notes: "MVP - pending implementation"},
			"list":              {Supported: false, Notes: "MVP - pending implementation"},
			"carousel":          {Supported: false, Notes: "not supported by whatsmeow natively"},
			"poll":              {Supported: false, Notes: "MVP - pending implementation"},
			"presence":          {Supported: true},
			"groups_list":       {Supported: false, Notes: "MVP - pending implementation"},
			"groups_manage":     {Supported: false, Notes: "MVP - pending implementation"},
			"proxy_per_instance": {Supported: false, Notes: "MVP - pending implementation"},
		},
	}
}
