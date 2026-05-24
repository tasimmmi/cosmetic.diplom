<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Client;
use App\Models\Cosmetologist;
use App\Models\OAuthAccount;
use App\Services\TokenService;
use App\Services\EmailService;
use App\Services\YandexOAuthService;
use App\Services\LoggerService;
use App\Utils\Validator;
use App\Config\Database;

class AuthController
{
    private $tokenService;
    private $emailService;
    private $yandexOAuth;

    public function __construct()
    {
        $this->tokenService = new TokenService();
        $this->emailService = new EmailService();
        $this->yandexOAuth = new YandexOAuthService();
    }

    /**
     * Регистрация нового пользователя
     */
    public function register($request, $response)
    {
        $data = $request->getBody();
        
        $validator = new Validator($data);
        $validator->required(['email', 'password', 'fullname', 'phone', 'role'])
                  ->email('email')
                  ->minLength('password', 8)
                  ->in('role', ['client', 'cosmetologist']);
        
        if (!$validator->isValid()) {
            return $response->error($validator->getFirstError(), 400, $validator->getErrors());
        }
        
        $existingUser = User::findByEmail($data['email']);
        if ($existingUser !== null) {
            return $response->error('Пользователь с таким email уже существует', 409);
        }
        
        try {
            Database::beginTransaction();
            
            $userId = User::create([
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'email_verified' => false
            ]);
            
            if ($userId === 0) throw new \Exception('Не удалось создать пользователя');
            
            if ($data['role'] === 'client') {
                Client::create([
                    'user_id' => $userId,
                    'fullname' => $data['fullname'],
                    'phone' => $data['phone']
                ]);
            } else {
                if (empty($data['first_name']) || empty($data['last_name'])) {
                    throw new \Exception('Имя и фамилия обязательны для косметолога');
                }
                Cosmetologist::create([
                    'user_id' => $userId,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'address' => $data['address'] ?? null
                ]);
            }
            
            $verificationToken = $this->tokenService->generateVerificationToken();
            User::updateVerificationToken($userId, $verificationToken);
            
            Database::commit();
            
            $name = $data['fullname'] ?? ($data['first_name'] . ' ' . $data['last_name']);
            $this->emailService->sendVerificationEmail($data['email'], $verificationToken, $name);
            
            LoggerService::info('User registered', ['user_id' => $userId, 'role' => $data['role']]);
            
            return $response->success([
                'user_id' => $userId,
                'message' => 'Регистрация успешна. Проверьте email для подтверждения.'
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            LoggerService::error('Register failed: ' . $e->getMessage());
            return $response->error($e->getMessage(), 500);
        }
    }

    /**
     * Вход в систему
     */
    public function login($request, $response)
    {
        $data = $request->getBody();
        
        $validator = new Validator($data);
        $validator->required(['email', 'password'])->email('email');
        
        if (!$validator->isValid()) {
            return $response->error($validator->getFirstError(), 400);
        }
        
        $user = User::findByEmail($data['email']);
        
        if (!$user || !User::verifyPassword($user, $data['password'])) {
            LoggerService::warning('Login failed', ['email' => $data['email']]);
            return $response->error('Неверный email или пароль', 401);
        }
        
        if (empty($user['email_verified'])) {
            return $response->error('Пожалуйста, подтвердите email перед входом', 403);
        }
        
        User::updateLastLogin((int)$user['id']);
        
        $tokens = $this->tokenService->generateTokenPair($user, [
            'ip' => $request->getIp(),
            'userAgent' => $request->getUserAgent()
        ]);
        
        $userData = [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'client_id' => isset($user['client_id']) ? (int)$user['client_id'] : null,
            'cosmetologist_id' => isset($user['cosmetologist_id']) ? (int)$user['cosmetologist_id'] : null
        ];
        
        if ($user['role'] === 'client') {
            $userData['fullname'] = $user['client_name'] ?? '';
            $userData['phone'] = $user['client_phone'] ?? '';
        } elseif ($user['role'] === 'cosmetologist') {
            $userData['fullname'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $userData['phone'] = $user['cosmetologist_phone'] ?? '';
        }
        
        LoggerService::info('User logged in', ['user_id' => $user['id'], 'role' => $user['role']]);
        
        return $response->success([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'] ?? 900,
            'user' => $userData
        ]);
    }

    /**
     * Подтверждение email
     */
    public function verifyEmail($request, $response)
    {
        $token = $request->getQueryParam('token');
        $clientUrl = $_ENV['CLIENT_URL'] ?? 'http://localhost:3000';
        
        if (empty($token)) {
            header('Location: ' . $clientUrl . '/login.html?error=missing_token');
            exit;
        }
        
        $verified = User::verifyEmail($token);
        header('Location: ' . $clientUrl . '/login.html?' . ($verified ? 'verified=true' : 'error=invalid_token'));
        exit;
    }

    /**
     * Обновление токена
     */
    public function refreshToken($request, $response)
    {
        $data = $request->getBody();
        
        if (empty($data['refresh_token'])) {
            return $response->error('Refresh token обязателен', 400);
        }
        
        try {
            return $response->success($this->tokenService->refreshAccessToken($data['refresh_token']));
        } catch (\Exception $e) {
            return $response->error($e->getMessage(), 401);
        }
    }

    /**
     * Выход из системы
     */
    public function logout($request, $response)
    {
        $data = $request->getBody();
        if (!empty($data['refresh_token'])) {
            $this->tokenService->revokeRefreshToken($data['refresh_token']);
        }
        return $response->success(null, 'Выход выполнен успешно');
    }

    /**
     * Выход со всех устройств
     */
    public function logoutAll($request, $response)
    {
        $userId = (int)$request->getParam('user_id');
        if ($userId === 0) return $response->error('Пользователь не авторизован', 401);
        
        $this->tokenService->revokeAllUserTokens($userId);
        return $response->success(null, 'Выход со всех устройств выполнен');
    }

    /**
     * Получить URL для Яндекс OAuth
     */
    public function yandexAuth($request, $response)
    {
        $state = bin2hex(random_bytes(16));
        return $response->success([
            'url' => $this->yandexOAuth->getAuthorizationUrl($state),
            'state' => $state
        ]);
    }

    /**
     * Яндекс OAuth callback
     */
    public function yandexCallback($request, $response)
    {
        $code = $request->getQueryParam('code');
        $clientUrl = $_ENV['CLIENT_URL'] ?? 'https://cosmetic.diplom/frontend';
        
        if (empty($code)) {
            header('Location: ' . $clientUrl . '/login.html?error=no_code');
            exit;
        }
        
        $isNewUser = false;
        
        try {
            $tokenData = $this->yandexOAuth->exchangeCodeForToken($code);
            $yandexUser = $this->yandexOAuth->getUserInfo($tokenData['access_token']);
            
            if (empty($yandexUser['email'])) {
                throw new \Exception('Email not provided by Yandex');
            }
            
            $oauthAccount = OAuthAccount::findByProvider('yandex', $yandexUser['id']);
            
            if ($oauthAccount) {
                $user = User::findById((int)$oauthAccount['user_id']);
                if (!$user) throw new \Exception('User not found');
            } else {
                $user = User::findByEmail($yandexUser['email']);
                
                if ($user) {
                    OAuthAccount::create([
                        'user_id' => (int)$user['id'],
                        'provider' => 'yandex',
                        'provider_user_id' => $yandexUser['id'],
                        'access_token' => $tokenData['access_token']
                    ]);
                } else {
                    $isNewUser = true;
                    Database::beginTransaction();
                    
                    $userId = User::create([
                        'email' => $yandexUser['email'],
                        'password' => null,
                        'role' => 'client',
                        'email_verified' => true
                    ]);
                    
                    $fullname = trim(($yandexUser['first_name'] ?? '') . ' ' . ($yandexUser['last_name'] ?? ''));
                    if (empty($fullname)) $fullname = $yandexUser['display_name'] ?? 'Пользователь';
                    
                    Client::create([
                        'user_id' => $userId,
                        'fullname' => $fullname,
                        'phone' => $yandexUser['phone'] ?? null,
                        'communication' => 'phone'
                    ]);
                    
                    OAuthAccount::create([
                        'user_id' => $userId,
                        'provider' => 'yandex',
                        'provider_user_id' => $yandexUser['id'],
                        'access_token' => $tokenData['access_token']
                    ]);
                    
                    Database::commit();
                    $user = User::findById($userId);
                }
            }
            
            User::updateLastLogin((int)$user['id']);
            $tokens = $this->tokenService->generateTokenPair($user, [
                'ip' => $request->getIp(),
                'userAgent' => $request->getUserAgent()
            ]);
            
            $dashboardUrl = ($user['role'] === 'cosmetologist') ? '/cosmetologist/dashboard.php' : '/client/dashboard.php';
            
            header('Location: ' . $clientUrl . $dashboardUrl . '?' . http_build_query([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'is_new_user' => $isNewUser ? '1' : '0'
            ]));
            exit;
            
        } catch (\Exception $e) {
            if ($isNewUser) Database::rollback();
            LoggerService::error('Yandex callback failed: ' . $e->getMessage());
            header('Location: ' . $clientUrl . '/login.html?error=yandex_auth_failed');
            exit;
        }
    }

    /**
     * Привязать Яндекс аккаунт
     */
    public function linkYandex($request, $response)
    {
        $userId = (int)$request->getParam('user_id');
        $data = $request->getBody();
        
        if ($userId === 0) return $response->error('Пользователь не авторизован', 401);
        if (empty($data['code'])) return $response->error('Код авторизации обязателен', 400);
        
        try {
            $tokenData = $this->yandexOAuth->exchangeCodeForToken($data['code']);
            $yandexUser = $this->yandexOAuth->getUserInfo($tokenData['access_token']);
            
            $existing = OAuthAccount::findByProvider('yandex', $yandexUser['id']);
            if ($existing) {
                return $response->error(
                    (int)$existing['user_id'] === $userId 
                        ? 'Этот Яндекс аккаунт уже привязан к вашему профилю'
                        : 'Этот Яндекс аккаунт уже привязан к другому пользователю',
                    409
                );
            }
            
            if (!empty(OAuthAccount::findByUser($userId, 'yandex'))) {
                return $response->error('К вашему профилю уже привязан Яндекс аккаунт', 409);
            }
            
            OAuthAccount::create([
                'user_id' => $userId,
                'provider' => 'yandex',
                'provider_user_id' => $yandexUser['id'],
                'access_token' => $tokenData['access_token']
            ]);
            
            return $response->success(null, 'Яндекс аккаунт успешно привязан');
            
        } catch (\Exception $e) {
            return $response->error('Ошибка привязки аккаунта: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Отвязать Яндекс аккаунт
     */
    public function unlinkYandex($request, $response)
    {
        $userId = (int)$request->getParam('user_id');
        if ($userId === 0) return $response->error('Пользователь не авторизован', 401);
        
        if (empty(OAuthAccount::findByUser($userId, 'yandex'))) {
            return $response->error('Яндекс аккаунт не привязан', 404);
        }
        
        $user = User::findById($userId);
        if ($user && empty($user['password'])) {
            return $response->error('Нельзя отвязать единственный способ входа. Сначала установите пароль.', 400);
        }
        
        OAuthAccount::deleteByUserAndProvider($userId, 'yandex');
        return $response->success(null, 'Яндекс аккаунт отвязан');
    }

    /**
     * Получить привязанные аккаунты
     */
    public function getLinkedAccounts($request, $response)
    {
        $userId = (int)$request->getParam('user_id');
        if ($userId === 0) return $response->error('Пользователь не авторизован', 401);
        
        $accounts = OAuthAccount::findByUser($userId);
        $linked = array_map(function($acc) {
            return ['provider' => $acc['provider'], 'linked_at' => $acc['created']];
        }, $accounts);
        
        return $response->success(['accounts' => $linked]);
    }

    /**
     * Запрос на сброс пароля
     */
    public function forgotPassword($request, $response)
    {
        $data = $request->getBody();
        
        $validator = new Validator($data);
        $validator->required(['email'])->email('email');
        if (!$validator->isValid()) return $response->error($validator->getFirstError(), 400);
        
        $user = User::findByEmail($data['email']);
        if (!$user) return $response->success(null, 'Если email зарегистрирован, на него отправлена инструкция');
        
        $resetToken = $this->tokenService->generateVerificationToken();
        User::updatePasswordResetToken((int)$user['id'], $resetToken);
        
        $name = $user['client_name'] ?? (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if (empty(trim($name))) $name = 'Пользователь';
        
        $this->emailService->sendPasswordResetEmail($data['email'], $resetToken, $name);
        return $response->success(null, 'Инструкция по сбросу пароля отправлена на email');
    }

    /**
     * Сброс пароля
     */
    public function resetPassword($request, $response)
    {
        $data = $request->getBody();
        
        $validator = new Validator($data);
        $validator->required(['token', 'password'])->minLength('password', 8);
        if (!$validator->isValid()) return $response->error($validator->getFirstError(), 400);
        
        if ($data['password'] !== ($data['confirm_password'] ?? '')) {
            return $response->error('Пароли не совпадают', 400);
        }
        
        $user = User::findByPasswordResetToken($data['token']);
        if (!$user) return $response->error('Недействительный или истекший токен', 400);
        
        User::updatePassword((int)$user['id'], $data['password']);
        User::clearPasswordResetToken((int)$user['id']);
        $this->tokenService->revokeAllUserTokens((int)$user['id']);
        
        return $response->success(null, 'Пароль успешно изменен. Теперь вы можете войти.');
    }

    /**
     * Повторная отправка письма подтверждения
     */
    public function resendVerification($request, $response)
    {
        $data = $request->getBody();
        
        $validator = new Validator($data);
        $validator->required(['email'])->email('email');
        if (!$validator->isValid()) return $response->error($validator->getFirstError(), 400);
        
        $user = User::findByEmail($data['email']);
        if (!$user) return $response->error('Пользователь не найден', 404);
        if (!empty($user['email_verified'])) return $response->error('Email уже подтвержден', 400);
        
        $verificationToken = $this->tokenService->generateVerificationToken();
        User::updateVerificationToken((int)$user['id'], $verificationToken);
        
        $name = $user['client_name'] ?? (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if (empty(trim($name))) $name = 'Пользователь';
        
        $this->emailService->sendVerificationEmail($data['email'], $verificationToken, $name);
        return $response->success(null, 'Письмо подтверждения отправлено повторно');
    }
}