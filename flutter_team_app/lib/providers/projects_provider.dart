import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/project.dart';
import '../models/stats.dart';
import '../services/api_service.dart';

class ProjectsProvider extends ChangeNotifier {
  final ApiService _apiService;

  List<ProjectItem> _projects = [];
  ExecutiveStats? _stats;
  bool _isLoading = false;
  String? _errorMessage;

  String _selectedStatus = 'all';
  String _selectedTeam = 'all';
  late String _selectedMonth;
  String _searchQuery = '';

  ProjectsProvider(this._apiService) {
    // Default to current month (matches Website All Projects default)
    _selectedMonth = DateFormat('yyyy-MM').format(DateTime.now());
    // Default to current month in Bangladesh Standard Time (UTC+6) — matches Website 100%
    final nowBst = DateTime.now().toUtc().add(const Duration(hours: 6));
    _selectedMonth = DateFormat('yyyy-MM').format(nowBst);
  }

  List<ProjectItem> get projects => _projects;
  ExecutiveStats? get stats => _stats;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  String get selectedStatus => _selectedStatus;
  String get selectedTeam => _selectedTeam;
  String get selectedMonth => _selectedMonth;
  String get searchQuery => _searchQuery;

  void setSelectedMonth(String month) {
    if (_selectedMonth == month) return;
    _selectedMonth = month;
    notifyListeners();
    fetchDashboardData();
  }

  List<ProjectItem> get filteredProjects {
    return _projects.where((p) {
      // Carry forward filter (real YYYY-MM months are already filtered accurately server-side)
      if (_selectedMonth == 'carry_forward' && !p.isCarryForward) {
        return false;
      }
      // Status filter
      if (_selectedStatus != 'all') {
        if (_selectedStatus == 'active') {
          if (!p.isActive) return false;
          final st = p.status.toLowerCase();
          if (st != 'wip' && st != 'nra' && st != 'revision') return false;
        } else if (_selectedStatus == 'delivered_all') {
          if (!p.isCompleted) return false;
          if (!p.isCompleted && !p.isSubmitted) return false;
        } else if (_selectedStatus == 'repeat' || _selectedStatus == 'repeat_client') {
          if (!p.isRepeatClient) return false;
        } else if (p.status.toLowerCase() != _selectedStatus.toLowerCase()) {
          return false;
        }
      }

      // Team filter
      if (_selectedTeam != 'all') {
        final pTeam = p.team.toLowerCase().replaceAll(' ', '');
        final sTeam = _selectedTeam.toLowerCase().replaceAll(' ', '');
        if (pTeam != sTeam) {
          return false;
        }
      }

      // Search query
      if (_searchQuery.isNotEmpty) {
        final q = _searchQuery.toLowerCase().trim();
        final matchOrder = p.orderId.toLowerCase().contains(q);
        final matchBuyer = p.buyerUsername.toLowerCase().contains(q);
        final matchTitle = p.title.toLowerCase().contains(q);
        final matchProfile = p.profileName.toLowerCase().contains(q);
        final matchTeam = p.team.toLowerCase().contains(q);
        final matchAssignee = p.assignees.any((a) =>
            a.name.toLowerCase().contains(q) ||
            a.callSign.toLowerCase().contains(q));

        if (!matchOrder && !matchBuyer && !matchTitle && !matchProfile && !matchTeam && !matchAssignee) {
          return false;
        }
      }

      return true;
    }).toList();
  }

  bool get _useServerTotals =>
      _stats != null &&
      _selectedTeam == 'all' &&
      _searchQuery.isEmpty &&
      _selectedMonth != 'carry_forward';

