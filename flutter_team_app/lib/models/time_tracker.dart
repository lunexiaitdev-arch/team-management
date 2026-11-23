class TimeSnapshotItem {
  final int id;
  final int sessionId;
  final int userId;
  final String specialistName;
  final String avatar;
  final int projectId;
  final String orderId;
  final String clientName;
  final String teamName;
  final String sessionType;
  final String notes;
  final String url;
  final int elapsedSeconds;
  final String elapsedLabel;
  final int mouseClicks;
  final int keyboardClicks;
  final String capturedAt;
  final String capturedAtFormatted;

  TimeSnapshotItem({
    required this.id,
    this.sessionId = 0,
    required this.userId,
    this.specialistName = 'Specialist',
    this.avatar = '',
    this.projectId = 0,
    required this.orderId,
    this.clientName = '',
    this.teamName = 'WpCraft',
    this.sessionType = 'regular',
    this.notes = '',
    this.url = '',
    this.elapsedSeconds = 0,
    this.elapsedLabel = '',
    this.mouseClicks = 0,
    this.keyboardClicks = 0,
    this.capturedAt = '',
    this.capturedAtFormatted = '',
  });

  bool get isExtraTime => sessionType == 'extra_time';
  // Backwards-compatible aliases
  String get userName => specialistName;
  String get userAvatar => avatar;
  String get team => teamName;
  String get auditNotes => notes;
  String get imageUrl => url;
  String get createdAt => capturedAt;
  String get elapsedFormatted => elapsedLabel;
  String get formattedTime =>
      capturedAtFormatted.isNotEmpty ? capturedAtFormatted : capturedAt;

  factory TimeSnapshotItem.fromJson(Map<String, dynamic> json) {
    final elapsedSec =
        int.tryParse(json['elapsed_seconds']?.toString() ?? '0') ?? 0;
    final rawElapsedLabel = (json['elapsed_label'] ??
            json['elapsed_formatted'] ??
            '')
        .toString();
    return TimeSnapshotItem(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      sessionId: int.tryParse(json['session_id']?.toString() ?? '0') ?? 0,
      userId: int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      specialistName: (json['specialist_name'] ??
              json['user_name'] ??
              'Specialist')
          .toString(),
      avatar: (json['avatar'] ?? json['user_avatar'] ?? '').toString(),
      projectId: int.tryParse(json['project_id']?.toString() ?? '0') ?? 0,
      orderId: (json['order_id'] ?? '').toString(),
      clientName: (json['client_name'] ?? '').toString(),
      teamName: (json['team_name'] ?? json['team'] ?? 'WpCraft').toString(),
      sessionType: (json['session_type'] ?? 'regular').toString(),
      notes: (json['notes'] ?? json['audit_notes'] ?? '').toString(),
      url: (json['url'] ?? json['image_url'] ?? json['snapshot_url'] ?? '')
          .toString(),
      elapsedSeconds: elapsedSec,
      elapsedLabel: rawElapsedLabel.isNotEmpty
          ? rawElapsedLabel
          : '+${(elapsedSec / 60).round()}m',
      mouseClicks: int.tryParse(json['mouse_clicks']?.toString() ?? '0') ?? 0,
      keyboardClicks: int.tryParse(json['keyboard_clicks']?.toString() ??
              json['key_clicks']?.toString() ??
              '0') ??
          0,
      capturedAt:
          (json['captured_at'] ?? json['created_at'] ?? '').toString(),
      capturedAtFormatted: (json['captured_at_formatted'] ??
              json['formatted_time'] ??
              json['created_at'] ??
              '')
          .toString(),
    );
  }
}

class ActiveTrackingSession {
  final int sessionId;
  final int userId;
  final int projectId;
  final String orderId;
  final String clientName;
  final String profileName;
  final String sessionType;
  final String startTime;
  final int startTimestamp;
  final int elapsedSeconds;
  final int snapshotsCount;
  final int totalMouseClicks;
  final int totalKeyboardClicks;
  final String notes;
  final String status;
  final DateTime fetchedAt;

