import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:material_ui/material_ui.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:tourisme_ivoire/main.dart';
import 'package:tourisme_ivoire/services/api_service.dart';

void main() {
  testWidgets('opens the Tourisme Ivoire home shell', (
    WidgetTester tester,
  ) async {
    SharedPreferences.setMockInitialValues({'onboarding_complete': true});
    final dio = Dio()..httpClientAdapter = _EmptyResponseAdapter();
    final api = ApiService(dio: dio, tokenStore: _MemoryTokenStore());
    await tester.pumpWidget(TourismeIvoireApp(api: api));
    await tester.pumpAndSettle();

    expect(find.text('TOURISME IVOIRE'), findsOneWidget);
    expect(find.byType(NavigationBar), findsOneWidget);
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
