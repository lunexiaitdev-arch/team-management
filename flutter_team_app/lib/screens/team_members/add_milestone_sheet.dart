import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/team_members_provider.dart';
import '../../theme/app_colors.dart';

class AddMilestoneSheet extends StatefulWidget {
  final int userId;
  final VoidCallback? onAdded;

  const AddMilestoneSheet({super.key, required this.userId, this.onAdded});

  @override
  State<AddMilestoneSheet> createState() => _AddMilestoneSheetState();
}

class _AddMilestoneSheetState extends State<AddMilestoneSheet> {
  final _titleController = TextEditingController();
  final _descController = TextEditingController();
  DateTime _milestoneDate = DateTime.now();
  bool _isSubmitting = false;

  @override
  void dispose() {
    _titleController.dispose();
    _descController.dispose();
    super.dispose();
  }

  Future<void> _handleSubmit() async {
    final title = _titleController.text.trim();
    if (title.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter a milestone title.')),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final prov = Provider.of<TeamMembersProvider>(context, listen: false);
    final success = await prov.addMilestone(
      widget.userId,
      title,
      DateFormat('dd MMM yyyy').format(_milestoneDate),
      _descController.text.trim(),
    );
    setState(() => _isSubmitting = false);

    if (mounted) {
      if (success) {
        Navigator.of(context).pop();
        widget.onAdded?.call();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Milestone added successfully!'), backgroundColor: AppColors.success),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to add milestone'), backgroundColor: AppColors.danger),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        padding: const EdgeInsets.all(22),
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
              'Add Career Milestone',
              style: TextStyle(
                color: isDark ? AppColors.textLight : AppColors.textDark,
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 16),

            // Milestone Title
            TextField(
              controller: _titleController,
              style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
              decoration: const InputDecoration(hintText: 'e.g. Promoted to Senior Specialist / 100+ Orders Award'),
            ),
            const SizedBox(height: 12),

            // Date Picker Box
            InkWell(
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: _milestoneDate,
                  firstDate: DateTime(2020),
                  lastDate: DateTime.now().add(const Duration(days: 365)),
                );
                if (picked != null) {
                  setState(() => _milestoneDate = picked);
                }
              },
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                decoration: BoxDecoration(
                  color: isDark ? AppColors.darkCard : AppColors.lightCard,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      DateFormat('dd MMMM yyyy').format(_milestoneDate),
                      style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13, fontWeight: FontWeight.w600),
                    ),
                    const Icon(Icons.calendar_today_rounded, size: 16, color: AppColors.primaryBlueLight),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),

            // Description
            TextField(
              controller: _descController,
              maxLines: 2,
              style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
              decoration: const InputDecoration(hintText: 'Description or notes for this recognition...'),
            ),
            const SizedBox(height: 20),

            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: _isSubmitting ? null : _handleSubmit,
                child: _isSubmitting
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('Save Milestone', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
              ),
            ),
            const SizedBox(height: 10),
          ],
        ),
      ),
    );
  }
}
