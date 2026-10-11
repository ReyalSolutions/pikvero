<?php
namespace App\Application\Services;
use App\Core\Database\Connection;
use App\Core\Auth\Auth;
use App\Infrastructure\Repositories\SystemSettingRepository;
class PackagePaymentVerifier {
    public static function remember(string $reference,?string $provider,int $plan,string $cycle,int $amount): void {
        Connection::getInstance()->execute('INSERT INTO package_checkouts (reference,provider_id,session_hash,user_id,plan_id,billing_cycle,amount_centavos) VALUES (?,?,?,?,?,?,?)',[$reference,$provider,hash('sha256',session_id()),Auth::id(),$plan,$cycle,$amount],'sssiisi');
    }
    // Call inside the activation transaction. Lock prevents receipt reuse.
    public static function verify(string $reference,int $plan,string $cycle): array {
        $db=Connection::getInstance();
        $row=$db->selectOne('SELECT *, (created_at>=DATE_SUB(NOW(),INTERVAL 1 DAY)) AS fresh FROM package_checkouts WHERE reference=? FOR UPDATE',[$reference],'s');
        if(!$row || !hash_equals($row['session_hash'],hash('sha256',session_id())) || ($row['user_id'] && (int)$row['user_id']!==(int)Auth::id()) || (int)$row['plan_id']!==$plan || $row['billing_cycle']!==$cycle || $row['consumed_at'] || !$row['fresh']){
            throw new \RuntimeException('Invalid, expired, or already used package checkout. Start checkout again.');
        }
        if((int)$row['amount_centavos']===0)return $row;
        if(!$row['provider_id'] || !function_exists('curl_init'))throw new \RuntimeException('Package payment cannot be verified.');
        $settings=(new SystemSettingRepository())->getAllAsMap();
        $secret=trim($settings['paymongo_secret_key']??'') ?: (getenv('PAYMONGO_SECRET_KEY')?:'');
        $ch=curl_init('https://api.paymongo.com/v1/checkout_sessions/'.rawurlencode($row['provider_id']));
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_HTTPHEADER=>['Authorization: Basic '.base64_encode($secret.':'),'Accept: application/json']]);
        $raw=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        $attributes=json_decode($raw?:'{}',true)['data']['attributes']??[];
        $intent=$attributes['payment_intent']['attributes']??[];
        $local=getenv('APP_ALLOW_TEST_PAYMENTS')==='true' && in_array($_SERVER['SERVER_ADDR']??'', ['127.0.0.1','::1'],true);
        if($status!==200 || ($intent['status']??'')!=='succeeded' || ($intent['currency']??'')!=='PHP' || (int)($intent['amount']??0)!==(int)$row['amount_centavos'] || (!$local && empty($attributes['livemode']))){
            throw new \RuntimeException('Successful package payment has not been verified. Complete checkout before activation.');
        }
        return $row;
    }
    public static function consume(string $reference): void {
        Connection::getInstance()->execute('UPDATE package_checkouts SET consumed_at=NOW() WHERE reference=? AND consumed_at IS NULL',[$reference],'s');
    }
}
