<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
Connection::getInstance()->execute("CREATE TABLE IF NOT EXISTS page_visits (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, visit_date DATE NOT NULL, page_path VARCHAR(255) NOT NULL,
 visitor_hash CHAR(64) NOT NULL, user_id INT NULL, views INT NOT NULL DEFAULT 1,
 first_seen DATETIME NOT NULL, last_seen DATETIME NOT NULL,
 UNIQUE KEY uq_daily_page_visitor (visit_date,page_path,visitor_hash),
 INDEX idx_visit_date (visit_date), INDEX idx_visit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "Page visit tracking migration completed.\n";
