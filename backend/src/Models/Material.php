<?php
namespace App\Models;

use App\Config\Database;
use Exception;

class Material
{
    /**
     * Получить все материалы (доступные косметологу)
     */
    public static function getAll(?int $cosmetologistId = null): array
    {
        $sql = "SELECT * FROM Materials WHERE 1=1";
        $params = [];
        $types = '';
        
        if ($cosmetologistId) {
            $sql .= " AND (cosmetologist_id = ? OR is_mutable = TRUE)";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        $sql .= " ORDER BY material";
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Получить статистику по материалам
     */
    public static function getInventoryStatistics(?int $cosmetologistId = null): array
    {
        $sql = "SELECT * FROM v_inventory_statictic WHERE 1=1";
        $params = [];
        $types = '';
        
        if ($cosmetologistId) {
            $sql .= " AND (cosmetologist_id = ? OR is_mutable = TRUE)";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Получить отсутствующие материалы
     */
    public static function getOutOfStock(?int $cosmetologistId = null): array
    {
        $sql = "SELECT * FROM v_material_out_of_stock WHERE 1=1";
        $params = [];
        $types = '';
        
        if ($cosmetologistId) {
            $sql .= " AND (cosmetologist_id = ? OR is_mutable = TRUE)";
            $params[] = $cosmetologistId;
            $types .= 'i';
        }
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Получить материал по ID
     */
    public static function findById(int $id): ?array
    {
        $sql = "SELECT * FROM Materials WHERE id = ?";
        return Database::fetch($sql, [$id], 'i');
    }

    /**
     * Добавить материал
     */
    public static function create(string $material, int $cosmetologistId, bool $isStock = false, bool $isMutable = true): int
    {
        $sql = "INSERT INTO Materials (material, cosmetologist_id, is_stock, is_mutable) 
                VALUES (?, ?, ?, ?)";
        
        Database::execute($sql, [
            $material, 
            $cosmetologistId, 
            (int)$isStock, 
            (int)$isMutable
        ], 'siii');
        
        return Database::lastInsertId();
    }

    /**
     * Обновить статус наличия
     */
    public static function updateStockStatus(int $materialId, bool $isStock): int
    {
        $sql = "UPDATE Materials SET is_stock = ? WHERE id = ?";
        return Database::execute($sql, [(int)$isStock, $materialId], 'ii');
    }

    /**
     * Обновить материал
     */
    public static function update(int $id, string $material, bool $isStock, bool $isMutable): int
    {
        $sql = "UPDATE Materials SET material = ?, is_stock = ?, is_mutable = ? WHERE id = ?";
        return Database::execute($sql, [$material, (int)$isStock, (int)$isMutable, $id], 'siii');
    }

    /**
     * Удалить материал
     */
    public static function delete(int $id): int
    {
        $checkSql = "SELECT COUNT(*) as count FROM Procurements WHERE material_id = ?";
        $result = Database::fetch($checkSql, [$id], 'i');
        
        if ($result && $result['count'] > 0) {
            throw new Exception("Нельзя удалить материал, так как есть связанные закупки");
        }
        
        $sql = "DELETE FROM Materials WHERE id = ?";
        return Database::execute($sql, [$id], 'i');
    }
}