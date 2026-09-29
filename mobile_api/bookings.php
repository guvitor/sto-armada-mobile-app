<?php

/**
 * Публичный эндпоинт списка записей текущего пользователя (Фаза 2) —
 * тот же мини-REST в обход OAuth, что и остальные mobile_api/*.
 * Вызывает уже реализованный (дни 1-5 MVP) StoBookingRestService::list()
 * напрямую, без Bitrix REST-модуля. access_token — query-параметром,
 * тем же способом, что token приложения (см. _auth.php).
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once __DIR__ . '/_cors.php';
require_once __DIR__ . '/_auth.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'METHOD_NOT_ALLOWED', 'error_description' => 'Только GET'], JSON_UNESCAPED_UNICODE);
    exit;
}

stoCheckAppToken();

if (!\Bitrix\Main\Loader::includeModule('rest') || !\Bitrix\Main\Loader::includeModule('iblock')) {
    http_response_code(500);
    echo json_encode(
        ['error' => 'CONFIG_ERROR', 'error_description' => 'Модуль rest или iblock не установлен'],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$params = [
    'access_token' => $_GET['access_token'] ?? '',
    'order' => $_GET['order'] ?? 'DESC',
];

try {
    $response = StoBookingRestService::list($params);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (StoAuthFailedException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage(), 'code' => 'AUTH_FAILED'], JSON_UNESCAPED_UNICODE);
} catch (\Bitrix\Rest\RestException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(
        ['error' => 'INTERNAL_ERROR', 'error_description' => $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
}
