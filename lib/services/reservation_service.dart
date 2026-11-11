import 'api_service.dart';

class ReservationService {
  ReservationService(this._api);
  final ApiService _api;

  Future<List<dynamic>> transports() => _api.getTransports();
  Future<List<dynamic>> search(String departure, String arrival) => _api.searchItineraries(departure, arrival);
  Future<Map<String, dynamic>> reserve(int itineraryId, {int quantity = 1}) =>
      _api.createReservation(itineraryId, quantity: quantity);
  Future<List<dynamic>> history() => _api.getReservations();
}
