package config

import (
	"flag"
	"os"
)

type Config struct {
	Port      string
	LogLevel  string
	LogDir    string
	StoreDir  string
	ProxyURL  string
	WebhookURL string
	APIKey    string
}

func Load() *Config {
	cfg := &Config{}

	flag.StringVar(&cfg.Port, "port", "8090", "HTTP server port")
	flag.StringVar(&cfg.LogLevel, "log-level", "info", "Log level (debug, info, warn, error)")
	flag.StringVar(&cfg.LogDir, "log-dir", "logs", "Directory for log files")
	flag.StringVar(&cfg.StoreDir, "store-dir", "storage/sessions", "Directory for session storage")
	flag.StringVar(&cfg.ProxyURL, "proxy", "", "Default proxy URL (overridden per instance)")
	flag.StringVar(&cfg.WebhookURL, "webhook-url", "https://zapmatic.tec.br/index.php/bot-builder/webhook", "Default webhook URL for incoming events")
	flag.StringVar(&cfg.APIKey, "api-key", "", "API key for gateway authentication")
	flag.Parse()

	if v := os.Getenv("ZAPMATIC_PORT"); v != "" {
		cfg.Port = v
	}
	if v := os.Getenv("ZAPMATIC_LOG_LEVEL"); v != "" {
		cfg.LogLevel = v
	}
	if v := os.Getenv("ZAPMATIC_WEBHOOK_URL"); v != "" {
		cfg.WebhookURL = v
	}
	if v := os.Getenv("ZAPMATIC_API_KEY"); v != "" {
		cfg.APIKey = v
	}

	return cfg
}