  ActiveTrackingSession({
    required this.sessionId,
    this.userId = 0,
    required this.projectId,
    required this.orderId,
    this.clientName = '',
    this.profileName = '',
    this.sessionType = 'regular',
    this.startTime = '',
    this.startTimestamp = 0,
    this.elapsedSeconds = 0,
    this.snapshotsCount = 0,
    this.totalMouseClicks = 0,
    this.totalKeyboardClicks = 0,
    this.notes = '',
    this.status = 'active',
    DateTime? fetchedAt,
  }) : fetchedAt = fetchedAt ?? DateTime.now();

  int get id => sessionId;
  String get startedAt => startTime;
  int get snapshotCount => snapshotsCount;
  bool get isExtraTime => sessionType == 'extra_time';

  int get liveElapsedSeconds {
    final delta = DateTime.now().difference(fetchedAt).inSeconds;
    return elapsedSeconds + (delta > 0 ? delta : 0);
  }

  factory ActiveTrackingSession.fromJson(Map<String, dynamic> json) {
    return ActiveTrackingSession(
      sessionId: int.tryParse(
              json['session_id']?.toString() ?? json['id']?.toString() ?? '0') ??
          0,
      userId: int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      projectId: int.tryParse(json['project_id']?.toString() ?? '0') ?? 0,
      orderId: (json['order_id'] ?? '').toString(),
      clientName: (json['client_name'] ?? '').toString(),
      profileName: (json['profile_name'] ?? '').toString(),
      sessionType: (json['session_type'] ?? 'regular').toString(),
      startTime:
          (json['start_time'] ?? json['started_at'] ?? '').toString(),
      startTimestamp:
          int.tryParse(json['start_timestamp']?.toString() ?? '0') ?? 0,
      elapsedSeconds:
          int.tryParse(json['elapsed_seconds']?.toString() ?? '0') ?? 0,
      snapshotsCount: int.tryParse(
              json['snapshots_count']?.toString() ??
                  json['snapshot_count']?.toString() ??
                  '0') ??
          0,
      totalMouseClicks: int.tryParse(
              json['total_mouse_clicks']?.toString() ??
                  json['mouse_clicks']?.toString() ??
                  '0') ??
          0,
      totalKeyboardClicks: int.tryParse(
              json['total_keyboard_clicks']?.toString() ??
                  json['keyboard_clicks']?.toString() ??
                  '0') ??
          0,
      notes: (json['notes'] ?? '').toString(),
      status: (json['status'] ?? 'active').toString(),
      fetchedAt: DateTime.now(),
    );
  }
}

class ExtraTimeHistoryItem {
  final int id;
  final int reportId;
  final int projectId;
  final String orderId;
  final String clientName;
  final double hoursSpent;
  final String reportDate;
  final String reportDateFormatted;
  final double extraTimeHours;
  final String extraTimeFormatted;
  final String extraTimeReason;
  final String sessionType;
  final String tasksCompleted;
  final String status;

  ExtraTimeHistoryItem({
    required this.id,
    this.reportId = 0,
    this.projectId = 0,
    required this.orderId,
    this.clientName = '',
    this.hoursSpent = 0.0,
    this.reportDate = '',
    this.reportDateFormatted = '',
    this.extraTimeHours = 0.0,
    this.extraTimeFormatted = '',
    this.extraTimeReason = '',
    this.sessionType = 'extra_time',
    this.tasksCompleted = '',
    this.status = 'submitted',
  });

  String get deliverables => tasksCompleted;
  String get date => reportDate;

