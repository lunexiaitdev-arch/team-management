import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'dart:ui';
import '../../providers/monthly_reports_provider.dart';
import '../../models/monthly_report.dart';
import '../../theme/app_colors.dart';
import '../../widgets/team_badge.dart';
import '../../widgets/filter_pill_group.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/app_drawer.dart';
import '../../widgets/drawer_opener.dart';
import '../messages/chat_conversation_screen.dart';

class MonthlyReportsScreen extends StatefulWidget {
  const MonthlyReportsScreen({super.key});

  @override
  State<MonthlyReportsScreen> createState() => _MonthlyReportsScreenState();
}

class _MonthlyReportsScreenState extends State<MonthlyReportsScreen> {
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<MonthlyReportsProvider>(context, listen: false).fetchMonthlyReport();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _showMonthPicker(BuildContext context, MonthlyReportsProvider prov) {
    final months = MonthlyReportsProvider.availableReportingMonths;

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
                  'Select Reporting Period',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                ),
              ),
              const Divider(height: 1),
              Expanded(
                child: ListView(
                  children: months.map((m) {
                    final isSelected = prov.selectedMonth == m['key'];
                    return ListTile(
                      title: Text(
                        m['label']!,
                        style: TextStyle(
                          fontWeight: isSelected ? FontWeight.w800 : FontWeight.w500,
                          color: isSelected ? AppColors.primaryBlueLight : null,
                        ),
                      ),
                      trailing: isSelected
                          ? const Icon(Icons.check_circle_rounded, color: AppColors.primaryBlueLight)
                          : null,
                      onTap: () {
                        Navigator.of(ctx).pop();
                        prov.setSelectedMonth(m['key']!);
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

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final prov = Provider.of<MonthlyReportsProvider>(context);
    final data = prov.monthlyData;
    final filteredRows = prov.filteredRows;

    final teamPills = [
      FilterPillItem(keyName: 'all', label: 'All Teams'),
      FilterPillItem(keyName: 'WpTitans', label: 'WpTitans'),
      FilterPillItem(keyName: 'WpNexus', label: 'WpNexus'),
      FilterPillItem(keyName: 'WpCraft', label: 'WpCraft'),
    ];

    String displayMonth = prov.selectedMonth;
    final matchPreset = MonthlyReportsProvider.availableReportingMonths.firstWhere(
      (m) => m['key'] == prov.selectedMonth,
      orElse: () => {'label': prov.selectedMonth},
    );
    displayMonth = matchPreset['label']!;

    return Scaffold(
      drawer: AppDrawer(
        selectedIndex: 4,
        onItemSelected: (idx) {
          Navigator.of(context).pop();
          DrawerOpener.of(context)?.navigateTo(idx);
        },
      ),
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
        title: const Text('Operational Progress Report'),
        actions: [
          TextButton.icon(
            onPressed: () => _showMonthPicker(context, prov),
            icon: const Icon(Icons.calendar_month_rounded, size: 16),
            label: Text(
              displayMonth,
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
      body: Column(
        children: [
          // Specialist Search Bar
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
                hintText: 'Search specialists by name, Call Sign, or Badge ID...',
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

          // Squad Filter Pills (including WpHorizon)
          FilterPillGroup(
            items: teamPills,
            selectedKey: prov.selectedTeam,
            onSelected: (key) => prov.setSelectedTeam(key),
          ),
          const SizedBox(height: 8),

          if (prov.isLoading && data != null)
            const LinearProgressIndicator(minHeight: 2),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => prov.fetchMonthlyReport(),
              child: prov.isLoading && data == null
                  ? const Center(child: CircularProgressIndicator())
                  : data == null && prov.errorMessage != null
                      ? EmptyStateWidget(
                          icon: Icons.cloud_off_rounded,
                          title: 'Could Not Load Report',
                          description: prov.errorMessage!,
                          actionLabel: 'Retry',
                          actionIcon: Icons.refresh_rounded,
                          onAction: () => prov.fetchMonthlyReport(),
                        )
                      : data == null || data.rows.isEmpty
                          ? const EmptyStateWidget(
                              icon: Icons.table_rows_rounded,
                              title: 'No Report Records',
                              description: 'No operational report data found for this period selection.',
                            )
                          : ListView(
                          padding: const EdgeInsets.all(16),
                          children: [
                            // 1. Total Assigned Reconciliation Master Card
                            _buildWorkloadMasterCard(context, data, isDark),
                            const SizedBox(height: 12),

                            // 2. The 6 Operational Drivers Grid
                            _buildOperationalDriverGrid(context, data, isDark),
                            const SizedBox(height: 18),

                            // 3. Squad Breakdown Section
                            if (data.teamBreakdown.isNotEmpty) ...[
                              _buildSquadBreakdownSection(context, data.teamBreakdown, isDark),
                              const SizedBox(height: 18),
                            ],

                            // 4. Specialist Roster Section Header
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'SPECIALIST OPERATIONAL PROGRESS (${filteredRows.length})',
                                  style: TextStyle(
                                    color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w800,
                                    letterSpacing: 0.8,
                                  ),
                                ),
                                Text(
                                  'Ranked by Delivery',
                                  style: TextStyle(
                                    color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                    fontSize: 10,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),

                            // Specialist Rows
                            ...filteredRows.asMap().entries.map((entry) {
                              final rank = entry.key + 1;
                              final row = entry.value;
                              return _buildSpecialistRowCard(context, row, rank, isDark);
                            }),
                          ],
                        ),
            ),
          ),
        ],
      ),
    );
  }

  // Workload Reconciliation Master Card (Total Assigned = New Assigned + Carry Forward)
  Widget _buildWorkloadMasterCard(BuildContext context, MonthlyReportData data, bool isDark) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDark
              ? [const Color(0xFF1E293B), const Color(0xFF0F172A)]
              : [const Color(0xFFEFF6FF), const Color(0xFFDBEAFE)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: AppColors.primaryBlueLight.withValues(alpha: 0.4),
          width: 1.5,
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
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: AppColors.primaryBlue.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.assignment_turned_in_rounded, size: 18, color: AppColors.primaryBlueLight),
                  ),
                  const SizedBox(width: 8),
                  const Text(
                    'TOTAL ASSIGNED WORKLOAD',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.8,
                      color: AppColors.primaryBlueLight,
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: AppColors.primaryBlue.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${data.totalAssignedCount} Orders',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                    color: AppColors.primaryBlueLight,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Text(
                '\$${data.totalAssignedVal.toStringAsFixed(0)}',
                style: TextStyle(
                  fontSize: 26,
                  fontWeight: FontWeight.w900,
                  color: isDark ? Colors.white : AppColors.textDark,
                  fontFeatures: const [FontFeature.tabularFigures()],
                ),
              ),
              const SizedBox(width: 8),
              Text(
                'total workload in period',
                style: TextStyle(
                  fontSize: 12,
                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          const Divider(height: 1),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildSubWorkloadCol(
                  label: 'NEW ASSIGNED',
                  count: data.totalNewAssignedCount,
                  value: data.totalNewAssignedVal,
                  sub: 'Booked in period',
                  accentColor: const Color(0xFF38BDF8),
                  isDark: isDark,
                ),
              ),
              Container(
                height: 36,
                width: 1,
                color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
              ),
              Expanded(
                child: _buildSubWorkloadCol(
                  label: 'CARRY FORWARD',
                  count: data.totalCarryForwardCount,
                  value: data.totalCarryForwardVal,
                  sub: 'From past months',
                  accentColor: const Color(0xFFA78BFA),
                  isDark: isDark,
                ),
              ),
              Container(
                height: 36,
                width: 1,
                color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
              ),
              Expanded(
                child: _buildSubWorkloadCol(
                  label: 'REPEAT CLIENT',
                  count: data.totalRepeatCount,
                  value: data.totalRepeatVal,
                  sub: 'Returning clients',
                  accentColor: const Color(0xFF06B6D4),
                  isDark: isDark,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSubWorkloadCol({
    required String label,
    required int count,
    required double value,
    required String sub,
    required Color accentColor,
    required bool isDark,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.5,
            color: accentColor,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          '\$${value.toStringAsFixed(0)}',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w800,
            color: isDark ? Colors.white : AppColors.textDark,
            fontFeatures: const [FontFeature.tabularFigures()],
          ),
        ),
        Text(
          '$count orders • $sub',
          style: TextStyle(
            fontSize: 10,
            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
          ),
        ),
      ],
    );
  }

  // Operational Driver Grid: Total WIP, NRA, Delivery, Cancelled, New Assigned, Carry Forward
  Widget _buildOperationalDriverGrid(BuildContext context, MonthlyReportData data, bool isDark) {
    final cards = [
      {
        'title': 'Total WIP',
        'count': '${data.totalWipCount} orders',
        'value': '\$${data.totalWipVal.toStringAsFixed(0)}',
        'sub': 'In progress right now',
        'color': AppColors.warning,
        'icon': Icons.hourglass_top_rounded,
      },
      {
        'title': 'NRA',
        'count': '${data.totalNraCount} orders',
        'value': '\$${data.totalNraVal.toStringAsFixed(0)}',
        'sub': 'Not started yet',
        'color': AppColors.cyanAccent,
        'icon': Icons.pending_actions_rounded,
      },
      {
        'title': 'Delivery',
        'count': '${data.totalDeliveryCount} orders',
        'value': '\$${data.totalDeliveryVal.toStringAsFixed(0)}',
        'sub': '80% net completed',
        'color': AppColors.success,
        'icon': Icons.check_circle_rounded,
      },
      {
        'title': 'Cancelled',
        'count': '${data.totalCancelledCount} orders',
        'value': '\$${data.totalCancelledVal.toStringAsFixed(0)}',
        'sub': 'Lost / cancelled volume',
        'color': AppColors.danger,
        'icon': Icons.cancel_outlined,
      },
      {
        'title': 'New Assigned',
        'count': '${data.totalNewAssignedCount} orders',
        'value': '\$${data.totalNewAssignedVal.toStringAsFixed(0)}',
        'sub': 'New current month queue',
        'color': const Color(0xFF38BDF8),
        'icon': Icons.add_task_rounded,
      },
      {
        'title': 'Carry Forward',
        'count': '${data.totalCarryForwardCount} orders',
        'value': '\$${data.totalCarryForwardVal.toStringAsFixed(0)}',
        'sub': 'Pending from past months',
        'color': const Color(0xFFA78BFA),
        'icon': Icons.inventory_2_rounded,
      },
    ];

    return GridView.builder(
      itemCount: cards.length,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        mainAxisSpacing: 10,
        crossAxisSpacing: 10,
        childAspectRatio: 1.5,
      ),
      itemBuilder: (ctx, idx) {
        final c = cards[idx];
        final color = c['color'] as Color;
        final icon = c['icon'] as IconData;

        return Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkCard : AppColors.lightCard,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
              width: 1,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    c['title'] as String,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                    ),
                  ),
                  Icon(icon, size: 16, color: color),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    c['value'] as String,
                    style: TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w800,
                      color: color,
                      fontFeatures: const [FontFeature.tabularFigures()],
                    ),
                  ),
                  const SizedBox(height: 2),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        c['count'] as String,
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: isDark ? AppColors.textLight : AppColors.textDark,
                        ),
                      ),
                      Flexible(
                        child: Text(
                          c['sub'] as String,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 9,
                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  // Squad / Team Breakdown Section
  Widget _buildSquadBreakdownSection(BuildContext context, List<TeamBreakdownItem> teams, bool isDark) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'SQUAD / TEAM OPERATIONAL BREAKDOWN',
          style: TextStyle(
            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
            fontSize: 11,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.8,
          ),
        ),
        const SizedBox(height: 10),
        SizedBox(
          height: 140,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: teams.length,
            separatorBuilder: (_, __) => const SizedBox(width: 10),
            itemBuilder: (ctx, idx) {
              final t = teams[idx];
              return Container(
                width: 250,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: isDark ? AppColors.darkCard : AppColors.lightCard,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        TeamBadge(team: t.teamName, isSmall: true),
                        Text(
                          '${t.memberCount} specialists',
                          style: TextStyle(
                            fontSize: 11,
                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                          ),
                        ),
                      ],
                    ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Total Assigned', style: TextStyle(fontSize: 10, color: AppColors.primaryBlueLight, fontWeight: FontWeight.w700)),
                            Text('${t.totalAssignedCount} (\$${t.totalAssignedVal.toStringAsFixed(0)})', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
                          ],
                        ),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            const Text('Delivered', style: TextStyle(fontSize: 10, color: AppColors.success, fontWeight: FontWeight.w700)),
                            Text('${t.deliveryCount} (\$${t.deliveryVal.toStringAsFixed(0)})', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppColors.success)),
                          ],
                        ),
                      ],
                    ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('WIP: ${t.wipCount}', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: AppColors.warning)),
                        Text('NRA: ${t.nraCount}', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: AppColors.cyanAccent)),
                        Text('Canc: ${t.cancelledCount}', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: AppColors.danger)),
                        Text('Carry: ${t.carryForwardCount}', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: Color(0xFFA78BFA))),
                      ],
                    ),
                  ],
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  // Specialist Row Card with the 7 Operational Metrics
  Widget _buildSpecialistRowCard(BuildContext context, MonthlyReportRow row, int rank, bool isDark) {
    final String effectiveName = row.name.isNotEmpty ? row.name : (row.callSign.isNotEmpty ? row.callSign : 'Specialist');
    final String rawCustom = row.customId.replaceAll('#', '').replaceAll('null', '').trim();
    final String badgeStr = rawCustom.isNotEmpty ? '#$rawCustom' : '';
    final bool isCallSignRedundant = row.callSign.trim().toLowerCase() == effectiveName.trim().toLowerCase() || row.callSign.trim().isEmpty;
    final String secondaryTitle = isCallSignRedundant ? (row.role.isNotEmpty ? row.role : 'Specialist') : 'Call Sign: ${row.callSign}';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
          width: 1,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () => _showSpecialistDetails(context, row, isDark),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header: Rank, Avatar, Name, Badge ID, Team
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Row(
                        children: [
                          Container(
                            width: 24,
                            height: 24,
                            decoration: BoxDecoration(
                              color: rank == 1
                                  ? const Color(0xFFFFD700)
                                  : (rank == 2
                                      ? const Color(0xFFC0C0C0)
                                      : (rank == 3
                                          ? const Color(0xFFCD7F32)
                                          : (isDark ? AppColors.darkSurface : AppColors.lightSurface))),
                              shape: BoxShape.circle,
                              border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                            ),
                            child: Center(
                              child: Text(
                                '#$rank',
                                style: TextStyle(
                                  color: rank <= 3 ? Colors.black : (isDark ? AppColors.textLight : AppColors.textDark),
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),

                          CircleAvatar(
                            radius: 17,
                            backgroundColor: AppColors.primaryBlue.withValues(alpha: 0.2),
                            backgroundImage: row.avatar.isNotEmpty ? NetworkImage(row.avatar) : null,
                            child: row.avatar.isEmpty
                                ? Text(
                                    effectiveName.isNotEmpty ? effectiveName[0].toUpperCase() : 'S',
                                    style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight),
                                  )
                                : null,
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Flexible(
                                      child: Text(
                                        effectiveName,
                                        style: TextStyle(
                                          color: isDark ? AppColors.textLight : AppColors.textDark,
                                          fontSize: 14,
                                          fontWeight: FontWeight.w700,
                                        ),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                    if (badgeStr.isNotEmpty) ...[
                                      const SizedBox(width: 6),
                                      Text(
                                        badgeStr,
                                        style: TextStyle(
                                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                          fontSize: 11,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                                Text(
                                  secondaryTitle,
                                  style: TextStyle(
                                    color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                    fontSize: 11,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (row.team.isNotEmpty) TeamBadge(team: row.team, isSmall: true),
                  ],
                ),

                const SizedBox(height: 12),

                // Operational Workload Pill (Total Assigned = New + Carry)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  decoration: BoxDecoration(
                    color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.assignment_turned_in_outlined, size: 14, color: AppColors.primaryBlueLight),
                          const SizedBox(width: 6),
                          Text(
                            'Total Assigned: ${row.totalAssignedCount} orders',
                            style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700),
                          ),
                        ],
                      ),
                      Text(
                        'New: ${row.newAssignedCount} • Carry: ${row.carryForwardCount}',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 8),

                // 4-Column Metric Status Bar: Delivery, WIP, NRA, Cancelled
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  decoration: BoxDecoration(
                    color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                  ),
                  child: Row(
                    children: [
                      // Delivery
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('DELIVERY', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: AppColors.success)),
                            const SizedBox(height: 2),
                            Text('\$${row.deliveryVal.toStringAsFixed(0)}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.success)),
                            Text('${row.deliveryCount} orders', style: TextStyle(fontSize: 9.5, color: isDark ? AppColors.textDim : AppColors.textDarkMuted)),
                          ],
                        ),
                      ),
                      // WIP
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            const Text('WIP', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: AppColors.warning)),
                            const SizedBox(height: 2),
                            Text('\$${row.wipVal.toStringAsFixed(0)}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.warning)),
                            Text('${row.wipCount} orders', style: TextStyle(fontSize: 9.5, color: isDark ? AppColors.textDim : AppColors.textDarkMuted)),
                          ],
                        ),
                      ),
                      // NRA
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            const Text('NRA', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: AppColors.cyanAccent)),
                            const SizedBox(height: 2),
                            Text('\$${row.nraVal.toStringAsFixed(0)}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.cyanAccent)),
                            Text('${row.nraCount} orders', style: TextStyle(fontSize: 9.5, color: isDark ? AppColors.textDim : AppColors.textDarkMuted)),
                          ],
                        ),
                      ),
                      // Cancelled
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            const Text('CANCELLED', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: AppColors.danger)),
                            const SizedBox(height: 2),
                            Text('\$${row.cancelledVal.toStringAsFixed(0)}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.danger)),
                            Text('${row.cancelledCount} orders', style: TextStyle(fontSize: 9.5, color: isDark ? AppColors.textDim : AppColors.textDarkMuted)),
                        Text(
                          '\$${row.cancelledVal.toStringAsFixed(0)}',
                          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.danger),
                        ),
                        Text('${row.cancelledCount} orders', style: TextStyle(fontSize: 9.5, color: isDark ? AppColors.textDim : AppColors.textDarkMuted)),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 8),

                // Per-Member Repeat Client Orders Bar
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                  decoration: BoxDecoration(
                    color: const Color(0xFF06B6D4).withValues(alpha: isDark ? 0.11 : 0.08),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: const Color(0xFF06B6D4).withValues(alpha: row.repeatCount > 0 ? 0.35 : 0.14),
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Icon(
                            Icons.repeat_rounded,
                            size: 14,
                            color: row.repeatCount > 0
                                ? const Color(0xFF06B6D4)
                                : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            'Repeat Orders: ${row.repeatCount}',
                            style: TextStyle(
                              fontSize: 11.5,
                              fontWeight: FontWeight.w700,
                              color: row.repeatCount > 0
                                  ? const Color(0xFF06B6D4)
                                  : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                            ),
                          ),
                        ],
                      ),
                      Text(
                        '\$${row.repeatVal.toStringAsFixed(2)}',
                        style: TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w800,
                          color: row.repeatCount > 0
                              ? const Color(0xFF06B6D4)
                              : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                          fontFeatures: const [FontFeature.tabularFigures()],
                        ),
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

  void _showSpecialistDetails(BuildContext context, MonthlyReportRow row, bool isDark) {
    final String effectiveName = row.name.isNotEmpty ? row.name : (row.callSign.isNotEmpty ? row.callSign : 'Specialist');
    final String rawCustom = row.customId.replaceAll('#', '').replaceAll('null', '').trim();
    final String badgeStr = rawCustom.isNotEmpty ? '#$rawCustom' : '';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: isDark ? AppColors.darkCard : AppColors.lightCard,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
        ),
        child: SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.white24,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              Row(
                children: [
                  CircleAvatar(
                    radius: 24,
                    backgroundColor: AppColors.primaryBlue.withValues(alpha: 0.2),
                    backgroundImage: row.avatar.isNotEmpty ? NetworkImage(row.avatar) : null,
                    child: row.avatar.isEmpty
                        ? Text(
                            effectiveName.isNotEmpty ? effectiveName[0].toUpperCase() : 'S',
                            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 18, color: AppColors.primaryBlueLight),
                          )
                        : null,
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          effectiveName,
                          style: TextStyle(
                            color: isDark ? AppColors.textLight : AppColors.textDark,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Row(
                          children: [
                            if (badgeStr.isNotEmpty) ...[
                              Text(
                                badgeStr,
                                style: const TextStyle(color: AppColors.primaryBlueLight, fontSize: 12, fontWeight: FontWeight.w600),
                              ),
                              const SizedBox(width: 8),
                            ],
                            Text(
                              row.role.isNotEmpty ? row.role : 'Specialist',
                              style: TextStyle(color: isDark ? AppColors.textDim : AppColors.textDarkMuted, fontSize: 12),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  if (row.team.isNotEmpty) TeamBadge(team: row.team),
                ],
              ),
              const SizedBox(height: 20),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Column(
                  children: [
                    _buildDetailMetricRow('Total Assigned Workload', '${row.totalAssignedCount} orders', '\$${row.totalAssignedVal.toStringAsFixed(0)}', isDark, isHighlight: true),
                    const Divider(height: 16),
                    _buildDetailMetricRow('New Assigned in Period', '${row.newAssignedCount} orders', '\$${row.newAssignedVal.toStringAsFixed(0)}', isDark),
                    const Divider(height: 16),
                    _buildDetailMetricRow('Carry Forward from Past', '${row.carryForwardCount} orders', '\$${row.carryForwardVal.toStringAsFixed(0)}', isDark),
                    const Divider(height: 16),
                    _buildDetailMetricRow('Total WIP (In Progress)', '${row.wipCount} orders', '\$${row.wipVal.toStringAsFixed(0)}', isDark),
                    const Divider(height: 16),
                    _buildDetailMetricRow('NRA (Not Started)', '${row.nraCount} orders', '\$${row.nraVal.toStringAsFixed(0)}', isDark),
                    const Divider(height: 16),
                    _buildDetailMetricRow('Delivery (Net Revenue)', '${row.deliveryCount} orders', '\$${row.deliveryVal.toStringAsFixed(0)}', isDark),
                    _buildDetailMetricRow('Delivery (Net Revenue)', '${row.deliveryCount} orders', '\$${row.deliveryVal.toStringAsFixed(2)}', isDark),
                    const Divider(height: 16),
                    _buildDetailMetricRow('Cancelled Orders', '${row.cancelledCount} orders', '\$${row.cancelledVal.toStringAsFixed(0)}', isDark),
                    _buildDetailMetricRow('Repeat Client Orders', '${row.repeatCount} orders', '\$${row.repeatVal.toStringAsFixed(2)}', isDark, isHighlight: row.repeatCount > 0),
                    const Divider(height: 16),
                    _buildDetailMetricRow('Cancelled Orders', '${row.cancelledCount} orders', '\$${row.cancelledVal.toStringAsFixed(2)}', isDark),
                    if (row.stars5 > 0) ...[
                      const Divider(height: 16),
                      _buildDetailMetricRow('5-Star Client Reviews', '${row.stars5} reviews', '⭐⭐⭐⭐⭐', isDark),
                    ],
                    if (row.onTimeCount > 0) ...[
                      const Divider(height: 16),
                      _buildDetailMetricRow('On-Time (No Extension)', '${row.onTimeCount} orders', '🎯 Perfect', isDark),
                    ],
                    if (row.extensionCount > 0) ...[
                      const Divider(height: 16),
                      _buildDetailMetricRow('Extensions Requested', '${row.extensionCount} times (+${row.extensionDays}d)', '⏳ Adjusted', isDark),
                    ],
                    if (row.lateCount > 0) ...[
                      const Divider(height: 16),
                      _buildDetailMetricRow('Late Deliveries', '${row.lateCount} orders', '⚠️ Delayed', isDark),
                    ],
                    if (row.tipCount > 0 || row.totalTips > 0) ...[
                      const Divider(height: 16),
                      _buildDetailMetricRow('Client Tips & Bonus', '${row.tipCount} tips', '\$${row.totalTips.toStringAsFixed(0)} bonus', isDark),
                    ],
                    if (row.reviewCount > 0) ...[
                      const Divider(height: 16),
                      _buildDetailMetricRow('Average Client Rating', '${row.avgRating.toStringAsFixed(1)} ★ (${row.reviewCount} reviews)', '🌟 Feedback', isDark),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 20),
              if (row.userId > 0)
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primaryBlue,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    icon: const Icon(Icons.chat_bubble_outline_rounded, size: 18),
                    label: Text('Send Private Message to $effectiveName'),
                    onPressed: () {
                      Navigator.pop(ctx);
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => ChatConversationScreen(
                            conversationType: 'direct',
                            conversationId: 'dm_${row.userId}',
                            recipientId: row.userId,
                            title: effectiveName,
                            subtitle: row.role.isNotEmpty ? row.role : 'Specialist',
                            avatar: row.avatar,
                          ),
                        ),
                      );
                    },
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetailMetricRow(String label, String count, String amount, bool isDark, {bool isHighlight = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            color: isHighlight ? AppColors.primaryBlueLight : (isDark ? AppColors.textLight : AppColors.textDark),
            fontWeight: isHighlight ? FontWeight.bold : FontWeight.w500,
            fontSize: 13,
          ),
        ),
        Row(
          children: [
            if (count.isNotEmpty) ...[
              Text(
                count,
                style: TextStyle(color: isDark ? AppColors.textDim : AppColors.textDarkMuted, fontSize: 12),
              ),
              const SizedBox(width: 8),
            ],
            Text(
              amount,
              style: TextStyle(
                color: isHighlight ? AppColors.primaryBlueLight : (isDark ? AppColors.textLight : AppColors.textDark),
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
          ],
        ),
      ],
    );
  }
}
