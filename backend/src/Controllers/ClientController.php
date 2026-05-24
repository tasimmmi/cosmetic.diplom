<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Booking;

class ClientController
{
    /** GET /api/client/history */
    public function history($request, $response)
    {
        $userId = (int)$request->getParam('user_id');
        $user = User::findById($userId);
        
        if (!$user || !$user['client_id']) {
            return $response->error('Клиент не найден', 404);
        }
        
        $bookings = Booking::findByClientId((int)$user['client_id'], 'completed');
        
        return $response->success([
            'bookings' => $bookings,
            'total_spent' => array_sum(array_map(fn($b) => (float)($b['price'] ?? 0), $bookings)),
            'total_visits' => count($bookings)
        ]);
    }

    /** GET /api/client/statistics */
    public function statistics($request, $response)
    {
        $userId = (int)$request->getParam('user_id');
        $user = User::findById($userId);
        
        if (!$user || !$user['client_id']) {
            return $response->error('Клиент не найден', 404);
        }
        
        $bookings = Booking::findByClientId((int)$user['client_id']);
        
        return $response->success([
            'total' => count($bookings),
            'pending' => count(array_filter($bookings, fn($b) => $b['status'] === 'pending')),
            'confirmed' => count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed')),
            'completed' => count(array_filter($bookings, fn($b) => $b['status'] === 'completed')),
            'cancelled' => count(array_filter($bookings, fn($b) => $b['status'] === 'cancelled')),
        ]);
    }
}