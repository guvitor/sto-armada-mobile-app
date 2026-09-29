# sto_app — мобильное приложение СТО Армада Моторс

Flutter-клиент для записи на сервис sto.armada-motors.com: каталог услуг → запись (гостем по имени и телефону или под аккаунтом сайта) → «Мои записи» со статусами.

Backend — мини-REST `/mobile-api/` на сайте (1С-Битрикс). Установка backend, история решений и журнал тестирования — в [../README.md](../README.md).

## Требования

- Flutter SDK с Dart `^3.13.4`
- для Android-сборок: JDK 17 и Android SDK

Платформы: Android (основная, публикуется в Google Play) и Web (для превью и тестирования). iOS-сборка возможна только на macOS с Xcode.

## Токен приложения

Каждый запрос к API подписывается статическим токеном приложения. На сервере это константа `STO_MOBILE_APP_TOKEN` в `init.php`. Локально значение лежит в `../.secrets/sto_app_token`: файл не коммитится, при клонировании репозитория его нужно создать вручную. В код токен не зашит, он передаётся при запуске и сборке:

```
--dart-define=STO_APP_TOKEN=$(cat ../.secrets/sto_app_token)
```

Без токена приложение запустится, но на первом же запросе покажет «Не настроен STO_APP_TOKEN».

`baseUrl` указывает на прод (`lib/config/api_config.dart`), поэтому **любой запуск работает с боевыми данными**: записи создаются в реальном инфоблоке 130.

## Запуск

```
flutter pub get
flutter run -d chrome --dart-define=STO_APP_TOKEN=<токен>
```

Тестовая сборка web и статический сервер:

```
flutter build web --dart-define=STO_APP_TOKEN=<токен>
python -m http.server 8765 --directory build/web
```

После каждой пересборки открывай сборку на новом порту. Service Worker кэширует предыдущую версию, и на старом порту изменения не видны.

## Проверка

```
flutter analyze
flutter test
```

## Сборка под Android

```
flutter build apk --release --dart-define=STO_APP_TOKEN=<токен>        # для установки на телефон
flutter build appbundle --release --dart-define=STO_APP_TOKEN=<токен>  # для Google Play
```

Релизная подпись берётся из `android/key.properties` (шаблон — `android/key.properties.example`). Без этого файла сборка подписывается debug-ключом и для публикации не подходит. Keystore и `key.properties` не должны попадать в репозиторий, храни их отдельно: без них обновить приложение в Google Play нельзя.

Версия задаётся в `pubspec.yaml` (`version: X.Y.Z+N`). Build number `N` увеличивай перед каждой загрузкой в Play Console.

Порядок публикации и материалы листинга — в [../store_listing/publish_checklist.md](../store_listing/publish_checklist.md) и [../README.md](../README.md) (раздел 14).

## Структура

```
lib/
├── main.dart                  # инициализация сессии, MaterialApp
├── config/api_config.dart     # baseUrl, токен из --dart-define
├── services/
│   ├── api_client.dart        # HTTP-вызовы /mobile-api/*, разбор ответа
│   ├── api_exception.dart
│   └── auth_service.dart      # вход/выход, refresh токена, secure storage
├── models/                    # service, booking_request, booking_item, auth_session
└── screens/                   # catalog, booking, login, my_bookings
test/                          # модельные и виджет-тесты
```

## Иконка

Иконка сейчас временная (`assets/icon/icon_temp.jpg`). Чтобы заменить её, положи новый файл, поправь `image_path` в секции `flutter_launcher_icons` в `pubspec.yaml` и выполни:

```
dart run flutter_launcher_icons
```
