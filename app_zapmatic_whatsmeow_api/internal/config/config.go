package config

import (
	"encoding/json"
	"flag"
	"fmt"
	"os"
	"path/filepath"
)

// DBConfig holds MySQL connection parameters.
type DBConfig struct {
	Host     string `json:"host"`
	Port     int    `json:"port"`
	User     string `json:"user"`
	Password string `json:"password"`
	Name     string `json:"name"`
}

// fileConfig mirrors config.json structure (all fields optional).
type fileConfig struct {
	Port       interface{} `json:"port"` // accepts string or int
	LogLevel   string      `json:"log_level"`
	LogDir     string      `json:"log_dir"`
	StoreDir   string      `json:"store_dir"`
	ProxyURL   string      `json:"proxy"`
	WebhookURL string      `json:"webhook_url"`
	APIKey     string      `json:"api_key"`
	DB         *DBConfig   `json:"database"`
}

type Config struct {
	Port       string
	LogLevel   string
	LogDir     string
	StoreDir   string
	ProxyURL   string
	WebhookURL string
	APIKey     string
	DB         DBConfig
}

// defaultDB returns the legacy hardcoded defaults so existing deployments
// that have no config.json keep working without any change.
func defaultDB() DBConfig {
	return DBConfig{
		Host:     "localhost",
		Port:     3306,
		User:     "db_zapmatic_sql",
		Password: "inTwk7z37PnhWcY5",
		Name:     "db_zapmatic_sql",
	}
}

func Load() *Config {
	// --- 1. Start with hard-coded defaults ---
	cfg := &Config{
		Port:      "8090",
		LogLevel:  "info",
		LogDir:    "logs",
		StoreDir:  "storage/sessions",
		WebhookURL: "https://zapmatic.tec.br/index.php/bot-builder/webhook",
		DB:        defaultDB(),
	}

	// --- 2. Load config.json if it exists (priority over defaults) ---
	loadFileConfig(cfg)

	// --- 3. CLI flags override config.json ---
	flag.StringVar(&cfg.Port, "port", cfg.Port, "HTTP server port")
	flag.StringVar(&cfg.LogLevel, "log-level", cfg.LogLevel, "Log level (debug, info, warn, error)")
	flag.StringVar(&cfg.LogDir, "log-dir", cfg.LogDir, "Directory for log files")
	flag.StringVar(&cfg.StoreDir, "store-dir", cfg.StoreDir, "Directory for session storage")
	flag.StringVar(&cfg.ProxyURL, "proxy", cfg.ProxyURL, "Default proxy URL (overridden per instance)")
	flag.StringVar(&cfg.WebhookURL, "webhook-url", cfg.WebhookURL, "Default webhook URL for incoming events")
	flag.StringVar(&cfg.APIKey, "api-key", cfg.APIKey, "API key for gateway authentication")
	flag.Parse()

	// --- 4. Environment variables override everything ---
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
	// DB env overrides (optional)
	if v := os.Getenv("ZAPMATIC_DB_HOST"); v != "" {
		cfg.DB.Host = v
	}
	if v := os.Getenv("ZAPMATIC_DB_PORT"); v != "" {
		cfg.DB.Port = atoi(v, cfg.DB.Port)
	}
	if v := os.Getenv("ZAPMATIC_DB_USER"); v != "" {
		cfg.DB.User = v
	}
	if v := os.Getenv("ZAPMATIC_DB_PASSWORD"); v != "" {
		cfg.DB.Password = v
	}
	if v := os.Getenv("ZAPMATIC_DB_NAME"); v != "" {
		cfg.DB.Name = v
	}

	return cfg
}

// loadFileConfig tries to read config.json next to the executable or in the
// current working directory. It silently returns if the file does not exist.
func loadFileConfig(cfg *Config) {
	paths := []string{"config.json"}

	// Also try next to the executable
	if exe, err := os.Executable(); err == nil {
		paths = append(paths, filepath.Join(filepath.Dir(exe), "config.json"))
	}

	for _, p := range paths {
		data, err := os.ReadFile(p)
		if err != nil {
			continue
		}
		var fc fileConfig
		if err := json.Unmarshal(data, &fc); err != nil {
			continue
		}
		// Apply non-empty values from file
		if fc.Port != nil {
			switch v := fc.Port.(type) {
			case string:
				if v != "" {
					cfg.Port = v
				}
			case float64:
				cfg.Port = fmt.Sprintf("%.0f", v)
			}
		}
		if fc.LogLevel != "" {
			cfg.LogLevel = fc.LogLevel
		}
		if fc.LogDir != "" {
			cfg.LogDir = fc.LogDir
		}
		if fc.StoreDir != "" {
			cfg.StoreDir = fc.StoreDir
		}
		if fc.ProxyURL != "" {
			cfg.ProxyURL = fc.ProxyURL
		}
		if fc.WebhookURL != "" {
			cfg.WebhookURL = fc.WebhookURL
		}
		if fc.APIKey != "" {
			cfg.APIKey = fc.APIKey
		}
		if fc.DB != nil {
			if fc.DB.Host != "" {
				cfg.DB.Host = fc.DB.Host
			}
			if fc.DB.Port != 0 {
				cfg.DB.Port = fc.DB.Port
			}
			if fc.DB.User != "" {
				cfg.DB.User = fc.DB.User
			}
			if fc.DB.Password != "" {
				cfg.DB.Password = fc.DB.Password
			}
			if fc.DB.Name != "" {
				cfg.DB.Name = fc.DB.Name
			}
		}
		return // use the first file found
	}
}

func atoi(s string, fallback int) int {
	n := 0
	for _, c := range s {
		if c >= '0' && c <= '9' {
			n = n*10 + int(c-'0')
		} else {
			return fallback
		}
	}
	if n == 0 {
		return fallback
	}
	return n
}
