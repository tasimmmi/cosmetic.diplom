<?php
namespace App\Models;

use App\Config\Database;
use Exception;

class Report
{
    /**
     * Получить финансовый отчет
     */
    public static function getFinancialReport(int $cosmetologistId, string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    ? as start_date,
                    ? as end_date,
                    COALESCE(SUM(s.price), 0) as total_revenue,
                    COALESCE(COUNT(b.id), 0) as completed_services_count,
                    COALESCE((
                        SELECT SUM(p.price)
                        FROM Procurements p
                        JOIN Materials m ON p.material_id = m.id
                        WHERE (m.cosmetologist_id = ? OR m.is_mutable = TRUE)
                          AND DATE(p.date) BETWEEN ? AND ?
                    ), 0) as total_procurements_cost
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                WHERE b.cosmetologist_id = ?
                  AND b.status = 'completed'
                  AND DATE(b.schedule) BETWEEN ? AND ?";
        
        $result = Database::fetch($sql, [
            $startDate, $endDate,
            $cosmetologistId, $startDate, $endDate,
            $cosmetologistId, $startDate, $endDate
        ], 'ssississ');
        
        if ($result) {
            $netProfit = $result['total_revenue'] - $result['total_procurements_cost'];
            $profitability = $result['total_revenue'] > 0 
                ? round(($netProfit * 100.0) / $result['total_revenue'], 2) 
                : 0;
            
            return [[
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_revenue' => $result['total_revenue'],
                'completed_services_count' => $result['completed_services_count'],
                'total_procurements_cost' => $result['total_procurements_cost'],
                'net_profit' => $netProfit,
                'profitability_percent' => $profitability,
                'profit_status' => $netProfit > 0 ? 'Прибыль' : ($netProfit == 0 ? 'Безубыточность' : 'Убыток')
            ]];
        }
        
        return [];
    }

    /**
     * Получить прибыль по услугам за период
     */
    public static function getProfitByServices(int $cosmetologistId, string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    s.id as service_id,
                    s.service,
                    COUNT(CASE WHEN b.status = 'completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN b.status != 'completed' THEN 1 END) as cancelled_count,
                    COALESCE(SUM(CASE WHEN b.status = 'completed' THEN s.price ELSE 0 END), 0) as total_revenue
                FROM Books b
                JOIN Services s ON b.service_id = s.id
                WHERE s.cosmetologist_id = ?
                  AND DATE(b.schedule) BETWEEN ? AND ?
                GROUP BY s.id, s.service
                ORDER BY total_revenue DESC";
        
        $result = Database::fetchAll($sql, [$cosmetologistId, $startDate, $endDate], 'iss');
        
        $total = array_sum(array_column($result, 'total_revenue'));
        
        foreach ($result as &$item) {
            $item['revenue_percentage'] = $total > 0 
                ? round(($item['total_revenue'] * 100) / $total, 2) 
                : 0;
        }
        
        return $result;
    }

    /**
     * Получить имя косметолога
     */
    public static function getCosmetologistName(int $cosmetologistId): string
    {
        $sql = "SELECT CONCAT(first_name, ' ', last_name) as name FROM Cosmetologist WHERE id = ?";
        $result = Database::fetch($sql, [$cosmetologistId], 'i');
        return $result['name'] ?? 'Косметолог #' . $cosmetologistId;
    }
}