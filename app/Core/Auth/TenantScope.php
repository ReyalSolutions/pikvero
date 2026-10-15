<?php
namespace App\Core\Auth;

use App\Core\Database\Connection;
use App\Core\Http\Response;

final class TenantScope {
    public static function organizationSql(string $column): string {
        if (Auth::hasRole('super_admin', 'platform_admin')) return '1=1';
        $userId = (int)Auth::id();
        if (!Auth::check()) return '1=0';
        if (Auth::hasRole('court_owner', 'owner')) {
            return "{$column} IN (SELECT id FROM organizations WHERE owner_id = {$userId})";
        }
        return "{$column} IN (SELECT organization_id FROM organization_users WHERE user_id = {$userId})";
    }

    public static function facilitySql(string $column): string {
        $scope = self::organizationSql('tenant_f.organization_id');
        return "{$column} IN (SELECT tenant_f.id FROM facilities tenant_f WHERE {$scope})";
    }

    public static function requireFacility(int $id): void {
        Auth::requireAuth();
        $scope = self::facilitySql('id');
        if (!Connection::getInstance()->selectOne("SELECT id FROM facilities WHERE id = ? AND {$scope}", [$id], 'i')) {
            Response::forbidden('You do not have access to this facility.');
        }
    }

    public static function requireOrganization(): int {
        Auth::requireAuth();
        $orgId = Auth::organizationId();
        if (!$orgId) Response::forbidden('No organization is associated with this account.');
        return $orgId;
    }
}
