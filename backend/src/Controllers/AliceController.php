<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Config\Database;
use App\Services\LoggerService;

class AliceController
{
    /**
     * Webhook для Алисы (Яндекс.Диалоги)
     * POST /api/alice/webhook
     */
    public function webhook(Request $request, Response $response)
    {
        $body = $request->getBody();
        
        // Логируем входящий запрос
        LoggerService::info('Alice webhook called', [
            'session_id' => $body['session']['session_id'] ?? 'unknown',
            'user_id' => $body['session']['user_id'] ?? 'anonymous',
            'command' => $body['request']['command'] ?? ''
        ]);
        
        // Проверяем версию протокола
        if (empty($body['version']) || $body['version'] !== '1.0') {
            return $this->errorResponse($body, 'Неподдерживаемая версия протокола');
        }
        
        $session = $body['session'];
        $request_data = $body['request'];
        $state = $body['state']['session'] ?? [];
        
        // Определяем тип запроса
        if ($request_data['type'] === 'SimpleUtterance') {
            return $this->handleSimpleUtterance($body, $session, $request_data, $state);
        } elseif ($request_data['type'] === 'ButtonPressed') {
            return $this->handleButtonPressed($body, $session, $request_data, $state);
        }
        
        // По умолчанию - приветствие
        return $this->welcomeResponse($body);
    }

    /**
     * Обработка текстовых команд
     */
    private function handleSimpleUtterance($body, $session, $request_data, $state)
    {
        $command = mb_strtolower(trim($request_data['command']));
        $tokens = $request_data['nlu']['tokens'] ?? [];
        
        // Определяем интент
        $intent = $this->detectIntent($command, $tokens);
        
        switch ($intent) {
            case 'book_service':
                return $this->handleBookService($body, $session, $request_data, $state);
                
            case 'my_bookings':
                return $this->handleMyBookings($body, $session, $request_data, $state);
                
            case 'cancel_booking':
                return $this->handleCancelBooking($body, $session, $request_data, $state);
                
            case 'services_list':
                return $this->handleServicesList($body, $session, $request_data, $state);
                
            case 'help':
                return $this->helpResponse($body);
                
            default:
                return $this->fallbackResponse($body);
        }
    }

    /**
     * Обработка нажатия кнопок
     */
    private function handleButtonPressed($body, $session, $request_data, $state)
    {
        $payload = $request_data['payload'] ?? [];
        $action = $payload['action'] ?? '';
        
        switch ($action) {
            case 'book':
                return $this->startBookingFlow($body, $session, $state);
                
            case 'list':
                return $this->handleMyBookings($body, $session, $request_data, $state);
                
            case 'help':
                return $this->helpResponse($body);
                
            default:
                return $this->welcomeResponse($body);
        }
    }

    /**
     * Определить намерение пользователя
     */
    private function detectIntent($command, $tokens)
    {
        $keywords = [
            'book_service' => ['записаться', 'запись', 'запиши', 'забронировать', 'бронь', 'записать'],
            'my_bookings' => ['мои', 'записи', 'бронирования', 'предстоящие', 'запланировано'],
            'cancel_booking' => ['отменить', 'отмена', 'удалить', 'откажись'],
            'services_list' => ['услуги', 'прайс', 'цены', 'стоимость', 'что', 'делаете'],
            'help' => ['помощь', 'помоги', 'что', 'умеешь', 'навыки', 'команды']
        ];
        
        foreach ($keywords as $intent => $words) {
            foreach ($words as $word) {
                if (strpos($command, $word) !== false) {
                    return $intent;
                }
            }
        }
        
        return 'unknown';
    }

    /**
     * Приветственный ответ
     */
    private function welcomeResponse($body)
    {
        $text = "Здравствуйте! Я помогу вам записаться к косметологу. " .
                "Вы можете:\n" .
                "- Записаться на услугу\n" .
                "- Посмотреть ваши записи\n" .
                "- Отменить запись\n" .
                "- Узнать список услуг и цены\n\n" .
                "Что хотите сделать?";
        
        $buttons = [
            ['title' => '📅 Записаться', 'payload' => ['action' => 'book'], 'hide' => true],
            ['title' => '📋 Мои записи', 'payload' => ['action' => 'list'], 'hide' => true],
            ['title' => '❓ Помощь', 'payload' => ['action' => 'help'], 'hide' => true]
        ];
        
        return $this->buildResponse($body, $text, $buttons);
    }