  // Count getters
  int get countAll => _useServerTotals ? _stats!.total : _projects.length;
  int get countActive => _useServerTotals
      ? _stats!.activeOrdersCount
      : _projects.where((p) {
          final st = p.status.toLowerCase();
          return st == 'wip' || st == 'nra' || st == 'revision';
        }).length;
  int get countDeliveredAll => _useServerTotals
      ? (_stats!.deliveryAccepted + _stats!.completed)
      : _projects.where((p) => p.isCompleted).length;
  int get countRepeat => _useServerTotals ? _stats!.repeatOrdersCount : _projects.where((p) => p.isRepeatClient).length;
  int get countCarryForward => _projects.where((p) => p.isCarryForward).length;
  int get countNra => _useServerTotals ? _stats!.nra : _projects.where((p) => p.status.toLowerCase() == 'nra').length;
  int get countWip => _useServerTotals ? _stats!.wip : _projects.where((p) => p.status.toLowerCase() == 'wip').length;
  int get countSubmitted => _useServerTotals ? _stats!.deliverySubmitted : _projects.where((p) => p.status.toLowerCase() == 'delivery_submitted').length;
  int get countRevision => _useServerTotals ? _stats!.revision : _projects.where((p) => p.status.toLowerCase() == 'revision').length;
  int get countAccepted => _useServerTotals ? _stats!.deliveryAccepted : _projects.where((p) => p.status.toLowerCase() == 'delivery_accepted').length;
  int get countCompleted => _projects.where((p) => p.status.toLowerCase() == 'completed').length;
  int get countCancelled => _useServerTotals ? _stats!.cancelled : _projects.where((p) => p.status.toLowerCase() == 'cancelled').length;

  double get totalRepeatNet => _useServerTotals
      ? _stats!.repeatNetRevenue
      : _projects.where((p) => p.isRepeatClient).fold(0.0, (sum, p) => sum + p.netValue);

  // Realized and active pipeline financial KPI getters (100% aligned with Website /stats & Monthly Sheet)
  double get totalSubmittedValue => _useServerTotals
      ? _stats!.deliverySubmittedValue
      : _projects
          .where((p) => p.status.toLowerCase() == 'delivery_submitted')
          .fold(0.0, (sum, p) => sum + p.netValue);

  double get totalWipValue => _useServerTotals
      ? _stats!.activeWipValue
      : _projects
          .where((p) => p.status.toLowerCase() == 'wip' || p.status.toLowerCase() == 'nra')
          .fold(0.0, (sum, p) => sum + p.netValue);

  double get totalRevisionValue => _useServerTotals
      ? _stats!.activeRevisionValue
      : _projects
          .where((p) => p.status.toLowerCase() == 'revision')
          .fold(0.0, (sum, p) => sum + p.netValue);

  double get totalDeliveredNet => _useServerTotals
      ? _stats!.totalNetRevenue
      : _projects
          .where((p) => p.isCompleted)
          .fold(0.0, (sum, p) => sum + p.netValue);

  double get totalCancelledNet => _useServerTotals
      ? _stats!.cancelledNet
      : _projects
          .where((p) => p.status.toLowerCase() == 'cancelled')
          .fold(0.0, (sum, p) => sum + p.netValue);

  double get totalTips => _useServerTotals
      ? _stats!.totalTips
      : _projects.fold(0.0, (sum, p) => sum + p.tips);

  void setStatusFilter(String status) {
    _selectedStatus = status;
    notifyListeners();
  }

  void setTeamFilter(String team) {
    _selectedTeam = team;
    notifyListeners();
  }

  void setSearchQuery(String query) {
    _searchQuery = query;
    notifyListeners();
  }

  Future<void> fetchDashboardData() async {
    await Future.wait([
      fetchStats(),
      fetchProjects(),
    ]);
  }

  Future<void> fetchStats() async {
    try {
      final apiMonth = _selectedMonth == 'carry_forward' ? 'all' : _selectedMonth;
      final res = await _apiService.getStats(month: apiMonth);
      _stats = ExecutiveStats.fromJson(res);
      notifyListeners();
    } catch (_) {}
  }

