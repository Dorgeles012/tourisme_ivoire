import 'dart:async';

import 'package:material_ui/material_ui.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/app_alert.dart';
import '../widgets/common.dart';
import 'explore_screen.dart';
import 'home_screen.dart';
import 'profile_screen.dart';
import 'video_screen.dart';
import 'tourism_content_screen.dart';

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

class _AppShellState extends State<AppShell> with WidgetsBindingObserver {
  late final ApiService _api;
  int _index = 0;
  int _contentRevision = 0;
  String? _lastContentVersion;
  Timer? _contentPoll;
  Timer? _notificationPoll;
  bool _checkingContent = false;
  bool _checkingNotifications = false;
  int? _lastNotificationId;

  static const _destinations = [
    _NavItem(Icons.home_rounded, Icons.home_outlined, 'Accueil'),
    _NavItem(Icons.explore_rounded, Icons.explore_outlined, 'Explorer'),
    _NavItem(
      Icons.play_circle_rounded,
      Icons.play_circle_outline_rounded,
      'Vidéos',
    ),
    _NavItem(Icons.person_rounded, Icons.person_outline_rounded, 'Profil'),
  ];

  @override
  void initState() {
    super.initState();
    _api = widget.api ?? ApiService();
    WidgetsBinding.instance.addObserver(this);
    _contentPoll = Timer.periodic(
      const Duration(seconds: 60),
      (_) => _checkContentUpdates(),
    );
    _notificationPoll = Timer.periodic(
      const Duration(seconds: 60),
      (_) => _checkDatabaseNotifications(showNew: true),
    );
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _routeExistingAdmin();
      _checkContentUpdates();
      _checkDatabaseNotifications();
    });
  }

  Future<void> _checkDatabaseNotifications({bool showNew = false}) async {
    if (_checkingNotifications) return;
    _checkingNotifications = true;
    try {
      if (!await _api.hasSession) {
        _lastNotificationId = null;
        return;
      }
      final items = await _api.getNotifications(page: 1);
      if (items.isEmpty) return;
      final rows = items
          .whereType<Map>()
          .map(Map<String, dynamic>.from)
          .toList();
      if (rows.isEmpty) return;
      final newestId = rows
          .map((row) => int.tryParse('${row['id']}') ?? 0)
          .reduce((a, b) => a > b ? a : b);
      final previousId = _lastNotificationId;
      _lastNotificationId = newestId;
      if (!showNew ||
          previousId == null ||
          newestId <= previousId ||
          !mounted) {
        return;
      }
      final newest = rows.firstWhere(
        (row) => (int.tryParse('${row['id']}') ?? 0) == newestId,
      );
      final target = Uri.tryParse('${newest['cible_url'] ?? ''}');
      final type = target?.queryParameters['page'];
      final canOpen = const {
        'actualites',
        'promotions',
        'hebergements',
        'activites',
      }.contains(type);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('${newest['titre'] ?? 'Nouveau contenu publié'}'),
          action: canOpen
              ? SnackBarAction(
                  label: 'Ouvrir',
                  onPressed: () => _openPublishedContent(type!),
                )
              : null,
        ),
      );
    } catch (_) {
      // MySQL notification polling must not block normal browsing when offline.
    } finally {
      _checkingNotifications = false;
    }
  }

  void _openPublishedContent(String type) {
    if (!const {
      'actualites',
      'promotions',
      'hebergements',
      'activites',
    }.contains(type)) {
      return;
    }
    setState(() => _index = 0);
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => TourismContentScreen(api: _api, initialType: type),
      ),
    );
  }

  Future<void> _routeExistingAdmin() async {
    if (!await _api.hasSession || !mounted) return;
    try {
      final opened = await AppAlert.runWithLoading(
        context,
        action: () async {
          final user = await _api.currentUser();
          if (user['role'] != 'Sadmin') return false;
          final code = await _api.createAdminHandoff();
          return launchUrl(
            _api.adminHandoffUri(code),
            mode: LaunchMode.externalApplication,
          );
        },
      );
      if (!opened && mounted) {
        await AppAlert.error(context, 'Impossible d’ouvrir le back-office.');
      }
    } catch (error) {
      if (mounted) await AppAlert.error(context, readableError(error));
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _checkContentUpdates();
      _checkDatabaseNotifications(showNew: true);
    }
  }

  Future<void> _checkContentUpdates() async {
    if (_checkingContent) return;
    _checkingContent = true;
    try {
      final version = await _api.getContentVersion();
      if (!mounted) return;
      if (_lastContentVersion != null && version != _lastContentVersion) {
        setState(() => _contentRevision++);
      }
      _lastContentVersion = version;
    } catch (_) {
      // A version check must never block browsing while the API is unavailable.
    } finally {
      _checkingContent = false;
    }
  }

  @override
  void dispose() {
    _contentPoll?.cancel();
    _notificationPoll?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    if (widget.api == null) _api.dispose();
    super.dispose();
  }

  Future<void> _refreshCurrentPage() async {
    final revision = _contentRevision;
    await _checkContentUpdates();
    if (!mounted) return;
    if (_contentRevision == revision) setState(() => _contentRevision++);
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

    if (useRail) {
      return _DesktopLayout(
        index: _index,
        onIndexChanged: (i) => setState(() => _index = i),
        pages: _pages,
        destinations: _destinations,
        contentRevision: _contentRevision,
      );
    }

    return _MobileLayout(
      index: _index,
      onIndexChanged: (i) => setState(() => _index = i),
      pages: _pages,
      destinations: _destinations,
      contentRevision: _contentRevision,
      onRefresh: _refreshCurrentPage,
    );
  }
}

class _MobileLayout extends StatelessWidget {
  const _MobileLayout({
    required this.index,
    required this.onIndexChanged,
    required this.pages,
    required this.destinations,
    required this.contentRevision,
    required this.onRefresh,
  });

  final int index;
  final ValueChanged<int> onIndexChanged;
  final List<Widget> pages;
  final List<_NavItem> destinations;
  final int contentRevision;
  final Future<void> Function() onRefresh;

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
          transitionBuilder: (child, animation) =>
              FadeTransition(opacity: animation, child: child),
          child: KeyedSubtree(
            key: ValueKey((index, contentRevision)),
            child: index == 0
                ? pages[index]
                : RefreshIndicator(onRefresh: onRefresh, child: pages[index]),
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

class _DesktopLayout extends StatelessWidget {
  const _DesktopLayout({
    required this.index,
    required this.onIndexChanged,
    required this.pages,
    required this.destinations,
    required this.contentRevision,
  });

  final int index;
  final ValueChanged<int> onIndexChanged;
  final List<Widget> pages;
  final List<_NavItem> destinations;
  final int contentRevision;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Scaffold(
      body: SafeArea(
        child: Row(
          children: [
            NavigationRail(
              selectedIndex: index,
              onDestinationSelected: onIndexChanged,
              backgroundColor: scheme.surface,
              extended: true,
              minExtendedWidth: 200,
              indicatorColor: ivorianOrange.withValues(alpha: 0.14),

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
                      child: const Icon(
                        Icons.landscape_rounded,
                        color: Colors.white,
                        size: 22,
                      ),
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

            VerticalDivider(
              width: 1,
              thickness: 1,
              color: scheme.outlineVariant.withValues(alpha: 0.4),
            ),

            Expanded(
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 200),
                transitionBuilder: (child, animation) =>
                    FadeTransition(opacity: animation, child: child),
                child: KeyedSubtree(
                  key: ValueKey((index, contentRevision)),
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
