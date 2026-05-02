<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class AliceController
{
    public function webhook($request, $response)
    {
        $data = $request->getBody();
        $session = $data['session'] ?? [];
        $command = mb_strtolower(trim($data['request']['command'] ?? ''));
        $isNewSession = $session['new'] ?? false;
        
        if ($isNewSession && empty($command)) {
            return $this->alice('Здравствуйте! Я помощник Cosmetic. Привяжите Яндекс аккаунт в профиле на сайте.', $session);
        }
        
        return $this->alice('Скажите «Помощь» для списка команд.', $session);
    }
    
    private function alice(string $text, array $session, bool $end = false): array
    {
        return [
            'response' => ['text' => $text, 'tts' => $text, 'end_session' => $end],
            'session' => $session,
            'version' => '1.0'
        ];
    }
}