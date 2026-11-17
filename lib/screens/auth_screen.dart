import 'package:material_ui/material_ui.dart';

import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/app_alert.dart';
import '../widgets/common.dart';
import 'profile_photo_setup_screen.dart';

enum _AuthMode { login, register, forgot, reset }

class AuthScreen extends StatefulWidget {
  const AuthScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final _name = TextEditingController();
  final _firstName = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _passwordConfirmation = TextEditingController();
  final _token = TextEditingController();
  _AuthMode _mode = _AuthMode.login;
  bool _busy = false;
  bool _obscure = true;

  String? _validationError() {
    if (_mode == _AuthMode.register) {
      if (_name.text.trim().isEmpty || _name.text.trim().length > 100) {
        return 'Saisissez un nom de 100 caractères maximum.';
      }
      if (_firstName.text.length > 100) return '100 caractères maximum.';
      if (_phone.text.length > 30) return '30 caractères maximum.';
    }
    if (!_email.text.contains('@')) return 'E-mail invalide.';
    if (_mode == _AuthMode.reset && _token.text.trim().isEmpty) {
      return 'Saisissez le code reçu.';
    }
    if (_mode != _AuthMode.forgot) {
      if (_mode == _AuthMode.login && _password.text.isEmpty) {
        return 'Saisissez votre mot de passe.';
      }
      if (_mode != _AuthMode.login && _password.text.length < 10) {
        return 'Minimum 10 caractères.';
      }
    }
    if (_mode == _AuthMode.register &&
        _passwordConfirmation.text != _password.text) {
      return 'Les mots de passe ne correspondent pas.';
    }
    return null;
  }

