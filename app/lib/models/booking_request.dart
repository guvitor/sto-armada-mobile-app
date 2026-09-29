class BookingRequest {
  final String name;
  final String phone;
  final int serviceId;
  final DateTime datetime;
  final String? comment;

  const BookingRequest({
    required this.name,
    required this.phone,
    required this.serviceId,
    required this.datetime,
    this.comment,
  });

  Map<String, dynamic> toJson() {
    return {
      'name': name,
      'phone': phone,
      'service_id': serviceId,
      'datetime': _formatDatetime(datetime),
      if (comment != null && comment!.isNotEmpty) 'comment': comment,
    };
  }

  static String _formatDatetime(DateTime dt) {
    String two(int n) => n.toString().padLeft(2, '0');
    return '${dt.year}-${two(dt.month)}-${two(dt.day)} ${two(dt.hour)}:${two(dt.minute)}:00';
  }
}
