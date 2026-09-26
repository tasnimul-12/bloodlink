<?php
/**
 * BloodLink - View Renderer
 */

require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Csrf.php';

class View {
    public static function render(string $viewPath, array $data = [], string $layout = 'main'): void {
        extract($data);

        // Capture view content
        $viewFile = __DIR__ . '/../../views/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            die("View file not found: views/{$viewPath}.php");
        }

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // Render layout
        if ($layout) {
            $layoutFile = __DIR__ . '/../../views/layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                die("Layout file not found: views/layouts/{$layout}.php");
            }
            include $layoutFile;
        } else {
            echo $content;
        }
    }
}

// Global View Helpers
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string {
    return APP_ROOT_URL . '/' . ltrim($path, '/');
}

function csrf_field(): string {
    return Csrf::input();
}
