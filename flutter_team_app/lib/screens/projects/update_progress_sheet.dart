import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/project.dart';
import '../../providers/projects_provider.dart';
import '../../theme/app_colors.dart';

class UpdateProgressSheet extends StatefulWidget {
  final ProjectItem project;

  const UpdateProgressSheet({super.key, required this.project});

  @override
  State<UpdateProgressSheet> createState() => _UpdateProgressSheetState();
}

class _UpdateProgressSheetState extends State<UpdateProgressSheet> {
  late double _progressValue;
  final _noteController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _progressValue = widget.project.progressPercent.toDouble();
    if (widget.project.latestProgress.isNotEmpty) {
      _noteController.text = widget.project.latestProgress;
    }
  }

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _handleSubmit() async {
    final note = _noteController.text.trim();
    final progress = _progressValue.round();

    setState(() => _isSubmitting = true);
    final prov = Provider.of<ProjectsProvider>(context, listen: false);
    final success = await prov.updateProgress(widget.project.id, progress, note);
    setState(() => _isSubmitting = false);

    if (mounted) {
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            success ? 'Progress updated to $progress%' : 'Failed to update progress',
          ),
          backgroundColor: success ? AppColors.success : AppColors.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
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

            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Update Progress',
                      style: TextStyle(
                        color: isDark ? AppColors.textLight : AppColors.textDark,
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    Text(
                      widget.project.orderId,
                      style: const TextStyle(
                        color: AppColors.primaryBlueLight,
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                  decoration: BoxDecoration(
                    color: AppColors.primaryBlue.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: AppColors.primaryBlue.withOpacity(0.3)),
                  ),
                  child: Text(
                    '${_progressValue.round()}%',
                    style: const TextStyle(
                      color: AppColors.primaryBlueLight,
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Slider
            SliderTheme(
              data: SliderTheme.of(context).copyWith(
                activeTrackColor: AppColors.primaryBlue,
                inactiveTrackColor: isDark ? AppColors.darkBorder : AppColors.lightBorder,
                thumbColor: AppColors.primaryBlueLight,
                overlayColor: AppColors.primaryBlue.withOpacity(0.2),
                trackHeight: 6,
              ),
              child: Slider(
                value: _progressValue,
                min: 0,
                max: 100,
                divisions: 20,
                onChanged: (val) {
                  setState(() {
                    _progressValue = val;
                  });
                },
              ),
            ),

            // Quick Preset Buttons
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [25, 50, 75, 100].map((pct) {
                final isSelected = _progressValue.round() == pct;
                return TextButton(
                  onPressed: () => setState(() => _progressValue = pct.toDouble()),
                  style: TextButton.styleFrom(
                    backgroundColor: isSelected
                        ? AppColors.primaryBlue.withOpacity(0.15)
                        : Colors.transparent,
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  ),
                  child: Text(
                    '$pct%',
                    style: TextStyle(
                      color: isSelected ? AppColors.primaryBlueLight : AppColors.textDim,
                      fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                      fontSize: 12,
                    ),
                  ),
                );
              }).toList(),
            ),
            const SizedBox(height: 16),

            // Progress Notes
            Text(
              'PROGRESS NOTE / MILESTONE SUMMARY',
              style: TextStyle(
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                fontSize: 11,
                fontWeight: FontWeight.w700,
                letterSpacing: 0.5,
              ),
            ),
            const SizedBox(height: 6),
            TextField(
              controller: _noteController,
              maxLines: 3,
              style: TextStyle(
                color: isDark ? AppColors.textLight : AppColors.textDark,
                fontSize: 13,
              ),
              decoration: const InputDecoration(
                hintText: 'What did you complete on this order today? (e.g. Header completed, mobile responsiveness tuned)',
              ),
            ),
            const SizedBox(height: 20),

            // Submit Button
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: _isSubmitting ? null : _handleSubmit,
                child: _isSubmitting
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      )
                    : const Text(
                        'Save Progress',
                        style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
                      ),
              ),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}
