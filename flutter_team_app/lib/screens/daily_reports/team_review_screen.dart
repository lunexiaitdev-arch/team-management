import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import '../../models/daily_report.dart';
import '../../providers/auth_provider.dart';
import '../../providers/daily_reports_provider.dart';
import '../../providers/team_members_provider.dart';
import '../../theme/app_colors.dart';
import '../../widgets/drawer_opener.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/filter_pill_group.dart';
import '../../widgets/team_badge.dart';
import 'report_detail_sheet.dart';

class TeamReviewScreen extends StatefulWidget {
  final bool isEmbeddedInTab;
  const TeamReviewScreen({super.key, this.isEmbeddedInTab = false});

  @override
  State<TeamReviewScreen> createState() => _TeamReviewScreenState();
}

class _TeamReviewScreenState extends State<TeamReviewScreen> {
  final _commentController = TextEditingController();
  final _searchController = TextEditingController();
  bool _showMissingRoster = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final prov = Provider.of<DailyReportsProvider>(context, listen: false);
      final membersProv =
          Provider.of<TeamMembersProvider>(context, listen: false);
      prov.fetchReports();
      if (membersProv.members.isEmpty) {
        membersProv.fetchMembers();
      }
    });
  }

  @override
  void dispose() {
    _commentController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _quickAction(
    DailyReportsProvider prov,
    DailyReport report,
    String action, {
    String feedback = '',
  }) async {
    final res = await prov.reviewStandupAction(
      report.id,
      action,
      feedback: feedback,
    );
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res.message),
          backgroundColor: res.success ? AppColors.success : AppColors.danger,
        ),
      );
    }
  }

  void _showReviewDialog(
    BuildContext context,
    DailyReportsProvider prov,
    AuthProvider auth,
    DailyReport report,
  ) {
    _commentController.clear();
    final isManager = auth.isOperationsManager;

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Review ${report.userName}\'s Standup'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${report.reportDate} • ${report.totalHours.toStringAsFixed(1)}h • ${report.items.length} deliverables',
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _commentController,
              maxLines: 3,
              decoration: const InputDecoration(
                hintText: 'Review remark, revision instructions, or comment...',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Cancel'),
          ),
          OutlinedButton(
            onPressed: () async {
              final fb = _commentController.text.trim();
              if (fb.isEmpty) {
                ScaffoldMessenger.of(ctx).showSnackBar(
                  const SnackBar(
                    content: Text('Please enter feedback for revision request.'),
                  ),
                );
                return;
              }
              Navigator.of(ctx).pop();
              await _quickAction(prov, report, 'revision', feedback: fb);
            },
            child: const Text(
              'Request Revision',
              style: TextStyle(color: AppColors.warning),
            ),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.success),
            onPressed: () async {
              Navigator.of(ctx).pop();
              await _quickAction(
                prov,
                report,
                isManager ? 'approve' : 'leader_approve',
                feedback: _commentController.text.trim().isNotEmpty
                    ? _commentController.text.trim()
                    : (isManager
                        ? 'Approved by Operations Manager'
                        : 'Verified by Squad Leader'),
              );
            },
            child: Text(isManager ? 'Final Approve' : 'Verify'),
          ),
        ],
      ),
    );
  }

  void _confirmResetReport(
    BuildContext context,
    DailyReportsProvider prov,
    DailyReport report,
  ) {
    final reasonCtrl = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Reset ${report.userName}\'s Report?'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              'This clears the current submission so the member can submit a fresh work standup for this date.',
              style: TextStyle(fontSize: 12.5),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: reasonCtrl,
              decoration: const InputDecoration(
                hintText: 'Optional reason...',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            onPressed: () async {
              Navigator.of(ctx).pop();
              await _quickAction(
                prov,
                report,
                'reset',
                feedback: reasonCtrl.text.trim(),
              );
            },
            child: const Text('Reset Submission'),
          ),
        ],
      ),
    );
  }

  void _handleForwardReport(DailyReportsProvider prov, AuthProvider auth) {
    final noteCtrl = TextEditingController();
    final targetTeam = prov.selectedTeam != 'all'
        ? prov.selectedTeam
        : (auth.userTeam.isNotEmpty ? auth.userTeam : 'WpCraft');

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Forward Team Daily Summary'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Squad: $targetTeam • Date: ${prov.selectedDate == 'all' ? DateFormat('yyyy-MM-dd').format(DateTime.now()) : prov.selectedDate}',
              style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: noteCtrl,
              maxLines: 3,
              decoration: const InputDecoration(
                hintText: 'Leadership summary highlights & blockers...',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.of(ctx).pop();
              final res = await prov.forwardTeamReport(
                prov.selectedDate,
                targetTeam,
                summaryNotes: noteCtrl.text.trim(),
              );
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(res.message),
                    backgroundColor:
                        res.success ? AppColors.success : AppColors.danger,
                  ),
                );
              }
            },
            child: const Text('Forward to Manager'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final prov = Provider.of<DailyReportsProvider>(context);
    final membersProv = Provider.of<TeamMembersProvider>(context);
    final auth = Provider.of<AuthProvider>(context);
    final filtered = prov.filteredReports;

    final todayStr = DateFormat('yyyy-MM-dd').format(DateTime.now());
    final yesterdayStr = DateFormat('yyyy-MM-dd')
        .format(DateTime.now().subtract(const Duration(days: 1)));

    final datePills = [
      FilterPillItem(keyName: 'all', label: 'All Recent'),
      FilterPillItem(keyName: todayStr, label: 'Today'),
      FilterPillItem(keyName: yesterdayStr, label: 'Yesterday'),
    ];

    final teamPills = [
      FilterPillItem(keyName: 'all', label: 'All Teams'),
      FilterPillItem(keyName: 'WpTitans', label: 'WpTitans'),
      FilterPillItem(keyName: 'WpNexus', label: 'WpNexus'),
      FilterPillItem(keyName: 'WpCraft', label: 'WpCraft'),
    ];

    final statusPills = [
      FilterPillItem(
          keyName: 'all', label: 'All Status', count: prov.reports.length),
      FilterPillItem(
        keyName: 'submitted',
        label: 'Awaiting Review',
        count: prov.reports.where((r) => r.status == 'submitted').length,
      ),
      FilterPillItem(
        keyName: 'leader_approved',
        label: 'Leader Verified',
        count:
            prov.reports.where((r) => r.status == 'leader_approved').length,
      ),
      FilterPillItem(
        keyName: 'approved',
        label: 'Approved',
        count: prov.reports.where((r) => r.status == 'approved').length,
      ),
      FilterPillItem(
        keyName: 'revision_requested',
        label: 'Revision',
        count: prov.reports
            .where((r) => r.status == 'revision_requested')
            .length,
      ),
    ];

    // Compute Today's Roster Submission Coverage
    final workingMembers = membersProv.members.where((m) {
      final role = m.teamRole.toLowerCase();
      final team = m.team.toLowerCase();
      if (role == 'executive' ||
          role == 'deputy' ||
          role == 'management' ||
          role == 'admin') {
        return false;
      }
      if (team.contains('management')) return false;
      if (prov.selectedTeam != 'all' &&
          team.replaceAll(' ', '') !=
              prov.selectedTeam.toLowerCase().replaceAll(' ', '')) {
        return false;
      }
      return true;
    }).toList();

    final activeReportsForCoverage =
        prov.selectedDate == 'all' ? prov.todayReports : prov.reports;
    final submittedUserIds =
        activeReportsForCoverage.map((r) => r.userId).toSet();
    final missingMembers =
        workingMembers.where((m) => !submittedUserIds.contains(m.id)).toList();

    final content = Column(
      children: [
        // Search Bar + Calendar Date Button
        Padding(
          padding: const EdgeInsets.fromLTRB(14, 10, 14, 6),
          child: Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _searchController,
                  onChanged: (v) => prov.setSearchQuery(v),
                  decoration: InputDecoration(
                    hintText: 'Search member, Order ID, client, task...',
                    prefixIcon: const Icon(Icons.search_rounded, size: 18),
                    suffixIcon: _searchController.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear_rounded, size: 16),
                            onPressed: () {
                              _searchController.clear();
                              prov.setSearchQuery('');
                            },
                          )
                        : null,
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 8,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              InkWell(
                onTap: () async {
                  final initial = prov.selectedDate == 'all'
                      ? DateTime.now()
                      : (DateTime.tryParse(prov.selectedDate) ??
                          DateTime.now());
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: initial,
                    firstDate: DateTime(2026, 1, 1),
                    lastDate: DateTime.now().add(const Duration(days: 7)),
                  );
                  if (picked != null) {
                    prov.setSelectedDate(DateFormat('yyyy-MM-dd').format(picked));
                  }
                },
                borderRadius: BorderRadius.circular(10),
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 11,
                  ),
                  decoration: BoxDecoration(
                    color: (prov.selectedDate != 'all' &&
                            prov.selectedDate != todayStr &&
                            prov.selectedDate != yesterdayStr)
                        ? AppColors.primaryBlue.withValues(alpha: 0.14)
                        : (isDark
                            ? AppColors.darkCard
                            : AppColors.lightCard),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: isDark
                          ? AppColors.darkBorder
                          : AppColors.lightBorder,
                    ),
                  ),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.calendar_month_rounded,
                        size: 16,
                        color: AppColors.primaryBlueLight,
                      ),
                      if (prov.selectedDate != 'all' &&
                          prov.selectedDate != todayStr &&
                          prov.selectedDate != yesterdayStr) ...[
                        const SizedBox(width: 5),
                        Text(
                          prov.selectedDate,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
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

        // Single Streamlined Filter Row (Squad + Date + Status)
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
          child: Row(
            children: [
              // Squad Selector Popup (for Manager & Executive)
              if (auth.canViewAllTeams) ...[
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
                          prov.selectedTeam == 'all' ? 'All Squads' : prov.selectedTeam,
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
                    PopupMenuItem(value: 'all', child: Text('All Squads')),
                    PopupMenuItem(value: 'WpTitans', child: Text('WpTitans')),
                    PopupMenuItem(value: 'WpNexus', child: Text('WpNexus')),
                    PopupMenuItem(value: 'WpCraft', child: Text('WpCraft')),
                    PopupMenuItem(value: 'WpHorizon', child: Text('WpHorizon')),
                  ],
                ),
                const SizedBox(width: 8),
              ],

              // Date Filter Chips
              ...datePills.map((dp) {
                final isSelected = prov.selectedDate == dp.keyName;
                return Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: FilterChip(
                    label: Text(dp.label),
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
                    onSelected: (_) => prov.setSelectedDate(dp.keyName),
                  ),
                );
              }),

              // Status Filter Chips
              ...statusPills.map((sp) {
                final isSelected = prov.selectedStatus == sp.keyName;
                return Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: FilterChip(
                    label: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(sp.label),
                        if (sp.count != null && sp.count! > 0) ...[
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
                              '${sp.count}',
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
                      color: isSelected ? Colors.white : (isDark ? AppColors.textLight : AppColors.textDark),
                      fontSize: 12,
                      fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    visualDensity: VisualDensity.compact,
                    onSelected: (_) => prov.setSelectedStatus(sp.keyName),
                  ),
                );
              }),
            ],
          ),
        ),
        const SizedBox(height: 6),

        // Roster Submission Coverage Summary Strip
        if (workingMembers.isNotEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: isDark ? AppColors.darkCard : AppColors.lightCard,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  InkWell(
                    onTap: () => setState(
                        () => _showMissingRoster = !_showMissingRoster),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          '${prov.selectedDate == 'all' ? 'Today' : prov.selectedDate} Coverage: ${workingMembers.length - missingMembers.length} / ${workingMembers.length} Submitted',
                          style: const TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w800,
                            color: AppColors.primaryBlueLight,
                          ),
                        ),
                        Row(
                          children: [
                            Text(
                              missingMembers.isEmpty
                                  ? 'All Submitted ✓'
                                  : '${missingMembers.length} Pending',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: missingMembers.isEmpty
                                    ? AppColors.success
                                    : AppColors.warning,
                              ),
                            ),
                            if (missingMembers.isNotEmpty)
                              Icon(
                                _showMissingRoster
                                    ? Icons.expand_less_rounded
                                    : Icons.expand_more_rounded,
                                size: 18,
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  if (_showMissingRoster && missingMembers.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 6,
                      runSpacing: 6,
                      children: missingMembers.map((m) {
                        return Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: AppColors.warning.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(
                              color: AppColors.warning.withValues(alpha: 0.3),
                            ),
                          ),
                          child: Text(
                            '${m.name} (${m.team})',
                            style: const TextStyle(
                              fontSize: 10.5,
                              fontWeight: FontWeight.w600,
                              color: AppColors.warning,
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                  ],
                ],
              ),
            ),
          ),

        const SizedBox(height: 6),

        // Submissions List
        if (prov.isLoading && prov.reports.isNotEmpty)
          const LinearProgressIndicator(minHeight: 2),
        Expanded(
          child: RefreshIndicator(
            onRefresh: () => prov.fetchReports(),
            child: prov.isLoading && prov.reports.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : prov.errorMessage != null && prov.reports.isEmpty
                    ? EmptyStateWidget(
                        icon: Icons.cloud_off_rounded,
                        title: 'Could Not Load Submissions',
                        description: prov.errorMessage!,
                        actionLabel: 'Retry',
                        actionIcon: Icons.refresh_rounded,
                        onAction: () => prov.fetchReports(),
                      )
                    : filtered.isEmpty
                        ? EmptyStateWidget(
                            icon: Icons.checklist_rounded,
                            title: 'No Matching Submissions',
                            description:
                                'No standups match the selected date, squad, status, or search filter.',
                            actionLabel: prov.selectedDate != 'all'
                                ? 'Show All Recent'
                                : null,
                            onAction: prov.selectedDate != 'all'
                                ? () => prov.setSelectedDate('all')
                                : null,
                          )
                        : ListView.builder(
                            padding: const EdgeInsets.all(14),
                            itemCount: filtered.length,
                            itemBuilder: (context, index) {
                              final report = filtered[index];
                              return _buildReviewCard(
                                context,
                                report,
                                prov,
                                auth,
                                isDark,
                              );
                            },
                          ),
          ),
        ),
      ],
    );

    if (widget.isEmbeddedInTab) {
      return content;
    }

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
        title: Text(
          auth.isOperationsManager
              ? 'All Team Submissions Control'
              : (auth.isExecutive
                  ? 'Agency Standups (Read-Only)'
                  : 'Team Report Approvals'),
        ),
        actions: [
          if (!auth.isViewOnly && auth.isSquadLeader)
            IconButton(
              icon: const Icon(Icons.send_rounded),
              onPressed: () => _handleForwardReport(prov, auth),
              tooltip: 'Forward Summary to Management',
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => prov.fetchReports(),
            tooltip: 'Refresh Submissions',
          ),
        ],
      ),
      body: content,
    );
  }

  Widget _buildReviewCard(
    BuildContext context,
    DailyReport report,
    DailyReportsProvider prov,
    AuthProvider auth,
    bool isDark,
  ) {
    final isManager = auth.isOperationsManager;
    final canAct = auth.canReviewStandups && !auth.isViewOnly;

    Color statusColor = AppColors.primaryBlueLight;
    if (report.status == 'approved') {
      statusColor = AppColors.success;
    } else if (report.status == 'leader_approved') {
      statusColor = const Color(0xFF0D9488);
    } else if (report.status == 'revision_requested') {
      statusColor = AppColors.warning;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () => ReportDetailSheet.show(context, report),
          child: Padding(
            padding: const EdgeInsets.all(15),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Row(
                        children: [
                          CircleAvatar(
                            radius: 18,
                            backgroundColor:
                                AppColors.primaryBlue.withValues(alpha: 0.2),
                            backgroundImage: report.userAvatar.isNotEmpty
                                ? NetworkImage(report.userAvatar)
                                : null,
                            child: report.userAvatar.isEmpty
                                ? Text(
                                    report.userName.isNotEmpty
                                        ? report.userName[0].toUpperCase()
                                        : 'S',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.primaryBlueLight,
                                      fontSize: 13,
                                    ),
                                  )
                                : null,
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  report.userName,
                                  style: TextStyle(
                                    color: isDark
                                        ? AppColors.textLight
                                        : AppColors.textDark,
                                    fontSize: 14,
                                    fontWeight: FontWeight.w700,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                                Row(
                                  children: [
                                    if (report.team.isNotEmpty) ...[
                                      TeamBadge(
                                        team: report.team,
                                        isSmall: true,
                                      ),
                                      const SizedBox(width: 6),
                                    ],
                                    Text(
                                      report.reportDate,
                                      style: TextStyle(
                                        color: isDark
                                            ? AppColors.textDim
                                            : AppColors.textDarkMuted,
                                        fontSize: 11,
                                        fontWeight: FontWeight.w600,
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
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 3,
                          ),
                          decoration: BoxDecoration(
                            color: statusColor.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(
                              color: statusColor.withValues(alpha: 0.3),
                            ),
                          ),
                          child: Text(
                            report.statusLabel,
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                              color: statusColor,
                            ),
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          (report.reportType == 'on_leave' ||
                                  report.reportType == 'weekend')
                              ? (report.reportType == 'on_leave'
                                  ? '🏖️ On Leave'
                                  : '🌴 Weekend')
                              : '${report.totalHours.toStringAsFixed(1)}h logged${report.isLate ? " • LATE" : ""}',
                          style: TextStyle(
                            color: report.isLate
                                ? AppColors.warning
                                : AppColors.success,
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),

                const SizedBox(height: 10),

                if (report.deliveredProjects.isNotEmpty) ...[
                  Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    padding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: const Color(0xFF10B981).withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                        color: const Color(0xFF10B981).withValues(alpha: 0.25),
                      ),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.rocket_launch_rounded,
                          size: 14,
                          color: Color(0xFF10B981),
                        ),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            '${report.deliveredProjects.length} Deliveries: ${report.deliveredProjects.map((d) => d.orderId).join(", ")}',
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF10B981),
                            ),
                          ),
                        ),
                        Text(
                          '+ \$${report.deliveredProjects.fold<double>(0.0, (acc, d) => acc + d.netValue).toStringAsFixed(2)}',
                          style: const TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF10B981),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],

                ...report.items.take(3).map((i) {
                  final desc = i.tasksCompleted.isNotEmpty
                      ? i.tasksCompleted
                      : i.taskDescription;
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.check_circle_outline_rounded,
                          size: 14,
                          color: AppColors.primaryBlueLight,
                        ),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            '${i.orderId.isNotEmpty ? '[${i.orderId}] ' : ''}${desc.isNotEmpty ? desc : 'Work log'} (${i.hoursSpent.toStringAsFixed(1)}h)',
                            style: TextStyle(
                              color: isDark
                                  ? AppColors.textLight
                                  : AppColors.textDark,
                              fontSize: 12,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  );
                }),
                if (report.items.length > 3)
                  Text(
                    '+ ${report.items.length - 3} more items...',
                    style: TextStyle(
                      fontSize: 11,
                      fontStyle: FontStyle.italic,
                      color:
                          isDark ? AppColors.textDim : AppColors.textDarkMuted,
                    ),
                  ),

                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                        onPressed: () =>
                            ReportDetailSheet.show(context, report),
                        icon: const Icon(Icons.visibility_rounded, size: 15),
                        label: const Text(
                          'Inspect',
                          style: TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                    if (canAct && report.status != 'approved') ...[
                      const SizedBox(width: 8),
                      Expanded(
                        child: ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.success,
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                            elevation: 0,
                          ),
                          onPressed: () => _quickAction(
                            prov,
                            report,
                            isManager ? 'approve' : 'leader_approve',
                            feedback: isManager
                                ? 'Final approved by Operations Manager'
                                : 'Verified by Squad Leader',
                          ),
                          icon: const Icon(
                            Icons.check_circle_rounded,
                            size: 15,
                            color: Colors.white,
                          ),
                          label: Text(
                            isManager ? 'Final Approve' : 'Verify',
                            style: const TextStyle(
                              fontSize: 11.5,
                              fontWeight: FontWeight.w800,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ),
                    ],
                    if (canAct) ...[
                      const SizedBox(width: 4),
                      PopupMenuButton<String>(
                        icon: const Icon(Icons.more_vert_rounded, size: 20),
                        onSelected: (choice) {
                          if (choice == 'comment_review') {
                            _showReviewDialog(context, prov, auth, report);
                          } else if (choice == 'reset') {
                            _confirmResetReport(context, prov, report);
                          } else if (choice == 'leader_verify') {
                            _quickAction(
                              prov,
                              report,
                              'leader_approve',
                              feedback: 'Verified by leadership',
                            );
                          }
                        },
                        itemBuilder: (_) => [
                          const PopupMenuItem(
                            value: 'comment_review',
                            child: Text('Review / Revision with Note'),
                          ),
                          if (isManager && report.status == 'submitted')
                            const PopupMenuItem(
                              value: 'leader_verify',
                              child: Text('Mark Leader Verified'),
                            ),
                          const PopupMenuItem(
                            value: 'reset',
                            child: Text(
                              'Reset / Clear Submission',
                              style: TextStyle(color: AppColors.danger),
                            ),
                          ),
                        ],
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
