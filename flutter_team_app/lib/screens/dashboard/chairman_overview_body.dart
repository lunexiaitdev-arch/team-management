import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import '../../models/leaderboard.dart';
import '../../models/monthly_report.dart';
import '../../models/project.dart';
import '../../providers/auth_provider.dart';
import '../../providers/leaderboard_provider.dart';
import '../../providers/monthly_reports_provider.dart';
import '../../providers/notices_provider.dart';
import '../../providers/projects_provider.dart';
import '../../providers/tasks_provider.dart';
import '../../providers/time_tracker_provider.dart';
import '../../theme/app_colors.dart';
import '../../widgets/countdown_pill.dart';
import '../../widgets/status_badge.dart';
import '../../widgets/team_badge.dart';
import '../daily_reports/log_standup_screen.dart';
import '../notices/create_notice_sheet.dart';
import '../notices/notices_screen.dart';
import '../projects/add_project_sheet.dart';
import '../projects/project_detail_sheet.dart';

/// Dedicated, cleanly organized Current-Month Executive Dashboard for Chairman / Executive oversight.
/// All metrics stay 100% synchronized with the Website (All Projects, Monthly Sheet & Leaderboard).
class ChairmanOverviewBody extends StatelessWidget {
  final ValueChanged<int>? onNavigateToTab;

  const ChairmanOverviewBody({super.key, this.onNavigateToTab});

  String _formatMonthShort(String ym) {
    if (ym == 'all') return 'All Months';
    if (ym == 'carry_forward') return 'Carry Fwd';
    try {
      final dt = DateFormat('yyyy-MM').parse(ym);
      return DateFormat('MMM yyyy').format(dt);
    } catch (_) {
      return ym;
    }
  }

  String _formatMonthFull(String ym) {
    if (ym == 'all') return 'All Time';
    try {
      final dt = DateFormat('yyyy-MM').parse(ym);
      return DateFormat('MMMM yyyy').format(dt);
    } catch (_) {
      return ym;
    }
  }

  /// Current Bangladesh Standard Time (UTC+6) display string
  String _bstNowLabel() {
    final bst = DateTime.now().toUtc().add(const Duration(hours: 6));
    return '${DateFormat('EEE, d MMM yyyy').format(bst)} · ${DateFormat('h:mm a').format(bst)} BST';
  }

