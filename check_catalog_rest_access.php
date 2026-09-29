<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;

echo 'Модуль catalog установлен: ' . (Loader::includeModule('catalog') ? 'Да' : 'НЕТ') . "\n";
echo 'Модуль rest установлен: ' . (Loader::includeModule('rest') ? 'Да' : 'НЕТ') . "\n";

// Собираем список методов так же, как это делает ядро REST при разборе запроса —
// через обработчики события OnRestServiceBuildDescription всех модулей.
$methods = [];
$events = GetModuleEvents('rest', 'OnRestServiceBuildDescription', true);

while ($event = $events->Fetch()) {
    $handlerMethods = ExecuteModuleEventEx($event);
    if (is_array($handlerMethods)) {
        $methods = array_merge($methods, array_keys($handlerMethods));
    }
}

echo 'Всего REST-методов зарегистрировано в системе: ' . count($methods) . "\n";

$target = 'catalog.product.list';
$found = in_array($target, $methods, true);

echo "{$target} зарегистрирован: " . ($found ? 'Да' : 'НЕТ') . "\n";

if ($found) {
    echo "\nМетод доступен на уровне модулей. Осталось проверить вручную:\n";
    echo "- в вебхуке/REST-приложении, которым будет пользоваться мобильное приложение,\n";
    echo "  включён скоуп «Каталог» (catalog) — без него метод вернёт ACCESS_DENIED;\n";
    echo "- у нужных услуг заполнены ID, NAME, PRICE, ACTIVE=Y (иначе список будет пустой).\n";
} else {
    echo "\nМетод не зарегистрирован. Проверить: установлен и обновлён ли модуль catalog,\n";
    echo "активен ли модуль rest (Настройки → Модули).\n";
}

echo "\nНайденные методы catalog.*: " . implode(', ', array_filter($methods, fn($m) => str_starts_with($m, 'catalog.'))) . "\n";
