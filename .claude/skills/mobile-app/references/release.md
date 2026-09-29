# Релиз под Google Play

Платформа — только Android. Пакет: `com.armadamotors.sto_app`, название «СТО Армада Моторс». Первый релиз собран 20.09.2026 (`version: 1.0.0+1`). Чек-лист публикации — `mobile_app/store_listing/publish_checklist.md`. **Сначала прочитай его и продолжай с того места, где остановились**, а не начинай заново.

## 1. Предпроверка

Из `mobile_app/app/`:

```
flutter pub get
flutter analyze
flutter test
```

- В `android/app/src/main/AndroidManifest.xml` должен быть `<uses-permission android:name="android.permission.INTERNET"/>`, а также разрешения для новых плагинов, если такие появились.
- Проверь, что `android/key.properties` существует. Смотри только наличие файла (`test -f`), не содержимое. Если файла нет, `build.gradle.kts` подпишет релиз debug-ключом. Такой AAB Play отклонит, а при уже опубликованном приложении он вообще не подходит. В этом случае остановись и сообщи пользователю.
- Если изменился backend, убедись, что `mobile_api/` и `rest/` уже выложены на прод: `baseUrl` смотрит в прод.

## 2. Версия

`pubspec.yaml` → `version: X.Y.Z+N`:
- `X.Y.Z` (versionName) — то, что видит пользователь. Исправление → patch, новая функция → minor.
- `N` (versionCode) — **строго больше**, чем в любой ранее загруженной в Play Console сборке, включая внутреннее тестирование. Повтор приводит к отказу при загрузке.

## 3. Сборка

```
flutter build appbundle --release --dart-define=STO_APP_TOKEN=$(cat ../.secrets/sto_app_token)
flutter build apk --release --dart-define=STO_APP_TOKEN=<тот же токен>
```

Результаты (ориентир по размеру — около 50 МБ):
- `build/app/outputs/bundle/release/app-release.aab` — загружается в Play Console;
- `build/app/outputs/flutter-apk/app-release.apk` — для ручной установки на телефон.

Без `--dart-define` сборка пройдёт, но приложение на первом же запросе покажет «Не настроен STO_APP_TOKEN». Это самая частая ошибка релиза.

## 4. Проверка артефакта

```
jarsigner -verify -certs build/app/outputs/bundle/release/app-release.aab
```

Ожидается `jar verified` и сертификат `CN=Armada Motors`: смотри в выводе `-verbose -certs`, строку можно найти через `grep -oE "CN=[^,]*"`. У APK подпись схемы v2 и выше, `jarsigner` её сертификат не покажет. Для APK используй `C:\Android\sdk\build-tools\<версия>\apksigner.bat verify --print-certs app-release.apk`. Если видишь `CN=Android Debug`, подпись не та и публиковать нельзя.

Функциональная проверка — на телефоне пользователя через `app-release.apk`: каталог, гостевая запись, вход, «Мои записи». Эмулятор здесь не работает, а веб-превью не проверяет нативные разрешения.

## 5. Материалы листинга

Всё лежит в `mobile_app/store_listing/`:
- `google_play.md` — название, краткое и полное описание, ответы для анкеты Data safety. Если приложение начало собирать новые данные (новые поля формы, аналитика, push), обнови раздел Data safety.
- `privacy_policy_app_addendum.md` — дополнение к `/kontakty/politika-konfidentsialnosti/`. На сайт его публикует пользователь через админку.
- `icon_512.png` — временная иконка. Когда появится брендовый ассет, замени `app/assets/icon/icon_temp.jpg` (или поправь путь в `pubspec.yaml`), выполни `dart run flutter_launcher_icons` и пересоздай `icon_512.png` (512×512 PNG).
- `screenshots/` — 2–8 скриншотов, соотношение сторон не больше 2:1. Снимаются через Playwright с web-сборки в разрешении 1080×1920 (как — см. SKILL.md, «Окружение и запуск»).

## 6. Передача пользователю

Публикацию в Play Console (аккаунт, оплата, загрузка AAB, анкеты, отправка на проверку) выполняет пользователь. Ты:
- отмечаешь в `publish_checklist.md` выполненное (`[x]`) и дописываешь новые шаги, если они появились;
- дописываешь в `mobile_app/README.md` подраздел раздела 14: версию, дату, что вошло в сборку, результат `jarsigner`;
- в итоговом ответе называешь пути к AAB и APK и список оставшихся ручных шагов.

Для первого релиза рекомендуй дорожку Internal testing перед Production.

## Обновление уже опубликованного приложения

Порядок тот же: подними versionCode, собери сборку с **тем же** keystore, загрузи AAB в новый выпуск. Если keystore утерян, обновить приложение нельзя. Не пересоздавай keystore без явного решения пользователя: это означает Play App Signing reset через поддержку Google или новое приложение.
