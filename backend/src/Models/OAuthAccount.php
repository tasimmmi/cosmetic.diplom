<?php
namespace App\Models;

use App\Config\Database;

class OAuthAccount
{
    /**
     * Создать связь OAuth
     */
    public static function create($data)
    {
        $sql = "INSERT INTO OAuthAccounts 
                (user_id, provider, provider_user_id, access_token, refresh_token, created) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        Database::execute($sql, [
            $data['user_id'],
            $data['provider'],
            $data['provider_user_id'],
            $data['access_token'],
            $data['refresh_token'] ?? null
        ], 'issss');
        
        return Database::lastInsertId();
    }

    /**
     * Найти по провайдеру и ID пользователя провайдера
     */
    public static function findByProvider($provider, $providerUserId)
    {
        $sql = "SELECT * FROM OAuthAccounts 
                WHERE provider = ? AND provider_user_id = ?";
        
        return Database::fetch($sql, [$provider, $providerUserId], 'ss');
    }

    /**
     * Найти все связи пользователя
     */
    public static function findByUser($userId, $provider = null)
    {
        if ($provider) {
            $sql = "SELECT * FROM OAuthAccounts WHERE user_id = ? AND provider = ?";
            return Database::fetch($sql, [$userId, $provider], 'is');
        }
        
        $sql = "SELECT * FROM OAuthAccounts WHERE user_id = ?";
        return Database::fetchAll($sql, [$userId], 'i');
    }

    /**
     * Обновить токены
     */
    public static function updateTokens($id, $accessToken, $refreshToken = null)
    {
        if ($refreshToken) {
            $sql = "UPDATE OAuthAccounts 
                    SET access_token = ?, refresh_token = ?, updated = NOW() 
                    WHERE id = ?";
            return Database::execute($sql, [$accessToken, $refreshToken, $id], 'ssi');
        }
        
        $sql = "UPDATE OAuthAccounts 
                SET access_token = ?, updated = NOW() 
                WHERE id = ?";
        return Database::execute($sql, [$accessToken, $id], 'si');
    }

    /**
     * Удалить связь
     */
    public static function delete($id)
    {
        $sql = "DELETE FROM OAuthAccounts WHERE id = ?";
        return Database::execute($sql, [$id], 'i');
    }

    /**
     * Удалить связь по пользователю и провайдеру
     */
    public static function deleteByUserAndProvider($userId, $provider)
    {
        $sql = "DELETE FROM OAuthAccounts WHERE user_id = ? AND provider = ?";
        return Database::execute($sql, [$userId, $provider], 'is');
    }
}