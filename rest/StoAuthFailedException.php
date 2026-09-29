<?php

/**
 * Маркер «access_token/refresh_token невалиден или истёк» — отдельный
 * тип вместо строкового кода через RestException::getCode(): у Bitrix
 * RestException, как и у обычного \Exception, второй аргумент
 * конструктора приводится к int, поэтому строка 'AUTH_FAILED' молча
 * превращается в 0 (проверено 20.09.2026, mobile_api/*.php отдавали
 * "code":0 вместо кода). mobile_api/*.php ловят этот тип отдельно,
 * до общего catch(RestException), и отдают стабильный машиночитаемый
 * код 'AUTH_FAILED' в ответе — нужен Flutter-стороне (AuthService.
 * authorizedRequest()) для авто-refresh токена.
 */
class StoAuthFailedException extends \Bitrix\Rest\RestException
{
}
