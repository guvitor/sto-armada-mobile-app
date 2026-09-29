<?php

class StoBookingRestService
{
    const IBLOCK_ID = 130;           // Записи на сервис
    const CATALOG_IBLOCK_ID = 117;   // Каталог услуг

    public static function onRestServiceBuildDescription(): array
    {
        return [
            'sto.booking.add' => [__CLASS__, 'add'],
            'sto.booking.get' => [__CLASS__, 'get'],
            'sto.booking.list' => [__CLASS__, 'list'],
        ];
    }

    public static function add($arParams): array
    {
        $name = trim((string)($arParams['name'] ?? ''));
        $phone = trim((string)($arParams['phone'] ?? ''));
        $serviceId = (int)($arParams['service_id'] ?? 0);
        $datetimeRaw = trim((string)($arParams['datetime'] ?? ''));
        $comment = trim((string)($arParams['comment'] ?? ''));
        $accessToken = trim((string)($arParams['access_token'] ?? ''));

        $userId = $accessToken !== '' ? self::resolveUserIdFromToken($accessToken) : 0;

        if ($name === '' || $phone === '' || $serviceId <= 0 || $datetimeRaw === '') {
            throw new \Bitrix\Rest\RestException(
                'Не переданы обязательные параметры name/phone/service_id/datetime',
                'BOOKING_BAD_REQUEST',
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        $serviceExists = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => self::CATALOG_IBLOCK_ID, 'ID' => $serviceId, 'ACTIVE' => 'Y'],
            false,
            false,
            ['ID']
        )->Fetch();

        if (!$serviceExists) {
            throw new \Bitrix\Rest\RestException(
                'Услуга с указанным service_id не найдена или не активна',
                'BOOKING_SERVICE_NOT_FOUND',
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        try {
            $datetime = new \Bitrix\Main\Type\DateTime($datetimeRaw, 'Y-m-d H:i:s');
        } catch (\Exception $e) {
            throw new \Bitrix\Rest\RestException(
                'Некорректный формат datetime, ожидается Y-m-d H:i:s',
                'BOOKING_BAD_DATETIME',
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        $statusEnumId = self::getStatusEnumId('NEW');

        if (!$statusEnumId) {
            throw new \Bitrix\Rest\RestException(
                'Не найдено значение справочника статусов NEW — проверьте свойство STATUS инфоблока ' . self::IBLOCK_ID,
                'BOOKING_CONFIG_ERROR',
                \CRestServer::STATUS_INTERNAL
            );
        }

        $el = new \CIBlockElement;

        $fields = [
            'IBLOCK_ID' => self::IBLOCK_ID,
            'NAME' => 'Запись на сервис от ' . $datetime->toString(),
            'ACTIVE' => 'Y',
            'PROPERTY_VALUES' => [
                'SERVICE_ID' => $serviceId,
                'DATETIME' => $datetime,
                'STATUS' => $statusEnumId,
                'CLIENT_NAME' => $name,
                'CLIENT_PHONE' => $phone,
                'COMMENT' => $comment,
            ],
        ];

        if ($userId > 0) {
            $fields['PROPERTY_VALUES']['USER_ID'] = $userId;
        }

        $elementId = $el->Add($fields);

        if (!$elementId) {
            throw new \Bitrix\Rest\RestException(
                'Ошибка создания записи: ' . $el->LAST_ERROR,
                'BOOKING_ADD_FAILED',
                \CRestServer::STATUS_INTERNAL
            );
        }

        return ['result' => (int)$elementId];
    }

    public static function get($arParams): array
    {
        $userId = self::resolveUserIdFromToken((string)($arParams['access_token'] ?? ''));
        $id = (int)($arParams['id'] ?? 0);

        if ($id <= 0) {
            throw new \Bitrix\Rest\RestException(
                'Не передан id',
                'BOOKING_BAD_REQUEST',
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        $res = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => self::IBLOCK_ID, 'ID' => $id, 'ACTIVE' => 'Y'],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME']
        );

        $obElement = $res->GetNextElement();

        if (!$obElement) {
            throw new \Bitrix\Rest\RestException(
                'Запись не найдена',
                'BOOKING_NOT_FOUND',
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        $booking = self::formatBooking($obElement);

        if ($booking['user_id'] !== $userId) {
            // Не свой access_token — не подтверждаем даже факт существования записи
            throw new \Bitrix\Rest\RestException(
                'Запись не найдена',
                'BOOKING_NOT_FOUND',
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        unset($booking['user_id']);

        return $booking;
    }

    public static function list($arParams): array
    {
        $userId = self::resolveUserIdFromToken((string)($arParams['access_token'] ?? ''));

        $order = strtoupper((string)($arParams['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $res = \CIBlockElement::GetList(
            ['PROPERTY_DATETIME' => $order],
            [
                'IBLOCK_ID' => self::IBLOCK_ID,
                'ACTIVE' => 'Y',
                'PROPERTY_USER_ID' => $userId,
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME']
        );

        $items = [];

        while ($obElement = $res->GetNextElement()) {
            $booking = self::formatBooking($obElement);
            unset($booking['user_id']);
            $items[] = $booking;
        }

        return ['result' => $items];
    }

    private static function formatBooking(\_CIBElement $obElement): array
    {
        $fields = $obElement->GetFields();
        $props = $obElement->GetProperties();

        $datetime = $props['DATETIME']['VALUE'] ?? null;

        if ($datetime instanceof \Bitrix\Main\Type\DateTime) {
            $datetimeFormatted = $datetime->format('Y-m-d H:i:s');
        } elseif (is_string($datetime) && $datetime !== '') {
            $parsed = \DateTime::createFromFormat('d.m.Y H:i:s', $datetime);
            $datetimeFormatted = $parsed ? $parsed->format('Y-m-d H:i:s') : $datetime;
        } else {
            $datetimeFormatted = '';
        }

        return [
            'id' => (int)$fields['ID'],
            'service_id' => (int)($props['SERVICE_ID']['VALUE'] ?? 0),
            'datetime' => $datetimeFormatted,
            'status' => (string)($props['STATUS']['VALUE_XML_ID'] ?? ''),
            'status_name' => (string)($props['STATUS']['VALUE_ENUM'] ?? ''),
            'comment' => (string)($props['COMMENT']['VALUE'] ?? ''),
            'client_name' => (string)($props['CLIENT_NAME']['VALUE'] ?? ''),
            'client_phone' => (string)($props['CLIENT_PHONE']['VALUE'] ?? ''),
            'user_id' => (int)($props['USER_ID']['VALUE'] ?? 0),
        ];
    }

    private static function resolveUserIdFromToken(string $accessToken): int
    {
        global $DB;

        if ($accessToken === '') {
            throw new \Bitrix\Rest\RestException(
                'Не передан access_token',
                'AUTH_REQUIRED',
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        $hash = hash('sha256', $accessToken);

        $res = $DB->Query("
            SELECT USER_ID
            FROM sto_auth_token
            WHERE ACCESS_TOKEN_HASH = '" . $hash . "'
              AND ACCESS_EXPIRES_AT > NOW()
        ");

        $row = $res->Fetch();

        if (!$row) {
            require_once __DIR__ . '/StoAuthFailedException.php';
            throw new StoAuthFailedException(
                'Неверный или истёкший access_token',
                0,
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        return (int)$row['USER_ID'];
    }

    private static function getStatusEnumId(string $xmlId): ?int
    {
        $property = \CIBlockProperty::GetList(
            [],
            ['IBLOCK_ID' => self::IBLOCK_ID, 'CODE' => 'STATUS']
        )->Fetch();

        if (!$property) {
            return null;
        }

        $enum = \CIBlockPropertyEnum::GetList(
            [],
            ['PROPERTY_ID' => $property['ID'], 'XML_ID' => $xmlId]
        )->Fetch();

        return $enum ? (int)$enum['ID'] : null;
    }
}
