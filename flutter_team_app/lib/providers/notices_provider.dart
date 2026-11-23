import 'package:flutter/material.dart';
import '../models/notice.dart';
import '../services/api_service.dart';

class NoticesProvider extends ChangeNotifier {
  final ApiService _apiService;

  List<AgencyNotice> _notices = [];
  bool _canManage = false;
  bool _isLoading = false;
  String? _errorMessage;

  NoticesProvider(this._apiService);

  List<AgencyNotice> get notices => _notices;
  bool get canManage => _canManage;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  List<AgencyNotice> get pinnedNotices => _notices.where((n) => n.isPinned).toList();
  List<AgencyNotice> get unpinnedNotices => _notices.where((n) => !n.isPinned).toList();

  Future<void> fetchNotices() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await _apiService.getNotices();
      if (res['notices'] is List) {
        _notices = (res['notices'] as List)
            .map((item) => AgencyNotice.fromJson(Map<String, dynamic>.from(item)))
            .toList();
      }
      _canManage = res['can_manage'] == true;
    } catch (e) {
      _errorMessage = 'Failed to load notices: $e';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> createNotice(String title, String content, String priority, bool isPinned) async {
    try {
      final res = await _apiService.createNotice(title, content, priority, isPinned);
      if (res['success'] == true) {
        await fetchNotices();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> togglePin(int noticeId) async {
    try {
      final res = await _apiService.togglePinNotice(noticeId);
      if (res['success'] == true) {
        await fetchNotices();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> deleteNotice(int noticeId) async {
    try {
      final res = await _apiService.deleteNotice(noticeId);
      if (res['success'] == true) {
        await fetchNotices();
        return true;
      }
    } catch (_) {}
    return false;
  }
}
