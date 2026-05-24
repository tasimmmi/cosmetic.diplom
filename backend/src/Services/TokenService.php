<?php
namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\RefreshToken;
use App\Models\User;
use App\Models\Cosmetologist;
use App\Models\Client;

class TokenService
{
    private string $secretKey;
    private int $accessExpires;
    private int $refreshExpires;

    public function __construct()
    {
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'default-secret-key-change-this';
        $this->accessExpires = $this->parseExpiration($_ENV['JWT_ACCESS_EXPIRES'] ?? '15m');
        $this->refreshExpires = $this->parseExpiration($_ENV['JWT_REFRESH_EXPIRES'] ?? '30d');
    }

    /**
     * Сгенерировать access токен с role_id
     */
    public function generateAccessToken($user)
    {
        $payload = [
            'user_id' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'client',
            'type' => 'access',
            'iat' => time(),
            'exp' => time() + $this->accessExpires
        ];

        if (($user['role'] ?? '') === 'cosmetologist') {
            $payload['cosmetologist_id'] = $user['cosmetologist_id'] ?? $this->getCosmetologistId($user['id']);
        } elseif (($user['role'] ?? '') === 'client') {
            $payload['client_id'] = $user['client_id'] ?? $this->getClientId($user['id']);
        }

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function generateRefreshToken($user, $metadata = [])
    {
        $token = bin2hex(random_bytes(64));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->refreshExpires);

        RefreshToken::create([
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expiresAt,
            'ip_address' => $metadata['ip'] ?? null,
            'user_agent' => $metadata['userAgent'] ?? null
        ]);

        return $token;
    }

    /**
     * Сгенерировать пару токенов
     */
    public function generateTokenPair($user, $metadata = [])
    {
        return [
            'access_token' => $this->generateAccessToken($user),
            'refresh_token' => $this->generateRefreshToken($user, $metadata),
            'expires_in' => $this->accessExpires
        ];
    }

    public function verifyAccessToken($token)
    {
        $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
        
        if (!isset($decoded->type) || $decoded->type !== 'access') {
            throw new \Exception('Invalid token type');
        }
        
        return (array) $decoded;
    }

    /**
     * Обновить access токен с сохранением role_id
     */
    public function refreshAccessToken($refreshToken)
    {
        $tokenRecord = RefreshToken::findByToken($refreshToken);
        
        if (!$tokenRecord) throw new \Exception('Invalid refresh token');
        if ($tokenRecord['revoked']) throw new \Exception('Refresh token has been revoked');
        if (strtotime($tokenRecord['expires_at']) < time()) throw new \Exception('Refresh token expired');
        
        $user = User::findById($tokenRecord['user_id']);
        if (!$user) throw new \Exception('User not found');
        
        if ($user['role'] === 'cosmetologist') {
            $cosm = Cosmetologist::findByUserId($user['id']);
            $user['cosmetologist_id'] = $cosm['id'] ?? null;
        } elseif ($user['role'] === 'client') {
            $client = Client::findByUserId($user['id']);
            $user['client_id'] = $client['id'] ?? null;
        }
        
        $accessToken = $this->generateAccessToken($user);
        
        if (($_ENV['ROTATE_REFRESH_TOKENS'] ?? 'false') === 'true') {
            RefreshToken::revoke($refreshToken);
            return [
                'access_token' => $accessToken,
                'refresh_token' => $this->generateRefreshToken($user, ['ip' => $tokenRecord['ip_address'], 'userAgent' => $tokenRecord['user_agent']]),
                'expires_in' => $this->accessExpires
            ];
        }
        
        return ['access_token' => $accessToken, 'expires_in' => $this->accessExpires];
    }

    public function revokeRefreshToken($token) { return RefreshToken::revoke($token); }
    public function revokeAllUserTokens($userId) { return RefreshToken::revokeAllForUser($userId); }
    public function generateVerificationToken() { return bin2hex(random_bytes(32)); }

    /**
     * Получить cosmetologist_id по user_id
     */
    private function getCosmetologistId($userId): ?int
    {
        $cosm = Cosmetologist::findByUserId((int)$userId);
        return $cosm ? (int)$cosm['id'] : null;
    }

    /**
     * Получить client_id по user_id
     */
    private function getClientId($userId): ?int
    {
        $client = Client::findByUserId((int)$userId);
        return $client ? (int)$client['id'] : null;
    }

    private function parseExpiration($expiresIn)
    {
        $units = ['s' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400];
        if (preg_match('/^(\d+)([smhd])$/', $expiresIn, $matches)) {
            return (int) $matches[1] * $units[$matches[2]];
        }
        return 900;
    }
}