  @override
  void dispose() {
    _name.dispose();
    _firstName.dispose();
    _phone.dispose();
    _email.dispose();
    _password.dispose();
    _passwordConfirmation.dispose();
    _token.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final validationError = _validationError();
    if (validationError != null) {
      await AppAlert.warning(context, validationError);
      return;
    }
    setState(() => _busy = true);
    try {
      switch (_mode) {
        case _AuthMode.login:
          await widget.api.login(_email.text, _password.text);
          if (mounted) Navigator.of(context).pop(true);
          break;
        case _AuthMode.register:
          await widget.api.register(
            name: _name.text,
            firstName: _firstName.text,
            phone: _phone.text,
            email: _email.text,
            password: _password.text,
          );
          if (!mounted) break;
          await Navigator.of(context).push<bool>(
            MaterialPageRoute<bool>(
              builder: (_) => ProfilePhotoSetupScreen(api: widget.api),
            ),
          );
          if (mounted) Navigator.of(context).pop(true);
          break;
        case _AuthMode.forgot:
          await widget.api.forgotPassword(_email.text);
          if (mounted) {
            await AppAlert.info(
              context,
              'Si le compte existe, un code sera envoyé à cette adresse.',
            );
            setState(() => _mode = _AuthMode.reset);
          }
          break;
        case _AuthMode.reset:
          await widget.api.resetPassword(
            email: _email.text,
            token: _token.text.trim(),
            password: _password.text,
          );
          if (mounted) {
            await AppAlert.success(
              context,
              'Mot de passe réinitialisé. Connectez-vous.',
            );
            setState(() => _mode = _AuthMode.login);
          }
          break;
      }
    } catch (error) {
      if (mounted) {
        await AppAlert.error(context, readableError(error));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  String get _title => switch (_mode) {
        _AuthMode.login => 'Connexion',
        _AuthMode.register => 'Créer un compte',
        _AuthMode.forgot => 'Mot de passe oublié',
        _AuthMode.reset => 'Réinitialiser',
      };

  String get _submitLabel => switch (_mode) {
        _AuthMode.login => 'Se connecter',
        _AuthMode.register => 'Créer mon compte',
        _AuthMode.forgot => 'Envoyer le code',
        _AuthMode.reset => 'Réinitialiser',
      };

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Scaffold(
      backgroundColor: cs.surface,
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 280,
            pinned: true,
            stretch: true,
            backgroundColor: cs.surface,
            surfaceTintColor: cs.surface,
            elevation: 0,
            scrolledUnderElevation: 1,
            leading: IconButton(
              tooltip: 'Retour',
              icon: const Icon(Icons.arrow_back_rounded),
              onPressed: () => Navigator.of(context).pop(),
            ),
            flexibleSpace: FlexibleSpaceBar(
              stretchModes: const [
                StretchMode.zoomBackground,
                StretchMode.blurBackground,
              ],
              background: Stack(
                fit: StackFit.expand,
                children: [
                  Image.asset(
                    'lib/images/Tourisme.jpg',
                    fit: BoxFit.cover,
                  ),
                  DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.black.withValues(alpha: 0.10),
                          Colors.black.withValues(alpha: 0.55),
                          Colors.black.withValues(alpha: 0.82),
                        ],
                        stops: const [0, .55, 1],
                      ),
                    ),
                  ),
                  Positioned(
                    top: -60,
                    right: -60,
                    child: _GlowBlob(
                      size: 220,
                      color: Colors.white.withValues(alpha: 0.10),
                    ),
                  ),
                  Positioned(
                    top: 40,
                    left: -50,
                    child: _GlowBlob(
                      size: 160,
                      color: ivorianOrange.withValues(alpha: 0.18),
                    ),
                  ),
                  Positioned(
                    bottom: -20,
                    right: 30,
                    child: _GlowBlob(
                      size: 80,
                      color: Colors.white.withValues(alpha: 0.06),
                    ),
                  ),
                  SafeArea(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(24, 32, 24, 32),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          Container(
                            width: 60,
                            height: 60,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: Colors.white.withValues(alpha: 0.18),
                              border: Border.all(
                                color: Colors.white.withValues(alpha: 0.55),
                                width: 2,
                              ),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: .2),
                                  blurRadius: 18,
                                  offset: const Offset(0, 6),
                                ),
                              ],
                            ),
                            child: const Icon(
                              Icons.travel_explore_rounded,
                              color: Colors.white,
                              size: 30,
                            ),
                          ),
                          const SizedBox(height: 14),
                          const Text(
                            'Tourisme Ivoire',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 28,
                              fontWeight: FontWeight.w900,
                              letterSpacing: -0.7,
                              height: 1.1,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'Explorez la Côte d\'Ivoire autrement',
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.85),
                              fontSize: 14.5,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(24, 24, 24, 32),
              child: Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 480),
                  child: Form(
                    child: AnimatedSwitcher(
                      duration: kNormalAnim,
                      switchInCurve: Curves.easeOut,
                      switchOutCurve: Curves.easeIn,
                      transitionBuilder: (child, anim) => FadeTransition(
                        opacity: anim,
                        child: SlideTransition(
                          position: Tween<Offset>(
                            begin: const Offset(0, 0.05),
                            end: Offset.zero,
                          ).animate(anim),
                          child: child,
                        ),
                      ),
                      child: _FormContent(
                        key: ValueKey(_mode),
                        mode: _mode,
                        title: _title,
                        submitLabel: _submitLabel,
                        busy: _busy,
                        obscure: _obscure,
                        nameCtrl: _name,
                        firstNameCtrl: _firstName,
                        phoneCtrl: _phone,
                        emailCtrl: _email,
                        passwordCtrl: _password,
                        passwordConfirmationCtrl: _passwordConfirmation,
                        tokenCtrl: _token,
                        onToggleObscure: () =>
                            setState(() => _obscure = !_obscure),
                        onSubmit: _submit,
                        onModeChange: (m) => setState(() => _mode = m),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _GlowBlob extends StatelessWidget {
  const _GlowBlob({required this.size, required this.color});
  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: RadialGradient(
          colors: [color, color.withValues(alpha: 0)],
        ),
      ),
    );
  }
}

