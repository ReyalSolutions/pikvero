<?php
namespace App\Core\Auth;

use App\Config\PermissionConfig;
use App\Core\Database\Connection;
use App\Core\Http\Response;

class Auth {
    public static function login(array $user): void {
        Session::regenerate();
        Session::set('user_id', (int)$user['id']);
        Session::set('user_email', $user['email']);
        Session::set('user_name', $user['first_name'] . ' ' . $user['last_name']);
        Session::set('user_role', $user['role_name'] ?? 'customer');
        Session::set('organization_id', isset($user['organization_id']) ? (int)$user['organization_id'] : null);
        Session::set('user_data', $user);
    }

    public static function check(): bool {
        return Session::has('user_id');
    }

    public static function id(): ?int {
        return Session::get('user_id');
    }

    public static function user(): ?array {
        return Session::get('user_data');
    }

    public static function role(): ?string {
        return Session::get('user_role', 'customer');
    }

    public static function organizationId(): ?int {
        $orgId = Session::get('organization_id');
        if (($orgId === null || (int)$orgId <= 0) && self::check()) {
            $userId = self::id();
            if ($userId) {
                try {
                    $db = Connection::getInstance();
                    // 1. Check if user owns an organization
                    $org = $db->selectOne("SELECT id FROM organizations WHERE owner_id = ? LIMIT 1", [$userId], 'i');
                    if ($org && !empty($org['id'])) {
                        $orgId = (int)$org['id'];
                    } else {
                        // 2. Check if user is linked to an organization via facilities
                        $fac = $db->selectOne("SELECT organization_id FROM facilities WHERE organization_id IS NOT NULL ORDER BY id ASC LIMIT 1");
                        if ($fac && !empty($fac['organization_id'])) {
                            $orgId = (int)$fac['organization_id'];
                        }
                    }

                    // 3. Fallback to first organization in database
                    if (!$orgId) {
                        $firstOrg = $db->selectOne("SELECT id FROM organizations ORDER BY id ASC LIMIT 1");
                        if ($firstOrg && !empty($firstOrg['id'])) {
                            $orgId = (int)$firstOrg['id'];
                        } else {
                            $orgId = 1;
                        }
                    }

                    if ($orgId > 0) {
                        Session::set('organization_id', $orgId);
                    }
                } catch (\Throwable $e) {}
            }
        }
        return $orgId ? (int)$orgId : null;
    }

    public static function logout(): void {
        Session::destroy();
    }

    public static function hasRole(string ...$roles): bool {
        $currentRole = self::role();
        return in_array($currentRole, $roles, true);
    }

    public static function permissions(): array {
        $user = self::user();
        if (!$user) return [];

        $role = $user['role_name'] ?? '';
        if ($role === 'super_admin') {
            return array_keys(PermissionConfig::$permissions);
        }

        $roleId = (int)($user['role_id'] ?? 0);
        if ($roleId > 0) {
            try {
                $db = \App\Core\Database\Connection::getInstance();
                $rows = $db->select("
                    SELECT p.name
                    FROM permissions p
                    JOIN role_permissions rp ON rp.permission_id = p.id
                    WHERE rp.role_id = ?
                ", [$roleId], 'i');
                if (!empty($rows)) {
                    return array_column($rows, 'name');
                }
            } catch (\Throwable $e) {}
        }

        return PermissionConfig::$rolePermissions[$role] ?? [];
    }

    public static function hasPermission(string ...$permissions): bool {
        foreach ($permissions as $p) {
            if (self::can($p)) return true;
        }
        return false;
    }

    public static function can(string $permission): bool {
        $role = self::role();
        if (!$role) return false;

        // Super Admin has all permissions
        if ($role === 'super_admin') return true;

        $perms = self::permissions();
        return in_array($permission, $perms, true);
    }

    // Step 1: Authentication Guard
    public static function requireAuth(): void {
        if (!self::check()) {
            Response::unauthorized('Authentication required to access this resource.');
        }
    }

    // Step 2: Role Permission Guard
    public static function requirePermission(string $permission): void {
        self::requireAuth();
        if (!self::can($permission)) {
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $isJson = (strpos($accept, 'application/json') !== false) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

            if (!$isJson) {
                header('Location: /pikvero/public/403.php?permission=' . urlencode($permission));
                exit;
            }

            Response::forbidden("Access denied. Permission '{$permission}' required.");
        }
    }

    // Step 3: Organization / Multi-Tenant Guard
    public static function requireTenantAccess(int $targetOrgId): void {
        self::requireAuth();

        // Super Admin bypasses tenant restriction for cross-tenant management
        if (self::role() === 'super_admin') return;

        $userOrgId = self::organizationId();
        if (!$userOrgId || (int)$userOrgId !== (int)$targetOrgId) {
            Response::forbidden('Access denied. You do not have permission to access resources in this tenant organization.');
        }
    }

    // Step 4: Resource Ownership Guard
    public static function requireResourceOwnership(int $resourceOwnerUserId): void {
        self::requireAuth();

        if (self::role() === 'super_admin') return;

        if ((int)self::id() !== (int)$resourceOwnerUserId) {
            Response::forbidden('Access denied. You are not the owner of this resource.');
        }
    }

    public static function getLogoUrl(): string {
        $logoUrl = '/pikvero/assets/images/logo.png';
        try {
            $db = Connection::getInstance();
            $role = self::role();
            $orgId = self::organizationId();

            if ($orgId && $role !== 'super_admin' && $role !== 'platform_admin') {
                $org = $db->selectOne("SELECT logo_url FROM organizations WHERE id = ?", [$orgId], 'i');
                if (!empty($org['logo_url'])) {
                    return $org['logo_url'];
                }
            }

            $setting = $db->selectOne("SELECT setting_value FROM system_settings WHERE setting_key = 'org_logo_url' LIMIT 1");
            if (!empty($setting['setting_value'])) {
                return $setting['setting_value'];
            }
        } catch (\Throwable $e) {
            // fallback
        }
        return $logoUrl;
    }
}
