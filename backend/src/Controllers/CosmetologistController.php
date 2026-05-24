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
    private function getCosmetologistId($request)
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            try {
                $tokenService = new \App\Services\TokenService();
                $decoded = $tokenService->verifyAccessToken($matches[1]);
                $userId = (int)($decoded['user_id'] ?? 0);
                
                if ($userId > 0) {
                    $user = User::findById($userId);
                    if ($user && !empty($user['cosmetologist_id'])) {
                        return (int)$user['cosmetologist_id'];
                    }
                }
            } catch (\Exception $e) {
                LoggerService::error('Token decode error: ' . $e->getMessage());
            }
        }
        
        return 0;
    }

    /** GET /api/cosmetologists */
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

    /** GET /api/cosmetologists/{id} */
    public function show($request, $response, $id)
    {
        $cosmetologist = Cosmetologist::findById((int)$id);
        if (!$cosmetologist) return $response->error('Косметолог не найден', 404);
        
        return $response->success([
            'cosmetologist' => $cosmetologist,
            'services' => Cosmetologist::getServices((int)$id)
        ]);
    }

    /** GET /api/cosmetologists/{id}/services */
    public function services($request, $response, $id)
    {
        $cosmetologist = Cosmetologist::findById((int)$id);
        if (!$cosmetologist) return $response->error('Косметолог не найден', 404);
        
        return $response->success([
            'cosmetologist' => $cosmetologist,
            'services' => Cosmetologist::getServices((int)$id)
        ]);
    }

    /** GET /api/cosmetologist/statistics */
    public function statistics($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        
        $bookings = Booking::findByCosmetologist($cosmetologistId, date('Y-m-d'));
        
        return $response->success([
            'today_bookings' => count($bookings),
            'pending' => count(array_filter($bookings, fn($b) => $b['status'] === 'pending')),
            'confirmed' => count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed')),
            'completed_today' => count(array_filter($bookings, fn($b) => $b['status'] === 'completed')),
            'cancelled' => count(array_filter($bookings, fn($b) => $b['status'] === 'cancelled')),
            'today_revenue' => array_sum(array_map(fn($b) => $b['status'] === 'completed' ? (float)($b['price'] ?? 0) : 0, $bookings))
        ]);
    }

    /** GET /api/cosmetologist/bookings */
    public function bookings($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        
        $date = $request->getQueryParam('date');
        $status = $request->getQueryParam('status');
        $month = $request->getQueryParam('month');
        $page = (int)($request->getQueryParam('page', 1));
        $limit = (int)($request->getQueryParam('limit', 20));
        $offset = ($page - 1) * $limit;
        
        if ($month) {
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $sql = "SELECT b.schedule, b.status, b.id, b.client_id,
                           c.user_id AS cosmetologist_user_id,
                           s.service AS service_name, cl.fullname AS client_name, 
                           cl.phone AS client_phone, b.price, b.description
                    FROM Books b
                    JOIN Cosmetologist c ON b.cosmetologist_id = c.id
                    JOIN Services s ON b.service_id = s.id
                    JOIN Clients cl ON b.client_id = cl.id
                    WHERE b.cosmetologist_id = ? AND DATE(b.schedule) BETWEEN ? AND ?
                    ORDER BY b.schedule";
            
            return $response->success(['month_bookings' => Database::fetchAll($sql, [$cosmetologistId, $startDate, $endDate], 'iss')]);
        }
        
        $bookings = Booking::findByCosmetologistPaginated($cosmetologistId, $date, $status, $limit, $offset);
        $total = Booking::countByCosmetologist($cosmetologistId, $date, $status);
        
        return $response->success([
            'bookings' => $bookings,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'has_more' => ($offset + $limit) < $total
        ]);
    }

    /** PUT /api/cosmetologist/bookings/{id}/confirm */
    public function confirmBooking($request, $response, $id)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $booking = Booking::findById((int)$id);
        
        if (!$booking || (int)$booking['cosmetologist_id'] !== $cosmetologistId) {
            return $response->error('Запись не найдена', 404);
        }
        
        Booking::updateStatus((int)$id, 'confirmed');
        return $response->success(null, 'Запись подтверждена');
    }

    /** PUT /api/cosmetologist/bookings/{id}/complete */
    public function completeBooking($request, $response, $id)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $booking = Booking::findById((int)$id);
        
        if (!$booking || (int)$booking['cosmetologist_id'] !== $cosmetologistId) {
            return $response->error('Запись не найдена', 404);
        }
        
        Booking::updateStatus((int)$id, 'completed');
        return $response->success(null, 'Запись завершена');
    }

    /** GET /api/cosmetologist/services */
    public function getServices($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        return $response->success(['services' => Service::findByCosmetologist($cosmetologistId)]);
    }

    /** POST /api/services */
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

    /** PUT /api/services/{id} */
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

    /** DELETE /api/services/{id} */
    public function deleteService($request, $response, $id)
    {
        Service::delete((int)$id);
        return $response->success(null, 'Услуга удалена');
    }

    /** GET /api/cosmetologist/materials */
    public function materials($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        return $response->success(['materials' => Material::getAll($cosmetologistId)]);
    }

    /** POST /api/cosmetologist/materials */
    public function addMaterial($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $data = $request->getBody();
        
        $materialId = Material::create($data['material'] ?? '', $cosmetologistId, $data['is_stock'] ?? false, $data['is_mutable'] ?? true);
        return $response->success(['material_id' => $materialId], 'Материал добавлен');
    }

    /** PUT /api/cosmetologist/materials/{id} */
    public function updateMaterial($request, $response, $id)
    {
        $data = $request->getBody();
        
        if (isset($data['is_stock']) && !isset($data['material'])) {
            Material::updateStockStatus((int)$id, (bool)$data['is_stock']);
        } else {
            Material::update((int)$id, $data['material'] ?? '', $data['is_stock'] ?? false, $data['is_mutable'] ?? true);
        }
        return $response->success(null, 'Материал обновлен');
    }

    /** DELETE /api/cosmetologist/materials/{id} */
    public function deleteMaterial($request, $response, $id)
    {
        try {
            Material::delete((int)$id);
            return $response->success(null, 'Материал удален');
        } catch (\Exception $e) {
            return $response->error($e->getMessage(), 400);
        }
    }

    /** POST /api/cosmetologist/procurements */
    public function addProcurement($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        $data = $request->getBody();
        
        if (Procurement::create((int)($data['material_id'] ?? 0), (float)($data['price'] ?? 0), $cosmetologistId)) {
            return $response->success(null, 'Закупка добавлена');
        }
        return $response->error('Ошибка добавления закупки', 400);
    }

    /** GET /api/cosmetologist/clients */
    public function clients($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        
        $search = $request->getQueryParam('search', '');
        $sort = $request->getQueryParam('sort', 'recent');
        
        return $response->success(['clients' => Client::getByCosmetologistId($cosmetologistId, $search, $sort)]);
    }

    /** GET /api/cosmetologist/clients/{id} */
    public function clientDetails($request, $response, $id)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        
        $client = Client::findInView((int)$id, $cosmetologistId);
        if (!$client) return $response->error('Клиент не найден или недоступен', 404);
        
        return $response->success([
            'client' => $client,
            'history' => Client::getHistoryWithCosmetologist((int)$id, $cosmetologistId)
        ]);
    }

    /** POST /api/cosmetologist/clients */
    public function addClient($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        
        $data = $request->getBody();
        
        try {
            $clientId = Client::create([
                'creator_id' => $cosmetologistId,
                'fullname' => $data['fullname'] ?? '',
                'phone' => $data['phone'] ?? '',
                'communication' => $data['communication'] ?? 'phone'
            ]);
            
            if ($clientId) return $response->success(['client_id' => $clientId], 'Клиент добавлен');
            return $response->error('Ошибка добавления клиента', 400);
        } catch (\Exception $e) {
            LoggerService::error('Add client failed: ' . $e->getMessage());
            return $response->error($e->getMessage(), 500);
        }
    }

    /** PUT /api/cosmetologist/clients/{id} */
    public function updateClient($request, $response, $id)
    {
        $cosmetologistId = $this->getCosmetologistId($request);
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        
        $data = $request->getBody();
        
        try {
            $client = Client::findInView((int)$id, $cosmetologistId);
            if (!$client) return $response->error('Клиент не найден или недоступен', 404);
            
            Client::update((int)$id, [
                'fullname' => $data['fullname'] ?? '',
                'phone' => $data['phone'] ?? '',
                'communication' => $data['communication'] ?? 'phone'
            ]);
            
            return $response->success(null, 'Клиент обновлен');
        } catch (\Exception $e) {
            LoggerService::error('Update client failed: ' . $e->getMessage());
            return $response->error($e->getMessage(), 400);
        }
    }

    /** GET /api/cosmetologist/reports */
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
}