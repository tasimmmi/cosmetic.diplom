<?php
namespace App\Controllers;

use App\Models\Cosmetologist;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Material;
use App\Models\Schedule;
use App\Services\AliceCommandParser;

class AliceCosmetologistController
{
    public function handle(array $data, array $user): array
    {
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        $session = $data['session'] ?? [];
        $cosmId = $user['cosmetologist_id'] ?? null;
        
        if (!$cosmId) return AliceController::reply('Профиль косметолога не найден.', $session, true);

        if (AliceController::match($command, ['есть ли', 'проверь', 'посмотри', 'что с', 'как там', 'как с'])) {
            return $this->handleMaterials($data, $user);
        }
        if (AliceController::match($command, ['записи', 'сегодня'])) {
            return $this->handleBookings($data, $user);
        }
        if (AliceController::match($command, ['расписание', 'график', 'окошк', 'свободные'])) {
            return $this->handleSchedule($data, $user);
        }
        if (AliceController::match($command, ['материалы', 'склад'])) {
            return $this->handleMaterials($data, $user);
        }
        if (AliceController::match($command, ['услуги', 'прайс', 'цены'])) {
            return $this->handleServices($data, $user);
        }
        if (AliceController::match($command, ['добавить клиента', 'новый клиент'])) {
            return $this->handleAddClient($data, $user);
        }
        if (AliceController::match($command, ['списать', 'израсходовать'])) {
            return $this->handleWriteOff($data, $user);
        }
        if (AliceController::match($command, ['отчёт', 'статистика'])) {
            return $this->handleReports($data, $user);
        }
        
        return AliceController::reply('Не поняла. Скажите «Помощь».', $session);
    }
    
    public function handleBookings(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $cosmId = $user['cosmetologist_id'];
        $date = $data['request']['nlu']['intents']['get_bookings']['slots']['date']['value'] ?? date('Y-m-d');
        $date = $this->parseDate($date);
        
        $bookings = Booking::findByCosmetologist($cosmId, $date);
        
        if (empty($bookings)) return AliceController::reply('На ' . $date . ' записей нет.', $session, true);
        
        $lines = [];
        foreach ($bookings as $b) {
            $t = date('H:i', strtotime($b['schedule']));
            $s = ['pending' => '⏳', 'confirmed' => '✅', 'completed' => '✔️', 'cancelled' => '❌'][$b['status']] ?? '';
            $lines[] = "{$t} — {$b['client_name']}: {$b['service_name']} {$s}";
        }
        
        return AliceController::reply('Записи на ' . $date . ': ' . implode('. ', $lines) . '.', $session, true);
    }
    
    public function handleSchedule(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $cosmId = $user['cosmetologist_id'];
        $date = $data['request']['nlu']['intents']['get_schedule']['slots']['date']['value'] ?? date('Y-m-d');
        $date = $this->parseDate($date);
        
        $stats = Schedule::getDayStats($cosmId, $date);
        
        if ($stats['total'] == 0) return AliceController::reply('На ' . $date . ' расписание не настроено.', $session, true);
        
        return AliceController::reply(
            "На {$date}: всего {$stats['total']} окон, свободно {$stats['free']}, занято {$stats['booked']}, отключено {$stats['deactivated']}.",
            $session, true
        );
    }
    
    public function handleMaterials(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        $cosmId = $user['cosmetologist_id'];
        
        $materials = Material::getAll($cosmId);
        
        if (empty($materials)) {
            return AliceController::reply('У вас пока нет материалов.', $session, false);
        }
        
        $foundMaterial = AliceCommandParser::extractMaterial($command, $materials);
        
        if ($foundMaterial) {
            $status = $foundMaterial['is_stock'] ? 'в наличии' : 'закончился';
            return AliceController::reply("«{$foundMaterial['material']}» {$status}.", $session, false);
        }
        
        if (AliceCommandParser::hasSpecificMaterial($command)) {
            return AliceController::reply('Я не нашла такой материал. Скажите «Материалы» для списка всех материалов.', $session, false);
        }
        
        $lines = array_map(function($m) {
            return $m['material'] . ' (' . ($m['is_stock'] ? 'в наличии' : 'закончился') . ')';
        }, $materials);
        
        return AliceController::reply('Материалы: ' . implode('. ', $lines) . '.', $session, false);
    }
    
    public function handleServices(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $cosmId = $user['cosmetologist_id'];
        
        $services = Cosmetologist::getServices($cosmId);
        if (empty($services)) return AliceController::reply('У вас пока нет услуг.', $session, true);
        
        $lines = array_map(function($s) {
            return $s['service'] . ' — ' . $s['price'] . ' BYN';
        }, $services);
        
        return AliceController::reply('Услуги: ' . implode('. ', $lines) . '.', $session, true);
    }
    
    public function handleAddClient(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        $cosmId = $user['cosmetologist_id'];
        
        $name = trim(str_replace(['добавить клиента', 'новый клиент', 'телефон', 'номер'], '', $command));
        preg_match('/\+?\d{10,15}/', $command, $phoneMatch);
        $phone = $phoneMatch[0] ?? '';
        
        if (empty($name) || empty($phone)) {
            return AliceController::reply('Назовите имя и телефон. Например: «Добавить клиента Иванова Анна телефон 375291234567»', $session);
        }
        
        try {
            Client::create(['creator_id' => $cosmId, 'fullname' => $name, 'phone' => $phone, 'communication' => 'phone']);
            return AliceController::reply("Клиент {$name} добавлен.", $session, true);
        } catch (\Exception $e) {
            return AliceController::reply('Ошибка добавления клиента.', $session, true);
        }
    }
    
    public function handleWriteOff(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        $cosmId = $user['cosmetologist_id'];
        
        $materials = Material::getAll($cosmId);
        foreach ($materials as $m) {
            if (mb_strpos($command, mb_strtolower($m['material'])) !== false) {
                Material::update($m['id'], $m['material'], false, $m['is_mutable']);
                return AliceController::reply("Материал «{$m['material']}» списан.", $session, true);
            }
        }
        
        return AliceController::reply('Назовите материал. Например: «Списать ватные диски».', $session);
    }
    
    public function handleReports(array $data, array $user): array
    {
        $session = $data['session'] ?? [];
        $cosmId = $user['cosmetologist_id'];
        
        $bookings = Booking::findByCosmetologist($cosmId, date('Y-m-d'));
        $completed = array_filter($bookings, fn($b) => $b['status'] === 'completed');
        $revenue = array_sum(array_column($completed, 'price'));
        
        $pending = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
        $confirmed = count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'));
        
        return AliceController::reply(
            "Сегодня: ожидают {$pending}, подтверждены {$confirmed}, завершены " . count($completed) . ", выручка {$revenue} BYN.",
            $session, true
        );
    }
    
    private function parseDate(string $date): string
    {
        $map = [
            'сегодня' => date('Y-m-d'),
            'завтра' => date('Y-m-d', strtotime('+1 day')),
            'послезавтра' => date('Y-m-d', strtotime('+2 days')),
        ];
        
        return $map[$date] ?? $date;
    }
}