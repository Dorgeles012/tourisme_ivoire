import 'package:material_ui/material_ui.dart';
import '../theme/design_system.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({required this.onComplete, super.key});
  final VoidCallback onComplete;

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen>
    with SingleTickerProviderStateMixin {
  final _ctrl = PageController();
  int _page = 0;

  static const _slides = [
    _Slide(
      icon: Icons.landscape_rounded,
      title: 'Explorez la Côte d\'Ivoire',
      subtitle: 'Découvrez les destinations, les régions et les patrimoines du pays.',
      bgColor: ivorianOrange,
      iconColor: Colors.white,
    ),
    _Slide(
      icon: Icons.route_rounded,
      title: 'Préparez vos découvertes',
      subtitle: 'Recherchez des trajets et envoyez une demande de réservation sans frais inventés.',
      bgColor: ivorianForest,
      iconColor: Colors.white,
    ),
    _Slide(
      icon: Icons.travel_explore_rounded,
      title: 'Un passeport à votre image',
      subtitle: 'Gardez vos favoris, visites, quiz et badges dans un seul espace personnel.',
      bgColor: ivorianInk,
      iconColor: ivorianGold,
    ),
  ];

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  void _next() {
    if (_page < _slides.length - 1) {
      _ctrl.nextPage(duration: kNormalAnim, curve: Curves.easeInOut);
    } else {
      widget.onComplete();
    }
  }

  @override
  Widget build(BuildContext context) {
    final slide = _slides[_page];

    return Scaffold(
      backgroundColor: slide.bgColor,
      body: Stack(
        children: [
          // Cercles décoratifs
          _Circles(color: slide.bgColor),

          SafeArea(
            child: Column(
              children: [
                // Bouton Passer
                Align(
                  alignment: Alignment.centerRight,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(0, 8, 16, 0),
                    child: TextButton(
                      onPressed: widget.onComplete,
                      style: TextButton.styleFrom(
                        foregroundColor: Colors.white.withValues(alpha: 0.75),
                        textStyle: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                      ),
                      child: const Text('Passer'),
                    ),
                  ),
                ),

                // Pages
                Expanded(
                  child: PageView.builder(
                    controller: _ctrl,
                    itemCount: _slides.length,
                    onPageChanged: (i) => setState(() => _page = i),
                    itemBuilder: (context, i) => _SlideContent(slide: _slides[i]),
                  ),
                ),

                // Dots + Bouton
                Padding(
                  padding: const EdgeInsets.fromLTRB(32, 8, 32, 32),
                  child: Column(
                    children: [
                      // Indicateurs de page
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: List.generate(
                          _slides.length,
                          (i) => AnimatedContainer(
                            duration: kNormalAnim,
                            curve: Curves.easeInOut,
                            margin: const EdgeInsets.symmetric(horizontal: 4),
                            width: i == _page ? 24 : 8,
                            height: 8,
                            decoration: BoxDecoration(
                              color: i == _page
                                  ? Colors.white
                                  : Colors.white.withValues(alpha: 0.35),
                              borderRadius: BorderRadius.circular(4),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 28),
                      // Bouton Suivant / Commencer
                      SizedBox(
                        width: double.infinity,
                        height: 54,
                        child: ElevatedButton(
                          onPressed: _next,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: slide.bgColor,
                            elevation: 0,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(kRadiusXl),
                            ),
                            textStyle: const TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: 16,
                            ),
                          ),
                          child: Text(
                            _page == _slides.length - 1 ? 'Commencer l\'aventure' : 'Suivant',
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// 🎨 Cercles décoratifs de fond

class _Circles extends StatelessWidget {
  const _Circles({required this.color});
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Positioned(
          top: -80,
          right: -80,
          child: Container(
            width: 250, height: 250,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.07),
            ),
          ),
        ),
        Positioned(
          bottom: 60,
          left: -60,
          child: Container(
            width: 180, height: 180,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.06),
            ),
          ),
        ),
        Positioned(
          top: 160,
          right: -40,
          child: Container(
            width: 100, height: 100,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.05),
            ),
          ),
        ),
      ],
    );
  }
}

// 📄 Contenu d'un slide

class _SlideContent extends StatelessWidget {
  const _SlideContent({required this.slide});
  final _Slide slide;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 32),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          // Icône dans cercle
          Container(
            width: 140,
            height: 140,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.14),
            ),
            child: Icon(slide.icon, size: 70, color: slide.iconColor),
          ),
          const SizedBox(height: 40),
          Text(
            slide.title,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w900,
              height: 1.2,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            slide.subtitle,
            textAlign: TextAlign.center,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.80),
              fontSize: 15,
              height: 1.55,
              fontWeight: FontWeight.w400,
            ),
          ),
        ],
      ),
    );
  }
}

class _Slide {
  const _Slide({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.bgColor,
    required this.iconColor,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final Color bgColor;
  final Color iconColor;
}
