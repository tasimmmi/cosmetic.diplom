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

    /**
     * Получить ID пользователя из токена
     */
    private function getUserId($request)
    {
        // Пробуем получить из middleware
        $userId = (int)$request->getParam('user_id');
        if ($userId > 0) return $userId;
        
        // Если нет - читаем токен напрямую
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
        
        $hasAccess = false;
        
        if ($user['role'] === 'client' && !empty($user['client_id']) && 
            (int)$booking['client_id'] === (int)$user['client_id']) {
            $hasAccess = true;
        } elseif ($user['role'] === 'cosmetologist' && !empty($user['cosmetologist_id']) && 
                  (int)$booking['cosmetologist_id'] === (int)$user['cosmetologist_id']) {
            $hasAccess = true;
        }
        
        if (!$hasAccess) {
            return $response->error('Доступ запрещен', 403);
        }
        
        return $response->success(['booking' => $booking]);
    }

    /**
     * Создать новое бронирование
     */
    public function create($request, $response)
    {
        $data = $request->getBody();
        
        LoggerService::info('Creating booking', ['data' => $data]);
        
        $validator = new Validator($data);
        $validator->required(['cosmetologist_id', 'service_id', 'schedule', 'client_id'])
                  ->numeric('cosmetologist_id')
                  ->numeric('service_id')
                  ->numeric('client_id');
        
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            $firstError = $validator->getFirstError();
            if ($firstError === null) {
                $firstError = 'Validation failed';
            }
            return $response->error($firstError, 400, $errors);
        }
        
        $schedule = $data['schedule'];
        $timestamp = strtotime($schedule);
        $scheduleFormatted = date('Y-m-d H:i:s', $timestamp);
        
        $service = Service::findById((int)$data['service_id']);
        
        if (!$service) {
            return $response->error('Услуга не найдена', 404);
        }
        
        if ((int)$service['cosmetologist_id'] !== (int)$data['cosmetologist_id']) {
            return $response->error('Услуга не принадлежит указанному косметологу', 400);
        }
        
        try {
            Database::beginTransaction();
            
            $bookingId = Booking::create([
                'cosmetologist_id' => (int)$data['cosmetologist_id'],
                'service_id' => (int)$data['service_id'],
                'schedule' => $scheduleFormatted,
                'client_id' => (int)$data['client_id'],
                'status' => 'pending',
                'description' => isset($data['description']) ? $data['description'] : null
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
        
        $canCancel = false;
        
        if ($user['role'] === 'client' && !empty($user['client_id']) && 
            (int)$booking['client_id'] === (int)$user['client_id']) {
            $canCancel = true;
        } elseif ($user['role'] === 'cosmetologist' && !empty($user['cosmetologist_id']) && 
                  (int)$booking['cosmetologist_id'] === (int)$user['cosmetologist_id']) {
            $canCancel = true;
        }
        
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
     * Получить услуги косметолога
     */
    public function services($request, $response, $id)
    {
        $services = Service::findByCosmetologist((int)$id);
        $cosmetologist = \App\Models\Cosmetologist::findById((int)$id);
        
        if (!$cosmetologist) {
            return $response->error('Косметолог не найден', 404);
        }
        
        return $response->success([
            'cosmetologist' => $cosmetologist,
            'services' => $services
        ]);
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
        
        return $response->success([
            'cosmetologist_id' => (int)$id,
            'date' => $date,
            'slots' => $slots
        ]);
    }

    /**
     * Получить бронирования для косметолога
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

    /**
     * Обновить комментарий к записи
     */
    public function updateComment($request, $response, $id)
    {
        $data = $request->getBody();
        $description = isset($data['description']) ? $data['description'] : '';
        
        Booking::updateDescription((int)$id, $description);
        
        LoggerService::info('Booking comment updated', ['booking_id' => $id]);
        
        return $response->success(null, 'Комментарий обновлен');
    }
}