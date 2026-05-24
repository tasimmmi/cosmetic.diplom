<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\ReportModel;
use App\Services\LoggerService;
use App\Services\TokenService;

class ReportController
{
    private function getCosmetologistId(): int
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            try {
                $tokenService = new TokenService();
                $decoded = $tokenService->verifyAccessToken($matches[1]);
                $userId = (int)($decoded['user_id'] ?? 0);
                if ($userId > 0) {
                    $user = User::findById($userId);
                    if ($user && !empty($user['cosmetologist_id'])) return (int)$user['cosmetologist_id'];
                }
            } catch (\Exception $e) {
                LoggerService::error('Token error in Report: ' . $e->getMessage());
            }
        }
        return 0;
    }

    /** GET /api/cosmetologist/reports/financial */
    public function financial($request, $response)
    {
        $cosmId = $this->getCosmetologistId();
        if (!$cosmId) return $response->error('Косметолог не найден', 404);

        $startDate = $request->getQueryParam('start_date', date('Y-m-01'));
        $endDate = $request->getQueryParam('end_date', date('Y-m-t'));

        return $response->success(ReportModel::getFinancialReport($cosmId, $startDate, $endDate));
    }

    /** GET /api/cosmetologist/reports/services */
    public function services($request, $response)
    {
        $cosmId = $this->getCosmetologistId();
        if (!$cosmId) return $response->error('Косметолог не найден', 404);

        $startDate = $request->getQueryParam('start_date', date('Y-m-01'));
        $endDate = $request->getQueryParam('end_date', date('Y-m-t'));

        return $response->success(ReportModel::getServicesProfit($cosmId, $startDate, $endDate));
    }

    /** GET /api/cosmetologist/reports/materials */
    public function materials($request, $response)
    {
        $cosmId = $this->getCosmetologistId();
        if (!$cosmId) return $response->error('Косметолог не найден', 404);

        return $response->success([
            'stats' => ReportModel::getMaterialsStats($cosmId),
            'out_of_stock' => ReportModel::getOutOfStock($cosmId)
        ]);
    }

    /** GET /api/cosmetologist/reports/bookings */
    public function bookings($request, $response)
    {
        $cosmId = $this->getCosmetologistId();
        if (!$cosmId) return $response->error('Косметолог не найден', 404);

        return $response->success([
            'today' => ReportModel::getTodayBookings($cosmId),
            'tomorrow_pending' => ReportModel::getPendingTomorrow($cosmId)
        ]);
    }

    /** GET /api/cosmetologist/reports/summary */
    public function summary($request, $response)
    {
        $cosmId = $this->getCosmetologistId();
        if (!$cosmId) return $response->error('Косметолог не найден', 404);

        $startDate = $request->getQueryParam('start_date', date('Y-m-01'));
        $endDate = $request->getQueryParam('end_date', date('Y-m-t'));

        return $response->success([
            'stats' => ReportModel::getFullStats($cosmId),
            'financial' => ReportModel::getFinancialReport($cosmId, $startDate, $endDate),
            'services' => ReportModel::getServicesProfit($cosmId, $startDate, $endDate),
            'materials' => ReportModel::getMaterialsStats($cosmId),
            'out_of_stock' => ReportModel::getOutOfStock($cosmId)
        ]);
    }

    /** GET /api/cosmetologist/reports/export-pdf */
    public function exportPdf($request, $response)
    {
        $cosmId = $this->getCosmetologistId();
        if (!$cosmId) return $response->error('Косметолог не найден', 404);

        $startDate = $request->getQueryParam('start_date', date('Y-m-01'));
        $endDate = $request->getQueryParam('end_date', date('Y-m-t'));

        $data = [
            'financial' => ReportModel::getFinancialReport($cosmId, $startDate, $endDate),
            'services' => ReportModel::getServicesProfit($cosmId, $startDate, $endDate),
            'materials' => ReportModel::getMaterialsStats($cosmId),
            'out_of_stock' => ReportModel::getOutOfStock($cosmId),
            'today_bookings' => ReportModel::getTodayBookings($cosmId),
            'tomorrow_pending' => ReportModel::getPendingTomorrow($cosmId),
            'stats' => ReportModel::getFullStats($cosmId)
        ];

        return $response->success($data);
    }
}