<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class SystemSettingRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function getAllAsMap(): array {
        $rows = $this->db->select("SELECT setting_key, setting_value, group_name FROM system_settings");
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        return $settings;
    }

    public function getByKey(string $key, ?string $default = null): ?string {
        $row = $this->db->selectOne("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
        return $row ? $row['setting_value'] : $default;
    }

    public function updateKey(string $key, ?string $value, string $group = 'general'): void {
        $existing = $this->db->selectOne("SELECT setting_key FROM system_settings WHERE setting_key = ?", [$key]);
        if ($existing) {
            $this->db->execute("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?", [$value, $key], 'ss');
        } else {
            $this->db->execute("INSERT INTO system_settings (setting_key, setting_value, group_name) VALUES (?, ?, ?)", [$key, $value, $group], 'sss');
        }
    }

    public function updateBatch(array $settingsMap): void {
        foreach ($settingsMap as $key => $value) {
            $this->updateKey((string)$key, $value === null ? null : (string)$value);
        }
    }
}
