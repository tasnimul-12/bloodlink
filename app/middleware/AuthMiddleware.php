<?php
/**
 * BloodLink - Authentication Middleware
 */

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/Response.php';

class AuthMiddleware {
    public function handle(Request $request, Response $response): void {
        Session::start();
        if (!Session::isLoggedIn()) {
            Session::setFlash('warning', 'Please sign in to access this portal.');
            $response->redirect('/login');
            exit;
        }

        // Verify account is still ACTIVE
        $user = Session::user();
        if (($user['account_status'] ?? '') !== 'ACTIVE') {
            Session::destroy();
            Session::start();
            Session::setFlash('danger', 'Your account is currently ' . htmlspecialchars($user['account_status'] ?? 'INACTIVE') . '. Please contact the system administrator.');
            $response->redirect('/login');
            exit;
        }
    }
}
