<?php
/**
 * BloodLink - Guest Middleware
 */

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/Response.php';

class GuestMiddleware {
    public function handle(Request $request, Response $response): void {
        Session::start();
        if (Session::isLoggedIn()) {
            $role = Session::role();
            if ($role === 'ADMIN') {
                $response->redirect('/admin/dashboard');
            } elseif ($role === 'HOSPITAL_STAFF') {
                $response->redirect('/hospital/dashboard');
            } elseif ($role === 'DONOR') {
                $response->redirect('/donor/dashboard');
            } else {
                $response->redirect('/');
            }
            exit;
        }
    }
}
