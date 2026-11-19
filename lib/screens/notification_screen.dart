import 'package:material_ui/material_ui.dart';

import 'tourism_content_screen.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../theme/design_system.dart';
import '../widgets/app_alert.dart';
import '../widgets/common.dart';

Color _typeColor(String? type) => switch (type) {
  'success' => const Color(0xFF2E7D32),
  'warning' => ivorianOcre,
  'error' => ivorianTerracotta,
  _ => ivorianForest,
};

IconData _typeIcon(String? type) => switch (type) {
  'nouveau_contenu' => Icons.campaign_outlined,
  'success' => Icons.check_circle_outline,
  'warning' => Icons.warning_amber_outlined,
  'error' => Icons.error_outline,
  _ => Icons.notifications_outlined,
};

String _relativeTime(String? iso) {
  if (iso == null) return '';
  final dt = DateTime.tryParse(iso);
  if (dt == null) return '';
  final diff = DateTime.now().difference(dt);
  if (diff.inSeconds < 60) return 'à l\'instant';
  if (diff.inMinutes < 60) return 'il y a ${diff.inMinutes} min';
  if (diff.inHours < 24) return 'il y a ${diff.inHours} h';
  if (diff.inDays == 1) return 'hier';
  if (diff.inDays < 7) return 'il y a ${diff.inDays} j';
  return '${dt.day}/${dt.month}/${dt.year}';
}

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

  void _reload() =>
      setState(() => _notifications = NotificationService(widget.api).list());

  Future<void> _openNotification(Map<String, dynamic> item) async {
    try {
      if (item['lu_at'] == null) {
        await NotificationService(widget.api)
            .markRead(int.parse('${item['id']}'));
      }
      if (!mounted) return;
      _reload();
      final target = Uri.tryParse('${item['cible_url'] ?? ''}');
      final page = target?.queryParameters['page'];
      if (const {
        'actualites',
        'promotions',
        'hebergements',
        'activites',
      }.contains(page)) {
        Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) =>
                TourismContentScreen(api: widget.api, initialType: page!),
          ),
        );
      }
    } catch (error) {
      if (mounted) await AppAlert.error(context, readableError(error));
    }
  }

  Future<void> _markRead(int id) async {
    try {
      await AppAlert.runWithLoading(
        context,
        action: () => NotificationService(widget.api).markRead(id),
      );
      if (mounted) {
        _reload();
        await AppAlert.success(context, 'Notification marquée comme lue.');
      }
    } catch (error) {
      if (mounted) {
        await AppAlert.error(context, readableError(error));
      }
    }
  }

  Future<void> _markAllRead(List<dynamic> items) async {
    final unread = items
        .where((r) => (r as Map)['lu_at'] == null)
        .map((r) => int.parse('${(r as Map)['id']}'))
        .toList();
    if (unread.isEmpty) return;
    if (!await AppAlert.confirm(
      context,
      title: 'Tout marquer comme lu ?',
      message: 'Toutes vos notifications non lues seront marquées comme lues.',
    )) {
      return;
    }
    if (!mounted) return;
    try {
      await AppAlert.runWithLoading(
        context,
        action: () async {
          for (final id in unread) {
            await NotificationService(widget.api).markRead(id);
          }
        },
      );
      if (mounted) {
        _reload();
        await AppAlert.success(context, 'Notifications marquées comme lues.');
      }
    } catch (error) {
      if (mounted) await AppAlert.error(context, readableError(error));
    }
  }

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        centerTitle: false,
        actions: [
          FutureBuilder<List<dynamic>>(
            future: _notifications,
            builder: (context, snapshot) {
              final hasUnread =
                  snapshot.data?.any((r) => (r as Map)['lu_at'] == null) ??
                  false;
              if (!hasUnread) return const SizedBox.shrink();
              return IconButton(
                tooltip: 'Tout marquer comme lu',
                icon: const Icon(Icons.done_all_outlined),
                onPressed: () => _markAllRead(snapshot.data!),
              );
            },
          ),
        ],
      ),
      body: FutureBuilder<List<dynamic>>(
        future: _notifications,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: readableError(snapshot.error!),
              onRetry: _reload,
            );
          }
          if (snapshot.data!.isEmpty) {
            return const EmptyView(
              message: 'Aucune notification pour le moment.',
              icon: Icons.notifications_none_outlined,
            );
          }

          final items = snapshot.data!;
          return RefreshIndicator(
            color: ivorianOrange,
            onRefresh: () async => _reload(),
            child: ListView.builder(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = Map<String, dynamic>.from(items[index] as Map);
                final isUnread = item['lu_at'] == null;
                final typeColor = _typeColor('${item['type']}');
                final typeIcon = _typeIcon('${item['type']}');

                return FadeSlideIn(
                  delay: Duration(
                    milliseconds: index * kStaggerDelay.inMilliseconds,
                  ),
                  child: RepaintBoundary(
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: AnimatedContainer(
                        duration: kNormalAnim,
                        decoration: BoxDecoration(
                          color: isUnread
                              ? ivorianOrange.withValues(alpha: .06)
                              : cs.surface,
                          borderRadius: BorderRadius.circular(kRadiusMd),
                          border: Border.all(
                            color: isUnread
                                ? ivorianOrange.withValues(alpha: .25)
                                : cs.outlineVariant.withValues(alpha: .4),
                            width: 1,
                          ),
                          boxShadow: kShadowSoft,
                        ),
                        child: Material(
                          color: Colors.transparent,
                          borderRadius: BorderRadius.circular(kRadiusMd),
                          child: InkWell(
                            borderRadius: BorderRadius.circular(kRadiusMd),
                            splashColor: ivorianOrange.withValues(alpha: .08),
                            onTap: () => _openNotification(item),
                            child: Padding(
                              padding: const EdgeInsets.all(14),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Container(
                                    width: 42,
                                    height: 42,
                                    decoration: BoxDecoration(
                                      color: typeColor.withValues(alpha: .12),
                                      shape: BoxShape.circle,
                                    ),
                                    child: Icon(
                                      typeIcon,
                                      size: 20,
                                      color: typeColor,
                                    ),
                                  ),
                                  const SizedBox(width: 12),

                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          children: [
                                            Expanded(
                                              child: Text(
                                                '${item['titre']}',
                                                style: TextStyle(
                                                  fontWeight: isUnread
                                                      ? FontWeight.w800
                                                      : FontWeight.w600,
                                                  fontSize: 14,
                                                ),
                                              ),
                                            ),
                                            if (isUnread)
                                              Semantics(
                                                label: 'Non lue',
                                                child: Container(
                                                  width: 8,
                                                  height: 8,
                                                  margin: const EdgeInsets.only(
                                                    left: 6,
                                                  ),
                                                  decoration: BoxDecoration(
                                                    color: ivorianOrange,
                                                    shape: BoxShape.circle,
                                                  ),
                                                ),
                                              ),
                                          ],
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          '${item['contenu']}',
                                          style: TextStyle(
                                            fontSize: 13,
                                            color: cs.onSurface.withValues(
                                              alpha: .7,
                                            ),
                                            height: 1.4,
                                          ),
                                        ),
                                        const SizedBox(height: 6),
                                        Row(
                                          children: [
                                            Text(
                                              _relativeTime(
                                                '${item['created_at']}',
                                              ),
                                              style: TextStyle(
                                                fontSize: 11,
                                                color: cs.onSurface.withValues(
                                                  alpha: .45,
                                                ),
                                              ),
                                            ),
                                            if (isUnread) ...[
                                              const Spacer(),
                                              GestureDetector(
                                                onTap: () => _markRead(
                                                  int.parse('${item['id']}'),
                                                ),
                                                child: Text(
                                                  'Marquer comme lu',
                                                  style: TextStyle(
                                                    fontSize: 11,
                                                    color: ivorianOrange,
                                                    fontWeight: FontWeight.w700,
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
