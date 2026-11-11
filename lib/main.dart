import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'services/api_service.dart';
import 'screens/app_shell.dart';
import 'screens/onboarding_screen.dart';
import 'theme.dart';

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
          'clair' => ThemeMode.light,
          _ => ThemeMode.system,
        };
        _highContrast = prefs.getBool('high_contrast') ?? false;
        _largeText = prefs.getBool('large_text') ?? false;
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
        'clair' => ThemeMode.light,
        _ => ThemeMode.system,
      };
    });
  }

  Future<void> _setAccessibility(Map<String, dynamic> options) async {
    final prefs = await _preferences;
    final highContrast = options['contraste_eleve'] == true;
    final largeText = options['texte_agrandi'] == true;
    await prefs.setBool('high_contrast', highContrast);
    await prefs.setBool('large_text', largeText);
    if (!mounted) return;
    setState(() {
      _highContrast = highContrast;
      _largeText = largeText;
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
      data: MediaQuery.of(context)
          .copyWith(textScaler: TextScaler.linear(_largeText ? 1.18 : 1)),
      child: child ?? const SizedBox.shrink(),
    ),
    home: FutureBuilder<SharedPreferences>(
      future: _preferences,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Scaffold(
            body: Center(child: CircularProgressIndicator()),
          );
        }
        final prefs = snapshot.data!;
        if (prefs.getBool('onboarding_complete') != true) {
          return OnboardingScreen(
            onComplete: () async {
              await prefs.setBool('onboarding_complete', true);
              if (!context.mounted) return;
              Navigator.of(context).pushReplacement(
                MaterialPageRoute<void>(
                  builder: (_) => AppShell(
                    api: widget.api,
                    onThemeChanged: _setTheme,
                    onAccessibilityChanged: _setAccessibility,
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
