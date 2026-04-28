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
        http_response_code($code);
        return $this;
    }

    public function json($data, $statusCode = null)
    {
        if ($statusCode) {
            $this->setStatusCode($statusCode);
        }
        
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->sendHeaders();
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function success($data = null, $message = 'Success')
    {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $this->json($response, 200);
    }

    public function error($message, $code = 400, $details = null)
    {
        $response = [
            'success' => false,
            'error' => $message
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        $this->json($response, $code);
    }

    private function sendHeaders()
    {
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
    }

    public function redirect($url, $statusCode = 302)
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        $this->sendHeaders();
        exit;
    }
}