<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
Connection::getInstance()->execute("CREATE TABLE IF NOT EXISTS referral_action_limits (
 user_id INT NOT NULL, action VARCHAR(20) NOT NULL, window_start DATETIME NOT NULL,
 attempts INT NOT NULL DEFAULT 1, PRIMARY KEY(user_id,action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "Referral security migration completed.\n";
