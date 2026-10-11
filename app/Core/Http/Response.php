<?php
namespace App\Core\Http;

class Response {
    public static function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        exit;
    }

    public static function success(string $message = 'Operation successful', array $data = [], int $statusCode = 200): void {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public static function error(string $message = 'An error occurred', array $errors = [], int $statusCode = 400): void {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    public static function unauthorized(string $message = 'Unauthorized access'): void {
        self::json([
            'success' => false,
            'message' => $message,
            'code' => 'UNAUTHORIZED'
        ], 401);
    }

    public static function forbidden(string $message = 'Access forbidden'): void {
        try { \App\Application\Services\SecurityMonitor::log('access_denied','Restricted action denied.'); } catch (\Throwable $ignored) {}
        self::json([
            'success' => false,
            'message' => $message,
            'code' => 'FORBIDDEN'
        ], 403);
    }

    public static function notFound(string $message = 'Resource not found'): void {
        self::json([
            'success' => false,
            'message' => $message,
            'code' => 'NOT_FOUND'
        ], 404);
    }
}
