/// Настройки REST-транспорта. Мини-REST в обход OAuth (см.
/// mobile_app/README.md, раздел «Мини-REST для мобильного приложения») —
/// `appToken` авторизует само приложение, не пользователя, и передаётся
/// снаружи (`--dart-define=STO_APP_TOKEN=...`), чтобы не хранить секрет
/// в коде.
class ApiConfig {
  static const String baseUrl = 'https://sto.armada-motors.com';

  static const String appToken = String.fromEnvironment(
    'STO_APP_TOKEN',
    defaultValue: '',
  );

  static bool get hasAppToken => appToken.isNotEmpty;
}
