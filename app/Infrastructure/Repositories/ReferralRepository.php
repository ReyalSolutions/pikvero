<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;
use RuntimeException;

class ReferralRepository {
    private Connection $db;
    public function __construct() { $this->db = Connection::getInstance(); }

    public function ensureCode(int $userId): string {
        $user = $this->db->selectOne('SELECT referral_code FROM users WHERE id = ?', [$userId], 'i');
        if (!$user) throw new RuntimeException('User not found.');
        if ($user['referral_code']) return $user['referral_code'];
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = 'PK' . strtoupper(bin2hex(random_bytes(6)));
            try {
                $this->db->execute('UPDATE users SET referral_code = ? WHERE id = ? AND referral_code IS NULL', [$code, $userId], 'si');
                return $this->db->selectOne('SELECT referral_code FROM users WHERE id = ?', [$userId], 'i')['referral_code'];
            } catch (\mysqli_sql_exception $e) {
                if ((int)$e->getCode() !== 1062) throw $e;
            }
        }
        throw new RuntimeException('Unable to generate referral code.');
    }

    public function attach(int $ownerId, string $code): void {
        $code = strtoupper(trim($code));
        if ($code === '') return;
        $this->rateLimit($ownerId, 'attach');
        if (!preg_match('/^PK[0-9A-F]{12}$/', $code)) throw new RuntimeException('Referral code is invalid or inactive.');
        $referrer = $this->db->selectOne("SELECT id FROM users WHERE referral_code = ? AND deleted_at IS NULL AND status = 'active'", [$code], 's');
        if (!$referrer) throw new RuntimeException('Referral code is invalid or inactive.');
        if ((int)$referrer['id'] === $ownerId) throw new RuntimeException('You cannot use your own referral code.');
        $owner = $this->db->selectOne("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND r.name IN ('court_owner','owner') AND u.deleted_at IS NULL AND u.status='active'", [$ownerId], 'i');
        if (!$owner) throw new RuntimeException('Referral codes can only be applied to an owner account.');
        $existing = $this->db->selectOne('SELECT referral_code FROM owner_referrals WHERE owner_id=?', [$ownerId], 'i');
        if ($existing) {
            if ($existing['referral_code'] !== $code) throw new RuntimeException('This owner already has a referral code assigned.');
            return;
        }
        $paid = $this->db->selectOne("SELECT p.id FROM subscription_payments p JOIN subscriptions s ON s.id=p.subscription_id JOIN organizations o ON o.id=s.organization_id WHERE o.owner_id=? AND p.amount>0 AND p.payment_status IN ('paid','completed') LIMIT 1", [$ownerId], 'i');
        if ($paid) throw new RuntimeException('Enter the referral code before the first paid package purchase.');
        $this->db->execute('INSERT INTO owner_referrals (referrer_id,owner_id,referral_code) VALUES (?,?,?)', [(int)$referrer['id'], $ownerId, $code], 'iis');
    }

    public function syncPayments(): void {
        // Payment records in existing onboarding are client initiated; staff must
        // verify the receipt before a reward becomes claimable.
        $this->db->execute("UPDATE owner_referrals r SET payment_id = (
            SELECT MIN(p.id) FROM subscription_payments p JOIN subscriptions s ON s.id=p.subscription_id
            JOIN organizations o ON o.id=s.organization_id
            WHERE o.owner_id=r.owner_id AND p.amount>0 AND p.payment_status IN ('paid','completed') AND p.created_at>=r.created_at
        ), status='pending_verification' WHERE r.status='pending_payment' AND EXISTS (
            SELECT 1 FROM subscription_payments p JOIN subscriptions s ON s.id=p.subscription_id JOIN organizations o ON o.id=s.organization_id
            WHERE o.owner_id=r.owner_id AND p.amount>0 AND p.payment_status IN ('paid','completed') AND p.created_at>=r.created_at
        )");
    }

    public function list(int $userId, bool $admin): array {
        return $this->db->select("SELECT r.*, CONCAT(u.first_name,' ',u.last_name) AS referrer_name,
            CONCAT(ow.first_name,' ',ow.last_name) AS owner_name, p.amount AS package_amount, p.payment_method,
            p.payment_status, p.created_at AS payment_date
            FROM owner_referrals r JOIN users u ON u.id=r.referrer_id JOIN users ow ON ow.id=r.owner_id
            LEFT JOIN subscription_payments p ON p.id=r.payment_id
            " . ($admin ? '' : 'WHERE r.referrer_id=?') . ' ORDER BY r.id DESC LIMIT 500', $admin ? [] : [$userId], $admin ? '' : 'i');
    }

    public function transition(int $id, string $action, int $actor, bool $admin, string $reference = ''): void {
        if (!in_array($action, ['verify','claim','pay'], true) || $id <= 0) throw new RuntimeException('Invalid referral action.');
        $this->rateLimit($actor, $action);
        $actorUser = $this->db->selectOne("SELECT r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.status='active' AND u.deleted_at IS NULL", [$actor], 'i');
        if (!$actorUser) throw new RuntimeException('An active account is required.');
        $admin = $admin && in_array($actorUser['role'], ['super_admin','platform_admin','finance_admin'], true);
        $this->db->beginTransaction();
        try {
            $r = $this->db->selectOne('SELECT * FROM owner_referrals WHERE id=? FOR UPDATE', [$id], 'i');
            if (!$r) throw new RuntimeException('Referral reward not found.');
            if ($action !== 'claim' && (!$admin || in_array($actor, [(int)$r['owner_id'],(int)$r['referrer_id']], true))) {
                throw new RuntimeException('An independent administrator must verify payments and record payouts.');
            }
            if ((float)$r['bonus_amount'] !== 150.0) throw new RuntimeException('Invalid referral bonus amount. Contact an administrator.');
            $accounts = $this->db->selectOne("SELECT COUNT(*) AS total FROM users WHERE id IN (?,?) AND status='active' AND deleted_at IS NULL", [$r['owner_id'],$r['referrer_id']], 'ii');
            if ((int)$accounts['total'] !== 2) throw new RuntimeException('Both referral accounts must be active.');
            if (in_array($action, ['verify','claim','pay'], true)) {
                $payment = $this->db->selectOne("SELECT p.id FROM subscription_payments p JOIN subscriptions s ON s.id=p.subscription_id JOIN organizations o ON o.id=s.organization_id WHERE p.id=? AND o.owner_id=? AND p.amount>0 AND p.payment_status IN ('paid','completed') FOR UPDATE", [$r['payment_id'], $r['owner_id']], 'ii');
                if (!$payment) throw new RuntimeException('The qualifying package payment is no longer successful.');
            }
            if ($action === 'claim') {
                if ((int)$r['referrer_id'] !== $actor || $r['status'] !== 'available') throw new RuntimeException('This reward is not available to claim.');
                $this->db->execute("UPDATE owner_referrals SET status='requested',requested_at=NOW() WHERE id=?", [$id], 'i');
            } elseif ($action === 'verify') {
                if (!$admin || $r['status'] !== 'pending_verification') throw new RuntimeException('Payment cannot be verified for this reward.');
                $payment = $this->db->selectOne("SELECT p.id FROM subscription_payments p JOIN subscriptions s ON s.id=p.subscription_id JOIN organizations o ON o.id=s.organization_id WHERE p.id=? AND o.owner_id=? AND p.amount>0 AND p.payment_status IN ('paid','completed') FOR UPDATE", [$r['payment_id'], $r['owner_id']], 'ii');
                if (!$payment) throw new RuntimeException('A successful paid package purchase is required.');
                $this->db->execute("UPDATE owner_referrals SET status='available',verified_by=?,verified_at=NOW() WHERE id=?", [$actor,$id], 'ii');
            } elseif ($action === 'pay') {
                if (!$admin || $r['status'] !== 'requested') throw new RuntimeException('This reward has no pending cash claim.');
                $reference = trim($reference);
                if (strlen($reference)<3 || strlen($reference)>150 || preg_match('/[\x00-\x1F\x7F]/', $reference)) throw new RuntimeException('Enter a payout receipt/reference (3–150 characters, no control characters).');
                $this->db->execute("UPDATE owner_referrals SET status='paid',paid_by=?,paid_at=NOW(),payout_reference=? WHERE id=?", [$actor,$reference,$id], 'isi');
            } else throw new RuntimeException('Invalid referral action.');
            (new AuditLogRepository())->log($actor, 'referral.' . $action, 'Referrals', 'Reward #' . $id . ' PHP150 ' . $reference);
            $this->db->commit();
        } catch (\Throwable $e) { $this->db->rollback(); throw $e; }
    }

    private function rateLimit(int $userId, string $action): void {
        $this->db->execute("INSERT INTO referral_action_limits (user_id,action,window_start,attempts) VALUES (?,?,NOW(),1)
            ON DUPLICATE KEY UPDATE attempts=IF(window_start<=DATE_SUB(NOW(),INTERVAL 1 MINUTE),1,attempts+1),
            window_start=IF(window_start<=DATE_SUB(NOW(),INTERVAL 1 MINUTE),NOW(),window_start)", [$userId,$action], 'is');
        $limit = $this->db->selectOne('SELECT attempts FROM referral_action_limits WHERE user_id=? AND action=?', [$userId,$action], 'is');
        if ((int)$limit['attempts'] > 20) throw new RuntimeException('Too many referral attempts. Please wait one minute and try again.');
    }
}
