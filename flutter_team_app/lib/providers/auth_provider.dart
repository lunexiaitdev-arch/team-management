import 'package:flutter/material.dart';
import '../models/user.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../services/notification_service.dart';

class AuthProvider extends ChangeNotifier {
  final ApiService _apiService;
  final StorageService _storageService;

  AuthUser? _currentUser;
  bool _isLoading = false;
  String? _errorMessage;

  AuthProvider(this._apiService, this._storageService) {
    _currentUser = _storageService.getUser();
  }

  AuthUser? get currentUser => _currentUser;
  int? get userId => _currentUser?.id;
  bool get isAuthenticated => _currentUser != null && _storageService.getToken() != null;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  bool get isAdmin => _currentUser?.isAdmin ?? false;
  bool get isLeader => _currentUser?.isLeader ?? false;
  bool get isExecutive => _currentUser?.isExecutive ?? false;
  bool get isDeputy => _currentUser?.isDeputy ?? false;
  bool get isOperationsManager => _currentUser?.isOperationsManager ?? false;
  bool get isSquadLeader => _currentUser?.isSquadLeader ?? false;
  bool get isSpecialist => _currentUser?.isSpecialist ?? false;
  String get userTeam => _currentUser?.team ?? '';

  // Permission Accessors
  bool get canViewAllProjects => _currentUser?.canViewAllProjects ?? false;
  bool get canViewAllTeams => _currentUser?.canViewAllTeams ?? false;
  bool get canViewAllDailyReports => _currentUser?.canViewAllDailyReports ?? false;
  bool get canViewAllMonthlyReports => _currentUser?.canViewAllMonthlyReports ?? false;
  bool get canViewAllMembers => _currentUser?.canViewAllMembers ?? false;
  bool get canAddProjects => _currentUser?.canAddProjects ?? false;
  bool get canEditProjects => _currentUser?.canEditProjects ?? false;
  bool get canDeleteProjects => _currentUser?.canDeleteProjects ?? false;
  bool get canReviewStandups => _currentUser?.canReviewStandups ?? false;
  bool get canLogStandup => _currentUser?.canLogStandup ?? false;
  bool get isViewOnly => _currentUser?.isViewOnly ?? false;

  Future<bool> checkAuthSession() async {
    final token = _storageService.getToken();
    if (token == null || token.isEmpty) {
      _currentUser = null;
      notifyListeners();
      return false;
    }

    try {
      final res = await _apiService.getMe();
      if (res['success'] == true && res['user'] != null) {
        _currentUser = AuthUser.fromJson(Map<String, dynamic>.from(res['user']));
        await _storageService.saveUser(_currentUser!);
        notifyListeners();
        return true;
      }
    } on ApiException catch (e) {
      if (e.statusCode == 401 || e.statusCode == 403) {
        await _storageService.clearSession();
        _currentUser = null;
        notifyListeners();
        return false;
      }
      if (_currentUser != null) {
        return true;
      }
    } catch (_) {
      // If network temporarily fails, try using cached user if available
      if (_currentUser != null) {
        return true;
      }
    }

    return false;
  }

  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await _apiService.login(username, password);
      if (res['success'] == true && res['user'] != null) {
        final token = res['token']?.toString() ?? '';
        if (token.isNotEmpty) {
          await _storageService.saveToken(token);
        }

        _currentUser = AuthUser.fromJson(Map<String, dynamic>.from(res['user']));
        await _storageService.saveUser(_currentUser!);

        // Register FCM push token for this user
        NotificationService().registerFcmTokenWithBackend(_apiService);

        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = res['message']?.toString() ?? 'Invalid credentials';
      }
    } on ApiException catch (e) {
      _errorMessage = e.message;
    } catch (e) {
      _errorMessage = 'Connection failed: ${e.toString()}';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();

    try {
      await _apiService.logout();
    } catch (_) {}

    await _storageService.clearSession();
    _currentUser = null;
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> updateUserProfile(Map<String, dynamic> data) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await _apiService.updateUserProfile(data);
      if (res['success'] == true) {
        // Refresh profile
        await checkAuthSession();
        _isLoading = false;
        notifyListeners();
        return true;
      }
      _errorMessage = res['message']?.toString() ?? 'Failed to update profile';
    } on ApiException catch (e) {
      _errorMessage = e.message;
    } catch (e) {
      _errorMessage = e.toString();
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<bool> changePassword(String currentPassword, String newPassword) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await _apiService.updateUserPassword(currentPassword, newPassword);
      if (res['success'] == true) {
        _isLoading = false;
        notifyListeners();
        return true;
      }
      _errorMessage = res['message']?.toString() ?? 'Failed to change password';
    } on ApiException catch (e) {
      _errorMessage = e.message;
    } catch (e) {
      _errorMessage = e.toString();
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }
}
