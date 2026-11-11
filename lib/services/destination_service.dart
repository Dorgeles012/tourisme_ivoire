import '../models/destination.dart';
import 'api_service.dart';

class DestinationService {
  DestinationService(this._api);
  final ApiService _api;

  Future<List<Destination>> search({
    String? query,
    String? category,
    int? regionId,
    int? districtId,
    int page = 1,
    int perPage = 12,
  }) =>
      _api.fetchDestinations(
        search: query,
        category: category,
        regionId: regionId,
        districtId: districtId,
        page: page,
        perPage: perPage,
      );

  Future<Destination> details(int id) => _api.fetchDestination(id);
}
