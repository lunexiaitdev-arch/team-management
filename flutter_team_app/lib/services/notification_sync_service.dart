import 'dart:async';
import 'package:flutter/foundation.dart';
import '../providers/notifications_provider.dart';

class NotificationSyncService {
  static final NotificationSyncService _instance = NotificationSyncService._internal();
  factory NotificationSyncService() => _instance;
  NotificationSyncService._internal();

  Timer? _periodicTimer;
  NotificationsProvider? _provider;
  bool _isRunning = false;

  void initialize(NotificationsProvider provider) {
    _provider = provider;
  }

  void startPeriodicSync({Duration interval = const Duration(seconds: 45)}) {
    if (_isRunning) return;
    _isRunning = true;

    // Run first sync immediately
    syncNow();

    // Setup periodic polling
    _periodicTimer?.cancel();
    _periodicTimer = Timer.periodic(interval, (_) {
      syncNow();
    });

    debugPrint('NotificationSyncService: Started periodic background sync every ${interval.inSeconds}s');
  }

  void stopPeriodicSync() {
    _periodicTimer?.cancel();
    _periodicTimer = null;
    _isRunning = false;
    debugPrint('NotificationSyncService: Stopped periodic background sync');
  }

  Future<void> syncNow() async {
    if (_provider == null) return;
    try {
      await _provider!.fetchNotifications(
        showMobileBarAlerts: true,
        silent: true,
      );
    } catch (e) {
      debugPrint('NotificationSyncService: Sync error: $e');
    }
  }
}
