import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/attendance_provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/team_members_provider.dart';
import '../../models/attendance.dart';
import '../../models/user.dart';
import '../../theme/app_colors.dart';
import '../../widgets/drawer_opener.dart';
import '../../widgets/empty_state.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final p = Provider.of<AttendanceProvider>(context, listen: false);
      p.fetchAttendance();
      p.fetchLeavesReport();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _pickDate(BuildContext context, AttendanceProvider provider) async {
    DateTime initial = DateTime.tryParse(provider.selectedDate) ?? DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2026, 10, 1),
      lastDate: DateTime(2030, 12, 31),
    );
    if (picked != null) {
      provider.setSelectedDate(DateFormat('yyyy-MM-dd').format(picked));
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final attProv = Provider.of<AttendanceProvider>(context);
    final auth = Provider.of<AuthProvider>(context);
    final isLeaderOrAdmin = (auth.isLeader || auth.isAdmin) && !auth.isViewOnly;

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
        title: const Text('Attendance & Leaves'),
        actions: [
          if (!auth.isViewOnly)
            IconButton(
              icon: const Icon(Icons.add_circle_outline_rounded),
              tooltip: 'Apply for Leave',
              onPressed: () => _showApplyLeaveSheet(context, attProv),
            ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'Refresh Roster',
            onPressed: () => attProv.fetchAttendance(),
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: AppColors.primaryBlue,
          indicatorWeight: 3,
          labelColor: AppColors.primaryBlueLight,
          unselectedLabelColor: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
          labelStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
          tabs: [
            const Tab(text: 'Specialist Roster'),
            Tab(
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text('Leave Requests'),
                  if (attProv.pendingLeavesCount > 0) ...[
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: AppColors.warning,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        '${attProv.pendingLeavesCount}',
                        style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w800),
                      ),
                    ),
                  ],
                ],
              ),
            ),
            const Tab(text: '📊 Leave Report'),
          ],
        ),
      ),
      body: attProv.isLoading && attProv.roster.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                if (attProv.isLoading && attProv.roster.isNotEmpty)
                  const LinearProgressIndicator(minHeight: 2),
                // Filter Header: Date picker & Squad tabs
                _buildToolbar(context, isDark, attProv),

                // KPI Overview Cards
                _buildKpiBar(isDark, attProv.kpis),

                // Tab Views
                Expanded(
                  child: TabBarView(
                    controller: _tabController,
                    children: [
                      _buildRosterTab(isDark, attProv, isLeaderOrAdmin),
                      _buildLeavesTab(isDark, attProv, auth),
                      _buildLeaveReportTab(isDark, attProv, auth),
                    ],
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildToolbar(BuildContext context, bool isDark, AttendanceProvider attProv) {
    final teams = ['all', 'WpTitans', 'WpNexus', 'WpCraft', 'WpHorizon'];

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        border: Border(bottom: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.lightBorder)),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              InkWell(
                onTap: () => _pickDate(context, attProv),
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.calendar_today_rounded, size: 14, color: AppColors.primaryBlueLight),
                      const SizedBox(width: 6),
                      Text(
                        attProv.selectedDate,
                        style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(width: 4),
                      const Icon(Icons.arrow_drop_down_rounded, size: 18),
                    ],
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.primaryBlue.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.access_time_filled_rounded, size: 12, color: AppColors.primaryBlueLight),
                    SizedBox(width: 4),
                    Text(
                      'Cutoff: 9:15 AM',
                      style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: teams.map((team) {
                final isSelected = attProv.selectedTeam == team;
                final label = team == 'all' ? 'All Squads' : team;
                return Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: ChoiceChip(
                    label: Text(label),
                    selected: isSelected,
                    onSelected: (_) => attProv.setSelectedTeam(team),
                    labelStyle: TextStyle(
                      fontSize: 11.5,
                      fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                      color: isSelected ? Colors.white : (isDark ? AppColors.textMuted : AppColors.textDarkMuted),
                    ),
                    selectedColor: AppColors.primaryBlue,
                    backgroundColor: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  ),
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildKpiBar(bool isDark, AttendanceKpis? kpis) {
    if (kpis == null) return const SizedBox.shrink();

    final items = [
      {'label': 'Present', 'count': kpis.present, 'color': AppColors.success},
      {'label': 'Working', 'count': kpis.working, 'color': AppColors.primaryBlueLight},
      {'label': 'On Leave', 'count': kpis.onLeave, 'color': AppColors.warning},
      {'label': 'Day Off', 'count': kpis.dayOff, 'color': AppColors.textDim},
      {'label': 'Late / Absent', 'count': kpis.late + kpis.absent, 'color': AppColors.danger},
    ];

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: items.map((item) {
            final color = item['color'] as Color;
            return Container(
              margin: const EdgeInsets.only(right: 8),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: isDark ? AppColors.darkCard : AppColors.lightCard,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
              ),
              child: Row(
                children: [
                  Container(
                    width: 8,
                    height: 8,
                    decoration: BoxDecoration(color: color, shape: BoxShape.circle),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    '${item['label']}: ',
                    style: TextStyle(
                      fontSize: 11.5,
                      color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  Text(
                    '${item['count']}',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                      color: color,
                    ),
                  ),
                ],
              ),
            );
          }).toList(),
        ),
      ),
    );
  }

  Widget _buildRosterTab(bool isDark, AttendanceProvider attProv, bool isLeaderOrAdmin) {
    final roster = attProv.roster;
    if (roster.isEmpty) {
      return RefreshIndicator(
        onRefresh: () => attProv.fetchAttendance(),
        child: attProv.errorMessage != null
            ? EmptyStateWidget(
                icon: Icons.cloud_off_rounded,
                title: 'Could Not Load Attendance',
                description: attProv.errorMessage!,
                actionLabel: 'Retry',
                actionIcon: Icons.refresh_rounded,
                onAction: () => attProv.fetchAttendance(),
              )
            : const EmptyStateWidget(
                icon: Icons.groups_outlined,
                title: 'No Specialists Found',
                description: 'No attendance records found for this date and squad filter.',
              ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => attProv.fetchAttendance(),
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        itemCount: roster.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (ctx, i) {
          final r = roster[i];
          return _buildRosterCard(ctx, isDark, r, attProv, isLeaderOrAdmin);
        },
      ),
    );
  }

  Widget _buildRosterCard(BuildContext context, bool isDark, AttendanceRosterItem r, AttendanceProvider attProv, bool isLeaderOrAdmin) {
    Color statusColor;
    if (r.status == 'present') {
      statusColor = AppColors.success;
    } else if (r.status == 'on_leave') {
      statusColor = AppColors.warning;
    } else if (r.status == 'day_off') {
      statusColor = AppColors.textDim;
    } else if (r.status == 'late' || r.status == 'absent') {
      statusColor = AppColors.danger;
    } else {
      statusColor = AppColors.primaryBlueLight;
    }

    return InkWell(
      onTap: () => _showSpecialistDetailsSheet(context, isDark, r, attProv, isLeaderOrAdmin),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isDark ? AppColors.darkCard : AppColors.lightCard,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
          Row(
            children: [
              CircleAvatar(
                radius: 18,
                backgroundColor: AppColors.primaryBlue.withValues(alpha: 0.15),
                backgroundImage: r.avatar.isNotEmpty ? NetworkImage(r.avatar) : null,
                child: r.avatar.isEmpty
                    ? Text(
                        r.name.isNotEmpty ? r.name[0].toUpperCase() : '?',
                        style: const TextStyle(fontWeight: FontWeight.w800, color: AppColors.primaryBlueLight),
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
                        Text(
                          r.name,
                          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5),
                        ),
                        if (r.customId.isNotEmpty) ...[
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                            decoration: BoxDecoration(
                              color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                              borderRadius: BorderRadius.circular(4),
                              border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                            ),
                            child: Text(
                              r.customId,
                              style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700),
                            ),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${r.team} • ${r.roleLabel}',
                      style: TextStyle(
                        fontSize: 11,
                        color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  r.statusLabel,
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    color: statusColor,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      'Off: ${r.weeklyDayOff}',
                      style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600),
                    ),
                  ),
                  const SizedBox(width: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: r.hasReport
                          ? AppColors.success.withValues(alpha: 0.12)
                          : (isDark ? AppColors.darkSurface : AppColors.lightSurface),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      r.hasReport
                          ? (r.submissionTime != null ? 'Report: ${r.submissionTime}' : 'Report: Done')
                          : 'Report: Pending',
                      style: TextStyle(
                        fontSize: 10.5,
                        fontWeight: FontWeight.w600,
                        color: r.hasReport ? AppColors.success : (isDark ? AppColors.textMuted : AppColors.textDarkMuted),
                      ),
                    ),
                  ),
                ],
              ),
              if (isLeaderOrAdmin)
                Row(
                  children: [
                    InkWell(
                      onTap: () => _showChangeDayOffDialog(context, r, attProv),
                      borderRadius: BorderRadius.circular(4),
                      child: const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                        child: Text(
                          'Change Day-Off',
                          style: TextStyle(fontSize: 11, color: AppColors.primaryBlueLight, fontWeight: FontWeight.w700),
                        ),
                      ),
                    ),
                    const SizedBox(width: 4),
                    InkWell(
                      onTap: () => _showOverrideStatusDialog(context, r, attProv),
                      borderRadius: BorderRadius.circular(4),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.primaryBlue.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: const Text(
                          'Override',
                          style: TextStyle(fontSize: 11, color: AppColors.primaryBlueLight, fontWeight: FontWeight.w700),
                        ),
                      ),
                    ),
                  ],
                ),
            ],
          ),
          if (r.notes.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              r.notes,
              style: TextStyle(
                fontSize: 11,
                fontStyle: FontStyle.italic,
                color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
              ),
            ),
          ],
        ],
      ),
    ),
  );
  }

  Widget _buildLeavesTab(bool isDark, AttendanceProvider attProv, AuthProvider auth) {
    final filters = [
      {'key': 'all', 'label': 'All (${attProv.leaves.length})'},
      {'key': 'pending', 'label': 'Pending (${attProv.pendingLeavesCount})'},
      {'key': 'leader_approved', 'label': 'Leader Verified'},
      {'key': 'approved', 'label': 'Approved'},
      {'key': 'rejected', 'label': 'Rejected'},
    ];

    final leaves = attProv.leaves;

    return Column(
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkCard : AppColors.lightCard,
            border: Border(bottom: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.lightBorder)),
          ),
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: filters.map((f) {
                final isSelected = attProv.leavesFilter == f['key'];
                return Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: ChoiceChip(
                    label: Text(f['label']!),
                    selected: isSelected,
                    onSelected: (_) => attProv.setLeavesFilter(f['key']!),
                    labelStyle: TextStyle(
                      fontSize: 11,
                      fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                      color: isSelected ? Colors.white : (isDark ? AppColors.textMuted : AppColors.textDarkMuted),
                    ),
                    selectedColor: AppColors.primaryBlue,
                    backgroundColor: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  ),
                );
              }).toList(),
            ),
          ),
        ),
        Expanded(
          child: leaves.isEmpty
              ? Center(
                  child: Text(
                    'No leave applications found for this filter.',
                    style: TextStyle(color: isDark ? AppColors.textMuted : AppColors.textDarkMuted),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: () => attProv.fetchLeaves(),
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    itemCount: leaves.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 10),
                    itemBuilder: (ctx, i) {
                      final l = leaves[i];
                      final isPending = l.status == 'pending';
                      final canReviewLeader = !auth.isViewOnly && isPending && auth.isLeader && !auth.isOperationsManager;
                      final canApproveAdmin = !auth.isViewOnly && auth.isAdmin && l.status != 'approved';
                      final canRejectAdmin = !auth.isViewOnly && auth.isAdmin && l.status != 'rejected';
                      final canManageLeave = !auth.isViewOnly && (auth.isAdmin || auth.isLeader || l.userId == auth.userId);

                      return Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: isDark ? AppColors.darkCard : AppColors.lightCard,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  children: [
                                    CircleAvatar(
                                      radius: 14,
                                      backgroundImage: l.userAvatar.isNotEmpty ? NetworkImage(l.userAvatar) : null,
                                      child: l.userAvatar.isEmpty ? Text(l.userName.isNotEmpty ? l.userName[0] : '?') : null,
                                    ),
                                    const SizedBox(width: 8),
                                    Text(
                                      l.userName,
                                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
                                    ),
                                    const SizedBox(width: 6),
                                    Text(
                                      '(${l.teamName})',
                                      style: TextStyle(fontSize: 11, color: isDark ? AppColors.textMuted : AppColors.textDarkMuted),
                                    ),
                                  ],
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: l.status == 'approved'
                                        ? AppColors.success.withValues(alpha: 0.15)
                                        : (l.status == 'rejected'
                                            ? AppColors.danger.withValues(alpha: 0.15)
                                            : AppColors.warning.withValues(alpha: 0.15)),
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  child: Text(
                                    l.statusLabel,
                                    style: TextStyle(
                                      fontSize: 10.5,
                                      fontWeight: FontWeight.w800,
                                      color: l.status == 'approved'
                                          ? AppColors.success
                                          : (l.status == 'rejected' ? AppColors.danger : AppColors.warning),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Text(
                              l.requestType == 'day_off_switch'
                                  ? '🔄 Switch Off-Day: ${l.switchFromDate} ➔ ${l.switchToDate}'
                                  : '🏖️ ${l.leaveTypeLabel}: ${l.startDate} to ${l.endDate} (${l.daysLabel} days)',
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
                            ),
                            if (l.reason.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                l.reason,
                                style: TextStyle(
                                  fontSize: 11.5,
                                  color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                                ),
                              ),
                            ],
                            const SizedBox(height: 10),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Text(
                                    'Leader: ${l.leaderStatus} | Mgr: ${l.managerStatus}',
                                    style: TextStyle(
                                      fontSize: 10.5,
                                      color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                                    ),
                                  ),
                                ),
                                if (canReviewLeader || canApproveAdmin) ...[
                                  ElevatedButton(
                                    onPressed: () => _reviewLeave(
                                      context,
                                      attProv,
                                      l.id,
                                      auth.isAdmin ? 'approve' : 'leader_approve',
                                    ),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: AppColors.success,
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      minimumSize: Size.zero,
                                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                    ),
                                    child: Text(
                                      auth.isAdmin ? 'Approve' : 'Verify',
                                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700),
                                    ),
                                  ),
                                ],
                                if ((canReviewLeader || canRejectAdmin)) ...[
                                  const SizedBox(width: 4),
                                  OutlinedButton(
                                    onPressed: () => _reviewLeave(context, attProv, l.id, 'reject'),
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: AppColors.danger,
                                      side: const BorderSide(color: AppColors.danger),
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      minimumSize: Size.zero,
                                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                    ),
                                    child: const Text('Reject', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
                                  ),
                                ],
                                if (canManageLeave) ...[
                                  const SizedBox(width: 4),
                                  IconButton(
                                    icon: const Icon(Icons.edit_outlined, size: 18, color: AppColors.primaryBlueLight),
                                    tooltip: 'Edit Leave Dates / Reason',
                                    padding: EdgeInsets.zero,
                                    constraints: const BoxConstraints(),
                                    onPressed: () => _showEditLeaveSheet(context, attProv, l),
                                  ),
                                  const SizedBox(width: 4),
                                  IconButton(
                                    icon: const Icon(Icons.delete_outline_rounded, size: 18, color: AppColors.danger),
                                    tooltip: 'Delete / Cancel Leave',
                                    padding: EdgeInsets.zero,
                                    constraints: const BoxConstraints(),
                                    onPressed: () => _confirmDeleteLeave(context, attProv, l.id),
                                  ),
                                ],
                              ],
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                ),
        ),
      ],
    );
  }

  void _reviewLeave(BuildContext context, AttendanceProvider attProv, int leaveId, String action) async {
    final success = await attProv.reviewLeave(leaveId: leaveId, action: action);
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(success ? 'Leave request updated!' : 'Failed to update leave.'),
        backgroundColor: success ? AppColors.success : AppColors.danger,
      ),
    );
  }

  void _confirmDeleteLeave(BuildContext context, AttendanceProvider attProv, int leaveId) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Cancel Leave Application', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
        content: const Text('Are you sure you want to cancel this leave application? This will also remove any synced on-leave daily reports.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Keep Application'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            onPressed: () async {
              Navigator.pop(ctx);
              final success = await attProv.deleteLeave(leaveId);
              if (!context.mounted) return;
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(success ? 'Leave application deleted & synced reports cleared!' : 'Failed to delete application.'),
                  backgroundColor: success ? AppColors.success : AppColors.danger,
                ),
              );
            },
            child: const Text('Delete Leave', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  String _normalizeLeaveCategory(String raw) {
    final s = raw.trim().toLowerCase();
    if (s == 'casual' || s == 'casual_leave') return 'casual_leave';
    if (s == 'sick' || s == 'sick_leave') return 'sick_leave';
    if (s == 'half_day_first' || s == 'half_day_morning' || s == 'half_day') return 'half_day_morning';
    if (s == 'half_day_second' || s == 'half_day_evening') return 'half_day_evening';
    if (s == 'vacation' || s == 'planned_vacation') return 'vacation';
    if (s == 'emergency') return 'emergency';
    return 'casual_leave';
  }

  void _showEditLeaveSheet(BuildContext context, AttendanceProvider attProv, LeaveRequestItem l) {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final membersProv = Provider.of<TeamMembersProvider>(context, listen: false);
    if ((auth.isOperationsManager || auth.isLeader) && membersProv.members.isEmpty && !membersProv.isLoading) {
      membersProv.fetchMembers();
    }

    int selectedUserId = l.userId;
    String reqType = l.requestType == 'day_off_switch' ? 'day_off_switch' : 'leave';
    DateTime start = DateTime.tryParse(l.startDate) ?? DateTime.now();
    DateTime end = DateTime.tryParse(l.endDate) ?? start;
    DateTime switchFrom = DateTime.tryParse(l.switchFromDate) ?? start;
    DateTime switchTo = DateTime.tryParse(l.switchToDate) ?? end;
    String category = _normalizeLeaveCategory(l.leaveType);
    String statusOverride = ['approved', 'leader_approved', 'pending', 'rejected'].contains(l.status) ? l.status : 'pending';
    final daysCtrl = TextEditingController(text: l.daysCount > 0 ? l.daysLabel : '');
    final reasonCtrl = TextEditingController(text: l.reason);
    final managerFeedbackCtrl = TextEditingController(text: l.managerFeedback ?? '');

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) {
          final teamMembers = ctx.watch<TeamMembersProvider>().members;
          final bool isSwitch = reqType == 'day_off_switch';
          final bool isHalf = category.startsWith('half_day');

          return Padding(
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(ctx).viewInsets.bottom,
              left: 20,
              right: 20,
              top: 20,
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          'Edit Request: ${l.userName}',
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                    ],
                  ),
                  if (auth.isOperationsManager && teamMembers.isNotEmpty) ...[
                    const SizedBox(height: 10),
                    const Text('Assigned Member', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<int>(
                      value: teamMembers.any((m) => m.id == selectedUserId) ? selectedUserId : null,
                      hint: Text(l.userName),
                      items: teamMembers.map((m) => DropdownMenuItem<int>(
                        value: m.id,
                        child: Text('${m.name} (${m.team})'),
                      )).toList(),
                      onChanged: (val) {
                        if (val != null) setSheetState(() => selectedUserId = val);
                      },
                      decoration: const InputDecoration(border: OutlineInputBorder()),
                    ),
                  ],
                  if (auth.isOperationsManager) ...[
                    const SizedBox(height: 12),
                    const Text('Request Type', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<String>(
                      value: reqType,
                      items: const [
                        DropdownMenuItem(value: 'leave', child: Text('🏖️ Leave Request')),
                        DropdownMenuItem(value: 'day_off_switch', child: Text('🔄 Day-Off Adjustment / Switch')),
                      ],
                      onChanged: (val) {
                        if (val != null) setSheetState(() => reqType = val);
                      },
                      decoration: const InputDecoration(border: OutlineInputBorder()),
                    ),
                  ],
                  if (!isSwitch) ...[
                    const SizedBox(height: 12),
                    const Text('Leave Type:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<String>(
                      value: category,
                      items: const [
                        DropdownMenuItem(value: 'casual_leave', child: Text('Casual Leave (Full Day)')),
                        DropdownMenuItem(value: 'sick_leave', child: Text('Sick Leave (Full Day)')),
                        DropdownMenuItem(value: 'half_day_morning', child: Text('🌅 Half Day (Morning / 1st Half - 0.5d)')),
                        DropdownMenuItem(value: 'half_day_evening', child: Text('🌆 Half Day (Evening / 2nd Half - 0.5d)')),
                        DropdownMenuItem(value: 'vacation', child: Text('Planned Vacation')),
                        DropdownMenuItem(value: 'emergency', child: Text('Emergency Leave')),
                      ],
                      onChanged: (val) {
                        if (val != null) setSheetState(() => category = val);
                      },
                      decoration: const InputDecoration(border: OutlineInputBorder()),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(isHalf ? 'Leave Date:' : 'Start Date:', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                              const SizedBox(height: 6),
                              OutlinedButton.icon(
                                onPressed: () async {
                                  final p = await showDatePicker(
                                    context: ctx,
                                    initialDate: start,
                                    firstDate: DateTime(2025, 1, 1),
                                    lastDate: DateTime(2030, 12, 31),
                                  );
                                  if (p != null) {
                                    setSheetState(() {
                                      start = p;
                                      if (end.isBefore(start)) end = start;
                                    });
                                  }
                                },
                                icon: const Icon(Icons.calendar_today, size: 14),
                                label: Text(DateFormat('yyyy-MM-dd').format(start), style: const TextStyle(fontSize: 11)),
                              ),
                            ],
                          ),
                        ),
                        if (!isHalf) ...[
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('End Date:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                                const SizedBox(height: 6),
                                OutlinedButton.icon(
                                  onPressed: () async {
                                    final p = await showDatePicker(
                                      context: ctx,
                                      initialDate: end,
                                      firstDate: start,
                                      lastDate: DateTime(2030, 12, 31),
                                    );
                                    if (p != null) setSheetState(() => end = p);
                                  },
                                  icon: const Icon(Icons.calendar_today, size: 14),
                                  label: Text(DateFormat('yyyy-MM-dd').format(end), style: const TextStyle(fontSize: 11)),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ],
                    ),
                  ] else ...[
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Original Off-Day:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                              const SizedBox(height: 6),
                              OutlinedButton.icon(
                                onPressed: () async {
                                  final p = await showDatePicker(
                                    context: ctx,
                                    initialDate: switchFrom,
                                    firstDate: DateTime(2025, 1, 1),
                                    lastDate: DateTime(2030, 12, 31),
                                  );
                                  if (p != null) setSheetState(() => switchFrom = p);
                                },
                                icon: const Icon(Icons.calendar_today, size: 14),
                                label: Text(DateFormat('yyyy-MM-dd').format(switchFrom), style: const TextStyle(fontSize: 11)),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Switched Day:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                              const SizedBox(height: 6),
                              OutlinedButton.icon(
                                onPressed: () async {
                                  final p = await showDatePicker(
                                    context: ctx,
                                    initialDate: switchTo,
                                    firstDate: DateTime(2025, 1, 1),
                                    lastDate: DateTime(2030, 12, 31),
                                  );
                                  if (p != null) setSheetState(() => switchTo = p);
                                },
                                icon: const Icon(Icons.calendar_today, size: 14),
                                label: Text(DateFormat('yyyy-MM-dd').format(switchTo), style: const TextStyle(fontSize: 11)),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ],
                  if (auth.isOperationsManager) ...[
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Status Override', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                              const SizedBox(height: 6),
                              DropdownButtonFormField<String>(
                                value: statusOverride,
                                items: const [
                                  DropdownMenuItem(value: 'approved', child: Text('Approved')),
                                  DropdownMenuItem(value: 'leader_approved', child: Text('Leader Verified')),
                                  DropdownMenuItem(value: 'pending', child: Text('Pending')),
                                  DropdownMenuItem(value: 'rejected', child: Text('Rejected')),
                                ],
                                onChanged: (val) {
                                  if (val != null) setSheetState(() => statusOverride = val);
                                },
                                decoration: const InputDecoration(border: OutlineInputBorder()),
                              ),
                            ],
                          ),
                        ),
                        if (!isSwitch) ...[
                          const SizedBox(width: 10),
                          SizedBox(
                            width: 110,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('Days Count', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                                const SizedBox(height: 6),
                                TextField(
                                  controller: daysCtrl,
                                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                  decoration: const InputDecoration(
                                    hintText: 'Auto',
                                    border: OutlineInputBorder(),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 12),
                    const Text('Manager Feedback (Optional)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: managerFeedbackCtrl,
                      decoration: const InputDecoration(
                        hintText: 'Admin note / approval comment',
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ],
                  const SizedBox(height: 12),
                  const Text('Reason / Handover Notes:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: reasonCtrl,
                    maxLines: 2,
                    decoration: const InputDecoration(border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 18),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () async {
                        final payload = <String, dynamic>{
                          'request_type': reqType,
                          'reason': reasonCtrl.text.trim(),
                        };
                        if (isSwitch) {
                          payload['switch_from_date'] = DateFormat('yyyy-MM-dd').format(switchFrom);
                          payload['switch_to_date'] = DateFormat('yyyy-MM-dd').format(switchTo);
                        } else {
                          payload['leave_type'] = category;
                          payload['start_date'] = DateFormat('yyyy-MM-dd').format(start);
                          payload['end_date'] = DateFormat('yyyy-MM-dd').format(isHalf ? start : end);
                        }
                        if (auth.isOperationsManager) {
                          payload['user_id'] = selectedUserId;
                          payload['status'] = statusOverride;
                          final parsedDays = double.tryParse(daysCtrl.text.trim());
                          if (!isSwitch && parsedDays != null && parsedDays > 0) {
                            payload['days_count'] = parsedDays;
                          }
                          if (managerFeedbackCtrl.text.trim().isNotEmpty) {
                            payload['manager_feedback'] = managerFeedbackCtrl.text.trim();
                          }
                        }

                        Navigator.pop(ctx);
                        final success = await attProv.updateLeave(l.id, payload);
                        if (!context.mounted) return;
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(success ? 'Request updated successfully!' : (attProv.errorMessage ?? 'Failed to update request.')),
                            backgroundColor: success ? AppColors.success : AppColors.danger,
                          ),
                        );
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primaryBlue,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      child: const Text('Save Changes', style: TextStyle(fontWeight: FontWeight.w800)),
                    ),
                  ),
                  const SizedBox(height: 20),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  // --- TAB 3: Comprehensive Leave Management Report & Balances ---
  Widget _buildLeaveReportTab(bool isDark, AttendanceProvider attProv, AuthProvider auth) {
    if (attProv.isLoadingReport) {
      return const Center(child: CircularProgressIndicator());
    }

    final rep = attProv.leavesReport ?? {};
    final summary = rep['summary'] is Map ? Map<String, dynamic>.from(rep['summary']) : {};
    final matrix = rep['matrix'] is List ? (rep['matrix'] as List) : [];
    final records = rep['records'] is List ? (rep['records'] as List) : [];

    return RefreshIndicator(
      onRefresh: () async => attProv.fetchLeavesReport(),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Month Selector Bar
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Reporting Month: ${attProv.reportMonth}',
                  style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
                ),
                OutlinedButton.icon(
                  onPressed: () async {
                    final initial = DateTime.tryParse('${attProv.reportMonth}-01') ?? DateTime.now();
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: initial,
                      firstDate: DateTime(2026, 1, 1),
                      lastDate: DateTime(2030, 12, 31),
                    );
                    if (picked != null) {
                      attProv.setReportMonth(DateFormat('yyyy-MM').format(picked));
                    }
                  },
                  icon: const Icon(Icons.date_range_rounded, size: 14),
                  label: const Text('Change Month', style: TextStyle(fontSize: 11)),
                  style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6), minimumSize: Size.zero),
                ),
              ],
            ),

            const SizedBox(height: 12),

            // Summary 4 KPIs
            Row(
              children: [
                Expanded(
                  child: _buildReportKpiCard(
                    title: 'Month Total',
                    value: '${summary['total_leave_days_month'] ?? 0}d',
                    color: AppColors.primaryBlueLight,
                    isDark: isDark,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildReportKpiCard(
                    title: 'YTD Total',
                    value: '${summary['total_leave_days_year'] ?? 0}d',
                    color: AppColors.success,
                    isDark: isDark,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildReportKpiCard(
                    title: 'Casual',
                    value: '${summary['casual_days_month'] ?? 0}d',
                    color: const Color(0xFFF59E0B),
                    isDark: isDark,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildReportKpiCard(
                    title: 'Sick',
                    value: '${summary['sick_days_month'] ?? 0}d',
                    color: const Color(0xFF8B5CF6),
                    isDark: isDark,
                  ),
                ),
              ],
            ),

            const SizedBox(height: 20),

            // Specialist Quotas & Balances
            const Text(
              'Specialist Leave Balances & Quotas',
              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 8),
            if (matrix.isEmpty)
              Container(
                padding: const EdgeInsets.all(16),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: isDark ? AppColors.darkCard : AppColors.lightCard,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                ),
                child: Text('No specialist balances found.', style: TextStyle(color: isDark ? AppColors.textMuted : AppColors.textDarkMuted, fontSize: 12)),
              )
            else
              ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: matrix.length,
                separatorBuilder: (_, _) => const SizedBox(height: 8),
                itemBuilder: (ctx, i) {
                  final m = Map<String, dynamic>.from(matrix[i]);
                  final uid = int.tryParse(m['user_id']?.toString() ?? '0') ?? 0;
                  final name = m['member_name']?.toString() ?? 'Specialist';
                  final team = m['team_name']?.toString() ?? '';
                  final cTaken = m['casual_taken']?.toString() ?? '0';
                  final cQuota = m['casual_quota']?.toString() ?? '14';
                  final sTaken = m['sick_taken']?.toString() ?? '0';
                  final sQuota = m['sick_quota']?.toString() ?? '14';
                  final adj = double.tryParse(m['adjustment_days']?.toString() ?? '0') ?? 0.0;
                  final remaining = m['remaining']?.toString() ?? '';
                  final dayOff = m['weekly_day_off']?.toString() ?? '';
                  final adjText = adj == 0 ? '' : (adj > 0 ? ' +${adj % 1 == 0 ? adj.toInt() : adj} adj' : ' ${adj % 1 == 0 ? adj.toInt() : adj} adj');

                  return Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    decoration: BoxDecoration(
                      color: isDark ? AppColors.darkCard : AppColors.lightCard,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5)),
                                  Text(
                                    [
                                      if (team.isNotEmpty) team,
                                      if (dayOff.isNotEmpty) 'Off: $dayOff',
                                      if (remaining.isNotEmpty) 'Bal: ${remaining}d$adjText',
                                    ].join(' • '),
                                    style: TextStyle(fontSize: 10.5, color: isDark ? AppColors.textMuted : AppColors.textDarkMuted),
                                  ),
                                ],
                              ),
                            ),
                            Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(color: const Color(0xFFF59E0B).withValues(alpha: 0.15), borderRadius: BorderRadius.circular(6)),
                                  child: Text('Casual: $cTaken/$cQuota d', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: Color(0xFFF59E0B))),
                                ),
                                const SizedBox(width: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(color: const Color(0xFF8B5CF6).withValues(alpha: 0.15), borderRadius: BorderRadius.circular(6)),
                                  child: Text('Sick: $sTaken/$sQuota d', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: Color(0xFF8B5CF6))),
                                ),
                              ],
                            ),
                          ],
                        ),
                        if (!auth.isViewOnly && (auth.isOperationsManager || auth.isLeader) && uid > 0) ...[
                          const SizedBox(height: 8),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.end,
                            children: [
                              OutlinedButton.icon(
                                onPressed: () => _showApplyLeaveSheet(context, attProv, preselectedUserId: uid),
                                icon: const Icon(Icons.add_rounded, size: 14),
                                label: const Text('Apply Leave', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
                                style: OutlinedButton.styleFrom(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  minimumSize: Size.zero,
                                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                ),
                              ),
                              if (auth.isOperationsManager) ...[
                                const SizedBox(width: 8),
                                ElevatedButton.icon(
                                  onPressed: () => _showAdjustQuotaSheet(context, attProv, m),
                                  icon: const Icon(Icons.tune_rounded, size: 14),
                                  label: const Text('Quota & Off', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: AppColors.primaryBlue,
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                    minimumSize: Size.zero,
                                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ],
                      ],
                    ),
                  );
                },
              ),

            const SizedBox(height: 20),

            // Detailed Leave Records Audit Log
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Leave Audit Log & Management',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800),
                ),
                Text(
                  '${records.length} Records',
                  style: TextStyle(fontSize: 11, color: isDark ? AppColors.textMuted : AppColors.textDarkMuted),
                ),
              ],
            ),
            const SizedBox(height: 8),
            if (records.isEmpty)
              Container(
                padding: const EdgeInsets.all(20),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: isDark ? AppColors.darkCard : AppColors.lightCard,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                ),
                child: Text('No leave records logged for this period.', style: TextStyle(color: isDark ? AppColors.textMuted : AppColors.textDarkMuted, fontSize: 12)),
              )
            else
              ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: records.length,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (ctx, i) {
                  final r = Map<String, dynamic>.from(records[i]);
                  final rid = int.tryParse(r['id']?.toString() ?? '0') ?? 0;
                  final rUserId = int.tryParse(r['user_id']?.toString() ?? '0') ?? 0;
                  final rName = r['user_name']?.toString() ?? 'Specialist';
                  final rType = r['leave_type_label']?.toString() ?? r['request_type_label']?.toString() ?? 'Leave';
                  final rStart = r['start_date']?.toString() ?? '';
                  final rEnd = r['end_date']?.toString() ?? '';
                  final rDays = r['days_count']?.toString() ?? '1';
                  final rReason = r['reason']?.toString() ?? '';
                  final rStatus = r['status']?.toString() ?? 'pending';
                  final rStatusLabel = r['status_label']?.toString() ?? rStatus;
                  final canManage = !auth.isViewOnly && (auth.isAdmin || auth.isLeader || rUserId == auth.userId);

                  return Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: isDark ? AppColors.darkCard : AppColors.lightCard,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(rName, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13)),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: rStatus == 'approved'
                                    ? AppColors.success.withValues(alpha: 0.15)
                                    : (rStatus == 'rejected' ? AppColors.danger.withValues(alpha: 0.15) : AppColors.warning.withValues(alpha: 0.15)),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                rStatusLabel,
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                  color: rStatus == 'approved' ? AppColors.success : (rStatus == 'rejected' ? AppColors.danger : AppColors.warning),
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Text(
                          '$rType: $rStart ➔ $rEnd (${rDays}d)',
                          style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700),
                        ),
                        if (rReason.isNotEmpty) ...[
                          const SizedBox(height: 4),
                          Text(
                            'Reason: $rReason',
                            style: TextStyle(fontSize: 11, color: isDark ? AppColors.textMuted : AppColors.textDarkMuted),
                          ),
                        ],
                        if (canManage) ...[
                          const SizedBox(height: 8),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.end,
                            children: [
                              IconButton(
                                icon: const Icon(Icons.edit_outlined, size: 18, color: AppColors.primaryBlueLight),
                                tooltip: 'Edit Dates',
                                constraints: const BoxConstraints(),
                                padding: const EdgeInsets.symmetric(horizontal: 8),
                                onPressed: () {
                                  final dummy = LeaveRequestItem(
                                    id: rid,
                                    userId: rUserId,
                                    userName: rName,
                                    userAvatar: '',
                                    teamName: r['team_name']?.toString() ?? '',
                                    requestType: r['request_type']?.toString() ?? 'leave',
                                    requestTypeLabel: r['request_type_label']?.toString() ?? 'Leave',
                                    leaveType: r['leave_type']?.toString() ?? 'casual_leave',
                                    leaveTypeLabel: rType,
                                    startDate: rStart,
                                    endDate: rEnd,
                                    switchFromDate: r['switch_from_date']?.toString() ?? '',
                                    switchToDate: r['switch_to_date']?.toString() ?? '',
                                    daysCount: double.tryParse(rDays) ?? 1.0,
                                    reason: rReason,
                                    status: rStatus,
                                    statusLabel: rStatusLabel,
                                    leaderStatus: r['leader_status']?.toString() ?? '',
                                    managerStatus: r['manager_status']?.toString() ?? '',
                                    createdAt: '',
                                    leaderFeedback: r['leader_feedback']?.toString(),
                                    managerFeedback: r['manager_feedback']?.toString(),
                                  );
                                  _showEditLeaveSheet(context, attProv, dummy);
                                },
                              ),
                              IconButton(
                                icon: const Icon(Icons.delete_outline_rounded, size: 18, color: AppColors.danger),
                                tooltip: 'Delete Leave',
                                constraints: const BoxConstraints(),
                                padding: const EdgeInsets.symmetric(horizontal: 8),
                                onPressed: () => _confirmDeleteLeave(context, attProv, rid),
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  );
                },
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildReportKpiCard({required String title, required String value, required Color color, required bool isDark}) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
      ),
      child: Column(
        children: [
          Text(value, style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: color)),
          const SizedBox(height: 2),
          Text(title, style: TextStyle(fontSize: 10, color: isDark ? AppColors.textMuted : AppColors.textDarkMuted), textAlign: TextAlign.center),
        ],
      ),
    );
  }

  void _showSpecialistDetailsSheet(BuildContext context, bool isDark, AttendanceRosterItem r, AttendanceProvider attProv, bool isLeaderOrAdmin) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: isDark ? AppColors.darkCard : AppColors.lightCard,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: AppColors.primaryBlue.withValues(alpha: 0.15),
                  backgroundImage: r.avatar.isNotEmpty ? NetworkImage(r.avatar) : null,
                  child: r.avatar.isEmpty ? Text(r.name.isNotEmpty ? r.name[0] : '?', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18)) : null,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(r.name, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                      Text('${r.customId.isNotEmpty ? '#${r.customId} • ' : ''}${r.team} • ${r.roleLabel}',
                          style: TextStyle(fontSize: 12, color: isDark ? AppColors.textMuted : AppColors.textDarkMuted)),
                    ],
                  ),
                ),
                IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
              ],
            ),
            const SizedBox(height: 16),
            const Divider(),
            const SizedBox(height: 12),
            _buildDetailRow(isDark, 'Date', attProv.selectedDate),
            _buildDetailRow(isDark, 'Attendance Status', r.statusLabel),
            _buildDetailRow(isDark, 'Weekly Scheduled Off', r.weeklyDayOff),
            _buildDetailRow(isDark, 'Daily Standup Report', r.hasReport ? 'Submitted (${r.submissionTime ?? 'Done'})' : 'Pending (Due 9:15 AM)'),
            _buildDetailRow(isDark, 'Daily Standup Report', r.hasReport ? 'Submitted (${r.submissionTime ?? 'Done'})' : 'Pending (Due 6:15 PM)'),
            if (r.notes.isNotEmpty) _buildDetailRow(isDark, 'Notes / Handover', r.notes),
            const SizedBox(height: 16),
            if (isLeaderOrAdmin) ...[
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      icon: const Icon(Icons.event_repeat_rounded, size: 16),
                      label: const Text('Change Day-Off', style: TextStyle(fontSize: 12)),
                      onPressed: () {
                        Navigator.pop(ctx);
                        _showChangeDayOffDialog(context, r, attProv);
                      },
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: ElevatedButton.icon(
                      icon: const Icon(Icons.edit_calendar_rounded, size: 16),
                      label: const Text('Override Status', style: TextStyle(fontSize: 12)),
                      onPressed: () {
                        Navigator.pop(ctx);
                        _showOverrideStatusDialog(context, r, attProv);
                      },
                    ),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow(bool isDark, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 140,
            child: Text(label, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: isDark ? AppColors.textMuted : AppColors.textDarkMuted)),
          ),
          Expanded(
            child: Text(value, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }

  void _showChangeDayOffDialog(BuildContext context, AttendanceRosterItem r, AttendanceProvider attProv) {
    final days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Change Day-Off for ${r.name}', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: days.map((day) {
            final isCurrent = r.weeklyDayOff.toLowerCase() == day.toLowerCase();
            return ListTile(
              title: Text(day),
              trailing: isCurrent ? const Icon(Icons.check, color: AppColors.primaryBlue) : null,
              onTap: () async {
                Navigator.pop(ctx);
                final success = await attProv.updateDayOff(userId: r.userId, dayOff: day);
                if (!context.mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(success ? 'Weekly day off updated to $day!' : 'Failed to update day off.'),
                    backgroundColor: success ? AppColors.success : AppColors.danger,
                  ),
                );
              },
            );
          }).toList(),
        ),
      ),
    );
  }

  void _showOverrideStatusDialog(BuildContext context, AttendanceRosterItem r, AttendanceProvider attProv) {
    final statuses = [
      {'key': 'present', 'label': 'Present'},
      {'key': 'working', 'label': 'Working'},
      {'key': 'home_office', 'label': 'Home Office'},
      {'key': 'on_leave', 'label': 'On Leave'},
      {'key': 'day_off', 'label': 'Day Off'},
      {'key': 'late', 'label': 'Late (Past 9:15 AM)'},
      {'key': 'absent', 'label': 'Absent'},
      {'key': 'auto', 'label': 'Reset to Automatic (Clear Override)'},
    ];

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Override Attendance: ${r.name}', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: statuses.map((st) {
            return ListTile(
              title: Text(st['label']!),
              onTap: () async {
                Navigator.pop(ctx);
                final success = await attProv.markAttendance(
                  userId: r.userId,
                  status: st['key']!,
                );
                if (!context.mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(success ? 'Status updated to ${st['label']}!' : 'Failed to update status.'),
                    backgroundColor: success ? AppColors.success : AppColors.danger,
                  ),
                );
              },
            );
          }).toList(),
        ),
      ),
    );
  }

  void _showAdjustQuotaSheet(BuildContext context, AttendanceProvider attProv, Map<String, dynamic> m) {
    final uid = int.tryParse(m['user_id']?.toString() ?? '0') ?? 0;
    if (uid <= 0) return;

    final name = m['member_name']?.toString() ?? 'Specialist';
    final quotaCtrl = TextEditingController(text: (m['annual_quota'] ?? 28).toString());
    final adjCtrl = TextEditingController(text: (m['adjustment_days'] ?? 0).toString());
    final noteCtrl = TextEditingController(text: (m['quota_note'] ?? '').toString());
    String dayOff = (m['weekly_day_off']?.toString() ?? 'Sunday').trim();
    const daysOfWeek = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    if (!daysOfWeek.contains(dayOff)) dayOff = 'Sunday';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) {
          return Padding(
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(ctx).viewInsets.bottom,
              left: 20,
              right: 20,
              top: 20,
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          'Leave Quota & Day-Off: $name',
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Annual Leave Quota (Days)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: quotaCtrl,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: const InputDecoration(border: OutlineInputBorder()),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Balance Adjustment (+/-)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: adjCtrl,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: true),
                              decoration: const InputDecoration(
                                hintText: 'e.g. +2 or -1.5',
                                border: OutlineInputBorder(),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Text('Weekly Scheduled Day-Off', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  DropdownButtonFormField<String>(
                    value: dayOff,
                    items: daysOfWeek.map((d) => DropdownMenuItem(value: d, child: Text(d))).toList(),
                    onChanged: (v) {
                      if (v != null) setSheetState(() => dayOff = v);
                    },
                    decoration: const InputDecoration(border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  const Text('Adjustment Note', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: noteCtrl,
                    decoration: const InputDecoration(
                      hintText: 'Carry-over, bonus credit, deduction note...',
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 18),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () async {
                        final annual = double.tryParse(quotaCtrl.text.trim()) ?? 28.0;
                        final adj = double.tryParse(adjCtrl.text.trim()) ?? 0.0;
                        Navigator.pop(ctx);
                        final ok = await attProv.setMemberLeaveQuota(uid, {
                          'annual_quota': annual,
                          'adjustment_days': adj,
                          'weekly_day_off': dayOff,
                          'quota_note': noteCtrl.text.trim(),
                        });
                        if (!context.mounted) return;
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(ok ? 'Quota & weekly day-off updated for $name!' : (attProv.errorMessage ?? 'Update failed.')),
                            backgroundColor: ok ? AppColors.success : AppColors.danger,
                          ),
                        );
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primaryBlue,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      child: const Text('Save Quota & Day-Off', style: TextStyle(fontWeight: FontWeight.w800)),
                    ),
                  ),
                  const SizedBox(height: 20),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _showApplyLeaveSheet(BuildContext context, AttendanceProvider attProv, {int? preselectedUserId}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (_) => _ApplyLeaveSheet(attProv: attProv, preselectedUserId: preselectedUserId),
    );
  }
}

class _ApplyLeaveSheet extends StatefulWidget {
  final AttendanceProvider attProv;
  final int? preselectedUserId;
  const _ApplyLeaveSheet({required this.attProv, this.preselectedUserId});

  @override
  State<_ApplyLeaveSheet> createState() => _ApplyLeaveSheetState();
}

class _ApplyLeaveSheetState extends State<_ApplyLeaveSheet> {
  int? _selectedUserId;
  String _requestType = 'leave';
  String _leaveCategory = 'casual_leave';
  String _statusOverride = 'approved';
  DateTime _startDate = DateTime.now();
  DateTime _endDate = DateTime.now();
  DateTime _switchFrom = DateTime.now();
  DateTime _switchTo = DateTime.now().add(const Duration(days: 1));
  final _daysCtrl = TextEditingController();
  final _managerFeedbackCtrl = TextEditingController();
  final _reasonCtrl = TextEditingController();
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final membersProv = Provider.of<TeamMembersProvider>(context, listen: false);
      setState(() {
        _selectedUserId ??= widget.preselectedUserId ?? auth.userId;
      });
      if ((auth.isOperationsManager || auth.isLeader) && membersProv.members.isEmpty && !membersProv.isLoading) {
        membersProv.fetchMembers();
      }
    });
  }

  @override
  void dispose() {
    _daysCtrl.dispose();
    _managerFeedbackCtrl.dispose();
    _reasonCtrl.dispose();
    super.dispose();
  }

  void _submit() async {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final rawReason = _reasonCtrl.text.trim();
    if (rawReason.isEmpty && !auth.isOperationsManager) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter a reason for your request.')),
      );
      return;
    }
    final reason = rawReason.isNotEmpty ? rawReason : 'Added / Adjusted by Super Admin';

    setState(() => _isSubmitting = true);

    final isHalf = _leaveCategory.startsWith('half_day');
    final payload = <String, dynamic>{
      'request_type': _requestType,
      'leave_type': _leaveCategory,
      'start_date': DateFormat('yyyy-MM-dd').format(_startDate),
      'end_date': DateFormat('yyyy-MM-dd').format(isHalf ? _startDate : _endDate),
      'is_half_day': isHalf ? 1 : 0,
      'switch_from_date': DateFormat('yyyy-MM-dd').format(_switchFrom),
      'switch_to_date': DateFormat('yyyy-MM-dd').format(_switchTo),
      'reason': reason,
    };

    if ((auth.isOperationsManager || auth.isLeader) && _selectedUserId != null && _selectedUserId! > 0) {
      payload['user_id'] = _selectedUserId;
    }
    if (auth.isOperationsManager) {
      payload['status'] = _statusOverride;
      final parsedDays = double.tryParse(_daysCtrl.text.trim());
      if (_requestType == 'leave' && parsedDays != null && parsedDays > 0) {
        payload['days_count'] = parsedDays;
      }
      if (_managerFeedbackCtrl.text.trim().isNotEmpty) {
        payload['manager_feedback'] = _managerFeedbackCtrl.text.trim();
      }
    }

    final success = await widget.attProv.submitLeave(payload);
    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (success) {
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(auth.isOperationsManager ? 'Leave / adjustment saved!' : 'Leave application submitted to leadership!'),
          backgroundColor: AppColors.success,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(widget.attProv.errorMessage ?? 'Submission failed.'),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final auth = Provider.of<AuthProvider>(context);
    final membersProv = Provider.of<TeamMembersProvider>(context);
    final canPickMember = !auth.isViewOnly && (auth.isOperationsManager || auth.isLeader);
    final List<Specialist> selectableMembers = auth.isOperationsManager
        ? membersProv.members
        : membersProv.members
            .where((m) => m.team.toLowerCase().replaceAll(' ', '') == auth.userTeam.toLowerCase().replaceAll(' ', ''))
            .toList();
    final effectiveUserId = _selectedUserId ?? widget.preselectedUserId ?? auth.userId;

    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: isDark ? AppColors.darkCard : AppColors.lightCard,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Apply for Leave / Adjust Day-Off',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              if (canPickMember) ...[
                const SizedBox(height: 10),
                const Text('Member', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                const SizedBox(height: 6),
                if (membersProv.isLoading && selectableMembers.isEmpty)
                  const LinearProgressIndicator(minHeight: 2)
                else if (selectableMembers.isNotEmpty)
                  DropdownButtonFormField<int>(
                    value: selectableMembers.any((m) => m.id == effectiveUserId) ? effectiveUserId : null,
                    hint: const Text('Select Team Member'),
                    items: selectableMembers.map((m) => DropdownMenuItem<int>(
                      value: m.id,
                      child: Text('${m.name} (${m.team})'),
                    )).toList(),
                    onChanged: (val) => setState(() => _selectedUserId = val),
                    decoration: const InputDecoration(border: OutlineInputBorder()),
                  ),
              ],
              const SizedBox(height: 14),

              // Request Type
              const Text('Request Type', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
              const SizedBox(height: 6),
              DropdownButtonFormField<String>(
                value: _requestType,
                items: const [
                  DropdownMenuItem(value: 'leave', child: Text('🏖️ Leave Notice (Casual, Sick, etc.)')),
                  DropdownMenuItem(value: 'day_off_switch', child: Text('🔄 Switch / Adjust Weekly Day-Off')),
                ],
                onChanged: (v) => setState(() => _requestType = v!),
                decoration: const InputDecoration(border: OutlineInputBorder()),
              ),
              const SizedBox(height: 14),

              if (_requestType == 'leave') ...[
                const Text('Leave Category', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: _leaveCategory,
                  items: const [
                    DropdownMenuItem(value: 'casual_leave', child: Text('Casual Leave (Full Day)')),
                    DropdownMenuItem(value: 'half_day_morning', child: Text('🌅 Half Day Leave (Morning / 1st Half - 0.5d)')),
                    DropdownMenuItem(value: 'half_day_evening', child: Text('🌆 Half Day Leave (Evening / 2nd Half - 0.5d)')),
                    DropdownMenuItem(value: 'sick_leave', child: Text('Sick Leave / Medical')),
                    DropdownMenuItem(value: 'vacation', child: Text('Planned Vacation')),
                    DropdownMenuItem(value: 'emergency', child: Text('Emergency / Family')),
                  ],
                  onChanged: (v) => setState(() => _leaveCategory = v!),
                  decoration: const InputDecoration(border: OutlineInputBorder()),
                ),
                const SizedBox(height: 14),

                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(_leaveCategory.startsWith('half_day') ? 'Leave Date' : 'Start Date', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                          const SizedBox(height: 6),
                          InkWell(
                            onTap: () async {
                              final picked = await showDatePicker(
                                context: context,
                                initialDate: _startDate,
                                firstDate: DateTime(2025, 1, 1),
                                lastDate: DateTime(2030, 12, 31),
                              );
                              if (picked != null) {
                                setState(() {
                                  _startDate = picked;
                                  if (_endDate.isBefore(picked)) _endDate = picked;
                                });
                              }
                            },
                            child: InputDecorator(
                              decoration: const InputDecoration(border: OutlineInputBorder()),
                              child: Text(DateFormat('yyyy-MM-dd').format(_startDate)),
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (!_leaveCategory.startsWith('half_day')) ...[
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('End Date', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            InkWell(
                              onTap: () async {
                                final picked = await showDatePicker(
                                  context: context,
                                  initialDate: _endDate,
                                  firstDate: _startDate,
                                  lastDate: DateTime(2030, 12, 31),
                                );
                                if (picked != null) setState(() => _endDate = picked);
                              },
                              child: InputDecorator(
                                decoration: const InputDecoration(border: OutlineInputBorder()),
                                child: Text(DateFormat('yyyy-MM-dd').format(_endDate)),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
              ] else ...[
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Original Off-Day', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                          const SizedBox(height: 6),
                          InkWell(
                            onTap: () async {
                              final picked = await showDatePicker(
                                context: context,
                                initialDate: _switchFrom,
                                firstDate: DateTime(2025, 1, 1),
                                lastDate: DateTime(2030, 12, 31),
                              );
                              if (picked != null) setState(() => _switchFrom = picked);
                            },
                            child: InputDecorator(
                              decoration: const InputDecoration(border: OutlineInputBorder()),
                              child: Text(DateFormat('yyyy-MM-dd').format(_switchFrom)),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Desired Switched Day', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                          const SizedBox(height: 6),
                          InkWell(
                            onTap: () async {
                              final picked = await showDatePicker(
                                context: context,
                                initialDate: _switchTo,
                                firstDate: DateTime(2025, 1, 1),
                                lastDate: DateTime(2030, 12, 31),
                              );
                              if (picked != null) setState(() => _switchTo = picked);
                            },
                            child: InputDecorator(
                              decoration: const InputDecoration(border: OutlineInputBorder()),
                              child: Text(DateFormat('yyyy-MM-dd').format(_switchTo)),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
              if (auth.isOperationsManager) ...[
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Initial Status (Admin)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                          const SizedBox(height: 6),
                          DropdownButtonFormField<String>(
                            value: _statusOverride,
                            items: const [
                              DropdownMenuItem(value: 'approved', child: Text('Approve Immediately')),
                              DropdownMenuItem(value: 'leader_approved', child: Text('Leader Verified')),
                              DropdownMenuItem(value: 'pending', child: Text('Pending Review')),
                              DropdownMenuItem(value: 'rejected', child: Text('Rejected')),
                            ],
                            onChanged: (val) {
                              if (val != null) setState(() => _statusOverride = val);
                            },
                            decoration: const InputDecoration(border: OutlineInputBorder()),
                          ),
                        ],
                      ),
                    ),
                    if (_requestType == 'leave') ...[
                      const SizedBox(width: 10),
                      SizedBox(
                        width: 110,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Custom Days', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: _daysCtrl,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: const InputDecoration(
                                hintText: 'Auto',
                                border: OutlineInputBorder(),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 12),
                const Text('Manager Note (Optional)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                const SizedBox(height: 6),
                TextField(
                  controller: _managerFeedbackCtrl,
                  decoration: const InputDecoration(
                    hintText: 'Super Admin adjustment note...',
                    border: OutlineInputBorder(),
                  ),
                ),
              ],
              const SizedBox(height: 14),

              // Reason
              Text(auth.isOperationsManager ? 'Reason / Handover Notes (Optional)' : 'Reason / Handover Notes *', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
              const SizedBox(height: 6),
              TextField(
                controller: _reasonCtrl,
                maxLines: 3,
                decoration: const InputDecoration(
                  hintText: 'Enter reason or task handover notes...',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 18),

              SizedBox(
                width: double.infinity,
                height: 46,
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primaryBlue,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  child: _isSubmitting
                      ? const CircularProgressIndicator(color: Colors.white)
                      : Text(auth.isOperationsManager ? 'Save Leave / Adjustment' : 'Submit Application', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

