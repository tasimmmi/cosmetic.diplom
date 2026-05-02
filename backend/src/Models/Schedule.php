<?php
namespace App\Models;

use App\Config\Database;

class Schedule
{
    /**
     * 🔥 Сгенерировать расписание через процедуру
     */
    public static function generate(int $cosmetologistId, string $startTime, string $endTime, array $dates): array
    {
        $json = json_encode($dates, JSON_UNESCAPED_UNICODE);
        $sql = "CALL sp_generate_schedule(?, ?, ?, ?)";
        return Database::fetch($sql, [$cosmetologistId, $startTime, $endTime, $json], 'isss') ?: [];
    }

    public static function createSlot(int $cosmetologistId, string $beginTime, string $endTime): int
    {
        $sql = "INSERT INTO Schedule (cosmetologist_id, begin_time, end_time, is_booked) VALUES (?, ?, ?, 0)";
        Database::execute($sql, [$cosmetologistId, $beginTime, $endTime], 'iss');
        return Database::lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $sql = "SELECT * FROM Schedule WHERE id = ?";
        return Database::fetch($sql, [$id], 'i');
    }

    public static function getByCosmetologist(int $cosmetologistId, ?string $date = null): array
    {
        $sql = "SELECT s.id, s.cosmetologist_id, s.begin_time, s.end_time, s.is_booked, s.booking_id,
                       b.status AS booking_status, srv.service AS booking_service, cl.fullname AS client_name
                FROM Schedule s
                LEFT JOIN Books b ON s.booking_id = b.id
                LEFT JOIN Services srv ON b.service_id = srv.id
                LEFT JOIN Clients cl ON b.client_id = cl.id
                WHERE s.cosmetologist_id = ?";
        $params = [$cosmetologistId]; $types = 'i';
        if ($date) { $sql .= " AND DATE(s.begin_time) = ?"; $params[] = $date; $types .= 's'; }
        $sql .= " ORDER BY s.begin_time";
        return Database::fetchAll($sql, $params, $types) ?: [];
    }

    public static function getAvailableSlots(string $date, int $serviceId): array
    {
        error_log("=== Schedule::getAvailableSlots ===");
        error_log("date: " . $date);
        error_log("serviceId: " . $serviceId);
        
        $sql = "CALL sp_get_available_slots(?, ?)";
        
        error_log("SQL: " . $sql);
        
        $result = Database::fetchAll($sql, [$date, $serviceId], 'si') ?: [];
        
        error_log("Result count: " . count($result));
        error_log("Result: " . json_encode($result));
        
        return $result;
    }

    public static function checkTimeAvailable(int $serviceId, string $schedule): array
    {
        $sql = "CALL sp_check_time_available(?, ?)";
        return Database::fetch($sql, [$serviceId, $schedule], 'is') ?: [];
    }

    public static function getAllByDate(string $date): array
    {
        $sql = "SELECT s.id, s.cosmetologist_id, s.begin_time, s.end_time, s.is_booked, s.booking_id,
                       CONCAT(c.first_name, ' ', c.last_name) AS cosmetologist_name,
                       c.user_id AS cosmetologist_user_id,
                       b.status AS booking_status, srv.service AS booking_service, cl.fullname AS client_name
                FROM Schedule s
                JOIN Cosmetologist c ON s.cosmetologist_id = c.id
                LEFT JOIN Books b ON s.booking_id = b.id
                LEFT JOIN Services srv ON b.service_id = srv.id
                LEFT JOIN Clients cl ON b.client_id = cl.id
                WHERE DATE(s.begin_time) = ?
                ORDER BY s.cosmetologist_id, s.begin_time";
        return Database::fetchAll($sql, [$date], 's') ?: [];
    }

    public static function getBookedSlots(string $date): array
    {
        $sql = "SELECT s.id, s.cosmetologist_id, s.begin_time, s.end_time, s.booking_id,
                       CONCAT(c.first_name, ' ', c.last_name) AS cosmetologist_name,
                       b.status AS booking_status, srv.service AS booking_service,
                       cl.fullname AS client_name
                FROM Schedule s
                JOIN Cosmetologist c ON s.cosmetologist_id = c.id
                JOIN Books b ON s.booking_id = b.id
                JOIN Services srv ON b.service_id = srv.id
                JOIN Clients cl ON b.client_id = cl.id
                WHERE DATE(s.begin_time) = ? AND s.is_booked = 1 AND b.status IN ('pending', 'confirmed')
                ORDER BY s.cosmetologist_id, s.begin_time";
        return Database::fetchAll($sql, [$date], 's') ?: [];
    }

