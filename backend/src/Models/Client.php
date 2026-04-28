<?php
namespace App\Models;

use App\Config\Database;

class Client
{
    /**
     * Получить клиента по ID
     */
    public static function findById(int $id): ?array
    {
        $sql = "SELECT c.*, u.email, u.role, u.email_verified, u.last_login
                FROM Clients c 
                JOIN Users u ON c.user_id = u.id 
                WHERE c.id = ?";
        return Database::fetch($sql, [$id], 'i');
    }

    /**
     * Получить клиента по user_id
     */
    public static function findByUserId(int $userId): ?array
    {
        $sql = "SELECT c.*, u.email, u.role
                FROM Clients c 
                JOIN Users u ON c.user_id = u.id 
                WHERE c.user_id = ?";
        return Database::fetch($sql, [$userId], 'i');
    }

    /**
     * Создать клиента
     */
    public static function create(array $data): int
    {
        $sql = "INSERT INTO Clients (user_id, creator_id, fullname, phone, communication) 
                VALUES (?, ?, ?, ?, ?)";
        
        Database::execute($sql, [
            $data['user_id'] ?? null,
            $data['creator_id'] ?? null,
            $data['fullname'],
            $data['phone'],
            $data['communication'] ?? 'phone'
        ], 'iisss');
        
        return Database::lastInsertId();
    }

    /**
     * Обновить профиль клиента по ID
     */
    public static function update(int $id, array $data): int
    {
        $sql = "UPDATE Clients SET 
                fullname = ?, 
                phone = ?, 
                communication = ? 
                WHERE id = ?";
        
        return Database::execute($sql, [
            $data['fullname'],
            $data['phone'],
            $data['communication'] ?? 'phone',
            $id
        ], 'sssi');
    }

    /**
     * Обновить профиль клиента по user_id
     */
    public static function updateByUserId(int $userId, array $data): int
    {
        $sql = "UPDATE Clients SET 
                fullname = ?, 
                phone = ?, 
                communication = ? 
                WHERE user_id = ?";
        
        return Database::execute($sql, [
            $data['fullname'],
            $data['phone'],
            $data['communication'] ?? 'phone',
            $userId
        ], 'sssi');
    }

    /**
     * Получить клиентов косметолога
     */
    public static function getByCosmetologist(int $cosmetologistId, string $search = '', string $sort = 'recent'): array
    {
        $sql = "SELECT 
                    c.id as client_id,
                    c.fullname,
                    c.phone,
                    c.communication,
                    c.creator_id,
                    u.email,
                    COUNT(CASE WHEN b.status = 'completed' THEN 1 END) as visit_count,
                    COALESCE(SUM(CASE WHEN b.status = 'completed' THEN s.price ELSE 0 END), 0) as total_spent,
                    MAX(b.schedule) as last_visit,
                    CASE 
                        WHEN MAX(b.schedule) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1
                        ELSE 0
                    END as is_active
                FROM Clients c
                LEFT JOIN Users u ON c.user_id = u.id
                LEFT JOIN Books b ON c.id = b.client_id AND b.cosmetologist_id = ?
                LEFT JOIN Services s ON b.service_id = s.id
                WHERE c.creator_id = ? OR EXISTS (
                    SELECT 1 FROM Books b2 
                    WHERE b2.client_id = c.id 
                    AND b2.cosmetologist_id = ?
                )";
        
        $params = [$cosmetologistId, $cosmetologistId, $cosmetologistId];
        $types = 'iii';
        
        if (!empty($search)) {
            $sql .= " AND (c.fullname LIKE ? OR c.phone LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        $sql .= " GROUP BY c.id, c.fullname, c.phone, c.communication, c.creator_id, u.email";
        
        $sortOptions = [
            'frequent' => 'visit_count DESC',
            'name' => 'c.fullname ASC',
            'revenue' => 'total_spent DESC',
            'recent' => 'last_visit DESC'
        ];
        
        $sql .= " ORDER BY " . ($sortOptions[$sort] ?? $sortOptions['recent']);
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Получить историю записей клиента у косметолога
     */
    public static function getHistory(int $clientId, int $cosmetologistId): array
    {
        $sql = "SELECT 
                    b.id as booking_id,
                    b.schedule,
                    s.service,
                    s.price,
                    b.status,
                    b.description,
                    DATE(b.schedule) as visit_date,
                    TIME(b.schedule) as visit_time
                FROM Books b
                INNER JOIN Services s ON b.service_id = s.id
                WHERE b.client_id = ? AND b.cosmetologist_id = ?
                ORDER BY b.schedule DESC";
        
        return Database::fetchAll($sql, [$clientId, $cosmetologistId], 'ii');
    }

    /**
     * Получить статистику по клиентам косметолога
     */
    public static function getStatistics(int $cosmetologistId): array
    {
        $sql = "SELECT 
                    COUNT(DISTINCT c.id) as total_clients,
                    COALESCE(AVG(stats.visit_count), 0) as avg_visits,
                    COALESCE(AVG(stats.total_spent), 0) as avg_revenue
                FROM Clients c
                LEFT JOIN (
                    SELECT 
                        client_id,
                        COUNT(*) as visit_count,
                        SUM(s.price) as total_spent
                    FROM Books b
                    JOIN Services s ON b.service_id = s.id
                    WHERE b.cosmetologist_id = ? AND b.status = 'completed'
                    GROUP BY client_id
                ) stats ON c.id = stats.client_id
                WHERE c.creator_id = ? OR EXISTS (
                    SELECT 1 FROM Books b2 
                    WHERE b2.client_id = c.id AND b2.cosmetologist_id = ?
                )";
        
        return Database::fetch($sql, [$cosmetologistId, $cosmetologistId, $cosmetologistId], 'iii') ?? [];
    }

    /**
     * Получить детальную информацию о клиенте
     */
    public static function getDetails(int $clientId, ?int $cosmetologistId = null): ?array
    {
        $sql = "SELECT 
                    c.*,
                    u.email,
                    u.role,
                    u.last_login,
                    COUNT(b.id) as total_bookings,
                    COALESCE(SUM(CASE WHEN b.status = 'completed' THEN s.price ELSE 0 END), 0) as total_spent,
                    MAX(b.schedule) as last_visit
                FROM Clients c
                LEFT JOIN Users u ON c.user_id = u.id
                LEFT JOIN Books b ON c.id = b.client_id";
        
        $params = [];
        $types = '';
        
        if ($cosmetologistId) {
            $sql .= " AND b.cosmetologist_id = ?";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        $sql .= " LEFT JOIN Services s ON b.service_id = s.id WHERE c.id = ?";
        $params[] = $clientId;
        $types .= 'i';
        
        $sql .= " GROUP BY c.id, u.email, u.role, u.last_login";
        
        return Database::fetch($sql, $params, $types);
    }

    /**
     * Получить статистику бронирований клиента
     */
    public static function getBookingStatistics(int $clientId): array
    {
        $sql = "SELECT 
                    COUNT(CASE WHEN b.status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN b.status = 'confirmed' THEN 1 END) as confirmed,
                    COUNT(CASE WHEN b.status = 'completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN b.status = 'cancelled' THEN 1 END) as cancelled,
                    COALESCE(SUM(CASE WHEN b.status = 'completed' THEN s.price ELSE 0 END), 0) as total_spent
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                WHERE b.client_id = ?";
        
        return Database::fetch($sql, [$clientId], 'i') ?? [];
    }

    /**
     * Удалить клиента
     */
    public static function delete(int $id): int
    {
        $sql = "DELETE FROM Clients WHERE id = ?";
        return Database::execute($sql, [$id], 'i');
    }
}