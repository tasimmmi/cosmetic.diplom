<?php
namespace App\Services;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class LoggerService
{
    private static $logger = null;

    private static function getLogger()
    {
        if (self::$logger === null) {
            self::$logger = new Logger('cosmetic');
            
            $logDir = __DIR__ . '/../../logs';
            
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }
            
            try {
                $formatter = new LineFormatter(
                    "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                    "Y-m-d H:i:s"
                );
                
                $handler = new RotatingFileHandler(
                    $logDir . '/app.log',
                    30,
                    Logger::DEBUG
                );
                $handler->setFormatter($formatter);
                self::$logger->pushHandler($handler);
                
                self::$logger->info('Logger initialized');
                
            } catch (\Exception $e) {
                // Тихо падаем — логи не критичны
            }
        }
        
        return self::$logger;
    }

    public static function debug($message, $context = [])
    {
        try { self::getLogger()->debug($message, $context); } catch (\Exception $e) {}
    }

    public static function info($message, $context = [])
    {
        try { self::getLogger()->info($message, $context); } catch (\Exception $e) {}
    }

    public static function warning($message, $context = [])
    {
        try { self::getLogger()->warning($message, $context); } catch (\Exception $e) {}
    }

    public static function error($message, $context = [])
    {
        try { self::getLogger()->error($message, $context); } catch (\Exception $e) {}
    }

    public static function critical($message, $context = [])
    {
        try { self::getLogger()->critical($message, $context); } catch (\Exception $e) {}
    }
}