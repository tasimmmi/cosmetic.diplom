<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Client;
use App\Models\Cosmetologist;
use App\Models\Booking;
use App\Services\LoggerService;
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
        
        if ($user['role'] === 'client' && $user['client_id']) {
            $user['client_details'] = Client::getDetails($user['client_id']);
        } elseif ($user['role'] === 'cosmetologist' && $user['cosmetologist_id']) {
            $user['cosmetologist_details'] = Cosmetologist::findById($user['cosmetologist_id']);
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
        
        $validator = new Validator($data);
        $validator->required(['phone']);
        
        if (!$validator->isValid()) {
            return $response->error($validator->getFirstError(), 400);
        }
        
        try {
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
                    'address' => $data['address'] ?? $user['address'],
                    'education' => $data['education'] ?? $user['education'],
                    'about' => $data['about'] ?? $user['about']
                ]);
            }
            
            LoggerService::info('Profile updated', ['user_id' => $userId]);
            
            $updatedUser = User::findById($userId);
            unset($updatedUser['password'], $updatedUser['salt']);
            
            return $response->success(['user' => $updatedUser], 'Профиль обновлен');
            
        } catch (\Exception $e) {
            LoggerService::error('Profile update failed', ['error' => $e->getMessage()]);
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

    public function getClients(Request $request, Response $response)
    {
        $userId = $request->getParam('user_id');
        $user = User::findById($userId);
        
        if (!$user || $user['role'] !== 'cosmetologist') {
            return $response->error('Доступ запрещен', 403);
        }
        
        $search = $request->getQueryParam('search', '');
        $sort = $request->getQueryParam('sort', 'recent');
        
        $clients = Client::getByCosmetologist($user['cosmetologist_id'], $search, $sort);
        
        return $response->success(['clients' => $clients]);
    }

    public function getClientDetails(Request $request, Response $response, int $clientId)
    {
        $userId = $request->getParam('user_id');
        $user = User::findById($userId);
        
        if (!$user || $user['role'] !== 'cosmetologist') {
            return $response->error('Доступ запрещен', 403);
        }
        
        $client = Client::getDetails($clientId, $user['cosmetologist_id']);
        
        if (!$client) {
            return $response->error('Клиент не найден', 404);
        }
        
        $history = Client::getHistory($clientId, $user['cosmetologist_id']);
        $statistics = Client::getBookingStatistics($clientId);
        
        return $response->success([
            'client' => $client,
            'history' => $history,
            'statistics' => $statistics
        ]);
    }

    public function changePassword(Request $request, Response $response)
    {
        $userId = $request->getParam('user_id');
        $data = $request->getBody();
        
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