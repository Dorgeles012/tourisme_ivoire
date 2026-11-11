import 'api_service.dart';

class NotificationService {
  NotificationService(this._api);
  final ApiService _api;

  Future<List<dynamic>> list({int page = 1}) => _api.getNotifications(page: page);
  Future<void> markRead(int id) => _api.markNotificationRead(id);
}
