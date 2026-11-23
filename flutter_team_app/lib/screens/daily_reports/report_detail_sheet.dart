import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import '../../models/daily_report.dart';
import '../../providers/auth_provider.dart';
import '../../providers/daily_reports_provider.dart';
import '../../theme/app_colors.dart';
import '../../widgets/team_badge.dart';
import 'log_standup_screen.dart';

class ReportDetailSheet extends StatefulWidget {
  final DailyReport report;
  const ReportDetailSheet({super.key, required this.report});

  static void show(BuildContext context, DailyReport report) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => ReportDetailSheet(report: report),
    );
  }

  @override
  State<ReportDetailSheet> createState() => _ReportDetailSheetState();
}

class _ReportDetailSheetState extends State<ReportDetailSheet> {
  final _commentController = TextEditingController();
  bool _isActionInProgress = false;

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'approved':
        return AppColors.success;
      case 'leader_approved':
        return const Color(0xFF0D9488);
      case 'revision_requested':
        return AppColors.warning;
      case 'draft':
        return AppColors.textDim;
      case 'submitted':
      default:
        return AppColors.primaryBlueLight;
    }
  }

  Future<void> _handleApprove(DailyReportsProvider prov, AuthProvider auth) async {
    final isManager = auth.isOperationsManager;
    final action = isManager ? 'approve' : 'leader_approve';
    final defaultNote = isManager ? 'Final approved by Operations Manager' : 'Verified by Squad Leader';
    setState(() => _isActionInProgress = true);
    final res = await prov.reviewStandupAction(
      widget.report.id,
      action,
      feedback: defaultNote,
    );
    setState(() => _isActionInProgress = false);

    if (mounted) {
      if (res.success) Navigator.of(context).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res.success
              ? '${isManager ? 'Final approved' : 'Verified'} standup for ${widget.report.userName}!'
              : res.message),
          backgroundColor: res.success ? AppColors.success : AppColors.danger,
        ),
      );
    }
  }

  Future<void> _handleReset(DailyReportsProvider prov) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Reset Standup: ${widget.report.userName}'),
        content: const Text(
          'Reset this submission status back to Submitted so it can be re-verified?',
          style: TextStyle(fontSize: 13),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Reset Status', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    setState(() => _isActionInProgress = true);
    final res = await prov.reviewStandupAction(widget.report.id, 'reset', feedback: '');
    setState(() => _isActionInProgress = false);

    if (mounted) {
      if (res.success) Navigator.of(context).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res.message),
          backgroundColor: res.success ? AppColors.primaryBlue : AppColors.danger,
        ),
      );
    }
  }

  void _showCommentDialog(DailyReportsProvider prov) {
    _commentController.text = widget.report.managerFeedback ?? widget.report.leaderFeedback ?? '';
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Manager / Leader Note: ${widget.report.userName}'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Save feedback or review instructions without altering approval status:',
              style: TextStyle(fontSize: 12.5),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _commentController,
              maxLines: 3,
              autofocus: true,
              decoration: const InputDecoration(
                hintText: 'Write feedback or guidance...',
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
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primaryBlue),
            onPressed: () async {
              final note = _commentController.text.trim();
              if (note.isEmpty) return;
              Navigator.of(ctx).pop();

              setState(() => _isActionInProgress = true);
              final res = await prov.reviewStandupAction(
                widget.report.id,
                'comment',
                feedback: note,
              );
              setState(() => _isActionInProgress = false);

              if (mounted) {
                if (res.success) Navigator.of(context).pop();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(res.message),
                    backgroundColor: res.success ? AppColors.primaryBlue : AppColors.danger,
                  ),
                );
              }
            },
            child: const Text('Save Note', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }

  void _showRevisionDialog(DailyReportsProvider prov) {
    _commentController.clear();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Request Revision: ${widget.report.userName}'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Specify what needs correction or additional deliverables:',
              style: TextStyle(fontSize: 12.5),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _commentController,
              maxLines: 3,
              autofocus: true,
              decoration: const InputDecoration(
                hintText: 'e.g., Please add client name or link to latest delivery...',
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
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.warning),
            onPressed: () async {
              final note = _commentController.text.trim();
              if (note.isEmpty) return;
              Navigator.of(ctx).pop();

              setState(() => _isActionInProgress = true);
              final res = await prov.reviewStandupAction(
                widget.report.id,
                'revision',
                feedback: note,
              );
              setState(() => _isActionInProgress = false);

              if (mounted) {
                if (res.success) Navigator.of(context).pop();
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(res.success ? 'Revision requested!' : res.message),
                    backgroundColor: res.success ? AppColors.warning : AppColors.danger,
                  ),
                );
              }
            },
            child: const Text('Submit Revision Request', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final auth = Provider.of<AuthProvider>(context);
    final prov = Provider.of<DailyReportsProvider>(context, listen: false);
    final r = widget.report;

    final canReview = auth.canReviewStandups && !auth.isViewOnly;
    final canEditMine = !auth.isViewOnly &&
        r.userId == auth.userId &&
        (r.status == 'revision_requested' || r.status == 'draft' || r.status == 'submitted');
    final statusColor = _getStatusColor(r.status);

    DateTime? reportDt = DateTime.tryParse(r.reportDate);
    final dateFormatted = reportDt != null ? DateFormat('EEEE, dd MMMM yyyy').format(reportDt) : r.reportDate;

    return DraggableScrollableSheet(
      initialChildSize: 0.90,
      minChildSize: 0.5,
      maxChildSize: 0.96,
      builder: (ctx, scrollController) {
        return Container(
          decoration: BoxDecoration(
            color: isDark ? const Color(0xFF111827) : Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.3),
                blurRadius: 20,
                offset: const Offset(0, -4),
              ),
            ],
          ),
          child: Column(
            children: [
              // Sheet Drag Handle
              Container(
                margin: const EdgeInsets.only(top: 12, bottom: 8),
                width: 44,
                height: 5,
                decoration: BoxDecoration(
                  color: isDark ? Colors.white24 : Colors.black12,
                  borderRadius: BorderRadius.circular(3),
                ),
              ),

              // Title Bar
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Daily Standup Report Details',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded),
                      onPressed: () => Navigator.of(context).pop(),
                      visualDensity: VisualDensity.compact,
                    ),
                  ],
                ),
              ),

              const Divider(height: 1),

              // Scrollable Content
              Expanded(
                child: ListView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(20),
                  children: [
                    // Member Profile & Status Card
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: isDark ? AppColors.darkCard : AppColors.lightCard,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              CircleAvatar(
                                radius: 24,
                                backgroundColor: AppColors.primaryBlue.withOpacity(0.18),
                                backgroundImage: r.userAvatar.isNotEmpty ? NetworkImage(r.userAvatar) : null,
                                child: r.userAvatar.isEmpty
                                    ? Text(
                                        r.userName.isNotEmpty ? r.userName[0].toUpperCase() : 'S',
                                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18, color: AppColors.primaryBlueLight),
                                      )
                                    : null,
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      r.userName,
                                      style: TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w800,
                                        color: isDark ? AppColors.textLight : AppColors.textDark,
                                      ),
                                    ),
                                    const SizedBox(height: 3),
                                    Row(
                                      children: [
                                        if (r.team.isNotEmpty) ...[
                                          TeamBadge(team: r.team, isSmall: true),
                                          const SizedBox(width: 6),
                                        ],
                                        Text(
                                          r.callSign,
                                          style: TextStyle(
                                            color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                                            fontSize: 11.5,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                              // Status Badge
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                decoration: BoxDecoration(
                                  color: statusColor.withOpacity(0.12),
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: statusColor.withOpacity(0.3)),
                                ),
                                child: Text(
                                  r.statusLabel,
                                  style: TextStyle(
                                    color: statusColor,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 14),
                          const Divider(height: 1),
                          const SizedBox(height: 12),

                          // Metadata Pills
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              _buildMetaPill(
                                icon: Icons.calendar_month_rounded,
                                text: dateFormatted,
                                color: AppColors.primaryBlueLight,
                                isDark: isDark,
                              ),
                              if (r.submissionTime != null && r.submissionTime!.isNotEmpty)
                                _buildMetaPill(
                                  icon: Icons.schedule_rounded,
                                  text: 'Submitted ${r.submissionTime}',
                                  color: Colors.grey,
                                  isDark: isDark,
                                ),
                              if (r.isLate)
                                _buildMetaPill(
                                  icon: Icons.alarm_off_rounded,
                                  text: 'Late Submission (After 6:15 PM)',
                                  color: AppColors.warning,
                                  isDark: isDark,
                                )
                              else
                                _buildMetaPill(
                                  icon: Icons.check_circle_rounded,
                                  text: 'On-Time Submission',
                                  color: AppColors.success,
                                  isDark: isDark,
                                ),
                            ],
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Metrics Banner
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            AppColors.primaryBlue.withOpacity(0.15),
                            const Color(0xFF10B981).withOpacity(0.12),
                          ],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.primaryBlue.withOpacity(0.2)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          _buildMetricItem('Total Work Hours', '${r.totalHours.toStringAsFixed(1)}h', Icons.timer_rounded, AppColors.primaryBlueLight),
                          Container(width: 1, height: 32, color: Colors.grey.withOpacity(0.25)),
                          _buildMetricItem('Task Deliverables', '${r.items.length}', Icons.check_circle_outline_rounded, const Color(0xFF10B981)),
                          Container(width: 1, height: 32, color: Colors.grey.withOpacity(0.25)),
                          _buildMetricItem('Deliveries', '${r.deliveredProjects.length}', Icons.rocket_launch_rounded, const Color(0xFFF59E0B)),
                        ],
                      ),
                    ),

                    // Delivered Projects Section
                    if (r.deliveredProjects.isNotEmpty) ...[
                      const SizedBox(height: 24),
                      _buildSectionHeader('🚀 DELIVERED PROJECTS TODAY (${r.deliveredProjects.length})', isDark),
                      const SizedBox(height: 10),
                      ...r.deliveredProjects.map((dp) => _buildDeliveredCard(dp, isDark)),
                    ],

                    // Extended Projects Section
                    if (r.extendedProjects.isNotEmpty) ...[
                      const SizedBox(height: 20),
                      _buildSectionHeader('⏱ EXTENDED PROJECTS (${r.extendedProjects.length})', isDark),
                      const SizedBox(height: 10),
                      ...r.extendedProjects.map((ep) => _buildExtendedCard(ep, isDark)),
                    ],

                    // Overdue / Late Projects Section
                    if (r.lateProjects.isNotEmpty) ...[
                      const SizedBox(height: 20),
                      _buildSectionHeader('⚠️ OVERDUE PROJECTS (${r.lateProjects.length})', isDark),
                      const SizedBox(height: 10),
                      ...r.lateProjects.map((lp) => _buildLateCard(lp, isDark)),
                    ],

                    // Itemized Task Deliverables
                    const SizedBox(height: 24),
                    _buildSectionHeader('📋 ITEMISED DELIVERABLES & PROGRESS (${r.items.length})', isDark),
                    const SizedBox(height: 10),

                    if (r.items.isEmpty)
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: isDark ? AppColors.darkCard : AppColors.lightCard,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                        ),
                        child: Text(
                          r.reportType == 'on_leave' ? 'Specialist was on scheduled leave.' : 'No itemized tasks recorded.',
                          style: TextStyle(color: isDark ? AppColors.textDim : AppColors.textDarkMuted),
                        ),
                      )
                    else
                      ...r.items.map((item) => _buildTaskItemCard(item, isDark)),

                    // Overall Blockers Section
                    if (r.blockers.isNotEmpty) ...[
                      const SizedBox(height: 20),
                      _buildSectionHeader('⚠️ BLOCKERS & CHALLENGES', isDark),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: AppColors.danger.withOpacity(0.08),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.danger.withOpacity(0.3)),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Icon(Icons.warning_amber_rounded, color: AppColors.danger, size: 20),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                r.blockers,
                                style: TextStyle(
                                  color: isDark ? AppColors.textLight : AppColors.textDark,
                                  fontSize: 13,
                                  height: 1.4,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],

                    // Tomorrow's Plan Section
                    if (r.tomorrowPlan.isNotEmpty) ...[
                      const SizedBox(height: 20),
                      _buildSectionHeader('🎯 PLANNED FOR TOMORROW', isDark),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: AppColors.primaryBlue.withOpacity(0.08),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.primaryBlue.withOpacity(0.25)),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Icon(Icons.next_plan_rounded, color: AppColors.primaryBlueLight, size: 20),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                r.tomorrowPlan,
                                style: TextStyle(
                                  color: isDark ? AppColors.textLight : AppColors.textDark,
                                  fontSize: 13,
                                  height: 1.4,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],

                    // Leadership Review & Audit Section
                    const SizedBox(height: 24),
                    _buildSectionHeader('🛡️ LEADERSHIP AUDIT & REVIEW', isDark),
                    const SizedBox(height: 10),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: isDark ? AppColors.darkCard : AppColors.lightCard,
                        borderRadius: BorderRadius.circular(14),
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
                                  Icon(
                                    r.status == 'approved' ? Icons.verified_rounded : Icons.pending_actions_rounded,
                                    size: 18,
                                    color: statusColor,
                                  ),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Review Status: ${r.statusLabel}',
                                    style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: statusColor),
                                  ),
                                ],
                              ),
                              if (r.reviewedAt != null && r.reviewedAt!.isNotEmpty)
                                Text(
                                  r.reviewedAt!,
                                  style: TextStyle(fontSize: 11, color: isDark ? AppColors.textDim : AppColors.textDarkMuted),
                                ),
                            ],
                          ),
                          if (r.reviewerName != null && r.reviewerName!.isNotEmpty) ...[
                            const SizedBox(height: 6),
                            Text(
                              'Verified by: ${r.reviewerName}',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                              ),
                            ),
                          ],
                          if (r.leaderFeedback != null && r.leaderFeedback!.isNotEmpty) ...[
                            const SizedBox(height: 10),
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: AppColors.warning.withValues(alpha: 0.08),
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: AppColors.warning.withValues(alpha: 0.2)),
                              ),
                              child: Text(
                                'Leader Feedback: ${r.leaderFeedback}',
                                style: const TextStyle(fontSize: 12, color: AppColors.warning, height: 1.3),
                              ),
                            ),
                          ],
                          if (r.managerFeedback != null && r.managerFeedback!.isNotEmpty) ...[
                            const SizedBox(height: 10),
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: AppColors.primaryBlue.withValues(alpha: 0.08),
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: AppColors.primaryBlue.withValues(alpha: 0.25)),
                              ),
                              child: Text(
                                'Manager Feedback: ${r.managerFeedback}',
                                style: const TextStyle(fontSize: 12, color: AppColors.primaryBlueLight, height: 1.3),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),

                    const SizedBox(height: 30),
                  ],
                ),
              ),

              // Bottom Sticky Action Bar (For Leadership or Report Owner Resubmit)
              if (canReview || canEditMine) ...[
                const Divider(height: 1),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF1E293B) : Colors.grey[50],
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (canEditMine)
                        SizedBox(
                          width: double.infinity,
                          child: OutlinedButton.icon(
                            style: OutlinedButton.styleFrom(
                              foregroundColor: AppColors.primaryBlueLight,
                              side: const BorderSide(color: AppColors.primaryBlueLight),
                              padding: const EdgeInsets.symmetric(vertical: 11),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                            onPressed: () {
                              Navigator.of(context).pop();
                              Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (_) => LogStandupScreen(existingReport: r),
                                ),
                              );
                            },
                            icon: const Icon(Icons.edit_note_rounded, size: 18),
                            label: Text(
                              r.status == 'revision_requested'
                                  ? 'Revise & Resubmit Standup'
                                  : (r.status == 'draft' ? 'Continue Draft & Submit' : 'Edit Standup'),
                              style: const TextStyle(fontWeight: FontWeight.w700),
                            ),
                          ),
                        ),
                      if (canEditMine && canReview) const SizedBox(height: 8),
                      if (canReview) ...[
                        Row(
                          children: [
                            Expanded(
                              child: OutlinedButton.icon(
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: AppColors.warning,
                                  side: const BorderSide(color: AppColors.warning),
                                  padding: const EdgeInsets.symmetric(vertical: 11),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                ),
                                onPressed: _isActionInProgress ? null : () => _showRevisionDialog(prov),
                                icon: const Icon(Icons.replay_rounded, size: 18),
                                label: const Text('Revision', style: TextStyle(fontWeight: FontWeight.w700)),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              flex: 2,
                              child: ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: AppColors.success,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(vertical: 11),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  elevation: 0,
                                ),
                                onPressed: _isActionInProgress ? null : () => _handleApprove(prov, auth),
                                icon: _isActionInProgress
                                    ? const SizedBox(
                                        width: 16,
                                        height: 16,
                                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                      )
                                    : const Icon(Icons.check_rounded, size: 18),
                                label: Text(
                                  _isActionInProgress
                                      ? 'Saving...'
                                      : (auth.isOperationsManager ? 'Final Approve' : 'Verify Standup'),
                                  style: const TextStyle(fontWeight: FontWeight.w800),
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            Expanded(
                              child: OutlinedButton.icon(
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: AppColors.primaryBlueLight,
                                  side: BorderSide(color: AppColors.primaryBlueLight.withOpacity(0.5)),
                                  padding: const EdgeInsets.symmetric(vertical: 9),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                ),
                                onPressed: _isActionInProgress ? null : () => _showCommentDialog(prov),
                                icon: const Icon(Icons.add_comment_rounded, size: 16),
                                label: const Text('Manager Note', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                              ),
                            ),
                            if (r.status != 'submitted' && r.status != 'draft') ...[
                              const SizedBox(width: 10),
                              Expanded(
                                child: OutlinedButton.icon(
                                  style: OutlinedButton.styleFrom(
                                    foregroundColor: AppColors.danger,
                                    side: BorderSide(color: AppColors.danger.withOpacity(0.5)),
                                    padding: const EdgeInsets.symmetric(vertical: 9),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  ),
                                  onPressed: _isActionInProgress ? null : () => _handleReset(prov),
                                  icon: const Icon(Icons.restart_alt_rounded, size: 16),
                                  label: const Text('Reset Status', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
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
        fontSize: 11,
        fontWeight: FontWeight.w800,
        color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
        letterSpacing: 0.8,
      ),
    );
  }

  Widget _buildMetaPill({required IconData icon, required String text, required Color color, required bool isDark}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.10),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withOpacity(0.25)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: color),
          const SizedBox(width: 6),
          Text(
            text,
            style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: color),
          ),
        ],
      ),
    );
  }

  Widget _buildMetricItem(String label, String value, IconData icon, Color color) {
    return Column(
      children: [
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 14, color: color),
            const SizedBox(width: 4),
            Text(value, style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: color)),
          ],
        ),
        const SizedBox(height: 2),
        Text(label, style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: Colors.grey)),
      ],
    );
  }

  Widget _buildDeliveredCard(DeliveredProjectItem dp, bool isDark) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFF10B981).withOpacity(0.35)),
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
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: const Color(0xFF10B981).withOpacity(0.15),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      dp.orderId,
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12, color: Color(0xFF10B981)),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    dp.clientName,
                    style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: isDark ? AppColors.textLight : AppColors.textDark),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: AppColors.success.withOpacity(0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  '+ \$${dp.netValue.toStringAsFixed(2)}',
                  style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12, color: AppColors.success),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              if (dp.starRating > 0) ...[
                const Icon(Icons.star_rounded, size: 14, color: Color(0xFFF59E0B)),
                const SizedBox(width: 3),
                Text(
                  dp.starRating.toStringAsFixed(1),
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFFF59E0B)),
                ),
                const SizedBox(width: 10),
              ],
              Text(
                dp.onTimeLabel,
                style: TextStyle(
                  fontSize: 11.5,
                  fontWeight: FontWeight.w600,
                  color: dp.deliveredWithoutExtension ? AppColors.success : AppColors.warning,
                ),
              ),
            ],
          ),
          if (dp.extensionReason.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              'Extension Reason: ${dp.extensionReason}',
              style: TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: isDark ? AppColors.textDim : AppColors.textDarkMuted),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildExtendedCard(ExtendedProjectItem ep, bool isDark) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.warning.withOpacity(0.06),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.warning.withOpacity(0.25)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                '${ep.orderId} • ${ep.clientName}',
                style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5),
              ),
              Text(
                '${ep.extensionCount}x Extended (+${ep.extensionDays}d)',
                style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 11, color: AppColors.warning),
              ),
            ],
          ),
          if (ep.extensionReason.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              'Reason: ${ep.extensionReason}',
              style: TextStyle(fontSize: 11, color: isDark ? AppColors.textDim : AppColors.textDarkMuted),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildLateCard(LateProjectItem lp, bool isDark) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.danger.withOpacity(0.08),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.danger.withOpacity(0.25)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            '${lp.orderId} • ${lp.clientName}',
            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5, color: AppColors.danger),
          ),
          Text(
            '${lp.daysLate}d Overdue',
            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 11.5, color: AppColors.danger),
          ),
        ],
      ),
    );
  }

  Widget _buildTaskItemCard(DailyReportTaskItem item, bool isDark) {
    final taskText = item.tasksCompleted.isNotEmpty ? item.tasksCompleted : item.taskDescription;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkCard : AppColors.lightCard,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header: Order ID & Hours
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  if (item.orderId.isNotEmpty) ...[
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: AppColors.primaryBlue.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        item.orderId,
                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 11.5, color: AppColors.primaryBlueLight),
                      ),
                    ),
                    const SizedBox(width: 8),
                  ],
                  if (item.clientName.isNotEmpty)
                    Text(
                      item.clientName,
                      style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: isDark ? AppColors.textLight : AppColors.textDark),
                    ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: AppColors.success.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  '${item.hoursSpent.toStringAsFixed(1)}h Logged',
                  style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 11.5, color: AppColors.success),
                ),
              ),
            ],
          ),

          const SizedBox(height: 10),

          // Progress Bar
          Row(
            children: [
              Expanded(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: (item.progressPercent / 100).clamp(0.0, 1.0),
                    backgroundColor: isDark ? Colors.white10 : Colors.black12,
                    valueColor: AlwaysStoppedAnimation<Color>(
                      item.progressPercent >= 100 ? AppColors.success : AppColors.primaryBlueLight,
                    ),
                    minHeight: 6,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Text(
                '${item.progressPercent}% Complete',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                ),
              ),
            ],
          ),

          const SizedBox(height: 12),

          // Completed Tasks Text
          if (taskText.isNotEmpty) ...[
            Text(
              'Tasks Completed:',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              taskText,
              style: TextStyle(
                fontSize: 13,
                height: 1.4,
                color: isDark ? AppColors.textLight : AppColors.textDark,
              ),
            ),
          ],

          // Work in Progress Text (if present)
          if (item.workInProgress.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              'Work in Progress:',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
              ),
            ),
            const SizedBox(height: 3),
            Text(
              item.workInProgress,
              style: TextStyle(
                fontSize: 12.5,
                height: 1.35,
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
              ),
            ),
          ],

          // Next Day Plan (if present)
          if (item.nextDayPlan.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              'Next Day Plan:',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
              ),
            ),
            const SizedBox(height: 3),
            Text(
              item.nextDayPlan,
              style: TextStyle(
                fontSize: 12.5,
                height: 1.35,
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
              ),
            ),
          ],

          // Blocker Description (if present)
          if (item.hasBlocker && item.blockerDescription.isNotEmpty) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: AppColors.danger.withOpacity(0.08),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: AppColors.danger.withOpacity(0.25)),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.error_outline_rounded, size: 16, color: AppColors.danger),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      item.blockerDescription,
                      style: const TextStyle(fontSize: 11.5, color: AppColors.danger, height: 1.35),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

