import 'dart:io' show File;

import 'package:file_picker/file_picker.dart';
import 'package:material_ui/material_ui.dart';
import 'package:video_player/video_player.dart';

import '../services/api_service.dart';
import '../services/video_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';

// ═══════════════════════════════════════════════════════════════════════════════
// VIDEO FEED SCREEN — TikTok-style vertical scroll
// ═══════════════════════════════════════════════════════════════════════════════
class VideoFeedScreen extends StatefulWidget {
  const VideoFeedScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<VideoFeedScreen> createState() => _VideoFeedScreenState();
}

class _VideoFeedScreenState extends State<VideoFeedScreen> {
  late Future<List<dynamic>> _videos;

  @override
  void initState() {
    super.initState();
    _videos = VideoService(widget.api).feed();
  }

  Future<void> _studio() async {
    final published = await Navigator.of(context).push<bool>(
      PageRouteBuilder<bool>(
        pageBuilder: (_, anim, __) => VideoStudioScreen(api: widget.api),
        transitionsBuilder: (_, anim, __, child) => SlideTransition(
          position: Tween(begin: const Offset(0, 1), end: Offset.zero)
              .animate(CurvedAnimation(parent: anim, curve: Curves.easeOutCubic)),
          child: child,
        ),
        transitionDuration: kSlowAnim,
      ),
    );
    if (published == true && mounted) {
      setState(() => _videos = VideoService(widget.api).feed());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: AppBar(
        backgroundColor: Colors.black.withValues(alpha: .55),
        foregroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Communauté vidéo',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800),
        ),
        actions: [
          IconButton(
            tooltip: 'Studio de création',
            onPressed: _studio,
            icon: const Icon(Icons.add_a_photo_outlined, color: Colors.white),
          ),
        ],
      ),
      body: FutureBuilder<List<dynamic>>(
        future: _videos,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return Container(
              color: Colors.black,
              child: const LoadingView(),
            );
          }
          if (snapshot.hasError) {
            return Container(
              color: Colors.black,
              child: ErrorView(
                message: readableError(snapshot.error!),
                onRetry: () =>
                    setState(() => _videos = VideoService(widget.api).feed()),
              ),
            );
          }
          if (snapshot.data!.isEmpty) {
            return _EmptyVideoState(onStudio: _studio);
          }
          final videos = snapshot.data!
              .map((row) => Map<String, dynamic>.from(row as Map))
              .toList();
          return PageView.builder(
            scrollDirection: Axis.vertical,
            itemCount: videos.length,
            itemBuilder: (context, index) =>
                _VideoCard(api: widget.api, video: videos[index]),
          );
        },
      ),
    );
  }
}

