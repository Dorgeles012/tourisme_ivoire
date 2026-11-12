// lib/screens/home_screen.dart
// Écran Accueil — redesigné UX/UI
import 'package:material_ui/material_ui.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';
import 'feature_screens.dart';
import 'map_screen.dart';
import 'passport_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({
    required this.api,
    required this.onExplore,
    required this.onThemeChanged,
    required this.onAccessibilityChanged,
    super.key,
  });

  final ApiService api;
  final VoidCallback onExplore;
  final ValueChanged<String> onThemeChanged;
  final ValueChanged<Map<String, dynamic>> onAccessibilityChanged;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<List<Destination>> _destinations;

  @override
  void initState() {
    super.initState();
    _destinations = widget.api.fetchDestinations(perPage: 6);
  }

  void _reload() => setState(() => _destinations = widget.api.fetchDestinations(perPage: 6));

  void _open(Widget page) => navigateTo(context, page);

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final hPad = horizontalPadding(width);
    final grid = isGridLayout(width);

    return RefreshIndicator(
      onRefresh: () async => _reload(),
      color: ivorianOrange,
      child: CustomScrollView(
        slivers: [
          // ── AppBar flottant ──────────────────────────────
          SliverAppBar(
            floating: true,
            snap: true,
            backgroundColor: Theme.of(context).scaffoldBackgroundColor,
            elevation: 0,
            scrolledUnderElevation: 0,
            titleSpacing: hPad,
            title: const Text(
              'TOURISME IVOIRE',
              style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18, letterSpacing: 0.5),
            ),
            actions: [
              IconButton(
                tooltip: 'Notifications',
                icon: const Icon(Icons.notifications_outlined),
                onPressed: () => _open(NotificationsScreen(api: widget.api)),
              ),
              const SizedBox(width: 8),
            ],
          ),

          // ── Hero Banner ──────────────────────────────────
          SliverPadding(
            padding: EdgeInsets.fromLTRB(hPad, 8, hPad, 0),
            sliver: SliverToBoxAdapter(
              child: _HeroBanner(onExplore: widget.onExplore),
            ),
          ),

          // ── Quick Features (horizontal scroll) ───────────
          SliverPadding(
            padding: EdgeInsets.fromLTRB(0, 24, 0, 0),
            sliver: SliverToBoxAdapter(
              child: _QuickFeatures(
                onTransport: () => _open(TransportScreen(api: widget.api)),
                onQuiz:      () => _open(QuizListScreen(api: widget.api)),
                onHeritage:  () => _open(HeritageScreen(api: widget.api)),
                onMap:       () => _open(MapScreen(api: widget.api)),
                onPassport:  () => _open(PassportScreen(api: widget.api)),
              ),
            ),
          ),

          // ── Section Destinations ─────────────────────────
          SliverPadding(
            padding: EdgeInsets.fromLTRB(hPad, 28, hPad, 8),
            sliver: SliverToBoxAdapter(
              child: SectionHeader(
                title: 'Destinations à découvrir',
                actionLabel: 'Tout voir',
                onAction: widget.onExplore,
              ),
            ),
          ),

          SliverPadding(
            padding: EdgeInsets.fromLTRB(hPad, 4, hPad, 0),
            sliver: FutureBuilder<List<Destination>>(
              future: _destinations,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return SliverToBoxAdapter(
                    child: LoadingView(isGrid: grid, itemCount: 4),
                  );
                }
                if (snapshot.hasError) {
                  return SliverToBoxAdapter(
                    child: SizedBox(
                      height: 200,
                      child: ErrorView(
                        message: readableError(snapshot.error!),
                        onRetry: _reload,
                      ),
                    ),
                  );
                }
                final items = snapshot.data ?? [];
                if (items.isEmpty) {
                  return const SliverToBoxAdapter(
                    child: SizedBox(
                      height: 160,
                      child: EmptyView(
                        message: 'Aucune destination publiée pour le moment.',
                        icon: Icons.landscape_outlined,
                      ),
                    ),
                  );
                }

                if (grid) {
                  return SliverGrid(
                    gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: crossAxisCount(width),
                      crossAxisSpacing: 12,
                      mainAxisSpacing: 12,
                      childAspectRatio: 0.82,
                    ),
                    delegate: SliverChildBuilderDelegate(
                      (_, i) => DestinationTile(
                        destination: items[i],
                        api: widget.api,
                        compact: true,
                        index: i,
                      ),
                      childCount: items.length,
                    ),
                  );
                }

                return SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (_, i) => DestinationTile(
                      destination: items[i],
                      api: widget.api,
                      compact: false,
                      index: i,
                    ),
                    childCount: items.length,
                  ),
                );
              },
            ),
          ),

          // ── Section Fonctionnalités ──────────────────────
          SliverPadding(
            padding: EdgeInsets.fromLTRB(hPad, 32, hPad, 8),
            sliver: SliverToBoxAdapter(
              child: SectionHeader(title: 'Que voulez-vous faire ?'),
            ),
          ),

          SliverPadding(
            padding: EdgeInsets.fromLTRB(hPad, 8, hPad, 32),
            sliver: SliverToBoxAdapter(
              child: _FeatureGrid(
                onTransport: () => _open(TransportScreen(api: widget.api)),
                onQuiz:      () => _open(QuizListScreen(api: widget.api)),
                onHeritage:  () => _open(HeritageScreen(api: widget.api)),
                onMap:       () => _open(MapScreen(api: widget.api)),
                onSettings:  () => _open(SettingsScreen(
                      api: widget.api,
                      onThemeChanged: widget.onThemeChanged,
                      onAccessibilityChanged: widget.onAccessibilityChanged,
                    )),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════
// 🌟 Hero Banner
// ═══════════════════════════════════════════════════════════

class _HeroBanner extends StatefulWidget {
  const _HeroBanner({required this.onExplore});
  final VoidCallback onExplore;

  @override
  State<_HeroBanner> createState() => _HeroBannerState();
}

class _HeroBannerState extends State<_HeroBanner>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 10),
    )..repeat();
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 200,
      decoration: BoxDecoration(
        gradient: kHeroGradient,
        borderRadius: BorderRadius.circular(kRadiusXl),
        boxShadow: kShadowColored(ivorianOrange),
      ),
      clipBehavior: Clip.antiAlias,
      child: Stack(
        children: [
          // Cercles décoratifs animés
          AnimatedBuilder(
            animation: _ctrl,
            builder: (_, __) => Stack(
              children: [
                Positioned(
                  top: -30 + _ctrl.value * 8,
                  right: -30 + _ctrl.value * 6,
                  child: Container(
                    width: 160,
                    height: 160,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white.withValues(alpha: 0.08),
                    ),
                  ),
                ),
                Positioned(
                  bottom: -20 - _ctrl.value * 6,
                  left: 20 - _ctrl.value * 4,
                  child: Container(
                    width: 100,
                    height: 100,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white.withValues(alpha: 0.06),
                    ),
                  ),
                ),
                Positioned(
                  top: 60 + _ctrl.value * 5,
                  left: 60 + _ctrl.value * 3,
                  child: Container(
                    width: 50,
                    height: 50,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white.withValues(alpha: 0.05),
                    ),
                  ),
                ),
              ],
            ),
          ),
          // Contenu
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                const Text(
                  'Découvrez la\nCôte d\'Ivoire',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 26,
                    fontWeight: FontWeight.w900,
                    height: 1.15,
                    letterSpacing: -0.5,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'Destinations, cultures et aventures',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.85),
                    fontSize: 14,
                    fontWeight: FontWeight.w400,
                  ),
                ),
                const SizedBox(height: 16),
                OutlinedButton(
                  onPressed: widget.onExplore,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: const BorderSide(color: Colors.white, width: 1.5),
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                    minimumSize: const Size(44, 40),
                    textStyle: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(kRadiusXl),
                    ),
                  ),
                  child: const Text('Explorer maintenant'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════
// ⚡ Quick Features (horizontal scroll)
// ═══════════════════════════════════════════════════════════

class _QuickFeatures extends StatelessWidget {
  const _QuickFeatures({
    required this.onTransport,
    required this.onQuiz,
    required this.onHeritage,
    required this.onMap,
    required this.onPassport,
  });

  final VoidCallback onTransport;
  final VoidCallback onQuiz;
  final VoidCallback onHeritage;
  final VoidCallback onMap;
  final VoidCallback onPassport;

  @override
  Widget build(BuildContext context) {
    final items = [
      _QuickItem(Icons.directions_bus_rounded,  'Transport',  const Color(0xFF2E86AB), onTransport),
      _QuickItem(Icons.quiz_rounded,            'Quiz',       const Color(0xFF8E44AD), onQuiz),
      _QuickItem(Icons.account_balance_rounded, 'Patrimoine', const Color(0xFF16A085), onHeritage),
      _QuickItem(Icons.map_rounded,             'Carte',      const Color(0xFF27AE60), onMap),
      _QuickItem(Icons.badge_rounded,           'Passeport',  ivorianOrange,            onPassport),
    ];

    return SizedBox(
      height: 100,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: items.length,
        itemBuilder: (_, i) => FadeSlideIn(
          delay: kStaggerDelay * i,
          child: _QuickFeatureChip(item: items[i]),
        ),
      ),
    );
  }
}

