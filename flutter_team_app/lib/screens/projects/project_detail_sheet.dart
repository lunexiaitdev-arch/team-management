import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../models/project.dart';
import '../../providers/auth_provider.dart';
import '../../providers/projects_provider.dart';
import '../../providers/team_members_provider.dart';
import '../../providers/time_tracker_provider.dart';
import '../../services/api_service.dart';
import '../../theme/app_colors.dart';
import '../../widgets/status_badge.dart';
import '../../widgets/team_badge.dart';
import '../../widgets/progress_bar.dart';
import '../../widgets/countdown_pill.dart';
import '../messages/chat_conversation_screen.dart';
import 'update_progress_sheet.dart';
import 'extend_delivery_sheet.dart';
import 'edit_project_sheet.dart';

class ProjectDetailSheet extends StatefulWidget {
  final ProjectItem project;

  const ProjectDetailSheet({super.key, required this.project});

  @override
  State<ProjectDetailSheet> createState() => _ProjectDetailSheetState();
}

class _ProjectDetailSheetState extends State<ProjectDetailSheet> {
  final _noteController = TextEditingController();
  bool _isAddingNote = false;
  late ProjectItem _project;
  List<ProjectNote> _notes = [];
  List<Map<String, dynamic>> _tasks = [];
  bool _isLoadingDetails = false;

