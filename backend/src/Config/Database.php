<?php
namespace App\Config;

use App\Services\LoggerService;
use mysqli;

class Database
{
    private static ?mysqli $connection = null;

    /**
     * Получить соединение с базой данных (Singleton)
     */
    public static function getConnection(): mysqli
    {
        if (self::$connection === null) {
            try {
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $port = (int)($_ENV['DB_PORT'] ?? 3306);
                $dbname = $_ENV['DB_NAME'] ?? 'izbavitels_cosmeticdb';
                $user = $_ENV['DB_USER'] ?? 'root';
                $password = $_ENV['DB_PASSWORD'] ?? '';

                // Создаем соединение
                self::$connection = new mysqli($host, $user, $password, $dbname, $port);

                // Проверяем соединение
                if (self::$connection->connect_error) {
                    throw new \Exception("Connection failed: " . self::$connection->connect_error);
                }

                // Устанавливаем кодировку
                self::$connection->set_charset("utf8mb4");
                
                LoggerService::info('Database connected successfully (MySQLi)');
                
            } catch (\Exception $e) {
                LoggerService::critical('Database connection failed', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
        
        return self::$connection;
    }

    /**
     * Выполнить запрос и вернуть результат
     */
    public static function query($sql, $params = [], $types = '')
    {
        $conn = self::getConnection();
        
        // Определяем типы параметров автоматически, если не указаны
        if (empty($types) && !empty($params)) {
            $types = self::determineTypes($params);
        }

        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            LoggerService::error('Prepare failed', [
                'sql' => $sql,
                'error' => $conn->error
            ]);
            throw new \Exception("Prepare failed: " . $conn->error);
        }

        // Привязываем параметры
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        // Выполняем запрос
        if (!$stmt->execute()) {
            LoggerService::error('Execute failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $stmt->error
            ]);
            throw new \Exception("Execute failed: " . $stmt->error);
        }

        return $stmt;
    }

    /**
     * Получить все строки
     */
    public static function fetchAll($sql, $params = [], $types = '')
    {
        $stmt = self::query($sql, $params, $types);
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $rows;
    }

    /**
     * Получить одну строку
     */
    public static function fetch($sql, $params = [], $types = '')
    {
        $stmt = self::query($sql, $params, $types);
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
    }

    /**
     * Выполнить запрос без возврата результата (INSERT, UPDATE, DELETE)
     */
    public static function execute($sql, $params = [], $types = '')
    {
        $stmt = self::query($sql, $params, $types);
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows;
    }

    /**
     * Получить ID последней вставленной записи
     */
    public static function lastInsertId()
    {
        return self::getConnection()->insert_id;
    }

    /**
     * Экранировать строку
     */
    public static function escape($string)
    {
        return self::getConnection()->real_escape_string($string);
    }

    /**
     * Начать транзакцию
     */
    public static function beginTransaction()
    {
        self::getConnection()->begin_transaction();
    }

    /**
     * Зафиксировать транзакцию
     */
    public static function commit()
    {
        self::getConnection()->commit();
    }

    /**
     * Откатить транзакцию
     */
    public static function rollback()
    {
        self::getConnection()->rollback();
    }

    /**
     * Вызвать хранимую процедуру
     */
    public static function callProcedure($procedureName, $params = [], $types = '')
    {
        $placeholders = implode(',', array_fill(0, count($params), '?'));
        $sql = "CALL $procedureName($placeholders)";
        
        return self::query($sql, $params, $types);
    }

    /**
     * Получить результаты хранимой процедуры
     */
    public static function callProcedureAndFetch($procedureName, $params = [], $types = '')
    {
        $stmt = self::callProcedure($procedureName, $params, $types);
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Очищаем оставшиеся результаты
        $conn = self::getConnection();
        while ($conn->more_results()) {
            $conn->next_result();
        }
        
        return $rows;
    }

    /**
     * Автоматически определить типы параметров для bind_param
     */
    private static function determineTypes($params)
    {
        $types = '';
        
        foreach ($params as $param) {
            if (is_int($param) || is_bool($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b'; // blob
            }
        }
        
        return $types;
    }

    /**
     * Закрыть соединение
     */
    public static function close()
    {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}