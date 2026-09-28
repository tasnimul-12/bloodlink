<?php
/**
 * BloodLink - Authentication Middleware
 */

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../config/database.php';

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

        if (($user['role_name'] ?? '') === 'HOSPITAL_STAFF') {
            $stmt = Database::getConnection()->prepare("
                SELECT hs.staff_status, h.approval_status
                FROM hospital_staff hs
                JOIN hospitals h ON h.hospital_id = hs.hospital_id
                WHERE hs.user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $user['user_id']]);
            $staffAccess = $stmt->fetch();

            if (!$staffAccess
                || $staffAccess['staff_status'] !== 'ACTIVE'
                || in_array($staffAccess['approval_status'], ['REJECTED', 'SUSPENDED'], true)) {
                Session::destroy();
                Session::start();
                Session::setFlash('danger', 'Your hospital staff access is no longer active. Please contact an administrator.');
                $response->redirect('/login');
                exit;
            }
        }
    }
}
