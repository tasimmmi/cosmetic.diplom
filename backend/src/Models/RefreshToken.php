<?php
namespace App\Models;

use App\Config\Database;

class RefreshToken
{
    /**
     * Создать refresh token
     */
    public static function create($data)
    {
        $sql = "INSERT INTO RefreshTokens 
                (user_id, token, expires_at, ip_address, user_agent, created) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        Database::execute($sql, [
            $data['user_id'],
            $data['token'],
            $data['expires_at'],
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null
        ], 'issss');
        
        return Database::lastInsertId();
    }

    /**
     * Найти по токену
     */
    public static function findByToken($token)
    {
        $sql = "SELECT * FROM RefreshTokens WHERE token = ?";
        return Database::fetch($sql, [$token], 's');
    }

    /**
     * Найти все активные токены пользователя
     */
    public static function findByUser($userId)
    {
        $sql = "SELECT * FROM RefreshTokens 
                WHERE user_id = ? AND revoked = FALSE AND expires_at > NOW()
                ORDER BY created DESC";
        
        return Database::fetchAll($sql, [$userId], 'i');
    }

    /**
     * Отозвать токен
     */
    public static function revoke($token)
    {
        $sql = "UPDATE RefreshTokens SET revoked = TRUE WHERE token = ?";
        return Database::execute($sql, [$token], 's');
    }

    /**
     * Отозвать все токены пользователя
     */
    public static function revokeAllForUser($userId)
    {
        $sql = "UPDATE RefreshTokens SET revoked = TRUE WHERE user_id = ? AND revoked = FALSE";
        return Database::execute($sql, [$userId], 'i');
    }

    /**
     * Очистить истекшие токены
     */
    public static function cleanupExpired()
    {
        $sql = "DELETE FROM RefreshTokens WHERE expires_at < NOW() OR revoked = TRUE";
        return Database::execute($sql);
    }
}