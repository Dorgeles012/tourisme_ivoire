import 'package:material_ui/material_ui.dart';

import '../services/api_service.dart';

class TourismContentScreen extends StatelessWidget {
  const TourismContentScreen({
    required this.api,
    this.initialType = 'actualites',
    super.key,
  });
  final ApiService api;
  final String initialType;

  static const _types = [
    'actualites',
    'promotions',
    'hebergements',
    'activites',
  ];
  static const _labels = [
    'Actualités',
    'Promotions',
    'Hébergements',
    'Activités',
  ];

  @override
  Widget build(BuildContext context) => DefaultTabController(
    length: _types.length,
    initialIndex: _types.contains(initialType)
        ? _types.indexOf(initialType)
        : 0,
    child: Scaffold(
      appBar: AppBar(
        title: const Text('À découvrir'),
        bottom: TabBar(
          isScrollable: true,
          tabs: [for (final label in _labels) Tab(text: label)],
        ),
      ),
      body: TabBarView(
        children: [
          for (var index = 0; index < _types.length; index++)
            _ContentList(api: api, type: _types[index], label: _labels[index]),
        ],
      ),
    ),
  );
}

class _ContentList extends StatelessWidget {
  const _ContentList({
    required this.api,
    required this.type,
    required this.label,
  });
  final ApiService api;
  final String type;
  final String label;

  @override
  Widget build(BuildContext context) => FutureBuilder<List<dynamic>>(
    future: api.getTourismContent(type),
    builder: (context, snapshot) {
      if (snapshot.connectionState != ConnectionState.done) {
        return const Center(child: CircularProgressIndicator());
      }
      if (snapshot.hasError) {
        return Center(child: Text('Impossible de charger $label.'));
      }
      final items = snapshot.data ?? const [];
      if (items.isEmpty) {
        return Center(child: Text('Aucun contenu publié dans $label.'));
      }
      return ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: items.length,
        separatorBuilder: (_, _) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final item = Map<String, dynamic>.from(items[index] as Map);
          final title = '${item['titre'] ?? item['nom'] ?? label}';
          final description =
              '${item['resume'] ?? item['description'] ?? item['contenu'] ?? ''}';
          final image = item['image_url'];
          final price = item['prix'] ?? item['prix_min'];
          final locality = item['localite'];
          return Card(
            clipBehavior: Clip.antiAlias,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (image is String && image.isNotEmpty)
                  Image.network(
                    api.mediaUri(image).toString(),
                    height: 190,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => const SizedBox.shrink(),
                  ),
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      if (locality is String && locality.isNotEmpty) ...[
                        const SizedBox(height: 5),
                        Text(
                          locality,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.secondary,
                          ),
                        ),
                      ],
                      if (price != null) ...[
                        const SizedBox(height: 5),
                        Text('${price.toString()} ${item['devise'] ?? 'XOF'}'),
                      ],
                      if (description.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Text(
                          description,
                          maxLines: 6,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      );
    },
  );
}
