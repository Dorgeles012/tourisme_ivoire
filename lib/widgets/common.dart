// lib/widgets/common.dart
// Composants réutilisables — Design System Tourisme Ivoire
import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/services.dart' show PlatformException;
import 'package:material_ui/material_ui.dart';

import '../models/destination.dart';
import '../services/api_service.dart';
import '../screens/destination_detail_screen.dart';
import '../theme/design_system.dart';

// 🛠️ UTILITAIRES

String readableError(Object error) {
  if (error is DioException) {
    final body = error.response?.data;
    if (body is Map && body['error'] is Map && body['error']['message'] is String) {
      final message = body['error']['message'] as String;
      final details = body['error']['details'];
      return details is String && details.isNotEmpty
          ? '$message\nDétail : $details'
          : message;
    }
    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.sendTimeout ||
        error.type == DioExceptionType.receiveTimeout ||
        error.type == DioExceptionType.connectionError) {
      return 'Connexion à l\'API impossible (${error.requestOptions.uri}). '
          'Vérifiez que Apache/MySQL sont démarrés et que l\'URL API est correcte.';
    }
    final status = error.response?.statusCode;
    if (status != null) {
      return switch (status) {
        404 => 'Adresse introuvable (404). '
            'Vérifiez que le projet est dans htdocs/tourisme_ivoire et que Apache autorise mod_rewrite.',
        413 => 'La requête est trop volumineuse (413).',
        429 => 'Trop de tentatives. Réessayez plus tard.',
        >= 500 => 'Erreur du serveur PHP ($status). Consultez le journal PHP/Apache de XAMPP.',
        _ => 'La requête a échoué (HTTP $status). ${error.message ?? ''}'.trim(),
      };
    }
    return 'Erreur de communication avec l\'API : ${error.message ?? error.type.name}';
  }
  if (error is FormatException) return error.message;
  if (error is PlatformException) {
    return 'Le stockage sécurisé Android a échoué (${error.code}). '
        '${error.message ?? 'Vérifiez la configuration Android puis relancez l\'application.'}';
  }
  if (error is StateError) return error.message;
  return 'Une erreur est survenue. Veuillez réessayer.';
}

/// Navigation avec transition fade + slide-up
Future<T?> navigateTo<T>(BuildContext context, Widget page) {
  return Navigator.of(context).push<T>(
    PageRouteBuilder<T>(
      pageBuilder: (_, animation, _) => page,
      transitionDuration: kNormalAnim,
      reverseTransitionDuration: kFastAnim,
      transitionsBuilder: (_, animation, _, child) {
        return FadeTransition(
          opacity: CurvedAnimation(parent: animation, curve: Curves.easeOut),
          child: SlideTransition(
            position: Tween<Offset>(
              begin: const Offset(0, 0.04),
              end: Offset.zero,
            ).animate(CurvedAnimation(parent: animation, curve: Curves.easeOutCubic)),
            child: child,
          ),
        );
      },
    ),
  );
}

// ✨ _FadeSlideIn — Animation d'entrée stagger

class FadeSlideIn extends StatelessWidget {
  const FadeSlideIn({
    required this.child,
    this.delay = Duration.zero,
    this.duration = kNormalAnim,
    super.key,
  });

  final Widget child;
  final Duration delay;
  final Duration duration;

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0.0, end: 1.0),
      duration: duration + delay,
      curve: Interval(
        delay.inMilliseconds / (duration + delay).inMilliseconds,
        1.0,
        curve: Curves.easeOutCubic,
      ),
      // ignore: no_leading_underscores_for_local_identifiers
      builder: (_, value, _child) => Opacity(
        opacity: value,
        child: Transform.translate(
          offset: Offset(0, (1 - value) * 20),
          child: _child,
        ),
      ),
      child: child,
    );
  }
}

// 💀 SkeletonBox — Rectangle shimmer animé

