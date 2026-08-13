CREATE TABLE IF NOT EXISTS sp_connection_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    instance_id VARCHAR(50) NOT NULL,
    token CHAR(36) NOT NULL,
    client_name VARCHAR(100) DEFAULT '',
    status ENUM('pending','used','expired') DEFAULT 'pending',
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME DEFAULT NULL,
    connected_phone VARCHAR(30) DEFAULT '',
    connected_name VARCHAR(100) DEFAULT '',
    connected_avatar VARCHAR(500) DEFAULT '',
    UNIQUE KEY idx_token (token),
    KEY idx_team_status (team_id, status),
    KEY idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
