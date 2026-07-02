package bulk

import (
	"database/sql"
	"fmt"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

var mysqlDB *sql.DB

type DBConfig struct {
	Host     string
	Port     int
	User     string
	Password string
	Name     string
}

func DefaultDBConfig() DBConfig {
	return DBConfig{
		Host:     "localhost",
		Port:     3306,
		User:     "db_zapmatic_sql",
		Password: "inTwk7z37PnhWcY5",
		Name:     "db_zapmatic_sql",
	}
}

func InitMySQL(cfg DBConfig) error {
	dsn := fmt.Sprintf("%s:%s@tcp(%s:%d)/%s?parseTime=true&charset=utf8mb4&loc=Local",
		cfg.User, cfg.Password, cfg.Host, cfg.Port, cfg.Name,
	)
	var err error
	mysqlDB, err = sql.Open("mysql", dsn)
	if err != nil {
		return fmt.Errorf("mysql open: %w", err)
	}
	mysqlDB.SetMaxOpenConns(10)
	mysqlDB.SetMaxIdleConns(5)
	mysqlDB.SetConnMaxLifetime(5 * time.Minute)

	if err = mysqlDB.Ping(); err != nil {
		return fmt.Errorf("mysql ping: %w", err)
	}
	logging.Log.Info().Str("host", cfg.Host).Str("db", cfg.Name).Msg("MySQL connected")
	return nil
}

func DB() *sql.DB {
	return mysqlDB
}

func NowUnix() int64 {
	return time.Now().Unix()
}
