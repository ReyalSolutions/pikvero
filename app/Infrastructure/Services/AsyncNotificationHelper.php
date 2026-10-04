<?php
namespace App\Infrastructure\Services;

class AsyncNotificationHelper {
    /**
     * Non-blocking local cURL trigger (50ms timeout) to offload notification dispatches completely
     */
    public static function dispatch(string $action, array $params): void {
        $url = 'http://127.0.0.1/pikvero/api/async-notifier.php';
        $payload = json_encode([
            'action' => $action,
            'params' => $params
        ]);

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 50);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 50);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            @curl_exec($ch);
            @curl_close($ch);
        }
    }
}
