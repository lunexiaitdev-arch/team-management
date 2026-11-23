import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import '../../models/daily_report.dart';
import '../../models/project.dart';
import '../../providers/daily_reports_provider.dart';
import '../../providers/projects_provider.dart';
import '../../services/api_service.dart';
import '../../theme/app_colors.dart';
import '../../widgets/status_badge.dart';

class LogStandupScreen extends StatefulWidget {
  final DailyReport? existingReport;
  const LogStandupScreen({super.key, this.existingReport});

  @override
  State<LogStandupScreen> createState() => _LogStandupScreenState();
}

class _LogStandupScreenState extends State<LogStandupScreen> {
  String _reportType = 'work'; // 'work', 'weekend', 'on_leave'
  DateTime _reportDate = DateTime.now();
  DateTime _leaveStartDate = DateTime.now();
  DateTime _leaveEndDate = DateTime.now();
  String _leaveType = 'casual_leave';

  final _leaveReasonController = TextEditingController();
  final _hoursController = TextEditingController(text: '8.0');
  final _blockersController = TextEditingController();
  final _tomorrowPlanController = TextEditingController();

  final List<DailyReportTaskItem> _tasks = [];
  final List<TextEditingController> _taskDescControllers = [];
  final List<TextEditingController> _taskWipControllers = [];
  final List<TextEditingController> _taskNextControllers = [];
  final List<TextEditingController> _taskHoursControllers = [];
  final List<TextEditingController> _taskBlockerControllers = [];

  final List<int> _completedTaskIds = [];
  List<Map<String, dynamic>> _myPendingTasks = [];
  bool _isSubmitting = false;

  double _parseHours(String input) {
    final s = input.trim().toLowerCase();
    if (s.isEmpty) return 0.0;
    if (s.contains(':')) {
      final parts = s.split(':');
      final h = double.tryParse(parts[0]) ?? 0.0;
      final m = double.tryParse(parts[1]) ?? 0.0;
      return h + (m / 60.0);
    }
    final match = RegExp(
      r'(?:(\d+(?:\.\d+)?)\s*h(?:ours?|rs?)?)?\s*(?:(\d+(?:\.\d+)?)\s*m(?:in(?:ute)?s?)?)?',
    ).firstMatch(s);
    if (match != null && (match.group(1) != null || match.group(2) != null)) {
      final h = double.tryParse(match.group(1) ?? '') ?? 0.0;
      final m = double.tryParse(match.group(2) ?? '') ?? 0.0;
      if (h > 0 || m > 0) return h + (m / 60.0);
    }
    return double.tryParse(s) ?? 0.0;
  }

  void _addTaskSlot(DailyReportTaskItem item) {
    _tasks.add(item);
    _taskDescControllers.add(TextEditingController(
      text: item.tasksCompleted.isNotEmpty
          ? item.tasksCompleted
          : item.taskDescription,
    ));
    _taskWipControllers.add(TextEditingController(text: item.workInProgress));
    _taskNextControllers.add(TextEditingController(text: item.nextDayPlan));
    _taskHoursControllers.add(
      TextEditingController(text: item.hoursSpent.toString()),
    );
    _taskBlockerControllers.add(
      TextEditingController(text: item.blockerDescription),
    );
  }

