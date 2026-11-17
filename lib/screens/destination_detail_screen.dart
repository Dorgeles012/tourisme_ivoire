import 'package:material_ui/material_ui.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/app_alert.dart';
import '../widgets/common.dart';

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
      await AppAlert.success(context, success);
    } catch (error) {
      if (!mounted) return;
      await AppAlert.error(context, readableError(error));
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
      await AppAlert.error(context, 'Impossible d’ouvrir l’application de cartes.');
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
        SliverAppBar(
          expandedHeight: 320,
          pinned: true,
          stretch: true,
          backgroundColor: cs.surface,
          surfaceTintColor: cs.surface,
          elevation: 0,
          scrolledUnderElevation: 1,
          leading: _GlassIconButton(
            icon: Icons.arrow_back_rounded,
            onTap: () => Navigator.of(context).maybePop(),
          ),
          actions: [
            Padding(
              padding: const EdgeInsets.only(right: 12),
              child: _GlassIconButton(
                icon: isFav ? Icons.favorite : Icons.favorite_border,
                color: isFav ? ivorianOrange : Colors.white,
                onTap: busy || isFav ? null : onFavorite,
                tooltip: isFav ? 'Déjà en favoris' : 'Ajouter aux favoris',
              ),
            ),
          ],
          flexibleSpace: FlexibleSpaceBar(
            stretchModes: const [
              StretchMode.zoomBackground,
              StretchMode.blurBackground,
            ],
            background: Stack(
              fit: StackFit.expand,
              children: [
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
                          child: Icon(Icons.landscape,
                              size: 80, color: Colors.white54),
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
                const DecoratedBox(
                  decoration: BoxDecoration(gradient: kImageOverlayGradient),
                ),
                Positioned(
                  left: 20,
                  right: 20,
                  bottom: 24,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: ivorianOrange,
                          borderRadius: BorderRadius.circular(50),
                          boxShadow: [
                            BoxShadow(
                              color: ivorianOrange.withValues(alpha: .4),
                              blurRadius: 12,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Text(
                          destination.category.toUpperCase(),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10.5,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 1.2,
                          ),
                        ),
                      ),
                      const SizedBox(height: 10),
                      Text(
                        destination.name,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 28,
                          fontWeight: FontWeight.w900,
                          letterSpacing: -0.5,
                          height: 1.15,
                          shadows: [
                            Shadow(blurRadius: 12, offset: Offset(0, 2)),
                          ],
                        ),
                      ),
                      if (destination.location.isNotEmpty) ...[
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            const Icon(Icons.place_outlined,
                                size: 15, color: Colors.white70),
                            const SizedBox(width: 4),
                            Flexible(
                              child: Text(
                                destination.location,
                                style: const TextStyle(
                                    color: Colors.white70, fontSize: 13),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
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
        ),

        SliverPadding(
          padding: EdgeInsets.symmetric(horizontal: hPad, vertical: 24),
          sliver: SliverList(
            delegate: SliverChildListDelegate([
              FadeSlideIn(
                delay: const Duration(milliseconds: 50),
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: cs.surfaceContainerHighest.withValues(alpha: 0.4),
                    borderRadius: BorderRadius.circular(kRadiusLg),
                    border: Border.all(
                      color: cs.outlineVariant.withValues(alpha: 0.35),
                      width: 0.6,
                    ),
                  ),
                  child: Text(
                    destination.description,
                    style: const TextStyle(fontSize: 15, height: 1.6),
                  ),
                ),
              ),

              if (destination.story != null &&
                  destination.story!.isNotEmpty) ...[
                const SizedBox(height: 28),
                FadeSlideIn(
                  delay: const Duration(milliseconds: 100),
                  child: _SectionBlock(
                    icon: Icons.auto_stories_outlined,
                    title: 'Histoire',
                    content: destination.story!,
                  ),
                ),
              ],

              if (destination.address != null &&
                  destination.address!.isNotEmpty) ...[
                const SizedBox(height: 20),
                FadeSlideIn(
                  delay: const Duration(milliseconds: 150),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(
                      color: cs.surface,
                      borderRadius: BorderRadius.circular(kRadiusMd),
                      border: Border.all(
                        color: cs.outlineVariant.withValues(alpha: 0.35),
                        width: 0.6,
                      ),
                    ),
                    child: Row(
                      children: [
                        Container(
                          width: 32,
                          height: 32,
                          decoration: BoxDecoration(
                            color: ivorianOcre.withValues(alpha: .14),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Icon(Icons.location_on_outlined,
                              size: 17, color: ivorianOcre),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            destination.address!,
                            style: TextStyle(
                                color: cs.onSurface.withValues(alpha: .8),
                                fontSize: 13.5),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],

              if (destination.latitude == null ||
                  destination.longitude == null) ...[
                const SizedBox(height: 12),
                FadeSlideIn(
                  delay: const Duration(milliseconds: 180),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: ivorianOcre.withValues(alpha: .08),
                      borderRadius: BorderRadius.circular(kRadiusMd),
                      border: Border.all(
                        color: ivorianOcre.withValues(alpha: .2),
                        width: 0.6,
                      ),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.info_outline,
                            size: 16,
                            color: ivorianOcre.withValues(alpha: .9)),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Coordonnées GPS non vérifiées ; la carte effectuera une recherche par nom.',
                            style: TextStyle(
                                fontSize: 11.5,
                                height: 1.4,
                                color: cs.onSurface.withValues(alpha: .6)),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],

              const SizedBox(height: 32),
              FadeSlideIn(
                delay: const Duration(milliseconds: 220),
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

class _GlassIconButton extends StatelessWidget {
  const _GlassIconButton({
    required this.icon,
    required this.onTap,
    this.color = Colors.white,
    this.tooltip,
  });
  final IconData icon;
  final VoidCallback? onTap;
  final Color color;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    final button = Padding(
      padding: const EdgeInsets.all(8),
      child: Material(
        color: Colors.black.withValues(alpha: .35),
        shape: const CircleBorder(),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onTap,
          child: SizedBox(
            width: 40,
            height: 40,
            child: Icon(icon, color: color, size: 20),
          ),
        ),
      ),
    );
    return tooltip != null ? Tooltip(message: tooltip!, child: button) : button;
  }
}

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
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    ivorianOrange.withValues(alpha: .20),
                    ivorianOrange.withValues(alpha: .06),
                  ],
                ),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, size: 18, color: ivorianOrange),
            ),
            const SizedBox(width: 10),
            Text(
              title,
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.2,
                  ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Text(
          content,
          style: TextStyle(
            fontSize: 15,
            height: 1.6,
            color: cs.onSurface.withValues(alpha: .82),
          ),
        ),
      ],
    );
  }
}

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
        AnimatedScale(
          scale: busy ? 0.97 : 1.0,
          duration: kFastAnim,
          child: FilledButton.icon(
            onPressed: busy || isFav ? null : onFavorite,
            icon: Icon(isFav ? Icons.favorite : Icons.favorite_border, size: 20),
            label: Text(isFav ? 'Ajouté aux favoris' : 'Ajouter aux favoris'),
            style: FilledButton.styleFrom(
              backgroundColor: isFav ? const Color(0xFF2E7D32) : ivorianOrange,
              foregroundColor: Colors.white,
              minimumSize: const Size(double.infinity, 54),
              textStyle: const TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 14.5,
                letterSpacing: -0.1,
              ),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(kRadiusMd)),
            ),
          ),
        ),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          onPressed: busy ? null : onRecordVisit,
          icon: const Icon(Icons.check_circle_outline, size: 20),
          label: const Text('Enregistrer une visite'),
          style: OutlinedButton.styleFrom(
            foregroundColor: ivorianForest,
            side: BorderSide(color: ivorianForest.withValues(alpha: .5), width: 1.2),
            minimumSize: const Size(double.infinity, 52),
            textStyle: const TextStyle(
              fontWeight: FontWeight.w700,
              fontSize: 14,
            ),
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(kRadiusMd)),
          ),
        ),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          onPressed: onOpenMap,
          icon: const Icon(Icons.map_outlined, size: 20),
          label: const Text('Rechercher sur la carte'),
          style: OutlinedButton.styleFrom(
            minimumSize: const Size(double.infinity, 52),
            textStyle: const TextStyle(
              fontWeight: FontWeight.w700,
              fontSize: 14,
            ),
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(kRadiusMd)),
          ),
        ),
      ],
    );
  }
}