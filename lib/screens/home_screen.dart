// lib/screens/home_screen.dart
import 'dart:async';

import 'package:material_ui/material_ui.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';
import 'feature_screens.dart';
import 'map_screen.dart';
import 'passport_screen.dart';

class _FeatureData {
  const _FeatureData({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.shortLabel,
    required this.color,
    required this.builder,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final String shortLabel;
  final Color color;
  final Widget Function(ApiService api, HomeScreen widget) builder;
}

final List<_FeatureData> _kFeatures = [
  _FeatureData(
    icon: Icons.directions_bus_rounded,
    title: 'Transport',
    subtitle: 'Rechercher et réserver',
    shortLabel: 'Transport',
    color: const Color(0xFF2E86AB),
    builder: (api, _) => TransportScreen(api: api),
  ),
  _FeatureData(
    icon: Icons.quiz_rounded,
    title: 'Quiz & Culture',
    subtitle: 'Testez vos connaissances',
    shortLabel: 'Quiz',
    color: const Color(0xFF8E44AD),
    builder: (api, _) => QuizListScreen(api: api),
  ),
  _FeatureData(
    icon: Icons.account_balance_rounded,
    title: 'Patrimoine',
    subtitle: 'Visites et contenus culturels',
    shortLabel: 'Patrimoine',
    color: const Color(0xFF16A085),
    builder: (api, _) => HeritageScreen(api: api),
  ),
  _FeatureData(
    icon: Icons.map_rounded,
    title: 'Carte',
    subtitle: 'Explorer les lieux sur la carte',
    shortLabel: 'Carte',
    color: const Color(0xFF27AE60),
    builder: (api, _) => MapScreen(api: api),
  ),
  _FeatureData(
    icon: Icons.badge_rounded,
    title: 'Passeport',
    subtitle: 'Votre carnet de voyage',
    shortLabel: 'Passeport',
    color: ivorianOrange,
    builder: (api, _) => PassportScreen(api: api),
  ),
  _FeatureData(
    icon: Icons.settings_rounded,
    title: 'Paramètres',
    subtitle: 'Thème et accessibilité',
    shortLabel: 'Réglages',
    color: const Color(0xFF7F8C8D),
    builder: (api, w) => SettingsScreen(
      api: api,
      onThemeChanged: w.onThemeChanged,
      onAccessibilityChanged: w.onAccessibilityChanged,
    ),
  ),
];

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
  static const double _contentMax = 1280;
  static const int _perPageBase = 6;
  static const int _perPageMax = 12;

  late Future<List<Destination>> _destinations;
  int _perPage = _perPageBase;
  int _lastCols = 0;

  @override
  void initState() {
    super.initState();
    _destinations = widget.api.fetchDestinations(perPage: _perPage);
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final width = MediaQuery.sizeOf(context).width;
    final cols = crossAxisCount(width);
    final desired = (cols * 2).clamp(_perPageBase, _perPageMax);
    if (desired != _perPage || cols != _lastCols) {
      _lastCols = cols;
      _perPage = desired;
      _destinations = widget.api.fetchDestinations(perPage: _perPage);
    }
  }

  void _reload() => setState(
        () => _destinations = widget.api.fetchDestinations(perPage: _perPage),
      );

  void _open(Widget page) => navigateTo(context, page);

  void _openFeature(_FeatureData feature) =>
      _open(feature.builder(widget.api, widget));

  double _tileAspectRatio(double screenWidth, int cols) {
    final gap = _gridGap(cols);
    final cellWidth = (screenWidth - gap * (cols - 1)) / cols;
    if (cellWidth < 140) return 0.78;
    if (cellWidth < 180) return 0.82;
    if (cellWidth < 240) return 0.88;
    if (cellWidth < 320) return 0.95;
    return 1.05;
  }

  double _gridGap(int cols) {
    if (cols >= 4) return 20;
    if (cols == 3) return 16;
    return 12;
  }

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final hPad = horizontalPadding(width);
    final sidePad = width > _contentMax ? (width - _contentMax) / 2 : hPad;
    final grid = isGridLayout(width);
    final cols = crossAxisCount(width);
    final gap = _gridGap(cols);

    return RefreshIndicator(
      onRefresh: () async => _reload(),
      color: ivorianOrange,
      child: CustomScrollView(
        slivers: [
          SliverAppBar(
            floating: true,
            snap: true,
            backgroundColor: Theme.of(context).scaffoldBackgroundColor,
            elevation: 0,
            scrolledUnderElevation: 0,
            titleSpacing: sidePad,
            title: const Text(
              'TOURISME IVOIRE',
              style: TextStyle(
                fontWeight: FontWeight.w900,
                fontSize: 18,
                letterSpacing: 0.5,
              ),
            ),
            actions: [
              IconButton(
                tooltip: 'Notifications',
                icon: const Icon(Icons.notifications_outlined),
                onPressed: () =>
                    _open(NotificationsScreen(api: widget.api)),
              ),
              const SizedBox(width: 8),
            ],
          ),
          SliverPadding(
            padding: EdgeInsets.fromLTRB(sidePad, 8, sidePad, 0),
            sliver: SliverToBoxAdapter(
              child: _HeroBanner(onExplore: widget.onExplore),
            ),
          ),
          SliverPadding(
            padding: EdgeInsets.fromLTRB(sidePad, 24, sidePad, 8),
            sliver: SliverToBoxAdapter(
              child: SectionHeader(title: 'Que voulez-vous faire ?'),
            ),
          ),
          SliverPadding(
            padding: EdgeInsets.fromLTRB(sidePad, 4, sidePad, 8),
            sliver: SliverToBoxAdapter(
              child: _FeatureShowcase(
                features: _kFeatures,
                onOpen: _openFeature,
                isGrid: grid,
                crossAxisCount: cols,
              ),
            ),
          ),
          SliverPadding(
            padding: EdgeInsets.fromLTRB(sidePad, 24, sidePad, 8),
            sliver: SliverToBoxAdapter(
              child: SectionHeader(
                title: 'Destinations à découvrir',
                actionLabel: 'Tout voir',
                onAction: widget.onExplore,
              ),
            ),
          ),
          SliverPadding(
            padding: EdgeInsets.fromLTRB(sidePad, 4, sidePad, 32),
            sliver: FutureBuilder<List<Destination>>(
              future: _destinations,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return SliverToBoxAdapter(
                    child: LoadingView(
                      isGrid: grid,
                      itemCount: grid ? cols * 2 : 4,
                    ),
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
                    child: EmptyView(
                      message: 'Aucune destination publiée pour le moment.',
                      icon: Icons.landscape_outlined,
                    ),
                  );
                }

                if (grid) {
                  return SliverGrid(
                    gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: cols,
                      crossAxisSpacing: gap,
                      mainAxisSpacing: gap,
                      childAspectRatio: _tileAspectRatio(width, cols),
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
                    (_, i) => Padding(
                      padding: EdgeInsets.only(
                        bottom: i == items.length - 1 ? 0 : 12,
                      ),
                      child: DestinationTile(
                        destination: items[i],
                        api: widget.api,
                        compact: false,
                        index: i,
                      ),
                    ),
                    childCount: items.length,
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _HeroBanner extends StatefulWidget {
  const _HeroBanner({required this.onExplore});
  final VoidCallback onExplore;

  @override
  State<_HeroBanner> createState() => _HeroBannerState();
}

class _HeroBannerState extends State<_HeroBanner>
    with SingleTickerProviderStateMixin {
  static const _images = [
    'lib/images/Stade.jfif',
    'lib/images/basilique.jpg',
    'lib/images/Cascade.jfif',
  ];

  late final AnimationController _ctrl;
  late final Timer _imageTimer;
  int _imageIndex = 0;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 10),
    )..repeat();
    _imageTimer = Timer.periodic(const Duration(seconds: 3), (_) {
      setState(() => _imageIndex = (_imageIndex + 1) % _images.length);
    });
  }

  @override
  void dispose() {
    _imageTimer.cancel();
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
          Positioned.fill(
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 600),
              child: Image.asset(
                _images[_imageIndex],
                key: ValueKey(_images[_imageIndex]),
                fit: BoxFit.cover,
                width: double.infinity,
                height: double.infinity,
              ),
            ),
          ),
          Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.centerLeft,
                  end: Alignment.centerRight,
                  colors: [
                    Colors.black.withValues(alpha: 0.65),
                    Colors.black.withValues(alpha: 0.08),
                  ],
                ),
              ),
            ),
          ),
          AnimatedBuilder(
            animation: _ctrl,
            builder: (_, _) => Stack(
              children: [
                _circle(
                  size: 160,
                  top: -30 + _ctrl.value * 8,
                  right: -30 + _ctrl.value * 6,
                  alpha: 0.08,
                ),
                _circle(
                  size: 100,
                  bottom: -20 - _ctrl.value * 6,
                  left: 20 - _ctrl.value * 4,
                  alpha: 0.06,
                ),
                _circle(
                  size: 50,
                  top: 60 + _ctrl.value * 5,
                  left: 60 + _ctrl.value * 3,
                  alpha: 0.05,
                ),
              ],
            ),
          ),
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
                  ),
                ),
                const SizedBox(height: 16),
                OutlinedButton(
                  onPressed: widget.onExplore,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: const BorderSide(color: Colors.white, width: 1.5),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 20,
                      vertical: 10,
                    ),
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

  Widget _circle({
    required double size,
    double? top,
    double? bottom,
    double? left,
    double? right,
    required double alpha,
  }) {
    return Positioned(
      top: top,
      bottom: bottom,
      left: left,
      right: right,
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: Colors.white.withValues(alpha: alpha),
        ),
      ),
    );
  }
}

