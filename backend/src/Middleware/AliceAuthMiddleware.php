<?php
namespace App\Middleware;

use App\Models\User;
use App\Models\OAuthAccount;
use App\Models\Client;
use App\Models\Cosmetologist;
use App\Services\LoggerService;

class AliceAuthMiddleware
{
    public function handle($request, $next)
    {
        $data = $request->getBody();
        $yandexUserId = $data['session']['user']['user_id'] ?? null;
        
        LoggerService::info('AliceAuth: checking user', ['yandex_id' => $yandexUserId]);
        
        // Один Яндекс ID = один пользователь
        $oauth = OAuthAccount::findByProvider('yandex', $yandexUserId);
        
        if (!$oauth) {
            LoggerService::info('AliceAuth: no account found');
            $request->setAttribute('alice_user', null);
            $request->setAttribute('alice_authenticated', false);
            return $next($request);
        }
        
        $user = User::findById((int)$oauth['user_id']);
        
        if ($user) {
            // Добавляем role_id
            if ($user['role'] === 'cosmetologist') {
                $cosm = Cosmetologist::findByUserId($user['id']);
                $user['cosmetologist_id'] = $cosm['id'] ?? null;
            } elseif ($user['role'] === 'client') {
                $client = Client::findByUserId($user['id']);
                $user['client_id'] = $client['id'] ?? null;
            }
        }
        
        $request->setAttribute('alice_user', $user);
        $request->setAttribute('alice_authenticated', $user !== null);
        $request->setAttribute('alice_session', $data['session'] ?? []);
        
        return $next($request);
    }
}