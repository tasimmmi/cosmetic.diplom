<?php
require_once __DIR__ . '/config.php';

class ApiClient {
    private static $baseUrl = API_URL;
    
    public static function request($method, $endpoint, $data = null, $token = null) {
        $url = self::$baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }
    
    public static function get($endpoint, $token = null) {
        return self::request('GET', $endpoint, null, $token);
    }
    
    public static function post($endpoint, $data, $token = null) {
        return self::request('POST', $endpoint, $data, $token);
    }
    
    public static function put($endpoint, $data, $token = null) {
        return self::request('PUT', $endpoint, $data, $token);
    }
    
    public static function delete($endpoint, $token = null) {
        return self::request('DELETE', $endpoint, null, $token);
    }
    
    public static function refreshToken($refreshToken) {
        $result = self::post('/auth/refresh', ['refresh_token' => $refreshToken]);
        return $result;
    }
}