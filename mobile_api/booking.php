<?php

/**
 * Публичный эндпоинт записи на сервис для мобильного приложения —
 * тот же мини-REST в обход OAuth, что и catalog.php. Логика не
 * дублируется: вызывает уже протестированный (день 5)
 * StoBookingRestService::add() — тем же способом, каким его вызывал
 * диагностический test_rest_methods.php, напрямую, без Bitrix
 * REST-модуля. Класс доступен глобально — подключается в init.php
 * через mobile_app/rest/init_snippet.php.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once __DIR__ . '/_cors.php';
require_once __DIR__ . '/_auth.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'METHOD_NOT_ALLOWED', 'error_description' => 'Только POST'], JSON_UNESCAPED_UNICODE);
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

$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    $response = StoBookingRestService::add($input);
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
