import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/auth_provider.dart';
import '../../providers/daily_reports_provider.dart';
import '../../models/daily_report.dart';
import '../../theme/app_colors.dart';
import '../../widgets/team_badge.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/drawer_opener.dart';
import 'log_standup_screen.dart';
import 'team_review_screen.dart';
import 'report_detail_sheet.dart';

class DailyReportsScreen extends StatefulWidget {
  const DailyReportsScreen({super.key});

  @override
  State<DailyReportsScreen> createState() => _DailyReportsScreenState();
}

class _DailyReportsScreenState extends State<DailyReportsScreen> {
  bool _showAllMyHistory = true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final prov = Provider.of<DailyReportsProvider>(context, listen: false);
      if (auth.canLogStandup) {
        prov.fetchMyReports(includeHistory: _showAllMyHistory);
      }
      final canReview =
          auth.canReviewStandups || auth.isExecutive || auth.canViewAllDailyReports;
      // View-only executives render TeamReviewScreen directly, which fetches team reports in its own initState.
      if (canReview && !(auth.isExecutive && !auth.canLogStandup)) {
        prov.fetchReports();
      }
    });
  }

  Future<void> _pickDate(DailyReportsProvider prov) async {
    final DateTime current =
        DateTime.tryParse(prov.mySelectedDate) ?? DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime.now().subtract(const Duration(days: 120)),
      lastDate: DateTime.now().add(const Duration(days: 7)),
    );
    if (picked != null) {
      setState(() => _showAllMyHistory = false);
      prov.setMySelectedDate(DateFormat('yyyy-MM-dd').format(picked));
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final prov = Provider.of<DailyReportsProvider>(context);
    final auth = Provider.of<AuthProvider>(context);

    final canReview =
        auth.canReviewStandups || auth.isExecutive || auth.canViewAllDailyReports;

    // Executive oversight users do not submit personal standups -> show full Team Standups screen directly.
    if (auth.isExecutive && !auth.canLogStandup) {
      return const TeamReviewScreen(isEmbeddedInTab: false);
    }

    final myStandupContent =
        _buildMyStandupView(context, prov, auth, isDark, canReview);

    if (canReview) {
      return DefaultTabController(
        length: 2,
        initialIndex: auth.isOperationsManager ? 1 : 0,
        child: Scaffold(
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
            title: const Text('Daily Standup & Reports'),
            bottom: TabBar(
              indicatorColor: AppColors.primaryBlueLight,
              labelColor: AppColors.primaryBlueLight,
              unselectedLabelColor: Colors.grey,
              tabs: [
                const Tab(
                  icon: Icon(Icons.person_pin_rounded, size: 18),
                  text: 'My Standup',
                ),
                Tab(
                  icon: const Icon(Icons.fact_check_rounded, size: 18),
                  text: auth.isOperationsManager
                      ? 'All Team Standups'
                      : 'Team Approvals',
                ),
              ],
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.calendar_today_rounded),
                onPressed: () => _pickDate(prov),
                tooltip: 'Filter My Standups by Date',
              ),
              IconButton(
                icon: const Icon(Icons.refresh_rounded),
                onPressed: () {
                  prov.fetchMyReports(includeHistory: _showAllMyHistory);
                  prov.fetchReports();
                },
                tooltip: 'Refresh Reports',
              ),
            ],
          ),
          floatingActionButton: auth.canLogStandup
              ? FloatingActionButton.extended(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const LogStandupScreen()),
                    );
                  },
                  backgroundColor: AppColors.primaryBlue,
                  icon: const Icon(Icons.edit_note_rounded, color: Colors.white),
                  label: const Text(
                    'Log Standup',
                    style: TextStyle(fontWeight: FontWeight.w700, color: Colors.white),
                  ),
                )
              : null,
          body: TabBarView(
            children: [
              myStandupContent,
              const TeamReviewScreen(isEmbeddedInTab: true),
            ],
          ),
        ),
      );
    }

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
        title: const Text('Daily Standups'),
        actions: [
          IconButton(
            icon: const Icon(Icons.calendar_today_rounded),
            onPressed: () => _pickDate(prov),
            tooltip: 'Select Date',
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () {
              prov.fetchMyReports(includeHistory: _showAllMyHistory);
              if (canReview) {
                prov.fetchReports();
              }
            },
            tooltip: 'Refresh Reports',
          ),
        ],
      ),
      floatingActionButton: auth.canLogStandup
          ? FloatingActionButton.extended(
              onPressed: () {
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const LogStandupScreen()),
                );
              },
              backgroundColor: AppColors.primaryBlue,
              icon: const Icon(Icons.edit_note_rounded, color: Colors.white),
              label: const Text(
                'Log Standup',
                style: TextStyle(fontWeight: FontWeight.w700, color: Colors.white),
              ),
            )
          : null,
      body: myStandupContent,
    );
  }

  Widget _buildMyStandupView(BuildContext context, DailyReportsProvider prov, AuthProvider auth, bool isDark, bool canReview) {
    final list = prov.myReports;
    final isLoading = prov.isLoadingMine;

    return Column(
      children: [
        // Submission banner
        if (auth.canLogStandup)
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF1E3A8A), Color(0xFF2563EB)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: Colors.blue.withOpacity(0.2),
                  blurRadius: 8,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.18),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.edit_note_rounded, color: Colors.white, size: 22),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: const [
                      Text(
                        'Submit Today\'s Standup Report',
                        style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 13.5),
                      ),
                      SizedBox(height: 2),
                      Text(
                        'Log your project tasks, deliverables & hours',
                        style: TextStyle(color: Colors.white70, fontSize: 11),
                      ),
                    ],
                  ),
                ),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: const Color(0xFF1E3A8A),
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    elevation: 0,
                  ),
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const LogStandupScreen()),
                    );
                  },
                  child: const Text('Log Standup', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 12)),
                ),
              ],
            ),
          ),

        // Date Selector Header
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
            border: Border(
              bottom: BorderSide(
                color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                width: 1,
              ),
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Row(
                  children: [
                    Icon(
                      _showAllMyHistory
                          ? Icons.history_rounded
                          : Icons.calendar_month_rounded,
                      size: 16,
                      color: AppColors.primaryBlueLight,
                    ),
                    const SizedBox(width: 8),
                    Flexible(
                      child: Text(
                        _showAllMyHistory
                            ? 'All My Standups (${list.length})'
                            : DateFormat('EEE, dd MMM yyyy').format(
                                DateTime.tryParse(prov.mySelectedDate) ??
                                    DateTime.now(),
                              ),
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: isDark ? AppColors.textLight : AppColors.textDark,
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextButton(
                    onPressed: () {
                      setState(() => _showAllMyHistory = !_showAllMyHistory);
                      prov.fetchMyReports(includeHistory: _showAllMyHistory);
                    },
                    child: Text(
                      _showAllMyHistory ? 'By Date' : 'All History',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  TextButton(
                    onPressed: () {
                      setState(() => _showAllMyHistory = false);
                      prov.setMySelectedDate(
                        DateFormat('yyyy-MM-dd').format(DateTime.now()),
                      );
                    },
                    child: const Text(
                      'Today',
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),

        // Reports List
        if (isLoading && list.isNotEmpty)
          const LinearProgressIndicator(minHeight: 2),
        Expanded(
          child: RefreshIndicator(
            onRefresh: () async {
              await prov.fetchMyReports(includeHistory: _showAllMyHistory);
              if (canReview) {
                await prov.fetchReports();
              }
            },
            child: isLoading && list.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : prov.errorMessage != null && list.isEmpty
                    ? EmptyStateWidget(
                        icon: Icons.cloud_off_rounded,
                        title: 'Could Not Load Standups',
                        description: prov.errorMessage!,
                        actionLabel: 'Retry',
                        actionIcon: Icons.refresh_rounded,
                        onAction: () => prov.fetchMyReports(includeHistory: _showAllMyHistory),
                      )
                    : list.isEmpty
                        ? EmptyStateWidget(
                            icon: Icons.event_available_rounded,
                            title: 'No Standup Submitted Yet',
                            description: _showAllMyHistory
                                ? 'No personal standup logs found yet. Tap Log Standup below to record today\'s deliverables, progress, and working hours.'
                                : 'No daily standup logged for ${prov.mySelectedDate}. Tap Log Standup below or switch to All History.',
                            actionLabel: auth.canLogStandup ? 'Log Daily Standup' : null,
                            onAction: auth.canLogStandup
                                ? () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (_) => const LogStandupScreen(),
                                      ),
                                    );
                                  }
                                : null,
                          )
                        : ListView.builder(
                            padding: const EdgeInsets.all(16),
                            itemCount: list.length,
                            itemBuilder: (context, index) {
                              final report = list[index];
                              return _buildStandupCard(context, report, isDark);
                            },
                          ),
          ),
        ),
      ],
    );
  }

  Widget _buildStandupCard(BuildContext context, DailyReport report, bool isDark) {
    Color statusColor = AppColors.primaryBlueLight;
    if (report.status == 'approved') {
      statusColor = AppColors.success;
    } else if (report.status == 'leader_approved') {
      statusColor = const Color(0xFF0D9488);
    } else if (report.status == 'revision_requested') {
      statusColor = AppColors.warning;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () => ReportDetailSheet.show(context, report),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Specialist Profile Header
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 18,
                          backgroundColor: AppColors.primaryBlue.withOpacity(0.2),
                          backgroundImage: report.userAvatar.isNotEmpty ? NetworkImage(report.userAvatar) : null,
                          child: report.userAvatar.isEmpty
                              ? Text(
                                  report.userName.isNotEmpty ? report.userName[0].toUpperCase() : 'S',
                                  style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight),
                                )
                              : null,
                        ),
                        const SizedBox(width: 10),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              report.userName,
                              style: TextStyle(
                                color: isDark ? AppColors.textLight : AppColors.textDark,
                                fontSize: 14,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            Row(
                              children: [
                                if (report.team.isNotEmpty) ...[
                                  TeamBadge(team: report.team, isSmall: true),
                                  const SizedBox(width: 6),
                                ],
                                Text(
                                  '${report.callSign.isNotEmpty ? '${report.callSign} • ' : ''}${report.reportDate}',
                                  style: TextStyle(
                                    color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                    fontSize: 11,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ],
                    ),
                    Row(
                      children: [
                        // Status Badge
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: statusColor.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: statusColor.withOpacity(0.3)),
                          ),
                          child: Text(
                            report.statusLabel,
                            style: TextStyle(
                              color: statusColor,
                              fontSize: 10.5,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        // Hours
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppColors.primaryBlue.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: AppColors.primaryBlue.withOpacity(0.25)),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.timer_rounded, size: 12, color: AppColors.primaryBlueLight),
                              const SizedBox(width: 4),
                              Text(
                                '${report.totalHours.toStringAsFixed(1)}h',
                                style: const TextStyle(
                                  color: AppColors.primaryBlueLight,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ],
                ),

                const SizedBox(height: 12),

                // Deliveries Row (if any)
                if (report.deliveredProjects.isNotEmpty) ...[
                  Container(
                    margin: const EdgeInsets.only(bottom: 10),
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFF10B981).withOpacity(0.10),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFF10B981).withOpacity(0.3)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.rocket_launch_rounded, size: 16, color: Color(0xFF10B981)),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            '${report.deliveredProjects.length} Project Deliveries (${report.deliveredProjects.map((d) => d.orderId).join(", ")})',
                            style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: Color(0xFF10B981)),
                          ),
                        ),
                        Text(
                          '+ \$${report.deliveredProjects.fold<double>(0.0, (acc, d) => acc + d.netValue).toStringAsFixed(2)}',
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF10B981)),
                        ),
                      ],
                    ),
                  ),
                ],

                // Itemized Tasks
                if (report.items.isNotEmpty) ...[
                  Text(
                    'COMPLETED TASKS & DELIVERABLES',
                    style: TextStyle(
                      color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.5,
                    ),
                  ),
                  const SizedBox(height: 6),
                  ...report.items.take(3).map((item) {
                    final desc = item.tasksCompleted.isNotEmpty ? item.tasksCompleted : item.taskDescription;
                    return Container(
                      margin: const EdgeInsets.only(bottom: 6),
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                      ),
                      child: Row(
                        children: [
                          if (item.orderId.isNotEmpty) ...[
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: AppColors.primaryBlue.withOpacity(0.12),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                item.orderId,
                                style: const TextStyle(
                                  color: AppColors.primaryBlueLight,
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                          ],
                          Expanded(
                            child: Text(
                              desc.isNotEmpty ? desc : 'Project deliverable',
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                color: isDark ? AppColors.textLight : AppColors.textDark,
                                fontSize: 12,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            '${item.hoursSpent.toStringAsFixed(1)}h',
                            style: const TextStyle(
                              color: AppColors.success,
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                  if (report.items.length > 3)
                    Padding(
                      padding: const EdgeInsets.only(top: 2, bottom: 6),
                      child: Text(
                        '+ ${report.items.length - 3} more deliverables...',
                        style: TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: isDark ? AppColors.textDim : AppColors.textDarkMuted),
                      ),
                    ),
                ],

                // Blockers / Challenges if any
                if (report.blockers.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppColors.danger.withOpacity(0.08),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: AppColors.danger.withOpacity(0.2)),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.warning_amber_rounded, size: 16, color: AppColors.danger),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Blockers & Challenges',
                                style: TextStyle(color: AppColors.danger, fontSize: 11, fontWeight: FontWeight.w700),
                              ),
                              Text(
                                report.blockers,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  color: isDark ? AppColors.textLight : AppColors.textDark,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],

                const SizedBox(height: 12),
                const Divider(height: 1),
                const SizedBox(height: 8),

                // Tap to view full report detail link
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Tap to inspect full report details',
                      style: TextStyle(fontSize: 11, color: isDark ? AppColors.textDim : AppColors.textDarkMuted),
                    ),
                    Row(
                      children: const [
                        Text(
                          'View Full Report',
                          style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight),
                        ),
                        SizedBox(width: 4),
                        Icon(Icons.arrow_forward_rounded, size: 14, color: AppColors.primaryBlueLight),
                      ],
                    ),
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
