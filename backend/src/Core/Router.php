<?php
namespace App\Core;

class Router
{
    public array $routes = [];

    public function addRoute($method, $path, $callback)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalizePath($path),
            'callback' => $callback,
            'middlewares' => []
        ];
        
        return new class($this, count($this->routes) - 1) {
            private $router;
            private $index;
            
            public function __construct($router, $index)
            {
                $this->router = $router;
                $this->index = $index;
            }
            
            public function middleware($m)
            {
                $this->router->routes[$this->index]['middlewares'][] = $m;
            }
        };
    }

    public function resolve($method, $path)
    {
        $path = str_replace('/backend/public', '', $path);
        $path = $this->normalizePath($path);
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && $route['method'] !== 'ANY') {
                continue;
            }

            $params = $this->matchPath($route['path'], $path);
            
            if ($params !== null) {
                return [
                    'callback' => $route['callback'],
                    'middlewares' => $route['middlewares'],
                    'params' => $params
                ];
            }
        }

        return null;
    }

    private function matchPath($routePath, $requestPath)
    {
        if ($routePath === $requestPath) {
            return [];
        }

        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));

        if (count($routeParts) !== count($requestParts)) {
            return null;
        }

        $params = [];
        
        for ($i = 0; $i < count($routeParts); $i++) {
            if (preg_match('/^{([a-zA-Z_][a-zA-Z0-9_]*)}$/', $routeParts[$i], $matches)) {
                $params[$matches[1]] = $requestParts[$i];
            } elseif ($routeParts[$i] !== $requestParts[$i]) {
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