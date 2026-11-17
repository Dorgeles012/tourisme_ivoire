import 'package:material_ui/material_ui.dart';
import 'package:file_picker/file_picker.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/app_alert.dart';
import '../widgets/common.dart';
import 'auth_screen.dart';
import 'feature_screens.dart';
import 'passport_screen.dart';

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
        await AppAlert.runWithLoading(
          context,
          action: () async {
            final user = await widget.api.currentUser();
            if (user['role'] == 'Sadmin') {
              final code = await widget.api.createAdminHandoff();
              final opened = await launchUrl(
                widget.api.adminHandoffUri(code),
                mode: LaunchMode.externalApplication,
              );
              if (!opened) throw StateError('Impossible to open admin portal.');
            }
          },
        );
      } catch (error) {
        if (mounted) {
          await AppAlert.error(context, readableError(error));
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
            try {
              await AppAlert.runWithLoading(
                context,
                action: widget.api.logout,
              );
            } catch (error) {
              if (mounted) {
                await AppAlert.error(context, readableError(error));
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
        SizedBox(
          height: 400,
          child: Stack(
            fit: StackFit.expand,
            children: [
              Image.asset('lib/images/Profile.avif', fit: BoxFit.cover),
              DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.black.withValues(alpha: .10),
                      Colors.black.withValues(alpha: .55),
                      Colors.black.withValues(alpha: .82),
                    ],
                    stops: const [0, .55, 1],
                  ),
                ),
              ),
              Positioned(
                top: -60,
                right: -60,
                child: _GlowBlob(
                  size: 220,
                  color: Colors.white.withValues(alpha: .10),
                ),
              ),
              Positioned(
                bottom: -80,
                left: -40,
                child: _GlowBlob(
                  size: 260,
                  color: ivorianOrange.withValues(alpha: .18),
                ),
              ),
              Positioned(
                top: 120,
                left: 40,
                child: _GlowBlob(
                  size: 60,
                  color: Colors.white.withValues(alpha: .06),
                ),
              ),
              SafeArea(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(24, 32, 24, 32),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      Container(
                        width: 72,
                        height: 72,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: Colors.white.withValues(alpha: .16),
                          border: Border.all(
                            color: Colors.white.withValues(alpha: .45),
                            width: 2,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: .25),
                              blurRadius: 24,
                              offset: const Offset(0, 10),
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.person_outline_rounded,
                          size: 36,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 20),
                      const Text(
                        'Mon espace voyageur',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 30,
                          fontWeight: FontWeight.w900,
                          letterSpacing: -0.8,
                          height: 1.1,
                        ),
                      ),
                      const SizedBox(height: 10),
                      Text(
                        'Connectez-vous pour accéder à votre profil',
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: .82),
                          fontSize: 14.5,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),

        // ---- Bloc CTA pleine largeur (page, pas popup) ----
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 20, 16, 8),
          child: _ModernFeatureCard(
            icon: Icons.explore_rounded,
            title: 'Prêt à explorer ?',
            subtitle:
                'Retrouvez vos favoris, votre passeport et votre progression.',
            onTap: onAuthenticate,
            accent: ivorianOrange,
            trailing: IvorianButton(
              label: 'Connexion / Inscription',
              icon: Icons.login_rounded,
              onPressed: onAuthenticate,
              useGradient: true,
              height: 50,
            ),
          ),
        ),

        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
          child: Column(
            children: [
              _ModernFeatureCard(
                icon: Icons.favorite_rounded,
                title: 'Favoris',
                subtitle: 'Connectez-vous pour consulter vos lieux enregistrés',
                onTap: onAuthenticate,
                accent: ivorianTerracotta,
              ),
              _ModernFeatureCard(
                icon: Icons.badge_rounded,
                title: 'Passeport numérique',
                subtitle: 'Visites, progression et récompenses',
                onTap: onAuthenticate,
                accent: ivorianOrange,
              ),
              _ModernFeatureCard(
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
                accent: Colors.blueGrey,
              ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
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
}

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
    final initials = _initials(user);
    final hasAvatar = avatarUrl is String && avatarUrl.isNotEmpty;

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
                  child: _GlowBlob(
                    size: 180,
                    color: Colors.white.withValues(alpha: .08),
                  ),
                ),
                Positioned(
                  bottom: -60,
                  left: -40,
                  child: _GlowBlob(
                    size: 160,
                    color: Colors.white.withValues(alpha: .05),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(24, 24, 24, 44),
                  child: Row(
                    children: [
                      Container(
                        width: 84,
                        height: 84,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: Colors.white.withValues(alpha: .18),
                          border: Border.all(
                            color: Colors.white.withValues(alpha: .6),
                            width: 2.5,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: .22),
                              blurRadius: 20,
                              offset: const Offset(0, 8),
                            ),
                          ],
                        ),
                        child: ClipOval(
                          child: hasAvatar
                              ? Image.network(
                                  api.mediaUri(avatarUrl).toString(),
                                  width: 84,
                                  height: 84,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, _, _) =>
                                      _InitialsAvatar(initials: initials),
                                )
                              : _InitialsAvatar(initials: initials),
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
                                fontSize: 22,
                                fontWeight: FontWeight.w900,
                                letterSpacing: -0.4,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 6),
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
                            borderRadius: BorderRadius.circular(14),
                          ),
                          minimumSize: const Size(44, 44),
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
          offset: const Offset(0, -28),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 20),
              decoration: BoxDecoration(
                color: cs.surface,
                borderRadius: BorderRadius.circular(kRadiusLg),
                boxShadow: kShadowSoft,
                border: Border.all(
                  color: cs.outlineVariant.withValues(alpha: .35),
                  width: .6,
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
                  const _GradientDivider(),
                  _StatItem(
                    value: '${user['favoris'] ?? 0}',
                    label: 'Favoris',
                    icon: Icons.favorite_rounded,
                  ),
                  const _GradientDivider(),
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
              _ModernFeatureCard(
                icon: Icons.badge_rounded,
                title: 'Passeport numérique',
                subtitle: 'Visites, progression et récompenses',
                onTap: () => navigateTo(context, PassportScreen(api: api)),
                accent: ivorianOrange,
              ),
              _ModernFeatureCard(
                icon: Icons.favorite_rounded,
                title: 'Mes favoris',
                subtitle: 'Retrouvez vos destinations enregistrées',
                onTap: () => navigateTo(context, FavoritesScreen(api: api)),
                accent: ivorianTerracotta,
              ),
              _ModernFeatureCard(
                icon: Icons.notifications_rounded,
                title: 'Notifications',
                subtitle: 'Consultez et marquez les notifications comme lues',
                onTap: () => navigateTo(context, NotificationsScreen(api: api)),
                accent: Colors.blueAccent,
              ),
              _ModernFeatureCard(
                icon: Icons.receipt_long_rounded,
                title: 'Réservations',
                subtitle: 'Suivez les demandes envoyées',
                onTap: () =>
                    navigateTo(context, ReservationHistoryScreen(api: api)),
                accent: Colors.teal,
              ),
              _ModernFeatureCard(
                icon: Icons.quiz_rounded,
                title: 'Résultats des quiz',
                subtitle: 'Retrouvez votre historique',
                onTap: () => navigateTo(context, QuizResultsScreen(api: api)),
                accent: Colors.purpleAccent,
              ),
              _ModernFeatureCard(
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
                accent: Colors.blueGrey,
              ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
          child: OutlinedButton.icon(
            onPressed: onLogout,
            icon: const Icon(Icons.logout_rounded, size: 20),
            label: const Text('Se déconnecter'),
            style: OutlinedButton.styleFrom(
              foregroundColor: ivorianTerracotta,
              side: BorderSide(color: ivorianTerracotta.withValues(alpha: .4)),
              minimumSize: const Size(double.infinity, 54),
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

  static String _initials(Map<String, dynamic> user) {
    final first = (user['prenom'] ?? '').toString().trim();
    final last = (user['nom'] ?? '').toString().trim();
    final source = first.isNotEmpty ? first : last;
    if (source.isEmpty) return '?';
    return source.substring(0, source.length >= 2 ? 2 : 1).toUpperCase();
  }
}

class _GlowBlob extends StatelessWidget {
  const _GlowBlob({required this.size, required this.color});
  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: RadialGradient(
          colors: [color, color.withValues(alpha: 0)],
        ),
      ),
    );
  }
}

class _InitialsAvatar extends StatelessWidget {
  const _InitialsAvatar({required this.initials});
  final String initials;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 84,
      height: 84,
      alignment: Alignment.center,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [ivorianOrange, ivorianTerracotta],
        ),
      ),
      child: Text(
        initials,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 28,
          fontWeight: FontWeight.w900,
          letterSpacing: -0.5,
        ),
      ),
    );
  }
}

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
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                ivorianOrange.withValues(alpha: .18),
                ivorianOrange.withValues(alpha: .06),
              ],
            ),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, size: 18, color: ivorianOrange),
        ),
        const SizedBox(height: 10),
        Text(
          value,
          style: const TextStyle(
            fontSize: 21,
            fontWeight: FontWeight.w900,
            color: ivorianOrange,
            letterSpacing: -0.4,
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

class _GradientDivider extends StatelessWidget {
  const _GradientDivider();

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Container(
      height: 48,
      width: 1,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            cs.outlineVariant.withValues(alpha: 0),
            cs.outlineVariant.withValues(alpha: .35),
            cs.outlineVariant.withValues(alpha: 0),
          ],
        ),
      ),
    );
  }
}

