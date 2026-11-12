import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/destination.dart';

class ApiService {
  ApiService({Dio? dio, TokenStore? tokenStore})
    : _tokenStore = tokenStore ?? SecureTokenStore(),
      _dio =
          dio ??
          Dio(
            BaseOptions(
              baseUrl: const String.fromEnvironment(
                'API_BASE_URL',
                defaultValue: 'http://10.0.2.2/tourisme_ivoire/backend/public/',
              ),
              connectTimeout: const Duration(seconds: 12),
              receiveTimeout: const Duration(seconds: 30),
              headers: const {'Accept': 'application/json'},
            ),
          ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStore.readToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  final Dio _dio;
  final TokenStore _tokenStore;

  Future<bool> get hasSession async =>
      (await _tokenStore.readToken())?.isNotEmpty ?? false;

  Future<List<Destination>> fetchDestinations({
    String? search,
    String? category,
    int? regionId,
    int? districtId,
    int page = 1,
    int perPage = 12,
  }) async {
    final queryParameters = <String, dynamic>{
      'page': page,
      'per_page': perPage.clamp(1, 50),
      if (search != null && search.trim().isNotEmpty) 'q': search.trim(),
      if (category != null && category.isNotEmpty) 'categorie': category,
    };
    if (regionId != null) queryParameters['region_id'] = regionId;
    if (districtId != null) queryParameters['district_id'] = districtId;
    final response = await _dio.get<Map<String, dynamic>>(
      'api/destinations',
      queryParameters: queryParameters,
    );
    final payload = _unwrap(response.data);
    final rows = payload is List
        ? payload
        : payload is Map
        ? payload['items']
        : null;
    if (rows is! List) {
      throw const FormatException('Format de réponse destinations invalide.');
    }
    return rows
        .whereType<Map>()
        .map((row) => Destination.fromJson(Map<String, dynamic>.from(row)))
        .toList(growable: false);
  }

  Future<Destination> fetchDestination(int id) async {
    final response = await _dio.get<Map<String, dynamic>>(
      'api/destinations/$id',
    );
    final payload = _unwrap(response.data);
    if (payload is! Map) {
      throw const FormatException('Détails de destination invalides.');
    }
    return Destination.fromJson(Map<String, dynamic>.from(payload));
  }

  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await _dio.post<Map<String, dynamic>>(
      'api/auth/login',
      data: {'email': email.trim(), 'mot_de_passe': password},
    );
    return _saveAuthResponse(response.data);
  }

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
  }) async {
    final response = await _dio.post<Map<String, dynamic>>(
      'api/auth/register',
      data: {
        'nom': name.trim(),
        'email': email.trim(),
        'mot_de_passe': password,
      },
    );
    return _saveAuthResponse(response.data);
  }

  Future<void> forgotPassword(String email) async {
    await _dio.post<void>(
      'api/auth/forgot-password',
      data: {'email': email.trim()},
    );
  }

  Future<void> resetPassword({
    required String email,
    required String token,
    required String password,
  }) async {
    await _dio.post<void>(
      'api/auth/reset-password',
      data: {'email': email.trim(), 'token': token, 'mot_de_passe': password},
    );
  }

  Future<Map<String, dynamic>> currentUser() async {
    final response = await _dio.get<Map<String, dynamic>>('api/auth/me');
    final payload = _unwrap(response.data);
    if (payload is! Map) {
      throw const FormatException('Profil utilisateur invalide.');
    }
    return Map<String, dynamic>.from(payload);
  }

  Future<Map<String, dynamic>> updateProfile(
    Map<String, dynamic> profile,
  ) async {
    final response = await _dio.put<Map<String, dynamic>>(
      'api/auth/me',
      data: profile,
    );
    final payload = _unwrap(response.data);
    if (payload is! Map) {
      throw const FormatException('Profil utilisateur invalide.');
    }
    return Map<String, dynamic>.from(payload);
  }

  Future<void> logout() async {
    try {
      await _dio.post<void>('api/auth/logout');
    } finally {
      await _tokenStore.clearToken();
    }
  }

  Future<List<dynamic>> getFavorites() async =>
      _asList(await _get('api/favorites'));

  Future<void> addFavorite(int destinationId) async {
    await _dio.post<void>(
      'api/favorites',
      data: {'destination_id': destinationId},
    );
  }

  Future<void> removeFavorite(int favoriteId) async {
    await _dio.delete<void>('api/favorites/$favoriteId');
  }

  Future<List<dynamic>> getTransports() async =>
      _asList(await _get('api/transports'));

  Future<List<dynamic>> searchItineraries(String from, String to) async =>
      _asList(await _post('api/itineraires', {'depart': from, 'arrivee': to}));

  Future<List<dynamic>> getItineraries() async =>
      _asList(await _get('api/itineraires'));

  Future<Map<String, dynamic>> createReservation(
    int itineraryId, {
    int quantity = 1,
  }) async => _asMap(
    await _post('api/reservations', {
      'itineraire_id': itineraryId,
      'quantite': quantity,
    }),
  );

  Future<List<dynamic>> getReservations() async =>
      _asList(await _get('api/reservations'));

  Future<List<dynamic>> getVideos({int page = 1}) async =>
      _asList(await _get('api/videos', query: {'page': page}));

  Future<bool> toggleVideoLike(int videoId) async {
    final result = await _post('api/videos/$videoId/like', const {});
    return _asMap(result)['liked'] == true;
  }

  Future<List<dynamic>> getComments(int videoId) async =>
      _asList(await _get('api/videos/$videoId/comments'));

  Future<void> addComment(int videoId, String content) async {
    await _post('api/videos/$videoId/comment', {'contenu': content});
  }

  Future<Map<String, dynamic>> uploadVideo({
    required PlatformFile file,
    required String title,
    required bool rightsConfirmed,
    String? description,
  }) async {
    final path = file.path;
    if (path == null) {
      throw StateError(
        'Le chemin du fichier vidéo est indisponible sur cet appareil.',
      );
    }
    final form = FormData.fromMap({
      'titre': title,
      'autorisation_diffusion': rightsConfirmed ? '1' : '0',
      if (description != null && description.trim().isNotEmpty)
        'description': description.trim(),
      'video': await MultipartFile.fromFile(path, filename: file.name),
    });
    final response = await _dio.post<Map<String, dynamic>>(
      'api/videos',
      data: form,
      options: Options(contentType: 'multipart/form-data'),
    );
    return _asMap(_unwrap(response.data));
  }

  Future<List<dynamic>> getQuizzes() async =>
      _asList(await _get('api/quizzes'));

  Future<Map<String, dynamic>> getQuiz(int id) async =>
      _asMap(await _get('api/quizzes/$id'));

  Future<Map<String, dynamic>> submitQuiz(int id, Map<int, int> answers) async {
    final response = await _post('api/quizzes/$id/submit', {
      'reponses': answers.values.toList(growable: false),
    });
    return _asMap(response);
  }

  Future<List<dynamic>> getQuizResults() async =>
      _asList(await _get('api/quizzes/results'));

  Future<Map<String, dynamic>> getPassport() async =>
      _asMap(await _get('api/passport'));

  Future<List<dynamic>> getBadges() async =>
      _asList(await _get('api/passport/badges'));

  Future<List<dynamic>> getVisits() async =>
      _asList(await _get('api/passport/visits'));

  Future<void> recordVisit(int destinationId) async {
    await _post('api/passport/visits', {'destination_id': destinationId});
  }

  Future<List<dynamic>> getNotifications({int page = 1}) async =>
      _asList(await _get('api/notifications', query: {'page': page}));

  Future<void> markNotificationRead(int id) async {
    await _dio.put<void>('api/notifications/$id/read');
  }

  Future<Map<String, dynamic>> getSettings() async =>
      _asMap(await _get('api/settings'));

  Future<Map<String, dynamic>> updateSettings(
    Map<String, dynamic> settings,
  ) async => _asMap(await _put('api/settings', settings));

  Future<List<dynamic>> getRegions() async =>
      _asList(await _get('api/regions'));

  Future<List<dynamic>> getCategories() async =>
      _asList(await _get('api/categories'));

  Future<List<dynamic>> getHeritage() async =>
      _asList(await _get('api/patrimoines'));

  Future<List<dynamic>> getVirtualVisits() async =>
      _asList(await _get('api/visites-virtuelles'));

  Future<void> recordHeritageView(int id) async {
    await _post('api/patrimoines/$id/view', const {});
  }

  Uri mediaUri(String path) {
    final uri = Uri.tryParse(path);
    if (uri != null && (uri.scheme == 'https' || uri.scheme == 'http')) {
      return uri;
    }
    final relative = path.startsWith('/') ? path.substring(1) : path;
    return Uri.parse(_dio.options.baseUrl).resolve(relative);
  }

  Future<dynamic> _get(String path, {Map<String, dynamic>? query}) async {
    final response = await _dio.get<Map<String, dynamic>>(
      path,
      queryParameters: query,
    );
    return _unwrap(response.data);
  }

  Future<dynamic> _post(String path, Object data) async {
    final response = await _dio.post<Map<String, dynamic>>(path, data: data);
    return _unwrap(response.data);
  }

  Future<dynamic> _put(String path, Object data) async {
    final response = await _dio.put<Map<String, dynamic>>(path, data: data);
    return _unwrap(response.data);
  }

  dynamic _unwrap(Map<String, dynamic>? body) {
    if (body == null || !body.containsKey('data')) {
      throw const FormatException('Réponse vide ou invalide du serveur.');
    }
    return body['data'];
  }

  List<dynamic> _asList(dynamic value) {
    if (value is List) return value;
    if (value is Map && value['items'] is List) return value['items'] as List;
    throw const FormatException(
      'Une liste était attendue dans la réponse API.',
    );
  }

  Map<String, dynamic> _asMap(dynamic value) {
    if (value is Map) return Map<String, dynamic>.from(value);
    throw const FormatException('Un objet était attendu dans la réponse API.');
  }

  Future<Map<String, dynamic>> _saveAuthResponse(
    Map<String, dynamic>? body,
  ) async {
    final payload = _unwrap(body);
    if (payload is! Map) {
      throw const FormatException('Réponse d’authentification invalide.');
    }
    final token = body?['token'];
    if (token is! String || token.isEmpty) {
      throw const FormatException('Jeton d’authentification absent.');
    }
    await _tokenStore.saveToken(token);
    return Map<String, dynamic>.from(payload);
  }

  void dispose() {}
}

abstract interface class TokenStore {
  Future<String?> readToken();
  Future<void> saveToken(String token);
  Future<void> clearToken();
}

class SecureTokenStore implements TokenStore {
  SecureTokenStore({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  @override
  Future<String?> readToken() => _storage.read(key: 'access_token');

  @override
  Future<void> saveToken(String token) =>
      _storage.write(key: 'access_token', value: token);

  @override
  Future<void> clearToken() => _storage.delete(key: 'access_token');
}
