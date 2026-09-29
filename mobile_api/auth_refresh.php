<?php

/**
 * Публичный эндпоинт обмена refresh_token на новый access_token
 * (Фаза 2) — тот же мини-REST в обход OAuth, что и auth.php. Вызывает
 * StoAuthRestService::refresh() напрямую, без Bitrix REST-модуля.
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

if (!\Bitrix\Main\Loader::includeModule('rest')) {
    http_response_code(500);
    echo json_encode(
        ['error' => 'CONFIG_ERROR', 'error_description' => 'Модуль rest не установлен'],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    $response = StoAuthRestService::refresh($input);
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
