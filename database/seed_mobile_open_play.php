<?php
// One-time, idempotent sample sessions for the mobile Open Play preview.
require_once __DIR__.'/../app/bootstrap.php';
$db=\App\Core\Database\Connection::getInstance();
$today=(new DateTimeImmutable('now',new DateTimeZone('Asia/Manila')))->format('Y-m-d');
$existing=$db->selectOne("SELECT COUNT(*) AS total FROM open_play_sessions WHERE session_date>=? AND status IN ('open','full')",[$today],'s');
if((int)$existing['total']>0){echo "Upcoming sessions already exist; no samples added.\n";exit;}
$facilities=$db->select("SELECT id FROM facilities WHERE status='active' ORDER BY id LIMIT 4");
if(!$facilities)throw new RuntimeException('No active facilities available.');
$samples=[['Morning Open Play','07:00:00','09:00:00',16],['Intermediate Open Play','18:00:00','20:00:00',12],['Beginner Friendly','07:00:00','09:00:00',16],['Weekend Fun Play','17:00:00','19:00:00',16]];
$db->beginTransaction();try{foreach($samples as $i=>$sample){$date=(new DateTimeImmutable($today))->modify('+'.($i+1).' days')->format('Y-m-d');$db->execute("INSERT INTO open_play_sessions (facility_id,title,session_date,start_time,end_time,fee_per_player,max_players,status) VALUES (?,?,?,?,?,70,?,'open')",[(int)$facilities[$i%count($facilities)]['id'],$sample[0],$date,$sample[1],$sample[2],$sample[3]],'issssi');}$db->commit();echo "Seeded four sample Open Play sessions.\n";}catch(Throwable $e){$db->rollback();throw $e;}
