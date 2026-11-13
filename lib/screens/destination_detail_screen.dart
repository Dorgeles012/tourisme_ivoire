import 'package:material_ui/material_ui.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';

// ═══════════════════════════════════════════════════════════════════════════════
// DESTINATION DETAIL SCREEN
// ═══════════════════════════════════════════════════════════════════════════════
class DestinationDetailScreen extends StatefulWidget {
  const DestinationDetailScreen(
      {required this.api, required this.destinationId, super.key});
  final ApiService api;
  final int destinationId;

  @override
  State<DestinationDetailScreen> createState() =>
      _DestinationDetailScreenState();
}

class _DestinationDetailScreenState extends State<DestinationDetailScreen> {
  late Future<Destination> _destination;
  bool _busy = false;
  bool _isFav = false;

  @override
  void initState() {
    super.initState();
    _destination = widget.api.fetchDestination(widget.destinationId);
  }

  Future<void> _act(Future<void> Function() action, String success) async {
    setState(() => _busy = true);
    try {
      await action();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(success),
          behavior: SnackBarBehavior.floating,
          backgroundColor: const Color(0xFF2E7D32),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(kRadiusMd)),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(readableError(error)),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(kRadiusMd)),
        ),
      );
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _openMap(Destination destination) async {
    final query =
        '${destination.name}, ${destination.location}, Côte d\'Ivoire';
    final uri = Uri.https(
        'www.google.com', '/maps/search/', {'api': '1', 'query': query});
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication) &&
        mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Impossible d\'ouvrir l\'application de cartes.'),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: FutureBuilder<Destination>(
        future: _destination,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Scaffold(body: LoadingView());
          }
          if (snapshot.hasError) {
            return Scaffold(
              appBar: AppBar(title: const Text('Destination')),
              body: ErrorView(
                message: readableError(snapshot.error!),
                onRetry: () => setState(
                  () => _destination =
                      widget.api.fetchDestination(widget.destinationId),
                ),
              ),
            );
          }
          final item = snapshot.data!;
          return _DetailBody(
            destination: item,
            busy: _busy,
            isFav: _isFav,
            onFavorite: () async {
              await _act(
                () => widget.api.addFavorite(item.id),
                'Destination ajoutée aux favoris.',
              );
              if (mounted) setState(() => _isFav = true);
            },
            onRecordVisit: () => _act(
              () => widget.api.recordVisit(item.id),
              'Visite ajoutée à votre passeport.',
            ),
            onOpenMap: () => _openMap(item),
          );
        },
      ),
    );
  }
}

// ─── Corps détail ─────────────────────────────────────────────────────────────
class _DetailBody extends StatelessWidget {
  const _DetailBody({
    required this.destination,
    required this.busy,
    required this.isFav,
    required this.onFavorite,
    required this.onRecordVisit,
    required this.onOpenMap,
  });
  final Destination destination;
  final bool busy;
  final bool isFav;
  final VoidCallback onFavorite;
  final VoidCallback onRecordVisit;
  final VoidCallback onOpenMap;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    final width = MediaQuery.sizeOf(context).width;
    final hPad = horizontalPadding(width);

