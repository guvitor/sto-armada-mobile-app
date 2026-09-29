import 'package:flutter_test/flutter_test.dart';

import 'package:sto_app/main.dart';

void main() {
  testWidgets('Каталог услуг отображается при запуске', (WidgetTester tester) async {
    await tester.pumpWidget(const StoApp());
    await tester.pumpAndSettle();
    expect(find.text('Каталог услуг'), findsOneWidget);
  });
}
