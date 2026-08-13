package logging

import (
	"io"
	"os"
	"path/filepath"
	"time"

	"github.com/rs/zerolog"
)

var (
	Log    zerolog.Logger
	Logger = Log
)

func Init(level string, logDir string) {
	lvl, err := zerolog.ParseLevel(level)
	if err != nil {
		lvl = zerolog.InfoLevel
	}

	zerolog.SetGlobalLevel(lvl)
	zerolog.TimeFieldFormat = time.RFC3339

	os.MkdirAll(logDir, 0755)
	logPath := filepath.Join(logDir, "gateway.log")
	logFile, err := os.OpenFile(logPath, os.O_CREATE|os.O_WRONLY|os.O_APPEND, 0644)
	if err != nil {
		logFile = nil
	}

	var writers []io.Writer
	writers = append(writers, zerolog.ConsoleWriter{Out: os.Stdout, TimeFormat: time.RFC3339})
	if logFile != nil {
		writers = append(writers, logFile)
	}

	multi := io.MultiWriter(writers...)
	Log = zerolog.New(multi).With().Timestamp().Logger()
	Log.Info().Str("path", logPath).Msg("Logger initialized")
}