class SkeletonBox extends StatefulWidget {
  const SkeletonBox({
    this.width,
    this.height = 60,
    this.borderRadius = 12.0,
    super.key,
  });

  final double? width;
  final double height;
  final double borderRadius;

  @override
  State<SkeletonBox> createState() => _SkeletonBoxState();
}

class _SkeletonBoxState extends State<SkeletonBox>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final base = scheme.surfaceContainerHighest;
    final highlight = scheme.surface;

    return AnimatedBuilder(
      animation: _controller,
      builder: (_, _) => ShaderMask(
        blendMode: BlendMode.srcATop,
        shaderCallback: (bounds) => LinearGradient(
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
          colors: [base, highlight, base],
          stops: [
            (_controller.value - 0.3).clamp(0.0, 1.0),
            _controller.value.clamp(0.0, 1.0),
            (_controller.value + 0.3).clamp(0.0, 1.0),
          ],
        ).createShader(bounds),
        child: Container(
          width: widget.width,
          height: widget.height,
          decoration: BoxDecoration(
            color: base,
            borderRadius: BorderRadius.circular(widget.borderRadius),
          ),
        ),
      ),
    );
  }
}

// ⏳ LoadingView — Skeleton shimmer adaptatif

class LoadingView extends StatelessWidget {
  const LoadingView({
    this.isGrid = false,
    this.itemCount = 4,
    super.key,
  });

  final bool isGrid;
  final int itemCount;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final hPad = horizontalPadding(width);

    if (isGrid) {
      return Padding(
        padding: EdgeInsets.symmetric(horizontal: hPad),
        child: GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: itemCount,
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: crossAxisCount(width),
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: 0.82,
          ),
          itemBuilder: (_, _) => const SkeletonBox(height: double.infinity, borderRadius: 18),
        ),
      );
    }

    return Padding(
      padding: EdgeInsets.symmetric(horizontal: hPad),
      child: Column(
        children: List.generate(
          itemCount,
          (i) => Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: SkeletonBox(height: 92, borderRadius: 18),
          ),
        ),
      ),
    );
  }
}

// ❌ ErrorView — Vue d'erreur

class ErrorView extends StatelessWidget {
  const ErrorView({required this.message, this.onRetry, super.key});

  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: scheme.error.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.cloud_off_rounded,
                size: 38,
                color: scheme.error,
              ),
            ),
            const SizedBox(height: 18),
            Text(
              'Oups, une erreur !',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w800,
                color: scheme.onSurface,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: scheme.onSurfaceVariant,
                height: 1.5,
                fontSize: 14,
              ),
            ),
            if (onRetry != null) ...[
              const SizedBox(height: 22),
              FilledButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh_rounded, size: 18),
                label: const Text('Réessayer'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

// 📭 EmptyView — Vue vide

class EmptyView extends StatelessWidget {
  const EmptyView({
    required this.message,
    this.icon = Icons.explore_outlined,
    this.ctaLabel,
    this.onCta,
    super.key,
  });

  final String message;
  final IconData icon;
  final String? ctaLabel;
  final VoidCallback? onCta;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 100,
              height: 100,
              decoration: BoxDecoration(
                color: ivorianOrange.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 48, color: ivorianOrange),
            ),
            const SizedBox(height: 20),
            Text(
              message,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                color: scheme.onSurfaceVariant,
                height: 1.5,
              ),
            ),
            if (onCta != null && ctaLabel != null) ...[
              const SizedBox(height: 20),
              FilledButton(
                onPressed: onCta,
                child: Text(ctaLabel!),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

// 📌 PageHeading — Entête de page

class PageHeading extends StatelessWidget {
  const PageHeading({
    required this.title,
    this.subtitle,
    this.action,
    super.key,
  });

  final String title;
  final String? subtitle;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final hPad = horizontalPadding(width);
    final scheme = Theme.of(context).colorScheme;

    return Padding(
      padding: EdgeInsets.fromLTRB(hPad, 20, hPad, 12),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.5,
                    height: 1.2,
                  ),
                ),
                if (subtitle != null) ...[
                  const SizedBox(height: 6),
                  Text(
                    subtitle!,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: scheme.onSurfaceVariant,
                      height: 1.4,
                    ),
                  ),
                ],
              ],
            ),
          ),
          ?action,
        ],
      ),
    );
  }
}