    return CustomScrollView(
      slivers: [
        // ── SliverAppBar avec image hero ─────────────────────────────────────
        SliverAppBar(
          expandedHeight: 300,
          pinned: true,
          stretch: true,
          flexibleSpace: FlexibleSpaceBar(
            stretchModes: const [
              StretchMode.zoomBackground,
              StretchMode.blurBackground,
            ],
            background: Stack(
              fit: StackFit.expand,
              children: [
                // Image
                destination.imageUrl == null || destination.imageUrl!.isEmpty
                    ? DecoratedBox(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                            colors: [ivorianForest, ivorianOcre],
                          ),
                        ),
                        child: const Center(
                          child: Icon(Icons.landscape, size: 80,
                              color: Colors.white54),
                        ),
                      )
                    : Image.network(
                        destination.imageUrl!,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) => Container(
                          color: ivorianForest.withValues(alpha: .3),
                          child: const Icon(Icons.landscape,
                              size: 80, color: Colors.white38),
                        ),
                      ),
                // Gradient bas
                const DecoratedBox(
                  decoration: BoxDecoration(gradient: kImageOverlayGradient),
                ),
                // Titre en bas de la hero
                Positioned(
                  left: 20,
                  right: 80,
                  bottom: 20,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Badge catégorie
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: ivorianOrange.withValues(alpha: .9),
                          borderRadius: BorderRadius.circular(kRadiusSm),
                        ),
                        child: Text(
                          destination.category.toUpperCase(),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10.5,
                            fontWeight: FontWeight.w700,
                            letterSpacing: 1.2,
                          ),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        destination.name,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 24,
                          fontWeight: FontWeight.w900,
                          shadows: [Shadow(blurRadius: 8)],
                        ),
                      ),
                      if (destination.location.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            const Icon(Icons.place_outlined,
                                size: 14, color: Colors.white70),
                            const SizedBox(width: 4),
                            Text(
                              destination.location,
                              style: const TextStyle(
                                  color: Colors.white70, fontSize: 13),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
          // Bouton favori flottant
          actions: [
            Padding(
              padding: const EdgeInsets.only(right: 10),
              child: Tooltip(
                message: isFav ? 'Déjà en favoris' : 'Ajouter aux favoris',
                child: FloatingActionButton.small(
                  heroTag: 'fav_fab',
                  onPressed: busy || isFav ? null : onFavorite,
                  backgroundColor: isFav
                      ? ivorianOrange
                      : cs.surface.withValues(alpha: .9),
                  child: Icon(
                    isFav ? Icons.favorite : Icons.favorite_border,
                    color: isFav ? Colors.white : ivorianOrange,
                  ),
                ),
              ),
            ),
          ],
        ),

        // ── Contenu ──────────────────────────────────────────────────────────
        SliverPadding(
          padding: EdgeInsets.symmetric(horizontal: hPad, vertical: 24),
          sliver: SliverList(
            delegate: SliverChildListDelegate([
              // Description
              FadeSlideIn(
                delay: const Duration(milliseconds: 50),
                child: Text(
                  destination.description,
                  style: const TextStyle(fontSize: 15, height: 1.55),
                ),
              ),

              // Histoire
              if (destination.story != null &&
                  destination.story!.isNotEmpty) ...[
                const SizedBox(height: 24),
                FadeSlideIn(
                  delay: const Duration(milliseconds: 100),
                  child: _SectionBlock(
                    icon: Icons.auto_stories_outlined,
                    title: 'Histoire',
                    content: destination.story!,
                  ),
                ),
              ],

              // Adresse
              if (destination.address != null &&
                  destination.address!.isNotEmpty) ...[
                const SizedBox(height: 20),
                FadeSlideIn(
                  delay: const Duration(milliseconds: 150),
                  child: Row(
                    children: [
                      const Icon(Icons.location_on_outlined,
                          size: 18, color: ivorianOcre),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          destination.address!,
                          style: TextStyle(
                              color: cs.onSurface.withValues(alpha: .75)),
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              // GPS disclaimer
              if (destination.latitude == null ||
                  destination.longitude == null) ...[
                const SizedBox(height: 8),
                FadeSlideIn(
                  delay: const Duration(milliseconds: 180),
                  child: Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: ivorianOcre.withValues(alpha: .08),
                      borderRadius: BorderRadius.circular(kRadiusSm),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.info_outline,
                            size: 14,
                            color: ivorianOcre.withValues(alpha: .8)),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Coordonnées GPS non vérifiées ; la carte effectuera une recherche par nom.',
                            style: TextStyle(
                                fontSize: 11,
                                color: cs.onSurface.withValues(alpha: .55)),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],

              // Actions
              const SizedBox(height: 32),
              FadeSlideIn(
                delay: const Duration(milliseconds: 200),
                child: _ActionButtons(
                  busy: busy,
                  isFav: isFav,
                  onFavorite: onFavorite,
                  onRecordVisit: onRecordVisit,
                  onOpenMap: onOpenMap,
                ),
              ),
              const SizedBox(height: 24),
            ]),
          ),
        ),
      ],
    );
  }
}

// ─── Bloc section (titre + texte) ─────────────────────────────────────────────
class _SectionBlock extends StatelessWidget {
  const _SectionBlock(
      {required this.icon, required this.title, required this.content});
  final IconData icon;
  final String title;
  final String content;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: ivorianOrange.withValues(alpha: .12),
                borderRadius: BorderRadius.circular(kRadiusSm),
              ),
              child: Icon(icon, size: 17, color: ivorianOrange),
            ),
            const SizedBox(width: 10),
            Text(
              title,
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Text(
          content,
          style: TextStyle(
            fontSize: 15,
            height: 1.55,
            color: cs.onSurface.withValues(alpha: .8),
          ),
        ),
      ],
    );
  }
}

// ─── Boutons d'action ─────────────────────────────────────────────────────────
class _ActionButtons extends StatelessWidget {
  const _ActionButtons({
    required this.busy,
    required this.isFav,
    required this.onFavorite,
    required this.onRecordVisit,
    required this.onOpenMap,
  });
  final bool busy;
  final bool isFav;
  final VoidCallback onFavorite;
  final VoidCallback onRecordVisit;
  final VoidCallback onOpenMap;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Ajouter aux favoris
        AnimatedScale(
          scale: busy ? 0.97 : 1.0,
          duration: kFastAnim,
          child: FilledButton.icon(
            onPressed: busy || isFav ? null : onFavorite,
            icon: Icon(isFav ? Icons.favorite : Icons.favorite_border),
            label: Text(isFav ? 'Ajouté aux favoris' : 'Ajouter aux favoris'),
            style: FilledButton.styleFrom(
              backgroundColor: isFav ? const Color(0xFF2E7D32) : ivorianOrange,
              minimumSize: const Size(double.infinity, 50),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(kRadiusMd)),
            ),
          ),
        ),
        const SizedBox(height: 10),
        // Enregistrer visite
        OutlinedButton.icon(
          onPressed: busy ? null : onRecordVisit,
          icon: const Icon(Icons.check_circle_outline),
          label: const Text('Enregistrer une visite'),
          style: OutlinedButton.styleFrom(
            foregroundColor: ivorianForest,
            side: BorderSide(color: ivorianForest.withValues(alpha: .5)),
            minimumSize: const Size(double.infinity, 48),
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(kRadiusMd)),
          ),
        ),
        const SizedBox(height: 10),
        // Carte
        OutlinedButton.icon(
          onPressed: onOpenMap,
          icon: const Icon(Icons.map_outlined),
          label: const Text('Rechercher sur la carte'),
          style: OutlinedButton.styleFrom(
            minimumSize: const Size(double.infinity, 48),
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(kRadiusMd)),
          ),
        ),
      ],
    );
  }
}
