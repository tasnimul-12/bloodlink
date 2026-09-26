<?php
/**
 * BloodLink - Web Routes Definition
 */

require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/middleware/GuestMiddleware.php';
require_once __DIR__ . '/../app/middleware/RoleMiddleware.php';

require_once __DIR__ . '/../app/controllers/HomeController.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DonorController.php';
require_once __DIR__ . '/../app/controllers/NotificationsController.php';
require_once __DIR__ . '/../app/controllers/HospitalController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';

function registerRoutes(Router $router): void {
    // ----------------------------------------------------
    // Public Routes
    // ----------------------------------------------------
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/compatibility', [HomeController::class, 'compatibility']);
    $router->get('/about', [HomeController::class, 'about']);

    // Authentication (Guests only)
    $router->get('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
    $router->post('/login', [AuthController::class, 'doLogin']);
    $router->get('/logout', [AuthController::class, 'logout']);
    $router->post('/logout', [AuthController::class, 'logout']);

    $router->get('/register/donor', [AuthController::class, 'registerDonor'], [GuestMiddleware::class]);
    $router->post('/register/donor', [AuthController::class, 'doRegisterDonor']);

    $router->get('/register/hospital', [AuthController::class, 'registerHospital'], [GuestMiddleware::class]);
    $router->post('/register/hospital', [AuthController::class, 'doRegisterHospital']);

    // ----------------------------------------------------
    // Donor Portal Routes
    // ----------------------------------------------------
    $router->get('/donor/dashboard', [DonorController::class, 'dashboard'], [AuthMiddleware::class, DonorMiddleware::class]);
    $router->get('/donor/profile', [DonorController::class, 'profile'], [AuthMiddleware::class, DonorMiddleware::class]);
    $router->post('/donor/profile', [DonorController::class, 'updateProfile'], [AuthMiddleware::class, DonorMiddleware::class]);
    $router->get('/donor/history', [DonorController::class, 'history'], [AuthMiddleware::class, DonorMiddleware::class]);
    $router->get('/notifications', [NotificationsController::class, 'index'], [AuthMiddleware::class]);
    $router->get('/donor/notifications', [NotificationsController::class, 'index'], [AuthMiddleware::class, DonorMiddleware::class]);
    $router->post('/donor/match/respond', [DonorController::class, 'respondMatch'], [AuthMiddleware::class, DonorMiddleware::class]);

    // ----------------------------------------------------
    // Hospital Portal Routes
    // ----------------------------------------------------
    $router->get('/hospital/dashboard', [HospitalController::class, 'dashboard'], [AuthMiddleware::class, HospitalMiddleware::class]);
    $router->get('/hospital/requests', [HospitalController::class, 'requests'], [AuthMiddleware::class, HospitalMiddleware::class]);
    $router->get('/hospital/requests/create', [HospitalController::class, 'createRequest'], [AuthMiddleware::class, HospitalMiddleware::class]);
    $router->post('/hospital/requests/create', [HospitalController::class, 'doCreateRequest'], [AuthMiddleware::class, HospitalMiddleware::class]);
    $router->get('/hospital/requests/view/{id}', [HospitalController::class, 'viewRequest'], [AuthMiddleware::class, HospitalMiddleware::class]);
    $router->post('/hospital/requests/fulfill/{id}', [HospitalController::class, 'fulfillRequest'], [AuthMiddleware::class, HospitalMiddleware::class]);
    $router->post('/hospital/requests/match/{id}', [HospitalController::class, 'matchRequest'], [AuthMiddleware::class, HospitalMiddleware::class]);
    $router->post('/hospital/requests/cancel/{id}', [HospitalController::class, 'cancelRequest'], [AuthMiddleware::class, HospitalMiddleware::class]);
    $router->get('/hospital/inventory', [HospitalController::class, 'inventory'], [AuthMiddleware::class, HospitalMiddleware::class]);
    $router->get('/hospital/fulfillments', [HospitalController::class, 'fulfillments'], [AuthMiddleware::class, HospitalMiddleware::class]);

    // ----------------------------------------------------
    // Administrator Portal Routes
    // ----------------------------------------------------
    $router->get('/admin/dashboard', [AdminController::class, 'dashboard'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->get('/admin/hospitals', [AdminController::class, 'hospitals'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->post('/admin/hospitals/status/{id}', [AdminController::class, 'updateHospitalStatus'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->get('/admin/inventory', [AdminController::class, 'inventory'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->post('/admin/inventory/discard', [AdminController::class, 'discardBag'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->post('/admin/inventory/transfer', [AdminController::class, 'transferBag'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->get('/admin/donations', [AdminController::class, 'donations'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->post('/admin/donations/record', [AdminController::class, 'recordDonation'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->get('/admin/donors', [AdminController::class, 'donors'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->get('/admin/audit-logs', [AdminController::class, 'auditLogs'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->get('/admin/reports', [AdminController::class, 'reports'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->get('/admin/settings', [AdminController::class, 'settings'], [AuthMiddleware::class, AdminMiddleware::class]);
    $router->post('/admin/settings', [AdminController::class, 'updateSettings'], [AuthMiddleware::class, AdminMiddleware::class]);
}
