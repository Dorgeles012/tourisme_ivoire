 
 
 
import 'package:material_ui/material_ui.dart';


 


 
const Color ivorianOrange = Color(0xFFF77F00);

 
const Color ivorianInk = Color(0xFF1A1A1A);

 
const Color ivorianOcre = Color(0xFFE36414);

 
const Color ivorianTerracotta = Color(0xFF9A031E);

 
const Color ivorianForest = Color(0xFF0F4C5C);

 
const Color ivorianGreen = Color(0xFF009E60);

 
const Color ivorianGold = Color(0xFFD4AF37);

 
const Color ivorianPaper = Color(0xFFFAF7F2);


 


const Duration kFastAnim   = Duration(milliseconds: 150);
const Duration kNormalAnim = Duration(milliseconds: 250);
const Duration kSlowAnim   = Duration(milliseconds: 400);
const Duration kStaggerDelay = Duration(milliseconds: 55);


 


const double kRadiusSm = 10.0;
const double kRadiusMd = 16.0;
const double kRadiusLg = 22.0;
const double kRadiusXl = 30.0;

BorderRadius get kBrSm  => BorderRadius.circular(kRadiusSm);
BorderRadius get kBrMd  => BorderRadius.circular(kRadiusMd);
BorderRadius get kBrLg  => BorderRadius.circular(kRadiusLg);
BorderRadius get kBrXl  => BorderRadius.circular(kRadiusXl);
BorderRadius get kBrPill => const BorderRadius.all(Radius.circular(100));


 


List<BoxShadow> get kShadowSoft => [
  BoxShadow(
    color: Colors.black.withValues(alpha: 0.07),
    blurRadius: 16,
    offset: const Offset(0, 4),
  ),
];

List<BoxShadow> get kShadowStrong => [
  BoxShadow(
    color: Colors.black.withValues(alpha: 0.14),
    blurRadius: 24,
    offset: const Offset(0, 8),
  ),
];

List<BoxShadow> kShadowColored(Color color) => [
  BoxShadow(
    color: color.withValues(alpha: 0.28),
    blurRadius: 18,
    offset: const Offset(0, 6),
  ),
];


 


 
const LinearGradient kHeroGradient = LinearGradient(
  begin: Alignment.topLeft,
  end: Alignment.bottomRight,
  colors: [Color(0xFFFFB347), ivorianOrange, ivorianOcre],
  stops: [0.0, 0.5, 1.0],
);

 
const LinearGradient kImageOverlayGradient = LinearGradient(
  begin: Alignment.topCenter,
  end: Alignment.bottomCenter,
  colors: [Colors.transparent, Color(0xCC000000)],
  stops: [0.4, 1.0],
);

 
const LinearGradient kAppBarGradient = LinearGradient(
  begin: Alignment.topCenter,
  end: Alignment.bottomCenter,
  colors: [Color(0x28F77F00), Colors.transparent],
);


 


double horizontalPadding(double width) {
  if (width >= 900) return 40.0;
  if (width >= 600) return 28.0;
  return 18.0;
}

bool isGridLayout(double width) => width >= 700;

int crossAxisCount(double width) {
  if (width >= 1100) return 3;
  if (width >= 700)  return 2;
  return 1;
}

bool isDesktop(double width) => width >= 900;
bool isTablet(double width) => width >= 600 && width < 900;
bool isMobile(double width) => width < 600;


 


ThemeData buildLightTheme({bool highContrast = false}) {
  final base = ColorScheme.fromSeed(
    seedColor: ivorianOrange,
    primary: ivorianOrange,
    secondary: ivorianGreen,
    surface: ivorianPaper,
    brightness: Brightness.light,
  );
  final colors = highContrast
      ? base.copyWith(
          primary: const Color(0xFF8A4300),
          secondary: const Color(0xFF006B40),
          onSurface: Colors.black,
          surface: Colors.white,
        )
      : base;
  return _buildTheme(
    colors,
    highContrast ? Colors.white : ivorianPaper,
    highContrast ? Colors.black : ivorianInk,
  );
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
      ? base.copyWith(
          primary: const Color(0xFFFFB45C),
          secondary: const Color(0xFF7BE0AB),
          onSurface: Colors.white,
          surface: Colors.black,
        )
      : base;
  return _buildTheme(
    colors,
    highContrast ? Colors.black : const Color(0xFF1E1A17),
    highContrast ? Colors.white : const Color(0xFFF0EBE3),
  );
}