  factory ExtraTimeHistoryItem.fromJson(Map<String, dynamic> json) {
    final hrs = double.tryParse(
            json['extra_time_hours']?.toString() ??
                json['hours']?.toString() ??
                '0') ??
        0.0;
    final mins = (hrs * 60).round();
    final fallbackFormatted = '${mins ~/ 60}h ${(mins % 60).toString().padLeft(2, '0')}m';

    return ExtraTimeHistoryItem(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      reportId: int.tryParse(json['report_id']?.toString() ?? '0') ?? 0,
      projectId: int.tryParse(json['project_id']?.toString() ?? '0') ?? 0,
      orderId: (json['order_id'] ?? '').toString(),
      clientName: (json['client_name'] ?? '').toString(),
      hoursSpent: double.tryParse(json['hours_spent']?.toString() ?? '0') ?? 0.0,
      reportDate: (json['report_date'] ?? json['date'] ?? '').toString(),
      reportDateFormatted:
          (json['report_date_formatted'] ?? json['report_date'] ?? json['date'] ?? '')
              .toString(),
      extraTimeHours: hrs,
      extraTimeFormatted:
          (json['extra_time_formatted']?.toString().isNotEmpty == true)
              ? json['extra_time_formatted'].toString()
              : fallbackFormatted,
      extraTimeReason:
          (json['extra_time_reason'] ?? 'Overtime Contribution').toString(),
      sessionType: json['session_type']?.toString() ?? 'extra_time',
      tasksCompleted: (json['tasks_completed'] ??
              json['deliverables'] ??
              '')
          .toString(),
      status: (json['status'] ?? 'submitted').toString(),
    );
  }
}

class ActiveHubSession {
  final int sessionId;
  final int userId;
  final String specialistName;
  final String avatar;
  final String teamName;
  final int projectId;
  final String orderId;
  final String clientName;
  final String profileName;
  final String startTime;
  final int totalSeconds;
  final String timeFormatted;
  final int snapshotsCount;
  final String latestSnapshotUrl;

  ActiveHubSession({
    required this.sessionId,
    required this.userId,
    required this.specialistName,
    required this.avatar,
    required this.teamName,
    required this.projectId,
    required this.orderId,
    required this.clientName,
    required this.profileName,
    required this.startTime,
    required this.totalSeconds,
    required this.timeFormatted,
    required this.snapshotsCount,
    required this.latestSnapshotUrl,
  });

  factory ActiveHubSession.fromJson(Map<String, dynamic> json) {
    return ActiveHubSession(
      sessionId: int.tryParse(json['session_id']?.toString() ?? '0') ?? 0,
      userId: int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      specialistName: (json['specialist_name'] ?? 'Specialist').toString(),
      avatar: (json['avatar'] ?? '').toString(),
      teamName: (json['team_name'] ?? 'WpCraft').toString(),
      projectId: int.tryParse(json['project_id']?.toString() ?? '0') ?? 0,
      orderId: (json['order_id'] ?? '').toString(),
      clientName: (json['client_name'] ?? '').toString(),
      profileName: (json['profile_name'] ?? '').toString(),
      startTime: (json['start_time'] ?? '').toString(),
      totalSeconds:
          int.tryParse(json['total_seconds']?.toString() ?? '0') ?? 0,
      timeFormatted: (json['time_formatted'] ?? '0h 00m').toString(),
      snapshotsCount:
          int.tryParse(json['snapshots_count']?.toString() ?? '0') ?? 0,
      latestSnapshotUrl: (json['latest_snapshot_url'] ?? '').toString(),
    );
  }
}

class TimeTrackerStats {
  final int totalSeconds;
  final String totalHoursFormatted;
  final int todaySeconds;
  final String todayHoursFormatted;
  final String selectedDate;
  final int selectedSeconds;
  final String selectedHoursFormatted;
  final int totalSnapshots;
  final int activeSessionsCount;
  final List<ActiveHubSession> activeSessions;

  TimeTrackerStats({
    required this.totalSeconds,
    required this.totalHoursFormatted,
    required this.todaySeconds,
    required this.todayHoursFormatted,
    required this.selectedDate,
    required this.selectedSeconds,
    required this.selectedHoursFormatted,
    required this.totalSnapshots,
    required this.activeSessionsCount,
    required this.activeSessions,
  });

