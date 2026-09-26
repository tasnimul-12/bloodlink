<?php
/**
 * BloodLink - Secure Session Manager
 */

class Session {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                ini_set('session.cookie_httponly', '1');
                ini_set('session.use_only_cookies', '1');
                ini_set('session.cookie_samesite', 'Lax');
                session_name(SESSION_NAME);
            }
            if (!headers_sent() || php_sapi_name() === 'cli') {
                @session_start();
            }

            // Check session expiry
            if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME)) {
                self::destroy();
                if (!headers_sent() || php_sapi_name() === 'cli') {
                    @session_start();
                }
            }
            $_SESSION['LAST_ACTIVITY'] = time();
        }
    }

    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_regenerate_id(true);
        }
    }

    public static function destroy(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
        }
    }

    public static function setFlash(string $type, string $message): void {
        self::start();
        $_SESSION['flash'][$type][] = $message;
    }

    public static function getFlashes(): array {
        self::start();
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }

    public static function user(): ?array {
        return self::get('user');
    }

    public static function isLoggedIn(): bool {
        return self::has('user') && !empty(self::get('user')['user_id']);
    }

    public static function role(): ?string {
        $user = self::user();
        return $user['role_name'] ?? null;
    }
}
