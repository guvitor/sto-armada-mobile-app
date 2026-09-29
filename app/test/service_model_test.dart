import 'package:flutter_test/flutter_test.dart';
import 'package:sto_app/models/service.dart';

void main() {
  test('Service.fromJson парсит контракт catalog.product.list из ТЗ', () {
    final service = Service.fromJson({
      'ID': 1,
      'NAME': 'Замена масла',
      'PRICE': 1500,
      'CURRENCY': 'RUB',
    });

    expect(service.id, 1);
    expect(service.name, 'Замена масла');
    expect(service.price, 1500.0);
    expect(service.currency, 'RUB');
  });
}
