CREATE TABLE IF NOT EXISTS announcements (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 author_id INT NOT NULL,
 organization_id INT NULL,
 source VARCHAR(20) NOT NULL,
 title VARCHAR(200) NOT NULL,
 message TEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_announcements_org (organization_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcement_reads (
 announcement_id INT UNSIGNED NOT NULL,
 user_id INT NOT NULL,
 read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (announcement_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
