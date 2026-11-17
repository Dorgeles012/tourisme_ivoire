import 'package:material_ui/material_ui.dart';

enum AppAlertType { success, error, warning, info }

class AppAlert {
  const AppAlert._();

  static Future<void> success(BuildContext context, String message) =>
      show(context, message, type: AppAlertType.success);

  static Future<void> error(BuildContext context, String message) =>
      show(context, message, type: AppAlertType.error);

  static Future<void> warning(BuildContext context, String message) =>
      show(context, message, type: AppAlertType.warning);

  static Future<void> info(BuildContext context, String message) =>
      show(context, message, type: AppAlertType.info);

  static Future<void> show(
    BuildContext context,
    String message, {
    AppAlertType type = AppAlertType.info,
  }) {
    if (!context.mounted) return Future<void>.value();
    final colorScheme = Theme.of(context).colorScheme;
    final (title, icon, color) = switch (type) {
      AppAlertType.success => ('Succès', Icons.check_circle_outline, colorScheme.primary),
      AppAlertType.error => ('Erreur', Icons.error_outline, colorScheme.error),
      AppAlertType.warning => ('Attention', Icons.warning_amber_rounded, colorScheme.tertiary),
      AppAlertType.info => ('Information', Icons.info_outline, colorScheme.primary),
    };

    return showDialog<void>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        icon: Icon(icon, color: color, size: 36),
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  static Future<bool> confirm(
    BuildContext context, {
    required String title,
    required String message,
    String confirmLabel = 'Confirmer',
    String cancelLabel = 'Annuler',
  }) async {
    if (!context.mounted) return false;
    return await showDialog<bool>(
          context: context,
          builder: (dialogContext) => AlertDialog(
            icon: Icon(
              Icons.help_outline_rounded,
              color: Theme.of(dialogContext).colorScheme.tertiary,
              size: 36,
            ),
            title: Text(title),
            content: Text(message),
            actions: [
              TextButton(
                onPressed: () => Navigator.of(dialogContext).pop(false),
                child: Text(cancelLabel),
              ),
              FilledButton(
                onPressed: () => Navigator.of(dialogContext).pop(true),
                child: Text(confirmLabel),
              ),
            ],
          ),
        ) ??
        false;
  }

  static Future<T> runWithLoading<T>(
    BuildContext context, {
    required Future<T> Function() action,
    String message = 'Chargement en cours…',
  }) async {
    if (!context.mounted) throw StateError('The loading context is no longer mounted.');
    final navigator = Navigator.of(context, rootNavigator: true);
    final route = DialogRoute<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => AlertDialog(
        content: Row(
          children: [
            const CircularProgressIndicator(),
            const SizedBox(width: 20),
            Expanded(child: Text(message)),
          ],
        ),
      ),
    );
    final dialog = navigator.push<void>(route);
    try {
      return await action();
    } finally {
      if (route.isActive) {
        if (route.isCurrent) {
          navigator.pop();
        } else {
          navigator.removeRoute(route);
        }
      }
      await dialog;
    }
  }
}
