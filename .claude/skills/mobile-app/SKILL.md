---
name: mobile-app
description: Работа с мобильным приложением sto.armada-motors.com в папке mobile_app/ — Flutter-клиент (app/), мини-REST на PHP (mobile_api/), REST-классы Битрикс (rest/), скрипты установки, материалы Google Play (store_listing/). Используй для любых задач по mobile_app — разобраться в структуре, запустить приложение или веб-превью, внести правку во Flutter или PHP-эндпоинт, проверить ошибки (analyze/test/curl), обновить README, собрать и подготовить релиз под Google Play, проверить iOS-сборку в GitHub Actions.
---

# Мобильное приложение СТО Армада Моторс

Приложение для записи на сервис: каталог услуг → запись (гостем по имени и телефону или под аккаунтом сайта) → «Мои записи» со статусами. Backend — сам сайт на 1С-Битрикс (редакция «Управление сайтом», **не** Bitrix24). Статус на 20.09.2026: MVP и Фаза 2 (авторизация + личный кабинет) готовы и проверены на проде. Идёт выпуск в Google Play (только Android), подписанные AAB/APK собраны. iOS (05.10.2026): неподписанная сборка идёт в GitHub Actions; для App Store нет аккаунта Apple Developer.

Источник правды по истории и установке — `mobile_app/README.md`. ТЗ — `development_plan/tz-mobile-app.md` (MVP) и `development_plan/tz-mobile-app-phase2.md`. Прежде чем что-то менять, прочитай нужный раздел README: там записаны решения, которые уже принимались, и баги, которые уже чинились.

## Структура

```
mobile_app/
├── README.md                     # порядок установки backend + журнал по разделам (1–15)
├── .github/workflows/ios-unsigned.yml  # неподписанная iOS-сборка на macOS-раннере (README, раздел 15)
├── add_*.php, install_*.php,     # разовые идемпотентные скрипты установки (prolog_before.php,
│   check_catalog_rest_access.php #   запускаются с корня сайта); уже выполнены на проде
├── mobile_api/                   # мини-REST → на сервере лежит в /mobile-api/ (с дефисом!)
│   ├── _auth.php                 #   stoCheckAppToken(): ?token= против STO_MOBILE_APP_TOKEN
│   ├── _cors.php                 #   CORS * + preflight OPTIONS (нужен только для Flutter Web)
│   ├── catalog.php  GET          #   услуги инфоблока 117 + цена (PriceTable)
│   ├── booking.php  POST         #   → StoBookingRestService::add()
│   ├── bookings.php GET          #   → StoBookingRestService::list() (нужен access_token)
│   ├── auth.php     POST         #   → StoAuthRestService::login()
│   └── auth_refresh.php POST     #   → StoAuthRestService::refresh()
├── rest/                         # классы → на сервере в /rest/ от корня сайта
│   ├── StoBookingRestService.php #   add / get / list, статусы, форматирование
│   ├── StoAuthRestService.php    #   login / refresh, токены в таблице sto_auth_token (SHA-256)
│   ├── StoAuthFailedException.php
│   └── init_snippet.php          #   вставляется в /local/php_interface/init.php (константа токена-плейсхолдер + регистрация)
├── app/                          # Flutter-проект sto_app (com.armadamotors.sto_app)
│   ├── lib/config/api_config.dart     # baseUrl = прод, appToken из --dart-define
│   ├── lib/services/api_client.dart   # все HTTP-вызовы, разбор конверта ответа
│   ├── lib/services/auth_service.dart # синглтон ChangeNotifier, secure storage, авто-refresh
│   ├── lib/models/                    # service, booking_request, booking_item, auth_session
│   ├── lib/screens/                   # catalog → booking, login, my_bookings
│   ├── test/                          # service_model_test.dart, widget_test.dart
│   ├── android/                       # build.gradle.kts с условной релизной подписью
│   └── ios/                           # Xcode-проект, bundle ID com.armadamotors.stoApp, iOS 15.0+
└── store_listing/                # google_play.md, privacy_policy_app_addendum.md,
                                  # publish_checklist.md, icon_512.png
```

