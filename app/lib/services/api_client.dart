import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../models/auth_session.dart';
import '../models/booking_item.dart';
import '../models/booking_request.dart';
import '../models/service.dart';
import 'api_exception.dart';

/// REST-клиент для sto.armada-motors.com через мини-REST в обход OAuth
/// (см. mobile_app/README.md, раздел «Мини-REST для мобильного
/// приложения»). Токен приложения — query-параметром `?token=...`, не
/// HTTP-заголовком: на nginx+PHP-FPM кастомные заголовки без явной
/// настройки сервера не доходят до PHP (проверено 19.09.2026).
class ApiClient {
  Uri _uri(String path, [Map<String, String> extraParams = const {}]) {
    return Uri.parse('${ApiConfig.baseUrl}$path').replace(queryParameters: {
      'token': ApiConfig.appToken,
      ...extraParams,
    });
  }

  Future<Map<String, dynamic>> _decode(http.Response response) {
    final body = jsonDecode(response.body) as Map<String, dynamic>;

    if (body.containsKey('error')) {
      final description = body['error_description'] as String?;
      throw ApiException(
        description != null && description.isNotEmpty
            ? '${body['error']}: $description'
            : '${body['error']}',
        code: body['code'] as String?,
      );
    }
    if (response.statusCode != 200) {
      throw ApiException('HTTP ${response.statusCode}');
    }

    return Future.value(body);
  }

  Future<List<Service>> getServices() async {
    if (!ApiConfig.hasAppToken) {
      throw const ApiException(
        'Не настроен STO_APP_TOKEN (см. mobile_app/README.md, «Мини-REST для мобильного приложения»)',
      );
    }

    final response = await http.get(_uri('/mobile-api/catalog.php'));
    final body = await _decode(response);

    final items = (body['result'] as List<dynamic>).cast<Map<String, dynamic>>();
    return items.map(Service.fromJson).toList();
  }

  Future<int> createBooking(BookingRequest request, {String? accessToken}) async {
    if (!ApiConfig.hasAppToken) {
      throw const ApiException(
        'Не настроен STO_APP_TOKEN (см. mobile_app/README.md, «Мини-REST для мобильного приложения»)',
      );
    }

    final payload = request.toJson();
    if (accessToken != null) {
      payload['access_token'] = accessToken;
    }

    final response = await http.post(
      _uri('/mobile-api/booking.php'),
      headers: const {'Content-Type': 'application/json'},
      body: jsonEncode(payload),
    );
    final body = await _decode(response);

    return body['result'] as int;
  }

  Future<List<BookingItem>> getMyBookings(String accessToken, {String order = 'DESC'}) async {
    final response = await http.get(
      _uri('/mobile-api/bookings.php', {'access_token': accessToken, 'order': order}),
    );
    final body = await _decode(response);

    final items = (body['result'] as List<dynamic>).cast<Map<String, dynamic>>();
    return items.map(BookingItem.fromJson).toList();
  }

  Future<AuthSession> login(String login, String password) async {
    if (!ApiConfig.hasAppToken) {
      throw const ApiException(
        'Не настроен STO_APP_TOKEN (см. mobile_app/README.md, «Мини-REST для мобильного приложения»)',
      );
    }

    final response = await http.post(
      _uri('/mobile-api/auth.php'),
      headers: const {'Content-Type': 'application/json'},
      body: jsonEncode({'login': login, 'password': password}),
    );
    final body = await _decode(response);

    return AuthSession(
      accessToken: body['access_token'] as String,
      refreshToken: body['refresh_token'] as String,
      userId: body['user_id'] as int,
    );
  }

  Future<String> refreshAccessToken(String refreshToken) async {
    final response = await http.post(
      _uri('/mobile-api/auth_refresh.php'),
      headers: const {'Content-Type': 'application/json'},
      body: jsonEncode({'refresh_token': refreshToken}),
    );
    final body = await _decode(response);

    return body['access_token'] as String;
  }
}