// ─── État vide stylisé ────────────────────────────────────────────────────────
class _EmptyVideoState extends StatelessWidget {
  const _EmptyVideoState({required this.onStudio});
  final VoidCallback onStudio;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: ivorianInk,
      child: Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 88,
                height: 88,
                decoration: BoxDecoration(
                  color: ivorianOrange.withValues(alpha: .15),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.video_library_outlined,
                    size: 44, color: ivorianOrange),
              ),
              const SizedBox(height: 20),
              const Text(
                'Aucune vidéo publiée',
                textAlign: TextAlign.center,
                style: TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 10),
              Text(
                'Les nouvelles vidéos sont vérifiées avant publication.',
                textAlign: TextAlign.center,
                style: TextStyle(
                    color: Colors.white.withValues(alpha: .65), fontSize: 14),
              ),
              const SizedBox(height: 28),
              FilledButton.icon(
                onPressed: onStudio,
                icon: const Icon(Icons.upload_file),
                label: const Text('Publier une vidéo'),
                style: FilledButton.styleFrom(
                  backgroundColor: ivorianOrange,
                  minimumSize: const Size(220, 48),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(kRadiusLg)),
                ),
              ),
              const SizedBox(height: 20),
              Text(
                'Les directs nécessitent un fournisseur de streaming configuré.',
                textAlign: TextAlign.center,
                style: TextStyle(
                    color: Colors.white.withValues(alpha: .4), fontSize: 12),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// _VIDEO CARD — carte fullscreen TikTok
// ═══════════════════════════════════════════════════════════════════════════════
class _VideoCard extends StatefulWidget {
  const _VideoCard({required this.api, required this.video});
  final ApiService api;
  final Map<String, dynamic> video;

  @override
  State<_VideoCard> createState() => _VideoCardState();
}

class _VideoCardState extends State<_VideoCard>
    with SingleTickerProviderStateMixin {
  VideoPlayerController? _controller;
  Object? _playerError;
  bool _liked = false;
  bool _busy = false;
  late final AnimationController _heartAnim;

  @override
  void initState() {
    super.initState();
    _heartAnim = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 300));
    _initialize();
  }

  Future<void> _initialize() async {
    try {
      final controller = VideoPlayerController.networkUrl(
          widget.api.mediaUri('${widget.video['media_url']}'));
      await controller.initialize();
      if (!mounted) {
        await controller.dispose();
        return;
      }
      setState(() => _controller = controller);
      await controller.setLooping(true);
      await controller.play();
    } catch (error) {
      if (mounted) setState(() => _playerError = error);
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    _heartAnim.dispose();
    super.dispose();
  }

  Future<void> _like() async {
    setState(() => _busy = true);
    _heartAnim.forward(from: 0);
    try {
      final liked =
          await widget.api.toggleVideoLike(int.parse('${widget.video['id']}'));
      if (mounted) setState(() => _liked = liked);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(readableError(error)),
          behavior: SnackBarBehavior.floating,
        ));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _togglePlay() {
    final c = _controller;
    if (c == null) return;
    setState(() => c.value.isPlaying ? c.pause() : c.play());
  }

  void _openComments() {
    Navigator.of(context).push(
      PageRouteBuilder<void>(
        pageBuilder: (_, anim, __) =>
            VideoDetailScreen(api: widget.api, video: widget.video),
        transitionsBuilder: (_, anim, __, child) => SlideTransition(
          position: Tween(begin: const Offset(0, 1), end: Offset.zero)
              .animate(
                  CurvedAnimation(parent: anim, curve: Curves.easeOutCubic)),
          child: child,
        ),
        transitionDuration: kNormalAnim,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    return Stack(
      fit: StackFit.expand,
      children: [
        // ── Vidéo / placeholder ──────────────────────────────────────────────
        ColoredBox(
          color: Colors.black,
          child: GestureDetector(
            onTap: _togglePlay,
            child: Center(
              child: _playerError != null
                  ? Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.videocam_off_outlined,
                            color: Colors.white54, size: 54),
                        const SizedBox(height: 8),
                        Text(
                          'Lecture impossible',
                          style: TextStyle(
                              color: Colors.white.withValues(alpha: .5)),
                        ),
                      ],
                    )
                  : controller == null
                      ? const SizedBox(
                          width: 36,
                          height: 36,
                          child: CircularProgressIndicator(
                              color: ivorianOrange, strokeWidth: 2.5),
                        )
                      : AspectRatio(
                          aspectRatio: controller.value.aspectRatio,
                          child: VideoPlayer(controller),
                        ),
            ),
          ),
        ),

        // ── Gradient overlay bas ─────────────────────────────────────────────
        const Positioned(
          left: 0,
          right: 0,
          bottom: 0,
          height: 280,
          child: DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [Colors.transparent, Colors.black87],
              ),
            ),
          ),
        ),

        // ── Infos vidéo (bas-gauche) ─────────────────────────────────────────
        Positioned(
          left: 18,
          right: 76,
          bottom: 32,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                '${widget.video['titre']}',
                style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                    fontSize: 18,
                    shadows: [Shadow(blurRadius: 6)]),
              ),
              const SizedBox(height: 5),
              Text(
                '${widget.video['auteur'] ?? ''}${widget.video['destination'] == null ? '' : ' • ${widget.video['destination']}'}',
                style: const TextStyle(color: Colors.white70, fontSize: 13),
              ),
              if (widget.video['description'] != null) ...[
                const SizedBox(height: 4),
                Text(
                  '${widget.video['description']}',
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style:
                      TextStyle(color: Colors.white.withValues(alpha: .85)),
                ),
              ],
              const SizedBox(height: 8),
              GestureDetector(
                onTap: _openComments,
                child: const Text(
                  'Voir les commentaires →',
                  style: TextStyle(
                    color: ivorianOrange,
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                  ),
                ),
              ),
            ],
          ),
        ),

        // ── Actions côté droit ───────────────────────────────────────────────
        Positioned(
          right: 10,
          bottom: 48,
          child: Column(
            children: [
              // Like animé
              ScaleTransition(
                scale: Tween(begin: 1.0, end: 1.3).animate(
                  CurvedAnimation(parent: _heartAnim, curve: Curves.elasticOut),
                ),
                child: IconButton(
                  tooltip: _liked ? 'Retirer le like' : 'Aimer',
                  onPressed: _busy ? null : _like,
                  icon: Icon(
                    _liked ? Icons.favorite : Icons.favorite_border,
                    color: _liked ? ivorianOrange : Colors.white,
                    size: 30,
                  ),
                ),
              ),
              Text(
                '${widget.video['likes'] ?? 0}',
                style: const TextStyle(color: Colors.white, fontSize: 12),
              ),
              const SizedBox(height: 20),
              // Commentaires
              IconButton(
                tooltip: 'Commentaires',
                onPressed: _openComments,
                icon: const Icon(Icons.comment_outlined,
                    color: Colors.white, size: 28),
              ),
              Text(
                '${widget.video['commentaires'] ?? 0}',
                style: const TextStyle(color: Colors.white, fontSize: 12),
              ),
            ],
          ),
        ),

        // ── Indicateur pause ─────────────────────────────────────────────────
        if (controller != null && !controller.value.isPlaying)
          Center(
            child: Container(
              width: 60,
              height: 60,
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: .5),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.play_arrow_rounded,
                  color: Colors.white, size: 36),
            ),
          ),
      ],
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// VIDEO STUDIO SCREEN — publication d'une vidéo
// ═══════════════════════════════════════════════════════════════════════════════
class VideoStudioScreen extends StatefulWidget {
  const VideoStudioScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<VideoStudioScreen> createState() => _VideoStudioScreenState();
}

