import 'package:material_ui/material_ui.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';

// PASSPORT SCREEN — Passeport numérique du voyageur
class PassportScreen extends StatelessWidget {
  const PassportScreen({required this.api, super.key});
  final ApiService api;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: FutureBuilder<List<dynamic>>(
        future: Future.wait([
          api.getPassport(),
          api.getBadges(),
          api.getVisits(),
        ]),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Scaffold(body: LoadingView());
          }
          if (snapshot.hasError) {
            return Scaffold(
              appBar: AppBar(title: const Text('Passeport numérique')),
              body: ErrorView(message: readableError(snapshot.error!)),
            );
          }
          final passport =
              Map<String, dynamic>.from(snapshot.data![0] as Map);
          final badges = (snapshot.data![1] as List)
              .map((r) => Map<String, dynamic>.from(r as Map))
              .toList();
          final visits = (snapshot.data![2] as List)
              .map((r) => Map<String, dynamic>.from(r as Map))
              .toList();

          return _PassportBody(
              passport: passport, badges: badges, visits: visits);
        },
      ),
    );
  }
}

// ─── Corps du passeport ───────────────────────────────────────────────────────
class _PassportBody extends StatelessWidget {
  const _PassportBody({
    required this.passport,
    required this.badges,
    required this.visits,
  });
  final Map<String, dynamic> passport;
  final List<Map<String, dynamic>> badges;
  final List<Map<String, dynamic>> visits;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    final points = passport['points_total'] as int? ?? 0;
    final niveau = passport['niveau'] as int? ?? 1;
    final visites = passport['visites'] as int? ?? 0;
    final badgesCount = passport['badges'] as int? ?? 0;
    final quizTermines = passport['quiz_termines'] as int? ?? 0;

    return CustomScrollView(
      slivers: [
        // ── SliverAppBar decoratif ────────────────────────────────────────
        SliverAppBar(
          expandedHeight: 240,
          pinned: true,
          title: const Text('Passeport numérique'),
          flexibleSpace: FlexibleSpaceBar(
            background: Stack(
              fit: StackFit.expand,
              children: [
                // Gradient
                const DecoratedBox(
                  decoration: BoxDecoration(gradient: kAppBarGradient),
                ),
                // Cercles décoratifs
                Positioned(
                  top: -30,
                  right: -40,
                  child: Container(
                    width: 180,
                    height: 180,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white.withValues(alpha: .07),
                    ),
                  ),
                ),
                Positioned(
                  bottom: -20,
                  left: -20,
                  child: Container(
                    width: 140,
                    height: 140,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white.withValues(alpha: .05),
                    ),
                  ),
                ),
                // Contenu passeport
                Positioned(
                  left: 20,
                  right: 20,
                  bottom: 20,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        '${passport['numero']}',
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: .65),
                          fontSize: 12,
                          letterSpacing: 2,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Niveau $niveau',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 28,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '$points points',
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: .85),
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 14),
                      // Stats row
                      Row(
                        children: [
                          _PassportStat(
                              value: '$visites',
                              label: 'Visites'),
                          const SizedBox(width: 24),
                          _PassportStat(
                              value: '$badgesCount',
                              label: 'Badges'),
                          const SizedBox(width: 24),
                          _PassportStat(
                              value: '$quizTermines',
                              label: 'Quiz'),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),

        // ── Contenu ──────────────────────────────────────────────────────
        SliverPadding(
          padding: const EdgeInsets.all(16),
          sliver: SliverList(
            delegate: SliverChildListDelegate([
              // ── Section Badges ─────────────────────────────────────
              if (badges.isNotEmpty) ...[
                SectionHeader(title: 'Badges', onTapAll: null),
                const SizedBox(height: 10),
                GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate:
                      const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                    childAspectRatio: 2.2,
                  ),
                  itemCount: badges.length,
                  itemBuilder: (context, index) {
                    final badge = badges[index];
                    final obtained = badge['obtenu'] == true;
                    return FadeSlideIn(
                      delay: Duration(
                          milliseconds:
                              index * kStaggerDelay.inMilliseconds),
                      child: _BadgeCard(badge: badge, obtained: obtained),
                    );
                  },
                ),
                const SizedBox(height: 24),
              ],

              // ── Section Visites ────────────────────────────────────
              SectionHeader(title: 'Historique des visites', onTapAll: null),
              const SizedBox(height: 10),

              if (visits.isEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  child: Text(
                    'Aucune visite enregistrée.',
                    style: TextStyle(
                        color:
                            cs.onSurface.withValues(alpha: .5)),
                  ),
                )
              else
                ...List.generate(visits.length, (index) {
                  final visit = visits[index];
                  return FadeSlideIn(
                    delay: Duration(
                        milliseconds:
                            (badges.length + index) *
                                kStaggerDelay.inMilliseconds),
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: Container(
                        decoration: BoxDecoration(
                          color: cs.surface,
                          borderRadius: BorderRadius.circular(kRadiusMd),
                          boxShadow: kShadowSoft,
                        ),
                        child: ListTile(
                          shape: RoundedRectangleBorder(
                              borderRadius:
                                  BorderRadius.circular(kRadiusMd)),
                          leading: Container(
                            width: 40,
                            height: 40,
                            decoration: BoxDecoration(
                              color: ivorianOrange.withValues(alpha: .1),
                              borderRadius:
                                  BorderRadius.circular(kRadiusSm),
                            ),
                            child: const Icon(Icons.place_outlined,
                                size: 20, color: ivorianOrange),
                          ),
                          title: Text(
                            '${visit['nom']}',
                            style: const TextStyle(
                                fontWeight: FontWeight.w700),
                          ),
                          subtitle: Text(
                              '${visit['localite']} • ${visit['visite_le']}'),
                        ),
                      ),
                    ),
                  );
                }),
              const SizedBox(height: 24),
            ]),
          ),
        ),
      ],
    );
  }
}

