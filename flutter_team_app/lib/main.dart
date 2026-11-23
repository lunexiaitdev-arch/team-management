import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import 'services/storage_service.dart';
import 'services/api_service.dart';
import 'services/notification_service.dart';
import 'services/notification_sync_service.dart';

import 'providers/auth_provider.dart';
import 'providers/theme_provider.dart';
import 'providers/projects_provider.dart';
import 'providers/daily_reports_provider.dart';
import 'providers/monthly_reports_provider.dart';
import 'providers/leaderboard_provider.dart';
import 'providers/team_members_provider.dart';
import 'providers/notices_provider.dart';
import 'providers/profiles_provider.dart';
import 'providers/notifications_provider.dart';
import 'providers/messages_provider.dart';
import 'providers/attendance_provider.dart';
import 'providers/time_tracker_provider.dart';
import 'providers/tasks_provider.dart';

import 'theme/app_theme.dart';
import 'screens/splash_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Set system UI overlay style
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
      systemNavigationBarColor: Colors.transparent,
    ),
  );

  final storageService = await StorageService.init();
  final apiService = ApiService(storageService);

  // Initialize native mobile notification bar service
  final notificationService = NotificationService();
  await notificationService.initialize(apiService: apiService);

  final notificationsProvider = NotificationsProvider(apiService, storageService);

  // Initialize background notification sync service
  final notificationSyncService = NotificationSyncService();
  notificationSyncService.initialize(notificationsProvider);
  notificationSyncService.startPeriodicSync();

  runApp(
    MultiProvider(
      providers: [
        Provider<StorageService>.value(value: storageService),
        Provider<ApiService>.value(value: apiService),
        ChangeNotifierProvider<ThemeProvider>(
          create: (_) => ThemeProvider(storageService),
        ),
        ChangeNotifierProvider<AuthProvider>(
          create: (_) => AuthProvider(apiService, storageService),
        ),
        ChangeNotifierProvider<NotificationsProvider>.value(
          value: notificationsProvider,
        ),
        ChangeNotifierProvider<ProjectsProvider>(
          create: (_) => ProjectsProvider(apiService),
        ),
        ChangeNotifierProvider<DailyReportsProvider>(
          create: (_) => DailyReportsProvider(apiService),
        ),
        ChangeNotifierProvider<MonthlyReportsProvider>(
          create: (_) => MonthlyReportsProvider(apiService),
        ),
        ChangeNotifierProvider<LeaderboardProvider>(
          create: (_) => LeaderboardProvider(apiService),
        ),
        ChangeNotifierProvider<TeamMembersProvider>(
          create: (_) => TeamMembersProvider(apiService),
        ),
        ChangeNotifierProvider<NoticesProvider>(
          create: (_) => NoticesProvider(apiService),
        ),
        ChangeNotifierProvider<ProfilesProvider>(
          create: (_) => ProfilesProvider(apiService),
        ),
        ChangeNotifierProvider<MessagesProvider>(
          create: (_) => MessagesProvider(apiService),
        ),
        ChangeNotifierProvider<AttendanceProvider>(
          create: (_) => AttendanceProvider(apiService),
        ),
        ChangeNotifierProvider<TimeTrackerProvider>(
          create: (_) => TimeTrackerProvider(apiService),
        ),
        ChangeNotifierProvider<TasksProvider>(
          create: (_) => TasksProvider(apiService),
        ),
      ],
      child: const FBTeamApp(),
    ),
  );
}

class FBTeamApp extends StatelessWidget {
  const FBTeamApp({super.key});

  @override
  Widget build(BuildContext context) {
    final themeProv = Provider.of<ThemeProvider>(context);

    return MaterialApp(
      title: 'FBIBD Team',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: themeProv.themeMode,
      home: const SplashScreen(),
    );
  }
}
