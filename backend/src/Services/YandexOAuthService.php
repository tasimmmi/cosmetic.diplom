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
        $this->httpClient = new Client(['verify' => false]);
        $this->clientId = $_ENV['YANDEX_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['YANDEX_CLIENT_SECRET'] ?? '';
        $this->redirectUri = $_ENV['YANDEX_REDIRECT_URI'] ?? '';
    }

    public function getAuthorizationUrl($state)
    {
        return $this->authUrl . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'scope' => 'login:email login:info',
            'force_confirm' => 'true'
        ]);
    }

    public function exchangeCodeForToken($code)
    {
        $response = $this->httpClient->post($this->tokenUrl, [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        
        if (isset($data['error'])) {
            throw new \Exception('Yandex error: ' . $data['error'] . ' - ' . ($data['error_description'] ?? ''));
        }
        
        return [
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'],
            'token_type' => $data['token_type']
        ];
    }

    public function getUserInfo($accessToken)
    {
        $response = $this->httpClient->get($this->userInfoUrl, [
            'headers' => ['Authorization' => 'OAuth ' . $accessToken]
        ]);

        $data = json_decode($response->getBody(), true);
        
        return [
            'id' => $data['id'],
            'email' => $data['default_email'] ?? null,
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'display_name' => $data['display_name'] ?? '',
            'phone' => $data['default_phone']['number'] ?? null
        ];
    }
}