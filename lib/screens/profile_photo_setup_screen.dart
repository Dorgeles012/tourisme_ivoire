import 'package:file_picker/file_picker.dart';
import 'package:material_ui/material_ui.dart';

import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';

class ProfilePhotoSetupScreen extends StatefulWidget {
  const ProfilePhotoSetupScreen({required this.api, super.key});

  final ApiService api;

  @override
  State<ProfilePhotoSetupScreen> createState() =>
      _ProfilePhotoSetupScreenState();
}

class _ProfilePhotoSetupScreenState extends State<ProfilePhotoSetupScreen> {
  bool _busy = false;
  bool _canPop = false;

  Future<void> _finish() async {
    setState(() => _canPop = true);
    await WidgetsBinding.instance.endOfFrame;
    if (!mounted) return;
    Navigator.of(context).pop(true);
  }

  Future<void> _addPhoto() async {
    setState(() => _busy = true);
    try {
      final file = await FilePicker.pickFile(type: FileType.image);
      if (file == null) return;
      await widget.api.uploadAvatar(file);
      if (mounted) await _finish();
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(readableError(error))));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _continueWithoutPhoto() => _finish();

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return PopScope(
      canPop: _canPop,
      child: Scaffold(
        appBar: AppBar(
          automaticallyImplyLeading: false,
          title: const Text('Personnaliser mon espace'),
          centerTitle: true,
        ),
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 480),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 132,
                      height: 132,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: ivorianOrange.withValues(alpha: .12),
                        border: Border.all(
                          color: ivorianOrange.withValues(alpha: .3),
                          width: 2,
                        ),
                      ),
                      child: Icon(
                        Icons.add_a_photo_outlined,
                        size: 54,
                        color: ivorianOrange.withValues(alpha: .85),
                      ),
                    ),
                    const SizedBox(height: 28),
                    Text(
                      'Bienvenue sur Tourisme Ivoire !',
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.headlineSmall
                          ?.copyWith(fontWeight: FontWeight.w900),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'Ajoutez une photo de profil pour personnaliser votre espace voyageur. Cette étape est facultative et vous pourrez la faire plus tard.',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: colors.onSurfaceVariant,
                        height: 1.5,
                      ),
                    ),
                    const SizedBox(height: 32),
                    IvorianButton(
                      label: 'Ajouter une photo',
                      icon: Icons.photo_library_outlined,
                      loading: _busy,
                      useGradient: true,
                      onPressed: _busy ? null : _addPhoto,
                    ),
                    const SizedBox(height: 12),
                    TextButton(
                      onPressed: _busy ? null : _continueWithoutPhoto,
                      child: const Text('Ignorer pour le moment'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
