import 'package:flutter/foundation.dart';
import '../models/task.dart';
import '../services/api_service.dart';

class TasksProvider with ChangeNotifier {
  final ApiService _apiService;

  TasksProvider(this._apiService);

  List<ProjectTask> _tasks = [];
  Map<String, int> _teamCounts = {};
  int _totalTasks = 0;
  int _pendingCount = 0;
  int _overdueCount = 0;
  int _completedCount = 0;
  int _myPendingCount = 0;
  int _myTotalCount = 0;
  int _allVisibleCount = 0;
  String _roleScope = 'member';
  String _userTeam = '';

  String _scope = 'all';
  String _teamFilter = 'all';
  String _statusFilter = 'all';
  String _priorityFilter = 'all';
  String _searchQuery = '';
  bool _isLoading = false;
  String? _errorMessage;

  List<ProjectTask> get tasks => _tasks;
  Map<String, int> get teamCounts => _teamCounts;
  int get totalTasks => _totalTasks;
  int get pendingCount => _pendingCount;
  int get overdueCount => _overdueCount;
  int get completedCount => _completedCount;
  int get countPending => _pendingCount;
  int get countOverdue => _overdueCount;
  int get countCompleted => _completedCount;
  int get myPendingCount => _myPendingCount;
  int get myTotalCount => _myTotalCount;
  int get allVisibleCount => _allVisibleCount;
  String get roleScope => _roleScope;
  String get userTeam => _userTeam;

  String get scope => _scope;
  String get teamFilter => _teamFilter;
  String get statusFilter => _statusFilter;
  String get priorityFilter => _priorityFilter;
  String get searchQuery => _searchQuery;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  void setScopeAndTeam({required String scope, String team = 'all'}) {
    _scope = scope;
    _teamFilter = team;
    fetchTasks();
  }

  void setStatusFilter(String status) {
    _statusFilter = status;
    fetchTasks();
  }

  void setPriorityFilter(String priority) {
    _priorityFilter = priority;
    fetchTasks();
  }

  void setSearchQuery(String query) {
    _searchQuery = query;
    fetchTasks();
  }

  Future<void> fetchTasks({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();
    }

    try {
      final params = <String, String>{
        'scope': _scope,
        'team': _teamFilter,
        'status': _statusFilter,
        'priority': _priorityFilter,
      };
      if (_searchQuery.trim().isNotEmpty) {
        params['search'] = _searchQuery.trim();
      }

      final res = await _apiService.get('/tasks', params);
      if (res is Map<String, dynamic>) {
        _roleScope = (res['role_scope'] ?? 'member').toString();
        _userTeam = (res['user_team'] ?? '').toString();
        _totalTasks = int.tryParse(res['total_tasks']?.toString() ?? '0') ?? 0;
        _pendingCount = int.tryParse(res['pending_count']?.toString() ?? '0') ?? 0;
        _overdueCount = int.tryParse(res['overdue_count']?.toString() ?? '0') ?? 0;
        _completedCount = int.tryParse(res['completed_count']?.toString() ?? '0') ?? 0;
        _myPendingCount = int.tryParse(res['my_pending_count']?.toString() ?? '0') ?? 0;
        _myTotalCount = int.tryParse(res['my_total_count']?.toString() ?? '0') ?? 0;
        _allVisibleCount = int.tryParse(res['all_visible_count']?.toString() ?? '0') ?? 0;

        final rawCounts = res['team_counts'];
        final parsedCounts = <String, int>{};
        if (rawCounts is Map) {
          rawCounts.forEach((k, v) {
            parsedCounts[k.toString()] = int.tryParse(v.toString()) ?? 0;
          });
        }
        _teamCounts = parsedCounts;

        final rawTasks = res['tasks'] is List ? (res['tasks'] as List) : [];
        _tasks = rawTasks
            .whereType<Map<String, dynamic>>()
            .map(ProjectTask.fromJson)
            .toList();
      }
      _errorMessage = null;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<({bool success, String message})> toggleTaskStatus(int taskId, bool isDone) async {
    try {
      final res = await _apiService.post('/tasks/$taskId/status', {
        'status': isDone ? 'done' : 'pending',
      });
      final msg = (res is Map && res['message'] != null)
          ? res['message'].toString()
          : (isDone ? 'Task completed & logged to order daily report!' : 'Task reopened.');
      await fetchTasks(silent: true);
      return (success: true, message: msg);
    } catch (e) {
      return (success: false, message: e.toString().replaceAll('Exception: ', ''));
    }
  }
}