  void _showMonthPicker(
    BuildContext context,
    ProjectsProvider projectsProv,
    MonthlyReportsProvider monthlyProv,
    LeaderboardProvider lbProv,
    bool isDark,
  ) {
    final months = MonthlyReportsProvider.availableReportingMonths;
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
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
              Text(
                'Select Reporting Month',
                style: TextStyle(
                  color: isDark ? AppColors.textLight : AppColors.textDark,
                  fontSize: 17,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Syncs Overview, Orders, Monthly Sheet & Leaderboard together.',
                style: TextStyle(
                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                  fontSize: 12,
                ),
              ),
              const SizedBox(height: 14),
              ...months.map((m) {
                final key = m['key']!;
                final label = m['label']!;
                final isSelected = monthlyProv.selectedMonth == key;
                return ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  tileColor: isSelected
                      ? AppColors.primaryBlue.withValues(alpha: 0.14)
                      : Colors.transparent,
                  leading: Icon(
                    isSelected ? Icons.radio_button_checked_rounded : Icons.calendar_month_rounded,
                    color: isSelected
                        ? AppColors.primaryBlueLight
                        : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                    size: 20,
                  ),
                  title: Text(
                    label,
                    style: TextStyle(
                      color: isDark ? AppColors.textLight : AppColors.textDark,
                      fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  onTap: () {
                    Navigator.of(ctx).pop();
                    projectsProv.setSelectedMonth(key);
                    monthlyProv.setSelectedMonth(key);
                    lbProv.setSelectedPeriod(key, 'monthly');
                  },
                );
              }),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final auth = Provider.of<AuthProvider>(context);
    final projectsProv = Provider.of<ProjectsProvider>(context);
    final monthlyProv = Provider.of<MonthlyReportsProvider>(context);
    final lbProv = Provider.of<LeaderboardProvider>(context);
    final tasksProv = Provider.of<TasksProvider>(context);
    final noticesProv = Provider.of<NoticesProvider>(context);
    final ttProv = Provider.of<TimeTrackerProvider>(context);

    final user = auth.currentUser;
    final stats = projectsProv.stats;
    final monthly = monthlyProv.monthlyData;
    final rankings = lbProv.leaderboardData?.rankings ?? const <LeaderboardItem>[];

    final moneyExact = NumberFormat.currency(symbol: '\$', decimalDigits: 2);
    final moneyShort = NumberFormat.currency(symbol: '\$', decimalDigits: 0);

    final activeMonthKey = monthlyProv.selectedMonth;
    final monthFullLabel = _formatMonthFull(activeMonthKey);
    final monthShortLabel = _formatMonthShort(activeMonthKey);

    // Unified single-source-of-truth current-month numbers (exact Website parity for every role!)
    // For Agency-wide roles (Chairman & Manager), cross-check MonthlyReport totals; for Leaders/Members use scoped /stats.
    final bool isAgencyWide = auth.isExecutive || auth.isOperationsManager;
    final double netDelivered = (isAgencyWide && monthly != null && monthly.totalNet > 0)
        ? monthly.totalNet
        : (stats?.totalNetRevenue ?? projectsProv.totalDeliveredNet);
    final double grossDelivered = (isAgencyWide && monthly != null && monthly.totalGross > 0)
        ? monthly.totalGross
        : (netDelivered > 0 ? netDelivered / 0.8 : 0.0);
    final double tipsNet = (isAgencyWide && monthly != null && monthly.totalTips > 0)
        ? monthly.totalTips
        : (stats?.totalTips ?? 0.0);
    final double submittedVal = (isAgencyWide && monthly != null && monthly.totalSubmittedVal > 0)
        ? monthly.totalSubmittedVal
        : (stats?.deliverySubmittedValue ?? projectsProv.totalSubmittedValue);
    final double wipVal = stats?.activeWipValue ?? (isAgencyWide ? (monthly?.totalWipVal ?? projectsProv.totalWipValue) : projectsProv.totalWipValue);
    final double revVal = stats?.activeRevisionValue ?? projectsProv.totalRevisionValue;
    final double cancelVal = stats?.cancelledNet ?? (isAgencyWide ? (monthly?.totalCancelledVal ?? projectsProv.totalCancelledNet) : projectsProv.totalCancelledNet);
    final double totalWorkload = netDelivered + submittedVal + wipVal + revVal + cancelVal;

    final int completedCount = stats?.totalCompletedCount ?? (isAgencyWide ? (monthly?.totalDeliveryCount ?? projectsProv.countDeliveredAll) : projectsProv.countDeliveredAll);
    final int submittedCount = stats?.deliverySubmitted ?? projectsProv.countSubmitted;
    final int wipCount = stats?.wip ?? projectsProv.countWip;
    final int revCount = stats?.revision ?? projectsProv.countRevision;
    final int activeCount = stats?.activeOrdersCount ?? projectsProv.countActive;
    final int cancelCount = stats?.cancelled ?? (isAgencyWide ? (monthly?.totalCancelledCount ?? projectsProv.countCancelled) : projectsProv.countCancelled);
    final int totalOrders = stats?.total ?? projectsProv.countAll;
    final int repeatCount = stats?.repeatOrdersCount ?? (isAgencyWide ? (monthly?.totalRepeatCount ?? projectsProv.countRepeat) : projectsProv.countRepeat);
    final double repeatVal = stats?.repeatNetRevenue ?? (isAgencyWide ? (monthly?.totalRepeatVal ?? projectsProv.totalRepeatNet) : projectsProv.totalRepeatNet);

    TeamBreakdownItem? userTeamBreakdown;
    if (!isAgencyWide && user?.team.isNotEmpty == true && monthly != null) {
      for (final t in monthly.teamBreakdown) {
        if (t.teamName.toLowerCase() == user!.team.toLowerCase()) {
          userTeamBreakdown = t;
          break;
        }
      }
    }

    final int newAssignedCount = isAgencyWide
        ? (monthly?.totalNewAssignedCount ?? (stats?.newSalesCount ?? 0))
        : (userTeamBreakdown?.newAssignedCount ?? (stats?.newSalesCount ?? 0));
    final double newAssignedVal = isAgencyWide
        ? (monthly?.totalNewAssignedVal ?? (stats?.newSalesVolume ?? 0.0))
        : (userTeamBreakdown?.newAssignedVal ?? (stats?.newSalesVolume ?? 0.0));
    final int carryCount = isAgencyWide
        ? (monthly?.totalCarryForwardCount ?? (stats?.carryForwardCount ?? 0))
        : (userTeamBreakdown?.carryForwardCount ?? (stats?.carryForwardCount ?? 0));
    final double carryVal = isAgencyWide
        ? (monthly?.totalCarryForwardVal ?? (stats?.carryForwardVolume ?? 0.0))
        : (userTeamBreakdown?.carryForwardVal ?? (stats?.carryForwardVolume ?? 0.0));
    final int totalAssignedCount = isAgencyWide
        ? (monthly != null && monthly.totalAssignedCount > 0 ? monthly.totalAssignedCount : (newAssignedCount + carryCount))
        : (userTeamBreakdown != null && userTeamBreakdown.totalAssignedCount > 0 ? userTeamBreakdown.totalAssignedCount : (newAssignedCount + carryCount));
    final double totalAssignedVal = isAgencyWide
        ? (monthly != null && monthly.totalAssignedVal > 0 ? monthly.totalAssignedVal : (newAssignedVal + carryVal + (monthly?.totalTips ?? 0.0)))
        : (userTeamBreakdown != null && userTeamBreakdown.totalAssignedVal > 0 ? userTeamBreakdown.totalAssignedVal : (newAssignedVal + carryVal));

    final double deliveryPct = totalWorkload > 0 ? (netDelivered / totalWorkload).clamp(0.0, 1.0) : 0.0;

    // Active production squads only (exclude 0-member/0-order archived teams)
    final activeSquads = (monthly?.teamBreakdown ?? const <TeamBreakdownItem>[])
        .where((t) {
          final hasData = t.memberCount > 0 || t.totalAssignedCount > 0 || t.deliveryVal > 0 || t.wipVal > 0;
          if (!hasData) return false;
          if (!isAgencyWide && (user?.team.isNotEmpty ?? false)) {
            return t.teamName.toLowerCase() == user!.team.toLowerCase();
          }
          return true;
        })
        .toList();

    final repeatLeaders = (monthly?.rows ?? const <MonthlyReportRow>[])
        .where((r) {
          if (r.repeatCount <= 0 && r.repeatVal <= 0) return false;
          if (!isAgencyWide && (user?.team.isNotEmpty ?? false)) {
            return r.team.toLowerCase() == user!.team.toLowerCase();
          }
          return true;
        })
        .toList()
      ..sort((a, b) {
        final cmp = b.repeatVal.compareTo(a.repeatVal);
        return cmp != 0 ? cmp : b.repeatCount.compareTo(a.repeatCount);
      });

    // Sort truly open active orders (WIP/NRA/Revision — exclude submitted) by exact backend BST daysRemaining
    final urgentOrders = projectsProv.projects
        .where((p) {
          final st = p.status.toLowerCase();
          return st == 'wip' || st == 'nra' || st == 'revision';
        })
        .toList()
      ..sort((a, b) {
        final cmp = a.daysRemaining.compareTo(b.daysRemaining);
        return cmp != 0 ? cmp : a.deliveryDate.compareTo(b.deliveryDate);
      });

    return RefreshIndicator(
      onRefresh: () async {
        await Future.wait([
          projectsProv.fetchDashboardData(),
          monthlyProv.fetchMonthlyReport(),
          lbProv.fetchLeaderboard(),
          tasksProv.fetchTasks(silent: true),
          noticesProv.fetchNotices(),
          ttProv.fetchStats(),
        ]);
      },
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 28),
        children: [
          // =================================================================
          // 1. CLEAN UNIFIED HEADER (FOR CHAIRMAN, MANAGER, LEADERS & MEMBERS)
          // =================================================================
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(18),
              gradient: LinearGradient(
                colors: auth.isExecutive
                    ? const [Color(0xFF0F172A), Color(0xFF1E1B4B), Color(0xFF2E1065)]
                    : const [Color(0xFF0F172A), Color(0xFF172554), Color(0xFF1E3A8A)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              border: Border.all(
                color: (auth.isExecutive ? const Color(0xFF8B5CF6) : AppColors.primaryBlueLight)
                    .withValues(alpha: 0.42),
                width: 1.2,
              ),
              boxShadow: [
                BoxShadow(
                  color: (auth.isExecutive ? const Color(0xFF4C1D95) : AppColors.primaryBlue)
                      .withValues(alpha: 0.22),
                  blurRadius: 16,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    if (auth.isExecutive)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF59E0B).withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(
                            color: const Color(0xFFFBBF24).withValues(alpha: 0.45),
                          ),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.workspace_premium_rounded, size: 12, color: Color(0xFFFBBF24)),
                            const SizedBox(width: 4),
                            Text(
                              (user?.professionalTitle.isNotEmpty ?? false)
                                  ? user!.professionalTitle.toUpperCase()
                                  : 'EXECUTIVE',
                              style: const TextStyle(
                                color: Color(0xFFFDE68A),
                                fontSize: 9.5,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 0.7,
                              ),
                            ),
                          ],
                        ),
                      )
                    else if (auth.isOperationsManager)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: AppColors.cyanAccent.withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: AppColors.cyanAccent.withValues(alpha: 0.45)),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.shield_rounded, size: 11, color: AppColors.cyanAccent),
                            SizedBox(width: 4),
                            Text(
                              'SUPER ADMIN · MANAGER',
                              style: TextStyle(
                                color: AppColors.cyanAccent,
                                fontSize: 9.5,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 0.6,
                              ),
                            ),
                          ],
                        ),
                      )
                    else
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          if (user?.team.isNotEmpty ?? false) ...[
                            TeamBadge(team: user!.team, isSmall: true),
                            const SizedBox(width: 6),
                          ],
                          Text(
                            auth.isSquadLeader ? 'SQUAD LEADER' : 'SPECIALIST',
                            style: const TextStyle(
                              color: Colors.white70,
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 0.6,
                            ),
                          ),
                        ],
                      ),
                    const Spacer(),
                    // Interactive Reporting Month Selector Pill
                    InkWell(
                      onTap: () => _showMonthPicker(context, projectsProv, monthlyProv, lbProv, isDark),
                      borderRadius: BorderRadius.circular(20),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.24)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.calendar_month_rounded, size: 13, color: Colors.white),
                            const SizedBox(width: 5),
                            Text(
                              monthShortLabel,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 11.5,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(width: 2),
                            const Icon(Icons.keyboard_arrow_down_rounded, size: 15, color: Colors.white70),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  'Welcome, ${user?.name ?? 'Team Member'}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.3,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Container(
                      width: 6,
                      height: 6,
                      decoration: const BoxDecoration(
                        color: AppColors.success,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        _bstNowLabel(),
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 11.5,
                          fontWeight: FontWeight.w500,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Live Active Work Timer Strip (for non-executive users currently tracking)
          if (!auth.isViewOnly && ttProv.activeSession != null) ...[
            const SizedBox(height: 10),
            InkWell(
              onTap: () => onNavigateToTab?.call(13),
              borderRadius: BorderRadius.circular(12),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                decoration: BoxDecoration(
                  color: AppColors.success.withValues(alpha: isDark ? 0.14 : 0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.success.withValues(alpha: 0.4)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.timer_rounded, size: 17, color: AppColors.success),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Live Tracking: ${ttProv.activeSession!.orderId}',
                        style: TextStyle(
                          color: isDark ? AppColors.textLight : AppColors.textDark,
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    Text(
                      ttProv.formattedClock,
                      style: const TextStyle(
                        color: AppColors.success,
                        fontSize: 13.5,
                        fontWeight: FontWeight.w900,
                        fontFeatures: [FontFeature.tabularFigures()],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],

          // Pinned Notice Strip (compact, if any)
          if (noticesProv.pinnedNotices.isNotEmpty) ...[
            const SizedBox(height: 10),
            InkWell(
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const NoticesScreen()),
              ),
              borderRadius: BorderRadius.circular(12),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                decoration: BoxDecoration(
                  color: AppColors.warning.withValues(alpha: isDark ? 0.12 : 0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.warning.withValues(alpha: 0.35)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.campaign_rounded, size: 16, color: AppColors.warning),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        noticesProv.pinnedNotices.first.title,
                        style: TextStyle(
                          color: isDark ? AppColors.textLight : AppColors.textDark,
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const Icon(Icons.chevron_right_rounded, size: 16, color: AppColors.warning),
                  ],
                ),
              ),
            ),
          ],

          const SizedBox(height: 16),

          // =================================================================
          // 2. CURRENT MONTH PROGRESS OVERVIEW (CENTERPIECE CARD)
          // =================================================================
          _buildSectionTitle(
            title: '${monthFullLabel.toUpperCase()} · PROGRESS OVERVIEW',
            actionLabel: 'Full Sheet →',
            onAction: () => onNavigateToTab?.call(4),
            isDark: isDark,
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDark ? AppColors.darkCard : AppColors.lightCard,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Top Row: Net Delivery Hero + Order Count Pill
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'NET DELIVERED REVENUE',
                            style: TextStyle(
                              color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 0.7,
                            ),
                          ),
                          const SizedBox(height: 4),
                          FittedBox(
                            fit: BoxFit.scaleDown,
                            alignment: Alignment.centerLeft,
                            child: Text(
                              moneyExact.format(netDelivered),
                              style: const TextStyle(
                                color: AppColors.success,
                                fontSize: 28,
                                fontWeight: FontWeight.w900,
                                letterSpacing: -0.6,
                                fontFeatures: [FontFeature.tabularFigures()],
                              ),
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Gross ${moneyExact.format(grossDelivered)}${tipsNet > 0 ? ' • incl. ${moneyExact.format(tipsNet)} tips' : ''}',
                            style: TextStyle(
                              color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: AppColors.success.withValues(alpha: 0.14),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: AppColors.success.withValues(alpha: 0.3)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            '${(deliveryPct * 100).toStringAsFixed(1)}%',
                            style: const TextStyle(
                              color: AppColors.success,
                              fontSize: 15,
                              fontWeight: FontWeight.w900,
                              fontFeatures: [FontFeature.tabularFigures()],
                            ),
                          ),
                          Text(
                            '$completedCount / $totalOrders orders',
                            style: TextStyle(
                              color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                              fontSize: 9.5,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),

                // Multi-Segment Stacked Monthly Progress Bar
                ClipRRect(
                  borderRadius: BorderRadius.circular(6),
                  child: SizedBox(
                    height: 9,
                    child: totalWorkload > 0
                        ? Row(
                            children: [
                              if (netDelivered > 0)
                                Expanded(
                                  flex: (netDelivered * 10).round().clamp(1, 1000000),
                                  child: Container(color: AppColors.success),
                                ),
                              if (submittedVal > 0)
                                Expanded(
                                  flex: (submittedVal * 10).round().clamp(1, 1000000),
                                  child: Container(color: const Color(0xFF06B6D4)),
                                ),
                              if (wipVal > 0)
                                Expanded(
                                  flex: (wipVal * 10).round().clamp(1, 1000000),
                                  child: Container(color: AppColors.warning),
                                ),
                              if (revVal > 0)
                                Expanded(
                                  flex: (revVal * 10).round().clamp(1, 1000000),
                                  child: Container(color: AppColors.purpleAccent),
                                ),
                              if (cancelVal > 0)
                                Expanded(
                                  flex: (cancelVal * 10).round().clamp(1, 1000000),
                                  child: Container(color: AppColors.danger),
                                ),
                            ],
                          )
                        : Container(
                            color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                          ),
                  ),
                ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Total Pipeline Volume: ${moneyShort.format(totalWorkload)}',
                      style: TextStyle(
                        color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    Text(
                      '$activeCount Active • $submittedCount Submitted',
                      style: TextStyle(
                        color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),

                const Divider(height: 22),

                // Clean Tappable Current-Month Pipeline Breakdown
                Row(
                  children: [
                    Expanded(
                      child: _buildMiniStatTile(
                        label: 'COMPLETED',
                        primary: '$completedCount Orders',
                        sub: '${moneyShort.format(netDelivered)} delivered',
                        color: AppColors.success,
                        isDark: isDark,
                        onTap: () {
                          projectsProv.setTeamFilter('all');
                          projectsProv.setStatusFilter('delivered_all');
                          onNavigateToTab?.call(1);
                        },
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildMiniStatTile(
                        label: 'DELIVERY SUBMITTED',
                        primary: moneyShort.format(submittedVal),
                        sub: '$submittedCount awaiting accept',
                        color: const Color(0xFF06B6D4),
                        isDark: isDark,
                        onTap: () {
                          projectsProv.setTeamFilter('all');
                          projectsProv.setStatusFilter('delivery_submitted');
                          onNavigateToTab?.call(1);
                        },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: _buildMiniStatTile(
                        label: 'ACTIVE WIP',
                        primary: moneyShort.format(wipVal),
                        sub: '$wipCount in production',
                        color: AppColors.warning,
                        isDark: isDark,
                        onTap: () {
                          projectsProv.setTeamFilter('all');
                          projectsProv.setStatusFilter('wip');
                          onNavigateToTab?.call(1);
                        },
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildMiniStatTile(
                        label: 'IN REVISION',
                        primary: moneyShort.format(revVal),
                        sub: '$revCount orders revising',
                        color: AppColors.purpleAccent,
                        isDark: isDark,
                        onTap: () {
                          projectsProv.setTeamFilter('all');
                          projectsProv.setStatusFilter('revision');
                          onNavigateToTab?.call(1);
                        },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: _buildMiniStatTile(
                        label: 'CANCELLED',
                        primary: '$cancelCount · ${moneyShort.format(cancelVal)}',
                        sub: 'Lost order value',
                        color: AppColors.danger,
                        isDark: isDark,
                        onTap: () {
                          projectsProv.setTeamFilter('all');
                          projectsProv.setStatusFilter('cancelled');
                          onNavigateToTab?.call(1);
                        },
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildMiniStatTile(
                        label: 'REPEAT CLIENTS',
                        primary: moneyShort.format(repeatVal),
                        sub: '$repeatCount repeat orders',
                        color: const Color(0xFFC084FC),
                        isDark: isDark,
                        onTap: () {
                          projectsProv.setTeamFilter('all');
                          projectsProv.setStatusFilter('repeat');
                          onNavigateToTab?.call(1);
                        },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),

                // Dedicated Total Assigned Workload Card (Total Workload + New Sales + Carry Forward + Tips)
                InkWell(
                  onTap: () {
                    projectsProv.setTeamFilter('all');
                    projectsProv.setStatusFilter('all');
                    onNavigateToTab?.call(1);
                  },
                  borderRadius: BorderRadius.circular(14),
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: isDark
                            ? [const Color(0xFF1E1B4B), const Color(0xFF312E81)]
                            : [const Color(0xFFEEF2FF), const Color(0xFFE0E7FF)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: const Color(0xFF6366F1).withValues(alpha: isDark ? 0.4 : 0.45),
                        width: 1.2,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF6366F1).withValues(alpha: isDark ? 0.18 : 0.08),
                          blurRadius: 8,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(5),
                              decoration: BoxDecoration(
                                color: const Color(0xFF6366F1).withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: const Icon(Icons.assignment_rounded, size: 16, color: Color(0xFF818CF8)),
                            ),
                            const SizedBox(width: 8),
                            const Expanded(
                              child: Text(
                                'TOTAL ASSIGNED WORKLOAD',
                                style: TextStyle(
                                  color: Color(0xFF818CF8),
                                  fontSize: 10.5,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 0.5,
                                ),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                              decoration: BoxDecoration(
                                color: const Color(0xFF6366F1).withValues(alpha: 0.22),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                '$totalAssignedCount Orders',
                                style: TextStyle(
                                  color: isDark ? const Color(0xFFA5B4FC) : const Color(0xFF4338CA),
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          crossAxisAlignment: CrossAxisAlignment.baseline,
                          textBaseline: TextBaseline.alphabetic,
                          children: [
                            Text(
                              moneyExact.format(totalAssignedVal),
                              style: TextStyle(
                                color: isDark ? Colors.white : const Color(0xFF1E1B4B),
                                fontSize: 22,
                                fontWeight: FontWeight.w900,
                                letterSpacing: -0.5,
                                fontFeatures: const [FontFeature.tabularFigures()],
                              ),
                            ),
                            Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(
                                  'View All',
                                  style: TextStyle(
                                    color: isDark ? const Color(0xFFA5B4FC) : const Color(0xFF4338CA),
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(width: 2),
                                Icon(
                                  Icons.arrow_forward_ios_rounded,
                                  size: 10,
                                  color: isDark ? const Color(0xFFA5B4FC) : const Color(0xFF4338CA),
                                ),
                              ],
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'New: ${moneyShort.format(newAssignedVal)} ($newAssignedCount) • Carry: ${moneyShort.format(carryVal)} ($carryCount)${tipsNet > 0 ? ' • Tips: +${moneyShort.format(tipsNet)}' : ''}',
                          style: TextStyle(
                            color: isDark ? const Color(0xFFC7D2FE) : const Color(0xFF4B5563),
                            fontSize: 10.5,
                            fontWeight: FontWeight.w600,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 8),

                Row(
                  children: [
                    Expanded(
                      child: _buildMiniStatTile(
                        label: 'NEW SALES ASSIGNED',
                        primary: moneyShort.format(newAssignedVal),
                        sub: '$newAssignedCount new orders',
                        color: const Color(0xFFF97316),
                        isDark: isDark,
                        onTap: () {
                          projectsProv.setTeamFilter('all');
                          projectsProv.setStatusFilter('all');
                          onNavigateToTab?.call(1);
                        },
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildMiniStatTile(
                        label: 'CARRY FORWARD',
                        primary: moneyShort.format(carryVal),
                        sub: '$carryCount carry forward',
                        color: const Color(0xFFFACC15),
                        isDark: isDark,
                        onTap: () {
                          projectsProv.setTeamFilter('all');
                          projectsProv.setStatusFilter('carry_forward');
                          onNavigateToTab?.call(1);
                        },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                InkWell(
                  onTap: () {
                    projectsProv.setTeamFilter('all');
                    projectsProv.setStatusFilter('repeat');
                    onNavigateToTab?.call(1);
                  },
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    decoration: BoxDecoration(
                      color: const Color(0xFF06B6D4).withValues(alpha: isDark ? 0.12 : 0.08),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFF06B6D4).withValues(alpha: 0.32)),
                    ),
                    child: Row(
                      children: [
                        Container(
                          width: 30,
                          height: 30,
                          decoration: BoxDecoration(
                            color: const Color(0xFF06B6D4).withValues(alpha: 0.18),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Icon(Icons.repeat_rounded, size: 16, color: Color(0xFF06B6D4)),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'REPEAT CLIENT ORDERS',
                                style: TextStyle(
                                  color: Color(0xFF06B6D4),
                                  fontSize: 9.5,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 0.5,
                                ),
                              ),
                              const SizedBox(height: 1),
                              Text(
                                '$repeatCount Repeat Orders in $monthShortLabel',
                                style: TextStyle(
                                  color: isDark ? AppColors.textLight : AppColors.textDark,
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.w800,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          moneyExact.format(repeatVal),
                          style: const TextStyle(
                            color: Color(0xFF06B6D4),
                            fontSize: 15,
                            fontWeight: FontWeight.w900,
                            fontFeatures: [FontFeature.tabularFigures()],
                          ),
                        ),
                        const SizedBox(width: 2),
                        const Icon(Icons.chevron_right_rounded, size: 16, color: Color(0xFF06B6D4)),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 18),

          // =================================================================
          // 3. QUICK WORKSPACE HUB (ROLE-TAILORED 4x2 UNIFORM ICON GRID)
          // =================================================================
          _buildSectionTitle(
            title: auth.isExecutive ? 'EXECUTIVE MONITORING HUB' : 'QUICK COMMAND HUB',
            isDark: isDark,
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
            decoration: BoxDecoration(
              color: isDark ? AppColors.darkCard : AppColors.lightCard,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
              ),
            ),
            child: GridView.count(
              crossAxisCount: 4,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 12,
              crossAxisSpacing: 6,
              childAspectRatio: 0.92,
              children: auth.isExecutive
                  ? [
                      _buildHubTile(
                        icon: Icons.table_chart_rounded,
                        label: 'Monthly',
                        color: const Color(0xFF8B5CF6),
                        isDark: isDark,
                        onTap: () => onNavigateToTab?.call(4),
                      ),
                      _buildHubTile(
                        icon: Icons.layers_rounded,
                        label: 'Orders',
                        badge: activeCount > 0 ? '$activeCount' : null,
                        color: AppColors.primaryBlueLight,
                        isDark: isDark,
                        onTap: () {
                          projectsProv.setStatusFilter('all');
                          onNavigateToTab?.call(1);
                        },
                      ),
                      _buildHubTile(
                        icon: Icons.task_alt_rounded,
                        label: 'Tasks',
                        badge: tasksProv.countPending > 0 ? '${tasksProv.countPending}' : null,
                        badgeColor: tasksProv.countOverdue > 0 ? AppColors.danger : AppColors.warning,
                        color: AppColors.cyanAccent,
                        isDark: isDark,
                        onTap: () => onNavigateToTab?.call(14),
                      ),
                      _buildHubTile(
                        icon: Icons.fact_check_rounded,
                        label: 'Standups',
                        color: const Color(0xFF38BDF8),
                        isDark: isDark,
                        onTap: () => onNavigateToTab?.call(3),
                      ),
                      _buildHubTile(
                        icon: Icons.leaderboard_rounded,
                        label: 'Leaders',
                        color: const Color(0xFFF59E0B),
                        isDark: isDark,
                        onTap: () => onNavigateToTab?.call(5),
                      ),
                      _buildHubTile(
                        icon: Icons.timer_rounded,
                        label: 'Tracker',
                        color: AppColors.success,
                        isDark: isDark,
                        onTap: () => onNavigateToTab?.call(13),
                      ),
                      _buildHubTile(
                        icon: Icons.how_to_reg_rounded,
                        label: 'Attendance',
                        color: const Color(0xFFEC4899),
                        isDark: isDark,
                        onTap: () => onNavigateToTab?.call(12),
                      ),
                      _buildHubTile(
                        icon: Icons.group_rounded,
                        label: 'Members',
                        color: const Color(0xFF6366F1),
                        isDark: isDark,
                        onTap: () => onNavigateToTab?.call(6),
                      ),
                    ]
                  : auth.isOperationsManager
                      ? [
                          _buildHubTile(
                            icon: Icons.add_box_rounded,
                            label: 'Add Order',
                            color: AppColors.primaryBlue,
                            isDark: isDark,
                            onTap: () => showModalBottomSheet(
                              context: context,
                              isScrollControlled: true,
                              builder: (_) => const AddProjectSheet(),
                            ),
                          ),
                          _buildHubTile(
                            icon: Icons.layers_rounded,
                            label: 'Orders',
                            badge: activeCount > 0 ? '$activeCount' : null,
                            color: AppColors.primaryBlueLight,
                            isDark: isDark,
                            onTap: () {
                              projectsProv.setStatusFilter('all');
                              onNavigateToTab?.call(1);
                            },
                          ),
                          _buildHubTile(
                            icon: Icons.task_alt_rounded,
                            label: 'Tasks',
                            badge: tasksProv.countPending > 0 ? '${tasksProv.countPending}' : null,
                            badgeColor: tasksProv.countOverdue > 0 ? AppColors.danger : AppColors.warning,
                            color: AppColors.cyanAccent,
                            isDark: isDark,
                            onTap: () => onNavigateToTab?.call(14),
                          ),
                          _buildHubTile(
                            icon: Icons.fact_check_rounded,
                            label: 'Approvals',
                            color: const Color(0xFF8B5CF6),
                            isDark: isDark,
                            onTap: () => onNavigateToTab?.call(3),
                          ),
                          _buildHubTile(
                            icon: Icons.table_chart_rounded,
                            label: 'Monthly',
                            color: const Color(0xFF38BDF8),
                            isDark: isDark,
                            onTap: () => onNavigateToTab?.call(4),
                          ),
                          _buildHubTile(
                            icon: Icons.timer_rounded,
                            label: 'Tracker',
                            color: AppColors.success,
                            isDark: isDark,
                            onTap: () => onNavigateToTab?.call(13),
                          ),
                          _buildHubTile(
                            icon: Icons.how_to_reg_rounded,
                            label: 'Attendance',
                            color: const Color(0xFFEC4899),
                            isDark: isDark,
                            onTap: () => onNavigateToTab?.call(12),
                          ),
                          _buildHubTile(
                            icon: Icons.campaign_rounded,
                            label: 'Broadcast',
                            color: AppColors.warning,
                            isDark: isDark,
                            onTap: () => showModalBottomSheet(
                              context: context,
                              isScrollControlled: true,
                              builder: (_) => const CreateNoticeSheet(),
                            ),
                          ),
                        ]
                      : [
                          _buildHubTile(
                            icon: Icons.edit_note_rounded,
                            label: 'Standup',
                            color: AppColors.cyanAccent,
                            isDark: isDark,
                            onTap: () => Navigator.of(context).push(
                              MaterialPageRoute(builder: (_) => const LogStandupScreen()),
                            ),
                          ),
                          _buildHubTile(
                            icon: Icons.layers_rounded,
                            label: 'Orders',
                            badge: activeCount > 0 ? '$activeCount' : null,
                            color: AppColors.primaryBlueLight,
                            isDark: isDark,
                            onTap: () {
                              projectsProv.setStatusFilter('all');
                              onNavigateToTab?.call(1);
                            },
                          ),
                          _buildHubTile(
                            icon: Icons.task_alt_rounded,
                            label: 'My Tasks',
                            badge: tasksProv.countPending > 0 ? '${tasksProv.countPending}' : null,
                            badgeColor: tasksProv.countOverdue > 0 ? AppColors.danger : AppColors.warning,
                            color: const Color(0xFF8B5CF6),
                            isDark: isDark,
                            onTap: () => onNavigateToTab?.call(14),
                          ),
                          _buildHubTile(
                            icon: Icons.timer_rounded,
                            label: ttProv.isTracking ? 'Live' : 'Tracker',
                            color: AppColors.success,
                            isDark: isDark,
                            onTap: () => onNavigateToTab?.call(13),
                          ),
                          if (auth.isSquadLeader)
                            _buildHubTile(
                              icon: Icons.fact_check_rounded,
                              label: 'Approvals',
                              color: const Color(0xFF38BDF8),
                              isDark: isDark,
                              onTap: () => onNavigateToTab?.call(3),
                            )
                          else
                            _buildHubTile(
                              icon: Icons.chat_rounded,
                              label: 'Chat',
                              color: const Color(0xFF38BDF8),
                              isDark: isDark,
                              onTap: () => onNavigateToTab?.call(11),
                            ),
                          _buildHubTile(
                            icon: Icons.table_chart_rounded,
                            label: 'Monthly',
                            color: const Color(0xFF6366F1),
                            isDark: isDark,
                            onTap: () => onNavigateToTab?.call(4),
                          ),
                          _buildHubTile(
                            icon: Icons.leaderboard_rounded,
                            label: 'Leaders',
                            color: const Color(0xFFF59E0B),
                            isDark: isDark,
                            onTap: () => onNavigateToTab?.call(5),
                          ),
                          _buildHubTile(
                            icon: Icons.how_to_reg_rounded,
                            label: 'Attendance',
                            color: const Color(0xFFEC4899),
                            isDark: isDark,
                            onTap: () => onNavigateToTab?.call(12),
                          ),
                        ],
            ),
          ),

          const SizedBox(height: 18),

          // =================================================================
          // 4. SQUAD PERFORMANCE — CURRENT MONTH (LIVE BREAKDOWN)
          // =================================================================
          _buildSectionTitle(
            title: 'SQUAD PERFORMANCE ($monthShortLabel)',
            actionLabel: 'Details →',
            onAction: () => onNavigateToTab?.call(4),
            isDark: isDark,
          ),
          const SizedBox(height: 8),
          if (monthlyProv.isLoading && activeSquads.isEmpty)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(20),
                child: CircularProgressIndicator(strokeWidth: 2.5),
              ),
            )
          else if (activeSquads.isEmpty)
            _buildEmptyBox('Squad metrics will appear once monthly data syncs.', isDark)
          else
            ...activeSquads.map((sq) {
              final sqTotal = sq.deliveryVal + sq.submittedVal + sq.wipVal + sq.nraVal;
              final pct = sqTotal > 0 ? (sq.deliveryVal / sqTotal).clamp(0.0, 1.0) : 0.0;
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: InkWell(
                  onTap: () {
                    projectsProv.setTeamFilter(sq.teamName);
                    projectsProv.setStatusFilter('all');
                    onNavigateToTab?.call(1);
                  },
                  borderRadius: BorderRadius.circular(14),
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: isDark ? AppColors.darkCard : AppColors.lightCard,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            TeamBadge(team: sq.teamName, isSmall: true),
                            const SizedBox(width: 8),
                            Text(
                              '${sq.memberCount} specialists',
                              style: TextStyle(
                                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                fontSize: 11.5,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            const Spacer(),
                            Text(
                              moneyExact.format(sq.deliveryVal),
                              style: const TextStyle(
                                color: AppColors.success,
                                fontSize: 15,
                                fontWeight: FontWeight.w800,
                                fontFeatures: [FontFeature.tabularFigures()],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 9),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(4),
                          child: LinearProgressIndicator(
                            value: pct,
                            minHeight: 6,
                            backgroundColor: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                            valueColor: const AlwaysStoppedAnimation<Color>(AppColors.success),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(
                                '${sq.deliveryCount} del • ${sq.submittedCount > 0 ? '${sq.submittedCount} subm • ' : ''}${sq.wipCount} WIP (${moneyShort.format(sq.wipVal)})${sq.newAssignedCount > 0 ? ' • ${sq.newAssignedCount} new (${moneyShort.format(sq.newAssignedVal)})' : ''}${sq.carryForwardCount > 0 ? ' • ${sq.carryForwardCount} c/f (${moneyShort.format(sq.carryForwardVal)})' : ''}${sq.repeatCount > 0 ? ' • ${sq.repeatCount} rep (${moneyShort.format(sq.repeatVal)})' : ''}',
                                style: TextStyle(
                                  color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            const SizedBox(width: 6),
                            Text(
                              sq.cancelledCount > 0
                                  ? '${sq.cancelledCount} cancel'
                                  : '${(pct * 100).round()}% done',
                              style: TextStyle(
                                color: sq.cancelledCount > 0 ? AppColors.danger : AppColors.primaryBlueLight,
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              );
            }),

          const SizedBox(height: 12),

          // =================================================================
          // 5. TOP PERFORMERS THIS MONTH (TOP 3 LIVE PREVIEW)
          // =================================================================
          _buildSectionTitle(
            title: 'TOP PERFORMERS ($monthShortLabel)',
            actionLabel: 'Leaderboard →',
            onAction: () => onNavigateToTab?.call(5),
            isDark: isDark,
          ),
          const SizedBox(height: 8),
          if (lbProv.isLoading && rankings.isEmpty)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(16),
                child: CircularProgressIndicator(strokeWidth: 2.5),
              ),
            )
          else if (rankings.isEmpty)
            _buildEmptyBox('No leaderboard records for $monthFullLabel yet.', isDark)
          else
            Container(
              decoration: BoxDecoration(
                color: isDark ? AppColors.darkCard : AppColors.lightCard,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(
                  color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                ),
              ),
              child: Column(
                children: rankings.take(3).toList().asMap().entries.map((entry) {
                  final idx = entry.key;
                  final item = entry.value;
                  final medalColors = [
                    const Color(0xFFF59E0B),
                    const Color(0xFF94A3B8),
                    const Color(0xFFD97706),
                  ];
                  final medalColor = medalColors[idx];
                  return Column(
                    children: [
                      InkWell(
                        onTap: () => onNavigateToTab?.call(5),
                        borderRadius: BorderRadius.circular(14),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 11),
                          child: Row(
                            children: [
                              Container(
                                width: 26,
                                height: 26,
                                alignment: Alignment.center,
                                decoration: BoxDecoration(
                                  color: medalColor.withValues(alpha: 0.16),
                                  shape: BoxShape.circle,
                                  border: Border.all(color: medalColor.withValues(alpha: 0.45)),
                                ),
                                child: Text(
                                  '${idx + 1}',
                                  style: TextStyle(
                                    color: medalColor,
                                    fontSize: 12,
                                    fontWeight: FontWeight.w900,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              CircleAvatar(
                                radius: 17,
                                backgroundImage: NetworkImage(item.avatar),
                                backgroundColor: AppColors.primaryBlue.withValues(alpha: 0.2),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      item.name,
                                      style: TextStyle(
                                        color: isDark ? AppColors.textLight : AppColors.textDark,
                                        fontSize: 13,
                                        fontWeight: FontWeight.w700,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      '${item.team} • ${item.completedOrders} orders • ★ ${item.clientRating.toStringAsFixed(1)}',
                                      style: TextStyle(
                                        color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                        fontSize: 10.5,
                                        fontWeight: FontWeight.w600,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 8),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(
                                    moneyExact.format(item.netRevenue),
                                    style: const TextStyle(
                                      color: AppColors.success,
                                      fontSize: 13.5,
                                      fontWeight: FontWeight.w800,
                                      fontFeatures: [FontFeature.tabularFigures()],
                                    ),
                                  ),
                                  Text(
                                    'Score ${item.score.round()}',
                                    style: const TextStyle(
                                      color: AppColors.primaryBlueLight,
                                      fontSize: 10.5,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                      if (idx < rankings.take(3).length - 1)
                        Divider(
                          height: 1,
                          indent: 14,
                          endIndent: 14,
                          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                        ),
                    ],
                  );
                }).toList(),
              ),
            ),

          if (repeatLeaders.isNotEmpty) ...[
            const SizedBox(height: 18),
            // =================================================================
            // 5B. REPEAT CLIENT ORDERS BY MEMBER ($monthShortLabel)
            // =================================================================
            _buildSectionTitle(
              title: 'REPEAT ORDERS BY MEMBER ($repeatCount · ${moneyShort.format(repeatVal)})',
              actionLabel: 'Full Sheet →',
              onAction: () => onNavigateToTab?.call(4),
              isDark: isDark,
            ),
            const SizedBox(height: 8),
            Container(
              decoration: BoxDecoration(
                color: isDark ? AppColors.darkCard : AppColors.lightCard,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(
                  color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                ),
              ),
              child: Column(
                children: repeatLeaders.take(5).toList().asMap().entries.map((entry) {
                  final idx = entry.key;
                  final m = entry.value;
                  return Column(
                    children: [
                      InkWell(
                        onTap: () => onNavigateToTab?.call(4),
                        borderRadius: BorderRadius.circular(14),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 10),
                          child: Row(
                            children: [
                              Container(
                                width: 28,
                                height: 28,
                                alignment: Alignment.center,
                                decoration: BoxDecoration(
                                  color: const Color(0xFF06B6D4).withValues(alpha: 0.14),
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: const Color(0xFF06B6D4).withValues(alpha: 0.35)),
                                ),
                                child: Text(
                                  '${m.repeatCount}',
                                  style: const TextStyle(
                                    color: Color(0xFF06B6D4),
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.w900,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      m.name,
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
                                        if (m.team.isNotEmpty) ...[
                                          TeamBadge(team: m.team, isSmall: true),
                                          const SizedBox(width: 6),
                                        ],
                                        Text(
                                          '${m.repeatCount} repeat ${m.repeatCount == 1 ? 'order' : 'orders'}',
                                          style: TextStyle(
                                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                            fontSize: 11,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 8),
                              Text(
                                moneyExact.format(m.repeatVal),
                                style: const TextStyle(
                                  color: Color(0xFF06B6D4),
                                  fontSize: 13.5,
                                  fontWeight: FontWeight.w800,
                                  fontFeatures: [FontFeature.tabularFigures()],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      if (idx < repeatLeaders.take(5).length - 1)
                        Divider(
                          height: 1,
                          indent: 14,
                          endIndent: 14,
                          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                        ),
                    ],
                  );
                }).toList(),
              ),
            ),
          ],

          const SizedBox(height: 18),

          // =================================================================
          // 6. PRIORITY ACTIVE ORDERS (CLEAN TOP 4)
          // =================================================================
          _buildSectionTitle(
            title: 'PRIORITY ACTIVE ORDERS ($activeCount)',
            actionLabel: 'View All →',
            onAction: () {
              projectsProv.setTeamFilter('all');
              projectsProv.setStatusFilter('active');
              onNavigateToTab?.call(1);
            },
            isDark: isDark,
          ),
          const SizedBox(height: 8),
          if (projectsProv.isLoading && urgentOrders.isEmpty)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(20),
                child: CircularProgressIndicator(strokeWidth: 2.5),
              ),
            )
          else if (urgentOrders.isEmpty)
            _buildEmptyBox('No active orders in progress for $monthFullLabel.', isDark)
          else
            ...urgentOrders.take(4).map((p) => _buildCompactOrderCard(context, p, isDark, moneyShort)),
        ],
      ),
    );
  }

  Widget _buildSectionTitle({
    required String title,
    String? actionLabel,
    VoidCallback? onAction,
    required bool isDark,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Expanded(
          child: Text(
            title,
            style: TextStyle(
              color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
              fontSize: 11,
              fontWeight: FontWeight.w800,
              letterSpacing: 0.7,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
        if (actionLabel != null && onAction != null)
          InkWell(
            onTap: onAction,
            borderRadius: BorderRadius.circular(6),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
              child: Text(
                actionLabel,
                style: const TextStyle(
                  color: AppColors.primaryBlueLight,
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildMiniStatTile({
    required String label,
    required String primary,
    required String sub,
    required Color color,
    required bool isDark,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 10),
        decoration: BoxDecoration(
          color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: color.withValues(alpha: 0.28),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 7,
                  height: 7,
                  decoration: BoxDecoration(color: color, shape: BoxShape.circle),
                ),
                const SizedBox(width: 5),
                Expanded(
                  child: Text(
                    label,
                    style: TextStyle(
                      color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                      fontSize: 9.5,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.5,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 5),
            FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerLeft,
              child: Text(
                primary,
                style: TextStyle(
                  color: isDark ? AppColors.textLight : AppColors.textDark,
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  fontFeatures: const [FontFeature.tabularFigures()],
                ),
              ),
            ),
            const SizedBox(height: 2),
            Text(
              sub,
              style: TextStyle(
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                fontSize: 10,
                fontWeight: FontWeight.w600,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHubTile({
    required IconData icon,
    required String label,
    required Color color,
    required bool isDark,
    required VoidCallback onTap,
    String? badge,
    Color? badgeColor,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: isDark ? 0.16 : 0.12),
                  borderRadius: BorderRadius.circular(13),
                  border: Border.all(color: color.withValues(alpha: 0.3)),
                ),
                child: Icon(icon, size: 21, color: color),
              ),
              if (badge != null)
                Positioned(
                  top: -5,
                  right: -6,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                    decoration: BoxDecoration(
                      color: badgeColor ?? color,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: isDark ? AppColors.darkCard : AppColors.lightCard,
                        width: 1.5,
                      ),
                    ),
                    child: Text(
                      badge,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 9,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            label,
            style: TextStyle(
              color: isDark ? AppColors.textLight : AppColors.textDark,
              fontSize: 11,
              fontWeight: FontWeight.w700,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  bool _isOverdue(String deliveryDateStr) {
    final dt = DateTime.tryParse(deliveryDateStr);
    if (dt == null) return false;
    final today = DateTime.now();
    final todayDate = DateTime(today.year, today.month, today.day);
    return DateTime(dt.year, dt.month, dt.day).isBefore(todayDate);
  }

  Widget _buildCompactOrderCard(
    BuildContext context,
    ProjectItem project,
    bool isDark,
    NumberFormat moneyShort,
  ) {
    final overdue = project.isLate;
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        onTap: () {
          showModalBottomSheet(
            context: context,
            isScrollControlled: true,
            backgroundColor: Colors.transparent,
            builder: (_) => ProjectDetailSheet(project: project),
          );
        },
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.all(13),
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkCard : AppColors.lightCard,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: overdue
                  ? AppColors.danger.withValues(alpha: 0.45)
                  : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  TeamBadge(team: project.team, isSmall: true),
                  if (project.isRepeatClient) ...[
                    const SizedBox(width: 5),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFF06B6D4).withValues(alpha: 0.14),
                        borderRadius: BorderRadius.circular(5),
                        border: Border.all(color: const Color(0xFF06B6D4).withValues(alpha: 0.35)),
                      ),
                      child: const Text(
                        'Repeat',
                        style: TextStyle(
                          color: Color(0xFF06B6D4),
                          fontSize: 9.5,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      '${project.buyerUsername} · ${project.orderId}',
                      style: TextStyle(
                        color: isDark ? AppColors.textLight : AppColors.textDark,
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    '\$${project.netValue.toStringAsFixed(2)}',
                    style: const TextStyle(
                      color: AppColors.success,
                      fontSize: 13.5,
                      fontWeight: FontWeight.w800,
                      fontFeatures: [FontFeature.tabularFigures()],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  StatusBadge(status: project.status, isSmall: true),
                  const Spacer(),
                  Flexible(
                    child: CountdownPill(
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
                  ),
                  const SizedBox(width: 6),
                  if (project.assignees.isNotEmpty)
                    Flexible(
                      child: Text(
                        project.assignees.first.name,
                        style: TextStyle(
                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.end,
                      ),
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyBox(String msg, bool isDark) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
        ),
      ),
      child: Center(
        child: Text(
          msg,
          textAlign: TextAlign.center,
          style: TextStyle(
            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
            fontSize: 12,
          ),
        ),
      ),
    );
  }
}
