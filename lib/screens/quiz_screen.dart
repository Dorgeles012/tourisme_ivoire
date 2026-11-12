import 'package:material_ui/material_ui.dart';

import '../services/api_service.dart';
import '../services/quiz_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';

// ─── Couleur difficulté ───────────────────────────────────────────────────────
Color _diffColor(String? diff) => switch ('$diff'.toLowerCase()) {
  'facile' => const Color(0xFF2E7D32),
  'difficile' => ivorianTerracotta,
  _ => ivorianOcre,
};

IconData _diffIcon(String? diff) => switch ('$diff'.toLowerCase()) {
  'facile' => Icons.sentiment_satisfied_outlined,
  'difficile' => Icons.local_fire_department_outlined,
  _ => Icons.sentiment_neutral_outlined,
};

//
// QUIZ LIST SCREEN
//
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
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final hPad = horizontalPadding(width);
    final isGrid = isGridLayout(width);
    final columns = crossAxisCount(width);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Quiz & culture'),
        centerTitle: false,
        actions: [
          IconButton(
            tooltip: 'Mes résultats',
            onPressed: () =>
                navigateTo(context, QuizResultsScreen(api: widget.api)),
            icon: const Icon(Icons.leaderboard_outlined),
          ),
        ],
      ),
      body: FutureBuilder<List<dynamic>>(
        future: _quizzes,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return LoadingView(isGrid: isGrid);
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: readableError(snapshot.error!),
              onRetry: () =>
                  setState(() => _quizzes = QuizService(widget.api).list()),
            );
          }
          if (snapshot.data!.isEmpty) {
            return const EmptyView(
              message: 'Aucun quiz publié pour le moment. Les séries de démonstration sont en attente de relecture.',
              icon: Icons.quiz_outlined,
            );
          }

          final quizzes = snapshot.data!
              .map((r) => Map<String, dynamic>.from(r as Map))
              .toList();

          if (isGrid) {
            return GridView.builder(
              padding: EdgeInsets.symmetric(horizontal: hPad, vertical: 16),
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: columns,
                crossAxisSpacing: 14,
                mainAxisSpacing: 14,
                childAspectRatio: 1.1,
              ),
              itemCount: quizzes.length,
              itemBuilder: (context, index) => FadeSlideIn(
                delay: Duration(
                  milliseconds: index * kStaggerDelay.inMilliseconds,
                ),
                child: RepaintBoundary(
                  child: _QuizCard(
                    quiz: quizzes[index],
                    onTap: () => _openQuiz(context, quizzes[index]),
                  ),
                ),
              ),
            );
          }

          return ListView.builder(
            padding: EdgeInsets.symmetric(horizontal: hPad, vertical: 16),
            itemCount: quizzes.length,
            itemBuilder: (context, index) => FadeSlideIn(
              delay: Duration(
                milliseconds: index * kStaggerDelay.inMilliseconds,
              ),
              child: RepaintBoundary(
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: _QuizCard(
                    quiz: quizzes[index],
                    onTap: () => _openQuiz(context, quizzes[index]),
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  void _openQuiz(BuildContext context, Map<String, dynamic> quiz) {
    navigateTo(
      context,
      QuizPlayScreen(api: widget.api, quizId: int.parse('${quiz['id']}')),
    );
  }
}

// ─── Carte quiz ───────────────────────────────────────────────────────────────
class _QuizCard extends StatelessWidget {
  const _QuizCard({required this.quiz, required this.onTap});
  final Map<String, dynamic> quiz;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    final diff = '${quiz['difficulte']}';
    final color = _diffColor(diff);

    return AnimatedScale(
      scale: 1.0,
      duration: kFastAnim,
      child: Material(
        color: cs.surface,
        borderRadius: BorderRadius.circular(kRadiusMd),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(kRadiusMd),
          splashColor: color.withValues(alpha: .1),
          child: Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(kRadiusMd),
              boxShadow: kShadowSoft,
              border: Border.all(
                color: cs.outlineVariant.withValues(alpha: .3),
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // ── Badge difficulté ─────────────────────────────────────
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: .12),
                        borderRadius: BorderRadius.circular(kRadiusSm),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(_diffIcon(diff), size: 12, color: color),
                          const SizedBox(width: 4),
                          Text(
                            diff.toUpperCase(),
                            style: TextStyle(
                              color: color,
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 1.1,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const Spacer(),
                    Icon(
                      Icons.quiz_outlined,
                      size: 20,
                      color: color.withValues(alpha: .6),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                // ── Titre ────────────────────────────────────────────────
                Text(
                  '${quiz['titre']}',
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 15,
                  ),
                ),
                const Spacer(),
                // ── Infos bas ────────────────────────────────────────────
                Row(
                  children: [
                    Icon(
                      Icons.topic_outlined,
                      size: 13,
                      color: cs.onSurface.withValues(alpha: .5),
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        '${quiz['theme']}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 11,
                          color: cs.onSurface.withValues(alpha: .55),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Icon(
                      Icons.help_outline,
                      size: 13,
                      color: cs.onSurface.withValues(alpha: .5),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      '${quiz['nombre_questions']} questions',
                      style: TextStyle(
                        fontSize: 11,
                        color: cs.onSurface.withValues(alpha: .55),
                      ),
                    ),
                    const Spacer(),
                    Icon(
                      Icons.chevron_right,
                      size: 16,
                      color: color.withValues(alpha: .7),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

//
// QUIZ PLAY SCREEN
//
class QuizPlayScreen extends StatefulWidget {
  const QuizPlayScreen({required this.api, required this.quizId, super.key});
  final ApiService api;
  final int quizId;

  @override
  State<QuizPlayScreen> createState() => _QuizPlayScreenState();
}

class _QuizPlayScreenState extends State<QuizPlayScreen>
    with SingleTickerProviderStateMixin {
  late Future<Map<String, dynamic>> _quiz;
  final Map<int, int> _answers = {};
  int _index = 0;
  bool _sending = false;
  late final AnimationController _progressAnim;

  @override
  void initState() {
    super.initState();
    _quiz = QuizService(widget.api).details(widget.quizId);
    _progressAnim = AnimationController(
      vsync: this,
      duration: kNormalAnim,
      value: 0,
    );
  }

  @override
  void dispose() {
    _progressAnim.dispose();
    super.dispose();
  }

  void _nextQuestion(int total) {
    setState(() => _index += 1);
    _progressAnim.animateTo((_index + 1) / total);
  }

  Future<void> _submit() async {
    setState(() => _sending = true);
    try {
      final result = await QuizService(widget.api)
          .submit(widget.quizId, _answers);
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        PageRouteBuilder<void>(
          pageBuilder: (_, animation, __) => QuizResultScreen(result: result),
          transitionDuration: kNormalAnim,
          reverseTransitionDuration: kFastAnim,
          transitionsBuilder: (_, animation, __, child) => FadeTransition(
            opacity: CurvedAnimation(parent: animation, curve: Curves.easeOut),
            child: SlideTransition(
              position: Tween<Offset>(
                begin: const Offset(0, 0.04),
                end: Offset.zero,
              ).animate(CurvedAnimation(parent: animation, curve: Curves.easeOutCubic)),
              child: child,
            ),
          ),
        ),
      );
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(readableError(error)),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Questionnaire')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _quiz,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(message: readableError(snapshot.error!));
          }
          final quiz = snapshot.data!;
          final questions = (quiz['questions'] as List).cast<Map>();
          if (questions.isEmpty) {
            return const EmptyView(
              message: 'Ce quiz ne contient aucune question active.',
            );
          }

          final question = Map<String, dynamic>.from(questions[_index]);
          final answers = (question['reponses'] as List).cast<Map>();
          final questionId = int.parse('${question['id']}');
          final selected = _answers[questionId];
          final isLast = _index == questions.length - 1;

          // Animate progress on first build
          WidgetsBinding.instance.addPostFrameCallback((_) {
            _progressAnim.animateTo((_index + 1) / questions.length);
          });

          return SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // ── En-tête quiz ─────────────────────────────────────
                  Text(
                    '${quiz['titre']}',
                    style: Theme.of(context).textTheme.titleMedium
                        ?.copyWith(fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 10),

                  // ── Barre de progression animée ──────────────────────
                  AnimatedBuilder(
                    animation: _progressAnim,
                    builder: (context, _) => ClipRRect(
                      borderRadius: BorderRadius.circular(kRadiusSm),
                      child: LinearProgressIndicator(
                        value: _progressAnim.value,
                        backgroundColor: ivorianOrange.withValues(alpha: .15),
                        color: ivorianOrange,
                        minHeight: 6,
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Question ${_index + 1} sur ${questions.length}',
                    style: TextStyle(
                      fontSize: 12,
                      color: Theme.of(context).colorScheme.onSurface
                          .withValues(alpha: .5),
                    ),
                  ),
                  const SizedBox(height: 20),

                  // ── Énoncé ───────────────────────────────────────────
                  AnimatedSwitcher(
                    duration: kNormalAnim,
                    child: Text(
                      '${question['enonce']}',
                      key: ValueKey(_index),
                      style: Theme.of(context).textTheme.headlineSmall
                          ?.copyWith(fontWeight: FontWeight.w700, height: 1.4),
                    ),
                  ),
                  const SizedBox(height: 20),

                  // ── Réponses ─────────────────────────────────────────
                  Expanded(
                    child: AnimatedSwitcher(
                      duration: kNormalAnim,
                      child: ListView(
                        key: ValueKey('answers_$_index'),
                        children: List.generate(answers.length, (i) {
                          final answer = Map<String, dynamic>.from(answers[i]);
                          final id = int.parse('${answer['id']}');
                          final isSelected = selected == id;
                          return FadeSlideIn(
                            delay: Duration(
                              milliseconds: i * kStaggerDelay.inMilliseconds,
                            ),
                            child: Padding(
                              padding: const EdgeInsets.only(bottom: 10),
                              child: AnimatedContainer(
                                duration: kNormalAnim,
                                decoration: BoxDecoration(
                                  color: isSelected
                                      ? ivorianOrange.withValues(alpha: .1)
                                      : Theme.of(context)
                                            .colorScheme
                                            .surfaceContainerHighest
                                            .withValues(alpha: .5),
                                  borderRadius: BorderRadius.circular(
                                    kRadiusMd,
                                  ),
                                  border: Border.all(
                                    color: isSelected
                                        ? ivorianOrange
                                        : Theme.of(context)
                                              .colorScheme
                                              .outlineVariant
                                              .withValues(alpha: .4),
                                    width: isSelected ? 2 : 1,
                                  ),
                                ),
                                child: RadioListTile<int>(
                                  value: id,
                                  groupValue: selected,
                                  activeColor: ivorianOrange,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(
                                      kRadiusMd,
                                    ),
                                  ),
                                  title: Text(
                                    '${answer['libelle']}',
                                    style: TextStyle(
                                      fontWeight: isSelected
                                          ? FontWeight.w700
                                          : FontWeight.w400,
                                    ),
                                  ),
                                  onChanged: (value) {
                                    if (value != null) {
                                      setState(
                                        () => _answers[questionId] = value,
                                      );
                                    }
                                  },
                                ),
                              ),
                            ),
                          );
                        }),
                      ),
                    ),
                  ),

                  // ── Bouton suivant/terminer ───────────────────────────
                  AnimatedScale(
                    scale: selected != null ? 1.0 : 0.95,
                    duration: kFastAnim,
                    child: IvorianButton(
                      label: _sending
                          ? 'Envoi…'
                          : isLast
                          ? 'Terminer le quiz'
                          : 'Question suivante',
                      icon: isLast
                          ? Icons.check_circle_outline
                          : Icons.arrow_forward,
                      useGradient: true,
                      height: 50,
                      onPressed: selected == null || _sending
                          ? null
                          : isLast
                          ? _submit
                          : () => _nextQuestion(questions.length),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

//
// QUIZ RESULT SCREEN
//
class QuizResultScreen extends StatelessWidget {
  const QuizResultScreen({required this.result, super.key});
  final Map<String, dynamic> result;

  @override
  Widget build(BuildContext context) {
    final corrections = (result['corrections'] as List? ?? const [])
        .cast<Map>();
    final bonnes = result['bonnes_reponses'] as int? ?? 0;
    final total = result['total_questions'] as int? ?? 1;
    final score = result['score'] ?? 0;
    final pct = bonnes / total;
    final color = pct >= 0.8
        ? const Color(0xFF2E7D32)
        : pct >= 0.5
        ? ivorianOcre
        : ivorianTerracotta;

    return Scaffold(
      appBar: AppBar(title: const Text('Résultat du quiz')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          // ── Score hero ─────────────────────────────────────────────────
          FadeSlideIn(
            delay: Duration.zero,
            child: Container(
              padding: const EdgeInsets.all(28),
              decoration: BoxDecoration(
                color: color.withValues(alpha: .08),
                borderRadius: BorderRadius.circular(kRadiusLg),
                border: Border.all(color: color.withValues(alpha: .25)),
              ),
              child: Column(
                children: [
                  Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: .15),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      pct >= 0.5
                          ? Icons.emoji_events_outlined
                          : Icons.refresh_outlined,
                      size: 36,
                      color: color,
                    ),
                  ),
                  const SizedBox(height: 14),
                  Text(
                    '$bonnes / $total',
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.displaySmall
                        ?.copyWith(fontWeight: FontWeight.w900, color: color),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    '$score points ajoutés à votre passeport',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface
                          .withValues(alpha: .7),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // ── Corrections ────────────────────────────────────────────────
          if (corrections.isNotEmpty) ...[
            const Text(
              'Corrections',
              style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 10),
            ...List.generate(corrections.length, (index) {
              final item = Map<String, dynamic>.from(corrections[index]);
              final correct = item['correct'] == true;
              return FadeSlideIn(
                delay: Duration(
                  milliseconds: (index + 1) * kStaggerDelay.inMilliseconds,
                ),
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: correct
                          ? const Color(0xFF2E7D32).withValues(alpha: .06)
                          : ivorianTerracotta.withValues(alpha: .06),
                      borderRadius: BorderRadius.circular(kRadiusMd),
                      border: Border.all(
                        color: correct
                            ? const Color(0xFF2E7D32).withValues(alpha: .25)
                            : ivorianTerracotta.withValues(alpha: .25),
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(
                              correct
                                  ? Icons.check_circle_outline
                                  : Icons.cancel_outlined,
                              size: 16,
                              color: correct
                                  ? const Color(0xFF2E7D32)
                                  : ivorianTerracotta,
                            ),
                            const SizedBox(width: 6),
                            Expanded(
                              child: Text(
                                '${item['question']}',
                                style: const TextStyle(
                                  fontWeight: FontWeight.w700,
                                  fontSize: 13,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        if (!correct) ...[
                          Text(
                            'Votre réponse : ${item['votre_reponse']}',
                            style: TextStyle(
                              color: ivorianTerracotta,
                              fontSize: 13,
                            ),
                          ),
                          const SizedBox(height: 2),
                        ],
                        Text(
                          'Réponse attendue : ${item['bonne_reponse']}',
                          style: TextStyle(
                            color: const Color(0xFF2E7D32),
                            fontWeight: FontWeight.w600,
                            fontSize: 13,
                          ),
                        ),
                        if (item['explication'] != null) ...[
                          const SizedBox(height: 6),
                          Text(
                            '${item['explication']}',
                            style: TextStyle(
                              fontSize: 12,
                              color: Theme.of(context).colorScheme.onSurface
                                  .withValues(alpha: .6),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              );
            }),
          ],
          const SizedBox(height: 20),
          IvorianButton(
            label: 'Terminer',
            icon: Icons.check,
            onPressed: () => Navigator.of(context).pop(),
            height: 50,
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}

//
// QUIZ RESULTS HISTORY
//
class QuizResultsScreen extends StatelessWidget {
  const QuizResultsScreen({required this.api, super.key});
  final ApiService api;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes résultats')),
      body: FutureBuilder<List<dynamic>>(
        future: api.getQuizResults(),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(message: readableError(snapshot.error!));
          }
          if (snapshot.data!.isEmpty) {
            return const EmptyView(
              message: 'Aucun résultat enregistré.',
              icon: Icons.leaderboard_outlined,
            );
          }
          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: snapshot.data!.length,
            itemBuilder: (context, index) {
              final item = Map<String, dynamic>.from(
                snapshot.data![index] as Map,
              );
              final bonnes = item['bonnes_reponses'] as int? ?? 0;
              final total = item['total_questions'] as int? ?? 1;
              final pct = bonnes / total;
              final color = pct >= 0.8
                  ? const Color(0xFF2E7D32)
                  : pct >= 0.5
                  ? ivorianOcre
                  : ivorianTerracotta;

              return FadeSlideIn(
                delay: Duration(
                  milliseconds: index * kStaggerDelay.inMilliseconds,
                ),
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Container(
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surface,
                      borderRadius: BorderRadius.circular(kRadiusMd),
                      boxShadow: kShadowSoft,
                    ),
                    child: ListTile(
                      leading: Container(
                        width: 42,
                        height: 42,
                        decoration: BoxDecoration(
                          color: color.withValues(alpha: .12),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(
                          Icons.emoji_events_outlined,
                          size: 20,
                          color: color,
                        ),
                      ),
                      title: Text(
                        '${item['quiz']}',
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      ),
                      subtitle: Text('$bonnes/$total bonnes réponses'),
                      trailing: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: color.withValues(alpha: .1),
                          borderRadius: BorderRadius.circular(kRadiusSm),
                        ),
                        child: Text(
                          '${item['score']} pts',
                          style: TextStyle(
                            color: color,
                            fontWeight: FontWeight.w800,
                            fontSize: 13,
                          ),
                        ),
                      ),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(kRadiusMd),
                      ),
                    ),
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
