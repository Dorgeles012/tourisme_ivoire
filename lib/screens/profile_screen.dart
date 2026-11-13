import 'package:material_ui/material_ui.dart';
import 'package:file_picker/file_picker.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';
import 'auth_screen.dart';
import 'feature_screens.dart';
import 'passport_screen.dart';

// PROFILE SCREEN
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({
    required this.api,
    required this.onThemeChanged,
    required this.onAccessibilityChanged,
    super.key,
  });
  final ApiService api;
  final ValueChanged<String> onThemeChanged;
  final ValueChanged<Map<String, dynamic>> onAccessibilityChanged;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late Future<Map<String, dynamic>> _user;

  @override
  void initState() {
    super.initState();
    _user = widget.api.currentUser();
  }

  void _reload() => setState(() => _user = widget.api.currentUser());

  Future<void> _authenticate() async {
    final authenticated = await navigateTo<bool>(
      context,
      AuthScreen(api: widget.api),
    );
    if (!mounted) return;
    if (authenticated == true) {
      try {
        final user = await widget.api.currentUser();
        if (user['role'] == 'Sadmin') {
          final code = await widget.api.createAdminHandoff();
          final opened = await launchUrl(
            widget.api.adminHandoffUri(code),
            mode: LaunchMode.externalApplication,
          );
          if (!opened) throw StateError('Impossible to open admin portal.');
        }
      } catch (error) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(readableError(error))),
          );
        }
      }
    }
    if (mounted) _reload();
  }

  Future<void> _editProfile(Map<String, dynamic> user) async {
    await navigateTo(context, EditProfileScreen(api: widget.api, user: user));
    if (mounted) _reload();
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, dynamic>>(
      future: _user,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const LoadingView();
        }
        if (snapshot.hasError) {
          return _UnauthenticatedView(
            api: widget.api,
            onThemeChanged: widget.onThemeChanged,
            onAccessibilityChanged: widget.onAccessibilityChanged,
            errorMessage: readableError(snapshot.error!),
            onAuthenticate: _authenticate,
          );
        }
        final user = snapshot.data!;
        return _AuthenticatedView(
          api: widget.api,
          user: user,
          onThemeChanged: widget.onThemeChanged,
          onAccessibilityChanged: widget.onAccessibilityChanged,
          onEditProfile: () => _editProfile(user),
          onLogout: () async {
            final messenger = ScaffoldMessenger.of(context);
            try {
              await widget.api.logout();
            } catch (error) {
              if (mounted) {
                messenger.showSnackBar(
                  SnackBar(
                    content: Text(readableError(error)),
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              }
            } finally {
              if (mounted) _reload();
            }
          },
        );
      },
    );
  }
}

