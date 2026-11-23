import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/daily_report.dart';
import '../services/api_service.dart';

class DailyReportsProvider extends ChangeNotifier {
  final ApiService _apiService;

  List<DailyReport> _reports = [];
  List<DailyReport> _todayReports = [];
  List<DailyReport> _myReports = [];
  bool _isLoading = false;
  bool _isLoadingMine = false;
  String? _errorMessage;

  String _selectedDate = 'all'; // 'all' shows all recent submissions by default for Manager/Leader oversight
  String _mySelectedDate = DateFormat('yyyy-MM-dd').format(DateTime.now());
  String _selectedTeam = 'all';
  String _selectedStatus = 'all';
  String _searchQuery = '';

  DailyReportsProvider(this._apiService);

  List<DailyReport> get reports => _reports;
  List<DailyReport> get todayReports => _todayReports;
  List<DailyReport> get myReports => _myReports;
  bool get isLoading => _isLoading;
  bool get isLoadingMine => _isLoadingMine;
  String? get errorMessage => _errorMessage;
  String get selectedDate => _selectedDate;
  String get mySelectedDate => _mySelectedDate;
  String get selectedTeam => _selectedTeam;
  String get selectedStatus => _selectedStatus;
  String get searchQuery => _searchQuery;

  List<DailyReport> get filteredReports {
    return _reports.where((r) {
      if (_selectedStatus != 'all' && r.status != _selectedStatus) {
        return false;
      }
      if (_searchQuery.trim().isNotEmpty) {
        final q = _searchQuery.trim().toLowerCase();
        final matchUser = r.userName.toLowerCase().contains(q) ||
            r.callSign.toLowerCase().contains(q) ||
            r.team.toLowerCase().contains(q);
        final matchTask = r.items.any((it) =>
            it.orderId.toLowerCase().contains(q) ||
            it.clientName.toLowerCase().contains(q) ||
            it.tasksCompleted.toLowerCase().contains(q) ||
            it.workInProgress.toLowerCase().contains(q));
        if (!matchUser && !matchTask) return false;
      }
      return true;
    }).toList();
  }

  void setSelectedDate(String date) {
    _selectedDate = date;
    if (date != 'all') {
      _mySelectedDate = date;
    }
    fetchReports();
    fetchMyReports();
  }

  void setMySelectedDate(String date) {
    _mySelectedDate = date;
    fetchMyReports();
  }

  void setSelectedTeam(String team) {
    _selectedTeam = team;
    fetchReports();
  }

  void setSelectedStatus(String status) {
    _selectedStatus = status;
    notifyListeners();
  }

  void setSearchQuery(String query) {
    _searchQuery = query;
    notifyListeners();
  }

  Future<void> fetchMyReports({bool includeHistory = true}) async {
    _isLoadingMine = true;
    notifyListeners();

    try {
      final res = await _apiService.getDailyReports(
        date: includeHistory ? null : _mySelectedDate,
        scope: 'mine',
      );
      _myReports = res
          .map((item) => DailyReport.fromJson(Map<String, dynamic>.from(item)))
          .toList();
    } catch (e) {
      debugPrint('Failed to load my standups: $e');
    }

    _isLoadingMine = false;
    notifyListeners();
  }

  Future<void> fetchReports() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final todayStr = DateFormat('yyyy-MM-dd').format(DateTime.now());
      final futures = <Future<List<dynamic>>>[
        _apiService.getDailyReports(
          date: _selectedDate,
          team: _selectedTeam,
        ),
        if (_selectedDate == 'all')
          _apiService.getDailyReports(
            date: todayStr,
            team: _selectedTeam,
          ),
      ];

      final results = await Future.wait(futures);
      _reports = results[0]
          .map((item) => DailyReport.fromJson(Map<String, dynamic>.from(item)))
          .toList();

