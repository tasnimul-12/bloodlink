<?php
/**
 * BloodLink - HTTP Response Wrapper
 */

class Response {
    public function setStatusCode(int $code): void {
        http_response_code($code);
    }

    public function redirect(string $url): void {
        // Prepend base url if relative
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = APP_ROOT_URL . '/' . ltrim($url, '/');
        }
        header("Location: $url");
        exit;
    }

    public function json(array $data, int $statusCode = 200): void {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
