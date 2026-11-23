class AgencyNotice {
  final int id;
  final String title;
  final String content;
  final int authorId;
  final String authorName;
  final String authorAvatar;
  final bool isPinned;
  final String priority; // 'urgent', 'high', 'normal', 'info'
  final String createdAt;

  AgencyNotice({
    required this.id,
    required this.title,
    required this.content,
    required this.authorId,
    required this.authorName,
    required this.authorAvatar,
    required this.isPinned,
    this.priority = 'normal',
    required this.createdAt,
  });

  factory AgencyNotice.fromJson(Map<String, dynamic> json) {
    return AgencyNotice(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      title: json['title'] ?? '',
      content: json['content'] ?? json['message'] ?? '',
      authorId: int.tryParse(json['author_id']?.toString() ?? json['user_id']?.toString() ?? '0') ?? 0,
      authorName: json['author_name'] ?? json['author'] ?? 'Management',
      authorAvatar: json['author_avatar'] ?? '',
      isPinned: json['is_pinned'] == true || json['is_pinned'] == 1 || json['pinned'] == 1,
      priority: json['priority'] ?? 'normal',
      createdAt: json['created_at'] ?? json['date'] ?? '',
    );
  }
}