  factory TimeTrackerStats.fromJson(Map<String, dynamic> json) {
    final activeList = json['active_sessions'] is List
        ? (json['active_sessions'] as List)
            .map((e) => ActiveHubSession.fromJson(Map<String, dynamic>.from(e)))
            .toList()
        : <ActiveHubSession>[];

    return TimeTrackerStats(
      totalSeconds:
          int.tryParse(json['total_seconds']?.toString() ?? '0') ?? 0,
      totalHoursFormatted:
          (json['total_hours_formatted'] ?? '0h 00m').toString(),
      todaySeconds:
          int.tryParse(json['today_seconds']?.toString() ?? '0') ?? 0,
      todayHoursFormatted:
          (json['today_hours_formatted'] ?? '0h 00m').toString(),
      selectedDate: (json['selected_date'] ?? '').toString(),
      selectedSeconds:
          int.tryParse(json['selected_seconds']?.toString() ?? '0') ?? 0,
      selectedHoursFormatted:
          (json['selected_hours_formatted'] ?? '0h 00m').toString(),
      totalSnapshots:
          int.tryParse(json['total_snapshots']?.toString() ?? '0') ?? 0,
      activeSessionsCount:
          int.tryParse(json['active_sessions_count']?.toString() ?? '0') ??
              activeList.length,
      activeSessions: activeList,
    );
  }
}

class TimeSessionItem {
  final int id;
  final int userId;
  final String specialistName;
  final String avatar;
  final int projectId;
  final String orderId;
  final String clientName;
  final String profileName;
  final String teamName;
  final String sessionType;
  final String workDate;
  final String startTime;
  final String endTime;
  final int totalSeconds;
  final String timeFormatted;
  final int snapshotsCount;
  final String status;
  final bool loggedToReport;
  final List<TimeSnapshotItem> snapshots;

  TimeSessionItem({
    required this.id,
    required this.userId,
    required this.specialistName,
    required this.avatar,
    required this.projectId,
    required this.orderId,
    required this.clientName,
    required this.profileName,
    required this.teamName,
    required this.sessionType,
    required this.workDate,
    required this.startTime,
    required this.endTime,
    required this.totalSeconds,
    required this.timeFormatted,
    required this.snapshotsCount,
    required this.status,
    required this.loggedToReport,
    this.snapshots = const [],
  });

