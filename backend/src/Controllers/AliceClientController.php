<?php
namespace App\Controllers;

use App\Models\Client;
use App\Models\Cosmetologist;
use App\Models\Booking;

class AliceClientController
{
    public function handle(array $data, array $user): array
    {
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        $session = $data['session'] ?? [];
        $clientId = $user['client_id'] ?? null;
        
        if (!$clientId) return AliceController::reply('Профиль клиента не найден.', $session, true);
        
        if (AliceController::match($command, ['мои записи', 'записи', 'ближайшие'])) {
            return $this->handleBookings($data, $user);
        }
        if (AliceController::match($command, ['отменить', 'отмена'])) {
            return $this->handleCancelBooking($data, $user);
        }
        if (AliceController::match($command, ['подтвердить'])) {
            return $this->handleConfirmBooking($data, $user);
        }
        if (AliceController::match($command, ['косметолог', 'специалист'])) {
            return $this->handleCosmetologists($data, $user);
        }
        if (AliceController::match($command, ['услуги', 'прайс', 'цены'])) {
            return $this->handleServices($data, $user);
        }
        
        return AliceController::reply('Не поняла. Скажите «Помощь».', $session);
    }
    
    public function handleBookings(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $clientId = $user['client_id'];
        
        $bookings = Booking::findByClientId($clientId);
        if (empty($bookings)) return AliceController::reply('У вас нет записей.', $session, true);
        
        $lines = [];
        foreach (array_slice($bookings, 0, 5) as $b) {
            $d = date('d.m', strtotime($b['schedule']));
            $t = date('H:i', strtotime($b['schedule']));
            $s = ['pending' => 'ожидает', 'confirmed' => 'подтверждена', 'completed' => 'завершена', 'cancelled' => 'отменена'][$b['status']] ?? $b['status'];
            $lines[] = "{$d} в {$t} — {$b['service_name']} ({$s})";
        }
        
        return AliceController::reply('Ваши записи: ' . implode('. ', $lines) . '.', $session, true);
    }
    
    public function handleCancelBooking(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $clientId = $user['client_id'];
        
        $active = array_merge(
            Booking::findByClientId($clientId, 'pending'),
            Booking::findByClientId($clientId, 'confirmed')
        );
        
        if (empty($active)) return AliceController::reply('Нет активных записей.', $session, true);
        if (count($active) === 1) {
            Booking::updateStatus($active[0]['id'], 'cancelled');
            return AliceController::reply('Запись отменена.', $session, true);
        }
        
        $buttons = array_map(fn($b) => ['title' => date('d.m H:i', strtotime($b['schedule'])), 'hide' => true], $active);
        return AliceController::reply('Какую запись отменить?', $session, false, $buttons);
    }
    
    public function handleConfirmBooking(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $clientId = $user['client_id'];
        
        $pending = Booking::findByClientId($clientId, 'pending');
        if (empty($pending)) return AliceController::reply('Нет записей для подтверждения.', $session, true);
        if (count($pending) === 1) {
            Booking::updateStatus($pending[0]['id'], 'confirmed');
            return AliceController::reply('Запись подтверждена.', $session, true);
        }
        
        $buttons = array_map(fn($b) => ['title' => date('d.m H:i', strtotime($b['schedule'])), 'hide' => true], $pending);
        return AliceController::reply('Какую запись подтвердить?', $session, false, $buttons);
    }
    
    public function handleCosmetologists(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $cosms = Cosmetologist::findAll();
        if (empty($cosms)) return AliceController::reply('Нет доступных косметологов.', $session, true);
        
        $lines = array_map(fn($c) => $c['first_name'] . ' ' . $c['last_name'], $cosms);
        return AliceController::reply(implode('. ', $lines) . '.', $session, true);
    }
    
    public function handleServices(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $cosms = Cosmetologist::findAll();
        $lines = [];
        foreach ($cosms as $c) {
            $services = Cosmetologist::getServices($c['id']);
            foreach ($services as $s) {
                $lines[] = $c['first_name'] . ': ' . $s['service'] . ' — ' . $s['price'] . ' BYN';
            }
        }
        if (empty($lines)) return AliceController::reply('Услуг пока нет.', $session, true);
        return AliceController::reply(implode('. ', $lines) . '.', $session, true);
    }
}