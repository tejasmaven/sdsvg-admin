<?php
/**
 * Router — front controller dispatcher
 */
class Router
{
    /** @var array[] */
    private array $routes = [];

    /**
     * Register a route.
     *
     * @param string $method       HTTP method (GET|POST)
     * @param string $path         URI pattern, e.g. '/families/{id}/edit'
     * @param string $controller   Controller class name
     * @param string $action       Method name on the controller
     * @param bool   $requiresAuth Whether Auth::check() must pass first
     */
    public function addRoute(
        string $method,
        string $path,
        string $controller,
        string $action,
        bool $requiresAuth = true
    ): void {
        $this->routes[] = compact('method', 'path', 'controller', 'action', 'requiresAuth');
    }

    /**
     * Match the current request against registered routes and dispatch.
     */
    public function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri    = $_SERVER['REQUEST_URI'];

        // Strip query string
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        // Strip the base-path prefix (e.g. /sdsvg-admin)
        $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?? '';
        if ($basePath !== '' && str_starts_with($requestUri, $basePath)) {
            $requestUri = substr($requestUri, strlen($basePath));
        }

        // Normalise: must start with /
        if ($requestUri === '' || $requestUri[0] !== '/') {
            $requestUri = '/' . $requestUri;
        }

        // Try each route
        foreach ($this->routes as $route) {
            if (strtoupper($route['method']) !== strtoupper($requestMethod)) {
                continue;
            }

            $params = [];
            if ($this->matchPath($route['path'], $requestUri, $params)) {
                // Auth check
                if ($route['requiresAuth']) {
                    Auth::check();
                }

                $controllerClass = $route['controller'];
                $actionMethod    = $route['action'];

                if (!class_exists($controllerClass)) {
                    $this->send404();
                    return;
                }

                $instance = new $controllerClass();
                if (!method_exists($instance, $actionMethod)) {
                    $this->send404();
                    return;
                }

                call_user_func_array([$instance, $actionMethod], $params);
                return;
            }
        }

        $this->send404();
    }

    /**
     * Match a route pattern against the actual URI.
     * Fills $params with named captures for {id} segments.
     */
    private function matchPath(string $pattern, string $uri, array &$params): bool
    {
        // Convert {id} to a named capture group — integer only
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[0-9]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Extract only named, non-integer-keyed matches
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[] = (int) $value;
                }
            }
            return true;
        }
        return false;
    }

    private function send404(): void
    {
        http_response_code(404);
        // Try to render a proper 404 view; fall back to plain text
        $viewFile = __DIR__ . '/../../views/errors/404.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo '<h1>404 — Page Not Found</h1>';
        }
    }
}
