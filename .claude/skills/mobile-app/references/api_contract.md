# Контракт мини-REST `/mobile-api/`

Базовый URL: `https://sto.armada-motors.com/mobile-api/`. Исходники лежат в `mobile_app/mobile_api/`. Бизнес-логика — в `mobile_app/rest/Sto*RestService.php`.

## Общие правила

- **Токен приложения** передаётся во всех запросах query-параметром: `?token=<STO_MOBILE_APP_TOKEN>`. Без него или с неверным — `401 {"error":"AUTH_FAILED","error_description":"Неверный или отсутствующий token"}`.
- **Пользовательский `access_token`** (Фаза 2) передаётся в query (`bookings.php`) или в JSON-теле (`booking.php`). Заголовки не используются.
- Тело POST — JSON, `Content-Type: application/json`.
- Неверный HTTP-метод — `405 METHOD_NOT_ALLOWED`. `OPTIONS` — `200` с CORS-заголовками (preflight для Flutter Web).
- Успех — `{"result": ...}`. Исключение — `auth.php` и `auth_refresh.php`: они отдают поля на верхнем уровне.
- Ошибки:
  - `RestException` → `400 {"error": "<текст сообщения>"}`. **Машинный код (`BOOKING_BAD_REQUEST` и т. п.) в тело не попадает**, в `error` приходит русский текст. В таблицах ниже код указан для ориентира по исходникам.
  - `StoAuthFailedException` → `400 {"error": "<текст>", "code": "AUTH_FAILED"}`. По `code` клиент понимает, что нужен refresh или logout.
  - Прочие исключения → `500 {"error":"INTERNAL_ERROR"|"CATALOG_ERROR","error_description":"..."}`.
- Даты — строго `Y-m-d H:i:s`.

## Эндпоинты

### `GET catalog.php`
Возвращает активные элементы инфоблока 117, у которых есть цена. Берётся первая строка `Bitrix\Catalog\PriceTable`: в каталоге один тип цены, подтверждено 20.09.2026. Если типов цен станет несколько, нужно фильтровать по `CATALOG_GROUP_ID`.

```json
{"result": [{"ID": 123, "NAME": "Замена тормозной жидкости", "PRICE": 840.0, "CURRENCY": "RUB"}]}
```

### `POST booking.php`
Тело: `name`\*, `phone`\*, `service_id`\*, `datetime`\* (`Y-m-d H:i:s`), `comment`, `access_token` (необязателен: с ним запись привязывается к `USER_ID`, без него создаётся гостевая).
Создаёт элемент инфоблока 130 со статусом `NEW`. Ответ: `{"result": <ID записи>}`.

| Ситуация | Код в исходнике | Текст |
|---|---|---|
| нет обязательного поля | `BOOKING_BAD_REQUEST` | Не переданы обязательные параметры name/phone/service_id/datetime |
| услуги нет или неактивна | `BOOKING_SERVICE_NOT_FOUND` | Услуга с указанным service_id не найдена или не активна |
| плохой `datetime` | `BOOKING_BAD_DATETIME` | Некорректный формат datetime, ожидается Y-m-d H:i:s |
| нет enum `NEW` у `STATUS` | `BOOKING_CONFIG_ERROR` | — (проблема настройки инфоблока) |
| ошибка `CIBlockElement::Add` | `BOOKING_ADD_FAILED` | Ошибка создания записи: … |
| переданный `access_token` невалиден | `AUTH_FAILED` (поле `code`) | Неверный или истёкший access_token |

### `GET bookings.php`
Query: `access_token`\*, `order` (`ASC`/`DESC`, по умолчанию `DESC` по дате визита). Возвращает записи текущего пользователя:

```json
{"result": [{"id": 1, "service_id": 123, "datetime": "2026-09-25 10:00:00", "status": "NEW", "status_name": "Новая", "comment": "", "client_name": "...", "client_phone": "..."}]}
```

Без токена — `AUTH_REQUIRED`, с неверным или истёкшим — `code: AUTH_FAILED`.

### `POST auth.php`
Тело: `login`, `password`. Пароль проверяется через `CUser::Login()`, после чего сразу выполняется `Logout()` (cookie не нужны). Ответ:
```json
{"access_token": "...", "refresh_token": "...", "user_id": 42}
```
TTL: access — 1 час, refresh — 30 дней. Токены — случайные строки по 64 символа, в `sto_auth_token` хранятся SHA-256-хэши. Неверный логин и неверный пароль дают один и тот же текст («Неверный логин или пароль»). Повторный вход не инвалидирует прежние сессии.

### `POST auth_refresh.php`
Тело: `refresh_token`. Ответ: `{"access_token": "...", "user_id": 42}`. Пустой токен — `AUTH_BAD_REQUEST`, невалидный — `code: AUTH_FAILED`.

### Не используется приложением
`sto.booking.get` (`StoBookingRestService::get`) — класс есть, отдельного эндпоинта в `mobile_api/` нет. Чужую запись метод возвращает как `BOOKING_NOT_FOUND`. Штатные Bitrix REST-методы `sto.*` (регистрация через `OnRestServiceBuildDescription`) требуют OAuth, который для этой редакции не настроен, поэтому приложение их не вызывает.

## Smoke-тест (curl)

`TOKEN=$(cat mobile_app/.secrets/sto_app_token)` на время теста, в файлы и вывод итога не попадает. Тела с кириллицей передавай из файла в scratchpad.

```bash
B=https://sto.armada-motors.com/mobile-api
curl -s "$B/catalog.php"                                   # 401 AUTH_FAILED
curl -s "$B/catalog.php?token=$TOKEN"                      # 200 result[]
curl -s "$B/booking.php?token=$TOKEN"                      # 405 (GET)
curl -s -X OPTIONS -i "$B/booking.php"                     # 200 + Access-Control-*
curl -s -X POST "$B/booking.php?token=$TOKEN" \
  -H 'Content-Type: application/json' -d '{}'              # 400 обязательные параметры
curl -s -X POST "$B/booking.php?token=$TOKEN" \
  -H 'Content-Type: application/json' -d @bad_service.json # 400 услуга не найдена
curl -s -X POST "$B/auth_refresh.php?token=$TOKEN" \
  -H 'Content-Type: application/json' -d '{"refresh_token":"x"}'  # 400 code AUTH_FAILED
curl -s "$B/bookings.php?token=$TOKEN&access_token=x"      # 400 code AUTH_FAILED
```

Успешный `booking.php` **создаёт реальную запись** на проде. Запускай его только когда это нужно, а ID созданной записи заноси в README как подлежащую удалению.
