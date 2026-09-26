<?php
/**
 * BloodLink - Base Controller
 */

require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/View.php';
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Csrf.php';

abstract class Controller {
    protected Request $request;
    protected Response $response;

    public function __construct() {
        $this->request = new Request();
        $this->response = new Response();
        Session::start();
    }

    protected function render(string $viewPath, array $data = [], string $layout = 'main'): void {
        View::render($viewPath, $data, $layout);
    }

    protected function json(array $data, int $statusCode = 200): void {
        $this->response->json($data, $statusCode);
    }

    protected function redirect(string $url): void {
        $this->response->redirect($url);
    }

    protected function validateCsrf(): void {
        if ($this->request->isPost()) {
            $token = $this->request->input('csrf_token');
            if (!Csrf::validate($token)) {
                Session::setFlash('danger', 'Invalid security token (CSRF). Please refresh and try again.');
                $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
                exit;
            }
        }
    }
}
