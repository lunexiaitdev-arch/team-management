import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/leaderboard_provider.dart';
import '../../models/leaderboard.dart';
import '../../theme/app_colors.dart';
import '../../widgets/team_badge.dart';
import '../../widgets/filter_pill_group.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/drawer_opener.dart';

class LeaderboardScreen extends StatefulWidget {
  const LeaderboardScreen({super.key});

  @override
  State<LeaderboardScreen> createState() => _LeaderboardScreenState();
}

class _LeaderboardScreenState extends State<LeaderboardScreen> {
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<LeaderboardProvider>(context, listen: false).fetchLeaderboard();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _showPeriodPicker(BuildContext context, LeaderboardProvider prov) {
    final periods = prov.defaultAvailablePeriods;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => SafeArea(
        child: Container(
          constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.75),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Padding(
                padding: EdgeInsets.all(18),
                child: Text(
                  'Select Leaderboard Period',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                ),
              ),
              const Divider(height: 1),
              Expanded(
                child: ListView(
                  children: periods.map((p) {
                    final isSelected = prov.selectedPeriod == p['key'];
                    return ListTile(
                      title: Text(
                        p['label']!,
                        style: TextStyle(
                          fontWeight: isSelected ? FontWeight.w800 : FontWeight.w500,
                          color: isSelected ? AppColors.primaryBlueLight : null,
                        ),
                      ),
                      subtitle: Text(
                        p['type'] == 'all_time'
                            ? 'All Historical Archive + Live'
                            : (p['type'] == 'quarterly' ? 'Quarter Performance' : 'Monthly Performance'),
                        style: const TextStyle(fontSize: 11, color: AppColors.textDim),
                      ),
                      trailing: isSelected
                          ? const Icon(Icons.check_circle_rounded, color: AppColors.primaryBlueLight)
                          : null,
                      onTap: () {
                        Navigator.of(ctx).pop();
                        prov.setSelectedPeriod(p['key']!, p['type'] ?? 'monthly');
                      },
                    );
                  }).toList(),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showMethodology(BuildContext context, String methodology) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(22),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Leaderboard Scoring Methodology',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 12),
              Text(
                methodology.isNotEmpty
                    ? methodology
                    : 'Leaderboard rankings prioritize realized Net Revenue (80% formula), count of successful deliveries accepted by clients, client tips, and on-time turnaround rate.',
                style: const TextStyle(fontSize: 13, height: 1.4, color: AppColors.textDim),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => Navigator.of(ctx).pop(),
                  child: const Text('Close'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final prov = Provider.of<LeaderboardProvider>(context);
    final data = prov.leaderboardData;
    final rankings = prov.filteredRankings;

    final teamPills = [
      FilterPillItem(keyName: 'all', label: 'All Teams'),
      FilterPillItem(keyName: 'WpTitans', label: 'WpTitans'),
      FilterPillItem(keyName: 'WpNexus', label: 'WpNexus'),
      FilterPillItem(keyName: 'WpCraft', label: 'WpCraft'),
    ];

    final sortPills = [
      FilterPillItem(keyName: 'revenue', label: '💰 Top Delivery (\$)'),
      FilterPillItem(keyName: 'score', label: '🏆 Score'),
      FilterPillItem(keyName: 'completed', label: '📦 Deliveries'),
      FilterPillItem(keyName: 'rating', label: '⭐ Rating'),
      FilterPillItem(keyName: 'ontime', label: '⏱️ On-Time %'),
    ];

    String displayPeriod = data?.periodLabel ?? prov.selectedPeriod;

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
        title: const Text('Specialist Leaderboard'),
        actions: [
          TextButton.icon(
            onPressed: () => _showPeriodPicker(context, prov),
            icon: const Icon(Icons.calendar_month_rounded, size: 16),
            label: Text(
              displayPeriod,
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          IconButton(
            icon: const Icon(Icons.info_outline_rounded),
            onPressed: () => _showMethodology(context, data?.methodology ?? ''),
            tooltip: 'Scoring Rules',
          ),
        ],
      ),
      body: Column(
        children: [
          // Search Specialists Bar
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: TextField(
              controller: _searchController,
              onChanged: (val) => prov.setSearchQuery(val),
              style: TextStyle(
                color: isDark ? AppColors.textLight : AppColors.textDark,
                fontSize: 14,
              ),
              decoration: InputDecoration(
                hintText: 'Search by Specialist name, Call Sign, or Badge ID...',
                prefixIcon: const Icon(Icons.search_rounded, size: 20),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear_rounded, size: 18),
                        onPressed: () {
                          _searchController.clear();
                          prov.setSearchQuery('');
                        },
                      )
                    : null,
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              ),
            ),
          ),

          // Single Streamlined Filter Row (Team Selector + Sort Pills)
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Row(
              children: [
                // Team Selector Popup
                PopupMenuButton<String>(
                  initialValue: prov.selectedTeam,
                  onSelected: (t) => prov.setSelectedTeam(t),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: prov.selectedTeam != 'all'
                          ? AppColors.primaryBlue
                          : (isDark ? AppColors.darkCard : AppColors.lightCard),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: prov.selectedTeam != 'all'
                            ? AppColors.primaryBlue
                            : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
                      ),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.groups_rounded,
                          size: 14,
                          color: prov.selectedTeam != 'all' ? Colors.white : AppColors.primaryBlueLight,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          prov.selectedTeam == 'all' ? 'All Teams' : prov.selectedTeam,
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: prov.selectedTeam != 'all' ? Colors.white : (isDark ? AppColors.textLight : AppColors.textDark),
                          ),
                        ),
                        const SizedBox(width: 2),
                        Icon(
                          Icons.arrow_drop_down_rounded,
                          size: 16,
                          color: prov.selectedTeam != 'all' ? Colors.white : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                        ),
                      ],
                    ),
                  ),
                  itemBuilder: (_) => const [
                    PopupMenuItem(value: 'all', child: Text('All Teams')),
                    PopupMenuItem(value: 'WpTitans', child: Text('WpTitans')),
                    PopupMenuItem(value: 'WpNexus', child: Text('WpNexus')),
                    PopupMenuItem(value: 'WpCraft', child: Text('WpCraft')),
                    PopupMenuItem(value: 'WpHorizon', child: Text('WpHorizon')),
                  ],
                ),
                const SizedBox(width: 8),

                // Sorting Filter Pills (Top Delivery Amount default)
                ...sortPills.map((pill) {
                  final isSelected = prov.selectedSort == pill.keyName;
                  return Padding(
                    padding: const EdgeInsets.only(right: 6),
                    child: FilterChip(
                      label: Text(pill.label),
                      selected: isSelected,
                      selectedColor: AppColors.primaryBlue,
                      checkmarkColor: Colors.white,
                      showCheckmark: false,
                      backgroundColor: isDark ? AppColors.darkCard : AppColors.lightCard,
                      side: BorderSide(
                        color: isSelected
                            ? AppColors.primaryBlue
                            : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
                      ),
                      labelStyle: TextStyle(
                        color: isSelected ? Colors.white : (isDark ? AppColors.textLight : AppColors.textDark),
                        fontSize: 12,
                        fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                      ),
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      visualDensity: VisualDensity.compact,
                      onSelected: (_) => prov.setSort(pill.keyName),
                    ),
                  );
                }),
              ],
            ),
          ),

          const SizedBox(height: 8),

          // Leaderboard Body
          if (prov.isLoading && data != null)
            const LinearProgressIndicator(minHeight: 2),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => prov.fetchLeaderboard(),
              child: prov.isLoading && data == null
                  ? const Center(child: CircularProgressIndicator())
                  : data == null && prov.errorMessage != null
                      ? EmptyStateWidget(
                          icon: Icons.cloud_off_rounded,
                          title: 'Could Not Load Leaderboard',
                          description: prov.errorMessage!,
                          actionLabel: 'Retry',
                          actionIcon: Icons.refresh_rounded,
                          onAction: () => prov.fetchLeaderboard(),
                        )
                      : (data == null || (data.podium.isEmpty && rankings.isEmpty))
                          ? const EmptyStateWidget(
                              icon: Icons.emoji_events_rounded,
                              title: 'No Rankings Available',
                              description: 'No performance records computed for this period and filter selection.',
                            )
                          : ListView(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          children: [
                            // Podium Top 3 Display (only when no active search query)
                            if (prov.searchQuery.isEmpty && data.podium.isNotEmpty) ...[
                              _buildPodiumSection(context, data.podium, isDark),
                              const SizedBox(height: 20),
                            ],

                            // Rankings List Header
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'PERFORMANCE RANKINGS (${rankings.length})',
                                  style: TextStyle(
                                    color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w800,
                                    letterSpacing: 0.8,
                                  ),
                                ),
                                Text(
                                  prov.selectedSort == 'revenue'
                                      ? 'Sorted by Top Delivery'
                                      : 'Sorted by ${prov.selectedSort.toUpperCase()}',
                                  style: TextStyle(
                                    color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                    fontSize: 10,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),

                            ...rankings.map((item) {
                              return _buildRankingTile(context, item, isDark);
                            }),
                          ],
                        ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPodiumSection(BuildContext context, List<LeaderboardItem> podium, bool isDark) {
    final first = podium.isNotEmpty ? podium[0] : null;
    final second = podium.length > 1 ? podium[1] : null;
    final third = podium.length > 2 ? podium[2] : null;

    return Container(
      padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 12),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
          width: 1,
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          // 2nd Place (Silver)
          if (second != null) _buildPodiumColumn(second, 2, 70, AppColors.silverGradient, isDark),

          // 1st Place (Gold - Center & Elevated)
          if (first != null) _buildPodiumColumn(first, 1, 95, AppColors.goldGradient, isDark),

          // 3rd Place (Bronze)
          if (third != null) _buildPodiumColumn(third, 3, 55, AppColors.bronzeGradient, isDark),
        ],
      ),
    );
  }

  Widget _buildPodiumColumn(
    LeaderboardItem item,
    int rank,
    double podiumHeight,
    LinearGradient gradient,
    bool isDark,
  ) {
    final isFirst = rank == 1;

    return Expanded(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (isFirst)
            const Icon(Icons.workspace_premium_rounded, color: Color(0xFFFFD700), size: 28),
          const SizedBox(height: 4),

          // Avatar with rank border
          Container(
            padding: const EdgeInsets.all(3),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: gradient,
              boxShadow: [
                BoxShadow(
                  color: isFirst ? const Color(0x66FFD700) : Colors.black12,
                  blurRadius: isFirst ? 12 : 6,
                  spreadRadius: isFirst ? 2 : 0,
                ),
              ],
            ),
            child: CircleAvatar(
              radius: isFirst ? 26 : 22,
              backgroundColor: isDark ? AppColors.darkSurface : AppColors.lightSurface,
              backgroundImage: item.avatar.isNotEmpty ? NetworkImage(item.avatar) : null,
              child: item.avatar.isEmpty
                  ? Text(
                      item.name.isNotEmpty ? item.name[0].toUpperCase() : 'S',
                      style: TextStyle(
                        fontWeight: FontWeight.w800,
                        color: isDark ? Colors.white : Colors.black,
                        fontSize: isFirst ? 16 : 14,
                      ),
                    )
                  : null,
            ),
          ),
          const SizedBox(height: 8),

          // Name
          Text(
            item.name,
            style: TextStyle(
              color: isDark ? AppColors.textLight : AppColors.textDark,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),

          // Team Badge
          if (item.team.isNotEmpty) TeamBadge(team: item.team, isSmall: true),

          const SizedBox(height: 4),

          // Net Value
          Text(
            '\$${item.netRevenue.toStringAsFixed(0)}',
            style: TextStyle(
              color: isFirst ? const Color(0xFFFFD700) : AppColors.success,
              fontSize: 13,
              fontWeight: FontWeight.w800,
            ),
          ),
          Text(
            '${item.completedOrders} orders',
            style: TextStyle(
              color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
              fontSize: 10,
            ),
          ),
          const SizedBox(height: 6),

          // Podium Pedestal
          Container(
            height: podiumHeight,
            width: double.infinity,
            margin: const EdgeInsets.symmetric(horizontal: 4),
            decoration: BoxDecoration(
              gradient: gradient,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(10)),
            ),
            child: Center(
              child: Text(
                '#$rank',
                style: const TextStyle(
                  color: Colors.black87,
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRankingTile(BuildContext context, LeaderboardItem item, bool isDark) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
          width: 1,
        ),
      ),
      child: Row(
        children: [
          // Rank Badge
          Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(
              color: item.rank == 1
                  ? const Color(0xFFFFD700)
                  : (item.rank == 2
                      ? const Color(0xFFC0C0C0)
                      : (item.rank == 3
                          ? const Color(0xFFCD7F32)
                          : (isDark ? AppColors.darkSurface : AppColors.lightSurface))),
              shape: BoxShape.circle,
              border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
            ),
            child: Center(
              child: Text(
                item.rankLabel,
                style: TextStyle(
                  color: item.rank <= 3 ? Colors.black : (isDark ? AppColors.textLight : AppColors.textDark),
                  fontSize: 11,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),

          // Specialist Avatar
          CircleAvatar(
            radius: 18,
            backgroundColor: AppColors.primaryBlue.withOpacity(0.2),
            backgroundImage: item.avatar.isNotEmpty ? NetworkImage(item.avatar) : null,
            child: item.avatar.isEmpty
                ? Text(
                    item.name.isNotEmpty ? item.name[0].toUpperCase() : 'S',
                    style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight, fontSize: 12),
                  )
                : null,
          ),
          const SizedBox(width: 12),

          // Details: Name, Badge ID, Team, Completed, WIP, Rating
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(
                        item.name,
                        style: TextStyle(
                          color: isDark ? AppColors.textLight : AppColors.textDark,
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      item.customId,
                      style: TextStyle(
                        color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 3),
                Row(
                  children: [
                    if (item.team.isNotEmpty) ...[
                      TeamBadge(team: item.team, isSmall: true),
                      const SizedBox(width: 6),
                    ],
                    Text(
                      '${item.completedOrders} deliveries',
                      style: TextStyle(
                        color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                        fontSize: 11,
                      ),
                    ),
                    if (item.clientRating > 0) ...[
                      const SizedBox(width: 6),
                      Text(
                        '• ⭐ ${item.clientRating.toStringAsFixed(1)}',
                        style: const TextStyle(
                          color: Color(0xFFFFD700),
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ],
                ),
                if (item.activeWip > 0) ...[
                  const SizedBox(height: 2),
                  Text(
                    'Active WIP: \$${item.activeWip.toStringAsFixed(0)}',
                    style: TextStyle(
                      color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                      fontSize: 10,
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                ],
              ],
            ),
          ),

          // Delivery Net Revenue + Score Pill
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '\$${item.netRevenue.toStringAsFixed(0)}',
                style: const TextStyle(
                  color: AppColors.success,
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                ),
              ),
              if (item.grossRevenue > item.netRevenue)
                Text(
                  'Gross: \$${item.grossRevenue.toStringAsFixed(0)}',
                  style: TextStyle(
                    color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                    fontSize: 10,
                  ),
                ),
              if (item.tips > 0)
                Text(
                  '+\$${item.tips.toStringAsFixed(0)} tips',
                  style: const TextStyle(
                    color: AppColors.warning,
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              if (item.score > 0) ...[
                const SizedBox(height: 3),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppColors.primaryBlue.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    '${item.score.toStringAsFixed(0)} pts',
                    style: const TextStyle(
                      color: AppColors.primaryBlueLight,
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}
