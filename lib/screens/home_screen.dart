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
  bool _destinationsInitialized = false;
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
    if (cols != _lastCols) {
      _lastCols = cols;
      final shouldFetch = desired != _perPage || !_destinationsInitialized;
      _perPage = desired;
      if (!shouldFetch) return;
      _destinationsInitialized = true;
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
                onPressed: () => _open(NotificationsScreen(api: widget.api)),
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
            padding: EdgeInsets.fromLTRB(sidePad, 28, sidePad, 8),
            sliver: SliverToBoxAdapter(
              child: const SectionHeader(title: 'Que voulez-vous faire ?'),
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
            padding: EdgeInsets.fromLTRB(sidePad, 28, sidePad, 8),
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
    final screenWidth = MediaQuery.sizeOf(context).width;
    final textScale = MediaQuery.textScalerOf(context).scale(14) / 14;
    final baseHeight = screenWidth < 400
        ? 280.0
        : screenWidth < 480
        ? 250.0
        : 230.0;
    final bannerHeight =
        baseHeight + ((textScale - 1).clamp(0.0, 2.0) * 170).toDouble();

    return Container(
      height: bannerHeight,
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
              duration: const Duration(milliseconds: 800),
              switchInCurve: Curves.easeOutCubic,
              switchOutCurve: Curves.easeInCubic,
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
                    Colors.black.withValues(alpha: 0.75),
                    Colors.black.withValues(alpha: 0.10),
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
                  size: 180,
                  top: -40 + _ctrl.value * 10,
                  right: -40 + _ctrl.value * 8,
                  alpha: 0.10,
                ),
                _circle(
                  size: 120,
                  bottom: -30 - _ctrl.value * 8,
                  left: 30 - _ctrl.value * 5,
                  alpha: 0.07,
                ),
                _circle(
                  size: 60,
                  top: 70 + _ctrl.value * 6,
                  left: 70 + _ctrl.value * 4,
                  alpha: 0.06,
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
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: ivorianOrange,
                    borderRadius: BorderRadius.circular(50),
                  ),
                  child: const Text(
                    'À LA UNE',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 1,
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                const Text(
                  'Découvrez la\nCôte d\'Ivoire',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 28,
                    fontWeight: FontWeight.w900,
                    height: 1.1,
                    letterSpacing: -0.6,
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
                FilledButton(
                  onPressed: widget.onExplore,
                  style: FilledButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: ivorianOrange,
                    padding: const EdgeInsets.symmetric(
                      horizontal: 22,
                      vertical: 12,
                    ),
                    minimumSize: const Size(44, 44),
                    textStyle: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 13,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(kRadiusXl),
                    ),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text('Explorer maintenant'),
                      SizedBox(width: 6),
                      Icon(Icons.arrow_forward_rounded, size: 16),
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
          gradient: RadialGradient(
            colors: [
              Colors.white.withValues(alpha: alpha),
              Colors.white.withValues(alpha: 0),
            ],
          ),
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
          childAspectRatio: 0.92,
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
        child: _ModernFeatureCard(
          data: features[i],
          onTap: () => onOpen(features[i]),
        ),
      ),
    );
  }
}

class _ModernFeatureCard extends StatefulWidget {
  const _ModernFeatureCard({required this.data, required this.onTap});
  final _FeatureData data;
  final VoidCallback onTap;

  @override
  State<_ModernFeatureCard> createState() => _ModernFeatureCardState();
}

class _ModernFeatureCardState extends State<_ModernFeatureCard> {
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
        scale: _pressed ? 0.96 : 1.0,
        duration: kFastAnim,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: scheme.surface,
            borderRadius: BorderRadius.circular(kRadiusLg),
            border: Border.all(
              color: scheme.outlineVariant.withValues(alpha: 0.35),
              width: 0.6,
            ),
            boxShadow: [
              BoxShadow(
                color: widget.data.color.withValues(alpha: 0.06),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      widget.data.color.withValues(alpha: 0.20),
                      widget.data.color.withValues(alpha: 0.06),
                    ],
                  ),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  widget.data.icon,
                  color: widget.data.color,
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      widget.data.title,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: scheme.onSurface,
                        letterSpacing: -0.1,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      widget.data.subtitle,
                      style: TextStyle(
                        fontSize: 11.5,
                        color: scheme.onSurface.withValues(alpha: 0.55),
                        height: 1.3,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.chevron_right_rounded,
                size: 20,
                color: scheme.onSurface.withValues(alpha: 0.3),
              ),
            ],
          ),
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
            color: scheme.surface,
            borderRadius: BorderRadius.circular(kRadiusLg),
            border: Border.all(
              color: scheme.outlineVariant.withValues(alpha: 0.35),
              width: 0.6,
            ),
            boxShadow: [
              BoxShadow(
                color: widget.data.color.withValues(alpha: 0.06),
                blurRadius: 10,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      widget.data.color.withValues(alpha: 0.20),
                      widget.data.color.withValues(alpha: 0.06),
                    ],
                  ),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  widget.data.icon,
                  color: widget.data.color,
                  size: 22,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                widget.data.shortLabel,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: scheme.onSurface,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