class _ModernFeatureCard extends StatelessWidget {
  const _ModernFeatureCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
    required this.accent,
    this.trailing,
  });
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  final Color accent;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    final content = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    accent.withValues(alpha: .18),
                    accent.withValues(alpha: .06),
                  ],
                ),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(icon, size: 22, color: accent),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 14.5,
                      fontWeight: FontWeight.w800,
                      color: cs.onSurface,
                      letterSpacing: -0.1,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 12,
                      color: cs.onSurface.withValues(alpha: .58),
                      height: 1.35,
                    ),
                  ),
                ],
              ),
            ),
            if (trailing == null) ...[
              const SizedBox(width: 8),
              Icon(
                Icons.chevron_right_rounded,
                size: 22,
                color: cs.onSurface.withValues(alpha: .35),
              ),
            ],
          ],
        ),
        if (trailing != null) ...[
          const SizedBox(height: 16),
          trailing!,
        ],
      ],
    );

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: cs.surface,
        borderRadius: BorderRadius.circular(kRadiusLg),
        elevation: 0,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(kRadiusLg),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(kRadiusLg),
              border: Border.all(
                color: cs.outlineVariant.withValues(alpha: .35),
                width: .6,
              ),
            ),
            child: content,
          ),
        ),
      ),
    );
  }
}

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
        await AppAlert.success(context, 'Photo de profil mise à jour.');
      }
    } catch (error) {
      if (mounted) {
        await AppAlert.error(context, readableError(error));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _save() async {
    if (!await AppAlert.confirm(
      context,
      title: 'Enregistrer les modifications ?',
      message: 'Voulez-vous enregistrer les changements de votre profil ?',
    )) {
      return;
    }
    setState(() => _busy = true);
    try {
      await widget.api.updateProfile({
        'nom': _name.text,
        'prenom': _firstName.text,
        'telephone': _phone.text,
      });
      if (mounted) {
        await AppAlert.success(context, 'Profil mis à jour avec succès.');
        Navigator.of(context).pop();
      }
    } catch (error) {
      if (mounted) {
        await AppAlert.error(context, readableError(error));
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
                textStyle: const TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 13,
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
                  ivorianOrange.withValues(alpha: .14),
                  ivorianOrange.withValues(alpha: .04),
                ],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(kRadiusLg),
              border: Border.all(
                color: ivorianOrange.withValues(alpha: .15),
              ),
            ),
            child: Row(
              children: [
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white,
                    boxShadow: kShadowSoft,
                  ),
                  child: const Icon(
                    Icons.person_rounded,
                    color: ivorianOrange,
                    size: 30,
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
                          height: 1.35,
                        ),
                      ),
                      const SizedBox(height: 4),
                      TextButton.icon(
                        onPressed: _busy ? null : _changeAvatar,
                        icon: const Icon(Icons.add_a_photo_outlined, size: 18),
                        label: const Text('Modifier la photo'),
                        style: TextButton.styleFrom(
                          foregroundColor: ivorianOrange,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 4,
                            vertical: 4,
                          ),
                          textStyle: const TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 13,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 28),
          const _FieldLabel('Nom'),
          const SizedBox(height: 8),
          _ProfileTextField(
            controller: _name,
            hintText: 'Votre nom',
            icon: Icons.person_outline_rounded,
            textInputAction: TextInputAction.next,
          ),
          const SizedBox(height: 18),
          const _FieldLabel('Prénom'),
          const SizedBox(height: 8),
          _ProfileTextField(
            controller: _firstName,
            hintText: 'Votre prénom',
            icon: Icons.badge_outlined,
            textInputAction: TextInputAction.next,
          ),
          const SizedBox(height: 18),
          const _FieldLabel('Téléphone'),
          const SizedBox(height: 8),
          _ProfileTextField(
            controller: _phone,
            hintText: '+225 XX XX XX XX XX',
            icon: Icons.phone_outlined,
            keyboardType: TextInputType.phone,
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => _save(),
          ),
          const SizedBox(height: 32),
          IvorianButton(
            label: _busy ? 'Enregistrement…' : 'Enregistrer les modifications',
            icon: Icons.save_rounded,
            onPressed: _busy ? null : _save,
            useGradient: true,
            height: 54,
          ),
        ],
      ),
    );
  }
}

class _ProfileTextField extends StatelessWidget {
  const _ProfileTextField({
    required this.controller,
    required this.hintText,
    required this.icon,
    this.keyboardType,
    this.textInputAction,
    this.onSubmitted,
  });

  final TextEditingController controller;
  final String hintText;
  final IconData icon;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;
  final ValueChanged<String>? onSubmitted;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    final borderRadius = BorderRadius.circular(kRadiusMd);

    OutlineInputBorder border({Color? color, double width = 1.5}) =>
        OutlineInputBorder(
          borderRadius: borderRadius,
          borderSide: color == null
              ? BorderSide.none
              : BorderSide(color: color, width: width),
        );

    return TextField(
      controller: controller,
      keyboardType: keyboardType,
      textInputAction: textInputAction,
      onSubmitted: onSubmitted,
      decoration: InputDecoration(
        hintText: hintText,
        prefixIcon: Icon(icon, size: 20),
        filled: true,
        fillColor: cs.surfaceContainerHighest.withValues(alpha: .4),
        border: border(),
        enabledBorder: border(),
        focusedBorder: border(
          color: ivorianOrange.withValues(alpha: .8),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 16,
        ),
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