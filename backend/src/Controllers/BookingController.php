<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Config\Database;
use App\Services\EmailService;
use App\Services\LoggerService;
use App\Services\TokenService;
use App\Utils\Validator;

class BookingController
{
    private $emailService;

    public function __construct()
    {
        $this->emailService = new EmailService();
    }

    private function getUserId($request)
    {
        $userId = (int)$request->getParam('user_id');
        if ($userId > 0) return $userId;
        
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            try {
                $tokenService = new TokenService();
                $decoded = $tokenService->verifyAccessToken($matches[1]);
                return (int)($decoded['user_id'] ?? 0);
            } catch (\Exception $e) {
                LoggerService::error('Token decode error in BookingController: ' . $e->getMessage());
            }
        }
        
        return 0;
    }

    /**
     * Получить список бронирований
     */
    public function index($request, $response)
    {
        $userId = $this->getUserId($request);
        $status = $request->getQueryParam('status');
        $user = User::findById($userId);
        
        if (!$user) {
            return $response->error('Пользователь не найден', 404);
        }
        
        $bookings = [];
        
        if ($user['role'] === 'client' && !empty($user['client_id'])) {
            $bookings = Booking::findByClientId((int)$user['client_id'], $status);
        } elseif ($user['role'] === 'cosmetologist' && !empty($user['cosmetologist_id'])) {
            $date = $request->getQueryParam('date');
            $bookings = Booking::findByCosmetologist((int)$user['cosmetologist_id'], $date, $status);
        }
        
        return $response->success(['bookings' => $bookings]);
    }

    /**
     * Получить конкретное бронирование
     */
    public function show($request, $response, $id)
    {
        $userId = $this->getUserId($request);
        $user = User::findById($userId);
        
        if (!$user) {
            return $response->error('Пользователь не найден', 404);
        }
        
        $booking = Booking::findById((int)$id);
        
        if (!$booking) {
            return $response->error('Бронирование не найдено', 404);
        }
        
        $hasAccess = ($user['role'] === 'client' && !empty($user['client_id']) && (int)$booking['client_id'] === (int)$user['client_id'])
                  || ($user['role'] === 'cosmetologist' && !empty($user['cosmetologist_id']) && (int)$booking['cosmetologist_id'] === (int)$user['cosmetologist_id']);
        
        if (!$hasAccess) {
            return $response->error('Доступ запрещен', 403);
        }
        
        return $response->success(['booking' => $booking]);
    }

    /**
     * Создать бронирование (клиент или косметолог)
     */
    public function create($request, $response)
    {
        $userId = $this->getUserId($request);
        $user = User::findById($userId);
        
        if (!$user) {
            return $response->error('Пользователь не найден', 404);
        }
        
        $data = $request->getBody();
        
        $cosmetologistId = $user['role'] === 'cosmetologist' 
            ? ($user['cosmetologist_id'] ?? null) 
            : ($data['cosmetologist_id'] ?? null);
        
        $clientId = $user['role'] === 'client' 
            ? ($user['client_id'] ?? null) 
            : ($data['client_id'] ?? null);
        
        if (!$cosmetologistId) {
            return $response->error('Не удалось определить косметолога', 400);
        }
        
        if (!$clientId) {
            return $response->error('Не удалось определить клиента', 400);
        }
        
        $validator = new Validator($data);
        $validator->required(['service_id', 'schedule'])->numeric('service_id');
        
        if (!$validator->isValid()) {
            return $response->error($validator->getFirstError(), 400, $validator->getErrors());
        }
        
        $scheduleFormatted = date('Y-m-d H:i:s', strtotime($data['schedule']));
        
        $service = Service::findById((int)$data['service_id']);
        
        if (!$service) {
            return $response->error('Услуга не найдена', 404);
        }
        
        if ((int)$service['cosmetologist_id'] !== (int)$cosmetologistId) {
            return $response->error('Услуга не принадлежит указанному косметологу', 400);
        }
        
        try {
            Database::beginTransaction();
            
            $bookingId = Booking::create([
                'cosmetologist_id' => (int)$cosmetologistId,
                'service_id' => (int)$data['service_id'],
                'schedule' => $scheduleFormatted,
                'client_id' => (int)$clientId,
                'status' => 'pending',
                'description' => $data['description'] ?? null
            ]);
            
            Database::commit();
            
            LoggerService::info('Booking created', ['booking_id' => $bookingId]);
            
            return $response->success(['booking_id' => $bookingId], 'Запись успешно создана');
            
        } catch (\Exception $e) {
            Database::rollback();
            LoggerService::error('Booking creation failed', ['error' => $e->getMessage()]);
            return $response->error('Ошибка создания записи: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Отменить бронирование
     */
    public function cancel($request, $response, $id)
    {
        $userId = $this->getUserId($request);
        $user = User::findById($userId);
        
        if (!$user) {
            return $response->error('Пользователь не найден', 404);
        }
        
        $booking = Booking::findById((int)$id);
        
        if (!$booking) {
            return $response->error('Бронирование не найдено', 404);
        }
        
        $canCancel = ($user['role'] === 'client' && !empty($user['client_id']) && (int)$booking['client_id'] === (int)$user['client_id'])
                  || ($user['role'] === 'cosmetologist' && !empty($user['cosmetologist_id']) && (int)$booking['cosmetologist_id'] === (int)$user['cosmetologist_id']);
        
        if (!$canCancel) {
            return $response->error('Доступ запрещен', 403);
        }
        
        if (in_array($booking['status'], ['completed', 'cancelled'])) {
            return $response->error('Нельзя отменить завершенную или уже отмененную запись', 400);
        }
        
        Booking::updateStatus((int)$id, 'cancelled');
        
        LoggerService::info('Booking cancelled', ['booking_id' => $id]);
        
        return $response->success(null, 'Запись отменена');
    }

    /**
     * Обновить комментарий к бронированию
     */
    public function updateComment($request, $response, $id)
    {
        $data = $request->getBody();
        
        Booking::updateDescription((int)$id, $data['description'] ?? '');
        
        LoggerService::info('Booking comment updated', ['booking_id' => $id]);
        
        return $response->success(null, 'Комментарий обновлен');
    }

    /**
     * Получить услуги косметолога
     */
    public function services($request, $response, $id)
    {
        $services = Service::findByCosmetologist((int)$id);
        $cosmetologist = \App\Models\Cosmetologist::findById((int)$id);
        
        if (!$cosmetologist) {
            return $response->error('Косметолог не найден', 404);
        }
        
        return $response->success(['cosmetologist' => $cosmetologist, 'services' => $services]);
    }

    /**
     * Получить доступные слоты
     */
    public function availableSlots($request, $response, $id)
    {
        $date = $request->getQueryParam('date', date('Y-m-d'));
        $serviceId = $request->getQueryParam('service_id');
        
        if (!$serviceId) {
            return $response->error('service_id обязателен', 400);
        }
        
        $slots = \App\Models\Cosmetologist::getAvailableSlots((int)$id, $date);
        
        return $response->success(['cosmetologist_id' => (int)$id, 'date' => $date, 'slots' => $slots]);
    }

    /**
     * Получить бронирования косметолога
     */
    public function cosmetologistBookings($request, $response)
    {
        $userId = $this->getUserId($request);
        $user = User::findById($userId);
        
        if (!$user || empty($user['cosmetologist_id'])) {
            return $response->error('Доступ запрещен', 403);
        }
        
        $date = $request->getQueryParam('date');
        $status = $request->getQueryParam('status');
        
        $bookings = Booking::findByCosmetologist((int)$user['cosmetologist_id'], $date, $status);
        
        return $response->success(['bookings' => $bookings]);
    }
}