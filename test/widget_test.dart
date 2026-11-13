import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:material_ui/material_ui.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:tourisme_ivoire/main.dart';
import 'package:tourisme_ivoire/screens/auth_screen.dart';
import 'package:tourisme_ivoire/screens/profile_photo_setup_screen.dart';
import 'package:tourisme_ivoire/services/api_service.dart';
import 'package:tourisme_ivoire/screens/profile_screen.dart';

void main() {
  testWidgets('shows the Tourisme photo in the orange auth header', (
    WidgetTester tester,
  ) async {
    tester.view.physicalSize = const Size(390, 844);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    final api = ApiService(
      dio: Dio()..httpClientAdapter = _EmptyResponseAdapter(),
      tokenStore: _MemoryTokenStore(),
    );
    await tester.pumpWidget(MaterialApp(home: AuthScreen(api: api)));
    await tester.pumpAndSettle();

    expect(
      find.byWidgetPredicate(
        (widget) =>
            widget is Image &&
            widget.image is AssetImage &&
            (widget.image as AssetImage).assetName == 'lib/images/Tourisme.jpg',
      ),
      findsOneWidget,
    );
    expect(tester.takeException(), isNull);
  });

  testWidgets('shows the profile image above the traveler heading', (
    WidgetTester tester,
  ) async {
    final api = ApiService(
      dio: Dio()..httpClientAdapter = _EmptyResponseAdapter(),
      tokenStore: _MemoryTokenStore(),
    );
    await tester.pumpWidget(
      MaterialApp(
        home: ProfileScreen(
          api: api,
          onThemeChanged: (_) {},
          onAccessibilityChanged: (_) {},
        ),
      ),
    );
    await tester.pumpAndSettle();

    final heading = find.text('Mon espace voyageur');
    final profileImage = find.byWidgetPredicate(
      (widget) =>
          widget is Image &&
          widget.image is AssetImage &&
          (widget.image as AssetImage).assetName == 'lib/images/Profile.avif' &&
          widget.width == null,
    );
    expect(heading, findsOneWidget);
    expect(profileImage, findsOneWidget);
    final header = find.ancestor(
      of: heading,
      matching: find.byWidgetPredicate(
        (widget) =>
            widget is Container &&
            widget.decoration is BoxDecoration &&
            (widget.decoration as BoxDecoration).gradient != null,
      ),
    );
    expect(header, findsOneWidget);
    expect(tester.getSize(header).height, greaterThanOrEqualTo(360));
    expect(
      tester.getSize(header).width,
      tester.view.physicalSize.width / tester.view.devicePixelRatio,
    );
    expect(
      tester.getTopLeft(profileImage).dy,
      lessThan(tester.getTopLeft(heading).dy),
    );
    expect(tester.takeException(), isNull);
  });

  testWidgets('opens the Tourisme Ivoire home shell', (
    WidgetTester tester,
  ) async {
    SharedPreferences.setMockInitialValues({'onboarding_complete': true});
    final dio = Dio()..httpClientAdapter = _EmptyResponseAdapter();
    final api = ApiService(dio: dio, tokenStore: _MemoryTokenStore());
    await tester.pumpWidget(TourismeIvoireApp(api: api));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 16));

    void expectBannerImage(String assetPath) {
      expect(
        find.byWidgetPredicate(
          (widget) =>
              widget is Image &&
              widget.image is AssetImage &&
              (widget.image as AssetImage).assetName == assetPath,
        ),
        findsOneWidget,
      );
    }

    expect(find.text('TOURISME IVOIRE'), findsOneWidget);
    expect(find.byType(NavigationBar), findsOneWidget);
    expectBannerImage('lib/images/Stade.jfif');
    await tester.pump(const Duration(seconds: 3));
    expectBannerImage('lib/images/basilique.jpg');
    await tester.pump(const Duration(seconds: 3));
    expectBannerImage('lib/images/Cascade.jfif');
    await tester.pump(const Duration(seconds: 3));
    expectBannerImage('lib/images/Stade.jfif');
  });
  testWidgets(
    'registration enforces the backend password policy and confirmation',
    (WidgetTester tester) async {
      tester.view.physicalSize = const Size(390, 844);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);

      final api = ApiService(
        dio: Dio()..httpClientAdapter = _EmptyResponseAdapter(),
        tokenStore: _MemoryTokenStore(),
      );
      await tester.pumpWidget(MaterialApp(home: AuthScreen(api: api)));
      await tester.tap(find.text('Créer un compte'));
      await tester.pumpAndSettle();

      final fields = find.byType(TextFormField);
      await tester.enterText(fields.at(0), 'Voyageur');
      await tester.enterText(fields.at(3), 'voyageur@example.com');
      await tester.enterText(fields.at(4), 'court');
      await tester.enterText(fields.at(5), 'different');
      final submitButton = find.text('Créer mon compte');
      await tester.ensureVisible(submitButton);
      await tester.pumpAndSettle();
      await tester.tap(submitButton);
      await tester.pumpAndSettle();

      expect(find.text('Minimum 10 caractères.'), findsOneWidget);
      expect(
        find.text('Les mots de passe ne correspondent pas.'),
        findsOneWidget,
      );
      expect(tester.takeException(), isNull);
    },
  );

  testWidgets('profile photo step can be skipped after registration', (
    WidgetTester tester,
  ) async {
    final api = ApiService(
      dio: Dio()..httpClientAdapter = _EmptyResponseAdapter(),
      tokenStore: _MemoryTokenStore(),
    );
    bool? completed;
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: Builder(
            builder: (context) => TextButton(
              onPressed: () async {
                completed = await Navigator.of(context).push<bool>(
                  MaterialPageRoute<bool>(
                    builder: (_) => ProfilePhotoSetupScreen(api: api),
                  ),
                );
              },
              child: const Text('Ouvrir la personnalisation'),
            ),
          ),
        ),
      ),
    );

    await tester.tap(find.text('Ouvrir la personnalisation'));
    await tester.pumpAndSettle();
    expect(find.text('Bienvenue sur Tourisme Ivoire !'), findsOneWidget);
    await tester.tap(find.text('Ignorer pour le moment'));
    await tester.pumpAndSettle();

    expect(completed, isTrue);
    expect(find.text('Ouvrir la personnalisation'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}

class _MemoryTokenStore implements TokenStore {
  @override
  Future<void> clearToken() async {}

  @override
  Future<String?> readToken() async => null;

  @override
  Future<void> saveToken(String token) async {}
}

class _EmptyResponseAdapter implements HttpClientAdapter {
  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async => ResponseBody.fromString(
    '{"data":[]}',
    200,
    headers: {
      Headers.contentTypeHeader: [Headers.jsonContentType],
    },
  );

  @override
  void close({bool force = false}) {}
}
