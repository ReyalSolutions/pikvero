<?php
require_once __DIR__.'/../../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Core\Database\Connection;
use App\Application\Services\SecurityMonitor;
Auth::requireAuth();$db=Connection::getInstance();
$actor=$db->selectOne("SELECT r.name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.status='active' AND u.deleted_at IS NULL",[Auth::id()],'i');
if(!$actor || !in_array($actor['name'],['super_admin','platform_admin'],true)) \App\Core\Http\Response::forbidden();
if(empty($_SESSION['security_csrf']))$_SESSION['security_csrf']=bin2hex(random_bytes(24));
$message='';$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 try{
  if(!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['security_csrf'],$_POST['csrf']))throw new RuntimeException('Reload the page and try again.');
  $ip=trim((string)($_POST['ip']??''));if(!filter_var($ip,FILTER_VALIDATE_IP))throw new RuntimeException('Enter a valid IP address.');
  $action=$_POST['action']??'';
  if($action==='block'){
   if($ip===SecurityMonitor::ip())throw new RuntimeException('You cannot block your current IP address.');
   $reason=trim((string)($_POST['reason']??''));if(strlen($reason)<3 || strlen($reason)>255)throw new RuntimeException('Enter a reason (3–255 characters).');
   $db->execute('INSERT INTO security_ip_blocks (ip_address,reason,blocked_by,blocked_until) VALUES (?,?,?,NULL) ON DUPLICATE KEY UPDATE reason=VALUES(reason),blocked_by=VALUES(blocked_by),blocked_until=NULL',[$ip,$reason,Auth::id()],'ssi');
  }elseif($action==='unblock'){
   $db->execute('DELETE FROM security_ip_blocks WHERE ip_address=?',[$ip],'s');
   $db->execute('DELETE FROM security_attempts WHERE ip_address=?',[$ip],'s');
  }else throw new RuntimeException('Invalid action.');
  SecurityMonitor::log('admin_ip_'.$action,$ip);$message='IP block updated.';
 }catch(RuntimeException $e){$error=$e->getMessage();}
}
$events=$db->select('SELECT * FROM security_events ORDER BY id DESC LIMIT 1000');
$blocks=$db->select('SELECT * FROM security_ip_blocks WHERE blocked_until IS NULL OR blocked_until>NOW() ORDER BY created_at DESC');
$pageTitle='Pikvero — Security Monitor';$headExtras=['datatables'];require __DIR__.'/../../includes/head.php';
?>
<style>
.security-page .portal-main>h1{font-size:1.5rem!important;line-height:1.2;margin:4px 0 8px}
.security-page .eyebrow{font-size:.65rem}
.security-panel{padding:14px 16px;margin:12px 0;border:1px solid #dce5df!important;border-radius:10px!important;box-shadow:none!important}
.security-panel h2{font-size:.95rem!important;line-height:1.3;margin:0 0 10px}
.security-form{display:grid;grid-template-columns:minmax(160px,1fr) minmax(220px,2fr) auto;gap:10px;align-items:end;max-width:850px}
.security-form label{display:flex;flex-direction:column;gap:4px;font-size:.72rem}
.security-form input{padding:8px 10px;min-height:36px;box-sizing:border-box;width:100%;border:1px solid #dce5df;border-radius:6px;font:inherit}
.security-page .button{padding:7px 12px;min-height:34px;font-size:.72rem;border-radius:7px!important;box-shadow:none!important;white-space:nowrap}
.security-table{width:100%;font-size:.74rem;line-height:1.35}
.security-table td,.security-table th{padding:7px 9px!important;text-align:left;vertical-align:top}
.security-table th{font-size:.7rem;background:#f3f7f4}
.security-table td form{margin:0}
.security-scroll{overflow-x:auto}
.security-note{font-size:.72rem;color:#687c70;line-height:1.45;margin:8px 0;max-width:1050px}
.security-page .dataTables_wrapper{font-size:.72rem}
.security-page .dataTables_length,.security-page .dataTables_filter{margin:0 0 8px}
.security-page .dataTables_filter input,.security-page .dataTables_length select{padding:5px 7px!important;border:1px solid #dce5df!important;border-radius:6px!important;font:inherit}
.security-page .dataTables_info,.security-page .dataTables_paginate{padding-top:8px!important}
.security-page .dataTables_paginate .paginate_button{padding:4px 8px!important}
@media(max-width:650px){.security-form{grid-template-columns:1fr}.security-panel{padding:12px}.security-form .button{justify-self:start}.security-table{min-width:600px}}
</style></head><body class="security-page"><aside id="sidebar-container"></aside><header id="navbar-container"></header><main class="portal-main"><div class="eyebrow">PLATFORM PROTECTION</div><h1>Security Monitor</h1><p class="security-note">Failed sign-ins, denied access, rejected package activations, and IP blocks. Events are indicators to investigate; they do not prove an attack. Repeated failures trigger a 15-minute block. IPs come from the server connection, not client-supplied forwarding headers.</p>
<?php if($message||$error):?><p role="status"><?= htmlspecialchars($error?:$message) ?></p><?php endif;?>
<section class="card-streetside security-panel"><h2>Block an IP address</h2><form method="post" class="security-form"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['security_csrf']) ?>"><input type="hidden" name="action" value="block"><label>IP address<input name="ip" maxlength="45" required placeholder="203.0.113.10"></label><label>Reason<input name="reason" minlength="3" maxlength="255" required></label><button class="button coral" type="submit">Block IP</button></form><p class="security-note">Manual blocks remain until unblocked. Shared networks can include legitimate users. Your current IP cannot be blocked from this page.</p></section>
<section class="card-streetside security-panel"><h2>Active IP blocks (<?= count($blocks) ?>)</h2><div class="security-scroll"><table id="security-blocks" class="security-table"><thead><tr><th>IP address</th><th>Reason</th><th>Expires</th><th>Action</th></tr></thead><tbody><?php foreach($blocks as $b):?><tr><td><?= htmlspecialchars($b['ip_address']) ?></td><td><?= htmlspecialchars($b['reason']) ?></td><td><?= htmlspecialchars($b['blocked_until']??'Until manually unblocked') ?></td><td><form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['security_csrf']) ?>"><input type="hidden" name="action" value="unblock"><input type="hidden" name="ip" value="<?= htmlspecialchars($b['ip_address']) ?>"><button class="button sand">Unblock</button></form></td></tr><?php endforeach;?></tbody></table></div></section>
<section class="card-streetside security-panel"><h2>Recent security events</h2><div class="security-scroll"><table id="security-events" class="security-table"><thead><tr><th>Time</th><th>IP</th><th>User ID</th><th>Event</th><th>Details</th></tr></thead><tbody><?php foreach($events as $e):?><tr><td><?= htmlspecialchars($e['created_at']) ?></td><td><?= htmlspecialchars($e['ip_address']) ?></td><td><?= (int)$e['user_id']?:'Guest' ?></td><td><?= htmlspecialchars($e['event_type']) ?></td><td><?= htmlspecialchars($e['detail']) ?></td></tr><?php endforeach;?></tbody></table></div></section><footer id="footer-container"></footer></main>
<?php require __DIR__.'/../../includes/scripts.php';?><script>document.addEventListener('DOMContentLoaded',async()=>{await NavbarComponent.render('#navbar-container',true);await AuthHelper.checkSession();SidebarComponent.render('security','admin');FooterComponent.render('#footer-container',true);$('#security-events').DataTable({order:[[0,'desc']],pageLength:10});$('#security-blocks').DataTable({pageLength:5,lengthMenu:[5,10,25,50],columnDefs:[{targets:3,orderable:false}]});});</script></body></html>
