<?php
namespace App\Models;

use App\Config\Database;

class Booking
{
    /**
     * Получить записи клиента по user_id
     */
    public static function findByClientUserId(int $userId, ?string $status = null): array
    {
        $sql = "SELECT b.*, 
                       s.service as service_name, 
                       s.price, 
                       s.duration,
                       CONCAT(c.first_name, ' ', c.last_name) as cosmetologist_name,
                       c.address,
                       c.phone as cosmetologist_phone
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                JOIN Cosmetologist c ON b.cosmetologist_id = c.id
                JOIN Clients cl ON b.client_id = cl.id
                WHERE cl.user_id = ?";
        
        $params = [$userId];
        $types = 'i';
        
        if ($status) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $sql .= " ORDER BY b.schedule DESC";
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Получить записи клиента по client_id
     */
    public static function findByClientId(int $clientId, ?string $status = null): array
    {
        $sql = "SELECT b.*, 
                       s.service as service_name, 
                       s.price, 
                       s.duration,
                       CONCAT(c.first_name, ' ', c.last_name) as cosmetologist_name
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                JOIN Cosmetologist c ON b.cosmetologist_id = c.id
                WHERE b.client_id = ?";
        
        $params = [$clientId];
        $types = 'i';
        
        if ($status) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $sql .= " ORDER BY b.schedule DESC";
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Получить записи косметолога
     */
    public static function findByCosmetologist(int $cosmetologistId, ?string $date = null, ?string $status = null): array
    {
        $sql = "SELECT b.*, 
                       s.service as service_name, 
                       s.price, 
                       s.duration,
                       cl.fullname as client_name,
                       cl.phone as client_phone,
                       cl.communication
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                JOIN Clients cl ON b.client_id = cl.id
                WHERE b.cosmetologist_id = ?";
        
        $params = [$cosmetologistId];
        $types = 'i';
        
        if ($date) {
            $sql .= " AND DATE(b.schedule) = ?";
            $params[] = $date;
            $types .= 's';
        }
        
        if ($status) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $sql .= " ORDER BY b.schedule ASC";
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Получить записи косметолога за период
     */
    public static function findByCosmetologistPeriod(int $cosmetologistId, string $startDate, string $endDate): array
    {
        $sql = "SELECT b.*, 
                       s.service as service_name, 
                       s.price,
                       cl.fullname as client_name
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                JOIN Clients cl ON b.client_id = cl.id
                WHERE b.cosmetologist_id = ?
                  AND DATE(b.schedule) BETWEEN ? AND ?
                ORDER BY b.schedule DESC";
        
        return Database::fetchAll($sql, [$cosmetologistId, $startDate, $endDate], 'iss');
    }

    /**
     * Создать бронирование
     */
    public static function create(array $data): int
    {
        $sql = "INSERT INTO Books 
                (cosmetologist_id, service_id, schedule, client_id, status, description, created) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        Database::execute($sql, [
            $data['cosmetologist_id'],
            $data['service_id'],
            $data['schedule'],
            $data['client_id'],
            $data['status'] ?? 'pending',
            $data['description'] ?? null
        ], 'iisiss');
        
        return Database::lastInsertId();
    }

    /**
     * Обновить статус бронирования
     */
    public static function updateStatus(int $bookingId, string $status): int
    {
        $sql = "UPDATE Books SET status = ?, updated = NOW() WHERE id = ?";
        return Database::execute($sql, [$status, $bookingId], 'si');
    }

    /**
     * Получить бронирование по ID
     */
    public static function findById(int $id): ?array
    {
        $sql = "SELECT b.*, 
                       s.service as service_name, 
                       s.price, 
                       s.duration,
                       s.break_time,
                       CONCAT(c.first_name, ' ', c.last_name) as cosmetologist_name,
                       c.address,
                       c.phone as cosmetologist_phone,
                       cl.fullname as client_name,
                       cl.phone as client_phone,
                       cl.communication,
                       u.email as client_email
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                JOIN Cosmetologist c ON b.cosmetologist_id = c.id
                JOIN Clients cl ON b.client_id = cl.id
                LEFT JOIN Users u ON cl.user_id = u.id
                WHERE b.id = ?";
        
        return Database::fetch($sql, [$id], 'i');
    }

    /**
     * Получить статистику бронирований косметолога
     */
    public static function getStatistics(int $cosmetologistId, ?string $date = null): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN s.price ELSE 0 END), 0) as total_revenue
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                WHERE b.cosmetologist_id = ?";
        
        $params = [$cosmetologistId];
        $types = 'i';
        
        if ($date) {
            $sql .= " AND DATE(b.schedule) = ?";
            $params[] = $date;
            $types .= 's';
        }
        
        return Database::fetch($sql, $params, $types) ?? [];
    }

    /**
     * Получить завтрашние записи (ожидающие подтверждения)
     */
    public static function getPendingTomorrow(int $cosmetologistId): array
    {
        $sql = "SELECT b.*, 
                       s.service as service_name,
                       cl.fullname as client_name,
                       cl.phone as client_phone
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                JOIN Clients cl ON b.client_id = cl.id
                WHERE b.cosmetologist_id = ?
                  AND DATE(b.schedule) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                  AND b.status = 'pending'
                ORDER BY b.schedule";
        
        return Database::fetchAll($sql, [$cosmetologistId], 'i');
    }

    /**
     * Получить записи на сегодня
     */
    public static function getToday(int $cosmetologistId): array
    {
        $sql = "SELECT b.*, 
                       s.service as service_name,
                       cl.fullname as client_name,
                       cl.phone as client_phone
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                JOIN Clients cl ON b.client_id = cl.id
                WHERE b.cosmetologist_id = ?
                  AND DATE(b.schedule) = CURDATE()
                ORDER BY b.schedule";
        
        return Database::fetchAll($sql, [$cosmetologistId], 'i');
    }

    /**
     * Обновить описание (комментарий) записи
     */
    public static function updateDescription($bookingId, $description)
    {
        $sql = "UPDATE Books SET description = ?, updated = NOW() WHERE id = ?";
        return Database::execute($sql, [$description, $bookingId], 'si');
    }

    /**
     * Записи косметолога с пагинацией (новые сверху)
     */
    public static function findByCosmetologistPaginated($cosmetologistId, $date = null, $status = null, $limit = 20, $offset = 0)
    {
        $sql = "SELECT b.*, s.service AS service_name, s.price, s.duration,
                    cl.fullname AS client_name, cl.phone AS client_phone
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                JOIN Clients cl ON b.client_id = cl.id
                WHERE b.cosmetologist_id = ?";
        
        $params = [$cosmetologistId];
        $types = 'i';
        
        if ($date) {
            $sql .= " AND DATE(b.schedule) = ?";
            $params[] = $date;
            $types .= 's';
        }
        
        if ($status) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $sql .= " ORDER BY DATE(b.schedule) DESC, TIME(b.schedule) ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Количество записей косметолога
     */
    public static function countByCosmetologist($cosmetologistId, $date = null, $status = null)
    {
        $sql = "SELECT COUNT(*) as cnt FROM Books WHERE cosmetologist_id = ?";
        $params = [$cosmetologistId];
        $types = 'i';
        
        if ($date) {
            $sql .= " AND DATE(schedule) = ?";
            $params[] = $date;
            $types .= 's';
        }
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $result = Database::fetch($sql, $params, $types);
        return (int)($result['cnt'] ?? 0);
    }
}