<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Infrastructure\Repositories\ReferralRepository;
use App\Infrastructure\Repositories\UserRepository;

function checkReferral(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function rejectReferral(callable $action, string $message): void {
    try { $action(); } catch (Exception $e) { return; }
    throw new RuntimeException($message);
}
$db=Connection::getInstance();$repo=new ReferralRepository();$users=new UserRepository();
$ids=[];$org=0;$sub=0;
$tag='referral_test_'.bin2hex(random_bytes(5));
try {
    foreach (['customer','court_owner','platform_admin'] as $role) {
        $ids[]=$users->create(['username'=>$tag.$role,'first_name'=>'Referral','last_name'=>'Test','email'=>$tag.$role.'@example.invalid','password_hash'=>password_hash(bin2hex(random_bytes(8)),PASSWORD_DEFAULT),'role_id'=>$users->getRoleIdByName($role)]);
    }
    [$referrer,$owner,$adminActor]=$ids;
    $code=$repo->ensureCode($referrer);
    checkReferral($code===$repo->ensureCode($referrer), 'Codes must stay stable.');
    checkReferral($code!==$repo->ensureCode($owner), 'Codes must be unique.');
    rejectReferral(fn()=>$repo->attach($owner,'INVALID'), 'Invalid codes must fail.');
    rejectReferral(fn()=>$repo->attach($owner,$repo->ensureCode($owner)), 'Self-referrals must fail.');
    $repo->attach($owner,strtolower($code));$repo->attach($owner,$code);
    $rows=$repo->list($referrer,false);
    checkReferral(count($rows)===1 && $rows[0]['status']==='pending_payment', 'One referral must be recorded.');
    $id=(int)$rows[0]['id'];
    rejectReferral(fn()=>$repo->transition($id,'claim',$referrer,false), 'Unpaid referrals must not be claimable.');
    $db->execute("INSERT INTO organizations (name,owner_id,status) VALUES (?,?,'active')",[$tag,$owner],'si');$org=$db->getLastInsertId();
    $plan=$db->selectOne('SELECT id FROM subscription_plans WHERE monthly_price>0 LIMIT 1');
    checkReferral((bool)$plan,'A paid plan is required for this test.');
    $db->execute("INSERT INTO subscriptions (organization_id,plan_id,status,current_period_end,billing_cycle) VALUES (?,?,'active','2099-01-01','monthly')",[$org,$plan['id']],'ii');$sub=$db->getLastInsertId();
    $db->execute("INSERT INTO subscription_payments (subscription_id,amount,payment_method,payment_status) VALUES (?,0,'Free Trial Promo','paid')",[$sub],'i');
    $repo->syncPayments();
    checkReferral($repo->list($referrer,false)[0]['status']==='pending_payment','Free purchases must not qualify.');
    $db->execute("INSERT INTO subscription_payments (subscription_id,amount,payment_method,payment_status) VALUES (?,999,'Integration test receipt','paid')",[$sub],'i');
    $repo->syncPayments();$repo->syncPayments();
    checkReferral($repo->list($referrer,false)[0]['status']==='pending_verification','Paid packages must await verification.');
    rejectReferral(fn()=>$repo->transition($id,'verify',$owner,false),'Non-admins cannot verify.');
    rejectReferral(fn()=>$repo->transition($id,'verify',$referrer,true),'Forged admin flag must fail.');
    $repo->transition($id,'verify',$adminActor,true);
    rejectReferral(fn()=>$repo->transition($id,'claim',$owner,false),'Another user cannot claim the bonus.');
    $db->execute("UPDATE users SET status='suspended' WHERE id=?",[$referrer],'i');
    rejectReferral(fn()=>$repo->transition($id,'claim',$referrer,false),'Suspended users cannot claim.');
    $db->execute("UPDATE users SET status='active' WHERE id=?",[$referrer],'i');
    $repo->transition($id,'claim',$referrer,false);
    rejectReferral(fn()=>$repo->transition($id,'claim',$referrer,false),'Duplicate claims must fail.');
    rejectReferral(fn()=>$repo->transition($id,'pay',$owner,false,'Receipt123'),'Non-admins cannot record payouts.');
    rejectReferral(fn()=>$repo->transition($id,'pay',$adminActor,true,''),'Payout reference is required.');
    $db->execute("UPDATE subscription_payments SET payment_status='refunded' WHERE subscription_id=? AND amount>0",[$sub],'i');
    rejectReferral(fn()=>$repo->transition($id,'pay',$adminActor,true,'REFUND'),'Refunded payments must not pay bonuses.');
    $db->execute("UPDATE subscription_payments SET payment_status='paid' WHERE subscription_id=? AND amount>0",[$sub],'i');
    $db->execute('UPDATE owner_referrals SET bonus_amount=300 WHERE id=?',[$id],'i');
    rejectReferral(fn()=>$repo->transition($id,'pay',$adminActor,true,'TAMPER'),'Tampered reward amounts must fail.');
    $db->execute('UPDATE owner_referrals SET bonus_amount=150 WHERE id=?',[$id],'i');
    $repo->transition($id,'pay',$adminActor,true,'TEST-RECEIPT-'.$tag);
    rejectReferral(fn()=>$repo->transition($id,'pay',$adminActor,true,'DUPLICATE'),'Duplicate payouts must fail.');
    $reward=$repo->list($referrer,false)[0];
    checkReferral($reward['status']==='paid' && (float)$reward['bonus_amount']===150.0 && $reward['paid_at'] && $reward['requested_at'] && $reward['verified_at'], 'Cash ledger must include amount and lifecycle timestamps.');
    checkReferral($repo->list($owner,false)===[], 'User reward listings must be private.');
    for($attempt=0;$attempt<21;$attempt++){try{$repo->attach($owner,'INVALID');}catch(Exception $e){$limited=str_contains($e->getMessage(),'Too many');}}
    checkReferral($limited,'Repeated referral guesses must be rate-limited.');
    echo "PASS: rate limits, suspended accounts, forged roles, refunded payments, amount tampering, automatic stable/unique codes, valid owner attribution, self-referral prevention, free-trial exclusion, verification permissions, claim ownership, duplicate prevention, PHP150 cash ledger.\n";
} finally {
    if ($ids) {
        foreach ($ids as $userId) {
            $db->execute('DELETE FROM referral_action_limits WHERE user_id=?',[$userId],'i');
            $db->execute('DELETE FROM audit_logs WHERE user_id=?',[$userId],'i');
            $db->execute('DELETE FROM owner_referrals WHERE owner_id=? OR referrer_id=?',[$userId,$userId],'ii');
        }
    }
    if($sub){$db->execute('DELETE FROM subscription_payments WHERE subscription_id=?',[$sub],'i');$db->execute('DELETE FROM subscriptions WHERE id=?',[$sub],'i');}
    if($org)$db->execute('DELETE FROM organizations WHERE id=?',[$org],'i');
    foreach($ids as $userId)$db->execute('DELETE FROM users WHERE id=?',[$userId],'i');
}
