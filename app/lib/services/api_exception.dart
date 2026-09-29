class ApiException implements Exception {
  final String message;

  /// Машиночитаемый код ошибки (например, `AUTH_FAILED`) — для логики
  /// повторов/разлогина, отдельно от текста для показа пользователю.
  final String? code;

  const ApiException(this.message, {this.code});

  @override
  String toString() => message;
}
