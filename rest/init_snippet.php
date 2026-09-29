<?php
// Добавить в /local/php_interface/init.php сайта (или подключить этот файл через require)

require_once($_SERVER['DOCUMENT_ROOT'] . '/rest/StoAuthRestService.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/rest/StoBookingRestService.php');

// Токен приложения для mobile_api/* (см. mobile_app/README.md, раздел
// «Мини-REST для мобильного приложения») — не токен пользователя, просто
// защита от случайного сканирования каталога/спама в форму записи.
// Значение не хранится в репозитории: боевое лежит только на сервере в
// init.php, локальная копия для сборок — mobile_app/.secrets/sto_app_token.
define('STO_MOBILE_APP_TOKEN', '<STO_MOBILE_APP_TOKEN>');

\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'rest',
    'OnRestServiceBuildDescription',
    ['StoAuthRestService', 'onRestServiceBuildDescription']
);

\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'rest',
    'OnRestServiceBuildDescription',
    ['StoBookingRestService', 'onRestServiceBuildDescription']
);