  @override
  void initState() {
    super.initState();
    _project = widget.project;
    _notes = List.from(widget.project.notes);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _fetchFullDetails();
    });
  }

  Future<void> _fetchFullDetails() async {
    setState(() => _isLoadingDetails = true);
    try {
      final api = Provider.of<ApiService>(context, listen: false);
      final res = await api.get('/projects/${widget.project.id}');
      if (res is Map) {
        if (res['project'] is Map) {
          final updated = ProjectItem.fromJson(Map<String, dynamic>.from(res['project']));
          _project = updated;
        }
        if (res['notes_list'] is List) {
          _notes = (res['notes_list'] as List).map((n) {
            return ProjectNote.fromJson(Map<String, dynamic>.from(n));
          }).toList();
        }
        if (res['tasks'] is Map && res['tasks']['tasks'] is List) {
          _tasks = (res['tasks']['tasks'] as List).map((t) => Map<String, dynamic>.from(t)).toList();
        }
      }
    } catch (e) {
      debugPrint('Error fetching project full details: $e');
    } finally {
      if (mounted) {
        setState(() => _isLoadingDetails = false);
      }
    }
  }

  void _showAddTaskDialog(BuildContext context) {
    final titleController = TextEditingController();
    String priority = 'high';

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setDlgState) => AlertDialog(
          title: const Text('Add Milestone Task'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: titleController,
                decoration: const InputDecoration(
                  labelText: 'Task Title',
                  hintText: 'e.g. 1st Draft, Initial Convo, Fixing',
                ),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                value: priority,
                decoration: const InputDecoration(labelText: 'Priority Level'),
                items: const [
                  DropdownMenuItem(value: 'urgent', child: Text('🚨 Urgent (5h Deadline)')),
                  DropdownMenuItem(value: 'high', child: Text('⚡ High (Milestone Draft)')),
                  DropdownMenuItem(value: 'medium', child: Text('🔷 Medium (Fixing)')),
                  DropdownMenuItem(value: 'low', child: Text('☕ Low (Minor)')),
                ],
                onChanged: (val) {
                  if (val != null) setDlgState(() => priority = val);
                },
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
                final t = titleController.text.trim();
                if (t.isEmpty) return;
                Navigator.of(ctx).pop();
                try {
                  final api = Provider.of<ApiService>(context, listen: false);
                  await api.post('/projects/${widget.project.id}/tasks', {
                    'title': t,
                    'priority': priority,
                  });
                  _fetchFullDetails();
                } catch (e) {
                  debugPrint('Error adding task: $e');
                }
              },
              child: const Text('Save Task'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  void _confirmDeleteProject(BuildContext context, ProjectsProvider prov) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.warning_amber_rounded, color: AppColors.danger),
            SizedBox(width: 8),
            Text('Delete Order?'),
          ],
        ),
        content: Text(
          'Are you sure you want to permanently delete order ${_project.orderId}? This removes all history, milestones, and split allocations. This action cannot be undone.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.danger,
              foregroundColor: Colors.white,
            ),
            onPressed: () async {
              Navigator.of(ctx).pop();
              final success = await prov.deleteProject(_project.id);
              if (mounted) {
                Navigator.of(context).pop();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(success
                        ? 'Order ${_project.orderId} permanently deleted.'
                        : (prov.errorMessage ?? 'Failed to delete order.')),
                    backgroundColor: success ? AppColors.success : AppColors.danger,
                  ),
                );
              }
            },
            child: const Text('Delete Permanently'),
          ),
        ],
      ),
    );
  }

  void _showStatusDialog(BuildContext context, ProjectsProvider prov) {
    final statuses = [
      {'key': 'nra', 'label': 'Not Started (NRA)'},
      {'key': 'wip', 'label': 'In Progress (WIP)'},
      {'key': 'delivery_submitted', 'label': 'Delivery Submitted'},
      {'key': 'revision', 'label': 'In Revision'},
      {'key': 'delivery_accepted', 'label': 'Delivery Accepted'},
      {'key': 'completed', 'label': 'Completed'},
      {'key': 'cancelled', 'label': 'Cancelled'},
    ];

    showDialog(
      context: context,
      builder: (ctx) => SimpleDialog(
        title: const Text('Update Order Status'),
        children: statuses.map((s) {
          final isSelected = _project.status.toLowerCase() == s['key'];
          return SimpleDialogOption(
            onPressed: () async {
              Navigator.of(ctx).pop();
              final success = await prov.updateStatus(_project.id, s['key']!);
              if (success && mounted) {
                Navigator.of(context).pop();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('Status updated to ${s['label']}')),
                );
              }
            },
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  s['label']!,
                  style: TextStyle(
                    fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                    color: isSelected ? AppColors.primaryBlueLight : null,
                  ),
                ),
                if (isSelected)
                  const Icon(Icons.check_circle_rounded, color: AppColors.primaryBlueLight, size: 18),
              ],
            ),
          );
        }).toList(),
      ),
    );
  }

  Future<void> _handleAddNote(ProjectsProvider prov) async {
    final note = _noteController.text.trim();
    if (note.isEmpty) return;

    setState(() => _isAddingNote = true);
    final success = await prov.addNote(_project.id, note);
    setState(() => _isAddingNote = false);

    if (success) {
      _noteController.clear();
      _fetchFullDetails();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Note added successfully')),
        );
      }
    }
  }

  Future<void> _showManageAssigneesDialog(
    BuildContext context,
    ProjectsProvider prov,
    AuthProvider auth,
  ) async {
    final membersProv = Provider.of<TeamMembersProvider>(context, listen: false);
    if (membersProv.members.isEmpty) {
      await membersProv.fetchMembers();
    }
    if (!context.mounted) return;

    final selectedIds = _project.assignees.map((a) => a.userId).toSet();
    String selectedTeam = _project.team.isNotEmpty ? _project.team : auth.userTeam;
    String searchText = '';
    const teams = ['WpCraft', 'WpTitans', 'WpNexus', 'WpHorizon'];
    bool saving = false;

    await showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (dlgCtx, setDlgState) {
          final q = searchText.trim().toLowerCase();
          final candidates = membersProv.members.where((m) {
            if (!auth.canViewAllTeams &&
                selectedTeam.isNotEmpty &&
                m.team.toLowerCase().replaceAll(' ', '') !=
                    selectedTeam.toLowerCase().replaceAll(' ', '')) {
              return false;
            }
            if (q.isNotEmpty) {
              return m.name.toLowerCase().contains(q) ||
                  m.callSign.toLowerCase().contains(q) ||
                  m.team.toLowerCase().contains(q);
            }
            return true;
          }).toList();

          return AlertDialog(
            title: Text('Assignees & Team (${_project.orderId})'),
            content: SizedBox(
              width: 360,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (auth.canViewAllTeams) ...[
                    DropdownButtonFormField<String>(
                      value: teams.contains(selectedTeam) ? selectedTeam : teams.first,
                      decoration: const InputDecoration(
                        labelText: 'Owning Squad',
                        isDense: true,
                      ),
                      items: teams
                          .map((t) => DropdownMenuItem(value: t, child: Text(t)))
                          .toList(),
                      onChanged: (val) {
                        if (val != null) setDlgState(() => selectedTeam = val);
                      },
                    ),
                    const SizedBox(height: 10),
                  ],
                  TextField(
                    decoration: const InputDecoration(
                      hintText: 'Search specialist name or squad...',
                      prefixIcon: Icon(Icons.search_rounded, size: 18),
                      isDense: true,
                    ),
                    onChanged: (v) => setDlgState(() => searchText = v),
                  ),
                  const SizedBox(height: 8),
                  SizedBox(
                    height: 250,
                    child: candidates.isEmpty
                        ? const Center(child: Text('No specialists match search.'))
                        : ListView.builder(
                            itemCount: candidates.length,
                            itemBuilder: (_, idx) {
                              final m = candidates[idx];
                              final checked = selectedIds.contains(m.id);
                              return CheckboxListTile(
                                dense: true,
                                value: checked,
                                title: Text(
                                  m.name,
                                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                                ),
                                subtitle: Text(
                                  '${m.team} • ${m.roleLabel}',
                                  style: const TextStyle(fontSize: 11),
                                ),
                                onChanged: (v) {
                                  setDlgState(() {
                                    if (v == true) {
                                      selectedIds.add(m.id);
                                    } else {
                                      selectedIds.remove(m.id);
                                    }
                                  });
                                },
                              );
                            },
                          ),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(
                onPressed: saving ? null : () => Navigator.of(dlgCtx).pop(),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: saving
                    ? null
                    : () async {
                        setDlgState(() => saving = true);
                        final res = await prov.updateAssignees(
                          _project.id,
                          selectedIds.toList(),
                          teamName: selectedTeam,
                        );
                        if (dlgCtx.mounted) Navigator.of(dlgCtx).pop();
                        if (mounted) {
                          if (res.success) _fetchFullDetails();
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(res.message),
                              backgroundColor: res.success ? AppColors.success : AppColors.danger,
                            ),
                          );
                        }
                      },
                child: Text(saving ? 'Saving...' : 'Save (${selectedIds.length})'),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _showEditSplitsDialog(
    BuildContext context,
    ProjectsProvider prov,
  ) async {
    if (_project.assignees.isEmpty) return;
    final controllers = <int, TextEditingController>{};
    for (final a in _project.assignees) {
      controllers[a.userId] = TextEditingController(
        text: a.splitPercent.toStringAsFixed(0),
      );
    }
    bool saving = false;

    await showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (dlgCtx, setDlgState) {
          final double total = controllers.values.fold<double>(
            0.0,
            (sum, c) => sum + (double.tryParse(c.text.trim()) ?? 0.0),
          );
          final bool validSum = (total - 100.0).abs() <= 0.1;

          return AlertDialog(
            title: Text('Edit Value Splits (${_project.orderId})'),
            content: SizedBox(
              width: 340,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  ..._project.assignees.map((a) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: Row(
                        children: [
                          Expanded(
                            child: Text(
                              a.name,
                              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                            ),
                          ),
                          SizedBox(
                            width: 84,
                            child: TextField(
                              controller: controllers[a.userId],
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: const InputDecoration(
                                suffixText: '%',
                                isDense: true,
                              ),
                              onChanged: (_) => setDlgState(() {}),
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                  const SizedBox(height: 6),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Total Allocation:', style: TextStyle(fontWeight: FontWeight.w700)),
                      Text(
                        '${total.toStringAsFixed(1)}%',
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          color: validSum ? AppColors.success : AppColors.danger,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(
                onPressed: saving ? null : () => Navigator.of(dlgCtx).pop(),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: (!validSum || saving)
                    ? null
                    : () async {
                        setDlgState(() => saving = true);
                        final map = <int, double>{};
                        controllers.forEach((uid, ctrl) {
                          map[uid] = double.tryParse(ctrl.text.trim()) ?? 0.0;
                        });
                        final res = await prov.updateSplits(_project.id, map);
                        if (dlgCtx.mounted) Navigator.of(dlgCtx).pop();
                        if (mounted) {
                          if (res.success) _fetchFullDetails();
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(res.message),
                              backgroundColor: res.success ? AppColors.success : AppColors.danger,
                            ),
                          );
                        }
                      },
                child: Text(saving ? 'Saving...' : 'Save Splits'),
              ),
            ],
          );
        },
      ),
    );

    for (final c in controllers.values) {
      c.dispose();
    }
  }

  Future<void> _showReviewDialog(
    BuildContext context,
    ProjectsProvider prov,
  ) async {
    bool reviewed = _project.clientReviewed == 'yes';
    int rating = _project.starRating > 0 ? _project.starRating : 5;
    final feedbackCtrl = TextEditingController(text: _project.reviewFeedback);
    bool saving = false;

    await showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (dlgCtx, setDlgState) => AlertDialog(
          title: const Text('Client Review & Satisfaction'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Client Submitted Review', style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600)),
                value: reviewed,
                onChanged: (v) => setDlgState(() => reviewed = v),
              ),
              const SizedBox(height: 6),
              const Text('Star Rating:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
              const SizedBox(height: 6),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(5, (idx) {
                  final starVal = idx + 1;
                  return IconButton(
                    icon: Icon(
                      starVal <= rating ? Icons.star_rounded : Icons.star_outline_rounded,
                      color: const Color(0xFFFFD700),
                      size: 28,
                    ),
                    onPressed: reviewed ? () => setDlgState(() => rating = starVal) : null,
                  );
                }),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: feedbackCtrl,
                maxLines: 3,
                enabled: reviewed,
                decoration: const InputDecoration(
                  labelText: 'Client Feedback Note',
                  hintText: 'Quote or summary of client review...',
                  border: OutlineInputBorder(),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: saving ? null : () => Navigator.of(dlgCtx).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: saving
                  ? null
                  : () async {
                      setDlgState(() => saving = true);
                      final res = await prov.updateReview(
                        _project.id,
                        clientReviewed: reviewed ? 'yes' : 'no',
                        starRating: reviewed ? rating.toDouble() : 0.0,
                        reviewFeedback: reviewed ? feedbackCtrl.text.trim() : '',
                      );
                      if (dlgCtx.mounted) Navigator.of(dlgCtx).pop();
                      if (mounted) {
                        if (res.success) _fetchFullDetails();
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(res.message),
                            backgroundColor: res.success ? AppColors.success : AppColors.danger,
                          ),
                        );
                      }
                    },
              child: Text(saving ? 'Saving...' : 'Save Review'),
            ),
          ],
        ),
      ),
    );
    feedbackCtrl.dispose();
  }

  Future<void> _showSettlementDialog(
    BuildContext context,
    ProjectsProvider prov,
  ) async {
    final deliveredCtrl = TextEditingController(text: _project.deliveredDate);
    final tipAmtCtrl = TextEditingController(
      text: _project.tips > 0 ? _project.tips.toStringAsFixed(0) : '0',
    );
    final tipDateCtrl = TextEditingController(text: _project.tipDate);
    bool saving = false;

    Future<void> pickDate(TextEditingController ctrl, StateSetter setDlgState) async {
      final init = DateTime.tryParse(ctrl.text.trim()) ?? DateTime.now();
      final picked = await showDatePicker(
        context: context,
        initialDate: init,
        firstDate: DateTime(2024),
        lastDate: DateTime(2030),
      );
      if (picked != null) {
        setDlgState(() {
          ctrl.text =
              '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
        });
      }
    }

    await showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (dlgCtx, setDlgState) => AlertDialog(
          title: const Text('Delivery Settlement & Client Tip'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: deliveredCtrl,
                readOnly: true,
                decoration: InputDecoration(
                  labelText: 'Actual Delivered Date (YYYY-MM-DD)',
                  suffixIcon: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (deliveredCtrl.text.isNotEmpty)
                        IconButton(
                          icon: const Icon(Icons.clear_rounded, size: 16),
                          onPressed: () => setDlgState(() => deliveredCtrl.clear()),
                        ),
                      IconButton(
                        icon: const Icon(Icons.calendar_today_rounded, size: 16),
                        onPressed: () => pickDate(deliveredCtrl, setDlgState),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: tipAmtCtrl,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(
                  labelText: 'Client Tip Amount (\$)',
                  prefixText: '\$ ',
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: tipDateCtrl,
                readOnly: true,
                decoration: InputDecoration(
                  labelText: 'Tip Credited Date (optional)',
                  suffixIcon: IconButton(
                    icon: const Icon(Icons.calendar_today_rounded, size: 16),
                    onPressed: () => pickDate(tipDateCtrl, setDlgState),
                  ),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: saving ? null : () => Navigator.of(dlgCtx).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: saving
                  ? null
                  : () async {
                      setDlgState(() => saving = true);
                      final res = await prov.updateSettlement(
                        _project.id,
                        deliveredDate: deliveredCtrl.text.trim().isNotEmpty
                            ? deliveredCtrl.text.trim()
                            : null,
                        tipAmount: double.tryParse(tipAmtCtrl.text.trim()) ?? 0.0,
                        tipDate: tipDateCtrl.text.trim().isNotEmpty
                            ? tipDateCtrl.text.trim()
                            : null,
                      );
                      if (dlgCtx.mounted) Navigator.of(dlgCtx).pop();
                      if (mounted) {
                        if (res.success) _fetchFullDetails();
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(res.message),
                            backgroundColor: res.success ? AppColors.success : AppColors.danger,
                          ),
                        );
                      }
                    },
              child: Text(saving ? 'Saving...' : 'Save Settlement'),
            ),
          ],
        ),
      ),
    );

    deliveredCtrl.dispose();
    tipAmtCtrl.dispose();
    tipDateCtrl.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final prov = Provider.of<ProjectsProvider>(context);
    final auth = Provider.of<AuthProvider>(context);
    final p = _project;

    return DraggableScrollableSheet(
      initialChildSize: 0.88,
      minChildSize: 0.5,
      maxChildSize: 0.96,
      expand: false,
      builder: (context, scrollController) {
        return Container(
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              // Sheet Grabber
              Padding(
                padding: const EdgeInsets.only(top: 12, bottom: 8),
                child: Container(
                  width: 38,
                  height: 4,
                  decoration: BoxDecoration(
                    color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),

              // Sheet Header
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Text(
                          p.orderId,
                          style: const TextStyle(
                            color: AppColors.primaryBlueLight,
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        if (p.team.isNotEmpty) ...[
                          const SizedBox(width: 8),
                          TeamBadge(team: p.team),
                        ],
                      ],
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded),
                      onPressed: () => Navigator.of(context).pop(),
                      constraints: const BoxConstraints(minWidth: 44, minHeight: 44),
                    ),
                  ],
                ),
              ),

              const Divider(height: 1),

              // Scrollable Details Body
              Expanded(
                child: ListView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(20),
                  children: [
                    // Status Badge & Deadline Row
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        StatusBadge(status: p.status),
                        Flexible(
                          child: CountdownPill(
                            deliveryDateStr: p.deliveryDate,
                            badgeText: p.daysRemainingBadge,
                            urgency: p.daysRemainingUrgency,
                            status: p.status,
                            isCompleted: p.isCompleted,
                            isSubmitted: p.isSubmitted,
                            isOntime: p.isOntime,
                            extensionCount: p.extensionCount,
                            extensionDays: p.extensionDays,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Project Title
                    Text(
                      p.title,
                      style: TextStyle(
                        color: isDark ? AppColors.textLight : AppColors.textDark,
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        height: 1.3,
                      ),
                    ),
                    const SizedBox(height: 8),

                    // Client & Account Meta
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: isDark ? AppColors.darkCard : AppColors.lightCard,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                      ),
                      child: Column(
                        children: [
                          _buildDetailRow('Client Buyer', p.buyerUsername, isDark),
                          const Divider(height: 14),
                          _buildDetailRow('Fiverr Profile', p.profileName, isDark),
                          const Divider(height: 14),
                          _buildDetailRow('Order Source', p.orderSource, isDark),
                          const Divider(height: 14),
                          _buildDetailRow('Delivery Deadline', p.deliveryDate, isDark),
                          if (p.deliveredDate.isNotEmpty) ...[
                            const Divider(height: 14),
                            _buildDetailRow('Delivered Date', p.deliveredDate, isDark),
                          ],
                          if (p.tipDate.isNotEmpty) ...[
                            const Divider(height: 14),
                            _buildDetailRow('Tip Credited Date', p.tipDate, isDark),
                          ],
                          if (p.isDelivered || p.status.toLowerCase() == 'completed' || p.deliveredDate.isNotEmpty) ...[
                            const Divider(height: 14),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'On-Time Delivery',
                                  style: TextStyle(
                                    color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                    fontSize: 12,
                                  ),
                                ),
                                Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: (p.isOntime ? AppColors.success : AppColors.danger).withOpacity(0.15),
                                        borderRadius: BorderRadius.circular(6),
                                        border: Border.all(
                                          color: (p.isOntime ? AppColors.success : AppColors.danger).withOpacity(0.4),
                                        ),
                                      ),
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Icon(
                                            p.isOntime ? Icons.check_circle_rounded : Icons.cancel_rounded,
                                            size: 13,
                                            color: p.isOntime ? AppColors.success : AppColors.danger,
                                          ),
                                          const SizedBox(width: 4),
                                          Text(
                                            p.isOntime ? 'On-Time Delivery' : 'Delayed / Extension',
                                            style: TextStyle(
                                              color: p.isOntime ? AppColors.success : AppColors.danger,
                                              fontSize: 11,
                                              fontWeight: FontWeight.w700,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    if (auth.canEditProjects && !auth.isViewOnly) ...[
                                      const SizedBox(width: 6),
                                      IconButton(
                                        icon: const Icon(Icons.swap_horiz_rounded, size: 16),
                                        tooltip: 'Toggle On-Time Status',
                                        padding: EdgeInsets.zero,
                                        constraints: const BoxConstraints(),
                                        onPressed: () async {
                                          final newStatus = !p.isOntime;
                                          final success = await prov.updateOnTime(p.id, newStatus);
                                          if (success) {
                                            _fetchFullDetails();
                                          }
                                        },
                                      ),
                                    ],
                                  ],
                                ),
                              ],
                            ),
                          ],
                          if (p.orderSource.isNotEmpty) ...[
                            const Divider(height: 14),
                            _buildDetailRow(
                              'Order Source',
                              p.orderSource == 'Repeat Client' ? '🔁 Repeat Client' : p.orderSource,
                              isDark,
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Google Sheet Link / Project Attachment Banner
                    if (p.projectSheetLink.isNotEmpty) ...[
                      InkWell(
                        onTap: () async {
                          final raw = p.projectSheetLink.trim();
                          final url = raw.startsWith('http://') || raw.startsWith('https://') ? raw : 'https://$raw';
                          final uri = Uri.tryParse(url);
                          if (uri != null) {
                            await launchUrl(uri, mode: LaunchMode.externalApplication);
                          }
                        },
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          decoration: BoxDecoration(
                            color: const Color(0xFF0F766E).withOpacity(0.12),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFF14B8A6).withOpacity(0.4)),
                          ),
                          child: Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF0D9488),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.table_chart_rounded, size: 18, color: Colors.white),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text(
                                      'Google Sheet & Requirements',
                                      style: TextStyle(
                                        color: Color(0xFF0D9488),
                                        fontWeight: FontWeight.w800,
                                        fontSize: 13,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      p.projectSheetLink,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                        fontSize: 11,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Icon(Icons.open_in_new_rounded, size: 16, color: Color(0xFF0D9488)),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],

                    // Financial Realization Box (80% Contract)
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: AppColors.cardGlowGradient,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.primaryBlue.withOpacity(0.3)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          Column(
                            children: [
                              Text(
                                'ORDER VALUE',
                                style: TextStyle(
                                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  letterSpacing: 0.5,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '\$${p.orderValue.toStringAsFixed(0)}',
                                style: TextStyle(
                                  color: isDark ? AppColors.textLight : AppColors.textDark,
                                  fontSize: 20,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ],
                          ),
                          Container(
                            height: 36,
                            width: 1,
                            color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                          ),
                          Column(
                            children: [
                              const Text(
                                'NET REVENUE (80%)',
                                style: TextStyle(
                                  color: AppColors.success,
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  letterSpacing: 0.5,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '\$${p.netValue.toStringAsFixed(0)}',
                                style: const TextStyle(
                                  color: AppColors.success,
                                  fontSize: 20,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ],
                          ),
                          if (p.tips > 0) ...[
                            Container(
                              height: 36,
                              width: 1,
                              color: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                            ),
                            Column(
                              children: [
                                const Text(
                                  'TIPS',
                                  style: TextStyle(
                                    color: AppColors.warning,
                                    fontSize: 10,
                                    fontWeight: FontWeight.w700,
                                    letterSpacing: 0.5,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '\$${p.tips.toStringAsFixed(0)}',
                                  style: const TextStyle(
                                    color: AppColors.warning,
                                    fontSize: 20,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),

                    // Progress Slider Bar
                    CustomProgressBar(progressPercent: p.progressPercent, height: 8),
                    if (p.latestProgress.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        'Latest: ${p.latestProgress}',
                        style: TextStyle(
                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                          fontSize: 12,
                          fontStyle: FontStyle.italic,
                        ),
                      ),
                    ],
                    const SizedBox(height: 20),

                    // 1. Combined Orders Section (ftmCombined)
                    if (p.isCombined || p.splits.isNotEmpty) ...[
                      _buildSectionHeader('COMBINED ORDER DISTRIBUTION', isDark),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: isDark ? AppColors.darkCard : AppColors.lightCard,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0xFF8B5CF6).withOpacity(0.35)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Text(
                                  'Full Order Total Value:',
                                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                                ),
                                Text(
                                  '\$${p.orderTotalValue.toStringAsFixed(0)}',
                                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Color(0xFFA78BFA)),
                                ),
                              ],
                            ),
                            const Divider(height: 14),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Text('My Realized Share (Net):', style: TextStyle(fontSize: 12)),
                                Text(
                                  '\$${p.netValue.toStringAsFixed(0)}',
                                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.success),
                                ),
                              ],
                            ),
                            if (p.othersValue > 0) ...[
                              const SizedBox(height: 4),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text('Partner & Other Teams Share:', style: TextStyle(fontSize: 12)),
                                  Text(
                                    '\$${p.othersValue.toStringAsFixed(0)}',
                                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.cyanAccent),
                                  ),
                                ],
                              ),
                            ],
                            if (p.splits.isNotEmpty) ...[
                              const Divider(height: 18),
                              const Text(
                                'Split Allocation Breakdown:',
                                style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.5),
                              ),
                              const SizedBox(height: 8),
                              ...p.splits.map((split) {
                                return Container(
                                  margin: const EdgeInsets.only(bottom: 6),
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                  decoration: BoxDecoration(
                                    color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
                                    borderRadius: BorderRadius.circular(8),
                                    border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                                  ),
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              split.partyLabel,
                                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
                                            ),
                                            if (split.skill.isNotEmpty)
                                              Text(
                                                split.skill,
                                                style: TextStyle(
                                                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                                  fontSize: 10,
                                                ),
                                              ),
                                            if (split.note.isNotEmpty)
                                              Text(
                                                split.note,
                                                style: TextStyle(
                                                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                                  fontSize: 10,
                                                  fontStyle: FontStyle.italic,
                                                ),
                                              ),
                                          ],
                                        ),
                                      ),
                                      Text(
                                        '\$${split.shareNet.toStringAsFixed(0)}',
                                        style: const TextStyle(
                                          fontSize: 13,
                                          fontWeight: FontWeight.w800,
                                          color: Color(0xFFA78BFA),
                                        ),
                                      ),
                                    ],
                                  ),
                                );
                              }),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: 18),
                    ],

                    // 2. Subscription Orders Section (ftmSub)
                    if (p.orderType == 'subscription' || p.subscriptionStatus.isNotEmpty) ...[
                      _buildSectionHeader('SUBSCRIPTION CONTRACT', isDark),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: isDark ? AppColors.darkCard : AppColors.lightCard,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: AppColors.cyanAccent.withOpacity(0.35)),
                        ),
                        child: Column(
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Text('Subscription Status:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(
                                    color: (p.subscriptionStatus == 'active' ? AppColors.success : AppColors.danger).withOpacity(0.15),
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  child: Text(
                                    p.subscriptionStatus.toUpperCase(),
                                    style: TextStyle(
                                      color: p.subscriptionStatus == 'active' ? AppColors.success : AppColors.danger,
                                      fontSize: 11,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const Divider(height: 14),
                            _buildDetailRow('Renewal Frequency', '${p.subscriptionTermDays} Days Cycle', isDark),
                            if (p.subscriptionNextDate != null && p.subscriptionNextDate!.isNotEmpty) ...[
                              const Divider(height: 14),
                              _buildDetailRow('Next Renewal Date', p.subscriptionNextDate!, isDark),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: 18),
                    ],

                    // 3. Client Review & Feedback
                    if (p.clientReviewed == 'yes' && p.starRating > 0) ...[
                      _buildSectionHeader('CLIENT REVIEW & SATISFACTION', isDark),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: isDark ? AppColors.darkCard : AppColors.lightCard,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0xFFFFD700).withOpacity(0.35)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Row(
                                  children: List.generate(5, (idx) {
                                    return Icon(
                                      idx < p.starRating ? Icons.star_rounded : Icons.star_outline_rounded,
                                      color: const Color(0xFFFFD700),
                                      size: 20,
                                    );
                                  }),
                                ),
                                const SizedBox(width: 8),
                                Text(
                                  '${p.starRating}.0 Rating',
                                  style: const TextStyle(
                                    color: Color(0xFFFFD700),
                                    fontSize: 13,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ],
                            ),
                            if (p.reviewFeedback.isNotEmpty) ...[
                              const SizedBox(height: 8),
                              Text(
                                '"${p.reviewFeedback}"',
                                style: TextStyle(
                                  color: isDark ? AppColors.textLight : AppColors.textDark,
                                  fontSize: 13,
                                  fontStyle: FontStyle.italic,
                                  height: 1.4,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: 18),
                    ],

                    // 4. Delivery Extensions History
                    if (p.extensionCount > 0) ...[
                      _buildSectionHeader('DELIVERY EXTENSION HISTORY', isDark),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: isDark ? AppColors.darkCard : AppColors.lightCard,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: AppColors.warning.withOpacity(0.35)),
                        ),
                        child: Column(
                          children: [
                            _buildDetailRow('Extensions Count', '${p.extensionCount} time(s)', isDark),
                            if (p.extensionDays > 0) ...[
                              const Divider(height: 14),
                              _buildDetailRow('Total Extra Days Added', '+${p.extensionDays} days', isDark),
                            ],
                            if (p.extensionReason.isNotEmpty) ...[
                              const Divider(height: 14),
                              _buildDetailRow('Latest Reason', p.extensionReason, isDark),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: 18),
                    ],

                    // 5. Assigned Specialists & Share Splits (ftmShare)
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: _buildSectionHeader('ASSIGNED SPECIALISTS & VALUE SPLITS', isDark),
                        ),
                        if (auth.canEditProjects && !auth.isViewOnly)
                          Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              TextButton.icon(
                                onPressed: () => _showManageAssigneesDialog(context, prov, auth),
                                icon: const Icon(Icons.group_add_rounded, size: 15),
                                label: const Text('Assign', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                                style: TextButton.styleFrom(
                                  visualDensity: VisualDensity.compact,
                                  padding: const EdgeInsets.symmetric(horizontal: 8),
                                ),
                              ),
                              if (p.assignees.length >= 2)
                                TextButton.icon(
                                  onPressed: () => _showEditSplitsDialog(context, prov),
                                  icon: const Icon(Icons.pie_chart_rounded, size: 15),
                                  label: const Text('Splits', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                                  style: TextButton.styleFrom(
                                    visualDensity: VisualDensity.compact,
                                    padding: const EdgeInsets.symmetric(horizontal: 8),
                                  ),
                                ),
                            ],
                          ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    if (p.assignees.isEmpty)
                      Text(
                        'No specialist assigned yet',
                        style: TextStyle(color: isDark ? AppColors.textDim : AppColors.textDarkMuted, fontSize: 13),
                      )
                    else
                      ...p.assignees.map((assignee) {
                        return Container(
                          margin: const EdgeInsets.only(bottom: 6),
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            color: isDark ? AppColors.darkCard : AppColors.lightCard,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                          ),
                          child: Row(
                            children: [
                              CircleAvatar(
                                radius: 14,
                                backgroundColor: AppColors.primaryBlue.withOpacity(0.2),
                                backgroundImage: assignee.avatar.isNotEmpty ? NetworkImage(assignee.avatar) : null,
                                child: assignee.avatar.isEmpty
                                    ? Text(
                                        assignee.name.isNotEmpty ? assignee.name[0].toUpperCase() : 'S',
                                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight),
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
                                          assignee.name,
                                          style: TextStyle(
                                            color: isDark ? AppColors.textLight : AppColors.textDark,
                                            fontSize: 13,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                        if (assignee.team.isNotEmpty) ...[
                                          const SizedBox(width: 6),
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                            decoration: BoxDecoration(
                                              color: AppColors.primaryBlue.withOpacity(0.15),
                                              borderRadius: BorderRadius.circular(4),
                                            ),
                                            child: Text(
                                              assignee.team,
                                              style: const TextStyle(
                                                color: AppColors.primaryBlueLight,
                                                fontSize: 9.5,
                                                fontWeight: FontWeight.w700,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ],
                                    ),
                                    if (assignee.roleLabel.isNotEmpty)
                                      Text(
                                        assignee.roleLabel,
                                        style: TextStyle(
                                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                          fontSize: 11,
                                        ),
                                      ),
                                  ],
                                ),
                              ),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: AppColors.primaryBlue.withOpacity(0.12),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      '${assignee.splitPercent.toStringAsFixed(0)}% Split',
                                      style: const TextStyle(
                                        color: AppColors.primaryBlueLight,
                                        fontSize: 11,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    '\$${(p.netValue * assignee.splitPercent / 100).toStringAsFixed(0)}',
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
                        );
                      }),

                    const SizedBox(height: 18),

                    // 6. Pipeline Tasks & Milestones
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        _buildSectionHeader('PIPELINE TASKS & MILESTONES (${_tasks.length})', isDark),
                        if (!auth.isExecutive && !auth.isViewOnly)
                          TextButton.icon(
                            onPressed: () => _showAddTaskDialog(context),
                            icon: const Icon(Icons.add, size: 14),
                            label: const Text('Add Task', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                          ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    if (_tasks.isEmpty)
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: isDark ? AppColors.darkCard : AppColors.lightCard,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                        ),
                        child: Text(
                          'No pipeline milestones recorded yet. Standard milestones generate automatically on project creation.',
                          style: TextStyle(color: isDark ? AppColors.textDim : AppColors.textDarkMuted, fontSize: 12.5),
                        ),
                      )
                    else
                      ...List.generate(_tasks.length, (idx) {
                        final t = _tasks[idx];
                        final isDone = t['status'] == 'done';
                        final priority = t['priority']?.toString() ?? 'medium';
                        final prioColor = priority == 'urgent'
                            ? AppColors.danger
                            : (priority == 'high'
                                ? AppColors.warning
                                : (priority == 'low' ? AppColors.textDim : AppColors.primaryBlueLight));

                        return Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                          decoration: BoxDecoration(
                            color: isDark ? AppColors.darkCard : AppColors.lightCard,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isDone
                                  ? AppColors.success.withOpacity(0.3)
                                  : (isDark ? AppColors.darkBorder : AppColors.lightBorder),
                            ),
                          ),
                          child: Row(
                            children: [
                              Checkbox(
                                value: isDone,
                                activeColor: AppColors.primaryBlue,
                                onChanged: (auth.isExecutive || auth.isViewOnly)
                                    ? null
                                    : (val) async {
                                        final newVal = val ?? false;
                                        setState(() {
                                          t['status'] = newVal ? 'done' : 'pending';
                                        });
                                        try {
                                          final api = Provider.of<ApiService>(context, listen: false);
                                          await api.post('/tasks/${t['id']}/status', {
                                            'status': newVal ? 'done' : 'pending',
                                          });
                                        } catch (e) {
                                          _fetchFullDetails();
                                        }
                                      },
                              ),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Expanded(
                                          child: Text(
                                            t['title']?.toString() ?? '',
                                            style: TextStyle(
                                              color: isDone
                                                  ? (isDark ? AppColors.textDim : AppColors.textDarkMuted)
                                                  : (isDark ? AppColors.textLight : AppColors.textDark),
                                              fontWeight: FontWeight.w600,
                                              fontSize: 13,
                                              decoration: isDone ? TextDecoration.lineThrough : null,
                                            ),
                                          ),
                                        ),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: prioColor.withOpacity(0.12),
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: Text(
                                            priority.toUpperCase(),
                                            style: TextStyle(
                                              color: prioColor,
                                              fontSize: 9.5,
                                              fontWeight: FontWeight.w800,
                                            ),
                                          ),
                                        ),
                                        if (auth.canEditProjects && !auth.isViewOnly) ...[
                                          const SizedBox(width: 4),
                                          IconButton(
                                            icon: const Icon(Icons.delete_outline_rounded, size: 17, color: AppColors.danger),
                                            tooltip: 'Delete Task',
                                            constraints: const BoxConstraints(),
                                            padding: const EdgeInsets.all(4),
                                            onPressed: () async {
                                              final taskId = int.tryParse(t['id']?.toString() ?? '0') ?? 0;
                                              if (taskId <= 0) return;
                                              try {
                                                final api = Provider.of<ApiService>(context, listen: false);
                                                await api.deleteProjectTask(taskId);
                                                _fetchFullDetails();
                                              } catch (e) {
                                                if (mounted) {
                                                  ScaffoldMessenger.of(this.context).showSnackBar(
                                                    SnackBar(content: Text('Failed to delete task: $e'), backgroundColor: AppColors.danger),
                                                  );
                                                }
                                              }
                                            },
                                          ),
                                        ],
                                      ],
                                    ),
                                    if (t['deadline_formatted'] != null && t['deadline_formatted'].toString().isNotEmpty) ...[
                                      const SizedBox(height: 3),
                                      Text(
                                        '⏰ Deadline: ${t['deadline_formatted']}',
                                        style: TextStyle(
                                          color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                          fontSize: 11,
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                            ],
                          ),
                        );
                      }),

                    const SizedBox(height: 20),

                    // Management Actions (Executive / Deputy is View-Only)
                    if (auth.isExecutive || auth.isViewOnly) ...[
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: const Color(0xFF8B5CF6).withOpacity(0.12),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFF8B5CF6).withOpacity(0.35)),
                        ),
                        child: const Row(
                          children: [
                            Icon(Icons.visibility_rounded, color: Color(0xFFA78BFA), size: 20),
                            SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                'Executive View-Only: Order telemetry and financial records are in read-only audit mode.',
                                style: TextStyle(color: Color(0xFFA78BFA), fontSize: 12, fontWeight: FontWeight.w600),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ] else ...[
                      _buildSectionHeader('ORDER ACTIONS', isDark),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          Expanded(
                            child: ElevatedButton.icon(
                              onPressed: () {
                                Navigator.of(context).pop();
                                showModalBottomSheet(
                                  context: context,
                                  isScrollControlled: true,
                                  builder: (_) => UpdateProgressSheet(project: p),
                                );
                              },
                              icon: const Icon(Icons.sync_rounded, size: 16),
                              label: const Text('Progress', style: TextStyle(fontSize: 12)),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: () => _showStatusDialog(context, prov),
                              icon: const Icon(Icons.flag_rounded, size: 16),
                              label: const Text('Status', style: TextStyle(fontSize: 12)),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: () {
                                Navigator.of(context).pop();
                                showModalBottomSheet(
                                  context: context,
                                  isScrollControlled: true,
                                  builder: (_) => ExtendDeliverySheet(project: p),
                                );
                              },
                              icon: const Icon(Icons.schedule_rounded, size: 16),
                              label: const Text('Extend', style: TextStyle(fontSize: 12)),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: () => _showReviewDialog(context, prov),
                              icon: const Icon(Icons.star_rate_rounded, size: 16, color: Color(0xFFF59E0B)),
                              label: const Text('Review & Stars', style: TextStyle(fontSize: 12)),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: () => _showSettlementDialog(context, prov),
                              icon: const Icon(Icons.payments_rounded, size: 16, color: AppColors.success),
                              label: const Text('Delivered & Tip', style: TextStyle(fontSize: 12)),
                            ),
                          ),
                        ],
                      ),
                      if (auth.canEditProjects || auth.canDeleteProjects) ...[
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            if (auth.canEditProjects)
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: () async {
                                    final updated = await showModalBottomSheet<bool>(
                                      context: context,
                                      isScrollControlled: true,
                                      builder: (_) => EditProjectSheet(project: _project),
                                    );
                                    if (updated == true) {
                                      _fetchFullDetails();
                                    }
                                  },
                                  icon: const Icon(Icons.edit_rounded, size: 16, color: AppColors.primaryBlueLight),
                                  label: const Text('Edit Order', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight)),
                                  style: OutlinedButton.styleFrom(
                                    side: const BorderSide(color: AppColors.primaryBlueLight),
                                  ),
                                ),
                              ),
                            if (auth.canEditProjects && auth.canDeleteProjects)
                              const SizedBox(width: 8),
                            if (auth.canDeleteProjects)
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: () => _confirmDeleteProject(context, prov),
                                  icon: const Icon(Icons.delete_forever_rounded, size: 16, color: AppColors.danger),
                                  label: const Text('Delete Order', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.danger)),
                                  style: OutlinedButton.styleFrom(
                                    side: const BorderSide(color: AppColors.danger),
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ],
                    ],

                    const SizedBox(height: 14),

                    // Start Work Time Tracker directly from Order (Non-Executive)
                    if (!auth.isExecutive && !auth.isViewOnly && p.isActive) ...[
                      Consumer<TimeTrackerProvider>(
                        builder: (ctx, tt, _) {
                          final isThisRunning = tt.activeSession?.projectId == p.id && tt.isTracking;
                          return SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: tt.isSubmitting
                                  ? null
                                  : () async {
                                      if (isThisRunning) {
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          SnackBar(
                                            content: Text('Timer already running (${tt.formattedClock}). Manage in Time Tracker screen.'),
                                          ),
                                        );
                                        return;
                                      }
                                      final res = await tt.startSession(
                                        projectId: p.id,
                                        orderId: p.orderId,
                                        clientName: p.buyerUsername,
                                        profileName: p.profileName,
                                        sessionType: 'regular',
                                      );
                                      if (mounted) {
                                        ScaffoldMessenger.of(this.context).showSnackBar(
                                          SnackBar(
                                            content: Text(res.message),
                                            backgroundColor: res.success ? AppColors.success : AppColors.danger,
                                          ),
                                        );
                                      }
                                    },
                              icon: Icon(isThisRunning ? Icons.timer_rounded : Icons.play_arrow_rounded, size: 18),
                              label: Text(
                                isThisRunning ? 'Timer Running (${tt.formattedClock})' : 'Start Time Tracker on Order',
                                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800),
                              ),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: isThisRunning ? AppColors.success : const Color(0xFF0F766E),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                            ),
                          );
                        },
                      ),
                      const SizedBox(height: 10),
                    ],

                    // Order Chat Button (All roles can participate)
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        onPressed: () {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => ChatConversationScreen(
                                conversationType: 'project',
                                conversationId: 'proj_${p.id}',
                                title: 'Order ${p.orderId} (${p.buyerUsername})',
                                subtitle: p.title,
                                team: p.team,
                                projectId: p.id,
                                orderId: p.orderId,
                              ),
                            ),
                          );
                        },
                        icon: const Icon(Icons.chat_bubble_rounded, size: 16, color: AppColors.primaryBlueLight),
                        label: const Text('Open Order Discussion & Chat', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.primaryBlueLight)),
                        style: OutlinedButton.styleFrom(
                          side: const BorderSide(color: AppColors.primaryBlueLight),
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                      ),
                    ),

                    const SizedBox(height: 24),

                    // Internal Project Notes Timeline
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'INTERNAL ORDER NOTES (${_notes.length})',
                          style: TextStyle(
                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.8,
                          ),
                        ),
                        if (_isLoadingDetails)
                          const SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primaryBlueLight),
                          ),
                      ],
                    ),
                    const SizedBox(height: 10),

                    // Add Note Input (Disabled for Executive)
                    if (!auth.isExecutive && !auth.isViewOnly) ...[
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _noteController,
                              style: TextStyle(
                                color: isDark ? AppColors.textLight : AppColors.textDark,
                                fontSize: 13,
                              ),
                              decoration: const InputDecoration(
                                hintText: 'Add an internal update or handover note...',
                                contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          IconButton(
                            onPressed: _isAddingNote ? null : () => _handleAddNote(prov),
                            icon: _isAddingNote
                                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                                : const Icon(Icons.send_rounded, color: AppColors.primaryBlueLight),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                    ],

                    if (_notes.isEmpty && !_isLoadingDetails)
                      Text(
                        'No notes posted for this project.',
                        style: TextStyle(color: isDark ? AppColors.textDim : AppColors.textDarkMuted, fontSize: 12),
                      )
                    else
                      ..._notes.map((note) {
                        return Container(
                          margin: const EdgeInsets.only(bottom: 8),
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
                                  Text(
                                    note.authorName,
                                    style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: AppColors.primaryBlueLight),
                                  ),
                                  Text(
                                    note.createdAt,
                                    style: const TextStyle(color: AppColors.textDim, fontSize: 10),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 4),
                              Text(
                                note.note,
                                style: TextStyle(
                                  color: isDark ? AppColors.textLight : AppColors.textDark,
                                  fontSize: 13,
                                  height: 1.3,
                                ),
                              ),
                            ],
                          ),
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
  }

  Widget _buildSectionHeader(String title, bool isDark) {
    return Text(
      title,
      style: TextStyle(
        color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
        fontSize: 11,
        fontWeight: FontWeight.w800,
        letterSpacing: 0.8,
      ),
    );
  }

  Widget _buildDetailRow(String label, String value, bool isDark) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
            fontSize: 12,
            fontWeight: FontWeight.w500,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            color: isDark ? AppColors.textLight : AppColors.textDark,
            fontSize: 13,
            fontWeight: FontWeight.w700,
          ),
        ),
      ],
    );
  }
}
