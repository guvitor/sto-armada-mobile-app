import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import 'screens/catalog_screen.dart';
import 'services/auth_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AuthService.instance.init();
  runApp(const StoApp());
}

class StoApp extends StatelessWidget {
  const StoApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'СТО Армада Моторс',
      // Без делегатов системные виджеты (календарь, выбор времени, подсказки)
      // остаются на английском даже на русском телефоне. Интерфейс только
      // русский, поэтому локаль зафиксирована, а не берётся из системы.
      locale: const Locale('ru'),
      supportedLocales: const [Locale('ru')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.deepOrange),
        useMaterial3: true,
      ),
      home: const CatalogScreen(),
    );
  }
}
