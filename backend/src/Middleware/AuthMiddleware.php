<?php
namespace App\Middleware;

use App\Services\TokenService;
use App\Services\LoggerService;

class AuthMiddleware
{
    private $tokenService;
    private $allowedRole;

    public function __construct(string $allowedRole = '')
    {
        $this->tokenService = new TokenService();
        $this->allowedRole = $allowedRole;
    }

    public function handle($request, $response)
    {
        $token = $this->extractToken();
        
        if (!$token) {
            $response->json(['error' => 'UNAUTHORIZED', 'message' => 'No token'], 401);
            return false;
        }

        try {
            $decoded = $this->tokenService->verifyAccessToken($token);
            $userRole = $decoded['role'] ?? '';
            
            $request->setParam('user_id', (int)($decoded['user_id'] ?? 0));
            $request->setParam('user_email', $decoded['email'] ?? '');
            $request->setParam('user_role', $userRole);
            
            // Если указана конкретная роль — проверяем
            if ($this->allowedRole && $userRole !== $this->allowedRole) {
                $response->json(['error' => 'FORBIDDEN', 'message' => 'Access denied'], 403);
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            $response->json(['error' => 'INVALID_TOKEN', 'message' => 'Invalid token'], 401);
            return false;
        }
    }
    
    private function extractToken()
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
}