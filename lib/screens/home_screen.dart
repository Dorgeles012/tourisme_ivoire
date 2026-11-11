import 'package:flutter/material.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../theme.dart';
import '../widgets/common.dart';
import 'feature_screens.dart';
import 'map_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({required this.api, required this.onExplore, required this.onThemeChanged, required this.onAccessibilityChanged, super.key});
  final ApiService api;
  final VoidCallback onExplore;
  final ValueChanged<String> onThemeChanged;
  final ValueChanged<Map<String, dynamic>> onAccessibilityChanged;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<List<Destination>> _destinations;

  @override
  void initState() {
    super.initState();
    _destinations = widget.api.fetchDestinations();
  }

  void _reload() => setState(() => _destinations = widget.api.fetchDestinations());

  void _open(Widget page) => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => page));

  @override
  Widget build(BuildContext context) => RefreshIndicator(
        onRefresh: () async => _reload(),
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
          children: [
            Row(
              children: [
                Container(width: 42, height: 42, decoration: BoxDecoration(color: ivorianOrange.withValues(alpha: .13), borderRadius: BorderRadius.circular(14)), child: const Icon(Icons.travel_explore, color: ivorianOrange)),
                const SizedBox(width: 10),
                const Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('TOURISME IVOIRE', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w900, letterSpacing: 1)), Text('La beauté à portée de voyage', style: TextStyle(fontSize: 11))])),
                IconButton(
                  tooltip: 'Notifications',
                  onPressed: () => _open(NotificationsScreen(api: widget.api)),
                  icon: const Icon(Icons.notifications_none_rounded),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Container(
              height: 285,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(30),
                gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xFFFFA233), ivorianOrange, Color(0xFFCE5900)]),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('DESTINATION CÔTE D’IVOIRE', style: TextStyle(color: Colors.white, fontSize: 10, letterSpacing: 1, fontWeight: FontWeight.w800)),
                  const Spacer(),
                  const Text('Votre prochaine\nhistoire commence ici.', style: TextStyle(color: Colors.white, fontSize: 29, height: 1.12, fontWeight: FontWeight.w800)),
                  const SizedBox(height: 10),
                  Text('Explorez les lieux, les cultures et les saveurs du pays.', style: TextStyle(color: Colors.white.withValues(alpha: .92), fontSize: 13)),
                  const SizedBox(height: 18),
                  FilledButton.icon(
                    onPressed: widget.onExplore,
                    style: FilledButton.styleFrom(backgroundColor: Colors.white, foregroundColor: ivorianInk),
                    icon: const Icon(Icons.explore, size: 18),
                    label: const Text('Explorer maintenant'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            Row(children: [Expanded(child: Text('Destinations à découvrir', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800))), TextButton(onPressed: widget.onExplore, child: const Text('Tout voir'))]),
            FutureBuilder<List<Destination>>(
              future: _destinations,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) return const SizedBox(height: 150, child: LoadingView());
                if (snapshot.hasError) return SizedBox(height: 180, child: ErrorView(message: readableError(snapshot.error!), onRetry: _reload));
                if (snapshot.data!.isEmpty) return const SizedBox(height: 150, child: EmptyView(message: 'Aucune destination publiée. Les fiches de démonstration sont en attente de vérification.'));
                return Column(children: snapshot.data!.take(3).map((item) => DestinationTile(destination: item, api: widget.api)).toList());
              },
            ),
            const SizedBox(height: 18),
            Text('Préparez votre séjour', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
            const SizedBox(height: 6),
            FeatureCard(icon: Icons.directions_bus_outlined, title: 'Transports', subtitle: 'Recherchez un itinéraire et envoyez une demande', onTap: () => _open(TransportScreen(api: widget.api))),
            FeatureCard(icon: Icons.quiz_outlined, title: 'Quiz & culture', subtitle: 'Testez vos connaissances et gagnez des points', onTap: () => _open(QuizListScreen(api: widget.api))),
            FeatureCard(icon: Icons.account_balance_outlined, title: 'Patrimoine & visites virtuelles', subtitle: 'Contenus documentés et expériences disponibles', onTap: () => _open(HeritageScreen(api: widget.api))),
            FeatureCard(icon: Icons.map_outlined, title: 'Carte', subtitle: 'Ouvrir les lieux publiés dans votre application de cartes', onTap: () => _open(MapScreen(api: widget.api))),
            FeatureCard(icon: Icons.settings_outlined, title: 'Paramètres & accessibilité', subtitle: 'Thème, notifications et options de confort', onTap: () => _open(SettingsScreen(api: widget.api, onThemeChanged: widget.onThemeChanged, onAccessibilityChanged: widget.onAccessibilityChanged))),
          ],
        ),
      );
}
