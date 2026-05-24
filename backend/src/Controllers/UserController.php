<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Client;
use App\Models\Cosmetologist;
use App\Models\Booking;
use App\Services\LoggerService;
use App\Services\EmailService;
use App\Utils\Validator;

class UserController
{
    
    public function me(Request $request, Response $response)
    {
        $userId = $request->getParam('user_id');
        $user = User::findById($userId);
        
        if (!$user) {
            return $response->error('Пользователь не найден', 404);
        }
        
        unset($user['password'], $user['salt'], $user['verification_token'], $user['verification_token_expires']);
        
        if ($user['role'] === 'cosmetologist' && $user['cosmetologist_id']) {
            $cosm = Cosmetologist::findById($user['cosmetologist_id']);
            if ($cosm && !empty($cosm['avatar'])) {
                $user['avatar'] = base64_encode($cosm['avatar']);
            } else {
                $user['avatar'] = null;
            }
        }
        
        return $response->success(['user' => $user]);
    }

    public function updateProfile(Request $request, Response $response)
    {
        $userId = $request->getParam('user_id');
        $data = $request->getBody();
        $user = User::findById($userId);
        
        if (!$user) {
            return $response->error('Пользователь не найден', 404);
        }
        
        try {
            if (isset($data['avatar'])) {
                if (!$user['cosmetologist_id']) {
                    return $response->error('Только косметолог может установить аватар', 400);
                }
                
                if ($data['avatar'] === null) {
                    Cosmetologist::updateAvatar($user['cosmetologist_id'], null);
                } else {
                    $avatarData = base64_decode($data['avatar']);
                    if ($avatarData === false) {
                        return $response->error('Неверный формат изображения', 400);
                    }
                    Cosmetologist::updateAvatar($user['cosmetologist_id'], $avatarData);
                }
                
                $updatedUser = User::findById($userId);
                unset($updatedUser['password'], $updatedUser['salt']);
                return $response->success(['user' => $updatedUser], 'Аватар обновлён');
            }
            
            // Обновление профиля
            $validator = new Validator($data);
            $validator->required(['phone']);
            
            if (!$validator->isValid()) {
                return $response->error($validator->getFirstError(), 400);
            }
            
            if ($user['role'] === 'client') {
                Client::updateByUserId($userId, [
                    'fullname' => $data['fullname'] ?? $user['client_name'],
                    'phone' => $data['phone'],
                    'communication' => $data['communication'] ?? 'phone'
                ]);
            } elseif ($user['role'] === 'cosmetologist') {
                Cosmetologist::updateByUserId($userId, [
                    'first_name' => $data['first_name'] ?? $user['first_name'],
                    'last_name' => $data['last_name'] ?? $user['last_name'],
                    'phone' => $data['phone'],
                    'address' => $data['address'] ?? $user['address']
                ]);
            }
            
            // Смена email
            if (!empty($data['email']) && $data['email'] !== $user['email']) {
                $existingUser = User::findByEmail($data['email']);
                if ($existingUser) {
                    return $response->error('Этот email уже используется', 409);
                }
                
                $verificationToken = bin2hex(random_bytes(32));
                User::updateEmail($userId, $data['email'], $verificationToken);
                
                $name = $user['client_name'] ?? $user['first_name'] ?? 'Пользователь';
                $emailService = new EmailService();
                $emailService->sendVerificationEmail($data['email'], $verificationToken, $name);
            }
            
            LoggerService::info('Profile updated', ['user_id' => $userId]);
            
            $updatedUser = User::findById($userId);
            unset($updatedUser['password'], $updatedUser['salt']);
            
            return $response->success(['user' => $updatedUser], 'Профиль обновлен');
            
        } catch (\Exception $e) {
            LoggerService::error('Profile update failed: ' . $e->getMessage());
            return $response->error('Ошибка обновления профиля', 500);
        }
    }

    public function getBookings(Request $request, Response $response)
    {
        $userId = $request->getParam('user_id');
        $status = $request->getQueryParam('status');
        $user = User::findById($userId);
        
        if (!$user) {
            return $response->error('Пользователь не найден', 404);
        }
        
        if ($user['role'] === 'client' && $user['client_id']) {
            $bookings = Booking::findByClientId($user['client_id'], $status);
        } elseif ($user['role'] === 'cosmetologist' && $user['cosmetologist_id']) {
            $date = $request->getQueryParam('date');
            $bookings = Booking::findByCosmetologist($user['cosmetologist_id'], $date, $status);
        } else {
            $bookings = [];
        }
        
        return $response->success(['bookings' => $bookings]);
    }

    public function changePassword(Request $request, Response $response)
    {
        $userId = $request->getParam('user_id');
        $data = $request->getBody();
        
        // Установка начального пароля (при отвязке Яндекс)
        if (!empty($data['set_initial'])) {
            if (empty($data['new_password'])) {
                return $response->error('Введите пароль', 400);
            }
            if ($data['new_password'] !== ($data['confirm_password'] ?? '')) {
                return $response->error('Пароли не совпадают', 400);
            }
            if (strlen($data['new_password']) < 8) {
                return $response->error('Минимум 8 символов', 400);
            }
            
            User::updatePassword($userId, $data['new_password']);
            return $response->success(null, 'Пароль установлен');
        }
        
        // Обычная смена пароля
        $validator = new Validator($data);
        $validator->required(['current_password', 'new_password'])->minLength('new_password', 8);
        
        if (!$validator->isValid()) {
            return $response->error($validator->getFirstError(), 400);
        }
        
        if ($data['new_password'] !== ($data['confirm_password'] ?? '')) {
            return $response->error('Пароли не совпадают', 400);
        }
        
        $user = User::findById($userId);
        
        if (!User::verifyPassword($user, $data['current_password'])) {
            return $response->error('Неверный текущий пароль', 401);
        }
        
        User::updatePassword($userId, $data['new_password']);
        LoggerService::info('Password changed', ['user_id' => $userId]);
        
        return $response->success(null, 'Пароль успешно изменен');
    }
}