<?php
namespace App\Models;

use App\Config\Database;

class Cosmetologist
{
    public static function findById(int $id): ?array
    {
        $sql = "SELECT c.*, u.email, u.role, u.email_verified, u.last_login
                FROM Cosmetologist c 
                JOIN Users u ON c.user_id = u.id 
                WHERE c.id = ?";
        return Database::fetch($sql, [$id], 'i');
    }

    public static function findByUserId(int $userId): ?array
    {
        $sql = "SELECT c.*, u.email, u.role
                FROM Cosmetologist c 
                JOIN Users u ON c.user_id = u.id 
                WHERE c.user_id = ?";
        return Database::fetch($sql, [$userId], 'i');
    }

    public static function create(array $data): int
    {
        $sql = "INSERT INTO Cosmetologist (user_id, first_name, last_name, phone, address) 
                VALUES (?, ?, ?, ?, ?)";
        
        Database::execute($sql, [
            $data['user_id'],
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['address'] ?? null
        ], 'issss');
        
        return Database::lastInsertId();
    }

    public static function findAll(): array
    {
        $sql = "SELECT c.*, u.email
                FROM Cosmetologist c 
                JOIN Users u ON c.user_id = u.id
                ORDER BY c.first_name ASC";
        
        return Database::fetchAll($sql);
    }

    public static function search(string $query, int $limit = 20): array
    {
        $searchTerm = "%{$query}%";
        $sql = "SELECT c.*, u.email
                FROM Cosmetologist c 
                JOIN Users u ON c.user_id = u.id
                WHERE c.first_name LIKE ? 
                   OR c.last_name LIKE ? 
                   OR c.address LIKE ?
                LIMIT ?";
        
        return Database::fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $limit], 'sssi');
    }

    public static function update(int $id, array $data): int
    {
        $sql = "UPDATE Cosmetologist SET 
                first_name = ?, 
                last_name = ?, 
                phone = ?, 
                address = ?
                WHERE id = ?";
        
        return Database::execute($sql, [
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['address'] ?? null,
            $id
        ], 'ssssi');
    }

    public static function updateByUserId(int $userId, array $data): int
    {
        $sql = "UPDATE Cosmetologist SET 
                first_name = ?, 
                last_name = ?, 
                phone = ?, 
                address = ?
                WHERE user_id = ?";
        
        return Database::execute($sql, [
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['address'] ?? null,
            $userId
        ], 'ssssi');
    }

    public static function getServices(int $cosmetologistId): array
    {
        $sql = "SELECT * FROM Services WHERE cosmetologist_id = ? ORDER BY service";
        return Database::fetchAll($sql, [$cosmetologistId], 'i');
    }

    public static function getAvailableSlots(int $cosmetologistId, ?string $date = null): array
    {
        $sql = "SELECT * FROM Schedule WHERE cosmetologist_id = ? AND is_booked = 0";
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

    public static function getHeaderName(int $cosmetologistId): ?array
    {
        $sql = "SELECT first_name, last_name FROM Cosmetologist WHERE id = ?";
        return Database::fetch($sql, [$cosmetologistId], 'i');
    }

    public static function delete(int $id): int
    {
        $sql = "DELETE FROM Cosmetologist WHERE id = ?";
        return Database::execute($sql, [$id], 'i');
    }

    public static function updateAvatar(int $id, $avatarData): int
    {
        $sql = "UPDATE Cosmetologist SET avatar = ? WHERE id = ?";
        return Database::execute($sql, [$avatarData, $id], 'bi');
    }
}