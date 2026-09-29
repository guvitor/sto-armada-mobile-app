<?php

use Bitrix\Main\Security\Random;

class StoAuthRestService
{
    const ACCESS_TTL = 3600;       // 1 час
    const REFRESH_TTL = 2592000;   // 30 дней

    public static function onRestServiceBuildDescription(): array
    {
        return [
            'sto.auth.login' => [__CLASS__, 'login'],
            'sto.auth.refresh' => [__CLASS__, 'refresh'],
        ];
    }

    public static function login($arParams): array
    {
        global $DB, $USER;

        $login = trim((string)($arParams['login'] ?? ''));
        $password = (string)($arParams['password'] ?? '');

        if ($login === '' || $password === '') {
            throw new \Bitrix\Rest\RestException(
                'Не переданы login/password',
                'AUTH_BAD_REQUEST',
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        $result = $USER->Login($login, $password, 'N');

        if ($result !== true) {
            // Не пересказываем сырой текст ошибки Bitrix ($result — массив
            // HTML-форматированных строк ядра) — фиксированное сообщение,
            // плюс не различаем «неверный логин» и «неверный пароль» умышленно.
            throw new \Bitrix\Rest\RestException(
                'Неверный логин или пароль',
                'AUTH_FAILED',
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        $userId = (int)$USER->GetID();

        $accessToken = Random::getString(64);
        $refreshToken = Random::getString(64);

        $DB->Query("
            INSERT INTO sto_auth_token
                (USER_ID, ACCESS_TOKEN_HASH, REFRESH_TOKEN_HASH, ACCESS_EXPIRES_AT, REFRESH_EXPIRES_AT, CREATED_AT)
            VALUES (
                " . $userId . ",
                '" . hash('sha256', $accessToken) . "',
                '" . hash('sha256', $refreshToken) . "',
                DATE_ADD(NOW(), INTERVAL " . self::ACCESS_TTL . " SECOND),
                DATE_ADD(NOW(), INTERVAL " . self::REFRESH_TTL . " SECOND),
                NOW()
            )
        ");

        // Логин выполнялся только для проверки пароля — куки сессии REST-клиенту не нужны
        $USER->Logout();

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user_id' => $userId,
        ];
    }

    public static function refresh($arParams): array
    {
        global $DB;

        $refreshToken = (string)($arParams['refresh_token'] ?? '');

        if ($refreshToken === '') {
            throw new \Bitrix\Rest\RestException(
                'Не передан refresh_token',
                'AUTH_BAD_REQUEST',
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        $hash = hash('sha256', $refreshToken);

        $res = $DB->Query("
            SELECT ID, USER_ID
            FROM sto_auth_token
            WHERE REFRESH_TOKEN_HASH = '" . $hash . "'
              AND REFRESH_EXPIRES_AT > NOW()
        ");

        $row = $res->Fetch();

        if (!$row) {
            require_once __DIR__ . '/StoAuthFailedException.php';
            throw new StoAuthFailedException(
                'Неверный или истёкший refresh_token',
                0,
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        $accessToken = Random::getString(64);

        $DB->Query("
            UPDATE sto_auth_token
            SET ACCESS_TOKEN_HASH = '" . hash('sha256', $accessToken) . "',
                ACCESS_EXPIRES_AT = DATE_ADD(NOW(), INTERVAL " . self::ACCESS_TTL . " SECOND)
            WHERE ID = " . (int)$row['ID'] . "
        ");

        return [
            'access_token' => $accessToken,
            'user_id' => (int)$row['USER_ID'],
        ];
    }
}
