import 'dart:async';
import 'package:flutter/material.dart';
import '../models/time_tracker.dart';
import '../services/api_service.dart';

class TimeTrackerProvider extends ChangeNotifier {
  final ApiService _api;

  TimeTrackerProvider(this._api);

  ActiveTrackingSession? _activeSession;
  TimeTrackerStats? _stats;
  List<TimeSessionItem> _sessions = [];
  DailyTimeSummaryResponse? _dailySummary;
  List<TimeSnapshotItem> _snapshots = [];

  double _totalExtraHours = 0.0;
  String _totalExtraFormatted = '0h 00m';
  int _totalExtraSessionsCount = 0;
  int _totalExtraSnapshotsCount = 0;
  List<ExtraTimeHistoryItem> _extraHistory = [];

  bool _isLoadingActive = false;
  bool _isLoadingStats = false;
  bool _isLoadingSessions = false;
  bool _isLoadingDaily = false;
  bool _isLoadingSnapshots = false;
  bool _isLoadingExtra = false;
  bool _isSubmitting = false;

  Timer? _tickTimer;
  Timer? _heartbeatTimer;

  ActiveTrackingSession? get activeSession => _activeSession;
  TimeTrackerStats? get stats => _stats;
  List<TimeSessionItem> get sessions => _sessions;
  DailyTimeSummaryResponse? get dailySummary => _dailySummary;
  List<TimeSnapshotItem> get snapshots => _snapshots;

  double get totalExtraHours => _totalExtraHours;
  String get totalExtraFormatted => _totalExtraFormatted;
  int get totalExtraSessionsCount => _totalExtraSessionsCount;
  int get totalExtraSnapshotsCount => _totalExtraSnapshotsCount;
  List<ExtraTimeHistoryItem> get extraHistory => _extraHistory;

  bool get isLoadingActive => _isLoadingActive;
  bool get isLoadingStats => _isLoadingStats;
  bool get isLoadingSessions => _isLoadingSessions;
  bool get isLoadingDaily => _isLoadingDaily;
  bool get isLoadingSnapshots => _isLoadingSnapshots;
  bool get isLoadingExtra => _isLoadingExtra;
  bool get isSubmitting => _isSubmitting;

  bool get isTracking => _activeSession != null;

  int get currentElapsedSeconds => _activeSession?.liveElapsedSeconds ?? 0;

  String get formattedClock => formatHmsClock(currentElapsedSeconds);

  static String formatHmsClock(int totalSec) {
    final s = totalSec < 0 ? 0 : totalSec;
    final h = s ~/ 3600;
    final m = (s % 3600) ~/ 60;
    final rem = s % 60;
    return '${h.toString().padLeft(2, '0')}:${m.toString().padLeft(2, '0')}:${rem.toString().padLeft(2, '0')}';
  }

  static String formatHmsCompact(int totalSec) {
    final s = totalSec < 0 ? 0 : totalSec;
    if (s > 0 && s < 60) {
      return '0m ${s.toString().padLeft(2, '0')}s';
    }
    final h = s ~/ 3600;
    final m = (s % 3600) ~/ 60;
    return '${h}h ${m.toString().padLeft(2, '0')}m';
  }

