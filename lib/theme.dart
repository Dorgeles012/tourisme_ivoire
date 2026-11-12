import 'package:material_ui/material_ui.dart';

const ivorianOrange = Color(0xFFF77F00);
const ivorianGreen = Color(0xFF009E60);
const ivorianGold = Color(0xFFD4AF37);
const ivorianPaper = Color(0xFFFAF7F2);
const ivorianInk = Color(0xFF1E1A17);

ThemeData buildLightTheme({bool highContrast = false}) {
  final base = ColorScheme.fromSeed(
        seedColor: ivorianOrange,
        primary: ivorianOrange,
        secondary: ivorianGreen,
        surface: ivorianPaper,
        brightness: Brightness.light,
      );
  final colors = highContrast
      ? base.copyWith(primary: const Color(0xFF8A4300), secondary: const Color(0xFF006B40), onSurface: Colors.black, surface: Colors.white)
      : base;
  return _buildTheme(colors, highContrast ? Colors.white : ivorianPaper, Colors.black);
}

ThemeData buildDarkTheme({bool highContrast = false}) {
  final base = ColorScheme.fromSeed(
        seedColor: ivorianOrange,
        primary: const Color(0xFFFFA43B),
        secondary: const Color(0xFF54C88F),
        surface: const Color(0xFF1E1A17),
        brightness: Brightness.dark,
      );
  final colors = highContrast
      ? base.copyWith(primary: const Color(0xFFFFB45C), secondary: const Color(0xFF7BE0AB), onSurface: Colors.white, surface: Colors.black)
      : base;
  return _buildTheme(colors, highContrast ? Colors.black : const Color(0xFF1E1A17), Colors.white);
}

ThemeData _buildTheme(ColorScheme colors, Color background, Color foreground) => ThemeData(
      useMaterial3: true,
      colorScheme: colors,
      scaffoldBackgroundColor: background,
      appBarTheme: AppBarTheme(backgroundColor: background, foregroundColor: foreground, elevation: 0),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: colors.surfaceContainerHighest.withValues(alpha: .45),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
      ),
      cardTheme: CardThemeData(
        color: colors.surface,
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
      ),
    );