    /**
     * Ответ с помощью
     */
    private function helpResponse($body)
    {
        $text = "Я умею:\n" .
                "🔹 «Запиши меня к косметологу» — создать новую запись\n" .
                "🔹 «Покажи мои записи» — посмотреть предстоящие визиты\n" .
                "🔹 «Отмени запись» — отменить существующую запись\n" .
                "🔹 «Какие услуги» — узнать список услуг и цены\n\n" .
                "Для записи нужно авторизоваться через Яндекс.";
        
        $buttons = [
            ['title' => '📅 Записаться', 'payload' => ['action' => 'book'], 'hide' => true],
            ['title' => '📋 Мои записи', 'payload' => ['action' => 'list'], 'hide' => true]
        ];
        
        return $this->buildResponse($body, $text, $buttons);
    }

    /**
     * Ответ при непонимании
     */
    private function fallbackResponse($body)
    {
        $text = "Извините, я не поняла. Скажите «Помощь», чтобы узнать, что я умею.";
        
        $buttons = [
            ['title' => '❓ Помощь', 'payload' => ['action' => 'help'], 'hide' => true]
        ];
        
        return $this->buildResponse($body, $text, $buttons);
    }

    /**
     * Начать процесс записи
     */
    private function startBookingFlow($body, $session, $state)
    {
        // Проверяем авторизацию
        $userId = $this->getUserIdFromSession($session);
        
        if (!$userId) {
            $text = "Для записи нужно авторизоваться. Откройте приложение Cosmetic и войдите через Яндекс.";
            return $this->buildResponse($body, $text, [], false);
        }
        
        // Получаем список косметологов
        $cosmetologists = $this->getCosmetologists();
        
        if (empty($cosmetologists)) {
            $text = "К сожалению, сейчас нет доступных косметологов. Попробуйте позже.";
            return $this->buildResponse($body, $text, [], false);
        }
        
        $text = "Выберите косметолога:\n";
        foreach ($cosmetologists as $index => $c) {
            $text .= ($index + 1) . ". " . $c['first_name'] . " " . $c['last_name'] . "\n";
        }
        $text .= "\nНазовите номер или имя.";
        
        // Сохраняем состояние в сессию
        $stateData = [
            'step' => 'select_cosmetologist',
            'cosmetologists' => $cosmetologists
        ];
        
        return $this->buildResponse($body, $text, [], false, $stateData);
    }

    /**
     * Обработка записи на услугу
     */
    private function handleBookService($body, $session, $request_data, $state)
    {
        $userId = $this->getUserIdFromSession($session);
        
        if (!$userId) {
            $text = "Для записи нужно авторизоваться. Откройте приложение Cosmetic и войдите через Яндекс.";
            return $this->buildResponse($body, $text, [], false);
        }
        
        // Если процесс уже начат
        if (isset($state['step'])) {
            return $this->continueBookingFlow($body, $session, $request_data, $state);
        }
        
        // Начинаем новый процесс
        return $this->startBookingFlow($body, $session, $state);
    }

