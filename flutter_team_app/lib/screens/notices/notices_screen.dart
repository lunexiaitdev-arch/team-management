import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/notices_provider.dart';
import '../../models/notice.dart';
import '../../theme/app_colors.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/drawer_opener.dart';
import 'create_notice_sheet.dart';

class NoticesScreen extends StatefulWidget {
  const NoticesScreen({super.key});

  @override
  State<NoticesScreen> createState() => _NoticesScreenState();
}

class _NoticesScreenState extends State<NoticesScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<NoticesProvider>(context, listen: false).fetchNotices();
    });
  }

  void _confirmDelete(BuildContext context, NoticesProvider prov, AgencyNotice notice) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Notice'),
        content: const Text('Are you sure you want to remove this announcement?'),
        actions: [
          TextButton(onPressed: () => Navigator.of(ctx).pop(), child: const Text('Cancel')),
          ElevatedButton(
            onPressed: () async {
              Navigator.of(ctx).pop();
              final success = await prov.deleteNotice(notice.id);
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(success ? 'Notice deleted' : 'Failed to delete notice'),
                    backgroundColor: success ? AppColors.success : AppColors.danger,
                  ),
                );
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final prov = Provider.of<NoticesProvider>(context);
    final auth = Provider.of<AuthProvider>(context);

    return Scaffold(
      appBar: AppBar(
        leading: Builder(
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
        title: const Text('Agency Broadcasts'),
        actions: [
          if ((auth.isOperationsManager || prov.canManage) && !auth.isViewOnly)
            IconButton(
              icon: const Icon(Icons.add_circle_outline_rounded),
              onPressed: () {
                showModalBottomSheet(
                  context: context,
                  isScrollControlled: true,
                  builder: (_) => const CreateNoticeSheet(),
                );
              },
              tooltip: 'Broadcast Notice',
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => prov.fetchNotices(),
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: Column(
        children: [
          if (prov.isLoading && prov.notices.isNotEmpty)
            const LinearProgressIndicator(minHeight: 2),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => prov.fetchNotices(),
              child: prov.isLoading && prov.notices.isEmpty
                  ? const Center(child: CircularProgressIndicator())
                  : prov.errorMessage != null && prov.notices.isEmpty
                      ? EmptyStateWidget(
                          icon: Icons.cloud_off_rounded,
                          title: 'Could Not Load Notices',
                          description: prov.errorMessage!,
                          actionLabel: 'Retry',
                          actionIcon: Icons.refresh_rounded,
                          onAction: () => prov.fetchNotices(),
                        )
                      : prov.notices.isEmpty
                          ? EmptyStateWidget(
                              icon: Icons.campaign_rounded,
                              title: 'No Broadcast Notices',
                              description: 'No announcements or notices published right now.',
                              actionLabel: ((auth.isOperationsManager || prov.canManage) && !auth.isViewOnly) ? 'Broadcast Notice' : null,
                              onAction: ((auth.isOperationsManager || prov.canManage) && !auth.isViewOnly)
                                  ? () {
                                      showModalBottomSheet(
                                        context: context,
                                        isScrollControlled: true,
                                        builder: (_) => const CreateNoticeSheet(),
                                      );
                                    }
                                  : null,
                            )
                          : ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      // Pinned Notices Section
                      if (prov.pinnedNotices.isNotEmpty) ...[
                        Text(
                          'PINNED ANNOUNCEMENTS',
                          style: TextStyle(
                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.8,
                          ),
                        ),
                        const SizedBox(height: 8),
                        ...prov.pinnedNotices.map((n) => _buildNoticeCard(context, n, prov, auth, isDark, true)),
                        const SizedBox(height: 16),
                      ],

                      // Recent Broadcasts
                      if (prov.unpinnedNotices.isNotEmpty) ...[
                        Text(
                          'RECENT NOTICES',
                          style: TextStyle(
                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.8,
                          ),
                        ),
                        const SizedBox(height: 8),
                        ...prov.unpinnedNotices.map((n) => _buildNoticeCard(context, n, prov, auth, isDark, false)),
                      ],
                    ],
                  ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNoticeCard(
    BuildContext context,
    AgencyNotice notice,
    NoticesProvider prov,
    AuthProvider auth,
    bool isDark,
    bool isPinned,
  ) {
    Color priorityColor = AppColors.info;
    if (notice.priority.toLowerCase() == 'urgent') priorityColor = AppColors.danger;
    if (notice.priority.toLowerCase() == 'high') priorityColor = AppColors.warning;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isPinned
            ? (isDark ? const Color(0xFF261E14) : const Color(0xFFFEF3C7))
            : (isDark ? AppColors.darkCard : AppColors.lightCard),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isPinned
              ? AppColors.warning.withOpacity(0.4)
              : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
          width: 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: priorityColor.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      notice.priority.toUpperCase(),
                      style: TextStyle(
                        color: priorityColor,
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  if (isPinned) ...[
                    const SizedBox(width: 8),
                    const Row(
                      children: [
                        Icon(Icons.push_pin_rounded, size: 14, color: AppColors.warning),
                        SizedBox(width: 3),
                        Text(
                          'Pinned',
                          style: TextStyle(color: AppColors.warning, fontSize: 11, fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
              if ((auth.isOperationsManager || prov.canManage) && !auth.isViewOnly)
                Row(
                  children: [
                    IconButton(
                      icon: Icon(
                        isPinned ? Icons.push_pin_rounded : Icons.push_pin_outlined,
                        size: 18,
                        color: isPinned ? AppColors.warning : AppColors.textDim,
                      ),
                      onPressed: () => prov.togglePin(notice.id),
                      constraints: const BoxConstraints(),
                      padding: const EdgeInsets.all(6),
                    ),
                    IconButton(
                      icon: const Icon(Icons.delete_outline_rounded, size: 18, color: AppColors.danger),
                      onPressed: () => _confirmDelete(context, prov, notice),
                      constraints: const BoxConstraints(),
                      padding: const EdgeInsets.all(6),
                    ),
                  ],
                )
              else
                Text(
                  notice.createdAt,
                  style: const TextStyle(color: AppColors.textDim, fontSize: 11),
                ),
            ],
          ),
          const SizedBox(height: 10),

          // Title
          Text(
            notice.title,
            style: TextStyle(
              color: isDark ? AppColors.textLight : AppColors.textDark,
              fontSize: 15,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 6),

          // Content
          Text(
            notice.content,
            style: TextStyle(
              color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
              fontSize: 13,
              height: 1.4,
            ),
          ),
          const SizedBox(height: 12),

          // Author & Date Footer
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 10,
                    backgroundColor: AppColors.primaryBlue.withOpacity(0.2),
                    child: Text(
                      notice.authorName.isNotEmpty ? notice.authorName[0].toUpperCase() : 'M',
                      style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight),
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    notice.authorName,
                    style: TextStyle(
                      color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
              Text(
                notice.createdAt,
                style: const TextStyle(color: AppColors.textDim, fontSize: 10),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
