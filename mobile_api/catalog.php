<?php

/**
 * Публичный эндпоинт каталога услуг для мобильного приложения —
 * замена catalog.product.list, не требующая OAuth (см.
 * mobile_app/README.md, раздел «Мини-REST для мобильного приложения»
 * и development_plan/tz-mobile-app.md про отложенный OAuth).
 *
 * Цена берётся из первой найденной строки catalog.PriceTable для
 * товара — если в каталоге несколько типов цен (розница/опт), нужно
 * дофильтровать по конкретному CATALOG_GROUP_ID; на момент написания
 * это не проверено на реальных данных сайта.
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

const CATALOG_IBLOCK_ID = 117;

try {
    if (!\Bitrix\Main\Loader::includeModule('iblock') || !\Bitrix\Main\Loader::includeModule('catalog')) {
        throw new \RuntimeException('Модули iblock/catalog не установлены');
    }

    $result = [];

    $res = \CIBlockElement::GetList(
        ['SORT' => 'ASC'],
        ['IBLOCK_ID' => CATALOG_IBLOCK_ID, 'ACTIVE' => 'Y'],
        false,
        false,
        ['ID', 'NAME']
    );

    while ($element = $res->Fetch()) {
        $priceRow = \Bitrix\Catalog\PriceTable::getList([
            'filter' => ['PRODUCT_ID' => $element['ID']],
            'order' => ['ID' => 'ASC'],
            'limit' => 1,
        ])->fetch();

        if (!$priceRow) {
            continue;
        }

        $result[] = [
            'ID' => (int)$element['ID'],
            'NAME' => $element['NAME'],
            'PRICE' => (float)$priceRow['PRICE'],
            'CURRENCY' => $priceRow['CURRENCY'],
        ];
    }

    echo json_encode(['result' => $result], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(
        ['error' => 'CATALOG_ERROR', 'error_description' => $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
}
