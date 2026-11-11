import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_service.dart';
import '../widgets/common.dart';

class HeritageScreen extends StatefulWidget {
  const HeritageScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<HeritageScreen> createState() => _HeritageScreenState();
}

class _HeritageScreenState extends State<HeritageScreen> {
  late Future<(List<dynamic>, List<dynamic>)> _content;

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
          const SnackBar(content: Text('Impossible d’ouvrir ce média.')),
        );
      }
    }
  }

  Future<void> _recordView(int id) async {
    if (!await widget.api.hasSession) {
      return;
    }
    try {
      await widget.api.recordHeritageView(id);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(readableError(error))));
      }
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Patrimoine & visites virtuelles')),
    body: FutureBuilder<(List<dynamic>, List<dynamic>)>(
      future: _content,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const LoadingView();
        }
        if (snapshot.hasError) {
          return ErrorView(
            message: readableError(snapshot.error!),
            onRetry: () => setState(() => _content = _load()),
          );
        }
        final heritage = snapshot.data!.$1;
        final virtualVisits = snapshot.data!.$2;
        if (heritage.isEmpty && virtualVisits.isEmpty) {
          return const EmptyView(
            message:
                'Aucun contenu culturel ou média virtuel vérifié et publié.',
            icon: Icons.account_balance_outlined,
          );
        }
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (heritage.isNotEmpty) ...[
              Text(
                'Culture et patrimoine',
                style: Theme.of(context).textTheme.titleLarge
                    ?.copyWith(fontWeight: FontWeight.w800),
              ),
              ...heritage.map((row) {
                final item = Map<String, dynamic>.from(row as Map);
                return Card(
                  child: ExpansionTile(
                    leading: const Icon(Icons.account_balance_outlined),
                    title: Text('${item['titre']}'),
                    subtitle: Text(
                      '${item['localite'] ?? 'Côte d’Ivoire'}${item['periode'] == null ? '' : ' • ${item['periode']}'}',
                    ),
                    onExpansionChanged: (expanded) {
                      if (expanded) {
                        _recordView(int.parse('${item['id']}'));
                      }
                    },
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(16),
                        child: Text('${item['contenu']}'),
                      ),
                    ],
                  ),
                );
              }),
            ],
            if (virtualVisits.isNotEmpty) ...[
              const SizedBox(height: 18),
              Text(
                'Expériences virtuelles',
                style: Theme.of(context).textTheme.titleLarge
                    ?.copyWith(fontWeight: FontWeight.w800),
              ),
              ...virtualVisits.map((row) {
                final item = Map<String, dynamic>.from(row as Map);
                return Card(
                  child: ListTile(
                    leading: const Icon(Icons.view_in_ar_outlined),
                    title: Text('${item['titre']}'),
                    subtitle: Text(
                      '${item['type_visite']} • ${item['patrimoine'] ?? item['destination'] ?? ''}',
                    ),
                    trailing: const Icon(Icons.open_in_new),
                    onTap: () => _open('${item['url']}'),
                  ),
                );
              }),
            ],
            const SizedBox(height: 20),
            const Card(
              child: ListTile(
                leading: Icon(Icons.info_outline),
                title: Text('Réalité augmentée'),
                subtitle: Text(
                  'Le scan AR et la comparaison avant/après nécessitent des contenus 3D et un fournisseur compatible. Aucune expérience non configurée n’est simulée.',
                ),
              ),
            ),
          ],
        );
      },
    ),
  );
}
