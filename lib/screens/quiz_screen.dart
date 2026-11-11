import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../services/quiz_service.dart';
import '../widgets/common.dart';

class QuizListScreen extends StatefulWidget {
  const QuizListScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<QuizListScreen> createState() => _QuizListScreenState();
}

class _QuizListScreenState extends State<QuizListScreen> {
  late Future<List<dynamic>> _quizzes;

  @override
  void initState() {
    super.initState();
    _quizzes = QuizService(widget.api).list();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(
          title: const Text('Quiz & culture'),
          actions: [IconButton(tooltip: 'Mes résultats', onPressed: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => QuizResultsScreen(api: widget.api))), icon: const Icon(Icons.leaderboard_outlined))],
        ),
        body: FutureBuilder<List<dynamic>>(
          future: _quizzes,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
            if (snapshot.hasError) return ErrorView(message: readableError(snapshot.error!), onRetry: () => setState(() => _quizzes = QuizService(widget.api).list()));
            if (snapshot.data!.isEmpty) return const EmptyView(message: 'Aucun quiz publié pour le moment. Les séries de démonstration sont en attente de relecture.', icon: Icons.quiz_outlined);
            return ListView(
              padding: const EdgeInsets.all(16),
              children: snapshot.data!.map((row) {
                final quiz = Map<String, dynamic>.from(row as Map);
                return Card(
                  child: ListTile(
                    leading: const CircleAvatar(child: Icon(Icons.quiz_outlined)),
                    title: Text('${quiz['titre']}', style: const TextStyle(fontWeight: FontWeight.w700)),
                    subtitle: Text('${quiz['theme']} • ${quiz['difficulte']} • ${quiz['nombre_questions']} questions'),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => QuizPlayScreen(api: widget.api, quizId: int.parse('${quiz['id']}')))),
                  ),
                );
              }).toList(),
            );
          },
        ),
      );
}

class QuizPlayScreen extends StatefulWidget {
  const QuizPlayScreen({required this.api, required this.quizId, super.key});
  final ApiService api;
  final int quizId;

  @override
  State<QuizPlayScreen> createState() => _QuizPlayScreenState();
}

class _QuizPlayScreenState extends State<QuizPlayScreen> {
  late Future<Map<String, dynamic>> _quiz;
  final Map<int, int> _answers = {};
  int _index = 0;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _quiz = QuizService(widget.api).details(widget.quizId);
  }

  Future<void> _submit() async {
    setState(() => _sending = true);
    try {
      final result = await QuizService(widget.api).submit(widget.quizId, _answers);
      if (!mounted) return;
      Navigator.of(context).pushReplacement(MaterialPageRoute<void>(builder: (_) => QuizResultScreen(result: result)));
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Questionnaire')),
        body: FutureBuilder<Map<String, dynamic>>(
          future: _quiz,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
            if (snapshot.hasError) return ErrorView(message: readableError(snapshot.error!));
            final quiz = snapshot.data!;
            final questions = (quiz['questions'] as List).cast<Map>();
            if (questions.isEmpty) return const EmptyView(message: 'Ce quiz ne contient aucune question active.');
            final question = Map<String, dynamic>.from(questions[_index]);
            final answers = (question['reponses'] as List).cast<Map>();
            final questionId = int.parse('${question['id']}');
            final selected = _answers[questionId];
            return Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text('${quiz['titre']}', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 8),
                  LinearProgressIndicator(value: (_index + 1) / questions.length),
                  const SizedBox(height: 8),
                  Text('Question ${_index + 1} sur ${questions.length}'),
                  const SizedBox(height: 22),
                  Text('${question['enonce']}', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700)),
                  const SizedBox(height: 16),
                  RadioGroup<int>(
                    groupValue: selected,
                    onChanged: (value) => setState(() { if (value != null) _answers[questionId] = value; }),
                    child: Column(
                      children: answers.map((answer) {
                        final id = int.parse('${answer['id']}');
                        return Card(child: RadioListTile<int>(value: id, title: Text('${answer['libelle']}')));
                      }).toList(),
                    ),
                  ),
                  const Spacer(),
                  FilledButton(
                    onPressed: selected == null || _sending
                        ? null
                        : _index == questions.length - 1
                            ? _submit
                            : () => setState(() => _index += 1),
                    child: _sending
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                        : Text(_index == questions.length - 1 ? 'Terminer le quiz' : 'Question suivante'),
                  ),
                ],
              ),
            );
          },
        ),
      );
}

class QuizResultScreen extends StatelessWidget {
  const QuizResultScreen({required this.result, super.key});
  final Map<String, dynamic> result;

  @override
  Widget build(BuildContext context) {
    final corrections = (result['corrections'] as List? ?? const []).cast<Map>();
    return Scaffold(
      appBar: AppBar(title: const Text('Résultat du quiz')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text('${result['bonnes_reponses']} / ${result['total_questions']}', textAlign: TextAlign.center, style: Theme.of(context).textTheme.displaySmall?.copyWith(fontWeight: FontWeight.w900)),
          Text('${result['score']} points ajoutés à votre passeport', textAlign: TextAlign.center),
          const SizedBox(height: 22),
          ...corrections.map((row) {
            final item = Map<String, dynamic>.from(row);
            return Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('${item['question']}', style: const TextStyle(fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  Text(item['correct'] == true ? 'Bonne réponse : ${item['bonne_reponse']}' : 'Votre réponse : ${item['votre_reponse']}\nRéponse attendue : ${item['bonne_reponse']}'),
                  if (item['explication'] != null) ...[const SizedBox(height: 5), Text('${item['explication']}')],
                ]),
              ),
            );
          }),
          const SizedBox(height: 14),
          FilledButton(onPressed: () => Navigator.of(context).pop(), child: const Text('Terminer')),
        ],
      ),
    );
  }
}

class QuizResultsScreen extends StatelessWidget {
  const QuizResultsScreen({required this.api, super.key});
  final ApiService api;

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Mes résultats')),
        body: FutureBuilder<List<dynamic>>(
          future: api.getQuizResults(),
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
            if (snapshot.hasError) return ErrorView(message: readableError(snapshot.error!));
            if (snapshot.data!.isEmpty) return const EmptyView(message: 'Aucun résultat enregistré.', icon: Icons.leaderboard_outlined);
            return ListView(
              padding: const EdgeInsets.all(16),
              children: snapshot.data!.map((row) {
                final item = Map<String, dynamic>.from(row as Map);
                return Card(
                  child: ListTile(
                    leading: const Icon(Icons.emoji_events_outlined),
                    title: Text('${item['quiz']}'),
                    subtitle: Text('${item['bonnes_reponses']}/${item['total_questions']} bonnes réponses'),
                    trailing: Text('${item['score']} pts'),
                  ),
                );
              }).toList(),
            );
          },
        ),
      );
}
