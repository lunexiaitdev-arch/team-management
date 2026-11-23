import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../models/app_notification.dart';
import '../../models/project.dart';
import '../../providers/notifications_provider.dart';
import '../../providers/projects_provider.dart';
import '../../theme/app_colors.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/drawer_opener.dart';
import '../projects/project_detail_sheet.dart';
import '../daily_reports/log_standup_screen.dart';
import '../messages/chat_conversation_screen.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<NotificationsProvider>(context, listen: false)
          .fetchNotifications(showMobileBarAlerts: false);
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final notifProv = Provider.of<NotificationsProvider>(context);

    return Scaffold(
      appBar: AppBar(
        leading: (ModalRoute.of(context)?.canPop ?? false)
            ? null
            : Builder(
                builder: (ctx) => IconButton(
                  icon: const Icon(Icons.menu_rounded),
                  onPressed: () {
                    final opener = DrawerOpener.of(ctx);
                    if (opener != null) {
                      opener.openDrawer();
                    } else {
                      Scaffold.of(ctx).openDrawer();
                    }
                  },
                  tooltip: 'Open Menu',
                ),
              ),
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Notification Center',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
            ),
            Text(
              'Direct mobile status bar alerts & triggers',
              style: TextStyle(fontSize: 11, color: AppColors.textDim),
            ),
          ],
        ),
        actions: [
          if (notifProv.unreadCount > 0 || notifProv.notifications.any((n) => !n.isRead))
            TextButton.icon(
              onPressed: () async {
                await notifProv.markAllAsRead();
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('All notifications marked as read'),
                      duration: Duration(seconds: 2),
                    ),
                  );
                }
              },
              icon: const Icon(Icons.done_all_rounded, size: 16, color: AppColors.primaryBlueLight),
              label: const Text(
                'Mark All Read',
                style: TextStyle(fontSize: 12, color: AppColors.primaryBlueLight, fontWeight: FontWeight.w700),
              ),
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => notifProv.fetchNotifications(showMobileBarAlerts: true),
            tooltip: 'Refresh & Evaluate Alerts',
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppColors.primaryBlueLight,
          unselectedLabelColor: isDark ? AppColors.textDim : AppColors.textDarkMuted,
          indicatorColor: AppColors.primaryBlue,
          tabs: [
            Tab(text: 'All (${notifProv.notifications.length})'),
            Tab(
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('Urgent'),
                  if (notifProv.urgentNotifications.isNotEmpty) ...[
                    const SizedBox(width: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                      decoration: BoxDecoration(
                        color: AppColors.danger,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        '${notifProv.urgentNotifications.length}',
                        style: const TextStyle(fontSize: 10, color: Colors.white, fontWeight: FontWeight.w800),
                      ),
                    ),
                  ],
                ],
              ),
            ),
            Tab(
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('Unread'),
                  if (notifProv.unreadCount > 0) ...[
                    const SizedBox(width: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                      decoration: BoxDecoration(
                        color: AppColors.primaryBlue,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        '${notifProv.unreadCount}',
                        style: const TextStyle(fontSize: 10, color: Colors.white, fontWeight: FontWeight.w800),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          if (notifProv.isLoading && notifProv.notifications.isNotEmpty)
            const LinearProgressIndicator(minHeight: 2),
          Expanded(
            child: notifProv.isLoading && notifProv.notifications.isEmpty
                ? const Center(child: CircularProgressIndicator(color: AppColors.primaryBlue))
                : TabBarView(
                    controller: _tabController,
                    children: [
                      _buildNotificationList(notifProv.notifications, notifProv, isDark),
                      _buildNotificationList(notifProv.urgentNotifications, notifProv, isDark),
                      _buildNotificationList(notifProv.unreadNotifications, notifProv, isDark),
                    ],
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationList(
    List<AppNotification> items,
    NotificationsProvider notifProv,
    bool isDark,
  ) {
    if (items.isEmpty) {
      return RefreshIndicator(
        onRefresh: () => notifProv.fetchNotifications(showMobileBarAlerts: true),
        child: notifProv.errorMessage != null && notifProv.notifications.isEmpty
            ? EmptyStateWidget(
                icon: Icons.cloud_off_rounded,
                title: 'Could Not Load Notifications',
                description: notifProv.errorMessage!,
                actionLabel: 'Retry',
                actionIcon: Icons.refresh_rounded,
                onAction: () => notifProv.fetchNotifications(showMobileBarAlerts: true),
              )
            : const EmptyStateWidget(
                icon: Icons.notifications_off_rounded,
                title: 'No Notifications Found',
                description: 'You are all caught up! New project assignments, deadline alerts (<3 days), and standup reminders will appear here and in your mobile notification bar.',
              ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => Provider.of<NotificationsProvider>(context, listen: false)
          .fetchNotifications(showMobileBarAlerts: true),
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          final item = items[index];
          return _buildNotificationCard(item, isDark);
        },
      ),
    );
  }

  Widget _buildNotificationCard(AppNotification item, bool isDark) {
    final notifProv = Provider.of<NotificationsProvider>(context, listen: false);
    final projectsProv = Provider.of<ProjectsProvider>(context, listen: false);

    Color accentColor;
    IconData iconData;
    String badgeLabel;

    switch (item.type) {
      case 'deadline_warning_3d':
        accentColor = AppColors.danger;
        iconData = Icons.alarm_rounded;
        badgeLabel = 'CRITICAL DEADLINE (< 3 DAYS)';
        break;
      case 'extension_advisory_3d':
        accentColor = AppColors.warning;
        iconData = Icons.schedule_send_rounded;
        badgeLabel = 'EXTENSION ADVISORY';
        break;
      case 'progress_prompt_2d':
        accentColor = AppColors.cyanAccent;
        iconData = Icons.rate_review_rounded;
        badgeLabel = 'PROGRESS STANDUP PROMPT';
        break;
      case 'new_assignment':
      default:
        accentColor = AppColors.primaryBlue;
        iconData = Icons.assignment_ind_rounded;
        badgeLabel = 'NEW PROJECT ASSIGNMENT';
        break;
    }

    String timeText = '';
    if (item.timestamp > 0) {
      final dt = DateTime.fromMillisecondsSinceEpoch(item.timestamp * 1000);
      final diff = DateTime.now().difference(dt);
      if (diff.inMinutes < 60) {
        timeText = '${diff.inMinutes}m ago';
      } else if (diff.inHours < 24) {
        timeText = '${diff.inHours}h ago';
      } else {
        timeText = DateFormat('MMM d, h:mm a').format(dt);
      }
    }

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () async {
          if (!item.isRead) {
            await notifProv.markAsRead(item.id);
          }

          if (!mounted) return;

          // Navigation action
          if (item.type == 'chat_message') {
            final convType = (item.data['conversation_type'] ?? 'direct').toString();
            final convId = (item.data['conversation_id'] ?? 'global').toString();
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => ChatConversationScreen(
                  conversationType: convType,
                  conversationId: convId,
                  title: item.title,
                  subtitle: item.message,
                  projectId: item.projectId,
                ),
              ),
            );
          } else if (item.projectId != null && item.projectId! > 0) {
            final proj = projectsProv.projects.firstWhere(
              (p) => p.id == item.projectId,
              orElse: () => ProjectItem(
                id: item.projectId!,
                orderId: item.data['order_id'] ?? '#${item.projectId}',
                profileName: item.data['profile'] ?? 'Fiverr Order',
                buyerUsername: item.data['buyer'] ?? 'Client',
                title: 'Order #${item.projectId}',
                orderValue: double.tryParse(item.data['budget']?.toString() ?? '0') ?? 0.0,
                netValue: (double.tryParse(item.data['budget']?.toString() ?? '0') ?? 0.0) * 0.8,
                status: 'wip',
                deliveryDate: '',
                progressPercent: 0,
                team: '',
                createdAt: '',
              ),
            );
            showModalBottomSheet(
              context: context,
              isScrollControlled: true,
              backgroundColor: Colors.transparent,
              builder: (_) => ProjectDetailSheet(project: proj),
            );
          } else if (item.type == 'progress_prompt_2d') {
            Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => const LogStandupScreen()),
            );
          } else {
            showDialog(
              context: context,
              builder: (ctx) => AlertDialog(
                title: Text(item.title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                content: Text(item.message, style: const TextStyle(fontSize: 14)),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.of(ctx).pop(),
                    child: const Text('Close'),
                  ),
                ],
              ),
            );
          }
        },
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: item.isRead
                ? (isDark ? AppColors.darkCard : AppColors.lightCard)
                : (isDark
                    ? AppColors.primaryBlue.withValues(alpha: 0.12)
                    : AppColors.primaryBlue.withValues(alpha: 0.06)),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: item.isRead
                  ? (isDark ? AppColors.darkBorder : AppColors.lightBorder)
                  : accentColor.withValues(alpha: 0.5),
              width: item.isRead ? 1 : 1.5,
            ),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: accentColor.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: Icon(iconData, color: accentColor, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: accentColor.withValues(alpha: 0.18),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            badgeLabel,
                            style: TextStyle(
                              fontSize: 9,
                              fontWeight: FontWeight.w800,
                              color: accentColor,
                              letterSpacing: 0.3,
                            ),
                          ),
                        ),
                        const Spacer(),
                        if (timeText.isNotEmpty)
                          Text(
                            timeText,
                            style: TextStyle(
                              fontSize: 10,
                              color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                            ),
                          ),
                        if (!item.isRead) ...[
                          const SizedBox(width: 8),
                          InkWell(
                            onTap: () async {
                              await notifProv.markAsRead(item.id);
                              if (context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text('Notification marked as read'),
                                    duration: Duration(seconds: 1),
                                  ),
                                );
                              }
                            },
                            borderRadius: BorderRadius.circular(12),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: AppColors.primaryBlue.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: AppColors.primaryBlue.withValues(alpha: 0.35)),
                              ),
                              child: const Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(Icons.check_rounded, size: 12, color: AppColors.primaryBlueLight),
                                  SizedBox(width: 3),
                                  Text(
                                    'Mark Read',
                                    style: TextStyle(
                                      fontSize: 10,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.primaryBlueLight,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      item.title,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: item.isRead ? FontWeight.w600 : FontWeight.w800,
                        color: isDark ? AppColors.textLight : AppColors.textDark,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      item.message,
                      style: TextStyle(
                        fontSize: 12,
                        color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                        height: 1.35,
                      ),
                    ),
                    if (item.projectId != null && item.projectId! > 0) ...[
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(Icons.touch_app_rounded, size: 13, color: accentColor),
                          const SizedBox(width: 4),
                          Text(
                            'Tap to view order details & update',
                            style: TextStyle(
                              fontSize: 11,
                              color: accentColor,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
