class AuthSession {
  final String accessToken;
  final String refreshToken;
  final int userId;

  const AuthSession({
    required this.accessToken,
    required this.refreshToken,
    required this.userId,
  });
}
