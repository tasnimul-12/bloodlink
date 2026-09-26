<?php
/**
 * BloodLink - Role-Based Access Control Middlewares
 */

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/Response.php';

class AdminMiddleware {
    public function handle(Request $request, Response $response): void {
        Session::start();
        if (!Session::isLoggedIn() || Session::role() !== 'ADMIN') {
            Session::setFlash('danger', 'Access denied. Administrator privileges required.');
            $response->redirect('/login');
            exit;
        }
    }
}

class HospitalMiddleware {
    public function handle(Request $request, Response $response): void {
        Session::start();
        if (!Session::isLoggedIn() || Session::role() !== 'HOSPITAL_STAFF') {
            Session::setFlash('danger', 'Access denied. Hospital Staff credentials required.');
            $response->redirect('/login');
            exit;
        }
    }
}

class DonorMiddleware {
    public function handle(Request $request, Response $response): void {
        Session::start();
        if (!Session::isLoggedIn() || Session::role() !== 'DONOR') {
            Session::setFlash('danger', 'Access denied. Registered Donor account required.');
            $response->redirect('/login');
            exit;
        }
    }
}
