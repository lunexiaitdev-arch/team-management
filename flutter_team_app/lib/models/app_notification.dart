class AppNotification {
  final String id;
  final int userId;
  final int? projectId;
  final String type; // 'new_assignment', 'deadline_warning_3d', 'progress_prompt_2d', 'extension_advisory_3d'
  final String title;
  final String message;
  final Map<String, dynamic> data;
  final bool isRead;
  final String createdAt;
  final int timestamp;

  AppNotification({
    required this.id,
    required this.userId,
    this.projectId,
    required this.type,
    required this.title,
    required this.message,
    this.data = const {},
    required this.isRead,
    required this.createdAt,
    required this.timestamp,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id']?.toString() ?? '',
      userId: int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      projectId: json['project_id'] != null ? int.tryParse(json['project_id'].toString()) : null,
      type: json['type'] ?? 'general',
      title: json['title'] ?? '',
      message: json['message'] ?? '',
      data: json['data'] is Map ? Map<String, dynamic>.from(json['data']) : {},
      isRead: json['is_read'] == true || json['is_read'] == 1,
      createdAt: json['created_at'] ?? '',
      timestamp: int.tryParse(json['timestamp']?.toString() ?? '0') ?? 0,
    );
  }

  bool get isUrgent =>
      type == 'deadline_warning_3d' || type == 'extension_advisory_3d';
}
