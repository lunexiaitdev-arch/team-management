import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import '../../models/project.dart';
import '../../models/time_tracker.dart';
import '../../providers/auth_provider.dart';
import '../../providers/projects_provider.dart';
import '../../providers/time_tracker_provider.dart';
import '../../theme/app_colors.dart';
import '../../widgets/drawer_opener.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/filter_pill_group.dart';
import '../../widgets/team_badge.dart';

class TimeTrackerScreen extends StatefulWidget {
  const TimeTrackerScreen({super.key});

  @override
  State<TimeTrackerScreen> createState() => _TimeTrackerScreenState();
}

class _TimeTrackerScreenState extends State<TimeTrackerScreen>
    with TickerProviderStateMixin {
  TabController? _tabController;
  bool _hasScreenshotTab = false;

  // Date-wise records filter state
  String _dailyPreset = '30d'; // 'today', 'yesterday', '7d', '30d', 'custom'
  String _dailyFrom = '';
  String _dailyTo = '';
  String _dailyTeam = 'all';
  String _dailyType = 'all';
  int? _dailySpecialistId;
  final TextEditingController _dailySearchCtrl = TextEditingController();

  // Sessions log filter state
  String _sessTeam = 'all';
  String _sessType = 'all';
  String _sessDate = '';
  final TextEditingController _sessSearchCtrl = TextEditingController();

  // Screenshot gallery filter state (Leaders/Admins/Exec only)
  String _snapType = 'all';
  String _snapTeam = 'all';
  String _snapDate = '';
  int? _snapSessionId;
  final TextEditingController _snapSearchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _applyPresetDates('30d', triggerFetch: false);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _refreshAll();
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final auth = Provider.of<AuthProvider>(context);
    final showHub = auth.isLeader || auth.isAdmin || auth.isExecutive;
    if (!auth.isAdmin && !auth.isExecutive && (auth.currentUser?.team.isNotEmpty ?? false)) {
      final myTeam = auth.currentUser!.team;
      if (_dailyTeam == 'all') _dailyTeam = myTeam;
      if (_sessTeam == 'all') _sessTeam = myTeam;
      if (_snapTeam == 'all') _snapTeam = myTeam;
    }
    if (_tabController == null || _hasScreenshotTab != showHub) {
      final oldIdx = _tabController?.index ?? 0;
      _tabController?.dispose();
      _hasScreenshotTab = showHub;
      final len = showHub ? 4 : 3;
      _tabController = TabController(
        length: len,
        vsync: this,
        initialIndex: oldIdx.clamp(0, len - 1),
      );
    }
  }

  @override
  void dispose() {
    _tabController?.dispose();
    _dailySearchCtrl.dispose();
    _sessSearchCtrl.dispose();
    _snapSearchCtrl.dispose();
    super.dispose();
  }

  void _applyPresetDates(String preset, {bool triggerFetch = true}) {
    final now = DateTime.now();
    final fmt = DateFormat('yyyy-MM-dd');
    _dailyPreset = preset;

    if (preset == 'today') {
      _dailyFrom = fmt.format(now);
      _dailyTo = fmt.format(now);
    } else if (preset == 'yesterday') {
      final y = now.subtract(const Duration(days: 1));
      _dailyFrom = fmt.format(y);
      _dailyTo = fmt.format(y);
    } else if (preset == '7d') {
      _dailyFrom = fmt.format(now.subtract(const Duration(days: 6)));
      _dailyTo = fmt.format(now);
    } else if (preset == '30d') {
      _dailyFrom = fmt.format(now.subtract(const Duration(days: 29)));
      _dailyTo = fmt.format(now);
    }

    if (triggerFetch) {
      _fetchDailySummary();
    }
  }

  Future<void> _refreshAll() async {
    if (!mounted) return;
    final tt = Provider.of<TimeTrackerProvider>(context, listen: false);
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final showHub = auth.isLeader || auth.isAdmin || auth.isExecutive;

    final futures = <Future<void>>[
      tt.fetchActiveSession(),
      tt.fetchExtraTimeSummary(),
      _fetchDailySummary(),
      _fetchSessions(),
      tt.fetchStats(team: _dailyTeam),
    ];
    if (showHub) {
      futures.add(_fetchSnapshots());
    }
    await Future.wait(futures);
  }

  Future<void> _fetchDailySummary() {
    final tt = Provider.of<TimeTrackerProvider>(context, listen: false);
    return tt.fetchDailySummary(
      team: _dailyTeam,
      dateFrom: _dailyFrom,
      dateTo: _dailyTo,
      sessionType: _dailyType,
      userId: _dailySpecialistId,
      search: _dailySearchCtrl.text.trim(),
    );
  }

  Future<void> _fetchSessions() {
    final tt = Provider.of<TimeTrackerProvider>(context, listen: false);
    return tt.fetchSessions(
      team: _sessTeam,
      sessionType: _sessType,
      date: _sessDate,
      search: _sessSearchCtrl.text.trim(),
    );
  }

  Future<void> _fetchSnapshots() {
    final tt = Provider.of<TimeTrackerProvider>(context, listen: false);
    return tt.fetchSnapshots(
      sessionType: _snapType,
      team: _snapTeam,
      date: _snapDate,
      sessionId: _snapSessionId,
      search: _snapSearchCtrl.text.trim(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final auth = Provider.of<AuthProvider>(context);
    final tt = Provider.of<TimeTrackerProvider>(context);
    final showHub = auth.isLeader || auth.isAdmin || auth.isExecutive;

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
        title: Text(
          showHub ? 'Time Tracker & Activity Hub' : 'Work Timer & Time Records',
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'Sync Time Hub',
            onPressed: _refreshAll,
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          tabAlignment: TabAlignment.start,
          indicatorColor: AppColors.primaryBlueLight,
          labelColor: AppColors.primaryBlueLight,
          unselectedLabelColor:
              isDark ? AppColors.textMuted : AppColors.textDarkMuted,
          labelStyle:
              const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800),
          tabs: [
            Tab(
              icon: Icon(
                auth.isViewOnly
                    ? Icons.more_time_rounded
                    : Icons.timer_rounded,
                size: 18,
              ),
              text: auth.isViewOnly ? 'Overtime Summary' : 'Tracker & Overtime',
            ),
            const Tab(
              icon: Icon(Icons.calendar_month_rounded, size: 18),
              text: 'Date-Wise Records',
            ),
            const Tab(
              icon: Icon(Icons.history_rounded, size: 18),
              text: 'Sessions Log',
            ),
            if (showHub)
              Tab(
                icon: const Icon(Icons.photo_library_rounded, size: 18),
                text: 'Screenshots (${tt.snapshots.length})',
              ),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildTrackerAndOvertimeTab(isDark, auth, tt, showHub),
          _buildDateWiseTab(isDark, auth, tt, showHub),
          _buildSessionsTab(isDark, auth, tt, showHub),
          if (showHub) _buildScreenshotHubTab(isDark, auth, tt),
        ],
      ),
    );
  }

  // =========================================================================
  // TAB 1: WORK TRACKER & OVERTIME
  // =========================================================================
  Widget _buildTrackerAndOvertimeTab(
    bool isDark,
    AuthProvider auth,
    TimeTrackerProvider tt,
    bool showHub,
  ) {
    final active = tt.activeSession;
    final stats = tt.stats;

    return RefreshIndicator(
      onRefresh: () async {
        await Future.wait([
          tt.fetchActiveSession(),
          tt.fetchExtraTimeSummary(),
          tt.fetchStats(team: _dailyTeam),
        ]);
      },
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Today / Total Quick Strip (Website parity, stealth-safe)
          Row(
            children: [
              Expanded(
                child: _buildMiniStatCard(
                  isDark,
                  "TODAY'S TRACKED",
                  stats?.todayHoursFormatted ?? '0h 00m',
                  AppColors.success,
                  Icons.today_rounded,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _buildMiniStatCard(
                  isDark,
                  'ALL-TIME TRACKED',
                  stats?.totalHoursFormatted ?? '0h 00m',
                  AppColors.primaryBlueLight,
                  Icons.timelapse_rounded,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Active Tracker Controls (Hidden for View-Only Executive)
          if (!auth.isViewOnly) ...[
            _buildTrackerControlCard(isDark, tt, active),
            const SizedBox(height: 20),
          ] else ...[
            Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF8B5CF6).withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: const Color(0xFF8B5CF6).withValues(alpha: 0.35),
                ),
              ),
              child: const Row(
                children: [
                  Icon(Icons.visibility_rounded,
                      size: 18, color: Color(0xFFA78BFA)),
                  SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Executive Oversight Mode (Read-Only): Explore Date-Wise Records, Sessions & Screenshots across all squads.',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFFA78BFA),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],

          // Overtime Header Banner
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF312E81), Color(0xFF4338CA)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.18),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.bolt_rounded,
                          color: Colors.amberAccent, size: 22),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Extra Time (Overtime after 6:20 PM)',
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w800,
                              fontSize: 14.5,
                            ),
                          ),
                          SizedBox(height: 2),
                          Text(
                            'Log urgent client revisions and post-shift deliverables',
                            style:
                                TextStyle(color: Colors.white70, fontSize: 11.5),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                if (!auth.isViewOnly) ...[
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      icon: const Icon(Icons.add_task_rounded, size: 17),
                      label: const Text('Submit Extra Time Work Report'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: const Color(0xFF312E81),
                        padding: const EdgeInsets.symmetric(vertical: 11),
                        textStyle: const TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 13,
                        ),
                      ),
                      onPressed: () => _showExtraTimeReportModal(context),
                    ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 14),

          // Overtime KPI Row (Strict stealth: NEVER show snapshot counts to Specialists)
          Row(
            children: [
              Expanded(
                child: _buildMiniStatCard(
                  isDark,
                  'EXTRA TIME HOURS',
                  tt.totalExtraFormatted,
                  Colors.amberAccent,
                  Icons.more_time_rounded,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _buildMiniStatCard(
                  isDark,
                  'EXTRA SESSIONS',
                  '${tt.totalExtraSessionsCount}',
                  AppColors.cyanAccent,
                  Icons.bolt_rounded,
                ),
              ),
              if (showHub) ...[
                const SizedBox(width: 10),
                Expanded(
                  child: _buildMiniStatCard(
                    isDark,
                    'OT SNAPSHOTS',
                    '${tt.totalExtraSnapshotsCount}',
                    AppColors.purpleAccent,
                    Icons.camera_alt_rounded,
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 18),

          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'EXTRA TIME & OVERTIME LOGS (${tt.extraHistory.length})',
                style: TextStyle(
                  fontSize: 11.5,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 0.7,
                  color: isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                ),
              ),
              if (tt.isLoadingExtra)
                const SizedBox(
                  width: 14,
                  height: 14,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
            ],
          ),
          const SizedBox(height: 10),

          if (tt.extraHistory.isEmpty && !tt.isLoadingExtra)
            const EmptyStateWidget(
              icon: Icons.more_time_rounded,
              title: 'No Overtime Records Yet',
              description:
                  'Track an overtime shift or submit an Extra Time Report after 6:20 PM to log extra work.',
            )
          else
            ...tt.extraHistory.map((item) {
              return Container(
                margin: const EdgeInsets.only(bottom: 10),
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
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 7,
                                vertical: 3,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.amber.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                item.extraTimeFormatted,
                                style: const TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w800,
                                  color: Colors.amber,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            if (item.orderId.isNotEmpty)
                              Text(
                                item.orderId,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                  fontSize: 12.5,
                                  color: AppColors.primaryBlueLight,
                                ),
                              ),
                          ],
                        ),
                        Text(
                          item.reportDateFormatted,
                          style: TextStyle(
                            fontSize: 11,
                            color: isDark
                                ? AppColors.textDim
                                : AppColors.textDarkMuted,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      item.tasksCompleted.isNotEmpty
                          ? item.tasksCompleted
                          : 'Overtime deliverables logged',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      '${item.clientName.isNotEmpty ? "${item.clientName} • " : ""}${item.extraTimeReason}',
                      style: TextStyle(
                        fontSize: 11.5,
                        color: isDark
                            ? AppColors.textMuted
                            : AppColors.textDarkMuted,
                      ),
                    ),
                  ],
                ),
              );
            }),
        ],
      ),
    );
  }

  Widget _buildTrackerControlCard(
    bool isDark,
    TimeTrackerProvider tt,
    ActiveTrackingSession? active,
  ) {
    final isExtra = active?.isExtraTime == true;

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: active != null
              ? (isExtra ? Colors.amber : AppColors.success)
              : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
          width: active != null ? 1.5 : 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: (active != null
                          ? (isExtra ? Colors.amber : AppColors.success)
                          : AppColors.primaryBlue)
                      .withValues(alpha: 0.14),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  active != null
                      ? (isExtra ? Icons.bolt_rounded : Icons.play_arrow_rounded)
                      : Icons.timer_outlined,
                  color: active != null
                      ? (isExtra ? Colors.amber : AppColors.success)
                      : AppColors.primaryBlueLight,
                  size: 26,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      active != null
                          ? (isExtra
                              ? '⚡ OVERTIME TRACKER RUNNING'
                              : '● ACTIVE SHIFT TRACKING')
                          : 'Work Time Tracker',
                      style: TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w800,
                        color: active != null
                            ? (isExtra ? Colors.amber : AppColors.success)
                            : null,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      active != null
                          ? 'Order ${active.orderId.isNotEmpty ? active.orderId : "#General"}${active.clientName.isNotEmpty ? " • ${active.clientName}" : ""}'
                          : 'Start tracking a project shift or extra overtime hours.',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDark
                            ? AppColors.textMuted
                            : AppColors.textDarkMuted,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (active != null) ...[
            const SizedBox(height: 16),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 14),
              decoration: BoxDecoration(
                color: isDark
                    ? Colors.black.withValues(alpha: 0.25)
                    : Colors.grey.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                tt.formattedClock,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.w900,
                  fontFeatures: const [FontFeature.tabularFigures()],
                  color: isExtra ? Colors.amber : AppColors.primaryBlueLight,
                  letterSpacing: 1.5,
                ),
              ),
            ),
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                icon: const Icon(Icons.stop_circle_rounded, size: 18),
                label: Text(
                  tt.isSubmitting ? 'Stopping...' : 'Stop & Log Deliverables',
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.danger,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  textStyle: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 13,
                  ),
                ),
                onPressed: tt.isSubmitting
                    ? null
                    : () => _showStopTrackerDialog(context, active),
              ),
            ),
          ] else ...[
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.play_arrow_rounded, size: 18),
                    label: const Text('Start Regular'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.success,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      textStyle: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 12.5,
                      ),
                    ),
                    onPressed: tt.isSubmitting
                        ? null
                        : () => _showStartTrackerModal(
                              context,
                              sessionType: 'regular',
                            ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.bolt_rounded, size: 18),
                    label: const Text('Start Overtime'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.amber.shade700,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      textStyle: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 12.5,
                      ),
                    ),
                    onPressed: tt.isSubmitting
                        ? null
                        : () => _showStartTrackerModal(
                              context,
                              sessionType: 'extra_time',
                            ),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  // =========================================================================
  // TAB 2: DATE-WISE TIME RECORDS (Website Parity)
  // =========================================================================
  Widget _buildDateWiseTab(
    bool isDark,
    AuthProvider auth,
    TimeTrackerProvider tt,
    bool showHub,
  ) {
    final data = tt.dailySummary;
    final summary = data?.summary;
    final totals = data?.dailyTotals ?? [];
    final records = data?.records ?? [];
    final specialists = data?.specialists ?? [];

    final presetPills = [
      FilterPillItem(keyName: 'today', label: 'Today'),
      FilterPillItem(keyName: 'yesterday', label: 'Yesterday'),
      FilterPillItem(keyName: '7d', label: '7 Days'),
      FilterPillItem(keyName: '30d', label: '30 Days'),
      FilterPillItem(keyName: 'custom', label: 'Custom Range'),
    ];

    final canViewAllSquads = auth.isAdmin || auth.isExecutive;
    final userSquad = (auth.currentUser?.team.isNotEmpty == true) ? auth.currentUser!.team : 'My Squad';
    final teamPills = canViewAllSquads
        ? [
            FilterPillItem(keyName: 'all', label: 'All Squads'),
            FilterPillItem(keyName: 'WpTitans', label: 'WpTitans'),
            FilterPillItem(keyName: 'WpNexus', label: 'WpNexus'),
            FilterPillItem(keyName: 'WpCraft', label: 'WpCraft'),
          ]
        : [
            FilterPillItem(keyName: userSquad, label: userSquad),
          ];

    final typePills = [
      FilterPillItem(keyName: 'all', label: 'All Shifts'),
      FilterPillItem(keyName: 'regular', label: 'Regular'),
      FilterPillItem(keyName: 'extra_time', label: '⚡ Overtime'),
    ];

    return Column(
      children: [
        // Filter Header
        Container(
          padding: const EdgeInsets.fromLTRB(0, 10, 0, 10),
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkCard : AppColors.lightCard,
            border: Border(
              bottom: BorderSide(
                color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
              ),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Date Presets
              FilterPillGroup(
                items: presetPills,
                selectedKey: _dailyPreset,
                onSelected: (key) async {
                  if (key == 'custom') {
                    final range = await showDateRangePicker(
                      context: context,
                      firstDate: DateTime(2026, 1, 1),
                      lastDate: DateTime.now().add(const Duration(days: 1)),
                      initialDateRange: DateTimeRange(
                        start: DateTime.tryParse(_dailyFrom) ??
                            DateTime.now().subtract(const Duration(days: 7)),
                        end: DateTime.tryParse(_dailyTo) ?? DateTime.now(),
                      ),
                    );
                    if (range != null) {
                      final fmt = DateFormat('yyyy-MM-dd');
                      setState(() {
                        _dailyPreset = 'custom';
                        _dailyFrom = fmt.format(range.start);
                        _dailyTo = fmt.format(range.end);
                      });
                      _fetchDailySummary();
                    }
                  } else {
                    setState(() {
                      _applyPresetDates(key, triggerFetch: false);
                    });
                    _fetchDailySummary();
                  }
                },
              ),
              const SizedBox(height: 8),

              // Date Range & Specialist / Search Filters
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14),
                child: Row(
                  children: [
                    Expanded(
                      child: InkWell(
                        onTap: () => _pickDailyBoundary(isFrom: true),
                        borderRadius: BorderRadius.circular(8),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 8,
                          ),
                          decoration: BoxDecoration(
                            color: isDark
                                ? AppColors.darkSurface
                                : AppColors.lightSurface,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: isDark
                                  ? AppColors.darkBorder
                                  : AppColors.lightBorder,
                            ),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.date_range_rounded,
                                  size: 14, color: AppColors.primaryBlueLight),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  'From: $_dailyFrom',
                                  style: const TextStyle(
                                    fontSize: 11.5,
                                    fontWeight: FontWeight.w700,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: InkWell(
                        onTap: () => _pickDailyBoundary(isFrom: false),
                        borderRadius: BorderRadius.circular(8),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 8,
                          ),
                          decoration: BoxDecoration(
                            color: isDark
                                ? AppColors.darkSurface
                                : AppColors.lightSurface,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: isDark
                                  ? AppColors.darkBorder
                                  : AppColors.lightBorder,
                            ),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.event_rounded,
                                  size: 14, color: AppColors.primaryBlueLight),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  'To: $_dailyTo',
                                  style: const TextStyle(
                                    fontSize: 11.5,
                                    fontWeight: FontWeight.w700,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),

              if (auth.canViewAllTeams) ...[
                const SizedBox(height: 8),
                FilterPillGroup(
                  items: teamPills,
                  selectedKey: _dailyTeam,
                  onSelected: (key) {
                    setState(() {
                      _dailyTeam = key;
                      _dailySpecialistId = null;
                    });
                    _fetchDailySummary();
                  },
                ),
              ],

              const SizedBox(height: 8),
              FilterPillGroup(
                items: typePills,
                selectedKey: _dailyType,
                onSelected: (key) {
                  setState(() => _dailyType = key);
                  _fetchDailySummary();
                },
              ),

              if (showHub && specialists.length > 1) ...[
                const SizedBox(height: 8),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 14),
                  child: Row(
                    children: [
                      Expanded(
                        child: DropdownButtonFormField<int?>(
                          initialValue: specialists.any(
                                  (s) => s.userId == _dailySpecialistId)
                              ? _dailySpecialistId
                              : null,
                          isDense: true,
                          decoration: const InputDecoration(
                            labelText: 'Filter by Specialist',
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                          ),
                          items: [
                            const DropdownMenuItem<int?>(
                              value: null,
                              child: Text(
                                'All Specialists',
                                style: TextStyle(fontSize: 12),
                              ),
                            ),
                            ...specialists.map(
                              (s) => DropdownMenuItem<int?>(
                                value: s.userId,
                                child: Text(
                                  '${s.name} (${s.team})',
                                  style: const TextStyle(fontSize: 12),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ),
                          ],
                          onChanged: (val) {
                            setState(() => _dailySpecialistId = val);
                            _fetchDailySummary();
                          },
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),

        // Body
        Expanded(
          child: RefreshIndicator(
            onRefresh: _fetchDailySummary,
            child: tt.isLoadingDaily && data == null
                ? const Center(child: CircularProgressIndicator())
                : ListView(
                    padding: const EdgeInsets.all(14),
                    children: [
                      // Period Summary KPIs
                      Row(
                        children: [
                          Expanded(
                            child: _buildMiniStatCard(
                              isDark,
                              'PERIOD TOTAL',
                              summary?.totalFormatted ?? '0h 00m',
                              AppColors.primaryBlueLight,
                              Icons.schedule_rounded,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _buildMiniStatCard(
                              isDark,
                              'REGULAR',
                              summary?.regularFormatted ?? '0h 00m',
                              AppColors.success,
                              Icons.work_history_rounded,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _buildMiniStatCard(
                              isDark,
                              'OVERTIME',
                              summary?.extraFormatted ?? '0h 00m',
                              Colors.amber,
                              Icons.bolt_rounded,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 2),
                        child: Text(
                          '${summary?.daysCount ?? 0} Active Day(s) • ${summary?.sessionsCount ?? 0} Session(s)'
                          '${showHub ? ' • ${summary?.snapshotsCount ?? 0} Snapshot(s)' : ''}',
                          style: TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w600,
                            color: isDark
                                ? AppColors.textMuted
                                : AppColors.textDarkMuted,
                          ),
                        ),
                      ),
                      const SizedBox(height: 14),

                      // Clickable Daily Totals Strip
                      if (totals.isNotEmpty) ...[
                        Text(
                          'DAILY TOTALS (TAP DAY TO DRILL DOWN)',
                          style: TextStyle(
                            fontSize: 10.5,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.6,
                            color: isDark
                                ? AppColors.textDim
                                : AppColors.textDarkMuted,
                          ),
                        ),
                        const SizedBox(height: 8),
                        SizedBox(
                          height: 62,
                          child: ListView.separated(
                            scrollDirection: Axis.horizontal,
                            itemCount: totals.length,
                            separatorBuilder: (_, _) =>
                                const SizedBox(width: 8),
                            itemBuilder: (_, idx) {
                              final dt = totals[idx];
                              final selected = _dailyFrom == dt.date &&
                                  _dailyTo == dt.date;
                              return InkWell(
                                borderRadius: BorderRadius.circular(12),
                                onTap: () {
                                  setState(() {
                                    _dailyPreset = 'custom';
                                    _dailyFrom = dt.date;
                                    _dailyTo = dt.date;
                                  });
                                  _fetchDailySummary();
                                },
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                    vertical: 8,
                                  ),
                                  decoration: BoxDecoration(
                                    color: selected
                                        ? AppColors.primaryBlue
                                        : (isDark
                                            ? AppColors.darkCard
                                            : AppColors.lightCard),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(
                                      color: selected
                                          ? AppColors.primaryBlueLight
                                          : (dt.isToday
                                              ? AppColors.success
                                              : (isDark
                                                  ? AppColors.darkBorder
                                                  : AppColors.lightBorder)),
                                    ),
                                  ),
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        dt.isToday
                                            ? 'Today • ${dt.date}'
                                            : dt.dateFormatted,
                                        style: TextStyle(
                                          fontSize: 10.5,
                                          fontWeight: FontWeight.w700,
                                          color: selected
                                              ? Colors.white70
                                              : (isDark
                                                  ? AppColors.textMuted
                                                  : AppColors.textDarkMuted),
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        '${dt.totalFormatted} • ${dt.sessionsCount}s',
                                        style: TextStyle(
                                          fontSize: 12.5,
                                          fontWeight: FontWeight.w900,
                                          color: selected
                                              ? Colors.white
                                              : AppColors.primaryBlueLight,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],

                      // Specialist Day-by-Day Records
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'DATE-WISE RECORDS (${records.length})',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 0.7,
                              color: isDark
                                  ? AppColors.textMuted
                                  : AppColors.textDarkMuted,
                            ),
                          ),
                          if (_dailyPreset == 'today' ||
                              _dailyPreset == 'yesterday' ||
                              _dailyPreset == 'custom')
                            TextButton(
                              onPressed: () {
                                setState(() {
                                  _applyPresetDates('30d', triggerFetch: false);
                                });
                                _fetchDailySummary();
                              },
                              child: const Text(
                                'Reset to 30d',
                                style: TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 8),

                      if (records.isEmpty)
                        const EmptyStateWidget(
                          icon: Icons.event_busy_rounded,
                          title: 'No Time Records Found',
                          description:
                              'No tracked sessions match the selected date range or filters.',
                        )
                      else
                        ...records.map(
                          (rec) => _buildDailyRecordCard(
                            isDark,
                            rec,
                            showHub,
                          ),
                        ),
                    ],
                  ),
          ),
        ),
      ],
    );
  }

  Widget _buildDailyRecordCard(
    bool isDark,
    DailyTimeRecordItem rec,
    bool showHub,
  ) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: rec.activeCount > 0
              ? AppColors.success
              : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 17,
                backgroundImage:
                    rec.avatar.isNotEmpty ? NetworkImage(rec.avatar) : null,
                child: rec.avatar.isEmpty
                    ? Text(
                        rec.specialistName.isNotEmpty
                            ? rec.specialistName[0].toUpperCase()
                            : 'S',
                        style: const TextStyle(fontWeight: FontWeight.w800),
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
                            rec.specialistName,
                            style: const TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: 13.5,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 6),
                        TeamBadge(team: rec.teamName, isSmall: true),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      rec.isToday ? 'Today (${rec.date})' : rec.dateFormatted,
                      style: TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.w600,
                        color: rec.isToday
                            ? AppColors.success
                            : (isDark
                                ? AppColors.textDim
                                : AppColors.textDarkMuted),
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    rec.totalFormatted,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                      color: AppColors.primaryBlueLight,
                    ),
                  ),
                  Text(
                    '${rec.totalHoursDecimal.toStringAsFixed(2)}h',
                    style: TextStyle(
                      fontSize: 10.5,
                      color: isDark
                          ? AppColors.textDim
                          : AppColors.textDarkMuted,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 6,
            children: [
              _buildChipPill(
                'Reg: ${rec.regularFormatted}',
                AppColors.success,
              ),
              if (rec.extraSeconds > 0)
                _buildChipPill(
                  '⚡ OT: ${rec.extraFormatted}',
                  Colors.amber,
                ),
              _buildChipPill(
                '${rec.sessionsCount} Session(s)',
                AppColors.primaryBlueLight,
              ),
              if (showHub && rec.snapshotsCount > 0)
                _buildChipPill(
                  '📸 ${rec.snapshotsCount} Snaps',
                  AppColors.purpleAccent,
                ),
              if (rec.activeCount > 0)
                _buildChipPill('● LIVE NOW', AppColors.success),
            ],
          ),
          if (rec.orders.isNotEmpty) ...[
            const SizedBox(height: 10),
            const Divider(height: 1),
            const SizedBox(height: 8),
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: rec.orders.map((ord) {
                return Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: isDark
                        ? AppColors.darkSurface
                        : AppColors.lightSurface,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color:
                          isDark ? AppColors.darkBorder : AppColors.lightBorder,
                    ),
                  ),
                  child: Text(
                    '${ord.orderId} • ${ord.timeFormatted}'
                    '${ord.clientName.isNotEmpty ? ' (${ord.clientName})' : ''}',
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                );
              }).toList(),
            ),
          ],
          const SizedBox(height: 8),
          Align(
            alignment: Alignment.centerRight,
            child: TextButton.icon(
              style: TextButton.styleFrom(
                visualDensity: VisualDensity.compact,
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              ),
              icon: const Icon(Icons.list_alt_rounded, size: 15),
              label: const Text(
                'Inspect Day Sessions →',
                style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700),
              ),
              onPressed: () {
                setState(() {
                  _sessDate = rec.date;
                });
                _fetchSessions();
                _tabController?.animateTo(2);
              },
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _pickDailyBoundary({required bool isFrom}) async {
    final initStr = isFrom ? _dailyFrom : _dailyTo;
    final initial = DateTime.tryParse(initStr) ?? DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2026, 1, 1),
      lastDate: DateTime.now().add(const Duration(days: 1)),
    );
    if (picked != null) {
      final fmt = DateFormat('yyyy-MM-dd');
      setState(() {
        _dailyPreset = 'custom';
        if (isFrom) {
          _dailyFrom = fmt.format(picked);
        } else {
          _dailyTo = fmt.format(picked);
        }
      });
      _fetchDailySummary();
    }
  }

  // =========================================================================
  // TAB 3: SESSIONS LOG
  // =========================================================================
  Widget _buildSessionsTab(
    bool isDark,
    AuthProvider auth,
    TimeTrackerProvider tt,
    bool showHub,
  ) {
    final canViewAllSquads = auth.isAdmin || auth.isExecutive;
    final userSquad = (auth.currentUser?.team.isNotEmpty == true) ? auth.currentUser!.team : 'My Squad';
    final teamPills = canViewAllSquads
        ? [
            FilterPillItem(keyName: 'all', label: 'All Squads'),
            FilterPillItem(keyName: 'WpTitans', label: 'WpTitans'),
            FilterPillItem(keyName: 'WpNexus', label: 'WpNexus'),
            FilterPillItem(keyName: 'WpCraft', label: 'WpCraft'),
          ]
        : [
            FilterPillItem(keyName: userSquad, label: userSquad),
          ];

    final typePills = [
      FilterPillItem(keyName: 'all', label: 'All Shifts'),
      FilterPillItem(keyName: 'regular', label: 'Regular'),
      FilterPillItem(keyName: 'extra_time', label: '⚡ Overtime'),
    ];

    return Column(
      children: [
        // Filter bar
        Container(
          padding: const EdgeInsets.fromLTRB(0, 10, 0, 10),
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkCard : AppColors.lightCard,
            border: Border(
              bottom: BorderSide(
                color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
              ),
            ),
          ),
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14),
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _sessSearchCtrl,
                        onSubmitted: (_) => _fetchSessions(),
                        decoration: InputDecoration(
                          hintText: 'Search Order ID, client, specialist...',
                          prefixIcon: const Icon(Icons.search_rounded, size: 18),
                          suffixIcon: _sessSearchCtrl.text.isNotEmpty
                              ? IconButton(
                                  icon: const Icon(Icons.clear_rounded, size: 16),
                                  onPressed: () {
                                    _sessSearchCtrl.clear();
                                    _fetchSessions();
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
                        final picked = await showDatePicker(
                          context: context,
                          initialDate:
                              DateTime.tryParse(_sessDate) ?? DateTime.now(),
                          firstDate: DateTime(2026, 1, 1),
                          lastDate: DateTime.now().add(const Duration(days: 1)),
                        );
                        if (picked != null) {
                          setState(() {
                            _sessDate = DateFormat('yyyy-MM-dd').format(picked);
                          });
                          _fetchSessions();
                        }
                      },
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 11,
                        ),
                        decoration: BoxDecoration(
                          color: _sessDate.isNotEmpty
                              ? AppColors.primaryBlue.withValues(alpha: 0.14)
                              : (isDark
                                  ? AppColors.darkSurface
                                  : AppColors.lightSurface),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: _sessDate.isNotEmpty
                                ? AppColors.primaryBlueLight
                                : (isDark
                                    ? AppColors.darkBorder
                                    : AppColors.lightBorder),
                          ),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.calendar_today_rounded,
                                size: 15, color: AppColors.primaryBlueLight),
                            if (_sessDate.isNotEmpty) ...[
                              const SizedBox(width: 5),
                              Text(
                                _sessDate,
                                style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(width: 4),
                              GestureDetector(
                                onTap: () {
                                  setState(() => _sessDate = '');
                                  _fetchSessions();
                                },
                                child: const Icon(Icons.close_rounded, size: 14),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              if (auth.canViewAllTeams) ...[
                const SizedBox(height: 8),
                FilterPillGroup(
                  items: teamPills,
                  selectedKey: _sessTeam,
                  onSelected: (key) {
                    setState(() => _sessTeam = key);
                    _fetchSessions();
                  },
                ),
              ],
              const SizedBox(height: 8),
              FilterPillGroup(
                items: typePills,
                selectedKey: _sessType,
                onSelected: (key) {
                  setState(() => _sessType = key);
                  _fetchSessions();
                },
              ),
            ],
          ),
        ),
        Expanded(
          child: RefreshIndicator(
            onRefresh: _fetchSessions,
            child: tt.isLoadingSessions && tt.sessions.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : tt.sessions.isEmpty
                    ? const EmptyStateWidget(
                        icon: Icons.history_toggle_off_rounded,
                        title: 'No Tracking Sessions Found',
                        description:
                            'No tracking sessions match the current filters.',
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.all(14),
                        itemCount: tt.sessions.length,
                        itemBuilder: (_, idx) {
                          final s = tt.sessions[idx];
                          final isExtra = s.sessionType == 'extra_time';
                          final isActive = s.status == 'active';
                          return Container(
                            margin: const EdgeInsets.only(bottom: 10),
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: isDark
                                  ? AppColors.darkCard
                                  : AppColors.lightCard,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(
                                color: isActive
                                    ? AppColors.success
                                    : (isDark
                                        ? AppColors.darkBorder
                                        : AppColors.lightBorder),
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Expanded(
                                      child: Row(
                                        children: [
                                          CircleAvatar(
                                            radius: 15,
                                            backgroundImage: s.avatar.isNotEmpty
                                                ? NetworkImage(s.avatar)
                                                : null,
                                            child: s.avatar.isEmpty
                                                ? Text(
                                                    s.specialistName.isNotEmpty
                                                        ? s.specialistName[0]
                                                            .toUpperCase()
                                                        : 'S',
                                                    style: const TextStyle(
                                                        fontSize: 12),
                                                  )
                                                : null,
                                          ),
                                          const SizedBox(width: 8),
                                          Expanded(
                                            child: Column(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.start,
                                              children: [
                                                Text(
                                                  s.specialistName,
                                                  style: const TextStyle(
                                                    fontWeight: FontWeight.w800,
                                                    fontSize: 13,
                                                  ),
                                                  overflow:
                                                      TextOverflow.ellipsis,
                                                ),
                                                Text(
                                                  '${s.workDate} • ${s.teamName}',
                                                  style: TextStyle(
                                                    fontSize: 11,
                                                    color: isDark
                                                        ? AppColors.textDim
                                                        : AppColors
                                                            .textDarkMuted,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 10,
                                        vertical: 5,
                                      ),
                                      decoration: BoxDecoration(
                                        color: AppColors.primaryBlue
                                            .withValues(alpha: 0.14),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text(
                                        s.timeFormatted,
                                        style: const TextStyle(
                                          fontWeight: FontWeight.w900,
                                          fontSize: 13,
                                          color: AppColors.primaryBlueLight,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 10),
                                Wrap(
                                  spacing: 6,
                                  runSpacing: 6,
                                  children: [
                                    _buildChipPill(
                                      isExtra ? '⚡ OVERTIME' : 'REGULAR',
                                      isExtra
                                          ? Colors.amber
                                          : AppColors.primaryBlueLight,
                                    ),
                                    if (s.orderId.isNotEmpty)
                                      _buildChipPill(
                                        '${s.orderId}${s.clientName.isNotEmpty ? " (${s.clientName})" : ""}',
                                        AppColors.cyanAccent,
                                      ),
                                    _buildChipPill(
                                      isActive ? '● ACTIVE' : 'COMPLETED',
                                      isActive
                                          ? AppColors.success
                                          : Colors.grey,
                                    ),
                                    if (s.loggedToReport)
                                      _buildChipPill(
                                        '✓ Logged to Standup',
                                        AppColors.success,
                                      ),
                                    if (showHub)
                                      _buildChipPill(
                                        '📸 ${s.snapshotsCount} Snaps',
                                        AppColors.purpleAccent,
                                      ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Expanded(
                                      child: Text(
                                        'Start: ${s.startTime}${s.endTime.isNotEmpty ? ' • End: ${s.endTime}' : ''}',
                                        style: TextStyle(
                                          fontSize: 10.5,
                                          color: isDark
                                              ? AppColors.textDim
                                              : AppColors.textDarkMuted,
                                        ),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                    if (showHub && s.snapshotsCount > 0)
                                      TextButton(
                                        style: TextButton.styleFrom(
                                          visualDensity: VisualDensity.compact,
                                          padding: const EdgeInsets.symmetric(
                                            horizontal: 8,
                                          ),
                                        ),
                                        onPressed: () {
                                          setState(() {
                                            _snapSessionId = s.id;
                                          });
                                          _fetchSnapshots();
                                          _tabController?.animateTo(3);
                                        },
                                        child: const Text(
                                          'View Snaps →',
                                          style: TextStyle(
                                            fontSize: 11.5,
                                            fontWeight: FontWeight.w700,
                                          ),
                                        ),
                                      ),
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

  // =========================================================================
  // TAB 4: SCREENSHOT GALLERY & LIVE HUB (LEADERS / ADMINS / EXEC ONLY)
  // =========================================================================
  Widget _buildScreenshotHubTab(
    bool isDark,
    AuthProvider auth,
    TimeTrackerProvider tt,
  ) {
    final stats = tt.stats;
    final activeLive = stats?.activeSessions ?? [];

    final canViewAllSquads = auth.isAdmin || auth.isExecutive;
    final userSquad = (auth.currentUser?.team.isNotEmpty == true) ? auth.currentUser!.team : 'My Squad';
    final teamPills = canViewAllSquads
        ? [
            FilterPillItem(keyName: 'all', label: 'All Squads'),
            FilterPillItem(keyName: 'WpTitans', label: 'WpTitans'),
            FilterPillItem(keyName: 'WpNexus', label: 'WpNexus'),
            FilterPillItem(keyName: 'WpCraft', label: 'WpCraft'),
          ]
        : [
            FilterPillItem(keyName: userSquad, label: userSquad),
          ];

    final typePills = [
      FilterPillItem(keyName: 'all', label: 'All Shifts'),
      FilterPillItem(keyName: 'regular', label: 'Regular'),
      FilterPillItem(keyName: 'extra_time', label: '⚡ Overtime'),
    ];

    return Column(
      children: [
        // Hub Header & Filter Controls
        Container(
          padding: const EdgeInsets.fromLTRB(0, 10, 0, 10),
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkCard : AppColors.lightCard,
            border: Border(
              bottom: BorderSide(
                color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
              ),
            ),
          ),
          child: Column(
            children: [
              // Hub Summary KPI Strip
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14),
                child: Row(
                  children: [
                    Expanded(
                      child: _buildMiniStatCard(
                        isDark,
                        'LIVE NOW',
                        '${stats?.activeSessionsCount ?? 0}',
                        AppColors.success,
                        Icons.sensors_rounded,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildMiniStatCard(
                        isDark,
                        'SCREENSHOTS',
                        '${stats?.totalSnapshots ?? tt.snapshots.length}',
                        AppColors.purpleAccent,
                        Icons.photo_camera_rounded,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildMiniStatCard(
                        isDark,
                        'TODAY WORK',
                        stats?.todayHoursFormatted ?? '0h 00m',
                        AppColors.primaryBlueLight,
                        Icons.today_rounded,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 10),

              // Search & Date Row
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14),
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _snapSearchCtrl,
                        onSubmitted: (_) => _fetchSnapshots(),
                        decoration: InputDecoration(
                          hintText: 'Search specialist, order ID, note...',
                          prefixIcon: const Icon(Icons.search_rounded, size: 18),
                          suffixIcon: _snapSearchCtrl.text.isNotEmpty
                              ? IconButton(
                                  icon: const Icon(Icons.clear_rounded, size: 16),
                                  onPressed: () {
                                    _snapSearchCtrl.clear();
                                    _fetchSnapshots();
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
                        final picked = await showDatePicker(
                          context: context,
                          initialDate:
                              DateTime.tryParse(_snapDate) ?? DateTime.now(),
                          firstDate: DateTime(2026, 1, 1),
                          lastDate: DateTime.now().add(const Duration(days: 1)),
                        );
                        if (picked != null) {
                          setState(() {
                            _snapDate = DateFormat('yyyy-MM-dd').format(picked);
                          });
                          _fetchSnapshots();
                        }
                      },
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 11,
                        ),
                        decoration: BoxDecoration(
                          color: _snapDate.isNotEmpty
                              ? AppColors.primaryBlue.withValues(alpha: 0.14)
                              : (isDark
                                  ? AppColors.darkSurface
                                  : AppColors.lightSurface),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: _snapDate.isNotEmpty
                                ? AppColors.primaryBlueLight
                                : (isDark
                                    ? AppColors.darkBorder
                                    : AppColors.lightBorder),
                          ),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.calendar_today_rounded,
                                size: 15, color: AppColors.primaryBlueLight),
                            if (_snapDate.isNotEmpty) ...[
                              const SizedBox(width: 5),
                              Text(
                                _snapDate,
                                style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(width: 4),
                              GestureDetector(
                                onTap: () {
                                  setState(() => _snapDate = '');
                                  _fetchSnapshots();
                                },
                                child: const Icon(Icons.close_rounded, size: 14),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              if (auth.canViewAllTeams) ...[
                const SizedBox(height: 8),
                FilterPillGroup(
                  items: teamPills,
                  selectedKey: _snapTeam,
                  onSelected: (key) {
                    setState(() => _snapTeam = key);
                    _fetchSnapshots();
                  },
                ),
              ],
              const SizedBox(height: 8),
              FilterPillGroup(
                items: typePills,
                selectedKey: _snapType,
                onSelected: (key) {
                  setState(() => _snapType = key);
                  _fetchSnapshots();
                },
              ),

              if (_snapSessionId != null) ...[
                const SizedBox(height: 8),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 14),
                  child: Row(
                    children: [
                      Chip(
                        label: Text('Session #$_snapSessionId'),
                        onDeleted: () {
                          setState(() => _snapSessionId = null);
                          _fetchSnapshots();
                        },
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),

        Expanded(
          child: RefreshIndicator(
            onRefresh: () async {
              await Future.wait([
                tt.fetchStats(team: _snapTeam),
                _fetchSnapshots(),
              ]);
            },
            child: ListView(
              padding: const EdgeInsets.all(14),
              children: [
                // Live Specialists Working Now Strip
                if (activeLive.isNotEmpty) ...[
                  Text(
                    'LIVE SPECIALISTS WORKING NOW (${activeLive.length})',
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.7,
                      color: AppColors.success,
                    ),
                  ),
                  const SizedBox(height: 8),
                  SizedBox(
                    height: 82,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemCount: activeLive.length,
                      separatorBuilder: (_, _) => const SizedBox(width: 8),
                      itemBuilder: (_, idx) {
                        final ls = activeLive[idx];
                        return Container(
                          width: 220,
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: AppColors.success.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: AppColors.success.withValues(alpha: 0.35),
                            ),
                          ),
                          child: Row(
                            children: [
                              CircleAvatar(
                                radius: 18,
                                backgroundImage: ls.avatar.isNotEmpty
                                    ? NetworkImage(ls.avatar)
                                    : null,
                                child: ls.avatar.isEmpty
                                    ? Text(ls.specialistName.isNotEmpty
                                        ? ls.specialistName[0].toUpperCase()
                                        : 'S')
                                    : null,
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Text(
                                      ls.specialistName,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w800,
                                        fontSize: 12,
                                      ),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    Text(
                                      '${ls.orderId.isNotEmpty ? ls.orderId : "#Work"} • ${ls.timeFormatted}',
                                      style: const TextStyle(
                                        fontSize: 10.5,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.success,
                                      ),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    Text(
                                      '${ls.snapshotsCount} snap(s) • ${ls.teamName}',
                                      style: TextStyle(
                                        fontSize: 10,
                                        color: isDark
                                            ? AppColors.textDim
                                            : AppColors.textDarkMuted,
                                      ),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                if (tt.isLoadingSnapshots && tt.snapshots.isEmpty)
                  const Padding(
                    padding: EdgeInsets.all(40),
                    child: Center(child: CircularProgressIndicator()),
                  )
                else if (tt.snapshots.isEmpty)
                  const EmptyStateWidget(
                    icon: Icons.photo_camera_back_rounded,
                    title: 'No Screenshots Found',
                    description:
                        'No captured work screenshots match the selected filters.',
                  )
                else
                  GridView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      crossAxisSpacing: 10,
                      mainAxisSpacing: 10,
                      childAspectRatio: 0.80,
                    ),
                    itemCount: tt.snapshots.length,
                    itemBuilder: (_, index) {
                      return _buildSnapshotGridCard(
                        isDark,
                        tt.snapshots[index],
                        auth,
                        tt,
                      );
                    },
                  ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSnapshotGridCard(
    bool isDark,
    TimeSnapshotItem item,
    AuthProvider auth,
    TimeTrackerProvider tt,
  ) {
    final isExtra = item.sessionType == 'extra_time';

    return Container(
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: () => _openLightboxViewer(context, item, auth, tt),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Thumbnail
              Expanded(
                child: ClipRRect(
                  borderRadius:
                      const BorderRadius.vertical(top: Radius.circular(14)),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      if (item.url.isNotEmpty)
                        Image.network(
                          item.url,
                          fit: BoxFit.cover,
                          errorBuilder: (_, _, _) => Container(
                            color: isDark
                                ? Colors.grey.shade900
                                : Colors.grey.shade200,
                            child: const Icon(
                              Icons.broken_image_rounded,
                              color: Colors.grey,
                              size: 32,
                            ),
                          ),
                        )
                      else
                        Container(
                          color: isDark
                              ? Colors.grey.shade900
                              : Colors.grey.shade200,
                          child: const Icon(Icons.image_not_supported_rounded),
                        ),
                      Positioned(
                        top: 6,
                        left: 6,
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 6,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: (isExtra
                                    ? Colors.amber.shade700
                                    : AppColors.primaryBlue)
                                .withValues(alpha: 0.92),
                            borderRadius: BorderRadius.circular(5),
                          ),
                          child: Text(
                            isExtra ? '⚡ OT' : 'REG',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 9.5,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                      ),
                      Positioned(
                        top: 6,
                        right: 6,
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 6,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.black.withValues(alpha: 0.65),
                            borderRadius: BorderRadius.circular(5),
                          ),
                          child: Text(
                            item.elapsedLabel,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 9.5,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 10,
                          backgroundImage: item.avatar.isNotEmpty
                              ? NetworkImage(item.avatar)
                              : null,
                          child: item.avatar.isEmpty
                              ? Text(
                                  item.specialistName.isNotEmpty
                                      ? item.specialistName[0].toUpperCase()
                                      : 'S',
                                  style: const TextStyle(fontSize: 9),
                                )
                              : null,
                        ),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            item.specialistName,
                            style: const TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: 11.5,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      item.orderId.isNotEmpty
                          ? '${item.orderId} • ${item.teamName}'
                          : item.teamName,
                      style: const TextStyle(
                        fontSize: 10.5,
                        color: AppColors.primaryBlueLight,
                        fontWeight: FontWeight.w700,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      item.formattedTime,
                      style: TextStyle(
                        fontSize: 9.5,
                        color: isDark
                            ? AppColors.textDim
                            : AppColors.textDarkMuted,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // =========================================================================
  // LIGHTBOX & AUDIT CONTROLS (DELETE / EDIT RESTRICTED TO OPERATIONS MANAGER)
  // =========================================================================
  void _openLightboxViewer(
    BuildContext context,
    TimeSnapshotItem item,
    AuthProvider auth,
    TimeTrackerProvider tt,
  ) {
    final isManager = auth.isOperationsManager; // Never Executive, never Leader

    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: const Color(0xFF0F172A),
        insetPadding: const EdgeInsets.all(14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 8, 8),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${item.specialistName} • ${item.orderId.isNotEmpty ? item.orderId : "#General"}',
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                            fontSize: 14,
                          ),
                        ),
                        Text(
                          '${item.formattedTime} (${item.elapsedLabel}) • ${item.teamName}',
                          style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 11,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close_rounded, color: Colors.white),
                    onPressed: () => Navigator.of(ctx).pop(),
                  ),
                ],
              ),
            ),

            // Zoomable Screenshot
            Flexible(
              child: ConstrainedBox(
                constraints: BoxConstraints(
                  maxHeight: MediaQuery.of(context).size.height * 0.55,
                ),
                child: InteractiveViewer(
                  maxScale: 4.0,
                  child: item.url.isNotEmpty
                      ? Image.network(
                          item.url,
                          fit: BoxFit.contain,
                          errorBuilder: (_, _, _) => const Padding(
                            padding: EdgeInsets.all(40),
                            child: Icon(
                              Icons.broken_image_rounded,
                              color: Colors.white54,
                              size: 48,
                            ),
                          ),
                        )
                      : const SizedBox(height: 200),
                ),
              ),
            ),

            // Notes & Audit Actions
            Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (item.notes.isNotEmpty) ...[
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        'Audit Note: ${item.notes}',
                        style: const TextStyle(
                          color: Colors.amberAccent,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                  ],
                  if (isManager)
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            style: OutlinedButton.styleFrom(
                              foregroundColor: Colors.white,
                              side: const BorderSide(color: Colors.white30),
                            ),
                            icon: const Icon(Icons.edit_note_rounded, size: 17),
                            label: const Text('Audit Note'),
                            onPressed: () {
                              Navigator.of(ctx).pop();
                              _showEditAuditNoteModal(context, item, tt);
                            },
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.danger,
                              foregroundColor: Colors.white,
                            ),
                            icon: const Icon(Icons.delete_outline_rounded,
                                size: 17),
                            label: const Text('Delete'),
                            onPressed: () {
                              Navigator.of(ctx).pop();
                              _confirmDeleteSnapshot(context, item.id, tt);
                            },
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
    );
  }

  void _showEditAuditNoteModal(
    BuildContext context,
    TimeSnapshotItem item,
    TimeTrackerProvider tt,
  ) {
    final noteCtrl = TextEditingController(text: item.notes);

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Edit Screenshot Audit Note'),
        content: TextField(
          controller: noteCtrl,
          maxLines: 3,
          decoration: const InputDecoration(
            hintText: 'Enter compliance note or review remark...',
            border: OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.of(ctx).pop();
              final ok = await tt.updateSnapshotNotes(
                item.id,
                noteCtrl.text.trim(),
              );
              if (mounted) {
                ScaffoldMessenger.of(this.context).showSnackBar(
                  SnackBar(
                    content: Text(
                      ok
                          ? 'Audit note updated!'
                          : 'Failed to update audit note.',
                    ),
                    backgroundColor: ok ? AppColors.success : AppColors.danger,
                  ),
                );
              }
            },
            child: const Text('Save Note'),
          ),
        ],
      ),
    );
  }

  void _confirmDeleteSnapshot(
    BuildContext context,
    int snapshotId,
    TimeTrackerProvider tt,
  ) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Screenshot?'),
        content: const Text(
          'Permanently delete this captured screenshot from storage? This cannot be undone.',
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
              final ok = await tt.deleteSnapshot(snapshotId);
              if (mounted) {
                if (ok) {
                  tt.fetchStats(team: _snapTeam, silent: true);
                }
                ScaffoldMessenger.of(this.context).showSnackBar(
                  SnackBar(
                    content: Text(
                      ok ? 'Screenshot deleted.' : 'Failed to delete screenshot.',
                    ),
                    backgroundColor: ok ? AppColors.success : AppColors.danger,
                  ),
                );
              }
            },
            child: const Text('Delete'),
          ),
        ],
      ),
    );
  }

  // =========================================================================
  // START / STOP / EXTRA TIME MODALS
  // =========================================================================
  void _showStartTrackerModal(
    BuildContext context, {
    required String sessionType,
  }) {
    final projects = Provider.of<ProjectsProvider>(context, listen: false)
        .projects
        .where((p) => p.isActive)
        .toList();

    ProjectItem? selectedProject = projects.isNotEmpty ? projects.first : null;
    final orderCtrl = TextEditingController(
      text: selectedProject?.orderId ?? '',
    );
    final clientCtrl = TextEditingController(
      text: selectedProject?.buyerUsername ?? '',
    );

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) => Padding(
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
                Text(
                  sessionType == 'extra_time'
                      ? '⚡ Start Overtime Session (After 6:20 PM)'
                      : '▶ Start Work Time Tracker',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 14),
                if (projects.isNotEmpty) ...[
                  DropdownButtonFormField<int?>(
                    initialValue: selectedProject?.id,
                    isExpanded: true,
                    decoration: const InputDecoration(
                      labelText: 'Select Active Project',
                    ),
                    items: [
                      const DropdownMenuItem<int?>(
                        value: null,
                        child: Text('Manual / General Work'),
                      ),
                      ...projects.map(
                        (p) => DropdownMenuItem<int?>(
                          value: p.id,
                          child: Text(
                            '${p.orderId} - ${p.buyerUsername} (${p.title})',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ),
                    ],
                    onChanged: (pid) {
                      setModalState(() {
                        selectedProject = projects
                            .where((p) => p.id == pid)
                            .firstOrNull;
                        orderCtrl.text = selectedProject?.orderId ?? '';
                        clientCtrl.text =
                            selectedProject?.buyerUsername ?? '';
                      });
                    },
                  ),
                  const SizedBox(height: 12),
                ],
                TextField(
                  controller: orderCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Order ID',
                    hintText: 'e.g. #FO12345678',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: clientCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Client / Buyer Name',
                    hintText: 'e.g. john_doe',
                  ),
                ),
                const SizedBox(height: 18),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: sessionType == 'extra_time'
                          ? Colors.amber.shade700
                          : AppColors.success,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 13),
                    ),
                    onPressed: () async {
                      final tt = Provider.of<TimeTrackerProvider>(
                        context,
                        listen: false,
                      );
                      Navigator.of(ctx).pop();
                      final res = await tt.startSession(
                        projectId: selectedProject?.id,
                        orderId: orderCtrl.text.trim(),
                        clientName: clientCtrl.text.trim(),
                        profileName: selectedProject?.profileName ?? '',
                        sessionType: sessionType,
                      );
                      if (res.success) {
                        _refreshAll();
                      }
                      if (mounted) {
                        ScaffoldMessenger.of(this.context).showSnackBar(
                          SnackBar(
                            content: Text(res.message),
                            backgroundColor: res.success
                                ? AppColors.success
                                : AppColors.danger,
                          ),
                        );
                      }
                    },
                    child: const Text(
                      'Start Timer Now',
                      style: TextStyle(fontWeight: FontWeight.w800),
                    ),
                  ),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showStopTrackerDialog(
    BuildContext context,
    ActiveTrackingSession active,
  ) {
    final tasksCtrl = TextEditingController();
    final reasonCtrl = TextEditingController();
    bool logToDailyReport = true;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDlgState) => AlertDialog(
          title: const Text('Stop & Log Deliverables'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Order ${active.orderId.isNotEmpty ? active.orderId : "#Work"} • Elapsed: ${TimeTrackerProvider.formatHmsCompact(active.liveElapsedSeconds)}',
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 12.5,
                    color: AppColors.primaryBlueLight,
                  ),
                ),
                const SizedBox(height: 12),
                CheckboxListTile(
                  value: logToDailyReport,
                  onChanged: (v) {
                    setDlgState(() => logToDailyReport = v ?? true);
                  },
                  contentPadding: EdgeInsets.zero,
                  title: const Text(
                    'Sync time to Daily Standup Report',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                  ),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: tasksCtrl,
                  maxLines: 3,
                  decoration: const InputDecoration(
                    labelText: 'Deliverables / Tasks Completed',
                    hintText: 'Describe what you completed in this session...',
                    border: OutlineInputBorder(),
                  ),
                ),
                if (active.isExtraTime) ...[
                  const SizedBox(height: 12),
                  TextField(
                    controller: reasonCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Overtime Reason (After 6:20 PM)',
                      hintText: 'e.g. Urgent client deadline / revision',
                      border: OutlineInputBorder(),
                    ),
                  ),
                ],
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
              onPressed: () async {
                final tt = Provider.of<TimeTrackerProvider>(
                  context,
                  listen: false,
                );
                Navigator.of(ctx).pop();
                final res = await tt.stopSession(
                  logToDailyReport: logToDailyReport,
                  tasksCompleted: tasksCtrl.text.trim(),
                  extraTimeReason: reasonCtrl.text.trim(),
                );
                if (res.success) {
                  _refreshAll();
                }
                if (mounted) {
                  ScaffoldMessenger.of(this.context).showSnackBar(
                    SnackBar(
                      content: Text(res.message),
                      backgroundColor:
                          res.success ? AppColors.success : AppColors.danger,
                    ),
                  );
                }
              },
              child: const Text('Stop & Save'),
            ),
          ],
        ),
      ),
    );
  }

  void _showExtraTimeReportModal(BuildContext context) {
    final projects = Provider.of<ProjectsProvider>(context, listen: false)
        .projects
        .where((p) => p.isActive)
        .toList();

    ProjectItem? selectedProject = projects.isNotEmpty ? projects.first : null;
    final orderCtrl = TextEditingController(
      text: selectedProject?.orderId ?? '',
    );
    final clientCtrl = TextEditingController(
      text: selectedProject?.buyerUsername ?? '',
    );
    final hoursCtrl = TextEditingController(text: '1.5');
    final reasonCtrl = TextEditingController();
    final tasksCtrl = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) => Padding(
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
                const Text(
                  '⚡ Submit Extra Time (Overtime) Report',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 14),
                if (projects.isNotEmpty) ...[
                  DropdownButtonFormField<int?>(
                    initialValue: selectedProject?.id,
                    isExpanded: true,
                    decoration: const InputDecoration(
                      labelText: 'Linked Order (Optional)',
                    ),
                    items: [
                      const DropdownMenuItem<int?>(
                        value: null,
                        child: Text('Manual / General Agency Overtime'),
                      ),
                      ...projects.map(
                        (p) => DropdownMenuItem<int?>(
                          value: p.id,
                          child: Text(
                            '${p.orderId} - ${p.buyerUsername}',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ),
                    ],
                    onChanged: (pid) {
                      setModalState(() {
                        selectedProject = projects
                            .where((p) => p.id == pid)
                            .firstOrNull;
                        orderCtrl.text = selectedProject?.orderId ?? '';
                        clientCtrl.text =
                            selectedProject?.buyerUsername ?? '';
                      });
                    },
                  ),
                  const SizedBox(height: 12),
                ],
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: orderCtrl,
                        decoration: const InputDecoration(labelText: 'Order ID'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextField(
                        controller: hoursCtrl,
                        decoration: const InputDecoration(
                          labelText: 'Hours *',
                          hintText: 'e.g. 1h 30m or 1.5',
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: clientCtrl,
                  decoration: const InputDecoration(labelText: 'Client Name'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: reasonCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Reason for Extra Time',
                    hintText: 'e.g. Urgent client launch',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: tasksCtrl,
                  maxLines: 3,
                  decoration: const InputDecoration(
                    labelText: 'Tasks Completed *',
                    hintText: 'Describe work finished during extra time...',
                  ),
                ),
                const SizedBox(height: 18),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF4338CA),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 13),
                    ),
                    onPressed: () async {
                      final hrs = hoursCtrl.text.trim();
                      final tasks = tasksCtrl.text.trim();
                      if (hrs.isEmpty || tasks.isEmpty) {
                        ScaffoldMessenger.of(ctx).showSnackBar(
                          const SnackBar(
                            content: Text(
                              'Please enter both extra hours and completed tasks.',
                            ),
                          ),
                        );
                        return;
                      }

                      final tt = Provider.of<TimeTrackerProvider>(
                        context,
                        listen: false,
                      );
                      Navigator.of(ctx).pop();
                      final res = await tt.submitExtraTimeReport(
                        projectId: selectedProject?.id,
                        orderId: orderCtrl.text.trim(),
                        clientName: clientCtrl.text.trim(),
                        hours: hrs,
                        tasksCompleted: tasks,
                        extraTimeReason: reasonCtrl.text.trim(),
                      );
                      if (res.success) {
                        _refreshAll();
                      }
                      if (mounted) {
                        ScaffoldMessenger.of(this.context).showSnackBar(
                          SnackBar(
                            content: Text(res.message),
                            backgroundColor: res.success
                                ? AppColors.success
                                : AppColors.danger,
                          ),
                        );
                      }
                    },
                    child: const Text(
                      'Submit & Sync Extra Time',
                      style: TextStyle(fontWeight: FontWeight.w800),
                    ),
                  ),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // =========================================================================
  // SMALL UI HELPERS
  // =========================================================================
  Widget _buildMiniStatCard(
    bool isDark,
    String title,
    String value,
    Color accent,
    IconData icon,
  ) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
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
              Icon(icon, size: 14, color: accent),
              const SizedBox(width: 4),
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: 9.5,
                    fontWeight: FontWeight.w800,
                    color:
                        isDark ? AppColors.textMuted : AppColors.textDarkMuted,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w900,
              color: accent,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  Widget _buildChipPill(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 10.5,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}
