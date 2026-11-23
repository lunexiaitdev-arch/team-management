import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../providers/messages_provider.dart';
import '../providers/notifications_provider.dart';
import '../providers/tasks_provider.dart';
import '../theme/app_colors.dart';
import 'animated_logo.dart';
import 'team_badge.dart';

class AppDrawer extends StatelessWidget {
  final int selectedIndex;
  final ValueChanged<int> onItemSelected;

  const AppDrawer({
    super.key,
    required this.selectedIndex,
    required this.onItemSelected,
  });

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final user = auth.currentUser;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Drawer(
      backgroundColor: isDark ? AppColors.darkBackground : AppColors.lightBackground,
      child: SafeArea(
        child: Column(
          children: [
            // Drawer Header with Brand and Close Button
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
              decoration: BoxDecoration(
                border: Border(
                  bottom: BorderSide(
                    color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                    width: 1,
                  ),
                ),
              ),
              child: Row(
                children: [
                  const AnimatedLogo(size: 38, showLiveIndicator: true),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'FB International',
                          style: TextStyle(
                            color: isDark ? AppColors.textLight : AppColors.textDark,
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.3,
                          ),
                        ),
                        Text(
                          'Operations Command Hub',
                          style: TextStyle(
                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                            fontSize: 11,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: Icon(
                      Icons.close_rounded,
                      color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                      size: 22,
                    ),
                    constraints: const BoxConstraints(minWidth: 44, minHeight: 44),
                  ),
                ],
              ),
            ),

            // Scrollable Navigation Items
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                children: [
                  _buildSectionHeader('OPERATIONS', isDark),
                  _buildNavItem(
                    context: context,
                    index: 0,
                    icon: Icons.grid_view_rounded,
                    title: 'Dashboard',
                    isDark: isDark,
                  ),
                  _buildNavItem(
                    context: context,
                    index: 1,
                    icon: Icons.layers_rounded,
                    title: 'Order Pipeline',
                    isDark: isDark,
                  ),
                  Consumer<TasksProvider>(
                    builder: (_, tasksProv, __) => _buildNavItem(
                      context: context,
                      index: 14,
                      icon: Icons.task_alt_rounded,
                      title: auth.isExecutive
                          ? 'Tasks & Milestones Monitor'
                          : 'Tasks & Milestones',
                      isDark: isDark,
                      badgeCount: tasksProv.countPending,
                    ),
                  ),
                  if (auth.canLogStandup)
                    _buildNavItem(
                      context: context,
                      index: 2,
                      icon: Icons.assignment_turned_in_rounded,
                      title: 'Daily Standup Submission',
                      isDark: isDark,
                    ),
                  if (auth.canReviewStandups || auth.isExecutive || auth.canViewAllDailyReports)
                    _buildNavItem(
                      context: context,
                      index: 3,
                      icon: Icons.fact_check_rounded,
                      title: auth.isExecutive
                          ? 'Team Standups Audit'
                          : (auth.isOperationsManager
                              ? 'All Team Submissions (Approve)'
                              : 'Team Report Approvals (Verify)'),
                      isDark: isDark,
                    ),
                  _buildNavItem(
                    context: context,
                    index: 12,
                    icon: Icons.how_to_reg_rounded,
                    title: 'Daily Attendance & Leaves',
                    isDark: isDark,
                  ),
                  _buildNavItem(
                    context: context,
                    index: 13,
                    icon: Icons.timer_rounded,
                    title: (auth.isLeader || auth.isAdmin || auth.isExecutive)
                        ? 'Time Tracker & Screenshots'
                        : 'Time Tracker & Records',
                    isDark: isDark,
                  ),
                  const SizedBox(height: 12),
                  _buildSectionHeader('REVENUE & METRICS', isDark),
                  _buildNavItem(
                    context: context,
                    index: 4,
                    icon: Icons.table_chart_rounded,
                    title: 'Monthly Revenue Sheet',
                    isDark: isDark,
                  ),
                  _buildNavItem(
                    context: context,
                    index: 5,
                    icon: Icons.leaderboard_rounded,
                    title: 'Specialist Leaderboard',
                    isDark: isDark,
                  ),
                  const SizedBox(height: 12),
                  _buildSectionHeader('AGENCY DIRECTORY', isDark),
                  _buildNavItem(
                    context: context,
                    index: 6,
                    icon: Icons.group_rounded,
                    title: 'Specialists & Teams',
                    isDark: isDark,
                  ),
                  _buildNavItem(
                    context: context,
                    index: 7,
                    icon: Icons.storefront_rounded,
                    title: 'Fiverr Profiles',
                    isDark: isDark,
                  ),
                  _buildNavItem(
                    context: context,
                    index: 8,
                    icon: Icons.campaign_rounded,
                    title: 'Agency Notices',
                    isDark: isDark,
                  ),
                  _buildSectionHeader('COMMUNICATIONS', isDark),
                  Consumer<MessagesProvider>(
                    builder: (_, msgProv, __) => _buildNavItem(
                      context: context,
                      index: 11,
                      icon: Icons.chat_rounded,
                      title: 'Team Messages & Chat',
                      isDark: isDark,
                      badgeCount: msgProv.totalUnread,
                    ),
                  ),
                  if (!auth.isExecutive)
                    Consumer<NotificationsProvider>(
                      builder: (_, notifProv, __) => _buildNavItem(
                        context: context,
                        index: 10,
                        icon: Icons.notifications_rounded,
                        title: 'Alerts & Notifications',
                        isDark: isDark,
                        badgeCount: notifProv.unreadCount,
                      ),
                    ),
                  const SizedBox(height: 12),
                  _buildSectionHeader('ACCOUNT', isDark),
                  _buildNavItem(
                    context: context,
                    index: 9,
                    icon: Icons.person_rounded,
                    title: 'Settings & Profile',
                    isDark: isDark,
                  ),
                ],
              ),
            ),

            // Pinned Drawer Footer: Mini Profile & Logout
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                border: Border(
                  top: BorderSide(
                    color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                    width: 1,
                  ),
                ),
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 20,
                        backgroundColor: auth.isExecutive
                            ? const Color(0xFF8B5CF6).withOpacity(0.2)
                            : AppColors.primaryBlue.withOpacity(0.2),
                        backgroundImage: (user?.avatar.isNotEmpty ?? false)
                            ? NetworkImage(user!.avatar)
                            : null,
                        child: (user?.avatar.isEmpty ?? true)
                            ? Text(
                                (user?.name.isNotEmpty ?? false) ? user!.name[0].toUpperCase() : 'U',
                                style: TextStyle(
                                  fontWeight: FontWeight.w700,
                                  color: auth.isExecutive ? const Color(0xFFA78BFA) : AppColors.primaryBlueLight,
                                ),
                              )
                            : null,
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              user?.name ?? 'Specialist',
                              style: TextStyle(
                                color: isDark ? AppColors.textLight : AppColors.textDark,
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 2),
                            Row(
                              children: [
                                if (auth.isExecutive) ...[
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFF8B5CF6).withOpacity(0.22),
                                      borderRadius: BorderRadius.circular(4),
                                      border: Border.all(
                                        color: const Color(0xFFA78BFA).withOpacity(0.35),
                                        width: 0.7,
                                      ),
                                    ),
                                    child: Text(
                                      (user?.professionalTitle.isNotEmpty ?? false)
                                          ? '${user!.professionalTitle.toUpperCase()} · VIEW-ONLY'
                                          : ((user?.role == 'management' || user?.role == 'ftm_management')
                                              ? 'MANAGEMENT · VIEW-ONLY'
                                              : 'EXECUTIVE · VIEW-ONLY'),
                                      style: const TextStyle(
                                        color: Color(0xFFA78BFA),
                                        fontSize: 9,
                                        fontWeight: FontWeight.w800,
                                        letterSpacing: 0.3,
                                      ),
                                    ),
                                  ),
                                ] else if (user?.team.isNotEmpty ?? false) ...[
                                  TeamBadge(team: user!.team, isSmall: true),
                                  const SizedBox(width: 6),
                                  Text(
                                    user.badgeId,
                                    style: TextStyle(
                                      color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                      fontSize: 11,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Navigator.of(context).pop();
                        _confirmLogout(context, auth);
                      },
                      icon: const Icon(Icons.logout_rounded, size: 16, color: AppColors.danger),
                      label: const Text(
                        'Log Out',
                        style: TextStyle(color: AppColors.danger, fontWeight: FontWeight.w600, fontSize: 13),
                      ),
                      style: OutlinedButton.styleFrom(
                        side: BorderSide(color: AppColors.danger.withOpacity(0.3)),
                        padding: const EdgeInsets.symmetric(vertical: 10),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title, bool isDark) {
    return Padding(
      padding: const EdgeInsets.only(left: 12, bottom: 6, top: 4),
      child: Text(
        title,
        style: TextStyle(
          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
          fontSize: 10,
          fontWeight: FontWeight.w800,
          letterSpacing: 1.0,
        ),
      ),
    );
  }

  Widget _buildNavItem({
    required BuildContext context,
    required int index,
    required IconData icon,
    required String title,
    required bool isDark,
    int? badgeCount,
  }) {
    final isSelected = selectedIndex == index;

    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            Navigator.of(context).pop();
            onItemSelected(index);
          },
          borderRadius: BorderRadius.circular(12),
          child: Ink(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: isSelected
                  ? AppColors.primaryBlue.withOpacity(0.12)
                  : Colors.transparent,
              borderRadius: BorderRadius.circular(12),
              border: isSelected
                  ? Border.all(color: AppColors.primaryBlue.withOpacity(0.3), width: 1)
                  : null,
            ),
            child: Row(
              children: [
                Icon(
                  icon,
                  size: 20,
                  color: isSelected
                      ? AppColors.primaryBlueLight
                      : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(
                      color: isSelected
                          ? AppColors.primaryBlueLight
                          : (isDark ? AppColors.textLight : AppColors.textDark),
                      fontSize: 13,
                      fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                    ),
                  ),
                ),
                if (badgeCount != null && badgeCount > 0)
                  Container(
                    margin: const EdgeInsets.only(left: 6),
                    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                    decoration: BoxDecoration(
                      color: index == 10 ? AppColors.danger : AppColors.primaryBlue,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      badgeCount > 99 ? '99+' : '$badgeCount',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  )
                else if (isSelected)
                  Container(
                    width: 6,
                    height: 6,
                    decoration: const BoxDecoration(
                      color: AppColors.primaryBlueLight,
                      shape: BoxShape.circle,
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _confirmLogout(BuildContext context, AuthProvider auth) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Log Out'),
        content: const Text('Are you sure you want to end your current session?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.of(ctx).pop();
              auth.logout();
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            child: const Text('Log Out'),
          ),
        ],
      ),
    );
  }
}
