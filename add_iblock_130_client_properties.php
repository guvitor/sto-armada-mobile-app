<?php
// Добавляет CLIENT_NAME/CLIENT_PHONE в инфоблок 130 — MVP без авторизации,
// запись идентифицируется по имени и телефону вместо привязки к пользователю сайта.
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!Bitrix\Main\Loader::includeModule('iblock')) {
    die('Модуль iblock не подключён');
}

$IBLOCK_ID = 130;

function addPropertyIfMissing(int $iblockId, array $fields): void
{
    $code = $fields['CODE'];

    $existing = CIBlockProperty::GetList([], [
        'IBLOCK_ID' => $iblockId,
        'CODE' => $code,
    ])->Fetch();

    if ($existing) {
        echo "Свойство {$code} уже существует (ID={$existing['ID']}), пропуск\n";
        return;
    }

    $ibp = new CIBlockProperty;
    $id = $ibp->Add($fields);

    if ($id) {
        echo "Свойство {$code} создано, ID = {$id}\n";
    } else {
        echo "Ошибка создания свойства {$code}: " . $ibp->LAST_ERROR . "\n";
    }
}

addPropertyIfMissing($IBLOCK_ID, [
    'IBLOCK_ID' => $IBLOCK_ID,
    'NAME' => 'Имя клиента',
    'CODE' => 'CLIENT_NAME',
    'PROPERTY_TYPE' => 'S',
    'ACTIVE' => 'Y',
    'MULTIPLE' => 'N',
    'IS_REQUIRED' => 'Y',
]);

addPropertyIfMissing($IBLOCK_ID, [
    'IBLOCK_ID' => $IBLOCK_ID,
    'NAME' => 'Телефон клиента',
    'CODE' => 'CLIENT_PHONE',
    'PROPERTY_TYPE' => 'S',
    'ACTIVE' => 'Y',
    'MULTIPLE' => 'N',
    'IS_REQUIRED' => 'Y',
]);
