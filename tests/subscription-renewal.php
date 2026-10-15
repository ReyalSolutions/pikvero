<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../app/bootstrap.php';

use App\Application\Services\SubscriptionBilling as Billing;
use App\Application\Services\PackagePaymentVerifier;
use App\Core\Auth\Auth;
use App\Core\Auth\Session;
use App\Core\Database\Connection;
use App\Infrastructure\Repositories\SubscriptionPlanRepository;

function expectRenewal(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}

$db = null;
try {
    $jan = new DateTimeImmutable('2026-01-31 12:00:00');
    expectRenewal(Billing::renewalEnd(null, 1, $jan) === '2026-02-28', 'January month-end skipped February.');
    expectRenewal(Billing::renewalEnd(null, 12, new DateTimeImmutable('2024-02-29')) === '2025-02-28', 'Leap-year renewal overflowed.');
    expectRenewal(Billing::isActive(['status' => 'active', 'current_period_end' => '2026-10-15'], new DateTimeImmutable('2026-10-15 23:59:58')), 'Plan expires before the last paid day ends.');
    expectRenewal(!Billing::isActive(['status' => 'active', 'current_period_end' => '2026-10-15'], new DateTimeImmutable('2026-10-16')), 'Expired plan remains active.');
    $quote = Billing::quote(['monthly_price' => 1000, 'yearly_price' => 0], 'yearly', false);
    expectRenewal($quote['amount_centavos'] === 877800, 'Yearly fallback or fee calculation differs.');

    $db = Connection::getInstance();
    $db->beginTransaction();
    $org = $db->selectOne('SELECT id, owner_id FROM organizations ORDER BY id LIMIT 1');
    $plan = $db->selectOne('SELECT * FROM subscription_plans WHERE monthly_price > 0 AND is_free_trial = 0 ORDER BY id LIMIT 1');
    expectRenewal((bool)$org && (bool)$plan, 'Development database needs an organization and a paid plan.');
    Session::set('user_id', (int)$org['owner_id']); Session::set('user_role', 'court_owner');
    Session::set('organization_id', 2147483647);
    Session::set('user_data', ['id' => $org['owner_id'], 'organization_id' => null]);
    expectRenewal(Auth::organizationId() === (int)$org['id'], 'Owner with missing/stale profile organization cannot renew.');
    $repo = new SubscriptionPlanRepository();
    $expired = date('Y-m-d', strtotime('-1 month'));
    $db->execute("INSERT INTO subscriptions (organization_id, plan_id, status, billing_cycle, current_period_end) VALUES (?, ?, 'active', 'monthly', ?)", [(int)$org['id'], (int)$plan['id'], $expired], 'iis');
    $subId = $db->getLastInsertId();
    $monthly = Billing::quote($plan, 'monthly', true)['total'];
    $renewed = $repo->subscribeTenantToPlan((int)$org['id'], (int)$plan['id'], 'monthly', 'Regression test receipt', $monthly);
    expectRenewal($renewed['success'], 'Expired monthly subscription could not renew.');
    $sub = $db->selectOne('SELECT * FROM subscriptions WHERE id = ?', [$subId], 'i');
    expectRenewal($sub['current_period_end'] === Billing::renewalEnd(null, 1), 'Expired subscription renewed from old expiry instead of today.');
    $payment = $db->selectOne('SELECT amount FROM subscription_payments WHERE subscription_id = ? ORDER BY id DESC LIMIT 1', [$subId], 'i');
    expectRenewal((float)$payment['amount'] === $monthly, 'Payment record differs from verified checkout total.');

    $future = date('Y-m-d', strtotime('+10 days'));
    $db->execute("UPDATE subscriptions SET billing_cycle = 'monthly', current_period_end = ? WHERE id = ?", [$future, $subId], 'si');
    $expected = Billing::renewalEnd(['status' => 'active', 'current_period_end' => $future], 1);
    $renewed = $repo->subscribeTenantToPlan((int)$org['id'], (int)$plan['id'], 'monthly', 'Regression early renewal', $monthly);
    expectRenewal($renewed['success'] && $db->selectOne('SELECT current_period_end FROM subscriptions WHERE id = ?', [$subId], 'i')['current_period_end'] === $expected, 'Early renewal loses remaining paid time.');

    $db->execute("UPDATE subscriptions SET billing_cycle = 'yearly', current_period_end = ? WHERE id = ?", [$expired, $subId], 'si');
    expectRenewal($repo->subscribeTenantToPlan((int)$org['id'], (int)$plan['id'], 'monthly', '', $monthly)['success'], 'Expired annual term blocks monthly renewal.');
    $db->execute("UPDATE subscriptions SET billing_cycle = 'yearly', current_period_end = ? WHERE id = ?", [$future, $subId], 'si');
    expectRenewal(!$repo->subscribeTenantToPlan((int)$org['id'], (int)$plan['id'], 'monthly', '', $monthly)['success'], 'Active annual term can be replaced with monthly billing.');
    $db->execute("UPDATE subscriptions SET billing_cycle = 'monthly' WHERE id = ?", [$subId], 'i');
    expectRenewal(!$repo->subscribeTenantToPlan((int)$org['id'], (int)$plan['id'], 'monthly', '', 0)['success'], 'Zero-value receipt can activate a paid renewal.');

    $trial = $db->selectOne('SELECT * FROM subscription_plans WHERE is_free_trial = 1 AND monthly_price > 0 LIMIT 1');
    if ($trial) {
        $db->execute('UPDATE subscriptions SET plan_id = ? WHERE id = ?', [(int)$trial['id'], $subId], 'ii');
        $db->execute("INSERT INTO subscription_payments (subscription_id, amount, payment_method, payment_status) VALUES (?, 0, 'Free Trial Promo', 'paid')", [$subId], 'i');
        $trialRenewal = Billing::quote($trial, 'monthly', $repo->hasUsedFreeTrial((int)$org['id']));
        expectRenewal(!$trialRenewal['trial_eligible'] && $trialRenewal['total'] > 0, 'A previously used trial renews for free.');
    }
    $ref = 'regression_' . bin2hex(random_bytes(10));
    PackagePaymentVerifier::remember($ref, null, (int)$plan['id'], 'monthly', 0);
    PackagePaymentVerifier::consume($ref);
    expectRenewal(!empty(PackagePaymentVerifier::verify($ref, (int)$plan['id'], 'monthly', true)['already_activated']), 'Checkout refresh is not idempotent.');
    $rejected = false;
    try { PackagePaymentVerifier::verify($ref, (int)$plan['id'], 'monthly'); } catch (RuntimeException $e) { $rejected = true; }
    expectRenewal($rejected, 'Consumed checkout can be reused for a new purchase.');
    $db->rollback();
    Session::destroy();
    echo "PASS: expiry, early renewal, stale organization, month-end dates, annual transition, trial reuse, verified amounts and receipt replay. Database changes rolled back.\n";
} catch (Throwable $e) {
    if ($db) $db->rollback();
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n"); exit(1);
}
