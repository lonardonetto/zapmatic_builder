CREATE TABLE IF NOT EXISTS sp_call_audios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    original_name VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    duration_seconds INT DEFAULT 0,
    format VARCHAR(10) DEFAULT 'mp3',
    file_size_bytes BIGINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_team (team_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sp_call_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    instance_id VARCHAR(50) NOT NULL,
    audio_id INT DEFAULT NULL,
    name VARCHAR(200) NOT NULL,
    status ENUM('draft','scheduled','running','paused','completed','failed') DEFAULT 'draft',
    max_concurrent INT DEFAULT 1,
    delay_between_calls INT DEFAULT 30,
    timeout_ring INT DEFAULT 30,
    record_response TINYINT(1) DEFAULT 0,
    schedule_start DATETIME DEFAULT NULL,
    schedule_end DATETIME DEFAULT NULL,
    total_leads INT DEFAULT 0,
    calls_made INT DEFAULT 0,
    calls_answered INT DEFAULT 0,
    calls_no_answer INT DEFAULT 0,
    calls_busy INT DEFAULT 0,
    calls_failed INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_team_status (team_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sp_call_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    phone VARCHAR(30) NOT NULL,
    name VARCHAR(100) DEFAULT '',
    status ENUM('pending','ringing','answered','no_answer','busy','failed','cancelled') DEFAULT 'pending',
    call_id VARCHAR(100) DEFAULT NULL,
    started_at DATETIME DEFAULT NULL,
    answered_at DATETIME DEFAULT NULL,
    ended_at DATETIME DEFAULT NULL,
    duration_seconds INT DEFAULT 0,
    response_audio VARCHAR(500) DEFAULT NULL,
    error_message VARCHAR(500) DEFAULT NULL,
    retry_count INT DEFAULT 0,
    INDEX idx_campaign_status (campaign_id, status),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
