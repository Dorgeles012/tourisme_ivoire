import 'dart:convert';

import 'package:material_ui/material_ui.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';

// ═══════════════════════════════════════════════════════════════════════════════
// SETTINGS SCREEN
// ═══════════════════════════════════════════════════════════════════════════════
class SettingsScreen extends StatefulWidget {
  const SettingsScreen({
    required this.api,
    required this.onThemeChanged,
    required this.onAccessibilityChanged,
    super.key,
  });
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
      widget.onAccessibilityChanged({
        'contraste_eleve': _highContrast,
        'texte_agrandi': _largeText,
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Paramètres enregistrés.'),
          behavior: SnackBarBehavior.floating,
          backgroundColor: const Color(0xFF2E7D32),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(kRadiusMd)),
        ),
      );
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
        title: const Text('Paramètres & accessibilité'),
        centerTitle: false,
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _settings,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: readableError(snapshot.error!),
              onRetry: () =>
                  setState(() => _settings = widget.api.getSettings()),
            );
          }
          final settings = snapshot.data!;
          if (_theme == 'systeme') _theme = '${settings['theme']}';
          final accessibility = settings['accessibilite_json'];
          if (accessibility is Map) {
            _highContrast = accessibility['contraste_eleve'] == true;
            _largeText = accessibility['texte_agrandi'] == true;
          }

          return ListView(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
            children: [
              // ── Section Apparence ────────────────────────────────────────
              _SectionLabel(label: 'Apparence', icon: Icons.palette_outlined),
              const SizedBox(height: 10),
              _SettingsCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 14, 16, 8),
                      child: Text(
                        'Thème de l\'application',
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          color: Theme.of(context)
                              .colorScheme
                              .onSurface
                              .withValues(alpha: .7),
                          fontSize: 13,
                        ),
                      ),
                    ),
                    // SegmentedButton thème
                    Padding(
                      padding: const EdgeInsets.fromLTRB(12, 0, 12, 14),
                      child: SegmentedButton<String>(
                        segments: const [
                          ButtonSegment(
                              value: 'systeme',
                              label: Text('Système'),
                              icon: Icon(Icons.brightness_auto_outlined)),
                          ButtonSegment(
                              value: 'clair',
                              label: Text('Clair'),
                              icon: Icon(Icons.light_mode_outlined)),
                          ButtonSegment(
                              value: 'sombre',
                              label: Text('Sombre'),
                              icon: Icon(Icons.dark_mode_outlined)),
                        ],
                        selected: {_theme},
                        onSelectionChanged: _busy
                            ? null
                            : (selection) {
                                if (selection.isNotEmpty) {
                                  _save({'theme': selection.first});
                                }
                              },
                        style: SegmentedButton.styleFrom(
                          selectedBackgroundColor:
                              ivorianOrange.withValues(alpha: .15),
                          selectedForegroundColor: ivorianOrange,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // ── Section Accessibilité ────────────────────────────────────
              _SectionLabel(
                  label: 'Accessibilité',
                  icon: Icons.accessibility_new_outlined),
              const SizedBox(height: 10),
              _SettingsCard(
                child: Column(
                  children: [
                    SwitchListTile(
                      activeColor: ivorianOrange,
                      title: const Text('Contraste renforcé'),
                      subtitle: const Text(
                        'Améliore la lisibilité sur fond clair ou sombre',
                        style: TextStyle(fontSize: 12),
                      ),
                      value: _highContrast,
                      onChanged: _busy
                          ? null
                          : (value) => _save({
                                'accessibilite': {
                                  'contraste_eleve': value,
                                  'texte_agrandi': _largeText,
                                }
                              }),
                    ),
                    Divider(height: 1, indent: 16, endIndent: 16),
                    SwitchListTile(
                      activeColor: ivorianOrange,
                      title: const Text('Texte agrandi'),
                      subtitle: const Text(
                        'Augmente la taille de la police dans l\'app',
                        style: TextStyle(fontSize: 12),
                      ),
                      value: _largeText,
                      onChanged: _busy
                          ? null
                          : (value) => _save({
                                'accessibilite': {
                                  'contraste_eleve': _highContrast,
                                  'texte_agrandi': value,
                                }
                              }),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // ── Section Contenu ──────────────────────────────────────────
              _SectionLabel(
                  label: 'Contenu & données', icon: Icons.storage_outlined),
              const SizedBox(height: 10),
              FeatureCard(
                icon: Icons.download_for_offline_outlined,
                title: 'Bibliothèque hors ligne',
                subtitle:
                    'Enregistrer et consulter les destinations déjà publiées',
                onTap: () =>
                    navigateTo(context, OfflineLibraryScreen(api: widget.api)),
              ),
              const SizedBox(height: 20),

              // ── Section Fonctionnalités non configurées ──────────────────
              _SectionLabel(
                  label: 'Fonctionnalités',
                  icon: Icons.extension_outlined),
              const SizedBox(height: 10),
              _SettingsCard(
                child: Column(
                  children: [
                    ListTile(
                      leading: Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: ivorianForest.withValues(alpha: .1),
                          borderRadius: BorderRadius.circular(kRadiusSm),
                        ),
                        child: Icon(Icons.notifications_outlined,
                            size: 18, color: ivorianForest),
                      ),
                      title: const Text('Notifications push'),
                      subtitle: const Text(
                        'Les notifications dans l\'app sont prises en charge. Les push nécessitent un fournisseur externe non configuré.',
                        style: TextStyle(fontSize: 12),
                      ),
                    ),
                    Divider(height: 1, indent: 16, endIndent: 16),
                    ListTile(
                      leading: Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: ivorianOcre.withValues(alpha: .1),
                          borderRadius: BorderRadius.circular(kRadiusSm),
                        ),
                        child: Icon(Icons.translate_outlined,
                            size: 18, color: ivorianOcre),
                      ),
                      title: const Text('Traduction vocale'),
                      subtitle: const Text(
                        'La reconnaissance vocale nécessite un fournisseur de traduction configuré.',
                        style: TextStyle(fontSize: 12),
                      ),
                    ),
                    Divider(height: 1, indent: 16, endIndent: 16),
                    ListTile(
                      leading: Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: ivorianTerracotta.withValues(alpha: .1),
                          borderRadius: BorderRadius.circular(kRadiusSm),
                        ),
                        child: Icon(Icons.live_tv_outlined,
                            size: 18, color: ivorianTerracotta),
                      ),
                      title: const Text('Diffusion en direct'),
                      subtitle: const Text(
                        'Aucun fournisseur de streaming configuré ; la diffusion n\'est pas disponible.',
                        style: TextStyle(fontSize: 12),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 32),
            ],
          );
        },
      ),
    );
  }
}

