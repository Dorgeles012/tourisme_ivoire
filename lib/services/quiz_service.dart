import 'api_service.dart';

class QuizService {
  QuizService(this._api);
  final ApiService _api;

  Future<List<dynamic>> list() => _api.getQuizzes();
  Future<Map<String, dynamic>> details(int id) => _api.getQuiz(id);
  Future<Map<String, dynamic>> submit(int id, Map<int, int> answers) => _api.submitQuiz(id, answers);
  Future<List<dynamic>> results() => _api.getQuizResults();
}
