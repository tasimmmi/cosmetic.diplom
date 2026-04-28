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
        
        LoggerService::info('AuthController initialized');
    }

    /**
     * Регистрация нового пользователя
     * POST /api/auth/register
     */
    public function register($request, $response)
    {
        LoggerService::info('=== REGISTER START ===');
        
        $data = $request->getBody();
        LoggerService::info('Register request data', ['email' => $data['email'] ?? 'not provided', 'role' => $data['role'] ?? 'not provided']);
        
        $validator = new Validator($data);
        $validator->required(['email', 'password', 'fullname', 'phone', 'role'])
                  ->email('email')
                  ->minLength('password', 8)
                  ->in('role', ['client', 'cosmetologist']);
        
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            $firstError = $validator->getFirstError();
            if ($firstError === null) {
                $firstError = 'Validation failed';
            }
            LoggerService::warning('Register validation failed', ['errors' => $errors]);
            return $response->error($firstError, 400, $errors);
        }
        
        LoggerService::info('Register validation passed');
        
        $existingUser = User::findByEmail($data['email']);
        if ($existingUser !== null) {
            LoggerService::warning('Register failed - user already exists', ['email' => $data['email']]);
            return $response->error('Пользователь с таким email уже существует', 409);
        }
        
        try {
            LoggerService::info('Starting transaction for user creation');
            Database::beginTransaction();
            
            LoggerService::info('Creating user record');
            $userId = User::create([
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'email_verified' => false
            ]);
            
            if ($userId === 0) {
                throw new \Exception('Не удалось создать пользователя');
            }
            LoggerService::info('User created', ['user_id' => $userId]);
            
            if ($data['role'] === 'client') {
                LoggerService::info('Creating client profile');
                $profileId = Client::create([
                    'user_id' => $userId,
                    'fullname' => $data['fullname'],
                    'phone' => $data['phone']
                ]);
                
                if ($profileId === 0) {
                    throw new \Exception('Не удалось создать профиль клиента');
                }
                LoggerService::info('Client profile created', ['client_id' => $profileId]);
            } else {
                LoggerService::info('Creating cosmetologist profile');
                
                if (empty($data['first_name']) || empty($data['last_name'])) {
                    throw new \Exception('Имя и фамилия обязательны для косметолога');
                }
                
                $profileId = Cosmetologist::create([
                    'user_id' => $userId,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'address' => isset($data['address']) ? $data['address'] : null
                ]);
                
                if ($profileId === 0) {
                    throw new \Exception('Не удалось создать профиль косметолога');
                }
                LoggerService::info('Cosmetologist profile created', ['cosmetologist_id' => $profileId]);
            }
            
            LoggerService::info('Generating verification token');
            $verificationToken = $this->tokenService->generateVerificationToken();
            User::updateVerificationToken($userId, $verificationToken);
            LoggerService::info('Verification token saved', ['token' => substr($verificationToken, 0, 10) . '...']);
            
            Database::commit();
            LoggerService::info('Transaction committed');
            
            $name = isset($data['fullname']) ? $data['fullname'] : ($data['first_name'] . ' ' . $data['last_name']);
            LoggerService::info('Sending verification email', ['email' => $data['email']]);
            $this->emailService->sendVerificationEmail($data['email'], $verificationToken, $name);
            LoggerService::info('Verification email sent');
            
            LoggerService::info('=== REGISTER SUCCESS ===', ['user_id' => $userId]);
            
            return $response->success([
                'user_id' => $userId,
                'message' => 'Регистрация успешна. Проверьте email для подтверждения.'
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            LoggerService::error('=== REGISTER FAILED ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => isset($data['email']) ? $data['email'] : 'unknown'
            ]);
            return $response->error($e->getMessage(), 500);
        }
    }

    /**
     * Вход в систему
     * POST /api/auth/login
     */
    public function login($request, $response)
    {
        LoggerService::info('=== LOGIN START ===');
        
        $data = $request->getBody();
        LoggerService::info('Login attempt', ['email' => $data['email'] ?? 'not provided']);
        
        $validator = new Validator($data);
        $validator->required(['email', 'password'])->email('email');
        
        if (!$validator->isValid()) {
            $firstError = $validator->getFirstError();
            if ($firstError === null) {
                $firstError = 'Validation failed';
            }
            LoggerService::warning('Login validation failed', ['error' => $firstError]);
            return $response->error($firstError, 400);
        }
        
        $user = User::findByEmail($data['email']);
        
        if ($user === null) {
            LoggerService::warning('Login failed - user not found', ['email' => $data['email'], 'ip' => $request->getIp()]);
            return $response->error('Неверный email или пароль', 401);
        }
        
        if (!User::verifyPassword($user, $data['password'])) {
            LoggerService::warning('Login failed - invalid password', ['email' => $data['email'], 'ip' => $request->getIp()]);
            return $response->error('Неверный email или пароль', 401);
        }
        
        if (empty($user['email_verified'])) {
            LoggerService::warning('Login failed - email not verified', ['email' => $data['email'], 'user_id' => $user['id']]);
            return $response->error('Пожалуйста, подтвердите email перед входом', 403);
        }
        
        LoggerService::info('Updating last login', ['user_id' => $user['id']]);
        User::updateLastLogin((int)$user['id']);
        
        LoggerService::info('Generating tokens', ['user_id' => $user['id']]);
        $tokens = $this->tokenService->generateTokenPair($user, [
            'ip' => $request->getIp(),
            'userAgent' => $request->getUserAgent()
        ]);
        LoggerService::info('Tokens generated', ['expires_in' => $tokens['expires_in'] ?? 900]);
        
        $userData = [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'client_id' => isset($user['client_id']) ? (int)$user['client_id'] : null,
            'cosmetologist_id' => isset($user['cosmetologist_id']) ? (int)$user['cosmetologist_id'] : null
        ];
        
        if ($user['role'] === 'client') {
            $userData['fullname'] = isset($user['client_name']) ? $user['client_name'] : '';
            $userData['phone'] = isset($user['client_phone']) ? $user['client_phone'] : '';
        } elseif ($user['role'] === 'cosmetologist') {
            $firstName = isset($user['first_name']) ? $user['first_name'] : '';
            $lastName = isset($user['last_name']) ? $user['last_name'] : '';
            $userData['fullname'] = trim($firstName . ' ' . $lastName);
            $userData['phone'] = isset($user['cosmetologist_phone']) ? $user['cosmetologist_phone'] : '';
        }
        
        LoggerService::info('=== LOGIN SUCCESS ===', ['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);
        
        return $response->success([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => isset($tokens['expires_in']) ? $tokens['expires_in'] : 900,
            'user' => $userData
        ]);
    }

    /**
     * Подтверждение email
     * GET /api/auth/verify-email
     */
    public function verifyEmail($request, $response)
    {
        LoggerService::info('=== VERIFY EMAIL START ===');
        
        $token = $request->getQueryParam('token');
        $clientUrl = isset($_ENV['CLIENT_URL']) ? $_ENV['CLIENT_URL'] : 'http://localhost:3000';
        
        LoggerService::info('Verify email request', ['token' => $token ? substr($token, 0, 10) . '...' : 'empty']);
        
        if (empty($token)) {
            LoggerService::warning('Verify email failed - no token');
            header('Location: ' . $clientUrl . '/login.html?error=missing_token');
            exit;
        }
        
        $verified = User::verifyEmail($token);
        
        if (!$verified) {
            LoggerService::warning('Verify email failed - invalid token', ['token' => substr($token, 0, 10) . '...']);
            header('Location: ' . $clientUrl . '/login.html?error=invalid_token');
            exit;
        }
        
        LoggerService::info('=== VERIFY EMAIL SUCCESS ===', ['token' => substr($token, 0, 10) . '...']);
        header('Location: ' . $clientUrl . '/login.html?verified=true');
        exit;
    }

    /**
     * Обновление токена
     * POST /api/auth/refresh
     */
    public function refreshToken($request, $response)
    {
        LoggerService::info('=== REFRESH TOKEN START ===');
        
        $data = $request->getBody();
        
        if (empty($data['refresh_token'])) {
            LoggerService::warning('Refresh token failed - no token provided');
            return $response->error('Refresh token обязателен', 400);
        }
        
        LoggerService::info('Attempting to refresh token', ['token' => substr($data['refresh_token'], 0, 10) . '...']);
        
        try {
            $tokens = $this->tokenService->refreshAccessToken($data['refresh_token']);
            LoggerService::info('=== REFRESH TOKEN SUCCESS ===');
            return $response->success($tokens);
        } catch (\Exception $e) {
            LoggerService::warning('Refresh token failed', ['error' => $e->getMessage()]);
            return $response->error($e->getMessage(), 401);
        }
    }

    /**
     * Выход из системы
     * POST /api/auth/logout
     */
    public function logout($request, $response)
    {
        LoggerService::info('=== LOGOUT START ===');
        
        $data = $request->getBody();
        
        if (!empty($data['refresh_token'])) {
            LoggerService::info('Revoking refresh token', ['token' => substr($data['refresh_token'], 0, 10) . '...']);
            $this->tokenService->revokeRefreshToken($data['refresh_token']);
        }
        
        LoggerService::info('=== LOGOUT SUCCESS ===');
        return $response->success(null, 'Выход выполнен успешно');
    }

    /**
     * Выход со всех устройств
     * POST /api/auth/logout-all
     */
    public function logoutAll($request, $response)
    {
        LoggerService::info('=== LOGOUT ALL START ===');
        
        $userId = (int)$request->getParam('user_id');
        
        if ($userId === 0) {
            LoggerService::warning('Logout all failed - no user_id');
            return $response->error('Пользователь не авторизован', 401);
        }
        
        LoggerService::info('Revoking all tokens for user', ['user_id' => $userId]);
        $this->tokenService->revokeAllUserTokens($userId);
        
        LoggerService::info('=== LOGOUT ALL SUCCESS ===', ['user_id' => $userId]);
        return $response->success(null, 'Выход со всех устройств выполнен');
    }

    /**
     * Получить URL для Яндекс OAuth
     * GET /api/auth/yandex
     */
    public function yandexAuth($request, $response)
    {
        LoggerService::info('=== YANDEX AUTH START ===');
        
        $state = bin2hex(random_bytes(16));
        $authUrl = $this->yandexOAuth->getAuthorizationUrl($state);
        
        LoggerService::info('Yandex auth URL generated', ['state' => $state]);
        
        return $response->success([
            'url' => $authUrl,
            'state' => $state
        ]);
    }

    /**
     * Яндекс OAuth callback
     * GET /api/auth/yandex/callback
     */
    public function yandexCallback($request, $response)
    {
        LoggerService::info('=== YANDEX CALLBACK START ===');
        
        $code = $request->getQueryParam('code');
        $state = $request->getQueryParam('state');
        $clientUrl = isset($_ENV['CLIENT_URL']) ? $_ENV['CLIENT_URL'] : 'https://cosmetic.diplom/frontend';
        
        LoggerService::info('Yandex callback params', [
            'code' => $code ? substr($code, 0, 20) . '...' : 'EMPTY',
            'state' => $state ? $state : 'EMPTY',
            'client_url' => $clientUrl
        ]);
        
        if (empty($code)) {
            LoggerService::error('Yandex callback - no code provided');
            header('Location: ' . $clientUrl . '/login.html?error=no_code');
            exit;
        }
        
        $isNewUser = false;
        
        try {
            LoggerService::info('Step 1: Exchanging code for token');
            $tokenData = $this->yandexOAuth->exchangeCodeForToken($code);
            LoggerService::info('Step 1 SUCCESS: Token received', ['access_token' => substr($tokenData['access_token'], 0, 20) . '...']);
            
            LoggerService::info('Step 2: Getting user info from Yandex');
            $yandexUser = $this->yandexOAuth->getUserInfo($tokenData['access_token']);
            LoggerService::info('Step 2 SUCCESS: User info received', [
                'yandex_id' => $yandexUser['id'],
                'email' => $yandexUser['email'] ?? 'not provided'
            ]);
            
            if (empty($yandexUser['email'])) {
                throw new \Exception('Email not provided by Yandex');
            }
            
            LoggerService::info('Step 3: Checking OAuth account', ['yandex_id' => $yandexUser['id']]);
            $oauthAccount = OAuthAccount::findByProvider('yandex', $yandexUser['id']);
            
            if ($oauthAccount !== null) {
                LoggerService::info('Step 3: Existing OAuth account found', ['user_id' => $oauthAccount['user_id']]);
                $user = User::findById((int)$oauthAccount['user_id']);
                if ($user === null) {
                    throw new \Exception('User not found for existing OAuth account');
                }
                LoggerService::info('Step 3 SUCCESS: Existing user found', ['user_id' => $user['id']]);
            } else {
                LoggerService::info('Step 3: No OAuth account, checking email', ['email' => $yandexUser['email']]);
                $user = User::findByEmail($yandexUser['email']);
                
                if ($user !== null) {
                    LoggerService::info('Step 3: Existing user found by email, linking Yandex', ['user_id' => $user['id']]);
                    OAuthAccount::create([
                        'user_id' => (int)$user['id'],
                        'provider' => 'yandex',
                        'provider_user_id' => $yandexUser['id'],
                        'access_token' => $tokenData['access_token']
                    ]);
                    LoggerService::info('Step 3 SUCCESS: Yandex linked to existing user');
                } else {
                    LoggerService::info('Step 3: Creating new user');
                    $isNewUser = true;
                    
                    Database::beginTransaction();
                    LoggerService::info('Step 4: Transaction started');
                    
                    $userId = User::create([
                        'email' => $yandexUser['email'],
                        'password' => null,
                        'role' => 'client',
                        'email_verified' => true
                    ]);
                    
                    if ($userId === 0) {
                        throw new \Exception('Failed to create user');
                    }
                    LoggerService::info('Step 4 SUCCESS: User created', ['user_id' => $userId]);
                    
                    $fullname = trim(
                        (isset($yandexUser['first_name']) ? $yandexUser['first_name'] : '') . ' ' . 
                        (isset($yandexUser['last_name']) ? $yandexUser['last_name'] : '')
                    );
                    if (empty($fullname)) {
                        $fullname = isset($yandexUser['display_name']) ? $yandexUser['display_name'] : 'Пользователь';
                    }
                    LoggerService::info('Fullname prepared', ['fullname' => $fullname]);
                    
                    $clientId = Client::create([
                        'user_id' => $userId,
                        'fullname' => $fullname,
                        'phone' => isset($yandexUser['phone']) ? $yandexUser['phone'] : null,
                        'communication' => 'phone'
                    ]);
                    
                    if ($clientId === 0) {
                        throw new \Exception('Failed to create client profile');
                    }
                    LoggerService::info('Step 5 SUCCESS: Client profile created', ['client_id' => $clientId]);
                    
                    OAuthAccount::create([
                        'user_id' => $userId,
                        'provider' => 'yandex',
                        'provider_user_id' => $yandexUser['id'],
                        'access_token' => $tokenData['access_token']
                    ]);
                    LoggerService::info('Step 6 SUCCESS: OAuth account created');
                    
                    Database::commit();
                    LoggerService::info('Step 7: Transaction committed');
                    
                    $user = User::findById($userId);
                    if ($user === null) {
                        throw new \Exception('Failed to retrieve created user');
                    }
                    LoggerService::info('Step 7 SUCCESS: User retrieved', ['user_id' => $user['id']]);
                }
            }
            
            LoggerService::info('Step 8: Updating last login', ['user_id' => $user['id']]);
            User::updateLastLogin((int)$user['id']);
            
            LoggerService::info('Step 9: Generating tokens', ['user_id' => $user['id']]);
            $tokens = $this->tokenService->generateTokenPair($user, [
                'ip' => $request->getIp(),
                'userAgent' => $request->getUserAgent()
            ]);
            LoggerService::info('Step 9 SUCCESS: Tokens generated');
            
            $role = isset($user['role']) ? $user['role'] : 'client';
            $dashboardUrl = $role === 'cosmetologist' 
                ? '/dashboard-cosmetologist.html' 
                : '/dashboard-client.html';
            
            $redirectUrl = $clientUrl . $dashboardUrl . '?' . http_build_query([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'is_new_user' => $isNewUser ? '1' : '0'
            ]);
            
            LoggerService::info('=== YANDEX CALLBACK SUCCESS ===', [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'is_new_user' => $isNewUser,
                'redirect' => $redirectUrl
            ]);
            
            header('Location: ' . $redirectUrl);
            exit;
            
        } catch (\Exception $e) {
            LoggerService::error('=== YANDEX CALLBACK ERROR ===', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($isNewUser) {
                Database::rollback();
                LoggerService::info('Transaction rolled back');
            }
            
            header('Location: ' . $clientUrl . '/login.html?error=yandex_auth_failed&message=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Привязать Яндекс аккаунт
     * POST /api/auth/yandex/link
     */
    public function linkYandex($request, $response)
    {
        LoggerService::info('=== LINK YANDEX START ===');
        
        $userId = (int)$request->getParam('user_id');
        $data = $request->getBody();
        
        LoggerService::info('Link Yandex request', ['user_id' => $userId]);
        
        if ($userId === 0) {
            LoggerService::warning('Link Yandex failed - user not authorized');
            return $response->error('Пользователь не авторизован', 401);
        }
        
        if (empty($data['code'])) {
            LoggerService::warning('Link Yandex failed - no code provided');
            return $response->error('Код авторизации обязателен', 400);
        }
        
        try {
            LoggerService::info('Exchanging code for token');
            $tokenData = $this->yandexOAuth->exchangeCodeForToken($data['code']);
            $yandexUser = $this->yandexOAuth->getUserInfo($tokenData['access_token']);
            
            LoggerService::info('Checking existing Yandex link', ['yandex_id' => $yandexUser['id']]);
            $existing = OAuthAccount::findByProvider('yandex', $yandexUser['id']);
            if ($existing !== null) {
                if ((int)$existing['user_id'] === $userId) {
                    LoggerService::warning('Link Yandex failed - already linked to this user');
                    return $response->error('Этот Яндекс аккаунт уже привязан к вашему профилю', 409);
                }
                LoggerService::warning('Link Yandex failed - already linked to another user');
                return $response->error('Этот Яндекс аккаунт уже привязан к другому пользователю', 409);
            }
            
            $userAccounts = OAuthAccount::findByUser($userId, 'yandex');
            if (!empty($userAccounts)) {
                LoggerService::warning('Link Yandex failed - user already has Yandex linked');
                return $response->error('К вашему профилю уже привязан Яндекс аккаунт', 409);
            }
            
            OAuthAccount::create([
                'user_id' => $userId,
                'provider' => 'yandex',
                'provider_user_id' => $yandexUser['id'],
                'access_token' => $tokenData['access_token']
            ]);
            
            LoggerService::info('=== LINK YANDEX SUCCESS ===', ['user_id' => $userId, 'yandex_id' => $yandexUser['id']]);
            return $response->success(null, 'Яндекс аккаунт успешно привязан');
            
        } catch (\Exception $e) {
            LoggerService::error('=== LINK YANDEX ERROR ===', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $response->error('Ошибка привязки аккаунта: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Отвязать Яндекс аккаунт
     * DELETE /api/auth/yandex/unlink
     */
    public function unlinkYandex($request, $response)
    {
        LoggerService::info('=== UNLINK YANDEX START ===');
        
        $userId = (int)$request->getParam('user_id');
        LoggerService::info('Unlink Yandex request', ['user_id' => $userId]);
        
        if ($userId === 0) {
            LoggerService::warning('Unlink Yandex failed - user not authorized');
            return $response->error('Пользователь не авторизован', 401);
        }
        
        $accounts = OAuthAccount::findByUser($userId, 'yandex');
        if (empty($accounts)) {
            LoggerService::warning('Unlink Yandex failed - no Yandex account linked');
            return $response->error('Яндекс аккаунт не привязан', 404);
        }
        
        $user = User::findById($userId);
        if ($user !== null && empty($user['password'])) {
            LoggerService::warning('Unlink Yandex failed - no password set');
            return $response->error('Нельзя отвязать единственный способ входа. Сначала установите пароль.', 400);
        }
        
        OAuthAccount::deleteByUserAndProvider($userId, 'yandex');
        
        LoggerService::info('=== UNLINK YANDEX SUCCESS ===', ['user_id' => $userId]);
        return $response->success(null, 'Яндекс аккаунт отвязан');
    }

    /**
     * Получить привязанные аккаунты
     * GET /api/auth/linked-accounts
     */
    public function getLinkedAccounts($request, $response)
    {
        LoggerService::info('=== GET LINKED ACCOUNTS START ===');
        
        $userId = (int)$request->getParam('user_id');
        LoggerService::info('Get linked accounts request', ['user_id' => $userId]);
        
        if ($userId === 0) {
            LoggerService::warning('Get linked accounts failed - user not authorized');
            return $response->error('Пользователь не авторизован', 401);
        }
        
        $accounts = OAuthAccount::findByUser($userId);
        
        $linked = [];
        foreach ($accounts as $acc) {
            $linked[] = [
                'provider' => $acc['provider'],
                'linked_at' => $acc['created']
            ];
        }
        
        LoggerService::info('=== GET LINKED ACCOUNTS SUCCESS ===', ['user_id' => $userId, 'count' => count($linked)]);
        return $response->success(['accounts' => $linked]);
    }

    /**
     * Запрос на сброс пароля
     * POST /api/auth/forgot-password
     */
    public function forgotPassword($request, $response)
    {
        LoggerService::info('=== FORGOT PASSWORD START ===');
        
        $data = $request->getBody();
        LoggerService::info('Forgot password request', ['email' => $data['email'] ?? 'not provided']);
        
        $validator = new Validator($data);
        $validator->required(['email'])->email('email');
        
        if (!$validator->isValid()) {
            $firstError = $validator->getFirstError();
            if ($firstError === null) {
                $firstError = 'Validation failed';
            }
            LoggerService::warning('Forgot password validation failed', ['error' => $firstError]);
            return $response->error($firstError, 400);
        }
        
        $user = User::findByEmail($data['email']);
        
        if ($user === null) {
            LoggerService::info('Forgot password - user not found, but returning success for security');
            return $response->success(null, 'Если email зарегистрирован, на него отправлена инструкция');
        }
        
        LoggerService::info('Generating password reset token', ['user_id' => $user['id']]);
        $resetToken = $this->tokenService->generateVerificationToken();
        User::updatePasswordResetToken((int)$user['id'], $resetToken);
        
        $name = 'Пользователь';
        if (isset($user['client_name'])) {
            $name = $user['client_name'];
        } elseif (isset($user['first_name']) && isset($user['last_name'])) {
            $name = $user['first_name'] . ' ' . $user['last_name'];
        }
        
        LoggerService::info('Sending password reset email', ['email' => $data['email']]);
        $this->emailService->sendPasswordResetEmail($data['email'], $resetToken, $name);
        
        LoggerService::info('=== FORGOT PASSWORD SUCCESS ===', ['user_id' => $user['id']]);
        return $response->success(null, 'Инструкция по сбросу пароля отправлена на email');
    }

    /**
     * Сброс пароля
     * POST /api/auth/reset-password
     */
    public function resetPassword($request, $response)
    {
        LoggerService::info('=== RESET PASSWORD START ===');
        
        $data = $request->getBody();
        LoggerService::info('Reset password request', ['token' => isset($data['token']) ? substr($data['token'], 0, 10) . '...' : 'empty']);
        
        $validator = new Validator($data);
        $validator->required(['token', 'password'])
                  ->minLength('password', 8);
        
        if (!$validator->isValid()) {
            $firstError = $validator->getFirstError();
            if ($firstError === null) {
                $firstError = 'Validation failed';
            }
            LoggerService::warning('Reset password validation failed', ['error' => $firstError]);
            return $response->error($firstError, 400);
        }
        
        if ($data['password'] !== (isset($data['confirm_password']) ? $data['confirm_password'] : '')) {
            LoggerService::warning('Reset password failed - passwords do not match');
            return $response->error('Пароли не совпадают', 400);
        }
        
        $user = User::findByPasswordResetToken($data['token']);
        
        if ($user === null) {
            LoggerService::warning('Reset password failed - invalid token', ['token' => substr($data['token'], 0, 10) . '...']);
            return $response->error('Недействительный или истекший токен', 400);
        }
        
        LoggerService::info('Updating password', ['user_id' => $user['id']]);
        User::updatePassword((int)$user['id'], $data['password']);
        User::clearPasswordResetToken((int)$user['id']);
        
        LoggerService::info('Revoking all tokens for security', ['user_id' => $user['id']]);
        $this->tokenService->revokeAllUserTokens((int)$user['id']);
        
        LoggerService::info('=== RESET PASSWORD SUCCESS ===', ['user_id' => $user['id']]);
        return $response->success(null, 'Пароль успешно изменен. Теперь вы можете войти.');
    }

    /**
     * Повторная отправка письма подтверждения
     * POST /api/auth/resend-verification
     */
    public function resendVerification($request, $response)
    {
        LoggerService::info('=== RESEND VERIFICATION START ===');
        
        $data = $request->getBody();
        LoggerService::info('Resend verification request', ['email' => $data['email'] ?? 'not provided']);
        
        $validator = new Validator($data);
        $validator->required(['email'])->email('email');
        
        if (!$validator->isValid()) {
            $firstError = $validator->getFirstError();
            if ($firstError === null) {
                $firstError = 'Validation failed';
            }
            LoggerService::warning('Resend verification validation failed', ['error' => $firstError]);
            return $response->error($firstError, 400);
        }
        
        $user = User::findByEmail($data['email']);
        
        if ($user === null) {
            LoggerService::warning('Resend verification failed - user not found', ['email' => $data['email']]);
            return $response->error('Пользователь не найден', 404);
        }
        
        if (!empty($user['email_verified'])) {
            LoggerService::warning('Resend verification failed - email already verified', ['user_id' => $user['id']]);
            return $response->error('Email уже подтвержден', 400);
        }
        
        LoggerService::info('Generating new verification token', ['user_id' => $user['id']]);
        $verificationToken = $this->tokenService->generateVerificationToken();
        User::updateVerificationToken((int)$user['id'], $verificationToken);
        
        $name = 'Пользователь';
        if (isset($user['client_name'])) {
            $name = $user['client_name'];
        } elseif (isset($user['first_name']) && isset($user['last_name'])) {
            $name = $user['first_name'] . ' ' . $user['last_name'];
        }
        
        LoggerService::info('Sending verification email', ['email' => $data['email']]);
        $this->emailService->sendVerificationEmail($data['email'], $verificationToken, $name);
        
        LoggerService::info('=== RESEND VERIFICATION SUCCESS ===', ['user_id' => $user['id']]);
        return $response->success(null, 'Письмо подтверждения отправлено повторно');
    }
}