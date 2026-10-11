<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Infrastructure\Repositories\ReferralRepository;
$db = Connection::getInstance();
if (!$db->selectOne("SHOW COLUMNS FROM users LIKE 'referral_code'")) {
    $db->execute('ALTER TABLE users ADD referral_code VARCHAR(24) NULL, ADD UNIQUE KEY uq_user_referral_code (referral_code)');
}
$db->execute("CREATE TABLE IF NOT EXISTS owner_referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    owner_id INT NOT NULL,
    referral_code VARCHAR(24) NOT NULL,
    payment_id INT NULL,
    bonus_amount DECIMAL(10,2) NOT NULL DEFAULT 150.00,
    status ENUM('pending_payment','pending_verification','available','requested','paid') NOT NULL DEFAULT 'pending_payment',
    verified_by INT NULL, verified_at DATETIME NULL,
    requested_at DATETIME NULL, paid_by INT NULL, paid_at DATETIME NULL,
    payout_reference VARCHAR(150) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_referred_owner (owner_id),
    UNIQUE KEY uq_referral_payment (payment_id),
    INDEX idx_referrer_status (referrer_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$repo = new ReferralRepository();
foreach ($db->select('SELECT id FROM users WHERE referral_code IS NULL') as $user) $repo->ensureCode((int)$user['id']);
echo "Referral schema and existing user codes created.\n";
