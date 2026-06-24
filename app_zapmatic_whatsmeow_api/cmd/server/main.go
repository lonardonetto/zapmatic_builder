package main

import (
	"context"
	"fmt"
	"net/http"
	"os"
	"os/signal"
	"syscall"

	_ "github.com/mattn/go-sqlite3"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/capabilities"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/config"
	zaphttp "github.com/lonardonetto/zapmatic-whatsmeow/internal/http"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/runtime"
)

func main() {
	cfg := config.Load()
	logging.Init(cfg.LogLevel, cfg.LogDir)

	logging.Log.Info().
		Str("port", cfg.Port).
		Str("webhook_url", cfg.WebhookURL).
		Str("store_dir", cfg.StoreDir).
		Msg("Starting Zapmatic Whatsmeow Gateway")

	caps := capabilities.Get()
	logging.Log.Info().Interface("capabilities", caps).Msg("Gateway capabilities")

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	rt := runtime.New(cfg.StoreDir, cfg.WebhookURL)
	if err := rt.Init(ctx); err != nil {
		logging.Log.Fatal().Err(err).Msg("Failed to initialize runtime")
	}

	router := zaphttp.NewRouter(rt, cfg.APIKey)

	server := &http.Server{
		Addr:    fmt.Sprintf(":%s", cfg.Port),
		Handler: router,
	}

	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		logging.Log.Info().Str("addr", server.Addr).Msg("HTTP server listening")
		if err := server.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			logging.Log.Fatal().Err(err).Msg("HTTP server failed")
		}
	}()

	<-quit
	logging.Log.Info().Msg("Shutting down server...")
	rt.Shutdown()
	cancel()
}
