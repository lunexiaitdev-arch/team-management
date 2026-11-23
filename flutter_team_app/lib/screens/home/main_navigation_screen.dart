import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/projects_provider.dart';
import '../../providers/time_tracker_provider.dart';
import '../../providers/notices_provider.dart';
import '../../providers/messages_provider.dart';
import '../../providers/tasks_provider.dart';
import '../../providers/monthly_reports_provider.dart';
import '../../providers/leaderboard_provider.dart';
import '../../services/notification_sync_service.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_drawer.dart';
import '../dashboard/dashboard_screen.dart';
import '../projects/projects_screen.dart';
import '../tasks/tasks_screen.dart';
import '../daily_reports/daily_reports_screen.dart';
import '../daily_reports/team_review_screen.dart';
import '../monthly_reports/monthly_reports_screen.dart';
import '../leaderboard/leaderboard_screen.dart';
import '../team_members/team_members_screen.dart';
import '../fiverr_profiles/fiverr_profiles_screen.dart';
import '../notices/notices_screen.dart';
import '../settings/settings_screen.dart';

import '../notifications/notifications_screen.dart';
import '../messages/messages_hub_screen.dart';
import '../attendance/attendance_screen.dart';
import '../time_tracker/time_tracker_screen.dart';
import '../../services/app_update_service.dart';
import '../../widgets/drawer_opener.dart';

class MainNavigationScreen extends StatefulWidget {
  final int initialIndex;
  const MainNavigationScreen({super.key, this.initialIndex = 0});

  @override
  State<MainNavigationScreen> createState() => _MainNavigationScreenState();
}

class _MainNavigationScreenState extends State<MainNavigationScreen> with WidgetsBindingObserver {
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();
  late int _currentIndex;
  late final Set<int> _activatedTabs;
  final List<int> _tabHistory = [];

