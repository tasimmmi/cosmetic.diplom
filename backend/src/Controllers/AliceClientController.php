<?php
namespace App\Controllers;

use App\Models\Client;
use App\Models\Cosmetologist;
use App\Models\Booking;
use App\Models\Service;

class AliceClientController
{
    public function handle(array $data, array $user): array
    {
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        $session = $data['session'] ?? [];
        $state = $session['state'] ?? [];
        $clientId = $user['client_id'] ?? null;
        
        if (!$clientId) {
            return $this->textResponse('Профиль клиента не найден.', true);
        }
        
        // Записаться
        if ($this->matchCommand($command, ['записаться', 'запись', 'запиши', 'хочу записаться'])) {
            return $this->handleBooking($data, $user, $clientId);
        }
        
        // Мои записи
        if ($this->matchCommand($command, ['мои записи', 'записи', 'ближайшие', 'предстоящие'])) {
            return $this->handleMyBookings($clientId);
        }
        
        // Подтвердить запись
        if ($this->matchCommand($command, ['подтвердить', 'подтверждаю'])) {
            return $this->handleConfirmBooking($data, $clientId);
        }
        
        // Отменить запись
        if ($this->matchCommand($command, ['отменить', 'отмена'])) {
            return $this->handleCancelBooking($data, $clientId);
        }
        
        // Косметологи
        if ($this->matchCommand($command, ['косметолог', 'косметологи', 'специалист'])) {
            return $this->handleCosmetologists();
        }
        
        // Услуги
        if ($this->matchCommand($command, ['услуги', 'прайс', 'цены'])) {
            return $this->handleServices();
        }
        
        return $this->textResponse('Неизвестная команда. Скажите «Помощь».', false);
    }

    private function handleBooking(array $data, array $user, int $clientId): array
    {
        $session = $data['session'] ?? [];
        $state = $session['state'] ?? [];
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        
        // Шаг 1: выбор косметолога (если не указан — предлагаем того, к кому уже ходили)
        if (empty($state['cosmetologist_id'])) {
            // Ищем косметолога по имени в команде
            $cosmetologist = $this->findCosmetologistByName($command);
            
            if ($cosmetologist) {
                $state['cosmetologist_id'] = $cosmetologist['id'];
                $state['cosmetologist_name'] = $cosmetologist['first_name'] . ' ' . $cosmetologist['last_name'];
                $session['state'] = $state;
            } else {
                // Предлагаем предыдущего косметолога
                $lastBooking = Booking::findByClientId($clientId);
                $prevCosmId = !empty($lastBooking) ? $lastBooking[0]['cosmetologist_id'] : null;
                
                $cosmetologists = Cosmetologist::findAll();
                $buttons = [];
                
                foreach ($cosmetologists as $c) {
                    $name = $c['first_name'] . ' ' . $c['last_name'];
                    $buttons[] = ['title' => $name, 'hide' => true];
                }
                
                $state['step'] = 'choose_cosmetologist';
                $session['state'] = $state;
                
                $text = 'К какому косметологу хотите записаться?';
                if ($prevCosmId) {
                    $prevCosm = Cosmetologist::findById($prevCosmId);
                    if ($prevCosm) {
                        $text .= ' Например, к ' . $prevCosm['first_name'] . ' ' . $prevCosm['last_name'] . '?';
                    }
                }
                
                return [
                    'response' => [
                        'text' => $text,
                        'tts' => 'К какому косметологу?',
                        'buttons' => $buttons,
                        'end_session' => false
                    ],
                    'session' => $session,
                    'version' => '1.0'
                ];
            }
        }
        
        // Шаг 2: выбор услуги
        if (empty($state['service_id'])) {
            $services = Cosmetologist::getServices($state['cosmetologist_id']);
            $service = $this->findServiceByName($command, $services);
            
            if ($service) {
                $state['service_id'] = $service['id'];
                $state['service_name'] = $service['service'];
                $session['state'] = $state;
            } else {
                $buttons = [];
                foreach ($services as $s) {
                    $buttons[] = ['title' => $s['service'], 'hide' => true];
                }
                
                $state['step'] = 'choose_service';
                $session['state'] = $state;
                
                $text = 'У косметолога ' . $state['cosmetologist_name'] . ' доступны: ' 
                      . implode(', ', array_column($services, 'service')) . '. Какую услугу выберете?';
                
                return [
                    'response' => [
                        'text' => $text,
                        'tts' => 'Какую услугу выберете?',
                        'buttons' => $buttons,
                        'end_session' => false
                    ],
                    'session' => $session,
                    'version' => '1.0'
                ];
            }
        }
        
        // TODO: выбор даты и времени, создание записи
        return $this->textResponse('Функция записи в разработке.', true);
    }

