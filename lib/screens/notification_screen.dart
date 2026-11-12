import 'package:material_ui/material_ui.dart';

import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';

// ─── Couleurs de types de notification ───────────────────────────────────────
Color _typeColor(String? type) => switch (type) {
      'success' => const Color(0xFF2E7D32),
      'warning' => ivorianOcre,
      'error' => ivorianTerracotta,
      _ => ivorianForest,
    };

IconData _typeIcon(String? type) => switch (type) {
      'success' => Icons.check_circle_outline,
      'warning' => Icons.warning_amber_outlined,
      'error' => Icons.error_outline,
      _ => Icons.notifications_outlined,
    };

// ─── Helpers temps relatif ────────────────────────────────────────────────────
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

// ─── Écran principal ──────────────────────────────────────────────────────────
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

  Future<void> _markRead(int id) async {
    try {
      await NotificationService(widget.api).markRead(id);
      if (mounted) _reload();
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(readableError(error)),
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(kRadiusMd)),
          ),
        );
      }
    }
  }

  Future<void> _markAllRead(List<dynamic> items) async {
    final unread = items
        .where((r) => (r as Map)['lu_at'] == null)
        .map((r) => int.parse('${(r as Map)['id']}'))
        .toList();
    for (final id in unread) {
      await NotificationService(widget.api).markRead(id);
    }
    if (mounted) _reload();
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
              final hasUnread = snapshot.data
                      ?.any((r) => (r as Map)['lu_at'] == null) ??
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
                  delay: Duration(milliseconds: index * kStaggerDelay.inMilliseconds),
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
                            onTap: isUnread
                                ? () => _markRead(int.parse('${item['id']}'))
                                : null,
                            child: Padding(
                              padding: const EdgeInsets.all(14),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  // ── Icône type ───────────────────────────
                                  Container(
                                    width: 42,
                                    height: 42,
                                    decoration: BoxDecoration(
                                      color:
                                          typeColor.withValues(alpha: .12),
                                      shape: BoxShape.circle,
                                    ),
                                    child: Icon(typeIcon,
                                        size: 20, color: typeColor),
                                  ),
                                  const SizedBox(width: 12),
                                  // ── Contenu ──────────────────────────────
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
                                                  margin: const EdgeInsets
                                                      .only(left: 6),
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
                                            color: cs.onSurface
                                                .withValues(alpha: .7),
                                            height: 1.4,
                                          ),
                                        ),
                                        const SizedBox(height: 6),
                                        Row(
                                          children: [
                                            Text(
                                              _relativeTime('${item['created_at']}'),
                                              style: TextStyle(
                                                fontSize: 11,
                                                color: cs.onSurface
                                                    .withValues(alpha: .45),
                                              ),
                                            ),
                                            if (isUnread) ...[
                                              const Spacer(),
                                              GestureDetector(
                                                onTap: () => _markRead(
                                                    int.parse('${item['id']}')),
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