ThemeData _buildTheme(ColorScheme colors, Color background, Color foreground) {
  final primary = colors.primary;

  return ThemeData(
    useMaterial3: true,
    colorScheme: colors,
    scaffoldBackgroundColor: background,

     
    appBarTheme: AppBarTheme(
      backgroundColor: background,
      foregroundColor: foreground,
      elevation: 0,
      scrolledUnderElevation: 1,
      shadowColor: Colors.black.withValues(alpha: 0.08),
      titleTextStyle: TextStyle(
        color: foreground,
        fontSize: 20,
        fontWeight: FontWeight.w800,
        letterSpacing: -0.3,
      ),
    ),

     
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: background,
      indicatorColor: primary.withValues(alpha: 0.14),
      labelTextStyle: WidgetStateProperty.resolveWith((states) {
        final selected = states.contains(WidgetState.selected);
        return TextStyle(
          fontSize: 11.5,
          fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
          color: selected ? primary : colors.onSurfaceVariant,
        );
      }),
      iconTheme: WidgetStateProperty.resolveWith((states) {
        final selected = states.contains(WidgetState.selected);
        return IconThemeData(
          color: selected ? primary : colors.onSurfaceVariant,
          size: 24,
        );
      }),
    ),

     
    navigationRailTheme: NavigationRailThemeData(
      backgroundColor: background,
      selectedIconTheme: IconThemeData(color: primary),
      unselectedIconTheme: IconThemeData(color: colors.onSurfaceVariant),
      selectedLabelTextStyle: TextStyle(color: primary, fontWeight: FontWeight.w700),
      indicatorColor: primary.withValues(alpha: 0.14),
    ),

     
    cardTheme: CardThemeData(
      color: colors.surface,
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(kRadiusLg)),
      shadowColor: Colors.black.withValues(alpha: 0.08),
    ),

     
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: colors.surfaceContainerHighest.withValues(alpha: 0.5),
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(kRadiusXl),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(kRadiusXl),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(kRadiusXl),
        borderSide: BorderSide(color: primary, width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(kRadiusXl),
        borderSide: BorderSide(color: colors.error, width: 1.5),
      ),
      hintStyle: TextStyle(
        color: colors.onSurfaceVariant.withValues(alpha: 0.6),
        fontWeight: FontWeight.w400,
      ),
    ),

     
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: primary,
        foregroundColor: Colors.white,
        minimumSize: const Size(44, 48),
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(kRadiusXl),
        ),
        textStyle: const TextStyle(
          fontWeight: FontWeight.w700,
          fontSize: 15,
          letterSpacing: 0.2,
        ),
      ),
    ),

     
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: primary,
        minimumSize: const Size(44, 48),
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(kRadiusXl),
        ),
        side: BorderSide(color: primary, width: 1.5),
        textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
      ),
    ),

     
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: primary,
        textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
        minimumSize: const Size(44, 44),
      ),
    ),

     
    chipTheme: ChipThemeData(
      backgroundColor: colors.surfaceContainerHighest.withValues(alpha: 0.6),
      selectedColor: primary.withValues(alpha: 0.15),
      checkmarkColor: primary,
      labelStyle: const TextStyle(
        fontSize: 13,
        fontWeight: FontWeight.w600,
      ),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(kRadiusXl)),
      side: BorderSide.none,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
    ),

     
    snackBarTheme: SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(kRadiusMd)),
      backgroundColor: colors.inverseSurface,
      contentTextStyle: TextStyle(
        color: colors.onInverseSurface,
        fontWeight: FontWeight.w500,
      ),
    ),

     
    dividerTheme: DividerThemeData(
      color: colors.outlineVariant.withValues(alpha: 0.5),
      thickness: 1,
      space: 1,
    ),

     
    progressIndicatorTheme: ProgressIndicatorThemeData(
      color: primary,
      linearTrackColor: primary.withValues(alpha: 0.15),
    ),
  );
}
