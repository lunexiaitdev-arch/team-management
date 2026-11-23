import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/auth_provider.dart';
import '../../providers/projects_provider.dart';
import '../../models/project.dart';
import '../../theme/app_colors.dart';
import '../../widgets/status_badge.dart';
import '../../widgets/team_badge.dart';
import '../../widgets/progress_bar.dart';
import '../../widgets/countdown_pill.dart';
import '../../widgets/filter_pill_group.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/drawer_opener.dart';
import 'project_detail_sheet.dart';
import 'update_progress_sheet.dart';
import 'add_project_sheet.dart';
import 'edit_project_sheet.dart';
import 'extend_delivery_sheet.dart';
import '../../providers/monthly_reports_provider.dart';

class ProjectsScreen extends StatefulWidget {
  const ProjectsScreen({super.key});

  @override
  State<ProjectsScreen> createState() => _ProjectsScreenState();
}

class _ProjectsScreenState extends State<ProjectsScreen> {
  final _searchController = TextEditingController();
  final _currency = NumberFormat.currency(symbol: '\$', decimalDigits: 0);

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  String _getMonthLabel(String key) {
    if (key == 'all') return 'All Months';
    if (key == 'carry_forward') return 'Carry Forward';
    for (final m in MonthlyReportsProvider.availableReportingMonths) {
      if (m['key'] == key) return m['label'] ?? key;
    }
    return key;
  }