// ─── Widgets internes ─────────────────────────────────────────────────────────
class _SectionLabel extends StatelessWidget {
  const _SectionLabel({required this.label, required this.icon});
  final String label;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 16, color: ivorianOrange),
        const SizedBox(width: 6),
        Text(
          label.toUpperCase(),
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w700,
            letterSpacing: 1.3,
            color: ivorianOrange,
          ),
        ),
      ],
    );
  }
}

class _SettingsCard extends StatelessWidget {
  const _SettingsCard({required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(kRadiusMd),
        boxShadow: kShadowSoft,
        border: Border.all(
          color: Theme.of(context)
              .colorScheme
              .outlineVariant
              .withValues(alpha: .3),
        ),
      ),
      child: child,
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// OFFLINE LIBRARY SCREEN
// ═══════════════════════════════════════════════════════════════════════════════
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
      _saved = values
          .map((v) =>
              Destination.fromJson(jsonDecode(v) as Map<String, dynamic>))
          .toList();
    });
  }

  Future<void> _download() async {
    setState(() => _busy = true);
    try {
      final destinations = <Destination>[];
      for (var page = 1; page <= 20; page++) {
        final batch =
            await widget.api.fetchDestinations(page: page, perPage: 50);
        destinations.addAll(batch);
        if (batch.length < 50) break;
      }
      final prefs = await SharedPreferences.getInstance();
      final rows = destinations
          .map((d) => jsonEncode({
                'id': d.id,
                'nom': d.name,
                'description': d.description,
                'localite': d.location,
                'categorie': d.category,
                'slug': d.slug,
                'image_principale': d.imageUrl,
                'histoire': d.story,
                'adresse': d.address,
                'latitude': d.latitude,
                'longitude': d.longitude,
              }))
          .toList();
      await prefs.setStringList('offline_destinations', rows);
      await _loadSaved();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
                '${destinations.length} destination(s) enregistrée(s) hors ligne.'),
            behavior: SnackBarBehavior.floating,
            backgroundColor: const Color(0xFF2E7D32),
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(kRadiusMd)),
          ),
        );
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

  Future<void> _clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('offline_destinations');
    await _loadSaved();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Bibliothèque hors ligne')),
      body: Column(
        children: [
          // ── Info + boutons ───────────────────────────────────────────────
          Container(
            margin: const EdgeInsets.all(16),
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: ivorianForest.withValues(alpha: .07),
              borderRadius: BorderRadius.circular(kRadiusMd),
              border: Border.all(
                  color: ivorianForest.withValues(alpha: .2)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Icon(Icons.info_outline,
                        size: 16,
                        color: ivorianForest.withValues(alpha: .8)),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Le téléchargement enregistre les destinations et leurs textes sur cet appareil. Les médias et cartes restent en ligne.',
                        style: TextStyle(
                            fontSize: 13,
                            color: Theme.of(context)
                                .colorScheme
                                .onSurface
                                .withValues(alpha: .7)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                FilledButton.icon(
                  onPressed: _busy ? null : _download,
                  icon: _busy
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(
                              color: Colors.white, strokeWidth: 2),
                        )
                      : const Icon(Icons.download),
                  label: Text(_busy
                      ? 'Enregistrement…'
                      : 'Mettre à jour la bibliothèque'),
                  style: FilledButton.styleFrom(
                    backgroundColor: ivorianForest,
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(kRadiusMd)),
                  ),
                ),
                if (_saved.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  TextButton.icon(
                    onPressed: _clear,
                    icon: const Icon(Icons.delete_outline, color: ivorianTerracotta),
                    label: const Text(
                      'Effacer les données hors ligne',
                      style: TextStyle(color: ivorianTerracotta),
                    ),
                  ),
                ],
              ],
            ),
          ),

          // ── Compteur ────────────────────────────────────────────────────
          if (_saved.isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                children: [
                  const Icon(Icons.cloud_done_outlined,
                      size: 16, color: ivorianForest),
                  const SizedBox(width: 6),
                  Text(
                    '${_saved.length} destination(s) disponibles hors ligne',
                    style: const TextStyle(
                        fontSize: 13, color: ivorianForest, fontWeight: FontWeight.w600),
                  ),
                ],
              ),
            ),

          // ── Liste ────────────────────────────────────────────────────────
          Expanded(
            child: _saved.isEmpty
                ? const EmptyView(
                    message: 'Aucun contenu enregistré hors ligne.',
                    icon: Icons.download_for_offline_outlined,
                  )
                : ListView.builder(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 12),
                    itemCount: _saved.length,
                    itemBuilder: (context, index) {
                      final item = _saved[index];
                      return FadeSlideIn(
                        delay: Duration(
                            milliseconds:
                                index * kStaggerDelay.inMilliseconds),
                        child: Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: ListTile(
                            tileColor: Theme.of(context)
                                .colorScheme
                                .surfaceContainerHighest
                                .withValues(alpha: .4),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(kRadiusMd),
                            ),
                            leading: Container(
                              width: 36,
                              height: 36,
                              decoration: BoxDecoration(
                                color: ivorianOrange.withValues(alpha: .1),
                                borderRadius:
                                    BorderRadius.circular(kRadiusSm),
                              ),
                              child: const Icon(Icons.place_outlined,
                                  size: 18, color: ivorianOrange),
                            ),
                            title: Text(item.name,
                                style: const TextStyle(
                                    fontWeight: FontWeight.w700)),
                            subtitle: Text(item.location),
                          ),
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
