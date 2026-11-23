class ChatMessage {
  final int id;
  final String conversationType; // 'global', 'team', 'project', 'direct'
  final String conversationId;
  final int senderId;
  final String senderName;
  final String senderAvatar;
  final String senderRole;
  final String senderTeam;
  final bool isMine;
  final String message;
  final String? attachmentUrl;
  final bool isRead;
  final String createdAt;
  final int timestamp;

  ChatMessage({
    required this.id,
    required this.conversationType,
    required this.conversationId,
    required this.senderId,
    required this.senderName,
    required this.senderAvatar,
    required this.senderRole,
    required this.senderTeam,
    required this.isMine,
    required this.message,
    this.attachmentUrl,
    required this.isRead,
    required this.createdAt,
    required this.timestamp,
  });

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    return ChatMessage(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      conversationType: json['conversation_type'] ?? 'global',
      conversationId: json['conversation_id'] ?? 'global',
      senderId: int.tryParse(json['sender_id']?.toString() ?? '0') ?? 0,
      senderName: json['sender_name'] ?? 'Member',
      senderAvatar: json['sender_avatar'] ?? '',
      senderRole: json['sender_role'] ?? 'Specialist',
      senderTeam: json['sender_team'] ?? '',
      isMine: json['is_mine'] == true || json['is_mine'] == 1,
      message: json['message'] ?? '',
      attachmentUrl: json['attachment_url'],
      isRead: json['is_read'] == true || json['is_read'] == 1,
      createdAt: json['created_at'] ?? '',
      timestamp: int.tryParse(json['timestamp']?.toString() ?? '0') ?? 0,
    );
  }
}

class ChatChannel {
  final String type; // 'global', 'team', 'project', 'direct'
  final String id;
  final String title;
  final String subtitle;
  final String? avatar;
  final String? team;
  final String? role;
  final int? recipientId;
  final int? projectId;
  final String? orderId;
  final String? status;
  final String lastMessage;
  final String? lastSender;
  final String lastTime;
  final int unreadCount;
  final bool hasHistory;

  ChatChannel({
    required this.type,
    required this.id,
    required this.title,
    required this.subtitle,
    this.avatar,
    this.team,
    this.role,
    this.recipientId,
    this.projectId,
    this.orderId,
    this.status,
    required this.lastMessage,
    this.lastSender,
    required this.lastTime,
    this.unreadCount = 0,
    this.hasHistory = false,
  });

  factory ChatChannel.fromJson(Map<String, dynamic> json) {
    return ChatChannel(
      type: json['type'] ?? 'global',
      id: json['id'] ?? 'global',
      title: json['title'] ?? '',
      subtitle: json['subtitle'] ?? '',
      avatar: json['avatar'],
      team: json['team'],
      role: json['role'],
      recipientId: json['recipient_id'] != null ? int.tryParse(json['recipient_id'].toString()) : null,
      projectId: json['project_id'] != null ? int.tryParse(json['project_id'].toString()) : null,
      orderId: json['order_id'],
      status: json['status'],
      lastMessage: json['last_message'] ?? '',
      lastSender: json['last_sender'],
      lastTime: json['last_time'] ?? '',
      unreadCount: int.tryParse(json['unread_count']?.toString() ?? '0') ?? 0,
      hasHistory: json['has_history'] == true || json['has_history'] == 1,
    );
  }
}
