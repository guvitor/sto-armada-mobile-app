<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!Bitrix\Main\Loader::includeModule('iblock')) {
    die('Модуль iblock не подключён');
}

$IBLOCK_ID = 130;
$LINK_IBLOCK_ID = 117; // каталог услуг
$CODE = 'SERVICE_ID';
$NAME = 'Услуга';

$existing = CIBlockProperty::GetList([], [
    'IBLOCK_ID' => $IBLOCK_ID,
    'CODE' => $CODE,
])->Fetch();

if ($existing) {
    die("Свойство с кодом {$CODE} уже существует в инфоблоке {$IBLOCK_ID} (ID={$existing['ID']})");
}

$ibp = new CIBlockProperty;

$fields = [
    'IBLOCK_ID' => $IBLOCK_ID,
    'NAME' => $NAME,
    'CODE' => $CODE,
    'PROPERTY_TYPE' => 'E', // привязка к элементам
    'LINK_IBLOCK_ID' => $LINK_IBLOCK_ID,
    'ACTIVE' => 'Y',
    'MULTIPLE' => 'N',
    'IS_REQUIRED' => 'Y',
];

$propertyId = $ibp->Add($fields);

if ($propertyId) {
    echo "Свойство создано, ID = {$propertyId}\n";
} else {
    echo "Ошибка создания свойства: " . $ibp->LAST_ERROR . "\n";
}
