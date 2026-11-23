class DailyReportTaskItem {
  final int? id;
  final int? projectId;
  final String orderId;
  final String clientName;
  final String taskDescription;
  final String tasksCompleted;
  final String workInProgress;
  final String nextDayPlan;
  final bool hasBlocker;
  final String blockerDescription;
  final double hoursSpent;
  final int progressPercent;

  DailyReportTaskItem({
    this.id,
    this.projectId,
    required this.orderId,
    this.clientName = '',
    required this.taskDescription,
    this.tasksCompleted = '',
    this.workInProgress = '',
    this.nextDayPlan = '',
    this.hasBlocker = false,
    this.blockerDescription = '',
    required this.hoursSpent,
    required this.progressPercent,
  });

  factory DailyReportTaskItem.fromJson(Map<String, dynamic> json) {
    final completed = json['tasks_completed']?.toString() ??
        json['task_description']?.toString() ??
        json['task']?.toString() ??
        json['description']?.toString() ??
        '';

    return DailyReportTaskItem(
      id: json['id'] != null ? int.tryParse(json['id'].toString()) : null,
      projectId: json['project_id'] != null ? int.tryParse(json['project_id'].toString()) : null,
      orderId: json['order_id']?.toString() ?? '',
      clientName: json['client_name']?.toString() ?? '',
      taskDescription: completed,
      tasksCompleted: completed,
      workInProgress: json['work_in_progress']?.toString() ?? '',
      nextDayPlan: json['next_day_plan']?.toString() ?? '',
      hasBlocker: json['has_blocker'] == true || json['has_blocker'] == 1 || json['has_blocker'] == '1',
      blockerDescription: json['blocker_description']?.toString() ?? json['blocker']?.toString() ?? '',
      hoursSpent: double.tryParse(json['hours_spent']?.toString() ?? json['hours']?.toString() ?? '0') ?? 0.0,
      progressPercent: int.tryParse(json['progress_percent']?.toString() ?? json['progress']?.toString() ?? '0') ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    final desc = tasksCompleted.isNotEmpty ? tasksCompleted : taskDescription;
    return {
      'project_id': projectId,
      'order_id': orderId,
      'client_name': clientName,
      'task_description': desc,
      'tasks_completed': desc,
      'work_in_progress': workInProgress,
      'next_day_plan': nextDayPlan,
      'has_blocker': hasBlocker ? 1 : 0,
      'blocker_description': blockerDescription,
      'hours_spent': hoursSpent,
      'progress_percent': progressPercent,
    };
  }
}

class DeliveredProjectItem {
  final int id;
  final String orderId;
  final String clientName;
  final String profileName;
  final double netValue;
  final double starRating;
  final int extensionCount;
  final int extensionDays;
  final String extensionReason;
  final String onTimeLabel;
  final bool deliveredWithoutExtension;

  DeliveredProjectItem({
    required this.id,
    required this.orderId,
    required this.clientName,
    required this.profileName,
    required this.netValue,
    required this.starRating,
    required this.extensionCount,
    required this.extensionDays,
    required this.extensionReason,
    required this.onTimeLabel,
    required this.deliveredWithoutExtension,
  });

  factory DeliveredProjectItem.fromJson(Map<String, dynamic> json) {
    return DeliveredProjectItem(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      orderId: json['order_id']?.toString() ?? '',
      clientName: json['client_name']?.toString() ?? '',
      profileName: json['profile_name']?.toString() ?? '',
      netValue: double.tryParse(json['net_value']?.toString() ?? '0') ?? 0.0,
      starRating: double.tryParse(json['star_rating']?.toString() ?? '0') ?? 0.0,
      extensionCount: int.tryParse(json['extension_count']?.toString() ?? '0') ?? 0,
      extensionDays: int.tryParse(json['extension_days']?.toString() ?? '0') ?? 0,
      extensionReason: json['extension_reason']?.toString() ?? '',
      onTimeLabel: json['on_time_label']?.toString() ?? '',
      deliveredWithoutExtension: json['delivered_without_extension'] == 1 || json['delivered_without_extension'] == true,
    );
  }
}

class ExtendedProjectItem {
  final int id;
  final String orderId;
  final String clientName;
  final int extensionCount;
  final int extensionDays;
  final String extensionReason;
  final String status;

  ExtendedProjectItem({
    required this.id,
    required this.orderId,
    required this.clientName,
    required this.extensionCount,
    required this.extensionDays,
    required this.extensionReason,
    required this.status,
  });

  factory ExtendedProjectItem.fromJson(Map<String, dynamic> json) {
    return ExtendedProjectItem(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      orderId: json['order_id']?.toString() ?? '',
      clientName: json['client_name']?.toString() ?? '',
      extensionCount: int.tryParse(json['extension_count']?.toString() ?? '0') ?? 0,
      extensionDays: int.tryParse(json['extension_days']?.toString() ?? '0') ?? 0,
      extensionReason: json['extension_reason']?.toString() ?? '',
      status: json['status']?.toString() ?? '',
    );
  }
}

class LateProjectItem {
  final int id;
  final String orderId;
  final String clientName;
  final String deliveryDate;
  final int daysLate;
  final String status;

  LateProjectItem({
    required this.id,
    required this.orderId,
    required this.clientName,
    required this.deliveryDate,
    required this.daysLate,
    required this.status,
  });

  factory LateProjectItem.fromJson(Map<String, dynamic> json) {
    return LateProjectItem(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      orderId: json['order_id']?.toString() ?? '',
      clientName: json['client_name']?.toString() ?? '',
      deliveryDate: json['delivery_date']?.toString() ?? '',
      daysLate: int.tryParse(json['days_late']?.toString() ?? '0') ?? 0,
      status: json['status']?.toString() ?? '',
    );
  }
}

class DailyReport {
  final int id;
  final int userId;
  final String userName;
  final String userAvatar;
  final String callSign;
  final String team;
  final String reportDate;
  final double totalHours;
  final String blockers;
  final String tomorrowPlan;
  final String status; // 'submitted', 'leader_approved', 'approved', 'revision_requested'
  final String statusLabel;
  final String reportType; // 'work', 'on_leave', 'weekend'
  final String? submissionTime;
  final bool isLate;
  final int leaderReviewedBy;
  final String? leaderReviewedAt;
  final String? leaderFeedback;
  final int managerReviewedBy;
  final String? managerReviewedAt;
  final String? managerFeedback;
  final String? reviewerName;
  final String? reviewedAt;
  final String? reviewerComment;
  final List<DailyReportTaskItem> items;
  final List<DeliveredProjectItem> deliveredProjects;
  final List<ExtendedProjectItem> extendedProjects;
  final List<LateProjectItem> lateProjects;
  final String createdAt;

  DailyReport({
    required this.id,
    required this.userId,
    required this.userName,
    required this.userAvatar,
    required this.callSign,
    required this.team,
    required this.reportDate,
    required this.totalHours,
    required this.blockers,
    required this.tomorrowPlan,
    this.status = 'submitted',
    this.statusLabel = 'Awaiting Leader Review',
    this.reportType = 'work',
    this.submissionTime,
    this.isLate = false,
    this.leaderReviewedBy = 0,
    this.leaderReviewedAt,
    this.leaderFeedback,
    this.managerReviewedBy = 0,
    this.managerReviewedAt,
    this.managerFeedback,
    this.reviewerName,
    this.reviewedAt,
    this.reviewerComment,
    this.items = const [],
    this.deliveredProjects = const [],
    this.extendedProjects = const [],
    this.lateProjects = const [],
    required this.createdAt,
  });

  factory DailyReport.fromJson(Map<String, dynamic> json) {
    List<DailyReportTaskItem> taskItems = [];
    if (json['items'] is List) {
      taskItems = (json['items'] as List)
          .map((i) => DailyReportTaskItem.fromJson(Map<String, dynamic>.from(i)))
          .toList();
    }

    List<DeliveredProjectItem> delivered = [];
    if (json['delivered_projects'] is List) {
      delivered = (json['delivered_projects'] as List)
          .map((i) => DeliveredProjectItem.fromJson(Map<String, dynamic>.from(i)))
          .toList();
    }

    List<ExtendedProjectItem> extended = [];
    if (json['extended_projects'] is List) {
      extended = (json['extended_projects'] as List)
          .map((i) => ExtendedProjectItem.fromJson(Map<String, dynamic>.from(i)))
          .toList();
    }

    List<LateProjectItem> lateList = [];
    if (json['late_projects'] is List) {
      lateList = (json['late_projects'] as List)
          .map((i) => LateProjectItem.fromJson(Map<String, dynamic>.from(i)))
          .toList();
    }

    double calcHours = double.tryParse(json['total_hours']?.toString() ?? json['hours']?.toString() ?? '0') ?? 0.0;
    if (calcHours <= 0 && taskItems.isNotEmpty) {
      calcHours = taskItems.fold<double>(0.0, (acc, item) => acc + item.hoursSpent);
    }

    final rawStatus = json['status']?.toString() ?? 'submitted';
    String defaultLabel = 'Awaiting Leader Review';
    if (rawStatus == 'leader_approved') {
      defaultLabel = 'Leader Verified (Awaiting Manager)';
    } else if (rawStatus == 'approved') {
      defaultLabel = 'Final Approved';
    } else if (rawStatus == 'revision_requested') {
      defaultLabel = 'Revision Requested';
    } else if (rawStatus == 'draft') {
      defaultLabel = 'Draft';
    }

    final statusLabel = json['status_label']?.toString() ?? defaultLabel;

    return DailyReport(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      userId: int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      userName: json['user_name'] ?? json['display_name'] ?? json['name'] ?? '',
      userAvatar: json['user_avatar'] ?? json['avatar'] ?? '',
      callSign: json['call_sign'] ?? json['user_name'] ?? '',
      team: json['team_name'] ?? json['team'] ?? '',
      reportDate: json['report_date'] ?? json['date'] ?? '',
      totalHours: calcHours,
      blockers: json['blockers'] ?? json['challenges'] ?? '',
      tomorrowPlan: json['tomorrow_plan'] ?? json['next_day_tasks'] ?? '',
      status: rawStatus,
      statusLabel: statusLabel,
      reportType: json['report_type']?.toString() ?? 'work',
      submissionTime: json['submission_time']?.toString(),
      isLate: json['is_late'] == 1 || json['is_late'] == true || json['is_late'] == '1',
      leaderReviewedBy: int.tryParse(json['leader_reviewed_by']?.toString() ?? '0') ?? 0,
      leaderReviewedAt: json['leader_reviewed_at']?.toString(),
      leaderFeedback: json['leader_feedback']?.toString() ?? json['reviewer_comment']?.toString(),
      managerReviewedBy: int.tryParse(json['manager_reviewed_by']?.toString() ?? '0') ?? 0,
      managerReviewedAt: json['manager_reviewed_at']?.toString(),
      managerFeedback: json['manager_feedback']?.toString(),
      reviewerName: json['reviewer_name']?.toString(),
      reviewedAt: json['reviewed_at']?.toString(),
      reviewerComment: json['reviewer_comment'] ?? json['leader_feedback'] ?? json['review_note'],
      items: taskItems,
      deliveredProjects: delivered,
      extendedProjects: extended,
      lateProjects: lateList,
      createdAt: json['created_at'] ?? '',
    );
  }
}
