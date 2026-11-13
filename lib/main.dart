// lib/main.dart
// Point d'entrée — Tourisme Ivoire
import 'package:material_ui/material_ui.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'services/api_service.dart';
import 'screens/app_shell.dart';
import 'screens/onboarding_screen.dart';
import 'theme/design_system.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const TourismeIvoireApp());
}

class TourismeIvoireApp extends StatefulWidget {
  const TourismeIvoireApp({this.api, super.key});
  final ApiService? api;

  @override
  State<TourismeIvoireApp> createState() => _TourismeIvoireAppState();
}

class _TourismeIvoireAppState extends State<TourismeIvoireApp> {
  late Future<SharedPreferences> _preferences;
  ThemeMode _themeMode = ThemeMode.system;
  bool _highContrast = false;
  bool _largeText = false;

  @override
  void initState() {
    super.initState();
    _preferences = SharedPreferences.getInstance();
    _preferences.then((prefs) {
      if (!mounted) return;
      setState(() {
        _themeMode = switch (prefs.getString('theme') ?? 'systeme') {
          'sombre' => ThemeMode.dark,
          'clair'  => ThemeMode.light,
          _        => ThemeMode.system,
        };
        _highContrast = prefs.getBool('high_contrast') ?? false;
        _largeText    = prefs.getBool('large_text')    ?? false;
      });
    });
  }

  Future<void> _setTheme(String value) async {
    final prefs = await _preferences;
    await prefs.setString('theme', value);
    if (!mounted) return;
    setState(() {
      _themeMode = switch (value) {
        'sombre' => ThemeMode.dark,
        'clair'  => ThemeMode.light,
        _        => ThemeMode.system,
      };
    });
  }

  Future<void> _setAccessibility(Map<String, dynamic> options) async {
    final prefs = await _preferences;
    final highContrast = options['contraste_eleve'] == true;
    final largeText    = options['texte_agrandi']    == true;
    await prefs.setBool('high_contrast', highContrast);
    await prefs.setBool('large_text', largeText);
    if (!mounted) return;
    setState(() {
      _highContrast = highContrast;
      _largeText    = largeText;
    });
  }

  @override
  Widget build(BuildContext context) => MaterialApp(
    title: 'Tourisme Ivoire',
    debugShowCheckedModeBanner: false,
    theme: buildLightTheme(highContrast: _highContrast),
    darkTheme: buildDarkTheme(highContrast: _highContrast),
    themeMode: _themeMode,
    builder: (context, child) => MediaQuery(
      data: MediaQuery.of(context).copyWith(
        textScaler: TextScaler.linear(_largeText ? 1.18 : 1.0),
      ),
      child: child ?? const SizedBox.shrink(),
    ),
    home: FutureBuilder<SharedPreferences>(
      future: _preferences,
      builder: (context, snapshot) {
        // Splash stylisé pendant le chargement
        if (!snapshot.hasData) return const _SplashScreen();

        final prefs = snapshot.data!;
        if (prefs.getBool('onboarding_complete') != true) {
          return OnboardingScreen(
            onComplete: () async {
              await prefs.setBool('onboarding_complete', true);
              if (!context.mounted) return;
              Navigator.of(context).pushReplacement(
                PageRouteBuilder<void>(
                  pageBuilder: (_, _, _) => AppShell(
                    api: widget.api,
                    onThemeChanged: _setTheme,
                    onAccessibilityChanged: _setAccessibility,
                  ),
                  transitionDuration: const Duration(milliseconds: 400),
                  transitionsBuilder: (_, anim, _, child) => FadeTransition(
                    opacity: CurvedAnimation(parent: anim, curve: Curves.easeOut),
                    child: child,
                  ),
                ),
              );
            },
          );
        }

        return AppShell(
          api: widget.api,
          onThemeChanged: _setTheme,
          onAccessibilityChanged: _setAccessibility,
        );
      },
    ),
  );
}

// 🌅 Splash Screen stylisé

class _SplashScreen extends StatefulWidget {
  const _SplashScreen();

  @override
  State<_SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<_SplashScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _fadeIn;
  late final Animation<double> _scaleIn;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 700),
    );
    _fadeIn = CurvedAnimation(parent: _controller, curve: Curves.easeOut);
    _scaleIn = Tween<double>(begin: 0.75, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.elasticOut),
    );
    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ivorianOrange,
      body: Stack(
        children: [
          // Cercles décoratifs
          Positioned(
            top: -60,
            right: -60,
            child: Container(
              width: 220,
              height: 220,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.07),
              ),
            ),
          ),
          Positioned(
            bottom: -40,
            left: -40,
            child: Container(
              width: 180,
              height: 180,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.07),
              ),
            ),
          ),
          Positioned(
            top: 100,
            left: -80,
            child: Container(
              width: 140,
              height: 140,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.05),
              ),
            ),
          ),
          // Contenu centré
          Center(
            child: FadeTransition(
              opacity: _fadeIn,
              child: ScaleTransition(
                scale: _scaleIn,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Logo
                    Container(
                      width: 100,
                      height: 100,
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.2),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.landscape_rounded,
                        size: 56,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 24),
                    const Text(
                      'Tourisme Ivoire',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 28,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.5,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Découvrez la Côte d\'Ivoire',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.85),
                        fontSize: 15,
                        fontWeight: FontWeight.w400,
                      ),
                    ),
                    const SizedBox(height: 48),
                    SizedBox(
                      width: 32,
                      height: 32,
                      child: CircularProgressIndicator(
                        color: Colors.white.withValues(alpha: 0.7),
                        strokeWidth: 2.5,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
