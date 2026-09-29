class Service {
  final int id;
  final String name;
  final double price;
  final String currency;

  const Service({
    required this.id,
    required this.name,
    required this.price,
    required this.currency,
  });

  factory Service.fromJson(Map<String, dynamic> json) {
    return Service(
      id: json['ID'] as int,
      name: json['NAME'] as String,
      price: (json['PRICE'] as num).toDouble(),
      currency: json['CURRENCY'] as String,
    );
  }
}
