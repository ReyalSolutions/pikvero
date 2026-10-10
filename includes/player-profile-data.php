<?php
use App\Core\Database\Connection;
function playerProfileData(int $userId): array {
    $db = Connection::getInstance();
    $db->execute("CREATE TABLE IF NOT EXISTS player_profiles (user_id INT PRIMARY KEY, date_of_birth DATE NULL, gender VARCHAR(30) NULL, city VARCHAR(100) NULL, playing_level VARCHAR(30) NULL, image_url VARCHAR(255) NULL)");
    return $db->selectOne('SELECT * FROM player_profiles WHERE user_id = ?', [$userId], 'i') ?? [];
}
