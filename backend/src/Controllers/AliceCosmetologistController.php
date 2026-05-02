<?php
namespace App\Controllers;

use App\Models\Cosmetologist;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Material;
use App\Models\Procurement;
use App\Models\Schedule;

class AliceCosmetologistController
{
    public function handle(array $data, array $user): array
    {
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        $cosmetologistId = $user['cosmetologist_id'] ?? null;
        
        if (!$cosmetologistId) {
            return $this->textResponse('Профиль косметолога не найден.', true);
        }
        
        // Записи на сегодня
        if ($this->matchCommand($command, ['записи на сегодня', 'сегодня', 'записи'])) {
            return $this->handleTodayBookings($cosmetologistId);
        }
        
        // Расписание
        if ($this->matchCommand($command, ['расписание', 'график'])) {
            return $this->handleSchedule($cosmetologistId);
        }
        
        // Добавить запись
        if ($this->matchCommand($command, ['добавить запись', 'новая запись', 'записать клиента'])) {
            return $this->handleAddBooking($data, $cosmetologistId);
        }
        
        // Добавить клиента
        if ($this->matchCommand($command, ['добавить клиента', 'новый клиент'])) {
            return $this->handleAddClient($data, $cosmetologistId);
        }
        
        // Материалы
        if ($this->matchCommand($command, ['материалы', 'склад'])) {
            return $this->handleMaterials($cosmetologistId);
        }
        
        // Добавить закупку
        if ($this->matchCommand($command, ['добавить закупку', 'закупка', 'купить материал'])) {
            return $this->handleAddProcurement($data, $cosmetologistId);
        }
        
        // Списать материал
        if ($this->matchCommand($command, ['списать', 'израсходовать', 'потратить'])) {
            return $this->handleWriteOff($data, $cosmetologistId);
        }
        
        // Отчёты
        if ($this->matchCommand($command, ['отчёт', 'отчет', 'статистика'])) {
            return $this->handleReports($cosmetologistId);
        }
        
        return $this->textResponse('Неизвестная команда. Скажите «Помощь».', false);
    }

    private function handleTodayBookings(int $cosmetologistId): array
    {
        $today = date('Y-m-d');
        $bookings = Booking::findByCosmetologist($cosmetologistId, $today);
        
        if (empty($bookings)) {
            return $this->textResponse('На сегодня записей нет.', true);
        }
        
        $lines = [];
        foreach ($bookings as $b) {
            $time = date('H:i', strtotime($b['schedule']));
            $status = ['pending' => '⏳', 'confirmed' => '✅', 'completed' => '✔️', 'cancelled' => '❌'][$b['status']] ?? '';
            $lines[] = "{$time} — {$b['client_name']}: {$b['service_name']} {$status}";
        }
        
        return $this->textResponse('Записи на сегодня: ' . implode('. ', $lines) . '.', true);
    }

    private function handleSchedule(int $cosmetologistId): array
    {
        $today = date('Y-m-d');
        $slots = Schedule::getByCosmetologist($cosmetologistId, $today);
        $stats = Schedule::getDayStats($cosmetologistId, $today);
        
        if ($stats['total'] == 0) {
            return $this->textResponse('На сегодня расписание не настроено.', true);
        }
        
        return $this->textResponse(
            "Расписание на сегодня: всего {$stats['total']} окон, "
            . "свободно {$stats['free']}, занято {$stats['booked']}, отключено {$stats['deactivated']}.",
            true
        );
    }

    private function handleAddBooking(array $data, int $cosmetologistId): array
    {
        return $this->textResponse(
            'Для создания записи используйте сайт или скажите «Добавить клиента» чтобы сначала создать клиента.',
            true
        );
    }

    private function handleAddClient(array $data, int $cosmetologistId): array
    {
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        $session = $data['session'] ?? [];
        $state = $session['state'] ?? [];
        
        // Извлекаем имя и телефон из команды
        // Пример: "Добавить клиента Иванова Анна телефон 375291234567"
        preg_match('/телефон\s*(\+?\d+)/', $command, $phoneMatch);
        $phone = $phoneMatch[1] ?? '';
        
        // Убираем служебные слова
        $nameStr = str_replace(['добавить клиента', 'новый клиент', 'телефон', $phone, '  '], '', $command);
        $nameStr = trim($nameStr);
        
        if (empty($nameStr) || empty($phone)) {
            $session['state'] = ['step' => 'add_client'];
            return [
                'response' => [
                    'text' => 'Назовите имя и телефон клиента. Например: «Иванова Анна телефон 375291234567»',
                    'tts' => 'Назовите имя и телефон клиента',
                    'end_session' => false
                ],
                'session' => $session,
                'version' => '1.0'
            ];
        }
        
        try {
            $clientId = Client::create([
                'creator_id' => $cosmetologistId,
                'fullname' => $nameStr,
                'phone' => $phone,
                'communication' => 'phone'
            ]);
            
            return $this->textResponse("Клиент {$nameStr} добавлен.", true);
        } catch (\Exception $e) {
            return $this->textResponse('Ошибка добавления клиента.', true);
        }
    }

    private function handleMaterials(int $cosmetologistId): array
    {
        $materials = Material::getAll($cosmetologistId);
        
        if (empty($materials)) {
            return $this->textResponse('У вас пока нет материалов.', true);
        }
        
        $lines = [];
        foreach ($materials as $m) {
            $status = $m['is_stock'] ? 'в наличии' : 'закончился';
            $lines[] = "{$m['material']} ({$status})";
        }
        
        return $this->textResponse('Материалы: ' . implode('. ', $lines) . '.', true);
    }

    private function handleAddProcurement(array $data, int $cosmetologistId): array
    {
        return $this->textResponse(
            'Для добавления закупки используйте сайт в разделе «Склад».',
            true
        );
    }

    private function handleWriteOff(array $data, int $cosmetologistId): array
    {
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        
        $materials = Material::getAll($cosmetologistId);
        $found = null;
        
        foreach ($materials as $m) {
            if (mb_strpos($command, mb_strtolower($m['material'])) !== false) {
                $found = $m;
                break;
            }
        }
        
        if ($found) {
            Material::update($found['id'], $found['material'], false, $found['is_mutable']);
            return $this->textResponse("Материал «{$found['material']}» списан.", true);
        }
        
        return $this->textResponse('Назовите материал для списания. Например: «Списать ватные диски».', false);
    }

    private function handleReports(int $cosmetologistId): array
    {
        $today = date('Y-m-d');
        $bookings = Booking::findByCosmetologist($cosmetologistId, $today);
        
        $completed = array_filter($bookings, function($b) { return $b['status'] === 'completed'; });
        $revenue = array_sum(array_column($completed, 'price'));
        
        $pending = count(array_filter($bookings, function($b) { return $b['status'] === 'pending'; }));
        $confirmed = count(array_filter($bookings, function($b) { return $b['status'] === 'confirmed'; }));
        
        return $this->textResponse(
            "Сегодня: ожидают {$pending}, подтверждены {$confirmed}, "
            . "завершены " . count($completed) . ", выручка {$revenue} BYN.",
            true
        );
    }

    private function matchCommand(string $command, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (mb_strpos($command, $kw) !== false) return true;
        }
        return false;
    }

    private function textResponse(string $text, bool $endSession): array
    {
        return [
            'response' => ['text' => $text, 'tts' => $text, 'end_session' => $endSession],
            'session' => [],
            'version' => '1.0'
        ];
    }
}