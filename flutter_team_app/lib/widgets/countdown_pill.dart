import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

class CountdownPill extends StatelessWidget {
  final String deliveryDateStr;
  final String? badgeText;
  final String? urgency;
  final String? status;
  final bool? isCompleted;
  final bool? isSubmitted;
  final bool? isOntime;
  final int extensionCount;
  final int extensionDays;

  const CountdownPill({
    super.key,
    required this.deliveryDateStr,
    this.badgeText,
    this.urgency,
    this.status,
    this.isCompleted,
    this.isSubmitted,
    this.isOntime,
    this.extensionCount = 0,
    this.extensionDays = 0,
  });

  @override
  Widget build(BuildContext context) {
    if (deliveryDateStr.isEmpty && (badgeText == null || badgeText!.isEmpty)) {
      return const SizedBox.shrink();
    }

    final lowerStatus = (status ?? '').toLowerCase();
    final completed = isCompleted == true ||
        lowerStatus == 'delivery_accepted' ||
        lowerStatus == 'completed' ||
        lowerStatus == 'delivered';
    final submitted = isSubmitted == true || lowerStatus == 'delivery_submitted';
    final cancelled = lowerStatus == 'cancelled';

    Color color;
    String label;
    IconData icon;

    if (cancelled) {
      label = 'Cancelled';
      color = AppColors.danger;
      icon = Icons.cancel_outlined;
    } else if (completed) {
      final onTime = isOntime ?? (extensionCount == 0 && extensionDays == 0);
      if (onTime) {
        label = 'On-Time';
        color = AppColors.success;
        icon = Icons.check_circle_outline_rounded;
      } else {
        label = extensionDays > 0 ? 'Delivered (+${extensionDays}d)' : 'Delivered (Ext)';
        color = AppColors.warning;
        icon = Icons.check_circle_outline_rounded;
      }
    } else if (submitted) {
      label = extensionCount > 0 ? 'Submitted (${extensionCount} Ext)' : 'Submitted';
      color = AppColors.info;
      icon = Icons.task_alt_rounded;
    } else if (badgeText != null && badgeText!.trim().isNotEmpty) {
      label = badgeText!.trim();
      final urg = (urgency ?? '').toLowerCase();
      final lowerBadge = label.toLowerCase();
      if (urg == 'danger' || lowerBadge.contains('late') || lowerBadge.contains('overdue')) {
        color = AppColors.danger;
        icon = Icons.error_outline_rounded;
      } else if (urg == 'warning' ||
          lowerBadge.contains('today') ||
          lowerBadge.contains('tomorrow') ||
          lowerBadge == '1d left' ||
          lowerBadge == '2d left') {
        color = AppColors.warning;
        icon = Icons.timer_outlined;
      } else {
        color = AppColors.success;
        icon = Icons.schedule_rounded;
      }
    } else {
      // Calendar-day comparison in Bangladesh Standard Time (UTC+6)
      final datePart = deliveryDateStr.length >= 10 ? deliveryDateStr.substring(0, 10) : deliveryDateStr;
      final parts = datePart.split('-');
      if (parts.length != 3) {
        return _pill(deliveryDateStr, AppColors.info, Icons.calendar_today_rounded);
      }
      final y = int.tryParse(parts[0]);
      final m = int.tryParse(parts[1]);
      final d = int.tryParse(parts[2]);
      if (y == null || m == null || d == null) {
        return _pill(deliveryDateStr, AppColors.info, Icons.calendar_today_rounded);
      }

      final nowBst = DateTime.now().toUtc().add(const Duration(hours: 6));
      final todayBst = DateTime.utc(nowBst.year, nowBst.month, nowBst.day);
      final dueBst = DateTime.utc(y, m, d);
      final diffDays = dueBst.difference(todayBst).inDays;

      if (diffDays < 0) {
        label = '${diffDays.abs()}d late';
        color = AppColors.danger;
        icon = Icons.error_outline_rounded;
      } else if (diffDays == 0) {
        label = 'Due Today';
        color = AppColors.warning;
        icon = Icons.timer_outlined;
      } else if (diffDays == 1) {
        label = 'Tomorrow';
        color = AppColors.warning;
        icon = Icons.schedule_rounded;
      } else if (diffDays <= 2) {
        label = '${diffDays}d left';
        color = AppColors.warning;
        icon = Icons.schedule_rounded;
      } else {
        label = '${diffDays}d left';
        color = AppColors.success;
        icon = Icons.schedule_rounded;
      }
    }

    final mainPill = _pill(label, color, icon);
    if (!completed && !submitted && !cancelled && extensionCount > 0) {
      final extText = extensionDays > 0 ? '+${extensionDays}d (${extensionCount}x)' : '${extensionCount}x Ext';
      return Wrap(
        spacing: 5,
        runSpacing: 4,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          mainPill,
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
            decoration: BoxDecoration(
              color: AppColors.warning.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(7),
              border: Border.all(color: AppColors.warning.withValues(alpha: 0.32), width: 1),
            ),
            child: Text(
              extText,
              style: const TextStyle(
                color: AppColors.warning,
                fontSize: 10.5,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      );
    }

    return mainPill;
  }

  Widget _pill(String label, Color color, IconData icon) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withValues(alpha: 0.25), width: 1),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: color),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
              color: color,
              fontSize: 11,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}
