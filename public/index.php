<?php
/**
 * BloodLink - Front Controller & Application Entry Point
 */

// Keep diagnostics in server logs; never expose exceptions to visitors.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Request.php';
require_once __DIR__ . '/../app/core/Response.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../routes/web.php';

try {
    $request  = new Request();
    $response = new Response();
    $router   = new Router($request, $response);

    registerRoutes($router);
    $router->dispatch();
} catch (Throwable $e) {
    error_log("Unhandled exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>BloodLink — Application Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-4">
        <div class="card shadow-sm border-danger" style="max-width: 650px;">
            <div class="card-header bg-danger text-white fw-bold">
                BloodLink Application Error (500)
            </div>
            <div class="card-body">
                <h5 class="card-title text-danger">An unexpected error occurred</h5>
                <p class="card-text text-muted">Please try again later. The issue has been logged for investigation.</p>
                <div class="mt-3 text-end">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">Go Back</a>
                    <a href="<?= APP_ROOT_URL ?>/" class="btn btn-primary">Return to Home</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
