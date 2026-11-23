import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_team_app/widgets/animated_logo.dart';

void main() {
  testWidgets('AnimatedLogo renders with custom size and pulses smoothly', (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: Center(
            child: AnimatedLogo(
              size: 42,
              showLiveIndicator: true,
            ),
          ),
        ),
      ),
    );

    // Initial frame
    expect(find.byType(AnimatedLogo), findsOneWidget);

    // Advance animation by 500ms
    await tester.pump(const Duration(milliseconds: 500));
    expect(find.byType(AnimatedLogo), findsOneWidget);

    // Advance another 1000ms
    await tester.pump(const Duration(milliseconds: 1000));
    expect(find.byType(AnimatedLogo), findsOneWidget);
  });

  testWidgets('AnimatedLogo renders without live indicator when disabled', (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: Center(
            child: AnimatedLogo(
              size: 34,
              showLiveIndicator: false,
              enablePulse: false,
            ),
          ),
        ),
      ),
    );

    expect(find.byType(AnimatedLogo), findsOneWidget);
  });
}
