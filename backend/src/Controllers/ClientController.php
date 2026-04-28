<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Booking;
use App\Services\LoggerService;

class ClientController
{
    /**
     * GET /api/client/history
     * История записей клиента
     */
    public function history($request, $response)
    {
        $userId = (int)$request->getParam('user_id');
        $user = User::findById($userId);
        
        if (!$user || !$user['client_id']) {
            return $response->error('Клиент не найден', 404);
        }
        
        $bookings = Booking::findByClientId((int)$user['client_id'], 'completed');
        
        $totalSpent = array_sum(array_map(fn($b) => (float)($b['price'] ?? 0), $bookings));
        
        return $response->success([
            'bookings' => $bookings,
            'total_spent' => $totalSpent,
            'total_visits' => count($bookings)
        ]);
    }

    /**
     * GET /api/client/statistics
     * Статистика клиента
     */
    public function statistics($request, $response)
    {
        $userId = (int)$request->getParam('user_id');
        $user = User::findById($userId);
        
        if (!$user || !$user['client_id']) {
            return $response->error('Клиент не найден', 404);
        }
        
        $allBookings = Booking::findByClientId((int)$user['client_id']);
        
        $stats = [
            'total' => count($allBookings),
            'pending' => count(array_filter($allBookings, fn($b) => $b['status'] === 'pending')),
            'confirmed' => count(array_filter($allBookings, fn($b) => $b['status'] === 'confirmed')),
            'completed' => count(array_filter($allBookings, fn($b) => $b['status'] === 'completed')),
            'cancelled' => count(array_filter($allBookings, fn($b) => $b['status'] === 'cancelled')),
        ];
        
        return $response->success($stats);
    }
}