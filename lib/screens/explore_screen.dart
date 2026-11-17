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
  late Future<List<dynamic>> _districts;

  String? _category;
  int? _districtId;
  String? _districtName;
  int? _regionId;
  String? _regionName;
  int? _departmentId;
  String? _departmentName;
  int _categoryChipIndex = 0;
  List<String> _categoryLabels = ['Tout'];
  List<String> _categorySlugs = [''];
  int _total = 0;

  @override
  void initState() {
    super.initState();
    _destinations = widget.api.fetchDestinations();
    _categories = widget.api.getCategories();
    _districts = widget.api.getDistricts();

    _categories.then((cats) {
      if (!mounted) return;
      setState(() {
        _categoryLabels = ['Tout', ...cats.map((c) => '${(c as Map)['nom']}')];
        _categorySlugs = ['', ...cats.map((c) => '${(c as Map)['slug']}')];
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
      _districtId = null;
      _districtName = null;
      _regionId = null;
      _regionName = null;
      _departmentId = null;
      _departmentName = null;
      _categoryChipIndex = 0;
    }
    setState(() {
      _destinations = widget.api.fetchDestinations(
        search: _search.text.isEmpty ? null : _search.text,
        category: _category,
        regionId: _regionId,
        districtId: _districtId,
        departmentId: _departmentId,
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
    final districts = await _districts;
    if (!mounted) return;

    Future<(int?, String?)?> choose(List<dynamic> items, String title) {
      return showModalBottomSheet<(int?, String?)>(
        context: context,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(kRadiusXl)),
        ),
        builder: (_) => _RegionBottomSheet(regions: items, title: title),
      );
    }

    final district = await choose(districts, 'Choisir un district');
    if (!mounted || district == null) return;
    final districtId = district.$1;
    final districtName = district.$2;
    int? regionId;
    String? regionName;
    int? departmentId;
    String? departmentName;

    if (districtId != null) {
      final row = districts.cast<Map>().firstWhere(
        (item) => int.tryParse(item['id'].toString()) == districtId,
      );
      final isAutonomous = row['type'].toString() == 'autonome';
      if (!isAutonomous) {
        final regions = await widget.api.getRegions(districtId: districtId);
        if (!mounted) return;
        final region = await choose(regions, 'Choisir une région');
        if (region == null) return;
        regionId = region.$1;
        regionName = region.$2;
      }

      final departments = (regionId != null || isAutonomous)
          ? await widget.api.getDepartements(
              regionId: regionId,
              districtId: isAutonomous ? districtId : null,
            )
          : <dynamic>[];
      if (!mounted) return;
      if (departments.isNotEmpty) {
        final department = await choose(departments, 'Choisir un département');
        if (department == null) return;
        departmentId = department.$1;
        departmentName = department.$2;
      }
    }

    setState(() {
      _districtId = districtId;
      _districtName = districtName;
      _regionId = regionId;
      _regionName = regionName;
      _departmentId = departmentId;
      _departmentName = departmentName;
    });
    _query();
  }

  bool get _hasGeoFilter => _regionId != null || _districtId != null || _departmentId != null;

  String get _geoLabel =>
      _departmentName ?? _regionName ?? _districtName ?? 'Toute la Côte d’Ivoire';

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final hPad = horizontalPadding(width);
    final grid = isGridLayout(width);
    final cs = Theme.of(context).colorScheme;

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            floating: true,
            snap: true,
            backgroundColor: cs.surface,
            surfaceTintColor: cs.surface,
            elevation: 0,
            scrolledUnderElevation: 0.5,
            titleSpacing: hPad,
            title: const Text(
              'Explorer',
              style: TextStyle(
                fontWeight: FontWeight.w900,
                fontSize: 22,
                letterSpacing: -0.6,
              ),
            ),
            bottom: PreferredSize(
              preferredSize: const Size.fromHeight(118),
              child: Padding(
                padding: EdgeInsets.fromLTRB(hPad, 0, hPad, 14),
                child: Column(
                  children: [
                    SearchField(
                      controller: _search,
                      hint: 'Destination, région, activité…',
                      onSearch: _query,
                      onChanged: (_) => _query(),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: GestureDetector(
                            onTap: _openRegionSheet,
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 200),
                              height: 46,
                              padding: const EdgeInsets.symmetric(horizontal: 16),
                              decoration: BoxDecoration(
                                color: _hasGeoFilter
                                    ? ivorianOrange.withValues(alpha: 0.10)
                                    : cs.surfaceContainerHighest.withValues(alpha: 0.55),
                                borderRadius: BorderRadius.circular(kRadiusXl),
                                border: Border.all(
                                  color: _hasGeoFilter
                                      ? ivorianOrange.withValues(alpha: 0.55)
                                      : cs.outlineVariant.withValues(alpha: 0.4),
                                  width: _hasGeoFilter ? 1.5 : 0.8,
                                ),
                              ),
                              child: Row(
                                children: [
                                  Icon(
                                    Icons.map_outlined,
                                    size: 18,
                                    color: _hasGeoFilter
                                        ? ivorianOrange
                                        : cs.onSurfaceVariant,
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      _geoLabel,
                                      style: TextStyle(
                                        fontSize: 13,
                                        fontWeight: FontWeight.w700,
                                        color: _hasGeoFilter
                                            ? ivorianOrange
                                            : cs.onSurfaceVariant,
                                        letterSpacing: -0.1,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  Icon(
                                    Icons.keyboard_arrow_down_rounded,
                                    size: 20,
                                    color: _hasGeoFilter
                                        ? ivorianOrange
                                        : cs.onSurfaceVariant,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                        if (_hasGeoFilter) ...[
                          const SizedBox(width: 8),
                          Material(
                            color: cs.surfaceContainerHighest.withValues(alpha: 0.55),
                            shape: const CircleBorder(),
                            child: IconButton(
                              tooltip: 'Effacer le filtre géographique',
                              icon: Icon(Icons.close_rounded, size: 18, color: cs.onSurfaceVariant),
                              onPressed: () {
                                setState(() {
                                  _districtId = null;
                                  _districtName = null;
                                  _regionId = null;
                                  _regionName = null;
                                  _departmentId = null;
                                  _departmentName = null;
                                });
                                _query();
                              },
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),

          SliverToBoxAdapter(
            child: SizedBox(
              height: 56,
              child: _categoryLabels.length <= 1
                  ? const SizedBox.shrink()
                  : FilterChipRow(
                      items: _categoryLabels,
                      selectedIndex: _categoryChipIndex,
                      onSelected: _selectCategory,
                    ),
            ),
          ),

          if (_total > 0)
            SliverPadding(
              padding: EdgeInsets.fromLTRB(hPad, 8, hPad, 4),
              sliver: SliverToBoxAdapter(
                child: Row(
                  children: [
                    Container(
                      width: 6,
                      height: 6,
                      decoration: const BoxDecoration(
                        color: ivorianOrange,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      '$_total destination${_total > 1 ? 's' : ''} trouvée${_total > 1 ? 's' : ''}',
                      style: TextStyle(
                        fontSize: 12.5,
                        color: cs.onSurfaceVariant,
                        fontWeight: FontWeight.w600,
                        letterSpacing: -0.1,
                      ),
                    ),
                  ],
                ),
              ),
            ),

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
                  child: Padding(
                    padding: EdgeInsets.symmetric(horizontal: hPad),
                    child: ErrorView(
                      message: readableError(snapshot.error!),
                      onRetry: _query,
                    ),
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
                  child: Padding(
                    padding: EdgeInsets.symmetric(horizontal: hPad),
                    child: EmptyView(
                      message: 'Aucune destination pour ces filtres.',
                      icon: Icons.search_off_rounded,
                      ctaLabel: 'Réinitialiser',
                      onCta: () => _query(reset: true),
                    ),
                  ),
                );
              }

              if (grid) {
                return SliverPadding(
                  padding: EdgeInsets.fromLTRB(hPad, 16, hPad, 32),
                  sliver: SliverGrid(
                    gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: crossAxisCount(width),
                      crossAxisSpacing: 14,
                      mainAxisSpacing: 14,
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
                    (_, i) => Padding(
                      padding: EdgeInsets.only(
                        bottom: i == items.length - 1 ? 0 : 14,
                      ),
                      child: DestinationTile(
                        destination: items[i],
                        api: widget.api,
                        compact: false,
                        index: i,
                      ),
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

class _RegionBottomSheet extends StatelessWidget {
  const _RegionBottomSheet({
    required this.regions,
    this.title = 'Choisir une région',
  }) : selectedId = null;

  final List<dynamic> regions;
  final int? selectedId;
  final String title;

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
          Container(
            margin: const EdgeInsets.only(top: 12),
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: scheme.outlineVariant.withValues(alpha: 0.6),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 20, 20, 12),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    title,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.4,
                    ),
                  ),
                ),
                Material(
                  color: scheme.surfaceContainerHighest.withValues(alpha: 0.5),
                  shape: const CircleBorder(),
                  child: IconButton(
                    icon: const Icon(Icons.close_rounded, size: 18),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                ),
              ],
            ),
          ),
          Divider(height: 1, color: scheme.outlineVariant.withValues(alpha: 0.4)),
          Flexible(
            child: ListView(
              shrinkWrap: true,
              padding: const EdgeInsets.fromLTRB(12, 12, 12, 24),
              children: [
                _SheetItem(
                  label: 'Toute la sélection',
                  selected: selectedId == null,
                  onTap: () => Navigator.of(context).pop((null, null)),
                  leading: Icons.public_rounded,
                ),
                for (final r in regions)
                  Builder(builder: (context) {
                    final region = Map<String, dynamic>.from(r as Map);
                    final id = int.tryParse('${region['id']}');
                    final nom = '${region['nom']}';
                    final selected = id == selectedId;
                    return _SheetItem(
                      label: nom,
                      selected: selected,
                      onTap: () => Navigator.of(context).pop((id, nom)),
                      leading: Icons.place_outlined,
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

class _SheetItem extends StatelessWidget {
  const _SheetItem({
    required this.label,
    required this.selected,
    required this.onTap,
    required this.leading,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;
  final IconData leading;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Material(
        color: selected ? ivorianOrange.withValues(alpha: 0.08) : Colors.transparent,
        borderRadius: BorderRadius.circular(kRadiusMd),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(kRadiusMd),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(kRadiusMd),
              border: Border.all(
                color: selected
                    ? ivorianOrange.withValues(alpha: 0.4)
                    : Colors.transparent,
                width: 1,
              ),
            ),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: selected
                        ? ivorianOrange.withValues(alpha: 0.18)
                        : cs.surfaceContainerHighest.withValues(alpha: 0.5),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(
                    leading,
                    size: 18,
                    color: selected ? ivorianOrange : cs.onSurfaceVariant,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    label,
                    style: TextStyle(
                      fontSize: 14.5,
                      fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
                      color: selected ? ivorianOrange : cs.onSurface,
                      letterSpacing: -0.1,
                    ),
                  ),
                ),
                if (selected)
                  const Icon(Icons.check_circle_rounded,
                      color: ivorianOrange, size: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }
}