// 📋 SectionHeader — Titre de section + CTA "Tout voir"

class SectionHeader extends StatelessWidget {
  const SectionHeader({
    required this.title,
    this.actionLabel,
    this.onAction,
    this.onTapAll,
    super.key,
  });

  final String title;
  final String? actionLabel;
  final VoidCallback? onAction;
  final VoidCallback? onTapAll;

  @override
  Widget build(BuildContext context) {
    final effectiveOnAction = onAction ?? onTapAll;
    final effectiveActionLabel =
        actionLabel ?? (effectiveOnAction != null ? 'Tout voir' : null);

    return Row(
      children: [
        Expanded(
          child: Text(
            title,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.w800,
              letterSpacing: -0.3,
            ),
          ),
        ),
        if (effectiveOnAction != null && effectiveActionLabel != null)
          TextButton(
            onPressed: effectiveOnAction,
            style: TextButton.styleFrom(
              foregroundColor: ivorianOrange,
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            ),
            child: Text(
              effectiveActionLabel,
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 13,
              ),
            ),
          ),
      ],
    );
  }
}

// 🃏 DestinationTile — Carte destination (liste & grille)

class DestinationTile extends StatelessWidget {
  const DestinationTile({
    required this.destination,
    required this.api,
    this.compact = false,
    this.index = 0,
    super.key,
  });

  final Destination destination;
  final ApiService api;
  final bool compact;
  final int index;

  @override
  Widget build(BuildContext context) {
    return RepaintBoundary(
      child: FadeSlideIn(
        delay: kStaggerDelay * index,
        child: compact ? _CompactTile(destination: destination, api: api) : _ListTile(destination: destination, api: api),
      ),
    );
  }
}

