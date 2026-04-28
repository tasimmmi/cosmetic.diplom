<?php
namespace App\Models;

use App\Config\Database;

class Schedule
{
    /**
     * Создать слот расписания
     */
    public static function create($cosmetologistId, $beginTime, $endTime)
    {
        $sql = "INSERT INTO Schedule (cosmetologist_id, begin_time, end_time, is_booked) 
                VALUES (?, ?, ?, 0)";
        Database::execute($sql, [$cosmetologistId, $beginTime, $endTime], 'iss');
        return Database::lastInsertId();
    }

    /**
     * Получить расписание косметолога на дату
     */
    public static function getByCosmetologist($cosmetologistId, $date = null)
    {
        $sql = "SELECT s.*, 
                       CONCAT(c.first_name, ' ', c.last_name) AS cosmetologist_name
                FROM Schedule s
                JOIN Cosmetologist c ON s.cosmetologist_id = c.id
                WHERE s.cosmetologist_id = ?";
        
        $params = [$cosmetologistId];
        $types = 'i';
        
        if ($date) {
            $sql .= " AND DATE(s.begin_time) = ?";
            $params[] = $date;
            $types .= 's';
        }
        
        $sql .= " ORDER BY s.begin_time";
        
        return Database::fetchAll($sql, $params, $types);
    }

    /**
     * Получить все слоты на дату (для проверки занятости)
     */
    public static function getAllByDate($date)
    {
        $sql = "SELECT s.*, 
                       CONCAT(c.first_name, ' ', c.last_name) AS cosmetologist_name,
                       c.user_id AS cosmetologist_user_id
                FROM Schedule s
                JOIN Cosmetologist c ON s.cosmetologist_id = c.id
                WHERE DATE(s.begin_time) = ?
                ORDER BY s.begin_time";
        
        return Database::fetchAll($sql, [$date], 's');
    }

    /**
     * Освободить слоты по ID записи
     */
    public static function releaseByBooking($bookingId)
    {
        $sql = "UPDATE Schedule SET is_booked = 0, booking_id = NULL WHERE booking_id = ?";
        return Database::execute($sql, [$bookingId], 'i');
    }

    /**
     * Заблокировать слоты для записи
     */
    public static function bookSlots($bookingId, $beginTime, $endTime)
    {
        $sql = "UPDATE Schedule 
                SET is_booked = 1, booking_id = ? 
                WHERE begin_time >= ? AND end_time <= ? AND is_booked = 0";
        return Database::execute($sql, [$bookingId, $beginTime, $endTime], 'iss');
    }
}