      if (_selectedDate == 'all' && results.length > 1) {
        _todayReports = results[1]
            .map((item) => DailyReport.fromJson(Map<String, dynamic>.from(item)))
            .toList();
      } else if (_selectedDate == todayStr) {
        _todayReports = List.from(_reports);
      }
    } catch (e) {
      _errorMessage = 'Failed to load standups: $e';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<({bool success, String message})> submitStandup({
    required String date,
    String reportType = 'work',
    String status = 'submitted',
    double totalHours = 0.0,
    String blockers = '',
    String tomorrowPlan = '',
    List<DailyReportTaskItem> items = const [],
    List<int>? completedTaskIds,
    bool replaceAll = false,
    int? existingReportId,
    String? leaveStartDate,
    String? leaveEndDate,
    String? leaveType,
    String? leaveReason,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final payload = <String, dynamic>{
        'report_date': date,
        'report_type': reportType,
        'status': status,
        if (existingReportId != null && existingReportId > 0)
          'report_id': existingReportId,
        if (replaceAll) 'replace_all': true,
      };

      if (reportType == 'on_leave') {
        payload['leave_start_date'] = leaveStartDate ?? date;
        payload['leave_end_date'] = leaveEndDate ?? leaveStartDate ?? date;
        payload['leave_type'] = leaveType ?? 'casual_leave';
        payload['leave_reason'] = leaveReason ?? '';
      } else if (reportType == 'work') {
        payload['total_hours'] = totalHours;
        payload['blockers'] = blockers;
        payload['tomorrow_plan'] = tomorrowPlan;
        payload['items'] = items.map((i) => i.toJson()).toList();
        if (completedTaskIds != null && completedTaskIds.isNotEmpty) {
          payload['completed_task_ids'] = completedTaskIds;
        }
      }

      final res = await _apiService.submitDailyReport(payload);
      if (res['success'] == true) {
        await Future.wait([fetchReports(), fetchMyReports()]);
        _isLoading = false;
        notifyListeners();
        return (
          success: true,
          message: res['message']?.toString() ?? 'Daily Standup logged!',
        );
      }
      _errorMessage = res['message']?.toString() ?? 'Failed to submit standup';
      _isLoading = false;
      notifyListeners();
      return (success: false, message: _errorMessage!);
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return (success: false, message: _errorMessage!);
    }
  }

  Future<({bool success, String message})> reviewStandupAction(
    int reportId,
    String action, {
    String feedback = '',
  }) async {
    try {
      final res = await _apiService.reviewDailyReport(
        reportId,
        action: action,
        feedback: feedback,
      );
      if (res['success'] == true) {
        await Future.wait([fetchReports(), fetchMyReports()]);
        return (
          success: true,
          message: res['message']?.toString() ?? 'Review action saved.',
        );
      }
      return (
        success: false,
        message: res['message']?.toString() ?? 'Review update failed.',
      );
    } catch (e) {
      return (success: false, message: e.toString());
    }
  }

  Future<bool> reviewStandup(
    int reportId,
    bool approved,
    String comment, {
    bool isManager = false,
  }) async {
    final action = approved ? (isManager ? 'approve' : 'leader_approve') : 'revision';
    final res = await reviewStandupAction(reportId, action, feedback: comment);
    return res.success;
  }

  Future<({bool success, String message})> forwardTeamReport(
    String date,
    String team, {
    String summaryNotes = '',
  }) async {
    try {
      final effectiveDate = (date == 'all' || date.isEmpty)
          ? DateFormat('yyyy-MM-dd').format(DateTime.now())
          : date;
      final res = await _apiService.forwardTeamReport(
        effectiveDate,
        team,
        summaryNotes: summaryNotes,
      );
      if (res['success'] == true) {
        return (
          success: true,
          message: res['message']?.toString() ??
              'Team Daily Status Report forwarded!',
        );
      }
      return (
        success: false,
        message: res['message']?.toString() ?? 'Failed to forward report.',
      );
    } catch (e) {
      return (success: false, message: e.toString());
    }
  }
}
