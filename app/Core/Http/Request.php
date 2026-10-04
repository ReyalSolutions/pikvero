<?php
namespace App\Core\Http;

class Request {
    private array $get;
    private array $post;
    private array $json;
    private array $server;

    public function __construct() {
        $this->get = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->server = $_SERVER ?? [];

        $input = file_get_contents('php://input');
        $decoded = json_decode($input, true);
        $this->json = is_array($decoded) ? $decoded : [];
    }

    public function getMethod(): string {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function get(string $key, $default = null) {
        if (isset($this->json[$key])) return $this->json[$key];
        if (isset($this->post[$key])) return $this->post[$key];
        if (isset($this->get[$key])) return $this->get[$key];
        return $default;
    }

    public function all(): array {
        return array_merge($this->get, $this->post, $this->json);
    }

    public function isJson(): bool {
        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        return strpos($contentType, 'application/json') !== false;
    }

    public function isPost(): bool {
        return $this->getMethod() === 'POST';
    }

    public function isGet(): bool {
        return $this->getMethod() === 'GET';
    }
}