class _QuickItem {
  const _QuickItem(this.icon, this.label, this.color, this.onTap);
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;
}

class _QuickFeatureChip extends StatefulWidget {
  const _QuickFeatureChip({required this.item});
  final _QuickItem item;

  @override
  State<_QuickFeatureChip> createState() => _QuickFeatureChipState();
}

class _QuickFeatureChipState extends State<_QuickFeatureChip> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return GestureDetector(
      onTapDown: (_) => setState(() => _pressed = true),
      onTapUp: (_) => setState(() => _pressed = false),
      onTapCancel: () => setState(() => _pressed = false),
      onTap: widget.item.onTap,
      child: AnimatedScale(
        scale: _pressed ? 0.93 : 1.0,
        duration: kFastAnim,
        child: Container(
          width: 78,
          margin: const EdgeInsets.only(right: 10),
          decoration: BoxDecoration(
            color: scheme.surfaceContainerHighest.withValues(alpha: 0.6),
            borderRadius: BorderRadius.circular(kRadiusLg),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: widget.item.color.withValues(alpha: 0.14),
                  shape: BoxShape.circle,
                ),
                child: Icon(widget.item.icon, color: widget.item.color, size: 22),
              ),
              const SizedBox(height: 6),
              Text(
                widget.item.label,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: scheme.onSurface,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════
// 🗂️ Feature Grid (bas de page)
// ═══════════════════════════════════════════════════════════

class _FeatureGrid extends StatelessWidget {
  const _FeatureGrid({
    required this.onTransport,
    required this.onQuiz,
    required this.onHeritage,
    required this.onMap,
    required this.onSettings,
  });

  final VoidCallback onTransport;
  final VoidCallback onQuiz;
  final VoidCallback onHeritage;
  final VoidCallback onMap;
  final VoidCallback onSettings;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final features = [
      (Icons.directions_bus_rounded,  'Transport',                 'Rechercher et réserver',              const Color(0xFF2E86AB), onTransport),
      (Icons.quiz_rounded,            'Quiz & Culture',            'Testez vos connaissances',            const Color(0xFF8E44AD), onQuiz),
      (Icons.account_balance_rounded, 'Patrimoine',                'Visites et contenus culturels',       const Color(0xFF16A085), onHeritage),
      (Icons.map_rounded,             'Carte',                     'Explorer les lieux sur la carte',     const Color(0xFF27AE60), onMap),
      (Icons.settings_rounded,        'Paramètres',                'Thème et accessibilité',              const Color(0xFF7F8C8D), onSettings),
    ];

    if (width < 700) {
      return Column(
        children: [
          for (var i = 0; i < features.length; i++) ...[
            FadeSlideIn(
              delay: kStaggerDelay * i,
              child: FeatureCard(
                icon: features[i].$1,
                title: features[i].$2,
                subtitle: features[i].$3,
                iconColor: features[i].$4,
                onTap: features[i].$5,
              ),
            ),
            const SizedBox(height: 10),
          ],
        ],
      );
    }

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: features.length,
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: crossAxisCount(width),
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
        childAspectRatio: 2.5,
      ),
      itemBuilder: (_, i) => FadeSlideIn(
        delay: kStaggerDelay * i,
        child: FeatureCard(
          icon: features[i].$1,
          title: features[i].$2,
          subtitle: features[i].$3,
          iconColor: features[i].$4,
          onTap: features[i].$5,
        ),
      ),
    );
  }
}
