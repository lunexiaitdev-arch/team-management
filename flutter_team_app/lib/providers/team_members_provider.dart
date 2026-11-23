import 'package:flutter/material.dart';
import '../models/user.dart';
import '../services/api_service.dart';

class TeamMembersProvider extends ChangeNotifier {
  final ApiService _apiService;

  List<Specialist> _members = [];
  bool _isLoading = false;
  String? _errorMessage;
  String _selectedTeam = 'all';
  bool _includeInactive = false;

  TeamMembersProvider(this._apiService);

  List<Specialist> get members => _members;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  String get selectedTeam => _selectedTeam;
  bool get includeInactive => _includeInactive;

  List<Specialist> get filteredMembers {
    if (_selectedTeam == 'all') return _members;
    return _members.where((m) =>
      m.team.toLowerCase().replaceAll(' ', '') == _selectedTeam.toLowerCase().replaceAll(' ', '')
    ).toList();
  }

  void setSelectedTeam(String team) {
    _selectedTeam = team;
    notifyListeners();
  }

  void setIncludeInactive(bool value) {
    _includeInactive = value;
    fetchMembers();
  }

  Future<void> fetchMembers() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await _apiService.getTeamMembers(null, _includeInactive);
      _members = res.map((m) => Specialist.fromJson(Map<String, dynamic>.from(m))).toList();
    } catch (e) {
      _errorMessage = 'Failed to load team roster: $e';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<String?> createMember(Map<String, dynamic> data) async {
    try {
      await _apiService.createTeamMember(data);
      await fetchMembers();
      return null;
    } catch (e) {
      return e.toString().replaceFirst('Exception: ', '');
    }
  }

  Future<String?> updateMember(int userId, Map<String, dynamic> data) async {
    try {
      await _apiService.updateTeamMember(userId, data);
      await fetchMembers();
      return null;
    } catch (e) {
      return e.toString().replaceFirst('Exception: ', '');
    }
  }

  Future<Map<String, dynamic>?> getMemberProfile(int id) async {
    try {
      final res = await _apiService.getMemberProfile(id);
      return res;
    } catch (_) {
      return null;
    }
  }

  Future<bool> addMilestone(int userId, String title, String date, String description) async {
    try {
      final res = await _apiService.addMemberMilestone(userId, {
        'title': title,
        'milestone_date': date,
        'description': description,
      });
      return res['success'] == true;
    } catch (_) {
      return false;
    }
  }
}