Ключевые ID Битрикса: каталог услуг — инфоблок **117**, записи на сервис — инфоблок **130** (свойства `SERVICE_ID`, `DATETIME`, `STATUS`, `USER_ID`, `COMMENT`, `CLIENT_NAME`, `CLIENT_PHONE`). `STATUS` — список: `NEW`, `CONFIRMED`, `IN_PROGRESS`, `DONE`, `CANCELLED`. Контракт эндпоинтов — в [references/api_contract.md](references/api_contract.md).

## Окружение и запуск

Flutter SDK: `C:\src\flutter`, JDK 17, Android SDK: `C:\Android\sdk`. Все команды Flutter запускаются из `mobile_app/app/`.

- **Android-эмулятор на этой машине не работает** (вложенная виртуализация, зависает на WHPX). Не пытайся его запускать. Если просят «показать на эмуляторе», предупреди об этом и предложи веб-превью. Проверка на устройстве — только на телефоне пользователя, через APK.
- **Токен приложения** передаётся только при сборке: `--dart-define=STO_APP_TOKEN=<токен>`. Без него `ApiClient` выбрасывает «Не настроен STO_APP_TOKEN». Значение лежит в `mobile_app/.secrets/sto_app_token`: файл в `.gitignore`, в `rest/init_snippet.php` вместо него плейсхолдер. Подставляй через `$(cat ../.secrets/sto_app_token)` из `app/`. **Не переписывай** значение в код, README, скилл или коммит.
- **Быстрое превью:** `flutter run -d chrome --dart-define=STO_APP_TOKEN=...`
- **Сценарный прогон через браузер** (claude-in-chrome / playwright):
  ```
  flutter build web --dart-define=STO_APP_TOKEN=...
  python -m http.server <порт> --directory build/web
  ```
  **После каждой пересборки бери новый порт** (8765 → 8766 → …). Service Worker Flutter Web кэширует предыдущую сборку, и на старом порту изменения «не появляются», хотя код верный.
- `baseUrl` захардкожен на прод (`https://sto.armada-motors.com`). Любой запуск ходит в боевой API и создаёт **настоящие записи** в инфоблоке 130. Тестовые записи помечай в имени клиента (например, «TEST … delete me»). В комментарий пометку не пиши: экран «Мои записи» показывает комментарий, и он попадёт в скриншоты, а их ID записывай в README (см. ниже).
- Скриншоты для Play Store снимай через **Playwright MCP**, а не через claude-in-chrome: у claude-in-chrome resize не меняет захват. В `browser_run_code_unsafe` создай новый контекст `{viewport: {width: 360, height: 640}, deviceScaleFactor: 3, isMobile: true, hasTouch: true}`: получится 1080×1920. Flutter рисует на canvas, поэтому кликай мышью по координатам в CSS-пикселях, а текст вводи через `keyboard.type`. Форму записи не отправляй: это создаст реальную запись. Файлы клади в `store_listing/screenshots/`. Playwright сохраняет свои снимки в `.playwright-mcp/` в корне проекта.

## Как вносить изменения

Перед нетривиальной правкой коротко опиши план (какие файлы и какой результат) и дождись «ок». Это правило проекта из CLAUDE.md.

### Flutter (app/lib)
- Все сетевые вызовы делай только через `ApiClient`. Токен приложения добавляет `_uri()`. Ошибки приходят как `ApiException`. На экранах их ловит `catch (ApiException)` и показывает `SnackBar`: молча ошибки не глотаем (такой баг уже был на дне 9).
- Запросы с пользовательским токеном оборачивай в `AuthService.instance.authorizedRequest(...)`. Он сам делает refresh, а при неудаче выполняет `logout()` и пробрасывает исключение.
- Модели разбирают ответ через `fromJson` с ключами как в ответе PHP (`ID`, `NAME`, `PRICE`, `CURRENCY` у каталога). Если меняется контракт, правь `fromJson` и тест в `test/` вместе.
- Язык UI и комментариев — русский, стиль комментариев как в существующем коде: объясняем «почему» и ссылаемся на раздел README.
- Добавил плагин, которому нужны нативные разрешения, — сразу проверь `android/app/src/main/AndroidManifest.xml`. Flutter Web разрешений не требует, поэтому веб-тест такие ошибки не ловит (так пропустили `INTERNET`).