    private function handleMyBookings(int $clientId): array
    {
        $bookings = Booking::findByClientId($clientId);
        
        if (empty($bookings)) {
            return $this->textResponse('У вас пока нет записей.', true);
        }
        
        $lines = [];
        foreach (array_slice($bookings, 0, 5) as $b) {
            $date = date('d.m', strtotime($b['schedule']));
            $time = date('H:i', strtotime($b['schedule']));
            $status = ['pending' => 'ожидает', 'confirmed' => 'подтверждена', 'completed' => 'завершена', 'cancelled' => 'отменена'][$b['status']] ?? $b['status'];
            $lines[] = "{$date} в {$time} — {$b['service_name']} ({$status})";
        }
        
        return $this->textResponse('Ваши записи: ' . implode('. ', $lines) . '.', true);
    }

    private function handleConfirmBooking(array $data, int $clientId): array
    {
        $pending = Booking::findByClientId($clientId, 'pending');
        
        if (empty($pending)) {
            return $this->textResponse('У вас нет записей, ожидающих подтверждения.', true);
        }
        
        if (count($pending) === 1) {
            $b = $pending[0];
            Booking::updateStatus($b['id'], 'confirmed');
            $date = date('d.m', strtotime($b['schedule']));
            $time = date('H:i', strtotime($b['schedule']));
            return $this->textResponse("Запись на {$date} в {$time} подтверждена.", true);
        }
        
        $buttons = [];
        foreach ($pending as $b) {
            $date = date('d.m H:i', strtotime($b['schedule']));
            $buttons[] = ['title' => $date, 'hide' => true];
        }
        
        return [
            'response' => [
                'text' => 'У вас несколько записей. Какую подтвердить?',
                'tts' => 'Какую запись подтвердить?',
                'buttons' => $buttons,
                'end_session' => false
            ],
            'session' => $data['session'] ?? [],
            'version' => '1.0'
        ];
    }

    private function handleCancelBooking(array $data, int $clientId): array
    {
        $active = array_merge(
            Booking::findByClientId($clientId, 'pending'),
            Booking::findByClientId($clientId, 'confirmed')
        );
        
        if (empty($active)) {
            return $this->textResponse('У вас нет активных записей для отмены.', true);
        }
        
        if (count($active) === 1) {
            Booking::updateStatus($active[0]['id'], 'cancelled');
            $date = date('d.m H:i', strtotime($active[0]['schedule']));
            return $this->textResponse("Запись на {$date} отменена.", true);
        }
        
        $buttons = [];
        foreach ($active as $b) {
            $date = date('d.m H:i', strtotime($b['schedule']));
            $buttons[] = ['title' => $date, 'hide' => true];
        }
        
        return [
            'response' => [
                'text' => 'Какую запись отменить?',
                'tts' => 'Какую запись отменить?',
                'buttons' => $buttons,
                'end_session' => false
            ],
            'session' => $data['session'] ?? [],
            'version' => '1.0'
        ];
    }

    private function handleCosmetologists(): array
    {
        $cosmetologists = Cosmetologist::findAll();
        if (empty($cosmetologists)) {
            return $this->textResponse('Нет доступных косметологов.', true);
        }
        $lines = array_map(function($c) {
            return $c['first_name'] . ' ' . $c['last_name'] . ' — ' . ($c['address'] ?? 'адрес не указан');
        }, $cosmetologists);
        return $this->textResponse(implode('. ', $lines) . '.', true);
    }

    private function handleServices(): array
    {
        $cosmetologists = Cosmetologist::findAll();
        $lines = [];
        foreach ($cosmetologists as $c) {
            $services = Cosmetologist::getServices($c['id']);
            foreach ($services as $s) {
                $lines[] = $c['first_name'] . ': ' . $s['service'] . ' — ' . $s['price'] . ' BYN';
            }
        }
        if (empty($lines)) return $this->textResponse('Услуг пока нет.', true);
        return $this->textResponse(implode('. ', $lines) . '.', true);
    }

    private function findCosmetologistByName(string $name): ?array
    {
        $cosmetologists = Cosmetologist::findAll();
        $name = mb_strtolower(trim($name));
        foreach ($cosmetologists as $c) {
            $full = mb_strtolower($c['first_name'] . ' ' . $c['last_name']);
            if (mb_strpos($full, $name) !== false || mb_strpos($name, mb_strtolower($c['first_name'])) !== false) {
                return $c;
            }
        }
        return null;
    }

    private function findServiceByName(string $name, array $services): ?array
    {
        $name = mb_strtolower(trim($name));
        foreach ($services as $s) {
            if (mb_strpos(mb_strtolower($s['service']), $name) !== false) return $s;
        }
        return null;
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