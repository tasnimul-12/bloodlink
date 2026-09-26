<?php
/**
 * BloodLink - CSRF Protection Helper. testing github actions.
 */

require_once __DIR__ . '/Session.php';

class Csrf {
    public static function token(): string {
        Session::start();
        $token = Session::get('csrf_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set('csrf_token', $token);
        }
        return $token;
    }

    public static function input(): string {
        $token = self::token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate(?string $submittedToken): bool {
        Session::start();
        $storedToken = Session::get('csrf_token');
        if (!$storedToken || !$submittedToken) {
            return false;
        }
        return hash_equals($storedToken, $submittedToken);
    }
}
