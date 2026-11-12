import 'package:material_ui/material_ui.dart';

import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';
import 'auth_screen.dart';
import 'feature_screens.dart';
import 'passport_screen.dart';

// ═══════════════════════════════════════════════════════════════════════════════
// PROFILE SCREEN
// ═══════════════════════════════════════════════════════════════════════════════
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
    await navigateTo(context, AuthScreen(api: widget.api));
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
              if (mounted) _reload();
            } catch (error) {
              if (mounted) {
                messenger.showSnackBar(SnackBar(
                  content: Text(readableError(error)),
                  behavior: SnackBarBehavior.floating,
                ));
              }
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
      padding: const EdgeInsets.all(0),
      children: [
        // ── Header illustratif ───────────────────────────────────────────────
        Container(
          height: 220,
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [ivorianForest, ivorianForest.withValues(alpha: .75)],
            ),
          ),
          child: Stack(
            children: [
              Positioned(
                top: -20,
                right: -30,
                child: Container(
                  width: 160,
                  height: 160,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white.withValues(alpha: .06),
                  ),
                ),
              ),
              Positioned(
                bottom: -40,
                left: -20,
                child: Container(
                  width: 200,
                  height: 200,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white.withValues(alpha: .04),
                  ),
                ),
              ),
              Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 72,
                      height: 72,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.white.withValues(alpha: .15),
                        border: Border.all(
                            color: Colors.white.withValues(alpha: .3),
                            width: 2),
                      ),
                      child: const Icon(Icons.person_outline,
                          size: 36, color: Colors.white),
                    ),
                    const SizedBox(height: 14),
                    const Text(
                      'Mon espace voyageur',
                      style: TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.w800),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Connectez-vous pour accéder à votre profil',
                      style: TextStyle(
                          color: Colors.white.withValues(alpha: .75),
                          fontSize: 13),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),

        // ── CTA connexion ────────────────────────────────────────────────────
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 24, 20, 8),
          child: IvorianButton(
            label: 'Connexion / Inscription',
            icon: Icons.login_outlined,
            onPressed: onAuthenticate,
            useGradient: true,
            height: 50,
          ),
        ),

        // ── Feature cards ────────────────────────────────────────────────────
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Column(
            children: [
              FeatureCard(
                icon: Icons.favorite_border,
                title: 'Favoris',
                subtitle: 'Connectez-vous pour consulter vos lieux enregistrés',
                onTap: onAuthenticate,
              ),
              FeatureCard(
                icon: Icons.badge_outlined,
                title: 'Passeport numérique',
                subtitle: 'Visites, progression et récompenses',
                onTap: onAuthenticate,
              ),
              FeatureCard(
                icon: Icons.settings_outlined,
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

        // ── Statut discret ───────────────────────────────────────────────────
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
          child: Text(
            'État : $errorMessage',
            style: TextStyle(
                fontSize: 11,
                color: cs.onSurface.withValues(alpha: .35)),
          ),
        ),
      ],
    );
  }
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

  String get _initial => ('${user['nom']}').isNotEmpty
      ? '${user['nom']}'[0].toUpperCase()
      : '?';

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return ListView(
      padding: const EdgeInsets.all(0),
      children: [
        // ── Header orange avec avatar ────────────────────────────────────────
        Container(
          padding: const EdgeInsets.fromLTRB(24, 48, 24, 28),
          decoration: const BoxDecoration(
            gradient: kAppBarGradient,
          ),
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              // Cercles décoratifs
              Positioned(
                top: -10,
                right: -20,
                child: Container(
                  width: 120,
                  height: 120,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white.withValues(alpha: .07),
                  ),
                ),
              ),
              Row(
                children: [
                  // Avatar
                  Container(
                    width: 70,
                    height: 70,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white.withValues(alpha: .2),
                      border: Border.all(
                          color: Colors.white.withValues(alpha: .5), width: 2.5),
                    ),
                    child: Center(
                      child: Text(
                        _initial,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 28,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  // Infos
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${user['prenom'] ?? ''} ${user['nom']}'.trim(),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 20,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${user['email']}',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: .8),
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  ),
                  // Bouton édition
                  IconButton(
                    tooltip: 'Modifier le profil',
                    onPressed: onEditProfile,
                    style: IconButton.styleFrom(
                      backgroundColor: Colors.white.withValues(alpha: .15),
                    ),
                    icon: const Icon(Icons.edit_outlined, color: Colors.white),
                  ),
                ],
              ),
            ],
          ),
        ),

        // ── Stats passeport ──────────────────────────────────────────────────
        Container(
          margin: const EdgeInsets.fromLTRB(16, 16, 16, 4),
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            color: cs.surfaceContainerHighest.withValues(alpha: .5),
            borderRadius: BorderRadius.circular(kRadiusMd),
            boxShadow: kShadowSoft,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              _StatItem(
                value: '${user['visites'] ?? 0}',
                label: 'Visites',
                icon: Icons.place_outlined,
              ),
              _Divider(),
              _StatItem(
                value: '${user['favoris'] ?? 0}',
                label: 'Favoris',
                icon: Icons.favorite_border,
              ),
              _Divider(),
              _StatItem(
                value: '${user['points'] ?? 0}',
                label: 'Points',
                icon: Icons.stars_outlined,
              ),
            ],
          ),
        ),

        // ── Feature cards ────────────────────────────────────────────────────
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
          child: Column(
            children: [
              FeatureCard(
                icon: Icons.badge_outlined,
                title: 'Passeport numérique',
                subtitle: 'Visites, progression et récompenses',
                onTap: () => navigateTo(context, PassportScreen(api: api)),
              ),
              FeatureCard(
                icon: Icons.favorite_border,
                title: 'Mes favoris',
                subtitle: 'Retrouvez vos destinations enregistrées',
                onTap: () => navigateTo(context, FavoritesScreen(api: api)),
              ),
              FeatureCard(
                icon: Icons.notifications_none_outlined,
                title: 'Notifications',
                subtitle: 'Consultez et marquez les notifications comme lues',
                onTap: () => navigateTo(context, NotificationsScreen(api: api)),
              ),
              FeatureCard(
                icon: Icons.receipt_long_outlined,
                title: 'Réservations',
                subtitle: 'Suivez les demandes envoyées',
                onTap: () => navigateTo(context, ReservationHistoryScreen(api: api)),
              ),
              FeatureCard(
                icon: Icons.quiz_outlined,
                title: 'Résultats des quiz',
                subtitle: 'Retrouvez votre historique',
                onTap: () => navigateTo(context, QuizResultsScreen(api: api)),
              ),
              FeatureCard(
                icon: Icons.settings_outlined,
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

        // ── Déconnexion ──────────────────────────────────────────────────────
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
          child: OutlinedButton.icon(
            onPressed: onLogout,
            icon: const Icon(Icons.logout),
            label: const Text('Se déconnecter'),
            style: OutlinedButton.styleFrom(
              foregroundColor: ivorianTerracotta,
              side: BorderSide(color: ivorianTerracotta.withValues(alpha: .4)),
              minimumSize: const Size(double.infinity, 46),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(kRadiusMd)),
            ),
          ),
        ),
      ],
    );
  }
}

