import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';

import '../services/api_service.dart';
import '../services/video_service.dart';
import '../theme.dart';
import '../widgets/common.dart';

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
    final published = await Navigator.of(context).push<bool>(MaterialPageRoute<bool>(builder: (_) => VideoStudioScreen(api: widget.api)));
    if (published == true && mounted) setState(() => _videos = VideoService(widget.api).feed());
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(
          title: const Text('Communauté vidéo'),
          actions: [IconButton(tooltip: 'Studio de création', onPressed: _studio, icon: const Icon(Icons.add_a_photo_outlined))],
        ),
        body: FutureBuilder<List<dynamic>>(
          future: _videos,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
            if (snapshot.hasError) return ErrorView(message: readableError(snapshot.error!), onRetry: () => setState(() => _videos = VideoService(widget.api).feed()));
            if (snapshot.data!.isEmpty) {
              return Center(
                child: Padding(
                  padding: const EdgeInsets.all(28),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.video_library_outlined, size: 56, color: ivorianGreen),
                      const SizedBox(height: 12),
                      const Text('Aucune vidéo publiée.', textAlign: TextAlign.center),
                      const SizedBox(height: 8),
                      const Text('Les nouvelles vidéos sont vérifiées avant publication.', textAlign: TextAlign.center),
                      const SizedBox(height: 16),
                      FilledButton.icon(onPressed: _studio, icon: const Icon(Icons.upload_file), label: const Text('Publier une vidéo')),
                      const SizedBox(height: 18),
                      const Text('Les directs nécessitent un fournisseur de streaming configuré.', textAlign: TextAlign.center, style: TextStyle(fontSize: 12)),
                    ],
                  ),
                ),
              );
            }
            final videos = snapshot.data!.map((row) => Map<String, dynamic>.from(row as Map)).toList();
            return PageView.builder(
              scrollDirection: Axis.vertical,
              itemCount: videos.length,
              itemBuilder: (context, index) => _VideoCard(api: widget.api, video: videos[index]),
            );
          },
        ),
      );
}

class _VideoCard extends StatefulWidget {
  const _VideoCard({required this.api, required this.video});
  final ApiService api;
  final Map<String, dynamic> video;

  @override
  State<_VideoCard> createState() => _VideoCardState();
}