// ─── Vue non-connectée ────────────────────────────────────────────────────────
class _UnauthenticatedView extends StatelessWidget {
  const _UnauthenticatedView({
    required this.api,
    required this.onThemeChanged,
    required this.onAccessibilityChanged,
    required this.errorMessage,
    required this.onAuthenticate,
  });
  final ApiService api;
  final ValueChanged<String> onThemeChanged;
  final ValueChanged<Map<String, dynamic>> onAccessibilityChanged;
  final String errorMessage;
  final VoidCallback onAuthenticate;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return ListView(
      padding: EdgeInsets.zero,
      children: [
        Container(
          width: double.infinity,
          height: 360,
          decoration: const BoxDecoration(gradient: kAppBarGradient),
          child: Stack(
            children: [
              Positioned.fill(
                child: Image.asset(
                  'lib/images/Profile.avif',
                  fit: BoxFit.cover,
                ),
              ),
              Positioned.fill(
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: [
                        Colors.black.withValues(alpha: .12),
                        Colors.black.withValues(alpha: .58),
                      ],
                    ),
                  ),
                ),
              ),
              Positioned(
                top: -40,
                right: -40,
                child: _blob(180, Colors.white.withValues(alpha: .08)),
              ),
              Positioned(
                bottom: -60,
                left: -30,
                child: _blob(220, Colors.white.withValues(alpha: .05)),
              ),
              Positioned(
                top: 80,
                left: 40,
                child: _blob(60, Colors.white.withValues(alpha: .04)),
              ),
              SafeArea(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(24, 32, 24, 24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      Container(
                        width: 64,
                        height: 64,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: Colors.white.withValues(alpha: .18),
                          border: Border.all(
                            color: Colors.white.withValues(alpha: .4),
                            width: 2,
                          ),
                        ),
                        child: const Icon(
                          Icons.person_outline_rounded,
                          size: 32,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 16),
                      const Text(
                        'Mon espace voyageur',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 24,
                          fontWeight: FontWeight.w900,
                          letterSpacing: -0.5,
                          height: 1.15,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Connectez-vous pour accéder à votre profil',
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: .85),
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: cs.surface,
              borderRadius: BorderRadius.circular(kRadiusLg),
              boxShadow: kShadowSoft,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'Prêt à explorer ?',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: cs.onSurface,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Retrouvez vos favoris, votre passeport et votre progression.',
                  style: TextStyle(
                    fontSize: 13,
                    color: cs.onSurface.withValues(alpha: .6),
                    height: 1.4,
                  ),
                ),
                const SizedBox(height: 16),
                IvorianButton(
                  label: 'Connexion / Inscription',
                  icon: Icons.login_rounded,
                  onPressed: onAuthenticate,
                  useGradient: true,
                  height: 50,
                ),
              ],
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: Column(
            children: [
              FeatureCard(
                icon: Icons.favorite_rounded,
                title: 'Favoris',
                subtitle: 'Connectez-vous pour consulter vos lieux enregistrés',
                onTap: onAuthenticate,
              ),
              FeatureCard(
                icon: Icons.badge_rounded,
                title: 'Passeport numérique',
                subtitle: 'Visites, progression et récompenses',
                onTap: onAuthenticate,
              ),
              FeatureCard(
                icon: Icons.settings_rounded,
                title: 'Paramètres',
                subtitle: 'Apparence et accessibilité',
                onTap: () => navigateTo(
                  context,
                  SettingsScreen(
                    api: api,
                    onThemeChanged: onThemeChanged,
                    onAccessibilityChanged: onAccessibilityChanged,
                  ),
                ),
              ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.info_outline_rounded,
                size: 12,
                color: cs.onSurface.withValues(alpha: .3),
              ),
              const SizedBox(width: 6),
              Flexible(
                child: Text(
                  errorMessage,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 11,
                    color: cs.onSurface.withValues(alpha: .35),
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _blob(double size, Color color) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(shape: BoxShape.circle, color: color),
  );
}

// ─── Vue connectée ────────────────────────────────────────────────────────────
class _AuthenticatedView extends StatelessWidget {
  const _AuthenticatedView({
    required this.api,
    required this.user,
    required this.onThemeChanged,
    required this.onAccessibilityChanged,
    required this.onEditProfile,
    required this.onLogout,
  });
  final ApiService api;
  final Map<String, dynamic> user;
  final ValueChanged<String> onThemeChanged;
  final ValueChanged<Map<String, dynamic>> onAccessibilityChanged;
  final VoidCallback onEditProfile;
  final VoidCallback onLogout;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    final avatarUrl = user['avatar_url'];
    return ListView(
      padding: EdgeInsets.zero,
      children: [
        Container(
          decoration: const BoxDecoration(gradient: kAppBarGradient),
          child: SafeArea(
            bottom: false,
            child: Stack(
              clipBehavior: Clip.none,
              children: [
                Positioned(
                  top: -40,
                  right: -40,
                  child: Container(
                    width: 180,
                    height: 180,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white.withValues(alpha: .08),
                    ),
                  ),
                ),
                Positioned(
                  bottom: -60,
                  left: -40,
                  child: Container(
                    width: 160,
                    height: 160,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white.withValues(alpha: .05),
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(24, 24, 24, 32),
                  child: Row(
                    children: [
                      Container(
                        width: 76,
                        height: 76,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: Colors.white.withValues(alpha: .2),
                          border: Border.all(
                            color: Colors.white.withValues(alpha: .55),
                            width: 2.5,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: .15),
                              blurRadius: 16,
                              offset: const Offset(0, 6),
                            ),
                          ],
                        ),
                        child: ClipOval(
                          child: avatarUrl is String && avatarUrl.isNotEmpty
                              ? Image.network(
                                  api.mediaUri(avatarUrl).toString(),
                                  width: 76,
                                  height: 76,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, _, _) => Image.asset(
                                    'lib/images/Profile.avif',
                                    width: 76,
                                    height: 76,
                                    fit: BoxFit.cover,
                                  ),
                                )
                              : Image.asset(
                                  'lib/images/Profile.avif',
                                  width: 76,
                                  height: 76,
                                  fit: BoxFit.cover,
                                ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              '${user['prenom'] ?? ''} ${user['nom']}'.trim(),
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 21,
                                fontWeight: FontWeight.w900,
                                letterSpacing: -0.3,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                Icon(
                                  Icons.mail_outline_rounded,
                                  size: 13,
                                  color: Colors.white.withValues(alpha: .8),
                                ),
                                const SizedBox(width: 5),
                                Flexible(
                                  child: Text(
                                    '${user['email']}',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(
                                      color: Colors.white.withValues(
                                        alpha: .85,
                                      ),
                                      fontSize: 13,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      IconButton(
                        tooltip: 'Modifier le profil',
                        onPressed: onEditProfile,
                        style: IconButton.styleFrom(
                          backgroundColor: Colors.white.withValues(alpha: .18),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        icon: const Icon(
                          Icons.edit_rounded,
                          color: Colors.white,
                          size: 20,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        Transform.translate(
          offset: const Offset(0, -22),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 18),
              decoration: BoxDecoration(
                color: cs.surface,
                borderRadius: BorderRadius.circular(kRadiusLg),
                boxShadow: kShadowSoft,
                border: Border.all(
                  color: cs.outlineVariant.withValues(alpha: .4),
                  width: .5,
                ),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  _StatItem(
                    value: '${user['visites'] ?? 0}',
                    label: 'Visites',
                    icon: Icons.place_rounded,
                  ),
                  const _Divider(),
                  _StatItem(
                    value: '${user['favoris'] ?? 0}',
                    label: 'Favoris',
                    icon: Icons.favorite_rounded,
                  ),
                  const _Divider(),
                  _StatItem(
                    value: '${user['points'] ?? 0}',
                    label: 'Points',
                    icon: Icons.stars_rounded,
                  ),
                ],
              ),
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 0),
          child: Column(
            children: [
              FeatureCard(
                icon: Icons.badge_rounded,
                title: 'Passeport numérique',
                subtitle: 'Visites, progression et récompenses',
                onTap: () => navigateTo(context, PassportScreen(api: api)),
              ),
              FeatureCard(
                icon: Icons.favorite_rounded,
                title: 'Mes favoris',
                subtitle: 'Retrouvez vos destinations enregistrées',
                onTap: () => navigateTo(context, FavoritesScreen(api: api)),
              ),
              FeatureCard(
                icon: Icons.notifications_rounded,
                title: 'Notifications',
                subtitle: 'Consultez et marquez les notifications comme lues',
                onTap: () => navigateTo(context, NotificationsScreen(api: api)),
              ),
              FeatureCard(
                icon: Icons.receipt_long_rounded,
                title: 'Réservations',
                subtitle: 'Suivez les demandes envoyées',
                onTap: () =>
                    navigateTo(context, ReservationHistoryScreen(api: api)),
              ),
              FeatureCard(
                icon: Icons.quiz_rounded,
                title: 'Résultats des quiz',
                subtitle: 'Retrouvez votre historique',
                onTap: () => navigateTo(context, QuizResultsScreen(api: api)),
              ),
              FeatureCard(
                icon: Icons.settings_rounded,
                title: 'Paramètres & accessibilité',
                subtitle: 'Thème et préférences',
                onTap: () => navigateTo(
                  context,
                  SettingsScreen(
                    api: api,
                    onThemeChanged: onThemeChanged,
                    onAccessibilityChanged: onAccessibilityChanged,
                  ),
                ),
              ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
          child: OutlinedButton.icon(
            onPressed: onLogout,
            icon: const Icon(Icons.logout_rounded, size: 20),
            label: const Text('Se déconnecter'),
            style: OutlinedButton.styleFrom(
              foregroundColor: ivorianTerracotta,
              side: BorderSide(color: ivorianTerracotta.withValues(alpha: .4)),
              minimumSize: const Size(double.infinity, 50),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(kRadiusMd),
              ),
              textStyle: const TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 14,
              ),
            ),
          ),
        ),
      ],
    );
  }
}

// ─── Widgets internes ─────────────────────────────────────────────────────────
class _StatItem extends StatelessWidget {
  const _StatItem({
    required this.value,
    required this.label,
    required this.icon,
  });
  final String value;
  final String label;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 34,
          height: 34,
          decoration: BoxDecoration(
            color: ivorianOrange.withValues(alpha: .12),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, size: 17, color: ivorianOrange),
        ),
        const SizedBox(height: 8),
        Text(
          value,
          style: const TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.w900,
            color: ivorianOrange,
            letterSpacing: -0.3,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: cs.onSurface.withValues(alpha: .55),
          ),
        ),
      ],
    );
  }
}

class _Divider extends StatelessWidget {
  const _Divider();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 40,
      width: 1,
      color: Theme.of(context).colorScheme.outlineVariant
          .withValues(alpha: .35),
    );
  }
}

// EDIT PROFILE SCREEN
class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({required this.api, required this.user, super.key});
  final ApiService api;
  final Map<String, dynamic> user;

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  late final TextEditingController _name;
  late final TextEditingController _firstName;
  late final TextEditingController _phone;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(text: '${widget.user['nom'] ?? ''}');
    _firstName = TextEditingController(text: '${widget.user['prenom'] ?? ''}');
    _phone = TextEditingController(text: '${widget.user['telephone'] ?? ''}');
  }

  @override
  void dispose() {
    _name.dispose();
    _firstName.dispose();
    _phone.dispose();
    super.dispose();
  }

  Future<void> _changeAvatar() async {
    try {
      final file = await FilePicker.pickFile(type: FileType.image);
      if (file == null || !mounted) return;
      setState(() => _busy = true);
      await widget.api.uploadAvatar(file);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Photo de profil mise à jour.')),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(readableError(error))),
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _save() async {
    setState(() => _busy = true);
    try {
      await widget.api.updateProfile({
        'nom': _name.text,
        'prenom': _firstName.text,
        'telephone': _phone.text,
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('Profil mis à jour !'),
            behavior: SnackBarBehavior.floating,
            backgroundColor: const Color(0xFF2E7D32),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(kRadiusMd),
            ),
          ),
        );
        Navigator.of(context).pop();
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(readableError(error)),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Modifier mon profil'),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: FilledButton(
              onPressed: _busy ? null : _save,
              style: FilledButton.styleFrom(
                backgroundColor: ivorianOrange,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(kRadiusMd),
                ),
              ),
              child: Text(_busy ? 'Enregistrement…' : 'Enregistrer'),
            ),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 32),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  ivorianOrange.withValues(alpha: .12),
                  ivorianOrange.withValues(alpha: .04),
                ],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(kRadiusLg),
            ),
            child: Row(
              children: [
                Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white,
                    boxShadow: kShadowSoft,
                  ),
                  child: const Icon(
                    Icons.person_rounded,
                    color: ivorianOrange,
                    size: 28,
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Informations personnelles',
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w800,
                          color: cs.onSurface,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Ces informations apparaîtront sur votre profil.',
                        style: TextStyle(
                          fontSize: 12,
                          color: cs.onSurface.withValues(alpha: .6),
                        ),
                      ),
                      TextButton.icon(
                        onPressed: _busy ? null : _changeAvatar,
                        icon: const Icon(Icons.add_a_photo_outlined, size: 18),
                        label: const Text('Modifier la photo'),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          const _FieldLabel('Nom'),
          const SizedBox(height: 8),
          TextField(
            controller: _name,
            textInputAction: TextInputAction.next,
            decoration: InputDecoration(
              hintText: 'Votre nom',
              prefixIcon: const Icon(Icons.person_outline_rounded),
              filled: true,
              fillColor: cs.surfaceContainerHighest.withValues(alpha: .4),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(kRadiusMd),
                borderSide: BorderSide.none,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(kRadiusMd),
                borderSide: BorderSide.none,
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(kRadiusMd),
                borderSide: BorderSide(
                  color: ivorianOrange.withValues(alpha: .8),
                  width: 1.5,
                ),
              ),
            ),
          ),
          const SizedBox(height: 18),
          const _FieldLabel('Prénom'),
          const SizedBox(height: 8),
          TextField(
            controller: _firstName,
            textInputAction: TextInputAction.next,
            decoration: InputDecoration(
              hintText: 'Votre prénom',
              prefixIcon: const Icon(Icons.badge_outlined),
              filled: true,
              fillColor: cs.surfaceContainerHighest.withValues(alpha: .4),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(kRadiusMd),
                borderSide: BorderSide.none,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(kRadiusMd),
                borderSide: BorderSide.none,
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(kRadiusMd),
                borderSide: BorderSide(
                  color: ivorianOrange.withValues(alpha: .8),
                  width: 1.5,
                ),
              ),
            ),
          ),
          const SizedBox(height: 18),
          const _FieldLabel('Téléphone'),
          const SizedBox(height: 8),
          TextField(
            controller: _phone,
            keyboardType: TextInputType.phone,
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => _save(),
            decoration: InputDecoration(
              hintText: '+225 XX XX XX XX XX',
              prefixIcon: const Icon(Icons.phone_outlined),
              filled: true,
              fillColor: cs.surfaceContainerHighest.withValues(alpha: .4),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(kRadiusMd),
                borderSide: BorderSide.none,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(kRadiusMd),
                borderSide: BorderSide.none,
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(kRadiusMd),
                borderSide: BorderSide(
                  color: ivorianOrange.withValues(alpha: .8),
                  width: 1.5,
                ),
              ),
            ),
          ),
          const SizedBox(height: 32),
          IvorianButton(
            label: _busy ? 'Enregistrement…' : 'Enregistrer les modifications',
            icon: Icons.save_rounded,
            onPressed: _busy ? null : _save,
            useGradient: true,
            height: 52,
          ),
        ],
      ),
    );
  }
}

class _FieldLabel extends StatelessWidget {
  const _FieldLabel(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: TextStyle(
        fontSize: 13,
        fontWeight: FontWeight.w700,
        color: Theme.of(context).colorScheme.onSurface.withValues(alpha: .7),
        letterSpacing: .2,
      ),
    );
  }
}
