<?php

/**
 * Проверка токена приложения (не пользователя — в MVP авторизации нет).
 * Токен статический, задаётся константой STO_MOBILE_APP_TOKEN в
 * /local/php_interface/init.php (см. mobile_app/rest/init_snippet.php).
 * Назначение — не пускать случайных сборщиков каталога/спам в форму
 * записи, а не аутентифицировать конкретного пользователя.
 *
 * Токен передаётся query-параметром `?token=...`, а не HTTP-заголовком:
 * на nginx+PHP-FPM (типовой хостинг под Bitrix) кастомные заголовки вроде
 * X-App-Token без явной настройки nginx не доходят до $_SERVER — было
 * проверено 19.09.2026 (provided_length всегда 0). Query-параметр не
 * зависит от конфигурации сервера, тем же способом передаёт auth и
 * штатный Bitrix REST (`?auth=...`).
 */
function stoCheckAppToken(): void
{
    $configured = defined('STO_MOBILE_APP_TOKEN') ? STO_MOBILE_APP_TOKEN : '';
    $provided = $_GET['token'] ?? '';

    if ($configured === '' || $provided === '' || !hash_equals($configured, $provided)) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(
            ['error' => 'AUTH_FAILED', 'error_description' => 'Неверный или отсутствующий token'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}
