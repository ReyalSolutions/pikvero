<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class OrganizationRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function findByName(string $name): ?array {
        $sql = "SELECT * FROM organizations WHERE name = ? LIMIT 1";
        return $this->db->selectOne($sql, [$name]);
    }

    public function findByTaxId(string $taxId): ?array {
        $sql = "SELECT * FROM organizations WHERE tax_id = ? LIMIT 1";
        return $this->db->selectOne($sql, [$taxId]);
    }

    public function findById(int $id): ?array {
        $sql = "SELECT * FROM organizations WHERE id = ? LIMIT 1";
        return $this->db->selectOne($sql, [$id]);
    }

    public function findByOwnerId(int $ownerId): ?array {
        $sql = "SELECT * FROM organizations WHERE owner_id = ? LIMIT 1";
        return $this->db->selectOne($sql, [$ownerId]);
    }

    public function create(array $data): int {
        $sql = "INSERT INTO organizations (name, tax_id, owner_id, status) VALUES (?, ?, ?, ?)";
        $this->db->execute($sql, [
            $data['name'],
            $data['tax_id'] ?? null,
            $data['owner_id'],
            $data['status'] ?? 'submitted'
        ], 'ssis');
        return $this->db->getLastInsertId();
    }

    public function updateBranding(int $id, array $data): bool {
        $sql = "UPDATE organizations SET 
                name = ?, 
                app_title = ?, 
                logo_url = ?, 
                tax_id = ?, 
                phone = ?, 
                email = ?, 
                description = ? 
                WHERE id = ?";
        return $this->db->execute($sql, [
            $data['name'],
            $data['app_title'] ?? null,
            $data['logo_url'] ?? null,
            $data['tax_id'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['description'] ?? null,
            $id
        ], 'sssssssi');
    }
}