class _VideoCardState extends State<_VideoCard> {
  VideoPlayerController? _controller;
  Object? _playerError;
  bool _liked = false;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _initialize();
  }

  Future<void> _initialize() async {
    try {
      final controller = VideoPlayerController.networkUrl(widget.api.mediaUri('${widget.video['media_url']}'));
      await controller.initialize();
      if (!mounted) {
        await controller.dispose();
        return;
      }
      setState(() => _controller = controller);
      await controller.setLooping(true);
    } catch (error) {
      if (mounted) setState(() => _playerError = error);
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  Future<void> _like() async {
    setState(() => _busy = true);
    try {
      final liked = await widget.api.toggleVideoLike(int.parse('${widget.video['id']}'));
      if (mounted) setState(() => _liked = liked);
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    return Stack(
      fit: StackFit.expand,
      children: [
        ColoredBox(
          color: Colors.black,
          child: Center(
            child: _playerError != null
                ? const Icon(Icons.videocam_off_outlined, color: Colors.white, size: 54)
                : controller == null
                    ? const CircularProgressIndicator(color: Colors.white)
                    : GestureDetector(
                        onTap: () => setState(() => controller.value.isPlaying ? controller.pause() : controller.play()),
                        child: AspectRatio(aspectRatio: controller.value.aspectRatio, child: VideoPlayer(controller)),
                      ),
          ),
        ),
        Positioned(
          left: 18,
          right: 76,
          bottom: 28,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('${widget.video['titre']}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 19)),
              const SizedBox(height: 6),
              Text('${widget.video['auteur'] ?? ''}${widget.video['destination'] == null ? '' : ' • ${widget.video['destination']}'}', style: const TextStyle(color: Colors.white70)),
              if (widget.video['description'] != null) ...[
                const SizedBox(height: 5),
                Text('${widget.video['description']}', maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white)),
              ],
              TextButton(
                onPressed: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => VideoDetailScreen(api: widget.api, video: widget.video))),
                child: const Text('Commentaires', style: TextStyle(color: Colors.white)),
              ),
            ],
          ),
        ),
        Positioned(
          right: 12,
          bottom: 52,
          child: Column(
            children: [
              IconButton(onPressed: _busy ? null : _like, icon: Icon(_liked ? Icons.favorite : Icons.favorite_border, color: Colors.white, size: 30)),
              Text('${widget.video['likes'] ?? ''}', style: const TextStyle(color: Colors.white)),
              const SizedBox(height: 16),
              IconButton(onPressed: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => VideoDetailScreen(api: widget.api, video: widget.video))), icon: const Icon(Icons.comment_outlined, color: Colors.white, size: 28)),
            ],
          ),
        ),
      ],
    );
  }
}

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
    final result = await FilePicker.platform.pickFiles(type: FileType.video, withData: false);
    if (result != null && mounted) setState(() => _file = result.files.single);
  }

  Future<void> _publish() async {
    if (_title.text.trim().isEmpty || _file == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Ajoutez un titre et sélectionnez une vidéo.')));
      return;
    }
    setState(() => _busy = true);
    try {
      await VideoService(widget.api).publish(_file!, _title.text.trim(), description: _description.text, rightsConfirmed: _rightsConfirmed);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Vidéo reçue et envoyée en modération.')));
        Navigator.of(context).pop(true);
      }
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Studio de création')),
        body: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            TextField(controller: _title, maxLength: 180, decoration: const InputDecoration(labelText: 'Titre')),
            const SizedBox(height: 12),
            TextField(controller: _description, maxLines: 4, decoration: const InputDecoration(labelText: 'Description')),
            const SizedBox(height: 14),
            OutlinedButton.icon(onPressed: _busy ? null : _chooseFile, icon: const Icon(Icons.video_file_outlined), label: Text(_file?.name ?? 'Choisir une vidéo (MP4, WebM ou MOV)')),
            const SizedBox(height: 8),
            const Text('Taille maximale : 100 Mo. La vidéo sera modérée avant publication.', style: TextStyle(fontSize: 12)),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              value: _rightsConfirmed,
              onChanged: _busy ? null : (value) => setState(() => _rightsConfirmed = value ?? false),
              title: const Text('Je confirme détenir les droits de diffusion de cette vidéo.'),
            ),
            const SizedBox(height: 18),
            FilledButton(onPressed: _busy ? null : _publish, child: Text(_busy ? 'Envoi…' : 'Envoyer pour modération')),
          ],
        ),
      );
}

class VideoDetailScreen extends StatefulWidget {
  const VideoDetailScreen({required this.api, required this.video, super.key});
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
    _comments = widget.api.getComments(int.parse('${widget.video['id']}'));
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
      await widget.api.addComment(int.parse('${widget.video['id']}'), _comment.text.trim());
      _comment.clear();
      if (mounted) setState(() => _comments = widget.api.getComments(int.parse('${widget.video['id']}')));
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: Text('${widget.video['titre']}')),
        body: Column(
          children: [
            Expanded(
              child: FutureBuilder<List<dynamic>>(
                future: _comments,
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) return const LoadingView();
                  if (snapshot.hasError) return ErrorView(message: readableError(snapshot.error!), onRetry: () => setState(() => _comments = widget.api.getComments(int.parse('${widget.video['id']}'))));
                  if (snapshot.data!.isEmpty) return const EmptyView(message: 'Aucun commentaire.');
                  return ListView(
                    padding: const EdgeInsets.all(12),
                    children: snapshot.data!.map((row) {
                      final comment = Map<String, dynamic>.from(row as Map);
                      return ListTile(leading: const CircleAvatar(child: Icon(Icons.person_outline)), title: Text('${comment['auteur']}'), subtitle: Text('${comment['contenu']}'));
                    }).toList(),
                  );
                },
              ),
            ),
            SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(10),
                child: Row(
                  children: [
                    Expanded(child: TextField(controller: _comment, maxLength: 2000, decoration: const InputDecoration(hintText: 'Écrire un commentaire', counterText: ''))),
                    IconButton(onPressed: _sending ? null : _sendComment, icon: const Icon(Icons.send, color: ivorianOrange)),
                  ],
                ),
              ),
            ),
          ],
        ),
      );
}
