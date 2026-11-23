import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../models/project.dart';
import '../../providers/projects_provider.dart';
import '../../theme/app_colors.dart';

class ExtendDeliverySheet extends StatefulWidget {
  final ProjectItem project;

  const ExtendDeliverySheet({super.key, required this.project});

  @override
  State<ExtendDeliverySheet> createState() => _ExtendDeliverySheetState();
}

class _ExtendDeliverySheetState extends State<ExtendDeliverySheet> {
  DateTime _selectedDate = DateTime.now().add(const Duration(days: 2));
  final _reasonController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    try {
      final parsed = DateTime.tryParse(widget.project.deliveryDate);
      if (parsed != null) {
        _selectedDate = parsed.add(const Duration(days: 2));
      }
    } catch (_) {}
  }

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 180)),
    );
    if (picked != null) {
      setState(() {
        _selectedDate = picked;
      });
    }
  }

  Future<void> _handleSubmit() async {
    final reason = _reasonController.text.trim();
    final formattedDate = DateFormat('yyyy-MM-dd HH:mm:ss').format(_selectedDate);

    setState(() => _isSubmitting = true);
    final prov = Provider.of<ProjectsProvider>(context, listen: false);
    final success = await prov.extendDelivery(widget.project.id, formattedDate, reason);
    setState(() => _isSubmitting = false);

    if (mounted) {
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            success ? 'Delivery extended to ${DateFormat('dd MMM yyyy').format(_selectedDate)}' : 'Failed to extend delivery',
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

            Text(
              'Extend Delivery Deadline',
              style: TextStyle(
                color: isDark ? AppColors.textLight : AppColors.textDark,
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
            Text(
              '${widget.project.orderId} • Current: ${widget.project.deliveryDate}',
              style: const TextStyle(
                color: AppColors.textDim,
                fontSize: 12,
              ),
            ),
            const SizedBox(height: 20),

            // Date Picker Box
            Text(
              'NEW DELIVERY DEADLINE',
              style: TextStyle(
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                fontSize: 11,
                fontWeight: FontWeight.w700,
                letterSpacing: 0.5,
              ),
            ),
            const SizedBox(height: 6),
            InkWell(
              onTap: _pickDate,
              borderRadius: BorderRadius.circular(12),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                decoration: BoxDecoration(
                  color: isDark ? AppColors.darkCard : AppColors.lightCard,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: isDark ? AppColors.darkBorder : AppColors.lightBorder),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.calendar_today_rounded, size: 18, color: AppColors.primaryBlueLight),
                        const SizedBox(width: 12),
                        Text(
                          DateFormat('EEEE, dd MMM yyyy').format(_selectedDate),
                          style: TextStyle(
                            color: isDark ? AppColors.textLight : AppColors.textDark,
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                    const Icon(Icons.edit_rounded, size: 16, color: AppColors.textDim),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Reason Input
            Text(
              'REASON FOR EXTENSION',
              style: TextStyle(
                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                fontSize: 11,
                fontWeight: FontWeight.w700,
                letterSpacing: 0.5,
              ),
            ),
            const SizedBox(height: 6),
            TextField(
              controller: _reasonController,
              maxLines: 2,
              style: TextStyle(
                color: isDark ? AppColors.textLight : AppColors.textDark,
                fontSize: 13,
              ),
              decoration: const InputDecoration(
                hintText: 'e.g. Buyer requested additional customization / revision cycle',
              ),
            ),
            const SizedBox(height: 22),

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
                        'Confirm Extension',
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
