import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../widgets/common.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  late Future<List<dynamic>> _notifications;

  @override
  void initState() {
    super.initState();
    _notifications = NotificationService(widget.api).list();
  }

  Future<void> _markRead(int id) async {
    try {
      await NotificationService(widget.api).markRead(id);
      if (mounted) setState(() => _notifications = NotificationService(widget.api).list());
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Notifications')),
        body: FutureBuilder<List<dynamic>>(
          future: _notifications,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
            if (snapshot.hasError) return ErrorView(message: readableError(snapshot.error!), onRetry: () => setState(() => _notifications = NotificationService(widget.api).list()));
            if (snapshot.data!.isEmpty) return const EmptyView(message: 'Aucune notification.', icon: Icons.notifications_none);
            return ListView(
              padding: const EdgeInsets.all(14),
              children: snapshot.data!.map((row) {
                final item = Map<String, dynamic>.from(row as Map);
                final read = item['lu_at'] != null;
                return Card(
                  child: ListTile(
                    leading: Icon(read ? Icons.notifications_none : Icons.notifications_active_outlined),
                    title: Text('${item['titre']}', style: TextStyle(fontWeight: read ? FontWeight.normal : FontWeight.w800)),
                    subtitle: Text('${item['contenu']}'),
                    trailing: read ? null : IconButton(tooltip: 'Marquer comme lu', icon: const Icon(Icons.done), onPressed: () => _markRead(int.parse('${item['id']}'))),
                  ),
                );
              }).toList(),
            );
          },
        ),
      );
}
