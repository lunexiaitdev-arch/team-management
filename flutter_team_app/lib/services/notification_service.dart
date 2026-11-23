import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import '../models/app_notification.dart';
import '../firebase_options.dart';
import 'api_service.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp(
      options: DefaultFirebaseOptions.currentPlatform,
    );
  } catch (_) {}

  debugPrint('Handling background FCM message: ${message.messageId}');
  final notification = message.notification;
  if (notification != null) {
    // Show local notification directly to notification bar when app is inactive/killed
    final flutterLocalNotificationsPlugin = FlutterLocalNotificationsPlugin();
    const androidDetails = AndroidNotificationDetails(
      NotificationService.channelId,
      NotificationService.channelName,
      channelDescription: NotificationService.channelDescription,
      importance: Importance.max,
      priority: Priority.high,
      icon: '@mipmap/ic_launcher',
    );
    await flutterLocalNotificationsPlugin.show(
      id: message.hashCode.abs() % 100000,
      title: notification.title ?? 'FBIBD Team Alert',
      body: notification.body ?? '',
      notificationDetails: const NotificationDetails(android: androidDetails),
      payload: jsonEncode(message.data),
    );
  }
}

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FlutterLocalNotificationsPlugin _notificationsPlugin =
      FlutterLocalNotificationsPlugin();

  bool _isInitialized = false;
  Function(Map<String, dynamic> payload)? onNotificationTapped;
  ApiService? _apiService;

  static const String channelId = 'fbibd_team_alerts_channel';
  static const String channelName = 'FBIBD Team Alerts';
  static const String channelDescription =
      'Direct notification bar alerts for project assignments, 3-day deadline warnings, and progress reminders';

  Future<void> initialize({
    ApiService? apiService,
    Function(Map<String, dynamic> payload)? onSelectNotification,
  }) async {
    if (_isInitialized) return;
    _apiService = apiService;
    onNotificationTapped = onSelectNotification;

    try {
      // 1. Initialize Local Notification Plugin
      const androidSettings =
          AndroidInitializationSettings('@mipmap/ic_launcher');

      const darwinSettings = DarwinInitializationSettings(
        requestAlertPermission: true,
        requestBadgePermission: true,
        requestSoundPermission: true,
      );

      const initSettings = InitializationSettings(
        android: androidSettings,
        iOS: darwinSettings,
        macOS: darwinSettings,
      );

      await _notificationsPlugin.initialize(
        settings: initSettings,
        onDidReceiveNotificationResponse: (NotificationResponse response) {
          if (response.payload != null && response.payload!.isNotEmpty) {
            try {
              final data = jsonDecode(response.payload!);
              if (data is Map<String, dynamic>) {
                onNotificationTapped?.call(data);
              }
            } catch (e) {
              debugPrint('Error parsing notification payload: $e');
            }
          }
        },
      );

      // Create high-importance Android channel for heads-up notifications
      final androidImplementation = _notificationsPlugin
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>();

      if (androidImplementation != null) {
        await androidImplementation.createNotificationChannel(
          const AndroidNotificationChannel(
            channelId,
            channelName,
            description: channelDescription,
            importance: Importance.max,
            playSound: true,
            enableVibration: true,
            showBadge: true,
          ),
        );

        // Request POST_NOTIFICATIONS permission on Android 13+
        await androidImplementation.requestNotificationsPermission();
      }

      // 2. Initialize Firebase Core & Firebase Messaging
      try {
        await Firebase.initializeApp(
          options: DefaultFirebaseOptions.currentPlatform,
        );

        FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

        final messaging = FirebaseMessaging.instance;
        await messaging.requestPermission(
          alert: true,
          badge: true,
          sound: true,
          provisional: false,
        );

        // Get FCM Device Token & register with backend server
        final token = await messaging.getToken();
        if (token != null && token.isNotEmpty) {
          debugPrint('FCM Device Token: $token');
          if (_apiService != null) {
            try {
              await _apiService!.registerPushToken(token, 'android');
              debugPrint('FCM Device Token successfully registered with live WordPress backend');
            } catch (e) {
              debugPrint('Failed to register FCM token with server: $e');
            }
          }
        }

        // Listen for token refreshes
        messaging.onTokenRefresh.listen((newToken) {
          debugPrint('FCM Device Token refreshed: $newToken');
          _apiService?.registerPushToken(newToken, 'android');
        });

        // Listen for incoming foreground messages
        FirebaseMessaging.onMessage.listen((RemoteMessage message) {
          debugPrint('Foreground FCM message received: ${message.notification?.title}');
          final notification = message.notification;
          if (notification != null) {
            showLocalNotification(
              id: message.hashCode.abs() % 100000,
              title: notification.title ?? 'FBIBD Team Alert',
              body: notification.body ?? '',
              payload: message.data,
              isUrgent: message.data['urgent'] == '1' || message.data['type'] == 'deadline_warning_3d',
            );
          }
        });

        // Handle notification click when app is opened from notification
        FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
          debugPrint('Notification opened from background: ${message.data}');
          onNotificationTapped?.call(message.data);
        });

        debugPrint('Firebase Cloud Messaging (FCM) initialized successfully');
      } catch (e) {
        debugPrint('Firebase initialization warning: $e');
      }

      _isInitialized = true;
      debugPrint('NotificationService initialized successfully');
    } catch (e) {
      debugPrint('Failed to initialize NotificationService: $e');
    }
  }

  Future<void> registerFcmTokenWithBackend(ApiService api) async {
    _apiService = api;
    try {
      final token = await FirebaseMessaging.instance.getToken();
      if (token != null && token.isNotEmpty) {
        await api.registerPushToken(token, 'android');
        debugPrint('FCM Token registered with backend: $token');
      }
    } catch (e) {
      debugPrint('Could not register FCM token: $e');
    }
  }

  Future<void> showLocalNotification({
    required int id,
    required String title,
    required String body,
    Map<String, dynamic>? payload,
    bool isUrgent = false,
  }) async {
    try {
      final androidDetails = AndroidNotificationDetails(
        channelId,
        channelName,
        channelDescription: channelDescription,
        importance: isUrgent ? Importance.max : Importance.high,
        priority: isUrgent ? Priority.max : Priority.high,
        ticker: 'FBIBD Team Alert',
        color: const Color(0xFF0689E3),
        icon: '@mipmap/ic_launcher',
        styleInformation: BigTextStyleInformation(
          body,
          contentTitle: title,
          summaryText: isUrgent ? 'URGENT ALERT' : 'FBIBD Team',
        ),
      );

      const darwinDetails = DarwinNotificationDetails(
        presentAlert: true,
        presentBadge: true,
        presentSound: true,
      );

      final platformDetails = NotificationDetails(
        android: androidDetails,
        iOS: darwinDetails,
        macOS: darwinDetails,
      );

      await _notificationsPlugin.show(
        id: id,
        title: title,
        body: body,
        notificationDetails: platformDetails,
        payload: payload != null ? jsonEncode(payload) : null,
      );
    } catch (e) {
      debugPrint('Error displaying notification bar alert: $e');
    }
  }

  /// Show alert formatted directly from an AppNotification model
  Future<void> showFromAppNotification(AppNotification notification) async {
    final int notifId = notification.id.hashCode.abs() % 100000;
    await showLocalNotification(
      id: notifId,
      title: notification.title,
      body: notification.message,
      isUrgent: notification.isUrgent,
      payload: {
        'id': notification.id,
        'type': notification.type,
        'project_id': notification.projectId,
        'data': notification.data,
      },
    );
  }

  Future<void> cancelAll() async {
    await _notificationsPlugin.cancelAll();
  }
}