  factory TimeSessionItem.fromJson(Map<String, dynamic> json) {
    final snapsRaw = json['snapshots'] is List ? json['snapshots'] as List : [];
    return TimeSessionItem(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      userId: int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      specialistName: (json['specialist_name'] ?? 'Specialist').toString(),
      avatar: (json['avatar'] ?? '').toString(),
      projectId: int.tryParse(json['project_id']?.toString() ?? '0') ?? 0,
      orderId: (json['order_id'] ?? '').toString(),
      clientName: (json['client_name'] ?? '').toString(),
      profileName: (json['profile_name'] ?? '').toString(),
      teamName: (json['team_name'] ?? 'WpCraft').toString(),
      sessionType: (json['session_type'] ?? 'regular').toString(),
      workDate: (json['work_date'] ?? '').toString(),
      startTime: (json['start_time'] ?? '').toString(),
      endTime: (json['end_time'] ?? '').toString(),
      totalSeconds:
          int.tryParse(json['total_seconds']?.toString() ?? '0') ?? 0,
      timeFormatted: (json['time_formatted'] ?? '0h 00m').toString(),
      snapshotsCount:
          int.tryParse(json['snapshots_count']?.toString() ?? '0') ?? 0,
      status: (json['status'] ?? 'completed').toString(),
      loggedToReport: json['logged_to_report'] == true ||
          json['logged_to_report'] == 1 ||
          json['logged_to_report'] == '1',
      snapshots: snapsRaw
          .map((e) => TimeSnapshotItem.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

class DailyOrderBreakdown {
  final int projectId;
  final String orderId;
  final String clientName;
  final int seconds;
  final String timeFormatted;

  DailyOrderBreakdown({
    required this.projectId,
    required this.orderId,
    required this.clientName,
    required this.seconds,
    required this.timeFormatted,
  });

  factory DailyOrderBreakdown.fromJson(Map<String, dynamic> json) {
    return DailyOrderBreakdown(
      projectId: int.tryParse(json['project_id']?.toString() ?? '0') ?? 0,
      orderId: (json['order_id'] ?? '').toString(),
      clientName: (json['client_name'] ?? '').toString(),
      seconds: int.tryParse(json['seconds']?.toString() ?? '0') ?? 0,
      timeFormatted: (json['time_formatted'] ?? '0h 00m').toString(),
    );
  }
}

class DailyTimeRecordItem {
  final String date;
  final String dateFormatted;
  final bool isToday;
  final int userId;
  final String specialistName;
  final String avatar;
  final String teamName;
  final int regularSeconds;
  final String regularFormatted;
  final int extraSeconds;
  final String extraFormatted;
  final int totalSeconds;
  final String totalFormatted;
  final double totalHoursDecimal;
  final int sessionsCount;
  final int activeCount;
  final int snapshotsCount;
  final List<DailyOrderBreakdown> orders;

  DailyTimeRecordItem({
    required this.date,
    required this.dateFormatted,
    required this.isToday,
    required this.userId,
    required this.specialistName,
    required this.avatar,
    required this.teamName,
    required this.regularSeconds,
    required this.regularFormatted,
    required this.extraSeconds,
    required this.extraFormatted,
    required this.totalSeconds,
    required this.totalFormatted,
    required this.totalHoursDecimal,
    required this.sessionsCount,
    required this.activeCount,
    required this.snapshotsCount,
    required this.orders,
  });

  factory DailyTimeRecordItem.fromJson(Map<String, dynamic> json) {
    final ordersRaw = json['orders'] is List ? json['orders'] as List : [];
    return DailyTimeRecordItem(
      date: (json['date'] ?? '').toString(),
      dateFormatted: (json['date_formatted'] ?? json['date'] ?? '').toString(),
      isToday: json['is_today'] == true || json['is_today'] == 1,
      userId: int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      specialistName: (json['specialist_name'] ?? 'Specialist').toString(),
      avatar: (json['avatar'] ?? '').toString(),
      teamName: (json['team_name'] ?? 'WpCraft').toString(),
      regularSeconds:
          int.tryParse(json['regular_seconds']?.toString() ?? '0') ?? 0,
      regularFormatted: (json['regular_formatted'] ?? '0h 00m').toString(),
      extraSeconds:
          int.tryParse(json['extra_seconds']?.toString() ?? '0') ?? 0,
      extraFormatted: (json['extra_formatted'] ?? '0h 00m').toString(),
      totalSeconds:
          int.tryParse(json['total_seconds']?.toString() ?? '0') ?? 0,
      totalFormatted: (json['total_formatted'] ?? '0h 00m').toString(),
      totalHoursDecimal:
          double.tryParse(json['total_hours_decimal']?.toString() ?? '0') ??
              0.0,
      sessionsCount:
          int.tryParse(json['sessions_count']?.toString() ?? '0') ?? 0,
      activeCount: int.tryParse(json['active_count']?.toString() ?? '0') ?? 0,
      snapshotsCount:
          int.tryParse(json['snapshots_count']?.toString() ?? '0') ?? 0,
      orders: ordersRaw
          .map((e) => DailyOrderBreakdown.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

class DailyTimeTotalItem {
  final String date;
  final String dateFormatted;
  final bool isToday;
  final int specialistsCount;
  final int sessionsCount;
  final int snapshotsCount;
  final int regularSeconds;
  final String regularFormatted;
  final int extraSeconds;
  final String extraFormatted;
  final int totalSeconds;
  final String totalFormatted;

  DailyTimeTotalItem({
    required this.date,
    required this.dateFormatted,
    required this.isToday,
    required this.specialistsCount,
    required this.sessionsCount,
    required this.snapshotsCount,
    required this.regularSeconds,
    required this.regularFormatted,
    required this.extraSeconds,
    required this.extraFormatted,
    required this.totalSeconds,
    required this.totalFormatted,
  });

  factory DailyTimeTotalItem.fromJson(Map<String, dynamic> json) {
    return DailyTimeTotalItem(
      date: (json['date'] ?? '').toString(),
      dateFormatted: (json['date_formatted'] ?? json['date'] ?? '').toString(),
      isToday: json['is_today'] == true || json['is_today'] == 1,
      specialistsCount:
          int.tryParse(json['specialists_count']?.toString() ?? '0') ?? 0,
      sessionsCount:
          int.tryParse(json['sessions_count']?.toString() ?? '0') ?? 0,
      snapshotsCount:
          int.tryParse(json['snapshots_count']?.toString() ?? '0') ?? 0,
      regularSeconds:
          int.tryParse(json['regular_seconds']?.toString() ?? '0') ?? 0,
      regularFormatted: (json['regular_formatted'] ?? '0h 00m').toString(),
      extraSeconds:
          int.tryParse(json['extra_seconds']?.toString() ?? '0') ?? 0,
      extraFormatted: (json['extra_formatted'] ?? '0h 00m').toString(),
      totalSeconds:
          int.tryParse(json['total_seconds']?.toString() ?? '0') ?? 0,
      totalFormatted: (json['total_formatted'] ?? '0h 00m').toString(),
    );
  }
}

class TimeSpecialistOption {
  final int userId;
  final String name;
  final String team;

  TimeSpecialistOption({
    required this.userId,
    required this.name,
    required this.team,
  });

  factory TimeSpecialistOption.fromJson(Map<String, dynamic> json) {
    return TimeSpecialistOption(
      userId: int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      name: (json['name'] ?? 'Specialist').toString(),
      team: (json['team'] ?? '').toString(),
    );
  }
}

class DailySummaryTotals {
  final int totalSeconds;
  final String totalFormatted;
  final int regularSeconds;
  final String regularFormatted;
  final int extraSeconds;
  final String extraFormatted;
  final int daysCount;
  final int sessionsCount;
  final int snapshotsCount;

  DailySummaryTotals({
    required this.totalSeconds,
    required this.totalFormatted,
    required this.regularSeconds,
    required this.regularFormatted,
    required this.extraSeconds,
    required this.extraFormatted,
    required this.daysCount,
    required this.sessionsCount,
    required this.snapshotsCount,
  });

  factory DailySummaryTotals.fromJson(Map<String, dynamic> json) {
    return DailySummaryTotals(
      totalSeconds:
          int.tryParse(json['total_seconds']?.toString() ?? '0') ?? 0,
      totalFormatted: (json['total_formatted'] ?? '0h 00m').toString(),
      regularSeconds:
          int.tryParse(json['regular_seconds']?.toString() ?? '0') ?? 0,
      regularFormatted: (json['regular_formatted'] ?? '0h 00m').toString(),
      extraSeconds:
          int.tryParse(json['extra_seconds']?.toString() ?? '0') ?? 0,
      extraFormatted: (json['extra_formatted'] ?? '0h 00m').toString(),
      daysCount: int.tryParse(json['days_count']?.toString() ?? '0') ?? 0,
      sessionsCount:
          int.tryParse(json['sessions_count']?.toString() ?? '0') ?? 0,
      snapshotsCount:
          int.tryParse(json['snapshots_count']?.toString() ?? '0') ?? 0,
    );
  }
}

class DailyTimeSummaryResponse {
  final DailySummaryTotals summary;
  final List<DailyTimeTotalItem> dailyTotals;
  final List<DailyTimeRecordItem> records;
  final List<TimeSpecialistOption> specialists;

  DailyTimeSummaryResponse({
    required this.summary,
    required this.dailyTotals,
    required this.records,
    required this.specialists,
  });

  factory DailyTimeSummaryResponse.fromJson(Map<String, dynamic> json) {
    final sumMap = json['summary'] is Map
        ? Map<String, dynamic>.from(json['summary'])
        : <String, dynamic>{};
    final totalsRaw =
        json['daily_totals'] is List ? json['daily_totals'] as List : [];
    final recordsRaw = json['records'] is List ? json['records'] as List : [];
    final specsRaw =
        json['specialists'] is List ? json['specialists'] as List : [];

    return DailyTimeSummaryResponse(
      summary: DailySummaryTotals.fromJson(sumMap),
      dailyTotals: totalsRaw
          .map((e) => DailyTimeTotalItem.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      records: recordsRaw
          .map(
              (e) => DailyTimeRecordItem.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      specialists: specsRaw
          .map((e) =>
              TimeSpecialistOption.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}
