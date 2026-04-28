<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Cosmetologist;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Material;
use App\Models\Client;
use App\Models\Procurement;
use App\Config\Database;
use App\Services\LoggerService;


class CosmetologistController
{
    /**
     * Получить ID косметолога из токена
     */
    private function getCosmetologistId($request)
{
    // Получаем токен напрямую из заголовков
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    LoggerService::info('Auth header: ' . ($authHeader ? substr($authHeader, 0, 50) . '...' : 'NOT FOUND'));
    
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        
        try {
            $tokenService = new \App\Services\TokenService();
            $decoded = $tokenService->verifyAccessToken($token);
            $userId = (int)($decoded['user_id'] ?? 0);
            
            LoggerService::info('Token decoded, user_id: ' . $userId);
            
            if ($userId > 0) {
                $user = User::findById($userId);
                
                if ($user && !empty($user['cosmetologist_id'])) {
                    LoggerService::info('Cosmetologist found, id: ' . $user['cosmetologist_id']);
                    return (int)$user['cosmetologist_id'];
                }
                
                LoggerService::warning('User has no cosmetologist_id', ['user_id' => $userId]);
            }
        } catch (\Exception $e) {
            LoggerService::error('Token decode error: ' . $e->getMessage());
        }
    }
    
    return 0;
}

    /**
     * GET /api/cosmetologists
     * Список всех косметологов
     */
    public function list($request, $response)
    {
        $cosmetologists = Cosmetologist::findAll();
        
        foreach ($cosmetologists as &$c) {
            $services = Cosmetologist::getServices($c['id']);
            $c['services'] = $services;
            $c['min_price'] = !empty($services) ? min(array_column($services, 'price')) : null;
        }
        
        return $response->success($cosmetologists);
    }

    /**
     * GET /api/cosmetologists/{id}
     * Карточка косметолога
     */
    public function show($request, $response, $id)
    {
        $cosmetologist = Cosmetologist::findById((int)$id);
        
        if (!$cosmetologist) {
            return $response->error('Косметолог не найден', 404);
        }
        
        $services = Cosmetologist::getServices((int)$id);
        
        return $response->success([
            'cosmetologist' => $cosmetologist,
            'services' => $services
        ]);
    }

    /**
     * GET /api/cosmetologists/{id}/services
     * Услуги косметолога
     */
    public function services($request, $response, $id)
    {
        $cosmetologist = Cosmetologist::findById((int)$id);
        
        if (!$cosmetologist) {
            return $response->error('Косметолог не найден', 404);
        }
        
        $services = Cosmetologist::getServices((int)$id);
        
        return $response->success([
            'cosmetologist' => $cosmetologist,
            'services' => $services
        ]);
    }

    /**
     * GET /api/cosmetologists/{id}/slots
     * Доступные слоты
     */
    public function availableSlots($request, $response, $id)
    {
        $date = $request->getQueryParam('date', date('Y-m-d'));
        $serviceId = $request->getQueryParam('service_id');
        
        $slots = Cosmetologist::getAvailableSlots((int)$id, $date);
        
        return $response->success(['slots' => $slots]);
    }

    /**
     * GET /api/cosmetologist/statistics
     * Статистика косметолога
     */
    public function statistics($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        
        if (!$cosmetologistId) {
            return $response->error('Косметолог не найден', 404);
        }
        
        $today = date('Y-m-d');
        $bookings = Booking::findByCosmetologist($cosmetologistId, $today);
        
        $stats = [
            'today_bookings' => count($bookings),
            'pending' => count(array_filter($bookings, fn($b) => $b['status'] === 'pending')),
            'confirmed' => count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed')),
            'completed_today' => count(array_filter($bookings, fn($b) => $b['status'] === 'completed')),
            'cancelled' => count(array_filter($bookings, fn($b) => $b['status'] === 'cancelled')),
            'today_revenue' => array_sum(array_map(function($b) {
                return $b['status'] === 'completed' ? (float)($b['price'] ?? 0) : 0;
            }, $bookings))
        ];
        
        return $response->success($stats);
    }

    /**
     * GET /api/cosmetologist/bookings
     * Записи косметолога
     */
