import 'api_service.dart';

class AuthService {
  AuthService(this._api);
  final ApiService _api;

  Future<Map<String, dynamic>> signIn(String email, String password) => _api.login(email, password);

  Future<Map<String, dynamic>> signUp({
    required String name,
    required String email,
    required String password,
  }) =>
      _api.register(name: name, email: email, password: password);

  Future<Map<String, dynamic>> currentUser() => _api.currentUser();
  Future<void> signOut() => _api.logout();
  Future<void> requestPasswordReset(String email) => _api.forgotPassword(email);
}
