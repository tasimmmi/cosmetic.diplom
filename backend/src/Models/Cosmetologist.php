<?php
namespace App\Models;

use App\Config\Database;

class Cosmetologist
{
    /**
     * Получить косметолога по ID
     */
    public static function findById(int $id): ?array
    {
        $sql = "SELECT c.*, u.email, u.role, u.email_verified, u.last_login
                FROM Cosmetologist c 
                JOIN Users u ON c.user_id = u.id 
                WHERE c.id = ?";
        return Database::fetch($sql, [$id], 'i');
    }

    /**
     * Получить косметолога по user_id
     */
    public static function findByUserId(int $userId): ?array
    {
        $sql = "SELECT c.*, u.email, u.role
                FROM Cosmetologist c 
                JOIN Users u ON c.user_id = u.id 
                WHERE c.user_id = ?";
        return Database::fetch($sql, [$userId], 'i');
    }

    /**
     * Создать косметолога
     */
    public static function create(array $data): int
    {
        $sql = "INSERT INTO Cosmetologist (user_id, first_name, last_name, phone, address, education, about) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        Database::execute($sql, [
            $data['user_id'],
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['education'] ?? null,
            $data['about'] ?? null
        ], 'issssss');
        
        return Database::lastInsertId();
    }

    /**
     * Получить всех косметологов
     */
    public static function findAll(?int $limit = null, int $offset = 0): array
    {
        $sql = "SELECT c.*, u.email
                FROM Cosmetologist c 
                JOIN Users u ON c.user_id = u.id
                ORDER BY c.rating DESC, c.first_name ASC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return Database::fetchAll($sql, [$limit, $offset], 'ii');
        }
        
        return Database::fetchAll($sql);
    }

    /**
     * Поиск косметологов
     */
    public static function search(string $query, int $limit = 20): array
    {
        $searchTerm = "%{$query}%";
        $sql = "SELECT c.*, u.email
                FROM Cosmetologist c 
                JOIN Users u ON c.user_id = u.id
                WHERE c.first_name LIKE ? 
                   OR c.last_name LIKE ? 
                   OR c.address LIKE ?
                   OR EXISTS (
                       SELECT 1 FROM Services s 
                       WHERE s.cosmetologist_id = c.id AND s.service LIKE ?
                   )
                ORDER BY c.rating DESC
                LIMIT ?";
        
        return Database::fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit], 'ssssi');
    }

    /**
     * Получить косметолога по slug
     */
    public static function findBySlug(string $slug): ?array
    {
        $sql = "SELECT c.*, u.email
                FROM Cosmetologist c 
                JOIN Users u ON c.user_id = u.id 
                WHERE CONCAT(LOWER(c.first_name), '-', LOWER(c.last_name)) = ?";
        return Database::fetch($sql, [$slug], 's');
    }

    /**
     * Обновить профиль косметолога по ID
     */
    public static function update(int $id, array $data): int
    {
        $sql = "UPDATE Cosmetologist SET 
                first_name = ?, 
                last_name = ?, 
                phone = ?, 
                address = ?,
                education = ?,
                about = ?
                WHERE id = ?";
        
        return Database::execute($sql, [
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['address'] ?? null,
            $data['education'] ?? null,
            $data['about'] ?? null,
            $id
        ], 'ssssssi');
    }

    /**
     * Обновить профиль косметолога по user_id
     */
    public static function updateByUserId(int $userId, array $data): int
    {
        $sql = "UPDATE Cosmetologist SET 
                first_name = ?, 
                last_name = ?, 
                phone = ?, 
                address = ?,
                education = ?,
                about = ?
                WHERE user_id = ?";
        
        return Database::execute($sql, [
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['address'] ?? null,
            $data['education'] ?? null,
            $data['about'] ?? null,
            $userId
        ], 'ssssssi');
    }

    /**
     * Получить услуги косметолога
     */
    public static function getServices(int $cosmetologistId): array
    {
        $sql = "SELECT * FROM Services WHERE cosmetologist_id = ? ORDER BY service";
        return Database::fetchAll($sql, [$cosmetologistId], 'i');
    }

    /**
     * Получить доступные слоты
     */
    public static function getAvailableSlots(int $cosmetologistId, ?string $date = null): array
    {
        $sql = "SELECT * FROM v_available_time 
                WHERE cosmetologist_id = ?";
        
        $params = [$cosmetologistId];
        $types = 'i';
        
        if ($date) {
            $sql .= " AND DATE(begin_time) = ?";
            $params[] = $date;
            $types .= 's';
        }
        
        $sql .= " ORDER BY begin_time";
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Получить имя косметолога для шапки
     */
    public static function getHeaderName(int $cosmetologistId): ?array
    {
        $sql = "SELECT first_name, last_name FROM Cosmetologist WHERE id = ?";
        return Database::fetch($sql, [$cosmetologistId], 'i');
    }

    /**
     * Удалить косметолога
     */
    public static function delete(int $id): int
    {
        $sql = "DELETE FROM Cosmetologist WHERE id = ?";
        return Database::execute($sql, [$id], 'i');
    }
}