    /**
     * Продолжить процесс записи
     */
    private function continueBookingFlow($body, $session, $request_data, $state)
    {
        $step = $state['step'];
        $command = $request_data['command'];
        
        switch ($step) {
            case 'select_cosmetologist':
                $cosmetologists = $state['cosmetologists'];
                $selectedIndex = $this->extractNumber($command) - 1;
                
                if ($selectedIndex >= 0 && $selectedIndex < count($cosmetologists)) {
                    $selected = $cosmetologists[$selectedIndex];
                    
                    // Получаем услуги косметолога
                    $services = Service::findByCosmetologist($selected['id']);
                    
                    if (empty($services)) {
                        $text = "У этого косметолога пока нет услуг. Выберите другого.";
                        return $this->buildResponse($body, $text, [], false);
                    }
                    
                    $text = "Услуги " . $selected['first_name'] . " " . $selected['last_name'] . ":\n";
                    foreach ($services as $index => $s) {
                        $text .= ($index + 1) . ". " . $s['service'] . " — " . $s['price'] . " BYN\n";
                    }
                    $text .= "\nКакую услугу выбираете?";
                    
                    $newState = [
                        'step' => 'select_service',
                        'cosmetologist_id' => $selected['id'],
                        'cosmetologist_name' => $selected['first_name'] . ' ' . $selected['last_name'],
                        'services' => $services
                    ];
                    
                    return $this->buildResponse($body, $text, [], false, $newState);
                }
                
                $text = "Пожалуйста, назовите номер косметолога из списка.";
                return $this->buildResponse($body, $text, [], false, $state);
                
            case 'select_service':
                $services = $state['services'];
                $selectedIndex = $this->extractNumber($command) - 1;
                
                if ($selectedIndex >= 0 && $selectedIndex < count($services)) {
                    $selected = $services[$selectedIndex];
                    
                    // Получаем доступные слоты
                    $slots = $this->getAvailableSlotsForAlice(
                        $state['cosmetologist_id'],
                        $selected['id']
                    );
                    
                    if (empty($slots)) {
                        $text = "На ближайшие дни свободных слотов нет. Попробуйте позже.";
                        return $this->buildResponse($body, $text, [], false);
                    }
                    
                    $text = "Ближайшее доступное время:\n";
                    foreach (array_slice($slots, 0, 5) as $index => $slot) {
                        $time = date('d.m в H:i', strtotime($slot['begin_time']));
                        $text .= ($index + 1) . ". " . $time . "\n";
                    }
                    $text .= "\nВыберите время или скажите «на завтра».";
                    
                    $newState = [
                        'step' => 'select_time',
                        'cosmetologist_id' => $state['cosmetologist_id'],
                        'cosmetologist_name' => $state['cosmetologist_name'],
                        'service_id' => $selected['id'],
                        'service_name' => $selected['service'],
                        'price' => $selected['price'],
                        'slots' => $slots
                    ];
                    
                    return $this->buildResponse($body, $text, [], false, $newState);
                }
                
                $text = "Пожалуйста, назовите номер услуги из списка.";
                return $this->buildResponse($body, $text, [], false, $state);
                
            case 'select_time':
                $slots = $state['slots'];
                $selectedIndex = $this->extractNumber($command) - 1;
                
                // Проверяем "на завтра"
                if (strpos(mb_strtolower($command), 'завтра') !== false) {
                    $tomorrow = date('Y-m-d', strtotime('+1 day'));
                    $tomorrowSlots = array_filter($slots, function($s) use ($tomorrow) {
                        return date('Y-m-d', strtotime($s['begin_time'])) === $tomorrow;
                    });
                    
                    if (!empty($tomorrowSlots)) {
                        $slots = array_values($tomorrowSlots);
                        $selectedIndex = 0;
                    }
                }
                
                if ($selectedIndex >= 0 && $selectedIndex < count($slots)) {
                    $selected = $slots[$selectedIndex];
                    
                    // Создаем бронирование
                    $userId = $this->getUserIdFromSession($session);
                    $user = User::findById($userId);
                    
                    try {
                        $bookingId = Booking::create([
                            'cosmetologist_id' => $state['cosmetologist_id'],
                            'service_id' => $state['service_id'],
                            'schedule' => $selected['begin_time'],
                            'client_id' => $user['client_id'],
                            'status' => 'pending'
                        ]);
                        
                        $text = "✅ Запись создана!\n" .
                                "Косметолог: " . $state['cosmetologist_name'] . "\n" .
                                "Услуга: " . $state['service_name'] . "\n" .
                                "Время: " . date('d.m.Y в H:i', strtotime($selected['begin_time'])) . "\n" .
                                "Стоимость: " . $state['price'] . " BYN\n\n" .
                                "Ожидайте подтверждения от косметолога.";
                        
                        LoggerService::info('Booking created via Alice', [
                            'booking_id' => $bookingId,
                            'user_id' => $userId
                        ]);
                        
                        return $this->buildResponse($body, $text, [], true);
                        
                    } catch (\Exception $e) {
                        $text = "Не удалось создать запись. Попробуйте позже.";
                        return $this->buildResponse($body, $text, [], true);
                    }
                }
                
                $text = "Пожалуйста, выберите время из списка.";
                return $this->buildResponse($body, $text, [], false, $state);
                
            default:
                return $this->fallbackResponse($body);
        }
    }