  void _startTimers() {
    _stopTimers();
    _tickTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (_activeSession != null) {
        notifyListeners();
      }
    });
    _heartbeatTimer = Timer.periodic(const Duration(seconds: 30), (_) {
      sendHeartbeat();
    });
  }

  void _stopTimers() {
    _tickTimer?.cancel();
    _tickTimer = null;
    _heartbeatTimer?.cancel();
    _heartbeatTimer = null;
  }

  @override
  void dispose() {
    _stopTimers();
    super.dispose();
  }

  Future<void> sendHeartbeat() async {
    final s = _activeSession;
    if (s == null || s.sessionId <= 0) return;
    try {
      await _api.heartbeatTimeSession(s.sessionId, s.liveElapsedSeconds);
    } catch (e) {
      debugPrint('Heartbeat error: $e');
    }
  }

  Future<void> fetchActiveSession({bool silent = false}) async {
    if (!silent) {
      _isLoadingActive = true;
      notifyListeners();
    }
    try {
      final res = await _api.getActiveTimeSession();
      if (res['success'] == true && res['data'] is Map) {
        _activeSession = ActiveTrackingSession.fromJson(
          Map<String, dynamic>.from(res['data']),
        );
        _startTimers();
      } else {
        _activeSession = null;
        _stopTimers();
      }
    } catch (e) {
      debugPrint('Fetch active session error: $e');
    } finally {
      _isLoadingActive = false;
      notifyListeners();
    }
  }

  Future<({bool success, String message})> startSession({
    int? projectId,
    String orderId = '',
    String clientName = '',
    String profileName = '',
    String sessionType = 'regular',
  }) async {
    _isSubmitting = true;
    notifyListeners();

    try {
      final res = await _api.startTimeTracking({
        if (projectId != null && projectId > 0) 'project_id': projectId,
        'order_id': orderId,
        'client_name': clientName,
        'profile_name': profileName,
        'session_type': sessionType,
      });

      if (res['success'] == true && res['data'] is Map) {
        _activeSession = ActiveTrackingSession.fromJson(
          Map<String, dynamic>.from(res['data']),
        );
        _startTimers();
        _isSubmitting = false;
        notifyListeners();
        return (
          success: true,
          message: res['message']?.toString() ?? 'Time tracker started!',
        );
      }
      _isSubmitting = false;
      notifyListeners();
      return (
        success: false,
        message: res['message']?.toString() ?? 'Failed to start session.',
      );
    } catch (e) {
      _isSubmitting = false;
      notifyListeners();
      return (success: false, message: 'Failed to start: $e');
    }
  }

  Future<({bool success, String message})> stopSession({
    bool logToDailyReport = true,
    String tasksCompleted = '',
    String extraTimeReason = '',
  }) async {
    final current = _activeSession;
    if (current == null || current.sessionId <= 0) {
      return (success: false, message: 'No active session found.');
    }

    _isSubmitting = true;
    notifyListeners();

    final finalSec = current.liveElapsedSeconds;

    try {
      final res = await _api.stopTimeTracking({
        'session_id': current.sessionId,
        'total_seconds': finalSec,
        'elapsed_seconds': finalSec,
        'log_to_daily_report': logToDailyReport,
        'tasks_completed': tasksCompleted,
        'extra_time_reason': extraTimeReason,
      });

      if (res['success'] == true) {
        _activeSession = null;
        _stopTimers();
        _isSubmitting = false;
        notifyListeners();
        return (
          success: true,
          message: res['message']?.toString() ?? 'Session stopped & logged!',
        );
      }
      _isSubmitting = false;
      notifyListeners();
      return (
        success: false,
        message: res['message']?.toString() ?? 'Failed to stop session.',
      );
    } catch (e) {
      _isSubmitting = false;
      notifyListeners();
      return (success: false, message: 'Failed to stop: $e');
    }
  }

  Future<({bool success, String message})> submitExtraTimeReport({
    int? projectId,
    String orderId = '',
    String clientName = '',
    required String hours,
    required String tasksCompleted,
    String extraTimeReason = '',
    String? reportDate,
  }) async {
    _isSubmitting = true;
    notifyListeners();

    try {
      final res = await _api.submitExtraTimeReport({
        if (projectId != null && projectId > 0) 'project_id': projectId,
        'order_id': orderId,
        'client_name': clientName,
        'hours': hours,
        'tasks_completed': tasksCompleted,
        'extra_time_reason': extraTimeReason,
        if (reportDate != null && reportDate.isNotEmpty) 'report_date': reportDate,
      });

      _isSubmitting = false;
      if (res['success'] == true) {
        await fetchExtraTimeSummary(silent: true);
        notifyListeners();
        return (
          success: true,
          message:
              res['message']?.toString() ?? 'Extra time report submitted!',
        );
      }
      notifyListeners();
      return (
        success: false,
        message:
            res['message']?.toString() ?? 'Failed to submit extra time report.',
      );
    } catch (e) {
      _isSubmitting = false;
      notifyListeners();
      return (success: false, message: 'Error submitting extra time: $e');
    }
  }

  Future<void> fetchExtraTimeSummary({int? userId, bool silent = false}) async {
    if (!silent) {
      _isLoadingExtra = true;
      notifyListeners();
    }
    try {
      final res = await _api.getExtraTimeSummary(userId: userId);
      _totalExtraHours =
          double.tryParse(res['total_extra_hours']?.toString() ?? '0') ?? 0.0;
      _totalExtraFormatted =
          res['total_extra_formatted']?.toString() ?? '0h 00m';
      _totalExtraSessionsCount =
          int.tryParse(res['total_sessions_count']?.toString() ?? '0') ?? 0;
      _totalExtraSnapshotsCount =
          int.tryParse(res['total_snapshots_count']?.toString() ?? '0') ?? 0;

      if (res['history'] is List) {
        _extraHistory = (res['history'] as List)
            .map((it) =>
                ExtraTimeHistoryItem.fromJson(Map<String, dynamic>.from(it)))
            .toList();
      } else {
        _extraHistory = [];
      }
    } catch (e) {
      debugPrint('Fetch extra time summary error: $e');
    } finally {
      _isLoadingExtra = false;
      notifyListeners();
    }
  }

  Future<void> fetchStats({
    String? team,
    String? date,
    String? dateFrom,
    String? dateTo,
    int? userId,
    bool silent = false,
  }) async {
    if (!silent) {
      _isLoadingStats = true;
      notifyListeners();
    }
    try {
      final res = await _api.getTimeTrackerStats(
        team: team,
        date: date,
        dateFrom: dateFrom,
        dateTo: dateTo,
        userId: userId,
      );
      _stats = TimeTrackerStats.fromJson(res);
    } catch (e) {
      debugPrint('Fetch tracker stats error: $e');
    } finally {
      _isLoadingStats = false;
      notifyListeners();
    }
  }

  Future<void> fetchDailySummary({
    String? team,
    String? date,
    String? dateFrom,
    String? dateTo,
    String? sessionType,
    String? search,
    int? userId,
    bool silent = false,
  }) async {
    if (!silent) {
      _isLoadingDaily = true;
      notifyListeners();
    }
    try {
      final res = await _api.getTimeDailySummary(
        team: team,
        date: date,
        dateFrom: dateFrom,
        dateTo: dateTo,
        sessionType: sessionType,
        search: search,
        userId: userId,
      );
      _dailySummary = DailyTimeSummaryResponse.fromJson(res);
    } catch (e) {
      debugPrint('Fetch daily summary error: $e');
    } finally {
      _isLoadingDaily = false;
      notifyListeners();
    }
  }

  Future<void> fetchSessions({
    String? status,
    String? team,
    String? sessionType,
    String? date,
    String? dateFrom,
    String? dateTo,
    String? search,
    int? userId,
    int limit = 100,
    bool silent = false,
  }) async {
    if (!silent) {
      _isLoadingSessions = true;
      notifyListeners();
    }
    try {
      final list = await _api.getTimeSessions(
        status: status,
        team: team,
        sessionType: sessionType,
        date: date,
        dateFrom: dateFrom,
        dateTo: dateTo,
        search: search,
        userId: userId,
        limit: limit,
      );
      _sessions = list.map((e) => TimeSessionItem.fromJson(e)).toList();
    } catch (e) {
      debugPrint('Fetch sessions error: $e');
    } finally {
      _isLoadingSessions = false;
      notifyListeners();
    }
  }

  Future<void> fetchSnapshots({
    String? sessionType,
    String? date,
    int? userId,
    int? sessionId,
    int? projectId,
    String? team,
    String? search,
    int limit = 100,
    bool silent = false,
  }) async {
    if (!silent) {
      _isLoadingSnapshots = true;
      notifyListeners();
    }
    try {
      final list = await _api.getTimeSnapshots(
        sessionType: sessionType,
        date: date,
        userId: userId,
        sessionId: sessionId,
        projectId: projectId,
        team: team,
        search: search,
        limit: limit,
      );
      _snapshots = list.map((e) => TimeSnapshotItem.fromJson(e)).toList();
    } catch (e) {
      debugPrint('Fetch snapshots error: $e');
    } finally {
      _isLoadingSnapshots = false;
      notifyListeners();
    }
  }

  Future<bool> updateSnapshotNotes(int snapshotId, String notes) async {
    try {
      final res = await _api.updateSnapshot(snapshotId, notes);
      if (res['success'] == true) {
        final idx = _snapshots.indexWhere((s) => s.id == snapshotId);
        if (idx >= 0) {
          final old = _snapshots[idx];
          _snapshots[idx] = TimeSnapshotItem(
            id: old.id,
            sessionId: old.sessionId,
            userId: old.userId,
            specialistName: old.specialistName,
            avatar: old.avatar,
            projectId: old.projectId,
            orderId: old.orderId,
            clientName: old.clientName,
            teamName: old.teamName,
            sessionType: old.sessionType,
            notes: notes,
            url: old.url,
            elapsedSeconds: old.elapsedSeconds,
            elapsedLabel: old.elapsedLabel,
            capturedAt: old.capturedAt,
            capturedAtFormatted: old.capturedAtFormatted,
          );
          notifyListeners();
        }
        return true;
      }
    } catch (e) {
      debugPrint('Update snapshot notes error: $e');
    }
    return false;
  }

  Future<bool> deleteSnapshot(int snapshotId) async {
    try {
      final res = await _api.deleteSnapshot(snapshotId);
      if (res['success'] == true) {
        _snapshots.removeWhere((s) => s.id == snapshotId);
        notifyListeners();
        return true;
      }
    } catch (e) {
      debugPrint('Delete snapshot error: $e');
    }
    return false;
  }
}

