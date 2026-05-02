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
     * 🔥 Получить клиентов косметолога из представления
     */
    public static function getByCosmetologistId(int $cosmetologistId, string $search = '', string $sort = 'recent'): array
    {
        $viewName = 'v_clients_cosmetologist_' . $cosmetologistId;
        
        // Безопасность: разрешены только существующие представления
        $allowedViews = ['v_clients_cosmetologist_1', 'v_clients_cosmetologist_2'];
        if (!in_array($viewName, $allowedViews)) {
            return [];
        }
        
        $sql = "SELECT * FROM {$viewName}";
        $params = [];
        $types = '';
        
        // Поиск
        if (!empty($search)) {
            $sql .= " WHERE (fullname LIKE ? OR phone LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= 'ss';
        }
        
        // Сортировка
        $sortOptions = [
            'frequent' => 'visit_count DESC',
            'name' => 'fullname ASC',
            'revenue' => 'total_spent DESC',
            'recent' => 'last_visit DESC, fullname ASC'
        ];
        
        $sql .= " ORDER BY " . ($sortOptions[$sort] ?? $sortOptions['recent']);
        
        return Database::fetchAll($sql, $params, $types) ?: [];
    }

    /**
     * 🔥 Получить клиента из представления (с проверкой доступа)
     */
    public static function findInView(int $clientId, int $cosmetologistId): ?array
    {
        $viewName = 'v_clients_cosmetologist_' . $cosmetologistId;
        
        $allowedViews = ['v_clients_cosmetologist_1', 'v_clients_cosmetologist_2'];
        if (!in_array($viewName, $allowedViews)) {
            return null;
        }
        
        return Database::fetch(
            "SELECT * FROM {$viewName} WHERE id = ?",
            [$clientId],
            'i'
        );
    }

    /**
     * 🔥 Получить историю записей клиента у косметолога
     */
    public static function getHistoryWithCosmetologist(int $clientId, int $cosmetologistId): array
    {
        $sql = "SELECT 
                    b.id,
                    b.schedule,
                    b.status,
                    b.description,
                    s.service,
                    s.price
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                WHERE b.client_id = ? AND b.cosmetologist_id = ?
                ORDER BY b.schedule DESC";
        
        return Database::fetchAll($sql, [$clientId, $cosmetologistId], 'ii') ?: [];
    }

    /**
     * Получить статистику по клиентам косметолога
     */
    public static function getStatistics(int $cosmetologistId): array
    {
        $viewName = 'v_clients_cosmetologist_' . $cosmetologistId;
        
        $allowedViews = ['v_clients_cosmetologist_1', 'v_clients_cosmetologist_2'];
        if (!in_array($viewName, $allowedViews)) {
            return [];
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_clients,
                    COALESCE(AVG(visit_count), 0) as avg_visits,
                    COALESCE(AVG(total_spent), 0) as avg_revenue
                FROM {$viewName}";
        
        return Database::fetch($sql) ?? [];
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