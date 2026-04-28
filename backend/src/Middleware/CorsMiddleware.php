<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class CorsMiddleware
{
    public function handle(Request $request, Response $response)
    {
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->setHeader('Access-Control-Max-Age', '3600');
        
        // Обработка preflight запросов
        if ($request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(200);
            $response->json(['status' => 'ok']);
            return false;
        }
        
        return true;
    }
}