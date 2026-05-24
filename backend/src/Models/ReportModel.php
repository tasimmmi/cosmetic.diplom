<?php
namespace App\Models;

use App\Config\Database;

class ReportModel
{
    public static function getFinancialReport(int $cosmetologistId, string $startDate, string $endDate): array
    {
        return Database::callProcedureAndFetch('sp_get_financial_report', [$cosmetologistId, $startDate, $endDate], 'iss') ?: [];
    }

    public static function getServicesProfit(int $cosmetologistId, string $startDate, string $endDate): array
    {
        return Database::callProcedureAndFetch('sp_get_services_profit', [$cosmetologistId, $startDate, $endDate], 'iss') ?: [];
    }

    public static function getMaterialsStats(int $cosmetologistId): array
    {
        return Database::callProcedureAndFetch('sp_get_materials_stats', [$cosmetologistId], 'i') ?: [];
    }

    public static function getOutOfStock(int $cosmetologistId): array
    {
        return Database::callProcedureAndFetch('sp_get_out_of_stock', [$cosmetologistId], 'i') ?: [];
    }

    public static function getTodayBookings(int $cosmetologistId): array
    {
        return Database::callProcedureAndFetch('sp_get_today_bookings', [$cosmetologistId], 'i') ?: [];
    }

    public static function getPendingTomorrow(int $cosmetologistId): array
    {
        return Database::callProcedureAndFetch('sp_get_pending_tomorrow', [$cosmetologistId], 'i') ?: [];
    }

    public static function getFullStats(int $cosmetologistId): array
    {
        $result = Database::callProcedureAndFetch('sp_get_full_stats', [$cosmetologistId], 'i') ?: [];
        return $result[0] ?? [];
    }

    public static function getCalendarData(string $startDate, string $endDate): array
    {
        $bookings = Database::callProcedureAndFetch('sp_get_calendar_data', [$startDate, $endDate], 'ss') ?: [];
        $grouped = [];
        foreach ($bookings as $b) {
            $grouped[$b['date']][] = $b;
        }
        return $grouped;
    }
}