import 'package:flutter/material.dart';
import '../models/fiverr_profile.dart';
import '../services/api_service.dart';

class ProfilesProvider extends ChangeNotifier {
  final ApiService _apiService;

  List<FiverrProfile> _profiles = [];
  bool _isLoading = false;
  String? _errorMessage;

  ProfilesProvider(this._apiService);

  List<FiverrProfile> get profiles => _profiles;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  Future<void> fetchProfiles() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final res = await _apiService.getFiverrProfiles();
      _profiles = res.map((p) => FiverrProfile.fromJson(Map<String, dynamic>.from(p))).toList();
    } catch (e) {
      _errorMessage = 'Failed to load Fiverr profiles: $e';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> createProfile(String name, String url, String status) async {
    try {
      final res = await _apiService.createFiverrProfile({
        'name': name,
        'url': url,
        'status': status,
      });
      if (res['success'] == true) {
        await fetchProfiles();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> deleteProfile(int id) async {
    try {
      final res = await _apiService.deleteFiverrProfile(id);
      if (res['success'] == true) {
        await fetchProfiles();
        return true;
      }
    } catch (_) {}
    return false;
  }
}
