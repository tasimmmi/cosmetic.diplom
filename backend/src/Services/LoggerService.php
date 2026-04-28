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
            
            // Определяем путь к логам
            $logDir = __DIR__ . '/../../logs';
            
            // Отладка пути
            error_log("=== LOGGER SERVICE INIT ===");
            error_log("Log dir: " . $logDir);
            
            // Создаем папку если её нет
            if (!is_dir($logDir)) {
                error_log("Creating log directory: " . $logDir);
                if (!mkdir($logDir, 0777, true)) {
                    error_log("FAILED to create log directory!");
                }
            }
            
            // Проверяем права на запись
            if (!is_writable($logDir)) {
                error_log("WARNING: Log directory is not writable: " . $logDir);
                // Пробуем изменить права
                chmod($logDir, 0777);
            }
            
            error_log("Log dir exists: " . (is_dir($logDir) ? 'YES' : 'NO'));
            error_log("Log dir writable: " . (is_writable($logDir) ? 'YES' : 'NO'));
            
            try {
                // Форматтер для логов
                $formatter = new LineFormatter(
                    "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                    "Y-m-d H:i:s"
                );
                
                // Файловый обработчик с ротацией
                $logFile = $logDir . '/app.log';
                error_log("Log file: " . $logFile);
                
                $handler = new RotatingFileHandler(
                    $logFile,
                    30,
                    Logger::DEBUG  // ВСЕГДА DEBUG для отладки
                );
                $handler->setFormatter($formatter);
                self::$logger->pushHandler($handler);
                
                // Отдельный файл для ошибок
                $errorFile = $logDir . '/error.log';
                $errorHandler = new RotatingFileHandler(
                    $errorFile,
                    30,
                    Logger::ERROR
                );
                $errorHandler->setFormatter($formatter);
                self::$logger->pushHandler($errorHandler);
                
                // ТЕСТОВАЯ ЗАПИСЬ
                self::$logger->info('=== LOGGER SERVICE INITIALIZED ===', [
                    'log_dir' => $logDir,
                    'writable' => is_writable($logDir),
                    'time' => date('Y-m-d H:i:s')
                ]);
                
                error_log("LoggerService: Test log written");
                
            } catch (\Exception $e) {
                error_log("LoggerService ERROR: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
        }
        
        return self::$logger;
    }

    private static function getLogLevel()
    {
        // Для отладки всегда DEBUG
        return Logger::DEBUG;
    }

    public static function debug($message, $context = [])
    {
        try {
            self::getLogger()->debug($message, $context);
        } catch (\Exception $e) {
            error_log("Logger debug error: " . $e->getMessage());
        }
    }

    public static function info($message, $context = [])
    {
        try {
            self::getLogger()->info($message, $context);
            // Также пишем в error_log для надежности
            error_log("[INFO] " . $message . " " . json_encode($context, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            error_log("Logger info error: " . $e->getMessage());
        }
    }

    public static function warning($message, $context = [])
    {
        try {
            self::getLogger()->warning($message, $context);
            error_log("[WARNING] " . $message . " " . json_encode($context, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            error_log("Logger warning error: " . $e->getMessage());
        }
    }

    public static function error($message, $context = [])
    {
        try {
            self::getLogger()->error($message, $context);
            error_log("[ERROR] " . $message . " " . json_encode($context, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            error_log("Logger error error: " . $e->getMessage());
        }
    }

    public static function critical($message, $context = [])
    {
        try {
            self::getLogger()->critical($message, $context);
            error_log("[CRITICAL] " . $message . " " . json_encode($context, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            error_log("Logger critical error: " . $e->getMessage());
        }
    }
}