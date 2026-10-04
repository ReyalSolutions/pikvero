<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class PackageRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
        $this->ensureSchemaAndSeeds();
    }

    private function ensureSchemaAndSeeds(): void {
        try {
            // Ensure packages table exists
            $this->db->query("CREATE TABLE IF NOT EXISTS `packages` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `organization_id` INT NOT NULL DEFAULT 1,
              `name` VARCHAR(100) NOT NULL,
              `total_hours` INT NOT NULL,
              `price` DECIMAL(10,2) NOT NULL,
              `validity_days` INT DEFAULT 30,
              `discount_badge` VARCHAR(50) DEFAULT NULL,
              `description` TEXT DEFAULT NULL,
              `status` ENUM('active', 'inactive') DEFAULT 'active'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Check if discount_badge or description column missing in existing schema
            $cols = $this->db->select("SHOW COLUMNS FROM `packages` LIKE 'discount_badge'");
            if (empty($cols)) {
                $this->db->query("ALTER TABLE `packages` ADD COLUMN `discount_badge` VARCHAR(50) DEFAULT NULL AFTER `validity_days`");
            }
            $colsDesc = $this->db->select("SHOW COLUMNS FROM `packages` LIKE 'description'");
            if (empty($colsDesc)) {
                $this->db->query("ALTER TABLE `packages` ADD COLUMN `description` TEXT DEFAULT NULL AFTER `discount_badge`");
            }

            // Seed sample packages if table is empty
            $countRes = $this->db->selectOne("SELECT COUNT(*) AS total FROM `packages`");
            if (empty($countRes) || (int)($countRes['total'] ?? 0) === 0) {
                // Get organization id (default 1 if none)
                $org = $this->db->selectOne("SELECT id FROM organizations ORDER BY id ASC LIMIT 1");
                $orgId = $org ? (int)$org['id'] : 1;

                $this->db->execute("INSERT INTO `packages` (`organization_id`, `name`, `total_hours`, `price`, `validity_days`, `discount_badge`, `description`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
                    $orgId,
                    '10-HOUR PLAY PASS',
                    10,
                    2500.00,
                    30,
                    '10% DISCOUNT',
                    'Flexible 10 court hours usable across all SmashZone courts. Valid for 30 days.',
                    'active'
                ], 'isidisss');

                $this->db->execute("INSERT INTO `packages` (`organization_id`, `name`, `total_hours`, `price`, `validity_days`, `discount_badge`, `description`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
                    $orgId,
                    'PRO MONTHLY PASS',
                    25,
                    5500.00,
                    30,
                    '20% DISCOUNT',
                    '25 court hours + priority peak hour booking access for active tournament players.',
                    'active'
                ], 'isidisss');
            }
        } catch (\Throwable $e) {
            error_log('PackageRepository schema setup exception: ' . $e->getMessage());
        }
    }

    public function getAllActive(): array {
        $sql = "SELECT * FROM packages WHERE status = 'active' ORDER BY id ASC";
        return $this->db->select($sql);
    }
}
