class BookingItem {
  final int id;
  final int serviceId;
  final DateTime datetime;
  final String status;
  final String statusName;
  final String comment;

  const BookingItem({
    required this.id,
    required this.serviceId,
    required this.datetime,
    required this.status,
    required this.statusName,
    required this.comment,
  });

  factory BookingItem.fromJson(Map<String, dynamic> json) {
    return BookingItem(
      id: json['id'] as int,
      serviceId: json['service_id'] as int,
      datetime: DateTime.parse(json['datetime'] as String),
      status: json['status'] as String,
      statusName: json['status_name'] as String,
      comment: json['comment'] as String? ?? '',
    );
  }
}
