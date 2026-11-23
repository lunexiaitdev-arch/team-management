import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/notices_provider.dart';
import '../../theme/app_colors.dart';

class CreateNoticeSheet extends StatefulWidget {
  const CreateNoticeSheet({super.key});

  @override
  State<CreateNoticeSheet> createState() => _CreateNoticeSheetState();
}

class _CreateNoticeSheetState extends State<CreateNoticeSheet> {
  final _titleController = TextEditingController();
  final _contentController = TextEditingController();
  String _selectedPriority = 'normal';
  bool _isPinned = false;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _titleController.dispose();
    _contentController.dispose();
    super.dispose();
  }

  Future<void> _handleSubmit() async {
    final title = _titleController.text.trim();
    final content = _contentController.text.trim();

    if (title.isEmpty || content.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter both title and content.')),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final prov = Provider.of<NoticesProvider>(context, listen: false);
    final success = await prov.createNotice(title, content, _selectedPriority, _isPinned);
    setState(() => _isSubmitting = false);

    if (mounted) {
      if (success) {
        Navigator.of(context).pop();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Notice broadcasted to all specialists!'), backgroundColor: AppColors.success),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to post notice'), backgroundColor: AppColors.danger),
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
              'Broadcast Agency Notice',
              style: TextStyle(
                color: isDark ? AppColors.textLight : AppColors.textDark,
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 16),

            // Notice Title
            TextField(
              controller: _titleController,
              style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
              decoration: const InputDecoration(hintText: 'Notice Headline (e.g. Server Maintenance or Monthly Targets)'),
            ),
            const SizedBox(height: 12),

            // Content
            TextField(
              controller: _contentController,
              maxLines: 4,
              style: TextStyle(color: isDark ? AppColors.textLight : AppColors.textDark, fontSize: 13),
              decoration: const InputDecoration(hintText: 'Full announcement details for specialists and leaders...'),
            ),
            const SizedBox(height: 14),

            // Priority Selector
            Row(
              children: [
                Text(
                  'PRIORITY:',
                  style: TextStyle(
                    color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(width: 12),
                Wrap(
                  spacing: 6,
                  children: ['normal', 'high', 'urgent'].map((p) {
                    final isSelected = _selectedPriority == p;
                    return ChoiceChip(
                      label: Text(p.toUpperCase()),
                      selected: isSelected,
                      labelStyle: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        color: isSelected ? Colors.white : (isDark ? AppColors.textDim : AppColors.textDarkMuted),
                      ),
                      onSelected: (_) => setState(() => _selectedPriority = p),
                    );
                  }).toList(),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Pin notice
            Row(
              children: [
                SizedBox(
                  width: 24,
                  height: 24,
                  child: Checkbox(
                    value: _isPinned,
                    activeColor: AppColors.primaryBlue,
                    onChanged: (val) => setState(() => _isPinned = val ?? false),
                  ),
                ),
                const SizedBox(width: 8),
                Text(
                  'Pin announcement to top of dashboard',
                  style: TextStyle(
                    color: isDark ? AppColors.textLight : AppColors.textDark,
                    fontSize: 13,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Submit Button
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: _isSubmitting ? null : _handleSubmit,
                child: _isSubmitting
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('Post Broadcast', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
              ),
            ),
            const SizedBox(height: 10),
          ],
        ),
      ),
    );
  }
}