  @override
  void initState() {
    super.initState();
    final existing = widget.existingReport;
    if (existing != null) {
      _reportDate = DateTime.tryParse(existing.reportDate) ?? DateTime.now();
      _reportType = existing.reportType.isNotEmpty ? existing.reportType : 'work';
      _hoursController.text =
          existing.totalHours > 0 ? existing.totalHours.toString() : '8.0';
      _blockersController.text = existing.blockers;
      _tomorrowPlanController.text = existing.tomorrowPlan;
      if (existing.items.isNotEmpty) {
        for (final it in existing.items) {
          _addTaskSlot(it);
        }
      } else {
        _addTaskSlot(DailyReportTaskItem(
          orderId: '',
          taskDescription: '',
          hoursSpent: 4.0,
          progressPercent: 50,
        ));
      }
    } else {
      _addTaskSlot(DailyReportTaskItem(
        orderId: '',
        taskDescription: '',
        hoursSpent: 4.0,
        progressPercent: 50,
      ));
    }

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final projectsProv =
          Provider.of<ProjectsProvider>(context, listen: false);
      if (projectsProv.projects.isEmpty) {
        projectsProv.fetchProjects();
      }
      try {
        final api = Provider.of<ApiService>(context, listen: false);
        final res = await api.get('/tasks/my-pending');
        if (!mounted) return;
        List<dynamic> rawList = [];
        if (res is List) {
          rawList = res;
        } else if (res is Map) {
          if (res['pending_tasks'] is List) {
            rawList = res['pending_tasks'] as List;
          } else if (res['tasks'] is List) {
            rawList = res['tasks'] as List;
          }
        }
        setState(() {
          _myPendingTasks =
              rawList.map((t) => Map<String, dynamic>.from(t)).toList();
        });
      } catch (_) {}
    });
  }

  @override
  void dispose() {
    _leaveReasonController.dispose();
    _hoursController.dispose();
    _blockersController.dispose();
    _tomorrowPlanController.dispose();
    for (final c in _taskDescControllers) {
      c.dispose();
    }
    for (final c in _taskWipControllers) {
      c.dispose();
    }
    for (final c in _taskNextControllers) {
      c.dispose();
    }
    for (final c in _taskHoursControllers) {
      c.dispose();
    }
    for (final c in _taskBlockerControllers) {
      c.dispose();
    }
    super.dispose();
  }

  void _addNewTask() {
    setState(() {
      _addTaskSlot(DailyReportTaskItem(
        orderId: '',
        taskDescription: '',
        hoursSpent: 2.0,
        progressPercent: 50,
      ));
    });
  }

  void _removeTask(int index) {
    if (_tasks.length <= 1) return;
    setState(() {
      _tasks.removeAt(index);
      _taskDescControllers.removeAt(index).dispose();
      _taskWipControllers.removeAt(index).dispose();
      _taskNextControllers.removeAt(index).dispose();
      _taskHoursControllers.removeAt(index).dispose();
      _taskBlockerControllers.removeAt(index).dispose();
    });
  }

  void _recalculateTotalHours() {
    double sum = 0;
    for (int i = 0; i < _tasks.length; i++) {
      final h = _parseHours(_taskHoursControllers[i].text);
      sum += h > 0 ? h : _tasks[i].hoursSpent;
    }
    if (sum > 0) {
      _hoursController.text = sum.toStringAsFixed(sum % 1 == 0 ? 1 : 2);
    }
  }

  void _syncTaskFromControllers(int index) {
    final t = _tasks[index];
    final desc = _taskDescControllers[index].text.trim();
    final wip = _taskWipControllers[index].text.trim();
    final next = _taskNextControllers[index].text.trim();
    final hrs = _parseHours(_taskHoursControllers[index].text);
    final blocker = _taskBlockerControllers[index].text.trim();

    _tasks[index] = DailyReportTaskItem(
      id: t.id,
      projectId: t.projectId,
      orderId: t.orderId,
      clientName: t.clientName,
      taskDescription: desc,
      tasksCompleted: desc,
      workInProgress: wip,
      nextDayPlan: next,
      hasBlocker: t.hasBlocker || blocker.isNotEmpty,
      blockerDescription: blocker,
      hoursSpent: hrs > 0 ? hrs : t.hoursSpent,
      progressPercent: t.progressPercent,
    );
  }

  Future<void> _showProjectSearchPicker(
    int taskIndex,
    List<ProjectItem> allProjects,
    bool isDark,
  ) async {
    final searchCtrl = TextEditingController();
    final picked = await showModalBottomSheet<dynamic>(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? AppColors.darkSurface : AppColors.lightSurface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setSheetState) {
            final q = searchCtrl.text.trim().toLowerCase();
            final matches = allProjects.where((p) {
              if (q.isEmpty) return true;
              return p.orderId.toLowerCase().contains(q) ||
                  p.buyerUsername.toLowerCase().contains(q) ||
                  p.title.toLowerCase().contains(q) ||
                  p.profileName.toLowerCase().contains(q) ||
                  p.status.toLowerCase().contains(q);
            }).toList();

            return DraggableScrollableSheet(
              initialChildSize: 0.78,
              minChildSize: 0.5,
              maxChildSize: 0.94,
              expand: false,
              builder: (_, scrollCtrl) => Column(
                children: [
                  const SizedBox(height: 10),
                  Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: isDark ? Colors.white24 : Colors.black12,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Search & Select Project',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            Text(
                              '${matches.length} orders',
                              style: TextStyle(
                                fontSize: 11.5,
                                color: isDark
                                    ? AppColors.textMuted
                                    : AppColors.textDarkMuted,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: searchCtrl,
                          autofocus: true,
                          onChanged: (_) => setSheetState(() {}),
                          decoration: InputDecoration(
                            hintText:
                                'Search Order ID, client, title, profile, status...',
                            prefixIcon:
                                const Icon(Icons.search_rounded, size: 19),
                            suffixIcon: searchCtrl.text.isNotEmpty
                                ? IconButton(
                                    icon: const Icon(Icons.clear_rounded,
                                        size: 17),
                                    onPressed: () {
                                      searchCtrl.clear();
                                      setSheetState(() {});
                                    },
                                  )
                                : null,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 1),
                  Expanded(
                    child: ListView(
                      controller: scrollCtrl,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 8,
                      ),
                      children: [
                        // General / Custom Option
                        ListTile(
                          leading: CircleAvatar(
                            backgroundColor:
                                AppColors.primaryBlue.withValues(alpha: 0.14),
                            child: const Icon(Icons.add_box_rounded,
                                color: AppColors.primaryBlueLight, size: 19),
                          ),
                          title: const Text(
                            '+ Other / General Agency Task',
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 13,
                            ),
                          ),
                          subtitle: const Text(
                            'Old client, internal work, or non-Fiverr task',
                            style: TextStyle(fontSize: 11.5),
                          ),
                          onTap: () => Navigator.of(ctx).pop('custom'),
                        ),
                        const Divider(height: 8),
                        if (matches.isEmpty)
                          const Padding(
                            padding: EdgeInsets.all(24),
                            child: Center(
                              child: Text(
                                'No matching orders found. Try clearing search or choose General Task above.',
                                textAlign: TextAlign.center,
                                style: TextStyle(fontSize: 12.5),
                              ),
                            ),
                          )
                        else
                          ...matches.map((p) {
                            final isCurrent = _tasks[taskIndex].projectId == p.id;
                            return ListTile(
                              selected: isCurrent,
                              selectedTileColor:
                                  AppColors.primaryBlue.withValues(alpha: 0.1),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                              title: Row(
                                children: [
                                  Text(
                                    p.orderId,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w800,
                                      fontSize: 12.5,
                                      color: AppColors.primaryBlueLight,
                                    ),
                                  ),
                                  const SizedBox(width: 6),
                                  Expanded(
                                    child: Text(
                                      p.buyerUsername,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                        fontSize: 13,
                                      ),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  StatusBadge(status: p.status),
                                ],
                              ),
                              subtitle: Padding(
                                padding: const EdgeInsets.only(top: 3),
                                child: Text(
                                  '${p.title}${p.profileName.isNotEmpty ? " • ${p.profileName}" : ""}',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(fontSize: 11.5),
                                ),
                              ),
                              onTap: () => Navigator.of(ctx).pop(p),
                            );
                          }),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
    searchCtrl.dispose();

    if (picked == null || !mounted) return;
    setState(() {
      _syncTaskFromControllers(taskIndex);
      final cur = _tasks[taskIndex];
      if (picked == 'custom') {
        _tasks[taskIndex] = DailyReportTaskItem(
          id: cur.id,
          projectId: null,
          orderId: '',
          clientName: '',
          taskDescription: cur.taskDescription,
          tasksCompleted: cur.tasksCompleted,
          workInProgress: cur.workInProgress,
          nextDayPlan: cur.nextDayPlan,
          hasBlocker: cur.hasBlocker,
          blockerDescription: cur.blockerDescription,
          hoursSpent: cur.hoursSpent,
          progressPercent: cur.progressPercent,
        );
      } else if (picked is ProjectItem) {
        _tasks[taskIndex] = DailyReportTaskItem(
          id: cur.id,
          projectId: picked.id,
          orderId: picked.orderId,
          clientName: picked.buyerUsername,
          taskDescription: cur.taskDescription,
          tasksCompleted: cur.tasksCompleted,
          workInProgress: cur.workInProgress,
          nextDayPlan: cur.nextDayPlan,
          hasBlocker: cur.hasBlocker,
          blockerDescription: cur.blockerDescription,
          hoursSpent: cur.hoursSpent,
          progressPercent: cur.progressPercent == 50 && picked.progressPercent > 0
              ? picked.progressPercent
              : cur.progressPercent,
        );
      }
    });
  }

  Future<void> _handleSubmit({String status = 'submitted'}) async {
    final prov = Provider.of<DailyReportsProvider>(context, listen: false);
    final fmt = DateFormat('yyyy-MM-dd');

    if (_reportType == 'weekend') {
      setState(() => _isSubmitting = true);
      final res = await prov.submitStandup(
        date: fmt.format(_reportDate),
        reportType: 'weekend',
        status: status,
      );
      setState(() => _isSubmitting = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res.message),
            backgroundColor: res.success ? AppColors.success : AppColors.danger,
          ),
        );
        if (res.success) Navigator.of(context).pop();
      }
      return;
    }

    if (_reportType == 'on_leave') {
      if (_leaveEndDate.isBefore(_leaveStartDate)) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Leave end date cannot be before start date.'),
          ),
        );
        return;
      }
      setState(() => _isSubmitting = true);
      final res = await prov.submitStandup(
        date: fmt.format(_leaveStartDate),
        reportType: 'on_leave',
        status: status,
        leaveStartDate: fmt.format(_leaveStartDate),
        leaveEndDate: fmt.format(_leaveEndDate),
        leaveType: _leaveType,
        leaveReason: _leaveReasonController.text.trim(),
      );
      setState(() => _isSubmitting = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res.message),
            backgroundColor: res.success ? AppColors.success : AppColors.danger,
          ),
        );
        if (res.success) Navigator.of(context).pop();
      }
      return;
    }

    for (int i = 0; i < _tasks.length; i++) {
      _syncTaskFromControllers(i);
    }

    final double totalHours = _parseHours(_hoursController.text.trim());
    if (totalHours <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Please enter valid total working hours (e.g. 8.0 or 7h 30m).',
          ),
        ),
      );
      return;
    }

    if (_tasks.isEmpty ||
        _tasks.any((t) => t.taskDescription.trim().isEmpty)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please enter what was completed for each task card.'),
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final res = await prov.submitStandup(
      date: fmt.format(_reportDate),
      reportType: 'work',
      status: status,
      totalHours: totalHours,
      blockers: _blockersController.text.trim(),
      tomorrowPlan: _tomorrowPlanController.text.trim(),
      items: _tasks,
      completedTaskIds: _completedTaskIds,
      replaceAll: widget.existingReport != null,
      existingReportId: widget.existingReport?.id,
    );

    setState(() => _isSubmitting = false);

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res.message),
          backgroundColor: res.success ? AppColors.success : AppColors.danger,
        ),
      );
      if (res.success) {
        Navigator.of(context).pop();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final projectsProv = Provider.of<ProjectsProvider>(context);
    final allProjects = projectsProv.projects;

    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.existingReport != null
              ? 'Edit / Resubmit Standup'
              : 'Log Your Daily Progress',
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildCutoffBanner(isDark),
            const SizedBox(height: 16),

            // Report Type Switcher (Website Parity: Regular Work / Weekend / On Leave)
            Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                color: isDark ? AppColors.darkCard : AppColors.lightCard,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                ),
              ),
              child: Row(
                children: [
                  _buildTypeTab('work', 'Regular Work', Icons.work_rounded, isDark),
                  _buildTypeTab(
                      'weekend', 'Weekend / Off', Icons.weekend_rounded, isDark),
                  _buildTypeTab(
                      'on_leave', 'On Leave', Icons.beach_access_rounded, isDark),
                ],
              ),
            ),
            const SizedBox(height: 16),

            if (_reportType == 'weekend') ...[
              _buildDateRow(isDark),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.primaryBlue.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: AppColors.primaryBlue.withValues(alpha: 0.25),
                  ),
                ),
                child: const Text(
                  'Submit your scheduled weekly off-day notice for leadership verification.',
                  style: TextStyle(fontSize: 13, height: 1.4),
                ),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : () => _handleSubmit(),
                  child: Text(
                    _isSubmitting ? 'Submitting...' : 'Submit Weekend / Off Day',
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
              ),
            ] else if (_reportType == 'on_leave') ...[
              _buildLeaveSection(isDark),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : () => _handleSubmit(),
                  child: Text(
                    _isSubmitting ? 'Submitting...' : 'Submit Leave Standup',
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
              ),
            ] else ...[
              if (_myPendingTasks.isNotEmpty) ...[
                _buildTasksIntegration(isDark),
                const SizedBox(height: 16),
              ],

              // Date & Total Hours
              Row(
                children: [
                  Expanded(child: _buildDateRow(isDark)),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildLabel('TOTAL HOURS', isDark),
                        const SizedBox(height: 6),
                        TextField(
                          controller: _hoursController,
                          keyboardType: TextInputType.text,
                          style: TextStyle(
                            color: isDark
                                ? AppColors.textLight
                                : AppColors.textDark,
                            fontSize: 13,
                          ),
                          decoration: const InputDecoration(
                            hintText: 'e.g. 8.0 or 7h 30m',
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 22),

              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _buildLabel('PROJECT WORK LOGS (${_tasks.length})', isDark),
                  TextButton.icon(
                    onPressed: _addNewTask,
                    icon: const Icon(Icons.add_rounded, size: 16),
                    label: const Text(
                      'Add Project Log',
                      style:
                          TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),

              ..._tasks.asMap().entries.map((entry) {
                final index = entry.key;
                final task = entry.value;
                final selectedLabel = task.orderId.isNotEmpty
                    ? '${task.orderId}${task.clientName.isNotEmpty ? " - ${task.clientName}" : ""}'
                    : '+ Other / General Agency Work (Tap to Search Project)';

                return Container(
                  margin: const EdgeInsets.only(bottom: 14),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: isDark ? AppColors.darkCard : AppColors.lightCard,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color:
                          isDark ? AppColors.darkBorder : AppColors.lightBorder,
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Deliverable #${index + 1}',
                            style: const TextStyle(
                              color: AppColors.primaryBlueLight,
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          if (_tasks.length > 1)
                            IconButton(
                              icon: const Icon(
                                Icons.delete_outline_rounded,
                                color: AppColors.danger,
                                size: 18,
                              ),
                              onPressed: () => _removeTask(index),
                              constraints: const BoxConstraints(),
                              padding: EdgeInsets.zero,
                            ),
                        ],
                      ),
                      const SizedBox(height: 8),

                      // Searchable Project Picker Trigger
                      _buildLabel('SELECT PROJECT (SEARCHABLE)', isDark),
                      const SizedBox(height: 5),
                      InkWell(
                        onTap: () => _showProjectSearchPicker(
                          index,
                          allProjects,
                          isDark,
                        ),
                        borderRadius: BorderRadius.circular(10),
                        child: Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 12,
                          ),
                          decoration: BoxDecoration(
                            color: isDark
                                ? AppColors.darkSurface
                                : AppColors.lightSurface,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: task.orderId.isNotEmpty
                                  ? AppColors.primaryBlueLight
                                      .withValues(alpha: 0.6)
                                  : (isDark
                                      ? AppColors.darkBorder
                                      : AppColors.lightBorder),
                            ),
                          ),
                          child: Row(
                            children: [
                              const Icon(
                                Icons.search_rounded,
                                size: 17,
                                color: AppColors.primaryBlueLight,
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  selectedLabel,
                                  style: TextStyle(
                                    fontSize: 12.5,
                                    fontWeight: task.orderId.isNotEmpty
                                        ? FontWeight.w700
                                        : FontWeight.w500,
                                    color: isDark
                                        ? AppColors.textLight
                                        : AppColors.textDark,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              const Icon(Icons.arrow_drop_down_rounded,
                                  size: 20),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 10),

                      TextField(
                        controller: _taskDescControllers[index],
                        maxLines: 3,
                        onChanged: (_) => _syncTaskFromControllers(index),
                        style: TextStyle(
                          color: isDark
                              ? AppColors.textLight
                              : AppColors.textDark,
                          fontSize: 13,
                        ),
                        decoration: const InputDecoration(
                          labelText: 'What is completed? *',
                          hintText:
                              'e.g. Header responsive layout fixed & tested',
                        ),
                      ),
                      const SizedBox(height: 10),

                      TextField(
                        controller: _taskWipControllers[index],
                        maxLines: 2,
                        onChanged: (_) => _syncTaskFromControllers(index),
                        style: TextStyle(
                          color: isDark
                              ? AppColors.textLight
                              : AppColors.textDark,
                          fontSize: 12.5,
                        ),
                        decoration: const InputDecoration(
                          labelText: 'What are you working on now?',
                          hintText: 'Ongoing customization / testing...',
                        ),
                      ),
                      const SizedBox(height: 10),

                      TextField(
                        controller: _taskNextControllers[index],
                        maxLines: 2,
                        onChanged: (_) => _syncTaskFromControllers(index),
                        style: TextStyle(
                          color: isDark
                              ? AppColors.textLight
                              : AppColors.textDark,
                          fontSize: 12.5,
                        ),
                        decoration: const InputDecoration(
                          labelText: 'Next Plan (Task-level)',
                          hintText: 'Prepare for client review...',
                        ),
                      ),
                      const SizedBox(height: 10),

                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _taskHoursControllers[index],
                              keyboardType: TextInputType.text,
                              onChanged: (_) {
                                _syncTaskFromControllers(index);
                                _recalculateTotalHours();
                              },
                              style: TextStyle(
                                color: isDark
                                    ? AppColors.textLight
                                    : AppColors.textDark,
                                fontSize: 12.5,
                              ),
                              decoration: const InputDecoration(
                                labelText: 'Work Hours *',
                                hintText: 'e.g. 2h 30m or 2.5',
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Est. Progress: ${task.progressPercent}%',
                                style: const TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              SizedBox(
                                width: 145,
                                child: Slider(
                                  value: task.progressPercent
                                      .toDouble()
                                      .clamp(0, 100),
                                  min: 0,
                                  max: 100,
                                  divisions: 20,
                                  onChanged: (val) {
                                    setState(() {
                                      _tasks[index] = DailyReportTaskItem(
                                        id: task.id,
                                        projectId: task.projectId,
                                        orderId: task.orderId,
                                        clientName: task.clientName,
                                        taskDescription: task.taskDescription,
                                        tasksCompleted: task.tasksCompleted,
                                        workInProgress: task.workInProgress,
                                        nextDayPlan: task.nextDayPlan,
                                        hasBlocker: task.hasBlocker,
                                        blockerDescription:
                                            task.blockerDescription,
                                        hoursSpent: task.hoursSpent,
                                        progressPercent: val.round(),
                                      );
                                    });
                                  },
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),

                      CheckboxListTile(
                        value: task.hasBlocker,
                        contentPadding: EdgeInsets.zero,
                        dense: true,
                        title: const Text(
                          'Has Blocker / Client Issue?',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        onChanged: (v) {
                          setState(() {
                            _tasks[index] = DailyReportTaskItem(
                              id: task.id,
                              projectId: task.projectId,
                              orderId: task.orderId,
                              clientName: task.clientName,
                              taskDescription: task.taskDescription,
                              tasksCompleted: task.tasksCompleted,
                              workInProgress: task.workInProgress,
                              nextDayPlan: task.nextDayPlan,
                              hasBlocker: v ?? false,
                              blockerDescription: task.blockerDescription,
                              hoursSpent: task.hoursSpent,
                              progressPercent: task.progressPercent,
                            );
                          });
                        },
                      ),
                      if (task.hasBlocker)
                        TextField(
                          controller: _taskBlockerControllers[index],
                          maxLines: 2,
                          onChanged: (_) => _syncTaskFromControllers(index),
                          decoration: const InputDecoration(
                            labelText: 'Blocker Description',
                            hintText: 'Waiting for credentials / feedback...',
                          ),
                        ),
                    ],
                  ),
                );
              }),

              const SizedBox(height: 10),
              _buildLabel('OVERALL BLOCKERS & ISSUES (OPTIONAL)', isDark),
              const SizedBox(height: 6),
              TextField(
                controller: _blockersController,
                maxLines: 2,
                style: TextStyle(
                  color: isDark ? AppColors.textLight : AppColors.textDark,
                  fontSize: 13,
                ),
                decoration: const InputDecoration(
                  hintText: 'Waiting for client feedback, server access...',
                ),
              ),
              const SizedBox(height: 16),

              _buildLabel("NEXT PLAN / TOMORROW'S PLAN", isDark),
              const SizedBox(height: 6),
              TextField(
                controller: _tomorrowPlanController,
                maxLines: 2,
                style: TextStyle(
                  color: isDark ? AppColors.textLight : AppColors.textDark,
                  fontSize: 13,
                ),
                decoration: const InputDecoration(
                  hintText: 'What is planned for delivery tomorrow?',
                ),
              ),
              const SizedBox(height: 24),

              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      onPressed: _isSubmitting
                          ? null
                          : () => _handleSubmit(status: 'draft'),
                      child: const Text(
                        'Save as Draft',
                        style: TextStyle(fontWeight: FontWeight.w700),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      onPressed: _isSubmitting
                          ? null
                          : () => _handleSubmit(status: 'submitted'),
                      child: _isSubmitting
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(
                              widget.existingReport != null
                                  ? 'Resubmit Daily Report'
                                  : 'Submit Daily Report',
                              style: const TextStyle(
                                fontWeight: FontWeight.w800,
                                fontSize: 14,
                              ),
                            ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildTypeTab(String type, String label, IconData icon, bool isDark) {
    final active = _reportType == type;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _reportType = type),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 9),
          decoration: BoxDecoration(
            color: active ? AppColors.primaryBlue : Colors.transparent,
            borderRadius: BorderRadius.circular(9),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                icon,
                size: 14,
                color: active
                    ? Colors.white
                    : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
              ),
              const SizedBox(width: 5),
              Flexible(
                child: Text(
                  label,
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: active
                        ? Colors.white
                        : (isDark
                            ? AppColors.textDim
                            : AppColors.textDarkMuted),
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDateRow(bool isDark) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildLabel('REPORT DATE', isDark),
        const SizedBox(height: 6),
        InkWell(
          onTap: () async {
            final picked = await showDatePicker(
              context: context,
              initialDate: _reportDate,
              firstDate: DateTime.now().subtract(const Duration(days: 30)),
              lastDate: DateTime.now().add(const Duration(days: 7)),
            );
            if (picked != null) {
              setState(() => _reportDate = picked);
            }
          },
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
            decoration: BoxDecoration(
              color: isDark ? AppColors.darkCard : AppColors.lightCard,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  DateFormat('dd MMM yyyy').format(_reportDate),
                  style: TextStyle(
                    color: isDark ? AppColors.textLight : AppColors.textDark,
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const Icon(
                  Icons.calendar_today_rounded,
                  size: 16,
                  color: AppColors.primaryBlueLight,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildLeaveSection(bool isDark) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildLabel('LEAVE TYPE', isDark),
        const SizedBox(height: 6),
        DropdownButtonFormField<String>(
          initialValue: _leaveType,
          items: const [
            DropdownMenuItem(value: 'casual_leave', child: Text('Casual Leave')),
            DropdownMenuItem(value: 'sick_leave', child: Text('Sick Leave')),
            DropdownMenuItem(
                value: 'emergency_leave', child: Text('Emergency Leave')),
            DropdownMenuItem(value: 'annual_leave', child: Text('Annual / Vacation')),
          ],
          onChanged: (v) {
            if (v != null) setState(() => _leaveType = v);
          },
        ),
        const SizedBox(height: 14),
        Row(
          children: [
            Expanded(
              child: InkWell(
                onTap: () async {
                  final p = await showDatePicker(
                    context: context,
                    initialDate: _leaveStartDate,
                    firstDate: DateTime.now().subtract(const Duration(days: 15)),
                    lastDate: DateTime.now().add(const Duration(days: 90)),
                  );
                  if (p != null) setState(() => _leaveStartDate = p);
                },
                child: InputDecorator(
                  decoration: const InputDecoration(labelText: 'From Date'),
                  child: Text(DateFormat('yyyy-MM-dd').format(_leaveStartDate)),
                ),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: InkWell(
                onTap: () async {
                  final p = await showDatePicker(
                    context: context,
                    initialDate: _leaveEndDate,
                    firstDate: _leaveStartDate,
                    lastDate: DateTime.now().add(const Duration(days: 90)),
                  );
                  if (p != null) setState(() => _leaveEndDate = p);
                },
                child: InputDecorator(
                  decoration: const InputDecoration(labelText: 'To Date'),
                  child: Text(DateFormat('yyyy-MM-dd').format(_leaveEndDate)),
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 14),
        TextField(
          controller: _leaveReasonController,
          maxLines: 3,
          decoration: const InputDecoration(
            labelText: 'Leave Reason / Notes',
            hintText: 'Reason for leave or vacation...',
          ),
        ),
      ],
    );
  }

  Widget _buildLabel(String label, bool isDark) {
    return Text(
      label,
      style: TextStyle(
        color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
        fontSize: 10.5,
        fontWeight: FontWeight.w800,
        letterSpacing: 0.5,
      ),
    );
  }

  Widget _buildCutoffBanner(bool isDark) {
    final now = DateTime.now();
    final cutoff = DateTime(now.year, now.month, now.day, 18, 15);
    final isPast = now.isAfter(cutoff);
    final diff = cutoff.difference(now);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: isPast
            ? AppColors.danger.withValues(alpha: 0.12)
            : AppColors.primaryBlue.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isPast
              ? AppColors.danger.withValues(alpha: 0.4)
              : AppColors.primaryBlue.withValues(alpha: 0.35),
        ),
      ),
      child: Row(
        children: [
          Icon(
            isPast ? Icons.warning_amber_rounded : Icons.schedule_rounded,
            color: isPast ? AppColors.danger : AppColors.primaryBlueLight,
            size: 20,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isPast
                      ? '🚨 Past 6:15 PM Cutoff (Late)'
                      : '⏰ Daily Report Cutoff: 6:15 PM',
                  style: TextStyle(
                    color: isPast
                        ? AppColors.danger
                        : (isDark ? AppColors.textLight : AppColors.textDark),
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  isPast
                      ? 'Submissions after 6:15 PM will be flagged as late for leadership audit.'
                      : '${diff.inHours}h ${diff.inMinutes % 60}m remaining until daily report cutoff.',
                  style: TextStyle(
                    color: isPast
                        ? AppColors.danger
                        : (isDark
                            ? AppColors.textDim
                            : AppColors.textDarkMuted),
                    fontSize: 11.5,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTasksIntegration(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(12),
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
              Text(
                '📋 MY ACTIVE MILESTONES (${_myPendingTasks.length})',
                style: TextStyle(
                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                  fontSize: 10.5,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 0.5,
                ),
              ),
              const Text(
                'Tap to include in report',
                style:
                    TextStyle(color: AppColors.primaryBlueLight, fontSize: 11),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 6,
            children: _myPendingTasks.map((t) {
              final title = t['title']?.toString() ?? 'Task';
              final orderId = t['order_id']?.toString() ?? '';
              final projectId =
                  int.tryParse(t['project_id']?.toString() ?? '0');
              final clientName = t['client_name']?.toString() ?? '';
              final tId = int.tryParse(t['id']?.toString() ?? '0') ?? 0;
              final isChecked = _completedTaskIds.contains(tId);

              return ActionChip(
                avatar: Icon(
                  isChecked
                      ? Icons.check_circle_rounded
                      : Icons.add_task_rounded,
                  size: 16,
                  color: isChecked
                      ? AppColors.success
                      : AppColors.primaryBlueLight,
                ),
                label: Text(
                  '$title ${orderId.isNotEmpty ? "($orderId)" : ""}',
                  style: TextStyle(
                    fontSize: 11.5,
                    fontWeight: isChecked ? FontWeight.bold : FontWeight.w500,
                    color: isChecked
                        ? AppColors.success
                        : (isDark ? AppColors.textLight : AppColors.textDark),
                  ),
                ),
                backgroundColor: isChecked
                    ? AppColors.success.withValues(alpha: 0.12)
                    : (isDark ? AppColors.darkSurface : AppColors.lightSurface),
                onPressed: () {
                  setState(() {
                    if (!isChecked && tId > 0) {
                      _completedTaskIds.add(tId);
                    }
                    if (_tasks.isNotEmpty) {
                      _syncTaskFromControllers(0);
                      final cur = _taskDescControllers[0].text.trim();
                      final appended =
                          cur.isEmpty ? 'Completed: $title' : '$cur\n• Completed: $title';
                      _taskDescControllers[0].text = appended;
                      _tasks[0] = DailyReportTaskItem(
                        id: _tasks[0].id,
                        projectId: _tasks[0].projectId ?? projectId,
                        orderId: _tasks[0].orderId.isNotEmpty
                            ? _tasks[0].orderId
                            : orderId,
                        clientName: _tasks[0].clientName.isNotEmpty
                            ? _tasks[0].clientName
                            : clientName,
                        taskDescription: appended,
                        tasksCompleted: appended,
                        workInProgress: _tasks[0].workInProgress,
                        nextDayPlan: _tasks[0].nextDayPlan,
                        hasBlocker: _tasks[0].hasBlocker,
                        blockerDescription: _tasks[0].blockerDescription,
                        hoursSpent: _tasks[0].hoursSpent,
                        progressPercent: 100,
                      );
                    }
                  });
                },
              );
            }).toList(),
          ),
        ],
      ),
    );
  }
}
