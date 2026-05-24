<?php
namespace App\Core;
use App\Services\LoggerService;

class Request
{
    private array $params = [];
    private array $headers = [];
    private $body = null;
    private array $query = [];
    private string $method;
    private string $path;
    private string $ip;
    private string $userAgent;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->parsePath();
        $this->query = $_GET;
        $this->parseHeaders();
        $this->parseBody();
        $this->ip = $this->parseIp();
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    private function parsePath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }
        
        return $path ?: '/';
    }

    private function parseHeaders()
    {
        if (function_exists('getallheaders')) {
            $this->headers = getallheaders();
            LoggerService::info('Headers parsed via getallheaders', ['headers' => $this->headers]);
        } else {
            foreach ($_SERVER as $name => $value) {
                if (strpos($name, 'HTTP_') === 0) {
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $this->headers[$headerName] = $value;
                }
            }
            LoggerService::info('Headers parsed via $_SERVER', ['headers' => $this->headers]);
        }
    }

    private function parseBody()
    {
        $contentType = $this->headers['Content-Type'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $this->body = json_decode($input, true) ?? [];
        } else {
            $this->body = $_POST;
        }
    }

    private function parseIp()
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
              $_SERVER['HTTP_CLIENT_IP'] ?? 
              $_SERVER['REMOTE_ADDR'] ?? 
              '0.0.0.0';
              
        if (strpos($ip, ',') !== false) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]);
        }
        
        return $ip;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function input($key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function getHeader($name)
    {
        return $this->headers[$name] ?? null;
    }

    public function getBearerToken()
    {
        $authHeader = $this->getHeader('Authorization');
        LoggerService::info('Auth header: ' . ($authHeader ?? 'NOT SET'));
        
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    public function getQueryParam($name, $default = null)
    {
        return $this->query[$name] ?? $default;
    }

    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    public function getParam($name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    public function all()
    {
        return array_merge($this->query, $this->body);
    }

    public function only($keys)
    {
        $result = [];
        $all = $this->all();
        
        foreach ((array) $keys as $key) {
            if (isset($all[$key])) {
                $result[$key] = $all[$key];
            }
        }
        
        return $result;
    }

    public function has($key)
    {
        $all = $this->all();
        return isset($all[$key]);
    }
}