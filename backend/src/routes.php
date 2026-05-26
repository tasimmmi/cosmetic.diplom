<?php
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\BookingController;
use App\Controllers\CosmetologistController;
use App\Controllers\ScheduleController;
use App\Controllers\ClientController;
use App\Controllers\AliceController;
use App\Controllers\LogController;
use App\Middleware\AuthMiddleware;
use App\Controllers\ReportController;

/** @var \App\Core\App $app */

// ==================== ПУБЛИЧНЫЕ МАРШРУТЫ ====================

$app->get('/api/health', function() {
    return ['status' => 'healthy', 'timestamp' => date('Y-m-d H:i:s')];
});

$app->post('/api/auth/register', [AuthController::class, 'register']);
$app->post('/api/auth/login', [AuthController::class, 'login']);
$app->get('/api/auth/verify-email', [AuthController::class, 'verifyEmail']);
$app->post('/api/auth/refresh', [AuthController::class, 'refreshToken']);
$app->post('/api/auth/logout', [AuthController::class, 'logout']);

$app->get('/api/auth/yandex', [AuthController::class, 'yandexAuth']);
$app->get('/api/auth/yandex/callback', [AuthController::class, 'yandexCallback']);

$app->get('/api/cosmetologists', [CosmetologistController::class, 'list']);
$app->get('/api/cosmetologists/{id}', [CosmetologistController::class, 'show']);
$app->get('/api/cosmetologists/{id}/services', [CosmetologistController::class, 'services']);
$app->get('/api/cosmetologists/{id}/slots', [ScheduleController::class, 'availableSlots']);

$app->post('/api/alice/webhook', [AliceController::class, 'webhook']);
$app->post('/api/logs/frontend', [LogController::class, 'frontendLogs']);

$app->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$app->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);

// ==================== ЗАЩИЩЁННЫЕ (любая роль) ====================

$app->post('/api/auth/logout-all', [AuthController::class, 'logoutAll'])
    ->middleware([AuthMiddleware::class]);
$app->get('/api/auth/linked-accounts', [AuthController::class, 'getLinkedAccounts'])
    ->middleware([AuthMiddleware::class]);
$app->post('/api/auth/yandex/link', [AuthController::class, 'linkYandex'])
    ->middleware([AuthMiddleware::class]);
$app->delete('/api/auth/yandex/unlink', [AuthController::class, 'unlinkYandex'])
    ->middleware([AuthMiddleware::class]);

$app->get('/api/users/me', [UserController::class, 'me'])
    ->middleware([AuthMiddleware::class]);
$app->put('/api/users/profile', [UserController::class, 'updateProfile'])
    ->middleware([AuthMiddleware::class]);
$app->get('/api/users/bookings', [UserController::class, 'getBookings'])
    ->middleware([AuthMiddleware::class]);
$app->post('/api/users/change-password', [UserController::class, 'changePassword'])
    ->middleware([AuthMiddleware::class]);

$app->get('/api/bookings', [BookingController::class, 'index'])
    ->middleware([AuthMiddleware::class]);
$app->post('/api/bookings', [BookingController::class, 'create'])
    ->middleware([AuthMiddleware::class]);
$app->get('/api/bookings/{id}', [BookingController::class, 'show'])
    ->middleware([AuthMiddleware::class]);
$app->put('/api/bookings/{id}/cancel', [BookingController::class, 'cancel'])
    ->middleware([AuthMiddleware::class]);

// ==================== КОСМЕТОЛОГ ====================

