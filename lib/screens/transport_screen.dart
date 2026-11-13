import 'package:material_ui/material_ui.dart';

import '../services/api_service.dart';
import '../services/reservation_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';

// TRANSPORT SCREEN
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
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Indiquez le départ et l\'arrivée.'),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(kRadiusMd)),
        ),
      );
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
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Demande envoyée : ${result['reference']}'),
          behavior: SnackBarBehavior.floating,
          backgroundColor: const Color(0xFF2E7D32),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(kRadiusMd)),
        ),
      );
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(readableError(error)),
          behavior: SnackBarBehavior.floating,
        ));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Transports'),
        centerTitle: false,
        actions: [
          IconButton(
            tooltip: 'Historique des réservations',
            onPressed: () =>
                navigateTo(context, ReservationHistoryScreen(api: widget.api)),
            icon: const Icon(Icons.receipt_long_outlined),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          // ── Formulaire de recherche ────────────────────────────────────
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  ivorianOrange.withValues(alpha: .07),
                  ivorianForest.withValues(alpha: .05),
                ],
              ),
              borderRadius: BorderRadius.circular(kRadiusLg),
              border: Border.all(
                  color: ivorianOrange.withValues(alpha: .15)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        color: ivorianOrange.withValues(alpha: .12),
                        borderRadius: BorderRadius.circular(kRadiusSm),
                      ),
                      child: const Icon(Icons.directions_bus_outlined,
                          size: 18, color: ivorianOrange),
                    ),
                    const SizedBox(width: 10),
                    Text(
                      'Rechercher un itinéraire',
                      style: Theme.of(context)
                          .textTheme
                          .titleMedium
                          ?.copyWith(fontWeight: FontWeight.w800),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                // Départ
                TextField(
                  controller: _from,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'Départ',
                    prefixIcon: const Icon(Icons.trip_origin,
                        color: ivorianForest),
                    filled: true,
                    fillColor: cs.surface,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(kRadiusMd),
                      borderSide: BorderSide.none,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(kRadiusMd),
                      borderSide: BorderSide(
                          color: cs.outlineVariant.withValues(alpha: .4)),
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                // Arrivée
                TextField(
                  controller: _to,
                  textInputAction: TextInputAction.search,
                  onSubmitted: (_) => _search(),
                  decoration: InputDecoration(
                    labelText: 'Arrivée',
                    prefixIcon: const Icon(Icons.place_outlined,
                        color: ivorianOrange),
                    filled: true,
                    fillColor: cs.surface,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(kRadiusMd),
                      borderSide: BorderSide.none,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(kRadiusMd),
                      borderSide: BorderSide(
                          color: cs.outlineVariant.withValues(alpha: .4)),
                    ),
                  ),
                ),
                const SizedBox(height: 14),
                // Bouton rechercher
                IvorianButton(
                  label: _loading ? 'Recherche…' : 'Comparer les options',
                  icon: Icons.search,
                  onPressed: _loading ? null : _search,
                  useGradient: true,
                  height: 48,
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // ── Chips transporteurs disponibles ───────────────────────────
          FutureBuilder<List<dynamic>>(
            future: _service.transports(),
            builder: (context, snapshot) {
              if (!snapshot.hasData || snapshot.data!.isEmpty) {
                return const SizedBox.shrink();
              }
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Wrap(
                  spacing: 8,
                  runSpacing: 6,
                  children: snapshot.data!
                      .map((row) => Chip(
                            label: Text('${(row as Map)['nom']}'),
                            backgroundColor:
                                ivorianForest.withValues(alpha: .08),
                            side: BorderSide(
                                color: ivorianForest.withValues(alpha: .2)),
                            labelStyle:
                                const TextStyle(fontSize: 12),
                          ))
                      .toList(),
                ),
              );
            },
          ),

          // ── État loading ──────────────────────────────────────────────
          if (_loading)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 24),
              child: Center(
                child: SizedBox(
                  width: 32,
                  height: 32,
                  child: CircularProgressIndicator(
                      color: ivorianOrange, strokeWidth: 2.5),
                ),
              ),
            ),

          // ── État erreur ───────────────────────────────────────────────
          if (_error != null) ErrorView(message: _error!, onRetry: _search),

          // ── État vide ─────────────────────────────────────────────────
          if (!_loading && _searched && _error == null && _routes.isEmpty)
            const EmptyView(
              message:
                  'Aucun itinéraire vérifié ne correspond à cette recherche. Les tarifs et horaires ne sont pas inventés.',
              icon: Icons.route_outlined,
            ),

          // ── Résultats ─────────────────────────────────────────────────
          ...List.generate(_routes.length, (index) {
            final route =
                Map<String, dynamic>.from(_routes[index] as Map);
            final price = route['tarif'];
            final duration = route['duree_minutes'];
            final available =
                route['disponibilite'] == 'disponible';

            return FadeSlideIn(
              delay: Duration(
                  milliseconds: index * kStaggerDelay.inMilliseconds),
              child: Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Container(
                  decoration: BoxDecoration(
                    color: cs.surface,
                    borderRadius: BorderRadius.circular(kRadiusMd),
                    boxShadow: kShadowSoft,
                    border: Border.all(
                        color: cs.outlineVariant.withValues(alpha: .3)),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // ── Trajet ─────────────────────────────────────
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                '${route['depart_libelle']} → ${route['arrivee_libelle']}',
                                style: const TextStyle(
                                    fontWeight: FontWeight.w800,
                                    fontSize: 16),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: available
                                    ? const Color(0xFF2E7D32)
                                        .withValues(alpha: .1)
                                    : ivorianOcre.withValues(alpha: .1),
                                borderRadius:
                                    BorderRadius.circular(kRadiusSm),
                              ),
                              child: Text(
                                available ? 'Disponible' : 'À confirmer',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  color: available
                                      ? const Color(0xFF2E7D32)
                                      : ivorianOcre,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),

                        // ── Infos ──────────────────────────────────────
                        Wrap(
                          spacing: 16,
                          runSpacing: 4,
                          children: [
                            _InfoChip(
                              icon: Icons.directions_bus_outlined,
                              label:
                                  '${route['transport']}${route['transporteur'] == null ? '' : ' • ${route['transporteur']}'}',
                            ),
                            _InfoChip(
                              icon: Icons.attach_money,
                              label: price == null
                                  ? 'Tarif non communiqué'
                                  : '$price ${route['devise'] ?? ''}',
                            ),
                            if (duration != null)
                              _InfoChip(
                                icon: Icons.schedule_outlined,
                                label: '$duration min',
                              ),
                          ],
                        ),
                        const SizedBox(height: 14),

                        // ── Bouton réservation ─────────────────────────
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            onPressed: () => _reserve(route),
                            icon: const Icon(Icons.send_outlined, size: 18),
                            label: const Text(
                                'Envoyer une demande de réservation'),
                            style: FilledButton.styleFrom(
                              backgroundColor: ivorianOrange,
                              minimumSize: const Size(double.infinity, 44),
                              shape: RoundedRectangleBorder(
                                borderRadius:
                                    BorderRadius.circular(kRadiusMd),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          }),
        ],
      ),
    );
  }
}

