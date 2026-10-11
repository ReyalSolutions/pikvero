<?php
require_once __DIR__.'/../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Application\Services\SecurityMonitor;
use App\Application\Services\PackagePaymentVerifier;
function rejectSecurity(callable $action,string $message):void{try{$action();}catch(RuntimeException $e){return;}throw new RuntimeException($message);}
$db=Connection::getInstance();$db->beginTransaction();
$originalIp=$_SERVER['REMOTE_ADDR']??null;
try{
 $_SERVER['REMOTE_ADDR']='203.0.113.250';
 $db->execute('DELETE FROM security_attempts WHERE ip_address=?',[SecurityMonitor::ip()],'s');
 SecurityMonitor::attempt('test_failure',2);SecurityMonitor::attempt('test_failure',2);
 rejectSecurity(fn()=>SecurityMonitor::attempt('test_failure',2),'Repeated failures were not blocked.');
 $block=$db->selectOne('SELECT * FROM security_ip_blocks WHERE ip_address=? AND blocked_until>NOW()',[SecurityMonitor::ip()],'s');
 if(!$block)throw new RuntimeException('Automatic IP block missing.');
 if(!$db->selectOne("SELECT id FROM security_events WHERE ip_address=? AND event_type='automatic_ip_block'",[SecurityMonitor::ip()],'s'))throw new RuntimeException('Security event not recorded.');
 $ref='free_test_'.bin2hex(random_bytes(10));
 PackagePaymentVerifier::remember($ref,null,1,'monthly',0);
 rejectSecurity(fn()=>PackagePaymentVerifier::verify('forged',1,'monthly'),'Forged reference accepted.');
 rejectSecurity(fn()=>PackagePaymentVerifier::verify($ref,2,'monthly'),'Different plan accepted.');
 rejectSecurity(fn()=>PackagePaymentVerifier::verify($ref,1,'yearly'),'Different billing cycle accepted.');
 PackagePaymentVerifier::verify($ref,1,'monthly');PackagePaymentVerifier::consume($ref);
 rejectSecurity(fn()=>PackagePaymentVerifier::verify($ref,1,'monthly'),'Consumed checkout reused.');
 $paid='paid_test_'.bin2hex(random_bytes(10));PackagePaymentVerifier::remember($paid,null,1,'monthly',99900);
 rejectSecurity(fn()=>PackagePaymentVerifier::verify($paid,1,'monthly'),'Unverified paid checkout accepted.');
 $db->execute('UPDATE package_checkouts SET session_hash=? WHERE reference=?',[str_repeat('0',64),$paid],'ss');
 rejectSecurity(fn()=>PackagePaymentVerifier::verify($paid,1,'monthly'),'Another session receipt accepted.');
 echo "PASS: repeated-attempt IP blocks, event logging, forged references, plan/cycle tampering, receipt replay, unverified payments, session ownership.\n";
}finally{$db->rollback();if($originalIp===null)unset($_SERVER['REMOTE_ADDR']);else $_SERVER['REMOTE_ADDR']=$originalIp;}
