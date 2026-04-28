<?php
namespace App\Models;

use App\Config\Database;

class Procurement
{
    /**
     * Получить все закупки
     */
    public static function getAll(?int $cosmetologistId = null): array
    {
        $sql = "SELECT p.*, m.material 
                FROM Procurements p 
                LEFT JOIN Materials m ON p.material_id = m.id 
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if ($cosmetologistId) {
            $sql .= " AND (m.cosmetologist_id = ? OR m.is_mutable = TRUE)";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        $sql .= " ORDER BY p.date DESC";
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Получить закупки по материалу
     */
    public static function getByMaterial(int $materialId, ?int $cosmetologistId = null): array
    {
        $sql = "SELECT p.*, m.material 
                FROM Procurements p 
                LEFT JOIN Materials m ON p.material_id = m.id 
                WHERE p.material_id = ?";
        
        $params = [$materialId];
        $types = 'i';
        
        if ($cosmetologistId) {
            $sql .= " AND (m.cosmetologist_id = ? OR m.is_mutable = TRUE)";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        $sql .= " ORDER BY p.date DESC";
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Получить закупки за период
     */
    public static function getByPeriod(string $startDate, string $endDate, ?int $cosmetologistId = null): array
    {
        $sql = "SELECT 
                    p.*,
                    m.material,
                    DATE_FORMAT(p.date, '%Y-%m') as month
                FROM Procurements p
                LEFT JOIN Materials m ON p.material_id = m.id
                WHERE p.date BETWEEN ? AND ?";
        
        $params = [$startDate, $endDate];
        $types = 'ss';
        
        if ($cosmetologistId) {
            $sql .= " AND (m.cosmetologist_id = ? OR m.is_mutable = TRUE)";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        $sql .= " ORDER BY p.date DESC";
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Добавить закупку
     */
    public static function create(int $materialId, float $price, ?int $cosmetologistId = null): bool
    {
        $checkSql = "SELECT id FROM Materials WHERE id = ?";
        $params = [$materialId];
        $types = 'i';
        
        if ($cosmetologistId) {
            $checkSql .= " AND (cosmetologist_id = ? OR is_mutable = TRUE)";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        $material = Database::fetch($checkSql, $params, $types);
        
        if (!$material) {
            return false;
        }
        
        $sql = "INSERT INTO Procurements (material_id, price) VALUES (?, ?)";
        return Database::execute($sql, [$materialId, $price], 'id') > 0;
    }

    /**
     * Получить статистику цен по материалу
     */
    public static function getPriceStatistics(int $materialId, ?int $cosmetologistId = null): ?array
    {
        $sql = "SELECT 
                    MIN(p.price) as min_price,
                    MAX(p.price) as max_price,
                    AVG(p.price) as avg_price,
                    COUNT(*) as total_purchases,
                    DATE_FORMAT(MIN(p.date), '%d.%m.%Y') as first_purchase,
                    DATE_FORMAT(MAX(p.date), '%d.%m.%Y') as last_purchase
                FROM Procurements p
                LEFT JOIN Materials m ON p.material_id = m.id
                WHERE p.material_id = ?";
        
        $params = [$materialId];
        $types = 'i';
        
        if ($cosmetologistId) {
            $sql .= " AND (m.cosmetologist_id = ? OR m.is_mutable = TRUE)";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        return Database::fetch($sql, $params, $types);
    }

    /**
     * Получить последнюю цену материала
     */
    public static function getLastPrice(int $materialId, ?int $cosmetologistId = null): float
    {
        $sql = "SELECT p.price FROM Procurements p
                LEFT JOIN Materials m ON p.material_id = m.id
                WHERE p.material_id = ?";
        
        $params = [$materialId];
        $types = 'i';
        
        if ($cosmetologistId) {
            $sql .= " AND (m.cosmetologist_id = ? OR m.is_mutable = TRUE)";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        $sql .= " ORDER BY p.date DESC LIMIT 1";
        
        $result = Database::fetch($sql, $params, $types);
        return $result ? (float)$result['price'] : 0.0;
    }

    /**
     * Обновить закупку
     */
    public static function update(int $id, int $materialId, float $price, string $date, ?int $cosmetologistId = null): bool
    {
        $checkSql = "SELECT id FROM Materials WHERE id = ?";
        $params = [$materialId];
        $types = 'i';
        
        if ($cosmetologistId) {
            $checkSql .= " AND (cosmetologist_id = ? OR is_mutable = TRUE)";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        $material = Database::fetch($checkSql, $params, $types);
        
        if (!$material) {
            return false;
        }
        
        $sql = "UPDATE Procurements SET material_id = ?, price = ?, date = ? WHERE id = ?";
        return Database::execute($sql, [$materialId, $price, $date, $id], 'idsi') > 0;
    }

    /**
     * Удалить закупку
     */
    public static function delete(int $id, ?int $cosmetologistId = null): int
    {
        $sql = "DELETE p FROM Procurements p
                LEFT JOIN Materials m ON p.material_id = m.id
                WHERE p.id = ?";
        
        $params = [$id];
        $types = 'i';
        
        if ($cosmetologistId) {
            $sql .= " AND (m.cosmetologist_id = ? OR m.is_mutable = TRUE)";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        return Database::execute($sql, $params, $types);
    }
}