package storage

import (
	"crypto/rand"
	"encoding/hex"
	"fmt"
	"io"
	"mime/multipart"
	"net/http"
	"os"
	"path/filepath"
	"strings"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

type Manager struct {
	baseDir string
}

func New(baseDir string) *Manager {
	filesDir := filepath.Join(baseDir, "files")
	if err := os.MkdirAll(filesDir, 0755); err != nil {
		logging.Log.Warn().Err(err).Str("dir", filesDir).Msg("Failed to create files directory")
	}
	return &Manager{baseDir: baseDir}
}

func (m *Manager) generateID() string {
	b := make([]byte, 8)
	rand.Read(b)
	return hex.EncodeToString(b)
}

func (m *Manager) Save(instanceID string, data io.Reader, filename string) (string, error) {
	dir := filepath.Join(m.baseDir, "files", instanceID)
	if err := os.MkdirAll(dir, 0755); err != nil {
		return "", fmt.Errorf("failed to create instance dir: %w", err)
	}

	ext := filepath.Ext(filename)
	if ext == "" {
		ext = ".bin"
	}
	storeName := m.generateID() + ext

	path := filepath.Join(dir, storeName)
	f, err := os.Create(path)
	if err != nil {
		return "", fmt.Errorf("failed to create file: %w", err)
	}
	defer f.Close()

	written, err := io.Copy(f, data)
	if err != nil {
		os.Remove(path)
		return "", fmt.Errorf("failed to write file: %w", err)
	}

	logging.Log.Info().Str("instance", instanceID).Str("path", path).Int64("bytes", written).Msg("File saved")
	return storeName, nil
}

func (m *Manager) SaveFromURL(instanceID, url string, filename string) (string, error) {
	resp, err := http.Get(url)
	if err != nil {
		return "", fmt.Errorf("failed to download: %w", err)
	}
	defer resp.Body.Close()

	return m.Save(instanceID, resp.Body, filename)
}

func (m *Manager) SaveFromMultipart(instanceID string, fileHeader *multipart.FileHeader) (string, error) {
	f, err := fileHeader.Open()
	if err != nil {
		return "", fmt.Errorf("failed to open upload: %w", err)
	}
	defer f.Close()

	return m.Save(instanceID, f, fileHeader.Filename)
}

func (m *Manager) Path(instanceID, filename string) string {
	return filepath.Join(m.baseDir, "files", instanceID, filename)
}

func (m *Manager) Exists(instanceID, filename string) bool {
	_, err := os.Stat(m.Path(instanceID, filename))
	return err == nil
}

func (m *Manager) List(instanceID string) ([]string, error) {
	dir := filepath.Join(m.baseDir, "files", instanceID)
	entries, err := os.ReadDir(dir)
	if err != nil {
		if os.IsNotExist(err) {
			return nil, nil
		}
		return nil, err
	}

	var files []string
	for _, e := range entries {
		if !e.IsDir() {
			files = append(files, e.Name())
		}
	}
	return files, nil
}

func (m *Manager) Delete(instanceID, filename string) error {
	path := m.Path(instanceID, filename)
	if err := os.Remove(path); err != nil && !os.IsNotExist(err) {
		return err
	}
	return nil
}

func (m *Manager) ContentType(filename string) string {
	ext := strings.ToLower(filepath.Ext(filename))
	switch ext {
	case ".jpg", ".jpeg":
		return "image/jpeg"
	case ".png":
		return "image/png"
	case ".gif":
		return "image/gif"
	case ".webp":
		return "image/webp"
	case ".mp4":
		return "video/mp4"
	case ".ogg":
		return "audio/ogg"
	case ".mp3":
		return "audio/mpeg"
	case ".pdf":
		return "application/pdf"
	case ".doc", ".docx":
		return "application/msword"
	default:
		return "application/octet-stream"
	}
}
