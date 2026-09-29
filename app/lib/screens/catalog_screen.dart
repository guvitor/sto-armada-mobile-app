import 'package:flutter/material.dart';

import '../models/service.dart';
import '../services/api_client.dart';
import '../services/auth_service.dart';
import 'booking_screen.dart';
import 'login_screen.dart';
import 'my_bookings_screen.dart';

class CatalogScreen extends StatefulWidget {
  const CatalogScreen({super.key});

  @override
  State<CatalogScreen> createState() => _CatalogScreenState();
}

class _CatalogScreenState extends State<CatalogScreen> {
  final ApiClient _apiClient = ApiClient();
  late Future<List<Service>> _servicesFuture;

  @override
  void initState() {
    super.initState();
    _servicesFuture = _apiClient.getServices();
    AuthService.instance.addListener(_onAuthChanged);
  }

  @override
  void dispose() {
    AuthService.instance.removeListener(_onAuthChanged);
    super.dispose();
  }

  void _onAuthChanged() => setState(() {});

  Future<void> _openLogin() async {
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
    );
  }

  @override
  Widget build(BuildContext context) {
    final loggedIn = AuthService.instance.isLoggedIn;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Каталог услуг'),
        actions: [
          if (loggedIn) ...[
            IconButton(
              icon: const Icon(Icons.event_note),
              tooltip: 'Мои записи',
              onPressed: () {
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const MyBookingsScreen()),
                );
              },
            ),
            IconButton(
              icon: const Icon(Icons.logout),
              tooltip: 'Выйти',
              onPressed: () => AuthService.instance.logout(),
            ),
          ] else
            IconButton(
              icon: const Icon(Icons.login),
              tooltip: 'Войти',
              onPressed: _openLogin,
            ),
        ],
      ),
      body: FutureBuilder<List<Service>>(
        future: _servicesFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('Ошибка загрузки: ${snapshot.error}'));
          }
          final services = snapshot.data ?? [];
          if (services.isEmpty) {
            return const Center(child: Text('Услуги не найдены'));
          }
          return ListView.separated(
            itemCount: services.length,
            separatorBuilder: (_, _) => const Divider(height: 1),
            itemBuilder: (context, index) {
              final service = services[index];
              return ListTile(
                title: Text(service.name),
                trailing: Text('${service.price.toStringAsFixed(0)} ${service.currency}'),
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => BookingScreen(service: service),
                    ),
                  );
                },
              );
            },
          );
        },
      ),
    );
  }
}
