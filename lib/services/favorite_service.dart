import 'api_service.dart';

class FavoriteService {
  FavoriteService(this._api);
  final ApiService _api;

  Future<List<dynamic>> list() => _api.getFavorites();
  Future<void> add(int destinationId) => _api.addFavorite(destinationId);
  Future<void> remove(int favoriteId) => _api.removeFavorite(favoriteId);
}
