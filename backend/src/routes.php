<?php
/**
 * Файл маршрутизации
 */

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\BookingController;
use App\Controllers\CosmetologistController;
use App\Controllers\ScheduleController;
use App\Controllers\ClientController;
use App\Controllers\AliceController;
use App\Controllers\LogController;
use App\Middleware\AuthMiddleware;
use App\Middleware\AliceAuthMiddleware;

/** @var \App\Core\App $app */

// ==================== ПУБЛИЧНЫЕ МАРШРУТЫ ====================

// Health check
$app->get('/api/health', function() {
    return ['status' => 'healthy', 'timestamp' => date('Y-m-d H:i:s')];
});

// Аутентификация
$app->post('/api/auth/register', [AuthController::class, 'register']);
$app->post('/api/auth/login', [AuthController::class, 'login']);
$app->get('/api/auth/verify-email', [AuthController::class, 'verifyEmail']);
$app->post('/api/auth/refresh', [AuthController::class, 'refreshToken']);
$app->post('/api/auth/logout', [AuthController::class, 'logout']);

// Яндекс OAuth
$app->get('/api/auth/yandex', [AuthController::class, 'yandexAuth']);
$app->get('/api/auth/yandex/callback', [AuthController::class, 'yandexCallback']);

// Публичные маршруты косметологов
$app->get('/api/cosmetologists', [CosmetologistController::class, 'list']);
$app->get('/api/cosmetologists/{id}', [CosmetologistController::class, 'show']);
$app->get('/api/cosmetologists/{id}/services', [CosmetologistController::class, 'services']);
$app->get('/api/cosmetologists/{id}/slots', [ScheduleController::class, 'availableSlots']);

// Алиса
$app->post('/api/alice/webhook', [AliceController::class, 'webhook']);
    //->middleware(new AliceAuthMiddleware());

// Логи с фронтенда
$app->post('/api/logs/frontend', [LogController::class, 'frontendLogs']);

// ==================== ЗАЩИЩЕННЫЕ МАРШРУТЫ ====================

// Аутентификация
$app->post('/api/auth/logout-all', [AuthController::class, 'logoutAll'])
    ->middleware(new AuthMiddleware());
$app->get('/api/auth/linked-accounts', [AuthController::class, 'getLinkedAccounts'])
    ->middleware(new AuthMiddleware());
$app->post('/api/auth/yandex/link', [AuthController::class, 'linkYandex'])
    ->middleware(new AuthMiddleware());
$app->delete('/api/auth/yandex/unlink', [AuthController::class, 'unlinkYandex'])
    ->middleware(new AuthMiddleware());

// Профиль
$app->get('/api/users/me', [UserController::class, 'me'])
    ->middleware(new AuthMiddleware());
$app->put('/api/users/profile', [UserController::class, 'updateProfile'])
    ->middleware(new AuthMiddleware());
$app->get('/api/users/bookings', [UserController::class, 'getBookings'])
    ->middleware(new AuthMiddleware());
$app->post('/api/users/change-password', [UserController::class, 'changePassword'])
    ->middleware(new AuthMiddleware());

// Бронирования
$app->get('/api/bookings', [BookingController::class, 'index'])
    ->middleware(new AuthMiddleware());
$app->post('/api/bookings', [BookingController::class, 'create'])
    ->middleware(new AuthMiddleware());
$app->get('/api/bookings/{id}', [BookingController::class, 'show'])
    ->middleware(new AuthMiddleware());
$app->put('/api/bookings/{id}/cancel', [BookingController::class, 'cancel'])
    ->middleware(new AuthMiddleware());

// ==================== МАРШРУТЫ КОСМЕТОЛОГА ====================

// Статистика
$app->get('/api/cosmetologist/statistics', [CosmetologistController::class, 'statistics'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// Записи
$app->get('/api/cosmetologist/bookings', [CosmetologistController::class, 'bookings'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->put('/api/cosmetologist/bookings/{id}/confirm', [CosmetologistController::class, 'confirmBooking'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->put('/api/cosmetologist/bookings/{id}/complete', [CosmetologistController::class, 'completeBooking'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->put('/api/bookings/{id}/comment', [BookingController::class, 'updateComment'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// Услуги
$app->get('/api/cosmetologist/services', [CosmetologistController::class, 'getServices'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->post('/api/services', [CosmetologistController::class, 'createService'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->put('/api/services/{id}', [CosmetologistController::class, 'updateService'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->delete('/api/services/{id}', [CosmetologistController::class, 'deleteService'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// Материалы
$app->get('/api/cosmetologist/materials', [CosmetologistController::class, 'materials'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->post('/api/cosmetologist/materials', [CosmetologistController::class, 'addMaterial'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->put('/api/cosmetologist/materials/{id}', [CosmetologistController::class, 'updateMaterial'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->delete('/api/cosmetologist/materials/{id}', [CosmetologistController::class, 'deleteMaterial'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->post('/api/cosmetologist/procurements', [CosmetologistController::class, 'addProcurement'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// Клиенты
$app->get('/api/cosmetologist/clients/{id}', [CosmetologistController::class, 'clientDetails'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->put('/api/cosmetologist/clients/{id}', [CosmetologistController::class, 'updateClient'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->post('/api/cosmetologist/clients', [CosmetologistController::class, 'addClient'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->get('/api/cosmetologist/clients', [CosmetologistController::class, 'clients'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// Отчеты
$app->get('/api/cosmetologist/reports', [CosmetologistController::class, 'reports'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// ==================== РАСПИСАНИЕ (ScheduleController) ====================

// Просмотр
$app->get('/api/cosmetologist/schedule', [ScheduleController::class, 'index'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->get('/api/cosmetologist/schedule/all', [ScheduleController::class, 'allByDate'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->get('/api/cosmetologist/schedule/calendar-data', [ScheduleController::class, 'getCalendarData'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// Генерация
$app->post('/api/cosmetologist/schedule/generate', [ScheduleController::class, 'generate'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// Управление слотами
$app->delete('/api/cosmetologist/schedule/slot/{id}', [ScheduleController::class, 'deleteSlot'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->put('/api/cosmetologist/schedule/slot/{id}/deactivate', [ScheduleController::class, 'deactivateSlot'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->put('/api/cosmetologist/schedule/slot/{id}/activate', [ScheduleController::class, 'activateSlot'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// Управление днями
$app->delete('/api/cosmetologist/schedule/day', [ScheduleController::class, 'clearDay'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->put('/api/cosmetologist/schedule/day/deactivate', [ScheduleController::class, 'deactivateDay'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->put('/api/cosmetologist/schedule/day/activate', [ScheduleController::class, 'activateDay'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// Занятые слоты
$app->get('/api/cosmetologist/schedule/booked', [ScheduleController::class, 'getBookedSlots'])
    ->middleware(new AuthMiddleware(['cosmetologist']));
$app->get('/api/cosmetologist/schedule/calendar', [ScheduleController::class, 'getCalendarBooked'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// Статистика
$app->get('/api/cosmetologist/schedule/stats', [ScheduleController::class, 'dayStats'])
    ->middleware(new AuthMiddleware(['cosmetologist']));

// ==================== МАРШРУТЫ КЛИЕНТА ====================
$app->get('/api/client/history', [ClientController::class, 'history'])
    ->middleware(new AuthMiddleware(['client']));
$app->get('/api/client/statistics', [ClientController::class, 'statistics'])
    ->middleware(new AuthMiddleware(['client']));