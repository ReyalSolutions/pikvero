<?php
namespace App\Core\View;

use App\Core\Auth\Auth;
use App\Core\Database\Connection;

final class PortalTheme {
    public static function context(): array {
        $mode = 'light';
        $scope = 'guest';
        if (Auth::check()) {
            $db = Connection::getInstance();
            if (Auth::hasRole('super_admin', 'platform_admin')) {
                $scope = 'platform';
                $setting = $db->selectOne("SELECT setting_value FROM system_settings WHERE setting_key = 'admin_theme_mode' LIMIT 1");
                $mode = $setting['setting_value'] ?? 'light';
            } else {
                $orgId = Auth::organizationId();
                if ($orgId) {
                    $scope = 'organization-' . $orgId;
                    $org = $db->selectOne('SELECT theme_mode FROM organizations WHERE id = ?', [$orgId], 'i');
                    $mode = $org['theme_mode'] ?? 'light';
                }
            }
        }
        return ['mode' => $mode === 'dark' ? 'dark' : 'light', 'scope' => $scope];
    }

    public static function attributes(bool $force = false): string {
        $path = str_replace('\\', '/', ($_SERVER['SCRIPT_NAME'] ?? '') . ' ' . ($_SERVER['REQUEST_URI'] ?? ''));
        if (!$force && !preg_match('~(?:^|/)public/(admin|owner)/~', $path)) return '';
        $context = self::context();
        return 'data-portal-theme="' . $context['mode'] . '" data-portal-scope="' . $context['scope'] . '"';
    }
}
