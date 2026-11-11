import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../services/reservation_service.dart';
import '../widgets/common.dart';

class TransportScreen extends StatefulWidget {
  const TransportScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<TransportScreen> createState() => _TransportScreenState();
}

class _TransportScreenState extends State<TransportScreen> {
  final _from = TextEditingController();
  final _to = TextEditingController();
  late final ReservationService _service;
  List<dynamic> _routes = const [];
  String? _error;
  bool _loading = false;
  bool _searched = false;

  @override
  void initState() {
    super.initState();
    _service = ReservationService(widget.api);
  }

  @override
  void dispose() {
    _from.dispose();
    _to.dispose();
    super.dispose();
  }

  Future<void> _search() async {
    if (_from.text.trim().isEmpty || _to.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Indiquez le départ et l’arrivée.')));
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
      _searched = true;
    });
    try {
      final routes = await _service.search(_from.text, _to.text);
      if (!mounted) return;
      setState(() => _routes = routes);
    } catch (error) {
      if (mounted) setState(() => _error = readableError(error));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _reserve(Map<String, dynamic> route) async {
    try {
      final result = await _service.reserve(int.parse('${route['id']}'));
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Demande envoyée : ${result['reference']}')));
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(
          title: const Text('Transports'),
          actions: [IconButton(tooltip: 'Historique des réservations', onPressed: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => ReservationHistoryScreen(api: widget.api))), icon: const Icon(Icons.receipt_long_outlined))],
        ),
        body: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Text('Rechercher un itinéraire', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
            const SizedBox(height: 12),
            TextField(controller: _from, textInputAction: TextInputAction.next, decoration: const InputDecoration(labelText: 'Départ', prefixIcon: Icon(Icons.trip_origin))),
            const SizedBox(height: 10),
            TextField(controller: _to, textInputAction: TextInputAction.search, onSubmitted: (_) => _search(), decoration: const InputDecoration(labelText: 'Arrivée', prefixIcon: Icon(Icons.place_outlined))),
            const SizedBox(height: 12),
            FilledButton.icon(onPressed: _loading ? null : _search, icon: const Icon(Icons.search), label: const Text('Comparer les options')),
            const SizedBox(height: 16),
            FutureBuilder<List<dynamic>>(
              future: _service.transports(),
              builder: (context, snapshot) {
                if (snapshot.hasError) return Text(readableError(snapshot.error!));
                if (!snapshot.hasData || snapshot.data!.isEmpty) return const SizedBox.shrink();
                return Wrap(
                  spacing: 8,
                  children: snapshot.data!.map((row) => Chip(label: Text('${(row as Map)['nom']}'))).toList(),
                );
              },
            ),
            if (_loading) const Padding(padding: EdgeInsets.all(20), child: Center(child: CircularProgressIndicator())),
            if (_error != null) ErrorView(message: _error!, onRetry: _search),
            if (!_loading && _searched && _error == null && _routes.isEmpty)
              const EmptyView(message: 'Aucun itinéraire vérifié ne correspond à cette recherche. Les tarifs et horaires ne sont pas inventés.', icon: Icons.route_outlined),
            ..._routes.map((row) {
              final route = Map<String, dynamic>.from(row as Map);
              final price = route['tarif'];
              final duration = route['duree_minutes'];
              return Card(
                margin: const EdgeInsets.symmetric(vertical: 7),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('${route['depart_libelle']} → ${route['arrivee_libelle']}', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
                      const SizedBox(height: 8),
                      Text('${route['transport']}${route['transporteur'] == null ? '' : ' • ${route['transporteur']}'}'),
                      const SizedBox(height: 5),
                      Text(price == null ? 'Tarif non communiqué' : '$price ${route['devise'] ?? ''}'),
                      Text(duration == null ? 'Durée non communiquée' : 'Durée : $duration min'),
                      Text(route['disponibilite'] == 'disponible' ? 'Disponibilité déclarée disponible' : 'Disponibilité à confirmer'),
                      const SizedBox(height: 10),
                      FilledButton.tonal(onPressed: () => _reserve(route), child: const Text('Envoyer une demande de réservation')),
                    ],
                  ),
                ),
              );
            }),
          ],
        ),
      );
}

class ReservationHistoryScreen extends StatelessWidget {
  const ReservationHistoryScreen({required this.api, super.key});
  final ApiService api;

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Mes réservations')),
        body: FutureBuilder<List<dynamic>>(
          future: api.getReservations(),
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
            if (snapshot.hasError) return ErrorView(message: readableError(snapshot.error!));
            if (snapshot.data!.isEmpty) return const EmptyView(message: 'Aucune demande de réservation.', icon: Icons.receipt_long_outlined);
            return ListView(
              padding: const EdgeInsets.all(16),
              children: snapshot.data!.map((row) {
                final booking = Map<String, dynamic>.from(row as Map);
                return Card(
                  child: ListTile(
                    leading: const Icon(Icons.confirmation_number_outlined),
                    title: Text('${booking['libelle_snapshot'] ?? booking['reference']}'),
                    subtitle: Text('${booking['reference']} • ${booking['statut']}'),
                    trailing: Text(booking['montant_total'] == null ? 'À confirmer' : '${booking['montant_total']} ${booking['devise'] ?? ''}'),
                  ),
                );
              }).toList(),
            );
          },
        ),
      );
}