// ─── Widgets internes ─────────────────────────────────────────────────────────
class _StatItem extends StatelessWidget {
  const _StatItem(
      {required this.value, required this.label, required this.icon});
  final String value;
  final String label;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 18, color: ivorianOrange),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(
              fontSize: 20, fontWeight: FontWeight.w900, color: ivorianOrange),
        ),
        Text(
          label,
          style: TextStyle(
              fontSize: 11,
              color:
                  Theme.of(context).colorScheme.onSurface.withValues(alpha: .55)),
        ),
      ],
    );
  }
}

class _Divider extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
        height: 36,
        width: 1,
        color: Theme.of(context).colorScheme.outlineVariant.withValues(alpha: .4));
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// EDIT PROFILE SCREEN
// ═══════════════════════════════════════════════════════════════════════════════
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
    _firstName =
        TextEditingController(text: '${widget.user['prenom'] ?? ''}');
    _phone =
        TextEditingController(text: '${widget.user['telephone'] ?? ''}');
  }

  @override
  void dispose() {
    _name.dispose();
    _firstName.dispose();
    _phone.dispose();
    super.dispose();
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
                borderRadius: BorderRadius.circular(kRadiusMd)),
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
                    borderRadius: BorderRadius.circular(kRadiusMd)),
              ),
              child: Text(_busy ? 'Enregistrement…' : 'Enregistrer'),
            ),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          TextField(
            controller: _name,
            textInputAction: TextInputAction.next,
            decoration: InputDecoration(
              labelText: 'Nom',
              prefixIcon: const Icon(Icons.person_outline),
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(kRadiusMd)),
            ),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _firstName,
            textInputAction: TextInputAction.next,
            decoration: InputDecoration(
              labelText: 'Prénom',
              prefixIcon: const Icon(Icons.badge_outlined),
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(kRadiusMd)),
            ),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _phone,
            keyboardType: TextInputType.phone,
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => _save(),
            decoration: InputDecoration(
              labelText: 'Téléphone',
              prefixIcon: const Icon(Icons.phone_outlined),
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(kRadiusMd)),
            ),
          ),
          const SizedBox(height: 28),
          IvorianButton(
            label: _busy ? 'Enregistrement…' : 'Enregistrer les modifications',
            icon: Icons.save_outlined,
            onPressed: _busy ? null : _save,
            useGradient: true,
            height: 50,
          ),
        ],
      ),
    );
  }
}
