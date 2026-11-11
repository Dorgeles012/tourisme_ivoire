import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../widgets/common.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({required this.api, required this.onThemeChanged, required this.onAccessibilityChanged, super.key});
  final ApiService api;
  final ValueChanged<String> onThemeChanged;
  final ValueChanged<Map<String, dynamic>> onAccessibilityChanged;

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  late Future<Map<String, dynamic>> _settings;
  String _theme = 'systeme';
  bool _busy = false;
  bool _highContrast = false;
  bool _largeText = false;

  @override
  void initState() {
    super.initState();
    _settings = widget.api.getSettings();
  }

  Future<void> _save(Map<String, dynamic> change) async {
    setState(() => _busy = true);
    try {
      final saved = await widget.api.updateSettings(change);
      if (!mounted) return;
      setState(() {
        _theme = '${saved['theme']}';
        final accessibility = saved['accessibilite_json'];
        if (accessibility is Map) {
          _highContrast = accessibility['contraste_eleve'] == true;
          _largeText = accessibility['texte_agrandi'] == true;
        }
      });
      widget.onThemeChanged(_theme);
      widget.onAccessibilityChanged({'contraste_eleve': _highContrast, 'texte_agrandi': _largeText});
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Paramètres enregistrés.')));
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Paramètres & accessibilité')),
        body: FutureBuilder<Map<String, dynamic>>(
          future: _settings,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
            if (snapshot.hasError) return ErrorView(message: readableError(snapshot.error!), onRetry: () => setState(() => _settings = widget.api.getSettings()));
            final settings = snapshot.data!;
            if (_theme == 'systeme') _theme = '${settings['theme']}';
            final accessibility = settings['accessibilite_json'];
            if (accessibility is Map) {
              _highContrast = accessibility['contraste_eleve'] == true;
              _largeText = accessibility['texte_agrandi'] == true;
            }
            return ListView(
              padding: const EdgeInsets.all(18),
              children: [
                Text('Apparence', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
                const SizedBox(height: 8),
                DropdownButtonFormField<String>(
                  initialValue: _theme,
                  decoration: const InputDecoration(labelText: 'Thème'),
                  items: const [
                    DropdownMenuItem(value: 'systeme', child: Text('Suivre le système')),
                    DropdownMenuItem(value: 'clair', child: Text('Clair')),
                    DropdownMenuItem(value: 'sombre', child: Text('Sombre')),
                  ],
                  onChanged: _busy ? null : (value) { if (value != null) _save({'theme': value}); },
                ),
                const SizedBox(height: 18),
                Text('Accessibilité', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: const Text('Contraste renforcé'),
                  value: _highContrast,
                  onChanged: _busy ? null : (value) => _save({'accessibilite': {'contraste_eleve': value, 'texte_agrandi': _largeText}}),
                ),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: const Text('Texte agrandi'),
                  value: _largeText,
                  onChanged: _busy ? null : (value) => _save({'accessibilite': {'contraste_eleve': _highContrast, 'texte_agrandi': value}}),
                ),
                const SizedBox(height: 12),
                FeatureCard(
                  icon: Icons.download_for_offline_outlined,
                  title: 'Bibliothèque hors ligne',
                  subtitle: 'Enregistrer et consulter les destinations déjà publiées',
                  onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => OfflineLibraryScreen(api: widget.api))),
                ),
                const Card(child: ListTile(leading: Icon(Icons.notifications_outlined), title: Text('Notifications push'), subtitle: Text('Les notifications dans l’application sont prises en charge. Les notifications push nécessitent un fournisseur externe non configuré.'))),
                const Card(child: ListTile(leading: Icon(Icons.translate_outlined), title: Text('Traduction vocale'), subtitle: Text('La reconnaissance et la traduction vocales nécessitent un fournisseur de traduction et de transcription configuré.'))),
                const Card(child: ListTile(leading: Icon(Icons.live_tv_outlined), title: Text('Diffusion en direct'), subtitle: Text('Aucun fournisseur de streaming n’est configuré; la diffusion n’est pas présentée comme disponible.'))),
              ],
            );
          },
        ),
      );
}

class OfflineLibraryScreen extends StatefulWidget {
  const OfflineLibraryScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<OfflineLibraryScreen> createState() => _OfflineLibraryScreenState();
}

class _OfflineLibraryScreenState extends State<OfflineLibraryScreen> {
  bool _busy = false;
  List<Destination> _saved = const [];

  @override
  void initState() {
    super.initState();
    _loadSaved();
  }

  Future<void> _loadSaved() async {
    final prefs = await SharedPreferences.getInstance();
    final values = prefs.getStringList('offline_destinations') ?? [];
    if (!mounted) return;
    setState(() {
      _saved = values.map((value) => Destination.fromJson(jsonDecode(value) as Map<String, dynamic>)).toList();
    });
  }

  Future<void> _download() async {
    setState(() => _busy = true);
    try {
      final destinations = <Destination>[];
      for (var page = 1; page <= 20; page++) {
        final batch = await widget.api.fetchDestinations(page: page, perPage: 50);
        destinations.addAll(batch);
        if (batch.length < 50) break;
      }
      final prefs = await SharedPreferences.getInstance();
      final rows = destinations.map((item) => jsonEncode({
            'id': item.id,
            'nom': item.name,
            'description': item.description,
            'localite': item.location,
            'categorie': item.category,
            'slug': item.slug,
            'image_principale': item.imageUrl,
            'histoire': item.story,
            'adresse': item.address,
            'latitude': item.latitude,
            'longitude': item.longitude,
          })).toList();
      await prefs.setStringList('offline_destinations', rows);
      await _loadSaved();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('${destinations.length} destination(s) enregistrée(s) hors ligne.')));
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('offline_destinations');
    await _loadSaved();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Bibliothèque hors ligne')),
        body: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text('Le téléchargement enregistre les destinations publiées et leurs textes sur cet appareil. Les médias et les cartes restent en ligne.'),
                  const SizedBox(height: 10),
                  FilledButton.icon(onPressed: _busy ? null : _download, icon: const Icon(Icons.download), label: Text(_busy ? 'Enregistrement…' : 'Mettre à jour la bibliothèque')),
                  if (_saved.isNotEmpty) TextButton.icon(onPressed: _clear, icon: const Icon(Icons.delete_outline), label: const Text('Effacer les données hors ligne')),
                ],
              ),
            ),
            Expanded(
              child: _saved.isEmpty
                  ? const EmptyView(message: 'Aucun contenu enregistré hors ligne.', icon: Icons.download_for_offline_outlined)
                  : ListView(
                      children: _saved.map((item) => ListTile(leading: const Icon(Icons.place_outlined), title: Text(item.name), subtitle: Text(item.location))).toList(),
                    ),
            ),
          ],
        ),
      );
}
