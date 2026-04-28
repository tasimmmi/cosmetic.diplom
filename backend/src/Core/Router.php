<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function addRoute($method, $path, $callback)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalizePath($path),
            'callback' => $callback,
            'middlewares' => []
        ];
        
        return $this;
    }

    public function middleware($middleware)
    {
        $lastIndex = count($this->routes) - 1;
        if ($lastIndex >= 0) {
            $this->routes[$lastIndex]['middlewares'][] = $middleware;
        }
        return $this;
    }

    public function resolve($method, $path)
    {
        // Убираем /backend/public из начала пути
        $path = str_replace('/backend/public', '', $path);
        $path = $this->normalizePath($path);
        $method = strtoupper($method);

        error_log("Router resolving: $method $path");
        error_log("Available routes: " . count($this->routes));

        foreach ($this->routes as $route) {
            error_log("Checking route: " . $route['method'] . " " . $route['path']);
            
            if ($route['method'] !== $method && $route['method'] !== 'ANY') {
                continue;
            }

            $params = $this->matchPath($route['path'], $path);
            
            if ($params !== null) {
                error_log("Route matched: " . $route['path']);
                return [
                    'callback' => $route['callback'],
                    'middlewares' => $route['middlewares'],
                    'params' => $params
                ];
            }
        }

        error_log("No route found for: $method $path");
        return null;
    }

    private function matchPath($routePath, $requestPath)
    {
        if ($routePath === $requestPath) {
            return [];
        }

        // Проверяем параметры вида {id}
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));

        if (count($routeParts) !== count($requestParts)) {
            return null;
        }

        $params = [];
        
        for ($i = 0; $i < count($routeParts); $i++) {
            $routePart = $routeParts[$i];
            $requestPart = $requestParts[$i];

            if (preg_match('/^{([a-zA-Z_][a-zA-Z0-9_]*)}$/', $routePart, $matches)) {
                $params[$matches[1]] = $requestPart;
            } elseif ($routePart !== $requestPart) {
                return null;
            }
        }

        return $params;
    }

    private function normalizePath($path)
    {
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }

    public function getRoutes()
    {
        return $this->routes;
    }
}