<?php
namespace App\Core;

class App
{
    private Router $router;
    private Request $request;
    private Response $response;

    public function __construct()
    {
        $this->router = new Router();
        $this->request = new Request();
        $this->response = new Response();
    }

    public function get($path, $callback)
    {
        return $this->router->addRoute('GET', $path, $callback);
    }

    public function post($path, $callback)
    {
        return $this->router->addRoute('POST', $path, $callback);
    }

    public function put($path, $callback)
    {
        return $this->router->addRoute('PUT', $path, $callback);
    }

    public function delete($path, $callback)
    {
        return $this->router->addRoute('DELETE', $path, $callback);
    }

    public function run()
    {
        $route = $this->router->resolve(
            $this->request->getMethod(),
            $this->request->getPath()
        );

        if (!$route) {
            $this->response->json($this->response->error('Route not found', 404));
            return;
        }

        foreach ($route['middlewares'] as $mw) {
            if (is_array($mw) && class_exists($mw[0])) {
                $args = $mw[1] ?? '';
                $args = is_array($args) ? $args : [$args];
                $mw = new $mw[0](...$args);
            }
            if (method_exists($mw, 'handle')) {
                if ($mw->handle($this->request, $this->response) === false) return;
            }
        }

        $callback = $route['callback'];
        $params = $route['params'] ?? [];

        if (is_callable($callback)) {
            $result = $callback($this->request, $this->response, ...array_values($params));
        } elseif (is_array($callback)) {
            $controller = new $callback[0]();
            $result = $controller->{$callback[1]}($this->request, $this->response, ...array_values($params));
        }

        if (isset($result) && is_array($result)) {
            $this->response->json($result, $this->response->getStatusCode());
        }
    }
}