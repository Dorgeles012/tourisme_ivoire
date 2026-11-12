// lib/screens/explore_screen.dart
// Écran Explorer — recherche, filtres, grille responsive
import 'package:material_ui/material_ui.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';

class ExploreScreen extends StatefulWidget {
  const ExploreScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<ExploreScreen> createState() => _ExploreScreenState();
}

class _ExploreScreenState extends State<ExploreScreen> {
  final _search = TextEditingController();
  late Future<List<Destination>> _destinations;
  late Future<List<dynamic>> _categories;
  late Future<List<dynamic>> _regions;

  String? _category;
  int? _regionId;
  String? _regionName;
  int _categoryChipIndex = 0;
  List<String> _categoryLabels = ['Tout'];
  List<String> _categorySlugs = [''];
  int _total = 0;

  @override
  void initState() {
    super.initState();
    _destinations = widget.api.fetchDestinations();
    _categories   = widget.api.getCategories();
    _regions      = widget.api.getRegions();

    _categories.then((cats) {
      if (!mounted) return;
      setState(() {
        _categoryLabels = ['Tout', ...cats.map((c) => '${(c as Map)['nom']}')];
        _categorySlugs  = ['', ...cats.map((c) => '${(c as Map)['slug']}')];
      });
    });
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  void _query({bool reset = false}) {
    if (reset) {
      _search.clear();
      _category = null;
      _regionId = null;
      _regionName = null;
      _categoryChipIndex = 0;
    }
    setState(() {
      _destinations = widget.api.fetchDestinations(
        search:   _search.text.isEmpty ? null : _search.text,
        category: _category,
        regionId: _regionId,
      );
    });
  }

  void _selectCategory(int index) {
    setState(() {
      _categoryChipIndex = index;
      _category = _categorySlugs.length > index && _categorySlugs[index].isNotEmpty
          ? _categorySlugs[index]
          : null;
    });
    _query();
  }

  Future<void> _openRegionSheet() async {
    final regions = await _regions;
    if (!mounted) return;

    final result = await showModalBottomSheet<(int?, String?)>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(kRadiusXl)),
      ),
      builder: (_) => _RegionBottomSheet(
        regions: regions,
        selectedId: _regionId,
      ),
    );

    if (result != null) {
      setState(() {
        _regionId = result.$1;
        _regionName = result.$2;
      });
      _query();
    }
  }

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final hPad  = horizontalPadding(width);
    final grid  = isGridLayout(width);

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // ── AppBar ──────────────────────────────────────
          SliverAppBar(
            floating: true,
            snap: true,
            titleSpacing: hPad,
            title: const Text('Explorer'),
            bottom: PreferredSize(
              preferredSize: const Size.fromHeight(116),
              child: Padding(
                padding: EdgeInsets.fromLTRB(hPad, 0, hPad, 12),
                child: Column(
                  children: [
                    // Champ de recherche
                    SearchField(
                      controller: _search,
                      hint: 'Destination, région, activité…',
                      onSearch: _query,
                      onChanged: (_) => _query(),
                    ),
                    const SizedBox(height: 10),
                    // Bouton région
                    Row(
                      children: [
                        Expanded(
                          child: GestureDetector(
                            onTap: _openRegionSheet,
                            child: Container(
                              height: 42,
                              padding: const EdgeInsets.symmetric(horizontal: 16),
                              decoration: BoxDecoration(
                                color: _regionId != null
                                    ? ivorianOrange.withValues(alpha: 0.1)
                                    : Theme.of(context).colorScheme.surfaceContainerHighest.withValues(alpha: 0.5),
                                borderRadius: BorderRadius.circular(kRadiusXl),
                                border: _regionId != null
                                    ? Border.all(color: ivorianOrange.withValues(alpha: 0.5), width: 1.5)
                                    : null,
                              ),
                              child: Row(
                                children: [
                                  Icon(
                                    Icons.map_outlined,
                                    size: 18,
                                    color: _regionId != null
                                        ? ivorianOrange
                                        : Theme.of(context).colorScheme.onSurfaceVariant,
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      _regionName ?? 'Toutes les régions',
                                      style: TextStyle(
                                        fontSize: 13,
                                        fontWeight: FontWeight.w600,
                                        color: _regionId != null
                                            ? ivorianOrange
                                            : Theme.of(context).colorScheme.onSurfaceVariant,
                                      ),
                                    ),
                                  ),
                                  Icon(
                                    Icons.keyboard_arrow_down_rounded,
                                    size: 18,
                                    color: _regionId != null
                                        ? ivorianOrange
                                        : Theme.of(context).colorScheme.onSurfaceVariant,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                        if (_regionId != null) ...[
                          const SizedBox(width: 8),
                          IconButton(
                            tooltip: 'Effacer la région',
                            icon: const Icon(Icons.close_rounded, size: 18),
                            onPressed: () {
                              setState(() {
                                _regionId = null;
                                _regionName = null;
                              });
                              _query();
                            },
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),

          // ── Chips catégories ────────────────────────────
          SliverToBoxAdapter(
            child: SizedBox(
              height: 50,
              child: _categoryLabels.length <= 1
                  ? const SizedBox.shrink()
                  : FilterChipRow(
                      items: _categoryLabels,
                      selectedIndex: _categoryChipIndex,
                      onSelected: _selectCategory,
                    ),
            ),
          ),

          // ── Compteur de résultats ────────────────────────
          if (_total > 0)
            SliverPadding(
              padding: EdgeInsets.fromLTRB(hPad, 8, hPad, 0),
              sliver: SliverToBoxAdapter(
                child: Text(
                  '$_total destination${_total > 1 ? 's' : ''} trouvée${_total > 1 ? 's' : ''}',
                  style: TextStyle(
                    fontSize: 12.5,
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ),

          // ── Contenu ─────────────────────────────────────
          FutureBuilder<List<Destination>>(
            future: _destinations,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return SliverPadding(
                  padding: EdgeInsets.fromLTRB(hPad, 16, hPad, 0),
                  sliver: SliverToBoxAdapter(
                    child: LoadingView(isGrid: grid, itemCount: 6),
                  ),
                );
              }
              if (snapshot.hasError) {
                return SliverToBoxAdapter(
                  child: ErrorView(
                    message: readableError(snapshot.error!),
                    onRetry: _query,
                  ),
                );
              }
              final items = snapshot.data ?? [];
              WidgetsBinding.instance.addPostFrameCallback((_) {
                if (mounted && _total != items.length) {
                  setState(() => _total = items.length);
                }
              });

              if (items.isEmpty) {
                return SliverToBoxAdapter(
                  child: EmptyView(
                    message: 'Aucune destination pour ces filtres.',
                    icon: Icons.search_off_rounded,
                    ctaLabel: 'Réinitialiser',
                    onCta: () => _query(reset: true),
                  ),
                );
              }

              if (grid) {
                return SliverPadding(
                  padding: EdgeInsets.fromLTRB(hPad, 16, hPad, 32),
                  sliver: SliverGrid(
                    gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: crossAxisCount(width),
                      crossAxisSpacing: 12,
                      mainAxisSpacing: 12,
                      childAspectRatio: 0.82,
                    ),
                    delegate: SliverChildBuilderDelegate(
                      (_, i) => DestinationTile(
                        destination: items[i],
                        api: widget.api,
                        compact: true,
                        index: i,
                      ),
                      childCount: items.length,
                    ),
                  ),
                );
              }

              return SliverPadding(
                padding: EdgeInsets.fromLTRB(hPad, 16, hPad, 32),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (_, i) => DestinationTile(
                      destination: items[i],
                      api: widget.api,
                      compact: false,
                      index: i,
                    ),
                    childCount: items.length,
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════
// 🗺️ Bottom Sheet sélection de région
// ═══════════════════════════════════════════════════════════

class _RegionBottomSheet extends StatelessWidget {
  const _RegionBottomSheet({required this.regions, this.selectedId});

  final List<dynamic> regions;
  final int? selectedId;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Container(
      decoration: BoxDecoration(
        color: scheme.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(kRadiusXl)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Handle
          Container(
            margin: const EdgeInsets.only(top: 12),
            width: 36,
            height: 4,
            decoration: BoxDecoration(
              color: scheme.outlineVariant,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 20, 20, 8),
            child: Text(
              'Choisir une région',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          const Divider(),
          Flexible(
            child: ListView(
              shrinkWrap: true,
              padding: const EdgeInsets.only(bottom: 24),
              children: [
                // Option "Toutes les régions"
                ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 20),
                  title: const Text('Toutes les régions',
                      style: TextStyle(fontWeight: FontWeight.w600)),
                  trailing: selectedId == null
                      ? Icon(Icons.check_rounded, color: ivorianOrange)
                      : null,
                  onTap: () => Navigator.of(context).pop((null, null)),
                ),
                // Régions
                for (final r in regions)
                  Builder(builder: (context) {
                    final region = Map<String, dynamic>.from(r as Map);
                    final id = int.tryParse('${region['id']}');
                    final nom = '${region['nom']}';
                    final selected = id == selectedId;
                    return ListTile(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 20),
                      title: Text(
                        nom,
                        style: TextStyle(
                          fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                          color: selected ? ivorianOrange : null,
                        ),
                      ),
                      trailing: selected
                          ? Icon(Icons.check_rounded, color: ivorianOrange)
                          : null,
                      onTap: () => Navigator.of(context).pop((id, nom)),
                    );
                  }),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
