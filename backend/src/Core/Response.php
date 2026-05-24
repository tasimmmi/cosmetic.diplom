<?php
namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setStatusCode($code)
    {
        $this->statusCode = $code;
        return $this;
    }

        public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function json($data, $statusCode = null)
    {
        if ($statusCode) {
            $this->setStatusCode($statusCode);
        }
        
        http_response_code($this->statusCode);
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->sendHeaders();
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function success($data = null, $message = 'Success'): array
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) $response['data'] = $data;
        return $response;
    }

    public function error($message, $code = 400, $details = null): array
    {
        $this->setStatusCode($code);
        $response = ['success' => false, 'error' => $message];
        if ($details !== null) $response['details'] = $details;
        return $response;
    }

    private function sendHeaders()
    {
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
    }
}