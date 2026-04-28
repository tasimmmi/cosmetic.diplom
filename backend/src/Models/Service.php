<?php
namespace App\Models;

use App\Config\Database;

class Service
{
    /**
     * Получить услуги косметолога
     */
    public static function findByCosmetologist($cosmetologistId)
    {
        $sql = "SELECT * FROM Services WHERE cosmetologist_id = ? ORDER BY service ASC";
        return Database::fetchAll($sql, [$cosmetologistId], 'i');
    }

    /**
     * Получить услугу по ID
     */
    public static function findById($id)
    {
        $sql = "SELECT s.*, 
                       CONCAT(c.first_name, ' ', c.last_name) as cosmetologist_name
                FROM Services s
                JOIN Cosmetologist c ON s.cosmetologist_id = c.id
                WHERE s.id = ?";
        
        return Database::fetch($sql, [$id], 'i');
    }

    /**
     * Создать услугу
     */
    public static function create($data)
    {
        $sql = "INSERT INTO Services 
                (cosmetologist_id, service, duration, break_time, price, description) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        Database::execute($sql, [
            $data['cosmetologist_id'],
            $data['service'],
            $data['duration'],
            $data['break_time'] ?? '00:00:00',
            $data['price'],
            $data['description'] ?? null
        ], 'isssds');
        
        return Database::lastInsertId();
    }

    /**
     * Обновить услугу
     */
    public static function update($id, $data)
    {
        $sql = "UPDATE Services 
                SET service = ?, duration = ?, break_time = ?, price = ?, description = ?
                WHERE id = ?";
        
        return Database::execute($sql, [
            $data['service'],
            $data['duration'],
            $data['break_time'] ?? '00:00:00',
            $data['price'],
            $data['description'] ?? null,
            $id
        ], 'sssdsi');
    }

    /**
     * Удалить услугу
     */
    public static function delete($id)
    {
        $sql = "DELETE FROM Services WHERE id = ?";
        return Database::execute($sql, [$id], 'i');
    }
}