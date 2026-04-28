<?php
namespace App\Services;

use GuzzleHttp\Client;

class YandexOAuthService
{
    private $httpClient;
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $tokenUrl = 'https://oauth.yandex.ru/token';
    private $userInfoUrl = 'https://login.yandex.ru/info';
    private $authUrl = 'https://oauth.yandex.ru/authorize';

    public function __construct()
    {
        $this->httpClient = new Client(['verify' => false]); // Отключаем проверку SSL для локальной разработки
        
        $this->clientId = isset($_ENV['YANDEX_CLIENT_ID']) ? $_ENV['YANDEX_CLIENT_ID'] : '';
        $this->clientSecret = isset($_ENV['YANDEX_CLIENT_SECRET']) ? $_ENV['YANDEX_CLIENT_SECRET'] : '';
        $this->redirectUri = isset($_ENV['YANDEX_REDIRECT_URI']) ? $_ENV['YANDEX_REDIRECT_URI'] : '';
        
        error_log("YandexOAuthService initialized:");
        error_log("Client ID: " . ($this->clientId ? substr($this->clientId, 0, 10) . '...' : 'EMPTY'));
        error_log("Client Secret: " . ($this->clientSecret ? 'SET' : 'EMPTY'));
        error_log("Redirect URI: " . $this->redirectUri);
    }

    public function getAuthorizationUrl($state)
{
    $params = [
        'response_type' => 'code',
        'client_id' => $this->clientId,
        'redirect_uri' => $this->redirectUri,
        'state' => $state,
        'scope' => 'login:email login:info',
        'force_confirm' => 'true'  // ← ДОБАВИТЬ ЭТУ СТРОКУ
    ];

    $url = $this->authUrl . '?' . http_build_query($params);
    
    return $url;
}

    public function exchangeCodeForToken($code)
    {
        error_log("Exchanging code for token...");
        error_log("Code: " . substr($code, 0, 20) . '...');
        
        try {
            $response = $this->httpClient->post($this->tokenUrl, [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            error_log("Token response: " . json_encode($data));
            
            if (isset($data['error'])) {
                throw new \Exception('Yandex error: ' . $data['error'] . ' - ' . ($data['error_description'] ?? ''));
            }
            
            return [
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'],
                'token_type' => $data['token_type']
            ];
        } catch (\Exception $e) {
            error_log("Token exchange error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getUserInfo($accessToken)
    {
        error_log("Getting user info with token: " . substr($accessToken, 0, 20) . '...');
        
        try {
            $response = $this->httpClient->get($this->userInfoUrl, [
                'headers' => [
                    'Authorization' => 'OAuth ' . $accessToken
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            error_log("User info response: " . json_encode($data));
            
            return [
                'id' => $data['id'],
                'email' => isset($data['default_email']) ? $data['default_email'] : null,
                'first_name' => isset($data['first_name']) ? $data['first_name'] : '',
                'last_name' => isset($data['last_name']) ? $data['last_name'] : '',
                'display_name' => isset($data['display_name']) ? $data['display_name'] : '',
                'phone' => isset($data['default_phone']['number']) ? $data['default_phone']['number'] : null
            ];
        } catch (\Exception $e) {
            error_log("User info error: " . $e->getMessage());
            throw $e;
        }
    }
}