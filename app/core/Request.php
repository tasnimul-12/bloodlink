<?php
/**
 * BloodLink - HTTP Request Wrapper. testing two
 */

class Request {
    public function getMethod(): string {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool {
        return $this->getMethod() === 'POST';
    }

    public function isGet(): bool {
        return $this->getMethod() === 'GET';
    }

    public function getPath(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        // Strip query string
        $position = strpos($uri, '?');
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }

        // Adjust for subfolder like /bloodlink or /bloodlink/public
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = dirname($scriptName);
        
        // Normalize slashes
        $uri = str_replace('\\', '/', $uri);
        $baseDir = str_replace('\\', '/', $baseDir);

        if ($baseDir !== '/' && $baseDir !== '' && str_starts_with($uri, $baseDir)) {
            $uri = substr($uri, strlen($baseDir));
        }

        $uri = '/' . ltrim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }

    public function all(): array {
        $body = [];
        if ($this->getMethod() === 'GET') {
            foreach ($_GET as $key => $value) {
                $body[$key] = is_string($value) ? trim($value) : $value;
            }
        } elseif ($this->getMethod() === 'POST') {
            foreach ($_POST as $key => $value) {
                $body[$key] = is_string($value) ? trim($value) : $value;
            }
        }
        return $body;
    }

    public function input(string $key, mixed $default = null): mixed {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    public function ip(): string {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