  Future<void> fetchProjects() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final apiMonth = _selectedMonth == 'carry_forward' ? 'all' : _selectedMonth;
      // Pass perPage: 250 & exact month so app matches Website All Projects 100%
      final res = await _apiService.getProjects(perPage: 250, month: apiMonth);
      if (res['items'] is List) {
        _projects = (res['items'] as List)
            .map((item) => ProjectItem.fromJson(Map<String, dynamic>.from(item)))
            .toList();
      }
    } on ApiException catch (e) {
      _errorMessage = e.message;
    } catch (e) {
      _errorMessage = 'Failed to load projects: ${e.toString()}';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> updateProgress(int projectId, int progressPercent, String note) async {
    try {
      final res = await _apiService.updateProjectProgress(projectId, progressPercent, note);
      if (res['success'] == true) {
        await fetchProjects();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> updateStatus(int projectId, String newStatus) async {
    try {
      final res = await _apiService.updateProjectStatus(projectId, newStatus);
      if (res['success'] == true) {
        await fetchProjects();
        await fetchStats();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> extendDelivery(int projectId, String newDeliveryDate, String reason) async {
    try {
      final res = await _apiService.extendProjectDelivery(projectId, newDeliveryDate, reason);
      if (res['success'] == true || res['id'] != null) {
        await fetchProjects();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> updateOnTime(int projectId, bool isOntime) async {
    try {
      final res = await _apiService.updateProjectOnTime(projectId, isOntime);
      if (res['success'] == true) {
        await fetchProjects();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> addProject(Map<String, dynamic> projectData) async {
    try {
      final res = await _apiService.createProject(projectData);
      if (res['success'] == true) {
        await fetchProjects();
        await fetchStats();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> updateProject(int projectId, Map<String, dynamic> data) async {
    try {
      final res = await _apiService.updateProject(projectId, data);
      if (res['success'] == true) {
        await fetchProjects();
        await fetchStats();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> deleteProject(int projectId) async {
    try {
      final res = await _apiService.deleteProject(projectId);
      if (res['success'] == true) {
        await fetchProjects();
        await fetchStats();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> addNote(int projectId, String note) async {
    try {
      final res = await _apiService.addProjectNote(projectId, note);
      if (res['success'] == true) {
        await fetchProjects();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<({bool success, String message})> updateAssignees(
    int projectId,
    List<int> assignedUserIds, {
    String? teamName,
  }) async {
    try {
      final res = await _apiService.updateProjectAssignees(
        projectId,
        assignedUserIds,
        teamName: teamName,
      );
      if (res['success'] == true) {
        await fetchProjects();
        return (success: true, message: res['message']?.toString() ?? 'Assignees updated');
      }
      return (success: false, message: res['message']?.toString() ?? 'Failed to update assignees');
    } catch (e) {
      return (success: false, message: e.toString());
    }
  }

  Future<({bool success, String message})> updateReview(
    int projectId, {
    required String clientReviewed,
    required double starRating,
    required String reviewFeedback,
  }) async {
    try {
      final res = await _apiService.updateProjectReview(
        projectId,
        clientReviewed: clientReviewed,
        starRating: starRating,
        reviewFeedback: reviewFeedback,
      );
      if (res['success'] == true) {
        await fetchProjects();
        await fetchStats();
        return (success: true, message: 'Client review & rating saved');
      }
      return (success: false, message: res['message']?.toString() ?? 'Failed to save review');
    } catch (e) {
      return (success: false, message: e.toString());
    }
  }

  Future<({bool success, String message})> updateSettlement(
    int projectId, {
    String? deliveredDate,
    String? tipDate,
    double? tipAmount,
  }) async {
    try {
      final res = await _apiService.updateProjectSettlement(
        projectId,
        deliveredDate: deliveredDate,
        tipDate: tipDate,
        tipAmount: tipAmount,
      );
      if (res['success'] == true) {
        await fetchProjects();
        await fetchStats();
        return (success: true, message: 'Settlement dates & tips saved');
      }
      return (success: false, message: res['message']?.toString() ?? 'Failed to update settlement');
    } catch (e) {
      return (success: false, message: e.toString());
    }
  }

  Future<({bool success, String message})> updateSplits(
    int projectId,
    Map<int, double> splits,
  ) async {
    try {
      final res = await _apiService.updateProjectSplits(projectId, splits);
      if (res['success'] == true) {
        await fetchProjects();
        return (success: true, message: 'Value split updated');
      }
      return (success: false, message: res['message']?.toString() ?? 'Failed to update split');
    } catch (e) {
      return (success: false, message: e.toString());
    }
  }
}
