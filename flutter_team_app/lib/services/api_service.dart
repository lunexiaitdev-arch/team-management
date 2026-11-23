import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'storage_service.dart';

class ApiException implements Exception {
  final String message;
  final int statusCode;

  ApiException(this.message, [this.statusCode = 400]);

  @override
  String toString() => message;
}

class ApiService {
  final StorageService storageService;

  ApiService(this.storageService);

  String get baseUrl => storageService.getBaseUrl();

  Map<String, String> _buildHeaders() {
    final token = storageService.getToken();
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'User-Agent': 'FBTeamMobile/1.0 (Flutter)',
    };

    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
      headers['X-FTM-Token'] = token;
    }

    return headers;
  }

  static const Duration _requestTimeout = Duration(seconds: 20);

  dynamic _processResponse(http.Response response) {
    dynamic body;
    bool isJson = true;
    try {
      body = jsonDecode(response.body);
    } catch (_) {
      isJson = false;
      body = {'message': response.body};
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    }

    String errorMessage = 'Server error (${response.statusCode})';
    if (!isJson) {
      final raw = response.body.trim();
      final looksLikeHtml =
          raw.startsWith('<') || raw.toLowerCase().contains('<html');
      if (looksLikeHtml) {
        errorMessage =
            'Server temporarily unavailable (${response.statusCode}). Please try again.';
      } else if (raw.isNotEmpty) {
        errorMessage = raw.length > 180 ? '${raw.substring(0, 180)}...' : raw;
      }
    } else if (body is Map && body['message'] != null) {
      final msg = body['message'].toString().trim();
      if (msg.isNotEmpty) {
        errorMessage = msg;
      }
    }

    throw ApiException(errorMessage, response.statusCode);
  }

  Future<dynamic> _send(Future<http.Response> Function() requestFn) async {
    try {
      final response = await requestFn().timeout(_requestTimeout);
      return _processResponse(response);
    } on TimeoutException {
      throw ApiException(
        'Request timed out. Please check your connection and try again.',
        408,
      );
    } on SocketException {
      throw ApiException(
        'No internet connection or server unreachable.',
        503,
      );
    } on http.ClientException catch (e) {
      throw ApiException(
        e.message.isNotEmpty ? e.message : 'Network error occurred.',
        503,
      );
    }
  }

  // Generic Request Handlers
  Future<dynamic> get(String endpoint, [Map<String, String>? queryParams]) async {
    Uri uri = Uri.parse('$baseUrl$endpoint');
    if (queryParams != null && queryParams.isNotEmpty) {
      uri = uri.replace(queryParameters: queryParams);
    }

    return _send(() => http.get(uri, headers: _buildHeaders()));
  }

  Future<dynamic> post(String endpoint, [dynamic data]) async {
    final uri = Uri.parse('$baseUrl$endpoint');
    return _send(
      () => http.post(
        uri,
        headers: _buildHeaders(),
        body: data != null ? jsonEncode(data) : null,
      ),
    );
  }

  Future<dynamic> put(String endpoint, [dynamic data]) async {
    final uri = Uri.parse('$baseUrl$endpoint');
    return _send(
      () => http.put(
        uri,
        headers: _buildHeaders(),
        body: data != null ? jsonEncode(data) : null,
      ),
    );
  }

  Future<dynamic> delete(String endpoint) async {
    final uri = Uri.parse('$baseUrl$endpoint');
    return _send(() => http.delete(uri, headers: _buildHeaders()));
  }

  // --- Auth APIs ---
  Future<Map<String, dynamic>> login(String username, String password) async {
    final response = await post('/auth/login', {
      'username': username,
      'password': password,
      'device_name': 'Flutter Mobile App',
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> getMe() async {
    final response = await get('/auth/me');
    return Map<String, dynamic>.from(response);
  }

  Future<void> logout() async {
    try {
      await post('/auth/logout');
    } catch (_) {}
  }

  // --- Dashboard & Stats ---
  Future<Map<String, dynamic>> getStats({String? month, String? team}) async {
    final query = <String, String>{};
    if (month != null && month.isNotEmpty) query['month'] = month;
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    final response = await get('/stats', query.isNotEmpty ? query : null);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> getManagerOverview() async {
    final response = await get('/manager/overview');
    return Map<String, dynamic>.from(response);
  }

  Future<List<dynamic>> getAlerts() async {
    final response = await get('/alerts');
    if (response is List) return response;
    if (response is Map && response.containsKey('alerts')) return response['alerts'];
    return [];
  }

  // --- Projects Pipeline ---
  Future<Map<String, dynamic>> getProjects({
    String? status,
    String? team,
    String? month,
    String? search,
    int? page,
    int? perPage,
  }) async {
    final query = <String, String>{};
    if (status != null && status.isNotEmpty && status != 'all') query['status'] = status;
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    if (month != null && month.isNotEmpty && month != 'carry_forward') query['month'] = month;
    if (search != null && search.isNotEmpty) query['search'] = search;
    if (page != null) query['page'] = page.toString();
    if (perPage != null) query['per_page'] = perPage.toString();

    final response = await get('/projects', query);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> getProject(int id) async {
    final response = await get('/projects/$id');
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> createProject(Map<String, dynamic> data) async {
    final response = await post('/projects', data);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateProjectProgress(int id, int progress, String note) async {
    final response = await post('/projects/$id/progress', {
      'progress': progress,
      'note': note,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateProjectStatus(int id, String status) async {
    final response = await post('/projects/$id/status', {
      'status': status,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> extendProjectDelivery(int id, String newDate, String reason) async {
    final cleanDate = newDate.length >= 10 ? newDate.substring(0, 10) : newDate;
    final response = await post('/projects/$id/extend', {
      'new_date': cleanDate,
      'new_delivery_date': cleanDate,
      'delivery_date': cleanDate,
      'reason': reason,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateProjectOnTime(int id, bool isOntime) async {
    final response = await post('/projects/$id/ontime', {
      'is_ontime': isOntime ? 1 : 0,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateProjectAssignees(
    int id,
    List<int> assignedUserIds, {
    String? teamName,
  }) async {
    final response = await post('/projects/$id/assignees', {
      'assigned_user_ids': assignedUserIds,
      if (teamName != null && teamName.isNotEmpty) 'team_name': teamName,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateProjectReview(
    int id, {
    required String clientReviewed,
    required double starRating,
    required String reviewFeedback,
  }) async {
    final response = await post('/projects/$id/review', {
      'client_reviewed': clientReviewed,
      'star_rating': starRating,
      'review_feedback': reviewFeedback,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateProjectSettlement(
    int id, {
    String? deliveredDate,
    String? tipDate,
    double? tipAmount,
  }) async {
    final response = await post('/projects/$id/settlement', {
      'delivered_date': deliveredDate,
      'tip_date': tipDate,
      if (tipAmount != null) 'tip_amount': tipAmount,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateProjectSplits(
    int id,
    Map<int, double> assigneeSplits,
  ) async {
    final payload = <String, dynamic>{
      'assignee_splits': assigneeSplits.map((k, v) => MapEntry(k.toString(), v)),
    };
    final response = await post('/projects/$id/split', payload);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> deleteProjectTask(int taskId) async {
    final response = await delete('/tasks/$taskId');
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> addProjectNote(int id, String note) async {
    final response = await post('/projects/$id/notes', {
      'note': note,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateProject(int id, Map<String, dynamic> data) async {
    final response = await post('/projects/$id', data);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> deleteProject(int id) async {
    final response = await delete('/projects/$id');
    return Map<String, dynamic>.from(response);
  }

  // --- Daily Reports / Standups ---
  Future<List<dynamic>> getDailyReports({
    String? date,
    String? dateFrom,
    String? dateTo,
    String? team,
    int? userId,
    String? scope,
    String? status,
    String? search,
  }) async {
    final query = <String, String>{};
    if (date != null && date.isNotEmpty) query['date'] = date;
    if (dateFrom != null && dateFrom.isNotEmpty) query['date_from'] = dateFrom;
    if (dateTo != null && dateTo.isNotEmpty) query['date_to'] = dateTo;
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    if (userId != null && userId > 0) query['user_id'] = userId.toString();
    if (scope != null && scope.isNotEmpty) query['scope'] = scope;
    if (status != null && status.isNotEmpty && status != 'all') query['status'] = status;
    if (search != null && search.trim().isNotEmpty) query['search'] = search.trim();

    final response = await get('/daily-reports', query);
    if (response is List) return response;
    if (response is Map && response.containsKey('reports')) return response['reports'];
    return [];
  }

  Future<Map<String, dynamic>> submitDailyReport(Map<String, dynamic> data) async {
    final response = await post('/daily-reports', data);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> reviewDailyReport(
    int id, {
    required String action, // 'approve', 'leader_approve', 'revision', 'reset', 'comment'
    String feedback = '',
  }) async {
    final response = await post('/daily-reports/$id/review', {
      'action': action,
      'feedback': feedback,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> forwardTeamReport(
    String date,
    String team, {
    String summaryNotes = '',
  }) async {
    final response = await post('/daily-reports/team-forward', {
      'report_date': date,
      'team_name': team,
      if (summaryNotes.trim().isNotEmpty) 'summary_notes': summaryNotes.trim(),
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> getAgencyDailySummary([String? date]) async {
    final query = <String, String>{};
    if (date != null) query['date'] = date;
    final response = await get('/daily-reports/agency-summary', query);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> getWeeklyOperationsSummary([String? date]) async {
    final query = <String, String>{};
    if (date != null) query['date'] = date;
    final response = await get('/daily-reports/weekly-summary', query);
    return Map<String, dynamic>.from(response);
  }

  // --- Attendance & Leaves ---
  Future<Map<String, dynamic>> getAttendance({String? date, String? team}) async {
    final query = <String, String>{};
    if (date != null && date.isNotEmpty) query['date'] = date;
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    final response = await get('/attendance', query);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> markAttendance({
    required int userId,
    required String date,
    required String status,
    String? notes,
  }) async {
    final response = await post('/attendance/mark', {
      'user_id': userId,
      'date': date,
      'status': status,
      if (notes != null) 'notes': notes,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateDayOff({
    required int userId,
    required String dayOff,
  }) async {
    final response = await post('/members/$userId/day-off', {
      'day_off': dayOff,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<List<dynamic>> getLeaves({String? status, int? userId, String? team}) async {
    final query = <String, String>{};
    if (status != null && status.isNotEmpty && status != 'all') query['status'] = status;
    if (userId != null && userId > 0) query['user_id'] = userId.toString();
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    final response = await get('/leaves', query);
    if (response is List) return response;
    if (response is Map && response.containsKey('leaves')) return response['leaves'];
    return [];
  }

  Future<Map<String, dynamic>> submitLeave(Map<String, dynamic> data) async {
    final response = await post('/leaves', data);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> reviewLeave({
    required int leaveId,
    required String action,
    String? feedback,
  }) async {
    final response = await post('/leaves/$leaveId/review', {
      'action': action,
      if (feedback != null) 'feedback': feedback,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateLeave(int leaveId, Map<String, dynamic> data) async {
    final response = await post('/leaves/$leaveId', data);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> deleteLeave(int leaveId) async {
    final response = await delete('/leaves/$leaveId');
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> getLeavesReport({String? month, String? team}) async {
    final query = <String, String>{};
    if (month != null && month.isNotEmpty) query['month'] = month;
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    final response = await get('/leaves/report', query);
    return Map<String, dynamic>.from(response);
  }

  // --- Time Tracker & Overtime Tracking ---
  Future<Map<String, dynamic>> getActiveTimeSession() async {
    final response = await get('/time-tracker/active');
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> startTimeTracking(Map<String, dynamic> data) async {
    final response = await post('/time-tracker/start', data);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> heartbeatTimeSession(int sessionId, int totalSeconds) async {
    final response = await post('/time-tracker/heartbeat', {
      'session_id': sessionId,
      'elapsed_seconds': totalSeconds,
      'total_seconds': totalSeconds,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> stopTimeTracking(Map<String, dynamic> data) async {
    final response = await post('/time-tracker/stop', data);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> getTimeTrackerStats({
    String? team,
    String? date,
    String? dateFrom,
    String? dateTo,
    int? userId,
  }) async {
    final query = <String, String>{};
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    if (date != null && date.isNotEmpty) query['date'] = date;
    if (dateFrom != null && dateFrom.isNotEmpty) query['date_from'] = dateFrom;
    if (dateTo != null && dateTo.isNotEmpty) query['date_to'] = dateTo;
    if (userId != null && userId > 0) query['user_id'] = userId.toString();
    final response = await get('/time-tracker/stats', query);
    return Map<String, dynamic>.from(response);
  }

  Future<List<Map<String, dynamic>>> getTimeSessions({
    String? status,
    String? team,
    String? sessionType,
    String? date,
    String? dateFrom,
    String? dateTo,
    String? search,
    int? userId,
    int limit = 100,
  }) async {
    final query = <String, String>{'limit': limit.toString()};
    if (status != null && status.isNotEmpty && status != 'all') query['status'] = status;
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    if (sessionType != null && sessionType.isNotEmpty && sessionType != 'all') query['session_type'] = sessionType;
    if (date != null && date.isNotEmpty) query['date'] = date;
    if (dateFrom != null && dateFrom.isNotEmpty) query['date_from'] = dateFrom;
    if (dateTo != null && dateTo.isNotEmpty) query['date_to'] = dateTo;
    if (search != null && search.trim().isNotEmpty) query['search'] = search.trim();
    if (userId != null && userId > 0) query['user_id'] = userId.toString();

    final response = await get('/time-tracker/sessions', query);
    if (response is List) {
      return response.map((e) => Map<String, dynamic>.from(e)).toList();
    }
    if (response is Map && response['sessions'] is List) {
      return (response['sessions'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
    }
    return [];
  }

  Future<Map<String, dynamic>> getTimeDailySummary({
    String? team,
    String? date,
    String? dateFrom,
    String? dateTo,
    String? sessionType,
    String? search,
    int? userId,
  }) async {
    final query = <String, String>{};
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    if (date != null && date.isNotEmpty) query['date'] = date;
    if (dateFrom != null && dateFrom.isNotEmpty) query['date_from'] = dateFrom;
    if (dateTo != null && dateTo.isNotEmpty) query['date_to'] = dateTo;
    if (sessionType != null && sessionType.isNotEmpty && sessionType != 'all') query['session_type'] = sessionType;
    if (search != null && search.trim().isNotEmpty) query['search'] = search.trim();
    if (userId != null && userId > 0) query['user_id'] = userId.toString();
    final response = await get('/time-tracker/daily-summary', query);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> getExtraTimeSummary({int? userId}) async {
    final query = <String, String>{};
    if (userId != null && userId > 0) query['user_id'] = userId.toString();
    final response = await get('/time-tracker/extra-time-summary', query);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> submitExtraTimeReport(Map<String, dynamic> data) async {
    final response = await post('/time-tracker/extra-time-report', data);
    return Map<String, dynamic>.from(response);
  }

  Future<List<Map<String, dynamic>>> getTimeSnapshots({
    String? sessionType,
    String? date,
    int? userId,
    int? sessionId,
    int? projectId,
    String? team,
    String? search,
    int limit = 100,
  }) async {
    final query = <String, String>{'limit': limit.toString()};
    if (sessionType != null && sessionType.isNotEmpty && sessionType != 'all') query['session_type'] = sessionType;
    if (date != null && date.isNotEmpty) query['date'] = date;
    if (userId != null && userId > 0) query['user_id'] = userId.toString();
    if (sessionId != null && sessionId > 0) query['session_id'] = sessionId.toString();
    if (projectId != null && projectId > 0) query['project_id'] = projectId.toString();
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    if (search != null && search.trim().isNotEmpty) query['search'] = search.trim();

    final response = await get('/time-tracker/snapshots', query);
    if (response is List) {
      return response.map((e) => Map<String, dynamic>.from(e)).toList();
    }
    if (response is Map && response['snapshots'] is List) {
      return (response['snapshots'] as List).map((e) => Map<String, dynamic>.from(e)).toList();
    }
    return [];
  }

  Future<Map<String, dynamic>> deleteSnapshot(int snapshotId) async {
    final response = await delete('/time-tracker/snapshots/$snapshotId');
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateSnapshot(int snapshotId, String auditNotes) async {
    final response = await post('/time-tracker/snapshots/$snapshotId/update', {
      'notes': auditNotes,
      'audit_notes': auditNotes,
    });
    return Map<String, dynamic>.from(response);
  }

  // --- Monthly Reports & Leaderboard ---
  Future<Map<String, dynamic>> getMonthlyReport({String? month, String? team}) async {
    final query = <String, String>{};
    if (month != null && month.isNotEmpty) query['month'] = month;
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;

    final response = await get('/reports/monthly', query);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> getLeaderboard({
    String? month,
    String? periodKey,
    String? period,
    String? team,
    String? sort,
  }) async {
    final query = <String, String>{};
    if (month != null && month.isNotEmpty) query['month'] = month;
    if (periodKey != null && periodKey.isNotEmpty) query['period_key'] = periodKey;
    if (period != null && period.isNotEmpty) query['period'] = period;
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    if (sort != null && sort.isNotEmpty) query['sort'] = sort;

    final response = await get('/reports/leaderboard', query);
    return Map<String, dynamic>.from(response);
  }

  // --- Team Members & Profiles ---
  Future<List<dynamic>> getTeamMembers([String? team, bool includeInactive = false]) async {
    final query = <String, String>{};
    if (team != null && team.isNotEmpty && team != 'all') query['team'] = team;
    if (includeInactive) query['include_inactive'] = '1';

    final response = await get('/team-members', query);
    if (response is List) return response;
    if (response is Map && response.containsKey('members')) return response['members'];
    return [];
  }

  Future<dynamic> createTeamMember(Map<String, dynamic> data) async {
    return post('/team-members', data);
  }

  Future<dynamic> updateTeamMember(int userId, Map<String, dynamic> data) async {
    return post('/team-members/$userId', data);
  }

  Future<dynamic> setMemberLeaveQuota(int userId, Map<String, dynamic> data) async {
    return post('/members/$userId/leave-quota', data);
  }

  Future<Map<String, dynamic>> getMemberProfile(int id) async {
    final response = await get('/team-members/$id/profile');
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> getMemberActivity(int id) async {
    final response = await get('/team-members/$id/activity');
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> addMemberMilestone(int userId, Map<String, dynamic> milestone) async {
    final response = await post('/team-members/$userId/milestones', milestone);
    return Map<String, dynamic>.from(response);
  }

  // --- Fiverr Profiles ---
  Future<List<dynamic>> getFiverrProfiles() async {
    final response = await get('/profiles');
    if (response is List) return response;
    if (response is Map && response.containsKey('profiles')) return response['profiles'];
    return [];
  }

  Future<Map<String, dynamic>> createFiverrProfile(Map<String, dynamic> data) async {
    final response = await post('/profiles', data);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> deleteFiverrProfile(int id) async {
    final response = await delete('/profiles/$id');
    return Map<String, dynamic>.from(response);
  }

  // --- Notices ---
  Future<Map<String, dynamic>> getNotices() async {
    final response = await get('/notices');
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> createNotice(String title, String content, String priority, bool isPinned) async {
    final response = await post('/notices', {
      'title': title,
      'content': content,
      'priority': priority,
      'is_pinned': isPinned ? 1 : 0,
    });
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> togglePinNotice(int id) async {
    final response = await post('/notices/$id/pin');
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> deleteNotice(int id) async {
    final response = await delete('/notices/$id');
    return Map<String, dynamic>.from(response);
  }

  // --- User Profile & Settings ---
  Future<Map<String, dynamic>> updateUserProfile(Map<String, dynamic> data) async {
    final response = await post('/user/profile', data);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> updateUserPassword(String currentPassword, String newPassword) async {
    final response = await post('/user/password', {
      'current_password': currentPassword,
      'new_password': newPassword,
    });
    return Map<String, dynamic>.from(response);
  }

  // --- Push & In-App Notifications ---
  Future<Map<String, dynamic>> getNotifications({
    int page = 1,
    int perPage = 50,
    bool unreadOnly = false,
  }) async {
    final query = <String, String>{
      'page': page.toString(),
      'per_page': perPage.toString(),
    };
    if (unreadOnly) {
      query['unread_only'] = '1';
    }
    final response = await get('/notifications', query);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> markNotificationRead(String id) async {
    final response = await post('/notifications/mark-read', {'id': id});
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> markAllNotificationsRead() async {
    final response = await post('/notifications/mark-read', {'all': true});
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> evaluateNotifications() async {
    final response = await post('/notifications/evaluate', {});
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> registerPushToken(String token, String platform) async {
    final response = await post('/notifications/register-token', {
      'token': token,
      'platform': platform,
    });
    return Map<String, dynamic>.from(response);
  }

  // --- Real-Time Messaging & Chat APIs ---
  Future<Map<String, dynamic>> getChatChannels() async {
    final response = await get('/messages/channels');
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> getChatMessages({
    required String type,
    required String id,
    int? recipientId,
    int? projectId,
    int limit = 50,
  }) async {
    final query = <String, String>{
      'type': type,
      'id': id,
      'limit': limit.toString(),
    };
    if (recipientId != null && recipientId > 0) {
      query['recipient_id'] = recipientId.toString();
    }
    if (projectId != null && projectId > 0) {
      query['project_id'] = projectId.toString();
    }

    final response = await get('/messages/thread', query);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> sendChatMessage({
    required String type,
    required String id,
    required String message,
    int? recipientId,
    int? projectId,
    String? attachmentUrl,
  }) async {
    final data = <String, dynamic>{
      'type': type,
      'id': id,
      'message': message,
    };
    if (recipientId != null && recipientId > 0) {
      data['recipient_id'] = recipientId;
    }
    if (projectId != null && projectId > 0) {
      data['project_id'] = projectId;
    }
    if (attachmentUrl != null && attachmentUrl.isNotEmpty) {
      data['attachment_url'] = attachmentUrl;
    }

    final response = await post('/messages/send', data);
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> markChatRead(String type, String id) async {
    final response = await post('/messages/mark-read', {
      'type': type,
      'id': id,
    });
    return Map<String, dynamic>.from(response);
  }
}

