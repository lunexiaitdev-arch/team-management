import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/message.dart';
import '../../providers/messages_provider.dart';
import '../../theme/app_colors.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/status_badge.dart';
import '../../widgets/team_badge.dart';
import '../../widgets/drawer_opener.dart';
import 'chat_conversation_screen.dart';

class MessagesHubScreen extends StatefulWidget {
  final VoidCallback? onOpenDrawer;

  const MessagesHubScreen({super.key, this.onOpenDrawer});

  @override
  State<MessagesHubScreen> createState() => _MessagesHubScreenState();
}

class _MessagesHubScreenState extends State<MessagesHubScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final TextEditingController _searchController = TextEditingController();
  String _dmSearchQuery = '';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<MessagesProvider>(context, listen: false).fetchChannels();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _openChat(ChatChannel channel) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ChatConversationScreen(
          conversationType: channel.type,
          conversationId: channel.id,
          title: channel.title,
          subtitle: channel.subtitle,
          avatar: channel.avatar,
          team: channel.team,
          recipientId: channel.recipientId,
          projectId: channel.projectId,
          orderId: channel.orderId,
        ),
      ),
    ).then((_) {
      // Refresh channels unread count when returning
      Provider.of<MessagesProvider>(context, listen: false).fetchChannels();
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final prov = Provider.of<MessagesProvider>(context);

    int squadsUnread = 0;
    for (final t in prov.teamChannels) {
      squadsUnread += t.unreadCount;
    }
    int directUnread = 0;
    for (final d in prov.directChannels) {
      directUnread += d.unreadCount;
    }
    int projectsUnread = 0;
    for (final p in prov.projectChannels) {
      projectsUnread += p.unreadCount;
    }

    return Scaffold(
      appBar: AppBar(
        leading: Builder(
          builder: (ctx) => IconButton(
            icon: const Icon(Icons.menu_rounded),
            onPressed: () {
              if (widget.onOpenDrawer != null) {
                widget.onOpenDrawer!();
              } else {
                final opener = DrawerOpener.of(ctx);
                if (opener != null) {
                  opener.openDrawer();
                } else {
                  Scaffold.of(ctx).openDrawer();
                }
              }
            },
            tooltip: 'Open Menu',
          ),
        ),
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Team Messages & Hub',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
            ),
            Text(
              'Global, squad, project & direct inboxes',
              style: TextStyle(fontSize: 11, color: AppColors.textDim),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => prov.fetchChannels(),
            tooltip: 'Refresh Channels',
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppColors.primaryBlueLight,
          unselectedLabelColor: isDark ? AppColors.textDim : AppColors.textDarkMuted,
          indicatorColor: AppColors.primaryBlue,
          isScrollable: true,
          tabAlignment: TabAlignment.start,
          tabs: [
            Tab(
              child: Row(
                children: [
                  const Text('All-Hands'),
                  if ((prov.globalChannel?.unreadCount ?? 0) > 0) ...[
                    const SizedBox(width: 4),
                    _buildBadgePill('${prov.globalChannel!.unreadCount}'),
                  ],
                ],
              ),
            ),
            Tab(
              child: Row(
                children: [
                  const Text('Squads'),
                  if (squadsUnread > 0) ...[
                    const SizedBox(width: 4),
                    _buildBadgePill('$squadsUnread'),
                  ],
                ],
              ),
            ),
            Tab(
              child: Row(
                children: [
                  const Text('Direct DMs'),
                  if (directUnread > 0) ...[
                    const SizedBox(width: 4),
                    _buildBadgePill('$directUnread'),
                  ],
                ],
              ),
            ),
            Tab(
              child: Row(
                children: [
                  const Text('Order Chats'),
                  if (projectsUnread > 0) ...[
                    const SizedBox(width: 4),
                    _buildBadgePill('$projectsUnread'),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          if (prov.isLoadingChannels && prov.globalChannel != null)
            const LinearProgressIndicator(minHeight: 2),
          Expanded(
            child: prov.isLoadingChannels && prov.globalChannel == null
                ? const Center(child: CircularProgressIndicator(color: AppColors.primaryBlue))
                : TabBarView(
                    controller: _tabController,
                    children: [
                      _buildGlobalTab(prov.globalChannel, prov, isDark),
                      _buildSquadsTab(prov.teamChannels, isDark),
                      _buildDirectTab(prov.directChannels, isDark),
                      _buildProjectsTab(prov.projectChannels, isDark),
                    ],
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildBadgePill(String count) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
      decoration: BoxDecoration(
        color: AppColors.primaryBlue,
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(
        count,
        style: const TextStyle(fontSize: 10, color: Colors.white, fontWeight: FontWeight.w800),
      ),
    );
  }

  // --- 1. Global Tab ---
  Widget _buildGlobalTab(ChatChannel? global, MessagesProvider prov, bool isDark) {
    if (global == null) {
      return RefreshIndicator(
        onRefresh: () => prov.fetchChannels(),
        child: EmptyStateWidget(
          icon: Icons.public_off_rounded,
          title: 'Global Chat Unavailable',
          description: prov.errorMessage ?? 'Unable to reach the agency all-hands server channel.',
          actionLabel: 'Retry',
          actionIcon: Icons.refresh_rounded,
          onAction: () => prov.fetchChannels(),
        ),
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Brand Header Banner
        Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.primaryBlue.withOpacity(0.3)),
          ),
          child: Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: const BoxDecoration(
                  gradient: AppColors.brandGradient,
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.public_rounded, color: Colors.white, size: 26),
              ),
              const SizedBox(width: 14),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Agency All-Hands Room',
                      style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800),
                    ),
                    SizedBox(height: 2),
                    Text(
                      'Universal chat across all squads, leaders & management',
                      style: TextStyle(color: AppColors.textDim, fontSize: 12),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 16),

        _buildChannelCard(global, isDark),
      ],
    );
  }

  // --- 2. Squads Tab ---
  Widget _buildSquadsTab(List<ChatChannel> squads, bool isDark) {
    if (squads.isEmpty) {
      return const EmptyStateWidget(
        icon: Icons.groups_rounded,
        title: 'No Squad Channels Found',
        description: 'Squad channels are automatically organized by team.',
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: squads.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (context, index) {
        final ch = squads[index];
        return _buildChannelCard(ch, isDark);
      },
    );
  }

  // --- 3. Direct DMs Tab ---
  Widget _buildDirectTab(List<ChatChannel> directList, bool isDark) {
    final filtered = directList.where((d) {
      if (_dmSearchQuery.isEmpty) return true;
      final q = _dmSearchQuery.toLowerCase();
      return d.title.toLowerCase().contains(q) ||
          d.subtitle.toLowerCase().contains(q) ||
          (d.team?.toLowerCase().contains(q) ?? false);
    }).toList();

    return Column(
      children: [
        // Search Box
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 6),
          child: TextField(
            controller: _searchController,
            onChanged: (val) => setState(() => _dmSearchQuery = val),
            decoration: InputDecoration(
              hintText: 'Search members by name or squad...',
              prefixIcon: const Icon(Icons.search_rounded, size: 20),
              suffixIcon: _dmSearchQuery.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear_rounded, size: 18),
                      onPressed: () {
                        _searchController.clear();
                        setState(() => _dmSearchQuery = '');
                      },
                    )
                  : null,
              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            ),
          ),
        ),

        Expanded(
          child: filtered.isEmpty
              ? const EmptyStateWidget(
                  icon: Icons.person_search_rounded,
                  title: 'No Members Found',
                  description: 'No team members match your search criteria.',
                )
              : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: filtered.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final ch = filtered[index];
                    return _buildChannelCard(ch, isDark);
                  },
                ),
        ),
      ],
    );
  }

  // --- 4. Order / Project Chats Tab ---
  Widget _buildProjectsTab(List<ChatChannel> projectChats, bool isDark) {
    if (projectChats.isEmpty) {
      return const EmptyStateWidget(
        icon: Icons.inventory_2_outlined,
        title: 'No Project Discussions Yet',
        description: 'Orders with dedicated team chat will be displayed here.',
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: projectChats.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (context, index) {
        final ch = projectChats[index];
        return _buildChannelCard(ch, isDark);
      },
    );
  }

  // --- Channel Card Item Builder ---
  Widget _buildChannelCard(ChatChannel channel, bool isDark) {
    return Container(
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: channel.unreadCount > 0
              ? AppColors.primaryBlue.withOpacity(0.5)
              : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
          width: channel.unreadCount > 0 ? 1.5 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDark ? 0.2 : 0.04),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => _openChat(channel),
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                // Avatar / Icon
                Stack(
                  children: [
                    CircleAvatar(
                      radius: 22,
                      backgroundColor: channel.type == 'global'
                          ? AppColors.primaryBlue.withOpacity(0.2)
                          : channel.type == 'team'
                              ? const Color(0xFF10B981).withOpacity(0.2)
                              : channel.type == 'project'
                                  ? const Color(0xFFF59E0B).withOpacity(0.2)
                                  : AppColors.primaryBlue.withOpacity(0.2),
                      backgroundImage: (channel.avatar != null && channel.avatar!.isNotEmpty)
                          ? NetworkImage(channel.avatar!)
                          : null,
                      child: (channel.avatar == null || channel.avatar!.isEmpty)
                          ? Icon(
                              channel.type == 'global'
                                  ? Icons.public_rounded
                                  : channel.type == 'team'
                                      ? Icons.groups_rounded
                                      : channel.type == 'project'
                                          ? Icons.assignment_rounded
                                          : Icons.person_rounded,
                              color: channel.type == 'global'
                                  ? AppColors.primaryBlueLight
                                  : channel.type == 'team'
                                      ? const Color(0xFF10B981)
                                      : channel.type == 'project'
                                          ? const Color(0xFFF59E0B)
                                          : AppColors.primaryBlueLight,
                              size: 22,
                            )
                          : null,
                    ),
                    if (channel.unreadCount > 0)
                      Positioned(
                        right: 0,
                        top: 0,
                        child: Container(
                          width: 12,
                          height: 12,
                          decoration: BoxDecoration(
                            color: AppColors.primaryBlue,
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: isDark ? AppColors.darkCard : AppColors.lightCard,
                              width: 2,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),

                const SizedBox(width: 14),

                // Channel Info
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Row(
                              children: [
                                Flexible(
                                  child: Text(
                                    channel.title,
                                    style: TextStyle(
                                      color: isDark ? AppColors.textLight : AppColors.textDark,
                                      fontSize: 14,
                                      fontWeight: channel.unreadCount > 0 ? FontWeight.w800 : FontWeight.w700,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                                if (channel.team != null && channel.team!.isNotEmpty) ...[
                                  const SizedBox(width: 6),
                                  TeamBadge(team: channel.team!, isSmall: true),
                                ],
                                if (channel.status != null) ...[
                                  const SizedBox(width: 6),
                                  StatusBadge(status: channel.status!, isSmall: true),
                                ],
                              ],
                            ),
                          ),
                          if (channel.lastTime.isNotEmpty)
                            Text(
                              channel.lastTime,
                              style: TextStyle(
                                color: channel.unreadCount > 0
                                    ? AppColors.primaryBlueLight
                                    : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                                fontSize: 10,
                                fontWeight: channel.unreadCount > 0 ? FontWeight.w700 : FontWeight.w500,
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 3),
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              channel.lastSender != null
                                  ? '${channel.lastSender}: ${channel.lastMessage}'
                                  : channel.lastMessage,
                              style: TextStyle(
                                color: channel.unreadCount > 0
                                    ? (isDark ? AppColors.textLight : AppColors.textDark)
                                    : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                                fontSize: 12,
                                fontWeight: channel.unreadCount > 0 ? FontWeight.w600 : FontWeight.w400,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          if (channel.unreadCount > 0)
                            Container(
                              margin: const EdgeInsets.only(left: 6),
                              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                              decoration: const BoxDecoration(
                                color: AppColors.primaryBlue,
                                shape: BoxShape.circle,
                              ),
                              child: Text(
                                '${channel.unreadCount}',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                ),
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
        ),
      ),
    );
  }
}