### PHP (mobile_api/, rest/)
- Каждый эндпоинт устроен одинаково: `prolog_before.php` → `_cors.php` → `_auth.php` → проверка метода (405) → `stoCheckAppToken()` → `Loader::includeModule(...)` → `try/catch` с конвертом `{"result": ...}` / `{"error": ..., "error_description": ...}`. Новый эндпоинт копируй с ближайшего существующего.
- **Никаких кастомных HTTP-заголовков.** На сервере (Apache + mod_fcgid, PHP 8.2) они не доходят до `$_SERVER`. Токены и флаги передавай только query-параметром или телом.
- Используешь `CIBlockElement` — подключай `iblock` (и `catalog` для цен), а не только `rest`. Без этого получишь `Class "CIBlockElement" not found`.
- Ловушки Битрикс API, которые уже стоили отдельных раундов отладки:
  - в `CIBlockElement::GetList()` обязательно выбирай `IBLOCK_ID` в select, иначе `GetProperties()` молча вернёт пусто;
  - `CIBlockPropertyEnum::GetList()` фильтрует по `PROPERTY_ID`, а не по `CODE`;
  - `GetNextElement()` возвращает `_CIBElement`;
  - 4-й параметр `CUser::Login()` — это `password_original`, а не «secure»;
  - дату отдаём строго в формате `Y-m-d H:i:s`.
- Ошибки авторизации не должны раскрывать, существует ли аккаунт или запись: одинаковый текст для «нет логина» и «неверный пароль», `BOOKING_NOT_FOUND` для чужой записи.
- `sto_auth_token` хранит только SHA-256-хэши токенов, сырые токены в БД не пишем.
- Не используй модуль `modules/simbirsoft.mobile` (устарел и небезопасен) и штатный `/rest/` с OAuth (для этой редакции не настроен). Новые нужды API закрываем через `mobile_api/*.php`.

### Деплой на сервер
Код в репозитории — локальная копия, на прод он не попадает сам. Соответствие путей: `mobile_app/mobile_api/*` → `/mobile-api/`, `mobile_app/rest/*.php` → `/rest/`, `init_snippet.php` → `/local/php_interface/init.php`. Заливает пользователь (или ты — только по явной просьбе). В итоге всегда перечисляй, какие файлы нужно выложить и куда. Если правится `init.php`, не пользуйся встроенным редактором и файловым менеджером админки (см. память про ловушки админки).

## Проверка ошибок

Минимум после любой правки, из `mobile_app/app/`:

```
flutter analyze
flutter test
```

Для PHP: `php -l <файл>` по каждому изменённому файлу, если `php` доступен локально. Если нет — внимательно перечитай код, а в итоге явно напиши, что линт не запускался.

