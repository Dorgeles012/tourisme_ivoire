// lib/screens/auth_screen.dart
// Écran Authentification — redesigné avec header orange décoratif
import 'package:material_ui/material_ui.dart';

import '../services/api_service.dart';
import '../theme/design_system.dart';
import '../widgets/common.dart';

enum _AuthMode { login, register, forgot, reset }

class AuthScreen extends StatefulWidget {
  const AuthScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final _form     = GlobalKey<FormState>();
  final _name     = TextEditingController();
  final _email    = TextEditingController();
  final _password = TextEditingController();
  final _token    = TextEditingController();
  _AuthMode _mode = _AuthMode.login;
  bool _busy    = false;
  bool _obscure = true;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    _token.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_form.currentState!.validate()) return;
    setState(() => _busy = true);
    try {
      switch (_mode) {
        case _AuthMode.login:
          await widget.api.login(_email.text, _password.text);
          if (mounted) Navigator.of(context).pop(true);
        case _AuthMode.register:
          await widget.api.register(name: _name.text, email: _email.text, password: _password.text);
          if (mounted) {
            await showDialog<void>(
              context: context,
              builder: (ctx) => AlertDialog(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(kRadiusLg)),
                title: const Text('Compte créé', style: TextStyle(fontWeight: FontWeight.w800)),
                content: const Text('Votre compte a été créé avec succès.'),
                actions: [
                  FilledButton(
                    onPressed: () => Navigator.of(ctx).pop(),
                    child: const Text('Continuer'),
                  ),
                ],
              ),
            );
            if (mounted) Navigator.of(context).pop(true);
          }
        case _AuthMode.forgot:
          await widget.api.forgotPassword(_email.text);
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Si le compte existe, un lien sera envoyé à cette adresse.')),
            );
            setState(() => _mode = _AuthMode.reset);
          }
        case _AuthMode.reset:
          await widget.api.resetPassword(
            email: _email.text,
            token: _token.text.trim(),
            password: _password.text,
          );
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Mot de passe réinitialisé. Connectez-vous.')),
            );
            setState(() => _mode = _AuthMode.login);
          }
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(readableError(error))),
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  String get _title => switch (_mode) {
    _AuthMode.login    => 'Connexion',
    _AuthMode.register => 'Créer un compte',
    _AuthMode.forgot   => 'Mot de passe oublié',
    _AuthMode.reset    => 'Réinitialiser',
  };

  String get _submitLabel => switch (_mode) {
    _AuthMode.login    => 'Se connecter',
    _AuthMode.register => 'Créer mon compte',
    _AuthMode.forgot   => 'Envoyer le lien',
    _AuthMode.reset    => 'Réinitialiser',
  };

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // ── Header orange décoratif ──────────────────────
          Positioned(
            top: 0, left: 0, right: 0,
            child: Container(
              height: 260,
              decoration: const BoxDecoration(
                gradient: kHeroGradient,
              ),
              child: Stack(
                children: [
                  // Cercles décoratifs
                  Positioned(
                    top: -50, right: -50,
                    child: Container(
                      width: 180, height: 180,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.white.withValues(alpha: 0.08),
                      ),
                    ),
                  ),
                  Positioned(
                    top: 40, left: -40,
                    child: Container(
                      width: 120, height: 120,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.white.withValues(alpha: 0.06),
                      ),
                    ),
                  ),
                  // Bouton retour
                  SafeArea(
                    child: Align(
                      alignment: Alignment.topLeft,
                      child: IconButton(
                        tooltip: 'Retour',
                        icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
                        onPressed: () => Navigator.of(context).pop(),
                      ),
                    ),
                  ),
                  // Logo + titre
                  Positioned(
                    bottom: 32, left: 0, right: 0,
                    child: Column(
                      children: [
                        Container(
                          width: 70, height: 70,
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.2),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.landscape_rounded, size: 36, color: Colors.white),
                        ),
                        const SizedBox(height: 10),
                        Text(
                          'Tourisme Ivoire',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.9),
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),

          // ── Formulaire (carte blanche arrondie) ─────────
          Align(
            alignment: Alignment.bottomCenter,
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Espace pour le header
                  const SizedBox(height: 220),
                  Container(
                    decoration: BoxDecoration(
                      color: Theme.of(context).scaffoldBackgroundColor,
                      borderRadius: const BorderRadius.vertical(
                        top: Radius.circular(kRadiusXl),
                      ),
                      boxShadow: kShadowStrong,
                    ),
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(24, 28, 24, 32),
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(maxWidth: 480),
                        child: Form(
                          key: _form,
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
                              emailCtrl: _email,
                              passwordCtrl: _password,
                              tokenCtrl: _token,
                              onToggleObscure: () => setState(() => _obscure = !_obscure),
                              onSubmit: _submit,
                              onModeChange: (m) => setState(() => _mode = m),
                            ),
                          ),
                        ),
                      ),
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

// ═══════════════════════════════════════════════════════════
// 📋 Contenu du formulaire (séparé pour AnimatedSwitcher)
// ═══════════════════════════════════════════════════════════

class _FormContent extends StatelessWidget {
  const _FormContent({
    required this.mode,
    required this.title,
    required this.submitLabel,
    required this.busy,
    required this.obscure,
    required this.nameCtrl,
    required this.emailCtrl,
    required this.passwordCtrl,
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
  final TextEditingController emailCtrl;
  final TextEditingController passwordCtrl;
  final TextEditingController tokenCtrl;
  final VoidCallback onToggleObscure;
  final VoidCallback onSubmit;
  final ValueChanged<_AuthMode> onModeChange;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Titre
        Text(
          title,
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
            fontWeight: FontWeight.w900,
            letterSpacing: -0.5,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          _subtitle,
          style: TextStyle(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
            fontSize: 14,
          ),
        ),
        const SizedBox(height: 24),

        // Champ Nom (register)
        if (mode == _AuthMode.register) ...[
          TextFormField(
            controller: nameCtrl,
            textCapitalization: TextCapitalization.words,
            decoration: const InputDecoration(
              labelText: 'Nom complet',
              prefixIcon: Icon(Icons.person_outline_rounded),
            ),
            validator: (v) => v == null || v.trim().isEmpty ? 'Saisissez votre nom.' : null,
          ),
          const SizedBox(height: 14),
        ],

        // Champ Email
        TextFormField(
          controller: emailCtrl,
          keyboardType: TextInputType.emailAddress,
          decoration: const InputDecoration(
            labelText: 'Adresse e-mail',
            prefixIcon: Icon(Icons.mail_outline_rounded),
          ),
          validator: (v) => v == null || !v.contains('@') ? 'E-mail invalide.' : null,
        ),

        // Champ Token (reset)
        if (mode == _AuthMode.reset) ...[
          const SizedBox(height: 14),
          TextFormField(
            controller: tokenCtrl,
            decoration: const InputDecoration(
              labelText: 'Code de réinitialisation',
              prefixIcon: Icon(Icons.key_rounded),
            ),
            validator: (v) => v == null || v.trim().isEmpty ? 'Saisissez le code reçu.' : null,
          ),
        ],

        // Champ Password (login, register, reset)
        if (mode != _AuthMode.forgot) ...[
          const SizedBox(height: 14),
          TextFormField(
            controller: passwordCtrl,
            obscureText: obscure,
            decoration: InputDecoration(
              labelText: 'Mot de passe',
              prefixIcon: const Icon(Icons.lock_outline_rounded),
              suffixIcon: IconButton(
                tooltip: obscure ? 'Afficher' : 'Masquer',
                icon: Icon(obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                onPressed: onToggleObscure,
              ),
            ),
            validator: (v) => v == null || v.length < 6 ? 'Minimum 6 caractères.' : null,
          ),
        ],

        // Lien mot de passe oublié (login)
        if (mode == _AuthMode.login) ...[
          Align(
            alignment: Alignment.centerRight,
            child: TextButton(
              onPressed: () => onModeChange(_AuthMode.forgot),
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
              ),
              child: const Text('Mot de passe oublié ?', style: TextStyle(fontSize: 13)),
            ),
          ),
        ] else ...[
          const SizedBox(height: 20),
        ],

        const SizedBox(height: 8),

        // Bouton principal
        IvorianButton(
          label: submitLabel,
          useGradient: true,
          loading: busy,
          onPressed: busy ? null : onSubmit,
        ),

        const SizedBox(height: 20),

        // Lien switch de mode
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              _switchText,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                fontSize: 14,
              ),
            ),
            const SizedBox(width: 4),
            GestureDetector(
              onTap: () => onModeChange(_switchMode),
              child: Text(
                _switchLabel,
                style: const TextStyle(
                  color: ivorianOrange,
                  fontWeight: FontWeight.w700,
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
    _AuthMode.login    => 'Bon retour parmi nous !',
    _AuthMode.register => 'Rejoignez la communauté Tourisme Ivoire.',
    _AuthMode.forgot   => 'Recevez un lien de réinitialisation.',
    _AuthMode.reset    => 'Choisissez un nouveau mot de passe.',
  };

  String get _switchText => switch (mode) {
    _AuthMode.login    => 'Pas encore de compte ?',
    _AuthMode.register => 'Déjà inscrit ?',
    _AuthMode.forgot   => 'Vous vous en souvenez ?',
    _AuthMode.reset    => 'Retour à la',
  };

  String get _switchLabel => switch (mode) {
    _AuthMode.login    => 'Créer un compte',
    _AuthMode.register => 'Se connecter',
    _AuthMode.forgot   => 'Se connecter',
    _AuthMode.reset    => 'connexion',
  };

  _AuthMode get _switchMode => switch (mode) {
    _AuthMode.login    => _AuthMode.register,
    _AuthMode.register => _AuthMode.login,
    _AuthMode.forgot   => _AuthMode.login,
    _AuthMode.reset    => _AuthMode.login,
  };
}
