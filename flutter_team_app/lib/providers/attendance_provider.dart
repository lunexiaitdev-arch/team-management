import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/attendance.dart';
import '../services/api_service.dart';

class AttendanceProvider extends ChangeNotifier {
  final ApiService _apiService;

  List<AttendanceRosterItem> _roster = [];
  AttendanceKpis? _kpis;
  List<LeaveRequestItem> _leaves = [];
  Map<String, dynamic>? _leavesReport;
  bool _isLoadingReport = false;
  bool _isLoading = false;
  String? _errorMessage;

  String _selectedDate = DateFormat('yyyy-MM-dd').format(DateTime.now());
  String _selectedTeam = 'all';
  String _leavesFilter = 'all';
  String _reportMonth = DateFormat('yyyy-MM').format(DateTime.now());

  AttendanceProvider(this._apiService);

  List<AttendanceRosterItem> get roster => _roster;
  AttendanceKpis? get kpis => _kpis;
  List<LeaveRequestItem> get leaves => _leaves;
  Map<String, dynamic>? get leavesReport => _leavesReport;
  bool get isLoadingReport => _isLoadingReport;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  String get selectedDate => _selectedDate;
  String get selectedTeam => _selectedTeam;
  String get leavesFilter => _leavesFilter;
  String get reportMonth => _reportMonth;

  int get pendingLeavesCount => _leaves.where((l) => l.status == 'pending' || l.status == 'leader_approved').length;

  void setSelectedDate(String date) {
    _selectedDate = date;
    fetchAttendance();
  }

  void setSelectedTeam(String team) {
    _selectedTeam = team;
    fetchAttendance();
  }

  void setLeavesFilter(String filter) {
    _leavesFilter = filter;
    fetchLeaves();
  }

  Future<void> fetchAttendance() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await _apiService.getAttendance(
        date: _selectedDate,
        team: _selectedTeam,
      );

      if (res['kpis'] is Map) {
        _kpis = AttendanceKpis.fromJson(Map<String, dynamic>.from(res['kpis']));
      }

      if (res['roster'] is List) {
        _roster = (res['roster'] as List)
            .map((item) => AttendanceRosterItem.fromJson(Map<String, dynamic>.from(item)))
            .toList();
      }

      // Also refresh leaves queue
      await fetchLeaves();
    } catch (e) {
      _errorMessage = 'Failed to load attendance roster: $e';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> fetchLeaves() async {
    try {
      final raw = await _apiService.getLeaves(status: _leavesFilter);
      _leaves = raw
          .map((item) => LeaveRequestItem.fromJson(Map<String, dynamic>.from(item)))
          .toList();
      notifyListeners();
    } catch (_) {}
  }

  Future<bool> markAttendance({
    required int userId,
    required String status,
    String? notes,
  }) async {
    try {
      await _apiService.markAttendance(
        userId: userId,
        date: _selectedDate,
        status: status,
        notes: notes,
      );
      await fetchAttendance();
      return true;
    } catch (e) {
      _errorMessage = 'Error updating attendance: $e';
      notifyListeners();
      return false;
    }
  }

  Future<bool> updateDayOff({
    required int userId,
    required String dayOff,
  }) async {
    try {
      await _apiService.updateDayOff(
        userId: userId,
        dayOff: dayOff,
      );
      await fetchAttendance();
      return true;
    } catch (e) {
      _errorMessage = 'Error updating day off: $e';
      notifyListeners();
      return false;
    }
  }

  Future<bool> submitLeave(Map<String, dynamic> data) async {
    try {
      await _apiService.submitLeave(data);
      await fetchAttendance();
      return true;
    } catch (e) {
      _errorMessage = 'Error submitting leave: $e';
      notifyListeners();
      return false;
    }
  }

  Future<bool> reviewLeave({
    required int leaveId,
    required String action,
    String? feedback,
  }) async {
    try {
      await _apiService.reviewLeave(
        leaveId: leaveId,
        action: action,
        feedback: feedback,
      );
      await fetchAttendance();
      return true;
    } catch (e) {
      _errorMessage = 'Error reviewing leave: $e';
      notifyListeners();
      return false;
    }
  }

  void setReportMonth(String month) {
    _reportMonth = month;
    fetchLeavesReport();
  }

  Future<void> fetchLeavesReport() async {
    _isLoadingReport = true;
    notifyListeners();
    try {
      final res = await _apiService.getLeavesReport(
        month: _reportMonth,
        team: _selectedTeam,
      );
      _leavesReport = res;
    } catch (e) {
      debugPrint('Error fetching leaves report: $e');
    } finally {
      _isLoadingReport = false;
      notifyListeners();
    }
  }

  Future<bool> updateLeave(int leaveId, Map<String, dynamic> data) async {
    try {
      await _apiService.updateLeave(leaveId, data);
      await fetchAttendance();
      await fetchLeavesReport();
      return true;
    } catch (e) {
      _errorMessage = 'Error updating leave: $e';
      notifyListeners();
      return false;
    }
  }

  Future<bool> deleteLeave(int leaveId) async {
    try {
      await _apiService.deleteLeave(leaveId);
      await fetchAttendance();
      await fetchLeavesReport();
      return true;
    } catch (e) {
      _errorMessage = 'Error cancelling leave: $e';
      notifyListeners();
      return false;
    }
  }

  Future<bool> setMemberLeaveQuota(int userId, Map<String, dynamic> data) async {
    try {
      await _apiService.setMemberLeaveQuota(userId, data);
      await fetchAttendance();
      await fetchLeavesReport();
      return true;
    } catch (e) {
      _errorMessage = 'Error updating leave quota: $e';
      notifyListeners();
      return false;
    }
  }
}

