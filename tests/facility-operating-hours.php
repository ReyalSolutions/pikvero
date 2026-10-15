<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Application\Services\AvailabilityEngine;
$db=Connection::getInstance();
$db->beginTransaction();
try {
 $court=$db->selectOne("SELECT id FROM courts WHERE status='active' LIMIT 1");
 if (!$court) throw new RuntimeException('An active test court is required.');
 $id=(int)$court['id']; $date='2030-10-15'; $day=(int)date('w',strtotime($date));
 $db->execute('DELETE FROM court_operating_hours WHERE court_id=?',[$id],'i');
 $engine=new AvailabilityEngine();
 if (!$engine->getAvailableTimeSlots($id,$date)) throw new RuntimeException('Legacy court lost its default schedule.');
 $db->execute('INSERT INTO court_operating_hours (court_id,day_of_week,open_time,close_time) VALUES (?, ?, ?, ?)',[$id,($day+1)%7,'06:00:00','22:00:00'],'iiss');
 if ($engine->getAvailableTimeSlots($id,$date)!==[]) throw new RuntimeException('Missing hours must be closed.');
 function rejected($engine,$id,$date,$start,$end) { try {$engine->validateSlotAvailability($id,$date,$start,$end);} catch (Exception $e) {return;} throw new RuntimeException('Closed interval was accepted.'); }
 rejected($engine,$id,$date,'08:00','09:00');
 $db->execute('INSERT INTO court_operating_hours (court_id,day_of_week,open_time,close_time) VALUES (?, ?, ?, ?)',[$id,$day,'08:30:00','17:30:00'],'iiss');
 $slots=$engine->getAvailableTimeSlots($id,$date);
 if ($slots[0]['start_time']!=='09:00'||end($slots)['end_time']!=='17:00') throw new RuntimeException('Slots exceed operating boundaries.');
 $db->execute('DELETE FROM court_pricing WHERE court_id=?',[$id],'i');
 $db->execute('INSERT INTO court_pricing (court_id,name,price_per_hour,start_time,end_time,day_type,is_active) VALUES (?, ?, ?, ?, ?, ?, ?)',[$id,'Test peak pricing',425,'09:00:00','11:00:00','all',1],'isdsssi');
 $priced=$engine->getAvailableTimeSlots($id,$date);
 $quote=(new App\Application\Services\CourtPricing())->quote($id,$date,'09:00','11:00',200);
 if ($priced[0]['price']!==425.0 || $priced[1]['price']!==425.0 || $quote!==850.0) throw new RuntimeException('Slot prices differ from checkout quote.');
 rejected($engine,$id,$date,'08:00','09:00');
 rejected($engine,$id,$date,'17:00','18:00');
 rejected($engine,$id,$date,'12:00','11:00');
 $db->execute('INSERT INTO court_blocked_schedules (court_id,block_date,start_time,end_time,reason) VALUES (?, ?, ?, ?, ?)',[$id,$date,'12:00:00','13:00:00','Test closure'],'issss');
 rejected($engine,$id,$date,'11:00','14:00');
 $now=new DateTimeImmutable('now',new DateTimeZone('Asia/Manila'));
 $today=$now->format('Y-m-d'); $todayDay=(int)$now->format('w');
 $db->execute('DELETE FROM court_operating_hours WHERE court_id=? AND day_of_week=?',[$id,$todayDay],'ii');
 $db->execute('INSERT INTO court_operating_hours (court_id,day_of_week,open_time,close_time) VALUES (?, ?, ?, ?)',[$id,$todayDay,'00:00:00','23:59:00'],'iiss');
 foreach ($engine->getAvailableTimeSlots($id,$today) as $slot) {
  $passed=new DateTimeImmutable($today.' '.$slot['start_time'],$now->getTimezone()) <= $now;
  if ($passed && ($slot['available'] || $slot['status']!=='past')) throw new RuntimeException('Elapsed time remains available.');
  if (!$passed && $slot['status']==='past') throw new RuntimeException('Future time marked past.');
 }
 rejected($engine,$id,$today,'00:00','01:00');
 echo "PASS: closures, operating boundaries, blocked schedules and elapsed Philippine-time slots.\n";
} finally {$db->rollback();}
