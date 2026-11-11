import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../widgets/common.dart';
import 'auth_screen.dart';
import 'feature_screens.dart';
import 'passport_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({required this.api, required this.onThemeChanged, required this.onAccessibilityChanged, super.key});
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
    await Navigator.of(context).push(MaterialPageRoute<bool>(builder: (_) => AuthScreen(api: widget.api)));
    if (mounted) _reload();
  }

  Future<void> _editProfile(Map<String, dynamic> user) async {
    final navigator = Navigator.of(context);
    await navigator.push<void>(MaterialPageRoute<void>(builder: (_) => EditProfileScreen(api: widget.api, user: user)));
    if (!mounted) return;
    _reload();
  }

  @override
  Widget build(BuildContext context) => FutureBuilder<Map<String, dynamic>>(
        future: _user,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
          if (snapshot.hasError) {
            return ListView(
              padding: const EdgeInsets.all(22),
              children: [
                const PageHeading(title: 'Votre espace voyageur', subtitle: 'Connectez-vous pour synchroniser votre passeport et vos favoris.'),
                const SizedBox(height: 18),
                FilledButton.icon(onPressed: _authenticate, icon: const Icon(Icons.login), label: const Text('Connexion / inscription')),
                const SizedBox(height: 12),
                FeatureCard(icon: Icons.favorite_border, title: 'Favoris', subtitle: 'Connectez-vous pour consulter vos lieux enregistrés', onTap: _authenticate),
                FeatureCard(icon: Icons.settings_outlined, title: 'Paramètres', subtitle: 'Apparence et accessibilité', onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => SettingsScreen(api: widget.api, onThemeChanged: widget.onThemeChanged, onAccessibilityChanged: widget.onAccessibilityChanged)))),
                const SizedBox(height: 14),
                Text('État du compte : ${readableError(snapshot.error!)}', style: Theme.of(context).textTheme.bodySmall),
              ],
            );
          }
          final user = snapshot.data!;
          final messenger = ScaffoldMessenger.of(context);
          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              const PageHeading(title: 'Mon profil'),
              Card(
                child: ListTile(
                  leading: CircleAvatar(child: Text('${user['nom']}'.isEmpty ? '?' : '${user['nom']}'.substring(0, 1).toUpperCase())),
                  title: Text('${user['nom']} ${user['prenom'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w800)),
                  subtitle: Text('${user['email']}'),
                  trailing: IconButton(tooltip: 'Modifier le profil', onPressed: () => _editProfile(user), icon: const Icon(Icons.edit_outlined)),
                ),
              ),
              const SizedBox(height: 12),
              FeatureCard(icon: Icons.badge_outlined, title: 'Passeport numérique', subtitle: 'Visites, progression et récompenses', onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => PassportScreen(api: widget.api)))),
              FeatureCard(icon: Icons.favorite_border, title: 'Mes favoris', subtitle: 'Retrouvez vos destinations enregistrées', onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => FavoritesScreen(api: widget.api)))),
              FeatureCard(icon: Icons.notifications_none, title: 'Notifications', subtitle: 'Consultez et marquez les notifications comme lues', onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => NotificationsScreen(api: widget.api)))),
              FeatureCard(icon: Icons.receipt_long_outlined, title: 'Réservations', subtitle: 'Suivez les demandes envoyées', onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => ReservationHistoryScreen(api: widget.api)))),
              FeatureCard(icon: Icons.quiz_outlined, title: 'Résultats des quiz', subtitle: 'Retrouvez votre historique', onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => QuizResultsScreen(api: widget.api)))),
              FeatureCard(icon: Icons.settings_outlined, title: 'Paramètres & accessibilité', subtitle: 'Thème et préférences', onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => SettingsScreen(api: widget.api, onThemeChanged: widget.onThemeChanged, onAccessibilityChanged: widget.onAccessibilityChanged)))),
              const SizedBox(height: 10),
              OutlinedButton.icon(
                onPressed: () async {
                  try {
                    await widget.api.logout();
                    if (mounted) _reload();
                  } catch (error) {
                    if (mounted) messenger.showSnackBar(SnackBar(content: Text(readableError(error))));
                  }
                },
                icon: const Icon(Icons.logout),
                label: const Text('Se déconnecter'),
              ),
            ],
          );
        },
      );
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

  Future<void> _save() async {
    setState(() => _busy = true);
    try {
      await widget.api.updateProfile({'nom': _name.text, 'prenom': _firstName.text, 'telephone': _phone.text});
      if (mounted) Navigator.of(context).pop();
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Modifier mon profil')),
        body: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            TextField(controller: _name, decoration: const InputDecoration(labelText: 'Nom')),
            const SizedBox(height: 12),
            TextField(controller: _firstName, decoration: const InputDecoration(labelText: 'Prénom')),
            const SizedBox(height: 12),
            TextField(controller: _phone, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Téléphone')),
            const SizedBox(height: 18),
            FilledButton(onPressed: _busy ? null : _save, child: Text(_busy ? 'Enregistrement…' : 'Enregistrer')),
          ],
        ),
      );
}
