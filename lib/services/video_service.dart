import 'package:file_picker/file_picker.dart';

import 'api_service.dart';

class VideoService {
  VideoService(this._api);
  final ApiService _api;

  Future<List<dynamic>> feed({int page = 1}) => _api.getVideos(page: page);
  Future<bool> toggleLike(int videoId) => _api.toggleVideoLike(videoId);
  Future<List<dynamic>> comments(int videoId) => _api.getComments(videoId);
  Future<void> comment(int videoId, String content) => _api.addComment(videoId, content);
  Future<Map<String, dynamic>> publish(PlatformFile file, String title, {String? description, required bool rightsConfirmed}) =>
      _api.uploadVideo(file: file, title: title, description: description, rightsConfirmed: rightsConfirmed);
}
