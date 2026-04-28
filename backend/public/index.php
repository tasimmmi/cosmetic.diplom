<?php
// САМОЕ НАЧАЛО
error_log('=== REQUEST START ===');
error_log('Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('URI: ' . $_SERVER['REQUEST_URI']);
error_log('All GET: ' . json_encode($_GET));
error_log('All POST: ' . json_encode($_POST));

// Восстанавливаем Authorization
if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    error_log('Authorization: ' . substr($_SERVER['HTTP_AUTHORIZATION'], 0, 50) . '...');
}

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


// Включаем отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);



// Подключаем автозагрузку Composer
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\App;
use Dotenv\Dotenv;


// Восстанавливаем Authorization из переменной окружения
if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

// Логируем для проверки
error_log('=== REQUEST ===');
error_log('HTTP_AUTHORIZATION: ' . ($_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT SET'));

// Загружаем переменные окружения
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (\Exception $e) {
    // Если .env нет, продолжаем без него
}

// Устанавливаем часовой пояс
date_default_timezone_set('Europe/Minsk');

// Создаем и запускаем приложение
$app = new App();

// Подключаем маршруты
require_once __DIR__ . '/../src/routes.php';

// Запускаем обработку запроса
$app->run();