    /**
     * Показать записи пользователя
     */
    private function handleMyBookings($body, $session, $request_data, $state)
    {
        $userId = $this->getUserIdFromSession($session);
        
        if (!$userId) {
            $text = "Для просмотра записей нужно авторизоваться.";
            return $this->buildResponse($body, $text, [], false);
        }
        
        $bookings = Booking::findByUser($userId);
        
        // Фильтруем только активные
        $activeBookings = array_filter($bookings, function($b) {
            return in_array($b['status'], ['pending', 'confirmed']) &&
                   strtotime($b['schedule']) > time();
        });
        
        if (empty($activeBookings)) {
            $text = "У вас нет предстоящих записей.";
            $buttons = [
                ['title' => '📅 Записаться', 'payload' => ['action' => 'book'], 'hide' => true]
            ];
            return $this->buildResponse($body, $text, $buttons);
        }
        
        $text = "Ваши предстоящие записи:\n";
        foreach ($activeBookings as $index => $b) {
            $time = date('d.m в H:i', strtotime($b['schedule']));
            $status = $b['status'] === 'confirmed' ? '✅' : '⏳';
            $text .= ($index + 1) . ". $status " . $b['service_name'] . " — $time\n";
        }
        
        return $this->buildResponse($body, $text, [], false);
    }

    /**
     * Отмена записи
     */
    private function handleCancelBooking($body, $session, $request_data, $state)
    {
        $userId = $this->getUserIdFromSession($session);
        
        if (!$userId) {
            $text = "Для отмены записи нужно авторизоваться.";
            return $this->buildResponse($body, $text, [], false);
        }
        
        $bookings = Booking::findByUser($userId);
        $activeBookings = array_filter($bookings, function($b) {
            return in_array($b['status'], ['pending', 'confirmed']) &&
                   strtotime($b['schedule']) > time();
        });
        
        if (empty($activeBookings)) {
            $text = "У вас нет записей, которые можно отменить.";
            return $this->buildResponse($body, $text, [], false);
        }
        
        // Если в процессе отмены
        if (isset($state['cancel_step'])) {
            $command = $request_data['command'];
            $selectedIndex = $this->extractNumber($command) - 1;
            $bookingIds = array_keys($activeBookings);
            
            if ($selectedIndex >= 0 && $selectedIndex < count($bookingIds)) {
                $bookingId = $bookingIds[$selectedIndex];
                $booking = $activeBookings[$bookingId];
                
                Booking::updateStatus($booking['id'], 'cancelled');
                
                $text = "✅ Запись на " . $booking['service_name'] . " отменена.";
                return $this->buildResponse($body, $text, [], true);
            }
        }
        
        $text = "Какую запись отменить?\n";
        foreach (array_values($activeBookings) as $index => $b) {
            $time = date('d.m в H:i', strtotime($b['schedule']));
            $text .= ($index + 1) . ". " . $b['service_name'] . " — $time\n";
        }
        
        $newState = ['cancel_step' => true, 'bookings' => $activeBookings];
        
        return $this->buildResponse($body, $text, [], false, $newState);
    }

