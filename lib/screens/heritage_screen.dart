import 'package:material_ui/material_ui.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';

// HERITAGE SCREEN — Culture & visites virtuelles
class HeritageScreen extends StatefulWidget {
  const HeritageScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<HeritageScreen> createState() => _HeritageScreenState();
}

class _HeritageScreenState extends State<HeritageScreen> {
  late Future<(List<dynamic>, List<dynamic>)> _content;
  String _filter = 'Tout';

  static const _categories = ['Tout', 'Culturel', 'Historique', 'Naturel', 'Religieux'];

  @override
  void initState() {
    super.initState();
    _content = _load();
  }

  Future<(List<dynamic>, List<dynamic>)> _load() async {
    final results = await Future.wait([
      widget.api.getHeritage(),
      widget.api.getVirtualVisits(),
    ]);
    return (results[0], results[1]);
  }

  Future<void> _open(String url) async {
    final uri = Uri.tryParse(url);
    if (uri == null ||
        !uri.hasScheme ||
        !await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('Impossible d\'ouvrir ce média.'),
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(kRadiusMd)),
          ),
        );
      }
    }
  }

  Future<void> _recordView(int id) async {
    if (!await widget.api.hasSession) return;
    try {
      await widget.api.recordHeritageView(id);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(readableError(error))));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: FutureBuilder<(List<dynamic>, List<dynamic>)>(
        future: _content,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Scaffold(
              body: LoadingView(),
            );
          }
          if (snapshot.hasError) {
            return Scaffold(
              appBar: AppBar(title: const Text('Patrimoine & visites virtuelles')),
              body: ErrorView(
                message: readableError(snapshot.error!),
                onRetry: () => setState(() => _content = _load()),
              ),
            );
          }

          final allHeritage = snapshot.data!.$1;
          final virtualVisits = snapshot.data!.$2;

          if (allHeritage.isEmpty && virtualVisits.isEmpty) {
            return Scaffold(
              appBar: AppBar(title: const Text('Patrimoine & visites virtuelles')),
              body: const EmptyView(
                message: 'Aucun contenu culturel ou média virtuel vérifié et publié.',
                icon: Icons.account_balance_outlined,
              ),
            );
          }

          // Filtre catégorie
          final heritage = _filter == 'Tout'
              ? allHeritage
              : allHeritage
                  .where((r) =>
                      '${(r as Map)['categorie']}'.toLowerCase() ==
                      _filter.toLowerCase())
                  .toList();

          return CustomScrollView(
            slivers: [
              // ── SliverAppBar ─────────────────────────────────────────────
              SliverAppBar(
                pinned: true,
                title: const Text('Patrimoine & culture'),
                centerTitle: false,
                bottom: PreferredSize(
                  preferredSize: const Size.fromHeight(52),
                  child: Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: SizedBox(
                      height: 36,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: _categories.length,
                        separatorBuilder: (_, _) => const SizedBox(width: 8),
                        itemBuilder: (context, index) {
                          final cat = _categories[index];
                          final selected = cat == _filter;
                          return AnimatedContainer(
                            duration: kNormalAnim,
                            child: FilterChip(
                              label: Text(cat),
                              selected: selected,
                              onSelected: (_) =>
                                  setState(() => _filter = cat),
                              selectedColor:
                                  ivorianOrange.withValues(alpha: .15),
                              checkmarkColor: ivorianOrange,
                              labelStyle: TextStyle(
                                color: selected
                                    ? ivorianOrange
                                    : null,
                                fontWeight: selected
                                    ? FontWeight.w700
                                    : FontWeight.w400,
                              ),
                              side: BorderSide(
                                color: selected
                                    ? ivorianOrange
                                    : Theme.of(context)
                                        .colorScheme
                                        .outlineVariant,
                              ),
                              shape: RoundedRectangleBorder(
                                borderRadius:
                                    BorderRadius.circular(kRadiusXl),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                  ),
                ),
              ),

              // ── Contenu ─────────────────────────────────────────────────
              SliverPadding(
                padding: const EdgeInsets.all(16),
                sliver: SliverList(
                  delegate: SliverChildListDelegate([
                    // Section Patrimoine
                    if (heritage.isNotEmpty) ...[
                      SectionHeader(
                        title: 'Culture et patrimoine',
                        onTapAll: null,
                      ),
                      const SizedBox(height: 10),
                      ...List.generate(heritage.length, (index) {
                        final item =
                            Map<String, dynamic>.from(heritage[index] as Map);
                        return FadeSlideIn(
                          delay: Duration(
                              milliseconds:
                                  index * kStaggerDelay.inMilliseconds),
                          child: RepaintBoundary(
                            child: _HeritageCard(
                              item: item,
                              onExpand: () =>
                                  _recordView(int.parse('${item['id']}')),
                            ),
                          ),
                        );
                      }),
                    ],

                    // Section Visites virtuelles
                    if (virtualVisits.isNotEmpty) ...[
                      const SizedBox(height: 20),
                      SectionHeader(
                        title: 'Expériences virtuelles',
                        onTapAll: null,
                      ),
                      const SizedBox(height: 10),
                      ...List.generate(virtualVisits.length, (index) {
                        final item = Map<String, dynamic>.from(
                            virtualVisits[index] as Map);
                        return FadeSlideIn(
                          delay: Duration(
                              milliseconds:
                                  (heritage.length + index) *
                                      kStaggerDelay.inMilliseconds),
                          child: _VirtualVisitCard(
                            item: item,
                            onTap: () => _open('${item['url']}'),
                          ),
                        );
                      }),
                    ],

                    // Note AR
                    const SizedBox(height: 20),
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: ivorianForest.withValues(alpha: .07),
                        borderRadius: BorderRadius.circular(kRadiusMd),
                        border: Border.all(
                            color: ivorianForest.withValues(alpha: .2)),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.info_outline,
                              size: 18,
                              color: ivorianForest.withValues(alpha: .8)),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Le scan AR et la comparaison avant/après nécessitent des contenus 3D et un fournisseur compatible.',
                              style: TextStyle(
                                fontSize: 12,
                                color: Theme.of(context)
                                    .colorScheme
                                    .onSurface
                                    .withValues(alpha: .6),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                  ]),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

// ─── Carte patrimoine (ExpansionTile stylisé) ─────────────────────────────────
class _HeritageCard extends StatelessWidget {
  const _HeritageCard({required this.item, required this.onExpand});
  final Map<String, dynamic> item;
  final VoidCallback onExpand;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Container(
        decoration: BoxDecoration(
          color: cs.surface,
          borderRadius: BorderRadius.circular(kRadiusMd),
          boxShadow: kShadowSoft,
          border: Border.all(color: cs.outlineVariant.withValues(alpha: .3)),
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(kRadiusMd),
          child: ExpansionTile(
            leading: Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: ivorianOrange.withValues(alpha: .1),
                borderRadius: BorderRadius.circular(kRadiusSm),
              ),
              child: const Icon(Icons.account_balance_outlined,
                  size: 20, color: ivorianOrange),
            ),
            title: Text(
              '${item['titre']}',
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
            ),
            subtitle: Text(
              '${item['localite'] ?? 'Côte d\'Ivoire'}${item['periode'] == null ? '' : ' • ${item['periode']}'}',
              style: const TextStyle(fontSize: 12),
            ),
            onExpansionChanged: (expanded) {
              if (expanded) onExpand();
            },
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
                color: ivorianOrange.withValues(alpha: .03),
                child: Text(
                  '${item['contenu']}',
                  style: const TextStyle(height: 1.55, fontSize: 14),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ─── Carte visite virtuelle ───────────────────────────────────────────────────
class _VirtualVisitCard extends StatelessWidget {
  const _VirtualVisitCard({required this.item, required this.onTap});
  final Map<String, dynamic> item;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: cs.surface,
        borderRadius: BorderRadius.circular(kRadiusMd),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(kRadiusMd),
          splashColor: ivorianForest.withValues(alpha: .08),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(kRadiusMd),
              boxShadow: kShadowSoft,
              border:
                  Border.all(color: cs.outlineVariant.withValues(alpha: .3)),
            ),
            child: Row(
              children: [
                Container(
                  width: 46,
                  height: 46,
                  decoration: BoxDecoration(
                    color: ivorianForest.withValues(alpha: .1),
                    borderRadius: BorderRadius.circular(kRadiusSm),
                  ),
                  child: const Icon(Icons.view_in_ar_outlined,
                      size: 22, color: ivorianForest),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${item['titre']}',
                        style: const TextStyle(
                            fontWeight: FontWeight.w700, fontSize: 14),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        '${item['type_visite']} • ${item['patrimoine'] ?? item['destination'] ?? ''}',
                        style: TextStyle(
                            fontSize: 12,
                            color: cs.onSurface.withValues(alpha: .6)),
                      ),
                    ],
                  ),
                ),
                Icon(Icons.open_in_new,
                    size: 18,
                    color: ivorianForest.withValues(alpha: .7)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