    public static function getBookedSlotsForCalendar(string $startDate, string $endDate): array
    {
        $sql = "SELECT DATE(s.begin_time) AS date, s.cosmetologist_id,
                       CONCAT(c.first_name, ' ', c.last_name) AS cosmetologist_name,
                       c.user_id AS cosmetologist_user_id, s.begin_time, b.status,
                       srv.service AS service_name, cl.fullname AS client_name
                FROM Schedule s
                JOIN Cosmetologist c ON s.cosmetologist_id = c.id
                LEFT JOIN Books b ON s.booking_id = b.id
                LEFT JOIN Services srv ON b.service_id = srv.id
                LEFT JOIN Clients cl ON b.client_id = cl.id
                WHERE DATE(s.begin_time) BETWEEN ? AND ? AND s.is_booked IN (0, 2)
                ORDER BY DATE(s.begin_time), s.cosmetologist_id, s.begin_time";
        $slots = Database::fetchAll($sql, [$startDate, $endDate], 'ss') ?: [];
        $grouped = [];
        foreach ($slots as $slot) { $grouped[$slot['date']][] = $slot; }
        return $grouped;
    }

    /**
     * 🔥 Получить данные для календаря (только записи из Books)
     */
    public static function getCalendarData(string $startDate, string $endDate): array
    {
        $sql = "CALL sp_get_calendar_data(?, ?)";
        $bookings = Database::fetchAll($sql, [$startDate, $endDate], 'ss') ?: [];
        $grouped = [];
        foreach ($bookings as $b) { $grouped[$b['date']][] = $b; }
        return $grouped;
    }

    public static function deleteSlot(int $id): bool
    {
        $slot = self::findById($id);
        if (!$slot || $slot['is_booked'] == 1) return false;
        return Database::execute("DELETE FROM Schedule WHERE id = ? AND is_booked != 1", [$id], 'i') > 0;
    }

    public static function clearDay(int $cosmetologistId, string $date): int
    {
        return Database::execute("DELETE FROM Schedule WHERE cosmetologist_id = ? AND DATE(begin_time) = ? AND is_booked != 1", [$cosmetologistId, $date], 'is');
    }

    public static function deactivateSlot(int $id): bool
    {
        return Database::execute("UPDATE Schedule SET is_booked = 2 WHERE id = ? AND is_booked = 0", [$id], 'i') > 0;
    }

    public static function activateSlot(int $id): bool
    {
        return Database::execute("UPDATE Schedule SET is_booked = 0 WHERE id = ? AND is_booked = 2", [$id], 'i') > 0;
    }

    public static function deactivateDay(int $cosmetologistId, string $date): int
    {
        return Database::execute("UPDATE Schedule SET is_booked = 2 WHERE cosmetologist_id = ? AND DATE(begin_time) = ? AND is_booked = 0", [$cosmetologistId, $date], 'is');
    }

    public static function activateDay(int $cosmetologistId, string $date): int
    {
        return Database::execute("UPDATE Schedule SET is_booked = 0 WHERE cosmetologist_id = ? AND DATE(begin_time) = ? AND is_booked = 2", [$cosmetologistId, $date], 'is');
    }

    public static function releaseByBooking(int $bookingId): int
    {
        return Database::execute("UPDATE Schedule SET is_booked = 0, booking_id = NULL WHERE booking_id = ?", [$bookingId], 'i');
    }

    public static function bookSlots(int $bookingId, string $beginTime, string $endTime): int
    {
        return Database::execute("UPDATE Schedule SET is_booked = 1, booking_id = ? WHERE begin_time >= ? AND end_time <= ? AND is_booked = 0", [$bookingId, $beginTime, $endTime], 'iss');
    }

    public static function getDayStats(int $cosmetologistId, string $date): array
    {
        $sql = "SELECT COUNT(*) AS total, SUM(CASE WHEN is_booked = 0 THEN 1 ELSE 0 END) AS free,
                       SUM(CASE WHEN is_booked = 1 THEN 1 ELSE 0 END) AS booked,
                       SUM(CASE WHEN is_booked = 2 THEN 1 ELSE 0 END) AS deactivated
                FROM Schedule WHERE cosmetologist_id = ? AND DATE(begin_time) = ?";
        return Database::fetch($sql, [$cosmetologistId, $date], 'is') ?: [];
    }
}