<?php
namespace App\Config;

class Constants
{
    // Роли пользователей
    const ROLE_CLIENT = 'client';
    const ROLE_COSMETOLOGIST = 'cosmetologist';
    const ROLE_ADMIN = 'admin';

    // Статусы бронирований
    const BOOKING_PENDING = 'pending';
    const BOOKING_CONFIRMED = 'confirmed';
    const BOOKING_COMPLETED = 'completed';
    const BOOKING_CANCELLED = 'cancelled';

    // OAuth провайдеры
    const OAUTH_YANDEX = 'yandex';

    // Время жизни токенов (в секундах)
    const VERIFICATION_TOKEN_EXPIRES = 86400; // 24 часа
    const PASSWORD_RESET_EXPIRES = 3600; // 1 час

    // Пагинация
    const DEFAULT_PAGE_SIZE = 20;
    const MAX_PAGE_SIZE = 100;

    // Логирование
    const LOG_LEVEL_DEBUG = 'debug';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_CRITICAL = 'critical';
}