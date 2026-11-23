import 'package:flutter/material.dart';
import '../models/app_notification.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../services/notification_service.dart';

class NotificationsProvider extends ChangeNotifier {
  final ApiService _api;
  final StorageService _storage;
  final NotificationService _notificationService = NotificationService();

  List<AppNotification> _notifications = [];
  int _unreadCount = 0;
  bool _isLoading = false;
  String? _errorMessage;
  Set<String> _notifiedIds = {};
  Set<String> _readIds = {};

  NotificationsProvider(this._api, this._storage) {
    _notifiedIds = _storage.getNotifiedIds().toSet();
    _readIds = _storage.getReadNotificationIds().toSet();
  }

  List<AppNotification> get notifications => _notifications;
  int get unreadCount => _unreadCount;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  List<AppNotification> get urgentNotifications =>
      _notifications.where((n) => n.isUrgent).toList();

  List<AppNotification> get unreadNotifications =>
      _notifications.where((n) => !n.isRead).toList();

  Future<void> fetchNotifications({
    bool showMobileBarAlerts = true,
    bool silent = false,
  }) async {
    final token = _storage.getToken();
    if (token == null || token.isEmpty) {
      if (_notifications.isNotEmpty || _unreadCount != 0) {
        _notifications = [];
        _unreadCount = 0;
        notifyListeners();
      }
      return;
    }

    if (silent && _isLoading) return;

    if (!silent) {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();
    }

    try {
      // 1. Trigger server evaluation of notification rules:
      try {
        await _api.evaluateNotifications();
      } catch (e) {
        debugPrint('Notification evaluation error: $e');
      }

      // 2. Fetch the user's notifications
      final res = await _api.getNotifications(perPage: 50);
      final rawList = res['notifications'] as List? ?? [];

      _readIds = _storage.getReadNotificationIds().toSet();

      final items = rawList.map((item) {
        final notif = AppNotification.fromJson(Map<String, dynamic>.from(item));
        if (_readIds.contains(notif.id)) {
          return AppNotification(
            id: notif.id,
            userId: notif.userId,
            projectId: notif.projectId,
            type: notif.type,
            title: notif.title,
            message: notif.message,
            data: notif.data,
            isRead: true,
            createdAt: notif.createdAt,
            timestamp: notif.timestamp,
          );
        }
        return notif;
      }).toList();

      _notifications = items;
      _unreadCount = _notifications.where((n) => !n.isRead).length;

      // 3. For any unread notification not yet dispatched to mobile status bar, trigger native alert!
      if (showMobileBarAlerts) {
        bool changedNotified = false;
        for (final item in _notifications) {
          if (!item.isRead && !_notifiedIds.contains(item.id)) {
            await _notificationService.showFromAppNotification(item);
            _notifiedIds.add(item.id);
            changedNotified = true;
          }
        }
        if (changedNotified) {
          await _storage.saveNotifiedIds(_notifiedIds.toList());
        }
      }
    } catch (e) {
      if (!silent) {
        _errorMessage = e.toString();
      }
      debugPrint('Failed to fetch notifications: $e');
    } finally {
      if (!silent) {
        _isLoading = false;
      }
      notifyListeners();
    }
  }

  Future<void> markAsRead(String id) async {
    try {
      _readIds.add(id);
      await _storage.saveReadNotificationIds(_readIds.toList());

      final index = _notifications.indexWhere((n) => n.id == id);
      if (index != -1 && !_notifications[index].isRead) {
        final updated = AppNotification(
          id: _notifications[index].id,
          userId: _notifications[index].userId,
          projectId: _notifications[index].projectId,
          type: _notifications[index].type,
          title: _notifications[index].title,
          message: _notifications[index].message,
          data: _notifications[index].data,
          isRead: true,
          createdAt: _notifications[index].createdAt,
          timestamp: _notifications[index].timestamp,
        );
        _notifications[index] = updated;
        _unreadCount = _notifications.where((n) => !n.isRead).length;
        notifyListeners();
      }

      await _api.markNotificationRead(id);
    } catch (e) {
      debugPrint('Error marking notification as read: $e');
    }
  }

  Future<void> markAllAsRead() async {
    try {
      for (final n in _notifications) {
        _readIds.add(n.id);
      }
      await _storage.saveReadNotificationIds(_readIds.toList());

      _notifications = _notifications.map((n) {
        if (!n.isRead) {
          return AppNotification(
            id: n.id,
            userId: n.userId,
            projectId: n.projectId,
            type: n.type,
            title: n.title,
            message: n.message,
            data: n.data,
            isRead: true,
            createdAt: n.createdAt,
            timestamp: n.timestamp,
          );
        }
        return n;
      }).toList();
      _unreadCount = 0;
      notifyListeners();

      await _api.markAllNotificationsRead();
    } catch (e) {
      debugPrint('Error marking all notifications as read: $e');
    }
  }
}