  void _openDrawer() {
    _scaffoldKey.currentState?.openDrawer();
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _currentIndex = widget.initialIndex;
    _activatedTabs = <int>{widget.initialIndex};

    // Prefetch initial dashboard & shared badge data; remaining tabs lazy-load on first visit.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      Provider.of<ProjectsProvider>(context, listen: false).fetchDashboardData();
      Provider.of<MonthlyReportsProvider>(context, listen: false).fetchMonthlyReport();
      Provider.of<LeaderboardProvider>(context, listen: false).fetchLeaderboard();
      Provider.of<TimeTrackerProvider>(context, listen: false).fetchActiveSession(silent: true);
      Provider.of<NoticesProvider>(context, listen: false).fetchNotices();
      Provider.of<MessagesProvider>(context, listen: false).fetchChannels();
      Provider.of<TasksProvider>(context, listen: false).fetchTasks(silent: true);
      final auth = Provider.of<AuthProvider>(context, listen: false);
      if (auth.isExecutive) {
        // Executive / Chairman accounts do not need day-to-day operational push/alert bar spam
        NotificationSyncService().stopPeriodicSync();
      } else {
        NotificationSyncService().syncNow();
      }

      // Check for in-app GitHub OTA update silently on launch
      _checkUpdateSilently();
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _checkUpdateSilently();
    }
  }

  Future<void> _checkUpdateSilently() async {
    await Future.delayed(const Duration(seconds: 2));
    if (!mounted) return;
    final updateInfo = await AppUpdateService.checkForUpdate();
    if (updateInfo != null && updateInfo.hasUpdate && mounted) {
      AppUpdateService.showUpdateDialog(context, updateInfo);
    }
  }

  void _onNavigationChanged(int index) {
    if (_currentIndex != index) {
      _tabHistory.add(_currentIndex);
    }
    setState(() {
      _currentIndex = index;
      _activatedTabs.add(index);
    });
  }

  Future<bool?> _showExitDialog(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: isDark ? AppColors.darkCard : AppColors.lightSurface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: AppColors.danger.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.exit_to_app_rounded, color: AppColors.danger, size: 22),
            ),
            const SizedBox(width: 12),
            Text(
              'Exit App?',
              style: TextStyle(
                fontSize: 17,
                fontWeight: FontWeight.w800,
                color: isDark ? AppColors.textLight : AppColors.textDark,
              ),
            ),
          ],
        ),
        content: Text(
          'Are you sure you want to close FB International Team App?',
          style: TextStyle(
            fontSize: 13.5,
            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(
              'Cancel',
              style: TextStyle(
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.danger,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            ),
            child: const Text('Exit', style: TextStyle(fontWeight: FontWeight.w800)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final auth = Provider.of<AuthProvider>(context);

    final List<Widget> allScreens = [
      DashboardScreen(onNavigateToTab: _onNavigationChanged, onOpenDrawer: _openDrawer),
      const ProjectsScreen(),
      const DailyReportsScreen(),
      if (auth.canReviewStandups || auth.isExecutive || auth.canViewAllDailyReports)
        const TeamReviewScreen()
      else
        const DailyReportsScreen(),
      const MonthlyReportsScreen(),
      const LeaderboardScreen(),
      const TeamMembersScreen(),
      const FiverrProfilesScreen(),
      const NoticesScreen(),
      const SettingsScreen(),
      const NotificationsScreen(),
      MessagesHubScreen(onOpenDrawer: _openDrawer),
      const AttendanceScreen(),
      const TimeTrackerScreen(),
      const TasksScreen(),
    ];

    final int safeIndex =
        (_currentIndex >= 0 && _currentIndex < allScreens.length) ? _currentIndex : 0;
    _activatedTabs.add(safeIndex);

    final List<Widget> screens = List<Widget>.generate(allScreens.length, (i) {
      if (_activatedTabs.contains(i)) {
        return allScreens[i];
      }
      return SizedBox.shrink(key: ValueKey<String>('lazy-tab-$i'));
    });

    // Map screen indices to bottom navigation bar items (0 to 4)
    final int thirdTabTarget = auth.isExecutive ? 3 : 2;
    int bottomNavIndex = 0;
    if (safeIndex == 0) {
      bottomNavIndex = 0;
    } else if (safeIndex == 1) {
      bottomNavIndex = 1;
    } else if (safeIndex == thirdTabTarget) {
      bottomNavIndex = 2;
    } else if (safeIndex == 4) {
      bottomNavIndex = 3;
    } else {
      bottomNavIndex = 4;
    }

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) async {
        if (didPop) return;
        // 1. If drawer is currently open, close it
        if (_scaffoldKey.currentState?.isDrawerOpen == true) {
          _scaffoldKey.currentState?.closeDrawer();
          return;
        }
        // 2. If tab history has previous tabs, pop back to previous tab
        if (_tabHistory.isNotEmpty) {
          final prev = _tabHistory.removeLast();
          setState(() {
            _currentIndex = prev;
          });
          return;
        }
        // 3. If on a non-root tab with empty history, navigate back to Dashboard
        if (_currentIndex != 0) {
          setState(() {
            _currentIndex = 0;
          });
          return;
        }
        // 4. If already on Dashboard (root), confirm before exiting
        final shouldExit = await _showExitDialog(context);
        if (shouldExit == true) {
          SystemNavigator.pop();
        }
      },
      child: DrawerOpener(
        openDrawer: _openDrawer,
        navigateTo: _onNavigationChanged,
        child: Scaffold(
          key: _scaffoldKey,
          drawer: AppDrawer(
            selectedIndex: safeIndex,
            onItemSelected: _onNavigationChanged,
          ),
          body: IndexedStack(
            index: safeIndex,
            children: screens,
          ),
          bottomNavigationBar: Container(
            decoration: BoxDecoration(
              color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
              border: Border(
                top: BorderSide(
                  color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                  width: 1,
                ),
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.08),
                  blurRadius: 10,
                  offset: const Offset(0, -3),
                ),
              ],
            ),
            child: BottomNavigationBar(
              currentIndex: bottomNavIndex,
              onTap: (index) {
                if (index == 0) _onNavigationChanged(0);
                if (index == 1) _onNavigationChanged(1);
                if (index == 2) _onNavigationChanged(thirdTabTarget);
                if (index == 3) _onNavigationChanged(4);
                if (index == 4) {
                  // Open modal bottom sheet or drawer for additional views
                  _showMoreMenu(context);
                }
              },
              type: BottomNavigationBarType.fixed,
              backgroundColor: Colors.transparent,
              elevation: 0,
              selectedItemColor: AppColors.primaryBlueLight,
              unselectedItemColor: isDark ? AppColors.textDim : AppColors.textDarkMuted,
              selectedLabelStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 11),
              unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w500, fontSize: 11),
              items: [
                const BottomNavigationBarItem(
                  icon: Icon(Icons.grid_view_rounded),
                  activeIcon: Icon(Icons.grid_view_rounded),
                  label: 'Dashboard',
                ),
                const BottomNavigationBarItem(
                  icon: Icon(Icons.layers_rounded),
                  activeIcon: Icon(Icons.layers_rounded),
                  label: 'Orders',
                ),
                BottomNavigationBarItem(
                  icon: Icon(auth.isExecutive ? Icons.fact_check_rounded : Icons.assignment_turned_in_rounded),
                  activeIcon: Icon(auth.isExecutive ? Icons.fact_check_rounded : Icons.assignment_turned_in_rounded),
                  label: auth.isExecutive ? 'Reports' : 'Standup',
                ),
                const BottomNavigationBarItem(
                  icon: Icon(Icons.table_chart_rounded),
                  activeIcon: Icon(Icons.table_chart_rounded),
                  label: 'Revenue',
                ),
                const BottomNavigationBarItem(
                  icon: Icon(Icons.menu_rounded),
                  activeIcon: Icon(Icons.menu_rounded),
                  label: 'More',
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showMoreMenu(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final auth = Provider.of<AuthProvider>(context, listen: false);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 38,
                    height: 4,
                    decoration: BoxDecoration(
                      color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'Operations & Directory',
                  style: TextStyle(
                    color: isDark ? AppColors.textLight : AppColors.textDark,
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 16),
                GridView.count(
                  crossAxisCount: 3,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 12,
                  crossAxisSpacing: 12,
                  childAspectRatio: 0.95,
                  children: [
                    _buildQuickTile(
                      icon: Icons.task_alt_rounded,
                      title: 'Tasks & Milestones',
                      color: AppColors.primaryBlue,
                      isDark: isDark,
                      onTap: () {
                        Navigator.of(ctx).pop();
                        _onNavigationChanged(14);
                      },
                    ),
                    if (auth.canReviewStandups || auth.isExecutive || auth.canViewAllDailyReports)
                      _buildQuickTile(
                        icon: Icons.fact_check_rounded,
                        title: auth.isExecutive ? 'Team Reports' : 'Report Approvals',
                        color: AppColors.cyanAccent,
                        isDark: isDark,
                        onTap: () {
                          Navigator.of(ctx).pop();
                          _onNavigationChanged(3);
                        },
                      ),
                    _buildQuickTile(
                      icon: Icons.timer_rounded,
                      title: 'Time Tracker',
                      color: AppColors.success,
                      isDark: isDark,
                      onTap: () {
                        Navigator.of(ctx).pop();
                        _onNavigationChanged(13);
                      },
                    ),
                    _buildQuickTile(
                      icon: Icons.leaderboard_rounded,
                      title: 'Leaderboard',
                      color: AppColors.purpleAccent,
                      isDark: isDark,
                      onTap: () {
                        Navigator.of(ctx).pop();
                        _onNavigationChanged(5);
                      },
                    ),
                    _buildQuickTile(
                      icon: Icons.group_rounded,
                      title: 'Specialists',
                      color: AppColors.primaryBlueLight,
                      isDark: isDark,
                      onTap: () {
                        Navigator.of(ctx).pop();
                        _onNavigationChanged(6);
                      },
                    ),
                    _buildQuickTile(
                      icon: Icons.storefront_rounded,
                      title: 'Fiverr Accounts',
                      color: AppColors.success,
                      isDark: isDark,
                      onTap: () {
                        Navigator.of(ctx).pop();
                        _onNavigationChanged(7);
                      },
                    ),
                    _buildQuickTile(
                      icon: Icons.how_to_reg_rounded,
                      title: 'Attendance & Leaves',
                      color: AppColors.primaryBlueLight,
                      isDark: isDark,
                      onTap: () {
                        Navigator.of(ctx).pop();
                        _onNavigationChanged(12);
                      },
                    ),
                    _buildQuickTile(
                      icon: Icons.campaign_rounded,
                      title: 'Notices',
                      color: AppColors.warning,
                      isDark: isDark,
                      onTap: () {
                        Navigator.of(ctx).pop();
                        _onNavigationChanged(8);
                      },
                    ),
                    if (!auth.isExecutive)
                      _buildQuickTile(
                        icon: Icons.notifications_rounded,
                        title: 'Alerts & Push',
                        color: AppColors.primaryBlueLight,
                        isDark: isDark,
                        onTap: () {
                          Navigator.of(ctx).pop();
                          _onNavigationChanged(10);
                        },
                      ),
                    _buildQuickTile(
                      icon: Icons.chat_rounded,
                      title: 'Team Chat',
                      color: const Color(0xFF0689E3),
                      isDark: isDark,
                      onTap: () {
                        Navigator.of(ctx).pop();
                        _onNavigationChanged(11);
                      },
                    ),
                    _buildQuickTile(
                      icon: Icons.settings_rounded,
                      title: 'Settings',
                      color: AppColors.textDim,
                      isDark: isDark,
                      onTap: () {
                        Navigator.of(ctx).pop();
                        _onNavigationChanged(9);
                      },
                    ),
                  ],
                ),
                const SizedBox(height: 12),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildQuickTile({
    required IconData icon,
    required String title,
    required Color color,
    required bool isDark,
    required VoidCallback onTap,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Ink(
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkCard : AppColors.lightCard,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
              width: 1,
            ),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: color.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: color, size: 22),
              ),
              const SizedBox(height: 8),
              Text(
                title,
                style: TextStyle(
                  color: isDark ? AppColors.textLight : AppColors.textDark,
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
                textAlign: TextAlign.center,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
