<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\OAuthAccount;
use App\Models\Client;
use App\Models\Cosmetologist;

class AliceController
{
    public function webhook($request, $response)
    {
        $data = $request->getBody();
        $session = $data['session'] ?? [];
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        $isNewSession = $session['new'] ?? false;
        $intents = $data['request']['nlu']['intents'] ?? [];
        
        $user = $this->findUser($data);
        
        if ($user === false) {
            if ($isNewSession && empty($command)) {
                return $this->reply('Здравствуйте! Для работы с навыком необходимо войти в Яндекс аккаунт на вашем устройстве.', $session);
            }
            return $this->reply('Пожалуйста, войдите в Яндекс аккаунт на устройстве.', $session, true);
        }
        
        if ($user === null) {
            if ($isNewSession && empty($command)) {
                return $this->reply('Здравствуйте! Привяжите Яндекс аккаунт в профиле на сайте cosmetic.diplom.', $session);
            }
            if ($this->match($command, ['привязать', 'связать', 'как'])) {
                return $this->reply('Зайдите на сайт cosmetic.diplom, войдите в профиль и привяжите Яндекс аккаунт в настройках.', $session, true);
            }
            return $this->reply('Аккаунт не привязан. Скажите «Как привязать».', $session);
        }
        
        $name = $this->userName($user);
        
        if ($isNewSession && empty($command)) {
            $role = $user['role'] === 'cosmetologist' ? 'косметолог' : 'клиент';
            return $this->reply("Здравствуйте, {$name}! Вы {$role}. Скажите «Помощь».", $session);
        }
        
        if ($this->match($command, ['помощь', 'справка', 'команды'])) {
            return $this->helpResponse($user, $session);
        }
        
        if ($user['role'] === 'cosmetologist') {
            $controller = new AliceCosmetologistController();
            
            if (isset($intents['get_bookings'])) return $controller->handleBookings($data, $user);
            if (isset($intents['get_schedule'])) return $controller->handleSchedule($data, $user);
            if (isset($intents['get_materials'])) return $controller->handleMaterials($data, $user);
            if (isset($intents['get_services'])) return $controller->handleServices($data, $user);
            if (isset($intents['add_client'])) return $controller->handleAddClient($data, $user);
            if (isset($intents['write_off_material'])) return $controller->handleWriteOff($data, $user);
            if (isset($intents['get_reports'])) return $controller->handleReports($data, $user);
            
            return $controller->handle($data, $user);
        }
        
        if ($user['role'] === 'client') {
            $controller = new AliceClientController();
            
            if (isset($intents['get_bookings'])) return $controller->handleBookings($data, $user);
            if (isset($intents['cancel_booking'])) return $controller->handleCancelBooking($data, $user);
            if (isset($intents['confirm_booking'])) return $controller->handleConfirmBooking($data, $user);
            if (isset($intents['get_services'])) return $controller->handleServices($data, $user);
            
            return $controller->handle($data, $user);
        }
        
        return $this->reply('Не поняла. Скажите «Помощь».', $session);
    }
    
    private function findUser(array $data)
    {
        $session = $data['session'] ?? [];
        $yandexUserId = $session['user']['user_id'] ?? null;
        
        if ($yandexUserId) {
            $oauth = OAuthAccount::findByProvider('yandex', $yandexUserId);
            if ($oauth) {
                $user = User::findById((int)$oauth['user_id']);
                if ($user) {
                    if ($user['role'] === 'cosmetologist') {
                        $cosm = Cosmetologist::findByUserId($user['id']);
                        $user['cosmetologist_id'] = $cosm['id'] ?? null;
                    } elseif ($user['role'] === 'client') {
                        $client = Client::findByUserId($user['id']);
                        $user['client_id'] = $client['id'] ?? null;
                    }
                    return $user;
                }
            }
            return null;
        }
        
        return false;
    }
    
    private function helpResponse(array $user, array $session): array
    {
        $text = $user['role'] === 'cosmetologist'
            ? 'Команды: «Записи на сегодня», «Расписание», «Добавить клиента», «Материалы», «Списать материал», «Отчёт».'
            : 'Команды: «Мои записи», «Записаться», «Отменить запись», «Подтвердить запись», «Косметологи», «Услуги».';
        return $this->reply($text, $session);
    }
    
    public static function match(string $command, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (mb_strpos($command, $kw) !== false) return true;
        }
        return false;
    }
    
    public static function userName(array $user): string
    {
        return $user['first_name'] ?? $user['client_name'] ?? $user['email'] ?? 'Пользователь';
    }
    
    public static function reply(string $text, array $session, bool $end = false, array $buttons = []): array
    {
        return [
            'response' => ['text' => $text, 'tts' => $text, 'buttons' => $buttons, 'end_session' => $end],
            'session' => $session,
            'version' => '1.0'
        ];
    }
}