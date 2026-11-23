class TaskAssignee {
  final int id;
  final String name;
  final String avatar;
  final String team;

  const TaskAssignee({
    required this.id,
    required this.name,
    this.avatar = '',
    this.team = '',
  });

  factory TaskAssignee.fromJson(Map<String, dynamic> json) {
    return TaskAssignee(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      name: (json['name'] ?? '').toString(),
      avatar: (json['avatar'] ?? '').toString(),
      team: (json['team'] ?? '').toString(),
    );
  }
}

class TaskProjectRef {
  final int id;
  final String orderId;
  final String clientName;
  final String teamName;
  final String projectStatus;
  final String projectDeliveryDate;

  const TaskProjectRef({
    required this.id,
    required this.orderId,
    required this.clientName,
    required this.teamName,
    this.projectStatus = '',
    this.projectDeliveryDate = '',
  });

  factory TaskProjectRef.fromJson(Map<String, dynamic> json) {
    return TaskProjectRef(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      orderId: (json['order_id'] ?? '').toString(),
      clientName: (json['client_name'] ?? '').toString(),
      teamName: (json['team_name'] ?? '').toString(),
      projectStatus: (json['project_status'] ?? '').toString(),
      projectDeliveryDate: (json['project_delivery_date'] ?? '').toString(),
    );
  }
}

class ProjectTask {
  final int id;
  final int projectId;
  final int assignedUserId;
  final List<int> assignedUserIds;
  final List<TaskAssignee> assignedUsers;
  final String title;
  final String description;
  final String priority;
  final String status;
  final bool isDone;
  final bool isOverdue;
  final String deadline;
  final String deadlineFormatted;
  final String countdownLabel;
  final TaskProjectRef? project;

  const ProjectTask({
    required this.id,
    required this.projectId,
    required this.assignedUserId,
    this.assignedUserIds = const [],
    this.assignedUsers = const [],
    required this.title,
    this.description = '',
    this.priority = 'medium',
    this.status = 'pending',
    this.isDone = false,
    this.isOverdue = false,
    this.deadline = '',
    this.deadlineFormatted = 'No Deadline',
    this.countdownLabel = '',
    this.project,
  });

  factory ProjectTask.fromJson(Map<String, dynamic> json) {
    final List<TaskAssignee> users = [];
    if (json['assigned_users'] is List) {
      for (final u in (json['assigned_users'] as List)) {
        if (u is Map<String, dynamic>) {
          users.add(TaskAssignee.fromJson(u));
        }
      }
    } else if (json['assigned_user'] is Map<String, dynamic>) {
      users.add(TaskAssignee.fromJson(json['assigned_user'] as Map<String, dynamic>));
    }

    final List<int> ids = [];
    if (json['assigned_user_ids'] is List) {
      for (final raw in (json['assigned_user_ids'] as List)) {
        final v = int.tryParse(raw.toString()) ?? 0;
        if (v > 0 && !ids.contains(v)) ids.add(v);
      }
    }
    final primaryId = int.tryParse(json['assigned_user_id']?.toString() ?? '0') ?? 0;
    if (primaryId > 0 && !ids.contains(primaryId)) ids.insert(0, primaryId);

    return ProjectTask(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      projectId: int.tryParse(json['project_id']?.toString() ?? '0') ?? 0,
      assignedUserId: primaryId,
      assignedUserIds: ids,
      assignedUsers: users,
      title: (json['title'] ?? '').toString(),
      description: (json['description'] ?? '').toString(),
      priority: (json['priority'] ?? 'medium').toString(),
      status: (json['status'] ?? 'pending').toString(),
      isDone: json['is_done'] == true || json['status'] == 'done',
      isOverdue: json['is_overdue'] == true,
      deadline: (json['deadline'] ?? '').toString(),
      deadlineFormatted: (json['deadline_formatted'] ?? 'No Deadline').toString(),
      countdownLabel: (json['countdown_label'] ?? '').toString(),
      project: json['project'] is Map<String, dynamic>
          ? TaskProjectRef.fromJson(json['project'] as Map<String, dynamic>)
          : null,
    );
  }
}