class _ListTile extends StatelessWidget {
  const _ListTile({required this.destination, required this.api});
  final Destination destination;
  final ApiService api;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: scheme.surface,
        borderRadius: BorderRadius.circular(kRadiusLg),
        boxShadow: kShadowSoft,
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(kRadiusLg),
        child: InkWell(
          borderRadius: BorderRadius.circular(kRadiusLg),
          onTap: () => navigateTo(
            context,
            DestinationDetailScreen(api: api, destinationId: destination.id),
          ),
          child: Row(
            children: [
              // Image
              ClipRRect(
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(kRadiusLg),
                  bottomLeft: Radius.circular(kRadiusLg),
                ),
                child: SizedBox(
                  width: 110,
                  height: 110,
                  child: _DestinationImage(imageUrl: destination.imageUrl),
                ),
              ),
              // Contenu
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Badge catégorie
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: ivorianOrange.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(100),
                        ),
                        child: Text(
                          destination.category.toUpperCase(),
                          style: const TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 1.0,
                            color: ivorianOrange,
                          ),
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        destination.name,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w800,
                          height: 1.2,
                        ),
                      ),
                      if (destination.location.isNotEmpty) ...[
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            Icon(
                              Icons.place_outlined,
                              size: 13,
                              color: Theme.of(context).colorScheme.onSurfaceVariant,
                            ),
                            const SizedBox(width: 3),
                            Expanded(
                              child: Text(
                                destination.location,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.only(right: 10),
                child: Icon(
                  Icons.chevron_right_rounded,
                  color: Theme.of(context).colorScheme.onSurfaceVariant.withValues(alpha: 0.5),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CompactTile extends StatelessWidget {
  const _CompactTile({required this.destination, required this.api});
  final Destination destination;
  final ApiService api;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(kRadiusLg),
        boxShadow: kShadowSoft,
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(kRadiusLg),
        child: InkWell(
          borderRadius: BorderRadius.circular(kRadiusLg),
          onTap: () => navigateTo(
            context,
            DestinationDetailScreen(api: api, destinationId: destination.id),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(kRadiusLg),
            child: Stack(
              fit: StackFit.expand,
              children: [
                // Image fond
                _DestinationImage(imageUrl: destination.imageUrl),
                // Gradient overlay
                const DecoratedBox(
                  decoration: BoxDecoration(gradient: kImageOverlayGradient),
                ),
                // Contenu texte en bas
                Positioned(
                  bottom: 12,
                  left: 12,
                  right: 12,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                        decoration: BoxDecoration(
                          color: ivorianOrange.withValues(alpha: 0.85),
                          borderRadius: BorderRadius.circular(100),
                        ),
                        child: Text(
                          destination.category.toUpperCase(),
                          style: const TextStyle(
                            fontSize: 9,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 1.0,
                            color: Colors.white,
                          ),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        destination.name,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          height: 1.2,
                        ),
                      ),
                      if (destination.location.isNotEmpty) ...[
                        const SizedBox(height: 3),
                        Row(
                          children: [
                            const Icon(Icons.place_outlined, size: 11, color: Colors.white70),
                            const SizedBox(width: 2),
                            Expanded(
                              child: Text(
                                destination.location,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  color: Colors.white70,
                                  fontSize: 11,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DestinationImage extends StatelessWidget {
  const _DestinationImage({this.imageUrl});
  final String? imageUrl;

  @override
  Widget build(BuildContext context) {
    if (imageUrl == null || imageUrl!.isEmpty) {
      return Container(
        color: ivorianForest.withValues(alpha: 0.1),
        child: const Icon(Icons.landscape_rounded, size: 36, color: ivorianForest),
      );
    }
    return Image.network(
      imageUrl!,
      fit: BoxFit.cover,
      errorBuilder: (_, _, _) => Container(
        color: ivorianForest.withValues(alpha: 0.1),
        child: const Icon(Icons.landscape_rounded, size: 36, color: ivorianForest),
      ),
      loadingBuilder: (_, child, loadingProgress) {
        if (loadingProgress == null) return child;
        return const SkeletonBox(height: double.infinity, borderRadius: 0);
      },
    );
  }
}

// 🃏 FeatureCard — Carte fonctionnalité

class FeatureCard extends StatefulWidget {
  const FeatureCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
    this.iconColor,
    super.key,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  final Color? iconColor;

  @override
  State<FeatureCard> createState() => _FeatureCardState();
}

class _FeatureCardState extends State<FeatureCard> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final color = widget.iconColor ?? ivorianOrange;

    return GestureDetector(
      onTapDown: (_) => setState(() => _pressed = true),
      onTapUp: (_) => setState(() => _pressed = false),
      onTapCancel: () => setState(() => _pressed = false),
      onTap: widget.onTap,
      child: AnimatedScale(
        scale: _pressed ? 0.97 : 1.0,
        duration: kFastAnim,
        child: Container(
          decoration: BoxDecoration(
            color: scheme.surfaceContainerHighest.withValues(alpha: 0.5),
            borderRadius: BorderRadius.circular(kRadiusLg),
            boxShadow: kShadowSoft,
          ),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Container(
                  width: 46,
                  height: 46,
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(kRadiusMd),
                  ),
                  child: Icon(widget.icon, color: color, size: 22),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        widget.title,
                        style: const TextStyle(
                          fontSize: 14.5,
                          fontWeight: FontWeight.w700,
                          height: 1.2,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        widget.subtitle,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 12.5,
                          height: 1.35,
                          color: scheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
                Icon(
                  Icons.chevron_right_rounded,
                  color: scheme.onSurfaceVariant.withValues(alpha: 0.5),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// 🏷️ FilterChipRow — Rangée de chips filtrables

class FilterChipRow extends StatelessWidget {
  const FilterChipRow({
    required this.items,
    required this.selectedIndex,
    required this.onSelected,
    super.key,
  });

  final List<String> items;
  final int selectedIndex;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 16),
      itemCount: items.length,
      itemBuilder: (context, i) {
        final selected = i == selectedIndex;
        return Padding(
          padding: const EdgeInsets.only(right: 8),
          child: _AnimatedChip(
            label: items[i],
            selected: selected,
            onTap: () => onSelected(i),
          ),
        );
      },
    );
  }
}

class _AnimatedChip extends StatefulWidget {
  const _AnimatedChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  State<_AnimatedChip> createState() => _AnimatedChipState();
}

class _AnimatedChipState extends State<_AnimatedChip> {
  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return GestureDetector(
      onTap: widget.onTap,
      child: AnimatedContainer(
        duration: kFastAnim,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
        decoration: BoxDecoration(
          color: widget.selected
              ? ivorianOrange
              : scheme.surfaceContainerHighest.withValues(alpha: 0.7),
          borderRadius: BorderRadius.circular(100),
          boxShadow: widget.selected ? kShadowColored(ivorianOrange) : null,
        ),
        child: Text(
          widget.label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: widget.selected ? Colors.white : scheme.onSurface,
            letterSpacing: 0.1,
          ),
        ),
      ),
    );
  }
}

// 🔍 SearchField — Champ de recherche stylisé avec debounce

class SearchField extends StatefulWidget {
  const SearchField({
    required this.controller,
    this.hint = 'Rechercher…',
    this.onSearch,
    this.onChanged,
    super.key,
  });

  final TextEditingController controller;
  final String hint;
  final VoidCallback? onSearch;
  final ValueChanged<String>? onChanged;

  @override
  State<SearchField> createState() => _SearchFieldState();
}

class _SearchFieldState extends State<SearchField> {
  Timer? _debounce;
  bool _hasText = false;

  @override
  void initState() {
    super.initState();
    _hasText = widget.controller.text.isNotEmpty;
    widget.controller.addListener(_onControllerChanged);
  }

  void _onControllerChanged() {
    final has = widget.controller.text.isNotEmpty;
    if (has != _hasText) setState(() => _hasText = has);

    if (widget.onChanged != null) {
      _debounce?.cancel();
      _debounce = Timer(kSlowAnim, () {
        if (mounted) widget.onChanged!(widget.controller.text);
      });
    }
  }

  @override
  void dispose() {
    _debounce?.cancel();
    widget.controller.removeListener(_onControllerChanged);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Container(
      height: 52,
      decoration: BoxDecoration(
        color: scheme.surfaceContainerHighest.withValues(alpha: 0.55),
        borderRadius: BorderRadius.circular(kRadiusXl),
        boxShadow: kShadowSoft,
      ),
      child: Row(
        children: [
          const SizedBox(width: 16),
          Icon(Icons.search_rounded, color: scheme.onSurfaceVariant, size: 22),
          const SizedBox(width: 10),
          Expanded(
            child: TextField(
              controller: widget.controller,
              textInputAction: TextInputAction.search,
              onSubmitted: (_) => widget.onSearch?.call(),
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w500),
              decoration: InputDecoration(
                hintText: widget.hint,
                hintStyle: TextStyle(
                  color: scheme.onSurfaceVariant.withValues(alpha: 0.6),
                  fontWeight: FontWeight.w400,
                  fontSize: 15,
                ),
                border: InputBorder.none,
                enabledBorder: InputBorder.none,
                focusedBorder: InputBorder.none,
                filled: false,
                contentPadding: EdgeInsets.zero,
                isDense: true,
              ),
            ),
          ),
          // Bouton clear animé
          ScaleTransition(
            scale: _hasText
                ? const AlwaysStoppedAnimation(1.0)
                : const AlwaysStoppedAnimation(0.0),
            child: AnimatedOpacity(
              opacity: _hasText ? 1.0 : 0.0,
              duration: kFastAnim,
              child: IconButton(
                tooltip: 'Effacer',
                icon: const Icon(Icons.close_rounded, size: 18),
                color: scheme.onSurfaceVariant,
                onPressed: () {
                  widget.controller.clear();
                  widget.onSearch?.call();
                },
              ),
            ),
          ),
          // Bouton recherche
          if (widget.onSearch != null)
            Padding(
              padding: const EdgeInsets.only(right: 6),
              child: IconButton(
                tooltip: 'Rechercher',
                icon: const Icon(Icons.arrow_forward_rounded, size: 20),
                color: ivorianOrange,
                onPressed: widget.onSearch,
              ),
            ),
        ],
      ),
    );
  }
}

// ⭐ RatingBadge — Badge note étoilée

class RatingBadge extends StatelessWidget {
  const RatingBadge({required this.rating, super.key});
  final double rating;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: ivorianGold.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(100),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.star_rounded, size: 14, color: ivorianGold),
          const SizedBox(width: 3),
          Text(
            rating.toStringAsFixed(1),
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: ivorianGold,
            ),
          ),
        ],
      ),
    );
  }
}

// 🔵 IvorianButton — Bouton primaire avec gradient optionnel

class IvorianButton extends StatefulWidget {
  const IvorianButton({
    required this.label,
    required this.onPressed,
    this.icon,
    this.useGradient = false,
    this.loading = false,
    this.height = 52.0,
    super.key,
  });

  final String label;
  final VoidCallback? onPressed;
  final IconData? icon;
  final bool useGradient;
  final bool loading;
  final double height;

  @override
  State<IvorianButton> createState() => _IvorianButtonState();
}

class _IvorianButtonState extends State<IvorianButton> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    if (!widget.useGradient) {
      return AnimatedScale(
        scale: _pressed ? 0.96 : 1.0,
        duration: kFastAnim,
        child: GestureDetector(
          onTapDown: (_) => setState(() => _pressed = true),
          onTapUp: (_) => setState(() => _pressed = false),
          onTapCancel: () => setState(() => _pressed = false),
          child: SizedBox(
            height: widget.height,
            child: widget.icon != null
                ? FilledButton.icon(
                    onPressed: widget.loading ? null : widget.onPressed,
                    style: FilledButton.styleFrom(
                      minimumSize: Size(double.infinity, widget.height),
                    ),
                    icon: widget.loading
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : Icon(widget.icon, size: 18),
                    label: Text(widget.label),
                  )
                : FilledButton(
                    onPressed: widget.loading ? null : widget.onPressed,
                    style: FilledButton.styleFrom(
                      minimumSize: Size(double.infinity, widget.height),
                    ),
                    child: widget.loading
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : Text(widget.label),
                  ),
          ),
        ),
      );
    }

    // Version avec gradient
    return GestureDetector(
      onTapDown: (_) => setState(() => _pressed = true),
      onTapUp: (_) => setState(() => _pressed = false),
      onTapCancel: () => setState(() => _pressed = false),
      onTap: widget.loading ? null : widget.onPressed,
      child: AnimatedScale(
        scale: _pressed ? 0.96 : 1.0,
        duration: kFastAnim,
        child: Container(
          height: widget.height,
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFFFFB347), ivorianOrange, ivorianOcre],
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
            ),
            borderRadius: BorderRadius.circular(kRadiusXl),
            boxShadow: kShadowColored(ivorianOrange),
          ),
          child: Center(
            child: widget.loading
                ? const SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                  )
                : Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (widget.icon != null) ...[
                        Icon(widget.icon, color: Colors.white, size: 20),
                        const SizedBox(width: 10),
                      ],
                      Text(
                        widget.label,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w700,
                          fontSize: 16,
                          letterSpacing: 0.3,
                        ),
                      ),
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}