Проверка API на проде — curl (набор кейсов в [references/api_contract.md](references/api_contract.md#smoke-тест-curl)). Тело с кириллицей передавай **через файл** (`-d @body.json`), а не инлайн: в консоли этой машины кодировка ломается. Временные файлы клади в scratchpad.

Сценарий в UI: сборка web → новый порт → браузер. Прогони путь, который затронула правка, плюс регрессию гостевой записи: она не должна требовать логина.

Если после ребилда «ничего не изменилось» — сначала смени порт, потом ищи баг.

## Обновление README

`mobile_app/README.md` — это журнал разворачивания и решений, а не пользовательская документация. Правила:
- Новый этап оформляй новым разделом `## N. Название (дата ДД.ММ.ГГГГ)` по порядку. Уточнение к существующему пункту — подразделом (`### 14.5.`).
- В разделе пиши: что сделано и зачем, какие файлы и куда их положить на сервере, как проверено (curl, UI), найденные баги с причиной, что осталось на сервере (**ID тестовых записей в инфоблоке 130** и диагностические скрипты) и что пользователь должен сделать руками.
- Итоговый статус — жирным (`**Проверено на проде.**`, `**Багов не найдено.**`), по фактам. Если что-то не проверено, так и пиши.
- Секреты не пишем: ни значение токена, ни пароли тестового аккаунта, ни пароли keystore.
- Если изменился контракт API, обнови и `references/api_contract.md` в этом скилле.
- Если меняется статус работ, обнови раздел «Статус» и `store_listing/publish_checklist.md`, если он затронут.

`app/README.md` — README для разработчика: требования, токен, запуск, проверки, сборка, структура `lib/`. Обновляй его, когда меняются команды запуска и сборки, структура `lib/` или процесс подписи. Историю и журнал туда не переносим, они живут в `../README.md`.

## Подготовка к публикации

Пошагово — в [references/release.md](references/release.md). Кратко:
1. Подними версию в `pubspec.yaml` (`version: X.Y.Z+N`). **Build number `N` обязан расти** с каждой загрузкой в Play Console.
2. Выполни `flutter analyze` и `flutter test`, проверь манифест (`INTERNET`, новые разрешения).
3. Собери `flutter build appbundle --release --dart-define=STO_APP_TOKEN=...` (плюс `apk --release` для проверки на телефоне).
4. Проверь подпись: `jarsigner -verify -certs` — должно быть `CN=Armada Motors`, а не Android Debug.
5. Сверься с `store_listing/publish_checklist.md`: отметь сделанное и перечисли, что осталось пользователю.

## iOS-сборка

Локально iOS не собрать: Windows, Xeon E5-2650 v2 без AVX2 (macOS для Xcode 26 в VM не пойдёт), включён Hyper-V, VMware не установлен. Не предлагай VM с macOS. Собираем в GitHub Actions.

- Workflow `mobile_app/.github/workflows/ios-unsigned.yml`: Flutter 3.47.5 → `pub get` → `analyze` → `test` → `flutter build ios --release --no-codesign` → артефакт `sto_app-ios-unsigned` (неподписанный `.ipa`, 7 дней). Идёт ~3–6 мин.
- Триггеры: ручной запуск и push в `main` с изменениями в `app/**` или в самом workflow. Минуты macOS в приватном репозитории считаются x10, поэтому триггеры не расширяй без причины.
- Токен — секрет репозитория `STO_APP_TOKEN` (заведён 05.10.2026). Обновлять: `tr -d '\r\n' < .secrets/sto_app_token | gh secret set STO_APP_TOKEN --repo guvitor/sto-armada-mobile-app`. Значение не выводи. Что токен дошёл — в логе шага сборки строка `STO_APP_TOKEN: ***`.
- `gh` — по полному пути `"/c/Program Files/GitHub CLI/gh.exe"`. Запуск: `gh workflow run ios-unsigned.yml --ref main`, ожидание: `gh run watch <id> --exit-status` в фоне. Для push изменений в `.github/workflows/` токену `gh` нужен scope `workflow` (уже выдан). Если push отклонён с «without `workflow` scope», пользователь выполняет `gh auth refresh -h github.com -s workflow` сам: нужен вход в браузере.
- Неподписанную сборку на устройство не поставить. Для TestFlight/App Store нужны Apple Developer Program ($99/год, оформление и оплата из РФ затруднены — решает пользователь), сертификат, provisioning profile и iOS-иконки (`flutter_launcher_icons` сейчас только `android: true`). Это следующий этап, пока не начат.

## Границы

- Git-репозиторий — `mobile_app/` (приватный GitHub `sto-armada-mobile-app`, ветка `main`). Перед каждым коммитом прогоняй `git grep --cached -lE "[0-9a-f]{64}"` (кроме `pubspec.lock`) и проверяй, что `.secrets/`, `key.properties` и `*.keystore` не попали в индекс. Push делай только по просьбе.
- У скилла две копии: рабочая в `.claude/skills/mobile-app/` в корне сайта и копия в репозитории `mobile_app/.claude/skills/mobile-app/`. После правки скилла синхронизируй копию в репозитории и закоммить её.

- `android/key.properties` и `android/sto_app_release.keystore` **не открывать, не копировать, не пересоздавать**. Потеря или замена ключа делает обновления в Play Store невозможными. Если `key.properties` отсутствует, сборка молча подпишется debug-ключом: сообщи об этом, а не публикуй такой AAB.
- Не деплоить на прод, не удалять записи в инфоблоке 130 и не менять настройки инфоблоков и REST в админке без явной просьбы.
- Не отправлять ничего в Google Play Console от имени пользователя. Аккаунт, оплата, загрузка и модерация — на стороне пользователя.
- Разовые скрипты установки в `mobile_app/` уже выполнены. Повторный запуск безопасен (они идемпотентны), но без причины их не запускай.
- Диагностические PHP-скрипты на сервере публично доступны. Если создал такой скрипт, в итоге напомни удалить его и запиши это в README.
- Файлы и папки не удалять и не переименовывать без разрешения (правило проекта).
