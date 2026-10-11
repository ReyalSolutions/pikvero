<?php
namespace App\Application\Services;
use App\Core\Database\Connection;
use App\Core\Auth\Auth;

class SecurityMonitor {
    public static function ip(): string {
        $ip=$_SERVER['REMOTE_ADDR']??'127.0.0.1';
        return filter_var($ip,FILTER_VALIDATE_IP)?$ip:'127.0.0.1';
    }
    public static function log(string $type,string $detail): void {
        Connection::getInstance()->execute('INSERT INTO security_events (ip_address,user_id,event_type,detail) VALUES (?,?,?,?)',[self::ip(),Auth::id(),substr($type,0,50),substr($detail,0,255)],'siss');
    }
    public static function attempt(string $category,int $limit,int $minutes=15): void {
        $db=Connection::getInstance();$ip=self::ip();$minutes=max(1,min(60,$minutes));
        $db->execute("INSERT INTO security_attempts (ip_address,category,started_at,attempts) VALUES (?,?,NOW(),1)
          ON DUPLICATE KEY UPDATE attempts=IF(started_at<=DATE_SUB(NOW(),INTERVAL {$minutes} MINUTE),1,attempts+1),started_at=IF(started_at<=DATE_SUB(NOW(),INTERVAL {$minutes} MINUTE),NOW(),started_at)",[$ip,$category],'ss');
        $row=$db->selectOne('SELECT attempts FROM security_attempts WHERE ip_address=? AND category=?',[$ip,$category],'ss');
        if((int)$row['attempts']>$limit){
            $db->execute("INSERT INTO security_ip_blocks (ip_address,reason,blocked_until) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE reason=IF(blocked_until IS NULL,reason,VALUES(reason)),blocked_until=IF(blocked_until IS NULL,NULL,VALUES(blocked_until))",[$ip,'Repeated '.$category.' attempts'],'ss');
            self::log('automatic_ip_block','Repeated '.$category.' attempts; blocked for 15 minutes.');
            throw new \RuntimeException('Too many attempts. Access temporarily blocked.');
        }
    }
    public static function enforce(): void {
        if(PHP_SAPI==='cli')return;
        $block=Connection::getInstance()->selectOne('SELECT ip_address FROM security_ip_blocks WHERE ip_address=? AND (blocked_until IS NULL OR blocked_until>NOW())',[self::ip()],'s');
        if($block){http_response_code(403);header('Content-Type: text/plain; charset=utf-8');exit('Access blocked. Contact support if you believe this is an error.');}
        if (in_array($_SERVER['REQUEST_METHOD']??'', ['POST','PUT','PATCH','DELETE'],true) && ($_SERVER['HTTP_SEC_FETCH_SITE']??'')==='cross-site') {
            self::log('cross_site_write_rejected','Cross-site browser mutation rejected.');
            http_response_code(403);exit('Cross-site requests are not allowed.');
        }
        try{self::attempt('request_rate',180,1);}catch(\RuntimeException $e){http_response_code(429);header('Retry-After: 900');exit('Too many requests. Please try again later.');}
        header('X-Content-Type-Options: nosniff');header('X-Frame-Options: SAMEORIGIN');header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