$app->get('/api/cosmetologist/statistics', [CosmetologistController::class, 'statistics'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/bookings', [CosmetologistController::class, 'bookings'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->put('/api/cosmetologist/bookings/{id}/confirm', [CosmetologistController::class, 'confirmBooking'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->put('/api/cosmetologist/bookings/{id}/complete', [CosmetologistController::class, 'completeBooking'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->put('/api/bookings/{id}/comment', [BookingController::class, 'updateComment'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);

$app->get('/api/cosmetologist/services', [CosmetologistController::class, 'getServices'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->post('/api/services', [CosmetologistController::class, 'createService'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->put('/api/services/{id}', [CosmetologistController::class, 'updateService'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->delete('/api/services/{id}', [CosmetologistController::class, 'deleteService'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);

$app->get('/api/cosmetologist/materials', [CosmetologistController::class, 'materials'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->post('/api/cosmetologist/materials', [CosmetologistController::class, 'addMaterial'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->put('/api/cosmetologist/materials/{id}', [CosmetologistController::class, 'updateMaterial'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->delete('/api/cosmetologist/materials/{id}', [CosmetologistController::class, 'deleteMaterial'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->post('/api/cosmetologist/procurements', [CosmetologistController::class, 'addProcurement'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);

$app->get('/api/cosmetologist/procurements/all', [CosmetologistController::class, 'getAllProcurements'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->post('/api/cosmetologist/procurements', [CosmetologistController::class, 'addProcurement'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->put('/api/cosmetologist/procurements/{id}', [CosmetologistController::class, 'updateProcurement'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->delete('/api/cosmetologist/procurements/{id}', [CosmetologistController::class, 'deleteProcurement'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);

$app->get('/api/cosmetologist/clients/{id}', [CosmetologistController::class, 'clientDetails'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->put('/api/cosmetologist/clients/{id}', [CosmetologistController::class, 'updateClient'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->post('/api/cosmetologist/clients', [CosmetologistController::class, 'addClient'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/clients', [CosmetologistController::class, 'clients'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);

$app->get('/api/cosmetologist/reports', [CosmetologistController::class, 'reports'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);

// Расписание
$app->get('/api/cosmetologist/schedule', [ScheduleController::class, 'index'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/schedule/all', [ScheduleController::class, 'allByDate'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/schedule/calendar-data', [ScheduleController::class, 'getCalendarData'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->post('/api/cosmetologist/schedule/generate', [ScheduleController::class, 'generate'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->delete('/api/cosmetologist/schedule/slot/{id}', [ScheduleController::class, 'deleteSlot'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->put('/api/cosmetologist/schedule/slot/{id}/deactivate', [ScheduleController::class, 'deactivateSlot'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->put('/api/cosmetologist/schedule/slot/{id}/activate', [ScheduleController::class, 'activateSlot'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->delete('/api/cosmetologist/schedule/day', [ScheduleController::class, 'clearDay'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->put('/api/cosmetologist/schedule/day/deactivate', [ScheduleController::class, 'deactivateDay'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->put('/api/cosmetologist/schedule/day/activate', [ScheduleController::class, 'activateDay'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/schedule/booked', [ScheduleController::class, 'getBookedSlots'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/schedule/calendar', [ScheduleController::class, 'getCalendarBooked'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/schedule/stats', [ScheduleController::class, 'dayStats'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->post('/api/cosmetologist/schedule/check-and-generate', [ScheduleController::class, 'checkAndGenerate'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);

// Отчёты
$app->get('/api/cosmetologist/reports/financial', [ReportController::class, 'financial'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/reports/services', [ReportController::class, 'services'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/reports/materials', [ReportController::class, 'materials'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/reports/bookings', [ReportController::class, 'bookings'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/reports/summary', [ReportController::class, 'summary'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);
$app->get('/api/cosmetologist/reports/export-pdf', [ReportController::class, 'exportPdf'])
    ->middleware([AuthMiddleware::class, 'cosmetologist']);

// ==================== КЛИЕНТ ====================

$app->get('/api/client/history', [ClientController::class, 'history'])
    ->middleware([AuthMiddleware::class, 'client']);
$app->get('/api/client/statistics', [ClientController::class, 'statistics'])
    ->middleware([AuthMiddleware::class, 'client']);