// ─── Stat dans le header ──────────────────────────────────────────────────────
class _PassportStat extends StatelessWidget {
  const _PassportStat({required this.value, required this.label});
  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          value,
          style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.w900),
        ),
        Text(
          label,
          style: TextStyle(
              color: Colors.white.withValues(alpha: .7), fontSize: 11),
        ),
      ],
    );
  }
}

// ─── Carte badge ──────────────────────────────────────────────────────────────
class _BadgeCard extends StatelessWidget {
  const _BadgeCard({required this.badge, required this.obtained});
  final Map<String, dynamic> badge;
  final bool obtained;

  static const _goldColor = Color(0xFFD4AF37);

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Container(
      decoration: BoxDecoration(
        color: obtained
            ? _goldColor.withValues(alpha: .08)
            : cs.surfaceContainerHighest.withValues(alpha: .5),
        borderRadius: BorderRadius.circular(kRadiusMd),
        border: Border.all(
          color: obtained
              ? _goldColor.withValues(alpha: .4)
              : cs.outlineVariant.withValues(alpha: .3),
        ),
        boxShadow: obtained ? kShadowSoft : null,
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        child: Row(
          children: [
            Icon(
              obtained ? Icons.verified : Icons.lock_outline,
              size: 22,
              color: obtained
                  ? _goldColor
                  : cs.onSurface.withValues(alpha: .3),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    '${badge['nom']}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                      color: obtained
                          ? cs.onSurface
                          : cs.onSurface.withValues(alpha: .4),
                    ),
                  ),
                  Text(
                    '${badge['points']} pts',
                    style: TextStyle(
                      fontSize: 11,
                      color: obtained
                          ? _goldColor
                          : cs.onSurface.withValues(alpha: .3),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// FAVORITES SCREEN
class FavoritesScreen extends StatefulWidget {
  const FavoritesScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen> {
  late Future<List<dynamic>> _favorites;

  @override
  void initState() {
    super.initState();
    _favorites = widget.api.getFavorites();
  }

  Future<void> _remove(int id) async {
    try {
      await widget.api.removeFavorite(id);
      if (mounted) {
        setState(() => _favorites = widget.api.getFavorites());
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
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes favoris'),
        centerTitle: false,
      ),
      body: FutureBuilder<List<dynamic>>(
        future: _favorites,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: readableError(snapshot.error!),
              onRetry: () =>
                  setState(() => _favorites = widget.api.getFavorites()),
            );
          }
          if (snapshot.data!.isEmpty) {
            return const EmptyView(
              message: 'Aucun favori enregistré.',
              icon: Icons.favorite_border,
            );
          }

          return ListView.builder(
            padding:
                const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            itemCount: snapshot.data!.length,
            itemBuilder: (context, index) {
              final item = Map<String, dynamic>.from(
                  snapshot.data![index] as Map);
              final destination = Destination.fromJson(item);

              return FadeSlideIn(
                delay: Duration(
                    milliseconds: index * kStaggerDelay.inMilliseconds),
                child: Dismissible(
                  key: ValueKey(item['favori_id']),
                  direction: DismissDirection.endToStart,
                  onDismissed: (_) =>
                      _remove(int.parse('${item['favori_id']}')),
                  background: Container(
                    alignment: Alignment.centerRight,
                    padding: const EdgeInsets.only(right: 24),
                    decoration: BoxDecoration(
                      color: ivorianTerracotta,
                      borderRadius: BorderRadius.circular(kRadiusMd),
                    ),
                    child: const Icon(Icons.delete_outline,
                        color: Colors.white),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: DestinationTile(
                      destination: destination,
                      api: widget.api,
                      index: index,
                    ),
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
