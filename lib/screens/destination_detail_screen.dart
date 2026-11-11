import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../theme.dart';
import '../widgets/common.dart';

class DestinationDetailScreen extends StatefulWidget {
  const DestinationDetailScreen({required this.api, required this.destinationId, super.key});
  final ApiService api;
  final int destinationId;

  @override
  State<DestinationDetailScreen> createState() => _DestinationDetailScreenState();
}

class _DestinationDetailScreenState extends State<DestinationDetailScreen> {
  late Future<Destination> _destination;
  bool _busy = false;

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
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(success)));
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _openMap(Destination destination) async {
    final query = '${destination.name}, ${destination.location}, Côte d’Ivoire';
    final uri = Uri.https('www.google.com', '/maps/search/', {'api': '1', 'query': query});
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication) && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Impossible d’ouvrir l’application de cartes.')));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Détail de la destination')),
        body: FutureBuilder<Destination>(
          future: _destination,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
            if (snapshot.hasError) return ErrorView(message: readableError(snapshot.error!), onRetry: () => setState(() => _destination = widget.api.fetchDestination(widget.destinationId)));
            final item = snapshot.data!;
            return ListView(
              padding: const EdgeInsets.only(bottom: 28),
              children: [
                SizedBox(
                  height: 245,
                  child: item.imageUrl == null || item.imageUrl!.isEmpty
                      ? Container(color: ivorianGreen.withValues(alpha: .1), child: const Icon(Icons.landscape, size: 72, color: ivorianGreen))
                      : Image.network(item.imageUrl!, fit: BoxFit.cover, errorBuilder: (_, _, _) => const Icon(Icons.landscape, size: 72, color: ivorianGreen)),
                ),
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(item.category.toUpperCase(), style: const TextStyle(color: ivorianGreen, fontWeight: FontWeight.w800, letterSpacing: .7)),
                      const SizedBox(height: 8),
                      Text(item.name, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800)),
                      if (item.location.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text(item.location, style: Theme.of(context).textTheme.bodyMedium),
                      ],
                      const SizedBox(height: 16),
                      Text(item.description, style: Theme.of(context).textTheme.bodyLarge?.copyWith(height: 1.5)),
                      if (item.story != null && item.story!.isNotEmpty) ...[
                        const SizedBox(height: 20),
                        Text('Histoire', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
                        const SizedBox(height: 7),
                        Text(item.story!, style: const TextStyle(height: 1.5)),
                      ],
                      if (item.address != null && item.address!.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        Text('Adresse : ${item.address!}'),
                      ],
                      const SizedBox(height: 20),
                      FilledButton.icon(
                        onPressed: _busy ? null : () => _act(() => widget.api.addFavorite(item.id), 'Destination ajoutée aux favoris.'),
                        icon: const Icon(Icons.favorite_border),
                        label: const Text('Ajouter aux favoris'),
                      ),
                      const SizedBox(height: 8),
                      OutlinedButton.icon(
                        onPressed: _busy ? null : () => _act(() => widget.api.recordVisit(item.id), 'Visite ajoutée à votre passeport.'),
                        icon: const Icon(Icons.check_circle_outline),
                        label: const Text('Enregistrer une visite'),
                      ),
                      const SizedBox(height: 8),
                      OutlinedButton.icon(
                        onPressed: () => _openMap(item),
                        icon: const Icon(Icons.map_outlined),
                        label: const Text('Rechercher sur la carte'),
                      ),
                      if (item.latitude == null || item.longitude == null) ...[
                        const SizedBox(height: 8),
                        Text('Coordonnées GPS non vérifiées; la carte effectuera une recherche par nom.', style: Theme.of(context).textTheme.bodySmall),
                      ],
                    ],
                  ),
                ),
              ],
            );
          },
        ),
      );
}
