import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'screens/login_screen.dart';
import 'screens/journey_plan_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final prefs = await SharedPreferences.getInstance();
  final String? token = prefs.getString('auth_token');
  final String? userName = prefs.getString('user_name');

  runApp(BaraMobileApp(
    initialToken: token,
    initialUserName: userName,
  ));
}

class BaraMobileApp extends StatelessWidget {
  final String? initialToken;
  final String? initialUserName;

  const BaraMobileApp({
    super.key,
    this.initialToken,
    this.initialUserName,
  });

  @override
  Widget build(BuildContext context) {
    final bool isLoggedIn = initialToken != null && initialToken!.isNotEmpty;

    return MaterialApp(
      title: 'BARA Mobile Field SFA',
      debugShowCheckedModeBanner: false,
      theme: ThemeData.dark().copyWith(
        scaffoldBackgroundColor: const Color(0xFF0B0F19),
        primaryColor: const Color(0xFF6366F1),
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF6366F1),
          brightness: Brightness.dark,
        ),
      ),
      home: isLoggedIn
          ? JourneyPlanScreen(token: initialToken!, userName: initialUserName ?? 'Nairobi Field Rep 1')
          : const LoginScreen(),
    );
  }
}