class _FormContent extends StatelessWidget {
  const _FormContent({
    required this.mode,
    required this.title,
    required this.submitLabel,
    required this.busy,
    required this.obscure,
    required this.nameCtrl,
    required this.firstNameCtrl,
    required this.phoneCtrl,
    required this.emailCtrl,
    required this.passwordCtrl,
    required this.passwordConfirmationCtrl,
    required this.tokenCtrl,
    required this.onToggleObscure,
    required this.onSubmit,
    required this.onModeChange,
    super.key,
  });

  final _AuthMode mode;
  final String title;
  final String submitLabel;
  final bool busy;
  final bool obscure;
  final TextEditingController nameCtrl;
  final TextEditingController firstNameCtrl;
  final TextEditingController phoneCtrl;
  final TextEditingController emailCtrl;
  final TextEditingController passwordCtrl;
  final TextEditingController passwordConfirmationCtrl;
  final TextEditingController tokenCtrl;
  final VoidCallback onToggleObscure;
  final VoidCallback onSubmit;
  final ValueChanged<_AuthMode> onModeChange;

  InputDecoration _decoration({
    required BuildContext context,
    required String label,
    required IconData icon,
    Widget? suffix,
  }) {
    final colorScheme = Theme.of(context).colorScheme;
    return InputDecoration(
      labelText: label,
      prefixIcon: Icon(icon, size: 20),
      suffixIcon: suffix,
      filled: true,
      fillColor: colorScheme.surfaceContainerHighest.withValues(alpha: 0.4),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(kRadiusMd),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(kRadiusMd),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(kRadiusMd),
        borderSide: BorderSide(
          color: ivorianOrange.withValues(alpha: 0.8),
          width: 1.5,
        ),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(kRadiusMd),
        borderSide: const BorderSide(color: ivorianTerracotta, width: 1.2),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(kRadiusMd),
        borderSide: const BorderSide(color: ivorianTerracotta, width: 1.5),
      ),
      contentPadding: const EdgeInsets.symmetric(
        horizontal: 16,
        vertical: 18,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Center(
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
            decoration: BoxDecoration(
              color: ivorianOrange.withValues(alpha: .12),
              borderRadius: BorderRadius.circular(50),
            ),
            child: Text(
              _subtitle.toUpperCase(),
              style: const TextStyle(
                color: ivorianOrange,
                fontSize: 10,
                fontWeight: FontWeight.w800,
                letterSpacing: 1.2,
              ),
            ),
          ),
        ),
        const SizedBox(height: 14),
        Text(
          title,
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.w900,
                letterSpacing: -0.5,
              ),
        ),
        const SizedBox(height: 28),
        if (mode == _AuthMode.register) ...[
          TextFormField(
            controller: nameCtrl,
            textCapitalization: TextCapitalization.words,
            textInputAction: TextInputAction.next,
            decoration: _decoration(
              context: context,
              label: 'Nom',
              icon: Icons.person_outline_rounded,
            ),
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: firstNameCtrl,
            textCapitalization: TextCapitalization.words,
            textInputAction: TextInputAction.next,
            decoration: _decoration(
              context: context,
              label: 'Prénom (facultatif)',
              icon: Icons.person_outline_rounded,
            ),
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: phoneCtrl,
            keyboardType: TextInputType.phone,
            textInputAction: TextInputAction.next,
            decoration: _decoration(
              context: context,
              label: 'Téléphone (facultatif)',
              icon: Icons.phone_outlined,
            ),
          ),
          const SizedBox(height: 14),
        ],
        TextFormField(
          controller: emailCtrl,
          keyboardType: TextInputType.emailAddress,
          maxLength: 190,
          textInputAction: TextInputAction.next,
          decoration: _decoration(
            context: context,
            label: 'Adresse e-mail',
            icon: Icons.mail_outline_rounded,
          ).copyWith(counterText: ''),
        ),
        if (mode == _AuthMode.reset) ...[
          const SizedBox(height: 14),
          TextFormField(
            controller: tokenCtrl,
            textInputAction: TextInputAction.next,
            decoration: _decoration(
              context: context,
              label: 'Code de réinitialisation',
              icon: Icons.key_rounded,
            ),
          ),
        ],
        if (mode != _AuthMode.forgot) ...[
          const SizedBox(height: 14),
          TextFormField(
            controller: passwordCtrl,
            obscureText: obscure,
            textInputAction: TextInputAction.done,
            onFieldSubmitted: (_) => onSubmit(),
            decoration: _decoration(
              context: context,
              label: 'Mot de passe',
              icon: Icons.lock_outline_rounded,
              suffix: IconButton(
                tooltip: obscure ? 'Afficher' : 'Masquer',
                icon: Icon(
                  obscure
                      ? Icons.visibility_outlined
                      : Icons.visibility_off_outlined,
                  size: 20,
                ),
                onPressed: onToggleObscure,
              ),
            ),
          ),
          if (mode == _AuthMode.register) ...[
            const SizedBox(height: 14),
            TextFormField(
              controller: passwordConfirmationCtrl,
              obscureText: obscure,
              textInputAction: TextInputAction.done,
              onFieldSubmitted: (_) => onSubmit(),
              decoration: _decoration(
                context: context,
                label: 'Confirmer le mot de passe',
                icon: Icons.lock_outline_rounded,
              ),
            ),
          ],
        ],
        if (mode == _AuthMode.login) ...[
          Align(
            alignment: Alignment.centerRight,
            child: TextButton(
              onPressed: () => onModeChange(_AuthMode.forgot),
              style: TextButton.styleFrom(
                padding:
                    const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
              ),
              child: const Text(
                'Mot de passe oublié ?',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
              ),
            ),
          ),
        ] else ...[
          const SizedBox(height: 20),
        ],
        const SizedBox(height: 8),
        IvorianButton(
          label: submitLabel,
          useGradient: true,
          loading: busy,
          onPressed: busy ? null : onSubmit,
        ),
        const SizedBox(height: 22),
        Wrap(
          alignment: WrapAlignment.center,
          crossAxisAlignment: WrapCrossAlignment.center,
          spacing: 4,
          children: [
            Text(
              _switchText,
              style: TextStyle(color: cs.onSurfaceVariant, fontSize: 14),
            ),
            GestureDetector(
              onTap: () => onModeChange(_switchMode),
              child: Text(
                _switchLabel,
                style: const TextStyle(
                  color: ivorianOrange,
                  fontWeight: FontWeight.w800,
                  fontSize: 14,
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }

  String get _subtitle => switch (mode) {
        _AuthMode.login => 'Bon retour parmi nous !',
        _AuthMode.register => 'Rejoignez la communauté Tourisme Ivoire.',
        _AuthMode.forgot => 'Recevez un code de réinitialisation.',
        _AuthMode.reset => 'Choisissez un nouveau mot de passe.',
      };

  String get _switchText => switch (mode) {
        _AuthMode.login => 'Pas encore de compte ?',
        _AuthMode.register => 'Déjà inscrit ?',
        _AuthMode.forgot => 'Vous vous en souvenez ?',
        _AuthMode.reset => 'Retour à la',
      };

  String get _switchLabel => switch (mode) {
        _AuthMode.login => 'Créer un compte',
        _AuthMode.register => 'Se connecter',
        _AuthMode.forgot => 'Se connecter',
        _AuthMode.reset => 'connexion',
      };

  _AuthMode get _switchMode => switch (mode) {
        _AuthMode.login => _AuthMode.register,
        _AuthMode.register => _AuthMode.login,
        _AuthMode.forgot => _AuthMode.login,
        _AuthMode.reset => _AuthMode.login,
      };
}