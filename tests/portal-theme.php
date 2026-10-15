<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Core\Auth\Session;
use App\Core\Database\Connection;
use App\Core\View\PortalTheme;
use App\Infrastructure\Repositories\OrganizationRepository;
use App\Infrastructure\Repositories\SystemSettingRepository;

function themeCheck(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
$db = Connection::getInstance();
try {
    $db->beginTransaction();
    $orgs = $db->select('SELECT * FROM organizations ORDER BY id');
    themeCheck(count($orgs) > 1, 'Need two development organizations.');
    $org = $orgs[0];
    $other = null;
    foreach ($orgs as $candidate) if ($candidate['owner_id'] !== $org['owner_id']) { $other = $candidate; break; }
    themeCheck((bool)$other, 'Need different organization owners.');
    Session::set('user_id', (int)$org['owner_id']); Session::set('user_role', 'court_owner'); Session::set('organization_id', (int)$org['id']);
    $repo = new OrganizationRepository();
    $org['theme_mode'] = 'dark'; $repo->updateBranding((int)$org['id'], $org);
    themeCheck($repo->findById((int)$org['id'])['theme_mode'] === 'dark', 'Dark theme did not save.');
    themeCheck(PortalTheme::context() === ['mode' => 'dark', 'scope' => 'organization-' . $org['id']], 'Owner theme context is wrong.');
    $_SERVER['SCRIPT_NAME'] = '/pikvero/public/admin/calendar.php';
    themeCheck(str_contains(PortalTheme::attributes(), 'data-portal-theme="dark"'), 'Admin page cannot paint dark mode on first render.');
    $_SERVER['SCRIPT_NAME'] = '/pikvero/public/customer/profile.php';
    themeCheck(PortalTheme::attributes() === '', 'Admin theme leaked to player pages.');
    Session::set('user_id', (int)$other['owner_id']); Session::set('organization_id', (int)$org['id']);
    themeCheck(PortalTheme::context()['scope'] !== 'organization-' . $org['id'], 'Theme from another owner was selected.');
    themeCheck($repo->findById((int)$other['id'])['theme_mode'] === $other['theme_mode'], 'Another organization was modified.');
    Session::set('user_id', (int)$org['owner_id']); Session::set('organization_id', (int)$org['id']);
    $org['theme_mode'] = 'light'; $repo->updateBranding((int)$org['id'], $org);
    themeCheck(PortalTheme::context()['mode'] === 'light', 'Switching back to light was not saved.');
    Session::set('user_role', 'super_admin');
    (new SystemSettingRepository())->updateKey('admin_theme_mode', 'dark');
    themeCheck(PortalTheme::context() === ['mode' => 'dark', 'scope' => 'platform'], 'Platform admin appearance is not isolated.');
    $db->rollback(); Session::destroy();
    echo "PASS: theme persistence, initial server rendering, owner isolation, platform settings, light restore and player-page exclusion. Database changes rolled back.\n";
} catch (Throwable $error) {
    $db->rollback(); fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n"); exit(1);
}
