import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';

class StorageService {
  static const String _keyToken = 'ftm_auth_token';
  static const String _keyUser = 'ftm_user_json';
  static const String _keyThemeMode = 'ftm_theme_mode';
  static const String _keyBaseUrl = 'ftm_api_base_url';

  static const String defaultBaseUrl = 'https://team.fbinternationalbd.com/wp-json/ftm/v1';

  final SharedPreferences _prefs;

  StorageService(this._prefs);

  static Future<StorageService> init() async {
    final prefs = await SharedPreferences.getInstance();
    return StorageService(prefs);
  }

  // Token Management
  String? getToken() => _prefs.getString(_keyToken);

  Future<void> saveToken(String token) async {
    await _prefs.setString(_keyToken, token);
  }

  Future<void> removeToken() async {
    await _prefs.remove(_keyToken);
  }

  // User Session Management
  AuthUser? getUser() {
    final raw = _prefs.getString(_keyUser);
    if (raw == null) return null;
    try {
      final json = jsonDecode(raw);
      return AuthUser.fromJson(json);
    } catch (_) {
      return null;
    }
  }

  Future<void> saveUser(AuthUser user) async {
    final raw = jsonEncode(user.toJson());
    await _prefs.setString(_keyUser, raw);
  }

  Future<void> removeUser() async {
    await _prefs.remove(_keyUser);
  }

  // Theme Management
  String getThemeMode() => _prefs.getString(_keyThemeMode) ?? 'dark';

  Future<void> saveThemeMode(String mode) async {
    await _prefs.setString(_keyThemeMode, mode);
  }

  // Base URL
  String getBaseUrl() => _prefs.getString(_keyBaseUrl) ?? defaultBaseUrl;

  Future<void> saveBaseUrl(String url) async {
    await _prefs.setString(_keyBaseUrl, url);
  }

  // Push Notifications Tracking
  static const String _keyNotifiedIds = 'ftm_notified_notification_ids';
  static const String _keyReadNotificationIds = 'ftm_read_notification_ids';

  List<String> getNotifiedIds() => _prefs.getStringList(_keyNotifiedIds) ?? [];

  Future<void> saveNotifiedIds(List<String> ids) async {
    // Keep max latest 500 ids to avoid memory bloat
    final truncated = ids.length > 500 ? ids.sublist(ids.length - 500) : ids;
    await _prefs.setStringList(_keyNotifiedIds, truncated);
  }

  List<String> getReadNotificationIds() => _prefs.getStringList(_keyReadNotificationIds) ?? [];

  Future<void> saveReadNotificationIds(List<String> ids) async {
    final truncated = ids.length > 500 ? ids.sublist(ids.length - 500) : ids;
    await _prefs.setStringList(_keyReadNotificationIds, truncated);
  }

  // Clear Session
  Future<void> clearSession() async {
    await removeToken();
    await removeUser();
  }
}
