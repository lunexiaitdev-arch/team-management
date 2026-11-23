import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/leaderboard.dart';
import '../services/api_service.dart';

class LeaderboardProvider extends ChangeNotifier {
  final ApiService _apiService;

  LeaderboardData? _leaderboardData;
  bool _isLoading = false;
  String? _errorMessage;

  String _selectedPeriod = DateFormat('yyyy-MM').format(DateTime.now().toUtc().add(const Duration(hours: 6)));
  String _selectedPeriodType = 'monthly';
  String _selectedTeam = 'all';
  String _selectedSort = 'revenue'; // Top Delivery Amount default
  String _searchQuery = '';

  LeaderboardProvider(this._apiService);

  LeaderboardData? get leaderboardData => _leaderboardData;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  String get selectedPeriod => _selectedPeriod;
  String get selectedPeriodType => _selectedPeriodType;
  String get selectedTeam => _selectedTeam;
  String get selectedSort => _selectedSort;
  String get searchQuery => _searchQuery;

  // Filtered rankings based on specialist search query
  List<LeaderboardItem> get filteredRankings {
    final list = _leaderboardData?.rankings ?? [];
    if (_searchQuery.isEmpty) return list;

    final q = _searchQuery.toLowerCase().trim();
    return list.where((item) {
      final matchName = item.name.toLowerCase().contains(q);
      final matchCall = item.callSign.toLowerCase().contains(q);
      final matchId = item.customId.toLowerCase().contains(q);
      final matchTeam = item.team.toLowerCase().contains(q);
      final matchRole = item.role.toLowerCase().contains(q);
      return matchName || matchCall || matchId || matchTeam || matchRole;
    }).toList();
  }

  // Strictly starts from October 2026 up to current month
  List<Map<String, String>> get defaultAvailablePeriods {
    final List<Map<String, String>> list = [];
    final now = DateTime.now().toUtc().add(const Duration(hours: 6));
    DateTime cursor = DateTime(now.year, now.month, 1);
    final origin = DateTime(2026, 10, 1);

    while (!cursor.isBefore(origin)) {
      final key = DateFormat('yyyy-MM').format(cursor);
      final isCurrent = (cursor.year == now.year && cursor.month == now.month);
      final label = isCurrent
          ? '${DateFormat('MMMM yyyy').format(cursor)} (Current)'
          : DateFormat('MMMM yyyy').format(cursor);
      list.add({'key': key, 'label': label, 'type': 'monthly'});
      cursor = DateTime(cursor.year, cursor.month - 1, 1);
    }
    list.add({'key': 'all_time', 'label': 'All-Time Records', 'type': 'all_time'});
    return list;
  }

  void setSelectedPeriod(String period, [String type = 'monthly']) {
    _selectedPeriod = period;
    _selectedPeriodType = type;
    fetchLeaderboard();
  }

  void setSelectedTeam(String team) {
    _selectedTeam = team;
    fetchLeaderboard();
  }

  void setSort(String sort) {
    _selectedSort = sort;
    fetchLeaderboard();
  }

  void setSearchQuery(String query) {
    _searchQuery = query;
    notifyListeners();
  }

  Future<void> fetchLeaderboard() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await _apiService.getLeaderboard(
        month: _selectedPeriod,
        periodKey: _selectedPeriod,
        period: _selectedPeriodType,
        team: _selectedTeam,
        sort: _selectedSort,
      );
      _leaderboardData = LeaderboardData.fromJson(res);
    } catch (e) {
      _errorMessage = 'Failed to load leaderboard: $e';
    }

    _isLoading = false;
    notifyListeners();
  }
}
