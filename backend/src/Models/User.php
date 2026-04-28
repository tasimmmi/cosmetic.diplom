<?php
namespace App\Models;

use App\Config\Database;
use App\Services\LoggerService;

class User
{
    /**
     * Найти пользователя по ID
     */
    public static function findById(int $id): ?array
    {
        $sql = "SELECT u.*, 
                       c.id as client_id,
                       c.fullname as client_name,
                       c.phone as client_phone,
                       c.communication as client_communication,
                       co.id as cosmetologist_id,
                       co.first_name,
                       co.last_name,
                       co.phone as cosmetologist_phone,
                       co.address,
                       co.education,
                       co.about,
                       co.rating
                FROM Users u
                LEFT JOIN Clients c ON u.id = c.user_id
                LEFT JOIN Cosmetologist co ON u.id = co.user_id
                WHERE u.id = ?";
        
        return Database::fetch($sql, [$id], 'i');
    }

    /**
     * Найти пользователя по Email
     */
    public static function findByEmail(string $email): ?array
    {
        $sql = "SELECT u.*, 
                       c.id as client_id,
                       c.fullname as client_name,
                       c.phone as client_phone,
                       c.communication as client_communication,
                       co.id as cosmetologist_id,
                       co.first_name,
                       co.last_name,
                       co.phone as cosmetologist_phone,
                       co.address
                FROM Users u
                LEFT JOIN Clients c ON u.id = c.user_id
                LEFT JOIN Cosmetologist co ON u.id = co.user_id
                WHERE u.email = ?";
        
        return Database::fetch($sql, [$email], 's');
    }

    /**
     * Создать нового пользователя
     */
   public static function create(array $data): int
{
    $email = $data['email'];
    $password = $data['password'] ?? null;
    $role = $data['role'] ?? 'client';
    $emailVerified = $data['email_verified'] ?? false;
    
    $salt = random_bytes(32);
    
    if ($password !== null) {
        $hashedPassword = hash('sha256', $password . bin2hex($salt));
    } else {
        $hashedPassword = '';
    }
    
    $sql = "INSERT INTO Users (email, password, salt, role, email_verified, created) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    Database::execute($sql, [
        $email,
        $hashedPassword,
        $salt, 
        $role,
        (int)$emailVerified
    ], 'ssssi'); 
    
    return Database::lastInsertId();
}

    /**
     * Проверить пароль (SHA256 + соль)
     */
    public static function verifyPassword($user, $password)
{
    if (empty($user['password']) || empty($user['salt'])) {
        LoggerService::warning('Empty password or salt');
        return false;
    }
    
    $salt = is_resource($user['salt']) ? stream_get_contents($user['salt']) : $user['salt'];
    $saltHex = bin2hex($salt);
    $hashedPassword = hash('sha256', $password . $saltHex);
    
    return $hashedPassword === $user['password'];
}

    public static function updateVerificationToken(int $userId, string $token): int
    {
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);
        
        $sql = "UPDATE Users 
                SET verification_token = ?, 
                    verification_token_expires = ? 
                WHERE id = ?";
        
        return Database::execute($sql, [$token, $expiresAt, $userId], 'ssi');
    }

    /**
     * Подтвердить email по токену
     */
    public static function verifyEmail(string $token): bool
    {
        $sql = "UPDATE Users 
                SET email_verified = 1,
                    verification_token = NULL,
                    verification_token_expires = NULL
                WHERE verification_token = ? 
                  AND verification_token_expires > NOW()
                  AND email_verified = 0";
        
        return Database::execute($sql, [$token], 's') > 0;
    }

    /**
     * Обновить время последнего входа
     */
    public static function updateLastLogin(int $userId): int
    {
        $sql = "UPDATE Users SET last_login = NOW() WHERE id = ?";
        return Database::execute($sql, [$userId], 'i');
    }

    /**
     * Обновить пароль
     */
    public static function updatePassword(int $userId, string $newPassword): int
    {
        $salt = random_bytes(32);
        $hashedPassword = hash('sha256', $newPassword . bin2hex($salt));
        
        $sql = "UPDATE Users SET password = ?, salt = ? WHERE id = ?";
        return Database::execute($sql, [$hashedPassword, $salt, $userId], 'sbi');
    }

    /**
     * Проверить, подтвержден ли email
     */
    public static function isEmailVerified(int $userId): bool
    {
        $user = self::findById($userId);
        return $user && !empty($user['email_verified']);
    }

    /**
     * Получить всех пользователей (для админа)
     */
    public static function findAll(?int $limit = null, int $offset = 0): array
    {
        $sql = "SELECT u.*, 
                       c.id as client_id,
                       co.id as cosmetologist_id
                FROM Users u
                LEFT JOIN Clients c ON u.id = c.user_id
                LEFT JOIN Cosmetologist co ON u.id = co.user_id
                ORDER BY u.created DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return Database::fetchAll($sql, [$limit, $offset], 'ii');
        }
        
        return Database::fetchAll($sql);
    }

    /**
     * Обновить роль пользователя
     */
    public static function updateRole(int $userId, string $role): int
    {
        $sql = "UPDATE Users SET role = ? WHERE id = ?";
        return Database::execute($sql, [$role, $userId], 'si');
    }

    /**
     * Удалить пользователя
     */
    public static function delete(int $userId): int
    {
        $sql = "DELETE FROM Users WHERE id = ?";
        return Database::execute($sql, [$userId], 'i');
    }

    // В модель User.php нужно добавить:

    /**
     * Обновить токен сброса пароля
     */
    public static function updatePasswordResetToken(int $userId, string $token): int
    {
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 час
        
        $sql = "UPDATE Users 
                SET password_reset_token = ?, 
                    password_reset_expires = ? 
                WHERE id = ?";
        
        return Database::execute($sql, [$token, $expiresAt, $userId], 'ssi');
    }

    /**
     * Найти пользователя по токену сброса пароля
     */
    public static function findByPasswordResetToken(string $token): ?array
    {
        $sql = "SELECT * FROM Users 
                WHERE password_reset_token = ? 
                AND password_reset_expires > NOW()";
        
        return Database::fetch($sql, [$token], 's');
    }

    /**
     * Очистить токен сброса пароля
     */
    public static function clearPasswordResetToken(int $userId): int
    {
        $sql = "UPDATE Users 
                SET password_reset_token = NULL, 
                    password_reset_expires = NULL 
                WHERE id = ?";
        
        return Database::execute($sql, [$userId], 'i');
    }
}