    /**
     * Список услуг
     */
    private function handleServicesList($body, $session, $request_data, $state)
    {
        $cosmetologists = $this->getCosmetologists();
        
        if (empty($cosmetologists)) {
            $text = "Информация об услугах временно недоступна.";
            return $this->buildResponse($body, $text, [], false);
        }
        
        $text = "Наши косметологи и услуги:\n\n";
        
        foreach ($cosmetologists as $c) {
            $text .= "👩‍⚕️ " . $c['first_name'] . " " . $c['last_name'] . ":\n";
            $services = Service::findByCosmetologist($c['id']);
            
            foreach (array_slice($services, 0, 3) as $s) {
                $text .= "  • " . $s['service'] . " — " . $s['price'] . " BYN\n";
            }
            $text .= "\n";
        }
        
        $text .= "Скажите «Запиши меня», чтобы записаться.";
        
        $buttons = [
            ['title' => '📅 Записаться', 'payload' => ['action' => 'book'], 'hide' => true]
        ];
        
        return $this->buildResponse($body, $text, $buttons);
    }

    /**
     * Получить список косметологов
     */
    private function getCosmetologists()
    {
        $sql = "SELECT id, first_name, last_name, address, phone FROM Cosmetologist LIMIT 10";
        return Database::fetchAll($sql);
    }

    /**
     * Получить доступные слоты для Алисы
     */
    private function getAvailableSlotsForAlice($cosmetologistId, $serviceId)
    {
        try {
            return Database::callProcedureAndFetch('sp_available_time', [], '');
        } catch (\Exception $e) {
            // Тестовые слоты
            $slots = [];
            $startDate = strtotime('+1 day');
            
            for ($i = 0; $i < 5; $i++) {
                $date = date('Y-m-d', strtotime("+$i day", $startDate));
                for ($hour = 10; $hour < 17; $hour += 2) {
                    $slots[] = [
                        'begin_time' => "$date " . sprintf('%02d', $hour) . ":00:00",
                        'end_time' => "$date " . sprintf('%02d', $hour + 1) . ":00:00"
                    ];
                }
            }
            
            return $slots;
        }
    }

    /**
     * Извлечь число из команды
     */
    private function extractNumber($text)
    {
        $numbers = [
            'один' => 1, 'одну' => 1, 'перв' => 1,
            'два' => 2, 'втор' => 2, 'две' => 2,
            'три' => 3, 'трет' => 3,
            'четыре' => 4, 'четверт' => 4,
            'пять' => 5, 'пят' => 5
        ];
        
        // Ищем цифры
        if (preg_match('/\d+/', $text, $matches)) {
            return (int)$matches[0];
        }
        
        // Ищем слова
        foreach ($numbers as $word => $num) {
            if (strpos($text, $word) !== false) {
                return $num;
            }
        }
        
        return 1; // По умолчанию первый
    }

    /**
     * Получить ID пользователя из сессии Алисы
     */
    private function getUserIdFromSession($session)
    {
        // В реальном приложении здесь должна быть проверка OAuth токена
        // и получение user_id из базы
        
        $accessToken = $session['user']['access_token'] ?? null;
        
        if (!$accessToken) {
            return null;
        }
        
        // Здесь должна быть логика получения user_id по токену
        // Для примера возвращаем тестового пользователя
        $sql = "SELECT u.id FROM Users u 
                JOIN Clients c ON u.id = c.user_id 
                LIMIT 1";
        
        $user = Database::fetch($sql);
        
        return $user['id'] ?? null;
    }

    /**
     * Построить ответ для Алисы
     */
    private function buildResponse($body, $text, $buttons = [], $endSession = false, $stateData = null)
    {
        $response = [
            'version' => $body['version'],
            'session' => $body['session'],
            'response' => [
                'text' => $text,
                'end_session' => $endSession
            ]
        ];
        
        // Добавляем кнопки
        if (!empty($buttons)) {
            $response['response']['buttons'] = $buttons;
        }
        
        // Добавляем TTS (озвучка)
        $response['response']['tts'] = $text;
        
        // Сохраняем состояние сессии
        if ($stateData !== null) {
            $response['session_state'] = $stateData;
        } elseif (isset($body['state']['session'])) {
            $response['session_state'] = $body['state']['session'];
        }
        
        return $response;
    }

    /**
     * Ответ с ошибкой
     */
    private function errorResponse($body, $message)
    {
        return [
            'version' => $body['version'] ?? '1.0',
            'session' => $body['session'] ?? [],
            'response' => [
                'text' => $message,
                'tts' => $message,
                'end_session' => true
            ]
        ];
    }
}