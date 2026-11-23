import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../models/project.dart';
import '../../providers/projects_provider.dart';
import '../../theme/app_colors.dart';

class EditProjectSheet extends StatefulWidget {
  final ProjectItem project;

  const EditProjectSheet({super.key, required this.project});

  @override
  State<EditProjectSheet> createState() => _EditProjectSheetState();
}

class _EditProjectSheetState extends State<EditProjectSheet> {
  final _formKey = GlobalKey<FormState>();

  late TextEditingController _orderIdController;
  late TextEditingController _titleController;
  late TextEditingController _buyerController;
  late TextEditingController _profileController;
  late TextEditingController _orderValueController;
  late TextEditingController _sheetLinkController;
  late TextEditingController _orderLinkController;
  late TextEditingController _extensionCountController;
  late TextEditingController _extensionDaysController;
  late TextEditingController _extensionReasonController;
  late TextEditingController _deliveryDateController;
  late TextEditingController _deliveredDateController;

  late String _selectedStatus;
  late String _selectedTeam;
  late String _selectedOrderSource;
  bool _isSaving = false;

  int get _currentExtensionCount =>
      (int.tryParse(_extensionCountController.text.trim()) ?? 0).clamp(0, 9999);

  bool get _autoDeliveredOnTime =>
      _currentExtensionCount == 0 &&
      (_selectedStatus == 'delivery_accepted' || _selectedStatus == 'completed');

  final List<Map<String, String>> _statusOptions = [
    {'key': 'nra', 'label': 'Not Started (NRA)'},
    {'key': 'wip', 'label': 'In Progress (WIP)'},
    {'key': 'delivery_submitted', 'label': 'Delivery Submitted'},
    {'key': 'revision', 'label': 'In Revision'},
    {'key': 'delivery_accepted', 'label': 'Delivery Accepted'},
    {'key': 'completed', 'label': 'Completed'},
    {'key': 'cancelled', 'label': 'Cancelled'},
  ];

  final List<String> _teamOptions = ['WpTitans', 'WpNexus', 'WpCraft', 'WpHorizon'];
  final List<String> _orderSourceOptions = ['Query', 'Repeat Client', 'Custom Offer', 'Direct Message', 'Inbound'];

  @override
  void initState() {
    super.initState();
    final p = widget.project;
    _orderIdController = TextEditingController(text: p.orderId);
    _titleController = TextEditingController(text: p.title);
    _buyerController = TextEditingController(text: p.buyerUsername);
    _profileController = TextEditingController(text: p.profileName);
    _orderValueController = TextEditingController(text: p.orderValue.toStringAsFixed(0));
    _sheetLinkController = TextEditingController(text: p.projectSheetLink);
    _orderLinkController = TextEditingController(text: p.orderLink);
    _extensionCountController = TextEditingController(text: p.extensionCount.toString());
    _extensionDaysController = TextEditingController(text: p.extensionDays.toString());
    _extensionReasonController = TextEditingController(text: p.extensionReason);
    _deliveryDateController = TextEditingController(text: p.deliveryDate);
    _deliveredDateController = TextEditingController(text: p.deliveredDate);

    _selectedStatus = p.status.isNotEmpty ? p.status.toLowerCase() : 'wip';
    _selectedTeam = p.team.isNotEmpty ? p.team : 'WpCraft';
    _selectedOrderSource = _orderSourceOptions.contains(p.orderSource) ? p.orderSource : 'Query';
  }

  @override
  void dispose() {
    _orderIdController.dispose();
    _titleController.dispose();
    _buyerController.dispose();
    _profileController.dispose();
    _orderValueController.dispose();
    _sheetLinkController.dispose();
    _orderLinkController.dispose();
    _extensionCountController.dispose();
    _extensionDaysController.dispose();
    _extensionReasonController.dispose();
    _deliveryDateController.dispose();
    _deliveredDateController.dispose();
    super.dispose();
  }

