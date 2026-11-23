import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/messages_provider.dart';
import '../../providers/notifications_provider.dart';
import '../../theme/app_colors.dart';
import '../../widgets/animated_logo.dart';
import '../../widgets/app_drawer.dart';
import '../../widgets/team_badge.dart';
import '../messages/messages_hub_screen.dart';
import '../notifications/notifications_screen.dart';
import 'chairman_overview_body.dart';

class DashboardScreen extends StatelessWidget {
  final ValueChanged<int>? onNavigateToTab;
  final VoidCallback? onOpenDrawer;

  const DashboardScreen({super.key, this.onNavigateToTab, this.onOpenDrawer});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final auth = Provider.of<AuthProvider>(context);
    final notifProv = Provider.of<NotificationsProvider>(context);
    final msgProv = Provider.of<MessagesProvider>(context);
    final user = auth.currentUser;

    return Scaffold(
      drawer: AppDrawer(
        selectedIndex: 0,
        onItemSelected: (idx) {
          onNavigateToTab?.call(idx);
        },
      ),
      appBar: AppBar(
        titleSpacing: 0,
        elevation: 0,
        leading: Builder(
          builder: (ctx) => Center(
            child: Container(
              margin: const EdgeInsets.only(left: 10),
              decoration: BoxDecoration(
                color: isDark ? AppColors.darkCard : AppColors.lightSurface,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                  width: 1,
                ),
              ),
              child: IconButton(
                visualDensity: VisualDensity.compact,
                padding: const EdgeInsets.all(6),
                constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                icon: Icon(
                  Icons.menu_rounded,
                  size: 20,
                  color: isDark ? AppColors.textLight : AppColors.textDark,
                ),
                onPressed: () {
                  if (onOpenDrawer != null) {
                    onOpenDrawer!();
                  } else {
                    Scaffold.of(ctx).openDrawer();
                  }
                },
                tooltip: 'Open Menu',
              ),
            ),
          ),
        ),
        title: Padding(
          padding: const EdgeInsets.only(left: 8),
          child: Row(
            children: [
              const AnimatedLogo(size: 32, showLiveIndicator: false),
              const SizedBox(width: 9),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            'FB International',
                            style: TextStyle(
                              color: isDark ? AppColors.textLight : AppColors.textDark,
                              fontSize: 14.5,
                              fontWeight: FontWeight.w800,
                              letterSpacing: -0.3,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 5),
                        Container(
                          width: 6.5,
                          height: 6.5,
                          decoration: const BoxDecoration(
                            color: AppColors.success,
                            shape: BoxShape.circle,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 1.5),
                    Row(
                      children: [
                        if (auth.isExecutive) ...[
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                            decoration: BoxDecoration(
                              color: const Color(0xFF8B5CF6).withValues(alpha: 0.18),
                              borderRadius: BorderRadius.circular(4),
                              border: Border.all(
                                color: const Color(0xFF8B5CF6).withValues(alpha: 0.35),
                                width: 0.8,
                              ),
                            ),
                            child: Text(
                              (user?.professionalTitle.isNotEmpty ?? false)
                                  ? user!.professionalTitle.toUpperCase()
                                  : 'MANAGEMENT',
                              style: const TextStyle(
                                color: Color(0xFFA78BFA),
                                fontSize: 8.5,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 0.4,
                              ),
                            ),
                          ),
                          const SizedBox(width: 4),
                        ] else if (user?.team.isNotEmpty ?? false) ...[
                          TeamBadge(team: user!.team, isSmall: true),
                          const SizedBox(width: 4),
                        ],
                        Flexible(
                          child: Text(
                            (user?.name != null && user!.name.isNotEmpty)
                                ? '${user.callSign.isNotEmpty ? user.callSign : user.name} (${user.badgeId})'
                                : 'Operations Hub',
                            style: TextStyle(
                              color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        actions: [
          // Team Chat Button
          Stack(
            alignment: Alignment.center,
            children: [
              Container(
                margin: const EdgeInsets.symmetric(horizontal: 3),
                decoration: BoxDecoration(
                  color: isDark ? AppColors.darkCard : AppColors.lightSurface,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(
                    color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                    width: 1,
                  ),
                ),
                child: IconButton(
                  visualDensity: VisualDensity.compact,
                  padding: const EdgeInsets.all(6),
                  constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                  icon: Icon(
                    Icons.chat_bubble_outline_rounded,
                    size: 19,
                    color: isDark ? AppColors.textLight : AppColors.textDark,
                  ),
                  onPressed: () {
                    if (onNavigateToTab != null) {
                      onNavigateToTab!(11);
                    } else {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => const MessagesHubScreen()),
                      );
                    }
                  },
                  tooltip: 'Team Chat & Inboxes',
                ),
              ),
              if (msgProv.totalUnread > 0)
                Positioned(
                  top: 2,
                  right: 3,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1.5),
                    decoration: BoxDecoration(
                      color: AppColors.primaryBlue,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    constraints: const BoxConstraints(minWidth: 15, minHeight: 15),
                    child: Text(
                      '${msgProv.totalUnread > 99 ? '99+' : msgProv.totalUnread}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 8.5,
                        fontWeight: FontWeight.w900,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ),
            ],
          ),
          // Notifications Bell Button
          if (!auth.isExecutive) ...[
            Stack(
              alignment: Alignment.center,
              children: [
                Container(
                  margin: const EdgeInsets.symmetric(horizontal: 3),
                  decoration: BoxDecoration(
                    color: isDark ? AppColors.darkCard : AppColors.lightSurface,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                      width: 1,
                    ),
                  ),
                  child: IconButton(
                    visualDensity: VisualDensity.compact,
                    padding: const EdgeInsets.all(6),
                    constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                    icon: Icon(
                      Icons.notifications_none_rounded,
                      size: 20,
                      color: isDark ? AppColors.textLight : AppColors.textDark,
                    ),
                    onPressed: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => const NotificationsScreen()),
                      );
                    },
                    tooltip: 'Alerts & Push Notifications',
                  ),
                ),
                if (notifProv.unreadCount > 0)
                  Positioned(
                    top: 2,
                    right: 3,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1.5),
                      decoration: BoxDecoration(
                        color: AppColors.danger,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      constraints: const BoxConstraints(minWidth: 15, minHeight: 15),
                      child: Text(
                        '${notifProv.unreadCount > 99 ? '99+' : notifProv.unreadCount}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 8.5,
                          fontWeight: FontWeight.w900,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ),
              ],
            ),
          ],
          const SizedBox(width: 8),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1.0),
          child: Container(
            color: (isDark ? AppColors.darkBorder : AppColors.lightBorder).withValues(alpha: 0.6),
            height: 1.0,
          ),
        ),
      ),
      body: ChairmanOverviewBody(onNavigateToTab: onNavigateToTab),
    );
  }
}
