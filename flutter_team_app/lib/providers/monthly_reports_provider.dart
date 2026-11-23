import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/monthly_report.dart';
import '../services/api_service.dart';

class MonthlyReportsProvider extends ChangeNotifier {
  final ApiService _apiService;

  MonthlyReportData? _monthlyData;
  bool _isLoading = false;
  String? _errorMessage;

  late String _selectedMonth;
  String _selectedTeam = 'all';
  String _searchQuery = '';

  MonthlyReportsProvider(this._apiService) {
    final months = availableReportingMonths;
    _selectedMonth = months.isNotEmpty ? months.first['key']! : DateFormat('yyyy-MM').format(DateTime.now());
    final nowBst = DateTime.now().toUtc().add(const Duration(hours: 6));
    _selectedMonth = months.isNotEmpty ? months.first['key']! : DateFormat('yyyy-MM').format(nowBst);
  }

  MonthlyReportData? get monthlyData => _monthlyData;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  String get selectedMonth => _selectedMonth;
  String get selectedTeam => _selectedTeam;
  String get searchQuery => _searchQuery;

  List<MonthlyReportRow> get filteredRows {
    final rows = _monthlyData?.rows ?? [];
    if (_searchQuery.isEmpty) return rows;

    final q = _searchQuery.toLowerCase().trim();
    return rows.where((row) {
      final matchName = row.name.toLowerCase().contains(q);
      final matchCall = row.callSign.toLowerCase().contains(q);
      final matchId = row.customId.toLowerCase().contains(q);
      final matchTeam = row.team.toLowerCase().contains(q);
      final matchRole = row.role.toLowerCase().contains(q);
      return matchName || matchCall || matchId || matchTeam || matchRole;
    }).toList();
  }

  /// Strictly starts from October 2026 up to current month (no pre-Oct 2026, no unreached future months)
  static List<Map<String, String>> get availableReportingMonths {
    final List<Map<String, String>> list = [];
    final now = DateTime.now().toUtc().add(const Duration(hours: 6));
    DateTime cursor = DateTime(now.year, now.month, 1);
    final origin = DateTime(2026, 10, 1); // Earliest origin: October 2026

    while (!cursor.isBefore(origin)) {
      final key = DateFormat('yyyy-MM').format(cursor);
      final isCurrent = (cursor.year == now.year && cursor.month == now.month);
      final label = isCurrent
          ? '${DateFormat('MMMM yyyy').format(cursor)} (Current)'
          : DateFormat('MMMM yyyy').format(cursor);
      list.add({'key': key, 'label': label});
      cursor = DateTime(cursor.year, cursor.month - 1, 1);
    }
    return list;
  }

  void setSelectedMonth(String month) {
    _selectedMonth = month;
    fetchMonthlyReport();
  }

  void setSelectedTeam(String team) {
    _selectedTeam = team;
    fetchMonthlyReport();
  }

  void setSearchQuery(String query) {
    _searchQuery = query;
    notifyListeners();
  }

  Future<void> fetchMonthlyReport() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await _apiService.getMonthlyReport(
        month: _selectedMonth,
        team: _selectedTeam,
      );
      _monthlyData = MonthlyReportData.fromJson(res);
    } catch (e) {
      _errorMessage = 'Failed to load monthly sheet: $e';
    }

    _isLoading = false;
    notifyListeners();
  }
}