  void _resetExtensionFields() {
    setState(() {
      _extensionCountController.text = '0';
      _extensionDaysController.text = '0';
      _extensionReasonController.text = '';
    });
    final bool isCompletedStatus =
        _selectedStatus == 'delivery_accepted' || _selectedStatus == 'completed';
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          isCompletedStatus
              ? 'Extension reset to 0 days (On-Time Delivery restored)'
              : 'Extension reset to 0 (will qualify as On-Time upon completion)',
        ),
        backgroundColor: AppColors.success,
        duration: const Duration(seconds: 2),
      ),
    );
  }

  Future<void> _pickDeliveryDate(BuildContext context) async {
    DateTime initial = DateTime.tryParse(_deliveryDateController.text) ?? DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2026, 10, 1),
      lastDate: DateTime(2030, 12, 31),
    );
    if (picked != null) {
      setState(() {
        _deliveryDateController.text = DateFormat('yyyy-MM-dd').format(picked);
      });
    }
  }

  Future<void> _pickDeliveredDate(BuildContext context) async {
    DateTime initial = DateTime.tryParse(_deliveredDateController.text) ?? DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2026, 10, 1),
      lastDate: DateTime(2030, 12, 31),
    );
    if (picked != null) {
      setState(() {
        _deliveredDateController.text = DateFormat('yyyy-MM-dd').format(picked);
      });
    }
  }

  Future<void> _handleSave() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);
    final prov = Provider.of<ProjectsProvider>(context, listen: false);

    final payload = <String, dynamic>{
      'order_id': _orderIdController.text.trim(),
      'title': _titleController.text.trim(),
      'buyer_username': _buyerController.text.trim(),
      'client_name': _buyerController.text.trim(),
      'profile_name': _profileController.text.trim(),
      'team_name': _selectedTeam,
      'gross_order_value': double.tryParse(_orderValueController.text.trim()) ?? widget.project.orderValue,
      'status': _selectedStatus,
      'delivery_date': _deliveryDateController.text.trim(),
      if (_deliveredDateController.text.trim().isNotEmpty)
        'delivered_date': _deliveredDateController.text.trim(),
      'project_sheet_link': _sheetLinkController.text.trim(),
      'order_link': _orderLinkController.text.trim(),
      'order_source': _selectedOrderSource,
      'extension_count': _currentExtensionCount,
      'extension_days': int.tryParse(_extensionDaysController.text.trim()) ?? 0,
      'extension_reason': _extensionReasonController.text.trim(),
      'delivered_without_extension': _autoDeliveredOnTime ? 1 : 0,
    };

    final success = await prov.updateProject(widget.project.id, payload);

    if (mounted) {
      setState(() => _isSaving = false);
      if (success) {
        Navigator.of(context).pop(true);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Order ${_orderIdController.text} updated successfully!'),
            backgroundColor: AppColors.success,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(prov.errorMessage ?? 'Failed to update order. Check permissions.'),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return DraggableScrollableSheet(
      initialChildSize: 0.92,
      minChildSize: 0.6,
      maxChildSize: 0.98,
      expand: false,
      builder: (context, scrollController) {
        return Container(
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkSurface : AppColors.lightSurface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              // Grabber
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
                        Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: AppColors.primaryBlue.withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Icon(Icons.edit_note_rounded, color: AppColors.primaryBlueLight, size: 20),
                        ),
                        const SizedBox(width: 10),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Edit Order ${widget.project.orderId}',
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                            ),
                            Text(
                              'Management & Leadership Control',
                              style: TextStyle(
                                fontSize: 11,
                                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded),
                      onPressed: () => Navigator.of(context).pop(),
                    ),
                  ],
                ),
              ),

              const Divider(height: 1),

              // Form Body
              Expanded(
                child: Form(
                  key: _formKey,
                  child: ListView(
                    controller: scrollController,
                    padding: const EdgeInsets.all(20),
                    children: [
                      // Order ID & Buyer Row
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _orderIdController,
                              decoration: const InputDecoration(labelText: 'Order ID *'),
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextFormField(
                              controller: _buyerController,
                              decoration: const InputDecoration(labelText: 'Buyer Username *'),
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),

                      // Project Title
                      TextFormField(
                        controller: _titleController,
                        decoration: const InputDecoration(labelText: 'Project Title *'),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                      ),
                      const SizedBox(height: 14),

                      // Profile & Squad
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _profileController,
                              decoration: const InputDecoration(labelText: 'Fiverr Profile *'),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: DropdownButtonFormField<String>(
                              value: _selectedTeam,
                              decoration: const InputDecoration(labelText: 'Assigned Squad *'),
                              items: _teamOptions.map((t) => DropdownMenuItem(value: t, child: Text(t))).toList(),
                              onChanged: (val) {
                                if (val != null) setState(() => _selectedTeam = val);
                              },
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),

                      // Order Value & Status
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _orderValueController,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(
                                labelText: 'Order Gross Value (\$) *',
                                prefixText: '\$ ',
                              ),
                              validator: (v) {
                                if (v == null || v.trim().isEmpty) return 'Required';
                                if (double.tryParse(v.trim()) == null) return 'Invalid amount';
                                return null;
                              },
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: DropdownButtonFormField<String>(
                              value: _selectedStatus,
                              decoration: const InputDecoration(labelText: 'Status *'),
                              items: _statusOptions
                                  .map((s) => DropdownMenuItem(value: s['key']!, child: Text(s['label']!, style: const TextStyle(fontSize: 13))))
                                  .toList(),
                              onChanged: (val) {
                                if (val != null) setState(() => _selectedStatus = val);
                              },
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),

                      // Delivery Date & Delivered Date
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _deliveryDateController,
                              readOnly: true,
                              onTap: () => _pickDeliveryDate(context),
                              decoration: const InputDecoration(
                                labelText: 'Deadline Date *',
                                suffixIcon: Icon(Icons.calendar_month_rounded, size: 18),
                              ),
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextFormField(
                              controller: _deliveredDateController,
                              readOnly: true,
                              onTap: () => _pickDeliveredDate(context),
                              decoration: InputDecoration(
                                labelText: 'Delivered Date',
                                suffixIcon: _deliveredDateController.text.isNotEmpty
                                    ? IconButton(
                                        icon: const Icon(Icons.clear_rounded, size: 16),
                                        onPressed: () => setState(() => _deliveredDateController.clear()),
                                      )
                                    : const Icon(Icons.calendar_month_rounded, size: 18),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),

                      // Order Source & Sheet Link
                      DropdownButtonFormField<String>(
                        value: _selectedOrderSource,
                        decoration: const InputDecoration(labelText: 'Order Source'),
                        items: _orderSourceOptions.map((s) => DropdownMenuItem(value: s, child: Text(s))).toList(),
                        onChanged: (val) {
                          if (val != null) setState(() => _selectedOrderSource = val);
                        },
                      ),
                      const SizedBox(height: 14),

                      TextFormField(
                        controller: _sheetLinkController,
                        decoration: const InputDecoration(
                          labelText: 'Google Sheet / Project Brief URL',
                          hintText: 'https://docs.google.com/spreadsheets/...',
                          prefixIcon: Icon(Icons.table_chart_rounded, size: 18),
                        ),
                      ),
                      const SizedBox(height: 18),

                      // --- EXTENSION MANAGEMENT BOX ---
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: AppColors.warning.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: AppColors.warning.withValues(alpha: 0.4), width: 1.5),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Row(
                                  children: [
                                    Icon(Icons.schedule_rounded, color: AppColors.warning, size: 18),
                                    SizedBox(width: 8),
                                    Text(
                                      'DELIVERY EXTENSION & OVERRIDES',
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w800,
                                        color: AppColors.warning,
                                        letterSpacing: 0.6,
                                      ),
                                    ),
                                  ],
                                ),
                                TextButton.icon(
                                  onPressed: _resetExtensionFields,
                                  style: TextButton.styleFrom(
                                    foregroundColor: AppColors.success,
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  ),
                                  icon: const Icon(Icons.restart_alt_rounded, size: 16),
                                  label: const Text('Reset Extension', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800)),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Fix accidental extensions submitted by specialists. Reset to 0 if applied by mistake.',
                              style: TextStyle(
                                fontSize: 11,
                                color: isDark ? AppColors.textDim : AppColors.textDarkMuted,
                              ),
                            ),
                            const SizedBox(height: 12),

                            Row(
                              children: [
                                Expanded(
                                  child: TextFormField(
                                    controller: _extensionCountController,
                                    keyboardType: TextInputType.number,
                                    onChanged: (_) => setState(() {}),
                                    decoration: const InputDecoration(labelText: 'Extension Count'),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: TextFormField(
                                    controller: _extensionDaysController,
                                    keyboardType: TextInputType.number,
                                    decoration: const InputDecoration(labelText: 'Extension Days'),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),

                            TextFormField(
                              controller: _extensionReasonController,
                              decoration: const InputDecoration(
                                labelText: 'Extension Reason',
                                hintText: 'Reason for deadline extension...',
                              ),
                            ),
                            const SizedBox(height: 10),

                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              decoration: BoxDecoration(
                                color: (_autoDeliveredOnTime
                                        ? AppColors.success
                                        : _currentExtensionCount > 0
                                            ? Colors.orange
                                            : Colors.blueGrey)
                                    .withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: (_autoDeliveredOnTime
                                          ? AppColors.success
                                          : _currentExtensionCount > 0
                                              ? Colors.orange
                                              : Colors.blueGrey)
                                      .withValues(alpha: 0.35),
                                ),
                              ),
                              child: Row(
                                children: [
                                  Icon(
                                    _autoDeliveredOnTime
                                        ? Icons.verified_rounded
                                        : _currentExtensionCount > 0
                                            ? Icons.schedule_rounded
                                            : Icons.hourglass_bottom_rounded,
                                    size: 20,
                                    color: _autoDeliveredOnTime
                                        ? AppColors.success
                                        : _currentExtensionCount > 0
                                            ? Colors.orange.shade700
                                            : Colors.blueGrey,
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          _autoDeliveredOnTime
                                              ? 'Auto Metric: On-Time Delivery (No Extension)'
                                              : _currentExtensionCount > 0
                                                  ? 'Auto Metric: Not On-Time ($_currentExtensionCount extension${_currentExtensionCount == 1 ? '' : 's'})'
                                                  : 'Auto Metric: Pending Completion (No Extension Yet)',
                                          style: TextStyle(
                                            fontSize: 12.5,
                                            fontWeight: FontWeight.w700,
                                            color: _autoDeliveredOnTime
                                                ? AppColors.success
                                                : _currentExtensionCount > 0
                                                    ? Colors.orange.shade800
                                                    : Colors.blueGrey.shade700,
                                          ),
                                        ),
                                        const SizedBox(height: 2),
                                        const Text(
                                          'Calculated automatically from extension count & completion status',
                                          style: TextStyle(fontSize: 11),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Submit & Cancel Buttons
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton(
                              onPressed: () => Navigator.of(context).pop(),
                              style: OutlinedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(vertical: 14),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                              child: const Text('Cancel'),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            flex: 2,
                            child: ElevatedButton.icon(
                              onPressed: _isSaving ? null : _handleSave,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.primaryBlue,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 14),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                              icon: _isSaving
                                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : const Icon(Icons.check_circle_rounded, size: 18),
                              label: Text(_isSaving ? 'Saving Changes...' : 'Save Changes'),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

