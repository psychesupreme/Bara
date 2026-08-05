import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';
import '../services/sync_manager.dart';
import 'journey_plan_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController(text: 'nairobi.rep1@bara.app');
  final _passwordController = TextEditingController(text: 'password');
  bool _isLoading = false;
  String? _errorMessage;

  Future<void> _handleLogin() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final String email = _emailController.text.trim();
    final String password = _passwordController.text.trim();

    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/auth/login'),
        headers: ApiConfig.getHeaders(),
        body: jsonEncode({
          'email': email,
          'password': password,
          // Note: dynamic device info should be used here, but we can't add device_info_plus dependency in this sprint
          'device_name': 'bara_android_${DateTime.now().millisecondsSinceEpoch}',
        }),
      ).timeout(const Duration(seconds: 5));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final String? token = data['token'];
        if (token == null || token.isEmpty) {
            setState(() => _errorMessage = 'Server returned invalid token.');
            return;
        }
        final String userName = data['user']?['name'] ?? email.split('@').first;

        // Persist token and tenant locally
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', token);
        await prefs.setString('tenant_id', ApiConfig.defaultTenant);
        await prefs.setString('user_email', email);
        await prefs.setString('user_name', userName);

        SyncManager.initialize(authToken: token);

        if (!mounted) return;
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => JourneyPlanScreen(token: token, userName: userName)),
        );
      } else {
        // Check for cached credentials for offline login
        final prefs = await SharedPreferences.getInstance();
        final cachedToken = prefs.getString('auth_token');
        final cachedEmail = prefs.getString('user_email');
        
        if (cachedToken != null && cachedToken.isNotEmpty && cachedEmail == email) {
            // Offline login with previously authenticated credentials
            final cachedName = prefs.getString('user_name') ?? 'Offline User';
            if (!mounted) return;
            Navigator.of(context).pushReplacement(
                MaterialPageRoute(builder: (_) => JourneyPlanScreen(token: cachedToken, userName: '$cachedName (Offline)')),
            );
        } else {
            setState(() => _errorMessage = 'Authentication failed (HTTP ${response.statusCode}). No cached credentials available for offline login.');
        }
      }
    } catch (e) {
        // Offline fallback — only allow if previously authenticated
        final prefs = await SharedPreferences.getInstance();
        final cachedToken = prefs.getString('auth_token');
        final cachedEmail = prefs.getString('user_email');
        
        if (cachedToken != null && cachedToken.isNotEmpty && cachedEmail == email) {
            final cachedName = prefs.getString('user_name') ?? 'Offline User';
            if (!mounted) return;
            Navigator.of(context).pushReplacement(
                MaterialPageRoute(builder: (_) => JourneyPlanScreen(token: cachedToken, userName: '$cachedName (Offline)')),
            );
        } else {
            setState(() => _errorMessage = 'Network error. No cached credentials available. Please connect to the server to log in for the first time.');
        }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // BARA Logo & Brand Title
              Center(
                child: Container(
                  width: 72,
                  height: 72,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(colors: [Color(0xFF6366F1), Color(0xFFEC4899)]),
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF6366F1).withValues(alpha: 0.4),
                        blurRadius: 20,
                        offset: const Offset(0, 8),
                      ),
                    ],
                  ),
                  child: const Center(
                    child: Text(
                      'B',
                      style: TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: Colors.white),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              const Text(
                'BARA Field SFA',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Colors.white),
              ),
              const SizedBox(height: 6),
              const Text(
                'Nairobi Central Territory | Physical Mobile Sprint',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 13, color: Colors.grey),
              ),
              const SizedBox(height: 36),

              // Error Banner
              if (_errorMessage != null)
                Container(
                  padding: const EdgeInsets.all(12),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.red.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.red.withValues(alpha: 0.3)),
                  ),
                  child: Text(_errorMessage!, style: const TextStyle(color: Colors.redAccent, fontSize: 13)),
                ),

              // Email Input
              TextField(
                controller: _emailController,
                style: const TextStyle(color: Colors.white),
                decoration: InputDecoration(
                  labelText: 'Rep Email Address',
                  labelStyle: const TextStyle(color: Colors.grey),
                  prefixIcon: const Icon(Icons.email_outlined, color: Colors.indigoAccent),
                  filled: true,
                  fillColor: const Color(0xFF1E293B),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                ),
              ),
              const SizedBox(height: 16),

              // Password Input
              TextField(
                controller: _passwordController,
                obscureText: true,
                style: const TextStyle(color: Colors.white),
                decoration: InputDecoration(
                  labelText: 'Password',
                  labelStyle: const TextStyle(color: Colors.grey),
                  prefixIcon: const Icon(Icons.lock_outline, color: Colors.indigoAccent),
                  filled: true,
                  fillColor: const Color(0xFF1E293B),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                ),
              ),
              const SizedBox(height: 24),

              // Login Button
              ElevatedButton(
                onPressed: _isLoading ? null : _handleLogin,
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  backgroundColor: const Color(0xFF6366F1),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: _isLoading
                    ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Text('Authenticate & Access Route', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white)),
              ),
              const SizedBox(height: 16),
              const Text(
                'Connected Host: 192.168.100.6:8000 (Tenant: tenant1)',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.indigoAccent, fontSize: 12, fontWeight: FontWeight.bold),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
