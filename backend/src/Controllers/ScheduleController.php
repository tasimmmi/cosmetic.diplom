<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Schedule;
use App\Services\LoggerService;
use App\Services\TokenService;

class ScheduleController
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
                LoggerService::error('Token decode error: ' . $e->getMessage());
            }
        }
        return 0;
    }

    public function index($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId();
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        $date = $request->getQueryParam('date', date('Y-m-d'));
        return $response->success([
            'slots' => Schedule::getByCosmetologist($cosmetologistId, $date),
            'stats' => Schedule::getDayStats($cosmetologistId, $date),
            'date' => $date
        ]);
    }

    public function allByDate($request, $response)
    {
        $date = $request->getQueryParam('date', date('Y-m-d'));
        return $response->success(['date' => $date, 'slots' => Schedule::getAllByDate($date)]);
    }

    /**
     * Доступные слоты с учётом длительности услуги
     */
    public function availableSlots($request, $response, $id)
    {
        $date = $request->getQueryParam('date', date('Y-m-d'));
        $serviceId = $request->getQueryParam('service_id');
        
        LoggerService::error('=== availableSlots CALLED ===', [
            'id' => $id,
            'date' => $date,
            'service_id' => $serviceId
        ]);
        
        if (!$serviceId) {
            LoggerService::error('❌ No service_id');
            return $response->error('Укажите service_id', 400);
        }
        
        LoggerService::error('📤 Calling Schedule::getAvailableSlots');
        $slots = Schedule::getAvailableSlots($date, (int)$serviceId);
        
        LoggerService::error('📥 Slots result:', ['count' => count($slots), 'data' => $slots]);
        
        return $response->success(['slots' => $slots]);
    }

    public function generate($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId();
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        $data = $request->getBody();
        $startTime = $data['start_time'] ?? '09:00';
        $endTime = $data['end_time'] ?? '18:00';
        $dates = $data['dates'] ?? [];
        if (empty($dates)) return $response->error('Укажите хотя бы одну дату', 400);
        try {
            $result = Schedule::generate($cosmetologistId, $startTime, $endTime, $dates);
            return $response->success($result, 'Расписание создано');
        } catch (\Exception $e) {
            LoggerService::error('Generate error: ' . $e->getMessage());
            return $response->error($e->getMessage(), 500);
        }
    }

    public function deleteSlot($request, $response, $id)
    {
        if (Schedule::deleteSlot((int)$id)) return $response->success(null, 'Слот удалён');
        return $response->error('Нельзя удалить занятый слот', 400);
    }

    public function clearDay($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId();
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        $date = $request->getBody()['date'] ?? date('Y-m-d');
        return $response->success(['deleted' => Schedule::clearDay($cosmetologistId, $date)], 'День очищен');
    }

    public function deactivateSlot($request, $response, $id)
    {
        if (Schedule::deactivateSlot((int)$id)) return $response->success(null, 'Слот деактивирован');
        return $response->error('Нельзя деактивировать занятый слот', 400);
    }

    public function activateSlot($request, $response, $id)
    {
        if (Schedule::activateSlot((int)$id)) return $response->success(null, 'Слот активирован');
        return $response->error('Слот не найден или уже активен', 400);
    }

    public function deactivateDay($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId();
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        $date = $request->getBody()['date'] ?? date('Y-m-d');
        return $response->success(['deactivated' => Schedule::deactivateDay($cosmetologistId, $date)], 'День деактивирован');
    }

    public function activateDay($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId();
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        $date = $request->getBody()['date'] ?? date('Y-m-d');
        return $response->success(['activated' => Schedule::activateDay($cosmetologistId, $date)], 'День активирован');
    }

    public function getBookedSlots($request, $response)
    {
        $date = $request->getQueryParam('date', date('Y-m-d'));
        $bookedSlots = Schedule::getBookedSlots($date);
        $grouped = [];
        foreach ($bookedSlots as $slot) {
            $cosmId = $slot['cosmetologist_id'];
            if (!isset($grouped[$cosmId])) $grouped[$cosmId] = ['cosmetologist_id' => $cosmId, 'cosmetologist_name' => $slot['cosmetologist_name'], 'slots' => []];
            $grouped[$cosmId]['slots'][] = $slot;
        }
        return $response->success(['date' => $date, 'cosmetologists' => array_values($grouped)]);
    }

    public function getCalendarBooked($request, $response)
    {
        $startDate = $request->getQueryParam('start_date', date('Y-m-01'));
        $endDate = $request->getQueryParam('end_date', date('Y-m-t'));
        return $response->success(['start_date' => $startDate, 'end_date' => $endDate, 'booked' => Schedule::getBookedSlotsForCalendar($startDate, $endDate)]);
    }

    public function getCalendarData($request, $response)
    {
        $startDate = $request->getQueryParam('start_date', date('Y-m-01'));
        $endDate = $request->getQueryParam('end_date', date('Y-m-t'));
        return $response->success(['start_date' => $startDate, 'end_date' => $endDate, 'bookings' => Schedule::getCalendarData($startDate, $endDate)]);
    }

    public function dayStats($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId();
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        $date = $request->getQueryParam('date', date('Y-m-d'));
        return $response->success(['date' => $date, 'stats' => Schedule::getDayStats($cosmetologistId, $date)]);
    }

    /**
     * POST /api/cosmetologist/schedule/check-and-generate
     * Проверить доступность и создать слоты (всё или ничего)
     */
    public function checkAndGenerate($request, $response)
    {
        $cosmetologistId = $this->getCosmetologistId();
        if (!$cosmetologistId) return $response->error('Косметолог не найден', 404);
        
        $data = $request->getBody();
        $startTime = $data['start_time'] ?? '09:00';
        $endTime = $data['end_time'] ?? '18:00';
        $date = $data['date'] ?? date('Y-m-d');
        $serviceId = $data['service_id'] ?? null;
        
        if (!$serviceId) return $response->error('Укажите service_id', 400);
        
        try {
            // Шаг 1: Получаем длительность услуги
            $service = \App\Models\Service::findById((int)$serviceId);
            if (!$service) return $response->error('Услуга не найдена', 404);
            
            // Шаг 2: Проверяем занятые слоты в диапазоне
            $schedule = date('Y-m-d H:i:s', strtotime($date . ' ' . $startTime));
            $endSchedule = date('Y-m-d H:i:s', strtotime($date . ' ' . $endTime));
            
            $conflict = \App\Models\Schedule::checkConflicts($cosmetologistId, $schedule, $endSchedule);
            
            if ($conflict) {
                return $response->error('Время занято', 409);
            }
            
            // Шаг 3: Создаём слоты
            $result = \App\Models\Schedule::generate(
                $cosmetologistId, 
                $startTime, 
                $endTime, 
                [$date]
            );
            
            return $response->success($result, 'Слоты созданы');
            
        } catch (\Exception $e) {
            LoggerService::error('Check and generate error: ' . $e->getMessage());
            return $response->error($e->getMessage(), 500);
        }
    }
}