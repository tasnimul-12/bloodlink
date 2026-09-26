<?php
/**
 * BloodLink - HTTP Router & Dispatcher
 */

require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/Response.php';

class Router {
    private array $routes = [];
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, array $handler, array $middleware = []): void {
        $this->routes['GET'][$path] = [
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function post(string $path, array $handler, array $middleware = []): void {
        $this->routes['POST'][$path] = [
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function dispatch(): void {
        $method = $this->request->getMethod();
        $path = $this->request->getPath();

        // Exact match
        $route = $this->routes[$method][$path] ?? null;

        // Check for parameterized routes (e.g. /admin/hospitals/approve/{id})
        $params = [];
        if (!$route && isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $routePath => $routeConfig) {
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $routePath);
                $pattern = "#^" . $pattern . "$#";
                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches); // Remove full match
                    $params = $matches;
                    $route = $routeConfig;
                    break;
                }
            }
        }

        if (!$route) {
            $this->response->setStatusCode(404);
            View::render('errors/404', ['title' => 'Page Not Found - BloodLink']);
            return;
        }

        // Execute Middleware chain
        foreach ($route['middleware'] as $mw) {
            if (class_exists($mw)) {
                $middlewareInstance = new $mw();
                if (method_exists($middlewareInstance, 'handle')) {
                    $middlewareInstance->handle($this->request, $this->response);
                }
            }
        }

        [$controllerClass, $actionMethod] = $route['handler'];

        if (!class_exists($controllerClass)) {
            die("Controller class '{$controllerClass}' not found.");
        }

        $controllerInstance = new $controllerClass();
        if (!method_exists($controllerInstance, $actionMethod)) {
            die("Action '{$actionMethod}' not found in controller '{$controllerClass}'.");
        }

        call_user_func_array([$controllerInstance, $actionMethod], $params);
    }
}
