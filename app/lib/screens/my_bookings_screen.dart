import 'package:flutter/material.dart';

import '../models/booking_item.dart';
import '../models/service.dart';
import '../services/api_client.dart';
import '../services/auth_service.dart';

class _BookingsData {
  final List<BookingItem> bookings;
  final Map<int, String> serviceNames;

  const _BookingsData(this.bookings, this.serviceNames);
}

class MyBookingsScreen extends StatefulWidget {
  const MyBookingsScreen({super.key});

  @override
  State<MyBookingsScreen> createState() => _MyBookingsScreenState();
}

class _MyBookingsScreenState extends State<MyBookingsScreen> {
  final ApiClient _apiClient = ApiClient();
  late Future<_BookingsData> _dataFuture;

  @override
  void initState() {
    super.initState();
    _dataFuture = _load();
  }

  Future<_BookingsData> _load() async {
    final services = await _apiClient.getServices();
    final bookings = await AuthService.instance.authorizedRequest(
      (token) => _apiClient.getMyBookings(token),
    );

    final names = {for (final Service s in services) s.id: s.name};
    return _BookingsData(bookings, names);
  }

  String _formatDatetime(DateTime dt) {
    String two(int n) => n.toString().padLeft(2, '0');
    return '${dt.day}.${dt.month}.${dt.year} ${two(dt.hour)}:${two(dt.minute)}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Мои записи')),
      body: FutureBuilder<_BookingsData>(
        future: _dataFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('Ошибка загрузки: ${snapshot.error}'));
          }
          final data = snapshot.data!;
          if (data.bookings.isEmpty) {
            return const Center(child: Text('Записей пока нет'));
          }
          return ListView.separated(
            itemCount: data.bookings.length,
            separatorBuilder: (_, _) => const Divider(height: 1),
            itemBuilder: (context, index) {
              final booking = data.bookings[index];
              final serviceName = data.serviceNames[booking.serviceId] ?? 'Услуга #${booking.serviceId}';
              return ListTile(
                title: Text(serviceName),
                subtitle: Text(
                  booking.comment.isEmpty
                      ? _formatDatetime(booking.datetime)
                      : '${_formatDatetime(booking.datetime)}\n${booking.comment}',
                ),
                isThreeLine: booking.comment.isNotEmpty,
                trailing: Text(booking.statusName),
              );
            },
          );
        },
      ),
    );
  }
}
