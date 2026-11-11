import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../theme.dart';
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

  @override
  void initState() {
    super.initState();
    _api = widget.api ?? ApiService();
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
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
    return Scaffold(
      body: SafeArea(
        child: IndexedStack(index: _index, children: pages),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (index) => setState(() => _index = index),
        backgroundColor: Theme.of(context).colorScheme.surface,
        indicatorColor: ivorianOrange.withValues(alpha: .15),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Accueil',
          ),
          NavigationDestination(
            icon: Icon(Icons.explore_outlined),
            selectedIcon: Icon(Icons.explore),
            label: 'Explorer',
          ),
          NavigationDestination(
            icon: Icon(Icons.play_circle_outline),
            selectedIcon: Icon(Icons.play_circle),
            label: 'Vidéos',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}
