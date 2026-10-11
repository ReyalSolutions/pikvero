<?php
namespace App\Application\Services;
use App\Core\Database\Connection;
use App\Core\Auth\Auth;

class PageVisitTracker {
    public static function register(): void {
        if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') return;
        $script = str_replace('\\','/',$_SERVER['SCRIPT_FILENAME'] ?? '');
        $root = str_replace('\\','/',ROOT_DIR);
        if (!str_starts_with($script, $root . '/public/') && $script !== $root . '/index.php') return;
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (!$agent || preg_match('/bot|crawl|spider|preview|headless/i',$agent)) return;
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (!str_contains($accept,'text/html')) return;
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (!is_string($path) || strlen($path)>255) return;
        $path = preg_replace('#^/pikvero(?=/|$)#','',$path) ?: '/';
        if (in_array($path,['/index.php','/public/index.php'],true)) $path='/';
        $token = $_COOKIE['pikvero_visitor'] ?? '';
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/',$token)) {
            $token = bin2hex(random_bytes(32));
            setcookie('pikvero_visitor',$token,['expires'=>time()+365*86400,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
            $_COOKIE['pikvero_visitor']=$token;
        }
        register_shutdown_function(function() use($path,$token) {
            if (http_response_code()>=300) return;
            foreach(headers_list() as $header) {
                if (stripos($header,'Content-Type:')===0 && stripos($header,'text/html')===false) return;
            }
            try { self::record($path,hash('sha256',$token),Auth::id()); }
            catch(\Throwable $e) { error_log('Page visit tracking unavailable.'); }
        });
    }

    public static function record(string $path,string $visitorHash,?int $userId): void {
        $now = new \DateTimeImmutable('now',new \DateTimeZone('Asia/Manila'));
        $timestamp=$now->format('Y-m-d H:i:s');
        Connection::getInstance()->execute("INSERT INTO page_visits (visit_date,page_path,visitor_hash,user_id,first_seen,last_seen)
          VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
          views=views+IF(last_seen<=DATE_SUB(VALUES(last_seen),INTERVAL 10 SECOND),1,0),
          user_id=COALESCE(VALUES(user_id),user_id),last_seen=VALUES(last_seen)",[$now->format('Y-m-d'),$path,$visitorHash,$userId,$timestamp,$timestamp],'sssiss');
    }
}
