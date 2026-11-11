import 'package:flutter/material.dart';

import '../theme.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({required this.onComplete, super.key});
  final VoidCallback onComplete;

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final _controller = PageController();
  int _page = 0;

  static const _slides = [
    (Icons.landscape_rounded, 'Explorez la Côte d’Ivoire', 'Découvrez les destinations, les régions et les patrimoines du pays.'),
    (Icons.route_rounded, 'Préparez vos découvertes', 'Recherchez des trajets et envoyez une demande de réservation sans frais inventés.'),
    (Icons.travel_explore_rounded, 'Un passeport à votre image', 'Gardez vos favoris, visites, quiz et badges dans un seul espace.'),
  ];

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            children: [
              Align(
                alignment: Alignment.centerRight,
                child: TextButton(onPressed: widget.onComplete, child: const Text('Passer')),
              ),
              Expanded(
                child: PageView.builder(
                  controller: _controller,
                  itemCount: _slides.length,
                  onPageChanged: (value) => setState(() => _page = value),
                  itemBuilder: (context, index) {
                    final item = _slides[index];
                    return Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          width: 150,
                          height: 150,
                          decoration: BoxDecoration(color: ivorianOrange.withValues(alpha: .12), borderRadius: BorderRadius.circular(44)),
                          child: Icon(item.$1, size: 72, color: ivorianOrange),
                        ),
                        const SizedBox(height: 32),
                        Text(item.$2, textAlign: TextAlign.center, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800)),
                        const SizedBox(height: 12),
                        Text(item.$3, textAlign: TextAlign.center, style: Theme.of(context).textTheme.bodyLarge?.copyWith(height: 1.5)),
                      ],
                    );
                  },
                ),
              ),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(_slides.length, (index) => Container(
                  width: index == _page ? 22 : 8,
                  height: 8,
                  margin: const EdgeInsets.symmetric(horizontal: 4),
                  decoration: BoxDecoration(color: index == _page ? ivorianOrange : Colors.black26, borderRadius: BorderRadius.circular(8)),
                )),
              ),
              const SizedBox(height: 22),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: _page == _slides.length - 1
                      ? widget.onComplete
                      : () => _controller.nextPage(duration: const Duration(milliseconds: 260), curve: Curves.easeOut),
                  child: Text(_page == _slides.length - 1 ? 'Commencer' : 'Continuer'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
