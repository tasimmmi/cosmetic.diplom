<?php
namespace App\Models;

use App\Config\Database;

class Procurement
{
    public static function create(int $materialId, float $price, int $cosmetologistId): int
    {
        $sql = "INSERT INTO Procurements (material_id, price, date) VALUES (?, ?, NOW())";
        Database::execute($sql, [$materialId, $price], 'id');
        
        // Обновляем статус наличия материала
        Material::updateStockStatus($materialId, true);
        
        return Database::lastInsertId();
    }
    
    public static function getAll(int $cosmetologistId): array
    {
        $sql = "SELECT 
                    p.id,
                    p.price,
                    p.date,
                    m.id as material_id,
                    m.material as material_name,
                    m.is_mutable,
                    m.cosmetologist_id as material_owner_id
                FROM Procurements p
                JOIN Materials m ON p.material_id = m.id
                WHERE m.cosmetologist_id = ? OR m.is_mutable = 1
                ORDER BY p.date DESC";
        
        return Database::fetchAll($sql, [$cosmetologistId], 'i');
    }
    
    public static function findById(int $id): ?array
    {
        $sql = "SELECT * FROM Procurements WHERE id = ?";
        return Database::fetch($sql, [$id], 'i');
    }
    
    public static function getByMaterialId(int $materialId): array
    {
        $sql = "SELECT * FROM Procurements WHERE material_id = ? ORDER BY date DESC";
        return Database::fetchAll($sql, [$materialId], 'i');
    }
    
    public static function update(int $id, array $data): int
    {
        $sql = "UPDATE Procurements SET price = ?, date = ? WHERE id = ?";
        return Database::execute($sql, [$data['price'], $data['date'], $id], 'dsi');
    }
    
    public static function delete(int $id): int
    {
        $sql = "DELETE FROM Procurements WHERE id = ?";
        return Database::execute($sql, [$id], 'i');
    }
}