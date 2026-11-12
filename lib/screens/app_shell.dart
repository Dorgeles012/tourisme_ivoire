// lib/screens/app_shell.dart
// Navigation principale — responsive bottom nav / rail latéral
import 'package:material_ui/material_ui.dart';

import '../services/api_service.dart';
import '../theme/design_system.dart';
import 'explore_screen.dart';
import 'home_screen.dart';
import 'profile_screen.dart';
import 'video_screen.dart';

class AppShell extends StatefulWidget {
  const AppShell({
    required this.onThemeChanged,
    required this.onAccessibilityChanged,
    this.api,
    super.key,
  });

  final ValueChanged<String> onThemeChanged;
  final ValueChanged<Map<String, dynamic>> onAccessibilityChanged;
  final ApiService? api;

  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  late final ApiService _api;
  int _index = 0;

  static const _destinations = [
    _NavItem(Icons.home_rounded, Icons.home_outlined, 'Accueil'),
    _NavItem(Icons.explore_rounded, Icons.explore_outlined, 'Explorer'),
    _NavItem(Icons.play_circle_rounded, Icons.play_circle_outline_rounded, 'Vidéos'),
    _NavItem(Icons.person_rounded, Icons.person_outline_rounded, 'Profil'),
  ];

  @override
  void initState() {
    super.initState();
    _api = widget.api ?? ApiService();
  }

  List<Widget> get _pages => [
    HomeScreen(
      api: _api,
      onExplore: () => setState(() => _index = 1),
      onThemeChanged: widget.onThemeChanged,
      onAccessibilityChanged: widget.onAccessibilityChanged,
    ),
    ExploreScreen(api: _api),
    VideoFeedScreen(api: _api),
    ProfileScreen(
      api: _api,
      onThemeChanged: widget.onThemeChanged,
      onAccessibilityChanged: widget.onAccessibilityChanged,
    ),
  ];

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final useRail = isDesktop(width);

    if (useRail) return _DesktopLayout(
      index: _index,
      onIndexChanged: (i) => setState(() => _index = i),
      pages: _pages,
      destinations: _destinations,
    );

    return _MobileLayout(
      index: _index,
      onIndexChanged: (i) => setState(() => _index = i),
      pages: _pages,
      destinations: _destinations,
    );
  }
}

// ═══════════════════════════════════════════════════════════
// 📱 Layout Mobile/Tablette — NavigationBar bottom
// ═══════════════════════════════════════════════════════════

class _MobileLayout extends StatelessWidget {
  const _MobileLayout({
    required this.index,
    required this.onIndexChanged,
    required this.pages,
    required this.destinations,
  });

  final int index;
  final ValueChanged<int> onIndexChanged;
  final List<Widget> pages;
  final List<_NavItem> destinations;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: AnimatedSwitcher(
          duration: const Duration(milliseconds: 200),
          switchInCurve: Curves.easeOut,
          switchOutCurve: Curves.easeIn,
          transitionBuilder: (child, animation) => FadeTransition(
            opacity: animation,
            child: child,
          ),
          child: KeyedSubtree(
            key: ValueKey(index),
            child: pages[index],
          ),
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        onDestinationSelected: onIndexChanged,
        backgroundColor: scheme.surface,
        indicatorColor: ivorianOrange.withValues(alpha: 0.14),
        height: 68,
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        destinations: [
          for (final d in destinations)
            NavigationDestination(
              icon: Icon(d.icon),
              selectedIcon: Icon(d.selectedIcon, color: ivorianOrange),
              label: d.label,
            ),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════
// 🖥️ Layout Desktop — NavigationRail latéral
// ═══════════════════════════════════════════════════════════

class _DesktopLayout extends StatelessWidget {
  const _DesktopLayout({
    required this.index,
    required this.onIndexChanged,
    required this.pages,
    required this.destinations,
  });

  final int index;
  final ValueChanged<int> onIndexChanged;
  final List<Widget> pages;
  final List<_NavItem> destinations;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Scaffold(
      body: SafeArea(
        child: Row(
          children: [
            // Rail latéral
            NavigationRail(
              selectedIndex: index,
              onDestinationSelected: onIndexChanged,
              backgroundColor: scheme.surface,
              extended: true,
              minExtendedWidth: 200,
              indicatorColor: ivorianOrange.withValues(alpha: 0.14),
              // Header avec logo
              leading: Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 20),
                child: Row(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFFFFB347), ivorianOrange],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(kRadiusMd),
                      ),
                      child: const Icon(Icons.landscape_rounded, color: Colors.white, size: 22),
                    ),
                    const SizedBox(width: 10),
                    const Text(
                      'Tourisme\nIvoire',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        height: 1.2,
                      ),
                    ),
                  ],
                ),
              ),
              destinations: [
                for (final d in destinations)
                  NavigationRailDestination(
                    icon: Icon(d.icon),
                    selectedIcon: Icon(d.selectedIcon, color: ivorianOrange),
                    label: Text(d.label),
                    padding: const EdgeInsets.symmetric(vertical: 4),
                  ),
              ],
            ),
            // Séparateur
            VerticalDivider(
              width: 1,
              thickness: 1,
              color: scheme.outlineVariant.withValues(alpha: 0.4),
            ),
            // Contenu principal
            Expanded(
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 200),
                transitionBuilder: (child, animation) => FadeTransition(
                  opacity: animation,
                  child: child,
                ),
                child: KeyedSubtree(
                  key: ValueKey(index),
                  child: pages[index],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _NavItem {
  const _NavItem(this.selectedIcon, this.icon, this.label);
  final IconData selectedIcon;
  final IconData icon;
  final String label;
}
