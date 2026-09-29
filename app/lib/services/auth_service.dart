import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/auth_session.dart';
import 'api_client.dart';
import 'api_exception.dart';

/// Держит текущую сессию входа в памяти и синхронизирует её с
/// secure storage устройства (Keychain/Keystore). Один инстанс на
/// приложение — `AuthService.instance`.
class AuthService extends ChangeNotifier {
  AuthService._();

  static final AuthService instance = AuthService._();

  static const _storage = FlutterSecureStorage();
  static const _keyAccessToken = 'sto_access_token';
  static const _keyRefreshToken = 'sto_refresh_token';
  static const _keyUserId = 'sto_user_id';

  final ApiClient _apiClient = ApiClient();

  AuthSession? _session;

  AuthSession? get session => _session;

  bool get isLoggedIn => _session != null;

  /// Загружает сохранённую сессию при старте приложения.
  Future<void> init() async {
    final accessToken = await _storage.read(key: _keyAccessToken);
    final refreshToken = await _storage.read(key: _keyRefreshToken);
    final userIdRaw = await _storage.read(key: _keyUserId);

    if (accessToken != null && refreshToken != null && userIdRaw != null) {
      _session = AuthSession(
        accessToken: accessToken,
        refreshToken: refreshToken,
        userId: int.parse(userIdRaw),
      );
      notifyListeners();
    }
  }

  Future<void> login(String login, String password) async {
    final session = await _apiClient.login(login, password);
    await _persist(session);
  }

  /// Меняет истёкший access_token на новый через refresh_token текущей
  /// сессии. Бросает ApiException, если refresh_token тоже недействителен —
  /// вызывающий код должен в этом случае разлогинить пользователя.
  Future<void> refresh() async {
    final current = _session;
    if (current == null) return;

    final newAccessToken = await _apiClient.refreshAccessToken(current.refreshToken);
    await _persist(AuthSession(
      accessToken: newAccessToken,
      refreshToken: current.refreshToken,
      userId: current.userId,
    ));
  }

  /// Выполняет запрос, требующий access_token текущей сессии. При ответе
  /// сервера AUTH_FAILED (токен истёк/невалиден) один раз пытается
  /// обменять refresh_token на новый access_token и повторяет запрос; если
  /// и это не удаётся — разлогинивает и пробрасывает исходную ошибку.
  Future<T> authorizedRequest<T>(Future<T> Function(String accessToken) request) async {
    final current = _session;
    if (current == null) {
      throw const ApiException('Не выполнен вход');
    }

    try {
      return await request(current.accessToken);
    } on ApiException catch (e) {
      if (e.code != 'AUTH_FAILED') rethrow;
    }

    try {
      await refresh();
    } on ApiException {
      await logout();
      rethrow;
    }

    try {
      return await request(_session!.accessToken);
    } on ApiException catch (e) {
      if (e.code == 'AUTH_FAILED') {
        await logout();
      }
      rethrow;
    }
  }

  Future<void> logout() async {
    _session = null;
    await _storage.delete(key: _keyAccessToken);
    await _storage.delete(key: _keyRefreshToken);
    await _storage.delete(key: _keyUserId);
    notifyListeners();
  }

  Future<void> _persist(AuthSession session) async {
    _session = session;
    await _storage.write(key: _keyAccessToken, value: session.accessToken);
    await _storage.write(key: _keyRefreshToken, value: session.refreshToken);
    await _storage.write(key: _keyUserId, value: session.userId.toString());
    notifyListeners();
  }
}
