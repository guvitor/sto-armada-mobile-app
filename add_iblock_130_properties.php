<?php
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

// DATETIME — дата/время визита
addPropertyIfMissing($IBLOCK_ID, [
    'IBLOCK_ID' => $IBLOCK_ID,
    'NAME' => 'Дата/время визита',
    'CODE' => 'DATETIME',
    'PROPERTY_TYPE' => 'S',
    'USER_TYPE' => 'DateTime',
    'ACTIVE' => 'Y',
    'MULTIPLE' => 'N',
    'IS_REQUIRED' => 'Y',
]);

// STATUS — справочник статусов записи
addPropertyIfMissing($IBLOCK_ID, [
    'IBLOCK_ID' => $IBLOCK_ID,
    'NAME' => 'Статус',
    'CODE' => 'STATUS',
    'PROPERTY_TYPE' => 'L',
    'LIST_TYPE' => 'L',
    'ACTIVE' => 'Y',
    'MULTIPLE' => 'N',
    'IS_REQUIRED' => 'Y',
    'VALUES' => [
        ['VALUE' => 'Новая', 'XML_ID' => 'NEW', 'DEF' => 'Y', 'SORT' => 100],
        ['VALUE' => 'Подтверждена', 'XML_ID' => 'CONFIRMED', 'SORT' => 200],
        ['VALUE' => 'В работе', 'XML_ID' => 'IN_PROGRESS', 'SORT' => 300],
        ['VALUE' => 'Выполнена', 'XML_ID' => 'DONE', 'SORT' => 400],
        ['VALUE' => 'Отменена', 'XML_ID' => 'CANCELLED', 'SORT' => 500],
    ],
]);

// USER_ID — привязка к пользователю
addPropertyIfMissing($IBLOCK_ID, [
    'IBLOCK_ID' => $IBLOCK_ID,
    'NAME' => 'Пользователь',
    'CODE' => 'USER_ID',
    'PROPERTY_TYPE' => 'S',
    'USER_TYPE' => 'UserID',
    'ACTIVE' => 'Y',
    'MULTIPLE' => 'N',
    'IS_REQUIRED' => 'Y',
]);

// COMMENT — комментарий клиента
addPropertyIfMissing($IBLOCK_ID, [
    'IBLOCK_ID' => $IBLOCK_ID,
    'NAME' => 'Комментарий',
    'CODE' => 'COMMENT',
    'PROPERTY_TYPE' => 'S',
    'ROW_COUNT' => 3,
    'COL_COUNT' => 30,
    'ACTIVE' => 'Y',
    'MULTIPLE' => 'N',
    'IS_REQUIRED' => 'N',
]);
