<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\LoggerService;

class LogController
{
    /**
     * Принять логи с фронтенда
     * POST /api/logs/frontend
     */
    public function frontendLogs($request, $response)
    {
        $data = $request->getBody();
        $logs = $data['logs'] ?? [];
        
        if (empty($logs)) {
            return $response->success(null, 'No logs to save');
        }
        
        foreach ($logs as $log) {
            $level = $log['level'] ?? 'info';
            $message = '[FRONTEND] ' . ($log['message'] ?? 'No message');
            $context = [
                'url' => $log['url'] ?? 'unknown',
                'userAgent' => $log['userAgent'] ?? 'unknown',
                'timestamp' => $log['timestamp'] ?? date('Y-m-d H:i:s'),
                'context' => $log['context'] ?? []
            ];
            
            switch ($level) {
                case 'error':
                    LoggerService::error($message, $context);
                    break;
                case 'warn':
                    LoggerService::warning($message, $context);
                    break;
                case 'debug':
                    LoggerService::debug($message, $context);
                    break;
                default:
                    LoggerService::info($message, $context);
            }
        }
        
        LoggerService::info('Frontend logs received', ['count' => count($logs)]);
        
        return $response->success(null, 'Logs saved');
    }
}