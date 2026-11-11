import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../widgets/common.dart';

class MapScreen extends StatefulWidget {
  const MapScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<MapScreen> createState() => _MapScreenState();
}

class _MapScreenState extends State<MapScreen> {
  late Future<List<Destination>> _destinations;

  @override
  void initState() {
    super.initState();
    _destinations = widget.api.fetchDestinations(page: 1);
  }

  Future<void> _open(Destination destination) async {
    final query = '${destination.name}, ${destination.location}, Côte d’Ivoire';
    final uri = Uri.https('www.google.com','/maps/search/',{'api':'1','query':query});
    if (!await launchUrl(uri,mode:LaunchMode.externalApplication) && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Impossible d’ouvrir l’application de cartes.')));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Carte touristique')),
        body: FutureBuilder<List<Destination>>(
          future: _destinations,
          builder: (context,snapshot) {
            if (snapshot.connectionState==ConnectionState.waiting) return const LoadingView();
            if (snapshot.hasError) return ErrorView(message:readableError(snapshot.error!),onRetry:()=>setState(()=>_destinations=widget.api.fetchDestinations()));
            if (snapshot.data!.isEmpty) return const EmptyView(message:'Aucune destination publiée à afficher sur la carte.');
            return ListView(
              padding:const EdgeInsets.all(14),
              children:[
                const Card(child:ListTile(leading:Icon(Icons.info_outline),title:Text('Carte externe'),subtitle:Text('La recherche ouvre votre application de cartes. Les coordonnées manquantes ne sont pas inventées.'))),
                ...snapshot.data!.map((item)=>Card(child:ListTile(
                  leading:const Icon(Icons.place_outlined),
                  title:Text(item.name),
                  subtitle:Text(item.location),
                  trailing:IconButton(tooltip:'Ouvrir la carte',icon:const Icon(Icons.map_outlined),onPressed:()=>_open(item)),
                ))),
              ],
            );
          },
        ),
      );
}
