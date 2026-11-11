import 'package:flutter/material.dart';

import '../services/api_service.dart';

import '../widgets/common.dart';

enum _AuthMode { login, register, forgot, reset }

class AuthScreen extends StatefulWidget {
  const AuthScreen({required this.api, super.key});
  final ApiService api;

  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _token = TextEditingController();
  _AuthMode _mode = _AuthMode.login;
  bool _busy = false;
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
          if (mounted) Navigator.of(context).pop(true);
        case _AuthMode.forgot:
          await widget.api.forgotPassword(_email.text);
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Si le compte existe, un lien sera envoyé à cette adresse.')));
            setState(() => _mode = _AuthMode.reset);
          }
        case _AuthMode.reset:
          await widget.api.resetPassword(email: _email.text, token: _token.text.trim(), password: _password.text);
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Mot de passe réinitialisé. Connectez-vous.')));
            setState(() => _mode = _AuthMode.login);
          }
      }
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(readableError(error))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  String get _title => switch (_mode) {
        _AuthMode.login => 'Connexion',
        _AuthMode.register => 'Créer un compte',
        _AuthMode.forgot => 'Mot de passe oublié',
        _AuthMode.reset => 'Réinitialiser le mot de passe',
      };

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: Text(_title)),
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 480),
                child: Form(
                  key: _form,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Icon(Icons.travel_explore, size: 60, color: Color(0xFFF77F00)),
                      const SizedBox(height: 18),
                      Text(_title, textAlign: TextAlign.center, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800)),
                      const SizedBox(height: 24),
                      if (_mode == _AuthMode.register)
                        TextFormField(
                          controller: _name,
                          decoration: const InputDecoration(labelText: 'Nom', prefixIcon: Icon(Icons.person_outline)),
                          validator: (value) => value == null || value.trim().isEmpty ? 'Saisissez votre nom.' : null,
                        ),
                      if (_mode == _AuthMode.register) const SizedBox(height: 12),
                      TextFormField(
                        controller: _email,
                        keyboardType: TextInputType.emailAddress,
                        decoration: const InputDecoration(labelText: 'Adresse e-mail', prefixIcon: Icon(Icons.email_outlined)),
                        validator: (value) => value == null || !value.contains('@') ? 'Saisissez une adresse e-mail valide.' : null,
                      ),
                      if (_mode == _AuthMode.reset) ...[
                        const SizedBox(height: 12),
                        TextFormField(controller: _token, decoration: const InputDecoration(labelText: 'Jeton reçu par e-mail'), validator: (value) => value == null || value.trim().length != 64 ? 'Le jeton doit contenir 64 caractères.' : null),
                      ],
                      if (_mode == _AuthMode.login || _mode == _AuthMode.register || _mode == _AuthMode.reset) ...[
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _password,
                          obscureText: _obscure,
                          decoration: InputDecoration(
                            labelText: _mode == _AuthMode.login ? 'Mot de passe' : 'Nouveau mot de passe',
                            prefixIcon: const Icon(Icons.lock_outline),
                            suffixIcon: IconButton(
                              onPressed: () => setState(() => _obscure = !_obscure),
                              icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                            ),
                          ),
                          validator: (value) => value == null || value.length < 10 ? '10 caractères minimum.' : null,
                        ),
                      ],
                      const SizedBox(height: 18),
                      FilledButton(
                        onPressed: _busy ? null : _submit,
                        child: _busy ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(_title),
                      ),
                      if (_mode == _AuthMode.login)
                        TextButton(onPressed: () => setState(() => _mode = _AuthMode.forgot), child: const Text('Mot de passe oublié ?')),
                      if (_mode == _AuthMode.login || _mode == _AuthMode.register)
                        TextButton(
                          onPressed: () => setState(() => _mode = _mode == _AuthMode.login ? _AuthMode.register : _AuthMode.login),
                          child: Text(_mode == _AuthMode.login ? 'Créer un compte' : 'Déjà inscrit ? Se connecter'),
                        ),
                      if (_mode == _AuthMode.forgot || _mode == _AuthMode.reset)
                        TextButton(onPressed: () => setState(() => _mode = _AuthMode.login), child: const Text('Retour à la connexion')),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      );
}
