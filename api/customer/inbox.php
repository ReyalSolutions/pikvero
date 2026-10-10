<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
Auth::requireAuth();
$db = Connection::getInstance(); $request = new Request(); $uid = (int)Auth::id();
$role = Auth::role();
$owner = Auth::hasRole('court_owner','owner');
$platform = !$owner && (Auth::hasRole('super_admin','platform_admin','developer') || Auth::can('system.manage'));
$org = $owner ? $db->selectOne('SELECT id FROM organizations WHERE owner_id=? ORDER BY id LIMIT 1', [$uid], 'i') : null;
$orgId = (int)($org['id'] ?? 0);
if ($request->getMethod() === 'GET' && $request->get('view') === 'unread_count') {
 $count = $db->selectOne('SELECT COUNT(*) AS total FROM notifications WHERE user_id=? AND is_read=0', [$uid], 'i');
 Response::success('Unread notifications', ['count'=>(int)($count['total'] ?? 0)]);
}
// Court-owner announcements are visible to their own players and the publishing owner.
$audience = "(a.organization_id IS NULL OR EXISTS (SELECT 1 FROM organizations o WHERE o.id=a.organization_id AND o.owner_id=?) OR EXISTS (SELECT 1 FROM bookings b WHERE b.organization_id=a.organization_id AND b.customer_id=?) OR EXISTS (SELECT 1 FROM open_play_registrations r JOIN open_play_sessions s ON s.id=r.session_id JOIN facilities f ON f.id=s.facility_id WHERE f.organization_id=a.organization_id AND r.user_id=?))";
if ($request->getMethod() === 'POST') {
 $data = $request->all();
 if (empty($_SESSION['profile_csrf']) || !hash_equals($_SESSION['profile_csrf'], (string)($data['csrf'] ?? ''))) Response::error('Please reload and try again.');
 $action = $data['action'] ?? '';
 if ($action === 'publish') {
  if (!$platform && !($owner && $orgId)) Response::forbidden('You cannot publish announcements.');
  $title = trim((string)($data['title'] ?? '')); $message = trim((string)($data['message'] ?? ''));
  if (strlen($title)<4 || strlen($title)>200 || strlen($message)<10 || strlen($message)>10000) Response::error('Enter a title (4–200 characters) and message (10–10,000 characters).');
  $source = $platform ? ($role === 'developer' ? 'developer' : 'admin') : 'court_owner';
  $db->execute('INSERT INTO announcements (author_id,organization_id,source,title,message) VALUES (?,?,?,?,?)', [$uid,$platform ? null : $orgId,$source,$title,$message], 'iisss');
  Response::success('Announcement published.');
 }
 if ($action === 'read') {
  $id = (int)($data['id'] ?? 0);
  if (($data['kind'] ?? '') === 'announcement') {
   $a=$db->selectOne("SELECT a.id FROM announcements a WHERE a.id=? AND $audience",[$id,$uid,$uid,$uid],'iiii');
   if (!$a) Response::forbidden('Announcement unavailable.');
   $db->execute('INSERT IGNORE INTO announcement_reads (announcement_id,user_id) VALUES (?,?)',[$id,$uid],'ii');
  } else {
   $db->execute('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?',[$id,$uid],'ii');
  }
  Response::success('Marked as read.');
 }
 Response::error('Invalid action.');
}
$view = (string)$request->get('view', '');
if (in_array($view, ['notifications','announcements','managed'], true)) {
 $cursor = max(0,(int)$request->get('before',0));
 $limit = 21; // One extra record determines whether another page exists.
 if ($view === 'managed') {
  if (!$platform && !($owner && $orgId)) Response::forbidden('Permission denied.');
  $where=$platform?'1=1':'a.organization_id=?';$params=$platform?[]:[$orgId];$types=$platform?'':'i';
  if($cursor){$where.=' AND a.id<?';$params[]=$cursor;$types.='i';}
  $rows=$db->select("SELECT a.*,o.name AS organization_name FROM announcements a LEFT JOIN organizations o ON o.id=a.organization_id WHERE $where ORDER BY a.id DESC LIMIT $limit",$params,$types);
 } elseif ($view === 'notifications') {
  $rows=$db->select('SELECT id,title,message,type,is_read,created_at FROM notifications WHERE user_id=?'.($cursor?' AND id<?':'')." ORDER BY id DESC LIMIT $limit",$cursor?[$uid,$cursor]:[$uid],$cursor?'ii':'i');
 } else {
  $rows=$db->select("SELECT a.id,a.title,a.message,a.source,a.created_at,o.name AS organization_name,IF(ar.user_id IS NULL,0,1) AS is_read FROM announcements a LEFT JOIN organizations o ON o.id=a.organization_id LEFT JOIN announcement_reads ar ON ar.announcement_id=a.id AND ar.user_id=? WHERE $audience".($cursor?' AND a.id<?':'')." ORDER BY a.id DESC LIMIT $limit",$cursor?[$uid,$uid,$uid,$uid,$cursor]:[$uid,$uid,$uid,$uid],$cursor?'iiiii':'iiii');
 }
 $more=count($rows)>20;$rows=array_slice($rows,0,20);
 Response::success('Inbox messages',['items'=>$rows,'has_more'=>$more,'next_cursor'=>$more?(int)end($rows)['id']:null]);
}
if ($request->get('manage') === '1') {
 if (!$platform && !($owner && $orgId)) Response::forbidden('Permission denied.');
 $rows=$platform?$db->select('SELECT * FROM announcements ORDER BY id DESC LIMIT 100'):$db->select('SELECT * FROM announcements WHERE organization_id=? ORDER BY id DESC LIMIT 100',[$orgId],'i');
 Response::success('Published announcements',$rows);
}
Response::error('Select a valid inbox view.');
