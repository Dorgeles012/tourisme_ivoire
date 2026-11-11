import 'package:flutter/material.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
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

  @override
  void initState() {
    super.initState();
    _destinations = widget.api.fetchDestinations();
    _categories = widget.api.getCategories();
    _regions = widget.api.getRegions();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  void _query({String? category, bool clearCategory = false}) {
    setState(() {
      if (clearCategory) {
        _category = null;
      } else if (category != null) {
        _category = category;
      }
      _destinations = widget.api.fetchDestinations(
        search: _search.text,
        category: _category,
        regionId: _regionId,
      );
    });
  }

  @override
  Widget build(BuildContext context) => Column(
    children: [
      const PageHeading(
        title: 'Explorer',
        subtitle: 'Trouvez votre prochaine escale en Côte d’Ivoire.',
      ),
      Padding(
        padding: const EdgeInsets.fromLTRB(20, 6, 20, 12),
        child: TextField(
          controller: _search,
          textInputAction: TextInputAction.search,
          onSubmitted: (_) => _query(),
          decoration: InputDecoration(
            hintText: 'Destination, région, activité…',
            prefixIcon: const Icon(Icons.search),
            suffixIcon: IconButton(
              onPressed: () => _query(),
              icon: const Icon(Icons.arrow_forward),
            ),
          ),
        ),
      ),
      SizedBox(
        height: 46,
        child: FutureBuilder<List<dynamic>>(
          future: _categories,
          builder: (context, snapshot) {
            if (!snapshot.hasData) return const SizedBox.shrink();
            return ListView(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              scrollDirection: Axis.horizontal,
              children: [
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                  child: ChoiceChip(
                    label: const Text('Tout'),
                    selected: _category == null,
                    onSelected: (_) => _query(clearCategory: true),
                  ),
                ),
                FutureBuilder<List<dynamic>>(
                  future: _regions,
                  builder: (context, snapshot) {
                    if (!snapshot.hasData) return const SizedBox.shrink();
                    return Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 20,
                        vertical: 6,
                      ),
                      child: SizedBox(
                        width: 220,
                        child: DropdownButtonFormField<int?>(
                          initialValue: _regionId,
                          decoration: const InputDecoration(
                            labelText: 'Région',
                            prefixIcon: Icon(Icons.map_outlined),
                          ),
                          items: [
                            const DropdownMenuItem<int?>(
                              value: null,
                              child: Text('Toutes les régions'),
                            ),
                            ...snapshot.data!.map((row) {
                              final region = Map<String, dynamic>.from(
                                row as Map,
                              );
                              return DropdownMenuItem<int?>(
                                value: int.parse('${region['id']}'),
                                child: Text('${region['nom']}'),
                              );
                            }),
                          ],
                          onChanged: (value) {
                            _regionId = value;
                            _query();
                          },
                        ),
                      ),
                    );
                  },
                ),
                ...snapshot.data!.map((row) {
                  final category = Map<String, dynamic>.from(row as Map);
                  final slug = '${category['slug']}';
                  return Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 4),
                    child: ChoiceChip(
                      label: Text('${category['nom']}'),
                      selected: _category == slug,
                      onSelected: (_) => _query(category: slug),
                    ),
                  );
                }),
              ],
            );
          },
        ),
      ),
      const SizedBox(height: 10),
      Expanded(
        child: FutureBuilder<List<Destination>>(
          future: _destinations,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const LoadingView();
            }
            if (snapshot.hasError) {
              return ErrorView(
                message: readableError(snapshot.error!),
                onRetry: _query,
              );
            }
            final items = snapshot.data!;
            if (items.isEmpty) {
              return const EmptyView(
                message: 'Aucun résultat publié pour ces filtres.',
              );
            }
            return RefreshIndicator(
              onRefresh: () async => _query(),
              child: ListView.builder(
                itemCount: items.length,
                itemBuilder: (context, index) =>
                    DestinationTile(destination: items[index], api: widget.api),
              ),
            );
          },
        ),
      ),
    ],
  );
}