// ─── Chip info ────────────────────────────────────────────────────────────────
class _InfoChip extends StatelessWidget {
  const _InfoChip({required this.icon, required this.label});
  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon,
            size: 13,
            color:
                Theme.of(context).colorScheme.onSurface.withValues(alpha: .5)),
        const SizedBox(width: 4),
        Text(
          label,
          style: TextStyle(
              fontSize: 12,
              color: Theme.of(context)
                  .colorScheme
                  .onSurface
                  .withValues(alpha: .7)),
        ),
      ],
    );
  }
}

// RESERVATION HISTORY SCREEN
class ReservationHistoryScreen extends StatelessWidget {
  const ReservationHistoryScreen({required this.api, super.key});
  final ApiService api;

  Color _statusColor(String? status) => switch ('$status'.toLowerCase()) {
        'confirmee' || 'confirmé' => const Color(0xFF2E7D32),
        'annulee' || 'annulé' => ivorianTerracotta,
        _ => ivorianOcre,
      };

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes réservations')),
      body: FutureBuilder<List<dynamic>>(
        future: api.getReservations(),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(message: readableError(snapshot.error!));
          }
          if (snapshot.data!.isEmpty) {
            return const EmptyView(
              message: 'Aucune demande de réservation.',
              icon: Icons.receipt_long_outlined,
            );
          }
          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: snapshot.data!.length,
            itemBuilder: (context, index) {
              final booking = Map<String, dynamic>.from(
                  snapshot.data![index] as Map);
              final statut = '${booking['statut']}';
              final color = _statusColor(statut);

              return FadeSlideIn(
                delay: Duration(
                    milliseconds: index * kStaggerDelay.inMilliseconds),
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Container(
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surface,
                      borderRadius: BorderRadius.circular(kRadiusMd),
                      boxShadow: kShadowSoft,
                    ),
                    child: ListTile(
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(kRadiusMd),
                      ),
                      leading: Container(
                        width: 42,
                        height: 42,
                        decoration: BoxDecoration(
                          color: color.withValues(alpha: .1),
                          borderRadius: BorderRadius.circular(kRadiusSm),
                        ),
                        child: Icon(
                            Icons.confirmation_number_outlined,
                            size: 20,
                            color: color),
                      ),
                      title: Text(
                        '${booking['libelle_snapshot'] ?? booking['reference']}',
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      ),
                      subtitle: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: color.withValues(alpha: .1),
                              borderRadius:
                                  BorderRadius.circular(kRadiusSm),
                            ),
                            child: Text(
                              statut,
                              style: TextStyle(
                                  fontSize: 10,
                                  color: color,
                                  fontWeight: FontWeight.w700),
                            ),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            '${booking['reference']}',
                            style: const TextStyle(fontSize: 11),
                          ),
                        ],
                      ),
                      trailing: booking['montant_total'] == null
                          ? const Text('À confirmer',
                              style: TextStyle(fontSize: 12))
                          : Text(
                              '${booking['montant_total']} ${booking['devise'] ?? ''}',
                              style: const TextStyle(
                                  fontWeight: FontWeight.w700)),
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
