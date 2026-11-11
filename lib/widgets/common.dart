import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../theme.dart';
import '../screens/destination_detail_screen.dart';

String readableError(Object error) {
  if (error is DioException) {
    final body = error.response?.data;
    if (body is Map && body['error'] is Map && body['error']['message'] is String) {
      return body['error']['message'] as String;
    }
    if (error.type == DioExceptionType.connectionTimeout || error.type == DioExceptionType.connectionError) {
      return 'Connexion au serveur impossible. Vérifiez l’adresse API et réessayez.';
    }
  }
  if (error is FormatException) return error.message;
  return 'Une erreur est survenue. Veuillez réessayer.';
}

class LoadingView extends StatelessWidget {
  const LoadingView({super.key, this.label = 'Chargement…'});
  final String label;

  @override
  Widget build(BuildContext context) => Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [const CircularProgressIndicator(color: ivorianOrange), const SizedBox(height: 12), Text(label)],
        ),
      );
}

class ErrorView extends StatelessWidget {
  const ErrorView({required this.message, this.onRetry, super.key});
  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.cloud_off_outlined, size: 38, color: ivorianOrange),
              const SizedBox(height: 12),
              Text(message, textAlign: TextAlign.center),
              if (onRetry != null) ...[
                const SizedBox(height: 12),
                OutlinedButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh), label: const Text('Réessayer')),
              ],
            ],
          ),
        ),
      );
}

class EmptyView extends StatelessWidget {
  const EmptyView({required this.message, this.icon = Icons.explore_outlined, super.key});
  final String message;
  final IconData icon;

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [Icon(icon, size: 40, color: ivorianGreen), const SizedBox(height: 12), Text(message, textAlign: TextAlign.center)],
          ),
        ),
      );
}

class PageHeading extends StatelessWidget {
  const PageHeading({required this.title, this.subtitle, super.key});
  final String title;
  final String? subtitle;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800)),
            if (subtitle != null) ...[
              const SizedBox(height: 5),
              Text(subtitle!, style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant)),
            ],
          ],
        ),
      );
}

class DestinationTile extends StatelessWidget {
  const DestinationTile({required this.destination, required this.api, super.key});
  final Destination destination;
  final ApiService api;

  @override
  Widget build(BuildContext context) => Card(
        margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 7),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(
            builder: (_) => DestinationDetailScreen(api: api, destinationId: destination.id),
          )),
          child: Row(
            children: [
              SizedBox(
                width: 112,
                height: 118,
                child: destination.imageUrl == null || destination.imageUrl!.isEmpty
                    ? Container(color: ivorianGreen.withValues(alpha: .1), child: const Icon(Icons.landscape, size: 38, color: ivorianGreen))
                    : Image.network(destination.imageUrl!, fit: BoxFit.cover, errorBuilder: (_, _, _) => const Icon(Icons.landscape, size: 38, color: ivorianGreen)),
              ),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(destination.category.toUpperCase(), style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: .6, color: ivorianGreen)),
                      const SizedBox(height: 5),
                      Text(destination.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800)),
                      if (destination.location.isNotEmpty) ...[
                        const SizedBox(height: 5),
                        Row(children: [const Icon(Icons.place_outlined, size: 15), const SizedBox(width: 3), Expanded(child: Text(destination.location, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12)))]),
                      ],
                    ],
                  ),
                ),
              ),
              const Padding(padding: EdgeInsets.only(right: 12), child: Icon(Icons.chevron_right)),
            ],
          ),
        ),
      );
}

class FeatureCard extends StatelessWidget {
  const FeatureCard({required this.icon, required this.title, required this.subtitle, required this.onTap, super.key});
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Card(
        child: ListTile(
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          leading: Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(color: ivorianOrange.withValues(alpha: .12), borderRadius: BorderRadius.circular(14)),
            child: Icon(icon, color: ivorianOrange),
          ),
          title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
          subtitle: Text(subtitle),
          trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 16),
          onTap: onTap,
        ),
      );
}