class _VideoStudioScreenState extends State<VideoStudioScreen> {
  final _title = TextEditingController();
  final _description = TextEditingController();
  PlatformFile? _file;
  bool _busy = false;
  bool _rightsConfirmed = false;

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    super.dispose();
  }

  Future<void> _chooseFile() async {
    final file = await FilePicker.pickFile(type: FileType.video);
    if (file != null && mounted) {
      setState(() => _file = file);
    }
  }

  String _formatFileSize(PlatformFile file) {
    int? bytes = file.lengthSync();
    if (bytes == null && file.path != null) {
      try {
        bytes = File(file.path!).lengthSync();
      } catch (_) {}
    }
    if (bytes == null) return '';
    return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} Mo';
  }

  Future<void> _publish() async {
    if (_title.text.trim().isEmpty || _file == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Ajoutez un titre et sélectionnez une vidéo.'),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(kRadiusMd)),
        ),
      );
      return;
    }
    setState(() => _busy = true);
    try {
      await VideoService(widget.api).publish(
        _file!,
        _title.text.trim(),
        description: _description.text,
        rightsConfirmed: _rightsConfirmed,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content:
                const Text('Vidéo reçue et envoyée en modération.'),
            behavior: SnackBarBehavior.floating,
            backgroundColor: const Color(0xFF2E7D32),
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(kRadiusMd)),
          ),
        );
        Navigator.of(context).pop(true);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(readableError(error)),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Scaffold(
      appBar: AppBar(title: const Text('Studio de création')),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          // ── En-tête décoratif ──────────────────────────────────────────────
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  ivorianOrange.withValues(alpha: .10),
                  ivorianForest.withValues(alpha: .08),
                ],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(kRadiusLg),
            ),
            child: Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: ivorianOrange.withValues(alpha: .15),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.movie_creation_outlined,
                      color: ivorianOrange, size: 26),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Partager votre expérience',
                        style: TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 16,
                            color: cs.onSurface),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Vidéo modérée avant publication',
                        style: TextStyle(
                            fontSize: 12,
                            color: cs.onSurface.withValues(alpha: .55)),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // ── Titre ──────────────────────────────────────────────────────────
          TextField(
            controller: _title,
            maxLength: 180,
            decoration: InputDecoration(
              labelText: 'Titre de la vidéo',
              prefixIcon: const Icon(Icons.title),
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(kRadiusMd)),
            ),
          ),
          const SizedBox(height: 14),

          // ── Description ────────────────────────────────────────────────────
          TextField(
            controller: _description,
            maxLines: 4,
            decoration: InputDecoration(
              labelText: 'Description (facultatif)',
              alignLabelWithHint: true,
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(kRadiusMd)),
            ),
          ),
          const SizedBox(height: 16),

          // ── Sélecteur fichier ──────────────────────────────────────────────
          AnimatedContainer(
            duration: kNormalAnim,
            decoration: BoxDecoration(
              color: _file != null
                  ? ivorianForest.withValues(alpha: .08)
                  : cs.surfaceContainerHighest.withValues(alpha: .5),
              borderRadius: BorderRadius.circular(kRadiusMd),
              border: Border.all(
                color: _file != null
                    ? ivorianForest.withValues(alpha: .4)
                    : cs.outlineVariant,
              ),
            ),
            child: ListTile(
              contentPadding:
                  const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              leading: Icon(
                _file != null
                    ? Icons.video_file
                    : Icons.video_file_outlined,
                color: _file != null ? ivorianForest : cs.onSurfaceVariant,
                size: 28,
              ),
              title: Text(
                _file?.name ?? 'Choisir une vidéo (MP4, WebM ou MOV)',
                style: TextStyle(
                  fontWeight:
                      _file != null ? FontWeight.w700 : FontWeight.w400,
                  color: _file != null
                      ? ivorianForest
                      : cs.onSurface.withValues(alpha: .6),
                ),
              ),
              subtitle: _file != null
                  ? Text(
                      _formatFileSize(_file!),
                      style: const TextStyle(fontSize: 12),
                    )
                  : null,
              trailing: OutlinedButton(
                onPressed: _busy ? null : _chooseFile,
                style: OutlinedButton.styleFrom(
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(kRadiusMd)),
                ),
                child: Text(_file != null ? 'Changer' : 'Parcourir'),
              ),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Taille maximale : 100 Mo. La vidéo sera modérée avant publication.',
            style: TextStyle(
                fontSize: 12, color: cs.onSurface.withValues(alpha: .5)),
          ),
          const SizedBox(height: 16),

          // ── Droits ────────────────────────────────────────────────────────
          Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(kRadiusMd),
              border: Border.all(color: cs.outlineVariant),
            ),
            child: CheckboxListTile(
              contentPadding:
                  const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
              value: _rightsConfirmed,
              activeColor: ivorianOrange,
              onChanged: _busy
                  ? null
                  : (value) =>
                      setState(() => _rightsConfirmed = value ?? false),
              title: const Text(
                'Je confirme détenir les droits de diffusion de cette vidéo.',
                style: TextStyle(fontSize: 13),
              ),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(kRadiusMd)),
            ),
          ),
          const SizedBox(height: 28),

          // ── Bouton envoi ──────────────────────────────────────────────────
          IvorianButton(
            label: _busy ? 'Envoi…' : 'Envoyer pour modération',
            icon: _busy ? null : Icons.send_outlined,
            onPressed: _busy ? null : _publish,
            useGradient: true,
            height: 52,
          ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// VIDEO DETAIL SCREEN — commentaires
// ═══════════════════════════════════════════════════════════════════════════════
class VideoDetailScreen extends StatefulWidget {
  const VideoDetailScreen(
      {required this.api, required this.video, super.key});
  final ApiService api;
  final Map<String, dynamic> video;

  @override
  State<VideoDetailScreen> createState() => _VideoDetailScreenState();
}

class _VideoDetailScreenState extends State<VideoDetailScreen> {
  final _comment = TextEditingController();
  late Future<List<dynamic>> _comments;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _comments =
        widget.api.getComments(int.parse('${widget.video['id']}'));
  }

  @override
  void dispose() {
    _comment.dispose();
    super.dispose();
  }

  Future<void> _sendComment() async {
    if (_comment.text.trim().isEmpty) return;
    setState(() => _sending = true);
    try {
      await widget.api
          .addComment(int.parse('${widget.video['id']}'), _comment.text.trim());
      _comment.clear();
      if (mounted) {
        setState(() =>
            _comments = widget.api.getComments(int.parse('${widget.video['id']}')));
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(readableError(error)),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Scaffold(
      appBar: AppBar(
        title: Text(
          '${widget.video['titre']}',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        centerTitle: false,
      ),
      body: Column(
        children: [
          // ── Liste commentaires ───────────────────────────────────────────
          Expanded(
            child: FutureBuilder<List<dynamic>>(
              future: _comments,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const LoadingView();
                }
                if (snapshot.hasError) {
                  return ErrorView(
                    message: readableError(snapshot.error!),
                    onRetry: () => setState(() => _comments =
                        widget.api.getComments(
                            int.parse('${widget.video['id']}'))),
                  );
                }
                if (snapshot.data!.isEmpty) {
                  return const EmptyView(
                    message: 'Aucun commentaire. Soyez le premier !',
                    icon: Icons.comment_outlined,
                  );
                }
                return ListView.builder(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 16, vertical: 12),
                  itemCount: snapshot.data!.length,
                  itemBuilder: (context, index) {
                    final comment = Map<String, dynamic>.from(
                        snapshot.data![index] as Map);
                    return FadeSlideIn(
                      delay: Duration(
                          milliseconds:
                              index * kStaggerDelay.inMilliseconds),
                      child: Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            CircleAvatar(
                              radius: 18,
                              backgroundColor:
                                  ivorianOrange.withValues(alpha: .15),
                              child: Text(
                                ('${comment['auteur']}').isNotEmpty
                                    ? '${comment['auteur']}'[0].toUpperCase()
                                    : '?',
                                style: const TextStyle(
                                    color: ivorianOrange,
                                    fontWeight: FontWeight.w800),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: cs.surfaceContainerHighest,
                                  borderRadius: const BorderRadius.only(
                                    topRight: Radius.circular(kRadiusMd),
                                    bottomLeft: Radius.circular(kRadiusMd),
                                    bottomRight: Radius.circular(kRadiusMd),
                                  ),
                                ),
                                child: Column(
                                  crossAxisAlignment:
                                      CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      '${comment['auteur']}',
                                      style: const TextStyle(
                                          fontWeight: FontWeight.w700,
                                          fontSize: 13),
                                    ),
                                    const SizedBox(height: 3),
                                    Text(
                                      '${comment['contenu']}',
                                      style: const TextStyle(
                                          fontSize: 14, height: 1.4),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                );
              },
            ),
          ),

          // ── Saisie commentaire ──────────────────────────────────────────
          SafeArea(
            child: Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(
                color: cs.surface,
                border: Border(
                    top: BorderSide(
                        color: cs.outlineVariant.withValues(alpha: .4))),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _comment,
                      maxLength: 2000,
                      textInputAction: TextInputAction.send,
                      onSubmitted: (_) => _sendComment(),
                      decoration: InputDecoration(
                        hintText: 'Écrire un commentaire…',
                        counterText: '',
                        filled: true,
                        fillColor:
                            cs.surfaceContainerHighest.withValues(alpha: .6),
                        contentPadding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 10),
                        border: OutlineInputBorder(
                          borderRadius:
                              BorderRadius.circular(kRadiusXl),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  AnimatedScale(
                    scale: _sending ? 0.85 : 1.0,
                    duration: kFastAnim,
                    child: IconButton.filled(
                      tooltip: 'Envoyer',
                      onPressed: _sending ? null : _sendComment,
                      style: IconButton.styleFrom(
                        backgroundColor: ivorianOrange,
                        minimumSize: const Size(44, 44),
                      ),
                      icon: _sending
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                  color: Colors.white, strokeWidth: 2),
                            )
                          : const Icon(Icons.send, color: Colors.white),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