class _FeatureShowcase extends StatelessWidget {
  const _FeatureShowcase({
    required this.features,
    required this.onOpen,
    required this.isGrid,
    required this.crossAxisCount,
  });

  final List<_FeatureData> features;
  final ValueChanged<_FeatureData> onOpen;
  final bool isGrid;
  final int crossAxisCount;

  @override
  Widget build(BuildContext context) {
    if (!isGrid) {
      return GridView.builder(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        itemCount: features.length,
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 3,
          crossAxisSpacing: 10,
          mainAxisSpacing: 10,
          childAspectRatio: 0.95,
        ),
        itemBuilder: (_, i) => FadeSlideIn(
          delay: kStaggerDelay * i,
          child: _FeatureChip(
            data: features[i],
            onTap: () => onOpen(features[i]),
          ),
        ),
      );
    }

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: features.length,
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: crossAxisCount,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
        childAspectRatio: 2.5,
      ),
      itemBuilder: (_, i) => FadeSlideIn(
        delay: kStaggerDelay * i,
        child: FeatureCard(
          icon: features[i].icon,
          title: features[i].title,
          subtitle: features[i].subtitle,
          iconColor: features[i].color,
          onTap: () => onOpen(features[i]),
        ),
      ),
    );
  }
}

class _FeatureChip extends StatefulWidget {
  const _FeatureChip({required this.data, required this.onTap});
  final _FeatureData data;
  final VoidCallback onTap;

  @override
  State<_FeatureChip> createState() => _FeatureChipState();
}

class _FeatureChipState extends State<_FeatureChip> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return GestureDetector(
      onTapDown: (_) => setState(() => _pressed = true),
      onTapUp: (_) => setState(() => _pressed = false),
      onTapCancel: () => setState(() => _pressed = false),
      onTap: widget.onTap,
      child: AnimatedScale(
        scale: _pressed ? 0.93 : 1.0,
        duration: kFastAnim,
        child: Container(
          decoration: BoxDecoration(
            color: scheme.surfaceContainerHighest.withValues(alpha: 0.6),
            borderRadius: BorderRadius.circular(kRadiusLg),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: widget.data.color.withValues(alpha: 0.14),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  widget.data.icon,
                  color: widget.data.color,
                  size: 22,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                widget.data.shortLabel,
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