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
            $this->response->json(['error' => 'Route not found'], 404);
            return;
        }

        $callback = $route['callback'];
        $params = $route['params'] ?? [];

        if (is_callable($callback)) {
            $result = $callback($this->request, $this->response, ...array_values($params));
        } elseif (is_array($callback)) {
            $controller = new $callback[0]();
            $method = $callback[1];
            $result = $controller->$method($this->request, $this->response, ...array_values($params));
        } else {
            $result = null;
        }

        if ($result !== null) {
            $this->response->json($result);
        }
    }
}