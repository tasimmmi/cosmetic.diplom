<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class RateLimitMiddleware
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $storageDir;

    public function __construct($maxRequests = 100, $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->storageDir = __DIR__ . '/../../logs/ratelimit';
        
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    public function handle(Request $request, Response $response)
    {
        $ip = $request->getIp();
        $key = md5($ip);
        $file = $this->storageDir . '/' . $key . '.json';
        
        $data = $this->loadData($file);
        
        // Очищаем старые записи
        $data = array_filter($data, function($timestamp) {
            return $timestamp > (time() - $this->windowSeconds);
        });
        
        // Проверяем лимит
        if (count($data) >= $this->maxRequests) {
            $response->json([
                'error' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Too many requests. Please try again later.'
            ], 429);
            return false;
        }
        
        // Добавляем текущий запрос
        $data[] = time();
        $this->saveData($file, $data);
        
        // Добавляем заголовки
        $response->setHeader('X-RateLimit-Limit', $this->maxRequests);
        $response->setHeader('X-RateLimit-Remaining', $this->maxRequests - count($data));
        $response->setHeader('X-RateLimit-Reset', time() + $this->windowSeconds);
        
        return true;
    }

    private function loadData($file)
    {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            return json_decode($content, true) ?: [];
        }
        
        return [];
    }

    private function saveData($file, $data)
    {
        file_put_contents($file, json_encode($data));
    }
}