public function bookings($request, $response)
{
    $cosmetologistId = $this->getCosmetologistId($request);
    
    if (!$cosmetologistId) {
        return $response->error('Косметолог не найден', 404);
    }
    
    $date = $request->getQueryParam('date');
    $status = $request->getQueryParam('status');
    $month = $request->getQueryParam('month');
    $page = (int)($request->getQueryParam('page', 1));
    $limit = (int)($request->getQueryParam('limit', 20));
    $offset = ($page - 1) * $limit;
    
    // Записи за месяц для календаря
    if ($month) {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $sql = "SELECT b.schedule, b.status, c.user_id AS cosmetologist_user_id,
                       s.service AS service_name, cl.fullname AS client_name, b.price
                FROM Books b
                JOIN Cosmetologist c ON b.cosmetologist_id = c.id
                JOIN Services s ON b.service_id = s.id
                JOIN Clients cl ON b.client_id = cl.id
                WHERE DATE(b.schedule) BETWEEN ? AND ?
                ORDER BY b.schedule";
        
        $monthBookings = Database::fetchAll($sql, [$startDate, $endDate], 'ss');
        return $response->success(['month_bookings' => $monthBookings]);
    }
    
    // Записи с пагинацией (новые сверху)
    $bookings = Booking::findByCosmetologistPaginated(
        $cosmetologistId, $date, $status, $limit, $offset
    );
    
    $total = Booking::countByCosmetologist($cosmetologistId, $date, $status);
    
    return $response->success([
        'bookings' => $bookings,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'has_more' => ($offset + $limit) < $total
    ]);
}

    /**
     * PUT /api/cosmetologist/bookings/{id}/confirm
     * Подтвердить запись
     */
    public function confirmBooking($request, $response, $id)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        
        $booking = Booking::findById((int)$id);
        
        if (!$booking || (int)$booking['cosmetologist_id'] !== $cosmetologistId) {
            return $response->error('Запись не найдена', 404);
        }
        
        Booking::updateStatus((int)$id, 'confirmed');
        
        LoggerService::info('Booking confirmed', ['booking_id' => $id]);
        
        return $response->success(null, 'Запись подтверждена');
    }

    /**
     * PUT /api/cosmetologist/bookings/{id}/complete
     * Завершить запись
     */
    public function completeBooking($request, $response, $id)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        
        $booking = Booking::findById((int)$id);
        
        if (!$booking || (int)$booking['cosmetologist_id'] !== $cosmetologistId) {
            return $response->error('Запись не найдена', 404);
        }
        
        Booking::updateStatus((int)$id, 'completed');
        
        LoggerService::info('Booking completed', ['booking_id' => $id]);
        
        return $response->success(null, 'Запись завершена');
    }

    /**
     * GET /api/cosmetologist/services
     * Услуги косметолога (для управления)
     */
    public function getServices($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        
        $services = Service::findByCosmetologist($cosmetologistId);
        
        return $response->success(['services' => $services]);
    }

    /**
     * POST /api/services
     * Создать услугу
     */
    public function createService($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $data = $request->getBody();
        
        $serviceId = Service::create([
            'cosmetologist_id' => $cosmetologistId,
            'service' => $data['service'] ?? '',
            'duration' => $data['duration'] ?? '01:00:00',
            'break_time' => $data['break_time'] ?? null,
            'price' => (float)($data['price'] ?? 0),
            'description' => $data['description'] ?? null
        ]);
        
        return $response->success(['service_id' => $serviceId], 'Услуга создана');
    }

    /**
     * PUT /api/services/{id}
     * Обновить услугу
     */
    public function updateService($request, $response, $id)
    {
        $data = $request->getBody();
        
        Service::update((int)$id, [
            'service' => $data['service'] ?? '',
            'duration' => $data['duration'] ?? '01:00:00',
            'break_time' => $data['break_time'] ?? null,
            'price' => (float)($data['price'] ?? 0),
            'description' => $data['description'] ?? null
        ]);
        
        return $response->success(null, 'Услуга обновлена');
    }

    /**
     * DELETE /api/services/{id}
     * Удалить услугу
     */
    public function deleteService($request, $response, $id)
    {
        Service::delete((int)$id);
        
        return $response->success(null, 'Услуга удалена');
    }

    /**
     * GET /api/cosmetologist/materials
     * Материалы косметолога
     */
    public function materials($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        
        $materials = Material::getAll($cosmetologistId);
        
        return $response->success(['materials' => $materials]);
    }

    /**
     * POST /api/cosmetologist/materials
     * Добавить материал
     */
    public function addMaterial($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $data = $request->getBody();
        
        $materialId = Material::create(
            $data['material'] ?? '',
            $cosmetologistId,
            $data['is_stock'] ?? false,
            $data['is_mutable'] ?? true
        );
        
        return $response->success(['material_id' => $materialId], 'Материал добавлен');
    }

    /**
     * GET /api/cosmetologist/clients
     * Клиенты косметолога
     */
    public function clients($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $search = $request->getQueryParam('search', '');
        $sort = $request->getQueryParam('sort', 'recent');
        
        $clients = Client::getByCosmetologist($cosmetologistId, $search, $sort);
        
        return $response->success(['clients' => $clients]);
    }

    /**
     * GET /api/cosmetologist/schedule
     * Расписание
     */
    public function schedule($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $date = $request->getQueryParam('date');
        
        $slots = Cosmetologist::getAvailableSlots($cosmetologistId, $date);
        
        return $response->success(['slots' => $slots]);
    }

    /**
     * GET /api/cosmetologist/reports
     * Отчеты
     */
    public function reports($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $startDate = $request->getQueryParam('start_date', date('Y-m-01'));
        $endDate = $request->getQueryParam('end_date', date('Y-m-t'));
        
        $bookings = Booking::findByCosmetologistPeriod($cosmetologistId, $startDate, $endDate);
        
        $totalRevenue = 0;
        $completedCount = 0;
        
        foreach ($bookings as $b) {
            if ($b['status'] === 'completed') {
                $totalRevenue += (float)($b['price'] ?? 0);
                $completedCount++;
            }
        }
        
        return $response->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_bookings' => count($bookings),
            'completed_count' => $completedCount,
            'total_revenue' => $totalRevenue
        ]);
    }

    /**
     * PUT /api/cosmetologist/materials/{id}
     * Обновить материал
     */
    public function updateMaterial($request, $response, $id)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $data = $request->getBody();
        
        // Если передан только is_stock - обновляем только его
        if (isset($data['is_stock']) && !isset($data['material'])) {
            Material::updateStockStatus((int)$id, (bool)$data['is_stock']);
        } else {
            Material::update(
                (int)$id,
                $data['material'] ?? '',
                $data['is_stock'] ?? false,
                $data['is_mutable'] ?? true
            );
        }
        
        return $response->success(null, 'Материал обновлен');
    }

    /**
     * DELETE /api/cosmetologist/materials/{id}
     * Удалить материал
     */
    public function deleteMaterial($request, $response, $id)
    {
        try {
            Material::delete((int)$id);
            return $response->success(null, 'Материал удален');
        } catch (\Exception $e) {
            return $response->error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/cosmetologist/procurements
     * Добавить закупку
     */
    public function addProcurement($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $data = $request->getBody();
        
        $result = Procurement::create(
            (int)($data['material_id'] ?? 0),
            (float)($data['price'] ?? 0),
            $cosmetologistId
        );
        
        if ($result) {
            return $response->success(null, 'Закупка добавлена');
        }
        
        return $response->error('Ошибка добавления закупки', 400);
    }
    
    /**
     * GET /api/cosmetologist/clients/{id}
     * Детали клиента и история
     */
    public function clientDetails($request, $response, $id)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        
        $client = Client::getDetails((int)$id, $cosmetologistId);
        
        if (!$client) {
            return $response->error('Клиент не найден', 404);
        }
        
        $history = Client::getHistory((int)$id, $cosmetologistId);
        
        return $response->success([
            'client' => $client,
            'history' => $history
        ]);
    }

    /**
     * POST /backend/public/api/cosmetologist/clients
     * Создание клиента от косметолога
     */

    public function addClient($request, $response)
    {
        // Включаем вывод ошибок
        error_reporting(E_ALL);
        ini_set('display_errors', 0); // Не показывать, а логировать
        
        $cosmetologistId = $this->getCosmetologistId($request);
        $data = $request->getBody();
        
        LoggerService::info('Adding client', [
            'cosmetologist_id' => $cosmetologistId,
            'data' => $data
        ]);
        
        try {
            $clientId = Client::create([
                'creator_id' => $cosmetologistId,
                'fullname' => $data['fullname'] ?? '',
                'phone' => $data['phone'] ?? '',
                'communication' => $data['communication'] ?? 'phone'
            ]);
            
            if ($clientId) {
                return $response->success(['client_id' => $clientId], 'Клиент добавлен');
            }
            
            return $response->error('Ошибка добавления клиента', 400);
            
        } catch (\Exception $e) {
            LoggerService::error('Add client exception: ' . $e->getMessage());
            LoggerService::error('Trace: ' . $e->getTraceAsString());
            return $response->error($e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/cosmetologist/clients/{id}
     * Обновить клиента
     */
    public function updateClient($request, $response, $id)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $data = $request->getBody();
        
        try {
            Client::update((int)$id, [
                'fullname' => $data['fullname'] ?? '',
                'phone' => $data['phone'] ?? '',
                'communication' => $data['communication'] ?? 'phone'
            ]);
            
            LoggerService::info('Client updated', ['client_id' => $id]);
            
            return $response->success(null, 'Клиент обновлен');
            
        } catch (\Exception $e) {
            LoggerService::error('Update client error: ' . $e->getMessage());
            return $response->error($e->getMessage(), 400);
        }
    }

    
}