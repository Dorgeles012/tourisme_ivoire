import 'package:flutter/material.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../widgets/common.dart';

class PassportScreen extends StatelessWidget {
  const PassportScreen({required this.api, super.key});
  final ApiService api;

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Passeport numérique')),
        body: FutureBuilder<List<dynamic>>(
          future: Future.wait([api.getPassport(), api.getBadges(), api.getVisits()]),
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
            if (snapshot.hasError) return ErrorView(message: readableError(snapshot.error!));
            final passport = Map<String, dynamic>.from(snapshot.data![0] as Map);
            final badges = snapshot.data![1];
            final visits = snapshot.data![2];
            return ListView(
              padding: const EdgeInsets.all(18),
              children: [
                Card(
                  color: Theme.of(context).colorScheme.primaryContainer,
                  child: Padding(
                    padding: const EdgeInsets.all(22),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('${passport['numero']}', style: Theme.of(context).textTheme.labelLarge),
                        const SizedBox(height: 10),
                        Text('Niveau ${passport['niveau']}', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900)),
                        Text('${passport['points_total']} points'),
                        const SizedBox(height: 10),
                        Text('${passport['visites']} visites • ${passport['badges']} badges • ${passport['quiz_termines']} quiz'),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Text('Badges', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
                ...badges.map((row) {
                  final badge = Map<String, dynamic>.from(row as Map);
                  return Card(
                    child: ListTile(
                      leading: Icon(badge['obtenu'] == true ? Icons.verified : Icons.lock_outline, color: badge['obtenu'] == true ? const Color(0xFFD4AF37) : null),
                      title: Text('${badge['nom']}'),
                      subtitle: Text('${badge['description'] ?? ''}'),
                      trailing: Text('${badge['points']} pts'),
                    ),
                  );
                }),
                const SizedBox(height: 18),
                Text('Historique des visites', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
                if (visits.isEmpty) const Padding(padding: EdgeInsets.all(12), child: Text('Aucune visite enregistrée.')),
                ...visits.map((row) {
                  final visit = Map<String, dynamic>.from(row as Map);
                  return ListTile(leading: const Icon(Icons.place_outlined), title: Text('${visit['nom']}'), subtitle: Text('${visit['localite']} • ${visit['visite_le']}'));
                }),
              ],
            );
          },
        ),
      );
}

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
      if (mounted) setState(() => _favorites = widget.api.getFavorites());
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Mes favoris')),
        body: FutureBuilder<List<dynamic>>(
          future: _favorites,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
            if (snapshot.hasError) return ErrorView(message: readableError(snapshot.error!), onRetry: () => setState(() => _favorites = widget.api.getFavorites()));
            if (snapshot.data!.isEmpty) return const EmptyView(message: 'Aucun favori enregistré.', icon: Icons.favorite_border);
            return ListView(
              padding: const EdgeInsets.all(12),
              children: snapshot.data!.map((row) {
                final item = Map<String, dynamic>.from(row as Map);
                final destination = Destination.fromJson(item);
                return Dismissible(
                  key: ValueKey(item['favori_id']),
                  direction: DismissDirection.endToStart,
                  onDismissed: (_) => _remove(int.parse('${item['favori_id']}')),
                  background: Container(alignment: Alignment.centerRight, padding: const EdgeInsets.only(right: 24), color: Colors.red, child: const Icon(Icons.delete_outline, color: Colors.white)),
                  child: DestinationTile(destination: destination, api: widget.api),
                );
              }).toList(),
            );
          },
        ),
      );
}
