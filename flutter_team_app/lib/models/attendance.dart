class AttendanceRosterItem {
  final int userId;
  final String customId;
  final String name;
  final String avatar;
  final String team;
  final String roleLabel;
  final String weeklyDayOff;
  final String status;
  final String statusLabel;
  final String notes;
  final bool hasReport;
  final String? submissionTime;
  final bool isLate;

  AttendanceRosterItem({
    required this.userId,
    required this.customId,
    required this.name,
    required this.avatar,
    required this.team,
    required this.roleLabel,
    required this.weeklyDayOff,
    required this.status,
    required this.statusLabel,
    required this.notes,
    required this.hasReport,
    this.submissionTime,
    required this.isLate,
  });

  factory AttendanceRosterItem.fromJson(Map<String, dynamic> json) {
    return AttendanceRosterItem(
      userId: int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      customId: (json['custom_id'] ?? '').toString(),
      name: (json['name'] ?? '').toString(),
      avatar: (json['avatar'] ?? '').toString(),
      team: (json['team'] ?? '').toString(),
      roleLabel: (json['role_label'] ?? 'Specialist').toString(),
      weeklyDayOff: (json['weekly_day_off'] ?? 'Friday').toString(),
      status: (json['status'] ?? 'working').toString(),
      statusLabel: (json['status_label'] ?? 'Working').toString(),
      notes: (json['notes'] ?? '').toString(),
      hasReport: json['has_report'] == true,
      submissionTime: json['submission_time']?.toString(),
      isLate: json['is_late'] == 1 || json['is_late'] == true,
    );
  }
}

class AttendanceKpis {
  final int total;
  final int present;
  final int working;
  final int onLeave;
  final int dayOff;
  final int late;
  final int absent;

  AttendanceKpis({
    required this.total,
    required this.present,
    required this.working,
    required this.onLeave,
    required this.dayOff,
    required this.late,
    required this.absent,
  });

  factory AttendanceKpis.fromJson(Map<String, dynamic> json) {
    return AttendanceKpis(
      total: int.tryParse(json['total']?.toString() ?? '0') ?? 0,
      present: int.tryParse(json['present']?.toString() ?? '0') ?? 0,
      working: int.tryParse(json['working']?.toString() ?? '0') ?? 0,
      onLeave: int.tryParse(json['on_leave']?.toString() ?? '0') ?? 0,
      dayOff: int.tryParse(json['day_off']?.toString() ?? '0') ?? 0,
      late: int.tryParse(json['late']?.toString() ?? '0') ?? 0,
      absent: int.tryParse(json['absent']?.toString() ?? '0') ?? 0,
    );
  }
}

class LeaveRequestItem {
  final int id;
  final int userId;
  final String userName;
  final String userAvatar;
  final String teamName;
  final String requestType;
  final String requestTypeLabel;
  final String leaveType;
  final String leaveTypeLabel;
  final String startDate;
  final String endDate;
  final String switchFromDate;
  final String switchToDate;
  final double daysCount;
  final String? leaderFeedback;
  final String? managerFeedback;
  final String reason;
  final String status;
  final String statusLabel;
  final String leaderStatus;
  final String managerStatus;
  final String createdAt;

  LeaveRequestItem({
    required this.id,
    required this.userId,
    required this.userName,
    required this.userAvatar,
    required this.teamName,
    required this.requestType,
    required this.requestTypeLabel,
    required this.leaveType,
    required this.leaveTypeLabel,
    required this.startDate,
    required this.endDate,
    required this.switchFromDate,
    required this.switchToDate,
    required this.daysCount,
    required this.reason,
    required this.status,
    required this.statusLabel,
    required this.leaderStatus,
    required this.managerStatus,
    required this.createdAt,
    this.leaderFeedback,
    this.managerFeedback,
  });

  String get daysLabel => daysCount % 1 == 0 ? '${daysCount.toInt()}' : daysCount.toStringAsFixed(1);

  factory LeaveRequestItem.fromJson(Map<String, dynamic> json) {
    return LeaveRequestItem(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      userId: int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      userName: (json['user_name'] ?? '').toString(),
      userAvatar: (json['user_avatar'] ?? '').toString(),
      teamName: (json['team_name'] ?? '').toString(),
      requestType: (json['request_type'] ?? 'leave').toString(),
      requestTypeLabel: (json['request_type_label'] ?? 'Leave').toString(),
      leaveType: (json['leave_type'] ?? '').toString(),
      leaveTypeLabel: (json['leave_type_label'] ?? '').toString(),
      startDate: (json['start_date'] ?? json['start_date_formatted'] ?? '').toString(),
      endDate: (json['end_date'] ?? json['end_date_formatted'] ?? '').toString(),
      switchFromDate: (json['switch_from_date'] ?? '').toString(),
      switchToDate: (json['switch_to_date'] ?? '').toString(),
      daysCount: double.tryParse(json['days_count']?.toString() ?? '1') ?? 1.0,
      leaderFeedback: json['leader_feedback']?.toString(),
      managerFeedback: json['manager_feedback']?.toString(),
      reason: (json['reason'] ?? '').toString(),
      status: (json['status'] ?? 'pending').toString(),
      statusLabel: (json['status_label'] ?? 'Pending').toString(),
      leaderStatus: (json['leader_status'] ?? 'Pending').toString(),
      managerStatus: (json['manager_status'] ?? 'Pending').toString(),
      createdAt: (json['created_at'] ?? '').toString(),
    );
  }
}

