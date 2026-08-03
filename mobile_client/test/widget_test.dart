import 'package:flutter_test/flutter_test.dart';
import 'package:bara_mobile/main.dart';

void main() {
  testWidgets('BARA Mobile App Login Smoke Test', (WidgetTester tester) async {
    await tester.pumpWidget(const BaraMobileApp());

    expect(find.text('BARA Field SFA'), findsOneWidget);
    expect(find.text('Authenticate & Access Route'), findsOneWidget);
  });
}