  void _showFilterSheet(BuildContext context, ProjectsProvider prov, bool isDark) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (sheetCtx, setSheetState) {
            const teamItems = ['all', 'WpTitans', 'WpNexus', 'WpCraft', 'WpHorizon'];
            final monthItems = [
              {'key': 'all', 'label': 'All Months'},
              ...MonthlyReportsProvider.availableReportingMonths,
              {'key': 'carry_forward', 'label': '📦 Carry Forward (${prov.countCarryForward})'},
            ];

            return Container(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
              decoration: BoxDecoration(
                color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
              ),
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
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Filter Orders',
                        style: TextStyle(
                          color: isDark ? AppColors.textLight : AppColors.textDark,
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      if (prov.selectedTeam != 'all' || prov.selectedMonth != 'all')
                        TextButton(
                          onPressed: () {
                            prov.setTeamFilter('all');
                            prov.setSelectedMonth('all');
                            Navigator.of(sheetCtx).pop();
                          },
                          child: const Text('Reset All', style: TextStyle(color: AppColors.danger, fontWeight: FontWeight.w700)),
                        ),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // Squad Filter
                  Text(
                    'SQUAD / TEAM',
                    style: TextStyle(
                      color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.5,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 6,
                    children: teamItems.map((t) {
                      final isSel = prov.selectedTeam == t;
                      final label = t == 'all' ? 'All Squads' : t;
                      return ChoiceChip(
                        label: Text(label),
                        selected: isSel,
                        selectedColor: AppColors.primaryBlue,
                        labelStyle: TextStyle(
                          color: isSel ? Colors.white : (isDark ? AppColors.textLight : AppColors.textDark),
                          fontWeight: isSel ? FontWeight.w700 : FontWeight.w500,
                          fontSize: 12,
                        ),
                        onSelected: (_) {
                          prov.setTeamFilter(t);
                          setSheetState(() {});
                        },
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 18),

                  // Month Filter
                  Text(
                    'REPORTING MONTH / CARRY FORWARD',
                    style: TextStyle(
                      color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.5,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 6,
                    children: monthItems.map((m) {
                      final k = m['key']!;
                      final l = m['label']!;
                      final isSel = prov.selectedMonth == k;
                      return ChoiceChip(
                        label: Text(l),
                        selected: isSel,
                        selectedColor: AppColors.primaryBlue,
                        labelStyle: TextStyle(
                          color: isSel ? Colors.white : (isDark ? AppColors.textLight : AppColors.textDark),
                          fontWeight: isSel ? FontWeight.w700 : FontWeight.w500,
                          fontSize: 12,
                        ),
                        onSelected: (_) {
                          prov.setSelectedMonth(k);
                          setSheetState(() {});
                        },
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 22),

                  SizedBox(
                    width: double.infinity,
                    height: 46,
                    child: ElevatedButton(
                      onPressed: () => Navigator.of(sheetCtx).pop(),
                      child: const Text('Apply Filters', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final projectsProv = Provider.of<ProjectsProvider>(context);
    final auth = Provider.of<AuthProvider>(context);
    final filtered = projectsProv.filteredProjects;

    final int activeExtraFilters =
        (projectsProv.selectedTeam != 'all' ? 1 : 0) + (projectsProv.selectedMonth != 'all' ? 1 : 0);

    final statusPills = [
      FilterPillItem(keyName: 'all', label: 'All Orders', count: projectsProv.countAll),
      FilterPillItem(keyName: 'active', label: 'Active', count: projectsProv.countActive),
      FilterPillItem(keyName: 'repeat', label: 'Repeat Client', count: projectsProv.countRepeat),
      FilterPillItem(keyName: 'wip', label: 'In Progress', count: projectsProv.countWip),
      FilterPillItem(keyName: 'nra', label: 'Not Started', count: projectsProv.countNra),
      FilterPillItem(keyName: 'delivery_submitted', label: 'Submitted', count: projectsProv.countSubmitted),
      FilterPillItem(keyName: 'revision', label: 'Revision', count: projectsProv.countRevision),
      FilterPillItem(keyName: 'delivered_all', label: 'All Delivered', count: projectsProv.countDeliveredAll),
      FilterPillItem(keyName: 'delivery_accepted', label: 'Accepted', count: projectsProv.countAccepted),
      FilterPillItem(keyName: 'completed', label: 'Completed', count: projectsProv.countCompleted),
      FilterPillItem(keyName: 'cancelled', label: 'Cancelled', count: projectsProv.countCancelled),
    ];

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
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Order Pipeline', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800)),
            Text(
              _getMonthLabel(projectsProv.selectedMonth),
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
              ),
            ),
          ],
        ),
        actions: [
          if (auth.canAddProjects)
            IconButton(
              icon: const Icon(Icons.add_circle_outline_rounded),
              onPressed: () {
                showModalBottomSheet(
                  context: context,
                  isScrollControlled: true,
                  builder: (_) => const AddProjectSheet(),
                );
              },
              tooltip: 'Add Order',
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => projectsProv.fetchDashboardData(),
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: Column(
        children: [
          // Ultra Compact KPI Metric Summary Strip (~40px)
          Container(
            margin: const EdgeInsets.fromLTRB(16, 6, 16, 4),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            decoration: BoxDecoration(
              color: isDark ? AppColors.darkCard : AppColors.lightCard,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                width: 1,
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildSummaryCol(
                  'TOTAL',
                  '${projectsProv.countAll}',
                  AppColors.primaryBlueLight,
                  isDark,
                  isActive: projectsProv.selectedStatus == 'all',
                  onTap: () => projectsProv.setStatusFilter('all'),
                ),
                _buildVerticalDivider(isDark),
                _buildSummaryCol(
                  'ACTIVE WIP',
                  _currency.format(projectsProv.totalWipValue),
                  AppColors.warning,
                  isDark,
                  isActive: projectsProv.selectedStatus == 'wip',
                  onTap: () => projectsProv.setStatusFilter('wip'),
                ),
                _buildVerticalDivider(isDark),
                _buildSummaryCol(
                  'DELIVERED',
                  _currency.format(projectsProv.totalDeliveredNet),
                  AppColors.success,
                  isDark,
                  isActive: projectsProv.selectedStatus == 'delivered_all',
                  onTap: () => projectsProv.setStatusFilter('delivered_all'),
                ),
                _buildVerticalDivider(isDark),
                _buildSummaryCol(
                  'CANCELLED',
                  '${projectsProv.countCancelled} · ${_currency.format(projectsProv.totalCancelledNet)}',
                  AppColors.danger,
                  isDark,
                  isActive: projectsProv.selectedStatus == 'cancelled',
                  onTap: () => projectsProv.setStatusFilter('cancelled'),
                ),
              ],
            ),
          ),

          // Search Bar + Filter Button
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Row(
              children: [
                Expanded(
                  child: SizedBox(
                    height: 40,
                    child: TextField(
                      controller: _searchController,
                      onChanged: (val) => projectsProv.setSearchQuery(val),
                      style: TextStyle(
                        color: isDark ? AppColors.textLight : AppColors.textDark,
                        fontSize: 13,
                      ),
                      decoration: InputDecoration(
                        isDense: true,
                        hintText: 'Search Order ID, Buyer, Specialist...',
                        hintStyle: TextStyle(
                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                          fontSize: 12.5,
                        ),
                        prefixIcon: const Icon(Icons.search_rounded, size: 18),
                        suffixIcon: _searchController.text.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear_rounded, size: 16),
                                onPressed: () {
                                  _searchController.clear();
                                  projectsProv.setSearchQuery('');
                                },
                              )
                            : null,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                // Filter Button with Badge
                InkWell(
                  onTap: () => _showFilterSheet(context, projectsProv, isDark),
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    height: 40,
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: BoxDecoration(
                      color: activeExtraFilters > 0
                          ? AppColors.primaryBlue
                          : (isDark ? AppColors.darkCard : AppColors.lightCard),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: activeExtraFilters > 0
                            ? AppColors.primaryBlue
                            : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
                      ),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.tune_rounded,
                          size: 16,
                          color: activeExtraFilters > 0 ? Colors.white : AppColors.primaryBlueLight,
                        ),
                        if (activeExtraFilters > 0) ...[
                          const SizedBox(width: 5),
                          Container(
                            padding: const EdgeInsets.all(4),
                            decoration: const BoxDecoration(
                              color: Colors.white,
                              shape: BoxShape.circle,
                            ),
                            child: Text(
                              '$activeExtraFilters',
                              style: const TextStyle(
                                color: AppColors.primaryBlue,
                                fontSize: 10,
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Single Streamlined Horizontal Filter Row (Active Filter Chips + Status Pills)
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Row(
              children: [
                // Active Team Filter Chip
                if (projectsProv.selectedTeam != 'all') ...[
                  Container(
                    margin: const EdgeInsets.only(right: 6),
                    child: InputChip(
                      label: Text(
                        '★ ${projectsProv.selectedTeam}',
                        style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: Colors.white),
                      ),
                      backgroundColor: AppColors.primaryBlue,
                      deleteIconColor: Colors.white,
                      onDeleted: () => projectsProv.setTeamFilter('all'),
                      padding: const EdgeInsets.symmetric(horizontal: 6),
                      visualDensity: VisualDensity.compact,
                    ),
                  ),
                ],

                // Active Month Filter Chip
                if (projectsProv.selectedMonth != 'all') ...[
                  Container(
                    margin: const EdgeInsets.only(right: 6),
                    child: InputChip(
                      label: Text(
                        '📅 ${_getMonthLabel(projectsProv.selectedMonth)}',
                        style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: Colors.white),
                      ),
                      backgroundColor: const Color(0xFF0F766E),
                      deleteIconColor: Colors.white,
                      onDeleted: () => projectsProv.setSelectedMonth('all'),
                      padding: const EdgeInsets.symmetric(horizontal: 6),
                      visualDensity: VisualDensity.compact,
                    ),
                  ),
                ],

                // Status Pills
                ...statusPills.map((pill) {
                  final isSelected = projectsProv.selectedStatus == pill.keyName;
                  return Padding(
                    padding: const EdgeInsets.only(right: 6),
                    child: FilterChip(
                      label: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(pill.label),
                          if (pill.count != null && pill.count! > 0) ...[
                            const SizedBox(width: 4),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                              decoration: BoxDecoration(
                                color: isSelected
                                    ? Colors.white.withOpacity(0.25)
                                    : (isDark ? Colors.white.withOpacity(0.08) : Colors.black.withOpacity(0.06)),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Text(
                                '${pill.count}',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  color: isSelected
                                      ? Colors.white
                                      : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
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
                        color: isSelected
                            ? Colors.white
                            : (isDark ? AppColors.textLight : AppColors.textDark),
                        fontSize: 11.5,
                        fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                      ),
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      visualDensity: VisualDensity.compact,
                      onSelected: (_) => projectsProv.setStatusFilter(pill.keyName),
                    ),
                  );
                }),
              ],
            ),
          ),

          const SizedBox(height: 4),

          // Project Cards List
          if (projectsProv.isLoading && projectsProv.projects.isNotEmpty)
            const LinearProgressIndicator(minHeight: 2),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => projectsProv.fetchDashboardData(),
              child: projectsProv.isLoading && projectsProv.projects.isEmpty
                  ? const Center(child: CircularProgressIndicator())
                  : projectsProv.errorMessage != null && projectsProv.projects.isEmpty
                      ? EmptyStateWidget(
                          icon: Icons.cloud_off_rounded,
                          title: 'Could Not Load Orders',
                          description: projectsProv.errorMessage!,
                          actionLabel: 'Retry',
                          actionIcon: Icons.refresh_rounded,
                          onAction: () => projectsProv.fetchProjects(),
                        )
                      : filtered.isEmpty
                          ? EmptyStateWidget(
                              icon: Icons.layers_clear_rounded,
                              title: 'No Orders Found',
                              description: 'No orders match your selected filter criteria or search query.',
                              actionLabel: auth.canAddProjects ? 'Add New Order' : null,
                              onAction: auth.canAddProjects
                                  ? () {
                                      showModalBottomSheet(
                                        context: context,
                                        isScrollControlled: true,
                                        builder: (_) => const AddProjectSheet(),
                                      );
                                    }
                                  : null,
                            )
                          : ListView.builder(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                              itemCount: filtered.length,
                              itemBuilder: (context, index) {
                                final project = filtered[index];
                                return _buildProjectCard(context, project, isDark);
                              },
                            ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryCol(
    String label,
    String value,
    Color accent,
    bool isDark, {
    bool isActive = false,
    VoidCallback? onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
        decoration: isActive
            ? BoxDecoration(
                color: accent.withValues(alpha: 0.14),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: accent.withValues(alpha: 0.45), width: 1),
              )
            : null,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Text(
              label,
              style: TextStyle(
                color: isActive ? accent : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                fontSize: 9,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.4,
              ),
            ),
            const SizedBox(height: 1),
            Text(
              value,
              style: TextStyle(
                color: accent,
                fontSize: 12,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildVerticalDivider(bool isDark) {
    return Container(
      height: 22,
      width: 1,
      color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
    );
  }

  Widget _buildProjectCard(BuildContext context, ProjectItem project, bool isDark) {
    final auth = Provider.of<AuthProvider>(context, listen: false);

    // Accent line & tint for Late and Short Deadline
    final bool isLate = project.isLate;
    final bool isShortDeadline = project.isShortDeadline;

    BoxDecoration cardDecoration;
    if (isLate) {
      cardDecoration = BoxDecoration(
        color: isDark
            ? Color.alphaBlend(AppColors.danger.withOpacity(0.08), AppColors.darkCard)
            : Color.alphaBlend(AppColors.danger.withOpacity(0.04), AppColors.lightCard),
        borderRadius: BorderRadius.circular(16),
        border: Border(
          left: const BorderSide(color: AppColors.danger, width: 5),
          top: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.lightBorder, width: 1),
          right: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.lightBorder, width: 1),
          bottom: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.lightBorder, width: 1),
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.danger.withOpacity(0.12),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      );
    } else if (isShortDeadline) {
      cardDecoration = BoxDecoration(
        color: isDark
            ? Color.alphaBlend(AppColors.warning.withOpacity(0.08), AppColors.darkCard)
            : Color.alphaBlend(AppColors.warning.withOpacity(0.04), AppColors.lightCard),
        borderRadius: BorderRadius.circular(16),
        border: Border(
          left: const BorderSide(color: AppColors.warning, width: 5),
          top: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.lightBorder, width: 1),
          right: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.lightBorder, width: 1),
          bottom: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.lightBorder, width: 1),
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.warning.withOpacity(0.1),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      );
    } else {
      cardDecoration = BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDark ? 0.2 : 0.04),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      );
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: cardDecoration,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            showModalBottomSheet(
              context: context,
              isScrollControlled: true,
              builder: (_) => ProjectDetailSheet(project: project),
            );
          },
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(15),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Top Row: Order ID + Team Pill + Urgency Badge + Extension Pill + Status Pill
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Flexible(
                      child: Wrap(
                        crossAxisAlignment: WrapCrossAlignment.center,
                        spacing: 6,
                        runSpacing: 4,
                        children: [
                          Text(
                            project.orderId,
                            style: const TextStyle(
                              color: AppColors.primaryBlueLight,
                              fontSize: 14,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 0.3,
                            ),
                          ),
                          if (project.team.isNotEmpty)
                            TeamBadge(team: project.team, isSmall: true),
                          if (project.isRepeatClient)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: const Color(0xFF06B6D4).withValues(alpha: 0.14),
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: const Color(0xFF06B6D4).withValues(alpha: 0.4)),
                              ),
                              child: const Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(Icons.repeat_rounded, size: 10, color: Color(0xFF06B6D4)),
                                  SizedBox(width: 3),
                                  Text(
                                    'Repeat',
                                    style: TextStyle(
                                      color: Color(0xFF06B6D4),
                                      fontSize: 10,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          if (isLate)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: AppColors.danger.withOpacity(0.15),
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: AppColors.danger.withOpacity(0.4)),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.schedule_rounded, size: 10, color: AppColors.danger),
                                  const SizedBox(width: 3),
                                  Text(
                                    project.daysRemainingBadge.isNotEmpty ? project.daysRemainingBadge : 'Late',
                                    style: const TextStyle(
                                      color: AppColors.danger,
                                      fontSize: 10,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ],
                              ),
                            )
                          else if (isShortDeadline)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: AppColors.warning.withOpacity(0.15),
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: AppColors.warning.withOpacity(0.4)),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.timer_outlined, size: 10, color: AppColors.warning),
                                  const SizedBox(width: 3),
                                  Text(
                                    project.daysRemainingBadge.isNotEmpty ? project.daysRemainingBadge : 'Due Soon',
                                    style: const TextStyle(
                                      color: AppColors.warning,
                                      fontSize: 10,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          if (project.extensionCount > 0)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: Colors.amber.withOpacity(0.15),
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: Colors.amber.withOpacity(0.4)),
                              ),
                              child: Text(
                                '+${project.extensionDays}d (${project.extensionCount}x)',
                                style: TextStyle(
                                  color: isDark ? Colors.amber[300] : Colors.amber[800],
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 6),
                    StatusBadge(status: project.status),
                  ],
                ),

                // Special Chips Row: On-Time / Carry Forward / Combined / Subscription
                if (project.isCompleted ||
                    project.isCarryForward ||
                    project.isCombined ||
                    project.orderType == 'subscription') ...[
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 6,
                    runSpacing: 4,
                    children: [
                      if (project.isDelivered || project.isCompleted)
                      if (project.isCompleted)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                          decoration: BoxDecoration(
                            color: (project.isOntime ? AppColors.success : AppColors.danger).withOpacity(0.15),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(
                              color: (project.isOntime ? AppColors.success : AppColors.danger).withOpacity(0.35),
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                project.isOntime ? Icons.check_circle_rounded : Icons.cancel_rounded,
                                size: 11,
                                color: project.isOntime ? AppColors.success : AppColors.danger,
                              ),
                              const SizedBox(width: 4),
                              Text(
                                project.isOntime ? 'On-Time Delivery' : 'Delayed / Extension',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  color: project.isOntime ? AppColors.success : AppColors.danger,
                                ),
                              ),
                            ],
                          ),
                        ),
                      if (project.isCarryForward)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                          decoration: BoxDecoration(
                            color: Colors.amber.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(color: Colors.amber.withOpacity(0.4)),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Text('📦 ', style: TextStyle(fontSize: 10)),
                              Text(
                                project.carryForwardDays > 0 ? 'Carry Forward (${project.carryForwardDays}d)' : 'Carry Forward',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  color: isDark ? Colors.amber[300] : Colors.amber[800],
                                ),
                              ),
                            ],
                          ),
                        ),
                      if (project.isCombined)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                          decoration: BoxDecoration(
                            color: const Color(0xFF8B5CF6).withOpacity(0.15),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(color: const Color(0xFF8B5CF6).withOpacity(0.35)),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.call_split_rounded, size: 11, color: Color(0xFFA78BFA)),
                              const SizedBox(width: 4),
                              Text(
                                'Combined · Full \$${project.orderTotalValue.toStringAsFixed(0)} (My Share \$${project.netValue.toStringAsFixed(0)})',
                                style: const TextStyle(
                                  color: Color(0xFFA78BFA),
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ],
                          ),
                        ),
                      if (project.orderType == 'subscription')
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                          decoration: BoxDecoration(
                            color: AppColors.cyanAccent.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(color: AppColors.cyanAccent.withOpacity(0.35)),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.autorenew_rounded, size: 11, color: AppColors.cyanAccent),
                              const SizedBox(width: 4),
                              Text(
                                'Subscription · ${project.subscriptionStatus.toUpperCase()}',
                                style: const TextStyle(
                                  color: AppColors.cyanAccent,
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                ],

                const SizedBox(height: 8),

                // Title & Client
                Text(
                  project.title,
                  style: TextStyle(
                    color: isDark ? AppColors.textLight : AppColors.textDark,
                    fontSize: 14.5,
                    fontWeight: FontWeight.w700,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 3),
                Row(
                  children: [
                    Icon(
                      Icons.person_outline_rounded,
                      size: 13,
                      color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        'Buyer: ${project.buyerUsername} • ${project.profileName}',
                        style: TextStyle(
                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                          fontSize: 11.5,
                          fontWeight: FontWeight.w500,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),

                // Metrics Box (Financials & Countdown)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                      width: 1,
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'NET REVENUE',
                            style: TextStyle(
                              color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                              fontSize: 9.5,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.5,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '\$${project.netValue.toStringAsFixed(2)}',
                            style: const TextStyle(
                              color: AppColors.success,
                              fontSize: 14.5,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Text(
                            'ORDER PRICE',
                            style: TextStyle(
                              color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                              fontSize: 9.5,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.5,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '\$${project.orderValue.toStringAsFixed(2)}',
                            style: TextStyle(
                              color: isDark ? AppColors.textLight : AppColors.textDark,
                              fontSize: 13.5,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            project.deliveryDate.isNotEmpty ? 'DUE ${project.deliveryDate}' : 'DEADLINE',
                            style: TextStyle(
                              color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                              fontSize: 9.5,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.4,
                            ),
                          ),
                          const SizedBox(height: 2),
                          CountdownPill(
                            deliveryDateStr: project.deliveryDate,
                            badgeText: project.daysRemainingBadge,
                            urgency: project.daysRemainingUrgency,
                            status: project.status,
                            isCompleted: project.isCompleted,
                            isSubmitted: project.isSubmitted,
                            isOntime: project.isOntime,
                            extensionCount: project.extensionCount,
                            extensionDays: project.extensionDays,
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 8),

                // Assignee stack with Cross-Team Squad and Split %
                Row(
                  children: [
                    if (project.assignees.isNotEmpty) ...[
                      SizedBox(
                        height: 22,
                        child: Row(
                          children: project.assignees.take(3).map((a) {
                            return Container(
                              margin: const EdgeInsets.only(right: 4),
                              child: CircleAvatar(
                                radius: 10,
                                backgroundColor: AppColors.primaryBlue.withOpacity(0.2),
                                backgroundImage: a.avatar.isNotEmpty ? NetworkImage(a.avatar) : null,
                                child: a.avatar.isEmpty
                                    ? Text(
                                        a.name.isNotEmpty ? a.name[0].toUpperCase() : 'S',
                                        style: const TextStyle(fontSize: 8.5, fontWeight: FontWeight.bold, color: AppColors.primaryBlueLight),
                                      )
                                    : null,
                              ),
                            );
                          }).toList(),
                        ),
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          project.assignees.map((a) {
                            final splitStr = project.assignees.length > 1 ? ' (${a.splitPercent.toStringAsFixed(0)}%)' : '';
                            final teamStr = (a.team.isNotEmpty && a.team != project.team) ? ' [${a.team}]' : '';
                            return '${a.name}$teamStr$splitStr';
                          }).join(', '),
                          style: TextStyle(
                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                            fontSize: 11,
                            fontWeight: FontWeight.w500,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 6),

                // Progress Bar
                CustomProgressBar(progressPercent: project.progressPercent),
                const SizedBox(height: 10),

                // Action Buttons Row (Executive is View-Only)
                if (auth.isExecutive)
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        showModalBottomSheet(
                          context: context,
                          isScrollControlled: true,
                          backgroundColor: Colors.transparent,
                          builder: (_) => ProjectDetailSheet(project: project),
                        );
                      },
                      icon: const Icon(Icons.visibility_rounded, size: 15),
                      label: const Text('View Order & Telemetry', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF7C3AED),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 9),
                      ),
                    ),
                  )
                else
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () {
                            showModalBottomSheet(
                              context: context,
                              isScrollControlled: true,
                              backgroundColor: Colors.transparent,
                              builder: (_) => ProjectDetailSheet(project: project),
                            );
                          },
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 8),
                          ),
                          child: const Text('View Details', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: ElevatedButton(
                          onPressed: () {
                            showModalBottomSheet(
                              context: context,
                              isScrollControlled: true,
                              backgroundColor: Colors.transparent,
                              builder: (_) => UpdateProgressSheet(project: project),
                            );
                          },
                          style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 8),
                          ),
                          child: const Text('Update Progress', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700)),
                        ),
                      ),
                      if (project.isActive && !auth.isExecutive) ...[
                        const SizedBox(width: 8),
                        IconButton.outlined(
                          onPressed: () {
                            showModalBottomSheet(
                              context: context,
                              isScrollControlled: true,
                              backgroundColor: Colors.transparent,
                              builder: (_) => ExtendDeliverySheet(project: project),
                            );
                          },
                          icon: const Icon(Icons.schedule_rounded, size: 15, color: AppColors.warning),
                          tooltip: 'Extend Delivery Deadline',
                          style: IconButton.styleFrom(
                            side: BorderSide(color: AppColors.warning.withValues(alpha: 0.5)),
                            padding: const EdgeInsets.all(7),
                          ),
                        ),
                      ],
                      if (auth.canEditProjects) ...[
                        const SizedBox(width: 8),
                        IconButton.outlined(
                          onPressed: () {
                            showModalBottomSheet(
                              context: context,
                              isScrollControlled: true,
                              builder: (_) => EditProjectSheet(project: project),
                            );
                          },
                          icon: const Icon(Icons.edit_rounded, size: 15, color: AppColors.primaryBlueLight),
                          tooltip: 'Edit Order & Overrides',
                          style: IconButton.styleFrom(
                            side: BorderSide(color: AppColors.primaryBlueLight.withValues(alpha: 0.5)),
                            padding: const EdgeInsets.all(7),
                          ),
                        ),
                      ],
                    ],
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
