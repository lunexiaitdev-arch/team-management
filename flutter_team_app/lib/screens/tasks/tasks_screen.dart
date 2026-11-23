import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/task.dart';
import '../../providers/auth_provider.dart';
import '../../providers/projects_provider.dart';
import '../../providers/tasks_provider.dart';
import '../../theme/app_colors.dart';
import '../../widgets/drawer_opener.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/team_badge.dart';
import '../projects/project_detail_sheet.dart';

class TasksScreen extends StatefulWidget {
  const TasksScreen({super.key});

  @override
  State<TasksScreen> createState() => _TasksScreenState();
}

class _TasksScreenState extends State<TasksScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final tasksProv = Provider.of<TasksProvider>(context, listen: false);
      if (auth.isSpecialist) {
        tasksProv.setScopeAndTeam(scope: 'mine', team: 'all');
      } else {
        tasksProv.fetchTasks();
      }
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchCtrl.dispose();
    super.dispose();
  }

  void _onSearchChanged(String val) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 300), () {
      if (mounted) {
        Provider.of<TasksProvider>(context, listen: false).setSearchQuery(val);
      }
    });
  }

  Future<void> _openProjectById(int projectId) async {
    if (projectId <= 0) return;
    final projectsProv = Provider.of<ProjectsProvider>(context, listen: false);
    final existing = projectsProv.projects.where((p) => p.id == projectId).toList();
    if (existing.isNotEmpty) {
      if (!mounted) return;
      showModalBottomSheet(
        context: context,
        isScrollControlled: true,
        backgroundColor: Colors.transparent,
        builder: (_) => ProjectDetailSheet(project: existing.first),
      );
      return;
    }
    // Fetch fresh project list if not cached yet
    await projectsProv.fetchProjects();
    if (!mounted) return;
    final found = projectsProv.projects.where((p) => p.id == projectId).toList();
    if (found.isNotEmpty) {
      showModalBottomSheet(
        context: context,
        isScrollControlled: true,
        backgroundColor: Colors.transparent,
        builder: (_) => ProjectDetailSheet(project: found.first),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Order details not loaded in current period filter.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final auth = Provider.of<AuthProvider>(context);
    final tasksProv = Provider.of<TasksProvider>(context);

    final isAdminOrExec = auth.isExecutive || auth.isOperationsManager;
    final isLeadOrAdmin = isAdminOrExec || auth.isSquadLeader;

    return Scaffold(
      backgroundColor: isDark ? AppColors.darkBackground : AppColors.lightBackground,
      appBar: AppBar(
        backgroundColor: isDark ? AppColors.darkSurface : AppColors.lightSurface,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.menu_rounded, color: isDark ? AppColors.textLight : AppColors.textDark),
          onPressed: () => DrawerOpener.of(context)?.openDrawer(),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              isLeadOrAdmin ? 'Tasks & Milestones' : 'My Dedicated Tasks',
              style: TextStyle(
                color: isDark ? AppColors.textLight : AppColors.textDark,
                fontSize: 17,
                fontWeight: FontWeight.w800,
              ),
            ),
            Text(
              auth.isExecutive
                  ? 'Executive Oversight · All Teams'
                  : (auth.isSquadLeader
                      ? '${auth.currentUser?.team ?? "Squad"} Deliverables'
                      : 'Assigned order tasks & deadlines'),
              style: TextStyle(
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                fontSize: 11,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh_rounded, color: isDark ? AppColors.textLight : AppColors.textDark),
            onPressed: () => tasksProv.fetchTasks(),
            tooltip: 'Refresh Tasks',
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => tasksProv.fetchTasks(),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // 1. Compact 4-Column Tappable KPI Strip
                    Row(
                      children: [
                        Expanded(
                          child: _buildMiniKpi(
                            label: 'Total',
                            value: '${tasksProv.totalTasks}',
                            color: AppColors.primaryBlueLight,
                            isActive: tasksProv.statusFilter == 'all',
                            isDark: isDark,
                            onTap: () => tasksProv.setStatusFilter('all'),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: _buildMiniKpi(
                            label: 'Pending',
                            value: '${tasksProv.pendingCount}',
                            color: AppColors.warning,
                            isActive: tasksProv.statusFilter == 'pending',
                            isDark: isDark,
                            onTap: () => tasksProv.setStatusFilter(
                              tasksProv.statusFilter == 'pending' ? 'all' : 'pending',
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: _buildMiniKpi(
                            label: 'Overdue',
                            value: '${tasksProv.overdueCount}',
                            color: AppColors.danger,
                            isActive: tasksProv.statusFilter == 'overdue',
                            isDark: isDark,
                            onTap: () => tasksProv.setStatusFilter(
                              tasksProv.statusFilter == 'overdue' ? 'all' : 'overdue',
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: _buildMiniKpi(
                            label: 'Done',
                            value: '${tasksProv.completedCount}',
                            color: AppColors.success,
                            isActive: tasksProv.statusFilter == 'done',
                            isDark: isDark,
                            onTap: () => tasksProv.setStatusFilter(
                              tasksProv.statusFilter == 'done' ? 'all' : 'done',
                            ),
                          ),
                        ),
                      ],
                    ),

                    // 2. Dedicated Team / Squad Filter Pills (for Chairman / Manager / Leaders)
                    if (isLeadOrAdmin) ...[
                      const SizedBox(height: 10),
                      SizedBox(
                        height: 34,
                        child: ListView(
                          scrollDirection: Axis.horizontal,
                          children: [
                            if (isAdminOrExec) ...[
                              _buildTeamPill(
                                label: 'All Teams (${tasksProv.allVisibleCount})',
                                isSelected: tasksProv.scope != 'mine' && tasksProv.teamFilter == 'all',
                                isDark: isDark,
                                onTap: () => tasksProv.setScopeAndTeam(scope: 'all', team: 'all'),
                              ),
                              ...tasksProv.teamCounts.entries.map((entry) => _buildTeamPill(
                                    label: '${entry.key} (${entry.value})',
                                    isSelected: tasksProv.scope != 'mine' &&
                                        tasksProv.teamFilter.toLowerCase() == entry.key.toLowerCase(),
                                    isDark: isDark,
                                    onTap: () => tasksProv.setScopeAndTeam(scope: 'all', team: entry.key),
                                  )),
                            ] else if (auth.isSquadLeader) ...[
                              _buildTeamPill(
                                label: '${tasksProv.userTeam.isNotEmpty ? tasksProv.userTeam : "My Squad"} (${tasksProv.allVisibleCount})',
                                isSelected: tasksProv.scope != 'mine',
                                isDark: isDark,
                                onTap: () => tasksProv.setScopeAndTeam(scope: 'all', team: tasksProv.userTeam),
                              ),
                            ],
                            if (!auth.isExecutive)
                              _buildTeamPill(
                                label: 'My Tasks (${tasksProv.myTotalCount})',
                                isSelected: tasksProv.scope == 'mine',
                                accentColor: AppColors.success,
                                isDark: isDark,
                                onTap: () => tasksProv.setScopeAndTeam(scope: 'mine', team: 'all'),
                              ),
                          ],
                        ),
                      ),
                    ],

                    // 3. Compact Search + Priority Filter Bar
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Expanded(
                          child: SizedBox(
                            height: 38,
                            child: TextField(
                              controller: _searchCtrl,
                              onChanged: _onSearchChanged,
                              style: TextStyle(
                                fontSize: 13,
                                color: isDark ? AppColors.textLight : AppColors.textDark,
                              ),
                              decoration: InputDecoration(
                                hintText: 'Search task, order, client...',
                                hintStyle: TextStyle(
                                  fontSize: 12.5,
                                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                ),
                                prefixIcon: Icon(
                                  Icons.search_rounded,
                                  size: 18,
                                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                ),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
                                filled: true,
                                fillColor: isDark ? AppColors.darkCard : AppColors.lightCard,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(10),
                                  borderSide: BorderSide(
                                    color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                                  ),
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(10),
                                  borderSide: BorderSide(
                                    color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          height: 38,
                          padding: const EdgeInsets.symmetric(horizontal: 10),
                          decoration: BoxDecoration(
                            color: isDark ? AppColors.darkCard : AppColors.lightCard,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                            ),
                          ),
                          child: DropdownButtonHideUnderline(
                            child: DropdownButton<String>(
                              value: tasksProv.priorityFilter,
                              dropdownColor: isDark ? AppColors.darkCard : AppColors.lightCard,
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: isDark ? AppColors.textLight : AppColors.textDark,
                              ),
                              items: const [
                                DropdownMenuItem(value: 'all', child: Text('All Priority')),
                                DropdownMenuItem(value: 'urgent', child: Text('Urgent')),
                                DropdownMenuItem(value: 'high', child: Text('High')),
                                DropdownMenuItem(value: 'medium', child: Text('Medium')),
                                DropdownMenuItem(value: 'low', child: Text('Low')),
                              ],
                              onChanged: (v) {
                                if (v != null) tasksProv.setPriorityFilter(v);
                              },
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),

            // 4. Task List (Organized by Team when viewing All Teams as Admin/Chairman, or by Project)
            if (tasksProv.isLoading && tasksProv.tasks.isEmpty)
              const SliverFillRemaining(
                hasScrollBody: false,
                child: Center(child: CircularProgressIndicator()),
              )
            else if (tasksProv.errorMessage != null && tasksProv.tasks.isEmpty)
              SliverFillRemaining(
                hasScrollBody: false,
                child: EmptyStateWidget(
                  icon: Icons.cloud_off_rounded,
                  title: 'Unable to load tasks',
                  description: tasksProv.errorMessage!,
                  actionLabel: 'Retry',
                  actionIcon: Icons.refresh_rounded,
                  onAction: () => tasksProv.fetchTasks(),
                ),
              )
            else if (tasksProv.tasks.isEmpty)
              const SliverFillRemaining(
                hasScrollBody: false,
                child: EmptyStateWidget(
                  icon: Icons.task_alt_rounded,
                  title: 'No matching tasks',
                  description: 'All clear for the selected team and filter.',
                ),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(14, 4, 14, 24),
                sliver: SliverList(
                  delegate: SliverChildListDelegate(
                    _buildGroupedTaskWidgets(
                      context: context,
                      tasks: tasksProv.tasks,
                      groupByTeam: isAdminOrExec && tasksProv.scope != 'mine' && tasksProv.teamFilter == 'all',
                      isViewOnly: auth.isViewOnly,
                      isDark: isDark,
                      tasksProv: tasksProv,
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildGroupedTaskWidgets({
    required BuildContext context,
    required List<ProjectTask> tasks,
    required bool groupByTeam,
    required bool isViewOnly,
    required bool isDark,
    required TasksProvider tasksProv,
  }) {
    final widgets = <Widget>[];
    if (groupByTeam) {
      final grouped = <String, List<ProjectTask>>{};
      for (final t in tasks) {
        final tm = (t.project?.teamName.isNotEmpty ?? false) ? t.project!.teamName : 'Unassigned';
        grouped.putIfAbsent(tm, () => []).add(t);
      }
      final sortedTeams = grouped.keys.toList()..sort();
      for (final tm in sortedTeams) {
        final list = grouped[tm]!;
        final ovd = list.where((x) => !x.isDone && x.isOverdue).length;
        widgets.add(
          Padding(
            padding: const EdgeInsets.only(top: 10, bottom: 6),
            child: Row(
              children: [
                TeamBadge(team: tm, isSmall: true),
                const SizedBox(width: 8),
                Text(
                  '${list.length} task${list.length == 1 ? '' : 's'}',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: isDark ? AppColors.textLight : AppColors.textDark,
                  ),
                ),
                if (ovd > 0) ...[
                  const SizedBox(width: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppColors.danger.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      '$ovd overdue',
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: AppColors.danger,
                      ),
                    ),
                  ),
                ],
                const Spacer(),
                GestureDetector(
                  onTap: () => tasksProv.setScopeAndTeam(scope: 'all', team: tm),
                  child: const Text(
                    'Focus →',
                    style: TextStyle(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primaryBlueLight,
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
        for (final t in list) {
          widgets.add(_buildTaskCard(context, t, isViewOnly, isDark, tasksProv));
        }
      }
    } else {
      for (final t in tasks) {
        widgets.add(_buildTaskCard(context, t, isViewOnly, isDark, tasksProv));
      }
    }
    return widgets;
  }

  Widget _buildTaskCard(
    BuildContext context,
    ProjectTask task,
    bool isViewOnly,
    bool isDark,
    TasksProvider tasksProv,
  ) {
    final prColor = task.priority == 'urgent'
        ? AppColors.danger
        : (task.priority == 'high'
            ? AppColors.warning
            : (task.priority == 'medium' ? AppColors.primaryBlueLight : AppColors.textDim));

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: (!task.isDone && task.isOverdue)
              ? AppColors.danger.withOpacity(0.45)
              : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
        ),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () => _openProjectById(task.projectId),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Interactive toggle checkbox for non-view-only users; clean status icon for Chairman
              if (!isViewOnly)
                Padding(
                  padding: const EdgeInsets.only(top: 2, right: 8),
                  child: SizedBox(
                    width: 24,
                    height: 24,
                    child: Checkbox(
                      value: task.isDone,
                      activeColor: AppColors.success,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(5)),
                      onChanged: (val) async {
                        if (val == null) return;
                        final res = await tasksProv.toggleTaskStatus(task.id, val);
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(res.message),
                              backgroundColor: res.success ? AppColors.success : AppColors.danger,
                            ),
                          );
                        }
                      },
                    ),
                  ),
                )
              else
                Padding(
                  padding: const EdgeInsets.only(top: 2, right: 10),
                  child: Icon(
                    task.isDone
                        ? Icons.check_circle_rounded
                        : (task.isOverdue ? Icons.error_rounded : Icons.radio_button_unchecked_rounded),
                    size: 20,
                    color: task.isDone
                        ? AppColors.success
                        : (task.isOverdue ? AppColors.danger : AppColors.warning),
                  ),
                ),

              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Title + Priority Badge
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Text(
                            task.title,
                            style: TextStyle(
                              fontSize: 13.5,
                              fontWeight: FontWeight.w700,
                              decoration: task.isDone ? TextDecoration.lineThrough : null,
                              color: task.isDone
                                  ? (isDark ? AppColors.textDim : AppColors.textDarkMuted)
                                  : (isDark ? AppColors.textLight : AppColors.textDark),
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(
                            color: prColor.withOpacity(0.14),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            task.priority.toUpperCase(),
                            style: TextStyle(
                              fontSize: 9.5,
                              fontWeight: FontWeight.w800,
                              color: prColor,
                            ),
                          ),
                        ),
                      ],
                    ),

                    if (task.description.isNotEmpty) ...[
                      const SizedBox(height: 3),
                      Text(
                        task.description,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 11.5,
                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                        ),
                      ),
                    ],

                    const SizedBox(height: 8),

                    // Dedicated Order + Team + Deadline Row
                    Wrap(
                      spacing: 6,
                      runSpacing: 5,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      children: [
                        if (task.project != null) ...[
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                            decoration: BoxDecoration(
                              color: AppColors.primaryBlue.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(color: AppColors.primaryBlue.withOpacity(0.28)),
                            ),
                            child: Text(
                              '${task.project!.orderId} · ${task.project!.clientName}',
                              style: const TextStyle(
                                fontSize: 10.5,
                                fontWeight: FontWeight.w700,
                                color: AppColors.primaryBlueLight,
                              ),
                            ),
                          ),
                          if (task.project!.teamName.isNotEmpty)
                            TeamBadge(team: task.project!.teamName, isSmall: true),
                        ],
                        if (task.deadlineFormatted != 'No Deadline')
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                            decoration: BoxDecoration(
                              color: (!task.isDone && task.isOverdue)
                                  ? AppColors.danger.withOpacity(0.14)
                                  : (isDark ? AppColors.darkSurface : AppColors.lightSurface),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.schedule_rounded,
                                  size: 11,
                                  color: (!task.isDone && task.isOverdue)
                                      ? AppColors.danger
                                      : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  '${task.deadlineFormatted}${(!task.isDone && task.countdownLabel.isNotEmpty) ? " · ${task.countdownLabel}" : ""}',
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.w700,
                                    color: (!task.isDone && task.isOverdue)
                                        ? AppColors.danger
                                        : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                                  ),
                                ),
                              ],
                            ),
                          ),
                      ],
                    ),

                    // Multi-Assignee Chips + Synced to Order Report Indicator
                    if (task.assignedUsers.isNotEmpty || task.isDone) ...[
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 6,
                        runSpacing: 4,
                        crossAxisAlignment: WrapCrossAlignment.center,
                        children: [
                          ...task.assignedUsers.map((u) => Container(
                                padding: const EdgeInsets.fromLTRB(3, 2, 7, 2),
                                decoration: BoxDecoration(
                                  color: isDark
                                      ? Colors.white.withOpacity(0.05)
                                      : Colors.black.withOpacity(0.04),
                                  borderRadius: BorderRadius.circular(999),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    CircleAvatar(
                                      radius: 8,
                                      backgroundColor: AppColors.primaryBlue.withOpacity(0.2),
                                      backgroundImage:
                                          u.avatar.isNotEmpty ? NetworkImage(u.avatar) : null,
                                      child: u.avatar.isEmpty
                                          ? Text(
                                              u.name.isNotEmpty ? u.name[0].toUpperCase() : '?',
                                              style: const TextStyle(fontSize: 8, fontWeight: FontWeight.w700),
                                            )
                                          : null,
                                    ),
                                    const SizedBox(width: 5),
                                    Text(
                                      u.name,
                                      style: TextStyle(
                                        fontSize: 10.5,
                                        fontWeight: FontWeight.w600,
                                        color: isDark ? AppColors.textLight : AppColors.textDark,
                                      ),
                                    ),
                                  ],
                                ),
                              )),
                          if (task.isDone)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                              decoration: BoxDecoration(
                                color: AppColors.success.withOpacity(0.14),
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: const Text(
                                '✓ Synced to Order Report',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.success,
                                ),
                              ),
                            ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMiniKpi({
    required String label,
    required String value,
    required Color color,
    required bool isActive,
    required bool isDark,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 9),
        decoration: BoxDecoration(
          color: isDark ? AppColors.darkCard : AppColors.lightCard,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isActive ? color : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
            width: isActive ? 1.6 : 1,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w700,
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              value,
              style: TextStyle(
                fontSize: 17,
                fontWeight: FontWeight.w800,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTeamPill({
    required String label,
    required bool isSelected,
    required bool isDark,
    required VoidCallback onTap,
    Color accentColor = AppColors.primaryBlueLight,
  }) {
    return Padding(
      padding: const EdgeInsets.only(right: 6),
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          decoration: BoxDecoration(
            color: isSelected
                ? accentColor.withOpacity(0.18)
                : (isDark ? AppColors.darkCard : AppColors.lightCard),
            borderRadius: BorderRadius.circular(999),
            border: Border.all(
              color: isSelected
                  ? accentColor
                  : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
            ),
          ),
          child: Center(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 11.5,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
                color: isSelected
                    ? accentColor
                    : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
