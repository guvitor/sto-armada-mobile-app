<?php

/**
 * CORS-заголовки для mobile_api. Мобильное приложение (Android/iOS) под
 * CORS не подпадает — это чисто браузерное ограничение, но без него
 * эндпоинты нельзя проверить из Flutter Web (та же причина, по которой
 * этот файл вообще понадобился, — проверено 19.09.2026). Origin — `*`,
 * т.к. авторизация через query-токен, не cookie — общий доступ не
 * расширяет площадь атаки.
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
