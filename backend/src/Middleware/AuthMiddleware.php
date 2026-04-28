<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\TokenService;
use App\Services\LoggerService;

class AuthMiddleware
{
    private $tokenService;
    private $allowedRoles;

    public function __construct(array $allowedRoles = [])
    {
        $this->tokenService = new TokenService();
        $this->allowedRoles = $allowedRoles;
    }

    public function handle($request, $response)
    {
        // Получаем токен НАПРЯМУЮ из заголовков
        $token = $this->extractToken();
        
        LoggerService::info('AuthMiddleware: token extracted', [
            'has_token' => !empty($token),
            'token_length' => $token ? strlen($token) : 0
        ]);

        if (!$token) {
            LoggerService::warning('AuthMiddleware: No token');
            $response->json([
                'error' => 'UNAUTHORIZED',
                'message' => 'No token provided'
            ], 401);
            return false;
        }

        try {
            $decoded = $this->tokenService->verifyAccessToken($token);
            
            $userId = (int)($decoded['user_id'] ?? 0);
            $userRole = $decoded['role'] ?? 'client';
            
            // Сохраняем данные пользователя в запросе
            $request->setParam('user_id', $userId);
            $request->setParam('user_email', $decoded['email'] ?? '');
            $request->setParam('user_role', $userRole);
            
            LoggerService::info('AuthMiddleware: user set', [
                'user_id' => $userId,
                'role' => $userRole
            ]);
            
            // Проверяем роль, если указаны разрешенные
            if (!empty($this->allowedRoles)) {
                LoggerService::info('AuthMiddleware: role check', [
                    'user_role' => $userRole,
                    'allowed_roles' => $this->allowedRoles
                ]);
                
                if (!in_array($userRole, $this->allowedRoles)) {
                    LoggerService::warning('AuthMiddleware: access denied');
                    $response->json([
                        'error' => 'FORBIDDEN',
                        'message' => 'Insufficient permissions'
                    ], 403);
                    return false;
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            LoggerService::warning('AuthMiddleware: token error: ' . $e->getMessage());
            $response->json([
                'error' => 'INVALID_TOKEN',
                'message' => 'Invalid token'
            ], 401);
            return false;
        }
    }
    
    /**
     * Извлечь токен из заголовков
     */
    private function extractToken()
    {
        // Способ 1: getallheaders()
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        // Способ 2: $_SERVER
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        // Способ 3: REDIRECT_HTTP_AUTHORIZATION
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
}